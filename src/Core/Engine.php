<?php

declare(strict_types=1);

namespace PhpScript\Core;

use ErrorException;
use InvalidArgumentException;
use PhpScript\Ast\Program;
use PhpScript\Ast\ReturnStatement;
use PhpScript\Contracts\AstTraverserInterface;
use PhpScript\Contracts\LexerInterface;
use PhpScript\Contracts\ParserInterface;
use PhpScript\Exceptions\BudgetExceededException;
use PhpScript\Exceptions\EngineException;
use PhpScript\Exceptions\LexerException;
use PhpScript\Exceptions\ParseException;
use PhpScript\Linter\LinterService;
use PhpScript\Monarch\MonarchLanguageDefinitionService;
use PhpScript\Runtime\Alarm;
use PhpScript\Runtime\CallGuard;
use PhpScript\Runtime\ExecutionBudget;
use PhpScript\Runtime\Sandbox;
use PhpScript\Runtime\Signature;
use ReflectionFunction;
use Throwable;

final class Engine
{
    /**
     * Lines the engine puts in front of the generated code (`<?php` and `declare`).
     */
    private const int HEADER_LINES = 2;

    /**
     * Functions that break out of the sandbox or change the PHP process. They can
     * never be allowed, not even by mistake.
     */
    public const array NEVER_ALLOWED = [
        'exit', 'die', 'eval', 'include', 'include_once', 'require', 'require_once',
        'extract', 'compact', 'get_defined_vars', 'func_get_args', 'func_get_arg', 'func_num_args',
        'assert', 'assert_options', 'ini_set', 'ini_alter', 'ini_restore', 'set_time_limit', 'ignore_user_abort',
        'putenv', 'set_error_handler', 'set_exception_handler', 'restore_error_handler', 'restore_exception_handler',
        'register_shutdown_function', 'register_tick_function', 'error_reporting', 'header', 'header_remove',
        'mt_srand', 'srand', 'pcntl_alarm', 'pcntl_signal', 'pcntl_async_signals',
        // These call functions named in strings or arrays, which Signature cannot see.
        'call_user_func', 'call_user_func_array', 'forward_static_call', 'forward_static_call_array',
        'filter_var', 'filter_var_array', 'filter_input', 'filter_input_array', 'preg_replace_callback_array',
        // Object injection and global state.
        'unserialize', 'define', 'constant', 'dl', 'set_include_path', 'spl_autoload_register',
    ];

    /**
     * Temporary files of runs that are still in progress. A fatal error (e.g. PHP's
     * own memory limit) skips `finally`, so a shutdown function removes them.
     *
     * @var array<string, true>
     */
    private static array $pendingFiles = [];

    private static bool $cleanupRegistered = false;

    /** @var array<string, mixed> */
    private array $context = [];

    /** @var array<string, string> */
    private array $contextDocumentation = [];

    /**
     * @var string[]
     */
    private array $allowedFunctions = [];

    /**
     * Non-deterministic functions the host explicitly allowed in deterministic mode.
     *
     * @var string[]
     */
    private array $nonDeterministicFunctions = [];

    private bool $deterministic = false;

    private int $executionTimeLimit = 0; // Default to no time limit

    private int $operationLimit = 0; // Default to no operation limit

    private int $memoryLimit = 0; // Default to no memory limit

    public function __construct(
        private readonly LexerInterface $lexer = new Lexer,
        private readonly ParserInterface $parser = new Parser,
        private readonly AstTraverserInterface $astTraverser = new AstTraverser,
    ) {}

    /**
     * Makes PHP functions callable from scripts.
     *
     * @throws InvalidArgumentException if a function does not exist, can never be allowed
     *                                  or takes a callback (use allowWithCallbacks())
     * @throws EngineException if the engine is deterministic and a function is non-deterministic
     */
    public function allow(string ...$functionNames): self
    {
        return $this->allowFunctions($functionNames, callbacks: false);
    }

    /**
     * Allows functions that take a callback, like `array_map` or `usort`. Scripts can
     * only pass names of other allowed functions as callbacks; this is checked when
     * the script runs.
     *
     * @throws InvalidArgumentException if a function does not exist or can never be allowed
     * @throws EngineException if the engine is deterministic and a function is non-deterministic
     */
    public function allowWithCallbacks(string ...$functionNames): self
    {
        return $this->allowFunctions($functionNames, callbacks: true);
    }

    /**
     * Allows functions even when the engine is deterministic. Use with care:
     * the host takes responsibility for reproducible results.
     *
     * @throws InvalidArgumentException if a function does not exist or cannot be allowed
     */
    public function allowNonDeterministic(string ...$functionNames): self
    {
        $this->nonDeterministicFunctions = array_merge(
            $this->nonDeterministicFunctions,
            array_map(DeterminismProfile::normalize(...), $functionNames),
        );

        return $this->allow(...$functionNames);
    }

    /**
     * Deterministic mode refuses functions that depend on time, randomness or the
     * environment (see DeterminismProfile). Pass the current time as a context
     * variable instead, e.g. `->set('now', new DateTimeImmutable)`.
     *
     * @throws EngineException if an already allowed function is non-deterministic
     */
    public function deterministic(bool $enabled = true): self
    {
        if ($enabled) {
            foreach ($this->allowedFunctions as $functionName) {
                $this->assertDeterministic($functionName, deterministic: true);
            }
        }

        $this->deterministic = $enabled;

        return $this;
    }

    public function isDeterministic(): bool
    {
        return $this->deterministic;
    }

    /**
     * @throws InvalidArgumentException if the name is reserved
     */
    public function set(string $name, mixed $value, ?string $documentation = null): self
    {
        if (in_array($name, AstTraverser::RESERVED_VARIABLES, true) || str_starts_with($name, AstTraverser::INTERNAL_PREFIX)) {
            throw new InvalidArgumentException(sprintf("Context variable name '%s' is reserved.", $name));
        }

        $this->context[$name] = $value;
        if ($documentation !== null) {
            $this->contextDocumentation[$name] = $documentation;
        }

        return $this;
    }

    public function forget(string $name): self
    {
        unset($this->context[$name]);

        return $this;
    }

    /**
     * Limits the run time in seconds. With the pcntl extension only the script is
     * aborted. Without it, PHP's own time limit is used, which ends the whole PHP
     * process; in long running workers prefer setOperationLimit(). 0 means unlimited.
     */
    public function setExecutionTimeLimit(int $seconds): self
    {
        $this->executionTimeLimit = max(0, $seconds);

        return $this;
    }

    /**
     * Limits the operations (statements and loop iterations) of each execute(),
     * run() or evaluate() call. 0 means unlimited.
     */
    public function setOperationLimit(int $operations): self
    {
        $this->operationLimit = max(0, $operations);

        return $this;
    }

    /**
     * Limits how much memory a single execute(), run() or evaluate() call may
     * allocate. Best effort: measured as growth of the process memory. 0 means unlimited.
     */
    public function setMemoryLimit(int $bytes): self
    {
        $this->memoryLimit = max(0, $bytes);

        return $this;
    }

    /**
     * Runs a script and returns its echo output.
     *
     * @throws EngineException
     */
    public function execute(string $script): mixed
    {
        return $this->run($script)->output;
    }

    /**
     * Runs a script and returns its echo output together with the value of `return`.
     *
     * @throws EngineException
     */
    public function run(string $script): ExecutionResult
    {
        return $this->runCompiled(...$this->compile($script, expression: false));
    }

    /**
     * Evaluates a single expression, e.g. `data.amount * 1.19`, and returns its value.
     * Statements, assignments and increments are rejected.
     *
     * @throws EngineException
     */
    public function evaluate(string $expression): mixed
    {
        return $this->runCompiled(...$this->compile($expression, expression: true))->value;
    }

    public function linter(string $script): LinterService
    {
        return new LinterService($this->lexer, $this->parser)
            ->withAllowedFunctions($this->allowedFunctions)
            ->withScript($script);
    }

    public function monarchLanguageDefinition(): MonarchLanguageDefinitionService
    {
        return new MonarchLanguageDefinitionService(
            $this->allowedFunctions,
            $this->context,
            $this->contextDocumentation,
        );
    }

    /**
     * @return array{string, Token[]} the generated PHP code and its source map
     *
     * @throws EngineException
     */
    private function compile(string $source, bool $expression): array
    {
        try {
            $tokens = $this->lexer->tokenize($source);
            $ast = $expression
                ? new Program([new ReturnStatement($this->parser->parseExpression($tokens))])
                : $this->parser->parse($tokens);
        } catch (ParseException $e) {
            $token = $e->getToken();
            throw new EngineException($e->getMessage(), $token->line ?? 1, (int) $token?->column, (int) $token?->offset, $token instanceof Token ? max(1, strlen($token->value)) : 1, $e);
        } catch (LexerException $e) {
            throw new EngineException($e->getMessage(), $e->line, $e->column, $e->offset, 1, $e);
        }

        $this->astTraverser->setAllowedFunctions($this->allowedFunctions);
        $this->astTraverser->setBudgetTracking(true);
        $phpCode = $this->astTraverser->traverse($ast);

        return [$phpCode, $this->astTraverser->getSourceMap()];
    }

    /**
     * @param  Token[]  $sourceMap
     *
     * @throws EngineException
     */
    private function runCompiled(string $phpCode, array $sourceMap): ExecutionResult
    {
        $timeLimit = $this->executionTimeLimit;
        $usePcntl = $timeLimit > 0 && function_exists('pcntl_signal') && function_exists('pcntl_alarm');
        $previousHandler = $usePcntl ? pcntl_signal_get_handler(SIGALRM) : null;
        $previousAsync = $usePcntl && pcntl_async_signals();
        $alarm = new Alarm;
        $previousTimeLimit = (int) ini_get('max_execution_time');
        $previousErrorReporting = error_reporting();
        $tmpFile = null;
        $buffering = false;
        $handlerSet = false;

        try {
            error_reporting(-1);
            set_error_handler(
                static function (int $severity, string $message, ?string $file, ?int $line): never {
                    throw new ErrorException($message, 0, $severity, $file, $line);
                }
            );
            $handlerSet = true;

            $tmpFile = $this->createTemporaryFile();
            file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\n" . $phpCode . "return null;\n");

            $budget = new ExecutionBudget($this->operationLimit, $this->memoryLimit);
            $guard = new CallGuard($this->allowedFunctions);

            ob_start();
            $buffering = true;

            if ($usePcntl) {
                pcntl_async_signals(true);
                // Only throw while the script runs: an exception in the cleanup below would skip it.
                pcntl_signal(SIGALRM, static function () use ($timeLimit, $alarm): void {
                    if ($alarm->isArmed()) {
                        throw BudgetExceededException::timeLimit($timeLimit);
                    }
                });
                pcntl_alarm($timeLimit);
            } elseif ($timeLimit > 0) {
                // @codeCoverageIgnoreStart
                set_time_limit($timeLimit);
                // @codeCoverageIgnoreEnd
            }

            $alarm->arm();
            try {
                $value = Sandbox::run($tmpFile, $this->context, $budget, $guard);
            } finally {
                $alarm->disarm();
            }

            $buffering = false;

            return new ExecutionResult((string) ob_get_clean(), $value, $budget->operations());
        } catch (BudgetExceededException $e) {
            $token = $this->locate($e, (string) $tmpFile, $sourceMap);
            throw $e->at($token->line ?? 1, (int) $token?->column, (int) $token?->offset, $token instanceof Token ? strlen($token->value) : 1);
        } catch (EngineException $e) {
            throw $e;
        } catch (Throwable $e) {
            $token = $this->locate($e, (string) $tmpFile, $sourceMap);
            if ($token instanceof Token) {
                throw EngineException::runtimeError($e->getMessage(), $token->line, $token->column, $token->offset, strlen($token->value), $e);
            }

            // @codeCoverageIgnoreStart
            throw EngineException::runtimeError($e->getMessage(), 1, 0, 0, 1, $e);
            // @codeCoverageIgnoreEnd
        } finally {
            if ($buffering) {
                ob_end_clean();
            }

            if ($usePcntl) {
                pcntl_alarm(0);
                pcntl_signal(SIGALRM, is_callable($previousHandler) || is_int($previousHandler) ? $previousHandler : SIG_DFL);
                pcntl_async_signals($previousAsync);
            } elseif ($timeLimit > 0) {
                // @codeCoverageIgnoreStart
                set_time_limit($previousTimeLimit);
                // @codeCoverageIgnoreEnd
            }

            if ($tmpFile !== null) {
                self::removeFile($tmpFile);
            }

            if ($handlerSet) {
                restore_error_handler();
            }

            error_reporting($previousErrorReporting);
        }
    }

    /**
     * Finds the script token for an error: either the error happened in the generated
     * file itself or in a runtime helper called from it.
     *
     * @param  Token[]  $sourceMap
     */
    private function locate(Throwable $e, string $tmpFile, array $sourceMap): ?Token
    {
        $line = $e->getFile() === $tmpFile ? $e->getLine() : null;

        foreach ($line === null ? $e->getTrace() : [] as $frame) {
            if (($frame['file'] ?? null) === $tmpFile) {
                $line = $frame['line'] ?? null;
                break;
            }
        }

        return $line === null ? null : ($sourceMap[$line - self::HEADER_LINES] ?? null);
    }

    /**
     * @throws EngineException
     */
    private function assertDeterministic(string $functionName, bool $deterministic = false): void
    {
        if (($deterministic || $this->deterministic)
            && DeterminismProfile::isNonDeterministic($functionName)
            && ! in_array(DeterminismProfile::normalize($functionName), $this->nonDeterministicFunctions, true)) {
            throw EngineException::nonDeterministicFunction($functionName);
        }
    }

    /**
     * @param  string[]  $functionNames
     *
     * @throws InvalidArgumentException
     * @throws EngineException
     */
    private function allowFunctions(array $functionNames, bool $callbacks): self
    {
        foreach ($functionNames as $functionName) {
            $normalized = DeterminismProfile::normalize($functionName);

            if (in_array($normalized, self::NEVER_ALLOWED, true) || str_starts_with($normalized, 'ob_')) {
                throw new InvalidArgumentException(sprintf("Function '%s' can never be allowed.", $functionName));
            }

            if (preg_match('/^[A-Za-z_]\w*$/', $functionName) !== 1 || ! function_exists($functionName)) {
                throw new InvalidArgumentException(sprintf("Function '%s' does not exist.", $functionName));
            }

            if (! $callbacks && Signature::takesCallback(new ReflectionFunction($functionName))) {
                throw new InvalidArgumentException(sprintf(
                    "Function '%s' takes a callback. Use allowWithCallbacks() to allow it.",
                    $functionName,
                ));
            }

            $this->assertDeterministic($functionName);
        }

        $this->allowedFunctions = array_values(array_unique(array_merge($this->allowedFunctions, $functionNames)));

        return $this;
    }

    /**
     * @throws \PhpScript\Exceptions\EngineException
     */
    private function createTemporaryFile(): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'php-script_run_');
        // @codeCoverageIgnoreStart
        if ($tmpFile === false) {
            throw EngineException::temporaryFileCreationFailed();
        }

        // @codeCoverageIgnoreEnd

        if (! self::$cleanupRegistered) {
            self::$cleanupRegistered = true;
            register_shutdown_function(self::removePendingFiles(...));
        }

        self::$pendingFiles[$tmpFile] = true;

        return $tmpFile;
    }

    /**
     * Removes temporary files that a fatal error or exit left behind.
     */
    public static function removePendingFiles(): void
    {
        foreach (array_keys(self::$pendingFiles) as $file) {
            self::removeFile($file);
        }
    }

    private static function removeFile(string $file): void
    {
        unset(self::$pendingFiles[$file]);

        if (is_file($file)) {
            unlink($file);
        }
    }
}

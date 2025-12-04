<?php

declare(strict_types=1);

namespace PhpScript\Core;

use ErrorException;
use PhpScript\Contracts\AstTraverserInterface;
use PhpScript\Contracts\LexerInterface;
use PhpScript\Contracts\ParserInterface;
use PhpScript\Exceptions\EngineException;
use PhpScript\Exceptions\LexerException;
use PhpScript\Exceptions\ParseException;
use PhpScript\Linter\LinterService;
use PhpScript\Monarch\MonarchLanguageDefinitionService;
use Throwable;

final class Engine
{
    /** @var array<string, mixed> */
    private array $context = [];

    /** @var array<string, string> */
    private array $contextDocumentation = [];

    /**
     * @var string[]
     */
    private array $allowedFunctions = [];

    private int $executionTimeLimit = 0; // Default to no time limit

    public function __construct(
        private readonly LexerInterface $lexer = new Lexer,
        private readonly ParserInterface $parser = new Parser,
        private readonly AstTraverserInterface $astTraverser = new AstTraverser,
    ) {}

    public function allow(string ...$functionNames): self
    {
        $this->allowedFunctions = array_merge($this->allowedFunctions, $functionNames);

        return $this;
    }

    public function set(string $name, mixed $value, ?string $documentation = null): self
    {
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

    public function setExecutionTimeLimit(int $seconds): self
    {
        $this->executionTimeLimit = $seconds;

        return $this;
    }

    /**
     * @throws \PhpScript\Exceptions\EngineException
     */
    public function execute(string $script): mixed
    {
        $usePcntl = $this->executionTimeLimit > 0 && extension_loaded('pcntl') && function_exists('pcntl_signal');

        if ($usePcntl) {
            pcntl_async_signals(true);
            pcntl_signal(SIGALRM, function (): never {
                throw new EngineException(sprintf('Maximum execution time of %d second(s) exceeded', $this->executionTimeLimit));
            });
        }

        $previousErrorReporting = error_reporting(-1);
        set_error_handler(
            function (int $severity, string $message, ?string $file, ?int $line): never {
                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );

        try {
            $tokens = $this->lexer->tokenize($script);
            $ast = $this->parser->parse($tokens);
            $this->astTraverser->setAllowedFunctions($this->allowedFunctions);
            $phpCode = $this->astTraverser->traverse($ast);
            $sourceMap = $this->astTraverser->getSourceMap();

            extract($this->context);

            ob_start();

            $tmpFile = $this->createTemporaryFile();

            file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\n" . $phpCode);

            // Set the execution time limit before including the file
            if ($usePcntl) {
                pcntl_alarm($this->executionTimeLimit);
            } else {
                set_time_limit($this->executionTimeLimit);
            }

            include $tmpFile;

            if ($usePcntl) {
                pcntl_alarm(0); // Cancel alarm
            }

            unlink($tmpFile);
        } catch (ParseException $e) {
            $token = $e->getToken();
            throw new EngineException($e->getMessage(), (int) $token?->line, (int) $token?->column, (int) $token?->offset, $token instanceof Token ? strlen($token->value) : 1, $e);
        } catch (EngineException $e) {
            throw $e;
        } catch (LexerException $e) {
            throw new EngineException($e->getMessage(), $e->line, $e->column, $e->offset, 1, $e);
        } catch (Throwable $e) {
            ob_end_clean();
            if (isset($tmpFile) && file_exists($tmpFile)) {
                unlink($tmpFile);
            }

            $line = $e->getLine() - 2;
            $token = $sourceMap[$line] ?? null;
            if ($token instanceof Token) {
                $length = strlen($token->value);
                throw EngineException::runtimeError($e->getMessage(), $token->line, $token->column, $token->offset, $length, $e);
            }

            // @codeCoverageIgnoreStart
            throw EngineException::runtimeError($e->getMessage(), 1, 0, 0, 1, $e);
            // @codeCoverageIgnoreEnd
        } finally {
            if ($usePcntl) {
                pcntl_signal(SIGALRM, SIG_DFL); // Restore default handler
            }

            restore_error_handler();
            error_reporting($previousErrorReporting);
        }

        return ob_get_clean();
    }

    public function linter(string $script): LinterService
    {
        return new LinterService($this->lexer, $this->parser)
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
        return $tmpFile;
    }
}

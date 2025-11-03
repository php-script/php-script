<?php

declare(strict_types=1);

namespace PhpScript\Core;

use ErrorException;
use PhpScript\Contracts\LexerInterface;
use PhpScript\Exceptions\EngineException;
use PhpScript\Exceptions\SecurityException;
use Throwable;

final class Engine
{
    /**
     * @var array<string, mixed>
     */
    private array $context = [];

    /**
     * @var string[]
     */
    private array $forbiddenFunctions = [
        'exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open',
        'eval', 'create_function', 'assert',
        'fopen', 'file_get_contents', 'file_put_contents', 'unlink', 'rmdir', 'mkdir',
        'include', 'require', 'include_once', 'require_once',
        'mysql_', 'mysqli_', 'pdo',
    ];

    public function __construct(private readonly ?LexerInterface $lexer = new Lexer)
    {
    }

    public function set(string $name, mixed $value): self
    {
        $this->context[$name] = $value;

        return $this;
    }

    public function forget(string $name): self
    {
        unset($this->context[$name]);

        return $this;
    }

    public function execute(string $script): mixed
    {
        $this->ensureScriptCanBeExecuted($script);

        $previousErrorReporting = error_reporting(-1);
        set_error_handler(function (int $severity, string $message, ?string $file, ?int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        $phpCode = $this->transpile($script);

        extract($this->context);

        ob_start();

        try {
            $tmpFile = $this->createTemporaryFile();

            file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\n".$phpCode);
            include $tmpFile;

            unlink($tmpFile);
        } catch (Throwable $e) {
            ob_end_clean();
            unlink($tmpFile);
            throw EngineException::runtimeError($e->getMessage(), $e->getLine() - 2, $e);
        } finally {
            restore_error_handler();
            error_reporting($previousErrorReporting);
        }

        return ob_get_clean();
    }

    /**
     * @throws \PhpScript\Exceptions\SecurityException
     */
    private function ensureScriptCanBeExecuted(string $script): void
    {
        foreach ($this->forbiddenFunctions as $func) {
            if (preg_match('/\b'.$func.'\s*\(/i', $script)) {
                throw SecurityException::invalidFunctionCall($func);
            }
        }
    }

    private function transpile(string $script): string
    {
        $tokens = $this->lexer->tokenize($script);

        $phpCode = '';

        /**
         * Indicates whether the current element is the start of a chain.
         *
         * This boolean variable determines if the current processing unit,
         * node, or element is the first in a sequence or chain. It can be
         * used in contexts where chaining or linked sequences are managed,
         * enabling operations or logic to treat the start of the chain
         * differently. Example: user.logins.count -> $user->logins->count()
         *
         * @var bool $isStartOfChain
         */
        $isStartOfChain = true;

        foreach ($tokens as $token) {
            $value = $token->value;

            switch ($token->type) {
                case TokenType::T_IDENTIFIER:
                    if ($isStartOfChain) {
                        $phpCode .= '$'.$value; // e.g. user -> $user OR u1 -> $u1
                    } else {
                        $phpCode .= $value; // e.g. logins -> logins
                    }
                    $isStartOfChain = false; // after the identifier the chain is not at the start anymore
                    break;

                case TokenType::T_DOT:
                    $phpCode .= '->'; // . -> ->
                    $isStartOfChain = false; // we are within a chain
                    break;

                case TokenType::T_CONCAT:
                    $phpCode .= '.'; // ~ -> . (String concatenation)
                    $isStartOfChain = true;
                    break;

                    // reset the chain
                    // after "(", "+", "=" etc. the next thing has to be a variable
                case TokenType::T_LPAREN:
                case TokenType::T_RPAREN:
                case TokenType::T_LBRACE:
                case TokenType::T_RBRACE:
                case TokenType::T_SEMICOLON:
                case TokenType::T_EQUALS:
                case TokenType::T_EQUALS_EQUALS:
                case TokenType::T_PLUS:
                case TokenType::T_MINUS:
                case TokenType::T_MULTIPLY:
                case TokenType::T_DIVIDE:
                case TokenType::T_GT:
                case TokenType::T_LT:
                case TokenType::T_IF:
                case TokenType::T_ELSE:
                case TokenType::T_FOREACH:
                case TokenType::T_AS:
                case TokenType::T_ECHO:
                case TokenType::T_RETURN:

                case TokenType::T_NUMBER:
                case TokenType::T_STRING:
                    // @codeCoverageIgnoreStart
                case TokenType::T_TRUE:
                case TokenType::T_FALSE:
                case TokenType::T_NULL:
                    // @codeCoverageIgnoreEnd
                default:
                    $phpCode .= $value;
                    $isStartOfChain = true;
                    break;
            }
            $phpCode .= ' '; // just for better feeling
        }

        return $phpCode;
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

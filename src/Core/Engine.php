<?php

declare(strict_types=1);

namespace PhpScript\Core;

use ErrorException;
use PhpScript\Exceptions\EngineException;
use PhpScript\Exceptions\ParseException;
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

    public function __construct(
        private readonly Lexer $lexer = new Lexer,
        private readonly Parser $parser = new Parser,
        private readonly AstTraverser $astTraverser = new AstTraverser,
    ) {}

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

        try {
            $tokens = $this->lexer->tokenize($script);
            $ast = $this->parser->parse($tokens);
            $phpCode = $this->astTraverser->traverse($ast);
            $sourceMap = $this->astTraverser->getSourceMap();

            extract($this->context);

            ob_start();

            $tmpFile = $this->createTemporaryFile();

            file_put_contents($tmpFile, "<?php\ndeclare(strict_types=1);\n".$phpCode);
            include $tmpFile;

            unlink($tmpFile);
        } catch (ParseException $e) {
            $token = $e->getToken();
            throw new EngineException($e->getMessage(), $token->line, $token->column, $token->offset, $e);
        } catch (Throwable $e) {
            ob_end_clean();
            if (isset($tmpFile) && is_string($tmpFile)) {
                unlink($tmpFile);
            }

            $line = $e->getLine() - 2;
            $token = $sourceMap[$line] ?? $e->getToken() ?? null;
            if ($token) {
                throw EngineException::runtimeError($e->getMessage(), $token->line, $token->column, $token->offset, $e);
            }

            throw EngineException::runtimeError($e->getMessage(), 0, 0, 0, $e);
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
            if (preg_match('/\b'.$func.'\s*\(/i', $script, $matches, PREG_OFFSET_CAPTURE)) {
                $offset = $matches[0][1];
                $line = substr_count(substr($script, 0, $offset), "\n") + 1;
                $col = $offset - (int) strrpos(substr($script, 0, $offset), "\n");
                throw SecurityException::invalidFunctionCall($func, $line, $col, $offset);
            }
        }
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

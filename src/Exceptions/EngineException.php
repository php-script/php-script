<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

use RuntimeException;
use Throwable;

class EngineException extends RuntimeException
{
    /**
     * The message without prefix and source position, e.g. for editor markers.
     */
    public readonly string $reason;

    public function __construct(
        string $message,
        public int $line = 1,
        public readonly int $column = 0,
        public readonly int $offset = 0,
        public readonly int $length = 1,
        ?Throwable $previous = null,
        ?string $reason = null,
    ) {
        parent::__construct($message, 0, $previous);
        $this->reason = $reason ?? $message;
    }

    /**
     * @codeCoverageIgnore
     */
    public static function temporaryFileCreationFailed(): self
    {
        return new self('Temporary file creation failed.');
    }

    public static function runtimeError(string $message, int $line, int $column, int $offset, int $length, ?Throwable $previous = null): self
    {
        $formatted = sprintf('Runtime error: %s in line: %d, column: %d, offset: %d', $message, $line, $column, $offset);

        return new self($formatted, $line, $column, $offset, $length, $previous, $message);
    }

    /**
     * The script uses a construct that is not allowed; found before it runs.
     */
    public static function compileError(string $message, int $line, int $column, int $offset, int $length): self
    {
        $formatted = sprintf('%s in line: %d, column: %d, offset: %d', $message, $line, $column, $offset);

        return new self($formatted, $line, $column, $offset, $length, reason: $message);
    }

    public static function invalidFunctionCall(string $functionName, int $line, int $column, int $offset, int $length, ?Throwable $previous = null): self
    {
        $message = sprintf('Invalid function call: %s in line: %d, column: %d, offset: %d', $functionName, $line, $column, $offset);

        return new self($message, $line, $column, $offset, $length, $previous, 'Invalid function call: ' . $functionName);
    }

    public static function nonDeterministicFunction(string $functionName): self
    {
        return new self(sprintf("Function '%s' is not deterministic. Use allowNonDeterministic() to allow it anyway.", $functionName));
    }
}

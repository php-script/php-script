<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

use RuntimeException;
use Throwable;

class EngineException extends RuntimeException
{
    public function __construct(
        string $message,
        public int $line = 1,
        public readonly int $column = 0,
        public readonly int $offset = 0,
        public readonly int $length = 1,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
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
        $message = sprintf('Runtime error: %s in line: %d, column: %d, offset: %d', $message, $line, $column, $offset);

        return new self($message, $line, $column, $offset, $length, $previous);
    }

    public static function invalidFunctionCall(string $functionName, int $line, int $column, int $offset, int $length, ?Throwable $previous = null): self
    {
        $message = sprintf('Invalid function call: %s in line: %d, column: %d, offset: %d', $functionName, $line, $column, $offset);

        return new self($message, $line, $column, $offset, $length, $previous);
    }
}

<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

use Throwable;

class EngineException extends \RuntimeException
{
    /**
     * @codeCoverageIgnore
     */
    public static function temporaryFileCreationFailed(): self
    {
        return new self('Temporary file creation failed.');
    }

    public static function runtimeError(string $message, int $line, ?Throwable $previous = null): self
    {
        return new self("Runtime error: $message in line: $line", previous: $previous);
    }
}

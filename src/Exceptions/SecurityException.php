<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

class SecurityException extends \RuntimeException
{
    public static function invalidFunctionCall(string $functionName, int $line, int $column, int $offset): self
    {
        return new self("Invalid function call: $functionName at line: $line, column: $column, offset: $offset");
    }
}

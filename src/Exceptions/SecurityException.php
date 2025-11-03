<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

class SecurityException extends \RuntimeException
{
    public static function invalidFunctionCall(string $functionName): self
    {
        return new self("Invalid function call: $functionName");
    }
}

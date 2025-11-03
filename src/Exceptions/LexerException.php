<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

class LexerException extends \RuntimeException
{
    public static function unknownCharOrSyntaxError(string $token): self
    {
        return new self("Unknown char or syntax error: $token");
    }
}

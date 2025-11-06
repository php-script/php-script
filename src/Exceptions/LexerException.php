<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

use RuntimeException;
use Throwable;

class LexerException extends RuntimeException
{
    public function __construct(
        string $message,
        public int $line = 0,
        public readonly int $column = 0,
        public readonly int $offset = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function unknownCharOrSyntaxError(string $token, int $line = 0, int $column = 0, int $offset = 0): self
    {
        $token = str_replace(["\r\n", "\n", "\r"], '⏎', $token);
        if (mb_strlen($token) > 10) {
            $token = mb_substr($token, 0, 10) . '…';
        }

        return new self("Unknown character or syntax error `$token` at line $line, column $column.", $line, $column, $offset);
    }
}

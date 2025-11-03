<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

use PhpScript\Core\Token;

class ParseException extends \RuntimeException
{
    public function __construct(string $message, private readonly ?Token $token)
    {
        parent::__construct($message);
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }
}

<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

abstract readonly class BaseNode implements Node
{
    public function __construct(private ?Token $token = null) {}

    public function getToken(): ?Token
    {
        return $this->token;
    }
}

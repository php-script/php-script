<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

final readonly class PostfixOperation extends BaseNode
{
    public function __construct(
        public Node $left,
        public TokenType $operator,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'left' => $this->left->toArray(),
            'operator' => $this->operator->value,
        ];
    }
}

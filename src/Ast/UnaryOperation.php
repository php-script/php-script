<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

final readonly class UnaryOperation extends BaseNode
{
    public function __construct(
        public TokenType $operator,
        public Node $right,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'operator' => $this->operator->value,
            'right' => $this->right->toArray(),
        ];
    }
}

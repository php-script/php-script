<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\TokenType;

final readonly class BinaryOperation implements Node
{
    public function __construct(
        public Node $left,
        public TokenType $operator,
        public Node $right,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'left' => $this->left->toArray(),
            'operator' => $this->operator->value,
            'right' => $this->right->toArray(),
        ];
    }
}

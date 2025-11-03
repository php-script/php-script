<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

final readonly class Assignment implements Node
{
    public function __construct(
        public Variable $variable,
        public Node $expression,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'variable' => $this->variable->toArray(),
            'expression' => $this->expression->toArray(),
        ];
    }
}

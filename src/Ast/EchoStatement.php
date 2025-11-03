<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

final readonly class EchoStatement implements Node
{
    public function __construct(
        public Node $expression,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'expression' => $this->expression->toArray(),
        ];
    }
}

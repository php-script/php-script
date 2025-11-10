<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class IfStatement extends BaseNode
{
    public function __construct(
        public Node $condition,
        public Node $then,
        public ?Node $else,
        Token $token
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'condition' => $this->condition->toArray(),
            'then' => $this->then->toArray(),
            'else' => $this->else?->toArray() ?? null,
        ];
    }
}

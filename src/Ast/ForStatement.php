<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class ForStatement extends BaseNode
{
    public function __construct(
        public ?Node $initializer,
        public ?Node $condition,
        public ?Node $increment,
        public Node $body,
        Token $token
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'initializer' => $this->initializer?->toArray(),
            'condition' => $this->condition?->toArray(),
            'increment' => $this->increment?->toArray(),
            'body' => $this->body->toArray(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class ForeachStatement extends BaseNode
{
    public function __construct(
        public Node $iterable,
        public Variable $value,
        public ?Variable $key,
        public Node $body,
        Token $token
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'iterable' => $this->iterable->toArray(),
            'value' => $this->value->toArray(),
            'key' => $this->key?->toArray(),
            'body' => $this->body->toArray(),
        ];
    }
}

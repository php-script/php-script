<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class EchoStatement extends BaseNode
{
    public function __construct(
        public Node $expression,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'expression' => $this->expression->toArray(),
        ];
    }
}

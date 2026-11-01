<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class ReturnStatement extends BaseNode
{
    public function __construct(
        public ?Node $expression = null,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'expression' => $this->expression?->toArray(),
        ];
    }
}

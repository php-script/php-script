<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class Assignment extends BaseNode
{
    public function __construct(
        public Variable|MemberAccess|ArrayAccess $variable,
        public Node $expression,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'variable' => $this->variable->toArray(),
            'expression' => $this->expression->toArray(),
        ];
    }
}

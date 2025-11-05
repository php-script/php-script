<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Core\Token;

final readonly class Variable extends BaseNode
{
    public function __construct(
        public string $name,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'name' => $this->name,
        ];
    }
}

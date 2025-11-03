<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

final readonly class Variable implements Node
{
    public function __construct(
        public string $name,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'name' => $this->name,
        ];
    }
}

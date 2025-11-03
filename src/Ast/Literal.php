<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

final readonly class Literal implements Node
{
    public function __construct(
        public mixed $value,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'value' => $this->value,
        ];
    }
}

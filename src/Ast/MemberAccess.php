<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

final readonly class MemberAccess implements Node
{
    public function __construct(
        public Node $object,
        public Identifier $property,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'object' => $this->object->toArray(),
            'property' => $this->property->toArray(),
        ];
    }
}

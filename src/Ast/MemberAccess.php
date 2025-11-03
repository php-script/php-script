<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class MemberAccess extends BaseNode
{
    public function __construct(
        public Node $object,
        public Identifier $property,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'object' => $this->object->toArray(),
            'property' => $this->property->toArray(),
        ];
    }
}

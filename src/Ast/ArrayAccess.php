<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class ArrayAccess extends BaseNode
{
    public function __construct(
        public Node $array,
        public Node $key,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'array' => $this->array->toArray(),
            'key' => $this->key->toArray(),
        ];
    }
}

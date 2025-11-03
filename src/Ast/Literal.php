<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Core\Token;

final readonly class Literal extends BaseNode
{
    public function __construct(
        public mixed $value,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'value' => $this->value,
        ];
    }
}

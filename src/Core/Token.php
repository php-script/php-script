<?php

declare(strict_types=1);

namespace PhpScript\Core;

use JsonSerializable;
use Serializable;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'value' => $this->value,
        ];
    }
}

<?php

declare(strict_types=1);

namespace PhpScript\Core;

final readonly class Token
{
    public function __construct(
        public TokenType $type,
        public string $value,
    ) {}

    /**
     * @return array{type: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'value' => $this->value,
        ];
    }
}

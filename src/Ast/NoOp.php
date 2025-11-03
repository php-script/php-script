<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

/**
 * Represents a no-operation statement (e.g., an empty statement ';').
 */
final readonly class NoOp implements Node
{
    public function toArray(): array
    {
        return [
            'type' => self::class,
        ];
    }
}

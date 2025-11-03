<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Core\Token;

/**
 * Represents a no-operation statement (e.g., an empty statement ';').
 */
final readonly class NoOp extends BaseNode
{
    public function __construct(?Token $token = null)
    {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
        ];
    }
}

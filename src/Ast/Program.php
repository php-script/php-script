<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

final readonly class Program implements Node
{
    /**
     * @param  Node[]  $statements
     */
    public function __construct(
        public array $statements,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'statements' => array_map(static fn (Node $node): array => $node->toArray(), $this->statements),
        ];
    }
}

<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;

final readonly class Program extends BaseNode
{
    /**
     * @param  Node[]  $statements
     */
    public function __construct(
        public array $statements,
    ) {
        parent::__construct();
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'statements' => array_map(static fn (Node $node): array => $node->toArray(), $this->statements),
        ];
    }
}

<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

final readonly class FunctionCall extends BaseNode
{
    /**
     * @param  Node[]  $arguments
     */
    public function __construct(
        public Node $callee,
        public array $arguments,
        ?Token $token = null
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'callee' => $this->callee->toArray(),
            'arguments' => array_map(fn (Node $node) => $node->toArray(), $this->arguments),
        ];
    }
}

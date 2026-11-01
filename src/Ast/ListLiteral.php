<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

/**
 * A list literal like `[1, 2, 3]`. Evaluates to a PHP list array.
 */
final readonly class ListLiteral extends BaseNode
{
    /**
     * @param  list<Node>  $elements
     */
    public function __construct(
        public array $elements,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'elements' => array_map(fn (Node $node): array => $node->toArray(), $this->elements),
        ];
    }
}

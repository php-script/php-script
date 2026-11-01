<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\Node;
use PhpScript\Core\Token;

/**
 * A map literal like `{name: 'John', 'e-mail': mail}`. Evaluates to a stdClass,
 * so an empty map `{}` stays distinguishable from an empty list `[]`.
 */
final readonly class MapLiteral extends BaseNode
{
    /**
     * @param  list<array{string, Node}>  $entries  key/value pairs in source order
     */
    public function __construct(
        public array $entries,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'entries' => array_map(
                fn (array $entry): array => ['key' => $entry[0], 'value' => $entry[1]->toArray()],
                $this->entries,
            ),
        ];
    }
}

<?php

declare(strict_types=1);

use PhpScript\Ast\ListLiteral;
use PhpScript\Ast\Literal;
use PhpScript\Ast\MapLiteral;
use PhpScript\Ast\ReturnStatement;

it('returns the array representation of a list literal', function (): void {
    expect(new ListLiteral([new Literal(1)])->toArray())->toBe([
        'type' => ListLiteral::class,
        'elements' => [['type' => Literal::class, 'value' => 1]],
    ]);
});

it('returns the array representation of a map literal', function (): void {
    expect(new MapLiteral([['a', new Literal(1)]])->toArray())->toBe([
        'type' => MapLiteral::class,
        'entries' => [['key' => 'a', 'value' => ['type' => Literal::class, 'value' => 1]]],
    ]);
});

it('returns the array representation of a return statement', function (): void {
    expect(new ReturnStatement(new Literal(1))->toArray())->toBe([
        'type' => ReturnStatement::class,
        'expression' => ['type' => Literal::class, 'value' => 1],
    ])->and(new ReturnStatement()->toArray())->toBe([
        'type' => ReturnStatement::class,
        'expression' => null,
    ]);
});

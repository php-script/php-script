<?php

use PhpScript\Ast\Literal;

it('should return correct array representation for a string', function (): void {
    $literal = new Literal('hello');
    expect($literal->toArray())->toBe([
        'type' => Literal::class,
        'value' => 'hello',
    ]);
});

it('should return correct array representation for a number', function (): void {
    $literal = new Literal(123);
    expect($literal->toArray())->toBe([
        'type' => Literal::class,
        'value' => 123,
    ]);
});

it('should return correct array representation for null', function (): void {
    $literal = new Literal(null);
    expect($literal->toArray())->toBe([
        'type' => Literal::class,
        'value' => null,
    ]);
});

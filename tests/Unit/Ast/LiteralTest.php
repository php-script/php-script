<?php

use PhpScript\Ast\Literal;

it('should return correct array representation for a string', function () {
    $literal = new Literal('hello');
    expect($literal->toArray())->toBe([
        'type' => Literal::class,
        'value' => 'hello',
    ]);
});

it('should return correct array representation for a number', function () {
    $literal = new Literal(123);
    expect($literal->toArray())->toBe([
        'type' => Literal::class,
        'value' => 123,
    ]);
});

it('should return correct array representation for null', function () {
    $literal = new Literal(null);
    expect($literal->toArray())->toBe([
        'type' => Literal::class,
        'value' => null,
    ]);
});

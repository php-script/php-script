<?php

use PhpScript\Ast\Assignment;
use PhpScript\Ast\Literal;
use PhpScript\Ast\Variable;

it('should return correct array representation', function () {
    $assignment = new Assignment(
        new Variable('foo'),
        new Literal('bar')
    );

    expect($assignment->toArray())->toBe([
        'type' => Assignment::class,
        'variable' => [
            'type' => Variable::class,
            'name' => 'foo',
        ],
        'expression' => [
            'type' => Literal::class,
            'value' => 'bar',
        ],
    ]);
});

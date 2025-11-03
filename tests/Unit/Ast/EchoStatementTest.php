<?php

use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\Literal;

it('should return correct array representation', function () {
    $echoStatement = new EchoStatement(
        new Literal('hello world')
    );

    expect($echoStatement->toArray())->toBe([
        'type' => EchoStatement::class,
        'expression' => [
            'type' => Literal::class,
            'value' => 'hello world',
        ],
    ]);
});

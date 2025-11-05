<?php

declare(strict_types=1);

use PhpScript\Ast\FunctionCall;
use PhpScript\Ast\Identifier;
use PhpScript\Ast\Literal;

it('converts to array correctly', function (): void {
    $functionCall = new FunctionCall(
        new Identifier('my_function'),
        [
            new Literal('arg1'),
            new Literal(123),
        ]
    );

    $expectedArray = [
        'type' => FunctionCall::class,
        'callee' => [
            'type' => Identifier::class,
            'name' => 'my_function',
        ],
        'arguments' => [
            [
                'type' => Literal::class,
                'value' => 'arg1',
            ],
            [
                'type' => Literal::class,
                'value' => 123,
            ],
        ],
    ];

    expect($functionCall->toArray())->toBe($expectedArray);
});

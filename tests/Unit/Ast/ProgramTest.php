<?php

use PhpScript\Ast\NoOp;
use PhpScript\Ast\Program;

it('should return correct array representation', function () {
    $program = new Program([
        new NoOp(),
    ]);

    expect($program->toArray())->toBe([
        'type' => Program::class,
        'statements' => [
            [
                'type' => NoOp::class,
            ],
        ],
    ]);
});

it('should return correct array representation with empty statements', function () {
    $program = new Program([]);

    expect($program->toArray())->toBe([
        'type' => Program::class,
        'statements' => [],
    ]);
});

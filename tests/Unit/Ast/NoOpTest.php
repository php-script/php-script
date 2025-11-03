<?php

use PhpScript\Ast\NoOp;

it('should return correct array representation', function () {
    $noOp = new NoOp;
    expect($noOp->toArray())->toBe([
        'type' => NoOp::class,
    ]);
});

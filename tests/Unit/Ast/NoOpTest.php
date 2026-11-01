<?php

declare(strict_types=1);

use PhpScript\Ast\NoOp;

it('should return correct array representation', function (): void {
    $noOp = new NoOp;
    expect($noOp->toArray())->toBe([
        'type' => NoOp::class,
    ]);
});

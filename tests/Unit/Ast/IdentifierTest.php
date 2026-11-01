<?php

declare(strict_types=1);

use PhpScript\Ast\Identifier;

it('should return correct array representation', function (): void {
    $identifier = new Identifier('foo');
    expect($identifier->toArray())->toBe([
        'type' => Identifier::class,
        'name' => 'foo',
    ]);
});

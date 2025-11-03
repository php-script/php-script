<?php

use PhpScript\Ast\Identifier;

it('should return correct array representation', function () {
    $identifier = new Identifier('foo');
    expect($identifier->toArray())->toBe([
        'type' => Identifier::class,
        'name' => 'foo',
    ]);
});

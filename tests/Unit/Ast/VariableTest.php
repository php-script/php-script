<?php

use PhpScript\Ast\Variable;

it('should return correct array representation', function () {
    $variable = new Variable('foo');
    expect($variable->toArray())->toBe([
        'type' => Variable::class,
        'name' => 'foo',
    ]);
});

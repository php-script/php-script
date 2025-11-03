<?php

use PhpScript\Ast\Identifier;
use PhpScript\Ast\MemberAccess;
use PhpScript\Ast\Variable;

it('should return correct array representation', function () {
    $memberAccess = new MemberAccess(
        new Variable('foo'),
        new Identifier('bar')
    );

    expect($memberAccess->toArray())->toBe([
        'type' => MemberAccess::class,
        'object' => [
            'type' => Variable::class,
            'name' => 'foo',
        ],
        'property' => [
            'type' => Identifier::class,
            'name' => 'bar',
        ],
    ]);
});

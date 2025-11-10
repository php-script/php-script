<?php

use PhpScript\Ast\ArrayAccess;
use PhpScript\Ast\Literal;
use PhpScript\Ast\Variable;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can create an array access', function () {
    $array = new Variable('foo', new Token(TokenType::T_IDENTIFIER, 'foo', 1, 1, 0));
    $key = new Literal('bar', new Token(TokenType::T_STRING, 'bar', 1, 1, 0));
    $arrayAccess = new ArrayAccess($array, $key, new Token(TokenType::T_LEFT_BRACKET, '[', 1, 1, 0));

    expect($arrayAccess->array)->toBe($array);
    expect($arrayAccess->key)->toBe($key);
    expect($arrayAccess->toArray())->toBe([
        'type' => ArrayAccess::class,
        'array' => $array->toArray(),
        'key' => $key->toArray(),
    ]);
});

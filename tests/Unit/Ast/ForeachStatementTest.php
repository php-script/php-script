<?php

use PhpScript\Ast\ForeachStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\Program;
use PhpScript\Ast\Variable;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can create a foreach statement', function () {
    $iterable = new Literal([], new Token(TokenType::T_IDENTIFIER, 'test', 1, 1, 0));
    $value = new Variable('value', new Token(TokenType::T_IDENTIFIER, 'value', 1, 1, 0));
    $key = new Variable('key', new Token(TokenType::T_IDENTIFIER, 'key', 1, 1, 0));
    $body = new Program([], new Token(TokenType::T_LEFT_BRACE, '{', 1, 1, 0));
    $foreach = new ForeachStatement($iterable, $value, $key, $body, new Token(TokenType::T_FOREACH, 'foreach', 1, 1, 0));

    expect($foreach->iterable)->toBe($iterable);
    expect($foreach->value)->toBe($value);
    expect($foreach->key)->toBe($key);
    expect($foreach->body)->toBe($body);
    expect($foreach->toArray())->toBe([
        'type' => ForeachStatement::class,
        'iterable' => $iterable->toArray(),
        'value' => $value->toArray(),
        'key' => $key->toArray(),
        'body' => $body->toArray(),
    ]);
});

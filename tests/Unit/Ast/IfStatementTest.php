<?php

use PhpScript\Ast\IfStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\Program;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can create an if statement', function () {
    $condition = new Literal(true, new Token(TokenType::T_TRUE, 'true', 1, 1, 0));
    $then = new Program([], new Token(TokenType::T_LEFT_BRACE, '{', 1, 1, 0));
    $else = new Program([], new Token(TokenType::T_LEFT_BRACE, '{', 1, 1, 0));
    $if = new IfStatement($condition, $then, $else, new Token(TokenType::T_IF, 'if', 1, 1, 0));

    expect($if->condition)->toBe($condition);
    expect($if->then)->toBe($then);
    expect($if->else)->toBe($else);
    expect($if->toArray())->toBe([
        'type' => IfStatement::class,
        'condition' => $condition->toArray(),
        'then' => $then->toArray(),
        'else' => $else->toArray(),
    ]);
});

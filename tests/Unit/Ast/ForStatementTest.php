<?php

use PhpScript\Ast\ForStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\Program;
use PhpScript\Ast\Variable;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can create a for statement', function (): void {
    $initializer = new Variable('i', new Token(TokenType::T_IDENTIFIER, 'i', 1, 1, 0));
    $condition = new Literal(true, new Token(TokenType::T_TRUE, 'true', 1, 1, 0));
    $increment = new Variable('i', new Token(TokenType::T_IDENTIFIER, 'i', 1, 1, 0));
    $body = new Program([], new Token(TokenType::T_LEFT_BRACE, '{', 1, 1, 0));
    $for = new ForStatement($initializer, $condition, $increment, $body, new Token(TokenType::T_FOR, 'for', 1, 1, 0));

    expect($for->initializer)->toBe($initializer);
    expect($for->condition)->toBe($condition);
    expect($for->increment)->toBe($increment);
    expect($for->body)->toBe($body);
    expect($for->toArray())->toBe([
        'type' => ForStatement::class,
        'initializer' => $initializer->toArray(),
        'condition' => $condition->toArray(),
        'increment' => $increment->toArray(),
        'body' => $body->toArray(),
    ]);
});

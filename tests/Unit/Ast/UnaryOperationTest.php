<?php

use PhpScript\Ast\Literal;
use PhpScript\Ast\UnaryOperation;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can create a unary operation', function (): void {
    $right = new Literal(true, new Token(TokenType::T_TRUE, 'true', 1, 1, 0));
    $unary = new UnaryOperation(TokenType::T_BANG, $right, new Token(TokenType::T_BANG, '!', 1, 1, 0));

    expect($unary->operator)->toBe(TokenType::T_BANG);
    expect($unary->right)->toBe($right);
    expect($unary->toArray())->toBe([
        'type' => UnaryOperation::class,
        'operator' => TokenType::T_BANG->value,
        'right' => $right->toArray(),
    ]);
});

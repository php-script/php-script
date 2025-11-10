<?php

use PhpScript\Ast\PostfixOperation;
use PhpScript\Ast\Variable;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can create a postfix operation', function () {
    $left = new Variable('i', new Token(TokenType::T_IDENTIFIER, 'i', 1, 1, 0));
    $postfix = new PostfixOperation($left, TokenType::T_INCREMENT, new Token(TokenType::T_INCREMENT, '++', 1, 1, 0));

    expect($postfix->left)->toBe($left);
    expect($postfix->operator)->toBe(TokenType::T_INCREMENT);
    expect($postfix->toArray())->toBe([
        'type' => PostfixOperation::class,
        'left' => $left->toArray(),
        'operator' => TokenType::T_INCREMENT->value,
    ]);
});

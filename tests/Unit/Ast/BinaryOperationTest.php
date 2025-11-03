<?php

use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\Literal;
use PhpScript\Core\TokenType;

it('should return correct array representation', function (): void {
    $binaryOperation = new BinaryOperation(
        new Literal(1),
        TokenType::T_PLUS,
        new Literal(2)
    );

    expect($binaryOperation->toArray())->toBe([
        'type' => BinaryOperation::class,
        'left' => [
            'type' => Literal::class,
            'value' => 1,
        ],
        'operator' => TokenType::T_PLUS->value,
        'right' => [
            'type' => Literal::class,
            'value' => 2,
        ],
    ]);
});

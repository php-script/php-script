<?php

declare(strict_types=1);

use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('should convert token to array', function (): void {
    $token = new Token(
        type: TokenType::T_IDENTIFIER,
        value: 'foo',
        line: 10,
        column: 5,
        offset: 100
    );

    $expectedArray = [
        'type' => 'T_IDENTIFIER',
        'value' => 'foo',
    ];

    expect($token->toArray())->toBe($expectedArray);
});

<?php

declare(strict_types=1);

use PhpScript\Core\Lexer;
use PhpScript\Core\Token;
use PhpScript\Exceptions\LexerException;

it('lexer resolves script code', function (): void {
    $phpScriptCode = 'u = 10
u2 = u * 2;
echo u2
';
    $lexer = new Lexer;
    $tokens = $lexer->tokenize($phpScriptCode);

    expect(array_map(fn (Token $token) => $token->toArray(), $tokens))->toBe([
        [
            'type' => 'T_IDENTIFIER',
            'value' => 'u',
        ],
        [
            'type' => 'T_EQUALS',
            'value' => '=',
        ],
        [
            'type' => 'T_NUMBER',
            'value' => '10',
        ],
        [
            'type' => 'T_SEMICOLON',
            'value' => ';',
        ],
        [
            'type' => 'T_IDENTIFIER',
            'value' => 'u2',
        ],
        [
            'type' => 'T_EQUALS',
            'value' => '=',
        ],
        [
            'type' => 'T_IDENTIFIER',
            'value' => 'u',
        ],
        [
            'type' => 'T_MULTIPLY',
            'value' => '*',
        ],
        [
            'type' => 'T_NUMBER',
            'value' => '2',
        ],
        [
            'type' => 'T_SEMICOLON',
            'value' => ';',
        ],
        [
            'type' => 'T_ECHO',
            'value' => 'echo',
        ],
        [
            'type' => 'T_IDENTIFIER',
            'value' => 'u2',
        ],
        [
            'type' => 'T_SEMICOLON',
            'value' => ';',
        ],
    ]);
});

it('throws exception when token is not recognized', function (): void {
    $phpScriptCode = 'foo!!!()';

    $lexer = new Lexer;
    $this->expectException(LexerException::class);
    $lexer->tokenize($phpScriptCode);
});

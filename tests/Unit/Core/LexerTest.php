<?php

declare(strict_types=1);

use PhpScript\Core\Lexer;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;
use PhpScript\Exceptions\LexerException;

it('lexer resolves script code', function (): void {
    $phpScriptCode = 'u = 10' . "\n" . 'u2 = u * 2;
echo u2';
    $lexer = new Lexer;
    $tokens = $lexer->tokenize($phpScriptCode);

    $expectedTokens = [
        new Token(TokenType::T_IDENTIFIER, 'u', 1, 1, 0),
        new Token(TokenType::T_WHITESPACE, ' ', 1, 2, 1),
        new Token(TokenType::T_EQUALS, '=', 1, 3, 2),
        new Token(TokenType::T_WHITESPACE, ' ', 1, 4, 3),
        new Token(TokenType::T_NUMBER, '10', 1, 5, 4),
        new Token(TokenType::T_WHITESPACE, "\n", 1, 7, 6),
        new Token(TokenType::T_IDENTIFIER, 'u2', 2, 1, 7),
        new Token(TokenType::T_WHITESPACE, ' ', 2, 3, 9),
        new Token(TokenType::T_EQUALS, '=', 2, 4, 10),
        new Token(TokenType::T_WHITESPACE, ' ', 2, 5, 11),
        new Token(TokenType::T_IDENTIFIER, 'u', 2, 6, 12),
        new Token(TokenType::T_WHITESPACE, ' ', 2, 7, 13),
        new Token(TokenType::T_MULTIPLY, '*', 2, 8, 14),
        new Token(TokenType::T_WHITESPACE, ' ', 2, 9, 15),
        new Token(TokenType::T_NUMBER, '2', 2, 10, 16),
        new Token(TokenType::T_SEMICOLON, ';', 2, 11, 17),
        new Token(TokenType::T_WHITESPACE, "\n", 2, 12, 18),
        new Token(TokenType::T_ECHO, 'echo', 3, 1, 19),
        new Token(TokenType::T_WHITESPACE, ' ', 3, 5, 23),
        new Token(TokenType::T_IDENTIFIER, 'u2', 3, 6, 24),
    ];

    expect($tokens)->toEqual($expectedTokens);
});

it('throws exception when token is not recognized', function (): void {
    $phpScriptCode = "a = 1;\nb = @;\nc = 3;";

    $lexer = new Lexer;
    try {
        $lexer->tokenize($phpScriptCode);
    } catch (LexerException $e) {
        expect($e->getMessage())->toBe('Unknown character or syntax error `@;⏎c = 3;` at line 2, column 5.');
    }
});

it('handles non-breaking spaces', function (): void {
    $phpScriptCode = "a =\u{00A0}1;";
    $lexer = new Lexer;
    $tokens = $lexer->tokenize($phpScriptCode);

    $expectedTokens = [
        new Token(TokenType::T_IDENTIFIER, 'a', 1, 1, 0),
        new Token(TokenType::T_WHITESPACE, ' ', 1, 2, 1),
        new Token(TokenType::T_EQUALS, '=', 1, 3, 2),
        new Token(TokenType::T_WHITESPACE, "\u{00A0}", 1, 4, 3),
        new Token(TokenType::T_NUMBER, '1', 1, 6, 5),
        new Token(TokenType::T_SEMICOLON, ';', 1, 7, 6),
    ];

    expect($tokens)->toEqual($expectedTokens);
});

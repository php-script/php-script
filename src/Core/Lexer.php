<?php

declare(strict_types=1);

namespace PhpScript\Core;

use PhpScript\Exceptions\LexerException;

final readonly class Lexer
{
    /**
     * An associative array defining token patterns for a lexical analyzer or tokenizer.
     * Each key corresponds to a specific token type, and its value is the regex pattern
     * to recognize that type in the input string.
     *
     * Token types include:
     * - Whitespace and comments
     * - Numbers and strings
     * - Programming language keywords
     * - Identifiers (e.g., variables and functions)
     * - Common operators and symbols (e.g., `=`, `+`, `{`, `}`)
     *
     * @var array<string, string>
     */
    private array $tokenPatterns;

    public function __construct()
    {
        // initialize token patterns in defined order
        $this->tokenPatterns = [
            TokenType::T_WHITESPACE->value => '\s+',
            TokenType::T_COMMENT->value => '\/\/[^\n]*',
            TokenType::T_NUMBER->value => '\b\d+(\.\d+)?\b',
            TokenType::T_STRING->value => '\"(.*?)(?<!\\\\)\"|\'(.*?)(?<!\\\\)\'',

            // Keywords (have to be before T_IDENTIFIER)
            TokenType::T_IF->value => '\bif\b',
            TokenType::T_ELSE->value => '\belse\b',
            TokenType::T_FOREACH->value => '\bforeach\b',
            TokenType::T_AS->value => '\bas\b',
            TokenType::T_ECHO->value => '\becho\b',
            TokenType::T_RETURN->value => '\breturn\b',
            TokenType::T_TRUE->value => '\btrue\b',
            TokenType::T_FALSE->value => '\bfalse\b',
            TokenType::T_NULL->value => '\bnull\b',

            // Identifiers
            TokenType::T_IDENTIFIER->value => '\b[a-zA-Z_]\w*\b',

            // Operators
            TokenType::T_COMPARE_EQUALS->value => '==(=)?',
            TokenType::T_COMPARE_UNEQUALS->value => '!=(=)?',
            TokenType::T_EQUALS->value => '=',
            TokenType::T_DOT->value => '\.',
            TokenType::T_LEFT_PARENTHESIS->value => '\(',
            TokenType::T_RIGHT_PARENTHESIS->value => '\)',
            TokenType::T_LEFT_BRACE->value => '\{',
            TokenType::T_RIGHT_BRACE->value => '\}',
            TokenType::T_SEMICOLON->value => ';',
            TokenType::T_COMMA->value => ',',
            TokenType::T_PLUS->value => '\+',
            TokenType::T_MINUS->value => '\-',
            TokenType::T_MULTIPLY->value => '\*',
            TokenType::T_DIVIDE->value => '\/',
            TokenType::T_GREATER_THAN->value => '>',
            TokenType::T_LESS_THAN->value => '<',
            TokenType::T_CONCAT->value => '~',
        ];
    }

    /**
     * Tokenizes the given script string into an array of tokens based on predefined patterns.
     *
     * @return list<\PhpScript\Core\Token>
     *
     * @throws LexerException If an unknown character or syntax error is encountered during tokenization.
     */
    public function tokenize(string $script): array
    {
        $tokens = [];
        $offset = 0;
        $line = 1;
        $lineOffset = 0;
        $length = strlen($script);

        while ($offset < $length) {
            $remaining = substr($script, $offset);
            $matchFound = false;

            foreach ($this->tokenPatterns as $type => $pattern) {
                if (preg_match('/^('.$pattern.')/', $remaining, $matches)) {
                    $tokenTypeEnum = TokenType::from($type);
                    $value = $matches[1];

                    if ($tokenTypeEnum === TokenType::T_STRING) {
                        $content = isset($matches[3]) && $matches[3] !== '' ? $matches[3] : ($matches[2] ?? '');
                        $value = stripcslashes($content);
                    }

                    $column = $offset - $lineOffset + 1;
                    $tokens[] = new Token($tokenTypeEnum, $value, $line, $column, $offset);

                    if (str_contains($matches[0], "\n")) {
                        $line += substr_count($matches[0], "\n");
                        $lineOffset = $offset + (int) strrpos($matches[0], "\n") + 1;
                    }

                    $offset += strlen($matches[0]);
                    $matchFound = true;
                    break;
                }
            }

            if (! $matchFound) {
                $column = $offset - $lineOffset + 1;
                throw new LexerException("Unknown character or syntax error at line $line, column $column.");
            }
        }

        return $tokens;
    }
}

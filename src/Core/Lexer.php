<?php

declare(strict_types=1);

namespace PhpScript\Core;

use PhpScript\Contracts\LexerInterface;
use PhpScript\Exceptions\LexerException;

final readonly class Lexer implements LexerInterface
{
    /**
     * Token patterns in the order they are tried. Keywords come before
     * identifiers, longer operators before shorter ones.
     *
     * @var array<string, string>
     */
    private const array TOKEN_PATTERNS = [
        'T_WHITESPACE' => '[\s\x{00A0}]+',
        'T_COMMENT' => '\/\/[^\n]*',
        'T_NUMBER' => '[0-9]+(?:\.[0-9]+)?(?![A-Za-z0-9_])',
        'T_STRING' => '"(?<dq>(?:[^"\\\\]|\\\\.)*)"|\'(?<sq>(?:[^\'\\\\]|\\\\.)*)\'',

        // Keywords (have to be before T_IDENTIFIER)
        'T_IF' => 'if' . self::WORD_END,
        'T_ELSE' => 'else' . self::WORD_END,
        'T_FOR' => 'for' . self::WORD_END,
        'T_FOREACH' => 'foreach' . self::WORD_END,
        'T_AS' => 'as' . self::WORD_END,
        'T_ECHO' => 'echo' . self::WORD_END,
        'T_RETURN' => 'return' . self::WORD_END,
        'T_TRUE' => 'true' . self::WORD_END,
        'T_FALSE' => 'false' . self::WORD_END,
        'T_NULL' => 'null' . self::WORD_END,
        'T_BREAK' => 'break' . self::WORD_END,
        'T_CONTINUE' => 'continue' . self::WORD_END,
        'T_LINEBREAK' => 'LINEBREAK' . self::WORD_END,

        // Identifiers
        'T_IDENTIFIER' => '[A-Za-z_][A-Za-z0-9_]*',

        // Operators
        'T_INCREMENT' => '\+\+',
        'T_DECREMENT' => '--',
        'T_GREATER_EQUAL' => '>=',
        'T_LESS_EQUAL' => '<=',
        'T_AND' => '&&',
        'T_OR' => '\|\|',
        'T_COMPARE_EQUALS' => '===?',
        'T_COMPARE_UNEQUALS' => '!==?',
        'T_EQUALS' => '=',
        'T_BANG' => '!',
        'T_DOT' => '\.',
        'T_LEFT_PARENTHESIS' => '\(',
        'T_RIGHT_PARENTHESIS' => '\)',
        'T_LEFT_BRACE' => '\{',
        'T_RIGHT_BRACE' => '\}',
        'T_LEFT_BRACKET' => '\[',
        'T_RIGHT_BRACKET' => '\]',
        'T_SEMICOLON' => ';',
        'T_COMMA' => ',',
        'T_COLON' => ':',
        'T_MODULO' => '%',
        'T_PLUS' => '\+',
        'T_MINUS' => '\-',
        'T_MULTIPLY' => '\*',
        'T_DIVIDE' => '\/',
        'T_GREATER_THAN' => '>',
        'T_LESS_THAN' => '<',
    ];

    private const string WORD_END = '(?!\w)';

    /**
     * All patterns as one regex. `\G` anchors it at the current offset, `(*MARK)`
     * names the token type that matched.
     */
    private string $pattern;

    public function __construct()
    {
        $alternatives = [];
        foreach (self::TOKEN_PATTERNS as $type => $pattern) {
            $alternatives[] = '(?:' . $pattern . ')(*MARK:' . $type . ')';
        }

        $this->pattern = '/\G(?:' . implode('|', $alternatives) . ')/us';
    }

    /**
     * Tokenizes the given script string into an array of tokens based on predefined patterns.
     *
     * @return list<Token>
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
            $column = $offset - $lineOffset + 1;

            // Invalid UTF-8 makes preg_match() fail, which is reported like an unknown character.
            if (preg_match($this->pattern, $script, $matches, PREG_UNMATCHED_AS_NULL, $offset) !== 1 || ! is_string($matches['MARK'] ?? null)) {
                throw LexerException::unknownCharOrSyntaxError(substr($script, $offset), $line, $column, $offset);
            }

            $type = TokenType::from($matches['MARK']);
            $text = (string) $matches[0];
            $value = $type === TokenType::T_STRING ? stripcslashes((string) ($matches['dq'] ?? $matches['sq'])) : $text;

            $tokens[] = new Token($type, $value, $line, $column, $offset);

            $newlines = substr_count($text, "\n");
            if ($newlines > 0) {
                $line += $newlines;
                $lineOffset = $offset + (int) strrpos($text, "\n") + 1;
            }

            $offset += strlen($text);
        }

        return $tokens;
    }
}

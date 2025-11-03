<?php

declare(strict_types=1);

namespace PhpScript\Core;

use PhpScript\Exceptions\LexerException;

class Lexer
{
    // Token-Typen
    public const string T_IDENTIFIER = 'T_IDENTIFIER'; // Variablennamen, Funktionsnamen (user, logins, count)

    public const string T_NUMBER = 'T_NUMBER';       // Zahlen (1, 10, 3.14)

    public const string T_STRING = 'T_STRING';       // Strings ("Hallo")

    public const string T_DOT = 'T_DOT';         // .

    public const string T_LPAREN = 'T_LPAREN';     // (

    public const string T_RPAREN = 'T_RPAREN';     // )

    public const string T_LBRACE = 'T_LBRACE';     // {

    public const string T_RBRACE = 'T_RBRACE';     // }

    public const string T_SEMICOLON = 'T_SEMICOLON';   // ;

    public const string T_EQUALS = 'T_EQUALS';     // =

    public const string T_PLUS = 'T_PLUS';       // +

    public const string T_MINUS = 'T_MINUS';      // -

    public const string T_MULTIPLY = 'T_MULTIPLY';   // *

    public const string T_DIVIDE = 'T_DIVIDE';     // /

    public const string T_GT = 'T_GT';         // >

    public const string T_LT = 'T_LT';         // <

    public const string T_EQUALS_EQUALS = 'T_EQUALS_EQUALS'; // ==

    public const string T_WHITESPACE = 'T_WHITESPACE';  // (wird ignoriert)

    public const string T_COMMENT = 'T_COMMENT';     // // Kommentar (wird ignoriert)

    public const string T_UNKNOWN = 'T_UNKNOWN';     // Unbekanntes Zeichen

    // Keywords der Sprache
    public const string T_IF = 'T_IF';

    public const string T_ELSE = 'T_ELSE';

    public const string T_FOREACH = 'T_FOREACH';

    public const string T_AS = 'T_AS';

    public const string T_ECHO = 'T_ECHO';

    public const string T_RETURN = 'T_RETURN';

    public const string T_TRUE = 'T_TRUE';

    public const string T_FALSE = 'T_FALSE';

    public const string T_NULL = 'T_NULL';

    /**
     * Regex-Muster für jeden Token-Typ.
     * Die Reihenfolge ist wichtig!
     */
    private array $tokenPatterns = [
        self::T_WHITESPACE => '\s+',
        self::T_COMMENT => '\/\/[^\n]*',
        self::T_NUMBER => '\b\d+(\.\d+)?\b',
        self::T_STRING => '"(.*?)(?<!\\\\)"|\'(.*?)(?<!\\\\)\'',

        // Keywords (müssen VOR T_IDENTIFIER kommen)
        self::T_IF => '\bif\b',
        self::T_ELSE => '\belse\b',
        self::T_FOREACH => '\bforeach\b',
        self::T_AS => '\bas\b',
        self::T_ECHO => '\becho\b',
        self::T_RETURN => '\breturn\b',
        self::T_TRUE => '\btrue\b',
        self::T_FALSE => '\bfalse\b',
        self::T_NULL => '\bnull\b',

        // Bezeichner (Variablen, Funktionen)
        self::T_IDENTIFIER => '\b[a-zA-Z_]\w*\b',

        // Operatoren
        self::T_EQUALS_EQUALS => '==',
        self::T_EQUALS => '=',
        self::T_DOT => '\.',
        self::T_LPAREN => '\(',
        self::T_RPAREN => '\)',
        self::T_LBRACE => '\{',
        self::T_RBRACE => '\}',
        self::T_SEMICOLON => ';',
        self::T_PLUS => '\+',
        self::T_MINUS => '\-',
        self::T_MULTIPLY => '\*',
        self::T_DIVIDE => '\/',
        self::T_GT => '>',
        self::T_LT => '<',
    ];

    /**
     * Zerlegt einen php-script-Code-String in ein Array von Tokens.
     *
     * @return list<array{type: string, value: mixed}>
     *
     * @throws \PhpScript\Exceptions\LexerException
     */
    public function tokenize(string $script): array
    {
        // 1. Zeilenumbrüche als Semikolon behandeln (optional am Ende)
        // Ersetze alle Zeilenumbrüche durch Semikolons
        $script = str_replace(["\r\n", "\n"], ";\n", $script);
        // Entferne doppelte Semikolons, die dadurch entstehen könnten
        $script = preg_replace('/;+/', ';', $script);

        $tokens = [];
        $offset = 0;
        $length = strlen((string) $script);

        while ($offset < $length) {
            $remaining = substr((string) $script, $offset);
            $matchFound = false;

            foreach ($this->tokenPatterns as $type => $pattern) {
                // Wir suchen nur am Anfang des verbleibenden Strings (^)
                if (preg_match('/^('.$pattern.')/', $remaining, $matches)) {
                    $value = $matches[1];

                    // Whitespace und Kommentare ignorieren
                    if ($type !== self::T_WHITESPACE && $type !== self::T_COMMENT) {
                        $tokens[] = [
                            'type' => $type,
                            'value' => $value,
                        ];
                    }

                    $offset += strlen($value);
                    $matchFound = true;
                    break; // Nächstes Token im Haupt-Loop suchen
                }
            }

            if (! $matchFound) {
                throw LexerException::unknownCharOrSyntaxError($remaining);
            }
        }

        return $tokens;
    }
}

<?php

declare(strict_types=1);

namespace PhpScript\Core;

enum TokenType: string
{
    // Token-Typen
    case T_IDENTIFIER = 'T_IDENTIFIER'; // Variablennamen, Funktionsnamen (user, logins, count)
    case T_NUMBER = 'T_NUMBER';       // Zahlen (1, 10, 3.14)
    case T_STRING = 'T_STRING';       // Strings ("Hallo")
    case T_DOT = 'T_DOT';         // . (Objektzugriff)
    case T_CONCAT = 'T_CONCAT';     // ~ (String-Verkettung)
    case T_LPAREN = 'T_LPAREN';     // (
    case T_RPAREN = 'T_RPAREN';     // )
    case T_LBRACE = 'T_LBRACE';     // {
    case T_RBRACE = 'T_RBRACE';     // }
    case T_SEMICOLON = 'T_SEMICOLON';   // ;
    case T_EQUALS = 'T_EQUALS';     // =
    case T_PLUS = 'T_PLUS';       // +
    case T_MINUS = 'T_MINUS';      // -
    case T_MULTIPLY = 'T_MULTIPLY';   // *
    case T_DIVIDE = 'T_DIVIDE';     // /
    case T_GT = 'T_GT';         // >
    case T_LT = 'T_LT';         // <
    case T_EQUALS_EQUALS = 'T_EQUALS_EQUALS'; // ==
    case T_WHITESPACE = 'T_WHITESPACE';  // (wird ignoriert)
    case T_COMMENT = 'T_COMMENT';     // // Kommentar (wird ignoriert)
    case T_UNKNOWN = 'T_UNKNOWN';     // Unbekanntes Zeichen

    // Keywords der Sprache
    case T_IF = 'T_IF';
    case T_ELSE = 'T_ELSE';
    case T_FOREACH = 'T_FOREACH';
    case T_AS = 'T_AS';
    case T_ECHO = 'T_ECHO';
    case T_RETURN = 'T_RETURN';
    case T_TRUE = 'T_TRUE';
    case T_FALSE = 'T_FALSE';
    case T_NULL = 'T_NULL';
}

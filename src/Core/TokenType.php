<?php

declare(strict_types=1);

namespace PhpScript\Core;

enum TokenType: string
{
    // Token-Typen
    case T_IDENTIFIER = 'T_IDENTIFIER'; // name of variables, functions (user, logins, count)
    case T_NUMBER = 'T_NUMBER';       // Numbers (1, 10, 3.14)
    case T_STRING = 'T_STRING';       // Strings ("Hello")
    case T_DOT = 'T_DOT';         // . (object access)
    case T_CONCAT = 'T_CONCAT';     // ~ (String-concatenation)
    case T_LEFT_PARENTHESIS = 'T_LEFT_PARENTHESIS';     // (
    case T_RIGHT_PARENTHESIS = 'T_RIGHT_PARENTHESIS';     // )
    case T_LEFT_BRACE = 'T_LEFT_BRACE';     // {
    case T_RIGHT_BRACE = 'T_RIGHT_BRACE';     // }
    case T_SEMICOLON = 'T_SEMICOLON';   // ;
    case T_COMMA = 'T_COMMA';       // ,
    case T_EQUALS = 'T_EQUALS';     // =
    case T_PLUS = 'T_PLUS';       // +
    case T_MINUS = 'T_MINUS';      // -
    case T_MULTIPLY = 'T_MULTIPLY';   // *
    case T_DIVIDE = 'T_DIVIDE';     // /
    case T_GREATER_THAN = 'T_GREATER_THAN';         // >
    case T_LESS_THAN = 'T_LESS_THAN';         // <
    case T_COMPARE_EQUALS = 'T_COMPARE_EQUALS'; // ==, ===
    case T_COMPARE_UNEQUALS = 'T_COMPARE_UNEQUALS'; // !=, !==
    case T_WHITESPACE = 'T_WHITESPACE';  // (whitespace)
    case T_COMMENT = 'T_COMMENT';     // // line comment
    case T_UNKNOWN = 'T_UNKNOWN';     // unknown char

    // Keywords
    case T_IF = 'T_IF';
    case T_ELSE = 'T_ELSE';
    case T_FOREACH = 'T_FOREACH';
    case T_AS = 'T_AS';
    case T_ECHO = 'T_ECHO';
    case T_RETURN = 'T_RETURN';
    case T_TRUE = 'T_TRUE';
    case T_FALSE = 'T_FALSE';
    case T_NULL = 'T_NULL';
    case T_LINEBREAK = 'T_LINEBREAK';
}

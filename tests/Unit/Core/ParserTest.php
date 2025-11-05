<?php

use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\Identifier;
use PhpScript\Ast\Literal;
use PhpScript\Ast\MemberAccess;
use PhpScript\Ast\NoOp;
use PhpScript\Ast\Program;
use PhpScript\Ast\Variable;
use PhpScript\Core\Parser;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;
use PhpScript\Exceptions\ParseException;

beforeEach(function (): void {
    $this->parser = new Parser;
});

it('should parse an empty program', function (): void {
    $tokens = [];
    $ast = $this->parser->parse($tokens);

    expect($ast)->toBeInstanceOf(Program::class);
    expect($ast->statements)->toBeEmpty();
});

it('should parse a no-op statement', function (): void {
    $tokens = [
        new Token(TokenType::T_SEMICOLON, ';', 1, 1, 0),
    ];
    $ast = $this->parser->parse($tokens);

    expect($ast->statements[0])->toBeInstanceOf(NoOp::class);
});

it('should parse an echo statement', function (): void {
    $tokens = [
        new Token(TokenType::T_ECHO, 'echo', 1, 1, 0),
        new Token(TokenType::T_STRING, 'hello', 1, 6, 5),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var EchoStatement $echoStatement */
    $echoStatement = $ast->statements[0];
    expect($echoStatement)->toBeInstanceOf(EchoStatement::class);
    expect($echoStatement->expression)->toBeInstanceOf(Literal::class);
    expect($echoStatement->expression->value)->toBe('hello');
});

it('should parse an assignment', function (): void {
    $tokens = [
        new Token(TokenType::T_IDENTIFIER, 'foo', 1, 1, 0),
        new Token(TokenType::T_EQUALS, '=', 1, 5, 4),
        new Token(TokenType::T_STRING, 'bar', 1, 7, 6),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var Assignment $assignment */
    $assignment = $ast->statements[0];
    expect($assignment)->toBeInstanceOf(Assignment::class);
    expect($assignment->variable)->toBeInstanceOf(Variable::class);
    expect($assignment->variable->name)->toBe('foo');
    expect($assignment->expression)->toBeInstanceOf(Literal::class);
    expect($assignment->expression->value)->toBe('bar');
});

it('should parse a binary operation', function (): void {
    $tokens = [
        new Token(TokenType::T_NUMBER, '1', 1, 1, 0),
        new Token(TokenType::T_PLUS, '+', 1, 3, 2),
        new Token(TokenType::T_NUMBER, '2', 1, 5, 4),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var BinaryOperation $binaryOperation */
    $binaryOperation = $ast->statements[0];
    expect($binaryOperation)->toBeInstanceOf(BinaryOperation::class);
    expect($binaryOperation->left)->toBeInstanceOf(Literal::class);
    expect($binaryOperation->left->value)->toBe('1');
    expect($binaryOperation->operator)->toBe(TokenType::T_PLUS);
    expect($binaryOperation->right)->toBeInstanceOf(Literal::class);
    expect($binaryOperation->right->value)->toBe('2');
});

it('should parse a member access', function (): void {
    $tokens = [
        new Token(TokenType::T_IDENTIFIER, 'foo', 1, 1, 0),
        new Token(TokenType::T_DOT, '.', 1, 4, 3),
        new Token(TokenType::T_IDENTIFIER, 'bar', 1, 5, 4),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var MemberAccess $memberAccess */
    $memberAccess = $ast->statements[0];
    expect($memberAccess)->toBeInstanceOf(MemberAccess::class);
    expect($memberAccess->object)->toBeInstanceOf(Variable::class);
    expect($memberAccess->object->name)->toBe('foo');
    expect($memberAccess->property)->toBeInstanceOf(Identifier::class);
    expect($memberAccess->property->name)->toBe('bar');
});

it('should parse a literal', function (): void {
    $tokens = [
        new Token(TokenType::T_STRING, 'hello', 1, 1, 0),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var Literal $literal */
    $literal = $ast->statements[0];
    expect($literal)->toBeInstanceOf(Literal::class);
    expect($literal->value)->toBe('hello');
});

it('should parse a variable', function (): void {
    $tokens = [
        new Token(TokenType::T_IDENTIFIER, 'foo', 1, 1, 0),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var Variable $variable */
    $variable = $ast->statements[0];
    expect($variable)->toBeInstanceOf(Variable::class);
    expect($variable->name)->toBe('foo');
});

it('should throw an exception for invalid assignment target', function (): void {
    $tokens = [
        new Token(TokenType::T_NUMBER, '1', 1, 1, 0),
        new Token(TokenType::T_EQUALS, '=', 1, 3, 2),
        new Token(TokenType::T_NUMBER, '2', 1, 5, 4),
    ];
    $this->parser->parse($tokens);
})->throws(ParseException::class, 'Invalid assignment target.');

it('should throw an exception for unexpected token', function (): void {
    $tokens = [
        new Token(TokenType::T_RPAREN, ')', 1, 1, 0),
    ];
    $this->parser->parse($tokens);
})->throws(ParseException::class, 'Unexpected token: T_RPAREN');

it('should parse a parenthesized expression', function (): void {
    $tokens = [
        new Token(TokenType::T_LPAREN, '(', 1, 1, 0),
        new Token(TokenType::T_NUMBER, '1', 1, 2, 1),
        new Token(TokenType::T_PLUS, '+', 1, 4, 3),
        new Token(TokenType::T_NUMBER, '2', 1, 6, 5),
        new Token(TokenType::T_RPAREN, ')', 1, 7, 6),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var Program $ast */
    expect($ast)->toBeInstanceOf(Program::class);
    expect($ast->statements)->toHaveCount(1);

    /** @var BinaryOperation $binaryOperation */
    $binaryOperation = $ast->statements[0];
    expect($binaryOperation)->toBeInstanceOf(BinaryOperation::class);
    expect($binaryOperation->left)->toBeInstanceOf(Literal::class);
    expect($binaryOperation->left->value)->toBe('1');
    expect($binaryOperation->operator)->toBe(TokenType::T_PLUS);
    expect($binaryOperation->right)->toBeInstanceOf(Literal::class);
    expect($binaryOperation->right->value)->toBe('2');
});

it('should throw exception at previous token when consume fails at end of file', function (): void {
    $tokens = [
        new Token(TokenType::T_LPAREN, '(', 1, 1, 0),
        new Token(TokenType::T_NUMBER, '1', 1, 2, 1),
    ];

    try {
        $this->parser->parse($tokens);
    } catch (ParseException $e) {
        expect($e->getMessage())->toBe("Expect ')' after expression.");
        // Crucially, the error is associated with the LAST valid token, not a non-existent one.
        expect($e->getToken()->type)->toBe(TokenType::T_NUMBER);
        expect($e->getToken()->value)->toBe('1');
    }
});

it('should throw an exception for missing identifier after dot', function (): void {
    $tokens = [
        new Token(TokenType::T_IDENTIFIER, 'foo', 1, 1, 0),
        new Token(TokenType::T_DOT, '.', 1, 4, 3),
    ];

    try {
        $this->parser->parse($tokens);
    } catch (ParseException $e) {
        expect($e->getMessage())->toBe('Expected identifier after .');
        expect($e->getToken()->type)->toBe(TokenType::T_DOT);
    }
});

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
        new Token(TokenType::T_SEMICOLON, ';'),
    ];
    $ast = $this->parser->parse($tokens);

    expect($ast->statements[0])->toBeInstanceOf(NoOp::class);
});

it('should parse an echo statement', function (): void {
    $tokens = [
        new Token(TokenType::T_ECHO, 'echo'),
        new Token(TokenType::T_STRING, 'hello'),
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
        new Token(TokenType::T_IDENTIFIER, 'foo'),
        new Token(TokenType::T_EQUALS, '='),
        new Token(TokenType::T_STRING, 'bar'),
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
        new Token(TokenType::T_NUMBER, '1'),
        new Token(TokenType::T_PLUS, '+'),
        new Token(TokenType::T_NUMBER, '2'),
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
        new Token(TokenType::T_IDENTIFIER, 'foo'),
        new Token(TokenType::T_DOT, '.'),
        new Token(TokenType::T_IDENTIFIER, 'bar'),
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
        new Token(TokenType::T_STRING, 'hello'),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var Literal $literal */
    $literal = $ast->statements[0];
    expect($literal)->toBeInstanceOf(Literal::class);
    expect($literal->value)->toBe('hello');
});

it('should parse a variable', function (): void {
    $tokens = [
        new Token(TokenType::T_IDENTIFIER, 'foo'),
    ];
    $ast = $this->parser->parse($tokens);

    /** @var Variable $variable */
    $variable = $ast->statements[0];
    expect($variable)->toBeInstanceOf(Variable::class);
    expect($variable->name)->toBe('foo');
});

it('should throw an exception for invalid assignment target', function (): void {
    $tokens = [
        new Token(TokenType::T_NUMBER, '1'),
        new Token(TokenType::T_EQUALS, '='),
        new Token(TokenType::T_NUMBER, '2'),
    ];
    $this->parser->parse($tokens);
})->throws(RuntimeException::class, 'Invalid assignment target.');

it('should throw an exception for unexpected token', function (): void {
    $tokens = [
        new Token(TokenType::T_RPAREN, ')'),
    ];
    $this->parser->parse($tokens);
})->throws(RuntimeException::class, 'Unexpected token: T_RPAREN');

it('should parse a parenthesized expression', function (): void {
    $tokens = [
        new Token(TokenType::T_LPAREN, '('),
        new Token(TokenType::T_NUMBER, '1'),
        new Token(TokenType::T_PLUS, '+'),
        new Token(TokenType::T_NUMBER, '2'),
        new Token(TokenType::T_RPAREN, ')'),
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

it('should throw an exception for missing closing parenthesis', function (): void {
    $tokens = [
        new Token(TokenType::T_LPAREN, '('),
        new Token(TokenType::T_NUMBER, '1'),
    ];
    $this->parser->parse($tokens);
})->throws(RuntimeException::class, "Expect ')' after expression.");

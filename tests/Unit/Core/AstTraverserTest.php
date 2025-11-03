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
use PhpScript\Contracts\Node;
use PhpScript\Core\AstTraverser;
use PhpScript\Core\TokenType;

beforeEach(function (): void {
    $this->traverser = new AstTraverser;
});

it('should traverse a program', function (): void {
    $program = new Program([
        new EchoStatement(new Literal('hello')),
        new EchoStatement(new Literal('world')),
    ]);
    $result = $this->traverser->traverse($program);
    expect($result)->toBe("echo 'hello';\n"."echo 'world';\n");
});

it('should traverse an empty program', function (): void {
    $program = new Program([]);
    $result = $this->traverser->traverse($program);
    expect($result)->toBe('');
});

it('should traverse an echo statement', function (): void {
    $echo = new EchoStatement(new Literal('hello world'));
    $result = $this->traverser->traverse($echo);
    expect($result)->toBe("echo 'hello world'");
});

it('should traverse an assignment', function (): void {
    $assignment = new Assignment(
        new Variable('foo'),
        new Literal('bar')
    );
    $result = $this->traverser->traverse($assignment);
    expect($result)->toBe('$foo = \'bar\'');
});

it('should traverse a binary operation', function (TokenType $operator, string $expectedOperator): void {
    $binaryOp = new BinaryOperation(
        new Literal(1),
        $operator,
        new Literal(2)
    );
    $result = $this->traverser->traverse($binaryOp);
    expect($result)->toBe('1 '.$expectedOperator.' 2');
})->with([
    [TokenType::T_PLUS, '+'],
    [TokenType::T_MINUS, '-'],
    [TokenType::T_MULTIPLY, '*'],
    [TokenType::T_DIVIDE, '/'],
    [TokenType::T_CONCAT, '.'],
    [TokenType::T_EQUALS_EQUALS, '=='],
    [TokenType::T_GT, '>'],
    [TokenType::T_LT, '<'],
]);

it('should throw an exception for unknown binary operator', function (): void {
    $binaryOp = new BinaryOperation(
        new Literal(1),
        TokenType::T_DOT,
        new Literal(2)
    );
    $this->traverser->traverse($binaryOp);
})->throws(RuntimeException::class, 'Unknown operator: T_DOT');

it('should traverse a member access', function (): void {
    $memberAccess = new MemberAccess(
        new Variable('foo'),
        new Identifier('bar')
    );
    $result = $this->traverser->traverse($memberAccess);
    expect($result)->toBe('$foo->bar');
});

it('should traverse a variable', function (): void {
    $variable = new Variable('foo');
    $result = $this->traverser->traverse($variable);
    expect($result)->toBe('$foo');
});

it('should traverse an identifier', function (): void {
    $identifier = new Identifier('foo');
    $result = $this->traverser->traverse($identifier);
    expect($result)->toBe('foo');
});

it('should traverse a literal', function (mixed $value, string $expected): void {
    $literal = new Literal($value);
    $result = $this->traverser->traverse($literal);
    expect($result)->toBe($expected);
})->with([
    ['hello', "'hello'"],
    ["'hello'", "'\\'hello\\''"],
    [123, '123'],
    [3.14, '3.14'],
    [true, 'true'],
    [false, 'false'],
    [null, 'null'],
]);

it('should traverse a no-op', function (): void {
    $noOp = new NoOp;
    $result = $this->traverser->traverse($noOp);
    expect($result)->toBe('');
});

it('should throw an exception for unknown node type', function (): void {
    $unknownNode = new class implements Node
    {
        public function toArray(): array
        {
            return [];
        }
    };
    $this->traverser->traverse($unknownNode);
})->throws(RuntimeException::class);

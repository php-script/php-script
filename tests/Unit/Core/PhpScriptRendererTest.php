<?php

use PhpScript\Ast\ArrayAccess;
use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\BreakStatement;
use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\ForeachStatement;
use PhpScript\Ast\ForStatement;
use PhpScript\Ast\FunctionCall;
use PhpScript\Ast\Identifier;
use PhpScript\Ast\IfStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\MemberAccess;
use PhpScript\Ast\NoOp;
use PhpScript\Ast\PostfixOperation;
use PhpScript\Ast\Program;
use PhpScript\Ast\UnaryOperation;
use PhpScript\Ast\Variable;
use PhpScript\Core\PhpScriptRenderer;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;
use PhpScript\Exceptions\AstTraverserException;

it('can render a simple echo statement', function (): void {
    // AST for: echo 'hello';
    $program = new Program([
        new EchoStatement(
            new Literal('hello', new Token(TokenType::T_STRING, "'hello'", 1, 6, 5)),
            new Token(TokenType::T_ECHO, 'echo', 1, 1, 0)
        ),
    ]);

    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);

    expect(trim($output))->toBe("echo 'hello';");
});

it('can render a more complex script', function (): void {
    // AST for:
    // u = users_list;
    // for (i = 0; i < 2; i++) {
    //     echo u[i] + LINEBREAK;
    // }
    $program = new Program([
        new Assignment(
            new Variable('u', new Token(TokenType::T_IDENTIFIER, 'u', 1, 1, 0)),
            new Variable('users_list', new Token(TokenType::T_IDENTIFIER, 'users_list', 1, 5, 4))
        ),
        new ForStatement(
            new Assignment(
                new Variable('i', new Token(TokenType::T_IDENTIFIER, 'i', 2, 6, 25)),
                new Literal(0, new Token(TokenType::T_NUMBER, '0', 2, 10, 29))
            ),
            new BinaryOperation(
                new Variable('i', new Token(TokenType::T_IDENTIFIER, 'i', 2, 13, 32)),
                TokenType::T_LESS_THAN,
                new Literal(2, new Token(TokenType::T_NUMBER, '2', 2, 15, 34))
            ),
            new PostfixOperation(
                new Variable('i', new Token(TokenType::T_IDENTIFIER, 'i', 2, 18, 37)),
                TokenType::T_INCREMENT
            ),
            new Program([
                new EchoStatement(
                    new BinaryOperation(
                        new ArrayAccess(
                            new Variable('u', new Token(TokenType::T_IDENTIFIER, 'u', 3, 10, 48)),
                            new Variable('i', new Token(TokenType::T_IDENTIFIER, 'i', 3, 12, 50))
                        ),
                        TokenType::T_PLUS,
                        new Literal(PHP_EOL, new Token(TokenType::T_LINEBREAK, 'LINEBREAK', 3, 17, 55))
                    )
                ),
            ]),
            new Token(TokenType::T_FOR, 'for', 2, 1, 20)
        ),
    ]);

    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);

    $spacing = '    ';

    $expected = "
u = users_list;
for (i = 0; i < 2; i++) {
    echo u[i] + LINEBREAK;
{$spacing}
}
";

    expect(trim($output))->toBe(trim($expected));
});

it('can render an if statement without an else block', function (): void {
    $program = new Program([
        new IfStatement(new Literal(true), new Program([]), null, new Token(TokenType::T_IF, 'if', 1, 1, 0)),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('if (true) {' . "\n    \n" . '}');
});

it('can render an if statement with an else block', function (): void {
    $program = new Program([
        new IfStatement(new Literal(true), new Program([]), new Program([]), new Token(TokenType::T_IF, 'if', 1, 1, 0)),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('if (true) {' . "\n    \n" . '} else {' . "\n    \n" . '}');
});

it('can render a for statement with partial null parts', function (): void {
    $program = new Program([
        new ForStatement(new Assignment(new Variable('i'), new Literal(0)), null, null, new Program([]), new Token(TokenType::T_FOR, 'for', 1, 1, 0)),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('for (i = 0; ; ) {' . "\n    \n" . '}');
});

it('can render a for statement with only a condition', function (): void {
    $program = new Program([
        new ForStatement(null, new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)), null, new Program([]), new Token(TokenType::T_FOR, 'for', 1, 1, 0)),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('for (; i < 10; ) {' . "\n    \n" . '}');
});

it('can render a foreach statement without a key', function (): void {
    $program = new Program([
        new ForeachStatement(
            new Variable('items'),
            new Variable('item'),
            null,
            new Program([]),
            new Token(TokenType::T_FOREACH, 'foreach', 1, 1, 0)
        ),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('foreach (items as item) {' . "\n    \n" . '}');
});

it('can render a foreach statement with a key', function (): void {
    $program = new Program([
        new ForeachStatement(
            new Variable('items'),
            new Variable('item'),
            new Variable('i'),
            new Program([]),
            new Token(TokenType::T_FOREACH, 'foreach', 1, 1, 0)
        ),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('foreach (items as i => item) {' . "\n    \n" . '}');
});

it('can render various binary operators', function (): void {
    $operators = [
        TokenType::T_PLUS->value => '+',
        TokenType::T_MINUS->value => '-',
        TokenType::T_MULTIPLY->value => '*',
        TokenType::T_DIVIDE->value => '/',
        TokenType::T_COMPARE_EQUALS->value => '==',
        TokenType::T_COMPARE_UNEQUALS->value => '!=',
        TokenType::T_GREATER_THAN->value => '>',
        TokenType::T_LESS_THAN->value => '<',
    ];

    foreach ($operators as $tokenType => $operator) {
        $program = new Program([
            new BinaryOperation(new Literal(1), TokenType::tryFrom($tokenType), new Literal(2)),
        ]);
        $renderer = new PhpScriptRenderer;
        $output = $renderer->traverse($program);
        expect(trim($output))->toBe("1 {$operator} 2");
    }
});

it('can render a unary minus operation', function (): void {
    $program = new Program([
        new UnaryOperation(TokenType::T_MINUS, new Literal(1)),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('-1');
});

it('can render a postfix decrement operation', function (): void {
    $program = new Program([
        new PostfixOperation(new Variable('i'), TokenType::T_DECREMENT),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('i--');
});

it('can render an empty statement', function (): void {
    $program = new Program([
        new NoOp,
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('');
});

it('can render a LINEBREAK literal', function (): void {
    $program = new Program([
        new EchoStatement(new Literal(PHP_EOL)),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('echo LINEBREAK;');
});

it('can render a member access', function (): void {
    $program = new Program([
        new MemberAccess(new Variable('foo'), new Identifier('bar')),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('foo.bar');
});

it('can render a function call with arguments', function (): void {
    $program = new Program([
        new FunctionCall(new Identifier('foo'), [new Literal(1), new Literal('bar')]),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe("foo(1, 'bar')");
});

it('can render a function call with no arguments', function (): void {
    $program = new Program([
        new FunctionCall(new Identifier('foo'), []),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('foo()');
});

it('can render a null literal', function (): void {
    $program = new Program([
        new Literal(null),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('null');
});

it('throws an exception for unknown binary operator', function (): void {
    $program = new Program([
        new BinaryOperation(new Literal(1), TokenType::T_UNKNOWN, new Literal(2)),
    ]);
    $renderer = new PhpScriptRenderer;
    $renderer->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown unary operator', function (): void {
    $program = new Program([
        new UnaryOperation(TokenType::T_UNKNOWN, new Literal(1)),
    ]);
    $renderer = new PhpScriptRenderer;
    $renderer->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown postfix operator', function (): void {
    $program = new Program([
        new PostfixOperation(new Variable('i'), TokenType::T_UNKNOWN),
    ]);
    $renderer = new PhpScriptRenderer;
    $renderer->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown literal type', function (): void {
    $program = new Program([
        new EchoStatement(new Literal([])),
    ]);
    $renderer = new PhpScriptRenderer;
    $renderer->traverse($program);
})->throws(AstTraverserException::class);

it('can render a break statement without level', function (): void {
    $program = new Program([
        new BreakStatement(level: 1),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('break;');
});

it('can render a break statement with level', function (): void {
    $program = new Program([
        new BreakStatement(level: 2),
    ]);
    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);
    expect(trim($output))->toBe('break 2;');
});

it('can round-trip a break statement', function (): void {
    // Test that code → AST → code produces the same code
    $lexer = new \PhpScript\Core\Lexer;
    $parser = new \PhpScript\Core\Parser;
    $renderer = new PhpScriptRenderer;

    $code = 'break;';
    $tokens = $lexer->tokenize($code);
    $ast = $parser->parse($tokens);
    $output = $renderer->traverse($ast);

    expect(trim($output))->toBe($code);
});

it('can round-trip a break statement with level', function (): void {
    // Test that code → AST → code produces the same code
    $lexer = new \PhpScript\Core\Lexer;
    $parser = new \PhpScript\Core\Parser;
    $renderer = new PhpScriptRenderer;

    $code = 'break 3;';
    $tokens = $lexer->tokenize($code);
    $ast = $parser->parse($tokens);
    $output = $renderer->traverse($ast);

    expect(trim($output))->toBe($code);
});

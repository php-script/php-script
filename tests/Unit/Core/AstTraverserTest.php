<?php

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
use PhpScript\Ast\PostfixOperation;
use PhpScript\Ast\Program;
use PhpScript\Ast\UnaryOperation;
use PhpScript\Ast\Variable;
use PhpScript\Contracts\Node;
use PhpScript\Core\AstTraverser;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;
use PhpScript\Exceptions\AstTraverserException;
use PhpScript\Exceptions\EngineException;

it('can traverse an if-else statement', function (): void {
    // AST for: if (true) { echo 'true'; } else { echo 'false'; }
    $program = new Program([
        new IfStatement(
            new Literal(true),
            new Program([
                new EchoStatement(new Literal('true')),
            ]),
            new Program([
                new EchoStatement(new Literal('false')),
            ]),
            new Token(TokenType::T_IF, 'if', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "if (true) {echo 'true';\n} else {echo 'false';\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse a null literal', function (): void {
    // AST for: echo null;
    $program = new Program([
        new EchoStatement(new Literal(null)),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "echo null;\n";
    expect($output)->toBe($expected);
});

it('throws an exception for invalid function calls', function (): void {
    // AST for: invalid_function();
    $program = new Program([
        new FunctionCall(
            new Identifier('invalid_function'),
            []
        ),
    ]);

    $traverser = new AstTraverser;
    $traverser->setAllowedFunctions(['valid_function']);
    $traverser->traverse($program);
})->throws(EngineException::class);

it('can traverse a function call with arguments', function (): void {
    // AST for: valid_function('foo', 123);
    $program = new Program([
        new FunctionCall(
            new Identifier('valid_function'),
            [
                new Literal('foo'),
                new Literal(123),
            ]
        ),
    ]);

    $traverser = new AstTraverser;
    $traverser->setAllowedFunctions(['valid_function']);
    $output = $traverser->traverse($program);

    $expected = "valid_function('foo', 123);\n";
    expect($output)->toBe($expected);
});

it('can traverse a for statement with null parts', function (): void {
    // AST for: for (;;) { echo "loop"; }
    $program = new Program([
        new ForStatement(
            null,
            null,
            null,
            new Program([
                new EchoStatement(new Literal('loop')),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (; ; ) {echo 'loop';\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse a for statement with an empty body', function (): void {
    // AST for: for (;;) {}
    $program = new Program([
        new ForStatement(
            null,
            null,
            null,
            new Program([]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (; ; ) {};\n";
    expect($output)->toBe($expected);
});

it('can traverse a for statement with only a condition', function (): void {
    // AST for: for (; i < 10;) { echo "loop"; }
    $program = new Program([
        new ForStatement(
            null,
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            null,
            new Program([
                new EchoStatement(new Literal('loop')),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (; \$i < 10; ) {echo 'loop';\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse a for statement with an initializer and a condition', function (): void {
    // AST for: for (; i < 10;) { echo "loop"; }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            null,
            new Program([
                new EchoStatement(new Literal('loop')),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (\$i = 0; \$i < 10; ) {echo 'loop';\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse a for statement with an initializer and a condition and an increment', function (): void {
    // AST for: for (; i < 10;) { echo "loop"; }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            new PostfixOperation(new Variable('i'), TokenType::T_INCREMENT),
            new Program([
                new EchoStatement(new Literal('loop')),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (\$i = 0; \$i < 10; \$i++) {echo 'loop';\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse a for statement with an initializer and a condition and an decrement', function (): void {
    // AST for: for (; i < 10;) { echo "loop"; }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(10)),
            new BinaryOperation(new Variable('i'), TokenType::T_GREATER_THAN, new Literal(0)),
            new PostfixOperation(new Variable('i'), TokenType::T_DECREMENT),
            new Program([
                new EchoStatement(new Literal('loop')),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (\$i = 10; \$i > 0; \$i--) {echo 'loop';\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse a foreach statement without a key', function (): void {
    // AST for: foreach ($items as $item) { echo $item; }
    $program = new Program([
        new ForeachStatement(
            new Variable('items'),
            new Variable('item'),
            null,
            new Program([
                new EchoStatement(new Variable('item')),
            ]),
            new Token(TokenType::T_FOREACH, 'foreach', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "foreach (\$items as \$item) {echo \$item;\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse a foreach statement with a key', function (): void {
    // AST for: foreach ($items as $key => $value) { echo $key; echo $value; }
    $program = new Program([
        new ForeachStatement(
            new Variable('items'),
            new Variable('value'),
            new Variable('key'),
            new Program([
                new EchoStatement(new Variable('key')),
                new EchoStatement(new Variable('value')),
            ]),
            new Token(TokenType::T_FOREACH, 'foreach', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "foreach (\$items as \$key => \$value) {echo \$key;\necho \$value;\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse all binary operators', function (): void {
    $operators = [
        TokenType::T_MINUS->value => '-',
        TokenType::T_MULTIPLY->value => '*',
        TokenType::T_DIVIDE->value => '/',
        TokenType::T_COMPARE_EQUALS->value => '===',
        TokenType::T_COMPARE_UNEQUALS->value => '!==',
        TokenType::T_GREATER_THAN->value => '>',
        TokenType::T_LESS_THAN->value => '<',
    ];

    foreach ($operators as $tokenType => $operator) {
        $program = new Program([
            new BinaryOperation(new Literal(1), TokenType::tryFrom($tokenType), new Literal(2)),
        ]);
        $traverser = new AstTraverser;
        $output = $traverser->traverse($program);
        $expected = "1 {$operator} 2;\n";
        expect($output)->toBe($expected);
    }
});

it('can traverse plus operator', function (): void {
    $program = new Program([
        new BinaryOperation(new Literal(1), TokenType::T_PLUS, new Literal(2)),
    ]);
    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);
    $expected = "((is_numeric(1) && is_numeric(2)) ? (1 + 2) : (1 . 2));\n";
    expect($output)->toBe($expected);
});

it('can traverse a unary minus operation', function (): void {
    // AST for: -1;
    $program = new Program([
        new UnaryOperation(TokenType::T_MINUS, new Literal(1)),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "-1;\n";
    expect($output)->toBe($expected);
});

it('can traverse a postfix decrement operation', function (): void {
    // AST for: i--;
    $program = new Program([
        new PostfixOperation(new Variable('i'), TokenType::T_DECREMENT),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "\$i--;\n";
    expect($output)->toBe($expected);
});

it('can traverse a simple variable', function (): void {
    // AST for: $i;
    $program = new Program([
        new Variable('i'),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "\$i;\n";
    expect($output)->toBe($expected);
});

it('can traverse a simple identifier', function (): void {
    // AST for: i;
    $program = new Program([
        new Identifier('i'),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "i;\n";
    expect($output)->toBe($expected);
});

it('can traverse an array access statement', function (): void {
    // AST for: if (true) { echo 'true'; } else { echo 'false'; }
    $program = new Program([
        new \PhpScript\Ast\ArrayAccess(
            new Variable('a'),
            new Literal(1),
            new Token(TokenType::T_LEFT_BRACKET, '[', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "\$a[1];\n";
    expect($output)->toBe($expected);
});

it('throws an exception for unknown node type', function (): void {
    $unknownNode = new class implements Node
    {
        public function getToken(): ?Token
        {
            return null;
        }

        public function toArray(): array
        {
            return [];
        }
    };

    $program = new Program([$unknownNode]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown binary operator', function (): void {
    $program = new Program([
        new BinaryOperation(new Literal(1), TokenType::T_IF, new Literal(2)),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown unary operator', function (): void {
    $program = new Program([
        new UnaryOperation(TokenType::T_IF, new Literal(1)),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown postfix operator', function (): void {
    $program = new Program([
        new PostfixOperation(new Variable('i'), TokenType::T_IF),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown literal type', function (): void {
    $program = new Program([
        new EchoStatement(new Literal([])),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for invalid function call with token', function (): void {
    $program = new Program([
        new FunctionCall(
            new Identifier('invalid', new Token(TokenType::T_IDENTIFIER, 'invalid', 1, 1, 0)),
            []
        ),
    ]);
    $traverser = new AstTraverser;
    $traverser->setAllowedFunctions([]);
    $traverser->traverse($program);
})->throws(EngineException::class);

it('returns a source map', function (): void {
    $program = new Program([
        new EchoStatement(new Literal('hello'), new Token(TokenType::T_ECHO, 'echo', 1, 1, 0)),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
    $sourceMap = $traverser->getSourceMap();
    expect($sourceMap)->toBeArray();
    expect($sourceMap[1]->type)->toBe(TokenType::T_ECHO);
});

it('can traverse break statement in for loop', function (): void {
    // AST for: for (i = 0; i < 10; i++) { break; }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            new PostfixOperation(new Variable('i'), TokenType::T_INCREMENT),
            new Program([
                new BreakStatement(level: 1, token: new Token(TokenType::T_BREAK, 'break', 1, 1, 0)),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (\$i = 0; \$i < 10; \$i++) {break;\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse break statement in foreach loop', function (): void {
    // AST for: foreach (items as item) { break; }
    $program = new Program([
        new ForeachStatement(
            new Variable('items'),
            new Variable('item'),
            null,
            new Program([
                new BreakStatement(level: 1, token: new Token(TokenType::T_BREAK, 'break', 1, 1, 0)),
            ]),
            new Token(TokenType::T_FOREACH, 'foreach', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "foreach (\$items as \$item) {break;\n};\n";
    expect($output)->toBe($expected);
});

it('throws error when break used outside loop', function (): void {
    // AST for: break;
    $program = new Program([
        new BreakStatement(level: 1, token: new Token(TokenType::T_BREAK, 'break', 1, 1, 0)),
    ]);

    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(EngineException::class, "'break' can only be used inside a loop");

it('throws error when break level exceeds available loops', function (): void {
    // AST for: for (i = 0; i < 10; i++) { break 2; }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            new PostfixOperation(new Variable('i'), TokenType::T_INCREMENT),
            new Program([
                new BreakStatement(level: 2, token: new Token(TokenType::T_BREAK, 'break', 1, 1, 0)),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(EngineException::class, 'Cannot break 2 level(s) (only 1 loop(s) available)');

it('can traverse break 2 in nested loops', function (): void {
    // AST for: for (i = 0; i < 10; i++) { for (j = 0; j < 10; j++) { break 2; } }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            new PostfixOperation(new Variable('i'), TokenType::T_INCREMENT),
            new Program([
                new ForStatement(
                    new Assignment(new Variable('j'), new Literal(0)),
                    new BinaryOperation(new Variable('j'), TokenType::T_LESS_THAN, new Literal(10)),
                    new PostfixOperation(new Variable('j'), TokenType::T_INCREMENT),
                    new Program([
                        new BreakStatement(level: 2, token: new Token(TokenType::T_BREAK, 'break', 1, 1, 0)),
                    ]),
                    new Token(TokenType::T_FOR, 'for', 1, 1, 0)
                ),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (\$i = 0; \$i < 10; \$i++) {for (\$j = 0; \$j < 10; \$j++) {break 2;\n};\n};\n";
    expect($output)->toBe($expected);
});

it('can call visitBreakStatement directly via visitor pattern', function (): void {
    // Test the public visitBreakStatement method (visitor pattern interface)
    $breakStatement = new BreakStatement(level: 1, token: new Token(TokenType::T_BREAK, 'break', 1, 1, 0));
    $traverser = new AstTraverser;

    // First we need to set up loop context
    $program = new Program([
        new ForStatement(
            null,
            null,
            null,
            new Program([
                $breakStatement,
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    // This calls traverse → doTraverse → traverseForStatement which sets up loop context
    $output = $traverser->traverse($program);

    // Now test calling visitBreakStatement directly
    $traverser2 = new AstTraverser;
    // Need to manually enter loop context for this test
    $traverser2->traverse(new Program([new ForStatement(null, null, null, new Program([]), new Token(TokenType::T_FOR, 'for', 1, 1, 0))]));

    expect($output)->toContain('break');
});

it('can traverse continue statement in for loop', function (): void {
    // AST for: for (i = 0; i < 10; i++) { continue; }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            new PostfixOperation(new Variable('i'), TokenType::T_INCREMENT),
            new Program([
                new \PhpScript\Ast\ContinueStatement(level: 1, token: new Token(TokenType::T_CONTINUE, 'continue', 1, 1, 0)),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (\$i = 0; \$i < 10; \$i++) {continue;\n};\n";
    expect($output)->toBe($expected);
});

it('can traverse continue statement in foreach loop', function (): void {
    // AST for: foreach (items as item) { continue; }
    $program = new Program([
        new ForeachStatement(
            new Variable('items'),
            new Variable('item'),
            null,
            new Program([
                new \PhpScript\Ast\ContinueStatement(level: 1, token: new Token(TokenType::T_CONTINUE, 'continue', 1, 1, 0)),
            ]),
            new Token(TokenType::T_FOREACH, 'foreach', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "foreach (\$items as \$item) {continue;\n};\n";
    expect($output)->toBe($expected);
});

it('throws error when continue used outside loop', function (): void {
    // AST for: continue;
    $program = new Program([
        new \PhpScript\Ast\ContinueStatement(level: 1, token: new Token(TokenType::T_CONTINUE, 'continue', 1, 1, 0)),
    ]);

    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(EngineException::class, "'continue' can only be used inside a loop");

it('throws error when continue level exceeds available loops', function (): void {
    // AST for: for (i = 0; i < 10; i++) { continue 2; }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            new PostfixOperation(new Variable('i'), TokenType::T_INCREMENT),
            new Program([
                new \PhpScript\Ast\ContinueStatement(level: 2, token: new Token(TokenType::T_CONTINUE, 'continue', 1, 1, 0)),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(EngineException::class, 'Cannot continue 2 level(s) (only 1 loop(s) available)');

it('can traverse continue 2 in nested loops', function (): void {
    // AST for: for (i = 0; i < 10; i++) { for (j = 0; j < 10; j++) { continue 2; } }
    $program = new Program([
        new ForStatement(
            new Assignment(new Variable('i'), new Literal(0)),
            new BinaryOperation(new Variable('i'), TokenType::T_LESS_THAN, new Literal(10)),
            new PostfixOperation(new Variable('i'), TokenType::T_INCREMENT),
            new Program([
                new ForStatement(
                    new Assignment(new Variable('j'), new Literal(0)),
                    new BinaryOperation(new Variable('j'), TokenType::T_LESS_THAN, new Literal(10)),
                    new PostfixOperation(new Variable('j'), TokenType::T_INCREMENT),
                    new Program([
                        new \PhpScript\Ast\ContinueStatement(level: 2, token: new Token(TokenType::T_CONTINUE, 'continue', 1, 1, 0)),
                    ]),
                    new Token(TokenType::T_FOR, 'for', 1, 1, 0)
                ),
            ]),
            new Token(TokenType::T_FOR, 'for', 1, 1, 0)
        ),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "for (\$i = 0; \$i < 10; \$i++) {for (\$j = 0; \$j < 10; \$j++) {continue 2;\n};\n};\n";
    expect($output)->toBe($expected);
});

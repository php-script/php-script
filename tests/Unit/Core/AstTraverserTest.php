<?php

use PhpScript\Ast\BinaryOperation;
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

it('can traverse an if-else statement', function () {
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

it('can traverse a null literal', function () {
    // AST for: echo null;
    $program = new Program([
        new EchoStatement(new Literal(null)),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "echo null;\n";
    expect($output)->toBe($expected);
});

it('throws an exception for invalid function calls', function () {
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

it('can traverse a function call with arguments', function () {
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

it('can traverse a for statement with null parts', function () {
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

it('can traverse a for statement with an empty body', function () {
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

it('can traverse a foreach statement without a key', function () {
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

it('can traverse a foreach statement with a key', function () {
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

it('can traverse all binary operators', function () {
    $operators = [
        TokenType::T_PLUS->value => '+',
        TokenType::T_MINUS->value => '-',
        TokenType::T_MULTIPLY->value => '*',
        TokenType::T_DIVIDE->value => '/',
        TokenType::T_CONCAT->value => '.',
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

it('can traverse a unary minus operation', function () {
    // AST for: -1;
    $program = new Program([
        new UnaryOperation(TokenType::T_MINUS, new Literal(1)),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "-1;\n";
    expect($output)->toBe($expected);
});

it('can traverse a postfix decrement operation', function () {
    // AST for: i--;
    $program = new Program([
        new PostfixOperation(new Variable('i'), TokenType::T_DECREMENT),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "\$i--;\n";
    expect($output)->toBe($expected);
});

it('can traverse a simple variable', function () {
    // AST for: $i;
    $program = new Program([
        new Variable('i'),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "\$i;\n";
    expect($output)->toBe($expected);
});

it('can traverse a simple identifier', function () {
    // AST for: i;
    $program = new Program([
        new Identifier('i'),
    ]);

    $traverser = new AstTraverser;
    $output = $traverser->traverse($program);

    $expected = "i;\n";
    expect($output)->toBe($expected);
});

it('throws an exception for unknown node type', function () {
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

it('throws an exception for unknown binary operator', function () {
    $program = new Program([
        new BinaryOperation(new Literal(1), TokenType::T_IF, new Literal(2)),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown unary operator', function () {
    $program = new Program([
        new UnaryOperation(TokenType::T_IF, new Literal(1)),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown postfix operator', function () {
    $program = new Program([
        new PostfixOperation(new Variable('i'), TokenType::T_IF),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for unknown literal type', function () {
    $program = new Program([
        new EchoStatement(new Literal([])),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
})->throws(AstTraverserException::class);

it('throws an exception for invalid function call with token', function () {
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

it('returns a source map', function () {
    $program = new Program([
        new EchoStatement(new Literal('hello'), new Token(TokenType::T_ECHO, 'echo', 1, 1, 0)),
    ]);
    $traverser = new AstTraverser;
    $traverser->traverse($program);
    $sourceMap = $traverser->getSourceMap();
    expect($sourceMap)->toBeArray();
    expect($sourceMap[1]->type)->toBe(TokenType::T_ECHO);
});

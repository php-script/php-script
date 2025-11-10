<?php

use PhpScript\Ast\ArrayAccess;
use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\ForStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\PostfixOperation;
use PhpScript\Ast\Program;
use PhpScript\Ast\Variable;
use PhpScript\Core\PhpScriptRenderer;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can render a simple echo statement', function () {
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

it('can render a more complex script', function () {
    // AST for:
    // u = users_list;
    // for (i = 0; i < 2; i++) {
    //     echo u[i] ~ LINEBREAK;
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
                        TokenType::T_CONCAT,
                        new Literal(PHP_EOL, new Token(TokenType::T_LINEBREAK, 'LINEBREAK', 3, 17, 55))
                    )
                ),
            ]),
            new Token(TokenType::T_FOR, 'for', 2, 1, 20)
        ),
    ]);

    $renderer = new PhpScriptRenderer;
    $output = $renderer->traverse($program);

    $expected = <<<'EOT'
u = users_list
for (i = 0; i < 2; i++) {echo u[i] ~ LINEBREAK;
}
EOT;

    expect(trim($output))->toBe(trim($expected));
});

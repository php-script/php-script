<?php

use PhpScript\Ast\ArrayAccess;
use PhpScript\Ast\Assignment;
use PhpScript\Ast\BinaryOperation;
use PhpScript\Ast\BreakStatement;
use PhpScript\Ast\EchoStatement;
use PhpScript\Ast\ForeachStatement;
use PhpScript\Ast\ForStatement;
use PhpScript\Ast\IfStatement;
use PhpScript\Ast\Literal;
use PhpScript\Ast\NoOp;
use PhpScript\Ast\PostfixOperation;
use PhpScript\Ast\Program;
use PhpScript\Ast\UnaryOperation;
use PhpScript\Ast\Variable;
use PhpScript\Contracts\Node;
use PhpScript\Core\Lexer;
use PhpScript\Core\Parser;
use PhpScript\Core\TokenType;
use PhpScript\Exceptions\ParseException;

function parse(string $code): Node
{
    $lexer = new Lexer;
    $tokens = $lexer->tokenize($code);
    $parser = new Parser;

    return $parser->parse($tokens);
}

it('throws exception for unexpected end of file', function (): void {
    parse('if (true)');
})->throws(ParseException::class, "Expect '{' before block.");

it('throws exception for invalid assignment target', function (): void {
    parse("'hello' = 'world';");
})->throws(ParseException::class, 'Invalid assignment target.');

it('throws exception for unexpected token in primary expression', function (): void {
    parse('*');
})->throws(ParseException::class, 'Unexpected token: T_MULTIPLY');

it('throws exception for missing parenthesis in if statement', function (): void {
    parse('if true) {}');
})->throws(ParseException::class, "Expect '(' after 'if'.");

it('throws exception for missing parenthesis after if condition', function (): void {
    parse('if (true {}');
})->throws(ParseException::class, "Expect ')' after if condition.");

it('throws exception for missing brace after if', function (): void {
    parse('if (true) echo 1;');
})->throws(ParseException::class, "Expect '{' before block.");

it('throws exception for missing brace at end of block', function (): void {
    parse('if (true) {');
})->throws(ParseException::class, "Expect '}' after block.");

it('parses an if statement without an else block', function (): void {
    $program = parse('if (true) { echo "hello"; }');
    expect($program->statements[0])->toBeInstanceOf(IfStatement::class);
    expect($program->statements[0]->else)->toBeNull();
});

it('parses an if statement with an else block', function (): void {
    $program = parse('if (true) { echo "hello"; } else { echo "world"; }');
    expect($program->statements[0])->toBeInstanceOf(IfStatement::class);
    expect($program->statements[0]->else)->toBeInstanceOf(Program::class);
});

it('parses a parenthesized expression', function (): void {
    $program = parse('echo (1 + 2);');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(EchoStatement::class);
    expect($statement->expression)->toBeInstanceOf(BinaryOperation::class);
});

it('parses an empty statement', function (): void {
    $program = parse(';');
    expect($program->statements[0])->toBeInstanceOf(NoOp::class);
});

it('parses various binary operators', function (): void {
    $operators = ['+', '-', '*', '/', '==', '!=', '>', '<'];
    foreach ($operators as $op) {
        $program = parse("1 {$op} 2;");
        $statement = $program->statements[0];
        expect($statement)->toBeInstanceOf(BinaryOperation::class);
    }
});

it('parses a unary not operation', function (): void {
    $program = parse('!true;');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(UnaryOperation::class);
    expect($statement->operator)->toBe(TokenType::T_BANG);
});

it('parses a false operation', function (): void {
    $program = parse('false;');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(Literal::class);
});

it('parses a postfix decrement operation', function (): void {
    $program = parse('i--;');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(PostfixOperation::class);
    expect($statement->operator)->toBe(TokenType::T_DECREMENT);
});

it('parses a for loop with all clauses', function (): void {
    $program = parse('for (i = 0; i < 10; i++) { echo i; }');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(ForStatement::class);
    expect($statement->initializer)->toBeInstanceOf(Assignment::class);
    expect($statement->condition)->toBeInstanceOf(BinaryOperation::class);
    expect($statement->increment)->toBeInstanceOf(PostfixOperation::class);
});

it('parses a foreach loop with a key', function (): void {
    $program = parse('foreach (items as key, value) { echo key; }');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(ForeachStatement::class);
    expect($statement->key)->toBeInstanceOf(Variable::class);
    expect($statement->value)->toBeInstanceOf(Variable::class);
});

it('parses an array access assignment', function (): void {
    $program = parse('a[0] = 1;');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(Assignment::class);
    expect($statement->variable)->toBeInstanceOf(ArrayAccess::class);
});

it('throws exception for missing identifier in member access', function (): void {
    parse('foo.;');
})->throws(ParseException::class, 'Expected identifier after .');

it('throws exception for missing right parenthesis in function call', function (): void {
    parse('foo(1, 2');
})->throws(ParseException::class, "Expect ')' after arguments.");

it('throws exception for missing right parenthesis after expression', function (): void {
    parse('(1 + 2');
})->throws(ParseException::class, "Expect ')' after expression.");

it('parses break statement without level', function (): void {
    $program = parse('break;');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(BreakStatement::class);
    expect($statement->level)->toBe(1);
});

it('parses break statement with explicit levels', function (): void {
    $program = parse('break 1; break 2; break 3;');
    expect($program->statements[0])->toBeInstanceOf(BreakStatement::class);
    expect($program->statements[0]->level)->toBe(1);
    expect($program->statements[1])->toBeInstanceOf(BreakStatement::class);
    expect($program->statements[1]->level)->toBe(2);
    expect($program->statements[2])->toBeInstanceOf(BreakStatement::class);
    expect($program->statements[2]->level)->toBe(3);
});

it('throws exception for break with zero level', function (): void {
    parse('break 0;');
})->throws(ParseException::class, 'Break level must be a positive integer (got 0)');

it('throws exception for break with negative level', function (): void {
    parse('break -1;');
})->throws(ParseException::class, 'Unexpected token: T_MINUS');

it('throws exception for break with non-numeric argument', function (): void {
    parse('break foo;');
})->throws(ParseException::class, 'Unexpected token: T_IDENTIFIER');

it('throws exception for break with float argument', function (): void {
    parse('break 2.5;');
})->throws(ParseException::class, 'Break level must be a positive integer (got 2.5)');

it('parses continue statement without level', function (): void {
    $program = parse('continue;');
    $statement = $program->statements[0];
    expect($statement)->toBeInstanceOf(\PhpScript\Ast\ContinueStatement::class);
    expect($statement->level)->toBe(1);
});

it('parses continue statement with explicit levels', function (): void {
    $program = parse('continue 1; continue 2; continue 3;');
    expect($program->statements[0])->toBeInstanceOf(\PhpScript\Ast\ContinueStatement::class);
    expect($program->statements[0]->level)->toBe(1);
    expect($program->statements[1])->toBeInstanceOf(\PhpScript\Ast\ContinueStatement::class);
    expect($program->statements[1]->level)->toBe(2);
    expect($program->statements[2])->toBeInstanceOf(\PhpScript\Ast\ContinueStatement::class);
    expect($program->statements[2]->level)->toBe(3);
});

it('throws exception for continue with zero level', function (): void {
    parse('continue 0;');
})->throws(ParseException::class, 'Continue level must be a positive integer (got 0)');

it('throws exception for continue with negative level', function (): void {
    parse('continue -1;');
})->throws(ParseException::class, 'Unexpected token: T_MINUS');

it('throws exception for continue with non-numeric argument', function (): void {
    parse('continue foo;');
})->throws(ParseException::class, 'Unexpected token: T_IDENTIFIER');

it('throws exception for continue with float argument', function (): void {
    parse('continue 2.5;');
})->throws(ParseException::class, 'Continue level must be a positive integer (got 2.5)');

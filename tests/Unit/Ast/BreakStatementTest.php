<?php

use PhpScript\Ast\BreakStatement;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

it('can create a break statement with default level', function (): void {
    $breakStatement = new BreakStatement;

    expect($breakStatement->level)->toBe(1);
});

it('can create a break statement with explicit level', function (): void {
    $breakStatement = new BreakStatement(level: 2);

    expect($breakStatement->level)->toBe(2);
});

it('returns correct array representation', function (): void {
    $breakStatement = new BreakStatement(level: 3);

    expect($breakStatement->toArray())->toBe([
        'type' => BreakStatement::class,
        'level' => 3,
    ]);
});

it('stores the token correctly', function (): void {
    $token = new Token(TokenType::T_BREAK, 'break', 1, 1, 0);
    $breakStatement = new BreakStatement(level: 1, token: $token);

    expect($breakStatement->getToken())->toBe($token);
});

it('accept method calls visitor visitBreakStatement', function (): void {
    $breakStatement = new BreakStatement(level: 2);
    $renderer = new \PhpScript\Core\PhpScriptRenderer;

    $result = $breakStatement->accept($renderer);

    expect($result)->toContain('break 2');
});

it('accept method calls AstTraverser visitBreakStatement without loop context', function (): void {
    $breakStatement = new BreakStatement(level: 1);
    $traverser = new \PhpScript\Core\AstTraverser;

    // Calling accept outside a loop should throw an exception
    // This tests the visitBreakStatement method in AstTraverser
    expect(fn (): string => $breakStatement->accept($traverser))
        ->toThrow(\PhpScript\Exceptions\EngineException::class, "'break' can only be used inside a loop");
});

it('accept method returns code when called on AstTraverser in loop context', function (): void {
    $breakStatement = new BreakStatement(level: 2);

    // Use test helper to set loop depth
    $traverser = new \Tests\Support\TestAstTraverser;
    $traverser->setLoopDepthForTesting(2);

    $result = $breakStatement->accept($traverser);

    expect($result)->toContain('break 2');
});

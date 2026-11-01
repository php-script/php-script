<?php

use PhpScript\Ast\ContinueStatement;
use PhpScript\Core\AstTraverser;
use PhpScript\Core\PhpScriptRenderer;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;
use PhpScript\Exceptions\EngineException;
use Tests\Support\TestAstTraverser;

it('can create a continue statement with default level', function (): void {
    $continueStatement = new ContinueStatement;

    expect($continueStatement->level)->toBe(1);
});

it('can create a continue statement with explicit level', function (): void {
    $continueStatement = new ContinueStatement(level: 2);

    expect($continueStatement->level)->toBe(2);
});

it('returns correct array representation', function (): void {
    $continueStatement = new ContinueStatement(level: 3);

    expect($continueStatement->toArray())->toBe([
        'type' => ContinueStatement::class,
        'level' => 3,
    ]);
});

it('stores the token correctly', function (): void {
    $token = new Token(TokenType::T_CONTINUE, 'continue', 1, 1, 0);
    $continueStatement = new ContinueStatement(level: 1, token: $token);

    expect($continueStatement->getToken())->toBe($token);
});

it('accept method calls visitor visitContinueStatement', function (): void {
    $continueStatement = new ContinueStatement(level: 2);
    $renderer = new PhpScriptRenderer;

    $result = $continueStatement->accept($renderer);

    expect($result)->toContain('continue 2');
});

it('accept method calls AstTraverser visitContinueStatement without loop context', function (): void {
    $continueStatement = new ContinueStatement(level: 1);
    $traverser = new AstTraverser;

    // Calling accept outside a loop should throw an exception
    // This tests the visitContinueStatement method in AstTraverser
    expect(fn (): string => $continueStatement->accept($traverser))
        ->toThrow(EngineException::class, "'continue' can only be used inside a loop");
});

it('accept method returns code when called on AstTraverser in loop context', function (): void {
    $continueStatement = new ContinueStatement(level: 2);

    // Use test helper to set loop depth
    $traverser = new TestAstTraverser;
    $traverser->setLoopDepthForTesting(2);

    $result = $continueStatement->accept($traverser);

    expect($result)->toContain('continue 2');
});

<?php

use PhpScript\Ast\ContinueStatement;
use PhpScript\Core\Token;
use PhpScript\Core\TokenType;

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
    $renderer = new \PhpScript\Core\PhpScriptRenderer;

    $result = $continueStatement->accept($renderer);

    expect($result)->toContain('continue 2');
});

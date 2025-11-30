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

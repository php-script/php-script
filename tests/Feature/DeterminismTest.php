<?php

use PhpScript\Core\DeterminismProfile;
use PhpScript\Core\Engine;
use PhpScript\Exceptions\EngineException;

it('is not deterministic by default', function (): void {
    $engine = new Engine;

    expect($engine->isDeterministic())->toBeFalse()
        ->and($engine->allow('time'))->toBe($engine);
});

it('refuses non-deterministic functions in deterministic mode', function (string $function): void {
    new Engine()->deterministic()->allow($function);
})->with(['time', 'rand', 'random_int', 'date', 'getenv', 'uniqid', 'TIME', 'sprintf', 'spl_object_id'])
    ->throws(EngineException::class, 'is not deterministic');

it('refuses already allowed non-deterministic functions when switching to deterministic mode', function (): void {
    new Engine()->allow('strtoupper', 'mt_rand')->deterministic();
})->throws(EngineException::class, "Function 'mt_rand' is not deterministic");

it('allows deterministic functions in deterministic mode', function (): void {
    $engine = new Engine()->deterministic()->allow('strtoupper');

    expect($engine->isDeterministic())->toBeTrue()
        ->and($engine->evaluate('strtoupper("a")'))->toBe('A');
});

it('allows explicitly released non-deterministic functions', function (): void {
    $engine = new Engine()->deterministic()->allowNonDeterministic('time');

    expect($engine->evaluate('time() > 0'))->toBeTrue()
        ->and($engine->deterministic())->toBe($engine);
});

it('can leave deterministic mode', function (): void {
    expect(new Engine()->deterministic()->deterministic(false)->allow('rand')->isDeterministic())->toBeFalse();
});

it('uses now from the context for dates', function (): void {
    $engine = new Engine()->deterministic()->set('now', new DateTimeImmutable('2026-01-31'));

    expect($engine->evaluate("now.modify('+1 day').format('Y-m-d')"))->toBe('2026-02-01');
});

it('knows the non-deterministic functions', function (): void {
    expect(DeterminismProfile::isNonDeterministic('random_bytes'))->toBeTrue()
        ->and(DeterminismProfile::isNonDeterministic('\\Date'))->toBeTrue()
        ->and(DeterminismProfile::isNonDeterministic('strlen'))->toBeFalse();
});

<?php

use PhpScript\Core\Engine;
use PhpScript\Linter\LinterService;

it('formats the script correctly', function (): void {
    $linter = new LinterService;
    $script = "echo 'HI';\n\nasd = 1";
    $expected = "echo 'HI';\nasd = 1;";

    $linted = $linter->withScript($script)->linted();

    expect($linted)->toBe($expected);
});

it('returns the original script on error', function (): void {
    $linter = new LinterService;
    $script = "echo 'HI';\n\nasd = = 1";

    $linted = $linter->withScript($script)->linted();

    expect($linted)->toBe(rtrim($script));
});

it('provides a linter service', function (): void {
    $engine = new Engine;
    $linter = $engine->linter("echo 'hello'");
    expect($linter)->toBeInstanceOf(LinterService::class);
    expect($linter->linted())->toBe("echo 'hello';");
});

<?php

use PhpScript\Core\Engine;
use PhpScript\Linter\LintDiagnostic;
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

it('is lazy and reports no diagnostics for valid scripts', function (): void {
    $linter = new LinterService()->withScript("x = 1\necho x");

    expect($linter->diagnostics())->toBe([])
        ->and($linter->isValid())->toBeTrue()
        ->and($linter->linted())->toBe("x = 1;\necho x;");
});

it('reports syntax errors with position', function (): void {
    $diagnostics = new LinterService()->withScript("echo 'HI'\nasd = = 1")->diagnostics();

    expect($diagnostics)->toHaveCount(1)
        ->and($diagnostics[0]->rule)->toBe(LinterService::RULE_SYNTAX)
        ->and($diagnostics[0]->line)->toBe(2)
        ->and($diagnostics[0]->severity)->toBe(LintDiagnostic::SEVERITY_ERROR);
});

it('reports lexer errors', function (): void {
    $diagnostics = new LinterService()->withScript('x = 1 # 2')->diagnostics();

    expect($diagnostics[0]->toArray())->toMatchArray(['rule' => 'syntax', 'line' => 1, 'column' => 7, 'offset' => 6, 'length' => 1]);
});

it('reports a parse error at the end of input', function (): void {
    $diagnostics = new LinterService()->withScript('')->asExpression()->diagnostics();

    expect($diagnostics[0]->message)->toBe('Expected an expression.')
        ->and($diagnostics[0]->line)->toBe(1)
        ->and($diagnostics[0]->length)->toBe(1);
});

it('lints expressions', function (): void {
    $linter = new LinterService()->withScript('data.amount>100')->asExpression();

    expect($linter->isValid())->toBeTrue()
        ->and($linter->linted())->toBe('data.amount > 100')
        ->and($linter->asExpression(false)->withScript('echo 1')->isValid())->toBeTrue()
        ->and($linter->asExpression()->isValid())->toBeFalse();
});

it('reports disallowed function calls when an allowlist is given', function (): void {
    $linter = new LinterService()->withScript("x = 1\nsystem('id')");

    expect($linter->isValid())->toBeTrue();

    $diagnostics = $linter->withAllowedFunctions(['strlen'])->diagnostics();

    expect($diagnostics)->toHaveCount(1)
        ->and($diagnostics[0]->rule)->toBe(LinterService::RULE_COMPILE)
        ->and($diagnostics[0]->line)->toBe(2)
        ->and($diagnostics[0]->message)->toBe('Invalid function call: system')
        ->and($linter->withoutRule(LinterService::RULE_COMPILE)->isValid())->toBeTrue();
});

it('checks expressions against the allowlist', function (): void {
    $linter = new LinterService()->withScript('strlen("a") + foo()')->asExpression()->withAllowedFunctions(['strlen']);

    expect($linter->diagnostics())->toHaveCount(1);
});

it('reports obvious infinite loops', function (string $script, int $line): void {
    $diagnostics = new LinterService()->withScript($script)->diagnostics();

    expect($diagnostics)->toHaveCount(1)
        ->and($diagnostics[0]->rule)->toBe(LinterService::RULE_INFINITE_LOOP)
        ->and($diagnostics[0]->line)->toBe($line)
        ->and($diagnostics[0]->message)->toBe('Loop has no exit condition and no reachable break or return.');
})->with([
    'no condition' => ["for (;;) {\n echo 1\n}", 1],
    'constant true' => ['for (i = 0; true; i++) { echo i }', 1],
    'constant truthy number' => ['for (; 1;) { echo 1 }', 1],
    'nested break only exits inner loop' => ["x = 1\nfor (;;) {\n for (;;) { break }\n}", 2],
    'nested in if' => ["if (true) {\n for (;;) { echo 1 }\n} else {\n echo 2\n}", 2],
    'nested in foreach' => ["foreach (items as item) {\n for (;;) {\n  continue\n }\n}", 2],
]);

it('accepts loops with a condition, a break or a return', function (string $script): void {
    expect(new LinterService()->withScript($script)->isValid())->toBeTrue();
})->with([
    'condition' => ['for (i = 0; i < 10; i++) { echo i }'],
    'constant false' => ['for (; false;) { echo 1 }'],
    'break' => ['for (;;) { break }'],
    'break in if' => ['for (;;) { if (x) { echo 1 } else { break } }'],
    'return' => ['for (;;) { return 1 }'],
    'break 2 from nested loop' => ['for (;;) { foreach (items as item) { break 2 } }'],
    'foreach' => ['foreach (items as item) { echo item }'],
]);

it('can disable and re-enable the infinite loop rule', function (): void {
    $linter = new LinterService()->withScript('for (;;) { echo 1 }')->withoutRule(LinterService::RULE_INFINITE_LOOP);

    expect($linter->isValid())->toBeTrue()
        ->and($linter->withRule(LinterService::RULE_INFINITE_LOOP)->diagnostics())->toHaveCount(1)
        ->and($linter->diagnostics()[0]->severity)->toBe(LintDiagnostic::SEVERITY_WARNING)
        ->and($linter->isValid())->toBeTrue();
});

it('rejects unknown lint rules', function (string $method): void {
    new LinterService()->{$method}('infinite_loop');
})->with(['withoutRule', 'withRule'])->throws(InvalidArgumentException::class, "Unknown lint rule 'infinite_loop'");

it('passes the engine allowlist to the linter', function (): void {
    $engine = new Engine()->allow('strlen');

    expect($engine->linter("strlen('a')")->isValid())->toBeTrue()
        ->and($engine->linter("system('a')")->isValid())->toBeFalse();
});

it('reports compile errors without prefix and source position', function (): void {
    $diagnostics = new LinterService()->withScript('break')->withAllowedFunctions([])->diagnostics();

    expect($diagnostics)->toHaveCount(1)
        ->and($diagnostics[0]->message)->toBe("'break' can only be used inside a loop")
        ->and($diagnostics[0]->column)->toBe(1);
});

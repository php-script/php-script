<?php

use PhpScript\Core\Engine;
use PhpScript\Exceptions\EngineException;

/*
 * Regression tests for sandbox escapes found while building the flow runtime
 * extensions. Each of these executed or leaked data before the fix.
 */

it('rejects dynamic callees', function (string $script): void {
    new Engine()->allow('strtoupper')->set('a', ['system'])->run($script);
})->with([
    'string callee' => ["'system'('id')"],
    'call result callee' => ["strtoupper('system')('id')"],
    'array element callee' => ["a[0]('id')"],
    'variable callee' => ["f = 'system'\nf('id')"],
    'parenthesized callee' => ["('system')('id')"],
])->throws(EngineException::class);

it('rejects functions that were not allowed', function (): void {
    new Engine()->run("system('id')");
})->throws(EngineException::class, 'Invalid function call: system');

it('rejects magic method calls on context objects', function (string $method): void {
    new Engine()->set('obj', new ArrayObject)->run("obj.{$method}()");
})->with(['__construct', '__destruct', '__toString', '__invoke'])
    ->throws(EngineException::class, 'Calling magic method');

it('hides engine internals and superglobals from scripts', function (string $variable): void {
    new Engine()->run("echo {$variable}");
})->with(['this', 'GLOBALS', '_SERVER', '_ENV', '_GET', '_POST', '_COOKIE', '_FILES', '_REQUEST', '_SESSION', 'argv', 'argc', 'http_response_header', '__ps_budget', '__ps_file'])
    ->throws(EngineException::class, 'is reserved');

it('does not leak variables of the engine scope', function (string $variable): void {
    new Engine()->run("echo {$variable}");
})->with(['tmpFile', 'usePcntl', 'phpCode', 'sourceMap', 'budget', 'value'])
    ->throws(EngineException::class, 'Undefined variable');

it('refuses reserved context variable names', function (string $name): void {
    new Engine()->set($name, 'x');
})->with(['this', 'GLOBALS', '_SERVER', '__ps_budget'])
    ->throws(InvalidArgumentException::class, 'is reserved');

it('rejects object instantiation and static calls', function (string $script): void {
    new Engine()->run($script);
})->with([
    'new' => ['x = new DateTime()'],
    'static call' => ['x = DateTime::createFromFormat("Y", "2020")'],
])->throws(EngineException::class);

it('evaluates the left operand of + only once', function (): void {
    $counter = new class
    {
        public int $calls = 0;

        public function next(): int
        {
            return ++$this->calls;
        }
    };

    new Engine()->set('counter', $counter)->evaluate('counter.next() + 1');

    expect($counter->calls)->toBe(1);
});

it('escapes string literals', function (): void {
    expect(new Engine()->evaluate("'it\\'s' + \"a\\\"b\" + '\$x' + '{\$x}'"))->toBe('it\'sa"b$x{$x}');
});

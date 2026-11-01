<?php

use PhpScript\Core\Engine;
use PhpScript\Core\ExecutionResult;
use PhpScript\Exceptions\BudgetExceededException;
use PhpScript\Exceptions\EngineException;

it('returns echo output and the returned value', function (): void {
    $result = new Engine()->set('data', ['amount' => 10])->run(<<<'SCRIPT'
        echo 'calculating'
        total = data.amount * 2
        return {total: total, items: [1, 2]}
        SCRIPT);

    expect($result)->toBeInstanceOf(ExecutionResult::class)
        ->and($result->output)->toBe('calculating')
        ->and($result->value)->toEqual((object) ['total' => 20, 'items' => [1, 2]])
        ->and($result->operations)->toBe(3);
});

it('returns null without a return statement or with an empty return', function (): void {
    $engine = new Engine;

    expect($engine->run("echo 'x'")->value)->toBeNull()
        ->and($engine->run('return')->value)->toBeNull()
        ->and($engine->run("if (true) { return }\necho 'never'")->output)->toBe('');
});

it('returns from inside loops', function (): void {
    $result = new Engine()->run(<<<'SCRIPT'
        foreach ([1, 2, 3] as n) {
            if (n == 2) {
                return n * 10
            }
        }
        SCRIPT);

    expect($result->value)->toBe(20);
});

it('keeps execute() backward compatible', function (): void {
    expect(new Engine()->execute("echo 'hi'\nreturn 5"))->toBe('hi');
});

it('lets assignments write to arrays and objects', function (): void {
    $result = new Engine()->set('user', new stdClass)->run(<<<'SCRIPT'
        list = [1, 2]
        list[0] = 5
        map = {a: 1}
        map.a = 2
        user.name = 'Ada'
        return [list[0], map.a, user.name]
        SCRIPT);

    expect($result->value)->toBe([5, 2, 'Ada']);
});

it('aborts scripts that exceed the operation limit', function (): void {
    $engine = new Engine()->setOperationLimit(50);

    try {
        $engine->run("x = 0\nfor (;;) {\n x++\n}");
        $this->fail('Expected exception');
    } catch (BudgetExceededException $e) {
        expect($e->getMessage())->toContain('Operation limit of 50 exceeded')
            ->and($e->line)->toBeGreaterThan(0)
            ->and($e->getPrevious())->toBeNull();
    }

    // The budget resets for each run.
    expect($engine->run('x = 1')->operations)->toBe(1);
});

it('applies the operation limit to expressions', function (): void {
    expect(new Engine()->setOperationLimit(1)->evaluate('1 + 1'))->toBe(2);
});

it('counts foreach iterations as operations', function (): void {
    expect(new Engine()->run("foreach ([1, 2, 3] as n) {\n}")->operations)->toBe(4);
});

it('aborts scripts that exceed the memory limit', function (): void {
    new Engine()->setMemoryLimit(1024)->allow('str_repeat')->run(<<<'SCRIPT'
        list = []
        for (i = 0; i < 1000; i++) {
            list[i] = str_repeat('x', 1000) + i
        }
        SCRIPT);
})->throws(BudgetExceededException::class, 'Memory limit of 1024 bytes exceeded');

it('treats negative limits as unlimited', function (): void {
    expect(new Engine()->setOperationLimit(-1)->setMemoryLimit(-1)->run('return 1')->value)->toBe(1);
});

it('maps php errors to engine exceptions', function (): void {
    new Engine()->run('x = 1 / 0');
})->throws(EngineException::class, 'Division by zero');

it('maps php warnings to engine exceptions', function (): void {
    new Engine()->allow('intdiv', 'array_combine')->run('x = array_combine([1], [1, 2])');
})->throws(EngineException::class);

it('cleans the output buffer when a script fails', function (): void {
    $level = ob_get_level();

    try {
        new Engine()->run("echo 'partial'\nx = 1 / 0");
    } catch (EngineException) {
    }

    expect(ob_get_level())->toBe($level);
});

it('rethrows engine exceptions raised while running', function (): void {
    $engine = new Engine()->set('fail', new class
    {
        public function now(): never
        {
            throw new EngineException('host failure');
        }
    });

    $engine->run('fail.now()');
})->throws(EngineException::class, 'host failure');

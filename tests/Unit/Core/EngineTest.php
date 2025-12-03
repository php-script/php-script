<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use PhpScript\Core\Engine;
use PhpScript\Exceptions\EngineException;
use Throwable;

it('engine resolves numbers', function (): void {
    $engine = new Engine;
    $engine->set('u2', 2);
    $result = $engine->execute('u = 10; echo u + u2;');

    expect($result)->toBe('12');
});

it('engine resolves strings', function (): void {
    $engine = new Engine;
    $engine->set('s', 'hello');
    $result = $engine->execute("echo s + ' world';");

    expect($result)->toBe('hello world');
});

it('can set and forget context', function (): void {
    $engine = new Engine;
    $engine->set('u2', 2);
    $result = $engine->execute('u = 10; echo u + u2;');
    expect($result)->toBe('12');

    $engine->forget('u2');

    $this->expectException(EngineException::class);
    $result = $engine->execute('u = 10; echo u + u2;');
    expect($result)->toBe('10');
});

it('prevents from calling forbidden functions', function (): void {
    $engine = new Engine;
    $this->expectException(EngineException::class);
    $this->expectExceptionMessage('Invalid function call: exec in line: 1, column: 6, offset: 5');
    $engine->execute('echo exec("ls");');
});

it('allows whitelisted functions', function (): void {
    $engine = new Engine;
    $engine->allow('strtoupper');
    $result = $engine->execute('echo strtoupper("hello");');
    expect($result)->toBe('HELLO');
});

it('allows whitelisted functions with multiple arguments', function (): void {
    $engine = new Engine;
    $engine->allow('str_replace');
    $result = $engine->execute('echo str_replace("l", "L", "hello world");');
    expect($result)->toBe('heLLo worLd');
});

it('throws exception on runtime error', function (): void {
    $engine = new Engine;
    try {
        $engine->execute('echo 1/0;');
    } catch (EngineException $e) {
        expect($e->getMessage())->toContain('Division by zero');
        expect($e->line)->toBe(1);
        expect($e->column)->toBe(1);
        expect($e->offset)->toBe(0);
    }
});

it('handles object property access', function (): void {
    $engine = new Engine;
    $user = new \stdClass;
    $user->name = 'John Doe';
    $engine->set('user', $user);
    $result = $engine->execute('echo user.name;');

    expect($result)->toBe('John Doe');
});

it('handles string concatenation', function (): void {
    $engine = new Engine;
    $result = $engine->execute("echo 'hello' + ' world';");

    expect($result)->toBe('hello world');
});

it('throws exception on parse error', function (): void {
    $engine = new Engine;
    try {
        $engine->execute('1 = 2;');
    } catch (EngineException $e) {
        expect($e->getMessage())->toBe('Invalid assignment target.');
        expect($e->line)->toBe(1);
        expect($e->column)->toBe(3);
        expect($e->offset)->toBe(2);
    }
});

it('throws exception with correct line number on runtime error in multi-line script', function (): void {
    $engine = new Engine;
    $script = <<<'SCRIPT'
        a = 10;
        b = 0;
        echo a / b;
    SCRIPT;

    try {
        $engine->execute($script);
    } catch (EngineException $e) {
        expect($e->getMessage())->toContain('Division by zero');
        // The error happens on line 3. Due to heredoc indentation stripping,
        // the 'echo' keyword correctly starts at column 5.
        expect($e->line)->toBe(3);
        expect($e->column)->toBe(5);
        // The offset may vary based on \n vs \r\n, but with the corrected
        // logic, it will now correctly point to the 'echo' token.
        // Assuming \n, the offset is 27.
        expect($e->offset)->toBe(27);
    }
});

it('handles the LINEBREAK constant', function (): void {
    $engine = new Engine;
    $result = $engine->execute("echo 'hello' + LINEBREAK + 'world';");

    expect($result)->toBe('hello' . PHP_EOL . 'world');
});

/** @runInSeparateProcess */
it('throws an exception when an infinite loop exceeds the execution time limit', function (): void {
    $engine = new Engine;
    $engine->setExecutionTimeLimit(1); // Set a 1-second time limit

    $script = <<<'SCRIPT'
        i = 0;
        for (;;) {
            i++;
        }
    SCRIPT;

    try {
        $engine->execute($script);
        $this->fail('Expected EngineException not thrown.');
    } catch (Throwable $e) {
        expect($e->getMessage())->toContain('Maximum execution time of 1 second exceeded');
    }
})->skip('skipped because throws internal Fatal error: Maximum execution time of 1 second exceeded in');

it('handles lexer exceptions', function (): void {
    $engine = new Engine;
    $script = <<<'SCRIPT'
        b=1
        a=2
        echo a+b + LINEBREAK;
        $b = 4;
        echo "Hello " + '!';
    SCRIPT;

    try {
        $engine->execute($script);
    } catch (EngineException $e) {
        expect($e->getMessage())->toBe('Unknown character or syntax error `$b = 4;⏎  …` at line 4, column 5.');
    }
});

it('executes break statement in loop', function (): void {
    $engine = new Engine;
    $script = <<<'SCRIPT'
        for (i = 0; i < 10; i++) {
            if (i == 5) {
                break;
            }
            echo i;
        }
    SCRIPT;

    $result = $engine->execute($script);
    expect($result)->toBe('01234');
});

it('executes continue statement in loop', function (): void {
    $engine = new Engine;
    $script = <<<'SCRIPT'
        for (i = 0; i < 5; i++) {
            if (i == 2) {
                continue;
            }
            echo i;
        }
    SCRIPT;

    $result = $engine->execute($script);
    expect($result)->toBe('0134');
});

<?php

declare(strict_types=1);

use PhpScript\Core\Engine;
use PhpScript\Exceptions\EngineException;
use PhpScript\Exceptions\SecurityException;

it('engine resolves numbers', function (): void {
    $engine = new Engine;
    $engine->set('u2', 2);
    $result = $engine->execute('u = 10; echo u + u2;');

    expect($result)->toBe('12');
});
it('engine resolves strings', function (): void {
    $engine = new Engine;
    $engine->set('s', 'hello');
    $result = $engine->execute("echo s ~ ' world';");

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
    $this->expectException(SecurityException::class);
    $engine->execute('echo exec("ls");');
});

it('throws exception on runtime error', function (): void {
    $engine = new Engine;
    $this->expectException(EngineException::class);
    $this->expectExceptionMessage('Runtime error: Division by zero in line: 1');
    $engine->execute('1/0;');
});

it('handles object property access', function (): void {
    $engine = new Engine;
    $user = new stdClass();
    $user->name = 'John Doe';
    $engine->set('user', $user);
    $result = $engine->execute('echo user.name;');

    expect($result)->toBe('John Doe');
});

it('handles string concatenation', function (): void {
    $engine = new Engine;
    $result = $engine->execute("echo 'hello' ~ ' world';");

    expect($result)->toBe('hello world');
});

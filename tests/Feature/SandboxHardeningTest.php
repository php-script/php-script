<?php

use PhpScript\Core\Engine;
use PhpScript\Core\Lexer;
use PhpScript\Core\Parser;
use PhpScript\Core\PhpScriptRenderer;
use PhpScript\Exceptions\BudgetExceededException;
use PhpScript\Exceptions\EngineException;
use PhpScript\Exceptions\LexerException;
use PhpScript\Exceptions\ParseException;
use PhpScript\Exceptions\ScriptRuntimeException;
use PhpScript\Runtime\CallGuard;
use PhpScript\Runtime\Runtime;
use PhpScript\Runtime\Signature;

final class HardeningCollection
{
    /** @param list<mixed> $items */
    public function __construct(public array $items = [1, 2]) {}

    /** @return list<mixed> */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items);
    }

    /** @return list<mixed> */
    public function each($callback): array
    {
        return array_map($callback, $this->items);
    }

    public function where(mixed $column, string $value = ''): string
    {
        return $column . $value;
    }

    public function closure(Closure $callback): mixed
    {
        return $callback(1);
    }

    public function either(callable|string $value): string
    {
        return is_string($value) ? $value : 'callable';
    }

    public function counted(Countable&ArrayAccess $value): int
    {
        return count($value);
    }

    public function all(mixed ...$values): int
    {
        return count($values);
    }

    public static function make(): self
    {
        return new self;
    }
}

final class HardeningMagic
{
    /** @var array<string, mixed> */
    private array $attributes = ['name' => null];

    public string $title = 'T';

    private string $secret = 's';

    /** @param list<mixed> $arguments */
    public function __call(string $method, array $arguments): string
    {
        return $method . ':' . implode(',', array_map(static fn (mixed $a): string => is_string($a) ? $a : get_debug_type($a), $arguments));
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function secret(): string
    {
        return $this->secret;
    }
}

function hardeningEngine(): Engine
{
    return new Engine()
        ->allow('strtoupper', 'strlen')
        ->allowWithCallbacks('array_map', 'usort')
        ->set('collection', new HardeningCollection)
        ->set('magic', new HardeningMagic);
}

describe('allow()', function (): void {
    it('rejects functions that can never be allowed', function (string $function): void {
        new Engine()->allow($function);
    })->with(['extract', 'EXIT', 'ob_start', 'set_error_handler', 'ini_set', '\\get_defined_vars'])
        ->throws(InvalidArgumentException::class, 'can never be allowed');

    it('rejects functions that do not exist', function (string $function): void {
        new Engine()->allow($function);
    })->with(['does_not_exist', '\\strlen', 'Foo::bar', 'str len'])
        ->throws(InvalidArgumentException::class, 'does not exist');

    it('requires allowWithCallbacks() for functions that take callbacks', function (): void {
        new Engine()->allow('array_map');
    })->throws(InvalidArgumentException::class, "Function 'array_map' takes a callback. Use allowWithCallbacks() to allow it.");

    it('ignores duplicates', function (): void {
        $engine = new Engine()->allow('strlen', 'strlen')->allow('strlen');

        expect($engine->linter('strlen("a")')->isValid())->toBeTrue();
    });
});

describe('callbacks', function (): void {
    it('accepts allowed functions and host closures as callbacks', function (): void {
        $engine = hardeningEngine()->set('double', static fn (int $x): int => $x * 2);

        expect($engine->evaluate('array_map("strtoupper", ["a", "b"])'))->toBe(['A', 'B'])
            ->and($engine->evaluate('array_map("STRTOUPPER", ["a"])'))->toBe(['A'])
            ->and($engine->evaluate('array_map(double, [1, 2])'))->toBe([2, 4])
            ->and($engine->evaluate('array_map(null, [1, 2])'))->toBe([1, 2])
            ->and($engine->evaluate('collection.map("strlen")'))->toBe([1, 1])
            ->and($engine->evaluate('collection.closure(double)'))->toBe(2);
    });

    it('rejects other callbacks for allowed functions', function (string $expression, string $message): void {
        expect(fn (): mixed => hardeningEngine()->evaluate($expression))->toThrow(EngineException::class, $message);
    })->with([
        'php function' => ['array_map("shell_exec", ["id"])', "Passing 'shell_exec' as a callback is not allowed."],
        'static method string' => ['array_map("DateTime::createFromFormat", ["x"])', "Passing 'DateTime::createFromFormat' as a callback"],
        'array callable' => ['array_map(["DateTime", "createFromFormat"], ["x"])', 'Passing array as a callback'],
    ]);

    it('checks callbacks passed to methods', function (string $expression, string $message): void {
        expect(fn (): mixed => hardeningEngine()->evaluate($expression))->toThrow(EngineException::class, $message);
    })->with([
        'callable parameter' => ['collection.map("shell_exec")', "Passing 'shell_exec' as a callback"],
        'untyped parameter, static string' => ['collection.each("DateTime::createFromFormat")', "Passing 'DateTime::createFromFormat'"],
        'untyped parameter, array callable' => ['collection.each([collection, "map"])', 'Passing array as a callback'],
        'mixed parameter' => ['collection.where("A::b")', "Passing 'A::b'"],
        'variadic mixed parameter' => ['collection.all(1, 2, "A::b")', "Passing 'A::b'"],
        'union with callable' => ['collection.either("system")', "Passing 'system'"],
        'magic __call' => ['magic.anything(1, "A::b")', "Passing 'A::b'"],
    ]);

    it('lets data through untyped and typed parameters', function (): void {
        $engine = hardeningEngine()->set('list', new ArrayObject([1, 2]));

        expect($engine->evaluate('collection.where("date", "x")'))->toBe('datex')
            ->and($engine->evaluate('collection.all(1, [2, 3], "a")'))->toBe(3)
            ->and($engine->evaluate('collection.counted(list)'))->toBe(2)
            ->and($engine->evaluate('collection.each("strlen")'))->toBe([1, 1]);
    });
});

describe('method calls', function (): void {
    it('calls public methods of context objects', function (): void {
        expect(hardeningEngine()->evaluate('magic.secret()'))->toBe('s')
            ->and(hardeningEngine()->evaluate('magic.whatever("x")'))->toBe('whatever:x')
            ->and(hardeningEngine()->evaluate('magic.name'))->toBeNull();
    });

    it('rejects calls that are not plain instance method calls', function (string $expression, string $message): void {
        expect(fn (): mixed => hardeningEngine()->evaluate($expression))->toThrow(EngineException::class, $message);
    })->with([
        'static method' => ['collection.make()', "Calling static method 'make' is not allowed"],
        'protected method' => ['collection.hidden()', 'Call to undefined method HardeningCollection::hidden()'],
        'missing method' => ['collection.missing()', 'Call to undefined method HardeningCollection::missing()'],
        'method on a string' => ['"abc".length()', "Cannot call method 'length' on string"],
    ]);

    it('caches method signatures per class', function (): void {
        $guard = new CallGuard(['strlen']);
        $collection = new HardeningCollection(['ab']);

        expect($guard->call($collection, 'map', 'strlen'))->toBe([2])
            ->and($guard->call($collection, 'MAP', 'strlen'))->toBe([2]);
    });
});

describe('signatures', function (): void {
    it('classifies parameters', function (): void {
        $signature = Signature::of(new ReflectionMethod(HardeningCollection::class, 'all'));

        expect(Signature::at($signature, 5))->toBe(Signature::LOOSE)
            ->and(Signature::at(Signature::of(new ReflectionFunction('strlen')), 3))->toBeNull()
            ->and(Signature::at(Signature::of(new ReflectionMethod(HardeningCollection::class, 'counted')), 0))->toBeNull()
            ->and(Signature::takesCallback(new ReflectionFunction('usort')))->toBeTrue()
            ->and(Signature::takesCallback(new ReflectionFunction('strlen')))->toBeFalse();
    });
});

describe('writes', function (): void {
    it('writes through paths', function (): void {
        $object = new HardeningMagic;
        $bag = new ArrayObject(['x' => new HardeningMagic]);
        $result = new Engine()
            ->set('data', ['a' => ['b' => [1, 2]]])
            ->set('object', $object)
            ->set('bag', $bag)
            ->run(<<<'SCRIPT'
                data.a.b[1] = 20
                data.a.c = 'new'
                object.title = 'changed'
                bag["x"].title = 'deep'
                bag["y"] = 5
                map = {a: {b: 1}}
                map.a.b = 2
                map["a"].c = 3
                list = [[1]]
                list[0][0] = 9
                return [data, map, list]
                SCRIPT);

        expect($result->value[0])->toBe(['a' => ['b' => [1, 20], 'c' => 'new']])
            ->and($result->value[1])->toEqual((object) ['a' => (object) ['b' => 2, 'c' => 3]])
            ->and($result->value[2])->toBe([[9]])
            ->and($object->title)->toBe('changed')
            ->and($bag['x']->title)->toBe('deep')
            ->and($bag['y'])->toBe(5);
    });

    it('rejects writes that cannot be done', function (string $script, string $message): void {
        expect(fn (): mixed => new Engine()
            ->set('object', new HardeningMagic)
            ->set('bag', new ArrayObject(['list' => [1]]))
            ->run($script))->toThrow(EngineException::class, $message);
    })->with([
        'missing key on the way' => ['x = {}' . "\n" . 'x.a.b = 1', "Undefined property 'a'"],
        'into a string' => ["s = 'abc'\ns[0] = 'x'", "Cannot access '0' on string"],
        'below a string' => ["s = {a: 'abc'}\ns.a[0] = 'x'", "Cannot access '0' on string"],
        'through ArrayAccess array' => ['bag["list"][0] = 2', "Cannot modify 'list' of ArrayObject"],
        'through private property' => ['object.secret.x = 1', "Cannot modify 'secret' of HardeningMagic"],
        'invalid key' => ['x = [1]' . "\n" . 'x[null] = 2', 'Invalid key of type null'],
        'invalid key on the way' => ['x = [[1]]' . "\n" . 'x[true][0] = 2', 'Invalid key of type bool'],
    ]);

    it('rejects member keys that are not strings or integers', function (): void {
        $root = (object) ['a' => 1];

        Runtime::increment($root, [[Runtime::MEMBER, null]], 1);
    })->throws(ScriptRuntimeException::class, 'Invalid key of type null');

    it('increments numbers only', function (): void {
        $result = new Engine()->run(<<<'SCRIPT'
            x = {count: 1, total: 1.5}
            x.count++
            x.total--
            i = 0
            i++
            return [x.count, x.total, i]
            SCRIPT);

        expect($result->value)->toBe([2, 0.5, 1]);
    });

    it('rejects incrementing other values', function (string $script, string $message): void {
        expect(fn (): mixed => new Engine()->run($script))->toThrow(EngineException::class, $message);
    })->with([
        ["x = 'a'\nx++", 'Cannot increment string'],
        ["x = {a: null}\nx.a--", 'Cannot decrement null'],
    ]);
});

describe('equality', function (): void {
    it('compares values without type juggling', function (string $expression, bool $expected): void {
        expect(new Engine()
            ->set('a', new HardeningCollection([1]))
            ->set('b', new HardeningCollection([1]))
            ->set('c', new HardeningCollection([2]))
            ->set('d', new HardeningMagic)
            ->evaluate($expression))->toBe($expected);
    })->with([
        ['[1, 2] == [1, 2]', true],
        ['{a: 1, b: 2} == {b: 2, a: 1}', true],
        ['[1, 2] == [2, 1]', false],
        ['[1] == [1, 2]', false],
        ['{a: 1} == {b: 1}', false],
        ['[1] == ["1"]', false],
        ['a == b', true],
        ['a == c', false],
        ['a == d', false],
        ['a == a', true],
        ['null == false', false],
        ['10 == 10.0', true],
    ]);

    it('stops comparing values that are nested too deeply', function (): void {
        $deep = [];
        for ($i = 0; $i < 70; $i++) {
            $deep = [$deep];
        }

        new Engine()->set('a', $deep)->set('b', $deep)->evaluate('a == b');
    })->throws(EngineException::class, 'Values are nested too deeply to compare');
});

describe('limits', function (): void {
    it('counts string concatenation against the memory limit', function (): void {
        new Engine()->setMemoryLimit(1_000_000)->run(<<<'SCRIPT'
            s = 'x'
            for (i = 0; i < 40; i++) {
                s = s + s
            }
            SCRIPT);
    })->throws(BudgetExceededException::class, 'Memory limit of 1000000 bytes exceeded');

    it('aborts a script that runs too long and restores the signal handler', function (): void {
        $handler = static function (): void {};
        pcntl_signal(SIGALRM, $handler);

        try {
            new Engine()->setExecutionTimeLimit(1)->run('for (;;) {}');
            $this->fail('Expected exception');
        } catch (BudgetExceededException $e) {
            expect($e->getMessage())->toContain('Maximum execution time of 1 second(s) exceeded')
                ->and($e->line)->toBe(1)
                ->and(pcntl_signal_get_handler(SIGALRM))->toBe($handler);
        } finally {
            pcntl_signal(SIGALRM, SIG_DFL);
        }
    })->skip(! function_exists('pcntl_signal'), 'pcntl is not available');

    it('treats negative limits as unlimited', function (): void {
        expect(new Engine()->setExecutionTimeLimit(-1)->evaluate('1'))->toBe(1);
    });

    it('removes its temporary files', function (): void {
        $pattern = sys_get_temp_dir() . '/php-script_run_*';
        $before = count(glob($pattern) ?: []);

        new Engine()->run('x = 1');
        try {
            new Engine()->run('x = y');
        } catch (EngineException) {
        }

        expect(count(glob($pattern) ?: []))->toBe($before);
    });

    it('removes files left behind by an aborted run', function (): void {
        $file = tempnam(sys_get_temp_dir(), 'php-script_run_');
        $pending = new ReflectionProperty(Engine::class, 'pendingFiles');
        $pending->setValue(null, [$file => true]);

        Engine::removePendingFiles();

        expect(file_exists((string) $file))->toBeFalse()
            ->and($pending->getValue())->toBe([]);
    });

    it('keeps deterministic mode off when switching fails', function (): void {
        $engine = new Engine()->allow('time');

        expect(fn (): Engine => $engine->deterministic())->toThrow(EngineException::class)
            ->and($engine->isDeterministic())->toBeFalse();
    });

    it('matches released non-deterministic functions case-insensitively', function (): void {
        expect(new Engine()->allowNonDeterministic('TIME')->deterministic()->isDeterministic())->toBeTrue();
    });
});

describe('parser', function (): void {
    it('rejects scripts that are nested too deeply', function (string $script): void {
        new Engine()->set('x', 1)->run($script);
    })->with([
        'parentheses' => [str_repeat('(', 300) . '1' . str_repeat(')', 300)],
        'operator chain' => ['x = 1' . str_repeat(' + 1', 300)],
        'unary chain' => ['x = ' . str_repeat('!', 300) . 'true'],
        'blocks' => [str_repeat('if (true) {', 300) . str_repeat('}', 300)],
        'lists' => [str_repeat('[', 300) . str_repeat(']', 300)],
        'member chain' => ['x' . str_repeat('.a', 300)],
    ])->throws(EngineException::class, 'Script is nested too deeply.');

    it('accepts reasonably nested scripts', function (): void {
        expect(new Engine()->evaluate(str_repeat('(', 50) . '1' . str_repeat(' + 1)', 50)))->toBe(51);
    });

    it('rejects invalid write targets', function (string $script, string $message): void {
        (new Parser)->parse((new Lexer)->tokenize($script));
    })->with([
        ['f() = 1', 'Invalid assignment target.'],
        ['f().x = 1', 'Invalid assignment target.'],
        ['(1).x = 1', 'Invalid assignment target.'],
        ['f()++', 'Invalid increment or decrement target.'],
        ['1++', 'Invalid increment or decrement target.'],
        ['a.b()--', 'Invalid increment or decrement target.'],
    ])->throws(ParseException::class);

    it('rejects numbers that do not fit', function (string $number): void {
        new Engine()->evaluate($number);
    })->with(['99999999999999999999', str_repeat('9', 400) . '.0'])->throws(EngineException::class, 'is too large.');

    it('reads numbers with leading zeros', function (): void {
        expect(new Engine()->evaluate('007'))->toBe(7)
            ->and(new Engine()->evaluate('000'))->toBe(0)
            ->and(new Engine()->evaluate('9223372036854775807'))->toBe(PHP_INT_MAX);
    });

    it('binds relational operators tighter than equality', function (): void {
        expect(new Engine()->evaluate('true == 1 < 2'))->toBeTrue()
            ->and(new Engine()->evaluate('1 < 2 == 2 < 3'))->toBeTrue();
    });

    it('ends return, break and continue at a line break', function (): void {
        expect(new Engine()->run("return\n5")->value)->toBeNull()
            ->and(new Engine()->run("for (i = 0; i < 3; i++) {\n  break\n  2\n}\nreturn i")->value)->toBe(0)
            ->and(new Engine()->run("n = 0\nfor (i = 0; i < 3; i++) {\n  n++\n  continue\n  2\n}\nreturn n")->value)->toBe(3);
    });
});

describe('source positions', function (): void {
    it('maps errors inside blocks and after multi-line strings to the right line', function (string $script, int $line): void {
        try {
            new Engine()->set('data', [])->run($script);
            $this->fail('Expected exception');
        } catch (EngineException $e) {
            expect($e->line)->toBe($line);
        }
    })->with([
        'if body' => ["if (true) {\n  x = data.missing\n}", 2],
        'else body' => ["if (false) {\n  x = 1\n} else {\n  x = 1\n  x = data.missing\n}", 5],
        'loop body' => ["for (i = 0; i < 1; i++) {\n  x = data.missing\n}", 2],
        'after string with line break' => ["x = 'a\nb'\ny = data.missing", 3],
        'after escaped line break' => ["x = 'a\\nb'\ny = data.missing", 2],
    ]);

    it('keeps control characters in string literals', function (): void {
        expect(new Engine()->evaluate("'a\\nb\\t\"\$x'"))->toBe("a\nb\t\"\$x")
            ->and(new Engine()->evaluate("'a\\\\'"))->toBe('a\\');
    });

    it('reports compile errors without a runtime error prefix', function (): void {
        new Engine()->set('x', 1)->evaluate('x.__toString()');
    })->throws(EngineException::class, "Calling magic method '__toString' is not allowed in line: 1");
});

describe('lexer', function (): void {
    it('rejects invalid UTF-8', function (): void {
        (new Lexer)->tokenize("x = '\xff'");
    })->throws(LexerException::class, 'Unknown character');

    it('does not treat Unicode letters or digits as identifiers or numbers', function (string $script): void {
        (new Lexer)->tokenize($script);
    })->with(['ä = 1', 'x = ٣'])->throws(LexerException::class);
});

describe('renderer', function (): void {
    it('renders scripts that parse back to the same program', function (string $script, string $expected): void {
        $render = static fn (string $source): string => (new PhpScriptRenderer)->traverse((new Parser)->parse((new Lexer)->tokenize($source)));
        $rendered = $render($script);

        expect(trim($rendered))->toBe($expected)
            ->and($render($rendered))->toBe($rendered);
    })->with([
        'double minus' => ['x = - -1', 'x = -(-1);'],
        'minus not' => ['x = -!a', 'x = -!a;'],
        'large float' => ['x = 10000000000000000000000000.0', 'x = 10000000000000000000000000.0;'],
        'plain float' => ['x = 1.5', 'x = 1.5;'],
        'small float' => ['x = 0.00000001', 'x = 0.00000001;'],
        'escaped string' => ["x = 'it\\'s\\n'", "x = 'it\\'s\\n';"],
        'map key' => ["x = {'a\\nb': 1}", "x = {'a\\nb': 1};"],
        'equality and relational' => ['x = a == (b == c)', 'x = a == (b == c);'],
        'relational inside equality' => ['x = a < b == c', 'x = a < b == c;'],
    ]);
});

describe('review round 2', function (): void {
    it('refuses functions that take callbacks as callbacks', function (string $expression): void {
        new Engine()
            ->allow('strtoupper')
            ->allowWithCallbacks('array_map', 'usort', 'array_filter')
            ->evaluate($expression);
    })->with([
        'array_map in array_map' => ['array_map("array_map", ["strtoupper"], [["a"]])'],
        'usort in array_map' => ['array_map("USORT", [[2, 1]])'],
        'array_filter in array_map' => ['array_map("\\\\array_filter", [[1]])'],
    ])->throws(EngineException::class, 'as a callback is not allowed');

    it('never allows functions that call names hidden in arrays or options', function (string $function): void {
        new Engine()->allow($function);
    })->with(['filter_var', 'filter_var_array', 'preg_replace_callback_array', 'call_user_func', 'call_user_func_array', 'unserialize', 'constant'])
        ->throws(InvalidArgumentException::class, 'can never be allowed');

    it('limits the work of comparing values', function (): void {
        $start = microtime(true);

        try {
            new Engine()->run(<<<'SCRIPT'
                a = [1]
                b = [1]
                for (i = 0; i < 30; i++) {
                    a = [a, a]
                    b = [b, b]
                }
                return a == b
                SCRIPT);
            $this->fail('Expected exception');
        } catch (EngineException $e) {
            expect($e->getMessage())->toContain('Values are too large to compare')
                ->and(microtime(true) - $start)->toBeLessThan(2.0);
        }
    });

    it('keeps every control character in string literals', function (): void {
        expect(bin2hex((string) new Engine()->evaluate("'\\a\\b\\f\\v\\0\\x1b\\x7f'")))->toBe('07080c0b001b7f');
    });

    it('refuses methods of closures', function (string $method): void {
        new Engine()
            ->set('f', static fn (): int => 1)
            ->set('other', new HardeningMagic)
            ->evaluate("f.{$method}(other)");
    })->with(['call', 'bindTo', 'bind'])->throws(EngineException::class, 'on a closure');
});

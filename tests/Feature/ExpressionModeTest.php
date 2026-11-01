<?php

use PhpScript\Core\Engine;
use PhpScript\Exceptions\EngineException;

function flowEngine(): Engine
{
    return new Engine()
        ->set('data', [
            'amount' => 150,
            'customer' => ['name' => 'Ada'],
            'tags' => ['a', 'b'],
            'deadline' => new DateTimeImmutable('2026-01-01'),
        ])
        ->set('now', new DateTimeImmutable('2026-06-01'));
}

it('evaluates expressions to typed values', function (string $expression, mixed $expected): void {
    expect(flowEngine()->evaluate($expression))->toBe($expected);
})->with([
    'comparison' => ['data.amount > 100', true],
    'int addition stays int' => ['data.amount + 50', 200],
    'int times float is float' => ['data.amount * 1.5', 225.0],
    'nested dot access on arrays' => ['data.customer.name', 'Ada'],
    'string literal' => ['"flow"', 'flow'],
    'null literal' => ['null', null],
    'boolean literal' => ['true', true],
    'list index' => ['data.tags[1]', 'b'],
    'numeric string stays string' => ['"42"', '42'],
    'string concatenation' => ['"a" + 1', 'a1'],
    'modulo' => ['7 % 3', 1],
    'greater or equal' => ['3 >= 3', true],
    'less or equal' => ['4 <= 3', false],
    'logical and' => ['true && false', false],
    'logical or' => ['false || true', true],
    'date comparison with now' => ['data.deadline < now', true],
    'date method on now' => ["now.format('Y-m-d')", '2026-06-01'],
    'date equality by value' => ['data.deadline == data.deadline.modify("+0 days")', true],
    'strict scalar equality' => ['1 == "1"', false],
    'inequality' => ['1 != 2', true],
]);

it('evaluates list and map literals', function (): void {
    $engine = new Engine;

    expect($engine->evaluate('[1, "two", [3]]'))->toBe([1, 'two', [3]])
        ->and($engine->evaluate('[]'))->toBe([])
        ->and($engine->evaluate('{a: 1, "b c": [2], "1": "x"}'))->toEqual((object) ['a' => 1, 'b c' => [2], '1' => 'x'])
        ->and($engine->evaluate('{}'))->toEqual(new stdClass)
        ->and($engine->evaluate('{a: {b: 2}}.a.b'))->toBe(2)
        ->and($engine->evaluate('{a: 1}["a"]'))->toBe(1)
        ->and(fn (): mixed => $engine->evaluate('{1: "x"}'))->toThrow(EngineException::class, 'Map keys must be identifiers or strings.');
});

it('respects operator precedence and parentheses', function (string $expression, mixed $expected): void {
    expect(new Engine()->evaluate($expression))->toBe($expected);
})->with([
    ['10 - (5 - 3)', 8],
    ['2 * (3 - 1)', 4],
    ['!(1 == 2)', true],
    ['1 + 2 * 3', 7],
    ['(1 + 2) * 3', 9],
    ['-(2 + 3)', -5],
    ['10 - 5 - 3', 2],
    ['true || false && false', true],
    ['1 < 2 && 2 < 3', true],
]);

it('rejects statements in expression mode', function (string $expression): void {
    new Engine()->evaluate($expression);
})->with([
    'echo' => ['echo "x"'],
    'assignment' => ['x = 1'],
    'increment' => ['x++'],
    'incomplete expression' => ['data.amount >'],
    'two expressions' => ['1 2'],
    'empty' => [''],
    'return' => ['return 1'],
])->throws(EngineException::class);

it('reports the position of parse errors', function (): void {
    try {
        new Engine()->evaluate('1 +');
        $this->fail('Expected exception');
    } catch (EngineException $e) {
        expect($e->line)->toBe(1)
            ->and($e->getMessage())->toContain('Unexpected end of input');
    }
});

it('reports lexer errors as engine exceptions', function (): void {
    new Engine()->evaluate('1 # 2');
})->throws(EngineException::class, 'Unknown character');

it('throws for a missing key instead of returning null', function (string $expression, string $message): void {
    expect(fn (): mixed => flowEngine()->evaluate($expression))->toThrow(EngineException::class, $message);
})->with([
    ['data.missing', "Undefined key 'missing'"],
    ['data.tags[5]', "Undefined key '5'"],
    ['now.missing', "Undefined property 'missing'"],
    ['data.amount.value', "Cannot access 'value' on int"],
    ['data.tags[null]', 'Invalid key of type null'],
    ['data[true]', 'Invalid key of type bool'],
    ['"abc"[5]', "Undefined key '5'"],
    ['data.amount[0]', "Cannot access '0' on int"],
    ['[1] + 1', 'Cannot convert array to string'],
]);

it('reads keys from ArrayAccess objects, maps and strings', function (): void {
    $engine = new Engine()->set('items', new ArrayObject(['x' => 1]));

    expect($engine->evaluate('items["x"]'))->toBe(1)
        ->and($engine->evaluate('"abc"[1]'))->toBe('b')
        ->and($engine->evaluate('"abc"[-1]'))->toBe('c')
        ->and(fn (): mixed => $engine->evaluate('items["y"]'))->toThrow(EngineException::class, "Undefined key 'y'");
});

it('concatenates null, booleans and stringables', function (): void {
    $engine = new Engine()->set('value', new class implements Stringable
    {
        public function __toString(): string
        {
            return 'S';
        }
    });

    expect($engine->evaluate('"a" + null + true + false + value + 1.5'))->toBe('a1S1.5');
});

it('evaluates allowed function calls', function (): void {
    expect(new Engine()->allow('strtoupper')->evaluate('strtoupper("a")'))->toBe('A');
});

it('positions runtime errors at the failing script line', function (): void {
    try {
        flowEngine()->run("x = 1\ny = data.missing");
        $this->fail('Expected exception');
    } catch (EngineException $e) {
        expect($e->line)->toBe(2)
            ->and($e->getMessage())->toContain("Undefined key 'missing'");
    }
});

it('compares only values of the same kind', function (): void {
    $engine = new Engine()
        ->set('now', new DateTimeImmutable('2026-03-15'))
        ->set('later', new DateTime('2026-04-01'));

    expect($engine->evaluate('1 < 1.5'))->toBeTrue()
        ->and($engine->evaluate('"a" <= "b"'))->toBeTrue()
        ->and($engine->evaluate('later >= now'))->toBeTrue()
        ->and($engine->evaluate('2 >= 2'))->toBeTrue();
});

it('rejects comparisons of mixed types', function (string $expression, string $message): void {
    expect(fn (): mixed => new Engine()->set('now', new DateTimeImmutable)->evaluate($expression))
        ->toThrow(EngineException::class, $message);
})->with([
    ['"2026-03-01" < now', 'Cannot compare string with DateTimeImmutable'],
    ['now > "2026-03-01"', 'Cannot compare DateTimeImmutable with string'],
    ['"10" > 9', 'Cannot compare string with int'],
    ['null < 1', 'Cannot compare null with int'],
    ['true > false', 'Cannot compare bool with bool'],
    ['[1] < [2]', 'Cannot compare array with array'],
]);

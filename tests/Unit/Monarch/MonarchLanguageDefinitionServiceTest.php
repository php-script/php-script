<?php

declare(strict_types=1);

namespace Tests\Unit\Monarch;

use PhpScript\Core\Engine;
use PhpScript\Monarch\MonarchLanguageDefinitionService;

class MyOtherTestClass
{
    public string $otherProp = 'hello';
}

interface MyTestInterface
{
    public function interfaceMethod(): bool;
}

class MyTestClass implements MyTestInterface
{
    /** This is a public property. */
    public string $publicProp = 'test';

    protected string $protectedProp = 'protected';

    private string $privateProp = 'private';

    /**
     * This is a public method.
     *
     * @param  string  $param1  The first parameter.
     * @return int The return value.
     */
    public function publicMethod(string $param1, bool $param2 = true): int
    {
        return 1;
    }

    /**
     * This method returns another object.
     */
    public function getOtherClass(): MyOtherTestClass
    {
        return new MyOtherTestClass;
    }

    public function methodWithUnionType(string|int $param): void {}

    public function methodWithNullableType(?string $param): void {}

    public function methodWithIntersectionType(): MyTestClass&MyTestInterface
    {
        return $this;
    }

    public function interfaceMethod(): bool
    {
        return true;
    }

    public function __construct() {}
}

function my_test_function(array $items): int
{
    return count($items);
}

it('returns the correct definition', function (): void {
    $service = new MonarchLanguageDefinitionService;
    $definition = $service->getDefinition();

    expect($definition)->toBe([
        'keywords' => [
            'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
        ],
        'allowedFunctions' => [],
        'contextVariables' => [],
        'operators' => [
            '==', '===', '!=', '!==', '=', '.', '+', '-', '*', '/', '>', '<', '~',
        ],
        'symbols' => '[=><!~?&|+\\-*/^%.,;()\\{}\\[\\]]',
        'tokenizer' => [
            'root' => [
                [
                    '[a-zA-Z_]\\w*',
                    [
                        'cases' => [
                            '@keywords' => 'keyword',
                            '@allowedFunctions' => 'predefined',
                            '@contextVariables' => 'variable.predefined',
                            '@default' => 'identifier',
                        ],
                    ],
                ],
                ['include' => '@whitespace'],
                ['[{}()\\[\\]]', '@brackets'],
                ['[<>](?!@symbols)', '@brackets'],
                [
                    '@symbols',
                    [
                        'cases' => [
                            '@operators' => 'operator',
                            '@default' => '',
                        ],
                    ],
                ],
                ['\\d+(\\.\\d+)?', 'number'],
                ['"', 'string.quote', '@string_double'],
                ["'", 'string.quote', '@string_single'],
            ],
            'whitespace' => [
                ['[ \\t\\r\\n]+', ''],
                ['\\/\\/.*$', 'comment'],
            ],
            'string_double' => [
                ['[^\\\\"]+', 'string'],
                ['\\\\.', 'string.escape'],
                ['"', 'string.quote', '@pop'],
            ],
            'string_single' => [
                ["[^\\\\']+", 'string'],
                ['\\\\.', 'string.escape'],
                ["'", 'string.quote', '@pop'],
            ],
        ],
    ]);
});

it('returns the correct definition with allowed functions', function (): void {
    $service = new MonarchLanguageDefinitionService(allowedFunctions: ['test_function']);
    $definition = $service->getDefinition();

    expect($definition['allowedFunctions'])->toBe(['test_function']);
});

it('returns the correct definition with context variables', function (): void {
    $service = new MonarchLanguageDefinitionService(contextVariables: ['test_variable' => 'test_value']);
    $definition = $service->getDefinition();

    expect($definition['contextVariables'])->toBe(['test_variable']);
});

it('returns no undefined versions', function (): void {
    $service = new MonarchLanguageDefinitionService(allowedFunctions: ['test_function']);
    $completionItems = json_encode($service->getCompletionItems());

    expect($completionItems)->not()->toContain('test_function');
});

it('returns the correct completion items with context variables', function (): void {
    $service = new MonarchLanguageDefinitionService(
        contextVariables: ['test_variable' => 'test_value'],
        contextDocumentation: ['test_variable' => 'This is a test variable']
    );
    $completionItems = json_encode($service->getCompletionItems());

    expect($completionItems)->toContain('test_variable')
        ->toContain('This is a test variable');
});

it('returns the correct completion items when an allowed function is added', function (): void {
    $engine = new Engine;
    $engine->allow('count');

    $completionItems = json_encode($engine->monarchLanguageDefinition()->getCompletionItems());

    expect($completionItems)->toContain('count');
});

it('returns the correct completion items when a context variable is set', function (): void {
    $engine = new Engine;
    $engine->set('user', new \stdClass, 'The current user');

    $completionItems = json_encode($engine->monarchLanguageDefinition()->getCompletionItems());

    expect($completionItems)->toContain('user');
});

it('reflects object properties and methods for completion', function () {
    $service = new MonarchLanguageDefinitionService(
        contextVariables: ['myObject' => new MyTestClass],
    );

    $completionItems = $service->getCompletionItems();
    $json = json_encode($completionItems);

    // Check variable
    expect($completionItems['globalVariables'][0]['label'])->toBe('myObject');
    expect($completionItems['globalVariables'][0]['detail'])->toBe(MyTestClass::class);

    // Check class reflection
    $classDef = $completionItems['classes'][MyTestClass::class];
    expect($classDef)->not()->toBeNull();

    // Check public property
    expect($json)->toContain('publicProp');
    expect($json)->toContain('This is a public property.');

    // Check that private/protected properties are not included
    expect($json)->not()->toContain('protectedProp');
    expect($json)->not()->toContain('privateProp');

    // Check public method
    $publicMethod = current(array_filter($classDef['methods'], fn ($method) => $method['label'] === 'publicMethod'));
    expect($publicMethod)->not()->toBeNull();
    expect($publicMethod['detail'])->toBe('publicMethod(string $param1, bool $param2 = ...): int');
    expect($publicMethod['doc'])->toBe('This is a public method.');
    expect($publicMethod['snippet'])->toBe('publicMethod(${1:param1}, ${2:param2})');

    // Check that magic methods are not included
    expect($json)->not()->toContain('__construct');
});

it('recursively reflects return types of methods', function () {
    $service = new MonarchLanguageDefinitionService(
        contextVariables: ['myObject' => new MyTestClass],
    );

    $completionItems = $service->getCompletionItems();

    // MyTestClass should be in classes
    expect(array_key_exists(MyTestClass::class, $completionItems['classes']))->toBeTrue();

    // MyOtherTestClass should also be in classes, because getOtherClass() returns it
    expect(array_key_exists(MyOtherTestClass::class, $completionItems['classes']))->toBeTrue();

    // Check property of MyOtherTestClass
    $otherClassDef = $completionItems['classes'][MyOtherTestClass::class];
    expect($otherClassDef['properties'][0]['label'])->toBe('otherProp');
});

it('handles various type hints correctly', function () {
    $service = new MonarchLanguageDefinitionService(
        contextVariables: ['myObject' => new MyTestClass],
    );

    $completionItems = $service->getCompletionItems();
    $classDef = $completionItems['classes'][MyTestClass::class];

    $unionTypeMethod = current(array_filter($classDef['methods'], fn ($method) => $method['label'] === 'methodWithUnionType'));
    expect($unionTypeMethod['detail'])->toBe('methodWithUnionType(string|int $param): mixed');

    $nullableTypeMethod = current(array_filter($classDef['methods'], fn ($method) => $method['label'] === 'methodWithNullableType'));
    expect($nullableTypeMethod['detail'])->toBe('methodWithNullableType(?string $param): mixed');

    $intersectionTypeMethod = current(array_filter($classDef['methods'], fn ($method) => $method['label'] === 'methodWithIntersectionType'));
    expect($intersectionTypeMethod['detail'])->toBe('methodWithIntersectionType(): Tests\Unit\Monarch\MyTestClass&Tests\Unit\Monarch\MyTestInterface');
});

it('provides completion for allowed global functions', function () {
    $service = new MonarchLanguageDefinitionService(
        allowedFunctions: [__NAMESPACE__ . '\\my_test_function'],
    );

    $completionItems = $service->getCompletionItems();
    $json = json_encode($completionItems);

    expect($json)->toContain('my_test_function');

    $functionDef = $completionItems['globalFunctions'][0];
    expect($functionDef['label'])->toBe('my_test_function');
    expect($functionDef['detail'])->toBe('my_test_function(array $items): int');
    expect($functionDef['snippet'])->toBe('my_test_function(${1:items})');
});

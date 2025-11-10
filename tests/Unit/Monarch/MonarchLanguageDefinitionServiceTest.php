<?php

declare(strict_types=1);

namespace Tests\Unit\Monarch;

use PhpScript\Core\Engine;
use PhpScript\Monarch\MonarchLanguageDefinitionService;

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

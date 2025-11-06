<?php

declare(strict_types=1);

namespace Tests\Unit\Monarch;

use PhpScript\Core\Engine;
use PhpScript\Monarch\CompletionItemKind;
use PhpScript\Monarch\MonarchLanguageDefinitionService;

it('returns the correct definition', function () {
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

it('returns the correct definition with allowed functions', function () {
    $service = new MonarchLanguageDefinitionService(allowedFunctions: ['test_function']);
    $definition = $service->getDefinition();

    expect($definition['allowedFunctions'])->toBe(['test_function']);
});

it('returns the correct definition with context variables', function () {
    $service = new MonarchLanguageDefinitionService(contextVariables: ['test_variable' => 'test_value']);
    $definition = $service->getDefinition();

    expect($definition['contextVariables'])->toBe(['test_variable']);
});

it('returns the correct completion items', function () {
    $service = new MonarchLanguageDefinitionService;
    $completionItems = $service->getCompletionItems();

    $keywords = [
        'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
    ];
    $keywordItems = array_map(static fn (string $keyword) => [
        'label' => $keyword,
        'kind' => CompletionItemKind::Keyword->value,
        'insertText' => $keyword,
        'detail' => 'Keyword',
        'documentation' => '',
    ], $keywords);

    expect($completionItems)->toBe([
        'text' => $keywordItems,
        'keyword' => [],
    ]);
});

it('returns the correct completion items with allowed functions', function () {
    $service = new MonarchLanguageDefinitionService(allowedFunctions: ['test_function']);
    $completionItems = $service->getCompletionItems();

    $keywords = [
        'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
    ];
    $keywordItems = array_map(static fn (string $keyword) => [
        'label' => $keyword,
        'kind' => CompletionItemKind::Keyword->value,
        'insertText' => $keyword,
        'detail' => 'Keyword',
        'documentation' => '',
    ], $keywords);

    expect($completionItems['text'])->toEqual($keywordItems);
    expect($completionItems['keyword'])->toBe([
        [
            'label' => 'test_function',
            'kind' => CompletionItemKind::Function->value,
            'insertText' => 'test_function(${1:condition})',
            'detail' => 'Allowed function',
            'documentation' => '',
        ],
    ]);
});

it('returns the correct completion items with context variables', function () {
    $service = new MonarchLanguageDefinitionService(
        contextVariables: ['test_variable' => 'test_value'],
        contextDocumentation: ['test_variable' => 'This is a test variable']
    );
    $completionItems = $service->getCompletionItems();

    $keywords = [
        'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
    ];
    $keywordItems = array_map(static fn (string $keyword) => [
        'label' => $keyword,
        'kind' => CompletionItemKind::Keyword->value,
        'insertText' => $keyword,
        'detail' => 'Keyword',
        'documentation' => '',
    ], $keywords);

    $variableItems = [
        [
            'label' => 'test_variable',
            'kind' => CompletionItemKind::Variable->value,
            'insertText' => 'test_variable',
            'detail' => 'Context variable',
            'documentation' => 'This is a test variable',
        ],
    ];

    expect($completionItems['text'])->toEqual(array_merge($keywordItems, $variableItems));
    expect($completionItems['keyword'])->toBe([]);
});

it('returns the correct completion items when an allowed function is added', function () {
    $engine = new Engine;
    $engine->allow('count');

    $completionItems = $engine->monarchLanguageDefinition()->getCompletionItems();

    $functionItem = [
        'label' => 'count',
        'kind' => CompletionItemKind::Function->value,
        'insertText' => 'count(${1:condition})',
        'detail' => 'Allowed function',
        'documentation' => '',
    ];

    expect($completionItems['keyword'])->toContain($functionItem);
});

it('returns the correct completion items when a context variable is set', function () {
    $engine = new Engine;
    $engine->set('user', new \stdClass, 'The current user');

    $completionItems = $engine->monarchLanguageDefinition()->getCompletionItems();

    $variableItem = [
        'label' => 'user',
        'kind' => CompletionItemKind::Variable->value,
        'insertText' => 'user',
        'detail' => 'Context variable',
        'documentation' => 'The current user',
    ];

    expect($completionItems['text'])->toContain($variableItem);
});

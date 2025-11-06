<?php

declare(strict_types=1);

namespace PhpScript\Monarch;

final readonly class MonarchLanguageDefinitionService
{
    /** @var string[] */
    private const array KEYWORDS = [
        'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
    ];

    /**
     * @param  string[]  $allowedFunctions
     * @param  array<string, mixed>  $contextVariables
     * @param  array<string, string>  $contextDocumentation
     */
    public function __construct(
        private array $allowedFunctions = [],
        private array $contextVariables = [],
        private array $contextDocumentation = [],
    ) {}

    /**
     * @return array{
     *     keywords: string[],
     *     allowedFunctions: string[],
     *     contextVariables: string[],
     *     operators: string[],
     *     symbols: string,
     *     tokenizer: array<string, mixed>,
     * }
     */
    public function getDefinition(): array
    {
        return [
            'keywords' => self::KEYWORDS,
            'allowedFunctions' => array_values($this->allowedFunctions),
            'contextVariables' => array_keys($this->contextVariables),
            'operators' => [
                '==', '===', '!=', '!==', '=', '.', '+', '-', '*', '/', '>', '<', '~',
            ],
            'symbols' => '[=><!~?&|+\-*/^%.,;()\{}\[\]]',
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
                    ['[{}()\[\]]', '@brackets'],
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
        ];
    }

    /**
     * @return array{
     *     text: non-empty-list<array{label: string, kind: int, insertText: string, detail: string, documentation: string}>,
     *     keyword: array<array{label: string, kind: int, insertText: non-falsy-string, detail: string, documentation: string}>
     * }
     */
    public function getCompletionItems(): array
    {
        $functionItems = array_map(fn (string $functionName): array => [
            'label' => $functionName,
            'kind' => CompletionItemKind::Function->value,
            'insertText' => $functionName . '(${1:condition})',
            'detail' => 'Allowed function',
            'documentation' => '',
        ], $this->allowedFunctions);

        $variableItems = array_map(fn (string $variableName): array => [
            'label' => $variableName,
            'kind' => CompletionItemKind::Variable->value,
            'insertText' => $variableName,
            'detail' => 'Context variable',
            'documentation' => $this->contextDocumentation[$variableName] ?? '',
        ], array_keys($this->contextVariables));

        $keywordItems = array_map(static fn (string $keyword): array => [
            'label' => $keyword,
            'kind' => CompletionItemKind::Keyword->value,
            'insertText' => $keyword,
            'detail' => 'Keyword',
            'documentation' => '',
        ], self::KEYWORDS);

        return [
            'text' => array_merge($keywordItems, $variableItems),
            'keyword' => $functionItems,
        ];
    }
}

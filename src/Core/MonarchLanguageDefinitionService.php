<?php

declare(strict_types=1);

namespace PhpScript\Core;

final readonly class MonarchLanguageDefinitionService
{
    /**
     * @param string[] $allowedFunctions
     * @param array<string, mixed> $contextVariables
     */
    public function __construct(private array $allowedFunctions = [], private array $contextVariables = []) {
    }

    public function getDefinition(): array
    {
        return [
            'keywords' => [
                'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
            ],
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
                    ["\\\\.", 'string.escape'],
                    ["'", 'string.quote', '@pop'],
                ],
            ],
        ];
    }

}

<?php

declare(strict_types=1);

namespace PhpScript\Monarch;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;

final class MonarchLanguageDefinitionService
{
    /** @var string[] */
    private const array KEYWORDS = [
        'if', 'else', 'for', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
    ];

    /**
     * Loop control keywords that should only be suggested inside loops.
     * These are provided separately in the loopControls completion category.
     *
     * @var string[]
     */
    private const array LOOP_KEYWORDS = [
        'break', 'continue',
    ];

    /** @var array<string, bool> */
    private array $reflectionCache = [];

    /**
     * @param  string[]  $allowedFunctions
     * @param  array<string, mixed>  $contextVariables
     * @param  array<string, string>  $contextDocumentation
     */
    public function __construct(
        private readonly array $allowedFunctions = [],
        private readonly array $contextVariables = [],
        private readonly array $contextDocumentation = [],
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
            'allowedFunctions' => array_map(function (string $fqn): string {
                try {
                    return new ReflectionFunction($fqn)->getShortName();
                } catch (ReflectionException) {
                    return $fqn;
                }
            }, $this->allowedFunctions),
            'contextVariables' => array_keys($this->contextVariables),
            'operators' => [
                '==', '===', '!=', '!==', '=', '.', '+', '-', '*', '/', '>', '<',
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
        ];
    }

    /**
     * @return array{
     *     globalFunctions: list<array{label: string, kind: string, detail: string, doc: string, snippet: string}>,
     *     globalVariables: list<array{label: string, kind: 'Variable', detail: mixed, doc: string}>,
     *     classes: array<string, array{
     *         properties: list<array{label: string, kind: 'Property', detail: string, doc: string}>,
     *         methods: list<array{label: string, kind: string, detail: string, doc: string, snippet: string}>
     *     }>,
     *     controlFlows: list<array{label: string, kind: 'Snippet', detail: string, doc: string, snippet: string}>,
     *     loopControls: list<array{label: string, kind: 'Snippet', detail: string, doc: string, snippet: string}>,
     *     loopKeywords: list<string>
     * }
     */
    public function getCompletionItems(): array
    {
        $this->reflectionCache = [];
        $model = [
            'globalFunctions' => [],
            'globalVariables' => [],
            'classes' => [],
            'controlFlows' => [],
            'loopControls' => [],
            'loopKeywords' => self::LOOP_KEYWORDS,
        ];

        // 1. Erlaubte globale Funktionen (Whitelist)
        foreach ($this->allowedFunctions as $funcName) {
            try {
                $model['globalFunctions'][] = $this->formatFunctionSuggestion(new ReflectionFunction($funcName));
            } catch (ReflectionException) {
            }
        }

        // 2. Globale Kontext-Variablen (set)
        foreach ($this->contextVariables as $name => $value) {
            $type = gettype($value);

            $detailType = $type;

            // 3. Klassen-Definitionen rekursiv analysieren
            if (is_object($value)) {
                $detailType = $value::class;
                $this->reflectClass($value::class, $model['classes']);
            }

            $model['globalVariables'][] = [
                'label' => $name,
                'kind' => 'Variable',
                'detail' => $detailType,
                'doc' => $this->parseDocComment($name),
            ];
        }

        // 3. Control Flow: for, foreach, if, ifelse
        $model['controlFlows'][] = [
            'label' => 'for',
            'kind' => 'Snippet',
            'snippet' => implode("\n", [
                'for (${1:i} = ${2:0}; ${1} < ${3:count}(${4}); ${1}++) {',
                '    $0',
                '}',
            ]),
            'doc' => 'For-Loop',
            'detail' => 'for (int $i = 0; $i < count($items); $i++) {',
        ];
        $model['controlFlows'][] = [
            'label' => 'foreach',
            'kind' => 'Snippet',
            'snippet' => implode("\n", [
                'foreach (${1:items} as ${2:item}) {',
                '    $0',
                '}',
            ]),
            'doc' => 'Foreach-Loop',
            'detail' => 'foreach ($items as $item) {',
        ];
        $model['controlFlows'][] = [
            'label' => 'foreach (key, value)',
            'kind' => 'Snippet',
            'snippet' => implode("\n", [
                'foreach (${1:map} as ${2:key}, ${3:value}) {',
                '    $0',
                '}',
            ]),
            'doc' => 'Foreach-Loop for Key/Value Pairs',
            'detail' => 'foreach ($map as $key => $value) {',
        ];
        $model['controlFlows'][] = [
            'label' => 'if',
            'kind' => 'Snippet',
            'snippet' => implode("\n", [
                'if (${1:condition}) {',
                '    $0',
                '}',
            ]),
            'doc' => 'If-Condition',
            'detail' => 'if ($condition) {',
        ];
        $model['controlFlows'][] = [
            'label' => 'ifelse',
            'kind' => 'Snippet',
            'snippet' => implode("\n", [
                'if (${1:condition}) {',
                '    $2',
                '} else {',
                '    $0',
                '}',
            ]),
            'doc' => 'If-Else-Condition',
            'detail' => 'if ($condition) { ... } else { ... }',
        ];

        // 4. Loop Controls: break, continue (only show inside loops)
        $model['loopControls'][] = [
            'label' => 'break 2',
            'kind' => 'Snippet',
            'snippet' => 'break ${1:2};',
            'doc' => 'Break from nested loops',
            'detail' => 'break N;',
        ];
        $model['loopControls'][] = [
            'label' => 'continue 2',
            'kind' => 'Snippet',
            'snippet' => 'continue ${1:2};',
            'doc' => 'Continue outer loop from nested loops',
            'detail' => 'continue N;',
        ];

        return $model;
    }

    /**
     * Analysiert eine Klasse und fügt sie (und alle Kind-Klassen)
     * dem 'classes'-Modell hinzu.
     *
     * @param  class-string  $className
     * @param  array<string, array{properties: list<array{label: string, kind: 'Property', detail: string, doc: string}>, methods: list<array{label: string, kind: string, detail: string, doc: string, snippet: string}>}>  $classesModel
     */
    private function reflectClass(string $className, array &$classesModel): void
    {
        if (isset($this->reflectionCache[$className])) {
            return;
        }
        $this->reflectionCache[$className] = true;

        // Check if the class exists before attempting to reflect it
        if (! class_exists($className)) {
            return;
        }

        $refClass = new ReflectionClass($className);

        $classDef = [
            'properties' => [],
            'methods' => [],
        ];

        // Public Properties
        foreach ($refClass->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $this->reflectType($prop->getType(), $classesModel);
            $classDef['properties'][] = [
                'label' => $prop->getName(),
                'kind' => 'Property',
                'detail' => $this->parseTypeHint($prop->getType()),
                'doc' => $this->parseDocComment($prop->getDocComment()),
            ];
        }

        // Public Methods
        foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor() || str_starts_with($method->getName(), '__')) {
                // @codeCoverageIgnoreStart
                continue;
                // @codeCoverageIgnoreEnd
            }
            $classDef['methods'][] = $this->formatFunctionSuggestion($method);

            // Rückgabetyp ebenfalls rekursiv analysieren
            $this->reflectType($method->getReturnType(), $classesModel);
        }

        $classesModel[$className] = $classDef;
    }

    /**
     * @param  array<string, array{properties: list<array{label: string, kind: 'Property', detail: string, doc: string}>, methods: list<array{label: string, kind: string, detail: string, doc: string, snippet: string}>}>  $classesModel
     */
    private function reflectType(?ReflectionType $type, array &$classesModel): void
    {
        if (! $type instanceof ReflectionType) {
            return;
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $innerType) {
                $this->reflectType($innerType, $classesModel);
            }

            return;
        }

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $typeName = $type->getName();
            /** @var class-string $typeName */
            $this->reflectClass($typeName, $classesModel);
        }
    }

    /**
     * Formatiert eine Reflection-Funktion/-Methode in ein Suggestion-Objekt.
     *
     * @return array{label: string, kind: string, detail: string, doc: string, snippet: string}
     */
    private function formatFunctionSuggestion(ReflectionFunctionAbstract $ref): array
    {
        $params = [];
        $snippetParams = []; // Für Snippet-Generierung
        $paramIndex = 1;

        foreach ($ref->getParameters() as $param) {
            $paramType = $this->parseTypeHint($param->getType());
            $paramName = '$' . $param->getName();
            $paramStr = $paramType . ' ' . $paramName;

            $snippetParam = '${' . ($paramIndex++) . ':' . $param->getName() . '}';

            if ($param->isDefaultValueAvailable()) {
                $paramStr .= ' = ...'; // Standardwert andeuten
                // Optional: Snippet für optionale Parameter anders behandeln (hier nicht implementiert)
            }
            $params[] = $paramStr;
            $snippetParams[] = $snippetParam;
        }

        $paramSignature = implode(', ', $params);
        $snippetSignature = implode(', ', $snippetParams);
        $returnType = $this->parseTypeHint($ref->getReturnType());
        $label = $ref->getShortName();

        return [
            'label' => $label,
            'kind' => $ref instanceof ReflectionMethod ? 'Method' : 'Function',
            'detail' => "$label($paramSignature): $returnType",
            'doc' => $this->parseDocComment($ref->getDocComment()),
            'snippet' => $label . '(' . $snippetSignature . ')', // Snippet hinzufügen
        ];
    }

    /**
     * Liest den Typ aus einem ReflectionType (kann ?string, string|int, etc. sein).
     */
    private function parseTypeHint(?ReflectionType $type): string
    {
        if (! $type instanceof ReflectionType) {
            return 'mixed';
        }

        $name = '';
        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
        } elseif ($type instanceof ReflectionUnionType) {
            $types = array_map($this->parseTypeHint(...), $type->getTypes());
            $name = implode('|', $types);
        } elseif ($type instanceof ReflectionIntersectionType) {
            $types = array_map($this->parseTypeHint(...), $type->getTypes());
            $name = implode('&', $types);
        }

        return ($type->allowsNull() && $name !== 'mixed') ? "?$name" : $name;
    }

    /**
     * Extrahiert die erste Zeile eines Doc-Kommentars.
     */
    private function parseDocComment(string|false $doc): string
    {
        if ($doc === false || ($doc === '' || $doc === '0')) {
            return '';
        }

        if (array_key_exists($doc, $this->contextDocumentation)) {
            return $this->contextDocumentation[$doc];
        }

        $doc = preg_replace('/[\t ]*(\*\/|\/\*\*|\* ?)/', '', $doc);
        $lines = explode("\n", (string) $doc);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && $line !== '0' && ! str_starts_with($line, '@')) {
                return $line;
            }
        }

        return '';
    }
}

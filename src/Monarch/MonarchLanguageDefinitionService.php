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
        'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
    ];

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

    public function getCompletionItems(): array
    {
        $this->reflectionCache = [];
        $model = [
            'globalFunctions' => [],
            'globalVariables' => [],
            'classes' => [],
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
            $className = ($type === 'object') ? get_class($value) : $type;

            $model['globalVariables'][] = [
                'label' => $name,
                'kind' => 'Variable',
                'detail' => $className,
                'doc' => $this->parseDocComment($name),
            ];

            // 3. Klassen-Definitionen rekursiv analysieren
            if ($type === 'object') {
                $this->reflectClass($className, $model['classes']);
            }
        }

        return $model;
    }

    /**
     * Analysiert eine Klasse und fügt sie (und alle Kind-Klassen)
     * dem 'classes'-Modell hinzu.
     */
    private function reflectClass(string $className, array &$classesModel)
    {
        if (isset($this->reflectionCache[$className])) {
            return;
        }
        $this->reflectionCache[$className] = true;

        try {
            $refClass = new ReflectionClass($className);
        } catch (ReflectionException) {
            return;
        }

        $classDef = [
            'properties' => [],
            'methods' => [],
        ];

        // Public Properties
        foreach ($refClass->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
            $type = $this->parseTypeHint($prop->getType());
            $classDef['properties'][] = [
                'label' => $prop->getName(),
                'kind' => 'Property',
                'detail' => $type,
                'doc' => $this->parseDocComment($prop->getDocComment()),
            ];
        }

        // Public Methods
        foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor() || $method->isDestructor() || str_starts_with($method->getName(), '__')) {
                continue;
            }
            $classDef['methods'][] = $this->formatFunctionSuggestion($method);

            // Rückgabetyp ebenfalls rekursiv analysieren
            $returnType = $method->getReturnType();
            if ($returnType && ! $returnType->isBuiltin()) {
                $returnTypeName = $this->parseTypeHint($returnType);
                $cleanClassName = ltrim($returnTypeName, '?');

                // Verhindern, dass wir versuchen, 'mixed' oder 'string|int' als Klasse zu reflektieren
                if (! empty($cleanClassName) && $cleanClassName !== 'mixed' && ! str_contains($cleanClassName, '|') && ! str_contains($cleanClassName, '&')) {
                    $this->reflectClass($cleanClassName, $classesModel);
                }
            }
        }

        $classesModel[$className] = $classDef;
    }

    /**
     * Formatiert eine Reflection-Funktion/-Methode in ein Suggestion-Objekt.
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
        $label = $ref->getName();

        return [
            'label' => $label,
            'kind' => $ref instanceof ReflectionMethod ? 'Method' : 'Function',
            // 'insertText' => $label . '()', // Veraltet, Snippet ist besser
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
        if ($type === null) {
            return 'mixed';
        }

        $name = '';
        if ($type instanceof ReflectionNamedType) {
            $name = $type->getName();
        } elseif ($type instanceof ReflectionUnionType) {
            $name = implode('|', $type->getTypes());
        } elseif ($type instanceof ReflectionIntersectionType) {
            $name = implode('&', $type->getTypes());
        }

        return ($type->allowsNull() && $name !== 'mixed') ? "?$name" : $name;
    }

    /**
     * Extrahiert die erste Zeile eines Doc-Kommentars.
     */
    private function parseDocComment(string|false $doc): string
    {
        if (empty($doc)) {
            return '';
        }

        if ($doc !== false && array_key_exists($doc, $this->contextDocumentation)) {
            return $this->contextDocumentation[$doc];
        }

        $doc = preg_replace('/[\t ]*(\*\/|\/\*\*|\* ?)/', '', $doc);
        $lines = explode("\n", $doc);
        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line) && ! str_starts_with($line, '@')) {
                return $line;
            }
        }

        return '';
    }
}

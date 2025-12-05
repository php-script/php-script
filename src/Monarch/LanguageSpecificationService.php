<?php

declare(strict_types=1);

namespace PhpScript\Monarch;

final class LanguageSpecificationService
{
    public function __construct(
        private readonly MonarchLanguageDefinitionService $monarchLanguageDefinitionService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getSpecification(): array
    {
        $definition = $this->monarchLanguageDefinitionService->getDefinition();
        $completionItems = $this->monarchLanguageDefinitionService->getCompletionItems();

        return [
            'name' => 'php-script',
            'version' => '1.0.0',
            'keywords' => $definition['keywords'],
            'operators' => $definition['operators'],
            'symbols' => $definition['symbols'],
            'tokenizer' => $definition['tokenizer'],
            'completionItems' => $completionItems,
        ];
    }
}

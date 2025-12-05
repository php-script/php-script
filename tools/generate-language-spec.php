<?php

declare(strict_types=1);

use PhpScript\Monarch\LanguageSpecificationService;
use PhpScript\Monarch\MonarchLanguageDefinitionService;

require __DIR__.'/../vendor/autoload.php';

$monarchLanguageDefinitionService = new MonarchLanguageDefinitionService();
$languageSpecificationService = new LanguageSpecificationService($monarchLanguageDefinitionService);

$specification = $languageSpecificationService->getSpecification();

$json = json_encode($specification, JSON_PRETTY_PRINT);

file_put_contents(__DIR__.'/../docs/language-spec.json', $json);

echo "Language specification generated successfully.\n";

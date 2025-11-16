<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

interface AstTraverserInterface
{
    /**
     * @param  string[]  $allowedFunctions
     */
    public function setAllowedFunctions(array $allowedFunctions): void;

    public function traverse(Node $node): string;

    /**
     * @return \PhpScript\Core\Token[]
     */
    public function getSourceMap(): array;
}

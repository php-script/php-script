<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

interface AstTraverserInterface
{
    public function traverse(Node $node): string;
}

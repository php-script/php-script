<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

use PhpScript\Ast\BreakStatement;
use PhpScript\Ast\ContinueStatement;

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

    public function visitBreakStatement(BreakStatement $node): string;

    public function visitContinueStatement(ContinueStatement $node): string;
}

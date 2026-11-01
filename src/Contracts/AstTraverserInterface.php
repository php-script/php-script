<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

use PhpScript\Ast\BreakStatement;
use PhpScript\Ast\ContinueStatement;
use PhpScript\Core\Token;

interface AstTraverserInterface
{
    /**
     * @param  string[]  $allowedFunctions
     */
    public function setAllowedFunctions(array $allowedFunctions): void;

    /**
     * Emits operation counting into the generated code (see ExecutionBudget).
     */
    public function setBudgetTracking(bool $enabled): void;

    public function traverse(Node $node): string;

    /**
     * @return Token[]
     */
    public function getSourceMap(): array;

    public function visitBreakStatement(BreakStatement $node): string;

    public function visitContinueStatement(ContinueStatement $node): string;
}

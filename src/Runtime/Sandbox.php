<?php

declare(strict_types=1);

namespace PhpScript\Runtime;

/**
 * Runs compiled script code in an isolated function scope: no `$this`, no engine
 * internals, only the context variables. Superglobals are blocked at compile time.
 */
final class Sandbox
{
    /**
     * The generated code reads `$__ps_budget` and `$__ps_guard` from this scope.
     *
     * @param  array<string, mixed>  $__ps_context
     *
     * @throws \PhpScript\Exceptions\BudgetExceededException
     */
    public static function run(string $__ps_file, array $__ps_context, ExecutionBudget $__ps_budget, CallGuard $__ps_guard): mixed
    {
        extract($__ps_context, EXTR_SKIP);
        unset($__ps_context);

        $__ps_value = include $__ps_file;

        // The last statement, e.g. `return str_repeat(…)`, has no tick after it.
        $__ps_budget->checkMemory();

        return $__ps_value;
    }
}

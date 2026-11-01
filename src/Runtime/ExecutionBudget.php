<?php

declare(strict_types=1);

namespace PhpScript\Runtime;

use PhpScript\Exceptions\BudgetExceededException;

/**
 * Counts operations (statements and loop iterations) of one script run and
 * aborts the script when a limit is reached. A limit of 0 means unlimited.
 *
 * The memory check is best effort: it measures the growth of the process
 * memory since the run started.
 */
final class ExecutionBudget
{
    private int $operations = 0;

    private readonly int $memoryBaseline;

    public function __construct(
        private readonly int $operationLimit = 0,
        private readonly int $memoryLimit = 0,
    ) {
        $this->memoryBaseline = memory_get_usage();
    }

    /**
     * @throws BudgetExceededException
     */
    public function tick(): void
    {
        $this->operations++;

        if ($this->operationLimit > 0 && $this->operations > $this->operationLimit) {
            throw BudgetExceededException::operationLimit($this->operationLimit);
        }

        $this->checkMemory();
    }

    /**
     * Fails before a value of `$bytes` is built, e.g. a long concatenation that
     * would blow the memory limit within a single statement.
     *
     * @throws BudgetExceededException
     */
    public function reserve(int $bytes): void
    {
        $this->checkMemory($bytes);
    }

    /**
     * @throws BudgetExceededException
     */
    public function checkMemory(int $additionalBytes = 0): void
    {
        if ($this->memoryLimit > 0 && memory_get_usage() - $this->memoryBaseline + $additionalBytes > $this->memoryLimit) {
            throw BudgetExceededException::memoryLimit($this->memoryLimit);
        }
    }

    public function operations(): int
    {
        return $this->operations;
    }
}

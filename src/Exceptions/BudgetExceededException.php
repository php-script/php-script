<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

/**
 * Thrown when a script exceeds its operation or memory budget.
 * Only the script is aborted; the PHP process keeps running.
 */
final class BudgetExceededException extends EngineException
{
    public static function operationLimit(int $limit): self
    {
        return new self(sprintf('Operation limit of %d exceeded', $limit));
    }

    public static function memoryLimit(int $limit): self
    {
        return new self(sprintf('Memory limit of %d bytes exceeded', $limit));
    }

    public static function timeLimit(int $seconds): self
    {
        return new self(sprintf('Maximum execution time of %d second(s) exceeded', $seconds));
    }

    public function at(int $line, int $column, int $offset, int $length): self
    {
        return new self(
            sprintf('%s in line: %d, column: %d, offset: %d', $this->getMessage(), $line, $column, $offset),
            $line,
            $column,
            $offset,
            $length,
            $this->getPrevious(),
        );
    }
}

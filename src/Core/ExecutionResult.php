<?php

declare(strict_types=1);

namespace PhpScript\Core;

/**
 * The result of Engine::run(): the echo output and the value of `return`.
 */
final readonly class ExecutionResult
{
    public function __construct(
        public string $output,
        public mixed $value,
        public int $operations,
    ) {}
}

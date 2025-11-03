<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

interface Node
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

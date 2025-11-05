<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

use PhpScript\Core\Token;

interface Node
{
    public function getToken(): ?Token;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}

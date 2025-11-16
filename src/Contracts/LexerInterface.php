<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

interface LexerInterface
{
    /**
     * @return list<\PhpScript\Core\Token>
     */
    public function tokenize(string $script): array;
}

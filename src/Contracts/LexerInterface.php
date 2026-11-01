<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

use PhpScript\Core\Token;

interface LexerInterface
{
    /**
     * @return list<Token>
     */
    public function tokenize(string $script): array;
}

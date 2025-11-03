<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

interface ParserInterface
{
    /**
     * @param  list<\PhpScript\Core\Token>  $tokens
     */
    public function parse(array $tokens): Node;
}

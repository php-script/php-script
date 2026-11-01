<?php

declare(strict_types=1);

namespace PhpScript\Contracts;

use PhpScript\Core\Token;

interface ParserInterface
{
    /**
     * @param  list<Token>  $tokens
     */
    public function parse(array $tokens): Node;

    /**
     * Parses exactly one side-effect free expression.
     *
     * @param  list<Token>  $tokens
     */
    public function parseExpression(array $tokens): Node;
}

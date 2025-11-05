<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

class AstTraverserException extends \RuntimeException
{
    public static function unknownNodeType(string $nodeClass): self
    {
        return new self(sprintf('Unknown node type: %s', $nodeClass));
    }

    public static function unknownOperator(string $operator): self
    {
        return new self(sprintf('Unknown operator: %s', $operator));
    }

    /**
     * @codeCoverageIgnore
     */
    public static function unknownLiteralType(string $type): self
    {
        return new self(sprintf('Unknown literal type: %s', $type));
    }
}

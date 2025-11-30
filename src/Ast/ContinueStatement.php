<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\AstTraverserInterface;
use PhpScript\Core\Token;

final readonly class ContinueStatement extends BaseNode
{
    public function __construct(
        public int $level = 1,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function accept(AstTraverserInterface $traverser): string
    {
        return $traverser->visitContinueStatement($this);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'level' => $this->level,
        ];
    }
}

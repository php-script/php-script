<?php

declare(strict_types=1);

namespace Tests\Support;

use PhpScript\Core\AstTraverser;

final class TestAstTraverser extends AstTraverser
{
    /**
     * Set loop depth for testing purposes only
     * Uses reflection to access private property
     */
    public function setLoopDepthForTesting(int $depth): void
    {
        $property = new \ReflectionProperty(AstTraverser::class, 'loopDepth');
        $property->setValue($this, $depth);
    }
}

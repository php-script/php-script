<?php

declare(strict_types=1);

namespace PhpScript\Runtime;

/**
 * Tells the SIGALRM handler whether the script is still running. A signal that
 * arrives during cleanup must not throw, or the cleanup would be skipped.
 */
final class Alarm
{
    private bool $armed = false;

    public function arm(): void
    {
        $this->armed = true;
    }

    public function disarm(): void
    {
        $this->armed = false;
    }

    public function isArmed(): bool
    {
        return $this->armed;
    }
}

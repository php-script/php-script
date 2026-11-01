<?php

declare(strict_types=1);

namespace PhpScript\Exceptions;

use RuntimeException;

/**
 * Thrown by the script runtime while a script runs, e.g. for a missing key.
 * The engine converts it into an EngineException with the script position.
 */
final class ScriptRuntimeException extends RuntimeException
{
    public static function undefinedKey(string|int $key): self
    {
        return new self(sprintf("Undefined key '%s'", $key));
    }

    public static function undefinedProperty(string $property, object $object): self
    {
        return new self(sprintf("Undefined property '%s' on %s", $property, $object::class));
    }

    public static function notAccessible(string|int $key, mixed $target): self
    {
        return new self(sprintf("Cannot access '%s' on %s", $key, get_debug_type($target)));
    }

    public static function forbiddenCallback(mixed $value): self
    {
        $name = is_string($value) ? sprintf("'%s'", $value) : get_debug_type($value);

        return new self(sprintf('Passing %s as a callback is not allowed. Only allowed functions can be used as callbacks.', $name));
    }

    public static function invalidKey(mixed $key): self
    {
        return new self(sprintf('Invalid key of type %s', get_debug_type($key)));
    }
}

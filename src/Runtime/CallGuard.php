<?php

declare(strict_types=1);

namespace PhpScript\Runtime;

use Closure;
use PhpScript\Exceptions\ScriptRuntimeException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Checks what a script passes to PHP code that may call it.
 *
 * A script can name any PHP function in a string (`'shell_exec'`) or build a
 * static callable (`'Class::method'`, `[object, 'method']`). Passed to a
 * function like `array_map` or a method like `Collection::map`, PHP would call
 * it. The guard only lets through names of allowed functions and closures
 * (which scripts cannot create, so they come from the host).
 */
final class CallGuard
{
    /**
     * Allowed functions and whether they may be passed as callbacks.
     *
     * @var array<string, bool>
     */
    private array $functions = [];

    /** @var array<string, array{list<Signature::CALLBACK|Signature::LOOSE|null>, bool}> */
    private array $signatures = [];

    /**
     * @param  string[]  $allowedFunctions
     */
    public function __construct(array $allowedFunctions = [])
    {
        foreach ($allowedFunctions as $function) {
            $name = $this->normalize($function);
            // A function that calls its own arguments (array_map, usort, …) would call
            // whatever the script passes next to it, without this guard seeing it.
            $this->functions[$name] = ! function_exists($name) || ! Signature::takesCallback(new ReflectionFunction($name));
        }
    }

    /**
     * Checks an argument for a `callable` parameter of an allowed function.
     *
     * @throws ScriptRuntimeException
     */
    public function callback(mixed $value): mixed
    {
        if (is_string($value) && ! str_contains($value, '::') && ($this->functions[$this->normalize($value)] ?? false)) {
            return $value;
        }

        if (is_object($value)) {
            return $value;
        }

        if (is_string($value) || is_array($value)) {
            throw ScriptRuntimeException::forbiddenCallback($value);
        }

        return $value;
    }

    /**
     * Calls `$object->method(...$arguments)`. Static methods are refused (an
     * instance would give access to every static factory of the class), and the
     * arguments are checked like callbacks where the method may call them.
     *
     * @throws ScriptRuntimeException
     */
    public function call(mixed $object, string $method, mixed ...$arguments): mixed
    {
        if (! is_object($object)) {
            throw new ScriptRuntimeException(sprintf("Cannot call method '%s' on %s", $method, get_debug_type($object)));
        }

        if ($object instanceof Closure) {
            // call(), bind() and bindTo() would run host code with another `$this`.
            throw new ScriptRuntimeException(sprintf("Cannot call method '%s' on a closure", $method));
        }

        $signature = $this->signature($object, $method);

        foreach (array_values($arguments) as $position => $argument) {
            match (Signature::at($signature, $position)) {
                Signature::CALLBACK => $this->callback($argument),
                Signature::LOOSE => $this->loose($argument),
                default => null,
            };
        }

        return $object->{$method}(...$arguments);
    }

    /**
     * An untyped parameter may still be used as a callable. Plain strings pass,
     * because they are mostly data (`where('date', …)`); static callables do not.
     *
     * @throws ScriptRuntimeException
     */
    private function loose(mixed $value): void
    {
        if (is_string($value) && str_contains($value, '::')) {
            throw ScriptRuntimeException::forbiddenCallback($value);
        }

        if (is_array($value) && count($value) === 2 && array_is_list($value)
            && (is_object($value[0]) || is_string($value[0])) && is_string($value[1])
            && is_callable($value)) {
            throw ScriptRuntimeException::forbiddenCallback($value);
        }
    }

    /**
     * @return array{list<Signature::CALLBACK|Signature::LOOSE|null>, bool}
     *
     * @throws ScriptRuntimeException
     */
    private function signature(object $object, string $method): array
    {
        $key = $object::class . '::' . strtolower($method);

        if (isset($this->signatures[$key])) {
            return $this->signatures[$key];
        }

        if (method_exists($object, $method)) {
            $reflection = new ReflectionMethod($object, $method);

            if ($reflection->isStatic()) {
                throw new ScriptRuntimeException(sprintf("Calling static method '%s' is not allowed", $method));
            }

            if ($reflection->isPublic()) {
                return $this->signatures[$key] = Signature::of($reflection);
            }
        }

        if (! method_exists($object, '__call')) {
            throw new ScriptRuntimeException(sprintf('Call to undefined method %s::%s()', $object::class, $method));
        }

        // A magic method can do anything with its arguments.
        return $this->signatures[$key] = [[Signature::LOOSE], true];
    }

    private function normalize(string $function): string
    {
        return strtolower(ltrim($function, '\\'));
    }
}

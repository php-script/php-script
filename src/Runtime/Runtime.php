<?php

declare(strict_types=1);

namespace PhpScript\Runtime;

use ArrayAccess;
use DateTimeInterface;
use PhpScript\Exceptions\ScriptRuntimeException;
use Stringable;

/**
 * Helpers called by the generated PHP code. Scripts cannot call them directly,
 * because the language has no syntax for static calls.
 */
final class Runtime
{
    /**
     * Path segment kinds for assign() and increment(): `a.b` and `a[b]`.
     */
    public const int MEMBER = 0;

    public const int INDEX = 1;

    private const int MAX_COMPARE_DEPTH = 64;

    /**
     * Lists that contain the same list twice grow exponentially without using memory,
     * so `==` also limits how many values it compares.
     */
    private const int MAX_COMPARE_VALUES = 100_000;

    /**
     * Reads `target.key`: a key of an array or a property of an object.
     * A missing key or property is an error, never a silent null.
     *
     * @throws ScriptRuntimeException
     */
    public static function member(mixed $target, string $key): mixed
    {
        if (is_array($target)) {
            return self::arrayValue($target, $key);
        }

        if (is_object($target)) {
            // Objects with __get (e.g. Eloquent models) report attributes that are null as not set.
            if (! property_exists($target, $key) && ! isset($target->{$key}) && ! method_exists($target, '__get')) {
                throw ScriptRuntimeException::undefinedProperty($key, $target);
            }

            return $target->{$key};
        }

        throw ScriptRuntimeException::notAccessible($key, $target);
    }

    /**
     * Reads `target[key]` from an array, an ArrayAccess object, a map (stdClass) or a string.
     *
     * @throws ScriptRuntimeException
     */
    public static function index(mixed $target, mixed $key): mixed
    {
        if (! is_string($key) && ! is_int($key)) {
            throw ScriptRuntimeException::invalidKey($key);
        }

        if (is_array($target)) {
            return self::arrayValue($target, $key);
        }

        if ($target instanceof ArrayAccess) {
            if (! $target->offsetExists($key)) {
                throw ScriptRuntimeException::undefinedKey($key);
            }

            return $target->offsetGet($key);
        }

        if (is_object($target)) {
            return self::member($target, (string) $key);
        }

        if (is_string($target) && is_int($key)) {
            if ($key >= strlen($target) || $key < -strlen($target)) {
                throw ScriptRuntimeException::undefinedKey($key);
            }

            return $target[$key];
        }

        throw ScriptRuntimeException::notAccessible($key, $target);
    }

    /**
     * Writes `root.a[b] = value`. Every key on the way must exist; only the last
     * one may be new. Objects are written in place, arrays through a reference.
     *
     * @param  list<array{self::MEMBER|self::INDEX, mixed}>  $path
     *
     * @throws ScriptRuntimeException
     */
    public static function assign(mixed &$root, array $path, mixed $value): mixed
    {
        $target = &$root;
        $last = array_pop($path);

        foreach ($path as [$kind, $key]) {
            $target = &self::child($target, $kind, $key);
        }

        if ($last === null) {
            $target = $value;

            return $value;
        }

        self::write($target, $last[0], $last[1], $value);

        return $value;
    }

    /**
     * `root.a++` and `root.a--`: only numbers can be incremented. Returns the old value.
     *
     * @param  list<array{self::MEMBER|self::INDEX, mixed}>  $path
     *
     * @throws ScriptRuntimeException
     */
    public static function increment(mixed &$root, array $path, int $delta): int|float
    {
        $current = $root;
        foreach ($path as [$kind, $key]) {
            $current = $kind === self::INDEX ? self::index($current, $key) : self::member($current, self::key($key));
        }

        if (! is_int($current) && ! is_float($current)) {
            throw new ScriptRuntimeException(sprintf('Cannot %s %s', $delta > 0 ? 'increment' : 'decrement', get_debug_type($current)));
        }

        self::assign($root, $path, $current + $delta);

        return $current;
    }

    /**
     * `+` adds two numbers and concatenates everything else.
     *
     * @throws ScriptRuntimeException
     */
    public static function add(mixed $left, mixed $right, ?ExecutionBudget $budget = null): mixed
    {
        if ((is_int($left) || is_float($left)) && (is_int($right) || is_float($right))) {
            return $left + $right;
        }

        $left = self::toString($left);
        $right = self::toString($right);
        $budget?->reserve(strlen($left) + strlen($right));

        return $left . $right;
    }

    /**
     * `==` never converts types: `1 == "1"` is false, but `10 == 10.0` is true.
     * Lists, maps and objects are compared by value, dates by their point in time.
     *
     * @throws ScriptRuntimeException
     */
    public static function equals(mixed $left, mixed $right): bool
    {
        $budget = self::MAX_COMPARE_VALUES;

        return self::same($left, $right, 0, $budget);
    }

    /**
     * `<`, `>`, `<=` and `>=` only compare numbers with numbers, strings with strings
     * and dates with dates. PHP would silently order mixed types (an object is always
     * greater than a string), which gives wrong results instead of an error.
     *
     * @throws ScriptRuntimeException
     */
    public static function compare(mixed $left, string $operator, mixed $right): bool
    {
        $comparable = ((is_int($left) || is_float($left)) && (is_int($right) || is_float($right)))
            || (is_string($left) && is_string($right))
            || ($left instanceof DateTimeInterface && $right instanceof DateTimeInterface);

        if (! $comparable) {
            throw new ScriptRuntimeException(sprintf('Cannot compare %s with %s', get_debug_type($left), get_debug_type($right)));
        }

        return match ($operator) {
            '<' => $left < $right,
            '>' => $left > $right,
            '<=' => $left <= $right,
            default => $left >= $right,
        };
    }

    /**
     * @throws ScriptRuntimeException
     */
    private static function same(mixed $left, mixed $right, int $depth, int &$budget): bool
    {
        if ($depth > self::MAX_COMPARE_DEPTH) {
            throw new ScriptRuntimeException('Values are nested too deeply to compare');
        }

        if (--$budget < 0) {
            throw new ScriptRuntimeException('Values are too large to compare');
        }

        if ((is_int($left) || is_float($left)) && (is_int($right) || is_float($right))) {
            // Same as PHP's `10 == 10.0`; an int is compared as a float.
            return is_int($left) && is_int($right) ? $left === $right : (float) $left === (float) $right;
        }

        if ($left instanceof DateTimeInterface && $right instanceof DateTimeInterface) {
            return $left == $right;
        }

        if (is_object($left) && is_object($right)) {
            return $left === $right
                || ($left::class === $right::class && self::same((array) $left, (array) $right, $depth + 1, $budget));
        }

        if (is_array($left) && is_array($right)) {
            if (count($left) !== count($right)) {
                return false;
            }

            return array_all($left, function (mixed $value, int|string $key) use ($right, $depth, &$budget): bool {
                return array_key_exists($key, $right) && self::same($value, $right[$key], $depth + 1, $budget);
            });
        }

        return $left === $right;
    }

    /**
     * Reference to `target.key` / `target[key]` for a write further down the path.
     *
     * @throws ScriptRuntimeException
     */
    private static function &child(mixed &$target, int $kind, mixed $key): mixed
    {
        $value = $kind === self::INDEX ? self::index($target, $key) : self::member($target, self::key($key));

        if (is_object($value)) {
            return $value;
        }

        if (is_array($target) && (is_string($key) || is_int($key))) {
            return $target[$key];
        }

        if (is_object($target) && ! $target instanceof ArrayAccess && array_key_exists(self::key($key), get_object_vars($target))) {
            return $target->{self::key($key)};
        }

        throw new ScriptRuntimeException(sprintf("Cannot modify '%s' of %s", is_scalar($key) ? (string) $key : '?', get_debug_type($target)));
    }

    /**
     * @throws ScriptRuntimeException
     */
    private static function write(mixed &$target, int $kind, mixed $key, mixed $value): void
    {
        if (! is_string($key) && ! is_int($key)) {
            throw ScriptRuntimeException::invalidKey($key);
        }

        if (is_array($target)) {
            $target[$key] = $value;

            return;
        }

        if ($kind === self::INDEX && $target instanceof ArrayAccess) {
            $target->offsetSet($key, $value);

            return;
        }

        if (is_object($target)) {
            $target->{(string) $key} = $value;

            return;
        }

        throw ScriptRuntimeException::notAccessible($key, $target);
    }

    /**
     * @throws ScriptRuntimeException
     */
    private static function key(mixed $key): string
    {
        if (! is_string($key) && ! is_int($key)) {
            throw ScriptRuntimeException::invalidKey($key);
        }

        return (string) $key;
    }

    /**
     * @param  array<array-key, mixed>  $array
     *
     * @throws ScriptRuntimeException
     */
    private static function arrayValue(array $array, string|int $key): mixed
    {
        if (! array_key_exists($key, $array)) {
            throw ScriptRuntimeException::undefinedKey($key);
        }

        return $array[$key];
    }

    /**
     * @throws ScriptRuntimeException
     */
    private static function toString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        if ($value === null || is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        throw new ScriptRuntimeException(sprintf('Cannot convert %s to string', get_debug_type($value)));
    }
}

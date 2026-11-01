<?php

declare(strict_types=1);

namespace PhpScript\Runtime;

use Closure;
use ReflectionFunctionAbstract;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * Classifies the parameters of a function or method by how dangerous a script
 * argument in that position can be.
 */
final class Signature
{
    /**
     * The parameter is typed `callable` or `Closure`: PHP will call the argument.
     */
    public const string CALLBACK = 'callback';

    /**
     * The parameter is untyped or `mixed`: the code may call the argument.
     */
    public const string LOOSE = 'loose';

    /**
     * Kind of each parameter (null for a parameter with another type) and whether
     * the last parameter is variadic.
     *
     * @return array{list<self::CALLBACK|self::LOOSE|null>, bool}
     */
    public static function of(ReflectionFunctionAbstract $function): array
    {
        $kinds = [];
        $variadic = false;

        foreach ($function->getParameters() as $parameter) {
            $kinds[] = self::kind($parameter->getType());
            $variadic = $parameter->isVariadic();
        }

        return [$kinds, $variadic];
    }

    /**
     * Kind of the parameter that receives the argument at `$position`.
     *
     * @param  array{list<self::CALLBACK|self::LOOSE|null>, bool}  $signature
     * @return self::CALLBACK|self::LOOSE|null
     */
    public static function at(array $signature, int $position): ?string
    {
        [$kinds, $variadic] = $signature;

        if ($position >= count($kinds)) {
            return $variadic && $kinds !== [] ? $kinds[array_key_last($kinds)] : null;
        }

        return $kinds[$position];
    }

    public static function takesCallback(ReflectionFunctionAbstract $function): bool
    {
        return in_array(self::CALLBACK, self::of($function)[0], true);
    }

    /**
     * @return self::CALLBACK|self::LOOSE|null
     */
    private static function kind(?ReflectionType $type): ?string
    {
        if (! $type instanceof ReflectionType) {
            return self::LOOSE;
        }

        $types = $type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType
            ? $type->getTypes()
            : [$type];

        $kind = null;
        foreach ($types as $member) {
            $name = $member instanceof ReflectionNamedType ? strtolower($member->getName()) : '';

            if ($name === 'callable' || $name === strtolower(Closure::class)) {
                return self::CALLBACK;
            }

            if ($name === 'mixed') {
                $kind = self::LOOSE;
            }
        }

        return $kind;
    }
}

<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Utilities;

use Closure;
use InvalidArgumentException;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Types\Argument;
use Secundo\GraphQL\Types\Directive;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\InlineFragment;
use Secundo\GraphQL\Types\Variable;

class TypeGuards
{
    /**
     * Check if value is a Field instance
     */
    public static function isField(mixed $value): bool
    {
        return $value instanceof Field;
    }

    /**
     * Check if value is an InlineFragment instance
     */
    public static function isInlineFragment(mixed $value): bool
    {
        return $value instanceof InlineFragment;
    }

    /**
     * Check if value is a Fragment instance
     */
    public static function isFragment(mixed $value): bool
    {
        return $value instanceof Fragment;
    }

    /**
     * Check if value is a Directive instance
     */
    public static function isDirective(mixed $value): bool
    {
        return $value instanceof Directive;
    }

    /**
     * Check if value is an Argument instance
     */
    public static function isArgument(mixed $value): bool
    {
        return $value instanceof Argument;
    }

    /**
     * Check if value is a Variable instance
     */
    public static function isVariable(mixed $value): bool
    {
        return $value instanceof Variable;
    }

    /**
     * Check if value is an ArgumentBuilder instance
     */
    public static function isArgumentBuilder(mixed $value): bool
    {
        return $value instanceof ArgumentBuilder;
    }

    /**
     * Check if value is a Closure
     */
    public static function isClosure(mixed $value): bool
    {
        return $value instanceof Closure;
    }

    /**
     * Check if value is a string
     */
    public static function isString(mixed $value): bool
    {
        return is_string($value);
    }

    /**
     * Check if value is an array
     */
    public static function isArray(mixed $value): bool
    {
        return is_array($value);
    }

    /**
     * Check if value is a non-empty string
     */
    public static function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }

    /**
     * Check if value is a valid field name (non-empty string)
     */
    public static function isValidFieldName(mixed $value): bool
    {
        return static::isNonEmptyString($value);
    }

    /**
     * Check if array has a 'name' key (metadata format)
     */
    public static function isMetadataArray(mixed $value): bool
    {
        return is_array($value) && isset($value['name']);
    }

    /**
     * Check if value is a valid GraphQL type
     */
    public static function isGraphQLType(mixed $value): bool
    {
        return static::isField($value)
            || static::isInlineFragment($value)
            || static::isFragment($value)
            || static::isDirective($value)
            || static::isArgument($value)
            || static::isVariable($value);
    }

    /**
     * Assert that value is of expected type, throw exception if not
     */
    public static function assertType(mixed $value, string $expectedType, string $context = ''): void
    {
        $methodName = 'is'.ucfirst($expectedType);

        if (! method_exists(static::class, $methodName)) {
            throw new InvalidArgumentException("Unknown type guard: {$expectedType}");
        }

        if (! static::$methodName($value)) {
            $actualType = get_debug_type($value);
            $message = "Expected {$expectedType}, got {$actualType}";

            if ($context !== '') {
                $message .= " in {$context}";
            }

            throw new InvalidArgumentException($message);
        }
    }
}

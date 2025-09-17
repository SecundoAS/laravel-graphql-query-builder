<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Processors;

use Secundo\GraphQL\Types\Directive;

class DirectiveProcessor
{
    /**
     * Process directives array into Directive objects
     */
    public static function process(array $directives): array
    {
        $processedDirectives = [];

        foreach ($directives as $directive) {
            if ($directive instanceof Directive) {
                $processedDirectives[] = $directive;
            } elseif (is_array($directive)) {
                $processedDirectives[] = new Directive(
                    $directive['name'] ?? 'unknown',
                    $directive['arguments'] ?? []
                );
            } else {
                $processedDirectives[] = new Directive((string) $directive);
            }
        }

        return $processedDirectives;
    }

    /**
     * Convert directives to array format
     */
    public static function toArray(array $directives): array
    {
        return array_map(fn (Directive $directive): array => $directive->toArray(), $directives);
    }

    /**
     * Find a directive by name
     */
    public static function findByName(array $directives, string $name): ?Directive
    {
        foreach ($directives as $directive) {
            if ($directive->getName() === $name) {
                return $directive;
            }
        }

        return null;
    }

    /**
     * Check if directives contain a directive with given name
     */
    public static function hasDirective(array $directives, string $name): bool
    {
        foreach ($directives as $directive) {
            if ($directive->getName() === $name) {
                return true;
            }
        }

        return false;
    }
}

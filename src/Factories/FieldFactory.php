<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Factories;

use Closure;
use Secundo\GraphQL\Types\Field;

class FieldFactory
{
    /**
     * Create a Field object with optional arguments, fields, and alias
     */
    public static function create(string $name, array $arguments = [], array|Closure|null $fields = null, ?string $alias = null, array $directives = []): Field
    {
        if ($fields instanceof Closure) {
            $subInstance = new Field($name);
            $fields($subInstance);
            $fields = $subInstance->getFields();
        }

        return new Field($name, $arguments, $fields ?? [], $alias, $directives);
    }

    /**
     * Create a Field from array definition
     */
    public static function fromArray(string $name, array $definition): Field
    {
        $arguments = [];
        $fields = [];
        $alias = null;
        $directives = [];

        // Handle different array formats
        if (isset($definition['arguments'])) {
            $arguments = $definition['arguments'];
        }

        if (isset($definition['fields'])) {
            $fields = $definition['fields'];
        }

        if (isset($definition['alias'])) {
            $alias = $definition['alias'];
        }

        if (isset($definition['directives'])) {
            $directives = $definition['directives'];
        }

        // If it's a simple array of field names, treat as fields
        if (! isset($definition['arguments']) && ! isset($definition['fields']) && ! isset($definition['alias']) && ! isset($definition['directives'])) {
            $fields = $definition;
        }

        return new Field($name, $arguments, $fields, $alias, $directives);
    }

    /**
     * Create a Field from metadata array
     */
    public static function fromMetadata(array $metadata): Field
    {
        return new Field(
            $metadata['name'],
            $metadata['arguments'] ?? [],
            $metadata['fields'] ?? [],
            $metadata['alias'] ?? null,
            $metadata['directives'] ?? []
        );
    }

    /**
     * Create multiple fields from array of definitions
     */
    public static function createMany(array $definitions): array
    {
        $fields = [];

        foreach ($definitions as $key => $definition) {
            if (is_string($definition)) {
                $fields[] = new Field($definition);
            } elseif (is_string($key) && is_array($definition)) {
                $fields[] = static::fromArray($key, $definition);
            } elseif (is_array($definition) && isset($definition['name'])) {
                $fields[] = static::fromMetadata($definition);
            }
        }

        return $fields;
    }
}

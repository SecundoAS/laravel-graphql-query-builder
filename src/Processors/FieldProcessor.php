<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Processors;

use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\InlineFragment;

class FieldProcessor
{
    /**
     * Process fields array into Field objects
     */
    public static function process(array $fields): array
    {
        $processedFields = [];

        foreach ($fields as $key => $field) {
            if ($field instanceof Field) {
                $processedFields[] = $field;
            } elseif (is_string($field)) {
                $processedFields[] = new Field($field);
            } elseif (is_array($field) && is_string($key)) {
                // Named field with array definition
                $processedFields[] = static::createFieldFromArray($key, $field);
            } elseif (is_array($field) && isset($field['name'])) {
                // Field with metadata
                $processedFields[] = static::createFieldFromMetadata($field);
            }
        }

        return $processedFields;
    }

    /**
     * Convert fields to array format for GraphQL output
     */
    public static function toArray(array $fields): array
    {
        return array_map(function ($field) {
            if ($field instanceof InlineFragment) {
                return $field->toArray();
            }

            if ($field instanceof Field) {
                return $field->toArray();
            }

            return $field; // Return strings as-is
        }, $fields);
    }

    /**
     * Create a Field from array definition
     */
    protected static function createFieldFromArray(string $name, array $definition): Field
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
    protected static function createFieldFromMetadata(array $metadata): Field
    {
        return new Field(
            $metadata['name'],
            $metadata['arguments'] ?? [],
            $metadata['fields'] ?? [],
            $metadata['alias'] ?? null,
            $metadata['directives'] ?? []
        );
    }
}

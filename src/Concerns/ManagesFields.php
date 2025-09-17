<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Concerns;

use Closure;
use InvalidArgumentException;
use Secundo\GraphQL\Factories\FieldFactory;
use Secundo\GraphQL\Processors\FieldProcessor;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\InlineFragment;
use Secundo\GraphQL\Utilities\TypeGuards;

trait ManagesFields
{
    /**
     * The fields collection for this instance.
     *
     * @var array<Field|InlineFragment>
     */
    private array $fields = [];

    /**
     * Add a single field to the collection.
     */
    public function field(Field|string $field, array $arguments = [], array|Closure|null $fields = null, ?string $alias = null): static
    {
        if (! TypeGuards::isField($field)) {
            $field = FieldFactory::create($field, $arguments, $fields, $alias);
        }

        $this->fields[] = $field;

        return $this;
    }

    /**
     * Add multiple fields to the collection.
     */
    public function fields(array $fields): static
    {
        $this->processFieldsArray($fields);

        return $this;
    }

    /**
     * Add an inline fragment to the collection.
     */
    public function inlineFragment(Fragment|InlineFragment|string $fragment, Closure|array $fields = [], array $directives = []): static
    {
        if (TypeGuards::isFragment($fragment)) {
            // Convert regular fragment to inline fragment
            $inlineFragment = new InlineFragment($fragment->getOnType(), $fragment->getFields(), $directives);
        } elseif (TypeGuards::isInlineFragment($fragment)) {
            // Already an inline fragment - use directly
            $inlineFragment = $fragment;
            // Add any additional directives
            foreach ($directives as $directive) {
                if (TypeGuards::isDirective($directive)) {
                    $inlineFragment->directive($directive);
                } elseif (is_array($directive)) {
                    $inlineFragment->directive($directive['name'] ?? 'unknown', $directive['arguments'] ?? []);
                } else {
                    $inlineFragment->directive((string) $directive);
                }
            }
        } else {
            // Create new inline fragment from type name
            $inlineFragment = new InlineFragment($fragment, [], $directives);
        }

        if ($fields instanceof Closure) {
            $fields($inlineFragment);
        } elseif (is_array($fields) && (! TypeGuards::isFragment($fragment) && ! TypeGuards::isInlineFragment($fragment))) {
            // Only process array fields if we created a new InlineFragment
            $inlineFragment->fields($fields);
        }

        $this->fields[] = $inlineFragment;

        return $this;
    }

    /**
     * Get all fields in array format (for GraphQL output).
     */
    public function getFields(): array
    {
        return FieldProcessor::toArray($this->fields);
    }

    /**
     * Get raw field objects.
     */
    public function getRawFields(): array
    {
        return $this->fields;
    }

    /**
     * Check if there are any fields.
     */
    public function hasFields(): bool
    {
        return ! empty($this->fields);
    }

    /**
     * Count the number of fields.
     */
    public function fieldsCount(): int
    {
        return count($this->fields);
    }

    /**
     * Clear all fields.
     */
    public function clearFields(): static
    {
        $this->fields = [];

        return $this;
    }

    /**
     * Set fields directly (replaces all existing fields).
     */
    public function setFields(array $fields): static
    {
        $this->fields = FieldProcessor::process($fields);

        return $this;
    }

    /**
     * Get fields array for use in Builder.getFieldsArray().
     */
    public function getFieldsArray(): array
    {
        return array_map(fn (Field $field): array => $field->toArray(), $this->fields);
    }

    /**
     * Process an array of fields with support for different field formats.
     */
    protected function processFieldsArray(array $fields): void
    {
        foreach ($fields as $fieldName => $fieldValue) {
            if (is_string($fieldName) && is_array($fieldValue)) {
                $this->processNamedArrayField($fieldName, $fieldValue);
            } elseif (is_string($fieldName) && $fieldValue instanceof Closure) {
                $this->processNamedCallableField($fieldName, $fieldValue);
            } elseif (is_string($fieldValue)) {
                $this->processStringField($fieldValue);
            } elseif (is_array($fieldValue) && isset($fieldValue['name'])) {
                $this->processFieldArray($fieldValue);
            } else {
                $this->processGenericField($fieldValue);
            }
        }
    }

    /**
     * Process a named field with array definition.
     */
    protected function processNamedArrayField(string $name, array $definition): void
    {
        $this->field($name, [], $definition);
    }

    /**
     * Process a named field with Closure definition.
     */
    protected function processNamedCallableField(string $name, Closure $callback): void
    {
        $this->field($name, [], $callback);
    }

    /**
     * Process a simple string field.
     */
    protected function processStringField(string $fieldName): void
    {
        $this->field($fieldName);
    }

    /**
     * Process a field defined as an array with metadata.
     */
    protected function processFieldArray(array $fieldData): void
    {
        $this->addFieldFromArray($fieldData);
    }

    /**
     * Add a field from array metadata.
     */
    protected function addFieldFromArray(array $fieldData): void
    {
        $field = FieldFactory::create($fieldData['name']);

        if (! empty($fieldData['arguments'])) {
            $field->arguments($fieldData['arguments']);
        }

        if (! empty($fieldData['fields'])) {
            $field->fields($fieldData['fields']);
        }

        if (! empty($fieldData['directives'])) {
            $field->directives($fieldData['directives']);
        }

        $this->field($field);
    }

    /**
     * Process any other field type (fallback).
     */
    protected function processGenericField(mixed $field): void
    {
        if (TypeGuards::isField($field) || TypeGuards::isInlineFragment($field)) {
            $this->fields[] = $field;
        } else {
            throw new InvalidArgumentException('Invalid field specification: '.json_encode($field));
        }
    }

    /**
     * Create a Field object with optional arguments, fields, and alias.
     * Legacy method for backward compatibility.
     */
    protected function makeField(string $name, array $arguments = [], array|Closure|null $fields = null, ?string $alias = null): Field
    {
        return FieldFactory::create($name, $arguments, $fields, $alias);
    }
}

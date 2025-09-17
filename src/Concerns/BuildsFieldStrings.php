<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Concerns;

trait BuildsFieldStrings
{
    use FormatsArguments;

    protected function buildFieldsString(array $fields, int $indentLevel = 0): string
    {
        $indent = str_repeat('  ', $indentLevel);
        $result = '';

        foreach ($fields as $field) {
            // Handle simple string fields (like fragment references)
            if (is_string($field)) {
                $result .= $indent.$field."\n";

                continue;
            }

            // Handle InlineFragment objects specially
            if (is_object($field) && method_exists($field, 'isInline') && $field->isInline()) {
                // For inline fragments, render as "... on TypeName"
                $result .= $indent.$field->getName();

                // Add the fields if any exist
                $fragmentFields = $field->getFields();
                if (! empty($fragmentFields)) {
                    $result .= " {\n";
                    $result .= $this->buildFieldsString($fragmentFields, $indentLevel + 1);
                    $result .= $indent.'}';
                }

                $result .= "\n";

                continue;
            }

            // Handle Field objects by converting them to arrays
            if (is_object($field) && method_exists($field, 'toArray')) {
                $field = $field->toArray();
            }

            // Handle field arrays
            if (is_array($field) && isset($field['name'])) {
                $fieldName = $field['name'];
                $arguments = $field['arguments'] ?? [];
                $subFields = $field['fields'] ?? [];
                $directives = $field['directives'] ?? [];

                // Handle alias if present
                if (! empty($field['alias'])) {
                    $fieldName = $field['alias'].': '.$fieldName;
                }

                $result .= $indent.$fieldName;

                // Add arguments (with variable filtering)
                if (! empty($arguments)) {
                    $argStrings = [];
                    foreach ($arguments as $argName => $argValue) {
                        // Filter out variable references that don't exist
                        if (is_string($argValue) && str_starts_with($argValue, '$')) {
                            $variableName = mb_substr($argValue, 1);
                            if ($this->hasVariable($variableName)) {
                                $argStrings[] = "{$argName}: {$argValue}";
                            }
                        } else {
                            $argStrings[] = "{$argName}: ".$this->formatArgumentValue($argValue);
                        }
                    }

                    $result .= '('.implode(', ', $argStrings).')';
                }

                // Add directives
                if (! empty($directives)) {
                    foreach ($directives as $directive) {
                        $result .= ' @'.$directive['name'];
                        if (! empty($directive['arguments'])) {
                            $directiveArgs = [];
                            foreach ($directive['arguments'] as $argName => $argValue) {
                                if (is_string($argValue) && str_starts_with($argValue, '$')) {
                                    $directiveArgs[] = "{$argName}: {$argValue}";
                                } else {
                                    $directiveArgs[] = "{$argName}: ".$this->formatArgumentValue($argValue);
                                }
                            }

                            $result .= '('.implode(', ', $directiveArgs).')';
                        }
                    }
                }

                // Add sub-fields
                if (! empty($subFields)) {
                    $result .= " {\n";
                    $result .= $this->buildFieldsString($subFields, $indentLevel + 1);
                    $result .= $indent.'}';
                }

                $result .= "\n";
            }
        }

        return $result;
    }
}

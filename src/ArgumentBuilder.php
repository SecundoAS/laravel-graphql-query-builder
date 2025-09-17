<?php

declare(strict_types=1);

namespace Secundo\GraphQL;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use Stringable;

class ArgumentBuilder implements Stringable
{
    use Conditionable;

    protected array $conditions = [];

    protected string $boolean = 'AND';

    public function __construct()
    {
        //
    }

    /**
     * Convert to string for Stringable interface
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    public static function create(): static
    {
        return new static;
    }

    /**
     * Add a where clause to the query
     */
    public function where(string|Closure $field, mixed $operator = ':', mixed $value = null): static
    {
        if ($field instanceof Closure) {
            return $this->whereNested($field, 'AND');
        }

        // Handle shorthand: where('field', 'value') assumes ':' operator
        if ($value === null && ! is_string($operator)) {
            $value = $operator;
            $operator = ':';
        } elseif ($value === null && is_string($operator) && ! in_array($operator, [':', ':<', ':>', ':<=', ':>='])) {
            $value = $operator;
            $operator = ':';
        }

        $this->conditions[] = [
            'type' => 'where',
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'boolean' => $this->boolean,
        ];

        $this->boolean = 'AND';

        return $this;
    }

    /**
     * Add an OR where clause to the query
     */
    public function orWhere(string|Closure $field, mixed $operator = ':', mixed $value = null): static
    {
        if ($field instanceof Closure) {
            return $this->whereNested($field, 'OR');
        }

        $this->boolean = 'OR';

        return $this->where($field, $operator, $value);
    }

    /**
     * Add a NOT where clause to the query
     */
    public function whereNot(string $field, mixed $value): static
    {
        $this->conditions[] = [
            'type' => 'whereNot',
            'field' => $field,
            'value' => $value,
            'boolean' => $this->boolean,
        ];

        $this->boolean = 'AND';

        return $this;
    }

    /**
     * Add an OR NOT where clause to the query
     */
    public function orWhereNot(string $field, mixed $value): static
    {
        $this->boolean = 'OR';

        return $this->whereNot($field, $value);
    }

    /**
     * Add a where in clause to the query
     */
    public function whereIn(string $field, array $values): static
    {
        $this->conditions[] = [
            'type' => 'whereIn',
            'field' => $field,
            'values' => $values,
            'boolean' => $this->boolean,
        ];

        $this->boolean = 'AND';

        return $this;
    }

    /**
     * Add an OR where in clause to the query
     */
    public function orWhereIn(string $field, array $values): static
    {
        $this->boolean = 'OR';

        return $this->whereIn($field, $values);
    }

    /**
     * Add a where not in clause to the query
     */
    public function whereNotIn(string $field, array $values): static
    {
        $this->conditions[] = [
            'type' => 'whereNotIn',
            'field' => $field,
            'values' => $values,
            'boolean' => $this->boolean,
        ];

        $this->boolean = 'AND';

        return $this;
    }

    /**
     * Add an OR where not in clause to the query
     */
    public function orWhereNotIn(string $field, array $values): static
    {
        $this->boolean = 'OR';

        return $this->whereNotIn($field, $values);
    }

    /**
     * Add a wildcard search (starts with)
     */
    public function whereStartsWith(string $field, string $value): static
    {
        return $this->where($field, ':', $value.'*');
    }

    /**
     * Add an OR wildcard search (starts with)
     */
    public function orWhereStartsWith(string $field, string $value): static
    {
        $this->boolean = 'OR';

        return $this->whereStartsWith($field, $value);
    }

    /**
     * Add a wildcard pattern search
     */
    public function whereWildcard(string $field, string $pattern): static
    {
        return $this->where($field, ':', $pattern);
    }

    /**
     * Add an OR wildcard pattern search
     */
    public function orWhereWildcard(string $field, string $pattern): static
    {
        $this->boolean = 'OR';

        return $this->whereWildcard($field, $pattern);
    }

    /**
     * Add a phrase search (quoted)
     */
    public function wherePhrase(string $field, string $phrase): static
    {
        return $this->where($field, ':', '"'.$this->escapeValue($phrase).'"');
    }

    /**
     * Add an OR phrase search (quoted)
     */
    public function orWherePhrase(string $field, string $phrase): static
    {
        $this->boolean = 'OR';

        return $this->wherePhrase($field, $phrase);
    }

    /**
     * Add a contains search for partial matches
     */
    public function whereContains(string $field, string $value): static
    {
        // For tokenized fields, this is just a regular search
        return $this->where($field, ':', $value);
    }

    /**
     * Add an OR contains search
     */
    public function orWhereContains(string $field, string $value): static
    {
        $this->boolean = 'OR';

        return $this->whereContains($field, $value);
    }

    /**
     * Add a full-text search without specific field
     */
    public function search(string $terms): static
    {
        $this->conditions[] = [
            'type' => 'search',
            'terms' => $terms,
            'boolean' => $this->boolean,
        ];

        $this->boolean = 'AND';

        return $this;
    }

    /**
     * Add an OR full-text search
     */
    public function orSearch(string $terms): static
    {
        $this->boolean = 'OR';

        return $this->search($terms);
    }

    /**
     * Add a date comparison
     */
    public function whereDate(string $field, string $operator, string $date): static
    {
        // Ensure dates are properly quoted
        $quotedDate = $this->needsQuoting($date) ? '"'.$this->escapeValue($date).'"' : $date;

        return $this->where($field, $operator, $quotedDate);
    }

    /**
     * Add an OR date comparison
     */
    public function orWhereDate(string $field, string $operator, string $date): static
    {
        $this->boolean = 'OR';

        return $this->whereDate($field, $operator, $date);
    }

    /**
     * Add a raw query string
     */
    public function whereRaw(string $query): static
    {
        $this->conditions[] = [
            'type' => 'raw',
            'query' => $query,
            'boolean' => $this->boolean,
        ];

        $this->boolean = 'AND';

        return $this;
    }

    /**
     * Add an OR raw query string
     */
    public function orWhereRaw(string $query): static
    {
        $this->boolean = 'OR';

        return $this->whereRaw($query);
    }

    /**
     * Convert to query string
     */
    public function toString(): string
    {
        if ($this->conditions === []) {
            return '';
        }

        $parts = [];
        $isFirst = true;

        foreach ($this->conditions as $condition) {
            $part = $this->buildCondition($condition);

            if ($part === '') {
                continue;
            }

            if ($isFirst) {
                $parts[] = $part;
                $isFirst = false;
            } else {
                $parts[] = $condition['boolean'].' '.$part;
            }
        }

        return implode(' ', $parts);
    }

    /**
     * Add nested where clauses
     */
    protected function whereNested(Closure $callback, string $boolean = 'AND'): static
    {
        $query = new static;
        $callback($query);

        if ($query->conditions !== []) {
            $this->conditions[] = [
                'type' => 'nested',
                'query' => $query,
                'boolean' => $boolean,
            ];
        }

        return $this;
    }

    /**
     * Build individual condition string
     */
    protected function buildCondition(array $condition): string
    {
        return match ($condition['type']) {
            'where' => $this->buildWhereCondition($condition),
            'whereNot' => 'NOT '.$this->buildFieldValue($condition['field'], $condition['value']),
            'whereIn' => $this->buildWhereInCondition($condition),
            'whereNotIn' => 'NOT '.$this->buildWhereInCondition($condition),
            'search' => $this->escapeValue($condition['terms']),
            'raw' => $condition['query'],
            'nested' => '('.$condition['query']->toString().')',
            default => '',
        };
    }

    /**
     * Build where condition string
     */
    protected function buildWhereCondition(array $condition): string
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];

        if ($operator === ':') {
            return $this->buildFieldValue($field, $value);
        }

        return $field.':'.$operator.$this->formatValue($value);
    }

    /**
     * Build where in condition string
     */
    protected function buildWhereInCondition(array $condition): string
    {
        $field = $condition['field'];
        $values = array_map([$this, 'formatValue'], $condition['values']);

        return $field.':'.implode(',', $values);
    }

    /**
     * Build field:value pair
     */
    protected function buildFieldValue(string $field, mixed $value): string
    {
        return $field.':'.$this->formatValue($value);
    }

    /**
     * Format value for output
     */
    protected function formatValue(mixed $value): string
    {
        if (is_string($value)) {
            // Check if it's a variable reference
            if (str_starts_with($value, '$')) {
                return $value;
            }

            // Check if it already has quotes or is a special value
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                return $value;
            }

            if (in_array($value, ['now', 'today', 'yesterday'])) {
                return $value;
            }

            // Check if it needs quoting
            if ($this->needsQuoting($value)) {
                return '"'.$this->escapeValue($value).'"';
            }

            return $this->escapeValue($value);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        return (string) $value;
    }

    /**
     * Check if value needs quoting
     */
    protected function needsQuoting(string $value): bool
    {
        // Values with spaces, special characters (except : in ISO timestamps), or that look like dates need quoting
        return preg_match('/[\s()\\\\]|^\d{4}-\d{2}-\d{2}/', $value) === 1;
    }

    /**
     * Escape special characters in values
     */
    protected function escapeValue(string $value): string
    {
        // Don't escape colons in ISO timestamps, but escape other special characters
        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
            // This is an ISO timestamp, only escape quotes, backslashes, and parentheses
            return addcslashes($value, '\\()\"');
        }

        // Escape special characters: \, :, (, ), "
        return addcslashes($value, '\\:()\"');
    }
}

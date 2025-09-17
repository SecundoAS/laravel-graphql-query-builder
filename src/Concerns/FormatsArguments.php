<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Concerns;

use Secundo\GraphQL\ArgumentBuilder;

trait FormatsArguments
{
    protected function formatArgumentValue(mixed $value): string
    {
        if ($value instanceof ArgumentBuilder) {
            $queryString = $value->toString();

            // Always quote ArgumentBuilder results
            return '"'.addslashes($queryString).'"';
        }

        if (is_string($value)) {
            // Don't quote variables
            if (str_starts_with($value, '$')) {
                return $value;
            }

            return '"'.addslashes($value).'"';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                $items = array_map([$this, 'formatArgumentValue'], $value);

                return '['.implode(', ', $items).']';
            }

            $pairs = [];
            foreach ($value as $key => $val) {
                $pairs[] = $key.': '.$this->formatArgumentValue($val);
            }

            return '{'.implode(', ', $pairs).'}';
        }

        return (string) $value;
    }

    protected function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}

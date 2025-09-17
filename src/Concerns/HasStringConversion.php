<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Concerns;

trait HasStringConversion
{
    public function __toString(): string
    {
        // Try toString() first, then toDefinitionString()
        // @phpstan-ignore-next-line function.alreadyNarrowedType
        if (method_exists($this, 'toString')) {
            return $this->toString();
        }

        // @phpstan-ignore-next-line function.alreadyNarrowedType
        if (method_exists($this, 'toDefinitionString')) {
            return $this->toDefinitionString();
        }

        // Fallback to class name if neither method exists
        return static::class;
    }
}

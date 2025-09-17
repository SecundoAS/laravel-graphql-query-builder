<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Types;

use Illuminate\Support\Traits\Conditionable;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Concerns\HasStringConversion;
use Stringable;

class Variable implements Stringable
{
    use Conditionable;
    use HasStringConversion;

    public function __construct(
        private string $name,
        private string $type,
        private mixed $value = null,
    ) {
        // If value is an ArgumentBuilder, convert it to string
        if ($value instanceof ArgumentBuilder) {
            $this->value = $value->toString();
        }
    }

    public static function create(string $name, string $type, mixed $value = null): static
    {
        return new static($name, $type, $value);
    }

    public static function fromArray(array $data): static
    {
        return new static(
            $data['name'],
            $data['type'],
            $data['value'] ?? null
        );
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function value(mixed $value): static
    {
        // If value is an ArgumentBuilder, convert it to string
        $this->value = $value instanceof ArgumentBuilder ? $value->toString() : $value;

        return $this;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function hasValue(): bool
    {
        return $this->value !== null;
    }

    public function isRequired(): bool
    {
        return str_ends_with($this->type, '!');
    }

    public function isNullable(): bool
    {
        return ! $this->isRequired();
    }

    public function isList(): bool
    {
        return str_contains($this->type, '[') && str_contains($this->type, ']');
    }

    public function baseType(): string
    {
        $type = $this->type;

        // Remove list brackets
        $type = preg_replace('/\[([^\]]+)\]/', '$1', $type);

        // Remove non-null indicator
        $type = mb_rtrim($type, '!');

        return $type;
    }

    public function toDefinitionString(): string
    {
        return "\${$this->name}: {$this->type}";
    }

    public function toString(): string
    {
        return $this->toDefinitionString();
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}

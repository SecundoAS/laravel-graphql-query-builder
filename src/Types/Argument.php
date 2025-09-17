<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Types;

use Illuminate\Support\Traits\Conditionable;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Concerns\FormatsArguments;
use Secundo\GraphQL\Concerns\HasStringConversion;
use Stringable;

class Argument implements Stringable
{
    use Conditionable;
    use FormatsArguments;
    use HasStringConversion;

    public function __construct(
        private string $name,
        private mixed $value = null,
    ) {
        // If value is an ArgumentBuilder, convert it to string
        if ($value instanceof ArgumentBuilder) {
            $this->value = $value->toString();
        }
    }

    public static function create(string $name, mixed $value): static
    {
        return new static($name, $value);
    }

    public static function variable(string $name, string $variableName): static
    {
        $variableName = str_starts_with($variableName, '$') ? $variableName : '$'.$variableName;

        return new static($name, $variableName);
    }

    public static function literal(string $name, mixed $value): static
    {
        return new static($name, $value);
    }

    public static function fromArray(array $data): static
    {
        return new static($data['name'], $data['value']);
    }

    public static function fromKeyValue(string $key, mixed $value): static
    {
        return new static($key, $value);
    }

    public static function collection(array $arguments): array
    {
        $collection = [];
        foreach ($arguments as $name => $value) {
            $collection[] = static::fromKeyValue($name, $value);
        }

        return $collection;
    }

    public static function builder(): ArgumentBuilder
    {
        return ArgumentBuilder::create();
    }

    public static function query(): ArgumentBuilder
    {
        return ArgumentBuilder::create();
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

    public function isVariable(): bool
    {
        return is_string($this->value) && str_starts_with($this->value, '$');
    }

    public function getVariableName(): ?string
    {
        if ($this->isVariable()) {
            return mb_substr((string) $this->value, 1);
        }

        return null;
    }

    public function isLiteral(): bool
    {
        return ! $this->isVariable();
    }

    public function type(): string
    {
        return match (true) {
            is_string($this->value) => $this->isVariable() ? 'variable' : 'string',
            is_int($this->value) => 'int',
            is_float($this->value) => 'float',
            is_bool($this->value) => 'boolean',
            is_null($this->value) => 'null',
            is_array($this->value) => $this->isAssociativeArray($this->value) ? 'object' : 'array',
            default => 'unknown'
        };
    }

    public function toGraphQLString(): string
    {
        if ($this->isVariable()) {
            return $this->value;
        }

        return $this->formatArgumentValue($this->value);
    }

    public function toString(): string
    {
        return "{$this->name}: {$this->toGraphQLString()}";
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'type' => $this->type(),
        ];
    }
}

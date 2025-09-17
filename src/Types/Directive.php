<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Types;

use Illuminate\Support\Traits\Conditionable;
use Secundo\GraphQL\Concerns\FormatsArguments;
use Secundo\GraphQL\Concerns\HasStringConversion;
use Stringable;

class Directive implements Stringable
{
    use Conditionable;
    use FormatsArguments;
    use HasStringConversion;

    /**
     * @param  array<string, scalar>  $arguments
     */
    public function __construct(
        private string $name,
        private array $arguments = []
    ) {}

    /**
     * @param  array<string, scalar>  $arguments
     */
    public static function create(string $name, array $arguments = []): static
    {
        return new static($name, $arguments);
    }

    public static function include(string|bool $condition): static
    {
        return new static('include', ['if' => $condition]);
    }

    public static function skip(string|bool $condition): static
    {
        return new static('skip', ['if' => $condition]);
    }

    public static function deprecated(?string $reason = null): static
    {
        $arguments = [];
        if ($reason !== null) {
            $arguments['reason'] = $reason;
        }

        return new static('deprecated', $arguments);
    }

    public static function specifiedBy(string $url): static
    {
        return new static('specifiedBy', ['url' => $url]);
    }

    public static function oneOf(): static
    {
        return new static('oneOf');
    }

    public static function fromArray(array $data): static
    {
        return new static($data['name'], $data['arguments'] ?? []);
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

    public function arguments(array $arguments): static
    {
        $this->arguments = $arguments;

        return $this;
    }

    public function addArgument(string $name, mixed $value): static
    {
        $this->arguments[$name] = $value;

        return $this;
    }

    /**
     * @return array<string, scalar>
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getArgument(string $name): mixed
    {
        return $this->arguments[$name] ?? null;
    }

    public function hasArguments(): bool
    {
        return $this->arguments !== [];
    }

    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    public function isBuiltIn(): bool
    {
        return in_array($this->name, ['include', 'skip', 'deprecated', 'specifiedBy', 'oneOf']);
    }

    public function isConditional(): bool
    {
        return in_array($this->name, ['include', 'skip']);
    }

    public function getCondition(): mixed
    {
        if ($this->isConditional()) {
            return $this->getArgument('if');
        }

        return null;
    }

    public function shouldApply(array $variables = []): bool
    {
        if (! $this->isConditional()) {
            return true;
        }

        $condition = $this->getCondition();

        // Handle variable references
        if (is_string($condition) && str_starts_with($condition, '$')) {
            $variableName = mb_substr($condition, 1);
            $condition = $variables[$variableName] ?? false;
        }

        return match ($this->name) {
            'include' => (bool) $condition,
            'skip' => ! (bool) $condition,
            default => true,
        };
    }

    public function toString(): string
    {
        $directive = "@{$this->name}";

        if ($this->hasArguments()) {
            $argumentStrings = [];
            foreach ($this->arguments as $name => $value) {
                $argumentStrings[] = "{$name}: {$this->formatArgumentValue($value)}";
            }

            $directive .= '('.implode(', ', $argumentStrings).')';
        }

        return $directive;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->arguments,
        ];
    }
}

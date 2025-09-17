<?php

declare(strict_types=1);

namespace Secundo\GraphQL;

use Closure;
use Illuminate\Support\Traits\Conditionable;
use InvalidArgumentException;
use Secundo\GraphQL\Concerns\BuildsFieldStrings;
use Secundo\GraphQL\Concerns\ManagesFields;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\Variable;
use Secundo\GraphQL\Utilities\TypeGuards;
use Stringable;

class Builder implements Stringable
{
    use BuildsFieldStrings;
    use Conditionable;
    use ManagesFields;

    /** @var array<string, Variable> */
    protected array $variables = [];

    /** @var array<string, Fragment> */
    protected array $fragments = [];

    protected ?string $operationType = null;

    protected ?string $operationName = null;

    public function __toString(): string
    {
        return $this->toGraphQL();
    }

    public function query(?string $name = null): static
    {
        $this->operationType = 'query';
        $this->operationName = $name;

        return $this;
    }

    public function mutation(?string $name = null): static
    {
        $this->operationType = 'mutation';
        $this->operationName = $name;

        return $this;
    }

    public function fieldWithQuery(string $name, Closure $queryCallback, array|Closure|null $fields = null, ?string $alias = null): static
    {
        $builder = ArgumentBuilder::create();
        $queryCallback($builder);

        return $this->field($name, ['query' => $builder], $fields, $alias);
    }

    public function fieldWithArgumentBuilder(string $fieldName, string $argumentName, Closure $callback): static
    {
        $builder = ArgumentBuilder::create();
        $callback($builder);

        return $this->field($fieldName, [$argumentName => $builder]);
    }

    public function variable(Variable|string $variable, ?string $type = null, mixed $value = null): static
    {
        if (! TypeGuards::isVariable($variable)) {
            $variable = new Variable($variable, $type, $value);
        }

        $this->variables[$variable->getName()] = $variable;

        return $this;
    }

    public function variables(array $variables): static
    {
        foreach ($variables as $name => $config) {
            if (TypeGuards::isVariable($config)) {
                $this->variable($config);
            } elseif (is_array($config) && isset($config['type'])) {
                $this->variable($name, $config['type'], $config['value'] ?? null);
            } else {
                throw new InvalidArgumentException("Variable '{$name}' must have a 'type' specified or be a Variable instance.");
            }
        }

        return $this;
    }

    public function fragment(Fragment|string $fragment, ?string $onType = null, array|Closure|null $fields = null): static
    {
        if (TypeGuards::isFragment($fragment)) {
            $this->fragments[$fragment->getName()] = $fragment;

            return $this;
        }

        if (is_array($fields)) {
            // Direct array of fields
            $fragmentObject = new Fragment($fragment, $onType, $fields);
        } elseif ($fields instanceof Closure) {
            // Callback to build fields
            $subBuilder = new static;
            $fields($subBuilder);
            $fragmentObject = new Fragment($fragment, $onType, $subBuilder->getFieldsArray());
        } else {
            // No fields
            $fragmentObject = new Fragment($fragment, $onType);
        }

        $this->fragments[$fragment] = $fragmentObject;

        return $this;
    }

    public function fragments(array $fragments): static
    {
        foreach ($fragments as $item) {
            if (is_array($item)) {
                // Handle array format: ['name', 'onType', callback] or ['name', 'onType']
                $name = $item[0] ?? null;
                $onType = $item[1] ?? null;
                $callback = $item[2] ?? null;

                if ($name === null) {
                    throw new InvalidArgumentException('Fragment name is required when using array format');
                }

                $this->fragment($name, $onType, $callback);
            } elseif (TypeGuards::isFragment($item)) {
                // Handle Fragment object
                $this->fragment($item);
            } elseif (is_string($item)) {
                throw new InvalidArgumentException('String fragments require onType parameter. Use array format: [name, onType] or [name, onType, callback]');
            } else {
                throw new InvalidArgumentException('Fragment must be a Fragment object or array format [name, onType, callback?]');
            }
        }

        return $this;
    }

    public function toGraphQL(): string
    {
        $query = '';

        foreach ($this->fragments as $fragment) {
            $query .= $fragment->toDefinitionString()."\n\n";
        }

        $operationHeader = $this->operationType ?? 'query';

        if ($this->operationName !== null && $this->operationName !== '' && $this->operationName !== '0') {
            $operationHeader .= " {$this->operationName}";
        }

        if ($this->variables !== []) {
            $variableDefinitions = [];
            foreach ($this->variables as $variable) {
                $variableDefinitions[] = $variable->toDefinitionString();
            }

            $operationHeader .= '('.implode(', ', $variableDefinitions).')';
        }

        $query .= $operationHeader." {\n";
        $query .= $this->buildFieldsString($this->getFieldsArray(), 1);

        return $query.'}';
    }

    public function getVariableValues(): array
    {
        $values = [];
        foreach ($this->variables as $variable) {
            if ($variable->hasValue()) {
                $values[$variable->getName()] = $variable->getValue();
            }
        }

        return $values;
    }

    public function getVariable(string $name): ?Variable
    {
        return $this->variables[$name] ?? null;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function hasVariable(string $name): bool
    {
        return isset($this->variables[$name]);
    }

    public function getFragment(string $name): ?Fragment
    {
        return $this->fragments[$name] ?? null;
    }

    public function getFragments(): array
    {
        return $this->fragments;
    }

    public function hasFragment(string $name): bool
    {
        return isset($this->fragments[$name]);
    }
}

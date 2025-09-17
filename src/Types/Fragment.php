<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Types;

use Illuminate\Support\Traits\Conditionable;
use Secundo\GraphQL\Concerns\BuildsFieldStrings;
use Secundo\GraphQL\Concerns\HasStringConversion;
use Secundo\GraphQL\Concerns\ManagesDirectives;
use Secundo\GraphQL\Concerns\ManagesFields;
use Stringable;

class Fragment implements Stringable
{
    use BuildsFieldStrings;
    use Conditionable;
    use HasStringConversion;
    use ManagesDirectives;
    use ManagesFields;

    public function __construct(
        protected string $name,
        protected string $onType,
        array $fields = [],
        array $directives = [],
    ) {
        $this->setFields($fields);
        $this->setDirectives($directives);
    }

    public static function create(string $name, string $onType, array $fields = []): static
    {
        return new static($name, $onType, $fields);
    }

    public static function fromArray(array $data): static
    {
        return new static($data['name'], $data['on'], $data['fields']);
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

    public function getUsageName(): string
    {
        return $this->toUsageString();
    }

    public function onType(string $onType): static
    {
        $this->onType = $onType;

        return $this;
    }

    public function getOnType(): string
    {
        return $this->onType;
    }

    public function toDefinitionString(): string
    {
        $definition = "fragment {$this->name} on {$this->onType}";

        // Add directives if any exist
        foreach ($this->getDirectives() as $directive) {
            $definition .= ' @'.$directive->getName();
            if (! empty($directive->getArguments())) {
                $directiveArgs = [];
                foreach ($directive->getArguments() as $argName => $argValue) {
                    $directiveArgs[] = "{$argName}: ".$this->formatArgumentValue($argValue);
                }

                $definition .= '('.implode(', ', $directiveArgs).')';
            }
        }

        $definition .= " {\n";
        $definition .= $this->buildFieldsString($this->fields, 1);

        return $definition.'}';
    }

    /**
     * This method returns the name of the fragment as it should be referenced inside the fields array
     */
    public function toUsageString(): string
    {
        return "...{$this->name}";
    }

    public function toString(): string
    {
        return $this->toDefinitionString();
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'on' => $this->onType,
            'fields' => $this->fields,
            'directives' => $this->getDirectivesArray(),
        ];
    }
}

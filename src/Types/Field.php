<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Types;

use Illuminate\Support\Traits\Conditionable;
use Secundo\GraphQL\Concerns\BuildsFieldStrings;
use Secundo\GraphQL\Concerns\HasStringConversion;
use Secundo\GraphQL\Concerns\ManagesArguments;
use Secundo\GraphQL\Concerns\ManagesDirectives;
use Secundo\GraphQL\Concerns\ManagesFields;
use Stringable;

class Field implements Stringable
{
    use BuildsFieldStrings;
    use Conditionable;
    use HasStringConversion;
    use ManagesArguments;
    use ManagesDirectives;
    use ManagesFields;

    public function __construct(
        protected string $name,
        array $arguments = [],
        array $fields = [],
        protected ?string $alias = null,
        array $directives = [],
    ) {
        $this->setArguments($arguments);
        $this->setFields($fields);
        $this->setDirectives($directives);
    }

    public function toString(): string
    {
        $fieldArray = $this->toArray();
        $result = $this->buildFieldsString([$fieldArray], 0);

        return mb_trim($result);
    }

    public function alias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'arguments' => $this->getArguments(),
            'fields' => $this->getFields(),
            'alias' => $this->alias,
            'directives' => $this->getDirectivesArray(),
        ];
    }

    protected function hasVariable(string $name): bool
    {
        // Field doesn't track variables, so assume all variable references are valid
        return true;
    }
}

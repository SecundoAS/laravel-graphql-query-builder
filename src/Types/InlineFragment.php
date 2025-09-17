<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Types;

use Override;

class InlineFragment extends Field
{
    public function __construct(
        protected string $onType,
        array $fields = [],
        array $directives = []
    ) {
        parent::__construct("... on {$onType}", [], $fields, null, $directives);
    }

    public static function create(string $onType, array $fields = []): static
    {
        return new static($onType, $fields);
    }

    public function getOnType(): string
    {
        return $this->onType;
    }

    public function isInline(): bool
    {
        return true;
    }

    #[Override]
    public function toArray(): array
    {
        $array = parent::toArray();
        $array['on'] = $this->onType;

        return $array;
    }
}

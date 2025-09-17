<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Concerns;

use Secundo\GraphQL\Processors\DirectiveProcessor;
use Secundo\GraphQL\Types\Directive;
use Secundo\GraphQL\Utilities\TypeGuards;

trait ManagesDirectives
{
    /**
     * The directives collection for this instance.
     *
     * @var array<Directive>
     */
    private array $directives = [];

    /**
     * Add a directive to the collection.
     */
    public function directive(string|Directive $directive, array $arguments = []): static
    {
        if (TypeGuards::isDirective($directive)) {
            $this->directives[] = $directive;
        } else {
            $this->directives[] = new Directive($directive, $arguments);
        }

        return $this;
    }

    /**
     * Add multiple directives to the collection.
     */
    public function directives(array $directives): static
    {
        $this->directives = array_merge($this->directives, DirectiveProcessor::process($directives));

        return $this;
    }

    /**
     * Add an @include directive.
     */
    public function include(string|bool $condition): static
    {
        return $this->directive(Directive::include($condition));
    }

    /**
     * Add a @skip directive.
     */
    public function skip(string|bool $condition): static
    {
        return $this->directive(Directive::skip($condition));
    }

    /**
     * Add a @deprecated directive.
     */
    public function deprecated(?string $reason = null): static
    {
        return $this->directive(Directive::deprecated($reason));
    }

    /**
     * Get all directives.
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    /**
     * Check if there are any directives.
     */
    public function hasDirectives(): bool
    {
        return $this->directives !== [];
    }

    /**
     * Check if a specific directive exists.
     */
    public function hasDirective(string $name): bool
    {
        return DirectiveProcessor::hasDirective($this->directives, $name);
    }

    /**
     * Get a specific directive by name.
     */
    public function getDirective(string $name): ?Directive
    {
        return DirectiveProcessor::findByName($this->directives, $name);
    }

    /**
     * Count the number of directives.
     */
    public function directivesCount(): int
    {
        return count($this->directives);
    }

    /**
     * Clear all directives.
     */
    public function clearDirectives(): static
    {
        $this->directives = [];

        return $this;
    }

    /**
     * Set directives directly (replaces all existing directives).
     */
    public function setDirectives(array $directives): static
    {
        $this->directives = DirectiveProcessor::process($directives);

        return $this;
    }

    /**
     * Get directives in array format for GraphQL output.
     */
    public function getDirectivesArray(): array
    {
        return DirectiveProcessor::toArray($this->directives);
    }
}

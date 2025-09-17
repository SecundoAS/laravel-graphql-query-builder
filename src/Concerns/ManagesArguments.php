<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Concerns;

use Closure;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Processors\ArgumentProcessor;
use Secundo\GraphQL\Types\Argument;

trait ManagesArguments
{
    /**
     * The arguments collection for this instance.
     *
     * @var array<Argument>
     */
    private array $arguments = [];

    /**
     * Add multiple arguments to the collection.
     */
    public function arguments(array $arguments): static
    {
        $this->arguments = array_merge($this->arguments, ArgumentProcessor::process($arguments));

        return $this;
    }

    /**
     * Add a single argument to the collection.
     */
    public function argument(string $name, mixed $value): static
    {
        // Process the single argument value
        $processed = ArgumentProcessor::process([$name => $value]);
        $this->arguments = array_merge($this->arguments, $processed);

        return $this;
    }

    /**
     * Add an argument using an ArgumentBuilder callback.
     */
    public function argumentBuilder(string $name, Closure $callback): static
    {
        $builder = ArgumentBuilder::create();
        $callback($builder);

        return $this->argument($name, $builder);
    }

    /**
     * Add a query argument using an ArgumentBuilder callback.
     */
    public function queryBuilder(Closure $callback): static
    {
        return $this->argumentBuilder('query', $callback);
    }

    /**
     * Get all arguments.
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Check if there are any arguments.
     */
    public function hasArguments(): bool
    {
        return ! empty($this->arguments);
    }

    /**
     * Check if a specific argument exists.
     */
    public function hasArgument(string $name): bool
    {
        foreach ($this->arguments as $argument) {
            if ($argument->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get a specific argument by name.
     */
    public function getArgument(string $name): ?object
    {
        foreach ($this->arguments as $argument) {
            if ($argument->getName() === $name) {
                return $argument;
            }
        }

        return null;
    }

    /**
     * Count the number of arguments.
     */
    public function argumentsCount(): int
    {
        return count($this->arguments);
    }

    /**
     * Clear all arguments.
     */
    public function clearArguments(): static
    {
        $this->arguments = [];

        return $this;
    }

    /**
     * Set arguments directly (replaces all existing arguments).
     */
    public function setArguments(array $arguments): static
    {
        $this->arguments = ArgumentProcessor::process($arguments);

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Processors;

use Closure;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Types\Argument;

class ArgumentProcessor
{
    /**
     * Process array of arguments where values can be Closures, ArgumentBuilders, etc.
     */
    public static function process(array $arguments): array
    {
        $processed = [];

        foreach ($arguments as $key => $value) {
            if ($value instanceof ArgumentBuilder) {
                // Keep ArgumentBuilder as-is - it will be processed during GraphQL generation
                $processed[$key] = $value;
            } elseif ($value instanceof Closure) {
                // Execute closure to build ArgumentBuilder
                $builder = ArgumentBuilder::create();
                $value($builder);
                $processed[$key] = $builder;
            } else {
                // Keep other types as-is (string, int, array, etc.)
                $processed[$key] = $value;
            }
        }

        return $processed;
    }

    /**
     * Convert arguments to Argument objects collection
     */
    public static function toArgumentObjects(array $arguments): array
    {
        return Argument::collection($arguments);
    }

    /**
     * Convert Argument objects back to array format
     */
    public static function fromArgumentObjects(array $argumentObjects): array
    {
        $result = [];
        foreach ($argumentObjects as $argument) {
            $result[$argument->getName()] = $argument->getValue();
        }

        return $result;
    }
}

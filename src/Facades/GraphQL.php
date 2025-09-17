<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Facades;

use Illuminate\Support\Facades\Facade;
use Secundo\GraphQL\Builder;

/**
 * @method static Builder query(?string $name = null)
 * @method static Builder mutation(?string $name = null)
 * @method static Builder subscription(?string $name = null)
 * @method static Builder field(string $name, array $arguments = [], array|callable|null $fieldsOrCallback = null)
 * @method static Builder variable(string $name, string $type, mixed $value = null)
 * @method static Builder variables(array $variables)
 * @method static Builder fragment(string $name, string $onType, callable $callback)
 * @method static string toGraphQL()
 * @method static array getVariableValues()
 *
 * @see Builder
 */
class GraphQL extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'graphql-query-builder';
    }
}

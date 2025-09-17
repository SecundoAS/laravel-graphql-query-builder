<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Secundo\GraphQL\Builder query(?string $name = null)
 * @method static \Secundo\GraphQL\Builder mutation(?string $name = null)
 * @method static \Secundo\GraphQL\Builder subscription(?string $name = null)
 * @method static \Secundo\GraphQL\Builder field(string $name, array $arguments = [], array|callable|null $fieldsOrCallback = null)
 * @method static \Secundo\GraphQL\Builder variable(string $name, string $type, mixed $value = null)
 * @method static \Secundo\GraphQL\Builder variables(array $variables)
 * @method static \Secundo\GraphQL\Builder fragment(string $name, string $onType, callable $callback)
 * @method static string toGraphQL()
 * @method static array getVariableValues()
 *
 * @see \Secundo\GraphQL\Builder
 */
class GraphQL extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'graphql-query-builder';
    }
}

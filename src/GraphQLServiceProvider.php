<?php

declare(strict_types=1);

namespace Secundo\GraphQL;

use Illuminate\Support\ServiceProvider;
use Override;

class GraphQLServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton('graphql-query-builder', fn (): GraphQL => new GraphQL);

        $this->app->alias('graphql-query-builder', GraphQL::class);
    }

    public function boot(): void
    {
        // Nothing to boot for a pure query builder
    }

    #[Override]
    public function provides(): array
    {
        return [
            'graphql-query-builder',
            GraphQL::class,
        ];
    }
}

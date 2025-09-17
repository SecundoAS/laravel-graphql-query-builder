<?php

declare(strict_types=1);

namespace Secundo\GraphQL;

use Illuminate\Support\ServiceProvider;

class GraphQLServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('graphql-query-builder', function () {
            return new GraphQL;
        });

        $this->app->alias('graphql-query-builder', GraphQL::class);
    }

    public function boot(): void
    {
        // Nothing to boot for a pure query builder
    }

    public function provides(): array
    {
        return [
            'graphql-query-builder',
            GraphQL::class,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Secundo\GraphQL\Builder;
use Secundo\GraphQL\Facades\GraphQL;
use Secundo\GraphQL\GraphQLServiceProvider;

class GraphQLFacadeTest extends TestCase
{
    protected static $latestResponse = null;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function facade_can_create_query(): void
    {
        $query = GraphQL::query('GetUsers')
            ->field('users', [], ['id', 'name'])
            ->toGraphQL();

        $expected = 'query GetUsers {
  users {
    id
    name
  }
}';

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function facade_can_create_mutation(): void
    {
        $mutation = GraphQL::mutation('CreateUser')
            ->variable('input', 'UserInput!')
            ->field('userCreate', ['input' => '$input'], ['user' => ['id', 'name']])
            ->toGraphQL();

        $expected = 'mutation CreateUser($input: UserInput!) {
  userCreate(input: $input) {
    user {
      id
      name
    }
  }
}';

        $this->assertEquals($expected, $mutation);
    }

    #[Test]
    public function facade_returns_fresh_builder_instances(): void
    {
        $builder1 = GraphQL::query('Query1')->field('users', [], ['id']);
        $builder2 = GraphQL::query('Query2')->field('products', [], ['title']);

        // Should be different instances
        $this->assertNotSame($builder1, $builder2);

        // Should be Builder instances
        $this->assertInstanceOf(Builder::class, $builder1);
        $this->assertInstanceOf(Builder::class, $builder2);

        // Should have different queries
        $this->assertStringContainsString('users', $builder1->toGraphQL());
        $this->assertStringContainsString('products', $builder2->toGraphQL());

        $this->assertStringNotContainsString('products', $builder1->toGraphQL());
        $this->assertStringNotContainsString('users', $builder2->toGraphQL());
    }

    #[Test]
    public function facade_resolves_from_container(): void
    {
        $graphqlInstance = $this->app['graphql-query-builder'];
        $builder = $graphqlInstance->query();

        $this->assertInstanceOf(Builder::class, $builder);
    }

    #[Test]
    public function facade_works_with_complex_queries(): void
    {
        $query = GraphQL::query('GetProductsWithVariables')
            ->variable('first', 'Int', 10)
            ->variable('query', 'String', 'title:test')
            ->field('products', [
                'first' => '$first',
                'query' => '$query',
            ], function ($field): void {
                $field->field('edges', [], function ($field): void {
                    $field->field('node', [], ['id', 'title', 'handle']);
                    $field->field('cursor');
                });
                $field->field('pageInfo', [], ['hasNextPage']);
            })
            ->toGraphQL();

        $this->assertStringContainsString('query GetProductsWithVariables($first: Int, $query: String)', $query);
        $this->assertStringContainsString('products(first: $first, query: $query)', $query);
        $this->assertStringContainsString('edges', $query);
        $this->assertStringContainsString('node', $query);
        $this->assertStringContainsString('pageInfo', $query);
    }

    #[Test]
    public function multiple_facade_calls_dont_interfere(): void
    {
        // Create first query
        $query1 = GraphQL::query('GetUsers')
            ->field('users', [], ['id', 'name']);

        // Create second query
        $query2 = GraphQL::mutation('CreateProduct')
            ->field('productCreate', [], ['product' => ['id']]);

        // First query should still be clean
        $query1String = $query1->toGraphQL();
        $this->assertStringContainsString('GetUsers', $query1String);
        $this->assertStringContainsString('users', $query1String);
        $this->assertStringNotContainsString('productCreate', $query1String);
        $this->assertStringNotContainsString('mutation', $query1String);

        // Second query should be clean too
        $query2String = $query2->toGraphQL();
        $this->assertStringContainsString('CreateProduct', $query2String);
        $this->assertStringContainsString('productCreate', $query2String);
        $this->assertStringNotContainsString('users', $query2String);
        $this->assertStringContainsString('mutation', $query2String);
    }

    protected function getPackageProviders($app): array
    {
        return [GraphQLServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'GraphQL' => GraphQL::class,
        ];
    }
}

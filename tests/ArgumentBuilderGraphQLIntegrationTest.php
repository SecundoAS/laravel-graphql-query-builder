<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Builder;
use Secundo\GraphQL\Types\Field;

class ArgumentBuilderGraphQLIntegrationTest extends TestCase
{
    #[Test]
    public function it_can_use_argument_builder_in_field_query_builder(): void
    {
        $field = new Field('products');
        $field->queryBuilder(function ($query): void {
            $query->where('status', 'active')
                ->whereIn('vendor', ['Nike', 'Adidas'])
                ->where('created_at', '>', '2020-01-01');
        });

        $arguments = $field->getArguments();
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['query']);
        $this->assertEquals('status:active AND vendor:Nike,Adidas AND created_at:>"2020-01-01"', $arguments['query']->toString());
    }

    #[Test]
    public function it_can_use_argument_builder_for_custom_arguments(): void
    {
        $field = new Field('products');
        $field->argumentBuilder('filter', function ($builder): void {
            $builder->where('price', '<=', 100)
                ->where('available', true)
                ->whereNot('discontinued', true);
        });

        $arguments = $field->getArguments();
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['filter']);
        $this->assertEquals('price:<=100 AND available:true AND NOT discontinued:true', $arguments['filter']->toString());
    }

    #[Test]
    public function it_can_use_field_with_query_in_builder(): void
    {
        $builder = new Builder;
        $builder->fieldWithQuery('products', function ($query): void {
            $query->where('status', 'active')
                ->where(function ($subQuery): void {
                    $subQuery->where('vendor', 'Nike')
                        ->orWhere('vendor', 'Adidas');
                });
        }, ['id', 'title', 'vendor']);

        $fields = $builder->getFieldsArray();
        $this->assertCount(1, $fields);

        $productField = $fields[0];
        $this->assertEquals('products', $productField['name']);
        $this->assertArrayHasKey('query', $productField['arguments']);
        $this->assertEquals('status:active AND (vendor:Nike OR vendor:Adidas)', $productField['arguments']['query']->toString());
        $this->assertCount(3, $productField['fields']);
    }

    #[Test]
    public function it_can_use_argument_builder_in_graphql_builder(): void
    {
        $builder = new Builder;
        $builder->fieldWithArgumentBuilder('products', 'query', function ($query): void {
            $query->whereIn('status', ['active', 'draft'])
                ->where('created_at', '>', 'now')
                ->search('iPhone OR Samsung');
        });

        $fields = $builder->getFieldsArray();
        $this->assertCount(1, $fields);

        $productField = $fields[0];
        $this->assertEquals('products', $productField['name']);
        $this->assertInstanceOf(ArgumentBuilder::class, $productField['arguments']['query']);
        $this->assertEquals('status:active,draft AND created_at:>now AND iPhone OR Samsung', $productField['arguments']['query']->toString());
        $this->assertStringContainsString('products(query: "status:active,draft AND created_at:>now AND iPhone OR Samsung")', $builder->toGraphQL());
    }

    #[Test]
    public function it_generates_correct_graphql_with_argument_builder(): void
    {
        $builder = new Builder;
        $builder->query()
            ->fieldWithQuery('products', function ($query): void {
                $query->where('status', 'active')
                    ->where('vendor', 'Nike')
                    ->where('price', '<=', 200);
            }, ['id', 'title', 'price'])
            ->field('shop', [], ['name', 'domain']);

        $graphql = $builder->toGraphQL();

        // The ArgumentBuilder should be converted to a quoted string in the GraphQL output
        $this->assertStringContainsString('products(query: "status:active AND vendor:Nike AND price:<=200")', $graphql);
        $this->assertStringContainsString('id', $graphql);
        $this->assertStringContainsString('title', $graphql);
        $this->assertStringContainsString('price', $graphql);
        $this->assertStringContainsString('shop', $graphql);
    }

    #[Test]
    public function it_can_combine_regular_arguments_with_argument_builder(): void
    {
        $field = new Field('products');
        $field->argument('first', 10)
            ->argument('sortKey', 'CREATED_AT')
            ->queryBuilder(function ($query): void {
                $query->where('status', 'active')
                    ->where('featured', true);
            })
            ->argument('reverse', false);

        $arguments = $field->getArguments();
        $this->assertEquals(10, $arguments['first']);
        $this->assertEquals('CREATED_AT', $arguments['sortKey']);
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['query']);
        $this->assertEquals('status:active AND featured:true', $arguments['query']->toString());
        $this->assertFalse($arguments['reverse']);
    }

    #[Test]
    public function it_works_with_complex_nested_queries(): void
    {
        $builder = new Builder;
        $builder->query()
            ->fieldWithQuery('products', function ($query): void {
                $query->where('created_at', '>', '2020-01-01')
                    ->where(function ($subQuery): void {
                        $subQuery->where('status', 'active')
                            ->orWhere('status', 'draft');
                    })
                    ->where(function ($subQuery): void {
                        $subQuery->where('vendor', 'Nike')
                            ->orWhere('vendor', 'Adidas')
                            ->orWhere(function ($deepQuery): void {
                                $deepQuery->where('product_type', 'shoes')
                                    ->where('price', '<=', 150);
                            });
                    })
                    ->whereNot('discontinued', true)
                    ->search('running OR basketball');
            }, function ($field): void {
                $field->field('id')
                    ->field('title')
                    ->field('vendor')
                    ->field('variants', ['first' => 5], ['id', 'price', 'sku']);
            });

        $graphql = $builder->toGraphQL();

        // Should contain the complex nested query (now properly quoted)
        $this->assertStringContainsString('products(query: "', $graphql);
        $this->assertStringContainsString('created_at:>\\"2020-01-01\\"', $graphql);
        $this->assertStringContainsString('(status:active OR status:draft)', $graphql);
        $this->assertStringContainsString('NOT discontinued:true', $graphql);
        $this->assertStringContainsString('running OR basketball', $graphql);

        // Should contain the nested fields
        $this->assertStringContainsString('variants(first: 5)', $graphql);
        $this->assertStringContainsString('id', $graphql);
        $this->assertStringContainsString('title', $graphql);
        $this->assertStringContainsString('sku', $graphql);
        $this->assertStringContainsString('sku', $graphql);
        $this->assertStringContainsString(
            'products(query: "created_at:>\"2020-01-01\" AND (status:active OR status:draft) AND (vendor:Nike OR vendor:Adidas OR (product_type:shoes AND price:<=150)) AND NOT discontinued:true AND running OR basketball")',
            $graphql,
        );

    }

    #[Test]
    public function it_supports_method_chaining_with_argument_builders(): void
    {
        $field = new Field('products')
            ->argument('first', 20)
            ->queryBuilder(function ($query): void {
                $query->where('status', 'active');
            })
            ->argumentBuilder('filter', function ($builder): void {
                $builder->where('price', '>=', 10)
                    ->where('price', '<=', 100);
            })
            ->argument('sortKey', 'PRICE');

        $arguments = $field->getArguments();
        $this->assertEquals(20, $arguments['first']);
        $this->assertEquals('status:active', $arguments['query']->toString());
        $this->assertEquals('price:>=10 AND price:<=100', $arguments['filter']->toString());
        $this->assertEquals('PRICE', $arguments['sortKey']);
        $this->assertStringContainsString(
            'products(first: 20, query: "status:active", filter: "price:>=10 AND price:<=100", sortKey: "PRICE")',
            (string) $field,
        );
    }

    #[Test]
    public function it_can_create_shopify_style_product_queries(): void
    {
        $builder = new Builder;
        $builder->query()
            ->fieldWithQuery('products', function ($query): void {
                // Example: Find Nike or Adidas shoes under $200, created after 2020
                $query->where('created_at', '>', '2020-01-01')
                    ->where('product_type', 'shoes')
                    ->where('price', '<=', 200)
                    ->where(function ($subQuery): void {
                        $subQuery->where('vendor', 'Nike')
                            ->orWhere('vendor', 'Adidas');
                    })
                    ->whereNot('tag', 'discontinued')
                    ->search('running OR basketball OR athletic');
            }, function ($field): void {
                $field->field('id')
                    ->field('title')
                    ->field('vendor')
                    ->field('productType')
                    ->field('tags')
                    ->field('priceRange', [], function ($priceField): void {
                        $priceField->field('minVariantPrice', [], ['amount', 'currencyCode'])
                            ->field('maxVariantPrice', [], ['amount', 'currencyCode']);
                    });
            });

        $graphql = $builder->toGraphQL();

        // Verify the query structure
        $this->assertStringContainsString('query {', $graphql);
        $this->assertStringContainsString('products(query:', $graphql);
        $this->assertStringContainsString('created_at:>\\"2020-01-01\\"', $graphql);
        $this->assertStringContainsString('product_type:shoes', $graphql);
        $this->assertStringContainsString('price:<=200', $graphql);
        $this->assertStringContainsString('(vendor:Nike OR vendor:Adidas)', $graphql);
        $this->assertStringContainsString('NOT tag:discontinued', $graphql);
        $this->assertStringContainsString('running OR basketball OR athletic', $graphql);

        // Verify the field structure
        $this->assertStringContainsString('priceRange {', $graphql);
        $this->assertStringContainsString('minVariantPrice {', $graphql);
        $this->assertStringContainsString('amount', $graphql);
        $this->assertStringContainsString('currencyCode', $graphql);
    }
}

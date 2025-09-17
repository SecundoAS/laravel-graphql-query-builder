<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Builder;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\InlineFragment;
use Secundo\GraphQL\Types\Variable;

class BuilderTest extends TestCase
{
    #[Test]
    public function it_can_create_simple_query(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('products', [], ['id', 'title'])
            ->toGraphQL();

        $expected = "query {\n  products {\n    id\n    title\n  }\n}";
        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_create_query_with_arguments(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('product', ['id' => 'gid://shopify/Product/123'], ['id', 'title'])
            ->toGraphQL();

        $expected = "query {\n  product(id: \"gid://shopify/Product/123\") {\n    id\n    title\n  }\n}";
        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_create_nested_fields(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('products', [], function (Field $field): void {
                $field->field('edges', [], function (Field $field): void {
                    $field->field('node', [], ['id', 'title']);
                });
            })
            ->toGraphQL();

        $expected = "query {\n  products {\n    edges {\n      node {\n        id\n        title\n      }\n    }\n  }\n}";
        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_create_query_with_variables(): void
    {
        $builder = new Builder;
        $query = $builder->query('GetProduct')
            ->variable('id', 'ID!')
            ->field('product', ['id' => '$id'], ['id', 'title'])
            ->toGraphQL();

        $expected = "query GetProduct(\$id: ID!) {\n  product(id: \$id) {\n    id\n    title\n  }\n}";
        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_get_variable_values(): void
    {
        $builder = new Builder;
        $builder->variable('id', 'ID!', 'gid://shopify/Product/123')
            ->variable('first', 'Int', 10);

        $values = $builder->getVariableValues();
        $expected = [
            'id' => 'gid://shopify/Product/123',
            'first' => 10,
        ];

        $this->assertEquals($expected, $values);
    }

    #[Test]
    public function it_can_filter_arguments_with_variables(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->variable('productId', 'ID!')
            ->field('product', ['id' => '$productId', 'invalidVar' => '$nonExistent'], ['id', 'title'])
            ->toGraphQL();

        $expected = "query(\$productId: ID!) {\n  product(id: \$productId) {\n    id\n    title\n  }\n}";
        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_create_mutation(): void
    {
        $builder = new Builder;
        $query = $builder->mutation('ProductUpdate')
            ->variable('id', 'ID!')
            ->variable('input', 'ProductInput!')
            ->field('productUpdate', ['id' => '$id', 'input' => '$input'], function (Field $field): void {
                $field->field('product', [], ['id', 'title'])
                    ->field('userErrors', [], ['field', 'message']);
            })
            ->toGraphQL();

        $expected = "mutation ProductUpdate(\$id: ID!, \$input: ProductInput!) {\n  productUpdate(id: \$id, input: \$input) {\n    product {\n      id\n      title\n    }\n    userErrors {\n      field\n      message\n    }\n  }\n}";
        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_handle_fragments(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->fragment('ProductFields', 'Product', function (Builder $builder): void {
                $builder->fields(['id', 'title', 'handle']);
            })
            ->field('products', [], function (Field $field): void {
                $field->field('edges', [], function (Field $field): void {
                    $field->field('node', [], ['...ProductFields']);
                });
            })
            ->toGraphQL();

        $this->assertStringContainsString('fragment ProductFields on Product', $query);
        $this->assertStringContainsString('...ProductFields', $query);
    }

    #[Test]
    public function it_can_format_different_argument_types(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('products', [
                'first' => 10,
                'query' => 'title:test',
                'reverse' => true,
                'tags' => ['new', 'featured'],
                'metafields' => ['namespace' => 'custom', 'key' => 'data'],
            ], ['id'])
            ->toGraphQL();

        $this->assertStringContainsString('first: 10', $query);
        $this->assertStringContainsString('query: "title:test"', $query);
        $this->assertStringContainsString('reverse: true', $query);
        $this->assertStringContainsString('tags: ["new", "featured"]', $query);
        $this->assertStringContainsString('metafields: {namespace: "custom", key: "data"}', $query);
    }

    #[Test]
    public function it_can_use_select_method(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->fields([
                'shop' => ['id', 'name', 'domain'],
                'products' => function (Field $field): void {
                    $field->fields(['id', 'title']);
                },
            ])
            ->toGraphQL();

        $this->assertStringContainsString('shop {', $query);
        $this->assertStringContainsString('id', $query);
        $this->assertStringContainsString('name', $query);
        $this->assertStringContainsString('domain', $query);
        $this->assertStringContainsString('products {', $query);
        $this->assertStringContainsString('title', $query);
    }

    #[Test]
    public function it_converts_to_string_returns_graphql(): void
    {
        $builder = new Builder;
        $builder->query()->field('shop', [], ['id', 'name']);

        $string = (string) $builder;
        $graphql = $builder->toGraphQL();

        $this->assertEquals($graphql, $string);
    }

    #[Test]
    public function it_can_create_inline_fragments(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('nodes', [], function (Field $field): void {
                $field->field('id')
                    ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('title')
                            ->field('handle');
                    })
                    ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('name')
                            ->field('email');
                    });
            })
            ->toGraphQL();

        $this->assertStringContainsString('nodes', $query);
        $this->assertStringContainsString('... on Product', $query);
        $this->assertStringContainsString('... on Order', $query);
        $this->assertStringContainsString('title', $query);
        $this->assertStringContainsString('handle', $query);
        $this->assertStringContainsString('name', $query);
        $this->assertStringContainsString('email', $query);
    }

    #[Test]
    public function it_can_nest_inline_fragments(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('search', [], function (Field $field): void {
                $field->field('edges', [], function (Field $field): void {
                    $field->field('node', [], function (Field $field): void {
                        $field->field('id')
                            ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                                $inlineFragment->field('title')
                                    ->field('vendor');
                            })
                            ->inlineFragment('Collection', function (InlineFragment $inlineFragment): void {
                                $inlineFragment->field('title')
                                    ->field('description');
                            });
                    });
                });
            })
            ->toGraphQL();

        $this->assertStringContainsString('search', $query);
        $this->assertStringContainsString('edges', $query);
        $this->assertStringContainsString('node', $query);
        $this->assertStringContainsString('... on Product', $query);
        $this->assertStringContainsString('... on Collection', $query);
        $this->assertStringContainsString('vendor', $query);
        $this->assertStringContainsString('description', $query);
    }

    #[Test]
    public function it_handles_inline_fragment_with_arguments(): void
    {
        $builder = new Builder;
        $query = $builder->query('GetNodes')
            ->variable('ids', '[ID!]!')
            ->field('nodes', ['ids' => '$ids'], function (Field $field): void {
                $field->field('id')
                    ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('name')
                            ->field('email')
                            ->field('lineItems', ['first' => 10], function (Field $field): void {
                                $field->field('edges', [], function (Field $field): void {
                                    $field->field('node', [], ['id', 'title']);
                                });
                            });
                    });
            })
            ->toGraphQL();

        $this->assertStringContainsString('query GetNodes($ids: [ID!]!)', $query);
        $this->assertStringContainsString('nodes(ids: $ids)', $query);
        $this->assertStringContainsString('... on Order', $query);
        $this->assertStringContainsString('lineItems(first: 10)', $query);
    }

    #[Test]
    public function it_can_use_variables_method(): void
    {
        $builder = new Builder;
        $query = $builder->query('GetProducts')
            ->variables([
                'first' => ['type' => 'Int', 'value' => 25],
                'query' => ['type' => 'String', 'value' => 'product_type:shirt'],
                'reverse' => ['type' => 'Boolean', 'value' => true],
                'sortKey' => ['type' => 'ProductSortKeys'],  // No default value
            ])
            ->field('products', [
                'first' => '$first',
                'query' => '$query',
                'reverse' => '$reverse',
                'sortKey' => '$sortKey',
            ], ['id', 'title'])
            ->toGraphQL();

        $expected = "query GetProducts(\$first: Int, \$query: String, \$reverse: Boolean, \$sortKey: ProductSortKeys) {\n  products(first: \$first, query: \$query, reverse: \$reverse, sortKey: \$sortKey) {\n    id\n    title\n  }\n}";
        $this->assertEquals($expected, $query);

        $variables = $builder->getVariableValues();
        $expected = [
            'first' => 25,
            'query' => 'product_type:shirt',
            'reverse' => true,
        ];
        $this->assertEquals($expected, $variables);
        $this->assertArrayNotHasKey('sortKey', $variables); // No default value
    }

    #[Test]
    public function it_throws_exception_for_invalid_variables_structure(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Variable 'invalidVar' must have a 'type' specified or be a Variable instance.");

        $builder = new Builder;
        $builder->variables([
            'validVar' => ['type' => 'String', 'value' => 'test'],
            'invalidVar' => 'missing_type_structure',  // Invalid - should be array with 'type' key
        ]);
    }

    #[Test]
    public function it_can_get_individual_variables(): void
    {
        $builder = new Builder;
        $builder->variable('first', 'Int', 10)
            ->variable('query', 'String', 'title:shirt');

        $firstVar = $builder->getVariable('first');
        $this->assertNotNull($firstVar);
        $this->assertEquals('first', $firstVar->getName());
        $this->assertEquals('Int', $firstVar->getType());
        $this->assertEquals(10, $firstVar->getValue());

        $nonExistent = $builder->getVariable('nonexistent');
        $this->assertNull($nonExistent);

        $this->assertTrue($builder->hasVariable('first'));
        $this->assertFalse($builder->hasVariable('nonexistent'));
    }

    #[Test]
    public function it_can_get_all_variables(): void
    {
        $builder = new Builder;
        $builder->variable('first', 'Int', 10)
            ->variable('query', 'String', 'title:shirt');

        $variables = $builder->getVariables();
        $this->assertCount(2, $variables);
        $this->assertArrayHasKey('first', $variables);
        $this->assertArrayHasKey('query', $variables);
        $this->assertInstanceOf(Variable::class, $variables['first']);
    }

    #[Test]
    public function it_can_get_individual_fragments(): void
    {
        $builder = new Builder;
        $builder->fragment('ProductFields', 'Product', function ($builder): void {
            $builder->fields(['id', 'title']);
        });

        $fragment = $builder->getFragment('ProductFields');
        $this->assertNotNull($fragment);
        $this->assertEquals('ProductFields', $fragment->getName());
        $this->assertEquals('Product', $fragment->getOnType());

        $nonExistent = $builder->getFragment('nonexistent');
        $this->assertNull($nonExistent);

        $this->assertTrue($builder->hasFragment('ProductFields'));
        $this->assertFalse($builder->hasFragment('nonexistent'));
    }

    #[Test]
    public function it_can_get_all_fragments(): void
    {
        $builder = new Builder;
        $builder->fragment('ProductFields', 'Product', function ($builder): void {
            $builder->fields(['id', 'title']);
        })
            ->fragment('OrderFields', 'Order', function ($builder): void {
                $builder->fields(['id', 'name']);
            });

        $fragments = $builder->getFragments();
        $this->assertCount(2, $fragments);
        $this->assertArrayHasKey('ProductFields', $fragments);
        $this->assertArrayHasKey('OrderFields', $fragments);
        $this->assertInstanceOf(Fragment::class, $fragments['ProductFields']);
    }

    #[Test]
    public function it_can_add_multiple_fragments_at_once(): void
    {
        $builder = new Builder;

        $productFragment = new Fragment('ProductFields', 'Product');
        $productFragment->fields(['id', 'title', 'handle']);

        $orderFragment = new Fragment('OrderFields', 'Order');
        $orderFragment->fields(['id', 'name', 'createdAt']);

        $userFragment = new Fragment('UserFields', 'User');
        $userFragment->fields(['id', 'email', 'displayName']);

        $builder->fragments([$productFragment, $orderFragment, $userFragment]);

        $fragments = $builder->getFragments();
        $this->assertCount(3, $fragments);
        $this->assertArrayHasKey('ProductFields', $fragments);
        $this->assertArrayHasKey('OrderFields', $fragments);
        $this->assertArrayHasKey('UserFields', $fragments);
        $this->assertSame($productFragment, $fragments['ProductFields']);
        $this->assertSame($orderFragment, $fragments['OrderFields']);
        $this->assertSame($userFragment, $fragments['UserFields']);
    }

    #[Test]
    public function it_throws_exception_for_invalid_fragments_input(): void
    {
        $builder = new Builder;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('String fragments require onType parameter. Use array format: [name, onType] or [name, onType, callback]');

        $builder->fragments(['not-a-fragment', 'another-invalid']);
    }

    #[Test]
    public function it_allows_fragments_method_to_be_chained(): void
    {
        $builder = new Builder;

        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->fields(['id', 'title']);

        $result = $builder->fragments([$fragment])
            ->query('TestQuery')
            ->field('products', [], ['...ProductFields']);

        $this->assertSame($builder, $result);
        $this->assertTrue($builder->hasFragment('ProductFields'));
    }

    #[Test]
    public function it_handles_mixed_fragments_input_types(): void
    {
        $builder = new Builder;

        // Create a Fragment object
        $fragmentObject = new Fragment('ProductFields', 'Product');
        $fragmentObject->fields(['id', 'title', 'handle']);

        // Mix of Fragment objects, arrays, and strings
        $builder->fragments([
            $fragmentObject,
            ['OrderFields', 'Order', function ($builder): void {
                $builder->fields(['id', 'name', 'createdAt']);
            }],
            ['UserFields', 'User'], // Without callback
        ]);

        $fragments = $builder->getFragments();
        $this->assertCount(3, $fragments);

        // Verify Fragment object was added correctly
        $this->assertArrayHasKey('ProductFields', $fragments);
        $this->assertSame($fragmentObject, $fragments['ProductFields']);

        // Verify array with callback was processed
        $this->assertArrayHasKey('OrderFields', $fragments);
        $this->assertEquals('OrderFields', $fragments['OrderFields']->getName());
        $this->assertEquals('Order', $fragments['OrderFields']->getOnType());

        // Verify array without callback was processed
        $this->assertArrayHasKey('UserFields', $fragments);
        $this->assertEquals('UserFields', $fragments['UserFields']->getName());
        $this->assertEquals('User', $fragments['UserFields']->getOnType());
    }

    #[Test]
    public function it_throws_exception_for_invalid_fragments_array_format(): void
    {
        $builder = new Builder;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fragment name is required when using array format');

        $builder->fragments([
            [], // Empty array - should throw exception
        ]);
    }

    #[Test]
    public function it_converts_regular_fragment_to_inline_fragment(): void
    {
        // Create a regular (non-inline) Fragment
        $regularFragment = new Fragment('ProductFields', 'Product', ['id', 'title', 'handle']);

        $builder = new Builder;
        $query = $builder->query()
            ->field('search', [], function (Field $field) use ($regularFragment): void {
                $field->field('edges', [], function (Field $field) use ($regularFragment): void {
                    $field->field('node', [], function (Field $field) use ($regularFragment): void {
                        $field->field('id')
                            ->inlineFragment($regularFragment); // Should convert to inline
                    });
                });
            })
            ->toGraphQL();

        // Should contain the inline fragment syntax, not fragment reference
        $this->assertStringContainsString('... on Product', $query);
        $this->assertStringContainsString('id', $query);
        $this->assertStringContainsString('title', $query);
        $this->assertStringContainsString('handle', $query);

        // Should NOT contain fragment definition at top level
        $this->assertStringNotContainsString('fragment ProductFields on Product', $query);
    }

    #[Test]
    public function it_casts_builder_with_inline_fragments_to_string_correctly(): void
    {
        $builder = new Builder;
        $builder->query('SearchProducts')
            ->variable('query', 'String')
            ->field('search', ['query' => '$query'], function (Field $field): void {
                $field->field('edges', [], function (Field $field): void {
                    $field->field('node', [], function (Field $field): void {
                        $field->field('id')
                            ->field('__typename')
                            ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                                $inlineFragment->field('title')
                                    ->field('handle')
                                    ->field('vendor');
                            })
                            ->inlineFragment('Collection', function (InlineFragment $inlineFragment): void {
                                $inlineFragment->field('title')
                                    ->field('description')
                                    ->field('handle');
                            });
                    });
                });
            });

        $expected = 'query SearchProducts($query: String) {
  search(query: $query) {
    edges {
      node {
        id
        __typename
        ... on Product {
          title
          handle
          vendor
        }
        ... on Collection {
          title
          description
          handle
        }
      }
    }
  }
}';

        $this->assertEquals($expected, (string) $builder);
    }

    #[Test]
    public function it_casts_builder_with_mutation_and_inline_fragments_to_string_correctly(): void
    {
        $builder = new Builder;
        $builder->mutation('UpdateNode')
            ->variable('id', 'ID!')
            ->variable('input', 'NodeInput!')
            ->field('nodeUpdate', ['id' => '$id', 'input' => '$input'], function (Field $field): void {
                $field->field('node', [], function (Field $field): void {
                    $field->field('id')
                        ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                            $inlineFragment->field('title')
                                ->field('updatedAt');
                        })
                        ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                            $inlineFragment->field('name')
                                ->field('processedAt');
                        });
                })
                    ->field('userErrors', [], ['field', 'message']);
            });

        $expected = 'mutation UpdateNode($id: ID!, $input: NodeInput!) {
  nodeUpdate(id: $id, input: $input) {
    node {
      id
      ... on Product {
        title
        updatedAt
      }
      ... on Order {
        name
        processedAt
      }
    }
    userErrors {
      field
      message
    }
  }
}';

        $this->assertEquals($expected, (string) $builder);
    }

    #[Test]
    public function it_casts_builder_with_nested_inline_fragments_to_string_correctly(): void
    {
        $builder = new Builder;
        $builder->query()
            ->field('nodes', ['ids' => ['gid://shopify/Product/1', 'gid://shopify/Order/1']], function (Field $field): void {
                $field->field('id')
                    ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('title')
                            ->field('variants', ['first' => 3], function (Field $field): void {
                                $field->field('id')
                                    ->field('price')
                                    ->field('inventoryItem', [], function (Field $field): void {
                                        $field->field('id')
                                            ->field('tracked');
                                    });
                            });
                    })
                    ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('name')
                            ->field('lineItems', ['first' => 5], ['id', 'title', 'quantity']);
                    })
                    ->inlineFragment('Customer'); // Empty inline fragment
            });

        $expected = 'query {
  nodes(ids: ["gid://shopify/Product/1", "gid://shopify/Order/1"]) {
    id
    ... on Product {
      title
      variants(first: 3) {
        id
        price
        inventoryItem {
          id
          tracked
        }
      }
    }
    ... on Order {
      name
      lineItems(first: 5) {
        id
        title
        quantity
      }
    }
    ... on Customer
  }
}';

        $this->assertEquals($expected, (string) $builder);
    }

    #[Test]
    public function it_casts_builder_with_mixed_fragments_and_regular_fragments_to_string_correctly(): void
    {
        $builder = new Builder;
        $builder->query()
            ->fragment('ProductFields', 'Product', function (Builder $builder): void {
                $builder->fields(['id', 'title', 'handle']);
            })
            ->field('search', [], function (Field $field): void {
                $field->field('edges', [], function (Field $field): void {
                    $field->field('node', [], function (Field $field): void {
                        $field->field('id')
                            ->field('...ProductFields') // Named fragment reference
                            ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                                $inlineFragment->field('name')
                                    ->field('email');
                            });
                    });
                });
            });

        $graphql = (string) $builder;

        // Should contain both fragment definition and inline fragment
        $this->assertStringContainsString('fragment ProductFields on Product', $graphql);
        $this->assertStringContainsString('...ProductFields', $graphql);
        $this->assertStringContainsString('... on Order', $graphql);
        $this->assertStringContainsString('name', $graphql);
        $this->assertStringContainsString('email', $graphql);

        // Verify the structure is correct
        $this->assertStringContainsString('query {', $graphql);
        $this->assertStringContainsString('search {', $graphql);
        $this->assertStringContainsString('edges {', $graphql);
        $this->assertStringContainsString('node {', $graphql);
    }
}

<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\InlineFragment;

class FieldInlineFragmentTest extends TestCase
{
    #[Test]
    public function it_can_add_inline_fragment_with_string(): void
    {
        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('title')
                    ->field('handle');
            });

        $fields = $field->getFields();
        $this->assertCount(2, $fields);

        // First field should be 'id'
        $this->assertEquals('id', $fields[0]['name']);

        // Second field should be the inline fragment
        $this->assertEquals('... on Product', $fields[1]['name']);
        $this->assertEquals('Product', $fields[1]['on']);
        $this->assertCount(2, $fields[1]['fields']);
    }

    #[Test]
    public function it_can_add_inline_fragment_with_fragment_object(): void
    {
        $inlineFragment = new InlineFragment('Product');
        $inlineFragment->field('title')
            ->field('handle')
            ->field('vendor');

        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment($inlineFragment);

        $fields = $field->getFields();
        $this->assertCount(2, $fields);

        // Second field should be the inline fragment
        $this->assertEquals('... on Product', $fields[1]['name']);
        $this->assertEquals('Product', $fields[1]['on']);
        $this->assertCount(3, $fields[1]['fields']);
    }

    #[Test]
    public function it_converts_regular_fragment_to_inline_fragment(): void
    {
        $regularFragment = new Fragment('ProductFields', 'Product');
        $regularFragment->field('title')
            ->field('handle');

        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment($regularFragment);

        $fields = $field->getFields();
        $this->assertCount(2, $fields);

        // Should be converted to inline fragment
        $this->assertEquals('... on Product', $fields[1]['name']);
        $this->assertEquals('Product', $fields[1]['on']);
        $this->assertCount(2, $fields[1]['fields']);
    }

    #[Test]
    public function it_can_add_multiple_inline_fragments(): void
    {
        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('title')
                    ->field('handle');
            })
            ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name')
                    ->field('email');
            });

        $fields = $field->getFields();
        $this->assertCount(3, $fields);

        // First field should be 'id'
        $this->assertEquals('id', $fields[0]['name']);

        // Second field should be Product inline fragment
        $this->assertEquals('... on Product', $fields[1]['name']);
        $this->assertEquals('Product', $fields[1]['on']);

        // Third field should be Order inline fragment
        $this->assertEquals('... on Order', $fields[2]['name']);
        $this->assertEquals('Order', $fields[2]['on']);
    }

    #[Test]
    public function it_can_add_inline_fragment_without_callback(): void
    {
        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment('Product'); // No callback

        $fields = $field->getFields();
        $this->assertCount(2, $fields);

        // Second field should be empty inline fragment
        $this->assertEquals('... on Product', $fields[1]['name']);
        $this->assertEquals('Product', $fields[1]['on']);
        $this->assertEmpty($fields[1]['fields']);
    }

    #[Test]
    public function it_renders_inline_fragments_in_graphql_output(): void
    {
        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('title')
                    ->field('handle');
            })
            ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name')
                    ->field('email');
            });

        $graphql = (string) $field;

        $this->assertStringContainsString('nodes', $graphql);
        $this->assertStringContainsString('id', $graphql);
        $this->assertStringContainsString('... on Product', $graphql);
        $this->assertStringContainsString('... on Order', $graphql);
        $this->assertStringContainsString('title', $graphql);
        $this->assertStringContainsString('handle', $graphql);
        $this->assertStringContainsString('name', $graphql);
        $this->assertStringContainsString('email', $graphql);
    }

    #[Test]
    public function it_can_nest_inline_fragments(): void
    {
        $field = new Field('search');
        $field->field('edges', [], function (Field $field): void {
            $field->field('node', [], function (Field $field): void {
                $field->field('id')
                    ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('title')
                            ->field('variants', ['first' => 5], ['id', 'price']);
                    })
                    ->inlineFragment('Collection', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('title')
                            ->field('description');
                    });
            });
        });

        $graphql = (string) $field;

        $this->assertStringContainsString('search', $graphql);
        $this->assertStringContainsString('edges', $graphql);
        $this->assertStringContainsString('node', $graphql);
        $this->assertStringContainsString('... on Product', $graphql);
        $this->assertStringContainsString('... on Collection', $graphql);
        $this->assertStringContainsString('variants(first: 5)', $graphql);
        $this->assertStringContainsString('price', $graphql);
        $this->assertStringContainsString('description', $graphql);
    }

    #[Test]
    public function it_supports_method_chaining_with_inline_fragments(): void
    {
        $field = (new Field('nodes'))
            ->field('id')
            ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('title');
            })
            ->field('__typename')
            ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name');
            });

        $fields = $field->getFields();
        $this->assertCount(4, $fields);

        $this->assertEquals('id', $fields[0]['name']);
        $this->assertEquals('... on Product', $fields[1]['name']);
        $this->assertEquals('__typename', $fields[2]['name']);
        $this->assertEquals('... on Order', $fields[3]['name']);
    }

    #[Test]
    public function it_handles_empty_inline_fragments_correctly(): void
    {
        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment('Product'); // No fields added

        $graphql = (string) $field;

        $this->assertStringContainsString('nodes', $graphql);
        $this->assertStringContainsString('id', $graphql);
        $this->assertStringContainsString('... on Product', $graphql);
        // Empty inline fragments should NOT have braces
        $this->assertStringNotContainsString('... on Product {', $graphql);
    }

    #[Test]
    public function it_casts_field_with_inline_fragments_to_string_correctly(): void
    {
        $field = new Field('search');
        $field->field('edges', [], function (Field $field): void {
            $field->field('node', [], function (Field $field): void {
                $field->field('id')
                    ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('title')
                            ->field('handle')
                            ->field('vendor');
                    })
                    ->inlineFragment('Collection', function (InlineFragment $inlineFragment): void {
                        $inlineFragment->field('title')
                            ->field('description');
                    });
            });
        });

        $expected = 'search {
  edges {
    node {
      id
      ... on Product {
        title
        handle
        vendor
      }
      ... on Collection {
        title
        description
      }
    }
  }
}';

        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_casts_field_with_mixed_fields_and_inline_fragments_to_string_correctly(): void
    {
        $field = new Field('products', ['first' => 10]);
        $field->field('edges', [], function (Field $field): void {
            $field->field('cursor')
                ->field('node', [], function (Field $field): void {
                    $field->field('id')
                        ->field('__typename')
                        ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                            $inlineFragment->field('title')
                                ->field('handle');
                        })
                        ->field('createdAt')
                        ->inlineFragment('DigitalProduct', function (InlineFragment $inlineFragment): void {
                            $inlineFragment->field('downloadUrl');
                        });
                });
        });

        $expected = 'products(first: 10) {
  edges {
    cursor
    node {
      id
      __typename
      ... on Product {
        title
        handle
      }
      createdAt
      ... on DigitalProduct {
        downloadUrl
      }
    }
  }
}';

        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_casts_field_with_empty_and_populated_inline_fragments_to_string_correctly(): void
    {
        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment('Product') // Empty
            ->inlineFragment('Order', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name')
                    ->field('email');
            })
            ->inlineFragment('User') // Empty
            ->field('__typename');

        $expected = 'nodes {
  id
  ... on Product
  ... on Order {
    name
    email
  }
  ... on User
  __typename
}';

        $this->assertEquals($expected, (string) $field);
    }
}

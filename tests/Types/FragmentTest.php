<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\InlineFragment;
use Stringable;

class FragmentTest extends TestCase
{
    #[Test]
    public function it_can_create_fragment(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');

        $this->assertEquals('ProductFields', $fragment->getName());
        $this->assertEquals('Product', $fragment->getOnType());
        $this->assertEquals([], $fragment->getFields());
    }

    #[Test]
    public function it_can_add_fields(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->fields(['id', 'title', 'handle']);

        $fields = $fragment->getFields();
        $fieldNames = array_map(fn (array $field) => $field['name'], $fields);
        $this->assertEquals(['id', 'title', 'handle'], $fieldNames);
    }

    #[Test]
    public function it_can_add_single_field(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')
            ->field('title')
            ->field('handle');

        $this->assertEquals(['id', 'title', 'handle'], array_map(
            fn (Field $field): string => (string) $field,
            $fragment->getRawFields(),
        ));
    }

    #[Test]
    public function it_can_generate_definition_string(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->fields(['id', 'title', 'handle']);

        $expected = "fragment ProductFields on Product {\n  id\n  title\n  handle\n}";
        $this->assertEquals($expected, $fragment->toDefinitionString());
    }

    #[Test]
    public function it_can_generate_usage_string(): void
    {
        $namedFragment = new Fragment('ProductFields', 'Product');
        $this->assertEquals('...ProductFields', $namedFragment->toUsageString());

        $inlineFragment = new InlineFragment('Product');
        $this->assertEquals('... on Product', $inlineFragment->toString());
    }

    #[Test]
    public function it_can_handle_complex_fields(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->fields([
            [
                'name' => 'variants',
                'arguments' => ['first' => 10],
                'fields' => [
                    ['name' => 'edges', 'fields' => [
                        ['name' => 'node', 'fields' => ['id', 'sku']],
                    ]],
                ],
            ],
        ]);

        $definition = $fragment->toDefinitionString();
        $this->assertStringContainsString('variants(first: 10)', $definition);
        $this->assertStringContainsString('edges', $definition);
        $this->assertStringContainsString('node', $definition);
        $this->assertStringContainsString('id', $definition);
        $this->assertStringContainsString('sku', $definition);
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->fields(['id', 'title']);

        $expected = [
            'name' => 'ProductFields',
            'on' => 'Product',
            'fields' => ['id', 'title'],
            'directives' => [],
        ];

        $this->assertEquals($expected, $fragment->toArray());
    }

    #[Test]
    public function it_can_create_from_static_methods(): void
    {
        $fragment = Fragment::create('ProductFields', 'Product');
        $this->assertEquals('ProductFields', $fragment->getName());
        $this->assertEquals('Product', $fragment->getOnType());
    }

    #[Test]
    public function it_can_create_from_array(): void
    {
        $data = [
            'name' => 'ProductFields',
            'on' => 'Product',
            'fields' => ['id', 'title', 'handle'],
        ];

        $fragment = Fragment::fromArray($data);
        $this->assertEquals('ProductFields', $fragment->getName());
        $this->assertEquals('Product', $fragment->getOnType());
        $fieldNames = array_map(fn (array $field) => $field['name'], $fragment->getFields());
        $this->assertEquals(['id', 'title', 'handle'], $fieldNames);
    }

    #[Test]
    public function it_returns_definition_when_cast_to_string(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->fields(['id', 'title']);

        $string = (string) $fragment;
        $definition = $fragment->toDefinitionString();

        $this->assertEquals($definition, $string);
    }

    #[Test]
    public function it_returns_definition_string_when_cast_to_string(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->fields(['id', 'title', 'handle']);

        $expected = "fragment ProductFields on Product {\n  id\n  title\n  handle\n}";
        $this->assertEquals($expected, (string) $fragment);
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $fragment = new Fragment('UserFields', 'User');
        $this->assertInstanceOf(Stringable::class, $fragment);
    }

    #[Test]
    public function it_casts_fragment_with_simple_fields_to_string(): void
    {
        $fragment = new Fragment('SimpleFields', 'Product');
        $fragment->fields(['id', 'title']);

        $expected = "fragment SimpleFields on Product {\n  id\n  title\n}";
        $this->assertEquals($expected, (string) $fragment);
    }

    #[Test]
    public function it_casts_fragment_with_complex_fields_to_string(): void
    {
        $fragment = new Fragment('ComplexFields', 'Product');
        $fragment->field('variants', ['first' => 10], function ($field): void {
            $field->field('edges', [], function ($field): void {
                $field->field('node', [], ['id', 'sku', 'price']);
            });
        });

        $expected = "fragment ComplexFields on Product {\n  variants(first: 10) {\n    edges {\n      node {\n        id\n        sku\n        price\n      }\n    }\n  }\n}";
        $this->assertEquals($expected, (string) $fragment);
    }

    #[Test]
    public function it_casts_fragment_with_nested_fragments_to_string(): void
    {
        $fragment = new Fragment('NestedFields', 'Order');
        $fragment->fields(['id', 'name', '...OrderLineFields']);

        $expected = "fragment NestedFields on Order {\n  id\n  name\n  ...OrderLineFields\n}";
        $this->assertEquals($expected, (string) $fragment);
    }

    #[Test]
    public function it_casts_empty_fragment_to_string(): void
    {
        $fragment = new Fragment('EmptyFields', 'User');

        $expected = "fragment EmptyFields on User {\n}";
        $this->assertEquals($expected, (string) $fragment);
    }
}

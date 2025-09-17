<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Processors;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Processors\FieldProcessor;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\InlineFragment;

class FieldProcessorTest extends TestCase
{
    #[Test]
    public function it_processes_empty_fields_array(): void
    {
        $result = FieldProcessor::process([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_keeps_field_objects_unchanged(): void
    {
        $field1 = new Field('user');
        $field2 = new Field('product');

        $fields = [$field1, $field2];
        $result = FieldProcessor::process($fields);

        $this->assertCount(2, $result);
        $this->assertSame($field1, $result[0]);
        $this->assertSame($field2, $result[1]);
    }

    #[Test]
    public function it_keeps_inline_fragment_objects_unchanged(): void
    {
        $inlineFragment = new InlineFragment('Product');
        $field = new Field('id');

        $fields = [$inlineFragment, $field];
        $result = FieldProcessor::process($fields);

        $this->assertCount(2, $result);
        $this->assertSame($inlineFragment, $result[0]);
        $this->assertSame($field, $result[1]);
    }

    #[Test]
    public function it_converts_string_fields_to_field_objects(): void
    {
        $fields = ['id', 'name', 'email'];

        $result = FieldProcessor::process($fields);

        $this->assertCount(3, $result);

        foreach ($result as $i => $field) {
            $this->assertInstanceOf(Field::class, $field);
            $this->assertEquals($fields[$i], $field->getName());
        }
    }

    #[Test]
    public function it_creates_fields_from_named_array_definitions(): void
    {
        $fields = [
            'user' => ['id', 'name', 'email'],
            'product' => ['id', 'title', 'price'],
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(2, $result);

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('user', $result[0]->getName());

        $this->assertInstanceOf(Field::class, $result[1]);
        $this->assertEquals('product', $result[1]->getName());
    }

    #[Test]
    public function it_creates_fields_from_metadata_arrays(): void
    {
        $fields = [
            ['name' => 'user', 'fields' => ['id', 'name']],
            ['name' => 'product', 'arguments' => ['id' => '$productId']],
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(2, $result);

        $this->assertInstanceOf(Field::class, $result[0]);
        $this->assertEquals('user', $result[0]->getName());

        $this->assertInstanceOf(Field::class, $result[1]);
        $this->assertEquals('product', $result[1]->getName());
    }

    #[Test]
    public function it_processes_mixed_field_types(): void
    {
        $existingField = new Field('existingField');
        $inlineFragment = new InlineFragment('Product');

        $fields = [
            $existingField,
            'stringField',
            'namedField' => ['id', 'title'],
            ['name' => 'metadataField'],
            $inlineFragment,
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(5, $result);

        $this->assertSame($existingField, $result[0]);

        $this->assertInstanceOf(Field::class, $result[1]);
        $this->assertEquals('stringField', $result[1]->getName());

        $this->assertInstanceOf(Field::class, $result[2]);
        $this->assertEquals('namedField', $result[2]->getName());

        $this->assertInstanceOf(Field::class, $result[3]);
        $this->assertEquals('metadataField', $result[3]->getName());

        $this->assertSame($inlineFragment, $result[4]);
    }

    #[Test]
    public function it_converts_fields_to_array_format(): void
    {
        $field = new Field('user', ['id' => '$userId'], ['id', 'name']);
        $inlineFragment = new InlineFragment('Product', ['title', 'price']);

        $fields = [$field, $inlineFragment, 'stringField'];

        $result = FieldProcessor::toArray($fields);

        $this->assertCount(3, $result);

        $this->assertEquals($field->toArray(), $result[0]);
        $this->assertEquals($inlineFragment->toArray(), $result[1]);
        $this->assertEquals('stringField', $result[2]); // Strings returned as-is
    }

    #[Test]
    public function it_handles_empty_array_in_to_array_conversion(): void
    {
        $result = FieldProcessor::toArray([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_creates_field_from_simple_array_definition(): void
    {
        $fields = ['simpleField' => ['id', 'name', 'email']];

        $result = FieldProcessor::process($fields);

        $this->assertCount(1, $result);

        $field = $result[0];
        $this->assertInstanceOf(Field::class, $field);
        $this->assertEquals('simpleField', $field->getName());

        $fieldArray = $field->toArray();
        $this->assertEquals(['id', 'name', 'email'], array_column($fieldArray['fields'], 'name'));
    }

    #[Test]
    public function it_creates_field_from_complex_array_definition(): void
    {
        $fields = [
            'complexField' => [
                'arguments' => ['first' => 10],
                'fields' => ['id', 'title'],
                'alias' => 'myField',
                'directives' => [['name' => 'include', 'arguments' => ['if' => '$show']]],
            ],
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(1, $result);

        $field = $result[0];
        $this->assertInstanceOf(Field::class, $field);
        $this->assertEquals('complexField', $field->getName());
        $this->assertEquals('myField', $field->getAlias());
        $this->assertEquals(['first' => 10], $field->getArguments());
    }

    #[Test]
    public function it_creates_field_from_metadata_with_all_properties(): void
    {
        $fields = [
            [
                'name' => 'fullField',
                'arguments' => ['id' => '$fieldId'],
                'fields' => ['id', 'value'],
                'alias' => 'aliasedField',
                'directives' => [['name' => 'cached']],
            ],
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(1, $result);

        $field = $result[0];
        $this->assertInstanceOf(Field::class, $field);
        $this->assertEquals('fullField', $field->getName());
        $this->assertEquals('aliasedField', $field->getAlias());
        $this->assertEquals(['id' => '$fieldId'], $field->getArguments());
    }

    #[Test]
    public function it_creates_field_from_metadata_with_minimal_properties(): void
    {
        $fields = [
            ['name' => 'minimalField'],
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(1, $result);

        $field = $result[0];
        $this->assertInstanceOf(Field::class, $field);
        $this->assertEquals('minimalField', $field->getName());
        $this->assertNull($field->getAlias());
        $this->assertEmpty($field->getArguments());
        $this->assertEmpty($field->getFields());
    }

    #[Test]
    public function it_handles_array_definition_without_structured_keys(): void
    {
        // When an array doesn't have structured keys (arguments, fields, etc.)
        // it should be treated as a fields array
        $fields = [
            'userProfile' => ['id', 'username', 'email', 'avatar'],
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(1, $result);

        $field = $result[0];
        $this->assertInstanceOf(Field::class, $field);
        $this->assertEquals('userProfile', $field->getName());

        $fieldArray = $field->toArray();
        $fieldNames = array_column($fieldArray['fields'], 'name');
        $this->assertEquals(['id', 'username', 'email', 'avatar'], $fieldNames);
    }

    #[Test]
    public function it_preserves_order_of_processed_fields(): void
    {
        $field1 = new Field('first');
        $field2 = new Field('second');

        $fields = [
            $field1,
            'third',
            'fourth' => ['id'],
            ['name' => 'fifth'],
            $field2,
        ];

        $result = FieldProcessor::process($fields);

        $this->assertCount(5, $result);
        $this->assertSame($field1, $result[0]);
        $this->assertEquals('third', $result[1]->getName());
        $this->assertEquals('fourth', $result[2]->getName());
        $this->assertEquals('fifth', $result[3]->getName());
        $this->assertSame($field2, $result[4]);
    }

    #[Test]
    public function it_handles_numeric_string_field_names(): void
    {
        $fields = ['123', '456'];

        $result = FieldProcessor::process($fields);

        $this->assertCount(2, $result);
        $this->assertEquals('123', $result[0]->getName());
        $this->assertEquals('456', $result[1]->getName());
    }

    #[Test]
    public function it_handles_special_graphql_field_names(): void
    {
        $fields = ['__typename', '__schema', '...FragmentName'];

        $result = FieldProcessor::process($fields);

        $this->assertCount(3, $result);
        $this->assertEquals('__typename', $result[0]->getName());
        $this->assertEquals('__schema', $result[1]->getName());
        $this->assertEquals('...FragmentName', $result[2]->getName());
    }
}

<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Types\Variable;
use Stringable;

class VariableTest extends TestCase
{
    #[Test]
    public function it_can_create_variable(): void
    {
        $variable = new Variable('id', 'ID!', 'gid://shopify/Product/123');

        $this->assertEquals('id', $variable->getName());
        $this->assertEquals('ID!', $variable->getType());
        $this->assertEquals('gid://shopify/Product/123', $variable->getValue());
    }

    #[Test]
    public function it_can_create_variable_without_value(): void
    {
        $variable = new Variable('first', 'Int');

        $this->assertEquals('first', $variable->getName());
        $this->assertEquals('Int', $variable->getType());
        $this->assertNull($variable->getValue());
        $this->assertFalse($variable->hasValue());
    }

    #[Test]
    public function it_can_detect_required_variables(): void
    {
        $required = new Variable('id', 'ID!');
        $optional = new Variable('first', 'Int');

        $this->assertTrue($required->isRequired());
        $this->assertFalse($required->isNullable());

        $this->assertFalse($optional->isRequired());
        $this->assertTrue($optional->isNullable());
    }

    #[Test]
    public function it_can_detect_list_variables(): void
    {
        $listVar = new Variable('ids', '[ID!]!');
        $scalarVar = new Variable('id', 'ID!');

        $this->assertTrue($listVar->isList());
        $this->assertFalse($scalarVar->isList());
    }

    #[Test]
    public function it_can_get_base_type(): void
    {
        $variable = new Variable('ids', '[ID!]!');
        $this->assertEquals('ID', $variable->baseType());

        $variable = new Variable('id', 'ID!');
        $this->assertEquals('ID', $variable->baseType());

        $variable = new Variable('name', 'String');
        $this->assertEquals('String', $variable->baseType());
    }

    #[Test]
    public function it_can_generate_definition_string(): void
    {
        $variable = new Variable('id', 'ID!');
        $this->assertEquals('$id: ID!', $variable->toDefinitionString());
        $this->assertEquals('$id: ID!', (string) $variable);
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $variable = new Variable('first', 'Int', 10);
        $array = $variable->toArray();

        $expected = [
            'name' => 'first',
            'type' => 'Int',
            'value' => 10,
        ];

        $this->assertEquals($expected, $array);
    }

    #[Test]
    public function it_can_create_from_static_methods(): void
    {
        $variable = Variable::create('id', 'ID!', 'test');
        $this->assertEquals('id', $variable->getName());
        $this->assertEquals('ID!', $variable->getType());
        $this->assertEquals('test', $variable->getValue());
    }

    #[Test]
    public function it_can_create_from_array(): void
    {
        $data = [
            'name' => 'query',
            'type' => 'String',
            'value' => 'product_type:shirt',
        ];

        $variable = Variable::fromArray($data);
        $this->assertEquals('query', $variable->getName());
        $this->assertEquals('String', $variable->getType());
        $this->assertEquals('product_type:shirt', $variable->getValue());
    }

    #[Test]
    public function it_casts_variable_string_with_different_types(): void
    {
        $stringVar = new Variable('query', 'String');
        $this->assertEquals('$query: String', (string) $stringVar);

        $requiredVar = new Variable('userId', 'ID!');
        $this->assertEquals('$userId: ID!', (string) $requiredVar);

        $listVar = new Variable('ids', '[ID!]!');
        $this->assertEquals('$ids: [ID!]!', (string) $listVar);
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $variable = new Variable('test', 'String');
        $this->assertInstanceOf(Stringable::class, $variable);
    }

    #[Test]
    public function it_casts_variable_string_comprehensively(): void
    {
        // Required scalar type
        $requiredScalar = new Variable('id', 'ID!');
        $this->assertEquals('$id: ID!', (string) $requiredScalar);

        // Optional scalar type
        $optionalScalar = new Variable('name', 'String');
        $this->assertEquals('$name: String', (string) $optionalScalar);

        // Required list type
        $requiredList = new Variable('ids', '[ID!]!');
        $this->assertEquals('$ids: [ID!]!', (string) $requiredList);

        // Optional list type
        $optionalList = new Variable('tags', '[String]');
        $this->assertEquals('$tags: [String]', (string) $optionalList);

        // Complex input type
        $inputType = new Variable('filter', 'ProductFilter!');
        $this->assertEquals('$filter: ProductFilter!', (string) $inputType);

        // Nested list type
        $nestedList = new Variable('matrix', '[[Int]]');
        $this->assertEquals('$matrix: [[Int]]', (string) $nestedList);
    }

    #[Test]
    public function it_casts_variable_with_value_to_string(): void
    {
        // Variables with values still only show type definition in GraphQL
        $withValue = new Variable('limit', 'Int', 10);
        $this->assertEquals('$limit: Int', (string) $withValue);

        $withStringValue = new Variable('query', 'String', 'test search');
        $this->assertEquals('$query: String', (string) $withStringValue);
    }
}

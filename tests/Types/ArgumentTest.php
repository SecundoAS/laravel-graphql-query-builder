<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Types\Argument;
use Stringable;

class ArgumentTest extends TestCase
{
    #[Test]
    public function it_can_create_literal_argument(): void
    {
        $argument = new Argument('first', 10);

        $this->assertEquals('first', $argument->getName());
        $this->assertEquals(10, $argument->getValue());
        $this->assertTrue($argument->isLiteral());
        $this->assertFalse($argument->isVariable());
    }

    #[Test]
    public function it_can_create_variable_argument(): void
    {
        $argument = new Argument('id', '$productId');

        $this->assertEquals('id', $argument->getName());
        $this->assertEquals('$productId', $argument->getValue());
        $this->assertTrue($argument->isVariable());
        $this->assertFalse($argument->isLiteral());
        $this->assertEquals('productId', $argument->getVariableName());
    }

    #[Test]
    public function it_can_detect_argument_types(): void
    {
        $stringArg = new Argument('query', 'title:shirt');
        $intArg = new Argument('first', 10);
        $boolArg = new Argument('reverse', true);
        $nullArg = new Argument('tags', null);
        $arrayArg = new Argument('ids', [1, 2, 3]);
        $objectArg = new Argument('metafields', ['namespace' => 'custom']);
        $variableArg = new Argument('id', '$productId');

        $this->assertEquals('string', $stringArg->type());
        $this->assertEquals('int', $intArg->type());
        $this->assertEquals('boolean', $boolArg->type());
        $this->assertEquals('null', $nullArg->type());
        $this->assertEquals('array', $arrayArg->type());
        $this->assertEquals('object', $objectArg->type());
        $this->assertEquals('variable', $variableArg->type());
    }

    #[Test]
    public function it_can_format_different_value_types(): void
    {
        $stringArg = new Argument('query', 'title:shirt');
        $this->assertEquals('"title:shirt"', $stringArg->toGraphQLString());

        $intArg = new Argument('first', 10);
        $this->assertEquals('10', $intArg->toGraphQLString());

        $boolArg = new Argument('reverse', true);
        $this->assertEquals('true', $boolArg->toGraphQLString());

        $nullArg = new Argument('tags', null);
        $this->assertEquals('null', $nullArg->toGraphQLString());

        $arrayArg = new Argument('ids', [1, 2, 3]);
        $this->assertEquals('[1, 2, 3]', $arrayArg->toGraphQLString());

        $objectArg = new Argument('metafields', ['namespace' => 'custom', 'key' => 'data']);
        $this->assertEquals('{namespace: "custom", key: "data"}', $objectArg->toGraphQLString());

        $variableArg = new Argument('id', '$productId');
        $this->assertEquals('$productId', $variableArg->toGraphQLString());
    }

    #[Test]
    public function it_can_convert_to_string(): void
    {
        $argument = new Argument('first', 10);
        $this->assertEquals('first: 10', $argument->toString());
        $this->assertEquals('first: 10', (string) $argument);
    }

    #[Test]
    public function it_can_create_from_static_methods(): void
    {
        $literal = Argument::literal('first', 10);
        $this->assertEquals('first', $literal->getName());
        $this->assertEquals(10, $literal->getValue());
        $this->assertTrue($literal->isLiteral());

        $variable = Argument::variable('id', 'productId');
        $this->assertEquals('id', $variable->getName());
        $this->assertEquals('$productId', $variable->getValue());
        $this->assertTrue($variable->isVariable());

        $variable2 = Argument::variable('id', '$productId');
        $this->assertEquals('$productId', $variable2->getValue());
    }

    #[Test]
    public function it_can_create_collection_from_array(): void
    {
        $args = Argument::collection([
            'first' => 10,
            'query' => 'title:shirt',
            'id' => '$productId',
        ]);

        $this->assertCount(3, $args);
        $this->assertInstanceOf(Argument::class, $args[0]);
        $this->assertEquals('first', $args[0]->getName());
        $this->assertEquals(10, $args[0]->getValue());
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $argument = new Argument('first', 10);
        $array = $argument->toArray();

        $expected = [
            'name' => 'first',
            'value' => 10,
            'type' => 'int',
        ];

        $this->assertEquals($expected, $array);
    }

    #[Test]
    public function it_can_handle_nested_arrays(): void
    {
        $argument = new Argument('complex', [
            'level1' => [
                'level2' => ['item1', 'item2'],
                'simple' => 'value',
            ],
        ]);

        $expected = '{level1: {level2: ["item1", "item2"], simple: "value"}}';
        $this->assertEquals($expected, $argument->toGraphQLString());
    }

    #[Test]
    public function it_casts_argument_string_with_different_types(): void
    {
        // String argument
        $stringArg = new Argument('query', 'test');
        $this->assertEquals('query: "test"', (string) $stringArg);

        // Variable argument
        $variableArg = new Argument('id', '$productId');
        $this->assertEquals('id: $productId', (string) $variableArg);

        // Boolean argument
        $boolArg = new Argument('reverse', true);
        $this->assertEquals('reverse: true', (string) $boolArg);

        // Null argument
        $nullArg = new Argument('after', null);
        $this->assertEquals('after: null', (string) $nullArg);
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $argument = new Argument('test', 'value');
        $this->assertInstanceOf(Stringable::class, $argument);
    }

    #[Test]
    public function it_casts_argument_string_comprehensively(): void
    {
        // String values
        $stringArg = new Argument('query', 'product title');
        $this->assertEquals('query: "product title"', (string) $stringArg);

        // Integer values
        $intArg = new Argument('first', 25);
        $this->assertEquals('first: 25', (string) $intArg);

        // Float values
        $floatArg = new Argument('price', 19.99);
        $this->assertEquals('price: 19.99', (string) $floatArg);

        // Boolean values
        $trueArg = new Argument('available', true);
        $falseArg = new Argument('published', false);
        $this->assertEquals('available: true', (string) $trueArg);
        $this->assertEquals('published: false', (string) $falseArg);

        // Null values
        $nullArg = new Argument('after', null);
        $this->assertEquals('after: null', (string) $nullArg);

        // Variable references
        $varArg = new Argument('userId', '$currentUserId');
        $this->assertEquals('userId: $currentUserId', (string) $varArg);
    }

    #[Test]
    public function it_casts_argument_string_with_complex_values(): void
    {
        // Array values
        $arrayArg = new Argument('tags', ['shirt', 'cotton', 'blue']);
        $this->assertEquals('tags: ["shirt", "cotton", "blue"]', (string) $arrayArg);

        // Numeric array
        $numArrayArg = new Argument('ids', [1, 2, 3, 4]);
        $this->assertEquals('ids: [1, 2, 3, 4]', (string) $numArrayArg);

        // Object values
        $objectArg = new Argument('filter', ['title' => 'shirt', 'available' => true, 'price' => 25.50]);
        $this->assertEquals('filter: {title: "shirt", available: true, price: 25.5}', (string) $objectArg);

        // Nested objects
        $nestedArg = new Argument('complex', [
            'user' => ['id' => 123, 'name' => 'John'],
            'settings' => ['theme' => 'dark', 'notifications' => false],
        ]);
        $this->assertEquals('complex: {user: {id: 123, name: "John"}, settings: {theme: "dark", notifications: false}}', (string) $nestedArg);
    }

    #[Test]
    public function it_casts_argument_string_with_special_characters(): void
    {
        // String with quotes
        $quotedArg = new Argument('description', 'A "special" product');
        $this->assertEquals('description: "A \\"special\\" product"', (string) $quotedArg);

        // String with newlines and special chars - test actual output
        $specialArg = new Argument('notes', "Line 1\nLine 2\tTabbed");
        $actualOutput = (string) $specialArg;
        $this->assertStringContainsString('notes: "Line 1', $actualOutput);
        $this->assertStringContainsString('Line 2', $actualOutput);
        $this->assertStringContainsString('Tabbed"', $actualOutput);
    }
}

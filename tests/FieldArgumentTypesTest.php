<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Types\Field;

class FieldArgumentTypesTest extends TestCase
{
    #[Test]
    public function it_can_accept_argument_builder_in_arguments_array(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where('vendor', 'Nike');

        $field = new Field('products', ['query' => $builder]);

        $arguments = $field->getArguments();
        $this->assertArrayHasKey('query', $arguments);
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['query']);
        $this->assertEquals('status:active AND vendor:Nike', $arguments['query']->toString());
        $this->assertEquals('products(query: "status:active AND vendor:Nike")', (string) $field);
    }

    #[Test]
    public function it_can_accept_closure_in_constructor(): void
    {
        $field = new Field('products', ['first' => 5, 'query' => function (ArgumentBuilder $builder): void {
            $builder->where('status', 'active')->whereIn('vendor', ['Nike', 'Adidas']);
        }]);

        // Test that arguments are processed correctly
        $arguments = $field->getArguments();
        $this->assertArrayHasKey('first', $arguments);
        $this->assertArrayHasKey('query', $arguments);
        $this->assertEquals(5, $arguments['first']);
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['query']);
        $this->assertEquals('status:active AND vendor:Nike,Adidas', $arguments['query']->toString());
        $this->assertEquals('products(first: 5, query: "status:active AND vendor:Nike,Adidas")', (string) $field);
    }

    #[Test]
    public function it_can_accept_array_in_constructor(): void
    {
        $field = new Field('products', ['first' => 10, 'last' => 5]);

        $arguments = $field->getArguments();
        $this->assertEquals(['first' => 10, 'last' => 5], $arguments);
        $this->assertEquals('products(first: 10, last: 5)', (string) $field);
    }

    #[Test]
    public function it_can_accept_string_in_arguments_array(): void
    {
        $field = new Field('products', ['query' => 'status:active AND vendor:Nike']);

        $arguments = $field->getArguments();
        $this->assertArrayHasKey('query', $arguments);
        $this->assertEquals('status:active AND vendor:Nike', $arguments['query']);
        $this->assertEquals('products(query: "status:active AND vendor:Nike")', (string) $field);
    }

    #[Test]
    public function it_handles_empty_arguments_in_constructor(): void
    {
        $field = new Field('products');

        $arguments = $field->getArguments();
        $this->assertEquals([], $arguments);
        $this->assertEquals('products', (string) $field);
    }

    #[Test]
    public function it_can_update_arguments_after_construction(): void
    {
        $field = new Field('products', ['first' => 10]);

        // Add ArgumentBuilder
        $builder = ArgumentBuilder::create()->where('status', 'active');
        $field->arguments(['query' => $builder]);

        $arguments = $field->getArguments();
        $this->assertArrayHasKey('first', $arguments);
        $this->assertArrayHasKey('query', $arguments);
        $this->assertEquals(10, $arguments['first']);
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['query']);
        $this->assertEquals('products(first: 10, query: "status:active")', (string) $field);
    }

    #[Test]
    public function it_can_merge_array_arguments(): void
    {
        $field = new Field('products', ['first' => 10]);
        $field->arguments(['last' => 5, 'reverse' => true]);

        $arguments = $field->getArguments();
        $this->assertEquals([
            'first' => 10,
            'last' => 5,
            'reverse' => true,
        ], $arguments);
        $this->assertEquals('products(first: 10, last: 5, reverse: true)', (string) $field);
    }
}

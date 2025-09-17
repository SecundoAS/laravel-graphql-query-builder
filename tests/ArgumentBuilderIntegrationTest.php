<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Types\Argument;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\Variable;

class ArgumentBuilderIntegrationTest extends TestCase
{
    #[Test]
    public function it_can_create_argument_builder_from_argument_class(): void
    {
        $builder = Argument::builder();
        $this->assertInstanceOf(ArgumentBuilder::class, $builder);

        $builder2 = Argument::query();
        $this->assertInstanceOf(ArgumentBuilder::class, $builder2);
    }

    #[Test]
    public function it_can_use_argument_builder_as_argument_value(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where('vendor', 'Nike');

        $argument = new Argument('query', $builder);

        $this->assertEquals('status:active AND vendor:Nike', $argument->getValue());
        $this->assertEquals('query: "status:active AND vendor:Nike"', $argument->toString());
    }

    #[Test]
    public function it_can_set_argument_builder_via_value_method(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereIn('status', ['active', 'draft'])
            ->where('created_at', '>', '2020-01-01');

        $argument = new Argument('query', 'initial')
            ->value($builder);

        $this->assertEquals('status:active,draft AND created_at:>"2020-01-01"', $argument->getValue());
    }

    #[Test]
    public function it_can_use_argument_builder_with_variable(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where('price', '<=', '$maxPrice');

        $variable = new Variable('searchQuery', 'String', $builder);

        $this->assertEquals('status:active AND price:<=$maxPrice', $variable->getValue());
    }

    #[Test]
    public function it_can_set_argument_builder_via_variable_value_method(): void
    {
        $builder = ArgumentBuilder::create()
            ->search('iPhone')
            ->where('vendor', 'Apple');

        $variable = new Variable('query', 'String')
            ->value($builder);

        $this->assertEquals('iPhone AND vendor:Apple', $variable->getValue());
    }

    #[Test]
    public function it_can_use_argument_builder_in_field_arguments(): void
    {
        $field = new Field('products');

        $queryBuilder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->whereIn('vendor', ['Nike', 'Adidas'])
            ->where('created_at', '>', '2020-01-01');

        $field->argument('query', $queryBuilder);

        $arguments = $field->getArguments();
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['query']);
        $this->assertEquals('status:active AND vendor:Nike,Adidas AND created_at:>"2020-01-01"', $arguments['query']->toString());
    }

    #[Test]
    public function it_maintains_stringable_interface_throughout_chain(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->where('vendor', 'Nike')
                    ->orWhere('vendor', 'Adidas');
            });

        $argument = new Argument('query', $builder);
        $field = new Field('products');
        $field->argument('search', $argument);

        // Should be able to cast everything to string
        $builderString = (string) $builder;
        $argumentString = (string) $argument;

        $this->assertEquals('status:active AND (vendor:Nike OR vendor:Adidas)', $builderString);
        $this->assertEquals('query: "status:active AND (vendor:Nike OR vendor:Adidas)"', $argumentString);
    }

    #[Test]
    public function it_works_with_shopify_examples_from_user(): void
    {
        // Example: created_at:>'2020-10-21T23:39:20Z'
        $builder1 = ArgumentBuilder::create()
            ->where('created_at', '>', '2020-10-21T23:39:20Z');
        $this->assertEquals('created_at:>"2020-10-21T23:39:20Z"', $builder1->toString());

        // Example: created_at:<now
        $builder2 = ArgumentBuilder::create()
            ->where('created_at', '<', 'now');
        $this->assertEquals('created_at:<now', $builder2->toString());

        // Example: created_at:<='2024'
        $builder3 = ArgumentBuilder::create()
            ->where('created_at', '<=', '2024');
        $this->assertEquals('created_at:<=2024', $builder3->toString());

        // Example: status:active,draft
        $builder4 = ArgumentBuilder::create()
            ->whereIn('status', ['active', 'draft']);
        $this->assertEquals('status:active,draft', $builder4->toString());

        // Example: vendor:Snowdevil OR vendor:Icedevil
        $builder5 = ArgumentBuilder::create()
            ->where('vendor', 'Snowdevil')
            ->orWhere('vendor', 'Icedevil');
        $this->assertEquals('vendor:Snowdevil OR vendor:Icedevil', $builder5->toString());

        // Example: query=bob OR norman AND Shopify
        $builder6 = ArgumentBuilder::create()
            ->search('bob')
            ->orSearch('norman')
            ->search('Shopify');
        $this->assertEquals('bob OR norman AND Shopify', $builder6->toString());

        // Example: query=state:disabled AND ("sale shopper" OR VIP)
        $builder7 = ArgumentBuilder::create()
            ->where('state', 'disabled')
            ->where(function ($query): void {
                $query->wherePhrase('query', 'sale shopper')
                    ->orWhere('query', 'VIP');
            });
        $this->assertEquals('state:disabled AND (query:"sale shopper" OR query:VIP)', $builder7->toString());
    }

    #[Test]
    public function it_can_recreate_complex_shopify_queries(): void
    {
        // Complex nested query with multiple conditions
        $builder = ArgumentBuilder::create()
            ->where('created_at', '>', '2020-01-01')
            ->whereIn('status', ['active', 'draft'])
            ->where(function ($query): void {
                $query->where('vendor', 'Nike')
                    ->orWhere('vendor', 'Adidas')
                    ->orWhere(function ($subQuery): void {
                        $subQuery->where('product_type', 'shoes')
                            ->where('price', '<=', 200);
                    });
            })
            ->whereNot('title', 'discontinued')
            ->search('running OR basketball');

        $expected = 'created_at:>"2020-01-01" AND status:active,draft AND (vendor:Nike OR vendor:Adidas OR (product_type:shoes AND price:<=200)) AND NOT title:discontinued AND running OR basketball';
        $this->assertEquals($expected, $builder->toString());
    }

    #[Test]
    public function it_preserves_argument_builder_when_used_directly(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active');

        $field = new Field('products');
        $field->argument('query', $builder);

        // The argument should store the builder object, not convert to string immediately
        $arguments = $field->getArguments();
        $this->assertInstanceOf(ArgumentBuilder::class, $arguments['query']);

        // But should convert to string when needed
        $this->assertEquals('status:active', $arguments['query']->toString());
    }

    #[Test]
    public function it_handles_argument_from_key_value_with_builder(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('vendor', 'Nike')
            ->whereIn('size', ['M', 'L', 'XL']);

        $argument = Argument::fromKeyValue('query', $builder);

        $this->assertEquals('vendor:Nike AND size:M,L,XL', $argument->getValue());
        $this->assertEquals('query: "vendor:Nike AND size:M,L,XL"', $argument->toString());
    }

    #[Test]
    public function it_works_in_argument_collection(): void
    {
        $queryBuilder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where('featured', true);

        $arguments = Argument::collection([
            'first' => 10,
            'query' => $queryBuilder,
            'sortKey' => 'CREATED_AT',
        ]);

        $this->assertCount(3, $arguments);
        $this->assertEquals('first', $arguments[0]->getName());
        $this->assertEquals(10, $arguments[0]->getValue());
        $this->assertEquals('query', $arguments[1]->getName());
        $this->assertEquals('status:active AND featured:true', $arguments[1]->getValue());
        $this->assertEquals('sortKey', $arguments[2]->getName());
        $this->assertEquals('CREATED_AT', $arguments[2]->getValue());
    }
}

<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Processors;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\ArgumentBuilder;
use Secundo\GraphQL\Processors\ArgumentProcessor;
use Secundo\GraphQL\Types\Argument;

class ArgumentProcessorTest extends TestCase
{
    #[Test]
    public function it_processes_empty_arguments_array(): void
    {
        $result = ArgumentProcessor::process([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_keeps_primitive_values_unchanged(): void
    {
        $arguments = [
            'first' => 10,
            'query' => 'title:test',
            'reverse' => true,
            'tags' => ['new', 'featured'],
            'filters' => null,
        ];

        $result = ArgumentProcessor::process($arguments);

        $this->assertEquals($arguments, $result);
        $this->assertSame(10, $result['first']);
        $this->assertSame('title:test', $result['query']);
        $this->assertTrue($result['reverse']);
        $this->assertEquals(['new', 'featured'], $result['tags']);
        $this->assertNull($result['filters']);
    }

    #[Test]
    public function it_keeps_argument_builder_instances_unchanged(): void
    {
        $builder = ArgumentBuilder::create()->where('status', 'active');
        $arguments = ['query' => $builder];

        $result = ArgumentProcessor::process($arguments);

        $this->assertSame($builder, $result['query']);
        $this->assertInstanceOf(ArgumentBuilder::class, $result['query']);
    }

    #[Test]
    public function it_executes_closures_to_create_argument_builders(): void
    {
        $arguments = [
            'query' => function (ArgumentBuilder $builder): void {
                $builder->where('status', 'active')
                    ->where('vendor', 'Nike');
            },
        ];

        $result = ArgumentProcessor::process($arguments);

        $this->assertInstanceOf(ArgumentBuilder::class, $result['query']);
        $this->assertEquals('status:active AND vendor:Nike', $result['query']->toString());
    }

    #[Test]
    public function it_processes_mixed_argument_types(): void
    {
        $existingBuilder = ArgumentBuilder::create()->where('featured', true);

        $arguments = [
            'first' => 25,
            'existingQuery' => $existingBuilder,
            'newQuery' => function (ArgumentBuilder $builder): void {
                $builder->where('price', '>', 10);
            },
            'sort' => 'CREATED_AT',
        ];

        $result = ArgumentProcessor::process($arguments);

        $this->assertSame(25, $result['first']);
        $this->assertSame($existingBuilder, $result['existingQuery']);
        $this->assertInstanceOf(ArgumentBuilder::class, $result['newQuery']);
        $this->assertEquals('price:>10', $result['newQuery']->toString());
        $this->assertSame('CREATED_AT', $result['sort']);
    }

    #[Test]
    public function it_handles_complex_closure_builders(): void
    {
        $arguments = [
            'filter' => function (ArgumentBuilder $builder): void {
                $builder->where('created_at', '>', '2023-01-01')
                    ->where(function (ArgumentBuilder $subBuilder): void {
                        $subBuilder->where('status', 'active')
                            ->orWhere('status', 'draft');
                    })
                    ->whereIn('tag', ['featured', 'new'])
                    ->search('iPhone OR Samsung');
            },
        ];

        $result = ArgumentProcessor::process($arguments);

        $this->assertInstanceOf(ArgumentBuilder::class, $result['filter']);
        $queryString = $result['filter']->toString();

        $this->assertStringContainsString('created_at:>"2023-01-01"', $queryString);
        $this->assertStringContainsString('status:active OR status:draft', $queryString);
        $this->assertStringContainsString('tag:featured,new', $queryString);
        $this->assertStringContainsString('iPhone OR Samsung', $queryString);
    }

    #[Test]
    public function it_converts_arguments_to_argument_objects(): void
    {
        $arguments = [
            'first' => 10,
            'query' => 'title:test',
            'reverse' => true,
        ];

        $result = ArgumentProcessor::toArgumentObjects($arguments);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $argument) {
            $this->assertInstanceOf(Argument::class, $argument);
        }

        $this->assertEquals('first', $result[0]->getName());
        $this->assertEquals(10, $result[0]->getValue());

        $this->assertEquals('query', $result[1]->getName());
        $this->assertEquals('title:test', $result[1]->getValue());

        $this->assertEquals('reverse', $result[2]->getName());
        $this->assertTrue($result[2]->getValue());
    }

    #[Test]
    public function it_converts_argument_objects_back_to_array(): void
    {
        $argumentObjects = [
            new Argument('first', 10),
            new Argument('query', 'title:test'),
            new Argument('reverse', true),
        ];

        $result = ArgumentProcessor::fromArgumentObjects($argumentObjects);

        $expected = [
            'first' => 10,
            'query' => 'title:test',
            'reverse' => true,
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_handles_roundtrip_conversion(): void
    {
        $originalArguments = [
            'first' => 25,
            'query' => 'product_type:shoes',
            'reverse' => false,
            'sortKey' => 'PRICE',
        ];

        // Convert to objects and back
        $objects = ArgumentProcessor::toArgumentObjects($originalArguments);
        $result = ArgumentProcessor::fromArgumentObjects($objects);

        $this->assertEquals($originalArguments, $result);
    }

    #[Test]
    public function it_handles_empty_arrays_in_object_conversion(): void
    {
        $this->assertEquals([], ArgumentProcessor::toArgumentObjects([]));
        $this->assertEquals([], ArgumentProcessor::fromArgumentObjects([]));
    }

    #[Test]
    public function it_preserves_array_values_in_object_conversion(): void
    {
        $arguments = [
            'tags' => ['featured', 'new', 'sale'],
            'filters' => ['status' => 'active', 'vendor' => 'Nike'],
        ];

        $objects = ArgumentProcessor::toArgumentObjects($arguments);
        $result = ArgumentProcessor::fromArgumentObjects($objects);

        $this->assertEquals($arguments, $result);
        $this->assertEquals(['featured', 'new', 'sale'], $result['tags']);
        $this->assertEquals(['status' => 'active', 'vendor' => 'Nike'], $result['filters']);
    }

    #[Test]
    public function it_handles_null_values_in_object_conversion(): void
    {
        $arguments = [
            'first' => 10,
            'after' => null,
            'query' => 'test',
        ];

        $objects = ArgumentProcessor::toArgumentObjects($arguments);
        $result = ArgumentProcessor::fromArgumentObjects($objects);

        $this->assertEquals($arguments, $result);
        $this->assertNull($result['after']);
    }
}

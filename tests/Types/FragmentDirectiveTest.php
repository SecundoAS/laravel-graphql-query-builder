<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Types\Directive;
use Secundo\GraphQL\Types\Fragment;

class FragmentDirectiveTest extends TestCase
{
    #[Test]
    public function it_can_create_fragment_without_directives(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')->field('title');

        $definition = $fragment->toDefinitionString();
        $expected = "fragment ProductFields on Product {\n  id\n  title\n}";

        $this->assertEquals($expected, $definition);
        $this->assertEmpty($fragment->getDirectives());
    }

    #[Test]
    public function it_can_add_single_directive_to_fragment(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')->field('title');
        $fragment->directive('include', ['if' => '$showProduct']);

        $definition = $fragment->toDefinitionString();
        $expected = "fragment ProductFields on Product @include(if: \$showProduct) {\n  id\n  title\n}";

        $this->assertEquals($expected, $definition);
        $this->assertCount(1, $fragment->getDirectives());
    }

    #[Test]
    public function it_can_add_multiple_directives_to_fragment(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')->field('title');
        $fragment->directive('include', ['if' => '$showProduct'])
            ->directive('skip', ['if' => '$hideDetails']);

        $definition = $fragment->toDefinitionString();
        $expected = "fragment ProductFields on Product @include(if: \$showProduct) @skip(if: \$hideDetails) {\n  id\n  title\n}";

        $this->assertEquals($expected, $definition);
        $this->assertCount(2, $fragment->getDirectives());
    }

    #[Test]
    public function it_can_add_directive_without_arguments(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')->field('title');
        $fragment->directive('cached');

        $definition = $fragment->toDefinitionString();
        $expected = "fragment ProductFields on Product @cached {\n  id\n  title\n}";

        $this->assertEquals($expected, $definition);
    }

    #[Test]
    public function it_can_add_directive_object_directly(): void
    {
        $directive = new Directive('include', ['if' => '$showProduct']);

        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')->field('title');
        $fragment->directive($directive);

        $definition = $fragment->toDefinitionString();
        $expected = "fragment ProductFields on Product @include(if: \$showProduct) {\n  id\n  title\n}";

        $this->assertEquals($expected, $definition);
        $this->assertSame($directive, $fragment->getDirectives()[0]);
    }

    #[Test]
    public function it_includes_directives_in_to_array(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')->field('title');
        $fragment->directive('include', ['if' => '$showProduct']);

        $array = $fragment->toArray();

        $this->assertArrayHasKey('directives', $array);
        $this->assertCount(1, $array['directives']);
        $this->assertEquals('include', $array['directives'][0]['name']);
        $this->assertEquals(['if' => '$showProduct'], $array['directives'][0]['arguments']);
    }

    #[Test]
    public function it_can_create_fragment_with_directives_in_constructor(): void
    {
        $directive = new Directive('include', ['if' => '$showProduct']);
        $fragment = new Fragment('ProductFields', 'Product', [], [$directive]);
        $fragment->field('id')->field('title');

        $definition = $fragment->toDefinitionString();
        $expected = "fragment ProductFields on Product @include(if: \$showProduct) {\n  id\n  title\n}";

        $this->assertEquals($expected, $definition);
        $this->assertCount(1, $fragment->getDirectives());
    }

    #[Test]
    public function it_handles_complex_directive_arguments(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');
        $fragment->field('id')->field('title');
        $fragment->directive('customDirective', [
            'string' => 'value',
            'number' => 42,
            'boolean' => true,
            'array' => ['a', 'b', 'c'],
            'object' => ['key' => 'value'],
        ]);

        $definition = $fragment->toDefinitionString();

        $this->assertStringContainsString('@customDirective(', $definition);
        $this->assertStringContainsString('string: "value"', $definition);
        $this->assertStringContainsString('number: 42', $definition);
        $this->assertStringContainsString('boolean: true', $definition);
    }

    #[Test]
    public function it_supports_method_chaining_with_directives(): void
    {
        $fragment = new Fragment('ProductFields', 'Product');

        $result = $fragment->field('id')
            ->field('title')
            ->directive('include', ['if' => '$showProduct'])
            ->directive('cached');

        $this->assertSame($fragment, $result);
        $this->assertCount(2, $fragment->getDirectives());
    }
}

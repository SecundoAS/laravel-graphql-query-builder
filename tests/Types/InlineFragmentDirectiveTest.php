<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Types\Directive;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\InlineFragment;

class InlineFragmentDirectiveTest extends TestCase
{
    #[Test]
    public function it_can_create_inline_fragment_without_directives(): void
    {
        $inlineFragment = new InlineFragment('Product');
        $inlineFragment->field('id')->field('title');

        $graphql = (string) $inlineFragment;
        $expected = "... on Product {\n  id\n  title\n}";

        $this->assertEquals($expected, $graphql);
        $this->assertEmpty($inlineFragment->getDirectives());
    }

    #[Test]
    public function it_can_create_inline_fragment_with_directives_in_constructor(): void
    {
        $directive = new Directive('include', ['if' => '$showProduct']);
        $inlineFragment = new InlineFragment('Product', [], [$directive]);
        $inlineFragment->field('id')->field('title');

        $array = $inlineFragment->toArray();

        $this->assertArrayHasKey('directives', $array);
        $this->assertCount(1, $array['directives']);
        $this->assertEquals('include', $array['directives'][0]['name']);
        $this->assertCount(1, $inlineFragment->getDirectives());
    }

    #[Test]
    public function it_can_add_directives_to_inline_fragment_after_creation(): void
    {
        $inlineFragment = new InlineFragment('Product');
        $inlineFragment->field('id')->field('title');
        $inlineFragment->directive('include', ['if' => '$showProduct']);

        $directives = $inlineFragment->getDirectives();

        $this->assertCount(1, $directives);
        $this->assertEquals('include', $directives[0]->getName());
        $this->assertEquals(['if' => '$showProduct'], $directives[0]->getArguments());
    }

    #[Test]
    public function it_can_add_multiple_directives_to_inline_fragment(): void
    {
        $inlineFragment = new InlineFragment('Product');
        $inlineFragment->field('id')->field('title');
        $inlineFragment->directive('include', ['if' => '$showProduct'])
            ->directive('skip', ['if' => '$hideDetails']);

        $directives = $inlineFragment->getDirectives();

        $this->assertCount(2, $directives);
        $this->assertEquals('include', $directives[0]->getName());
        $this->assertEquals('skip', $directives[1]->getName());
    }

    #[Test]
    public function it_includes_directives_in_to_array(): void
    {
        $inlineFragment = new InlineFragment('Product');
        $inlineFragment->field('id')->field('title');
        $inlineFragment->directive('include', ['if' => '$showProduct']);

        $array = $inlineFragment->toArray();

        $this->assertArrayHasKey('directives', $array);
        $this->assertCount(1, $array['directives']);
        $this->assertEquals('include', $array['directives'][0]['name']);
        $this->assertEquals(['if' => '$showProduct'], $array['directives'][0]['arguments']);
    }

    #[Test]
    public function it_can_use_inline_fragment_with_directives_in_field(): void
    {
        $field = new Field('nodes');
        $field->field('id')
            ->inlineFragment('Product', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('title')
                    ->field('handle')
                    ->directive('include', ['if' => '$showProductDetails']);
            });

        $fields = $field->getFields();

        $this->assertCount(2, $fields);
        $this->assertEquals('... on Product', $fields[1]['name']);
        $this->assertCount(1, $fields[1]['directives']);
        $this->assertEquals('include', $fields[1]['directives'][0]['name']);
    }

    #[Test]
    public function it_supports_method_chaining_with_directives(): void
    {
        $inlineFragment = new InlineFragment('Product');

        $result = $inlineFragment->field('id')
            ->field('title')
            ->directive('include', ['if' => '$showProduct'])
            ->directive('cached');

        $this->assertSame($inlineFragment, $result);
        $this->assertCount(2, $inlineFragment->getDirectives());
    }

    #[Test]
    public function it_can_add_directive_object_directly(): void
    {
        $directive = new Directive('include', ['if' => '$showProduct']);
        $inlineFragment = new InlineFragment('Product');

        $inlineFragment->field('id')
            ->field('title')
            ->directive($directive);

        $directives = $inlineFragment->getDirectives();
        $this->assertSame($directive, $directives[0]);
    }

    #[Test]
    public function it_handles_empty_inline_fragment_with_directives(): void
    {
        $inlineFragment = new InlineFragment('Product');
        $inlineFragment->directive('include', ['if' => '$showProduct']);

        $array = $inlineFragment->toArray();

        $this->assertEquals('... on Product', $array['name']);
        $this->assertEmpty($array['fields']);
        $this->assertCount(1, $array['directives']);
    }
}

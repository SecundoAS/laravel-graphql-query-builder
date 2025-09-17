<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Processors;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Processors\DirectiveProcessor;
use Secundo\GraphQL\Types\Directive;

class DirectiveProcessorTest extends TestCase
{
    #[Test]
    public function it_processes_empty_directives_array(): void
    {
        $result = DirectiveProcessor::process([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_keeps_directive_objects_unchanged(): void
    {
        $directive1 = new Directive('include', ['if' => '$showField']);
        $directive2 = new Directive('deprecated', ['reason' => 'Use newField instead']);

        $directives = [$directive1, $directive2];
        $result = DirectiveProcessor::process($directives);

        $this->assertCount(2, $result);
        $this->assertSame($directive1, $result[0]);
        $this->assertSame($directive2, $result[1]);
    }

    #[Test]
    public function it_converts_array_directives_to_directive_objects(): void
    {
        $directives = [
            ['name' => 'include', 'arguments' => ['if' => '$showField']],
            ['name' => 'skip', 'arguments' => ['if' => '$hideField']],
        ];

        $result = DirectiveProcessor::process($directives);

        $this->assertCount(2, $result);

        $this->assertInstanceOf(Directive::class, $result[0]);
        $this->assertEquals('include', $result[0]->getName());
        $this->assertEquals(['if' => '$showField'], $result[0]->getArguments());

        $this->assertInstanceOf(Directive::class, $result[1]);
        $this->assertEquals('skip', $result[1]->getName());
        $this->assertEquals(['if' => '$hideField'], $result[1]->getArguments());
    }

    #[Test]
    public function it_handles_array_directives_without_arguments(): void
    {
        $directives = [
            ['name' => 'cached'],
            ['name' => 'deprecated'],
        ];

        $result = DirectiveProcessor::process($directives);

        $this->assertCount(2, $result);

        $this->assertInstanceOf(Directive::class, $result[0]);
        $this->assertEquals('cached', $result[0]->getName());
        $this->assertEmpty($result[0]->getArguments());

        $this->assertInstanceOf(Directive::class, $result[1]);
        $this->assertEquals('deprecated', $result[1]->getName());
        $this->assertEmpty($result[1]->getArguments());
    }

    #[Test]
    public function it_handles_array_directives_with_missing_name(): void
    {
        $directives = [
            ['arguments' => ['if' => '$showField']], // Missing name
            [], // Completely empty
        ];

        $result = DirectiveProcessor::process($directives);

        $this->assertCount(2, $result);

        $this->assertInstanceOf(Directive::class, $result[0]);
        $this->assertEquals('unknown', $result[0]->getName());
        $this->assertEquals(['if' => '$showField'], $result[0]->getArguments());

        $this->assertInstanceOf(Directive::class, $result[1]);
        $this->assertEquals('unknown', $result[1]->getName());
        $this->assertEmpty($result[1]->getArguments());
    }

    #[Test]
    public function it_converts_string_directives_to_directive_objects(): void
    {
        $directives = ['cached', 'deprecated', 'auth'];

        $result = DirectiveProcessor::process($directives);

        $this->assertCount(3, $result);

        foreach ($result as $i => $directive) {
            $this->assertInstanceOf(Directive::class, $directive);
            $this->assertEquals($directives[$i], $directive->getName());
            $this->assertEmpty($directive->getArguments());
        }
    }

    #[Test]
    public function it_converts_numeric_values_to_string_directives(): void
    {
        $directives = [123, 45.67, true, false];

        $result = DirectiveProcessor::process($directives);

        $this->assertCount(4, $result);

        $this->assertEquals('123', $result[0]->getName());
        $this->assertEquals('45.67', $result[1]->getName());
        $this->assertEquals('1', $result[2]->getName());
        $this->assertEquals('', $result[3]->getName());
    }

    #[Test]
    public function it_processes_mixed_directive_types(): void
    {
        $existingDirective = new Directive('include', ['if' => '$show']);

        $directives = [
            $existingDirective,
            ['name' => 'skip', 'arguments' => ['if' => '$hide']],
            'cached',
            ['name' => 'deprecated'],
        ];

        $result = DirectiveProcessor::process($directives);

        $this->assertCount(4, $result);

        $this->assertSame($existingDirective, $result[0]);

        $this->assertEquals('skip', $result[1]->getName());
        $this->assertEquals(['if' => '$hide'], $result[1]->getArguments());

        $this->assertEquals('cached', $result[2]->getName());
        $this->assertEmpty($result[2]->getArguments());

        $this->assertEquals('deprecated', $result[3]->getName());
        $this->assertEmpty($result[3]->getArguments());
    }

    #[Test]
    public function it_converts_directives_to_array_format(): void
    {
        $directives = [
            new Directive('include', ['if' => '$showField']),
            new Directive('skip', ['if' => '$hideField']),
            new Directive('cached'),
        ];

        $result = DirectiveProcessor::toArray($directives);

        $expected = [
            ['name' => 'include', 'arguments' => ['if' => '$showField']],
            ['name' => 'skip', 'arguments' => ['if' => '$hideField']],
            ['name' => 'cached', 'arguments' => []],
        ];

        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_handles_empty_array_in_to_array_conversion(): void
    {
        $result = DirectiveProcessor::toArray([]);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_finds_directive_by_name(): void
    {
        $includeDirective = new Directive('include', ['if' => '$show']);
        $skipDirective = new Directive('skip', ['if' => '$hide']);
        $cachedDirective = new Directive('cached');

        $directives = [$includeDirective, $skipDirective, $cachedDirective];

        // Test finding existing directives
        $this->assertSame($includeDirective, DirectiveProcessor::findByName($directives, 'include'));
        $this->assertSame($skipDirective, DirectiveProcessor::findByName($directives, 'skip'));
        $this->assertSame($cachedDirective, DirectiveProcessor::findByName($directives, 'cached'));

        // Test finding non-existing directive
        $this->assertNull(DirectiveProcessor::findByName($directives, 'nonexistent'));
    }

    #[Test]
    public function it_finds_directive_by_name_in_empty_array(): void
    {
        $this->assertNull(DirectiveProcessor::findByName([], 'include'));
    }

    #[Test]
    public function it_finds_first_matching_directive_by_name(): void
    {
        $firstInclude = new Directive('include', ['if' => '$first']);
        $secondInclude = new Directive('include', ['if' => '$second']);

        $directives = [$firstInclude, $secondInclude];

        // Should return the first matching directive
        $result = DirectiveProcessor::findByName($directives, 'include');
        $this->assertSame($firstInclude, $result);
    }

    #[Test]
    public function it_checks_if_directive_exists(): void
    {
        $directives = [
            new Directive('include', ['if' => '$show']),
            new Directive('skip', ['if' => '$hide']),
            new Directive('cached'),
        ];

        $this->assertTrue(DirectiveProcessor::hasDirective($directives, 'include'));
        $this->assertTrue(DirectiveProcessor::hasDirective($directives, 'skip'));
        $this->assertTrue(DirectiveProcessor::hasDirective($directives, 'cached'));
        $this->assertFalse(DirectiveProcessor::hasDirective($directives, 'nonexistent'));
    }

    #[Test]
    public function it_checks_directive_existence_in_empty_array(): void
    {
        $this->assertFalse(DirectiveProcessor::hasDirective([], 'include'));
    }

    #[Test]
    public function it_handles_case_sensitive_directive_names(): void
    {
        $directives = [new Directive('Include', ['if' => '$show'])];

        $this->assertTrue(DirectiveProcessor::hasDirective($directives, 'Include'));
        $this->assertFalse(DirectiveProcessor::hasDirective($directives, 'include'));
        $this->assertFalse(DirectiveProcessor::hasDirective($directives, 'INCLUDE'));
    }

    #[Test]
    public function it_handles_multiple_directives_with_same_name(): void
    {
        $directives = [
            new Directive('include', ['if' => '$first']),
            new Directive('include', ['if' => '$second']),
            new Directive('skip', ['if' => '$hide']),
        ];

        $this->assertTrue(DirectiveProcessor::hasDirective($directives, 'include'));
        $this->assertTrue(DirectiveProcessor::hasDirective($directives, 'skip'));

        // findByName should return the first match
        $found = DirectiveProcessor::findByName($directives, 'include');
        $this->assertEquals(['if' => '$first'], $found->getArguments());
    }
}

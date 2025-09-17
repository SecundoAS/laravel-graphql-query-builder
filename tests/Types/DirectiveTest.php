<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Types\Directive;
use Stringable;

class DirectiveTest extends TestCase
{
    #[Test]
    public function it_can_create_directive(): void
    {
        $directive = new Directive('include', ['if' => '$includeField']);

        $this->assertEquals('include', $directive->getName());
        $this->assertEquals(['if' => '$includeField'], $directive->getArguments());
        $this->assertTrue($directive->hasArguments());
    }

    #[Test]
    public function it_can_create_directive_without_arguments(): void
    {
        $directive = new Directive('oneOf');

        $this->assertEquals('oneOf', $directive->getName());
        $this->assertEquals([], $directive->getArguments());
        $this->assertFalse($directive->hasArguments());
    }

    #[Test]
    public function it_can_create_include_directive(): void
    {
        $stringCondition = Directive::include('$includeField');
        $this->assertEquals('include', $stringCondition->getName());
        $this->assertEquals(['if' => '$includeField'], $stringCondition->getArguments());

        $boolCondition = Directive::include(true);
        $this->assertEquals('include', $boolCondition->getName());
        $this->assertEquals(['if' => true], $boolCondition->getArguments());
    }

    #[Test]
    public function it_can_create_skip_directive(): void
    {
        $stringCondition = Directive::skip('$skipField');
        $this->assertEquals('skip', $stringCondition->getName());
        $this->assertEquals(['if' => '$skipField'], $stringCondition->getArguments());

        $boolCondition = Directive::skip(false);
        $this->assertEquals('skip', $boolCondition->getName());
        $this->assertEquals(['if' => false], $boolCondition->getArguments());
    }

    #[Test]
    public function it_can_create_deprecated_directive(): void
    {
        $withReason = Directive::deprecated('This field is deprecated');
        $this->assertEquals('deprecated', $withReason->getName());
        $this->assertEquals(['reason' => 'This field is deprecated'], $withReason->getArguments());

        $withoutReason = Directive::deprecated();
        $this->assertEquals('deprecated', $withoutReason->getName());
        $this->assertEquals([], $withoutReason->getArguments());
    }

    #[Test]
    public function it_can_create_specified_by_directive(): void
    {
        $directive = Directive::specifiedBy('https://example.com/spec');
        $this->assertEquals('specifiedBy', $directive->getName());
        $this->assertEquals(['url' => 'https://example.com/spec'], $directive->getArguments());
    }

    #[Test]
    public function it_can_create_one_of_directive(): void
    {
        $directive = Directive::oneOf();
        $this->assertEquals('oneOf', $directive->getName());
        $this->assertEquals([], $directive->getArguments());
    }

    #[Test]
    public function it_can_manage_arguments(): void
    {
        $directive = new Directive('custom');
        $this->assertFalse($directive->hasArguments());

        $directive->addArgument('param1', 'value1');
        $this->assertTrue($directive->hasArguments());
        $this->assertTrue($directive->hasArgument('param1'));
        $this->assertEquals('value1', $directive->getArgument('param1'));

        $directive->arguments(['param2' => 'value2', 'param3' => 'value3']);
        $this->assertFalse($directive->hasArgument('param1'));
        $this->assertTrue($directive->hasArgument('param2'));
        $this->assertTrue($directive->hasArgument('param3'));
    }

    #[Test]
    public function it_can_detect_built_in_directives(): void
    {
        $this->assertTrue(Directive::include(true)->isBuiltIn());
        $this->assertTrue(Directive::skip(false)->isBuiltIn());
        $this->assertTrue(Directive::deprecated()->isBuiltIn());
        $this->assertTrue(Directive::specifiedBy('url')->isBuiltIn());
        $this->assertTrue(Directive::oneOf()->isBuiltIn());

        $custom = new Directive('custom');
        $this->assertFalse($custom->isBuiltIn());
    }

    #[Test]
    public function it_can_detect_conditional_directives(): void
    {
        $this->assertTrue(Directive::include(true)->isConditional());
        $this->assertTrue(Directive::skip(false)->isConditional());
        $this->assertFalse(Directive::deprecated()->isConditional());
        $this->assertFalse(Directive::oneOf()->isConditional());
    }

    #[Test]
    public function it_can_get_condition_from_conditional_directives(): void
    {
        $include = Directive::include('$shouldInclude');
        $this->assertEquals('$shouldInclude', $include->getCondition());

        $skip = Directive::skip(true);
        $this->assertTrue($skip->getCondition());

        $deprecated = Directive::deprecated();
        $this->assertNull($deprecated->getCondition());
    }

    #[Test]
    public function it_applies_correct_logic(): void
    {
        // Non-conditional directives should always apply
        $deprecated = Directive::deprecated();
        $this->assertTrue($deprecated->shouldApply());

        // Include directive with literal true
        $includeTrue = Directive::include(true);
        $this->assertTrue($includeTrue->shouldApply());

        // Include directive with literal false
        $includeFalse = Directive::include(false);
        $this->assertFalse($includeFalse->shouldApply());

        // Skip directive with literal true
        $skipTrue = Directive::skip(true);
        $this->assertFalse($skipTrue->shouldApply());

        // Skip directive with literal false
        $skipFalse = Directive::skip(false);
        $this->assertTrue($skipFalse->shouldApply());

        // Include directive with variable
        $includeVar = Directive::include('$shouldInclude');
        $this->assertTrue($includeVar->shouldApply(['shouldInclude' => true]));
        $this->assertFalse($includeVar->shouldApply(['shouldInclude' => false]));
        $this->assertFalse($includeVar->shouldApply([])); // Missing variable defaults to false

        // Skip directive with variable
        $skipVar = Directive::skip('$shouldSkip');
        $this->assertFalse($skipVar->shouldApply(['shouldSkip' => true]));
        $this->assertTrue($skipVar->shouldApply(['shouldSkip' => false]));
        $this->assertTrue($skipVar->shouldApply([])); // Missing variable defaults to false, so skip=false means apply
    }

    #[Test]
    public function it_can_format_to_string(): void
    {
        // Simple directive without arguments
        $simple = new Directive('oneOf');
        $this->assertEquals('@oneOf', $simple->toString());
        $this->assertEquals('@oneOf', (string) $simple);

        // Directive with string argument
        $withString = Directive::deprecated('This is deprecated');
        $this->assertEquals('@deprecated(reason: "This is deprecated")', $withString->toString());

        // Directive with variable argument
        $withVariable = Directive::include('$includeThis');
        $this->assertEquals('@include(if: $includeThis)', $withVariable->toString());

        // Directive with boolean argument
        $withBool = Directive::skip(true);
        $this->assertEquals('@skip(if: true)', $withBool->toString());

        // Directive with multiple arguments
        $custom = new Directive('custom', ['param1' => 'value1', 'param2' => 42, 'param3' => true]);
        $expected = '@custom(param1: "value1", param2: 42, param3: true)';
        $this->assertEquals($expected, $custom->toString());
    }

    #[Test]
    public function it_can_handle_complex_argument_types(): void
    {
        $directive = new Directive('complex', [
            'stringArray' => ['item1', 'item2'],
            'objectArg' => ['key1' => 'value1', 'key2' => 42],
            'nestedObject' => [
                'level1' => [
                    'level2' => ['nested1', 'nested2'],
                    'simple' => 'value',
                ],
            ],
        ]);

        $expected = '@complex(stringArray: ["item1", "item2"], objectArg: {key1: "value1", key2: 42}, nestedObject: {level1: {level2: ["nested1", "nested2"], simple: "value"}})';
        $this->assertEquals($expected, $directive->toString());
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $directive = Directive::include('$shouldInclude');
        $array = $directive->toArray();

        $expected = [
            'name' => 'include',
            'arguments' => ['if' => '$shouldInclude'],
        ];

        $this->assertEquals($expected, $array);
    }

    #[Test]
    public function it_can_create_from_array(): void
    {
        $data = [
            'name' => 'custom',
            'arguments' => ['param1' => 'value1', 'param2' => 42],
        ];

        $directive = Directive::fromArray($data);
        $this->assertEquals('custom', $directive->getName());
        $this->assertEquals(['param1' => 'value1', 'param2' => 42], $directive->getArguments());
    }

    #[Test]
    public function it_can_create_from_static_method(): void
    {
        $directive = Directive::create('custom', ['param' => 'value']);
        $this->assertEquals('custom', $directive->getName());
        $this->assertEquals(['param' => 'value'], $directive->getArguments());
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $directive = new Directive('include', ['if' => '$test']);
        $this->assertInstanceOf(Stringable::class, $directive);
    }

    #[Test]
    public function it_casts_directive_string_comprehensively(): void
    {
        // Simple directive without arguments
        $simple = new Directive('oneOf');
        $this->assertEquals('@oneOf', (string) $simple);

        // Directive with single string argument
        $singleString = Directive::deprecated('This field is deprecated');
        $this->assertEquals('@deprecated(reason: "This field is deprecated")', (string) $singleString);

        // Directive with single variable argument
        $singleVariable = Directive::include('$shouldShow');
        $this->assertEquals('@include(if: $shouldShow)', (string) $singleVariable);

        // Directive with single boolean argument
        $singleBoolean = Directive::skip(true);
        $this->assertEquals('@skip(if: true)', (string) $singleBoolean);

        // Directive with multiple arguments
        $multiple = new Directive('custom', ['param1' => 'value1', 'param2' => 42, 'param3' => '$variable']);
        $this->assertEquals('@custom(param1: "value1", param2: 42, param3: $variable)', (string) $multiple);
    }

    #[Test]
    public function it_casts_directive_string_with_edge_cases(): void
    {
        // Directive with null argument
        $withNull = new Directive('nullable', ['value' => null]);
        $this->assertEquals('@nullable(value: null)', (string) $withNull);

        // Directive with array argument
        $withArray = new Directive('arrayDir', ['list' => [1, 2, 3]]);
        $this->assertEquals('@arrayDir(list: [1, 2, 3])', (string) $withArray);

        // Directive with object argument
        $withObject = new Directive('objectDir', ['config' => ['key' => 'value', 'num' => 10]]);
        $this->assertEquals('@objectDir(config: {key: "value", num: 10})', (string) $withObject);
    }
}

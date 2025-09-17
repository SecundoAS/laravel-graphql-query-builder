<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests\Types;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Builder;
use Secundo\GraphQL\Types\Directive;
use Secundo\GraphQL\Types\Field;
use Stringable;

class FieldTest extends TestCase
{
    #[Test]
    public function it_can_construct_field_with_name(): void
    {
        $field = new Field('user');

        $this->assertEquals('user', $field->getName());
        $this->assertEmpty($field->getArguments());
        $this->assertEmpty($field->getFields());
        $this->assertFalse($field->hasDirectives());
    }

    #[Test]
    public function it_can_construct_field_with_all_parameters(): void
    {
        $field = new Field(
            name: 'user',
            arguments: ['id' => '123'],
            fields: ['name', 'email'],
            alias: 'currentUser'
        );

        $this->assertEquals('user', $field->getName());
        $this->assertEquals(['id' => '123'], $field->getArguments());
        $this->assertCount(2, $field->getFields());
    }

    #[Test]
    public function it_can_add_arguments(): void
    {
        $field = new Field('user');
        $field->arguments(['id' => '123', 'active' => true]);

        $this->assertEquals(['id' => '123', 'active' => true], $field->getArguments());
    }

    #[Test]
    public function it_can_merge_arguments(): void
    {
        $field = new Field('user', ['id' => '123']);
        $field->arguments(['active' => true, 'name' => 'John']);

        $this->assertEquals(['id' => '123', 'active' => true, 'name' => 'John'], $field->getArguments());
    }

    #[Test]
    public function it_can_add_single_argument(): void
    {
        $field = new Field('user');
        $field->argument('id', '123');
        $field->argument('active', true);

        $this->assertEquals(['id' => '123', 'active' => true], $field->getArguments());
    }

    #[Test]
    public function it_can_set_alias(): void
    {
        $field = new Field('user');
        $field->alias('currentUser');

        $this->assertEquals('currentUser: user', (string) $field);

        $array = $field->toArray();
        $this->assertEquals('user', $array['name']);
        $this->assertEquals('currentUser', $array['alias']);
    }

    #[Test]
    public function it_can_add_string_fields(): void
    {
        $field = new Field('user');
        $field->fields(['name', 'email', 'phone']);

        $fields = $field->getFields();
        $this->assertCount(3, $fields);
    }

    #[Test]
    public function it_can_add_single_field(): void
    {
        $field = new Field('user');
        $field->field('profile', ['id' => '123'], ['name', 'avatar']);

        $fields = $field->getFields();
        $this->assertCount(1, $fields);
        $this->assertEquals('profile', $fields[0]['name']);
        $this->assertEquals(['id' => '123'], $fields[0]['arguments']);
    }

    #[Test]
    public function it_can_add_field_with_alias(): void
    {
        $field = new Field('user');
        $field->field('profile', [], null, 'userProfile');
        $this->assertEquals("user {\n  userProfile: profile\n}", (string) $field);

        $fields = $field->getFields();
        $this->assertEquals('profile', $fields[0]['name']);
        $this->assertEquals('userProfile', $fields[0]['alias']);
    }

    #[Test]
    public function it_can_add_nested_fields(): void
    {
        $field = new Field('user');
        $field->field('profile', [], ['name', 'email']);

        $fields = $field->getFields();
        $profileField = $fields[0];
        $this->assertEquals('profile', $profileField['name']);
        $this->assertCount(2, $profileField['fields']);
    }

    #[Test]
    public function it_includes_all_field_data_in_array(): void
    {
        $field = new Field('user');
        $field->arguments(['id' => '123'])
            ->alias('currentUser')
            ->fields(['name', 'email']);

        $this->assertEquals("currentUser: user(id: \"123\") {\n  name\n  email\n}", (string) $field);
        $array = $field->toArray();

        $this->assertEquals('user', $array['name']);
        $this->assertEquals('currentUser', $array['alias']);
        $this->assertEquals(['id' => '123'], $array['arguments']);
        $this->assertCount(2, $array['fields']);
        $this->assertEmpty($array['directives']);
    }

    #[Test]
    public function it_generates_correct_graphql_string_for_simple_field(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $builder->field($field);

        $graphql = $builder->toGraphQL();
        $this->assertStringContainsString('user', $graphql);
    }

    #[Test]
    public function it_generates_correct_graphql_string_for_field_with_arguments(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $field->arguments(['id' => '123', 'active' => true]);

        $builder->field($field);

        $graphql = $builder->toGraphQL();
        $this->assertStringContainsString('user(id: "123", active: true)', $graphql);
    }

    #[Test]
    public function it_generates_correct_graphql_string_for_field_with_alias(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $field->alias('currentUser');

        $builder->field($field);

        $graphql = $builder->toGraphQL();
        $this->assertStringContainsString('currentUser: user', $graphql);
    }

    #[Test]
    public function it_generates_correct_graphql_string_for_field_with_sub_fields(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $field->fields(['name', 'email']);

        $builder->field($field);

        $graphql = $builder->toGraphQL();
        $this->assertStringContainsString('user {', $graphql);
        $this->assertStringContainsString('name', $graphql);
        $this->assertStringContainsString('email', $graphql);
    }

    #[Test]
    public function it_generates_correct_graphql_string_for_complex_field(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $field->alias('currentUser')
            ->arguments(['id' => '123'])
            ->fields(['name', 'email']);
        $builder->field($field);

        $graphql = $builder->toGraphQL();
        $this->assertStringContainsString('currentUser: user(id: "123") {', $graphql);
        $this->assertStringContainsString('name', $graphql);
        $this->assertStringContainsString('email', $graphql);
    }

    #[Test]
    public function it_can_add_directive_to_field(): void
    {
        $field = new Field('user');
        $field->directive('deprecated', ['reason' => 'Use newUser instead']);

        $this->assertTrue($field->hasDirectives());
        $this->assertTrue($field->hasDirective('deprecated'));
        $this->assertFalse($field->hasDirective('include'));

        $directive = $field->getDirective('deprecated');
        $this->assertInstanceOf(Directive::class, $directive);
        $this->assertEquals('deprecated', $directive->getName());
        $this->assertEquals(['reason' => 'Use newUser instead'], $directive->getArguments());
    }

    #[Test]
    public function it_can_add_directive_object_to_field(): void
    {
        $directive = Directive::include('$shouldInclude');
        $field = new Field('user');
        $field->directive($directive);

        $this->assertTrue($field->hasDirective('include'));
        $retrievedDirective = $field->getDirective('include');
        $this->assertSame($directive, $retrievedDirective);
    }

    #[Test]
    public function it_can_use_include_helper(): void
    {
        $field = new Field('user');
        $field->include('$shouldShow');

        $this->assertTrue($field->hasDirective('include'));
        $directive = $field->getDirective('include');
        $this->assertEquals('include', $directive->getName());
        $this->assertEquals(['if' => '$shouldShow'], $directive->getArguments());
    }

    #[Test]
    public function it_can_use_skip_helper(): void
    {
        $field = new Field('user');
        $field->skip('$shouldHide');

        $this->assertTrue($field->hasDirective('skip'));
        $directive = $field->getDirective('skip');
        $this->assertEquals('skip', $directive->getName());
        $this->assertEquals(['if' => '$shouldHide'], $directive->getArguments());
    }

    #[Test]
    public function it_can_use_deprecated_helper(): void
    {
        $field = new Field('oldField');
        $field->deprecated('This field is deprecated');

        $this->assertTrue($field->hasDirective('deprecated'));
        $directive = $field->getDirective('deprecated');
        $this->assertEquals('deprecated', $directive->getName());
        $this->assertEquals(['reason' => 'This field is deprecated'], $directive->getArguments());
    }

    #[Test]
    public function it_can_add_multiple_directives(): void
    {
        $field = new Field('user');
        $field->include('$shouldShow')
            ->deprecated('Use newUser instead');

        $this->assertTrue($field->hasDirective('include'));
        $this->assertTrue($field->hasDirective('deprecated'));
        $this->assertCount(2, $field->getDirectives());
    }

    #[Test]
    public function it_includes_directives_in_array(): void
    {
        $field = new Field('user');
        $field->include('$shouldShow')
            ->deprecated('This is old');

        $array = $field->toArray();

        $this->assertArrayHasKey('directives', $array);
        $this->assertCount(2, $array['directives']);

        $includeDirective = $array['directives'][0];
        $this->assertEquals('include', $includeDirective['name']);
        $this->assertEquals(['if' => '$shouldShow'], $includeDirective['arguments']);

        $deprecatedDirective = $array['directives'][1];
        $this->assertEquals('deprecated', $deprecatedDirective['name']);
        $this->assertEquals(['reason' => 'This is old'], $deprecatedDirective['arguments']);
    }

    #[Test]
    public function it_can_get_nonexistent_directive(): void
    {
        $field = new Field('user');
        $this->assertNull($field->getDirective('nonexistent'));
        $this->assertFalse($field->hasDirective('nonexistent'));
    }

    #[Test]
    public function it_handles_field_without_directives(): void
    {
        $field = new Field('user');
        $this->assertFalse($field->hasDirectives());
        $this->assertEmpty($field->getDirectives());

        $array = $field->toArray();
        $this->assertArrayHasKey('directives', $array);
        $this->assertEmpty($array['directives']);
    }

    #[Test]
    public function it_can_chain_directive_methods(): void
    {
        $field = new Field('user');
        $result = $field->include('$shouldShow')
            ->skip('$shouldHide')
            ->deprecated();

        $this->assertSame($field, $result);
        $this->assertCount(3, $field->getDirectives());
    }

    #[Test]
    public function it_generates_correct_graphql_string_for_field_with_directive(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $field->include('$shouldShow');

        $builder->field($field);
        $graphql = $builder->toGraphQL();

        $this->assertStringContainsString('user @include(if: $shouldShow)', $graphql);
    }

    #[Test]
    public function it_generates_correct_graphql_string_for_field_with_multiple_directives(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $field->include('$shouldShow')
            ->deprecated('Use newUser instead');

        $builder->field($field);
        $graphql = $builder->toGraphQL();

        $this->assertStringContainsString('user @include(if: $shouldShow) @deprecated(reason: "Use newUser instead")', $graphql);
    }

    #[Test]
    public function it_formats_different_directive_types_correctly_in_graphql(): void
    {
        $builder = new Builder;

        // Test @include directive
        $includeField = new Field('includeField');
        $includeField->include('$showField');

        $builder->field($includeField);

        // Test @skip directive
        $skipField = new Field('skipField');
        $skipField->skip('$hideField');

        $builder->field($skipField);

        // Test @deprecated directive
        $deprecatedField = new Field('deprecatedField');
        $deprecatedField->deprecated('This field is old');

        $builder->field($deprecatedField);

        $graphql = $builder->toGraphQL();

        $this->assertStringContainsString('includeField @include(if: $showField)', $graphql);
        $this->assertStringContainsString('skipField @skip(if: $hideField)', $graphql);
        $this->assertStringContainsString('deprecatedField @deprecated(reason: "This field is old")', $graphql);
    }

    #[Test]
    public function it_returns_graphql_representation_when_cast_to_string(): void
    {
        $field = new Field('user');
        $this->assertEquals('user', (string) $field);
    }

    #[Test]
    public function it_returns_full_graphql_for_complex_field_when_cast_to_string(): void
    {
        $field = new Field('user');
        $field->arguments(['id' => '123', 'active' => false])
            ->alias('currentUser')
            ->fields(['name', 'email'])
            ->deprecated('Use newUser instead');

        $expected = 'currentUser: user(id: "123", active: false) @deprecated(reason: "Use newUser instead") {
  name
  email
}';
        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_implements_stringable(): void
    {
        $field = new Field('products');
        $this->assertInstanceOf(Stringable::class, $field);
    }

    #[Test]
    public function it_casts_field_with_arguments_only_to_string(): void
    {
        $field = new Field('user');
        $field->arguments(['id' => '123', 'first' => 10, 'active' => true]);

        $expected = 'user(id: "123", first: 10, active: true)';
        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_casts_field_with_alias_only_to_string(): void
    {
        $field = new Field('user');
        $field->alias('currentUser');

        $expected = 'currentUser: user';
        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_casts_field_with_sub_fields_only_to_string(): void
    {
        $field = new Field('user');
        $field->fields(['name', 'email', 'phone']);

        $expected = 'user {
  name
  email
  phone
}';
        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_casts_field_with_directives_only_to_string(): void
    {
        $field = new Field('user');
        $field->include('$shouldShow')->deprecated('Old field');

        $expected = 'user @include(if: $shouldShow) @deprecated(reason: "Old field")';
        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_casts_field_with_nested_sub_fields_to_string(): void
    {
        $field = new Field('user');
        $field->field('profile', ['id' => 'profile123'], ['name', 'avatar']);

        $expected = 'user {
  profile(id: "profile123") {
    name
    avatar
  }
}';
        $this->assertEquals($expected, (string) $field);
    }

    #[Test]
    public function it_casts_field_with_everything_to_string(): void
    {
        $field = new Field('users');
        $field->alias('allUsers')
            ->arguments(['first' => 10, 'after' => '$cursor'])
            ->include('$shouldInclude')
            ->deprecated('Use newUsers instead')
            ->field('edges', [], function ($field): void {
                $field->field('node', [], ['id', 'name', 'email']);
            });

        $expected = 'allUsers: users(first: 10, after: $cursor) @include(if: $shouldInclude) @deprecated(reason: "Use newUsers instead") {
  edges {
    node {
      id
      name
      email
    }
  }
}';
        $this->assertEquals($expected, (string) $field);
    }
}

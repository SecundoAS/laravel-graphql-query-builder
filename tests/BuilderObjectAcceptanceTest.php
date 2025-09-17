<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Builder;
use Secundo\GraphQL\Types\Directive;
use Secundo\GraphQL\Types\Field;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\Variable;

class BuilderObjectAcceptanceTest extends TestCase
{
    #[Test]
    public function it_can_accept_variable_object(): void
    {
        $builder = new Builder;
        $variable = new Variable('userId', 'ID!', 'gid://shopify/User/123');

        $builder->variable($variable);

        $retrievedVariable = $builder->getVariable('userId');
        $this->assertSame($variable, $retrievedVariable);
        $this->assertEquals('userId', $retrievedVariable->getName());
        $this->assertEquals('ID!', $retrievedVariable->getType());
        $this->assertEquals('gid://shopify/User/123', $retrievedVariable->getValue());
    }

    #[Test]
    public function it_can_accept_variable_objects_in_variables_array(): void
    {
        $builder = new Builder;
        $variable1 = new Variable('first', 'Int', 10);
        $variable2 = new Variable('query', 'String', 'title:test');

        $builder->variables([
            $variable1,
            $variable2,
            'manual' => ['type' => 'Boolean', 'value' => true],  // Mixed with traditional syntax
        ]);

        $this->assertSame($variable1, $builder->getVariable('first'));
        $this->assertSame($variable2, $builder->getVariable('query'));
        $this->assertInstanceOf(Variable::class, $builder->getVariable('manual'));
        $this->assertEquals('Boolean', $builder->getVariable('manual')->getType());
    }

    #[Test]
    public function it_can_accept_fragment_object(): void
    {
        $builder = new Builder;
        $fragment = Fragment::create('UserFields', 'User');
        $fragment->fields(['id', 'name', 'email']);

        $builder->fragment($fragment);

        $retrievedFragment = $builder->getFragment('UserFields');
        $this->assertSame($fragment, $retrievedFragment);
        $this->assertEquals('UserFields', $retrievedFragment->getName());
        $this->assertEquals('User', $retrievedFragment->getOnType());
        $this->assertCount(3, $retrievedFragment->getFields());
        $this->assertEquals('fragment UserFields on User {
  id
  name
  email
}', (string) $retrievedFragment);
    }

    #[Test]
    public function it_uses_fragment_object_overriding_other_parameters(): void
    {
        $builder = new Builder;
        $fragment = Fragment::create('ProductFields', 'Product');
        $fragment->fields(['id', 'title', 'handle']);

        // The fragment object should be used, ignoring other parameters
        $builder->fragment($fragment, 'IgnoredType', function (): void {
            // This callback should be ignored
        });

        $retrievedFragment = $builder->getFragment('ProductFields');
        $this->assertSame($fragment, $retrievedFragment);
        $this->assertEquals('Product', $retrievedFragment->getOnType()); // Not 'IgnoredType'
        $fields = $retrievedFragment->getFields();
        $fieldNames = array_map(fn (array $field) => $field['name'], $fields);
        $this->assertEquals(['id', 'title', 'handle'], $fieldNames);
    }

    #[Test]
    public function it_can_accept_field_object(): void
    {
        $builder = new Builder;
        $field = new Field('user');
        $field->arguments(['id' => '$userId'])
            ->fields(['id', 'name', 'email'])
            ->include('$includeUser');

        $builder->field($field);

        $fields = $builder->getFieldsArray();
        $this->assertCount(1, $fields);

        $fieldArray = $fields[0];
        $this->assertEquals('user', $fieldArray['name']);
        $this->assertEquals(['id' => '$userId'], $fieldArray['arguments']);
        $this->assertCount(3, $fieldArray['fields']);
        $this->assertCount(1, $fieldArray['directives']);
    }

    #[Test]
    public function it_uses_field_object_overriding_other_parameters(): void
    {
        $builder = new Builder;
        $field = new Field('product');
        $field->fields(['id', 'title']);

        // The field object should be used, ignoring other parameters
        $builder->field($field, ['ignored', 'fields'], ['ignored' => 'argument']);

        $fields = $builder->getFieldsArray();
        $this->assertCount(1, $fields);

        $fieldArray = $fields[0];
        $this->assertEquals('product', $fieldArray['name']);
        $this->assertCount(2, $fieldArray['fields']); // Original fields, not ignored ones
        $this->assertEmpty($fieldArray['arguments']); // No ignored arguments
    }

    #[Test]
    public function it_can_mix_object_and_traditional_syntax(): void
    {
        $builder = new Builder;

        // Mix variables
        $variableObject = new Variable('first', 'Int', 25);
        $builder->variable($variableObject)
            ->variable('query', 'String', 'title:test'); // Traditional syntax

        // Mix fragments
        $fragmentObject = Fragment::create('ProductFields', 'Product');
        $fragmentObject->fields(['id', 'title']);
        $builder->fragment($fragmentObject)
            ->fragment('UserFields', 'User', function (Builder $builder): void {
                $builder->fields(['id', 'name']);
            });

        // Mix fields
        $fieldObject = new Field('shop');
        $fieldObject->fields(['id', 'name']);
        $builder->field($fieldObject)
            ->field('products', [], ['id', 'title']); // Traditional syntax

        // Verify all were added correctly
        $this->assertCount(2, $builder->getVariables());
        $this->assertSame($variableObject, $builder->getVariable('first'));
        $this->assertInstanceOf(Variable::class, $builder->getVariable('query'));

        $this->assertCount(2, $builder->getFragments());
        $this->assertSame($fragmentObject, $builder->getFragment('ProductFields'));
        $this->assertInstanceOf(Fragment::class, $builder->getFragment('UserFields'));

        $this->assertCount(2, $builder->getFieldsArray());
    }

    #[Test]
    public function it_can_build_complex_query_with_objects(): void
    {
        $builder = new Builder;

        // Create objects
        $userIdVariable = Variable::create('userId', 'ID!', 'gid://shopify/User/123');
        $includeMetaVariable = Variable::create('includeMeta', 'Boolean', true);

        $userFragment = Fragment::create('UserFields', 'User');
        $userFragment->fields(['id', 'name', 'email']);

        $userField = new Field('user');
        $userField->arguments(['id' => '$userId'])
            ->fields(['...UserFields', 'phone'])
            ->include('$includeMeta');

        // Build query using objects
        $query = $builder->query('GetUser')
            ->variable($userIdVariable)
            ->variable($includeMetaVariable)
            ->fragment($userFragment)
            ->field($userField)
            ->toGraphQL();

        // Verify the query structure
        $this->assertStringContainsString('query GetUser($userId: ID!, $includeMeta: Boolean)', $query);
        $this->assertStringContainsString('fragment UserFields on User', $query);
        $this->assertStringContainsString('user(id: $userId) @include(if: $includeMeta)', $query);
        $this->assertStringContainsString('...UserFields', $query);
        $this->assertStringContainsString('phone', $query);
    }

    #[Test]
    public function it_handles_field_with_directive_objects(): void
    {
        $builder = new Builder;

        $includeDirective = Directive::include('$shouldShow');
        $deprecatedDirective = Directive::deprecated('Use newField instead');

        $field = new Field('oldField');
        $field->directive($includeDirective)
            ->directive($deprecatedDirective);

        $builder->field($field);

        $fields = $builder->getFieldsArray();
        $fieldArray = $fields[0];

        $this->assertCount(2, $fieldArray['directives']);
        $this->assertEquals('include', $fieldArray['directives'][0]['name']);
        $this->assertEquals(['if' => '$shouldShow'], $fieldArray['directives'][0]['arguments']);
        $this->assertEquals('deprecated', $fieldArray['directives'][1]['name']);
        $this->assertEquals(['reason' => 'Use newField instead'], $fieldArray['directives'][1]['arguments']);
    }

    #[Test]
    public function it_preserves_variable_object_values(): void
    {
        $builder = new Builder;

        $varWithValue = Variable::create('first', 'Int', 10);
        $varWithoutValue = Variable::create('query', 'String');

        $builder->variable($varWithValue)
            ->variable($varWithoutValue);

        $values = $builder->getVariableValues();

        $this->assertArrayHasKey('first', $values);
        $this->assertEquals(10, $values['first']);
        $this->assertArrayNotHasKey('query', $values); // No value set
    }

    #[Test]
    public function it_uses_fragment_objects_in_query_generation(): void
    {
        $builder = new Builder;

        $fragment = Fragment::create('ProductInfo', 'Product');
        $fragment->fields(['id', 'title', 'handle']);

        $query = $builder->query()
            ->fragment($fragment)
            ->field('products', [], function ($builder): void {
                $builder->field('edges', [], function ($builder): void {
                    $builder->field('node', [], ['...ProductInfo']);
                });
            })
            ->toGraphQL();

        $this->assertStringContainsString('fragment ProductInfo on Product', $query);
        $this->assertStringContainsString('id', $query);
        $this->assertStringContainsString('title', $query);
        $this->assertStringContainsString('handle', $query);
        $this->assertStringContainsString('...ProductInfo', $query);
    }
}

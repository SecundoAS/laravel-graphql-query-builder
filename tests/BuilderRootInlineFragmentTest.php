<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\Builder;
use Secundo\GraphQL\Types\Directive;
use Secundo\GraphQL\Types\Fragment;
use Secundo\GraphQL\Types\InlineFragment;

class BuilderRootInlineFragmentTest extends TestCase
{
    #[Test]
    public function it_can_add_inline_fragment_at_root_level_with_callback(): void
    {
        $builder = new Builder;
        $query = $builder->query('GetNode')
            ->variable('id', 'ID!')
            ->field('node', ['id' => '$id'], function ($field): void {
                $field->field('id')
                    ->field('__typename');
            })
            ->inlineFragment('User', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name')
                    ->field('email');
            })
            ->toGraphQL();

        $expected = 'query GetNode($id: ID!) {
  node(id: $id) {
    id
    __typename
  }
  ... on User {
    name
    email
  }
}';

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_add_inline_fragment_at_root_level_with_array(): void
    {
        $builder = new Builder;
        $query = $builder->query('GetCurrentUser')
            ->field('__typename')
            ->inlineFragment('Query', ['currentUser' => ['id', 'name', 'email']])
            ->toGraphQL();

        $expected = 'query GetCurrentUser {
  __typename
  ... on Query {
    currentUser {
      id
      name
      email
    }
  }
}';

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_add_multiple_inline_fragments_at_root_level(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('__typename')
            ->inlineFragment('Query', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('currentUser', [], ['id', 'name']);
            })
            ->inlineFragment('Mutation', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('lastMutation', [], ['id', 'timestamp']);
            })
            ->toGraphQL();

        $expected = 'query {
  __typename
  ... on Query {
    currentUser {
      id
      name
    }
  }
  ... on Mutation {
    lastMutation {
      id
      timestamp
    }
  }
}';

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_use_inline_fragments_with_unions_at_root(): void
    {
        $builder = new Builder;
        $query = $builder->query('SearchResults')
            ->variable('query', 'String!')
            ->field('search', ['query' => '$query'], function ($field): void {
                $field->field('id')
                    ->field('__typename');
            })
            ->inlineFragment('User', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('username')
                    ->field('displayName');
            })
            ->inlineFragment('Organization', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name')
                    ->field('description');
            })
            ->toGraphQL();

        $expected = 'query SearchResults($query: String!) {
  search(query: $query) {
    id
    __typename
  }
  ... on User {
    username
    displayName
  }
  ... on Organization {
    name
    description
  }
}';

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_add_empty_inline_fragment_at_root_level(): void
    {
        $builder = new Builder;
        $query = $builder->query()
            ->field('__typename')
            ->inlineFragment('Query') // No fields
            ->toGraphQL();

        $expected = 'query {
  __typename
  ... on Query
}';

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_can_use_inline_fragments_with_directives_at_root(): void
    {
        $builder = new Builder;
        $query = $builder->query('ConditionalFields')
            ->variable('includeAdmin', 'Boolean!')
            ->field('currentUser', [], ['id', 'name'])
            ->inlineFragment('AdminUser', function (InlineFragment $inlineFragment): void {
                $inlineFragment->directive('include', ['if' => '$includeAdmin'])
                    ->field('adminLevel')
                    ->field('permissions');
            })
            ->toGraphQL();

        $expected = 'query ConditionalFields($includeAdmin: Boolean!) {
  currentUser {
    id
    name
  }
  ... on AdminUser @include(if: $includeAdmin) {
    adminLevel
    permissions
  }
}';

        $this->assertEquals($expected, $query);
    }

    #[Test]
    public function it_supports_method_chaining_with_root_inline_fragments(): void
    {
        $builder = new Builder;

        $result = $builder->query('ChainTest')
            ->field('__typename')
            ->inlineFragment('User', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name');
            })
            ->inlineFragment('Organization', function (InlineFragment $inlineFragment): void {
                $inlineFragment->field('name');
            });

        $this->assertSame($builder, $result);

        $fields = $builder->getFieldsArray();
        $this->assertCount(3, $fields); // __typename + 2 inline fragments
    }

    #[Test]
    public function it_can_convert_regular_fragment_to_inline_at_root(): void
    {
        $builder = new Builder;

        // Create a regular fragment
        $regularFragment = $builder->getFragment('UserFields');
        if (! $regularFragment instanceof Fragment) {
            // Create it first
            $builder->fragment('UserFields', 'User', ['id', 'name']);
            $regularFragment = $builder->getFragment('UserFields');
        }

        $query = $builder->query()
            ->field('__typename')
            ->inlineFragment($regularFragment) // Convert Fragment to inline
            ->toGraphQL();

        $this->assertStringContainsString('... on User', $query);
        $this->assertStringContainsString('fragment UserFields on User', $query);
    }

    #[Test]
    public function it_can_add_inline_fragment_with_directives_using_new_signature(): void
    {
        $builder = new Builder;
        $query = $builder->query('ConditionalQuery')
            ->variable('showUser', 'Boolean!')
            ->field('currentUser', [], ['id', 'name'])
            ->inlineFragment('AdminUser',
                function (InlineFragment $inlineFragment): void {
                    $inlineFragment->field('adminLevel')
                        ->field('permissions');
                },
                [
                    ['name' => 'include', 'arguments' => ['if' => '$showUser']],
                    ['name' => 'cached', 'arguments' => []],
                ]
            )
            ->toGraphQL();

        $this->assertStringContainsString('... on AdminUser @include(if: $showUser) @cached', $query);
        $this->assertStringContainsString('adminLevel', $query);
        $this->assertStringContainsString('permissions', $query);
    }

    #[Test]
    public function it_can_add_inline_fragment_with_directive_objects(): void
    {
        $builder = new Builder;

        $includeDirective = new Directive('include', ['if' => '$showUser']);

        $query = $builder->query('DirectiveTest')
            ->variable('showUser', 'Boolean!')
            ->field('__typename')
            ->inlineFragment('User',
                ['name', 'email'],
                [$includeDirective]
            )
            ->toGraphQL();

        $this->assertStringContainsString('... on User @include(if: $showUser)', $query);
        $this->assertStringContainsString('name', $query);
        $this->assertStringContainsString('email', $query);
    }
}

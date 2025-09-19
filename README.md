# Laravel GraphQL Query Builder

[![Latest Version on Packagist](https://img.shields.io/packagist/v/secundo/laravel-graphql-query-builder.svg?style=flat-square)](https://packagist.org/packages/secundo/laravel-graphql-query-builder)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/SecundoAS/laravel-graphql-query-builder/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/SecundoAS/laravel-graphql-query-builder/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/secundo/laravel-graphql-query-builder.svg?style=flat-square)](https://packagist.org/packages/secundo/laravel-graphql-query-builder)

A powerful and elegant GraphQL query builder for PHP, designed specifically for Laravel applications. Build GraphQL queries and mutations with a fluent, type-safe interface.

## Features

- **Fluent API**: Build GraphQL queries using an intuitive, chainable interface
- **Type Safety**: Full PHP 8+ type hints for better IDE support and fewer runtime errors
- **Laravel Integration**: Built with Laravel conventions and auto-discovery
- **Variable Support**: Automatic variable handling with type safety
- **Fragment Support**: Create reusable query fragments
- **Inline Fragments**: Type-based conditional field selection

## Requirements

- PHP 8.3 or higher
- Laravel 11.0 or higher

## Installation

You can install the package via composer:

```bash
composer require secundo/laravel-graphql-query-builder
```

The service provider will be automatically registered using Laravel's package discovery.

## Quick Start

### Basic Query

```php
use Secundo\GraphQL\Builder;

$builder = new Builder();

$query = $builder->query()
    ->field('products', [], ['id', 'title', 'handle'])
    ->toGraphQL();

// Output:
// query {
//   products {
//     id
//     title
//     handle
//   }
// }
```

### Query with Arguments

```php
$query = $builder->query()
    ->field('product', ['id' => 'gid://shopify/Product/123'], ['id', 'title', 'handle'])
    ->toGraphQL();

// Output:
// query {
//   product(id: "gid://shopify/Product/123") {
//     id
//     title
//     handle
//   }
// }
```

### Nested Fields

```php
$query = $builder->query()
    ->field('products', ['first' => 10], function ($field) {
        $field->field('edges', [], function ($field) {
            $field->field('node', [], ['id', 'title']);
            $field->field('cursor');
        });
        $field->field('pageInfo', [], ['hasNextPage', 'endCursor']);
    })
    ->toGraphQL();
```

### Using Variables

```php
$query = $builder->query('GetProduct')
    ->variable('id', 'ID!', 'gid://shopify/Product/123')
    ->field('product', ['id' => '$id'], ['id', 'title', 'description'])
    ->toGraphQL();

// Get variable values for the request
$variables = $builder->getVariableValues();

// Output:
// query GetProduct($id: ID!) {
//   product(id: $id) {
//     id
//     title
//     description
//   }
// }
// Variables: {"id": "gid://shopify/Product/123"}
```

### Mutations

```php
$mutation = $builder->mutation('UpdateProduct')
    ->variable('id', 'ID!')
    ->variable('input', 'ProductInput!')
    ->field('productUpdate', ['id' => '$id', 'product' => '$input'], function ($field) {
        $field->field('product', [], ['id', 'title']);
        $field->field('userErrors', [], ['field', 'message']);
    })
    ->toGraphQL();
```

### Fragments

```php
$query = $builder->query()
    ->fragment('ProductFields', 'Product', ['id', 'title', 'handle', 'createdAt'])
    ->field('products', [], function ($field) {
        $field->field('edges', [], function ($field) {
            $field->field('node', [], ['...ProductFields']);
        });
    })
    ->toGraphQL();
```

### Inline Fragments

```php
$query = $builder->query()
    ->field('node', ['id' => '$id'], function ($field) {
        $field->field('id');
        $field->inlineFragment('Product', function ($fragment) {
            $fragment->field('title');
            $fragment->field('handle');
        });
        $field->inlineFragment('Collection', function ($fragment) {
            $fragment->field('title');
            $fragment->field('description');
        });
    })
    ->toGraphQL();

// Output:
// query {
//   node(id: $id) {
//     id
//     ... on Product {
//       title
//       handle
//     }
//     ... on Collection {
//       title
//       description
//     }
//   }
// }
```

## Advanced Usage

### Multiple Variables with Default Values

```php
$query = $builder->query('SearchProducts')
    ->variables([
        'first' => ['type' => 'Int', 'value' => 10],
        'query' => ['type' => 'String', 'value' => 'title:shirt'],
        'sortKey' => ['type' => 'ProductSortKeys', 'value' => 'CREATED_AT']
    ])
    ->field('products', [
        'first' => '$first',
        'query' => '$query',
        'sortKey' => '$sortKey'
    ], ['id', 'title']);
```

### Field Aliases

```php
use Secundo\GraphQL\Types\Field;

$field = new Field('product');
$field->alias('myProduct')
    ->arguments(['id' => 'gid://shopify/Product/123'])
    ->fields(['id', 'title']);
```

### Directives

```php
$field = new Field('expensiveField');
$field->directive('include', ['if' => '$includeExpensive'])
    ->fields(['data']);

$field = new Field('debugInfo');
$field->directive('skip', ['if' => '$production'])
    ->fields(['logs']);
```

## Laravel Integration

### Using the Facade

```php
use Secundo\GraphQL\Facades\GraphQL;

$query = GraphQL::query()
    ->field('shop', [], ['name', 'email'])
    ->toGraphQL();
```

### Dependency Injection

```php
use Secundo\GraphQL\GraphQL;

class ProductController extends Controller
{
    public function __construct(
        private GraphQL $graphql
    ) {}

    public function index()
    {
        $query = $this->graphql->query()
            ->field('products', ['first' => 10], ['id', 'title'])
            ->toGraphQL();
            
        // Execute query...
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

If you discover a security vulnerability, please send an email to einar@secundo.com. All security vulnerabilities will be promptly addressed.

## Credits

- [Secundo Team](https://github.com/SecundoAS)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

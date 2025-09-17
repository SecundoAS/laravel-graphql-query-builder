<?php

declare(strict_types=1);

namespace Secundo\GraphQL\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Secundo\GraphQL\ArgumentBuilder;
use Stringable;

class ArgumentBuilderTest extends TestCase
{
    #[Test]
    public function it_implements_stringable(): void
    {
        $builder = ArgumentBuilder::create();
        $this->assertInstanceOf(Stringable::class, $builder);
    }

    #[Test]
    public function it_can_create_simple_where_condition(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active');

        $this->assertEquals('status:active', $builder->toString());
    }

    #[Test]
    public function it_can_create_where_condition_with_explicit_operator(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('created_at', '>', '2020-01-01');

        $this->assertEquals('created_at:>"2020-01-01"', $builder->toString());
    }

    #[Test]
    public function it_can_create_multiple_and_conditions(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where('vendor', 'Nike');

        $this->assertEquals('status:active AND vendor:Nike', $builder->toString());
    }

    #[Test]
    public function it_can_create_or_conditions(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('vendor', 'Nike')
            ->orWhere('vendor', 'Adidas');

        $this->assertEquals('vendor:Nike OR vendor:Adidas', $builder->toString());
    }

    #[Test]
    public function it_can_create_not_conditions(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->whereNot('vendor', 'BadVendor');

        $this->assertEquals('status:active AND NOT vendor:BadVendor', $builder->toString());
    }

    #[Test]
    public function it_can_create_where_in_conditions(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereIn('status', ['active', 'draft']);

        $this->assertEquals('status:active,draft', $builder->toString());
    }

    #[Test]
    public function it_can_create_where_not_in_conditions(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereNotIn('status', ['disabled', 'archived']);

        $this->assertEquals('NOT status:disabled,archived', $builder->toString());
    }

    #[Test]
    public function it_can_create_wildcard_searches(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereStartsWith('title', 'iPhone');

        $this->assertEquals('title:iPhone*', $builder->toString());
    }

    #[Test]
    public function it_can_create_custom_wildcard_patterns(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereWildcard('sku', 'SHIRT-*-BLUE');

        $this->assertEquals('sku:SHIRT-*-BLUE', $builder->toString());
    }

    #[Test]
    public function it_can_create_phrase_searches(): void
    {
        $builder = ArgumentBuilder::create()
            ->wherePhrase('title', 'sale shopper');

        $this->assertEquals('title:"sale shopper"', $builder->toString());
    }

    #[Test]
    public function it_can_create_contains_searches(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereContains('tags', 'summer');

        $this->assertEquals('tags:summer', $builder->toString());
    }

    #[Test]
    public function it_can_create_full_text_search(): void
    {
        $builder = ArgumentBuilder::create()
            ->search('bob norman');

        $this->assertEquals('bob norman', $builder->toString());
    }

    #[Test]
    public function it_can_create_date_comparisons(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereDate('created_at', '>', '2020-01-01')
            ->whereDate('updated_at', '<', 'now');

        $this->assertEquals('created_at:>"2020-01-01" AND updated_at:<now', $builder->toString());
    }

    #[Test]
    public function it_can_create_raw_queries(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->whereRaw('(vendor:Nike OR vendor:Adidas)');

        $this->assertEquals('status:active AND (vendor:Nike OR vendor:Adidas)', $builder->toString());
    }

    #[Test]
    public function it_can_create_nested_conditions_with_closures(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->where('vendor', 'Nike')
                    ->orWhere('vendor', 'Adidas');
            });

        $this->assertEquals('status:active AND (vendor:Nike OR vendor:Adidas)', $builder->toString());
    }

    #[Test]
    public function it_can_create_complex_nested_conditions(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('created_at', '<', 'now')
            ->where(function ($query): void {
                $query->where('state', 'disabled')
                    ->where(function ($subQuery): void {
                        $subQuery->wherePhrase('tags', 'sale shopper')
                            ->orWhere('customer_type', 'VIP');
                    });
            });

        $expected = 'created_at:<now AND (state:disabled AND (tags:"sale shopper" OR customer_type:VIP))';
        $this->assertEquals($expected, $builder->toString());
    }

    #[Test]
    public function it_handles_comparison_operators(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('price', '>', 100)
            ->where('quantity', '<=', 50)
            ->where('rating', '>=', 4);

        $this->assertEquals('price:>100 AND quantity:<=50 AND rating:>=4', $builder->toString());
    }

    #[Test]
    public function it_handles_boolean_values(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('featured', true)
            ->where('discontinued', false);

        $this->assertEquals('featured:true AND discontinued:false', $builder->toString());
    }

    #[Test]
    public function it_handles_null_values(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('description', null);

        $this->assertEquals('description:null', $builder->toString());
    }

    #[Test]
    public function it_handles_variable_references(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where('created_at', '>', '$startDate');

        $this->assertEquals('status:active AND created_at:>$startDate', $builder->toString());
    }

    #[Test]
    public function it_escapes_special_characters_in_values(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('title', 'Product: Special (Edition)');

        $this->assertEquals('title:"Product\\: Special \\(Edition\\)"', $builder->toString());
    }

    #[Test]
    public function it_handles_values_with_spaces(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('vendor', 'Big Brand Name');

        $this->assertEquals('vendor:"Big Brand Name"', $builder->toString());
    }

    #[Test]
    public function it_handles_already_quoted_values(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('title', '"Already Quoted"');

        $this->assertEquals('title:"Already Quoted"', $builder->toString());
    }

    #[Test]
    public function it_handles_special_date_values(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('created_at', '>', 'now')
            ->where('updated_at', '<', 'today')
            ->where('published_at', '>=', 'yesterday');

        $this->assertEquals('created_at:>now AND updated_at:<today AND published_at:>=yesterday', $builder->toString());
    }

    #[Test]
    public function it_can_combine_different_condition_types(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('created_at', '>', '2020-10-21T23:39:20Z')
            ->whereIn('status', ['active', 'draft'])
            ->where(function ($query): void {
                $query->where('vendor', 'Snowdevil')
                    ->orWhere('vendor', 'Icedevil');
            })
            ->search('bob OR norman AND Shopify');

        $expected = 'created_at:>"2020-10-21T23:39:20Z" AND status:active,draft AND (vendor:Snowdevil OR vendor:Icedevil) AND bob OR norman AND Shopify';
        $this->assertEquals($expected, $builder->toString());
    }

    #[Test]
    public function it_returns_empty_string_for_no_conditions(): void
    {
        $builder = ArgumentBuilder::create();

        $this->assertEquals('', $builder->toString());
    }

    #[Test]
    public function it_can_be_cast_to_string(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active');

        $this->assertEquals('status:active', (string) $builder);
    }

    #[Test]
    public function it_supports_or_where_not(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->orWhereNot('vendor', 'BadVendor');

        $this->assertEquals('status:active OR NOT vendor:BadVendor', $builder->toString());
    }

    #[Test]
    public function it_supports_or_where_in(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereIn('status', ['active', 'draft'])
            ->orWhereIn('type', ['physical', 'digital']);

        $this->assertEquals('status:active,draft OR type:physical,digital', $builder->toString());
    }

    #[Test]
    public function it_supports_or_where_not_in(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->orWhereNotIn('vendor', ['BadVendor', 'WorseVendor']);

        $this->assertEquals('status:active OR NOT vendor:BadVendor,WorseVendor', $builder->toString());
    }

    #[Test]
    public function it_supports_or_variants_of_special_methods(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereStartsWith('title', 'iPhone')
            ->orWhereWildcard('sku', 'ANDROID-*')
            ->orWherePhrase('description', 'best seller')
            ->orWhereContains('tags', 'electronics')
            ->orSearch('tablet OR laptop')
            ->orWhereDate('created_at', '>', '2020-01-01')
            ->orWhereRaw('featured:true');

        $expected = 'title:iPhone* OR sku:ANDROID-* OR description:"best seller" OR tags:electronics OR tablet OR laptop OR created_at:>"2020-01-01" OR featured:true';
        $this->assertEquals($expected, $builder->toString());
    }

    #[Test]
    public function it_handles_empty_nested_conditions(): void
    {
        $builder = ArgumentBuilder::create()
            ->where('status', 'active')
            ->where(function ($query): void {
                // Empty nested condition
            });

        $this->assertEquals('status:active', $builder->toString());
    }

    #[Test]
    public function it_handles_numeric_array_values(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereIn('id', [1, 2, 3, 4]);

        $this->assertEquals('id:1,2,3,4', $builder->toString());
    }

    #[Test]
    public function it_handles_mixed_array_values(): void
    {
        $builder = ArgumentBuilder::create()
            ->whereIn('mixed', ['string', 123, true, false]);

        $this->assertEquals('mixed:string,123,true,false', $builder->toString());
    }
}

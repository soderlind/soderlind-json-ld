<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests\Schema;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\Schema\BreadcrumbListSchema;
use Soderlind\JsonLd\Tests\TestCase;

final class BreadcrumbListSchemaTest extends TestCase {

    public function test_not_applicable_on_front_page(): void {
        Functions\expect('is_front_page')->once()->andReturn(true);

        $schema = new BreadcrumbListSchema();
        $this->assertFalse($schema->is_applicable());
    }

    public function test_applicable_on_inner_pages(): void {
        Functions\expect('is_front_page')->once()->andReturn(false);

        $schema = new BreadcrumbListSchema();
        $this->assertTrue($schema->is_applicable());
    }

    public function test_generates_breadcrumbs_for_singular_post(): void {
        Functions\expect('is_front_page')->andReturn(false);
        Functions\expect('is_singular')->andReturn(true);
        Functions\expect('is_category')->andReturn(false);
        Functions\expect('is_tag')->andReturn(false);
        Functions\expect('is_tax')->andReturn(false);
        Functions\expect('is_post_type_archive')->andReturn(false);
        Functions\expect('is_author')->andReturn(false);
        Functions\expect('is_date')->andReturn(false);
        Functions\expect('is_search')->andReturn(false);

        $post = $this->make_post(['post_type' => 'post']);
        Functions\expect('get_post')->andReturn($post);
        Functions\expect('is_post_type_hierarchical')->with('post')->andReturn(false);

        $cat = $this->make_term(['term_id' => 5, 'name' => 'Tech', 'parent' => 0]);
        Functions\expect('get_the_category')->with(1)->andReturn([$cat]);
        Functions\expect('get_category_link')->with(5)->andReturn('https://example.com/category/tech/');
        Functions\expect('get_the_title')->andReturn('Test Post');
        Functions\expect('get_permalink')->andReturn('https://example.com/test-post/');
        Functions\expect('get_ancestors')->andReturn([]);

        $schema = new BreadcrumbListSchema();
        $data = $schema->generate();

        $this->assertSame('BreadcrumbList', $data['@type']);
        $this->assertCount(3, $data['itemListElement']);
        $this->assertSame('Test Site', $data['itemListElement'][0]['name']);
        $this->assertSame(1, $data['itemListElement'][0]['position']);
        $this->assertSame('Tech', $data['itemListElement'][1]['name']);
        $this->assertSame('Test Post', $data['itemListElement'][2]['name']);
    }

    public function test_generates_breadcrumbs_for_hierarchical_page(): void {
        Functions\expect('is_front_page')->andReturn(false);
        Functions\expect('is_singular')->andReturn(true);
        Functions\expect('is_category')->andReturn(false);
        Functions\expect('is_tag')->andReturn(false);
        Functions\expect('is_tax')->andReturn(false);
        Functions\expect('is_post_type_archive')->andReturn(false);
        Functions\expect('is_author')->andReturn(false);
        Functions\expect('is_date')->andReturn(false);
        Functions\expect('is_search')->andReturn(false);

        $child = $this->make_post([
            'ID'          => 10,
            'post_title'  => 'Child Page',
            'post_type'   => 'page',
            'post_parent' => 5,
        ]);

        $parent = $this->make_post([
            'ID'         => 5,
            'post_title' => 'Parent Page',
            'post_type'  => 'page',
        ]);

        Functions\expect('get_post')->andReturn($child, $parent);
        Functions\expect('is_post_type_hierarchical')->with('page')->andReturn(true);
        Functions\expect('get_post_ancestors')->with($child)->andReturn([5]);
        Functions\expect('get_the_title')->andReturn('Parent Page', 'Child Page');
        Functions\expect('get_permalink')->andReturn('https://example.com/parent/', 'https://example.com/parent/child/');

        $schema = new BreadcrumbListSchema();
        $data = $schema->generate();

        $this->assertSame('BreadcrumbList', $data['@type']);
        $this->assertCount(3, $data['itemListElement']);
        $this->assertSame('Parent Page', $data['itemListElement'][1]['name']);
        $this->assertSame('Child Page', $data['itemListElement'][2]['name']);
    }
}

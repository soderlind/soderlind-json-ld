<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests\Schema;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\Schema\BlogPostingSchema;
use Soderlind\JsonLd\Tests\TestCase;

final class BlogPostingSchemaTest extends TestCase {

	public function test_applicable_on_single_post(): void {
		Functions\expect( 'is_singular' )->with( 'post' )->andReturn( true );

		$schema = new BlogPostingSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_not_applicable_on_page(): void {
		Functions\expect( 'is_singular' )->with( 'post' )->andReturn( false );

		$schema = new BlogPostingSchema();
		$this->assertFalse( $schema->is_applicable() );
	}

	public function test_generates_blogposting_schema(): void {
		$post = $this->make_post( [
			'ID'           => 42,
			'post_title'   => 'My Blog Post',
			'post_content' => '<p>First paragraph of content.</p>',
			'post_excerpt' => 'The excerpt',
			'post_author'  => 1,
		] );

		Functions\expect( 'is_singular' )->with( 'post' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/my-blog-post/' );
		Functions\expect( 'get_the_title' )->andReturn( 'My Blog Post' );
		Functions\expect( 'get_the_date' )->with( 'c', $post )->andReturn( '2025-01-01T12:00:00+00:00' );
		Functions\expect( 'get_the_modified_date' )->with( 'c', $post )->andReturn( '2025-06-15T10:00:00+00:00' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );

		// Author stubs.
		$user = $this->make_user();
		Functions\expect( 'get_userdata' )->with( 1 )->andReturn( $user );
		Functions\expect( 'get_author_posts_url' )->with( 1 )->andReturn( 'https://example.com/author/johndoe/' );
		Functions\expect( 'get_the_author_meta' )->andReturn( '' );
		Functions\expect( 'get_avatar_url' )->andReturn( 'https://example.com/avatar.jpg' );

		// Categories and tags.
		$cat = $this->make_term( [ 'term_id' => 3, 'name' => 'Tech' ] );
		Functions\expect( 'get_the_category' )->with( 42 )->andReturn( [ $cat ] );
		Functions\expect( 'get_the_tags' )->with( 42 )->andReturn( false );

		$schema = new BlogPostingSchema();
		$data   = $schema->generate();

		$this->assertSame( 'BlogPosting', $data[ '@type' ] );
		$this->assertSame( 'My Blog Post', $data[ 'headline' ] );
		$this->assertSame( 'The excerpt', $data[ 'description' ] );
		$this->assertSame( '2025-01-01T12:00:00+00:00', $data[ 'datePublished' ] );
		$this->assertSame( '2025-06-15T10:00:00+00:00', $data[ 'dateModified' ] );
		$this->assertSame( 'Person', $data[ 'author' ][ '@type' ] );
		$this->assertSame( 'John Doe', $data[ 'author' ][ 'name' ] );
		$this->assertSame( 'Organization', $data[ 'publisher' ][ '@type' ] );
		$this->assertSame( [ 'Tech' ], $data[ 'articleSection' ] );
		$this->assertSame( 'en-US', $data[ 'inLanguage' ] );
		$this->assertArrayNotHasKey( 'image', $data );
	}

	public function test_includes_image_when_thumbnail_exists(): void {
		$post = $this->make_post();

		Functions\expect( 'is_singular' )->with( 'post' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/test-post/' );
		Functions\expect( 'get_the_title' )->andReturn( 'Test Post' );
		Functions\expect( 'get_the_date' )->andReturn( '2025-01-01T12:00:00+00:00' );
		Functions\expect( 'get_the_modified_date' )->andReturn( '2025-06-15T10:00:00+00:00' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 99 );
		Functions\expect( 'wp_get_attachment_image_url' )->with( 99, 'full' )->andReturn( 'https://example.com/image.jpg' );
		Functions\expect( 'wp_get_attachment_metadata' )->with( 99 )->andReturn( [ 'width' => 1200, 'height' => 630 ] );

		$user = $this->make_user();
		Functions\expect( 'get_userdata' )->andReturn( $user );
		Functions\expect( 'get_author_posts_url' )->andReturn( 'https://example.com/author/johndoe/' );
		Functions\expect( 'get_the_author_meta' )->andReturn( '' );
		Functions\expect( 'get_avatar_url' )->andReturn( '' );

		Functions\expect( 'get_the_category' )->andReturn( [] );
		Functions\expect( 'get_the_tags' )->andReturn( false );

		$schema = new BlogPostingSchema();
		$data   = $schema->generate();

		$this->assertSame( 'ImageObject', $data[ 'image' ][ '@type' ] );
		$this->assertSame( 'https://example.com/image.jpg', $data[ 'image' ][ 'url' ] );
		$this->assertSame( 1200, $data[ 'image' ][ 'width' ] );
		$this->assertSame( 630, $data[ 'image' ][ 'height' ] );
	}
}

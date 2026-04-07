<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests\Schema;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\Schema\ArticleSchema;
use Soderlind\JsonLd\Schema\WebPageSchema;
use Soderlind\JsonLd\Schema\AboutPageSchema;
use Soderlind\JsonLd\Schema\ContactPageSchema;
use Soderlind\JsonLd\Schema\CollectionPageSchema;
use Soderlind\JsonLd\Schema\ProfilePageSchema;
use Soderlind\JsonLd\Schema\PersonSchema;
use Soderlind\JsonLd\Tests\TestCase;

final class PageContextSchemaTest extends TestCase {

	// --- ArticleSchema ---

	public function test_article_applicable_for_custom_post_type(): void {
		$post = $this->make_post( [ 'post_type' => 'portfolio' ] );
		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'is_post_type_viewable' )->with( 'portfolio' )->andReturn( true );

		$schema = new ArticleSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_article_not_applicable_for_standard_post(): void {
		$post = $this->make_post( [ 'post_type' => 'post' ] );
		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );

		$schema = new ArticleSchema();
		$this->assertFalse( $schema->is_applicable() );
	}

	public function test_article_generates_data(): void {
		$post = $this->make_post( [ 'ID' => 7, 'post_type' => 'portfolio', 'post_title' => 'My Work' ] );
		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'is_post_type_viewable' )->andReturn( true );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/portfolio/my-work/' );
		Functions\expect( 'get_the_title' )->andReturn( 'My Work' );
		Functions\expect( 'get_the_date' )->andReturn( '2025-01-01T00:00:00+00:00' );
		Functions\expect( 'get_the_modified_date' )->andReturn( '2025-01-01T00:00:00+00:00' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );

		$user = $this->make_user();
		Functions\expect( 'get_userdata' )->andReturn( $user );
		Functions\expect( 'get_author_posts_url' )->andReturn( 'https://example.com/author/johndoe/' );
		Functions\expect( 'get_the_author_meta' )->andReturn( '' );
		Functions\expect( 'get_avatar_url' )->andReturn( '' );

		$schema = new ArticleSchema();
		$data   = $schema->generate();

		$this->assertSame( 'Article', $data[ '@type' ] );
		$this->assertSame( 'My Work', $data[ 'headline' ] );
	}

	// --- WebPageSchema ---

	public function test_webpage_applicable_for_generic_page(): void {
		$post = $this->make_post( [ 'post_type' => 'page', 'post_name' => 'services' ] );
		Functions\expect( 'is_singular' )->with( 'page' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_page_template_slug' )->andReturn( '' );

		$schema = new WebPageSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_webpage_not_applicable_for_about_page(): void {
		$post = $this->make_post( [ 'post_type' => 'page', 'post_name' => 'about-us' ] );
		Functions\expect( 'is_singular' )->with( 'page' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_page_template_slug' )->andReturn( '' );

		$schema = new WebPageSchema();
		$this->assertFalse( $schema->is_applicable() );
	}

	// --- AboutPageSchema ---

	public function test_about_page_applicable(): void {
		$post = $this->make_post( [ 'post_type' => 'page', 'post_name' => 'about' ] );
		Functions\expect( 'is_singular' )->with( 'page' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_page_template_slug' )->andReturn( '' );

		$schema = new AboutPageSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_about_page_generates_correct_type(): void {
		$post = $this->make_post( [ 'post_type' => 'page', 'post_name' => 'about' ] );
		Functions\expect( 'is_singular' )->with( 'page' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_page_template_slug' )->andReturn( '' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/about/' );
		Functions\expect( 'get_the_title' )->andReturn( 'About Us' );
		Functions\expect( 'get_the_date' )->andReturn( '2025-01-01T00:00:00+00:00' );
		Functions\expect( 'get_the_modified_date' )->andReturn( '2025-01-01T00:00:00+00:00' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );

		$schema = new AboutPageSchema();
		$data   = $schema->generate();

		$this->assertSame( 'AboutPage', $data[ '@type' ] );
		$this->assertSame( 'About Us', $data[ 'name' ] );
	}

	// --- ContactPageSchema ---

	public function test_contact_page_applicable(): void {
		$post = $this->make_post( [ 'post_type' => 'page', 'post_name' => 'contact' ] );
		Functions\expect( 'is_singular' )->with( 'page' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_page_template_slug' )->andReturn( '' );

		$schema = new ContactPageSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_contact_page_generates_correct_type(): void {
		$post = $this->make_post( [ 'post_type' => 'page', 'post_name' => 'contact' ] );
		Functions\expect( 'is_singular' )->with( 'page' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );
		Functions\expect( 'get_page_template_slug' )->andReturn( '' );
		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/contact/' );
		Functions\expect( 'get_the_title' )->andReturn( 'Contact Us' );
		Functions\expect( 'get_the_date' )->andReturn( '2025-01-01T00:00:00+00:00' );
		Functions\expect( 'get_the_modified_date' )->andReturn( '2025-01-01T00:00:00+00:00' );
		Functions\expect( 'get_post_thumbnail_id' )->andReturn( 0 );

		$schema = new ContactPageSchema();
		$data   = $schema->generate();

		$this->assertSame( 'ContactPage', $data[ '@type' ] );
	}

	// --- CollectionPageSchema ---

	public function test_collection_applicable_on_archive(): void {
		Functions\expect( 'is_archive' )->andReturn( true );
		Functions\expect( 'is_home' )->andReturn( false );

		$schema = new CollectionPageSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_collection_applicable_on_home(): void {
		Functions\expect( 'is_archive' )->andReturn( false );
		Functions\expect( 'is_home' )->andReturn( true );

		$schema = new CollectionPageSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_collection_generates_with_item_list(): void {
		Functions\expect( 'is_archive' )->andReturn( true );
		Functions\expect( 'is_home' )->andReturn( false );
		Functions\expect( 'get_the_archive_title' )->andReturn( 'Category: Tech' );
		Functions\expect( 'get_the_archive_description' )->andReturn( 'Technology articles' );

		// Mock global $wp.
		$wp            = new \stdClass();
		$wp->request   = 'category/tech';
		$GLOBALS[ 'wp' ] = $wp;

		// Mock WP_Query with posts.
		$post1               = $this->make_post( [ 'ID' => 1, 'post_title' => 'Post 1' ] );
		$post2               = $this->make_post( [ 'ID' => 2, 'post_title' => 'Post 2' ] );
		$wp_query            = new \stdClass();
		$wp_query->posts     = [ $post1, $post2 ];
		$GLOBALS[ 'wp_query' ] = $wp_query;

		Functions\expect( 'get_permalink' )->andReturn( 'https://example.com/post-1/', 'https://example.com/post-2/' );
		Functions\expect( 'get_the_title' )->andReturn( 'Post 1', 'Post 2' );
		Functions\expect( 'get_option' )->with( 'page_for_posts' )->andReturn( 0 );

		$schema = new CollectionPageSchema();
		$data   = $schema->generate();

		$this->assertSame( 'CollectionPage', $data[ '@type' ] );
		$this->assertSame( 'Category: Tech', $data[ 'name' ] );
		$this->assertSame( 'Technology articles', $data[ 'description' ] );
		$this->assertSame( 'ItemList', $data[ 'mainEntity' ][ '@type' ] );
		$this->assertCount( 2, $data[ 'mainEntity' ][ 'itemListElement' ] );
		$this->assertSame( 'Post 1', $data[ 'mainEntity' ][ 'itemListElement' ][ 0 ][ 'name' ] );
	}

	// --- ProfilePageSchema ---

	public function test_profile_applicable_on_author(): void {
		Functions\expect( 'is_author' )->andReturn( true );

		$schema = new ProfilePageSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_profile_generates_data(): void {
		Functions\expect( 'is_author' )->andReturn( true );
		$user = $this->make_user( [ 'ID' => 5, 'display_name' => 'Jane Doe', 'user_registered' => '2021-03-15 00:00:00' ] );
		Functions\expect( 'get_queried_object' )->andReturn( $user );
		Functions\expect( 'get_author_posts_url' )->with( 5 )->andReturn( 'https://example.com/author/janedoe/' );
		Functions\expect( 'get_the_author_meta' )->andReturn( '' );
		Functions\expect( 'get_avatar_url' )->andReturn( '' );
		Functions\expect( 'mysql2date' )->with( 'c', '2021-03-15 00:00:00' )->andReturn( '2021-03-15T00:00:00+00:00' );

		$schema = new ProfilePageSchema();
		$data   = $schema->generate();

		$this->assertSame( 'ProfilePage', $data[ '@type' ] );
		$this->assertStringContainsString( 'Jane Doe', $data[ 'name' ] );
		$this->assertSame( 'Person', $data[ 'mainEntity' ][ '@type' ] );
		$this->assertSame( 'Jane Doe', $data[ 'mainEntity' ][ 'name' ] );
		$this->assertSame( '2021-03-15T00:00:00+00:00', $data[ 'dateCreated' ] );
	}

	// --- PersonSchema ---

	public function test_person_applicable_on_author(): void {
		Functions\expect( 'is_author' )->andReturn( true );

		$schema = new PersonSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_person_generates_data(): void {
		Functions\expect( 'is_author' )->andReturn( true );
		$user = $this->make_user( [ 'ID' => 3, 'display_name' => 'Bob Smith', 'user_url' => 'https://bob.example.com' ] );
		Functions\expect( 'get_queried_object' )->andReturn( $user );
		Functions\expect( 'get_author_posts_url' )->with( 3 )->andReturn( 'https://example.com/author/bob/' );
		Functions\expect( 'get_the_author_meta' )->andReturn( '' );
		Functions\expect( 'get_avatar_url' )->andReturn( 'https://example.com/bob-avatar.jpg' );

		$schema = new PersonSchema();
		$data   = $schema->generate();

		$this->assertSame( 'Person', $data[ '@type' ] );
		$this->assertSame( 'Bob Smith', $data[ 'name' ] );
		$this->assertSame( 'https://example.com/bob-avatar.jpg', $data[ 'image' ] );
		$this->assertContains( 'https://bob.example.com', $data[ 'sameAs' ] );
	}
}

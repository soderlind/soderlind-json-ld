<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests;

use Brain\Monkey\Functions;
use Mockery;
use Soderlind\JsonLd\Cache;

final class CacheTest extends TestCase {

	public function test_get_key_for_singular_post(): void {
		$post = $this->make_post( [
			'ID'            => 42,
			'post_content'  => 'content here',
			'post_modified' => '2025-06-15 10:00:00',
		] );

		Functions\expect( 'is_singular' )->andReturn( true );
		Functions\expect( 'get_post' )->andReturn( $post );

		$cache = new Cache();
		$key   = $cache->get_key();

		$hash = md5( 'content here' . '2025-06-15 10:00:00' );
		$this->assertSame( "jsonld_1_42_{$hash}", $key );
	}

	public function test_get_key_for_author_archive(): void {
		$user = $this->make_user( [ 'ID' => 7 ] );

		Functions\expect( 'is_singular' )->andReturn( false );
		Functions\expect( 'is_author' )->andReturn( true );
		Functions\expect( 'get_queried_object' )->andReturn( $user );

		$cache = new Cache();
		$key   = $cache->get_key();

		$this->assertSame( 'jsonld_1_author_7', $key );
	}

	public function test_get_key_for_archive(): void {
		Functions\expect( 'is_singular' )->andReturn( false );
		Functions\expect( 'is_author' )->andReturn( false );
		Functions\expect( 'is_archive' )->andReturn( true );
		Functions\expect( 'is_home' )->andReturn( false );
		Functions\expect( 'get_query_var' )->with( 'paged', 0 )->andReturn( 2 );

		$wp            = new \stdClass();
		$wp->request   = 'category/tech';
		$GLOBALS[ 'wp' ] = $wp;

		$cache = new Cache();
		$key   = $cache->get_key();

		$hash = md5( 'category/tech' . '2' );
		$this->assertSame( "jsonld_1_archive_{$hash}", $key );
	}

	public function test_get_returns_string_from_transient(): void {
		Functions\expect( 'get_transient' )->with( 'test_key' )->andReturn( '<script>cached</script>' );

		$cache = new Cache();
		$this->assertSame( '<script>cached</script>', $cache->get( 'test_key' ) );
	}

	public function test_get_returns_null_for_miss(): void {
		Functions\expect( 'get_transient' )->with( 'missing_key' )->andReturn( false );

		$cache = new Cache();
		$this->assertNull( $cache->get( 'missing_key' ) );
	}

	public function test_set_stores_transient_with_ttl(): void {
		// Brain Monkey's apply_filters returns the first value arg (604800).
		$stored = [];
		Functions\when( 'set_transient' )->alias( function ( string $key, string $value, int $ttl ) use ( &$stored ) {
			$stored = [ 'key' => $key, 'value' => $value, 'ttl' => $ttl ];
			return true;
		} );

		$cache = new Cache();
		$cache->set( 'test_key', '<script>output</script>' );

		$this->assertSame( 'test_key', $stored[ 'key' ] );
		$this->assertSame( '<script>output</script>', $stored[ 'value' ] );
		$this->assertSame( 604800, $stored[ 'ttl' ] ); // 7 * DAY_IN_SECONDS
	}

	public function test_invalidate_post_skips_revisions(): void {
		Functions\expect( 'wp_is_post_revision' )->with( 42 )->andReturn( true );
		Functions\expect( 'wp_is_post_autosave' )->with( 42 )->andReturn( false );

		// Should not reach delete_transients_by_prefix, so no DB calls expected.
		Cache::invalidate_post( 42 );

		// If we get here without errors, the test passes.
		$this->assertTrue( true );
	}

	public function test_invalidate_site_deletes_by_prefix(): void {
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->options = 'wp_options';
		$wpdb->shouldReceive( 'esc_like' )->andReturnUsing( fn( $v ) => $v );
		$wpdb->shouldReceive( 'prepare' )->andReturn( "SELECT option_name FROM wp_options WHERE option_name LIKE '_transient_jsonld_1_%'" );
		$wpdb->shouldReceive( 'get_col' )->andReturn( [
			'_transient_jsonld_1_42_abc',
			'_transient_jsonld_1_site_xyz',
		] );
		Functions\expect( 'delete_transient' )->with( 'jsonld_1_42_abc' )->once();
		Functions\expect( 'delete_transient' )->with( 'jsonld_1_site_xyz' )->once();

		Cache::invalidate_site();

		$this->assertTrue( true );
	}
}

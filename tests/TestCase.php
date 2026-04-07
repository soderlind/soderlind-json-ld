<?php
/**
 * Base test case using Brain Monkey.
 */

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Stub common WordPress functions used throughout the plugin.
		$this->stub_common_wp_functions();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Provide default stubs for WP functions used broadly.
	 * Individual tests can override any of these.
	 */
	protected function stub_common_wp_functions(): void {
		// Passthrough / identity stubs.
		Functions\stubs( [
			'__'                  => static fn( string $text ): string => $text,
			'esc_html__'          => static fn( string $text ): string => $text,
			'esc_html_e'          => static fn( string $text ): string => $text,
			'esc_attr'            => static fn( string $text ): string => $text,
			'esc_url'             => static fn( string $url ): string => $url,
			'esc_url_raw'         => static fn( string $url ): string => $url,
			'esc_html'            => static fn( string $text ): string => $text,
			'esc_js'              => static fn( string $text ): string => $text,
			'sanitize_text_field' => static fn( string $text ): string => trim( strip_tags( $text ) ),
			'wp_unslash'          => static fn( mixed $val ): mixed => $val,
			'wp_strip_all_tags'   => static fn( string $text ): string => strip_tags( $text ),
			'wp_trim_words'       => static function ( string $text, int $num_words = 55, string $more = '…' ): string {
				$words = explode( ' ', $text );
				if ( count( $words ) <= $num_words ) {
					return $text;
				}
				return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
			},
			'wp_parse_args'       => static fn( array|string $args, array $defaults = [] ): array => array_merge( $defaults, (array) $args ),
			'trailingslashit'     => static fn( string $val ): string => rtrim( $val, '/' ) . '/',
			'home_url'            => static fn( string $path = '' ): string => 'https://example.com' . ( $path ? '/' . ltrim( $path, '/' ) : '' ),
			'get_bloginfo'        => static function ( string $show = '' ): string {
				return match ( $show ) {
					'name'        => 'Test Site',
					'description' => 'Just a test site',
					'language'    => 'en-US',
					default       => '',
				};
			},
			'wp_json_encode'      => static fn( mixed $data, int $opts = 0 ): string|false => json_encode( $data, $opts ),
		] );

		// Use when() for functions that tests commonly override.
		// Brain Monkey expect() takes priority over when() aliases.
		Functions\when( 'get_option' )->alias( static fn( string $key, mixed $default = false ): mixed => $default );
		Functions\when( 'get_site_option' )->alias( static fn( string $key, mixed $default = false ): mixed => $default );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'get_current_blog_id' )->justReturn( 1 );
	}

	/**
	 * Create a mock WP_Post.
	 *
	 * @param array<string, mixed> $props
	 */
	protected function make_post( array $props = [] ): \WP_Post {
		$defaults = [
			'ID'            => 1,
			'post_title'    => 'Test Post',
			'post_content'  => '<p>Test content.</p>',
			'post_excerpt'  => 'Test excerpt',
			'post_name'     => 'test-post',
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_author'   => 1,
			'post_date'     => '2025-01-01 12:00:00',
			'post_modified' => '2025-06-15 10:00:00',
			'post_parent'   => 0,
		];

		$data = array_merge( $defaults, $props );
		$post = Mockery::mock( 'WP_Post' );

		foreach ( $data as $key => $value ) {
			$post->{$key} = $value;
		}

		// Make instanceof WP_Post work.
		$post->shouldReceive( '__isset' )->andReturnUsing( fn( $k ) => isset( $data[ $k ] ) );

		return $post;
	}

	/**
	 * Create a mock WP_User.
	 *
	 * @param array<string, mixed> $props
	 */
	protected function make_user( array $props = [] ): \WP_User {
		$defaults = [
			'ID'              => 1,
			'display_name'    => 'John Doe',
			'user_url'        => 'https://johndoe.com',
			'user_registered' => '2020-01-01 00:00:00',
		];

		$data = array_merge( $defaults, $props );
		$user = Mockery::mock( 'WP_User' );

		foreach ( $data as $key => $value ) {
			$user->{$key} = $value;
		}

		return $user;
	}

	/**
	 * Create a mock WP_Term.
	 *
	 * @param array<string, mixed> $props
	 */
	protected function make_term( array $props = [] ): \WP_Term {
		$defaults = [
			'term_id'  => 1,
			'name'     => 'Test Category',
			'slug'     => 'test-category',
			'taxonomy' => 'category',
			'parent'   => 0,
		];

		$data = array_merge( $defaults, $props );
		$term = Mockery::mock( 'WP_Term' );

		foreach ( $data as $key => $value ) {
			$term->{$key} = $value;
		}

		return $term;
	}
}

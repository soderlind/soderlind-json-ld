<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\Cache;
use Soderlind\JsonLd\ContentAnalyzer;
use Soderlind\JsonLd\SchemaManager;

final class SchemaManagerTest extends TestCase {

	public function test_output_skipped_in_admin(): void {
		Functions\expect( 'is_admin' )->andReturn( true );
		Functions\expect( 'is_feed' )->andReturn( false );

		$manager = new SchemaManager( new ContentAnalyzer(), new Cache() );

		ob_start();
		$manager->output();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	public function test_output_skipped_in_feed(): void {
		Functions\expect( 'is_admin' )->andReturn( false );
		Functions\expect( 'is_feed' )->andReturn( true );

		$manager = new SchemaManager( new ContentAnalyzer(), new Cache() );

		ob_start();
		$manager->output();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	public function test_output_returns_cached_content(): void {
		Functions\expect( 'is_admin' )->andReturn( false );
		Functions\expect( 'is_feed' )->andReturn( false );
		Functions\expect( 'is_singular' )->andReturn( false );
		Functions\expect( 'is_author' )->andReturn( false );
		Functions\expect( 'is_archive' )->andReturn( false );
		Functions\expect( 'is_home' )->andReturn( false );

		$cached = '<script type="application/ld+json">{"cached": true}</script>';
		Functions\when( 'get_transient' )->justReturn( $cached );

		$manager = new SchemaManager( new ContentAnalyzer(), new Cache() );

		ob_start();
		$manager->output();
		$output = ob_get_clean();

		$this->assertSame( $cached, $output );
	}

	public function test_output_generates_graph_structure(): void {
		Functions\expect( 'is_admin' )->andReturn( false );
		Functions\expect( 'is_feed' )->andReturn( false );
		Functions\expect( 'is_singular' )->andReturn( false );
		Functions\expect( 'is_front_page' )->andReturn( true );
		Functions\expect( 'is_archive' )->andReturn( false );
		Functions\expect( 'is_home' )->andReturn( false );
		Functions\expect( 'is_author' )->andReturn( false );
		Functions\expect( 'has_custom_logo' )->andReturn( false );

		// Cache miss.
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$manager = new SchemaManager( new ContentAnalyzer(), new Cache() );

		ob_start();
		$manager->output();
		$output = ob_get_clean();

		$this->assertStringContainsString( '<script type="application/ld+json">', $output );
		$this->assertStringContainsString( '"@context": "https://schema.org"', $output );
		$this->assertStringContainsString( '"@graph"', $output );
		$this->assertStringContainsString( '"Organization"', $output );
		$this->assertStringContainsString( '"WebSite"', $output );
	}
}

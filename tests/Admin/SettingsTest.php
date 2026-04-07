<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests\Admin;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\Admin\Settings;
use Soderlind\JsonLd\Tests\TestCase;

final class SettingsTest extends TestCase {

	public function test_get_defaults(): void {
		$defaults = Settings::get_defaults();

		$this->assertArrayHasKey( 'org_name', $defaults );
		$this->assertArrayHasKey( 'org_logo', $defaults );
		$this->assertArrayHasKey( 'org_founding_date', $defaults );
		$this->assertArrayHasKey( 'org_social_urls', $defaults );
		$this->assertSame( '', $defaults[ 'org_name' ] );
		$this->assertSame( [], $defaults[ 'org_social_urls' ] );
	}

	public function test_get_merged_single_site(): void {
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'get_option' )->alias( static fn( string $key, mixed $default = false ): mixed => match ( $key ) {
			'soderlind_jsonld_settings' => [
				'org_name' => 'My Site',
				'org_logo' => 'https://example.com/logo.png',
			],
			default                     => $default,
		} );

		$merged = Settings::get_merged();

		$this->assertSame( 'My Site', $merged[ 'org_name' ] );
		$this->assertSame( 'https://example.com/logo.png', $merged[ 'org_logo' ] );
		$this->assertSame( '', $merged[ 'org_founding_date' ] );
	}

	public function test_get_merged_multisite_network_defaults(): void {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_site_option' )->alias( static fn( string $key, mixed $default = false ): mixed => match ( $key ) {
			'soderlind_jsonld_network_settings' => [
				'org_name' => 'Network Org',
				'org_logo' => 'https://network.example.com/logo.png',
			],
			default                             => $default,
		} );
		Functions\when( 'get_option' )->alias( static fn( string $key, mixed $default = false ): mixed => $default );

		$merged = Settings::get_merged();

		$this->assertSame( 'Network Org', $merged[ 'org_name' ] );
		$this->assertSame( 'https://network.example.com/logo.png', $merged[ 'org_logo' ] );
	}

	public function test_get_merged_site_overrides_network(): void {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_site_option' )->alias( static fn( string $key, mixed $default = false ): mixed => match ( $key ) {
			'soderlind_jsonld_network_settings' => [
				'org_name'          => 'Network Org',
				'org_logo'          => 'https://network.example.com/logo.png',
				'org_founding_date' => '2020',
			],
			default                             => $default,
		} );
		Functions\when( 'get_option' )->alias( static fn( string $key, mixed $default = false ): mixed => match ( $key ) {
			'soderlind_jsonld_settings' => [
				'org_name' => 'Site Override',
			],
			default                     => $default,
		} );

		$merged = Settings::get_merged();

		$this->assertSame( 'Site Override', $merged[ 'org_name' ] );
		$this->assertSame( 'https://network.example.com/logo.png', $merged[ 'org_logo' ] );
		$this->assertSame( '2020', $merged[ 'org_founding_date' ] );
	}

	public function test_sanitize_valid_input(): void {
		$input = [
			'org_name'          => '  Acme Corp  ',
			'org_logo'          => 'https://example.com/logo.png',
			'org_founding_date' => '2015',
			'org_social_urls'   => [
				'https://linkedin.com/company/acme',
				'  https://twitter.com/acme  ',
				'',
			],
		];

		$clean = Settings::sanitize( $input );

		$this->assertSame( 'Acme Corp', $clean[ 'org_name' ] );
		$this->assertSame( 'https://example.com/logo.png', $clean[ 'org_logo' ] );
		$this->assertSame( '2015', $clean[ 'org_founding_date' ] );
		$this->assertCount( 2, $clean[ 'org_social_urls' ] );
	}

	public function test_sanitize_strips_html(): void {
		$input = [
			'org_name' => '<script>alert("xss")</script>My Org',
		];

		$clean = Settings::sanitize( $input );

		// sanitize_text_field (stub) strips tags.
		$this->assertStringNotContainsString( '<script>', $clean[ 'org_name' ] );
		$this->assertStringContainsString( 'My Org', $clean[ 'org_name' ] );
	}

	public function test_sanitize_non_array_returns_defaults(): void {
		$clean = Settings::sanitize( 'not an array' );
		$this->assertSame( Settings::get_defaults(), $clean );
	}

	public function test_sanitize_missing_keys_default_empty(): void {
		$clean = Settings::sanitize( [] );

		$this->assertSame( '', $clean[ 'org_name' ] );
		$this->assertSame( '', $clean[ 'org_logo' ] );
		$this->assertSame( '', $clean[ 'org_founding_date' ] );
		$this->assertSame( [], $clean[ 'org_social_urls' ] );
	}

	public function test_get_site_returns_site_only(): void {
		Functions\when( 'get_option' )->alias( static fn( string $key, mixed $default = false ): mixed => match ( $key ) {
			'soderlind_jsonld_settings' => [
				'org_name' => 'Site Only',
			],
			default                     => $default,
		} );

		$site = Settings::get_site();

		$this->assertSame( 'Site Only', $site[ 'org_name' ] );
		$this->assertSame( '', $site[ 'org_logo' ] );
	}

	public function test_get_network_single_site_returns_defaults(): void {
		Functions\when( 'is_multisite' )->justReturn( false );

		$network = Settings::get_network();

		$this->assertSame( Settings::get_defaults(), $network );
	}

	public function test_get_network_multisite_returns_network(): void {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'get_site_option' )->alias( static fn( string $key, mixed $default = false ): mixed => match ( $key ) {
			'soderlind_jsonld_network_settings' => [
				'org_name' => 'Net Name',
			],
			default                             => $default,
		} );

		$network = Settings::get_network();

		$this->assertSame( 'Net Name', $network[ 'org_name' ] );
		$this->assertSame( '', $network[ 'org_logo' ] );
	}
}

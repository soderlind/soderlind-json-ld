<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests\Schema;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\Schema\WebSiteSchema;
use Soderlind\JsonLd\Tests\TestCase;

final class WebSiteSchemaTest extends TestCase {

	public function test_is_always_applicable(): void {
		$schema = new WebSiteSchema();
		$this->assertTrue( $schema->is_applicable() );
	}

	public function test_generates_website_schema(): void {
		$schema = new WebSiteSchema();
		$data   = $schema->generate();

		$this->assertSame( 'WebSite', $data[ '@type' ] );
		$this->assertSame( 'https://example.com/#website', $data[ '@id' ] );
		$this->assertSame( 'Test Site', $data[ 'name' ] );
		$this->assertSame( 'Just a test site', $data[ 'description' ] );
		$this->assertSame( 'https://example.com/', $data[ 'url' ] );
		$this->assertSame( 'en-US', $data[ 'inLanguage' ] );

		// Publisher reference.
		$this->assertSame( 'Organization', $data[ 'publisher' ][ '@type' ] );
		$this->assertSame( 'https://example.com/#organization', $data[ 'publisher' ][ '@id' ] );

		// SearchAction.
		$this->assertSame( 'SearchAction', $data[ 'potentialAction' ][ '@type' ] );
		$this->assertStringContainsString( '{search_term_string}', $data[ 'potentialAction' ][ 'target' ][ 'urlTemplate' ] );
	}
}

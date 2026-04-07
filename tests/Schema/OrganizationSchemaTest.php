<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Tests\Schema;

use Brain\Monkey\Functions;
use Soderlind\JsonLd\Schema\OrganizationSchema;
use Soderlind\JsonLd\Tests\TestCase;

final class OrganizationSchemaTest extends TestCase {

    public function test_is_always_applicable(): void {
        $schema = new OrganizationSchema();
        $this->assertTrue($schema->is_applicable());
    }

    public function test_generates_basic_organization_from_site_name(): void {
        // Settings return empty → falls back to bloginfo('name').
        Functions\expect('has_custom_logo')->once()->andReturn(false);

        $schema = new OrganizationSchema();
        $data = $schema->generate();

        $this->assertSame('Organization', $data['@type']);
        $this->assertSame('https://example.com/#organization', $data['@id']);
        $this->assertSame('Test Site', $data['name']);
        $this->assertSame('https://example.com/', $data['url']);
        $this->assertArrayNotHasKey('logo', $data);
        $this->assertArrayNotHasKey('foundingDate', $data);
        $this->assertArrayNotHasKey('sameAs', $data);
    }

    public function test_generates_organization_from_settings(): void {
        Functions\when('get_option')->alias(static fn(string $key, mixed $default = false): mixed => match ($key) {
            'soderlind_jsonld_settings' => [
                'org_name'          => 'Acme Corp',
                'org_logo'          => 'https://example.com/logo.png',
                'org_founding_date' => '2015',
                'org_social_urls'   => [
                    'https://linkedin.com/company/acme',
                    'https://twitter.com/acme',
                ],
            ],
            default => $default,
        });
        Functions\expect('has_custom_logo')->never();

        $schema = new OrganizationSchema();
        $data = $schema->generate();

        $this->assertSame('Acme Corp', $data['name']);
        $this->assertSame('2015', $data['foundingDate']);
        $this->assertSame('ImageObject', $data['logo']['@type']);
        $this->assertSame('https://example.com/logo.png', $data['logo']['url']);
        $this->assertCount(2, $data['sameAs']);
        $this->assertContains('https://linkedin.com/company/acme', $data['sameAs']);
    }

    public function test_uses_custom_logo_when_no_setting(): void {
        Functions\expect('has_custom_logo')->once()->andReturn(true);
        Functions\expect('get_theme_mod')->with('custom_logo')->andReturn(42);
        Functions\expect('wp_get_attachment_image_url')->with(42, 'full')->andReturn('https://example.com/theme-logo.png');

        $schema = new OrganizationSchema();
        $data = $schema->generate();

        $this->assertSame('https://example.com/theme-logo.png', $data['logo']['url']);
    }

    public function test_filters_invalid_social_urls(): void {
        Functions\when('get_option')->alias(static fn(string $key, mixed $default = false): mixed => match ($key) {
            'soderlind_jsonld_settings' => [
                'org_name'        => '',
                'org_logo'        => '',
                'org_founding_date' => '',
                'org_social_urls' => [
                    'https://valid.com',
                    'not-a-url',
                    '',
                    'https://also-valid.com',
                ],
            ],
            default => $default,
        });
        Functions\expect('has_custom_logo')->andReturn(false);

        $schema = new OrganizationSchema();
        $data = $schema->generate();

        $this->assertCount(2, $data['sameAs']);
        $this->assertSame('https://valid.com', $data['sameAs'][0]);
        $this->assertSame('https://also-valid.com', $data['sameAs'][1]);
    }
}

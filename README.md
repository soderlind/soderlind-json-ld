# Soderlind JSON-LD

AI-optimized JSON-LD structured data for WordPress. Auto-detects content patterns and outputs schema.org markup via `@graph` for maximum AI search visibility.

## Features

- **Single `@graph` output** — all schemas bundled in one `<script type="application/ld+json">` block with `@id` cross-referencing
- **15 schema types** across three tiers:
  - **Site-wide:** Organization, WebSite, BreadcrumbList
  - **Page-context:** BlogPosting, Article, WebPage, AboutPage, ContactPage, CollectionPage, ProfilePage, Person
  - **Content-detected:** FAQPage, HowTo, SoftwareApplication, VideoObject
- **Automatic content detection** — FAQ pairs, HowTo steps, software keywords and video embeds are identified from post content
- **Transient caching** with content-hash invalidation
- **Multisite support** — network-wide defaults with per-site overrides
- **Developer filters** — `soderlind_jsonld_schemas`, `soderlind_jsonld_schema_{type}`, `soderlind_jsonld_cache_ttl`

## Requirements

- WordPress 6.8+
- PHP 8.3+

## Installation

1. Upload the `soderlind-json-ld` folder to `/wp-content/plugins/`
2. Activate the plugin (per-site or network-wide on multisite)
3. Go to **Settings → JSON-LD** to configure organization details

## Settings

| Field | Description |
|---|---|
| Organization Name | Falls back to site name if empty |
| Organization Logo | URL or media library picker; falls back to custom logo |
| Founding Date | Year the organization was founded |
| Social URLs | One per line (LinkedIn, X/Twitter, GitHub, etc.) |

On multisite, network defaults can be set under **Network Admin → Settings → JSON-LD**. Per-site settings override network defaults.

## Filters

```php
// Modify the complete schemas array before output.
add_filter('soderlind_jsonld_schemas', function (array $schemas): array {
    return $schemas;
});

// Modify a specific schema type.
add_filter('soderlind_jsonld_schema_BlogPosting', function (array $data): array {
    $data['copyrightYear'] = '2025';
    return $data;
});

// Change cache TTL (default: 7 days).
add_filter('soderlind_jsonld_cache_ttl', function (): int {
    return DAY_IN_SECONDS;
});
```

## Development

```bash
composer install
composer dump-autoload

# Run tests
vendor/bin/phpunit
```

## License

GPL-2.0-or-later

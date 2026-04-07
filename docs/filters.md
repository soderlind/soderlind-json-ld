# Filters Reference

Soderlind JSON-LD provides three filters for customizing the structured data output. All filters run **before** the result is cached, so changes take effect on the next uncached page load.

---

## Table of Contents

- [`soderlind_jsonld_schemas`](#soderlind_jsonld_schemas) — Modify the complete schema collection
- [`soderlind_jsonld_schema_{type}`](#soderlind_jsonld_schema_type) — Modify a single schema by type
- [`soderlind_jsonld_cache_ttl`](#soderlind_jsonld_cache_ttl) — Change the cache duration

---

## `soderlind_jsonld_schemas`

Filters the full array of schema data arrays before they are wrapped in the `@graph` and output as JSON-LD.

### Signature

```php
apply_filters('soderlind_jsonld_schemas', array $schemas): array
```

### Parameters

| Parameter | Type | Description |
|---|---|---|
| `$schemas` | `array` | Indexed array of schema data arrays. Each element is an associative array with at least an `@type` key. |

### Return

An indexed array of schema data arrays. Return an empty array to suppress all JSON-LD output for the current page.

### Examples

#### Remove a specific schema type

```php
add_filter('soderlind_jsonld_schemas', function (array $schemas): array {
    return array_values(array_filter(
        $schemas,
        fn(array $schema) => ($schema['@type'] ?? '') !== 'BreadcrumbList',
    ));
});
```

#### Remove multiple schema types

```php
add_filter('soderlind_jsonld_schemas', function (array $schemas): array {
    $exclude = ['HowTo', 'SoftwareApplication'];

    return array_values(array_filter(
        $schemas,
        fn(array $schema) => !in_array($schema['@type'] ?? '', $exclude, true),
    ));
});
```

#### Only output schemas on singular pages

```php
add_filter('soderlind_jsonld_schemas', function (array $schemas): array {
    if (!is_singular()) {
        return [];
    }
    return $schemas;
});
```

#### Add a custom schema

```php
add_filter('soderlind_jsonld_schemas', function (array $schemas): array {
    if (is_front_page()) {
        $schemas[] = [
            '@type'       => 'LocalBusiness',
            '@id'         => home_url('/#localbusiness'),
            'name'        => 'My Business',
            'address'     => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => '123 Main St',
                'addressLocality' => 'Oslo',
                'addressCountry'  => 'NO',
            ],
            'telephone'   => '+47 123 45 678',
            'openingHours' => 'Mo-Fr 09:00-17:00',
        ];
    }
    return $schemas;
});
```

#### Reorder schemas (Organization first)

```php
add_filter('soderlind_jsonld_schemas', function (array $schemas): array {
    usort($schemas, function (array $a, array $b): int {
        $priority = ['Organization' => 0, 'WebSite' => 1, 'BreadcrumbList' => 2];
        $pa = $priority[$a['@type'] ?? ''] ?? 99;
        $pb = $priority[$b['@type'] ?? ''] ?? 99;
        return $pa <=> $pb;
    });
    return $schemas;
});
```

---

## `soderlind_jsonld_schema_{type}`

Filters an individual schema's data array before it is added to the `@graph`. The `{type}` placeholder is the schema's `@type` value.

### Available filter names

| Filter name | When it fires |
|---|---|
| `soderlind_jsonld_schema_Organization` | Every page |
| `soderlind_jsonld_schema_WebSite` | Every page |
| `soderlind_jsonld_schema_BreadcrumbList` | Every page except front page |
| `soderlind_jsonld_schema_BlogPosting` | Single posts |
| `soderlind_jsonld_schema_Article` | Singular custom post types |
| `soderlind_jsonld_schema_WebPage` | Pages (not about/contact) |
| `soderlind_jsonld_schema_AboutPage` | Pages with "about" in slug/template |
| `soderlind_jsonld_schema_ContactPage` | Pages with "contact" in slug/template |
| `soderlind_jsonld_schema_CollectionPage` | Archives and blog home |
| `soderlind_jsonld_schema_ProfilePage` | Author archives |
| `soderlind_jsonld_schema_Person` | Author archives |
| `soderlind_jsonld_schema_FAQPage` | Singular posts with FAQ content detected |
| `soderlind_jsonld_schema_HowTo` | Singular posts with HowTo content detected |
| `soderlind_jsonld_schema_SoftwareApplication` | Singular posts with software keywords detected |
| `soderlind_jsonld_schema_VideoObject` | Singular posts with video embeds detected |

### Signature

```php
apply_filters("soderlind_jsonld_schema_{$type}", array $data): array
```

### Parameters

| Parameter | Type | Description |
|---|---|---|
| `$data` | `array` | The schema data array. Keys vary by type (see examples below). Always includes `@type` and `@id`. |

### Return

The modified schema data array. Return an empty array to exclude this schema from the output.

### Examples

#### Add copyright year to BlogPosting

```php
add_filter('soderlind_jsonld_schema_BlogPosting', function (array $data): array {
    $data['copyrightYear'] = get_the_date('Y');
    return $data;
});
```

#### Add social profiles to Organization

```php
add_filter('soderlind_jsonld_schema_Organization', function (array $data): array {
    $data['sameAs'] = array_merge($data['sameAs'] ?? [], [
        'https://linkedin.com/company/my-company',
        'https://github.com/my-company',
    ]);
    $data['sameAs'] = array_values(array_unique($data['sameAs']));
    return $data;
});
```

#### Override the Organization logo

```php
add_filter('soderlind_jsonld_schema_Organization', function (array $data): array {
    $data['logo'] = [
        '@type'      => 'ImageObject',
        'url'        => 'https://example.com/custom-logo.svg',
        'contentUrl' => 'https://example.com/custom-logo.svg',
        'width'      => 600,
        'height'     => 60,
    ];
    return $data;
});
```

#### Add estimatedCost and totalTime to HowTo

```php
add_filter('soderlind_jsonld_schema_HowTo', function (array $data): array {
    $post = get_post();
    if (!$post) {
        return $data;
    }

    // Add from custom fields.
    $cost = get_post_meta($post->ID, '_howto_cost', true);
    if ($cost) {
        $data['estimatedCost'] = [
            '@type'    => 'MonetaryAmount',
            'currency' => 'USD',
            'value'    => $cost,
        ];
    }

    $duration = get_post_meta($post->ID, '_howto_duration', true);
    if ($duration) {
        $data['totalTime'] = $duration; // ISO 8601 duration, e.g. "PT30M"
    }

    return $data;
});
```

#### Add offer pricing to SoftwareApplication

```php
add_filter('soderlind_jsonld_schema_SoftwareApplication', function (array $data): array {
    $post = get_post();
    if (!$post) {
        return $data;
    }

    $price = get_post_meta($post->ID, '_software_price', true);
    $data['offers'] = [
        '@type'         => 'Offer',
        'price'         => $price ?: '0',
        'priceCurrency' => 'USD',
    ];

    $os = get_post_meta($post->ID, '_software_os', true);
    if ($os) {
        $data['operatingSystem'] = $os;
    }

    return $data;
});
```

#### Conditionally remove a schema

Return an empty array to exclude the schema from output:

```php
add_filter('soderlind_jsonld_schema_FAQPage', function (array $data): array {
    // Skip FAQ schema on a specific post.
    $post = get_post();
    if ($post && $post->ID === 42) {
        return [];
    }
    return $data;
});
```

#### Add video duration and thumbnail from post meta

```php
add_filter('soderlind_jsonld_schema_VideoObject', function (array $data): array {
    $post = get_post();
    if (!$post) {
        return $data;
    }

    $duration = get_post_meta($post->ID, '_video_duration', true);
    if ($duration) {
        $data['duration'] = $duration; // ISO 8601, e.g. "PT5M30S"
    }

    return $data;
});
```

#### Customize BreadcrumbList for WooCommerce

```php
add_filter('soderlind_jsonld_schema_BreadcrumbList', function (array $data): array {
    if (!function_exists('is_product') || !is_product()) {
        return $data;
    }

    // Replace breadcrumbs with WooCommerce-aware trail.
    $data['itemListElement'] = [
        [
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => get_bloginfo('name'),
            'item'     => home_url('/'),
        ],
        [
            '@type'    => 'ListItem',
            'position' => 2,
            'name'     => 'Shop',
            'item'     => wc_get_page_permalink('shop'),
        ],
        [
            '@type'    => 'ListItem',
            'position' => 3,
            'name'     => get_the_title(),
            'item'     => get_permalink(),
        ],
    ];
    return $data;
});
```

### BlogPosting data structure reference

For reference, here are the keys available in the `BlogPosting` schema array:

```php
[
    '@type'            => 'BlogPosting',
    '@id'              => 'https://example.com/my-post/#blogposting',
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => 'https://example.com/my-post/'],
    'headline'         => 'Post Title',
    'description'      => 'Excerpt or auto-generated summary',
    'datePublished'    => '2025-01-01T12:00:00+00:00',
    'dateModified'     => '2025-06-15T10:00:00+00:00',
    'author'           => ['@type' => 'Person', 'name' => '...', ...],
    'publisher'        => ['@type' => 'Organization', '@id' => '.../#organization'],
    'isPartOf'         => ['@id' => '.../#website'],
    'inLanguage'       => 'en-US',
    'image'            => ['@type' => 'ImageObject', 'url' => '...', ...], // if featured image
    'articleSection'   => ['Category1', 'Category2'],                       // if categories
    'keywords'         => ['Tag1', 'Tag2'],                                 // if tags
    'wordCount'        => 350,
]
```

---

## `soderlind_jsonld_cache_ttl`

Filters the cache TTL (time to live) in seconds. The cache uses WordPress transients.

### Signature

```php
apply_filters('soderlind_jsonld_cache_ttl', int $ttl): int
```

### Parameters

| Parameter | Type | Description |
|---|---|---|
| `$ttl` | `int` | Cache duration in seconds. Default: `604800` (7 days). |

### Return

An integer representing the cache duration in seconds.

### Examples

#### Set cache to 1 hour

```php
add_filter('soderlind_jsonld_cache_ttl', fn(): int => HOUR_IN_SECONDS);
```

#### Set cache to 1 day

```php
add_filter('soderlind_jsonld_cache_ttl', fn(): int => DAY_IN_SECONDS);
```

#### Disable caching entirely

```php
add_filter('soderlind_jsonld_cache_ttl', fn(): int => 0);
```

> **Note:** Setting TTL to 0 means transients expire immediately. This is useful during development but not recommended for production.

#### Shorter cache for logged-in users

```php
add_filter('soderlind_jsonld_cache_ttl', function (int $ttl): int {
    if (is_user_logged_in()) {
        return HOUR_IN_SECONDS;
    }
    return $ttl;
});
```

---

## Tips

### Flush cached output after adding filters

Filter changes only take effect after the cache expires or is invalidated. To see changes immediately:

```php
// Via WP-CLI:
wp transient delete --all

// Or programmatically:
\Soderlind\JsonLd\Cache::invalidate_site();
```

### Where to put filter code

Add filters in your theme's `functions.php` or in a custom plugin. They must run before `wp_head` fires (priority 1), so hooking into `init` or `after_setup_theme` works:

```php
add_action('after_setup_theme', function () {
    add_filter('soderlind_jsonld_schema_BlogPosting', function (array $data): array {
        $data['copyrightYear'] = get_the_date('Y');
        return $data;
    });
});
```

Or simply place the `add_filter()` calls at the top level of your file — they register immediately and execute when the plugin calls `apply_filters()`.

### Combining filters

Filters chain naturally. Multiple callbacks on the same filter run in priority order:

```php
// First: add copyright year.
add_filter('soderlind_jsonld_schema_BlogPosting', function (array $data): array {
    $data['copyrightYear'] = get_the_date('Y');
    return $data;
}, 10);

// Second: add sponsor.
add_filter('soderlind_jsonld_schema_BlogPosting', function (array $data): array {
    $data['sponsor'] = [
        '@type' => 'Organization',
        'name'  => 'Acme Corp',
    ];
    return $data;
}, 20);
```

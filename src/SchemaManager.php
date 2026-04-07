<?php

declare(strict_types=1);

namespace Soderlind\JsonLd;

use Soderlind\JsonLd\Schema\AboutPageSchema;
use Soderlind\JsonLd\Schema\ArticleSchema;
use Soderlind\JsonLd\Schema\BlogPostingSchema;
use Soderlind\JsonLd\Schema\BreadcrumbListSchema;
use Soderlind\JsonLd\Schema\CollectionPageSchema;
use Soderlind\JsonLd\Schema\ContactPageSchema;
use Soderlind\JsonLd\Schema\FAQPageSchema;
use Soderlind\JsonLd\Schema\HowToSchema;
use Soderlind\JsonLd\Schema\OrganizationSchema;
use Soderlind\JsonLd\Schema\PersonSchema;
use Soderlind\JsonLd\Schema\ProfilePageSchema;
use Soderlind\JsonLd\Schema\SchemaInterface;
use Soderlind\JsonLd\Schema\SoftwareApplicationSchema;
use Soderlind\JsonLd\Schema\VideoObjectSchema;
use Soderlind\JsonLd\Schema\WebPageSchema;
use Soderlind\JsonLd\Schema\WebSiteSchema;

final class SchemaManager {

    public function __construct(
        private readonly ContentAnalyzer $analyzer,
        private readonly Cache $cache,
    ) {}

    /**
     * Output JSON-LD via wp_head.
     */
    public function output(): void {
        // Don't output in admin, feeds, or REST requests.
        if (is_admin() || is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        $cache_key = $this->cache->get_key();
        $cached = $this->cache->get($cache_key);

        if (is_string($cached)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-encoded JSON.
            echo $cached;
            return;
        }

        $schemas = $this->collect_schemas();

        /**
         * Filter the complete set of schema arrays before JSON-LD output.
         *
         * @param list<array<string, mixed>> $schemas Array of schema data arrays.
         */
        $schemas = apply_filters('soderlind_jsonld_schemas', $schemas);

        if (empty($schemas)) {
            return;
        }

        $json_ld = [
            '@context' => 'https://schema.org',
            '@graph'   => $schemas,
        ];

        $output = sprintf(
            '<script type="application/ld+json">%s</script>' . "\n",
            wp_json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        );

        $this->cache->set($cache_key, $output);

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD script tag.
        echo $output;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function collect_schemas(): array {
        $generators = $this->get_generators();
        $schemas = [];

        foreach ($generators as $generator) {
            if (! $generator->is_applicable()) {
                continue;
            }

            $data = $generator->generate();
            if (empty($data)) {
                continue;
            }

            $type = $data['@type'] ?? 'Unknown';

            /**
             * Filter individual schema data before inclusion in @graph.
             *
             * @param array<string, mixed> $data Schema data array.
             */
            $data = apply_filters("soderlind_jsonld_schema_{$type}", $data);

            if (! empty($data)) {
                $schemas[] = $data;
            }
        }

        return $schemas;
    }

    /**
     * @return list<SchemaInterface>
     */
    private function get_generators(): array {
        return [
            // Site-wide.
            new OrganizationSchema(),
            new WebSiteSchema(),
            new BreadcrumbListSchema(),

            // Page-context.
            new BlogPostingSchema(),
            new ArticleSchema(),
            new AboutPageSchema(),
            new ContactPageSchema(),
            new WebPageSchema(),
            new CollectionPageSchema(),
            new ProfilePageSchema(),
            new PersonSchema(),

            // Content-detected.
            new FAQPageSchema($this->analyzer),
            new HowToSchema($this->analyzer),
            new SoftwareApplicationSchema($this->analyzer),
            new VideoObjectSchema($this->analyzer),
        ];
    }
}

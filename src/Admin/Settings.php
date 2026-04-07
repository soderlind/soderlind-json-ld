<?php

declare(strict_types=1);

namespace Soderlind\JsonLd\Admin;

final class Settings {

    public const OPTION_KEY = 'soderlind_jsonld_settings';
    public const NETWORK_OPTION_KEY = 'soderlind_jsonld_network_settings';

    /**
     * @return array<string, mixed>
     */
    public static function get_defaults(): array {
        return [
            'org_name'         => '',
            'org_logo'         => '',
            'org_founding_date' => '',
            'org_social_urls'  => [],
        ];
    }

    /**
     * Get merged settings: network defaults + per-site overrides.
     *
     * @return array<string, mixed>
     */
    public static function get_merged(): array {
        $defaults = self::get_defaults();

        $network = is_multisite()
            ? (array) get_site_option(self::NETWORK_OPTION_KEY, [])
            : [];

        $site = (array) get_option(self::OPTION_KEY, []);

        // Network fills in defaults, site overrides non-empty values.
        $merged = wp_parse_args(array_filter($network, [self::class, 'is_non_empty']), $defaults);
        $merged = wp_parse_args(array_filter($site, [self::class, 'is_non_empty']), $merged);

        return $merged;
    }

    /**
     * Get network-only settings (for displaying defaults in per-site form).
     *
     * @return array<string, mixed>
     */
    public static function get_network(): array {
        if (! is_multisite()) {
            return self::get_defaults();
        }

        return wp_parse_args(
            (array) get_site_option(self::NETWORK_OPTION_KEY, []),
            self::get_defaults(),
        );
    }

    /**
     * Get per-site-only settings (without network merge).
     *
     * @return array<string, mixed>
     */
    public static function get_site(): array {
        return wp_parse_args(
            (array) get_option(self::OPTION_KEY, []),
            self::get_defaults(),
        );
    }

    /**
     * Sanitize settings array.
     *
     * @param mixed $input
     * @return array<string, mixed>
     */
    public static function sanitize(mixed $input): array {
        if (! is_array($input)) {
            return self::get_defaults();
        }

        $clean = [];

        $clean['org_name'] = isset($input['org_name'])
            ? sanitize_text_field($input['org_name'])
            : '';

        $clean['org_logo'] = isset($input['org_logo'])
            ? esc_url_raw($input['org_logo'])
            : '';

        $clean['org_founding_date'] = isset($input['org_founding_date'])
            ? sanitize_text_field($input['org_founding_date'])
            : '';

        $clean['org_social_urls'] = [];
        if (! empty($input['org_social_urls']) && is_array($input['org_social_urls'])) {
            foreach ($input['org_social_urls'] as $url) {
                $url = esc_url_raw(trim($url));
                if ($url) {
                    $clean['org_social_urls'][] = $url;
                }
            }
        }

        return $clean;
    }

    private static function is_non_empty(mixed $value): bool {
        if (is_array($value)) {
            return ! empty($value);
        }
        if (is_string($value)) {
            return $value !== '';
        }
        return $value !== null;
    }
}

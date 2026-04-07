<?php

declare(strict_types=1);

namespace Soderlind\JsonLd;

use Soderlind\JsonLd\Admin\Settings;

final class Cache {

    private const DEFAULT_TTL = 7 * DAY_IN_SECONDS;

    /**
     * Build the cache key for the current request.
     */
    public function get_key(): string {
        $blog_id = get_current_blog_id();

        if (is_singular()) {
            $post = get_post();
            if ($post instanceof \WP_Post) {
                $hash = md5($post->post_content . $post->post_modified);
                return "jsonld_{$blog_id}_{$post->ID}_{$hash}";
            }
        }

        if (is_author()) {
            $author = get_queried_object();
            if ($author instanceof \WP_User) {
                return "jsonld_{$blog_id}_author_{$author->ID}";
            }
        }

        if (is_archive() || is_home()) {
            global $wp;
            $hash = md5($wp->request . get_query_var('paged', 0));
            return "jsonld_{$blog_id}_archive_{$hash}";
        }

        // Front page or other.
        $settings_hash = $this->get_settings_hash();
        return "jsonld_{$blog_id}_site_{$settings_hash}";
    }

    /**
     * Get cached output.
     */
    public function get(string $key): ?string {
        $value = get_transient($key);
        return is_string($value) ? $value : null;
    }

    /**
     * Store output in cache.
     */
    public function set(string $key, string $output): void {
        /**
         * Filter the cache TTL in seconds.
         *
         * @param int $ttl Cache duration in seconds. Default 7 days.
         */
        $ttl = (int) apply_filters('soderlind_jsonld_cache_ttl', self::DEFAULT_TTL);
        set_transient($key, $output, $ttl);
    }

    /**
     * Invalidate cache for a specific post.
     */
    public static function invalidate_post(int $post_id): void {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        $blog_id = get_current_blog_id();
        $prefix = "jsonld_{$blog_id}_{$post_id}_";
        self::delete_transients_by_prefix($prefix);

        // Also invalidate archive caches since post changes affect archive listings.
        $archive_prefix = "jsonld_{$blog_id}_archive_";
        self::delete_transients_by_prefix($archive_prefix);
    }

    /**
     * Invalidate site-level cache (triggers on settings update).
     */
    public static function invalidate_site(): void {
        $blog_id = get_current_blog_id();
        self::delete_transients_by_prefix("jsonld_{$blog_id}_");
    }

    /**
     * Invalidate all caches across the network (triggers on network settings update).
     */
    public static function invalidate_network(): void {
        if (! is_multisite()) {
            self::invalidate_site();
            return;
        }

        $sites = get_sites(['fields' => 'ids', 'number' => 0]);
        foreach ($sites as $site_id) {
            switch_to_blog((int) $site_id);
            self::invalidate_site();
            restore_current_blog();
        }
    }

    /**
     * Delete all transients matching a prefix.
     */
    private static function delete_transients_by_prefix(string $prefix): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
        $transient_keys = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $prefix) . '%',
            ),
        );

        foreach ($transient_keys as $key) {
            $transient_name = str_replace('_transient_', '', $key);
            delete_transient($transient_name);
        }
    }

    private function get_settings_hash(): string {
        $settings = Settings::get_merged();
        $network_version = is_multisite() ? (string) get_site_option('soderlind_jsonld_network_version', '0') : '0';
        return md5(wp_json_encode($settings) . $network_version);
    }
}

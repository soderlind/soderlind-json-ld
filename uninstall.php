<?php
/**
 * Uninstall handler for Soderlind JSON-LD.
 *
 * Cleans up plugin data on uninstall.
 *
 * @package Soderlind\JsonLd
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

/**
 * Remove all plugin data for the current site.
 */
function soderlind_jsonld_clean_site(): void {
    global $wpdb;

    delete_option('soderlind_jsonld_settings');

    // Delete all jsonld_ transients.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $wpdb->esc_like('_transient_jsonld_') . '%',
            $wpdb->esc_like('_transient_timeout_jsonld_') . '%',
        ),
    );
}

if (is_multisite()) {
    // Clean each site.
    $sites = get_sites(['fields' => 'ids', 'number' => 0]);
    foreach ($sites as $site_id) {
        switch_to_blog((int) $site_id);
        soderlind_jsonld_clean_site();
        restore_current_blog();
    }

    // Clean network options.
    delete_site_option('soderlind_jsonld_network_settings');
    delete_site_option('soderlind_jsonld_network_version');
} else {
    soderlind_jsonld_clean_site();
}

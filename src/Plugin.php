<?php

declare(strict_types=1);

namespace Soderlind\JsonLd;

use Soderlind\JsonLd\Admin\NetworkSettingsPage;
use Soderlind\JsonLd\Admin\SettingsPage;

final class Plugin {

    public static function init(): void {
        $schema_manager = new SchemaManager(new ContentAnalyzer(), new Cache());

        add_action('wp_head', [$schema_manager, 'output'], 1);
        add_action('save_post', [Cache::class, 'invalidate_post'], 10, 1);
        add_action('update_option_soderlind_jsonld_settings', [Cache::class, 'invalidate_site']);
        add_action('update_site_option_soderlind_jsonld_network_settings', [Cache::class, 'invalidate_network']);

        if (is_admin()) {
            $settings_page = new SettingsPage();
            add_action('admin_menu', [$settings_page, 'register']);
            add_action('admin_init', [$settings_page, 'register_settings']);
            add_action('admin_post_soderlind_jsonld_flush_cache', [$settings_page, 'handle_flush_cache']);
        }

        if (is_multisite()) {
            $network_page = new NetworkSettingsPage();
            add_action('network_admin_menu', [$network_page, 'register']);
            add_action('network_admin_edit_soderlind_jsonld', [$network_page, 'handle_save']);
        }
    }
}

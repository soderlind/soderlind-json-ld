<?php
/**
 * Plugin bootstrap class.
 *
 * @package Soderlind\JsonLd
 */

declare(strict_types=1);

namespace Soderlind\JsonLd;

use Soderlind\JsonLd\Admin\NetworkSettingsPage;
use Soderlind\JsonLd\Admin\SettingsPage;

/**
 * Registers all plugin hooks and initializes components.
 */
final class Plugin {

	/**
	 * Initialize the plugin by registering hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		$schema_manager = new SchemaManager( new ContentAnalyzer(), new Cache() );

		add_action( 'wp_head', array( $schema_manager, 'output' ), 1 );
		add_action( 'save_post', array( Cache::class, 'invalidate_post' ), 10, 1 );
		add_action( 'update_option_soderlind_jsonld_settings', array( Cache::class, 'invalidate_site' ) );
		add_action( 'update_site_option_soderlind_jsonld_network_settings', array( Cache::class, 'invalidate_network' ) );

		if ( is_admin() ) {
			$settings_page = new SettingsPage();
			add_action( 'admin_menu', array( $settings_page, 'register' ) );
			add_action( 'admin_init', array( $settings_page, 'register_settings' ) );
			add_action( 'admin_post_soderlind_jsonld_flush_cache', array( $settings_page, 'handle_flush_cache' ) );
		}

		if ( is_multisite() ) {
			$network_page = new NetworkSettingsPage();
			add_action( 'network_admin_menu', array( $network_page, 'register' ) );
			add_action( 'network_admin_edit_soderlind_jsonld', array( $network_page, 'handle_save' ) );
		}
	}
}

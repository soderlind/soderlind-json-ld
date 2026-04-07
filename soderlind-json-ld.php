<?php
/**
 * Plugin Name: Soderlind JSON-LD
 * Plugin URI:  https://github.com/soderlind/soderlind-json-ld
 * Description: AI-optimized JSON-LD structured data for WordPress. Auto-detects content patterns and outputs schema.org markup via @graph for maximum AI search visibility.
 * Version:     0.2.0
 * Author:      Per Soderlind
 * Author URI:  https://soderlind.no
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.8
 * Requires PHP: 8.3
 * Text Domain: soderlind-json-ld
 * Domain Path: /languages
 *
 * @package Soderlind\JsonLd
 */

declare(strict_types=1);

namespace Soderlind\JsonLd;

use Soderlind\WordPress\GitHubUpdater;

defined( 'ABSPATH' ) || exit;

define( 'SODERLIND_JSONLD_VERSION', '0.2.0' );
define( 'SODERLIND_JSONLD_FILE', __FILE__ );
define( 'SODERLIND_JSONLD_DIR', plugin_dir_path( __FILE__ ) );

if ( file_exists( SODERLIND_JSONLD_DIR . 'vendor/autoload.php' ) ) {
	require_once SODERLIND_JSONLD_DIR . 'vendor/autoload.php';
}

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain( 'soderlind-json-ld', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

Plugin::init();

GitHubUpdater::init(
	github_url: 'https://github.com/soderlind/soderlind-json-ld',
	plugin_file: __FILE__,
	plugin_slug: 'soderlind-json-ld',
	name_regex: '/soderlind-json-ld\.zip/',
	branch: 'main',
);

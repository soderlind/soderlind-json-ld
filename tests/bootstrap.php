<?php
/**
 * PHPUnit bootstrap for Soderlind JSON-LD plugin tests.
 *
 * Sets up Brain Monkey and defines WordPress constants/stubs
 * that the plugin code expects at load time.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// WordPress constants expected by plugin code.
defined('ABSPATH') || define('ABSPATH', '/tmp/wp/');
defined('DAY_IN_SECONDS') || define('DAY_IN_SECONDS', 86400);
defined('SODERLIND_JSONLD_VERSION') || define('SODERLIND_JSONLD_VERSION', '1.0.0-test');
defined('SODERLIND_JSONLD_FILE') || define('SODERLIND_JSONLD_FILE', dirname(__DIR__) . '/soderlind-json-ld.php');
defined('SODERLIND_JSONLD_DIR') || define('SODERLIND_JSONLD_DIR', dirname(__DIR__) . '/');

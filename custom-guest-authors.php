<?php
/*
Plugin Name: Custom Guest Authors
Description: Replace the default post author name with custom guest author names using a custom field. Supports multiple authors.
Version: 2.1.0
Author: MENJ
Author URI: https://github.com/menj
License: GPLv2 or later
Text Domain: custom-guest-authors
Domain Path: /languages
Credits: This plugin was inspired by a tutorial from WPBeginner - https://www.wpbeginner.com/wp-tutorials/how-to-rewrite-guest-author-name-with-custom-fields-in-wordpress/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CGA_VERSION',    '2.1.0' );
define( 'CGA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CGA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// i18n
// ---------------------------------------------------------------------------

/**
 * Load the plugin text domain so that .po/.mo translations are applied.
 * The languages/ directory ships with custom-guest-authors-ms_MY.po/.mo.
 */
add_action( 'init', static function () {
    load_plugin_textdomain(
        'custom-guest-authors',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
} );

// ---------------------------------------------------------------------------
// Load includes
// ---------------------------------------------------------------------------

// Always required — front-end filters, cache, and REST meta registration.
require_once CGA_PLUGIN_DIR . 'includes/front-end.php';
require_once CGA_PLUGIN_DIR . 'includes/cache.php';
require_once CGA_PLUGIN_DIR . 'includes/post-meta.php';

// Admin-only — meta box, settings page, asset enqueuing.
// Not loaded on front-end page requests.
if ( is_admin() ) {
    require_once CGA_PLUGIN_DIR . 'admin/admin.php';
}

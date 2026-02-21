<?php
/**
 * Custom Guest Authors — Post meta registration
 *
 * Registers the guest-author meta key for REST API and Gutenberg access.
 * Loaded on every request (required on front-end for REST responses).
 *
 * @package CustomGuestAuthors
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Register post meta for REST API / Gutenberg access
// ---------------------------------------------------------------------------

/**
 * Ensure all enabled post types declare 'custom-fields' support.
 *
 * WordPress's REST API only writes post meta for post types that support
 * 'custom-fields'. Without this, useEntityProp() in the Gutenberg sidebar
 * panel accepts input but silently discards it on save — no error is returned
 * to the editor. register_post_meta() with show_in_rest => true is not
 * sufficient on its own; the post type support flag is also required.
 *
 * This is called on 'init' at priority 9, before cga_register_post_meta()
 * at priority 10, so the support flag is set before meta registration.
 */
add_action( 'init', static function () {
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) ) {
        $enabled_types = array( 'post' );
    }
    foreach ( $enabled_types as $post_type ) {
        if ( post_type_exists( $post_type ) || 'post' === $post_type || 'page' === $post_type ) {
            add_post_type_support( $post_type, 'custom-fields' );
        }
    }
}, 9 );

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- cga_ is the plugin's registered prefix.
function cga_register_post_meta() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    register_post_meta( '', 'guest-author', array(
        'show_in_rest'      => true,
        'single'            => true,
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback'     => function() {
            return current_user_can( 'edit_posts' );
        },
    ) );
}
add_action( 'init', 'cga_register_post_meta' );

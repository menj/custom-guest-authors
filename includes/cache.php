<?php
/**
 * Custom Guest Authors — Cache invalidation
 *
 * Clears per-post transients when post meta or post content changes.
 * Loaded on every request.
 *
 * @package CustomGuestAuthors
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Cache invalidation
// ---------------------------------------------------------------------------

/**
 * Invalidate the transient on post save.
 *
 * @param int $post_id
 */
function custom_guest_authors_invalidate_cache( $post_id ) {
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    delete_transient( 'cga_' . $post_id );
}
add_action( 'save_post', 'custom_guest_authors_invalidate_cache' );

/**
 * Invalidate the transient on direct post meta changes (REST API, WP-CLI, etc.).
 *
 * @param int    $meta_id
 * @param int    $post_id
 * @param string $meta_key
 */
function custom_guest_authors_invalidate_cache_on_meta_update( $meta_id, $post_id, $meta_key ) {
    if ( 'guest-author' === $meta_key ) {
        delete_transient( 'cga_' . $post_id );
    }
}
add_action( 'updated_post_meta', 'custom_guest_authors_invalidate_cache_on_meta_update', 10, 3 );
add_action( 'added_post_meta',   'custom_guest_authors_invalidate_cache_on_meta_update', 10, 3 );
add_action( 'deleted_post_meta', 'custom_guest_authors_invalidate_cache_on_meta_update', 10, 3 );

// ---------------------------------------------------------------------------
// Version-based cache flush
// ---------------------------------------------------------------------------

/**
 * Flush all cga_ transients when the plugin is updated to a new version.
 *
 * Old versions stored '' (empty string) as the cache sentinel for posts with
 * no guest-author meta. The new sentinel is '__cga_none__'. Any site upgrading
 * from an older version will have stale '' entries that would permanently block
 * the default guest author from showing. This one-time flush on version change
 * clears them all.
 */
add_action( 'init', static function () {
    $stored_version = get_option( 'cga_cache_version', '' );
    if ( $stored_version === CGA_VERSION ) {
        return;
    }

    // Record the new version BEFORE flushing. If the DELETE fails the version
    // is still updated, so we do not re-run the flush on every subsequent
    // request. A partial flush is acceptable; the next save_post will
    // re-prime affected transients correctly.
    update_option( 'cga_cache_version', CGA_VERSION, false );

    global $wpdb;
    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cga_%' OR option_name LIKE '_transient_timeout_cga_%'" );
} );

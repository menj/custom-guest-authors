<?php
/**
 * Custom Guest Authors — Front-end filters
 *
 * Handles author name replacement, author link suppression, and
 * JSON-LD schema author removal. Loaded on every request.
 *
 * @package CustomGuestAuthors
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sentinel value stored in the transient cache when a post has been checked
 * and confirmed to have no guest-author meta. Distinct from '' (empty string)
 * so that get_transient() returning false (cache miss) can never be confused
 * with a post that genuinely has no meta set.
 */
define( 'CGA_NO_META', '__cga_none__' );

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

/**
 * Read the guest-author meta for a post, using the transient cache.
 *
 * Returns the raw comma-separated string if meta is set, or '' if none.
 * Primes the cache with CGA_NO_META on the first miss so subsequent calls
 * skip the DB read without ambiguity.
 *
 * @param int $post_id
 * @return string Raw meta value, or '' if none.
 */
function cga_get_authors( $post_id ) {
    $transient_key = 'cga_' . $post_id;
    $cached        = get_transient( $transient_key );

    if ( false === $cached ) {
        $db_value = get_post_meta( $post_id, 'guest-author', true );
        $ttl      = absint( get_option( 'cga_cache_ttl', 12 ) );
        if ( $ttl < 1 ) {
            $ttl = 12;
        }
        set_transient( $transient_key, $db_value ? $db_value : CGA_NO_META, $ttl * HOUR_IN_SECONDS );
        return $db_value;
    }

    return ( CGA_NO_META === $cached ) ? '' : $cached;
}

/**
 * Format a raw comma-separated author string according to the join style setting.
 *
 * @param string $raw Comma-separated author names from post meta.
 * @return string Formatted author name string ready to output.
 */
function cga_format_authors( $raw ) {
    $author_array = array_values(
        array_filter(
            array_map( 'sanitize_text_field', array_map( 'trim', explode( ',', $raw ) ) )
        )
    );
    $count      = count( $author_array );
    $join_style = get_option( 'cga_join_style', 'natural' );

    if ( 1 === $count ) {
        return $author_array[0];
    }

    if ( 'comma' === $join_style ) {
        return implode( ', ', $author_array );
    }

    if ( 'ampersand' === $join_style ) {
        return implode( ' & ', $author_array );
    }

    // Natural (default): "A and B" or "A, B and C".
    $last = array_pop( $author_array );
    return ( 1 === count( $author_array )
        ? $author_array[0]
        : implode( ', ', $author_array )
    ) . ' ' . __( 'and', 'custom-guest-authors' ) . ' ' . $last;
}

// ---------------------------------------------------------------------------
// Author name filters
// ---------------------------------------------------------------------------

// Priority 20 — runs after ent2ncr (priority 8) and any other early hooks.
// get_the_author_display_name is the dynamic filter fired by get_the_author_meta()
// for the 'display_name' field. Block themes (TT21–TT25) use this path via the
// core/post-author and core/post-author-name blocks.
add_filter( 'the_author',                  'custom_guest_authors_name', 20 );
add_filter( 'get_the_author_display_name', 'custom_guest_authors_name_meta', 20, 3 );

/**
 * Replace the post author display name — classic theme path.
 *
 * Fires via the_author() / get_the_author(), used by classic themes and any
 * code that calls these functions directly.
 *
 * @param string $name The current author display name.
 * @return string Guest author name, or original if none is set.
 */
function custom_guest_authors_name( $name ) {
    $post = get_post();
    if ( ! $post instanceof WP_Post ) {
        $post = get_queried_object();
    }
    if ( ! $post instanceof WP_Post ) {
        return $name;
    }

    $post_type     = get_post_type( $post->ID );
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) ) {
        $enabled_types = array( 'post' );
    }
    if ( ! in_array( $post_type, $enabled_types, true ) ) {
        return $name;
    }

    // apply_on gate: restricts per-post overrides to singular views when set.
    // The site-wide default (below) is intentionally exempt — it is a site-level
    // override, not a post-specific one, and should always apply.
    $apply_on     = get_option( 'cga_apply_on', 'all' );
    $block_on_ctx = ( 'singular' === $apply_on && ! is_singular() );

    $authors = cga_get_authors( $post->ID );

    if ( $authors && ! $block_on_ctx ) {
        return cga_format_authors( $authors );
    }

    $default = get_option( 'cga_default_guest_author', '' );
    if ( $default ) {
        return sanitize_text_field( $default );
    }

    return $name;
}

/**
 * Replace the author display_name value — block theme / get_the_author_meta() path.
 *
 * Fires via get_the_author_meta( 'display_name' ), used by all block themes
 * (Twenty Twenty-One through Twenty Twenty-Five) and most modern page builders
 * (Elementor, Beaver Builder, Divi, WPBakery, Kadence, Astra, GeneratePress).
 *
 * WordPress fires a dynamic filter inside get_the_author_meta():
 *   apply_filters( "get_the_author_{$field}", $value, $user_id, $original_user_id )
 * For 'display_name' this resolves to 'get_the_author_display_name'.
 * There is no static 'get_the_author_meta' filter in WordPress core.
 *
 * @param string   $value            The current display name value.
 * @param int      $user_id          The author's user ID.
 * @param int|bool $original_user_id The originally requested user ID.
 * @return string Guest author name, or original if none is set.
 */
function custom_guest_authors_name_meta( $value, $user_id, $original_user_id ) {
    // get_the_ID() is reliable in both classic Loop and FSE block rendering:
    // it is set by WP_Query::get_posts() before any template or block rendering
    // begins, whereas $GLOBALS['post'] may not be set in FSE contexts that do
    // not call the_post() / setup_postdata().
    $post_id = get_the_ID();

    if ( ! $post_id ) {
        $queried = get_queried_object();
        $post_id = ( $queried instanceof WP_Post ) ? $queried->ID : 0;
    }

    if ( ! $post_id ) {
        return $value;
    }

    $post_type     = get_post_type( $post_id );
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) || ! in_array( $post_type, $enabled_types, true ) ) {
        return $value;
    }

    // Mirror the apply_on gate from custom_guest_authors_name() exactly, so both
    // filter paths behave identically regardless of which one the theme uses.
    $apply_on     = get_option( 'cga_apply_on', 'all' );
    $block_on_ctx = ( 'singular' === $apply_on && ! is_singular() );

    $authors = cga_get_authors( $post_id );

    if ( $authors && ! $block_on_ctx ) {
        return cga_format_authors( $authors );
    }

    $default = get_option( 'cga_default_guest_author', '' );
    if ( $default ) {
        return sanitize_text_field( $default );
    }

    return $value;
}

// ---------------------------------------------------------------------------
// Author link suppression
// ---------------------------------------------------------------------------

/**
 * Suppress the author archive URL whenever a guest author is active.
 *
 * WordPress builds the author link in two stages:
 *   1. get_author_posts_url() → filtered by 'author_link'
 *   2. the_author_posts_link() wraps that URL in <a> → filtered by 'the_author_posts_link'
 *
 * Returning an empty string from 'author_link' causes WordPress to skip
 * rendering the anchor entirely.
 *
 * @param string $url The author archive URL.
 * @return string Empty string if a guest author is active, original URL otherwise.
 */
function custom_guest_authors_suppress_url( $url ) {
    $post = get_post();
    if ( ! $post instanceof WP_Post ) {
        $post = get_queried_object();
    }
    if ( ! $post instanceof WP_Post ) {
        return $url;
    }

    $post_type     = get_post_type( $post->ID );
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) || ! in_array( $post_type, $enabled_types, true ) ) {
        return $url;
    }

    if ( cga_get_authors( $post->ID ) ) {
        return '';
    }

    if ( get_option( 'cga_default_guest_author', '' ) ) {
        return '';
    }

    return $url;
}
add_filter( 'author_link', 'custom_guest_authors_suppress_url', 10, 1 );

/**
 * Fallback link stripper for themes that build the author anchor themselves.
 *
 * Some themes call the_author_posts_link() with a hardcoded href rather than
 * using get_author_posts_url(), bypassing the 'author_link' filter above.
 * This strips the <a> tag and returns the plain inner text as a safety net.
 *
 * @param string $link Full <a href="...">Name</a> HTML string.
 * @return string Plain author name with anchor tags stripped.
 */
function custom_guest_authors_strip_link( $link ) {
    $post = get_post();
    if ( ! $post instanceof WP_Post ) {
        $post = get_queried_object();
    }
    if ( ! $post instanceof WP_Post ) {
        return $link;
    }

    $post_type     = get_post_type( $post->ID );
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) || ! in_array( $post_type, $enabled_types, true ) ) {
        return $link;
    }

    if ( cga_get_authors( $post->ID ) || get_option( 'cga_default_guest_author', '' ) ) {
        return wp_strip_all_tags( $link );
    }

    return $link;
}
add_filter( 'the_author_posts_link', 'custom_guest_authors_strip_link', 10, 1 );

// ---------------------------------------------------------------------------
// Schema / structured data suppression
// ---------------------------------------------------------------------------

/**
 * Remove the author from Yoast SEO JSON-LD schema when enabled.
 *
 * @param array $data The schema graph array.
 * @return array Modified schema.
 */
function cga_suppress_yoast_author( $data ) {
    if ( ! get_option( 'cga_suppress_schema', false ) ) {
        return $data;
    }
    if ( ! is_singular() || ! isset( $data['@graph'] ) ) {
        return $data;
    }
    foreach ( $data['@graph'] as &$node ) {
        if ( isset( $node['@type'] ) && in_array( $node['@type'], array( 'Article', 'WebPage', 'NewsArticle', 'BlogPosting' ), true ) ) {
            unset( $node['author'] );
        }
    }
    return $data;
}
add_filter( 'wpseo_schema_graph', 'cga_suppress_yoast_author' );

/**
 * Remove the author from Rank Math JSON-LD schema when enabled.
 *
 * @param array $data The schema entity array.
 * @return array Modified schema.
 */
function cga_suppress_rankmath_author( $data ) {
    if ( ! get_option( 'cga_suppress_schema', false ) ) {
        return $data;
    }
    unset( $data['author'] );
    return $data;
}
add_filter( 'rank_math/schema/article', 'cga_suppress_rankmath_author' );

<?php
/*
Plugin Name: Custom Guest Authors
Description: Replace the default post author name with custom guest author names using a custom field. Supports multiple authors.
Version: 1.9.1
Author: MENJ
Author URI: https://github.com/menj
License: GPLv2 or later
Text Domain: custom-guest-authors
Credits: This plugin was inspired by a tutorial from WPBeginner - https://www.wpbeginner.com/wp-tutorials/how-to-rewrite-guest-author-name-with-custom-fields-in-wordpress/
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CGA_VERSION',     '1.9.1' );
define( 'CGA_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CGA_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// i18n
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Front-end: author name filter
// ---------------------------------------------------------------------------

add_filter( 'the_author',     'custom_guest_authors_name' );
add_filter( 'get_the_author', 'custom_guest_authors_name' );

/**
 * Suppress the author archive URL whenever a guest author is active.
 *
 * WordPress builds the author link in two stages:
 *   1. get_author_posts_url() → filtered by 'author_link'
 *   2. the_author_posts_link() wraps that URL in <a> → filtered by 'the_author_posts_link'
 *
 * Returning an empty string from 'author_link' causes WordPress to skip
 * rendering the anchor entirely, making the output plain text. We also filter
 * 'the_author_posts_link' as a fallback for themes that build the anchor
 * themselves without calling get_author_posts_url().
 *
 * @param string $url The author archive URL.
 * @return string Empty string if a guest author is set, original URL otherwise.
 */
function custom_guest_authors_suppress_url( $url ) {
    global $post;

    if ( null === $post ) {
        return $url;
    }

    $post_type     = get_post_type( $post->ID );
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) || ! in_array( $post_type, $enabled_types, true ) ) {
        return $url;
    }

    $transient_key = 'cga_' . $post->ID;
    $authors       = get_transient( $transient_key );

    if ( false === $authors ) {
        $authors = get_post_meta( $post->ID, 'guest-author', true );
        $ttl     = absint( get_option( 'cga_cache_ttl', 12 ) );
        if ( $ttl < 1 ) {
            $ttl = 12;
        }
        set_transient( $transient_key, $authors ? $authors : '', $ttl * HOUR_IN_SECONDS );
    }

    if ( $authors ) {
        return '';
    }

    $default = get_option( 'cga_default_guest_author', '' );
    if ( $default ) {
        return '';
    }

    return $url;
}
add_filter( 'author_link', 'custom_guest_authors_suppress_url', 10, 1 );

/**
 * Fallback: if the theme calls the_author_posts_link() and still produces an
 * anchor (e.g. it hardcodes the href rather than using get_author_posts_url()),
 * strip the <a> tag and return the inner text only.
 *
 * @param string $link Full <a href="...">Name</a> HTML string.
 * @return string Plain author name with all anchor tags stripped.
 */
function custom_guest_authors_strip_link( $link ) {
    global $post;

    if ( null === $post ) {
        return $link;
    }

    $authors = get_post_meta( $post->ID, 'guest-author', true );
    if ( ! $authors ) {
        $default = get_option( 'cga_default_guest_author', '' );
        if ( ! $default ) {
            return $link;
        }
    }

    // Strip <a> and </a> tags, return only the link text.
    return wp_strip_all_tags( $link );
}
add_filter( 'the_author_posts_link', 'custom_guest_authors_strip_link', 10, 1 );

/**
 * Replace the post author display name with the guest author custom field value.
 *
 * @param string $name The current author display name.
 * @return string The guest author name, or the original name if no guest author is set.
 */
function custom_guest_authors_name( $name ) {
    global $post;

    if ( null === $post ) {
        return $name;
    }

    // Check whether this post type has the override enabled.
    $post_type     = get_post_type( $post->ID );
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) ) {
        $enabled_types = array( 'post' );
    }
    if ( ! in_array( $post_type, $enabled_types, true ) ) {
        return $name;
    }

    // Honour the 'Show Override On' setting — optionally restrict to singular views.
    $apply_on = get_option( 'cga_apply_on', 'all' );
    if ( 'singular' === $apply_on && ! is_singular() ) {
        return $name;
    }

    $transient_key = 'cga_' . $post->ID;
    $authors       = get_transient( $transient_key );

    if ( false === $authors ) {
        $authors = get_post_meta( $post->ID, 'guest-author', true );
        $ttl = absint( get_option( 'cga_cache_ttl', 12 ) );
        if ( $ttl < 1 ) {
            $ttl = 12;
        }
        set_transient( $transient_key, $authors ? $authors : '', $ttl * HOUR_IN_SECONDS );
    }

    if ( $authors ) {
        $author_array = array_map( 'trim', explode( ',', $authors ) );
        $author_array = array_map( 'sanitize_text_field', $author_array );
        $author_array = array_values( array_filter( $author_array ) );

        $count      = count( $author_array );
        $join_style = get_option( 'cga_join_style', 'natural' );

        if ( 1 === $count ) {
            $name = $author_array[0];
        } elseif ( 'comma' === $join_style ) {
            // Comma-only: "A, B, C"
            $name = implode( ', ', $author_array );
        } elseif ( 'ampersand' === $join_style ) {
            // Ampersand: "A & B & C"
            $name = implode( ' & ', $author_array );
        } else {
            // Natural (default): "A and B" or "A, B and C"
            if ( 2 === $count ) {
                $name = $author_array[0] . ' ' . __( 'and', 'custom-guest-authors' ) . ' ' . $author_array[1];
            } else {
                $last = array_pop( $author_array );
                $name = implode( ', ', $author_array ) . ' ' . __( 'and', 'custom-guest-authors' ) . ' ' . $last;
            }
        }

    } elseif ( get_option( 'cga_default_guest_author' ) ) {
        $name = sanitize_text_field( get_option( 'cga_default_guest_author' ) );
    }

    return esc_html( $name );
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
// Register post meta for REST API / Gutenberg access
// ---------------------------------------------------------------------------

/**
 * Register the guest-author meta key so the block editor can read and write it
 * via the REST API. Without this registration the Gutenberg sidebar panel
 * cannot access or persist the value.
 */
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

// ---------------------------------------------------------------------------
// Classic editor meta box
// ---------------------------------------------------------------------------

/**
 * Register the classic editor meta box.
 *
 * Only registered when the block editor is NOT active for the post type being
 * edited. When Gutenberg is active, the PluginDocumentSettingPanel handles the
 * UI instead; registering the classic meta box as well would cause WordPress to
 * render it a second time inside a collapsed compatibility panel in the sidebar.
 *
 * @param string $post_type The post type being edited.
 */
function cga_add_meta_box( $post_type ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    // Resolve the correct function — wp_use_block_editor_for_post_type()
    // replaced use_block_editor_for_post_type() in WP 6.5.
    $block_editor_check = function_exists( 'wp_use_block_editor_for_post_type' )
        ? 'wp_use_block_editor_for_post_type'
        : 'use_block_editor_for_post_type';

    // Do not register the classic meta box when the block editor is active.
    if ( $block_editor_check( $post_type ) ) {
        return;
    }

    // Respect the enabled post types setting so the meta box only appears
    // on post types where the override is actually active.
    $enabled_types = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) || empty( $enabled_types ) ) {
        $enabled_types = array( 'post' );
    }

    add_meta_box(
        'cga-meta-box',
        __( 'Guest Authors', 'custom-guest-authors' ),
        'cga_render_meta_box',
        $enabled_types,
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'cga_add_meta_box' );

/**
 * Render the classic editor meta box HTML.
 *
 * @param WP_Post $post
 */
function cga_render_meta_box( $post ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    $value = get_post_meta( $post->ID, 'guest-author', true );
    wp_nonce_field( 'cga_save_meta', 'cga_nonce' );
    ?>
    <div class="cga-field-group">
        <label for="cga_guest_author">
            <?php esc_html_e( 'Guest Author Name(s)', 'custom-guest-authors' ); ?>
        </label>
        <input
            type="text"
            id="cga_guest_author"
            name="cga_guest_author"
            value="<?php echo esc_attr( $value ); ?>"
            placeholder="<?php esc_attr_e( 'e.g. John Doe, Jane Smith', 'custom-guest-authors' ); ?>"
        />
        <p class="cga-field-hint">
            <?php esc_html_e( 'Separate multiple authors with commas. Leave blank to use the post\'s WordPress author.', 'custom-guest-authors' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Save the classic editor meta box value.
 *
 * @param int $post_id
 */
function cga_save_meta_box( $post_id ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    // Verify nonce
    if ( ! isset( $_POST['cga_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cga_nonce'] ?? '' ) ), 'cga_save_meta' ) ) {
        return;
    }

    // Bail on autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check user capability
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['cga_guest_author'] ) ) {
        $value = sanitize_text_field( wp_unslash( $_POST['cga_guest_author'] ) );

        if ( ! empty( $value ) ) {
            update_post_meta( $post_id, 'guest-author', $value );
        } else {
            delete_post_meta( $post_id, 'guest-author' );
        }
    }
}
add_action( 'save_post', 'cga_save_meta_box' );

// ---------------------------------------------------------------------------
// Enqueue assets
// ---------------------------------------------------------------------------

/**
 * Enqueue classic editor meta box assets (admin only).
 *
 * @param string $hook The current admin page hook.
 */
function cga_enqueue_admin_assets( $hook ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    // Only load on post edit screens
    if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
        return;
    }

    // Skip if the block editor is active for this post type — Gutenberg assets
    // are enqueued separately via enqueue_block_editor_assets.
    $screen    = get_current_screen();
    $post_type = $screen ? $screen->post_type : '';

    // Bail if post type is not yet determined (can happen on post-new.php
    // before the post type query arg is resolved).
    if ( empty( $post_type ) ) {
        return;
    }

    // use_block_editor_for_post_type() was deprecated in WP 6.5.
    // Use the replacement function when available.
    $block_editor_check = function_exists( 'wp_use_block_editor_for_post_type' )
        ? 'wp_use_block_editor_for_post_type'
        : 'use_block_editor_for_post_type';

    if ( $block_editor_check( $post_type ) ) {
        return;
    }

    wp_enqueue_style(
        'cga-meta-box',
        CGA_PLUGIN_URL . 'css/meta-box.css',
        array(),
        CGA_VERSION
    );

    wp_enqueue_script(
        'cga-meta-box',
        CGA_PLUGIN_URL . 'js/meta-box.js',
        array( 'jquery' ),
        CGA_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'cga_enqueue_admin_assets' );

/**
 * Enqueue Gutenberg sidebar plugin assets.
 */
function cga_enqueue_block_editor_assets() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    wp_enqueue_script(
        'cga-gutenberg-sidebar',
        CGA_PLUGIN_URL . 'js/gutenberg-sidebar.js',
        array( 'wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-components', 'wp-data', 'wp-element', 'wp-i18n', 'wp-core-data' ),
        CGA_VERSION,
        true
    );

    wp_enqueue_style(
        'cga-gutenberg-sidebar',
        CGA_PLUGIN_URL . 'css/gutenberg-sidebar.css',
        array( 'wp-edit-post' ),
        CGA_VERSION
    );
}
add_action( 'enqueue_block_editor_assets', 'cga_enqueue_block_editor_assets' );

// ---------------------------------------------------------------------------
// Schema / structured data suppression
// ---------------------------------------------------------------------------

/**
 * Remove the author from Yoast SEO JSON-LD schema when enabled.
 *
 * @param array $data The schema graph array.
 * @return array Modified schema.
 */
function cga_suppress_yoast_author( $data ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
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
function cga_suppress_rankmath_author( $data ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    if ( ! get_option( 'cga_suppress_schema', false ) ) {
        return $data;
    }
    unset( $data['author'] );
    return $data;
}
add_filter( 'rank_math/schema/article', 'cga_suppress_rankmath_author' );

// ---------------------------------------------------------------------------
// Settings page
// ---------------------------------------------------------------------------

/**
 * Register the plugin settings menu item under Settings.
 */
function cga_add_settings_menu() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    add_options_page(
        __( 'Custom Guest Authors', 'custom-guest-authors' ),
        __( 'Guest Authors', 'custom-guest-authors' ),
        'manage_options',
        'custom-guest-authors',
        'cga_render_settings_page'
    );
}
add_action( 'admin_menu', 'cga_add_settings_menu' );

/**
 * After options.php saves, redirect back to the settings page preserving the
 * active tab stored in the HTTP_REFERER query string.
 *
 * @param string $location The default redirect URL from options.php.
 * @return string Modified redirect URL with the tab parameter restored.
 */
function cga_settings_redirect( $location ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    if ( strpos( $location, 'settings-updated' ) === false ) {
        return $location;
    }
    $referer = wp_get_referer();
    if ( ! $referer ) {
        return $location;
    }
    $parsed = wp_parse_url( $referer );
    if ( ! empty( $parsed['query'] ) ) {
        parse_str( $parsed['query'], $query );
        if ( isset( $query['page'] ) && 'custom-guest-authors' === $query['page'] && isset( $query['tab'] ) ) {
            $location = add_query_arg( 'tab', sanitize_key( $query['tab'] ), $location );
        }
    }
    return $location;
}
add_filter( 'wp_redirect',      'cga_settings_redirect', 10 );
add_filter( 'wp_safe_redirect', 'cga_settings_redirect', 10 );

/**
 * Register options so they are whitelisted for update_option() via the
 * Settings API. The form posts to options.php.
 */
function cga_register_settings() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    // General tab
    register_setting( 'cga_general', 'cga_default_guest_author', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ) );
    register_setting( 'cga_general', 'cga_enabled_post_types', array(
        'type'              => 'array',
        'sanitize_callback' => 'cga_sanitize_post_types',
        'default'           => array( 'post' ),
    ) );

    // Display tab
    register_setting( 'cga_display', 'cga_join_style', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_key',
        'default'           => 'natural',
    ) );
    register_setting( 'cga_display', 'cga_apply_on', array(
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_key',
        'default'           => 'all',
    ) );

    // Advanced tab
    register_setting( 'cga_advanced', 'cga_cache_ttl', array(
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'default'           => 12,
    ) );
    register_setting( 'cga_advanced', 'cga_suppress_schema', array(
        'type'              => 'integer',
        'sanitize_callback' => 'cga_sanitize_checkbox',
        'default'           => 0,
    ) );
}

/**
 * Sanitize the cga_enabled_post_types array — allow only registered post type slugs.
 *
 * @param mixed $input Raw input from the form.
 * @return array Sanitized array of valid post type slugs.
 */
function cga_sanitize_post_types( $input ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    $valid = array_keys( get_post_types( array( 'public' => true ) ) );
    if ( ! is_array( $input ) ) {
        return array();
    }
    // Filter out the empty sentinel value added to ensure the key is always POSTed.
    $input = array_filter( $input, 'strlen' );
    return array_values( array_intersect( array_map( 'sanitize_key', $input ), $valid ) );
}

/**
 * Sanitize a checkbox field — returns 1 if the value is truthy, 0 otherwise.
 * Unlike rest_sanitize_boolean, this works correctly with HTML form submissions
 * where unchecked checkboxes send nothing (which WordPress treats as missing,
 * not false). The hidden companion input in the form sends '0' when unchecked.
 *
 * @param mixed $input Raw value from the form.
 * @return int 1 or 0.
 */
function cga_sanitize_checkbox( $input ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    return ( ! empty( $input ) && '0' !== $input ) ? 1 : 0;
}
add_action( 'admin_init', 'cga_register_settings' );

/**
 * Enqueue settings page assets.
 *
 * @param string $hook Current admin page hook.
 */
function cga_enqueue_settings_assets( $hook ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
    if ( 'settings_page_custom-guest-authors' !== $hook ) {
        return;
    }
    wp_enqueue_style(
        'cga-settings',
        CGA_PLUGIN_URL . 'css/settings.css',
        array(),
        CGA_VERSION
    );
    wp_enqueue_script(
        'cga-settings',
        CGA_PLUGIN_URL . 'js/settings.js',
        array(),
        CGA_VERSION,
        true
    );
    wp_localize_script(
        'cga-settings',
        'cgaSettings',
        array(
            'previewNames' => array( 'Zamri Vinoth', 'Firdaus Wong', 'Ali Hassan' ),
            'i18nAnd'      => __( 'and', 'custom-guest-authors' ),
        )
    );
}
add_action( 'admin_enqueue_scripts', 'cga_enqueue_settings_assets' );

/**
 * Render the settings page.
 */
function cga_render_settings_page() { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // URL-based tab state.
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! in_array( $active_tab, array( 'general', 'display', 'advanced' ), true ) ) {
        $active_tab = 'general';
    }

    $tab_url = function( $slug ) {
        return esc_url( add_query_arg( array( 'page' => 'custom-guest-authors', 'tab' => $slug ), admin_url( 'options-general.php' ) ) );
    };

    $option_group = array(
        'general'  => 'cga_general',
        'display'  => 'cga_display',
        'advanced' => 'cga_advanced',
    );

    // Read current values
    $default_author    = get_option( 'cga_default_guest_author', '' );
    $enabled_types     = get_option( 'cga_enabled_post_types', array( 'post' ) );
    if ( ! is_array( $enabled_types ) ) { $enabled_types = array( 'post' ); }
    $join_style        = get_option( 'cga_join_style', 'natural' );
    $apply_on          = get_option( 'cga_apply_on', 'all' );
    $cache_ttl         = absint( get_option( 'cga_cache_ttl', 12 ) );
    $suppress_schema   = (bool) get_option( 'cga_suppress_schema', false );

    // All public post types for the checkbox grid
    $all_post_types = get_post_types( array( 'public' => true ), 'objects' );

    ?>
    <div class="wrap cga-wrap">

        <!-- Page header -->
        <div class="wpcp-page-header">
            <div class="wpcp-page-header-content">
                <h1 class="wpcp-page-title">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect width="32" height="32" rx="7" fill="currentColor" fill-opacity="0.1"/>
                        <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2" fill="none"/>
                        <circle cx="21" cy="12" r="3" stroke="currentColor" stroke-width="1.5" fill="none"/>
                        <path d="M4 24c0-3.314 3.582-6 8-6s8 2.686 8 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" fill="none"/>
                        <path d="M21 18c2.21 0 4 1.567 4 3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                    </svg>
                    <?php esc_html_e( 'Guest Authors', 'custom-guest-authors' ); ?>
                </h1>
                <span class="wpcp-version-badge">v<?php echo esc_html( CGA_VERSION ); ?></span>
            </div>
            <p class="wpcp-page-description">
                <?php esc_html_e( 'Replace WordPress author names with custom guest author names per post. Supports multiple authors.', 'custom-guest-authors' ); ?>
            </p>
        </div>

        <?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
            <div class="cga-notice cga-notice-success">
                <svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1.5-4.5 5-5-1-1-4 4-2-2-1 1 3 3z" fill="currentColor"/>
                </svg>
                <?php esc_html_e( 'Settings saved.', 'custom-guest-authors' ); ?>
            </div>
        <?php endif; ?>

        <!-- Tab navigation -->
        <nav class="wpcp-tabs" role="tablist">
            <a href="<?php echo $tab_url( 'general' ); // phpcs:ignore ?>"
               class="wpcp-tab<?php echo 'general' === $active_tab ? ' wpcp-tab-active' : ''; ?>"
               role="tab" aria-selected="<?php echo 'general' === $active_tab ? 'true' : 'false'; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                <?php esc_html_e( 'General', 'custom-guest-authors' ); ?>
            </a>
            <a href="<?php echo $tab_url( 'display' ); // phpcs:ignore ?>"
               class="wpcp-tab<?php echo 'display' === $active_tab ? ' wpcp-tab-active' : ''; ?>"
               role="tab" aria-selected="<?php echo 'display' === $active_tab ? 'true' : 'false'; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <?php esc_html_e( 'Display', 'custom-guest-authors' ); ?>
            </a>
            <a href="<?php echo $tab_url( 'advanced' ); // phpcs:ignore ?>"
               class="wpcp-tab<?php echo 'advanced' === $active_tab ? ' wpcp-tab-active' : ''; ?>"
               role="tab" aria-selected="<?php echo 'advanced' === $active_tab ? 'true' : 'false'; ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="4 17 10 11 4 5"/>
                    <line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
                <?php esc_html_e( 'Advanced', 'custom-guest-authors' ); ?>
            </a>
        </nav>

        <form method="post" action="options.php">
            <?php settings_fields( $option_group[ $active_tab ] ); ?>

            <?php if ( 'general' === $active_tab ) : ?>

                <!-- ── General tab ───────────────────────────────────────── -->

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <?php esc_html_e( 'Default Author', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <div class="wpcp-field-row">
                            <label class="wpcp-field-label" for="cga_default_guest_author">
                                <?php esc_html_e( 'Fallback Guest Author Name', 'custom-guest-authors' ); ?>
                            </label>
                            <input
                                type="text"
                                id="cga_default_guest_author"
                                name="cga_default_guest_author"
                                class="wpcp-field-input"
                                value="<?php echo esc_attr( $default_author ); ?>"
                                placeholder="<?php esc_attr_e( 'e.g. Editorial Team', 'custom-guest-authors' ); ?>"
                            />
                            <p class="wpcp-help-text">
                                <?php esc_html_e( 'Shown when a post has no guest-author field set. Leave blank to fall back to the WordPress post author.', 'custom-guest-authors' ); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                                <line x1="8" y1="21" x2="16" y2="21"/>
                                <line x1="12" y1="17" x2="12" y2="21"/>
                            </svg>
                            <?php esc_html_e( 'Active Post Types', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <p class="wpcp-help-text" style="margin-bottom:14px;">
                            <?php esc_html_e( 'Select which post types the guest author override applies to. The meta box will appear in the editor for all checked types.', 'custom-guest-authors' ); ?>
                        </p>
                        <!-- Hidden sentinel: ensures the key is present in POST even when
                             all checkboxes are unchecked, so the sanitize callback can
                             return an empty array rather than the default. -->
                        <input type="hidden" name="cga_enabled_post_types[]" value="" />
                        <div class="wpcp-checkbox-grid">
                            <?php foreach ( $all_post_types as $pt ) : ?>
                                <label class="wpcp-checkbox-card<?php echo in_array( $pt->name, $enabled_types, true ) ? ' wpcp-checkbox-card--checked' : ''; ?>">
                                    <input
                                        type="checkbox"
                                        name="cga_enabled_post_types[]"
                                        value="<?php echo esc_attr( $pt->name ); ?>"
                                        <?php checked( in_array( $pt->name, $enabled_types, true ) ); ?>
                                    />
                                    <span class="wpcp-checkbox-indicator"></span>
                                    <span class="wpcp-checkbox-text"><?php echo esc_html( $pt->label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <?php esc_html_e( 'Usage', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <div class="wpcp-info-box">
                            <?php esc_html_e( 'Set guest authors per post using the "Guest Authors" panel in the post editor sidebar. Separate multiple names with commas. The custom field key is', 'custom-guest-authors' ); ?>
                            <code>guest-author</code>.
                        </div>
                    </div>
                </div>

            <?php elseif ( 'display' === $active_tab ) : ?>

                <!-- ── Display tab ───────────────────────────────────────── -->

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <line x1="8" y1="6" x2="21" y2="6"/>
                                <line x1="8" y1="12" x2="21" y2="12"/>
                                <line x1="8" y1="18" x2="21" y2="18"/>
                                <line x1="3" y1="6" x2="3.01" y2="6"/>
                                <line x1="3" y1="12" x2="3.01" y2="12"/>
                                <line x1="3" y1="18" x2="3.01" y2="18"/>
                            </svg>
                            <?php esc_html_e( 'Multi-Author Join Style', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <p class="wpcp-help-text" style="margin-bottom:16px;">
                            <?php esc_html_e( 'Controls how multiple author names are joined together when displayed.', 'custom-guest-authors' ); ?>
                        </p>
                        <div class="wpcp-radio-group">
                            <label class="wpcp-radio-card<?php echo 'natural' === $join_style ? ' selected' : ''; ?>">
                                <input type="radio" name="cga_join_style" value="natural" <?php checked( $join_style, 'natural' ); ?> />
                                <span class="wpcp-radio-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                </span>
                                <span class="wpcp-radio-text">
                                    <strong><?php esc_html_e( 'Natural language', 'custom-guest-authors' ); ?></strong>
                                    <span class="wpcp-radio-example">A, B <?php esc_html_e( 'and', 'custom-guest-authors' ); ?> C</span>
                                </span>
                            </label>
                            <label class="wpcp-radio-card<?php echo 'comma' === $join_style ? ' selected' : ''; ?>">
                                <input type="radio" name="cga_join_style" value="comma" <?php checked( $join_style, 'comma' ); ?> />
                                <span class="wpcp-radio-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                                </span>
                                <span class="wpcp-radio-text">
                                    <strong><?php esc_html_e( 'Comma only', 'custom-guest-authors' ); ?></strong>
                                    <span class="wpcp-radio-example">A, B, C</span>
                                </span>
                            </label>
                            <label class="wpcp-radio-card<?php echo 'ampersand' === $join_style ? ' selected' : ''; ?>">
                                <input type="radio" name="cga_join_style" value="ampersand" <?php checked( $join_style, 'ampersand' ); ?> />
                                <span class="wpcp-radio-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.5 12c0 4.4-3.6 8-8 8s-8-3.6-8-8 3.6-8 8-8c2 0 3.9.7 5.3 2"/><path d="M22 22l-5-5"/></svg>
                                </span>
                                <span class="wpcp-radio-text">
                                    <strong><?php esc_html_e( 'Ampersand', 'custom-guest-authors' ); ?></strong>
                                    <span class="wpcp-radio-example">A &amp; B &amp; C</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                            <?php esc_html_e( 'Show Override On', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <p class="wpcp-help-text" style="margin-bottom:16px;">
                            <?php esc_html_e( 'Control whether the guest author name is applied everywhere or only on individual post pages.', 'custom-guest-authors' ); ?>
                        </p>
                        <div class="wpcp-radio-group">
                            <label class="wpcp-radio-card<?php echo 'all' === $apply_on ? ' selected' : ''; ?>">
                                <input type="radio" name="cga_apply_on" value="all" <?php checked( $apply_on, 'all' ); ?> />
                                <span class="wpcp-radio-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                                </span>
                                <span class="wpcp-radio-text">
                                    <strong><?php esc_html_e( 'Everywhere', 'custom-guest-authors' ); ?></strong>
                                    <span class="wpcp-radio-example"><?php esc_html_e( 'Singles, archives, feeds', 'custom-guest-authors' ); ?></span>
                                </span>
                            </label>
                            <label class="wpcp-radio-card<?php echo 'singular' === $apply_on ? ' selected' : ''; ?>">
                                <input type="radio" name="cga_apply_on" value="singular" <?php checked( $apply_on, 'singular' ); ?> />
                                <span class="wpcp-radio-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </span>
                                <span class="wpcp-radio-text">
                                    <strong><?php esc_html_e( 'Single posts only', 'custom-guest-authors' ); ?></strong>
                                    <span class="wpcp-radio-example"><?php esc_html_e( 'Not on archive/home loops', 'custom-guest-authors' ); ?></span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                            <?php esc_html_e( 'Preview', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <?php
                        $p_names = array( 'Zamri Vinoth', 'Firdaus Wong', 'Ali Hassan' );
                        $p_last  = array_pop( $p_names );
                        if ( 'comma' === $join_style ) {
                            $p3 = implode( ', ', array_merge( $p_names, array( $p_last ) ) );
                            $p2 = 'Zamri Vinoth, Firdaus Wong';
                        } elseif ( 'ampersand' === $join_style ) {
                            $p3 = implode( ' & ', array_merge( $p_names, array( $p_last ) ) );
                            $p2 = 'Zamri Vinoth & Firdaus Wong';
                        } else {
                            $p3 = implode( ', ', $p_names ) . ' ' . __( 'and', 'custom-guest-authors' ) . ' ' . $p_last;
                            $p2 = 'Zamri Vinoth ' . __( 'and', 'custom-guest-authors' ) . ' Firdaus Wong';
                        }
                        ?>
                        <span class="wpcp-preview-label"><?php esc_html_e( '3 authors', 'custom-guest-authors' ); ?></span>
                        <div class="wpcp-preview-output" id="cga-preview-3"><?php echo esc_html( $p3 ); ?></div>
                        <span class="wpcp-preview-label" style="margin-top:12px;display:block;"><?php esc_html_e( '2 authors', 'custom-guest-authors' ); ?></span>
                        <div class="wpcp-preview-output" id="cga-preview-2"><?php echo esc_html( $p2 ); ?></div>
                    </div>
                </div>

            <?php elseif ( 'advanced' === $active_tab ) : ?>

                <!-- ── Advanced tab ──────────────────────────────────────── -->

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <?php esc_html_e( 'Cache', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <div class="wpcp-field-row">
                            <label class="wpcp-field-label" for="cga_cache_ttl">
                                <?php esc_html_e( 'Cache Lifetime (hours)', 'custom-guest-authors' ); ?>
                            </label>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <input
                                    type="number"
                                    id="cga_cache_ttl"
                                    name="cga_cache_ttl"
                                    class="wpcp-field-input wpcp-input-sm"
                                    value="<?php echo esc_attr( $cache_ttl ); ?>"
                                    min="1"
                                    max="168"
                                />
                                <span style="font-size:13px;color:var(--wpcp-gray-500);"><?php esc_html_e( 'hours (1–168)', 'custom-guest-authors' ); ?></span>
                            </div>
                            <p class="wpcp-help-text">
                                <?php esc_html_e( 'How long guest author names are cached per post. Lower values mean name changes appear sooner; higher values reduce database queries. Cache is always cleared immediately on post save regardless of this setting.', 'custom-guest-authors' ); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                            </svg>
                            <?php esc_html_e( 'Structured Data', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <!-- Hidden companion: sends 0 when the checkbox is unchecked. -->
                        <input type="hidden" name="cga_suppress_schema" value="0" />
                        <label class="wpcp-toggle">
                            <input
                                type="checkbox"
                                name="cga_suppress_schema"
                                value="1"
                                <?php checked( $suppress_schema ); ?>
                            />
                            <span class="wpcp-toggle-slider"></span>
                            <span class="wpcp-toggle-text">
                                <span class="wpcp-toggle-label"><?php esc_html_e( 'Suppress author from JSON-LD schema', 'custom-guest-authors' ); ?></span>
                                <span class="wpcp-toggle-desc"><?php esc_html_e( 'Removes the author property from Article schema output. Prevents search engines from indexing the WordPress username as the author. Works with Yoast SEO and Rank Math.', 'custom-guest-authors' ); ?></span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="wpcp-card">
                    <div class="wpcp-card-header">
                        <h3>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <polyline points="4 17 10 11 4 5"/>
                                <line x1="12" y1="19" x2="20" y2="19"/>
                            </svg>
                            <?php esc_html_e( 'Debug Information', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body">
                        <?php
                        global $wpdb;
                        $cache_key       = 'cga_transient_count';
                        $transient_count = wp_cache_get( $cache_key, 'custom-guest-authors' );
                        if ( false === $transient_count ) {
                            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                            $transient_count = $wpdb->get_var(
                                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_cga_%'"
                            );
                            wp_cache_set( $cache_key, $transient_count, 'custom-guest-authors' );
                        }
                        $active_types_labels = array();
                        foreach ( $enabled_types as $slug ) {
                            if ( isset( $all_post_types[ $slug ] ) ) {
                                $active_types_labels[] = $all_post_types[ $slug ]->label;
                            }
                        }
                        ?>
                        <table class="wpcp-debug-table">
                            <tr>
                                <th><?php esc_html_e( 'Plugin version', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( CGA_VERSION ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Active post types', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( $active_types_labels ? implode( ', ', $active_types_labels ) : __( 'None', 'custom-guest-authors' ) ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Join style', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( $join_style ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Cache TTL', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( $cache_ttl ); ?> <?php esc_html_e( 'hours', 'custom-guest-authors' ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Cached entries', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( $transient_count ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Schema suppression', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo $suppress_schema ? esc_html__( 'Enabled', 'custom-guest-authors' ) : esc_html__( 'Disabled', 'custom-guest-authors' ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'WordPress version', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( get_bloginfo( 'version' ) ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'PHP version', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>

            <?php endif; ?>

            <?php if ( 'advanced' !== $active_tab || 'advanced' === $active_tab ) : ?>
                <div class="wpcp-submit-row">
                    <?php submit_button( __( 'Save Settings', 'custom-guest-authors' ), 'primary', 'submit', false ); ?>
                </div>
            <?php endif; ?>

        </form>

    </div><!-- /.cga-wrap -->
    <?php
}

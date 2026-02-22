<?php
/**
 * Custom Guest Authors — Admin
 *
 * Classic editor meta box, asset enqueuing, and settings page
 * registration. Loaded only on admin requests.
 *
 * @package CustomGuestAuthors
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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
function cga_add_meta_box( $post_type ) {
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
function cga_render_meta_box( $post ) {
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
function cga_save_meta_box( $post_id ) {
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
function cga_enqueue_admin_assets( $hook ) {
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
function cga_enqueue_block_editor_assets() {
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
// Settings page
// ---------------------------------------------------------------------------

/**
 * Register the plugin settings menu item under Settings.
 */
function cga_add_settings_menu() {
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
function cga_settings_redirect( $location ) {
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
function cga_register_settings() {
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
function cga_sanitize_post_types( $input ) {
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
function cga_sanitize_checkbox( $input ) {
    return ( ! empty( $input ) && '0' !== $input ) ? 1 : 0;
}
add_action( 'admin_init', 'cga_register_settings' );

/**
 * Enqueue settings page assets.
 *
 * @param string $hook Current admin page hook.
 */
function cga_enqueue_settings_assets( $hook ) {
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
 * Render the settings page by loading the view template.
 */
function cga_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    require_once CGA_PLUGIN_DIR . 'admin/views/settings-page.php';
}

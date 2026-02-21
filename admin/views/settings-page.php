<?php
/**
 * Custom Guest Authors — Settings page view
 *
 * Loaded by cga_render_settings_page() in admin/admin.php.
 * All variables are derived from get_option() calls within this file;
 * no variables are passed in from the calling context.
 *
 * @package CustomGuestAuthors
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

    // URL-based tab state.
    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! in_array( $active_tab, array( 'general', 'display', 'advanced', 'debug' ), true ) ) {
        $active_tab = 'general';
    }

    $tab_url = function( $slug ) {
        return esc_url( add_query_arg( array( 'page' => 'custom-guest-authors', 'tab' => $slug ), admin_url( 'options-general.php' ) ) );
    };

    $option_group = array(
        'general'  => 'cga_general',
        'display'  => 'cga_display',
        'advanced' => 'cga_advanced',
        'debug'    => 'cga_advanced', // debug tab is read-only; reuse group so settings_fields() stays valid
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
        </div><!-- /.wpcp-page-header -->
        <div class="wpcp-header-stone-bar"></div>

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
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                <?php esc_html_e( 'General', 'custom-guest-authors' ); ?>
            </a>
            <a href="<?php echo $tab_url( 'display' ); // phpcs:ignore ?>"
               class="wpcp-tab<?php echo 'display' === $active_tab ? ' wpcp-tab-active' : ''; ?>"
               role="tab" aria-selected="<?php echo 'display' === $active_tab ? 'true' : 'false'; ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                <?php esc_html_e( 'Display', 'custom-guest-authors' ); ?>
            </a>
            <a href="<?php echo $tab_url( 'advanced' ); // phpcs:ignore ?>"
               class="wpcp-tab<?php echo 'advanced' === $active_tab ? ' wpcp-tab-active' : ''; ?>"
               role="tab" aria-selected="<?php echo 'advanced' === $active_tab ? 'true' : 'false'; ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <polyline points="4 17 10 11 4 5"/>
                    <line x1="12" y1="19" x2="20" y2="19"/>
                </svg>
                <?php esc_html_e( 'Advanced', 'custom-guest-authors' ); ?>
            </a>
            <a href="<?php echo $tab_url( 'debug' ); // phpcs:ignore ?>"
               class="wpcp-tab wpcp-tab-debug<?php echo 'debug' === $active_tab ? ' wpcp-tab-active' : ''; ?>"
               role="tab" aria-selected="<?php echo 'debug' === $active_tab ? 'true' : 'false'; ?>">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>
                <?php esc_html_e( 'Debug', 'custom-guest-authors' ); ?>
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

            <?php endif; ?>

            <?php if ( 'debug' === $active_tab ) : ?>

                <!-- ── Debug tab ──────────────────────────────────────────── -->

                <?php
                // Handle clear-cache action (nonce-verified, GET-based, no settings save)
                global $wpdb;
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified via wp_verify_nonce on the same condition.
                if (
                    isset( $_GET['cga_action'], $_GET['_wpnonce'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    'clear_cache' === sanitize_key( wp_unslash( $_GET['cga_action'] ) ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                    wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'cga_clear_cache' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                ) {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
                    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_cga_%' OR option_name LIKE '_transient_timeout_cga_%'" );
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is hardcoded markup; dynamic text is escaped via esc_html__().
                    echo '<div class="cga-notice cga-notice-success"><svg width="18" height="18" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1.5-4.5 5-5-1-1-4 4-2-2-1 1 3 3z" fill="currentColor"/></svg>' . esc_html__( 'Author name cache cleared.', 'custom-guest-authors' ) . '</div>';
                }

                // Gather debug data
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
                $clear_cache_url = wp_nonce_url(
                    add_query_arg( array( 'page' => 'custom-guest-authors', 'tab' => 'debug', 'cga_action' => 'clear_cache' ), admin_url( 'options-general.php' ) ),
                    'cga_clear_cache'
                );
                ?>

                <?php require_once CGA_PLUGIN_DIR . 'admin/views/tab-debug.php'; ?>

                <div class="wpcp-card">
                    <div class="wpcp-card-header wpcp-card-header--dark">
                        <h3>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                                <path d="M12 8v4M12 16h.01"/>
                            </svg>
                            <?php esc_html_e( 'System Information', 'custom-guest-authors' ); ?>
                        </h3>
                    </div>
                    <div class="wpcp-card-body" style="padding:0;">
                        <table class="wpcp-debug-table">
                            <tr>
                                <th><?php esc_html_e( 'Plugin version', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( CGA_VERSION ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'WordPress version', 'custom-guest-authors' ); ?></th>
                                <td>
                                    <?php
                                    $wp_ver   = get_bloginfo( 'version' );
                                    $wp_ok    = version_compare( $wp_ver, '5.7', '>=' );
                                    echo '<span class="wpcp-status-pill ' . ( $wp_ok ? 'wpcp-status-pill--ok' : 'wpcp-status-pill--warn' ) . '">' . esc_html( $wp_ver ) . '</span>';
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'PHP version', 'custom-guest-authors' ); ?></th>
                                <td>
                                    <?php
                                    $php_ok = version_compare( PHP_VERSION, '8.0', '>=' );
                                    echo '<span class="wpcp-status-pill ' . ( $php_ok ? 'wpcp-status-pill--ok' : 'wpcp-status-pill--warn' ) . '">' . esc_html( PHP_VERSION ) . '</span>';
                                    ?>
                                </td>
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
                                <th><?php esc_html_e( 'Schema suppression', 'custom-guest-authors' ); ?></th>
                                <td>
                                    <?php if ( $suppress_schema ) : ?>
                                        <span class="wpcp-status-pill wpcp-status-pill--ok"><?php esc_html_e( 'Enabled', 'custom-guest-authors' ); ?></span>
                                    <?php else : ?>
                                        <span class="wpcp-status-pill wpcp-status-pill--warn"><?php esc_html_e( 'Disabled', 'custom-guest-authors' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="wpcp-card">
                    <div class="wpcp-card-header wpcp-card-header--dark">
                        <h3>
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <?php esc_html_e( 'Cache Status', 'custom-guest-authors' ); ?>
                        </h3>
                        <div class="wpcp-card-header-actions">
                            <a href="<?php echo esc_url( $clear_cache_url ); ?>"
                               class="wpcp-action-btn"
                               onclick="return confirm('<?php echo esc_js( __( 'Clear all cached guest author names?', 'custom-guest-authors' ) ); ?>');">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <polyline points="1 4 1 10 7 10"/>
                                    <path d="M3.51 15a9 9 0 1 0 .49-3.51"/>
                                </svg>
                                <?php esc_html_e( 'Clear Cache', 'custom-guest-authors' ); ?>
                            </a>
                        </div>
                    </div>
                    <div class="wpcp-card-body" style="padding:0;">
                        <table class="wpcp-debug-table">
                            <tr>
                                <th><?php esc_html_e( 'Cache lifetime', 'custom-guest-authors' ); ?></th>
                                <td><code><?php echo esc_html( $cache_ttl ); ?> <?php esc_html_e( 'hours', 'custom-guest-authors' ); ?></code></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e( 'Cached entries', 'custom-guest-authors' ); ?></th>
                                <td>
                                    <?php if ( $transient_count > 0 ) : ?>
                                        <span class="wpcp-status-pill wpcp-status-pill--ok"><?php echo esc_html( $transient_count ); ?></span>
                                    <?php else : ?>
                                        <span class="wpcp-status-pill wpcp-status-pill--warn"><?php esc_html_e( 'Empty', 'custom-guest-authors' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

            <?php endif; ?>

            <?php if ( 'debug' !== $active_tab ) : ?>
                <div class="wpcp-submit-row">
                    <?php submit_button( __( 'Save Settings', 'custom-guest-authors' ), 'primary', 'submit', false ); ?>
                </div>
            <?php endif; ?>

        </form>

    </div><!-- /.cga-wrap -->
    <?php

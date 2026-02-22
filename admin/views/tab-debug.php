<?php
/**
 * Custom Guest Authors — Debug tab view
 *
 * Runs live diagnostics and displays the results in the settings page.
 *
 * @package CustomGuestAuthors
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── Run diagnostics ──────────────────────────────────────────────────────────

$checks = array();

// 1. Plugin file is loaded
$checks[] = array(
    'label'  => 'Plugin bootstrap loaded',
    'pass'   => defined( 'CGA_VERSION' ),
    'detail' => defined( 'CGA_VERSION' ) ? 'CGA_VERSION = ' . CGA_VERSION : 'CGA_VERSION not defined',
);

// 2. front-end.php loaded (check by function existence)
$checks[] = array(
    'label'  => 'Front-end filter file loaded',
    'pass'   => function_exists( 'custom_guest_authors_name' ),
    'detail' => function_exists( 'custom_guest_authors_name' )
        ? 'custom_guest_authors_name() exists'
        : 'Function not found — includes/front-end.php may not be loading',
);

// 3. the_author filter hooked
$hooked_the_author = has_filter( 'the_author', 'custom_guest_authors_name' );
$checks[] = array(
    'label'  => "'the_author' filter registered",
    'pass'   => $hooked_the_author !== false,
    'detail' => $hooked_the_author !== false
        ? 'Registered at priority ' . $hooked_the_author
        : 'NOT registered — add_filter() call may have failed',
);

// 4. get_the_author_display_name filter hooked (dynamic filter fired by get_the_author_meta)
$hooked_meta = has_filter( 'get_the_author_display_name', 'custom_guest_authors_name_meta' );
$checks[] = array(
    'label'  => "'get_the_author_display_name' filter registered",
    'pass'   => $hooked_meta !== false,
    'detail' => $hooked_meta !== false
        ? 'Registered at priority ' . $hooked_meta
        : 'NOT registered — block themes (TT25, TT24) will not be intercepted',
);

// 5. cga_enabled_post_types option
$enabled_types = get_option( 'cga_enabled_post_types', null );
$checks[] = array(
    'label'  => 'Enabled post types option',
    'pass'   => true, // informational
    'info'   => true,
    'detail' => $enabled_types === null
        ? 'Option not yet saved — using default: [post]'
        : 'Saved value: ' . implode( ', ', (array) $enabled_types ),
);

// 6. cga_apply_on option
$apply_on = get_option( 'cga_apply_on', 'all' );
$checks[] = array(
    'label'  => 'Apply override on',
    'pass'   => true,
    'info'   => true,
    'detail' => 'cga_apply_on = "' . esc_html( $apply_on ) . '"',
);

// 7. Default guest author option
$default_author = get_option( 'cga_default_guest_author', '' );
$checks[] = array(
    'label'  => 'Default guest author',
    'pass'   => true,
    'info'   => true,
    'detail' => $default_author
        ? '"' . esc_html( $default_author ) . '"'
        : '(not set)',
);

// 8. Test post — find the most recent published post
$test_post = null;
$recent    = get_posts( array( 'numberposts' => 1, 'post_status' => 'publish' ) );
if ( ! empty( $recent ) ) {
    $test_post = $recent[0];
}

if ( $test_post ) {
    // 9. guest-author meta for the test post
    $meta_value = get_post_meta( $test_post->ID, 'guest-author', true );
    $checks[] = array(
        'label'  => 'guest-author meta on most recent post (ID ' . $test_post->ID . ')',
        'pass'   => ! empty( $meta_value ),
        'detail' => ! empty( $meta_value )
            ? '"' . esc_html( $meta_value ) . '"'
            : '(empty — no guest-author meta saved on this post)',
    );

    // 10. Transient for the test post
    $transient = get_transient( 'cga_' . $test_post->ID );
    if ( false === $transient ) {
        $transient_detail = '(not cached)';
    } elseif ( defined( 'CGA_NO_META' ) && CGA_NO_META === $transient ) {
        $transient_detail = '(cached — no guest-author meta on this post)';
    } else {
        $transient_detail = '"' . esc_html( $transient ) . '" (cached)';
    }
    $checks[] = array(
        'label'  => 'Transient cache for post ' . $test_post->ID,
        'pass'   => true,
        'info'   => true,
        'detail' => $transient_detail,
    );

    // 11. Post type of test post
    $post_type     = get_post_type( $test_post->ID );
    $enabled       = get_option( 'cga_enabled_post_types', array( 'post' ) );
    $type_enabled  = is_array( $enabled ) && in_array( $post_type, $enabled, true );
    $checks[] = array(
        'label'  => 'Post type "' . esc_html( $post_type ) . '" in enabled list',
        'pass'   => $type_enabled,
        'detail' => $type_enabled
            ? 'Yes — plugin will apply override on this post type'
            : 'NO — this post type is not in the enabled list. The override is silently skipped.',
    );

    // 12. custom-fields support for this post type
    $has_cf = post_type_supports( $post_type, 'custom-fields' );
    $checks[] = array(
        'label'  => 'Post type "' . esc_html( $post_type ) . '" supports custom-fields',
        'pass'   => $has_cf,
        'detail' => $has_cf
            ? 'Yes — REST API can read/write post meta'
            : 'NO — REST API will silently discard meta saves from the block editor',
    );

    // 13. Simulate the filter — call custom_guest_authors_name() directly
    // We need to fake $post global to point at our test post
    $saved_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    $GLOBALS['post'] = $test_post;
    $simulated = custom_guest_authors_name( $test_post->post_author
        ? get_userdata( $test_post->post_author )->display_name ?? '(no wp author)'
        : '(no wp author)' );
    $GLOBALS['post'] = $saved_post;
    $wp_author = $test_post->post_author
        ? ( get_userdata( $test_post->post_author ) ? get_userdata( $test_post->post_author )->display_name : '(invalid user)' )
        : '(no author)';
    $filter_changed = $simulated !== $wp_author;
    $checks[] = array(
        'label'  => 'Filter simulation on post ' . $test_post->ID,
        'pass'   => $filter_changed || ! empty( $meta_value ),
        'detail' => 'WP author: "' . esc_html( $wp_author ) . '" → Filter returns: "' . esc_html( $simulated ) . '"'
            . ( $filter_changed ? ' ✓ CHANGED' : ' — unchanged (no guest meta or not applicable)' ),
    );
} else {
    $checks[] = array(
        'label'  => 'Test post lookup',
        'pass'   => false,
        'detail' => 'No published posts found to test against',
    );
}

// 14. Conflicting plugins — other plugins hooking the_author at higher priority
global $wp_filter;
$conflicts = array();
if ( isset( $wp_filter['the_author'] ) ) {
    foreach ( $wp_filter['the_author']->callbacks as $priority => $callbacks ) {
        foreach ( $callbacks as $id => $cb ) {
            $fn = is_array( $cb['function'] )
                ? ( is_object( $cb['function'][0] ) ? get_class( $cb['function'][0] ) : $cb['function'][0] ) . '::' . $cb['function'][1]
                : ( is_string( $cb['function'] ) ? $cb['function'] : '{closure}' );
            if ( $fn !== 'custom_guest_authors_name' ) {
                $conflicts[] = $fn . ' (priority ' . $priority . ')';
            }
        }
    }
}
$checks[] = array(
    'label'  => 'Other plugins hooking the_author',
    'pass'   => true,
    'info'   => true,
    'detail' => empty( $conflicts )
        ? 'None'
        : implode( ', ', $conflicts ) . ' — plugin runs at priority 20, after all listed hooks',
);

?>

<div class="cga-debug-results">

    <p class="cga-debug-intro">
        <?php esc_html_e( 'These diagnostics run live on the server. Green = pass, Red = problem, Blue = informational.', 'custom-guest-authors' ); ?>
    </p>

    <table class="cga-debug-table widefat">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Check', 'custom-guest-authors' ); ?></th>
                <th><?php esc_html_e( 'Result', 'custom-guest-authors' ); ?></th>
                <th><?php esc_html_e( 'Detail', 'custom-guest-authors' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $checks as $check ) :
                $is_info = ! empty( $check['info'] );
                $row_class = $is_info ? 'cga-info' : ( $check['pass'] ? 'cga-pass' : 'cga-fail' );
                $badge     = $is_info ? 'INFO' : ( $check['pass'] ? 'PASS' : 'FAIL' );
            ?>
            <tr class="<?php echo esc_attr( $row_class ); ?>">
                <td><?php echo esc_html( $check['label'] ); ?></td>
                <td><span class="cga-badge cga-badge-<?php echo esc_attr( $row_class ); ?>"><?php echo esc_html( $badge ); ?></span></td>
                <td><code><?php echo wp_kses_post( $check['detail'] ); ?></code></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr>

    <h3><?php esc_html_e( 'Manual test', 'custom-guest-authors' ); ?></h3>
    <p><?php esc_html_e( 'Enter a post ID below to check whether the guest-author meta is saved and what the filter returns for it.', 'custom-guest-authors' ); ?></p>

    <?php
    $manual_id = isset( $_GET['cga_test_id'] ) ? absint( $_GET['cga_test_id'] ) : 0;
    if ( $manual_id ) {
        $manual_post = get_post( $manual_id );
        if ( $manual_post ) {
            $manual_meta = get_post_meta( $manual_id, 'guest-author', true );
            $saved_post  = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
            $GLOBALS['post'] = $manual_post;
            $manual_result   = custom_guest_authors_name( 'TEST_INPUT' );
            $GLOBALS['post'] = $saved_post;
            $manual_type     = get_post_type( $manual_id );
            $manual_enabled  = is_array( get_option( 'cga_enabled_post_types', array( 'post' ) ) )
                && in_array( $manual_type, get_option( 'cga_enabled_post_types', array( 'post' ) ), true );
            ?>
            <div class="cga-manual-result">
                <p><strong>Post ID <?php echo esc_html( $manual_id ); ?></strong> — "<?php echo esc_html( $manual_post->post_title ); ?>"</p>
                <ul>
                    <li><strong>Post type:</strong> <code><?php echo esc_html( $manual_type ); ?></code> — <?php echo $manual_enabled ? '<span style="color:green">enabled</span>' : '<span style="color:red">NOT in enabled list</span>'; ?></li>
                    <li><strong>guest-author meta:</strong> <code><?php echo $manual_meta ? esc_html( $manual_meta ) : '(empty)'; ?></code></li>
                    <li><strong>Filter output:</strong> <code><?php echo esc_html( $manual_result ); ?></code>
                        <?php if ( $manual_result === 'TEST_INPUT' ) : ?>
                            <span style="color:red"> — filter returned unchanged input. Post type not enabled, or no meta and no default.</span>
                        <?php else : ?>
                            <span style="color:green"> — filter IS substituting the name correctly.</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
            <?php
        } else {
            echo '<p style="color:red">Post ID ' . esc_html( $manual_id ) . ' not found.</p>';
        }
    }
    ?>

    <form method="get" action="">
        <?php
        // Preserve existing query args for the settings page URL
        foreach ( $_GET as $key => $val ) {
            if ( $key !== 'cga_test_id' ) {
                echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '">';
            }
        }
        ?>
        <input type="number" name="cga_test_id" value="<?php echo esc_attr( $manual_id ); ?>" placeholder="Post ID" style="width:120px">
        <button type="submit" class="button"><?php esc_html_e( 'Test Post ID', 'custom-guest-authors' ); ?></button>
    </form>

</div>

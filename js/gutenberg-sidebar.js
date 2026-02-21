/**
 * Custom Guest Authors — Gutenberg Sidebar Panel
 *
 * Registers a PluginDocumentSettingPanel that appears in the block editor's
 * Document sidebar, allowing editors to set the guest-author post meta
 * without needing to open the raw custom fields panel.
 *
 * Depends on: wp-plugins, wp-edit-post, wp-components, wp-data, wp-element,
 *             wp-i18n, wp-core-data
 */
( function ( wp ) {
    'use strict';

    var el               = wp.element.createElement;
    var __               = wp.i18n.__;
    var registerPlugin   = wp.plugins.registerPlugin;
    // wp.editor.PluginDocumentSettingPanel is the canonical location from WP 6.6+.
    // wp.editPost still re-exports it for backwards compatibility; prefer wp.editor
    // when available to avoid deprecation console notices on WP 6.6+.
    var PluginDocumentSettingPanel = ( wp.editor && wp.editor.PluginDocumentSettingPanel )
        ? wp.editor.PluginDocumentSettingPanel
        : wp.editPost.PluginDocumentSettingPanel;
    var TextControl      = wp.components.TextControl;
    var useSelect        = wp.data.useSelect;
    var useEntityProp    = wp.coreData ? wp.coreData.useEntityProp : null;

    // useEntityProp is available from WP 5.7+ via @wordpress/core-data.
    // Gracefully degrade if unavailable.
    if ( ! useEntityProp ) {
        return;
    }

    function CgaSidebarPanel() {
        var postType = useSelect( function ( select ) {
            return select( 'core/editor' ).getCurrentPostType();
        } );

        // useEntityProp must be called unconditionally (React hooks rule), but we
        // pass an empty string when postType is not yet resolved so the hook reads
        // a stable no-op entity rather than the wrong type. The null guard below
        // ensures nothing is rendered until the real postType is available.
        var _useEntityProp   = useEntityProp( 'postType', postType || '', 'meta' );
        var meta             = _useEntityProp[ 0 ];
        var setMeta          = _useEntityProp[ 1 ];

        // postType not yet resolved — render nothing to avoid flicker or errors.
        if ( ! postType ) {
            return null;
        }

        var guestAuthor = meta ? ( meta[ 'guest-author' ] || '' ) : '';

        function onChange( value ) {
            setMeta( { 'guest-author': value } );
        }

        return el(
            PluginDocumentSettingPanel,
            {
                name:  'cga-guest-authors-panel',
                title: __( 'Guest Authors', 'custom-guest-authors' ),
                className: 'cga-sidebar-panel',
            },
            el( TextControl, {
                label:       __( 'Guest Author Name(s)', 'custom-guest-authors' ),
                value:       guestAuthor,
                onChange:    onChange,
                placeholder: __( 'e.g. John Doe, Jane Smith', 'custom-guest-authors' ),
                help:        __( 'Separate multiple authors with commas. Leave blank to use the post\'s WordPress author.', 'custom-guest-authors' ),
            } )
        );
    }

    registerPlugin( 'cga-guest-authors', {
        render: CgaSidebarPanel,
        icon:   'admin-users',
    } );

} )( window.wp );

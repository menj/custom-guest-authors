=== Custom Guest Authors ===
Contributors: MENJ
Donate link: https://paypal.me/menj
Tags: guest, author, post, contributor
Requires at least: 5.7
Tested up to: 6.7
Requires PHP: 7.0
Stable tag: 1.8.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Replace the default post author name with custom guest author names using a custom field. Supports multiple authors. Inspired by a tutorial from WPBeginner.

== Description ==

The Custom Guest Authors plugin allows setting custom guest author names or multiple guest author names for posts using a custom field named `guest-author`. For multiple authors, separate the names with commas. Ideal for blog owners with multiple guest contributors who don't want separate WordPress user accounts for each.

From version 1.6, guest authors can be set directly from the post editor — no need to use the raw custom fields panel. A dedicated sidebar panel is available in both the classic editor and the block editor (Gutenberg).

This plugin was inspired by a tutorial from [WPBeginner](https://www.wpbeginner.com/wp-tutorials/how-to-rewrite-guest-author-name-with-custom-fields-in-wordpress/).

== Installation ==

1. Upload the `custom-guest-authors` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit any post or page — a "Guest Authors" panel will appear in the sidebar.

== Frequently Asked Questions ==

= Can I use this for custom post types? =

Not currently. Support for custom post types is planned for a future release.

= How do I set custom guest authors for a post? =

Edit a post — a "Guest Authors" panel appears in the right-hand sidebar of both the classic and block editors. Enter one name or multiple names separated by commas, e.g. "John Doe, Jane Smith".

= Can I still use the raw custom field directly? =

Yes. The field key is `guest-author`. Setting it via the custom fields panel or programmatically works as before.

= I updated a guest author name but the old name is still showing. Why? =

This was a bug in versions prior to 1.5 where the cached author name would persist for up to 12 hours. Version 1.5 fixed this — the cache is now invalidated immediately whenever a post is saved or its meta is updated.

== Changelog ==

= 1.8.0 =
* New: General tab — Post type selection. A checkbox grid of all public post types replaces the single "Override on Pages" toggle. The cga_override_on_pages option is superseded by cga_enabled_post_types (array). Posts enabled by default; all other types opt-in.
* New: Display tab — Multi-Author Join Style. Radio card selector: Natural language (A, B and C), Comma only (A, B, C), Ampersand (A & B & C). Stored as cga_join_style.
* New: Display tab — Show Override On. Radio card selector to restrict the override to singular post views only or apply on all views including archive/home loops. Stored as cga_apply_on.
* New: Advanced tab — Cache Lifetime. Configurable transient TTL in hours (default 12, range 1–168). Previously hardcoded.
* New: Advanced tab — Suppress author from JSON-LD schema. Toggle that removes the author property from Article schema. Compatible with Yoast SEO and Rank Math.
* New: Advanced tab — Debug Information. Read-only table showing plugin version, active post types, join style, cache TTL, cached transient count, WordPress version, and PHP version.
* Improved: Settings page CSS updated with checkbox grid, radio card, and debug table component styles.
* Improved: cga_register_settings() expanded with sanitize callbacks for all new options.

* New: General tab — Post type selection. A checkbox grid of all public post types replaces the single "Override on Pages" toggle. The cga_override_on_pages option is superseded by cga_enabled_post_types (array). Posts is enabled by default; all other types are opt-in.
* New: Display tab — Multi-Author Join Style. Radio card selector with three options: Natural language (A, B and C), Comma only (A, B, C), and Ampersand (A & B & C). Stored as cga_join_style.
* New: Display tab — Show Override On. Radio card selector to restrict the override to singular post views only (not archive/home loops) or apply everywhere. Stored as cga_apply_on.
* New: Display tab — Preview updated to show Malay example names and reflect the current join style live.
* New: Advanced tab — Cache Lifetime. Configurable transient TTL in hours (default 12, range 1–168). Previously hardcoded.
* New: Advanced tab — Suppress author from JSON-LD schema. Toggle that removes the author property from Article schema. Compatible with Yoast SEO (wpseo_schema_graph) and Rank Math (rank_math/schema/article).
* New: Advanced tab — Debug Information. Read-only table showing plugin version, active post types, join style, cache TTL, cached transient count, WordPress version, and PHP version.
* Improved: cga_register_settings() expanded with sanitize callbacks for all new options including a dedicated cga_sanitize_post_types() function that validates against get_post_types().
* Improved: settings.css expanded with checkbox grid, radio card, and debug table component styles.

= 1.7.5 =
* Removed: Author name prefix feature removed entirely. The theme already outputs its own "Written by" or equivalent label, making the plugin prefix redundant and producing doubled output (e.g. "Written by Oleh John Doe").
* Fixed: Author name is now output as plain unlinked text. Guest authors are not WordPress users and have no author archive, so the hyperlink generated by the_author_posts_link() was meaningless. A new filter on the_author_posts_link() suppresses the anchor and returns the plain name instead.
* Updated: POT and ms_MY translation files updated to remove prefix-related strings and recompiled.

= 1.7.4 =
* New: Added Malay (Malaysia) translation — ms_MY.po and ms_MY.mo included in /languages/. The "and" conjunction correctly outputs as "dan" when WordPress is set to ms_MY locale.
* New: Added POT template file (custom-guest-authors.pot) covering all translatable strings in the plugin.
* Fixed: load_plugin_textdomain() was never called despite the Text Domain header being declared. All __() calls were silently falling back to English regardless of the site locale. The text domain is now properly loaded on init.

= 1.7.3 =
* Fixed: Prefix was concatenated directly against the author name with no space, producing output like "ByJohn Doe". The prefix is now rtrim'd and a single space is always inserted between it and the first author name.
* Improved: Multi-author output now uses smart natural-language joining. Two authors produce "Guest A and Guest B"; three or more produce "Guest A, Guest B and Guest C". The configurable separator field has been removed as it is superseded by this logic.
* Removed: cga_separator option and its settings field — no longer needed given the smart join behaviour.
* Improved: Display tab preview updated to show both the 2-author and 3-author formats with the current prefix applied.

= 1.7.2 =
* Improved: Settings page completely redesigned to match the established plugin suite design language — wpcp- CSS classes, slate color palette, CSS custom properties, page header with SVG icon and version badge.
* Improved: Tab navigation converted from JavaScript button-based switching to URL ?tab= parameter links, consistent with the suite pattern and requiring no client-side JS.
* Improved: Boolean toggle converted from a plain checkbox to a proper toggle switch component matching the suite style.
* Improved: Form now posts to options.php via the Settings API (settings_fields) instead of self-posting, with a wp_redirect filter to restore the active tab after save.
* Improved: All dashicons removed from the settings page and replaced with inline SVG icons.
* Removed: settings.js no longer needed and removed from the plugin.
* Improved: meta-box.css and gutenberg-sidebar.css updated to use the same CSS variables and class conventions as the suite.

= 1.7.1 =
* Fixed: cga_override_on_pages option was registered and displayed in the UI but never read in the front-end filter — pages were always overridden regardless of the checkbox state. The filter now correctly bails on pages when the option is disabled.
* Fixed: The hidden tab input always output 'cga-tab-general' on fresh page loads, overriding sessionStorage tab memory and forcing the settings page to always open on the General tab. The input is now empty on GET requests and only populated after a successful POST save.
* Fixed: Removed dead register_setting() calls whose sanitize_callbacks were never invoked since the form posts to self rather than options.php. Manual sanitization in the save handler is now the sole authoritative path.

= 1.7 =
* New: Settings page at Settings > Guest Authors with tabbed interface (General and Display tabs).
* New: General tab — configurable default guest author name shown when no per-post guest author is set.
* New: General tab — option to enable guest author overrides on Pages (disabled by default, Posts only).
* New: Display tab — configurable multi-author separator (default ", "; examples: " & ", " / ").
* New: Display tab — optional author name prefix (e.g. "By ") prepended to all guest author output.
* New: Display tab — live preview card showing example output with current separator and prefix settings.
* New: Settings page assets (css/settings.css, js/settings.js) properly separated and enqueued only on the settings page hook.
* New: Tab state persisted in sessionStorage so the last active tab is remembered within the admin session.
* Improved: Separator and prefix options wired into the front-end author name filter.

= 1.6.2 =
* Fixed: Classic editor meta box was appearing twice in the block editor — once as the dedicated Gutenberg sidebar panel and again as a WordPress compatibility meta box. The classic meta box is now suppressed when the block editor is active for the post type.
* Fixed: Removed unused useDispatch declaration in gutenberg-sidebar.js (dead code).
* Fixed: PluginDocumentSettingPanel now resolves from wp.editor (canonical WP 6.6+ location) with a fallback to wp.editPost, eliminating deprecation notices in the browser console on WP 6.6+.
* Improved: Added wp-editor to Gutenberg sidebar script dependencies to support the wp.editor fallback.

= 1.6.1 =
* Fixed: JavaScript crash in Gutenberg sidebar on initial render when post type is not yet resolved by the block editor store.
* Fixed: use_block_editor_for_post_type() deprecated since WP 6.5 — replaced with wp_use_block_editor_for_post_type() with a backwards-compatible shim for older WP versions.
* Fixed: Potential PHP warning on post-new.php when get_current_screen()->post_type returns an empty string before the post type is determined.

= 1.6 =
* New: Dedicated "Guest Authors" sidebar panel in the classic editor (meta box).
* New: Dedicated "Guest Authors" sidebar panel in the block editor (Gutenberg PluginDocumentSettingPanel).
* New: Post meta registered via register_post_meta() with REST API exposure, enabling full Gutenberg read/write support.
* New: CSS and JS assets separated into /css/ and /js/ directories and properly enqueued via WordPress APIs.
* New: Minimalist slate-toned styling for the meta box and Gutenberg panel, consistent with the plugin suite design language.
* New: Plugin constants defined (CGA_VERSION, CGA_PLUGIN_DIR, CGA_PLUGIN_URL) for cleaner asset enqueuing.
* Improved: Classic editor assets only load on post edit screens and only when the block editor is not active, preventing unnecessary asset loading.
* Requires at least: WordPress 5.7 (useEntityProp introduced in core-data).

= 1.5 =
* Fixed: Transient cache is now invalidated immediately on post save, preventing stale guest author names from displaying after an update.
* Fixed: Cache invalidation also fires on direct post meta updates (e.g. via REST API or programmatic writes) that bypass save_post.
* Improved: Transient key shortened and namespaced to cga_{post_id} for consistency.
* Improved: Default guest author option key namespaced to cga_default_guest_author to prevent collision with other plugins.
* Improved: Added ABSPATH exit guard as standard security practice.
* Improved: Empty author entries are now filtered out from comma-separated lists.

= 1.2 =
* Support for multiple guest authors separated by commas.

= 1.1 =
* Transient caching for performance.
* Security measures: input sanitization and output escaping.

= 1.0 =
* Initial release.

== Future Plans for Enhancement ==

1. Support for custom post types — a settings page option to select which post types the plugin operates on.
2. Author avatar and bio display — a shortcode or block to render a guest author byline card beneath post content.
3. Gutenberg block — a dedicated Guest Author Byline block for manual placement in the editor.
4. Suite integration — grouping under a shared admin menu with other plugins in the suite, and coordination with Endmark to avoid typographic conflicts.

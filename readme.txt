=== Custom Guest Authors ===
Contributors: MENJ
Donate link: https://paypal.me/menj
Tags: guest author, multiple authors, author override, byline, custom fields
Requires at least: 5.7
Tested up to: 6.9
Requires PHP: 8.2
Stable tag: 2.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Replace the default WordPress post author with custom guest author names via a custom field. Supports multiple comma-separated authors.

== Description ==

The Custom Guest Authors plugin (developed and maintained by [Mohd Elfie Nieshaem Juferi](https://ms.wikipedia.org/wiki/Mohd_Elfie_Nieshaem_Juferi)) allows you to set custom guest author names or multiple guest authors for posts using a custom field key named `guest-author`. For multiple authors, separate the names with commas. Ideal for sites with multiple contributors where separate WordPress user accounts are unnecessary.

From version 1.6 onward, guest authors can be set directly from the post editor — no need to use the raw custom fields panel. A dedicated sidebar panel is available in both the classic editor and the block editor (Gutenberg).

This plugin was inspired by a tutorial from [WPBeginner](https://www.wpbeginner.com/wp-tutorials/how-to-rewrite-guest-author-name-with-custom-fields-in-wordpress/).

== Installation ==

1. Upload the `custom-guest-authors` directory to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit any post or page — a "Guest Authors" panel will appear in the sidebar.

== Frequently Asked Questions ==

= Can I use this for custom post types? =

Yes. Go to Settings › Guest Authors › General tab and check any public post types you want the override to apply to.

= How do I set custom guest authors for a post? =

Edit a post — a "Guest Authors" panel appears in the right-hand sidebar of both the classic and block editors. Enter one name or multiple names separated by commas, e.g. "John Doe, Jane Smith".

= Can I still use the raw custom field directly? =

Yes. The field key is `guest-author`. Setting it via the custom fields panel or programmatically works as before.

= I updated a guest author name but the old name is still showing. Why? =

This was a bug in versions prior to 1.5 where the cached author name would persist for up to 12 hours. Version 1.5 fixed this — the cache is now invalidated immediately whenever a post is saved or its meta is updated.

== Future Updates ==

The following features are planned for future versions.

= Suite Menu =
All MENJ suite plugins (Auto Justify Content, Cite, Endmark, Custom Guest Authors) will be grouped under a shared "MENJ Plugins" top-level admin menu instead of individual Settings entries.

= Guest Author Byline Card =
A shortcode `[guest_author_card]` and optional auto-append toggle to render a styled byline card beneath post content, showing guest author name(s) and an optional per-post bio (stored as a second meta field `guest-author-bio`). Bio field will be available in both the classic editor meta box and the Gutenberg sidebar panel.

= Guest Author Byline Block =
A dedicated Gutenberg block (`custom-guest-authors/byline`) that renders the byline card inline in the editor with live preview via `useEntityProp`. Depends on the Byline Card feature.

= Per-Post Author Link Override =
An optional `guest-author-url` meta field per post to set a custom destination link for the author name (e.g. the guest's personal site or social profile), instead of always suppressing the link.

= REST API Autocomplete Endpoint =
A `GET /wp-json/cga/v1/authors` endpoint returning recently used guest author names, powering an autocomplete suggestions field in the Gutenberg sidebar.

= Meta Key Migration Tool =
A one-click tool on the Debug tab to migrate post meta from an existing custom field key into `guest-author`, with a dry-run count before committing. Useful when transitioning from another plugin or an ACF field with a different key name.

= RSS Feed Toggle =
A toggle on the Display tab to control whether the guest author override applies inside RSS/Atom feeds independently from on-site display.

== Changelog ==

= 2.1.0 =
* Changed: Settings page completely redesigned with a dark hero header (Endmark-style), underline tab navigation flush to the header, plain white card bodies with uppercase muted section labels (replacing gradient card headers), AJC-style toggle switch for schema suppression, AJC-style Save Settings button, and a MENJ footer link pointing to https://github.com/menj. Original colour palette retained throughout.

= 2.0.9 =
* Added: `CGA_NO_META` constant replaces the `'__cga_none__'` sentinel string literal that was repeated inline in three functions.
* Added: `cga_get_authors()` shared helper encapsulates the full transient cache read/write path. Eliminates ~20 lines of duplicated cache logic previously copied across three functions.
* Added: `cga_format_authors()` shared helper encapsulates the multi-author join pipeline. Eliminates the formatting block previously duplicated across two functions.
* Fixed: `custom_guest_authors_name_meta()` (block theme path) did not respect the "Show Override On" setting. Per-post guest author names were appearing on archive pages even when set to Singular only. Both filter callbacks now apply an identical context gate.
* Fixed: `custom_guest_authors_strip_link()` was calling `get_post_meta()` directly, bypassing the transient cache. Now uses `cga_get_authors()`.
* Fixed: `useEntityProp` in `gutenberg-sidebar.js` used `postType || 'post'` as fallback on initial render. On Pages and custom post types this transiently read meta from the wrong entity. Changed to `postType || ''`.
* Fixed: Version-based cache flush called `update_option()` after the `DELETE` query. A write failure would cause the flush to re-run on every request. Order reversed — version recorded first.
* Fixed: Duplicate docblock before `cga_render_settings_page()` in `admin.php` removed.
* Fixed: Debug tab transient display now shows a human-readable label instead of the raw `__cga_none__` sentinel string.

= 2.0.8 =
* Added: Live diagnostics panel on the Debug tab. Checks filter hook registration, guest-author meta presence, post type enablement, custom-fields support, and runs a filter simulation on a real post. Includes a manual post-ID tester.
* Fixed: Root cause of author name never appearing on block themes (TT25, TT24, all FSE themes). `get_the_author_meta()` fires a dynamic filter — `get_the_author_{$field}` — so the correct hook name for display_name is `get_the_author_display_name`. The plugin had been registering a non-existent `get_the_author_meta` filter since v2.0.5; the callback was never called on any block theme frontend.
* Fixed: Stale empty-string transient cache permanently blocking the default guest author. The cache sentinel is now `__cga_none__` so a cached "no meta" result no longer prevents the default from showing.
* Fixed: Automatic cache flush on plugin update purges stale transients from older installs without requiring a manual Clear Cache.
* Fixed: Default guest author not appearing on archive and listing pages. The `apply_on = "singular"` context gate was placed before the default author fallback and caused early exit on non-singular pages. The gate now only applies to per-post meta overrides.
* Fixed: Filter hooks raised to priority 20 to run after `ent2ncr` (registered at priority 8 by another plugin on `the_author`).
* Fixed: Post resolution hardened in all front-end filter functions using `get_post()` + `get_queried_object()` fallback, covering singular page templates before `setup_postdata()` runs, nested queries, and page-builder contexts.

= 2.0.4 =
* Fixed: Guest author names saved via the Gutenberg sidebar panel were silently discarded. WordPress only writes post meta via the REST API for post types that declare `custom-fields` support. The plugin now calls `add_post_type_support()` for all enabled post types on `init`.

= 2.0.2 =
* Changed: Plugin architecture refactored from a 1,175-line monolith. 901 lines of admin-only code were parsed on every front-end request. Code is now split into conditional includes: always-loaded front-end filters, cache, and meta registration; admin-only meta box and settings page.
* Fixed: Author filter was returning `esc_html($name)` — HTML-escaped text — instead of a raw plain-text value. Author names containing `&`, `<`, `>`, or `"` displayed as literal HTML entities on screen.
* Fixed: `Domain Path: /languages` added to plugin file header.

= 2.0.1 =
* Fixed: Fatal PHP parse error on activation — plugin could not be activated at all.
* Fixed: `load_plugin_textdomain()` was absent; bundled Malay translation never loaded.
* Fixed: `$_GET['cga_action']` accessed without `wp_unslash()` or `sanitize_key()`.
* Fixed: `Requires PHP` corrected from 8.5 to 8.2.

= 2.0.0 =
* Improved: Settings UI completely redesigned with pill-style tab navigation, navy-to-teal gradient card headers, and stone accent divider.
* Added: Debug tab — promoted from the Advanced tab, with System Information and Cache Status cards and a Clear Cache button.

= 1.9.1 =
* Added: `phpcs.xml` declaring authorised plugin prefixes; all `cga_*` functions annotated with inline `phpcs:ignore` for Plugin Check compatibility.
* Updated: `Requires PHP` bumped to 8.2. `Tested up to` confirmed at 6.9.

= 1.8.9 =
* Fixed: Radio and checkbox cards showed no visual feedback on click; interactive state now updated via delegated JS listener.
* Fixed: Join style preview was static; now updates live on radio card change.
* Fixed: Active settings tab lost after save due to `wp_safe_redirect` bypassing the redirect filter. Callback now registered on both `wp_redirect` and `wp_safe_redirect`.
* Fixed: `custom_guest_authors_suppress_url()` bypassed the transient cache, causing one extra DB query per post on archive pages. Now reads from the `cga_{post_id}` transient first.
* Added: `js/settings.js` for interactive card state and live join-style preview.

= 1.8.1 =
* Fixed: Classic meta box hardcoded to `post` and `page`; now reads `cga_enabled_post_types`.
* Fixed: `cga_suppress_schema` toggle not saving correctly for unchecked state.
* Fixed: `cga_enabled_post_types` could not be saved as empty array.

= 1.8.0 =
* Added: Post type selection (checkbox grid replacing single "Override on Pages" toggle).
* Added: Multi-Author Join Style — Natural, Comma, Ampersand.
* Added: Show Override On — singular views only or all views.
* Added: Configurable cache lifetime (1–168 hours, default 12).
* Added: Suppress author from JSON-LD schema (Yoast SEO and Rank Math).
* Added: Debug Information table on the Advanced tab.

= 1.7.5 =
* Added: Malay (Malaysia) translation (`ms_MY`). POT template file.
* Removed: Author name prefix feature (produced doubled output with theme labels).
* Fixed: Author name now output as plain unlinked text (guest authors have no author archive).

= 1.7.2 =
* Improved: Settings page redesigned to match plugin suite design language.

= 1.7.1 =
* Added: Settings page with General and Display tabs, default guest author, live preview.

= 1.6.2 =
* Fixed: Classic meta box appeared twice in block editor. Gutenberg JS crash on initial render. Deprecated `use_block_editor_for_post_type()` replaced.

= 1.6 =
* Added: Classic editor meta box and Gutenberg sidebar panel. REST API meta registration. CSS/JS assets separated into `/css/` and `/js/`.

= 1.5 =
* Fixed: Transient cache invalidated on post save and on programmatic meta updates.

= 1.2 =
* Added: Support for multiple comma-separated guest authors.

= 1.1 =
* Added: Transient caching, input sanitization, output escaping.

= 1.0 =
* Initial release.

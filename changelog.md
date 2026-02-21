# Custom Guest Authors — Changelog

All notable changes to this plugin are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## Unreleased

### Suite Integration
```yaml
description: >
  Group all suite plugins (Auto Justify Content, Cite, Endmark, Custom Guest
  Authors) under a shared top-level admin menu rather than individual entries
  under Settings, and coordinate with Endmark to avoid typographic conflicts
  between the end mark and a guest author byline card.

implementation:
  shared_admin_menu:
    top_level_slug: menj-plugin-suite
    top_level_label: "MENJ Plugins"
    top_level_icon: dashicons-admin-plugins
    description: >
      Each suite plugin registers its settings as a submenu page under the
      shared top-level menu. Only one plugin should register the top-level
      menu; others attach as submenus. Use a shared constant
      (e.g. MENJ_SUITE_MENU_REGISTERED) to prevent duplicate entries.
    migration: >
      The existing Settings > Guest Authors entry is replaced by
      MENJ Plugins > Guest Authors. A Settings redirect shim should be
      added for one version to avoid broken bookmarks for existing users.

  endmark_coordination:
    description: >
      When Endmark is active and the Guest Author Byline Card is appended
      after post content, the end mark may render inside or before the card
      rather than at the end of the article body. Apply the byline card
      after Endmark has already processed the_content to ensure correct
      render order.
    detection: function_exists() check on a known Endmark function or constant

files_affected:
  - custom-guest-authors.php
  - readme.txt
  - changelog.md
```

---

### Guest Author Byline Card
```yaml
description: >
  A shortcode and optional auto-append setting to render a styled byline
  card beneath post content displaying the guest author name(s) and an
  optional per-post bio line. Bio text is stored as a second per-post
  custom field; no full author profile system is introduced.

implementation:
  new_meta_field: guest-author-bio
  field_label: Guest Author Bio
  field_type: textarea
  storage: post meta via update_post_meta()

  shortcode:
    tag: '[guest_author_card]'
    output: >
      Renders a styled byline card using the guest-author and
      guest-author-bio meta fields for the current post. Falls back
      gracefully when either field is absent.

  auto_append:
    option_key: cga_auto_append_card
    settings_tab: Display
    description: >
      Optional toggle on the Display tab to automatically append the byline
      card after post content via the_content filter, without requiring
      manual shortcode placement.

editor_ui:
  classic: >
    Extend the existing classic editor meta box to include a textarea field
    for the guest author bio beneath the existing name input.
  gutenberg: >
    Extend the existing Gutenberg sidebar panel to include a TextareaControl
    for the bio field.

new_files:
  - css/byline-card.css

files_affected:
  - custom-guest-authors.php
  - css/meta-box.css
  - css/gutenberg-sidebar.css
  - js/gutenberg-sidebar.js
  - readme.txt
  - changelog.md
```

---

### Guest Author Byline Block
```yaml
depends_on: Guest Author Byline Card

description: >
  A dedicated Gutenberg block that renders the guest author byline card
  inline within the block editor, complementing the shortcode. Server-side
  rendered with live preview in the editor canvas via useEntityProp.

implementation:
  block_name: custom-guest-authors/byline
  block_category: text
  attributes:
    - post_id: (auto-resolved from current post context)
    - show_bio: boolean, default true
  render: >
    register_block_type() with a render_callback. Outputs the same HTML
    as the byline card shortcode for consistent front-end output.
  editor_preview: >
    Uses useEntityProp to read guest-author and guest-author-bio meta
    live in the editor without requiring a page reload.

new_files:
  - js/byline-block.js
  - css/byline-block.css

files_affected:
  - custom-guest-authors.php
  - readme.txt
  - changelog.md
```

---

## [2.0.9] — 2026-02-21

### Added
- `CGA_NO_META` constant defined in `front-end.php` — replaces the `'__cga_none__'` string literal that was repeated inline in three separate functions. A single definition ensures a typo cannot cause a silent sentinel mismatch.
- `cga_get_authors( $post_id )` — shared helper that encapsulates the full transient cache read/write path (cache miss → DB read → prime with `CGA_NO_META`; cache hit sentinel → `''`; cache hit value → value). Eliminates ~20 lines of duplicated cache logic previously copied across `custom_guest_authors_name()`, `custom_guest_authors_name_meta()`, and `custom_guest_authors_suppress_url()`.
- `cga_format_authors( $raw )` — shared helper that encapsulates the explode/trim/sanitize/join pipeline. Eliminates the multi-author formatting block previously duplicated across `custom_guest_authors_name()` and `custom_guest_authors_name_meta()`.

### Fixed
- **`custom_guest_authors_name_meta()` did not respect the "Show Override On" (`apply_on`) setting.** When set to *Singular only*, the `the_author` filter correctly suppressed per-post overrides on archive and listing pages, but the `get_the_author_display_name` filter (used by all block themes) had no such gate. On block themes, per-post guest author names were still appearing on category/tag/date archive pages even when the setting was set to singular. Both filter callbacks now apply an identical `$block_on_ctx` gate.
- **`custom_guest_authors_strip_link()` was calling `get_post_meta()` directly**, bypassing the transient cache. On archive pages this added one uncached DB query per post per render. The function now calls `cga_get_authors()` for a cached read.
- **`useEntityProp` in `gutenberg-sidebar.js` used `postType || 'post'` as the entity type fallback.** On the initial render pass before the block editor store is hydrated, `postType` is `undefined`, causing the hook to read meta from the `post` entity. On a Page or custom post type this would transiently display stale meta from a different entity until `postType` resolved. Changed to `postType || ''` so the hook reads a stable no-op entity on the unresolved pass rather than the wrong type.
- **Version-based cache flush in `cache.php` called `update_option()` after the `DELETE` query.** If the `DELETE` succeeded but `update_option()` then failed (e.g. a transient DB write error), the version would not be recorded and the flush query would re-run on every subsequent request. The order is now reversed: `update_option()` is called first, then the `DELETE`. A partial flush is acceptable — the next `save_post` will re-prime affected transients correctly.
- **Duplicate docblock** before `cga_render_settings_page()` in `admin/admin.php` removed (dead stub left over from an earlier refactor).
- **Transient display in the Debug tab** now shows a human-readable label — *(cached — no guest-author meta on this post)* — instead of the raw `__cga_none__` sentinel string when a post has a confirmed no-meta cache entry.

---

## [2.0.8] — 2026-02-21

### Added
- **Live diagnostics panel on the Debug tab.** Runs checks directly on the server: whether filter hooks are registered, whether the `guest-author` meta is saved for a given post, whether the post type is in the enabled list, whether `custom-fields` support is declared, a filter simulation showing what the function actually returns for a real post, and a manual post-ID tester. Go to **Settings → Custom Guest Authors → Debug** to run the checks.

### Fixed
- **Root cause of author name never appearing on block themes (TT25, TT24, and all FSE themes).** `get_the_author_meta()` fires a *dynamic* filter — `apply_filters( "get_the_author_{$field}", ... )` — so for `display_name` the correct filter name is `get_the_author_display_name`. The plugin had been hooking a non-existent `get_the_author_meta` filter since v2.0.5; the callback was never called. Block themes render the author via the `core/post-author` and `core/post-author-name` blocks, which call `get_the_author_meta( 'display_name', $author_id )` directly. Correcting the hook name to `get_the_author_display_name` fixes author name substitution on all block theme frontends.
- **Stale empty-string transient cache permanently blocking the default guest author.** Previous versions stored `""` as the cache value for posts with no guest-author meta. Because `get_transient()` returns `false` only on a true cache miss, `""` was treated as a valid hit — the DB read was skipped and the default author fallback was never reached. The cache sentinel is now `'__cga_none__'`, a distinct value meaning "checked DB, no meta found", so the default author correctly applies for posts without per-post meta.
- **Automatic cache flush on plugin update.** On first load after a version change, all `cga_` transients are purged from the database, clearing any stale `""` entries from older installs without requiring a manual Clear Cache.
- **Default guest author not appearing on archive and listing pages.** The `apply_on = "singular"` context check was placed before the default author fallback, causing the function to exit early on non-singular pages before ever reaching the default. The gate now only applies to per-post meta overrides; the site-wide default is applied on all pages regardless of context.
- **Filter hooks raised to priority 20** to run after `ent2ncr`, which another plugin had registered at priority 8 on `the_author`, potentially transforming output before the substitution could take effect.
- **`$post` global resolution hardened.** All three front-end filter functions now use `get_post()` first, then `get_queried_object()` as a fallback, covering singular page templates where `setup_postdata()` has not yet been called, nested queries, and page-builder rendering contexts.

---

## [2.0.4] — 2026-02-21

### Fixed
- **Guest author names saved via the Gutenberg sidebar panel were silently discarded.** WordPress only writes post meta via the REST API for post types that declare `'custom-fields'` in their `supports` array. `register_post_meta()` with `show_in_rest => true` is necessary but not sufficient — without the support flag, `useEntityProp()` reads and writes correctly in the editor's local state, but on save the REST endpoint's `update_post_meta_fields()` silently skips writing and returns no error to the client. The plugin now calls `add_post_type_support( $post_type, 'custom-fields' )` on `init` (priority 9) for every post type in the enabled list.

---

## [2.0.2] — 2026-02-20

### Changed
- **Plugin architecture refactored from a 1,175-line monolith.** 901 lines of admin-only code were being parsed on every front-end request. The plugin is now split into purpose-built includes loaded conditionally:
  - `custom-guest-authors.php` — bootstrap only: constants, i18n, loader (~51 lines)
  - `includes/front-end.php` — author name filter, URL suppression, schema suppression (always loaded)
  - `includes/cache.php` — transient invalidation hooks (always loaded)
  - `includes/post-meta.php` — REST API / Gutenberg meta registration (always loaded)
  - `admin/admin.php` — classic meta box, asset enqueuing, settings hooks (admin-only)
  - `admin/views/settings-page.php` — settings page HTML template (admin-only, loaded on demand)

### Fixed
- `return esc_html( $name )` in the author filter returned HTML-escaped text instead of a plain-text value. WordPress filter contracts require data filters to return raw unescaped strings; HTML escaping is the theme's responsibility at output. Author names containing `&`, `<`, `>`, `'`, or `"` displayed as literal HTML entities on screen. Removed the erroneous `esc_html()` wrapper; values are already sanitized via `sanitize_text_field()`.
- `Domain Path: /languages` added to the plugin file header for correctness and Plugin Check compliance.

---

## [2.0.1] — 2026-02-20

### Fixed
- Fatal PHP parse error on activation caused by a rogue backslash before `$_GET` on the settings page. The plugin could not be activated at all.
- `load_plugin_textdomain()` was absent — the bundled Malay `.mo` file and all future translations would never load. Hook restored on `init`.
- `$_GET['cga_action']` in the Clear Cache handler was accessed without `wp_unslash()` or `sanitize_key()`. Now uses `sanitize_key( wp_unslash( ... ) )` with correct `phpcs:ignore` annotations.
- Clear Cache success echo missing a `phpcs:ignore WordPress.Security.EscapeOutput` annotation.
- Duplicate `global $wpdb` declaration in the Debug tab handler consolidated.

### Updated
- `Requires PHP` corrected from `8.5` to `8.2`.
- `testVersion` in `phpcs.xml` corrected from `8.5-` to `8.2-`.

---

## [2.0.0] — 2026-02-20

### Improved
- Settings UI completely redesigned with filled pill-style tab navigation matching the Endmark/Cite family aesthetic.
- All card headers now use a vivid navy-to-teal gradient (`#1B3C53` → `#2E6A8E`), replacing the previous flat light-grey headers.
- A warm stone accent bar (`#C8BAB0`) appears as a decorative divider beneath the page header.
- Tab active state uses a filled navy pill (background `#1B3C53`, white text) rather than an underline indicator.
- Debug tab link uses a stone tint when inactive, distinguishing it visually from the three settings tabs.

### Added
- **Debug tab** — Debug Information promoted from the bottom of the Advanced tab to its own dedicated fourth tab with a darker card header variant.
- **System Information card** — shows plugin, WordPress, and PHP versions with colour-coded status pills (teal = OK, stone = warn).
- **Cache Status card** — shows cache TTL and entry count with status pill, and a **Clear Cache** button wired to a nonce-verified GET action directly in the card header.

### Fixed
- Submit button no longer appears on the Debug tab (it has no saveable settings).

---

## [1.9.1] — 2026-02-20

### Added
- `phpcs.xml` added to the plugin root, declaring `cga_` and `custom_guest_authors_` as authorised prefixes for `WordPress.NamingConventions.PrefixAllGlobals`. Also pins `minimum_wp_version` to `5.7` and `testVersion` to `8.2-`.

### Fixed
- `WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound` warnings persisted in Plugin Check — it derives the expected prefix from the plugin slug and does not read `phpcs.xml`. All 15 `cga_*` function declarations now carry inline `// phpcs:ignore` annotations. The `phpcs.xml` declaration is retained for local PHPCS runs.

### Updated
- `Requires PHP` bumped from `7.0` to `8.2`.
- `Tested up to` confirmed at `6.9`.

---

## [1.8.9] — 2026-02-20

### Fixed
- Radio cards and checkbox cards on the settings page showed no visual feedback when clicked — `.selected` and `.wpcp-checkbox-card--checked` CSS classes were only applied server-side and did not update on interaction. A delegated `change` listener now keeps card visual state in sync at all times.
- Join style preview on the Display tab was static. Switching between Natural, Comma, and Ampersand radio cards now updates both preview outputs immediately without a page save.
- Active settings tab was not reliably preserved after saving. `options.php` uses `wp_safe_redirect()` internally; the existing `cga_settings_redirect` filter only hooked `wp_redirect`, so the tab parameter was silently dropped. The callback is now also registered on `wp_safe_redirect` at priority 10.
- `custom_guest_authors_suppress_url()` was calling `get_post_meta()` directly on every author link render, bypassing the transient cache. The function now reads from the `cga_{post_id}` transient first, falling back to `get_post_meta()` only on a cache miss.
- `WordPress.Security.ValidatedSanitizedInput.MissingUnslash` on `$_POST['cga_nonce']` in `cga_save_meta_box_data()`. Now passed through `wp_unslash()` before `sanitize_text_field()`.
- `WordPress.DB.DirectDatabaseQuery.DirectQuery` on the transient count query. Result is now wrapped with `wp_cache_get()` / `wp_cache_set()` and runs at most once per page load.

### Added
- `js/settings.js` — handles interactive card state and live join-style preview. Enqueued via `wp_enqueue_script()`, loaded in the footer.
- `wp_localize_script()` added to pass `cgaSettings.previewNames` and `cgaSettings.i18nAnd` for translation-aware previews.

### Removed
- Explicit `load_plugin_textdomain()` call removed (flagged as a Plugin Check error for WordPress.org-hosted plugins). *(Restored in v2.0.1 to support bundled translations on self-hosted installs.)*

---

## [1.8.1] — 2026-02-19

### Fixed
- Classic editor meta box was hardcoded to `post` and `page`, ignoring the Active Post Types setting. It now reads `cga_enabled_post_types` and registers only for selected types.
- `cga_suppress_schema` toggle was not saving correctly. Unchecked checkboxes send nothing in HTML form submissions. A hidden companion input now sends `0` when unchecked, and the sanitize callback is a dedicated `cga_sanitize_checkbox()` function.
- `cga_enabled_post_types` checkboxes could not be saved as an empty array — the sanitize callback returned `array('post')` as a fallback. A hidden sentinel input ensures the POST key is always present; the sanitizer filters out the empty sentinel so all-unchecked correctly saves as `[]`.

---

## [1.8.0]

### Added
- General tab — Post type selection. A checkbox grid of all public post types replaces the single "Override on Pages" toggle. Stored as `cga_enabled_post_types` (array). Posts enabled by default.
- Display tab — Multi-Author Join Style. Radio card selector: Natural (`A, B and C`), Comma (`A, B, C`), Ampersand (`A & B & C`). Stored as `cga_join_style`.
- Display tab — Show Override On. Restrict the override to singular views only or apply on all views. Stored as `cga_apply_on`.
- Advanced tab — Cache Lifetime. Configurable transient TTL in hours (default 12, range 1–168). Previously hardcoded.
- Advanced tab — Suppress author from JSON-LD schema. Removes the author property from Article schema. Compatible with Yoast SEO and Rank Math.
- Advanced tab — Debug Information. Read-only table showing plugin version, active post types, join style, cache TTL, cached transient count, WordPress and PHP versions.

### Improved
- `cga_register_settings()` expanded with sanitize callbacks for all new options including `cga_sanitize_post_types()`.

---

## [1.7.5]

### Added
- Malay (Malaysia) translation — `ms_MY.po` and `ms_MY.mo` in `/languages/`. The "and" conjunction outputs as "dan" on `ms_MY` locale.
- POT template file (`custom-guest-authors.pot`) covering all translatable strings.

### Removed
- Author name prefix feature removed. The theme already outputs its own "Written by" label, making the plugin prefix redundant and producing doubled output.

### Fixed
- Author name is now output as plain unlinked text. Guest authors are not WordPress users and have no author archive; the hyperlink was meaningless.

---

## [1.7.3]

### Fixed
- Prefix concatenated with no space, producing output like `ByJohn Doe`. Now `rtrim`'d with a single space always inserted.

### Improved
- Multi-author output uses smart natural-language joining: two authors produce "A and B", three or more produce "A, B and C".

### Removed
- `cga_separator` option superseded by the smart join behaviour.

---

## [1.7.2]

### Improved
- Settings page completely redesigned to match the plugin suite design language — `wpcp-` CSS classes, slate colour palette, CSS custom properties, page header with SVG icon and version badge.
- Tab navigation converted to URL `?tab=` parameter links. Form posts to `options.php` via the Settings API. All dashicons replaced with inline SVGs.
- `meta-box.css` and `gutenberg-sidebar.css` updated to use the same CSS variables and class conventions.

### Removed
- `settings.js` removed (tab switching no longer required client-side JS).

---

## [1.7.1]

### Added
- Settings page at Settings › Guest Authors with General and Display tabs.
- General tab — default guest author name, option to enable override on Pages.
- Display tab — configurable multi-author separator, optional author name prefix, live preview card.
- Settings page assets (`css/settings.css`, `js/settings.js`) separated and enqueued only on the settings page hook.

### Fixed
- `cga_override_on_pages` was never read in the front-end filter — pages were always overridden regardless of the setting.
- Hidden tab input always output `cga-tab-general` on page load. Now empty on GET, populated only after a save.
- Removed dead `register_setting()` calls whose sanitize callbacks were never invoked.

---

## [1.6.2]

### Fixed
- Classic meta box appeared twice in the block editor. Now suppressed when the block editor is active.
- JavaScript crash in the Gutenberg sidebar on initial render when post type is not yet resolved.
- `use_block_editor_for_post_type()` deprecated since WP 6.5 — replaced with `wp_use_block_editor_for_post_type()` with backwards-compatible shim.
- Potential PHP warning on `post-new.php` when `get_current_screen()->post_type` returns empty string.
- Removed unused `useDispatch` declaration in `gutenberg-sidebar.js`.
- `PluginDocumentSettingPanel` now resolves from `wp.editor` (canonical WP 6.6+ location) with fallback to `wp.editPost`.

### Improved
- Added `wp-editor` to Gutenberg sidebar script dependencies.

---

## [1.6]

### Added
- Dedicated "Guest Authors" panel in the classic editor (meta box) and block editor (Gutenberg `PluginDocumentSettingPanel`).
- Post meta registered via `register_post_meta()` with REST API exposure for full Gutenberg read/write support.
- CSS and JS assets separated into `/css/` and `/js/` directories and enqueued via WordPress APIs.
- Minimalist slate-toned styling consistent with the plugin suite design language.
- Plugin constants `CGA_VERSION`, `CGA_PLUGIN_DIR`, `CGA_PLUGIN_URL` defined.

### Requires
- WordPress 5.7 minimum (`useEntityProp` introduced in `@wordpress/core-data`).

---

## [1.5]

### Fixed
- Transient cache invalidated immediately on post save, preventing stale guest author names after an update.
- Cache invalidation also fires on direct post meta updates via REST API or programmatic writes.

### Improved
- Transient key namespaced to `cga_{post_id}`. Default guest author option namespaced to `cga_default_guest_author`. Empty author entries filtered from comma-separated lists. `ABSPATH` exit guard added.

---

## [1.2]

### Added
- Support for multiple guest authors separated by commas.

---

## [1.1]

### Added
- Transient caching for performance. Input sanitization and output escaping.

---

## [1.0]

### Added
- Initial release.

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

## [1.9.1] — 2025-02-20

### Fixed
- `WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound` warnings persisted in Plugin Check despite `phpcs.xml` declaring `cga` as an authorised prefix. Plugin Check runs its own internal PHPCS configuration and does not read `phpcs.xml` — it derives the expected prefix from the plugin slug (`custom_guest_authors`), which does not match the short `cga_` prefix heuristic. All 15 `cga_*` function declarations now carry an inline `// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound` annotation. The `phpcs.xml` prefix declaration is retained for local PHPCS runs.

---

## [1.9.0] — 2025-02-20

### Added
- `phpcs.xml` added to the plugin root, declaring `cga_` and `custom_guest_authors_` as authorised prefixes for `WordPress.NamingConventions.PrefixAllGlobals`. Without this file the rule had no way to recognise the plugin's registered prefix and raised a false-positive warning on every global function declaration. Also pins `minimum_wp_version` to `5.7` and `testVersion` to `8.5-` to align PHPCS analysis with the plugin's declared requirements.

### Updated
- `Requires PHP` bumped from `7.0` to `8.5` to reflect the current PHP stable release.
- `Tested up to` confirmed at `6.9` (WordPress 6.9 "Gene", released 2 December 2025).

---

## [1.8.9] — 2025-02-20

### Fixed
- `WordPress.DB.DirectDatabaseQuery.DirectQuery` warning on the cached transient count query in the Advanced tab debug table. The result of the `$wpdb->get_var()` `SELECT COUNT` query is now wrapped with `wp_cache_get()` / `wp_cache_set()` using the `'custom-guest-authors'` cache group. The query runs at most once per page load; subsequent calls within the same request read from the object cache. A `phpcs:ignore` inline annotation is retained on the query line itself since there is no WordPress API equivalent for a `LIKE`-pattern option count.

---

## [1.8.8] — 2025-02-20

### Fixed
- `WordPress.Security.ValidatedSanitizedInput.MissingUnslash` warning on the nonce check in `cga_save_meta_box_data()`. `$_POST['cga_nonce']` is now passed through `wp_unslash()` before `sanitize_text_field()` prior to `wp_verify_nonce()`, matching the pattern already used for all other `$_POST` reads in the plugin.

---

## [1.8.7] — 2025-02-20

### Removed
- `cga_load_textdomain()` and its `init` hook removed. WordPress automatically loads translations for plugins hosted on WordPress.org as of WP 4.6; an explicit `load_plugin_textdomain()` call is unnecessary and is flagged as a Plugin Check error (`PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound`).

---

## [1.8.6] — 2025-02-20

### Fixed
- `custom_guest_authors_suppress_url()` was calling `get_post_meta()` directly on every author link render, bypassing the transient cache used by the name filter. On archive pages with many posts this produced one uncached database query per post per page load. The function now reads from the `cga_{post_id}` transient first and falls back to `get_post_meta()` only on a cache miss, then sets the transient using the same `cga_cache_ttl` option and TTL logic as `custom_guest_authors_name()`. Both functions now draw from the same cache layer.

---

## [1.8.5] — 2025-02-20

### Fixed
- `get_the_author_display_name` is not a registered WordPress core filter hook and was never fired, meaning the guest author override had no effect in any context that calls `get_the_author()` programmatically. Replaced with the correct `get_the_author` hook, which WordPress fires whenever `get_the_author()` is called without arguments.

---

## [1.8.4] — 2025-02-20

### Fixed
- Active settings tab was not reliably preserved after saving. `options.php` uses `wp_safe_redirect()` internally; the existing `cga_settings_redirect` filter only hooked `wp_redirect`, so the tab query parameter was silently dropped on hosts where the redirect bypassed that filter. The same callback is now also registered on `wp_safe_redirect` at priority 10. No change to the function itself.

---

## [1.8.3] — 2025-02-20

### Fixed
- Join style preview on the Display tab was static and only reflected the last saved value. Switching between Natural, Comma, and Ampersand radio cards now updates both preview outputs immediately without requiring a page save.

### Improved
- `js/settings.js` updated with `buildPreview()` and `updatePreview()` functions. The delegated change listener now additionally triggers a preview refresh when the `cga_join_style` radio group changes.
- Preview output divs in the Display tab now carry `id="cga-preview-3"` and `id="cga-preview-2"` for reliable JS targeting.
- `wp_localize_script()` added to `cga_enqueue_settings_assets()` passing `cgaSettings.previewNames` and `cgaSettings.i18nAnd` so preview names and the conjunction are translation-aware and not hardcoded in JS.

---

## [1.8.2] — 2025-02-20

### Fixed
- Radio cards and checkbox cards on the settings page showed no visual feedback when clicked. The `.selected` and `.wpcp-checkbox-card--checked` CSS classes were only applied server-side at page load and did not update on interaction. A delegated `change` listener now keeps card visual state in sync with the underlying input state at all times.

### Added
- `js/settings.js` — new file handling interactive card state on the settings page. Enqueued via `wp_enqueue_script()` in `cga_enqueue_settings_assets()`, loaded in the footer with no dependencies.

---

## [1.8.1] — 2025-02-19

### Fixed
- Classic editor meta box was still hardcoded to appear only on `post` and `page` post types, ignoring the Active Post Types setting entirely. It now reads `cga_enabled_post_types` and registers the meta box only for the selected types.
- `cga_suppress_schema` toggle was not saving correctly. WordPress HTML form submissions send nothing for unchecked checkboxes, so the option could never be saved as false. A hidden companion input now sends `0` when unchecked, and the sanitize callback was changed from `rest_sanitize_boolean` to a dedicated `cga_sanitize_checkbox()` function that handles both `0` and `1` correctly.
- `cga_enabled_post_types` checkboxes could not be saved as an empty array — the sanitize callback was returning `array('post')` as a fallback when nothing was submitted. A hidden sentinel input now ensures the POST key is always present, and the sanitizer filters out the empty sentinel value so all-unchecked correctly saves as an empty array.

---

## [1.8.0]

### Added
- General tab — Post type selection. A checkbox grid of all public post types replaces the single "Override on Pages" toggle. The `cga_override_on_pages` option is superseded by `cga_enabled_post_types` (array). Posts enabled by default; all other types opt-in.
- Display tab — Multi-Author Join Style. Radio card selector: Natural language (`A, B and C`), Comma only (`A, B, C`), Ampersand (`A & B & C`). Stored as `cga_join_style`.
- Display tab — Show Override On. Radio card selector to restrict the override to singular post views only or apply on all views including archive/home loops. Stored as `cga_apply_on`.
- Advanced tab — Cache Lifetime. Configurable transient TTL in hours (default 12, range 1–168). Previously hardcoded.
- Advanced tab — Suppress author from JSON-LD schema. Toggle that removes the author property from Article schema. Compatible with Yoast SEO (`wpseo_schema_graph`) and Rank Math (`rank_math/schema/article`).
- Advanced tab — Debug Information. Read-only table showing plugin version, active post types, join style, cache TTL, cached transient count, WordPress version, and PHP version.

### Improved
- Settings page CSS updated with checkbox grid, radio card, and debug table component styles.
- `cga_register_settings()` expanded with sanitize callbacks for all new options including a dedicated `cga_sanitize_post_types()` function that validates against `get_post_types()`.

---

## [1.7.5]

### Removed
- Author name prefix feature removed entirely. The theme already outputs its own "Written by" or equivalent label, making the plugin prefix redundant and producing doubled output.

### Fixed
- Author name is now output as plain unlinked text. Guest authors are not WordPress users and have no author archive, so the hyperlink generated by `the_author_posts_link()` was meaningless. A new filter on `the_author_posts_link()` suppresses the anchor and returns the plain name instead.

### Updated
- POT and `ms_MY` translation files updated to remove prefix-related strings and recompiled.

---

## [1.7.4]

### Added
- Malay (Malaysia) translation — `ms_MY.po` and `ms_MY.mo` included in `/languages/`. The "and" conjunction correctly outputs as "dan" when WordPress is set to `ms_MY` locale.
- POT template file (`custom-guest-authors.pot`) covering all translatable strings in the plugin.

### Fixed
- `load_plugin_textdomain()` was never called despite the Text Domain header being declared. All `__()` calls were silently falling back to English regardless of the site locale. The text domain is now properly loaded on `init`.

---

## [1.7.3]

### Fixed
- Prefix was concatenated directly against the author name with no space, producing output like `ByJohn Doe`. The prefix is now `rtrim`'d and a single space is always inserted between it and the first author name.

### Improved
- Multi-author output now uses smart natural-language joining. Two authors produce "Guest A and Guest B"; three or more produce "Guest A, Guest B and Guest C". The configurable separator field has been removed as it is superseded by this logic.
- Display tab preview updated to show both the 2-author and 3-author formats with the current prefix applied.

### Removed
- `cga_separator` option and its settings field — no longer needed given the smart join behaviour.

---

## [1.7.2]

### Improved
- Settings page completely redesigned to match the established plugin suite design language — `wpcp-` CSS classes, slate colour palette, CSS custom properties, page header with SVG icon and version badge.
- Tab navigation converted from JavaScript button-based switching to URL `?tab=` parameter links, consistent with the suite pattern and requiring no client-side JS.
- Boolean toggle converted from a plain checkbox to a proper toggle switch component matching the suite style.
- Form now posts to `options.php` via the Settings API (`settings_fields`) instead of self-posting, with a `wp_redirect` filter to restore the active tab after save.
- All dashicons removed from the settings page and replaced with inline SVG icons.
- `meta-box.css` and `gutenberg-sidebar.css` updated to use the same CSS variables and class conventions as the suite.

### Removed
- `settings.js` removed (tab switching no longer required client-side JS at that point).

---

## [1.7.1]

### Fixed
- `cga_override_on_pages` option was registered and displayed in the UI but never read in the front-end filter — pages were always overridden regardless of the checkbox state. The filter now correctly bails on pages when the option is disabled.
- The hidden tab input always output `cga-tab-general` on fresh page loads, overriding sessionStorage tab memory and forcing the settings page to always open on the General tab. The input is now empty on GET requests and only populated after a successful POST save.
- Removed dead `register_setting()` calls whose `sanitize_callback`s were never invoked since the form posted to self rather than `options.php`. Manual sanitization in the save handler is now the sole authoritative path.

---

## [1.7]

### Added
- Settings page at Settings › Guest Authors with tabbed interface (General and Display tabs).
- General tab — configurable default guest author name shown when no per-post guest author is set.
- General tab — option to enable guest author overrides on Pages (disabled by default, Posts only).
- Display tab — configurable multi-author separator (default `, `; examples: ` & `, ` / `).
- Display tab — optional author name prefix (e.g. `By `) prepended to all guest author output.
- Display tab — live preview card showing example output with current separator and prefix settings.
- Settings page assets (`css/settings.css`, `js/settings.js`) properly separated and enqueued only on the settings page hook.
- Tab state persisted in `sessionStorage` so the last active tab is remembered within the admin session.

### Improved
- Separator and prefix options wired into the front-end author name filter.

---

## [1.6.2]

### Fixed
- Classic editor meta box was appearing twice in the block editor — once as the dedicated Gutenberg sidebar panel and again as a WordPress compatibility meta box. The classic meta box is now suppressed when the block editor is active for the post type.
- Removed unused `useDispatch` declaration in `gutenberg-sidebar.js` (dead code).
- `PluginDocumentSettingPanel` now resolves from `wp.editor` (canonical WP 6.6+ location) with a fallback to `wp.editPost`, eliminating deprecation notices in the browser console on WP 6.6+.

### Improved
- Added `wp-editor` to Gutenberg sidebar script dependencies to support the `wp.editor` fallback.

---

## [1.6.1]

### Fixed
- JavaScript crash in Gutenberg sidebar on initial render when post type is not yet resolved by the block editor store.
- `use_block_editor_for_post_type()` deprecated since WP 6.5 — replaced with `wp_use_block_editor_for_post_type()` with a backwards-compatible shim for older WP versions.
- Potential PHP warning on `post-new.php` when `get_current_screen()->post_type` returns an empty string before the post type is determined.

---

## [1.6]

### Added
- Dedicated "Guest Authors" sidebar panel in the classic editor (meta box).
- Dedicated "Guest Authors" sidebar panel in the block editor (Gutenberg `PluginDocumentSettingPanel`).
- Post meta registered via `register_post_meta()` with REST API exposure, enabling full Gutenberg read/write support.
- CSS and JS assets separated into `/css/` and `/js/` directories and properly enqueued via WordPress APIs.
- Minimalist slate-toned styling for the meta box and Gutenberg panel, consistent with the plugin suite design language.
- Plugin constants defined (`CGA_VERSION`, `CGA_PLUGIN_DIR`, `CGA_PLUGIN_URL`) for cleaner asset enqueuing.

### Improved
- Classic editor assets only load on post edit screens and only when the block editor is not active, preventing unnecessary asset loading.

### Requires
- WordPress 5.7 minimum (`useEntityProp` introduced in `@wordpress/core-data`).

---

## [1.5]

### Fixed
- Transient cache is now invalidated immediately on post save, preventing stale guest author names from displaying after an update.
- Cache invalidation also fires on direct post meta updates (e.g. via REST API or programmatic writes) that bypass `save_post`.

### Improved
- Transient key shortened and namespaced to `cga_{post_id}` for consistency.
- Default guest author option key namespaced to `cga_default_guest_author` to prevent collision with other plugins.
- Added `ABSPATH` exit guard as standard security practice.
- Empty author entries are now filtered out from comma-separated lists.

---

## [1.2]

### Added
- Support for multiple guest authors separated by commas.

---

## [1.1]

### Added
- Transient caching for performance.
- Input sanitization and output escaping.

---

## [1.0]

### Added
- Initial release.

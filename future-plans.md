# Custom Guest Authors — Future Plans

---

## Item 4: Custom Post Type Support

```yaml
feature: Custom Post Type Support
priority: 4
status: planned

description: >
  Add a settings page option allowing site administrators to select which
  registered post types the guest author override applies to. Currently the
  plugin acts on Posts only (with an optional Pages toggle). This feature
  extends that to any public custom post type registered on the site.

implementation:
  settings_tab: General
  ui: >
    A dynamically populated checkbox list under a new "Post Types" card on the
    General settings tab. Uses get_post_types({ public: true }) to enumerate
    all available post types at render time. Post and Page are pre-checked by
    default; custom post types default to unchecked.
  option_key: cga_enabled_post_types
  option_type: array of post type slugs
  storage: serialized array via update_option()

frontend_filter:
  change: >
    Replace the current 'page' check in custom_guest_authors_name() with a
    check against the cga_enabled_post_types option array. Bail if the current
    post type is not in the enabled list.
  cache_key: no change required

meta_box:
  change: >
    Update cga_add_meta_box() to register the meta box for all enabled post
    types rather than the hardcoded array('post', 'page').

files_affected:
  - custom-guest-authors.php
  - css/settings.css (minor — no new styles expected)
```

---

## Item 5: Author Avatar and Bio Display

```yaml
feature: Author Avatar and Bio Display
priority: 5
status: planned

description: >
  A shortcode and/or widget to render a guest author byline card beneath post
  content (or at any manually placed location). The card displays the guest
  author name(s) and an optional bio line. No author profile system is
  introduced — bio text is stored as a second per-post custom field.

implementation:
  new_meta_field: guest-author-bio
  field_label: Guest Author Bio
  field_type: textarea
  storage: post meta via update_post_meta()

  shortcode:
    tag: '[guest_author_card]'
    output: >
      Renders a styled byline card using the guest-author and guest-author-bio
      meta fields for the current post. Falls back gracefully when either field
      is absent.

  auto_append:
    option_key: cga_auto_append_card
    description: >
      Optional setting on the Display tab to automatically append the byline
      card after post content via the_content filter, without requiring manual
      shortcode placement.

editor_ui:
  classic: >
    Extend the existing classic editor meta box to include a textarea field
    for the guest author bio beneath the existing name input.
  gutenberg: >
    Extend the existing Gutenberg sidebar panel to include a TextareaControl
    for the bio field.

new_files:
  - css/byline-card.css  (front-end card styles, slate palette)

files_affected:
  - custom-guest-authors.php
  - css/meta-box.css (textarea field styles)
  - css/gutenberg-sidebar.css (TextareaControl styles)
  - js/gutenberg-sidebar.js (add TextareaControl for bio)
  - readme.txt
```

---

## Item 6: Gutenberg Block

```yaml
feature: Guest Author Byline Block
priority: 6
status: planned
depends_on: Item 5 (Author Avatar and Bio Display)

description: >
  A dedicated Gutenberg block that renders the guest author byline card inline
  within the block editor. Complements the shortcode added in Item 5 but
  provides a native block editor experience with live preview in the editor
  canvas. Most useful for structured article formats where byline position
  varies per post.

implementation:
  block_name: custom-guest-authors/byline
  block_category: text
  attributes:
    - post_id: (auto-resolved from current post context)
    - show_bio: boolean, default true
  render: >
    Server-side rendered via register_block_type() with a render_callback.
    Reads guest-author and guest-author-bio meta for the current post and
    outputs the same HTML as the shortcode card from Item 5, ensuring
    consistent front-end output regardless of how the byline is inserted.

  editor_preview: >
    Uses useEntityProp to read the guest-author and guest-author-bio meta
    fields live in the editor, so the block previews the actual saved values
    without requiring a page reload.

new_files:
  - js/byline-block.js   (block registration and editor preview component)
  - css/byline-block.css (editor-side block styles)

files_affected:
  - custom-guest-authors.php (register_block_type call)
  - readme.txt
```

---

## Item 7: Suite Integration

```yaml
feature: Suite Integration
priority: 7
status: planned
depends_on: Items 4–6 (all preceding features complete)

description: >
  Integrate Custom Guest Authors with the broader plugin suite (Auto Justify
  Content, Cite, Endmark) by grouping all suite plugins under a shared top-level
  admin menu and coordinating with Endmark to avoid typographic conflicts between
  the end mark and the guest author byline.

implementation:
  shared_admin_menu:
    top_level_slug: menj-plugin-suite
    top_level_label: "MENJ Plugins"
    top_level_icon: dashicons-admin-plugins
    description: >
      Each suite plugin registers its settings as a submenu page under the shared
      top-level menu rather than under Settings. Requires a coordination check —
      only one plugin should register the top-level menu; others attach as
      submenus. Use a shared option or constant (e.g. MENJ_SUITE_MENU_REGISTERED)
      to prevent duplicate top-level entries.
    migration: >
      The existing Settings > Guest Authors menu entry is replaced by
      MENJ Plugins > Guest Authors. A Settings redirect shim should be added
      for one version to avoid broken bookmarks.

  endmark_coordination:
    description: >
      When Endmark is active and a guest author byline card is appended after
      post content, the Endmark end mark may render inside or immediately before
      the byline card rather than at the end of the article body. Coordination
      logic should detect whether both plugins are active and suppress or
      reposition the end mark accordingly.
    detection: function_exists('endmark_some_known_function') or defined constant
    approach: >
      Hook into Endmark's output filter (if available) or apply the byline card
      after Endmark has already processed the_content, ensuring correct render
      order. Document the hook priority clearly.

files_affected:
  - custom-guest-authors.php (admin menu registration refactor)
  - readme.txt
```

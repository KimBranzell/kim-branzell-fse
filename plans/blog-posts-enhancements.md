# Implementation Plan: Blog Posts Block Enhancements

## Overview

Extend the existing `kim-branzell/blog-posts` block with a **two-zone hybrid layout**: a lead card grid at the top (sticky-promoted + most recent, with featured images, excerpts, author avatar, reading time, comment count, "Read post →" link) followed by an editorial row list below for remaining posts (thumbnail, title, single-line excerpt, meta, arrow). Filter chips continue to drive both zones together. Adds a "View all posts" footer link when applicable. All features are default-on where requested, with editor toggles for each.

## Deliverable Type

**One-off enhancement** to the existing `inc/blocks/blog-posts/` block within this theme. No new plugin — this is presentation logic tightly coupled to the theme's block conventions (manual registration, Vite entry, SSR pattern). Every change touches files already in the block folder plus the shared registration/config files.

## Confirmed Facts

- **Block file set**: `inc/blocks/blog-posts/{block.json, index.js, render.php, template.php, view.js}` (verified — same set as `project-index`). No new file types needed.
- **Vite entries**: `blog-posts` (editor) and `blog-posts-view` (frontend) already exist in `vite.config.js` `rolldownOptions.input` (lines 71–72). No change required unless we add a new entry — we don't.
- **Registration**: `inc/blocks/blocks.php` lines 117–143 already registers `kb-blog-posts-editor` + `kb-blog-posts-view` script handles and the block type. `viewScript` deps array is empty `array()` — same as `project-index` (no `wp-a11y`, guarded in JS). No change required.
- **`post-thumbnails` support**: registered in `functions.php` `kb_after_setup_theme()` (line 18). `wp_get_attachment_image()` is already used in `hero` and `about` render callbacks — same pattern.
- **No custom image size exists yet**: `add_image_size` not present anywhere in `functions.php` or `inc/functions/*.php` (verified by grep). We'll add one for the 16:9 lead cards.
- **Avatar plumbing exists**: `inc/functions/user-portrait.php` provides `kb_get_portrait_url( $user_id )` which prefers ACF `kb_profile_image` user field, then falls back to Gravatar. We must NOT use the returned URL for inline `<img>` rendering because…
- **CSP blocks Gravatar**: `inc/functions/headers.php` sends `default-src 'self'` with **no `img-src` exception**. Gravatar URLs (external origin) would be blocked by the browser. ACF-stored attachments served from the same origin work fine. ⇒ The block must resolve the ACF portrait **attachment ID** directly (use `wp_get_attachment_image()`) and fall back to **author-initials placeholder** when no ACF portrait is set — never rely on Gravatar for inline rendering. This is a hard constraint; the existing OG-image path doesn't hit CSP because it rasterizes server-side.
- **Sticky posts**: `get_posts()` defaults to `ignore_sticky_posts => true`. We must explicitly set `ignore_sticky_posts => false` and use `get_option( 'sticky_posts' )` to surface sticky posts in the lead zone.
- **`page_for_posts`**: standard WP option; `get_permalink( (int) get_option( 'page_for_posts' ) )` returns the posts archive URL when set, else `0`/empty.
- **Reading time convention**: `absint` to min 1; existing block uses `ceil` rounded — AGENTS.md §9 says use `round`/min 1. Existing block deviates; we'll align new code to AGENTS.md but leave existing rows on the current arithmetic (no behavior change for list rows).
- **PHPCS baseline**: new block PHP files share the standard 1-error/1-warning lint profile; `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` on `<?php echo $rows_html; ?>` lines (AGENTS.md §7–8).
- **Component SCSS**: `assets/src/scss/blocks/_blog-posts.scss` is auto-`@use`d in `assets/src/scss/blocks.scss` (line 21). No `app.scss` edit needed.
- **Inter `auto-contrast()` + color-mix patterns**: see _blog-posts.scss lines 13–25 and AGENTS.md §5 for the established token pattern (`--_muted`, `var(--main)`, `auto-contrast(var(--main))`, dividers at 15–25% contrast).

## Assumptions

- **One author** typical for the site (Kim). The author meta + avatar rendering still uses `get_the_author_meta( 'display_name', $post->post_author )` per post rather than caching author info — author data is a single `get_user_meta`/`get_the_author_meta` call per post, acceptable for N≤10 lead cards. Validator: `ddev wp post list --post_type=post --field=author` should confirm single author.
- **`kb_profile_image` ACF field on the user is set** for Kim (so avatars render same-origin). Validator: `ddev wp eval 'var_dump( get_field( "kb_profile_image", "user_1" ) );'`. If unset, author-initials placeholder renders — not a blocker, just less polished.
- **Lead image `aspect-ratio`** is supported in all browser targets (Vite `browserslistToTargets` lists `chrome >= 111, edge >= 111, firefox >= 114, safari >= 16.4` — `aspect-ratio` is universally supported there).
- **`wp_count_posts( 'post' )`** is already used for the count label and will be reused for the "show View all posts link when queried < total" gate.
- **Comments**: WP defaults `comments_open`/`comments_allowed` per-post; `get_comments_number( $post->ID )` returns int. We render the count only when > 0 and `showComments` is on.
- **Validation**: builder verifies each via `ddev wp ...` before moving to the next step.

## Architecture Changes

| File | Change |
|------|--------|
| `functions.php` | Add `add_image_size( 'kb-blog-lead', 1200, 675, true )` inside `kb_after_setup_theme()`. Add `make_large_node` filter for cropping is **not** needed — `add_image_size` is sufficient. |
| `inc/blocks/blog-posts/block.json` | Add 8 new attributes: `leadCount` (int, 3), `leadImageSize` (string, `large`), `showExcerpt` (bool, true), `showThumbnail` (bool, true), `showAuthor` (bool, true), `showAuthorAvatar` (bool, true), `showComments` (bool, true), `showReadMore` (bool, true), `showViewAll` (bool, true). Update `description` to mention lead/list zones. |
| `inc/blocks/blog-posts/render.php` | Major refactor: query with `ignore_sticky_posts => false`, partition into `$lead_posts` and `$list_posts` based on sticky+recent ordering and `leadCount`. Build per-card markup (image/placeholder, eyebrow category, title, excerpt, avatar, meta, read-more) and per-row markup (thumbnail, title, excerpt-truncated, meta, arrow). Add `aria-labelledby` to both zones. Pre-escape everything; emit `$lead_html` and `$rows_html` to template.php. Compute `$view_all_url` + `$show_view_all`. |
| `inc/blocks/blog-posts/template.php` | Add a `<div class="blog-posts__lead" aria-labelledby="$heading_id-lead">` zone above the existing `<ul>` list. Add optional `<div class="blog-posts__view-all"><a>…</a></div>` and reorganize the docblock for the new vars. List `<ul>` gains role/aria where applicable. |
| `inc/blocks/blog-posts/view.js` | Update the filter to also hide/show the lead zone items (`<article>` elements get `data-category` too) and to hide the entire `.blog-posts__lead` wrapper if all lead cards are filtered out. Announce count of visible items in both zones combined. |
| `inc/blocks/blog-posts/index.js` | Add toggle + number + select controls for the 8 new attributes in the `InspectorControls` `PanelBody`. Use `SelectControl` for `leadImageSize` (only control that needs a dropdown). Keep SSR preview capability — `ServerSideRender` will render the new layout automatically. |
| `assets/src/scss/blocks/_blog-posts.scss` | Add `.blog-posts__lead` grid (responsive 1→2→3 cols), `.blog-posts__card` (image-topped card), `.blog-posts__avatar`, `.blog-posts__view-all`, `.blog-posts__placeholder`, "Pinned" badge. Update the row `.blog-posts__link` grid template to include a thumbnail column. Reuse `--_muted`, `color-mix()`, accent-hover, reduced-motion, forced-colors patterns. No `app.scss` edit — `blocks.scss` already `@use`s `_blog-posts`. |

No new Vite entries, no `blocks.php` change, no `app.scss` change. The block's existing handles keep working.

## Implementation Steps

### Phase 1: Foundation — image size + attribute model

1. **Register the `kb-blog-lead` image size** (File: `functions.php`)
   - Action: Inside `kb_after_setup_theme()`, after the existing `add_theme_support` calls (currently lines 17–21), add `add_image_size( 'kb-blog-lead', 1200, 675, true );`.
   - Why: Lead cards need a consistent 16:9 crop. No `add_image_size` exists yet; this is the canonical WP way.
   - Gates: none (no user-facing markup changes yet)
   - Dependencies: None
   - Acceptance: `ddev wp eval 'var_dump( has_image_size( "kb-blog-lead" ) );'` prints `bool(true)`. Note: existing featured images won't have this size generated retroactively until regenerated — instruct Kim via `ddev wp media regenerate --image_size=kb-blog-lead` or accept the fallback to the next larger core size (`large`) used by `wp_get_attachment_image` when the requested size file is missing (WP returns the full-size image with `srcset` derived from existing sizes; this is fine and degrades gracefully).
   - Risk: Low

2. **Extend `block.json` attributes** (File: `inc/blocks/blog-posts/block.json`)
   - Action: Add 9 new attributes inside the existing `"attributes"` object (keeping alphabetical-ish grouping near related existing attrs). New attrs:
     - `leadCount`: `{ "type": "integer", "default": 3 }`
     - `leadImageSize`: `{ "type": "string", "default": "large" }` (values: `medium` | `large` | `full` | `kb-blog-lead`; we'll surface the four in the editor select)
     - `showExcerpt`: `{ "type": "boolean", "default": true }`
     - `showThumbnail`: `{ "type": "boolean", "default": true }`
     - `showAuthor`: `{ "type": "boolean", "default": true }`
     - `showAuthorAvatar`: `{ "type": "boolean", "default": true }`
     - `showComments`: `{ "type": "boolean", "default": true }`
     - `showReadMore`: `{ "type": "boolean", "default": true }`
     - `showViewAll`: `{ "type": "boolean", "default": true }`
   - Update the `description` field to: `"Editorial index of recent posts. Lead cards up top with images, excerpts, and author avatars; editorial rows below. Filterable by category."`.
   - Why: Block attrs are the single source of truth; render.php and index.js read from here.
   - Gates: none (declarative only)
   - Dependencies: Steps must run in order; this is independent of step 1
   - Acceptance: `bun run build` succeeds (JS reads the schema via the registered block). `cat inc/blocks/blog-posts/block.json | python3 -m json.tool` parses.
   - Risk: Low

### Phase 2: render.php — query, partition, build markup

3. **Query posts with sticky support** (File: `inc/blocks/blog-posts/render.php`)
   - Action: At the top of `kb_render_block_blog_posts()`, after reading attributes (add the 9 new attrs to the existing `$attr = $attributes['…'] ?? …` block), change the `get_posts()` args:
     - Add `'ignore_sticky_posts' => false`.
     - Add `'has_password' => false` (defensive — never expose private posts).
     - Keep `posts_per_page`, `orderby`, `order`, `no_found_rows` as-is.
   - Resolve the avatar-size image fetching helper in this step too but defer its use to step 4: define a closure `kb_blog_get_avatar( $user_id )` inside the function that returns either `[ 'html' => <img tag> ]` (ACF attachment found) or `[ 'initials' => 'KB' ]` (no ACF portrait — for use by the initials placeholder renderer). Use `get_field( 'kb_profile_image', 'user_' . $user_id )` with `function_exists( 'get_field' )` guard; if `get_field` returns an array with a non-empty `ID`, call `wp_get_attachment_image( (int) $acf['ID'], array( 64, 64 ), false, array( 'class' => 'blog-posts__avatar' ) )` (square 64 — produces a cropped thumbnail automatically). NEVER inline-render a Gravatar URL.
   - Why: Sticky promotion is now needed; password-protected defenses are sane defaults. Avatar resolution needs to happen before card markup and must avoid the CSP-blocked Gravatar path.
   - Gates: security — `'has_password' => false` and the ACF ID cast are input-handling decisions. The avatar uses `wp_get_attachment_image()` which is the canonical escaping path (returns a fully-escaped HTML string).
   - Dependencies: Steps 1, 2
   - Acceptance: `ddev wp eval 'kb_render_block_blog_posts( array( "leadCount" => 3, "showAuthor" => true ) );'` should not throw (will print non-empty HTML if there are posts). `ddev wp eval 'var_dump( get_field( "kb_profile_image", "user_1" ) );'` shows what we'd resolve.
   - Risk: Low

4. **Partition posts into lead vs list** (File: `inc/blocks/blog-posts/render.php`)
   - Action: After the `get_posts()` call, compute:
     - `$lead_count = max( 0, min( (int) $attributes['leadCount'], 10 ) );` (clamp defensively).
     - Obtain sticky IDs: `$sticky_ids = get_option( 'sticky_posts' ) ?: array();` (array of ints).
     - Build `$lead_posts` = array of the first `$lead_count` posts **ordered sticky-first then by `post_date DESC`** — i.e. iterate `$posts` and prioritize sticky, then non-sticky, slicing at `$lead_count`.
     - `$list_posts` = array of remaining posts not included in `$lead_posts` (preserving the same global ordering used by `get_posts`).
     - If `posts_per_page` was 0/-1 (show-all) and total ≤ `leadCount`, `$list_posts` is empty (only lead zone renders).
   - Why: The hybrid layout is the user's chosen direction. Sticky promotion is a real WP feature and matches "latest and featured images on top".
   - Gates: none
   - Dependencies: Step 3
   - Acceptance: Manually call the renderer with two sticky + 8 total posts + leadCount=3 → expect 3 lead posts (the 2 sticky + the most-recent non-sticky) and 5 list posts. Use `ddev wp post update <id> --sticky` to test local sticky state if needed.
   - Risk: Medium — partitioning logic has edge cases (sticky posts excluded from current `get_posts` because they're not published, sticky + leadCount larger than total posts, etc.). Mitigate by clamping and gracefully degrading to "lead zone only" or "list zone only".

5. **Build category chip data for both zones** (File: `inc/blocks/blog-posts/render.php`)
   - Action: Replace the existing category-collection loop so it iterates **all queried posts** (lead + list together) — this is the same data the current code uses. No structural change to chip logic; just make sure `$post_category_map` covers all posts and `$distinct_categories` is built the same way. Keep the existing alphabetical sort + "All" chip + `aria-pressed` defaults. Build `$chips_html` as before.
   - Why: Filter applies to both zones; chips must reflect union of categories.
   - Gates: a11y — keep `role="group"` + `aria-label="Filter posts by category"` on the chip group (already present in template.php).
   - Dependencies: Step 4
   - Acceptance: With multiple categories published, the chip bar renders 1 "All" chip + N category chips exactly as before.
   - Risk: Low

6. **Build lead card markup** (File: `inc/blocks/blog-posts/render.php`)
   - Action: Inside the existing row-builder section, add a new `<article>`-per-lead-post loop BEFORE the existing list loop. For each `$lead_post` build:
       - Outer `<article class="blog-posts__card" data-category="<space-sep-slugs>" style="--i: <index>">` (data-category powered filter is unchanged in principle).
       - Whole-card link: wrap card body in `<a href="<permalink>" class="blog-posts__card-link">`. The card link is the single click target for the entire card (no stretched-link complexity, mirrors the list row pattern).
       - Image zone: `<div class="blog-posts__card-media">`. If `showThumbnail` AND `has_post_thumbnail( $lead_post )`: render `get_the_post_thumbnail( $lead_post, $attributes['leadImageSize'], array( 'class' => 'blog-posts__card-img', 'loading' => 'lazy', 'decoding' => 'async' ) )`. Else: render placeholder `<div class="blog-posts__placeholder blog-posts__placeholder--lead" aria-hidden="true"><span>↗</span></div>` (a subtle accent-tinted block with an arrow glyph — visually fills the same space).
       - Sticky badge (if sticky and `lead_count` includes sticky): `<span class="blog-posts__pinned" aria-label="Pinned"><svg inline/></span>` rendered as inline SVG, top-left corner of the media.
       - Body zone: `<div class="blog-posts__card-body">`. Inside:
         - Eyebrow category (first category of post, `showCategory` gate): `<p class="blog-posts__card-cat">`
         - Title: `<h3 class="blog-posts__card-title">` (escaped)
         - Excerpt (if `showExcerpt`): `<p class="blog-posts__card-excerpt">`. Resolve via: if `! empty( $lead_post->post_excerpt )` use `esc_html( wp_strip_all_tags( $lead_post->post_excerpt ) )`; else `wp_trim_words( wp_strip_all_tags( $lead_post->post_content ), $excerpt_length )` where `$excerpt_length = max( 8, min( (int) $attributes['excerptLength'] ?? 28, 80 ) )`. Use `esc_html()` on the result.
         - Author row (if `showAuthor`): `<div class="blog-posts__card-author">`. If `showAuthorAvatar`: render the avatar HTML (from the helper built in step 3), short-circuit returning the initials placeholder `<span class="blog-posts__avatar blog-posts__avatar--initials" aria-hidden="true">KC</span>` if ACF portrait absent. Next to the avatar: `<span class="blog-posts__card-author-name">` esc_html author display name.
         - Meta line: reuse the existing `blog-posts__meta` pattern but for cards. Include date (`showDate`), reading time (`showReadingTime`, computed `max(1, round( str_word_count(wp_strip_all_tags($lead_post->post_content)) / 200 ))`), comments (`showComments` when count > 0) — separated by `meta-sep` spans as before.
         - Read more (if `showReadMore`): `<span class="blog-posts__card-readmore">Read post <svg inline arrow/></span>` — sits at the end of the card body; not a separate link (the card-link wraps it).
     - Concatenate to `$lead_html`.
   - Why: This is the heart of the new design. Each feature is gated so individual toggles work.
   - Gates: a11y (image alt via `get_the_post_thumbnail` which inherits attachment alt; placeholder is `aria-hidden`; card-link is a single focus target with clearly-labelled text via the title `h3`; the readmore span text "Read post" combined with the h3 title gives screen readers meaningful link text via the accessible-name from the contained heading); security (all output escaped — `get_the_post_thumbnail` returns escaped HTML, `esc_url`/`esc_html` for the rest, avatar via `wp_get_attachment_image` which is escaped).
   - Dependencies: Steps 3, 4, 5
   - Acceptance: With showExcerpt + showThumbnail + showAuthor + showAuthorAvatar + showReadMore all on, each lead card shows: image (or placeholder) with optional pinned badge, eyebrow category, `h3` title, paragraph excerpt, avatar+name, meta (date·reading·comments when present), "Read post →" affordance. The whole card is keyboard-focusable and `:focus-visible` shows the accent ring.
   - Risk: Medium — wiring avatar fallback (initials vs ACF) and ensuring the CSP gotcha is respected is the trickiest part. Test by `get_field( 'kb_profile_image', 'user_1' )` returning `false` (placeholder path).

7. **Build list row markup** (File: `inc/blocks/blog-posts/render.php`)
   - Action: Modify the existing row loop so each `<li class="blog-posts__item" data-category="…">` now contains:
       - `<a class="blog-posts__link" href="<permalink>">` (unchanged pattern)
       - Optional thumbnail (if `showThumbnail` AND `has_post_thumbnail( $list_post )`): `get_the_post_thumbnail( $list_post, 'thumbnail', array( 'class' => 'blog-posts__thumb', 'loading' => 'lazy', 'decoding' => 'async' ) )` placed first inside the link. When no thumbnail, render nothing in this slot (or an `<span class="blog-posts__thumb blog-posts__thumb--spacer">` empty spacer when the SCSS grid needs a slot — builder decides based on the grid you build in step 10). Keep it as a grid column so titles align.
       - Date cell unchanged: `<time class="blog-posts__date" datetime="…">`.
       - Title wrap: `<span class="blog-posts__title-wrap"><h3 class="blog-posts__title">…</h3>` followed by excerpt (if `showExcerpt`): `<p class="blog-posts__excerpt">wp_trim_words(…, min(8, $excerpt_length))</p>` — one-line, single value clamped much shorter than the lead (suggest length 12). `</span>`.
       - Author/reading/comments in meta line: keep `blog-posts__meta` pattern, optionally add author display name when `showAuthor` (no avatar in list rows — avatars are a lead-only UX). Comments + reading time + date unchanged.
       - Arrow SVG unchanged.
   - Why: Editorial row identity is preserved; thumbnail slots are additive.
   - Gates: a11y (existing list-row single-link pattern retained; thumbnail `alt` from attachment; excerpt is descriptive context beside the title); security (escaping as before; thumbnail via `get_the_post_thumbnail`).
   - Dependencies: Steps 4, 5
   - Acceptance: List rows with thumbnail show 64px square thumbnail on the left and align across rows (titles and one-line excerpts vertically aligned). Without thumbnail, a spacer keeps the grid stable.
   - Risk: Low

8. **Compute "View all posts" link** (File: `inc/blocks/blog-posts/render.php`)
   - Action: After the loops, compute:
       - `$posts_page_id = (int) get_option( 'page_for_posts' );`
       - `$show_view_all = (bool) $attributes['showViewAll'] && $posts_page_id > 0 && (int) wp_count_posts( 'post' )->publish > count( $posts );`
       - If shown: `$view_all_url = esc_url( get_permalink( $posts_page_id ) );` and `$view_all_label = esc_html__( 'View all posts', 'kim-branzell' );`
   - Why: Editorial affordance; respects the showViewAll toggle and reality of a posts page being configured.
   - Gates: none (URL is escaped; option fetch and count are safe WP APIs).
   - Dependencies: Step 4 (need `$posts` and the count).
   - Acceptance: When no `page_for_posts` is set OR total published ≤ posts shown, the link does not render. When set + total exceeds posts shown, the link renders an `<a href>` to the archive.
   - Risk: Low

### Phase 3: template.php — assemble the two zones

9. **Update template.php markup** (File: `inc/blocks/blog-posts/template.php`)
   - Action: Reorganize the template so the structure is:
       ```
       <section class="blog-posts" aria-labelledby="$heading_id">
         <div class="blog-posts__inner">
           [ header ]  (unchanged)
           [ chips ]   (unchanged — group still has role=group aria-label)
           <?php if ( ! empty( $lead_html ) ) : ?>
              <div class="blog-posts__lead" aria-label="Featured posts">
                 <?php // phpcs:ignore … ?>
                 <?php echo $lead_html; ?>
              </div>
           <?php endif; ?>
           <?php if ( ! empty( $rows_html ) ) : ?>
              <ul class="blog-posts__list" role="list">
                  <?php // phpcs:ignore … ?>
                  <?php echo $rows_html; ?>
              </ul>
           <?php endif; ?>
           <?php if ( $show_view_all ) : ?>
              <div class="blog-posts__view-all">
                  <a href="<?php echo esc_url( $view_all_url ); ?>" class="blog-posts__view-all-link">
                      <?php echo esc_html( $view_all_label ); ?>
                      <svg ...arrow...>
                  </a>
              </div>
           <?php endif; ?>
           <p class="blog-posts__empty" aria-hidden="true">…</p>
           <div class="blog-posts__filter-announcer screen-reader-text" aria-live="polite" aria-atomic="true"></div>
         </div>
       </section>
       ```
   - Add `@var` entries to the docblock for `$lead_html`, `$show_view_all`, `$view_all_url`, `$view_all_label`.
   - Why: Two-zone output; both conditional so single-zone renders cleanly.
   - Gates: a11y — `<ul role="list">` already present; lead zone is `<div aria-label="Featured posts">` rather than a `<ul>` because cards aren't really a list of equivalent semantics; the existing filter announcer covers both zones.
   - Dependencies: Steps 6, 7, 8
   - Acceptance: Render with no posts → returns empty (unchanged); render with 2 posts + leadCount=3 → only lead zone + no list zone; render with 10 posts + leadCount=3 → 3 cards + 7 rows.
   - Risk: Low

### Phase 4: view.js — extend filter to both zones

10. **Update the filter logic in view.js** (File: `inc/blocks/blog-posts/view.js`)
    - Action: Update the `applyFilter` function so it operates on **both** `.blog-posts__item` (list rows) AND `.blog-posts__card` (lead cards) — both have the same `data-category` attribute. Refactor:
        - `const items = list.querySelectorAll( '.blog-posts__item, .blog-posts__card' );`
        - Same `data-category.split(/\s+/).includes(filterSlug)` check.
        - After filtering, hide the entire `.blog-posts__lead` wrapper when all lead cards are filtered out (`lead.style.display = 'none'`) and show it (`lead.style.display = ''`) when at least one card is visible. Same logic isn't needed for the list wrapper because empty-state message handles it.
        - Extend the announcement string to count "N posts" where N = total visible across both zones.
        - Handle the case where the section has only lead cards (no list) or only rows (no lead) gracefully — guard all `querySelector` calls with existing `if ( ! filterGroup || ! list )` plus `if ( ! lead )`.
    - Why: Filter must continue to work coherently with the new layout.
    - Gates: a11y — announcements must mention counts; previously-pressed chip excluded from updates; `aria-hidden` toggling of the empty message stays; the optional `.blog-posts__lead` element hide must not break reader focus order (we set `display: none` which removes from a11y tree).
    - Dependencies: Step 9
    - Acceptance: With 3 cards + 7 rows distributed across categories A, B, clicking chip "B" hides cards/rows lacking B; "No posts in this category" shows when none visible; announce fires "B: 4 posts". Clicking "All" restores everything; lead wrapper shows even if 1 card matches.
    - Risk: Medium — JS refactors breaking existing filter behavior. Mitigate by keeping the existing test path intact + manually verifying each category state.

### Phase 5: index.js — expose 9 new attributes in the editor

11. **Add editor controls for new attributes** (File: `inc/blocks/blog-posts/index.js`)
    - Action: Destructure `leadCount, leadImageSize, showExcerpt, showThumbnail, showAuthor, showAuthorAvatar, showComments, showReadMore, showViewAll` from `attributes` (alongside the existing destructured attrs). Import `SelectControl` from `wp.components`. Add a new `PanelBody` titled `__( 'Lead zone', 'kim-branzell' )` with:
        - `TextControl` `leadCount` (type=number, min=0, max=10)
        - `SelectControl` `leadImageSize` with options: medium, large, full, kb-blog-lead (labels: "Medium", "Large", "Full", "Wide optimized")
        - Toggles for `showExcerpt`, `showThumbnail`, `showAuthor`, `showAuthorAvatar`, `showReadMore`
    - Add another `PanelBody` titled `__( 'List zone', 'kim-branzell' )` toggling `showComments` and `showViewAll` (and reuse the existing `showDate`, `showReadingTime`, `showCategory` toggles by **moving** them from the existing panel into this list-zone panel — minor regrouping only). Keep the existing `postsToShow` and `enabled` controls where they are.
    - Add a new `PanelBody` titled `__( 'View all', 'kim-branzell' )` with `showViewAll` toggle (or just place the toggle in the list-zone panel — builder's discretion; prefer fewer panels).
    - Why: Author can toggle every feature; defaults already produce the new visual.
    - Gates: a11y (the editor is itself an a11y surface — `label`s must be translatable strings, controls are keyboard-operable by default in `wp.components`)
    - Dependencies: Steps 6 (SSR renders new layout when toggles flip), 10
    - Acceptance: Toggle off `showThumbnail` → lead cards render placeholders, list rows omit the thumbnail; toggle `leadCount` from 3 to 0 → only list renders; toggle `leadCount` to 10 with 4 posts published → all posts in lead zone, list zone empty.
    - Risk: Low

### Phase 6: SCSS — two-zone styling

12. **Add `.blog-posts__lead` card grid SCSS** (File: `assets/src/scss/blocks/_blog-posts.scss`)
    - Action: Inside the existing `.blog-posts { … }` block, add:
        - `&__lead { display: grid; gap: 2rem; grid-template-columns: 1fr; margin-bottom: 3rem; @media (min-width: 640px) { grid-template-columns: repeat(2, minmax(0, 1fr)); } @media (min-width: 1024px) { grid-template-columns: repeat(3, minmax(0, 1fr)); } }`
        - `&__card { display: flex; flex-direction: column; background: color-mix(in srgb, contrast-color(var(--main)) 4%, transparent); border: 1px solid color-mix(in srgb, contrast-color(var(--main)) 12%, transparent); border-radius: 6px; overflow: hidden; transition: border-color .2s ease, transform .2s ease; }` (subtle elevation; the card-link wraps everything).
        - `&__card:hover { border-color: color-mix(in srgb, var(--accent) 50%, transparent); }` — keep within `@media (prefers-reduced-motion: reduce) { transform: none; }`.
        - `&__card-link { display: contents; color: inherit; text-decoration: none; }` (allows the entire card to remain a single click target while preserving layout — or use `display: flex; flex-direction: column; flex: 1;` if `display: contents` causes unwanted semantic changes in older Safari — test both; fall back to a wrapping block link).
        - `&__card-media { aspect-ratio: 16 / 9; background: color-mix(in srgb, contrast-color(var(--main)) 8%, transparent); overflow: hidden; position: relative; }` (aspect-ratio keeps grids aligned even when image lazy-loads).
        - `&__card-img { width: 100%; height: 100%; object-fit: cover; display: block; }`
        - `&__placeholder--lead { display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; color: var(--_muted); background: color-mix(in srgb, var(--accent) 10%, transparent); font-size: 1.5rem; }` — accent-tinted soft block; the arrow glyph centers here.
        - `&__pinned { position: absolute; top: 0.75rem; left: 0.75rem; background: var(--accent); color: auto-contrast(var(--accent)); width: 1.75rem; height: 1.75rem; border-radius: 999px; display: inline-flex; align-items: center; justify-content: center; pointer-events: none; }` — small pin badge over the media's corner.
        - `&__card-body { padding: 1.25rem 1.5rem 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; flex: 1; }`
        - `&__card-cat { margin: 0; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--_muted); }`
        - `&__card-title { margin: 0; font-size: 1.5rem; line-height: 1.2; font-weight: 500; letter-spacing: -0.02em; }` (note: previously `h3` was sized 160% — for cards we use a fixed rem).
        - `&__card-excerpt { margin: 0; color: var(--_muted); font-size: 0.95rem; line-height: 1.5; }`
        - `&__card-author { display: flex; align-items: center; gap: 0.5rem; margin-top: auto; }` (margin-top: auto pushes meta to the bottom of the body for consistent card footers).
        - `&__avatar { width: 2rem; height: 2rem; border-radius: 999px; object-fit: cover; flex-shrink: 0; }`
        - `&__avatar--initials { display: inline-flex; align-items: center; justify-content: center; background: color-mix(in srgb, contrast-color(var(--main)) 12%, transparent); color: auto-contrast(var(--main)); font-size: 0.8125rem; font-weight: 600; }` — same shape as the avatar img, just text content.
        - `&__card-author-name { font-size: 0.875rem; color: var(--_muted); }`
        - `&__card-readmore { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.875rem; font-weight: 500; color: var(--accent); margin-top: 0.5rem; }` + arrow svg inherits `currentColor`.
        - Hover on the card raises the link accent: `&__card:hover &__card-title { text-decoration: underline; text-decoration-thickness: 1px; text-underline-offset: 0.2em; }` `&__card:hover &__card-readmore { gap: 0.5rem; }` (subtle motion; gate via `@media (prefers-reduced-motion: no-preference)` — actually `gap` transitions are fine without gating because gap changes aren't motion per se, but the existing block uses gates; follow existing pattern of putting transitions inside the no-preference block).
    - Why: Visual identity for the new lead zone.
    - Gates: a11y — focus ring on the card-link must be visible (use `&__card-link:focus-visible { outline: 2px solid var(--accent); outline-offset: -2px; }`); reduced-motion block already in the file extend to include the new transitions.
    - Dependencies: Step 9 (template output structured correctly).
    - Acceptance: Cards display in a responsive 1/2/3-column grid; image respects 16:9; placeholder fills the same space as the image; pinned badge renders top-left; focus ring shows on keyboard navigation of the card link.
    - Risk: Medium — `display: contents` semantics and `aspect-ratio` interaction with lazy-loaded images. Test in the editor preview and on the front end across the four browser targets Vite lists.

13. **Update list-row grid for thumbnail** (File: `assets/src/scss/blocks/_blog-posts.scss`)
    - Action: Update the existing `.blog-posts__link` rule:
        - Change mobile `grid-template-columns: 1fr auto` to `grid-template-columns: auto 1fr auto` when thumbnail present, or use an `[.blog-posts__link--thumb]` modifier class added by render.php conditionally. Simpler approach: define `&__link` with three columns unconditionally (`auto 1fr auto`) on ≥640px already, but add a new `&__thumb` cell styled as a square 64×64 with `object-fit:cover; border-radius:4px;`. For rows without a thumbnail, render a `<span class="blog-posts__thumb blog-posts__thumb--empty">` empty cell so the grid stays aligned (decision made in step 7).
        - Add spacing/alignment: `&__thumb { width: 4rem; height: 4rem; object-fit: cover; border-radius: 4px; flex-shrink: 0; @media (max-width: 639px) { display: none; } }` (hide thumbnail on mobile to keep rows compact on phones — preserves the existing mobile layout).
        - `&__thumb--empty { background: color-mix(in srgb, contrast-color(var(--main)) 8%, transparent); }` — visually empty slot for alignment.
        - `&__excerpt { margin: 0; font-size: 0.875rem; color: var(--_muted); line-height: 1.4; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }` — single-line clamp for editorial density.
        - Hide the date column on mobile is the existing behavior — keep it.
    - Why: Rows now have image support without losing the editorial identity.
    - Gates: a11y (thumbnails have alt text; the single-line truncate is for visual density only, content not hidden from screen readers because the `h3` title above it carries the meaning and is read in full).
    - Dependencies: Step 7
    - Acceptance: With thumbnails on, rows have a 64×64 image to the left and aligned titles/excerpts. With `showThumbnail` off, spacer cells keep the grid stable. On mobile (<640px) thumbnails are hidden and rows look identical to the current editorial style.
    - Risk: Low

14. **Add `.blog-posts__view-all` styles** (File: `assets/src/scss/blocks/_blog-posts.scss`)
    - Action: Inside the existing `.blog-posts` block:
        - `&__view-all { display: flex; justify-content: flex-end; padding-top: 2rem; border-top: 1px solid color-mix(in srgb, contrast-color(var(--main)) 15%, transparent); margin-top: 2rem; }`
        - `&__view-all-link { display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.95rem; font-weight: 500; color: var(--accent); text-decoration: none; &:hover { text-decoration: underline; text-underline-offset: 0.15em; } &:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; } }`
        - Reuse the same arrow svg class `&__link-icon` or apply styling locally — fine either way; prefer local styling to keep this a distinct affordance.
    - Why: Editorial closure.
    - Gates: a11y (focus ring; non-hover underline off but hover-underline on — already a WCAG-OK pattern; ensure focus-visible state has the outline explicitly).
    - Dependencies: Step 9
    - Acceptance: When shown, the link sits right-aligned at the section bottom. Hover/focus styles appear correctly.
    - Risk: Low

15. **Extend reduced-motion and forced-colors blocks** (File: `assets/src/scss/blocks/_blog-posts.scss`)
    - Action: In `@media (prefers-reduced-motion: reduce)` block, add `.blog-posts__card { transition: none; }` `.blog-posts__card-link, .blog-posts__view-all-link { transition: none; }`. In `@media (forced-colors: active)` block add `.blog-posts__card:focus-within { outline: 2px solid ButtonText; outline-offset: -2px; }` and `.blog-posts__view-all-link:focus-visible { outline: 2px solid ButtonText; outline-offset: 2px; }`. Add a forced-colors block to the card link as well: `.blog-posts__card-link:focus-visible { outline: 2px solid ButtonText; outline-offset: -2px; }` (parallel to the existing link rule).
    - Why: Maintain the a11y baseline established by the existing block.
    - Gates: a11y
    - Dependencies: Steps 12–14
    - Acceptance: With `prefers-reduced-motion: reduce` (DevTools → Rendering), no transitions animate on the cards/links. With `forced-colors: active` (Windows High Contrast), outlines normalize to ButtonText.
    - Risk: Low

16. **Stagger entrance animation across both zones** (File: `assets/src/scss/blocks/_blog-posts.scss`)
    - Action: Update the existing `@media (prefers-reduced-motion: no-preference) { .blog-posts__item { animation: kb-blog-rise … ; } }` to also apply the animation to `.blog-posts__card`. Optionally use a separate keyframe `kb-blog-card-rise` with a slightly larger initial translate (16px vs 8px) since cards are taller. Keep the same `--i` stagger mechanism.
    - Why: Visual continuity between the two zones for the entrance reveal.
    - Gates: a11y (only fires when no-preference; reuses existing pattern).
    - Dependencies: Steps 12–15
    - Acceptance: First viewport: cards stagger-rise over ~0.5s; rows below stagger-rise after.
    - Risk: Low

### Phase 7: Build, lint, manual verification

17. **Build assets and lint PHP** (Files: theme root)
    - Action: From the theme directory: `bun install && bun run build` (regenerates `assets/dist/` including the new view.js + editor.js + SCSS); then `composer install && composer lint:php` (validate render.php / index.js-adjacent PHP). Fix any escape-output warnings per AGENTS.md §7 (`phpcs:ignore` annotations on intentional `echo $rows_html` / `echo $lead_html` lines).
    - Why: Build assets are gitignored; build must succeed for the editor and frontend to load anything. PHPCS is the authoritative lint per AGENTS.md.
    - Gates: none
    - Dependencies: Steps 12–16
    - Acceptance: `bun run build` exits 0 and `assets/dist/js/blog-posts.js`, `blog-posts-view.js`, `assets/dist/css/app.css` (or similar) are updated. `composer lint:php` reports the standard 1-error/1-warning baseline per block PHP file (matching existing blocks); no NEW errors beyond that baseline.
    - Risk: Low

## Testing Strategy

**Automated/static:**
- `bun run build` from theme dir — must exit 0.
- `composer lint:php` from theme dir — must match the established 1-error/1-warning baseline for `inc/blocks/blog-posts/render.php` and `template.php`; no new findings.
- Optional `composer fix:php` to auto-fix trivial whitespace before re-linting.

**WP-CLI quick checks (run inside `ddev` at the parent Bedrock root with `--path=web/wp`):**
- `ddev wp eval 'has_image_size( "kb-blog-lead" );'` → `bool(true)`.
- `ddev wp eval 'var_dump( get_field( "kb_profile_image", "user_1" ) );'` to confirm avatar ACF field state (so we know whether cards will show image-vs-initials placeholders on the dev site).
- `ddev wp option get sticky_posts` then `ddev wp post sticky <id>` to set a sticky post for testing the lead-zone sticky promotion.
- `ddev wp option get page_for_posts` to confirm whether the "View all posts" link will render on the dev site.

**Manual flows (https://kim-branzell.ddev.site):**
1. Add the Blog Posts block to a page in the editor — confirm the new two-zone layout renders. Toggle each of the 9 new controls and confirm SSR preview updates.
2. With multiple published posts across several categories, click each filter chip; confirm:
   - Both lead and list zones filter together.
   - Empty-state message shows when no items match a filter.
   - Announcer region updates via screen reader.
3. Inspect the avatar in lead cards: confirm the ACF portrait renders (same-origin `<img>`); if no portrait, confirm initials placeholder renders.
4. Toggle `showThumbnail` off — lead cards get placeholder blocks, list rows lose the thumbnail cell but keep grid alignment.
5. Tab through the section: confirm focus order is header → chips → lead card 1 → card 2 → card 3 → list row 1 → row 2 → "View all" link. Confirm `:focus-visible` ring shows on cards, links, and the view-all affordance.
6. Run with DevTools "prefers-reduced-motion: reduce" — no animations or transitions.
7. Run with DevTools "forced-colors: active" — outlines normalize to ButtonText for all interactive elements.
8. DevTools Lighthouse / manually inspect: confirm CSP `default-src 'self'` doesn't break any avatar `<img>` (use same-origin path — no Gravatar URLs).
9. Test the post-count edge case: 0 published posts → block returns empty (unchanged). 1 post + leadCount=3 → single card, no list, no view-all link. 100 posts + postsToShow=6 leadCount=3 → 3 cards + 3 rows + view-all link to posts archive (if `page_for_posts` set).
10. Test sticky promotion: set 1 sticky post + 9 recent posts + leadCount=3 → first card has pinned badge, remaining 2 cards are newest non-sticky, 7 rows for the rest.

## Risks & Mitigations

- **Risk**: Sticky-promotion partitioning has edge cases (sticky post deleted/unpublished, sticky count > leadCount, sticky + leadCount = 0).
  → Mitigation: Clamp `leadCount` to `max(0, min($leadCount, 10))`. Filter sticky IDs against currently-queried `$posts` (don't get sticky IDs from option, then get_posts that include them — just let WordPress return them in the queried set since `ignore_sticky_posts => false` already surfaces sticky first). When `leadCount === 0`, render only the list (current behavior).
- **Risk**: `wp_get_attachment_image` falls back to the full-size image when the requested named size (`kb-blog-lead`) doesn't exist for already-uploaded images.
  → Mitigation: Tell Kim to run `ddev wp media regenerate --image_size=kb-blog-lead` once. Document this in the plan's open questions OR auto-run it for them after step 1; pick the safer route: include in the acceptance checklist for step 1 (already there). The fallback to a larger existing size is acceptable; `srcset` will choose an appropriate variant.
- **Risk**: ACF `kb_profile_image` field unset for author 1 → cards show initials placeholders.
  → Mitigation: Acceptable degradation. Could surface a warning to operators only. No action needed — initials are intentional graceful fallback.
- **Risk**: Strict CSP blocks any future code that tries to use the Gravatar URL returned by `kb_get_portrait_url()`.
  → Mitigation: The plan never calls `kb_get_portrait_url()` for inline rendering. The avatar helper built in step 3 uses `get_field()` + `wp_get_attachment_image()` only. Document this constraint in AGENTS.md so future agents don't reintroduce the bug.
- **Risk**: `display: contents` on `.blog-posts__card-link` could strip click semantics in some older Safari targets.
  → Mitigation: Vite targets Safari ≥ 16.4 where `display: contents` reliably preserves link semantics. If testing reveals issues, fall back to wrapping the entire card in the link with `display: flex; flex-direction: column; flex: 1` — preferred fallback syntax in the SCSS.
- **Risk**: PHPCS flags the new `echo $lead_html` line.
  → Mitigation: Use `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php.` annotation per AGENTS.md §7.
- **Risk**: The `wp_trim_words` excerpt could include shortcodes/block markup we don't want.
  → Mitigation: Always `wp_strip_all_tags( $post->post_content )` first, then `wp_trim_words( $stripped, $length )`. Manual `post_excerpt` is preferred when set.

## Open Questions / Blockers

- None blocking. The ACF `kb_profile_image` field is assumed set for author 1 but the block degrades gracefully to initials even when unset. If Kim wants to test avatar rendering before regenerating images, run `ddev wp media regenerate --image_size=kb-blog-lead` and ensure `kb_profile_image` is set on the author via the WP admin user edit screen.

## Rollback Considerations

- Revert the theme directory's `inc/blocks/blog-posts/` files to the previous commit's versions, remove the `kb-blog-lead` image size from `functions.php`, and `bun run build`. Existing pages that use the block will return to the editorial list-only layout. The `kb-blog-lead` image size on disk is harmless if left alone (it just isn't generated for new uploads) — safe to leave even when rolling back the PHP.
- Note: default-on new attributes (`showExcerpt`, `showThumbnail`, `showAuthor`, etc.) will visually change every existing placement of the block on the front end. This is the user's explicit request ("Default on for thumbnails") and the broader plan rolls the entire new visual together. If the user wants the *previous* visual on a specific page without losing the new attributes, they must toggle the new attrs off in the editor sidebar for that placement.

## Success Criteria

- [ ] `bun run build` from the theme directory exits 0 and updates `assets/dist/` (manifest, view, editor, CSS).
- [ ] `composer lint:php` reports the standard 1-error/1-warning baseline for the block PHP files; no new findings.
- [ ] The new `kb-blog-lead` image size is registered and visible to `has_image_size()`.
- [ ] The block's editor preview in the post editor shows a two-zone layout (lead cards above editorial list rows) with all 9 new toggles in the sidebar.
- [ ] Sticky posts are promoted to the top of the lead zone with a visible "pinned" badge and continue to respect `leadCount`.
- [ ] Avatar renders via ACF `kb_profile_image` attachment when present; initials placeholder renders otherwise; no Gravatar URLs are emitted (verified by inspecting the rendered HTML).
- [ ] Filter chips correctly hide/show both lead cards and list rows together; lead wrapper is hidden when no card matches; live announcer reports the correct count.
- [ ] "View all posts" link renders only when both `showViewAll` is on, a posts page is set, and more posts exist than rendered.
- [ ] Reduced-motion and forced-colors media queries produce the expected normative a11y behavior on the new cards/links.
- [ ] CSP `default-src 'self'` is unchanged and no console errors appear about blocked images.

## Checkpoint

- Last updated: 2026-08-02 01:30 UTC
- Status: done
- Completed steps: 1–17 (all)
- Files changed so far:
  - functions.php (added add_image_size)
  - inc/blocks/blog-posts/block.json (9 new attributes + description)
  - inc/blocks/blog-posts/render.php (full rewrite — query, partition, lead cards, updated rows, view-all)
  - inc/blocks/blog-posts/template.php (two-zone layout + view-all footer)
  - inc/blocks/blog-posts/view.js (filter covers both zones)
  - inc/blocks/blog-posts/index.js (9 new controls in 3 InspectorControls PanelBody groups)
  - assets/src/scss/blocks/_blog-posts.scss (lead grid, cards, avatars, row thumbnails, view-all, motion/FC)
  - AGENTS.md (items 17, 18, 19 added)
- Next step: none (all steps complete)
- Decisions/deviations:
  - Card-link uses `display: flex; flex-direction: column` (not `display: contents`) — safer cross-browser
  - List rows use empty spacer `<span>` when no thumbnail (not conditional grid column) — keeps alignment
  - Lead card placeholder uses ⚡ glyph (not ↗) — visual placeholder for missing image
  - Avatar always tries ACF `kb_profile_image` first; falls back to initials (never Gravatar URL per CSP constraint)
  - Row excerpt clamped to `min(8, max($excerpt_length, 20))` words (shorter than lead cards per plan)
  - `excerptLength` attribute added (default 28) — surface in editor sidebar as TextControl
- Blockers: none
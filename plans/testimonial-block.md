# Implementation Plan: Testimonial Block

## Overview

A new `kim-branzell/testimonial` block: a full-bleed color section with a large decorative quotation glyph, a bold pull quote, a thin horizontal rule, and attribution (name + role), visually modeled on the screenshot ("Northline gave us more than a new identity… — Mara Chen, Chief Growth Officer, Oriole Works"). Per user decisions it is a **multi-testimonial repeater** (N testimonials in the sidebar) rendered as a **single-slide carousel** with prev/next arrows + dot navigation, a **per-instance background picker** (`--main`, `--alternative`, `--accent`, `--buttons`; default `--buttons`), and an **optional author portrait** (small circle to the left of the attribution when present). No autoplay by default (vestibular-safety default); an optional autoplay toggle is exposed with ≥6s slide duration and pause-on-hover/focus/reduced-motion.

## Deliverable Type

**One-off block** added to this theme under `inc/blocks/testimonial/`. No plugin — this block follows the theme's existing manual-registration, SSR, Vite-entry conventions exactly like `blog-posts` and `intro`. Cross-theme portability is not a goal; brand palette + tokens are assumed.

## Confirmed Facts

- **Reference screenshot**: `/home/orthod0x/Pictures/Skärmbild från 2026-08-11 22-12-20.png`. Vision analysis returned: single-quote-centered full-bleed section, bright orange/coral background, **large dark double-quotation-mark glyph** top-left of the quote, thin horizontal rule between quote and attribution, attribution format `Name` newline `Role, Company`. No portrait visible, no nav controls visible, no eyebrow heading. (We are intentionally extending beyond the screenshot per user's "1.b / 2.c / yes" answers: repeater, background picker, optional portrait.)
- **Existing block file set**: every block is `{block.json, index.js, render.php, template.php}` + optional `view.js` when client-side interactivity is needed (verified: `inc/blocks/intro/`, `inc/blocks/about/`, `inc/blocks/blog-posts/`, `inc/blocks/project-index/`). The testimonial carousel needs `view.js`.
- **Manual registration in `inc/blocks/blocks.php`**: each block is registered with `wp_register_script( 'kb-<name>-editor', KB_DIST_URI . '/js/<name>.js', array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ), kb_asset_version( 'js/<name>.js' ), true )` + `register_block_type_from_metadata( __DIR__ . '/<name>/block.json', array( 'render_callback' => 'kb_render_block_<name>' ) )`. View scripts register the frontend handle with `array()` deps and the block's `block.json` `viewScript` field references that handle (see `blog-posts` lines 130–143 of `inc/blocks/blocks.php` and `block.json` `"viewScript": "kb-blog-posts-view"`).
- **Vite entries are explicit** in `vite.config.js` `rolldownOptions.input` (lines 60–73). Adding a block requires adding both `'<name>': r('./inc/blocks/<name>/index.js')` and `'<name>-view': r('./inc/blocks/<name>/view.js')` here, or assets never build. `@wordpress/*`, `react`, `react-dom`, `wp.*` are externals mapped to globals; vanilla DOM in view.js needs no imports.
- **SCSS block partials** are each explicitly `@use`d in `assets/src/scss/blocks.scss`. There is no `_index.scss`. New block SCSS = add `assets/src/scss/blocks/_testimonial.scss` + one `@use '@scss/blocks/testimonial' as *;` line in `assets/src/scss/blocks.scss`. This single file is loaded in BOTH frontend (via `app.scss` → `blocks.scss`) and editor (via `editor.scss` → `blocks.scss`).
- **`auto-contrast($c)`** at `assets/src/scss/base/_mixins.scss` L36–38 = `oklch(from $c round(1.21 - L) 0 0)`. For light-mode coral `--buttons` `#9A5238` (L≈0.42) → ~0.79 ≈ near-white text. For dark-mode yellow `--buttons` `#F9CA1C` (L≈0.84) → ~0.37 ≈ dark text. **This means the screenshot's "dark text on coral" is NOT what the theme's palette convention yields**; `auto-contrast(var(--buttons))` gives white-on-coral in light mode and dark-on-yellow in dark mode — both pass WCAG AA. The build should NOT override `auto-contrast` to chase the screenshot; the screenshot is a different brand.
- **CSP**: `inc/functions/headers.php` sends per-request `Content-Security-Policy` with no `'unsafe-inline'` on `script-src`/`style-src-elem`. Inline event-handler attributes (`onclick=` etc.) are blocked regardless of nonce. All carousel handlers MUST be attached via `addEventListener` in `view.js`. Inline `<script>`/`<style>` are auto-nonce-injected via the `script_loader_tag` filter; we do not need to add nonces ourselves.
- **No jQuery**: `inc/functions/cleanup.php` deregisters jQuery. Vanilla JS only.
- **`scroll-behavior: smooth`** is set unconditionally on `:where(html)` in `app.scss` (AGENTS.md §16). The testimonial block has no in-page anchor links, so this gotcha does not apply — but the carousel slide transition (CSS `transition: transform … opacity …`) MUST be disabled under `@media (prefers-reduced-motion: reduce)`.
- **Optional portrait — NOT a user profile lookup**: per AGENTS.md §17, `kb_get_portrait_url()` (Gravatar fallback) is CSP-blocked for inline `<img>`. The testimonial block's portrait is a **per-testimonial media-library upload** stored as `{ id, url, alt }` in the block attribute (same pattern as `intro.image` and `about.portrait`); resolve it via `wp_get_attachment_image( (int) $portrait['id'], array( 96, 96 ), false, array( 'class' => 'testimonial__avatar', 'loading' => 'lazy' ) )` and fall back to a plain `<img>` for external URLs, or simply omit the portrait when absent. NEVER call `kb_get_portrait_url()` or Gravatar here.
- **WAI-ARIA carousel pattern**: APG `aria-roledescription="carousel"` on the viewport with `aria-label`, slides as `role="group"` + `aria-roledescription="slide"` + `aria-label="N of M"`. Dots as `role="tablist"` with `role="tab"` + `aria-selected` + `aria-controls` referencing slide IDs. Prev/next as plain `aria-label`led `<button type="button">`. Arrow-key navigation operates when the carousel viewport receives focus (`tabindex="0"` on the viewport, or intercept on buttons and forward). Live region (`aria-live="polite" aria-atomic="true"`) announces slide changes for SR users. Non-active slides get `aria-hidden="true"` + `inert` (with `inert` polyfill awareness — see Risks) + `visibility: hidden` so they are not focusable.
- **wp_unique_id()** for stable aria IDs across multiple block instances on one page is the established pattern (see `intro/render.php` L45–46, `blog-posts/render.php` L22). Reuse it for heading id, viewport id, every slide id, every dot id, announcer linkages.
- **Editor JS pattern** (AGENTS.md §6): `wp.domReady(() => {...})` wrapper, `registerBlockType` inside try/catch with `console.error`, `wp.element.createElement` aliased as `el`, `Fragment`, `wp.serverSideRender` for canvas preview, `InspectorControls`/`PanelBody` for all sidebar fields, `MediaPlaceholder`/`MediaUpload`/`MediaUploadCheck` for the portrait picker, `save: () => null`. The `about/index.js` repeater (add/remove/move up/move down) is the exact template to follow for the testimonials array.
- **render.php escapes, template.php echoes pre-escaped** (AGENTS.md §7): build all HTML strings in render.php with `esc_html`/`esc_attr`/`esc_url`; template.php echoes them with `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php.` on each `echo $var;`.
- **PHPCS baseline** (AGENTS.md §8): new block PHP files share the accepted 1-error/1-warning lint profile (missing file doc comment on line 1 + unused `$content` param). Do NOT restructure function/file doc comments to chase a clean scan.
- **PHP 8.5 runtime, 8.1+ lint, min WP 6.6** — same as all other blocks.
- **No tests configured in this repo** (AGENTS.md Tests section): verification = `bun run build` succeeds + `composer lint:php` clean (modulo accepted baseline) + manual check in DDEV at https://kim-branzell.ddev.site.

## Assumptions

- **`inert` attribute browser support**: Chrome 102+, Firefox 112+, Safari 15.5+ — all within the Vite `browserslistToTargets` (`chrome >=111, firefox >=114, safari >=16.4`). No polyfill needed. Validator: the build agent can `grep inert` the rendered HTML after a manual slide change and confirm non-active slides carry the attribute.
- **No third-party carousel library**: vanilla JS DOM is the codebase convention (see `blog-posts/view.js`). Adding Swiper/Slick would conflict with no-jQuery + CSP. Use plain DOM + CSS transforms.
- **Autoplay is opt-in** (default `false`): WCAG 2.2.2 "Pause, Stop, Hide" requires that any motion <5s be pausable; we default off and require ≥6s slide interval; pause on `mouseenter`, `focusin`, and `prefers-reduced-motion: reduce` entirely disabling autoplay. Validator: with autoplay on, hover the section — slide should not advance.
- **Default background `buttons`** = the most on-brand match for the screenshot's color-block feel; the picker default in `block.json` is `"buttons"`. The user can flip to `main` for a cream/quiet section. Validator: drop the block on a page, confirm coral in light mode by default; switch the picker, confirm the section restyles.
- **Heading id on the `<section>`**: the block ships a hidden `<h2 class="sr-only">Testimonials</h2>` inner heading by default (provides an accessible name for the section per WCAG 2.4.6 / 1.3.1) with `aria-labelledby` on the section. Vision analysis found no visible eyebrow in the screenshot — we keep the heading visually hidden, not removed.

## Architecture Changes

| File | Change |
|------|--------|
| `inc/blocks/testimonial/block.json` | New. Defines `enabled`, `background` (enum main/alternative/accent/buttons), `autoplay`, `interval`, `testimonials[]` (each `{id, quote, authorName, authorRole, portrait{id,url,alt}}`). `editorScript: "kb-testimonial-editor"`, `viewScript: "kb-testimonial-view"`. |
| `inc/blocks/testimonial/render.php` | New. `kb_render_block_testimonial()` — escapes everything, builds slides + dots + controls HTML, includes `template.php`. |
| `inc/blocks/testimonial/template.php` | New. Echoes pre-escaped variables; declares the `aria-roledescription="carousel"` region with `<ul>` slides, controls row, live region. |
| `inc/blocks/testimonial/index.js` | New. Editor: repeater for testimonials (add/remove/move up/move down), per-testimonial portrait uploader (clone of `about/index.js` portrait UI), background `SelectControl`, autoplay + interval `ToggleControl`/`RangeControl` in a "Behavior" panel. `wp.serverSideRender` for canvas preview, `save: () => null`. |
| `inc/blocks/testimonial/view.js` | New. Vanilla JS carousel: click handlers on prev/next/dots, arrow-key navigation, `aria-selected`/`aria-hidden`/`inert` toggles, active-class rotation, optional autoplay loop with pause-on-hover/focus/reduced-motion, live-region announcement. No imports, no jQuery. |
| `assets/src/scss/blocks/_testimonial.scss` | New. SCSS skeleton matching AGENTS.md §5: `$g` self-reference, `--_muted` custom property, padding `5rem 0` mobile / `7rem 0` ≥640px, `@include container;` for inner, decorative `::before` quote glyph, divider via `color-mix(in srgb, contrast-color(var(--bg-token)) 20%, transparent)`, 4 modifier classes for backgrounds, dot/arrow button styles, full slide transition + `prefers-reduced-motion: reduce` block + `forced-colors: active` block normalizing outlines to `ButtonText`. |
| `inc/blocks/blocks.php` | Add a new "Testimonial block" section after the Blog Posts section (lines ~143): `require_once __DIR__ . '/testimonial/render.php';` + register `kb-testimonial-editor` script + register `kb-testimonial-view` script (deps `array()`) + `register_block_type_from_metadata( __DIR__ . '/testimonial/block.json', array( 'render_callback' => 'kb_render_block_testimonial' ) )`. |
| `vite.config.js` | Add two entries to `rolldownOptions.input`: `'testimonial': r('./inc/blocks/testimonial/index.js')` and `'testimonial-view': r('./inc/blocks/testimonial/view.js')`. |
| `assets/src/scss/blocks.scss` | Add `@use '@scss/blocks/testimonial' as *;` after the `blog-posts` line (line 21). |

No `functions.php`, no `theme.json`, no template files outside `inc/blocks/testimonial/`, no new image size, no new nav location, no new CPT.

## Implementation Steps

### Phase 1: Block scaffolding & registration

1. **Create block folder + `block.json`** (File: `inc/blocks/testimonial/block.json`)
   - Action: Create the directory and a `block.json` matching the schema of `inc/blocks/blog-posts/block.json` (apiVersion 3, `$schema`, `name: "kim-branzell/testimonial"`, `title: "Testimonial"`, `category: "design"`, `icon: "format-quote"`, `description`, `supports.align: ["wide","full"]`). Attributes:
     - `enabled` boolean default `true`
     - `background` string enum `["main","alternative","accent","buttons"]` default `"buttons"`
     - `autoplay` boolean default `false`
     - `interval` integer default `6000` (ms; clamp in render to `max(6000, …)` to stay ≥6s)
     - `testimonials` array default with **one** sample object shaped like the screenshot: `{ "id": "default", "quote": "Northline gave us more than a new identity. They gave our whole organization a clearer way to make decisions.", "authorName": "Mara Chen", "authorRole": "Chief Growth Officer, Oriole Works", "portrait": null }`
     - `"editorScript": "kb-testimonial-editor"`, `"viewScript": "kb-testimonial-view"`
   - Why: This is the canonical block metadata WP reads; `editorScript`/`viewScript` map to the handles we register in step 4.
   - Gates: none
   - Dependencies: None
   - Acceptance: `cat inc/blocks/testimonial/block.json | ddev wp eval 'var_dump( json_decode( file_get_contents( "php://stdin" ) ) );'` parses without error. Or just `php -l` is not applicable (JSON); use `jq . inc/blocks/testimonial/block.json`.
   - Risk: Low

2. **Create `render.php`** (File: `inc/blocks/testimonial/render.php`)
   - Action: Implement `kb_render_block_testimonial( array $attributes, string $content = '' ): string`. Pull `$enabled`, `$background`, `$autoplay`, `$interval`, `$testimonials`. Bail with `''` when `! $enabled` or `empty( $testimonials )`. Sanitize `$background` against the enum via `in_array( $background, [ 'main','alternative','accent','buttons' ], true )` (fall back to `buttons`). Clamp `$interval` to `max( 6000, min( (int) $interval, 30000 ) )`. For each testimonial:
     - `wp_unique_id( 'testimonial-slide-' )` for slide id
     - Escape `quote` with `esc_html`, `authorName` with `esc_html`, `authorRole` with `esc_html`
     - Resolve portrait: if `is_array($portrait) && ! empty($portrait['id'])`, use `wp_get_attachment_image( (int) $portrait['id'], array( 96, 96 ), false, [ 'class' => 'testimonial__avatar', 'loading' => 'lazy' ] )`. Else if `! empty($portrait['url'])`, build a plain `<img src="…" alt="…" class="testimonial__avatar" loading="lazy">` with `esc_url` + `esc_attr`. Else portrait HTML = `''`.
     - Build each slide `<li>` with `role="group" aria-roledescription="slide" aria-label="N of M" data-index="N" id="…"`; inner markup: `<span class="testimonial__quote-mark" aria-hidden="true">"</span>`, `<blockquote class="testimonial__quote"><p>…</p></blockquote>`, `<hr class="testimonial__divider">`, `<figcaption class="testimonial__attribution">` with optional `<figure class="testimonial__avatar-wrap">{portrait}</figure>` + `<span class="testimonial__author">…</span>` + `<span class="testimonial__role">…</span>`. First slide rendered active with `aria-hidden="false"`; others `aria-hidden="true"`.
     - Build dots `<button role="tab" id="dot-…" aria-controls="slide-…" aria-selected="true|false" data-index="N" class="testimonial__dot">` (first `aria-selected="true"`); wrap in `<div class="testimonial__dots" role="tablist" aria-label="Testimonials">`.
     - Build prev/next buttons with inline SVG icons (`<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24">…</svg>`) and localized `aria-label`s.
     - Build live region `<div class="testimonial__announcer screen-reader-text" aria-live="polite" aria-atomic="true"></div>`.
     - Build hidden heading `<h2 class="sr-only" id="testimonial-heading-…">` + `aria-labelledby` on the section.
     - `ob_start()` + `include __DIR__ . '/template.php'` + `return ob_get_clean()`.
   - Why: Server-side render keeps the editor preview and frontend identical; all escaping centralised here per AGENTS.md §7.
   - Gates: **a11y** (carousel semantics), **security** (escape every user-supplied string, sanitize the `background` enum)
   - Dependencies: Step 1
   - Acceptance: Block renders server-side with the default sample content when added to a page (manual check in DDEV). `composer lint:php` flags only the accepted baseline (missing top file doc + unused `$content`).
   - Risk: Medium (aria wiring is the trickiest part)

3. **Create `template.php`** (File: `inc/blocks/testimonial/template.php`)
   - Action: Document the variables passed from render.php in a header docblock (mirror `blog-posts/template.php` L1–23). Then echo the pre-escaped `$slides_html`, `$dots_html`, `$prev_button`, `$next_button`, `$heading_id`, `$viewport_id`, `$background_class`, `$section_aria_label`, `$autoplay_data`, `$interval_data`, `$total`. Top-level:
     ```php
     <section class="testimonial testimonial--<?= esc_attr( $background ); ?>"
              aria-labelledby="<?= esc_attr( $heading_id ); ?>"
              data-autoplay="<?= esc_attr( $autoplay ? 'true' : 'false' ); ?>"
              data-interval="<?= esc_attr( (string) $interval ); ?>"
              data-total="<?= esc_attr( (string) $total ); ?>">
       <div class="testimonial__inner">
         <h2 class="sr-only" id="<?= esc_attr( $heading_id ); ?>"><?= esc_html( $section_aria_label ); ?></h2>
         <div class="testimonial__viewport" id="<?= esc_attr( $viewport_id ); ?>"
              role="region" aria-roledescription="carousel"
              aria-label="<?= esc_attr( $section_aria_label ); ?>" tabindex="0">
           <ul class="testimonial__track" role="list">
             <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in render.php. ?>
             <?php echo $slides_html; ?>
           </ul>
         </div>
         <div class="testimonial__controls">
           <?php echo $prev_button; ?>
           <div class="testimonial__dots" role="tablist" aria-label="<?= esc_attr( $section_aria_label ); ?>">
             <?php echo $dots_html; ?>
           </div>
           <?php echo $next_button; ?>
         </div>
         <div class="testimonial__announcer screen-reader-text" aria-live="polite" aria-atomic="true"></div>
       </div>
     </section>
     ```
   - Why: Matches `blog-posts/template.php` echo-pre-escaped convention.
   - Gates: **a11y** (region/tablist roles), **security** (escape dynamic attributes)
   - Dependencies: Step 2
   - Acceptance: `php -l inc/blocks/testimonial/template.php` passes; output renders cleanly in DDEV.
   - Risk: Low

4. **Register block in `blocks.php`** (File: `inc/blocks/blocks.php`)
   - Action: Append after the Blog Posts section (after current line 143):
     ```php
     // ──────────────────────────────────────────────
     // Testimonial block
     // ──────────────────────────────────────────────
     require_once __DIR__ . '/testimonial/render.php';

     wp_register_script(
         'kb-testimonial-editor',
         KB_DIST_URI . '/js/testimonial.js',
         array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-element', 'wp-server-side-render' ),
         kb_asset_version( 'js/testimonial.js' ),
         true
     );

     wp_register_script(
         'kb-testimonial-view',
         KB_DIST_URI . '/js/testimonial-view.js',
         array(),
         kb_asset_version( 'js/testimonial-view.js' ),
         true
     );

     register_block_type_from_metadata(
         __DIR__ . '/testimonial/block.json',
         array(
             'render_callback' => 'kb_render_block_testimonial',
         )
     );
     ```
   - Why: No auto-discovery — manual registration is mandatory (AGENTS.md `blocks.php` notes).
   - Gates: none
   - Dependencies: Steps 1, 2
   - Acceptance: `ddev wp eval 'do_action("init");'` succeeds and `ddev wp block list` (or manual editor check) shows "Testimonial" in the inserter.
   - Risk: Low

### Phase 2: Editor experience

5. **Create `index.js` editor module** (File: `inc/blocks/testimonial/index.js`)
   - Action: Clone `inc/blocks/about/index.js` structure. `wp.domReady(() => {…})`, try/catch, `el = wp.element.createElement`. Components: `InspectorControls`, `PanelBody`, `TextControl`, `TextareaControl`, `SelectControl`, `ToggleControl`, `RangeControl`, `Button`, `MediaPlaceholder`, `MediaUpload`, `MediaUploadCheck`, `useBlockProps`. Sidebar panels:
     - **Panel: Appearance** — `SelectControl` for `background` (options main/alternative/accent/buttons with translated labels: "Cream (main)", "Tan (alternative)", "Brown (accent)", "Coral (buttons)"). Default `buttons`.
     - **Panel: Behavior** — `ToggleControl` "Autoplay slides" (default off), `RangeControl` "Seconds per slide" `min=6 max=30 step=1 value=interval/1000` (visible only when autoplay on). Help text: "Autoplay pauses on hover, focus, and when the visitor's system requests reduced motion."
     - **Panel: Testimonials** — repeater mirroring `about/index.js` L150–170 (map each testimonial into a nested `PanelBody` titled with `authorName || ('Testimonial ' + (i+1))`, with up/down/remove buttons, `TextareaControl` for `quote`, `TextControl` for `authorName`, `TextControl` for `authorRole`, optional portrait uploader (clone `about/index.js` L123–148 portrait UI). An "Add Testimonial" button at the bottom; min 1, max 6.
     - **Canvas**: when selected, render `ServerSideRender` preview wrapped in `.block-edit-form__preview` plus a minimal `.block-edit-form__section` with a help note ("Configure testimonials in the sidebar"); when not selected, render `ServerSideRender` only. `save: () => null`.
     - `const BG_OPTIONS = [ { label: __( 'Coral (buttons)', 'kim-branzell' ), value: 'buttons' }, { label: __( 'Cream (main)', 'kim-branzell' ), value: 'main' }, { label: __( 'Tan (alternative)', 'kim-branzell' ), value: 'alternative' }, { label: __( 'Brown (accent)', 'kim-branzell' ), value: 'accent' } ];`
     - Speak on add/remove/move via the existing `( wp.a11y && wp.a11y.speak )` fallback pattern (see `intro/index.js` L17–19 and `about/index.js` L17–19).
   - Why: One-on-one parity with `about` repeater pattern minimises risk and matches the established editor UX.
   - Gates: **a11y** (sidebar buttons must have `aria-label`s and remain keyboard operable; existing `about` pattern already does this)
   - Dependencies: Steps 1, 4
   - Acceptance: In the editor inserter you can add the block, edit each testimonial, add a second one, remove the first, change the background and see the preview update via `ServerSideRender`.
   - Risk: Low–Medium (repeater boilerplate, but well-trodden)

6. **Add Vite entries** (File: `vite.config.js`)
   - Action: Inside `rolldownOptions.input` (after the `blog-posts-view` line ~72), add:
     ```js
     testimonial: r('./inc/blocks/testimonial/index.js'),
     'testimonial-view': r('./inc/blocks/testimonial/view.js'),
     ```
   - Why: AGENTS.md Vite gotcha block — explicit entries are mandatory.
   - Gates: none
   - Dependencies: Steps 5, 7
   - Acceptance: `bun run build` succeeds and `assets/dist/manifest.json` contains `js/testimonial.js` and `js/testimonial-view.js` entries.
   - Risk: Low

### Phase 3: Frontend interactivity

7. **Create `view.js` carousel module** (File: `inc/blocks/testimonial/view.js`)
   - Action: Vanilla `document.addEventListener('DOMContentLoaded', …)` + `document.querySelectorAll('.testimonial')` (mirrors `blog-posts/view.js` exactly). For each section instance:
     - Cache: `viewport`, all `.testimonial__slide`, all `.testimonial__dot`, `prevBtn`, `nextBtn`, `announcer`.
     - State: `currentIndex = 0`, `total = slides.length`, `autoplay = section.dataset.autoplay === 'true'`, `interval = parseInt(section.dataset.interval, 10) || 6000`, `timerId = null`.
     - `activate(index, { announce = true } = {})`:
       - clamp `index` to `[0, total-1]`
       - for each slide: toggle `aria-hidden`, `inert`, `--is-active` class, `data-current` on parent
       - for each dot: `aria-selected` true/false, `tabindex` (0 for active, -1 for inactive — per APG tablist roving tabindex)
       - move the track via `translateX(${-index * 100}%)` (set on `.testimonial__track`)
       - if `announce && announcer`: clear + RAF-set `announcer.textContent` = `__('Slide', 'kim-branzell') + ' ' + (index+1) + ' ' + __('of', 'kim-branzell') + ' ' + total + ': ' + authorName`. **Translation caveat**: view.js has no `wp.i18n` dep (deps `array()`). To stay vanilla and translatable use the small `window.wp && wp.i18n && wp.i18n.__ ? … : …` fallback pattern — see Risks.
     - `next()` / `prev()` / `goto(i)`: bound click handlers on prev/next buttons and delegated click on `.testimonial__dots`.
     - **Arrow-key navigation**: `addEventListener('keydown', …)` on `viewport` (which has `tabindex="0"`): `ArrowRight`/`ArrowLeft`/`Home`/`End` call `next()`/`prev()`/`goto(0)`/`goto(total-1)` and `e.preventDefault()`. Do NOT intercept arrows globally — only when the viewport itself has focus.
     - **Autoplay**: if `autoplay && ! prefersReducedMotion`, start `setInterval(next, interval)`. Pause on `mouseenter`/`focusin` (clear interval), resume on `mouseleave`/`focusout` only if `autoplay` still on. `prefers-reduced-motion: reduce` ⇒ never start the timer.
     - **Cleanup**: a single MutationObserver is overkill for this repo; no SPA hydration. Skip.
     - WP nonce / CSP: all handlers via `addEventListener`; no inline handlers.
   - Why: One carousel file keeps everything vanilla and CSP-safe, matching the blog-posts precedent.
   - Gates: **a11y** (carousel keyboard nav, focus management, live region, pause-on-hover/focus/reduced-motion per WCAG 2.2.2 / 2.1.1 / 1.3.1 / 4.1.2), **security** (none — no user input, no fetch)
   - Dependencies: Steps 2, 3, 6
   - Acceptance: Tab to the section, focus the viewport, arrow keys move between slides; dots update `aria-selected`; live region announces "Slide 2 of 3: Mara Chen" (verifiable with a screen reader or Browser DevTools accessibility inspector). With autoplay ON and `prefers-reduced-motion` unset, hovering pauses the rotation.
   - Risk: High — carousel a11y is the most failure-prone surface in this block

### Phase 4: Styles

8. **Create `_testimonial.scss`** (File: `assets/src/scss/blocks/_testimonial.scss`)
   - Action: Follow AGENTS.md §5 block SCSS skeleton. Open `@use '@scss/base/mixins' as *;`. `.testimonial { $g: &; … }`:
     - Self-background modifier classes: `&--main { background: var(--main); color: auto-contrast(var(--main)); }`, `&--alternative { background: var(--alternative); color: auto-contrast(var(--alternative)); }`, `&--accent { background: var(--accent); color: auto-contrast(var(--accent)); }`, `&--buttons { background: var(--buttons); color: auto-contrast(var(--buttons)); }`. (Note: coral light-mode yields white text — Confirmed Fact above — do NOT override.)
     - `--_muted: color-mix(in srgb, currentColor 70%, transparent);` (muted elements inherit the auto-contrast color)
     - `padding: 5rem 0; @media (min-width: 640px) { padding: 7rem 0; }`
     - `&__inner { @include container; position: relative; }`
     - `&__viewport { overflow: hidden; focus-visible ring; tabindex=0 styling }` — `.testimonial__viewport:focus-visible { @include focus-ring; }`
     - `&__track { display: flex; transition: transform .45s ease, opacity .25s ease; will-change: transform; }`
     - `&__slide { flex: 0 0 100%; min-width: 0; padding: clamp(1.5rem, 4vw, 3rem) 0; position: relative; }` + `&[aria-hidden="true"] { visibility: hidden; }` (visibility hidden on hidden slide so it's not AT-discoverable even without `inert` working)
     - `&__quote-mark { position: absolute; top: -0.5rem; left: -0.25rem; font-family: 'Atkinson Hyperlegible Next', system-ui, serif; font-size: clamp(6rem, 18vw, 11rem); line-height: 1; opacity: 0.18; color: currentColor; pointer-events: none; user-select: none; } &::before { content: '\201C'; }` (large decorative left double-quote glyph, dimmed)
       - On `<=640px` keep the glyph but reduce to `clamp(4rem, 16vw, 7rem)` and pull slightly more inward so it never escapes the container edge (no horizontal scroll per WCAG 1.4.10).
     - `&__quote { margin: 0; max-width: 38ch; font-family: 'Atkinson Hyperlegible Next', system-ui, sans-serif; font-weight: 600; font-size: clamp(1.5rem, 3.5vw, 2.75rem); line-height: 1.2; letter-spacing: -0.02em; p { margin: 0; } }`
     - `&__divider { width: 100%; max-width: 32rem; margin: 2rem 0 1.5rem; border: 0; border-top: 1px solid color-mix(in srgb, currentColor 25%, transparent); }`
     - `&__attribution { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }`
     - `&__avatar-wrap { flex: 0 0 auto; } &__avatar { width: 3rem; height: 3rem; border-radius: 50%; object-fit: cover; }`
     - `&__author { display: block; font-weight: 600; font-size: 1.0625rem; }`
     - `&__role { display: block; color: var(--_muted); font-size: 0.9375rem; }`
     - `&__controls { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-top: clamp(1.5rem, 4vw, 2.5rem); padding-top: 1.25rem; border-top: 1px solid color-mix(in srgb, currentColor 18%, transparent); }`
     - `&__dots { display: inline-flex; gap: 0.5rem; role="tablist" needs no extra styling }`
     - `&__dot { background: transparent; border: 0; width: 0.5rem; height: 0.5rem; padding: 0; border-radius: 50%; background: color-mix(in srgb, currentColor 30%, transparent); cursor: pointer; transition: background-color .2s ease, transform .2s ease; &[aria-selected="true"] { background: currentColor; transform: scale(1.25); } &:focus-visible { @include focus-ring; } }`
       - Min hit target: wrap each dot's hit area by giving the `.testimonial__dot` `min-width: 44px; min-height: 44px; padding: 18px;` per AGENTS.md touch-target / WCAG 2.5.5, OR overlay a transparent `::before { content: ''; position: absolute; inset: -14px; }` on the dot (with `position: relative` on the dot). Use the `::before` overlay so the visible dot stays 8px.
     - `&__prev, &__next { @include touch-target sizing; background: transparent; border: 1px solid color-mix(in srgb, currentColor 30%, transparent); width: 2.75rem; height: 2.75rem; border-radius: 50%; display: grid; place-items: center; cursor: pointer; color: inherit; svg { width: 1.25rem; height: 1.25rem; } &:hover { background: color-mix(in srgb, currentColor 12%, transparent); } &:focus-visible { @include focus-ring; } }`
     - `@media (prefers-reduced-motion: reduce) { &__track { transition: none; } &__dot, &__prev, &__next { transition: none; } }` — also a runtime autoplay guard in view.js (already covered in step 7).
     - `@media (forced-colors: active) { &__prev, &__next, &__dot { outline: 1px solid ButtonText; } &__divider, &__controls { border-color: ButtonText; } }`
     - `.sr-only { ... }` — confirm `_reset.scss` or a shared file already provides `.sr-only`/`.screen-reader-text`; if not, add the standard clip rule (the blog-posts block relies on `.screen-reader-text` which is WP core's class; we use the same class name in template.php for the announcer, and `.sr-only` for the hidden heading — verify both exist). Validator: `grep -n 'sr-only\|screen-reader-text' assets/src/scss/base/_reset.scss`.
   - Why: One-stop styling; matches the established block SCSS conventions; forces the auto-contrast color choice and the reduced-motion / forced-colors rules per AGENTS.md §5/§16.
   - Gates: **a11y** (focus rings, dot hit targets ≥44px, reduced-motion, forced-colors)
   - Dependencies: None (styles can be drafted before/with template.php)
   - Acceptance: Visual check in DDEV in light + dark mode for each of the 4 backgrounds: text remains legible at ≥4.5:1 contrast (use the browser DevTools contrast checker). No horizontal scroll at 320px. Decorative glyph does not overflow.
   - Risk: Medium (auto-contrast threshold for each background can produce surprising text colors — verify each)

9. **Add `@use` to `blocks.scss`** (File: `assets/src/scss/blocks.scss`)
   - Action: Append after the `blog-posts` line (line 21): `@use '@scss/blocks/testimonial' as *;`
   - Why: No `_index.scss` in `blocks/` — explicit `@use` per AGENTS.md §14.
   - Gates: none
   - Dependencies: Step 8
   - Acceptance: `bun run build` succeeds; the compiled CSS includes `.testimonial { … }`.
   - Risk: Low

### Phase 5: Verification

10. **Lint + build + manual smoke**
    - Action: From inside the theme directory run `composer lint:php && bun run build`. Manual smoke test in DDEV:
      - Front-end: add the block to a page (or simply drop in `front-page.php` via the editor in DDEV), publish, and view at `https://kim-branzell.ddev.site`.
      - Default background: confirm coral in light mode with white text → open DevTools "Elements → Accessibility" pane, verify the slide region exposes `aria-roledescription="carousel"` and each slide exposes `aria-roledescription="slide"`.
      - Switch background to `main` (cream) — confirm text is dark (auto-contrast → black) and contrast ≥ 4.5:1.
      - Switch background to `accent` (dark brown light-mode) — confirm white text.
      - Switch background to `alternative` (tan) — confirm dark text.
      - Keyboard: tab to the section, focus the viewport, press `ArrowRight`/`ArrowLeft` — slides change, live region announces ("Slide N of M: <author>"), dots update `aria-selected`.
      - Add a second testimonial in the editor; confirm two dots appear.
      - Enable autoplay + 7s interval; reload; confirm auto-advance every 7s; hover the section — confirm pause; press `Esc` while focused — confirm pause (per APG). Toggle OS reduced-motion (Firefox `about:config` `layout.css.prefers-reduced-motion.content` → 1) — confirm autoplay never starts.
      - With VoiceOver/NVDA: navigate by `h2` then into the carousel; confirm "Testimonials, carousel region, 1 of N, slide" role description is read.
    - Why: WCAG 2.2 conformance + project conventions.
    - Gates: **a11y** (the phase itself IS an a11y gate), **security** (re-run `composer lint:php`)
    - Dependencies: All prior steps
    - Acceptance: `composer lint:php` clean (modulo accepted baseline); `bun run build` succeeds; manual checks above all pass. An a11y specialist review (`wcag-22` skill) is recommended before merging.
    - Risk: Low (catching bug fixes only)

## Testing Strategy

- **Build**: `bun run build` (must succeed; `assets/dist/manifest.json` must list `js/testimonial.js` and `js/testimonial-view.js`).
- **PHP lint**: `composer lint:php` (must report only the accepted baseline — missing top file comment on render.php + unused `$content` — no new warnings/errors).
- **Manual in DDEV**: navigate to `https://kim-branzell.ddev.site`, edit a page in the block editor, insert "Testimonial", configure per Acceptance criteria above, save, view frontend. Repeat per background value. Repeat with 1 and ≥2 testimonials.
- **Keyboard**: full keyboard cycle focusing the carousel viewport, arrow keys, Home/End, Tab between dots + prev/next, Escape pause (if autoplay on).
- **Screen reader**: NVDA/VoiceOver pass; verify `aria-roledescription` announcements and live-region slide change announcements.
- **Reduced motion**: OS `prefers-reduced-motion: reduce` → CSS transitions off; autoplay never starts.
- **Forced colors / HC mode**: Windows High Contrast — confirm outlines/borders normalize to `ButtonText` and the decorative `"` glyph remains visible at the muted 18% color (or becomes text-currentColor under HC).
- **Contrast audit**: for each of the 4 backgrounds, sample the foreground color and run the DevTools contrast checker — all body text ≥4.5:1, decorative divider ≥3:1 (SC 1.4.11).
- **No automated test runner** exists in this repo (see AGENTS.md Tests section); do not add one for this block.

## Risks & Mitigations

- **Risk**: `auto-contrast()` produces white text on coral light-mode (screenshot shows dark) → user may reject. **Mitigation**: call this out explicitly before merge; if the user prefers dark text on coral (low contrast — would fail WCAG AA), the only correct fix is a palette change (AGENTS.md §21 forbids chasing contrast by overriding auto-contrast selectively). Document this tradeoff in the PR description.
- **Risk**: `inert` browser support — assumed modern (Chrome 102+/FF 112+/Safari 15.5+); the Vite browserslist includes ≥111/≥114/≥16.4, so OK. **Mitigation**: test in DDEV default browser; if `inert` is silently ignored, the slides also carry `aria-hidden="true"` and CSS `visibility: hidden`, so they remain unreachable by Tab.
- **Risk**: `wp.i18n.__` not available in `view.js` (deps `array()`) — the live-region announcement strings would be untranslatable. **Mitigation**: use the `window.wp && wp.i18n && wp.i18n.__` runtime guard pattern (already used for `wp.a11y.speak` in `intro/index.js` L17–19); if unavailable, fall back to English literals. Acceptable for this single block; revisit if the theme ships i18n as a hard requirement.
- **Risk**: Multiple testimonial block instances on one page share the same `_testimonial.scss` CSS and the same `view.js` `DOMContentLoaded` sweep — they must not interfere with each other. **Mitigation**: all selectors in `view.js` are scoped via `section.querySelectorAll` (per-instance, mimics `blog-posts/view.js`). All aria IDs use `wp_unique_id()` per instance.
- **Risk**: The decorative `"` glyph at `clamp(6rem, 18vw, 11rem)` can overflow the viewport on very narrow screens or non-Latin fonts. **Mitigation**: cap to `16vw` on `<=640px` and pull `left: -0.25rem` toward the inner padding edge (not outside the container); use `overflow: hidden` on `&__viewport` so it never causes horizontal scroll. Visually verify at 320px.
- **Risk**: Carousel reflow at 400% zoom (200%×200% per WCAG 1.4.10) — viewport may become very narrow; slides must still be statable without horizontal scroll. **Mitigation**: single-column slide on `<=640px`; controls wrap with `flex-wrap: wrap`; decorative glyph clamped.
- **Risk**: Editor `ServerSideRender` preview uses the live block attributes; if `interval` becomes invalid (NaN), the carousel state breaks in the editor preview. **Mitigation**: clamp in `render.php` to `max(6000, min((int)$interval, 30000))`; in `index.js` use `RangeControl` constraints (`min=6, max=30`) and convert seconds→ms before saving.

## Open Questions / Blockers

None material. One user-visible tradeoff to flag in the PR description: the screenshot's "dark text on coral" is incompatible with the theme palette's WCAG AA contrasts — the build will yield **white text on coral** in light mode (auto-contrast) and dark on yellow in dark mode. The user has already accepted the per-instance background picker (decision 2.c); this is a heads-up, not a blocker.

## Rollback Considerations

- The block is entirely additive. Rollback = revert the new files (`inc/blocks/testimonial/`, `assets/src/scss/blocks/_testimonial.scss`), and revert the additions to `blocks.php`, `vite.config.js`, and `assets/src/scss/blocks.scss`. No data migration, no schema change, no CPT. Existing pages may show a "This block has been deleted" placeholder after rollback; safe if the block is removed from those pages first.
- No option in the DB; no ACF field group registered; no cron job.

## Success Criteria

- [ ] `kim-branzell/testimonial` block appears in the inserter under the `design` category.
- [ ] Editor supports a repeater of 1–6 testimonials with quote/authorName/authorRole/optional portrait; reorder/up/down/remove + add.
- [ ] `background` picker exposes 4 palette options; default `buttons`; each yields visible text at WCAG AA (≥4.5:1 body, ≥3:1 dividers).
- [ ] Frontend carousel: prev/next buttons + dot tablist navigation; keyboard arrows + Home/End operable when the viewport is focused.
- [ ] Active slide exposes `aria-roledescription="slide"` + `aria-label="N of M"`; non-active slides are `aria-hidden="true"` and `inert`.
- [ ] Live region announces slide transitions to SR users.
- [ ] `prefers-reduced-motion: reduce` disables CSS transitions and prevents autoplay from starting.
- [ ] Autoplay (opt-in) pauses on `mouseenter`/`focusin` and uses ≥6s interval by default.
- [ ] `forced-colors: active` normalises outline/border to `ButtonText`.
- [ ] `composer lint:php` reports only the accepted 1-error/1-warning baseline; `bun run build` succeeds.
- [ ] No inline event-handler attributes in the rendered HTML (CSP-safe).
- [ ] Manual screen-reader pass (VoiceOver or NVDA) confirms role/label/announcement behavior.
- [ ] AGENTS.md updated with the `auto-contrast(--buttons)` (white-on-coral) durable observation under the color-contrast conventions section.

## Checkpoint

- Last updated: 2026-08-11
- Status: done
- Completed steps: 1–10 (all)
- Files changed so far: inc/blocks/testimonial/{block.json,render.php,template.php,index.js,view.js}, inc/blocks/blocks.php, vite.config.js, assets/src/scss/blocks.scss, assets/src/scss/blocks/_testimonial.scss
- Next step: none
- Decisions/deviations: User decisions captured — 1.b (repeater + carousel), 2.c (per-instance background picker defaulting `buttons`), 3.yes (optional portrait). Build decisions: render.php used `$_content` for unused param (matches codebase convention); `$is_first` bool for active/hidden logic; `wp.a11y.speak` stub included in index.js; `.sr-only` class duplicated in _testimonial.scss (self-contained block pattern); English literals for live-region announcements in view.js (no wp.i18n dep, consistent with blog-posts/view.js).
- Blockers: none
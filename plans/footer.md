# Implementation Plan: Footer (full content, ACF-managed)

## Overview
Rewrite `footer.php` from a near-empty stub (only theme switcher) into a complete, ACF-Options-managed site footer that appears on every page type. A merged B+C layout: an editorial brand statement plus multi-column nav/contact/social columns on top, over a slim legal ribbon holding copyright, legal links, back-to-top, and the existing Light/System/Dark theme switcher. A "prepare-now / enable-later" newsletter slot is included (hidden by default, rendered via a toggle + `brnzl-forms` embed when ready). All content is editable by the site admin without touching code.

## Deliverable Type
**One-off theme code** (not a separate plugin). Footer markup is presentation tightly coupled to this theme's palette tokens (`var(--main)`, `auto-contrast()`, `@include container`), its ACF registrations pattern, its existing `footer_menu` nav location, and its CSP/escaping conventions. Reusing the theme infrastructure (ACF Pro already a Composer dep, `footer_menu` already registered) keeps the surface area minimal and matches how `inc/functions/*.php` is organized.

## Confirmed Facts
- `footer.php` currently calls `wp_footer();` then renders `<footer>` containing only the theme switcher (Light/System/Dark radio group). See `footer.php` lines 1–39.
- `footer_menu` nav location is **registered** (`functions.php` lines 23–28, `register_nav_menus`) but **used nowhere** — ready to consume.
- ACF Pro is installed as a Composer dependency `wpengine/advanced-custom-fields-pro` (parent `composer.json`); `acf_add_options_page()` / `acf_add_local_field_group()` are available. Existing local-field-group registration pattern: `kb_register_acf_field_groups()` in `inc/functions/cpt.php` lines 142–184, hooked on `acf/init`.
- `functions.php` `require_once`s every `inc/functions/*.php` manually (lines 69–77) — new file must be added there.
- The `brnzl-forms` plugin (`web/app/plugins/brnzl-forms`) is a custom, GDPR-compliant form builder by the same author — the intended provider for the future newsletter form (shortcode or block).
- Footer appears on every page type already via `get_footer()` calls in `front-page.php`, `index.php`, `search.php`, `single-project.php` (and any singular template). No new template wiring needed.
- **Icon pattern**: in-codebase precedent is **inline SVG** authored in PHP render files (see `inc/blocks/project-index/render.php` lines 52–53 `internal_svg`/`external_svg`, and `inc/blocks/blog-posts/render.php` line 46 `arrow_svg`). Each `<svg>` carries its own `viewBox`, `aria-hidden="true"`, `focusable="false"`, `stroke="currentColor"`.
- A sprite `kb_add_svg_defs()` exists in `inc/functions/filters.php` (hooked `wp_footer`) defining `#rss-svg`, `#mastodon-svg`, `#bluesky-svg` — but it is **orphaned**: no `<use href="#...">` reference exists anywhere in the theme, and the defs use inconsistent coordinate spaces (RSS path is ~500 000-scale, others 0–512). **Do not extend it** for this task — use inline SVG per the established block pattern. (Cleanup of the orphaned sprite is out of scope.)
- **Palette/tokens** (`assets/src/scss/base/_palette.scss`, `_colors.scss`, `_mixins.scss`): role-based `--main`, `--alternative`, `--accent`, `--buttons` via `light-dark()`; `auto-contrast($color)` mixin returns contrast-flipped foreground automatically; `@include container` (max-width 1600px, 1rem gutters); `@include smartphone {…}` / `@include desktop {…}` breakpoints (767px / 1023px). Section padding precedent: blocks use `5rem 0` mobile / `7rem 0` ≥640px. Dividers via `color-mix(in oklch, auto-contrast(var(--…)) 12–25%, transparent)` (see `_intro.scss` lines 78–82).
- **SCSS include convention**: `assets/src/scss/app.scss` `@use`s component partials explicitly (`@use '@scss/components/search' as *;`, `@use '@scss/components/project-single' as *;`). There is **no** `_index.scss` in `components/` or `blocks/` — each new partial must be added to `app.scss` with an explicit `@use` line. Vite auto-prepends `@use "@scss/base/tokens" as *;` to every SCSS file (see `vite.config.js` line 36) — tokens/mixins are globally available, no manual import needed in the new file.
- **CSP** (`inc/functions/headers.php`): `default-src 'self'`, `script-src 'self' 'nonce-…'` (no `'unsafe-inline'`), `form-action 'self'`. Implications: external social links need `target="_blank"` + `rel="noopener external"`; mailto/tel need no extra rel; any future newsletter `<form action>` MUST be same-origin (`form-action 'self'`) or served via `brnzl-forms` AJAX — DO NOT point a raw form at an external ESP domain.
- **CSP `<use href="#id">`** (not used here, noted for awareness): same-document fragment refs don't violate `default-src 'self'`. Not relevant since we go inline.
- Site language/UI strings are **Swedish**, text domain `kim-branzell`. Existing strings: "Ljust/System/Mörkt", "Meny", "Du är här", "Hoppa till innehållet", "Läs mer". Footer copy MUST be Swedish, wrapped in `__()`/`esc_html__()`/`esc_attr_e()` with the `kim-branzell` domain.
- `app.scss` sets `html { scroll-behavior: smooth; }` **unconditionally** (line ~20) — this is an existing a11y gap (vestibular). A back-to-top anchor would smooth-scroll regardless of `prefers-reduced-motion`. The plan includes a one-line reduced-motion guard as part of the footer SCSS step.
- **PHPCS**: `composer lint:php` scans `functions.php` + `inc/`. Existing `inc/functions` files have varied file-doc-block styles (cpt.php has one; enqueues.php does not). Function doc blocks are encouraged but the codebase tolerates a small baseline of warnings. Do NOT add a file-level `@package` doc block just to chase zero errors if sibling files omit one — match nearest neighbor's style.

## Assumptions
- ACF Pro version installed supports `add_options_page`-style `acf_add_options_page()` (it has since ACF Pro 5.0+; the composer constraint is `*` so it pulls latest). **Validate**: builder runs `ddev wp plugin list | grep advanced-custom-fields-pro` and confirms "active". If somehow not active, the ACF field-registration function early-returns (guard `function_exists('acf_add_options_page')`) and footer falls back to defaults — no crash.
- The site admin will populate the `footer_menu` (WP admin → Appearance → Menus → "Footer Menu" location) and the Footer Options page after deploy. Until then the footer renders gracefully (tagline defaults to `get_option('blogdescription')`, nav section omitted if no menu assigned, social list omitted if repeater empty, legal list omitted if empty). **Validate**: load footer with zero ACF options set — no PHP warnings, no empty `<ul>`s, no broken markup.
- Simple Icons (https://simpleicons.org) single-path 24×24 viewBox SVG data is acceptable to inline for LinkedIn, Instagram, GitHub, X, YouTube, Threads, Facebook. (RSS, Mastodon, Bluesky already exist in the orphaned sprite but we are NOT reusing it; provide fresh 24×24 paths for these too for consistency so all footer icons share one viewBox.) **Validate**: builder opens one SVG in the editor canvas / frontend and confirms it renders at correct size & inherits `currentColor`.
- The future newsletter form will be delivered by `brnzl-forms` shortcode/HTML pasted into an ACF textarea. Running `do_shortcode()` on that admin-controlled field is acceptable under the trust model (only `manage_options` users edit Options pages). **Validate**: confirm `current_user_can('manage_options')` is required to load the Options page (ACF default) — it is.
- Default footer background is `var(--main)` with `color: auto-contrast(var(--main))` (matching hero/section convention) or `var(--alternative)` (matching intro). **Pick `var(--alternative)`** so the footer reads as a distinct band from `<main>` (which uses `var(--alternative)` per `app.scss`) — actually `<main>` already uses `--alternative`; using `--main` for the footer gives the strongest visual separation. **Final: footer background `var(--main)`, foreground `auto-contrast(var(--main))`**, divider lines `color-mix(in oklch, auto-contrast(var(--main)) 15%, transparent)`. **Validate**: builder screenshots footer in light + dark + 200% zoom and confirms ≥4.5:1 body text and ≥3:1 large text and UI components.

## Architecture Changes
- **NEW** `inc/functions/footer-options.php` — registers the "Site Settings → Footer" ACF Options sub-page and all footer fields; provides `kb_footer_option( $key, $default )` helper (cached) and `kb_get_footer_social()` / `kb_get_footer_legal_links()` accessors (return pre-sanitized arrays). Hooked on `acf/init`.
- **NEW** `inc/functions/footer-render.php` — provides `kb_render_footer(): string` returning fully-escaped footer HTML (mirrors the block `render.php` convention: build strings with `esc_*()` at construction time; template echoes pre-escaped vars). Contains the platform→SVG map (inline SVG, 24×24, `aria-hidden`).
- **NEW** `assets/src/scss/components/_footer.scss` — full footer layout + reduced-motion `scroll-behavior` guard. `@use`d from `app.scss`.
- **MODIFY** `footer.php` — replace the bare `<footer>`+switcher stub with `wp_footer();` then `<?php echo kb_render_footer(); // phpcs:ignore … pre-escaped`. (Inline `kb_render_footer()` output bypasses EO PHPCS rule by design, per AGENTS.md block convention §7.)
- **MODIFY** `functions.php` — add two `require_once` (footer-options, footer-render) near the other `inc/functions` requires (lines 69–77 area).
- **MODIFY** `assets/src/scss/app.scss` — add `@use '@scss/components/footer' as *;` next to the existing search/project-single `@use`s.
- **MODIFY** `inc/functions/site-menu.php` *optional* — `wp_nav_menu()` handles `footer_menu` natively; no change required. Only touched if a custom fallback callback is desired. (Default: no change.)
- **No build-config changes** — no new JS entrypoint needed (the theme switcher JS already ships in `assets/src/js/app.js`; footer is pure markup + CSS). No `vite.config.js` edit. No block registration. `bun run build` will pick up the new SCSS via the app entry automatically.

## Field set (ACF Footer Options page)
Swedish labels shown; builder may refine wording.

| Key | Type | Label (sv) | Default / fallback | Notes |
|-----|------|-----------|--------------------|-------|
| `footer_tagline` | Textarea | Varumärkes-text | `get_option('blogdescription')` | Big brand statement, ~1–2 sentences. Escaped with `wp_kses_post` (allows `a, br, em, strong, span`). |
| `footer_contact_email` | Email | Kontakt e-post | — | Renders `mailto:` link with `esc_url( 'mailto:' . $email )`. Omitted if empty. |
| `footer_contact_phone` | Text | Telefon (valfritt) | — | Renders `tel:` link (strip non-digits for href, display as authored). `esc_url('tel:…')`. |
| `footer_location` | Text | Ort / plats | — | Plain text line. `esc_html`. |
| `footer_social_links` | Repeater | Sociala länkar | — | Sub-fields: `platform` (Select: linkedin, instagram, github, x, youtube, threads, facebook, mastodon, bluesky, rss), `url` (URL). Each renders an icon link. |
| `footer_show_newsletter` | True/False | Visa nyhetsbrev | `false` | Prepare-now toggle. When true, renders the newsletter block. |
| `footer_newsletter_heading` | Text | Rubrik | "Nyhetsbrev" | Conditional on toggle. |
| `footer_newsletter_text` | Textarea | Beskrivning | — | Conditional. `wp_kses_post` (basic tags). |
| `footer_newsletter_embed` | Textarea | Inbäddning (shortcode/HTML) | — | When non-empty, passed through `do_shortcode()`. Instructions: "Klistra in brnzl-forms-shortcode när formuläret är klart." Admin-only. |
| `footer_colophon` | Textarea | Kolofon (valfritt) | — | e.g. "Byggd med WordPress av …". `wp_kses_post` min tag set. |
| `footer_legal_links` | Repeater | Juridiska länkar | — | Sub-fields: `label` (Text), `url` (URL). Up to ~3 (Privacy/Cookies/Imprint). Rendered in bottom bar. |
| `footer_copyright_override` | Text | Copyright-override | `© {YYYY} {site title}` | If empty, auto-generate. `esc_html` (no raw HTML). |
| `footer_show_back_to_top` | True/False | Visa "Till toppen" | `true` | Renders back-to-top anchor to `#main-content`. |

Acf options-page registration must use a parent "Site Settings" page (`acf_add_options_page`) + sub-page "Footer" (`acf_add_options_sub_page`) so future per-area settings pages nest cleanly. Page slug: `kb-site-settings` / `kb-footer-settings`.

## Layout (merged B + C)

```
<footer class="site-footer" aria-labelledby="site-footer-heading">
  <h2 id="site-footer-heading" class="screen-reader-text">Sidfot</h2>   ← landmark label

  <div class="site-footer__inner">                       ← container (1600px)

    <div class="site-footer__top">                       ← upper tier grid
      <div class="site-footer__brand">
        <a class="site-footer__brand-link" href="{home}">{logo svg inline}<span>{site name}</span></a>
        <p class="site-footer__tagline">{tagline}</p>
        <ul class="site-footer__social" role="list">      ← omitted if no social
          <li><a href="{url}" class="site-footer__social-link" rel="noopener external" target="_blank" aria-label="{Platform} — öppnas i nytt fönster">{inline svg}</a></li>
          …
        </ul>
      </div>

      <nav class="site-footer__nav" aria-label="{Footer navigation}">
        <h3 class="site-footer__heading">Navigering</h3>
        {wp_nav_menu footer_menu depth=1 menu_class=site-footer__nav-list fallback=__return_empty_string}   ← omitted entirely if no menu
      </nav>

      <div class="site-footer__contact">
        <h3 class="site-footer__heading">Kontakt</h3>
        <ul class="site-footer__contact-list" role="list">
          {email, phone, location items}
        </ul>

        {if footer_show_newsletter}
        <div class="site-footer__newsletter">
          <h3 class="site-footer__heading">{newsletter_heading}</h3>
          <p class="site-footer__newsletter-text">{newsletter_text}</p>
          {do_shortcode(newsletter_embed) OR stub "Kommer snart" placeholder when embed empty}
        </div>
        {/if}
      </div>
    </div>

    <div class="site-footer__bottom">                     ← legal ribbon
      <p class="site-footer__copyright">{copyright}</p>
      <ul class="site-footer__legal" role="list">
        {footer_legal_links as <li><a rel="noopener external" target="_blank" href=…>label</a></li>}   ← internal URLs get no target/rel
        {footer_colophon as <p class="site-footer__colophon">…</p>}
      </ul>
      <div class="site-footer__utilities">
        {if footer_show_back_to_top}<a class="site-footer__back-to-top" href="#main-content">{up svg}<span>Till toppen</span></a>{/if}
        <div class="theme-toggle"> {theme switcher markup — moved as-is from current footer.php} </div>
      </div>
    </div>

  </div>
</footer>
```

**Grid**: upper tier — `grid-template-columns: 1fr` mobile (stacked, brand→nav→contact order); `2fr 1fr 1fr` ≥ tablet; brand column dominant (editorial C), nav + contact as compact columns (B). Bottom ribbon — `flex` row: copyright (left) · legal+colophon (center) · utilities (right); wraps to stacked on mobile with utilities last.

**Heading hierarchy**: the site `<h1>`/`<h2>` semantics live in `<main>`. The footer introduces one visually-hidden `<h2>Sidfot</h2>` as the landmark label (so screen-reader users get "Sidfot, navigation" style announcements) and uses **`<h3>`** for the in-footer column headings (Navigering / Kontakt / Nyhetsbrev). This avoids skipping levels and keeps the footer self-contained.

## Implementation Steps

### Phase 1 — ACF Options page + accessors
1. **Create `inc/functions/footer-options.php`** (File: `inc/functions/footer-options.php`)
   - Action: Create file. Register parent options page `kb-site-settings` via `acf_add_options_page()` and sub-page `kb-footer-settings` via `acf_add_options_sub_page()` (parent slug = `kb-site-settings`) inside a function hooked on `acf/init`. Register one field group `group_kb_footer_settings` (location = options page `kb-footer-settings`) containing all fields listed in the table above (use ACF array format consistent with `cpt.php` lines 147–183). Use field keys `field_kb_footer_*`. Repeater sub-fields `field_kb_footer_social_*`, `field_kb_footer_legal_*`. Provide helpers:
     - `kb_footer_option( string $key, mixed $default = '' ): mixed` — `static $cache;` memoize `get_field( $key, 'option' )` returns.
     - `kb_get_footer_social(): array` — returns `[]` or array of `['platform'=>…,'url'=>…]` (URLs already `esc_url_raw`-clean on save by ACF; re-`esc_url` on output).
     - `kb_get_footer_legal_links(): array` — `['label'=>…,'url'=>…]` (label is raw — escape at output).
     - Early-return the whole registration if `! function_exists('acf_add_options_page')`.
   - Why: Single source of truth for all editable footer content; matches existing `kb_register_acf_field_groups` pattern; cached accessors avoid repeated `get_field` calls.
   - Gates: **security** — options page defaults to `manage_options` capability (ACF default); do NOT lower it. Validate no field accepts arbitrary unescaped HTML that reaches output (colophon/tagline/newsletter_text use `wp_kses_post`; copyright uses `esc_html`).
   - Dependencies: none (ACF Pro present).
   - Acceptance: `ddev wp eval 'get_field("footer_tagline","option");'` does not fatal; Options page visible under "Inställningar → Webbplatsinställningar → Sidfot".
   - Risk: Low (additive; guarded by `function_exists`).

2. **Wire the new file in `functions.php`** (File: `functions.php`)
   - Action: Add `require_once get_theme_file_path( 'inc/functions/footer-options.php' );` in the `inc/functions` require block (near line 71–77).
   - Why: Manual autoloading is the project convention (AGENTS.md).
   - Gates: none.
   - Dependencies: Step 1.
   - Acceptance: No `Call to undefined function kb_footer_option()` errors.
   - Risk: Low.

### Phase 2 — Footer render helper + SVG map
3. **Create `inc/functions/footer-render.php`** (File: `inc/functions/footer-render.php`)
   - Action: Create file. Define `kb_footer_social_svg( string $platform ): string` returning an inline `<svg class="site-footer__social-icon" aria-hidden="true" focusable="false" viewBox="0 0 24 24" …>…</svg>` for each of: linkedin, instagram, github, x, youtube, threads, facebook, mastodon, bluesky, rss. Source single-path 24×24 SVG path data from Simple Icons (https://simpleicons.org). Map keyed by platform slug; unknown slug → empty string (link still renders text label as fallback). Use `stroke="currentColor"` only for icons where the source is outline-style; Simple Icons are filled so use `fill="currentColor"` and no stroke. Define `kb_render_footer(): string` that:
     1. Pulls values via `kb_footer_option()` / `kb_get_footer_social()` / `kb_get_footer_legal_links()`.
     2. Builds each fragment string with `esc_*` at construction (esc_url for hrefs incl. `mailto:`/`tel:`, esc_html for text, wp_kses_post for tagline/colophon/newsletter_text, esc_url + esc_attr for attrs).
     3. Renders `wp_nav_menu( [ 'theme_location'=>'footer_menu', 'depth'=>1, 'container'=>false, 'menu_class'=>'site-footer__nav-list', 'fallback_cb'=>'__return_empty_string', 'echo'=>false ] )` — wrap in `<nav>`/`<h3>` only if non-empty string returned.
     4. Conditionally omits brand/social/nav/contact/newsletter/legal/colophon/back-to-top sections when their data is empty.
     5. Newsletter: when `footer_show_newsletter` true, render block; inside, if `footer_newsletter_embed` non-empty → `do_shortcode( $embed )` (this is admin-controlled content; safe under trust model), else render a styled stub `<p class="site-footer__newsletter-stub">Kommer snart</p>`.
     6. Copyright: if override non-empty use it (esc_html), else `sprintf( '© %d %s', (int) current_time('Y'), esc_html( get_bloginfo('name') ) )` in a Swedish translatable wrapper `__( '© %1$d %2$s', 'kim-branzell' )`.
     7. Legal links: each item — if URL host matches home host → plain link (no target/rel); else `target="_blank" rel="noopener external"`. Use `wp_parse_url` host compare against `home_url()` host.
     8. Theme switcher: copy the existing `<div class="theme-toggle">…</div>` block verbatim from current `footer.php` into the `site-footer__utilities` section (keep its markup, classes, aria roles unchanged — its JS in app.js keys off `.theme-switcher__option[data-value]`).
   - Why: Mirrors the block render.php + template.php "render returns pre-escaped HTML" convention from AGENTS.md §7. Keeps `footer.php` thin.
   - Gates: **security** (escaping everywhere; external rel; mailto/tel via esc_url; do_shortcode trust note; host compare for internal vs external legal links) **and a11y** (aria-label per social link with platform name + "öppnas i nytt fönster", nav landmark with aria-label, visually-hidden H2 landmark label, back-to-top is a real link with accessible name, no `onclick`/inline handlers — CSP-safe).
   - Dependencies: Steps 1–2 (accessors must exist).
   - Acceptance: `echo kb_render_footer();` from a `ddev wp eval` produces a complete HTML string with no unescaped user data, no PHP notices on empty options.
   - Risk: Medium (largest step; escaping + conditional rendering surface area). Mitigation: build incrementally — scaffold sections in order brand→nav→contact→newsletter→bottom, lint after each.

4. **Wire `footer-render.php` in `functions.php`** (File: `functions.php`)
   - Action: Add `require_once get_theme_file_path( 'inc/functions/footer-render.php' );` after the footer-options require.
   - Gates: none.
   - Dependencies: Step 3.
   - Acceptance: `kb_render_footer()` callable.
   - Risk: Low.

### Phase 3 — Template
5. **Rewrite `footer.php`** (File: `footer.php`)
   - Action: Replace file contents with:
     ```php
     <?php wp_footer(); ?>
     <?php echo kb_render_footer(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built pre-escaped in inc/functions/footer-render.php ?>
     ```
   - The theme-switcher markup moves out of `footer.php` into `kb_render_footer()` (still rendered in the footer DOM).
   - Why: Thin template; all logic lives in the render helper (matches AGENTS.md §7 + the block render.php/template.php split).
   - Gates: **security** (the `// phpcs:ignore` is intentional & documented; output is pre-escaped) — no new unescaped output introduced.
   - Dependencies: Steps 4.
   - Acceptance: View source on any page — footer markup present after `</main>`; no raw `wp_footer` duplication; no PHP warnings.
   - Risk: Low.

### Phase 4 — Styles
6. **Create `assets/src/scss/components/_footer.scss`** (File: `assets/src/scss/components/_footer.scss`)
   - Action: Create file. Author:
     - `.site-footer` — `background: var(--main); color: auto-contrast(var(--main)); padding: 5rem 0 2rem;` (≤767) / `7rem 0 2.5rem;` (≥768). `@include container` on `&__inner`.
     - `.site-footer__top` — `display:grid; grid-template-columns:1fr; gap:3rem;` mobile; `@include tablet/desktop { grid-template-columns: 2fr 1fr 1fr; }`.
     - `.site-footer__brand` — logo+name row (flex, gap), tagline below (`max-width: 30ch; line-height:1.6;`), social list below.
     - `.site-footer__brand-link` — text-decoration none, color inherit; the logo svg sized `clamp(2.5rem, 4vw, 3.5rem)` height; site name `font-family:'Atkinson Hyperlegible Next', system-ui; font-size: 150%; font-weight:500;` matching `.site-header__name` style.
     - `.site-footer__social` — flex row gap `0.5rem`; each link `display:inline-flex; align-items:center; justify-content:center; width:44px; height:44px;` (44x44 touch target — WCAG 2.2 §2.5.8 / 2.5.5); `border-radius:50%; border:1px solid color-mix(in oklch, auto-contrast(var(--main)) 25%, transparent);`; hover `background: var(--accent); color:auto-contrast(var(--accent));`; `:focus-visible { @include focus-ring; }`; icon svg `width:20px;height:20px;`.
     - `.site-footer__heading` (h3) — visually-distinct small caps eyebrow: `font-size:0.875rem; letter-spacing:0.15em; text-transform:uppercase; font-weight:600; color: color-mix(in oklch, auto-contrast(var(--main)) 70%, transparent);` (muted but ≥4.5:1 — builder must verify). `margin:0 0 1.25rem;`.
     - `.site-footer__nav-list` — `list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:0.5rem;` (desktop) / wraps if long. Links `text-decoration:underline; text-underline-offset:0.15em; text-decoration-color: color-mix(in srgb, var(--accent) 50%, transparent);` hover `color: var(--accent);`. `:focus-visible { @include focus-ring; }`. `<a>` `aria-current="page"` styled with `color: var(--accent); font-weight:600;` (wp_nav_menu adds the attr automatically).
     - `.site-footer__contact-list` — same ul/listless pattern; `mailto:`/`tel:` styled like nav links.
     - `.site-footer__location` — plain text, muted to `color-mix(in srgb, auto-contrast(var(--main)) 80%, transparent)` (verify ≥4.5:1).
     - `.site-footer__newsletter` — top divider `border-top:1px solid color-mix(in oklch, auto-contrast(var(--main)) 15%, transparent); margin-top:1.5rem; padding-top:1.5rem;`. Stub text muted italic.
     - `.site-footer__bottom` — `margin-top:3rem; padding-top:1.5rem; border-top:1px solid color-mix(in oklch, auto-contrast(var(--main)) 15%, transparent); display:flex; flex-wrap:wrap; gap:1rem 2rem; align-items:center; justify-content:space-between;`. On smartphone → flex-direction:column; align-items:flex-start.
     - `.site-footer__copyright` — `margin:0; font-size:0.8125rem; color: color-mix(in srgb, auto-contrast(var(--main)) 75%, transparent);` (verify ≥4.5:1).
     - `.site-footer__legal` — inline `flex gap:1.5rem; list-style:none; margin:0; padding:0;` wrap; links `font-size:0.8125rem;` styled like nav links (small). `.site-footer__legal` + `.site-footer__colophon` grouped: colophon full-width muted text above or inline — builder's discretion but keep muted & ≥4.5:1.
     - `.site-footer__utilities` — `display:inline-flex; align-items:center; gap:1rem;` contains back-to-top link + theme switcher.
     - `.site-footer__back-to-top` — `display:inline-flex; align-items:center; gap:0.4rem; min-height:44px; padding:0.5rem 0.75rem;` text-decoration none; `:focus-visible { @include focus-ring; }`; arrow up svg `width:16px;height:16px;` currentColor.
     - **Reduced-motion guard (fixes existing app.scss gap for back-to-top anchor)**:
       ```scss
       @media (prefers-reduced-motion: reduce) {
         html { scroll-behavior: auto; }
       }
       ```
       (Defensive even though `scroll-behavior` is also set on `:where(html)` unconditionally in app.scss — the media-query rule wins by equal specificity + later load. Confirm load order: `app.scss` `@use`s footer AFTER its own `:where(html)` block, so footer's rule comes later in the cascade → wins. Builder verify.)
     - `@media (forced-colors: active) { .site-footer__social-link, .site-footer__back-to-top { outline: 1px solid ButtonText; } }` (match project-index/blog-posts forced-colors convention from AGENTS.md §5).
   - Why: On-brand with palette tokens, matches block section conventions, full a11y (44px targets, focus rings, reduced-motion, forced-colors).
   - Gates: **a11y** (touch targets §2.5.8, focus indicators §2.4.7, color contrast §1.4.3/§1.4.6, reduced motion §2.3.3, forced colors) **and security** (none — SCSS only).
   - Dependencies: Steps 5 (markup classes must exist before styling).
   - Acceptance: `bun run build` succeeds; visual check in DDEV (light + dark + mobile + 200% zoom); Lighthouse a11y audit footer-relevant checks pass.
   - Risk: Medium (contrast of muted variants must be manually verified per palette). Mitigation: keep muted text at ≥80% in srgb mix and re-check with a contrast tool / DevTools.

7. **Import the footer partial in `app.scss`** (File: `assets/src/scss/app.scss`)
   - Action: Add `@use '@scss/components/footer' as *;` directly after the existing `@use '@scss/components/project-single' as *;` line.
   - Gates: none.
   - Dependencies: Step 6.
   - Acceptance: Built CSS contains `.site-footer` rules.
   - Risk: Low.

### Phase 5 — Build, lint, verify
8. **Build assets & lint** (Theme dir)
   - Action: `bun run build` then `composer lint:php`.
   - Expect possible baseline PHPCS note: the `// phpcs:ignore … OutputNotEscaped` on `kb_render_footer()` echo in `footer.php` (intentional, documented) and a possible "missing file doc comment" on new inc files matching sibling files — acceptable per AGENTS.md §8 baseline. Any OTHER error must be fixed before declaring done.
   - Gates: none (this step IS the gate check).
   - Dependencies: Steps 7.
   - Acceptance: `bun run build` exit 0 + produces `assets/dist/manifest.json` with the app entry; `composer lint:php` returns no NEW errors beyond the documented baseline.
   - Risk: Low–Medium (build may surface SCSS typos).

9. **Manual verification (DDEV)** (URL: https://kim-branzell.ddev.site)
   - Action: Visit front page, a single project, a single post, the search results, and a 404. Confirm footer present and correct on all. In WP admin populate the Footer Options page (tagline, one social link, one legal link, toggle newsletter on with stub) and assign a menu to the `footer_menu` location. Re-verify. Toggle theme switcher to confirm it still works in the new position. Run `ddev wp eval 'echo kb_render_footer();' | head` for a quick markup sanity check.
   - Gates: **a11y** manual — keyboard tab through the entire footer (visible focus rings, logical order), activate back-to-top (smooth scroll, then auto under reduced-motion), verify social links announce platform name + "öppnas i nytt fönster", verify footer nav landmark label. Optional NVDA/VoiceOver spot-check.
   - Dependencies: Step 8.
   - Acceptance: All five page types render the footer; all toggles work; no console errors; Lighthouse a11y ≥ 95 on sampled page (footer-relevant).
   - Risk: Low.

## Testing Strategy
- **PHP**: `composer lint:php` (PHPCS WPCS + PHPCompatibilityWP, PHP 8.1+). New files scanned automatically.
- **Build**: `bun run build` (Vite/rolldown + lightningcss). Failure on SCSS typo or missing import.
- **No runtime tests** configured in this project (no PHPUnit/JS test runner — AGENTS.md "Tests"). Verification = lint + build + manual.
- **ACF guard**: temporarily deactivate ACF via `ddev wp plugin deactivate advanced-custom-fields-pro` — footer must not fatal (only render defaults / skip dynamic fields). Re-activate.
- **Empty-state**: set all footer options to empty + assign no `footer_menu` → footer still renders with site name + copyright + theme switcher only, no warnings.
- **a11y**: keyboard-only traverse footer; check focus order & rings; toggle theme switcher by keyboard; verify `aria-label`s on social links; confirm `prefers-reduced-motion: reduce` disables smooth scroll for back-to-top; verify color contrast in light AND dark palettes (lint with a contrast checker or DevTools — target ≥4.5:1 body, ≥3:1 large/UI).
- **CSP**: open DevTools console on a footer page — no CSP violation reports (inline SVGs are fine; no inline scripts/styles added).

## Risks & Mitigations
- **Risk**: Muted helper text (`--muted`-style color-mix) drops below 4.5:1 contrast in one of the palettes (light `#F2ECE4`-based or dark `#122215`-based). → Mitigation: cap muted text at `color-mix(in srgb, foreground 75%, transparent)` minimum, verify both palettes with DevTools contrast tool before declaring step 6 done; bump to 85–90% if needed.
- **Risk**: `do_shortcode()` on `footer_newsletter_embed` ACF textarea could be used to inject unexpected content if an unauthorised user gains `manage_options`. → Mitigation: ACF Options page is `manage_options`-gated by default; document in the field instructions that this field is admin-trusted. Do NOT expose it to lower caps. (Acceptable per the existing trust model — `brnzl-forms` shortcodes are the intended content.)
- **Risk**: `target="_blank"` external links missing `rel="noopener external"` → reverse tabnabbing + CSP. → Mitigation: render helper assigns `rel` programmatically based on host compare; PHPCS may not catch it so re-check manually in source.
- **Risk**: `mailto:`/`tel:` URLs rejected by `esc_url()` default protocols. → Mitigation: `esc_url( $url, ['mailto','tel','http','https'] )` (explicit allowed protocols array) — verify in `footer-render.php`.
- **Risk**: Existing orphaned SVG sprite (`kb_add_svg_defs`) could be confused with the new inline approach. → Mitigation: do NOT modify `filters.php` in this task; the new social icons are inline. Document in footer-render.php header comment that the sprite is intentionally not reused (coordinate-space mismatch).
- **Risk**: `wp_footer()` outputs the hidden SVG defs sprite (from `filters.php`) at the top of the footer before the visible footer markup — fine, but visually unquiet in source. → Mitigation: leave as-is (out of scope). The defs `<svg aria-hidden="true">` renders nothing.
- **Risk**: Reduced-motion `scroll-behavior:auto` override in `_footer.scss` could be overridden back to smooth by another later-loaded stylesheet. → Mitigation: only `app.scss` (which `@use`s footer near the end) defines `scroll-behavior`; footer's `@media (prefers-reduced-motion: reduce) { html { scroll-behavior:auto } }` lands later in the cascade and wins. Verify in DevTools Computed pane under reduced-motion emulation.
- **Risk**: The theme switcher JS in `app.js` keys off `.theme-switcher__option[data-value]` and sets `data-theme` on `<html>`; moving the switcher DOM within `kb_render_footer()` (still inside `<footer>`) must not break those selectors. → Mitigation: copy the markup verbatim (classes intact, no wrappers renamed); the JS uses global `querySelectorAll`, container-independent. Verify toggling still updates the active option visual.

## Open Questions / Blockers
None — all five user decisions captured (merged B+C layout, full field set, theme switcher stays in footer, ACF Options page, footer on all page types).

## Rollback Considerations
- All changes are additive except `footer.php` (replaced) and `app.scss` (one added `@use` line) and `functions.php` (two added `require_once`).
- **Revert `footer.php`** to the prior 39-line stub (`git checkout footer.php`) to instantly restore the old behaviour.
- **Remove the two `require_once`** lines from `functions.php` to disable the ACF page + render helper (or just revert `functions.php`).
- ACF options stored in the `options` table are harmless leftovers if the code is removed; orphaned fields are not surfaced. No data migration needed.
- `_footer.scss` + its `@use` can be left in place harmlessly (unused rules); fully revert by removing the `@use` line.

## Success Criteria
- [ ] Footer renders on front page, single project, single post, search, archive, and 404 with identical markup & styling.
- [ ] All footer content (tagline, contact, social, nav, legal, colophon, copyright, back-to-top) is editable via WP admin → Inställningar → Webbplatsinställningar → Sidfot (ACF Options page).
- [ ] `footer_menu` location, when assigned, renders a single-level nav list inside `<nav aria-label>`.
- [ ] Social links open in a new tab with `rel="noopener external"` and platform-named `aria-label`s; inline SVG icons render correctly.
- [ ] Newsletter section is hidden by default; appears only when "Visa nyhetsbrev" is toggled on; renders the stub when the embed field is empty; renders `do_shortcode(embed)` when populated.
- [ ] Theme switcher operates identically from its new footer position (Light/System/Dark, persisted, no flash).
- [ ] Back-to-top anchor scrolls to `#main-content`; smooth normally, instant under `prefers-reduced-motion: reduce`.
- [ ] `bun run build` succeeds; `composer lint:php` introduces no undocumented new errors.
- [ ] No console errors / CSP violations on any footer-bearing page in light and dark modes.
- [ ] Keyboard-only traversal of the footer shows visible focus on every interactive element in a logical order; Lighthouse a11y audit footer-relevant items pass.
- [ ] AGENTS.md updated with the footer conventions established here.

## Checkpoint
- Last updated: 2026-08-02 (build complete)
- Status: done
- Completed steps: 1–9
- Files changed so far:
  - NEW: inc/functions/footer-options.php
  - NEW: inc/functions/footer-render.php
  - MODIFIED: footer.php (rewritten to call kb_render_footer())
  - MODIFIED: functions.php (2x require_once added)
  - NEW: assets/src/scss/components/_footer.scss
  - MODIFIED: assets/src/scss/app.scss (@use footer added)
- Next step: none (complete)
- Decisions/deviations:
  - Added @use '@scss/base/mixins' to _footer.scss (tokens auto-injected but mixins are not — matches hero/_intro convention)
  - Logo path needed fill="currentColor" for dark-mode contrast (original header has same gap, out of scope for footer task)
  - SCSS contact/nav styles duplicated explicitly instead of @extend to avoid compilation issues
  - All Simple Icons SVG paths fetched from cdn.jsdelivr.net/npm/simple-icons@latest for accuracy
  - WP-CLI ddev chokes on quoted PHP; verified via eval and browser instead
- Blockers: none
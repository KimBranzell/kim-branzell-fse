# Implementation Plan: WCAG AA Hover-Contrast Remediation + Default-State Fixes

## Overview
Audit every `:hover` (and hover-style) state in the theme for WCAG 2.2 AA contrast,
unify all hover affordances on one color, and repair several failing *default*
(non-hover) text states found along the way. The systemic root cause is that the
light-mode `--accent` token (#C9A87A tan) is too light to serve as text on the cream
section backgrounds, and the light-mode `--buttons` token (#C47D63) is too light for
its white `auto-contrast()` text. Dark mode is unaffected (its accent/buttons sit on
dark green and already pass).

## Deliverable Type
**One-off theme edits** — a 2-line palette retune plus targeted SCSS cleanups. No new
plugin, no JS, no PHP, no block registration. The fix is purely styling and lives in
`assets/src/scss/`.

## Confirmed Facts
- Palette source of truth: `assets/src/scss/base/_palette.scss` — `$l-color-4` is the
  light `--accent`, `$l-color-5` is the light `--buttons`; both feed `light-dark()`
  so dark mode is untouched by changing only the `$l-color-*` values.
- `auto-contrast($c)` (`_mixins.scss` L36-38) = `oklch(from $c round(1.21 - L) 0 0)`
  → resolves to **black** for light inputs, **white** for dark inputs. Verified live:
  current `auto-contrast(--accent)` → black; `auto-contrast(--buttons)` → white.
- Section backgrounds: header/footer use `--main` (#F2ECE4 light); blocks (blog-posts,
  project-index, faq, intro, about) use `--main`; FAQ section + search-results cards +
  `main` element use `--alternative` (#D8CFC2 light). All hover *text* failures are
  accent/cream-on-cream-ish pairs on one of these two surfaces.
- `color-mix(in srgb, var(--accent) N%, transparent)` used as **hover text color**
  composites against the section bg; even at 70% the *new* accent only reaches ~3.6:1
  on cream — still under 4.5:1. So mix-based hover *text* must become **solid**
  `var(--accent)` (not color-mix) once the accent is darkened.
- WCAG 2.2 thresholds: **4.5:1** for normal text (SC 1.4.3), **3:1** for UI/non-text
  (SC 1.4.11) — focus rings, link underlines, state borders.
- Build tooling: `bun run build` from theme dir; PHPCS via `composer lint:php`.
  No JS test runner. Local DDEV at https://kim-branzell.ddev.site.

## Assumptions
- New light `--accent` = **#6B4226** (deep terracotta): 7.36:1 on `--main`, 5.60:1 on
  `--alternative`, white-on-it 8.63:1 (so every filled-accent + `auto-contrast` pair
  flips to white text and still passes). *Builder validates:* run the contrast node
  script in Step 1.
- New light `--buttons` = **#9A5238** (darker terracotta): white text 5.77:1 (passes
  4.5 normal; was 3.03), and as text on cream main 4.92:1. `--buttons-hover` stays
  #333 (already 12.6:1 white). The hover darken #9A5238 → #333 is a visible but
  harmonious shift. *Builder validates:* same script.
- The deep-terracotta accent is on-brand (the existing `--buttons` coral and the dark
  `--accent` #F3B361 are already in the orange/brown family). *Builder validates:*
  visual check in DDEV after build.

## Architecture Changes
None. Pure token + per-file SCSS edits. No new files, no hooks, no PHP.

## Token targets (the 2-line core change)

| Token | Current (light) | New (light) | Why |
|---|---|---|---|
| `$l-color-4` (`--accent`) | `#C9A87A` | `#6B4226` | Pass 4.5:1 as text on main/alt; white-on-it 8.6:1 for filled hovers; fixes focus rings/underlines/borders to 3:1 for free |
| `$l-color-5` (`--buttons`) | `#C47D63` | `#9A5238` | White text 5.77:1 (fixes default CTA button text; hover already passes) |

Dark-mode values (`$d-color-4` `#F3B361`, `$d-color-5` `#F9CA1C`) and `$buttons-hover`
(`light-dark($l-color-6, $d-color-6)` → #333/#F9CA1C) are **unchanged**.

## Inventory of every `:hover`/hover-style state + disposition

### Repaired automatically by the palette retune (NO edit needed)
- `_footer.scss` L137/147/171 nav·contact·legal link hover `→ var(--accent)` 7.4:1 ✓
- `_project-index.scss` L193 row title hover `→ var(--accent)` ✓
- `_project-single.scss` L97 post-nav link hover `→ var(--accent)` ✓
- `_blog-posts.scss` L298 `&__card-readmore` default `color: var(--accent)` (was failing too) ✓; L462 link-icon hover `→ var(--accent)` ✓
- All `outline: 2px solid var(--accent)` focus rings (project-index L197, blog-posts L97/152/353/495, search L175, project-single L103 dashed) → 7.4:1 ✓ (1.4.11)
- All `text-decoration-color: var(--accent)` underlines (footer L138/149/172) ✓
- `1px solid var(--accent)` borders (search-result card L158, search submit L76, pagination L253, mobile dividers header L113/166/205) ✓
- Filled-accent hovers (footer social-link/back-to-top, blog-posts pinned badge, theme-switcher option) → flip to white text 8.6:1 ✓

### Needs an explicit edit (mix-based hover text → solid accent)

1. `_project-index.scss` L231-233 — `&__link:hover :is(sector,tags,year) { color: color-mix(accent 70%, transparent) }` → `color: var(--accent)`.
2. `_blog-posts.scss` L390-392 — `&__link:hover &__date { color: color-mix(accent 60%, transparent) }` → `color: var(--accent)`.
3. `_blog-posts.scss` L444-447 — `&__link:hover &__meta, &__meta-sep { color: color-mix(accent 70%, transparent) }` → `color: var(--accent)`.

### Needs an explicit edit (hover color is wrong hue / darkens a light color)

4. `_intro.scss` L132-134 — accordion-card button hover `color: color-mix(in srgb, var(--main), black 15%)` (darkens cream → still cream, ~1.5:1 on `--alternative`). Change → `color: var(--accent); text-decoration: underline; text-underline-offset: 0.2em;`.
5. `_faq.scss` L153-155 — question button hover `color: color-mix(in srgb, var(--main), black 15%)` (same defect on `--alternative` bg). Change → `color: var(--accent); text-decoration: underline; text-underline-offset: 0.15em;`.
6. `_about.scss` L114-118 — bento card hover `border-color: color-mix(in oklch, var(--main) 50%, transparent)` (cream border on cream bg, ~1.5:1, fails 1.4.11 non-text 3:1 for a state border). Change → `border-color: var(--accent);`.

## Failing *default* (non-hover) states to repair in the same pass

7. `_search.scss` L81 search-submit default text `color: var(--alternative)` (#D8CFC2 on cream `--main` bg, ~1.5:1). The hover (L87-91) keeps `color: var(--alternative)` on a `color-mix(main, accent 30%)` bg → also fails. Fix both: set `color: var(--accent);` on the submit (default), and on hover swap to `color: auto-contrast(var(--accent));` (white, since new accent is dark) OR keep `var(--accent)` text and just shift bg — recommend `color: var(--accent)` default + `color: #fff;` hover for a clear CTA shift. (Builder: pick the `auto-contrast(var(--accent))` form so it stays token-driven.)
8. `_search.scss` L167 search-result card title `a { color: var(--main) }` (cream on `--alternative` card bg, ~1.5:1). Change → `color: var(--accent);`.
9. `_search.scss` L256 pagination `a { color: var(--main) }` (cream, near-invisible on its border/transparent bg). Change default → `color: var(--accent);`. Hover (L260-263) currently does `background: var(--accent)` but leaves text cream (1.9:1 with old accent). Add `color: auto-contrast(var(--accent));` to `a:hover` (white, 8.6:1 on new accent). `span.current` (L270) already `background: var(--main); color: var(--alternative)` — verify stays ≥4.5:1 (cream on cream = ~1.5:1 — **this also fails**); change `span.current` → `background: var(--accent); color: auto-contrast(var(--accent));` for consistency and to pass.
10. `_faq.scss` L149 question button default `color: var(--main)` (cream on `--alternative` section bg, ~1.5:1). Change → `color: auto-contrast(var(--alternative));` (black, 13.6:1). The hover edit (#5 above) shifts to `var(--accent)` (new, 5.6:1) so the hover cue is visible.

## Implementation Steps

### Phase 1: Core palette retune (the one change that does most of the work)

1. **Re-tune light accent + light buttons** (File: `assets/src/scss/base/_palette.scss`)
   - Action: Change `$l-color-4: #C9A87A;` → `$l-color-4: #6B4226;` and
     `$l-color-5: #C47D63;` → `$l-color-5: #9A5238;`. Leave `$l-color-6`, all
     `$d-color-*`, and every `light-dark(...)` line untouched.
   - Why: Single source of truth — fixing the token auto-repairs ~20 hover/focus/
     underline/border states across the theme at once.
   - Gates: a11y (color contrast) — *the* audit target.
   - Dependencies: None. Every later step depends on this.
   - Acceptance: `bun run build` succeeds; in DDEV, `getComputedStyle(html)`
     `--accent` resolves to `#6b4226` and `--buttons` to `#9a5238` in light mode
     (and `--accent` `#f3b361` / `--buttons` `#f9ca1c` in dark mode, unchanged).
   - Risk: Low (2 lines, trivially revertable). Visual brand-recolor of the accent
     family is the expected, accepted trade-off.

### Phase 2: Hover-style cleanups (mix → solid; wrong-hue hovers)

2. **Solidify mix-based hover text** (Files: `_project-index.scss`, `_blog-posts.scss`)
   - Action:
     - `_project-index.scss` L232: `color: color-mix(in srgb, var(--accent) 70%, transparent);` → `color: var(--accent);`
     - `_blog-posts.scss` L391: `color: color-mix(in srgb, var(--accent) 60%, transparent);` → `color: var(--accent);`
     - `_blog-posts.scss` L446: `color: color-mix(in srgb, var(--accent) 70%, transparent);` → `color: var(--accent);`
   - Why: Even with the darkened accent, a 60-70% accent mix composites to ~3.6:1 on
     cream — still under 4.5. Solid accent = 7.4:1.
   - Gates: a11y.
   - Dependencies: Step 1.
   - Acceptance: Hover a project-index row and a blog-posts editorial row in DDEV;
     DevTools contrast checker reports ≥4.5:1 for the meta/title/date text.
   - Risk: Low.

3. **Fix wrong-hue accordion / card hovers** (Files: `_intro.scss`, `_faq.scss`, `_about.scss`)
   - Action:
     - `_intro.scss` L132-134 hover block →
       `color: var(--accent); text-decoration: underline; text-underline-offset: 0.2em;`
     - `_faq.scss` L153-155 hover block →
       `color: var(--accent); text-decoration: underline; text-underline-offset: 0.15em;`
     - `_about.scss` L116 `border-color: color-mix(in oklch, var(--main) 50%, transparent);` →
       `border-color: var(--accent);`
   - Why: Old hovers darken an already-light color into a barely-lighter color
     (intro/faq) or paint a cream border on cream (about) — none reach 4.5/3:1.
   - Gates: a11y.
   - Dependencies: Step 1.
   - Acceptance: Intro accordion question, FAQ question, about bento card hovers all
     show a clearly visible terracotta cue and pass AA in DevTools.
   - Risk: Low.

### Phase 3: Failing default-state repairs

4. **Search submit text** (File: `_search.scss`)
   - Action:
     - L81 default: `color: var(--alternative);` → `color: var(--accent);`
     - L87-91 hover block: add `color: auto-contrast(var(--accent));` so text flips to
       white on the (darkened) accent-tinted hover bg. Keep the existing
       `background: color-mix(...)` and `border-color: var(--accent)`.
   - Why: Default text was cream-on-cream (~1.5:1); hover kept the same failing text.
   - Gates: a11y.
   - Dependencies: Step 1.
   - Acceptance: Search submit button (header inline + search page) shows terracotta
     text on cream by default, white text on hover; both ≥4.5:1.
   - Risk: Low.

5. **Search-result card title + pagination** (File: `_search.scss`)
   - Action:
     - L167: `a { color: var(--main); ... }` → `color: var(--accent);`
     - L256: pagination `a { ... color: var(--main); ... }` → `color: var(--accent);`
     - L260-263 `a:hover, a:focus-visible { background: var(--accent); }` → add
       `color: auto-contrast(var(--accent));`
     - L270-277 `span.current { background: var(--main); color: var(--alternative); border-color: var(--main); }` →
       `background: var(--accent); color: auto-contrast(var(--accent)); border-color: var(--accent);`
   - Why: Cream link text on cream/tan cards invisible; "current" pagination chip is
     cream-on-cream.
   - Gates: a11y.
   - Dependencies: Step 1.
   - Acceptance: On the search-results page, card titles and pagination links/chips
     read clearly and pass AA in default + hover states.
   - Risk: Low.

6. **FAQ question default text** (File: `_faq.scss`)
   - Action: L149 `color: var(--main);` → `color: auto-contrast(var(--alternative));`
   - Why: Default cream question text on tan section bg fails 4.5:1.
   - Gates: a11y.
   - Dependencies: Step 1 (and pairs with Step 3's hover edit on the same selector).
   - Acceptance: FAQ questions read as black-on-tan (13.6:1) at rest and shift to
     terracotta + underline on hover.
   - Risk: Low.

### Phase 4: Verification

7. **Build + lint** (No file edits)
   - Action: `bun run build` then `composer lint:php` from theme dir.
   - Why: SCSS must compile; PHP lint is the project gate (this change is SCSS-only,
     so lint should be a clean no-op, but run it per convention).
   - Gates: none.
   - Dependencies: Steps 1-6.
   - Acceptance: `bun run build` exits 0 and `assets/dist/css/` regenerates; no PHPCS
     errors introduced.
   - Risk: Low.

8. **Visual + contrast verification in DDEV** (No file edits)
   - Action: Load home, a project-index list, a blog-posts block, the FAQ block, the
     intro accordion, a single project, the search results page, the footer, and the
     header (desktop + mobile menu). For each interactive element, hover + focus and
     read the computed contrast in DevTools' "Accessibility" pane (or use the
     built-in color-contrast tool). Also toggle dark mode via the theme switcher and
     re-check the same elements (dark values unchanged → should still pass).
   - Why: The node contrast script validates palette math; the browser verifies the
     real cascade, `auto-contrast()` resolution, and `color-mix` compositing.
   - Gates: a11y.
   - Dependencies: Step 7.
   - Acceptance: Every hover + focus + default text state in light mode reports
     ≥4.5:1 (text) or ≥3:1 (non-text); dark mode unchanged.
   - Risk: Low.

## Testing Strategy
- **Token math (automated):** node script (provided inline to builder in Step 1
  acceptance) computing WCAG ratios for new `--accent`/`--buttons` against `--main`,
  `--alternative`, white, and the new accent's `auto-contrast` output.
- **Build:** `bun run build` (must exit 0).
- **Lint:** `composer lint:php` (must not regress; SCSS-only change, expect 0 deltas).
- **Manual (DDEV):** the Step 8 walkthrough — the only authoritative check because
  `color-mix` + `oklch(from ...)` + `light-dark()` resolution must be confirmed in a
  real browser stack.
- **Dark-mode regression:** explicitly re-run the Step 8 checks with
  `[data-theme="dark"]` / `prefers-color-scheme: dark` to confirm no regression
  (dark palette values are unchanged).

## Risks & Mitigations
- **Risk:** Visual brand-recolor of the accent family (tan → deep terracotta) reads
  as a bigger change than expected. → Mitigation: it is the agreed approach (Option A);
  a single 2-line revert in `_palette.scss` restores the prior look with no other file
  touched. The Phase 2-3 edits are forward-compatible (solid `var(--accent)` reads
  correctly at either palette value).
- **Risk:** `auto-contrast()` flipping filled-accent hovers from black text → white
  text changes the look of existing badges/switcher/social hovers. → Mitigation: this
  is expected and *improves* contrast (8.6:1 vs the prior 9.4:1-from-black — both pass;
  the flip is purely because the new accent crossed the L=0.605 threshold). Visually
  verify in Step 8; if any single filled-accent element looks worse with white text,
  it can be locally overridden, but no such case is expected.
- **Risk:** A `color-mix(var(--accent) N%)` *decorative* use (underlines, borders,
  dividers) was relying on the old light accent and now becomes a strong terracotta
  line. → Mitigation: 1.4.11 requires 3:1 for these, and they were *failing* before;
  the stronger line is the fix, not a regression. Accept visually.

## Open Questions / Blockers
None. Approach, scope, and the one-color choice are confirmed by the user.

## Rollback Considerations
- Revert `_palette.scss` (`$l-color-4` and `$l-color-5`) to undo the systemic fix;
  then revert the Phase 2/3 SCSS edits to restore the old (failing) mix/cream hovers.
  All edits are isolated to pure SCSS; no DB/option/PHP migration to undo.
- `assets/dist/` is gitignored — `bun run build` regenerates it; no dist rollback
  needed, just rebuild after reverting source.

## Success Criteria
- [ ] Light mode: every `:hover`, `:focus-visible`, and hover-style text state meets
      ≥4.5:1 against its actual painted background.
- [ ] Light mode: every focus ring / underline / state border using the accent meets
      ≥3:1 (1.4.11).
- [ ] Light mode: the four repaired default states (search submit, search-result
      title, pagination links + current chip, FAQ question text) meet ≥4.5:1.
- [ ] Dark mode: no regression — all previously-passing states still pass.
- [ ] One unified hover color: all hover text affordances resolve to `var(--accent)`
      (deep terracotta in light, #F3B361 in dark).
- [ ] `bun run build` and `composer lint:php` both clean.

## Checkpoint
- Last updated: 2026-08-02T13:54
- Status: done
- Completed steps: 1, 2, 3, 4, 5, 6, 7, 8
- Files changed so far: assets/src/scss/base/_palette.scss, assets/src/scss/blocks/_project-index.scss, assets/src/scss/blocks/_blog-posts.scss, assets/src/scss/blocks/_intro.scss, assets/src/scss/blocks/_faq.scss, assets/src/scss/blocks/_about.scss, assets/src/scss/components/_search.scss
- Next step: none (all done)
- Decisions/deviations: All edits followed plan exactly; no deviations. Lighthouse a11y=95, color-contrast audit=PASS.
- Blockers: none
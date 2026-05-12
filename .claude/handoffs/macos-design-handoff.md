# macOS UI Overhaul (v2) — Implementation Handoff

**Source plan**: `/Users/rustamjonakhmedov/.claude/plans/i-checked-this-plugin-bubbly-seal.md` §§1–14 (lines 227–549)
**Prefix**: `prodimg-` (CSS), `$prodimg_seo_*` (PHP), `prodimg_seo_1972adm_` (option/action names)
**Stack**: Vanilla CSS + jQuery, PHP 7.4, no build step, WP.org-safe

---

## 1. Token Table

Paste this block into `admin/css/admin.css`, replacing the existing `.wrap { --prodimg-* }` block.

```css
/* =========================================================================
 * Design tokens — scoped so wp-admin globals stay untouched.
 * ========================================================================= */
.prodimg-app {
    /* Surfaces */
    --prodimg-bg-page:      #F5F5F7;
    --prodimg-bg-surface:   #FFFFFF;
    --prodimg-bg-elevated:  #FBFBFD;
    --prodimg-bg-vibrancy:  rgba(255, 255, 255, 0.8);

    /* Lines & text */
    --prodimg-border-hairline: rgba(0, 0, 0, 0.07);
    --prodimg-border-strong:   rgba(0, 0, 0, 0.12);
    --prodimg-text-primary:    #1D1D1F;
    --prodimg-text-secondary:  #86868B;
    --prodimg-text-tertiary:   #AEAEB2;

    /* Accents */
    --prodimg-accent-blue:       #007AFF;
    --prodimg-accent-green:      #34C759;
    --prodimg-accent-red:        #FF3B30;
    --prodimg-accent-orange:     #FF9500;
    --prodimg-accent-purple:     #AF52DE;
    --prodimg-accent-blue-bg:    rgba(0, 122, 255, 0.10);
    --prodimg-accent-green-bg:   rgba(52, 199, 89, 0.12);
    --prodimg-accent-orange-bg:  rgba(255, 149, 0, 0.12);
    --prodimg-accent-red-bg:     rgba(255, 59, 48, 0.10);

    /* Radii */
    --prodimg-radius-sm:   6px;
    --prodimg-radius-md:   8px;
    --prodimg-radius-lg:   12px;
    --prodimg-radius-xl:   16px;
    --prodimg-radius-pill: 999px;

    /* Shadows */
    --prodimg-shadow-hairline: 0 0 0 0.5px rgba(0, 0, 0, 0.07);
    --prodimg-shadow-card:     0 0 0 0.5px rgba(0, 0, 0, 0.07), 0 8px 24px rgba(0, 0, 0, 0.08);
    --prodimg-shadow-pop:      0 0 0 0.5px rgba(0, 0, 0, 0.07), 0 16px 48px rgba(0, 0, 0, 0.16);
    --prodimg-shadow-focus:    0 0 0 3px rgba(0, 122, 255, 0.30);

    /* Effects */
    --prodimg-blur-vibrancy: saturate(180%) blur(20px);

    /* Type */
    --prodimg-font: -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", system-ui, sans-serif;

    background: var(--prodimg-bg-page);
    color: var(--prodimg-text-primary);
    font-family: var(--prodimg-font);
}

/* Dark-mode override — CSS-only, no UI toggle in v2 */
@media (prefers-color-scheme: dark) {
    .prodimg-app {
        --prodimg-bg-page:      #1C1C1E;
        --prodimg-bg-surface:   #2C2C2E;
        --prodimg-bg-elevated:  #3A3A3C;
        --prodimg-bg-vibrancy:  rgba(28, 28, 30, 0.72);
        --prodimg-border-hairline: rgba(255, 255, 255, 0.08);
        --prodimg-border-strong:   rgba(255, 255, 255, 0.14);
        --prodimg-text-primary:    #F5F5F7;
        --prodimg-text-secondary:  #98989D;
        --prodimg-text-tertiary:   #6C6C70;
        --prodimg-shadow-card:     0 0 0 0.5px rgba(255, 255, 255, 0.08), 0 8px 24px rgba(0, 0, 0, 0.5);
    }
}

/* Reduced-motion killswitch — single rule covers all transitions/animations */
@media (prefers-reduced-motion: reduce) {
    .prodimg-app * { transition: none !important; animation: none !important; }
}
```

---

## 2. Component Spec Table

| Class | State | Key visual rules | Key markup change |
|---|---|---|---|
| `.prodimg-card` | Evolve existing | Remove `border`; use `box-shadow: var(--prodimg-shadow-card)`. Radius → `var(--prodimg-radius-lg)` (12px). | No markup change. |
| `.prodimg-card__title` | Evolve existing | Drop `text-transform: uppercase`. Font-weight 600, color `--prodimg-text-secondary`, sentence-case. | No markup change. |
| `.prodimg-card__value` | Evolve existing | 34px / 600, `letter-spacing: -0.02em`, `font-variant-numeric: tabular-nums`. | No markup change. |
| `.prodimg-score-gauge` | Rewrite from scratch | SVG ring; `stroke-linecap: round`; animated via `stroke-dashoffset`. Class stays on `<svg>` element. | Replace `<div>` with `<svg>` (see §3a). |
| `.prodimg-score-pill` | Evolve existing | `background: var(--prodimg-accent-*-bg)`, bold accent text, `border-radius: var(--prodimg-radius-pill)`. | No markup change. |
| `.prodimg-progress` | Evolve existing | 6px track, hairline `inset` box-shadow, accent-green fill, soft ease. | No markup change. |
| `.prodimg-progress--stacked` | Evolve existing | 10px segments, 1px white dividers; rounded outer corners via `border-radius` on container + `overflow: hidden`. | No markup change. |
| `.prodimg-tabs` | Rewrite from scratch | Pill container, light bg; selected = white chip with `--prodimg-shadow-hairline` + tiny drop shadow; `transition: 150ms ease`. | No markup change; same DOM, CSS body replaced. |
| `.prodimg-filter-chips` | Evolve existing | Hairline shadow chip; active = filled `--prodimg-accent-blue` + white text; `transition: 150ms ease`. | No markup change. |
| `.prodimg-empty-state` | Evolve existing | Elevated surface, 16px radius; dashicon in circular `--prodimg-accent-blue-bg` bubble; 17px body text. | Wrap icon in `<span class="prodimg-empty-state__icon-bubble">`. |
| `.prodimg-quick-actions .button-primary` | Evolve existing | 36px height, `var(--prodimg-radius-md)`, no gradient, accent blue, soft press shadow. Scoped to `.prodimg-app`. | No markup change. |
| `.prodimg-coverage-list` | Evolve existing | Leading dashicon bullet in tinted circle; 14px primary text. | Add optional `<span class="prodimg-coverage-list__icon dashicons dashicons-yes">`. |
| `.prodimg-signal-row` | Evolve existing | 4px track with accent-color tied to severity; weight pill as monospaced micro-chip via `font-variant-numeric: tabular-nums`. | No markup change. |
| `.prodimg-modal-overlay` + `.prodimg-modal` | Rewrite from scratch | Vibrancy backdrop; elevated surface, `--prodimg-shadow-pop`, 16px radius, `max-width: 720px`, sticky header. Spring ease on fade. | Strip all `style=""` from `catalog.php` (see §7). |
| `.prodimg-legend-dot--good/--ok/--poor` | Rewrite from scratch | Pure CSS background colors matching accent tokens; no inline hex. | Replace `style="background:#hex"` (see §7). |
| `.prodimg-switch` | Rewrite from scratch | macOS-style pill toggle; real `<input type="checkbox">` under the hood; `appearance: none` + `::before` knob. | Replaces radio pairs in settings.php Auto-fix tab (see §4). |
| `.prodimg-toast` | New | Top-center fixed HUD; frosted bg; slide-down spring; auto-dismiss 2.4s. | Injected by JS helper (see §5). |
| `.prodimg-page-header` | New | `position: sticky`, vibrancy bg, `backdrop-filter`. | New `<header>` block at top of each view (see §3b). |
| `.prodimg-segnav` | New | Segmented control; pill container; active item = white chip + hairline shadow. | New `<nav>` inside `.prodimg-page-header` (see §3c). |

---

## 3. Signature Elements

### 3a. SVG Activity-Ring Score Gauge

**Markup template** (replaces `<div class="prodimg-score-gauge ...">` in dashboard.php and audit-report.php):

```html
<svg class="prodimg-score-gauge prodimg-score-gauge--<?php echo esc_attr( $prodimg_seo_gauge_band ); ?>"
     viewBox="0 0 120 120"
     data-score="<?php echo esc_attr( $prodimg_seo_avg_score ); ?>"
     role="img"
     aria-label="<?php echo esc_attr( sprintf( __( 'Score %d', 'product-image-seo' ), $prodimg_seo_avg_score ) ); ?>">
  <circle class="prodimg-score-gauge__track"    cx="60" cy="60" r="52" />
  <circle class="prodimg-score-gauge__progress" cx="60" cy="60" r="52" />
  <text   class="prodimg-score-gauge__value"    x="60" y="64" text-anchor="middle">0</text>
  <text   class="prodimg-score-gauge__label"    x="60" y="82" text-anchor="middle"><?php esc_html_e( 'Score', 'product-image-seo' ); ?></text>
</svg>
```

**CSS** (add to `admin.css`):

```css
.prodimg-score-gauge__track,
.prodimg-score-gauge__progress {
    fill: none;
    stroke-width: 12;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: 60px 60px;
}
.prodimg-score-gauge__track {
    stroke: var(--prodimg-border-hairline);
}
.prodimg-score-gauge__progress {
    stroke: var(--prodimg-accent-green);
    stroke-dasharray: 326.7;   /* 2 * π * 52 */
    stroke-dashoffset: 326.7;  /* start empty */
    transition: stroke-dashoffset 1.2s cubic-bezier(0.22, 1, 0.36, 1);
}
.prodimg-score-gauge--ok   .prodimg-score-gauge__progress { stroke: var(--prodimg-accent-orange); }
.prodimg-score-gauge--poor .prodimg-score-gauge__progress { stroke: var(--prodimg-accent-red); }
.prodimg-score-gauge__value {
    font-size: 28px;
    font-weight: 600;
    fill: var(--prodimg-text-primary);
    font-family: var(--prodimg-font);
}
.prodimg-score-gauge__label {
    font-size: 12px;
    fill: var(--prodimg-text-secondary);
    font-family: var(--prodimg-font);
}
```

**JS** (in `admin.js` — replaces the current count-up-only block):

```js
$('.prodimg-score-gauge[data-score]').each(function() {
    var $g     = $(this);
    var target = parseInt( $g.attr('data-score'), 10 ) || 0;
    var $prog  = $g.find('.prodimg-score-gauge__progress');
    var $val   = $g.find('.prodimg-score-gauge__value');
    var C      = 326.7; // circumference = 2 * Math.PI * 52
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if ( reduce ) {
        $prog.css('stroke-dashoffset', C - ( C * target / 100 ) );
        $val.text( target );
        return;
    }
    // Ring sweep
    requestAnimationFrame(function() {
        $prog.css('stroke-dashoffset', C - ( C * target / 100 ) );
    });
    // Synchronized number count-up (~1.2s, ~30 ticks)
    var start = 0;
    var step  = Math.max( 1, Math.floor( target / 30 ) );
    var timer = setInterval(function() {
        start += step;
        if ( start >= target ) { start = target; clearInterval(timer); }
        $val.text( start );
    }, 40);
});
```

---

### 3b. Frosted Page Header

**Markup template** (inside `<div class="wrap prodimg-app">`, before page content):

```html
<header class="prodimg-page-header">
  <div class="prodimg-page-header__inner">
    <div class="prodimg-page-header__titleblock">
      <h1 class="prodimg-page-header__title"><?php esc_html_e( 'Dashboard', 'product-image-seo' ); ?></h1>
      <p class="prodimg-page-header__subtitle"><?php esc_html_e( 'Image SEO at a glance', 'product-image-seo' ); ?></p>
    </div>
    <div class="prodimg-page-header__actions">
      <button type="button" class="button button-primary" id="prodimg-seo-scan-catalog">
        <?php esc_html_e( 'Run Audit', 'product-image-seo' ); ?>
      </button>
    </div>
  </div>
  <nav class="prodimg-segnav" aria-label="<?php esc_attr_e( 'Plugin sections', 'product-image-seo' ); ?>">
    <!-- see §3c for nav items -->
  </nav>
</header>
```

*Per-page: adjust `__title`, `__subtitle`, and `__actions` content. Subtitle and actions may be omitted if not relevant.*

**CSS**:

```css
.prodimg-page-header {
    position: sticky;
    top: 32px; /* below wp-admin bar */
    z-index: 20;
    background: var(--prodimg-bg-vibrancy);
    backdrop-filter: var(--prodimg-blur-vibrancy);
    -webkit-backdrop-filter: var(--prodimg-blur-vibrancy);
    border-bottom: 1px solid var(--prodimg-border-hairline);
    margin: -10px -20px 16px;  /* bleed to viewport edges */
    padding: 16px 20px 12px;
}
.prodimg-page-header__inner {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 12px;
}
.prodimg-page-header__title {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--prodimg-text-primary);
    line-height: 1.2;
}
.prodimg-page-header__subtitle {
    margin: 2px 0 0;
    font-size: 13px;
    color: var(--prodimg-text-secondary);
}
/* Fallback: no vibrancy → opaque surface */
@supports not (backdrop-filter: blur(1px)) {
    .prodimg-page-header { background: var(--prodimg-bg-surface); }
}
@media (max-width: 600px) {
    .prodimg-page-header { top: 46px; } /* mobile WP admin bar */
}
/* WP notice breathing room when sticky header is present */
.prodimg-app > .notice { margin-top: 12px; }
body.prodimg-seo-skin .wrap > h1 { display: none; } /* hide wp-admin h1; our header replaces it */
```

---

### 3c. Segmented Sub-Nav (`.prodimg-segnav`)

**Markup template** (rendered server-side per page — set `is-active` on the current page's `<a>`):

```html
<nav class="prodimg-segnav" aria-label="<?php esc_attr_e( 'Plugin sections', 'product-image-seo' ); ?>">
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-1972adm' ) ); ?>"
     class="prodimg-segnav__item<?php echo ( 'prodimg-seo-1972adm' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
    <?php esc_html_e( 'Dashboard', 'product-image-seo' ); ?>
  </a>
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-1972adm-audit' ) ); ?>"
     class="prodimg-segnav__item<?php echo ( 'prodimg-seo-1972adm-audit' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
    <?php esc_html_e( 'Audit', 'product-image-seo' ); ?>
  </a>
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-1972adm-catalog' ) ); ?>"
     class="prodimg-segnav__item<?php echo ( 'prodimg-seo-1972adm-catalog' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
    <?php esc_html_e( 'Catalog', 'product-image-seo' ); ?>
  </a>
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-1972adm-bulk-fix' ) ); ?>"
     class="prodimg-segnav__item<?php echo ( 'prodimg-seo-1972adm-bulk-fix' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
    <?php esc_html_e( 'Bulk Fix', 'product-image-seo' ); ?>
  </a>
  <a href="<?php echo esc_url( admin_url( 'admin.php?page=prodimg-seo-1972adm-settings' ) ); ?>"
     class="prodimg-segnav__item<?php echo ( 'prodimg-seo-1972adm-settings' === $prodimg_seo_current_page ) ? ' is-active' : ''; ?>">
    <?php esc_html_e( 'Settings', 'product-image-seo' ); ?>
  </a>
</nav>
```

*`$prodimg_seo_current_page` = `sanitize_key( $_GET['page'] ?? '' )` — already available in each view.*

**CSS** (active-state styling):

```css
.prodimg-segnav {
    display: flex;
    gap: 2px;
    background: var(--prodimg-bg-page);
    border-radius: var(--prodimg-radius-pill);
    padding: 3px;
    width: fit-content;
}
.prodimg-segnav__item {
    padding: 5px 14px;
    border-radius: var(--prodimg-radius-pill);
    font-size: 13px;
    font-weight: 500;
    color: var(--prodimg-text-secondary);
    text-decoration: none;
    transition: color 150ms ease;
}
.prodimg-segnav__item:hover {
    color: var(--prodimg-text-primary);
    text-decoration: none;
}
.prodimg-segnav__item.is-active {
    background: var(--prodimg-bg-surface);
    color: var(--prodimg-text-primary);
    box-shadow: var(--prodimg-shadow-hairline), 0 1px 4px rgba(0, 0, 0, 0.06);
}
```

---

## 4. Pill Switch

**REPLACES** both radio-pair blocks in `settings.php` panel `#panel-autofix` (`auto_generate` and `skip_existing`). The controller handler (`Settings::update_all()`) must be verified to accept checkbox semantics — see note below.

**Markup** (one instance per boolean setting; repeat pattern):

```html
<!-- Replaces the two <label><input type="radio"> pairs for auto_generate -->
<label class="prodimg-switch" for="auto_generate">
    <input type="checkbox"
           id="auto_generate"
           name="auto_generate"
           value="yes"
           <?php checked( $prodimg_seo_auto_generate, 'yes' ); ?> />
    <span class="prodimg-switch__track">
        <span class="prodimg-switch__knob"></span>
    </span>
    <span class="prodimg-switch__label"><?php esc_html_e( 'Auto-generate on save', 'product-image-seo' ); ?></span>
</label>

<!-- Repeat pattern for skip_existing -->
<label class="prodimg-switch" for="skip_existing">
    <input type="checkbox"
           id="skip_existing"
           name="skip_existing"
           value="yes"
           <?php checked( $prodimg_seo_skip_existing, 'yes' ); ?> />
    <span class="prodimg-switch__track">
        <span class="prodimg-switch__knob"></span>
    </span>
    <span class="prodimg-switch__label"><?php esc_html_e( 'Skip images with existing alt text', 'product-image-seo' ); ?></span>
</label>
```

**CSS**:

```css
.prodimg-switch {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    user-select: none;
}
.prodimg-switch input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.prodimg-switch__track {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 26px;
    background: var(--prodimg-border-strong);
    border-radius: var(--prodimg-radius-pill);
    transition: background 200ms ease;
    flex-shrink: 0;
}
.prodimg-switch input:checked + .prodimg-switch__track {
    background: var(--prodimg-accent-green);
}
.prodimg-switch__knob {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.20);
    transition: transform 200ms cubic-bezier(0.22, 1, 0.36, 1);
}
.prodimg-switch input:checked ~ .prodimg-switch__track .prodimg-switch__knob {
    transform: translateX(18px);
}
.prodimg-switch input:focus-visible + .prodimg-switch__track {
    box-shadow: var(--prodimg-shadow-focus);
}
.prodimg-switch__label {
    font-size: 14px;
    color: var(--prodimg-text-primary);
}
```

> **DEVELOPER ACTION REQUIRED**: Verify `class-prodimg-seo-1972adm-admin-controller.php` → `Settings::update_all()`. When a checkbox is unchecked, its field is absent from `$_POST`. The handler must treat a missing `auto_generate` or `skip_existing` key as `'no'` (i.e., use `sanitize_key( wp_unslash( $_POST['auto_generate'] ?? 'no' ) )`). If the existing handler already does this (it reads from `Settings::get(...)` with a default), confirm and proceed. If it expects both radio values explicitly, add the null-coalescing default before merging.

---

## 5. HUD Toast

**JS helper** (add to `admin.js`, top-level scope, before `$(document).ready`):

```js
/**
 * prodimgToast — HUD notification.
 *
 * @param {string} msg  Message text.
 * @param {string} type 'success' | 'error' | 'info'  (default 'success')
 */
function prodimgToast( msg, type ) {
    type = type || 'success';
    var $t = $('<div class="prodimg-toast prodimg-toast--' + type + '">' + msg + '</div>');
    $('body').append( $t );
    setTimeout(function() { $t.addClass('is-visible'); }, 10);
    setTimeout(function() {
        $t.removeClass('is-visible');
        setTimeout(function() { $t.remove(); }, 400);
    }, 2400);
}
```

*Call on success/error paths in place of inline `.css('color', 'green')` etc. Keep existing `location.reload()` after single-product modal save — do NOT remove in v2.*

**CSS**:

```css
.prodimg-toast {
    position: fixed;
    top: 48px;
    left: 50%;
    transform: translateX(-50%) translateY(-12px);
    z-index: 99999;
    padding: 10px 18px;
    border-radius: var(--prodimg-radius-md);
    font-size: 13px;
    font-weight: 500;
    font-family: var(--prodimg-font);
    color: var(--prodimg-text-primary);
    background: var(--prodimg-bg-vibrancy);
    backdrop-filter: var(--prodimg-blur-vibrancy);
    -webkit-backdrop-filter: var(--prodimg-blur-vibrancy);
    box-shadow: var(--prodimg-shadow-pop);
    border: 1px solid var(--prodimg-border-hairline);
    opacity: 0;
    transition: opacity 250ms ease, transform 350ms cubic-bezier(0.22, 1, 0.36, 1);
    pointer-events: none;
}
.prodimg-toast.is-visible {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.prodimg-toast--success { border-left: 3px solid var(--prodimg-accent-green); }
.prodimg-toast--error   { border-left: 3px solid var(--prodimg-accent-red); }
.prodimg-toast--info    { border-left: 3px solid var(--prodimg-accent-blue); }
@supports not (backdrop-filter: blur(1px)) {
    .prodimg-toast { background: var(--prodimg-bg-surface); }
}
```

---

## 6. Migration Checklist — View Files

### `includes/Views/Admin/dashboard.php`

- Change `<div class="wrap">` → `<div class="wrap prodimg-app">` and remove the `<h1>` tag (header provides it).
- Insert `<header class="prodimg-page-header">` block (with "Dashboard" title, "Image SEO at a glance" subtitle, "Run Audit" CTA, and `.prodimg-segnav`) immediately after the opening `<div class="wrap prodimg-app">`.
- Replace the `<div class="prodimg-score-gauge ...">` block (lines 73–88) with the SVG markup from §3a; remove the `style="--prodimg-gauge-pct: ..."` inline prop.

### `includes/Views/Admin/catalog.php`

- Change `<div class="wrap">` → `<div class="wrap prodimg-app">` and remove `<h1>`.
- Insert `.prodimg-page-header` (title: "Catalog Audit", no subtitle, no CTA).
- Strip all `style=""` attributes from the modal overlay and container (lines 52–63); replace with class-based selectors — see §7.

### `includes/Views/Admin/bulk-fix.php`

- Change `<div class="wrap">` → `<div class="wrap prodimg-app">` and remove `<h1>`.
- Insert `.prodimg-page-header` (title: "Bulk Fix", subtitle: "Generate alt text for all products needing review").
- No other markup changes; progress bar is already class-based.

### `includes/Views/Admin/settings.php`

- Change `<div class="wrap">` → `<div class="wrap prodimg-app">` and remove `<h1>`.
- Insert `.prodimg-page-header` (title: "Settings", no CTA).
- In `#panel-autofix`: replace both radio-pair blocks (`auto_generate`, `skip_existing`) with `.prodimg-switch` markup from §4.
- The `.prodimg-tabs` nav stays; its CSS body is replaced in `admin.css`.

### `includes/Views/Admin/audit-report.php`

- Change `<div class="wrap">` → `<div class="wrap prodimg-app">` and remove `<h1>`.
- Insert `.prodimg-page-header` (title: "SEO Audit Report", no subtitle, CTA: "Download CSV Report").
- Replace three `<span class="prodimg-progress__legend-dot" style="background: #hex;">` (lines 105–107) with class-based dots — see §7.
- Remove `style="--prodimg-gauge-pct: ..."` from gauge div (line 50); replace `<div>` gauge with SVG markup from §3a.

---

## 7. Inline Styles to Migrate

### catalog.php — Modal (lines 52–63)

| Source attribute | Replacement |
|---|---|
| `<div id="prodimg-seo-modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">` | Remove all inline styles; show/hide via JS `.show()`/`.hide()`. Add class `prodimg-modal-overlay`. |
| `<div id="prodimg-seo-modal" style="background:#fff; width:800px; max-width:90%; margin:50px auto; padding:20px; border-radius:4px; max-height:80vh; overflow-y:auto; position:relative;">` | Remove all inline styles. Add class `prodimg-modal`. |
| `<button id="prodimg-seo-modal-close" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:20px; cursor:pointer;">` | Remove all inline styles. Add class `prodimg-modal__close`. |
| `<div style="margin-top:20px; text-align:right;">` (save button wrapper) | Remove inline styles. Add class `prodimg-modal__footer`. |

### audit-report.php — Legend dots (lines 105–107)

| Source | Replacement class |
|---|---|
| `<span class="prodimg-progress__legend-dot" style="background: #00a32a;">` | `<span class="prodimg-legend-dot prodimg-legend-dot--good">` |
| `<span class="prodimg-progress__legend-dot" style="background: #dba617;">` | `<span class="prodimg-legend-dot prodimg-legend-dot--ok">` |
| `<span class="prodimg-progress__legend-dot" style="background: #d63638;">` | `<span class="prodimg-legend-dot prodimg-legend-dot--poor">` |

**New CSS rules** (add to `admin.css`):

```css
.prodimg-legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 5px; vertical-align: middle; }
.prodimg-legend-dot--good { background: var(--prodimg-accent-green); }
.prodimg-legend-dot--ok   { background: var(--prodimg-accent-orange); }
.prodimg-legend-dot--poor { background: var(--prodimg-accent-red); }
```

### dashboard.php & audit-report.php — Gauge inline custom prop

- Line 76 of `dashboard.php` and line 50 of `audit-report.php`: remove `style="--prodimg-gauge-pct: <?php echo ...; ?>;"` — the SVG markup does not use this CSS custom property.
- Line 78 of `dashboard.php`: remove `<div style="text-align:center;">` wrapper — SVG `text-anchor="middle"` handles centering.

### audit-report.php — Download link wrapper

- Line 111: `<p style="margin-top: 20px;">` — remove inline style; use `class="prodimg-card__footer-actions"` or similar utility class.

---

## 8. Open Questions

- **`admin_body_class` filter registration**: the plan specifies adding it to `class-prodimg-seo-1972adm-admin-controller.php`. Confirm the controller's `__construct()` or `register_hooks()` method is the right attachment point, and that `get_current_screen()` is available at that hook priority (it is after `current_screen` fires, typically priority 10 on `admin_init`).

- **Pill switch value coercion in settings handler**: the plan confirms radios → switches, but the current `Settings::update_all()` implementation must be audited for the unchecked-checkbox case (field absent from `$_POST`). If the handler uses `isset( $_POST['auto_generate'] ) ? 'yes' : 'no'` already, no PHP change is needed. If not, a one-line null-coalesce fix is required before the v2 CSS/markup lands.

- **Sub-nav page slugs**: the plan uses `prodimg-seo-1972adm`, `prodimg-seo-1972adm-audit`, `prodimg-seo-1972adm-catalog`, `prodimg-seo-1972adm-bulk-fix`, `prodimg-seo-1972adm-settings` as the `?page=` values. Developer should verify these match the slugs registered in `add_submenu_page()` calls in the admin controller — any mismatch means the `is-active` logic in the segnav will silently fail.

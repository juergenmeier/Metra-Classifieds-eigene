<?php
/**
 * Plugin Name: METRA Classifieds
 * Description: METRA Classifieds plugin (core).
 * Version: 0.0726
 * Author: J. Meier, Cologne
 * Text Domain: metra-classifieds-plugin
 */

function mc_enqueue_master_css(): void {
    if (is_admin()) {
        return;
    }

    // Use filemtime as cache-buster when available.
    $ver = defined('MC_VERSION') ? MC_VERSION : '0.0379';
    if (is_file(MC_MASTER_CSS_ABSPATH)) {
        $mtime = @filemtime(MC_MASTER_CSS_ABSPATH);
        if ($mtime) {
            $ver = (string) $mtime;
        }
    }

    wp_enqueue_style('mc-metra-master', MC_MASTER_CSS_URL, [], $ver);
}

/**
 * Enqueue local Font Awesome Free assets shipped inside the plugin.
 * Purpose:
 * - no CDN dependency
 * - identical icon rendering on all installations
 * - shared foundation for later dashboard/category icon mapping
 */
function mc_enqueue_font_awesome_free(): void {
    $handle = 'mc-font-awesome-free';
    $base = plugin_dir_url(__FILE__) . 'assets/vendor/font-awesome/';
    $css_url = $base . 'css/font-awesome.min.css';
    $css_path = plugin_dir_path(__FILE__) . 'assets/vendor/font-awesome/css/font-awesome.min.css';

    if (!is_file($css_path)) {
        return;
    }

    $ver = defined('MC_VERSION') ? MC_VERSION : '0.0436';
    $mtime = @filemtime($css_path);
    if ($mtime) {
        $ver = (string) $mtime;
    }

    wp_enqueue_style($handle, $css_url, [], $ver);
}


/**
 * Create metra-master.css if missing or empty.
 * Never overwrites a non-empty file.
 */

/**
 * Ensure the master stylesheet contains the global price design block.
 * - Appends once, update-safe.
 * - Never overwrites user edits.
 */
function mc_master_css_ensure_price_design_block(): void {
    $path = MC_MASTER_CSS_ABSPATH;
    if (!is_file($path) || !is_readable($path) || !is_writable($path)) {
        return;
    }

    $css = (string) @file_get_contents($path);
    if ($css === '') {
        return;
    }

    $start = '/* === MC: Price Design Block START === */';
    $end   = '/* === MC: Price Design Block END === */';
    if (strpos($css, $start) !== false && strpos($css, $end) !== false) {
        return;
    }

    $block = <<<CSS

{$start}
/*
 * Version: 0.0726
 * Purpose: Update-safe global price styling for METRA listings.
 * Location: /wp-content/plugins/metra-master.css
 */
.mc-price,
.mc-listing-price,
.mc-single-price {
  color: #2f6813;
  font-size: 20px;
  font-weight: 600;
  line-height: 1.2;
}

.mc-listing-price-row,
.mc-price-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 6px;
  margin-bottom: 6px;
}

.mc-favorite-toggle,
.mc-favorite-heart {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-left: 6px;
  cursor: pointer;
}

.mc-image-count {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 6px;
  font-size: 13px;
  color: #777777;
}

.mc-listing-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 4px;
  line-height: 1.25;
}

.mc-listing-excerpt {
  font-size: 15px;
  color: #444444;
  margin-top: 6px;
  line-height: 1.4;
}
{$end}

CSS;

    @file_put_contents($path, rtrim($css) . $block);
}
function mc_ensure_master_css_exists(): void {
    $path = MC_MASTER_CSS_ABSPATH;

    if (is_file($path) && filesize($path) > 0) {
        return;
    }

    $css = mc_master_css_default_template();

    // Fast path: direct write.
    if (@file_put_contents($path, $css) !== false) {
        return;
    }

    // Fallback: WP_Filesystem for hosts that require it.
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $ok = WP_Filesystem();
    global $wp_filesystem;
    if ($ok && $wp_filesystem) {
        $wp_filesystem->put_contents($path, $css, FS_CHMOD_FILE);
    }
}

/**
 * Default minimal template (safe baseline).
 * We will migrate all plugin design CSS later.
 */
function mc_master_css_default_template(): string {
    return <<<'CSS'
/*!
 * METRA Master Stylesheet (AUTO-GENERATED TEMPLATE FROM PLUGIN CSS)
 * Location: /wp-content/plugins/metra-master.css
 * Purpose: Update-safe master stylesheet for METRA plugin UI (Last Wins, scope: .mc)
 * Generated: 2026-02-20 07:35:07 UTC
 *
 * NOTE:
 * - This content is the DEFAULT TEMPLATE used only when metra-master.css is missing or empty.
 * - Existing non-empty metra-master.css will NEVER be overwritten.
 */

/* ========================================================================== 
 * SOURCE FILE: public/assets/css/mc-theme.css
 * DESIGN ELEMENTS: Styles
 * ========================================================================== */
/*
 * METRA Classifieds Theme
 * Step 3: Component styles moved into a dedicated stylesheet using CSS variables.
 */

/*
 * Local fonts shipped with METRA Classifieds
 * - Roboto (Regular/Bold)
 * - Roboto Condensed (Regular/Bold)
 */

@font-face{
  font-family:"MC Roboto";
  src:url("../fonts/roboto/roboto-v50-latin-regular.woff2") format("woff2");
  font-weight:400;
  font-style:normal;
  font-display:swap;
}

@font-face{
  font-family:"MC Roboto";
  src:url("../fonts/roboto/roboto-v50-latin-700.woff2") format("woff2");
  font-weight:700;
  font-style:normal;
  font-display:swap;
}

@font-face{
  font-family:"MC Roboto Condensed";
  src:url("../fonts/roboto-condensed/roboto-condensed-v31-latin-regular.woff2") format("woff2");
  font-weight:400;
  font-style:normal;
  font-display:swap;
}

@font-face{
  font-family:"MC Roboto Condensed";
  src:url("../fonts/roboto-condensed/roboto-condensed-v31-latin-700.woff2") format("woff2");
  font-weight:700;
  font-style:normal;
  font-display:swap;
}

.mc-home-top {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--mc-home-gap, 14px);
  align-items: start;
  margin: 0 0 var(--mc-home-gap, 14px) 0;
}

/* Homepage layout (Step 1): left categories sidebar + right listings block */
.mc-home-layout{
  display:grid;
  grid-template-columns: 1fr;
  gap: var(--mc-home-gap, 14px);
  align-items:start;
  margin: 0 0 var(--mc-home-gap, 14px) 0;
}

/* Desktop breakpoint: only show sidebar layout above 980px */
@media (min-width: 981px){
  .mc-home-layout{ grid-template-columns: minmax(220px, 320px) minmax(0,1fr); }
}

/* Homepage layout mode (Design option: mc_design[home_layout]) */
@media (min-width: 981px){
  /* Default: sidebar + main */
  .mc-home-layout--sidebar{ grid-template-columns: minmax(220px, 320px) minmax(0,1fr); }

  /* Sidebar layout: 2-column listings on desktop */
  .mc-home-layout--sidebar .mc-listings{
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

	  /* One-column mode (desktop): keep categories sidebar + force single-column listings */
	  .mc-home-layout--onecol{ grid-template-columns: minmax(220px, 320px) minmax(0,1fr); }
	  .mc-home-layout--onecol .mc-home-sidebar{ display:block !important; }
	  .mc-home-layout--onecol .mc-listings{ grid-template-columns: 1fr; }
}

.mc-home-sidebar{
  border: var(--mc-border-width, 1px) solid var(--mc-border);
  border-radius: var(--mc-box-radius, 14px);
  padding: var(--mc-box-padding-y, 12px) var(--mc-box-padding-x, 14px);
  background: var(--mc-surface);
}

.mc-home-sidebar-head{
  display:flex;
  align-items:baseline;
  justify-content:space-between;
  gap:10px;
  margin: 0 0 10px 0;
}

.mc-home-sidebar-all{
  font-size: var(--mc-link-size, 13px);
  opacity: .9;
  text-decoration: none;
  color: var(--mc-primary);
}

.mc-home-sidebar-all:hover{ text-decoration: underline; }

.mc-home-cat-group{ padding: 10px 0; border-top: 1px solid rgba(0,0,0,.08); }
.mc-home-cat-group:first-of-type{ border-top: 0; padding-top: 0; }

.mc-home-cat-parent{
  display:block;
  font-weight:700;
  text-decoration:none;
  color: var(--mc-heading, var(--mc-text));
  margin: 0 0 6px 0;
}

.mc-home-cat-parent:hover{ text-decoration: underline; }

.mc-home-cat-children{ display:grid; gap: 4px; }

/* Keep the sidebar column visible at leaf-level (reserved for later content). */
.mc-home-cat-children-empty{ min-height: 20px; }

.mc-home-cat-child,
.mc-home-cat-more{
  display:block;
  text-decoration:none;
  color: var(--mc-text);
  opacity: .95;
}

.mc-home-cat-child:hover,
.mc-home-cat-more:hover{ text-decoration: underline; }

.mc-home-cat-more{ color: var(--mc-primary); opacity: .9; }

@media (max-width: 720px) {
  .mc-home-top { grid-template-columns: 1fr; }
}

.mc-home-col {
  border: var(--mc-border-width, 1px) solid var(--mc-border);
  border-radius: var(--mc-box-radius, 14px);
  padding: var(--mc-box-padding-y, 12px) var(--mc-box-padding-x, 14px);
  background: var(--mc-surface);
}

.mc-home-title {
  font-weight: 600;
  margin: 0 0 8px 0;
  color: var(--mc-heading, var(--mc-primary));
  font-family: var(--mc-font);
  font-size: var(--mc-font-size);
}

.mc-home-muted {
  opacity: 0.8;
  color: var(--mc-muted);
}

.mc-search-form {
  display: flex;
  gap: var(--mc-search-gap, 10px);
  align-items: center;
  flex-wrap: wrap;
}

.mc-input {
  flex: 1;
  min-width: 180px;
  border: var(--mc-border-width, 1px) solid var(--mc-border);
  border-radius: var(--mc-input-radius);
  padding: var(--mc-input-padding-y, 10px) var(--mc-input-padding-x, 12px);
  font-family: var(--mc-font);
  font-size: var(--mc-font-size);
  color: var(--mc-text);
  background: var(--mc-input-bg, var(--mc-bg));
}

.mc-button {
  border: var(--mc-border-width, 1px) solid var(--mc-primary) !important;
  border-radius: var(--mc-button-radius);
  padding: var(--mc-btn-padding-y, 10px) var(--mc-btn-padding-x, 14px);
  background: var(--mc-primary) !important;
  color: var(--mc-primary-text);
  cursor: pointer;
  font-family: var(--mc-font);
  font-size: var(--mc-font-size);
  line-height: 1;
}

.mc-button:hover { filter: brightness(0.95); }

.mc-link {
  font-size: var(--mc-link-size, 13px);
  opacity: 0.85;
  text-decoration: underline;
  color: inherit;
}

.mc-listings {
  display: grid;
  gap: var(--mc-gap, 12px);
}

/*
 * Mobile Homepage (Step 1): 2-column listing grid
 * - Only affects the homepage container (.mc-home-layout)
 * - Keeps all non-home listing pages unchanged
 */
@media (max-width: 980px) {
  .mc-home-layout .mc-listings{ grid-template-columns: repeat(2, minmax(0, 1fr)); }

  /* Card becomes vertical on small screens so it fits into 2 columns. */
  .mc-home-layout .mc-listing{ flex-direction: column; }

  /* Thumbnail stretches full width of the card (fixed height for consistent rows). */
  .mc-home-layout .mc-listing-thumb{ width: 100%; }
  .mc-home-layout .mc-thumb-img{ width: 100%; height: 140px; }
  .mc-home-layout .mc-thumb-placeholder{ width: 100%; height: 140px; }
}

.mc-listing {
  border: var(--mc-border-width, 1px) solid var(--mc-border);
  border-radius: var(--mc-card-radius, 16px);
  background: var(--mc-card-bg, var(--mc-surface));
  display: flex;
  gap: var(--mc-gap, 12px);
  padding: var(--mc-card-padding, 12px);
  align-items: flex-start;
  cursor: default;
}

/* Only thumb + title are links (so text remains selectable). */
.mc-listing-thumb-link,
.mc-listing-title-link {
  text-decoration: none;
  color: inherit;
}

.mc-listing-thumb-link { display: block; }

.mc-listing-thumb { flex: 0 0 auto; }

.mc-thumb-img {
  display: block;
  object-fit: cover;
  border-radius: var(--mc-thumb-radius, 12px);
  background: var(--mc-placeholder-bg, #f2f2f2);
}

.mc-thumb-placeholder {
  border-radius: var(--mc-thumb-radius, 12px);
  background: var(--mc-placeholder-bg, #f2f2f2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--mc-muted);
}

.mc-listing-body {
  min-width: 0;
  flex: 1 1 auto;
}

.mc-listing-title {
  font-family: var(--mc-listing-title-font, var(--mc-font));
  font-size: var(--mc-title-size, 16px);
  font-weight: var(--mc-title-weight, 700);
  line-height: 1.25;
  margin: 0 0 6px 0;
  color: var(--mc-heading, var(--mc-primary));
  overflow-wrap: anywhere;
}

.mc-listing-excerpt {
  font-family: var(--mc-listing-font, var(--mc-font));
  font-size: var(--mc-excerpt-size, 14px);
  font-weight: var(--mc-excerpt-weight, 400);
  line-height: 1.45;
  opacity: 0.95;
  overflow-wrap: anywhere;
}

.mc-listing-meta {
  margin-top: 8px;
  font-size: var(--mc-meta-size, 12px);
  opacity: 0.8;
  color: var(--mc-muted);
}

.mc-empty { padding: var(--mc-empty-padding-y, 10px) 0; }

/* =============================
   Category Wizard (Submit flow)
   ============================= */

.mc-catwiz{position:relative;z-index:10;}
/* Mobile-first: present as a bottom-sheet modal */
.mc-catwiz-overlay{display:block;position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:9998;}
.mc-catwiz-sheet{position:fixed;left:0;right:0;bottom:0;z-index:9999;background:var(--mc-card-bg,#fff);border-radius:18px 18px 0 0;box-shadow:0 -10px 30px rgba(0,0,0,.18);max-height:85vh;display:flex;flex-direction:column;overflow:hidden;}
.mc-catwiz-handle{width:46px;height:5px;border-radius:10px;background:rgba(0,0,0,.18);margin:10px auto 6px;}
.mc-catwiz-topbar{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;padding:10px 16px 12px;border-bottom:1px solid rgba(0,0,0,.08);}
.mc-catwiz-title{font-weight:600;text-align:center;}
.mc-catwiz-cancel{color:var(--mc-primary,#0b5fff);text-decoration:none;font-weight:500;}
.mc-catwiz-spacer{display:block;}
.mc-catwiz-section{padding:12px 16px;font-weight:600;color:rgba(0,0,0,.78);}
.mc-catwiz-backrow{padding:0 16px 10px;}
.mc-catwiz-back{color:rgba(0,0,0,.65);text-decoration:none;font-weight:500;}
.mc-catwiz-list{padding:0 0 6px;overflow:auto;}
.mc-catwiz-item{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-top:1px solid rgba(0,0,0,.08);text-decoration:none;color:inherit;}
.mc-catwiz-item:first-child{border-top:1px solid rgba(0,0,0,.08);}
.mc-catwiz-name{font-size:16px;}
.mc-catwiz-chevron{color:rgba(0,0,0,.35);font-size:20px;line-height:1;}
.mc-catwiz-empty{padding:14px 16px;color:rgba(0,0,0,.65);}
.mc-catwiz-actions{padding:12px 16px 16px;border-top:1px solid rgba(0,0,0,.08);background:rgba(0,0,0,.02);}
.mc-catwiz-primary{display:block;text-align:center;padding:12px 14px;border-radius:12px;background:var(--mc-primary,#0b5fff);color:#fff;text-decoration:none;font-weight:600;}
.mc-catwiz-primary:hover{opacity:.95}

/* Desktop / larger screens: render as centered card and use multi-column list */
@media (min-width: 900px){
  .mc-catwiz-overlay{position:fixed;}
  .mc-catwiz-sheet{
    position:relative;
    max-width:960px;
    margin:40px auto;
    border-radius:18px;
    max-height:none;
    height:auto;

  }
  .mc-catwiz-overlay{background:transparent;position:absolute;inset:auto;display:none;}
  .mc-catwiz{max-width:960px;margin:0 auto;}
  .mc-catwiz-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0;border-top:1px solid rgba(0,0,0,.08);}
  .mc-catwiz-item{border-top:none;border-bottom:1px solid rgba(0,0,0,.08);}
}
/* Very wide: 3 columns */
@media (min-width: 1200px){
  .mc-catwiz-list{grid-template-columns:repeat(3,minmax(0,1fr));}
}

/* ===== Category Tiles (Wizard Design System) =====
 * Used for category drilldown pages and [mc_categories_grid].
 * Same design language as the "Post a Listing" category wizard.
 */
  display:grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  margin: 0;
  padding: 0;
}
@media (max-width: 980px){
}
@media (max-width: 520px){
}
.mc-catwiz-tile{
  display:block;
  border: 1px solid rgba(0,0,0,.10);
  border-radius: 16px;
  padding: 14px 14px;
  background: var(--mc-surface);
  text-decoration:none;
  color: inherit;
}
.mc-catwiz-tile:hover{ text-decoration: underline; }
.mc-catwiz-tile-name{
  font-weight: 700;
  color: inherit;
}



  .mc-mobile-topcat {
    flex: 0 0 auto;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 16px;
    border: 1px solid #ff6a00;
    color: #ff6a00;
    font-size: 14px;
    text-decoration: none;
    white-space: nowrap;
  }

  .mc-mobile-topcat-icon {
    width: 16px;
    height: 16px;
    border: 1px solid #ff6a00;
    border-radius: 4px;
    display: inline-block;
    background: rgba(255, 106, 0, 0.08);
  }

  .mc-mobile-topnav-dropdown {
    flex: 0 0 auto;
    padding: 6px 10px;
    border-radius: 16px;
    border: 1px solid #ff6a00;
    background: #fff;
    font-size: 16px;
    line-height: 1;
  }
}

/* Price visible in listing grid */
.mc-listing-price{
  font-weight:700;
  margin-top:6px;
}

/* mc6879 code font inherit – safety: never show monospace in listings */
.mc-listings code, .mc-listings kbd, .mc-listings tt, .mc-listings var { font-family: inherit; font-size: inherit; }

/* === mc6880 – Fix: if any theme/content wraps sidebar links in <code>, inherit theme font === */
.mc-home-sidebar code,
.mc-home-sidebar kbd,
.mc-home-sidebar tt,
.mc-home-sidebar var {
  font-family: inherit !important;
  font-size: inherit !important;
  font-weight: inherit !important;
  line-height: inherit !important;
}

/* Ensure nested elements also inherit */
.mc-home-sidebar code * {
  font-family: inherit !important;
}

/* === mc6885 – MOBILE ONLY: remove ALL borders/lines (desktop untouched) === */
@media (max-width: 768px) {

  body {
    --mc-border-width: 0px !important;
    --mc-border: transparent !important;
    --mc-card-radius: 0px !important;
    --mc-box-radius: 0px !important;
  }

  body .mc-listing {
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    padding: 0 !important;

  }

  body .mc-home-sidebar {
    border: 0 !important;
    border-radius: 0 !important;
    background: transparent !important;
    padding: 0 !important;
  }

  body .mc-home-cat-group {
    border: 0 !important;
    padding: 0 !important;
  }

  body [class^="mc-"],
  body [class*=" mc-"] {

    outline: 0 !important;
  }
}

/* Standalone Categories Grid (Wizard look, non-modal) */
.mc-catwiz-standalone .mc-catwiz-overlay{display:none !important;}
.mc-catwiz-standalone .mc-catwiz-sheet{
  position:relative !important;
  left:auto !important;
  right:auto !important;
  bottom:auto !important;
  margin:0 auto !important;
  max-width:960px;
  max-height:none !important;
  height:auto !important;
}

/* --- Theme font inherit fix: prevent <code> wrappers (from editor/theme) from forcing monospace in MC wizard/grid --- */
.mc-catwiz code,
.mc-catwiz pre,
.mc-catwiz kbd,
.mc-catwiz samp,
.mc-catwiz tt,
.mc-home-layout code,
.mc-home-layout pre,
.mc-home-layout kbd,
.mc-home-layout samp,
.mc-home-layout tt{
  font-family: inherit !important;
  font-size: inherit !important;
  line-height: inherit !important;
  color: inherit !important;
}

/* =========================================================
   METRA Home – Full-text Search Bar (mc_q)
   ========================================================= */
.mc-home-search{ margin: 0 0 12px 0; }
.mc-home-search-row{ display:flex; gap:10px; align-items:stretch; }
.mc-home-search-input{
  width:100%;
  padding:10px 12px;
  border:1px solid rgba(0,0,0,.18);
  border-radius:8px;
  background:#fff;
  color:inherit;
  box-sizing:border-box;
}
.mc-home-search-btn{ white-space:nowrap; }

@media (max-width: 768px){
  .mc-home-search-row{ gap:8px; }
  .mc-home-search-input{ padding:10px 10px; }
}

/* ============================================================
   METRA – Home Search Bar (one-row layout, incl. mobile)
   - Search input + button always in ONE row
   - Frontend only
   ============================================================ */
.mc-home-search-row{
  display:flex;
  flex-wrap:nowrap;
  align-items:center;
  gap:10px;
  width:100%;
}
.mc-home-search-input{
  flex:1 1 auto;
  min-width:0;
  width:auto;
}
.mc-home-search-btn{
  flex:0 0 auto;
  white-space:nowrap;
}

/* =========================================================
 * Frontend UI: Unified Button Style (Button-22)
 * Purpose:
 * - Style all METRA frontend buttons consistently (incl. "Message seller",
 *   "Edit listing" and "Delete listing" actions) without touching theme files.
 * - Keep this purely CSS-based to avoid template changes.
 * ======================================================= */

/* Base button token (matches requested .button-22 look) */
.mc-btn,
a.mc-btn,
button.mc-btn,
input.mc-btn,
.mc-button,
a.mc-button,
button.mc-button,
input.mc-button,
.mc-home-search-btn,
a.mc-home-search-btn {
  align-items: center;
  appearance: button;
  background-color: var(--mc-primary, #0276FF) !important;
  border-radius: 8px;
  border-style: none;

  box-sizing: border-box;
  color: #fff;
  cursor: pointer;
  display: inline-flex;
  flex-direction: row;
  flex-shrink: 0;
  font-family: inherit;
  font-size: 100%;
  line-height: 1.15;
  margin: 0;
  padding: 10px 21px;
  text-align: center;
  text-transform: none;
  transition: color .13s ease-in-out, background .13s ease-in-out, opacity .13s ease-in-out, box-shadow .13s ease-in-out;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
  text-decoration: none;
}

.mc-btn:hover,
a.mc-btn:hover,
button.mc-btn:hover,
input.mc-btn:hover,
.mc-button:hover,
a.mc-button:hover,
button.mc-button:hover,
input.mc-button:hover,
.mc-home-search-btn:hover,
a.mc-home-search-btn:hover {
	  background-color: var(--mc-primary, #0276FF) !important;
  color: #fff;
  text-decoration: none;
}

.mc-btn:active,
a.mc-btn:active,
button.mc-btn:active,
input.mc-btn:active,
.mc-button:active,
a.mc-button:active,
button.mc-button:active,
input.mc-button:active,
.mc-home-search-btn:active,
a.mc-home-search-btn:active {
	  background-color: var(--mc-primary, #0276FF) !important;
}

/* Explicit primary alias used by the contact module */
.mc-btn-primary {
		  background: var(--mc-primary, #0276FF) !important;
		  border-color: var(--mc-primary, #0276FF) !important;
	  color: var(--mc-primary-text, #ffffff) !important;
}

/*
 * Scoped button styling (highest reliability):
 * Some third‑party themes ship global .mc-btn rules (sometimes loaded after plugins).
 * By scoping to the METRA wrapper we ensure the Design token "Button color" always wins.
 */
.metra-classifieds .mc-btn,
.metra-classifieds .mc-home-search-btn,
.metra-classifieds .mc-button,
.metra-classifieds a.mc-btn,
.metra-classifieds input.mc-btn,
.metra-classifieds .mc_button,
.metra-classifieds .mc-button-button,
.metra-classifieds .mc_button_button,
.metra-classifieds .mc_button_submit,
.metra-classifieds .mc-button-submit,
.metra-classifieds .mc-btn-primary {
  background: var(--mc-primary, #0276FF) !important;
  background-color: var(--mc-primary, #0276FF) !important;
  border-color: var(--mc-primary, #0276FF) !important;
  color: var(--mc-primary-text, #ffffff) !important;
}

.metra-classifieds .mc-btn:hover,
.metra-classifieds .mc-home-search-btn:hover,
.metra-classifieds .mc-button:hover,
.metra-classifieds a.mc-btn:hover,
.metra-classifieds input.mc-btn:hover,
.metra-classifieds .mc_button:hover,
.metra-classifieds .mc-button-button:hover,
.metra-classifieds .mc_button_button:hover,
.metra-classifieds .mc_button_submit:hover,
.metra-classifieds .mc-button-submit:hover,
.metra-classifieds .mc-btn-primary:hover {
  filter: brightness(0.95);
}

	.mc-btn:focus,
	a.mc-btn:focus,
	button.mc-btn:focus,
	input.mc-btn:focus,
	.mc-btn-primary:focus,
	a.mc-btn-primary:focus,
	button.mc-btn-primary:focus,
	input.mc-btn-primary:focus,
	.mc-button:focus,
	a.mc-button:focus,
	button.mc-button:focus,
	input.mc-button:focus,
	.mc-home-search-btn:focus,
	a.mc-home-search-btn:focus {
	  outline: none;
	  box-shadow: 0 0 0 3px var(--mc-focus-ring, rgba(2,118,255,0.20)) !important;
	}

/* Links that act as actions (e.g. edit/delete links) */
a.mc-action,
a.mc-edit,
a.mc-delete,
a.mc-edit-link,
a.mc-delete-link,
.mc-actions a,
.mc-listing-actions a {
  /* inherit button look */
  align-items: center;
  appearance: button;
  background-color: var(--mc-primary, #0276FF);
  border-radius: 8px;
  border-style: none;

  box-sizing: border-box;
  color: #fff;
  cursor: pointer;
  display: inline-flex;
  flex-direction: row;
  flex-shrink: 0;
  font-family: inherit;
  font-size: 100%;
  line-height: 1.15;
  margin: 0;
  padding: 10px 21px;
  text-align: center;
  transition: color .13s ease-in-out, background .13s ease-in-out, opacity .13s ease-in-out, box-shadow .13s ease-in-out;
  user-select: none;
  -webkit-user-select: none;
  touch-action: manipulation;
  text-decoration: none;
}

.mc-actions a:hover,
.mc-listing-actions a:hover,
a.mc-action:hover,
a.mc-edit:hover,
a.mc-delete:hover,
a.mc-edit-link:hover,
a.mc-delete-link:hover {
  background-color: var(--mc-primary) !important;
  color: #fff;
  text-decoration: none;
}

/* Layout helper: keep multiple action buttons readable */
.mc-actions,
.mc-listing-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

/* =========================================================
 * Homepage search: input + button in one row (desktop + mobile)
 * Notes:
 * - If markup contains an accidental <code> wrapper, neutralize it via display: contents.
 * ======================================================= */

.mc-home-search-row {
  display: flex;
  gap: 10px;
  align-items: center;
  width: 100%;
}

/* Safety: if a <code> wrapper exists inside the row, do not break the flex layout */
.mc-home-search-row > code {
  display: contents;
}

.mc-home-search-input {
  flex: 1 1 auto;
  width: auto;
  min-width: 0;
}

.mc-home-search-btn {
  flex: 0 0 auto;
  white-space: nowrap;
}

/* =========================================================
/* =========================================================
   Auth forms (Login / Register)
   - Make Login visually match Register
   - Fix missing inner padding inside the auth card
   Scope: Only METRA auth page markup (.mc-auth-page)
   ========================================================= */

.mc-auth-page .mc-card{
  background:#fff;
  border:1px solid #e8e8e8;
  border-radius:18px;

  padding:24px 28px;
}

@media (max-width: 520px){
  .mc-auth-page .mc-card{ padding:18px 16px; }
}

/* Form spacing */
.mc-auth-page .mc-card p{ margin:0 0 14px; }
.mc-auth-page .mc-card p:last-child{ margin-bottom:0; }

.mc-auth-page .mc-card label{
  display:block;
  margin:0 0 6px;
}

/* Inputs full width */
.mc-auth-page .mc-card input[type="text"],
.mc-auth-page .mc-card input[type="email"],
.mc-auth-page .mc-card input[type="password"],
.mc-auth-page .mc-card input[type="search"],
.mc-auth-page .mc-card select,
.mc-auth-page .mc-card textarea{
  width:100% !important;
  max-width:100% !important;
  box-sizing:border-box;
}

/* Action row: left link + right button */
.mc-auth-page .mc-card .mc-auth-actions,
.mc-auth-page .mc-card .mc-login-actions{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  flex-wrap:wrap;
}

.mc-auth-page .mc-card .mc-auth-actions a,
.mc-auth-page .mc-card .mc-login-actions a{
  text-decoration:none;
}

/* Step 2: Hide categories sidebar on <=980px (desktop resized to mobile breakpoint) */
@media (max-width: 980px){
  .mc-home-sidebar{ display:none !important; }
}

/* mc6951 / 0.6942: <=980px categories button below search (home/listings) */
.mc-home-categories-toggle { display:none; margin:10px 0 14px; }
.mc-home-categories-toggle > summary { list-style:none; cursor:pointer; }
.mc-home-categories-toggle > summary::-webkit-details-marker { display:none; }
@media (max-width:980px){
  .mc-home-categories-toggle{ display:block; }
  .mc-home-categories-btn{ display:block; width:100%; text-align:center; padding:14px; }
  .mc-home-categories-content{ margin-top:10px; }
}

/* Desktop: 10px Abstand zwischen Suchzeile und Listings */
@media (min-width: 981px){
    .mc-listings{
        margin-top: 10px;
    }
}

/* 0.0206: mobile top categories bar removed */
@media (max-width: 980px){
  .mc-mobile-topnav{ display:none !important; }
}
CSS;
}


// NOTE: Keep version explicit (do not rely on get_file_data),
// because this file contains CSS blocks that may include header-like strings.
// === MC: Dynamic version v0.0595 START ===
// Read version from plugin header so it never gets out of sync.
// Rollback: replace with define('MC_VERSION', 'x.xxxx');
if (!defined('MC_VERSION')) {
    $mc_plugin_data = get_file_data(__FILE__, ['Version' => 'Version'], 'plugin');
    define('MC_VERSION', $mc_plugin_data['Version'] ?? '0.0726');
}
// === MC: Dynamic version v0.0595 END ===
define('MC_PLUGIN_FILE', __FILE__);
define('MC_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Admin notice: required METRA pages missing.
 * Shows a warning instead of failing silently (e.g. 404 on /mc-submit-category/).
 */
add_action('admin_notices', static function (): void {
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    // Only show in wp-admin.
    $required = [
        'mc-login' => __('METRA Login Page', 'metra-classifieds-plugin'),
        'mc-register' => __('METRA Register Page', 'metra-classifieds-plugin'),
        'mc-submit' => __('METRA Post a listing Page', 'metra-classifieds-plugin'),
        'mc-submit-category' => __('METRA Category Selection (Wizard)', 'metra-classifieds-plugin'),
    ];

    $missing = [];
    foreach ($required as $slug => $label) {
        $p = get_page_by_path($slug);
        if (!$p || $p->post_status !== 'publish') {
            $missing[] = $slug;
        }
    }

    if (!$missing) {
        return;
    }

    $permalinks_url = esc_url(admin_url('options-permalink.php'));
    $plugins_url = esc_url(admin_url('plugins.php'));

    echo '<div class="notice notice-warning"><p><strong>METRA Classifieds:</strong> ';
    echo esc_html__('Required METRA pages are missing (or unpublished). Features like category drilldown may not work correctly.', 'metra-classifieds-plugin');
    echo '</p><p>';
    echo esc_html__('Missing slugs:', 'metra-classifieds-plugin') . ' <span class="mc-admin-missing-slugs">' . esc_html(implode(', ', $missing)) . '</span>';
    echo '</p><p>';
    echo esc_html__('Recommended:', 'metra-classifieds-plugin') . ' ';
    echo '<a href="' . $plugins_url . '">' . esc_html__('Deactivate and reactivate the plugin', 'metra-classifieds-plugin') . '</a>';
    echo ' · ';
    echo '<a href="' . $permalinks_url . '">' . esc_html__('Save permalinks once', 'metra-classifieds-plugin') . '</a>';
    echo '.</p></div>';
});

// === MC: Lucide load-order fix v0.0595 START ===
// Must be loaded BEFORE class-mc-plugin.php (which loads class-mc-icons.php,
// which calls metra_lucide_render()). Loading them after caused a Fatal Error.
// Rollback: move these two lines back to the end of this file.
require_once plugin_dir_path(__FILE__) . 'inc/lucide/lucide-registry.php';
require_once plugin_dir_path(__FILE__) . 'inc/lucide/lucide-render.php';
require_once plugin_dir_path(__FILE__) . 'inc/lucide/lucide-categories.php';
// === MC: Lucide load-order fix v0.0595 END ===

require_once MC_PLUGIN_DIR . 'includes/class-mc-plugin.php';


require_once __DIR__ . '/includes/class-mc-master-css.php';
function mc_run_plugin(): void
{
    $plugin = new \MetraClassifieds\MC_Plugin();
    $plugin->run();
}

mc_run_plugin();

// --- Admin Users: remove 'Name' and Core 'Posts', add 'Listings' ---
add_filter('manage_users_columns', 'mc6970_users_columns', 20);
add_filter('manage_users_custom_column', 'mc6970_users_column_render', 20, 3);

function mc6970_users_columns($columns)
{
    if (isset($columns['name'])) {
        unset($columns['name']);
    }
    if (isset($columns['posts'])) {
        unset($columns['posts']);
    }
    $columns['mc_listings'] = __('Listings', 'metra-classifieds-plugin');
    return $columns;
}

function mc6970_users_column_render($value, $column_name, $user_id)
{
    if ($column_name !== 'mc_listings') {
        return $value;
    }
    global $wpdb;
    return (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(ID)
         FROM {$wpdb->posts}
         WHERE post_type = %s
           AND post_author = %d
           AND post_status NOT IN ('trash','auto-draft')",
        'mc_listing',
        (int)$user_id
    ));
}





// ============================================================
// Feature: METRA admin menu icon color fix
// Version: 0.0610
// Purpose: WordPress masks SVG data:URI icons to white/grey.
//   Override to keep our blue circle (#0092e6) visible.
// Rollback: remove this add_action block.
// ============================================================
add_action('admin_head', static function (): void {
    echo '<style>
    /* Always show blue circle, grey only on current/hover (inverted) */
    #adminmenu #menu-posts-mc_listing .wp-menu-image::before {
        content: "" !important;
        font-family: none !important;
        background-image: none !important;
        -webkit-mask-image: none !important;
        mask-image: none !important;
        display: block !important;
        width: 20px !important;
        height: 20px !important;
        background: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iOSIgZmlsbD0iIzAwOTJlNiIvPjwvc3ZnPg==") center / 20px no-repeat !important;
        float: none !important;
        margin: 0 auto !important;
    }
    /* On hover/current: slightly lighter blue instead of grey */
    #adminmenu li#menu-posts-mc_listing:hover .wp-menu-image::before,
    #adminmenu li#menu-posts-mc_listing.wp-has-current-submenu .wp-menu-image::before,
    #adminmenu li#menu-posts-mc_listing.current .wp-menu-image::before {
        background: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iOSIgZmlsbD0iI2ZmZmZmZiIvPjwvc3ZnPg==") center / 20px no-repeat !important;
    }
    </style>';
});



// ============================================================
// Feature: AJAX drilldown for category bottom sheet
// Version: 0.0652
// Purpose: When user taps a non-leaf category in the mobile
//   bottom sheet, load the next level via AJAX instead of
//   navigating to mc-categories/?parent=ID. Keeps the sheet
//   open at full width on all levels.
// Rollback: remove this add_action block.
// ============================================================
add_action('wp_ajax_mc_cats_drilldown', '\MetraClassifieds\MC_Ajax_Cats::handle');
add_action('wp_ajax_nopriv_mc_cats_drilldown', '\MetraClassifieds\MC_Ajax_Cats::handle');


// ============================================================
// Feature: Hide page title on mc-listings page
// Version: 0.0726
// ============================================================
add_action('wp_head', static function(): void {
    if (!is_page()) return;
    $page = get_page_by_path('mc-listings');
    if (!$page || (int) get_queried_object_id() !== (int) $page->ID) return;
    echo '<style>.entry-title,.page-title,h1.entry-title,h1.page-title,.page-header,.inside-page-header{display:none!important}</style>';
});

// ============================================================
// Feature: body class for dedicated categories page
// Version: 0.0626
// Purpose: Add mc-categories-page to <body> only on the
//   mc-categories page so CSS can target it without affecting
//   other pages that also contain a catwiz panel.
// Rollback: remove this add_filter block.
// ============================================================
add_filter('body_class', static function(array $classes): array {
    if (!is_page()) return $classes;
    $id = (int) get_queried_object_id();

    $page_cats = get_page_by_path('mc-categories');
    if ($page_cats && $id === (int) $page_cats->ID) {
        $classes[] = 'mc-categories-page';
    }

    $page_listings = get_page_by_path('mc-listings');
    if ($page_listings && $id === (int) $page_listings->ID) {
        $classes[] = 'mc-listings-page';
    }

    return $classes;
});

// === MC: Body-Class für mobile Einzelansicht v0.0724 START ===
add_filter('body_class', static function(array $classes): array {
    if (is_singular('mc_listing') && wp_is_mobile()) {
        $classes[] = 'mc-mobile-single';
    }
    return $classes;
});
// === MC: Body-Class für mobile Einzelansicht v0.0724 END ===

// ============================================================
// Feature: Admin menu accordion (inline submenu)
// Version: 0.0609
// Purpose: Replace WordPress default fly-out submenu for the
//   METRA Classifieds CPT menu with an inline accordion that
//   expands below the main entry — like All-in-One WP Migration.
// Rollback: remove this add_action block.
// ============================================================
add_action('admin_head', static function (): void {
    $screen = get_current_screen();
    if (!$screen) return;
    // Only inject on pages that belong to our CPT menu
    $is_our_page = (
        isset($_GET['post_type']) && $_GET['post_type'] === 'mc_listing'
    ) || (
        isset($_GET['page']) && strpos((string) $_GET['page'], 'metra-classifieds') === 0
    ) || (
        isset($_GET['taxonomy']) && $_GET['taxonomy'] === 'mc_category'
    ) || in_array($screen->post_type ?? '', ['mc_listing'], true);
    if ($is_our_page) { ?>
    <style>
    /* METRA admin menu accordion — active page: show submenu inline */
    #adminmenu li.menu-top[id*="mc_listing"] > ul.wp-submenu {
        position: static !important;
        display: block !important;
        box-shadow: none !important;
        background: transparent !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        min-width: 0 !important;
        left: auto !important;
        top: auto !important;
    }
    #adminmenu li.menu-top[id*="mc_listing"] > ul.wp-submenu li a {
        padding-left: 20px !important;
    }
    #adminmenu li.menu-top[id*="mc_listing"] > ul.wp-submenu li.wp-first-item a {
        padding-top: 4px !important;
    }
    </style>
    <?php } ?>
    <script>
    (function(){
        var item = document.querySelector('#adminmenu li.menu-top[id*="mc_listing"]');
        if (!item) return;
        var toggle = item.querySelector('a.menu-top');
        if (!toggle) return;
        var isOurPage = <?php echo wp_json_encode($is_our_page); ?>;
        // On our pages: submenu already visible via CSS, click toggles
        // On other pages: let WordPress handle hover fly-out normally
        if (!isOurPage) return;
        toggle.addEventListener('click', function(e){
            var sub = item.querySelector('ul.wp-submenu');
            if (!sub) return;
            e.preventDefault();
            e.stopPropagation();
            sub.style.display = sub.style.display === 'none' ? 'block' : 'none';
        });
    })();
    </script>
    <?php
});



/* ===== Master CSS Bootstrap (Last wins) ===== */
\MC\Master_CSS::ensure_exists();

register_activation_hook(__FILE__, function (): void {
    \MC\Master_CSS::ensure_exists();
});

add_action('plugins_loaded', function (): void {
    \MC\Master_CSS::ensure_exists();
}, 5);


add_action('wp_enqueue_scripts', function (): void {
    \MC\Master_CSS::enqueue_last_wins();
}, 10000);


/* ===== Frontend Admin Bar Policy =====
 * 0.0390: Hide the WordPress admin bar on the public site for logged-in users,
 * except administrators who keep the admin bar in the frontend.
 * This is implemented via show_admin_bar filter (not CSS) to avoid leftover layout offsets.
 * WP admin backend remains unchanged.
 */
add_filter('show_admin_bar', static function ($show): bool {
    if (is_admin()) {
        return (bool) $show;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    return false;
}, 1000);


/**
 * Register Condition Taxonomy (Proposal A)
 */

- 0.0726: Plugin-Design-Schalter und Offcanvas-Navigation entfernt. mc-theme.css wird jetzt immer geladen. Kein Hamburger-Menu mehr durch das Plugin. Navigation liegt vollstaendig beim aktiven Theme.
- 0.0725: Theme-spezifische Bereinigung. GP-exklusive CSS-Selektoren (inside-*, GeneratePress-Container-Block) entfernt. Admin-Texte mit Theme-Bezug bereinigt. Font-Dropdown Inherit from theme geaendert zu System default. Plugin laeuft nun vollstaendig theme-neutral via Shortcodes.
- 0.0724: Mobile: .mc-seller-info bekommt gleiches padding-left/right wie
  .mc-msingle-info. Rollback: @media-Block entfernen.
- 0.0723: Mobile Einzelansicht: Body-Class mc-mobile-single ergänzt.
  Header, Navigation und Hamburger per CSS ausgeblendet.
  Rollback: body_class Filter und CSS-Block entfernen.
- 0.0722: mc-msingle rand-an-rand: padding der Eltern-Container (mc-single,
  site-content, content-area) auf Mobile via :has(.mc-msingle) auf 0 gesetzt.
  Rollback: den @media-Block entfernen.
- 0.0721: mc-msingle auf 100vw mit calc(50% - 50vw) Margins — rand-an-rand
  unabhängig vom Theme-Padding. Rollback: width/margin auf vorherige Werte.
- 0.0720: Mobile Chevrons: schwarze Rechtecke (32x56px), randbündig links/rechts,
  vertikal mittig. Weißes Chevron-SVG. Rollback: CSS auf v0.0719 zurücksetzen.
- 0.0719: Chevrons unten im Hero-Bild (bottom:14px), weißer Kreis (#fff).
  Rollback: CSS auf v0.0718 zurücksetzen.
- 0.0718: Mobile Einzelansicht: Chevron-Buttons links/rechts im Hero-Bild
  bei mehreren Bildern (50% transparenter weißer Kreis, vertikal mittig).
  Endlos-Navigation. setIdx() aktualisiert jetzt auch heroImg und heroCount.
  Rollback: Chevron-Buttons aus Template und CSS entfernen, setIdx zurücksetzen.
- 0.0717: Desktop-Einzelansicht: Titel aus mc-single-gallery-col heraus,
  jetzt erstes Element in mc-single-details-col oberhalb der Beschreibung.
  CSS-Selector von mc-single-gallery-col auf mc-single-details-col geändert,
  margin: 0 0 16px 0. Rollback: h1 zurück in gallery-col, CSS zurücksetzen.
- 0.0716: .mc-listing gap 14px→11px (3px weniger Abstand Bild→Text).
  .mc-listing-body padding-right: 3px (Abstand zum rechten Rand).
  Rollback: gap auf 14px zurück, padding-right entfernen.
- 0.0715: mc-listing-excerpt bekommt color: var(--mc-text, inherit).
  Widget-Titel (.widget_mc_categories_widget h2) explizit schwarz gesetzt.
  Rollback: beide Zeilen entfernen.
- 0.0715: ensure_managed_block() aktualisiert Blöcke jetzt immer beim
  Plugin-Update statt nur bei fehlenden Blöcken. Neue Hilfsmethode
  replace_or_append_block(). mc-listing-excerpt color: var(--mc-text).
  Rollback: replace_or_append_block und neues ensure_managed_block entfernen.
- 0.0714: Design-Tokens werden beim Speichern der Design-Settings direkt
  ans Ende von metra-master.css geschrieben (METRA TOKENS BLOCK). Damit
  kommen die :root-Werte garantiert nach allen anderen CSS-Regeln.
  Neue Methoden: MC_Design::get_tokens_public(),
  MC_Settings::write_tokens_to_master_css().
  Rollback: beide Methoden und den write_tokens_to_master_css()-Aufruf
  in handle_settings_save() entfernen.
- 0.0713: Bugfix: lokaler --mc-home-cat-parent-color Token in master-css
  überschrieb den :root Token aus Design-Settings. Lokale Deklaration entfernt.
  "Headline color" Feld aus Design-Tab entfernt (add_settings_field,
  field_design_heading Callback entfernt). --mc-heading Token bleibt intern
  erhalten für Fallbacks.
  Rollback: lokale Deklaration und Feld/Callback wiederherstellen.
- 0.0712: Bugfix: !important auf color bei .mc-listing-title-link,
  .mc-single-title und .mc-home-cat-parent ergänzt. Theme-CSS hatte
  die Token-Farben überschrieben.
  Rollback: !important aus den drei color-Regeln entfernen.
- 0.0711: Widget-Kategorien immer schwarz (#000 !important in mc-categories-widget.css).
  Neuer Token --mc-home-cat-parent-color für linke Sidebar-Hauptkategorien
  (Standard: #1e73be). Neues Farbfeld "Hauptkategorien-Farbe (Sidebar)" im
  Design-Tab nach "Anzeigentitel-Farbe".
  Rollback: CSS-Zeilen in widget.css entfernen, Token/Feld/Callback rückgängig.
- 0.0710: Neuer Token --mc-listing-title-color für AZ-Titel (Standard: #1a1a1a).
  Neues Farbfeld "Anzeigentitel-Farbe" im Design-Tab nach "Headline color".
  --mc-heading bei AZ-Titeln wieder entfernt (war v0.0709-Fehler).
  Betrifft: .mc-listing-title-link, .mc-single-title, .mc-single-title-below.
  Rollback: Token, Feld, Callback und CSS-Zeilen entfernen.
- 0.0709: Bugfix: --mc-heading Token wurde nicht auf Anzeigentitel angewendet.
  .mc-listing-title-link: color inherit → var(--mc-heading, inherit).
  .mc-single-title: color var(--mc-heading, inherit) ergänzt.
  .mc-single-title-below: color var(--mc-heading, inherit) !important ergänzt.
  Rollback: alle drei color-Zeilen entfernen.
- 0.0708: Live-Zähler und METRA-Style-Modal für Titel-Eingabe in allen drei
  Formularen (Submit-Neu, Edit-Page, Inline-Edit). Zähler zeigt verbleibende
  Zeichen, wird ab 5 Zeichen rot. Modal öffnet sich bei Überschreitung,
  Text wird automatisch auf max. Länge gekürzt. Schließen per OK, Backdrop-
  Klick oder Escape. Script wird nur einmal pro Seite gerendert.
  Rollback: render_title_guard_script() entfernen, alle drei Titel-Inputs
  auf vorherige Version zurücksetzen.
- 0.0707: Bugfix: Inline-Edit-Form (dritte Edit-Variante) hatte weder
  maxlength im Input noch mb_substr in handle_edit_submit. Beide ergänzt.
  Rollback: maxlength und mb_substr-Block aus inline-edit-Form und
  handle_edit_submit entfernen.
- 0.0706: Bugfix: mc_title_max_chars wurde in handle_settings_save nicht
  gespeichert. update_option-Aufruf nachgetragen. Rollback: diese Zeile
  aus handle_settings_save entfernen.
- 0.0705: Neue Settings-Option mc_title_max_chars (Basic-Tab, direkt über
  excerpt length). Bezeichnung: "Anzahl Zeichen im Anz.-Titel". Standard: 35.
  Placeholder im Eingabefeld zeigt "Max. XX". Validierung serverseitig in
  Submit- und Edit-Formular (mb_substr). maxlength-Attribut im HTML-Input
  dynamisch aus Option. Rollback: register_setting, add_settings_field,
  field_title_max_chars aus settings entfernen; Submit/Edit-Formulare auf
  maxlength="140" und mb_substr($title, 0, 140) zurücksetzen.
- 0.0704: Desktop-Einzelansicht: .mc-single-gallery-col bekommt width:100%
  und background:#fff. Weißer Hintergrund füllt jetzt immer die volle
  Breite der Galerie-Spalte, unabhängig von Bildbreite oder Textlänge.
  Rollback: width:100% und background:#fff aus .mc-single-gallery-col entfernen.
- 0.0670: Hide listings page title via wp_head inline style.
- 0.0669: Add mc-listings-page body class via PHP filter. CSS uses both
  page-slug-mc-listings and mc-listings-page to hide the title.
- 0.0668: Hide page title "Listings" on mc-listings page, desktop and mobile.
- 0.0667: mc-msingle-bar-fav added to same CSS rule as contact button.
- 0.0666: .mc-msingle-bar > a all states color:#fff !important.
- 0.0665: Inline style color:#fff on guest favorite button via extra_attributes.
- 0.0664: Force white text on mc-msingle-bar-fav for all element types.
  a:link, a:visited, a:hover, button all get color:#fff !important.
- 0.0663: Fix white text on guest favorite button (the <a> tag variant).
  Added mc-msingle-bar-contact-btn class to guest action button call.
- 0.0662: Favorite button gets same class as Contact Seller button
  (mc-msingle-bar-contact-btn) so identical CSS applies — white text
  guaranteed via color:#fff !important on that class.
- 0.0661: Contact Seller text vertically centered (display:inline-flex,
  align-items:center, fixed height 48px). Favorite button label fix:
  data-mc-base-color and data-mc-base-captured set in HTML so JS does
  not overwrite white text with a dark color on reset.
- 0.0660: White text guaranteed in all component states:
  mc-btn-favorite.is-favorite: color + label both #fff !important.
  mc-btn-contact: all child elements #fff !important.
- 0.0659: Guarantee white label text in mobile bar favorite button.
  Added !important and explicit .mc-favorite-button__label color rule.
- 0.0658: Two new reusable button components:
  mc-btn-favorite: inline pill, border, heart icon+label. In mobile bar:
  blue pill, full width. JS hook mc-favorite-button unchanged.
  mc-btn-contact: blue pill, full width in bar. Both added to all
  existing usages in template and listing-view.
- 0.0657: Fix favorite button label not showing.
  JS looks for .mc-favorite-button__label to update text.
  Added this class to the span in the template.
- 0.0656: Mobile bar pills: favorite button shows icon + Save/Saved text.
  All buttons pill-shape (border-radius:999px), equal width, blue.
- 0.0655: Fix mobile bar showing only one button.
  Favorite button was hidden (display:none) — now blue, flex:1, equal
  width. Both buttons (favorite + contact) fill the bar 50/50.
- 0.0654: Mobile single listing bar: both buttons blue, equal width (flex:1),
  16px side padding, border-radius 12px. Delete button stays red.
- 0.0653: Fix AJAX drilldown not loading subcategories.
  Two bugs: (1) MC_Icons::get_term_icon_svg() does not exist — replaced
  with render_mobile_category_list_icon(). (2) MC_Shortcodes::page_url()
  is private — replaced with MC_Pages::get_page_url().
- 0.0652: AJAX drilldown in category bottom sheet.
  Non-leaf categories now load next level via fetch() instead of
  navigating to mc-categories page. Sheet stays open at full width
  on all levels. Back button restores previous level from history.
  Standalone mc-categories page still uses normal navigation.
- 0.0651: Sheet category list refinements.
  Chevron: SVG instead of Unicode, 22px, stroke-width 2.8 (visually bolder).
  Section label "ALL CATEGORIES" centered.
  8px extra left padding before icons (padding-left 22px).
- 0.0650: Sheet animation .85s, overlay removed.
- 0.0649: Category sheet animation set to .7s.
- 0.0648: Category sheet animation slowed from .3s to .45s.
- 0.0647: Two reusable button components introduced:
  mc-search-btn: desktop=blue text+icon button, mobile=44px lupe icon only.
  mc-cats-btn: desktop=hidden, mobile=44px chevron icon button.
  Both usable anywhere in the plugin with consistent responsive behaviour.
- 0.0646: Fix chevron button not showing on mobile.
  mc-home-categories-btn had display:none !important globally, overriding
  the mobile display:inline-flex on mc-home-cats-btn--icon (same element).
  Fixed: mc-home-categories-btn now only hidden on desktop (min-width:981px).
- 0.0645: Mobile search bar redesigned.
  Desktop: blue Search button unchanged.
  Mobile: blue button replaced by two icon buttons in search row:
  (1) Magnifier icon = submit search.
  (2) Chevron-down icon = open category bottom sheet.
  Both icon buttons: 44x44px, 1.5px border, border-radius 10px.
- 0.0644: Show categories button hidden on desktop with !important.
  Theme button styles were overriding display:none without !important.
- 0.0643: Categories list page: removed whitespace gaps under empty categories.
  Changed layout from CSS Grid (equal row heights) to CSS Columns
  (flow layout). Items fill space without gaps. 3 cols desktop,
  2 cols tablet, 1 col mobile.
- 0.0642: Bottom sheet and button now mobile-only (max-width:980px).
  Button, overlay and sheet all hidden on desktop via CSS.
  Desktop uses existing sidebar navigation unchanged.
- 0.0641: Category picker rebuilt as real Bottom Sheet (Kleinanzeigen-style).
  Replaces <details> toggle entirely. Fixed bottom sheet slides up over
  current page with overlay, handle bar, title, close button.
  Swipe down to close. Auto-opens on cats=1 URL param.
  Works from any trigger: blue button or hamburger link.
- 0.0640: Fix cats=1 auto-open. Use DOMContentLoaded + setAttribute("open","")
  instead of d.open=true inside IIFE. Ensures DOM is ready and the
  open attribute is set before browser renders the page.
- 0.0639: Hamburger "Categories" link now opens category panel automatically.
  URL: mc-listings/?cats=1. JS detects cats=1 param and sets d.open=true
  on the details element, triggering the toggle handler immediately.
- 0.0638: Hamburger menu "Categories" link now points to mc-listings
  instead of mc-categories. Both entry points show the same layout.
  mc-categories page remains for drilldown navigation (parent param).
- 0.0637: Both category entry points now show identical layout.
  Added mobile override for .mc-catwiz-standalone .mc-catwiz-sheet:
  no border-radius, no shadow, edge-to-edge on max-width:980px.
  mc-categories-main breaks out of theme container padding on mobile.
- 0.0636: Listings page category panel improvements.
  Show categories button hidden when panel is open (details[open] > summary).
  Panel edge-to-edge with negative margins matching mc-single breakout.
  Dedicated mc-categories page (Bild 2) already correct via template.
- 0.0635: Fixed Show categories panel collapsing after open.
  Root cause: .mc-cats-open .mc-home-categories-toggle was hiding the
  <details> element itself when open, collapsing the panel content.
  Removed .mc-home-categories-toggle from the hide list — only search
  bar and listings are hidden, the panel stays visible.
- 0.0634: Categories page now uses dedicated minimal template.
  page-mc-categories.php: only get_header(), the_content(), get_footer().
  No search bar, no sidebar, no listings — just the category panel.
  Registered in class-mc-templates.php for slug mc-categories.
  All previous CSS hacks removed.
- 0.0633: Hide search/buttons on dedicated categories page (3-layer fix).
  Layer 1: body_class PHP filter (mc-categories-page).
  Layer 2: JS classList.add(mc-catwiz-page) in categories_grid().
  Layer 3: CSS :has(.mc-catwiz-standalone):not(:has(.mc-home-categories-content
  .mc-catwiz-standalone)) — detects standalone vs embedded by DOM structure.
  Listings page unaffected (its standalone is inside mc-home-categories-content).
- 0.0632: Fix search/button hiding on categories page. Two-layer approach:
  (1) body_class filter uses get_queried_object_id() instead of get_the_ID().
  (2) JS adds mc-catwiz-page to body when standalone categories_grid runs.
  CSS hides search+buttons via both .mc-categories-page and .mc-catwiz-page.
- 0.0631: Close button always shown, links to listings page.
  Listings and sidebar hidden when category panel is open on mobile.
  CSS details[open] sibling selector + JS toggle class mc-cats-open
  on .mc-home-main for reliable cross-browser behaviour.
- 0.0630: Fixed "Close" still appearing on Listings page.
  Root cause: is_page() returns true on any WP page so the check was
  unreliable. Fixed with _embedded parameter: build_home_search_bar()
  calls categories_grid([_embedded=>1]) directly; the function shows
  Cancel+spacer when embedded, spacer+Close when standalone.
- 0.0629: Fixed "Close" appearing on Listings page category panel.
  categories_grid() now detects context: on mc-categories page shows
  spacer+Categories+Close, on all other pages (Bottom-Sheet) shows
  Cancel+Categories+spacer as before.
- 0.0628: Hide search bar, search button and Show-categories button on
  mobile categories page. Uses .mc-categories-page body class (set via
  PHP body_class filter) so only that page is affected.
- 0.0627: Fixed Fatal error "Class MC\Pages not found" on line 1375.
  Root cause: body_class filter ran before plugin classes were loaded.
  Fixed by using get_page_by_path("mc-categories") instead of MC\Pages.
- 0.0626: Fixed search bar and categories button disappearing on listings page.
  Root cause: v0.0625 used body:has(.mc-catwiz-standalone) which matched
  the listings page too. Fixed: CSS now uses .mc-categories-page body class
  which is added via body_class filter only on the dedicated categories page.
- 0.0625: Mobile categories page redesigned (Kleinanzeigen-style).
  Topbar: spacer left, "Categories" bold center, "Close" right.
  Close links to listings page (not homepage).
  On mobile: WP page title, search bar and content after panel hidden.
  Panel fills full width edge-to-edge with no border or shadow.
  All strings translatable. Desktop unchanged.
- 0.0624: Mobile category panel now edge-to-edge. Standalone sheet breaks
  out of theme container with negative margins on mobile. Row padding
  reduced to 10px 14px. Section label smaller. Desktop unchanged.
- 0.0623: Mobile category list refinements based on live screenshot.
  Rows more compact (11px 16px padding), sheet border-radius reduced,
  section label smaller+uppercase, categories without icon get left
  padding to flush with icon-row text, chevron smaller.
- 0.0622: Mobile category list redesigned (Kleinanzeigen-style).
  Larger icon box (48px, rounded), bolder name (17px, weight 600),
  more padding per row (14px 20px), thinner dividers, edge-to-edge list,
  topbar title and cancel button enlarged, section label bigger.
  Desktop layout unchanged.
- 0.0621: Mobile single listing: edge-to-edge width and fluid typography.
  mc-msingle breaks out of mc-single padding with negative margins.
  All inner paddings use clamp(10px, 2.5vw, 16px) — adapts to screen width.
  Font sizes fluid: title clamp(17px,4.5vw,21px), price clamp(18px,5vw,22px),
  body text clamp(13px,3.5vw,15px), meta clamp(12px,3.2vw,14px).
- 0.0620: Internationalisation audit for mobile single listing block.
  14 missing strings added to de_DE, de_DE_formal, en_US and .pot:
  Activate, Category, Close, Condition, Condition:, Contact Seller,
  Delete, Edit, Favorite, Listing ID, Listing not available.,
  Pause, Price:, Set as Favorite.
- 0.0619: Mobile single listing improvements:
  (1) Category row added below Condition in property table.
  (2) Listing ID shown below description, separated by divider line.
  (3) Fixed action bar: side padding 16px, bottom padding increased to
      max(16px, safe-area-inset-bottom) to avoid phone screen rounding.
- 0.0618: Fixed PHP parse error in single-mc_listing.php (v0.0617 inserted
  mobile block at wrong position). Rebuilt template with correct structure:
  if(wp_is_mobile()) mobile block, else desktop block, both inside main.
- 0.0617: New mobile single listing layout (Kleinanzeigen-style).
  Full-width hero image with tap-to-open fullscreen gallery overlay.
  Swipe left/right in overlay to navigate images, X to close.
  Info block: title, price, location icon, date icon.
  Property table: condition as key/value rows with dividers.
  Fixed bottom action bar: heart favorite, contact button, owner actions.
  Desktop layout unchanged. wp_is_mobile() gates the new block.
- 0.0616: Adjusted listing card gap from 16px to 14px.
- 0.0615: Increased gap between thumbnail and text in listing cards from 12px to 16px.
- 0.0614: Removed card padding, card radius and image radius completely.
  CSS: padding and border-radius set to 0 directly (no longer via CSS vars).
  Design tab: removed Card radius and Card padding fields.
  class-mc-design.php: defaults set to 0px, removed from token map.
  Images and cards now display edge-to-edge without rounded corners.
- 0.0613: Removed category description feature (v0.0608) completely.
  Removed: mc-catwiz-name-wrap HTML wrapper, term->description output,
  mc-catwiz-desc and mc-catwiz-name-wrap CSS. Code fully cleaned.
- 0.0612: Fixed missing submenu items. Root cause: accordion CSS hid the
  submenu on ALL admin pages, not just ours. Fixed: submenu CSS only outputs
  on our pages (is_our_page check). On other pages WordPress fly-out works
  normally. On our pages submenu is always visible inline.
- 0.0611: Fixed admin menu icon — circle is now blue (#0092e6) by default,
  white on hover/active (previously inverted). CSS targets ::before pseudo
  element directly to override WordPress mask.
- 0.0610: Changed METRA admin menu icon from dashicons-megaphone (megaphone) to
  a blue circle (#0092e6). Uses SVG data:URI. CSS override prevents WordPress
  from masking the icon to white/grey.
- 0.0609: Admin menu now expands inline below main entry instead of fly-out.
  CSS hides the default absolute-positioned submenu and JS toggles it as a
  block. Active page auto-opens the menu. Rollback: remove admin_head block.
- 0.0608: Category description now shown below category name in the categories
  grid. The description field in WordPress category edit is now rendered as a
  small subtitle line under the category name. CSS added for mc-catwiz-desc.
- 0.0607: Added category filter bar to icon picker. All 42 official Lucide
  categories (animals, transportation, home, tools etc.) are shown as
  clickable pills above the search field. Clicking a category filters the
  grid to that category only. Search works within the active category.
  Category map generated from lucide-main/icons/*.json metadata.
- 0.0606: Fixed SyntaxError in icon picker JS. Root cause: ob_start/ob_get_clean
  was causing PHP to incorrectly process the JS block. Replaced with PHP
  NOWDOC (<<<'MCIPKRJS') which outputs JS literally without any processing.
- 0.0605: Fixed icon picker grid staying empty. Root cause: IIFE in footer ran
  before window.mcIpkrLucide was set by inline data script in the form. Fixed
  by moving JS directly after the data script inside render_picker_markup via
  ob_start/ob_get_clean — both scripts now output in correct order.
- 0.0604: Fixed icon picker showing only 50 icons. Root cause: get_builtin_icon_registry()
  returned the old hardcoded 50-entry list, ignoring the new metra_lucide_icons()
  dynamic registry entirely. Fixed by rewriting get_builtin_icon_registry() to
  call metra_lucide_icons() and build the registry from all SVG files.
- 0.0603: Fixed PHP parse error in class-mc-icons.php. Root cause: JS code was
  inside echo "..." with double quotes — PHP interpreted $ signs as variables
  causing a parse error. Fixed by switching to PHP open/close tags around the
  JS block so it is output literally without interpolation.
- 0.0602: Fixed icon picker JS not initialising. Root cause: footer script used
  DOMContentLoaded which already fired; switched to immediate IIFE. Rewrote
  JS to be more robust: direct DOM queries, no event-order dependency,
  fixed plugins_url path for SVG fetch.
- 0.0601: Fixed icon picker breaking categories overview page. Root cause:
  render_add_fields was loading 1694-icon JSON on the overview page inline add-form.
  Add-form now shows a plain hint "save first, then edit to assign icon".
  Full picker only renders on the single category edit page.
- 0.0600: Rebuilt category icon picker. New visual grid with search, pagination
  (80 icons per page), tab switch between Lucide and Custom icons, live SVG
  preview of selected icon, clear button. SVGs loaded on demand via fetch and
  cached — DOM stays lean even with 1694 icons. Color and size controls
  redesigned into compact control bar.
- 0.0599: Replaced manual 52-icon Lucide registry with full library (1694 icons).
  Registry now auto-reads all SVG files from assets/lucide/ — new icons appear
  automatically without code changes. Rollback: restore previous lucide-registry.php.
- 0.0598: Fixed listing cards showing image above text at 769-980px viewport width.
  Root cause: v0.0593 moved all 768px breakpoints to 980px, which caused
  flex-direction:column to apply too early. Split the block: 2-column grid
  stays at max-width:980px, flex-direction:column restored to max-width:768px.
- 0.0597: Fixed second hamburger — inspector revealed class "metra-mobile-toggle" (theme button, not Divi). Added to TOGGLE_SELECTORS removal list.
- 0.0596: Fixed second hamburger still appearing at <=768px. Root cause: Divi injects
  its .mobile_menu_bar element via its own JavaScript AFTER DOMContentLoaded, so the
  previous one-shot removeThemeToggles() ran too early and missed it. Fix adds three
  layers: (1) immediate call unchanged, (2) setTimeout repeats at 100/300/600ms to
  catch late Divi injection, (3) MutationObserver watches document.body permanently
  so any future DOM insertion of theme toggles is removed instantly. Guard now also
  checks mc-mobile-nav-universal container class in addition to mc-mobile-nav-btn.
- 0.0595: Fixed second hamburger icon showing beneath METRA button on iPhone. Extended JS removeThemeToggles() to also remove .et_mobile_nav_menu, .et_pb_menu__icon, .menu-toggle and other common theme hamburger elements from the DOM. Added guard to never remove our own mc-mobile-nav-btn.
- 0.0594: Fixed desktop nav still showing alongside METRA hamburger at 980px.
  Only Divi mobile menu elements were suppressed but the Divi desktop main nav
  (.et_pb_menu__menu, #et-top-navigation, ul.et-menu) continued to show.
  Added desktop nav suppression at max-width:980px for Divi and common theme
  selectors (.main-navigation ul, .primary-menu-container, #primary-menu etc).
- 0.0593: Consolidated all mobile breakpoints to 980px.
  Previously 8 media query blocks in mc-theme.css used max-width:768px (layout, typography, search, overflow, header) while the METRA navigation and sidebar used max-width:980px. This created a grey zone between 769px and 980px where the METRA hamburger was already visible but many elements were not yet mobile-optimised.
  All 768px breakpoints replaced with 980px. Small-screen refinements (420px, 520px, 360px) left unchanged as they are not nav-related.
- 0.0592: Added one-time migration for mobile_offcanvas setting. Existing installs had 0 stored in DB from the old default. On first load after update, the value is migrated to 1 so the checkbox shows correctly and the panel activates automatically.
- 0.0591: Changed mobile offcanvas nav default to enabled. Previously the option defaulted to off, so the METRA panel was never shown on new installs without manual activation. Now enabled by default — user can disable in Design settings to fall back to theme navigation.
- 0.0590: Final Divi menu fix. CSS display:none cannot win because Divi menu is in normal document flow and Divi CSS has higher specificity. Solution: remove theme menu elements from the DOM entirely via JavaScript on DOMContentLoaded. This is bulletproof — a removed element cannot be shown by any CSS or JS.
- 0.0589: Fixed Divi .is-open override. Inspector showed Divi mobile.min.css uses #metra-mobile-menu.is-open { display:block } which is more specific than our #metra-mobile-menu { display:none !important }. Added all .is-open variants explicitly to the suppression block.
- 0.0588: Further mobile nav fixes:
  (1) Added e.stopPropagation() to our button click handler to prevent Divi JS from catching the event.
  (2) Added capture-phase document click listener that force-hides Divi menu when our panel is open.
  (3) Extended Divi CSS suppression to include Builder module selectors (.et_pb_menu .et_mobile_menu, .et_pb_menu__menu-toggle) and Divi header style variants.
- 0.0587: Root fix for mobile nav not rendering on desktop browsers with narrow viewport.
  wp_is_mobile() checks the HTTP User-Agent string, not the viewport width. Desktop Chrome/Safari with narrow window always returned false, so our panel was never rendered.
  Removed wp_is_mobile() from both enqueue and render. Panel now always renders.
  CSS breakpoint (max-width:980px) controls visibility: our button shows only on mobile, theme nav suppression only applies at mobile width, desktop nav is untouched.
- 0.0586: Fixed Divi mobile menu still showing despite display:none. Divi opens its menu via JavaScript with inline style display:block which overrides CSS without !important. Added !important specifically to Divi selectors (#metra-mobile-menu, .et_mobile_nav_menu etc). Other themes keep non-!important suppression so users can override if needed.
- 0.0585: Mobile navigation overhaul:
  (1) Theme-owned hamburger buttons and mobile menus are now hidden when METRA offcanvas nav is active (covers Divi, GeneratePress, Astra, Neve, Kadence, OceanWP, Storefront). No !important used — theme CSS can override if desired.
  (2) Divi nav panel (#metra-mobile-menu) explicitly hidden so only METRA panel is shown.
  (3) METRA panel menu items hardened with inline styles on ul, li and a so no theme selector can override centering or font size.
- 0.0584: Fixed mobile nav centering — Divi theme uses ID selector (#metra-mobile-menu) which overrides all class selectors. Applied text-align:center and font styles directly as inline styles on the li and a elements so no external CSS can override them.
- 0.0583: Fixed wrong version number in Settings page. MC_VERSION was hardcoded to 0.0540 and never updated. Replaced with dynamic read from plugin header via get_file_data() so it always matches the current version automatically.
- 0.0582: Fixed mobile nav items not centering. Increased CSS specificity on nav link selector (.mc-mobile-nav-panel .mc-mobile-nav-menu a) and added !important on text-align:center to prevent Divi theme styles from overriding alignment.
- 0.0581: Mobile navigation improvements:
  (1) Nav menu items are now centered (text-align:center).
  (2) Font size increased from browser-default to 18px, weight 500.
  (3) Removed dead file mc-mobile-nav.js — it searched for .mc-mobile-nav-toggle which no longer exists in the HTML and was never enqueued. The active file is mc-universal-mobile-nav.js.
- 0.0580: Fixed search button clipped on mobile single-listing page. The search form in single-mc_listing.php used the old HTML structure without .mc-home-search-inputwrap, so the input occupied the full flex width and pushed the button out. Added the inputwrap div around the input to match the shortcode search bar structure.
- 0.0579: Fixed two mobile single-listing issues:
  (1) Search button clipped on right edge: removed max-width:42% and max-width:38% from button selectors in mobile media queries. Button uses flex:0 0 auto and must never be width-limited.
  (2) Title words broken with wrong hyphens (e.g. post-fach): removed hyphens:auto from mc-single-title and mc-listing-title. Titles now use overflow-wrap:break-word and hyphens:none only.
- 0.0578: CSS cleanup — removed conflicting duplicate blocks accumulated over previous versions:
  (1) mc-categories-list-grid: removed column-count blocks that conflicted with CSS-Grid layout.
  (2) mc-categories-list-back: removed first duplicate block (v0.0456), kept the complete later block.
  (3) mc-edit-form: consolidated standalone margin/max-width block into the group selector, removed redundant duplicate.
- 0.0577: Consolidated all search bar CSS into one authoritative block. Removed 4 conflicting scattered blocks that assigned flex:1 directly to the input element instead of the inputwrap container, causing the x-clear-button to escape at mobile breakpoints. All media queries now correctly give flex:1 to .mc-home-search-inputwrap and flex:none/width:100% to the input.
- 0.0576: Root-fix for ×-clear-button escaping the search field on mobile. The @media(max-width:768px) block was assigning flex:1 directly to the input element, bypassing the inputwrap container. Fixed by assigning flex:1 to .mc-home-search-inputwrap and setting the input to width:100%/flex:none so the absolute-positioned clear button always stays inside its parent.
- 0.0575: Fixed ×-clear-button breaking out of the search input at ~770px breakpoint. Enforced position:relative on inputwrap and position:absolute on the clear button with !important to prevent theme CSS from overriding flex/positioning at any screen size.
- 0.0574: Fixed inconsistent ×-clear-button in search input. Suppressed native browser button via CSS so layout is identical on desktop and mobile.
- 0.0574: Fixed fatal load-order error: lucide-registry.php and lucide-render.php were loaded at the end of metra-classifieds.php (line ~1349), but class-mc-icons.php already called metra_lucide_render() during plugin init. Moved both require_once calls to before class-mc-plugin.php is loaded.
- 0.0573: Removed clear (X) button from mobile search. Desktop remains unchanged.
- 0.0571: Removed horizontal divider line between icon panels.
- 0.0570: Fixed spacing correctly (25px only on first Icons panel, removed incorrect global margin).
- 0.0568: Added 25px spacing between tabs and first panel in Icons tab.
- 0.0567: Icons tab upload/default sections made more compact and grouped into separate white panels with subtle borders and inner padding.
- 0.0566: Fixed the Settings header version display to read dynamically from the plugin header instead of showing the old hardcoded 0.0540 value.
- 0.0565: Mail footer text now converts entered line breaks to <br> in outgoing e-mails, so CR/new lines from the admin textarea are respected in the sent mail.
- 0.0564: Mail footer text is now unslashed before saving, preventing repeated backslashes from accumulating on each save while still preserving the entered HTML exactly.
- 0.0563: Added contact-form submit lock with immediate sending notice, disabled repeated clicks during seller mail sending, and preserved the existing success message after the mail is actually sent.
- 0.0560: Mail footer text now allows HTML. Removed plain-text-only sanitizing/rendering and switched footer output to sanitized HTML.
- 0.0558: Changed 'Stored Icons' to 'Stored custom Icons' and replaced the card layout with a compact admin list using alternating white/light-grey rows and 21px icon previews.
- 0.0557: Added a safe clear-X button inside the home search field. The button appears only when text is present and clears the field without breaking PHP syntax.
- 0.0556: Added mobile category icons with a soft background box to the categories grid rows. No empty space remains when category icons are disabled, and desktop layout stays unchanged.
- 0.0555: Reduced category icon picker width/height footprint with smaller icon cards, scrollable picker area, and collapsible icon groups in the category admin picker.
- 0.0554: When category icons are disabled, empty icon wrapper spans are no longer rendered in the category lists, removing the unwanted left indent.
- 0.0553: Removed the Design-tab registrations for category icon size, category icon color and icon reset actions. No other settings fields were changed.
- 0.0552: Changed 'Name of Icon' to 'Name of new Icon' and switched Edit Category icon color control to the same native color wheel field style used in the settings area.
- 0.0551: Replaced Icons-tab color control with the same native color field approach used in the Design tab. Current global icon color is shown immediately and opens the color wheel on click.
- 0.0550: Replaced the two color inputs with one WordPress color picker using preset palettes and typed HEX/RGB input in the picker field.
- 0.0549: Split icon reset into separate size and color actions with separate Apply buttons. Added Iris color picker before the hex field in the Icons tab.
- 0.0548: Default icon size changed to 20 px. Added WP Color Picker field 'Color' to the Icons tab global reset form and reset of per-category color overrides.
- 0.0547: Added global Icons-tab size reset with correct settings nonce handling and default icon size 16 px.
- 0.0546: Added Icons settings tab with SVG upload, name + group assignment, and grouped custom-icon integration into the category icon picker.
0.0543
- Replaced main category icon picker/rendering with local Lucide SVG icons.
- Existing term meta keys and design settings kept for compatibility.
- Removed Font Awesome enqueue hooks for this category icon system.

Version 0.0540
- In the METRA listings admin list, clicking the listing title now opens the frontend single listing view instead of the classic editor.

Version 0.0539
- Removed the password field and password hint from the registration form.
- Registration now creates users with a temporary generated password as preparation for the new activation -> set-password flow.

Version 0.0538
- Increased the top spacing below the logo for the "User set password e-mail" intro text to match the contact mail layout.

Version 0.0537
- Increased the top spacing above the Message Text intro block in the contact mail to seller e-mail so it sits with more distance below the logo.

Version 0.0536
- Dashboard title is now personalized for logged-in users using the account display name.
- English title format: "%s's Dashboard" and prepared for translation to patterns like "Dashboard von %s".

Version 0.0535
- Admin listings date column now shows 'edit: no' when creation and modification timestamps are identical.

Version 0.0533
- Pending-to-publish approvals now send the existing 'New listing e-mail to author' when a listing changes from pending to publish.
- Added duplicate protection for the author notification on approval using a dedicated post meta flag.
- Existing mail path remains unchanged; only the trigger was added.

Version 0.0531
- Based on 0.0529. Rebuilt the listings admin table so the author column appears first, the new compact 'Mod.' column follows, 'Author listings' is renamed to 'Listings', and the Condition column is removed.
- Added a compact Approve action for pending listings in the new moderation column.

Version 0.0529
- Based on 0.0527.
- Fixed fatal error in Message Texts settings by making the default intro text provider methods callable from MC_Settings.
- Existing mail paths, URL structure (ID + slug), and current features remain unchanged.

Version 0.0527
- Renamed the admin settings tab from "Messages" to "Message Texts".
- Updated related admin labels and notes to reflect the new tab name.

Version 0.0523
- Single listing permalinks now use the configurable base slug plus listing ID and post slug, e.g. /anzeige/123/example-listing/.
- Prevents duplicate-title permalink suffixes like -2 and -3 by resolving listings primarily through the ID.
- Existing rewrite refresh handling remains in place and the plugin author attribution stays J. Meier, Cologne.

Version 0.0522
- Added a configurable listing URL slug field to the Basic tab.
- The admin can now define the single-listing URL base (default: listing).
- Changing the listing slug now triggers an automatic rewrite refresh on the next init cycle.

Version 0.0521
- Added new optional Messages field: New listing e-mail to author, including a test button in the Messages tab.
- Added intro/prefix integration for the author copy of the new-listing notification e-mail.
- Added English default text for the new-listing author intro and prepared German translation entries in the language files.
- Updated plugin author attribution to: J. Meier, Cologne.

Version 0.0520
- Message test mails now resolve the recipient exactly like the existing Email tab mail test: first mc_email[test_to], then WordPress admin email as fallback.
- Message test mails continue to use the existing METRA test mail sending path in MC_Emails::send_test_mail().

Version 0.0520
- Rebuilt from 0.0516 for message test mails.
- Message test buttons now use the proven Mail tab test-mail path by routing through MC_Emails::send_test_mail() with message-specific subject/body overrides.
- Test buttons are rendered as a simple "Send Test-Mail to Admin" note with a "Test" button above each editable message field.
- Removed the extra test-button column from the e-mail inventory table to keep the admin UI compact.

Version 0.0516
- Added the mandatory "User set password e-mail" intro field to the central Messages block.
- The set-password e-mail now shows the configurable intro text below the logo and above the existing mail content.
- Added the English default text for the set-password intro and prepared the German translation in the language files.

Version 0.0514
- Based on 0.0512.
- Added mandatory message field: User activation e-mail.
- Introduced robust message formatting syntax: [bold]...[/bold] and [big]...[/big].
- Real blank lines in message textareas are now preserved.
- Added activation-mail intro text above the existing activation-mail content without changing the overall mail template.

Version 0.0512
- Built a compact, centralized administrative inventory block for all currently identified METRA e-mail message types in the Messages tab.
- Kept the existing Contact mail to seller intro field as the first connected message field inside the same messages block.
- No existing mail sending logic was changed in this version.

Version 0.0509
- Fixed My Listings status toggle color specificity so the paused state now stays bright red, including link/visited/hover/focus states.
- Active and paused status links now use consistent link-state styling in the action row.

Version 0.0508
- In My Listings, the pause/activate toggle link is now inline after View, Edit, and Delete with a preceding " - " separator.
- Removed the separate status line in My Listings.
- Paused toggle text is now red and bold.

Version 0.0507
- In My Listings, the static status line is now replaced by a translatable toggle link.
- Active listings show "Active! - Click to pause".
- Paused listings show bold black "Paused! - Click to activate".
- Delete remains a separate action; the status text is no longer shown behind Delete.

Version 0.0505
- In My listings, the action text now uses 'Delete' instead of 'Delete permanently'.
- Added listing state output directly after Delete in My listings.
- The appended state is translatable; paused listings are emphasized in bold.

Version 0.0504
- Centered dashboard labels and listing captions in My Listings, Favorites, and Published Listings.
- CSS-only alignment update; no logic or modal behavior changed.

Version 0.0503
- Report action now uses a centered feedback modal after submit, matching the existing contact confirmation style more closely.
- Report inline notice in the single listing view is suppressed to avoid duplicate confirmations.
- All current single-listing related modals were tightened for mobile to a maximum width of 75vw.

Version 0.0502
- Pause/Activate in the single listing view now uses AJAX instead of form submit + redirect, preventing the page from jumping upward.
- The pause button is now rendered as a real button with live label switching between Pause and Activate.
- The pause notice updates live in the single listing view without reloading the page.
- Pause block note: mail/report sending remains untouched in this version.

Version 0.0501
- Pause notice is now shown only while the listing is paused; it no longer remains visible after re-activation.
- Added 15px top spacing above the pause notice in the single listing view.
- Pause block note: current pause/activate flow is still submit + redirect based; only the notice display/styling was adjusted in this version.

Version 0.0500
- Final Block 2 cleanup: notice rendering and modal stack rendering moved into MC_Listing_View.
- Removed remaining unused favorite action data from the single template context.
- No functional changes intended; favorite, report, contact, delete, and mail behavior remain unchanged.

Version 0.0498
- Continued Single template cleanup by moving action button rendering and modal scripts into MC_Listing_View.
- No functional changes intended; favorite, report, delete, and pause flows should behave as before.

Version 0.0498
- Continued Single template cleanup by moving notice preparation plus report/delete modal rendering into MC_Listing_View.
- No functional changes intended; Favorite, Report, and mail sending behavior remain untouched.

Version 0.0496
- Continued Single template cleanup by moving submeta and gallery preparation into MC_Listing_View.
- No functional changes intended; favorite behavior and report mail flow remain untouched.

Version 0.0495
- Continued Single template cleanup by moving mobile search context, footer meta context, and action context preparation into MC_Listing_View.
- No functional changes intended; mail/report sending logic remains untouched.

Version 0.0494
- Block 2 continued: more single listing view preparation moved from the template into MC_Listing_View.
- Extracted mobile search context, footer meta context, and action/status context for the single listing.
- No functional change intended; favorite, report, contact, edit, delete, and pause behavior remain unchanged.

Version 0.0493
- Block 2 started: extracted single listing context, breadcrumb preparation, and gallery ID collection into a dedicated listing view helper.
- Reduced logic directly inside public/templates/single-mc_listing.php without changing favorite, report, or mail behavior.

Version 0.0492
- Based on 0.0491.
- Fixed Favorite button behavior in the single listing view without touching report mail sending.
- Favorite button now uses the existing AJAX favorite toggle flow, avoids page reload/scroll jump, syncs with the heart toggle, and restores the original button colors when removing a favorite.

Version 0.0491
- Added central listing permission/context helper: includes/class-mc-listing-permissions.php
- Single listing template now reads owner/admin/login/button visibility from one permission context
- Reduced duplicated inline permission checks in public/templates/single-mc_listing.php
- No visual feature expansion; goal was safer code structure for action buttons and related modals

Version 0.0490

- Removed duplicate City line from single listing meta block
- Single footer meta remains profile-based: Location: City, ZIP - Posted by: Author
- No other layout or styling changes

Version 0.0490
- Lower single meta block aligned to agreed typography
- Footer line above Location / Posted by removed
- No other areas changed

Version 0.0490
- Single lower meta block documented
- Price increased strongly
- Heart icon increased strongly
- No other areas changed

<?php
declare(strict_types=1);

namespace MC;

if (!defined('ABSPATH')) {
    exit;
}

final class Master_CSS {

    /**
     * Absolute path to the update-safe master stylesheet outside the plugin folder.
     * This file survives plugin updates and remains the final design layer.
     */
    public const TARGET_PATH = WP_PLUGIN_DIR . '/metra-master.css';

    /**
     * Default template shipped with the plugin.
     * It is copied only when the external master file does not yet exist.
     */
    public static function template_path(): string {
        return plugin_dir_path(__FILE__) . '../assets/master-template/metra-master.template.css';
    }

    private static function managed_block_marker_start(): string {
        return "/* METRA MANAGED BLOCK: listing-card-step-2 START */";
    }

    private static function managed_block_marker_end(): string {
        return "/* METRA MANAGED BLOCK: listing-card-step-2 END */";
    }

    private static function managed_block_content(): string {
        return self::managed_block_marker_start() . "
"
            . "/* Step 2: listing card visual hierarchy */
"
            . ".mc-listing-title,
.metra-listing-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 4px;
    line-height: 1.25;
}

"
            . ".mc-listing-price-row,
.metra-listing-price-row,
.mc-price-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 6px;
    margin-bottom: 6px;
}

"
            . ".mc-listing-excerpt,
.metra-listing-excerpt {
    font-size: 15px;
    color: var(--mc-text, #444444);
    margin-top: 6px;
    line-height: 1.4;
}
"
            . self::managed_block_marker_end() . "
";
    }





    private static function fa_main_categories_block_marker_start(): string {
        return "/* METRA MANAGED BLOCK: fa-main-categories START */";
    }

    private static function fa_main_categories_block_marker_end(): string {
        return "/* METRA MANAGED BLOCK: fa-main-categories END */";
    }

    private static function fa_main_categories_block_content(): string {
        return self::fa_main_categories_block_marker_start() . "
"
            . "/* Font Awesome main-category icons
 * Scope:
 * - top-level categories only
 * - manual override via category admin
 * - automatic fallback by category slug/name
 */
"
            . ".mc-home-cat-parent{
    display:flex;
    align-items:flex-start;
    gap: var(--mc-maincat-icon-gap, 8px);
}

.mc-home-cat-parent__icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width: var(--mc-maincat-icon-box, 18px);
    line-height: 1;
    color: var(--mc-maincat-icon-color, var(--mc-primary));
}

.mc-home-cat-parent__label{
    min-width:0;
}

.mc-maincat-fa-icon,
.mc-mobile-topcat-fa-icon{
    line-height:1 !important;
}
"
            . self::fa_main_categories_block_marker_end() . "
";
    }

    private static function home_categories_block_marker_start(): string {
        return "/* METRA MANAGED BLOCK: home-categories START */";
    }

    private static function home_categories_block_marker_end(): string {
        return "/* METRA MANAGED BLOCK: home-categories END */";
    }

    private static function home_categories_block_content(): string {
        return self::home_categories_block_marker_start() . "
"
            . "/* Homepage / category-sidebar typography tokens
 * Purpose:
 * - centralize font size, color and line-height in metra-master.css
 * - remove dependence on inherited/body line-height
 * Default line-height requested: 1.2
 */
"
            . ".mc-home-sidebar{
    --mc-home-cat-title-size: 16px;
    --mc-home-cat-title-weight: 600;
    --mc-home-cat-title-line-height: 1.2;
    --mc-home-cat-title-color: var(--mc-heading, var(--mc-primary));

    --mc-home-cat-parent-size: 16px;
    --mc-home-cat-parent-weight: 700;
    --mc-home-cat-parent-line-height: 1.2;
    /* v0.0713: kein lokaler Token mehr — greift direkt auf :root --mc-home-cat-parent-color aus Design-Settings */

    --mc-home-cat-child-size: 14px;
    --mc-home-cat-child-weight: 400;
    --mc-home-cat-child-line-height: 1.2;
    --mc-home-cat-child-color: var(--mc-text, inherit);

    --mc-home-cat-more-size: 14px;
    --mc-home-cat-more-weight: 400;
    --mc-home-cat-more-line-height: 1.2;
    --mc-home-cat-more-color: var(--mc-text, inherit);
}

.mc-home-title{
    font-size: var(--mc-home-cat-title-size);
    font-weight: var(--mc-home-cat-title-weight);
    line-height: var(--mc-home-cat-title-line-height);
    color: var(--mc-home-cat-title-color);
}

.mc-home-cat-parent{
    display: block;
    font-size: var(--mc-home-cat-parent-size);
    font-weight: var(--mc-home-cat-parent-weight);
    line-height: var(--mc-home-cat-parent-line-height);
    color: var(--mc-home-cat-parent-color);
}

.mc-home-cat-children{
    line-height: 1;
}

.mc-home-cat-child{
    display: block;
    font-size: var(--mc-home-cat-child-size);
    font-weight: var(--mc-home-cat-child-weight);
    line-height: var(--mc-home-cat-child-line-height);
    color: var(--mc-home-cat-child-color);
}

.mc-home-cat-more{
    display: block;
    font-size: var(--mc-home-cat-more-size);
    font-weight: var(--mc-home-cat-more-weight);
    line-height: var(--mc-home-cat-more-line-height);
    color: var(--mc-home-cat-more-color);
}
"
            . self::home_categories_block_marker_end() . "
";
    }

    private static function dashboard_block_marker_start(): string {
        return "/* METRA MANAGED BLOCK: dashboard-cards START */";
    }

    private static function dashboard_block_marker_end(): string {
        return "/* METRA MANAGED BLOCK: dashboard-cards END */";
    }

    private static function dashboard_block_content(): string {
        return self::dashboard_block_marker_start() . "
"
            . "/* Dashboard cards + full-width cleanup */
"
            . "body.page-slug-mc-dashboard .entry-title,
body.page-slug-mc-dashboard h1.entry-title,
body.page-slug-mc-dashboard .page-header,
body.page-slug-mc-dashboard .inside-page-header,
body.page-slug-mc-dashboard #secondary,
body.page-slug-mc-dashboard .widget-area,
body.page-slug-mc-dashboard .right-sidebar,
body.page-slug-mc-dashboard .sidebar {
    display: none !important;
}

body.page-slug-mc-dashboard #primary,
body.page-slug-mc-dashboard .content-area,
body.page-slug-mc-dashboard .site-main,
body.page-slug-mc-dashboard .inside-article {
    width: 100% !important;
    max-width: 100% !important;
}

body.page-slug-mc-dashboard .content-area {
    margin-right: 0 !important;
}

.mc-dashboard {
    max-width: 1200px;
    margin: 0 auto;
}

.mc-dashboard-shell {
    display: grid;
    gap: 22px;
}

.mc-dashboard-header {
    display: grid;
    gap: 8px;
}

.mc-dashboard-title {
    margin: 0;
    font-family: var(--mc-font);
    font-size: 44px;
    line-height: 1.12;
    font-weight: 400;
    color: var(--mc-heading, inherit);
}

.mc-dashboard-intro {
    margin: 0;
    font-family: var(--mc-font);
    font-size: 17px;
    line-height: 1.45;
    color: var(--mc-text, inherit);
    opacity: .86;
}

.mc-dashboard-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.mc-dashboard-stat {
    border: 1px solid rgba(0,0,0,.08);
    background: #fff;
    border-radius: 16px;
    padding: 18px 20px;
    display: grid;
    gap: 4px;
    box-shadow: 0 4px 18px rgba(0,0,0,.04);
}

.mc-dashboard-stat__value {
    font-family: var(--mc-font);
    font-size: 28px;
    line-height: 1.1;
    font-weight: 700;
    color: var(--mc-heading, inherit);
}

.mc-dashboard-stat__label {
    font-family: var(--mc-font);
    font-size: 14px;
    line-height: 1.35;
    color: var(--mc-text, inherit);
    opacity: .78;
}

.mc-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
}

.mc-dashboard-card {
    display: grid;
    grid-template-columns: 56px 1fr;
    gap: 16px;
    align-items: start;
    text-decoration: none;
    color: inherit;
    border: 1px solid rgba(0,0,0,.08);
    background: #fff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 8px 24px rgba(0,0,0,.05);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.mc-dashboard-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(0,0,0,.08);
    border-color: rgba(0,0,0,.12);
    text-decoration: none;
}

.mc-dashboard-card__icon {
    width: 56px;
    height: 56px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    background: rgba(0,0,0,.04);
}

.mc-dashboard-card__icon-svg {
    width: 24px;
    height: 24px;
}

.mc-dashboard-card__body {
    display: grid;
    gap: 6px;
}

.mc-dashboard-card__title {
    font-family: var(--mc-font);
    font-size: 21px;
    line-height: 1.2;
    font-weight: 700;
    color: var(--mc-heading, inherit);
}

.mc-dashboard-card__text {
    font-family: var(--mc-font);
    font-size: 15px;
    line-height: 1.45;
    color: var(--mc-text, inherit);
    opacity: .84;
}

.mc-dashboard-login {
    border: 1px solid rgba(0,0,0,.08);
    background: #fff;
    border-radius: 18px;
    padding: 24px;
    box-shadow: 0 8px 24px rgba(0,0,0,.05);
}

.mc-dashboard-login-text {
    margin: 0 0 14px 0;
}

@media (max-width: 980px) {
    .mc-dashboard-stats,
    .mc-dashboard-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .mc-dashboard-title {
        font-size: 34px;
    }

    .mc-dashboard-stats,
    .mc-dashboard-grid {
        grid-template-columns: 1fr;
    }

    .mc-dashboard-card {
        grid-template-columns: 48px 1fr;
        padding: 18px;
    }

    .mc-dashboard-card__icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
    }

    .mc-dashboard-card__title {
        font-size: 19px;
    }
}
"
            . self::dashboard_block_marker_end() . "
";
    }

    private static function guest_action_buttons_block_marker_start(): string {
        return "/* METRA MANAGED BLOCK: guest-action-buttons START */";
    }

    private static function guest_action_buttons_block_marker_end(): string {
        return "/* METRA MANAGED BLOCK: guest-action-buttons END */";
    }

    private static function guest_action_buttons_block_content(): string {
        return self::guest_action_buttons_block_marker_start() . "
"
            . "/* Guest action buttons in single listing view */
"
            . ".mc-single-actions-wrapper {
    text-align: right;
}

.mc-single-actions.mc-single-actions--guest {
    justify-content: flex-end;
    flex-wrap: nowrap;
    gap: 12px;
}

.mc-guest-action-button,
.mc-single-actions--guest .button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0 22px;
    font-size: 16px;
    font-weight: 600;
    line-height: 1.2;
    text-decoration: none;
    white-space: nowrap;
}

@media (max-width: 980px) {
    .mc-single-actions.mc-single-actions--guest {
        flex-wrap: wrap;
    }
}
"
            . self::guest_action_buttons_block_marker_end() . "
";
    }

    private static function replace_or_append_block(string $content, string $start, string $end, string $block): string {
        if (strpos($content, $start) !== false) {
            // Block vorhanden — immer mit aktuellem Inhalt ersetzen
            $content = preg_replace(
                '/' . preg_quote($start, '/') . '.*?' . preg_quote($end, '/') . '/s',
                $block,
                $content
            );
        } else {
            // Block fehlt — anhängen
            $content = rtrim($content) . "\n\n" . $block;
        }
        return $content;
    }

    private static function ensure_managed_block(): void {
        $target = self::TARGET_PATH;
        if (!file_exists($target)) {
            return;
        }
        $content = @file_get_contents($target);
        if ($content === false) {
            return;
        }

        $content = self::replace_or_append_block($content, self::managed_block_marker_start(), self::managed_block_marker_end(), self::managed_block_content());
        $content = self::replace_or_append_block($content, self::guest_action_buttons_block_marker_start(), self::guest_action_buttons_block_marker_end(), self::guest_action_buttons_block_content());
        $content = self::replace_or_append_block($content, self::dashboard_block_marker_start(), self::dashboard_block_marker_end(), self::dashboard_block_content());
        $content = self::replace_or_append_block($content, self::home_categories_block_marker_start(), self::home_categories_block_marker_end(), self::home_categories_block_content());
        $content = self::replace_or_append_block($content, self::fa_main_categories_block_marker_start(), self::fa_main_categories_block_marker_end(), self::fa_main_categories_block_content());

        @file_put_contents($target, $content);
    }


    /**
     * Ensure metra-master.css exists. Existing user-edited files are never overwritten.
     */
    public static function ensure_exists(): void {
        $target = self::TARGET_PATH;

        if (file_exists($target)) {
            self::ensure_managed_block();
            return;
        }

        $template = self::template_path();
        if (!file_exists($template)) {
            @file_put_contents($target, "/* METRA Master Stylesheet (minimal fallback) */
");
            return;
        }

        $content = @file_get_contents($template);
        if ($content === false || trim($content) === '') {
            @file_put_contents($target, "/* METRA Master Stylesheet (minimal fallback) */
");
            return;
        }

        @file_put_contents($target, rtrim($content) . "
");
        self::ensure_managed_block();
    }

    /**
     * Frontend: enqueue metra-master.css as the final stylesheet (last wins).
     */
    public static function enqueue_last_wins(): void {
        $path = self::TARGET_PATH;
        if (!file_exists($path)) {
            return;
        }

        $url = content_url('plugins/metra-master.css');

        wp_enqueue_style(
            'mc-master-css',
            $url,
            array(),
            (string) @filemtime($path)
        );
    }
}

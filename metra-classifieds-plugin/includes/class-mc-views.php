<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Listing view counter.
 *
 * Responsibilities:
 * - Count single listing views on the canonical single page.
 * - Avoid repeated increments from the same browser for a limited period.
 * - Render the counter for the single template when enabled in Basic settings.
 */
final class MC_Views {

    private const META_KEY = 'mc_view_count';
    private const COOKIE_PREFIX = 'mc_viewed_';
    private const COOKIE_TTL = DAY_IN_SECONDS;

    public static function init(): void {
        add_action('wp', [self::class, 'maybe_count_view']);
    }

    public static function is_enabled(): bool {
        return (int) get_option('mc_single_view_counter_enabled', 1) === 1;
    }

    public static function maybe_count_view(): void {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_preview()) {
            return;
        }

        if (!is_singular('mc_listing')) {
            return;
        }

        $post_id = (int) get_queried_object_id();
        if ($post_id <= 0) {
            return;
        }

        if (isset($_GET['mc_edit']) && (string) $_GET['mc_edit'] === '1') {
            return;
        }

        $cookie_name = self::COOKIE_PREFIX . $post_id;
        if (!empty($_COOKIE[$cookie_name])) {
            return;
        }

        $current = (int) get_post_meta($post_id, self::META_KEY, true);
        update_post_meta($post_id, self::META_KEY, $current + 1);

        $secure = is_ssl();
        $httponly = true;
        $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
        setcookie($cookie_name, '1', time() + self::COOKIE_TTL, $path, '', $secure, $httponly);
        $_COOKIE[$cookie_name] = '1';
    }

    public static function get_count(int $post_id): int {
        return max(0, (int) get_post_meta($post_id, self::META_KEY, true));
    }

    public static function render(int $post_id): string {
        if (!self::is_enabled()) {
            return '';
        }

        $count = self::get_count($post_id);

        return '<div class="mc-listing-views">'
            . '<strong>' . esc_html__('Views:', 'metra-classifieds-plugin') . '</strong> '
            . esc_html(number_format_i18n($count))
            . '</div>';
    }
}

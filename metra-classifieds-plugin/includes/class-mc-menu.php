<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Conditional visibility for common header/menu links.
 *
 * - Register + Login are shown only when NOT logged in.
 * - My dashboard + Logout are shown only when logged in.
 *
 * Supports:
 * 1) WordPress Menus: hides items by matching their URL to METRA pages.
 * 2) Extra menu CSS classes:
 *    - mc-show-logged-in  -> show only when logged in
 *    - mc-show-logged-out -> show only when logged out
 *    - mc-logout          -> rewires URL to wp_logout_url(...), show only when logged in
 */
final class MC_Menu {

    public static function init(): void {
        add_filter('wp_nav_menu_objects', [self::class, 'filter_nav_menu_objects'], 20, 2);
        // Inject auth links into theme navigation menus so a dedicated METRA login page
        // is reachable without manually editing menus.
        add_filter('wp_nav_menu_items', [self::class, 'inject_auth_menu_items'], 30, 2);
    }

    /**
     * Append Login/Register or Dashboard/Logout links to the menu HTML.
     *
     * We only append links if the corresponding URLs are not already present.
     * This keeps the behaviour safe even when the site owner manually adds
     * their own menu items.
     */
    public static function inject_auth_menu_items(string $items, $args): string {
        if (is_admin()) {
            return $items;
        }

        $login_url    = MC_Pages::get_page_url('login') ?: home_url('/mc-login/');
        $register_url = MC_Pages::get_page_url('register') ?: home_url('/mc-register/');
        $dash_url     = MC_Pages::get_page_url('dashboard') ?: home_url('/mc-dashboard/');
        $logout_url   = wp_logout_url(home_url('/'));

        $logged_in = is_user_logged_in();

        // Avoid duplicates if the menu already contains these links.
        $has_login    = (strpos($items, esc_url($login_url)) !== false) || (strpos($items, '/mc-login') !== false);
        $has_register = (strpos($items, esc_url($register_url)) !== false) || (strpos($items, '/mc-register') !== false);
        $has_dash     = (strpos($items, esc_url($dash_url)) !== false) || (strpos($items, '/mc-dashboard') !== false);
        $has_logout   = (strpos($items, 'logout') !== false);

        $extra = '';

        if ($logged_in) {
            if (!$has_dash) {
                $extra .= '<li class="menu-item mc-menu-dashboard"><a href="' . esc_url($dash_url) . '">' . esc_html__('My dashboard', 'metra-classifieds-plugin') . '</a></li>';
            }
            if (!$has_logout) {
                $extra .= '<li class="menu-item mc-menu-logout"><a href="' . esc_url($logout_url) . '">' . esc_html__('Logout', 'metra-classifieds-plugin') . '</a></li>';
            }
        } else {
            if (!$has_login) {
                $extra .= '<li class="menu-item mc-menu-login"><a href="' . esc_url($login_url) . '">' . esc_html__('Login', 'metra-classifieds-plugin') . '</a></li>';
            }
            if (!$has_register) {
                $extra .= '<li class="menu-item mc-menu-register"><a href="' . esc_url($register_url) . '">' . esc_html__('Register', 'metra-classifieds-plugin') . '</a></li>';
            }
        }

        if ($extra === '') {
            return $items;
        }

        return $items . $extra;
    }

    /**
     * Normalize URL to compare paths reliably.
     */
    private static function norm_url(string $url): string {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        if ($path === '') {
            // Some menu items may use relative URLs.
            $path = $url;
        }
        $path = rtrim($path, '/');
        if ($path === '') {
            $path = '/';
        }
        return $path;
    }

    /**
     * Hide/show menu items based on login status.
     *
     * NOTE: We keep changes minimal and only affect the items that match
     * METRA links or explicit CSS classes.
     *
     * @param array<int, \WP_Post> $sorted_menu_items
     * @return array<int, \WP_Post>
     */
    public static function filter_nav_menu_objects(array $sorted_menu_items, $args): array {
        $logged_in = is_user_logged_in();

        $login_url    = MC_Pages::get_page_url('login') ?: home_url('/mc-login/');
        $register_url = MC_Pages::get_page_url('register') ?: home_url('/mc-register/');
        $dash_url     = MC_Pages::get_page_url('dashboard') ?: home_url('/mc-dashboard/');

        $login_p    = self::norm_url($login_url);
        $register_p = self::norm_url($register_url);
        $dash_p     = self::norm_url($dash_url);

        $out = [];
        foreach ($sorted_menu_items as $item) {
            $classes = [];
            if (!empty($item->classes) && is_array($item->classes)) {
                $classes = array_map('strval', $item->classes);
            }

            // Explicit classes win.
            if (in_array('mc-show-logged-in', $classes, true) && !$logged_in) {
                continue;
            }
            if (in_array('mc-show-logged-out', $classes, true) && $logged_in) {
                continue;
            }

            // Logout placeholder: set URL and hide/show.
            if (in_array('mc-logout', $classes, true)) {
                if (!$logged_in) {
                    continue;
                }
                $item->url = wp_logout_url(home_url('/'));
                $out[] = $item;
                continue;
            }

            $item_path = self::norm_url((string) ($item->url ?? ''));

            // Auto-hide METRA pages depending on auth.
            if ($item_path !== '') {
                if ($item_path === $register_p || $item_path === $login_p) {
                    if ($logged_in) {
                        continue;
                    }
                }
                if ($item_path === $dash_p) {
                    if (!$logged_in) {
                        continue;
                    }
                }
            }

            $out[] = $item;
        }

        return $out;
    }
}

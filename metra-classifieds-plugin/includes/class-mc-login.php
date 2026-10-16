<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Login-related helpers.
 *
 * Goal:
 * - Provide a branded METRA login page (/mc-login/) instead of wp-login.php.
 * - Keep core features: login, lost password, reset password.
 * - Redirect wp-login.php requests to METRA pages (front-end only).
 */
final class MC_Login {

    public static function init(): void {
        // Redirect wp-login.php to METRA pages.
        add_action('login_init', [self::class, 'maybe_redirect_wp_login']);

        // Ensure lost-password emails use our reset page URL.
        add_filter('retrieve_password_message', [self::class, 'filter_retrieve_password_message'], 10, 4);
        add_filter('retrieve_password_title', [self::class, 'filter_retrieve_password_title'], 10, 1);
    }

    /**
     * Redirect wp-login.php to METRA pages.
     *
     * We allow wp-login.php for:
     * - admins in wp-admin context
     * - logout action
     */
    public static function maybe_redirect_wp_login(): void {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        // Allow direct wp-login for users that can manage options (site operators).
        if (is_user_logged_in() && current_user_can('manage_options')) {
            return;
        }

        $action = isset($_REQUEST['action']) ? sanitize_key((string) $_REQUEST['action']) : 'login';

        // Let WP handle logout reliably.
        if ($action === 'logout') {
            return;
        }

        $login_url = MC_Pages::get_page_url('login');
        if ($login_url === '') {
            $login_url = home_url('/mc-login/');
        }

        // Password reset links from email:
        // wp-login.php?action=rp&key=...&login=...
        if ($action === 'rp' || $action === 'resetpass') {
            $reset_url = MC_Pages::get_page_url('reset_password');
            if ($reset_url === '') {
                $reset_url = home_url('/mc-reset-password/');
            }
            $key   = isset($_REQUEST['key']) ? sanitize_text_field((string) $_REQUEST['key']) : '';
            $login = isset($_REQUEST['login']) ? sanitize_user((string) $_REQUEST['login'], true) : '';
            $dest  = add_query_arg(['key' => rawurlencode($key), 'login' => rawurlencode($login)], $reset_url);
            wp_safe_redirect($dest);
            exit;
        }

        // Lost password form.
        if ($action === 'lostpassword') {
            $dest = add_query_arg('mc_action', 'lostpassword', $login_url);
            wp_safe_redirect($dest);
            exit;
        }

        // Default login.
        $redirect_to = isset($_REQUEST['redirect_to']) ? esc_url_raw((string) $_REQUEST['redirect_to']) : '';
        $dest = $login_url;
        if ($redirect_to !== '') {
            $dest = add_query_arg('redirect_to', rawurlencode($redirect_to), $login_url);
        }
        wp_safe_redirect($dest);
        exit;
    }

    /**
     * Replace the reset-password link in core lost-password emails
     * with our METRA reset page.
     */
    public static function filter_retrieve_password_message(string $message, string $key, string $user_login, $user_data): string {
        $reset_url = MC_Pages::get_page_url('reset_password');
        if ($reset_url === '') {
            $reset_url = home_url('/mc-reset-password/');
        }

        $link = add_query_arg(
            [
                'key' => rawurlencode($key),
                'login' => rawurlencode($user_login),
            ],
            $reset_url
        );

        // Keep core message style, just replace the URL portion.
        // Try to replace the first occurrence of wp-login.php?action=rp... link.
        $message = preg_replace('~https?://[^\s]+wp-login\.php\?action=rp[^\s]*~i', $link, $message, 1) ?: $message;
        return $message;
    }

    public static function filter_retrieve_password_title(string $title): string {
        // Brand the subject while keeping meaning.
        return '[METRA Classifieds] ' . $title;
    }
}

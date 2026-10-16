<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Shortcodes {

    /**
     * In-request status message used as a fallback when redirects are not possible
     * (e.g. headers already sent due to PHP notices in a dev environment).
     *
     * @var array{type:string,message:string}|null
     */
    private static ?array $reg_flash = null;

    /**
     * Best-effort return URL for POST handlers.
     *
     * Some browsers / privacy settings may omit the Referer header.
     * In that case we fall back to the current request URI so the user stays
     * on the same METRA page and can see the status message.
     */

    private static function current_return_url(): string {
        $ref = wp_get_referer();
        if (is_string($ref) && $ref !== '') {
            return $ref;
        }
        $req = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        if ($req === '') {
            return home_url('/');
        }
        // REQUEST_URI is a path+query; home_url() will prefix the correct site URL.
        return home_url($req);
    }

    /**
     * Sanitize public listing descriptions.
     *
     * Rules:
     * - no HTML
     * - no email addresses
     * - no website/URL strings
     * - simple bold markup stays allowed via **word**
     */
    // === MC: Title guard script helper v0.0708 START ===
    /**
     * Gibt das JS für den Titel-Live-Zähler und das METRA-Modal einmalig aus.
     * Wird von allen drei Titel-Inputs aufgerufen; das Script rendert sich
     * nur einmal pro Seite (Flag-Variable im JS-Scope).
     */
    private static function render_title_guard_script(): string {
        static $mc_title_guard_rendered = false;
        if ($mc_title_guard_rendered) {
            return '';
        }
        $mc_title_guard_rendered = true;
        return '<div id="mc-title-limit-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;">'
            . '<div style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>'
            . '<div role="dialog" aria-modal="true" style="position:relative;width:min(420px,88vw);margin:18vh auto 0 auto;background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.22);padding:24px 20px 20px;">'
            . '<div style="font-size:17px;font-weight:700;margin:0 0 10px 0;">&#9888; ' . esc_html__('Titel zu lang', 'metra-classifieds-plugin') . '</div>'
            . '<div id="mc-title-limit-msg" style="font-size:14px;line-height:1.5;color:#444;margin:0 0 18px 0;"></div>'
            . '<div style="text-align:right;">'
            . '<button type="button" id="mc-title-limit-close" style="background:#1a73e8;color:#fff;border:none;border-radius:8px;padding:9px 22px;font-size:14px;font-weight:600;cursor:pointer;">'
            . esc_html__('OK', 'metra-classifieds-plugin') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<script>'
            . '(function(){'
            . 'var _mcTitleGuardInit=false;'
            . 'window.mcTitleGuard=function(inputId,counterId,max){'
            . 'var inp=document.getElementById(inputId);'
            . 'var ctr=document.getElementById(counterId);'
            . 'var modal=document.getElementById("mc-title-limit-modal");'
            . 'var msg=document.getElementById("mc-title-limit-msg");'
            . 'var closeBtn=document.getElementById("mc-title-limit-close");'
            . 'if(!inp||!ctr||!modal)return;'
            . 'function update(){'
            . 'var len=inp.value.length;'
            . 'var rem=max-len;'
            . 'if(rem>=0){'
            . 'ctr.textContent=rem+" ' . esc_js(__('Zeichen verbleibend', 'metra-classifieds-plugin')) . '";'
            . 'ctr.style.color=rem<=5?"#c0392b":"#666";'
            . '}else{'
            . 'ctr.textContent="";'
            . 'msg.textContent="' . esc_js(__('Der Titel darf maximal ', 'metra-classifieds-plugin')) . '"+max+"' . esc_js(__(' Zeichen haben. Bitte kürze ihn.', 'metra-classifieds-plugin')) . '";'
            . 'modal.style.display="block";modal.setAttribute("aria-hidden","false");'
            . 'inp.value=inp.value.substring(0,max);'
            . 'update();'
            . '}'
            . '}'
            . 'inp.addEventListener("input",update);'
            . 'if(!_mcTitleGuardInit){'
            . '_mcTitleGuardInit=true;'
            . 'function closeModal(){modal.style.display="none";modal.setAttribute("aria-hidden","true");}'
            . 'closeBtn.addEventListener("click",closeModal);'
            . 'modal.addEventListener("click",function(e){if(e.target===modal.firstElementChild)closeModal();});'
            . 'document.addEventListener("keydown",function(e){if(e.key==="Escape"&&modal.style.display==="block")closeModal();});'
            . '}'
            . 'update();'
            . '};'
            . '})();'
            . '</script>';
    }
    // === MC: Title guard script helper v0.0708 END ===

    private static function sanitize_public_description(string $text, int $max_len = 20000): string {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = wp_strip_all_tags($text, false);

        // Remove email addresses.
        $text = (string) preg_replace('/\b[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}\b/i', '', $text);

        // Remove explicit URLs.
        $text = (string) preg_replace('/\b(?:https?:\/\/|ftp:\/\/|www\.)\S+/i', '', $text);

        // Remove plain website/domain patterns such as example.com/path.
        $text = (string) preg_replace('/\b(?:[A-Z0-9-]+\.)+[A-Z]{2,}(?:\/\S*)?\b/i', '', $text);

        // Clean spacing while preserving line breaks.
        $text = (string) preg_replace('/[^\S\n]+/u', ' ', $text);
        $text = (string) preg_replace('/\n{3,}/', "\n\n", $text);
        $text = trim($text);

        if (function_exists('mb_substr')) {
            $text = mb_substr($text, 0, $max_len);
        } else {
            $text = substr($text, 0, $max_len);
        }

        return $text;
    }

    /**
     * Render sanitized listing descriptions with very limited formatting.
     * Only **word** becomes <strong>word</strong>.
     */
    public static function render_public_description(string $text): string {
        $safe = esc_html(self::sanitize_public_description($text));
        $safe = (string) preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
        return wpautop($safe);
    }

        // === Registration (shortcodes + hooks) =================================
public static function register(): void {
        // MC_Shortcodes::register() is hooked on `init` (see MC_Plugin).
        // Any request-handlers registered again on `init` would miss the current request.
        // Therefore we hook them on `wp_loaded` (runs later in the same request).
        // Contact system is handled exclusively by modules/contact/.
        add_action('wp_loaded', [self::class, 'maybe_handle_register_post']);
        add_action('wp_loaded', [self::class, 'maybe_handle_set_password_post']);
        add_action('wp_loaded', [self::class, 'maybe_handle_login_post']);
        add_action('wp_loaded', [self::class, 'maybe_handle_lost_password_post']);
        add_action('wp_loaded', [self::class, 'maybe_handle_reset_password_post']);
        add_action('wp_loaded', [self::class, 'maybe_handle_delete_listing']);
        add_shortcode('mc_submit_link', [self::class, 'submit_link']);
        add_shortcode('mc_categories_grid', [self::class, 'categories_grid']);
        add_shortcode('mc_categories_list', [self::class, 'categories_list_page']);
        add_shortcode('mc_submit_category', [self::class, 'submit_category_wizard']);
        add_shortcode('mc_submit_form', [self::class, 'submit_form']);
        add_shortcode('mc_submit_pending', [self::class, 'submit_pending_page']);
        add_shortcode('mc_guest_action_required', [self::class, 'guest_action_required_page']);
        add_shortcode('mc_register', [self::class, 'register_page']);
        add_shortcode('mc_login', [self::class, 'login_page']);
        add_shortcode('mc_reset_password', [self::class, 'reset_password_page']);
        add_shortcode('mc_listings', [self::class, 'listings']);
        add_shortcode('mc_breadcrumb', [self::class, 'breadcrumb_shortcode']);
        add_shortcode('mc_my_listings', [self::class, 'my_listings']);

        // Convenience auth/navigation links for headers/builders.
        add_shortcode('mc_auth_nav', [self::class, 'auth_nav']);

        // Pages created automatically by the plugin.
        add_shortcode('mc_dashboard', [self::class, 'dashboard']);
        add_shortcode('mc_edit_listing', [self::class, 'edit_listing']);
        add_shortcode('mc_listing', [self::class, 'listing_view']);
        add_shortcode('mc_category_listings', [self::class, 'category_listings']);
        add_shortcode('mc_set_password', [self::class, 'set_password_page']);
        add_shortcode('mc_my_profile', [MC_Profile::class, 'shortcode']);
        add_shortcode('mc_favorites', [MC_Profile::class, 'favorites_shortcode']);
    }


    private const PAUSE_META_KEY = '_mc_paused';

    private static function is_listing_paused(int $post_id): bool {
        return (string) get_post_meta($post_id, self::PAUSE_META_KEY, true) === '1';
    }

    private static function get_listing_public_status_label(int $post_id): string {
        if (self::is_listing_paused($post_id)) {
            return __('Paused', 'metra-classifieds-plugin');
        }

        $status = (string) get_post_status($post_id);
        if ($status === 'publish') {
            return __('Active', 'metra-classifieds-plugin');
        }
        if ($status === 'pending') {
            return __('Pending', 'metra-classifieds-plugin');
        }
        if ($status === 'draft') {
            return __('Draft', 'metra-classifieds-plugin');
        }

        return $status;
    }

    /**
     * Shortcode: [mc_auth_nav]
     * Renders Login/Register (logged-out) or Dashboard/Logout (logged-in).
     */
    public static function auth_nav(array $atts = []): string {
        $atts = shortcode_atts([
            'class' => 'mc-auth-nav',
        ], $atts, 'mc_auth_nav');

        $class = esc_attr((string) $atts['class']);
        $login_url    = MC_Pages::get_page_url('login') ?: home_url('/mc-login/');
        $register_url = MC_Pages::get_page_url('register') ?: home_url('/mc-register/');
        $dash_url     = MC_Pages::get_page_url('dashboard') ?: home_url('/mc-dashboard/');

        if (is_user_logged_in()) {
            $logout = wp_logout_url($login_url);
            return '<span class="' . $class . '">'
                . '<a href="' . esc_url($dash_url) . '">' . esc_html__('My dashboard', 'metra-classifieds-plugin') . '</a>'
                . ' · '
                . '<a href="' . esc_url($logout) . '">' . esc_html__('Logout', 'metra-classifieds-plugin') . '</a>'
                . '</span>';
        }

        return '<span class="' . $class . '">'
            . '<a href="' . esc_url($login_url) . '">' . esc_html__('Login', 'metra-classifieds-plugin') . '</a>'
            . ' · '
            . '<a href="' . esc_url($register_url) . '">' . esc_html__('Register', 'metra-classifieds-plugin') . '</a>'
            . '</span>';
    }


    public static function submit_pending_page(array $atts = []): string {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(MC_Pages::get_page_url('submit_pending') ?: home_url('/'));
            return '<div class="mc-submit-pending mc-not-logged-in">'
                . esc_html__('Please log in to view this page.', 'metra-classifieds-plugin')
                . ' <a href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
        }

        $my_listings_url = MC_Pages::get_page_url('my_listings') ?: home_url('/mc-my-listings/');
        $edit_hint_url = MC_Pages::get_page_url('my_listings') ?: $my_listings_url;

        $out  = '<div class="mc-submit-pending" style="max-width:860px;margin:0 auto;padding:24px 0;">';
        $out .= '<div style="padding:24px;border:1px solid #e5e5e5;border-radius:16px;background:#fff;">';
        $out .= '<h1 style="margin:0 0 14px 0;">' . esc_html__('Listing submitted', 'metra-classifieds-plugin') . '</h1>';
        $out .= '<p style="margin:0 0 16px 0;">' . esc_html__('Your listing is now waiting for approval by the administrator.', 'metra-classifieds-plugin') . '</p>';
        $out .= '<p style="margin:0 0 16px 0;"><a class="button button-primary" href="' . esc_url($my_listings_url) . '">' . esc_html__('Go to My Listings', 'metra-classifieds-plugin') . '</a></p>';
        $out .= '<p style="margin:0 0 10px 0;">' . esc_html__('You can edit your listing later in My Listings.', 'metra-classifieds-plugin') . '</p>';
        $out .= '<p style="margin:0;">' . esc_html__('You will receive an e-mail notification after approval.', 'metra-classifieds-plugin') . '</p>';
        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }


    private static function get_guest_action_required_url(string $action = 'restricted', int $listing_id = 0): string {
        $base = MC_Pages::get_page_url('guest_action_required');
        if ($base === '') {
            $base = home_url('/mc-action-required/');
        }

        return (string) add_query_arg([
            'action' => sanitize_key($action),
            'listing_id' => max(0, $listing_id),
        ], $base);
    }

    public static function guest_action_required_page(array $atts = []): string {
        $action = isset($_GET['action']) ? sanitize_key((string) $_GET['action']) : 'restricted';
        $listing_id = isset($_GET['listing_id']) ? absint((string) $_GET['listing_id']) : 0;

        $login_url = MC_Pages::get_page_url('login') ?: home_url('/mc-login/');
        $register_url = MC_Pages::get_page_url('register') ?: home_url('/mc-register/');
        $listing_url = $listing_id > 0 ? get_permalink($listing_id) : '';
        if (!is_string($listing_url)) {
            $listing_url = '';
        }

        $headline = __('Login or register', 'metra-classifieds-plugin');
        $text = __('This action is only available after login or registration.', 'metra-classifieds-plugin');

        if ($action === 'favorite') {
            $headline = __('Save listings as favorites', 'metra-classifieds-plugin');
            $text = __('To save a listing as a favorite, please log in or create an account first.', 'metra-classifieds-plugin');
        } elseif ($action === 'report') {
            $headline = __('Report a listing', 'metra-classifieds-plugin');
            $text = __('To report a listing, please log in or create an account first.', 'metra-classifieds-plugin');
        }

        $out  = '<div class="mc-guest-action-required" style="max-width:900px;margin:0 auto;padding:24px 0;">';
        $out .= '<div style="padding:24px;border:1px solid #e5e5e5;border-radius:16px;background:#fff;">';
        $out .= '<h1 style="margin:0 0 14px 0;">' . esc_html($headline) . '</h1>';
        $out .= '<p style="margin:0 0 16px 0;">' . esc_html($text) . '</p>';
        $out .= '<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin:0 0 18px 0;">';
        $out .= '<a class="button button-primary" href="' . esc_url($login_url) . '">' . esc_html__('Login', 'metra-classifieds-plugin') . '</a>';
        $out .= '<a class="button" href="' . esc_url($register_url) . '">' . esc_html__('Register', 'metra-classifieds-plugin') . '</a>';
        $out .= '</div>';
        $out .= '<div style="margin:18px 0 0 0;padding:16px;border-radius:12px;background:#f7f7f7;">';
        $out .= '<strong style="display:block;margin-bottom:8px;">' . esc_html__('Why create an account?', 'metra-classifieds-plugin') . '</strong>';
        $out .= '<p style="margin:0 0 8px 0;">' . esc_html__('Registered users can save favorites, report listings, and use additional marketplace features.', 'metra-classifieds-plugin') . '</p>';
        $out .= '<p style="margin:0;">' . esc_html__('This page can also be used for additional notices, advertising, or promotional content.', 'metra-classifieds-plugin') . '</p>';
        $out .= '</div>';

        if ($listing_url !== '') {
            $out .= '<p style="margin:16px 0 0 0;"><a href="' . esc_url($listing_url) . '">' . esc_html__('Back to the listing', 'metra-classifieds-plugin') . '</a></p>';
        }

        $out .= '</div></div>';

        return $out;
    }

    /**
     * Handle POST from the METRA "Set password" page.
     *
     * This is intentionally separate from wp-login.php?action=rp.
     */
    public static function maybe_handle_set_password_post(): void {
        if (!isset($_POST['mc_setpw_submit'], $_POST['mc_setpw_nonce'])) {
            return;
        }

        $dest = self::current_return_url();
        if (!wp_verify_nonce((string) $_POST['mc_setpw_nonce'], 'mc_setpw')) {
            wp_safe_redirect(add_query_arg('mc_setpw', 'fail', $dest));
            exit;
        }

        $uid = isset($_POST['mc_uid']) ? (int) $_POST['mc_uid'] : 0;
        $key = isset($_POST['mc_key']) ? sanitize_text_field((string) $_POST['mc_key']) : '';

        $p1  = (string) ($_POST['mc_pw1'] ?? '');
        $p2  = (string) ($_POST['mc_pw2'] ?? '');

        if ($uid <= 0 || $key === '' || $p1 === '' || $p2 === '' || $p1 !== $p2) {
            wp_safe_redirect(add_query_arg('mc_setpw', 'fail', $dest));
            exit;
        }

        // Delegate validation + password set to the activation module.
        $ok = MC_User_Activation::handle_set_password($uid, $key, $p1);
        wp_safe_redirect(add_query_arg('mc_setpw', $ok ? 'success' : 'invalid', $dest));
        exit;
    }

    /**
     * Shortcode: [mc_set_password]
     *
     * Renders a minimal, branded password setup form.
     */
    public static function set_password_page(): string {
        $uid = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
        $key = isset($_GET['key']) ? sanitize_text_field((string) $_GET['key']) : '';

        $status = isset($_GET['mc_setpw']) ? sanitize_text_field((string) $_GET['mc_setpw']) : '';

        $out  = '<div class="mc-card" style="max-width:720px;margin:0 auto;">';
        $out .= '<h2 class="mc-title">' . esc_html__('Password festlegen', 'metra-classifieds-plugin') . '</h2>';

        if ($status === 'success') {
            $out .= '<p>' . esc_html__('Your password has been saved. You can now sign in.', 'metra-classifieds-plugin') . '</p>';
            $out .= '</div>';
            return $out;
        }

        if ($status === 'invalid') {
            $out .= '<p>' . esc_html__('The link is invalid or expired. Please request a new link.', 'metra-classifieds-plugin') . '</p>';
            $out .= '</div>';
            return $out;
        }

        if ($status === 'fail') {
            $out .= '<p>' . esc_html__('Please check your entries and try again.', 'metra-classifieds-plugin') . '</p>';
        }

        if ($uid <= 0 || $key === '') {
            $out .= '<p>' . esc_html__('Please open the link from your email to set your password.', 'metra-classifieds-plugin') . '</p>';
            $out .= '</div>';
            return $out;
        }

        // Lightweight pre-check so we can show a friendly message.
        if (!MC_User_Activation::is_set_password_link_valid($uid, $key)) {
            $out .= '<p>' . esc_html__('The link is invalid or expired. Please request a new link.', 'metra-classifieds-plugin') . '</p>';
            $out .= '</div>';
            return $out;
        }

        $out .= '<form method="post" class="mc-form" style="margin-top:16px;">';
        $out .= wp_nonce_field('mc_setpw', 'mc_setpw_nonce', true, false);
        $out .= '<input type="hidden" name="mc_uid" value="' . esc_attr((string) $uid) . '">';
        $out .= '<input type="hidden" name="mc_key" value="' . esc_attr($key) . '">';

        $out .= '<div class="mc-field">';
        $out .= '<label>' . esc_html__('New password', 'metra-classifieds-plugin') . '</label>';
        $out .= '<input type="password" name="mc_pw1" class="mc-input" required>';
        $out .= '</div>';

        $out .= '<div class="mc-field">';
        $out .= '<label>' . esc_html__('Confirm new password', 'metra-classifieds-plugin') . '</label>';
        $out .= '<input type="password" name="mc_pw2" class="mc-input" required>';
        $out .= '</div>';

        $out .= '<button type="submit" name="mc_setpw_submit" value="1" class="mc-button">' . esc_html__('Save password', 'metra-classifieds-plugin') . '</button>';
        $out .= '<script>(function(){var f=document.getElementById("mc-edit-page-form");if(!f) return;var shown=false;f.addEventListener("submit",function(){if(shown) return;shown=true;var m=document.getElementById("mc-edit-wait-modal");if(m){m.style.display="flex";m.setAttribute("aria-hidden","false");}var b=document.getElementById("mc-edit-page-submit");if(b){b.disabled=true;b.style.opacity=".7";b.style.cursor="not-allowed";}},{passive:true});})();</script>';
$out .= '</form>';

        $out .= '</div>';
        return $out;
    }

    /**
     * Helper: Build a URL to one of the required pages.
     */
        // === URL helpers =======================================================
private static function page_url(string $key, array $query = []): string {
        $base = MC_Pages::get_page_url($key);
        if ($base === '') {
            $base = home_url('/');
        }
        if (empty($query)) {
            return $base;
        }
        return add_query_arg(array_map('rawurlencode', array_map('strval', $query)), $base);
    }


    private static function category_tree_url(int $term_id): string {
        $base = MC_Pages::get_page_url('categories_list');
        if ($base === '') {
            $base = self::page_url('listings');
        }
        return add_query_arg(['focus_cat' => (string) $term_id], $base);
    }

    /**
     * Build a list-excerpt for a listing.
     *
     * Primary mode is word-based (mc_excerpt_words). If the text effectively contains no
     * usable word boundaries (e.g. one long token), we fall back to a character limit.
     */
        // === HTML builders =====================================================
private static function build_listing_excerpt(int $post_id, int $excerpt_words): string {
        if ($excerpt_words <= 0) {
            return '';
        }

        // Prefer the manual excerpt, fallback to content.
        $raw = (string) get_post_field('post_excerpt', $post_id);
        if (trim($raw) === '') {
            $raw = (string) get_post_field('post_content', $post_id);
        }

        $raw = wp_strip_all_tags(strip_shortcodes($raw));

        // Normalize whitespace (incl. non-breaking spaces) so word logic behaves consistently.
        $raw = str_replace("Â ", ' ', $raw); // nbsp
        $raw = str_replace("Â ", ' ', $raw);       // sometimes seen in broken encodings
        $raw = preg_replace('/\s+/u', ' ', $raw);
        $raw = trim((string) $raw);

        if ($raw === '') {
            return '';
        }

        $excerpt = trim(wp_trim_words($raw, $excerpt_words, '…'));

        // Robust fallback: if wp_trim_words didn't shorten (single long token etc.),
        // cut by characters.
        $needs_char_fallback = ($excerpt === $raw);

        if ($needs_char_fallback) {
            $char_limit = max(60, (int) ($excerpt_words * 10));

            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($raw) > $char_limit) {
                    return trim((string) mb_substr($raw, 0, $char_limit)) . '…';
                }
            } else {
                if (strlen($raw) > $char_limit) {
                    return trim((string) substr($raw, 0, $char_limit)) . '…';
                }
            }
        }

        return $excerpt;
    }

    /**
     * [mc_submit_link label="Listing aufgeben" url="/mc-submit/"]
     */
    public static function submit_link(array $atts = []): string {
        $atts = shortcode_atts([
            'label' => __('Post a listing', 'metra-classifieds-plugin'),
            'url'   => '',
            'class' => 'mc-submit-link',
        ], $atts, 'mc_submit_link');

        $url = trim((string) $atts['url']);
        if ($url === '') {
            // Prefer the category wizard page (if present), so users always select
            // a final (leaf) category before reaching the submit form.
            $url = MC_Pages::get_page_url('submit_category');
            if ($url === '') {
                // Fallback to submit page.
                $url = MC_Pages::get_page_url('submit');
            }
        }
        if ($url === '') {
            // Fallback to settings option if present.
            $url = (string) get_option('mc_submit_page_url', '');
        }
        if ($url === '') {
            $url = home_url('/');
        }

        $label = esc_html((string) $atts['label']);
        $class = esc_attr((string) $atts['class']);
        $href  = esc_url($url);

        return '<a class="' . $class . '" href="' . $href . '">' . $label . '</a>';
    }

    /**
     * Category drilldown wizard shown before the submit form.
     *
     * Page should use [mc_submit_category] and is auto-created as /mc-submit-category/.
     *
     * Flow:
     * - User navigates categories by clicking category cards.
     * - Clicking a non-leaf category drills down into its children.
     * - Clicking a leaf category redirects to /mc-submit/?mc_cat=<leaf_id>
     */
    public static function submit_category_wizard(array $atts = []): string {
    // Only logged in users may post listings.
    if (!is_user_logged_in()) {
        $login_url = wp_login_url(MC_Pages::get_page_url('submit_category') ?: home_url('/'));
        return '<div class="mc-submit-form mc-not-logged-in">'
            . esc_html__('Please log in to post a listing.', 'metra-classifieds-plugin')
            . ' <a href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a>'
            . '</div>';
    }

    $base_url = MC_Pages::get_page_url('submit_category');
    if ($base_url === '') {
        $base_url = home_url('/mc-submit-category/');
    }
    $submit_url = MC_Pages::get_page_url('submit');
    if ($submit_url === '') {
        $submit_url = home_url('/mc-submit/');
    }

    // Current category context
    $current_id = isset($_GET['mc_cat']) ? absint((string) $_GET['mc_cat']) : 0;
    $current = null;
    if ($current_id > 0) {
        $t = get_term($current_id, MC_CPT::TAX);
        if ($t && !is_wp_error($t)) {
            $current = $t;
        } else {
            $current_id = 0;
        }
    }

    $parent_id = $current ? (int) $current->term_id : 0;

    // Load direct children for the current parent
    $children = get_terms([
        'taxonomy'   => MC_CPT::TAX,
        'hide_empty' => false,
        'parent'     => $parent_id,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);
    if (!is_array($children)) {
        $children = [];
    }

    // Helper: does term have any children?
    $has_children = static function(int $term_id): bool {
        $ids = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => $term_id,
            'number'     => 1,
            'fields'     => 'ids',
        ]);
        return is_array($ids) && !empty($ids);
    };

    $title   = esc_html__('Categories', 'metra-classifieds-plugin');
    $section = $current ? (string) $current->name : esc_html__('All categories', 'metra-classifieds-plugin');

    // Links: cancel/back
    $cancel_href = home_url('/');
    $back_href = '';
    if ($current && (int) $current->parent > 0) {
        $back_href = add_query_arg('mc_cat', (int) $current->parent, $base_url) . '#mc-catwiz-top';
    } elseif ($current) {
        $back_href = $base_url . '#mc-catwiz-top'; // back to root
    }

    $out  = '<div class="mc-catwiz" id="mc-catwiz-top">';
    $out .= '  <div class="mc-catwiz-overlay" aria-hidden="true"></div>';
    $out .= '  <div class="mc-catwiz-sheet" role="dialog" aria-label="' . esc_attr($title) . '">';
    $out .= '    <div class="mc-catwiz-handle" aria-hidden="true"></div>';

    // Top bar (Cancel / Title)
    $out .= '    <div class="mc-catwiz-topbar">';
    $out .= '      <a class="mc-catwiz-cancel" href="' . esc_url($cancel_href) . '">' . esc_html__('Cancel', 'metra-classifieds-plugin') . '</a>';
    $out .= '      <div class="mc-catwiz-title">' . esc_html($title) . '</div>';
    $out .= '      <span class="mc-catwiz-spacer" aria-hidden="true"></span>';
    $out .= '    </div>';

    // Section label
    $out .= '    <div class="mc-catwiz-section">' . esc_html($section) . '</div>';

    // Optional back row when inside a subcategory
    if ($back_href !== '') {
        $out .= '    <div class="mc-catwiz-backrow">';
        $out .= '      <a class="mc-catwiz-back" href="' . esc_url($back_href) . '">‹ ' . esc_html__('Back', 'metra-classifieds-plugin') . '</a>';
        $out .= '    </div>';
    }

    // Items
    if (empty($children)) {
        // Current is a leaf category -> continue to submit form with this category selected.
        if ($current) {
            $out .= '    <div class="mc-catwiz-empty">';
            $out .=          esc_html__('This category has no subcategories.', 'metra-classifieds-plugin');
            $out .= '    </div>';
            $out .= '    <div class="mc-catwiz-actions">';
            $out .= '      <a class="mc-catwiz-primary" href="' . esc_url(add_query_arg('mc_cat', (int) $current->term_id, $submit_url)) . '">'
                . esc_html__('Choose this category', 'metra-classifieds-plugin') . '</a>';
            $out .= '    </div>';
        } else {
            $out .= '    <div class="mc-catwiz-empty">' . esc_html__('No categories found.', 'metra-classifieds-plugin') . '</div>';
        }
    } else {
        $out .= '    <div class="mc-catwiz-list">';
        foreach ($children as $term) {
            if (!$term || is_wp_error($term)) {
                continue;
            }
            $tid  = (int) $term->term_id;
            $name = (string) $term->name;

            $is_parent = $has_children($tid);

            // If the term has children -> drill down; otherwise -> leaf selection redirects to submit form.
            $href = $is_parent
                ? add_query_arg('mc_cat', $tid, $base_url) . '#mc-catwiz-top'
                : add_query_arg('mc_cat', $tid, $submit_url);

            $out .= '      <a class="mc-catwiz-item" href="' . esc_url($href) . '">';
            $out .= '        <span class="mc-catwiz-name">' . esc_html($name) . '</span>';
            $out .= '        <span class="mc-catwiz-chevron" aria-hidden="true"><svg class="mc-catwiz-chevron-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>';
            $out .= '      </a>';
        }
        $out .= '    </div>';
    }

    $out .= '  </div>';
    $out .= '</div>';

    return $out;
}

    /**
     * Dashboard page.
     *
     * Modern marketplace-style dashboard:
     * - compact stats row
     * - action cards with icons
     * - all labels fully translatable
     * - styling lives in the managed METRA master CSS block
     */
    public static function dashboard(array $atts = []): string {
        $dashboard_url = self::page_url('dashboard');
        if ($dashboard_url === '') {
            $dashboard_url = home_url('/mc-dashboard/');
        }

        if (!is_user_logged_in()) {
            $login_url = wp_login_url($dashboard_url);

            $out  = '<div class="mc-dashboard">';
            $out .= '<div class="mc-dashboard-shell">';
            $out .= '<div class="mc-dashboard-login">';
            $out .= '<h2 class="mc-dashboard-title">' . esc_html__('My dashboard', 'metra-classifieds-plugin') . '</h2>';
            $out .= '<p class="mc-dashboard-login-text">' . esc_html__('You are currently not logged in.', 'metra-classifieds-plugin') . '</p>';
            $out .= '<p><a class="button mc-dashboard-login-button" href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a></p>';
            $out .= '</div>';
            $out .= '</div>';
            $out .= '</div>';

            return $out;
        }

        $user_id = (int) get_current_user_id();
        $current_user = wp_get_current_user();
        $dashboard_display_name = '';
        if ($current_user instanceof \WP_User) {
            $dashboard_display_name = (string) $current_user->display_name;
            if ($dashboard_display_name === '') {
                $dashboard_display_name = (string) $current_user->user_login;
            }
        }
        if ($dashboard_display_name === '') {
            $dashboard_display_name = (string) __('User', 'metra-classifieds-plugin');
        }
        $dashboard_title = sprintf(
            esc_html__('%s\'s Dashboard', 'metra-classifieds-plugin'),
            $dashboard_display_name
        );

        $my_listings_query = new \WP_Query([
            'post_type'      => MC_CPT::CPT,
            'post_status'    => ['publish', 'pending', 'draft'],
            'author'         => $user_id,
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ]);
        $my_listings_count = (int) $my_listings_query->found_posts;
        wp_reset_postdata();

        $favorites_count = 0;
        if (class_exists(MC_Favorites::class)) {
            $favorite_ids = MC_Favorites::get_user_favorites($user_id);
            if (is_array($favorite_ids)) {
                $favorites_count = count(array_filter(array_map('intval', $favorite_ids), static function (int $listing_id): bool {
                    return $listing_id > 0;
                }));
            }
        }

        $published_total = wp_count_posts(MC_CPT::CPT);
        $published_count = isset($published_total->publish) ? (int) $published_total->publish : 0;

        $cards = [
            'submit' => [
                'label' => __('Post a listing', 'metra-classifieds-plugin'),
                'text'  => __('Create a new advertisement for the marketplace.', 'metra-classifieds-plugin'),
                'url'   => self::page_url('submit_category') ?: self::page_url('submit'),
                'icon'  => 'camera',
            ],
            'my_listings' => [
                'label' => __('My listings', 'metra-classifieds-plugin'),
                'text'  => __('Manage, edit, pause, or review your own listings.', 'metra-classifieds-plugin'),
                'url'   => self::page_url('my_listings'),
                'icon'  => 'calendar',
            ],
            'favorites' => [
                'label' => __('Favorites', 'metra-classifieds-plugin'),
                'text'  => __('Open your saved favorite listings.', 'metra-classifieds-plugin'),
                'url'   => self::page_url('favorites') ?: MC_Profile::get_tab_url('favorites'),
                'icon'  => 'heart-filled',
            ],
            'categories' => [
                'label' => __('Categories', 'metra-classifieds-plugin'),
                'text'  => __('Browse all marketplace categories.', 'metra-classifieds-plugin'),
                'url'   => self::page_url('categories_list') ?: self::page_url('categories'),
                'icon'  => 'location',
            ],
            'my_profile' => [
                'label' => __('My Profile', 'metra-classifieds-plugin'),
                'text'  => __('Edit your public account details and preferences.', 'metra-classifieds-plugin'),
                'url'   => MC_Profile::get_tab_url('profile'),
                'icon'  => 'user',
            ],
            'listings' => [
                'label' => __('Listings', 'metra-classifieds-plugin'),
                'text'  => __('Go to the public listings overview.', 'metra-classifieds-plugin'),
                'url'   => self::page_url('listings'),
                'icon'  => 'camera',
            ],
        ];

        $stats = [
            [
                'label' => __('My listings', 'metra-classifieds-plugin'),
                'value' => $my_listings_count,
            ],
            [
                'label' => __('Favorites', 'metra-classifieds-plugin'),
                'value' => $favorites_count,
            ],
            [
                'label' => __('Published listings', 'metra-classifieds-plugin'),
                'value' => $published_count,
            ],
        ];

        $out  = '<div class="mc-dashboard">';
        $out .= '<div class="mc-dashboard-shell">';
        $out .= '<div class="mc-dashboard-header">';
        $out .= '<h2 class="mc-dashboard-title">' . esc_html($dashboard_title) . '</h2>';
        $out .= '<p class="mc-dashboard-intro">' . esc_html__('Manage your marketplace activity from one central place.', 'metra-classifieds-plugin') . '</p>';
        $out .= '</div>';

        $out .= '<div class="mc-dashboard-stats" role="list">';
        foreach ($stats as $stat) {
            $out .= '<div class="mc-dashboard-stat" role="listitem">';
            $out .= '<span class="mc-dashboard-stat__value">' . esc_html(number_format_i18n((int) $stat['value'])) . '</span>';
            $out .= '<span class="mc-dashboard-stat__label">' . esc_html((string) $stat['label']) . '</span>';
            $out .= '</div>';
        }
        $out .= '</div>';

        $out .= '<div class="mc-dashboard-grid">';
        foreach ($cards as $card) {
            $icon_html = MC_Icons::render((string) $card['icon'], [
                'class' => 'mc-dashboard-card__icon-svg',
                'width' => 24,
                'height' => 24,
                'label' => (string) $card['label'],
            ]);

            $out .= '<a class="mc-dashboard-card" href="' . esc_url((string) $card['url']) . '">';
            $out .= '<span class="mc-dashboard-card__icon" aria-hidden="true">' . $icon_html . '</span>';
            $out .= '<span class="mc-dashboard-card__body">';
            $out .= '<span class="mc-dashboard-card__title">' . esc_html((string) $card['label']) . '</span>';
            $out .= '<span class="mc-dashboard-card__text">' . esc_html((string) $card['text']) . '</span>';
            $out .= '</span>';
            $out .= '</a>';
        }
        $out .= '</div>';

        $out .= '</div>';
        $out .= '</div>';

        return $out;
    }

    /**
     * Simple submit form.
     * Page should use [mc_submit_form].
     */
    public static function submit_form(array $atts = []): string {
        if (!is_user_logged_in()) {
            $login_target = MC_Pages::get_page_url('submit_category');
            if ($login_target === '') {
                $login_target = MC_Pages::get_page_url('submit') ?: home_url('/');
            }
            $login_url = wp_login_url($login_target);
            return '<div class="mc-submit-form mc-not-logged-in">'
                . esc_html__('Please log in to post a listing.', 'metra-classifieds-plugin')
                . ' <a href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
        }

        // If the user opens /mc-submit/ directly without selecting a category first,
        // redirect them to the drilldown wizard.
        if (!isset($_POST['mc_submit_form']) && (!isset($_GET['mc_cat']) || absint((string) $_GET['mc_cat']) <= 0)) {
            $wiz = MC_Pages::get_page_url('submit_category');
            if ($wiz !== '') {
                wp_safe_redirect($wiz);
                exit;
            }
        }

        $field_errors = [];
        $global_errors = [];
        $success_id = 0;

        $posted = [];
        if (isset($_POST['mc_submit_form']) && is_array($_POST['mc_submit_form'])) {
            $posted = (array) $_POST['mc_submit_form'];
        }

        if (!empty($posted)) {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], 'mc_submit_listing')) {
                $global_errors[] = __('Security check failed.', 'metra-classifieds-plugin');
            } else {
                $data = $posted;

                // Basic normalization
                $title = sanitize_text_field((string) ($data['title'] ?? ''));
                // === MC: Title max chars validation v0.0705 START ===
                $mc_title_max = (int) get_option('mc_title_max_chars', 35);
                $mc_title_max = max(1, min(200, $mc_title_max));
                $title = mb_substr($title, 0, $mc_title_max);
                // === MC: Title max chars validation v0.0705 END ===

                $desc  = self::sanitize_public_description((string) ($data['description'] ?? ''));

                $cat   = (int) ($data['category'] ?? 0);


                $price_raw   = (string) ($data['price'] ?? '');
                $price_clean = preg_replace('/\D+/', '', $price_raw);
                $price_clean = mb_substr((string) $price_clean, 0, 12);
                $price_negotiable = !empty($data['price_negotiable']);

                $condition_raw = sanitize_key((string) ($data['condition'] ?? ''));
                $allowed_slugs = ['new', 'like-new', 'lightly-used', 'used'];

                // Validation
                if ($title === '') {
                    $field_errors['title'] = __('Please enter a title.', 'metra-classifieds-plugin');
                }

                if ($cat <= 0) {
                    $field_errors['category'] = __('Please choose a category.', 'metra-classifieds-plugin');
                } elseif (!self::is_leaf_category_term($cat)) {
                    $field_errors['category'] = __('Please select a subcategory (not a category group).', 'metra-classifieds-plugin');
                }


                if ($price_clean === '') {
                    $field_errors['price'] = __('Please enter a price.', 'metra-classifieds-plugin');
                } elseif (!ctype_digit($price_clean)) {
                    $field_errors['price'] = __('Please enter a valid price.', 'metra-classifieds-plugin');
                }

                if ($desc === '') {
                    $field_errors['description'] = __('Please enter a description.', 'metra-classifieds-plugin');
                }

                if ($condition_raw !== '' && !in_array($condition_raw, $allowed_slugs, true)) {
                    $field_errors['condition'] = __('Please choose a valid condition.', 'metra-classifieds-plugin');
                }

                // Create listing
                if (empty($global_errors) && empty($field_errors)) {
                    $user_id = get_current_user_id();

                    $moderation = get_option('mc_moderation_mode', 'immediate');
                    if ($moderation === 'admin') {
                        $status = 'pending';
                    } else {
                        $status = 'publish';
                    }

                    $post_id = wp_insert_post([
                        'post_type'    => MC_CPT::CPT,
                        'post_status'  => $status,
                        'post_title'   => $title,
                        'post_content' => $desc,
                        'post_author'  => $user_id,
                    ], true);

                    if (is_wp_error($post_id) || (int) $post_id <= 0) {
                        $global_errors[] = __('Could not create the listing. Please try again.', 'metra-classifieds-plugin');
                    } else {
                        $post_id = (int) $post_id;

                        if ($cat > 0) {
                            wp_set_object_terms($post_id, [$cat], MC_CPT::TAX, false);
                        }

                        // Price
                        if ($price_clean !== '') {
                            update_post_meta($post_id, 'mc_price', (int) $price_clean);
                        } else {
                            delete_post_meta($post_id, 'mc_price');
                        }
                        update_post_meta($post_id, 'mc_price_type', $price_negotiable ? 'negotiable' : 'fixed');

                        // Condition (taxonomy)
                        if ($condition_raw !== '' && in_array($condition_raw, $allowed_slugs, true)) {
                            wp_set_object_terms($post_id, [$condition_raw], 'mc_condition', false);
                        } else {
                            wp_set_object_terms($post_id, [], 'mc_condition', false);
                        }

                        // Location comes from the user profile only.
                        delete_post_meta($post_id, 'mc_zip');
                        delete_post_meta($post_id, 'mc_city');

                        // Images (optional multi-upload)
                        $max_images = (int) apply_filters('mc_submit_max_images', 8);
                        if (!empty($_FILES['mc_images']) && is_array($_FILES['mc_images'])) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                            require_once ABSPATH . 'wp-admin/includes/media.php';
                            require_once ABSPATH . 'wp-admin/includes/image.php';

                            $files = $_FILES['mc_images'];
                            $count = isset($files['name']) && is_array($files['name']) ? count($files['name']) : 0;
                            $count = min($count, max(0, $max_images));

                            $gallery_ids = [];

                            for ($i = 0; $i < $count; $i++) {
                                if (empty($files['name'][$i])) {
                                    continue;
                                }

                                $file_array = [
                                    'name'     => $files['name'][$i],
                                    'type'     => $files['type'][$i] ?? '',
                                    'tmp_name' => $files['tmp_name'][$i] ?? '',
                                    'error'    => $files['error'][$i] ?? 0,
                                    'size'     => $files['size'][$i] ?? 0,
                                ];

                                // Basic mime allowlist
                                $type = wp_check_filetype_and_ext($file_array['tmp_name'], $file_array['name']);
                                $mime = (string) ($type['type'] ?? '');
                                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
                                    continue;
                                }

                                // Temporarily override global for media_handle_sideload compatibility
                                $overrides = ['test_form' => false];

                                // Inject into $_FILES slot expected by media_handle_upload
                                $_FILES['mc_single_image'] = $file_array;

                                $att_id = media_handle_upload('mc_single_image', $post_id, [], $overrides);
                                if (!is_wp_error($att_id) && (int) $att_id > 0) {
                                    $att_id = (int) $att_id;
                                    $gallery_ids[] = $att_id;
                                    // Set first uploaded image as featured
                                    if (!has_post_thumbnail($post_id)) {
                                        set_post_thumbnail($post_id, $att_id);
                                    }
                                }
                            }

                            if (!empty($gallery_ids)) {
                                // Persist gallery for edit screen and sorting
                                update_post_meta($post_id, 'mc_gallery_ids', array_values(array_unique(array_map('intval', $gallery_ids))));
                            }

                            unset($_FILES['mc_single_image']);
                        }

                        // Send METRA notifications for the new listing.
                        \MetraClassifieds\MC_Emails::notify_new_listing($post_id);

                        // Success: redirect to avoid resubmission
                        $success_id = $post_id;
                        if ($status === 'pending') {
                            $pending_url = MC_Pages::get_page_url('submit_pending');
                            if ($pending_url === '') {
                                $pending_url = home_url('/mc-submit-pending/');
                            }
                            $success_url = add_query_arg(['mc_submitted' => '1', 'id' => $post_id], $pending_url);
                        } else {
                            $success_url = add_query_arg(['mc_submitted' => '1', 'id' => $post_id], get_permalink($post_id));
                        }
                        wp_safe_redirect($success_url);
                        exit;
                    }
                }
            }
        }

        // Build sticky values (use POST first, then mc_cat query param for category)
        $title_val = isset($posted['title']) ? sanitize_text_field((string) $posted['title']) : '';
        $desc_val  = isset($posted['description']) ? (string) $posted['description'] : '';
        $price_val = isset($posted['price']) ? sanitize_text_field((string) $posted['price']) : '';
        $price_negotiable_val = !empty($posted['price_negotiable']);

        $cat_val = 0;
        if (isset($posted['category'])) {
            $cat_val = (int) $posted['category'];
        } elseif (isset($_GET['mc_cat'])) {
            $cat_val = absint((string) $_GET['mc_cat']);
        }

        $cond_val = isset($posted['condition']) ? sanitize_key((string) $posted['condition']) : '';

        $out = '<div class="mc-submit-form">';

        if (!empty($global_errors) || !empty($field_errors)) {
            $out .= '<div class="mc-notice mc-notice-error"><ul style="margin:0;padding-left:18px;">';
            foreach ($global_errors as $e) {
                $out .= '<li>' . esc_html($e) . '</li>';
            }
            foreach ($field_errors as $e) {
                $out .= '<li>' . esc_html($e) . '</li>';
            }
            $out .= '</ul></div>';
        }

        if (isset($_GET['mc_submitted']) && (string) $_GET['mc_submitted'] === '1') {
            $out .= '<div class="mc-notice mc-notice-success">' . esc_html__('Listing submitted successfully.', 'metra-classifieds-plugin') . '</div>';
        }

        // Category terms
        $terms = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        // Condition terms (taxonomy)
        $cond_terms = get_terms([
            'taxonomy'   => 'mc_condition',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        $field_style = 'width:100%;max-width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;';
        $field_style_narrow = 'width:100%;max-width:520px;padding:10px;border:1px solid #ddd;border-radius:10px;';
        $price_field_style = 'width:100%;max-width:12ch;padding:10px;border:1px solid #ddd;border-radius:10px;';
        $err_style = ' style="color:#b00020;margin:6px 0 0 0;"';

        $out .= '<form method="post" enctype="multipart/form-data">';
        $out .= wp_nonce_field('mc_submit_listing', '_wpnonce', true, false);

        // Title
        // === MC: Title max chars input v0.0708 START ===
        $mc_title_max_chars = (int) get_option('mc_title_max_chars', 35);
        $mc_title_max_chars = max(1, min(200, $mc_title_max_chars));
        $out .= self::render_title_guard_script();
        $out .= '<p><label><strong>' . esc_html__('Title', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<input type="text" id="mc-title-input-submit" name="mc_submit_form[title]" value="' . esc_attr($title_val) . '" style="' . esc_attr($field_style) . '" maxlength="' . esc_attr((string) $mc_title_max_chars) . '" placeholder="' . esc_attr('Max. ' . $mc_title_max_chars) . '" autocomplete="off" required>'
            . '<span id="mc-title-counter-submit" style="display:block;font-size:12px;margin-top:4px;color:#666;"></span>'
            . '</p>';
        $out .= '<script>mcTitleGuard("mc-title-input-submit","mc-title-counter-submit",' . (int)$mc_title_max_chars . ');</script>';
        // === MC: Title max chars input v0.0708 END ===
        if (isset($field_errors['title'])) {
            $out .= '<p'.$err_style.'>' . esc_html($field_errors['title']) . '</p>';
        }

        // Category
        $out .= '<p><label><strong>' . esc_html__('Category', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<select name="mc_submit_form[category]" style="' . esc_attr($field_style_narrow) . '" required>';
        $out .= '<option value="0">' . esc_html__('Select a category', 'metra-classifieds-plugin') . '</option>';
        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $t) {
                $out .= '<option value="' . esc_attr((string) $t->term_id) . '" ' . selected($cat_val, (int) $t->term_id, false) . '>' . esc_html($t->name) . '</option>';
            }
        }
        $out .= '</select></p>';
        if (isset($field_errors['category'])) {
            $out .= '<p'.$err_style.'>' . esc_html($field_errors['category']) . '</p>';
        }

        // Price
        $out .= '<div class="mc-price-row-wrap"><label style="display:block;"><strong>' . esc_html__('Price', 'metra-classifieds-plugin') . '</strong></label>'
            . '<div class="mc-price-row" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">'
            . '<input type="number" step="1" min="0" inputmode="numeric" pattern="[0-9]*" name="mc_submit_form[price]" value="' . esc_attr($price_val) . '" style="' . esc_attr($price_field_style) . '" required>'
            . '<label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;"><input type="checkbox" name="mc_submit_form[price_negotiable]" value="1" ' . checked($price_negotiable_val, true, false) . ' /> ' . esc_html__('Negotiable', 'metra-classifieds-plugin') . '</label>'
            . '</div>'
            . '<p class="description mc-field-hint" style="margin:6px 0 18px 0;max-width:100%;">' . esc_html__('Numbers only. Do not use currency symbols or special characters.', 'metra-classifieds-plugin') . '</p>'
            . '</div>';
        if (isset($field_errors['price'])) {
            $out .= '<p'.$err_style.'>' . esc_html($field_errors['price']) . '</p>';
        }

        // Condition
        $out .= '<p><label><strong>' . esc_html__('Condition', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<select name="mc_submit_form[condition]" style="' . esc_attr($field_style_narrow) . '">';
        $out .= '<option value="">' . esc_html__('Select condition', 'metra-classifieds-plugin') . '</option>';
        if (!is_wp_error($cond_terms) && !empty($cond_terms)) {
            foreach ($cond_terms as $t) {
                $slug = sanitize_key($t->slug);
                $label = $t->name;
                if (class_exists('MetraClassifieds\\MC_Helpers') && method_exists('MetraClassifieds\\MC_Helpers', 'mc_translate_condition_label')) {
                    $label = MC_Helpers::mc_translate_condition_label($slug, $label);
                }
                $out .= '<option value="' . esc_attr($slug) . '" ' . selected($cond_val, $slug, false) . '>' . esc_html($label) . '</option>';
            }
        }
        $out .= '</select></p>';
        if (isset($field_errors['condition'])) {
            $out .= '<p'.$err_style.'>' . esc_html($field_errors['condition']) . '</p>';
        }

        // Description
        $out .= '<div><label><strong>' . esc_html__('Description', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<textarea name="mc_submit_form[description]" rows="8" style="' . esc_attr($field_style) . '" required>' . esc_textarea($desc_val) . '</textarea>'
            . '<p class="description mc-field-hint" style="margin:6px 0 18px 0;max-width:100%;">' . esc_html__('Tip: Use **word** for bold text. Links, email addresses and websites are not allowed.', 'metra-classifieds-plugin') . '</p></div>';
        if (isset($field_errors['description'])) {
            $out .= '<p'.$err_style.'>' . esc_html($field_errors['description']) . '</p>';
        }

        // Images
        // Keep the native file input name (mc_images[]) unchanged so all existing upload
        // handling continues to work. The drag & drop UI is only an enhancement layer.
        $out .= '<div class="mc-upload-dnd">';
        $out .= '<label><strong>' . esc_html__('Images', 'metra-classifieds-plugin') . '</strong></label><br />';
        $out .= '<input id="mc-submit-images" class="mc-upload-dnd__input" type="file" name="mc_images[]" accept="image/*" multiple />';
        $out .= '<div class="mc-upload-dnd__surface" tabindex="0" role="button" aria-controls="mc-submit-images">';
        $out .= '<span class="mc-upload-dnd__title">' . esc_html__('Drop images here', 'metra-classifieds-plugin') . '</span>';
        $out .= '<span class="mc-upload-dnd__hint">' . esc_html__('or choose files', 'metra-classifieds-plugin') . '</span>';
        $out .= '<button type="button" class="mc-upload-dnd__button mc-btn mc-btn-secondary">' . esc_html__('Choose images', 'metra-classifieds-plugin') . '</button>';
        $out .= '<span class="mc-upload-dnd__status">' . esc_html__('No files selected', 'metra-classifieds-plugin') . '</span>';
        $out .= '<ul class="mc-upload-dnd__list" aria-live="polite"></ul>';
        $out .= '</div>';
        $out .= '</div>';
        $out .= '<p class="description" style="margin-top:6px;max-width:100%;">'
            . esc_html__('You can select multiple images. The first image will be used as the featured image.', 'metra-classifieds-plugin')
            . '</p>';

        $out .= '<p><button type="submit" class="mc-btn mc-btn-primary">' . esc_html__('Submit', 'metra-classifieds-plugin') . '</button></p>';
        $out .= '</form>';

        $out .= '</div>';

        return $out;
    }

    /**
     * Breadcrumb shortcode.
     * [mc_breadcrumb] — renders breadcrumb for the current listing.
     */
    public static function breadcrumb_shortcode(): string {
        $post_id = get_the_ID();
        if (!$post_id) return '';
        return \MetraClassifieds\MC_Listing_View::render_breadcrumb((int) $post_id);
    }

    /**
     * Simple listings view.
     * [mc_listings per_page="25"]
     */
    public static function listings(array $atts = []): string {
        $atts = shortcode_atts([
            'per_page'       => '25',
            'category_id'    => '0',
            'force_layout'   => '0',
            'custom_heading' => '',
        ], $atts, 'mc_listings');

        // Per-page can be provided by shortcode attribute (default) or overridden via GET parameter.
        // Allowed values are intentionally limited to keep the UI predictable.
        $allowed_pp = [10, 25, 50, 100];
        $pp_from_get = isset($_GET['mc_pp']) ? (string) $_GET['mc_pp'] : '';
        $pp_candidate = (int) $atts['per_page'];
        if ($pp_from_get !== '') {
            $pp_candidate = (int) $pp_from_get;
        }
        if (!in_array($pp_candidate, $allowed_pp, true)) {
            $pp_candidate = (int) $atts['per_page'];
        }
        if (!in_array($pp_candidate, $allowed_pp, true)) {
            $pp_candidate = 10;
        }
        $per_page = $pp_candidate;
        // Pagination on a static front page can use the query var "page" (not "paged").
        // Also, some setups only provide the page number via querystring (?paged=3).
        $paged = 1;
        $paged = max($paged, (int) get_query_var('paged'));
        $paged = max($paged, (int) get_query_var('page'));
        $paged = max($paged, isset($_GET['paged']) ? absint((string) $_GET['paged']) : 0);

        // Search term is accepted on all pages, but the home "top search" UI is only shown on the landing context.
        $search_term = isset($_GET['mc_q']) ? sanitize_text_field((string) $_GET['mc_q']) : '';

        $force_layout = !empty($atts['force_layout']) && $atts['force_layout'] !== '0';
        $custom_heading = isset($atts['custom_heading']) ? sanitize_text_field((string) $atts['custom_heading']) : '';
        $category_id = isset($atts['category_id']) ? absint((string) $atts['category_id']) : 0;

        $show_home_top = (is_front_page() || is_home());

        // Design option: homepage layout (desktop)
        $design = get_option('mc_design', []);
        if (!is_array($design)) {
            $design = [];
        }
        $home_layout = isset($design['home_layout']) && is_string($design['home_layout']) ? sanitize_key($design['home_layout']) : 'sidebar_2col';
        if (!in_array($home_layout, ['sidebar_2col', 'onecol'], true)) {
            $home_layout = 'sidebar_2col';
        }

        if (!$show_home_top) {
            // Also treat the plugin-created "Listings" page as landing context (common setup).
            $ids = get_option('mc_required_page_ids', []);
            if (is_array($ids) && !empty($ids['listings'])) {
                $listings_page_id = (int) $ids['listings'];
                if ($listings_page_id > 0 && is_page($listings_page_id)) {
                    $show_home_top = true;
                }
            }
        }

        if ($force_layout) {
            $show_home_top = true;
        }

        $args = [
            'post_type'      => MC_CPT::CPT,
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [[
                'relation' => 'OR',
                [
                    'key'     => self::PAUSE_META_KEY,
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => self::PAUSE_META_KEY,
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ]],
        ];
        if ($search_term !== '') {
            $args['s'] = $search_term;
        }

        // Seller filter: ?seller=USER_ID shows all active listings by that user.
        $seller_id = isset($_GET['seller']) ? absint((string) $_GET['seller']) : 0;
        if ($seller_id > 0 && get_userdata($seller_id) !== false) {
            $args['author'] = $seller_id;
        }

        // Category browse pages:
        // - leaf category => matching listings
        // - parent category => listings from the full subtree
        // This gives the expected marketplace behavior when a main category is clicked.
        if ($category_id > 0) {
            $args['tax_query'] = [[
                'taxonomy'         => MC_CPT::TAX,
                'field'            => 'term_id',
                'terms'            => [$category_id],
                'include_children' => true,
            ]];
        }

        $q = new \WP_Query($args);

        $excerpt_words = (int) get_option('mc_excerpt_words', 20);
        $excerpt_words = max(0, min(200, $excerpt_words));

        $thumb_w = (int) get_option('mc_thumb_w', 160);
        $thumb_h = (int) get_option('mc_thumb_h', 120);
        $thumb_w = max(40, min(800, $thumb_w));
        $thumb_h = max(40, min(800, $thumb_h));

        $out = '';
        if ($show_home_top) {
	        // Desktop homepage layout:
	        // - onecol  => left categories sidebar + ONE column of listings
	        // - sidebar => left categories sidebar + TWO columns of listings
	        $out .= '<div class="mc-home-layout ' . ($home_layout === 'onecol' ? 'mc-home-layout--onecol' : 'mc-home-layout--sidebar') . '">';
	        // Always render the categories sidebar on desktop layouts.
	        $out .= self::build_home_categories_sidebar();
            $out .= '<div class="mc-home-main">';
            if ($custom_heading !== '') {
                $out .= '<h1 class="mc-browse-title">' . esc_html($custom_heading) . '</h1>';
            }
            // Home search bar (full-text search)
            $search_hidden_args = [];
            if ($category_id > 0) {
                $search_hidden_args['cat'] = (string) $category_id;
            }
            $out .= self::build_home_search_bar($search_term, $search_hidden_args);

        }

if (!$q->have_posts()) {
    // On landing context we still show the left sidebar.
    if ($show_home_top) {
        return $out . '<div class="mc-listings mc-empty">' . esc_html__('No listings found.', 'metra-classifieds-plugin') . '</div></div></div>';
    }
    return '<div class="mc-listings mc-empty">' . esc_html__('No listings found.', 'metra-classifieds-plugin') . '</div>';
}

$out .= '<div class="mc-listings">';

        while ($q->have_posts()) {
            $q->the_post();

            $post_id = (int) get_the_ID();
            $url     = get_permalink($post_id);

            $thumb_html = '';
            if (has_post_thumbnail($post_id)) {
                $thumb_html = get_the_post_thumbnail(
                    $post_id,
                    [$thumb_w, $thumb_h],
                    [
                        'class'   => 'mc-thumb-img',
                        'style'   => 'width:' . (int) $thumb_w . 'px;height:' . (int) $thumb_h . 'px;',
                        'loading' => 'lazy',
                    ]
                );
            } else {
                $thumb_html = '<div class="mc-thumb-placeholder" aria-hidden="true" style="width:' . (int) $thumb_w . 'px;height:' . (int) $thumb_h . 'px;">'
                    . '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
                    . '<path d="M21 19V5C21 3.89543 20.1046 3 19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19Z" stroke="currentColor" stroke-width="1.5"/>'
                    . '<path d="M8.5 10.5C9.32843 10.5 10 9.82843 10 9C10 8.17157 9.32843 7.5 8.5 7.5C7.67157 7.5 7 8.17157 7 9C7 9.82843 7.67157 10.5 8.5 10.5Z" stroke="currentColor" stroke-width="1.5"/>'
                    . '<path d="M21 16L16.5 11.5L10 18L7 15L3 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>'
                    . '</svg>'
                    . '</div>';
            }
            $title = get_the_title($post_id);

            // Excerpt (list view)
            // Centralized so Home search + Listings always behave identically.
            $excerpt = self::build_listing_excerpt($post_id, $excerpt_words);

            $author_id = (int) get_post_field('post_author', $post_id);
            $zip  = trim((string) get_user_meta($author_id, 'mc_user_plz', true));
            $city = trim((string) get_user_meta($author_id, 'mc_user_ort', true));
            $location_bits = array_values(array_filter([$zip, $city], static fn($v) => $v !== ''));
            $location_label = implode(' ', $location_bits);

            $post_ts  = get_post_time('U', true, $post_id);
            $today_ymd = wp_date('Y-m-d');
            $post_ymd  = wp_date('Y-m-d', $post_ts);
            if ($post_ymd === $today_ymd) {
                $date_label = sprintf(
                    /* translators: %s = time */
                    esc_html__('Today, %s', 'metra-classifieds-plugin'),
                    wp_date(get_option('time_format'), $post_ts)
                );
            } else {
                $date_label = wp_date(get_option('date_format'), $post_ts);
            }

            $gallery_ids = get_post_meta($post_id, 'mc_gallery_ids', true);
            if (!is_array($gallery_ids)) {
                $gallery_ids = [];
            }
            $gallery_ids = array_values(array_filter(array_map('intval', $gallery_ids)));
            $image_count = count($gallery_ids);

            // Important UX: Only the thumbnail + title are links.
            // The excerpt/meta must remain selectable text (no full-card link overlay).
            $out .= '<article class="mc-listing">';

            $out .= '<div class="mc-listing-thumb">'
                . '<a class="mc-listing-thumb-link" href="' . esc_url((string) $url) . '">' . $thumb_html . '</a>';
            if ($image_count > 1) {
                $out .= '<div class="mc-listing-image-count mc-listing-image-count--badge">'
                    . '' . MC_Icons::render('camera', ['class' => 'mc-icon mc-icon-camera mc-listing-image-count__icon', 'width' => 14, 'height' => 14]) . ''
                    . '<span class="mc-listing-image-count__value">' . esc_html((string) $image_count) . '</span>'
                    . '</div>';
            }
            $out .= '</div>';

            $out .= '<div class="mc-listing-body">';

            if ($location_label !== '' || $date_label !== '') {
                $out .= '<div class="mc-listing-head-meta">';
                $out .= '<span class="mc-listing-head-meta__location">' . esc_html($location_label) . '</span>';
                $out .= '<span class="mc-listing-head-meta__date">' . esc_html($date_label) . '</span>';
                $out .= '</div>';
            }

            $out .= '<div class="mc-listing-title">'
                . '<a class="mc-listing-title-link" href="' . esc_url((string) $url) . '">' . esc_html((string) $title) . '</a>'
                . '</div>';

            if ($excerpt !== '') {
                $out .= '<div class="mc-listing-excerpt">' . esc_html($excerpt) . '</div>';
            }

            $mc_price = get_post_meta(get_the_ID(), 'mc_price', true);
            if ($mc_price !== '') {
                $out .= '<div class="mc-listing-price-row">';
                $out .= '<div class="mc-listing-price">' . esc_html(number_format_i18n((int) $mc_price)) . ' €</div>';
                $out .= self::render_favorite_heart_toggle($post_id, 'listing');
                $out .= '</div>';
            }

            $out .= '</div>'; // body
            $out .= '</article>';
        }

        wp_reset_postdata();

        $out .= '</div>';

        // Footer: pagination + per-page selector (kept minimal, preserves existing query args).
        $total_pages = (int) $q->max_num_pages;
        if ($total_pages < 1) {
            $total_pages = 1;
        }

        // Build a safe base URL keeping all current query args except 'paged'.
        $base_url = (string) remove_query_arg('paged');
        $page_url = static function(int $page) use ($base_url): string {
            return (string) add_query_arg('paged', (string) $page, $base_url);
        };

        // Per-page form (GET) - keeps all query args and resets to page 1 on change.
        $out .= '<div class="mc-listings-footer" style="display:flex;flex-wrap:wrap;align-items:center;gap:14px;justify-content:space-between;margin-top:14px;">';

        // Pagination (only if needed)
        if ($total_pages > 1) {
            $out .= '<div class="mc-pagination" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">';
            $out .= '<div class="mc-pagination-label" style="opacity:.9;">'
                . esc_html__('Page', 'metra-classifieds-plugin') . ' ' . (int) $paged . ' ' . esc_html__('of', 'metra-classifieds-plugin') . ' ' . (int) $total_pages
                . '</div>';

            // First / Prev
            if ($paged > 1) {
                $out .= '<a class="mc-page-link" href="' . esc_url($page_url(1)) . '" aria-label="' . esc_attr__('First page', 'metra-classifieds-plugin') . '">&laquo;&laquo;</a>';
                $out .= '<a class="mc-page-link" href="' . esc_url($page_url($paged - 1)) . '" aria-label="' . esc_attr__('Previous page', 'metra-classifieds-plugin') . '">&laquo;</a>';
            }

            // Number window
            $window = 3;
            $start = max(1, $paged - $window);
            $end   = min($total_pages, $paged + $window);
            for ($i = $start; $i <= $end; $i++) {
                if ($i === $paged) {
                    $out .= '<span class="mc-page-current" style="font-weight:700;">' . (int) $i . '</span>';
                } else {
                    $out .= '<a class="mc-page-link" href="' . esc_url($page_url($i)) . '">' . (int) $i . '</a>';
                }
            }

            // Next / Last
            if ($paged < $total_pages) {
                $out .= '<a class="mc-page-link" href="' . esc_url($page_url($paged + 1)) . '" aria-label="' . esc_attr__('Next page', 'metra-classifieds-plugin') . '">&raquo;</a>';
                $out .= '<a class="mc-page-link" href="' . esc_url($page_url($total_pages)) . '" aria-label="' . esc_attr__('Last page', 'metra-classifieds-plugin') . '">&raquo;&raquo;</a>';
            }
            $out .= '</div>';
        }

        // Per-page selector
        $out .= '<form method="get" class="mc-per-page" style="display:flex;align-items:center;gap:8px;">';
        // Preserve existing query args
        foreach ($_GET as $k => $v) {
            $k = (string) $k;
            if ($k === 'mc_pp' || $k === 'paged') {
                continue;
            }
            if (is_array($v)) {
                continue;
            }
            $out .= '<input type="hidden" name="' . esc_attr($k) . '" value="' . esc_attr((string) $v) . '" />';
        }
        $out .= '<label for="mc_pp" style="opacity:.9;">' . esc_html__('Listings per page', 'metra-classifieds-plugin') . '</label>';
        $out .= '<select id="mc_pp" name="mc_pp" onchange="this.form.submit()" style="padding:6px 8px;border:1px solid #ccc;border-radius:8px;">';
        foreach ($allowed_pp as $opt) {
            $out .= '<option value="' . (int) $opt . '" ' . selected($per_page, (int) $opt, false) . '>' . (int) $opt . '</option>';
        }
        $out .= '</select>';
        $out .= '</form>';

        $out .= '</div>';

        // Close wrappers for the home layout *after* the listings footer so the sidebar aligns with the full block.
        if ($show_home_top) {
            $out .= '</div></div>'; // .mc-home-main + .mc-home-layout
        }

        return $out;
    }

    /**
     * Home sidebar: categories (top-level + up to 2 children + "More …").
     * Step 1 (Homepage refactor): only adds this sidebar and keeps all other output unchanged.
     */

/**
 * Build the homepage full-text search bar (GET mc_q).
 * Kept minimal: no JS, preserves a few known filter params.
 */
private static function build_home_search_bar(string $value = '', array $extra_hidden = []): string {
    $value = sanitize_text_field($value);

    // Preserve common filters when searching.
    $hidden_map = [];
    foreach (['mc_cat', 'mc_scope', 'mc_pp', 'cat'] as $k) {
        if (isset($_GET[$k]) && $_GET[$k] !== '') {
            $hidden_map[$k] = sanitize_text_field((string) $_GET[$k]);
        }
    }
    foreach ($extra_hidden as $k => $v) {
        if ($v === '' || $v === null) {
            continue;
        }
        $hidden_map[sanitize_key((string) $k)] = sanitize_text_field((string) $v);
    }

    $hidden = '';
    foreach ($hidden_map as $k => $v) {
        $hidden .= '<input type="hidden" name="' . esc_attr((string) $k) . '" value="' . esc_attr((string) $v) . '">';
    }

    $ph  = esc_attr__('Search listings…', 'metra-classifieds-plugin');
    $btn = esc_html__('Search', 'metra-classifieds-plugin');

        $clear_label = esc_attr__('Clear search', 'metra-classifieds-plugin');
        $clear_hidden = ($value === '') ? ' hidden' : '';

        // Lupe + Chevron SVG icons (inline)
        $lupe_svg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
        $chev_svg = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>';
        $cats_label = esc_attr__('Show categories', 'metra-classifieds-plugin');

        return '<form class="mc-home-search" method="get" action="">'
        . $hidden
        . '<div class="mc-home-search-row">'
        . '<div class="mc-home-search-inputwrap">'
        . '<input class="mc-home-search-input" type="search" name="mc_q" value="' . esc_attr($value) . '" placeholder="' . $ph . '">'
        . '<button class="mc-home-search-clear" type="button" aria-label="' . $clear_label . '"' . $clear_hidden . '>×</button>'
        . '</div>'
        /* Desktop: blue Search button */
        /* mc-search-btn: desktop=blue text button, mobile=lupe icon */
        . '<button class="mc-search-btn mc-btn mc-btn-primary" type="submit" aria-label="' . $btn . '">'
        . '<span class="mc-search-btn__text">' . $btn . '</span>'
        . '<span class="mc-search-btn__icon" aria-hidden="true">' . $lupe_svg . '</span>'
        . '</button>'
        /* mc-cats-btn: mobile only, opens category bottom sheet */
        . '<button type="button" class="mc-cats-btn mc-home-categories-btn" id="mc-cats-open-btn" aria-haspopup="dialog" aria-label="' . $cats_label . '">'
        . $chev_svg
        . '</button>'
        . '</div>'
        . '</form>'
        . '<script>(function(){if(window.__mcHomeSearchClearInit){return;}window.__mcHomeSearchClearInit=true;document.addEventListener("click",function(e){var btn=e.target.closest(".mc-home-search-clear");if(!btn){return;}var wrap=btn.closest(".mc-home-search-inputwrap");if(!wrap){return;}var input=wrap.querySelector(".mc-home-search-input");if(!input){return;}input.value="";btn.hidden=true;input.focus();input.dispatchEvent(new Event("input",{bubbles:true}));});document.addEventListener("input",function(e){var input=e.target;if(!input.classList||!input.classList.contains("mc-home-search-input")){return;}var wrap=input.closest(".mc-home-search-inputwrap");if(!wrap){return;}var btn=wrap.querySelector(".mc-home-search-clear");if(!btn){return;}btn.hidden=input.value==="";});})();</script>'
        // <=980px: Bottom Sheet trigger is now the chevron icon in the search row
        // mc-cats-open-btn id is set on the chevron button above
        . '<div class="mc-cats-overlay" id="mc-cats-overlay" aria-hidden="true"></div>'
        . '<div class="mc-cats-sheet" id="mc-cats-sheet" role="dialog" aria-modal="true" aria-label="' . esc_attr__('Categories', 'metra-classifieds-plugin') . '">'
        .   '<div class="mc-cats-sheet__handle" aria-hidden="true"></div>'
        .   '<div class="mc-cats-sheet__topbar">'
        .     '<span class="mc-cats-sheet__spacer"></span>'
        .     '<span class="mc-cats-sheet__title">' . esc_html__('Categories', 'metra-classifieds-plugin') . '</span>'
        .     '<button type="button" class="mc-cats-sheet__close" id="mc-cats-close-btn">' . esc_html__('Close', 'metra-classifieds-plugin') . '</button>'
        .   '</div>'
        .   '<div class="mc-cats-sheet__body">'
        .     self::categories_grid(['_embedded' => '1'])
        .   '</div>'
        . '</div>'
        . '<script>'
        . 'document.addEventListener("DOMContentLoaded",function(){'
        . '  var sheet=document.getElementById("mc-cats-sheet");'
        . '  var overlay=document.getElementById("mc-cats-overlay");'
        . '  var openBtn=document.getElementById("mc-cats-open-btn");'
        . '  var closeBtn=document.getElementById("mc-cats-close-btn");'
        . '  if(!sheet)return;'
        . '  function openSheet(){'
        . '    sheet.classList.add("is-open");'
        . '    overlay.classList.add("is-open");'
        . '    document.body.classList.add("mc-sheet-open");'
        . '    sheet.setAttribute("aria-hidden","false");'
        . '    overlay.setAttribute("aria-hidden","false");'
        . '  }'
        . '  function closeSheet(){'
        . '    sheet.classList.remove("is-open");'
        . '    overlay.classList.remove("is-open");'
        . '    document.body.classList.remove("mc-sheet-open");'
        . '    sheet.setAttribute("aria-hidden","true");'
        . '    overlay.setAttribute("aria-hidden","true");'
        . '  }'
        . '  if(openBtn)openBtn.addEventListener("click",openSheet);'
        . '  if(closeBtn)closeBtn.addEventListener("click",closeSheet);'
        . '  overlay.addEventListener("click",closeSheet);'
        . '  /* swipe down to close */'
        . '  var ty=0;'
        . '  sheet.addEventListener("touchstart",function(e){ty=e.touches[0].clientY;},{passive:true});'
        . '  sheet.addEventListener("touchend",function(e){if(e.changedTouches[0].clientY-ty>60)closeSheet();},{passive:true});'
        . '  /* auto-open when cats=1 in URL */'
        . '  if(window.location.search.indexOf("cats=1")!==-1)openSheet();'
        . '  /* AJAX drilldown */'
        . '  var nonce="' . wp_create_nonce('mc_cats_drilldown') . '";'
        . '  var ajaxUrl="' . esc_url(admin_url('admin-ajax.php')) . '";'
        . '  var body=sheet.querySelector(".mc-cats-sheet__body");'
        . '  var sectionEl=sheet.querySelector(".mc-catwiz-section");'
        . '  var history=[];'
        . '  function drillDown(parentId,label){'
        . '    history.push({html:body.innerHTML,section:sectionEl?sectionEl.textContent:""});'
        . '    body.classList.add("mc-cats-loading");'
        . '    fetch(ajaxUrl+"?action=mc_cats_drilldown&parent="+parentId+"&nonce="+nonce)'
        . '      .then(function(r){return r.json();})'
        . '      .then(function(d){'
        . '        if(!d.success)return;'
        . '        var list=body.querySelector(".mc-catwiz-list");'
        . '        if(list)list.innerHTML=d.data.html;'
        . '        if(sectionEl)sectionEl.textContent=d.data.section;'
        . '        var back=body.querySelector(".mc-catwiz-backwrap");'
        . '        if(back)back.style.display=d.data.is_root?"none":"block";'
        . '        body.classList.remove("mc-cats-loading");'
        . '        attachDrillHandlers();'
        . '      });'
        . '  }'
        . '  function drillBack(){'
        . '    if(!history.length)return;'
        . '    var prev=history.pop();'
        . '    body.innerHTML=prev.html;'
        . '    if(sectionEl)sectionEl.textContent=prev.section;'
        . '    attachDrillHandlers();'
        . '  }'
        . '  function attachDrillHandlers(){'
        . '    body.querySelectorAll("[data-mc-drill]").forEach(function(el){'
        . '      el.addEventListener("click",function(e){'
        . '        e.preventDefault();'
        . '        drillDown(el.getAttribute("data-mc-drill"),el.getAttribute("data-mc-label"));'
        . '      },{once:true});'
        . '    });'
        . '    var backBtn=body.querySelector(".mc-catwiz-back");'
        . '    if(backBtn)backBtn.addEventListener("click",function(e){e.preventDefault();drillBack();},{once:true});'
        . '  }'
        . '  attachDrillHandlers();'
        . '});'
        . '</script>';
}

private static function build_home_categories_sidebar(): string {
// Mobile Step 2b: show a horizontal top navigation (main categories) instead of the desktop sidebar.
// This keeps the home layout stable while enabling a mobile-specific navigation UI.
        // mobile suppression removed so sidebar always renders


        $listings_url = MC_Pages::get_page_url('listings') ?: home_url('/');

        $out = '<aside class="mc-home-sidebar" aria-label="' . esc_attr__('Categories', 'metra-classifieds-plugin') . '">';
        $out .= '<div class="mc-home-sidebar-head">'
            . '<div class="mc-home-title">' . esc_html__('Categories', 'metra-classifieds-plugin') . '</div>'
            . '<a class="mc-home-sidebar-all" href="' . esc_url($listings_url) . '">' . esc_html__('View all', 'metra-classifieds-plugin') . '</a>'
            . '</div>';

        $parents = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($parents) || empty($parents)) {
            $out .= '<div class="mc-home-muted">' . esc_html__('No categories available.', 'metra-classifieds-plugin') . '</div>';
            $out .= '</aside>';
            return $out;
        }

        $child_limit = 2;

        foreach ($parents as $parent) {
            $parent_link = self::page_url('category_listings', ['cat' => (string) $parent->term_id]);

            $out .= '<div class="mc-home-cat-group">';
            $parent_count = self::get_effective_public_listing_count_for_term((int) $parent->term_id);
            $parent_count_html = $parent_count > 0 ? ' <span class="mc-home-cat-parent__count">(' . (int) $parent_count . ')</span>' : '';

            $out .= '<a class="mc-home-cat-parent" href="' . esc_url($parent_link) . '">';
            $parent_icon = MC_Icons::render_fa_category_icon($parent, ['class' => 'mc-maincat-fa-icon']);
            if ($parent_icon !== '') {
                $out .= '<span class="mc-home-cat-parent__icon" aria-hidden="true">' . $parent_icon . '</span>';
            }
            $out .= '<span class="mc-home-cat-parent__label">' . esc_html($parent->name) . $parent_count_html . '</span>';
            $out .= '</a>';

            $children = get_terms([
                'taxonomy'   => MC_CPT::TAX,
                'hide_empty' => false,
                'parent'     => (int) $parent->term_id,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (!is_wp_error($children) && !empty($children)) {
                $out .= '<div class="mc-home-cat-children">';

                $shown = 0;
                foreach ($children as $child) {
                    if ($shown >= $child_limit) {
                        break;
                    }
                    $child_link = self::page_url('category_listings', ['cat' => (string) $child->term_id]);
                    $child_count = self::get_effective_public_listing_count_for_term((int) $child->term_id);
                    $child_count_html = $child_count > 0 ? ' (' . (int) $child_count . ')' : '';
                    $out .= '<a class="mc-home-cat-child" href="' . esc_url($child_link) . '">' . esc_html($child->name . $child_count_html) . '</a>';
                    $shown++;
                }

                if (count($children) > $child_limit) {
                    // Keep the user inside category navigation instead of jumping to listings.
                    // Use the same direct child list as the category tree/right-side navigation,
                    // so both sidebars stay consistent.
                    $out .= '<a class="mc-home-cat-more" href="' . esc_url(self::category_tree_url((int) $parent->term_id)) . '">' . esc_html__('More …', 'metra-classifieds-plugin') . '</a>';
                }

                $out .= '</div>';
            }

            $out .= '</div>';
        }

        $out .= '</aside>';
        return $out;
    }

/**
 * Mobile Home – Top Navigation (Step 2b)
 * - Uses dummy icons (CSS) for now; real icons can be mapped later.
 * - The dropdown button (▼) is present but becomes functional in Step 2b/2c.
 */
private static function build_mobile_home_topnav(): string {
    $parents = get_terms([
        'taxonomy'   => MC_CPT::TAX,
        'hide_empty' => false,
        'parent'     => 0,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    if (is_wp_error($parents) || empty($parents)) {
        return '';
    }

    $out  = '<div class="mc-mobile-topnav" aria-label="' . esc_attr__('Categories', 'metra-classifieds-plugin') . '">';

    foreach ($parents as $parent) {
        $link = self::page_url('category_listings', ['cat' => (string) $parent->term_id]);
        $out .= '<a class="mc-mobile-topcat" href="' . esc_url($link) . '">';
        $mobile_icon = MC_Icons::render_fa_category_icon($parent, ['class' => 'mc-mobile-topcat-fa-icon', 'size' => 16]);
        if ($mobile_icon !== '') {
            $out .= '<span class="mc-mobile-topcat-icon" aria-hidden="true">' . $mobile_icon . '</span>';
        }
        $out .= '<span class="mc-mobile-topcat-label">' . esc_html($parent->name) . '</span>';
        $out .= '</a>';
    }


    // Dropdown button placeholder; overlay list comes in the next step.
    $out .= '<button class="mc-mobile-topnav-dropdown" type="button" aria-label="' . esc_attr__('All categories', 'metra-classifieds-plugin') . '">▾</button>';
    $out .= '</div>';

    return $out;
}

    /**
     * View a single listing on a page.
     * Usage: [mc_listing] with ?listing_id=123
     */
    public static function listing_view(array $atts = []): string {
        $listing_id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        if ($listing_id <= 0) {
            return '<div class="mc-listing mc-empty">' . esc_html__('Listing not found.', 'metra-classifieds-plugin') . '</div>';
        }

        $p = get_post($listing_id);
        if (!$p || $p->post_type !== MC_CPT::CPT || $p->post_status === 'trash') {
            return '<div class="mc-listing mc-empty">' . esc_html__('Listing not found.', 'metra-classifieds-plugin') . '</div>';
        }

        $is_paused = self::is_listing_paused($listing_id);
        if (($p->post_status !== 'publish' || $is_paused) && (!is_user_logged_in() || (int) $p->post_author !== get_current_user_id())) {
            return '<div class="mc-listing mc-empty">' . esc_html__('Listing not available.', 'metra-classifieds-plugin') . '</div>';
        }

        $terms = get_the_terms($p, MC_CPT::TAX);
        $cat_names = [];
        if (is_array($terms)) {
            foreach ($terms as $t) {
                $cat_names[] = $t->name;
            }
        }

        $out = '<article class="mc-listing" style="max-width:760px;padding:14px 16px;border:1px solid #e5e5e5;border-radius:10px;">';
        $out .= '<h2 style="margin:0 0 8px 0;">' . esc_html(get_the_title($p)) . '</h2>';
        if (!empty($cat_names)) {
            $out .= '<div style="font-size:14px;opacity:.9;margin-bottom:8px;">'
                . esc_html__('Category:', 'metra-classifieds-plugin') . ' ' . esc_html(implode(', ', $cat_names))
                . '</div>';
        }
        $out .= '<div class="mc-content">' . self::render_public_description((string) $p->post_content) . '</div>';

        // Owner actions.
        if (is_user_logged_in() && (int) $p->post_author === get_current_user_id()) {
            $edit_url = self::page_url('edit', ['listing_id' => (string) $listing_id]);
            $out .= '<div style="margin-top:12px;">'
                . '<a href="' . esc_url($edit_url) . '">' . esc_html__('Edit listing', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
        }

        $out .= '</article>';
        return $out;
    }

    /**
     * Logged-in user's listings.
     */
    public static function my_listings(array $atts = []): string {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(MC_Pages::get_page_url('my_listings') ?: home_url('/'));
            return '<div class="mc-my-listings mc-not-logged-in">'
                . esc_html__('Please log in to see your listings.', 'metra-classifieds-plugin')
                . ' <a href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
        }

        $user_id = get_current_user_id();

        $notice = '';
        if (isset($_GET['mc_deleted'])) {
            $flag = (string) $_GET['mc_deleted'];
            if ($flag === 'ok') {
                $notice = '<div class="mc-notice mc-notice-success" style="padding:10px 12px;border:1px solid #b7e4c7;background:#d8f3dc;border-radius:10px;">'
                    . esc_html__('Listing permanently deleted.', 'metra-classifieds-plugin')
                    . '</div>';
            } elseif ($flag === 'forbidden') {
                $notice = '<div class="mc-notice mc-notice-error" style="padding:10px 12px;border:1px solid #ffb4a2;background:#ffe5d9;border-radius:10px;">'
                    . esc_html__('You are not allowed to delete this listing.', 'metra-classifieds-plugin')
                    . '</div>';
            } elseif ($flag === 'nonce') {
                $notice = '<div class="mc-notice mc-notice-error" style="padding:10px 12px;border:1px solid #ffb4a2;background:#ffe5d9;border-radius:10px;">'
                    . esc_html__('Security check failed. Please try again.', 'metra-classifieds-plugin')
                    . '</div>';
            } else {
                $notice = '<div class="mc-notice mc-notice-error" style="padding:10px 12px;border:1px solid #ffb4a2;background:#ffe5d9;border-radius:10px;">'
                    . esc_html__('Could not delete listing.', 'metra-classifieds-plugin')
                    . '</div>';
            }
        }

        if (isset($_GET['mc_toggle_pause'], $_GET['listing_id'])) {
            $toggle_listing_id = (int) $_GET['listing_id'];
            $toggle_nonce = isset($_GET['_mc_nonce']) ? (string) $_GET['_mc_nonce'] : '';

            if ($toggle_listing_id > 0 && wp_verify_nonce($toggle_nonce, 'mc_toggle_pause_' . $toggle_listing_id)) {
                $toggle_post = get_post($toggle_listing_id);
                if ($toggle_post instanceof \WP_Post && (int) $toggle_post->post_author === $user_id) {
                    if (self::is_listing_paused($toggle_listing_id)) {
                        delete_post_meta($toggle_listing_id, self::PAUSE_META_KEY);
                    } else {
                        update_post_meta($toggle_listing_id, self::PAUSE_META_KEY, '1');
                    }
                    wp_safe_redirect(remove_query_arg(['mc_toggle_pause', 'listing_id', '_mc_nonce']));
                    exit;
                }
            }
        }

        $q = new \WP_Query([
            'post_type'      => MC_CPT::CPT,
            'post_status'    => ['publish', 'pending', 'draft'],
            'author'         => $user_id,
            'posts_per_page' => 50,
        ]);

        if (!$q->have_posts()) {
            return '<div class="mc-my-listings mc-empty">' . esc_html__('You have no listings yet.', 'metra-classifieds-plugin') . '</div>';
        }

        $out = '<div class="mc-my-listings" style="display:grid;gap:12px;">' . $notice;
        while ($q->have_posts()) {
            $q->the_post();
            $listing_id = (int) get_the_ID();
            $view_url = self::page_url('view', ['listing_id' => (string) $listing_id]);
            $edit_url = self::page_url('edit', ['listing_id' => (string) $listing_id]);
            $status_toggle = self::render_status_toggle_link($listing_id);

            $out .= '<article style="padding:14px 16px;border:1px solid #e5e5e5;border-radius:10px;">'
                . '<h3 style="margin:0 0 6px 0;">' . esc_html(get_the_title()) . '</h3>'
                . '<div style="margin-top:6px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">'
                . '<a href="' . esc_url($view_url) . '">' . esc_html__('View', 'metra-classifieds-plugin') . '</a>'
                . '<a href="' . esc_url($edit_url) . '">' . esc_html__('Edit', 'metra-classifieds-plugin') . '</a>'
                . self::render_delete_link($listing_id)
                . '<span class="mc-status-toggle-sep"> - </span>'
                . $status_toggle
                . '</div>'
                . '</article>';
        }
        wp_reset_postdata();
        $out .= '</div>';

        return $out;
    }

    /**
     * Edit listing page.
     * Usage: [mc_edit_listing] with ?listing_id=123
     */
    public static function edit_listing(array $atts = []): string {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(self::page_url('edit'));
            return '<div class="mc-edit-listing mc-not-logged-in">'
                . esc_html__('Please log in to edit your listing.', 'metra-classifieds-plugin')
                . ' <a href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
        }

        $listing_id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        if ($listing_id <= 0) {
            return '<div class="mc-edit-listing mc-empty">' . esc_html__('No listing selected.', 'metra-classifieds-plugin') . '</div>';
        }

        $p = get_post($listing_id);
        $is_admin = current_user_can('manage_options');
        if (!$p || $p->post_type !== MC_CPT::CPT || ((int) $p->post_author !== get_current_user_id() && !$is_admin)) {
            return '<div class="mc-edit-listing mc-empty">' . esc_html__('You are not allowed to edit this listing.', 'metra-classifieds-plugin') . '</div>';
        }

        $errors = [];
        $updated = false;

        if (isset($_POST['mc_edit_form']) && is_array($_POST['mc_edit_form'])) {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], 'mc_edit_listing_' . $listing_id)) {
                $errors[] = __('Security check failed.', 'metra-classifieds-plugin');
            } else {
                $data = (array) $_POST['mc_edit_form'];
                $title = sanitize_text_field((string) ($data['title'] ?? ''));
                // === MC: Title max chars validation v0.0705 START ===
                $mc_title_max_edit = (int) get_option('mc_title_max_chars', 35);
                $mc_title_max_edit = max(1, min(200, $mc_title_max_edit));
                $title = mb_substr($title, 0, $mc_title_max_edit);
                // === MC: Title max chars validation v0.0705 END ===
                $desc  = self::sanitize_public_description((string) ($data['description'] ?? ''));
                $cat   = (int) ($data['category'] ?? 0);
                $status = sanitize_key((string) ($data['status'] ?? $p->post_status));

                if ($title === '') {
                    $errors[] = __('Please enter a title.', 'metra-classifieds-plugin');
                }

                if ($desc === '') {
                    $errors[] = __('Please enter a description.', 'metra-classifieds-plugin');
                }

                // Disallow selecting non-leaf category terms (category groups)
                if ($cat > 0 && !self::is_leaf_category_term($cat)) {
                    $errors[] = __('Please select a subcategory (not a category group).', 'metra-classifieds-plugin');
                }

                $allowed_status = ['publish', 'pending', 'draft'];
                if (!in_array($status, $allowed_status, true)) {
                    $status = (string) $p->post_status;
                }

                if (empty($errors)) {
                    $res = wp_update_post([
                        'ID'           => $listing_id,
                        'post_title'   => $title,
                        'post_content' => $desc,
                        'post_status'  => $status,
                    ], true);

                    if (is_wp_error($res)) {
                        $errors[] = __('Could not update the listing. Please try again.', 'metra-classifieds-plugin');
                    } else {
                        if ($cat > 0) {
                            wp_set_object_terms($listing_id, [$cat], MC_CPT::TAX, false);
                        } else {
                            wp_set_object_terms($listing_id, [], MC_CPT::TAX, false);
                        }
                        $updated = true;
                        $p = get_post($listing_id); // refresh
                    }
                }
            }
        }

        $title_val = isset($_POST['mc_edit_form']['title']) ? sanitize_text_field((string) $_POST['mc_edit_form']['title']) : (string) $p->post_title;
        $desc_val  = isset($_POST['mc_edit_form']['description']) ? (string) $_POST['mc_edit_form']['description'] : (string) $p->post_content;
        $status_val = isset($_POST['mc_edit_form']['status']) ? sanitize_key((string) $_POST['mc_edit_form']['status']) : (string) $p->post_status;

        $term_ids = wp_get_object_terms($listing_id, MC_CPT::TAX, ['fields' => 'ids']);
        $cat_val = (is_array($term_ids) && !empty($term_ids)) ? (int) $term_ids[0] : 0;

        $terms = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
        ]);

        $view_url = self::page_url('view', ['listing_id' => (string) $listing_id]);

        $out = '<div class="mc-edit-listing" style="max-width:760px;">';
        $out .= '<h2 style="margin:0 0 10px 0;">' . esc_html__('Edit listing', 'metra-classifieds-plugin') . '</h2>';

        if ($updated) {
            $out .= '<div class="mc-notice mc-notice-success" style="padding:12px;border:1px solid #cfe7cf;border-radius:10px;margin-bottom:12px;">'
                . esc_html__('Listing updated. Redirecting…', 'metra-classifieds-plugin')
                . ' <a href="' . esc_url($view_url) . '">' . esc_html__('View listing', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
            $out .= '<script>(function(){try{window.location.href=' . wp_json_encode($view_url) . ';}catch(e){}})();</script>';
        }

        if (!empty($errors)) {
            $out .= '<div class="notice notice-error" style="padding:12px;border:1px solid #f2c2c2;border-radius:10px;margin-bottom:12px;">'
                . '<ul style="margin:0 0 0 18px;">';
            foreach ($errors as $e) {
                $out .= '<li>' . esc_html((string) $e) . '</li>';
            }
            $out .= '</ul></div>';
        }

        $out .= '<div id="mc-edit-wait-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;">'    . '<div style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>'    . '<div role="dialog" aria-modal="true" style="position:relative;max-width:520px;width:92%;background:#fff;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.25);padding:18px 18px 16px;">'    . '<div style="font-weight:800;font-size:18px;margin:0 0 6px 0;">' . esc_html__("Please wait", "metra-classifieds-plugin") . '</div>'    . '<div style="font-size:14px;opacity:.9;">' . esc_html__("Your listing is being updated.", "metra-classifieds-plugin") . '</div>'    . '<div style="margin-top:14px;height:10px;border-radius:999px;background:#f0f0f0;overflow:hidden;">'    . '<div style="height:100%;width:45%;background:#111;animation:mcBar 1.1s ease-in-out infinite;"></div>'    . '</div>'    . '</div>'    . '</div>'    . '<style>@keyframes mcBar{0%{transform:translateX(-120%);}100%{transform:translateX(260%);}}</style>';
$out .= '<form id="mc-edit-page-form" method="post" enctype="multipart/form-data">';
        $out .= wp_nonce_field('mc_edit_listing_' . $listing_id, '_wpnonce', true, false);

        // === MC: Title max chars edit input v0.0708 START ===
        $mc_title_max_edit_chars = (int) get_option('mc_title_max_chars', 35);
        $mc_title_max_edit_chars = max(1, min(200, $mc_title_max_edit_chars));
        $out .= self::render_title_guard_script();
        $out .= '<p><label><strong>' . esc_html__('Title', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<input type="text" id="mc-title-input-edit" name="mc_edit_form[title]" value="' . esc_attr($title_val) . '" maxlength="' . esc_attr((string) $mc_title_max_edit_chars) . '" placeholder="' . esc_attr('Max. ' . $mc_title_max_edit_chars) . '" style="width:100%;max-width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;" autocomplete="off" required>'
            . '<span id="mc-title-counter-edit" style="display:block;font-size:12px;margin-top:4px;color:#666;"></span>'
            . '</p>';
        $out .= '<script>mcTitleGuard("mc-title-input-edit","mc-title-counter-edit",' . (int)$mc_title_max_edit_chars . ');</script>';
        // === MC: Title max chars edit input v0.0708 END ===

        // Location is sourced from the profile only and is not editable in the listing form.

        $out .= '<p><label><strong>' . esc_html__('Category', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<select name="mc_edit_form[category]" style="width:100%;max-width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;">'
            . '<option value="0">' . esc_html__('— Select —', 'metra-classifieds-plugin') . '</option>';
        if (!is_wp_error($terms) && !empty($terms)) {
            $out .= self::render_category_tree_options($terms, $cat_val);
        }
        $out .= '</select></p>';

        $out .= '<div><label><strong>' . esc_html__('Description', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<textarea name="mc_edit_form[description]" rows="8" style="width:100%;max-width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;" required>'
            . esc_textarea($desc_val) . '</textarea>'
            . '<p class="description mc-field-hint" style="margin:6px 0 18px 0;max-width:100%;">' . esc_html__('Tip: Use **word** for bold text. Links, email addresses and websites are not allowed.', 'metra-classifieds-plugin') . '</p></div>';

        $out .= '<p><label><strong>' . esc_html__('Status', 'metra-classifieds-plugin') . '</strong></label><br />'
            . '<select name="mc_edit_form[status]" style="width:100%;max-width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;">'
            . '<option value="publish"' . selected($status_val, 'publish', false) . '>' . esc_html__('Published', 'metra-classifieds-plugin') . '</option>'
            . '<option value="pending"' . selected($status_val, 'pending', false) . '>' . esc_html__('Pending', 'metra-classifieds-plugin') . '</option>'
            . '<option value="draft"' . selected($status_val, 'draft', false) . '>' . esc_html__('Draft', 'metra-classifieds-plugin') . '</option>'
            . '</select></p>';

        $out .= '<p style="display:flex;gap:10px;flex-wrap:wrap;">'
            . '<button id=\"mc-edit-page-submit\" type=\"submit\" style="padding:10px 16px;border-radius:10px;border:1px solid #111;background:#111;color:#fff;cursor:pointer;">'
            . esc_html__('Save changes', 'metra-classifieds-plugin') . '</button>'
            . '<a href="' . esc_url($view_url) . '">' . esc_html__('Cancel', 'metra-classifieds-plugin') . '</a>'
            . '</p>';

        $out .= '</form>';
        $out .= '</div>';

        return $out;
    }


    // =========================================================
    // COMPONENT: DYNAMIC CATEGORY PAGE TITLE FILTER
    // Version: 0.0477
    // Purpose:
    // - hide only the WordPress page title on the mc-category page
    // - never affect listing card titles rendered inside the same request
    // Rollback:
    // - restore the previous maybe_filter_dynamic_page_title() method
    // =========================================================
    public static function maybe_filter_dynamic_page_title(string $title, int $post_id): string {
        if (is_admin()) {
            return $title;
        }

        if (!function_exists('is_page') || !function_exists('get_queried_object_id')) {
            return $title;
        }

        if (!is_page('mc-category')) {
            return $title;
        }

        $queried_object_id = (int) get_queried_object_id();
        if ($queried_object_id <= 0 || $post_id !== $queried_object_id) {
            return $title;
        }

        if (!in_the_loop()) {
            return $title;
        }

        return '';
    }

    /**
     * Listings filtered by category via query param.
     * Usage: [mc_category_listings] with ?cat=TERM_ID
     *
     * Architecture step 1:
     * The category page now reuses the normal listings renderer instead of
     * maintaining a second listing loop/markup variant.
     */
    public static function category_listings(array $atts = []): string {
        $term_id = isset($_GET['cat']) ? (int) $_GET['cat'] : 0;
        if ($term_id <= 0) {
            return '<div class="mc-category-listings mc-empty">' . esc_html__('No category selected.', 'metra-classifieds-plugin') . '</div>';
        }

        $term = get_term($term_id, MC_CPT::TAX);
        if (!$term || is_wp_error($term)) {
            return '<div class="mc-category-listings mc-empty">' . esc_html__('Category not found.', 'metra-classifieds-plugin') . '</div>';
        }

        $heading = sprintf(
            /* translators: %s = category name */
            __('Category: %s', 'metra-classifieds-plugin'),
            (string) $term->name
        );

        return self::listings([
            'per_page'       => '25',
            'category_id'    => (string) $term_id,
            'force_layout'   => '1',
            'custom_heading' => $heading,
        ]);
    }

    /**
     * Render the small AJAX heart toggle used in listing grids.
     *
     * Placement requirement from the project brief:
     * - directly after the category/date meta row
     * - 15px visual height
     * - green heart using the plugin asset favorite-heart.png
     *
     * The control is shown only to logged-in users, because favorites are stored
     * in user_meta and therefore require an authenticated account context.
     */
    public static function render_favorite_heart_toggle(int $listing_id, string $modifier_class = ''): string {
        if ($listing_id <= 0) {
            return '';
        }

        if (!is_user_logged_in()) {
            $class = 'mc-favorite-heart-toggle';
            if ($modifier_class !== '') {
                $class .= ' ' . trim($modifier_class);
            }

            $outline_icon = MC_Icons::render('heart-outline', ['class' => 'mc-icon mc-icon-heart-outline', 'width' => 14, 'height' => 14]);

            $guest_target = self::get_guest_action_required_url('favorite', $listing_id);

            return '<a href="' . esc_url($guest_target) . '" class="' . esc_attr($class) . '" aria-pressed="false" aria-label="' . esc_attr__('Set as Favorite', 'metra-classifieds-plugin') . '" title="' . esc_attr__('Set as Favorite', 'metra-classifieds-plugin') . '">'
                . '<span class="mc-favorite-heart-toggle__icon" aria-hidden="true">'
                . $outline_icon
                . '</span>'
                . '</a>';
        }

        $user_id = (int) get_current_user_id();
        $is_favorite = MC_Favorites::is_favorite($user_id, $listing_id);
        $label = $is_favorite
            ? __('Favorite', 'metra-classifieds-plugin')
            : __('Set as Favorite', 'metra-classifieds-plugin');

        $class = 'mc-favorite-heart-toggle';
        if ($modifier_class !== '') {
            $class .= ' ' . trim($modifier_class);
        }
        if ($is_favorite) {
            $class .= ' is-favorite';
        }

        $outline_icon = MC_Icons::render('heart-outline', ['class' => 'mc-icon mc-icon-heart-outline', 'width' => 14, 'height' => 14]);
        $filled_icon  = MC_Icons::render('heart-filled', ['class' => 'mc-icon mc-icon-heart-filled', 'width' => 14, 'height' => 14]);
        $active_icon  = $is_favorite ? $filled_icon : $outline_icon;

        return '<button type="button" class="' . esc_attr($class) . '" data-listing-id="' . (int) $listing_id . '" data-label-default="' . esc_attr__('Set as Favorite', 'metra-classifieds-plugin') . '" data-label-active="' . esc_attr__('Favorite', 'metra-classifieds-plugin') . '" data-icon-outline="' . esc_attr($outline_icon) . '" data-icon-filled="' . esc_attr($filled_icon) . '" aria-pressed="' . ($is_favorite ? 'true' : 'false') . '" aria-label="' . esc_attr($label) . '" title="' . esc_attr($label) . '">'
            . '<span class="mc-favorite-heart-toggle__icon" aria-hidden="true">'
            . $active_icon
            . '</span>'
            . '</button>';
    }


    /**
     * Render a guest-only action button that leads to the dedicated METRA action-required page.
     */
    public static function render_guest_action_button(string $label, string $class = 'button', string $extra_attributes = '', string $action = 'restricted', int $listing_id = 0): string {
        $attributes = trim($extra_attributes);
        if ($attributes !== '') {
            $attributes = ' ' . $attributes;
        }

        $target = self::get_guest_action_required_url($action, $listing_id);

        return '<a href="' . esc_url($target) . '" class="' . esc_attr($class) . '"' . $attributes . '>'
            . esc_html($label)
            . '</a>';
    }

    /**
     * Render the shared guest login/register requirement modal once per request.
     */
    public static function render_guest_action_modal(): string {
        static $rendered = false;

        if ($rendered) {
            return '';
        }
        $rendered = true;

        $login_url = MC_Pages::get_page_url('login') ?: home_url('/mc-login/');
        $register_url = MC_Pages::get_page_url('register') ?: home_url('/mc-register/');

        return '<div id="mc-login-required-modal" class="mc-modal mc-guest-action-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;">'
            . '<div class="mc-modal-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>'
            . '<div class="mc-modal-panel" role="dialog" aria-modal="true" style="position:relative;width:min(420px,75vw);margin:12vh auto 0 auto;background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.22);padding:16px 16px;">'
            . '<div style="font-size:16px;font-weight:700;margin-bottom:10px;">' . esc_html__('Login required', 'metra-classifieds-plugin') . '</div>'
            . '<div style="font-size:14px;opacity:.9;margin-bottom:14px;line-height:1.45;">' . esc_html__('This action is only available after login or registration.', 'metra-classifieds-plugin') . '</div>'
            . '<div style="display:flex;justify-content:center;gap:18px;flex-wrap:wrap;margin-top:10px;">'
            . '<a href="' . esc_url($login_url) . '">' . esc_html__('Login', 'metra-classifieds-plugin') . '</a>'
            . '<a href="' . esc_url($register_url) . '">' . esc_html__('Register', 'metra-classifieds-plugin') . '</a>'
            . '</div>'
            . '<div style="display:flex;justify-content:center;margin-top:16px;">'
            . '<button type="button" class="button mc-modal-cancel">' . esc_html__('Close', 'metra-classifieds-plugin') . '</button>'
            . '</div>'
            . '</div>'
            . '</div>'
            . '<script>(function(){if(window.__mcGuestActionModalInit){return;}window.__mcGuestActionModalInit=true;function mcOpenModal(modal){if(!modal){return;}modal.style.display="block";modal.setAttribute("aria-hidden","false");var panel=modal.querySelector(".mc-modal-panel");if(panel){panel.setAttribute("tabindex","-1");panel.focus();}}function mcCloseModal(modal){if(!modal){return;}modal.style.display="none";modal.setAttribute("aria-hidden","true");}document.addEventListener("click",function(e){var trigger=e.target&&e.target.closest?e.target.closest("[data-mc-guest-modal]"):null;if(trigger){e.preventDefault();var modal=document.getElementById(trigger.getAttribute("data-mc-guest-modal"));if(modal){mcOpenModal(modal);}return;}var modal=e.target&&e.target.closest?e.target.closest(".mc-guest-action-modal"):null;if(!modal){return;}if((e.target.classList&&e.target.classList.contains("mc-modal-backdrop"))||(e.target.classList&&e.target.classList.contains("mc-modal-cancel"))){e.preventDefault();mcCloseModal(modal);}},true);document.addEventListener("keydown",function(e){if(e.key!=="Escape"){return;}var open=document.querySelector(".mc-guest-action-modal[aria-hidden="false"]");if(open){mcCloseModal(open);}});})();</script>';
    }


    /* =========================================================
     * METRA: Categories list navigation helpers
     * Version: 0.0463
     * Purpose:
     * - keep category list navigation logic in one compact block
     * - route empty leaf categories to the focused tree page instead of listings
     * - render the Go back link above the focused category heading
     *
     * Rollback:
     * - remove this helper block and restore the previous categories list link logic
     * ========================================================= */
    private static function categories_list_term_link(int $term_id): string {
        $children = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => $term_id,
            'fields'     => 'ids',
        ]);

        if (!is_wp_error($children) && !empty($children)) {
            return self::category_tree_url($term_id);
        }

        $listing_count = self::get_effective_public_listing_count_for_term($term_id);
        if ($listing_count <= 0) {
            return self::category_tree_url($term_id);
        }

        return self::page_url('category_listings', ['cat' => (string) $term_id]);
    }

    private static function categories_list_parent_link_html(\WP_Term $term): string {
        $parent_id = isset($term->parent) ? (int) $term->parent : 0;
        $label = esc_html__('Go back', 'metra-classifieds-plugin');
        $target_url = '';

        if ($parent_id > 0) {
            $parent_term = get_term($parent_id, MC_CPT::TAX);
            if ($parent_term instanceof \WP_Term) {
                $target_url = self::category_tree_url((int) $parent_term->term_id);
            }
        }

        if ($target_url === '') {
            $target_url = MC_Pages::get_page_url('categories_list');
            if ($target_url === '') {
                $target_url = self::page_url('listings');
            }
        }

        return '<div class="mc-categories-list-back">'
            . '<a class="mc-categories-list-back__link" href="' . esc_url($target_url) . '">'
            . '<span class="mc-categories-list-back__icon" aria-hidden="true">&#8592;</span>'
            . '<span class="mc-categories-list-back__label">' . $label . '</span>'
            . '</a>'
            . '</div>';
    }

    private static function categories_list_empty_message_html(\WP_Term $term, string $children_html): string {
        if ($children_html !== '') {
            return '';
        }

        $count = self::get_effective_public_listing_count_for_term((int) $term->term_id);
        if ($count > 0) {
            return '';
        }

        return '<div class="mc-categories-list-empty-note">' . esc_html__('No listings in this category', 'metra-classifieds-plugin') . '</div>';
    }


    /**
     * Count direct published, non-paused listings assigned to one category term only.
     * Used as the leaf fallback when a category has no children.
     */
    private static function get_direct_public_listing_count_for_term(int $term_id): int {
        if ($term_id <= 0) {
            return 0;
        }

        $q = new \WP_Query([
            'post_type'      => MC_CPT::CPT,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'tax_query'      => [[
                'taxonomy'         => MC_CPT::TAX,
                'field'            => 'term_id',
                'terms'            => [$term_id],
                'include_children' => false,
            ]],
            'meta_query'     => [[
                'relation' => 'OR',
                [
                    'key'     => self::PAUSE_META_KEY,
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => self::PAUSE_META_KEY,
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ]],
        ]);

        $count = (int) $q->found_posts;
        wp_reset_postdata();

        return $count;
    }

    /**
     * Recursive public listing counter for category displays.
     *
     * Rule set:
     * - category with children => count is the sum of all descendants
     * - category without children => count is the direct public listing count
     *
     * This intentionally ignores direct listings on parent categories that already
     * have children, because those parent categories are navigational groups.
     */
    private static function get_effective_public_listing_count_for_term(int $term_id): int {
        static $cache = [];

        if ($term_id <= 0) {
            return 0;
        }

        if (isset($cache[$term_id])) {
            return (int) $cache[$term_id];
        }

        $children = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => $term_id,
            'fields'     => 'ids',
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($children)) {
            $cache[$term_id] = 0;
            return 0;
        }

        if (empty($children)) {
            $cache[$term_id] = self::get_direct_public_listing_count_for_term($term_id);
            return (int) $cache[$term_id];
        }

        $sum = 0;
        foreach ($children as $child_id) {
            $sum += self::get_effective_public_listing_count_for_term((int) $child_id);
        }

        $cache[$term_id] = $sum;
        return $sum;
    }

    private static function categories_list_term_matches(\WP_Term $term, string $needle): bool {
        if ($needle === '') {
            return true;
        }

        $name = function_exists('mb_strtolower') ? mb_strtolower((string) $term->name) : strtolower((string) $term->name);
        if (str_contains($name, $needle)) {
            return true;
        }

        $children = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => (int) $term->term_id,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($children) || empty($children)) {
            return false;
        }

        foreach ($children as $child) {
            if ($child instanceof \WP_Term && self::categories_list_term_matches($child, $needle)) {
                return true;
            }
        }

        return false;
    }

    private static function categories_list_render_children(int $parent_id, string $needle = ''): string {
        $terms = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => $parent_id,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            return '';
        }

        $items = '';
        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) {
                continue;
            }
            if ($needle !== '' && !self::categories_list_term_matches($term, $needle)) {
                continue;
            }

            $count = self::get_effective_public_listing_count_for_term((int) $term->term_id);
            $count_html = $count > 0 ? ' <span class="mc-categories-list-count">(' . (int) $count . ')</span>' : '';
            $items .= '<li class="mc-categories-list-item">';
            $items .= '<a href="' . esc_url(self::categories_list_term_link((int) $term->term_id)) . '">' . esc_html((string) $term->name) . '</a>' . $count_html;
            $items .= '</li>';
        }

        if ($items === '') {
            return '';
        }

        return '<ul class="mc-categories-list-sublist">' . $items . '</ul>';
    }

    public static function categories_list_page(array $atts = []): string {
        $query_raw = isset($_GET['q']) ? sanitize_text_field((string) $_GET['q']) : '';
        $query = trim($query_raw);
        $needle = $query !== ''
            ? (function_exists('mb_strtolower') ? mb_strtolower($query) : strtolower($query))
            : '';
        $focus_cat = isset($_GET['focus_cat']) ? absint((string) $_GET['focus_cat']) : 0;

        $out  = '<div class="mc-categories-list-page">';
        $out .= '<div class="mc-categories-list-searchbar">';
        $out .= '<form method="get" action="">';
        if ($focus_cat > 0) {
            $out .= '<input type="hidden" name="focus_cat" value="' . esc_attr((string) $focus_cat) . '">';
        }
        $out .= '<div class="mc-categories-list-searchrow">';
        $out .= '<input type="search" name="q" value="' . esc_attr($query) . '" placeholder="' . esc_attr__('Search categories', 'metra-classifieds-plugin') . '" />';
        $out .= '<button type="submit" class="button">' . esc_html__('Search', 'metra-classifieds-plugin') . '</button>';
        $out .= '</div>';
        $out .= '</form>';
        $out .= '</div>';

        $groups = '';

        if ($focus_cat > 0) {
            $focus_term = get_term($focus_cat, MC_CPT::TAX);
            if ($focus_term instanceof \WP_Term) {
                if ($needle === '' || self::categories_list_term_matches($focus_term, $needle)) {
                    $count = self::get_effective_public_listing_count_for_term((int) $focus_term->term_id);
                    $count_html = $count > 0 ? ' <span class="mc-categories-list-count">(' . (int) $count . ')</span>' : '';
                    $children_html = self::categories_list_render_children((int) $focus_term->term_id, $needle);
                    $back_link_html = self::categories_list_parent_link_html($focus_term);
                    $empty_note_html = self::categories_list_empty_message_html($focus_term, $children_html);

                    $groups .= '<section class="mc-categories-list-group mc-categories-list-group--focused">';
                    $groups .= $back_link_html;
                    $groups .= '<h3 class="mc-categories-list-heading"><a href="' . esc_url(self::categories_list_term_link((int) $focus_term->term_id)) . '">' . esc_html((string) $focus_term->name) . '</a>' . $count_html . '</h3>';
                    $groups .= $children_html;
                    $groups .= $empty_note_html;
                    $groups .= '</section>';
                }
            }
        } else {
            $top_terms = get_terms([
                'taxonomy'   => MC_CPT::TAX,
                'hide_empty' => false,
                'parent'     => 0,
                'orderby'    => 'name',
                'order'      => 'ASC',
            ]);

            if (is_wp_error($top_terms) || empty($top_terms)) {
                $out .= '<div class="mc-empty">' . esc_html__('No categories found.', 'metra-classifieds-plugin') . '</div>';
                $out .= '</div>';
                return $out;
            }

            foreach ($top_terms as $term) {
                if (!$term instanceof \WP_Term) {
                    continue;
                }
                if ($needle !== '' && !self::categories_list_term_matches($term, $needle)) {
                    continue;
                }

                $count = self::get_effective_public_listing_count_for_term((int) $term->term_id);
                $count_html = $count > 0 ? ' <span class="mc-categories-list-count">(' . (int) $count . ')</span>' : '';
                $children_html = self::categories_list_render_children((int) $term->term_id, $needle);

                $groups .= '<section class="mc-categories-list-group">';
                $groups .= '<h3 class="mc-categories-list-heading"><a href="' . esc_url(self::categories_list_term_link((int) $term->term_id)) . '">' . esc_html((string) $term->name) . '</a>' . $count_html . '</h3>';
                $groups .= $children_html;
                $groups .= '</section>';
            }
        }

        if ($groups === '') {
            $out .= '<div class="mc-empty">' . esc_html__('No matching categories found.', 'metra-classifieds-plugin') . '</div>';
            $out .= '</div>';
            return $out;
        }

        $out .= '<div class="mc-categories-list-grid">' . $groups . '</div>';
        $out .= '</div>';
        return $out;
    }

    /**
     * Minimal categories grid.
     * [mc_categories_grid parent="0" columns="3" show_count="1"]
     */
    public static function categories_grid(array $atts = []): string {
        /**
         * [mc_categories_grid]
         * Listing: Wizard-Design (mc-catwiz-*) wie "Post a listing" Categoryauswahl.
         * Verhalten:
         * - Non-leaf -> drilldown (shows next level, no listings)
         * - Leaf -> Nextleitung zur Listingsliste (mc_category_listings)
         *
         * Important: No impact on listings grid (mobile 2 columns unchanged).
         */
        $atts = shortcode_atts([
            'parent'     => isset($_GET['parent']) ? (string) $_GET['parent'] : '0',
            'show_count' => '0',
            'title'      => __('Categories', 'metra-classifieds-plugin'),
            'section'    => __('All categories', 'metra-classifieds-plugin'),
            '_embedded'  => '0',
        ], $atts, 'mc_categories_grid');
        $is_standalone_page = (string) $atts['_embedded'] !== '1';

        $parent     = absint((string) $atts['parent']);
        $show_count = ((string) $atts['show_count'] === '1');

        $terms = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => $parent,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        $title   = (string) $atts['title'];
        $section = (string) $atts['section'];

        // Back-Link (nur wenn parent > 0)
        $back_html = '';
        if ($parent > 0) {
            $parent_term = get_term($parent, MC_CPT::TAX);
            if ($parent_term && !is_wp_error($parent_term)) {
                $back_target = 0;
                if ((int) $parent_term->parent > 0) {
                    $back_target = (int) $parent_term->parent;
                }
                $back_url = add_query_arg(['parent' => (string) $back_target], self::page_url('categories') ?: home_url('/'));
                $back_html .= '<a class="mc-catwiz-back" href="' . esc_url($back_url) . '">‹ ' . esc_html__('Back', 'metra-classifieds-plugin') . '</a>';
                // Left heading (like wizard)
                $section = (string) $parent_term->name;
            }
        }

        // Cancel link: back to listings page.
        $cancel_href = self::page_url('listings') ?: home_url('/');
if (is_wp_error($terms) || empty($terms)) {
            return '<div class="mc-catwiz mc-catwiz-standalone"><div class="mc-catwiz-sheet"><div class="mc-catwiz-topbar">'
                . '<span class="mc-catwiz-spacer" aria-hidden="true"></span>'
                . '<div class="mc-catwiz-title">' . esc_html($title) . '</div>'
                . '<a class="mc-catwiz-close" href="' . esc_url($cancel_href) . '">' . esc_html__('Close', 'metra-classifieds-plugin') . '</a>'
                . '</div><div class="mc-catwiz-empty">'
                . esc_html__('No categories found.', 'metra-classifieds-plugin')
                . '</div></div></div>';
        }

        $out  = '<div class="mc-catwiz mc-catwiz-standalone">';
        $out .= '  <div class="mc-catwiz-overlay" aria-hidden="true"></div>';
        $out .= '  <div class="mc-catwiz-sheet" role="region" aria-label="' . esc_attr($title) . '">';
        $out .= '    <div class="mc-catwiz-topbar">';
        $out .= '      <span class="mc-catwiz-spacer" aria-hidden="true"></span>';
        $out .= '      <div class="mc-catwiz-title">' . esc_html($title) . '</div>';
        $out .= '      <a class="mc-catwiz-close" href="' . esc_url($cancel_href) . '">' . esc_html__('Close', 'metra-classifieds-plugin') . '</a>';
        $out .= '    </div>';

        $out .= '    <div class="mc-catwiz-section">' . esc_html($section) . '</div>';
        if ($back_html !== '') {
            $out .= '    <div class="mc-catwiz-backwrap">' . $back_html . '</div>';
        }

        $out .= '    <div class="mc-catwiz-list">';

        foreach ($terms as $term) {
            if (!$term || is_wp_error($term)) {
                continue;
            }

            // Check leaf
            $child_ids = get_terms([
                'taxonomy'   => MC_CPT::TAX,
                'hide_empty' => false,
                'parent'     => (int) $term->term_id,
                'fields'     => 'ids',
                'number'     => 1,
            ]);
            $has_children = (!is_wp_error($child_ids) && !empty($child_ids));

            // Linkziel:
            // - Nicht-Leaf + embedded -> AJAX drilldown (data-mc-drill)
            // - Nicht-Leaf + standalone -> normale Navigation zu mc-categories
            // - Leaf -> Listingsliste
            if ($has_children && !$is_standalone_page) {
                $link       = 'javascript:void(0)';
                $drill_attr = ' data-mc-drill="' . (int) $term->term_id . '" data-mc-label="' . esc_attr((string) $term->name) . '"';
            } elseif ($has_children) {
                $link       = add_query_arg(['parent' => (string) $term->term_id], self::page_url('categories') ?: home_url('/'));
                $drill_attr = '';
            } else {
                $link       = self::page_url('category_listings', ['cat' => (string) $term->term_id]);
                $drill_attr = '';
            }

            $name = (string) $term->name;
            if ($show_count) {
                $term_count = self::get_effective_public_listing_count_for_term((int) $term->term_id);
                $name .= ' (' . (int) $term_count . ')';
            }

            $out .= '      <a class="mc-catwiz-item" href="' . esc_url($link) . '"' . $drill_attr . '>';
            $mobile_icon = MC_Icons::render_mobile_category_list_icon($term, ['label' => (string) $term->name]);
            if ($mobile_icon !== '') {
                $out .= '        <span class="mc-catwiz-iconbox" aria-hidden="true">' . $mobile_icon . '</span>';
            }
            $out .= '        <span class="mc-catwiz-name">' . esc_html($name) . '</span>';
            $out .= '        <span class="mc-catwiz-chevron" aria-hidden="true"><svg class="mc-catwiz-chevron-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>';
            $out .= '      </a>';
        }

        $out .= '    </div>';
        $out .= '  </div>';
        $out .= '</div>';

        // Standalone page: set body class via JS to hide search/buttons reliably
        if ($is_standalone_page) {
            $out .= '<script>document.body.classList.add("mc-catwiz-page");</script>';
        }

        return $out;
    }

/**
 * Single listing action controls (Edit + Delete) – only for post author or admin.
 * Used on the single listing view to show both actions next to each other.
 */
public static function render_single_actions(int $post_id): string {
    if (!is_user_logged_in()) {
        return '';
    }
    $post = get_post($post_id);
    $is_admin = current_user_can('manage_options');
    if (!$post || ((int) $post->post_author !== get_current_user_id() && !$is_admin)) {
        return '';
    }

    $edit_url = add_query_arg('mc_edit', '1', get_permalink($post_id));

    $delete_url = self::page_url('my_listings') ?: home_url('/');
    $delete_url = add_query_arg([
        'mc_action'  => 'delete_listing',
        'listing_id' => (string) $post_id,
        '_mc_nonce'  => wp_create_nonce('mc_delete_listing_' . $post_id),
    ], $delete_url);

    $confirm = __('Do you really want to permanently delete this listing?', 'metra-classifieds-plugin');

    return '<div class="mc-single-actions" style="margin-top:14px;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">'
        . '<a class="button mc-edit-button" href="' . esc_url($edit_url) . '">' . esc_html__('Edit listing', 'metra-classifieds-plugin') . '</a>'
        . '<a class="button mc-delete-button" href="' . esc_url($delete_url) . '" onclick="return confirm(' . json_encode($confirm) . ');">'
        . esc_html__('Delete listing', 'metra-classifieds-plugin')
        . '</a>'
        . '</div>';
}

/**
 * Inline edit form on single listing (?mc_edit=1) – only for post author.
 */
public static function render_edit_form(int $post_id): string {
    if (!is_user_logged_in()) {
        return '';
    }

    $post = get_post($post_id);
    $is_admin = current_user_can('manage_options');
    if (!$post || ((int) $post->post_author !== get_current_user_id() && !$is_admin)) {
        return '';
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mc_edit_form'])) {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce((string) $_POST['_wpnonce'], 'mc_edit_listing_' . $post_id)) {
            $errors[] = __('Security check failed.', 'metra-classifieds-plugin');
        } else {
            self::handle_edit_submit($post_id, $errors);
            if (empty($errors)) {
                wp_safe_redirect(remove_query_arg('mc_edit', get_permalink($post_id)));
                exit;
            }
        }
    }

    // current values
    $title = (string) $post->post_title;
    $desc  = (string) $post->post_content;
    $price = (string) get_post_meta($post_id, 'mc_price', true);
    $ptype = (string) get_post_meta($post_id, 'mc_price_type', true);
    $terms = wp_get_object_terms($post_id, MC_CPT::TAX, ['fields' => 'ids']);
    $current_cat = !empty($terms) ? (int) $terms[0] : 0;

    $condition_terms = wp_get_object_terms($post_id, 'mc_condition', ['fields' => 'slugs']);
    $current_condition = (!is_wp_error($condition_terms) && !empty($condition_terms)) ? sanitize_key((string) $condition_terms[0]) : '';

    // If submitted, keep user input
    if (!empty($_POST['mc_edit_form']) && is_array($_POST['mc_edit_form'])) {
        $data = (array) $_POST['mc_edit_form'];
        $title = sanitize_text_field((string) ($data['title'] ?? $title));
        $desc  = self::sanitize_public_description((string) ($data['description'] ?? $desc));
        $current_cat = (int) ($data['category'] ?? $current_cat);
        $current_condition = sanitize_key((string) ($data['condition'] ?? $current_condition));
        $price = sanitize_text_field((string) ($data['price'] ?? $price));
        $ptype = !empty($data['price_negotiable']) ? 'negotiable' : 'fixed';    }

    $out = '<div class="mc-edit-form">';
    $out .= '<h3 style="margin:0 0 12px 0;">' . esc_html__('Edit Listing', 'metra-classifieds-plugin') . '</h3>';

    if (!empty($errors)) {
        $out .= '<div class="notice notice-error" style="padding:10px;border-left:4px solid #d63638;background:#fff;margin:10px 0;">'
            . '<ul style="margin:0 0 0 18px;">';
        foreach ($errors as $e) {
            $out .= '<li>' . esc_html((string) $e) . '</li>';
        }
        $out .= '</ul></div>';
    }

    $terms_all = get_terms([
        'taxonomy'   => MC_CPT::TAX,
        'hide_empty' => false,
    ]);

    $condition_terms_all = get_terms([
        'taxonomy'   => 'mc_condition',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    $out .= '<div id="mc-inline-edit-wait-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;align-items:center;justify-content:center;">'    . '<div style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>'    . '<div role="dialog" aria-modal="true" style="position:relative;max-width:520px;width:92%;background:#fff;border-radius:18px;box-shadow:0 10px 40px rgba(0,0,0,.25);padding:18px 18px 16px;">'    . '<div style="font-weight:800;font-size:18px;margin:0 0 6px 0;">' . esc_html__("Please wait", "metra-classifieds-plugin") . '</div>'    . '<div style="font-size:14px;opacity:.9;">' . esc_html__("Your listing is being updated.", "metra-classifieds-plugin") . '</div>'    . '<div style="margin-top:14px;height:10px;border-radius:999px;background:#f0f0f0;overflow:hidden;">'    . '<div style="height:100%;width:45%;background:#111;animation:mcBar 1.1s ease-in-out infinite;"></div>'    . '</div>'    . '</div>'    . '</div>'    . '<style>@keyframes mcBar{0%{transform:translateX(-120%);}100%{transform:translateX(260%);}}</style>';
$out .= '<form id="mc-inline-edit-form" method="post" enctype="multipart/form-data">';
    $out .= wp_nonce_field('mc_edit_listing_' . $post_id, '_wpnonce', true, false);
    $out .= '<input type="hidden" name="mc_edit_form[submit]" value="1" />';

    // === MC: Title max chars inline-edit input v0.0708 START ===
    $mc_title_max_inline = (int) get_option('mc_title_max_chars', 35);
    $mc_title_max_inline = max(1, min(200, $mc_title_max_inline));
    $out .= self::render_title_guard_script();
    $out .= '<p><label><strong>' . esc_html__('Title', 'metra-classifieds-plugin') . '</strong></label><br />'
        . '<input type="text" id="mc-title-input-inline" name="mc_edit_form[title]" value="' . esc_attr($title) . '" maxlength="' . esc_attr((string) $mc_title_max_inline) . '" placeholder="' . esc_attr('Max. ' . $mc_title_max_inline) . '" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;" autocomplete="off" required>'
        . '<span id="mc-title-counter-inline" style="display:block;font-size:12px;margin-top:4px;color:#666;"></span>'
        . '</p>';
    $out .= '<script>mcTitleGuard("mc-title-input-inline","mc-title-counter-inline",' . (int)$mc_title_max_inline . ');</script>';
    // === MC: Title max chars inline-edit input v0.0708 END ===

    $out .= '<div><label><strong>' . esc_html__('Description', 'metra-classifieds-plugin') . '</strong></label><br />'
        . '<textarea name="mc_edit_form[description]" rows="6" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;" required>'
        . esc_textarea($desc) . '</textarea>'
        . '<p class="description mc-field-hint" style="margin:6px 0 18px 0;max-width:100%;">' . esc_html__('Tip: Use **word** for bold text. Links, email addresses and websites are not allowed.', 'metra-classifieds-plugin') . '</p></div>';

	// Category (taxonomy) - only leaf terms are selectable
	$out .= '<p><label><strong>' . esc_html__('Category', 'metra-classifieds-plugin') . '</strong></label><br />'
	    . '<select name="mc_edit_form[category]" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;">'
	    . '<option value="0">' . esc_html__('Select category', 'metra-classifieds-plugin') . '</option>';
	if (!is_wp_error($terms_all) && !empty($terms_all)) {
	    $out .= self::render_category_tree_options($terms_all, (int) $current_cat);
	}
	$out .= '</select></p>';

    $out .= '<p><label><strong>' . esc_html__('Condition', 'metra-classifieds-plugin') . '</strong></label><br />'
        . '<select name="mc_edit_form[condition]" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:10px;">'
        . '<option value="">' . esc_html__('Select condition', 'metra-classifieds-plugin') . '</option>';
    if (!is_wp_error($condition_terms_all) && !empty($condition_terms_all)) {
        foreach ($condition_terms_all as $t) {
            $slug = sanitize_key((string) $t->slug);
            $label = (string) $t->name;
            if (class_exists('MetraClassifieds\MC_Helpers') && method_exists('MetraClassifieds\MC_Helpers', 'mc_translate_condition_label')) {
                $label = MC_Helpers::mc_translate_condition_label($slug, $label);
            }
            $out .= '<option value="' . esc_attr($slug) . '" ' . selected($current_condition, $slug, false) . '>' . esc_html($label) . '</option>';
        }
    }
    $out .= '</select></p>';

	// Price (meta)
	$out .= '<div class="mc-price-row-wrap"><label style="display:block;"><strong>' . esc_html__('Price', 'metra-classifieds-plugin') . '</strong></label>'
	    . '<div class="mc-price-row" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">'
	    . '<input type="number" step="1" min="0" inputmode="numeric" pattern="[0-9]*" name="mc_edit_form[price]" value="' . esc_attr($price) . '" style="width:100%;max-width:12ch;padding:10px;border:1px solid #ddd;border-radius:10px;" required />'
	    . '<label style="display:inline-flex;align-items:center;gap:6px;font-weight:400;"><input type="checkbox" name="mc_edit_form[price_negotiable]" value="1" ' . checked($ptype === 'negotiable', true, false) . ' /> ' . esc_html__('Negotiable', 'metra-classifieds-plugin') . '</label>'
	    . '</div>'
	    . '<p class="description mc-field-hint" style="margin:6px 0 18px 0;max-width:100%;">' . esc_html__('Numbers only. Do not use currency symbols or special characters.', 'metra-classifieds-plugin') . '</p>'
	    . '</div>';
	$out .= '<input type="hidden" name="mc_edit_form[price_type]" value="' . esc_attr($ptype === 'negotiable' ? 'negotiable' : 'fixed') . '" />';

// Images (delete / add / reorder / choose main)
$gallery_ids = get_post_meta($post_id, 'mc_gallery_ids', true);
if (!is_array($gallery_ids)) {
    $gallery_ids = [];
}
$gallery_ids = array_values(array_filter(array_map('intval', $gallery_ids)));

$thumb_id = (int) get_post_thumbnail_id($post_id);
if ($thumb_id <= 0 && !empty($gallery_ids)) {
    $thumb_id = (int) $gallery_ids[0];
}

$out .= '<div class="mc-edit-images" style="margin:14px 0 18px 0;padding:12px 12px;border:1px solid #e5e5e5;border-radius:14px;background:#fafafa;">';
$out .= '<div style="font-weight:700;margin-bottom:8px;">' . esc_html__('Images', 'metra-classifieds-plugin') . '</div>';

// hidden order field
$out .= '<input type="hidden" name="mc_edit_form[gallery_order]" id="mc_gallery_order" value="' . esc_attr(implode(',', $gallery_ids)) . '"/>';

if (!empty($gallery_ids)) {
    $out .= '<div id="mc_gallery_list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;">';
    foreach ($gallery_ids as $gid) {
        $img = wp_get_attachment_image_url($gid, 'thumbnail');
        if (!$img) { continue; }
        $is_main = ($gid === $thumb_id);
        $out .= '<div class="mc-gitem" draggable="true" data-id="' . (int) $gid . '" style="border:1px solid #ddd;border-radius:12px;overflow:hidden;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.04);">';
        $out .= '<div style="aspect-ratio:1/1;overflow:hidden;background:#f2f2f2;"><img src="' . esc_url($img) . '" style="width:100%;height:100%;object-fit:cover;display:block;" /></div>';
        $out .= '<div style="padding:8px 10px;font-size:13px;line-height:1.25;">';
        $out .= '<label style="display:flex;gap:6px;align-items:center;margin:0 0 6px 0;"><input type="radio" name="mc_edit_form[main_image]" value="' . (int) $gid . '" ' . checked($is_main, true, false) . ' /> '
            . esc_html__('Primary image', 'metra-classifieds-plugin') . '</label>';
        $out .= '<label style="display:flex;gap:6px;align-items:center;margin:0;"><input type="checkbox" name="mc_edit_form[delete_images][]" value="' . (int) $gid . '" /> '
            . esc_html__('Delete', 'metra-classifieds-plugin') . '</label>';
        $out .= '<div style="margin-top:6px;opacity:.7;font-size:12px;">' . esc_html__('Drag to sort', 'metra-classifieds-plugin') . '</div>';
        $out .= '</div></div>';
    }
    $out .= '</div>';
    $out .= '<p style="margin:10px 0 0 0;opacity:.85;font-size:13px;">' . esc_html__('Select images to delete, drag to sort, choose a primary image, then save.', 'metra-classifieds-plugin') . '</p>';
} else {
    $out .= '<p style="margin:0;opacity:.85;">' . esc_html__('No images yet.', 'metra-classifieds-plugin') . '</p>';
}

$out .= '<div style="margin-top:14px;">';
$out .= '<label style="display:block;font-weight:600;margin-bottom:6px;">' . esc_html__('Add new images', 'metra-classifieds-plugin') . '</label>';
$out .= '<div class="mc-upload-dnd">';
$out .= '<input id="mc-edit-new-images" class="mc-upload-dnd__input" type="file" name="mc_new_images[]" multiple accept="image/*" />';
$out .= '<div class="mc-upload-dnd__surface" tabindex="0" role="button" aria-controls="mc-edit-new-images">';
$out .= '<span class="mc-upload-dnd__title">' . esc_html__('Drop images here', 'metra-classifieds-plugin') . '</span>';
$out .= '<span class="mc-upload-dnd__hint">' . esc_html__('or choose files', 'metra-classifieds-plugin') . '</span>';
$out .= '<button type="button" class="mc-upload-dnd__button button">' . esc_html__('Choose images', 'metra-classifieds-plugin') . '</button>';
$out .= '<span class="mc-upload-dnd__status">' . esc_html__('No files selected', 'metra-classifieds-plugin') . '</span>';
$out .= '<ul class="mc-upload-dnd__list" aria-live="polite"></ul>';
$out .= '</div>';
$out .= '</div>';
$out .= '</div>';

// Drag & drop ordering script
$out .= '<script>
(function(){
  var list=document.getElementById("mc_gallery_list");
  if(!list) return;
  var dragEl=null;
  function update(){
    var ids=[].map.call(list.querySelectorAll(".mc-gitem"), function(el){ return el.getAttribute("data-id"); });
    var inp=document.getElementById("mc_gallery_order");
    if(inp) inp.value=ids.join(",");
  }
  list.addEventListener("dragstart", function(e){
    var t=e.target.closest(".mc-gitem"); if(!t) return;
    dragEl=t; e.dataTransfer.effectAllowed="move";
    try{ e.dataTransfer.setData("text/plain", t.getAttribute("data-id")); }catch(err){}
    t.style.opacity="0.6";
  });
  list.addEventListener("dragend", function(e){
    var t=e.target.closest(".mc-gitem"); if(!t) return;
    t.style.opacity="1";
    update();
  });
  list.addEventListener("dragover", function(e){
    e.preventDefault();
    var t=e.target.closest(".mc-gitem"); if(!t || t===dragEl) return;
    var rect=t.getBoundingClientRect();
    var next=(e.clientY-rect.top)/(rect.bottom-rect.top) > 0.5;
    list.insertBefore(dragEl, next ? t.nextSibling : t);
  });
  update();
})();
</script>';

$out .= '</div>';

    $out .= '<p style="margin-top:14px;">'
        . '<button id="mc-inline-edit-submit" type="submit" class="button button-primary">' . esc_html__('Save changes', 'metra-classifieds-plugin') . '</button> '
        . '<a class="button" href="' . esc_url(remove_query_arg('mc_edit', get_permalink($post_id))) . '">' . esc_html__('Cancel', 'metra-classifieds-plugin') . '</a>'
        . '</p>';

    // Wait modal on submit (edit listing inline).
    // Must be visible immediately before the browser starts uploading files.
    $out .= '<script>(function(){
        var f=document.getElementById("mc-inline-edit-form");
        if(!f) return;
        var m=document.getElementById("mc-inline-edit-wait-modal");
        var btn=document.getElementById("mc-inline-edit-submit");
        var shown=false;

        function show(){
            if(shown) return;
            shown=true;
            if(m){m.style.display="flex";m.setAttribute("aria-hidden","false");}
            if(btn){btn.disabled=true;btn.style.opacity=".7";btn.style.cursor="not-allowed";}
        }

        // Ensure a repaint before the request starts (important for file uploads).
        if(btn){
            btn.addEventListener("click", function(e){
                if(shown) return;
                e.preventDefault();
                show();
                setTimeout(function(){ f.submit(); }, 60);
            });
        }

        // Fallback (e.g. Enter key submit)
        f.addEventListener("submit", function(){ show(); });
    })();</script>';

    $out .= '</form></div>';

    return $out;
}

private static function handle_edit_submit(int $post_id, array &$errors): void {
    $post = get_post($post_id);
    if (!$post) {
        $errors[] = __('Listing not found.', 'metra-classifieds-plugin');
        return;
    }
    $is_admin = current_user_can('manage_options');
    if ((int) $post->post_author !== get_current_user_id() && !$is_admin) {
        $errors[] = __('You are not allowed to edit this listing.', 'metra-classifieds-plugin');
        return;
    }

    $data = isset($_POST['mc_edit_form']) && is_array($_POST['mc_edit_form']) ? (array) $_POST['mc_edit_form'] : [];

    $title = sanitize_text_field((string) ($data['title'] ?? ''));
    // === MC: Title max chars handle_edit_submit v0.0707 START ===
    $mc_title_max_submit = (int) get_option('mc_title_max_chars', 35);
    $mc_title_max_submit = max(1, min(200, $mc_title_max_submit));
    $title = mb_substr($title, 0, $mc_title_max_submit);
    // === MC: Title max chars handle_edit_submit v0.0707 END ===
    $desc  = self::sanitize_public_description((string) ($data['description'] ?? ''));
    $cat   = (int) ($data['category'] ?? 0);
    $condition = sanitize_key((string) ($data['condition'] ?? ''));
    $price_raw = (string) ($data['price'] ?? '');
    $price_clean = preg_replace('/\D+/', '', $price_raw);
    $price_clean = mb_substr((string) $price_clean, 0, 12);

    if ($title === '') {
        $errors[] = __('Please enter a title.', 'metra-classifieds-plugin');
    }

    if ($desc === '') {
        $errors[] = __('Please enter a description.', 'metra-classifieds-plugin');
    }

    // Disallow selecting non-leaf category terms (category groups)
    if ($cat > 0 && !self::is_leaf_category_term($cat)) {
        $errors[] = __('Please select a subcategory (not a category group).', 'metra-classifieds-plugin');
    }

    if ($price_clean === '') {
        $errors[] = __('Please enter a price.', 'metra-classifieds-plugin');
    }

    if ($condition !== '') {
        $condition_exists = term_exists($condition, 'mc_condition');
        if (!$condition_exists) {
            $errors[] = __('Please choose a valid condition.', 'metra-classifieds-plugin');
        }
    }

    if (!empty($errors)) {
        return;
    }

    $updated = wp_update_post([
        'ID'           => $post_id,
        'post_title'   => $title,
        'post_content' => $desc,
    ], true);

    if (is_wp_error($updated)) {
        $errors[] = __('Could not update listing.', 'metra-classifieds-plugin');
        return;
    }

    if ($cat > 0) {
        wp_set_object_terms($post_id, [$cat], MC_CPT::TAX, false);
    }

    if ($condition !== '') {
        wp_set_object_terms($post_id, [$condition], 'mc_condition', false);
    } else {
        wp_set_object_terms($post_id, [], 'mc_condition', false);
    }

    update_post_meta($post_id, 'mc_price', $price_clean);
    $price_type = !empty($data['price_negotiable']) ? 'negotiable' : 'fixed';
    update_post_meta($post_id, 'mc_price_type', $price_type);
    delete_post_meta($post_id, 'mc_zip');
    delete_post_meta($post_id, 'mc_city');

    // Images: delete existing / add new
    self::process_edit_images_simple($post_id, $data, $errors);
}

private static function process_edit_images_simple(int $post_id, array $data, array &$errors): void {
    $gallery_ids = get_post_meta($post_id, 'mc_gallery_ids', true);
    if (!is_array($gallery_ids)) {
        $gallery_ids = [];
    }
    $gallery_ids = array_values(array_filter(array_map('intval', $gallery_ids)));

    // Apply order from hidden field (prevent injection by intersecting with existing ids)
    $order = isset($data['gallery_order']) ? (string) $data['gallery_order'] : '';
    if ($order !== '') {
        $ordered = array_values(array_filter(array_map('intval', explode(',', $order))));
        $ordered = array_values(array_intersect($ordered, $gallery_ids));
        foreach ($gallery_ids as $id) {
            if (!in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
        $gallery_ids = $ordered;
    }

    // Deletions
    $to_delete = [];
    if (isset($data['delete_images']) && is_array($data['delete_images'])) {
        $to_delete = array_values(array_filter(array_map('intval', $data['delete_images'])));
        $to_delete = array_values(array_intersect($to_delete, $gallery_ids));
    }
    if (!empty($to_delete)) {
        foreach ($to_delete as $aid) {
            $att = get_post($aid);
            if ($att && $att->post_type === 'attachment') {
                wp_delete_attachment($aid, true);
            }
        }
        $gallery_ids = array_values(array_diff($gallery_ids, $to_delete));
    }

    // Upload new images (append)
    $new_ids = self::handle_new_images_upload_simple($post_id, $errors);
    if (!empty($new_ids)) {
        $gallery_ids = array_merge($gallery_ids, $new_ids);
    }

    // Main image selection
    $main_id = isset($data['main_image']) ? (int) $data['main_image'] : 0;
    if ($main_id > 0 && in_array($main_id, $gallery_ids, true)) {
        set_post_thumbnail($post_id, $main_id);
    } else {
        // keep existing thumbnail if still present, else set first
        $thumb_id = (int) get_post_thumbnail_id($post_id);
        if ($thumb_id > 0 && !in_array($thumb_id, $gallery_ids, true)) {
            delete_post_thumbnail($post_id);
            $thumb_id = 0;
        }
        if ($thumb_id <= 0 && !empty($gallery_ids)) {
            set_post_thumbnail($post_id, (int) $gallery_ids[0]);
        }
    }

    update_post_meta($post_id, 'mc_gallery_ids', $gallery_ids);
}

private static function handle_new_images_upload_simple(int $post_id, array &$errors): array {
    if (empty($_FILES['mc_new_images']) || !is_array($_FILES['mc_new_images'])) {
        return [];
    }

    $names = $_FILES['mc_new_images']['name'] ?? [];
    $tmps  = $_FILES['mc_new_images']['tmp_name'] ?? [];
    $errs  = $_FILES['mc_new_images']['error'] ?? [];
    $sizes = $_FILES['mc_new_images']['size'] ?? [];

    if (!is_array($names) || !is_array($tmps) || !is_array($errs) || !is_array($sizes)) {
        return [];
    }

    // If no new files were selected, do not block saves just because the listing already reached the max.
    $has_new_upload = false;
    for ($i = 0; $i < count($errs); $i++) {
        $err = (int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err !== UPLOAD_ERR_NO_FILE) {
            $has_new_upload = true;
            break;
        }
    }
    if (!$has_new_upload) {
        return [];
    }

    $max = (int) get_option('mc_max_images_per_listing', 0);
    $existing = get_post_meta($post_id, 'mc_gallery_ids', true);
    if (!is_array($existing)) {
        $existing = [];
    }
    $existing = array_values(array_filter(array_map('intval', $existing)));

    if ($max > 0) {
        $remaining = $max - count($existing);
        if ($remaining <= 0) {
            $errors[] = __('Maximum number of images reached for this listing.', 'metra-classifieds-plugin');
            return [];
        }
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $ids = [];
    for ($i = 0; $i < count($names); $i++) {
        if ($max > 0 && isset($remaining) && count($ids) >= $remaining) {
            $errors[] = __('Some images were not uploaded because the maximum number of images per listing was reached.', 'metra-classifieds-plugin');
            break;
        }

        $err = (int) ($errs[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = __('An image upload failed.', 'metra-classifieds-plugin');
            continue;
        }

        $file = [
            'name'     => (string) ($names[$i] ?? ''),
            'tmp_name' => (string) ($tmps[$i] ?? ''),
            'error'    => $err,
            'size'     => (int) ($sizes[$i] ?? 0),
        ];

        $check = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (empty($check['ext']) || empty($check['type']) || strpos((string) $check['type'], 'image/') !== 0) {
            $errors[] = sprintf(__('File "%s" is not a supported image.', 'metra-classifieds-plugin'), $file['name']);
            continue;
        }

        $uploaded = wp_handle_upload($file, ['test_form' => false]);
        if (empty($uploaded['file'])) {
            $errors[] = sprintf(__('Image "%s" could not be uploaded.', 'metra-classifieds-plugin'), $file['name']);
            continue;
        }

        $file_path = (string) $uploaded['file'];
        $file_url  = (string) ($uploaded['url'] ?? '');
        $mime_type = (string) ($uploaded['type'] ?? '');

        $att_id = wp_insert_attachment([
            'post_mime_type' => $mime_type,
            'post_title'     => sanitize_file_name(pathinfo($file_path, PATHINFO_FILENAME)),
            'post_status'    => 'inherit',
            'guid'           => $file_url,
        ], $file_path, $post_id);

        if (is_wp_error($att_id) || (int) $att_id <= 0) {
            $errors[] = sprintf(__('Image "%s" could not be attached.', 'metra-classifieds-plugin'), $file['name']);
            continue;
        }

        $meta = wp_generate_attachment_metadata((int) $att_id, $file_path);
        if (is_array($meta)) {
            wp_update_attachment_metadata((int) $att_id, $meta);
        }

        $ids[] = (int) $att_id;
    }

    return $ids;
}

/**
 * Render hierarchical category options (tree) for a select dropdown.
 *
 * @param array $terms Result of get_terms()
 * @param int   $selected_id Currently selected term ID
 * @return string HTML <option> list
 */
    /**
     * True if term has no children (i.e. a "real" selectable category). Used
     * to prevent assigning listings to category group headings.
     */
    private static function is_leaf_category_term(int $term_id): bool {
        if ($term_id <= 0) {
            return false;
        }
        $children = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => $term_id,
            'number'     => 1,
            'fields'     => 'ids',
        ]);
        return is_wp_error($children) || empty($children);
    }

private static function render_category_tree_options(array $terms, int $selected_id = 0): string {
    $children = [];
    $labels   = [];

    foreach ($terms as $t) {
        if (!isset($t->term_id)) {
            continue;
        }
        $id = (int) $t->term_id;
        $parent = isset($t->parent) ? (int) $t->parent : 0;
        $children[$parent] = $children[$parent] ?? [];
        $children[$parent][] = $id;
        $labels[$id] = (string) ($t->name ?? '');
    }

    // sort children by label
    foreach ($children as $p => $ids) {
        usort($ids, function($a, $b) use ($labels) {
            return strcasecmp($labels[$a] ?? '', $labels[$b] ?? '');
        });
        $children[$p] = $ids;
    }

    $out = '';
    $walk = function($parent, $depth) use (&$walk, &$out, $children, $labels, $selected_id) {
        if (empty($children[$parent])) {
            return;
        }
        foreach ($children[$parent] as $id) {
            $prefix = str_repeat('— ', max(0, (int) $depth));
            $name = $labels[$id] ?? '';
            $has_children = !empty($children[$id]);
            $disabled = $has_children ? ' disabled="disabled"' : '';
			$out .= '<option value="' . (int) $id . '"' . $disabled . selected($selected_id, (int) $id, false) . '>'
                . esc_html($prefix . $name) . '</option>';
            $walk($id, $depth + 1);
        }
    };

    $walk(0, 0);
    return $out;
}

/**
 * Render contact button + modal (safe: no PHP closing tags inside).
 */
// Contact UI + handlers moved to modules/contact/ (single source of truth).

public static function register_page(): string {
    if (is_user_logged_in()) {
        return '<div class="mc-register-status" style="padding:12px 14px;border-radius:12px;border:1px solid rgba(0,0,0,.12);background:#fafafa;">'
            . esc_html__('Sie sind bereits angemeldet.', 'metra-classifieds-plugin')
            . '</div>';
    }

    $status = isset($_GET['mc_reg']) ? sanitize_key((string) $_GET['mc_reg']) : '';
    $activation = isset($_GET['mc_activation']) ? sanitize_key((string) $_GET['mc_activation']) : '';
    $msg = '';
    $msg_type = 'ok';
    if ($status === 'sent') {
        $msg_type = 'ok';
        $msg = esc_html__('Email sent. Please check your inbox and click the confirmation link.', 'metra-classifieds-plugin');
    } elseif ($status === 'email_exists') {
        $msg_type = 'fail';
        $msg = esc_html__('This email address is already registered. Please sign in or use a different address.', 'metra-classifieds-plugin');
    } elseif ($status === 'user_exists') {
        $msg_type = 'fail';
        $msg = esc_html__('This username is already taken. Please choose another username.', 'metra-classifieds-plugin');
    } elseif ($status === 'exists') {
        $msg_type = 'fail';
        $msg = esc_html__('Username und/oder Email address existieren bereits. Please verwenden Sie andere Daten oder melden Sie sich an.', 'metra-classifieds-plugin');
    } elseif ($status === 'invalid') {
        $msg_type = 'fail';
        $msg = esc_html__('Please enter a valid email address, a username (min. 3 chars), a ZIP (4–5 digits) and a city (min. 3 chars).', 'metra-classifieds-plugin');

} elseif ($status === 'captcha') {
    $msg_type = 'fail';
    $msg = esc_html__('Please solve the security question correctly.', 'metra-classifieds-plugin');
} elseif ($status === 'mailfail') {
        $msg_type = 'fail';
        $msg = esc_html__('The user was created, but the activation email could not be sent. Please check your mail configuration (SMTP) or contact the administrator.', 'metra-classifieds-plugin');
    } elseif ($activation === 'success') {
        $msg_type = 'ok';
        $msg = esc_html__('Ihr Konto wurde aktiviert. Sie erhalten nun eine Email zum Festlegen Ihres Passwords.', 'metra-classifieds-plugin');
    } elseif ($status === 'fail') {
        $msg_type = 'fail';
        $msg = esc_html__('Registration failed. Please try again later.', 'metra-classifieds-plugin');
    } elseif ($activation === 'invalid') {
        $msg_type = 'fail';
        $msg = esc_html__('Aktivierungslink invalid oder expired.', 'metra-classifieds-plugin');
    }

    // Fallback message when redirect couldn't happen.
    if (!$msg && self::$reg_flash) {
        $msg = self::$reg_flash['message'];
        $msg_type = self::$reg_flash['type'];
    }

    $nonce = wp_create_nonce('mc_reg');
    // Hard return URL for POST (some environments strip Referer/REQUEST_URI). We keep the user on /mc-register/.
    $return_url = home_url(isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/');
    $html = '<div class="mc-register-page" style="max-width:640px;margin:0 auto;padding:16px 0;">';
    if ($msg) {
        $ok = ($msg_type !== 'fail');
        $html .= '<div style="margin:0 0 14px 0;padding:10px 12px;border-radius:12px;border:1px solid ' . ($ok ? 'rgba(0,0,0,.12)' : 'rgba(214,54,56,.35)') . ';background:' . ($ok ? 'rgba(46,125,50,.06)' : 'rgba(214,54,56,.06)') . ';color:' . ($ok ? '#1b5e20' : '#7a1f22') . ';">'
            . esc_html($msg) . '</div>';
    }

    $html .= '<form method="post" style="padding:16px 18px;border-radius:16px;border:1px solid rgba(0,0,0,.10);background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.06);">';
    $html .= '<input type="hidden" name="mc_reg_nonce" value="' . esc_attr($nonce) . '">';
    $html .= '<input type="hidden" name="mc_reg_action" value="1">';
    $html .= '<input type="hidden" name="mc_return" value="' . esc_url($return_url) . '">';
    $html .= '<h3 style="margin:0 0 12px 0;">' . esc_html__('Register', 'metra-classifieds-plugin') . '</h3>';

    $html .= '<p style="margin:0 0 12px 0;">'
        . '<label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('Email address', 'metra-classifieds-plugin') . '</label>'
        . '<input type="email" name="mc_reg_email" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;">'
        . '</p>';

    $html .= '<p style="margin:0 0 12px 0;">'
        . '<label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('Username', 'metra-classifieds-plugin') . '</label>'
        . '<input type="text" name="mc_reg_user" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;">'
        . '</p>';

    $html .= '<p style="margin:0 0 12px 0;">'
        . '<label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('ZIP', 'metra-classifieds-plugin') . ' *</label>'
        . '<input type="text" name="mc_reg_plz" required inputmode="numeric" pattern="\\d{4,5}" minlength="4" maxlength="5" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;">'
        . '</p>';

    $html .= '<p style="margin:0 0 12px 0;">'
        . '<label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('City', 'metra-classifieds-plugin') . ' *</label>'
        . '<input type="text" name="mc_reg_ort" required minlength="3" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;">'
        . '</p>';

    // Simple addition captcha (anti-bot)
$cap_a = function_exists('random_int') ? random_int(1, 9) : mt_rand(1, 9);
$cap_b = function_exists('random_int') ? random_int(10, 99) : mt_rand(10, 99);
$html .= '<p style="margin:0 0 12px 0;">'
    . '<label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('Security question', 'metra-classifieds-plugin') . ' *</label>'
    . '<span style="display:block;margin:0 0 6px 0;color:#666;font-size:13px;line-height:1.35;">'
    . esc_html(sprintf('%d + %d = ?', $cap_a, $cap_b))
    . '</span>'
    . '<input type="text" name="mc_reg_captcha" required inputmode="numeric" pattern="\\d+" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;">'
    . '<input type="hidden" name="mc_reg_captcha_a" value="' . esc_attr((string) $cap_a) . '">'
    . '<input type="hidden" name="mc_reg_captcha_b" value="' . esc_attr((string) $cap_b) . '">'
    . '</p>';

$html .= '<p style="text-align:right;margin:0;">'
        . '<button type="submit" name="mc_reg_submit" class="button button-primary" style="border-radius:12px;padding:10px 14px;">'
        . esc_html__('Register now', 'metra-classifieds-plugin')
        . '</button>'
        . '</p>';

    $html .= '</form></div>';
        return $html;
}

public static function maybe_handle_register_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['mc_reg_action'], $_POST['mc_reg_nonce'])) {
        return;
    }
    // Prefer explicit return URL from the form (works even if Referer/REQUEST_URI are stripped).
    $dest = self::current_return_url();
    if (isset($_POST['mc_return'])) {
        $candidate = esc_url_raw((string) wp_unslash($_POST['mc_return']));
        if ($candidate !== '' && wp_http_validate_url($candidate)) {
            $home_host = parse_url(home_url('/'), PHP_URL_HOST);
            $cand_host = parse_url($candidate, PHP_URL_HOST);
            // Only allow same-site URLs.
            if (is_string($home_host) && is_string($cand_host) && $home_host === $cand_host) {
                $dest = $candidate;
            }
        }
    }
    if (!wp_verify_nonce((string) $_POST['mc_reg_nonce'], 'mc_reg')) {
        $url = add_query_arg('mc_reg', 'fail', $dest);
        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }
        self::$reg_flash = [
            'type' => 'fail',
            'message' => __('Registration failed (security check). Please try again.', 'metra-classifieds-plugin'),
        ];
        return;
    }

    // Addition captcha (required): one 1-digit + one 2-digit number.
$cap_a = isset($_POST['mc_reg_captcha_a']) ? absint((string) wp_unslash($_POST['mc_reg_captcha_a'])) : 0;
$cap_b = isset($_POST['mc_reg_captcha_b']) ? absint((string) wp_unslash($_POST['mc_reg_captcha_b'])) : 0;
$cap_in = isset($_POST['mc_reg_captcha']) ? trim((string) wp_unslash($_POST['mc_reg_captcha'])) : '';
if ($cap_a < 1 || $cap_a > 9 || $cap_b < 10 || $cap_b > 99 || $cap_in === '' || !preg_match('/^\d+$/', $cap_in) || ((int) $cap_in) !== ($cap_a + $cap_b)) {
    $url = add_query_arg('mc_reg', 'captcha', $dest);
    if (!headers_sent()) {
        wp_safe_redirect($url);
        exit;
    }
    self::$reg_flash = [
        'type' => 'fail',
        'message' => __('Please solve the security question correctly.', 'metra-classifieds-plugin'),
    ];
    return;
}

$email = sanitize_email((string) ($_POST['mc_reg_email'] ?? ''));
    $user  = sanitize_user((string) ($_POST['mc_reg_user'] ?? ''), true);
    $plz_raw = (string) ($_POST['mc_reg_plz'] ?? '');
    $ort_raw = (string) ($_POST['mc_reg_ort'] ?? '');
    $plz = preg_replace('/\s+/', '', sanitize_text_field($plz_raw));
    $ort = sanitize_text_field($ort_raw);

    if (!is_email($email) || empty($user) || strlen($user) < 3 || !preg_match('/^\d{4,5}$/', $plz) || strlen(trim($ort)) < 3) {
        // Use a specific status key so the UI can show a helpful message.
        $url = add_query_arg('mc_reg', 'invalid', $dest);
        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }
        self::$reg_flash = [
            'type' => 'fail',
            'message' => __('Please enter a valid email address, a username (min. 3 chars), a ZIP (4–5 digits) and a city (min. 3 chars).', 'metra-classifieds-plugin'),
        ];
        return;
    }
    $user_exists  = username_exists($user);
    $email_exists = email_exists($email);
    if ($user_exists || $email_exists) {
        // Expose the concrete reason to the UI.
        $reason = ($user_exists && $email_exists) ? 'exists' : ($email_exists ? 'email_exists' : 'user_exists');
        $url = add_query_arg('mc_reg', $reason, $dest);
        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }
        self::$reg_flash = [
            'type' => 'fail',
            'message' => ($email_exists ? __('This email address is already registered.', 'metra-classifieds-plugin') : __('This username is already taken.', 'metra-classifieds-plugin')),
        ];
        return;
    }
    // Step 1 of the new registration flow: no password field in the form.
    // Create the user with a temporary random password until the dedicated set-password flow is fully wired.
    $password = wp_generate_password(20, true, true);

    $user_id = wp_insert_user([
        'user_login' => $user,
        'user_email' => $email,
        'user_pass'  => $password,
        'role'       => 'subscriber',
    ]);

    if (is_wp_error($user_id) || !$user_id) {
        // Map common WP error codes to user friendly status keys.
        $reason = 'fail';
        if (is_wp_error($user_id)) {
            $codes = $user_id->get_error_codes();
            if (in_array('existing_user_email', $codes, true)) {
                $reason = 'email_exists';
            } elseif (in_array('existing_user_login', $codes, true)) {
                $reason = 'user_exists';
            } elseif (in_array('invalid_username', $codes, true) || in_array('empty_username', $codes, true) || in_array('invalid_email', $codes, true) || in_array('empty_email', $codes, true)) {
                $reason = 'invalid';
            }
        }
        $url = add_query_arg('mc_reg', $reason, $dest);
        if (!headers_sent()) {
            wp_safe_redirect($url);
            exit;
        }
        $detail = is_wp_error($user_id) ? $user_id->get_error_message() : '';
        self::$reg_flash = [
            'type' => 'fail',
            'message' => __('Register failed. ', 'metra-classifieds-plugin') . $detail,
        ];
        return;
    }

    // mc6896: store mandatory location fields
    update_user_meta((int) $user_id, 'mc_user_plz', $plz);
    update_user_meta((int) $user_id, 'mc_user_ort', $ort);

    // Activation mail + pending flag are handled by MC_User_Activation via user_register hook.
    // If mail transport fails, show a dedicated status.
    $sent_ts = (string) get_user_meta((int) $user_id, '_mc_activation_mail_sent', true);
    $status_key = ($sent_ts !== '') ? 'sent' : 'mailfail';
    $url = add_query_arg('mc_reg', $status_key, $dest);
    if (!headers_sent()) {
        wp_safe_redirect($url);
        exit;
    }
    if ($status_key === 'sent') {
        self::$reg_flash = [
            'type' => 'ok',
            'message' => __('Email sent. Please check your inbox and click the confirmation link.', 'metra-classifieds-plugin'),
        ];
    } else {
        self::$reg_flash = [
            'type' => 'fail',
            'message' => __('The user was created, but the activation email could not be sent. Please check your mail configuration (SMTP).', 'metra-classifieds-plugin'),
        ];
    }
    return;
}

// -----------------------------------------------------------------------------
// Login (branded METRA pages)
// -----------------------------------------------------------------------------

/**
 * Handle POST from METRA login form.
 */
public static function maybe_handle_login_post(): void {
    if (!isset($_POST['mc_login_action'], $_POST['mc_login_nonce'])) {
        return;
    }

    $dest = self::current_return_url();
    if (isset($_POST['mc_return'])) {
        $candidate = esc_url_raw((string) wp_unslash($_POST['mc_return']));
        if ($candidate !== '' && wp_http_validate_url($candidate)) {
            $home_host = parse_url(home_url('/'), PHP_URL_HOST);
            $cand_host = parse_url($candidate, PHP_URL_HOST);
            if (is_string($home_host) && is_string($cand_host) && $home_host === $cand_host) {
                $dest = $candidate;
            }
        }
    }

    if (!wp_verify_nonce((string) $_POST['mc_login_nonce'], 'mc_login')) {
        wp_safe_redirect(add_query_arg('mc_login', 'fail', $dest));
        exit;
    }

    $user_login = sanitize_text_field((string) ($_POST['log'] ?? ''));
    $user_pass  = (string) ($_POST['pwd'] ?? '');
    $remember   = isset($_POST['rememberme']) && (string) $_POST['rememberme'] === 'forever';

    $redirect_to = isset($_POST['redirect_to']) ? esc_url_raw((string) $_POST['redirect_to']) : '';

    $result = wp_signon([
        'user_login'    => $user_login,
        'user_password' => $user_pass,
        'remember'      => $remember,
    ], is_ssl());

    if (is_wp_error($result)) {
        $code = $result->get_error_code();
        $status = ($code !== '') ? $code : 'fail';
        $url = add_query_arg('mc_login', $status, $dest);
        wp_safe_redirect($url);
        exit;
    }

    // Logged in successfully.
    if ($redirect_to === '') {
        $dash = MC_Pages::get_page_url('dashboard');
        $redirect_to = $dash !== '' ? $dash : home_url('/');
    }
    wp_safe_redirect($redirect_to);
    exit;
}

/**
 * Handle POST from METRA lost-password request form.
 */
public static function maybe_handle_lost_password_post(): void {
    if (!isset($_POST['mc_lostpw_action'], $_POST['mc_lostpw_nonce'])) {
        return;
    }
    $dest = self::current_return_url();
    if (isset($_POST['mc_return'])) {
        $candidate = esc_url_raw((string) wp_unslash($_POST['mc_return']));
        if ($candidate !== '' && wp_http_validate_url($candidate)) {
            $home_host = parse_url(home_url('/'), PHP_URL_HOST);
            $cand_host = parse_url($candidate, PHP_URL_HOST);
            if (is_string($home_host) && is_string($cand_host) && $home_host === $cand_host) {
                $dest = $candidate;
            }
        }
    }
    if (!wp_verify_nonce((string) $_POST['mc_lostpw_nonce'], 'mc_lostpw')) {
        wp_safe_redirect(add_query_arg(['mc_login' => 'lost_fail', 'mc_action' => 'lostpassword'], $dest));
        exit;
    }

    $login_or_email = sanitize_text_field((string) ($_POST['user_login'] ?? ''));
    if ($login_or_email === '') {
        wp_safe_redirect(add_query_arg(['mc_login' => 'lost_invalid', 'mc_action' => 'lostpassword'], $dest));
        exit;
    }

    $ok = retrieve_password($login_or_email);
    if (is_wp_error($ok)) {
        $code = $ok->get_error_code();
        $status = $code !== '' ? 'lost_' . $code : 'lost_fail';
        wp_safe_redirect(add_query_arg(['mc_login' => $status, 'mc_action' => 'lostpassword'], $dest));
        exit;
    }

    wp_safe_redirect(add_query_arg('mc_login', 'lost_sent', $dest));
    exit;
}

/**
 * Handle POST from METRA reset-password page.
 */
public static function maybe_handle_reset_password_post(): void {
    if (!isset($_POST['mc_resetpw_action'], $_POST['mc_resetpw_nonce'])) {
        return;
    }
    $dest = self::current_return_url();
    if (isset($_POST['mc_return'])) {
        $candidate = esc_url_raw((string) wp_unslash($_POST['mc_return']));
        if ($candidate !== '' && wp_http_validate_url($candidate)) {
            $home_host = parse_url(home_url('/'), PHP_URL_HOST);
            $cand_host = parse_url($candidate, PHP_URL_HOST);
            if (is_string($home_host) && is_string($cand_host) && $home_host === $cand_host) {
                $dest = $candidate;
            }
        }
    }
    if (!wp_verify_nonce((string) $_POST['mc_resetpw_nonce'], 'mc_resetpw')) {
        wp_safe_redirect(add_query_arg('mc_resetpw', 'fail', $dest));
        exit;
    }
    $login = sanitize_user((string) ($_POST['login'] ?? ''), true);
    $key   = sanitize_text_field((string) ($_POST['key'] ?? ''));
    $p1    = (string) ($_POST['pass1'] ?? '');
    $p2    = (string) ($_POST['pass2'] ?? '');

    if ($login === '' || $key === '' || $p1 === '' || $p1 !== $p2) {
        wp_safe_redirect(add_query_arg('mc_resetpw', 'fail', $dest));
        exit;
    }

    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user) || !$user instanceof \WP_User) {
        wp_safe_redirect(add_query_arg('mc_resetpw', 'invalid', $dest));
        exit;
    }

    reset_password($user, $p1);

    // Redirect to METRA login page with success flag.
    $login_url = MC_Pages::get_page_url('login');
    if ($login_url === '') {
        $login_url = home_url('/mc-login/');
    }
    wp_safe_redirect(add_query_arg('mc_login', 'reset_success', $login_url));
    exit;
}

    /**
     * Handle permanent deletion of a listing from the "My listings" dashboard page.
     *
     * Triggered via GET:
     *  - mc_action=delete_listing
     *  - listing_id=<ID>
     *  - _mc_nonce=<nonce>
     *
     * Security:
     *  - Requires logged-in user
     *  - Only listing author or admin can delete
     *  - Uses nonce bound to listing ID
     */
    public static function maybe_handle_delete_listing(): void {
        if (empty($_GET['mc_action']) || $_GET['mc_action'] !== 'delete_listing') {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        $listing_id = isset($_GET['listing_id']) ? absint($_GET['listing_id']) : 0;
        if ($listing_id <= 0) {
            return;
        }

        $nonce = isset($_GET['_mc_nonce']) ? (string) $_GET['_mc_nonce'] : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'mc_delete_listing_' . $listing_id)) {
            self::safe_redirect_with_flag(self::page_url('my_listings') ?: home_url('/'), 'mc_deleted', 'nonce');
            return;
        }

        $post = get_post($listing_id);
        if (!$post || $post->post_type !== MC_CPT::CPT) {
            self::safe_redirect_with_flag(self::page_url('my_listings') ?: home_url('/'), 'mc_deleted', 'notfound');
            return;
        }

        $user_id = get_current_user_id();

        $notice = '';
        if (isset($_GET['mc_deleted'])) {
            $flag = (string) $_GET['mc_deleted'];
            if ($flag === 'ok') {
                $notice = '<div class="mc-notice mc-notice-success" style="padding:10px 12px;border:1px solid #b7e4c7;background:#d8f3dc;border-radius:10px;">'
                    . esc_html__('Listing permanently deleted.', 'metra-classifieds-plugin')
                    . '</div>';
            } elseif ($flag === 'forbidden') {
                $notice = '<div class="mc-notice mc-notice-error" style="padding:10px 12px;border:1px solid #ffb4a2;background:#ffe5d9;border-radius:10px;">'
                    . esc_html__('You are not allowed to delete this listing.', 'metra-classifieds-plugin')
                    . '</div>';
            } elseif ($flag === 'nonce') {
                $notice = '<div class="mc-notice mc-notice-error" style="padding:10px 12px;border:1px solid #ffb4a2;background:#ffe5d9;border-radius:10px;">'
                    . esc_html__('Security check failed. Please try again.', 'metra-classifieds-plugin')
                    . '</div>';
            } else {
                $notice = '<div class="mc-notice mc-notice-error" style="padding:10px 12px;border:1px solid #ffb4a2;background:#ffe5d9;border-radius:10px;">'
                    . esc_html__('Could not delete listing.', 'metra-classifieds-plugin')
                    . '</div>';
            }
        }

        $is_owner = ((int) $post->post_author === (int) $user_id);
        $is_admin = current_user_can('manage_options');

        if (!$is_owner && !$is_admin) {
            self::safe_redirect_with_flag(self::page_url('my_listings') ?: home_url('/'), 'mc_deleted', 'forbidden');
            return;
        }

        // Delete attached media files (featured + gallery) from server.
        self::delete_listing_media($listing_id);

        // Permanent delete (no trash).
        wp_delete_post($listing_id, true);

        self::safe_redirect_with_flag(self::page_url('my_listings') ?: home_url('/'), 'mc_deleted', 'ok');
    }

    /**
     * Ensure listing images are deleted when a listing is deleted from anywhere
     * (e.g. WP Admin > Trash/Deleted).
     */
    public static function maybe_delete_listing_attachments(int $post_id): void {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== MC_CPT::CPT) {
            return;
        }

        self::delete_listing_media($post_id);
    }

    /**
     * Delete listing media (featured image + gallery attachments) physically.
     */
    private static function delete_listing_media(int $post_id): void {
        // Featured image
        $ids = [];
        $thumb_id = (int) get_post_thumbnail_id($post_id);
        if ($thumb_id > 0) {
            $ids[] = $thumb_id;
        }

        // Gallery images
        $gallery_ids = get_post_meta($post_id, 'mc_gallery_ids', true);
        if (is_array($gallery_ids)) {
            foreach ($gallery_ids as $aid) {
                $aid = (int) $aid;
                if ($aid > 0) {
                    $ids[] = $aid;
                }
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) {
            return;
        }

        foreach ($ids as $aid) {
            if (get_post_type($aid) === 'attachment') {
                // Force physical deletion of the file.
                wp_delete_attachment($aid, true);
            }
        }

        // Clean meta references
        delete_post_meta($post_id, 'mc_gallery_ids');
        delete_post_thumbnail($post_id);
    }

    /**
     * Helper: Redirect and attach a query flag for frontend notices.
     */

    /**
     * Render the status toggle link for a listing in My Listings.
     */
    private static function render_status_toggle_link(int $listing_id): string {
        $base_url = self::page_url('my_listings') ?: home_url('/');
        $toggle_url = add_query_arg([
            'mc_toggle_pause' => '1',
            'listing_id' => (string) $listing_id,
            '_mc_nonce' => wp_create_nonce('mc_toggle_pause_' . $listing_id),
        ], $base_url);

        if (self::is_listing_paused($listing_id)) {
            return '<a href="' . esc_url($toggle_url) . '" class="mc-status-toggle mc-status-toggle--paused">'
                . esc_html__('Paused! - Click to activate', 'metra-classifieds-plugin')
                . '</a>';
        }

        return '<a href="' . esc_url($toggle_url) . '" class="mc-status-toggle mc-status-toggle--active">'
            . esc_html__('Active! - Click to pause', 'metra-classifieds-plugin')
            . '</a>';
    }

    /**
     * Render a permanent delete link for a listing (My listings).
     */
    private static function render_delete_link(int $listing_id): string {
        $url = self::page_url('my_listings') ?: home_url('/');
        $url = add_query_arg([
            'mc_action'  => 'delete_listing',
            'listing_id' => (string) $listing_id,
            '_mc_nonce'  => wp_create_nonce('mc_delete_listing_' . $listing_id),
        ], $url);

        return '<a href="' . esc_url($url) . '" '
            . 'onclick="return confirm(' . json_encode(__('Do you really want to permanently delete this listing?', 'metra-classifieds-plugin')) . ');">'
            . esc_html__('Delete', 'metra-classifieds-plugin')
            . '</a>';
    }

    private static function safe_redirect_with_flag(string $url, string $key, string $value): void {
        $url = add_query_arg([$key => $value], $url);
        wp_safe_redirect($url);
        exit;
    }
/**
 * Shortcode: [mc_login]
 */
public static function login_page(): string {
    $login_url = MC_Pages::get_page_url('login');
    if ($login_url === '') {
        $login_url = home_url('/mc-login/');
    }

    if (is_user_logged_in()) {
        $dash = MC_Pages::get_page_url('dashboard');
        if ($dash === '') {
            $dash = home_url('/');
        }
        $out  = '<div class="mc-card" style="max-width:720px;margin:0 auto;">';
        $out .= '<h2 class="mc-title">' . esc_html__('Sie sind angemeldet.', 'metra-classifieds-plugin') . '</h2>';
        $out .= '<p><a class="button button-primary" href="' . esc_url($dash) . '">' . esc_html__('Go to dashboard', 'metra-classifieds-plugin') . '</a> ';
        $out .= '<a class="button" href="' . esc_url(wp_logout_url($login_url)) . '">' . esc_html__('Logout', 'metra-classifieds-plugin') . '</a></p>';
        $out .= '</div>';
        return $out;
    }

    $action = isset($_GET['mc_action']) ? sanitize_key((string) $_GET['mc_action']) : '';
    $status = isset($_GET['mc_login']) ? sanitize_key((string) $_GET['mc_login']) : '';

    $redirect_to = isset($_GET['redirect_to']) ? esc_url_raw((string) $_GET['redirect_to']) : '';
    if ($redirect_to === '') {
        $redirect_to = MC_Pages::get_page_url('dashboard');
        if ($redirect_to === '') {
            $redirect_to = home_url('/');
        }
    }

    $return_url = home_url(isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/');

    $msg = '';
    $msg_ok = true;
    if ($status === 'reset_success') {
        $msg_ok = true;
        $msg = __('Password saved. Please sign in.', 'metra-classifieds-plugin');
    } elseif ($status === 'lost_sent') {
        $msg_ok = true;
        $msg = __('Please check your inbox. You will receive an email to reset your password.', 'metra-classifieds-plugin');
    } elseif ((strpos($status, 'lost_') === 0) || $status === 'fail') {
        $msg_ok = false;
        $msg = __('Process failed. Please check your entries.', 'metra-classifieds-plugin');
        if ($status === 'lost_invalid_email' || $status === 'lost_invalidcombo') {
            $msg = __('Unknown email address or username.', 'metra-classifieds-plugin');
        }
    } elseif ($status === 'incorrect_password' || $status === 'invalid_username') {
        $msg_ok = false;
        $msg = __('Username/email or password is incorrect.', 'metra-classifieds-plugin');
    } elseif ($status === 'mc_not_activated') {
        $msg_ok = false;
        $msg = __('Please confirm your registration by email first.', 'metra-classifieds-plugin');
    }

    $out  = '<div class="mc-card" style="max-width:720px;margin:0 auto;">';
    $out .= '<h2 class="mc-title">' . esc_html__('Login', 'metra-classifieds-plugin') . '</h2>';

    if ($msg !== '') {
        $out .= '<div style="margin:0 0 14px 0;padding:10px 12px;border-radius:12px;border:1px solid ' . ($msg_ok ? 'rgba(0,0,0,.12)' : 'rgba(214,54,56,.35)') . ';background:' . ($msg_ok ? 'rgba(46,125,50,.06)' : 'rgba(214,54,56,.06)') . ';color:' . ($msg_ok ? '#1b5e20' : '#7a1f22') . ';">' . esc_html($msg) . '</div>';
    }

    if ($action === 'lostpassword') {
        $out .= '<form method="post" style="max-width:520px;">';
        $out .= '<input type="hidden" name="mc_lostpw_action" value="1">';
        $out .= '<input type="hidden" name="mc_lostpw_nonce" value="' . esc_attr(wp_create_nonce('mc_lostpw')) . '">';
        $out .= '<input type="hidden" name="mc_return" value="' . esc_url($return_url) . '">';
        $out .= '<p><label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('Email address or username', 'metra-classifieds-plugin') . '</label>';
        $out .= '<input type="text" name="user_login" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;"></p>';
        $out .= '<p style="display:flex;gap:10px;justify-content:flex-end;margin:0;">'
            . '<a class="button" href="' . esc_url($login_url) . '">' . esc_html__('Back', 'metra-classifieds-plugin') . '</a>'
            . '<button type="submit" class="button button-primary">' . esc_html__('Request password reset', 'metra-classifieds-plugin') . '</button>'
            . '</p>';
        $out .= '</form>';

        $out .= '</div>';
        return $out;
    }

    // Default: login form.
    $out .= '<form method="post" style="max-width:520px;">';
    $out .= '<input type="hidden" name="mc_login_action" value="1">';
    $out .= '<input type="hidden" name="mc_login_nonce" value="' . esc_attr(wp_create_nonce('mc_login')) . '">';
    $out .= '<input type="hidden" name="redirect_to" value="' . esc_url($redirect_to) . '">';
    $out .= '<input type="hidden" name="mc_return" value="' . esc_url($return_url) . '">';
    $out .= '<p><label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('Username or email', 'metra-classifieds-plugin') . '</label>'
        . '<input type="text" name="log" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;"></p>';
    $out .= '<p><label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html__('Password', 'metra-classifieds-plugin') . '</label>'
        . '<input type="password" name="pwd" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;"></p>';
    $out .= '<p style="display:flex;align-items:center;justify-content:space-between;gap:10px;">'
        . '<label style="display:flex;align-items:center;gap:8px;margin:0;">'
        . '<input type="checkbox" name="rememberme" value="forever"> ' . esc_html__('Remember me', 'metra-classifieds-plugin')
        . '</label>'
        . '<a href="' . esc_url(add_query_arg('mc_action', 'lostpassword', $login_url)) . '">' . esc_html__('Forgot password?', 'metra-classifieds-plugin') . '</a>'
        . '</p>';

    $out .= '<p style="display:flex;gap:10px;justify-content:flex-end;margin:0;">'
        . '<a class="button" href="' . esc_url(MC_Pages::get_page_url('register') ?: home_url('/mc-register/')) . '">' . esc_html__('Register', 'metra-classifieds-plugin') . '</a>'
        . '<button type="submit" class="button button-primary">' . esc_html__('Sign in', 'metra-classifieds-plugin') . '</button>'
        . '</p>';
    $out .= '</form>';
    $out .= '</div>';
    return $out;
}

/**
 * Shortcode: [mc_reset_password]
 */
public static function reset_password_page(): string {
    $login  = isset($_GET['login']) ? sanitize_user((string) $_GET['login'], true) : '';
    $key    = isset($_GET['key']) ? sanitize_text_field((string) $_GET['key']) : '';
    $status = isset($_GET['mc_resetpw']) ? sanitize_key((string) $_GET['mc_resetpw']) : '';

    $label_title        = __('Reset password', 'metra-classifieds-plugin');
    $label_new_password = __('New password', 'metra-classifieds-plugin');
    $label_confirm      = __('Confirm password', 'metra-classifieds-plugin');
    $label_save         = __('Save password', 'metra-classifieds-plugin');
    $msg_invalid_or_exp = __('The link is invalid or expired. Please request a new link.', 'metra-classifieds-plugin');
    $msg_check_entries  = __('Please check your entries.', 'metra-classifieds-plugin');
    $msg_use_email_link = __('Please use the link from your email.', 'metra-classifieds-plugin');

    $out  = '<div class="mc-card" style="max-width:720px;margin:0 auto;">';
    $out .= '<h2 class="mc-title">' . esc_html($label_title) . '</h2>';

    if ($status === 'invalid') {
        $out .= '<div style="margin:0 0 14px 0;padding:10px 12px;border-radius:12px;border:1px solid rgba(214,54,56,.35);background:rgba(214,54,56,.06);color:#7a1f22;">'
            . esc_html($msg_invalid_or_exp)
            . '</div>';
    } elseif ($status === 'fail') {
        $out .= '<div style="margin:0 0 14px 0;padding:10px 12px;border-radius:12px;border:1px solid rgba(214,54,56,.35);background:rgba(214,54,56,.06);color:#7a1f22;">'
            . esc_html($msg_check_entries)
            . '</div>';
    }

    if ($login === '' || $key === '') {
        $out .= '<p>' . esc_html($msg_use_email_link) . '</p>';
        $out .= '</div>';
        return $out;
    }

    // Validate the key before rendering the form so the user sees immediate feedback.
    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user) || !$user instanceof \WP_User) {
        $out .= '<p>' . esc_html($msg_invalid_or_exp) . '</p>';
        $out .= '</div>';
        return $out;
    }

    $return_url = home_url(isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '/');

    $out .= '<form method="post" style="max-width:520px;">';
    $out .= '<input type="hidden" name="mc_resetpw_action" value="1">';
    $out .= '<input type="hidden" name="mc_resetpw_nonce" value="' . esc_attr(wp_create_nonce('mc_resetpw')) . '">';
    $out .= '<input type="hidden" name="mc_return" value="' . esc_url($return_url) . '">';
    $out .= '<input type="hidden" name="login" value="' . esc_attr($login) . '">';
    $out .= '<input type="hidden" name="key" value="' . esc_attr($key) . '">';
    $out .= '<p><label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html($label_new_password) . '</label>'
        . '<input type="password" name="pass1" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;"></p>';
    $out .= '<p><label style="display:block;font-weight:700;margin-bottom:6px;">' . esc_html($label_confirm) . '</label>'
        . '<input type="password" name="pass2" required style="width:100%;padding:10px;border:1px solid #ddd;border-radius:12px;"></p>';
    $out .= '<p style="text-align:right;margin:0;"><button type="submit" class="button button-primary">' . esc_html($label_save) . '</button></p>';
    $out .= '</form>';

    $out .= '</div>';
    return $out;
}

public static function maybe_handle_activation(): void {
    if (!isset($_GET['mc_activate'], $_GET['uid'])) {
        return;
    }
    $uid = (int) $_GET['uid'];
    $key = sanitize_text_field((string) $_GET['mc_activate']);
    if ($uid <= 0 || $key === '') {
        return;
    }
    $saved = (string) get_user_meta($uid, 'mc_activation_key', true);
    if ($saved && hash_equals($saved, $key)) {
        update_user_meta($uid, 'mc_activated', '1');
        delete_user_meta($uid, 'mc_activation_key');
        wp_safe_redirect(add_query_arg('mc_reg', 'activated', home_url('/')));
        exit;
    }
}

public static function block_unactivated_users($user, $username, $password) {
    if (is_wp_error($user) || !($user instanceof \WP_User)) {
        return $user;
    }
    $activated = (string) get_user_meta($user->ID, 'mc_activated', true);
    if ($activated === '0') {
        return new \WP_Error('mc_not_activated', __('Please confirm your registration by email first.', 'metra-classifieds-plugin'));
    }
    return $user;
}

}
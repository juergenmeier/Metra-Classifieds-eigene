<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read-only user profile page for the frontend dashboard.
 *
 * Step 4 extends the profile output with a dedicated Favorites section.
 * The section uses the already introduced favorites data layer and keeps all
 * listing permissions/runtime behavior unchanged: only published listings are
 * shown and the existing single-listing toggle URLs are reused.
 */
final class MC_Profile {

    public static function init(): void {
        add_action('wp_loaded', [self::class, 'maybe_handle_delete_profile']);
    }

    /**
     * Shortcode: [mc_my_profile]
     */
    public static function shortcode(): string {
        if (!is_user_logged_in()) {
            $login_url = wp_login_url(home_url('/mc-my-profile/'));
            return '<div class="mc-profile mc-not-logged-in">'
                . esc_html__('Please log in to view your profile.', 'metra-classifieds-plugin')
                . ' <a href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
        }

        $user_id = (int) get_current_user_id();
        $user    = get_userdata($user_id);

        if (!$user) {
            return '<div class="mc-profile mc-error">' . esc_html__('User not found.', 'metra-classifieds-plugin') . '</div>';
        }

        // Custom METRA fields (if present).
        $plz = (string) get_user_meta($user_id, 'mc_user_plz', true);
        $ort = (string) get_user_meta($user_id, 'mc_user_ort', true);

        $rows = [
            [__('Username', 'metra-classifieds-plugin'), (string) $user->user_login],
            [__('E-mail', 'metra-classifieds-plugin'), (string) $user->user_email],
            [__('Display name', 'metra-classifieds-plugin'), (string) $user->display_name],
        ];

        if ($plz !== '') { $rows[] = [__('ZIP', 'metra-classifieds-plugin'), $plz]; }
        if ($ort !== '') { $rows[] = [__('City', 'metra-classifieds-plugin'), $ort]; }

        $active_tab = self::get_active_tab();

        $out  = '<div class="mc-profile" style="max-width:960px;">';
        $out .= '<h2 style="margin:0 0 12px 0;">' . esc_html__('My Profile', 'metra-classifieds-plugin') . '</h2>';
        $out .= self::render_tab_navigation($active_tab);

        if ($active_tab === 'favorites') {
            $out .= self::render_favorites_section($user_id);
        } else {
            $out .= '<div style="border:1px solid rgba(0,0,0,.08);padding:14px;border-radius:12px;">';

            $first = true;
            foreach ($rows as $r) {
                $out .= '<div style="display:flex;gap:10px;align-items:flex-start;padding:8px 0;'
                    . ($first ? '' : 'border-top:1px solid rgba(0,0,0,.06);') . '">';
                $out .= '<div style="min-width:160px;font-weight:700;">' . esc_html((string) $r[0]) . '</div>';
                $out .= '<div>' . esc_html((string) $r[1]) . '</div>';
                $out .= '</div>';
                $first = false;
            }

            $out .= '</div>';
            $out .= self::render_delete_profile_section();
        }

        $out .= '</div>';
        return $out;
    }



    /**
     * Resolve the active profile tab from the request.
     *
     * We intentionally use a real query parameter instead of a hash anchor so
     * the favorites area gets a stable, shareable URL inside the profile flow.
     */
    private static function get_active_tab(): string {
        $tab = isset($_GET['tab']) ? sanitize_key((string) $_GET['tab']) : 'profile';
        return in_array($tab, ['profile', 'favorites'], true) ? $tab : 'profile';
    }

    /**
     * Render the profile/favorites navigation with real URLs.
     */
    private static function render_tab_navigation(string $active_tab): string {
        $profile_url   = self::get_tab_url('profile');
        $favorites_url = self::get_tab_url('favorites');

        $profile_style = $active_tab === 'profile'
            ? 'display:inline-block;padding:8px 12px;border-radius:8px;background:#1e88e5;color:#fff;text-decoration:none;'
            : 'display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid rgba(0,0,0,.12);text-decoration:none;';

        $favorites_style = $active_tab === 'favorites'
            ? 'display:inline-block;padding:8px 12px;border-radius:8px;background:#1e88e5;color:#fff;text-decoration:none;'
            : 'display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid rgba(0,0,0,.12);text-decoration:none;';

        $out  = '<nav class="mc-profile-tabs" style="display:flex;flex-wrap:wrap;gap:10px;margin:0 0 18px 0;">';
        $out .= '<a href="' . esc_url($profile_url) . '" style="' . esc_attr($profile_style) . '">' . esc_html__('My Profile', 'metra-classifieds-plugin') . '</a>';
        $out .= '<a href="' . esc_url($favorites_url) . '" style="' . esc_attr($favorites_style) . '">' . esc_html__('Favorites', 'metra-classifieds-plugin') . '</a>';
        $out .= '</nav>';
        return $out;
    }

    /**
     * Build a stable profile tab URL.
     */
    public static function get_tab_url(string $tab = 'profile'): string {
        $base_url = site_url('/mc-profile/');
        if ($tab === 'favorites') {
            return add_query_arg('tab', 'favorites', $base_url);
        }

        return remove_query_arg('tab', $base_url);
    }

    /**
     * Render the dedicated favorites block below the standard profile data.
     *
     * The user asked for a profile option named "Favoriten" / "Favorites".
     * We keep the implementation universal by rendering a standalone section on
     * the existing profile page and by exposing a direct dashboard link to the
     * anchor of this section.
     */

    private static function render_delete_profile_section(): string {
        $action = esc_url(self::get_tab_url('profile'));
        $confirm = esc_js(__('Do you really want to delete your profile? All your listings and attachments will be deleted automatically.', 'metra-classifieds-plugin'));

        $out  = '<div class="mc-profile-delete" style="margin-top:34px;padding-top:24px;border-top:1px solid rgba(0,0,0,.08);">';
        $out .= '<h3 style="margin:0 0 10px 0;">' . esc_html__('Delete profile', 'metra-classifieds-plugin') . '</h3>';
        $out .= '<p style="margin:0 0 14px 0;">' . esc_html__('If you confirm this action, your profile, all your listings and related attachments such as images will be deleted automatically.', 'metra-classifieds-plugin') . '</p>';
        $out .= '<form method="post" action="' . $action . '" onsubmit="return confirm(\'' . $confirm . '\');">';
        $out .= wp_nonce_field('mc_delete_profile', 'mc_delete_profile_nonce', true, false);
        $out .= '<input type="hidden" name="mc_delete_profile" value="1" />';
        $out .= '<button type="submit" class="button" style="background:#b42318;border-color:#b42318;color:#fff;">' . esc_html__('Delete profile', 'metra-classifieds-plugin') . '</button>';
        $out .= '</form>';
        $out .= '</div>';

        return $out;
    }

    public static function maybe_handle_delete_profile(): void {
        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_SERVER['REQUEST_METHOD']) || strtoupper((string) $_SERVER['REQUEST_METHOD']) !== 'POST') {
            return;
        }

        if (!isset($_POST['mc_delete_profile']) || (string) $_POST['mc_delete_profile'] !== '1') {
            return;
        }

        if (!isset($_POST['mc_delete_profile_nonce']) || !wp_verify_nonce((string) $_POST['mc_delete_profile_nonce'], 'mc_delete_profile')) {
            return;
        }

        $user_id = (int) get_current_user_id();
        if ($user_id <= 0) {
            return;
        }

        $listing_ids = get_posts([
            'post_type'      => MC_CPT::CPT,
            'author'         => $user_id,
            'post_status'    => ['publish', 'pending', 'draft', 'future', 'private', 'trash'],
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ]);

        foreach ($listing_ids as $listing_id) {
            $listing_id = (int) $listing_id;
            if ($listing_id <= 0) {
                continue;
            }

            MC_Shortcodes::maybe_delete_listing_attachments($listing_id);
            wp_delete_post($listing_id, true);
        }

        $attachment_ids = get_posts([
            'post_type'      => 'attachment',
            'author'         => $user_id,
            'post_status'    => 'inherit',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ]);

        foreach ($attachment_ids as $attachment_id) {
            $attachment_id = (int) $attachment_id;
            if ($attachment_id > 0) {
                wp_delete_attachment($attachment_id, true);
            }
        }

        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        wp_delete_user($user_id);
        wp_logout();

        wp_safe_redirect(add_query_arg('mc_profile_deleted', '1', home_url('/')));
        exit;
    }

    public static function favorites_shortcode(): string {
        if (!is_user_logged_in()) {
            $login_target = MC_Pages::get_page_url('favorites');
            if ($login_target === '') {
                $login_target = home_url('/mc-favorites/');
            }
            $login_url = wp_login_url($login_target);
            return '<div class="mc-profile mc-not-logged-in">'
                . esc_html__('Please log in to view your favorites.', 'metra-classifieds-plugin')
                . ' <a href="' . esc_url($login_url) . '">' . esc_html__('Log in', 'metra-classifieds-plugin') . '</a>'
                . '</div>';
        }

        return self::render_favorites_section((int) get_current_user_id());
    }

    public static function render_favorites_section(int $user_id): string {
        $favorite_ids = MC_Favorites::get_user_favorites($user_id);
        $favorite_ids = array_values(array_filter(array_map('intval', $favorite_ids), static function (int $listing_id): bool {
            return $listing_id > 0 && get_post_type($listing_id) === MC_CPT::CPT && get_post_status($listing_id) === 'publish';
        }));

        $out  = '<div class="mc-profile-favorites">';
        $out .= '<h3 style="margin:0 0 12px 0;">' . esc_html__('Favorites', 'metra-classifieds-plugin') . '</h3>';

        if (empty($favorite_ids)) {
            $out .= '<div style="border:1px solid rgba(0,0,0,.08);padding:14px;border-radius:12px;">'
                 . esc_html__('You have not marked any listings as favorites yet.', 'metra-classifieds-plugin')
                 . '</div>';
            $out .= '</div>';
            return $out;
        }

        $out .= '<div style="display:grid;gap:12px;">';
        foreach ($favorite_ids as $listing_id) {
            $out .= self::render_favorite_card($listing_id, $user_id);
        }
        $out .= '</div>';
        $out .= '</div>';
        return $out;
    }

    /**
     * Render one favorite listing card for the profile page.
     *
     * We intentionally keep this output compact and self-contained instead of
     * reusing the full archive renderer. This avoids side effects on the public
     * listing pages and keeps the profile section stable.
     */
    private static function render_favorite_card(int $listing_id, int $user_id): string {
        $title = get_the_title($listing_id);
        $url   = get_permalink($listing_id);

        $terms = get_the_terms($listing_id, MC_CPT::TAX);
        $category_label = '';
        if (is_array($terms) && !empty($terms) && !is_wp_error($terms)) {
            $category_label = (string) $terms[0]->name;
        }

        $date_label = (string) get_the_date('', $listing_id);
        $price_raw  = get_post_meta($listing_id, 'mc_price', true);
        $price_html = '';
        if ($price_raw !== '' && is_numeric((string) $price_raw)) {
            $price_html = '<div style="font-size:18px;font-weight:600;">' . esc_html(number_format_i18n((int) $price_raw)) . ' €</div>';
        }

        $thumb_html = '';
        if (has_post_thumbnail($listing_id)) {
            $thumb_html = get_the_post_thumbnail(
                $listing_id,
                [120, 90],
                [
                    'style'   => 'width:120px;height:90px;object-fit:cover;border-radius:8px;display:block;',
                    'loading' => 'lazy',
                ]
            );
        } else {
            $thumb_html = '<div aria-hidden="true" style="width:120px;height:90px;border-radius:8px;background:rgba(0,0,0,.05);"></div>';
        }

        $meta_bits = [];
        if ($category_label !== '') {
            $meta_bits[] = esc_html($category_label);
        }
        if ($date_label !== '') {
            $meta_bits[] = esc_html($date_label);
        }
        $meta_html = empty($meta_bits)
            ? ''
            : '<div style="font-size:15px;opacity:.8;">' . implode(' · ', $meta_bits) . '</div>';

        $is_favorite = MC_Favorites::is_favorite($user_id, $listing_id);
        $toggle_url = $is_favorite ? '' : MC_Favorites::get_toggle_url($listing_id);
        $toggle_label = $is_favorite
            ? __('Favorite', 'metra-classifieds-plugin')
            : __('Set as Favorite', 'metra-classifieds-plugin');

        $out  = '<article class="mc-profile-favorite-card" style="border:1px solid rgba(0,0,0,.08);padding:14px;border-radius:12px;">';
        $out .= '<div style="display:grid;grid-template-columns:120px 1fr;gap:14px;align-items:start;">';
        $out .= '<div><a href="' . esc_url((string) $url) . '">' . $thumb_html . '</a></div>';
        $out .= '<div style="display:grid;gap:8px;">';
        $out .= '<div><a href="' . esc_url((string) $url) . '" style="font-weight:700;text-decoration:none;">' . esc_html((string) $title) . '</a></div>';
        $out .= MC_Listing_ID::render($listing_id);
        $out .= $meta_html;
        $out .= $price_html;
        $out .= '<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">';
        $out .= '<a href="' . esc_url((string) $url) . '">' . esc_html__('View listing', 'metra-classifieds-plugin') . '</a>';
        if ($is_favorite) {
            $out .= '<span style="display:inline-block;padding:8px 12px;border-radius:8px;background:#2e7d32;color:#fff;">' . esc_html($toggle_label) . '</span>';
        } elseif ($toggle_url !== '') {
            $out .= '<a href="' . esc_url($toggle_url) . '" style="display:inline-block;padding:8px 12px;border-radius:8px;background:#2e7d32;color:#fff;text-decoration:none;">' . esc_html($toggle_label) . '</a>';
        }
        $out .= '</div>';
        $out .= '</div>';
        $out .= '</div>';
        $out .= '</article>';
        return $out;
    }
}

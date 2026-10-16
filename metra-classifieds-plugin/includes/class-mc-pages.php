<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Required Pages manager.
 *
 * Rule: all pages required for operating the plugin must be created automatically
 * on activation (and repaired later if missing).
 */
final class MC_Pages {

    /** Option name that stores an associative array of required page IDs. */
    private const OPTION_PAGE_IDS = 'mc_required_page_ids';

    /** Internal lock option to avoid repeated page creation. */
    private const OPTION_LAST_ENSURE = 'mc_required_pages_last_ensure';

    /** Option name that stores reserved-slug conflicts (e.g. a *post* occupying /mc-login/). */
    private const OPTION_PAGE_CONFLICTS = 'mc_required_pages_conflicts';

    /**
     * Get a WP_Post by slug, looking at both pages and posts.
     *
     * Why: admins sometimes accidentally create a *post* with a reserved METRA slug
     * like /mc-login/. Then the URL resolves to the post and our page template
     * filter does not run. We do NOT auto-convert content types here (too risky).
     * Instead we detect the conflict and show an admin notice with a clear fix.
     */
    private static function get_post_or_page_by_path(string $slug): ?\WP_Post {
        $slug = sanitize_title($slug);
        $p = get_page_by_path($slug, OBJECT, ['page', 'post']);
        return ($p instanceof \WP_Post) ? $p : null;
    }

    /**
     * Ensure required pages exist early, if they are missing.
     *
     * Why: On some deployments the plugin is updated by replacing files (FTP/ZIP)
     * without re-running the activation hook. In that case, required pages (like
     * /mc-login/) might not exist yet. This method is a safe, lightweight
     * self-healing mechanism.
     */
    public static function ensure_required_pages_if_missing(): void {
        if (!function_exists('get_page_by_path') || !function_exists('wp_insert_post')) {
            return;
        }

        // If we already ensured recently, do nothing.
        // Quick check for the most critical pages.
        // NOTE: include the submit-category wizard, because deployments that
        // update plugins by replacing files (FTP/ZIP) may not re-run activation.
        // Without this page, the submit flow would redirect to a 404.
        $critical_slugs = ['mc-login', 'mc-register', 'mc-set-password', 'mc-submit', 'mc-submit-category', 'mc-submit-pending', 'mc-action-required', 'mc-categories-list'];
        foreach ($critical_slugs as $slug) {
            $p = self::get_post_or_page_by_path($slug);
            // Missing page OR reserved slug is occupied by a non-page content type.
            if (!($p instanceof \WP_Post) || $p->post_status === 'trash' || $p->post_type !== 'page') {
                self::ensure_required_pages();
                return;
            }
        }

        // If all critical pages exist, just update timestamp.
        update_option(self::OPTION_LAST_ENSURE, time(), false);
    }

    /**
     * Enforce the category drilldown wizard before allowing /mc-submit/.
     *
     * Why: A redirect inside the shortcode callback runs too late (theme output
     * may already have started). This must be handled on template_redirect.
     */
    public static function maybe_redirect_submit_to_category_wizard(): void {
        if (!function_exists('is_page')) {
            return;
        }

        // Only affect the METRA submit page.
        if (!is_page('mc-submit')) {
            return;
        }

        // If a category was already selected, allow the form page.
        $cat_id = isset($_GET['mc_cat']) ? absint((string) $_GET['mc_cat']) : 0;
        if ($cat_id > 0) {
            return;
        }

        // If the form was posted (validation error etc.), do not redirect.
        if (isset($_POST['mc_submit_form'])) {
            return;
        }

        $wiz = self::get_page_url('submit_category');
        if ($wiz === '') {
            $wiz = home_url('/mc-submit-category/');
        }

        wp_safe_redirect($wiz);
        exit;
    }

    /**
     * Default required pages. Developers can extend via filter `mc_required_pages`.
     *
     * @return array<string, array{title:string, slug:string, content:string, status:string}>
     */
    private static function get_required_pages(): array {
        // NOTE: all slugs are without leading/trailing slashes.
        $pages = [
            'login' => [
                'title'   => __('Login', 'metra-classifieds-plugin'),
                'slug'    => 'mc-login',
                'content' => "[mc_login]",
                'status'  => 'publish',
            ],

            'register' => [
                'title'   => __('Register', 'metra-classifieds-plugin'),
                'slug'    => 'mc-register',
                'content' => "[mc_register]",
                'status'  => 'publish',
            ],

            'reset_password' => [
                'title'   => __('Reset password', 'metra-classifieds-plugin'),
                'slug'    => 'mc-reset-password',
                'content' => "[mc_reset_password]",
                'status'  => 'publish',
            ],

            'dashboard' => [
                'title'   => __('My dashboard', 'metra-classifieds-plugin'),
                'slug'    => 'mc-dashboard',
                'content' => "[mc_dashboard]",
                'status'  => 'publish',
            ],

            'profile' => [
                'title'   => __('My Profile', 'metra-classifieds-plugin'),
                'slug'    => 'mc-profile',
                'content' => "[mc_my_profile]",
                'status'  => 'publish',
            ],

            'my_profile' => [
                'title'   => __('My Profile', 'metra-classifieds-plugin'),
                'slug'    => 'mc-my-profile',
                'content' => "[mc_my_profile]",
                'status'  => 'publish',
            ],
            'favorites' => [
                'title'   => __('Favorites', 'metra-classifieds-plugin'),
                'slug'    => 'mc-favorites',
                'content' => "[mc_favorites]",
                'status'  => 'publish',
            ],
            'guest_action_required' => [
                'title'   => __('Login or register', 'metra-classifieds-plugin'),
                'slug'    => 'mc-action-required',
                'content' => "[mc_guest_action_required]",
                'status'  => 'publish',
            ],
            'submit' => [
                'title'   => __('Post a listing', 'metra-classifieds-plugin'),
                'slug'    => 'mc-submit',
                'content' => "[mc_submit_form]",
                'status'  => 'publish',
            ],
            'submit_pending' => [
                'title'   => __('Listing pending approval', 'metra-classifieds-plugin'),
                'slug'    => 'mc-submit-pending',
                'content' => "[mc_submit_pending]",
                'status'  => 'publish',
            ],

            // Category drilldown wizard shown *before* the actual submit form.
            // Users select a final (leaf) category here. The selected term id is
            // then passed to /mc-submit/ via querystring (mc_cat).
            'submit_category' => [
                'title'   => __('Choose category', 'metra-classifieds-plugin'),
                'slug'    => 'mc-submit-category',
                'content' => "[mc_submit_category]",
                'status'  => 'publish',
            ],
            'listings' => [
                'title'   => __('Listings', 'metra-classifieds-plugin'),
                'slug'    => 'mc-listings',
                'content' => "[mc_listings]",
                'status'  => 'publish',
            ],
            'categories' => [
                'title'   => __('Categories', 'metra-classifieds-plugin'),
                'slug'    => 'mc-categories',
                'content' => "[mc_categories_grid]",
                'status'  => 'publish',
            ],
            'categories_list' => [
                'title'   => __('Categories List', 'metra-classifieds-plugin'),
                'slug'    => 'mc-categories-list',
                'content' => "[mc_categories_list]",
                'status'  => 'publish',
            ],
            // Page-based category browsing (querystring), as a stable fallback even if theme templates are minimal.
            'category_listings' => [
                'title'   => __('Browse category', 'metra-classifieds-plugin'),
                'slug'    => 'mc-category',
                'content' => "[mc_category_listings]",
                'status'  => 'publish',
            ],
            // Page-based single listing view (querystring), as a stable fallback.
            'view' => [
                'title'   => __('View listing', 'metra-classifieds-plugin'),
                'slug'    => 'mc-view',
                'content' => "[mc_listing]",
                'status'  => 'publish',
            ],
            'edit' => [
                'title'   => __('Edit listing', 'metra-classifieds-plugin'),
                'slug'    => 'mc-edit',
                'content' => "[mc_edit_listing]",
                'status'  => 'publish',
            ],
            'my_listings' => [
                'title'   => __('My listings', 'metra-classifieds-plugin'),
                'slug'    => 'mc-my-listings',
                'content' => "[mc_my_listings]",
                'status'  => 'publish',
            ],

            // Password setup page (used after email activation).
            // NOTE: This intentionally does NOT use wp-login.php?action=rp...
            // so we keep the entire onboarding flow inside METRA.
            'set_password' => [
                'title'   => __('Set password', 'metra-classifieds-plugin'),
                'slug'    => 'mc-set-password',
                'content' => "[mc_set_password]",
                'status'  => 'publish',
            ],
        ];

        /**
         * Filter required pages.
         *
         * @param array<string, array{title:string, slug:string, content:string, status:string}> $pages
         */
        $pages = (array) apply_filters('mc_required_pages', $pages);

        return $pages;
    }

    /**
     * Ensure pages exist (create if missing). Safe to call multiple times.
     */
    public static function ensure_required_pages(): void {
        if (!function_exists('wp_insert_post')) {
            return;
        }

        $required = self::get_required_pages();
        $stored_ids = get_option(self::OPTION_PAGE_IDS, []);
        if (!is_array($stored_ids)) {
            $stored_ids = [];
        }

        $conflicts = [];

        foreach ($required as $key => $def) {
            $page_id = isset($stored_ids[$key]) ? (int) $stored_ids[$key] : 0;

            // Validate stored id.
            if ($page_id > 0) {
                $p = get_post($page_id);
                if ($p && $p->post_type === 'page' && $p->post_status !== 'trash') {
                    continue;
                }
            }

            // Try find by slug.
            $existing = self::get_post_or_page_by_path((string) $def['slug']);
            if ($existing instanceof \WP_Post) {
                // Clean behavior: never convert posts to pages automatically.
                // If the slug is occupied by a non-page, record the conflict and skip.
                if ($existing->post_type !== 'page') {
                    $conflicts[(string) $def['slug']] = [
                        'id'    => (int) $existing->ID,
                        'type'  => (string) $existing->post_type,
                        'title' => (string) $existing->post_title,
                    ];
                    continue;
                }

                // Existing is a page: accept it.
                if ($existing->post_status !== 'trash') {
                    $stored_ids[$key] = (int) $existing->ID;
                    continue;
                }
            }

            // Create new page.
            $new_id = wp_insert_post([
                'post_type'    => 'page',
                'post_status'  => $def['status'] ?? 'publish',
                'post_title'   => $def['title'],
                'post_name'    => sanitize_title($def['slug']),
                'post_content' => $def['content'],
            ], true);

            if (!is_wp_error($new_id) && (int) $new_id > 0) {
                $stored_ids[$key] = (int) $new_id;
            }
        }

        update_option(self::OPTION_PAGE_IDS, $stored_ids, false);

        // Store reserved-slug conflicts to show an admin notice.
        update_option(self::OPTION_PAGE_CONFLICTS, $conflicts, false);

        // Keep legacy fallback option in sync.
        if (!empty($stored_ids['submit'])) {
            $submit_url = get_permalink((int) $stored_ids['submit']);
            if (is_string($submit_url) && $submit_url !== '') {
                update_option('mc_submit_page_url', $submit_url, false);
            }
        }

        update_option(self::OPTION_LAST_ENSURE, time(), false);
    }

    /**
     * Lightweight safeguard: ensure pages exist once per day in admin.
     */
    public static function maybe_ensure_required_pages(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        self::ensure_required_pages();
    }

    /**
     * Admin notice for reserved slug conflicts.
     */
    public static function admin_notices(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $conflicts = get_option(self::OPTION_PAGE_CONFLICTS, []);
        if (!is_array($conflicts) || empty($conflicts)) {
            return;
        }

        $items = [];
        foreach ($conflicts as $slug => $info) {
            if (!is_array($info)) {
                continue;
            }
            $id = isset($info['id']) ? (int) $info['id'] : 0;
            $type = isset($info['type']) ? sanitize_text_field((string) $info['type']) : 'post';
            $title = isset($info['title']) ? sanitize_text_field((string) $info['title']) : '';
            if ($id <= 0) {
                continue;
            }
            $edit = get_edit_post_link($id);
            $label = sprintf('/%s/ (%s)', sanitize_title((string) $slug), $type);
            if (is_string($edit) && $edit !== '') {
                $label = sprintf('<a href="%s">%s</a>', esc_url($edit), esc_html($label));
            } else {
                $label = esc_html($label);
            }
            if ($title !== '') {
                $label .= ' – ' . esc_html($title);
            }
            $items[] = $label;
        }

        if (empty($items)) {
            return;
        }

        echo '<div class="notice notice-warning"><p><strong>METRA Classifieds:</strong> '; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo esc_html__('A required METRA page slug is currently occupied by a non-page content type. Please delete or rename the conflicting post and keep the reserved slug for a WordPress page.', 'metra-classifieds-plugin');
        echo '</p><ul style="margin-left:1.2em; list-style:disc;">';
        foreach ($items as $li) {
            echo '<li>' . $li . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        echo '</ul><p>';
        echo esc_html__('After fixing, re-save Permalinks or simply revisit this page; METRA will recreate missing required pages automatically.', 'metra-classifieds-plugin');
        echo '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Get a required page URL by key.
     */
    public static function get_page_url(string $key): string {
        $ids = get_option(self::OPTION_PAGE_IDS, []);

        // If the stored mapping is missing (e.g. plugin updated), attempt a safe self-heal
        // by looking up the page by its expected slug and persisting the ID.
        if (!is_array($ids)) {
            $ids = [];
        }

        if (empty($ids[$key])) {
            $pages = self::get_required_pages();
            if (!empty($pages[$key]['slug'])) {
                $slug = (string) $pages[$key]['slug'];
                $page = get_page_by_path($slug);
                if ($page instanceof WP_Post && !empty($page->ID)) {
                    $ids[$key] = (int) $page->ID;
                    update_option(self::OPTION_PAGE_IDS, $ids, false);
                }
            }
        }

        if (empty($ids[$key])) {
            return '';
        }

        $url = get_permalink((int) $ids[$key]);
        return is_string($url) ? $url : '';
    }

    /**
     * Redirect native mc_category term archives to the METRA category listings page.
     *
     * This guarantees that category clicks always land on the unified METRA listings layout
     * instead of falling back to theme-specific taxonomy archive templates.
     */
    public static function maybe_redirect_category_archive(): void {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (!function_exists('is_tax') || !is_tax(MC_CPT::TAX)) {
            return;
        }

        $term = get_queried_object();
        if (!$term instanceof \WP_Term || $term->taxonomy !== MC_CPT::TAX) {
            return;
        }

        $target = self::get_page_url('category_listings');
        if ($target === '') {
            return;
        }

        $url = add_query_arg(['cat' => (string) $term->term_id], $target);
        wp_safe_redirect($url, 302);
        exit;
    }

    /**
     * Redirect legacy page-based view (/mc-view/?listing_id=123) to the canonical single listing permalink.
     *
     * This keeps the old endpoint as a fallback, but avoids duplicate "View listing" pages.
     */
    public static function maybe_redirect_legacy_view(): void {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return;
        }

        if (!isset($_GET['listing_id'])) {
            return;
        }

        $listing_id = (int) $_GET['listing_id'];
        if ($listing_id <= 0) {
            return;
        }

        // Only act on the legacy view page.
        if (!is_page()) {
            return;
        }

        $queried = get_queried_object();
        if (!$queried instanceof \WP_Post) {
            return;
        }

        $is_legacy_view = ($queried->post_name === 'mc-view');
        if (!$is_legacy_view) {
            // Also accept the stored required page id, if available.
            $ids = get_option(self::OPTION_PAGE_IDS, []);
            if (is_array($ids) && !empty($ids['view']) && (int) $ids['view'] === (int) $queried->ID) {
                $is_legacy_view = true;
            }
        }

        if (!$is_legacy_view) {
            return;
        }

        $post = get_post($listing_id);
        if (!$post instanceof \WP_Post || $post->post_type !== 'mc_listing') {
            $fallback = self::get_page_url('listings');
            if ($fallback === '') {
                $fallback = home_url('/');
            }
            wp_safe_redirect($fallback, 302);
            exit;
        }

        // Respect permissions for non-public posts.
        $status = get_post_status($post);
        if ($status !== 'publish' && !current_user_can('edit_post', $listing_id)) {
            $fallback = self::get_page_url('listings');
            if ($fallback === '') {
                $fallback = home_url('/');
            }
            wp_safe_redirect($fallback, 302);
            exit;
        }

        $url = get_permalink($listing_id);
        if (!is_string($url) || $url === '') {
            $fallback = self::get_page_url('listings');
            if ($fallback === '') {
                $fallback = home_url('/');
            }
            wp_safe_redirect($fallback, 302);
            exit;
        }

        wp_safe_redirect($url, 302);
        exit;
    }
}

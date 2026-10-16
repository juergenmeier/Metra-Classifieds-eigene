<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Favorites storage layer.
 *
 * Step 1 only: provide a documented, self-contained data layer that stores
 * favorite listing IDs per user without changing any existing frontend flow yet.
 *
 * Storage strategy:
 * - user_meta key: mc_favorite_listing_ids
 * - value: array<int> of mc_listing post IDs
 *
 * Why user_meta:
 * - no schema migration required
 * - keeps listing data untouched
 * - simple to extend later for profile output and favorite buttons
 */
final class MC_Favorites {

    /** @var string User meta key used to store favorite listing IDs. */
    private const META_KEY = 'mc_favorite_listing_ids';

    /**
     * Bootstrap favorites runtime hooks.
     *
     * Step 2 adds the first visible frontend interaction: a logged-in user can
     * toggle the favorite state of a single listing via a nonce-protected URL.
     * The profile output itself will follow in a later step.
     */
    public static function init(): void {
        add_action('wp_loaded', [self::class, 'maybe_handle_toggle_request']);

        // AJAX toggle endpoint for the modern favorites heart used in listing grids.
        // This keeps the classic link-based single-listing button intact while allowing
        // instant in-grid favorite toggling without a page reload.
        add_action('wp_ajax_mc_toggle_favorite', [self::class, 'ajax_toggle_favorite']);
    }

    /**
     * Handle the favorite toggle request on normal frontend page loads.
     *
     * Request shape:
     * - mc_action=toggle_favorite
     * - listing_id=<mc_listing post ID>
     * - _mc_nonce=<nonce>
     *
     * We redirect back to the listing page without action parameters so the
     * frontend remains clean after the action has been processed.
     */
    public static function maybe_handle_toggle_request(): void {
        if (!is_user_logged_in()) {
            return;
        }

        $action = isset($_GET['mc_action']) ? sanitize_key((string) $_GET['mc_action']) : '';
        if ($action !== 'toggle_favorite') {
            return;
        }

        $listing_id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
        $nonce      = isset($_GET['_mc_nonce']) ? (string) $_GET['_mc_nonce'] : '';

        if ($listing_id <= 0 || $nonce === '' || !wp_verify_nonce($nonce, 'mc_toggle_favorite_' . $listing_id)) {
            self::redirect_after_toggle($listing_id);
        }

        self::toggle_favorite((int) get_current_user_id(), $listing_id);
        self::redirect_after_toggle($listing_id);
    }


    /**
     * AJAX endpoint used by the listing-grid heart toggle.
     *
     * Response payload:
     * - success: true/false
     * - data.is_favorite: current state after the toggle
     * - data.listing_id: affected listing ID
     *
     * Security:
     * - logged-in users only
     * - standard WordPress AJAX nonce
     */
    public static function ajax_toggle_favorite(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Please log in first.', 'metra-classifieds-plugin'),
            ], 401);
        }

        check_ajax_referer('mc_toggle_favorite_ajax', 'nonce');

        $listing_id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        if ($listing_id <= 0 || get_post_type($listing_id) !== 'mc_listing') {
            wp_send_json_error([
                'message' => __('Invalid listing.', 'metra-classifieds-plugin'),
            ], 400);
        }

        $state = self::toggle_favorite((int) get_current_user_id(), $listing_id);
        if ($state === null) {
            wp_send_json_error([
                'message' => __('Favorite could not be updated.', 'metra-classifieds-plugin'),
            ], 500);
        }

        wp_send_json_success([
            'is_favorite' => (bool) $state,
            'listing_id'  => $listing_id,
            'label'       => $state
                ? __('Favorite', 'metra-classifieds-plugin')
                : __('Set as Favorite', 'metra-classifieds-plugin'),
        ]);
    }

    /**
     * Build a nonce-protected frontend URL for toggling one listing favorite.
     *
     * @param int $listing_id
     * @return string
     */
    public static function get_toggle_url(int $listing_id): string {
        if ($listing_id <= 0) {
            return '';
        }

        return (string) add_query_arg([
            'mc_action'  => 'toggle_favorite',
            'listing_id' => (string) $listing_id,
            '_mc_nonce'  => wp_create_nonce('mc_toggle_favorite_' . $listing_id),
        ], get_permalink($listing_id));
    }

    /**
     * Return all favorite listing IDs for a user.
     *
     * @param int $user_id
     * @return int[]
     */
    public static function get_user_favorites(int $user_id): array {
        if ($user_id <= 0) {
            return [];
        }

        $raw = get_user_meta($user_id, self::META_KEY, true);
        if (!is_array($raw)) {
            return [];
        }

        return self::sanitize_listing_ids($raw);
    }

    /**
     * Check whether a specific listing is already a favorite of a user.
     *
     * @param int $user_id
     * @param int $listing_id
     * @return bool
     */
    public static function is_favorite(int $user_id, int $listing_id): bool {
        if ($user_id <= 0 || $listing_id <= 0) {
            return false;
        }

        return in_array($listing_id, self::get_user_favorites($user_id), true);
    }

    /**
     * Add a listing to the user's favorites.
     *
     * @param int $user_id
     * @param int $listing_id
     * @return bool True if the favorites set was stored successfully.
     */
    public static function add_favorite(int $user_id, int $listing_id): bool {
        if (!self::can_store_favorite($user_id, $listing_id)) {
            return false;
        }

        $favorites = self::get_user_favorites($user_id);
        if (!in_array($listing_id, $favorites, true)) {
            $favorites[] = $listing_id;
        }

        return self::store_user_favorites($user_id, $favorites);
    }

    /**
     * Remove a listing from the user's favorites.
     *
     * @param int $user_id
     * @param int $listing_id
     * @return bool True if the favorites set was stored successfully.
     */
    public static function remove_favorite(int $user_id, int $listing_id): bool {
        if ($user_id <= 0 || $listing_id <= 0) {
            return false;
        }

        $favorites = array_values(array_filter(
            self::get_user_favorites($user_id),
            static fn (int $id): bool => $id !== $listing_id
        ));

        return self::store_user_favorites($user_id, $favorites);
    }

    /**
     * Toggle a listing's favorite status for a user.
     *
     * @param int $user_id
     * @param int $listing_id
     * @return bool|null True = now favorite, false = removed, null = invalid request.
     */
    public static function toggle_favorite(int $user_id, int $listing_id): ?bool {
        if ($user_id <= 0 || $listing_id <= 0) {
            return null;
        }

        if (self::is_favorite($user_id, $listing_id)) {
            return self::remove_favorite($user_id, $listing_id) ? false : null;
        }

        return self::add_favorite($user_id, $listing_id) ? true : null;
    }

    /**
     * Remove a deleted listing ID from all user favorite lists.
     *
     * This helper is documented and ready for later activation if cleanup across
     * all users becomes necessary. It is intentionally not wired into any global
     * delete hook in step 1 to avoid changing unrelated runtime behavior.
     *
     * @param int $listing_id
     */
    public static function purge_deleted_listing_from_all_users(int $listing_id): void {
        if ($listing_id <= 0) {
            return;
        }

        $user_query = new \WP_User_Query([
            'fields'     => 'ID',
            'number'     => 500,
            'meta_query' => [
                [
                    'key'     => self::META_KEY,
                    'compare' => 'EXISTS',
                ],
            ],
        ]);

        $user_ids = $user_query->get_results();
        if (!is_array($user_ids) || empty($user_ids)) {
            return;
        }

        foreach ($user_ids as $user_id) {
            self::remove_favorite((int) $user_id, $listing_id);
        }
    }


    /**
     * Redirect back to the cleaned single-listing URL after a toggle request.
     *
     * @param int $listing_id
     * @return never
     */
    private static function redirect_after_toggle(int $listing_id): void {
        $target = $listing_id > 0 ? get_permalink($listing_id) : home_url('/');
        if (!is_string($target) || $target === '') {
            $target = home_url('/');
        }

        wp_safe_redirect($target);
        exit;
    }

    /**
     * Validate whether the given listing can be stored as a favorite.
     *
     * @param int $user_id
     * @param int $listing_id
     * @return bool
     */
    private static function can_store_favorite(int $user_id, int $listing_id): bool {
        if ($user_id <= 0 || $listing_id <= 0) {
            return false;
        }

        return get_post_type($listing_id) === 'mc_listing';
    }

    /**
     * Persist the sanitized favorites array for a user.
     *
     * @param int   $user_id
     * @param int[] $favorites
     * @return bool
     */
    private static function store_user_favorites(int $user_id, array $favorites): bool {
        if ($user_id <= 0) {
            return false;
        }

        $favorites = self::sanitize_listing_ids($favorites);

        return (bool) update_user_meta($user_id, self::META_KEY, $favorites);
    }

    /**
     * Normalize a raw list of IDs to unique positive mc_listing post IDs.
     *
     * @param array<mixed> $ids
     * @return int[]
     */
    private static function sanitize_listing_ids(array $ids): array {
        $clean = [];

        foreach ($ids as $id) {
            $listing_id = (int) $id;
            if ($listing_id <= 0) {
                continue;
            }
            if (get_post_type($listing_id) !== 'mc_listing') {
                continue;
            }
            $clean[] = $listing_id;
        }

        $clean = array_values(array_unique($clean));
        sort($clean, SORT_NUMERIC);

        return $clean;
    }
}

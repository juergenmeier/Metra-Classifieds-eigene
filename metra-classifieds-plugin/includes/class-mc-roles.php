<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Roles / capabilities.
 *
 * Goal (per project decision):
 * - Subscribers start with WP role "subscriber".
 * - As soon as a user has at least one listing (publish or pending),
 *   they are moved to exactly one role: "mc_inserent".
 * - Admins keep their administrator role.
 */
final class MC_Roles {

    public const ROLE_INSERENT = 'mc_inserent';

    public static function init(): void {
        // Ensure role exists even if plugin files were deployed without a fresh activation run.
        add_action('init', [self::class, 'ensure_roles']);

        // Keep roles in sync with listings lifecycle.
        add_action('transition_post_status', [self::class, 'on_transition_post_status'], 20, 3);
        add_action('deleted_post', [self::class, 'on_deleted_post'], 20, 1);
        add_action('trashed_post', [self::class, 'on_trashed_post'], 20, 1);
        add_action('untrashed_post', [self::class, 'on_untrashed_post'], 20, 1);

        // One-time sync for existing data after updates.
        add_action('init', [self::class, 'maybe_run_one_time_sync'], 30);
    }

    public static function ensure_roles(): void {
        if (get_role(self::ROLE_INSERENT)) {
            return;
        }

        // Minimal capabilities: front-end access only.
        add_role(
            self::ROLE_INSERENT,
            __('Seller', 'metra-classifieds-plugin'),
            [
                'read' => true,
            ]
        );
    }

    /**
     * Promote a user to Inserent role (single role) once they created a listing.
     *
     * IMPORTANT:
     * - We do NOT touch administrators.
     * - We intentionally set exactly one role (as requested).
     */
    public static function maybe_set_inserent_role(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }
        $user = get_user_by('id', $user_id);
        if (!$user instanceof \WP_User) {
            return;
        }

        // Never change administrators.
        if (in_array('administrator', (array) $user->roles, true)) {
            return;
        }

        self::ensure_roles();

        // Set exactly one role.
        $user->set_role(self::ROLE_INSERENT);
    }

    /**
     * Sync user's role based on whether they have at least one listing in the system.
     * Rule:
     * - >= 1 listing (any status except trash/auto-draft) => Inserent
     * - 0 listings => subscriber
     * - administrators are never changed
     */
    public static function sync_role_by_listings(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }
        $user = get_user_by('id', $user_id);
        if (!$user instanceof \WP_User) {
            return;
        }

        // Never change administrators.
        if (in_array('administrator', (array) $user->roles, true)) {
            return;
        }

        self::ensure_roles();

        global $wpdb;
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(ID)
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_author = %d
               AND post_status NOT IN ('trash', 'auto-draft')",
            'mc_listing',
            $user_id
        ));

        if ($count > 0) {
            $user->set_role(self::ROLE_INSERENT);
        } else {
            $user->set_role('subscriber');
        }
    }

    public static function on_transition_post_status(string $new_status, string $old_status, \WP_Post $post): void {
        if (empty($post->ID) || $post->post_type !== 'mc_listing') {
            return;
        }
        // Any status change can affect whether a user has listings.
        self::sync_role_by_listings((int) $post->post_author);
    }

    public static function on_deleted_post(int $post_id): void {
        $post = get_post($post_id);
        if (!$post instanceof \WP_Post || $post->post_type !== 'mc_listing') {
            return;
        }
        self::sync_role_by_listings((int) $post->post_author);
    }

    public static function on_trashed_post(int $post_id): void {
        $post = get_post($post_id);
        if (!$post instanceof \WP_Post || $post->post_type !== 'mc_listing') {
            return;
        }
        self::sync_role_by_listings((int) $post->post_author);
    }

    public static function on_untrashed_post(int $post_id): void {
        $post = get_post($post_id);
        if (!$post instanceof \WP_Post || $post->post_type !== 'mc_listing') {
            return;
        }
        self::sync_role_by_listings((int) $post->post_author);
    }

    /**
     * One-time sync for existing installations (e.g., imported listings).
     */
    public static function maybe_run_one_time_sync(): void {
        $flag_key = 'mc_roles_one_time_sync_0_6971';
        if (get_option($flag_key)) {
            return;
        }

        self::ensure_roles();

        global $wpdb;

        // Promote users that have at least one listing.
        $author_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_author
             FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status NOT IN ('trash', 'auto-draft')
               AND post_author > 0",
            'mc_listing'
        ));

        if (is_array($author_ids)) {
            foreach ($author_ids as $uid) {
                self::sync_role_by_listings((int) $uid);
            }
        }

        // Also demote Inserent users that now have zero listings.
        $inserents = get_users(['role' => self::ROLE_INSERENT, 'fields' => 'ID']);
        if (is_array($inserents)) {
            foreach ($inserents as $uid) {
                self::sync_role_by_listings((int) $uid);
            }
        }

        update_option($flag_key, 1, false);
    }

}

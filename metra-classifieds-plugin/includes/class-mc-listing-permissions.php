<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central permission/context helper for single listing actions.
 *
 * Goal:
 * - keep owner/admin/login checks in one place
 * - make single listing templates consume one stable context
 * - avoid re-implementing the same UI decision logic inline
 */
final class MC_Listing_Permissions {

    /**
     * Build a normalized permission context for one listing.
     *
     * @return array{
     *   listing_id:int,
     *   current_user_id:int,
     *   is_logged_in:bool,
     *   is_admin:bool,
     *   is_owner:bool,
     *   can_manage:bool,
     *   can_view_paused:bool,
     *   show_favorite_button:bool,
     *   use_guest_favorite_gate:bool,
     *   show_contact_button:bool,
     *   can_open_contact:bool,
     *   use_guest_contact_gate:bool,
     *   show_report_button:bool,
     *   can_open_report:bool,
     *   use_guest_report_gate:bool,
     *   show_edit_button:bool,
     *   show_delete_button:bool,
     *   show_pause_button:bool,
     *   show_contact_modal:bool,
     *   show_report_modal:bool,
     *   show_delete_modal:bool,
     *   show_paused_notice:bool
     * }
     */
    public static function get_context(int $listing_id): array {
        $listing = get_post($listing_id);
        $current_user_id = (int) get_current_user_id();
        $is_logged_in = is_user_logged_in();
        $is_admin = current_user_can('manage_options');
        $is_owner = $listing instanceof \WP_Post
            ? ((int) $listing->post_author === $current_user_id)
            : false;

        $can_manage = $is_owner || $is_admin;
        $can_contact_owner = !$can_manage;

        return [
            'listing_id' => $listing_id,
            'current_user_id' => $current_user_id,
            'is_logged_in' => $is_logged_in,
            'is_admin' => $is_admin,
            'is_owner' => $is_owner,
            'can_manage' => $can_manage,
            'can_view_paused' => $can_manage,
            'show_favorite_button' => true,
            'use_guest_favorite_gate' => !$is_logged_in,
            'show_contact_button' => $can_contact_owner,
            'can_open_contact' => $can_contact_owner && $is_logged_in,
            'use_guest_contact_gate' => $can_contact_owner && !$is_logged_in,
            'show_report_button' => true,
            'can_open_report' => $is_logged_in,
            'use_guest_report_gate' => !$is_logged_in,
            'show_edit_button' => $can_manage,
            'show_delete_button' => $can_manage,
            'show_pause_button' => $can_manage,
            'show_contact_modal' => $can_contact_owner && $is_logged_in,
            'show_report_modal' => $is_logged_in,
            'show_delete_modal' => $can_manage,
            'show_paused_notice' => $can_manage,
        ];
    }
}

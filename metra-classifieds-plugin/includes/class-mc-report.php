<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Report {
    public static function init(): void {
        add_action('template_redirect', [self::class, 'handle_frontend_submit']);
    }

    public static function get_reasons(): array {
        $raw = get_option('mc_report_reasons', '');
        if (!is_string($raw) || trim($raw) === '') {
            return self::get_default_reasons();
        }

        $lines = preg_split('/
|
|
/', $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim(wp_strip_all_tags((string) $line));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out !== [] ? array_values(array_unique($out)) : self::get_default_reasons();
    }

    public static function get_default_reasons(): array {
        return [
            __('Spam or scam', 'metra-classifieds-plugin'),
            __('Duplicate listing', 'metra-classifieds-plugin'),
            __('Wrong category', 'metra-classifieds-plugin'),
            __('Prohibited item', 'metra-classifieds-plugin'),
            __('Misleading information', 'metra-classifieds-plugin'),
            __('Offensive content', 'metra-classifieds-plugin'),
            __('Other', 'metra-classifieds-plugin'),
        ];
    }

    public static function sanitize_reasons_text($value): string {
        if (!is_string($value)) {
            return '';
        }

        $lines = preg_split('/
|
|
/', $value) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim(wp_strip_all_tags((string) $line));
            if ($line !== '') {
                $out[] = $line;
            }
        }

        $out = array_values(array_unique($out));
        return implode("
", $out);
    }

    public static function handle_frontend_submit(): void {
        if (!isset($_POST['mc_report_submit'])) {
            return;
        }

        $listing_id = isset($_POST['mc_report_listing_id']) ? absint($_POST['mc_report_listing_id']) : 0;
        $redirect = $listing_id > 0 ? get_permalink($listing_id) : home_url('/');
        if (!is_string($redirect) || $redirect === '') {
            $redirect = home_url('/');
        }

        if (!is_user_logged_in()) {
            wp_safe_redirect(add_query_arg('mc_report', 'login_required', $redirect));
            exit;
        }

        if ($listing_id <= 0 || get_post_type($listing_id) !== 'mc_listing') {
            wp_safe_redirect(add_query_arg('mc_report', 'invalid', $redirect));
            exit;
        }

        if (!isset($_POST['_mc_report_nonce']) || !wp_verify_nonce((string) $_POST['_mc_report_nonce'], 'mc_report_listing_' . $listing_id)) {
            wp_safe_redirect(add_query_arg('mc_report', 'invalid_nonce', $redirect));
            exit;
        }

        $reason = isset($_POST['mc_report_reason']) ? sanitize_text_field((string) $_POST['mc_report_reason']) : '';
        $note = isset($_POST['mc_report_note']) ? sanitize_textarea_field((string) $_POST['mc_report_note']) : '';
        $allowed = self::get_reasons();
        if (!in_array($reason, $allowed, true)) {
            $reason = '';
        }

        if ($reason === '') {
            wp_safe_redirect(add_query_arg('mc_report', 'missing_reason', $redirect));
            exit;
        }

        $user = wp_get_current_user();
        $title = get_the_title($listing_id);
        $title_for_mail = is_string($title) && $title !== '' ? $title : __('(no title)', 'metra-classifieds-plugin');
        $listing_url = (string) get_permalink($listing_id);

        /* translators: 1: listing ID, 2: listing title */
        $subject = sprintf(
            __('Listing Report: #%1$d - %2$s', 'metra-classifieds-plugin'),
            $listing_id,
            $title_for_mail
        );

        
        // Email logo
        $logo_url = get_site_icon_url();
        if (!$logo_url) {
            $logo_url = home_url('/wp-content/uploads/metra-logo.png');
        }


        $message = '';
        $message .= '<h2 style="margin:0 0 18px 0;font-size:18px;line-height:1.3;">' . esc_html__('A listing was reported', 'metra-classifieds-plugin') . '</h2>';
        $message .= '<p style="margin:0 0 10px 0;">' . sprintf(esc_html__('Listing ID: %d', 'metra-classifieds-plugin'), $listing_id) . '</p>';
        $message .= '<p style="margin:0 0 10px 0;">' . sprintf(esc_html__('Listing title: %s', 'metra-classifieds-plugin'), $title_for_mail) . '</p>';
        $message .= '<p style="margin:0 0 20px 0;">' . esc_html__('Listing URL:', 'metra-classifieds-plugin') . ' <a href="' . esc_url($listing_url) . '">' . esc_html($listing_url) . '</a></p>';
        $message .= '<div style="margin:18px 0 8px 0;font-size:16px;font-weight:700;">' . esc_html__('Reason:', 'metra-classifieds-plugin') . '</div>';
        $message .= '<p style="margin:0 0 10px 0;">' . esc_html($reason) . '</p>';
        if ($note !== '') {
            $message .= '<p style="margin:0 0 20px 0;">' . sprintf(esc_html__('Additional note: %s', 'metra-classifieds-plugin'), $note) . '</p>';
        }
        $message .= '<p style="margin:18px 0 10px 0;">' . sprintf(esc_html__('Reported by user ID: %d', 'metra-classifieds-plugin'), (int) $user->ID) . '</p>';
        $message .= '<p style="margin:0 0 10px 0;">' . sprintf(esc_html__('Reported by user name: %s', 'metra-classifieds-plugin'), (string) $user->display_name) . '</p>';
        $message .= '<p style="margin:0;">' . esc_html__('Reported by email:', 'metra-classifieds-plugin') . ' <a href="mailto:' . antispambot((string) $user->user_email) . '">' . esc_html((string) $user->user_email) . '</a></p>';

        $headers = [];
        if (is_email((string) $user->user_email)) {
            $headers[] = 'Reply-To: ' . $user->display_name . ' <' . $user->user_email . '>';
        }

                $email_opt = get_option('mc_email', []);
        $report_to = '';
        if (is_array($email_opt) && isset($email_opt['report_to']) && is_string($email_opt['report_to'])) {
            $report_to = sanitize_email($email_opt['report_to']);
        }
        if ($report_to === '') {
            $report_to = sanitize_email((string) get_option('admin_email'));
        }

        $sent = MC_Emails::send_templated_html_mail($report_to, $subject, $message, $headers);
        wp_safe_redirect(add_query_arg('mc_report', $sent ? 'sent' : 'mail_failed', $redirect));
        exit;
    }
}

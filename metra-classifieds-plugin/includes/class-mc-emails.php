<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Central email templates for METRA Classifieds.
 *
 * Goal: Keep all notification templates in one place so future changes are easy.
 *
 * Currently used for: "new listing created" notifications
 * - to the listing author (confirmation)
 * - to the site admin (notification)
 */
final class MC_Emails {

    /**
     * Meta key used to prevent duplicate notifications on refresh.
     */
    private const META_NOTIFIED = '_mc_new_listing_notified';
    private const META_AUTHOR_NOTIFIED = '_mc_new_listing_author_notified';
    private const META_ADMIN_NOTIFIED = '_mc_new_listing_admin_notified';

    /**
     * Option name for email settings.
     */
    private const OPT_EMAIL = 'mc_email';

    /**
     * Send notifications for a newly created listing.
     *
     * IMPORTANT: This function is idempotent. If it has already sent
     * notifications for a given listing, it will not send them again.
     */
    public static function notify_new_listing(int $post_id): void {
        if ($post_id <= 0) {
            return;
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== MC_CPT::CPT) {
            return;
        }

        // Build common listing data once.
        $data = self::build_listing_data($post_id);
        $status = (string) get_post_status($post);
        $author = get_user_by('id', (int) $post->post_author);

        // 1) Author confirmation email
        // Send immediately only when the listing is already public.
        if ($status === 'publish') {
            self::maybe_send_new_listing_author_mail($post_id, $data, $author);
        }

        // 2) Admin notification email
        self::maybe_send_new_listing_admin_mail($post_id, $data, $author);
    }

    public static function maybe_send_new_listing_author_mail_on_transition(string $new_status, string $old_status, \WP_Post $post): void {
        if ($post->post_type !== MC_CPT::CPT) {
            return;
        }

        if ($old_status !== 'pending' || $new_status !== 'publish') {
            return;
        }

        $post_id = (int) $post->ID;
        if ($post_id <= 0) {
            return;
        }

        $data = self::build_listing_data($post_id);
        $author = get_user_by('id', (int) $post->post_author);
        self::maybe_send_new_listing_author_mail($post_id, $data, $author);
    }

    private static function maybe_send_new_listing_author_mail(int $post_id, array $data, $author): void {
        $author_sent = (string) get_post_meta($post_id, self::META_AUTHOR_NOTIFIED, true);
        $legacy_sent = (string) get_post_meta($post_id, self::META_NOTIFIED, true);
        if ($author_sent !== '' || $legacy_sent !== '') {
            return;
        }

        if (!$author || !is_email((string) $author->user_email)) {
            return;
        }

        $tpl = self::template_new_listing_author($data, $author);
        $intro = MC_Messages::get_new_listing_author_email_intro();
        if (!empty($intro['enabled']) && !empty($intro['html'])) {
            $html_body = '<div style="margin:12px 0 18px 0;">' . (string) $intro['html'] . '</div>';
            $html_body .= nl2br(esc_html((string) $tpl['body']), false);
            self::send_templated_html_mail((string) $author->user_email, $tpl['subject'], $html_body);
        } else {
            self::send_mail((string) $author->user_email, $tpl['subject'], $tpl['body']);
        }

        update_post_meta($post_id, self::META_AUTHOR_NOTIFIED, (string) time());
    }

    private static function maybe_send_new_listing_admin_mail(int $post_id, array $data, $author): void {
        $admin_sent = (string) get_post_meta($post_id, self::META_ADMIN_NOTIFIED, true);
        if ($admin_sent !== '') {
            return;
        }

        $admin_email = (string) get_option('admin_email');
        if (!is_email($admin_email)) {
            return;
        }

        $tpl = self::template_new_listing_admin($data, $author);
        self::send_mail($admin_email, $tpl['subject'], $tpl['body']);
        update_post_meta($post_id, self::META_ADMIN_NOTIFIED, (string) time());
    }

    /**
     * Send an activation email to a newly registered user.
     *
     * The user is considered "pending activation" until the activation link is clicked.
     * The link is processed by MC_User_Activation::maybe_handle_activation_request().
     */
    public static function send_user_activation_email(int $user_id, string $activation_key): bool {
        if ($user_id <= 0 || $activation_key === '') {
            return false;
        }

        $user = get_user_by('id', $user_id);
        if (!$user || !is_email((string) $user->user_email)) {
            return false;
        }

        $tpl = self::template_user_activation($user, $activation_key);
        $intro = MC_Messages::get_user_activation_email_intro();

        $html_body = '';
        if (!empty($intro['html'])) {
            $html_body .= '<div style="margin:20px 0 18px 0;">' . (string) $intro['html'] . '</div>';
        }
        $html_body .= nl2br(esc_html((string) $tpl['body']), false);

        // IMPORTANT:
        // Activation e-mails must always use the branded HTML wrapper (incl. logo),
        // exactly like all other METRA notifications.
        return self::send_mail_with_headers((string) $user->user_email, $tpl['subject'], $html_body, [
            'Content-Type: text/html; charset=UTF-8',
        ], true);
    }

    /**
     * After a user has activated their account, send a branded "set password" email.
     *
     * We intentionally do not use WordPress' core new-user notification, because we want
     * a clear flow:
     *   1) Activation email
     *   2) After activation: user sets password via secure one-time link
     */
    public static function send_user_set_password_email(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user || !is_email((string) $user->user_email)) {
            return;
        }

        // METRA-native password setup link (not wp-login.php?action=rp...).
        // This keeps the entire onboarding flow inside METRA.
        $key = MC_User_Activation::issue_set_password_key($user_id);
        if ($key === '') {
            return;
        }

        $setpw_url = MC_User_Activation::generate_set_password_url($user_id, $key);
        $tpl = self::template_user_set_password($user, $setpw_url);
        $intro = MC_Messages::get_user_set_password_email_intro();

        $html_body = '';
        if (!empty($intro['html'])) {
            $html_body .= '<div style="margin:12px 0 18px 0;">' . (string) $intro['html'] . '</div>';
        }
        $html_body .= nl2br(esc_html((string) $tpl['body']), false);

        self::send_mail_with_headers((string) $user->user_email, $tpl['subject'], $html_body, [
            'Content-Type: text/html; charset=UTF-8',
        ], true);
    }

    /**
     * Notify the site admin that a user has successfully activated their account.
     */
    public static function send_admin_user_activated_email(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $admin_email = (string) get_option('admin_email');
        if (!is_email($admin_email)) {
            return;
        }

        $tpl = self::template_admin_user_activated($user);
        self::send_mail($admin_email, $tpl['subject'], $tpl['body']);
    }

    /**
     * Notify the site admin that a user registered and is currently pending activation.
     */
    public static function send_admin_user_pending_activation_email(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        $admin_email = (string) get_option('admin_email');
        if (!is_email($admin_email)) {
            return;
        }

        $tpl = self::template_admin_user_pending_activation($user);
        self::send_mail($admin_email, $tpl['subject'], $tpl['body']);
    }

    /**
     * Send a contact message ("Message seller") using the same HTML wrapper
     * (incl. optional email-logo header) as the other METRA mails.
     *
     * Keeping this here ensures future email layout changes are done in one place.
     *
     * @param string $to
     * @param string $subject
     * @param string $plain_body
     * @param string $reply_to_name
     * @param string $reply_to_email
     */
    public static function send_contact_message(
        string $to,
        string $subject,
        string $plain_body,
        string $reply_to_name = '',
        string $reply_to_email = ''
    ): bool {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
        ];

        // Optional Reply-To (so the recipient can reply directly to the sender).
        if ($reply_to_email !== '' && is_email($reply_to_email)) {
            $rn = $reply_to_name !== '' ? $reply_to_name : $reply_to_email;
            $headers[] = 'Reply-To: ' . $rn . ' <' . $reply_to_email . '>';
        }

        $intro = MC_Messages::get_contact_mail_to_seller_intro();
        $html_body = '';

        if (!empty($intro['enabled']) && !empty($intro['html'])) {
            $html_body .= '<div style="margin:20px 0 18px 0;">' . (string) $intro['html'] . '</div>';
        }

        $html_body .= nl2br(esc_html($plain_body), false);

        return self::send_templated_html_mail($to, $subject, $html_body, $headers);
    }


    /**
     * Send a real METRA test mail through WordPress/SMTP using the central mail template.
     */
    public static function send_test_mail(string $to, string $subject_override = '', string $body_override = ''): bool {
        if (!is_email($to)) {
            return false;
        }

        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $subject = $subject_override !== ''
            ? $subject_override
            : sprintf(
                /* translators: %s: site name */
                __('METRA test e-mail from %s', 'metra-classifieds-plugin'),
                $site
            );

        if ($body_override !== '') {
            $body = $body_override;
        } else {
            $body  = '<h2 style="margin:0 0 18px 0;font-size:18px;line-height:1.3;">' . esc_html__('METRA mail test', 'metra-classifieds-plugin') . '</h2>';
            $body .= '<p style="margin:0 0 10px 0;">' . esc_html__('This is a real test e-mail sent by the METRA Classifieds plugin through WordPress.', 'metra-classifieds-plugin') . '</p>';
            $body .= '<p style="margin:0 0 10px 0;">' . esc_html__('If you receive this e-mail, the METRA mail path is working.', 'metra-classifieds-plugin') . '</p>';
            $body .= '<p style="margin:0 0 10px 0;"><strong>' . esc_html__('Website:', 'metra-classifieds-plugin') . '</strong> ' . esc_html($site) . '</p>';
            $body .= '<p style="margin:0 0 10px 0;"><strong>' . esc_html__('Recipient:', 'metra-classifieds-plugin') . '</strong> ' . esc_html($to) . '</p>';
            $body .= '<p style="margin:0;"><strong>' . esc_html__('Timestamp:', 'metra-classifieds-plugin') . '</strong> ' . esc_html(current_time('mysql')) . '</p>';
        }

        return self::send_templated_html_mail($to, $subject, $body);
    }

    /**
     * Build normalized listing data for email templates.
     */
    private static function build_listing_data(int $post_id): array {
        $post = get_post($post_id);
        $permalink = get_permalink($post_id);

        // Listing edit URL (front-end edit page).
        $edit_base = MC_Pages::get_page_url('edit');
        $edit_url  = $edit_base ? add_query_arg(['listing_id' => (string) $post_id], $edit_base) : '';

        // Price + condition (optional).
        $price = get_post_meta($post_id, 'mc_price', true);
        $price = ($price !== '' && $price !== null) ? (string) $price : '';
        $condition = (string) self::get_condition_label_from_taxonomy($post_id);

        // Category path (first term).
        $cat_path = '';
        $terms = wp_get_object_terms($post_id, MC_CPT::TAX, ['hide_empty' => false]);
        if (!is_wp_error($terms) && !empty($terms)) {
            $term = $terms[0];
            $cat_path = self::build_term_path($term);
        }

        return [
            'id'         => $post_id,
            'title'      => $post ? (string) $post->post_title : '',
            'status'     => $post ? (string) $post->post_status : '',
            'permalink'  => $permalink ? (string) $permalink : '',
            'edit_url'   => $edit_url ? (string) $edit_url : '',
            'cat_path'   => $cat_path,
            'price'      => $price,
            'condition'  => $condition,
            'created_at' => $post ? (string) mysql2date('Y-m-d H:i', (string) $post->post_date) : '',
        ];
    }


    private static function get_condition_label_from_taxonomy(int $post_id): string {
        if ($post_id <= 0) {
            return '';
        }

        $terms = wp_get_object_terms($post_id, 'mc_condition', ['hide_empty' => false]);
        if (is_wp_error($terms) || empty($terms) || !isset($terms[0]) || !is_object($terms[0])) {
            return '';
        }

        return isset($terms[0]->name) ? (string) $terms[0]->name : '';
    }

    /**
     * Build a readable category path like: Elektronik > Foto & Film > Kameras > Sony
     */
    private static function build_term_path($term): string {
        if (!is_object($term) || !isset($term->term_id)) {
            return '';
        }
        $parts = [(string) $term->name];
        $ancestors = get_ancestors((int) $term->term_id, (string) $term->taxonomy);
        if (!empty($ancestors)) {
            foreach ($ancestors as $ancestor_id) {
                $t = get_term((int) $ancestor_id, (string) $term->taxonomy);
                if ($t && !is_wp_error($t)) {
                    array_unshift($parts, (string) $t->name);
                }
            }
        }
        return implode(' > ', $parts);
    }

    /**
     * Template: email to the listing author.
     * NOTE: Do NOT include the listing description text; only the title and metadata.
     */
    private static function template_new_listing_author(array $d, $author): array {
        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $display_name = $author && isset($author->display_name) ? (string) $author->display_name : '';

        $subject = sprintf(
            /* translators: %s: listing title */
            __('Your listing has been created: %s', 'metra-classifieds-plugin'),
            $d['title']
        );

        // Allow site owners to override subject/body via filters.
        $subject = (string) apply_filters('mc_email_new_listing_author_subject', $subject, $d);

        $lines = [];
        $lines[] = $display_name !== ''
            ? sprintf(__('Hello %s,', 'metra-classifieds-plugin'), $display_name)
            : __('Hello,', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = __('Your listing was created successfully.', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = __('Details Ihrer Listing:', 'metra-classifieds-plugin');
        $lines[] = '— ' . __('Titel', 'metra-classifieds-plugin') . ': ' . $d['title'];
        if ($d['cat_path'] !== '') {
            $lines[] = '— ' . __('Category', 'metra-classifieds-plugin') . ': ' . $d['cat_path'];
        }
        if ($d['price'] !== '') {
            $lines[] = '— ' . __('Price', 'metra-classifieds-plugin') . ': ' . $d['price'];
        }
        if ($d['condition'] !== '') {
            $lines[] = '— ' . __('Condition', 'metra-classifieds-plugin') . ': ' . $d['condition'];
        }
        $lines[] = '— ' . __('Listing ID', 'metra-classifieds-plugin') . ': ' . $d['id'];
        if ($d['created_at'] !== '') {
            $lines[] = '— ' . __('Created on', 'metra-classifieds-plugin') . ': ' . $d['created_at'];
        }
        $lines[] = '';
        if ($d['permalink'] !== '') {
            $lines[] = __('View listing:', 'metra-classifieds-plugin');
            $lines[] = $d['permalink'];
            $lines[] = '';
        }
        if ($d['edit_url'] !== '') {
            $lines[] = __('Edit listing:', 'metra-classifieds-plugin');
            $lines[] = $d['edit_url'];
            $lines[] = '';
        }
        $lines[] = $site;

        $body = (string) apply_filters('mc_email_new_listing_author_body', implode("\n", $lines), $d);

        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Template: activation email sent to a newly registered user.
     */
    private static function template_user_activation($user, string $activation_key): array {
        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);

        $subject = __('Please confirm your email address', 'metra-classifieds-plugin');
        $subject = (string) apply_filters('mc_email_user_activation_subject', $subject, $user);

        $activate_url = add_query_arg([
            'mc_activate' => '1',
            'uid'         => (string) $user->ID,
            'key'         => $activation_key,
        ], home_url('/'));

        $name = isset($user->display_name) ? (string) $user->display_name : '';
        $lines = [];
        $lines[] = $name !== '' ? sprintf(__('Hello %s,', 'metra-classifieds-plugin'), $name) : __('Hello,', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = __('Please confirm your email address by clicking the following link:', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = $activate_url;
        $lines[] = '';
        $lines[] = __('If you did not create an account, you can ignore this email.', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = $site;

        $body = (string) apply_filters('mc_email_user_activation_body', implode("\n", $lines), $user, $activate_url);
        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Template: "set password" email sent AFTER successful activation.
     */
    private static function template_user_set_password($user, string $rp_url): array {
        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);

        $subject = __('Set your password now', 'metra-classifieds-plugin');
        $subject = (string) apply_filters('mc_email_user_set_password_subject', $subject, $user);

        $name = isset($user->display_name) ? (string) $user->display_name : '';
        $lines = [];
        $lines[] = $name !== '' ? sprintf(__('Hello %s,', 'metra-classifieds-plugin'), $name) : __('Hello,', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = __('Your account has been activated successfully.', 'metra-classifieds-plugin');
        $lines[] = __('Please set your password now by clicking the following link:', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = $rp_url;
        $lines[] = '';
        $lines[] = __('If you do not want to set a password or you received this email unexpectedly, you can ignore it.', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = $site;

        $body = (string) apply_filters('mc_email_user_set_password_body', implode("\n", $lines), $user, $rp_url);
        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Template: admin notification when a user successfully activated their account.
     */
    private static function template_admin_user_activated($user): array {
        $subject = sprintf(
            /* translators: %s: user email */
            __('User activated: %s', 'metra-classifieds-plugin'),
            (string) $user->user_email
        );
        $subject = (string) apply_filters('mc_email_admin_user_activated_subject', $subject, $user);

        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $lines = [];
        $lines[] = __('A user activated their account successfully.', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = '— ' . __('Name', 'metra-classifieds-plugin') . ': ' . (string) ($user->display_name ?? '');
        $lines[] = '— ' . __('Email', 'metra-classifieds-plugin') . ': ' . (string) $user->user_email;
        $lines[] = '— ' . __('User ID', 'metra-classifieds-plugin') . ': ' . (string) $user->ID;
        $lines[] = '';
        $lines[] = $site;

        $body = (string) apply_filters('mc_email_admin_user_activated_body', implode("\n", $lines), $user);
        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Template: admin notification when a user registered and is pending activation.
     */
    private static function template_admin_user_pending_activation($user): array {
        $subject = sprintf(
            /* translators: %s: user email */
            __('New registration (pending): %s', 'metra-classifieds-plugin'),
            (string) $user->user_email
        );
        $subject = (string) apply_filters('mc_email_admin_user_pending_subject', $subject, $user);

        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $lines = [];
        $lines[] = __('A new user has registered and is awaiting email confirmation (pending).', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = '— ' . __('Name', 'metra-classifieds-plugin') . ': ' . (string) ($user->display_name ?? '');
        $lines[] = '— ' . __('Username', 'metra-classifieds-plugin') . ': ' . (string) ($user->user_login ?? '');
        $lines[] = '— ' . __('Email', 'metra-classifieds-plugin') . ': ' . (string) $user->user_email;
        $lines[] = '— ' . __('User ID', 'metra-classifieds-plugin') . ': ' . (string) $user->ID;

        $edit = function_exists('get_edit_user_link') ? (string) get_edit_user_link((int) $user->ID) : '';
        if ($edit !== '') {
            $lines[] = '';
            $lines[] = __('Edit user:', 'metra-classifieds-plugin');
            $lines[] = $edit;
        }

        $lines[] = '';
        $lines[] = $site;

        $body = (string) apply_filters('mc_email_admin_user_pending_body', implode("\n", $lines), $user);
        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Template: email to the site admin.
     * NOTE: Do NOT include the listing description text; only the title and metadata.
     */
    private static function template_new_listing_admin(array $d, $author): array {
        $subject = sprintf(
            /* translators: 1: listing title, 2: listing id */
            __('New listing received: %1$s (ID %2$d)', 'metra-classifieds-plugin'),
            $d['title'],
            (int) $d['id']
        );
        $subject = (string) apply_filters('mc_email_new_listing_admin_subject', $subject, $d);

        $author_line = '';
        if ($author && isset($author->display_name, $author->user_email)) {
            $author_line = (string) $author->display_name . ' <' . (string) $author->user_email . '>';
        }

        $lines = [];
        $lines[] = __('Es wurde eine neue Listing erstellt.', 'metra-classifieds-plugin');
        $lines[] = '';
        $lines[] = __('Details:', 'metra-classifieds-plugin');
        if ($author_line !== '') {
            $lines[] = '— ' . __('Nutzer', 'metra-classifieds-plugin') . ': ' . $author_line;
        }
        $lines[] = '— ' . __('Titel', 'metra-classifieds-plugin') . ': ' . $d['title'];
        if ($d['cat_path'] !== '') {
            $lines[] = '— ' . __('Category', 'metra-classifieds-plugin') . ': ' . $d['cat_path'];
        }
        if ($d['price'] !== '') {
            $lines[] = '— ' . __('Price', 'metra-classifieds-plugin') . ': ' . $d['price'];
        }
        if ($d['condition'] !== '') {
            $lines[] = '— ' . __('Condition', 'metra-classifieds-plugin') . ': ' . $d['condition'];
        }
        $lines[] = '— ' . __('Status', 'metra-classifieds-plugin') . ': ' . $d['status'];
        $lines[] = '— ' . __('ID', 'metra-classifieds-plugin') . ': ' . $d['id'];
        if ($d['created_at'] !== '') {
            $lines[] = '— ' . __('Zeit', 'metra-classifieds-plugin') . ': ' . $d['created_at'];
        }
        $lines[] = '';
        if ($d['permalink'] !== '') {
            $lines[] = __('Link:', 'metra-classifieds-plugin');
            $lines[] = $d['permalink'];
            $lines[] = '';
        }

        // Admin edit link (wp-admin) is useful for moderation/workflow.
        $admin_edit = get_edit_post_link($d['id']);
        if ($admin_edit) {
            $lines[] = __('Admin-Link:', 'metra-classifieds-plugin');
            $lines[] = (string) $admin_edit;
            $lines[] = '';
        }

        $body = (string) apply_filters('mc_email_new_listing_admin_body', implode("\n", $lines), $d);
        return ['subject' => $subject, 'body' => $body];
    }

    /**
     * Get email template settings.
     *
     * @return array{url:string,max_w:int,content_w:int,footer_text:string}
     */
    private static function get_email_settings(): array {
        $opt = get_option(self::OPT_EMAIL, []);
        $url = '';
        $max_w = 240;
        $content_w = 680;
        $footer_text = '';

        if (is_array($opt)) {
            $u = $opt['logo_url'] ?? '';
            if (is_string($u)) {
                $url = trim($u);
            }

            $mw = $opt['logo_max_w'] ?? null;
            if (is_string($mw) && is_numeric($mw)) {
                $max_w = (int) $mw;
            } elseif (is_numeric($mw)) {
                $max_w = (int) $mw;
            }

            $cw = $opt['content_w'] ?? null;
            if (is_string($cw) && is_numeric($cw)) {
                $content_w = (int) $cw;
            } elseif (is_numeric($cw)) {
                $content_w = (int) $cw;
            }

            $ft = $opt['footer_text'] ?? '';
            if (is_string($ft)) {
                $footer_text = $ft;
            }
        }

        $max_w = max(50, min(800, $max_w));
        $content_w = max(420, min(900, $content_w));
        $url = $url !== '' ? esc_url($url) : '';

        return [
            'url' => $url,
            'max_w' => $max_w,
            'content_w' => $content_w,
            'footer_text' => $footer_text,
        ];
    }

    /**
     * Build the final HTML email wrapper.
     *
     * If $body_is_html is false, the body is treated as plain text and escaped.
     * If true, the provided body is assumed to be trusted plugin-generated HTML.
     */
    private static function build_email_html(string $subject, string $body, bool $body_is_html = false): string {
        $site = wp_specialchars_decode((string) get_bloginfo('name'), ENT_QUOTES);
        $settings = self::get_email_settings();

        $body_html = $body_is_html ? $body : nl2br(esc_html($body));

        $logo_html = '';
        if ($settings['url'] !== '') {
            $logo_html = '<div style="text-align:left;margin:0 0 18px 0;">'
                . '<img src="' . esc_url($settings['url']) . '" alt="' . esc_attr($site) . '" '
                . 'style="max-width:' . (int) $settings['max_w'] . 'px;width:auto;height:auto;display:inline-block;" />'
                . '</div>';
        }

        $footer_html = '';
        if ($settings['footer_text'] !== '') {
            $footer_text_html = preg_replace("/\r\n|\r|\n/", "<br>", $settings['footer_text']);
            if (!is_string($footer_text_html)) {
                $footer_text_html = $settings['footer_text'];
            }
            $footer_html = '<div style="margin-top:22px;padding-top:14px;border-top:1px solid #e5e7eb;color:#6b7280;font-size:12px;line-height:1.5;">'
                . $footer_text_html
                . '</div>';
        }

        $html = '';
        $html .= '<!doctype html><html><head><meta charset="utf-8" />';
        $html .= '<meta name="viewport" content="width=device-width,initial-scale=1" />';
        $html .= '<title>' . esc_html($subject) . '</title>';
        $html .= '</head><body style="margin:0;padding:0;background:#f6f7f7;">';
        $html .= '<div style="width:100%;padding:24px 12px;">';
        $html .= '<div style="max-width:' . (int) $settings['content_w'] . 'px;margin:0 auto;background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">';
        $html .= $logo_html;
        $html .= '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;line-height:1.6;color:#111827;word-break:break-word;overflow-wrap:anywhere;">';
        $html .= $body_html;
        $html .= $footer_html;
        $html .= '</div>';
        $html .= '</div></div>';
        $html .= '</body></html>';

        return (string) apply_filters('mc_email_html_wrapper', $html, $subject, $body);
    }

    /**
     * Wrapper around wp_mail with predictable headers.
     */
    private static function send_mail(string $to, string $subject, string $body): bool {
        return self::send_mail_with_headers($to, $subject, $body, [
            'Content-Type: text/html; charset=UTF-8',
        ], false);
    }

    /**
     * Public sender for plugin-generated HTML body fragments using the central METRA email template.
     *
     * @param string[] $headers
     */
    public static function send_templated_html_mail(string $to, string $subject, string $html_body, array $headers = []): bool {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        return self::send_mail_with_headers($to, $subject, $html_body, $headers, true);
    }

    /**
     * Low-level mail sender used by all METRA emails.
     *
     * @param string[] $headers
     */
    private static function send_mail_with_headers(string $to, string $subject, string $body, array $headers, bool $body_is_html = false): bool {
        $html = self::build_email_html($subject, $body, $body_is_html);

        $alt_body = wp_strip_all_tags($body, true);

        $alt_hook = static function ($phpmailer) use ($alt_body): void {
            if (is_object($phpmailer) && property_exists($phpmailer, 'AltBody')) {
                $phpmailer->AltBody = (string) $alt_body;
            }
        };
        add_action('phpmailer_init', $alt_hook, 10, 1);

        $last_error = '';
        $fail_hook = static function ($wp_error) use (&$last_error): void {
            if ($wp_error instanceof \WP_Error) {
                $last_error = (string) $wp_error->get_error_message();
            }
        };
        add_action('wp_mail_failed', $fail_hook, 10, 1);

        try {
            $ok = (bool) wp_mail($to, $subject, $html, $headers);
            remove_action('phpmailer_init', $alt_hook, 10);
            remove_action('wp_mail_failed', $fail_hook, 10);

            if (!$ok && defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MC EMAIL FAILED: to=' . $to . ' subject=' . $subject . ' err=' . $last_error);
            }

            if ($last_error !== '') {
                set_transient('mc_last_mail_error', $last_error, 60);
            }

            return $ok;
        } catch (\Throwable $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MC EMAIL ERROR: ' . $e->getMessage());
            }
            remove_action('phpmailer_init', $alt_hook, 10);
            remove_action('wp_mail_failed', $fail_hook, 10);
            set_transient('mc_last_mail_error', $e->getMessage(), 60);
            return false;
        }
    }

}

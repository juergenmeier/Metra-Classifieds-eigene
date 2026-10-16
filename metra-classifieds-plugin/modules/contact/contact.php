<?php
declare(strict_types=1);

namespace MetraClassifieds\Modules\Contact;

if (!defined('ABSPATH')) { exit; }

final class Module {

    public static function init(): void {
        // AJAX handler (logged-in only)
        add_action('wp_ajax_mc_contact_send', [self::class, 'ajax_send']);

        // UI injection on single listing content
        add_filter('the_content', [self::class, 'inject_ui'], 25);

        // Assets
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function enqueue_assets(): void {
        if (!is_singular('mc_listing')) { return; }

        wp_enqueue_script(
            'mc-contact',
            plugins_url('modules/contact/contact.js', \MC_PLUGIN_FILE),
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('mc-contact', 'MCContact', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'i18n'    => [
                'send_failed'   => __('Send failed.', 'metra-classifieds-plugin'),
                'empty_message' => __('Please enter a message.', 'metra-classifieds-plugin'),
                'sending'       => __('Einen Moment bitte. Ihre Mail wird gerade verschickt.', 'metra-classifieds-plugin'),
            ],
        ]);
    }

    public static function inject_ui(string $content): string {
        if (!is_singular('mc_listing') || !in_the_loop() || !is_main_query()) { return $content; }

        $post_id = get_the_ID();
        if (!$post_id) { return $content; }

        // Prevent duplicates
        if (strpos($content, 'mc-contact-open') !== false) { return $content; }

        return $content . self::render_ui($post_id);
    }

    public static function render_ui(int $post_id, bool $button_only = false, bool $include_button = true): string {
        if ($post_id <= 0) {
            return '';
        }

        $title = get_the_title($post_id);

        $author_id = (int) get_post_field('post_author', $post_id);
        $author_user = $author_id ? get_user_by('id', $author_id) : false;
        $author_name = ($author_user && !empty($author_user->display_name)) ? $author_user->display_name : '';

        $nonce = wp_create_nonce('mc_contact_' . $post_id);

        $button_html = '';
        if (!is_user_logged_in()) {
            $button_html .= '<div class="mc-alert mc-alert-info" style="margin-top:14px;padding:10px 12px;border-radius:12px;border:1px solid rgba(52,152,219,.35);background:rgba(52,152,219,.10);font-weight:700;">'
                         . esc_html__('Please log in to send a message.', 'metra-classifieds-plugin')
                         . '</div>';
        } else {
            $button_html .= '<button type="button" class="button mc-contact-open" data-mc-post-id="' . esc_attr((string) $post_id) . '" data-mc-nonce="' . esc_attr($nonce) . '">' 
                         . esc_html__('Message seller', 'metra-classifieds-plugin')
                         . '</button>';
        }

        if ($button_only) {
            return $button_html;
        }

        $ui = $include_button ? $button_html : '';
        $ui .= '<div id="mc-contact-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100000;"></div>';
        $ui .= '<div id="mc-contact-modal" style="display:none;position:fixed;left:50%;top:14%;transform:translateX(-50%);width:min(760px,75vw);background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);z-index:100001;overflow:hidden;">'
             . '  <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;padding:18px 18px 10px;border-bottom:1px solid rgba(0,0,0,.08);">'
             . '    <div>'
             . '      <div style="font-size:20px;font-weight:800;line-height:1.2;">' . esc_html($title) . '</div>'
             . '      <div style="font-size:14px;color:rgba(0,0,0,.55);margin-top:4px;">' . esc_html__('Message to', 'metra-classifieds-plugin') . ' ' . esc_html($author_name) . '</div>'
             . '    </div>'
             . '    <button type="button" class="mc-contact-close" aria-label="' . esc_attr__('Close', 'metra-classifieds-plugin') . '" style="border:0;background:rgba(0,0,0,.05);width:36px;height:36px;border-radius:999px;font-size:20px;line-height:36px;cursor:pointer;">×</button>'
             . '  </div>'
             . '  <div style="padding:16px 18px 18px;">'
             . '    <label style="display:block;font-weight:700;margin-bottom:8px;">' . esc_html__('Message', 'metra-classifieds-plugin') . '</label>'
             . '    <textarea id="mc-contact-message" rows="6" style="width:100%;border:1px solid rgba(0,0,0,.15);border-radius:12px;padding:12px;resize:vertical;"></textarea>'
             . '    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:12px;">'
             . '      <button type="button" class="mc-btn mc-contact-cancel">' . esc_html__('Cancel', 'metra-classifieds-plugin') . '</button>'
             . '      <button type="button" class="mc-btn mc-btn-primary mc-contact-send">' . esc_html__('Send message', 'metra-classifieds-plugin') . '</button>'
             . '    </div>'
             . '    <div id="mc-contact-sending-note" style="display:none;margin-top:10px;padding:10px 12px;border-radius:12px;background:rgba(34,113,177,.10);border:1px solid rgba(34,113,177,.30);font-weight:700;color:#1d4f91;"></div>'
             . '    <div id="mc-contact-inline" style="display:none;margin-top:10px;padding:10px 12px;border-radius:12px;"></div>'
             . '  </div>'
             . '</div>';

        $ui .= '<div id="mc-contact-success-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100002;">'
             . '  <div style="width:min(520px,75vw);margin:20vh auto;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);padding:20px 20px 16px;text-align:center;">'
             . '    <div style="font-size:18px;font-weight:900;margin-bottom:8px;">' . esc_html__('Message sent', 'metra-classifieds-plugin') . '</div>'
             . '    <div id="mc-contact-success-text" style="color:rgba(0,0,0,.65);margin-bottom:14px;">' . esc_html__('Your message has been sent to the seller.', 'metra-classifieds-plugin') . '</div>'
             . '    <button type="button" class="mc-btn mc-contact-success-close">' . esc_html__('OK', 'metra-classifieds-plugin') . '</button>'
             . '  </div>'
             . '</div>';

        return $ui;
    }

    public static function ajax_send(): void {
        // Only logged-in users
        if (!is_user_logged_in()) {
            wp_send_json_error(['code' => 'not_logged_in'], 403);
        }

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $nonce   = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        $message = isset($_POST['message']) ? trim((string)wp_unslash($_POST['message'])) : '';

        if (!$post_id || !wp_verify_nonce($nonce, 'mc_contact_' . $post_id)) {
            self::log('bad_nonce', ['post_id' => $post_id]);
            wp_send_json_error(['code' => 'bad_nonce'], 400);
        }

        $message_plain = trim(wp_strip_all_tags($message));
        if ($message_plain === '') {
            wp_send_json_error(['code' => 'empty_message'], 400);
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'mc_listing') {
            wp_send_json_error(['code' => 'bad_post'], 404);
        }

        $author_id = (int)$post->post_author;
        $to = $author_id ? get_the_author_meta('user_email', $author_id) : '';
        if (!$to || !is_email($to)) {
            self::log('no_recipient', ['post_id' => $post_id, 'author_id' => $author_id]);
            wp_send_json_error(['code' => 'no_recipient'], 400);
        }

        $admin_email = get_option('admin_email');

        $u = wp_get_current_user();
        $sender_email = $u && $u->user_email ? $u->user_email : '';
        $sender_name  = $u && $u->display_name ? $u->display_name : 'User';

        $subject = sprintf('[%s] %s', __('METRA Classifieds', 'metra-classifieds-plugin'), $post->post_title);

        $permalink = get_permalink($post_id);

        $body  = "Listing: " . $post->post_title . "\n";
        if ($permalink) {
            $body .= "Link: " . $permalink . "\n";
        }
        $body .= sprintf("%s %s <%s>\n\n", __('From:', 'metra-classifieds-plugin'), $sender_name, $sender_email);
        $body .= __('Message:', 'metra-classifieds-plugin') . "\n" . $message_plain . "\n";

        // Send using the centralized METRA email wrapper (HTML + logo + AltBody),
        // so contact mails look consistent with other notifications.
        $ok1 = \MetraClassifieds\MC_Emails::send_contact_message($to, $subject, $body, $sender_name, $sender_email);

        $ok2 = true;
        if ($admin_email && is_email($admin_email)) {
            $ok2 = \MetraClassifieds\MC_Emails::send_contact_message(
                $admin_email,
                sprintf(__('Classifieds communication – %s', 'metra-classifieds-plugin'), $post->post_title),
                $body,
                $sender_name,
                $sender_email
            );
        }

        if ($ok1 && $ok2) {
            self::log('sent', ['post_id' => $post_id, 'to' => $to]);
            wp_send_json_success(['code' => 'sent']);
        }

        self::log('send_failed', ['post_id' => $post_id, 'ok1' => $ok1, 'ok2' => $ok2]);
        wp_send_json_error(['code' => 'send_failed'], 500);
    }

    private static function log(string $msg, array $ctx = []): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[MC Contact] ' . $msg . ' ' . wp_json_encode($ctx));
        }
    }
}

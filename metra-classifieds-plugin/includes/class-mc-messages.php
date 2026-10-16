<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Messages {

    private const OPT_MESSAGES = 'mc_messages';

    /**
     * @return array<int,array<string,string|bool>>
     */
    public static function get_email_message_registry(): array {
        return [
            [
                'key' => 'contact_mail_to_seller_intro',
                'title' => __('Contact mail to seller', 'metra-classifieds-plugin'),
                'recipient' => __('Seller / listing owner', 'metra-classifieds-plugin'),
                'type' => __('Contact e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::send_contact_message()',
                'status' => __('Connected', 'metra-classifieds-plugin'),
                'notes' => __('Intro/prefix text already available in the Message Texts tab.', 'metra-classifieds-plugin'),
                'editable_now' => true,
            ],
            [
                'key' => 'user_activation_email',
                'title' => __('User activation e-mail', 'metra-classifieds-plugin'),
                'recipient' => __('Registered user', 'metra-classifieds-plugin'),
                'type' => __('Account e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::send_user_activation_email()',
                'status' => __('Connected', 'metra-classifieds-plugin'),
                'notes' => __('Mandatory intro/prefix text available in the Message Texts tab.', 'metra-classifieds-plugin'),
                'editable_now' => true,
            ],
            [
                'key' => 'user_set_password_email',
                'title' => __('User set password e-mail', 'metra-classifieds-plugin'),
                'recipient' => __('Activated user', 'metra-classifieds-plugin'),
                'type' => __('Account e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::send_user_set_password_email()',
                'status' => __('Connected', 'metra-classifieds-plugin'),
                'notes' => __('Mandatory intro/prefix text available in the Message Texts tab.', 'metra-classifieds-plugin'),
                'editable_now' => true,
            ],
            [
                'key' => 'new_listing_author_email',
                'title' => __('New listing e-mail to author', 'metra-classifieds-plugin'),
                'recipient' => __('Listing author', 'metra-classifieds-plugin'),
                'type' => __('Listing e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::notify_new_listing() / template_new_listing_author()',
                'status' => __('Connected', 'metra-classifieds-plugin'),
                'notes' => __('Optional intro/prefix text available in the Message Texts tab for the author copy.', 'metra-classifieds-plugin'),
                'editable_now' => true,
            ],
            [
                'key' => 'new_listing_admin_email',
                'title' => __('New listing e-mail to admin', 'metra-classifieds-plugin'),
                'recipient' => __('Admin / moderation', 'metra-classifieds-plugin'),
                'type' => __('Listing e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::notify_new_listing() / template_new_listing_admin()',
                'status' => __('Inventory only', 'metra-classifieds-plugin'),
                'notes' => __('Will later cover subject/body and optional extra message blocks for the admin copy.', 'metra-classifieds-plugin'),
                'editable_now' => false,
            ],
            [
                'key' => 'admin_user_activated_email',
                'title' => __('Admin notice: user activated', 'metra-classifieds-plugin'),
                'recipient' => __('Admin / moderation', 'metra-classifieds-plugin'),
                'type' => __('Admin notification', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::send_admin_user_activated_email()',
                'status' => __('Inventory only', 'metra-classifieds-plugin'),
                'notes' => __('Will later cover the admin message text for successful user activation.', 'metra-classifieds-plugin'),
                'editable_now' => false,
            ],
            [
                'key' => 'admin_user_pending_activation_email',
                'title' => __('Admin notice: registration pending', 'metra-classifieds-plugin'),
                'recipient' => __('Admin / moderation', 'metra-classifieds-plugin'),
                'type' => __('Admin notification', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::send_admin_user_pending_activation_email()',
                'status' => __('Inventory only', 'metra-classifieds-plugin'),
                'notes' => __('Will later cover the admin message text for pending registrations.', 'metra-classifieds-plugin'),
                'editable_now' => false,
            ],
            [
                'key' => 'report_listing_email',
                'title' => __('Report e-mail', 'metra-classifieds-plugin'),
                'recipient' => __('Admin / report recipient', 'metra-classifieds-plugin'),
                'type' => __('Moderation e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Report::handle_submit()',
                'status' => __('Inventory only', 'metra-classifieds-plugin'),
                'notes' => __('Will later cover report subject/body and optional moderation text blocks.', 'metra-classifieds-plugin'),
                'editable_now' => false,
            ],
            [
                'key' => 'metra_test_email',
                'title' => __('METRA test e-mail', 'metra-classifieds-plugin'),
                'recipient' => __('Configured test recipient', 'metra-classifieds-plugin'),
                'type' => __('System test e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Emails::send_test_mail()',
                'status' => __('Inventory only', 'metra-classifieds-plugin'),
                'notes' => __('Will later cover optional extra text for the regular METRA test e-mail.', 'metra-classifieds-plugin'),
                'editable_now' => false,
            ],
            [
                'key' => 'report_test_email',
                'title' => __('Report test e-mail', 'metra-classifieds-plugin'),
                'recipient' => __('Configured report recipient', 'metra-classifieds-plugin'),
                'type' => __('System test e-mail', 'metra-classifieds-plugin'),
                'source' => 'MC_Settings::handle_report_test_mail()',
                'status' => __('Inventory only', 'metra-classifieds-plugin'),
                'notes' => __('Will later cover optional extra text for the report test e-mail.', 'metra-classifieds-plugin'),
                'editable_now' => false,
            ],
        ];
    }

    public static function render_email_message_registry_admin(): string {
        $rows = self::get_email_message_registry();
        ob_start();
        echo '<div class="mc-message-registry" style="margin:18px 0 22px 0;">';
        echo '<h3 style="margin:0 0 10px 0;">' . esc_html__('E-mail message inventory', 'metra-classifieds-plugin') . '</h3>';
        echo '<p style="margin:0 0 14px 0;opacity:.9;">' . esc_html__('This block is the central administrative inventory for all current METRA e-mail message types. At this stage, only the contact intro text and the mandatory user activation intro text are already configurable. The remaining entries are documented here so future message fields can be added in one consistent place, without changing the existing sending logic yet.', 'metra-classifieds-plugin') . '</p>';
        echo '<table class="widefat striped" style="max-width:1100px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Message type', 'metra-classifieds-plugin') . '</th>';
        echo '<th>' . esc_html__('Recipient', 'metra-classifieds-plugin') . '</th>';
        echo '<th>' . esc_html__('Category', 'metra-classifieds-plugin') . '</th>';
        echo '<th>' . esc_html__('Current status', 'metra-classifieds-plugin') . '</th>';
        echo '<th>' . esc_html__('Source', 'metra-classifieds-plugin') . '</th>';
        echo '<th>' . esc_html__('Notes', 'metra-classifieds-plugin') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $status = (string) ($row['status'] ?? '');
            $editable = !empty($row['editable_now']);
            echo '<tr>';
            echo '<td><strong>' . esc_html((string) ($row['title'] ?? '')) . '</strong><br><code>' . esc_html((string) ($row['key'] ?? '')) . '</code>' . ($editable ? '<br><span style="color:#2271b1;">' . esc_html__('Editable now', 'metra-classifieds-plugin') . '</span>' : '') . '</td>';
            echo '<td>' . esc_html((string) ($row['recipient'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($row['type'] ?? '')) . '</td>';
            echo '<td>' . esc_html($status) . '</td>';
            echo '<td><code>' . esc_html((string) ($row['source'] ?? '')) . '</code></td>';
            echo '<td>' . esc_html((string) ($row['notes'] ?? '')) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
        return (string) ob_get_clean();
    }

    /**
     * @return array{enabled:bool,text:string,html:string}
     */
    public static function get_contact_mail_to_seller_intro(): array {
        $opt = get_option(self::OPT_MESSAGES, []);
        $enabled = true;
        $text = '';

        if (is_array($opt)) {
            $enabled = !isset($opt['contact_mail_to_seller_intro_enabled']) || in_array((string) $opt['contact_mail_to_seller_intro_enabled'], ['1', 'true', 'yes', 'on'], true);
            if (isset($opt['contact_mail_to_seller_intro_text']) && is_string($opt['contact_mail_to_seller_intro_text'])) {
                $text = $opt['contact_mail_to_seller_intro_text'];
            }
        }

        if ($text === '') {
            $text = self::default_contact_mail_to_seller_intro_text();
        }

        return [
            'enabled' => $enabled,
            'text' => $text,
            'html' => self::format_text_for_email($text),
        ];
    }

    /**
     * @return array{enabled:bool,text:string,html:string}
     */
    public static function get_user_activation_email_intro(): array {
        $opt = get_option(self::OPT_MESSAGES, []);
        $text = '';

        if (is_array($opt) && isset($opt['user_activation_email_intro_text']) && is_string($opt['user_activation_email_intro_text'])) {
            $text = $opt['user_activation_email_intro_text'];
        }

        if ($text === '') {
            $text = self::default_user_activation_email_intro_text();
        }

        return [
            'enabled' => true,
            'text' => $text,
            'html' => self::format_text_for_email($text),
        ];
    }

    /**
     * @return array{enabled:bool,text:string,html:string}
     */
    public static function get_user_set_password_email_intro(): array {
        $opt = get_option(self::OPT_MESSAGES, []);
        $text = '';

        if (is_array($opt) && isset($opt['user_set_password_email_intro_text']) && is_string($opt['user_set_password_email_intro_text'])) {
            $text = $opt['user_set_password_email_intro_text'];
        }

        if ($text === '') {
            $text = self::default_user_set_password_email_intro_text();
        }

        return [
            'enabled' => true,
            'text' => $text,
            'html' => self::format_text_for_email($text),
        ];
    }

    /**
     * @return array{enabled:bool,text:string,html:string}
     */
    public static function get_new_listing_author_email_intro(): array {
        $opt = get_option(self::OPT_MESSAGES, []);
        $enabled = true;
        $text = '';

        if (is_array($opt)) {
            $enabled = !isset($opt['new_listing_author_email_intro_enabled']) || in_array((string) $opt['new_listing_author_email_intro_enabled'], ['1', 'true', 'yes', 'on'], true);
            if (isset($opt['new_listing_author_email_intro_text']) && is_string($opt['new_listing_author_email_intro_text'])) {
                $text = $opt['new_listing_author_email_intro_text'];
            }
        }

        if ($text === '') {
            $text = self::default_new_listing_author_email_intro_text();
        }

        return [
            'enabled' => $enabled,
            'text' => $text,
            'html' => self::format_text_for_email($text),
        ];
    }

    public static function default_contact_mail_to_seller_intro_text(): string {
        return (string) __('[bold]Congratulations[/bold]

A reader of your listing is interested in your offer or request. Below you will find all the details needed to get in touch and reply.

Good luck!', 'metra-classifieds-plugin');
    }

    public static function default_user_activation_email_intro_text(): string {
        return (string) __('[big]Herzlich willkommen bei den Kleinanzeigen[/big]

Ihr neues Konto muss jetzt nur noch bestätigt werden. Anschließend können Sie alle Funktionen nutzen und mit Inserenten in Kontakt treten. Klicken Sie zur Aktivierung jetzt etwas weiter unten auf den Bestätigungslink. Danach melden Sie sich mit Ihren Angaben an... und los geht\'s.

Viel Erfolg mit den Kleinanzeigen.', 'metra-classifieds-plugin');
    }

    public static function default_user_set_password_email_intro_text(): string {
        return (string) __('[big]Set your password[/big]

Your account has been successfully activated. Please set your password to complete your registration.

Click the link below to create your password. After that, you can sign in and start using all features.

Good luck and enjoy using the classifieds!', 'metra-classifieds-plugin');
    }


    public static function default_new_listing_author_email_intro_text(): string {
        return (string) __('Your listing is now publicly visible in the classifieds. We are keeping our fingers crossed for a successful outcome.

Here are all the details of your listing:', 'metra-classifieds-plugin');
    }

    public static function sanitize_textarea_preserve_blank_lines($value): string {
        if (!is_string($value)) {
            return '';
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $value = str_replace(["
", "
"], "
", $value);
        return rtrim($value);
    }

    public static function format_text_for_email(string $text): string {
        $text = str_replace(["
", "
"], "
", $text);
        $text = rtrim($text);
        $safe = esc_html($text);
        $safe = preg_replace('/\[big\](.*?)\[\/big\]/is', '<div style="font-size:20px;font-weight:700;line-height:1.35;margin:0 0 10px 0;">$1</div>', $safe) ?? $safe;
        $safe = preg_replace('/\[bold\](.*?)\[\/bold\]/is', '<strong>$1</strong>', $safe) ?? $safe;
        return nl2br($safe, false);
    }


    public static function get_message_test_url(string $key): string {
        $title = self::get_registry_title($key);
        if ($key === '' || $title === '') {
            return '';
        }

        return wp_nonce_url(
            add_query_arg([
                'action' => 'mc_send_message_test_mail',
                'message_key' => $key,
            ], admin_url('admin-post.php')),
            'mc_send_message_test_mail_' . $key
        );
    }

    public static function send_test_mail_for_message_type(string $key): bool {
        $title = self::get_registry_title($key);
        if ($title === '') {
            return false;
        }

        $opt = get_option('mc_email', []);
        $to = '';
        if (is_array($opt) && isset($opt['test_to']) && is_string($opt['test_to'])) {
            $to = sanitize_email($opt['test_to']);
        }
        if ($to === '') {
            $to = sanitize_email((string) get_option('admin_email', ''));
        }
        if (!is_email($to)) {
            return false;
        }

        $subject = sprintf(__('Test-Mail "%s"', 'metra-classifieds-plugin'), $title);
        $body = self::build_test_mail_html($key, $title);

        return MC_Emails::send_test_mail($to, $subject, $body);
    }

    private static function get_registry_title(string $key): string {
        foreach (self::get_email_message_registry() as $row) {
            if ((string) ($row['key'] ?? '') === $key) {
                return (string) ($row['title'] ?? '');
            }
        }
        return '';
    }

    private static function build_test_mail_html(string $key, string $title): string {
        $intro_html = '';
        if ($key === 'contact_mail_to_seller_intro') {
            $intro = self::get_contact_mail_to_seller_intro();
            if (!empty($intro['enabled']) && !empty($intro['html'])) {
                $intro_html = '<div style="margin:12px 0 18px 0;">' . (string) $intro['html'] . '</div>';
            }
        } elseif ($key === 'user_activation_email') {
            $intro = self::get_user_activation_email_intro();
            if (!empty($intro['html'])) {
                $intro_html = '<div style="margin:12px 0 18px 0;">' . (string) $intro['html'] . '</div>';
            }
        } elseif ($key === 'user_set_password_email') {
            $intro = self::get_user_set_password_email_intro();
            if (!empty($intro['html'])) {
                $intro_html = '<div style="margin:12px 0 18px 0;">' . (string) $intro['html'] . '</div>';
            }
        } elseif ($key === 'new_listing_author_email') {
            $intro = self::get_new_listing_author_email_intro();
            if (!empty($intro['enabled']) && !empty($intro['html'])) {
                $intro_html = '<div style="margin:12px 0 18px 0;">' . (string) $intro['html'] . '</div>';
            }
        }

        $sample = self::format_text_for_email(self::get_test_dummy_text());
        $meta = '<div style="margin:18px 0 0 0;padding:12px 14px;border:1px solid #e5e5e5;border-radius:10px;background:#fafafa;">'
            . '<div style="font-weight:700;margin:0 0 6px 0;">' . esc_html__('This is a test mail with dummy data.', 'metra-classifieds-plugin') . '</div>'
            . '<div><strong>' . esc_html__('Message type:', 'metra-classifieds-plugin') . '</strong> ' . esc_html($title) . '</div>'
            . '</div>';

        return $intro_html . '<div style="margin:12px 0 0 0;">' . $sample . '</div>' . $meta;
    }

    private static function get_test_dummy_text(): string {
        return (string) __('[big]Sample heading[/big]

Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante venenatis dapibus posuere velit aliquet. Curabitur blandit tempus porttitor. Maecenas faucibus mollis interdum. Aenean eu leo quam. Pellentesque ornare sem lacinia quam venenatis vestibulum.', 'metra-classifieds-plugin');
    }

}

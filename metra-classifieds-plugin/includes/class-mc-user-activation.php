<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User activation flow (email confirmation).
 *
 * Implemented behaviour:
 * - New users receive an activation email with a unique activation link.
 * - Users cannot log in until they activate their account.
 * - Admin receives an email when a user successfully activates.
 * - No automatic deletion/cleanup is performed (users remain pending until activated or manually removed).
 *
 * Notes:
 * - We only affect users marked as "pending activation" via user meta.
 * - Existing users are not impacted.
 */
final class MC_User_Activation {

    /** User meta keys */
    private const META_PENDING = '_mc_activation_pending';
    private const META_KEY     = '_mc_activation_key';
    private const META_CREATED = '_mc_activation_created';
    /** Prevent duplicate password-set mails */
    private const META_PW_SENT = '_mc_activation_pw_sent';

    /**
     * Legacy: previously users had to set a password *after* activation.
     * Since we now collect a password during registration, this flag is no longer set.
     */
    /**
     * Legacy: previously users had to set a password *after* activation.
     * Since we now collect a password during registration, this flag is no longer set.
     */
    private const META_PW_REQUIRED = '_mc_pw_required';
    /** One-time key for METRA password setup */
    private const META_PW_KEY     = '_mc_pw_key';
    /** Timestamp when the password-setup key was issued */
    private const META_PW_CREATED = '_mc_pw_created';

    /**
     * Register hooks.
     */
    public static function init(): void {
        // Create activation key + send activation email.
        add_action('user_register', [self::class, 'on_user_register'], 10, 1);

        // Block login for users pending activation.
        add_filter('authenticate', [self::class, 'block_login_until_activated'], 30, 3);

        // Handle activation link requests.
        add_action('init', [self::class, 'maybe_handle_activation_request']);

        // Suppress WordPress core "new user" / password-setup emails for users pending activation.
        // We send our branded password-set mail AFTER activation.
        add_filter('pre_wp_mail', [self::class, 'maybe_suppress_core_password_set_emails'], 10, 2);

        // Note: we intentionally do NOT auto-delete unactivated users.

        // Admin: add a sortable "Pending" column to the Users list.
        if (is_admin()) {
            add_filter('manage_users_columns', [self::class, 'add_users_list_pending_column']);
            add_filter('manage_users_sortable_columns', [self::class, 'add_users_list_sortable_columns']);
            add_filter('manage_users_custom_column', [self::class, 'render_users_list_pending_column'], 10, 3);
            add_action('pre_get_users', [self::class, 'maybe_sort_users_list_by_pending']);
        }
    }

    /**
     * Add a "Pending" column to wp-admin Users list.
     *
     * @param array<string,string> $columns
     * @return array<string,string>
     */
    public static function add_users_list_pending_column(array $columns): array {
        // Place it near the email column if possible.
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'email') {
                $new['mc_pending'] = __('Pending', 'metra-classifieds-plugin');
            }
        }

        if (!isset($new['mc_pending'])) {
            $new['mc_pending'] = __('Pending', 'metra-classifieds-plugin');
        }

        return $new;
    }

    /**
     * Make the column sortable.
     *
     * @param array<string,string> $sortable
     * @return array<string,string>
     */
    public static function add_users_list_sortable_columns(array $sortable): array {
        $sortable['mc_pending'] = 'mc_pending';
        return $sortable;
    }

    /**
     * Render the column.
     *
     * @param string $output
     * @param string $column_name
     * @param int $user_id
     * @return string
     */
    public static function render_users_list_pending_column(string $output, string $column_name, int $user_id): string {
        if ($column_name !== 'mc_pending') {
            return $output;
        }

        $pending = (string) get_user_meta($user_id, self::META_PENDING, true);
        if ($pending === '1') {
            return '<span class="mc-pending" style="font-weight:600;">' . esc_html__('Yes', 'metra-classifieds-plugin') . '</span>';
        }
        return '<span class="mc-not-pending">' . esc_html__('No', 'metra-classifieds-plugin') . '</span>';
    }

    /**
     * Apply sorting by meta value when ordering by our column.
     */
    public static function maybe_sort_users_list_by_pending(
        \WP_User_Query $query
    ): void {
        if (!is_admin()) {
            return;
        }

        // Only affect the main Users list.
        global $pagenow;
        if ($pagenow !== 'users.php') {
            return;
        }

        $orderby = (string) $query->get('orderby');
        if ($orderby !== 'mc_pending') {
            return;
        }

        $query->set('meta_key', self::META_PENDING);
        $query->set('orderby', 'meta_value');

        // Default order: pending first.
        $order = strtoupper((string) $query->get('order'));
        if ($order === '') {
            $query->set('order', 'DESC');
        }
    }


    /**
     * Count users that are still pending activation.
     */
    public static function get_pending_user_count(): int {
        $query = new \WP_User_Query([
            'fields'     => 'ID',
            'number'     => 1,
            'count_total'=> true,
            'meta_key'   => self::META_PENDING,
            'meta_value' => '1',
        ]);

        return max(0, (int) $query->get_total());
    }

    /**
     * Delete all users that are still pending activation.
     *
     * @return int Number of deleted users.
     */
    public static function delete_all_pending_users(): int {
        $query = new \WP_User_Query([
            'fields'     => 'ID',
            'number'     => -1,
            'meta_key'   => self::META_PENDING,
            'meta_value' => '1',
        ]);

        $user_ids = $query->get_results();
        if (!is_array($user_ids) || empty($user_ids)) {
            return 0;
        }

        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $deleted = 0;
        foreach ($user_ids as $user_id) {
            $user_id = (int) $user_id;
            if ($user_id <= 0) {
                continue;
            }

            if (wp_delete_user($user_id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Prevent WP core from sending "set password" emails for users pending activation.
     *
     * Some environments/plugins trigger wp_new_user_notification(), which emails a reset-password
     * link (wp-login.php?action=rp...) immediately after registration. For METRA we want:
     *   1) activation mail first
     *   2) password-set mail AFTER activation
     *
     * Returning a non-null value short-circuits wp_mail().
     *
     * @param mixed $pre
     * @param array $atts
     * @return mixed
     */
    public static function maybe_suppress_core_password_set_emails($pre, array $atts) {
        $to = isset($atts['to']) ? (string) $atts['to'] : '';
        if ($to === '' || !is_email($to)) {
            return $pre;
        }

        // If our own mail explicitly allows the reset-password link, never suppress.
        $headers = $atts['headers'] ?? [];
        if (is_string($headers)) {
            $headers = [$headers];
        }
        $headers_list = is_array($headers) ? $headers : [];
        foreach ($headers_list as $h) {
            if (is_string($h) && stripos($h, 'X-MC-Allow-RP: 1') !== false) {
                return $pre;
            }
        }

        $msg = isset($atts['message']) ? (string) $atts['message'] : '';
        $looks_like_rp = (stripos($msg, 'wp-login.php') !== false && stripos($msg, 'action=rp') !== false && stripos($msg, 'key=') !== false && stripos($msg, 'login=') !== false);
        if (!$looks_like_rp) {
            return $pre;
        }

        $user = get_user_by('email', $to);
        if (!$user) {
            return $pre;
        }

        $pending = (string) get_user_meta((int) $user->ID, self::META_PENDING, true);
        if ($pending === '1') {
            return true; // pretend sent
        }

        return $pre;
    }

    /**
     * When a new user registers, mark them as pending activation and send email.
     */
    public static function on_user_register(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user || !is_email((string) $user->user_email)) {
            return;
        }

        // Do not re-run if already pending/activated.
        $pending = (string) get_user_meta($user_id, self::META_PENDING, true);
        if ($pending === '1') {
            return;
        }

        // Generate a unique activation key.
        $key = wp_generate_password(32, false, false);

        update_user_meta($user_id, self::META_PENDING, '1');
        update_user_meta($user_id, self::META_KEY, (string) $key);
        update_user_meta($user_id, self::META_CREATED, (string) time());

        // Password is collected during registration (no post-activation password step required).
        delete_user_meta($user_id, self::META_PW_REQUIRED);

        // Send activation email to the user.
        // Store the result so the registration screen can show a reliable status.
        $ok = MC_Emails::send_user_activation_email($user_id, (string) $key);

        // Also notify the admin immediately (pending activation).
        // This helps monitoring new signups and supports users if activation emails are delayed.
        MC_Emails::send_admin_user_pending_activation_email($user_id);

        if ($ok) {
            update_user_meta($user_id, '_mc_activation_mail_sent', (string) time());
            delete_transient('mc_last_mail_error');
        } else {
            $err = (string) get_transient('mc_last_mail_error');
            update_user_meta($user_id, '_mc_activation_mail_failed', (string) time());
            if ($err !== '') {
                update_user_meta($user_id, '_mc_activation_mail_error', $err);
            }
        }
    }

    /**
     * Prevent login until activation is completed.
     */
    public static function block_login_until_activated($user, string $username, string $password) {
        if ($user instanceof \WP_User) {
            $pending = (string) get_user_meta((int) $user->ID, self::META_PENDING, true);
            if ($pending === '1') {
                return new \WP_Error(
                    'mc_activation_required',
                    __('Please confirm your email address via the activation link before you can sign in.', 'metra-classifieds-plugin')
                );
            }

            // Password is collected during registration; no post-activation set-password gate.
        }
        return $user;
    }

    /**
     * Handle activation link requests.
     *
     * URL format:
     *   https://example.com/?mc_activate=1&uid=123&key=abcdef
     */
    public static function maybe_handle_activation_request(): void {
        if (!isset($_GET['mc_activate'])) {
            return;
        }

        $uid = isset($_GET['uid']) ? (int) $_GET['uid'] : 0;
        $key = isset($_GET['key']) ? (string) sanitize_text_field((string) $_GET['key']) : '';

        // Always redirect away from raw URL to a clean page afterwards.
        $redirect = home_url('/');

        if ($uid <= 0 || $key === '') {
            wp_safe_redirect(add_query_arg(['mc_activation' => 'invalid'], $redirect));
            exit;
        }

        $pending = (string) get_user_meta($uid, self::META_PENDING, true);
        $saved_key = (string) get_user_meta($uid, self::META_KEY, true);

        if ($pending !== '1' || $saved_key === '' || !hash_equals($saved_key, $key)) {
            wp_safe_redirect(add_query_arg(['mc_activation' => 'invalid'], $redirect));
            exit;
        }

        // Mark as activated.
        delete_user_meta($uid, self::META_PENDING);
        delete_user_meta($uid, self::META_KEY);
        delete_user_meta($uid, self::META_CREATED);

        // Password already exists (collected during registration). No set-password email.

        // Notify admin that the user activated successfully.
        MC_Emails::send_admin_user_activated_email($uid);

        // After successful activation, send the user to the METRA login page.
        $login_url = MC_Pages::get_page_url('login');
        if (!is_string($login_url) || $login_url === '') {
            $login_url = home_url('/mc-login/');
        }

        wp_safe_redirect(add_query_arg(['mc_login' => 'activated'], $login_url));
        exit;
    }

    /**
     * Build the METRA "set password" URL (page-based) for a user.
     */
    public static function build_set_password_url(int $user_id, string $key): string {
        $base = MC_Pages::get_page_url('set_password');
        if ($base === '') {
            $base = home_url('/');
        }
        return add_query_arg([
            'uid' => (string) $user_id,
            'key' => (string) $key,
        ], $base);
    }

    /**
     * Backward-compatible alias.
     *
     * Some earlier code paths (and third-party customizations) call
     * generate_set_password_url(). We keep it as a stable alias to the current
     * implementation.
     */
    public static function generate_set_password_url(int $user_id, string $key): string {
        return self::build_set_password_url($user_id, $key);
    }

    /**
     * Create and store a one-time password-setup key for the user.
     *
     * This is separate from WordPress' wp-login.php?action=rp mechanism.
     */
    public static function issue_set_password_key(int $user_id): string {
        if ($user_id <= 0) {
            return '';
        }
        $key = wp_generate_password(32, false, false);
        update_user_meta($user_id, self::META_PW_KEY, (string) $key);
        update_user_meta($user_id, self::META_PW_CREATED, (string) time());
        return (string) $key;
    }

    /**
     * Validate a set-password link (uid + key).
     */
    public static function is_set_password_link_valid(int $user_id, string $key): bool {
        if ($user_id <= 0 || $key === '') {
            return false;
        }

        $saved = (string) get_user_meta($user_id, self::META_PW_KEY, true);
        if ($saved === '' || !hash_equals($saved, $key)) {
            return false;
        }

        // Expire keys after 24 hours (filterable).
        $hours = (int) apply_filters('mc_pw_setup_expiry_hours', 24);
        if ($hours < 1) {
            $hours = 24;
        }
        $created = (int) get_user_meta($user_id, self::META_PW_CREATED, true);
        if ($created > 0 && (time() - $created) > ($hours * 3600)) {
            return false;
        }

        // Must still require password.
        $req = (string) get_user_meta($user_id, self::META_PW_REQUIRED, true);
        return ($req === '1');
    }

    /**
     * Set password via METRA flow.
     */
    public static function handle_set_password(int $user_id, string $key, string $new_password): bool {
        if (!self::is_set_password_link_valid($user_id, $key)) {
            return false;
        }

        if (strlen($new_password) < 8) {
            // Keep minimal policy: 8 chars.
            return false;
        }

        // Set the user's password.
        wp_set_password($new_password, $user_id);

        // Mark as ready to login.
        delete_user_meta($user_id, self::META_PW_REQUIRED);
        delete_user_meta($user_id, self::META_PW_KEY);
        delete_user_meta($user_id, self::META_PW_CREATED);

        return true;
    }

}

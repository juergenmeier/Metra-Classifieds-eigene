<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

require_once MC_PLUGIN_DIR . 'includes/class-mc-i18n.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-icons.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-icon-library.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-cpt.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-admin-columns.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-pages.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-shortcodes.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-settings.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-templates.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-design.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-emails.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-messages.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-roles.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-user-activation.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-login.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-menu.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-profile.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-favorites.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-report.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-listing-permissions.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-listing-view.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-ajax-cats.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-listing-id.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-views.php';
require_once MC_PLUGIN_DIR . 'includes/widgets/class-mc-categories-widget.php';
require_once MC_PLUGIN_DIR . 'includes/class-mc-widgets.php';

/**
 * Core plugin orchestrator.
 */
final class MC_Plugin {

    public function run(): void {

        // Contact module
        if (file_exists(MC_PLUGIN_DIR . 'modules/contact/contact.php')) {
            require_once MC_PLUGIN_DIR . 'modules/contact/contact.php';
            \MetraClassifieds\Modules\Contact\Module::init();
        }
$this->init_hooks();
    }

    private function init_hooks(): void {
        // i18n
        add_action('plugins_loaded', [MC_I18n::class, 'load_textdomain']);

        // Admin list table columns
        add_action('admin_init', [MC_Admin_Columns::class, 'init']);

        // Self-heal required pages (e.g., /mc-login/) if the plugin was updated
        // without re-running the activation hook.
        add_action('init', [MC_Pages::class, 'ensure_required_pages_if_missing'], 20);

        // CPT + taxonomy
        add_action('init', [MC_CPT::class, 'register']);
        add_filter('post_type_link', [MC_CPT::class, 'filter_listing_permalink'], 10, 2);
        add_action('init', [MC_CPT::class, 'maybe_flush_rewrite_rules'], 25);

        // shortcodes
        add_action('init', [MC_Shortcodes::class, 'register']);
        add_action('admin_notices', [self::class, 'mc_self_check_registrations']);

        // When a listing is deleted (frontend delete action or WP admin), also delete attached images.
        add_action('before_delete_post', [MC_Shortcodes::class, 'maybe_delete_listing_attachments']);

        // templates
        add_filter('template_include', [MC_Templates::class, 'template_include']);
        add_filter('the_title', [MC_Shortcodes::class, 'maybe_filter_dynamic_page_title'], 10, 2);

        // Design tokens + base stylesheet.
        // Enqueue late to override theme styles more reliably.
        add_action('wp_enqueue_scripts', [MC_Design::class, 'enqueue'], 999);

        // redirect native category archives to the unified METRA category listings page
        add_action('template_redirect', [MC_Pages::class, 'maybe_redirect_category_archive']);

        // redirect legacy view endpoint to canonical single permalink
        add_action('template_redirect', [MC_Pages::class, 'maybe_redirect_legacy_view']);

        // submit flow: enforce category drilldown wizard before allowing /mc-submit/
        // Must run before any output (template_redirect) otherwise redirects may fail.
        add_action('template_redirect', [MC_Pages::class, 'maybe_redirect_submit_to_category_wizard']);

        // settings/admin
        if (is_admin()) {
            add_action('admin_menu', [MC_Settings::class, 'register_menu']);
            add_action('admin_init', [MC_Settings::class, 'register_settings']);
            add_action('admin_post_mc_send_test_mail', [MC_Settings::class, 'handle_send_test_mail']);
            add_action('admin_post_mc_send_report_test_mail', [MC_Settings::class, 'handle_send_report_test_mail']);
            add_action('admin_post_mc_send_message_test_mail', [MC_Settings::class, 'handle_send_message_test_mail']);
            add_action('admin_post_mc_delete_unconfirmed_users', [MC_Settings::class, 'handle_delete_unconfirmed_users']);
            add_action('admin_post_mc_reset_global_icon_defaults', [MC_Settings::class, 'handle_reset_global_icon_defaults']);
            add_action('admin_post_mc_reset_category_icon_overrides', [MC_Settings::class, 'handle_reset_category_icon_overrides']);
            // Ensure required pages exist (also for future pages added during development).
            add_action('admin_init', [MC_Pages::class, 'maybe_ensure_required_pages']);
            // Warn if reserved METRA slugs (e.g. /mc-login/) are occupied by posts.
            add_action('admin_notices', [MC_Pages::class, 'admin_notices']);
        }

        // activation/deactivation
        register_activation_hook(MC_PLUGIN_FILE, [self::class, 'activate']);
        register_deactivation_hook(MC_PLUGIN_FILE, [self::class, 'deactivate']);

        // Font Awesome main-category icon admin + helpers
        MC_Icons::init();

        // roles/capabilities
        MC_Roles::init();

        // user activation + cleanup
        MC_User_Activation::init();

        // branded login (replaces wp-login.php for front-end users)
        MC_Login::init();

        // Conditional header/menu links (Register only when logged out,
        // Dashboard/Logout only when logged in)
        MC_Menu::init();

        // Frontend profile flows.
        MC_Profile::init();

        // Favorites storage layer (step 1: data basis only, no visible UI yet).
        MC_Favorites::init();

        // Listing reports from the single listing view.
        MC_Report::init();

        // Send the existing author notification when a pending listing is approved.
        add_action('transition_post_status', [MC_Emails::class, 'maybe_send_new_listing_author_mail_on_transition'], 20, 3);

        // AJAX actions for single listing UI.
        add_action('wp_ajax_mc_toggle_pause_listing', [MC_Listing_View::class, 'ajax_toggle_pause_listing']);

        // Listing view counter for single listings.
        MC_Views::init();

	        // Widgets
	        MC_Widgets::init();
    }

    public static function activate(): void {
        // Ensure CPT exists before flushing.
        MC_CPT::register();
        self::ensure_default_conditions();
        MC_Roles::ensure_roles();
        MC_Pages::ensure_required_pages();


        flush_rewrite_rules();
    }

    
/**
 * Ensure default Condition terms exist (canonical EN) in mc_condition taxonomy.
 */
private static function ensure_default_conditions(): void {
    if (!taxonomy_exists('mc_condition')) {
        return;
    }

    $defaults = [
        ['name' => 'New',          'slug' => 'new'],
        ['name' => 'Like New',     'slug' => 'like-new'],
        ['name' => 'Lightly Used', 'slug' => 'lightly-used'],
        ['name' => 'Used',         'slug' => 'used'],
    ];

    foreach ($defaults as $t) {
        if (!term_exists($t['slug'], 'mc_condition')) {
            wp_insert_term($t['name'], 'mc_condition', ['slug' => $t['slug']]);
        }
    }
}

public static function deactivate(): void {
        flush_rewrite_rules();
    }
/**
 * Lightweight self-check to prevent silent breakage after updates.
 * Shows an admin notice if critical registrations are missing.
 */
public static function mc_self_check_registrations(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    $missing = [];

    if (!post_type_exists('mc_listing')) {
        $missing[] = 'post_type:mc_listing';
    }
    if (!taxonomy_exists('mc_category')) {
        $missing[] = 'taxonomy:mc_category';
    }
    if (!taxonomy_exists('mc_condition')) {
        $missing[] = 'taxonomy:mc_condition';
    }
    if (!shortcode_exists('mc_listings')) {
        $missing[] = 'shortcode:mc_listings';
    }

    if (!empty($missing)) {
        echo '<div class="notice notice-error"><p><strong>METRA Classifieds:</strong> ' . esc_html__('Critical registrations missing:', 'metra-classifieds-plugin') . ' <code>' . esc_html(implode(', ', $missing)) . '</code></p></div>';
    }
}

}

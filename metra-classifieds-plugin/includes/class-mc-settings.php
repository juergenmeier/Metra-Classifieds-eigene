<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Settings {

    public static function register_menu(): void {
        // IMPORTANT:
        // The Listings CPT already registers a top-level admin menu (labelled "METRA Classifieds").
        // Creating another top-level page here would duplicate that menu.
        // Therefore we register the settings page as a submenu under the CPT menu.
        add_submenu_page(
            'edit.php?post_type=mc_listing',
            __('Settings', 'metra-classifieds-plugin'),
            __('Settings', 'metra-classifieds-plugin'),
            'manage_options',
            'metra-classifieds-settings',
            [self::class, 'render_page']
        );
    }

    public static function register_settings(): void {
        self::handle_settings_save();
        // BASIC settings group (saved only when the Basic tab is submitted)
        register_setting('mc_settings_basic', 'mc_submit_page_url', [
            'type' => 'string',
            // WordPress may pass NULL when the option field is missing from the current POST.
            // esc_url_raw(NULL) triggers a PHP 8.1+ deprecation and can break redirects.
            'sanitize_callback' => static function ($value) {
                if ($value === null) {
                    return '';
                }
                return esc_url_raw((string) $value);
            },
            'default' => '',
        ]);

        register_setting('mc_settings_basic', MC_CPT::LISTING_SLUG_OPTION, [
            'type' => 'string',
            'sanitize_callback' => [self::class, 'sanitize_listing_slug'],
            'default' => MC_CPT::LISTING_SLUG_DEFAULT,
        ]);

	    // Categories widget (global defaults)
	    register_setting('mc_settings_basic', 'mc_categories_widget_show_count', [
	        'type' => 'boolean',
	        'sanitize_callback' => [self::class, 'sanitize_checkbox'],
	        'default' => 1,
	    ]);

	    register_setting('mc_settings_basic', 'mc_categories_widget_only_with_listings', [
	        'type' => 'boolean',
	        'sanitize_callback' => [self::class, 'sanitize_checkbox'],
	        'default' => 0,
	    ]);

	    register_setting('mc_settings_basic', 'mc_categories_widget_root_term', [
	        'type' => 'integer',
	        'sanitize_callback' => [self::class, 'sanitize_root_term'],
	        'default' => 0,
	    ]);
        // === MC: Title max chars setting v0.0705 START ===
        register_setting('mc_settings_basic', 'mc_title_max_chars', [
            'type' => 'integer',
            'sanitize_callback' => static function ($value) {
                if ($value === null) {
                    return 35;
                }
                $v = (int) $value;
                return max(1, min(200, $v));
            },
            'default' => 35,
        ]);
        // === MC: Title max chars setting v0.0705 END ===

        register_setting('mc_settings_basic', 'mc_excerpt_words', [
    'type' => 'integer',
    // NULL-safe
    'sanitize_callback' => static function ($value) {
        if ($value === null) {
            return 20;
        }
        return MC_Settings::sanitize_int_range($value);
    },
    'default' => 20,
]);

        register_setting('mc_settings_basic', 'mc_thumb_w', [
    'type' => 'integer',
    'sanitize_callback' => static function ($value) {
        if ($value === null) {
            return 160;
        }
        return MC_Settings::sanitize_int_range($value);
    },
    'default' => 160,
]);

        register_setting('mc_settings_basic', 'mc_thumb_h', [
    'type' => 'integer',
    'sanitize_callback' => static function ($value) {
        if ($value === null) {
            return 120;
        }
        return MC_Settings::sanitize_int_range($value);
    },
    'default' => 120,
]);

        // Moderation mode: publish immediately or require admin approval.
        // Stored as string: 'immediate' | 'admin'
        register_setting('mc_settings_basic', 'mc_moderation_mode', [
            'type' => 'string',
            'sanitize_callback' => static function ($value) {
                $v = is_string($value) ? sanitize_key($value) : '';
                return in_array($v, ['immediate', 'admin'], true) ? $v : 'immediate';
            },
            'default' => 'immediate',
        ]);

        register_setting('mc_settings_basic', 'mc_max_images_per_listing', [
            'type' => 'integer',
            'sanitize_callback' => static function ($value) {
                if ($value === null) {
                    return 10;
                }
                return MC_Settings::sanitize_int_range($value, 1, 50);
            },
            'default' => 10,
        ]);

        // DESIGN settings group (saved only when the Design tab is submitted)
        register_setting('mc_settings_design', 'mc_design', [
            'type'              => 'array',
            'sanitize_callback' => [self::class, 'sanitize_design'],
            'default'           => [],
        ]);

        register_setting('mc_settings_report', 'mc_report_reasons', [
            'type' => 'string',
            'sanitize_callback' => [MC_Report::class, 'sanitize_reasons_text'],
            'default' => '',
        ]);

        // EMAIL settings group (saved only when the Email tab is submitted)
        register_setting('mc_settings_email', 'mc_email', [
            'type'              => 'array',
            'sanitize_callback' => [self::class, 'sanitize_email'],
            'default'           => [],
        ]);

        register_setting('mc_settings_messages', 'mc_messages', [
            'type'              => 'array',
            'sanitize_callback' => [self::class, 'sanitize_messages'],
            'default'           => [],
        ]);

        add_settings_section(
            'mc_main',
            __('Basic Settings', 'metra-classifieds-plugin'),
            function () {
                echo '<p>' . esc_html__('Minimal settings for the first stable build.', 'metra-classifieds-plugin') . '</p>';
            },
            'mc_settings_basic'
        );

        add_settings_section(
            'mc_design_section',
            __('Design', 'metra-classifieds-plugin'),
            function () {
                echo '<p>' . esc_html__('Basic design tokens. These settings only affect appearance.', 'metra-classifieds-plugin') . '</p>';
            },
            'mc_settings_design'
        );

        add_settings_section(
            'mc_report_section',
            __('Report', 'metra-classifieds-plugin'),
            function () {
                echo '<p>' . esc_html__('Define the available reasons for reporting a listing. One reason per line.', 'metra-classifieds-plugin') . '</p>';
            },
            'mc_settings_report'
        );

        add_settings_section(
            'mc_email_section',
            __('Email', 'metra-classifieds-plugin'),
            function () {
                echo '<p>' . esc_html__('Settings for outgoing e-mails (header/logo). The logo must be a publicly reachable JPG/PNG URL (HTTPS recommended).', 'metra-classifieds-plugin') . '</p>';
            },
            'mc_settings_email'
        );

        add_settings_section(
            'mc_messages_section',
            __('Message Texts', 'metra-classifieds-plugin'),
            function () {
                echo '<p>' . esc_html__('Additional intro/prefix texts for METRA system e-mails. Plain text only. Use [bold]...[/bold] for bold text and [big]...[/big] for a large bold headline. HTML is not allowed. Real blank lines are preserved.', 'metra-classifieds-plugin') . '</p>';
                echo MC_Messages::render_email_message_registry_admin();
            },
            'mc_settings_messages'
        );

        add_settings_field(
            'mc_contact_mail_to_seller_intro_enabled',
            __('Contact mail to seller: show intro text', 'metra-classifieds-plugin'),
            [self::class, 'field_contact_mail_to_seller_intro_enabled'],
            'mc_settings_messages',
            'mc_messages_section'
        );

        add_settings_field(
            'mc_contact_mail_to_seller_intro_text',
            __('Contact mail to seller', 'metra-classifieds-plugin'),
            [self::class, 'field_contact_mail_to_seller_intro_text'],
            'mc_settings_messages',
            'mc_messages_section'
        );

        add_settings_field(
            'mc_user_activation_email_intro_text',
            __('User activation e-mail', 'metra-classifieds-plugin'),
            [self::class, 'field_user_activation_email_intro_text'],
            'mc_settings_messages',
            'mc_messages_section'
        );

        add_settings_field(
            'mc_user_set_password_email_intro_text',
            __('User set password e-mail', 'metra-classifieds-plugin'),
            [self::class, 'field_user_set_password_email_intro_text'],
            'mc_settings_messages',
            'mc_messages_section'
        );
        add_settings_field(
            'mc_new_listing_author_email_intro_enabled',
            __('New listing e-mail to author', 'metra-classifieds-plugin'),
            [self::class, 'field_new_listing_author_email_intro_enabled'],
            'mc_settings_messages',
            'mc_messages_section'
        );
        add_settings_field(
            'mc_new_listing_author_email_intro_text',
            '',
            [self::class, 'field_new_listing_author_email_intro_text'],
            'mc_settings_messages',
            'mc_messages_section'
        );

        add_settings_field(
            'mc_submit_page_url',
            __('Submit page URL', 'metra-classifieds-plugin'),
            [self::class, 'field_submit_url'],
            'mc_settings_basic',
            'mc_main'
        );

        add_settings_field(
            'mc_listing_slug',
            __('Slug', 'metra-classifieds-plugin'),
            [self::class, 'field_listing_slug'],
            'mc_settings_basic',
            'mc_main'
        );

        add_settings_field(
            'mc_listing_counter',
            __('Listings counter', 'metra-classifieds-plugin'),
            [self::class, 'field_listing_counter'],
            'mc_settings_basic',
            'mc_main'
        );

        add_settings_field(
            'mc_unconfirmed_users_counter',
            __('Unconfirmed users', 'metra-classifieds-plugin'),
            [self::class, 'field_unconfirmed_users_counter'],
            'mc_settings_basic',
            'mc_main'
        );

	        // 3) Categories widget: show counters
	        add_settings_field(
	            'mc_categories_widget_show_count',
	            __('Categories widget: show counters', 'metra-classifieds-plugin'),
	            [self::class, 'field_categories_widget_show_count'],
	            'mc_settings_basic',
	            'mc_main'
	        );

	        // 4) Categories widget: only categories with listings
	        add_settings_field(
	            'mc_categories_widget_only_with_listings',
	            __('Categories widget: only categories with listings', 'metra-classifieds-plugin'),
	            [self::class, 'field_categories_widget_only_with_listings'],
	            'mc_settings_basic',
	            'mc_main'
	        );

	        // Categories widget: root selector
	        add_settings_field(
	            'mc_categories_widget_root_term',
	            __('Categories widget: root category', 'metra-classifieds-plugin'),
	            [self::class, 'field_categories_widget_root_term'],
	            'mc_settings_basic',
	            'mc_main'
	        );
        add_settings_field(
            'mc_report_reasons',
            __('Report reasons', 'metra-classifieds-plugin'),
            [self::class, 'field_report_reasons'],
            'mc_settings_report',
            'mc_report_section'
        );

// Email fields
        add_settings_field(
            'mc_email_logo_url',
            __('Email logo URL', 'metra-classifieds-plugin'),
            [self::class, 'field_email_logo_url'],
            'mc_settings_email',
            'mc_email_section'
        );

        add_settings_field(
            'mc_email_logo_max_width',
            __('Max. logo width (px)', 'metra-classifieds-plugin'),
            [self::class, 'field_email_logo_max_width'],
            'mc_settings_email',
            'mc_email_section'
        );

        add_settings_field(
            'mc_email_content_width',
            __('Mail content width (px)', 'metra-classifieds-plugin'),
            [self::class, 'field_email_content_width'],
            'mc_settings_email',
            'mc_email_section'
        );

        add_settings_field(
            'mc_email_footer_text',
            __('Mail footer text', 'metra-classifieds-plugin'),
            [self::class, 'field_email_footer_text'],
            'mc_settings_email',
            'mc_email_section'
        );

        add_settings_field(
            'mc_email_test_to',
            __('Test recipient e-mail', 'metra-classifieds-plugin'),
            [self::class, 'field_email_test_to'],
            'mc_settings_email',
            'mc_email_section'
        );

        add_settings_field(
            'mc_email_report_to',
            __('Report recipient e-mail', 'metra-classifieds-plugin'),
            [self::class, 'field_email_report_to'],
            'mc_settings_email',
            'mc_email_section'
        );

        // Design fields

        add_settings_field(
            'mc_design_home_layout',
            __('Desktop listing columns', 'metra-classifieds-plugin'),
            [self::class, 'field_design_home_layout'],
            'mc_settings_design',
            'mc_design_section'
        );



        add_settings_field(
            'mc_design_primary',
            __('Button color', 'metra-classifieds-plugin'),
            [self::class, 'field_design_primary'],
            'mc_settings_design',
            'mc_design_section'
        );

        // === MC: Listing title color field v0.0710 START ===
        add_settings_field(
            'mc_design_listing_title_color',
            __('Anzeigentitel-Farbe', 'metra-classifieds-plugin'),
            [self::class, 'field_design_listing_title_color'],
            'mc_settings_design',
            'mc_design_section'
        );
        // === MC: Listing title color field v0.0710 END ===

        // === MC: Home cat parent color field v0.0711 START ===
        add_settings_field(
            'mc_design_home_cat_parent_color',
            __('Hauptkategorien-Farbe (Sidebar)', 'metra-classifieds-plugin'),
            [self::class, 'field_design_home_cat_parent_color'],
            'mc_settings_design',
            'mc_design_section'
        );
        // === MC: Home cat parent color field v0.0711 END ===

        add_settings_field(
            'mc_design_text',
            __('Text color', 'metra-classifieds-plugin'),
            [self::class, 'field_design_text'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_font',
            __('Font family', 'metra-classifieds-plugin'),
            [self::class, 'field_design_font'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_listing_font',
            __('Listing text font (excerpt)', 'metra-classifieds-plugin'),
            [self::class, 'field_design_listing_font'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_listing_title_font',
            __('Listing title font', 'metra-classifieds-plugin'),
            [self::class, 'field_design_listing_title_font'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_title_size',
            __('Listing title font size', 'metra-classifieds-plugin'),
            [self::class, 'field_design_title_size'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_excerpt_size',
            __('Listing description font size', 'metra-classifieds-plugin'),
            [self::class, 'field_design_excerpt_size'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_title_weight',
            __('Listing title weight', 'metra-classifieds-plugin'),
            [self::class, 'field_design_title_weight'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_excerpt_weight',
            __('Listing description weight', 'metra-classifieds-plugin'),
            [self::class, 'field_design_excerpt_weight'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_radius',
            __('Corner radius', 'metra-classifieds-plugin'),
            [self::class, 'field_design_radius'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_border_width',
            __('Border width', 'metra-classifieds-plugin'),
            [self::class, 'field_design_border_width'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_input_radius',
            __('Input radius', 'metra-classifieds-plugin'),
            [self::class, 'field_design_input_radius'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_button_radius',
            __('Button radius', 'metra-classifieds-plugin'),
            [self::class, 'field_design_button_radius'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_gap',
            __('Grid gap', 'metra-classifieds-plugin'),
            [self::class, 'field_design_gap'],
            'mc_settings_design',
            'mc_design_section'
        );

        add_settings_field(
            'mc_design_fa_icons_enabled',
            __('Enable category icons', 'metra-classifieds-plugin'),
            [self::class, 'field_design_fa_icons_enabled'],
            'mc_settings_design',
            'mc_design_section'
        );

// === MC: Title max chars field v0.0705 START ===
add_settings_field(
    'mc_title_max_chars',
    __('Anzahl Zeichen im Anz.-Titel', 'metra-classifieds-plugin'),
    [self::class, 'field_title_max_chars'],
    'mc_settings_basic',
    'mc_main'
);
// === MC: Title max chars field v0.0705 END ===

add_settings_field(
    'mc_excerpt_words',
    __('Listing excerpt length (words)', 'metra-classifieds-plugin'),
    [self::class, 'field_excerpt_words'],
    'mc_settings_basic',
    'mc_main'
);

add_settings_field(
    'mc_thumb_w',
    __('Listing thumbnail width (px)', 'metra-classifieds-plugin'),
    [self::class, 'field_thumb_w'],
    'mc_settings_basic',
    'mc_main'
);

add_settings_field(
    'mc_thumb_h',
    __('Listing thumbnail height (px)', 'metra-classifieds-plugin'),
    [self::class, 'field_thumb_h'],
    'mc_settings_basic',
    'mc_main'
);

add_settings_field(
    'mc_moderation_mode',
    __('Listing moderation', 'metra-classifieds-plugin'),
    [self::class, 'field_moderation_mode'],
    'mc_settings_basic',
    'mc_main'
);

add_settings_field(
    'mc_max_images_per_listing',
    __('Max. images per listing', 'metra-classifieds-plugin'),
    [self::class, 'field_max_images_per_listing'],
    'mc_settings_basic',
    'mc_main'
);

    }

    public static function field_submit_url(): void {
        $val = (string) get_option('mc_submit_page_url', '');
        echo '<input type="url" class="regular-text" name="mc_submit_page_url" value="' . esc_attr($val) . '" placeholder="https://example.com/submit/" />';
        echo '<p class="description">' . esc_html__('Used as fallback for the [mc_submit_link] shortcode.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_listing_slug(): void {
        $val = MC_CPT::get_listing_slug();
        echo '<input type="text" class="regular-text" name="' . esc_attr(MC_CPT::LISTING_SLUG_OPTION) . '" value="' . esc_attr($val) . '" placeholder="' . esc_attr(MC_CPT::LISTING_SLUG_DEFAULT) . '" />';
        echo '<p class="description">' . esc_html__('Defines the URL base for single listings, for example /listing/sample-listing/. Use only lowercase letters, numbers and hyphens. After changing this value, METRA refreshes the rewrite rules automatically.', 'metra-classifieds-plugin') . '</p>';
    }
    public static function field_listing_counter(): void {
        $counts = wp_count_posts('mc_listing');
        $active   = isset($counts->publish) ? (int) $counts->publish : 0;
        $inactive = isset($counts->pending) ? (int) $counts->pending : 0;
        $draft    = isset($counts->draft) ? (int) $counts->draft : 0;

        echo '<div style="display:flex;gap:18px;flex-wrap:wrap">';
        echo '<div><strong>' . esc_html__('Active', 'metra-classifieds-plugin') . ':</strong> ' . esc_html((string) $active) . '</div>';
        echo '<div><strong>' . esc_html__('Not active', 'metra-classifieds-plugin') . ':</strong> ' . esc_html((string) $inactive) . '</div>';
        echo '<div><strong>' . esc_html__('Draft', 'metra-classifieds-plugin') . ':</strong> ' . esc_html((string) $draft) . '</div>';
        echo '</div>';
        echo '<p class="description">' . esc_html__('Counts all listings (post type mc_listing) by status.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_unconfirmed_users_counter(): void {
        $count = MC_User_Activation::get_pending_user_count();
        $confirm = esc_js(__('Do you really want to delete all unconfirmed users?', 'metra-classifieds-plugin'));

        echo '<div><strong>' . esc_html((string) $count) . '</strong></div>';
        echo '<p class="description">' . esc_html__('Users that registered but have not confirmed their activation link yet.', 'metra-classifieds-plugin') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'' . $confirm . '\');" style="margin-top:10px;">';
        wp_nonce_field('mc_delete_unconfirmed_users');
        echo '<input type="hidden" name="action" value="mc_delete_unconfirmed_users" />';
        submit_button(__('Unconfirmed users löschen', 'metra-classifieds-plugin'), 'secondary', 'submit', false);
        echo '</form>';
    }

	public static function field_categories_widget_show_count(): void {
	    $val = (int) get_option('mc_categories_widget_show_count', 1);
	    echo '<label><input type="checkbox" name="mc_categories_widget_show_count" value="1" ' . checked(1, $val, false) . '> ' . esc_html__('Show listing counters next to categories (widget default).', 'metra-classifieds-plugin') . '</label>';
	}

	public static function field_categories_widget_only_with_listings(): void {
	    $val = (int) get_option('mc_categories_widget_only_with_listings', 0);
	    echo '<label><input type="checkbox" name="mc_categories_widget_only_with_listings" value="1" ' . checked(1, $val, false) . '> ' . esc_html__('Only show categories that contain listings (directly or in descendants).', 'metra-classifieds-plugin') . '</label>';
	}

	public static function field_categories_widget_root_term(): void {
	    $current = (int) get_option('mc_categories_widget_root_term', 0);
	    $terms = get_terms([
	        'taxonomy' => 'mc_category',
	        'hide_empty' => false,
	        'parent' => 0,
	        'orderby' => 'name',
	        'order' => 'ASC',
	    ]);

	    echo '<select name="mc_categories_widget_root_term">';
	    echo '<option value="0" ' . selected(0, $current, false) . '>' . esc_html__('All categories (full tree)', 'metra-classifieds-plugin') . '</option>';
	    if (!is_wp_error($terms)) {
	        foreach ($terms as $t) {
	            echo '<option value="' . (int) $t->term_id . '" ' . selected((int) $t->term_id, $current, false) . '>' . esc_html($t->name) . '</option>';
	        }
	    }
	    echo '</select>';
	    echo '<p class="description">' . esc_html__('Root category for the Categories widget.', 'metra-classifieds-plugin') . '</p>';
	}


    public static function field_design_fa_icons_enabled(): void {
        $design = get_option('mc_design', []);
        $val = !is_array($design) || !array_key_exists('fa_icons_enabled', $design) ? 1 : (int) (!empty($design['fa_icons_enabled']));
        echo '<label><input type="checkbox" name="mc_design[fa_icons_enabled]" value="1" ' . checked(1, $val, false) . '> ' . esc_html__('Show Lucide category icons in the frontend.', 'metra-classifieds-plugin') . '</label>';
        echo '<p class="description">' . esc_html__('When disabled, Lucide category icons are hidden without deleting existing icon assignments or overrides.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_fa_icon_size(): void {
        $design = get_option('mc_design', []);
        $val = is_array($design) && isset($design['fa_icon_size']) ? (int) $design['fa_icon_size'] : 20;
        $val = max(12, min(48, $val));
        echo '<input type="number" min="12" max="48" step="1" name="mc_design[fa_icon_size]" value="' . esc_attr((string) $val) . '" style="width:100px;" /> px';
        echo '<p class="description">' . esc_html__('Global default for Lucide category icons. Individual category sizes can still override this value.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_fa_icon_color(): void {
        $design = get_option('mc_design', []);
        $val = is_array($design) && isset($design['fa_icon_color']) && is_string($design['fa_icon_color']) ? $design['fa_icon_color'] : '#297bc1';
        $val = sanitize_hex_color((string) $val);
        if ($val === null || $val === '') {
            $val = '#297bc1';
        }
        echo '<input type="color" name="mc_design[fa_icon_color]" value="' . esc_attr($val) . '" /> <code>' . esc_html($val) . '</code>';
        echo '<p class="description">' . esc_html__('Global default color for Lucide category icons. Individual category colors can still override this value.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_icon_reset_actions(): void {
        echo '<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(' . wp_json_encode(__('Reset the global icon defaults to 15 px and #297bc1?', 'metra-classifieds-plugin')) . ');">';
        wp_nonce_field('mc_reset_global_icon_defaults');
        echo '<input type="hidden" name="action" value="mc_reset_global_icon_defaults" />';
        submit_button(__('Reset global icon defaults', 'metra-classifieds-plugin'), 'secondary', 'submit', false);
        echo '</form>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(' . wp_json_encode(__('Delete all individual category icon color and size overrides?', 'metra-classifieds-plugin')) . ');">';
        wp_nonce_field('mc_reset_category_icon_overrides');
        echo '<input type="hidden" name="action" value="mc_reset_category_icon_overrides" />';
        submit_button(__('Reset all category icon overrides', 'metra-classifieds-plugin'), 'secondary', 'submit', false);
        echo '</form>';

        echo '</div>';
        echo '<p class="description">' . esc_html__('The first reset restores the global defaults only. The second reset removes individual category color and size overrides but keeps manually selected icon assignments.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function handle_reset_global_icon_defaults(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'metra-classifieds-plugin'));
        }
        check_admin_referer('mc_reset_global_icon_defaults');

        $design = get_option('mc_design', []);
        if (!is_array($design)) {
            $design = [];
        }
        $design['fa_icons_enabled'] = 1;
        $design['fa_icon_size'] = '15';
        $design['fa_icon_color'] = '#297bc1';
        update_option('mc_design', self::sanitize_design($design));

        $url = add_query_arg([
            'tab' => 'design',
            'mc_icon_defaults_reset' => '1',
        ], self::get_settings_base_url());

        wp_safe_redirect($url);
        exit;
    }

    public static function handle_reset_category_icon_overrides(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'metra-classifieds-plugin'));
        }
        check_admin_referer('mc_reset_category_icon_overrides');

        $terms = get_terms([
            'taxonomy'   => 'mc_category',
            'hide_empty' => false,
            'fields'     => 'ids',
        ]);

        if (is_array($terms)) {
            foreach ($terms as $term_id) {
                delete_term_meta((int) $term_id, '_mc_fa_color');
                delete_term_meta((int) $term_id, '_mc_fa_size');
            }
        }

        $url = add_query_arg([
            'tab' => 'design',
            'mc_icon_overrides_reset' => '1',
        ], self::get_settings_base_url());

        wp_safe_redirect($url);
        exit;
    }

public static function sanitize_int_range($value): int {
    $n = (int) $value;
    // Hard safety limits; individual fields can apply their own UI hints.
    if ($n < 0) {
        $n = 0;
    }
    if ($n > 2000) {
        $n = 2000;
    }
    return $n;
}

/**
 * Checkbox sanitizer (0/1).
 */
public static function sanitize_listing_slug($value): string {
    if (!is_string($value)) {
        return MC_CPT::LISTING_SLUG_DEFAULT;
    }

    $slug = sanitize_title($value);
    return $slug !== '' ? $slug : MC_CPT::LISTING_SLUG_DEFAULT;
}

public static function sanitize_checkbox($value): int {
    return empty($value) ? 0 : 1;
}

/**
 * Root term sanitizer (0 = full tree).
 */
public static function sanitize_root_term($value): int {
    $term_id = absint($value);
    if (0 === $term_id) {
        return 0;
    }
    $term = get_term($term_id, 'mc_category');
    return ($term && ! is_wp_error($term)) ? $term_id : 0;
}

// === MC: Title max chars callback v0.0705 START ===
public static function field_title_max_chars(): void {
    $val = (int) get_option('mc_title_max_chars', 35);
    $val = max(1, min(200, $val));
    echo '<input type="number" min="1" max="200" step="1" name="mc_title_max_chars" value="' . esc_attr((string) $val) . '" placeholder="' . esc_attr('Max. ' . $val) . '" style="width:90px;" />';
    echo '<p class="description">' . esc_html__('Maximale Zeichenanzahl für den Anzeigentitel. Standard: 35.', 'metra-classifieds-plugin') . '</p>';
}
// === MC: Title max chars callback v0.0705 END ===

public static function field_excerpt_words(): void {
    $val = (int) get_option('mc_excerpt_words', 20);
    $val = max(0, min(80, $val));
    echo '<input type="number" min="0" max="80" step="1" name="mc_excerpt_words" value="' . esc_attr((string) $val) . '" style="width:90px;" />';
    echo '<p class="description">' . esc_html__('Number of words shown under each listing title. Use 0 to hide the excerpt.', 'metra-classifieds-plugin') . '</p>';
}

public static function field_thumb_w(): void {
    $val = (int) get_option('mc_thumb_w', 160);
    $val = max(40, min(600, $val));
    echo '<input type="number" min="40" max="600" step="1" name="mc_thumb_w" value="' . esc_attr((string) $val) . '" style="width:90px;" /> px';
    echo '<p class="description">' . esc_html__('Thumbnail width in the listings overview.', 'metra-classifieds-plugin') . '</p>';
}

public static function field_thumb_h(): void {
    $val = (int) get_option('mc_thumb_h', 120);
    $val = max(40, min(600, $val));
    echo '<input type="number" min="40" max="600" step="1" name="mc_thumb_h" value="' . esc_attr((string) $val) . '" style="width:90px;" /> px';
    echo '<p class="description">' . esc_html__('Thumbnail height in the listings overview.', 'metra-classifieds-plugin') . '</p>';
}

    /**
     * Moderation / approval mode for new listings.
     *
     * - immediate: listing is published right away
     * - admin: listing is pending unless user can publish_posts
     */
    public static function field_moderation_mode(): void {
        $val = (string) get_option('mc_moderation_mode', 'immediate');
        if (!in_array($val, ['immediate', 'admin'], true)) {
            $val = 'immediate';
        }

        echo '<select name="mc_moderation_mode">';
        echo '<option value="immediate"' . selected($val, 'immediate', false) . '>' . esc_html__('Publish immediately', 'metra-classifieds-plugin') . '</option>';
        echo '<option value="admin"' . selected($val, 'admin', false) . '>' . esc_html__('Require admin approval (pending)', 'metra-classifieds-plugin') . '</option>';
        echo '</select>';

        echo '<p class="description">' . esc_html__('Choose whether new listings are visible immediately or require administrator approval first.', 'metra-classifieds-plugin') . '</p>';
    }

    /**
     * Sanitize the mc_design option array.
     *
     * @param mixed $value
     * @return array<string, string>
     */

    public static function field_max_images_per_listing(): void {
        $val = (int) get_option('mc_max_images_per_listing', 10);
        if ($val < 1) { $val = 1; }
        if ($val > 50) { $val = 50; }

        echo '<input type="number" name="mc_max_images_per_listing" value="' . esc_attr((string) $val) . '" min="1" max="50" step="1" style="width:120px;" />';
        echo '<p class="description">' . esc_html__('Limits how many images can be uploaded per listing (gallery).', 'metra-classifieds-plugin') . '</p>';
    }

public static function sanitize_design($value): array {
        if (!is_array($value)) {
            return [];
        }
        $out = [];

        // Homepage layout (desktop)
        if (isset($value['home_layout']) && is_string($value['home_layout'])) {
            $hl = sanitize_key($value['home_layout']);
            if (in_array($hl, ['sidebar_2col', 'onecol'], true)) {
                $out['home_layout'] = $hl;
            }
        }

        // Button color
        if (isset($value['primary'])) {
            $c = is_string($value['primary']) ? trim($value['primary']) : '';
            $c = sanitize_text_field($c);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1) {
                $out['primary'] = strtolower($c);
            }
        }

        // Headline color
        if (isset($value['heading'])) {
            $c = is_string($value['heading']) ? trim($value['heading']) : '';
            $c = sanitize_text_field($c);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1) {
                $out['heading'] = strtolower($c);
            }
        }

        // === MC: Listing title color sanitize v0.0710 START ===
        if (isset($value['listing_title_color'])) {
            $c = is_string($value['listing_title_color']) ? trim($value['listing_title_color']) : '';
            $c = sanitize_text_field($c);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1) {
                $out['listing_title_color'] = strtolower($c);
            }
        }
        // === MC: Listing title color sanitize v0.0710 END ===

        // === MC: Home cat parent color sanitize v0.0711 START ===
        if (isset($value['home_cat_parent_color'])) {
            $c = is_string($value['home_cat_parent_color']) ? trim($value['home_cat_parent_color']) : '';
            $c = sanitize_text_field($c);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1) {
                $out['home_cat_parent_color'] = strtolower($c);
            }
        }
        // === MC: Home cat parent color sanitize v0.0711 END ===

        // Text color
        if (isset($value['text'])) {
            $c = is_string($value['text']) ? trim($value['text']) : '';
            $c = sanitize_text_field($c);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1) {
                $out['text'] = strtolower($c);
            }
        }

        // Font family
        if (isset($value['font'])) {
            $f = is_string($value['font']) ? trim($value['font']) : '';
            $f = sanitize_text_field($f);
            // Allow a safe set of characters common in font-family values.
            $f = preg_replace('/[^a-zA-Z0-9\s\-\,\"\'\(\)\.]/', '', $f ?? '');
            if ($f === '') {
                $f = 'inherit';
            }
            $out['font'] = $f;
        }

        // Listing text font (excerpt/description)
        if (isset($value['listing_font'])) {
            $f = is_string($value['listing_font']) ? trim($value['listing_font']) : '';
            $f = sanitize_text_field($f);
            $allowed = ['inherit', '"MC Roboto"', '"MC Roboto Condensed"'];
            if (in_array($f, $allowed, true)) {
                $out['listing_font'] = $f;
            }
        }

        // Listing title font
        if (isset($value['listing_title_font'])) {
            $f = is_string($value['listing_title_font']) ? trim($value['listing_title_font']) : '';
            $f = sanitize_text_field($f);
            $allowed = ['inherit', '"MC Roboto"', '"MC Roboto Condensed"'];
            if (in_array($f, $allowed, true)) {
                $out['listing_title_font'] = $f;
            }
        }

        // Listing title font size (expects px)
        if (isset($value['title_size'])) {
            $ts = is_string($value['title_size']) ? trim($value['title_size']) : '';
            $ts = sanitize_text_field($ts);
            if (preg_match('/^([0-9]{1,2})\s*px$/', $ts, $m) === 1) {
                $n = (int) $m[1];
                $n = max(10, min(32, $n));
                $out['title_size'] = (string) $n . 'px';
            }
        }

        // Listing excerpt/description font size (expects px)
        if (isset($value['excerpt_size'])) {
            $es = is_string($value['excerpt_size']) ? trim($value['excerpt_size']) : '';
            $es = sanitize_text_field($es);
            if (preg_match('/^([0-9]{1,2})\s*px$/', $es, $m) === 1) {
                $n = (int) $m[1];
                $n = max(10, min(28, $n));
                $out['excerpt_size'] = (string) $n . 'px';
            }
        }

        // Listing title weight
        if (isset($value['title_weight'])) {
            $tw = is_string($value['title_weight']) ? trim($value['title_weight']) : '';
            $tw = sanitize_text_field($tw);
            if ($tw === 'bold') {
                $out['title_weight'] = '700';
            } elseif ($tw === 'normal') {
                $out['title_weight'] = '400';
            }
        }

        // Listing description/excerpt weight
        if (isset($value['excerpt_weight'])) {
            $ew = is_string($value['excerpt_weight']) ? trim($value['excerpt_weight']) : '';
            $ew = sanitize_text_field($ew);
            if ($ew === 'bold') {
                $out['excerpt_weight'] = '700';
            } elseif ($ew === 'normal') {
                $out['excerpt_weight'] = '400';
            }
        }

        // Radius (expects px)
        if (isset($value['radius'])) {
            $r = is_string($value['radius']) ? trim($value['radius']) : '';
            $r = sanitize_text_field($r);
            // Accept "Npx" only, clamp to a sensible range.
            if (preg_match('/^([0-9]{1,3})\s*px$/', $r, $m) === 1) {
                $n = (int) $m[1];
                $n = max(0, min(60, $n));
                $out['radius'] = (string) $n . 'px';
            }
        }

        // Border width (expects px)
        if (isset($value['border_width'])) {
            $bw = is_string($value['border_width']) ? trim($value['border_width']) : '';
            $bw = sanitize_text_field($bw);
            if (preg_match('/^([0-9]{1,2})\s*px$/', $bw, $m) === 1) {
                $n = (int) $m[1];
                $n = max(0, min(6, $n));
                $out['border_width'] = (string) $n . 'px';
            }
        }

        // Component radii (expects px)
        foreach (['input_radius', 'button_radius'] as $rk) {
            if (!isset($value[$rk])) {
                continue;
            }
            $rv = is_string($value[$rk]) ? trim($value[$rk]) : '';
            $rv = sanitize_text_field($rv);
            if (preg_match('/^([0-9]{1,3})\s*px$/', $rv, $m) === 1) {
                $n = (int) $m[1];
                $n = max(0, min(60, $n));
                $out[$rk] = (string) $n . 'px';
            }
        }

        // Spacing (expects px)
        foreach (['gap' => 40] as $sk => $max) {
            if (!isset($value[$sk])) {
                continue;
            }
            $sv = is_string($value[$sk]) ? trim($value[$sk]) : '';
            $sv = sanitize_text_field($sv);
            if (preg_match('/^([0-9]{1,3})\s*px$/', $sv, $m) === 1) {
                $n = (int) $m[1];
                $n = max(0, min((int) $max, $n));
                $out[$sk] = (string) $n . 'px';
            }
        }

        // Store last selected preset key (does not affect tokens unless applied).

        $out['fa_icons_enabled'] = isset($value['fa_icons_enabled']) && (string) $value['fa_icons_enabled'] === '1' ? 1 : 0;

        if (isset($value['fa_icon_size'])) {
            $n = (int) $value['fa_icon_size'];
            $n = max(12, min(48, $n));
            $out['fa_icon_size'] = (string) $n;
        } else {
            $out['fa_icon_size'] = '15';
        }

        if (isset($value['fa_icon_color'])) {
            $c = is_string($value['fa_icon_color']) ? trim($value['fa_icon_color']) : '';
            $c = sanitize_text_field($c);
            if (preg_match('/^#[0-9a-fA-F]{6}$/', $c) === 1) {
                $out['fa_icon_color'] = strtolower($c);
            } else {
                $out['fa_icon_color'] = '#297bc1';
            }
        } else {
            $out['fa_icon_color'] = '#297bc1';
        }

        return $out;
    }

    /**
     * Sanitize the mc_email option array.
     *
     * @param mixed $value
     * @return array<string, string>
     */
    public static function sanitize_email($value): array {
        if (!is_array($value)) {
            return [];
        }

        $out = [];

        // Logo URL (must be http/https and point to a JPG/PNG).
        if (isset($value['logo_url'])) {
            $u = is_string($value['logo_url']) ? trim($value['logo_url']) : '';
            $u = esc_url_raw($u);

            if ($u !== '') {
                $scheme = (string) wp_parse_url($u, PHP_URL_SCHEME);
                if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
                    $u = '';
                }

                // Basic file type check via path extension.
                $path = (string) wp_parse_url($u, PHP_URL_PATH);
                $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                    // Invalid/unknown extension – keep empty to avoid broken images.
                    $u = '';
                }
            }

            $out['logo_url'] = $u;
        }

        // Max logo width in pixels.
        if (isset($value['logo_max_w'])) {
            $n = is_numeric($value['logo_max_w']) ? (int) $value['logo_max_w'] : 240;
            $n = max(50, min(800, $n));
            $out['logo_max_w'] = (string) $n;
        }

        if (isset($value['content_w'])) {
            $n = is_numeric($value['content_w']) ? (int) $value['content_w'] : 680;
            $n = max(420, min(900, $n));
            $out['content_w'] = (string) $n;
        }

        if (isset($value['footer_text'])) {
            $out['footer_text'] = is_string($value['footer_text']) ? wp_unslash($value['footer_text']) : '';
        }

        if (isset($value['test_to'])) {
            $out['test_to'] = sanitize_email((string) $value['test_to']);
        }

        if (isset($value['report_to'])) {
            $out['report_to'] = sanitize_email((string) $value['report_to']);
        }

        return $out;
    }

    /**
     * Sanitize the mc_messages option array.
     *
     * @param mixed $value
     * @return array<string, string>
     */
    public static function sanitize_messages($value): array {
        if (!is_array($value)) {
            return [];
        }

        $out = [];
        $out['contact_mail_to_seller_intro_enabled'] = self::sanitize_checkbox($value['contact_mail_to_seller_intro_enabled'] ?? 0) ? '1' : '0';
        $out['contact_mail_to_seller_intro_text'] = MC_Messages::sanitize_textarea_preserve_blank_lines($value['contact_mail_to_seller_intro_text'] ?? '');
        $out['user_activation_email_intro_text'] = MC_Messages::sanitize_textarea_preserve_blank_lines($value['user_activation_email_intro_text'] ?? '');
        $out['user_set_password_email_intro_text'] = MC_Messages::sanitize_textarea_preserve_blank_lines($value['user_set_password_email_intro_text'] ?? '');
        $out['new_listing_author_email_intro_enabled'] = self::sanitize_checkbox($value['new_listing_author_email_intro_enabled'] ?? 0) ? '1' : '0';
        $out['new_listing_author_email_intro_text'] = MC_Messages::sanitize_textarea_preserve_blank_lines($value['new_listing_author_email_intro_text'] ?? '');

        return $out;
    }

    /**
     * @return array<string, array<string, string>>
     */

    // === MC: write_tokens_to_master_css v0.0714 START ===
    /**
     * Schreibt die aktuellen Design-Tokens als :root-Block ans Ende von metra-master.css.
     * Ersetzt dabei einen vorhandenen METRA-TOKENS-Block, damit keine Duplikate entstehen.
     */
    private static function write_tokens_to_master_css(): void {
        $path = \MC\Master_CSS::TARGET_PATH;
        if (!is_writable($path) && !is_writable(dirname($path))) {
            return;
        }
        $tokens = MC_Design::get_tokens_public();
        if ($tokens === '') {
            return;
        }
        $block = "\n/* METRA TOKENS BLOCK START */\n" . $tokens . "\n/* METRA TOKENS BLOCK END */\n";
        $existing = is_file($path) ? (string) file_get_contents($path) : '';
        // Vorhandenen Block ersetzen
        $existing = preg_replace(
            '/\n\/\* METRA TOKENS BLOCK START \*\/.*?\/\* METRA TOKENS BLOCK END \*\/\n/s',
            '',
            $existing
        );
        file_put_contents($path, $existing . $block);
    }
    // === MC: write_tokens_to_master_css v0.0714 END ===

    public static function get_design_option(string $key, string $default = ''): string {
        $opt = get_option('mc_design', []);
        if (!is_array($opt)) {
            return $default;
        }
        $v = $opt[$key] ?? $default;
        return is_string($v) ? $v : $default;
    }

    private static function get_email_option(string $key, string $default = ''): string {
        $opt = get_option('mc_email', []);
        if (!is_array($opt)) {
            return $default;
        }
        $v = $opt[$key] ?? $default;
        return is_string($v) ? $v : $default;
    }


    public static function field_report_reasons(): void {
        $value = get_option('mc_report_reasons', '');
        if (!is_string($value)) {
            $value = '';
        }

        echo '<textarea name="mc_report_reasons" rows="8" class="large-text code">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('One report reason per line. These reasons appear in the report popup.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_email_logo_url(): void {
        $val = self::get_email_option('logo_url', '');
        echo '<input type="url" name="mc_email[logo_url]" value="' . esc_attr($val) . '" class="regular-text" placeholder="https://.../logo.png" />';
        echo '<p class="description">' . esc_html__('Publicly reachable JPG/PNG URL. HTTPS recommended. If the URL is invalid or not a JPG/PNG, it will be cleared on save.', 'metra-classifieds-plugin') . '</p>';

        // Preview logo below URL (JS only to avoid any server-side issues)
        if (!empty($val)) {
            $maxw = (int) self::get_email_option('logo_max_w', '240');
            if ($maxw <= 0) { $maxw = 240; }

            echo '<div id="mc-email-logo-preview" data-logo-url="' . esc_attr($val) . '" data-logo-maxw="' . esc_attr((string) $maxw) . '" style="margin-top:8px;"></div>';
            echo '<script>
            (function(){
                var el=document.getElementById("mc-email-logo-preview");
                if(!el) return;
                var url=el.getAttribute("data-logo-url")||"";
                var maxw=parseInt(el.getAttribute("data-logo-maxw")||"240",10);
                if(!url) return;
                var a=document.createElement("a");
                a.href=url; a.target="_blank"; a.rel="noopener";
                var img=document.createElement("img");
                img.src=url;
                img.alt="Mail Logo Preview";
                img.style.maxWidth=(isNaN(maxw)?240:maxw)+"px";
                img.style.height="auto";
                img.style.display="inline-block";
                img.style.border="1px solid #e5e5e5";
                img.style.borderRadius="6px";
                img.style.padding="6px";
                img.style.background="#fff";
                a.appendChild(img);
                el.appendChild(a);
            })();
            </script>';
        }
    }

    public static function field_email_logo_max_width(): void {
        $val = self::get_email_option('logo_max_w', '240');
        $n = is_numeric($val) ? (int) $val : 240;
        $n = max(50, min(800, $n));
        echo '<input type="number" min="50" max="800" step="1" name="mc_email[logo_max_w]" value="' . esc_attr((string) $n) . '" style="width:90px;" /> px';
        echo '<p class="description">' . esc_html__('Maximum displayed logo width in outgoing HTML e-mails.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_email_content_width(): void {
        $val = self::get_email_option('content_w', '680');
        $n = is_numeric($val) ? (int) $val : 680;
        $n = max(420, min(900, $n));
        echo '<input type="number" min="420" max="900" step="1" name="mc_email[content_w]" value="' . esc_attr((string) $n) . '" style="width:90px;" /> px';
        echo '<p class="description">' . esc_html__('Maximum content width of the central METRA mail template.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_email_footer_text(): void {
        $val = self::get_email_option('footer_text', '');
        echo '<textarea name="mc_email[footer_text]" rows="4" class="large-text" placeholder="' . esc_attr__('Optional footer text for all METRA e-mails.', 'metra-classifieds-plugin') . '">' . esc_textarea($val) . '</textarea>';
        echo '<p class="description">' . esc_html__('Shown below the mail content in the central METRA mail template. HTML is allowed. WARNING: Invalid HTML will NOT be corrected. Broken code may result in missing links or layout issues.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_email_test_to(): void {
        $val = self::get_email_option('test_to', (string) get_option('admin_email', ''));
        echo '<input type="email" name="mc_email[test_to]" value="' . esc_attr($val) . '" class="regular-text" placeholder="name@example.com" />';
        echo '<p class="description">' . esc_html__('Used by the METRA mail test button below. Leave empty to use the WordPress admin e-mail address.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_email_report_to(): void {
        $val = self::get_email_option('report_to', '');
        echo '<input type="email" name="mc_email[report_to]" value="' . esc_attr($val) . '" class="regular-text" placeholder="name@example.com" />';
        echo '<p class="description">' . esc_html__('If left empty, reports are sent to the admin address.', 'metra-classifieds-plugin') . '</p>';
    }

    private static function get_messages_option(string $key, string $default = ''): string {
        $opt = get_option('mc_messages', []);
        if (!is_array($opt)) {
            return $default;
        }

        $value = $opt[$key] ?? $default;
        return is_string($value) ? $value : $default;
    }

    private static function render_message_test_ui(string $message_key): void {
        $url = MC_Messages::get_message_test_url($message_key);
        if ($url === '') {
            return;
        }

        echo '<div style="margin:0 0 8px 0;">';
        echo '<div style="margin:0 0 6px 0;font-size:12px;color:#666;">' . esc_html__('Send Test-Mail to Admin', 'metra-classifieds-plugin') . '</div>';
        echo '<a class="button button-secondary" href="' . esc_url($url) . '">' . esc_html__('Test', 'metra-classifieds-plugin') . '</a>';
        echo '</div>';
    }

    public static function field_contact_mail_to_seller_intro_enabled(): void {
        $checked = self::get_messages_option('contact_mail_to_seller_intro_enabled', '1');
        echo '<label><input type="checkbox" name="mc_messages[contact_mail_to_seller_intro_enabled]" value="1" ' . checked($checked, '1', false) . ' /> ' . esc_html__('Show this intro text in the contact mail to the seller.', 'metra-classifieds-plugin') . '</label>';
    }

    public static function field_contact_mail_to_seller_intro_text(): void {
        self::render_message_test_ui('contact_mail_to_seller_intro');
        $default = MC_Messages::default_contact_mail_to_seller_intro_text();
        $stored = self::get_messages_option('contact_mail_to_seller_intro_text', '');
        $value = $stored !== '' ? $stored : $default;
        echo '<textarea name="mc_messages[contact_mail_to_seller_intro_text]" rows="8" class="large-text code" placeholder="' . esc_attr($default) . '">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Shown below the mail logo (if present) and above the existing contact-mail details. Plain text only. Use [bold]...[/bold] for bold text and [big]...[/big] for a large bold headline. Real blank lines are preserved. Leave the field empty to use the translated default text from the language files.', 'metra-classifieds-plugin') . '</p>';
    }


    public static function field_user_activation_email_intro_text(): void {
        self::render_message_test_ui('user_activation_email');
        $default = MC_Messages::default_user_activation_email_intro_text();
        $stored = self::get_messages_option('user_activation_email_intro_text', '');
        $value = $stored !== '' ? $stored : $default;
        echo '<textarea name="mc_messages[user_activation_email_intro_text]" rows="9" class="large-text code" placeholder="' . esc_attr($default) . '">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Mandatory intro text shown below the mail logo (if present) and above the existing activation-mail content. Plain text only. Use [bold]...[/bold] for bold text and [big]...[/big] for a large bold headline. Real blank lines are preserved. Leave the field empty to use the translated default text from the language files.', 'metra-classifieds-plugin') . '</p>';
    }


    public static function field_user_set_password_email_intro_text(): void {
        self::render_message_test_ui('user_set_password_email');
        $default = MC_Messages::default_user_set_password_email_intro_text();
        $stored = self::get_messages_option('user_set_password_email_intro_text', '');
        $value = $stored !== '' ? $stored : $default;
        echo '<textarea name="mc_messages[user_set_password_email_intro_text]" rows="9" class="large-text code" placeholder="' . esc_attr($default) . '">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Mandatory intro text shown below the mail logo (if present) and above the existing set-password mail content. Plain text only. Use [bold]...[/bold] for bold text and [big]...[/big] for a large bold headline. Real blank lines are preserved. Leave the field empty to use the translated default text from the language files.', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_new_listing_author_email_intro_enabled(): void {
        $checked = self::get_messages_option('new_listing_author_email_intro_enabled', '1');
        echo '<label><input type="checkbox" name="mc_messages[new_listing_author_email_intro_enabled]" value="1" ' . checked($checked, '1', false) . ' /> ' . esc_html__('Show this intro text in the author copy of the new listing e-mail.', 'metra-classifieds-plugin') . '</label>';
    }

    public static function field_new_listing_author_email_intro_text(): void {
        self::render_message_test_ui('new_listing_author_email');
        $default = MC_Messages::default_new_listing_author_email_intro_text();
        $stored = self::get_messages_option('new_listing_author_email_intro_text', '');
        $value = $stored !== '' ? $stored : $default;
        echo '<textarea name="mc_messages[new_listing_author_email_intro_text]" rows="7" class="large-text code" placeholder="' . esc_attr($default) . '">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Shown below the mail logo (if present) and above the existing author copy of the new listing e-mail. Plain text only. Use [bold]...[/bold] for bold text and [big]...[/big] for a large bold headline. Real blank lines are preserved. Leave the field empty to use the translated default text from the language files.', 'metra-classifieds-plugin') . '</p>';
    }

    /**
     * Design: Homepage layout selector (desktop).
     *
     * Values:
	     * - sidebar_2col: left categories sidebar + right listings in 2 columns
	     * - onecol: left categories sidebar + right listings in 1 column
     */
    public static function field_design_home_layout(): void {
        $opt = get_option('mc_design', []);
        if (!is_array($opt)) {
            $opt = [];
        }
        $val = isset($opt['home_layout']) && is_string($opt['home_layout']) ? sanitize_key($opt['home_layout']) : 'sidebar_2col';
        if (!in_array($val, ['sidebar_2col', 'onecol'], true)) {
            $val = 'sidebar_2col';
        }

        echo '<select name="mc_design[home_layout]">';
        echo '<option value="sidebar_2col"' . selected($val, 'sidebar_2col', false) . '>' . esc_html__('2 Columns - No right Sidebar', 'metra-classifieds-plugin') . '</option>';
        echo '<option value="onecol"' . selected($val, 'onecol', false) . '>' . esc_html__('One column (desktop)', 'metra-classifieds-plugin') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Desktop only. Mobile remains unchanged. The left categories sidebar stays active; this switch changes the listing columns on desktop.', 'metra-classifieds-plugin') . '</p>';

    }

    public static function field_design_primary(): void {
        $val = self::get_design_option('primary', '#1e73be');
        echo '<input type="color" name="mc_design[primary]" value="' . esc_attr($val) . '" />';
        echo '<p class="description">' . esc_html__('Used for buttons/accents (token: --mc-primary).', 'metra-classifieds-plugin') . '</p>';
    }

    // === MC: Listing title color callback v0.0710 START ===
    public static function field_design_listing_title_color(): void {
        $val = self::get_design_option('listing_title_color', '#1a1a1a');
        echo '<input type="color" name="mc_design[listing_title_color]" value="' . esc_attr($val) . '" />';
        echo '<p class="description">' . esc_html__('Farbe der Anzeigentitel in der Listen- und Einzelansicht (token: --mc-listing-title-color).', 'metra-classifieds-plugin') . '</p>';
    }
    // === MC: Listing title color callback v0.0710 END ===

    // === MC: Home cat parent color callback v0.0711 START ===
    public static function field_design_home_cat_parent_color(): void {
        $val = self::get_design_option('home_cat_parent_color', '#1e73be');
        echo '<input type="color" name="mc_design[home_cat_parent_color]" value="' . esc_attr($val) . '" />';
        echo '<p class="description">' . esc_html__('Farbe der Hauptkategorien in der linken Sidebar-Liste (token: --mc-home-cat-parent-color).', 'metra-classifieds-plugin') . '</p>';
    }
    // === MC: Home cat parent color callback v0.0711 END ===

    public static function field_design_text(): void {
        $val = self::get_design_option('text', '#1a1a1a');
        echo '<input type="color" name="mc_design[text]" value="' . esc_attr($val) . '" />';
        echo '<p class="description">' . esc_html__('Main text color (token: --mc-text).', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_font(): void {
        $val = self::get_design_option('font', 'inherit');
        echo '<input type="text" class="regular-text" name="mc_design[font]" value="' . esc_attr($val) . '" placeholder="inherit" />';
        echo '<p class="description">' . esc_html__('CSS font-family value (token: --mc-font). Example: Inter, system-ui, sans-serif', 'metra-classifieds-plugin') . '</p>';
    }

    /**
     * Listing text font (excerpt/description) selector.
     * Stores the selected font-family string in mc_design[listing_font].
     */
    public static function field_design_listing_font(): void {
        $val = self::get_design_option('listing_font', 'inherit');

        $choices = [
            'inherit'              => __('System default', 'metra-classifieds-plugin'),
            '"MC Roboto"'         => __('Roboto (built-in)', 'metra-classifieds-plugin'),
            '"MC Roboto Condensed"' => __('Roboto Condensed (built-in)', 'metra-classifieds-plugin'),
        ];

        echo '<select name="mc_design[listing_font]" style="min-width:240px;">';
        foreach ($choices as $k => $label) {
            echo '<option value="' . esc_attr($k) . '" ' . selected($val, $k, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Font used for listing description text (token: --mc-listing-font).', 'metra-classifieds-plugin') . '</p>';
    }

    /**
     * Listing title font selector.
     * Stores the selected font-family string in mc_design[listing_title_font].
     */
    public static function field_design_listing_title_font(): void {
        $val = self::get_design_option('listing_title_font', 'inherit');

        $choices = [
            'inherit'              => __('System default', 'metra-classifieds-plugin'),
            '"MC Roboto"'         => __('Roboto (built-in)', 'metra-classifieds-plugin'),
            '"MC Roboto Condensed"' => __('Roboto Condensed (built-in)', 'metra-classifieds-plugin'),
        ];

        echo '<select name="mc_design[listing_title_font]" style="min-width:240px;">';
        foreach ($choices as $k => $label) {
            echo '<option value="' . esc_attr($k) . '" ' . selected($val, $k, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Font used for listing titles (token: --mc-listing-title-font).', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_title_size(): void {
        $val = self::get_design_option('title_size', '16px');
        echo '<input type="text" class="small-text" name="mc_design[title_size]" value="' . esc_attr($val) . '" placeholder="16px" />';
        echo '<p class="description">' . esc_html__('Font size for listing titles (token: --mc-title-size). Example: 16px', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_excerpt_size(): void {
        $val = self::get_design_option('excerpt_size', '14px');
        echo '<input type="text" class="small-text" name="mc_design[excerpt_size]" value="' . esc_attr($val) . '" placeholder="14px" />';
        echo '<p class="description">' . esc_html__('Font size for listing description/excerpt (token: --mc-excerpt-size). Example: 14px', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_title_weight(): void {
        $raw = self::get_design_option('title_weight', '700');
        $val = ($raw === '700') ? 'bold' : 'normal';

        echo '<select name="mc_design[title_weight]">';
        echo '<option value="normal" ' . selected($val, 'normal', false) . '>' . esc_html__('Normal', 'metra-classifieds-plugin') . '</option>';
        echo '<option value="bold" ' . selected($val, 'bold', false) . '>' . esc_html__('Bold', 'metra-classifieds-plugin') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Font weight for listing titles (token: --mc-title-weight).', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_excerpt_weight(): void {
        $raw = self::get_design_option('excerpt_weight', '400');
        $val = ($raw === '700') ? 'bold' : 'normal';

        echo '<select name="mc_design[excerpt_weight]">';
        echo '<option value="normal" ' . selected($val, 'normal', false) . '>' . esc_html__('Normal', 'metra-classifieds-plugin') . '</option>';
        echo '<option value="bold" ' . selected($val, 'bold', false) . '>' . esc_html__('Bold', 'metra-classifieds-plugin') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Font weight for listing description/excerpt (token: --mc-excerpt-weight).', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_radius(): void {
        $val = self::get_design_option('radius', '10px');
        echo '<input type="text" name="mc_design[radius]" value="' . esc_attr($val) . '" style="width:90px;" placeholder="10px" />';
        echo '<p class="description">' . esc_html__('Corner radius for cards/buttons (token: --mc-radius). Format: Npx', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_border_width(): void {
        $val = self::get_design_option('border_width', '1px');
        echo '<input type="text" name="mc_design[border_width]" value="' . esc_attr($val) . '" style="width:90px;" placeholder="1px" />';
        echo '<p class="description">' . esc_html__('Border width for cards/inputs/buttons (token: --mc-border-width). Format: Npx', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_input_radius(): void {
        $fallback = self::get_design_option('radius', '10px');
        $val = self::get_design_option('input_radius', $fallback);
        echo '<input type="text" name="mc_design[input_radius]" value="' . esc_attr($val) . '" style="width:90px;" placeholder="8px" />';
        echo '<p class="description">' . esc_html__('Corner radius for input fields (token: --mc-input-radius). Format: Npx', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_button_radius(): void {
        $fallback = self::get_design_option('radius', '10px');
        $val = self::get_design_option('button_radius', $fallback);
        echo '<input type="text" name="mc_design[button_radius]" value="' . esc_attr($val) . '" style="width:90px;" placeholder="10px" />';
        echo '<p class="description">' . esc_html__('Corner radius for buttons (token: --mc-button-radius). Format: Npx', 'metra-classifieds-plugin') . '</p>';
    }

    public static function field_design_gap(): void {
        $val = self::get_design_option('gap', '12px');
        echo '<input type="text" name="mc_design[gap]" value="' . esc_attr($val) . '" style="width:90px;" placeholder="12px" />';
        echo '<p class="description">' . esc_html__('Grid spacing between cards (token: --mc-gap). Format: Npx', 'metra-classifieds-plugin') . '</p>';
    }

    public static function handle_settings_save(): void {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['post_type'], $_GET['page']) || (string) $_GET['post_type'] !== 'mc_listing' || (string) $_GET['page'] !== 'metra-classifieds-settings') {
            return;
        }

        if (!isset($_POST['mc_settings_save']) || (string) $_POST['mc_settings_save'] !== '1') {
            return;
        }

        $tab = isset($_POST['mc_settings_tab']) ? sanitize_key((string) $_POST['mc_settings_tab']) : 'basic';
        if (!in_array($tab, ['basic', 'design', 'report', 'email', 'messages', 'icons'], true)) {
            $tab = 'basic';
        }

        check_admin_referer('mc_settings_save_' . $tab, 'mc_settings_nonce');

        if ($tab === 'basic') {
            $submit_page_url = isset($_POST['mc_submit_page_url']) ? esc_url_raw((string) $_POST['mc_submit_page_url']) : '';
            update_option('mc_submit_page_url', $submit_page_url);

            $old_listing_slug = MC_CPT::get_listing_slug();
            $new_listing_slug = self::sanitize_listing_slug($_POST[MC_CPT::LISTING_SLUG_OPTION] ?? MC_CPT::LISTING_SLUG_DEFAULT);
            update_option(MC_CPT::LISTING_SLUG_OPTION, $new_listing_slug);
            if ($new_listing_slug !== $old_listing_slug) {
                MC_CPT::mark_rewrite_flush_required();
            }

            update_option('mc_categories_widget_show_count', self::sanitize_checkbox($_POST['mc_categories_widget_show_count'] ?? 0));
            update_option('mc_categories_widget_only_with_listings', self::sanitize_checkbox($_POST['mc_categories_widget_only_with_listings'] ?? 0));
            update_option('mc_categories_widget_root_term', self::sanitize_root_term($_POST['mc_categories_widget_root_term'] ?? 0));
            // === MC: Title max chars save v0.0706 START ===
            $mc_title_max_chars = isset($_POST['mc_title_max_chars']) ? (int) $_POST['mc_title_max_chars'] : 35;
            update_option('mc_title_max_chars', max(1, min(200, $mc_title_max_chars)));
            // === MC: Title max chars save v0.0706 END ===
            update_option('mc_excerpt_words', self::sanitize_int_range($_POST['mc_excerpt_words'] ?? 20));
            update_option('mc_thumb_w', self::sanitize_int_range($_POST['mc_thumb_w'] ?? 160));
            update_option('mc_thumb_h', self::sanitize_int_range($_POST['mc_thumb_h'] ?? 120));

            $moderation = isset($_POST['mc_moderation_mode']) ? sanitize_key((string) $_POST['mc_moderation_mode']) : 'immediate';
            update_option('mc_moderation_mode', in_array($moderation, ['immediate', 'admin'], true) ? $moderation : 'immediate');

            update_option('mc_max_images_per_listing', self::sanitize_int_range($_POST['mc_max_images_per_listing'] ?? 10, 1, 50));
        } elseif ($tab === 'design') {
            $design = isset($_POST['mc_design']) && is_array($_POST['mc_design']) ? (array) $_POST['mc_design'] : [];
            update_option('mc_design', self::sanitize_design($design));
            // === MC: Design tokens in metra-master.css schreiben v0.0714 START ===
            self::write_tokens_to_master_css();
            // === MC: Design tokens in metra-master.css schreiben v0.0714 END ===
        } elseif ($tab === 'report') {
            $reasons = $_POST['mc_report_reasons'] ?? '';
            update_option('mc_report_reasons', MC_Report::sanitize_reasons_text($reasons));
        } elseif ($tab === 'email') {
            $email = isset($_POST['mc_email']) && is_array($_POST['mc_email']) ? (array) $_POST['mc_email'] : [];
            update_option('mc_email', self::sanitize_email($email));
        } elseif ($tab === 'messages') {
            $messages = isset($_POST['mc_messages']) && is_array($_POST['mc_messages']) ? (array) $_POST['mc_messages'] : [];
            update_option('mc_messages', self::sanitize_messages($messages));
        } elseif ($tab === 'icons') {
            MC_Icon_Library::handle_settings_save();
        }

        $url = add_query_arg([
            'page' => 'metra-classifieds-settings',
            'tab' => $tab,
            'settings-updated' => '1',
        ], admin_url('edit.php?post_type=mc_listing'));

        wp_safe_redirect($url);
        exit;
    }

        // === Admin UI (Settings pages) =========================================
public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $tab = isset($_GET['tab']) && is_string($_GET['tab']) ? sanitize_key($_GET['tab']) : 'basic';
        if (!in_array($tab, ['basic', 'design', 'report', 'email', 'messages', 'icons', 'importexport', 'shortcodes'], true)) {
            $tab = 'basic';
        }

        // Export categories CSV
        if ($tab === 'importexport' && isset($_GET['mc_export_categories']) && $_GET['mc_export_categories'] === '1') {
            check_admin_referer('mc_export_categories');
            self::export_categories_csv();
            return;
        }

        if ($tab === 'design') {
            $page_slug = 'mc_settings_design';
            $settings_group = 'mc_settings_design';
        } elseif ($tab === 'email') {
            $page_slug = 'mc_settings_email';
            $settings_group = 'mc_settings_email';
        } elseif ($tab === 'messages') {
            $page_slug = 'mc_settings_messages';
            $settings_group = 'mc_settings_messages';
        } elseif ($tab === 'importexport') {
            $page_slug = 'mc_settings_importexport';
            $settings_group = 'mc_settings_importexport';
        } else {
            $page_slug = 'mc_settings_basic';
            $settings_group = 'mc_settings_basic';
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('METRA Classifieds', 'metra-classifieds-plugin') . '</h1>';
        echo "<p style=\"margin-top:4px;opacity:.7\">Version: " . esc_html(MC_VERSION) . "</p>";

        // Tabs
        // IMPORTANT: This settings screen is registered as a submenu under the CPT menu:
        // edit.php?post_type=mc_listing&page=metra-classifieds-settings
        // So we must keep the same base URL when switching tabs.
        $base_url  = admin_url('edit.php?post_type=mc_listing');
        $basic_url = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'basic'], $base_url));
        $des_url   = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'design'], $base_url));
        $report_url = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'report'], $base_url));
        $email_url = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'email'], $base_url));
        $messages_url = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'messages'], $base_url));
        $icons_url = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'icons'], $base_url));
        $ie_url    = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'importexport'], $base_url));
        $sc_url    = esc_url(add_query_arg(['page' => 'metra-classifieds-settings', 'tab' => 'shortcodes'], $base_url));
                        echo '<h2 class="nav-tab-wrapper" style="margin-top:12px;">';
        echo '<a class="nav-tab ' . (($tab === 'basic') ? 'nav-tab-active' : '') . '" href="' . $basic_url . '">' . esc_html__('Basic', 'metra-classifieds-plugin') . '</a>';
        echo '<a class="nav-tab ' . (($tab === 'design') ? 'nav-tab-active' : '') . '" href="' . $des_url . '">' . esc_html__('Design', 'metra-classifieds-plugin') . '</a>';
        echo '<a class="nav-tab ' . (($tab === 'report') ? 'nav-tab-active' : '') . '" href="' . $report_url . '">' . esc_html__('Report', 'metra-classifieds-plugin') . '</a>';
        echo '<a class="nav-tab ' . (($tab === 'email') ? 'nav-tab-active' : '') . '" href="' . $email_url . '">' . esc_html__('Email', 'metra-classifieds-plugin') . '</a>';
        echo '<a class="nav-tab ' . (($tab === 'messages') ? 'nav-tab-active' : '') . '" href="' . $messages_url . '">' . esc_html__('Message Texts', 'metra-classifieds-plugin') . '</a>';
        echo '<a class="nav-tab ' . (($tab === 'icons') ? 'nav-tab-active' : '') . '" href="' . $icons_url . '">' . esc_html__('Icons', 'metra-classifieds-plugin') . '</a>';
        echo '<a class="nav-tab ' . (($tab === 'importexport') ? 'nav-tab-active' : '') . '" href="' . $ie_url . '">' . esc_html__('Import/Export', 'metra-classifieds-plugin') . '</a>';
        echo '<a class="nav-tab ' . (($tab === 'shortcodes') ? 'nav-tab-active' : '') . '" href="' . $sc_url . '">' . esc_html__('Shortcodes', 'metra-classifieds-plugin') . '</a>';
        echo '</h2>';
        if ($tab === 'icons') {
            if (isset($_GET['settings-updated']) && (string) $_GET['settings-updated'] === '1') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'metra-classifieds-plugin') . '</p></div>';
            }
            MC_Icon_Library::render_settings_page();
            echo '</div>';
            return;
        }
        if (isset($_GET['settings-updated']) && (string) $_GET['settings-updated'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'metra-classifieds-plugin') . '</p></div>';
        }
        if ($tab === 'design' && isset($_GET['mc_icon_defaults_reset']) && (string) $_GET['mc_icon_defaults_reset'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Global icon defaults were reset.', 'metra-classifieds-plugin') . '</p></div>';
        }
        if ($tab === 'design' && isset($_GET['mc_icon_overrides_reset']) && (string) $_GET['mc_icon_overrides_reset'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('All category icon overrides were reset.', 'metra-classifieds-plugin') . '</p></div>';
        }
        if ($tab === 'email' && isset($_GET['mc_mail_test']) && is_string($_GET['mc_mail_test'])) {
            $mail_test = sanitize_key((string) $_GET['mc_mail_test']);
            if ($mail_test === 'sent') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('METRA test e-mail sent successfully.', 'metra-classifieds-plugin') . '</p></div>';
            } elseif ($mail_test === 'failed') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('METRA test e-mail could not be sent. Please check your SMTP plugin / mail log.', 'metra-classifieds-plugin') . '</p></div>';
            } elseif ($mail_test === 'invalid') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('No valid test recipient e-mail is configured in the Mail tab.', 'metra-classifieds-plugin') . '</p></div>';
            }
        }
        if ($tab === 'email' && isset($_GET['mc_report_mail_test']) && is_string($_GET['mc_report_mail_test'])) {
            $report_test = sanitize_key((string) $_GET['mc_report_mail_test']);
            if ($report_test === 'sent') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Report test e-mail sent successfully.', 'metra-classifieds-plugin') . '</p></div>';
            } elseif ($report_test === 'failed') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Report test e-mail could not be sent. Please check your SMTP plugin / mail log.', 'metra-classifieds-plugin') . '</p></div>';
            } elseif ($report_test === 'invalid') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('No valid report recipient e-mail is configured.', 'metra-classifieds-plugin') . '</p></div>';
            }
        }
        if ($tab === 'messages' && isset($_GET['mc_message_test']) && is_string($_GET['mc_message_test'])) {
            $message_test = sanitize_key((string) $_GET['mc_message_test']);
            if ($message_test === 'sent') {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Test mail sent successfully to the WordPress admin e-mail address.', 'metra-classifieds-plugin') . '</p></div>';
            } elseif ($message_test === 'failed') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Test mail could not be sent. Please check your SMTP plugin / mail log.', 'metra-classifieds-plugin') . '</p></div>';
            } elseif ($message_test === 'invalid') {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('No valid WordPress admin e-mail address is configured for the test mail.', 'metra-classifieds-plugin') . '</p></div>';
            }
        }

if ($tab === 'importexport') {
            self::render_importexport();
        } elseif ($tab === 'shortcodes') {
            self::render_shortcodes_tab();
        } else {
            if ($tab === 'design') {
                $settings_group = 'mc_settings_design';
                $page_slug = 'mc_settings_design';
            } elseif ($tab === 'email') {
                $settings_group = 'mc_settings_email';
                $page_slug = 'mc_settings_email';
            } elseif ($tab === 'messages') {
                $settings_group = 'mc_settings_messages';
                $page_slug = 'mc_settings_messages';
            } elseif ($tab === 'report') {
                $settings_group = 'mc_settings_report';
                $page_slug = 'mc_settings_report';
            } else {
                $settings_group = 'mc_settings_basic';
                $page_slug = 'mc_settings_basic';
            }
            echo '<form method="post" action="' . esc_url(add_query_arg(['tab' => $tab], self::get_settings_base_url())) . '">';
            wp_nonce_field('mc_settings_save_' . $tab, 'mc_settings_nonce');
            echo '<input type="hidden" name="mc_settings_save" value="1" />';
            echo '<input type="hidden" name="mc_settings_tab" value="' . esc_attr($tab) . '" />';
            do_settings_sections($page_slug);
            submit_button();
            echo '</form>';

            if ($tab === 'email') {
                echo '<hr />';
                echo '<h2>' . esc_html__('Mail test', 'metra-classifieds-plugin') . '</h2>';
                echo '<p>' . esc_html__('Use this button to send a real METRA test mail through WordPress and your SMTP plugin. The result should also appear in your SMTP or mail logging plugin.', 'metra-classifieds-plugin') . '</p>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                wp_nonce_field('mc_send_test_mail');
                echo '<input type="hidden" name="action" value="mc_send_test_mail" />';
                submit_button(__('Send METRA test mail', 'metra-classifieds-plugin'), 'secondary', 'submit', false);
                echo '</form>';

                echo '<p style="margin-top:18px;">' . esc_html__('Send a test e-mail to the report recipient configured above.', 'metra-classifieds-plugin') . '</p>';
                echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                wp_nonce_field('mc_send_report_test_mail');
                echo '<input type="hidden" name="action" value="mc_send_report_test_mail" />';
                submit_button(__('Send report test mail', 'metra-classifieds-plugin'), 'secondary', 'submit', false);
                echo '</form>';
            }
        }

        echo '</div>';
    }


    private static function get_settings_base_url(): string {
        return admin_url('edit.php?post_type=mc_listing&page=metra-classifieds-settings');
    }

    public static function handle_send_message_test_mail(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'metra-classifieds-plugin'));
        }

        $message_key = isset($_REQUEST['message_key']) ? sanitize_key((string) $_REQUEST['message_key']) : '';
        check_admin_referer('mc_send_message_test_mail_' . $message_key);

        $redirect = add_query_arg(['tab' => 'messages'], self::get_settings_base_url());
        $admin_email = sanitize_email((string) get_option('admin_email'));
        if ($admin_email === '' || !is_email($admin_email)) {
            wp_safe_redirect(add_query_arg('mc_message_test', 'invalid', $redirect));
            exit;
        }

        $sent = MC_Messages::send_test_mail_for_message_type($message_key);
        wp_safe_redirect(add_query_arg('mc_message_test', $sent ? 'sent' : 'failed', $redirect));
        exit;
    }

    public static function handle_send_test_mail(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'metra-classifieds-plugin'));
        }

        check_admin_referer('mc_send_test_mail');

        $opt = get_option('mc_email', []);
        $to = '';
        if (is_array($opt) && isset($opt['test_to']) && is_string($opt['test_to'])) {
            $to = sanitize_email($opt['test_to']);
        }
        if ($to === '') {
            $to = sanitize_email((string) get_option('admin_email', ''));
        }

        $status = 'invalid';
        if (is_email($to)) {
            $status = MC_Emails::send_test_mail($to) ? 'sent' : 'failed';
        }

        $url = add_query_arg([
            'tab' => 'email',
            'mc_mail_test' => $status,
        ], self::get_settings_base_url());

        wp_safe_redirect($url);
        exit;
    }



    public static function handle_send_report_test_mail(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'metra-classifieds-plugin'));
        }

        check_admin_referer('mc_send_report_test_mail');

        $opt = get_option('mc_email', []);
        $to = '';
        if (is_array($opt) && isset($opt['report_to']) && is_string($opt['report_to'])) {
            $to = sanitize_email($opt['report_to']);
        }
        if ($to === '') {
            $to = sanitize_email((string) get_option('admin_email', ''));
        }

        $status = 'invalid';
        if (is_email($to)) {
            $status = MC_Emails::send_test_mail($to) ? 'sent' : 'failed';
        }

        $url = add_query_arg([
            'tab' => 'email',
            'mc_report_mail_test' => $status,
        ], self::get_settings_base_url());

        wp_safe_redirect($url);
        exit;
    }

    public static function handle_delete_unconfirmed_users(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'metra-classifieds-plugin'));
        }

        check_admin_referer('mc_delete_unconfirmed_users');

        $deleted = MC_User_Activation::delete_all_pending_users();

        $url = add_query_arg([
            'tab' => 'basic',
            'mc_unconfirmed_deleted' => (string) $deleted,
        ], self::get_settings_base_url());

        wp_safe_redirect($url);
        exit;
    }

    private static function render_info_blocks(): void {
        echo '<hr />';
        echo '<h2>' . esc_html__('Auto-created pages', 'metra-classifieds-plugin') . '</h2>';
        echo '<p>' . esc_html__('These pages are created automatically on plugin activation (and re-created if deleted).', 'metra-classifieds-plugin') . '</p>';
        echo '<ul style="list-style:disc;margin-left:20px;">';
        $map = [
            'submit'      => __('Post a listing', 'metra-classifieds-plugin'),
            'listings'    => __('Listings', 'metra-classifieds-plugin'),
            'categories'  => __('Categories', 'metra-classifieds-plugin'),
            'my_listings' => __('My listings', 'metra-classifieds-plugin'),
        ];
        foreach ($map as $key => $label) {
            $url = MC_Pages::get_page_url($key);
            if ($url === '') {
                echo '<li>' . esc_html($label) . ': <em>' . esc_html__('not found (will be created automatically)', 'metra-classifieds-plugin') . '</em></li>';
                continue;
            }
            echo '<li>' . esc_html($label) . ': <a href="' . esc_url($url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($url) . '</a></li>';
        }
        echo '</ul>';

        echo '<h2>' . esc_html__('Shortcodes', 'metra-classifieds-plugin') . '</h2>';
        echo '<ul style="list-style:disc;margin-left:20px;">';
        echo '<li><span class="mc-admin-shortcode">[mc_submit_link]</span> – ' . esc_html__('Renders a link to your submit page.', 'metra-classifieds-plugin') . '</li>';
        echo '<li><span class="mc-admin-shortcode">[mc_submit_form]</span> – ' . esc_html__('Renders a minimal submit form for logged-in users.', 'metra-classifieds-plugin') . '</li>';
        echo '<li><span class="mc-admin-shortcode">[mc_listings]</span> – ' . esc_html__('Shows the latest listings.', 'metra-classifieds-plugin') . '</li>';
        echo '<li><span class="mc-admin-shortcode">[mc_my_listings]</span> – ' . esc_html__('Shows the current user’s listings.', 'metra-classifieds-plugin') . '</li>';
        echo '<li><span class="mc-admin-shortcode">[mc_categories_grid]</span> – ' . esc_html__('Renders a minimal category grid.', 'metra-classifieds-plugin') . '</li>';
        echo '</ul>';
    }

    /**
     * Import/Export tab renderer.
     * - Imports categories hierarchy from CSV (name,slug,parent_name,parent_slug,level)
     * - Exports current categories as CSV in the same format
     */
    private static function render_importexport(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $messages = [];

        // Handle demo/dummy import (admin-only)
        if (isset($_POST['mc_demo_import_submit'])) {
            check_admin_referer('mc_demo_import', 'mc_demo_import_nonce');

            $package = isset($_POST['mc_demo_package']) ? absint($_POST['mc_demo_package']) : 0;
            $count   = isset($_POST['mc_demo_count']) ? absint($_POST['mc_demo_count']) : 75;
            $users   = isset($_POST['mc_demo_users']) ? absint($_POST['mc_demo_users']) : 5;

            if ($package <= 0 || $package > 50) {
                $messages[] = ['type' => 'error', 'text' => __('Invalid package number.', 'metra-classifieds-plugin')];
            } else {
                $report = self::run_demo_import($package, $count, $users);
                foreach ($report as $row) {
                    $messages[] = $row;
                }
            }
        }

        // Handle delete demo/dummy users submit
        if (isset($_POST['mc_demo_delete_submit'])) {
            check_admin_referer('mc_demo_delete', 'mc_demo_delete_nonce');
            $deleted = self::delete_demo_dummy_users();
            $messages[] = [
                'type' => 'updated',
                'text' => sprintf(
                    /* translators: %d: number of deleted users */
                    __('%d demo users deleted.', 'metra-classifieds-plugin'),
                    (int) $deleted
                ),
            ];
        }

        // Handle import submit
        if (isset($_POST['mc_import_categories_submit'])) {
            check_admin_referer('mc_import_categories', 'mc_import_nonce');

            $overwrite = isset($_POST['mc_import_overwrite']) && $_POST['mc_import_overwrite'] === '1';

            if (!isset($_FILES['mc_categories_csv']) || !is_array($_FILES['mc_categories_csv'])) {
                $messages[] = ['type' => 'error', 'text' => __('No CSV file uploaded.', 'metra-classifieds-plugin')];
            } else {
                $file = $_FILES['mc_categories_csv'];
                if (!empty($file['error'])) {
                    $messages[] = ['type' => 'error', 'text' => __('Upload error. Please try again.', 'metra-classifieds-plugin')];
                } else {
                    $report = self::import_categories_from_csv((string) $file['tmp_name'], $overwrite);
                    foreach ($report as $row) {
                        $messages[] = $row;
                    }
                }
            }
        }

        // Notices
        foreach ($messages as $m) {
            $class = ($m['type'] === 'success') ? 'notice notice-success' : (($m['type'] === 'warning') ? 'notice notice-warning' : 'notice notice-error');
            echo '<div class="' . esc_attr($class) . '"><p>' . esc_html($m['text']) . '</p></div>';
        }

        echo '<h2 style="margin-top:18px;">' . esc_html__('Import categories (CSV)', 'metra-classifieds-plugin') . '</h2>';
        echo '<p style="max-width:860px;opacity:.9;">' . esc_html__('Upload a CSV file with columns: name, slug, parent_name, parent_slug, level. Existing terms are detected by slug + parent.', 'metra-classifieds-plugin') . '</p>';

        echo '<form method="post" enctype="multipart/form-data" style="margin-top:12px;">';
        wp_nonce_field('mc_import_categories', 'mc_import_nonce');

        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr>';
        echo '<th scope="row"><label for="mc_categories_csv">' . esc_html__('CSV file', 'metra-classifieds-plugin') . '</label></th>';
        echo '<td><input type="file" id="mc_categories_csv" name="mc_categories_csv" accept=".csv,text/csv" required /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="mc_demo_images_mode">' . esc_html__('Images mode', 'metra-classifieds-plugin') . '</label></th>';
        echo '<td>';
        echo '<select id="mc_demo_images_mode" name="mc_demo_images_mode">';
        echo '<option value="placeholder">' . esc_html__('1) Placeholder image (same for all)', 'metra-classifieds-plugin') . '</option>';
        echo '<option value="local_random" selected>' . esc_html__('2) Locally generated 1–2 images per listing', 'metra-classifieds-plugin') . '</option>';
        echo '</select>';
        echo '<p class="description">' . esc_html__('Option 1 uses one shared image. Option 2 generates unique images locally (no external URLs).', 'metra-classifieds-plugin') . '</p>';
        echo '</td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('If category exists', 'metra-classifieds-plugin') . '</th>';
        echo '<td>';
        echo '<label><input type="radio" name="mc_import_overwrite" value="0" checked /> ' . esc_html__('Skip existing', 'metra-classifieds-plugin') . '</label><br>';
        echo '<label><input type="radio" name="mc_import_overwrite" value="1" /> ' . esc_html__('Overwrite existing', 'metra-classifieds-plugin') . '</label>';
        echo '<p class="description">' . esc_html__('Overwrite updates the name/slug for the term at the same hierarchy position.', 'metra-classifieds-plugin') . '</p>';
        echo '</td>';
        echo '</tr>';

        echo '</tbody></table>';

        echo '<p>';
        echo '<input type="submit" class="button button-primary" name="mc_import_categories_submit" value="' . esc_attr__('Import categories', 'metra-classifieds-plugin') . '">';
        echo '</p>';
        echo '</form>';

        echo '<hr style="margin:22px 0;">';

        echo '<h2>' . esc_html__('Export categories (CSV)', 'metra-classifieds-plugin') . '</h2>';
        echo '<p style="max-width:860px;opacity:.9;">' . esc_html__('Download the current category tree as CSV (same format as import).', 'metra-classifieds-plugin') . '</p>';

        $base_url = admin_url('edit.php?post_type=mc_listing');
        $export_url = add_query_arg([
            'page' => 'metra-classifieds-settings',
            'tab'  => 'importexport',
            'mc_export_categories' => '1',
        ], $base_url);
        $export_url = wp_nonce_url($export_url, 'mc_export_categories');

        echo '<p><a class="button" href="' . esc_url($export_url) . '">' . esc_html__('Download CSV', 'metra-classifieds-plugin') . '</a></p>';

        echo '<hr style="margin:22px 0;">';

        // Demo/Dummy Import (no SQL, no WXR)
        echo '<h2>' . esc_html__('Demo/Dummy import (creates users + listings)', 'metra-classifieds-plugin') . '</h2>';
        echo '<p style="max-width:860px;opacity:.9;">' . esc_html__('Creates demo users and published demo listings directly in WordPress. Users are created as active (not pending) with a fixed password. Existing users/listings are not modified.', 'metra-classifieds-plugin') . '</p>';

        echo '<form method="post" style="margin-top:12px;">';
        wp_nonce_field('mc_demo_import', 'mc_demo_import_nonce');

        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr>';
        echo '<th scope="row"><label for="mc_demo_package">' . esc_html__('Package number', 'metra-classifieds-plugin') . '</label></th>';
        echo '<td><input type="number" min="1" max="50" step="1" id="mc_demo_package" name="mc_demo_package" value="1" required />'
            . '<p class="description">' . esc_html__('Usernames will be: User No 0001 -Dummy …', 'metra-classifieds-plugin') . '</p></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="mc_demo_count">' . esc_html__('Listings to create', 'metra-classifieds-plugin') . '</label></th>';
        echo '<td><input type="number" min="1" max="500" step="1" id="mc_demo_count" name="mc_demo_count" value="75" required /></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><label for="mc_demo_users">' . esc_html__('Users to create', 'metra-classifieds-plugin') . '</label></th>';
        echo '<td><input type="number" min="1" max="50" step="1" id="mc_demo_users" name="mc_demo_users" value="5" required />'
            . '<p class="description">' . esc_html__('All demo users use the same password: Secret', 'metra-classifieds-plugin') . '</p></td>';
        echo '</tr>';

        echo '</tbody></table>';

        echo '<p>';
        echo '<input type="submit" class="button button-primary" name="mc_demo_import_submit" value="' . esc_attr__('Create demo data now', 'metra-classifieds-plugin') . '">';
        echo '</p>';
        echo '</form>';

        echo '<form method="post" style="margin-top:12px;">';
        wp_nonce_field('mc_demo_delete', 'mc_demo_delete_nonce');
        echo '<p class="description">' . esc_html__('Deletes all demo users whose username ends with "-dummy" (and deletes their listings).', 'metra-classifieds-plugin') . '</p>';
        echo '<p><input type="submit" class="button button-secondary" name="mc_demo_delete_submit" value="' . esc_attr__('Delete demo users now', 'metra-classifieds-plugin') . '"></p>';
        echo '</form>';

        // Info blocks (moved here from all tabs)
        self::render_info_blocks();
    }

    /**
     * Create demo users + published demo listings.
     * Returns notice rows: ['type' => success|warning|error, 'text' => string]
     */

/* ============================================================
 * Admin Settings Tab: Shortcodes
 *
 * Purpose:
 * - Display a live, always-up-to-date overview of all METRA shortcodes.
 * - Read-only (no settings saved).
 * - Designed to be removable as a single self-contained block.
 *
 * Removal:
 * - Remove:
 *   1) the 'shortcodes' tab wiring in render_page() (allowed tabs + nav link + switch)
 *   2) this method render_shortcodes_tab()
 * ============================================================ */
private static function render_shortcodes_tab(): void {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Get all registered shortcodes and keep only METRA ones (prefix mc_).
    global $shortcode_tags;
    $all = is_array($shortcode_tags) ? array_keys($shortcode_tags) : [];
    $metra = array_values(array_filter($all, static function ($tag) {
        return is_string($tag) && substr($tag, 0, 3) === 'mc_';
    }));
    sort($metra, SORT_NATURAL | SORT_FLAG_CASE);

    // Descriptions (kept in one place; unknown shortcodes still show up automatically).
    // NOTE: These strings are translatable.
    $desc = [
        'mc_login'            => __('Login form', 'metra-classifieds-plugin'),
        'mc_register'         => __('Registration form', 'metra-classifieds-plugin'),
        'mc_reset_password'   => __('Reset password (request link)', 'metra-classifieds-plugin'),
        'mc_set_password'     => __('Set password after activation', 'metra-classifieds-plugin'),
        'mc_dashboard'        => __('User dashboard', 'metra-classifieds-plugin'),
        'mc_my_profile'       => __('User profile (read-only)', 'metra-classifieds-plugin'),
        'mc_my_listings'      => __('Listings of the logged-in user', 'metra-classifieds-plugin'),
        'mc_listings'         => __('Public listings index (search + pagination)', 'metra-classifieds-plugin'),
        'mc_listing'          => __('Single listing view (fallback page)', 'metra-classifieds-plugin'),
        'mc_edit_listing'     => __('Edit listing (owner/admin)', 'metra-classifieds-plugin'),
        'mc_submit_form'      => __('Post a listing form (after category selection)', 'metra-classifieds-plugin'),
        'mc_submit_category'  => __('Category drilldown wizard (submit flow)', 'metra-classifieds-plugin'),
        'mc_categories_grid'  => __('Categories grid (tile view)', 'metra-classifieds-plugin'),
        'mc_category_listings'=> __('Browse category listings (page-based fallback)', 'metra-classifieds-plugin'),
        'mc_view'             => __('Legacy view endpoint (redirects to canonical listing)', 'metra-classifieds-plugin'),
    ];

    // Grouping rules (for structured display).
    $groups = [
        'auth' => [
            'label' => __('Authentication & Users', 'metra-classifieds-plugin'),
            'items' => [],
        ],
        'dashboard' => [
            'label' => __('Dashboard', 'metra-classifieds-plugin'),
            'items' => [],
        ],
        'listings' => [
            'label' => __('Listings', 'metra-classifieds-plugin'),
            'items' => [],
        ],
        'submit' => [
            'label' => __('Submit flow', 'metra-classifieds-plugin'),
            'items' => [],
        ],
        'categories' => [
            'label' => __('Categories', 'metra-classifieds-plugin'),
            'items' => [],
        ],
        'infrastructure' => [
            'label' => __('Infrastructure', 'metra-classifieds-plugin'),
            'items' => [],
        ],
        'other' => [
            'label' => __('Other', 'metra-classifieds-plugin'),
            'items' => [],
        ],
    ];

    foreach ($metra as $tag) {
        $g = 'other';
        if (in_array($tag, ['mc_login','mc_register','mc_reset_password','mc_set_password'], true)) {
            $g = 'auth';
        } elseif (in_array($tag, ['mc_dashboard','mc_my_profile','mc_my_listings'], true)) {
            $g = 'dashboard';
        } elseif (in_array($tag, ['mc_listings','mc_listing','mc_edit_listing','mc_view'], true)) {
            $g = 'listings';
        } elseif (in_array($tag, ['mc_submit_form','mc_submit_category'], true)) {
            $g = 'submit';
        } elseif (in_array($tag, ['mc_categories_grid','mc_category_listings'], true)) {
            $g = 'categories';
        }
        // Anything that exists but has no explicit description is still listed.
        if (!isset($desc[$tag]) && substr($tag, 0, 3) === 'mc_') {
            // If it's METRA but unknown, keep it visible for documentation.
            // Mark as TBD but do NOT hide.
            $desc[$tag] = __('(No description yet)', 'metra-classifieds-plugin');
        }
        $groups[$g]['items'][] = $tag;
    }

    echo '<div style="margin-top:14px;">';
    echo '<p style="margin:0 0 10px 0;opacity:.85;">' . esc_html__('This tab lists all currently registered METRA shortcodes (prefix mc_). The list updates automatically from the runtime registry.', 'metra-classifieds-plugin') . '</p>';

    // Render grouped tables.
    foreach ($groups as $group) {
        if (empty($group['items'])) {
            continue;
        }

        echo '<h2 style="margin-top:18px;">' . esc_html($group['label']) . '</h2>';
        echo '<table class="widefat striped" style="max-width: 980px;">';
        echo '<thead><tr>';
        echo '<th style="width:220px;">' . esc_html__('Shortcode', 'metra-classifieds-plugin') . '</th>';
        echo '<th>' . esc_html__('Description', 'metra-classifieds-plugin') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($group['items'] as $tag) {
            $d = $desc[$tag] ?? __('(No description yet)', 'metra-classifieds-plugin');
            echo '<tr>';
            echo '<td><code>[' . esc_html($tag) . ']</code></td>';
            echo '<td>' . esc_html($d) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    echo '</div>';
}

    private static function run_demo_import(int $package, int $count, int $users): array {
        $out = [];

        if ($count <= 0) {
            return [['type' => 'error', 'text' => __('Listings count must be greater than 0.', 'metra-classifieds-plugin')]];
        }

        if ($users <= 0) {
            return [['type' => 'error', 'text' => __('Users count must be greater than 0.', 'metra-classifieds-plugin')]];
        }

        // Pick from existing categories only.
        $terms = get_terms([
            'taxonomy'   => 'mc_category',
            'hide_empty' => false,
            'number'     => 2000,
        ]);

        $term_ids = [];
        if (is_array($terms)) {
            foreach ($terms as $t) {
                if ($t instanceof \WP_Term) {
                    $term_ids[] = (int) $t->term_id;
                }
            }
        }

        if (empty($term_ids)) {
            $out[] = ['type' => 'warning', 'text' => __('No mc_category terms found. Listings will be created without categories.', 'metra-classifieds-plugin')];
        }

        // Ensure we can create users without triggering activation/pending logic.
        // We temporarily detach the activation module from user_register.
        remove_action('user_register', [MC_User_Activation::class, 'on_user_register'], 10);

        $created_user_ids = [];
        $password = 'Secret';

        for ($i = 1; $i <= $users; $i++) {
            // Dummy users: "User No 0001 -Dummy" (stable + easy to bulk-delete).
            $num = (int) (($package * 1000) + $i);
            $num4 = str_pad((string) $num, 4, '0', STR_PAD_LEFT);

            $login   = 'user-no-' . $num4 . '-dummy';
            $email   = 'user' . $num4 . '@example.local';
            $display = 'User No ' . $num4 . ' -Dummy';

            $existing = get_user_by('login', $login);
            if ($existing instanceof \WP_User) {
                $created_user_ids[] = (int) $existing->ID;
                // Make sure it's not pending.
                self::mark_user_confirmed((int) $existing->ID);
                $out[] = ['type' => 'warning', 'text' => sprintf(__('User exists: %s (re-used)', 'metra-classifieds-plugin'), $login)];
                continue;
            }

            $uid = wp_insert_user([
                'user_login'   => $login,
                'user_pass'    => $password,
                'user_email'   => $email,
                'display_name' => $display,
                'role'         => 'subscriber',
            ]);

            if (is_wp_error($uid)) {
                $out[] = ['type' => 'error', 'text' => sprintf(__('Could not create user %s: %s', 'metra-classifieds-plugin'), $login, $uid->get_error_message())];
                continue;
            }

            $created_user_ids[] = (int) $uid;
            self::mark_user_confirmed((int) $uid);
        }

        // Restore activation hook.
        add_action('user_register', [MC_User_Activation::class, 'on_user_register'], 10, 1);

        if (empty($created_user_ids)) {
            $out[] = ['type' => 'error', 'text' => __('No users available to assign as authors.', 'metra-classifieds-plugin')];
            return $out;
        }

        $sent_pool = [
            __('This is a realistic dummy listing for testing search, filtering and layout.', 'metra-classifieds-plugin'),
            __('The content is fictional and intended for testing purposes only.', 'metra-classifieds-plugin'),
            __('Price, condition and category are set as examples to help test list views.', 'metra-classifieds-plugin'),
            __('Pickup or shipping is possible. Please ask for details via the contact form.', 'metra-classifieds-plugin'),
            __('The attached images are placeholders generated locally for testing.', 'metra-classifieds-plugin'),
        ];

        $cities = ['Berlin', 'Hamburg', 'Munich', 'Cologne', 'Frankfurt'];

        $created_listings = 0;
        $created_images = 0;
        $image_failures = 0;

        for ($n = 1; $n <= $count; $n++) {
            $author_id = (int) $created_user_ids[array_rand($created_user_ids)];

            $title = 'Listing Demo-Import-Dummies-' . $package . ' Listing ID ' . str_pad((string) $n, 3, '0', STR_PAD_LEFT);

            // 25–50 words
            $target = rand(25, 50);
            $words = [];
            while (count($words) < $target) {
                $words = array_merge($words, preg_split('/\s+/', (string) $sent_pool[array_rand($sent_pool)]) ?: []);
            }
            $desc = implode(' ', array_slice($words, 0, $target));

            $post_id = wp_insert_post([
                'post_type'    => 'mc_listing',
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_content' => '<p>' . esc_html($desc) . '</p>',
                'post_author'  => $author_id,
            ], true);

            if (is_wp_error($post_id)) {
                $out[] = ['type' => 'error', 'text' => sprintf(__('Could not create listing %s: %s', 'metra-classifieds-plugin'), $title, $post_id->get_error_message())];
                continue;
            }

            $created_listings++;

            // Category (existing only)
            if (!empty($term_ids)) {
                $tid = (int) $term_ids[array_rand($term_ids)];
                wp_set_object_terms((int) $post_id, [$tid], 'mc_category', false);
            }

            // Basic listing meta (compatible with existing UI)
            update_post_meta((int) $post_id, 'mc_price', (string) rand(10, 2500));
            wp_set_object_terms((int) $post_id, array('new'), 'mc_condition', false);
            // Images
            // We use locally generated dummy graphics only (no external sources).
            $images_mode = 'local_random';

            $gallery = [];

            if ($images_mode === 'placeholder') {
                // One shared placeholder image for all listings
                $att1 = self::get_or_create_demo_placeholder_attachment((int) $post_id);
                if ($att1 > 0) {
                    set_post_thumbnail((int) $post_id, $att1);
                    $created_images++;
                } else {
                    $image_failures++;
                }
            } else {
                // Locally generated images (1–2 per listing)
                $att1 = self::generate_local_demo_image((int) $post_id, $title . ' A', 'P' . $package . '-' . $n . '-A');
                if ($att1 > 0) {
                    set_post_thumbnail((int) $post_id, $att1);
                    $created_images++;
                } else {
                    $image_failures++;
                }

                if (rand(0, 1) === 1) {
                    $att2 = self::generate_local_demo_image((int) $post_id, $title . ' B', 'P' . $package . '-' . $n . '-B');
                    if ($att2 > 0) {
                        $gallery[] = $att2;
                        $created_images++;
                    } else {
                        $image_failures++;
                    }
                }
            }

            if (!empty($gallery)) {
                update_post_meta((int) $post_id, 'mc_gallery_ids', array_values(array_filter(array_map('intval', $gallery))));
            }

        }

        $out[] = ['type' => 'success', 'text' => sprintf(__('Demo import completed: %d listings created (package %d).', 'metra-classifieds-plugin'), $created_listings, $package)];
        $out[] = ['type' => 'success', 'text' => sprintf(__('Demo users: %d (password: Secret).', 'metra-classifieds-plugin'), count($created_user_ids))];
        $out[] = ['type' => 'success', 'text' => sprintf(__('Images created: %d', 'metra-classifieds-plugin'), $created_images)];

        return $out;
    }

    /**
     * Delete all demo/dummy users created by the importer.
     * Matching rule: user_login ends with "-dummy" (case-insensitive) OR display_name ends with " -Dummy".
     */
    private static function delete_demo_dummy_users(): int {
        if (!current_user_can('manage_options')) {
            return 0;
        }

        if (!function_exists('wp_delete_user')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $users = get_users([
            'fields'  => ['ID', 'user_login', 'display_name'],
            'number'  => 0,
            'orderby' => 'ID',
            'order'   => 'ASC',
        ]);

        $deleted = 0;
        foreach ($users as $u) {
        $is_dummy_meta = get_user_meta((int)$u->ID, 'mc_is_dummy', true) === '1';
            $login = strtolower((string) $u->user_login);
            $name  = (string) $u->display_name;

            if (!(str_ends_with($login, '_dummy') || str_ends_with($name, ' -Dummy'))) {
                continue;
            }

            // Delete user and their posts (default behavior deletes posts).
            if (wp_delete_user((int) $u->ID)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /** Remove METRA activation/pending flags for a user. */
    private static function mark_user_confirmed(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }
        delete_user_meta($user_id, '_mc_activation_pending');
        delete_user_meta($user_id, '_mc_activation_key');
        delete_user_meta($user_id, '_mc_activation_created');
        delete_user_meta($user_id, '_mc_activation_pw_sent');
        delete_user_meta($user_id, '_mc_pw_required');
        delete_user_meta($user_id, '_mc_pw_key');
        delete_user_meta($user_id, '_mc_pw_created');
        // In case something set it, force to 0.
        update_user_meta($user_id, '_mc_activation_pending', '0');
    }

    /**
     * Download an image and attach it to a post. Returns attachment ID or 0.
     */
    private static function sideload_image_to_post(string $url, int $post_id, string $desc): int {
        if ($url === '' || $post_id <= 0) {
            return 0;
        }

        if (!function_exists('media_handle_sideload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $tmp = download_url($url, 15);
        if (is_wp_error($tmp)) {
            return 0;
        }

        $file = [
            'name'     => sanitize_file_name(wp_basename(parse_url($url, PHP_URL_PATH) ?: 'demo.jpg')),
            'tmp_name' => $tmp,
        ];

        $att_id = media_handle_sideload($file, $post_id, $desc);
        if (is_wp_error($att_id)) {
            @unlink($tmp);
            return 0;
        }

        return (int) $att_id;
    }

    /**
     * Imports categories from a CSV file on disk.
     * Returns an array of notice rows: ['type' => success|warning|error, 'text' => string]
     */
    private static function import_categories_from_csv(string $csv_path, bool $overwrite): array {
        $out = [];

        if (!file_exists($csv_path) || !is_readable($csv_path)) {
            return [['type' => 'error', 'text' => __('CSV file is not readable.', 'metra-classifieds-plugin')]];
        }

        $handle = fopen($csv_path, 'r');
        if (!$handle) {
            return [['type' => 'error', 'text' => __('Could not open CSV file.', 'metra-classifieds-plugin')]];
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return [['type' => 'error', 'text' => __('CSV header is missing.', 'metra-classifieds-plugin')]];
        }

        $header = array_map('strtolower', array_map('trim', $header));
        $required = ['name', 'slug', 'parent_name', 'parent_slug', 'level'];
        foreach ($required as $col) {
            if (!in_array($col, $header, true)) {
                fclose($handle);
                return [['type' => 'error', 'text' => sprintf(__('CSV missing required column: %s', 'metra-classifieds-plugin'), $col)]];
            }
        }

        $idx = array_flip($header);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row) || count($row) < 2) {
                continue;
            }
            $name = isset($row[$idx['name']]) ? trim((string) $row[$idx['name']]) : '';
            $slug = isset($row[$idx['slug']]) ? sanitize_title((string) $row[$idx['slug']]) : '';
            $p_slug = isset($row[$idx['parent_slug']]) ? sanitize_title((string) $row[$idx['parent_slug']]) : '';
            $level = isset($row[$idx['level']]) ? absint($row[$idx['level']]) : 0;

            if ($name === '' || $slug === '') {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'slug' => $slug,
                'parent_slug' => $p_slug,
                'level' => $level,
            ];
        }
        fclose($handle);

        if (empty($rows)) {
            return [['type' => 'warning', 'text' => __('No rows found in CSV.', 'metra-classifieds-plugin')]];
        }

        // Sort by level, then by parent_slug, to make parent creation more likely earlier.
        usort($rows, static function($a, $b) {
            if ($a['level'] === $b['level']) {
                return strcmp((string) $a['parent_slug'], (string) $b['parent_slug']);
            }
            return $a['level'] <=> $b['level'];
        });

        $taxonomy = MC_CPT::TAX;

        // Map "slug|parent_id" => term_id
        $known = [];

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            $parent_id = 0;

            if ($r['parent_slug'] !== '') {
                // Find parent by slug at any level first, then prefer already known mapping.
                // In this CSV format parent slugs are unique per tree level for most datasets.
                $parent_term = get_term_by('slug', $r['parent_slug'], $taxonomy);
                if ($parent_term && !is_wp_error($parent_term)) {
                    $parent_id = (int) $parent_term->term_id;
                }
            }

            $exists = term_exists($r['slug'], $taxonomy, $parent_id);

            if (is_array($exists) && isset($exists['term_id'])) {
                $term_id = (int) $exists['term_id'];

                if ($overwrite) {
                    $res = wp_update_term($term_id, $taxonomy, [
                        'name' => $r['name'],
                        'slug' => $r['slug'],
                        'parent' => $parent_id,
                    ]);

                    if (is_wp_error($res)) {
                        $out[] = ['type' => 'error', 'text' => sprintf(__('Update failed for "%s": %s', 'metra-classifieds-plugin'), $r['name'], $res->get_error_message())];
                    } else {
                        $updated++;
                        $out[] = ['type' => 'success', 'text' => sprintf(__('Updated: %s', 'metra-classifieds-plugin'), $r['name'])];
                        $known[$r['slug'] . '|' . $parent_id] = $term_id;
                    }
                } else {
                    $skipped++;
                    $out[] = ['type' => 'warning', 'text' => sprintf(__('Skipped existing: %s', 'metra-classifieds-plugin'), $r['name'])];
                    $known[$r['slug'] . '|' . $parent_id] = $term_id;
                }

                continue;
            }

            $res = wp_insert_term($r['name'], $taxonomy, [
                'slug' => $r['slug'],
                'parent' => $parent_id,
            ]);

            if (is_wp_error($res)) {
                $out[] = ['type' => 'error', 'text' => sprintf(__('Insert failed for "%s": %s', 'metra-classifieds-plugin'), $r['name'], $res->get_error_message())];
                continue;
            }

            $term_id = isset($res['term_id']) ? (int) $res['term_id'] : 0;
            if ($term_id > 0) {
                $created++;
                $known[$r['slug'] . '|' . $parent_id] = $term_id;
                $out[] = ['type' => 'success', 'text' => sprintf(__('Created: %s', 'metra-classifieds-plugin'), $r['name'])];
            }
        }

        // Summary notice
        array_unshift($out, ['type' => 'success', 'text' => sprintf(__('Import finished. Created: %d, Updated: %d, Skipped: %d', 'metra-classifieds-plugin'), $created, $updated, $skipped)]);

        return $out;
    }

    private static function export_categories_csv(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $taxonomy = MC_CPT::TAX;

        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ]);

        if (is_wp_error($terms)) {
            wp_die(esc_html__('Could not export categories.', 'metra-classifieds-plugin'));
        }

        // Build quick parent maps
        $by_id = [];
        foreach ($terms as $t) {
            $by_id[(int) $t->term_id] = $t;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=metra-categories.csv');

        $out = fopen('php://output', 'w');
        if (!$out) {
            exit;
        }

        fputcsv($out, ['name', 'slug', 'parent_name', 'parent_slug', 'level']);

        foreach ($terms as $t) {
            $parent_id = (int) $t->parent;
            $level = 1;
            $p_name = '';
            $p_slug = '';
            $cursor = $parent_id;
            while ($cursor && isset($by_id[$cursor])) {
                $level++;
                $cursor = (int) $by_id[$cursor]->parent;
            }
            if ($parent_id && isset($by_id[$parent_id])) {
                $p_name = (string) $by_id[$parent_id]->name;
                $p_slug = (string) $by_id[$parent_id]->slug;
            }

            fputcsv($out, [(string) $t->name, (string) $t->slug, $p_name, $p_slug, $level]);
        }

        fclose($out);
        exit;
    }

    /**
     * Create/return a shared placeholder attachment (generated locally).
     */
    private static function get_or_create_demo_placeholder_attachment(int $post_id): int {
        $key = 'mc_demo_placeholder_attachment_id';
        $existing = (int) get_option($key, 0);
        if ($existing > 0 && get_post($existing)) {
            return $existing;
        }
        $att = self::generate_local_demo_image($post_id, 'METRA Demo Placeholder', 'PLACEHOLDER');
        if ($att > 0) {
            update_option($key, $att, false);
        }
        return $att;
    }

    /**
     * Generate a local demo image (PNG) and attach it to a post.
     * Requires GD. If GD is unavailable, returns 0.
     */
    private static function generate_local_demo_image(int $post_id, string $label, string $seed): int {
        if (!function_exists('imagecreatetruecolor')) {
            return 0;
        }

        $w = 1600;
        $h = 1200;

        $im = imagecreatetruecolor($w, $h);
        if (!$im) {
            return 0;
        }

        // Background
        $bg = imagecolorallocate($im, rand(15, 60), rand(15, 60), rand(15, 60));
        imagefilledrectangle($im, 0, 0, $w, $h, $bg);

        // Accent blocks
        for ($i = 0; $i < 6; $i++) {
            $c = imagecolorallocate($im, rand(70, 220), rand(70, 220), rand(70, 220));
            imagefilledrectangle($im, rand(0, $w-1), rand(0, $h-1), rand(0, $w-1), rand(0, $h-1), $c);
        }

        // Text
        $white = imagecolorallocate($im, 245, 245, 245);
        $text1 = 'METRA DEMO';
        $text2 = $seed;
        $text3 = mb_substr($label, 0, 60);

        imagestring($im, 5, 40, 50, $text1, $white);
        imagestring($im, 5, 40, 90, $text2, $white);
        imagestring($im, 4, 40, 130, $text3, $white);

        // Write temp file
        $tmp = wp_tempnam('metra-demo-' . sanitize_file_name($seed) . '.png');
        if (!$tmp) {
            imagedestroy($im);
            return 0;
        }

        imagepng($im, $tmp, 6);
        imagedestroy($im);

        // Sideload into media library
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file_array = [
            'name'     => 'metra-demo-' . sanitize_file_name($seed) . '.png',
            'tmp_name' => $tmp,
        ];

        $att_id = media_handle_sideload($file_array, $post_id, $label);

        if (is_wp_error($att_id)) {
            @unlink($tmp);
            return 0;
        }

        return (int) $att_id;
    }

}
<?php

declare(strict_types=1);

namespace MetraClassifieds;

/**
 * Widgets bootstrap.
 */
final class MC_Widgets {

    public static function init(): void {
        add_action('widgets_init', [self::class, 'register_widgets']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function register_widgets(): void {
        register_widget(MC_Categories_Widget::class);
    }

    public static function enqueue_assets(): void {
        // Load widget CSS only when widgets are active.
        if (!is_active_widget(false, false, 'mc_categories_widget', true)) {
            return;
        }

        wp_enqueue_style(
            'metra-categories-widget',
			plugins_url('assets/mc-categories-widget.css', MC_PLUGIN_FILE),
            [],
			MC_VERSION
        );
    }
}

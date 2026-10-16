<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Step 1: Central design tokens (CSS variables) + base stylesheet enqueue.
 *
 * This introduces a stable place for future design settings (colors, fonts, radius, etc.)
 * without touching functional code.
 */
final class MC_Design {

    /**
     * Enqueue base theme stylesheet and inject CSS variables.
     */
    public static function enqueue(): void {
        // Frontend only
        if (is_admin()) {
            return;
        }

        // Always enqueue functional assets.
        // These assets support upload UX and validation but do not impose a theme design.
        $functional_css_handle = 'metra-classifieds-functional';
        $functional_css_src    = plugins_url('public/assets/css/mc-functional.css', MC_PLUGIN_FILE);
        wp_enqueue_style($functional_css_handle, $functional_css_src, [], MC_VERSION);

        $js_handle = 'metra-classifieds-max-images';
        $js_src    = plugins_url('public/assets/js/mc-max-images.js', MC_PLUGIN_FILE);
        wp_enqueue_script($js_handle, $js_src, [], MC_VERSION, true);

        $upload_js_handle = 'metra-classifieds-upload-dnd';
        $upload_js_src    = plugins_url('public/assets/js/mc-upload-dnd.js', MC_PLUGIN_FILE);
        wp_enqueue_script($upload_js_handle, $upload_js_src, [], MC_VERSION, true);

        // Favorites heart toggle: functional JS only, no theme lock-in.
        // Loaded globally on the frontend because listing grids can appear in
        // multiple METRA views (home, category listings, profile-related views).
        $favorites_js_handle = 'metra-classifieds-favorites-toggle';
        $favorites_js_src    = plugins_url('public/assets/js/mc-favorites-toggle.js', MC_PLUGIN_FILE);
        wp_enqueue_script($favorites_js_handle, $favorites_js_src, [], MC_VERSION, true);

        $pause_js_handle = 'metra-classifieds-pause-toggle';
        $pause_js_src    = plugins_url('public/assets/js/mc-pause-toggle.js', MC_PLUGIN_FILE);
        wp_enqueue_script($pause_js_handle, $pause_js_src, [], MC_VERSION, true);

        wp_localize_script($favorites_js_handle, 'MC_FAVORITES', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('mc_toggle_favorite_ajax'),
            'i18n'    => [
                'setFavorite'    => esc_html__('Set as Favorite', 'metra-classifieds-plugin'),
                'favorite'       => esc_html__('Favorite', 'metra-classifieds-plugin'),
                'working'        => esc_html__('Updating…', 'metra-classifieds-plugin'),
                'genericError'   => esc_html__('Favorite could not be updated.', 'metra-classifieds-plugin'),
            ],
            'icons'   => [
                'heartOutline' => MC_Icons::render('heart-outline', ['class' => 'mc-icon mc-icon-heart-outline', 'width' => 14, 'height' => 14]),
                'heartFilled'  => MC_Icons::render('heart-filled', ['class' => 'mc-icon mc-icon-heart-filled', 'width' => 14, 'height' => 14]),
            ],
        ]);

        wp_localize_script($pause_js_handle, 'MC_PAUSE', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'i18n' => [
                'genericError' => esc_html__('Pause status could not be updated.', 'metra-classifieds-plugin'),
            ],
        ]);

        $handle = 'metra-classifieds-theme';
        $src    = plugins_url('public/assets/css/mc-theme.css', MC_PLUGIN_FILE);

        wp_enqueue_style($handle, $src, [], MC_VERSION);

        $tokens = self::get_tokens();
        $css    = self::tokens_to_css($tokens);
        if ($css !== '') {
            wp_add_inline_style($handle, $css);
        }

        $max_images = (int) get_option('mc_max_images_per_listing', 10);
        if ($max_images < 1) {
            $max_images = 10;
        }

        wp_localize_script($js_handle, 'MC_MAX_IMAGES', [
            'max'  => $max_images,
            'i18n' => [
                'title'   => esc_html__('Notice', 'metra-classifieds-plugin'),
                'ok'      => esc_html__('OK', 'metra-classifieds-plugin'),
                'message' => sprintf(esc_html__('You can select up to %d images.', 'metra-classifieds-plugin'), $max_images),
            ],
        ]);

        wp_localize_script($upload_js_handle, 'MC_UPLOAD_DND', [
            'i18n' => [
                'dropHere'      => esc_html__('Drop images here', 'metra-classifieds-plugin'),
                'orChoose'      => esc_html__('or choose files', 'metra-classifieds-plugin'),
                'chooseImages'  => esc_html__('Choose images', 'metra-classifieds-plugin'),
                'noFiles'       => esc_html__('No files selected', 'metra-classifieds-plugin'),
                'selectedFiles' => esc_html__('%d file(s) selected', 'metra-classifieds-plugin'),
            ],
        ]);

}

    /**
     * Returns design tokens (defaults now; settings will be added in later steps).
     *
     * @return array<string, string>
     */
    private static function get_tokens(): array {
        $defaults = [
            // colors
            'mc-primary'       => '#1e73be',
            'mc-primary-text'  => '#ffffff',
            'mc-heading'       => '#1e73be',
            // === MC: listing title color token v0.0710 ===
            'mc-listing-title-color' => '#1a1a1a',
            // === MC: home cat parent color token v0.0711 ===
            'mc-home-cat-parent-color' => '#1e73be',
            'mc-bg'            => '#ffffff',
            'mc-surface'       => '#ffffff',
            'mc-text'          => '#1a1a1a',
            'mc-muted'         => '#6b7280',
            'mc-border'        => 'rgba(0,0,0,.12)',
            'mc-border-width'  => '1px',

            // typography
            'mc-font'          => 'inherit',
            'mc-listing-font'  => 'inherit',
            'mc-listing-title-font' => 'inherit',
            'mc-font-size'     => '16px',
            'mc-title-size'    => '16px',
            'mc-excerpt-size'  => '14px',
            'mc-title-weight'  => '700',
            'mc-excerpt-weight'=> '400',

            // shape/spacing
            'mc-radius'        => '10px',
            'mc-gap'           => '12px',

            // component sizing
            'mc-card-radius'   => '0px',
            'mc-card-padding'  => '0px',
            'mc-thumb-radius'  => '0px',
            'mc-box-radius'    => '14px',
            'mc-home-gap'      => '14px',

            // buttons/inputs
            'mc-input-radius'  => '8px',
            'mc-button-radius' => '10px',
        ];

        // Step 2: allow a small, safe subset of tokens to be overridden via settings.
        $opt = get_option('mc_design', []);
        if (!is_array($opt)) {
            $opt = [];
        }

        // Only allow a controlled set of keys.
        $map = [
            'primary'       => 'mc-primary',
            'heading'       => 'mc-heading',
            // === MC: listing title color map v0.0710 ===
            'listing_title_color' => 'mc-listing-title-color',
            // === MC: home cat parent color map v0.0711 ===
            'home_cat_parent_color' => 'mc-home-cat-parent-color',
            'text'          => 'mc-text',
            'font'          => 'mc-font',
            'listing_font'  => 'mc-listing-font',
            'listing_title_font' => 'mc-listing-title-font',
            'title_size'    => 'mc-title-size',
            'excerpt_size'  => 'mc-excerpt-size',
            'title_weight'  => 'mc-title-weight',
            'excerpt_weight'=> 'mc-excerpt-weight',
            'radius'        => 'mc-radius',
            'border_width'  => 'mc-border-width',
            'input_radius'  => 'mc-input-radius',
            'button_radius' => 'mc-button-radius',
            'gap'           => 'mc-gap',
        ];

        $tokens = $defaults;
        foreach ($map as $opt_key => $token_name) {
            if (!array_key_exists($opt_key, $opt)) {
                continue;
            }
            $v = $opt[$opt_key];
            if (!is_string($v) && !is_numeric($v)) {
                continue;
            }
            $tokens[$token_name] = (string) $v;
        }

        /**
         * Allow themes/child-themes to override tokens safely.
         *
         * @param array<string, string> $tokens
         */
        $tokens = apply_filters('mc_design_tokens', $tokens);

        // Ensure values are strings.
        foreach ($tokens as $k => $v) {
            if (!is_string($k) || (!is_string($v) && !is_numeric($v))) {
                unset($tokens[$k]);
                continue;
            }
            $tokens[$k] = (string) $v;
        }

        // If per-component fonts are not set, fall back to the global font.
        if (($tokens['mc-listing-font'] ?? '') === 'inherit') {
            $tokens['mc-listing-font'] = $tokens['mc-font'];
        }
        if (($tokens['mc-listing-title-font'] ?? '') === 'inherit') {
            $tokens['mc-listing-title-font'] = $tokens['mc-font'];
        }

        // derive dependent tokens from global values when not explicitly set
        if (!isset($tokens['mc-home-gap'])) {
            $tokens['mc-home-gap'] = $tokens['mc-gap'];
        }
        if (!isset($tokens['mc-box-radius'])) {
        }

	    // focus ring derived from primary
	    if (!isset($tokens['mc-focus-ring']) && !empty($tokens['mc-primary'])) {
	        $tokens['mc-focus-ring'] = self::hex_to_rgba((string) $tokens['mc-primary'], 0.20);
	    }

        return $tokens;
    }

	private static function hex_to_rgba(string $hex, float $alpha): string {
	    $hex = ltrim(trim($hex), '#');
	    if (strlen($hex) === 3) {
	        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
	    }
	    if (strlen($hex) !== 6) {
	        return 'rgba(2,118,255,0.20)';
	    }
	    $r = hexdec(substr($hex, 0, 2));
	    $g = hexdec(substr($hex, 2, 2));
	    $b = hexdec(substr($hex, 4, 2));
	    $a = max(0, min(1, $alpha));
	    return sprintf('rgba(%d,%d,%d,%.3f)', $r, $g, $b, $a);
	}

    // === MC: get_tokens_public v0.0714 START ===
    public static function get_tokens_public(): string {
        $tokens = self::get_tokens();
        return self::tokens_to_css($tokens);
    }
    // === MC: get_tokens_public v0.0714 END ===

    /**
     * @param array<string, string> $tokens
     */
    private static function tokens_to_css(array $tokens): string {
        if (empty($tokens)) {
            return '';
        }

        $lines = [];
        foreach ($tokens as $name => $value) {
            // Keep it safe and predictable (CSS variable names only).
            $name = preg_replace('/[^a-zA-Z0-9\-]/', '', $name ?? '');
            if ($name === '') {
                continue;
            }
            $lines[] = sprintf('--%s:%s;', $name, $value);
        }

        if (empty($lines)) {
            return '';
        }

        return ":root{\n" . implode("\n", $lines) . "\n}\n";
    }
}

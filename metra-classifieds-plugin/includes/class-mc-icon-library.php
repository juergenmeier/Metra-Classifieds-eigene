<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Icon_Library {
    public const OPTION_KEY = 'mc_custom_icons';
    private const NONCE_ACTION = 'mc_icons_settings_save';
    private const NONCE_NAME = 'mc_icons_settings_nonce';

    public static function get_groups(): array {
        return [
            'vehicles' => __('Vehicles', 'metra-classifieds-plugin'),
            'animals' => __('Animals', 'metra-classifieds-plugin'),
            'home' => __('Home', 'metra-classifieds-plugin'),
            'electronics' => __('Electronics', 'metra-classifieds-plugin'),
            'fashion' => __('Fashion', 'metra-classifieds-plugin'),
            'jobs' => __('Jobs', 'metra-classifieds-plugin'),
            'services' => __('Services', 'metra-classifieds-plugin'),
            'sports' => __('Sports', 'metra-classifieds-plugin'),
            'tickets' => __('Tickets & Events', 'metra-classifieds-plugin'),
            'family' => __('Kids & Family', 'metra-classifieds-plugin'),
            'hobby' => __('Hobby & Leisure', 'metra-classifieds-plugin'),
            'other' => __('Other', 'metra-classifieds-plugin'),
        ];
    }

    public static function get_custom_icons(): array {
        $icons = get_option(self::OPTION_KEY, []);
        return is_array($icons) ? $icons : [];
    }

    public static function get_custom_icon(string $key): ?array {
        $icons = self::get_custom_icons();
        return isset($icons[$key]) && is_array($icons[$key]) ? $icons[$key] : null;
    }

    public static function get_upload_base(): array {
        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']) . 'metra-classifieds-icons/';
        $url = trailingslashit($upload['baseurl']) . 'metra-classifieds-icons/';
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        return ['dir' => $dir, 'url' => $url];
    }

    public static function get_custom_icon_path(string $file): string {
        $base = self::get_upload_base();
        return $base['dir'] . ltrim($file, '/');
    }

    public static function render_settings_page(): void {
        $icons = self::get_custom_icons();
        $groups = self::get_groups();

        echo '<div class="mc-icons-admin-panel mc-icons-admin-panel--upload">';
        echo '<form method="post" enctype="multipart/form-data">';
        wp_nonce_field('mc_settings_save_icons', 'mc_settings_nonce');
        echo '<input type="hidden" name="mc_settings_save" value="1" />';
        echo '<input type="hidden" name="mc_settings_tab" value="icons" />';
        echo '<table class="form-table mc-icons-admin-form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="mc_icon_name">' . esc_html__('Name of new Icon', 'metra-classifieds-plugin') . '</label></th><td>';
        echo '<input type="text" class="regular-text" id="mc_icon_name" name="mc_icon_name" value="" />';
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="mc_icon_group">' . esc_html__('Group', 'metra-classifieds-plugin') . '</label></th><td>';
        echo '<select id="mc_icon_group" name="mc_icon_group">';
        foreach ($groups as $group_key => $group_label) {
            echo '<option value="' . esc_attr($group_key) . '">' . esc_html($group_label) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="mc_icon_file">' . esc_html__('SVG Icon', 'metra-classifieds-plugin') . '</label></th><td>';
        echo '<input type="file" id="mc_icon_file" name="mc_icon_file" accept=".svg,image/svg+xml" />';
        echo '<p class="description">' . esc_html__('Only SVG files are allowed.', 'metra-classifieds-plugin') . '</p>';
        echo '</td></tr>';
        echo '</tbody></table>';
        submit_button(__('Upload Icon', 'metra-classifieds-plugin'));
        echo '</form>';
        echo '</div>';

        $design = get_option('mc_design', []);
        $global_size = is_array($design) && isset($design['fa_icon_size']) ? (int) $design['fa_icon_size'] : 20;
        $global_size = max(12, min(48, $global_size));
        $global_color = is_array($design) && isset($design['fa_icon_color']) && is_string($design['fa_icon_color']) ? (string) sanitize_hex_color($design['fa_icon_color']) : '#297bc1';
        if ($global_color === '') {
            $global_color = '#297bc1';
        }

        
        echo '<div class="mc-icons-admin-panel mc-icons-admin-panel--defaults">';

        echo '<form method="post">';
        wp_nonce_field('mc_settings_save_icons', 'mc_settings_nonce');
        echo '<input type="hidden" name="mc_settings_save" value="1" />';
        echo '<input type="hidden" name="mc_settings_tab" value="icons" />';
        echo '<input type="hidden" name="mc_icons_action" value="reset_size" />';
        echo '<table class="form-table mc-icons-admin-form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="mc_icon_reset_size">' . esc_html__('Set all Icons to...', 'metra-classifieds-plugin') . '</label></th><td>';
        echo '<input type="number" min="12" max="48" step="1" id="mc_icon_reset_size" name="mc_icon_reset_size" value="' . esc_attr((string) $global_size) . '" style="width:100px;" /> ';
        echo esc_html__('Pixel', 'metra-classifieds-plugin');
        echo '<p class="description">' . esc_html__('Resets all category icons to one global size and removes individual size overrides.', 'metra-classifieds-plugin') . '</p>';
        submit_button(__('Apply size to all Icons', 'metra-classifieds-plugin'), 'secondary', 'submit', false);
        echo '</td></tr>';
        echo '</tbody></table>';
        echo '</form>';

        echo '<form method="post" style="margin-top:8px;">';
        wp_nonce_field('mc_settings_save_icons', 'mc_settings_nonce');
        echo '<input type="hidden" name="mc_settings_save" value="1" />';
        echo '<input type="hidden" name="mc_settings_tab" value="icons" />';
        echo '<input type="hidden" name="mc_icons_action" value="reset_color" />';
        echo '<table class="form-table mc-icons-admin-form-table" role="presentation"><tbody>';
        echo '<tr><th scope="row"><label for="mc_icon_reset_color">' . esc_html__('Color', 'metra-classifieds-plugin') . '</label></th><td>';
        echo '<input type="color" id="mc_icon_reset_color" name="mc_icon_reset_color" value="' . esc_attr($global_color) . '" style="width:72px;height:42px;padding:2px;vertical-align:middle;" /> ';
        echo '<code style="margin-left:8px;vertical-align:middle;">' . esc_html($global_color) . '</code>';
        echo '<p class="description">' . esc_html__('Uses the current global icon color as start value. Click the color field to open the color wheel.', 'metra-classifieds-plugin') . '</p>';
        submit_button(__('Apply color to all Icons', 'metra-classifieds-plugin'), 'secondary', 'submit', false);
        echo '</td></tr>';
        echo '</tbody></table>';
        echo '</form>';
        echo '</div>';

        
        echo '<h2>' . esc_html__('Stored custom Icons', 'metra-classifieds-plugin') . '</h2>';
        if (empty($icons)) {
            echo '<p>' . esc_html__('No custom icons uploaded yet.', 'metra-classifieds-plugin') . '</p>';
            return;
        }

        echo '<div class="mc-icons-admin-list-wrap">';
        echo '<div class="mc-icons-admin-list-head">';
        echo '<span>' . esc_html__('Icon', 'metra-classifieds-plugin') . '</span>';
        echo '<span>' . esc_html__('Name', 'metra-classifieds-plugin') . '</span>';
        echo '<span>' . esc_html__('Slug', 'metra-classifieds-plugin') . '</span>';
        echo '<span>' . esc_html__('Group', 'metra-classifieds-plugin') . '</span>';
        echo '<span>' . esc_html__('File', 'metra-classifieds-plugin') . '</span>';
        echo '<span>' . esc_html__('Actions', 'metra-classifieds-plugin') . '</span>';
        echo '</div>';

        $row_index = 0;
        foreach ($icons as $key => $icon) {
            if (!is_array($icon)) {
                continue;
            }
            $label = isset($icon['label']) ? (string) $icon['label'] : $key;
            $file = isset($icon['file']) ? (string) $icon['file'] : '';
            $group = isset($icon['group']) ? (string) $icon['group'] : 'other';
            $group_label = $groups[$group] ?? $group;
            $svg = MC_Icons::get_icon_svg_by_key($key);

            echo '<div class="mc-icons-admin-row ' . (($row_index % 2 === 0) ? 'is-odd' : 'is-even') . '">';
            echo '<div class="mc-icons-admin-row__icon">' . $svg . '</div>';
            echo '<div class="mc-icons-admin-row__name"><strong>' . esc_html($label) . '</strong></div>';
            echo '<div class="mc-icons-admin-row__slug">' . esc_html($key) . '</div>';
            echo '<div class="mc-icons-admin-row__group">' . esc_html($group_label) . '</div>';
            echo '<div class="mc-icons-admin-row__file">' . esc_html($file) . '</div>';
            echo '<div class="mc-icons-admin-row__actions">';
            echo '<form method="post">';
            wp_nonce_field('mc_settings_save_icons', 'mc_settings_nonce');
            echo '<input type="hidden" name="mc_settings_save" value="1" />';
            echo '<input type="hidden" name="mc_settings_tab" value="icons" />';
            echo '<input type="hidden" name="mc_icons_action" value="delete" />';
            echo '<input type="hidden" name="mc_icon_key" value="' . esc_attr($key) . '" />';
            submit_button(__('Delete', 'metra-classifieds-plugin'), 'delete small', 'submit', false, ['onclick' => "return confirm('" . esc_js(__('Delete this icon?', 'metra-classifieds-plugin')) . "');"]);
            echo '</form>';
            echo '</div>';
            echo '</div>';

            $row_index++;
        }

        echo '</div>';
        echo '<style>
.mc-icons-admin-panel hr{display:none;}
        .mc-icons-admin-panel{


            
            max-width:1100px;
            background:#fff;
            border:1px solid #dcdcde;
            border-radius:12px;
            padding:12px 16px 10px 16px;
            margin:0 0 12px 0;
            box-sizing:border-box;
        }
        .mc-icons-admin-form-table{
            margin:0;
        }
        .mc-icons-admin-form-table th,
        .mc-icons-admin-form-table td{
            padding-top:8px !important;
            padding-bottom:8px !important;
            vertical-align:top;
        }
        .mc-icons-admin-form-table p.description{
            margin:4px 0 0 0;
        }
        .mc-icons-admin-panel .button{
            margin-top:6px;
        }
        .mc-icons-admin-panel:first-of-type{
    margin-top:25px;
}

.mc-icons-admin-list-wrap{
            border:1px solid #dcdcde;
            border-radius:12px;
            overflow:hidden;
            background:#fff;
            max-width:1100px;
        }
        .mc-icons-admin-list-head,
        .mc-icons-admin-row{
            display:grid;
            grid-template-columns:56px minmax(140px,1.2fr) minmax(110px,.9fr) minmax(140px,1fr) minmax(140px,1fr) 110px;
            gap:14px;
            align-items:center;
            padding:12px 14px;
        }
        .mc-icons-admin-list-head{
            background:#f0f0f1;
            font-weight:700;
            color:#1d2327;
            border-bottom:1px solid #dcdcde;
        }
        .mc-icons-admin-row{
            border-top:1px solid #f0f0f1;
        }
        .mc-icons-admin-row:first-of-type{
            border-top:0;
        }
        .mc-icons-admin-row.is-odd{
            background:#ffffff;
        }
        .mc-icons-admin-row.is-even{
            background:#f6f7f7;
        }
        .mc-icons-admin-row__icon{
            display:flex;
            align-items:center;
            justify-content:center;
            min-width:21px;
            color:#1d2327;
            line-height:1;
        }
        .mc-icons-admin-row__icon svg{
            width:21px;
            height:21px;
            stroke:currentColor;
            fill:none;
            stroke-width:2;
        }
        .mc-icons-admin-row__slug,
        .mc-icons-admin-row__group,
        .mc-icons-admin-row__file{
            color:#646970;
            word-break:break-word;
        }
        .mc-icons-admin-row__actions form{
            margin:0;
        }
        @media (max-width: 900px){
            .mc-icons-admin-list-head{
                display:none;
            }
            .mc-icons-admin-row{
                grid-template-columns:48px 1fr;
                gap:8px 12px;
                align-items:start;
            }
            .mc-icons-admin-row__name{
                grid-column:2;
            }
            .mc-icons-admin-row__slug,
            .mc-icons-admin-row__group,
            .mc-icons-admin-row__file,
            .mc-icons-admin-row__actions{
                grid-column:2;
            }
        }
        </style>';
    }

    public static function handle_settings_save(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_POST['mc_icons_action']) ? sanitize_key((string) $_POST['mc_icons_action']) : 'upload';

        if ($action === 'delete') {
            $key = isset($_POST['mc_icon_key']) ? sanitize_key((string) $_POST['mc_icon_key']) : '';
            if ($key !== '') {
                self::delete_icon($key);
            }
            return;
        }

        if ($action === 'reset_size') {
            $size = isset($_POST['mc_icon_reset_size']) ? (int) $_POST['mc_icon_reset_size'] : 20;
            self::reset_all_icon_sizes($size);
            return;
        }

        if ($action === 'reset_color') {
            $color = isset($_POST['mc_icon_reset_color']) ? (string) sanitize_hex_color((string) $_POST['mc_icon_reset_color']) : '#297bc1';
            if ($color === '') {
                $color = '#297bc1';
            }
            self::reset_all_icon_colors($color);
            return;
        }

        self::handle_upload();
    }

    private static function handle_upload(): void {
        $label = isset($_POST['mc_icon_name']) ? sanitize_text_field((string) $_POST['mc_icon_name']) : '';
        $group = isset($_POST['mc_icon_group']) ? sanitize_key((string) $_POST['mc_icon_group']) : 'other';
        if ($label === '' || empty($_FILES['mc_icon_file']['name'])) {
            return;
        }
        $groups = self::get_groups();
        if (!isset($groups[$group])) {
            $group = 'other';
        }
        $key = self::generate_unique_key($label);
        $tmp = isset($_FILES['mc_icon_file']['tmp_name']) ? (string) $_FILES['mc_icon_file']['tmp_name'] : '';
        $name = isset($_FILES['mc_icon_file']['name']) ? (string) $_FILES['mc_icon_file']['name'] : '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return;
        }
        if (strtolower((string) pathinfo($name, PATHINFO_EXTENSION)) !== 'svg') {
            return;
        }
        $content = (string) file_get_contents($tmp);
        if ($content === '' || stripos($content, '<svg') === false) {
            return;
        }
        $content = wp_kses($content, [
            'svg' => ['class' => true, 'xmlns' => true, 'width' => true, 'height' => true, 'viewBox' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'role' => true, 'aria-label' => true, 'focusable' => true, 'aria-hidden' => true],
            'g' => ['fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'transform' => true],
            'path' => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'transform' => true],
            'circle' => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true],
            'rect' => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true],
            'line' => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true],
            'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true],
            'polygon' => ['points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true],
            'ellipse' => ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true],
        ]);
        if ($content === '' || stripos($content, '<svg') === false) {
            return;
        }
        $base = self::get_upload_base();
        $filename = $key . '.svg';
        file_put_contents($base['dir'] . $filename, $content);
        $icons = self::get_custom_icons();
        $icons[$key] = [
            'label' => $label,
            'group' => $group,
            'file' => $filename,
            'origin' => 'custom',
        ];
        update_option(self::OPTION_KEY, $icons);
    }

    private static function delete_icon(string $key): void {
        $icons = self::get_custom_icons();
        if (!isset($icons[$key]) || !is_array($icons[$key])) {
            return;
        }
        $file = isset($icons[$key]['file']) ? (string) $icons[$key]['file'] : '';
        if ($file !== '') {
            $path = self::get_custom_icon_path($file);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        unset($icons[$key]);
        update_option(self::OPTION_KEY, $icons);
    }

    private static function reset_all_icon_sizes(int $size): void {
        $size = max(12, min(48, $size));

        $design = get_option('mc_design', []);
        if (!is_array($design)) {
            $design = [];
        }
        $design['fa_icon_size'] = $size;
        update_option('mc_design', $design);

        $terms = get_terms([
            'taxonomy' => MC_CPT::TAX,
            'hide_empty' => false,
            'fields' => 'ids',
        ]);

        if (is_array($terms)) {
            foreach ($terms as $term_id) {
                delete_term_meta((int) $term_id, '_mc_fa_size');
            }
        }
    }

    private static function reset_all_icon_colors(string $color): void {
        $color = (string) sanitize_hex_color($color);
        if ($color === '') {
            $color = '#297bc1';
        }

        $design = get_option('mc_design', []);
        if (!is_array($design)) {
            $design = [];
        }
        $design['fa_icon_color'] = $color;
        update_option('mc_design', $design);

        $terms = get_terms([
            'taxonomy' => MC_CPT::TAX,
            'hide_empty' => false,
            'fields' => 'ids',
        ]);

        if (is_array($terms)) {
            foreach ($terms as $term_id) {
                delete_term_meta((int) $term_id, '_mc_fa_color');
            }
        }
    }

    private static function generate_unique_key(string $label): string {
        $base = sanitize_key(remove_accents($label));
        if ($base === '') {
            $base = 'icon';
        }
        $key = $base;
        $icons = self::get_custom_icons();
        $builtins = MC_Icons::get_builtin_icon_registry();
        $i = 2;
        while (isset($icons[$key]) || isset($builtins[$key])) {
            $key = $base . '-' . $i;
            $i++;
        }
        return $key;
    }
}

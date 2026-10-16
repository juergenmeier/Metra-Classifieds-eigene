<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Icons {
    private const META_FA_ICON = '_mc_fa_icon';
    private const META_FA_COLOR = '_mc_fa_color';
    private const META_FA_SIZE = '_mc_fa_size';

    /**
     * Existing local SVG renderer kept intact for non-category areas.
     */
    public static function render(string $name, array $args = []): string {
        $file = MC_PLUGIN_DIR . 'assets/icons/' . $name . '.svg';
        if (!is_readable($file)) {
            return '';
        }

        $svg = (string) file_get_contents($file);
        if ($svg === '') {
            return '';
        }

        $class = isset($args['class']) ? (string) $args['class'] : 'mc-icon';
        $label = isset($args['label']) ? (string) $args['label'] : '';
        $width = isset($args['width']) ? max(1, (int) $args['width']) : 20;
        $height = isset($args['height']) ? max(1, (int) $args['height']) : 20;

        $aria = $label === ''
            ? ' aria-hidden="true" focusable="false"'
            : ' role="img" aria-label="' . esc_attr($label) . '" focusable="false"';

        $replacement = '<svg class="' . esc_attr($class) . '" width="' . $width . '" height="' . $height . '"' . $aria;
        $svg = preg_replace('/<svg\b/', $replacement, $svg, 1);

        return is_string($svg) ? $svg : '';
    }

    /**
     * Init category-admin controls for Lucide main category icons.
     */
    public static function init(): void {
        if (!is_admin()) {
            return;
        }

        add_action(MC_CPT::TAX . '_add_form_fields', [self::class, 'render_add_fields']);
        add_action(MC_CPT::TAX . '_edit_form_fields', [self::class, 'render_edit_fields']);
        add_action('created_' . MC_CPT::TAX, [self::class, 'save_term_fields']);
        add_action('edited_' . MC_CPT::TAX, [self::class, 'save_term_fields']);
        add_action('admin_head-edit-tags.php', [self::class, 'render_admin_styles']);
    }

    public static function are_fa_icons_enabled(): bool {
        $design = get_option('mc_design', []);
        if (!is_array($design) || !array_key_exists('fa_icons_enabled', $design)) {
            return true;
        }
        return in_array((string) $design['fa_icons_enabled'], ['1', 'true', 'yes', 'on'], true);
    }


public static function get_builtin_icon_registry(): array {
    // v0.0604: reads all SVGs from assets/lucide/ via metra_lucide_icons()
    // instead of the old hardcoded 50-entry list.
    // Rollback: restore previous hardcoded array.
    if (function_exists('metra_lucide_icons')) {
        $result = [];
        foreach (metra_lucide_icons() as $key => $filename) {
            $label = ucwords(str_replace(['-', '_'], ' ', $key));
            $result[$key] = ['label' => $label, 'group' => 'lucide'];
        }
        return $result;
    }
    // Fallback: minimal list if registry not loaded
    return ['car' => ['label' => 'Car', 'group' => 'lucide']];
}

public static function get_icon_registry(): array {
    $registry = self::get_builtin_icon_registry();
    if (class_exists(__NAMESPACE__ . '\MC_Icon_Library')) {
        foreach (MC_Icon_Library::get_custom_icons() as $key => $icon) {
            if (!is_array($icon)) {
                continue;
            }
            $registry[$key] = [
                'label' => isset($icon['label']) ? (string) $icon['label'] : $key,
                'group' => isset($icon['group']) ? (string) $icon['group'] : 'other',
                'origin' => 'custom',
                'file' => isset($icon['file']) ? (string) $icon['file'] : '',
            ];
        }
    }
    return $registry;
}

public static function get_grouped_icon_registry(): array {
    $groups = class_exists(__NAMESPACE__ . '\MC_Icon_Library') ? MC_Icon_Library::get_groups() : ['other' => __('Other', 'metra-classifieds-plugin')];
    $grouped = [];
    foreach (self::get_icon_registry() as $key => $icon) {
        $group = isset($icon['group']) && is_string($icon['group']) ? $icon['group'] : 'other';
        if (!isset($grouped[$group])) {
            $grouped[$group] = [];
        }
        $grouped[$group][$key] = $icon;
    }
    $ordered = [];
    foreach ($groups as $group_key => $label) {
        if (!empty($grouped[$group_key])) {
            $ordered[$group_key] = $grouped[$group_key];
        }
    }
    foreach ($grouped as $group_key => $items) {
        if (!isset($ordered[$group_key])) {
            $ordered[$group_key] = $items;
        }
    }
    return $ordered;
}

public static function get_icon_svg_by_key(string $icon_key): string {
    if (class_exists(__NAMESPACE__ . '\MC_Icon_Library')) {
        $custom = MC_Icon_Library::get_custom_icon($icon_key);
        if (is_array($custom) && !empty($custom['file'])) {
            $path = MC_Icon_Library::get_custom_icon_path((string) $custom['file']);
            if (is_readable($path)) {
                $svg = (string) file_get_contents($path);
                if ($svg !== '') {
                    return $svg;
                }
            }
        }
    }
    if (!function_exists('metra_lucide_render')) {
        return '';
    }
    return (string) \metra_lucide_render($icon_key);
}

    /**
     * Curated Lucide icon list for marketplace main categories.
     *
     * Keys intentionally stay compatible with existing storage.
     *
     * @return array<string,string>
     */
    public static function fa_choices(): array {
        $choices = [];
        foreach (self::get_icon_registry() as $key => $icon) {
            $choices[$key] = isset($icon['label']) ? (string) $icon['label'] : $key;
        }
        return $choices;
    }

    /**
     * Automatic category mapping.
     */
    public static function get_auto_fa_icon_class($term): string {
        if (!$term instanceof \WP_Term) {
            return 'map-marker';
        }

        $slug = sanitize_key((string) $term->slug);
        $name = strtolower(sanitize_text_field((string) $term->name));
        $haystack = trim($slug . ' ' . $name);

        $exact = [
            'cars-and-trailers' => 'car',
            'cars-bikes-boats' => 'car',
            'photography' => 'camera',
            'electronics' => 'gamepad',
            'fashion-beauty' => 'shopping-bag',
            'pets' => 'paw',
            'jobs' => 'briefcase',
            'event-tickets' => 'ticket',
            'music-movies-books' => 'book',
            'free-swap' => 'gift',
            'home-garden' => 'home',
            'consumer-electronics' => 'desktop',
        ];
        if (isset($exact[$slug])) {
            return $exact[$slug];
        }

        $map = [
            'car' => ['auto', 'car', 'cars', 'vehicle', 'vehicles', 'bike', 'boat', 'trailer'],
            'camera' => ['photo', 'photography', 'camera', 'video', 'film'],
            'gamepad' => ['electronics', 'gaming', 'console', 'game'],
            'shopping-bag' => ['fashion', 'beauty', 'shopping', 'bag', 'clothing', 'shoes'],
            'paw' => ['pet', 'pets', 'animal', 'animals', 'dog', 'cat', 'horse'],
            'briefcase' => ['job', 'jobs', 'career', 'business', 'office', 'service'],
            'ticket' => ['ticket', 'tickets', 'event', 'events', 'concert', 'sports'],
            'book' => ['book', 'books', 'magazine', 'comic', 'media', 'education'],
            'gift' => ['gift', 'free', 'swap'],
            'home' => ['home', 'house', 'real-estate', 'property', 'apartment', 'garden'],
            'wrench' => ['repair', 'tool', 'tools', 'diy', 'industrial'],
            'music' => ['music'],
        ];

        foreach ($map as $icon => $keywords) {
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($haystack, $keyword)) {
                    return $icon;
                }
            }
        }

        return 'map-marker';
    }

    /**
     * Manual override if present, otherwise automatic.
     */
    public static function get_fa_icon_class($term): string {
        if (!$term instanceof \WP_Term) {
            return 'map-marker';
        }

        $manual = (string) get_term_meta((int) $term->term_id, self::META_FA_ICON, true);
        if ($manual !== '' && array_key_exists($manual, self::fa_choices())) {
            return sanitize_text_field($manual);
        }

        return self::get_auto_fa_icon_class($term);
    }

    public static function get_fa_icon_color($term): string {
        $design = get_option('mc_design', []);
        $global = '';
        if (is_array($design) && isset($design['fa_icon_color']) && is_string($design['fa_icon_color'])) {
            $global = (string) sanitize_hex_color($design['fa_icon_color']);
        }
        if ($global === '') {
            $global = '#297bc1';
        }

        if (!$term instanceof \WP_Term) {
            return $global;
        }
        $value = (string) get_term_meta((int) $term->term_id, self::META_FA_COLOR, true);
        $safe = sanitize_hex_color($value);
        return is_string($safe) && $safe !== '' ? $safe : $global;
    }

    public static function get_fa_icon_size($term): int {
        $design = get_option('mc_design', []);
        $global = is_array($design) && isset($design['fa_icon_size']) ? (int) $design['fa_icon_size'] : 20;
        if ($global < 12 || $global > 48) {
            $global = 20;
        }

        if (!$term instanceof \WP_Term) {
            return $global;
        }
        $raw = get_term_meta((int) $term->term_id, self::META_FA_SIZE, true);
        if ($raw === '' || $raw === null) {
            return $global;
        }
        $size = (int) $raw;
        if ($size < 12 || $size > 48) {
            return $global;
        }
        return $size;
    }

    private static function get_lucide_svg(string $icon_key): string {
        return self::get_icon_svg_by_key($icon_key);
    }

    /**
     * Render a Lucide icon for main categories.
     * Public method name stays unchanged for compatibility.
     */
    public static function render_fa_category_icon($term, array $args = []): string {
        if (!self::are_fa_icons_enabled()) {
            return '';
        }

        $icon_key = self::get_fa_icon_class($term);
        $svg = self::get_lucide_svg($icon_key);
        if ($svg === '') {
            return '';
        }

        $label = isset($args['label']) ? (string) $args['label'] : '';
        $extra_class = isset($args['class']) ? trim((string) $args['class']) : 'mc-maincat-lucide-icon';
        $size = isset($args['size']) ? max(12, (int) $args['size']) : self::get_fa_icon_size($term);
        $color = self::get_fa_icon_color($term);

        $aria = $label === ''
            ? ' aria-hidden="true" focusable="false"'
            : ' role="img" aria-label="' . esc_attr($label) . '" focusable="false"';

        $style = 'width:' . $size . 'px;height:' . $size . 'px;';
        if ($color !== '') {
            $style .= 'color:' . $color . ';';
        }

        $replacement = '<svg class="' . esc_attr(trim($extra_class . ' mc-lucide-icon')) . '" width="' . $size . '" height="' . $size . '" style="' . esc_attr($style) . '"' . $aria;
        $svg = preg_replace('/<svg\b/', $replacement, $svg, 1);

        return is_string($svg) ? $svg : '';
    }

    public static function render_mobile_category_list_icon($term, array $args = []): string {
        $defaults = [
            'class' => 'mc-catwiz-term-icon',
            'size'  => 20,
        ];

        return self::render_fa_category_icon($term, array_merge($defaults, $args));
    }


    // ============================================================
    // Feature: Visual Icon Picker (v0.0600)
    // Purpose: Replace old grouped-button picker with a fast
    //   searchable visual grid. Lucide (1694) + Custom icons.
    //   JS renders pages of 80 icons at a time — DOM stays lean.
    //   Custom icons shown in separate tab.
    // Rollback: restore previous render_picker_markup,
    //   render_admin_styles, render_admin_scripts methods.
    // ============================================================
    private static function render_picker_markup(array $values = []): string {
        $selected_icon  = isset($values['icon'])  ? (string) $values['icon']  : '';
        $selected_color = isset($values['color']) ? (string) $values['color'] : '';
        $size_has_override = isset($values['size']) && (string) $values['size'] !== '';
        $selected_size  = $size_has_override ? max(12, min(96, (int) $values['size'])) : self::get_fa_icon_size(null);

        // Lucide icons (no 'file' key)
        $all_registry = self::get_icon_registry();
        $lucide_data  = [];
        foreach ($all_registry as $key => $icon) {
            if (!isset($icon['file'])) {
                $lucide_data[] = [$key, isset($icon['label']) ? $icon['label'] : $key];
            }
        }

        // Custom uploaded icons
        $custom_icons = class_exists(__NAMESPACE__ . '\\MC_Icon_Library') ? MC_Icon_Library::get_custom_icons() : [];
        $custom_data  = [];
        foreach ($custom_icons as $key => $icon) {
            $custom_data[] = [$key, isset($icon['label']) ? $icon['label'] : $key];
        }

        // Selected icon preview SVG
        $preview_svg = '';
        if ($selected_icon !== '') {
            $svg = self::get_icon_svg_by_key($selected_icon);
            if ($svg !== '') {
                $preview_svg = preg_replace('/<svg\b/', '<svg width="28" height="28" aria-hidden="true" style="stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;"', $svg, 1);
            }
        }

        $display_color = $selected_color !== '' ? $selected_color : '#297bc1';

        $html  = '<div class="mc-ipkr" id="mc-ipkr">';
        $html .= '<input type="hidden" name="mc_fa_icon" id="mc_fa_icon" value="' . esc_attr($selected_icon) . '" />';

        // Preview bar
        $none_label = esc_html__('None (automatic)', 'metra-classifieds-plugin');
        $html .= '<div class="mc-ipkr-bar">';
        $html .= '<span class="mc-ipkr-bar__preview" id="mc-ipkr-preview">' . ($preview_svg ?: '<span class="mc-ipkr-bar__empty">—</span>') . '</span>';
        $html .= '<span class="mc-ipkr-bar__name" id="mc-ipkr-name">' . esc_html($selected_icon !== '' ? $selected_icon : __('None (automatic)', 'metra-classifieds-plugin')) . '</span>';
        $html .= '<button type="button" class="button mc-ipkr-bar__clear" id="mc-ipkr-clear" title="' . esc_attr__('Remove icon', 'metra-classifieds-plugin') . '">✕ ' . esc_html__('Clear', 'metra-classifieds-plugin') . '</button>';
        $html .= '</div>';

        // Tabs
        $html .= '<div class="mc-ipkr-tabs" role="tablist">';
        $html .= '<button type="button" class="mc-ipkr-tab is-active" data-tab="lucide" role="tab">Lucide <span class="mc-ipkr-tab__count">' . count($lucide_data) . '</span></button>';
        $html .= '<button type="button" class="mc-ipkr-tab" data-tab="custom" role="tab">Custom <span class="mc-ipkr-tab__count">' . count($custom_data) . '</span></button>';
        $html .= '</div>';

        // Category filter bar
        $html .= '<div class="mc-ipkr-catbar" id="mc-ipkr-catbar">';
        $html .= '<button type="button" class="mc-ipkr-cat is-active" data-cat="">All</button>';
        if (function_exists('metra_lucide_categories')) {
            foreach (metra_lucide_categories() as $key => $cat) {
                $html .= '<button type="button" class="mc-ipkr-cat" data-cat="' . esc_attr($key) . '">' . esc_html($cat['label']) . '</button>';
            }
        }
        $html .= '</div>';

        // Search
        $html .= '<div class="mc-ipkr-searchrow">';
        $html .= '<input type="text" class="mc-ipkr-search" id="mc-ipkr-search" placeholder="' . esc_attr__('Search icons…', 'metra-classifieds-plugin') . '" autocomplete="off" spellcheck="false" />';
        $html .= '<span class="mc-ipkr-search__count" id="mc-ipkr-count"></span>';
        $html .= '</div>';

        // Grid + pagination (JS fills this)
        $html .= '<div class="mc-ipkr-grid-wrap">';
        $html .= '<div class="mc-ipkr-grid" id="mc-ipkr-grid" role="listbox"></div>';
        $html .= '<div class="mc-ipkr-pager" id="mc-ipkr-pager"></div>';
        $html .= '</div>';

        // Controls: color + size
        $html .= '<div class="mc-ipkr-controls">';

        $html .= '<div class="mc-ipkr-ctrl-row">';
        $html .= '<label class="mc-ipkr-ctrl-label"><strong>' . esc_html__('Color', 'metra-classifieds-plugin') . '</strong></label>';
        $html .= '<input type="color" name="mc_fa_color" id="mc_fa_color" value="' . esc_attr($display_color) . '" class="mc-ipkr-color" />';
        $html .= '<code class="mc-ipkr-color-hex" id="mc-ipkr-color-hex">' . esc_html($display_color) . '</code>';
        $html .= '<label class="mc-ipkr-ctrl-check"><input type="checkbox" name="mc_fa_color_enabled" value="1"' . checked($selected_color !== '', true, false) . ' id="mc_fa_color_enabled" /> ' . esc_html__('Use custom color', 'metra-classifieds-plugin') . '</label>';
        $html .= '</div>';

        $html .= '<div class="mc-ipkr-ctrl-row">';
        $html .= '<label class="mc-ipkr-ctrl-label"><strong>' . esc_html__('Size (px)', 'metra-classifieds-plugin') . '</strong></label>';
        $html .= '<input type="number" min="12" max="96" step="1" name="mc_fa_size" id="mc_fa_size" value="' . esc_attr((string) $selected_size) . '" class="mc-ipkr-size" />';
        $html .= '<label class="mc-ipkr-ctrl-check"><input type="checkbox" name="mc_fa_size_enabled" value="1"' . checked($size_has_override, true, false) . ' /> ' . esc_html__('Use custom size', 'metra-classifieds-plugin') . '</label>';
        $html .= '<span class="description mc-ipkr-ctrl-hint">' . esc_html__('Without custom size, global Design setting is used.', 'metra-classifieds-plugin') . '</span>';
        $html .= '</div>';

        $html .= '</div>'; // .mc-ipkr-controls
        $html .= '</div>'; // .mc-ipkr

        // Pass data to JS inline
        $html .= '<script>';
        $html .= 'window.mcIpkrLucide=' . wp_json_encode(array_values($lucide_data)) . ';';
        $html .= 'window.mcIpkrCustom='  . wp_json_encode(array_values($custom_data))  . ';';
        $html .= 'window.mcIpkrSelected=' . wp_json_encode($selected_icon) . ';';
        $html .= 'window.mcIpkrPluginUrl=' . wp_json_encode(plugins_url('', dirname(__FILE__) . '/../metra-classifieds.php')) . ';';
        // Category map for the category filter bar
        $cats = function_exists('metra_lucide_categories') ? metra_lucide_categories() : [];
        $cat_data = [];
        foreach ($cats as $key => $cat) {
            $cat_data[$key] = ['label' => $cat['label'], 'icons' => $cat['icons']];
        }
        $html .= 'window.mcIpkrCategories=' . wp_json_encode($cat_data) . ';';
        $html .= '</script>';
        // v0.0605: JS directly after data — guarantees mcIpkrLucide is set
        $html .= '<script>';
        $html .= <<<'MCIPKRJS'
(function(){
    var PER_PAGE = 80;
    var lucideIcons = window.mcIpkrLucide || [];
    var customIcons = window.mcIpkrCustom  || [];
    var pluginUrl   = window.mcIpkrPluginUrl || '';

    var input   = document.getElementById('mc_fa_icon');
    var preview = document.getElementById('mc-ipkr-preview');
    var nameEl  = document.getElementById('mc-ipkr-name');
    var clearBtn= document.getElementById('mc-ipkr-clear');
    var searchEl= document.getElementById('mc-ipkr-search');
    var countEl = document.getElementById('mc-ipkr-count');
    var grid    = document.getElementById('mc-ipkr-grid');
    var pager   = document.getElementById('mc-ipkr-pager');
    var colorEl = document.getElementById('mc_fa_color');
    var hexEl   = document.getElementById('mc-ipkr-color-hex');

    if (!grid) return;

    var currentTab  = 'lucide';
    var currentPage = 0;
    var filtered    = [];
    var allIcons    = lucideIcons;
    var svgCache    = {};
    var currentCat  = '';
    var categories  = window.mcIpkrCategories || {};

    // tabs
    document.querySelectorAll('.mc-ipkr-tab').forEach(function(tab){
        tab.addEventListener('click', function(){
            document.querySelectorAll('.mc-ipkr-tab').forEach(function(t){ t.classList.remove('is-active'); });
            tab.classList.add('is-active');
            currentTab = tab.getAttribute('data-tab');
            allIcons   = currentTab === 'custom' ? customIcons : lucideIcons;
            if (searchEl) searchEl.value = '';
            currentPage = 0;
            applyFilter();
        });
    });

    // category filter
    document.querySelectorAll('.mc-ipkr-cat').forEach(function(btn){
        btn.addEventListener('click', function(){
            document.querySelectorAll('.mc-ipkr-cat').forEach(function(b){ b.classList.remove('is-active'); });
            btn.classList.add('is-active');
            currentCat = btn.getAttribute('data-cat') || '';
            if (searchEl) searchEl.value = '';
            currentPage = 0;
            applyFilter();
        });
    });

    // search
    if (searchEl) {
        searchEl.addEventListener('input', function(){
            currentPage = 0;
            applyFilter();
        });
    }

    function applyFilter(){
        var needle = searchEl ? searchEl.value.toLowerCase().trim() : '';
        var catSet = null;
        if (currentCat && categories[currentCat]) {
            catSet = {};
            categories[currentCat].icons.forEach(function(k){ catSet[k] = true; });
        }
        filtered = allIcons.filter(function(ic){
            var matchCat    = !catSet || catSet[ic[0]];
            var matchSearch = !needle || ic[0].indexOf(needle) !== -1 || ic[1].toLowerCase().indexOf(needle) !== -1;
            return matchCat && matchSearch;
        });
        if (countEl) countEl.textContent = filtered.length + ' icons';
        renderPage();
        renderPager();
    }

    function getSvg(key, cb){
        if (svgCache[key]) { cb(svgCache[key]); return; }
        fetch(pluginUrl + '/assets/lucide/' + key + '.svg')
            .then(function(r){ return r.text(); })
            .then(function(svg){ svgCache[key] = svg; cb(svg); })
            .catch(function(){ cb(''); });
    }

    function renderPage(){
        grid.innerHTML = '';
        var start = currentPage * PER_PAGE;
        var slice = filtered.slice(start, start + PER_PAGE);
        if (slice.length === 0) {
            grid.innerHTML = '<div class="mc-ipkr-empty">No icons found.</div>';
            return;
        }
        var currentVal = input ? input.value : '';
        slice.forEach(function(ic){
            var key = ic[0], label = ic[1];
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'button mc-ipkr-card' + (key === currentVal ? ' is-selected' : '');
            btn.title = key;

            var iconSpan = document.createElement('span');
            iconSpan.className = 'mc-ipkr-card__icon';

            var labelSpan = document.createElement('span');
            labelSpan.className = 'mc-ipkr-card__label';
            labelSpan.textContent = label;

            btn.appendChild(iconSpan);
            btn.appendChild(labelSpan);

            if (currentTab === 'lucide') {
                getSvg(key, function(svg){ iconSpan.innerHTML = svg; });
            } else {
                iconSpan.innerHTML = '<img src="' + pluginUrl + '/assets/user-icons/' + key + '" style="width:22px;height:22px;" />';
            }

            btn.addEventListener('click', function(){ selectIcon(key, label); });
            grid.appendChild(btn);
        });
    }

    function renderPager(){
        pager.innerHTML = '';
        var pages = Math.ceil(filtered.length / PER_PAGE);
        if (pages <= 1) return;

        function mkBtn(label, page, disabled){
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'button mc-ipkr-pager__btn' + (page === currentPage ? ' is-active' : '');
            b.textContent = label;
            b.disabled = disabled;
            b.addEventListener('click', function(){ currentPage = page; renderPage(); renderPager(); });
            return b;
        }

        pager.appendChild(mkBtn('‹', currentPage - 1, currentPage === 0));

        var info = document.createElement('span');
        info.className = 'mc-ipkr-pager__info';
        info.textContent = (currentPage + 1) + ' / ' + pages;
        pager.appendChild(info);

        pager.appendChild(mkBtn('›', currentPage + 1, currentPage >= pages - 1));
    }

    function selectIcon(key, label){
        if (input)  input.value = key;
        if (nameEl) nameEl.textContent = key;
        if (preview) {
            getSvg(key, function(svg){ preview.innerHTML = svg; });
        }
        grid.querySelectorAll('.mc-ipkr-card').forEach(function(c){
            c.classList.toggle('is-selected', c.title === key);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function(){
            if (input)   input.value = '';
            if (nameEl)  nameEl.textContent = 'None (automatic)';
            if (preview) preview.innerHTML = '<span class="mc-ipkr-bar__empty">—</span>';
            grid.querySelectorAll('.mc-ipkr-card').forEach(function(c){ c.classList.remove('is-selected'); });
        });
    }

    if (colorEl && hexEl) {
        colorEl.addEventListener('input', function(){ hexEl.textContent = colorEl.value; });
    }

    // init — scroll to selected page
    allIcons = lucideIcons;
    var currentVal = input ? input.value : '';
    if (currentVal) {
        var idx = -1;
        for (var i = 0; i < lucideIcons.length; i++) {
            if (lucideIcons[i][0] === currentVal) { idx = i; break; }
        }
        if (idx >= 0) currentPage = Math.floor(idx / PER_PAGE);
    }
    applyFilter();
})();
MCIPKRJS;
        $html .= '</script>';

        return $html;
    }


    public static function render_add_fields(): void {
        // v0.0601: Full picker only on edit page. On overview page (add-form)
        // loading 1694 icons breaks the layout. Show a plain hint instead.
        wp_nonce_field('mc_fa_icon_term_meta', 'mc_fa_icon_term_meta_nonce');
        echo '<div class="form-field term-group">';
        echo '<label>' . esc_html__('Main category icon', 'metra-classifieds-plugin') . '</label>';
        echo '<p class="description">' . esc_html__('Save the category first, then edit it to assign an icon.', 'metra-classifieds-plugin') . '</p>';
        echo '<input type="hidden" name="mc_fa_icon" value="" />';
        echo '</div>';
    }

    public static function render_edit_fields(\WP_Term $term): void {
        wp_nonce_field('mc_fa_icon_term_meta', 'mc_fa_icon_term_meta_nonce');
        $values = [
            'icon' => (string) get_term_meta((int) $term->term_id, self::META_FA_ICON, true),
            'color' => (string) get_term_meta((int) $term->term_id, self::META_FA_COLOR, true),
            'size' => get_term_meta((int) $term->term_id, self::META_FA_SIZE, true),
        ];

        echo '<tr class="form-field term-group-wrap"><th scope="row">' . esc_html__('Main category icon', 'metra-classifieds-plugin') . '</th><td>';
        echo self::render_picker_markup($values);
        echo '</td></tr>';
    }

    public static function save_term_fields(int $term_id): void {
        if (!current_user_can('manage_categories')) {
            return;
        }
        if (!isset($_POST['mc_fa_icon_term_meta_nonce']) || !wp_verify_nonce((string) $_POST['mc_fa_icon_term_meta_nonce'], 'mc_fa_icon_term_meta')) {
            return;
        }

        $icon = isset($_POST['mc_fa_icon']) ? sanitize_text_field((string) $_POST['mc_fa_icon']) : '';
        $color_enabled = isset($_POST['mc_fa_color_enabled']) && (string) $_POST['mc_fa_color_enabled'] === '1';
        $color = isset($_POST['mc_fa_color']) ? sanitize_hex_color((string) $_POST['mc_fa_color']) : '';
        $size_enabled = isset($_POST['mc_fa_size_enabled']) && (string) $_POST['mc_fa_size_enabled'] === '1';
        $size = isset($_POST['mc_fa_size']) ? max(12, min(48, (int) $_POST['mc_fa_size'])) : 20;

        if ($icon !== '' && array_key_exists($icon, self::get_icon_registry())) {
            update_term_meta($term_id, self::META_FA_ICON, $icon);
        } else {
            delete_term_meta($term_id, self::META_FA_ICON);
        }

        if ($color_enabled && is_string($color) && $color !== '') {
            update_term_meta($term_id, self::META_FA_COLOR, $color);
        } else {
            delete_term_meta($term_id, self::META_FA_COLOR);
        }

        if ($size_enabled) {
            update_term_meta($term_id, self::META_FA_SIZE, $size);
        } else {
            delete_term_meta($term_id, self::META_FA_SIZE);
        }
    }


    public static function render_admin_styles(): void {
        $screen_tax = isset($_GET['taxonomy']) ? sanitize_key((string) $_GET['taxonomy']) : '';
        if ($screen_tax !== MC_CPT::TAX) {
            return;
        }
        echo '<style>
        /* METRA Icon Picker v0.0600 */
        .mc-ipkr{ max-width:680px; font-size:13px; }

        /* Preview bar */
        .mc-ipkr-bar{
            display:flex; align-items:center; gap:10px;
            padding:10px 14px; margin-bottom:10px;
            background:#f6f7f7; border:1px solid #dcdcde;
            border-radius:8px;
        }
        .mc-ipkr-bar__preview{
            display:flex; align-items:center; justify-content:center;
            width:36px; height:36px; flex-shrink:0;
            color:#2271b1;
        }
        .mc-ipkr-bar__empty{ color:#aaa; font-size:18px; }
        .mc-ipkr-bar__name{ flex:1; font-weight:600; color:#1d2327; font-size:13px; }
        .mc-ipkr-bar__clear{ flex-shrink:0; }

        /* Tabs */
        .mc-ipkr-tabs{ display:flex; gap:0; margin-bottom:0; border-bottom:2px solid #dcdcde; }
        .mc-ipkr-tab{
            padding:7px 16px; border:1px solid transparent;
            border-bottom:none; border-radius:4px 4px 0 0;
            background:none; cursor:pointer; font-size:13px;
            color:#646970; margin-bottom:-2px;
        }
        .mc-ipkr-tab.is-active{
            background:#fff; border-color:#dcdcde;
            border-bottom-color:#fff; color:#1d2327; font-weight:600;
        }
        .mc-ipkr-tab__count{
            display:inline-block; background:#dcdcde;
            border-radius:10px; padding:0 6px;
            font-size:11px; font-weight:600; margin-left:4px;
        }
        .mc-ipkr-tab.is-active .mc-ipkr-tab__count{ background:#2271b1; color:#fff; }

        /* Category filter bar */
        .mc-ipkr-catbar{
            display:flex; flex-wrap:wrap; gap:4px;
            padding:8px 10px; background:#f6f7f7;
            border:1px solid #dcdcde; border-top:none;
            max-height:96px; overflow-y:auto;
        }
        .mc-ipkr-cat{
            padding:3px 10px; border:1px solid #dcdcde;
            border-radius:12px; background:#fff;
            font-size:11px; cursor:pointer; white-space:nowrap;
            color:#1d2327;
        }
        .mc-ipkr-cat:hover{ border-color:#2271b1; color:#2271b1; }
        .mc-ipkr-cat.is-active{
            background:#2271b1; color:#fff;
            border-color:#2271b1; font-weight:600;
        }

        /* Search */
        .mc-ipkr-searchrow{
            display:flex; align-items:center; gap:10px;
            padding:10px; background:#fff;
            border:1px solid #dcdcde; border-top:none;
        }
        .mc-ipkr-search{ flex:1; margin:0 !important; }
        .mc-ipkr-search__count{ font-size:12px; color:#646970; white-space:nowrap; min-width:70px; text-align:right; }

        /* Grid */
        .mc-ipkr-grid-wrap{
            border:1px solid #dcdcde; border-top:none;
            background:#fff; min-height:260px;
        }
        .mc-ipkr-grid{
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(80px,1fr));
            gap:6px; padding:10px;
        }
        .mc-ipkr-card{
            display:flex !important; flex-direction:column;
            align-items:center; justify-content:center;
            gap:5px; padding:8px 4px !important;
            border:1px solid #dcdcde !important;
            border-radius:8px; background:#fff;
            cursor:pointer; text-align:center;
            min-height:72px; transition:border-color .1s, background .1s;
        }
        .mc-ipkr-card:hover{ border-color:#2271b1 !important; background:#f0f6fc; }
        .mc-ipkr-card.is-selected{
            border-color:#2271b1 !important;
            box-shadow:0 0 0 2px #2271b1 inset;
            background:#f0f6fc;
        }
        .mc-ipkr-card__icon{
            width:24px; height:24px; display:flex;
            align-items:center; justify-content:center;
            color:#2271b1; flex-shrink:0;
        }
        .mc-ipkr-card__icon svg{
            width:22px; height:22px;
            stroke:currentColor; fill:none;
            stroke-width:2; stroke-linecap:round; stroke-linejoin:round;
        }
        .mc-ipkr-card__icon img{ width:22px; height:22px; object-fit:contain; }
        .mc-ipkr-card__label{
            font-size:10px; line-height:1.2; color:#1d2327;
            word-break:break-word; max-width:100%;
            overflow:hidden; display:-webkit-box;
            -webkit-line-clamp:2; -webkit-box-orient:vertical;
        }
        .mc-ipkr-empty{
            grid-column:1/-1; text-align:center;
            padding:40px 20px; color:#646970;
        }

        /* Pagination */
        .mc-ipkr-pager{
            display:flex; align-items:center; justify-content:center;
            gap:4px; padding:8px 10px;
            border-top:1px solid #f0f0f1;
        }
        .mc-ipkr-pager__btn{
            padding:4px 10px; border:1px solid #dcdcde;
            border-radius:4px; background:#fff; cursor:pointer;
            font-size:12px; color:#2271b1;
        }
        .mc-ipkr-pager__btn:hover{ background:#f0f6fc; }
        .mc-ipkr-pager__btn.is-active{
            background:#2271b1; color:#fff; border-color:#2271b1; font-weight:700;
        }
        .mc-ipkr-pager__info{ font-size:12px; color:#646970; padding:0 6px; }

        /* Controls */
        .mc-ipkr-controls{
            margin-top:14px; padding:12px 14px;
            background:#f6f7f7; border:1px solid #dcdcde;
            border-radius:8px; display:flex; flex-direction:column; gap:10px;
        }
        .mc-ipkr-ctrl-row{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .mc-ipkr-ctrl-label{ min-width:80px; }
        .mc-ipkr-color{ width:48px; height:36px; padding:2px; border-radius:4px; cursor:pointer; }
        .mc-ipkr-color-hex{ font-size:12px; color:#646970; }
        .mc-ipkr-size{ width:70px !important; }
        .mc-ipkr-ctrl-check{ display:flex; align-items:center; gap:4px; }
        .mc-ipkr-ctrl-hint{ font-size:11px; color:#646970; }

        .mc-lucide-icon{
            display:inline-block; vertical-align:middle;
            stroke:currentColor; fill:none;
            stroke-width:2; stroke-linecap:round; stroke-linejoin:round;
        }
        </style>';
    }

    public static function render_admin_scripts(): void {
        // v0.0605: JS moved into render_picker_markup — runs directly after data script.
    }
}

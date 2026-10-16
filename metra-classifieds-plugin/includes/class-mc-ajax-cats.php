<?php
declare(strict_types=1);

namespace MetraClassifieds;

/**
 * AJAX handler for mobile category sheet drilldown.
 *
 * Version: 0.0652
 * Purpose: Returns the HTML for a category level so the bottom
 *          sheet can navigate without a full page reload.
 * Rollback: remove add_action calls in metra-classifieds.php
 *           and delete this file.
 */
class MC_Ajax_Cats {

    public static function handle(): void {
        // Nonce check
        check_ajax_referer('mc_cats_drilldown', 'nonce');

        $parent = isset($_GET['parent']) ? absint($_GET['parent']) : 0;

        // Build the list HTML for this level
        $terms = get_terms([
            'taxonomy'   => MC_CPT::TAX,
            'hide_empty' => false,
            'parent'     => $parent,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        if (is_wp_error($terms) || empty($terms)) {
            wp_send_json_success(['html' => '', 'section' => '', 'back' => '']);
            return;
        }

        // Section label
        $section = $parent === 0
            ? esc_html__('All categories', 'metra-classifieds-plugin')
            : esc_html(get_term($parent, MC_CPT::TAX)->name ?? '');

        // Back info
        $back_parent = 0;
        if ($parent > 0) {
            $parent_term = get_term($parent, MC_CPT::TAX);
            if ($parent_term && !is_wp_error($parent_term)) {
                $back_parent = (int) $parent_term->parent;
            }
        }

        // Build list items
        $items_html = '';
        foreach ($terms as $term) {
            if (!$term instanceof \WP_Term) continue;

            $child_ids = get_terms([
                'taxonomy'   => MC_CPT::TAX,
                'hide_empty' => false,
                'parent'     => (int) $term->term_id,
                'fields'     => 'ids',
                'number'     => 1,
            ]);
            $has_children = (!is_wp_error($child_ids) && !empty($child_ids));

            if ($has_children) {
                // Non-leaf: drilldown via AJAX
                $href        = 'javascript:void(0)';
                $data_drill  = ' data-mc-drill="' . (int) $term->term_id . '"';
                $data_label  = ' data-mc-label="' . esc_attr((string) $term->name) . '"';
            } else {
                // Leaf: navigate to listings
                $cat_base   = MC_Pages::get_page_url('category_listings');
                $href       = esc_url(add_query_arg(['cat' => (string) $term->term_id], $cat_base ?: home_url('/')));
                $data_drill = '';
                $data_label = '';
            }

            // Icon
            $icon_html = '';
            $icon_svg  = MC_Icons::render_mobile_category_list_icon($term, ['label' => (string) $term->name]);
            if ($icon_svg !== '') {
                $icon_html = '<span class="mc-catwiz-iconbox" aria-hidden="true">' . $icon_svg . '</span>';
            }

            $chev_svg = '<svg class="mc-catwiz-chevron-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';

            $items_html .= '<a class="mc-catwiz-item" href="' . $href . '"' . $data_drill . $data_label . '>'
                . $icon_html
                . '<span class="mc-catwiz-name">' . esc_html((string) $term->name) . '</span>'
                . '<span class="mc-catwiz-chevron" aria-hidden="true">' . $chev_svg . '</span>'
                . '</a>';
        }

        wp_send_json_success([
            'html'        => $items_html,
            'section'     => $section,
            'back_parent' => $back_parent,
            'is_root'     => ($parent === 0),
        ]);
    }
}

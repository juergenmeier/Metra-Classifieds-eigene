<?php

declare(strict_types=1);

namespace MetraClassifieds;

use WP_Widget;

/**
 * Widget: METRA Categories (Sidebar Tree)
 *
 * Renders the mc_category taxonomy as a hierarchical tree.
 * - Unlimited depth
 * - Depth 1 is open by default
 * - Uses <details>/<summary> for accordion behaviour (no JS required)
 *
 * Global defaults are controlled via Settings -> METRA Classifieds -> Basic.
 */
final class MC_Categories_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'mc_categories_widget',
            __('METRA Categories (Sidebar Tree)', 'metra-classifieds-plugin'),
            [
                'description' => __('Category sidebar block (parent + children + More).', 'metra-classifieds-plugin'),
            ]
        );
    }

    public function widget($args, $instance): void {
        echo $args['before_widget'] ?? '';

        $title = !empty($instance['title']) ? (string) $instance['title'] : __('Categories', 'metra-classifieds-plugin');
        if (!empty($title)) {
            echo ($args['before_title'] ?? '<h2>') . esc_html($title) . ($args['after_title'] ?? '</h2>');
        }

        $show_count = (int) get_option('mc_categories_widget_show_count', 1) === 1;
        $only_with  = (int) get_option('mc_categories_widget_only_with_listings', 0) === 1;
        $root_id    = (int) get_option('mc_categories_widget_root_term', 0);

        $tree = $this->build_tree($root_id, $only_with);
        if (empty($tree)) {
            echo '<p>' . esc_html__('No categories found.', 'metra-classifieds-plugin') . '</p>';
            echo $args['after_widget'] ?? '';
            return;
        }

        // Mirror the listings sidebar layout: show parent headline + a few children + "More ...".
        $child_limit = 3;
        $listings_url = MC_Pages::get_page_url('listings') ?: home_url('/');

        echo '<div class="mc-categories-widget mc-categories-widget--sidebar">';
        echo '<aside class="mc-home-sidebar" aria-label="' . esc_attr__('Categories', 'metra-classifieds-plugin') . '">';
        echo '<div class="mc-home-sidebar-head">'
            . '<div class="mc-home-title">' . esc_html__('Categories', 'metra-classifieds-plugin') . '</div>'
            . '<a class="mc-home-sidebar-all" href="' . esc_url($listings_url) . '">' . esc_html__('View all', 'metra-classifieds-plugin') . '</a>'
            . '</div>';

        foreach ($tree as $parent) {
            $parent_name = (string) ($parent['name'] ?? '');
            $parent_url  = (string) ($parent['url'] ?? '#');
            $parent_cnt  = (int) ($parent['count'] ?? 0);

            $parent_label = $parent_name;
            if ($show_count) {
                $parent_label .= ' (' . $parent_cnt . ')';
            }

            echo '<div class="mc-home-cat-group">';
            echo '<a class="mc-home-cat-parent" href="' . esc_url($parent_url) . '">' . esc_html($parent_label) . '</a>';

            $children = $parent['children'] ?? [];
            if (!empty($children)) {
                echo '<div class="mc-home-cat-children">';
                $shown = 0;
                foreach ($children as $child) {
                    if ($shown >= $child_limit) {
                        break;
                    }
                    $child_name = (string) ($child['name'] ?? '');
                    $child_url  = (string) ($child['url'] ?? '#');
                    $child_cnt  = (int) ($child['count'] ?? 0);

                    $child_label = $child_name;
                    if ($show_count) {
                        $child_label .= ' (' . $child_cnt . ')';
                    }

                    echo '<a class="mc-home-cat-child" href="' . esc_url($child_url) . '">' . esc_html($child_label) . '</a>';
                    $shown++;
                }

                if (count($children) > $shown) {
                    echo '<a class="mc-home-cat-more" href="' . esc_url($parent_url) . '">' . esc_html__('More ...', 'metra-classifieds-plugin') . '</a>';
                }

                echo '</div>';
            }

            echo '</div>';
        }

        echo '</aside>';
        echo '</div>';

        echo $args['after_widget'] ?? '';
    }

    public function form($instance): void {
        $title = isset($instance['title']) ? (string) $instance['title'] : __('Categories', 'metra-classifieds-plugin');
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'metra-classifieds-plugin'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p class="description">
            <?php esc_html_e('Display options (counters, filtering, root) are configured globally in METRA Classifieds -> Settings -> Basic.', 'metra-classifieds-plugin'); ?>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance): array {
        $instance = [];
        $instance['title'] = isset($new_instance['title']) ? sanitize_text_field((string) $new_instance['title']) : '';
        return $instance;
    }

    /**
     * Build full tree (optionally filtered by listings).
     * Returns an array of nodes.
     *
     * Node structure:
     * - term_id
     * - name
     * - url
     * - count (aggregated)
     * - children (array)
     */
    private function build_tree(int $root_term_id, bool $only_with_listings): array {
        $taxonomy = 'mc_category';

        $args = [
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ];

        $all = get_terms($args);
        if (is_wp_error($all) || empty($all)) {
            return [];
        }

        // Index terms by id + parent.
        $by_id = [];
        $children_map = [];
        foreach ($all as $t) {
            $by_id[(int) $t->term_id] = $t;
            $p = (int) $t->parent;
            if (!isset($children_map[$p])) {
                $children_map[$p] = [];
            }
            $children_map[$p][] = (int) $t->term_id;
        }

        $root_parent = 0;
        if ($root_term_id > 0) {
            if (!isset($by_id[$root_term_id])) {
                return [];
            }
            $root_parent = $root_term_id;
        }

        $build = function (int $parent_id) use (&$build, $children_map, $by_id, $taxonomy): array {
            $out = [];
            $child_ids = $children_map[$parent_id] ?? [];
            foreach ($child_ids as $cid) {
                if (!isset($by_id[$cid])) {
                    continue;
                }
                $term = $by_id[$cid];
                $node = [
                    'term_id'  => (int) $term->term_id,
                    'name'     => (string) $term->name,
                    'url'      => add_query_arg(['cat' => (string) $term->term_id], (MC_Pages::get_page_url('category_listings') ?: home_url('/'))),
                    'count'    => $this->get_effective_public_listing_count((int) $term->term_id, $children_map, $taxonomy),
                    'children' => $build((int) $term->term_id),
                ];

                $out[] = $node;
            }
            return $out;
        };

        $tree = $build($root_parent);

        if ($only_with_listings) {
            $filter = function (array $nodes) use (&$filter): array {
                $out = [];
                foreach ($nodes as $n) {
                    $n['children'] = $filter($n['children'] ?? []);
                    $has_children = !empty($n['children']);
                    $has_listings = ((int) ($n['count'] ?? 0)) > 0;
                    if ($has_listings || $has_children) {
                        $out[] = $n;
                    }
                }
                return $out;
            };
            $tree = $filter($tree);
        }

        return $tree;
    }


    /**
     * Direct published, non-paused listing count for one category term.
     */
    private function get_direct_public_listing_count(int $term_id, string $taxonomy): int {
        if ($term_id <= 0) {
            return 0;
        }

        $q = new \WP_Query([
            'post_type'      => \MetraClassifieds\MC_CPT::CPT,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => false,
            'tax_query'      => [[
                'taxonomy'         => $taxonomy,
                'field'            => 'term_id',
                'terms'            => [$term_id],
                'include_children' => false,
            ]],
            'meta_query'     => [[
                'relation' => 'OR',
                [
                    'key'     => '_mc_paused',
                    'compare' => 'NOT EXISTS',
                ],
                [
                    'key'     => '_mc_paused',
                    'value'   => '1',
                    'compare' => '!=',
                ],
            ]],
        ]);

        $count = (int) $q->found_posts;
        wp_reset_postdata();

        return $count;
    }

    /**
     * Effective display count:
     * - parent with children => descendant sum only
     * - leaf => direct public listing count
     */
    private function get_effective_public_listing_count(int $term_id, array $children_map, string $taxonomy): int {
        static $cache = [];

        $cache_key = $taxonomy . ':' . $term_id;
        if (isset($cache[$cache_key])) {
            return (int) $cache[$cache_key];
        }

        $child_ids = $children_map[$term_id] ?? [];
        if (empty($child_ids)) {
            $cache[$cache_key] = $this->get_direct_public_listing_count($term_id, $taxonomy);
            return (int) $cache[$cache_key];
        }

        $sum = 0;
        foreach ($child_ids as $cid) {
            $sum += $this->get_effective_public_listing_count((int) $cid, $children_map, $taxonomy);
        }

        $cache[$cache_key] = $sum;
        return $sum;
    }

    private function render_node(array $node, int $depth, bool $show_count): void {
        $name  = (string) ($node['name'] ?? '');
        $url   = (string) ($node['url'] ?? '#');
        $count = (int) ($node['count'] ?? 0);
        $children = $node['children'] ?? [];

        $label = $name;
        if ($show_count) {
            $label .= ' (' . $count . ')';
        }

        echo '<li class="mc-categories-tree__item" role="treeitem">';

        if (!empty($children)) {
            $open = ($depth === 1) ? ' open' : '';
            echo '<details class="mc-categories-tree__details"' . $open . '>';
            echo '<summary class="mc-categories-tree__summary">';
            echo '<a class="mc-categories-tree__link" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
            echo '</summary>';
            echo '<ul class="mc-categories-tree__children" role="group">';
            foreach ($children as $ch) {
                $this->render_node($ch, $depth + 1, $show_count);
            }
            echo '</ul>';
            echo '</details>';
        } else {
            echo '<a class="mc-categories-tree__link" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        }

        echo '</li>';
    }
}

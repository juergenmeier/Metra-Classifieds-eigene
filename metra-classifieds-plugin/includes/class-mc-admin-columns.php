<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin columns for Listings (CPT).
 *
 * Adds a column that shows the total number of listings per author.
 * Implemented via core WP admin hooks (no template changes).
 */
final class MC_Admin_Columns {

    /**
     * Cached counts per author for the current request.
     *
     * @var array<int,int>|null
     */
    private static ?array $author_counts = null;

    public static function init(): void {
        add_filter('manage_' . MC_CPT::CPT . '_posts_columns', [self::class, 'add_columns'], 20);
        add_action('manage_' . MC_CPT::CPT . '_posts_custom_column', [self::class, 'render_column'], 20, 2);
        add_action('admin_head', [self::class, 'render_admin_column_css']);
        add_action('admin_footer-edit.php', [self::class, 'render_title_link_override_js']);
        add_action('admin_post_mc_quick_approve_listing', [self::class, 'handle_quick_approve']);
    }

    /**
     * @param array<string,string> $columns
     * @return array<string,string>
     */
    public static function add_columns(array $columns): array {
        $out = [];

        if (isset($columns['cb'])) {
            $out['cb'] = $columns['cb'];
        }

        $out['author'] = $columns['author'] ?? __('Author', 'metra-classifieds-plugin');
        $out['mc_moderation'] = __('Mod.', 'metra-classifieds-plugin');
        $out['title'] = $columns['title'] ?? __('Title', 'metra-classifieds-plugin');
        $out['mc_author_count'] = __('Listings', 'metra-classifieds-plugin');

        foreach ($columns as $key => $label) {
            if (in_array($key, ['cb', 'author', 'title', 'taxonomy-mc_condition', 'mc_author_count', 'date'], true)) {
                continue;
            }
            $out[$key] = $label;
        }

        $out['mc_date'] = __('Date', 'metra-classifieds-plugin');

        return $out;
    }

    /**
     * @param string $column
     * @param int    $post_id
     */
    public static function render_column(string $column, int $post_id): void {
        if ($column === 'mc_moderation') {
            self::render_moderation_column($post_id);
            return;
        }

        if ($column === 'mc_date') {
            self::render_date_column($post_id);
            return;
        }

        if ($column !== 'mc_author_count') {
            return;
        }

        $author_id = (int) get_post_field('post_author', $post_id);
        if ($author_id <= 0) {
            echo '0';
            return;
        }

        $counts = self::get_author_counts();
        $count  = isset($counts[$author_id]) ? (int) $counts[$author_id] : 0;

        $url = add_query_arg([
            'post_type' => MC_CPT::CPT,
            'author'    => $author_id,
        ], admin_url('edit.php'));

        echo '<a href="' . esc_url($url) . '">' . esc_html((string) $count) . '</a>';
    }

    private static function render_moderation_column(int $post_id): void {
        $status = (string) get_post_status($post_id);
        if ($status !== 'pending') {
            echo '&mdash;';
            return;
        }

        $url = wp_nonce_url(
            add_query_arg([
                'action'  => 'mc_quick_approve_listing',
                'post_id' => $post_id,
            ], admin_url('admin-post.php')),
            'mc_quick_approve_listing_' . $post_id
        );

        echo '<a class="button button-small" href="' . esc_url($url) . '">' . esc_html__('Approve', 'metra-classifieds-plugin') . '</a>';
    }

    public static function handle_quick_approve(): void {
        if (!current_user_can('edit_others_posts')) {
            wp_die(esc_html__('You are not allowed to approve listings.', 'metra-classifieds-plugin'));
        }

        $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
        if ($post_id <= 0) {
            wp_safe_redirect(admin_url('edit.php?post_type=' . MC_CPT::CPT));
            exit;
        }

        check_admin_referer('mc_quick_approve_listing_' . $post_id);

        $post = get_post($post_id);
        if ($post instanceof \WP_Post && $post->post_type === MC_CPT::CPT && $post->post_status === 'pending') {
            wp_update_post([
                'ID' => $post_id,
                'post_status' => 'publish',
            ]);
        }

        $redirect = add_query_arg([
            'post_type' => MC_CPT::CPT,
        ], admin_url('edit.php'));

        wp_safe_redirect($redirect);
        exit;
    }


    private static function render_date_column(int $post_id): void {
        $published_ts = (int) get_post_time('U', false, $post_id);
        $modified_ts  = (int) get_post_modified_time('U', false, $post_id);
        $published    = wp_date('j.n.y, H:i', $published_ts);
        $modified     = wp_date('j.n.y, H:i', $modified_ts);

        echo '<span>' . esc_html($published) . '</span><br>';

        if ($published_ts === $modified_ts) {
            echo '<span>' . esc_html__('edit: no', 'metra-classifieds-plugin') . '</span>';
            return;
        }

        echo '<span>' . esc_html__('edit:', 'metra-classifieds-plugin') . ' ' . esc_html($modified) . '</span>';
    }


    public static function render_title_link_override_js(): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'edit-' . MC_CPT::CPT) {
            return;
        }

        global $wp_query;
        $map = [];

        if (isset($wp_query->posts) && is_array($wp_query->posts)) {
            foreach ($wp_query->posts as $post) {
                if (!$post instanceof \WP_Post || $post->post_type !== MC_CPT::CPT) {
                    continue;
                }

                $permalink = get_permalink($post);
                if (!is_string($permalink) || $permalink === '') {
                    continue;
                }

                $map[(int) $post->ID] = $permalink;
            }
        }

        if (!$map) {
            return;
        }
        ?>
        <script>
        (function(){
            var map = <?php echo wp_json_encode($map); ?> || {};
            document.querySelectorAll('tr[id^="post-"]').forEach(function(row){
                var id = row.id.replace('post-', '');
                if (!map[id]) {
                    return;
                }
                var link = row.querySelector('.row-title');
                if (!link) {
                    return;
                }
                link.setAttribute('href', map[id]);
            });
        })();
        </script>
        <?php
    }

    public static function render_admin_column_css(): void {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if (!$screen || $screen->id !== 'edit-' . MC_CPT::CPT) {
            return;
        }

        echo '<style>'
            . ' .wp-list-table .column-author{width:110px;}'
            . ' .wp-list-table .column-mc_moderation{width:84px;text-align:center;}'
            . ' .wp-list-table .column-mc_moderation .button{min-width:68px;padding:0 8px;}'
            . ' .wp-list-table .column-mc_author_count{width:62px;text-align:center;white-space:nowrap;}'
            . ' .wp-list-table .column-mc_date{width:150px;}'
            . '</style>';
    }

    /**
     * Returns counts per author for all listings (excluding trash).
     *
     * @return array<int,int>
     */
    private static function get_author_counts(): array {
        if (self::$author_counts !== null) {
            return self::$author_counts;
        }

        global $wpdb;

        $table = $wpdb->posts;
        $sql = $wpdb->prepare(
            "SELECT post_author, COUNT(ID) AS cnt
             FROM {$table}
             WHERE post_type = %s
               AND post_status <> 'trash'
             GROUP BY post_author",
            MC_CPT::CPT
        );

        $rows = $wpdb->get_results($sql, ARRAY_A); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

        $counts = [];
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $aid = (int) ($row['post_author'] ?? 0);
                $cnt = (int) ($row['cnt'] ?? 0);
                if ($aid > 0) {
                    $counts[$aid] = $cnt;
                }
            }
        }

        self::$author_counts = $counts;
        return self::$author_counts;
    }
}

<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Listing_View {

    public static function get_single_context(int $listing_id): array {
        $post = get_post($listing_id);
        $permissions = MC_Listing_Permissions::get_context($listing_id);

        $price = get_post_meta($listing_id, 'mc_price', true);
        $price_type = (string) get_post_meta($listing_id, 'mc_price_type', true);
        $is_negotiable = ($price_type === 'negotiable');

        $author_id = (int) get_post_field('post_author', $listing_id);
        $zip = $author_id ? (string) get_user_meta($author_id, 'mc_user_plz', true) : '';
        $city = $author_id ? (string) get_user_meta($author_id, 'mc_user_ort', true) : '';

        $condition = self::get_condition_label($listing_id);

        return [
            'post' => $post,
            'permissions' => $permissions,
            'price' => $price,
            'price_type' => $price_type,
            'is_negotiable' => $is_negotiable,
            'author_id' => $author_id,
            'zip' => $zip,
            'city' => $city,
            'condition' => $condition,
        ];
    }

    /**
     * Returns breadcrumb as array of ['label'=>string, 'url'=>string] items.
     * Each item is a clickable step in the category hierarchy.
     */
    public static function get_breadcrumb_links(int $listing_id): array {
        $terms = get_the_terms($listing_id, 'mc_category');
        if (!is_array($terms) || is_wp_error($terms) || empty($terms)) {
            return [];
        }

        usort($terms, static function ($a, $b) {
            return (int) $a->parent <=> (int) $b->parent;
        });

        $current_term = $terms[0];
        foreach ($terms as $t) {
            if ((int) $t->parent !== 0) { $current_term = $t; break; }
        }

        $category_base_url = MC_Pages::get_page_url('category_listings');
        $listings_url      = MC_Pages::get_page_url('listings');

        $links = [];

        // Root step
        if ($listings_url !== '') {
            $links[] = ['label' => __('All categories', 'metra-classifieds-plugin'), 'url' => $listings_url];
        }

        // Ancestor steps
        $ancestors = array_reverse(get_ancestors((int) $current_term->term_id, 'mc_category', 'taxonomy'));
        foreach ($ancestors as $ancestor_id) {
            $ancestor = get_term((int) $ancestor_id, 'mc_category');
            if ($ancestor instanceof \WP_Term && !is_wp_error($ancestor)) {
                $url = $category_base_url !== ''
                    ? add_query_arg('cat', (string) $ancestor_id, $category_base_url)
                    : '';
                $links[] = ['label' => (string) $ancestor->name, 'url' => $url];
            }
        }

        // Current category
        $url = $category_base_url !== ''
            ? add_query_arg('cat', (string) $current_term->term_id, $category_base_url)
            : '';
        $links[] = ['label' => (string) $current_term->name, 'url' => $url];

        return $links;
    }


    /**
     * Render a full breadcrumb for a listing as reusable HTML string.
     * Use anywhere: echo MC_Listing_View::render_breadcrumb($post_id);
     * Also available as shortcode: [mc_breadcrumb]
     *
     * @param int $listing_id
     * @return string HTML
     */
    public static function render_breadcrumb(int $listing_id): string {
        $links = self::get_breadcrumb_links($listing_id);
        if (empty($links)) return '';

        $chev = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
        $out  = '<nav class="mc-single-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'metra-classifieds-plugin') . '">';

        foreach ($links as $i => $item) {
            if ($i > 0) {
                $out .= '<span class="mc-bc-sep" aria-hidden="true">' . $chev . '</span>';
            }
            $is_last = ($i === count($links) - 1);
            if ($item['url'] !== '') {
                $class = $is_last ? 'mc-bc-link mc-bc-current' : 'mc-bc-link';
                $out .= '<a class="' . $class . '" href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a>';
            } else {
                $out .= '<span class="mc-bc-link">' . esc_html($item['label']) . '</span>';
            }
        }

        $out .= '</nav>';
        return $out;
    }

    public static function get_breadcrumb_context(int $listing_id): array {
        $listing_id_text = MC_Listing_ID::render($listing_id);
        $date_text = get_the_date();
        $breadcrumb_html = '';
        $breadcrumb_url = '';
        $terms = get_the_terms($listing_id, 'mc_category');

        if (is_array($terms) && !is_wp_error($terms) && !empty($terms)) {
            usort($terms, static function ($a, $b) {
                return (int) $a->parent <=> (int) $b->parent;
            });

            $current_term = $terms[0];
            foreach ($terms as $term_candidate) {
                if ((int) $term_candidate->parent !== 0) {
                    $current_term = $term_candidate;
                    break;
                }
            }

            $chain = [];
            $ancestors = array_reverse(get_ancestors((int) $current_term->term_id, 'mc_category', 'taxonomy'));
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term((int) $ancestor_id, 'mc_category');
                if ($ancestor instanceof \WP_Term && !is_wp_error($ancestor)) {
                    $chain[] = $ancestor->name;
                }
            }
            $chain[] = $current_term->name;
            $breadcrumb_html = esc_html(implode(' › ', array_filter($chain)));

            $category_base_url = MC_Pages::get_page_url('category_listings');
            if ($category_base_url !== '') {
                $breadcrumb_url = add_query_arg('cat', (string) ((int) $current_term->term_id), $category_base_url);
            }
        }

        return [
            'listing_id_text' => $listing_id_text,
            'date_text' => $date_text,
            'breadcrumb_html' => $breadcrumb_html,
            'breadcrumb_url' => $breadcrumb_url,
        ];
    }


    public static function get_submeta_context(int $listing_id): array {
        $breadcrumb = self::get_breadcrumb_context($listing_id);

        return [
            'listing_id_text' => (string) $breadcrumb['listing_id_text'],
            'date_text' => (string) $breadcrumb['date_text'],
            'breadcrumb_html' => (string) $breadcrumb['breadcrumb_html'],
            'breadcrumb_url' => (string) $breadcrumb['breadcrumb_url'],
        ];
    }

    public static function get_gallery_context(int $listing_id): array {
        $gallery_ids = self::get_gallery_ids($listing_id);
        $full_urls = [];
        $thumb_urls = [];

        foreach ($gallery_ids as $gid) {
            $full = wp_get_attachment_image_url($gid, 'large');
            $thumb = wp_get_attachment_image_url($gid, 'thumbnail');
            if (!$thumb) {
                continue;
            }
            $full_urls[] = $full ?: $thumb;
            $thumb_urls[] = $thumb;
        }

        return [
            'ids' => $gallery_ids,
            'full_urls' => $full_urls,
            'thumb_urls' => $thumb_urls,
            'uid' => 'mcgal_' . $listing_id . '_' . wp_rand(100, 999),
            'count' => count($full_urls),
            'has_gallery' => !empty($full_urls),
            'has_thumbs' => count($thumb_urls) > 1,
        ];
    }

    public static function get_mobile_search_context(): array {
        $search_value = isset($_GET['mc_q']) ? sanitize_text_field((string) $_GET['mc_q']) : '';
        $search_action = MC_Pages::get_page_url('listings') ?: home_url('/');

        return [
            'value' => $search_value,
            'action' => $search_action,
        ];
    }

    public static function get_footer_meta_context(int $listing_id): array {
        $author_id = (int) get_post_field('post_author', $listing_id);
        $author = $author_id ? (string) get_the_author_meta('display_name', $author_id) : '';
        $user_city = $author_id ? (string) get_user_meta($author_id, 'mc_user_ort', true) : '';
        $user_zip = $author_id ? (string) get_user_meta($author_id, 'mc_user_plz', true) : '';
        $parts = [];

        if ($user_city !== '' || $user_zip !== '') {
            $location_text = trim($user_city);
            if ($user_zip !== '') {
                $location_text .= ($location_text !== '' ? ', ' : '') . trim($user_zip);
            }
            $parts[] = esc_html__('Location', 'metra-classifieds-plugin') . ': ' . esc_html($location_text);
        }

        if ($author !== '') {
            $parts[] = esc_html__('Posted by', 'metra-classifieds-plugin') . ': ' . esc_html($author);
        }

        return [
            'author_id' => $author_id,
            'author' => $author,
            'user_city' => $user_city,
            'user_zip' => $user_zip,
            'parts' => $parts,
            'html' => implode(' - ', $parts),
        ];
    }


    /**
     * Render seller info block — reusable component.
     * Output (each on its own line):
     *   1. Posted by: Name
     *   2. Location: City, ZIP
     *   3. X listings online
     *   4. Active since: date
     *
     * Usage: echo MC_Listing_View::render_seller_info($post_id);
     *
     * @param int $listing_id
     * @return string HTML
     */
    public static function render_seller_info(int $listing_id): string {
        $author_id  = (int) get_post_field('post_author', $listing_id);
        if (!$author_id) return '';

        $author     = (string) get_the_author_meta('display_name', $author_id);
        $user_city  = (string) get_user_meta($author_id, 'mc_user_ort', true);
        $user_zip   = (string) get_user_meta($author_id, 'mc_user_plz', true);

        // Location
        $location = trim($user_city);
        if ($user_zip !== '') {
            $location .= ($location !== '' ? ', ' : '') . trim($user_zip);
        }

        // Active listings count (published, not paused)
        $active_count = (int) (new \WP_Query([
            'post_type'      => MC_CPT::CPT,
            'post_status'    => 'publish',
            'author'         => $author_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'OR',
                ['key' => '_mc_paused', 'compare' => 'NOT EXISTS'],
                ['key' => '_mc_paused', 'value' => '1', 'compare' => '!='],
            ],
        ]))->found_posts;

        // Registration date
        $user_data   = get_userdata($author_id);
        $active_since = $user_data ? date_i18n(get_option('date_format'), strtotime($user_data->user_registered)) : '';

        $listing_id_text = \MetraClassifieds\MC_Listing_ID::render($listing_id);
        $listing_date = get_the_date(get_option('date_format'), $listing_id);

        $out  = '<div class="mc-seller-info">';

        if ($listing_date !== '') {
            $out .= '<div class="mc-seller-info__row">';
            $out .= '<span class="mc-seller-info__label">' . esc_html__('Listed on', 'metra-classifieds-plugin') . ':</span> ';
            $out .= '<span class="mc-seller-info__val">' . esc_html($listing_date) . '</span>';
            $out .= '</div>';
        }

        if ($author !== '') {
            $out .= '<div class="mc-seller-info__row">';
            $out .= '<span class="mc-seller-info__label">' . esc_html__('Posted by', 'metra-classifieds-plugin') . ':</span> ';
            $out .= '<span class="mc-seller-info__val">' . esc_html($author) . '</span>';
            $out .= '</div>';
        }

        if ($location !== '') {
            $out .= '<div class="mc-seller-info__row">';
            $out .= '<span class="mc-seller-info__label">' . esc_html__('Location', 'metra-classifieds-plugin') . ':</span> ';
            $out .= '<span class="mc-seller-info__val">' . esc_html($location) . '</span>';
            $out .= '</div>';
        }

        if ($active_count > 0) {
            $seller_url = MC_Pages::get_page_url('listings');
            if ($seller_url !== '') {
                $seller_url = add_query_arg('seller', (string) $author_id, $seller_url);
            }
            $out .= '<div class="mc-seller-info__row">';
            $out .= '<span class="mc-seller-info__label">' . esc_html__('Listings online', 'metra-classifieds-plugin') . ':</span> ';
            if ($seller_url !== '') {
                $out .= '<span class="mc-seller-info__val">';
                $out .= '<a class="mc-seller-info__link" href="' . esc_url($seller_url) . '">' . (int) $active_count . '</a>';
                $out .= '&nbsp;&nbsp;<a class="mc-seller-info__cta" href="' . esc_url($seller_url) . '">' . esc_html__('View listings', 'metra-classifieds-plugin') . '</a>';
                $out .= '</span>';
            } else {
                $out .= '<span class="mc-seller-info__val">' . (int) $active_count . '</span>';
            }
            $out .= '</div>';
        }

        if ($active_since !== '') {
            $out .= '<div class="mc-seller-info__row">';
            $out .= '<span class="mc-seller-info__label">' . esc_html__('Active since', 'metra-classifieds-plugin') . ':</span> ';
            $out .= '<span class="mc-seller-info__val">' . esc_html($active_since) . '</span>';
            $out .= '</div>';
        }

        if ($listing_id_text !== '') {
            $out .= '<div class="mc-seller-info__row">';
            $out .= '<span class="mc-seller-info__label">' . esc_html__('Listing ID', 'metra-classifieds-plugin') . ':</span> ';
            $out .= '<span class="mc-seller-info__val">' . esc_html($listing_id_text) . '</span>';
            $out .= '</div>';
        }

        $out .= '<hr class="mc-seller-info__divider">';
        $out .= '</div>';
        return $out;
    }

    public static function get_action_context(int $listing_id, array $permissions, bool $is_logged_in, int $current_user_id, bool $is_paused): array {
        $post = get_post($listing_id);
        $is_favorite = $is_logged_in ? MC_Favorites::is_favorite($current_user_id, $listing_id) : false;
        $edit_url = ($post instanceof \WP_Post) ? add_query_arg('mc_edit', '1', get_permalink($listing_id)) : '';

        $delete_base = MC_Pages::get_page_url('my_listings');
        if ($delete_base === '') {
            $delete_base = home_url('/');
        }

        $delete_url = add_query_arg([
            'mc_action' => 'delete_listing',
            'listing_id' => (string) $listing_id,
            '_mc_nonce' => wp_create_nonce('mc_delete_listing_' . $listing_id),
        ], $delete_base);

        $pause_status = isset($_GET['mc_pause']) ? sanitize_key((string) $_GET['mc_pause']) : '';
        $report_status = isset($_GET['mc_report']) ? sanitize_key((string) $_GET['mc_report']) : '';

        return [
            'is_favorite' => $is_favorite,
            'edit_url' => $edit_url,
            'delete_url' => $delete_url,
            'delete_modal_id' => 'mc_del_modal_' . $listing_id,
            'report_modal_id' => 'mc_report_modal_' . $listing_id,
            'report_status' => $report_status,
            'pause_status' => $pause_status,
            'report_reasons' => MC_Report::get_reasons(),
            'permissions' => $permissions,
        ];
    }


    public static function get_notice_context(string $pause_status, bool $is_paused, string $report_status): array {
        $pause_messages = [
            'ok' => $is_paused ? __('Listing paused.', 'metra-classifieds-plugin') : __('Listing activated.', 'metra-classifieds-plugin'),
            'nonce' => __('Security check failed. Please try again.', 'metra-classifieds-plugin'),
            'forbidden' => __('You are not allowed to change this listing.', 'metra-classifieds-plugin'),
        ];
        $report_messages = [
            'sent' => __('Thank you. Your report has been sent.', 'metra-classifieds-plugin'),
            'missing_reason' => __('Please select a report reason.', 'metra-classifieds-plugin'),
            'mail_failed' => __('The report could not be sent. Please try again.', 'metra-classifieds-plugin'),
            'login_required' => __('Please log in to report a listing.', 'metra-classifieds-plugin'),
            'invalid' => __('The selected listing could not be reported.', 'metra-classifieds-plugin'),
            'invalid_nonce' => __('The report request was invalid. Please try again.', 'metra-classifieds-plugin'),
        ];

        return [
            'pause_status' => $pause_status,
            'pause_text' => $pause_messages[$pause_status] ?? '',
            'pause_background' => $pause_status === 'ok' ? '#e9f7ef' : '#fdecea',
            'pause_color' => $pause_status === 'ok' ? '#1f5134' : '#8a1f11',
            'is_paused' => $is_paused,
            'show_pause_notice' => $is_paused && $pause_status !== '' && isset($pause_messages[$pause_status]),
            'report_status' => $report_status,
            'report_text' => $report_messages[$report_status] ?? '',
            'report_background' => $report_status === 'sent' ? '#edf7ed' : '#fff4e5',
            'report_border' => $report_status === 'sent' ? '#c7e7c9' : '#f1d1a8',
            'show_report_notice' => $report_status !== '' && isset($report_messages[$report_status]),
        ];
    }


    public static function render_notices(array $notices): string {
        $show_pause_notice = !empty($notices['show_pause_notice']);
        $show_report_notice = false;

        if (!$show_pause_notice && !$show_report_notice) {
            return '';
        }

        ob_start();
        if ($show_pause_notice) {
            ?>
            <div class="mc-pause-notice" style="margin:15px 0 12px 0;padding:14px 16px;border-radius:12px;background:#444;color:#fff;font-weight:700;font-size:18px;text-align:center;">
                <?php echo esc_html((string) ($notices['pause_text'] ?? '')); ?>
            </div>
            <?php
        }
        if ($show_report_notice) {
            ?>
            <div class="mc-report-notice" style="margin:0 0 12px 0;padding:12px 14px;border-radius:12px;background:<?php echo esc_attr((string) ($notices['report_background'] ?? '')); ?>;color:#1f1f1f;border:1px solid <?php echo esc_attr((string) ($notices['report_border'] ?? '')); ?>;">
                <?php echo esc_html((string) ($notices['report_text'] ?? '')); ?>
            </div>
            <?php
        }

        return (string) ob_get_clean();
    }

    public static function render_modal_stack(array $args): string {
        $listing_id = (int) ($args['listing_id'] ?? 0);
        $permissions = (array) ($args['permissions'] ?? []);
        $report_modal_id = (string) ($args['report_modal_id'] ?? '');
        $delete_modal_id = (string) ($args['delete_modal_id'] ?? '');
        $report_reasons = (array) ($args['report_reasons'] ?? []);
        $report_status = (string) ($args['report_status'] ?? '');
        $pause_status = (string) ($args['pause_status'] ?? '');

        ob_start();
        if (!empty($permissions['show_contact_modal'])) {
            echo \MetraClassifieds\Modules\Contact\Module::render_ui($listing_id, false, false);
        }
        if (!empty($permissions['show_report_modal'])) {
            echo self::render_report_modal($listing_id, $report_modal_id, $report_reasons);
            echo self::render_report_modal_script();
            echo self::render_report_feedback_modal($report_status);
        }

        if ($report_status !== '' || $pause_status !== '') {
            echo self::render_feedback_state_cleanup_script($report_status, $pause_status);
        }
        if (!empty($permissions['show_delete_modal'])) {
            echo self::render_delete_modal($delete_modal_id);
            echo self::render_delete_modal_script();
        }

        return (string) ob_get_clean();
    }

    public static function render_report_modal(int $listing_id, string $modal_id, array $report_reasons): string {
        ob_start();
        ?>
        <div id="<?php echo esc_attr($modal_id); ?>" class="mc-modal mc-report-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;">
            <div class="mc-modal-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
            <div class="mc-modal-panel" role="dialog" aria-modal="true" style="position:relative;width:min(520px,75vw);margin:10vh auto 0 auto;background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.22);padding:16px 16px;">
                <div style="font-size:16px;font-weight:700;margin-bottom:10px;">
                    <?php echo esc_html__('Report Listing', 'metra-classifieds-plugin'); ?>
                </div>
                <form method="post" action="<?php echo esc_url(get_permalink($listing_id)); ?>">
                    <?php wp_nonce_field('mc_report_listing_' . $listing_id, '_mc_report_nonce'); ?>
                    <input type="hidden" name="mc_report_submit" value="1">
                    <input type="hidden" name="mc_report_listing_id" value="<?php echo (int) $listing_id; ?>">

                    <label for="mc-report-reason-<?php echo (int) $listing_id; ?>" style="display:block;font-weight:600;margin-bottom:6px;">
                        <?php echo esc_html__('Reason', 'metra-classifieds-plugin'); ?>
                    </label>
                    <select id="mc-report-reason-<?php echo (int) $listing_id; ?>" name="mc_report_reason" required style="width:100%;margin-bottom:12px;">
                        <option value=""><?php echo esc_html__('Please select', 'metra-classifieds-plugin'); ?></option>
                        <?php foreach ($report_reasons as $report_reason) : ?>
                            <option value="<?php echo esc_attr($report_reason); ?>"><?php echo esc_html($report_reason); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="mc-report-note-<?php echo (int) $listing_id; ?>" style="display:block;font-weight:600;margin-bottom:6px;">
                        <?php echo esc_html__('Additional note (optional)', 'metra-classifieds-plugin'); ?>
                    </label>
                    <textarea id="mc-report-note-<?php echo (int) $listing_id; ?>" name="mc_report_note" rows="4" style="width:100%;margin-bottom:14px;"></textarea>

                    <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                        <button type="button" class="button mc-modal-cancel"><?php echo esc_html__('Cancel', 'metra-classifieds-plugin'); ?></button>
                        <button type="submit" class="button button-primary"><?php echo esc_html__('Send report', 'metra-classifieds-plugin'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }


    public static function render_report_feedback_modal(string $report_status): string {
        $messages = [
            'sent' => [
                'title' => __('Report sent', 'metra-classifieds-plugin'),
                'text' => __('Thank you. Your report has been sent.', 'metra-classifieds-plugin'),
            ],
            'missing_reason' => [
                'title' => __('Report not sent', 'metra-classifieds-plugin'),
                'text' => __('Please select a report reason.', 'metra-classifieds-plugin'),
            ],
            'mail_failed' => [
                'title' => __('Report not sent', 'metra-classifieds-plugin'),
                'text' => __('The report could not be sent. Please try again.', 'metra-classifieds-plugin'),
            ],
            'login_required' => [
                'title' => __('Login required', 'metra-classifieds-plugin'),
                'text' => __('Please log in to report a listing.', 'metra-classifieds-plugin'),
            ],
            'invalid' => [
                'title' => __('Report not sent', 'metra-classifieds-plugin'),
                'text' => __('The selected listing could not be reported.', 'metra-classifieds-plugin'),
            ],
            'invalid_nonce' => [
                'title' => __('Report not sent', 'metra-classifieds-plugin'),
                'text' => __('The report request was invalid. Please try again.', 'metra-classifieds-plugin'),
            ],
        ];

        if ($report_status === '' || !isset($messages[$report_status])) {
            return '';
        }

        $title = (string) $messages[$report_status]['title'];
        $text = (string) $messages[$report_status]['text'];
        ob_start();
        ?>
        <div id="mc-report-feedback-overlay" class="mc-modal mc-report-feedback-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:100002;">
            <div class="mc-modal-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
            <div class="mc-modal-panel" role="dialog" aria-modal="true" style="position:relative;width:min(520px,75vw);margin:20vh auto 0 auto;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.25);padding:20px 20px 16px;text-align:center;">
                <div style="font-size:18px;font-weight:900;margin-bottom:8px;"><?php echo esc_html($title); ?></div>
                <div style="color:rgba(0,0,0,.65);margin-bottom:14px;"><?php echo esc_html($text); ?></div>
                <button type="button" class="button button-primary mc-report-feedback-close"><?php echo esc_html__('OK', 'metra-classifieds-plugin'); ?></button>
            </div>
        </div>
        <script>
        (function(){
            var modal = document.getElementById('mc-report-feedback-overlay');
            if (!modal) { return; }
            function closeModal(){ modal.style.display='none'; modal.setAttribute('aria-hidden','true'); }
            modal.style.display='block';
            modal.setAttribute('aria-hidden','false');
            var panel = modal.querySelector('.mc-modal-panel');
            if (panel) { panel.setAttribute('tabindex','-1'); panel.focus(); }
            modal.addEventListener('click', function(e){
                if ((e.target.classList && e.target.classList.contains('mc-modal-backdrop')) || (e.target.classList && e.target.classList.contains('mc-report-feedback-close'))) {
                    e.preventDefault();
                    closeModal();
                }
            });
            document.addEventListener('keydown', function onKey(e){
                if (e.key === 'Escape' && modal.getAttribute('aria-hidden') === 'false') {
                    closeModal();
                }
            });
        })();
        </script>
        <?php
        return (string) ob_get_clean();
    }


    public static function render_feedback_state_cleanup_script(string $report_status, string $pause_status): string {
        if ($report_status === '' && $pause_status === '') {
            return '';
        }

        ob_start();
        ?>
        <script>
        (function(){
            try {
                var url = new URL(window.location.href);
                var changed = false;
                ['mc_report', 'mc_pause'].forEach(function(key){
                    if (url.searchParams.has(key)) {
                        url.searchParams.delete(key);
                        changed = true;
                    }
                });
                if (!changed) {
                    return;
                }
                var next = url.pathname + (url.search ? url.search : '') + (url.hash ? url.hash : '');
                window.history.replaceState({}, document.title, next);
            } catch (e) {}
        })();
        </script>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_delete_modal(string $modal_id): string {
        ob_start();
        ?>
        <div id="<?php echo esc_attr($modal_id); ?>" class="mc-modal" aria-hidden="true" style="display:none;position:fixed;inset:0;z-index:99999;">
            <div class="mc-modal-backdrop" style="position:absolute;inset:0;background:rgba(0,0,0,.45);"></div>
            <div class="mc-modal-panel" role="dialog" aria-modal="true" style="position:relative;width:min(420px,75vw);margin:12vh auto 0 auto;background:#fff;border-radius:14px;box-shadow:0 18px 50px rgba(0,0,0,.22);padding:16px 16px;">
                <div style="font-size:16px;font-weight:600;margin-bottom:10px;">
                    <?php echo esc_html__('Really delete?', 'metra-classifieds-plugin'); ?>
                </div>
                <div style="font-size:14px;opacity:.9;margin-bottom:14px;line-height:1.45;">
                    <?php echo esc_html__('This listing will be permanently deleted and cannot be restored.', 'metra-classifieds-plugin'); ?>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;">
                    <button type="button" class="button mc-modal-cancel"><?php echo esc_html__('Cancel', 'metra-classifieds-plugin'); ?></button>
                    <button type="button" class="button button-primary mc-modal-confirm"><?php echo esc_html__('Delete', 'metra-classifieds-plugin'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }



    public static function render_actions(array $args): string {
        $listing_id = (int) ($args['listing_id'] ?? 0);
        $permissions = (array) ($args['permissions'] ?? []);
        $is_logged_in = !empty($args['is_logged_in']);
        $is_favorite = !empty($args['is_favorite']);
        $is_paused = !empty($args['is_paused']);
        $edit_url = (string) ($args['edit_url'] ?? '');
        $delete_url = (string) ($args['delete_url'] ?? '');
        $delete_modal_id = (string) ($args['delete_modal_id'] ?? '');
        $report_modal_id = (string) ($args['report_modal_id'] ?? '');

        ob_start();
        ?>
<div class="mc-single-actions-wrapper">
<?php if ($is_paused) : ?>
<div class="mc-single-paused-notice"><?php echo esc_html__('This listing is paused.', 'metra-classifieds-plugin'); ?></div>
<?php endif; ?>
<div class="mc-single-actions<?php echo !$is_logged_in ? ' mc-single-actions--guest' : ''; ?>">
    <?php if (!(bool) ($permissions['use_guest_favorite_gate'] ?? false)) : ?>
        <button type="button"
                class="button mc-favorite-button mc-btn-favorite mc-single-fav-btn<?php echo $is_favorite ? ' is-favorite' : ''; ?>"
                data-listing-id="<?php echo esc_attr((string) $listing_id); ?>"
                data-label-default="<?php echo esc_attr__('Set as Favorite', 'metra-classifieds-plugin'); ?>"
                data-label-active="<?php echo esc_attr__('Favorite', 'metra-classifieds-plugin'); ?>"
                aria-pressed="<?php echo $is_favorite ? 'true' : 'false'; ?>"
                aria-label="<?php echo esc_attr($is_favorite ? __('Favorite', 'metra-classifieds-plugin') : __('Set as Favorite', 'metra-classifieds-plugin')); ?>"
                title="<?php echo esc_attr($is_favorite ? __('Favorite', 'metra-classifieds-plugin') : __('Set as Favorite', 'metra-classifieds-plugin')); ?>">
            <span class="mc-favorite-button__label"><?php echo esc_html($is_favorite ? __('Favorite', 'metra-classifieds-plugin') : __('Set as Favorite', 'metra-classifieds-plugin')); ?></span>
        </button>
    <?php else : ?>
        <?php echo MC_Shortcodes::render_guest_action_button(__('Set as Favorite', 'metra-classifieds-plugin'), 'button mc-favorite-button mc-btn-favorite mc-guest-action-button', '', 'favorite', $listing_id); ?>
    <?php endif; ?>


    <?php if ((bool) ($permissions['can_open_contact'] ?? false)) : ?>
        <?php echo \MetraClassifieds\Modules\Contact\Module::render_ui($listing_id, true); ?>
    <?php elseif ((bool) ($permissions['use_guest_contact_gate'] ?? false)) : ?>
        <?php echo MC_Shortcodes::render_guest_action_button(__('Contact Seller', 'metra-classifieds-plugin'), 'button mc-contact-button mc-btn-contact mc-guest-action-button', '', 'contact', $listing_id); ?>
    <?php endif; ?>

    <?php if ((bool) ($permissions['can_open_report'] ?? false)) : ?>
        <a class="button mc-report-button" href="#" data-mc-report-modal="<?php echo esc_attr($report_modal_id); ?>"><?php echo esc_html__('Report Listing', 'metra-classifieds-plugin'); ?></a>
    <?php else : ?>
        <?php echo MC_Shortcodes::render_guest_action_button(__('Report Listing', 'metra-classifieds-plugin'), 'button mc-report-button mc-guest-action-button', '', 'report', $listing_id); ?>
    <?php endif; ?>

    <?php if ((bool) ($permissions['show_edit_button'] ?? false)) : ?>
        <a class="button mc-edit-button" href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html__('Edit listing', 'metra-classifieds-plugin'); ?></a>
    <?php endif; ?>

    <?php if ((bool) ($permissions['show_delete_button'] ?? false)) : ?>
        <a class="button mc-delete-button" href="#" data-mc-del-url="<?php echo esc_url($delete_url); ?>" data-mc-del-modal="<?php echo esc_attr($delete_modal_id); ?>"><?php echo esc_html__('Delete listing', 'metra-classifieds-plugin'); ?></a>
    <?php endif; ?>

    <?php if ((bool) ($permissions['show_pause_button'] ?? false)) : ?>
        <button type="button"
                class="button mc-pause-button<?php echo $is_paused ? ' is-paused' : ''; ?>"
                data-listing-id="<?php echo esc_attr((string) $listing_id); ?>"
                data-pause-nonce="<?php echo esc_attr(wp_create_nonce('mc_pause_listing_' . $listing_id)); ?>"
                data-label-pause="<?php echo esc_attr__('Pause', 'metra-classifieds-plugin'); ?>"
                data-label-activate="<?php echo esc_attr__('Activate', 'metra-classifieds-plugin'); ?>"
                aria-pressed="<?php echo $is_paused ? 'true' : 'false'; ?>"
                style="background:#9aa0a6;border-color:#9aa0a6;color:#fff;">
            <span class="mc-pause-button__label"><?php echo esc_html($is_paused ? __('Activate', 'metra-classifieds-plugin') : __('Pause', 'metra-classifieds-plugin')); ?></span>
        </button>
    <?php endif; ?>
</div>
</div>
        <?php
        return (string) ob_get_clean();
    }

    public static function render_report_modal_script(): string {
        return <<<'HTML'
<script>
(function(){
    if(window.__mcReportModalInit){ return; }
    window.__mcReportModalInit = true;

    function openModal(modal){
        if(!modal) return;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden','false');
        var panel = modal.querySelector('.mc-modal-panel');
        if(panel) { panel.setAttribute('tabindex','-1'); panel.focus(); }
    }

    function closeModal(modal){
        if(!modal) return;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden','true');
    }

    document.addEventListener('click', function(e){
        var reportLink = e.target && e.target.closest ? e.target.closest('[data-mc-report-modal]') : null;
        if(reportLink){
            e.preventDefault();
            var reportId = reportLink.getAttribute('data-mc-report-modal');
            var reportModal = document.getElementById(reportId);
            if(reportModal){ openModal(reportModal); }
            return;
        }

        var cancel = e.target && e.target.closest ? e.target.closest('.mc-report-modal .mc-modal-cancel') : null;
        if(cancel){
            e.preventDefault();
            closeModal(cancel.closest('.mc-report-modal'));
            return;
        }

        if(e.target.classList && e.target.classList.contains('mc-modal-backdrop')){
            var reportWrap = e.target.closest('.mc-report-modal');
            if(reportWrap){
                e.preventDefault();
                closeModal(reportWrap);
            }
        }
    }, true);

    document.addEventListener('keydown', function(e){
        if(e.key !== 'Escape') return;
        var open = document.querySelector('.mc-report-modal[aria-hidden="false"]');
        if(open) closeModal(open);
    });
})();
</script>
HTML;
    }

    public static function render_delete_modal_script(): string {
        return <<<'HTML'
<script>
(function(){
    if(window.__mcSingleDeleteModalInit){ return; }
    window.__mcSingleDeleteModalInit = true;

    function openModal(modal){
        if(!modal) return;
        modal.style.display = 'block';
        modal.setAttribute('aria-hidden','false');
        var panel = modal.querySelector('.mc-modal-panel');
        if(panel) { panel.setAttribute('tabindex','-1'); panel.focus(); }
    }

    function closeModal(modal){
        if(!modal) return;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden','true');
    }

    document.addEventListener('click', function(e){
        var a = e.target && e.target.closest ? e.target.closest('a[data-mc-del-url][data-mc-del-modal]') : null;
        if(a){
            e.preventDefault();
            var mid = a.getAttribute('data-mc-del-modal');
            var url = a.getAttribute('data-mc-del-url');
            var modal = document.getElementById(mid);
            if(!modal) return;
            modal.__mcDeleteUrl = url;
            openModal(modal);
            return;
        }

        var modal = e.target && e.target.closest ? e.target.closest('.mc-modal') : null;
        if(!modal) return;

        if(e.target.classList && (e.target.classList.contains('mc-modal-backdrop') || e.target.classList.contains('mc-modal-cancel'))){
            e.preventDefault();
            closeModal(modal);
            return;
        }
        if(e.target.classList && e.target.classList.contains('mc-modal-confirm')){
            e.preventDefault();
            var delUrl = modal.__mcDeleteUrl || '';
            if(delUrl) window.location.href = delUrl;
        }
    }, true);

    document.addEventListener('keydown', function(e){
        if(e.key !== 'Escape') return;
        var open = document.querySelector('.mc-modal[aria-hidden="false"]');
        if(open) closeModal(open);
    });
})();
</script>
HTML;
    }


    public static function ajax_toggle_pause_listing(): void {
        $listing_id = isset($_POST['listing_id']) ? (int) $_POST['listing_id'] : 0;
        $nonce = isset($_POST['nonce']) ? (string) $_POST['nonce'] : '';

        if ($listing_id <= 0 || $nonce === '' || !wp_verify_nonce($nonce, 'mc_pause_listing_' . $listing_id)) {
            wp_send_json_error([
                'message' => __('Security check failed. Please try again.', 'metra-classifieds-plugin'),
            ], 400);
        }

        $permissions = MC_Listing_Permissions::get_context($listing_id);
        if (empty($permissions['can_manage'])) {
            wp_send_json_error([
                'message' => __('You are not allowed to change this listing.', 'metra-classifieds-plugin'),
            ], 403);
        }

        $is_paused = (string) get_post_meta($listing_id, '_mc_paused', true) === '1';
        if ($is_paused) {
            delete_post_meta($listing_id, '_mc_paused');
            $is_paused = false;
        } else {
            update_post_meta($listing_id, '_mc_paused', '1');
            $is_paused = true;
        }

        wp_send_json_success([
            'is_paused' => $is_paused,
            'label' => $is_paused ? __('Activate', 'metra-classifieds-plugin') : __('Pause', 'metra-classifieds-plugin'),
            'notice_html' => $is_paused ? self::render_live_pause_notice(__('Listing paused.', 'metra-classifieds-plugin')) : '',
        ]);
    }

    private static function render_live_pause_notice(string $text): string {
        return '<div class="mc-pause-notice" style="margin:15px 0 12px 0;padding:14px 16px;border-radius:12px;background:#444;color:#fff;font-weight:700;font-size:18px;text-align:center;">' . esc_html($text) . '</div>';
    }

    public static function get_gallery_ids(int $listing_id): array {
        $gallery_ids = get_post_meta($listing_id, 'mc_gallery_ids', true);
        if (is_array($gallery_ids)) {
            $gallery_ids = array_values(array_filter(array_map('intval', $gallery_ids)));
        } else {
            $gallery_ids = [];
        }

        $thumb_id = (int) get_post_thumbnail_id($listing_id);
        if ($thumb_id > 0) {
            $gallery_ids = array_values(array_unique(array_merge([$thumb_id], $gallery_ids)));
        }

        return $gallery_ids;
    }

    private static function get_condition_label(int $listing_id): string {
        $condition_terms = get_the_terms($listing_id, 'mc_condition');
        if (is_array($condition_terms) && !is_wp_error($condition_terms) && !empty($condition_terms)) {
            return (string) $condition_terms[0]->name;
        }

        $condition_fallback = (string) get_post_meta($listing_id, 'mc_condition', true);
        $condition_labels = [
            'new' => __('New', 'metra-classifieds-plugin'),
            'as_new' => __('Like new', 'metra-classifieds-plugin'),
            'light_signs' => __('Light signs of wear', 'metra-classifieds-plugin'),
            'used' => __('Used', 'metra-classifieds-plugin'),
            'neu' => __('New', 'metra-classifieds-plugin'),
            'neuwertig' => __('Like new', 'metra-classifieds-plugin'),
            'leicht_gebraucht' => __('Light signs of wear', 'metra-classifieds-plugin'),
            'gebraucht' => __('Used', 'metra-classifieds-plugin'),
        ];

        if ($condition_fallback === '') {
            return '';
        }

        return (string) ($condition_labels[$condition_fallback] ?? $condition_fallback);
    }
}

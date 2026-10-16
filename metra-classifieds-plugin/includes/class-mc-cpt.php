<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_CPT {

    public const CPT = 'mc_listing';
    public const TAX = 'mc_category';
    public const LISTING_SLUG_OPTION = 'mc_listing_slug';
    public const LISTING_SLUG_DEFAULT = 'listing';
    public const REWRITE_FLUSH_OPTION = 'mc_listing_slug_flush_required';

    public static function get_listing_slug(): string {
        $slug = get_option(self::LISTING_SLUG_OPTION, self::LISTING_SLUG_DEFAULT);
        if (!is_string($slug)) {
            return self::LISTING_SLUG_DEFAULT;
        }

        $slug = sanitize_title($slug);
        return $slug !== '' ? $slug : self::LISTING_SLUG_DEFAULT;
    }

    public static function mark_rewrite_flush_required(): void {
        update_option(self::REWRITE_FLUSH_OPTION, '1', false);
    }

    public static function maybe_flush_rewrite_rules(): void {
        if ((string) get_option(self::REWRITE_FLUSH_OPTION, '') !== '1') {
            return;
        }

        flush_rewrite_rules(false);
        delete_option(self::REWRITE_FLUSH_OPTION);
    }

    public static function register(): void {
        $listing_slug = self::get_listing_slug();

        add_rewrite_rule(
            '^' . preg_quote($listing_slug, '/') . '/([0-9]+)/([^/]+)/?$',
            'index.php?post_type=' . self::CPT . '&p=$matches[1]',
            'top'
        );

        // Taxonomy
        $tax_labels = [
            'name'              => __('Categories', 'metra-classifieds-plugin'),
            'singular_name'     => __('Category', 'metra-classifieds-plugin'),
            'search_items'      => __('Search Categories', 'metra-classifieds-plugin'),
            'all_items'         => __('All Categories', 'metra-classifieds-plugin'),
            'parent_item'       => __('Parent Category', 'metra-classifieds-plugin'),
            'parent_item_colon' => __('Parent Category:', 'metra-classifieds-plugin'),
            'edit_item'         => __('Edit Category', 'metra-classifieds-plugin'),
            'update_item'       => __('Update Category', 'metra-classifieds-plugin'),
            'add_new_item'      => __('Add New Category', 'metra-classifieds-plugin'),
            'new_item_name'     => __('New Category Name', 'metra-classifieds-plugin'),
            'menu_name'         => __('Categories', 'metra-classifieds-plugin'),
        ];

        register_taxonomy(self::TAX, [self::CPT], [
            'hierarchical'      => true,
            'labels'            => $tax_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'mc-category'],
            'show_in_rest'      => true,
        ]);


// Condition taxonomy (canonical EN terms in DB; UI strings translated via gettext)
$cond_labels = [
    'name'              => __('Condition', 'metra-classifieds-plugin'),
    'singular_name'     => __('Condition', 'metra-classifieds-plugin'),
    'search_items'      => __('Search Conditions', 'metra-classifieds-plugin'),
    'all_items'         => __('All Conditions', 'metra-classifieds-plugin'),
    'edit_item'         => __('Edit Condition', 'metra-classifieds-plugin'),
    'update_item'       => __('Update Condition', 'metra-classifieds-plugin'),
    'add_new_item'      => __('Add New Condition', 'metra-classifieds-plugin'),
    'new_item_name'     => __('New Condition Name', 'metra-classifieds-plugin'),
    'menu_name'         => __('Condition', 'metra-classifieds-plugin'),
];

register_taxonomy('mc_condition', [self::CPT], [
    'hierarchical'      => false,
    'labels'            => $cond_labels,
    'show_ui'           => true,
    'show_admin_column' => true,
    'show_in_menu'      => 'edit.php?post_type=' . self::CPT,
    'query_var'         => true,
    'rewrite'           => ['slug' => 'condition'],
    'show_in_rest'      => true,
]);

        // CPT
        $labels = [
            'name'               => __('Listings', 'metra-classifieds-plugin'),
            'singular_name'      => __('Listing', 'metra-classifieds-plugin'),
            'menu_name'          => __('METRA Classifieds', 'metra-classifieds-plugin'),
            'name_admin_bar'     => __('Listing', 'metra-classifieds-plugin'),
            'add_new'            => __('Add New', 'metra-classifieds-plugin'),
            'add_new_item'       => __('Add New Listing', 'metra-classifieds-plugin'),
            'new_item'           => __('New Listing', 'metra-classifieds-plugin'),
            'edit_item'          => __('Edit Listing', 'metra-classifieds-plugin'),
            'view_item'          => __('View Listing', 'metra-classifieds-plugin'),
            'all_items'          => __('All Listings', 'metra-classifieds-plugin'),
            'search_items'       => __('Search Listings', 'metra-classifieds-plugin'),
            'not_found'          => __('No listings found.', 'metra-classifieds-plugin'),
            'not_found_in_trash' => __('No listings found in Trash.', 'metra-classifieds-plugin'),
        ];

        register_post_type(self::CPT, [
            'labels'             => $labels,
            'public'             => true,
            'has_archive'        => $listing_slug,
            'rewrite'            => ['slug' => $listing_slug],
            'supports'           => ['title', 'editor', 'thumbnail', 'author'],
            'taxonomies'         => [self::TAX],
            'show_in_rest'       => true,
            'menu_icon'          => 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iOSIgZmlsbD0iIzAwOTJlNiIvPjwvc3ZnPg==',
        ]);
    }

    public static function filter_listing_permalink(string $post_link, \WP_Post $post): string {
        if ($post->post_type !== self::CPT) {
            return $post_link;
        }

        $listing_slug = self::get_listing_slug();
        $post_slug = $post->post_name !== '' ? $post->post_name : sanitize_title($post->post_title);
        $post_slug = $post_slug !== '' ? $post_slug : 'listing';

        return home_url('/' . $listing_slug . '/' . (int) $post->ID . '/' . $post_slug . '/');
    }
}

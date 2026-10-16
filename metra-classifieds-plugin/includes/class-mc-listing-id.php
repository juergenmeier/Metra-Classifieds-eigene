<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Helper for a stable, human-readable listing reference number.
 *
 * We intentionally derive the visible listing ID from the immutable post ID.
 * This keeps the implementation lightweight and deterministic without adding
 * another persistence layer or changing existing listing behavior.
 */
final class MC_Listing_ID {

    /**
     * Return the formatted visible listing ID value.
     */
    public static function format(int $post_id): string {
        $post_id = max(0, $post_id);
        return sprintf('%06d', $post_id);
    }

    /**
     * Render the visible listing ID as plain text.
     *
     * The output intentionally contains no HTML wrapper so templates can place
     * the value inside their own structure without introducing block elements
     * into inline meta text flows.
     */
    public static function render(int $post_id, string $class = 'mc-listing-aznr'): string {
        if ($post_id <= 0) {
            return '';
        }

        return esc_html(self::format($post_id));
    }
}

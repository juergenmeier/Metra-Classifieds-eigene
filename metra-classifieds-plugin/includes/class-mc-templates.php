<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_Templates {

    /**
     * Use plugin templates for mc_listing single view.
     */
    public static function template_include(string $template): string {
        // Use a minimal, full-width template for METRA auth/onboarding pages.
        // Why: some themes render these pages with blog sidebars/meta/comments,
        // which looks inconsistent next to other METRA pages (e.g. Register).
        if (is_page()) {
            $p = get_queried_object();
            if ($p instanceof \WP_Post && $p->post_type === 'page') {
                $slug = (string) $p->post_name;
                $auth_slugs = [
                    'mc-login',
                    'mc-register',
                    'mc-reset-password',
                    'mc-set-password',
                ];
                if (in_array($slug, $auth_slugs, true)) {
                    $candidate = MC_PLUGIN_DIR . 'public/templates/page-mc-auth.php';
                    if (file_exists($candidate)) {
                        return $candidate;
                    }
                }
                // v0.0634: dedicated categories page — only shows the category list
                if ($slug === 'mc-categories') {
                    $candidate = MC_PLUGIN_DIR . 'public/templates/page-mc-categories.php';
                    if (file_exists($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        if (is_singular(MC_CPT::CPT)) {
            $candidate = MC_PLUGIN_DIR . 'public/templates/single-mc_listing.php';
            if (file_exists($candidate)) {
                return $candidate;
            }
        }
        return $template;
    }
}

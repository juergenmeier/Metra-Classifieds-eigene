<?php
/**
 * Minimal full-width template for METRA auth/onboarding pages.
 *
 * Purpose: Keep METRA pages like /mc-login/ visually consistent with /mc-register/
 * by preventing themes from rendering blog-like sidebars, post meta and comments.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();

?>

<main id="primary" class="site-main">
    <div class="mc-container" style="max-width: 1100px; margin: 0 auto; padding: 24px 16px;">
        <?php
        while (have_posts()) {
            the_post();

            // Render ONLY the content (where our shortcodes live).
            // No post meta, no comments, no sidebar.
            echo '<div class="mc-auth-page">';
            the_content();
            echo '</div>';
        }
        ?>
    </div>
</main>

<?php
get_footer();

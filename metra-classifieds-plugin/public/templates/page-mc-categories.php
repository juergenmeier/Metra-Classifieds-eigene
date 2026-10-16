<?php
/**
 * METRA Classifieds – Categories page template
 *
 * Version: 0.0634
 * Purpose: Renders ONLY the category list — no header title, no search bar,
 *          no sidebar, no listings. Just get_header(), the shortcode output,
 *          and get_footer().
 * Rollback: remove 'mc-categories' from $auth_slugs in class-mc-templates.php
 *           and delete this file.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>
<main id="primary" class="site-main mc-categories-main">
    <?php
    if (have_posts()) :
        while (have_posts()) : the_post();
            the_content();
        endwhile;
    endif;
    ?>
</main>
<?php
get_footer();

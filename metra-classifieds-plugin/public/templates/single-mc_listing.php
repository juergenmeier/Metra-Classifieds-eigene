<?php
declare(strict_types=1);

/**
 * METRA Classifieds – Single Listing Template
 *
 * @package Metra_Classifieds
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

if (have_posts()) :
    while (have_posts()) : the_post();
        $post_id = get_the_ID();

        $mc_view = \MetraClassifieds\MC_Listing_View::get_single_context($post_id);
        $mc_post = $mc_view['post'];
        $mc_permissions = $mc_view['permissions'];
        $mc_is_admin = (bool) $mc_permissions['is_admin'];
        $mc_current_user_id = (int) $mc_permissions['current_user_id'];
        $mc_is_owner = (bool) $mc_permissions['is_owner'];
        $mc_can_manage = (bool) $mc_permissions['can_manage'];
        $mc_is_logged_in = (bool) $mc_permissions['is_logged_in'];
        $mc_is_paused = (string) get_post_meta($post_id, '_mc_paused', true) === '1';

        if ($mc_is_paused && !(bool) $mc_permissions['can_view_paused']) {
            ?>
            <main class="mc-single">
                <article class="mc-single-card">
                    <p><?php echo esc_html__('Listing not available.', 'metra-classifieds-plugin'); ?></p>
                </article>
            </main>
            <?php
            break;
        }

        $mc_is_edit_mode = (isset($_GET['mc_edit']) && (string) $_GET['mc_edit'] === '1');
        if ($mc_is_edit_mode) {
            ?>
            <main class="mc-single">
                <article class="mc-edit-card">
                    <?php echo \MetraClassifieds\MC_Shortcodes::render_edit_form($post_id); ?>
                </article>
            </main>
            <?php
            break;
        }

        $mc_price = $mc_view['price'];
        $mc_price_type = (string) $mc_view['price_type'];
        $mc_is_negotiable = (bool) $mc_view['is_negotiable'];
        $mc_author_id = (int) $mc_view['author_id'];
        $mc_zip = (string) $mc_view['zip'];
        $mc_city = (string) $mc_view['city'];
        $mc_condition = (string) $mc_view['condition'];
        ?>
        <main class="mc-single">
        <?php if (wp_is_mobile()) : ?>
        <?php
        // ================================================================
        // COMPONENT: Mobile single listing layout (Kleinanzeigen-style)
        // Version: 0.0617
        // Purpose: Full-width hero, swipeable gallery overlay, info block,
        //   property table, fixed bottom action bar. Desktop unchanged.
        // Rollback: remove the if(wp_is_mobile()) block entirely.
        // ================================================================
        $mc_gallery   = \MetraClassifieds\MC_Listing_View::get_gallery_context($post_id);
        $mc_full_urls = (array) $mc_gallery['full_urls'];
        $mc_submeta   = \MetraClassifieds\MC_Listing_View::get_submeta_context($post_id);
        $mc_date_text = (string) $mc_submeta['date_text'];
        $mc_footer_m  = \MetraClassifieds\MC_Listing_View::get_footer_meta_context($post_id);
        $mc_loc_zip   = (string) $mc_footer_m['user_zip'];
        $mc_loc_city  = (string) $mc_footer_m['user_city'];
        $mc_location  = trim($mc_loc_zip . ' ' . $mc_loc_city);
        $mc_actions   = \MetraClassifieds\MC_Listing_View::get_action_context($post_id, $mc_permissions, $mc_is_logged_in, $mc_current_user_id, $mc_is_paused);
        $mc_is_fav    = (bool) $mc_actions['is_favorite'];
        $mc_edit_url  = (string) $mc_actions['edit_url'];
        $mc_del_url   = (string) $mc_actions['delete_url'];
        $mc_modal_id  = (string) $mc_actions['delete_modal_id'];
        $mc_rep_modal = (string) $mc_actions['report_modal_id'];
        $mc_rep_stat  = (string) $mc_actions['report_status'];
        $mc_pau_stat  = (string) $mc_actions['pause_status'];
        $mc_rep_rsns  = (array)  $mc_actions['report_reasons'];
        ?>
        <div class="mc-msingle" id="mc-msingle">

            <div class="mc-msingle-hero" id="mc-msingle-hero">

                <?php /* ── Bild ── */ ?>
                <?php if (!empty($mc_full_urls)) : ?>
                    <img class="mc-msingle-hero-img" id="mc-msingle-hero-img" src="<?php echo esc_url($mc_full_urls[0]); ?>" alt="" />
                    <?php if (count($mc_full_urls) > 1) : ?>
                        <span class="mc-msingle-hero-count" id="mc-msingle-hero-count">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            1 / <?php echo count($mc_full_urls); ?>
                        </span>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="mc-msingle-hero-noimg"></div>
                <?php endif; ?>

                <?php /* ── Zurück-Button oben links ── */ ?>
                <a class="mc-msingle-hero-btn mc-msingle-hero-back"
                   href="javascript:history.back()"
                   aria-label="<?php echo esc_attr__('Back', 'metra-classifieds-plugin'); ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </a>

                <?php /* ── Favorit-Button oben rechts ── */ ?>
                <?php if ($mc_is_logged_in) : ?>

                <?php /* ── Chevron-Buttons für Bildnavigation (nur bei mehreren Bildern) ── */ ?>
                <?php if (count($mc_full_urls) > 1) : ?>
                    <button type="button" class="mc-msingle-hero-btn mc-msingle-hero-prev" id="mc-msingle-hero-prev" aria-label="<?php echo esc_attr__('Previous image', 'metra-classifieds-plugin'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button type="button" class="mc-msingle-hero-btn mc-msingle-hero-next" id="mc-msingle-hero-next" aria-label="<?php echo esc_attr__('Next image', 'metra-classifieds-plugin'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                <?php endif; ?>

                    <button type="button"
                            id="mc-hero-fav-<?php echo (int)$post_id; ?>"
                            class="mc-msingle-hero-btn mc-msingle-hero-fav<?php echo $mc_is_fav ? ' is-active' : ''; ?>"
                            aria-label="<?php echo $mc_is_fav ? esc_attr__('Saved', 'metra-classifieds-plugin') : esc_attr__('Save', 'metra-classifieds-plugin'); ?>">
                        <?php if ($mc_is_fav) : ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="#ff6b00" stroke="#ff6b00" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6.716-4.335-9.327-8.082C.86 10.321 1.204 6.7 4.188 4.977c2.18-1.258 4.667-.63 6.208.973L12 7.467l1.604-1.517c1.541-1.603 4.028-2.231 6.208-.973 2.984 1.723 3.328 5.344 1.515 7.941C18.716 16.665 12 21 12 21z"/></svg>
                        <?php else : ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6.716-4.335-9.327-8.082C.86 10.321 1.204 6.7 4.188 4.977c2.18-1.258 4.667-.63 6.208.973L12 7.467l1.604-1.517c1.541-1.603 4.028-2.231 6.208-.973 2.984 1.723 3.328 5.344 1.515 7.941C18.716 16.665 12 21 12 21z"/></svg>
                        <?php endif; ?>
                    </button>
                    <script>(function(){
                        var btn = document.getElementById('mc-hero-fav-<?php echo (int)$post_id; ?>');
                        if (!btn) return;
                        var nonce = '<?php echo esc_js(wp_create_nonce("mc_toggle_favorite_" . $post_id)); ?>';
                        var ajax  = '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
                        var id    = '<?php echo (int)$post_id; ?>';
                        var svgOn  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="#ff6b00" stroke="#ff6b00" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6.716-4.335-9.327-8.082C.86 10.321 1.204 6.7 4.188 4.977c2.18-1.258 4.667-.63 6.208.973L12 7.467l1.604-1.517c1.541-1.603 4.028-2.231 6.208-.973 2.984 1.723 3.328 5.344 1.515 7.941C18.716 16.665 12 21 12 21z"/></svg>';
                        var svgOff = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6.716-4.335-9.327-8.082C.86 10.321 1.204 6.7 4.188 4.977c2.18-1.258 4.667-.63 6.208.973L12 7.467l1.604-1.517c1.541-1.603 4.028-2.231 6.208-.973 2.984 1.723 3.328 5.344 1.515 7.941C18.716 16.665 12 21 12 21z"/></svg>';
                        btn.addEventListener('click', function(){
                            var on = btn.classList.toggle('is-active');
                            btn.innerHTML = on ? svgOn : svgOff;
                            fetch(ajax + '?action=mc_toggle_favorite&listing_id=' + id + '&nonce=' + nonce, {method:'POST'});
                        });
                    })();</script>
                <?php else : ?>
                    <?php if (count($mc_full_urls) > 1) : ?>
                    <button type="button" class="mc-msingle-hero-btn mc-msingle-hero-prev" id="mc-msingle-hero-prev" aria-label="<?php echo esc_attr__('Previous image', 'metra-classifieds-plugin'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <button type="button" class="mc-msingle-hero-btn mc-msingle-hero-next" id="mc-msingle-hero-next" aria-label="<?php echo esc_attr__('Next image', 'metra-classifieds-plugin'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <?php endif; ?>
                    <a class="mc-msingle-hero-btn mc-msingle-hero-fav"
                       href="<?php echo esc_url(\MetraClassifieds\MC_Pages::get_page_url('login') ?: wp_login_url(get_permalink())); ?>"
                       aria-label="<?php echo esc_attr__('Save', 'metra-classifieds-plugin'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-6.716-4.335-9.327-8.082C.86 10.321 1.204 6.7 4.188 4.977c2.18-1.258 4.667-.63 6.208.973L12 7.467l1.604-1.517c1.541-1.603 4.028-2.231 6.208-.973 2.984 1.723 3.328 5.344 1.515 7.941C18.716 16.665 12 21 12 21z"/></svg>
                    </a>
                <?php endif; ?>

            </div>

            <?php if (count($mc_full_urls) > 1) : ?>
            <div class="mc-msingle-overlay" id="mc-msingle-overlay" aria-hidden="true">
                <div class="mc-msingle-overlay-bar">
                    <button class="mc-msingle-overlay-close" id="mc-msingle-overlay-close" aria-label="<?php echo esc_attr__('Close', 'metra-classifieds-plugin'); ?>">&#x2715;</button>
                    <span class="mc-msingle-overlay-count" id="mc-msingle-overlay-count">1 / <?php echo count($mc_full_urls); ?></span>
                </div>
                <div class="mc-msingle-overlay-stage">
                    <img class="mc-msingle-overlay-img" id="mc-msingle-overlay-img" src="<?php echo esc_url($mc_full_urls[0]); ?>" alt="" />
                </div>
            </div>
            <?php endif; ?>

            <div class="mc-msingle-info">
                <h1 class="mc-msingle-title"><?php echo esc_html(get_the_title()); ?></h1>
                <?php if ($mc_price !== '') : ?>
                    <div class="mc-msingle-price">
                        <?php echo esc_html(number_format_i18n((int) $mc_price)); ?> &euro;
                        <?php if ($mc_is_negotiable) : ?><span class="mc-msingle-negotiable">&middot; <?php echo esc_html__('Negotiable', 'metra-classifieds-plugin'); ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($mc_location !== '') : ?>
                    <div class="mc-msingle-location">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <?php echo esc_html($mc_location); ?>
                    </div>
                <?php endif; ?>
                <div class="mc-msingle-date">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <?php echo esc_html($mc_date_text); ?>
                </div>
            </div>

            <div class="mc-msingle-divider"></div>

            <?php if ($mc_condition !== '' || $mc_submeta['breadcrumb_html'] !== '') : ?>
            <div class="mc-msingle-props">
                <?php if ($mc_condition !== '') : ?>
                <div class="mc-msingle-prop-row">
                    <span class="mc-msingle-prop-key"><?php echo esc_html__('Condition', 'metra-classifieds-plugin'); ?></span>
                    <span class="mc-msingle-prop-val"><?php echo esc_html($mc_condition); ?></span>
                </div>
                <?php endif; ?>
                <?php if ((string) $mc_submeta['breadcrumb_html'] !== '') : ?>
                <div class="mc-msingle-prop-row">
                    <span class="mc-msingle-prop-key"><?php echo esc_html__('Category', 'metra-classifieds-plugin'); ?></span>
                    <span class="mc-msingle-prop-val">
                        <?php if ((string) $mc_submeta['breadcrumb_url'] !== '') : ?>
                            <a href="<?php echo esc_url((string) $mc_submeta['breadcrumb_url']); ?>" style="color:inherit;"><?php echo wp_kses_post((string) $mc_submeta['breadcrumb_html']); ?></a>
                        <?php else : ?>
                            <?php echo wp_kses_post((string) $mc_submeta['breadcrumb_html']); ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="mc-msingle-divider"></div>
            <?php endif; ?>

            <div class="mc-msingle-desc">
                <?php echo \MetraClassifieds\MC_Shortcodes::render_public_description((string) get_post_field('post_content', $post_id)); ?>
            </div>
            <div class="mc-msingle-divider"></div>
            <?php echo \MetraClassifieds\MC_Listing_View::render_seller_info($post_id); ?>

            <?php if ($mc_post instanceof \WP_Post) : ?>
                <?php
                $mc_notices = \MetraClassifieds\MC_Listing_View::get_notice_context($mc_pau_stat, $mc_is_paused, $mc_rep_stat);
                echo \MetraClassifieds\MC_Listing_View::render_notices($mc_notices);
                echo \MetraClassifieds\MC_Listing_View::render_modal_stack([
                    'listing_id'      => (int) $post_id,
                    'permissions'     => $mc_permissions,
                    'report_modal_id' => $mc_rep_modal,
                    'delete_modal_id' => $mc_modal_id,
                    'report_reasons'  => $mc_rep_rsns,
                    'report_status'   => $mc_rep_stat,
                    'pause_status'    => $mc_pau_stat,
                ]);
                ?>
            <?php endif; ?>

            <div class="mc-msingle-bar">
                <?php if ((bool) ($mc_permissions['can_open_contact'] ?? false)) : ?>
                    <div class="mc-msingle-bar-contact"><?php echo \MetraClassifieds\Modules\Contact\Module::render_ui($post_id, true); ?></div>
                <?php elseif ((bool) ($mc_permissions['use_guest_contact_gate'] ?? false)) : ?>
                    <div class="mc-msingle-bar-contact"><?php echo \MetraClassifieds\MC_Shortcodes::render_guest_action_button(__('Contact Seller', 'metra-classifieds-plugin'), 'button mc-btn mc-btn-primary mc-msingle-bar-contact-btn mc-btn-contact', '', 'contact', $post_id); ?></div>
                <?php endif; ?>

                <?php if ((bool) ($mc_permissions['show_edit_button'] ?? false)) : ?>
                    <a class="mc-msingle-bar-btn mc-msingle-bar-edit" href="<?php echo esc_url($mc_edit_url); ?>"><?php echo esc_html__('Edit', 'metra-classifieds-plugin'); ?></a>
                <?php endif; ?>
                <?php if ((bool) ($mc_permissions['show_delete_button'] ?? false)) : ?>
                    <a class="mc-msingle-bar-btn mc-msingle-bar-delete" href="#" data-mc-del-url="<?php echo esc_url($mc_del_url); ?>" data-mc-del-modal="<?php echo esc_attr($mc_modal_id); ?>"><?php echo esc_html__('Delete', 'metra-classifieds-plugin'); ?></a>
                <?php endif; ?>
                <?php if ((bool) ($mc_permissions['show_pause_button'] ?? false)) : ?>
                    <button type="button" class="mc-msingle-bar-btn mc-pause-button<?php echo $mc_is_paused ? ' is-paused' : ''; ?>"
                            data-listing-id="<?php echo esc_attr((string) $post_id); ?>"
                            data-pause-nonce="<?php echo esc_attr(wp_create_nonce('mc_pause_listing_' . $post_id)); ?>"
                            data-label-pause="<?php echo esc_attr__('Pause', 'metra-classifieds-plugin'); ?>"
                            data-label-activate="<?php echo esc_attr__('Activate', 'metra-classifieds-plugin'); ?>">
                        <?php echo esc_html($mc_is_paused ? __('Activate', 'metra-classifieds-plugin') : __('Pause', 'metra-classifieds-plugin')); ?>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($mc_full_urls)) : ?>
            <script>
            (function(){
                var urls = <?php echo wp_json_encode($mc_full_urls); ?>;
                var idx = 0;
                var heroImg   = document.getElementById('mc-msingle-hero-img');
                var heroCount = document.getElementById('mc-msingle-hero-count');
                var overlay   = document.getElementById('mc-msingle-overlay');
                var oImg      = document.getElementById('mc-msingle-overlay-img');
                var oCount    = document.getElementById('mc-msingle-overlay-count');
                var oClose    = document.getElementById('mc-msingle-overlay-close');
                var hero      = document.getElementById('mc-msingle-hero');
                var btnPrev   = document.getElementById('mc-msingle-hero-prev');
                var btnNext   = document.getElementById('mc-msingle-hero-next');
                if (!urls.length) return;
                function setIdx(i) {
                    idx = (i + urls.length) % urls.length;
                    if (heroImg)   heroImg.src = urls[idx];
                    if (heroCount) heroCount.textContent = (idx+1) + ' / ' + urls.length;
                    if (oImg)    oImg.src = urls[idx];
                    if (oCount)  oCount.textContent = (idx+1) + ' / ' + urls.length;
                }
                if (btnPrev) btnPrev.addEventListener('click', function(e){ e.stopPropagation(); setIdx(idx-1); });
                if (btnNext) btnNext.addEventListener('click', function(e){ e.stopPropagation(); setIdx(idx+1); });
                if (hero && overlay) {
                    hero.style.cursor = 'pointer';
                    hero.addEventListener('click', function(){
                        overlay.setAttribute('aria-hidden','false');
                        overlay.classList.add('is-open');
                        document.body.style.overflow = 'hidden';
                    });
                }
                if (oClose) {
                    oClose.addEventListener('click', function(){
                        overlay.setAttribute('aria-hidden','true');
                        overlay.classList.remove('is-open');
                        document.body.style.overflow = '';
                    });
                }
                var tx = 0;
                if (overlay) {
                    overlay.addEventListener('touchstart', function(e){ tx = e.changedTouches[0].clientX; }, {passive:true});
                    overlay.addEventListener('touchend', function(e){
                        var dx = e.changedTouches[0].clientX - tx;
                        if (Math.abs(dx) > 40) setIdx(dx < 0 ? idx+1 : idx-1);
                    }, {passive:true});
                }
            })();
            </script>
            <?php endif; ?>

        </div><!-- .mc-msingle -->
        <?php else : /* Desktop */ ?>
            <article class="mc-single-card">

                <?php /* ── Breadcrumb über dem Bild ── */
                echo \MetraClassifieds\MC_Listing_View::render_breadcrumb($post_id);
                ?>

                <?php
                $mc_gallery = \MetraClassifieds\MC_Listing_View::get_gallery_context($post_id);
                $mc_full_urls = (array) $mc_gallery['full_urls'];
                $mc_thumb_urls = (array) $mc_gallery['thumb_urls'];
                $mc_uid = (string) $mc_gallery['uid'];
                ?>

                <div class="mc-single-layout">
                    <section class="mc-single-gallery-col">
                        <?php if ((bool) $mc_gallery['has_gallery']) : ?>
                            <div class="mc-gallery" id="<?php echo esc_attr($mc_uid); ?>">
                                <div class="mc-gallery-stage">
                                    <a class="mc-gallery-back-btn" href="javascript:history.back()" aria-label="<?php echo esc_attr__('Back', 'metra-classifieds-plugin'); ?>">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                                    </a>
                                    <div class="mc-gallery-counter"><span class="mc-g-idx">1</span> / <span class="mc-g-total"><?php echo (int) $mc_gallery['count']; ?></span></div>
                                    <img class="mc-gallery-main" src="<?php echo esc_url($mc_full_urls[0]); ?>" alt="" />
                                </div>

                                <?php if ((bool) $mc_gallery['has_thumbs']) : ?>
                                    <div class="mc-gallery-thumbs">
                                        <?php foreach ($mc_thumb_urls as $i => $turl) : ?>
                                            <button type="button" class="mc-gallery-thumb<?php echo $i === 0 ? ' is-active' : ''; ?>" data-idx="<?php echo (int) $i; ?>">
                                                <img src="<?php echo esc_url($turl); ?>" alt="" />
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <script>
                                (function(){
                                  var root = document.getElementById(<?php echo json_encode($mc_uid); ?>);
                                  if(!root) return;
                                  var urls = <?php echo wp_json_encode($mc_full_urls); ?>;
                                  var counterIdx = root.querySelector('.mc-g-idx');
                                  var counterTotal = root.querySelector('.mc-g-total');
                                  if(counterTotal){ counterTotal.textContent = urls.length; }
                                  if(!urls || !urls.length) return;
                                  var idx = 0;
                                  var img = root.querySelector('.mc-gallery-main');
                                  var thumbs = [].slice.call(root.querySelectorAll('.mc-gallery-thumb'));

                                  function setActive(i){
                                    idx = (i + urls.length) % urls.length;
                                    if(img) img.src = urls[idx];
                                    if(counterIdx){ counterIdx.textContent = (idx + 1); }
                                    thumbs.forEach(function(b, j){
                                      if(j === idx) b.classList.add('is-active');
                                      else b.classList.remove('is-active');
                                    });
                                  }

                                  thumbs.forEach(function(b){
                                    b.addEventListener('click', function(){
                                      var i = parseInt(b.getAttribute('data-idx') || '0', 10);
                                      setActive(i);
                                    });
                                  });

                                  root.addEventListener('keydown', function(e){
                                    if(e.key === 'ArrowLeft') { setActive(idx - 1); }
                                    if(e.key === 'ArrowRight') { setActive(idx + 1); }
                                  });
                                })();
                                </script>
                            </div>
                        <?php endif; /* has_gallery */ ?>
                    </section>

                    <section class="mc-single-details-col">
                        <?php // === MC: Titel v0.0717: oberhalb Beschreibung, außerhalb Galerie-Spalte === ?>
                        <h1 class="mc-single-title-below"><?php echo esc_html(get_the_title()); ?></h1>
                        <div class="mc-single-content">
                            <?php
                            // =========================================================
                            // COMPONENT: SINGLE DESCRIPTION BLOCK
                            // Version: 0.0474
                            // Purpose:
                            // - provide a stable wrapper for description spacing/divider
                            // - keep spacing to the price block consistent on desktop/mobile
                            // ROLLBACK:
                            // - remove mc-single-description-block wrapper
                            // =========================================================
                            ?>
                            <div class="mc-single-description-block">
                                <?php echo \MetraClassifieds\MC_Shortcodes::render_public_description((string) get_post_field('post_content', $post_id)); ?>
                            </div>

                            <?php if ($mc_price !== '' || $mc_is_negotiable || $mc_condition !== '') : ?>
                                <?php
                                // =========================================================
                                // METRA: SINGLE PRICE + NEGOTIABLE + CONDITION STACK
                                // Version: 0.0464
                                // Purpose:
                                // - render price on its own row in the single listing view
                                // - render Negotiable and Condition on dedicated rows below price
                                // - keep the related output together in one compact template block
                                //
                                // Rollback:
                                // - remove this compact block and restore the previous price/condition markup
                                // =========================================================
                                ?>
                                <div class="mc-single-meta-block mc-listing-meta">
                                    <?php if ($mc_price !== '') : ?>
                                        <div class="mc-listing-price mc-listing-price-stack">
                                            <?php echo esc_html(number_format_i18n((int) $mc_price)); ?> €
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($mc_is_negotiable) : ?>
                                        <div class="mc-listing-negotiable mc-listing-price-stack-subline">
                                            <?php echo esc_html__('Negotiable', 'metra-classifieds-plugin'); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($mc_condition !== '') : ?>
                                        <div class="mc-listing-condition mc-listing-price-stack-subline">
                                            <strong><?php echo esc_html__('Condition: ', 'metra-classifieds-plugin'); ?></strong>
                                            <?php echo esc_html($mc_condition); ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php echo \MetraClassifieds\MC_Shortcodes::render_favorite_heart_toggle($post_id, 'mc-favorite-heart-toggle--single'); ?>


                                    <?php echo \MetraClassifieds\MC_Views::render($post_id); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mc-seller-info-wrap">
                            <?php echo \MetraClassifieds\MC_Listing_View::render_seller_info($post_id); ?>
                        </div>

                        <?php
                        $mc_actions = \MetraClassifieds\MC_Listing_View::get_action_context(
                            $post_id,
                            $mc_permissions,
                            $mc_is_logged_in,
                            $mc_current_user_id,
                            $mc_is_paused
                        );
                        $mc_is_favorite = (bool) $mc_actions['is_favorite'];
                
                        if ($mc_post instanceof \WP_Post) {
                                $mc_edit_url = (string) $mc_actions['edit_url'];
                                $mc_delete_url = (string) $mc_actions['delete_url'];
                                $mc_modal_id = (string) $mc_actions['delete_modal_id'];
                                $mc_report_modal_id = (string) $mc_actions['report_modal_id'];
                                $mc_report_status = (string) $mc_actions['report_status'];
                                $mc_pause_status = (string) $mc_actions['pause_status'];
                                $mc_report_reasons = (array) $mc_actions['report_reasons'];
                                $mc_notices = \MetraClassifieds\MC_Listing_View::get_notice_context($mc_pause_status, $mc_is_paused, $mc_report_status);
                                ?>
                                <?php echo \MetraClassifieds\MC_Listing_View::render_notices($mc_notices); ?>

<?php
                                echo \MetraClassifieds\MC_Listing_View::render_actions([
                                    'listing_id' => (int) $post_id,
                                    'permissions' => $mc_permissions,
                                    'is_logged_in' => (bool) $mc_is_logged_in,
                                    'is_favorite' => (bool) $mc_is_favorite,
                                    'is_paused' => (bool) $mc_is_paused,
                                    'edit_url' => (string) $mc_edit_url,
                                    'delete_url' => (string) $mc_delete_url,
                                    'delete_modal_id' => (string) $mc_modal_id,
                                    'report_modal_id' => (string) $mc_report_modal_id,
                                ]);
                                ?>

                                <?php
                                echo \MetraClassifieds\MC_Listing_View::render_modal_stack([
                                    'listing_id' => (int) $post_id,
                                    'permissions' => $mc_permissions,
                                    'report_modal_id' => (string) $mc_report_modal_id,
                                    'delete_modal_id' => (string) $mc_modal_id,
                                    'report_reasons' => $mc_report_reasons,
                                    'report_status' => (string) $mc_report_status,
                                    'pause_status' => (string) $mc_pause_status,
                                ]);
                                ?>
                                <?php
                            }
                        ?>
                    </section>
                </div>
            </article>
        <?php endif; /* end desktop */ ?>
        </main>
        <?php
    endwhile;
endif;

get_footer();

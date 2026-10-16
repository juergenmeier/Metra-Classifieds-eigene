(function () {
    'use strict';

    if (typeof document === 'undefined' || typeof window === 'undefined') {
        return;
    }

    var config = window.MC_PAUSE || null;
    if (!config || !config.ajaxUrl) {
        return;
    }

    function updatePauseButtonState(button, isPaused, label) {
        button.classList.toggle('is-paused', !!isPaused);
        button.setAttribute('aria-pressed', isPaused ? 'true' : 'false');

        var finalLabel = label || (isPaused ? (button.getAttribute('data-label-activate') || 'Activate') : (button.getAttribute('data-label-pause') || 'Pause'));
        var labelHolder = button.querySelector('.mc-pause-button__label');
        if (labelHolder) {
            labelHolder.textContent = finalLabel;
        } else {
            button.textContent = finalLabel;
        }
    }

    function syncPauseButtons(listingId, isPaused, label) {
        if (!listingId) {
            return;
        }

        var buttons = document.querySelectorAll('.mc-pause-button[data-listing-id="' + String(listingId) + '"]');
        buttons.forEach(function (button) {
            updatePauseButtonState(button, isPaused, label);
        });
    }

    function findNoticeAnchor(button) {
        var singleCard = button.closest('.mc-single-card');
        return singleCard || null;
    }

    function replacePauseNotice(anchor, noticeHtml) {
        if (!anchor) {
            return;
        }

        var existing = anchor.querySelector('.mc-pause-notice');
        if (existing) {
            existing.remove();
        }

        if (!noticeHtml) {
            return;
        }

        var actionWrap = anchor.querySelector('.mc-single-actions-wrapper');
        if (!actionWrap) {
            return;
        }

        actionWrap.insertAdjacentHTML('beforebegin', noticeHtml);
    }

    async function handleClick(button) {
        if (button.classList.contains('is-busy')) {
            return;
        }

        var listingId = parseInt(button.getAttribute('data-listing-id') || '0', 10);
        var nonce = button.getAttribute('data-pause-nonce') || '';
        if (!listingId || !nonce) {
            return;
        }

        button.classList.add('is-busy');

        try {
            var body = new URLSearchParams();
            body.set('action', 'mc_toggle_pause_listing');
            body.set('listing_id', String(listingId));
            body.set('nonce', nonce);

            var response = await fetch(String(config.ajaxUrl), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            });

            var json = await response.json();
            if (!response.ok || !json || !json.success || !json.data) {
                throw new Error((json && json.data && json.data.message) || (config.i18n && config.i18n.genericError) || 'Pause status could not be updated.');
            }

            syncPauseButtons(listingId, !!json.data.is_paused, String(json.data.label || ''));
            replacePauseNotice(findNoticeAnchor(button), String(json.data.notice_html || ''));
        } catch (error) {
            window.alert(error && error.message ? error.message : ((config.i18n && config.i18n.genericError) || 'Pause status could not be updated.'));
        } finally {
            button.classList.remove('is-busy');
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.mc-pause-button[data-listing-id]');
        if (!button) {
            return;
        }

        event.preventDefault();
        handleClick(button);
    });
})();

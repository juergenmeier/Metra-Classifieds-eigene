(function () {
    'use strict';

    if (typeof document === 'undefined' || typeof window === 'undefined') {
        return;
    }

    var config = window.MC_FAVORITES || null;
    if (!config || !config.ajaxUrl || !config.nonce) {
        return;
    }

    function rememberButtonBaseState(button) {
        if (!button || button.getAttribute('data-mc-base-captured') === '1') {
            return;
        }

        var computed = window.getComputedStyle(button);
        button.setAttribute('data-mc-base-background', computed.backgroundColor || '');
        button.setAttribute('data-mc-base-border-color', computed.borderColor || '');
        button.setAttribute('data-mc-base-color', computed.color || '');
        button.setAttribute('data-mc-base-captured', '1');
    }

    function updateButtonState(button, isFavorite) {
        var activeLabel = button.getAttribute('data-label-active') || (config.i18n && config.i18n.favorite) || 'Favorite';
        var defaultLabel = button.getAttribute('data-label-default') || (config.i18n && config.i18n.setFavorite) || 'Set as Favorite';
        var label = isFavorite ? activeLabel : defaultLabel;

        button.classList.toggle('is-favorite', !!isFavorite);
        button.setAttribute('aria-pressed', isFavorite ? 'true' : 'false');
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);

        var iconHolder = button.querySelector('.mc-favorite-heart-toggle__icon');
        if (iconHolder) {
            var outlineHtml = button.getAttribute('data-icon-outline') || (config.icons && config.icons.heartOutline) || '';
            var filledHtml = button.getAttribute('data-icon-filled') || (config.icons && config.icons.heartFilled) || '';
            iconHolder.innerHTML = isFavorite ? filledHtml : outlineHtml;
        }

        var labelHolder = button.querySelector('.mc-favorite-button__label');
        if (labelHolder) {
            labelHolder.textContent = label;
        } else if (!iconHolder && button.classList.contains('mc-favorite-button')) {
            button.textContent = label;
        }

        if (button.classList.contains('mc-favorite-button')) {
            rememberButtonBaseState(button);

            if (isFavorite) {
                button.style.background = '#ff923d';
                button.style.borderColor = '#ff923d';
                button.style.color = '#fff';
            } else {
                button.style.background = button.getAttribute('data-mc-base-background') || '';
                button.style.borderColor = button.getAttribute('data-mc-base-border-color') || '';
                button.style.color = button.getAttribute('data-mc-base-color') || '';
            }
        }
    }

    function syncListingButtons(listingId, isFavorite) {
        if (!listingId) {
            return;
        }

        var selector = '[data-listing-id="' + String(listingId) + '"]';
        var buttons = document.querySelectorAll(selector);
        buttons.forEach(function (button) {
            if (button.matches('.mc-favorite-heart-toggle, .mc-favorite-button')) {
                updateButtonState(button, isFavorite);
            }
        });
    }

    async function handleClick(button) {
        if (button.classList.contains('is-busy')) {
            return;
        }

        var listingId = parseInt(button.getAttribute('data-listing-id') || '0', 10);
        if (!listingId) {
            return;
        }

        button.classList.add('is-busy');

        try {
            var body = new URLSearchParams();
            body.set('action', 'mc_toggle_favorite');
            body.set('listing_id', String(listingId));
            body.set('nonce', String(config.nonce));

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
                throw new Error((json && json.data && json.data.message) || (config.i18n && config.i18n.genericError) || 'Favorite could not be updated.');
            }

            syncListingButtons(listingId, !!json.data.is_favorite);
        } catch (error) {
            window.alert(error && error.message ? error.message : ((config.i18n && config.i18n.genericError) || 'Favorite could not be updated.'));
        } finally {
            button.classList.remove('is-busy');
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.mc-favorite-heart-toggle, .mc-favorite-button[data-listing-id]');
        if (!button) {
            return;
        }

        if (button.hasAttribute('data-mc-guest-modal')) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        handleClick(button);
    });
    document.querySelectorAll('.mc-favorite-button[data-listing-id]').forEach(function (button) {
        rememberButtonBaseState(button);
    });
})();

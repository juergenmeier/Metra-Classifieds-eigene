/// <reference types="jquery" />

interface MCContactConfig {
    ajaxUrl: string;
}

declare const MCContact: MCContactConfig;

(function ($: JQueryStatic) {
    function qs(sel: string): HTMLElement | null { return document.querySelector(sel); }
    function show(el: HTMLElement | null): void { if (el) el.style.display = 'block'; }
    function hide(el: HTMLElement | null): void { if (el) el.style.display = 'none'; }

    function openModal(): void {
        show(qs('#mc-contact-overlay'));
        show(qs('#mc-contact-modal'));
        document.documentElement.style.overflow = 'hidden';
        const ta = qs('#mc-contact-message') as HTMLTextAreaElement | null;
        if (ta) { setTimeout(function () { ta.focus(); }, 50); }
    }

    function closeModal(): void {
        hide(qs('#mc-contact-modal'));
        hide(qs('#mc-contact-overlay'));
        document.documentElement.style.overflow = '';
        const inline = qs('#mc-contact-inline');
        if (inline) {
            inline.style.display = 'none';
            inline.textContent = '';
            inline.style.background = '';
            inline.style.border = '';
            inline.style.color = '';
        }
    }

    function showInlineError(text?: string): void {
        const inline = qs('#mc-contact-inline');
        if (!inline) return;
        inline.textContent = text || 'Send failed.';
        inline.style.display = 'block';
        inline.style.background = 'rgba(220,53,69,.10)';
        inline.style.border = '1px solid rgba(220,53,69,.35)';
        inline.style.color = 'rgba(220,53,69,1)';
    }

    function showSuccess(): void {
        closeModal();
        show(qs('#mc-contact-success-overlay'));
        document.documentElement.style.overflow = 'hidden';
    }

    function closeSuccess(): void {
        hide(qs('#mc-contact-success-overlay'));
        document.documentElement.style.overflow = '';
    }

    $(document).on('click', '.mc-contact-open', function (e) {
        e.preventDefault();
        openModal();
    });

    $(document).on('click', '.mc-contact-close, .mc-contact-cancel', function (e) {
        e.preventDefault();
        closeModal();
    });

    $(document).on('click', '#mc-contact-overlay', function () {
        closeModal();
    });

    $(document).on('click', '.mc-contact-success-close', function (e) {
        e.preventDefault();
        closeSuccess();
    });

    $(document).on('click', '.mc-contact-send', function (e) {
        e.preventDefault();

        const btn = document.querySelector('.mc-contact-open');
        const postId = btn ? btn.getAttribute('data-mc-post-id') : '';
        const nonce = btn ? btn.getAttribute('data-mc-nonce') : '';
        const msgEl = qs('#mc-contact-message') as HTMLTextAreaElement | null;
        const msg = msgEl ? msgEl.value : '';

        $.post(MCContact.ajaxUrl, {
            action: 'mc_contact_send',
            post_id: postId,
            nonce: nonce,
            message: msg
        }).done(function (resp: any) {
            if (resp && resp.success) {
                if (msgEl) msgEl.value = '';
                showSuccess();
                return;
            }
            showInlineError('Send failed.');
        }).fail(function () {
            showInlineError('Send failed.');
        });
    });

})(jQuery);

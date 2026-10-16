'use strict';

interface MCMaxImagesConfig {
    max?: string;
    i18n?: {
        ok?: string;
        title?: string;
        message?: string;
    };
}

// Extend Window interface for scripts
interface Window {
    MC_MAX_IMAGES?: MCMaxImagesConfig;
}

(function () {
    function ready(fn: () => void): void {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    function createModal(): HTMLElement {
        const overlay = document.createElement('div');
        overlay.id = 'mc-max-images-modal';
        overlay.style.position = 'fixed';
        overlay.style.left = '0';
        overlay.style.top = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.background = 'rgba(0,0,0,0.55)';
        overlay.style.display = 'none';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = '999999';

        const box = document.createElement('div');
        box.style.width = 'min(520px, 92vw)';
        box.style.background = '#fff';
        box.style.borderRadius = '14px';
        box.style.boxShadow = '0 20px 60px rgba(0,0,0,0.35)';
        box.style.padding = '18px 18px 14px 18px';
        box.style.fontFamily = 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif';

        const title = document.createElement('div');
        title.id = 'mc-max-images-modal-title';
        title.style.fontWeight = '700';
        title.style.fontSize = '16px';
        title.style.marginBottom = '10px';

        const msg = document.createElement('div');
        msg.id = 'mc-max-images-modal-msg';
        msg.style.fontSize = '14px';
        msg.style.lineHeight = '1.45';
        msg.style.marginBottom = '14px';

        const actions = document.createElement('div');
        actions.style.display = 'flex';
        actions.style.justifyContent = 'flex-end';
        actions.style.gap = '10px';

        const ok = document.createElement('button');
        ok.type = 'button';
        ok.id = 'mc-max-images-modal-ok';
        ok.style.padding = '9px 14px';
        ok.style.borderRadius = '10px';
        ok.style.border = '1px solid #111';
        ok.style.background = '#111';
        ok.style.color = '#fff';
        ok.style.cursor = 'pointer';
        ok.textContent = (window.MC_MAX_IMAGES && window.MC_MAX_IMAGES.i18n && window.MC_MAX_IMAGES.i18n.ok) ? window.MC_MAX_IMAGES.i18n.ok : 'OK';

        actions.appendChild(ok);
        box.appendChild(title);
        box.appendChild(msg);
        box.appendChild(actions);
        overlay.appendChild(box);
        document.body.appendChild(overlay);

        return overlay;
    }

    function showModal(text: string, onClose?: () => void): void {
        const overlay = document.getElementById('mc-max-images-modal') || createModal();
        const titleEl = document.getElementById('mc-max-images-modal-title') as HTMLElement;
        const msgEl = document.getElementById('mc-max-images-modal-msg') as HTMLElement;
        const okBtn = document.getElementById('mc-max-images-modal-ok') as HTMLButtonElement;

        const titleText = (window.MC_MAX_IMAGES && window.MC_MAX_IMAGES.i18n && window.MC_MAX_IMAGES.i18n.title) ? window.MC_MAX_IMAGES.i18n.title : 'Hinweis';
        titleEl.textContent = titleText;
        msgEl.textContent = text;

        function close(): void {
            overlay.style.display = 'none';
            document.removeEventListener('keydown', escListener);
            okBtn.removeEventListener('click', okListener);
            overlay.removeEventListener('click', overlayListener);
            if (typeof onClose === 'function') onClose();
        }

        function okListener(e: Event): void {
            e.preventDefault();
            close();
        }

        function overlayListener(e: MouseEvent): void {
            if (e.target === overlay) close();
        }

        function escListener(e: KeyboardEvent): void {
            if (e.key === 'Escape') close();
        }

        okBtn.addEventListener('click', okListener);
        overlay.addEventListener('click', overlayListener as EventListener);
        document.addEventListener('keydown', escListener);

        overlay.style.display = 'flex';
        okBtn.focus();
    }

    function bindMaxImagesCheck(): void {
        const cfg = window.MC_MAX_IMAGES || {};
        const max = parseInt(cfg.max || '0', 10);
        if (!max || isNaN(max)) return;

        const inputs = document.querySelectorAll('input[type="file"][multiple]') as NodeListOf<HTMLInputElement>;
        if (!inputs || !inputs.length) return;

        inputs.forEach(function (inp) {
            inp.addEventListener('change', function () {
                try {
                    const files = inp.files ? inp.files.length : 0;
                    if (!files) return;

                    if (files > max) {
                        let msg = (cfg.i18n && cfg.i18n.message) ? cfg.i18n.message : ('You can select up to ' + max + ' images.');
                        // ensure number is present even if message comes from translation without %d
                        if (msg.indexOf(String(max)) === -1) {
                            msg = msg + ' (' + max + ')';
                        }

                        showModal(msg, function () {
                            // clear selection so user can pick again
                            inp.value = '';
                        });
                    }
                } catch (e) {
                    // fail silently
                }
            });
        });
    }

    ready(bindMaxImagesCheck);
})();

(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function createModal() {
    var overlay = document.createElement('div');
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

    var box = document.createElement('div');
    box.style.width = 'min(520px, 92vw)';
    box.style.background = '#fff';
    box.style.borderRadius = '14px';
    box.style.boxShadow = '0 20px 60px rgba(0,0,0,0.35)';
    box.style.padding = '18px 18px 14px 18px';
    box.style.fontFamily = 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif';

    var title = document.createElement('div');
    title.id = 'mc-max-images-modal-title';
    title.style.fontWeight = '700';
    title.style.fontSize = '16px';
    title.style.marginBottom = '10px';

    var msg = document.createElement('div');
    msg.id = 'mc-max-images-modal-msg';
    msg.style.fontSize = '14px';
    msg.style.lineHeight = '1.45';
    msg.style.marginBottom = '14px';

    var actions = document.createElement('div');
    actions.style.display = 'flex';
    actions.style.justifyContent = 'flex-end';
    actions.style.gap = '10px';

    var ok = document.createElement('button');
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

  function showModal(text, onClose) {
    var overlay = document.getElementById('mc-max-images-modal') || createModal();
    var titleEl = document.getElementById('mc-max-images-modal-title');
    var msgEl = document.getElementById('mc-max-images-modal-msg');
    var okBtn = document.getElementById('mc-max-images-modal-ok');

    var titleText = (window.MC_MAX_IMAGES && window.MC_MAX_IMAGES.i18n && window.MC_MAX_IMAGES.i18n.title) ? window.MC_MAX_IMAGES.i18n.title : 'Hinweis';
    titleEl.textContent = titleText;
    msgEl.textContent = text;

    function close() {
      overlay.style.display = 'none';
      document.removeEventListener('keydown', escListener);
      okBtn.removeEventListener('click', okListener);
      overlay.removeEventListener('click', overlayListener);
      if (typeof onClose === 'function') onClose();
    }

    function okListener(e) {
      e.preventDefault();
      close();
    }

    function overlayListener(e) {
      if (e.target === overlay) close();
    }

    function escListener(e) {
      if (e.key === 'Escape') close();
    }

    okBtn.addEventListener('click', okListener);
    overlay.addEventListener('click', overlayListener);
    document.addEventListener('keydown', escListener);

    overlay.style.display = 'flex';
    okBtn.focus();
  }

  function bindMaxImagesCheck() {
    var cfg = window.MC_MAX_IMAGES || {};
    var max = parseInt(cfg.max, 10);
    if (!max || isNaN(max)) return;

    var inputs = document.querySelectorAll('input[type="file"][multiple]');
    if (!inputs || !inputs.length) return;

    inputs.forEach(function (inp) {
      inp.addEventListener('change', function () {
        try {
          var files = inp.files ? inp.files.length : 0;
          if (!files) return;

          if (files > max) {
            var msg = (cfg.i18n && cfg.i18n.message) ? cfg.i18n.message : ('You can select up to ' + max + ' images.');
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

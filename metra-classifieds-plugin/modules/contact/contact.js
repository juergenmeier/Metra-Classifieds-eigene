(function($){
  function qs(sel){ return document.querySelector(sel); }
  function show(el){ if(el) el.style.display='block'; }
  function hide(el){ if(el) el.style.display='none'; }

  function openModal(){
    show(qs('#mc-contact-overlay'));
    show(qs('#mc-contact-modal'));
    document.documentElement.style.overflow = 'hidden';
    var ta = qs('#mc-contact-message');
    if(ta){ setTimeout(function(){ ta.focus(); }, 50); }
  }
  function closeModal(){
    hide(qs('#mc-contact-modal'));
    hide(qs('#mc-contact-overlay'));
    document.documentElement.style.overflow = '';
    var inline = qs('#mc-contact-inline');
    if(inline){ inline.style.display='none'; inline.textContent=''; inline.style.background=''; inline.style.border=''; inline.style.color=''; }
  }
  function showInlineError(text){
    var inline = qs('#mc-contact-inline');
    if(!inline) return;
    inline.textContent = text || ((window.MCContact && MCContact.i18n && MCContact.i18n.send_failed) ? MCContact.i18n.send_failed : 'Send failed.');
    inline.style.display='block';
    inline.style.background='rgba(220,53,69,.10)';
    inline.style.border='1px solid rgba(220,53,69,.35)';
    inline.style.color='rgba(220,53,69,1)';
  }
  function showSuccess(){
    closeModal();
    show(qs('#mc-contact-success-overlay'));
    document.documentElement.style.overflow = 'hidden';
  }
  function closeSuccess(){
    hide(qs('#mc-contact-success-overlay'));
    document.documentElement.style.overflow = '';
  }

  var isSubmitting = false;

  function setSubmitting(state){
    isSubmitting = !!state;

    var sendBtn = qs('.mc-contact-send');
    var cancelBtn = qs('.mc-contact-cancel');
    var closeBtn = qs('.mc-contact-close');
    var msgEl = qs('#mc-contact-message');
    var note = qs('#mc-contact-sending-note');
    var text = (window.MCContact && MCContact.i18n && MCContact.i18n.sending)
      ? MCContact.i18n.sending
      : 'Einen Moment bitte. Ihre Mail wird gerade verschickt.';

    if (sendBtn) {
      sendBtn.disabled = isSubmitting;
      sendBtn.style.opacity = isSubmitting ? '0.7' : '';
      sendBtn.style.cursor = isSubmitting ? 'not-allowed' : '';
    }
    if (cancelBtn) {
      cancelBtn.disabled = isSubmitting;
      cancelBtn.style.opacity = isSubmitting ? '0.7' : '';
      cancelBtn.style.cursor = isSubmitting ? 'not-allowed' : '';
    }
    if (closeBtn) {
      closeBtn.disabled = isSubmitting;
      closeBtn.style.opacity = isSubmitting ? '0.7' : '';
      closeBtn.style.cursor = isSubmitting ? 'not-allowed' : '';
    }
    if (msgEl) {
      msgEl.readOnly = isSubmitting;
    }
    if (note) {
      note.textContent = text;
      note.style.display = isSubmitting ? 'block' : 'none';
    }
  }


  $(document).on('click', '.mc-contact-open', function(e){
    e.preventDefault();
    openModal();
  });

  $(document).on('click', '.mc-contact-close, .mc-contact-cancel', function(e){
    e.preventDefault();
    if (isSubmitting) { return; }
    closeModal();
  });

  $(document).on('click', '#mc-contact-overlay', function(){
    if (isSubmitting) { return; }
    closeModal();
  });

  $(document).on('click', '.mc-contact-success-close', function(e){
    e.preventDefault();
    closeSuccess();
  });

  $(document).on('click', '.mc-contact-send', function(e){
    e.preventDefault();

    if (isSubmitting) {
      return;
    }

    var btn = document.querySelector('.mc-contact-open');
    var postId = btn ? btn.getAttribute('data-mc-post-id') : '';
    var nonce  = btn ? btn.getAttribute('data-mc-nonce') : '';
    var msgEl  = qs('#mc-contact-message');
    var msg    = msgEl ? msgEl.value : '';

    if (!msg || !String(msg).trim()) {
      showInlineError((window.MCContact && MCContact.i18n && MCContact.i18n.empty_message) ? MCContact.i18n.empty_message : 'Please enter a message.');
      return;
    }

    setSubmitting(true);

    $.post(MCContact.ajaxUrl, {
      action: 'mc_contact_send',
      post_id: postId,
      nonce: nonce,
      message: msg
    }).done(function(resp){
      if(resp && resp.success){
        if(msgEl) msgEl.value='';
        setSubmitting(false);
        showSuccess();
        return;
      }
      setSubmitting(false);
      showInlineError((window.MCContact && MCContact.i18n && MCContact.i18n.send_failed) ? MCContact.i18n.send_failed : 'Send failed.');
    }).fail(function(){
      setSubmitting(false);
      showInlineError((window.MCContact && MCContact.i18n && MCContact.i18n.send_failed) ? MCContact.i18n.send_failed : 'Send failed.');
    });
  });

})(jQuery);

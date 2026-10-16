(function(){
  function ready(fn){
    if(document.readyState!=='loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function(){
    var root = document.documentElement;
    var btn = document.querySelector('.mc-mobile-nav-btn');
    var overlay = document.querySelector('.mc-mobile-nav-overlay');
    var panel = document.querySelector('.mc-mobile-nav-panel');
    var closeBtn = document.querySelector('.mc-mobile-nav-close');

    if(!btn || !overlay || !panel) return;

    // === Divi menu suppression ===
    // Feature: Divi hamburger removal
    // Version: 0.0596
    // Purpose: Remove all theme hamburger/menu elements from the DOM so only
    //          the METRA button remains. Divi injects its own hamburger AFTER
    //          DOMContentLoaded (triggered by its own JS at <=768px breakpoint),
    //          so a one-shot call is not enough. We use three layers:
    //          1) Immediate call on DOMContentLoaded
    //          2) setTimeout repeats at 100ms, 300ms, 600ms to catch late injections
    //          3) MutationObserver watches the DOM permanently for new nodes
    // Rollback: remove this block and the observer/timeout calls below.

    var MENU_SELECTORS = [
      '#metra-mobile-menu',
      '.et_mobile_nav_menu',
      '.et_pb_menu .et_mobile_menu',
      '.et_pb_fullwidth_menu .et_mobile_menu'
    ];

    var TOGGLE_SELECTORS = [
      '.mobile_menu_bar',
      '.et_toggle_slide_menu',
      '.et_mobile_nav_menu',
      '.et_pb_menu__menu-toggle',
      '.et_pb_menu__icon',
      '.menu-toggle',
      '.metra-mobile-toggle',
      'button.mobile_nav',
      '.mobile-menu-button',
      '.hamburger-menu'
    ];

    function isMetraElement(el){
      // Protect our own button and its container
      return el.classList.contains('mc-mobile-nav-btn') ||
             el.classList.contains('mc-mobile-nav-universal');
    }

    function removeThemeMenus(){
      MENU_SELECTORS.forEach(function(sel){
        document.querySelectorAll(sel).forEach(function(el){
          if(isMetraElement(el)) return;
          el.parentNode && el.parentNode.removeChild(el);
        });
      });
    }

    function removeThemeToggles(){
      TOGGLE_SELECTORS.forEach(function(sel){
        document.querySelectorAll(sel).forEach(function(el){
          if(isMetraElement(el)) return;
          el.parentNode && el.parentNode.removeChild(el);
        });
      });
    }

    function suppressAll(){
      removeThemeMenus();
      removeThemeToggles();
    }

    // Layer 1: immediate
    suppressAll();

    // Layer 2: timed repeats — catches Divi JS that runs after DOMContentLoaded
    setTimeout(suppressAll, 100);
    setTimeout(suppressAll, 300);
    setTimeout(suppressAll, 600);

    // Layer 3: MutationObserver — catches any future DOM injection permanently
    if(window.MutationObserver){
      var observer = new MutationObserver(function(mutations){
        var needsCheck = false;
        mutations.forEach(function(m){
          if(m.addedNodes && m.addedNodes.length) needsCheck = true;
        });
        if(needsCheck) suppressAll();
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }

    // === Our panel state ===
    function setState(open){
      if(open) root.classList.add('mc-mobile-nav-open');
      else root.classList.remove('mc-mobile-nav-open');
    }

    btn.addEventListener('click', function(e){
      e.preventDefault();
      e.stopPropagation();
      setState(!root.classList.contains('mc-mobile-nav-open'));
    });

    overlay.addEventListener('click', function(){ setState(false); });
    if(closeBtn){
      closeBtn.addEventListener('click', function(e){ e.preventDefault(); setState(false); });
    }

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') setState(false);
    });
  });
})();

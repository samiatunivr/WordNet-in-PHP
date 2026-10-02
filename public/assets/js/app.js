// Asl — progressive enhancement only; the shop works without JavaScript.
(function () {
  'use strict';

  // Product gallery thumbnails
  document.querySelectorAll('[data-gallery]').forEach(function (g) {
    var main = g.querySelector('[data-gallery-main]');
    g.querySelectorAll('.thumb').forEach(function (btn) {
      btn.addEventListener('click', function () {
        main.src = btn.getAttribute('data-src');
        g.querySelectorAll('.thumb').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
      });
    });
  });

  // Live price estimate (display only; the server always recomputes the price)
  var FACTORS = { mg: ['mass', 1e-6], g: ['mass', 1e-3], kg: ['mass', 1], ml: ['volume', 1e-3], l: ['volume', 1] };
  var LOCALES = { ar: 'ar', en: 'en-IE', nl: 'nl-NL' };
  function normalise(s) {
    return s.replace(/[٠-٩]/g, function (d) { return String(d.charCodeAt(0) - 0x0660); })
            .replace(/[۰-۹]/g, function (d) { return String(d.charCodeAt(0) - 0x06F0); })
            .replace(/[٫,]/g, '.').replace(/\s/g, '');
  }
  document.querySelectorAll('.buy-box').forEach(function (form) {
    var qty = form.querySelector('[data-qty]');
    var unit = form.querySelector('[data-unit]');
    var out = form.querySelector('[data-estimate]');
    var fmt;
    try {
      fmt = new Intl.NumberFormat(LOCALES[form.dataset.locale] || 'en', { style: 'currency', currency: form.dataset.currency });
    } catch (e) { fmt = { format: function (v) { return v.toFixed(2); } }; }
    function update() {
      var f = FACTORS[unit.value];
      var q = parseFloat(normalise(qty.value));
      var price = f && f[0] === 'mass' ? form.dataset.priceKg : form.dataset.priceL;
      if (!f || !price || !isFinite(q) || q <= 0) { out.textContent = '—'; return; }
      out.textContent = fmt.format(Math.round(q * f[1] * Number(price)) / 100);
    }
    qty.addEventListener('input', update);
    unit.addEventListener('change', update);
    update();
  });

  // Confirmation for destructive admin actions
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirm'))) { ev.preventDefault(); }
    });
  });
})();

// ---- Installable app (PWA)
(function () {
  'use strict';
  if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function () {});
    });
  }

  var btns = Array.prototype.slice.call(document.querySelectorAll('[data-install]'));
  var rows = document.querySelectorAll('[data-install-row]');
  var help = document.querySelector('[data-install-help]');
  if (!btns.length) return;
  var standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  if (standalone) return;
  function show(on) {
    btns.forEach(function (b) { b.hidden = !on; });
    rows.forEach(function (r) { r.hidden = !on; });
  }

  var deferred = null;
  window.addEventListener('beforeinstallprompt', function (ev) {
    ev.preventDefault();
    deferred = ev;
    show(true);
  });
  window.addEventListener('appinstalled', function () { show(false); deferred = null; });

  // iOS Safari has no install prompt: show manual "Add to Home Screen" instructions.
  var ua = navigator.userAgent;
  var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  if (isIOS) show(true);

  btns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (deferred) {
        deferred.prompt();
        deferred.userChoice.finally(function () { deferred = null; show(false); });
      } else if (help) {
        help.hidden = false;
      }
    });
  });
  var close = document.querySelector('[data-install-close]');
  if (close) close.addEventListener('click', function () { help.hidden = true; });
})();

// ---- App shell behaviour
(function () {
  'use strict';

  // App-bar back button: go back in history when we came from this shop.
  document.querySelectorAll('[data-back]').forEach(function (a) {
    a.addEventListener('click', function (ev) {
      try {
        if (document.referrer && new URL(document.referrer).origin === location.origin && history.length > 1) {
          ev.preventDefault();
          history.back();
        }
      } catch (e) { /* fall back to the link */ }
    });
  });

  // Toasts: success/info messages fade out; errors stay until tapped.
  document.querySelectorAll('[data-toasts] .flash').forEach(function (el) {
    function dismiss() {
      el.classList.add('hide');
      setTimeout(function () { el.remove(); }, 350);
    }
    el.addEventListener('click', dismiss);
    if (!el.classList.contains('flash-error')) setTimeout(dismiss, 3500);
  });

  // Swipe gallery dots
  document.querySelectorAll('[data-carousel]').forEach(function (track) {
    var dots = track.parentNode.querySelectorAll('.carousel-dots span');
    if (!dots.length) return;
    track.addEventListener('scroll', function () {
      var i = Math.round(Math.abs(track.scrollLeft) / track.clientWidth);
      dots.forEach(function (d, j) { d.classList.toggle('active', i === j); });
    }, { passive: true });
  });
})();

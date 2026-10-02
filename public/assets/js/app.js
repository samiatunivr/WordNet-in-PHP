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

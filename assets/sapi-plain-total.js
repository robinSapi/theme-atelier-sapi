/**
 * Sous-total des fiches accessoires sans options (inc/sapi-plain-total.php) :
 * prix unitaire × quantité. Produit simple : prix fixe (data-unit). Produit
 * variable : prix de la variation choisie (found_variation), vide sans choix.
 * Chargé seulement sur ces fiches : jamais sur un luminaire.
 */
(function ($) {
  'use strict';
  var box = document.querySelector('.sapi-plain-total');
  if (!box) return;
  var form = box.closest('form.cart');
  var amount = box.querySelector('.amount');
  var cfg = window.SAPI_PLAIN_TOTAL || {};
  var unit = box.hasAttribute('data-unit') ? parseFloat(box.getAttribute('data-unit')) : null;

  function money(n) {
    var d = parseInt(cfg.decimals, 10);
    if (!isFinite(d)) d = 2;
    var parts = n.toFixed(d).split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, cfg.thousand != null ? cfg.thousand : ' ');
    var num = parts.join(cfg.dec || ',');
    var fmt = cfg.format || '%2$s %1$s';
    return fmt.replace('%1$s', cfg.symbol || '€').replace('%2$s', num).replace(/ /g, ' ');
  }
  function qty() {
    var q = form ? form.querySelector('input.qty') : null;
    var n = q ? parseFloat(q.value) : 1;
    return isFinite(n) && n > 0 ? n : 1;
  }
  function render() {
    amount.textContent = unit === null || !isFinite(unit) ? '' : money(unit * qty());
    box.hidden = amount.textContent === '';
  }

  // Le prix de variation de WooCommerce n'est masqué qu'une fois ce script en place (style.css)
  if (form) form.classList.add('sapi-plain-total-ready');
  if (form && $) {
    $(form).on('found_variation', function (e, v) {
      unit = v && v.display_price != null ? parseFloat(v.display_price) : null;
      render();
    }).on('reset_data hide_variation', function () {
      if (!box.hasAttribute('data-unit')) { unit = null; render(); }
    });
  }
  if (form) {
    form.addEventListener('input', function (e) { if (e.target.matches('input.qty')) render(); });
    form.addEventListener('change', function (e) { if (e.target.matches('input.qty')) render(); });
    // Boutons − / + du thème : la valeur change sans toujours déclencher d'événement
    form.addEventListener('click', function () { setTimeout(render, 0); });
  }
  render();
})(window.jQuery);

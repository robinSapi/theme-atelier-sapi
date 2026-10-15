/**
 * Vitrine à gauche du mini-panier (inc/sapi-cart-suggestions.php).
 *  - « + Ajouter » (ampoule) : ajout AJAX, puis remplacement des fragments
 *    (mini-panier + vitrine) ;
 *  - « Non merci » : masque la vitrine pour le reste de la visite.
 * Délégation d'événements : la vitrine est remplacée à chaque fragment.
 */
(function ($) {
  'use strict';
  var KEY = 'sapiSuggestHidden';

  function hidden() {
    try { return sessionStorage.getItem(KEY) === '1'; } catch (e) { return false; }
  }
  function applyHidden() {
    if (hidden()) document.documentElement.classList.add('sapi-suggest-off');
  }
  applyHidden();

  document.addEventListener('click', function (e) {
    var close = e.target.closest('.sapi-suggest__close');
    if (close) {
      try { sessionStorage.setItem(KEY, '1'); } catch (err) {}
      document.documentElement.classList.add('sapi-suggest-off');
      return;
    }
    var btn = e.target.closest('.sapi-suggest__go--add');
    if (!btn || btn.disabled || !window.sapiSuggest || !$) return;
    e.preventDefault();
    btn.disabled = true;
    var label = btn.textContent;
    btn.textContent = 'Ajout…';
    $.post(sapiSuggest.addUrl, {
      action: 'sapi_add_to_cart',
      product_id: btn.getAttribute('data-product-id'),
      quantity: 1,
      nonce: sapiSuggest.nonce
    }).done(function (res) {
      if (res && res.success && res.data && res.data.fragments) {
        $.each(res.data.fragments, function (sel, html) { $(sel).replaceWith(html); });
        // added_to_cart : GTM / GA4 voient l'ajout comme les autres.
        $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash, $(btn)]);
        $(document.body).trigger('wc_fragments_refreshed');
      } else {
        btn.disabled = false;
        btn.textContent = label;
      }
    }).fail(function () {
      btn.disabled = false;
      btn.textContent = label;
    });
  });
})(window.jQuery);

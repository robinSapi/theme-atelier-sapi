/**
 * Page panier : languette « ampoule » sous chaque luminaire (inc/sapi-cart-page.php).
 *
 * Le panier est rendu par React (WooCommerce Blocks). On ajoute une ligne
 * <tr class="sapi-amp-row"> juste après la ligne du luminaire, et on la
 * resynchronise à chaque changement du DOM : retirée si le luminaire part,
 * retirées toutes dès qu'une ampoule est au panier.
 * « + Ajouter » passe par le store du panier Blocks (mise à jour sans
 * rechargement) ; à défaut, ajout AJAX classique puis rechargement.
 */
(function () {
  'use strict';
  var cfg = window.sapiCartPage || {};
  var amp = cfg.amp || {};

  function norm(s) { return (s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function rowFor(key) {
    var rows = document.querySelectorAll('.wc-block-cart-items__row');
    for (var i = 0; i < rows.length; i++) if (rows[i].getAttribute('data-cart-item-key') === key) return rows[i];
    return null;
  }
  function cartHasAmpoule() {
    var names = document.querySelectorAll('.wc-block-cart-items__row .wc-block-components-product-name');
    for (var i = 0; i < names.length; i++) if (norm(names[i].textContent).indexOf('ampoule') !== -1) return true;
    return false;
  }
  function tabHtml(key, a) {
    return '<td colspan="3"><div class="sapi-amp-tab">' +
      (a.img ? '<img class="sapi-amp-tab__img" src="' + esc(a.img) + '" alt="" width="40" height="40" loading="lazy">' : '') +
      '<p class="sapi-amp-tab__text"><strong>Ampoule non incluse.</strong> ' +
      '<span class="sapi-amp-tab__long">Celle que je recommande pour ' + esc(a.first) + '\u00a0: </span>' +
      esc(a.name) + ', ' + esc(a.price) + '</p>' +
      '<button type="button" class="sapi-amp-tab__add" data-key="' + esc(key) + '">+ Ajouter</button>' +
      '</div></td>';
  }

  var busy = false;
  function sync() {
    var hide = cartHasAmpoule();
    document.querySelectorAll('tr.sapi-amp-row').forEach(function (tr) {
      var row = rowFor(tr.getAttribute('data-for'));
      if (hide || !row || row.nextElementSibling !== tr) tr.remove();
    });
    // Vitrine sous le panier : rendue au chargement, masquée si son luminaire
    // source a quitté le panier (ou si le panier est vide).
    var vitrine = document.querySelector('.sapi-cart-page-suggest');
    if (vitrine) {
      var src = vitrine.getAttribute('data-source-key');
      var anyRow = document.querySelector('.wc-block-cart-items__row');
      vitrine.hidden = !anyRow || (src ? !rowFor(src) : false);
    }
    if (hide) return;
    Object.keys(amp).forEach(function (key) {
      var row = rowFor(key);
      if (!row) return;
      var next = row.nextElementSibling;
      if (next && next.classList.contains('sapi-amp-row')) return;
      var tr = document.createElement('tr');
      tr.className = 'sapi-amp-row';
      tr.setAttribute('data-for', key);
      tr.innerHTML = tabHtml(key, amp[key]);
      row.parentNode.insertBefore(tr, row.nextSibling);
    });
  }
  function schedule() {
    if (busy) return;
    busy = true;
    requestAnimationFrame(function () { busy = false; sync(); });
  }

  function fail(btn) {
    btn.textContent = 'Erreur';
    setTimeout(function () { btn.textContent = '+ Ajouter'; btn.disabled = false; }, 2000);
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('.sapi-amp-tab__add');
    if (!btn || btn.disabled) return;
    e.preventDefault();
    var key = btn.getAttribute('data-key');
    var a = amp[key];
    if (!a) return;
    var qty = 1;
    var row = rowFor(key);
    var input = row && row.querySelector('.wc-block-components-quantity-selector__input');
    if (input) qty = Math.max(1, parseInt(input.value, 10) || 1);
    btn.disabled = true;
    btn.textContent = 'Ajout…';

    var store = window.wp && wp.data && wp.data.dispatch && wp.data.dispatch('wc/store/cart');
    if (store && typeof store.addItemToCart === 'function') {
      Promise.resolve(store.addItemToCart(a.id, qty)).then(function () {
        schedule();
        // En-tête et mini-panier (fragments classiques) : compteur et vitrine à jour
        if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
      }, function () { fail(btn); });
      return;
    }
    if (!cfg.ajaxUrl) return fail(btn);
    var fd = new FormData();
    fd.append('product_id', a.id);
    fd.append('quantity', qty);
    fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.error) fail(btn); else location.reload(); })
      .catch(function () { fail(btn); });
  });

  sync();
  new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
})();

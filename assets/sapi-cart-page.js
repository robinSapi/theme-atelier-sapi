/**
 * Page panier : languette « ampoule » sous chaque luminaire (inc/sapi-cart-page.php).
 *
 * Le panier est rendu par React (WooCommerce Blocks). On ajoute une ligne
 * <tr class="sapi-amp-row"> juste après la ligne du luminaire, et on la
 * resynchronise à chaque changement du DOM : retirée si le luminaire part,
 * ou dès qu'une de ses ampoules est au panier.
 * La vitrine « Ça irait bien avec… » est déplacée dans la mise en page du
 * panier (.wc-block-components-sidebar-layout), que le CSS passe en grille.
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
  /** Une des ampoules compatibles avec ce luminaire est-elle au panier ? */
  function hasAmpouleFor(a) {
    // 1. Par id, dans le store du panier Blocks
    var sel = window.wp && wp.data && wp.data.select && wp.data.select('wc/store/cart');
    var data = sel && typeof sel.getCartData === 'function' ? sel.getCartData() : null;
    if (data && data.items && data.items.length) {
      var ids = (a.ids || []).map(Number);
      return data.items.some(function (it) { return ids.indexOf(Number(it.id)) !== -1; });
    }
    // 2. Repli : par nom affiché
    var wanted = (a.names || []).map(function (n) { return norm(n).trim(); });
    var names = document.querySelectorAll('.wc-block-cart-items__row .wc-block-components-product-name');
    for (var i = 0; i < names.length; i++) {
      if (wanted.indexOf(norm(names[i].textContent).trim()) !== -1) return true;
    }
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
  var vitrine = document.querySelector('.sapi-cart-page-suggest');
  function sync() {
    document.querySelectorAll('tr.sapi-amp-row').forEach(function (tr) {
      var key = tr.getAttribute('data-for');
      var row = rowFor(key);
      if (!row || row.nextElementSibling !== tr || (amp[key] && hasAmpouleFor(amp[key]))) tr.remove();
    });
    // Vitrine : placée dans la mise en page du panier (React peut la recréer,
    // on la replace alors), masquée si son luminaire source a quitté le panier.
    var layout = document.querySelector('.sapi-cart-outer .wc-block-components-sidebar-layout');
    if (vitrine && layout && vitrine.parentNode !== layout) {
      layout.appendChild(vitrine);
      vitrine.classList.add('is-placed');
    }
    if (vitrine) {
      var src = vitrine.getAttribute('data-source-key');
      var anyRow = document.querySelector('.wc-block-cart-items__row');
      vitrine.hidden = !anyRow || (src ? !rowFor(src) : false);
    }
    Object.keys(amp).forEach(function (key) {
      var row = rowFor(key);
      if (!row || hasAmpouleFor(amp[key])) return;
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
    var btn = e.target.closest && e.target.closest('.sapi-amp-tab__add[data-key]');
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
  // Filet : si la mise en page du panier reste introuvable, la vitrine
  // s'affiche à sa place d'origine (sous le panier) plutôt que jamais.
  setTimeout(function () { if (vitrine && !vitrine.classList.contains('is-placed')) vitrine.classList.add('is-placed'); }, 4000);
  new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
})();

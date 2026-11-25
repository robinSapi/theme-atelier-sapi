/**
 * Panneau « Quelle ampoule pour Léon ? » (25/11/2026, maquette selecteur-ampoules).
 * Partagé par la fiche produit (assets/sapi-ampoule-optin.js), le volet
 * (assets/sapi-cart-suggestions.js) et la page panier (assets/sapi-cart-page.js).
 *
 * Les données viennent des fiches des ampoules, via SAPI_AMPOULES
 * (inc/sapi-ampoule-optin.php, sapi_ampoule_cards) : nom, prix, photos,
 * phrase (description courte), caractéristiques (attributs visibles).
 *
 * Usage : sapiAmpPicker.open({ ids: [..], recoId, pickedId, first: 'Léon',
 *                              onPick: function (id) {} })
 * Le choix ne fait RIEN par lui-même : c'est l'appelant qui ajoute ou coche.
 */
(function () {
  'use strict';
  var DATA = window.SAPI_AMPOULES || {};
  var root = null, opts = null, lastFocus = null;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function build() {
    root = document.createElement('div');
    root.className = 'sapi-amp-picker';
    root.hidden = true;
    root.innerHTML =
      '<div class="sapi-amp-picker__panel" role="dialog" aria-modal="true" aria-labelledby="sapi-amp-picker-title">' +
        '<div class="sapi-amp-picker__head">' +
          '<h2 class="sapi-amp-picker__title" id="sapi-amp-picker-title"></h2>' +
          '<button type="button" class="sapi-amp-picker__close" aria-label="Fermer">×</button>' +
        '</div>' +
        '<p class="sapi-amp-picker__sub"></p>' +
        '<div class="sapi-amp-picker__grid"></div>' +
      '</div>';
    document.body.appendChild(root);
    root.addEventListener('click', function (e) {
      if (e.target === root || e.target.closest('.sapi-amp-picker__close')) { close(); return; }
      var b = e.target.closest('[data-amp-pick]');
      if (!b || !opts) return;
      var id = parseInt(b.getAttribute('data-amp-pick'), 10);
      var cb = opts.onPick;
      close();
      if (typeof cb === 'function') cb(id);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && root && !root.hidden) close();
    });
  }

  function card(a, reco, picked, first) {
    var specs = (a.specs || []).map(function (s) {
      return '<li><span class="sapi-amp-picker__k">' + esc(s[0]) + '</span> ' + esc(s[1]) + '</li>';
    }).join('');
    var photo = a.amb || a.img;
    return '<div class="sapi-amp-card' + (reco ? ' is-reco' : '') + (picked ? ' is-picked' : '') + '">' +
      (reco ? '<span class="sapi-amp-card__badge">Mon choix pour ' + esc(first) + '</span>' : '') +
      '<div class="sapi-amp-card__photos">' +
        (photo ? '<img src="' + esc(photo) + '" alt="" loading="lazy">' : '') +
        (a.amb && a.img ? '<img class="sapi-amp-card__pk" src="' + esc(a.img) + '" alt="" loading="lazy">' : '') +
      '</div>' +
      '<div class="sapi-amp-card__body">' +
        '<div class="sapi-amp-card__name"><b>' + esc(a.name) + '</b><span>' + esc(a.priceHtml) + '</span></div>' +
        (a.why ? '<p class="sapi-amp-card__why">' + esc(a.why) + '</p>' : '') +
        (specs ? '<ul class="sapi-amp-card__specs">' + specs + '</ul>' : '') +
        '<button type="button" class="sapi-amp-card__pick" data-amp-pick="' + a.id + '">' + (picked ? '✓ Choisie' : 'Choisir celle-ci') + '</button>' +
      '</div></div>';
  }

  function open(o) {
    var ids = (o && o.ids || []).filter(function (id) { return DATA[id]; });
    if (!ids.length) return;
    if (!root) build();
    opts = o;
    var first = o.first || 'ton luminaire';
    root.querySelector('.sapi-amp-picker__title').textContent = 'Quelle ampoule pour ' + first + ' ?';
    root.querySelector('.sapi-amp-picker__sub').textContent = ids.length > 1
      ? 'Elles vont toutes avec ' + first + '. Elles changent surtout l\'effet de la lumière à travers le bois.'
      : '';
    root.querySelector('.sapi-amp-picker__grid').innerHTML = ids.map(function (id) {
      return card(DATA[id], id === o.recoId, id === o.pickedId, first);
    }).join('');
    lastFocus = document.activeElement;
    root.hidden = false;
    document.documentElement.classList.add('sapi-amp-picker-open');
    var btn = root.querySelector('.sapi-amp-picker__close');
    if (btn) btn.focus();
  }

  function close() {
    if (!root) return;
    root.hidden = true;
    opts = null;
    document.documentElement.classList.remove('sapi-amp-picker-open');
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  window.sapiAmpPicker = { open: open, close: close, data: DATA };
})();

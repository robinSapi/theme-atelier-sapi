/**
 * Sapi Photo Swap (S28 Phase 4b) — swap des photos produits par pièce
 * via lecture de sapiProject.answers.piece (+ essence dérivée).
 *
 * Surfaces ciblées :
 *  - Cards /mes-creations/ ............. <el data-piece-swap data-product-id data-piece-swap-type="ambiance" data-piece-swap-size="large">
 *  - Cards page catégorie .............. idem (taille différente "woocommerce_thumbnail")
 *  - Home featured (coup de cœur) ...... idem (type "detail")
 *  - Fiche produit positions 1+2 ....... <img data-piece-swap-slide="1|2" data-product-id> (cas spécial : 2 slides depuis le même produit)
 *
 * Pattern :
 *  - Lit sapiProject (lecture seule via window.sapiProject)
 *  - Au DOMContentLoaded + à chaque sapiProject.subscribe notification :
 *    1. Cards : groupe par (type, size), un AJAX par groupe, swap chaque img.src
 *    2. Slides fiche produit : 1 AJAX dédié (count=2), swap les 2 positions
 *  - Si pas de pièce, ou AJAX échoue, ou pièce sans photo : le rendu PHP par défaut
 *    reste affiché (progressive enhancement strict, jamais de trou).
 *
 * Échecs silencieux. Le swap est un "plus" : un échec ne casse jamais l'affichage.
 */
(function () {
  'use strict';

  var config = window.SAPI_PHOTO_SWAP || {};
  if (!config.ajaxUrl || !config.nonce) return;

  // Mapping style → essence (mirror sapi-product-preselect.js).
  function deriveEssence(answers) {
    if (!answers || !answers.style) return '';
    if (answers.style === 'moderne') return 'peuplier';
    if (answers.style === 'ancien')  return 'okoume';
    return '';
  }

  function fetchWithTimeout(url, timeout) {
    if (typeof AbortController === 'undefined') return fetch(url); // vieux navigateurs : pas de timeout
    var controller = new AbortController();
    var id = setTimeout(function () { controller.abort(); }, timeout || 5000);
    return fetch(url, { signal: controller.signal }).finally(function () { clearTimeout(id); });
  }

  function buildUrl(params) {
    var qs = [];
    Object.keys(params).forEach(function (k) {
      qs.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
    });
    return config.ajaxUrl + '?' + qs.join('&');
  }

  // Swap d'une <img> : remplace src ET RESET srcset (cf CLAUDE.md règle #9 :
  // changer src sans reset srcset → le navigateur reste sur l'ancien srcset).
  function applySwap(img, url) {
    if (!img || !url) return;
    if (img.src === url && !img.srcset) return; // déjà à jour
    img.src = url;
    img.srcset = '';
    img.removeAttribute('srcset');
  }

  /* ─────────────────────────────────────────────
     Mode 1 — Cards (1 image par produit)
     ───────────────────────────────────────────── */
  function collectCards() {
    var cards = document.querySelectorAll('[data-piece-swap]:not([data-piece-swap-slide])');
    if (!cards.length) return [];
    var out = [];
    for (var i = 0; i < cards.length; i++) {
      var el = cards[i];
      var pid = el.getAttribute('data-product-id');
      if (!pid) continue;
      out.push({
        el:   el,
        pid:  pid,
        type: el.getAttribute('data-piece-swap-type') || 'ambiance',
        size: el.getAttribute('data-piece-swap-size') || 'large',
      });
    }
    return out;
  }

  function swapCards(piece, essence) {
    if (!piece) return; // pas de pièce → rien à faire, défaut reste
    var cards = collectCards();
    if (!cards.length) return;

    // Groupe par (type, size) → 1 AJAX par groupe
    var groups = {};
    cards.forEach(function (c) {
      var key = c.type + '|' + c.size;
      if (!groups[key]) groups[key] = { type: c.type, size: c.size, cards: [] };
      groups[key].cards.push(c);
    });

    Object.keys(groups).forEach(function (key) {
      var g = groups[key];
      var ids = g.cards.map(function (c) { return c.pid; }).join(',');
      var url = buildUrl({
        action:   'sapi_get_piece_photos',
        piece:    piece,
        essence:  essence || '',
        type:     g.type,
        size:     g.size,
        count:    1,
        ids:      ids,
        _wpnonce: config.nonce,
      });

      fetchWithTimeout(url, 5000)
        .then(function (r) { return r.json(); })
        .then(function (map) {
          if (!map || typeof map !== 'object') return;
          g.cards.forEach(function (c) {
            var urls = map[c.pid];
            if (!urls || !urls.length) return; // pas de piece-photo → défaut conservé
            var img = c.el.querySelector('img');
            if (!img) return;
            applySwap(img, urls[0]);
          });
        })
        .catch(function () { /* silencieux — défaut conservé */ });
    });
  }

  /* ─────────────────────────────────────────────
     Mode 2 — Slides fiche produit (positions 1+2)
     ───────────────────────────────────────────── */
  function collectSlides() {
    var slides = document.querySelectorAll('[data-piece-swap-slide]');
    if (!slides.length) return null;
    // Tous les slides partagent le même product-id (fiche produit unique)
    var pid = null;
    var byPosition = {};
    for (var i = 0; i < slides.length; i++) {
      var el = slides[i];
      var slidePid = el.getAttribute('data-product-id');
      if (!slidePid) continue;
      if (!pid) pid = slidePid;
      var pos = parseInt(el.getAttribute('data-piece-swap-slide'), 10);
      if (pos >= 1 && pos <= 4) byPosition[pos] = el;
    }
    if (!pid) return null;
    var positions = Object.keys(byPosition).map(Number);
    if (!positions.length) return null;
    var maxPos = Math.max.apply(null, positions);
    return { pid: pid, byPosition: byPosition, count: maxPos };
  }

  function swapProductSlides(piece, essence) {
    if (!piece) return;
    var data = collectSlides();
    if (!data) return;

    var url = buildUrl({
      action:   'sapi_get_piece_photos',
      piece:    piece,
      essence:  essence || '',
      type:     'ambiance',
      size:     'full',
      count:    data.count,
      ids:      data.pid,
      _wpnonce: config.nonce,
    });

    fetchWithTimeout(url, 5000)
      .then(function (r) { return r.json(); })
      .then(function (map) {
        if (!map || !map[data.pid]) return;
        var urls = map[data.pid];
        // Swap chaque position avec l'URL correspondante (1-indexed → 0-indexed)
        Object.keys(data.byPosition).forEach(function (posStr) {
          var pos = parseInt(posStr, 10);
          var swapUrl = urls[pos - 1];
          if (!swapUrl) return; // pièce a moins de photos que de positions demandées → on garde le défaut sur les positions restantes
          var el = data.byPosition[pos];
          var img = el.tagName === 'IMG' ? el : el.querySelector('img');
          applySwap(img, swapUrl);
        });
      })
      .catch(function () { /* silencieux */ });
  }

  /* ─────────────────────────────────────────────
     Orchestration
     ───────────────────────────────────────────── */
  function swapAll(project) {
    var answers = (project && project.answers) || {};
    var piece   = answers.piece || '';
    var essence = deriveEssence(answers);
    if (!piece) return; // sans pièce, rendu défaut conservé partout
    swapCards(piece, essence);
    swapProductSlides(piece, essence);
  }

  function init() {
    // Pas d'éléments à swapper sur cette page → no-op total
    var hasCards  = !!document.querySelector('[data-piece-swap]:not([data-piece-swap-slide])');
    var hasSlides = !!document.querySelector('[data-piece-swap-slide]');
    if (!hasCards && !hasSlides) return;

    if (!window.sapiProject) return;

    // Swap initial
    swapAll(window.sapiProject.get());

    // Re-swap si la pièce change in-session (modale fermée sans reload,
    // changement inter-onglets via storage event, etc.)
    if (typeof window.sapiProject.subscribe === 'function') {
      window.sapiProject.subscribe(function (project) {
        swapAll(project);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

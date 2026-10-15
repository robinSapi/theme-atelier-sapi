/**
 * Vitrine à gauche du mini-panier (inc/sapi-cart-suggestions.php).
 *
 * Le serveur rend les cartes dans .sapi-cart-suggest-src (caché), seule cible
 * du fragment AJAX. Ce script recopie la source dans la vitrine visible
 * .sapi-cart-suggest en comparant les cartes par produit :
 *   - une carte qui disparaît s'efface en fondu, puis est retirée ;
 *   - une carte nouvelle entre en fondu avec une légère montée ;
 *   - une carte conservée est mise à jour sur place, sans animation.
 * L'arrivée décalée à l'ouverture du volet est faite en CSS (transition-delay
 * par carte via --i).
 *
 * « + Ajouter » (ampoule) : ajout AJAX, puis remplacement des fragments.
 * « Non merci » : masque la vitrine pour le reste de la visite.
 */
(function ($) {
  'use strict';
  var KEY = 'sapiSuggestHidden';
  var LEAVE_MS = 280;

  try { if (sessionStorage.getItem(KEY) === '1') document.documentElement.classList.add('sapi-suggest-off'); } catch (e) {}

  var view = document.querySelector('.sapi-cart-suggest');
  if (!view) return;

  function cardsOf(root) {
    var map = {};
    root.querySelectorAll('.sapi-suggest__card').forEach(function (c) { map[c.getAttribute('data-product-id')] = c; });
    return map;
  }
  function setIndexes(list) {
    list.querySelectorAll('.sapi-suggest__card').forEach(function (c, i) { c.style.setProperty('--i', i); });
  }

  function sync() {
    var src = document.querySelector('.sapi-cart-suggest-src');
    if (!src) return;
    var srcCards = src.querySelectorAll('.sapi-suggest__card');

    // Plus rien à proposer : la vitrine s'efface entièrement.
    if (!srcCards.length) {
      if (view.children.length) {
        view.classList.add('is-leaving');
        setTimeout(function () { view.innerHTML = ''; view.classList.remove('is-leaving'); }, LEAVE_MS);
      }
      return;
    }

    // Premier rendu : copie simple, l'animation d'ouverture fera le reste.
    var list = view.querySelector('.sapi-suggest__cards');
    if (!list) {
      view.innerHTML = src.innerHTML;
      setIndexes(view.querySelector('.sapi-suggest__cards'));
      return;
    }

    // Titres (le prénom peut changer)
    ['.sapi-suggest__eyebrow', '.sapi-suggest__title'].forEach(function (sel) {
      var a = view.querySelector(sel), b = src.querySelector(sel);
      if (a && b && a.textContent !== b.textContent) a.textContent = b.textContent;
    });

    var oldMap = cardsOf(list);
    var newMap = cardsOf(src);
    var leaving = [];
    Object.keys(oldMap).forEach(function (id) {
      if (!newMap[id]) { oldMap[id].classList.add('is-leaving'); leaving.push(oldMap[id]); }
    });

    function rebuild() {
      leaving.forEach(function (c) { if (c.parentNode) c.parentNode.removeChild(c); });
      var frag = document.createDocumentFragment();
      Array.prototype.forEach.call(srcCards, function (sc) {
        var id = sc.getAttribute('data-product-id');
        var kept = oldMap[id] && !oldMap[id].classList.contains('is-leaving') ? oldMap[id] : null;
        if (kept) {
          // Contenu à jour (badge, prix) sans rejouer l'animation.
          if (kept.innerHTML !== sc.innerHTML) kept.innerHTML = sc.innerHTML;
          frag.appendChild(kept);
        } else {
          var fresh = sc.cloneNode(true);
          fresh.classList.add('is-entering');
          fresh.addEventListener('animationend', function () { fresh.classList.remove('is-entering'); }, { once: true });
          frag.appendChild(fresh);
        }
      });
      list.innerHTML = '';
      list.appendChild(frag);
      setIndexes(list);
    }
    if (leaving.length) setTimeout(rebuild, LEAVE_MS); else rebuild();
  }

  sync();

  // La source est remplacée à chaque fragment : on surveille son parent.
  var src0 = document.querySelector('.sapi-cart-suggest-src');
  if (src0 && src0.parentNode && window.MutationObserver) {
    new MutationObserver(function (muts) {
      for (var i = 0; i < muts.length; i++) {
        for (var j = 0; j < muts[i].addedNodes.length; j++) {
          var n = muts[i].addedNodes[j];
          if (n.nodeType === 1 && n.classList.contains('sapi-cart-suggest-src')) { sync(); return; }
        }
      }
    }).observe(src0.parentNode, { childList: true });
  }

  document.addEventListener('click', function (e) {
    var close = e.target.closest('.sapi-cart-suggest .sapi-suggest__close');
    if (close) {
      try { sessionStorage.setItem(KEY, '1'); } catch (err) {}
      document.documentElement.classList.add('sapi-suggest-off');
      return;
    }
    var btn = e.target.closest('.sapi-cart-suggest .sapi-suggest__add');
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

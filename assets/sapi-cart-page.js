/**
 * Page panier (WooCommerce Blocks) : ampoules et vitrine (inc/sapi-cart-page.php).
 *
 * OPTION A (25/11/2026) : une ampoule reste TOUJOURS une ligne de panier
 * ordinaire. Seul son emplacement à l'affichage change :
 *  - si UN SEUL luminaire du panier l'a parmi ses ampoules, sa ligne est
 *    masquée et remplacée sous lui par une sous-ligne (− / + / Retirer agissent
 *    sur la ligne de l'ampoule via le store du panier Blocks) ;
 *  - si plusieurs luminaires la partagent, elle reste une ligne normale,
 *    suivie de « Pour Gaston et Olivia » ;
 *  - un luminaire sans ampoule compatible au panier garde la languette
 *    « Ampoule non incluse · + Ajouter ».
 * Rien n'est synchronisé automatiquement : s'il manque des ampoules, « + n ».
 * Sécurité : une ligne d'ampoule n'est masquée que si sa sous-ligne est
 * affichée juste au-dessus. Sans store Blocks lisible, rien n'est masqué.
 *
 * La vitrine « Ça irait bien avec… » est déplacée dans la mise en page du
 * panier (.wc-block-components-sidebar-layout), que le CSS passe en grille.
 */
(function () {
  'use strict';
  var cfg = window.sapiCartPage || {};
  var amp = cfg.amp || {}; // par clé de ligne luminaire : id, ids, first, name, price, img

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function decode(s) { var t = document.createElement('textarea'); t.innerHTML = s || ''; return t.value; }
  function rowFor(key) {
    var rows = document.querySelectorAll('.wc-block-cart-items__row');
    for (var i = 0; i < rows.length; i++) if (rows[i].getAttribute('data-cart-item-key') === key) return rows[i];
    return null;
  }
  function storeItems() {
    var sel = window.wp && wp.data && wp.data.select && wp.data.select('wc/store/cart');
    var data = sel && typeof sel.getCartData === 'function' ? sel.getCartData() : null;
    return data && data.items && data.items.length ? data.items : null;
  }
  function dispatcher() {
    return window.wp && wp.data && wp.data.dispatch && wp.data.dispatch('wc/store/cart');
  }
  function money(item, qty) {
    var p = item.prices || {};
    var mu = typeof p.currency_minor_unit === 'number' ? p.currency_minor_unit : 2;
    var v = (parseInt(p.price, 10) || 0) * qty / Math.pow(10, mu);
    var str = v.toFixed(mu).replace('.', p.currency_decimal_separator || ',');
    return (p.currency_prefix || '') + str + (p.currency_suffix || '');
  }
  function names(list) {
    list = list.filter(function (n, i) { return list.indexOf(n) === i; }); // « Gaston et Gaston » -> « Gaston »
    return list.length < 2 ? list.join('') : list.slice(0, -1).join(', ') + ' et ' + list[list.length - 1];
  }

  /**
   * Répartition des ampoules du panier d'après le store :
   * attached[lumKey] = item ampoule, hidden[ampKey], shared[ampKey] = [lumKeys],
   * covered[lumKey], need[ampKey] = quantité de luminaires concernés.
   */
  function groups(items) {
    /* Même règle que sapi_cart_amp_groups() (volet, PHP) : rattachement par
       élimination, puis le reste partagé, avec un seul manque global. */
    var g = { attached: {}, hidden: {}, shared: {}, covered: {}, need: {}, missing: {}, byKey: {} };
    var lums = Object.keys(amp).filter(function (k) { return items.some(function (it) { return it.key === k; }); });
    items.forEach(function (it) { g.byKey[it.key] = it; });
    var users = {};
    items.forEach(function (it) {
      if (amp[it.key]) return; // c'est un luminaire
      var u = lums.filter(function (k) { return (amp[k].ids || []).map(Number).indexOf(Number(it.id)) !== -1; });
      if (u.length) users[it.key] = u;
    });
    function libres(u) { return u.filter(function (k) { return !g.attached[k]; }); }
    var done = {}, progress;
    do {
      progress = false;
      Object.keys(users).forEach(function (ak) {
        if (done[ak]) return;
        var free = libres(users[ak]);
        if (free.length === 1) {
          g.attached[free[0]] = g.byKey[ak];
          g.hidden[ak] = true;
          g.need[ak] = g.byKey[free[0]].quantity;
          done[ak] = true; progress = true;
        } else if (!free.length) {
          done[ak] = true; // ampoule en plus : ligne normale, sans mention
        }
      });
    } while (progress);
    var lumsPartages = {}, have = 0, first = null;
    Object.keys(users).forEach(function (ak) {
      if (done[ak]) return;
      var free = libres(users[ak]);
      g.shared[ak] = free;
      free.forEach(function (k) { g.covered[k] = true; lumsPartages[k] = true; });
      have += g.byKey[ak].quantity;
      if (first === null) first = ak;
    });
    if (first !== null) {
      var total = Object.keys(lumsPartages).reduce(function (s, k) { return s + g.byKey[k].quantity; }, 0);
      g.missing[first] = { miss: total - have, total: total };
    }
    return g;
  }

  /** « Il manque 1 ampoule pour vos 2 luminaires. » + bouton « Ajouter 1 ampoule ». */
  function hintHtml(ampItem, miss, total) {
    if (!(miss > 0)) return '';
    var pl = miss > 1 ? 's' : '';
    return '<span class="sapi-amp-hint">Il manque ' + miss + ' ampoule' + pl + ' pour vos ' + total + ' luminaires. ' +
      '<button type="button" class="sapi-amp-act" data-act="set" data-key="' + esc(ampItem.key) + '" data-qty="' + (ampItem.quantity + miss) + '">Ajouter ' +
      miss + ' ampoule' + pl + '</button></span>';
  }
  var CHEV = '<svg class="sapi-amp-bubble__chev" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true"><path d="M2 3.5l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>';
  function shortPrice(t) { return String(t || '').replace(/[,.]00(?=\D*$)/, ''); }
  // Ampoule choisie dans le panneau, par ligne luminaire (sinon la recommandée).
  var chosen = {};
  function chosenOf(key) {
    var a = amp[key], ids = (a && a.ids) || [];
    var c = chosen[key];
    return c && ids.indexOf(c) !== -1 ? c : a.id;
  }
  function tabHtml(key, a) {
    var ids = a.ids || [];
    var multi = ids.length > 1;
    var cur = chosenOf(key);
    var D = (window.sapiAmpPicker && window.sapiAmpPicker.data) || {};
    var name = cur !== a.id && D[cur] ? D[cur].name : decode(a.name);
    var price = cur !== a.id && D[cur] ? D[cur].priceHtml : a.price;
    var reco = !multi || cur === ids[0];
    var img = cur !== a.id && D[cur] && D[cur].img ? D[cur].img : a.img;
    return '<td colspan="3"><div class="sapi-amp-tab">' +
      (img ? '<img class="sapi-amp-tab__img" src="' + esc(img) + '" alt="" width="40" height="40" loading="lazy">' : '') +
      '<strong class="sapi-amp-tab__title">Ampoule non incluse</strong>' +
      '<span class="sapi-amp-split">' +
        '<button type="button" class="sapi-amp-tab__add" data-key="' + esc(key) + '">+ Ajouter l\'ampoule</button>' +
        '<button type="button" class="sapi-amp-bubble' + (reco ? ' is-reco' : '') + (multi ? '' : ' is-solo') + '" data-key="' + esc(key) + '"' + (multi ? ' aria-haspopup="dialog"' : ' tabindex="-1"') + '>' +
          '<span class="sapi-amp-bubble__name">' + esc(name) + '</span> ' +
          '<span class="sapi-amp-bubble__price">· ' + esc(shortPrice(price)) + '</span>' + CHEV +
        '</button>' +
      '</span>' +
      '</div></td>';
  }
  function subHtml(lumItem, a) {
    return '<td colspan="3"><div class="sapi-amp-sub">' +
      '<span class="sapi-amp-sub__check" aria-hidden="true">✓</span>' +
      '<p class="sapi-amp-sub__text"><strong>' + esc(decode(a.name)) + '</strong>' + hintHtml(a, lumItem.quantity - a.quantity, lumItem.quantity) + '</p>' +
      '<div class="sapi-amp-sub__qty">' +
        '<button type="button" class="sapi-amp-act" data-act="set" data-key="' + esc(a.key) + '" data-qty="' + (a.quantity - 1) + '" aria-label="Diminuer">−</button>' +
        '<span>' + a.quantity + '</span>' +
        '<button type="button" class="sapi-amp-act" data-act="set" data-key="' + esc(a.key) + '" data-qty="' + (a.quantity + 1) + '" aria-label="Augmenter">+</button>' +
      '</div>' +
      '<span class="sapi-amp-sub__price">' + esc(money(a, a.quantity)) + '</span>' +
      '<button type="button" class="sapi-amp-sub__remove sapi-amp-act" data-act="set" data-key="' + esc(a.key) + '" data-qty="0">Retirer</button>' +
      '</div></td>';
  }
  function sharedHtml(ampItem, users, g) {
    var list = users.map(function (k) { return amp[k].first; });
    return '<td colspan="3"><div class="sapi-amp-shared-row">' +
      '<span class="sapi-amp-shared">Pour ' + esc(names(list)) + '</span>' + (g.missing[ampItem.key] ? hintHtml(ampItem, g.missing[ampItem.key].miss, g.missing[ampItem.key].total) : '') +
      '</div></td>';
  }

  /** Place (ou remplace) la ligne ajoutée juste après `row`, seulement si son contenu a changé. */
  function place(row, forKey, kind, html) {
    var next = row.nextElementSibling;
    var tr = next && next.classList.contains('sapi-amp-row') ? next : null;
    var sig = kind + '|' + html;
    if (tr && tr.getAttribute('data-sig') === sig) return tr;
    if (!tr) {
      tr = document.createElement('tr');
      tr.className = 'sapi-amp-row';
      row.parentNode.insertBefore(tr, row.nextSibling);
    }
    tr.setAttribute('data-for', forKey);
    tr.setAttribute('data-kind', kind);
    tr.setAttribute('data-sig', sig);
    tr.innerHTML = html;
    return tr;
  }

  var busy = false;
  var working = false; // une action est en cours : on ne redessine pas sous le doigt
  var vitrine = document.querySelector('.sapi-cart-page-suggest');

  var lastHidden = {}; // lignes d'ampoule masquées au dernier calcul
  function sync() {
    if (working) {
      // Action en cours : on ne redessine rien, mais Blocks peut réécrire la
      // className d'une ligne (is-disabled) ; on remet le masquage tel quel.
      Object.keys(lastHidden).forEach(function (k) {
        var r = rowFor(k);
        if (r && !r.classList.contains('sapi-amp-hidden')) r.classList.add('sapi-amp-hidden');
      });
      return;
    }
    var items = storeItems();
    var g = items ? groups(items) : null;
    lastHidden = {};
    var want = {}; // clé de ligne -> true si une ligne ajoutée doit la suivre

    document.querySelectorAll('.wc-block-cart-items__row').forEach(function (row) {
      var key = row.getAttribute('data-cart-item-key');
      if (!key) return;
      var html = null, kind = null;
      if (g && g.byKey[key]) {
        if (amp[key]) {
          if (g.attached[key]) { kind = 'sub'; html = subHtml(g.byKey[key], g.attached[key]); }
          else if (!g.covered[key]) { kind = 'tab'; html = tabHtml(key, amp[key]); }
        } else if (g.shared[key]) {
          kind = 'shared'; html = sharedHtml(g.byKey[key], g.shared[key], g);
        }
      } else if (!g && amp[key] && !amp[key].has) {
        // Store illisible : proposition seulement si le serveur ne voit aucune
        // ampoule compatible au panier (évite un achat en double) ; rien n'est masqué
        kind = 'tab'; html = tabHtml(key, amp[key]);
      }
      if (html) { place(row, key, kind, html); want[key] = true; }
      // Ligne d'ampoule rangée sous son luminaire : masquée seulement si la sous-ligne est là
      var hide = false;
      if (g && g.hidden[key]) {
        Object.keys(g.attached).forEach(function (lk) {
          if (g.attached[lk].key !== key) return;
          var lrow = rowFor(lk);
          var sub = lrow && lrow.nextElementSibling;
          if (sub && sub.classList.contains('sapi-amp-row') && sub.getAttribute('data-kind') === 'sub') hide = true;
        });
      }
      if (hide) lastHidden[key] = true;
      if (row.classList.contains('sapi-amp-hidden') !== hide) row.classList.toggle('sapi-amp-hidden', hide);
    });
    // Lignes ajoutées orphelines ou périmées
    document.querySelectorAll('tr.sapi-amp-row').forEach(function (tr) {
      var key = tr.getAttribute('data-for');
      var row = rowFor(key);
      if (!row || row.nextElementSibling !== tr || !want[key]) tr.remove();
    });
    // Si une ligne masquée a perdu sa sous-ligne au passage, on la remontre
    document.querySelectorAll('.wc-block-cart-items__row.sapi-amp-hidden').forEach(function (row) {
      var ok = false;
      document.querySelectorAll('tr.sapi-amp-row[data-kind="sub"]').forEach(function (sub) {
        if (sub.innerHTML.indexOf('data-key="' + row.getAttribute('data-cart-item-key') + '"') !== -1) ok = true;
      });
      if (!ok) { row.classList.remove('sapi-amp-hidden'); delete lastHidden[row.getAttribute('data-cart-item-key')]; }
    });

    // Vitrine : placée dans la mise en page du panier, masquée si son luminaire source est parti
    var layout = document.querySelector('.sapi-cart-outer .wc-block-components-sidebar-layout');
    if (vitrine && layout && vitrine.parentNode !== layout) {
      layout.appendChild(vitrine);
      vitrine.classList.add('is-placed');
    }
    if (vitrine) {
      var src = vitrine.getAttribute('data-source-key');
      var anyRow = document.querySelector('.wc-block-cart-items__row');
      var hidden = !anyRow || (src ? !rowFor(src) : false);
      if (vitrine.hidden !== hidden) vitrine.hidden = hidden;
    }
  }
  function schedule() {
    if (busy) return;
    busy = true;
    requestAnimationFrame(function () { busy = false; sync(); });
  }

  function refreshHeader() {
    // En-tête et mini-panier (fragments classiques) : compteur et vitrine à jour
    if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
  }
  /* Bloque (ou débloque) tous les boutons de la languette d'ampoule : « + Ajouter l'ampoule »
     et la bulle ne doivent pas lancer deux ajouts en même temps. */
  function lockTab(btn, on) {
    var tab = btn.closest && btn.closest('.sapi-amp-tab');
    (tab ? Array.prototype.slice.call(tab.querySelectorAll('button')) : [btn]).forEach(function (b) { b.disabled = on; });
  }
  function fail(btn, label) {
    btn.textContent = 'Erreur';
    setTimeout(function () { btn.innerHTML = label; lockTab(btn, false); }, 2000);
  }
  var guardTimer = null;
  function done() { working = false; clearTimeout(guardTimer); schedule(); refreshHeader(); }
  // Filet : une réponse qui ne revient jamais ne doit pas figer l'affichage.
  // Un seul filet à la fois : celui d'une action terminée ne doit pas jouer
  // pendant la suivante.
  function guard() {
    clearTimeout(guardTimer);
    guardTimer = setTimeout(function () {
      if (!working) return;
      working = false;
      // Débloque les boutons et force le redessin des lignes ajoutées
      document.querySelectorAll('.sapi-amp-sub.is-busy, .sapi-amp-shared-row.is-busy').forEach(function (b) { b.classList.remove('is-busy'); });
      document.querySelectorAll('tr.sapi-amp-row').forEach(function (tr) { tr.removeAttribute('data-sig'); });
      schedule();
    }, 8000);
  }

  /* Ajoute une ampoule (par ID) en quantité qty, via le store Blocks ; `btn` affiche l'état. */
  function ajouterAmpoule(btn, ampId, qty) {
    if (btn.disabled || working) return; // un ajout est déjà en cours
    var label = btn.innerHTML; // la languette contient des balises (« Reco », prix)
    lockTab(btn, true);
    btn.textContent = 'Ajout…';
    var store = dispatcher();
    if (store && typeof store.addItemToCart === 'function') {
      working = true; guard();
      Promise.resolve(store.addItemToCart(ampId, qty)).then(done, function () { working = false; clearTimeout(guardTimer); fail(btn, label); });
      return;
    }
    if (!cfg.ajaxUrl) return fail(btn, label);
    var fd = new FormData();
    fd.append('product_id', ampId);
    fd.append('quantity', qty);
    fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && d.error) fail(btn, label); else location.reload(); })
      .catch(function () { fail(btn, label); });
  }
  function qtyOf(key) {
    var items = storeItems();
    var lum = items && items.filter(function (it) { return it.key === key; })[0];
    return lum ? lum.quantity : 1;
  }

  document.addEventListener('click', function (e) {
    // Bulle « Globe · 10 € ▾ » : panneau partagé. Le choix change la bulle et
    // l'ampoule que « + Ajouter l'ampoule » ajoutera, sans l'ajouter.
    var bub = e.target.closest && e.target.closest('.sapi-amp-bubble[data-key]');
    if (bub) {
      e.preventDefault();
      if (bub.classList.contains('is-solo') || bub.disabled || working || !window.sapiAmpPicker) return;
      var ck = bub.getAttribute('data-key');
      var ca = amp[ck];
      if (!ca) return;
      window.sapiAmpPicker.open({
        ids: ca.ids || [], recoId: (ca.ids || [])[0], pickedId: chosenOf(ck), first: ca.first,
        onPick: function (id) {
          chosen[ck] = id;
          // La ligne a pu être redessinée pendant que le panneau était ouvert :
          // on la retrouve par la clé du luminaire, pas par l'élément cliqué.
          var lum = rowFor(ck);
          var row = lum && lum.nextElementSibling;
          if (row && row.classList.contains('sapi-amp-row') && row.getAttribute('data-kind') === 'tab') {
            var html = tabHtml(ck, ca);
            row.innerHTML = html;
            row.setAttribute('data-sig', 'tab|' + html);
            var nb = row.querySelector('.sapi-amp-bubble');
            if (nb) nb.focus();
          } else {
            schedule();
          }
        }
      });
      return;
    }
    // « + Ajouter » de la languette
    var add = e.target.closest && e.target.closest('.sapi-amp-tab__add[data-key]');
    if (add && !add.disabled) {
      e.preventDefault();
      var key = add.getAttribute('data-key');
      var a = amp[key];
      if (!a) return;
      ajouterAmpoule(add, chosenOf(key), qtyOf(key));
      return;
    }
    // − / + / Retirer / « + n » : agissent sur la ligne de l'ampoule elle-même
    var btn = e.target.closest && e.target.closest('.sapi-amp-act');
    if (!btn || btn.disabled) return;
    e.preventDefault();
    var store2 = dispatcher();
    if (!store2) return;
    var ampKey = btn.getAttribute('data-key');
    var q = parseInt(btn.getAttribute('data-qty'), 10);
    if (isNaN(q)) return;
    var label = btn.textContent;
    var box = btn.closest('.sapi-amp-sub, .sapi-amp-shared-row');
    if (box) box.classList.add('is-busy');
    btn.disabled = true;
    working = true; guard();
    var p = q <= 0 ? store2.removeItemFromCart(ampKey) : store2.changeCartItemQuantity(ampKey, q);
    Promise.resolve(p).then(done, function () {
      working = false;
      clearTimeout(guardTimer);
      if (box) box.classList.remove('is-busy');
      fail(btn, label);
    });
  });

  sync();
  // Filet : si la mise en page du panier reste introuvable, la vitrine
  // s'affiche à sa place d'origine (sous le panier) plutôt que jamais.
  setTimeout(function () { if (vitrine && !vitrine.classList.contains('is-placed')) vitrine.classList.add('is-placed'); }, 4000);
  new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
  if (window.wp && wp.data && typeof wp.data.subscribe === 'function') wp.data.subscribe(schedule);
})();

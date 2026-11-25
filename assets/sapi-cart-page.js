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
    var g = { attached: {}, hidden: {}, shared: {}, covered: {}, need: {}, byKey: {} };
    var lums = Object.keys(amp).filter(function (k) { return items.some(function (it) { return it.key === k; }); });
    items.forEach(function (it) { g.byKey[it.key] = it; });
    items.forEach(function (it) {
      if (amp[it.key]) return; // c'est un luminaire
      var users = lums.filter(function (k) { return (amp[k].ids || []).map(Number).indexOf(Number(it.id)) !== -1; });
      if (!users.length) return;
      g.need[it.key] = users.reduce(function (s, k) { return s + (g.byKey[k] ? g.byKey[k].quantity : 0); }, 0);
      if (users.length === 1 && !g.attached[users[0]]) {
        g.attached[users[0]] = it;
        g.hidden[it.key] = true;
      } else {
        if (users.length > 1) g.shared[it.key] = users;
        users.forEach(function (k) { g.covered[k] = true; });
      }
    });
    return g;
  }

  function hintHtml(ampItem, need, label) {
    var miss = need - ampItem.quantity;
    if (miss <= 0) return '';
    return '<span class="sapi-amp-hint">' + esc(label) + ' <button type="button" class="sapi-amp-act" data-act="set" data-key="' +
      esc(ampItem.key) + '" data-qty="' + need + '">+ ' + miss + '</button></span>';
  }
  function tabHtml(key, a) {
    return '<td colspan="3"><div class="sapi-amp-tab">' +
      (a.img ? '<img class="sapi-amp-tab__img" src="' + esc(a.img) + '" alt="" width="40" height="40" loading="lazy">' : '') +
      '<p class="sapi-amp-tab__text"><strong>Ampoule non incluse.</strong> ' +
      '<span class="sapi-amp-tab__long">Celle que je recommande pour ' + esc(a.first) + ' : </span>' +
      esc(a.name) + ', ' + esc(a.price) + '</p>' +
      '<button type="button" class="sapi-amp-tab__add" data-key="' + esc(key) + '">+ Ajouter</button>' +
      '</div></td>';
  }
  function subHtml(lumItem, a) {
    var lq = lumItem.quantity;
    var label = lq + (lq > 1 ? ' luminaires, ' : ' luminaire, ') + a.quantity + (a.quantity > 1 ? ' ampoules' : ' ampoule');
    return '<td colspan="3"><div class="sapi-amp-sub">' +
      '<span class="sapi-amp-sub__check" aria-hidden="true">✓</span>' +
      '<p class="sapi-amp-sub__text"><strong>' + esc(decode(a.name)) + '</strong>' + hintHtml(a, lq, label) + '</p>' +
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
    var label = g.need[ampItem.key] + ' luminaires, ' + ampItem.quantity + (ampItem.quantity > 1 ? ' ampoules' : ' ampoule');
    return '<td colspan="3"><div class="sapi-amp-shared-row">' +
      '<span class="sapi-amp-shared">Pour ' + esc(names(list)) + '</span>' + hintHtml(ampItem, g.need[ampItem.key], label) +
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
  function fail(btn, label) {
    btn.textContent = 'Erreur';
    setTimeout(function () { btn.textContent = label; btn.disabled = false; }, 2000);
  }
  function done() { working = false; schedule(); refreshHeader(); }
  // Filet : une réponse qui ne revient jamais ne doit pas figer l'affichage
  function guard() {
    setTimeout(function () {
      if (!working) return;
      working = false;
      // Débloque les boutons et force le redessin des lignes ajoutées
      document.querySelectorAll('.sapi-amp-sub.is-busy, .sapi-amp-shared-row.is-busy').forEach(function (b) { b.classList.remove('is-busy'); });
      document.querySelectorAll('tr.sapi-amp-row').forEach(function (tr) { tr.removeAttribute('data-sig'); });
      schedule();
    }, 8000);
  }

  document.addEventListener('click', function (e) {
    // « + Ajouter » de la languette
    var add = e.target.closest && e.target.closest('.sapi-amp-tab__add[data-key]');
    if (add && !add.disabled) {
      e.preventDefault();
      var key = add.getAttribute('data-key');
      var a = amp[key];
      if (!a) return;
      var items = storeItems();
      var lum = items && items.filter(function (it) { return it.key === key; })[0];
      var qty = lum ? lum.quantity : 1;
      add.disabled = true;
      add.textContent = 'Ajout…';
      var store = dispatcher();
      if (store && typeof store.addItemToCart === 'function') {
        working = true; guard();
        Promise.resolve(store.addItemToCart(a.id, qty)).then(done, function () { working = false; fail(add, '+ Ajouter'); });
        return;
      }
      if (!cfg.ajaxUrl) return fail(add, '+ Ajouter');
      var fd = new FormData();
      fd.append('product_id', a.id);
      fd.append('quantity', qty);
      fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d && d.error) fail(add, '+ Ajouter'); else location.reload(); })
        .catch(function () { fail(add, '+ Ajouter'); });
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

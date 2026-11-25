/**
 * Option ampoule sur la fiche produit (cf. inc/sapi-ampoule-optin.php).
 *
 * 1. Déplace le bloc dans les add-ons, juste au-dessus du sous-total.
 * 2. Ajoute le prix de l'ampoule au sous-total affiché quand la case est cochée.
 * 3. Avec plusieurs ampoules, garde la case alignée sur le choix de la liste.
 * 4. (25/11/2026) La liste suit la variation : data-by-var donne la liste des
 *    variations qui ont la leur, data-default celle du produit.
 * 5. Avec plusieurs ampoules, la liste est masquée : le nom de l'ampoule devient
 *    un bouton qui ouvre le panneau partagé (assets/sapi-amp-picker.js). La
 *    liste reste dans le formulaire, c'est elle qui porte le choix.
 *
 * Le sous-total est calculé et réécrit par Product Add-Ons à chaque changement
 * (variation, add-on, quantité). On ne le recalcule pas : on repasse derrière
 * lui. Le montant d'origine est mémorisé sur l'élément (data-sapi-base) ; quand
 * Product Add-Ons réécrit le bloc, l'élément est neuf, donc la base aussi.
 * Sans add-ons sur la fiche, c'est le prix de la variation (juste au-dessus) qui est complété.
 */
(function ($) {
  'use strict';

  var form = document.querySelector('form.cart');
  var block = form && form.querySelector('.sapi-ampoule-addon');
  if (!block) return;
  var checkbox = block.querySelector('input[name="sapi_add_ampoule"]');
  var select = block.querySelector('select[name="sapi_ampoule_choice"]');
  var ampPrice = parseFloat(checkbox.getAttribute('data-price')) || 0;

  var model = block.querySelector('.sapi-ampoule-addon__model');
  var choose = block.querySelector('.sapi-ampoule-addon__choose');
  var chooseName = choose && choose.querySelector('.sapi-ampoule-addon__choose-name');
  var DATA = window.SAPI_AMPOULES || {};
  function parse(attr, fallback) {
    try { return JSON.parse(block.getAttribute(attr) || '') || fallback; } catch (e) { return fallback; }
  }
  var defaultIds = parse('data-default', []);
  var byVar = parse('data-by-var', {});
  var currentIds = defaultIds.slice();

  /* Reconstruit le choix pour une liste d'ampoules (celle de la variation, ou
     celle du produit). Liste vide : la ligne disparaît et la case se décoche. */
  function applyList(ids) {
    ids = (ids || []).filter(function (id) { return DATA[id]; });
    currentIds = ids;
    if (!ids.length) {
      block.hidden = true;
      if (checkbox.checked) { checkbox.checked = false; if (paoContainer && $) $(form).trigger('woocommerce-product-addons-update'); }
      schedule();
      return;
    }
    block.hidden = false;
    // On garde l'ampoule que le client a cochée si la nouvelle liste la contient,
    // sinon on prend la recommandée de la variation (la première).
    var cur = parseInt(checkbox.value, 10);
    var keep = checkbox.checked && ids.indexOf(cur) !== -1 ? cur : ids[0];
    // Ampoule cochée absente de la nouvelle variation : on décoche plutôt que de
    // laisser cochée une autre ampoule, à un autre prix, sans que le client le voie.
    if (checkbox.checked && keep !== cur) {
      checkbox.checked = false;
      if (paoContainer && $) $(form).trigger('woocommerce-product-addons-update');
    }
    if (select) {
      select.innerHTML = ids.map(function (id) {
        var a = DATA[id];
        return '<option value="' + id + '" data-price="' + a.price + '" data-price-html="' + String(a.priceHtml).replace(/"/g, '&quot;') + '"' + (id === keep ? ' selected' : '') + '>' +
          String(a.name).replace(/</g, '&lt;') + '</option>';
      }).join('');
      select.hidden = choose ? true : ids.length < 2;
    }
    if (model) { model.textContent = DATA[keep].name; model.hidden = ids.length > 1; }
    if (choose) choose.hidden = ids.length < 2;
    syncChoice();
    schedule();
  }

  // Plusieurs ampoules : la case porte l'ID et le prix de celle choisie dans la liste.
  function syncChoice() {
    if (!select || currentIds.length < 2) {
      var one = currentIds[0];
      if (one && DATA[one]) {
        checkbox.value = one;
        ampPrice = DATA[one].price || 0;
        var pe = block.querySelector('.sapi-ampoule-addon__price');
        if (pe) pe.textContent = '+' + DATA[one].priceHtml;
      }
      return;
    }
    var opt = select.options[select.selectedIndex];
    if (!opt) return;
    checkbox.value = opt.value;
    ampPrice = parseFloat(opt.getAttribute('data-price')) || 0;
    var priceEl = block.querySelector('.sapi-ampoule-addon__price');
    if (priceEl && opt.getAttribute('data-price-html')) priceEl.textContent = '+' + opt.getAttribute('data-price-html');
    if (chooseName) chooseName.textContent = opt.textContent;
  }
  syncChoice();

  // 1. Placement
  var paoContainer = form.querySelector('.wc-pao-addons-container');
  if (paoContainer) {
    // Juste après le dernier add-on. On ne se cale pas sur le bloc du sous-total :
    // Product Add-Ons le remplit après le chargement, il est souvent vide quand
    // ce script tourne, et la case finissait alors sous le prix.
    var addons = paoContainer.querySelectorAll(':scope > .wc-pao-addon');
    var lastAddon = addons.length ? addons[addons.length - 1] : null;
    if (lastAddon) {
      lastAddon.parentNode.insertBefore(block, lastAddon.nextSibling);
    } else {
      paoContainer.insertBefore(block, paoContainer.firstChild);
    }
  } else {
    // Sans add-ons : même place que sur une fiche qui en a, c'est-à-dire sous le
    // bloc de la variation (dimensions + prix) et au-dessus de la quantité.
    var priceZone = form.querySelector('.single_variation');
    if (priceZone && priceZone.parentNode) priceZone.parentNode.insertBefore(block, priceZone.nextSibling);
  }

  // 2. Prix
  function parseAmount(text) {
    var m = (text || '').replace(/[\s  ]/g, '').match(/(\d+(?:[.,]\d{1,2})?)/);
    return m ? parseFloat(m[1].replace(',', '.')) : NaN;
  }
  function formatAmount(n) {
    return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function quantity() {
    var q = form.querySelector('input.qty');
    var v = q ? parseInt(q.value, 10) : 1;
    return v > 0 ? v : 1;
  }
  function patch(amountEl, extra) {
    if (!amountEl) return;
    var base = amountEl.getAttribute('data-sapi-base');
    if (base === null) {
      base = String(parseAmount(amountEl.textContent));
      amountEl.setAttribute('data-sapi-base', base);
    }
    base = parseFloat(base);
    if (isNaN(base)) return;
    var target = base + extra;
    // On ne remplace que la partie chiffrée, le symbole et sa place restent ceux de WooCommerce.
    var walker = document.createTreeWalker(amountEl, NodeFilter.SHOW_TEXT);
    var node;
    while ((node = walker.nextNode())) {
      if (/\d/.test(node.nodeValue)) {
        node.nodeValue = node.nodeValue.replace(/\d[\d\s  .]*(?:,\d{1,2})?/, formatAmount(target));
        break;
      }
    }
  }
  function refresh() {
    var extra = checkbox.checked ? ampPrice * (paoContainer ? quantity() : 1) : 0;
    var amounts;
    if (paoContainer) {
      amounts = form.querySelectorAll('.wc-pao-subtotal-line .amount');
    } else {
      amounts = form.querySelectorAll('.woocommerce-variation-price .price ins .amount, .woocommerce-variation-price .price > .amount');
    }
    amounts.forEach(function (el) { patch(el, extra); });
  }

  var scheduled = false;
  function schedule() {
    if (scheduled) return;
    scheduled = true;
    setTimeout(function () { scheduled = false; refresh(); }, 0);
  }

  checkbox.addEventListener('change', function () {
    if (paoContainer && $) $(form).trigger('woocommerce-product-addons-update');
    schedule();
  });
  if (select) {
    select.addEventListener('change', function () {
      syncChoice();
      // Choisir une ampoule dans la liste, c'est la vouloir : on coche la case.
      checkbox.checked = true;
      if (paoContainer && $) $(form).trigger('woocommerce-product-addons-update');
      schedule();
    });
  }
  if ($) $(form).on('updated_addons show_variation found_variation', schedule);

  // 4. La liste suit la variation choisie
  if ($ && Object.keys(byVar).length) {
    $(form).on('found_variation', function (e, variation) {
      var vid = variation && variation.variation_id;
      applyList(byVar[vid] || defaultIds);
    });
    $(form).on('reset_data', function () { applyList(defaultIds); });
  }
  if (choose) {
    choose.addEventListener('click', function (e) {
      // Le bouton est dans le <label> de la case : le clic ne doit pas la cocher.
      e.preventDefault();
      e.stopPropagation();
      // Panneau absent : on rend la liste, pour que le choix reste possible.
      if (!window.sapiAmpPicker) {
        if (select) select.hidden = false;
        choose.hidden = true;
        return;
      }
      window.sapiAmpPicker.open({
        ids: currentIds,
        recoId: currentIds[0],
        pickedId: checkbox.checked ? parseInt(checkbox.value, 10) : null,
        first: block.getAttribute('data-first') || '',
        onPick: function (id) {
          if (select) { select.value = String(id); select.dispatchEvent(new Event('change', { bubbles: true })); }
        }
      });
    });
  }

  // Filet de sécurité : Product Add-Ons ou WooCommerce réécrivent ces zones sans
  // toujours déclencher d'événement (changement de quantité notamment).
  var watched = paoContainer ? paoContainer.querySelector('.product-addon-totals') : form.querySelector('.single_variation');
  if (watched && window.MutationObserver) {
    new MutationObserver(function (muts) {
      for (var i = 0; i < muts.length; i++) {
        if (muts[i].type === 'childList') { schedule(); return; }
      }
    }).observe(watched, { childList: true, subtree: true });
  }
  form.addEventListener('change', function (e) {
    if (e.target && e.target.matches('input.qty')) schedule();
  });

  // État de départ : liste de la variation déjà choisie (sinon du produit),
  // bouton ou nom selon le nombre d'ampoules. Si aucune de ces ampoules n'est
  // dans SAPI_AMPOULES, on garde le rendu du serveur plutôt que masquer la ligne.
  var vidInput = form.querySelector('input.variation_id');
  var vid0 = vidInput ? parseInt(vidInput.value, 10) : 0;
  var startIds = (vid0 && byVar[vid0]) || defaultIds;
  if (startIds.filter(function (id) { return DATA[id]; }).length) applyList(startIds);
  refresh();
})(window.jQuery);

/**
 * Option ampoule sur la fiche produit (cf. inc/sapi-ampoule-optin.php).
 *
 * 1. Déplace le bloc dans les add-ons, juste au-dessus du sous-total.
 * 2. Ajoute le prix de l'ampoule au sous-total affiché quand la case est cochée.
 *
 * Le sous-total est calculé et réécrit par Product Add-Ons à chaque changement
 * (variation, add-on, quantité). On ne le recalcule pas : on repasse derrière
 * lui. Le montant d'origine est mémorisé sur l'élément (data-sapi-base) ; quand
 * Product Add-Ons réécrit le bloc, l'élément est neuf, donc la base aussi.
 * Sans add-ons sur la fiche, c'est le prix de la variation qui est complété.
 */
(function ($) {
  'use strict';

  var form = document.querySelector('form.cart');
  var block = form && form.querySelector('.sapi-ampoule-addon');
  if (!block) return;
  var checkbox = block.querySelector('input[name="sapi_add_ampoule"]');
  var ampPrice = parseFloat(checkbox.getAttribute('data-price')) || 0;

  // 1. Placement
  var paoContainer = form.querySelector('.wc-pao-addons-container');
  if (paoContainer) {
    var totals = paoContainer.querySelector('.product-addon-totals');
    var anchor = totals ? totals.closest('.wc-pao-addons-container > *') : null;
    if (anchor) {
      paoContainer.insertBefore(block, anchor);
    } else {
      paoContainer.appendChild(block);
    }
  } else {
    var priceZone = form.querySelector('.single_variation');
    if (priceZone && priceZone.parentNode) priceZone.parentNode.insertBefore(block, priceZone);
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
  if ($) $(form).on('updated_addons show_variation found_variation', schedule);

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

  refresh();
})(window.jQuery);

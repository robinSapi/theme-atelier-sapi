/**
 * Cart Page — Sâpi sticky sidebar fallback
 *
 * Le CSS (.sapi-cart-outer .wc-block-components-sidebar) gère le sticky.
 * Ce JS est un filet de sécurité : il observe la fin de l'hydratation React
 * (suppression de is-loading) et force les propriétés via setProperty si besoin.
 */
(function () {
  'use strict';

  function sp(el, prop, val) {
    if (el) el.style.setProperty(prop, val, 'important');
  }

  function apply() {
    var sidebar = document.querySelector('.sapi-cart-outer .wc-block-components-sidebar');
    var layout  = document.querySelector('.sapi-cart-outer .wc-block-components-sidebar-layout');

    if (!sidebar || !layout) return false;

    sp(layout, 'align-items', 'flex-start');

    if (window.innerWidth > 768) {
      sp(sidebar, 'position', 'sticky');
      sp(sidebar, 'top', '2rem');
      sp(sidebar, 'align-self', 'flex-start');
    } else {
      sp(sidebar, 'position', 'static');
    }

    return true;
  }

  /**
   * Typographie française : espace insécable avant « : » dans les labels produit
   * WooCommerce Blocks rend "Matériau:" sans espace — on corrige après hydratation.
   */
  var colonTimer = null;
  function fixFrenchColons() {
    var labels = document.querySelectorAll(
      '.wc-block-components-product-details__name'
    );
    labels.forEach(function (el) {
      var text = el.textContent;
      // Déjà corrigé (contient espace insécable avant :) → on skip
      if (!text || /\u00a0:$/.test(text)) return;
      // Remplace ":" ou " :" en fin de texte par "\u00a0:" (espace insécable + :)
      if (/:$/.test(text.trim())) {
        el.textContent = text.replace(/\s*:\s*$/, '\u00a0:');
      }
    });
  }

  // Observe la suppression de is-loading = fin de l'hydratation React
  var cartBlock = document.querySelector('.wp-block-woocommerce-cart');
  if (cartBlock) {
    new MutationObserver(function (mutations, obs) {
      if (!cartBlock.classList.contains('is-loading')) {
        obs.disconnect();
        setTimeout(function () { apply(); fixFrenchColons(); }, 100);
        setTimeout(function () { apply(); fixFrenchColons(); }, 600);
        setTimeout(function () { apply(); fixFrenchColons(); }, 1500);
      }
    }).observe(cartBlock, { attributes: true, attributeFilter: ['class'] });

    // Observer continu pour les mises à jour dynamiques (quantité, etc.)
    // Debounce pour éviter la boucle infinie (notre modif DOM re-déclenche l'observer)
    new MutationObserver(function () {
      clearTimeout(colonTimer);
      colonTimer = setTimeout(fixFrenchColons, 200);
    }).observe(cartBlock, { childList: true, subtree: true });
  }

  // Fallback window.load
  window.addEventListener('load', function () {
    apply();
    fixFrenchColons();
    setTimeout(function () { apply(); fixFrenchColons(); }, 800);
  });

  // Resize
  window.addEventListener('resize', apply);
})();

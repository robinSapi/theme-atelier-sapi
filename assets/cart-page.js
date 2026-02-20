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

  // Observe la suppression de is-loading = fin de l'hydratation React
  var cartBlock = document.querySelector('.wp-block-woocommerce-cart');
  if (cartBlock) {
    new MutationObserver(function (mutations, obs) {
      if (!cartBlock.classList.contains('is-loading')) {
        obs.disconnect();
        setTimeout(apply, 100);
        setTimeout(apply, 600);
        setTimeout(apply, 1500);
      }
    }).observe(cartBlock, { attributes: true, attributeFilter: ['class'] });
  }

  // Fallback window.load
  window.addEventListener('load', function () {
    apply();
    setTimeout(apply, 800);
  });

  // Resize
  window.addEventListener('resize', apply);
})();

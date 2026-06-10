/**
 * Sapi Help Pill — Pill « Je t'aide à choisir la bonne version » sur fiche
 * produit (F2b). Texte statique (rendu côté PHP, identique quel que soit
 * l'état du projet). Au clic, ouvre la modale Conseiller (state="product") ;
 * sapi-modal-conseiller.js route ensuite vers s-product-recap ou le parcours
 * court selon le contenu du projet.
 */
(function () {
  'use strict';

  function init() {
    var pill = document.querySelector('[data-help-pill]');
    if (!pill) return;

    pill.addEventListener('click', function () {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', {
        detail: { state: 'product' }
      }));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

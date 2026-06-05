/**
 * Sâpi — Bandeau réassurance : randomisation mobile + cleanup localStorage legacy.
 *
 * - Sur écran ≤600px, masque 2 des 4 items réassurance au hasard (Fisher-Yates)
 *   pour économiser la largeur.
 * - Nettoie les clés localStorage `sapiGuidePrefs` / `sapiRobinPrefs` héritées
 *   de l'ancien Conseiller (F1c) — silencieux pour ne pas casser les visiteurs
 *   récurrents qui auraient encore ces clés.
 */
(function () {
  'use strict';

  function randomizeMobileReassurance() {
    if (window.innerWidth > 600) return;
    var items = document.querySelectorAll('#robin-bandeau .reassurance-item');
    if (items.length <= 2) return;

    var indices = [];
    for (var i = 0; i < items.length; i++) indices.push(i);

    // Fisher-Yates shuffle
    for (var j = indices.length - 1; j > 0; j--) {
      var k = Math.floor(Math.random() * (j + 1));
      var tmp = indices[j];
      indices[j] = indices[k];
      indices[k] = tmp;
    }

    var toHide = items.length - 2;
    for (var h = 0; h < toHide; h++) {
      items[indices[h]].classList.add('is-mobile-hidden');
    }
  }

  function cleanupLegacyConseillerStorage() {
    try {
      localStorage.removeItem('sapiGuidePrefs');
      localStorage.removeItem('sapiRobinPrefs');
    } catch (e) {
      // quota / privé : silencieux
    }
  }

  function init() {
    cleanupLegacyConseillerStorage();
    // Refonte #11 : les 2 items mobiles sont désormais FIXES (Livraison 48-72h +
    // Paiement sécurisé) via la classe .is-mobile-hidden dans le template.
    // Plus de randomisation.
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

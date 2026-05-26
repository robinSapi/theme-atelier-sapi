/**
 * Tracking GA4 sur /mes-creations/ — Chantier 5.
 *
 * 5 events via dataLayer.push (compatible GTM) avec fallback gtag :
 * - mes_creations_card_selection_click   : { product_id, piece, position }
 * - mes_creations_card_catalogue_click   : { product_id, product_cat, position }
 * - mes_creations_pill_categorie_click   : { categorie_slug }
 * - mes_creations_card_surmesure_click   : { piece }
 * - mes_creations_lien_modifier_projet_click : { piece }
 *
 * Délégation globale (1 listener à la racine document). Détection de la
 * provenance via .closest sur les sélecteurs DOM significatifs.
 */
(function () {
  'use strict';

  function push(eventName, params) {
    var payload = Object.assign({ event: eventName }, params || {});
    if (window.dataLayer && Array.isArray(window.dataLayer)) {
      window.dataLayer.push(payload);
    } else if (typeof window.gtag === 'function') {
      window.gtag('event', eventName, params || {});
    }
  }

  function getPiece() {
    if (window.sapiProject && typeof window.sapiProject.getAnswer === 'function') {
      return window.sapiProject.getAnswer('piece') || '';
    }
    return '';
  }

  function indexOfWithinSiblings(card) {
    var parent = card.parentElement;
    if (!parent) return 0;
    var siblings = parent.querySelectorAll('.product-card-cinetique, .mes-creations-surmesure-card');
    for (var i = 0; i < siblings.length; i++) {
      if (siblings[i] === card) return i + 1;
    }
    return 0;
  }

  function firstCategory(card) {
    var cats = (card.getAttribute('data-categories') || '').trim().split(/\s+/);
    return cats[0] || '';
  }

  document.addEventListener('click', function (e) {
    var target = e.target;

    // 1. Lien "Préciser ou modifier mon projet"
    var editLink = target.closest && target.closest('[data-mon-projet-edit]');
    if (editLink) {
      push('mes_creations_lien_modifier_projet_click', {
        piece: getPiece(),
      });
      return;
    }

    // 2. Card sur-mesure dans la sélection
    var surmesureCard = target.closest && target.closest('.mes-creations-surmesure-card');
    if (surmesureCard) {
      push('mes_creations_card_surmesure_click', {
        piece: getPiece(),
      });
      return;
    }

    // 3. Pills catégorie
    var pill = target.closest && target.closest('[data-mes-creations-pills] .mes-creations-pill');
    if (pill) {
      push('mes_creations_pill_categorie_click', {
        categorie_slug: pill.getAttribute('data-cat') || '',
      });
      return;
    }

    // 4. Card produit — distinguer sélection (slot englobante) vs catalogue
    var productCard = target.closest && target.closest('.product-card-cinetique');
    if (productCard) {
      var productId = productCard.getAttribute('data-product-id') || productCard.getAttribute('data-id') || '';
      var inSelection = !!productCard.closest('[data-mes-creations-selection-grid]');
      if (inSelection) {
        push('mes_creations_card_selection_click', {
          product_id: productId,
          piece: getPiece(),
          position: indexOfWithinSiblings(productCard),
        });
      } else if (productCard.closest('#sapi-product-grid')) {
        push('mes_creations_card_catalogue_click', {
          product_id: productId,
          product_cat: firstCategory(productCard),
          position: indexOfWithinSiblings(productCard),
        });
      }
      return;
    }
  });
})();

/**
 * Product Name Formatter
 * Sépare le prénom (premier mot) du reste du nom de produit
 * Prénom = Montserrat gras, Article + nom = Square Peg
 */

(function() {
  'use strict';

  // Attendre que le DOM soit chargé
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    // Sélecteurs pour tous les noms de produits sur le site
    const selectors = [
      '.product-title-mobile',           // Page produit mobile
      '.product-title-v2',               // Page produit desktop
      '.carousel-product-name',          // Carousel homepage
      '.product-name',                   // Grille produits
      '.product-hero-name',              // Mini-carousel catégories
      '.bento-product-featured h3',      // Bento grid homepage
      '.product-name-small',             // Petites cartes bento
      '.quick-view-title'                // Quick view modal
    ];

    // Pour chaque sélecteur, formater les noms
    selectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      elements.forEach(element => {
        formatProductName(element);
      });
    });
  }

  /**
   * Formate un nom de produit en séparant prénom et article+nom
   * @param {HTMLElement} element - L'élément contenant le nom du produit
   */
  function formatProductName(element) {
    // Éviter de reformater si déjà formaté
    if (element.querySelector('.product-firstname')) {
      return;
    }

    const fullName = element.textContent.trim();
    if (!fullName) return;

    // Séparer le premier mot (prénom) du reste
    const words = fullName.split(' ');
    if (words.length < 2) {
      // Si un seul mot, tout mettre en Montserrat
      element.innerHTML = `<span class="product-firstname">${fullName}</span>`;
      return;
    }

    const firstName = words[0];
    const rest = words.slice(1).join(' ');

    // Créer le HTML formaté
    element.innerHTML = `<span class="product-firstname">${firstName}</span> <span class="product-restname">${rest}</span>`;
  }

  // Observer pour les nouveaux éléments ajoutés dynamiquement (AJAX, infinite scroll, etc.)
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      mutation.addedNodes.forEach(function(node) {
        if (node.nodeType === 1) { // Element node
          // Vérifier si le noeud ajouté ou ses enfants contiennent des noms de produits
          const selectors = [
            '.product-title-mobile',
            '.product-title-v2',
            '.carousel-product-name',
            '.product-name',
            '.product-hero-name',
            '.bento-product-featured h3',
            '.product-name-small',
            '.quick-view-title'
          ];

          selectors.forEach(selector => {
            if (node.matches && node.matches(selector)) {
              formatProductName(node);
            }
            const children = node.querySelectorAll ? node.querySelectorAll(selector) : [];
            children.forEach(child => formatProductName(child));
          });
        }
      });
    });
  });

  // Observer le body pour les changements
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
})();

/**
 * Product Name Formatter
 * Sépare le prénom (premier mot) du reste du nom de produit
 * Prénom = Montserrat gras, Article + nom = Square Peg
 */

(function() {
  'use strict';

  // Catégories dont les noms de produits doivent être formatés (prénom + nom)
  var allowedCats = ['suspensions', 'appliques', 'lampadaires', 'lampeaposer'];

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
      '.product-intro-title',            // Intro screen produit
      '.bento-product-featured h3',      // Bento grid homepage
      '.bento-hero .bento-title',        // Hero bestseller homepage
      '.product-name-small',             // Petites cartes bento
      '.quick-view-title',               // Quick view modal
      '.wc-block-components-product-name' // Panier + récap commande (WooCommerce Blocks)
    ];

    // Sélecteurs spéciaux mini-cart (nom + variation séparés)
    const miniCartSelectors = [
      '.mini-cart-item-name',
      '.sapi-thankyou-outer .product-name' // Page confirmation commande
    ];

    // Pour chaque sélecteur, formater les noms
    selectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      elements.forEach(element => {
        // Exclure les éléments de la page thankyou (traités par miniCartSelectors)
        if (element.closest('.sapi-thankyou-outer')) return;
        if (isInAllowedCategory(element)) {
          formatProductName(element);
        }
      });
    });

    // Pour le mini-cart, formatage spécial (sépare variation)
    miniCartSelectors.forEach(selector => {
      const elements = document.querySelectorAll(selector);
      elements.forEach(element => {
        formatMiniCartName(element);
      });
    });
  }

  /**
   * Vérifie si un élément appartient à une catégorie autorisée
   */
  function isInAllowedCategory(element) {
    // Vérifier le <li class="product product_cat-xxx"> parent
    var productEl = element.closest('li.product, .product');
    if (productEl) {
      var classes = productEl.className.split(/\s+/);
      var catClasses = classes.filter(function(c) { return c.indexOf('product_cat-') === 0; });
      if (catClasses.length > 0) {
        return catClasses.some(function(c) { return allowedCats.indexOf(c.replace('product_cat-', '')) !== -1; });
      }
    }

    // Sur une page catégorie, vérifier les classes du body (term-xxx)
    if (document.body.classList.contains('tax-product_cat')) {
      var bodyClasses = document.body.className.split(/\s+/);
      var termClasses = bodyClasses.filter(function(c) { return c.indexOf('term-') === 0; });
      if (termClasses.length > 0) {
        return termClasses.some(function(c) { return allowedCats.indexOf(c.replace('term-', '')) !== -1; });
      }
    }

    // Par défaut (homepage, panier, etc.) : formater
    return true;
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

    // Si l'élément contient un lien <a>, formater à l'intérieur du lien (préserve le href)
    const link = element.querySelector('a');
    const target = link || element;

    const fullName = target.textContent.trim();
    if (!fullName) return;

    // Séparer le premier mot (prénom) du reste
    const words = fullName.split(' ');
    if (words.length < 2) {
      // Si un seul mot, tout mettre en Montserrat
      target.innerHTML = `<span class="product-firstname">${fullName}</span>`;
      return;
    }

    const firstName = words[0];
    const rest = words.slice(1).join(' ');

    // Créer le HTML formaté à l'intérieur de la cible (lien ou élément direct)
    target.innerHTML = `<span class="product-firstname">${firstName}</span> <span class="product-restname">${rest}</span>`;
  }

  /**
   * Formate un nom de produit dans le mini-cart
   * Sépare "Vincent L'incandescent - Peuplier, 18 cm x 33cm" en :
   *   - Nom formaté (prénom + restname)
   *   - Variation sur une ligne séparée
   */
  function formatMiniCartName(element) {
    if (element.querySelector('.product-firstname')) return;

    const link = element.querySelector('a');
    const target = link || element;
    const fullText = target.textContent.trim();
    if (!fullText) return;

    // Séparer nom produit et attributs de variation sur " - "
    var productName = fullText;
    var variationText = '';
    var dashIndex = fullText.indexOf(' - ');
    if (dashIndex !== -1) {
      productName = fullText.substring(0, dashIndex).trim();
      variationText = fullText.substring(dashIndex + 3).trim();
    }

    // Formater le nom (prénom + restname)
    var words = productName.split(' ');
    var nameHTML = '';
    if (words.length < 2) {
      nameHTML = '<span class="product-firstname">' + productName + '</span>';
    } else {
      nameHTML = '<span class="product-firstname">' + words[0] + '</span> <span class="product-restname">' + words.slice(1).join(' ') + '</span>';
    }

    // Injecter dans le lien ou l'élément
    target.innerHTML = nameHTML;

    // Ajouter la variation après le lien (dans l'élément parent, pas dans le <a>)
    if (variationText) {
      var variationSpan = document.createElement('span');
      variationSpan.className = 'mini-cart-item-variation';
      variationSpan.textContent = variationText;
      element.appendChild(variationSpan);
    }
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
            '.product-intro-title',
            '.bento-product-featured h3',
            '.bento-hero .bento-title',
            '.product-name-small',
            '.quick-view-title',
            '.wc-block-components-product-name'
          ];

          selectors.forEach(selector => {
            if (node.matches && node.matches(selector)) {
              if (isInAllowedCategory(node)) formatProductName(node);
            }
            const children = node.querySelectorAll ? node.querySelectorAll(selector) : [];
            children.forEach(child => {
              if (isInAllowedCategory(child)) formatProductName(child);
            });
          });

          // Mini-cart : formatage spécial
          ['.mini-cart-item-name'].forEach(selector => {
            if (node.matches && node.matches(selector)) {
              formatMiniCartName(node);
            }
            const children = node.querySelectorAll ? node.querySelectorAll(selector) : [];
            children.forEach(child => {
              formatMiniCartName(child);
            });
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

/**
 * Pills catégorie sur /mes-creations/ — Chantier 3.
 *
 * Filtrage AJAX-less : toutes les cards sont déjà dans le DOM (rendues par
 * archive-product.php avec data-categories). Au clic sur pill, on toggle
 * .is-cat-filtered sur les cards qui ne matchent pas, et on met à jour
 * history.pushState avec ?product_cat=<slug>. Au reload avec param présent,
 * le PHP a déjà mis is-active sur la bonne pill et l'attribut data-categories
 * est lu côté JS au load pour ré-appliquer le filtre visuel.
 *
 * IMPORTANT : ne touche QUE la grille basse #sapi-product-grid. Les clones
 * de la card "Ma sélection" ne sont pas filtrés par les pills (CSS scopé
 * et JS sélectionne seulement les cards de #sapi-product-grid).
 */
(function () {
  'use strict';

  var pills = document.querySelectorAll('[data-mes-creations-pills] .mes-creations-pill');
  if (!pills.length) return;

  var grid = document.getElementById('sapi-product-grid');
  if (!grid) return;

  function applyFilter(catSlug) {
    var cards = grid.querySelectorAll('.product-card-cinetique');
    cards.forEach(function (card) {
      if (catSlug === 'all' || !catSlug) {
        card.classList.remove('is-cat-filtered');
        return;
      }
      var cats = (card.getAttribute('data-categories') || '').split(/\s+/);
      var matches = cats.indexOf(catSlug) !== -1;
      card.classList.toggle('is-cat-filtered', !matches);
    });
  }

  function setActive(catSlug) {
    pills.forEach(function (p) {
      p.classList.toggle('is-active', p.getAttribute('data-cat') === catSlug);
    });
  }

  function updateUrl(catSlug) {
    if (!window.history || !window.history.pushState) return;
    var url = new URL(window.location.href);
    if (catSlug === 'all' || !catSlug) {
      url.searchParams.delete('product_cat');
    } else {
      url.searchParams.set('product_cat', catSlug);
    }
    window.history.pushState({ cat: catSlug }, '', url.toString());
  }

  pills.forEach(function (pill) {
    pill.addEventListener('click', function () {
      var catSlug = pill.getAttribute('data-cat') || 'all';
      setActive(catSlug);
      applyFilter(catSlug);
      updateUrl(catSlug);
    });
  });

  // Au load : si une pill is-active n'est pas "all", appliquer le filtre.
  // (Le PHP a déjà mis la bonne classe is-active selon ?product_cat=.)
  var activePill = document.querySelector('[data-mes-creations-pills] .mes-creations-pill.is-active');
  if (activePill) {
    var initialCat = activePill.getAttribute('data-cat') || 'all';
    if (initialCat !== 'all') {
      applyFilter(initialCat);
    }
  }

  // Au back/forward, ré-applique le filtre depuis l'URL.
  window.addEventListener('popstate', function () {
    var url = new URL(window.location.href);
    var catFromUrl = url.searchParams.get('product_cat') || 'all';
    setActive(catFromUrl);
    applyFilter(catFromUrl);
  });
})();

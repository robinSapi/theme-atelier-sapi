/**
 * Sapi Hero Live — Met à jour le H1 du hero /mes-creations/ en live au
 * changement de sapiProject.answers.piece (F2a-quinquies).
 *
 * Source du mapping : SAPI_HERO_TITLES localisé depuis le helper PHP
 * sapi_get_hero_piece_titles() — identique au rendu PHP initial pour
 * cohérence stricte. Crossfade subtil ~250 ms (fade-out 125 + swap +
 * fade-in 125).
 */
(function () {
  'use strict';

  var config = window.SAPI_HERO_TITLES || {};
  var defaultTitle = config.default || 'Mes Créations';
  var pieces = config.pieces || {};

  function getTitleForPiece(piece) {
    if (piece && Object.prototype.hasOwnProperty.call(pieces, piece)) return pieces[piece];
    return defaultTitle;
  }

  var fadeTimer = null;

  function updateHeroTitle(el, newTitle) {
    if (!el) return;
    if (el.textContent === newTitle) return;
    if (fadeTimer) {
      clearTimeout(fadeTimer);
      fadeTimer = null;
    }
    // Garantit une transition prête (au cas où c'est le premier changement)
    el.style.transition = 'opacity 0.125s ease';
    el.style.opacity = '0';
    fadeTimer = setTimeout(function () {
      el.textContent = newTitle;
      el.style.opacity = '1';
      fadeTimer = null;
    }, 125);
  }

  function init() {
    var heroTitle = document.querySelector('[data-hero-title]');
    if (!heroTitle) return;
    if (!window.sapiProject || typeof window.sapiProject.subscribe !== 'function') return;

    window.sapiProject.subscribe(function (project) {
      var piece = project && project.answers && project.answers.piece;
      updateHeroTitle(heroTitle, getTitleForPiece(piece));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

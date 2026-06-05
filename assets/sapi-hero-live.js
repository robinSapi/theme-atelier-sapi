/**
 * Sapi Hero Live — Met à jour le hero /mes-creations/ en live au changement
 * de sapiProject.answers.piece :
 *
 *   1. H1 textContent — crossfade 250 ms (F2a-quinquies).
 *      Mapping piece → titre exposé via SAPI_HERO_TITLES (helper PHP
 *      sapi_get_hero_piece_titles, source unique partagée avec le rendu PHP
 *      initial).
 *
 *   2. Background image — crossfade ~400 ms vers une photo tirée au sort
 *      parmi celles déclarées dans le data-hero-photos JSON (chantier
 *      hero-dynamique, photos ACF par pièce sur la page boutique). Si la
 *      pièce n'a pas de photo dédiée (ou pas de pièce du tout), on retombe
 *      sur la clé 'default' (Bandeau-1.jpg ou champ ACF hero_default).
 *
 * Progressive enhancement : si sapiProject ou data-hero-photos est absent,
 * le module ne fait que ce qu'il peut (titre live seul, ou rien). Le rendu
 * PHP initial reste fonctionnel sans JS.
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

  // ── Titre H1 ──────────────────────────────────────────────────────────
  var titleFadeTimer = null;

  function updateHeroTitle(el, newTitle) {
    if (!el) return;
    if (el.textContent === newTitle) return;
    if (titleFadeTimer) {
      clearTimeout(titleFadeTimer);
      titleFadeTimer = null;
    }
    el.style.transition = 'opacity 0.125s ease';
    el.style.opacity = '0';
    titleFadeTimer = setTimeout(function () {
      el.textContent = newTitle;
      el.style.opacity = '1';
      titleFadeTimer = null;
    }, 125);
  }

  // ── Background photo ──────────────────────────────────────────────────
  function parseHeroPhotos(heroEl) {
    if (!heroEl) return null;
    var raw = heroEl.getAttribute('data-hero-photos');
    if (!raw) return null;
    try {
      var parsed = JSON.parse(raw);
      return (parsed && typeof parsed === 'object') ? parsed : null;
    } catch (e) {
      return null;
    }
  }

  function pickRandomFromList(list) {
    if (!list || !list.length) return '';
    if (list.length === 1) return list[0];
    return list[Math.floor(Math.random() * list.length)] || list[0];
  }

  function pickPhotoForPiece(photosMap, piece) {
    if (!photosMap) return '';
    // Pièce reconnue → tirage parmi ses photos
    if (piece && photosMap[piece] && photosMap[piece].length) {
      return pickRandomFromList(photosMap[piece]);
    }
    // Sinon : fallback sur 'default' (Bandeau-1.jpg ou champ ACF hero_default)
    if (photosMap['default'] && photosMap['default'].length) {
      return pickRandomFromList(photosMap['default']);
    }
    return '';
  }

  var pendingBgUrl = null;

  function swapHeroBackground(heroEl, newUrl) {
    if (!heroEl || !newUrl) return;
    if (heroEl.getAttribute('data-hero-bg-current') === newUrl) return;
    // Évite plusieurs swaps simultanés si l'utilisateur enchaîne les
    // changements de pièce — on garde uniquement la dernière demande.
    pendingBgUrl = newUrl;

    var img = new Image();
    img.onload = function () {
      if (pendingBgUrl !== newUrl) return; // une demande plus récente a pris la main
      var layer = document.createElement('div');
      layer.className = 'shop-hero-artisan__fade-layer';
      layer.style.backgroundImage = 'url("' + newUrl.replace(/"/g, '\\"') + '")';
      heroEl.appendChild(layer);
      // Force reflow puis active le fade-in via la classe.
      void layer.offsetHeight;
      layer.classList.add('is-active');
      setTimeout(function () {
        if (pendingBgUrl === newUrl) {
          heroEl.style.backgroundImage = 'url("' + newUrl.replace(/"/g, '\\"') + '")';
          heroEl.setAttribute('data-hero-bg-current', newUrl);
        }
        if (layer.parentNode) layer.parentNode.removeChild(layer);
      }, 450);
    };
    img.onerror = function () {
      if (pendingBgUrl === newUrl) pendingBgUrl = null;
    };
    img.src = newUrl;
  }

  // ── Init ──────────────────────────────────────────────────────────────
  function init() {
    var heroEl = document.querySelector('.shop-hero-artisan');
    var heroTitle = document.querySelector('[data-hero-title]');
    if (!heroEl && !heroTitle) return;
    if (!window.sapiProject || typeof window.sapiProject.subscribe !== 'function') return;

    var photosMap = parseHeroPhotos(heroEl);

    // Mémorise l'URL du background initial (rendue par PHP via style inline
    // ou via la règle CSS de base) pour éviter de re-swap vers la même.
    if (heroEl && heroEl.style && heroEl.style.backgroundImage) {
      var m = heroEl.style.backgroundImage.match(/url\(["']?([^"')]+)["']?\)/);
      if (m && m[1]) heroEl.setAttribute('data-hero-bg-current', m[1]);
    }

    // Hero immersif quand un projet existe : la classe --projet retire le
    // titre + l'overlay sombre et double la hauteur (cf. style.css). Piloté
    // ici car ce module est déjà le seul abonné à sapiProject côté hero.
    function applyProjectState() {
      if (!heroEl) return;
      var hasProject = !!(window.sapiProject &&
        typeof window.sapiProject.hasProject === 'function' &&
        window.sapiProject.hasProject());
      heroEl.classList.toggle('shop-hero-artisan--projet', hasProject);
    }

    // Helper : applique titre + photo pour une pièce donnée. Factorisé pour
    // être réutilisé au sync initial (lecture localStorage) ET sur chaque
    // notify subscribe (changement de projet).
    function applyForPiece(piece) {
      updateHeroTitle(heroTitle, getTitleForPiece(piece));
      if (heroEl && photosMap) {
        var newUrl = pickPhotoForPiece(photosMap, piece);
        if (newUrl) swapHeroBackground(heroEl, newUrl);
      }
    }

    // Sync initial — sapiProject.subscribe ne notifie QUE sur changement
    // d'état. Sans cette lecture, un visiteur récurrent qui arrive sur
    // /mes-creations/ sans ?piece= en URL (mais avec piece=salon stocké
    // en localStorage) reste sur Bandeau-1 jusqu'à ce qu'il modifie son
    // projet. On lit donc l'état courant au load et on applique tout de
    // suite. Si le PHP a déjà rendu le bon bg (cas ?piece= en URL),
    // swapHeroBackground court-circuite via data-hero-bg-current.
    if (typeof window.sapiProject.get === 'function') {
      try {
        var initial = window.sapiProject.get();
        var initialPiece = initial && initial.answers && initial.answers.piece;
        if (initialPiece) applyForPiece(initialPiece);
      } catch (e) { /* silent : le subscribe ci-dessous reprendra la main */ }
    }
    applyProjectState();

    window.sapiProject.subscribe(function (project) {
      var piece = project && project.answers && project.answers.piece;
      applyForPiece(piece);
      applyProjectState();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

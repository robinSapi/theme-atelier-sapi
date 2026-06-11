/**
 * Sapi — /mes-creations/ état B « immersion » (arrivée depuis le room-picker).
 *
 * Contrôleur du hero immersif. Le markup + la sélection pièce-level sont rendus
 * CÔTÉ SERVEUR (archive-product.php) ; ce script joue la chorégraphie d'entrée
 * (machine à écrire), l'affinage inline taille→style (stocké dans
 * window.sapiProject) et surtout la RÉVÉLATION PILOTÉE PAR LE SCROLL : le hero
 * est épinglé (sticky dans un track), et la progression du scroll (--reveal,
 * 0→1) floute la photo, fait remonter le texte et fait apparaître les cards.
 * Réversible (lié à la position de scroll). Header + bandeau = mécanisme home.
 *
 * S'auto-désactive si le hero n'est pas présent (pas de [data-immersion]).
 */
(function () {
  'use strict';

  var config = window.SAPI_IMMERSION || {};

  function clamp(v, a, b) { return Math.max(a, Math.min(b, v)); }

  function init() {
    var section = document.querySelector('[data-immersion]');
    if (!section) return; // pas en mode immersion
    var track = document.querySelector('[data-immersion-track]');

    var els = {
      sig:           section.querySelector('[data-immersion-sig]'),
      phrase:        section.querySelector('[data-immersion-phrase]'),
      phraseText:    '',
      phraseContent: section.querySelector('.mescreations-immersion__phrase-content'),
      describe:      section.querySelector('[data-immersion-describe]'),
      selection:     section.querySelector('[data-immersion-selection]'),
      slider:        section.querySelector('[data-immersion-slider]'),
      prev:          section.querySelector('[data-immersion-prev]'),
      next:          section.querySelector('[data-immersion-next]'),
      scrollhint:    section.querySelector('[data-immersion-scrollhint]')
    };
    if (els.phrase) els.phraseText = els.phrase.getAttribute('data-immersion-phrase-text') || '';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var typeTimer = null;
    var seqTimers = [];
    function later(fn, ms) { var t = setTimeout(fn, ms); seqTimers.push(t); return t; }

    var charSpans = [];
    var CHAR_DELAY = 34; // cadence d'apparition (ms/lettre) — un peu plus lente

    /* Construit le texte en spans lettre par lettre (mot = inline-block pour ne
       pas couper un mot en fin de ligne). Toutes les lettres sont présentes dès
       le départ (opacity 0) → la hauteur du cadre est réservée (aucun saut). */
    function buildChars(text) {
      charSpans = [];
      if (!els.phraseContent) return;
      els.phraseContent.innerHTML = '';
      if (!text) return;
      var words = text.split(' ');
      var frag = document.createDocumentFragment();
      words.forEach(function (word, wi) {
        var w = document.createElement('span');
        w.className = 'mescreations-immersion__word';
        for (var k = 0; k < word.length; k++) {
          var c = document.createElement('span');
          c.className = 'mescreations-immersion__char';
          c.textContent = word.charAt(k);
          w.appendChild(c);
          charSpans.push(c);
        }
        frag.appendChild(w);
        if (wi < words.length - 1) frag.appendChild(document.createTextNode(' '));
      });
      els.phraseContent.appendChild(frag);
    }

    /* Révèle chaque lettre en fondu, une à une (CSS transition opacity). */
    function revealChars(done) {
      if (reduceMotion || !charSpans.length) {
        charSpans.forEach(function (c) { c.classList.add('is-shown'); });
        done && done();
        return;
      }
      var i = 0;
      clearInterval(typeTimer);
      typeTimer = setInterval(function () {
        if (charSpans[i]) charSpans[i].classList.add('is-shown');
        i++;
        if (i >= charSpans.length) {
          clearInterval(typeTimer);
          done && done();
        }
      }, CHAR_DELAY);
    }

    /* Bouton « Décrire mon projet en détail » → ouvre la modale Conseiller
       (questionnaire complet) pour un produit plus adapté. */
    function openModale() {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', { detail: { state: 's0' } }));
    }
    if (els.describe) els.describe.addEventListener('click', openModale);

    /* Indices du bas cliquables : « Découvre ta sélection » → scrolle pour
       révéler la sélection ; « Voir le catalogue complet » → scrolle au
       catalogue. pointer-events activé seulement quand l'indice est visible
       (cf. applyScroll). */
    var hintRevealEl = section.querySelector('.mescreations-immersion__hint--reveal');
    var hintCatalogueEl = section.querySelector('.mescreations-immersion__hint--catalogue');
    function scrollToReveal() {
      if (!track) return;
      var trackTop = track.getBoundingClientRect().top + window.pageYOffset;
      window.scrollTo({ top: Math.round(trackTop + window.innerHeight), behavior: reduceMotion ? 'auto' : 'smooth' });
    }
    function scrollToCatalogue() {
      var cat = document.getElementById('mes-creations-catalogue');
      if (cat) cat.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    }
    if (hintRevealEl) hintRevealEl.addEventListener('click', scrollToReveal);
    if (hintCatalogueEl) hintCatalogueEl.addEventListener('click', scrollToCatalogue);

    /* ── Slider : flèches de part et d'autre, 1 card par clic. Les flèches sont
       masquées si tout tient (pas de débordement) et désactivées aux extrémités. ── */
    var sliderEl = els.slider, prevEl = els.prev, nextEl = els.next;
    // Position de scroll (offset gauche) de chaque card dans le slider = points
    // de snap. On scrolle PILE sur l'une d'elles → pas de re-snap, pas de saut.
    function cardOffsets() {
      if (!sliderEl) return [];
      var base = sliderEl.getBoundingClientRect().left - sliderEl.scrollLeft;
      var cards = sliderEl.querySelectorAll('.product-card-cinetique, .mescreations-immersion__pcard--sur');
      return [].slice.call(cards).map(function (c) {
        return Math.round(c.getBoundingClientRect().left - base);
      });
    }
    function scrollCards(dir) {
      var offs = cardOffsets();
      if (!offs.length) return;
      var cur = sliderEl.scrollLeft;
      var idx = 0, best = Infinity;
      offs.forEach(function (o, i) { var d = Math.abs(o - cur); if (d < best) { best = d; idx = i; } });
      var target = Math.max(0, Math.min(offs.length - 1, idx + dir));
      sliderEl.scrollTo({ left: offs[target], behavior: reduceMotion ? 'auto' : 'smooth' });
    }
    function updateArrows() {
      if (!sliderEl) return;
      var overflow = sliderEl.scrollWidth > sliderEl.clientWidth + 4;
      if (prevEl) {
        prevEl.hidden = !overflow;
        prevEl.disabled = sliderEl.scrollLeft <= 2;
      }
      if (nextEl) {
        nextEl.hidden = !overflow;
        nextEl.disabled = sliderEl.scrollLeft >= sliderEl.scrollWidth - sliderEl.clientWidth - 2;
      }
    }
    if (prevEl) prevEl.addEventListener('click', function () { scrollCards(-1); });
    if (nextEl) nextEl.addEventListener('click', function () { scrollCards(1); });
    if (sliderEl) {
      var navRaf = null;
      sliderEl.addEventListener('scroll', function () {
        if (navRaf) cancelAnimationFrame(navRaf);
        navRaf = requestAnimationFrame(updateArrows);
      }, { passive: true });
    }
    updateArrows();
    setTimeout(updateArrows, 600);  // recalage après mise en page / chargement images
    window.addEventListener('resize', updateArrows, { passive: true });

    /* ── Moment 2 (refonte filtrage) : à la FERMETURE de la modale Conseiller,
       window.sapiProject émet UNE notification (resume) avec les réponses
       finales. On re-filtre + classe CÔTÉ SERVEUR (même moteur que le
       chargement) et on remplace les cards du slider. On ignore « pièce seule »
       (état initial / modale sans affinage) et les répétitions identiques. ── */
    var lastAnswersSig = null;
    function refreshSelection(answers, sig) {
      if (!sliderEl || !config.ajaxUrl) return;
      var fd = new FormData();
      fd.append('action', 'sapi_immersion_selection');
      fd.append('nonce', config.nonce || '');
      fd.append('answers', JSON.stringify(answers || {}));
      fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          if (!json || !json.success || !json.data || typeof json.data.html !== 'string') return;
          lastAnswersSig = sig; // on ne « brûle » la signature qu'en cas de succès (retry possible si échec)
          var sur = sliderEl.querySelector('.mescreations-immersion__pcard--sur');
          [].slice.call(sliderEl.querySelectorAll('.product-card-cinetique')).forEach(function (c) {
            if (c.parentNode) c.parentNode.removeChild(c);
          });
          var tmp = document.createElement('div');
          tmp.innerHTML = json.data.html; // product-name-formatter (MutationObserver) reformate les noms
          [].slice.call(tmp.querySelectorAll('.product-card-cinetique')).forEach(function (c) {
            sliderEl.insertBefore(c, sur || null);
          });
          sliderEl.scrollLeft = 0;
          updateArrows();
        })
        .catch(function () {});
    }
    /* On écoute l'événement déterministe émis par la modale à CHAQUE fermeture
       (fin ou abandon), porteur des réponses finales. Fiable contrairement au
       subscribe sapiProject dont le notify dépend du flush pendingNotify du
       resume (cause du « ne se recharge pas tout le temps »). */
    document.addEventListener('sapi:conseiller-closed', function (e) {
      var answers = (e && e.detail && e.detail.answers) ? e.detail.answers : {};
      var refined = Object.keys(answers).filter(function (k) { return k !== 'piece' && answers[k]; });
      if (!refined.length) return; // que la pièce → pas d'affinage à re-filtrer
      var sig = JSON.stringify(answers);
      if (sig === lastAnswersSig) return;
      refreshSelection(answers, sig);
    });

    /* ── Header + bandeau : MÊME mécanisme que la home (front-page.php). Le
       bandeau global est déplacé juste après le track et reçoit
       .home-repositioned-bar (sticky sous le header). ── */
    var header = document.querySelector('.site-header');
    var band = document.querySelector('.robin-bandeau');
    if (band && track && track.parentNode) {
      track.parentNode.insertBefore(band, track.nextSibling);
      band.classList.add('home-repositioned-bar');
    }

    /* ── Révélation pilotée par le scroll (--reveal 0→1 via le track épinglé) +
       header opaque (comme la home : quand le bas du hero passe le haut). ── */
    var rafPending = false;
    function applyScroll() {
      rafPending = false;
      if (track) {
        var rect = track.getBoundingClientRect();
        // La révélation se termine après ~1 écran de scroll (innerHeight) ; le
        // reste de la zone épinglée (le track est plus haut) = PAUSE à --reveal 1.
        var p = clamp((-rect.top) / window.innerHeight, 0, 1);
        section.style.setProperty('--reveal', p.toFixed(4));
        if (els.selection) els.selection.style.pointerEvents = p > 0.45 ? 'auto' : 'none';
        // Indices cliquables seulement quand ils sont visibles (sinon ils
        // capteraient les clics par-dessus l'autre).
        if (hintRevealEl) hintRevealEl.style.pointerEvents = p < 0.4 ? 'auto' : 'none';
        if (hintCatalogueEl) hintCatalogueEl.style.pointerEvents = p >= 0.85 ? 'auto' : 'none';
        // Recalage des flèches quand la sélection se dévoile (le layout est sûr
        // à ce moment ; évite une mesure de débordement faussée au tout load).
        if (p > 0.05) updateArrows();
      }
      if (header) {
        header.classList.toggle('is-scrolled', section.getBoundingClientRect().bottom < 50);
      }
    }
    function onScroll() {
      if (!rafPending) { rafPending = true; requestAnimationFrame(applyScroll); }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    applyScroll();

    /* Verrou de scroll pendant la frappe (sinon le scroll déclenche la
       révélation avant la fin du texte). Libéré quand la machine à écrire finit. */
    function lockScroll() {
      document.documentElement.style.overflow = 'hidden';
      document.body.style.overflow = 'hidden';
    }
    function unlockScroll() {
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
    }

    /* ── Séquence d'entrée (au load) : pill → phrase qui s'écrit → question →
       hint. La révélation de la sélection, elle, se joue au scroll. ── */
    function playSequence() {
      buildChars(els.phraseText); // lettres présentes (opacity 0) → hauteur réservée
      var safety = null;
      if (!reduceMotion) {
        lockScroll();
        safety = later(unlockScroll, 9000); // filet de sécurité
      }
      later(function () { if (els.sig) els.sig.classList.add('is-in'); }, reduceMotion ? 0 : 300);
      later(function () {
        revealChars(function () {
          unlockScroll();
          if (safety) clearTimeout(safety);
          if (els.describe) {
            els.describe.hidden = false;
            later(function () { els.describe.classList.add('is-in'); }, reduceMotion ? 0 : 250);
          }
          later(function () { if (els.scrollhint) els.scrollhint.classList.add('is-in'); }, reduceMotion ? 0 : 650);
        });
      }, reduceMotion ? 0 : 900);
    }

    playSequence();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

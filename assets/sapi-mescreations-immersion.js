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
      affine:        section.querySelector('[data-immersion-affine]'),
      affineQ:       section.querySelector('[data-immersion-affine-q]'),
      affineChips:   section.querySelector('[data-immersion-affine-chips]'),
      refine:        section.querySelector('[data-immersion-refine]'),
      selection:     section.querySelector('[data-immersion-selection]'),
      slider:        section.querySelector('[data-immersion-slider]'),
      prev:          section.querySelector('[data-immersion-prev]'),
      next:          section.querySelector('[data-immersion-next]'),
      scrollhint:    section.querySelector('[data-immersion-scrollhint]')
    };
    if (els.phrase) els.phraseText = els.phrase.getAttribute('data-immersion-phrase-text') || '';

    var possessive = config.possessive || 'ta pièce';

    // La pièce est déjà connue (room-picker). On n'affine inline que taille puis
    // style (le strict nécessaire pour cibler la variation). Le reste du
    // questionnaire reste réservé à la modale.
    var questions = [
      { id: 'taille', q: 'Quelle taille fait ' + possessive + ' ?', chips: [
        { l: 'Petit', v: 'petite' }, { l: 'Standard', v: 'moyenne' },
        { l: 'Grand', v: 'grande' }, { l: 'Je ne sais pas', v: '' }
      ] },
      { id: 'style', q: 'Quel style pour ' + possessive + ' ?', chips: [
        { l: 'Moderne, tons clairs', v: 'moderne' },
        { l: 'Ancien, bois, tons chauds', v: 'ancien' },
        { l: 'Pas de préférence', v: '' }
      ] }
    ];
    var qIndex = 0;

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var typeTimer = null;
    var seqTimers = [];
    function later(fn, ms) { var t = setTimeout(fn, ms); seqTimers.push(t); return t; }

    /* Réserve la hauteur finale du texte DÈS LE DÉPART (mesure le texte complet
       puis vide) : le cadre a sa taille définitive avant la frappe → le bloc ne
       grandit jamais ligne par ligne, donc aucun saut pendant la lecture. */
    function reservePhraseHeight() {
      if (!els.phrase || !els.phraseContent) return;
      els.phraseContent.textContent = els.phraseText || '';
      els.phrase.style.minHeight = els.phrase.offsetHeight + 'px';
      els.phraseContent.textContent = '';
    }

    /* ── Machine à écrire (le cadre est déjà à sa hauteur finale, cf. ci-dessus) ── */
    function typewriter(text, done) {
      if (!els.phraseContent || !els.phrase) { done && done(); return; }
      if (reduceMotion || !text) {
        els.phraseContent.textContent = text || '';
        els.phrase.classList.add('is-done');
        els.phrase.style.minHeight = ''; // texte complet présent → hauteur naturelle
        done && done();
        return;
      }
      var i = 0;
      els.phrase.classList.remove('is-done');
      els.phraseContent.textContent = '';
      clearInterval(typeTimer);
      typeTimer = setInterval(function () {
        i++;
        els.phraseContent.textContent = text.slice(0, i);
        if (i >= text.length) {
          clearInterval(typeTimer);
          els.phrase.classList.add('is-done');
          els.phrase.style.minHeight = ''; // texte complet → on libère (responsive)
          done && done();
        }
      }, 26);
    }

    /* ── Affinage inline (stocke la réponse, passe à la suivante ; NE déclenche
       PAS la révélation — celle-ci est pilotée par le scroll). ── */
    function renderQuestion() {
      var q = questions[qIndex];
      if (!q || !els.affineChips) return;
      if (els.affineQ) els.affineQ.textContent = q.q;
      els.affineChips.innerHTML = '';
      q.chips.forEach(function (c) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'mescreations-immersion__chip';
        b.textContent = c.l;
        b.addEventListener('click', function () { answer(q.id, c.v, c.l, b); });
        els.affineChips.appendChild(b);
      });
    }

    function storeAnswer(id, value, label) {
      if (!value) return; // « Je ne sais pas » / « Pas de préférence » : on n'impose rien
      if (window.sapiProject && typeof window.sapiProject.update === 'function') {
        var patch = {}; patch[id] = value;
        var lpatch = {}; lpatch[id] = label || value;
        window.sapiProject.update(patch, lpatch);
      }
    }

    function answer(id, value, label, btn) {
      storeAnswer(id, value, label);
      if (els.affineChips) {
        [].slice.call(els.affineChips.children).forEach(function (c) { c.classList.remove('is-validated'); });
      }
      if (btn) btn.classList.add('is-validated');
      later(function () {
        qIndex++;
        if (qIndex < questions.length) {
          renderQuestion(); // la question suivante prend la place
        } else if (els.affine) {
          els.affine.hidden = true; // parcours fini ; la sélection se révèle au scroll
        }
      }, 420);
    }

    function openModaleRefine() {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', { detail: { state: 's3' } }));
    }
    if (els.refine) els.refine.addEventListener('click', openModaleRefine);

    /* ── Slider : flèches de part et d'autre, 1 card par clic. Les flèches sont
       masquées si tout tient (pas de débordement) et désactivées aux extrémités. ── */
    var sliderEl = els.slider, prevEl = els.prev, nextEl = els.next;
    function cardStep() {
      if (!sliderEl) return 0;
      var card = sliderEl.querySelector('.product-card-cinetique, .mescreations-immersion__pcard--sur');
      if (!card) return sliderEl.clientWidth;
      var cs = getComputedStyle(sliderEl);
      var gap = parseFloat(cs.columnGap || cs.gap || '18') || 18;
      return card.getBoundingClientRect().width + gap;
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
    if (prevEl) prevEl.addEventListener('click', function () {
      sliderEl.scrollBy({ left: -cardStep(), behavior: reduceMotion ? 'auto' : 'smooth' });
    });
    if (nextEl) nextEl.addEventListener('click', function () {
      sliderEl.scrollBy({ left: cardStep(), behavior: reduceMotion ? 'auto' : 'smooth' });
    });
    if (sliderEl) {
      var navRaf = null;
      sliderEl.addEventListener('scroll', function () {
        if (navRaf) cancelAnimationFrame(navRaf);
        navRaf = requestAnimationFrame(updateArrows);
      }, { passive: true });
    }
    updateArrows();
    window.addEventListener('resize', updateArrows, { passive: true });

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
        var total = track.offsetHeight - window.innerHeight;
        var p = total > 0 ? clamp((-rect.top) / total, 0, 1) : 0;
        section.style.setProperty('--reveal', p.toFixed(4));
        if (els.selection) els.selection.style.pointerEvents = p > 0.45 ? 'auto' : 'none';
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
      reservePhraseHeight();
      renderQuestion();
      var safety = null;
      if (!reduceMotion) {
        lockScroll();
        safety = later(unlockScroll, 7000); // filet de sécurité
      }
      later(function () { if (els.sig) els.sig.classList.add('is-in'); }, reduceMotion ? 0 : 300);
      later(function () {
        typewriter(els.phraseText, function () {
          unlockScroll();
          if (safety) clearTimeout(safety);
          later(function () { if (els.affine) els.affine.classList.add('is-in'); }, reduceMotion ? 0 : 250);
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

/**
 * Sapi — /mes-creations/ état B « immersion » (arrivée depuis le room-picker).
 *
 * Contrôleur de la séquence du hero immersif (machine à écrire, affinage
 * inline taille→style, révélation de la sélection). Le markup + la sélection
 * pièce-level sont rendus CÔTÉ SERVEUR (archive-product.php) ; ce script ne
 * fait que jouer la chorégraphie et stocker les réponses dans window.sapiProject.
 *
 * S'auto-désactive si le hero immersif n'est pas présent (body sans
 * .mescreations-immersion, pas de [data-immersion]). Voir functions.php
 * (sapi_mescreations_immersion_piece, body_class, enqueue) et le CSS
 * (.mescreations-immersion*).
 *
 * Étape 1 : affinage stocke les réponses + révèle la sélection pièce-level.
 * Le re-filtrage taille/style de la sélection (AJAX serveur) viendra ensuite.
 */
(function () {
  'use strict';

  var config = window.SAPI_IMMERSION || {};

  function init() {
    var section = document.querySelector('[data-immersion]');
    if (!section) return; // pas en mode immersion

    var els = {
      sig:        section.querySelector('[data-immersion-sig]'),
      phrase:     section.querySelector('[data-immersion-phrase]'),
      phraseText: '',
      phraseContent: section.querySelector('.mescreations-immersion__phrase-content'),
      affine:     section.querySelector('[data-immersion-affine]'),
      affineQ:    section.querySelector('[data-immersion-affine-q]'),
      affineChips:section.querySelector('[data-immersion-affine-chips]'),
      refine:     section.querySelector('[data-immersion-refine]'),
      cta:        section.querySelector('[data-immersion-cta]'),
      seeSel:     section.querySelector('[data-immersion-see-selection]'),
      seeAll:     section.querySelectorAll('[data-immersion-see-all]'),
      scrollhint: section.querySelector('[data-immersion-scrollhint]')
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

    /* ── Machine à écrire (instantanée, jamais de réécriture) ── */
    function typewriter(text, done) {
      if (!els.phraseContent) { done && done(); return; }
      if (reduceMotion || !text) {
        els.phraseContent.textContent = text || '';
        els.phrase.classList.add('is-done');
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
          done && done();
        }
      }, 26);
    }

    /* ── Affinage inline ── */
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
        } else {
          // toutes les questions répondues → la sélection se révèle
          if (els.affine) els.affine.hidden = true;
          openSelection();
          if (els.refine) { els.refine.hidden = false; requestAnimationFrame(function () { els.refine.classList.add('is-in'); }); }
        }
      }, 420);
    }

    /* ── Révélation de la sélection ── */
    function openSelection() {
      var sel = section.querySelector('[data-immersion-selection]');
      if (sel) sel.hidden = false;
      // 2 rAF pour que la transition CSS (opacity/blur) parte d'un état rendu
      requestAnimationFrame(function () {
        requestAnimationFrame(function () { section.classList.add('is-selection'); });
      });
    }

    function scrollToCatalogue() {
      var cat = document.getElementById('mes-creations-catalogue');
      if (cat) cat.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
    }

    function openModaleRefine() {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', { detail: { state: 's3' } }));
    }

    /* ── Header + bandeau : MÊME mécanisme que la home (front-page.php). Le
       bandeau global est déplacé juste après le hero et reçoit
       .home-repositioned-bar (sticky sous le header). Le header bascule
       .is-scrolled quand le bas du hero passe le haut du viewport. ── */
    var header = document.querySelector('.site-header');
    var band = document.querySelector('.robin-bandeau');
    if (band && section.parentNode) {
      section.parentNode.insertBefore(band, section.nextSibling);
      band.classList.add('home-repositioned-bar');
    }
    if (header) {
      var updateHeaderState = function () {
        var bottom = section.getBoundingClientRect().bottom;
        header.classList.toggle('is-scrolled', bottom < 50);
      };
      window.addEventListener('scroll', updateHeaderState, { passive: true });
      window.addEventListener('resize', updateHeaderState, { passive: true });
      updateHeaderState();
    }

    /* ── Câblage ── */
    if (els.seeSel) els.seeSel.addEventListener('click', openSelection);
    if (els.seeAll && els.seeAll.length) {
      [].slice.call(els.seeAll).forEach(function (b) { b.addEventListener('click', scrollToCatalogue); });
    }
    if (els.refine) els.refine.addEventListener('click', openModaleRefine);

    /* ── Séquence d'entrée ── */
    function playSequence() {
      renderQuestion();
      later(function () { if (els.sig) els.sig.classList.add('is-in'); }, reduceMotion ? 0 : 300);
      later(function () {
        typewriter(els.phraseText, function () {
          later(function () { if (els.affine) els.affine.classList.add('is-in'); }, reduceMotion ? 0 : 250);
          later(function () { if (els.cta) els.cta.classList.add('is-in'); }, reduceMotion ? 0 : 700);
          later(function () { if (els.scrollhint) els.scrollhint.classList.add('is-in'); }, reduceMotion ? 0 : 950);
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

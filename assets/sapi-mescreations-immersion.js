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
    var genericPhrase = els.phraseText; // conseil générique par pièce (repli si l'IA échoue)
    // Au chargement : si un commentaire IA personnalisé existe déjà pour CETTE
    // pièce (ex. après rechargement suite à un changement de pièce dans la
    // modale), on le tape d'emblée au lieu du générique.
    try {
      var proj0 = (window.sapiProject && window.sapiProject.get) ? window.sapiProject.get() : null;
      if (proj0 && typeof proj0.advice_text === 'string' && proj0.advice_text &&
          proj0.answers && proj0.answers.piece === (config.piece || '')) {
        els.phraseText = proj0.advice_text;
      }
    } catch (e) { /* swallow */ }

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

    /* ── Commentaire IA personnalisé (fin de questionnaire modale) ──
       La modale émet 'sapi:advice-loading' DÈS le début du calcul (encore
       ouverte) → on vide la phrase et on affiche un loader 3 points. Puis
       'sapi:advice-ready' avec le texte → on le tape (ou repli générique). */
    function showPhraseDots() {
      if (!els.phraseContent) return;
      clearInterval(typeTimer);
      charSpans = [];
      els.phraseContent.innerHTML =
        '<span class="mescreations-immersion__dots" role="status" aria-label="Robin rédige son conseil">' +
        '<span></span><span></span><span></span></span>';
    }
    function retypePhrase(text) {
      els.phraseText = text || '';
      buildChars(els.phraseText);
      revealChars();
    }
    var advicePending = false; // true entre 'advice-loading' et 'advice-ready' (questionnaire terminé)
    document.addEventListener('sapi:advice-loading', function () {
      advicePending = true;
      if (els.phraseContent) showPhraseDots();
    });
    document.addEventListener('sapi:advice-ready', function (e) {
      advicePending = false;
      var advice = (e && e.detail && typeof e.detail.advice === 'string') ? e.detail.advice.trim() : '';
      /* Révélation SIMULTANÉE (décision Robin) : la sélection a été préchargée
         pendant le calcul IA mais volontairement PAS affichée. On la libère ici,
         à l'instant où le conseil est prêt → le texte et les nouvelles cards
         arrivent ensemble, jamais la sélection en avance sur le conseil.
         Sûr par construction : fetchAdviceFromIA a un timeout de 25 s et un
         .catch qui résout à null → cet événement est TOUJOURS émis, échec IA
         compris. Aucun risque de sélection bloquée indéfiniment. */
      flushPendingSelection();
      if (!els.phraseContent) return;
      retypePhrase(advice || genericPhrase);
    });

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
    /* Renvoie, pour chaque card, LA POSITION DE SCROLL qui l'amène à sa place —
       pas son offset brut. Les deux diffèrent depuis que le slider est en
       pleine largeur sur mobile : la card y est CENTRÉE dans l'écran, alors
       qu'en desktop elle est calée à gauche.
       On ne teste pas la largeur de l'écran pour le savoir : on lit le
       `scroll-snap-align` que le CSS a effectivement appliqué à la card. Le
       comportement suit donc automatiquement la feuille de style, sans qu'un
       point de rupture soit dupliqué ici (et sans risque de désaccord entre
       les deux fichiers). */
    function cardOffsets() {
      if (!sliderEl) return [];
      var base = sliderEl.getBoundingClientRect().left - sliderEl.scrollLeft;
      var cards = sliderEl.querySelectorAll('.product-card-cinetique, .mescreations-immersion__pcard--sur');
      var port = sliderEl.clientWidth;
      return [].slice.call(cards).map(function (c) {
        var r = c.getBoundingClientRect();
        var off = r.left - base;
        var align = '';
        try { align = window.getComputedStyle(c).scrollSnapAlign || ''; } catch (e) { /* swallow */ }
        if (align.indexOf('center') === 0) off -= (port - r.width) / 2;
        return Math.max(0, Math.round(off));
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
    /* Baseline de dédup = ce que le SERVEUR a déjà rendu, c.-à-d. la sélection
       pour la pièce seule (archive-product.php : $imm_answers = ['piece' => …]).
       Ainsi « aucun changement » est détecté par la signature, et un changement
       de pièce (recommencer le projet) produit une signature différente → recharge. */
    var lastAnswersSig = JSON.stringify({ piece: config.piece || '' });

    /* Le fetch et l'AFFICHAGE sont volontairement séparés. Au moment 2 on veut
       précharger la sélection pendant que l'IA calcule (les deux attentes se
       recouvrent) mais ne la révéler qu'avec le conseil — d'où fetch d'un côté,
       apply de l'autre, reliés par pendingSelection. */
    function fetchSelectionHtml(answers) {
      if (!sliderEl || !config.ajaxUrl) return Promise.resolve(null);
      var fd = new FormData();
      fd.append('action', 'sapi_immersion_selection');
      fd.append('nonce', config.nonce || '');
      fd.append('answers', JSON.stringify(answers || {}));
      return fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          if (!json || !json.success || !json.data || typeof json.data.html !== 'string') return null;
          return json.data.html;
        })
        .catch(function () { return null; });
    }

    function swapCards(html) {
      var sur = sliderEl.querySelector('.mescreations-immersion__pcard--sur');
      [].slice.call(sliderEl.querySelectorAll('.product-card-cinetique')).forEach(function (c) {
        if (c.parentNode) c.parentNode.removeChild(c);
      });
      var tmp = document.createElement('div');
      tmp.innerHTML = html; // product-name-formatter (MutationObserver) reformate les noms
      [].slice.call(tmp.querySelectorAll('.product-card-cinetique')).forEach(function (c) {
        sliderEl.insertBefore(c, sur || null);
      });
      sliderEl.scrollLeft = 0;
      updateArrows();
    }

    /* Transition douce : fondu de sortie → swap pendant que le slider est
       invisible → fondu d'entrée (le remplacement sec des cards « flashait »). */
    function applySelectionHtml(html, sig) {
      if (!sliderEl || typeof html !== 'string') return;
      lastAnswersSig = sig; // signature « brûlée » seulement en cas de succès (retry possible sinon)
      sliderEl.style.transition = 'opacity .22s ease';
      sliderEl.style.opacity = '0';
      later(function () {
        swapCards(html);
        requestAnimationFrame(function () { sliderEl.style.opacity = '1'; });
      }, 220);
    }

    /* Sélection préchargée en attente du conseil IA : { promise, sig }. */
    var pendingSelection = null;
    function flushPendingSelection() {
      if (!pendingSelection) return;
      var p = pendingSelection;
      pendingSelection = null;
      p.promise.then(function (html) { applySelectionHtml(html, p.sig); });
    }

    /* Questionnaire terminé : l'appel IA vient de partir et la modale est encore
       ouverte (il reste ~1,9 s d'animation de sortie). On lance la sélection MAINTENANT
       pour que ses ~300 ms disparaissent dans cette fenêtre : au moment où le
       conseil arrive, les cards sont déjà prêtes et la révélation est instantanée. */
    document.addEventListener('sapi:advice-loading', function (e) {
      var answers = (e && e.detail && e.detail.answers) ? e.detail.answers : null;
      if (!answers || !answers.piece) return;
      // Pièce différente → la page sera rechargée : précharger ne servirait à rien.
      if (answers.piece !== (config.piece || '')) return;
      var sig = JSON.stringify(answers);
      if (sig === lastAnswersSig) return; // identique à ce qui est déjà affiché
      pendingSelection = { promise: fetchSelectionHtml(answers), sig: sig };
    });

    function refreshSelection(answers, sig) {
      fetchSelectionHtml(answers).then(function (html) { applySelectionHtml(html, sig); });
    }

    /* ── Chorégraphie rejouée à chaque affinage (demande produit de Robin) ──
       Sans ça, on restait scrollé sur l'ANCIENNE sélection pendant que l'IA
       calculait : on la donnait donc à voir alors qu'elle était déjà périmée. On
       remonte en haut du track → --reveal repasse à 0 → il ne reste que le décor,
       les 3 points de chargement et le futur texte en grand. Le visiteur re-scrolle
       ensuite pour découvrir la nouvelle sélection, exactement comme à l'arrivée.
       Effet de bord recherché : le swap des cards (sur 'advice-ready') se fait
       alors HORS ÉCRAN.

       ⚠️ Pourquoi ici et pas sur 'sapi:advice-loading' — qui serait pourtant
       « le déclenchement du recalcul » : à cet instant la modale tient encore le
       verrou de scroll (overflow:hidden sur html+body jusqu'à t+1100 ms de sa
       séquence de sortie) et un scrollTo n'aurait tout simplement aucun effet.
       'sapi:conseiller-closed' est émis après ce déverrouillage, et TOUJOURS avant
       'advice-ready' (dispatchConseillerClosed() précède finishAdvice() dans
       sapi-modal-conseiller.js) → l'ordre remontée-puis-révélation est garanti.

       On ne verrouille volontairement PAS le scroll pendant la frappe du conseil
       (contrairement à la séquence de chargement) : bloquer la page juste après une
       interaction se lit comme un plantage, et le verrou par overflow est de toute
       façon inopérant au toucher sur iOS. Au pire le visiteur scrolle pendant la
       frappe et découvre la nouvelle sélection un peu plus tôt — jamais l'ancienne. */
    var REWIND_ON_ABANDON = true; // abandon en cours de questionnaire = un affinage comme un autre
    function rewindToTop() {
      if (!track) return;
      var top = Math.round(track.getBoundingClientRect().top + window.pageYOffset);
      if (window.pageYOffset <= top + 2) return; // déjà en haut : rien à faire
      // Même appel que scrollToReveal (l'inverse de celui-ci), volontairement.
      window.scrollTo({ top: top, behavior: reduceMotion ? 'auto' : 'smooth' });
    }
    /* On écoute l'événement déterministe émis par la modale à CHAQUE fermeture
       (fin ou abandon), porteur des réponses finales. Fiable contrairement au
       subscribe sapiProject dont le notify dépend du flush pendingNotify du
       resume (cause du « ne se recharge pas tout le temps »). */
    document.addEventListener('sapi:conseiller-closed', function (e) {
      var answers = (e && e.detail && e.detail.answers) ? e.detail.answers : {};
      if (!answers.piece) return; // jamais sans pièce
      // Changement de pièce (le projet recommence sur une autre pièce) : on
      // recharge la page vers ?piece=<nouvelle> pour que le décor du hero
      // (photo, phrase, pill) ET la sélection restent cohérents.
      if (answers.piece !== (config.piece || '')) {
        var go = function () {
          try {
            var url = new URL(window.location.href);
            url.searchParams.set('piece', answers.piece);
            window.location.assign(url.toString());
          } catch (err) {
            window.location.search = '?piece=' + encodeURIComponent(answers.piece);
          }
        };
        if (advicePending) {
          // Questionnaire TERMINÉ : un commentaire IA est en cours de calcul.
          // On attend qu'il soit stocké dans sapiProject (juste avant
          // 'sapi:advice-ready') AVANT de recharger, pour que la nouvelle page
          // l'affiche d'emblée. Garde-fou : recharge quand même après 4 s.
          var reloaded = false;
          var reload = function () { if (reloaded) return; reloaded = true; go(); };
          document.addEventListener('sapi:advice-ready', reload, { once: true });
          /* Garde-fou porté de 4 s à 26 s : on attend le conseil DANS TOUS LES CAS
             (décision Robin). L'appel IA a lui-même un timeout de 25 s et résout
             toujours → ce délai n'est qu'un filet ultime si l'événement se perd,
             jamais le chemin normal. À 4 s il coupait une IA simplement lente et
             la nouvelle page repartait sans le conseil. */
          setTimeout(reload, 26000);
        } else {
          // Abandon (pas de commentaire IA) → rechargement immédiat.
          go();
        }
        return;
      }
      // Même pièce, seuls les affinages changent.
      var sig = JSON.stringify(answers);
      if (sig === lastAnswersSig) return; // identique à ce qui est déjà affiché

      /* La sélection VA changer → on rejoue la chorégraphie d'arrivée. Placé
         APRÈS le test de signature : si rien ne change, on ne bouge pas la page. */
      if (advicePending || REWIND_ON_ABANDON) rewindToTop();

      /* Questionnaire TERMINÉ : la sélection a déjà été préchargée sur
         'sapi:advice-loading' et attend le conseil. On ne l'affiche surtout pas
         ici — ce serait précisément la désynchronisation qu'on corrige (la
         sélection arrivait ~300 ms après la fermeture, le texte 1 à 4 s plus tard).
         C'est 'sapi:advice-ready' qui révèle les deux ensemble. */
      if (advicePending) {
        // Filet : si le préchargement n'a pas eu lieu (event sans réponses,
        // signature égale au moment du calcul…), on l'amorce maintenant. Il sera
        // libéré par advice-ready comme les autres.
        if (!pendingSelection) pendingSelection = { promise: fetchSelectionHtml(answers), sig: sig };
        return;
      }
      // Abandon en cours de questionnaire : pas d'appel IA, donc rien à attendre.
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

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
      revealBtn:     section.querySelector('[data-immersion-reveal-btn]'),
      selection:     section.querySelector('[data-immersion-selection]'),
      slider:        section.querySelector('[data-immersion-slider]'),
      prev:          section.querySelector('[data-immersion-prev]'),
      next:          section.querySelector('[data-immersion-next]'),
      dots:          section.querySelector('[data-immersion-dots]'),
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
    // Le bouton blanc fait ce que faisait l'indice du bas : descendre jusqu'à
    // la sélection. Il vise la position d'ancrage 2, donc il arrive pile.
    if (els.revealBtn) els.revealBtn.addEventListener('click', function () { scrollToReveal(); });

    /* Indices du bas cliquables : « Découvre ta sélection » → scrolle pour
       révéler la sélection ; « Voir le catalogue complet » → scrolle au
       catalogue. pointer-events activé seulement quand l'indice est visible
       (cf. applyScroll). */
    var hintRevealEl = section.querySelector('.mescreations-immersion__hint--reveal');
    var hintCatalogueEl = section.querySelector('.mescreations-immersion__hint--catalogue');
    /* Les deux indices visent EXACTEMENT les positions d'ancrage 2 et 3, et
       passent par `programmaticScrollTo` : sans ce drapeau, l'ancreur se
       déclencherait à la fin de leur animation et se battrait avec elle. */
    function scrollToReveal() {
      if (!track) return;
      var trackTop = track.getBoundingClientRect().top + window.pageYOffset;
      // Même distance que celle qui pilote --reveal : l'indice amène donc
      // EXACTEMENT à la fin de la révélation, pas à 90 % ni à 110 %.
      programmaticScrollTo(trackTop + revealSpan);
    }
    function scrollToCatalogue() {
      var y = catalogueSnapY();
      if (y != null) programmaticScrollTo(y);
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
    /* ── Dots ────────────────────────────────────────────────────────────
       Construits en JS parce que le nombre de cards varie selon la pièce ET
       change au « moment 2 » quand la sélection est remplacée. Un rendu PHP
       obligerait à les régénérer aussi côté serveur : deux sources pour la
       même chose. On les reconstruit donc après chaque swap.
       Ils partagent `cardOffsets()` avec les flèches → un dot cliqué amène la
       card exactement là où une flèche l'aurait amenée, centrée ou non selon
       ce que le CSS a décidé. */
    var dotsEl = els.dots;
    function buildDots() {
      if (!dotsEl || !sliderEl) return;
      var n = sliderEl.querySelectorAll('.product-card-cinetique, .mescreations-immersion__pcard--sur').length;
      // Un seul écran de cards = pas de dots (rien à naviguer).
      var overflow = sliderEl.scrollWidth > sliderEl.clientWidth + 4;
      dotsEl.innerHTML = '';
      if (!overflow || n < 2) { dotsEl.hidden = true; return; }
      dotsEl.hidden = false;
      var frag = document.createDocumentFragment();
      for (var i = 0; i < n; i++) {
        var b = document.createElement('button');
        b.type = 'button';
        b.className = 'mescreations-immersion__dot';
        b.setAttribute('data-immersion-dot', String(i));
        b.setAttribute('aria-label', 'Créations ' + (i + 1) + ' sur ' + n);
        frag.appendChild(b);
      }
      dotsEl.appendChild(frag);
      updateDots();
    }
    function currentIndex() {
      var offs = cardOffsets();
      if (!offs.length) return 0;
      var cur = sliderEl.scrollLeft, idx = 0, best = Infinity;
      offs.forEach(function (o, i) { var d = Math.abs(o - cur); if (d < best) { best = d; idx = i; } });
      return idx;
    }
    function updateDots() {
      if (!dotsEl || dotsEl.hidden) return;
      var idx = currentIndex();
      [].slice.call(dotsEl.children).forEach(function (d, i) {
        d.classList.toggle('is-active', i === idx);
      });
    }
    if (dotsEl) {
      dotsEl.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('[data-immersion-dot]') : null;
        if (!b) return;
        var offs = cardOffsets();
        var i = parseInt(b.getAttribute('data-immersion-dot'), 10);
        if (offs[i] == null) return;
        sliderEl.scrollTo({ left: offs[i], behavior: reduceMotion ? 'auto' : 'smooth' });
      });
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
      updateDots();
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
    buildDots();
    updateArrows();
    // Recalage après mise en page / chargement des images : c'est seulement à
    // ce moment que le débordement du slider est mesurable de façon fiable,
    // donc que l'on sait s'il faut des dots.
    setTimeout(function () { buildDots(); updateArrows(); }, 600);
    window.addEventListener('resize', function () { buildDots(); updateArrows(); }, { passive: true });

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
      buildDots(); // le nombre de cards a pu changer
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
      // Position d'ancrage 1. Passe par le drapeau : sinon l'ancreur se
      // déclencherait à la fin de cette remontée, au moment le plus
      // scénographié de la page.
      programmaticScrollTo(top);
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
    /* ── Sur quelle distance la révélation se joue ─────────────────────────
       Mesurée sur le REPÈRE posé en CSS (`[data-immersion-mark]`, à `top:
       100vh` dans le track), et non plus sur `window.innerHeight`.

       Pourquoi ça compte : sur iOS Safari, `window.innerHeight` SUIT la barre
       d'adresse (752px barre déployée) alors que le `100vh` du CSS l'IGNORE
       (838px). Le JS et le CSS n'étaient donc pas d'accord sur « où finit la
       révélation » — 86px d'écart, invisibles tant qu'ils tombent dans le
       plateau, mais qui deviendraient la position d'arrêt elle-même dès qu'on
       ancrera le scroll sur ce point : la page se calerait sur une révélation
       à ~90 %. En lisant le repère, les deux ne PEUVENT plus diverger.

       La distance ne dépend que de `vh`, donc elle est constante quoi que
       fasse la barre d'adresse : on la mesure au chargement et aux
       redimensionnements, pas à chaque image de scroll.
       Repli sur `window.innerHeight` si le repère manque (markup plus ancien). */
    var revealMark = track ? track.querySelector('[data-immersion-mark]') : null;
    var revealSpan = window.innerHeight;
    function measureRevealSpan() {
      if (!revealMark || !track) { revealSpan = window.innerHeight; return; }
      var d = revealMark.getBoundingClientRect().top - track.getBoundingClientRect().top;
      revealSpan = d > 40 ? d : window.innerHeight; // garde-fou : jamais 0
    }
    measureRevealSpan();

    var rafPending = false;
    function applyScroll() {
      rafPending = false;
      if (track) {
        var rect = track.getBoundingClientRect();
        // La révélation se termine sur le repère ; le reste de la zone
        // épinglée (le track est plus haut) = PAUSE à --reveal 1.
        var p = clamp((-rect.top) / revealSpan, 0, 1);
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
      scheduleSnap();
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', function () { measureRevealSpan(); onScroll(); }, { passive: true });
    applyScroll();
    // La mise en page n'est fiable qu'après le premier rendu (polices, images) :
    // on remesure une fois, comme pour le débordement du slider.
    setTimeout(function () { measureRevealSpan(); applyScroll(); }, 600);

    /* ══════════════════════════════════════════════════════════════════════
       ANCRAGE AU DÉFILEMENT — trois positions d'arrêt (demande Robin)
       ──────────────────────────────────────────────────────────────────────
       1. le haut du track  → le conseil en grand, rien de révélé
       2. le repère         → la révélation est finie, le carrousel est là
       3. le catalogue      → la section « tous les modèles », calée sous le header

       POURQUOI EN JS ET NON EN CSS. `scroll-snap-type` ferait ça en une ligne,
       mais il ne sait pas S'ABSTENIR — or il le faut dans quatre situations
       (verrou de la machine à écrire, modale ouverte, remontée du moment 2,
       geste qui a fait défiler le carrousel). Et il ne doit surtout pas viser
       la scène épinglée : un `sticky` est perpétuellement « déjà aligné » pour
       le moteur d'ancrage, ce qui donne blocage ou tremblement selon le
       navigateur. Ici les cibles sont des positions calculées, jamais un
       élément épinglé.

       ⚠️ LA RÈGLE D'ACCROCHE N'EST PAS LA MÊME PARTOUT, et c'est le cœur du
       réglage : DANS la zone de révélation, on accroche TOUJOURS vers l'une
       des deux extrémités — un état à moitié révélé n'est jamais un état
       voulu. AU-DELÀ, on laisse libre : le visiteur qui descend vers le
       catalogue ne doit jamais se sentir retenu, et le plateau après la
       révélation est de toute façon identique au pixel près, donc il n'y a
       rien à corriger. C'est cette asymétrie qui évite l'effet « page
       collante ».

       Mouvement réduit demandé par le système → aucun ancrage : déplacer la
       page sous quelqu'un qui a demandé moins d'animation serait à contresens.
       ══════════════════════════════════════════════════════════════════════ */
    var SNAP_IDLE = 150;        // ms d'immobilité avant de considérer le geste fini
    var SNAP_CATCH = 0.30;      // distance d'accroche au catalogue, en hauteurs d'écran
    var snapTimer = null;
    var touching = false;       // un doigt est posé
    var progScroll = false;     // un scroll programmatique est en vol
    var skipNextSnap = false;   // le geste a fait défiler le carrousel
    var gestureInSlider = false, sliderStartLeft = 0;
    var scrollDir = 1, lastY = window.pageYOffset; // sens du dernier mouvement : 1 = vers le bas

    /* Le verrou de scroll est posé par `overflow: hidden` sur html — par NOTRE
       machine à écrire ET par la modale Conseiller. Un seul test couvre donc
       les deux situations où il ne faut pas ancrer. */
    function scrollLocked() {
      return document.documentElement.style.overflow === 'hidden';
    }

    /* Saut instantané. `window.scrollTo(x, y)` ne suffit pas : `html` porte un
       `scroll-behavior: smooth` global (style.css l. 128) qui animerait même
       ce saut. On le neutralise le temps de l'appel. Sert à ANNULER une
       animation d'ancrage dès que le visiteur reprend la main. */
    function jumpTo(y) {
      var html = document.documentElement, prev = html.style.scrollBehavior;
      html.style.scrollBehavior = 'auto';
      window.scrollTo(0, y);
      html.style.scrollBehavior = prev;
    }
    /* Tout scroll que NOUS déclenchons passe par ici : le drapeau empêche
       l'ancreur de se déclencher à la fin de l'animation et de se battre avec
       elle (deux animations sur le même axe = rebond). */
    function programmaticScrollTo(y) {
      progScroll = true;
      window.scrollTo({ top: Math.round(y), behavior: reduceMotion ? 'auto' : 'smooth' });
      setTimeout(function () { progScroll = false; }, 700);
    }
    function cancelProgrammatic() {
      if (!progScroll) return;
      jumpTo(window.pageYOffset);
      progScroll = false;
    }

    /* Hauteur de tout ce qui reste COLLÉ EN HAUT de l'écran et masquerait le
       haut du catalogue : le header, ET le bandeau de réassurance, qui est
       repositionné en sticky sous le header sur cette page (cf. plus haut dans
       ce fichier). Le `scroll-margin-top` du CSS ne connaissait que le header
       — d'où le titre « Toutes mes créations » masqué par le bandeau à
       l'arrivée du 3ᵉ aimant (constaté par Robin).
       Mesuré plutôt qu'écrit en dur : les deux hauteurs diffèrent entre mobile
       et desktop, et le bandeau peut changer de contenu. */
    /* Où commence vraiment la zone lisible, sous tout ce qui reste collé en
       haut (le header, et le bandeau de réassurance repositionné en sticky sur
       cette page).

       ⚠️ ON NE SOMME PAS LES HAUTEURS, on prend le BORD BAS LE PLUS BAS.
       Première version : `hauteur du header + hauteur du bandeau`. Faux, parce
       que le bandeau ne se colle pas sous le header mais à une position qui lui
       est propre (`position: sticky; top: 80px`, style.css l. 1390) — et cette
       valeur ne vaut pas la hauteur réelle du header. On additionnait donc un
       chevauchement, d'où le calage « presque bon » signalé par Robin.
       Ici : pour chaque élément épinglé, `top` (là où il se fige) + sa hauteur
       = son bord bas ; on garde le plus bas. Exact quelles que soient les
       valeurs, en mobile comme en desktop. */
    function stickyOffset() {
      var off = 0;
      [document.querySelector('.site-header'), document.querySelector('.robin-bandeau')].forEach(function (el) {
        if (!el) return;
        var cs = null;
        try { cs = window.getComputedStyle(el); } catch (e) { return; }
        if (cs.position !== 'fixed' && cs.position !== 'sticky') return;
        var bottom = (parseFloat(cs.top) || 0) + el.getBoundingClientRect().height;
        if (bottom > off) off = bottom;
      });
      return Math.round(off);
    }
    function catalogueSnapY() {
      var cat = document.getElementById('mes-creations-catalogue');
      if (!cat) return null;
      var cssMargin = 0, marginTop = 0;
      try {
        var cs = window.getComputedStyle(cat);
        cssMargin = parseFloat(cs.scrollMarginTop) || 0;
        /* On cale la MARGE HAUTE du catalogue sous la barre, pas son bord.
           `.mes-creations-catalogue` porte `margin-top: 44px` : c'est l'air
           prévu par le design. Sans ça, le titre arrive collé à la barre —
           techniquement visible, mais « pas pile poil ». On préfère reprendre
           cette valeur plutôt qu'inventer un espacement de confort. */
        marginTop = parseFloat(cs.marginTop) || 0;
      } catch (e) { /* swallow */ }
      var offset = Math.max(cssMargin, stickyOffset()) + marginTop;
      return Math.round(cat.getBoundingClientRect().top + window.pageYOffset - offset);
    }
    /* Les trois positions se comportent comme les trois vues d'un diaporama :
       tant qu'on est DANS le hero, on est toujours attiré vers la plus proche
       des trois. Au-dessus du hero et une fois entré dans le catalogue, la
       page redevient libre — on ne retient jamais quelqu'un qui lit.

       ⚠️ C'EST CETTE RÈGLE, ET ELLE SEULE, QUI A RÉPARÉ L'AIMANT DU
       CATALOGUE. Il ne mordait pas parce qu'il n'accrochait que dans une
       fenêtre de 30vh, sur un trajet de 150vh : un geste normal la survolait.
       En visant « la plus proche des trois », la bascule se fait à mi-chemin
       (75vh), ce qu'une poussée normale dépasse largement.
       On a un temps cru qu'il fallait aussi RACCOURCIR le track pour
       rapprocher les étapes. Essayé (200vh), et refusé par Robin dans la
       minute : ça supprime la pause après la révélation et le hero amorce son
       départ au moindre geste. La longueur n'était pas le problème. */
    function snapTarget() {
      if (!track) return null;
      var y = window.pageYOffset;
      var top = Math.round(track.getBoundingClientRect().top + y);
      var pos2 = top + Math.round(revealSpan);
      var pos3 = catalogueSnapY();
      if (pos3 == null) pos3 = pos2;

      if (y < top - 8) return null;                                  // avant le hero : libre
      if (y > pos3 + window.innerHeight * SNAP_CATCH) return null;   // dans le catalogue : libre

      /* BIAIS DIRECTIONNEL — on suit le sens du geste au lieu de viser
         bêtement la position la plus proche.
         Sans lui, la bascule se ferait à mi-chemin ; avec une pause longue
         (le track fait 300vh, donc 200vh entre les positions 2 et 3), il
         faudrait pousser un écran entier avant que la page accepte d'aller au
         catalogue, et elle paraîtrait retenir. Ici, descendre suffit à
         atteindre l'étape suivante dès 35 % du trajet, et remonter joue la
         règle symétrique. C'est ce qui permet d'allonger la pause SANS rendre
         le catalogue difficile à atteindre — les deux réglages seraient sinon
         en opposition. */
      var stops = [top, pos2, pos3];
      var a = top, b = pos3;
      for (var i = 0; i < stops.length - 1; i++) {
        if (y >= stops[i] - 8 && y <= stops[i + 1] + 8) { a = stops[i]; b = stops[i + 1]; break; }
      }
      if (b - a < 8) return a;
      var t = (y - a) / (b - a);
      var forward = scrollDir >= 0;
      return (forward ? t > 0.35 : t > 0.65) ? b : a;
    }

    function maybeSnap() {
      if (skipNextSnap) { skipNextSnap = false; return; }
      if (reduceMotion || touching || progScroll || scrollLocked()) return;
      var target = snapTarget();
      if (target == null) return;
      if (Math.abs(target - window.pageYOffset) < 4) return; // déjà en place
      programmaticScrollTo(target);
    }
    function scheduleSnap() {
      // Sens du geste, mémorisé pour le biais directionnel de snapTarget().
      // Un mouvement d'un pixel ne compte pas : c'est du bruit de rendu.
      var y = window.pageYOffset;
      if (Math.abs(y - lastY) > 1) { scrollDir = y > lastY ? 1 : -1; lastY = y; }
      clearTimeout(snapTimer);
      snapTimer = setTimeout(maybeSnap, SNAP_IDLE);
    }

    document.addEventListener('touchstart', function (e) {
      touching = true;
      cancelProgrammatic(); // le doigt reprend toujours la main
      gestureInSlider = !!(sliderEl && e.target && sliderEl.contains(e.target));
      sliderStartLeft = sliderEl ? sliderEl.scrollLeft : 0;
    }, { passive: true });
    document.addEventListener('touchend', function () {
      touching = false;
      /* On n'ancre pas si le geste a réellement fait défiler le carrousel.
         Test sur le DÉPLACEMENT et non sur la cible du toucher : un doigt posé
         sur les cards qui tire la page vers le bas est un scroll vertical
         parfaitement légitime, et il doit s'ancrer comme les autres. */
      if (gestureInSlider && sliderEl && Math.abs(sliderEl.scrollLeft - sliderStartLeft) > 6) {
        skipNextSnap = true;
      }
      gestureInSlider = false;
      scheduleSnap();
    }, { passive: true });
    window.addEventListener('wheel', cancelProgrammatic, { passive: true });
    window.addEventListener('keydown', cancelProgrammatic);

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
          /* Le bouton blanc « Découvrir ma sélection » prend le rôle que
             tenait « Décrire mon projet » dans cette séquence : il apparaît
             une fois la phrase écrite. L'autre a migré au-dessus du
             carrousel, où c'est l'opacité de la zone sélection qui le révèle. */
          if (els.revealBtn) {
            els.revealBtn.hidden = false;
            later(function () { els.revealBtn.classList.add('is-in'); }, reduceMotion ? 0 : 250);
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

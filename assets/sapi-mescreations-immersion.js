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

  /* ⚠️ MIROIR EXACT DE `sapi_immersion_signature()` CÔTÉ PHP.
     La déduplication compare deux chaînes JSON : elle est donc sensible à
     l'ORDRE DES CLÉS. Tant que la signature n'avait qu'une clé, l'ordre ne se
     voyait pas ; à sept clés, deux ordres différents font échouer la
     comparaison À CHAQUE FOIS — sans rien casser de visible. Une requête part
     pour rien à chaque chargement, et la bonne sélection de l'URL se fait
     remplacer par l'ancien projet du visiteur.
     L'ordre canonique est celui du questionnaire, et il vit dans
     `sapi-project.js` — le seul fichier chargé partout où la question se pose.
     On le lui demande, on ne le recopie pas : ce serait une table de plus à
     faire diverger. */
  function signatureCanonique(answers) {
    answers = answers || {};
    var ordre = (window.sapiProject && window.sapiProject.clesProjet)
      ? window.sapiProject.clesProjet() : [];
    var out = {};
    ordre.forEach(function (k) { if (answers[k]) out[k] = answers[k]; });
    Object.keys(answers).forEach(function (k) { if (!(k in out) && answers[k]) out[k] = answers[k]; });
    return JSON.stringify(out);
  }

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

    /* ─────────────────────────────────────────────
       « J'ai compris, montre-moi » — sauter la frappe
       ─────────────────────────────────────────────
       Un clic, un début de scroll ou une touche de défilement pendant que la
       phrase s'écrit doit l'afficher ENTIÈRE d'un coup, bouton compris.

       ⚠️ ON N'INTERCEPTE RIEN. Aucun `preventDefault`, aucun `stopPropagation`,
       et l'écoute est passive là où le navigateur le permet. Un clic sur
       « Décrire mon projet en détail » termine donc le texte ET ouvre la
       modale — dans cet ordre, parce que `pointerdown` précède toujours
       `click`. L'ordre compte : le texte se termine (et déverrouille le
       défilement) AVANT que la modale ne pose son propre verrou. L'inverse
       déverrouillerait la page sous une modale déjà ouverte.
       C'est ce qui évite d'avoir à maintenir une liste d'exceptions : le jour
       où un bouton est ajouté au hero, il n'y a rien à mettre à jour ici.

       ⚠️ LE DÉFILEMENT EST FREINÉ, PAS ARRÊTÉ, pendant la séquence d'entrée.
       `lockScroll` pose `overflow:hidden`, ce qui suffit à la souris mais pas
       au toucher sur iOS (voir la note de `touch-action` plus bas). Dans les
       deux cas les événements `wheel` et `touchmove` sont émis, et c'est ce
       qui nous permet de détecter l'intention de faire défiler. Sans ça, le
       geste le plus naturel du visiteur pressé serait le seul qu'on ne verrait
       pas. */
    var GESTES_SAUT = ['pointerdown', 'wheel', 'touchmove', 'keydown'];
    /* Touches qui expriment « fais défiler » ou « vas-y ». Un Tab de navigation
       ou une lettre ne doit pas sauter la phrase.
       ⚠️ L'ESPACE ET ENTRÉE SE TAPENT AUSSI DANS LES CHAMPS. Ce sont les deux
       frappes les plus courantes d'une saisie, et la modale de contact a un
       `textarea`. D'où le filtre `champDeSaisie()` ci-dessous — le même que
       celui du carrousel plus bas dans ce fichier, pour la même raison. */
    var TOUCHES_SAUT = {
      ' ': 1, 'Spacebar': 1, 'PageDown': 1, 'PageUp': 1, 'End': 1, 'Home': 1,
      'ArrowDown': 1, 'ArrowUp': 1, 'Enter': 1
    };
    function champDeSaisie(cible) {
      if (!cible || !cible.tagName) return false;
      return /^(INPUT|TEXTAREA|SELECT)$/.test(cible.tagName) || cible.isContentEditable;
    }
    var sauteur = null; // l'écouteur armé, ou null

    /* ⚠️ RETENU POUR LE DÉVERROUILLAGE DU DÉFILEMENT, ET RIEN D'AUTRE.
       Un saut au pavé tactile arrive avec de l'inertie : si on déverrouille
       dans la foulée, la fin du geste emporte la page de plusieurs centaines
       de pixels, puis le recalage la ramène — le texte s'affiche, la page
       part, la page revient, et le bouton blanc s'efface au passage puisque
       son opacité suit la position de défilement. On diffère donc le
       déverrouillage le temps que le geste retombe. Un clic, lui, n'a pas
       d'inertie : il déverrouille tout de suite. */
    var sautParDefilement = false;

    function armerSaut(action) {
      desarmerSaut();
      sauteur = function (e) {
        if (e.type === 'keydown') {
          if (!TOUCHES_SAUT[e.key]) return;
          if (champDeSaisie(e.target)) return; // il écrit, il ne veut pas sauter
        }
        sautParDefilement = (e.type !== 'pointerdown');
        desarmerSaut();
        action();
      };
      GESTES_SAUT.forEach(function (type) {
        window.addEventListener(type, sauteur, { passive: true });
      });
    }

    function desarmerSaut() {
      if (!sauteur) return;
      GESTES_SAUT.forEach(function (type) {
        window.removeEventListener(type, sauteur);
      });
      sauteur = null;
    }

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
    /**
     * @param done        appelée UNE fois la phrase entièrement visible.
     * @param instantane  true = tout afficher sans animer (le visiteur a déjà
     *                    fait un geste avant même que la frappe ne démarre).
     */
    function revealChars(done, instantane) {
      desarmerSaut(); // une frappe qui recommence annule le saut de la précédente
      /* ⚠️ ON COUPE L'ANCIENNE FRAPPE AVANT TOUT, Y COMPRIS SUR LE CHEMIN
         INSTANTANÉ. Ce `clearInterval` était plus bas, donc réservé au chemin
         animé : une frappe relancée pendant qu'une autre tournait laissait
         l'ancien intervalle vivant. Il continuait de lire `charSpans` — une
         variable de module, donc DÉJÀ remplacée par le nouveau texte — puis
         atteignait sa fin et appelait son propre `terminer()`, qui désarmait
         le saut de la frappe en cours. Résultat : le clic ne faisait plus
         rien, sans la moindre erreur. */
      clearInterval(typeTimer);
      if (reduceMotion || instantane || !charSpans.length) {
        charSpans.forEach(function (c) { c.classList.add('is-shown'); });
        done && done();
        return;
      }
      var i = 0;

      /* ⚠️ `done` NE DOIT PARTIR QU'UNE FOIS — mais pas pour la raison qu'on
         imagine. JavaScript n'exécute qu'une chose à la fois : un saut et la
         dernière lettre ne peuvent pas se croiser, le premier arrivé désarme
         ou annule l'autre. Ce garde-fou couvre un cas moins visible : un
         intervalle ORPHELIN d'une frappe précédente qui atteindrait sa fin ici
         (voir le `clearInterval` ci-dessus). Il est bon marché et il rend
         `terminer()` sûr quel que soit l'appelant — on le garde. */
      var fini = false;
      function terminer() {
        if (fini) return;
        fini = true;
        clearInterval(typeTimer);
        typeTimer = null;
        desarmerSaut();
        done && done();
      }

      typeTimer = setInterval(function () {
        if (charSpans[i]) charSpans[i].classList.add('is-shown');
        i++;
        if (i >= charSpans.length) terminer();
      }, CHAR_DELAY);

      armerSaut(function () {
        charSpans.forEach(function (c) { c.classList.add('is-shown'); });
        terminer();
      });
    }

    /* ── Commentaire IA personnalisé (fin de questionnaire modale) ──
       La modale émet 'sapi:advice-loading' DÈS le début du calcul (encore
       ouverte) → on vide la phrase et on affiche un loader 3 points. Puis
       'sapi:advice-ready' avec le texte → on le tape (ou repli générique). */
    function showPhraseDots() {
      if (!els.phraseContent) return;
      /* ⚠️ PENDANT QUE ROBIN RÉFLÉCHIT, IL N'Y A RIEN À SAUTER. Un saut armé
         ici n'effacerait pas les points — il ne toucherait qu'à des lettres
         déjà retirées du DOM — mais il appellerait `done` en avance, et `done`
         c'est le bouton blanc. On le verrait donc réapparaître PENDANT que
         l'IA rédige, c'est-à-dire exactement ce que `hideRevealBtn` empêche
         trois lignes plus haut : proposer une sélection qui n'est pas encore
         la bonne. */
      desarmerSaut();
      clearInterval(typeTimer);
      charSpans = [];
      els.phraseContent.innerHTML =
        '<span class="mescreations-immersion__dots" role="status" aria-label="Robin rédige son conseil">' +
        '<span></span><span></span><span></span></span>';
    }
    function retypePhrase(text, done, instantane) {
      els.phraseText = text || '';
      buildChars(els.phraseText);
      revealChars(done, instantane);
    }
    /* Le bouton blanc suit le texte : il s'efface pendant que l'IA rédige, et
       ne revient qu'une fois la phrase ÉCRITE — exactement comme à l'arrivée
       sur la page. Le proposer pendant que les trois points tournent laisserait
       découvrir une sélection qui n'est pas encore la bonne. */
    function hideRevealBtn() { if (els.revealBtn) els.revealBtn.classList.remove('is-in'); }
    function showRevealBtn() { if (els.revealBtn) els.revealBtn.classList.add('is-in'); }
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
      if (!els.phraseContent) { showRevealBtn(); return; }
      retypePhrase(advice || genericPhrase, showRevealBtn);
    });

    /* Bouton « Décrire mon projet en détail » → ouvre la modale Conseiller
       (questionnaire complet) pour un produit plus adapté. */
    function openModale() {
      /* `trigger` : c'est ce bouton qui doit reprendre le focus quand la
         modale se referme. Voir la note dans sapi-modal-conseiller.js. */
      document.dispatchEvent(new CustomEvent('sapi:open-modal', {
        detail: { state: 's0', trigger: els.describe || null }
      }));
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
      // L'étape 2 est calculée sur la même distance que celle qui pilote
      // --reveal : l'indice amène EXACTEMENT à la fin de la révélation, pas à
      // 90 % ni à 110 %.
      /* ⚠️ ON LIT LES ÉTAPES, ON NE LES RECALCULE PAS. Elles sont en cache
         depuis le 29/08 : recalculer ici donnerait une cible FRAÎCHE, puis
         l'ancreur repasserait 150 ms plus tard avec la valeur EN CACHE et
         pousserait la page de quelques pixels. Un micro-saut juste après
         l'arrivée, sans cause visible. Passer par la même source garantit
         l'accord par construction.
         Et on dit à l'ancreur où l'on arrive, sinon il déduit une direction
         d'un index périmé. */
      /* ⚠️ `progScroll` ET NON `carouselBusy()` : celui-ci inclut le verrou de
         page, ce qui rendrait ces boutons morts derrière n'importe quel panneau
         ouvert. Ici on ne veut qu'une chose — respecter « l'animation va au
         bout » : un clic pendant un mouvement en cours est perdu, comme un
         geste. */
      if (progScroll) return;
      var stops = stopPositions();
      if (!stops) return;
      lastStopIndex = 1;
      programmaticScrollTo(stops[1]);
    }
    function scrollToCatalogue() {
      if (progScroll) return;
      var stops = stopPositions();
      if (!stops) return;
      lastStopIndex = 2;
      programmaticScrollTo(stops[2]);
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
    /* ⚠️ LA BASELINE VIENT DU SERVEUR, DANS SON ORDRE.
       Elle disait `{"piece":"salon"}` alors que la page pouvait être rendue à
       partir de six critères. Le rattrapage d'en dessous comparait donc une
       signature complète à une signature à une clé, concluait « ça a changé »,
       et REMPLAÇAIT la bonne sélection de l'URL par l'ancien projet du
       visiteur — en envoyant une requête pour rien à chaque visite.
       ⚠️ La comparaison est une égalité de chaînes JSON, donc sensible à
       l'ordre des clés. PHP calcule la signature dans l'ordre du questionnaire
       (`sapi_immersion_signature()`) et nous la transmet toute faite : c'est la
       seule façon d'être certain que les deux côtés produisent la même chaîne.
       Ne PAS la recalculer ici. */
    var lastAnswersSig = config.signature || JSON.stringify({ piece: config.piece || '' });

    /* ⚠️ APPELÉE ICI, ET PAS PLUS HAUT. Elle l'était AVANT la déclaration de
       `lastAnswersSig` : par hoisting, la baseline valait `undefined` au moment
       de l'appel, donc la comparaison de déduplication ne sortait JAMAIS. Une
       requête partait à chaque chargement, même quand le projet mémorisé était
       rigoureusement identique à ce que le serveur venait de rendre. Défaut
       antérieur à ce lot, mais c'est lui qui rend la comparaison utile. */
    refineFromStoredProject();
    buildDots();
    updateArrows();
    // Recalage après mise en page / chargement des images : c'est seulement à
    // ce moment que le débordement du slider est mesurable de façon fiable,
    // donc que l'on sait s'il faut des dots.
    setTimeout(function () { buildDots(); updateArrows(); }, 600);
    window.addEventListener('resize', function () { buildDots(); updateArrows(); oublierEtapes(); }, { passive: true });

    /* ── Moment 2 (refonte filtrage) : à la FERMETURE de la modale Conseiller,
       window.sapiProject émet UNE notification (resume) avec les réponses
       finales. On re-filtre + classe CÔTÉ SERVEUR (même moteur que le
       chargement) et on remplace les cards du slider. On ignore « pièce seule »
       (état initial / modale sans affinage) et les répétitions identiques. ── */
    /* Baseline de dédup = ce que le SERVEUR a déjà rendu, c.-à-d. la sélection
       pour la pièce seule (archive-product.php : $imm_answers = ['piece' => …]).
       Ainsi « aucun changement » est détecté par la signature, et un changement
       de pièce (recommencer le projet) produit une signature différente → recharge. */

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
          /* On rend le PAQUET COMPLET, plus seulement le HTML : le décor
             (titre, pill) sort du même calcul serveur que les cards et doit
             changer au même instant, sinon il les contredit. */
          return json.data;
        })
        .catch(function () { return null; });
    }

    /* Décor : le titre de la sélection et la pill de signature. Ils étaient
       gravés au rendu serveur et ne bougeaient plus, quoi qu'affiche ensuite
       le carrousel. Ils suivent maintenant la sélection. */
    var hookEl  = section.querySelector('[data-imm-hook]');
    var titleEl = section.querySelector('[data-imm-title]');
    function applyDecor(data) {
      if (!data) return;
      if (titleEl && data.title) titleEl.textContent = data.title;
      if (hookEl && data.possessive) hookEl.textContent = 'Mon conseil pour ' + data.possessive;
      if (data.title) section.setAttribute('aria-label', data.title);
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
      /* La hauteur des cards a pu changer (un nom sur deux lignes suffit), donc
         la position du catalogue aussi : les étapes en cache seraient périmées
         et l'ancreur viserait à côté. */
      oublierEtapes();
    }

    /* Transition douce : fondu de sortie → swap pendant que le slider est
       invisible → fondu d'entrée (le remplacement sec des cards « flashait »). */
    function applySelectionHtml(data, sig) {
      if (!sliderEl || !data || typeof data.html !== 'string') return;
      lastAnswersSig = sig; // signature « brûlée » seulement en cas de succès (retry possible sinon)
      sliderEl.style.transition = 'opacity .22s ease';
      sliderEl.style.opacity = '0';
      later(function () {
        swapCards(data.html);
        /* Le décor change DANS le même creux du fondu que les cards. À aucun
           instant l'écran ne montre un titre et des produits qui se contredisent. */
        applyDecor(data);
        requestAnimationFrame(function () { sliderEl.style.opacity = '1'; });
      }, 220);
    }

    /* ── AU CHARGEMENT : affiner la sélection avec TOUT le projet ──────────
       ⚠️ LA PAGE NE CONNAÎT QUE LA PIÈCE. Le rendu serveur part de
       `['piece' => $imm_piece]` et rien d'autre (archive-product.php) : c'est
       une sélection « niveau pièce », prévue pour être affinée ensuite par le
       moment 2, quand la modale se ferme sans recharger la page.
       Or depuis qu'un projet sans pièce peut RECHARGER vers `?piece=`, ce
       moment 2 n'a jamais lieu : la page arrive neuve, avec la seule pièce.
       Défaut constaté par Robin : le conseil disait « ma sélection d'appliques
       pour ton couloir » — juste, il tenait compte de la sortie murale — tandis
       que le slider affichait les suspensions génériques de l'entrée. Le texte
       et les images ne parlaient pas du même projet.
       On rejoue donc ici l'affinage, à partir du projet mémorisé, en réutilisant
       exactement le mécanisme du moment 2 (même endpoint, même dédup). Le
       carrousel est invisible à ce stade (`--reveal` à 0), donc le remplacement
       ne se voit pas.
       La signature de dédup fait le tri : si le projet ne contient que la
       pièce, elle est identique à la baseline et rien n'est demandé.
       ⚠️ Cette comparaison ne fonctionnait PAS avant le 27/08 : l'appel
       précédait la déclaration de `lastAnswersSig`, qui valait donc `undefined`
       par hoisting. Une requête partait à chaque chargement, même pour un
       projet rigoureusement identique. Ne pas remonter l'appel au-dessus de la
       baseline. */
    function refineFromStoredProject() {
      if (!sliderEl || !config.ajaxUrl) return;
      /* ⚠️ QUAND L'URL EN DIT PLUS QUE LA PIÈCE, ELLE FAIT AUTORITÉ.
         Ce rattrapage existe pour les liens qui ne portent qu'une pièce : les
         cartes-pièces de l'accueil, les newsletters, Pinterest. Là, le projet
         mémorisé est plus riche que l'adresse et mérite de compléter.
         Mais si l'adresse porte déjà plusieurs critères, elle est plus fraîche
         et plus juste que le localStorage : laisser le projet reprendre la main
         ferait se dégrader la page sous les yeux du visiteur, en quelques
         centaines de millisecondes, jusqu'à la sélection d'avant. */
      var reponsesUrl = config.answers || {};
      if (Object.keys(reponsesUrl).length > 1) return;
      var proj = null;
      try { proj = (window.sapiProject && window.sapiProject.get) ? window.sapiProject.get() : null; } catch (e) { return; }
      if (!proj || !proj.answers || !proj.answers.piece) return;
      if (proj.answers.piece !== (config.piece || '')) return; // autre pièce : pas notre affaire
      var sig = signatureCanonique(proj.answers);
      if (sig === lastAnswersSig) return; // rien de plus que la pièce
      fetchSelectionHtml(proj.answers).then(function (data) {
        applySelectionHtml(data, sig);
      });
    }

    /* Sélection préchargée en attente du conseil IA : { promise, sig }. */
    var pendingSelection = null;
    function flushPendingSelection() {
      if (!pendingSelection) return;
      var p = pendingSelection;
      pendingSelection = null;
      p.promise.then(function (data) { applySelectionHtml(data, p.sig); });
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
      var sig = signatureCanonique(answers); // même ordre que la baseline serveur
      if (sig === lastAnswersSig) return; // identique à ce qui est déjà affiché
      pendingSelection = { promise: fetchSelectionHtml(answers), sig: sig };

      /* Remontée IMMÉDIATE, pendant que la modale est encore à l'écran.
         Avant, on attendait sa fermeture (`sapi:conseiller-closed`, ~1,9 s plus
         tard) : le visiteur restait donc quelques secondes devant l'ANCIENNE
         sélection, déjà périmée. Robin : « ça fait bizarre ».
         On ne pouvait pas simplement scroller : la modale tient le verrou
         (`overflow: hidden` sur html+body) et un scrollTo n'aurait aucun effet.
         Mais elle COUVRE l'écran — donc on peut lever le verrou le temps d'un
         saut instantané, que personne ne voit, et le remettre aussitôt. Quand
         la modale s'efface, la page est déjà en haut, sur les trois points.
         Aucun mouvement visible : c'est mieux qu'une remontée animée. */
      hideRevealBtn();
      if (track) {
        var html = document.documentElement, body = document.body;
        var hPrev = html.style.overflow, bPrev = body.style.overflow;
        html.style.overflow = ''; body.style.overflow = '';
        jumpTo(Math.round(track.getBoundingClientRect().top + window.pageYOffset));
        /* ⚠️ DIRE À L'ANCREUR OÙ L'ON VIENT D'ATTERRIR. Depuis le 29/08 il
           mémorise l'étape au lieu de l'oublier en sortie de plage : sans cette
           ligne, quelqu'un qui ouvre la modale depuis le catalogue garde un
           index à 2, et l'ancreur conclut « il vient de remonter de deux
           crans » — il redescendrait la page sur le carrousel juste après ce
           saut, au moment le plus scénographié. */
        lastStopIndex = 0;
        html.style.overflow = hPrev; body.style.overflow = bPrev;
      }
    });

    function refreshSelection(answers, sig) {
      fetchSelectionHtml(answers).then(function (data) { applySelectionHtml(data, sig); });
    }

    /* ── Chorégraphie rejouée à chaque affinage (demande produit de Robin) ──
       Sans ça, on restait scrollé sur l'ANCIENNE sélection pendant que l'IA
       calculait : on la donnait donc à voir alors qu'elle était déjà périmée. On
       remonte en haut du track → --reveal repasse à 0 → il ne reste que le décor,
       les 3 points de chargement et le futur texte en grand. Le visiteur re-scrolle
       ensuite pour découvrir la nouvelle sélection, exactement comme à l'arrivée.
       Effet de bord recherché : le swap des cards (sur 'advice-ready') se fait
       alors HORS ÉCRAN.

       ⚠️ CE CHEMIN N'EST PLUS LE PRINCIPAL. Quand le questionnaire est TERMINÉ,
       la remontée se fait maintenant beaucoup plus tôt, d'un saut instantané et
       invisible derrière la modale, dès 'sapi:advice-loading' (voir ce
       listener). Ce qui reste ici sert :
         · à l'ABANDON en cours de questionnaire, où aucun conseil n'est calculé
           et où 'advice-loading' n'est donc jamais émis ;
         · de filet, si le saut invisible n'a pas eu lieu.
       Dans le cas terminé, `rewindToTop()` ne fait rien : on est déjà en haut,
       et le test ci-dessous sort immédiatement.

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
      // ⚠️ Et on lui dit où l'on arrive — même raison que pour le saut
      // invisible plus haut : un index resté à 2 le ferait redescendre.
      lastStopIndex = 0;
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
          /* ⚠️ ON RECONSTRUIT L'ADRESSE, ON NE LA RETOUCHE PAS.
             On ne remplaçait que `piece`, donc TOUS les autres critères de
             l'ancienne adresse survivaient. Depuis que le serveur les lit,
             ils redeviennent actifs : un visiteur qui passait d'un salon avec
             sortie murale à une cuisine au plafond rechargeait vers
             `?piece=cuisine&sortie=mur` — la sortie du salon. Le serveur
             servait des appliques, le projet fraîchement rempli était écrasé,
             et le conseil de Robin détruit avec lui.
             On efface donc TOUTES les clés du questionnaire, puis on écrit
             celles du projet courant, dans l'ordre canonique. Les paramètres
             étrangers (utm, fbclid) sont préservés. */
          try {
            var url = new URL(window.location.href);
            if (window.sapiProject && window.sapiProject.ecrireProjetDansUrl) {
              window.sapiProject.ecrireProjetDansUrl(url, answers);
            } else {
              /* ⚠️ REPLI INATTEIGNABLE (`sapi-project` est dépendance dure),
                 mais il ne doit PAS réintroduire le défaut d'origine. Se
                 contenter d'écrire `piece` laisserait survivre les critères de
                 l'ancienne pièce — exactement ce que ce bloc répare. On repart
                 donc d'une adresse propre : on perd les paramètres de campagne
                 dans ce cas de figure, ce qui vaut mieux qu'une sélection
                 fausse. */
              url.search = '';
              url.searchParams.set('piece', answers.piece);
            }
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
      var sig = signatureCanonique(answers); // même ordre que la baseline serveur
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
        /* ⚠️ LES CINQ SEUILS CI-DESSOUS SONT INDEXÉS SUR DES FORMULES CSS.
           Ils ont tous bougé le 29/08 avec le réétalement du fondu croisé
           (style.css, notes sur `--reveal`). Les toucher d'un seul côté produit
           des boutons invisibles mais cliquables, ou visibles mais morts —
           deux pannes qui ne se voient pas en relisant le code.
           0,50 : instant où le bouton « Découvrir » devient totalement
           transparent (1 - --reveal × 2). Le carrousel et « Décrire » prennent
           la main exactement là où lui la rend : aucun recouvrement. */
        if (els.selection) els.selection.style.pointerEvents = p > 0.5 ? 'auto' : 'none';
        /* ⚠️ LE BOUTON « DÉCRIRE » A BESOIN DU MÊME RELAIS, DEPUIS LE 29/08.
           Il vivait DANS la zone sélection et héritait de sa cliquabilité sans
           qu'aucune ligne ne le dise. Il en est sorti pour pouvoir remonter sur
           desktop — sans cette ligne il serait cliquable dès le chargement,
           invisible, en plein sur la phrase de Robin.
           Même seuil que la zone, pour que les deux deviennent cliquables au
           même instant. */
        if (els.describe) els.describe.style.pointerEvents = p > 0.5 ? 'auto' : 'none';
        /* ⚠️ ET IL FAUT COUPER LE FANTÔME DU BOUTON « DÉCOUVRE TA SÉLECTION ».
           Celui-ci s'efface en opacité mais GARDE SA PLACE dans le flux (voir sa
           note dans style.css) — et sa règle `.is-in` lui laisse
           `pointer-events: auto` pour toujours. Depuis que « Décrire » remonte
           À SON EMPLACEMENT en desktop, les deux boîtes se superposent : sans
           cette ligne, les clics partent dans un bouton invisible qui rescrolle
           vers une position déjà atteinte. Rien ne casse, rien ne bouge, et le
           bouton visible paraît simplement mort.
           ⚠️ Le `z-index: 4` posé côté CSS protège la partie RECOUVERTE ; ce
           qui reste à couvrir ici, c'est ce que le fantôme laisse DÉPASSER en
           bas — 49 px de haut contre 33 à 39 px pour le bouton visible, soit
           une bande morte de 10 à 16 px juste dessous. Les deux sont
           nécessaires, chacun pour une zone différente.
           Seuil 0,50 : son opacité tombe à zéro exactement là
           (clamp(0, 1 - --reveal * 2, 1)). On le neutralise dès qu'il est
           invisible, pas plus tard. Réglé sur 0,4 jusqu'au 29/08, quand son
           coefficient valait 2,6.
           La chaîne vide rend la main au CSS : en haut de page il redevient
           cliquable de lui-même, sans qu'on ait à réécrire 'auto'. */
        if (els.revealBtn) els.revealBtn.style.pointerEvents = p > 0.5 ? 'none' : '';
        // Indices cliquables seulement quand ils sont visibles (sinon ils
        // capteraient les clics par-dessus l'autre). 0,50 et 0,72 suivent leurs
        // formules CSS respectives, réétalées le 29/08.
        if (hintRevealEl) hintRevealEl.style.pointerEvents = p < 0.5 ? 'auto' : 'none';
        if (hintCatalogueEl) hintCatalogueEl.style.pointerEvents = p >= 0.72 ? 'auto' : 'none';
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
    window.addEventListener('resize', function () { measureRevealSpan(); oublierEtapes(); onScroll(); }, { passive: true });
    applyScroll();
    // La mise en page n'est fiable qu'après le premier rendu (polices, images) :
    // on remesure une fois, comme pour le débordement du slider.
    setTimeout(function () { measureRevealSpan(); oublierEtapes(); applyScroll(); }, 600);
    /* ⚠️ ET UNE FOIS LES POLICES POSÉES. C'est le SEUL contenu variable qui
       entre dans la position du catalogue : le bandeau de réassurance est en
       `flex-wrap`, et des métriques de police différentes le font passer sur
       deux lignes. Tout le reste des étapes est figé en `vh` par le CSS, donc
       insensible au contenu comme à la barre d'adresse mobile.
       Si la police se pose après le recalage de 600 ms ci-dessus, la 3e étape
       reste calculée sur la mise en page de repli et plus rien ne la corrige :
       le titre du catalogue arrive à moitié caché par le bandeau, ou la page
       tire le visiteur au-delà. Silencieux, et non reproductible à volonté
       puisque ça dépend du moment où la police arrive. */
    if (document.fonts && document.fonts.ready && typeof document.fonts.ready.then === 'function') {
      document.fonts.ready.then(function () {
        measureRevealSpan(); oublierEtapes(); applyScroll();
      });
    }

    /* ══════════════════════════════════════════════════════════════════════
       ANCRAGE AU DÉFILEMENT — trois positions d'arrêt (demande Robin)
       ──────────────────────────────────────────────────────────────────────
       1. le haut du track  → le conseil en grand, rien de révélé
       2. le repère         → la révélation est finie, le carrousel est là
       3. le catalogue      → la section « tous les modèles », calée sous le header

       POURQUOI EN JS ET NON EN CSS. `scroll-snap-type` ferait ça en une ligne,
       mais il ne sait pas S'ABSTENIR — or il le faut dans plusieurs situations
       (verrou de la machine à écrire, panneau ou modale ouverts, remontée du
       moment 2, déplacement que nous avons nous-mêmes lancé). Et il ne doit surtout pas viser
       la scène épinglée : un `sticky` est perpétuellement « déjà aligné » pour
       le moteur d'ancrage, ce qui donne blocage ou tremblement selon le
       navigateur. Ici les cibles sont des positions calculées, jamais un
       élément épinglé.

       ⚠️ LA RÈGLE, DEPUIS L'ARBITRAGE DE ROBIN DU 29/08 : dans la plage des
       trois arrêts, un geste amène à l'arrêt SUIVANT, dans le sens du geste et
       à partir de celui où l'on était (`lastStopIndex`) — jamais « le plus
       proche », qui faisait remonter deux crans d'un coup depuis le catalogue.
       Un état à moitié révélé n'est jamais un état voulu.
       AU-DELÀ de la plage, on laisse libre : le visiteur qui lit le catalogue
       ne doit jamais se sentir retenu.

       Mouvement réduit demandé par le système → aucun ancrage : déplacer la
       page sous quelqu'un qui a demandé moins d'animation serait à contresens.
       ══════════════════════════════════════════════════════════════════════ */
    /* ⚠️ LE VRAI CARROUSEL A ÉTÉ ESSAYÉ PUIS RETIRÉ (25/08). Ne pas le
       réintroduire sans relire ceci.
       Principe : annuler le geste natif (`preventDefault` sur `touchmove`) pour
       que la page parte seule vers l'écran suivant. Ça ne peut pas marcher ici,
       et pas pour une raison de réglage : **le navigateur décide au TOUT
       PREMIER `touchmove` si le geste est un défilement**, et une fois qu'il a
       décidé, il ignore silencieusement toute annulation ultérieure. Un seuil
       de déclenchement, si petit soit-il, laisse passer les premiers
       mouvements — donc le mode ne prenait la main que sur les gestes
       VIOLENTS, et échouait précisément sur les gestes posés. C'était le
       symptôme rapporté par Robin.
       La seule mécanique correcte serait `touch-action` en CSS (consulté quand
       le doigt se pose, jamais trop tard), mais c'est un verrou DUR : si le JS
       ne fait pas son travail, la page devient impossible à faire défiler.
       Décision Robin : « quelque chose de simple et propre qui fonctionne »,
       avec le flou piloté au doigt et la pause. D'où la version ci-dessous, et
       elle seule. */

    var SNAP_IDLE = 150;              // ms d'immobilité avant le recalage de secours
    var GESTE_MINIMUM = 8;            // ⇦ en dessous, il ne s'est rien passé (px)
    var TOLERANCE_ARRIVEE = 4;        // px : on se considère posé sur une étape
    /* ⇦ Le silence qui sépare deux gestes de molette. En dessous, c'est encore
       la même poussée : le pavé tactile émet son inertie en continu pendant une
       à deux secondes.
       Monté de 140 à 260 ms le 29/08, en même temps que la correction de
       l'horloge (voir l'écouteur `wheel`). Les 140 ms suffisaient tant que le
       compteur n'était pas alimenté pendant l'animation ; maintenant qu'il
       l'est, la queue d'inertie d'un pavé macOS laisse des trous de 150 à
       200 ms qui se seraient lus comme un nouveau geste.
       ⚠️ DEUX CONSÉQUENCES ASSUMÉES, arbitrées par Robin le 29/08 :
       · deux poussées volontaires rapprochées ne comptent que pour une ;
       · après une poussée franche, le hero reste sourd jusqu'à ce que
         l'inertie se soit éteinte, soit jusqu'à deux secondes.
       C'est le prix d'« un geste = un arrêt », et il a été choisi en le
       sachant. Ne pas « améliorer la réactivité » sans le lui redemander. */
    var REPOS_ENTRE_DEUX_PAS = 260;   // ms
    /* ⇦ LE TEMPS QUE MET LA PAGE POUR PASSER D'UN ÉCRAN AU SUIVANT.
       Avant, on laissait faire `behavior: 'smooth'` : la durée appartenait
       alors au navigateur, et elle GRANDIT AVEC LA DISTANCE. Le trajet
       phrase → carrousel fait 100 % de la hauteur d'écran, d'où le « trop
       lent » de Robin ; celui vers le catalogue en fait 200 % et durait
       encore plus. Ici la durée est la MÊME pour toutes les étapes, quelle que
       soit la distance : c'est ce qui donne une sensation de pas régulier.
       Monter ce chiffre rend le mouvement plus posé, le descendre plus sec.
       Historique du réglage : 420 ms jugé « trop rapide » par Robin, porté à
       560, puis à 680 le 29/08 EN MÊME TEMPS que le changement d'adoucissement
       (voir `easeInOut` plus bas). Les deux vont ensemble : allonger seul rend
       mou, changer l'adoucissement seul ne suffit pas à rendre le mouvement
       suivable. */
    var DUREE_TRANSITION = 680;  // ms
    var snapTimer = null;
    var progScroll = false;     // un scroll programmatique est en vol

    /* Le verrou de scroll est posé par `overflow: hidden` sur html, sur body ou
       sur les deux selon qui le pose — par NOTRE
       machine à écrire ET par la modale Conseiller. Un seul test couvre donc
       les deux situations où il ne faut pas ancrer. */
    function scrollLocked() {
      /* ⚠️ LES DEUX ÉLÉMENTS, PAS SEULEMENT `html`. Notre machine à écrire pose
         le verrou sur les deux ; le menu, le panier et la recherche ne le
         posent que sur `body`. En ne testant que `html`, on ne les voyait pas :
         un geste de molette derrière un panneau ouvert franchissait une étape
         invisible et désynchronisait l'ancreur. */
      return document.documentElement.style.overflow === 'hidden' ||
             document.body.style.overflow === 'hidden';
    }

    /* Saut instantané. `window.scrollTo(x, y)` ne suffit pas : `html` porte un
       `scroll-behavior: smooth` global (style.css l. 128) qui animerait même
       ce saut. On le neutralise le temps de l'appel.
       ⚠️ IL N'ANNULE PAS une animation en vol (ni `progRaf`, ni `progScroll`) :
       lancé pendant l'une d'elles, il serait écrasé à l'image suivante. Ses
       deux appelants sont le saut invisible derrière la modale et le mode
       « mouvement réduit », jamais un geste du visiteur. */
    function jumpTo(y) {
      var html = document.documentElement, prev = html.style.scrollBehavior;
      html.style.scrollBehavior = 'auto';
      window.scrollTo(0, y);
      html.style.scrollBehavior = prev;
    }
    /* Tout scroll que NOUS déclenchons passe par ici : le drapeau empêche
       l'ancreur de se déclencher à la fin de l'animation et de se battre avec
       elle (deux animations sur le même axe = rebond).
       ⚠️ LE DRAPEAU SE LÈVE À L'ARRIVÉE, PAS APRÈS UN DÉLAI FIXE. Il était
       relâché par un `setTimeout(700)` — une supposition sur une durée que le
       navigateur possède. Or le trajet carrousel → catalogue fait 200 % de la
       hauteur d'écran, soit ~1 500px sur un téléphone : l'animation y dépasse
       facilement 700 ms. Le drapeau retombait alors EN PLEIN VOL, l'ancreur
       prenait une position intermédiaire pour une nouvelle origine de geste,
       et lançait une SECONDE transition. Un geste, deux mouvements.
       Le délai ne subsiste qu'en filet ultime (onglet passé en arrière-plan,
       animation jamais terminée). */
    var progTarget = null, progTimer = null, progRaf = null;
    var progFrom = 0, progStart = 0, progPrevBehavior = '', progPrevCaptured = false;
    /* ⚠️ CHANGÉ LE 29/08 : `easeOut` → `easeInOut`, et c'est la correction qui
       se voit le plus.
       L'ancien profil partait à 4 820 px/s : 45 % du trajet était fait en
       100 ms, puis les 200 dernières millisecondes ne parcouraient plus que
       41 px. « Brutal, puis mou » — l'œil n'avait rien à suivre, il constatait
       juste que l'écran avait changé.
       Celui-ci démarre doucement, accélère au milieu, se pose à la fin : la
       moitié du trajet tombe à mi-parcours (340 ms) au lieu de 115 ms. Même
       durée totale ou presque, mouvement suivable. */
    function easeInOut(t) {
      return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function programmaticScrollTo(y) {
      var target = Math.round(y);
      var from = window.pageYOffset;
      if (Math.abs(target - from) < TOLERANCE_ARRIVEE) return;
      progTarget = target;
      progFrom = from;
      progScroll = true;
      clearTimeout(progTimer);
      progTimer = setTimeout(endProgrammatic, 2500); // filet : onglet en arrière-plan
      if (reduceMotion) { jumpTo(target); endProgrammatic(); return; }

      /* ⚠️ NEUTRALISER LE `scroll-behavior: smooth` GLOBAL (style.css l. 128)
         PENDANT TOUTE L'ANIMATION, et pas seulement le temps d'un appel.
         Chaque image appelle `scrollTo` ; avec le smooth actif, CHAQUE APPEL
         déclencherait sa propre mini-animation et la page ramperait sans
         jamais arriver. Le symptôme serait « c'est devenu tout mou » — et on
         l'imputerait à la durée, donc à la mauvaise cause. */
      var html = document.documentElement;
      /* ⚠️ NE PAS RECAPTURER SI UNE ANIMATION EST DÉJÀ EN VOL. On mémoriserait
         alors le `auto` posé par la précédente, et `endProgrammatic` le
         réécrirait en style inline : le `scroll-behavior: smooth` global du
         site serait mort jusqu'au rechargement, pour toute la page. Atteignable
         en cliquant l'indice flèche pendant l'animation du bouton blanc. */
      if (!progPrevCaptured) { progPrevBehavior = html.style.scrollBehavior; progPrevCaptured = true; }
      html.style.scrollBehavior = 'auto';

      progStart = 0;
      if (progRaf) cancelAnimationFrame(progRaf);
      progRaf = requestAnimationFrame(function step(ts) {
        if (!progStart) progStart = ts;
        var t = Math.min(1, (ts - progStart) / DUREE_TRANSITION);
        window.scrollTo(0, Math.round(progFrom + (progTarget - progFrom) * easeInOut(t)));
        if (t < 1) progRaf = requestAnimationFrame(step);
        else endProgrammatic();
      });
    }
    function endProgrammatic() {
      clearTimeout(progTimer);
      if (progRaf) { cancelAnimationFrame(progRaf); progRaf = null; }
      document.documentElement.style.scrollBehavior = progPrevBehavior;
      progPrevCaptured = false;
      progScroll = false;
      progTarget = null;
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
    /* Les trois positions de repos, du haut vers le bas.
       ⚠️ MISES EN CACHE DEPUIS LE 29/08, et ce n'est pas une micro-optimisation.
       Chaque appel enchaîne un `getBoundingClientRect` et trois
       `getComputedStyle` — donc un recalcul de mise en page synchrone. Tant que
       l'écouteur de molette vivait sur le hero, ça ne coûtait rien ailleurs ;
       depuis qu'il est sur `window`, il tourne sur TOUTE la page, jusqu'à
       120 fois par seconde au pavé tactile, catalogue compris.
       Ces trois valeurs sont des positions dans le DOCUMENT : elles ne bougent
       pas quand on défile, seulement quand la mise en page change. D'où
       l'invalidation par `oublierEtapes()`, appelée au redimensionnement, après
       un remplacement de cards, au recalage de 600 ms — c'est celle-là qui
       rattrape un cache rempli trop tôt — et une fois les polices posées.
       ⚠️ Pas de `= null` ici : la déclaration vit plus bas dans le fichier que
       certains appelants. Une initialisation effacerait un cache déjà rempli
       si l'un d'eux devenait synchrone. */
    var stopsCache;
    function oublierEtapes() { stopsCache = null; }
    function stopPositions() {
      if (stopsCache) return stopsCache;
      if (!track) return null;
      var top = Math.round(track.getBoundingClientRect().top + window.pageYOffset);
      var pos2 = top + Math.round(revealSpan);
      var pos3 = catalogueSnapY();
      if (pos3 == null || pos3 < pos2) pos3 = pos2;
      stopsCache = [top, pos2, pos3];
      return stopsCache;
    }
    function nearestStep(y, stops) {
      var idx = 0, best = Infinity;
      stops.forEach(function (s, i) { var d = Math.abs(s - y); if (d < best) { best = d; idx = i; } });
      return idx;
    }
    /* Hors de cette plage, la page est LIBRE : au-dessus du hero, et dès qu'on
       est descendu sous la dernière étape. On ne retient jamais quelqu'un qui
       lit le catalogue. Bornes SERRÉES (8 px, la tolérance d'arrivée) : une
       marge plus large sous la
       dernière étape retiendrait le visiteur dans les premiers écrans du
       catalogue, ce qui est exactement l'inverse du but. */
    function inHeroRange(y, stops) {
      return y >= stops[0] - 8 && y <= stops[2] + 8;
    }
    /* ── Le chemin du RETOUR, et pourquoi il diffère ───────────────────────
       Le verrou `touch-action` ne vaut que pour les gestes NÉS dans le hero.
       Or à la dernière étape, le hero est déjà remonté hors de l'écran : le
       doigt se pose sur le catalogue, le geste échappe donc au verrou et la
       page défile librement. C'est pour ça que la remontée depuis « Toutes mes
       créations » ne se comportait pas comme le reste (constat de Robin).

       On ne peut pas verrouiller le catalogue : il doit rester lisible. On
       rattrape donc à la FIN du geste, et de façon DIRECTIONNELLE — une étape,
       dans le sens du mouvement, à partir de l'étape d'où l'on venait. Le
       résultat est le même (un geste = une étape) ; seul le moment où il se
       résout change : à la fin du geste au lieu de son début.

       `lastStopIndex` mémorise l'étape où l'on est posé. Il vaut `null` dès
       qu'on quitte la zone par une extrémité : c'est ce qui libère le
       catalogue au lieu de ramener le visiteur en arrière. */
    var lastStopIndex = null;
    function maybeSnap() {
      if (reduceMotion || progScroll || scrollLocked()) return;
      var stops = stopPositions();
      if (!stops) return;
      var y = window.pageYOffset;
      /* ⚠️ ON MÉMORISE PAR QUELLE EXTRÉMITÉ ON EST SORTI, on ne remet plus à
         `null`. Avec `null`, la rentrée dans la zone se recalait sur l'étape la
         PLUS PROCHE géométriquement : en remontant du catalogue, il suffisait de
         dépasser le carrousel de la moitié d'un écran — ce qu'un geste lancé
         fait sans effort — pour être ramené à la phrase de Robin. Deuxième
         cause du symptôme du 29/08, indépendante de celle de l'horloge.
         En gardant l'extrémité, la rentrée redevient DIRECTIONNELLE : une
         étape, dans le sens du mouvement. Ça ne retient personne dans le
         catalogue, la sortie de plage rend la main avant cette ligne. */
      if (!inHeroRange(y, stops)) {
        if (y > stops[2]) lastStopIndex = 2;
        else if (y < stops[0]) lastStopIndex = 0;
        return;
      }

      var i;
      if (lastStopIndex === null) {
        // On ne sait pas d'où l'on vient (rechargement, rotation, arrivée par
        // un lien) : on se contente de recaler sur l'étape la plus proche.
        i = nearestStep(y, stops);
      } else {
        var moved = y - stops[lastStopIndex];
        i = lastStopIndex;
        if (Math.abs(moved) > GESTE_MINIMUM) {
          var next = lastStopIndex + (moved > 0 ? 1 : -1);
          // Sortie par une extrémité → on relâche : au-delà, la page est libre.
          if (next < 0 || next > stops.length - 1) { lastStopIndex = null; return; }
          i = next;
        }
      }
      lastStopIndex = i;
      if (Math.abs(stops[i] - y) < TOLERANCE_ARRIVEE) return;
      programmaticScrollTo(stops[i]);
    }
    function scheduleSnap() {
      clearTimeout(snapTimer);
      snapTimer = setTimeout(maybeSnap, SNAP_IDLE);
    }

    /* ══════════════════════════════════════════════════════════════════════
       LE CARROUSEL : un geste amorcé = une étape, automatiquement.
       ──────────────────────────────────────────────────────────────────────
       Le clic sur « Découvrir ma sélection » et le début d'un geste vers le
       bas mènent au même arrêt — par deux chemins différents : le clic vise une
       position absolue, le geste passe par `stepBy`. Aucun état intermédiaire
       n'est VISÉ. Deux dépassements restent possibles et sont assumés : la
       queue d'inertie au-delà du dernier arrêt (voir la sortie « extrémités »),
       et le défilement natif si le JS ne tourne pas.

       ⚠️ LE VERROU EST EN CSS, ET IL DOIT L'ÊTRE. Le navigateur décide au TOUT
       PREMIER `touchmove` si un geste est un défilement, et ignore ensuite
       toute annulation : un `preventDefault` en JS arrive toujours trop tard,
       et c'est pourquoi la première tentative de carrousel ne fonctionnait que
       sur les gestes violents. `touch-action`, lui, est consulté quand le doigt
       SE POSE. Valeur : `pan-x pinch-zoom` — surtout pas `none`, qui tuerait
       le swipe horizontal des cards (le slider est un descendant du hero) et
       le pincer-zoomer.

       ⚠️ C'est un verrou DUR : si le JS ne fait pas son travail, le hero
       devient impossible à faire défiler. D'où : posé PAR le JS (donc absent
       si le script ne tourne pas), sur le hero SEUL (le reste de la page reste
       libre), et jamais en mouvement réduit.

       Le verrou ne concerne QUE le tactile. À la molette, on annule
       explicitement — et là `preventDefault` fonctionne. */
    var lastWheelAt = 0;
    /* Cumul et verrou d'un même geste à la molette, exactement comme `aFranchi`
       le fait pour le tactile. Un geste finit quand la molette se tait pendant
       REPOS_ENTRE_DEUX_PAS — le silence seul, sans exception d'amplitude. */
    var wheelCumul = 0, wheelFranchi = false;
    function stepBy(dir) {
      var stops = stopPositions();
      if (!stops) return false;
      var y = window.pageYOffset;
      if (!inHeroRange(y, stops)) return false;
      var from = nearestStep(y, stops);
      // Pas posé sur une étape (on y est entré par un autre chemin) : on
      // recale d'abord, plutôt que de franchir deux crans d'un coup.
      /* ⚠️ SEUIL ALIGNÉ SUR `GESTE_MINIMUM` (8) ET NON SUR `TOLERANCE_ARRIVEE`
         (4). Entre les deux valeurs s'ouvrait une bande de 4 px où un geste
         partait en recalage invisible au lieu de franchir : sur le chemin du
         retour depuis le catalogue — celui dont Robin s'est plaint — un geste
         sur deux se perdait dans un déplacement de 5 px, parfois même à
         contresens, en avalant les 680 ms d'animation. */
      if (Math.abs(stops[from] - y) > GESTE_MINIMUM) {
        /* ⚠️ POSER L'INDEX ICI AUSSI. Cette branche déplace la page sans le
           faire, et l'ancreur en tirait ensuite une direction fausse : recalé
           sur le catalogue avec un index resté à 0, il calculait « il est
           descendu », et ramenait au carrousel quelqu'un qui descendait. */
        lastStopIndex = from;
        programmaticScrollTo(stops[from]);
        return true;
      }
      var next = Math.max(0, Math.min(stops.length - 1, from + dir));
      if (next === from) return false; // déjà à une extrémité
      lastStopIndex = next; // le chemin du retour saura d'où l'on vient
      programmaticScrollTo(stops[next]);
      return true;
    }
    function carouselBusy() { return progScroll || scrollLocked(); }

    if (!reduceMotion) {
      section.classList.add('is-carousel');

      /* Molette et pavé tactile. Le pavé envoie une rafale continue d'inertie
         pendant une à deux secondes : sans temps de calme, une seule poussée
         franchirait plusieurs étapes. On annule le défilement à CHAQUE
         événement (sinon le hero défilerait librement entre deux pas) mais on
         ne franchit une étape qu'une fois par geste.
         ⚠️ L'ÉTAPE EST FRANCHIE AU DÉBUT DU GESTE, pas à sa fin. Le silence ne
         déclenche rien : il RÉARME. Une note disait « on ne franchit une étape
         qu'après un silence » — c'était la mécanique d'une première version, et
         elle contredit le contrat arbitré par Robin. */
      /* ⚠️ ÉCOUTEUR SUR `window`, PAS SUR LE HERO — CORRECTIF DU 29/08.
         Il était posé sur la section, au motif qu'il « ne coûterait rien au
         reste du site ». Sauf qu'un événement de molette est adressé à
         l'élément SOUS LE CURSEUR, et le header est collant : il recouvre le
         haut du hero. Curseur dans cette bande, la molette partait au header,
         notre écouteur ne voyait rien, et le geste était perdu — il fallait
         bouger la souris plus bas pour que ça reparte. Constaté par Robin.
         Le même piège vaut pour tout ce qui flotte au-dessus du hero.
         ⚠️ CE QUE ÇA COÛTE, ET IL FAUT LE SAVOIR : l'écouteur tourne désormais
         sur toute la page, catalogue compris. D'où le cache des positions
         d'étape (`stopPositions` mesurait le document à chaque événement, soit
         jusqu'à 120 fois par seconde) et les sorties anticipées ci-dessous, qui
         doivent TOUTES rester avant le `preventDefault`. */
      window.addEventListener('wheel', function (e) {
        /* ⚠️ SORTIE 0 — GESTE HORIZONTAL. Même règle que `touchmove` plus bas.
           Elle ne visait d'abord que le slider (`sliderEl.contains(e.target)`),
           ce qui laissait passer un défilement horizontal pur ailleurs sur le
           hero : `deltaY` valant 0, aucune sortie ne mordait, on annulait un
           geste dont on ne ferait rien — et on tuait au passage le « retour en
           arrière » par glissement du navigateur. */
        if (Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
        /* ⚠️ SORTIE 1 — UN PANNEAU FLOTTANT TIENT L'ÉCRAN. Modale Conseiller,
           panier, recherche, menu mobile : tous verrouillent la page par
           `overflow: hidden`, et tous ont un contenu qui défile à l'intérieur.
           Ce geste ne nous appartient pas, et l'annuler tuerait leur propre
           défilement. Un seul test les couvre tous, sans liste de classes à
           entretenir. */
        if (scrollLocked()) return;
        var stops = stopPositions();
        if (!stops) return;
        var yNow = window.pageYOffset;
        /* ⚠️ SORTIE 2 — HORS DE LA PLAGE DU HERO, la page est libre. */
        if (!inHeroRange(yNow, stops)) return;
        /* ⚠️ SORTIE 3 — AUX DEUX EXTRÉMITÉS, ON N'ANNULE PAS. `stepBy` refuse
           d'aller au-delà de la dernière étape et retourne `false` : annuler
           l'événement l'aurait été pour rien, et la page devenait IMPOSSIBLE à
           faire défiler vers le catalogue à la molette. Ce trou n'existait pas
           tant que l'écouteur vivait sur le hero (à la dernière étape, le hero
           est déjà sorti de l'écran et ne recevait plus rien) : c'est le
           passage sur `window` qui l'a ouvert. */
        if (e.deltaY > 0 && yNow >= stops[2] - TOLERANCE_ARRIVEE) return;
        if (e.deltaY < 0 && yNow <= stops[0] + TOLERANCE_ARRIVEE) return;
        e.preventDefault();

        /* ⚠️ L'HORLOGE EST MISE À JOUR AVANT LES SORTIES QUI COMPTENT (occupé,
           geste déjà franchi, seuil non atteint), ET C'EST LE CŒUR DU CORRECTIF
           DU 29/08. Elle reste APRÈS les sorties d'aiguillage plus haut, qui
           écartent des gestes qui ne nous appartiennent pas.
           ⚠️ Conséquence connue de la sortie « aux extrémités » : arrivé au
           catalogue, l'inertie du geste qui vous y a amené repasse en
           défilement natif et vous fait dépasser un peu l'arrêt. C'est le prix
           de cette sortie, sans laquelle la page serait bloquée là.
           Elle était écrite APRÈS le test `carouselBusy()` :
           pendant les 560 ms d'animation d'alors, l'inertie du pavé continuait
           d'arriver, était bien annulée, mais ne comptait pas comme du bruit.
           À l'instant où l'animation se terminait, le premier événement encore
           en vol trouvait une horloge vieille de 600 ms, en concluait « la voie
           est libre », et repartait pour une étape. UN GESTE, DEUX ÉCRANS —
           « ça passe presque directement au texte de Robin », dans les deux
           sens. Diagnostic de Robin le 29/08, confirmé par l'audit.
           Le commentaire de REPOS_ENTRE_DEUX_PAS conseillait de monter la
           valeur : ça ne pouvait rien faire, puisque le compteur qu'elle
           consulte n'était plus alimenté. */
        var now = Date.now();

        /* ⚠️ `deltaY` N'EST PAS TOUJOURS EN PIXELS, et l'oublier rend le hero
           impossible à faire défiler. Firefox à la souris envoie des LIGNES
           (`deltaMode` 1, environ 3 par cran) : le cumul plafonnait alors à 3,
           n'atteignait jamais le seuil de 8, et comme le défilement natif est
           déjà annulé plus haut, il ne se passait plus rien du tout. Chrome et
           Safari (pixels, ~100 par cran) ne montraient rien. */
        var dy = e.deltaY;
        if (e.deltaMode === 1) dy *= 16;                                // lignes → px
        else if (e.deltaMode === 2) dy *= (window.innerHeight || 800);  // pages → px
        /* ⚠️ TOUT ÉVÉNEMENT GARDE LE GESTE VIVANT, MÊME LE PLUS FAIBLE, ET
           C'EST UNE DÉCISION DE ROBIN (29/08), PRISE EN CONNAISSANT SON PRIX.
           Sa règle : « un long geste, qui déclenche le premier mouvement, ne
           doit pas passer plusieurs arrêts », et un geste finit quand le
           mouvement s'est VRAIMENT tu. La queue d'inertie d'un pavé émet
           pendant une seconde et demie : tant qu'elle coule, on est encore dans
           le même geste, et rien ne peut franchir un second arrêt.
           Le prix, assumé après lui avoir été présenté : après une poussée
           franche, le hero reste sourd jusqu'à deux secondes.
           ⚠️ NE PAS RÉINTRODUIRE D'EXCEPTION POUR LES PETITES AMPLITUDES. Elle a
           existé une demi-journée, pour rendre la main plus vite : elle
           permettait au silence d'être atteint EN PLEINE INERTIE, celle-ci
           passait alors pour un nouveau geste, et le double saut revenait par
           la porte de derrière. La règle simple est la bonne. */
        var nouveauGeste = now - lastWheelAt > REPOS_ENTRE_DEUX_PAS;
        if (nouveauGeste) { wheelCumul = 0; wheelFranchi = false; }
        lastWheelAt = now;
        wheelCumul += dy;

        /* ⚠️ ON MARQUE LE GESTE CONSOMMÉ, ON NE SE CONTENTE PAS DE SORTIR.
           Sinon un geste donné PENDANT une animation lancée autrement (clic sur
           « Découvrir ma sélection », sur un indice, retour de modale) n'est pas
           perdu mais MIS EN ATTENTE : il cumule dans le vide, et il part tout
           seul à l'arrivée. Un clic plus une poussée = deux arrêts, le second
           sans que personne n'ait rien demandé. Robin a tranché « le second
           geste est perdu », pas « différé ». */
        if (carouselBusy()) { wheelFranchi = true; return; }
        if (wheelFranchi) return;
        /* Même règle que le tactile : on cumule jusqu'à ce que le geste ait une
           amplitude, puis UNE seule étape par geste. Sans le cumul, un pavé
           tactile qui démarre à 1 ou 2 px par événement ne franchirait jamais
           rien ; sans le seuil, une dérive de pouce ferait sauter un écran. */
        if (Math.abs(wheelCumul) < GESTE_MINIMUM) return;
        wheelFranchi = true;
        stepBy(wheelCumul > 0 ? 1 : -1);
      }, { passive: false });

      /* Tactile. L'écouteur peut rester PASSIF : c'est `touch-action` qui a
         déjà empêché le défilement, on ne fait ici que lire la direction.
         `aFranchi` garantit une seule étape par geste, quelle que soit sa durée. */
      var tY = 0, tX = 0, aFranchi = false;
      section.addEventListener('touchstart', function (e) {
        tY = e.touches[0].clientY;
        tX = e.touches[0].clientX;
        aFranchi = false;
      }, { passive: true });
      section.addEventListener('touchmove', function (e) {
        // Même règle qu'à la molette : un geste donné pendant une animation est
        // consommé, pas mis en attente.
        if (carouselBusy()) { aFranchi = true; return; }
        if (aFranchi) return;
        var dy = tY - e.touches[0].clientY;
        var dx = tX - e.touches[0].clientX;
        // Geste horizontal → c'est le slider, on ne s'en mêle pas.
        if (Math.abs(dy) < GESTE_MINIMUM || Math.abs(dx) > Math.abs(dy)) return;
        aFranchi = true;
        stepBy(dy > 0 ? 1 : -1);
      }, { passive: true });

      /* Clavier : si la page est un carrousel, les flèches et la barre d'espace
         doivent franchir des étapes elles aussi — sinon elles traversent le
         hero en défilement natif et atterrissent entre deux états. */
      document.addEventListener('keydown', function (e) {
        /* ⚠️ `e.repeat` : une touche MAINTENUE émet en rafale, et le clavier
           était le seul déclencheur sans verrou de geste — il enchaînait les
           arrêts au rythme de l'animation. Une pression = un arrêt, comme un
           geste = un arrêt. */
        if (e.metaKey || e.ctrlKey || e.altKey) return;
        var t = e.target;
        if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
        var dir = 0;
        if (e.key === 'ArrowDown' || e.key === 'PageDown' || e.key === ' ' || e.key === 'Spacebar') dir = 1;
        else if (e.key === 'ArrowUp' || e.key === 'PageUp') dir = -1;
        if (!dir) return;
        var stops = stopPositions();
        if (!stops || !inHeroRange(window.pageYOffset, stops)) return;
        /* ⚠️ ON ANNULE D'ABORD, ON DÉCIDE ENSUITE. Une touche maintenue émet en
           rafale : la refuser en sortant sans `preventDefault` rendait la main
           au défilement natif, qui traversait alors le hero et ses états
           intermédiaires — exactement ce que ce gestionnaire existe pour
           empêcher. Une pression = un arrêt ; les répétitions et les pressions
           pendant l'animation sont AVALÉES, pas rendues au navigateur. */
        e.preventDefault();
        if (e.repeat || carouselBusy()) return;
        stepBy(dir);
      });
    }

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

      /* ⚠️ LE SAUT DOIT COUVRIR AUSSI CETTE ATTENTE. La frappe ne démarre qu'au
         bout de 900 ms — mais le scroll, lui, est bloqué DÈS MAINTENANT. Sans
         l'armement ci-dessous, le visiteur qui tente de faire défiler pendant
         cette seconde-là ne verrait rien se passer : ni la page qui bouge, ni
         le texte qui s'affiche. C'est précisément le moment où il conclurait
         que le site est figé.
         Ce pré-armement est remplacé par celui de `revealChars` dès que la
         frappe commence (`armerSaut` désarme le précédent). */
      var demarrerFrappe = function (instantane) {
        revealChars(function () {
          /* ⚠️ ON NE DÉVERROUILLE PAS DANS LE MÊME SOUFFLE QUE LE GESTE.
             Ce code tourne DANS le gestionnaire du geste, donc AVANT que le
             navigateur n'applique le défilement correspondant. Déverrouiller
             ici laisserait passer l'inertie du pavé tactile : la page
             descendrait de plusieurs centaines de pixels, le recalage la
             remonterait une demi-seconde plus tard, et le bouton blanc —
             dont l'opacité suit la position de défilement — clignoterait au
             passage. Le visiteur verrait sa page partir et revenir pour avoir
             voulu aller plus vite.
             250 ms suffisent à absorber la fin du geste. Un clic, lui, n'a
             pas d'inertie : il déverrouille immédiatement. */
          if (sautParDefilement) {
            later(function () {
              unlockScroll();
              /* ⚠️ ET ON DÉCLARE LE GESTE DÉJÀ FRANCHI. Les 250 ms n'absorbent
                 que le début de l'inertie : un pavé en émet une seconde et
                 demie. Sans cette ligne, le premier événement qui passe après
                 le déverrouillage trouve une horloge vieille de 250 ms et
                 franchit une étape — le visiteur voit le texte s'afficher, puis
                 la page partir seule vers le carrousel, sans avoir rien fait de
                 plus. Le défaut que ces 250 ms devaient éviter, décalé de
                 250 ms. La queue du geste qui vient de sauter la frappe n'est
                 pas une intention : elle ne doit pas consommer une étape. */
              lastWheelAt = Date.now();
              wheelCumul = 0;
              wheelFranchi = true;
            }, 250);
          } else {
            unlockScroll();
          }
          if (safety) clearTimeout(safety);
          /* Le bouton blanc « Découvrir ma sélection » prend le rôle que
             tenait « Décrire mon projet » dans cette séquence : il apparaît
             une fois la phrase écrite. L'autre a migré au-dessus du
             carrousel, où c'est l'opacité de la zone sélection qui le révèle. */
          /* ⚠️ SANS DÉCALAGE QUAND ON A SAUTÉ. Ces 250 et 650 ms sont une
             chorégraphie : le bouton puis l'indice arrivent après la phrase,
             posément. Mais quelqu'un qui vient de cliquer pour aller plus vite
             ne veut pas d'une chorégraphie — il veut tout, maintenant. Robin :
             « le texte et le bouton d'un coup ». */
          var pose = (reduceMotion || instantane) ? 0 : 250;
          if (els.revealBtn) {
            els.revealBtn.hidden = false;
            later(function () { els.revealBtn.classList.add('is-in'); }, pose);
          }
          later(function () { if (els.scrollhint) els.scrollhint.classList.add('is-in'); },
                (reduceMotion || instantane) ? 0 : 650);
          sautParDefilement = false; // la séquence est finie, on repart à plat
        }, instantane);
      };
      var attenteFrappe = later(function () { demarrerFrappe(false); }, reduceMotion ? 0 : 900);
      if (!reduceMotion) {
        armerSaut(function () {
          clearTimeout(attenteFrappe);
          demarrerFrappe(true); // on saute l'attente ET la frappe, d'un seul geste
        });
      }
    }

    playSequence();
  }

  /* ⚠️ « complete », PAS « loading ». Ce fichier doit démarrer APRÈS
     `sapi-project.js`, parce que elle relit le projet mémorisé au chargement (`refineFromStoredProject`) — or
     ce projet n'est complet qu'une fois l'adresse ingérée par `sapi-project`,
     ce qui arrive à `DOMContentLoaded`.
     Le test naïf `readyState === 'loading'` ne le garantit pas : **en
     production, Autoptimize ajoute `defer` à tous les scripts**, et dans un
     script différé `readyState` vaut déjà « interactive ». On tombait donc
     dans le `else` et `init()` partait trop tôt.
     Avec le test sur « complete », on attend `DOMContentLoaded` dans tous les
     cas sauf si la page est DÉJÀ entièrement chargée — donc en production on
     démarre à `DOMContentLoaded`, pas immédiatement. C'est bien l'effet
     recherché : ne pas confondre « le test dit complete » et « on s'exécute
     tout de suite ».
     ⚠️ Le site de test n'a PAS Autoptimize : cette classe de bug ne peut pas
     être montrée en recette. Ne pas « simplifier » ce test parce qu'il a l'air
     de marcher sur test. Explication complète dans sapi-project.js. */
  if (document.readyState === 'complete') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
})();

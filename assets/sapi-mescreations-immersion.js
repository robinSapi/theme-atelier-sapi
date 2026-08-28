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
    function retypePhrase(text, done) {
      els.phraseText = text || '';
      buildChars(els.phraseText);
      revealChars(done);
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
    /* ⇦ Le temps de calme exigé avant d'accepter un nouveau geste à la molette.
       À MONTER si une seule poussée de pavé tactile franchit deux étapes : le
       pavé émet son inertie en continu pendant une à deux secondes, et c'est ce
       silence qui distingue « même poussée » de « nouvelle poussée ». */
    var REPOS_ENTRE_DEUX_PAS = 140;   // ms
    /* ⇦ LE TEMPS QUE MET LA PAGE POUR PASSER D'UN ÉCRAN AU SUIVANT.
       Avant, on laissait faire `behavior: 'smooth'` : la durée appartenait
       alors au navigateur, et elle GRANDIT AVEC LA DISTANCE. Le trajet
       phrase → carrousel fait 100 % de la hauteur d'écran, d'où le « trop
       lent » de Robin ; celui vers le catalogue en fait 200 % et durait
       encore plus. Ici la durée est la MÊME pour toutes les étapes, quelle que
       soit la distance : c'est ce qui donne une sensation de pas régulier.
       Monter ce chiffre rend le mouvement plus posé, le descendre plus sec.
       Historique du réglage : 420 ms jugé « trop rapide » par Robin, porté à
       560. La fourchette utile est ~450-700 ; au-delà on retombe dans le
       « trop lent » qui avait motivé la reprise en main de l'animation. */
    var DUREE_TRANSITION = 560;  // ms
    var snapTimer = null;
    var progScroll = false;     // un scroll programmatique est en vol

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
    var progFrom = 0, progStart = 0, progPrevBehavior = '';
    // Décélération : départ franc, arrivée qui se pose. C'est ce qui fait
    // qu'un déplacement court paraît net sans paraître brutal.
    function easeOut(t) { return 1 - Math.pow(1 - t, 3); }

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
      progPrevBehavior = html.style.scrollBehavior;
      html.style.scrollBehavior = 'auto';

      progStart = 0;
      if (progRaf) cancelAnimationFrame(progRaf);
      progRaf = requestAnimationFrame(function step(ts) {
        if (!progStart) progStart = ts;
        var t = Math.min(1, (ts - progStart) / DUREE_TRANSITION);
        window.scrollTo(0, Math.round(progFrom + (progTarget - progFrom) * easeOut(t)));
        if (t < 1) progRaf = requestAnimationFrame(step);
        else endProgrammatic();
      });
    }
    function endProgrammatic() {
      clearTimeout(progTimer);
      if (progRaf) { cancelAnimationFrame(progRaf); progRaf = null; }
      document.documentElement.style.scrollBehavior = progPrevBehavior;
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
    /* Les trois positions de repos, du haut vers le bas. */
    function stopPositions() {
      if (!track) return null;
      var top = Math.round(track.getBoundingClientRect().top + window.pageYOffset);
      var pos2 = top + Math.round(revealSpan);
      var pos3 = catalogueSnapY();
      if (pos3 == null || pos3 < pos2) pos3 = pos2;
      return [top, pos2, pos3];
    }
    function nearestStep(y, stops) {
      var idx = 0, best = Infinity;
      stops.forEach(function (s, i) { var d = Math.abs(s - y); if (d < best) { best = d; idx = i; } });
      return idx;
    }
    /* Hors de cette plage, la page est LIBRE : au-dessus du hero, et dès qu'on
       est descendu sous la dernière étape. On ne retient jamais quelqu'un qui
       lit le catalogue. Bornes STRICTES : une marge de tolérance sous la
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
      if (!inHeroRange(y, stops)) { lastStopIndex = null; return; }

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
       bas font exactement la même chose. Aucun état intermédiaire n'est
       atteignable.

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
       explicitement — et là `preventDefault` fonctionne. L'écouteur est posé
       sur le hero et non sur `window` : il ne coûte donc rien au reste du site. */
    var lastWheelAt = 0;
    function stepBy(dir) {
      var stops = stopPositions();
      if (!stops) return false;
      var y = window.pageYOffset;
      if (!inHeroRange(y, stops)) return false;
      var from = nearestStep(y, stops);
      // Pas posé sur une étape (on y est entré par un autre chemin) : on
      // recale d'abord, plutôt que de franchir deux crans d'un coup.
      if (Math.abs(stops[from] - y) > TOLERANCE_ARRIVEE) { programmaticScrollTo(stops[from]); return true; }
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
         ne franchit une étape qu'après un silence. */
      section.addEventListener('wheel', function (e) {
        if (sliderEl && e.target && sliderEl.contains(e.target) && Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
        var stops = stopPositions();
        if (!stops || !inHeroRange(window.pageYOffset, stops)) return;
        e.preventDefault();
        if (carouselBusy()) return;
        var now = Date.now();
        var calme = now - lastWheelAt > REPOS_ENTRE_DEUX_PAS;
        lastWheelAt = now;
        if (calme) stepBy(e.deltaY > 0 ? 1 : -1);
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
        if (aFranchi || carouselBusy()) return;
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
        if (carouselBusy() || e.metaKey || e.ctrlKey || e.altKey) return;
        var t = e.target;
        if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
        var dir = 0;
        if (e.key === 'ArrowDown' || e.key === 'PageDown' || e.key === ' ' || e.key === 'Spacebar') dir = 1;
        else if (e.key === 'ArrowUp' || e.key === 'PageUp') dir = -1;
        if (!dir) return;
        var stops = stopPositions();
        if (!stops || !inHeroRange(window.pageYOffset, stops)) return;
        if (stepBy(dir)) e.preventDefault();
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

  /* ⚠️ « complete », PAS « loading ». Ce fichier doit démarrer APRÈS
     `sapi-project.js`, parce que elle relit le projet mémorisé au chargement (`refineFromStoredProject`) — or
     ce projet n'est complet qu'une fois l'adresse ingérée par `sapi-project`,
     ce qui arrive à `DOMContentLoaded`.
     Le test naïf `readyState === 'loading'` ne le garantit pas : **en
     production, Autoptimize ajoute `defer` à tous les scripts**, et dans un
     script différé `readyState` vaut déjà « interactive ». On tombait donc
     dans le `else` et `init()` partait trop tôt.
     ⚠️ Le site de test n'a PAS Autoptimize : cette classe de bug ne peut pas
     être montrée en recette. Ne pas « simplifier » ce test parce qu'il a l'air
     de marcher sur test. Explication complète dans sapi-project.js. */
  if (document.readyState === 'complete') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
})();

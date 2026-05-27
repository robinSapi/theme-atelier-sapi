/**
 * Sapi Cards Conseiller — Cards "Conseil de Robin" / "Mon projet" sur /mes-creations/ (F2a Phase 2)
 *
 * Lit window.sapiProject (source de vérité), choisit la card à afficher,
 * fetche la phrase IA pour "Mon projet" et filtre la grille produit selon
 * les réponses du projet.
 *
 * Reprend la logique de filtrage de assets/mega-filtre.js (cardMatches,
 * getAcceptedCategories, etc.) — phase de transition avant la refonte
 * complète de mega-filtre.js en Phase 3.
 */
(function () {
  'use strict';

  var config = window.SAPI_CARDS_CONSEILLER || {};
  var STEPS = Array.isArray(config.steps) ? config.steps : [];
  var ICONS = config.icons || {};
  var RULES = config.rules || {};

  /* ─────────────────────────────────────────────
     Helpers visibilité (proxy vers sapiProject — Round 2 / 3.2)
     ───────────────────────────────────────────── */
  function getVisibleStepIds(answers) {
    if (window.sapiProject && typeof window.sapiProject.computeVisibleStepIds === 'function') {
      return window.sapiProject.computeVisibleStepIds(answers, STEPS);
    }
    return [];
  }

  function cleanInvisibleAnswers(answers) {
    if (window.sapiProject && typeof window.sapiProject.cleanInvisibleAnswers === 'function') {
      return window.sapiProject.cleanInvisibleAnswers(answers, STEPS);
    }
    return answers;
  }

  // F2a-sexies : retourne le step COMPLET (avec question + choices) pour la
  // prochaine question visible non répondue, ou null si parcours complet.
  function getNextUnansweredStep(answers) {
    var visibleIds = getVisibleStepIds(answers);
    for (var i = 0; i < visibleIds.length; i++) {
      var id = visibleIds[i];
      if (!answers[id]) {
        for (var j = 0; j < STEPS.length; j++) {
          if (STEPS[j].id === id) return STEPS[j];
        }
      }
    }
    return null;
  }

  /* ─────────────────────────────────────────────
     Filtrage produit (mirror mega-filtre.js Phase 1)
     ───────────────────────────────────────────── */
  // Round 2 — 3.3 : passe par cleanInvisibleAnswers avant de compter pour
  // éviter les "chips fantômes" — une answer présente dans le storage mais
  // dont la step n'est pas visible (parce qu'un parent a changé) ne doit
  // pas faire basculer la card en mode "Mon projet".
  function hasAnyAnswer() {
    var raw = (window.sapiProject && window.sapiProject.get().answers) || {};
    var clean = cleanInvisibleAnswers(raw);
    for (var k in clean) {
      if (Object.prototype.hasOwnProperty.call(clean, k)) return true;
    }
    return false;
  }

  function getAnswers() {
    var raw = (window.sapiProject && window.sapiProject.get().answers) || {};
    return cleanInvisibleAnswers(raw);
  }

  function getAcceptedCategories(answers) {
    var sortie = answers.sortie || '';
    var piece = answers.piece || '';
    var eclairage = answers.eclairage || '';
    var pool;

    if (eclairage === 'secondaire') {
      var bySortie = RULES.cats_secondaire_by_sortie || {};
      pool = (bySortie[sortie] || bySortie[''] || ['lampadaires', 'lampesaposer']).slice();
      if (piece === 'cuisine') {
        pool = pool.filter(function (c) { return c !== 'lampesaposer'; });
      }
    } else {
      var cats = RULES.cats_by_sortie || {};
      pool = (cats[sortie] || cats[''] || ['suspensions', 'lampadaires', 'lampesaposer', 'appliques']).slice();
      if (piece === 'cuisine') {
        pool = pool.filter(function (c) { return c !== 'lampesaposer'; });
      }
    }

    // Round 2 — 2.3 : règle métier escalier. Un lampadaire ou une lampe à
    // poser dans un escalier n'a aucun sens, quel que soit taille_escalier
    // ou eclairage. Si le pool se vide après filtre, l'élargissement
    // progressif (computeEffectiveAnswers) prend le relais normalement.
    if (piece === 'escalier') {
      pool = pool.filter(function (c) { return c !== 'lampadaires' && c !== 'lampesaposer'; });
    }
    return pool;
  }

  function getAmpouleFilter(answers) {
    var piece = answers.piece || '';
    var taille = answers.taille || '';
    if (!piece) return null;
    if (taille === 'grande' && (RULES.ampoule_skip_when_grande || []).indexOf(piece) !== -1) return null;
    var map = RULES.ampoule_by_piece || {};
    if (Object.prototype.hasOwnProperty.call(map, piece)) return map[piece];
    return null;
  }

  function isVerticalAllowed(answers) {
    var piece = answers.piece || '';
    var taille = answers.taille || '';
    var hauteur = answers.hauteur || '';
    return (
      piece === 'escalier' ||
      (piece === 'entree' && (hauteur === 'haute' || hauteur === 'confortable')) ||
      (taille === 'petite' && (hauteur === 'haute' || hauteur === 'confortable'))
    );
  }

  function isHorizontalExcluded(answers) {
    return (
      answers.piece === 'escalier' ||
      (answers.taille === 'petite' && answers.hauteur === 'haute')
    );
  }

  // Refactor : la logique de match prend explicitement le set d'answers en
  // paramètre. Permet de tester avec des sous-ensembles lors de l'élargissement
  // progressif (computeEffectiveAnswers).
  function cardMatchesAnswers(card, answers) {
    if (!answers || Object.keys(answers).length === 0) return true;

    var catsAttr = card.getAttribute('data-categories') || '';
    var cardCats = catsAttr.split(/\s+/).filter(Boolean);

    var extras = RULES.extras_slugs || [];
    for (var i = 0; i < extras.length; i++) {
      if (cardCats.indexOf(extras[i]) !== -1) return false;
    }

    var accepted = getAcceptedCategories(answers);
    var hasMatchCat = cardCats.some(function (c) { return accepted.indexOf(c) !== -1; });
    if (!hasMatchCat) return false;

    var isSuspension = cardCats.indexOf('suspensions') !== -1;
    if (isSuspension) {
      var formatAttr = card.getAttribute('data-format-luminaire') || '';
      var cardFormats = formatAttr.split(/\s+/).filter(Boolean);
      if (cardFormats.indexOf('vertical') !== -1 && !isVerticalAllowed(answers)) return false;
      if (cardFormats.indexOf('horizontal') !== -1 && isHorizontalExcluded(answers)) return false;
    }

    var ampouleFilter = getAmpouleFilter(answers);
    if (ampouleFilter && ampouleFilter.length) {
      var ampAttr = card.getAttribute('data-type-ampoule') || '';
      var cardAmp = ampAttr.split(/\s+/).filter(Boolean);
      var ampOk = cardAmp.some(function (t) { return ampouleFilter.indexOf(t) !== -1; });
      if (!ampOk) return false;
    }

    return true;
  }

  // Wrapper public utilisé par shop.js — utilise les effectiveAnswers calculées
  // par computeEffectiveAnswers (window.sapiFilterMeta.effectiveAnswers) si dispo,
  // sinon fallback sur les answers brutes.
  function cardMatches(card) {
    var meta = window.sapiFilterMeta;
    var ans = meta && meta.effectiveAnswers ? meta.effectiveAnswers : getAnswers();
    return cardMatchesAnswers(card, ans);
  }

  // Compte/liste les IDs des cards matchant un set d'answers donné.
  function computeMatchingIds(answers) {
    var ids = [];
    var cards = document.querySelectorAll('.product-card-cinetique');
    cards.forEach(function (card) {
      if (cardMatchesAnswers(card, answers)) {
        var id = card.getAttribute('data-id') || card.getAttribute('data-product-id');
        if (id) ids.push(id);
      }
    });
    return ids;
  }

  // Ordre de retrait pour l'élargissement progressif (du moins critique au plus).
  // 'sortie' n'est JAMAIS dans cette liste — c'est ce qui détermine le type de
  // produit (applique vs suspension vs lampadaire), intouchable.
  var WIDENING_ORDER = ['style', 'table', 'hauteur', 'eclairage', 'taille', 'piece'];

  // Élargissement progressif : si le filtre direct ramène 0, retire les réponses
  // dans WIDENING_ORDER (cumul à partir du début), jusqu'à trouver ≥1 produit ou
  // épuiser la liste. Retourne {effectiveAnswers, ignoredAnswers, matchingIds}.
  // ignoredAnswers ne contient que des clés qui étaient réellement présentes
  // dans rawAnswers (skip silencieux pour les questions non répondues).
  function computeEffectiveAnswers(rawAnswers) {
    rawAnswers = rawAnswers || {};

    // Round 2 — 2.2 : normaliser taille_escalier → taille (mirror exact de
    // sapi_robin_handle_recommendation côté PHP : ouvert→grande, autre→petite).
    // Le filtrage côté JS dépend de `taille` (isVerticalAllowed, ampoule_skip,
    // etc.) — sans cette dérivation, un escalier "ouvert" passait avec
    // taille=undefined → résultats incohérents avec le PHP.
    rawAnswers = Object.assign({}, rawAnswers);
    if (rawAnswers.taille_escalier === 'ouvert') {
      rawAnswers.taille = 'grande';
    } else if (rawAnswers.taille_escalier === 'standard') {
      rawAnswers.taille = 'petite';
    }

    // Itération 0 : tous les filtres en place
    var effective = {};
    Object.keys(rawAnswers).forEach(function (k) { effective[k] = rawAnswers[k]; });
    var ids = computeMatchingIds(effective);
    if (ids.length > 0 || Object.keys(rawAnswers).length === 0) {
      return { effectiveAnswers: effective, ignoredAnswers: [], matchingIds: ids };
    }

    // Liste ordonnée des clés à retirer, restreinte aux clés effectivement
    // présentes (skip silencieux des questions non répondues — pas de bruit
    // dans ignored_answers pour ce que le visiteur n'avait pas indiqué).
    var orderedKeys = WIDENING_ORDER.filter(function (k) {
      var v = rawAnswers[k];
      return typeof v === 'string' && v !== '';
    });
    // taille_escalier est l'avatar de taille pour la pièce escalier
    if (typeof rawAnswers.taille_escalier === 'string' && rawAnswers.taille_escalier !== '') {
      // Insère taille_escalier juste avant taille (ou en fin si taille absente)
      var idxTaille = orderedKeys.indexOf('taille');
      if (idxTaille === -1) orderedKeys.push('taille_escalier');
      else orderedKeys.splice(idxTaille, 0, 'taille_escalier');
    }

    // Itère et retire cumulativement
    var ignored = [];
    for (var i = 0; i < orderedKeys.length; i++) {
      var key = orderedKeys[i];
      ignored.push(key);
      delete effective[key];
      ids = computeMatchingIds(effective);
      if (ids.length > 0) {
        return { effectiveAnswers: effective, ignoredAnswers: ignored, matchingIds: ids };
      }
    }

    // Cas extrême : 0 même au max d'élargissement (seul 'sortie' reste)
    return { effectiveAnswers: effective, ignoredAnswers: ignored, matchingIds: [] };
  }

  function refilterGrid() {
    // Calcule l'élargissement AVANT que shop.js itère sur les cards (puisque
    // shop.js va appeler cardMatches qui lit window.sapiFilterMeta).
    var raw = getAnswers();
    window.sapiFilterMeta = computeEffectiveAnswers(raw);

    if (typeof window.sapiShopRefilter === 'function') {
      window.sapiShopRefilter();
      return;
    }
    // Fallback autonome (cas où shop.js ne serait pas chargé)
    var cards = document.querySelectorAll('.product-card-cinetique');
    cards.forEach(function (card) {
      var show = cardMatches(card);
      card.style.display = show ? '' : 'none';
      card.classList.toggle('is-filtered-out', !show);
    });
  }

  // Override l'API window.sapiMegaFilter pour que shop.js l'utilise.
  // mega-filtre.js Phase 1 expose des no-ops harmlets ; on prend le relais ici.
  window.sapiMegaFilter = {
    cardMatches: cardMatches,
    hasAnyAnswer: hasAnyAnswer,
    // Expose pour que sapi-modal-conseiller.js puisse calculer la meta du
    // filtre (effectiveAnswers + ignoredAnswers + matchingIds) à la volée
    // avec un set d'answers précis (ex. l'état modale qui n'a pas encore
    // été persisté dans sapiProject).
    computeFilterMeta: computeEffectiveAnswers,
  };

  /* ─────────────────────────────────────────────
     Rendu des cards (F2a-bis : 100% synchronous, zéro AJAX au load)
     ───────────────────────────────────────────── */
  var els = {};
  var GENERIC_ADVICE = config.genericAdvice || {};
  var FALLBACK_ADVICE = config.fallbackAdvice || 'Voici la sélection que je te propose dans le catalogue de Robin.';

  /**
   * Texte à afficher sur la card "Mon projet" — résolu synchronement.
   * Priorité : advice_text (issu d'un parcours abouti) → générique de la pièce → fallback.
   */
  function getAdviceText(project) {
    if (project && typeof project.advice_text === 'string' && project.advice_text) {
      return project.advice_text;
    }
    var piece = project && project.answers && project.answers.piece;
    if (piece && GENERIC_ADVICE[piece]) return GENERIC_ADVICE[piece];
    return FALLBACK_ADVICE;
  }

  // F2a-ter raffinement : effet typewriter avec fade-in par lettre.
  // Chaque caractère est wrappé dans un <span class="conseiller-typewriter__char">
  // avec un transition-delay cascadé. Tous les spans démarrent opacity:0
  // (via CSS) puis passent à opacity:1 quand la classe .is-revealing est
  // ajoutée — l'effet visuel = chaque lettre fade-in en cascade fluide.
  // À la fin, fondu de la signature "— Robin" via .is-typing-done.
  var signatureTimer = null;

  function typewriterEffect(contentEl, phraseEl, text, perCharDelay) {
    if (signatureTimer) {
      clearTimeout(signatureTimer);
      signatureTimer = null;
    }
    // Reset état : signature invisible, content vidé
    if (phraseEl) phraseEl.classList.remove('is-typing-done');
    contentEl.classList.remove('is-revealing');
    contentEl.textContent = '';

    var fadeDuration = 280; // doit matcher .conseiller-typewriter__char transition
    var initialDelay = 200; // laisse la card s'afficher avant la frappe
    var chars = text.split('');
    var fragment = document.createDocumentFragment();
    chars.forEach(function (ch, i) {
      var span = document.createElement('span');
      span.className = 'conseiller-typewriter__char';
      span.style.transitionDelay = (initialDelay + i * perCharDelay) + 'ms';
      // L'espace blanc en début/fin de span doit être préservé visuellement.
      // textContent rend exactement le caractère, donc OK.
      span.textContent = ch;
      fragment.appendChild(span);
    });
    contentEl.appendChild(fragment);

    // Trigger la transition après 2 rAF (assure que les spans sont peints
    // avec opacity:0 avant qu'on ajoute la classe qui les fade vers 1).
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        contentEl.classList.add('is-revealing');
      });
    });

    // Schedule signature fade-in après que TOUS les chars aient fini leur fade.
    var totalMs = initialDelay + (chars.length - 1) * perCharDelay + fadeDuration;
    signatureTimer = setTimeout(function () {
      signatureTimer = null;
      if (phraseEl) phraseEl.classList.add('is-typing-done');
    }, totalMs);
  }

  // F2a-sexies : escape HTML pour injection sécurisée des labels de choix
  function escHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  // Renvoie le titre dynamique d'un step selon la pièce courante (pattern
  // identique à sapi-modal-conseiller.js getDynamicQuestion). Fallback sur
  // step.question si pas de dynamic_question.piece pour la pièce courante.
  function getDynamicQuestion(step, answers) {
    if (step.dynamic_question && step.dynamic_question.piece) {
      var p = answers && answers.piece;
      if (p && step.dynamic_question.piece[p]) return step.dynamic_question.piece[p];
    }
    return step.question;
  }

  // F2a-sexies (refondu) : injecte le markup "question + cards de réponse"
  // dans la zone data-inline-question — utilise le pattern .choice du modale
  // pour cohérence visuelle (cards carrées avec icône + label uppercase).
  function renderInlineQuestion(step, answers) {
    if (!els.inlineQuestion || !step) return;
    var choices = step.choices || [];
    var question = getDynamicQuestion(step, answers || {});

    var choicesClass = 'choices';
    if (choices.length === 2) choicesClass += ' choices--2col';
    else if (choices.length === 4) choicesClass += ' choices--4col';

    var html = '<div class="inline-question__label">' + escHtml(question) + '</div>';
    html += '<div class="' + choicesClass + '">';
    for (var i = 0; i < choices.length; i++) {
      var c = choices[i];
      html += '<button class="choice" type="button"' +
        ' data-step-id="' + escHtml(step.id) + '"' +
        ' data-slug="' + escHtml(c.slug) + '"' +
        ' data-label="' + escHtml(c.label) + '">';
      html += '<span class="choice__icon">' + (ICONS[c.icon] || '') + '</span>';
      html += '<span class="choice__label">' + escHtml(c.label) + '</span>';
      if (c.dim) html += '<span class="choice__dim">' + escHtml(c.dim) + '</span>';
      html += '</button>';
    }
    html += '</div>';
    els.inlineQuestion.innerHTML = html;
  }

  function renderMonProjet() {
    if (!els.cardMonProjet || !els.phraseContent) return;
    els.cardConseil && (els.cardConseil.hidden = true);
    els.cardMonProjet.hidden = false;

    // F2a-quater : pendant l'animation morph + l'attente IA, on injecte 3
    // ronds qui pulsent en cascade. Pendant ce temps : pas de chip-question,
    // pas de lien Modifier — juste la card et les dots.
    if (els.cardMonProjet.classList.contains('is-awaiting-advice')) {
      els.phraseContent.innerHTML =
        '<span class="conseiller-awaiting-dot"></span>' +
        '<span class="conseiller-awaiting-dot"></span>' +
        '<span class="conseiller-awaiting-dot"></span>';
      delete els.phraseContent.dataset.lastText;
      if (els.inlineQuestion) els.inlineQuestion.hidden = true;
      if (els.editLink) els.editLink.hidden = true;
      return;
    }

    var project = window.sapiProject ? window.sapiProject.get() : null;
    var newText = getAdviceText(project);

    // Ne déclenche le typewriter QUE si le texte a changé — évite de
    // relancer l'animation à chaque subscribe notification.
    if (els.phraseContent.dataset.lastText !== newText) {
      els.phraseContent.dataset.lastText = newText;
      typewriterEffect(els.phraseContent, els.phrase, newText, 16);
    }

    // F2a-sexies (restauré) : bascule entre chip-question (parcours
    // incomplet) et lien Modifier (parcours complet). La chip-question
    // affiche la prochaine question non répondue avec ses pills cliquables
    // pour progression rapide sans ouvrir la modale.
    var answers = (project && project.answers) || {};
    var next = getNextUnansweredStep(answers);
    if (next) {
      renderInlineQuestion(next, answers);
      if (els.inlineQuestion) els.inlineQuestion.hidden = false;
      if (els.editLink) els.editLink.hidden = true;
    } else {
      if (els.inlineQuestion) {
        els.inlineQuestion.innerHTML = '';
        els.inlineQuestion.hidden = true;
      }
      if (els.editLink) els.editLink.hidden = false;
    }
  }

  function renderConseil() {
    if (!els.cardConseil) return;
    els.cardMonProjet && (els.cardMonProjet.hidden = true);
    els.cardConseil.hidden = false;
  }

  function render() {
    if (window.sapiProject && window.sapiProject.hasProject()) {
      renderMonProjet();
    } else {
      renderConseil();
    }
    refilterGrid();
    populateSelectionGrid();
  }

  /**
   * Refonte /mes-creations/ — Peuple le slot grille de la card englobante
   * "Ma sélection" en clonant les .product-card-cinetique qui matchent
   * sapiProject (= celles SANS .is-filtered-out après refilterGrid).
   * Met aussi à jour le badge "Mon projet · N luminaire(s)".
   */
  function populateSelectionGrid() {
    if (!els.selectionGrid) return;
    els.selectionGrid.innerHTML = '';

    if (!window.sapiProject || !window.sapiProject.hasProject()) return;

    var sourceGrid = document.getElementById('sapi-product-grid');
    if (!sourceGrid) return;

    var matches = sourceGrid.querySelectorAll('.product-card-cinetique:not(.is-filtered-out)');
    var count = 0;
    matches.forEach(function (card) {
      var clone = card.cloneNode(true);
      clone.classList.add('is-selection-clone');
      els.selectionGrid.appendChild(clone);
      count++;
    });

    // Card sur-mesure en DERNIÈRE cellule du slot. Cloné depuis le <template>
    // pour préserver le markup PHP i18n. Affichée même si 0 match (cas dégradé)
    // — donne toujours une issue concrète au visiteur.
    if (els.surmesureTemplate && els.surmesureTemplate.content) {
      var surmesureClone = els.surmesureTemplate.content.cloneNode(true);
      els.selectionGrid.appendChild(surmesureClone);
    }

    // Badge "Mon projet · N luminaire(s)" — N = produits matchés, hors card sur-mesure
    if (els.badgeText) {
      if (count === 0) {
        els.badgeText.textContent = 'Mon projet';
      } else if (count === 1) {
        els.badgeText.textContent = 'Mon projet · 1 luminaire';
      } else {
        els.badgeText.textContent = 'Mon projet · ' + count + ' luminaires';
      }
    }

    // Navigation slider (flèches + dots) selon nb de cards total
    buildSelectionNav();
  }

  /* ─────────────────────────────────────────────
     Slider nav : flèches + dots
     ───────────────────────────────────────────── */

  function getCardsPerPage() {
    var vw = window.innerWidth || document.documentElement.clientWidth;
    if (vw <= 480) return 1;
    if (vw <= 768) return 2;
    if (vw <= 1024) return 3;
    return 4;
  }

  function getTotalCards() {
    if (!els.selectionGrid) return 0;
    return els.selectionGrid.children.length;
  }

  /**
   * Nombre de positions de scroll possibles (= dots affichés).
   * Avec N cards et P cards visibles : positions 0..N-P → totalDots = N - P + 1.
   * Min 1.
   */
  function getTotalDots() {
    var total = getTotalCards();
    var perPage = getCardsPerPage();
    return Math.max(1, total - perPage + 1);
  }

  function getCardStep() {
    if (!els.selectionGrid) return 0;
    var first = els.selectionGrid.querySelector(':scope > *');
    if (!first) return 0;
    var cardWidth = first.getBoundingClientRect().width;
    var gap = parseFloat(window.getComputedStyle(els.selectionGrid).gap) || 0;
    return cardWidth + gap;
  }

  function getCurrentDot() {
    if (!els.selectionGrid) return 0;
    var step = getCardStep();
    if (step === 0) return 0;
    return Math.round(els.selectionGrid.scrollLeft / step);
  }

  function scrollToDot(idx) {
    if (!els.selectionGrid) return;
    var totalDots = getTotalDots();
    idx = Math.max(0, Math.min(totalDots - 1, idx));
    var step = getCardStep();
    els.selectionGrid.scrollTo({ left: idx * step, behavior: 'smooth' });
  }

  function scrollByOneCard(direction) {
    if (!els.selectionGrid) return;
    var step = getCardStep();
    if (step === 0) return;
    var current = els.selectionGrid.scrollLeft;
    var target = direction === 'next' ? current + step : current - step;
    els.selectionGrid.scrollTo({ left: target, behavior: 'smooth' });
  }

  function updateActiveDot() {
    if (!els.selectionNav || !els.selectionGrid) return;
    var current = getCurrentDot();
    var dots = els.selectionNav.querySelectorAll('[data-page]');
    dots.forEach(function (dot) {
      var idx = parseInt(dot.getAttribute('data-page'), 10);
      dot.classList.toggle('is-active', idx === current);
    });
    // Flèches : disable aux extrémités du SCROLL (pas des pages, car les
    // flèches avancent d'une seule card à la fois). Tolérance 2px pour
    // les arrondis de subpixel scroll.
    var prevBtn = els.selectionNav.querySelector('[data-nav="prev"]');
    var nextBtn = els.selectionNav.querySelector('[data-nav="next"]');
    var scrollLeft = els.selectionGrid.scrollLeft;
    var maxScroll = els.selectionGrid.scrollWidth - els.selectionGrid.clientWidth;
    if (prevBtn) prevBtn.disabled = (scrollLeft <= 2);
    if (nextBtn) nextBtn.disabled = (scrollLeft >= maxScroll - 2);
  }

  var selectionNavBound = false;
  function buildSelectionNav() {
    if (!els.selectionNav || !els.selectionGrid) return;
    var pages = getTotalDots();
    if (pages <= 1) {
      els.selectionNav.hidden = true;
      els.selectionNav.innerHTML = '';
      return;
    }
    els.selectionNav.hidden = false;

    var html = '<button type="button" class="mes-creations-selection__nav-arrow" data-nav="prev" aria-label="Précédent">';
    html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>';
    html += '</button>';
    html += '<div class="mes-creations-selection__nav-dots">';
    for (var i = 0; i < pages; i++) {
      html += '<button type="button" class="mes-creations-selection__nav-dot" data-page="' + i + '" aria-label="Page ' + (i + 1) + '"></button>';
    }
    html += '</div>';
    html += '<button type="button" class="mes-creations-selection__nav-arrow" data-nav="next" aria-label="Suivant">';
    html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>';
    html += '</button>';

    els.selectionNav.innerHTML = html;

    if (!selectionNavBound) {
      // Délégation clic sur dots + arrows
      els.selectionNav.addEventListener('click', function (e) {
        var dot = e.target.closest('[data-page]');
        if (dot) {
          var idx = parseInt(dot.getAttribute('data-page'), 10);
          if (!isNaN(idx)) scrollToDot(idx);
          return;
        }
        var arrow = e.target.closest('[data-nav]');
        if (arrow) {
          scrollByOneCard(arrow.getAttribute('data-nav'));
        }
      });
      // Update dot actif au scroll (throttle via rAF)
      var scrollRaf = null;
      els.selectionGrid.addEventListener('scroll', function () {
        if (scrollRaf) return;
        scrollRaf = requestAnimationFrame(function () {
          scrollRaf = null;
          updateActiveDot();
        });
      }, { passive: true });
      selectionNavBound = true;
    }

    updateActiveDot();
  }

  /* ─────────────────────────────────────────────
     CTA → dispatch événement
     ───────────────────────────────────────────── */

  // F2a-sexies : clic sur une chip-réponse de la prochaine question.
  // ORDRE CRITIQUE : update AVANT dispatch — sinon la modale hydrate l'ancien
  // état et ouvre sur la question qu'on vient de répondre au lieu de la
  // suivante. Le re-render de la card par le subscribe est masqué par
  // l'ouverture immédiate de la modale par-dessus (pas de flash visible).
  function handleChipAnswer(chip) {
    var stepId = chip.getAttribute('data-step-id');
    var slug   = chip.getAttribute('data-slug');
    var label  = chip.getAttribute('data-label');
    if (!stepId || !slug) return;

    // Fallback label depuis STEPS si attribut manquant
    if (!label) {
      for (var i = 0; i < STEPS.length; i++) {
        if (STEPS[i].id !== stepId) continue;
        var choices = STEPS[i].choices || [];
        for (var j = 0; j < choices.length; j++) {
          if (choices[j].slug === slug) { label = choices[j].label; break; }
        }
        break;
      }
    }

    // 1. Enregistre la réponse en PREMIER → sapiProject à jour.
    //    ⚠️ Effet de bord : le subscribe re-render renderInlineQuestion qui
    //    DÉTACHE le chip cliqué du DOM (innerHTML wipe). On ne peut donc
    //    plus dispatcher l'événement DEPUIS le chip (le bubbling cassé).
    if (window.sapiProject && typeof window.sapiProject.update === 'function') {
      var patch  = {}; patch[stepId]  = slug;
      var lpatch = {}; lpatch[stepId] = label || slug;
      window.sapiProject.update(patch, lpatch);
    }

    // 2. Dispatch sur document directement (le listener modale est sur
    //    document.addEventListener) — pas besoin de bubbling, le chip
    //    détaché ne porte plus.
    document.dispatchEvent(new CustomEvent('sapi:open-modal', {
      detail: { state: 's0' },
    }));
  }

  function bindCTAs() {
    if (!els.zone) return;
    els.zone.addEventListener('click', function (e) {
      // F2a-sexies : clic sur une réponse de la chip-question (priorité
      // sur open-modal). Le data-step-id distingue ces .choice (rendues
      // par renderInlineQuestion) des .choice du modale (qui n'ont pas
      // data-step-id).
      var chip = e.target.closest('.choice[data-step-id]');
      if (chip) {
        e.preventDefault();
        handleChipAnswer(chip);
        return;
      }

      // Round 4 — Click sur une pièce du room picker dans la card Conseil :
      // enregistre la pièce dans sapiProject puis ouvre la modale en s0
      // (determineInitialState basculera en s0-partiel sur la prochaine
      // question non répondue).
      var roomCard = e.target.closest('.conseiller-card--conseil .room-card[data-piece]');
      if (roomCard) {
        e.preventDefault();
        var slug = roomCard.getAttribute('data-piece');
        var label = roomCard.getAttribute('data-piece-label') || slug;
        if (window.sapiProject && typeof window.sapiProject.update === 'function') {
          window.sapiProject.update({ piece: slug }, { piece: label });
        }
        roomCard.dispatchEvent(new CustomEvent('sapi:open-modal', {
          bubbles: true,
          detail: { state: 's0' },
        }));
        return;
      }

      // CTAs explicites (data-action="open-modal" sur la Conseil card + lien Modifier)
      var btn = e.target.closest('[data-action="open-modal"]');
      if (btn) {
        e.preventDefault();
        var modalState = btn.getAttribute('data-modal-state') || 's0';
        btn.dispatchEvent(new CustomEvent('sapi:open-modal', {
          bubbles: true,
          detail: { state: modalState },
        }));
        if (!window.__sapiModalReady) {
          // eslint-disable-next-line no-console
          console.info('[sapi] open-modal demandé (state=' + modalState + ') — modale non chargée ?');
        }
        return;
      }

      // F2a-sexies-bis : clic ailleurs sur la card "Mon projet" → ouvre la
      // modale. Les éléments interactifs internes (chip-question, lien
      // Modifier, CTAs data-action) sont déjà captés ci-dessus, donc on
      // n'arrive ici qu'au clic sur la card elle-même.
      // Refonte /mes-creations/ : pour la variante englobante, on exclut
      // les zones interactives internes (cards produit, card sur-mesure,
      // slider nav) pour ne pas déclencher la modale en double action.
      var monProjetCard = e.target.closest('.conseiller-card--mon-projet');
      if (monProjetCard) {
        var isEnglobante = monProjetCard.classList.contains('mes-creations-selection__card');
        if (isEnglobante && e.target.closest('.product-card-cinetique, .mes-creations-surmesure-card, .mes-creations-selection__nav')) {
          return; // laisser l'élément interne gérer son propre clic
        }
        // État initial : s3 si projet complet (récap), s0 sinon
        var hasProj = window.sapiProject && window.sapiProject.hasProject();
        monProjetCard.dispatchEvent(new CustomEvent('sapi:open-modal', {
          bubbles: true,
          detail: { state: hasProj ? 's3' : 's0' },
        }));
        return;
      }

      // Round 4 — clic ailleurs sur la card "Conseil de Robin" → ouvre s0
      // (les boutons internes — .room-card / form freetext — sont déjà
      // captés plus haut, donc on n'arrive ici qu'au clic sur la zone
      // décorative de la card elle-même).
      var conseilCard = e.target.closest('.conseiller-card--conseil');
      if (conseilCard) {
        conseilCard.dispatchEvent(new CustomEvent('sapi:open-modal', {
          bubbles: true,
          detail: { state: 's0' },
        }));
      }
    });

    // Round 4 — Submit du champ texte libre dans la card Conseil : ouvre
    // la modale en s0 + passe detail.freetext qui basculera en chat S2.
    els.zone.addEventListener('submit', function (e) {
      var form = e.target.closest('.conseiller-card--conseil [data-room-picker-freetext]');
      if (!form) return;
      e.preventDefault();
      var input = form.querySelector('input[name="freetext"]');
      var text = (input && input.value || '').trim();
      if (!text) return;
      form.dispatchEvent(new CustomEvent('sapi:open-modal', {
        bubbles: true,
        detail: { state: 's0', freetext: text },
      }));
    });
  }

  /* ─────────────────────────────────────────────
     Init
     ───────────────────────────────────────────── */
  function init() {
    els.zone = document.querySelector('[data-conseiller-zone]');
    if (!els.zone) return; // pas sur /mes-creations/

    els.cardConseil       = els.zone.querySelector('[data-conseiller-card="conseil"]');
    els.cardMonProjet     = els.zone.querySelector('[data-conseiller-card="mon-projet"]');
    els.phrase            = els.zone.querySelector('[data-mon-projet-phrase]');
    els.phraseContent     = els.zone.querySelector('[data-mon-projet-phrase-content]');
    // F2a-sexies : zone chip-question + lien Modifier coin haut-droit
    els.inlineQuestion    = els.zone.querySelector('[data-inline-question]');
    els.editLink          = els.zone.querySelector('[data-mon-projet-edit]');
    // Refonte /mes-creations/ : slot grille de la card englobante + badge dynamique
    els.selectionGrid     = els.zone.querySelector('[data-mes-creations-selection-grid]');
    els.selectionNav      = els.zone.querySelector('[data-mes-creations-selection-nav]');
    els.badgeText         = els.zone.querySelector('[data-mon-projet-badge-text]');
    els.surmesureTemplate = els.zone.querySelector('[data-mes-creations-surmesure-template]');

    // Resize : rebuild nav (cards visibles par page change selon viewport)
    var resizeTimer = null;
    window.addEventListener('resize', function () {
      if (resizeTimer) clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () { buildSelectionNav(); }, 150);
    }, { passive: true });

    bindCTAs();
    render();

    // Réagit aux changements du projet (autre onglet, modale, dev tools)
    if (window.sapiProject && typeof window.sapiProject.subscribe === 'function') {
      window.sapiProject.subscribe(function () {
        render();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

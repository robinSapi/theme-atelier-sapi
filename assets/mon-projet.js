/**
 * Mon Projet — Bandeau questionnaire permanent
 * Gère : expand/collapse, visibilité conditionnelle, sélection,
 * reset en cascade, chips résumé, sauvegarde localStorage.
 *
 * @package Theme_Sapi_Maison
 */
(function() {
  'use strict';

  // ─── DOM ───
  var bar       = document.getElementById('mon-projet-bar');
  if (!bar) return;

  var toggle    = document.getElementById('mon-projet-toggle');
  var expanded  = document.getElementById('mon-projet-expanded');
  var chipsEl   = document.getElementById('mon-projet-chips');
  var resetBtn  = document.getElementById('mon-projet-reset');
  var selBtn    = document.getElementById('mon-projet-btn-selection');

  // Steps data passed from PHP via wp_localize_script
  var steps     = (typeof sapiMonProjet !== 'undefined' && sapiMonProjet.steps) ? sapiMonProjet.steps : [];

  // ─── State ───
  var state = {
    answers: {},
    labels: {},
    isOpen: false
  };

  var STORAGE_KEY = 'sapiGuidePrefs';
  var hoverTimer  = null;
  var isTouch     = !window.matchMedia('(hover: hover)').matches;

  // ─── Storage ───
  function loadState() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (raw) {
        var data = JSON.parse(raw);
        if (data && data.answers) {
          state.answers = data.answers;
          state.labels  = data.labels || {};
        } else if (data) {
          // Migration ancien format (pieceLabel, styleLabel, tailleLabel)
          // On ne peut pas reconstruire les answers, mais on préserve les prefs
          state.answers = {};
          state.labels  = {};
        }
      }
    } catch (e) { /* */ }
  }

  function saveState() {
    try {
      var essenceMap = { moderne: 'peuplier', ancien: 'okoume' };
      var tailleMap  = { petite: 0, moyenne: 1, grande: 2 };

      var style  = state.answers.style || null;
      var taille = state.answers.taille || null;
      var piece  = state.answers.piece || null;

      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        answers: state.answers,
        labels: state.labels,
        // Champs dérivés pour guide-personalize.js + shop badges
        essence: essenceMap[style] || null,
        tailleIndex: taille in tailleMap ? tailleMap[taille] : null,
        recommendedIds: [],
        pieceLabel: state.labels.piece || null,
        styleLabel: state.labels.style || null,
        tailleLabel: state.labels.taille || null
      }));
    } catch (e) { /* */ }
  }

  // ─── Visibilité conditionnelle ───
  function getVisibleSteps() {
    var visible = [];
    for (var i = 0; i < steps.length; i++) {
      var step = steps[i];
      var vis = step.visibility;

      if (vis === 'always') {
        visible.push(step.id);
        continue;
      }

      if (typeof vis === 'object' && vis !== null) {
        if (vis._or) {
          var orMatch = false;
          for (var g = 0; g < vis._or.length; g++) {
            var group = vis._or[g];
            var groupOk = true;
            for (var key in group) {
              if (!group.hasOwnProperty(key)) continue;
              var answer = state.answers[key];
              if (!answer || group[key].indexOf(answer) === -1) {
                groupOk = false;
                break;
              }
            }
            if (groupOk) { orMatch = true; break; }
          }
          if (orMatch) visible.push(step.id);
        } else {
          var show = true;
          for (var key in vis) {
            if (!vis.hasOwnProperty(key)) continue;
            var answer = state.answers[key];
            if (!answer || vis[key].indexOf(answer) === -1) {
              show = false;
              break;
            }
          }
          if (show) visible.push(step.id);
        }
      }
    }
    return visible;
  }

  function cleanInvisibleAnswers() {
    var visible = getVisibleSteps();
    var changed = false;
    for (var stepId in state.answers) {
      if (state.answers.hasOwnProperty(stepId) && visible.indexOf(stepId) === -1) {
        delete state.answers[stepId];
        delete state.labels[stepId];
        changed = true;
      }
    }
    return changed;
  }

  // ─── UI : Questions visibility ───
  function updateQuestionsVisibility() {
    var visible = getVisibleSteps();
    var questionEls = expanded.querySelectorAll('.mon-projet-question');
    for (var i = 0; i < questionEls.length; i++) {
      var stepId = questionEls[i].getAttribute('data-step');
      questionEls[i].setAttribute('data-visible', visible.indexOf(stepId) !== -1 ? 'true' : 'false');
    }
  }

  // ─── UI : Selected state on choices ───
  function updateChoicesUI() {
    var allChoices = expanded.querySelectorAll('.mon-projet-choice');
    for (var i = 0; i < allChoices.length; i++) {
      var btn = allChoices[i];
      var stepId = btn.getAttribute('data-step');
      var slug   = btn.getAttribute('data-slug');
      if (state.answers[stepId] === slug) {
        btn.classList.add('is-selected');
      } else {
        btn.classList.remove('is-selected');
      }
    }
  }

  // ─── UI : Chips summary ───
  function updateChips() {
    var visible = getVisibleSteps();
    var parts = [];
    for (var i = 0; i < visible.length; i++) {
      var label = state.labels[visible[i]];
      if (label) parts.push(label);
    }

    if (parts.length === 0) {
      chipsEl.innerHTML = '<span class="mon-projet-placeholder">Cliquez pour d\u00e9finir votre projet</span>';
    } else {
      var html = '';
      for (var i = 0; i < parts.length; i++) {
        if (i > 0) html += '<span class="mon-projet-chip-sep">\u00b7</span>';
        html += '<span class="mon-projet-chip">' + escapeHtml(parts[i]) + '</span>';
      }
      chipsEl.innerHTML = html;
    }

    // Bouton "Ma sélection" visible si quiz complet
    var allAnswered = isQuizComplete();
    if (selBtn) {
      selBtn.style.display = allAnswered ? '' : 'none';
    }
  }

  function isQuizComplete() {
    var visible = getVisibleSteps();
    for (var i = 0; i < visible.length; i++) {
      if (!state.answers[visible[i]]) return false;
    }
    return visible.length > 0;
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ─── UI : Dynamic question text ───
  function updateDynamicQuestions() {
    for (var i = 0; i < steps.length; i++) {
      var step = steps[i];
      if (!step.dynamic_question) continue;
      for (var condStep in step.dynamic_question) {
        if (!step.dynamic_question.hasOwnProperty(condStep)) continue;
        var answer = state.answers[condStep];
        var textMap = step.dynamic_question[condStep];
        if (answer && textMap[answer]) {
          var questionEl = expanded.querySelector('.mon-projet-question[data-step="' + step.id + '"] .mon-projet-question-label');
          if (questionEl) questionEl.textContent = textMap[answer];
        }
      }
    }
  }

  // ─── Expand / Collapse ───
  function openBanner() {
    if (state.isOpen) return;
    state.isOpen = true;
    expanded.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
  }

  function closeBanner() {
    if (!state.isOpen) return;
    state.isOpen = false;
    expanded.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
  }

  function toggleBanner() {
    if (state.isOpen) closeBanner();
    else openBanner();
  }

  // ─── Event handlers ───
  function onChoiceClick(e) {
    var btn = e.target.closest('.mon-projet-choice');
    if (!btn) return;

    var stepId = btn.getAttribute('data-step');
    var slug   = btn.getAttribute('data-slug');
    var label  = btn.getAttribute('data-label');

    // Si on clique sur le même choix → désélectionner
    if (state.answers[stepId] === slug) {
      delete state.answers[stepId];
      delete state.labels[stepId];
    } else {
      state.answers[stepId] = slug;
      state.labels[stepId]  = label;
    }

    // Reset en cascade
    cleanInvisibleAnswers();

    // Tout mettre à jour
    updateAll();
    saveState();

    // AJAX si quiz complet
    if (isQuizComplete()) {
      fetchResults();
    }
  }

  function onReset() {
    state.answers = {};
    state.labels  = {};
    try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* */ }
    updateAll();
  }

  function updateAll() {
    updateQuestionsVisibility();
    updateChoicesUI();
    updateChips();
    updateDynamicQuestions();
  }

  // ─── AJAX : Fetch results when quiz is complete ───
  function fetchResults() {
    if (typeof sapiMonProjet === 'undefined' || !sapiMonProjet.ajaxUrl) return;

    // 1. Fetch product recommendations
    var xhr = new XMLHttpRequest();
    xhr.open('POST', sapiMonProjet.ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4 || xhr.status !== 200) return;
      try {
        var resp = JSON.parse(xhr.responseText);
        if (resp.success && resp.data) {
          var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
          prefs.recommendedIds = resp.data.product_ids || [];
          if (resp.data.ai_text) {
            prefs.aiText = resp.data.ai_text;
          }
          localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));

          if (selBtn) selBtn.style.display = '';
        }
      } catch (e) { /* */ }
    };

    var params = 'action=sapi_guide_results'
      + '&nonce=' + encodeURIComponent(sapiMonProjet.nonce)
      + '&answers=' + encodeURIComponent(JSON.stringify(state.answers))
      + '&guide_website=';

    xhr.send(params);

    // 2. Fetch personalized AI texts (separate call, cached by answers hash)
    fetchAiTexts();
  }

  // ─── AJAX : Fetch personalized AI texts ───
  function fetchAiTexts() {
    if (typeof sapiMonProjet === 'undefined' || !sapiMonProjet.ajaxUrl) return;

    // Build a simple hash of current answers to detect changes
    var answersStr = JSON.stringify(state.answers);
    var hash = simpleHash(answersStr);

    // Check if we already have cached texts for these exact answers
    var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
    if (prefs.aiTextsHash === hash && prefs.aiTexts) return;

    var xhr2 = new XMLHttpRequest();
    xhr2.open('POST', sapiMonProjet.ajaxUrl, true);
    xhr2.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr2.onreadystatechange = function() {
      if (xhr2.readyState !== 4 || xhr2.status !== 200) return;
      try {
        var resp = JSON.parse(xhr2.responseText);
        if (resp.success && resp.data) {
          var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
          prefs.aiTexts = resp.data;
          prefs.aiTextsHash = hash;
          localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
        }
      } catch (e) { /* */ }
    };

    var params2 = 'action=sapi_mon_projet_texts'
      + '&nonce=' + encodeURIComponent(sapiMonProjet.nonce)
      + '&answers=' + encodeURIComponent(answersStr)
      + '&guide_website=';

    xhr2.send(params2);
  }

  function simpleHash(str) {
    var hash = 0;
    for (var i = 0; i < str.length; i++) {
      var chr = str.charCodeAt(i);
      hash = ((hash << 5) - hash) + chr;
      hash |= 0;
    }
    return hash.toString(36);
  }

  // ─── Init ───
  loadState();
  updateAll();

  // Toggle button
  toggle.addEventListener('click', function(e) {
    e.stopPropagation();
    toggleBanner();
  });

  // Collapsed area click → open
  bar.querySelector('.mon-projet-collapsed').addEventListener('click', function(e) {
    // Ne pas ouvrir si on clique sur un lien ou bouton
    if (e.target.closest('a') || e.target.closest('.mon-projet-toggle')) return;
    if (!state.isOpen) openBanner();
  });

  // Desktop hover
  if (!isTouch) {
    bar.addEventListener('mouseenter', function() {
      clearTimeout(hoverTimer);
      hoverTimer = setTimeout(openBanner, 200);
    });
    bar.addEventListener('mouseleave', function() {
      clearTimeout(hoverTimer);
      hoverTimer = setTimeout(closeBanner, 300);
    });
  }

  // Choice clicks (event delegation)
  expanded.addEventListener('click', onChoiceClick);

  // Reset
  if (resetBtn) {
    resetBtn.addEventListener('click', onReset);
  }

  // ─── Page-specific AI text injection ───
  applyAiTexts();

  function applyAiTexts() {
    try {
      var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
      if (!prefs.aiTexts) return;

      // Page Conseils
      var conseilsIntro = document.getElementById('conseils-perso-intro');
      var conseilsText = document.getElementById('conseils-perso-text');
      if (conseilsIntro && conseilsText && prefs.aiTexts.conseils_intro) {
        conseilsText.textContent = prefs.aiTexts.conseils_intro;
        conseilsIntro.style.display = '';
      }

      // Pages catégorie — AI subtitle
      var catSubtitle = document.getElementById('category-perso-subtitle');
      var catText = document.getElementById('category-perso-text');
      if (catSubtitle && catText && prefs.aiTexts.category_texts) {
        var catSlug = catSubtitle.getAttribute('data-category-slug');
        if (catSlug && prefs.aiTexts.category_texts[catSlug]) {
          catText.textContent = prefs.aiTexts.category_texts[catSlug];
          catSubtitle.style.display = '';
        }
      }

      // Pages catégorie — reorder products (recommended first)
      if (prefs.recommendedIds && prefs.recommendedIds.length > 0) {
        reorderProducts(prefs.recommendedIds);
      }

      // Page Sur-mesure — AI intro
      var surMesureIntro = document.getElementById('sur-mesure-perso-intro');
      var surMesureText = document.getElementById('sur-mesure-perso-text');
      if (surMesureIntro && surMesureText && prefs.aiTexts.sur_mesure_intro) {
        surMesureText.textContent = prefs.aiTexts.sur_mesure_intro;
        surMesureIntro.style.display = '';
      }

      // Page Sur-mesure — pre-fill form
      prefillSurMesureForm(prefs);
    } catch (e) { /* */ }
  }

  function reorderProducts(recommendedIds) {
    var grid = document.querySelector('.products.columns-3');
    if (!grid) return;

    var cards = grid.querySelectorAll('.product-card-cinetique');
    if (!cards.length) return;

    var idsStr = recommendedIds.map(function(id) { return String(id); });
    var recommended = [];
    var others = [];

    for (var i = 0; i < cards.length; i++) {
      var cardId = cards[i].getAttribute('data-id');
      if (cardId && idsStr.indexOf(cardId) !== -1) {
        recommended.push(cards[i]);
      } else {
        others.push(cards[i]);
      }
    }

    if (recommended.length === 0) return;

    // Hide grid, reorder, fade in
    grid.style.opacity = '0';
    grid.style.transition = 'opacity 0.3s ease';

    for (var j = 0; j < recommended.length; j++) {
      grid.insertBefore(recommended[j], grid.firstChild);
    }

    requestAnimationFrame(function() {
      grid.style.opacity = '1';
    });
  }

  function prefillSurMesureForm(prefs) {
    if (!prefs.answers) return;
    var form = document.querySelector('.sur-mesure-form, .wpcf7-form, #sur-mesure-form');
    if (!form) return;

    var fieldMap = {
      piece: ['piece', 'destination', 'pièce'],
      taille: ['taille', 'dimensions', 'surface'],
      style: ['style', 'ambiance'],
      sortie: ['sortie', 'type-luminaire', 'installation'],
    };

    for (var answerKey in fieldMap) {
      if (!prefs.answers[answerKey]) continue;
      var names = fieldMap[answerKey];
      for (var n = 0; n < names.length; n++) {
        var input = form.querySelector('[name*="' + names[n] + '"]');
        if (input && !input.value) {
          var label = prefs.labels && prefs.labels[answerKey] ? prefs.labels[answerKey] : prefs.answers[answerKey];
          input.value = label;
          break;
        }
      }
    }
  }

})();

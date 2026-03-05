(function () {
  'use strict';

  // ================================================================
  // STATE
  // ================================================================
  var state = {
    currentStepId: null,
    answers: {},          // { sortie: 'plafond', hauteur: 'standard', ... }
    labels: {},           // { sortie: 'Au plafond', ... }
    isShowingResults: false,
    isQuizStarted: false,
    aiText: '',
    loadingInterval: null,
  };

  var SESSION_KEY = 'sapiGuideV2';

  // ================================================================
  // DOM REFS
  // ================================================================
  var dom = {};

  // ================================================================
  // STEP VISIBILITY LOGIC
  // ================================================================

  /**
   * Compute which steps are currently visible based on answers.
   * Returns an array of step IDs in order.
   */
  function getVisibleSteps() {
    var steps = sapiGuide.steps;
    var visible = [];

    for (var i = 0; i < steps.length; i++) {
      var step = steps[i];
      var vis = step.visibility;

      if (vis === 'always') {
        visible.push(step.id);
        continue;
      }

      if (typeof vis === 'object' && vis !== null) {
        // OR logic: { _or: [ {condA: [vals]}, {condB: [vals]} ] }
        if (vis._or) {
          var orGroups = vis._or;
          var orMatch = false;
          for (var g = 0; g < orGroups.length; g++) {
            var group = orGroups[g];
            var groupOk = true;
            for (var condStep in group) {
              if (!group.hasOwnProperty(condStep)) continue;
              var allowed = group[condStep];
              var answer = state.answers[condStep];
              if (!answer || allowed.indexOf(answer) === -1) {
                groupOk = false;
                break;
              }
            }
            if (groupOk) { orMatch = true; break; }
          }
          if (orMatch) visible.push(step.id);
        } else {
          // AND logic (existing behavior)
          var show = true;
          for (var condStep in vis) {
            if (!vis.hasOwnProperty(condStep)) continue;
            var allowed = vis[condStep];
            var answer = state.answers[condStep];
            if (!answer || allowed.indexOf(answer) === -1) {
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

  /**
   * Get the step data object by ID.
   */
  function getStepById(stepId) {
    var steps = sapiGuide.steps;
    for (var i = 0; i < steps.length; i++) {
      if (steps[i].id === stepId) return steps[i];
    }
    return null;
  }

  /**
   * Get the next visible step after the given one, or null if it's the last.
   */
  function getNextVisibleStep(currentId) {
    var visible = getVisibleSteps();
    var idx = visible.indexOf(currentId);
    if (idx === -1 || idx >= visible.length - 1) return null;
    return visible[idx + 1];
  }

  /**
   * Get the previous visible step before the given one, or null if it's the first.
   */
  function getPrevVisibleStep(currentId) {
    var visible = getVisibleSteps();
    var idx = visible.indexOf(currentId);
    if (idx <= 0) return null;
    return visible[idx - 1];
  }

  // ================================================================
  // INIT
  // ================================================================
  function init() {
    dom.intro           = document.getElementById('guide-intro');
    dom.quiz            = document.getElementById('guide-quiz');
    dom.steps           = document.querySelectorAll('.guide-step');
    dom.backBtn         = document.getElementById('guide-back');
    dom.results         = document.getElementById('guide-results');
    dom.resultsTags     = document.getElementById('guide-results-tags');
    dom.resultsLoading  = document.getElementById('guide-results-loading');
    dom.productsGrid    = document.getElementById('guide-result-products-grid');
    dom.aiText          = document.getElementById('guide-ai-text');
    dom.aiTextContent   = document.getElementById('guide-ai-text-content');
    dom.followupBtns    = document.getElementById('guide-followup-buttons');
    dom.resultsTitle    = document.getElementById('guide-results-title');
    dom.resultError     = document.getElementById('guide-result-error');
    dom.restartBtn      = document.getElementById('guide-restart');
    dom.resetBtn        = document.getElementById('guide-reset');
    dom.startBtn        = document.getElementById('guide-start-btn');
    dom.restartWrap     = document.getElementById('guide-restart-wrap');
    dom.ambianceBanner  = document.getElementById('guide-ambiance-banner');
    dom.ambianceImg     = document.getElementById('guide-ambiance-img');

    if (!dom.steps.length) return;

    // Check for saved session
    var saved = restoreSession();

    // Bind events
    if (dom.startBtn) {
      dom.startBtn.addEventListener('click', startQuiz);
    }

    bindChoiceClicks();
    bindBackButton();
    bindRestartButton();
    bindResetButton();
    bindKeyboard();

    // Restore session if valid V2 format
    if (saved && saved.currentStepId && typeof saved.currentStepId === 'string' && Object.keys(saved.answers).length > 0) {
      state.currentStepId = saved.currentStepId;
      state.answers = saved.answers;
      state.labels = saved.labels || {};
      startQuiz();
      renderStep(state.currentStepId, 'none');
      markPreviousAnswers();
    }

    // Pre-select room from ?piece= URL parameter (homepage cards)
    var urlParams = new URLSearchParams(window.location.search);
    var preselectedPiece = urlParams.get('piece');
    if (preselectedPiece && !state.isQuizStarted) {
      var pieceCard = document.querySelector('.guide-choice-card[data-step="piece"][data-slug="' + preselectedPiece + '"]');
      if (pieceCard) {
        state.answers['piece'] = preselectedPiece;
        state.labels['piece'] = pieceCard.getAttribute('data-label') || preselectedPiece;
        pieceCard.classList.add('is-selected');
        startQuiz();
        var nextStep = getNextVisibleStep('piece');
        if (nextStep) {
          state.currentStepId = nextStep;
          renderStep(nextStep, 'none');
        }
        saveSession();
      }
    }
  }

  // ================================================================
  // SESSION PERSISTENCE
  // ================================================================
  function saveSession() {
    try {
      sessionStorage.setItem(SESSION_KEY, JSON.stringify({
        currentStepId: state.currentStepId,
        answers: state.answers,
        labels: state.labels,
      }));
    } catch (e) { /* sessionStorage might be disabled */ }
  }

  function restoreSession() {
    try {
      var raw = sessionStorage.getItem(SESSION_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function clearSession() {
    try {
      sessionStorage.removeItem(SESSION_KEY);
      // Also clear old V1 session if present
      sessionStorage.removeItem('sapiGuide');
    } catch (e) { /* */ }
  }

  // ================================================================
  // START QUIZ
  // ================================================================
  function startQuiz() {
    if (state.isQuizStarted) return;
    state.isQuizStarted = true;

    // Hide intro
    if (dom.intro) {
      dom.intro.classList.add('is-hidden');
    }

    // Show quiz
    if (dom.quiz) {
      dom.quiz.setAttribute('aria-hidden', 'false');
    }

    // Set first step if not set
    if (!state.currentStepId) {
      var visible = getVisibleSteps();
      state.currentStepId = visible[0] || sapiGuide.steps[0].id;
    }

    renderStep(state.currentStepId, 'none');
  }

  // ================================================================
  // NAVIGATION
  // ================================================================
  function renderStep(stepId, direction) {
    var currentActive = document.querySelector('.guide-step.is-active');
    var nextStep = document.querySelector('.guide-step[data-step="' + stepId + '"]');

    if (!nextStep) return;

    // Helper: activate next step with fade in
    function activateNext() {
      nextStep.style.transition = 'none';
      nextStep.classList.remove('is-active', 'is-exiting');
      nextStep.style.opacity = '0';

      // Force reflow
      nextStep.getBoundingClientRect();

      // Restore transition and activate
      nextStep.style.transition = '';
      nextStep.style.opacity = '';
      nextStep.classList.add('is-active');

      // Update dynamic question text if applicable
      var stepData = getStepById(stepId);
      var heading = nextStep.querySelector('.guide-step-question');
      if (heading && stepData && stepData.dynamic_question) {
        for (var depStep in stepData.dynamic_question) {
          if (!stepData.dynamic_question.hasOwnProperty(depStep)) continue;
          var answer = state.answers[depStep];
          if (answer && stepData.dynamic_question[depStep][answer]) {
            heading.textContent = stepData.dynamic_question[depStep][answer];
            break;
          }
        }
      }

      // Focus question heading for accessibility
      if (heading) {
        heading.setAttribute('tabindex', '-1');
        heading.focus({ preventScroll: true });
      }
    }

    if (currentActive && currentActive !== nextStep && direction !== 'none') {
      currentActive.classList.add('is-exiting');
      currentActive.classList.remove('is-active');

      currentActive.addEventListener('transitionend', function handler() {
        currentActive.classList.remove('is-exiting');
        currentActive.removeEventListener('transitionend', handler);
        activateNext();
      });
    } else if (currentActive && currentActive !== nextStep) {
      currentActive.classList.remove('is-active');
      nextStep.classList.add('is-active');
    } else if (direction !== 'none') {
      activateNext();
    } else {
      nextStep.classList.add('is-active');
    }

    updateBackButton(stepId);
    updateStepCounter(stepId);
  }

  function advanceToStep(stepId) {
    state.currentStepId = stepId;
    saveSession();
    renderStep(stepId, 'forward');
  }

  function goBack() {
    var prevStep = getPrevVisibleStep(state.currentStepId);
    if (!prevStep) return;

    // Clear the answer for the current step
    delete state.answers[state.currentStepId];
    delete state.labels[state.currentStepId];

    // Also clear answers for steps that become invisible after going back
    cleanInvisibleAnswers();

    state.currentStepId = prevStep;
    saveSession();
    renderStep(prevStep, 'backward');
  }

  /**
   * Remove answers for steps that are no longer visible.
   */
  function cleanInvisibleAnswers() {
    var visible = getVisibleSteps();
    var allStepIds = sapiGuide.steps.map(function (s) { return s.id; });

    allStepIds.forEach(function (id) {
      if (visible.indexOf(id) === -1 && state.answers[id]) {
        delete state.answers[id];
        delete state.labels[id];
        // Deselect cards for that step
        var stepEl = document.querySelector('.guide-step[data-step="' + id + '"]');
        if (stepEl) {
          stepEl.querySelectorAll('.guide-choice-card.is-selected').forEach(function (c) {
            c.classList.remove('is-selected');
          });
        }
      }
    });
  }

  // ================================================================
  // CHOICE SELECTION
  // ================================================================
  function bindChoiceClicks() {
    document.addEventListener('click', function (e) {
      var card = e.target.closest('.guide-choice-card');
      if (!card) return;

      var stepId = card.getAttribute('data-step');
      var slug = card.getAttribute('data-slug');
      var label = card.getAttribute('data-label');

      // Save answer
      state.answers[stepId] = slug;
      state.labels[stepId] = label;

      // Visual: deselect siblings, select this one
      var siblings = card.parentElement.querySelectorAll('.guide-choice-card');
      siblings.forEach(function (s) { s.classList.remove('is-selected'); });
      card.classList.add('is-selected');

      // Clean invisible answers (a new choice might change which steps are visible)
      cleanInvisibleAnswers();

      // Determine next step
      var nextStepId = getNextVisibleStep(stepId);

      if (!nextStepId) {
        // Last visible step — show results
        saveSession();
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            showResults();
          });
        });
      } else {
        // Advance
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            advanceToStep(nextStepId);
          });
        });
      }
    });
  }

  function markPreviousAnswers() {
    Object.keys(state.answers).forEach(function (stepId) {
      var slug = state.answers[stepId];
      var card = document.querySelector('.guide-choice-card[data-step="' + stepId + '"][data-slug="' + slug + '"]');
      if (card) {
        card.classList.add('is-selected');
      }
    });
  }

  // ================================================================
  // RESULTS
  // ================================================================
  function showResults() {
    state.isShowingResults = true;

    // Hide quiz
    if (dom.quiz) {
      dom.quiz.setAttribute('aria-hidden', 'true');
    }

    // Hide back button
    if (dom.backBtn) dom.backBtn.hidden = true;

    // Show results
    if (dom.results) {
      dom.results.setAttribute('aria-hidden', 'false');
      dom.results.classList.add('is-visible');
    }

    // Render tags
    renderAnswerTags();

    // Fetch products + AI recommendation
    fetchResults();

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function renderAnswerTags() {
    if (!dom.resultsTags) return;

    var visible = getVisibleSteps();
    var html = '';

    visible.forEach(function (stepId) {
      var label = state.labels[stepId];
      if (!label) return;

      html += '<span class="guide-answer-tag">'
            + '<span class="guide-tag-label">' + escapeHtml(label) + '</span>'
            + '</span>';
    });

    // Restart button inline at the end of tags
    html += '<button class="guide-restart-inline" id="guide-restart-inline" type="button">'
          + '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>'
          + ' Recommencer'
          + '</button>';

    dom.resultsTags.innerHTML = html;

    // Bind restart inline button
    var inlineBtn = document.getElementById('guide-restart-inline');
    if (inlineBtn) {
      inlineBtn.addEventListener('click', function () {
        if (dom.restart) dom.restart.click();
      });
    }
  }

  // ================================================================
  // FETCH RESULTS + AI
  // ================================================================
  function fetchResults() {
    // Show loading
    if (dom.resultsLoading) dom.resultsLoading.setAttribute('aria-hidden', 'false');
    if (dom.productsGrid) dom.productsGrid.style.display = 'none';
    if (dom.aiText) dom.aiText.style.display = 'none';
    if (dom.followupBtns) dom.followupBtns.style.display = 'none';
    if (dom.resultError) dom.resultError.style.display = 'none';
    if (dom.restartWrap) dom.restartWrap.style.display = 'none';

    // Start loading animation
    startLoadingAnimation();

    var formData = new FormData();
    formData.append('action', 'sapi_guide_results');
    formData.append('nonce', sapiGuide.nonce);
    formData.append('answers', JSON.stringify(state.answers));

    fetch(sapiGuide.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
      stopLoadingAnimation();
      if (dom.resultsLoading) dom.resultsLoading.setAttribute('aria-hidden', 'true');
      if (dom.restartWrap) dom.restartWrap.style.display = '';

      if (data.success && data.data) {
        var d = data.data;

        // Reset title for normal results
        if (dom.resultsTitle) dom.resultsTitle.textContent = 'Ce que Robin vous propose';

        // Display AI recommendation text
        if (d.ai_text) {
          state.aiText = d.ai_text;
          renderAiText(d.ai_text);
        }

        // Display products grid (up to 4, or 3 + sur mesure card)
        if (d.products && d.products.length > 0) {
          renderProductsGrid(d.products, d.show_sur_mesure || false, d.sur_mesure_reason || '');

          // Show ambiance_2 photo of first product as full-width banner
          var firstProduct = d.products[0];
          if (firstProduct && firstProduct.ambiance_2 && dom.ambianceBanner && dom.ambianceImg) {
            dom.ambianceImg.src = firstProduct.ambiance_2;
            dom.ambianceImg.alt = firstProduct.title + ' \u2014 ambiance';
            dom.ambianceBanner.style.display = '';
          }
        }

        // Display follow-up buttons
        if (d.followup_buttons && d.followup_buttons.length > 0) {
          renderFollowupButtons(d.followup_buttons);
        }

      } else {
        renderResultsError();
      }
    })
    .catch(function () {
      stopLoadingAnimation();
      if (dom.resultsLoading) dom.resultsLoading.setAttribute('aria-hidden', 'true');
      if (dom.restartWrap) dom.restartWrap.style.display = '';
      renderResultsError();
    });
  }

  // ================================================================
  // LOADING ANIMATION
  // ================================================================
  function startLoadingAnimation() {
    var steps = document.querySelectorAll('.guide-loading-step');
    var stepIndex = 0;

    // Reset all
    steps.forEach(function (s) {
      s.classList.remove('is-active', 'is-done');
    });
    if (steps.length > 0) steps[0].classList.add('is-active');

    state.loadingInterval = setInterval(function () {
      if (stepIndex > 0 && stepIndex <= steps.length) {
        steps[stepIndex - 1].classList.remove('is-active');
        steps[stepIndex - 1].classList.add('is-done');
      }
      if (stepIndex < steps.length) {
        steps[stepIndex].classList.add('is-active');
      }
      stepIndex++;
      if (stepIndex > steps.length) {
        clearInterval(state.loadingInterval);
        state.loadingInterval = null;
      }
    }, 1200);
  }

  function stopLoadingAnimation() {
    if (state.loadingInterval) {
      clearInterval(state.loadingInterval);
      state.loadingInterval = null;
    }
  }

  // ================================================================
  // RENDER AI TEXT
  // ================================================================
  function renderAiText(text) {
    if (!dom.aiTextContent || !dom.aiText) return;
    // Strip any leftover markdown bold markers
    var clean = text.replace(/\*\*(.*?)\*\*/g, '$1');
    dom.aiTextContent.textContent = clean;
    dom.aiText.style.display = '';
  }

  // ================================================================
  // RENDER PRODUCTS GRID
  // ================================================================
  function renderProductsGrid(products, showSurMesure, surMesureReason) {
    if (!dom.productsGrid) return;

    var html = '';
    for (var i = 0; i < products.length; i++) {
      var p = products[i];
      var variationHtml = '';
      if (p.variation_label) {
        variationHtml += '<p class="guide-result-variation">'
          + 'Mat\u00e9riau conseill\u00e9 : ' + escapeHtml(p.variation_label)
          + '</p>';
      }
      if (p.size_label) {
        variationHtml += '<p class="guide-result-variation">'
          + 'Taille recommand\u00e9e : ' + escapeHtml(p.size_label)
          + '</p>';
      }

      html += '<article class="guide-result-card">'
        + '<a href="' + escapeHtml(p.permalink) + '" class="guide-result-card-link">'
        + '<div class="guide-result-image-wrap">'
        + '<img class="guide-result-image" src="' + escapeHtml(p.image) + '" srcset="" alt="' + escapeHtml(p.image_alt || p.title) + '" />'
        + '</div>'
        + '<h3 class="guide-result-name">' + escapeHtml(p.title) + '</h3>'
        + '<p class="guide-result-price">' + p.price + '</p>'
        + variationHtml
        + '<span class="guide-result-cta">'
        + 'Voir ce luminaire'
        + ' <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>'
        + '</span>'
        + '</a>'
        + '</article>';
    }

    // Sur mesure card (4th slot) — content adapts to reason
    if (showSurMesure) {
      var smTitle = 'Cr\u00e9ation sur mesure';
      var smDesc = 'Un luminaire unique, con\u00e7u sp\u00e9cialement pour votre espace par Robin.';

      if (surMesureReason === 'grappe') {
        smTitle = 'Cr\u00e9ation multi-ampoules';
        smDesc = 'Votre situation m\u00e9rite un luminaire d\u2019exception, comme un luminaire avec plusieurs ampoules\u00a0! \u00c7a tombe bien, moi (Robin), j\u2019adore cr\u00e9er et je serais ravi d\u2019en discuter avec vous.';
      }

      html += '<article class="guide-result-card guide-result-card--surmesure">'
        + '<div class="guide-surmesure-card">'
        + '<div class="guide-surmesure-icon">'
        + '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
        + '<path d="M12 2L2 7l10 5 10-5-10-5z"/>'
        + '<path d="M2 17l10 5 10-5"/>'
        + '<path d="M2 12l10 5 10-5"/>'
        + '</svg>'
        + '</div>'
        + '<h3 class="guide-result-name">' + smTitle + '</h3>'
        + '<p class="guide-surmesure-desc">' + smDesc + '</p>'
        + '<a href="/contact/" class="guide-result-cta guide-surmesure-cta">'
        + 'Contacter Robin'
        + ' <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>'
        + '</a>'
        + '</div>'
        + '</article>';
    }

    dom.productsGrid.innerHTML = html;
    dom.productsGrid.style.display = '';
  }

  // ================================================================
  // FOLLOW-UP BUTTONS
  // ================================================================
  function renderFollowupButtons(buttons) {
    // Phase B (chat interactif) pas encore implémentée — boutons masqués
    // Réactiver cette fonction quand Phase B sera prête
    if (!dom.followupBtns) return;
    dom.followupBtns.style.display = 'none';
  }

  function renderResultsError() {
    if (dom.productsGrid) dom.productsGrid.style.display = 'none';
    if (dom.aiText) dom.aiText.style.display = 'none';
    if (dom.resultError) dom.resultError.style.display = '';
  }

  // ================================================================
  // UI HELPERS
  // ================================================================
  function updateBackButton(stepId) {
    var visible = getVisibleSteps();
    var isFirst = visible.indexOf(stepId) <= 0;
    if (dom.backBtn) dom.backBtn.hidden = isFirst;
    if (dom.resetBtn) dom.resetBtn.hidden = isFirst;
  }

  function updateStepCounter(stepId) {
    var visible = getVisibleSteps();
    var idx = visible.indexOf(stepId);
    var counterEl = document.querySelector('.guide-step.is-active [data-step-counter]');
    if (!counterEl) {
      var stepEl = document.querySelector('.guide-step[data-step="' + stepId + '"]');
      if (stepEl) counterEl = stepEl.querySelector('[data-step-counter]');
    }
    if (counterEl && idx !== -1) {
      var pct = ((idx + 1) / visible.length) * 100;
      counterEl.style.width = pct + '%';
    }
  }

  function bindBackButton() {
    if (dom.backBtn) {
      dom.backBtn.addEventListener('click', function () {
        if (state.isShowingResults) {
          goBackFromResults();
        } else {
          goBack();
        }
      });
    }
  }

  function goBackFromResults() {
    state.isShowingResults = false;

    // Find last visible step
    var visible = getVisibleSteps();
    var lastStep = visible[visible.length - 1];
    state.currentStepId = lastStep;

    // Clear the last step's answer
    delete state.answers[lastStep];
    delete state.labels[lastStep];
    var stepEl = document.querySelector('.guide-step[data-step="' + lastStep + '"]');
    if (stepEl) {
      stepEl.querySelectorAll('.guide-choice-card.is-selected').forEach(function (c) {
        c.classList.remove('is-selected');
      });
    }

    // Hide results, show quiz
    if (dom.results) {
      dom.results.setAttribute('aria-hidden', 'true');
      dom.results.classList.remove('is-visible');
    }
    if (dom.quiz) {
      dom.quiz.setAttribute('aria-hidden', 'false');
    }

    saveSession();
    renderStep(lastStep, 'none');
  }

  function bindRestartButton() {
    if (dom.restartBtn) {
      dom.restartBtn.addEventListener('click', function () {
        resetQuiz();
      });
    }
  }

  function bindResetButton() {
    if (dom.resetBtn) {
      dom.resetBtn.addEventListener('click', function () {
        resetQuiz();
      });
    }
  }

  function resetQuiz() {
    state.currentStepId = null;
    state.answers = {};
    state.labels = {};
    state.isShowingResults = false;
    state.aiText = '';
    clearSession();

    // Deselect all cards
    document.querySelectorAll('.guide-choice-card.is-selected').forEach(function (c) {
      c.classList.remove('is-selected');
    });

    // Hide results if visible, show quiz
    if (dom.results) {
      dom.results.setAttribute('aria-hidden', 'true');
      dom.results.classList.remove('is-visible');
    }
    if (dom.ambianceBanner) dom.ambianceBanner.style.display = 'none';
    if (dom.quiz) {
      dom.quiz.setAttribute('aria-hidden', 'false');
    }

    // Reset to first step
    var visible = getVisibleSteps();
    state.currentStepId = visible[0];
    renderStep(state.currentStepId, 'none');
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function bindKeyboard() {
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && state.isQuizStarted) {
        if (state.isShowingResults) {
          goBackFromResults();
        } else {
          goBack();
        }
      }
    });
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  // ================================================================
  // BOOT
  // ================================================================
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();

(function () {
  'use strict';

  // ================================================================
  // STATE
  // ================================================================
  var state = {
    currentStep: 1,
    totalSteps: 6,
    answers: {},   // { pa_piece: 'salon-sejour', ... }
    labels: {},    // { pa_piece: 'Salon / Séjour', ... }
    isShowingResults: false,
    isQuizStarted: false,
  };

  var SESSION_KEY = 'sapiGuide';

  // ================================================================
  // DOM REFS
  // ================================================================
  var dom = {};

  // ================================================================
  // INIT
  // ================================================================
  function init() {
    dom.intro            = document.getElementById('guide-intro');
    dom.quiz             = document.getElementById('guide-quiz');
    dom.steps            = document.querySelectorAll('.guide-step');
    dom.progressBar      = document.querySelector('.guide-progress-bar');
    dom.progressSegments = document.querySelectorAll('.guide-progress-segment');
    dom.backBtn          = document.getElementById('guide-back');
    dom.stepCounter      = document.querySelector('.guide-step-counter');
    dom.currentStepEl    = document.getElementById('guide-current-step');
    dom.results          = document.getElementById('guide-results');
    dom.resultsGrid      = document.getElementById('guide-results-grid');
    dom.resultsTags      = document.getElementById('guide-results-tags');
    dom.resultsSubtitle  = document.getElementById('guide-results-subtitle');
    dom.resultsLoading   = document.getElementById('guide-results-loading');
    dom.restartBtn       = document.getElementById('guide-restart');
    dom.startBtn         = document.getElementById('guide-start-btn');
    dom.restartWrap      = document.getElementById('guide-restart-wrap');

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
    bindKeyboard();

    // Check for ?piece= URL parameter (from homepage room picker)
    var urlParams = new URLSearchParams(window.location.search);
    var pieceParam = urlParams.get('piece');

    if (pieceParam) {
      // Find the matching choice card for step 1 (pa_piece)
      var step1 = document.querySelector('.guide-step[data-step="1"]');
      if (step1) {
        var matchingCard = step1.querySelector('.guide-choice-card[data-slug="' + pieceParam + '"]');
        if (matchingCard) {
          var attr = matchingCard.getAttribute('data-attribute');
          var label = matchingCard.getAttribute('data-label');
          state.answers[attr] = pieceParam;
          state.labels[attr] = label;
          state.currentStep = 2;
          startQuiz();
          matchingCard.classList.add('is-selected');
          renderStep(2, 'none');
          saveSession();
          // Clean URL without reloading
          window.history.replaceState({}, '', window.location.pathname);
          return;
        }
      }
    }

    // If we have a saved session, restore
    if (saved && saved.step > 0 && Object.keys(saved.answers).length > 0) {
      state.currentStep = saved.step;
      state.answers = saved.answers;
      state.labels = saved.labels || {};
      startQuiz();
      renderStep(state.currentStep, 'none');
      // Mark previously selected cards
      markPreviousAnswers();
    }
  }

  // ================================================================
  // SESSION PERSISTENCE
  // ================================================================
  function saveSession() {
    try {
      sessionStorage.setItem(SESSION_KEY, JSON.stringify({
        step: state.currentStep,
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
    try { sessionStorage.removeItem(SESSION_KEY); } catch (e) { /* */ }
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

    // Show progress bar
    if (dom.progressBar) {
      dom.progressBar.classList.add('is-visible');
    }

    renderStep(state.currentStep, 'none');
  }

  // ================================================================
  // NAVIGATION
  // ================================================================
  function renderStep(stepNum, direction) {
    var currentActive = document.querySelector('.guide-step.is-active');
    var nextStep = document.querySelector('.guide-step[data-step="' + stepNum + '"]');

    if (!nextStep) return;

    if (currentActive && currentActive !== nextStep && direction !== 'none') {
      var exitClass = direction === 'backward' ? 'is-exiting-right' : 'is-exiting-left';
      currentActive.classList.add(exitClass);
      currentActive.classList.remove('is-active');

      // Clean up after transition
      currentActive.addEventListener('transitionend', function handler() {
        currentActive.classList.remove(exitClass);
        currentActive.removeEventListener('transitionend', handler);
      });
    } else if (currentActive && currentActive !== nextStep) {
      currentActive.classList.remove('is-active');
    }

    // For non-none directions, set up entrance
    if (direction !== 'none') {
      var enterFromClass = direction === 'backward' ? 'is-exiting-left' : 'is-exiting-right';
      // Temporarily position at start
      nextStep.style.transition = 'none';
      nextStep.classList.remove('is-active', 'is-exiting-left', 'is-exiting-right');
      nextStep.style.opacity = '0';
      nextStep.style.transform = direction === 'backward' ? 'translateX(-60px)' : 'translateX(60px)';

      // Force reflow
      nextStep.getBoundingClientRect();

      // Restore transition and activate
      nextStep.style.transition = '';
      nextStep.style.opacity = '';
      nextStep.style.transform = '';
      nextStep.classList.add('is-active');
    } else {
      nextStep.classList.add('is-active');
    }

    updateProgress(stepNum);
    updateBackButton(stepNum);
    updateStepCounter(stepNum);

    // Focus question heading for accessibility
    var heading = nextStep.querySelector('.guide-step-question');
    if (heading && direction !== 'none') {
      heading.setAttribute('tabindex', '-1');
      heading.focus({ preventScroll: true });
    }
  }

  function advanceToStep(stepNum) {
    state.currentStep = stepNum;
    saveSession();
    renderStep(stepNum, 'forward');
  }

  function goBack() {
    if (state.currentStep <= 1) return;

    // Clear the answer for the current step before going back
    var currentAttr = getAttributeForStep(state.currentStep);
    if (currentAttr) {
      delete state.answers[currentAttr];
      delete state.labels[currentAttr];
    }

    state.currentStep--;
    saveSession();
    renderStep(state.currentStep, 'backward');
  }

  function getAttributeForStep(stepNum) {
    var stepEl = document.querySelector('.guide-step[data-step="' + stepNum + '"]');
    if (!stepEl) return null;
    var firstCard = stepEl.querySelector('.guide-choice-card');
    return firstCard ? firstCard.getAttribute('data-attribute') : null;
  }

  // ================================================================
  // CHOICE SELECTION
  // ================================================================
  function bindChoiceClicks() {
    document.addEventListener('click', function (e) {
      var card = e.target.closest('.guide-choice-card');
      if (!card) return;

      var attribute = card.getAttribute('data-attribute');
      var slug = card.getAttribute('data-slug');
      var label = card.getAttribute('data-label');

      // Save answer
      state.answers[attribute] = slug;
      state.labels[attribute] = label;

      // Visual: deselect siblings, select this one
      var siblings = card.parentElement.querySelectorAll('.guide-choice-card');
      siblings.forEach(function (s) { s.classList.remove('is-selected'); });
      card.classList.add('is-selected');

      var nextStep = state.currentStep + 1;

      if (nextStep > state.totalSteps) {
        // All done - show results
        saveSession();
        // Small visual delay so user sees their selection
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            showResults();
          });
        });
      } else {
        // Advance
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            advanceToStep(nextStep);
          });
        });
      }
    });
  }

  function markPreviousAnswers() {
    // For each saved answer, mark the card as selected
    Object.keys(state.answers).forEach(function (attr) {
      var slug = state.answers[attr];
      var card = document.querySelector('.guide-choice-card[data-attribute="' + attr + '"][data-slug="' + slug + '"]');
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

    // Hide step counter and back button
    if (dom.backBtn) dom.backBtn.hidden = true;
    if (dom.stepCounter) dom.stepCounter.style.display = 'none';

    // Show results
    if (dom.results) {
      dom.results.setAttribute('aria-hidden', 'false');
      dom.results.classList.add('is-visible');
    }

    // Progress: all done
    dom.progressSegments.forEach(function (seg) {
      seg.classList.add('is-done');
      seg.classList.remove('is-active');
    });

    // Render tags
    renderAnswerTags();

    // Fetch products
    fetchResults();

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function renderAnswerTags() {
    if (!dom.resultsTags) return;

    var html = '';
    // Iterate in step order
    for (var i = 1; i <= state.totalSteps; i++) {
      var attr = getAttributeForStep(i);
      var label = attr ? state.labels[attr] : null;
      if (label) {
        html += '<button class="guide-answer-tag" data-step="' + i + '" type="button" '
              + 'aria-label="Modifier : ' + escapeHtml(label) + '">'
              + '<span class="guide-tag-label">' + escapeHtml(label) + '</span>'
              + '<svg class="guide-tag-edit" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
              + '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>'
              + '<path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>'
              + '</svg></button>';
      }
    }
    dom.resultsTags.innerHTML = html;

    // Bind tag clicks
    dom.resultsTags.querySelectorAll('.guide-answer-tag').forEach(function (tag) {
      tag.addEventListener('click', function () {
        var stepNum = parseInt(this.getAttribute('data-step'), 10);
        goBackToStep(stepNum);
      });
    });
  }

  function goBackToStep(stepNum) {
    state.isShowingResults = false;
    state.currentStep = stepNum;

    // Clear answers from this step onwards
    for (var i = stepNum; i <= state.totalSteps; i++) {
      var attr = getAttributeForStep(i);
      if (attr) {
        delete state.answers[attr];
        delete state.labels[attr];
        // Deselect cards for this step
        var stepEl = document.querySelector('.guide-step[data-step="' + i + '"]');
        if (stepEl) {
          stepEl.querySelectorAll('.guide-choice-card.is-selected').forEach(function (c) {
            c.classList.remove('is-selected');
          });
        }
      }
    }

    // Hide results, show quiz
    if (dom.results) {
      dom.results.setAttribute('aria-hidden', 'true');
      dom.results.classList.remove('is-visible');
    }
    if (dom.quiz) {
      dom.quiz.setAttribute('aria-hidden', 'false');
    }
    if (dom.stepCounter) {
      dom.stepCounter.style.display = '';
    }

    saveSession();
    renderStep(stepNum, 'none');
  }

  function fetchResults() {
    if (dom.resultsLoading) dom.resultsLoading.setAttribute('aria-hidden', 'false');
    if (dom.resultsGrid) dom.resultsGrid.innerHTML = '';
    if (dom.restartWrap) dom.restartWrap.style.display = 'none';

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
      if (dom.resultsLoading) dom.resultsLoading.setAttribute('aria-hidden', 'true');
      if (dom.restartWrap) dom.restartWrap.style.display = '';

      if (data.success && data.data) {
        renderResultProducts(data.data);
      } else {
        renderResultsError();
      }
    })
    .catch(function () {
      if (dom.resultsLoading) dom.resultsLoading.setAttribute('aria-hidden', 'true');
      if (dom.restartWrap) dom.restartWrap.style.display = '';
      renderResultsError();
    });
  }

  function renderResultProducts(data) {
    var subtitleMessages = {
      1: 'Sélection parfaite pour votre espace.',
      2: 'Voici nos créations qui correspondent le mieux à votre espace.',
      3: 'Explorez nos luminaires qui correspondent à votre style.',
      4: 'Notre collection complète — un luminaire trouvera sa place chez vous.',
    };

    if (dom.resultsSubtitle) {
      dom.resultsSubtitle.textContent = subtitleMessages[data.tier] || subtitleMessages[1];
    }

    if (dom.resultsGrid) {
      dom.resultsGrid.innerHTML = data.html;

      // Stagger animation for product cards
      var cards = dom.resultsGrid.querySelectorAll('li.product');
      cards.forEach(function (card, i) {
        card.style.animationDelay = (i * 0.08) + 's';
        card.classList.add('guide-result-reveal');
      });
    }
  }

  function renderResultsError() {
    if (dom.resultsGrid) {
      dom.resultsGrid.innerHTML = '<li class="guide-results-error">'
        + '<p>Impossible de charger les résultats. '
        + '<a href="' + escapeHtml(sapiGuide.shopUrl) + '">Voir toute la collection</a>.</p>'
        + '</li>';
    }
  }

  // ================================================================
  // UI HELPERS
  // ================================================================
  function updateProgress(stepNum) {
    dom.progressSegments.forEach(function (seg, i) {
      var segStep = i + 1;
      seg.classList.remove('is-done', 'is-active');
      if (segStep < stepNum) seg.classList.add('is-done');
      else if (segStep === stepNum) seg.classList.add('is-active');
    });
  }

  function updateBackButton(stepNum) {
    if (dom.backBtn) {
      dom.backBtn.hidden = stepNum <= 1;
    }
  }

  function updateStepCounter(stepNum) {
    if (dom.currentStepEl) {
      dom.currentStepEl.textContent = stepNum;
    }
  }

  function bindBackButton() {
    if (dom.backBtn) {
      dom.backBtn.addEventListener('click', function () {
        if (state.isShowingResults) {
          goBackToStep(state.totalSteps);
        } else {
          goBack();
        }
      });
    }
  }

  function bindRestartButton() {
    if (dom.restartBtn) {
      dom.restartBtn.addEventListener('click', function () {
        state.currentStep = 1;
        state.answers = {};
        state.labels = {};
        state.isShowingResults = false;
        clearSession();

        // Deselect all cards
        document.querySelectorAll('.guide-choice-card.is-selected').forEach(function (c) {
          c.classList.remove('is-selected');
        });

        // Hide results, show quiz
        if (dom.results) {
          dom.results.setAttribute('aria-hidden', 'true');
          dom.results.classList.remove('is-visible');
        }
        if (dom.quiz) {
          dom.quiz.setAttribute('aria-hidden', 'false');
        }
        if (dom.stepCounter) {
          dom.stepCounter.style.display = '';
        }

        renderStep(1, 'none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
  }

  function bindKeyboard() {
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && state.isQuizStarted) {
        if (state.isShowingResults) {
          goBackToStep(state.totalSteps);
        } else if (state.currentStep > 1) {
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

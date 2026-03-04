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
    resultProducts: [],
    resultIndex: 0,
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
    dom.productsRow     = document.getElementById('guide-result-products-row');
    dom.resultImage     = document.getElementById('guide-result-image');
    dom.resultName      = document.getElementById('guide-result-name');
    dom.resultPrice     = document.getElementById('guide-result-price');
    dom.resultCta       = document.getElementById('guide-result-cta');
    dom.resultVariation = document.getElementById('guide-result-variation');
    dom.complementWrap  = document.getElementById('guide-result-product-complement');
    dom.complementImage = document.getElementById('guide-complement-image');
    dom.complementName  = document.getElementById('guide-complement-name');
    dom.complementPrice = document.getElementById('guide-complement-price');
    dom.complementCta   = document.getElementById('guide-complement-cta');
    dom.aiText          = document.getElementById('guide-ai-text');
    dom.aiTextContent   = document.getElementById('guide-ai-text-content');
    dom.followupBtns    = document.getElementById('guide-followup-buttons');
    dom.resultError     = document.getElementById('guide-result-error');
    dom.nextBtn         = document.getElementById('guide-next-btn');
    dom.nextProposal    = document.getElementById('guide-next-proposal');
    dom.proposalCounter = document.getElementById('guide-proposal-counter');
    dom.restartBtn      = document.getElementById('guide-restart');
    dom.startBtn        = document.getElementById('guide-start-btn');
    dom.restartWrap     = document.getElementById('guide-restart-wrap');

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
    bindNextButton();
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

      var step = getStepById(stepId);
      var questionShort = step ? getShortQuestionLabel(step.id) : stepId;

      html += '<div class="guide-tag-wrap" data-step-id="' + escapeHtml(stepId) + '">'
            + '<button class="guide-answer-tag" data-step-id="' + escapeHtml(stepId) + '" type="button" '
            + 'aria-label="Modifier : ' + escapeHtml(label) + '">'
            + '<span class="guide-tag-label">' + escapeHtml(label) + '</span>'
            + '<svg class="guide-tag-edit" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
            + '<path d="m6 9 6 6 6-6"/>'
            + '</svg></button>'
            + '</div>';
    });

    dom.resultsTags.innerHTML = html;

    // Bind tag clicks
    dom.resultsTags.querySelectorAll('.guide-answer-tag').forEach(function (tag) {
      tag.addEventListener('click', function (e) {
        e.stopPropagation();
        var stepId = this.getAttribute('data-step-id');
        toggleTagDropdown(this, stepId);
      });
    });
  }

  /**
   * Short label for answer tags header.
   */
  function getShortQuestionLabel(stepId) {
    var labels = {
      'sortie':  'Sortie',
      'hauteur': 'Hauteur',
      'table':   'Table',
      'taille':  'Taille pièce',
      'piece':   'Pièce',
      'style':   'Style',
    };
    return labels[stepId] || stepId;
  }

  function toggleTagDropdown(tagBtn, stepId) {
    var wrap = tagBtn.closest('.guide-tag-wrap');
    var existingDropdown = wrap.querySelector('.guide-tag-dropdown');

    closeAllDropdowns();

    if (existingDropdown) return;

    // Get choices for this step from sapiGuide.steps
    var step = getStepById(stepId);
    if (!step || !step.choices) return;

    var currentSlug = state.answers[stepId];
    var dropdown = document.createElement('div');
    dropdown.className = 'guide-tag-dropdown';

    step.choices.forEach(function (choice) {
      var option = document.createElement('button');
      option.className = 'guide-tag-option';
      option.type = 'button';
      if (choice.slug === currentSlug) option.classList.add('is-current');
      option.textContent = choice.label;
      option.addEventListener('click', function (e) {
        e.stopPropagation();
        state.answers[stepId] = choice.slug;
        state.labels[stepId] = choice.label;

        // Clean answers for steps that may no longer be visible
        cleanInvisibleAnswers();
        saveSession();

        var labelEl = tagBtn.querySelector('.guide-tag-label');
        if (labelEl) labelEl.textContent = choice.label;

        closeAllDropdowns();

        // Re-render tags (visibility may have changed) and fetch
        renderAnswerTags();
        fetchResults();
      });
      dropdown.appendChild(option);
    });

    wrap.appendChild(dropdown);
    tagBtn.classList.add('is-open');

    setTimeout(function () {
      document.addEventListener('click', closeAllDropdowns, { once: true });
    }, 0);
  }

  function closeAllDropdowns() {
    document.querySelectorAll('.guide-tag-dropdown').forEach(function (d) { d.remove(); });
    document.querySelectorAll('.guide-answer-tag.is-open').forEach(function (t) { t.classList.remove('is-open'); });
  }

  // ================================================================
  // FETCH RESULTS + AI
  // ================================================================
  function fetchResults() {
    // Show loading
    if (dom.resultsLoading) dom.resultsLoading.setAttribute('aria-hidden', 'false');
    if (dom.productsRow) dom.productsRow.style.display = 'none';
    if (dom.aiText) dom.aiText.style.display = 'none';
    if (dom.followupBtns) dom.followupBtns.style.display = 'none';
    if (dom.nextProposal) dom.nextProposal.style.display = 'none';
    if (dom.resultError) dom.resultError.style.display = 'none';
    if (dom.restartWrap) dom.restartWrap.style.display = 'none';
    if (dom.complementWrap) dom.complementWrap.style.display = 'none';

    // Reset complement class
    if (dom.productsRow) dom.productsRow.classList.remove('has-complement');

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

        // Store all products for carousel
        state.resultProducts = d.products || [];
        state.resultIndex = 0;

        // Display AI recommendation text
        if (d.ai_text) {
          state.aiText = d.ai_text;
          renderAiText(d.ai_text);
        }

        // Display main product
        if (d.main_product) {
          renderProduct(d.main_product, 'main');
        } else if (state.resultProducts.length > 0) {
          renderProduct(state.resultProducts[0], 'main');
        }

        // Display complement if grande pièce
        if (d.complement) {
          renderProduct(d.complement, 'complement');
          if (dom.productsRow) dom.productsRow.classList.add('has-complement');
          if (dom.complementWrap) dom.complementWrap.style.display = '';
        }

        // Display follow-up buttons
        if (d.followup_buttons && d.followup_buttons.length > 0) {
          renderFollowupButtons(d.followup_buttons);
        }

        // Show products row
        if (dom.productsRow && state.resultProducts.length > 0) {
          dom.productsRow.style.display = '';
        }

        // Show next proposal if multiple products
        updateProposalCounter();

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
  // RENDER PRODUCTS
  // ================================================================
  function renderProduct(product, type) {
    if (type === 'complement') {
      // Complement product
      if (dom.complementImage) {
        dom.complementImage.src = product.image;
        dom.complementImage.srcset = '';
        dom.complementImage.alt = product.image_alt || product.title;
      }
      if (dom.complementName) {
        var newName = document.createElement('h3');
        newName.className = 'guide-result-name';
        newName.id = 'guide-complement-name';
        newName.textContent = product.title;
        dom.complementName.parentNode.replaceChild(newName, dom.complementName);
        dom.complementName = newName;
      }
      if (dom.complementPrice) {
        dom.complementPrice.innerHTML = product.price;
      }
      if (dom.complementCta) {
        dom.complementCta.href = product.permalink;
      }
    } else {
      // Main product
      if (dom.resultImage) {
        dom.resultImage.src = product.image;
        dom.resultImage.srcset = '';
        dom.resultImage.alt = product.image_alt || product.title;
      }
      if (dom.resultName) {
        var newMainName = document.createElement('h3');
        newMainName.className = 'guide-result-name';
        newMainName.id = 'guide-result-name';
        newMainName.textContent = product.title;
        dom.resultName.parentNode.replaceChild(newMainName, dom.resultName);
        dom.resultName = newMainName;
      }
      if (dom.resultPrice) {
        dom.resultPrice.innerHTML = product.price;
      }
      if (dom.resultVariation) {
        if (product.variation_label) {
          dom.resultVariation.textContent = 'Essence recommandée : ' + product.variation_label;
          dom.resultVariation.style.display = '';
        } else {
          dom.resultVariation.style.display = 'none';
        }
      }
      if (dom.resultCta) {
        dom.resultCta.href = product.permalink;
      }
    }
  }

  function renderCurrentProduct() {
    var product = state.resultProducts[state.resultIndex];
    if (!product) return;
    renderProduct(product, 'main');
    updateProposalCounter();
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

  // ================================================================
  // NEXT PROPOSAL (CAROUSEL)
  // ================================================================
  function updateProposalCounter() {
    var total = state.resultProducts.length;
    if (dom.proposalCounter) {
      dom.proposalCounter.textContent = (state.resultIndex + 1) + ' / ' + total + ' proposition' + (total > 1 ? 's' : '');
    }
    // Show/hide next button and counter
    if (dom.nextProposal) {
      dom.nextProposal.style.display = total > 1 ? '' : 'none';
    }
  }

  function bindNextButton() {
    if (dom.nextBtn) {
      dom.nextBtn.addEventListener('click', function () {
        if (state.resultProducts.length <= 1) return;
        state.resultIndex = (state.resultIndex + 1) % state.resultProducts.length;

        // Animate product change
        var mainProduct = document.getElementById('guide-result-product-main');
        if (mainProduct) {
          mainProduct.classList.remove('guide-product-fade');
          mainProduct.getBoundingClientRect();
          mainProduct.classList.add('guide-product-fade');
        }

        renderCurrentProduct();
      });
    }
  }

  function renderResultsError() {
    if (dom.productsRow) dom.productsRow.style.display = 'none';
    if (dom.aiText) dom.aiText.style.display = 'none';
    if (dom.resultError) dom.resultError.style.display = '';
  }

  // ================================================================
  // UI HELPERS
  // ================================================================
  function updateBackButton(stepId) {
    if (!dom.backBtn) return;
    var visible = getVisibleSteps();
    dom.backBtn.hidden = visible.indexOf(stepId) <= 0;
  }

  function updateStepCounter(stepId) {
    var visible = getVisibleSteps();
    var idx = visible.indexOf(stepId);
    var counterEl = document.querySelector('.guide-step.is-active [data-step-counter]');
    if (!counterEl) {
      // The step may not be active yet, try with the target step
      var stepEl = document.querySelector('.guide-step[data-step="' + stepId + '"]');
      if (stepEl) counterEl = stepEl.querySelector('[data-step-counter]');
    }
    if (counterEl && idx !== -1) {
      counterEl.textContent = pad(idx + 1) + ' / ' + pad(visible.length);
    }
  }

  function pad(n) {
    return n < 10 ? '0' + n : '' + n;
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
        state.currentStepId = null;
        state.answers = {};
        state.labels = {};
        state.isShowingResults = false;
        state.resultProducts = [];
        state.resultIndex = 0;
        state.aiText = '';
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

        // Reset to first step
        var visible = getVisibleSteps();
        state.currentStepId = visible[0];
        renderStep(state.currentStepId, 'none');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
    }
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

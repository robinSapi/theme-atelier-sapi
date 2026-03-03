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
    resultProducts: [],  // Array of product objects from AJAX
    resultIndex: 0,      // Current product index
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
    dom.progressBar      = null;
    dom.progressSegments = [];
    dom.backBtn          = document.getElementById('guide-back');
    dom.stepCounter      = null;
    dom.currentStepEl    = null;
    dom.results          = document.getElementById('guide-results');
    dom.resultsTags      = document.getElementById('guide-results-tags');
    dom.resultsSubtitle  = document.getElementById('guide-results-subtitle');
    dom.resultsLoading   = document.getElementById('guide-results-loading');
    dom.resultLayout     = document.getElementById('guide-result-layout');
    dom.resultImage      = document.getElementById('guide-result-image');
    dom.resultName       = document.getElementById('guide-result-name');
    dom.resultPrice      = document.getElementById('guide-result-price');
    dom.resultCta        = document.getElementById('guide-result-cta');
    dom.resultError      = document.getElementById('guide-result-error');
    dom.explanationsList = document.getElementById('guide-explanations-list');
    dom.nextBtn          = document.getElementById('guide-next-btn');
    dom.proposalCounter  = document.getElementById('guide-proposal-counter');
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
    bindNextButton();
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

      // Focus question heading for accessibility
      var heading = nextStep.querySelector('.guide-step-question');
      if (heading) {
        heading.setAttribute('tabindex', '-1');
        heading.focus({ preventScroll: true });
      }
    }

    if (currentActive && currentActive !== nextStep && direction !== 'none') {
      currentActive.classList.add('is-exiting');
      currentActive.classList.remove('is-active');

      // Wait for exit to finish, then enter
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

    updateProgress(stepNum);
    updateBackButton(stepNum);
    updateStepCounter(stepNum);
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
  // EXPLANATIONS DATA
  // ================================================================
  var stepQuestions = {
    'pa_piece':           'Votre pièce',
    'pa_eclairage':       'Usage principal',
    'pa_style':           'Style recherché',
    'pa_taille-piece':    'Taille de la pièce',
    'pa_hauteur':         'Hauteur sous plafond',
    'pa_type-luminaire':  'Placement',
  };

  var explanations = {
    'pa_piece': {
      'salon':     "Pensé pour être le point focal de votre pièce de vie, ce luminaire apporte une présence chaleureuse qui accompagne vos moments de détente.",
      'cuisine':   "Sa lumière généreuse illumine votre espace culinaire tout en ajoutant une touche artisanale à votre cuisine.",
      'chambre':   "Sa lumière douce crée l'atmosphère apaisante dont votre chambre a besoin pour des moments de calme et de repos.",
      'bureau':    "Un éclairage pensé pour la concentration, qui habille votre espace de travail avec élégance et caractère.",
      'couloir':   "Parfait pour accueillir vos invités, ce luminaire transforme votre entrée en une première impression mémorable.",
      'couloir-2': "Sa forme épurée s'intègre harmonieusement dans les espaces de passage tout en leur apportant de la personnalité.",
    },
    'pa_eclairage': {
      'fonctionnel':  "Son éclairage fonctionnel offre une lumière claire et bien répartie, idéale pour les activités du quotidien.",
      'ambiance':     "Conçu pour créer une ambiance enveloppante, il diffuse une lumière tamisée qui invite à la détente.",
      'les-deux':     "Sa polyvalence vous permet de basculer entre éclairage pratique et ambiance chaleureuse selon vos envies.",
    },
    'pa_style': {
      'epure':       "Ses lignes épurées et minimalistes s'intègrent avec discrétion dans votre intérieur, laissant le bois parler de lui-même.",
      'chaleureux':  "Ses courbes organiques et la chaleur naturelle du bois créent une atmosphère accueillante et réconfortante.",
      'imposant':    "Sa présence affirmée en fait une pièce maîtresse qui capte le regard et donne du caractère à votre espace.",
      'boheme':      "Son esprit bohème apporte une touche naturelle et décontractée, évoquant un intérieur libre et authentique.",
      'scandinave':  "Son design nordique épuré mise sur la fonctionnalité et la beauté simple des matériaux naturels.",
      'poetique':    "Sa silhouette délicate et romantique apporte une touche de poésie et de douceur à votre décoration.",
    },
    'pa_taille-piece': {
      'petite':  "Ses proportions sont adaptées aux espaces intimistes, sans les encombrer tout en restant visuellement présent.",
      'moyenne': "Ses dimensions équilibrées s'harmonisent parfaitement avec votre pièce de taille moyenne.",
      'grande':  "Sa envergure lui permet de remplir l'espace sans se perdre dans les grands volumes.",
    },
    'pa_hauteur': {
      'standard':     "Sa conception tient compte d'une hauteur sous plafond standard pour un rendu optimal.",
      'confortable':  "Il tire parti de votre belle hauteur sous plafond pour un effet suspendu élégant.",
      'haute':        "Pensé pour les volumes généreux, il occupe magnifiquement l'espace vertical de votre pièce.",
    },
    'pa_type-luminaire': {
      'au-dessus-meuble':  "Positionné au-dessus de votre mobilier, il crée un jeu de lumière qui met en valeur votre agencement.",
      'zone-passage':      "Son design s'adapte aux zones de circulation, offrant un éclairage pratique avec une touche décorative.",
      'dans-un-coin':      "Parfait pour habiller un coin de la pièce, il transforme un espace souvent oublié en point d'intérêt.",
      'sur-un-mur':        "Fixé au mur, il libère l'espace au sol tout en projetant une lumière sculpturale sur la surface.",
    },
  };

  // Choices data for dropdown editing
  var stepChoices = {
    'pa_piece': [
      { slug: 'salon',     label: 'Salon' },
      { slug: 'cuisine',   label: 'Cuisine' },
      { slug: 'chambre',   label: 'Chambre' },
      { slug: 'bureau',    label: 'Bureau' },
      { slug: 'couloir',   label: 'Hall' },
      { slug: 'couloir-2', label: 'Couloir' },
    ],
    'pa_eclairage': [
      { slug: 'fonctionnel', label: 'Éclairage fonctionnel' },
      { slug: 'ambiance',    label: 'Ambiance & décoration' },
      { slug: 'les-deux',    label: 'Les deux à la fois' },
    ],
    'pa_style': [
      { slug: 'epure',      label: 'Épuré / Minimaliste' },
      { slug: 'chaleureux', label: 'Chaleureux / Organique' },
      { slug: 'imposant',   label: 'Imposant / Statement' },
      { slug: 'boheme',     label: 'Bohème / Nature' },
      { slug: 'scandinave', label: 'Scandinave / Nordique' },
      { slug: 'poetique',   label: 'Poétique / Romantique' },
    ],
    'pa_taille-piece': [
      { slug: 'petite',  label: 'Petite (< 10 m²)' },
      { slug: 'moyenne', label: 'Moyenne (10–20 m²)' },
      { slug: 'grande',  label: 'Grande (> 20 m²)' },
    ],
    'pa_hauteur': [
      { slug: 'standard',    label: 'Standard (< 2,50 m)' },
      { slug: 'confortable', label: 'Confortable (2,50–3 m)' },
      { slug: 'haute',       label: 'Haute (> 3 m)' },
    ],
    'pa_type-luminaire': [
      { slug: 'au-dessus-meuble', label: 'Au-dessus d\'un meuble' },
      { slug: 'zone-passage',     label: 'Zone de passage' },
      { slug: 'dans-un-coin',     label: 'Dans un coin' },
      { slug: 'sur-un-mur',       label: 'Sur un mur' },
    ],
  };

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

    // Render tags and explanations
    renderAnswerTags();
    renderExplanations();

    // Fetch products
    fetchResults();

    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function renderAnswerTags() {
    if (!dom.resultsTags) return;

    var html = '';
    for (var i = 1; i <= state.totalSteps; i++) {
      var attr = getAttributeForStep(i);
      var label = attr ? state.labels[attr] : null;
      if (label) {
        html += '<div class="guide-tag-wrap" data-attribute="' + escapeHtml(attr) + '">'
              + '<button class="guide-answer-tag" data-step="' + i + '" data-attribute="' + escapeHtml(attr) + '" type="button" '
              + 'aria-label="Modifier : ' + escapeHtml(label) + '">'
              + '<span class="guide-tag-label">' + escapeHtml(label) + '</span>'
              + '<svg class="guide-tag-edit" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">'
              + '<path d="m6 9 6 6 6-6"/>'
              + '</svg></button>'
              + '</div>';
      }
    }
    dom.resultsTags.innerHTML = html;

    // Bind tag clicks to open dropdown
    dom.resultsTags.querySelectorAll('.guide-answer-tag').forEach(function (tag) {
      tag.addEventListener('click', function (e) {
        e.stopPropagation();
        var attr = this.getAttribute('data-attribute');
        toggleTagDropdown(this, attr);
      });
    });
  }

  function toggleTagDropdown(tagBtn, attribute) {
    var wrap = tagBtn.closest('.guide-tag-wrap');
    var existingDropdown = wrap.querySelector('.guide-tag-dropdown');

    // Close all other dropdowns first
    closeAllDropdowns();

    if (existingDropdown) return; // Was open, now closed by closeAllDropdowns

    // Build dropdown
    var choices = stepChoices[attribute];
    if (!choices) return;

    var currentSlug = state.answers[attribute];
    var dropdown = document.createElement('div');
    dropdown.className = 'guide-tag-dropdown';

    choices.forEach(function (choice) {
      var option = document.createElement('button');
      option.className = 'guide-tag-option';
      option.type = 'button';
      if (choice.slug === currentSlug) option.classList.add('is-current');
      option.textContent = choice.label;
      option.addEventListener('click', function (e) {
        e.stopPropagation();
        // Update answer
        state.answers[attribute] = choice.slug;
        state.labels[attribute] = choice.label;
        saveSession();

        // Update tag label
        var labelEl = tagBtn.querySelector('.guide-tag-label');
        if (labelEl) labelEl.textContent = choice.label;

        // Close dropdown
        closeAllDropdowns();

        // Re-render explanations and fetch new product
        renderExplanations();
        fetchResults();
      });
      dropdown.appendChild(option);
    });

    wrap.appendChild(dropdown);
    tagBtn.classList.add('is-open');

    // Close on outside click
    setTimeout(function () {
      document.addEventListener('click', closeAllDropdowns, { once: true });
    }, 0);
  }

  function closeAllDropdowns() {
    document.querySelectorAll('.guide-tag-dropdown').forEach(function (d) { d.remove(); });
    document.querySelectorAll('.guide-answer-tag.is-open').forEach(function (t) { t.classList.remove('is-open'); });
  }

  function renderExplanations() {
    if (!dom.explanationsList) return;

    var html = '';
    for (var i = 1; i <= state.totalSteps; i++) {
      var attr = getAttributeForStep(i);
      if (!attr || !state.answers[attr]) continue;

      var slug = state.answers[attr];
      var questionLabel = stepQuestions[attr] || '';
      var answerLabel = state.labels[attr] || '';
      var text = (explanations[attr] && explanations[attr][slug]) || '';

      html += '<div class="guide-explanation-item">'
            + '<div class="guide-explanation-header">'
            + '<svg class="guide-explanation-check" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>'
            + '<span class="guide-explanation-question">' + escapeHtml(questionLabel) + ' :</span> '
            + '<strong class="guide-explanation-answer">' + escapeHtml(answerLabel) + '</strong>'
            + '</div>'
            + '<p class="guide-explanation-text">' + escapeHtml(text) + '</p>'
            + '</div>';
    }

    dom.explanationsList.innerHTML = html;
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
    if (dom.resultLayout) dom.resultLayout.style.display = 'none';
    if (dom.resultError) dom.resultError.style.display = 'none';
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

      if (data.success && data.data && data.data.products && data.data.products.length) {
        state.resultProducts = data.data.products;
        state.resultIndex = 0;
        renderCurrentProduct();
        updateSubtitle(data.data.tier);
        if (dom.resultLayout) dom.resultLayout.style.display = '';
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

  function renderCurrentProduct() {
    var product = state.resultProducts[state.resultIndex];
    if (!product) return;

    if (dom.resultImage) {
      dom.resultImage.src = product.image;
      dom.resultImage.srcset = '';
      dom.resultImage.alt = product.image_alt || product.title;
    }
    if (dom.resultName) {
      dom.resultName.textContent = product.title;
      // Trigger product name formatter (MutationObserver will pick it up)
    }
    if (dom.resultPrice) {
      dom.resultPrice.innerHTML = product.price;
    }
    if (dom.resultCta) {
      dom.resultCta.href = product.permalink;
    }

    // Update proposal counter
    updateProposalCounter();
  }

  function updateProposalCounter() {
    var total = state.resultProducts.length;
    if (dom.proposalCounter) {
      dom.proposalCounter.textContent = (state.resultIndex + 1) + ' / ' + total + ' proposition' + (total > 1 ? 's' : '');
    }
    // Hide next button if only 1 product
    if (dom.nextBtn) {
      dom.nextBtn.style.display = total <= 1 ? 'none' : '';
    }
  }

  function updateSubtitle(tier) {
    var subtitleMessages = {
      1: 'Sélection parfaite pour votre espace.',
      2: 'Voici nos créations qui correspondent le mieux à votre espace.',
      3: 'Explorez nos luminaires qui correspondent à votre style.',
      4: 'Notre collection complète — un luminaire trouvera sa place chez vous.',
    };
    if (dom.resultsSubtitle) {
      dom.resultsSubtitle.textContent = subtitleMessages[tier] || subtitleMessages[1];
    }
  }

  function bindNextButton() {
    if (dom.nextBtn) {
      dom.nextBtn.addEventListener('click', function () {
        if (state.resultProducts.length <= 1) return;
        state.resultIndex = (state.resultIndex + 1) % state.resultProducts.length;

        // Animate product change
        if (dom.resultLayout) {
          var productCol = dom.resultLayout.querySelector('.guide-result-product');
          if (productCol) {
            productCol.classList.remove('guide-product-fade');
            productCol.getBoundingClientRect(); // force reflow
            productCol.classList.add('guide-product-fade');
          }
        }

        renderCurrentProduct();
      });
    }
  }

  function renderResultsError() {
    if (dom.resultLayout) dom.resultLayout.style.display = 'none';
    if (dom.resultError) dom.resultError.style.display = '';
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

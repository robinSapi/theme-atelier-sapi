/**
 * Mon Projet — Bandeau questionnaire permanent
 * Gère : expand/collapse, visibilité conditionnelle, sélection,
 * reset en cascade, chips résumé, sauvegarde localStorage.
 * AJAX déclenché à la fermeture du bandeau si pièce + taille répondues.
 * Un seul appel AJAX (sapi_guide_results) retourne produits + textes IA.
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
  var answersHashAtOpen = null;

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

      // Merge with existing data to preserve AJAX results
      var existing = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

      existing.answers = state.answers;
      existing.labels = state.labels;
      existing.essence = essenceMap[style] || null;
      existing.tailleIndex = taille in tailleMap ? tailleMap[taille] : null;
      existing.pieceLabel = state.labels.piece || null;
      existing.styleLabel = state.labels.style || null;
      existing.tailleLabel = state.labels.taille || null;

      if (!existing.recommendedIds) {
        existing.recommendedIds = [];
      }

      localStorage.setItem(STORAGE_KEY, JSON.stringify(existing));
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
    answersHashAtOpen = simpleHash(JSON.stringify(state.answers));
    expanded.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
  }

  function closeBanner() {
    if (!state.isOpen) return;
    state.isOpen = false;
    expanded.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');

    // Si les réponses ont changé et que pièce + taille sont répondues → AJAX
    if (hasMinimumAnswers()) {
      var currentHash = simpleHash(JSON.stringify(state.answers));
      if (currentHash !== answersHashAtOpen) {
        invalidateResults();
        fetchResults(function() {
          applyPageUpdates();
        });
      }
    }
  }

  function toggleBanner() {
    if (state.isOpen) closeBanner();
    else openBanner();
  }

  // ─── Invalidation des résultats quand les réponses changent ───
  function invalidateResults() {
    try {
      var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
      delete prefs.recommendedIds;
      delete prefs.conseilsText;
      delete prefs.selectionText;
      localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
    } catch (e) { /* */ }

    // Sur la page Conseils : masquer la partie perso et montrer le bouton
    var conseilsIntro = document.getElementById('conseils-perso-intro');
    if (conseilsIntro) {
      conseilsIntro.style.display = 'none';
    }
    var conseilsProducts = document.getElementById('conseils-products-section');
    if (conseilsProducts) {
      conseilsProducts.style.display = 'none';
      conseilsProducts.dataset.loaded = '';
    }
    var conseilsRefresh = document.getElementById('conseils-refresh-btn');
    if (conseilsRefresh) {
      conseilsRefresh.style.display = '';
    }
  }

  // ─── Event handlers ───
  function onChoiceClick(e) {
    var btn = e.target.closest('.mon-projet-choice');
    if (!btn) return;

    var stepId = btn.getAttribute('data-step');
    var slug   = btn.getAttribute('data-slug');
    var label  = btn.getAttribute('data-label');

    if (state.answers[stepId] === slug) {
      delete state.answers[stepId];
      delete state.labels[stepId];
    } else {
      state.answers[stepId] = slug;
      state.labels[stepId]  = label;
    }

    cleanInvisibleAnswers();
    updateAll();
    saveState();
  }

  function hasMinimumAnswers() {
    return !!state.answers.piece && !!state.answers.taille;
  }

  function onReset() {
    state.answers = {};
    state.labels  = {};
    try { localStorage.removeItem(STORAGE_KEY); } catch (e) { /* */ }
    updateAll();
    invalidateResults();
  }

  function updateAll() {
    updateQuestionsVisibility();
    updateChoicesUI();
    updateChips();
    updateDynamicQuestions();
  }

  // ─── AJAX : Un seul appel (produits + textes IA) ───
  function fetchResults(onDone) {
    if (typeof sapiMonProjet === 'undefined' || !sapiMonProjet.ajaxUrl) {
      if (onDone) onDone();
      return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', sapiMonProjet.ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
            var products = resp.data.products || [];
            prefs.recommendedIds = products.map(function(p) { return p.id; });

            // Textes IA retournés par le même endpoint
            if (resp.data.conseils_text) {
              prefs.conseilsText = resp.data.conseils_text;
            }
            if (resp.data.selection_text) {
              prefs.selectionText = resp.data.selection_text;
            }

            localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
          }
        } catch (e) { /* */ }
      }
      if (onDone) onDone();
    };

    var params = 'action=sapi_guide_results'
      + '&nonce=' + encodeURIComponent(sapiMonProjet.nonce)
      + '&answers=' + encodeURIComponent(JSON.stringify(state.answers))
      + '&guide_website=';

    xhr.send(params);
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

  // ─── Page updates after AJAX ───
  function applyPageUpdates() {
    applyAiTexts();
    // Déclencher un événement custom pour que shop.js rafraîchisse le filtre
    document.dispatchEvent(new CustomEvent('monProjetUpdated'));
  }

  // ─── Page-specific AI text injection ───
  function applyAiTexts() {
    try {
      var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');

      // Page Conseils — texte IA + chips projet
      var conseilsIntro = document.getElementById('conseils-perso-intro');
      var conseilsText = document.getElementById('conseils-perso-text');
      if (conseilsIntro && conseilsText && prefs.conseilsText) {
        conseilsText.textContent = prefs.conseilsText;
        conseilsIntro.style.display = '';

        // Injecter les chips du projet dans le bloc Robin
        var chipsContainer = document.getElementById('robin-conseil-chips');
        if (chipsContainer && prefs.labels) {
          var html = '';
          var visible = getVisibleSteps();
          for (var i = 0; i < visible.length; i++) {
            var lbl = prefs.labels[visible[i]];
            if (lbl) {
              html += '<span class="robin-conseil__chip">' + escapeHtml(lbl) + '</span>';
            }
          }
          chipsContainer.innerHTML = html;
        }
      }

      // Page Conseils — produits recommandés
      var conseilsProducts = document.getElementById('conseils-products-section');
      if (conseilsProducts && prefs.recommendedIds && prefs.recommendedIds.length > 0) {
        conseilsProducts.style.display = '';
        renderConseilsProducts(conseilsProducts, prefs.recommendedIds);
      }

      // Page Conseils — cacher le bouton refresh (données fraîches)
      var conseilsRefresh = document.getElementById('conseils-refresh-btn');
      if (conseilsRefresh && prefs.conseilsText) {
        conseilsRefresh.style.display = 'none';
      }

      // Page Conseils — cacher le CTA "Commencez votre projet"
      var conseilsCta = document.querySelector('.advice-guide-cta');
      if (conseilsCta && prefs.recommendedIds && prefs.recommendedIds.length > 0) {
        conseilsCta.style.display = 'none';
      }

      // Page Sur-mesure — pre-fill form
      prefillSurMesureForm(prefs);
    } catch (e) { /* */ }
  }

  // ─── Page Conseils : render product cards ───
  function renderConseilsProducts(container, ids) {
    if (container.dataset.loaded === 'true') return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', sapiMonProjet.ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            var grid = container.querySelector('.conseils-products-grid');
            if (grid) {
              grid.innerHTML = resp.data;
              container.dataset.loaded = 'true';
            }
          }
        } catch (e) { /* */ }
      }
    };

    var params = 'action=sapi_conseils_products'
      + '&nonce=' + encodeURIComponent(sapiMonProjet.nonce)
      + '&ids=' + encodeURIComponent(JSON.stringify(ids));

    xhr.send(params);
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

  // ─── Bouton "Les conseils de Robin" : valider si pas encore fait ───
  var conseilsBtn = bar.querySelector('.mon-projet-btn-conseils');
  if (conseilsBtn) {
    conseilsBtn.addEventListener('click', function(e) {
      try {
        var prefs = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
        if (prefs.recommendedIds && prefs.recommendedIds.length > 0) return;
      } catch (err) { /* */ }

      if (hasMinimumAnswers()) {
        e.preventDefault();
        conseilsBtn.textContent = 'Chargement\u2026';
        fetchResults(function() {
          window.location.href = conseilsBtn.href;
        });
      }
    });
  }

  // ─── Page Conseils : bouton "Obtenir les conseils de Robin" ───
  var refreshBtn = document.getElementById('conseils-refresh-btn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function() {
      if (!hasMinimumAnswers()) {
        openBanner();
        bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
      }
      refreshBtn.textContent = 'Chargement\u2026';
      refreshBtn.disabled = true;
      fetchResults(function() {
        window.location.reload();
      });
    });
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

  // Apply on page load
  applyPageUpdates();

})();

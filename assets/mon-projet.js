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
  var answersSnapshotAtOpen = null;
  var pendingXhr  = null;

  // ─── Helpers localStorage ───
  function safeLoad() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
    catch (e) { return {}; }
  }

  function safeSave(data) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); }
    catch (e) { /* quota exceeded */ }
  }

  // ─── Storage ───
  function loadState() {
    var data = safeLoad();
    if (data && data.answers) {
      state.answers = data.answers;
      state.labels  = data.labels || {};
    } else {
      state.answers = {};
      state.labels  = {};
    }
  }

  function saveState() {
    var essenceMap = { moderne: 'peuplier', ancien: 'okoume' };
    var tailleMap  = { petite: 0, moyenne: 1, grande: 2 };

    var style  = state.answers.style || null;
    var taille = state.answers.taille || null;

    var existing = safeLoad();

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

    safeSave(existing);
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
    answersSnapshotAtOpen = JSON.stringify(state.answers);
    expanded.setAttribute('aria-hidden', 'false');
    toggle.setAttribute('aria-expanded', 'true');
  }

  function closeBanner() {
    if (!state.isOpen) return;
    state.isOpen = false;
    expanded.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');

    // Si les réponses ont changé et que pièce + taille sont répondues → AJAX
    if (hasMinimumAnswers() && JSON.stringify(state.answers) !== answersSnapshotAtOpen) {
      invalidateResults();
      fetchResults(function() {
        applyPageUpdates();
      });
    }
  }

  function toggleBanner() {
    if (state.isOpen) closeBanner();
    else openBanner();
  }

  // ─── Invalidation des résultats quand les réponses changent ───
  function hideRobinCard(prefix) {
    var intro = document.getElementById(prefix + '-perso-intro');
    if (intro) intro.style.display = 'none';
    var grid = document.getElementById(prefix + '-products-grid');
    if (grid) grid.dataset.loaded = '';
  }

  function invalidateResults() {
    var prefs = safeLoad();
    delete prefs.recommendedIds;
    delete prefs.productLinks;
    delete prefs.conseilsText;
    delete prefs.selectionText;
    delete prefs.surMesureText;
    delete prefs.showSurMesure;
    safeSave(prefs);

    // Masquer les cards Robin sur toutes les pages
    hideRobinCard('conseils');
    hideRobinCard('selection');
    hideRobinCard('surmesure');

    // Page Conseils : montrer le bouton refresh
    var conseilsRefresh = document.getElementById('conseils-refresh-btn');
    if (conseilsRefresh) conseilsRefresh.style.display = '';
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
    updateActionButtons();
  }

  // ─── AJAX : Un seul appel (produits + textes IA) ───
  function fetchResults(onDone) {
    if (typeof sapiMonProjet === 'undefined' || !sapiMonProjet.ajaxUrl) {
      if (onDone) onDone();
      return;
    }

    // Annuler l'appel précédent s'il est encore en cours
    if (pendingXhr) { try { pendingXhr.abort(); } catch (e) { /* */ } }

    var xhr = new XMLHttpRequest();
    pendingXhr = xhr;
    xhr.open('POST', sapiMonProjet.ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr !== pendingXhr) return; // Ignoré si un appel plus récent a pris le relais
      pendingXhr = null;

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            var prefs = safeLoad();
            var products = resp.data.products || [];
            prefs.recommendedIds = products.map(function(p) { return p.id; });
            // Stocker les infos produits pour les liens dans le texte
            prefs.productLinks = products.map(function(p) {
              return { name: p.title, url: p.product_url };
            });
            if (resp.data.conseils_text) prefs.conseilsText = resp.data.conseils_text;
            if (resp.data.selection_text) prefs.selectionText = resp.data.selection_text;
            if (resp.data.sur_mesure_text) prefs.surMesureText = resp.data.sur_mesure_text;
            prefs.showSurMesure = !!resp.data.show_sur_mesure;
            safeSave(prefs);
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

  // ─── Page updates after AJAX ───
  function applyPageUpdates() {
    applyAiTexts();
  }

  // ─── Linkifier les noms de produits dans un texte ───
  function linkifyProducts(text, productLinks) {
    if (!productLinks || !productLinks.length) return escapeHtml(text).replace(/\n\n/g, '<br><br>');
    // Convertir les sauts de ligne en marqueurs, puis escape
    var html = escapeHtml(text).replace(/\n\n/g, '<br><br>');
    var linked = {};
    for (var i = 0; i < productLinks.length; i++) {
      var p = productLinks[i];
      if (!p.name || !p.url) continue;
      var firstName = p.name.split(' ')[0];
      if (firstName.length < 3 || linked[firstName.toLowerCase()]) continue;
      linked[firstName.toLowerCase()] = true;
      // Regex insensible à la casse, mot entier — remplace uniquement la 1ère occurrence
      var escaped = firstName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      var re = new RegExp('\\b(' + escaped + ')\\b', 'i');
      html = html.replace(re, '<a href="' + escapeHtml(p.url) + '" class="robin-conseil__product-link">$1</a>');
    }
    return html;
  }

  // ─── Robin-conseil card : affichage mutualisé ───
  function applyRobinCard(prefix, textKey, prefs) {
    var intro = document.getElementById(prefix + '-perso-intro');
    var textEl = document.getElementById(prefix + '-perso-text');
    if (!intro || !textEl || !prefs[textKey]) return;

    textEl.textContent = prefs[textKey];
    intro.style.display = '';

    // Chips projet
    var chips = document.getElementById(prefix + '-conseil-chips');
    if (chips && prefs.labels) {
      var html = '';
      for (var key in prefs.labels) {
        if (prefs.labels[key]) {
          html += '<span class="robin-conseil__chip">' + escapeHtml(prefs.labels[key]) + '</span>';
        }
      }
      chips.innerHTML = html;
    }

    // Bouton "Voir ma sélection"
    var selBtn = document.getElementById(prefix + '-selection-btn');
    if (selBtn && prefs.recommendedIds && prefs.recommendedIds.length > 0) {
      selBtn.style.display = '';
    }

    // Produits recommandés
    var grid = document.getElementById(prefix + '-products-grid');
    if (grid && prefs.recommendedIds && prefs.recommendedIds.length > 0) {
      renderProductCards(grid, prefs.recommendedIds);
    }
  }

  // ─── Page-specific AI text injection ───
  function applyAiTexts() {
    var prefs = safeLoad();

    // Page Conseils — texte conseil uniquement, pas de produits
    applyRobinCard('conseils', 'conseilsText', prefs);
    var conseilsGrid = document.getElementById('conseils-products-grid');
    if (conseilsGrid) conseilsGrid.style.display = 'none';

    var conseilsRefresh = document.getElementById('conseils-refresh-btn');
    if (conseilsRefresh && prefs.conseilsText) conseilsRefresh.style.display = 'none';

    var conseilsCta = document.querySelector('.advice-guide-cta');
    if (conseilsCta && prefs.recommendedIds && prefs.recommendedIds.length > 0) {
      conseilsCta.style.display = 'none';
    }

    // Page Nos Créations — masquer le bouton sélection (déjà sur la page)
    applyRobinCard('selection', 'selectionText', prefs);
    var selBtnCreations = document.getElementById('selection-selection-btn');
    if (selBtnCreations) selBtnCreations.style.display = 'none';

    // Page Sur-mesure — pré-remplissage formulaire uniquement
    prefillSurMesureForm(prefs);
  }

  // ─── Render product cards via AJAX ───
  function renderProductCards(grid, ids) {
    if (grid.dataset.loaded === 'true') return;
    if (typeof sapiMonProjet === 'undefined' || !sapiMonProjet.ajaxUrl) return;

    var xhr = new XMLHttpRequest();
    xhr.open('POST', sapiMonProjet.ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            var title = grid.querySelector('.robin-conseil__products-title');
            var titleHtml = title ? title.outerHTML : '';
            grid.innerHTML = titleHtml + resp.data;
            grid.dataset.loaded = 'true';

            // Card "Projet sur mesure" en dernière position
            var prefs = safeLoad();
            if (prefs.showSurMesure && prefs.surMesureText) {
              var ul = grid.querySelector('ul.products');
              if (ul) {
                var li = document.createElement('li');
                li.className = 'product sur-mesure-card';
                li.innerHTML = '<a href="/sur-mesure/" class="sur-mesure-card__link">'
                  + '<div class="sur-mesure-card__icon">'
                  + '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>'
                  + '</div>'
                  + '<h3 class="sur-mesure-card__title">Cr\u00e9ation sur mesure</h3>'
                  + '<p class="sur-mesure-card__text">' + escapeHtml(prefs.surMesureText) + '</p>'
                  + '<span class="sur-mesure-card__cta">D\u00e9couvrir \u2192</span>'
                  + '</a>';
                ul.appendChild(li);
              }
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
    if (!prefs.labels) return;
    var form = document.getElementById('sur-mesure-form');
    if (!form) return;

    var textarea = form.querySelector('textarea[name="message"]');
    if (!textarea || textarea.value) return; // Ne pas écraser une saisie existante

    var labelMap = {
      piece: 'Pièce',
      taille: 'Taille',
      style: 'Style',
      sortie: 'Sortie électrique',
      hauteur: 'Hauteur',
      eclairage: 'Type d\u2019éclairage',
      ambiance: 'Ambiance',
    };

    var lines = [];
    for (var key in labelMap) {
      if (prefs.labels[key]) {
        lines.push(labelMap[key] + ' : ' + prefs.labels[key]);
      }
    }

    if (lines.length > 0) {
      textarea.value = 'Mon projet :\n' + lines.join('\n') + '\n\n';
    }
  }

  // ─── Boutons d'action dans le bandeau déplié ───
  var btnConseils  = document.getElementById('mon-projet-btn-conseils');
  var btnSelection = document.getElementById('mon-projet-btn-selection');

  function updateActionButtons() {
    var disabled = !hasMinimumAnswers();
    if (btnConseils)  btnConseils.classList.toggle('is-disabled', disabled);
    if (btnSelection) btnSelection.classList.toggle('is-disabled', disabled);
  }

  // Lancer AJAX avant navigation si résultats pas encore dispo
  function handleActionClick(btn, e) {
    var prefs = safeLoad();
    if (prefs.recommendedIds && prefs.recommendedIds.length > 0) return;

    if (hasMinimumAnswers()) {
      e.preventDefault();
      btn.textContent = 'Chargement\u2026';
      fetchResults(function() {
        window.location.href = btn.href;
      });
    }
  }

  if (btnConseils) {
    btnConseils.addEventListener('click', function(e) { handleActionClick(btnConseils, e); });
  }
  if (btnSelection) {
    btnSelection.addEventListener('click', function(e) { handleActionClick(btnSelection, e); });
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

  // ─── Formulaire contact inline "Contacter Robin" ───
  function initContactForm(prefix) {
    var contactBtn = document.getElementById(prefix + '-contact-btn');
    var formEl     = document.getElementById(prefix + '-contact-form');
    var emailInput = document.getElementById(prefix + '-contact-email');
    var phoneInput = document.getElementById(prefix + '-contact-phone');
    var msgInput   = document.getElementById(prefix + '-contact-msg');
    var sendBtn    = document.getElementById(prefix + '-contact-send');
    var successEl  = document.getElementById(prefix + '-contact-success');
    if (!contactBtn || !formEl) return;

    contactBtn.addEventListener('click', function() {
      formEl.style.display = '';
      contactBtn.style.display = 'none';
      if (emailInput) emailInput.focus();
    });

    // Activer le bouton dès qu'un email valide est saisi
    function checkEmail() {
      if (!sendBtn || !emailInput) return;
      var v = emailInput.value.trim();
      sendBtn.disabled = !v || v.indexOf('@') === -1 || v.indexOf('.') === -1;
    }
    if (emailInput) {
      emailInput.addEventListener('input', checkEmail);
      emailInput.addEventListener('change', checkEmail);
    }

    if (!sendBtn) return;
    sendBtn.addEventListener('click', function() {
      if (!emailInput) return;
      var email = emailInput.value.trim();
      if (!email) { emailInput.focus(); return; }

      sendBtn.disabled = true;
      sendBtn.textContent = 'Envoi\u2026';

      var prefs = safeLoad();
      var projectSummary = '';
      if (prefs.labels) {
        var parts = [];
        var labelMap = {
          piece: 'Pi\u00e8ce', taille: 'Taille', style: 'Style',
          sortie: 'Sortie', hauteur: 'Hauteur', eclairage: '\u00c9clairage', ambiance: 'Ambiance'
        };
        for (var key in labelMap) {
          if (prefs.labels[key]) parts.push(labelMap[key] + ' : ' + prefs.labels[key]);
        }
        projectSummary = parts.join(', ');
      }

      if (typeof sapiMonProjet === 'undefined' || !sapiMonProjet.ajaxUrl) return;

      var xhr = new XMLHttpRequest();
      xhr.open('POST', sapiMonProjet.ajaxUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

      xhr.onreadystatechange = function() {
        if (xhr.readyState !== 4) return;
        sendBtn.disabled = false;
        sendBtn.textContent = 'Envoyer';

        if (xhr.status === 200) {
          formEl.querySelector('.robin-conseil__contact-fields').style.display = 'none';
          formEl.querySelector('.robin-conseil__contact-intro').style.display = 'none';
          if (successEl) successEl.style.display = '';
        }
      };

      var params = 'action=sapi_robin_contact'
        + '&nonce=' + encodeURIComponent(sapiMonProjet.nonce)
        + '&email=' + encodeURIComponent(email)
        + '&phone=' + encodeURIComponent(phoneInput ? phoneInput.value.trim() : '')
        + '&message=' + encodeURIComponent(msgInput ? msgInput.value.trim() : '')
        + '&project=' + encodeURIComponent(projectSummary)
        + '&page=' + encodeURIComponent(window.location.pathname);

      xhr.send(params);
    });
  }

  initContactForm('conseils');
  initContactForm('selection');

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

/**
 * Robin Conseiller V2 — Modale diaporama
 *
 * State machine + rendu fiches + AJAX lazy + localStorage sync.
 * Données des questions : sapiRobinConseiller.steps (via wp_localize_script).
 *
 * @package Theme_Sapi_Maison
 */
(function () {
  'use strict';

  /* ═══════════════════════════════════════════
     Config & refs (injectés par PHP)
  ═══════════════════════════════════════════ */
  var config = window.sapiRobinConseiller || {};
  var steps    = config.steps || [];
  var icons    = config.icons || {};
  var conseils = config.conseils || {};
  var AJAX_URL    = config.ajaxUrl || '/wp-admin/admin-ajax.php';
  var NONCE       = config.nonce || '';
  var STORAGE_KEY = 'sapiGuidePrefs';

  /* ═══════════════════════════════════════════
     State
  ═══════════════════════════════════════════ */
  var state = {
    answers: {},
    labels: {},
    currentStep: null,
    history: [],
    openingContext: '',
    contextData: {},
    conversation: [],
    aiCache: {},
    pendingXhr: null,
    isOpen: false
  };

  /* ═══════════════════════════════════════════
     localStorage helpers (même format que V1)
  ═══════════════════════════════════════════ */
  function safeLoad() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
    catch (e) { return {}; }
  }

  function safeSave(data) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); }
    catch (e) { /* quota exceeded */ }
  }

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

    existing.answers    = state.answers;
    existing.labels     = state.labels;
    existing.essence    = essenceMap[style] || null;
    existing.tailleIndex = taille in tailleMap ? tailleMap[taille] : null;
    existing.pieceLabel  = state.labels.piece || null;
    existing.styleLabel  = state.labels.style || null;
    existing.tailleLabel = state.labels.taille || null;

    if (!existing.recommendedIds) {
      existing.recommendedIds = [];
    }

    safeSave(existing);
  }

  /* ═══════════════════════════════════════════
     Logique de visibilité (portée de guide-data.php)
  ═══════════════════════════════════════════ */
  function getVisibleSteps() {
    var visible = [];
    for (var i = 0; i < steps.length; i++) {
      var step = steps[i];
      var vis  = step.visibility;

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

  function getStepById(id) {
    for (var i = 0; i < steps.length; i++) {
      if (steps[i].id === id) return steps[i];
    }
    return null;
  }

  function getNextStep(afterStepId) {
    var visible = getVisibleSteps();
    var idx = visible.indexOf(afterStepId);
    if (idx === -1 || idx >= visible.length - 1) return null;

    // Cherche le prochain step visible qui n'est pas encore répondu
    for (var i = idx + 1; i < visible.length; i++) {
      if (!state.answers[visible[i]]) return visible[i];
    }
    // Tous répondus → recommandation
    return 'recommendation';
  }

  function getFirstUnansweredStep() {
    var visible = getVisibleSteps();
    for (var i = 0; i < visible.length; i++) {
      if (!state.answers[visible[i]]) return visible[i];
    }
    return 'recommendation';
  }

  function hasAnyAnswer() {
    for (var k in state.answers) {
      if (state.answers.hasOwnProperty(k)) return true;
    }
    return false;
  }

  /* ═══════════════════════════════════════════
     Question dynamique (table/îlot selon pièce)
  ═══════════════════════════════════════════ */
  function getQuestionText(step) {
    if (step.dynamic_question && step.dynamic_question.piece) {
      var piece = state.answers.piece;
      if (piece && step.dynamic_question.piece[piece]) {
        return step.dynamic_question.piece[piece];
      }
    }
    return step.question;
  }

  /* ═══════════════════════════════════════════
     Lookup conseil pré-généré
  ═══════════════════════════════════════════ */
  function getConseil(stepId, slug) {
    // Construire la clé selon les règles de croisement par étape
    var piece = state.answers.piece || '';
    var taille = state.answers.taille || '';
    var taille_esc = state.answers.taille_escalier || '';
    var key = '';

    switch (stepId) {
      case 'piece':
        key = 'piece:' + slug;
        break;

      case 'taille':
        key = 'taille:' + slug + '|piece:' + piece;
        break;

      case 'taille_escalier':
        key = 'taille_escalier:' + slug;
        break;

      case 'eclairage':
        key = 'eclairage:' + slug + '|piece:' + piece;
        break;

      case 'sortie':
        if (slug === 'ne-sais-pas') {
          key = 'sortie:ne-sais-pas';
        } else if (slug === 'mur') {
          key = 'sortie:mur|piece:' + piece;
        } else {
          // plafond et pas-de-sortie : croisement piece × taille
          var t = piece === 'escalier' ? '|taille_escalier:' + taille_esc : '|taille:' + taille;
          key = 'sortie:' + slug + '|piece:' + piece + t;
        }
        break;

      case 'hauteur':
        var t = piece === 'escalier' ? '|taille_escalier:' + taille_esc : '|taille:' + taille;
        key = 'hauteur:' + slug + '|piece:' + piece + t;
        break;

      case 'table':
        key = 'table:' + slug + '|piece:' + piece;
        break;

      case 'style':
        key = 'style:' + slug;
        break;

      default:
        key = stepId + ':' + slug;
    }

    return conseils[key] || null;
  }

  /* ═══════════════════════════════════════════
     DOM refs
  ═══════════════════════════════════════════ */
  var modal, overlay, body, backBtn, closeBtn, badgeEl;

  function initDomRefs() {
    modal      = document.getElementById('robin-modal');
    overlay    = document.getElementById('robin-modal-overlay');
    body       = document.getElementById('robin-modal-body');
    backBtn    = document.getElementById('robin-modal-back');
    closeBtn   = document.getElementById('robin-modal-close');
    badgeEl    = document.getElementById('robin-modal-badge');
  }

  /* ═══════════════════════════════════════════
     Modale : ouverture / fermeture
  ═══════════════════════════════════════════ */
  function openModal(context, contextData) {
    if (!modal) return;

    state.openingContext = context || 'bandeau';
    state.contextData   = contextData || {};
    state.history       = [];
    state.conversation  = [];

    var startStep;

    switch (state.openingContext) {
      case 'bandeau':
        if (hasAnyAnswer()) {
          var next = getFirstUnansweredStep();
          // Si tout est répondu, revenir à la première étape pour naviguer/modifier
          startStep = (next === 'recommendation') ? steps[0].id : next;
        } else {
          startStep = steps[0].id;
        }
        break;

      case 'category':
        // Fiche de confirmation hardcodée — traitée dans showFiche
        startStep = '_category_confirm';
        break;

      case 'product':
        // Appel IA comparaison — traité dans showFiche
        startStep = '_product_compare';
        break;

      case 'homepage':
        // La pièce est déjà sélectionnée via contextData.piece
        if (state.contextData.piece) {
          var pieceStep = getStepById('piece');
          if (pieceStep) {
            var choice = null;
            for (var c = 0; c < pieceStep.choices.length; c++) {
              if (pieceStep.choices[c].slug === state.contextData.piece) {
                choice = pieceStep.choices[c];
                break;
              }
            }
            if (choice) {
              state.answers.piece = choice.slug;
              state.labels.piece  = choice.label;
              cleanInvisibleAnswers();
              saveState();
            }
          }
          startStep = getNextStep('piece') || 'taille';
        } else {
          startStep = steps[0].id;
        }
        break;

      default:
        startStep = steps[0].id;
    }

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    state.isOpen = true;

    updateModalProject();
    showFiche(startStep);
  }

  function closeModal() {
    if (!modal) return;

    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    state.isOpen = false;

    // Annuler tout AJAX en cours
    if (state.pendingXhr) {
      state.pendingXhr.abort();
      state.pendingXhr = null;
    }

    saveState();
    refreshPageVisuals();
  }

  /* ═══════════════════════════════════════════
     Rendu d'une fiche
  ═══════════════════════════════════════════ */
  function showFiche(stepId) {
    state.currentStep = stepId;

    // Navigation : retour + titre
    updateHeader(stepId);

    // Fiches spéciales (contextes d'ouverture)
    if (stepId === '_category_confirm') {
      renderCategoryConfirm();
      return;
    }
    if (stepId === '_product_compare') {
      renderProductCompare();
      return;
    }
    if (stepId === 'recommendation') {
      renderRecommendation();
      return;
    }
    if (stepId === 'hors_parcours') {
      renderHorsParcours();
      return;
    }

    // Fiche questionnaire standard
    var step = getStepById(stepId);
    if (!step) return;

    // Première fiche (piece) sans réponse = texte d'accueil hardcodé, pas d'appel IA
    var isFirstFiche = (stepId === steps[0].id && state.history.length === 0);

    var html = '';
    var shouldAnimate = true;

    // ─── Zone haute : conseil centré ───
    html += '<div class="robin-fiche__top">';
    html += '<div class="robin-fiche__conseil" id="robin-fiche-conseil">';
    if (isFirstFiche) {
      html += renderConseil({ conseil_text: 'Chaque luminaire que je cr\u00e9e est une pi\u00e8ce unique, fa\u00e7onn\u00e9e \u00e0 la main dans mon atelier. Pour vous orienter au mieux, dites-moi dans quelle pi\u00e8ce vous imaginez votre futur luminaire.' }, true);
    } else {
      var lastStep = state.history.length > 0 ? state.history[state.history.length - 1] : null;
      var lastSlug = lastStep ? state.answers[lastStep] : null;
      var conseilData = lastStep && lastSlug ? getConseil(lastStep, lastSlug) : null;
      if (conseilData) {
        html += renderConseil(conseilData, true);
      } else {
        shouldAnimate = false;
      }
    }
    html += '</div>';

    // Lien sortant (dans la zone haute, caché si animé)
    var lastStepForLink = state.history.length > 0 ? state.history[state.history.length - 1] : null;
    var lastSlugForLink = lastStepForLink ? state.answers[lastStepForLink] : null;
    var linkData = lastStepForLink && lastSlugForLink ? getConseil(lastStepForLink, lastSlugForLink) : null;
    if (linkData && linkData.link_url) {
      html += '<div class="robin-fiche__link" id="robin-fiche-link"' + (shouldAnimate ? ' style="opacity:0;"' : '') + '><a href="' + escHtml(linkData.link_url) + '">';
      html += escHtml(linkData.link_label || 'Voir') + ' &rarr;</a></div>';
    }
    html += '</div>';

    // ─── Zone basse : question + boutons + input (cachée si animée) ───
    html += '<div class="robin-fiche__bottom" id="robin-fiche-bottom"' + (shouldAnimate ? ' style="opacity:0;"' : '') + '>';

    // Question
    html += '<div class="robin-fiche__question">' + escHtml(getQuestionText(step)) + '</div>';

    // Boutons choix
    html += '<div class="robin-fiche__choices">';
    for (var i = 0; i < step.choices.length; i++) {
      var c = step.choices[i];
      var selected = state.answers[stepId] === c.slug ? ' is-selected' : '';
      var iconHtml = icons[c.icon] ? '<span class="robin-fiche__choice-icon">' + icons[c.icon] + '</span>' : '';
      var dimHtml  = c.dim ? ' <span class="robin-fiche__choice-dim">' + escHtml(c.dim) + '</span>' : '';
      html += '<button class="robin-fiche__choice' + selected + '" data-step="' + escAttr(stepId) + '" data-slug="' + escAttr(c.slug) + '" data-label="' + escAttr(c.label) + '">';
      html += iconHtml + escHtml(c.label) + dimHtml;
      html += '</button>';
    }
    html += '</div>';

    // Champ texte libre
    html += renderTextInput();

    html += '</div>';

    body.innerHTML = html;

    if (shouldAnimate) {
      animateConseil();
    }
  }

  /* ═══════════════════════════════════════════
     Rendu partiel : conseil, loader, input
  ═══════════════════════════════════════════ */
  function renderConseil(data, animate) {
    if (!data || !data.conseil_text) return '';
    var html = '<div class="robin-fiche__citation">';
    if (animate) {
      // Mots avec opacity 0, animés ensuite par animateConseil()
      var words = data.conseil_text.split(' ');
      html += '<p class="robin-fiche__citation-text">';
      for (var i = 0; i < words.length; i++) {
        html += '<span class="robin-word" style="opacity:0;">' + escHtml(words[i]) + '</span> ';
      }
      html += '</p>';
      html += '<span class="robin-fiche__signature" style="opacity:0;">&mdash; Robin</span>';
    } else {
      html += '<p class="robin-fiche__citation-text">' + escHtml(data.conseil_text) + '</p>';
      html += '<span class="robin-fiche__signature">&mdash; Robin</span>';
    }
    html += '</div>';
    return html;
  }

  function animateConseil() {
    var words = body.querySelectorAll('.robin-word');
    var signature = body.querySelector('.robin-fiche__signature');
    var bottom = document.getElementById('robin-fiche-bottom');
    var startDelay = 500; // 0.5s avant de commencer
    var wordDelay = 50;    // 50ms entre chaque mot

    for (var i = 0; i < words.length; i++) {
      (function(el, d) {
        setTimeout(function() {
          el.style.transition = 'opacity 0.25s';
          el.style.opacity = '1';
        }, d);
      })(words[i], startDelay + i * wordDelay);
    }

    var endTime = startDelay + words.length * wordDelay;

    // Signature apparaît après le dernier mot
    if (signature) {
      setTimeout(function() {
        signature.style.transition = 'opacity 0.4s';
        signature.style.opacity = '1';
      }, endTime + 200);
    }

    // Lien sortant apparaît après la signature
    var link = document.getElementById('robin-fiche-link');
    if (link) {
      setTimeout(function() {
        link.style.transition = 'opacity 0.4s';
        link.style.opacity = '1';
      }, endTime + 500);
    }

    // Zone basse apparaît après le lien
    if (bottom) {
      setTimeout(function() {
        bottom.style.transition = 'opacity 0.4s';
        bottom.style.opacity = '1';
      }, endTime + 800);
    }
  }

  function renderConseilLoader() {
    return '<div class="robin-fiche__loader">' +
      '<div class="robin-fiche__loader-line"></div>' +
      '<div class="robin-fiche__loader-line robin-fiche__loader-line--short"></div>' +
      '</div>';
  }

  function renderTextInput() {
    return '<div class="robin-fiche__input-wrap">' +
      '<div class="robin-fiche__input">' +
      '<input type="text" id="robin-text-input" placeholder="&Eacute;crire &agrave; Robin..." autocomplete="off">' +
      '<button type="button" class="robin-fiche__send" id="robin-text-send">&uarr;</button>' +
      '</div></div>';
  }

  /* ═══════════════════════════════════════════
     Fiches spéciales
  ═══════════════════════════════════════════ */
  function renderCategoryConfirm() {
    var slug = state.contextData.category_slug || '';
    var labelMap = {
      'suspensions': 'une suspension',
      'appliques': 'une applique murale',
      'lampadaires': 'un lampadaire',
      'lampesaposer': 'une lampe à poser'
    };
    var label = labelMap[slug] || 'ce type de luminaire';

    var html = '<div class="robin-fiche__conseil">';
    html += '<div class="robin-fiche__citation">';
    html += '<p class="robin-fiche__citation-text">C\'est bien ' + escHtml(label) + ' que vous cherchez ?</p>';
    html += '</div></div>';

    html += '<div class="robin-fiche__choices">';
    html += '<button class="robin-fiche__choice" id="robin-category-yes">Oui</button>';
    html += '<button class="robin-fiche__choice" id="robin-category-no">Non, autre chose</button>';
    html += '</div>';

    html += renderTextInput();
    body.innerHTML = html;
  }

  function renderProductCompare() {
    // TODO Phase D : appel IA comparaison produit/projet
    var html = '<div class="robin-fiche__conseil">';
    html += renderConseilLoader();
    html += '</div>';
    html += renderTextInput();
    body.innerHTML = html;
  }

  function renderRecommendation() {
    // TODO Phase E : appel pipeline complet
    var html = '<div class="robin-fiche__conseil">';
    html += renderConseilLoader();
    html += '</div>';
    html += renderTextInput();
    body.innerHTML = html;
  }

  function renderHorsParcours() {
    // TODO Phase C : fiche hors parcours
    var html = '<div class="robin-fiche__conseil">';
    html += renderConseilLoader();
    html += '</div>';
    html += renderTextInput();
    body.innerHTML = html;
  }

  /* ═══════════════════════════════════════════
     Header modale (retour, titre, avatar)
  ═══════════════════════════════════════════ */
  function updateHeader(stepId) {
    var isFirst = state.history.length === 0;

    // Bouton retour (gauche) — invisible mais occupe l'espace sur la première fiche
    backBtn.style.visibility = isFirst ? 'hidden' : 'visible';
  }

  /* ═══════════════════════════════════════════
     Interaction : clic bouton choix
  ═══════════════════════════════════════════ */
  function onChoiceClick(stepId, slug, label) {
    // Mettre à jour state
    state.answers[stepId] = slug;
    state.labels[stepId]  = label;

    // Nettoyer les réponses des steps cachés
    cleanInvisibleAnswers();
    saveState();

    // Déterminer la fiche suivante
    var next = getNextStep(stepId);
    if (!next) next = 'recommendation';

    // Empiler l'historique
    state.history.push(stepId);

    // Afficher la fiche suivante (instantané)
    showFiche(next);

    // Mettre à jour le bandeau + résumé modale
    updateBandeauChips();
    updateModalProject();
  }

  /* ═══════════════════════════════════════════
     Interaction : texte libre
  ═══════════════════════════════════════════ */
  function onFreeText(message) {
    if (!message || !message.trim()) return;

    // Empiler le step courant dans l'historique
    if (state.currentStep) {
      state.history.push(state.currentStep);
    }

    // Afficher un loader plein écran dans la modale
    state.currentStep = '_free_text_loading';
    var topEl = body.querySelector('.robin-fiche__top');
    var bottomEl = document.getElementById('robin-fiche-bottom');
    if (topEl) {
      topEl.innerHTML = '<div class="robin-fiche__conseil">' + renderConseilLoader() + '</div>';
    }
    if (bottomEl) {
      bottomEl.style.opacity = '0';
      bottomEl.style.pointerEvents = 'none';
    }

    // Appel AJAX
    var fd = new FormData();
    fd.append('action', 'sapi_robin_conseil_step');
    fd.append('nonce', NONCE);
    fd.append('guide_website', '');
    fd.append('step_id', state.history[state.history.length - 1] || 'piece');
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('opening_context', state.openingContext);
    fd.append('context_data', JSON.stringify(state.contextData));
    fd.append('user_message', message);
    fd.append('conversation', JSON.stringify(state.conversation));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', AJAX_URL, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            handleFreeTextResponse(resp.data, message);
            return;
          }
        } catch (e) {}
      }

      // Erreur — revenir à la fiche précédente
      if (state.history.length > 0) {
        var prev = state.history.pop();
        showFiche(prev);
      }
    };
    xhr.send(fd);
  }

  function handleFreeTextResponse(data, originalMessage) {
    // 1. Auto-remplir les réponses déduites
    if (data.answered_steps && typeof data.answered_steps === 'object') {
      for (var stepId in data.answered_steps) {
        if (data.answered_steps.hasOwnProperty(stepId)) {
          state.answers[stepId] = data.answered_steps[stepId];
          // Trouver le label correspondant
          var step = getStepById(stepId);
          if (step) {
            for (var i = 0; i < step.choices.length; i++) {
              if (step.choices[i].slug === data.answered_steps[stepId]) {
                state.labels[stepId] = step.choices[i].label;
                break;
              }
            }
          }
        }
      }
      cleanInvisibleAnswers();
      saveState();
      updateBandeauChips();
      updateModalProject();
    }

    // 2. Sauvegarder dans la conversation
    state.conversation.push({ role: 'user', content: originalMessage });
    state.conversation.push({ role: 'assistant', content: data.conseil_text || '' });

    // 3. Afficher la réponse IA
    var nextStep = data.next_step_id || getFirstUnansweredStep();
    state.currentStep = nextStep;

    // Rendre la fiche avec le conseil IA et les boutons suggérés
    var html = '';

    // Zone haute : conseil IA
    html += '<div class="robin-fiche__top">';
    html += '<div class="robin-fiche__conseil">';
    html += renderConseil({ conseil_text: data.conseil_text }, true);
    html += '</div>';
    if (data.link_url) {
      html += '<div class="robin-fiche__link" id="robin-fiche-link" style="opacity:0;"><a href="' + escHtml(data.link_url) + '">';
      html += escHtml(data.link_label || 'Voir') + ' &rarr;</a></div>';
    }
    html += '</div>';

    // Zone basse : boutons suggérés par l'IA OU la prochaine question du questionnaire
    html += '<div class="robin-fiche__bottom" id="robin-fiche-bottom" style="opacity:0;">';

    if (data.suggested_buttons && data.suggested_buttons.length > 0) {
      html += '<div class="robin-fiche__choices">';
      for (var i = 0; i < data.suggested_buttons.length; i++) {
        var btn = data.suggested_buttons[i];
        if (btn.url) {
          // Bouton lien → ouvre une page
          html += '<a class="robin-fiche__choice robin-fiche__choice--link" href="' + escAttr(btn.url) + '">';
          html += escHtml(btn.label);
          html += '</a>';
        } else {
          // Bouton questionnaire → navigue vers une étape
          html += '<button class="robin-fiche__choice" data-step="' + escAttr(btn.step_id || '') + '" data-slug="' + escAttr(btn.slug || '') + '" data-label="' + escAttr(btn.label || '') + '">';
          html += escHtml(btn.label);
          html += '</button>';
        }
      }
      html += '</div>';
    } else if (nextStep !== 'hors_parcours' && nextStep !== 'recommendation') {
      // Afficher la prochaine question du questionnaire
      var nextStepData = getStepById(nextStep);
      if (nextStepData) {
        html += '<div class="robin-fiche__question">' + escHtml(getQuestionText(nextStepData)) + '</div>';
        html += '<div class="robin-fiche__choices">';
        for (var j = 0; j < nextStepData.choices.length; j++) {
          var c = nextStepData.choices[j];
          var iconHtml = icons[c.icon] ? '<span class="robin-fiche__choice-icon">' + icons[c.icon] + '</span>' : '';
          var dimHtml = c.dim ? ' <span class="robin-fiche__choice-dim">' + escHtml(c.dim) + '</span>' : '';
          html += '<button class="robin-fiche__choice" data-step="' + escAttr(nextStep) + '" data-slug="' + escAttr(c.slug) + '" data-label="' + escAttr(c.label) + '">';
          html += iconHtml + escHtml(c.label) + dimHtml;
          html += '</button>';
        }
        html += '</div>';
      }
    }

    html += renderTextInput();
    html += '</div>';

    body.innerHTML = html;
    updateHeader(nextStep);
    animateConseil();
  }

  /* ═══════════════════════════════════════════
     Navigation retour
  ═══════════════════════════════════════════ */
  function goBack() {
    if (state.history.length === 0) return;
    var prevStep = state.history.pop();
    showFiche(prevStep);
  }

  /* ═══════════════════════════════════════════
     AJAX : fetch conseil IA (lazy)
  ═══════════════════════════════════════════ */
  function fetchConseil(stepId) {
    // Annuler le précédent
    if (state.pendingXhr) {
      state.pendingXhr.abort();
      state.pendingXhr = null;
    }

    var fd = new FormData();
    fd.append('action', 'sapi_robin_conseil_step');
    fd.append('nonce', NONCE);
    fd.append('guide_website', '');
    fd.append('step_id', stepId);
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('opening_context', state.openingContext);
    fd.append('context_data', JSON.stringify(state.contextData));
    fd.append('user_message', '');
    fd.append('conversation', JSON.stringify(state.conversation));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', AJAX_URL, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      state.pendingXhr = null;

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            state.aiCache[stepId] = resp.data;

            // Si on est toujours sur cette fiche, mettre à jour la zone conseil
            if (state.currentStep === stepId) {
              var conseilEl = document.getElementById('robin-fiche-conseil');
              if (conseilEl) {
                conseilEl.innerHTML = renderConseil(resp.data);
                // Ajouter le lien s'il y en a un
                if (resp.data.link_url) {
                  var linkHtml = '<div class="robin-fiche__link"><a href="' + escHtml(resp.data.link_url) + '">';
                  linkHtml += escHtml(resp.data.link_label || 'Voir') + ' &rarr;</a></div>';
                  conseilEl.insertAdjacentHTML('afterend', linkHtml);
                }
              }
            }
          }
        } catch (e) {
          // Silently fail — les boutons sont déjà affichés
        }
      }
    };
    xhr.send(fd);
    state.pendingXhr = xhr;
  }

  /* ═══════════════════════════════════════════
     Bandeau : mise à jour des chips
  ═══════════════════════════════════════════ */
  function updateBandeauChips() {
    var chipsEl = document.getElementById('robin-bandeau-chips');
    if (!chipsEl) return;

    var parts = [];
    var visible = getVisibleSteps();
    for (var i = 0; i < visible.length; i++) {
      var lbl = state.labels[visible[i]];
      if (lbl) parts.push(lbl);
    }

    chipsEl.textContent = parts.length > 0
      ? parts.join(' · ')
      : 'Robin peut vous conseiller';
  }

  /* ═══════════════════════════════════════════
     Résumé projet dans la modale (chips + recommencer)
  ═══════════════════════════════════════════ */
  function updateModalProject() {
    var projectEl = document.getElementById('robin-modal-project');
    if (!projectEl) return;

    var parts = [];
    var visible = getVisibleSteps();
    for (var i = 0; i < visible.length; i++) {
      var lbl = state.labels[visible[i]];
      if (lbl) parts.push(lbl);
    }

    if (parts.length === 0) {
      projectEl.style.display = 'none';
      return;
    }

    var html = '';
    for (var j = 0; j < parts.length; j++) {
      html += '<span class="robin-modal__chip">' + escHtml(parts[j]) + '</span>';
    }
    html += '<button class="robin-modal__reset" id="robin-modal-reset" type="button">Recommencer</button>';

    projectEl.innerHTML = html;
    projectEl.style.display = '';
  }

  function resetProject() {
    state.answers = {};
    state.labels  = {};
    state.history = [];
    state.aiCache = {};
    state.conversation = [];
    localStorage.removeItem(STORAGE_KEY);
    saveState();
    updateBandeauChips();
    updateModalProject();
    showFiche(steps[0].id);
  }

  /* ═══════════════════════════════════════════
     Refresh page visuals (consommateurs localStorage)
  ═══════════════════════════════════════════ */
  function refreshPageVisuals() {
    if (typeof window.sapiPersonalizeRefresh === 'function') {
      window.sapiPersonalizeRefresh();
    }
  }

  /* ═══════════════════════════════════════════
     Helpers
  ═══════════════════════════════════════════ */
  function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function escAttr(str) {
    return escHtml(str).replace(/"/g, '&quot;');
  }

  /* ═══════════════════════════════════════════
     Event listeners
  ═══════════════════════════════════════════ */
  function bindEvents() {
    // Fermer la modale
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay)  overlay.addEventListener('click', closeModal);

    // Retour
    if (backBtn) backBtn.addEventListener('click', goBack);

    // Reset projet (délégation car le bouton est dynamique)
    var projectEl = document.getElementById('robin-modal-project');
    if (projectEl) {
      projectEl.addEventListener('click', function (e) {
        if (e.target.id === 'robin-modal-reset' || e.target.closest('#robin-modal-reset')) {
          resetProject();
        }
      });
    }

    // Échap
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && state.isOpen) closeModal();
    });

    // Délégation : clic sur choix dans la modale
    if (body) {
      body.addEventListener('click', function (e) {
        var btn = e.target.closest('.robin-fiche__choice');
        if (btn && btn.dataset.step && btn.dataset.slug) {
          onChoiceClick(btn.dataset.step, btn.dataset.slug, btn.dataset.label || '');
          return;
        }

        // Confirmation catégorie
        if (e.target.id === 'robin-category-yes') {
          onCategoryConfirm(true);
          return;
        }
        if (e.target.id === 'robin-category-no') {
          onCategoryConfirm(false);
          return;
        }

        // Bouton envoyer texte libre
        if (e.target.id === 'robin-text-send' || e.target.closest('#robin-text-send')) {
          var input = document.getElementById('robin-text-input');
          if (input && input.value.trim()) {
            onFreeText(input.value.trim());
          }
          return;
        }
      });

      // Entrée dans le champ texte
      body.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.id === 'robin-text-input') {
          e.preventDefault();
          if (e.target.value.trim()) {
            onFreeText(e.target.value.trim());
          }
        }
      });
    }

    // Ouverture : bandeau + pills
    document.addEventListener('click', function (e) {
      // Bandeau
      var bandeau = e.target.closest('#robin-bandeau');
      if (bandeau) {
        openModal('bandeau');
        return;
      }

      // Pills contextuelles
      var pill = e.target.closest('.robin-pill');
      if (pill) {
        var context = pill.dataset.robinContext || 'bandeau';
        var data = {};
        try { data = JSON.parse(pill.dataset.robinData || '{}'); } catch (err) {}
        openModal(context, data);
        return;
      }
    });

    // Bandeau accessible au clavier
    var bandeau = document.getElementById('robin-bandeau');
    if (bandeau) {
      bandeau.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openModal('bandeau');
        }
      });
    }
  }

  /* ═══════════════════════════════════════════
     Confirmation catégorie
  ═══════════════════════════════════════════ */
  function onCategoryConfirm(yes) {
    if (yes) {
      // Pré-remplir la sortie selon la catégorie
      var catToSortie = {
        'suspensions': 'plafond',
        'appliques': 'mur',
        'lampadaires': 'pas-de-sortie',
        'lampesaposer': 'pas-de-sortie'
      };
      var slug = state.contextData.category_slug;
      if (slug && catToSortie[slug]) {
        state.answers.sortie = catToSortie[slug];
        state.labels.sortie  = {
          'plafond': 'Au plafond',
          'mur': 'Au mur',
          'pas-de-sortie': 'Sur prise classique 230V'
        }[catToSortie[slug]] || '';
      }
      cleanInvisibleAnswers();
      saveState();
      // Aller à la prochaine question pertinente
      var next = getFirstUnansweredStep();
      state.history.push('_category_confirm');
      showFiche(next);
    } else {
      // Pas la bonne catégorie → fiche 1
      state.history.push('_category_confirm');
      showFiche(steps[0].id);
    }
  }

  /* ═══════════════════════════════════════════
     Init
  ═══════════════════════════════════════════ */
  function init() {
    initDomRefs();
    if (!modal) return;

    // Debug : ?robin=reset pour vider le localStorage
    if (window.location.search.indexOf('robin=reset') !== -1) {
      localStorage.removeItem(STORAGE_KEY);
      state.answers = {};
      state.labels  = {};
      // Nettoyer l'URL
      var url = window.location.href.replace(/[?&]robin=reset/, '').replace(/\?$/, '');
      window.history.replaceState({}, '', url);
    }

    loadState();
    bindEvents();
    updateBandeauChips();
  }

  // Lancement
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // API publique pour ouverture depuis d'autres scripts
  window.sapiRobinOpen = openModal;

})();

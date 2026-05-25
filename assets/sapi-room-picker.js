/**
 * Sapi Room Picker — Bento card "Pour quelle pièce ?" sur la homepage.
 *
 * 3 sous-états togglés au load selon window.sapiProject :
 *  - initial      : pas de projet  → 6 pièces + champ texte libre
 *  - in-progress  : projet partiel → message "continue ton projet" + CTA Reprendre + champ texte libre
 *  - complete     : projet terminé → advice IA + CTA "Voir ma sélection" + champ texte libre
 *
 * Submit du champ texte libre → redirige vers /mes-creations/?freetext=…
 * La modale conseiller (présente sur shop/product) intercepte ce param au
 * load et s'ouvre en mode chat S2 avec le texte initial.
 */
(function () {
  'use strict';

  var picker = null;
  var CFG = window.SAPI_ROOM_PICKER || {};
  var STEPS = Array.isArray(CFG.steps) ? CFG.steps : [];
  var ICONS = CFG.icons || {};
  var CREATIONS_URL = CFG.creationsUrl || '/mes-creations/';

  function getProjectSnapshot() {
    if (!window.sapiProject || typeof window.sapiProject.get !== 'function') return null;
    try { return window.sapiProject.get(); } catch (e) { return null; }
  }

  function projectHasAny(project) {
    if (!project || !project.answers) return false;
    for (var k in project.answers) {
      if (Object.prototype.hasOwnProperty.call(project.answers, k)) return true;
    }
    return false;
  }

  function isProjectComplete(project) {
    if (!project || !project.answers) return false;
    return typeof project.advice_text === 'string' && project.advice_text.length > 0;
  }

  function setState(stateName) {
    if (!picker) return;
    var nodes = picker.querySelectorAll('[data-room-picker-state]');
    nodes.forEach(function (n) {
      n.hidden = (n.getAttribute('data-room-picker-state') !== stateName);
    });
  }

  // Question dynamique selon la pièce (mirror de la modale)
  function getDynamicQuestion(step, answers) {
    if (step.dynamic_question && step.dynamic_question.piece) {
      var p = answers && answers.piece;
      if (p && step.dynamic_question.piece[p]) return step.dynamic_question.piece[p];
    }
    return step.question;
  }

  // Renvoie le step complet de la prochaine question visible non répondue,
  // ou null si parcours achevé. Utilise sapiProject.computeVisibleStepIds
  // pour le calcul de visibilité (mirror exact de la modale).
  function getNextUnansweredStep(project) {
    if (!STEPS.length || !project || !project.answers) return null;
    if (!window.sapiProject || typeof window.sapiProject.computeVisibleStepIds !== 'function') return null;
    var visibleIds = window.sapiProject.computeVisibleStepIds(project.answers, STEPS);
    for (var i = 0; i < visibleIds.length; i++) {
      var id = visibleIds[i];
      if (!project.answers[id]) {
        for (var j = 0; j < STEPS.length; j++) {
          if (STEPS[j].id === id) return STEPS[j];
        }
      }
    }
    return null;
  }

  function populateInProgress(project) {
    var titleEl = picker.querySelector('[data-room-picker-question]');
    var cardsEl = picker.querySelector('[data-room-picker-cards-dynamic]');
    if (!titleEl || !cardsEl) return;

    var nextStep = getNextUnansweredStep(project);
    if (!nextStep) {
      titleEl.textContent = 'On continue ton projet ?';
      cardsEl.innerHTML = '';
      return;
    }

    // Titre = la question dynamique (selon piece)
    titleEl.textContent = getDynamicQuestion(nextStep, project.answers);

    // Cards de choix cliquables (même pattern que les 6 pièces de l'état initial)
    cardsEl.innerHTML = '';
    var choices = nextStep.choices || [];
    choices.forEach(function (choice) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'room-card';
      btn.setAttribute('data-step-id', nextStep.id);
      btn.setAttribute('data-choice-slug', choice.slug);
      btn.setAttribute('data-choice-label', choice.label);

      if (choice.icon && ICONS[choice.icon]) {
        var iconEl = document.createElement('span');
        iconEl.className = 'room-card-icon';
        iconEl.innerHTML = ICONS[choice.icon];
        btn.appendChild(iconEl);
      }

      var labelEl = document.createElement('span');
      labelEl.className = 'room-card-label';
      labelEl.textContent = choice.label;
      btn.appendChild(labelEl);

      cardsEl.appendChild(btn);
    });
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function populateComplete(project) {
    var adviceEl = picker.querySelector('[data-room-picker-advice]');
    if (!adviceEl || !project) return;
    adviceEl.textContent = project.advice_text || '';
  }

  function refresh() {
    var project = getProjectSnapshot();
    if (!projectHasAny(project)) {
      setState('initial');
      return;
    }
    if (isProjectComplete(project)) {
      populateComplete(project);
      setState('complete');
    } else {
      populateInProgress(project);
      setState('in-progress');
    }
  }

  function bindForms() {
    var forms = picker.querySelectorAll('[data-room-picker-freetext]');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var input = form.querySelector('input[name="freetext"]');
        var text = (input && input.value || '').trim();
        if (!text) return;
        // Redirige vers /mes-creations/ avec le texte libre — la modale
        // conseiller détecte le param freetext au load et s'ouvre en S2-chat.
        var url = CREATIONS_URL + '?freetext=' + encodeURIComponent(text);
        window.location.href = url;
      });
    });
  }

  // Click sur une card de choix dynamique (état in-progress) : enregistre
  // la réponse dans sapiProject + redirige vers /mes-creations/ où la
  // modale poursuivra le parcours (card "Mon projet" affiche la question
  // suivante via renderInlineQuestion).
  function bindDynamicCards() {
    picker.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-step-id][data-choice-slug]');
      if (!btn) return;
      e.preventDefault();
      var stepId = btn.getAttribute('data-step-id');
      var slug = btn.getAttribute('data-choice-slug');
      var label = btn.getAttribute('data-choice-label') || slug;
      if (window.sapiProject && typeof window.sapiProject.update === 'function') {
        var patch = {}; patch[stepId] = slug;
        var lpatch = {}; lpatch[stepId] = label;
        window.sapiProject.update(patch, lpatch);
      }
      window.location.href = CREATIONS_URL;
    });
  }

  function init() {
    picker = document.querySelector('[data-room-picker]');
    if (!picker) return;
    refresh();
    bindForms();
    bindDynamicCards();
    // Réagit aux changements de projet (storage event inter-onglets,
    // ou subscribe si sapiProject est chargé).
    if (window.sapiProject && typeof window.sapiProject.subscribe === 'function') {
      window.sapiProject.subscribe(refresh);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

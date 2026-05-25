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

  function populateInProgress(project) {
    var resume = picker.querySelector('[data-room-picker-resume]');
    if (!resume || !project || !project.labels) return;
    var labels = project.labels;
    var ordered = ['piece', 'taille', 'taille_escalier', 'eclairage', 'sortie', 'hauteur', 'table', 'style'];
    var parts = [];
    ordered.forEach(function (k) { if (labels[k]) parts.push(labels[k]); });
    if (parts.length) {
      resume.textContent = 'Tu as commencé : ' + parts.join(' · ') + '. On continue ?';
    }
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
        var url = '/mes-creations/?freetext=' + encodeURIComponent(text);
        window.location.href = url;
      });
    });
  }

  function init() {
    picker = document.querySelector('[data-room-picker]');
    if (!picker) return;
    refresh();
    bindForms();
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

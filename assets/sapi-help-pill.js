/**
 * Sapi Help Pill — Pill "Comment choisir ?" sur fiche produit (F2b).
 *
 * Trois états (repris du legacy robin-conseiller.js → updateProductPillContextual) :
 *  - Sans projet                 → "Comment choisir ?"
 *  - Projet partiel              → "Adapter à mon projet"
 *  - Projet complet (piece + taille|escalier + style) → chips
 *    "Salon · Grande · Peuplier ✓"
 *
 * Click → dispatch sapi:open-modal avec state="product".
 * sapi-modal-conseiller.js route ensuite vers s-product-recap ou parcours court
 * selon le contenu du projet.
 */
(function () {
  'use strict';

  var TXT_INITIAL = 'Comment choisir ?';
  var TXT_PARTIAL = 'Adapter à mon projet';

  // Mapping repris de l'ancien mon-projet.js (pré-F1c)
  var ESSENCE_FROM_STYLE = { moderne: 'peuplier', ancien: 'okoume' };
  var ESSENCE_LABEL      = { peuplier: 'Peuplier', okoume: 'Okoumé' };

  function isProjectComplete(project) {
    if (!project || !project.answers) return false;
    var a = project.answers;
    if (!a.piece) return false;
    if (!a.style) return false;
    if (a.piece === 'escalier') return !!a.taille_escalier;
    return !!a.taille;
  }

  function buildChipsText(project) {
    var labels = project.labels || {};
    var answers = project.answers || {};
    var parts = [];
    if (labels.piece) parts.push(labels.piece);
    if (labels.taille) parts.push(labels.taille);
    else if (labels.taille_escalier) parts.push(labels.taille_escalier);
    var essence = ESSENCE_FROM_STYLE[answers.style];
    if (essence) parts.push(ESSENCE_LABEL[essence]);
    return parts.join(' · ') + ' ✓';
  }

  function updatePillText(textEl, project) {
    if (!textEl) return;
    var newText;
    if (!project || !project.answers || Object.keys(project.answers).length === 0) {
      newText = TXT_INITIAL;
    } else if (isProjectComplete(project)) {
      newText = buildChipsText(project);
    } else {
      newText = TXT_PARTIAL;
    }
    if (textEl.textContent !== newText) textEl.textContent = newText;
  }

  function init() {
    var pill = document.querySelector('[data-help-pill]');
    if (!pill) return;
    var textEl = pill.querySelector('[data-help-pill-text]');
    if (!textEl) return;

    pill.addEventListener('click', function () {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', {
        detail: { state: 'product' }
      }));
    });

    if (window.sapiProject) {
      updatePillText(textEl, window.sapiProject.get());
      if (typeof window.sapiProject.subscribe === 'function') {
        window.sapiProject.subscribe(function (project) {
          updatePillText(textEl, project);
        });
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

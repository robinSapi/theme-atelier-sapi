/**
 * Sapi Help Pill — Pill "Comment choisir ?" sur fiche produit (F2b).
 *
 * - Sans projet : "Comment choisir ?"
 * - Avec projet : "Adapter à mon projet"
 *
 * Le bouton porte data-action="open-modal" data-modal-state="product" ;
 * sapi-modal-conseiller.js capte l'événement sapi:open-modal global.
 * Ici on s'occupe juste de mettre à jour le texte selon sapiProject.
 */
(function () {
  'use strict';

  var TXT_INITIAL = 'Comment choisir ?';
  var TXT_PROJECT = 'Adapter à mon projet';

  function updatePillText(textEl, project) {
    if (!textEl) return;
    var hasProject = !!(project && project.answers && Object.keys(project.answers).length > 0);
    var newText = hasProject ? TXT_PROJECT : TXT_INITIAL;
    if (textEl.textContent !== newText) textEl.textContent = newText;
  }

  function init() {
    var pill = document.querySelector('[data-help-pill]');
    if (!pill) return;
    var textEl = pill.querySelector('[data-help-pill-text]');
    if (!textEl) return;

    // Click → dispatch open-modal (sapi-modal-conseiller.js écoute)
    pill.addEventListener('click', function () {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', {
        detail: { state: 'product' }
      }));
    });

    // Texte initial + abonnement aux changements
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

<?php
/**
 * Robin Conseiller V2 — Shell HTML de la modale.
 * Le contenu des fiches est rendu par JS (innerHTML dans .robin-modal__body).
 *
 * @package Theme_Sapi_Maison
 */

if (!defined('ABSPATH')) exit;

function sapi_robin_modal() {
?>
<div class="robin-modal" id="robin-modal" aria-hidden="true" role="dialog" aria-label="Conseiller Robin">
  <div class="robin-modal__overlay" id="robin-modal-overlay"></div>
  <div class="robin-modal__container">

    <!-- Header : retour | badge centré | fermer -->
    <div class="robin-modal__header" id="robin-modal-header">
      <button class="robin-modal__back" id="robin-modal-back" type="button" aria-label="Retour" style="visibility:hidden;">
        &larr; Retour
      </button>
      <span class="robin-modal__badge" id="robin-modal-badge">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>
        Conseil de Robin
      </span>
      <button class="robin-modal__close" id="robin-modal-close" type="button" aria-label="Fermer">&times;</button>
    </div>

    <!-- Résumé projet : chips + recommencer (rempli par JS) -->
    <div class="robin-modal__project" id="robin-modal-project" style="display:none;"></div>

    <!-- Corps : JS rend les fiches ici -->
    <div class="robin-modal__body" id="robin-modal-body"></div>

  </div>
</div>
<?php
}

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
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
        Mon projet
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

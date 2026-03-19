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

    <!-- Header : avatar + retour + titre + fermer -->
    <div class="robin-modal__header" id="robin-modal-header">
      <div class="robin-modal__header-left">
        <button class="robin-modal__back" id="robin-modal-back" type="button" aria-label="Retour" style="display:none;">
          &larr; Retour
        </button>
        <div class="robin-modal__avatar-wrap" id="robin-modal-avatar-wrap">
          <span class="robin-modal__avatar">R</span>
          <span class="robin-modal__name">Robin</span>
          <span class="robin-modal__role">Votre conseiller luminaire</span>
        </div>
      </div>
      <span class="robin-modal__step-title" id="robin-modal-step-title"></span>
      <button class="robin-modal__close" id="robin-modal-close" type="button" aria-label="Fermer">&times;</button>
    </div>

    <!-- Corps : JS rend les fiches ici -->
    <div class="robin-modal__body" id="robin-modal-body"></div>

  </div>
</div>
<?php
}

<?php
/**
 * Robin Conseiller V2 — Bandeau simplifié sous le header.
 * Barre cliquable qui ouvre la modale. Pas de dépliement.
 *
 * @package Theme_Sapi_Maison
 */

if (!defined('ABSPATH')) exit;

function sapi_robin_bandeau_v2() {
?>
<div class="robin-bandeau" id="robin-bandeau" role="button" tabindex="0"
     data-robin-context="bandeau" aria-label="Ouvrir le conseiller Robin">
  <div class="robin-bandeau__left">
    <span class="robin-bandeau__badge">
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18h6M12 2v1M18 12a6 6 0 1 1-12 0 6 6 0 0 1 12 0z"/></svg>
      Mon projet
    </span>
    <span class="robin-bandeau__chips" id="robin-bandeau-chips">Robin peut vous conseiller</span>
  </div>
  <span class="robin-bandeau__arrow" aria-hidden="true">&rsaquo;</span>
</div>
<?php
}

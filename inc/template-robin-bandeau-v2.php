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
      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
      Mon projet
    </span>
    <span class="robin-bandeau__chips" id="robin-bandeau-chips">Robin peut vous conseiller</span>
  </div>
  <span class="robin-bandeau__arrow" aria-hidden="true">&rsaquo;</span>
</div>
<?php
}

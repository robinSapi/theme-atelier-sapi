<?php
/**
 * Robin Conseiller V2 — Bandeau dual-mode (redesigné + projet).
 *
 * Mode REPOS (par défaut, pas de projet en localStorage) :
 *   Bandeau fond bois avec picto, copy, 3 étapes visuelles et CTA blanc.
 *
 * Mode PROJET (projet en cours détecté par robin-conseiller.js) :
 *   badge "Mon projet" + chips résumant les réponses + flèche.
 *
 * Le toggle entre les deux modes est géré côté JS via la classe .has-project
 * sur l'élément #robin-bandeau (cf. updateBandeauChips() dans robin-conseiller.js).
 *
 * @package Theme_Sapi_Maison
 */

if (!defined('ABSPATH')) exit;

function sapi_robin_bandeau_v2() {
?>
<div class="robin-bandeau robin-bandeau--mode-repos" id="robin-bandeau" role="button" tabindex="0"
     data-robin-context="bandeau" aria-label="Ouvrir le conseiller Robin">

  <!-- ── Mode REPOS : redesigné — fond bois, étapes, CTA blanc ── -->
  <div class="robin-bandeau__repos" aria-hidden="false">
    <div class="robin-left">
      <div class="robin-picto">
        <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">
          <line x1="12" y1="1" x2="12" y2="5"/>
          <path d="M6 5 Q4 12 6 18 Q9 22 12 22 Q15 22 18 18 Q20 12 18 5 Z"/>
          <line x1="7" y1="13" x2="17" y2="13"/>
        </svg>
      </div>
      <div class="robin-copy">
        <h3>Pas sûr de votre choix ? Robin vous guide.</h3>
        <p>3 minutes pour trouver le luminaire idéal pour votre espace.</p>
      </div>
    </div>
    <div class="robin-steps">
      <div class="robin-step"><div class="robin-step-num">1</div><span>Votre pièce</span></div>
      <div class="robin-step-sep"></div>
      <div class="robin-step"><div class="robin-step-num">2</div><span>Votre style</span></div>
      <div class="robin-step-sep"></div>
      <div class="robin-step"><div class="robin-step-num">3</div><span>Votre reco</span></div>
    </div>
    <button type="button" class="robin-cta-btn js-open-robin">
      Me guider
      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
        <path d="M3 8h10M9 4l4 4-4 4"/>
      </svg>
    </button>
  </div>

  <!-- ── Mode PROJET : badge + chips ── -->
  <div class="robin-bandeau__projet" aria-hidden="true">
    <div class="robin-bandeau__left">
      <span class="robin-bandeau__badge">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
        Mon projet
      </span>
      <span class="robin-bandeau__chips" id="robin-bandeau-chips">Besoin d'aide pour choisir ?</span>
    </div>
    <span class="robin-bandeau__arrow" aria-hidden="true">&rsaquo;</span>
  </div>
</div>
<?php
}

/**
 * Sapi Sur-mesure Card — Card "Sur-mesure" intercalée dans la grille produit (F2a Phase 4)
 *
 * 3 états :
 * - empty   : pas de projet en cours → form complet (email + textarea)
 * - project : projet en cours → form compact (email + précisions optionnelles + chips récap)
 * - success : après envoi réussi → message de confirmation
 *
 * Soumission → endpoint sapi_megafilter_surmesure (déjà créé Phase 1).
 *
 * Réutilise SAPI_PROJECT (ajaxUrl + nonce) localisé sur sapi-project.js.
 */
(function () {
  'use strict';

  var config = window.SAPI_PROJECT || {};
  var els = {};
  var submitted = false; // local — perdu au reload, acceptable

  // Seuil de produits visibles sous lequel on affiche la card sur-mesure.
  // Intent : suggérer le sur-mesure quand la grille filtrée est maigre.
  // Au-dessus du seuil, la card reste cachée pour ne pas spammer.
  var VISIBLE_THRESHOLD = 6;

  /* ─────────────────────────────────────────────
     Rendu
     ───────────────────────────────────────────── */
  function showState(name) {
    if (!els.wrap) return;
    var cards = els.wrap.querySelectorAll('[data-surmesure-state]');
    cards.forEach(function (c) {
      c.hidden = (c.getAttribute('data-surmesure-state') !== name);
    });
  }

  function buildChipsCompact() {
    if (!els.chipsCompact) return;
    els.chipsCompact.innerHTML = '';
    if (!window.sapiProject) return;
    var p = window.sapiProject.get();
    var answers = (p && p.answers) || {};
    var labels  = (p && p.labels)  || {};

    // Ordre canonique des clés pour un affichage stable
    var orderedKeys = ['piece', 'taille', 'taille_escalier', 'eclairage', 'sortie', 'hauteur', 'table', 'style'];
    orderedKeys.forEach(function (k) {
      if (!answers[k]) return;
      var label = labels[k] || answers[k];
      var chip = document.createElement('span');
      chip.className = 'conseiller-chip conseiller-chip--condensed';
      chip.textContent = label;
      els.chipsCompact.appendChild(chip);
    });
  }

  function render() {
    if (submitted) {
      showState('success');
      return;
    }
    if (window.sapiProject && window.sapiProject.hasProject()) {
      buildChipsCompact();
      showState('project');
    } else {
      showState('empty');
    }
  }

  function countVisibleProducts() {
    var products = document.querySelectorAll('.product-card-cinetique');
    var count = 0;
    products.forEach(function (p) {
      if (!p.classList.contains('is-filtered-out')) count++;
    });
    return count;
  }

  function maybeShowOrHide() {
    if (!els.wrap) return;
    // État success : on garde la card visible (confirmation à l'utilisateur)
    if (submitted) {
      els.wrap.hidden = false;
      showState('success');
      return;
    }
    // Sinon : visible UNIQUEMENT si la grille filtrée a peu de résultats
    var visibleCount = countVisibleProducts();
    if (visibleCount > 0 && visibleCount <= VISIBLE_THRESHOLD) {
      els.wrap.hidden = false;
      render();
    } else {
      els.wrap.hidden = true;
    }
  }

  /* ─────────────────────────────────────────────
     Soumission form
     ───────────────────────────────────────────── */
  function submitForm(form) {
    if (!form || !config.ajaxUrl) return;

    // Disable submit button pour éviter double-submit
    var submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.disabled = true;

    var fd = new FormData(form);
    fd.append('action', 'sapi_megafilter_surmesure');
    fd.append('nonce', config.nonce || '');
    fd.append('source_url', window.location.href);

    // Si projet en cours, envoyer le snapshot
    if (window.sapiProject && window.sapiProject.hasProject()) {
      fd.append('project', JSON.stringify(window.sapiProject.get()));
    }

    fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        if (resp && resp.success) {
          submitted = true;
          render();
          // Scroll smooth vers la card success
          if (els.wrap && els.wrap.scrollIntoView) {
            els.wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return;
        }
        // Échec : ré-enable et affiche l'erreur sous le form
        if (submitBtn) submitBtn.disabled = false;
        var fallback = (resp && resp.data && resp.data.fallback) ||
          'L\'envoi a échoué. Tu peux écrire directement à robin@atelier-sapi.fr.';
        showInlineError(form, fallback);
      })
      .catch(function () {
        if (submitBtn) submitBtn.disabled = false;
        showInlineError(form, 'Petit souci de connexion. Tu peux réessayer ou écrire à robin@atelier-sapi.fr.');
      });
  }

  function showInlineError(form, message) {
    var existing = form.querySelector('.conseiller-surmesure-error');
    if (existing) existing.remove();
    var err = document.createElement('p');
    err.className = 'conseiller-surmesure-error';
    err.textContent = message;
    err.style.color = 'var(--color-error)';
    err.style.fontSize = '13px';
    err.style.marginTop = '8px';
    err.style.textAlign = 'center';
    form.appendChild(err);
  }

  /* ─────────────────────────────────────────────
     Init
     ───────────────────────────────────────────── */
  function init() {
    els.wrap = document.querySelector('[data-surmesure-wrap]');
    if (!els.wrap) return; // pas sur /mes-creations/
    els.chipsCompact = els.wrap.querySelector('[data-surmesure-chips]');

    // Affichage initial : count des produits déjà filtrés par sapi-cards-conseiller
    maybeShowOrHide();

    // Délégation submit sur les forms
    els.wrap.addEventListener('submit', function (e) {
      var form = e.target.closest('[data-surmesure-form]');
      if (!form) return;
      e.preventDefault();
      submitForm(form);
    });

    // React aux changements de projet (sapi-cards-conseiller refiltre la grille
    // dans son subscriber qui tourne AVANT le nôtre, donc le compte est à jour)
    if (window.sapiProject && typeof window.sapiProject.subscribe === 'function') {
      window.sapiProject.subscribe(function () {
        maybeShowOrHide();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * Sapi Product Preselect — Pré-sélection de variation sur fiche produit (F2b Phase 3).
 *
 * Deux déclencheurs :
 *  1. AU LOAD : si sapiProject contient une taille (ou taille_escalier), on
 *     pré-sélectionne la variation correspondante dès que le formulaire WC est prêt.
 *  2. ÉVÉNEMENT 'sapi:apply-product-selection' (dispatché par la modale au CTA
 *     "Appliquer cette sélection") : on applique l'ID variation retourné par
 *     l'IA serveur — plus précis que le matching client.
 *
 * Pattern repris de la version éprouvée pré-F1c (assets/cinetique.js, bloc
 * supprimé en F1c) : utilise jQuery wc_variation_form event + setTimeout
 * fallback pour gérer le timing init WC.
 *
 * Échecs silencieux (décision Robin C) : pas d'erreur visible si pas de match.
 */
(function () {
  'use strict';

  // Mapping projet → code de taille S/M/L (mirror PHP sapi_megafilter_project_to_size_code)
  function projectToSizeCode(answers) {
    if (!answers) return '';
    if (answers.piece === 'escalier') {
      if (answers.taille_escalier === 'ouvert') return 'M';
      return ''; // escalier standard : pas de reco
    }
    if (answers.taille === 'petite')  return 'S';
    if (answers.taille === 'moyenne') return 'M';
    if (answers.taille === 'grande')  return 'L';
    return ''; // ne-sais-pas ou rien
  }

  // Trouve l'option dans un <select> dont la value correspond au code S/M/L.
  // Stratégies en cascade : value exacte → préfixe → index (S=0, M=1, L=2).
  function findOptionForSize(select, sizeCode) {
    if (!select || !sizeCode) return null;
    var target = sizeCode.toLowerCase();
    var options = [];
    for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].value) options.push(select.options[i]);
    }
    if (!options.length) return null;

    // 1. Match exact sur value (ex. value="s", "m", "l")
    for (var j = 0; j < options.length; j++) {
      if (options[j].value.toLowerCase() === target) return options[j];
    }
    // 2. Match sur préfixe value (ex. "s-petit", "l-grande")
    for (var k = 0; k < options.length; k++) {
      var v = options[k].value.toLowerCase();
      if (v.indexOf(target + '-') === 0 || v.indexOf(target + '_') === 0) return options[k];
    }
    // 3. Match sur label (ex. "S — Petit", "L (grande)")
    for (var l = 0; l < options.length; l++) {
      var txt = (options[l].textContent || '').trim().toLowerCase();
      if (txt === target || txt.indexOf(target + ' ') === 0 || txt.indexOf(target + ' —') === 0) return options[l];
    }
    // 4. Fallback : index (S=0, M=1, L=2) clamp aux options dispo
    var indexMap = { 's': 0, 'm': 1, 'l': 2 };
    var idx = indexMap[target];
    if (typeof idx === 'number') {
      idx = Math.min(idx, options.length - 1);
      return options[idx];
    }
    return null;
  }

  // Trouve le select de taille parmi les attributs WC (peut être pa_taille, pa_format, etc.)
  function findSizeSelect(form) {
    var root = form || document;
    return root.querySelector('select[name="attribute_pa_taille"]') ||
           root.querySelector('select[name="attribute_pa_format"]') ||
           root.querySelector('select[name^="attribute_pa_taille"]') ||
           root.querySelector('select[name^="attribute_pa_format"]') ||
           null;
  }

  // Applique une option sur un <select> et déclenche les événements WC.
  // Retourne true si l'application a eu lieu.
  function applyOption(select, option) {
    if (!select || !option) return false;
    if (select.value === option.value) return true; // déjà sur la bonne valeur
    select.value = option.value;
    try {
      select.dispatchEvent(new Event('change', { bubbles: true }));
    } catch (e) { /* swallow */ }
    if (typeof jQuery !== 'undefined') {
      jQuery(select).trigger('change');
    }
    return true;
  }

  // Affiche le hint discret "✓ Pré-sélectionné pour votre projet" à côté du label
  // de l'attribut taille. Idempotent (ne duplique pas si déjà présent).
  function showPreselectHint(form) {
    if (!form) return;
    if (form.querySelector('[data-preselect-hint]')) return; // déjà là
    var sizeSelect = findSizeSelect(form);
    if (!sizeSelect) return;
    // Remonter à la ligne <tr> du tableau variations pour trouver le label
    var row = sizeSelect.closest('tr');
    var labelCell = row ? row.querySelector('th.label, td.label') : null;
    if (!labelCell) return;
    var hint = document.createElement('span');
    hint.className = 'conseiller-preselect-hint';
    hint.setAttribute('data-preselect-hint', '');
    hint.textContent = '✓ Pré-sélectionné pour votre projet';
    labelCell.appendChild(hint);
  }

  // Pré-sélection à partir d'un code S/M/L (utilisé au load).
  function preselectFromSizeCode(form, sizeCode) {
    if (!sizeCode) return false;
    var sizeSelect = findSizeSelect(form);
    if (!sizeSelect) return false;
    var option = findOptionForSize(sizeSelect, sizeCode);
    if (!option) return false;
    var applied = applyOption(sizeSelect, option);
    if (applied) showPreselectHint(form);
    return applied;
  }

  // Pré-sélection à partir d'un variation_id (utilisé après "Appliquer cette
  // sélection" depuis la modale). On lit data-product_variations sur le form
  // pour récupérer les attributs de cette variation, puis on applique chaque
  // attribut sur son select correspondant.
  function preselectFromVariationId(form, variationId) {
    if (!form || !variationId) return false;
    var raw = form.getAttribute('data-product_variations');
    if (!raw) return false;
    var variations;
    try { variations = JSON.parse(raw); } catch (e) { return false; }
    if (!Array.isArray(variations)) return false;
    var target = null;
    for (var i = 0; i < variations.length; i++) {
      if (parseInt(variations[i].variation_id, 10) === parseInt(variationId, 10)) {
        target = variations[i];
        break;
      }
    }
    if (!target || !target.attributes) return false;

    var any = false;
    Object.keys(target.attributes).forEach(function (attrName) {
      var attrVal = target.attributes[attrName];
      if (!attrVal) return;
      var sel = form.querySelector('select[name="' + attrName + '"]');
      if (!sel) return;
      // Trouve l'option dont value === attrVal (insensible casse)
      var match = null;
      for (var j = 0; j < sel.options.length; j++) {
        if (sel.options[j].value && sel.options[j].value.toLowerCase() === String(attrVal).toLowerCase()) {
          match = sel.options[j];
          break;
        }
      }
      if (match && applyOption(sel, match)) any = true;
    });
    if (any) showPreselectHint(form);
    return any;
  }

  // Bind sur le form : déclenche au moment où WC l'initialise.
  function whenFormReady(form, cb) {
    if (!form) { cb(); return; }
    if (typeof jQuery !== 'undefined') {
      jQuery(form).on('wc_variation_form', function () { cb(); });
      // Fallback si l'event a déjà fired (page cached, etc.)
      setTimeout(cb, 1000);
    } else {
      setTimeout(cb, 1000);
    }
  }

  function init() {
    var form = document.querySelector('form.variations_form');

    // Listener event modale (toujours actif, même si pas de form initial — la
    // modale peut être ouverte avant qu'on arrive sur cette fonction)
    document.addEventListener('sapi:apply-product-selection', function (e) {
      var detail = (e && e.detail) || {};
      var f = document.querySelector('form.variations_form');
      if (!f) return;
      // Priorité au variation_id fourni par le serveur
      if (detail.variationId) {
        preselectFromVariationId(f, detail.variationId);
        return;
      }
      // Sinon fallback : mapper depuis answers
      var sizeCode = projectToSizeCode(detail.answers || {});
      if (sizeCode) preselectFromSizeCode(f, sizeCode);
    });

    if (!form) return;

    // Pré-sélection au load si sapiProject existe
    if (window.sapiProject && window.sapiProject.hasProject()) {
      var p = window.sapiProject.get();
      var sizeCode = projectToSizeCode(p.answers || {});
      if (sizeCode) {
        whenFormReady(form, function () {
          preselectFromSizeCode(form, sizeCode);
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

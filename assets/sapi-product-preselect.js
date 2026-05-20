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

  // Mapping projet → index dans les options du select taille.
  // Repris du legacy mon-projet.js (pré-F1c) : petite=0, moyenne=1, grande=2,
  // escalier ouvert=1 (décision Robin). Retourne null si pas de reco.
  function projectToTailleIndex(answers) {
    if (!answers) return null;
    if (answers.piece === 'escalier') {
      if (answers.taille_escalier === 'ouvert') return 1;
      return null;
    }
    if (answers.taille === 'petite')  return 0;
    if (answers.taille === 'moyenne') return 1;
    if (answers.taille === 'grande')  return 2;
    return null;
  }

  // Mapping projet → code de taille S/M/L (fallback si l'index dépasse, ou pour
  // les produits dont les valeurs sont sluggées en S/M/L). Sert au matching
  // secondaire dans findOptionForSize après échec du mapping par index.
  function projectToSizeCode(answers) {
    var idx = projectToTailleIndex(answers);
    if (idx === 0) return 'S';
    if (idx === 1) return 'M';
    if (idx === 2) return 'L';
    return '';
  }

  // Mapping projet.style → essence (repris à l'identique du legacy mon-projet.js
  // pré-F1c : moderne → peuplier, ancien → okoume, neutre → rien).
  function projectToEssence(answers) {
    if (!answers || !answers.style) return '';
    if (answers.style === 'moderne') return 'peuplier';
    if (answers.style === 'ancien')  return 'okoume';
    return '';
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

  // Pré-sélection taille : on essaie d'abord par INDEX (pattern éprouvé pré-F1c
  // qui ignore complètement les noms d'options) ; si l'index n'est pas
  // disponible (escalier standard, ne-sais-pas), on retombe sur le matching
  // S/M/L via findOptionForSize.
  function preselectTaille(form, answers) {
    var sizeSelect = findSizeSelect(form);
    if (!sizeSelect) return false;
    var options = [];
    for (var i = 0; i < sizeSelect.options.length; i++) {
      if (sizeSelect.options[i].value) options.push(sizeSelect.options[i]);
    }
    if (!options.length) return false;

    // 1. INDEX-based (pattern éprouvé)
    var idx = projectToTailleIndex(answers);
    if (typeof idx === 'number') {
      var clamped = Math.min(idx, options.length - 1);
      var applied = applyOption(sizeSelect, options[clamped]);
      if (applied) showPreselectHint(form);
      return applied;
    }

    // 2. Fallback : matching S/M/L par value/label/préfixe
    var sizeCode = projectToSizeCode(answers);
    if (!sizeCode) return false;
    var match = findOptionForSize(sizeSelect, sizeCode);
    if (!match) return false;
    var ok = applyOption(sizeSelect, match);
    if (ok) showPreselectHint(form);
    return ok;
  }

  // Pré-sélection de l'essence (matière). Pattern repris du legacy cinetique.js
  // pré-F1c : on cherche d'abord un swatch custom .material-option[data-value="X"]
  // (qu'on clique pour déclencher la logique WC custom), fallback select natif.
  function preselectEssence(form, essenceSlug) {
    if (!form || !essenceSlug) return false;
    // 1. Swatch custom prioritaire
    var swatch = form.querySelector('.material-option[data-value="' + essenceSlug + '"]') ||
                 document.querySelector('.material-option[data-value="' + essenceSlug + '"]');
    if (swatch && !swatch.classList.contains('selected')) {
      try { swatch.click(); return true; } catch (e) { /* fall through au select */ }
    } else if (swatch && swatch.classList.contains('selected')) {
      return true; // déjà bon
    }
    // 2. Fallback : select WC standard attribute_pa_materiau
    var matSelect = form.querySelector('select[name="attribute_pa_materiau"]') ||
                    form.querySelector('select[name^="attribute_pa_materiau"]') ||
                    form.querySelector('select[name="attribute_pa_essence"]');
    if (!matSelect) return false;
    var match = null;
    for (var i = 0; i < matSelect.options.length; i++) {
      if (matSelect.options[i].value && matSelect.options[i].value.toLowerCase() === essenceSlug.toLowerCase()) {
        match = matSelect.options[i];
        break;
      }
    }
    return match ? applyOption(matSelect, match) : false;
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

  // Applique essence puis taille avec délai (pattern éprouvé pré-F1c).
  // L'essence est appliquée immédiatement ; la taille avec 400ms de délai pour
  // laisser WC traiter le change d'essence (qui peut recharger/filtrer les
  // options de taille selon les variations disponibles).
  function preselectAll(form, answers) {
    var essence = projectToEssence(answers);
    if (essence) preselectEssence(form, essence);
    setTimeout(function () {
      preselectTaille(form, answers);
    }, 400);
  }

  function init() {
    var form = document.querySelector('form.variations_form');

    // Listener event modale (toujours actif, même si pas de form initial — la
    // modale peut être ouverte avant qu'on arrive sur cette fonction)
    document.addEventListener('sapi:apply-product-selection', function (e) {
      var detail = (e && e.detail) || {};
      var f = document.querySelector('form.variations_form');
      if (!f) return;
      // Priorité au variation_id fourni par le serveur (couvre taille ET essence
      // si la matière est une variation WC)
      if (detail.variationId) {
        preselectFromVariationId(f, detail.variationId);
        // Essence en plus si elle est gérée hors variations (swatch custom)
        var essence = projectToEssence(detail.answers || {});
        if (essence) preselectEssence(f, essence);
        return;
      }
      // Fallback : essence immédiate + taille avec délai
      preselectAll(f, detail.answers || {});
    });

    if (!form) return;

    // Pré-sélection au load si sapiProject existe
    if (window.sapiProject && window.sapiProject.hasProject()) {
      var p = window.sapiProject.get();
      var answers = p.answers || {};
      whenFormReady(form, function () {
        preselectAll(form, answers);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

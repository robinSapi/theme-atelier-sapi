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

  /* Mapping projet.style → essence.
     ⚠️ CETTE TABLE EXISTE EN TROIS EXEMPLAIRES : ici, `sapi-photo-swap.js`
     (`deriveEssence`) et `sapi-modal-conseiller.js` (`ESSENCE_FROM_STYLE`).
     Elles pilotent respectivement la présélection, la photo affichée et le
     récap de la modale : les faire diverger, c'est montrer une photo de
     peuplier sous un récap qui dit okoumé. **Toute modification ici doit être
     reportée dans les deux autres.**

     `neutre` = « Pas de préférence » → PEUPLIER (décision Robin du 26/08).
     Le visiteur n'a pas refusé de choisir, il a délégué : lui laisser un menu
     vide serait lui rendre la question qu'il vient de nous confier. Sans cette
     valeur par défaut, la pill « C'est le mieux pour ton projet » ne pouvait
     jamais s'afficher pour lui — elle exige que TOUS les attributs soient
     recommandés, et son essence ne l'était pas.

     ⚠️ Un style ABSENT reste sans recommandation, volontairement : quelqu'un
     arrivé par une carte-pièce ou par le texte libre n'a jamais parlé de
     style, et lui choisir un bois serait décider à sa place, pas pour lui. */
  function projectToEssence(answers) {
    if (!answers || !answers.style) return '';
    if (answers.style === 'moderne') return 'peuplier';
    if (answers.style === 'ancien')  return 'okoume';
    if (answers.style === 'neutre')  return 'peuplier';
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

  // Pré-sélection taille : on essaie d'abord par INDEX (pattern éprouvé pré-F1c
  // qui ignore complètement les noms d'options) ; si l'index n'est pas
  // disponible (escalier standard, ne-sais-pas), on retombe sur le matching
  // S/M/L via findOptionForSize.
  /* Quelle option de taille CE projet désigne, sans l'appliquer.
     Séparé de l'application pour que la recommandation puisse être DÉCLARÉE
     avant d'être posée — voir `declarerRecommandation()` plus bas. */
  function cibleTaille(form, answers) {
    var sizeSelect = findSizeSelect(form);
    if (!sizeSelect) return null;
    var options = [];
    for (var i = 0; i < sizeSelect.options.length; i++) {
      if (sizeSelect.options[i].value) options.push(sizeSelect.options[i]);
    }
    if (!options.length) return null;

    // 1. INDEX-based (pattern éprouvé)
    var idx = projectToTailleIndex(answers);
    if (typeof idx === 'number') {
      return { select: sizeSelect, option: options[Math.min(idx, options.length - 1)] };
    }

    // 2. Fallback : matching S/M/L par value/label/préfixe
    var sizeCode = projectToSizeCode(answers);
    if (!sizeCode) return null;
    var match = findOptionForSize(sizeSelect, sizeCode);
    return match ? { select: sizeSelect, option: match } : null;
  }

  function preselectTaille(form, answers) {
    var cible = cibleTaille(form, answers);
    return cible ? applyOption(cible.select, cible.option) : false;
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
    var cible = cibleEssence(form, essenceSlug);
    return cible ? applyOption(cible.select, cible.option) : false;
  }

  /* Quelle option d'essence CE projet désigne, sans l'appliquer. Même raison
     que `cibleTaille` : déclarer avant de poser. */
  function cibleEssence(form, essenceSlug) {
    if (!form || !essenceSlug) return null;
    var matSelect = form.querySelector('select[name="attribute_pa_materiau"]') ||
                    form.querySelector('select[name^="attribute_pa_materiau"]') ||
                    form.querySelector('select[name="attribute_pa_essence"]');
    if (!matSelect) return null;
    for (var i = 0; i < matSelect.options.length; i++) {
      if (matSelect.options[i].value && matSelect.options[i].value.toLowerCase() === essenceSlug.toLowerCase()) {
        return { select: matSelect, option: matSelect.options[i] };
      }
    }
    return null;
  }

  /* ═══════════════════════════════════════════════════════════
     DÉCLARER LA RECOMMANDATION — l'étiquette posée sur la pièce
     ═══════════════════════════════════════════════════════════
     ⚠️ La pill (sapi-help-pill.js) doit savoir quelle version est la
     recommandée pour dire « C'est le mieux pour ton projet ». Elle a d'abord
     essayé de le DEVINER, en observant les événements que ce fichier émet.
     Ça échoue dans un cas courant et parfaitement silencieux : quand la valeur
     est DÉJÀ la bonne, `applyOption` sort sans rien émettre (ligne « déjà sur
     la bonne valeur »). Il n'y a alors rien à observer, et la pill se taisait
     devant la recommandation affichée à l'écran. Mesuré, reproduit.

     On l'écrit donc noir sur blanc, sur le formulaire lui-même.

     Trois règles à ne pas perdre :
       • on déclare la `value` de l'OPTION retenue, jamais un slug recalculé —
         une divergence d'accent ou de casse ferait échouer la comparaison sans
         que rien ne plante ;
       • la carte est REMPLACÉE, jamais fusionnée : sinon une taille
         recommandée par d'anciennes réponses survivrait à un questionnaire qui
         répond désormais « je ne sais pas » ;
       • on écrit EN UNE FOIS, avant d'appliquer. Ce fichier pose l'essence
         tout de suite et la taille 400 ms plus tard : une carte remplie au fil
         de l'eau serait incomplète pendant ces 400 ms, et la pill clignoterait
         à chaque chargement. */
  function declarerRecommandation(form, cibles) {
    if (!form) return;
    var carte = {};
    for (var i = 0; i < cibles.length; i++) {
      var c = cibles[i];
      if (c && c.select && c.select.name && c.option) carte[c.select.name] = c.option.value;
    }
    try {
      form.setAttribute('data-sapi-recommandation', JSON.stringify(carte));
    } catch (e) { /* swallow */ }
    /* La pill peut être initialisée avant ou après nous : un événement seul se
       raterait. L'attribut est lisible à tout moment, l'événement n'est qu'un
       réveil pour éviter d'attendre le prochain geste du visiteur. */
    try {
      document.dispatchEvent(new CustomEvent('sapi:recommandation-declaree'));
    } catch (e) { /* swallow */ }
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

    /* Même discipline que `preselectAll` : on repère TOUTES les cibles, on
       déclare la carte en une fois, puis on applique. Sans ça, ce chemin-là
       poserait la bonne version sans jamais l'annoncer à la pill. */
    var cibles = [];
    Object.keys(target.attributes).forEach(function (attrName) {
      var attrVal = target.attributes[attrName];
      if (!attrVal) return;
      var sel = form.querySelector('select[name="' + attrName + '"]');
      if (!sel) return;
      // Trouve l'option dont value === attrVal (insensible casse)
      for (var j = 0; j < sel.options.length; j++) {
        if (sel.options[j].value && sel.options[j].value.toLowerCase() === String(attrVal).toLowerCase()) {
          cibles.push({ select: sel, option: sel.options[j] });
          break;
        }
      }
    });

    declarerRecommandation(form, cibles);

    var any = false;
    cibles.forEach(function (c) {
      if (applyOption(c.select, c.option)) any = true;
    });
    return any;
  }

  // Bind sur le form : déclenche au moment où WC l'initialise.
  /* Le `cb` reçoit `true` quand c'est un RATTRAPAGE — c'est-à-dire le filet à
     1 s, ou une seconde émission de l'événement d'init. Un rattrapage ne doit
     remplir que ce qui est encore vide : à ce moment-là, le visiteur a pu
     choisir lui-même, et lui reprendre son choix est le comportement qu'on
     vient de corriger. */
  function whenFormReady(form, cb) {
    if (!form) { cb(false); return; }
    var dejaPasse = false;
    function une_fois() {
      var rattrapage = dejaPasse;
      dejaPasse = true;
      cb(rattrapage);
    }
    if (typeof jQuery !== 'undefined') {
      jQuery(form).on('wc_variation_form', une_fois);
      // Fallback si l'event a déjà fired (page cached, etc.)
      setTimeout(une_fois, 1000);
    } else {
      setTimeout(une_fois, 1000);
    }
  }

  // Applique essence puis taille avec délai (pattern éprouvé pré-F1c).
  // L'essence est appliquée immédiatement ; la taille avec 400ms de délai pour
  // laisser WC traiter le change d'essence (qui peut recharger/filtrer les
  // options de taille selon les variations disponibles).
  function preselectAll(form, answers, opts) {
    opts = opts || {};
    var essence = projectToEssence(answers);
    var cE = essence ? cibleEssence(form, essence) : null;
    var cT = cibleTaille(form, answers);

    // 1. Déclarer les DEUX d'un coup, avant d'appliquer quoi que ce soit.
    declarerRecommandation(form, [cE, cT]);

    /* 2. Appliquer. `seulementVides` protège le choix du visiteur : ce fichier
       tourne DEUX fois (l'événement d'init de WooCommerce, plus un filet à 1 s),
       et la seconde passe écrasait sans regarder qui avait écrit. Mesuré : entre
       ~2,2 s et ~3,0 s après l'arrivée sur la page, quelqu'un qui venait de
       choisir une taille se la voyait reprendre sans avoir rien touché.
       La seconde passe ne remplit donc plus que ce qui est encore vide. */
    function poser(cible, fn) {
      if (!cible) return;
      if (opts.seulementVides && cible.select && cible.select.value) return;
      fn();
    }
    poser(cE, function () { if (essence) preselectEssence(form, essence); });
    setTimeout(function () {
      poser(cT, function () { preselectTaille(form, answers); });
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
      whenFormReady(form, function (rattrapage) {
        preselectAll(form, answers, { seulementVides: rattrapage });
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

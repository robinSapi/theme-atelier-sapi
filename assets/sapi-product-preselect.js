/**
 * Sapi Product Preselect — Pré-sélection de variation sur fiche produit (F2b Phase 3).
 *
 * Deux déclencheurs :
 *  1. AU LOAD : dès que le formulaire WooCommerce est prêt, on pose la taille et
 *     l'essence que le projet mémorisé désigne.
 *  2. ÉVÉNEMENT 'sapi:apply-product-selection', émis par la modale au clic sur
 *     « Appliquer cette sélection ».
 *
 * Et une TROISIÈME chose, qui n'applique rien : à chaque changement du projet,
 * on réécrit l'étiquette `data-sapi-recommandation` sur le formulaire. C'est
 * elle que lit la pill pour dire « C'est le mieux pour ton projet ».
 *
 * ⚠️ CE FICHIER NE CONTIENT AUCUNE TABLE. `style → essence` et
 * `taille → intention` vivent dans `sapi-project.js`, le seul fichier chargé
 * partout où la question se pose. Elles ont existé en trois exemplaires, avec
 * des divergences réelles — dont celle qui faisait appliquer une taille et en
 * annoncer une autre sur le même écran. Ne pas les réintroduire ici.
 *
 * Ce fichier sait UNE chose que personne d'autre ne sait : combien de tailles a
 * ce modèle précis. C'est `resoudreTaille()` qui traduit l'intention en option.
 *
 * Échecs silencieux (décision Robin C) : pas d'erreur visible si pas de match.
 */
(function () {
  'use strict';

  /* L'INTENTION vient de `sapi-project.js`, source unique. On ne la recalcule
     pas ici : elle a existé en trois versions incohérentes, et c'est ce qui
     faisait que le site appliquait une taille tout en en annonçant une autre
     sur le même écran.
     ⚠️ PAS de repli sur une table locale si la fonction manque : ce serait le
     repli silencieux garanti — deux tables qui divergent sans que rien ne
     plante. Mieux vaut que ça se voie une fois au déploiement (vider le cache)
     que ça mente tous les jours. */
  function intentionTaille(answers) {
    return (window.sapiProject && window.sapiProject.tailleIntention)
      ? window.sapiProject.tailleIntention(answers)
      : null;
  }

  /* La traduction intention → option vit aussi dans `sapi-project.js` : le
     récap de la modale en a besoin au moment où l'étiquette n'est pas encore
     rafraîchie (la modale met les notifications en pause). On partage le
     CALCUL, pas seulement le résultat. */
  function resoudreTaille(options, intention) {
    return (window.sapiProject && window.sapiProject.resoudreTaille)
      ? window.sapiProject.resoudreTaille(options, intention)
      : null;
  }

  /* La table `style → essence` vit dans `sapi-project.js`, source unique.
     Elle a existé ici, dans `sapi-photo-swap.js` et dans
     `sapi-modal-conseiller.js` — trois copies à modifier à la main, donc trois
     occasions de diverger, donc une photo de peuplier sous un récap annonçant
     l'okoumé. Ne pas la réintroduire, même « juste pour un repli ». */
  function projectToEssence(answers) {
    return (window.sapiProject && window.sapiProject.styleToEssence)
      ? window.sapiProject.styleToEssence(answers)
      : '';
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
    var option = resoudreTaille(options, intentionTaille(answers));
    return option ? { select: sizeSelect, option: option } : null;
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
  /* Les deux cibles d'un projet, sur ce produit. Séparé pour pouvoir REDÉCLARER
     sans appliquer — voir `rafraichirEtiquette()`. */
  function ciblesDuProjet(form, answers) {
    var essence = projectToEssence(answers);
    return {
      essence: essence,
      cE: essence ? cibleEssence(form, essence) : null,
      cT: cibleTaille(form, answers)
    };
  }

  /* ⚠️ L'ÉTIQUETTE DOIT SUIVRE LE PROJET, PAS SEULEMENT LE CHARGEMENT.
     Elle n'était écrite qu'au démarrage. Chemin du défaut, reproduit :
     le visiteur arrive avec « salon / grand », la pill dit « C'est le mieux
     pour ton projet » ; il rouvre la modale, refait son projet en « petit »,
     puis ferme avec LA CROIX au lieu de cliquer « Appliquer ». Les menus n'ont
     pas bougé, l'étiquette non plus — et la pill continue d'affirmer que la
     version affichée est la meilleure pour un projet qui n'existe plus.
     Rien ne plante. C'est faux en silence, exactement la maladie qui avait fait
     abandonner la version précédente de la pill.
     On réécrit donc l'étiquette à chaque changement du projet, SANS RIEN
     APPLIQUER : aucun menu touché, aucun `change` émis, donc aucun risque de
     reprendre au visiteur le choix qu'il vient de faire. La pill s'éteint
     d'elle-même dès que l'écran cesse de correspondre.
     L'invariant devient énonçable : « l'étiquette dit toujours ce que le projet
     d'aujourd'hui recommande ; on ne la pose sur les menus qu'au chargement et
     quand le visiteur clique Appliquer. » */
  function rafraichirEtiquette() {
    var form = document.querySelector('form.variations_form');
    if (!form) return;
    var answers = {};
    try {
      if (window.sapiProject && window.sapiProject.hasProject && window.sapiProject.hasProject()) {
        answers = (window.sapiProject.get() || {}).answers || {};
      }
    } catch (e) { /* projet illisible → étiquette vide, la pill se taira */ }
    var c = ciblesDuProjet(form, answers);
    declarerRecommandation(form, [c.cE, c.cT]);
  }

  function preselectAll(form, answers, opts) {
    opts = opts || {};
    var c = ciblesDuProjet(form, answers);
    var essence = c.essence;
    var cE = c.cE;
    var cT = c.cT;

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
      /* Il existait ici une branche prioritaire « variation_id fourni par le
         serveur ». Retirée : AUCUN émetteur n'a jamais posé `variationId` —
         l'endpoint qui devait le fournir n'existe plus dans le code. La branche
         et ses 46 lignes ne pouvaient pas s'exécuter, mais l'en-tête du fichier
         affirmait qu'elles étaient le chemin « plus précis ». */
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

    /* Le projet peut changer sans qu'on applique quoi que ce soit : questionnaire
       refait puis fermé par la croix, « Recommencer mon projet », modification
       depuis un autre onglet. L'étiquette doit suivre, sinon la pill parle d'un
       projet périmé. On ne fait que RÉÉCRIRE, jamais poser. */
    try {
      if (window.sapiProject && window.sapiProject.subscribe) {
        window.sapiProject.subscribe(rafraichirEtiquette);
      }
    } catch (e) { /* swallow */ }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * Sapi Project — Module de persistance "Mon projet" (F2a)
 *
 * Source unique de vérité pour le projet en cours du visiteur, stocké dans
 * localStorage.sapiProject. Lu par les cards Conseiller, la modale tunnel
 * (Phase 3), la fiche produit (F2b) et le filtrage grille.
 *
 * Format stocké :
 * {
 *   answers:  { piece: 'salon', taille: 'spacieuse', ... },
 *   labels:   { piece: 'Salon / Salle à manger', ... },
 *   created_at: 1716000000,
 *   updated_at: 1716000123,
 *   session_id: 'mfs_xxx'  // optionnel
 * }
 *
 * Phase 1 : seul le câblage ?piece=X est branché (sauvegarde silencieuse).
 * Les cards Conseil/Mon projet sont injectées Phase 2.
 */
(function () {
  'use strict';

  var STORAGE_KEY = 'sapiProject';
  var listeners = [];

  /* ⚠️ LES CLÉS DU QUESTIONNAIRE, DANS L'ORDRE CANONIQUE — source unique.
     Transmises par le serveur (`SAPI_PROJECT.ordreCles`), jamais recopiées.
     Elles servent à écrire l'adresse toujours dans le même ordre, à calculer
     la signature de déduplication, et à effacer proprement toutes les clés.
     Repli sur `['piece']` : le comportement d'avant, jamais faux, seulement
     incomplet — mieux que d'inventer une liste qui divergerait. */
  function clesProjet() {
    var cfg = window.SAPI_PROJECT || {};
    return (cfg.ordreCles && cfg.ordreCles.length) ? cfg.ordreCles : ['piece'];
  }

  /* Écrit un jeu de réponses dans une URL : efface d'abord TOUTES les clés du
     questionnaire, puis réécrit celles qui ont une valeur, dans l'ordre. Les
     paramètres étrangers (utm, fbclid) sont préservés.
     ⚠️ Effacer avant d'écrire est ce qui empêche les critères d'une pièce
     précédente de survivre à un changement de projet. */
  function ecrireProjetDansUrl(url, answers) {
    var cles = clesProjet();
    cles.forEach(function (k) { url.searchParams.delete(k); });
    cles.forEach(function (k) { if (answers && answers[k]) url.searchParams.set(k, answers[k]); });
    return url;
  }

  /* ─────────────────────────────────────────────
     Helpers localStorage (tolérant aux erreurs)
     ───────────────────────────────────────────── */
  function readRaw() {
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      var parsed = JSON.parse(raw);
      if (!parsed || typeof parsed !== 'object') return null;
      if (!parsed.answers || typeof parsed.answers !== 'object') return null;
      return parsed;
    } catch (e) {
      return null;
    }
  }

  function writeRaw(project) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(project));
      return true;
    } catch (e) {
      return false;
    }
  }

  function clearRaw() {
    try {
      window.localStorage.removeItem(STORAGE_KEY);
      return true;
    } catch (e) {
      return false;
    }
  }

  /* ─────────────────────────────────────────────
     API publique
     ───────────────────────────────────────────── */

  /** Retourne le projet (toujours un objet, jamais null). */
  function get() {
    var p = readRaw();
    if (!p) {
      return { answers: {}, labels: {}, created_at: null, updated_at: null, session_id: null, advice_text: null, action: null, contact_kind: null, contact_subject: '', contact_message: '' };
    }
    if (!p.labels || typeof p.labels !== 'object') p.labels = {};
    if (!('advice_text' in p)) p.advice_text = null;
    // Round 3 — Lot C1 : champs contact (action="contact" + kind/subject/message)
    if (!('action' in p)) p.action = null;
    if (!('contact_kind' in p)) p.contact_kind = null;
    if (!('contact_subject' in p)) p.contact_subject = '';
    if (!('contact_message' in p)) p.contact_message = '';
    return p;
  }

  /** True si le projet contient au moins une réponse. */
  function hasProject() {
    var p = readRaw();
    if (!p || !p.answers) return false;
    for (var k in p.answers) {
      if (Object.prototype.hasOwnProperty.call(p.answers, k)) return true;
    }
    return false;
  }

  /** Récupère la réponse pour une clé (ex. 'piece'). */
  function getAnswer(key) {
    var p = readRaw();
    return (p && p.answers && p.answers[key]) || null;
  }

  /** Récupère le label humain pour une clé. */
  function getLabel(key) {
    var p = readRaw();
    return (p && p.labels && p.labels[key]) || null;
  }

  /**
   * Remplace entièrement le projet.
   * @param {Object} answers  { piece: 'salon', ... }
   * @param {Object} [labels] { piece: 'Salon / Salle à manger', ... }
   * @param {Object} [extra]  { session_id, advice_text } — advice_text est
   *                          remis à null si non fourni (sortie d'un parcours
   *                          modale = nouveau projet ≠ ancien advice).
   */
  function set(answers, labels, extra) {
    if (!answers || typeof answers !== 'object') return false;
    var now = Math.floor(Date.now() / 1000);
    var existing = readRaw();
    var project = {
      answers: {},
      labels: {},
      created_at: existing && existing.created_at ? existing.created_at : now,
      updated_at: now,
      session_id: (extra && extra.session_id) || (existing && existing.session_id) || null,
      advice_text: (extra && typeof extra.advice_text === 'string' && extra.advice_text)
                     ? extra.advice_text
                     : null,
      // Round 3 — Lot C1 : set() = remplace entièrement le projet (sortie
      // modale), donc on remet à zéro l'état contact aussi.
      action: null,
      contact_kind: null,
      contact_subject: '',
      contact_message: '',
    };
    Object.keys(answers).forEach(function (k) {
      var v = answers[k];
      if (typeof v === 'string' && v) project.answers[k] = v;
    });
    if (labels && typeof labels === 'object') {
      Object.keys(labels).forEach(function (k) {
        var v = labels[k];
        if (typeof v === 'string' && v) project.labels[k] = v;
      });
    }
    var ok = writeRaw(project);
    if (ok) notify();
    return ok;
  }

  /**
   * Fusionne un patch dans le projet existant. `null` supprime une clé.
   * Invalidation auto de `advice_text` quand les `answers` changent : un
   * advice IA précédent ne reflète plus le nouveau projet, donc on l'écarte
   * pour repartir sur le texte générique en attendant un nouveau parcours
   * (corrige le bug 19/05 où la card "Mon projet" gardait un advice
   * mentionnant "cuisine" alors que le visiteur avait changé pour "salon").
   * @param {Object} patchAnswers  { taille: 'grande', sortie: null }
   * @param {Object} [patchLabels] { taille: 'Grande pièce' }
   */
  function update(patchAnswers, patchLabels) {
    if (!patchAnswers || typeof patchAnswers !== 'object') return false;
    var p = readRaw();
    var now = Math.floor(Date.now() / 1000);
    if (!p) {
      p = { answers: {}, labels: {}, created_at: now, updated_at: now, session_id: null };
    }
    if (!p.labels) p.labels = {};

    // Snapshot avant patch pour détecter un changement effectif des answers
    var beforeAnswersJson = JSON.stringify(p.answers || {});

    Object.keys(patchAnswers).forEach(function (k) {
      var v = patchAnswers[k];
      if (v === null) {
        delete p.answers[k];
        delete p.labels[k];
      } else if (typeof v === 'string' && v) {
        p.answers[k] = v;
      }
    });

    if (patchLabels && typeof patchLabels === 'object') {
      Object.keys(patchLabels).forEach(function (k) {
        var v = patchLabels[k];
        if (v === null) {
          delete p.labels[k];
        } else if (typeof v === 'string' && v) {
          p.labels[k] = v;
        }
      });
    }

    // Si les answers ont vraiment changé, on invalide l'advice_text précédent
    // (il référence l'ancien projet). setAdviceText() écrit en direct via
    // writeRaw — il ne passe pas par update() — donc pas de risque de boucle.
    var afterAnswersJson = JSON.stringify(p.answers || {});
    if (beforeAnswersJson !== afterAnswersJson) {
      p.advice_text = null;
      // Round 3 — Lot C1 : invalide aussi l'état contact (le routing IA
      // précédent référençait l'ancien projet — un changement d'answers
      // peut faire passer de "contact" à "standard" ou changer le kind).
      // setContactState écrit en direct via writeRaw — pas de boucle.
      p.action = null;
      p.contact_kind = null;
      p.contact_subject = '';
      p.contact_message = '';
    }

    p.updated_at = now;
    var ok = writeRaw(p);
    if (ok) notify();
    return ok;
  }

  function clear() {
    // Round 3 — Lot C1 : clear l'état contact aussi (action + kind/subject/message
    // sont stockés au même niveau que answers dans le storage)
    var ok = clearRaw();
    /* Nettoyer l'URL de TOUTES les clés du questionnaire, sinon le projet
       « effacé » revient au premier rechargement.
       ⚠️ On n'effaçait que `?piece=`. Depuis que l'adresse porte les six autres
       critères, « Recommencer » laissait derrière lui `sortie=mur&taille=grande`
       — des critères orphelins, sans pièce, que le serveur relit au
       rechargement. Le visiteur croyait avoir tout effacé. */
    try {
      var url = new URL(window.location.href);
      var avant = url.search;
      clesProjet().forEach(function (k) { url.searchParams.delete(k); });
      if (url.search !== avant) {
        var newUrl = url.pathname + (url.search || '') + (url.hash || '');
        window.history.replaceState({}, '', newUrl);
      }
    } catch (e) { /* silencieux */ }
    if (ok) notify();
    return ok;
  }

  /**
   * Round 3 — Lot C1 : enregistre l'état contact renvoyé par l'IA
   * (action=contact + contact_kind/subject/message). Écrit en direct via
   * writeRaw pour ne pas déclencher l'invalidation d'advice_text par update().
   * Passer null pour clear l'état contact.
   */
  function setContactState(payload) {
    var p = readRaw();
    var now = Math.floor(Date.now() / 1000);
    if (!p) {
      p = { answers: {}, labels: {}, created_at: now, updated_at: now, session_id: null };
    }
    if (!p.labels) p.labels = {};
    if (!payload) {
      p.action = null;
      p.contact_kind = null;
      p.contact_subject = '';
      p.contact_message = '';
    } else {
      p.action = (payload.action === 'contact') ? 'contact' : null;
      var validKinds = ['pro', 'sur-mesure', 'simple'];
      p.contact_kind = (typeof payload.contact_kind === 'string' && validKinds.indexOf(payload.contact_kind) !== -1)
        ? payload.contact_kind : null;
      p.contact_subject = (typeof payload.contact_subject === 'string') ? payload.contact_subject : '';
      p.contact_message = (typeof payload.contact_message === 'string') ? payload.contact_message : '';
    }
    p.updated_at = now;
    var ok = writeRaw(p);
    if (ok) notify();
    return ok;
  }

  /**
   * Définit le texte conseil IA (advice_text). Passer null pour l'effacer.
   * Utilisé par la modale à la sortie d'un parcours abouti (F2a-bis).
   */
  function setAdviceText(text) {
    var p = readRaw();
    var now = Math.floor(Date.now() / 1000);
    if (!p) {
      p = { answers: {}, labels: {}, created_at: now, updated_at: now, session_id: null, advice_text: null };
    }
    if (!p.labels) p.labels = {};
    p.advice_text = (typeof text === 'string' && text) ? text : null;
    p.updated_at = now;
    var ok = writeRaw(p);
    if (ok) notify();
    return ok;
  }

  /* ─────────────────────────────────────────────
     Observateurs (cards qui doivent se redessiner
     quand le projet change dans la même session)
     ───────────────────────────────────────────── */
  function subscribe(fn) {
    if (typeof fn !== 'function') return function () {};
    listeners.push(fn);
    return function unsubscribe() {
      var i = listeners.indexOf(fn);
      if (i !== -1) listeners.splice(i, 1);
    };
  }

  // Notifications pause/resume : appelé par la modale au open/close pour
  // éviter que les cards en arrière-plan refilter à chaque réponse cliquée
  // (sinon flashs visibles à travers l'overlay). À resumeNotifications(),
  // on flush une seule notification finale si des update ont eu lieu.
  var notifyPaused = false;
  var pendingNotify = false;

  function notify() {
    if (notifyPaused) {
      pendingNotify = true;
      return;
    }
    var snapshot = get();
    for (var i = 0; i < listeners.length; i++) {
      try { listeners[i](snapshot); } catch (e) { /* swallow */ }
    }
  }

  function pauseNotifications() {
    notifyPaused = true;
  }

  function resumeNotifications() {
    notifyPaused = false;
    if (pendingNotify) {
      pendingNotify = false;
      notify();
    }
  }

  /* ─────────────────────────────────────────────
     Round 2 — 3.2 : visibility helpers centralisés
     Mirror de inc/guide-data.php (côté PHP : sapi_guide_get_steps + même
     algorithme). Avant Round 2, 3 implémentations dupliquées de
     cleanInvisibleAnswers existaient (sapi-modal-conseiller.js,
     sapi-cards-conseiller.js, inc/guide-data.php). Source unique JS ici.
     ───────────────────────────────────────────── */
  function computeVisibleStepIds(answers, steps) {
    var visible = [];
    if (!Array.isArray(steps)) return visible;
    for (var i = 0; i < steps.length; i++) {
      var step = steps[i];
      var vis = step.visibility;
      if (vis === 'always') { visible.push(step.id); continue; }
      if (typeof vis !== 'object' || vis === null) continue;

      if (vis._or) {
        var orMatch = false;
        for (var g = 0; g < vis._or.length; g++) {
          var group = vis._or[g];
          var groupOk = true;
          for (var k in group) {
            if (!group.hasOwnProperty(k)) continue;
            var ans = answers[k];
            if (!ans || group[k].indexOf(ans) === -1) { groupOk = false; break; }
          }
          if (groupOk) { orMatch = true; break; }
        }
        if (orMatch) visible.push(step.id);
      } else {
        var show = true;
        for (var key in vis) {
          if (!vis.hasOwnProperty(key)) continue;
          var a = answers[key];
          if (!a || vis[key].indexOf(a) === -1) { show = false; break; }
        }
        if (show) visible.push(step.id);
      }
    }
    return visible;
  }

  function cleanInvisibleAnswersImpl(answers, steps) {
    var visible = computeVisibleStepIds(answers, steps);
    var clean = {};
    for (var sid in answers) {
      if (answers.hasOwnProperty(sid) && visible.indexOf(sid) !== -1) {
        clean[sid] = answers[sid];
      }
    }
    return clean;
  }

  /* ─────────────────────────────────────────────
     Câblage initial : ?piece=X → projet partiel
     Mirror de la validation existante côté mega-filtre.js.
     ───────────────────────────────────────────── */
  var VALID_PIECES = {
    cuisine:  'Cuisine',
    bureau:   'Bureau / Atelier',
    salon:    'Salon / Salle à manger',
    chambre:  'Chambre',
    'chambre-enfant': 'Chambre enfant',
    entree:   'Entrée / Couloir',
    escalier: 'Cage d\'escalier',
  };

  /* ⚠️ L'ADRESSE FAIT AUTORITÉ À L'ARRIVÉE — mais seulement à l'arrivée.
     Le projet mémorisé sert ailleurs (fiche produit, présélection, pastilles).
     Ici, ce que le visiteur vient d'ouvrir est ce qu'il doit voir.

     Trois défauts corrigés d'un coup :
       • on n'ingérait QUE la pièce. Depuis que l'adresse peut porter les six
         autres critères, le projet et l'écran divergeaient immédiatement — et
         c'est le projet qui gagnait, en écrasant la sélection du lien reçu ;
       • « même pièce » sortait sans rien faire : un lien décrivant un salon
         avec sortie murale était donc ignoré si le destinataire avait déjà un
         salon en mémoire ;
       • « autre pièce » effaçait TOUT puis n'écrivait que la pièce. L'effacement
         emportait le conseil IA mémorisé et l'état contact, qui ne sont pas des
         réponses — et il créait une fenêtre où le projet ne contenait que la
         pièce, exactement l'état qui déclenchait la mauvaise requête.
     On remplace donc en UN SEUL geste.

     ⚠️ ON N'INGÈRE PAS L'URL, ON INGÈRE CE QUE LE SERVEUR EN A RETENU
     (`SAPI_IMMERSION.answers`, déjà validé contre le questionnaire). Re-valider
     ici imposerait une quatrième copie de la whitelist en JS — `VALID_PIECES`
     est déjà la troisième copie de la seule liste des pièces. */
  function ingestQueryParams() {
    try {
      var params = new URLSearchParams(window.location.search);
      var piece = params.get('piece');
      if (!piece || !Object.prototype.hasOwnProperty.call(VALID_PIECES, piece)) return;

      var imm = window.SAPI_IMMERSION || {};
      var reponsesUrl = (imm.answers && imm.answers.piece === piece) ? imm.answers : { piece: piece };

      var existingPiece = getAnswer('piece');
      var pieceChange = existingPiece && existingPiece !== piece;
      var urlRiche = Object.keys(reponsesUrl).length > 1;

      /* ⚠️ NE RIEN FAIRE SI L'ADRESSE NE DIT RIEN DE NOUVEAU.
         `set()` remet `advice_text` et l'état contact à zéro — c'est voulu pour
         un nouveau projet, mais désastreux sur un simple RECHARGEMENT : le
         conseil de Robin, déjà écrit et affiché, disparaîtrait au profit de la
         phrase générique, à chaque F5 et à chaque retour arrière.
         On compare donc les réponses avant d'écrire. */
      var actuel = get().answers || {};
      var identique = true;
      var toutesCles = Object.keys(reponsesUrl).concat(Object.keys(actuel));
      for (var i = 0; i < toutesCles.length; i++) {
        if (actuel[toutesCles[i]] !== reponsesUrl[toutesCles[i]]) { identique = false; break; }
      }
      if (identique) return;

      // Rien de neuf : même pièce, et l'adresse n'apporte aucun autre critère.
      if (!pieceChange && !urlRiche && existingPiece === piece) return;

      if (pieceChange || urlRiche) {
        /* `set` remplace le projet en entier, sans passer par un état
           intermédiaire vide. Les libellés sont reconstruits pour la pièce ;
           les autres viendront du questionnaire au prochain passage dans la
           modale, ce qui est sans conséquence : rien ne les affiche ici. */
        var labels = {};
        if (VALID_PIECES[piece]) labels.piece = VALID_PIECES[piece];
        set(reponsesUrl, labels);
        return;
      }
      // Pas de projet existant, adresse à une seule clé → ingestion classique.
      update({ piece: piece }, { piece: VALID_PIECES[piece] });
    } catch (e) {
      // URLSearchParams indisponible → silencieux
    }
  }

  /* ─────────────────────────────────────────────
     Sync inter-onglets (storage event)
     ───────────────────────────────────────────── */
  window.addEventListener('storage', function (e) {
    if (e.key === STORAGE_KEY) notify();
  });

  /* ─────────────────────────────────────────────
     Init
     ───────────────────────────────────────────── */
  function init() {
    ingestQueryParams();
  }

  /* ⚠️ ON ATTEND `DOMContentLoaded` DANS TOUS LES CAS SAUF « complete ».
     `ingestQueryParams` lit `SAPI_IMMERSION`, posé par un script chargé APRÈS
     celui-ci — il faut donc que tous les scripts aient tourné.
     Le test naïf `readyState === 'loading'` ne suffit PAS : **en production,
     Autoptimize ajoute `defer` à tous les scripts**, et dans un script différé
     `readyState` vaut déjà « interactive ». On tombait donc dans le `else`,
     `init()` partait immédiatement, avant que `SAPI_IMMERSION` n'existe, et
     l'ingestion retombait sur la pièce seule : **le lien partagé perdait ses
     critères, en production uniquement.**
     Et le site de test n'a PAS Autoptimize : la recette ne pouvait pas le
     montrer. Robin aurait validé un acquis qu'il n'avait pas.
     « interactive » précède toujours `DOMContentLoaded`, l'écouteur se
     déclenche donc bien. Seul « complete » signifie que l'événement est déjà
     passé. */
  if (document.readyState === 'complete') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }

  /* ═══════════════════════════════════════════════════════════
     TRADUIRE UN PROJET — les deux seules tables qui comptent
     ═══════════════════════════════════════════════════════════
     ⚠️ ELLES VIVENT ICI PARCE QUE C'EST LE SEUL FICHIER CHARGÉ PARTOUT où la
     question se pose : fiche produit, page de sélection, catégories, accueil.
     `sapi-product-preselect.js` n'existe que sur la fiche produit ; y mettre la
     table obligerait `sapi-photo-swap.js`, qui tourne ailleurs, à s'appuyer sur
     un fichier absent — et il retomberait silencieusement sur « aucune essence ».

     La phrase à retenir : **la mémoire du projet sait le traduire ; personne ne
     le recalcule.**

     Historique : `style → essence` a existé en TROIS exemplaires et `taille →
     intention` en TROIS versions incohérentes, cause du défaut de l'escalier
     (le site appliquait une taille et en annonçait une autre sur le même écran).

     ⚠️ Ces fonctions sont PURES : aucun DOM, aucun produit. Elles disent une
     INTENTION. C'est à celui qui connaît les options du produit de la traduire
     en option réelle — voir `resoudreTaille()` dans sapi-product-preselect.js. */

  /* Décisions Robin du 26/08 — dans les trois cas, le site DÉCIDE plutôt que de
     laisser le visiteur dans le vide :
       • « Pas de préférence » de style → peuplier ;
       • « Je ne sais pas » pour la taille → taille moyenne ;
       • escalier standard → taille moyenne ;
       • escalier ouvert → LA PLUS GRANDE taille disponible (pas la troisième :
         sur un modèle qui en a quatre, c'est bien la dernière). */
  function styleToEssence(answers) {
    if (!answers || !answers.style) return ''; // jamais interrogé ≠ sans préférence
    /* ⚠️ LA TABLE VIENT DU RÉGLAGE DE ROBIN (`SAPI_PROJECT.styleEssence`),
       plus d'une liste écrite ici. Son panneau d'administration proposait ce
       réglage depuis toujours et personne ne le lisait : il pouvait changer
       l'essence associée à un style, enregistrer, et le site n'en tenait aucun
       compte.
       ⚠️ PAS DE REPLI SUR UNE TABLE LOCALE si le réglage manque. Deux tables
       qui divergent sans que rien ne plante, c'est le défaut qu'on vient de
       supprimer — mieux vaut aucune essence recommandée qu'une essence qui
       contredit le moteur. */
    var cfg = window.SAPI_PROJECT || {};
    var map = cfg.styleEssence || {};
    return map[answers.style] || '';
  }

  /* Renvoie 'petite' | 'moyenne' | 'grande' | 'max' | null.
     'max' = la dernière option, quel que soit le nombre de tailles du modèle.
     'grande' = la troisième si elle existe, sinon la dernière. */
  function tailleIntention(answers) {
    if (!answers) return null;
    if (answers.piece === 'escalier') {
      if (answers.taille_escalier === 'ouvert') return 'max';
      if (answers.taille_escalier === 'standard') return 'moyenne';
      /* Escalier SANS précision : rien, comme un salon sans taille. Le visiteur
         arrivé par une carte-pièce n'a répondu qu'à une question ; lui
         recommander une taille serait décider sur la foi d'un seul mot, et ce
         serait asymétrique avec toutes les autres pièces. */
      return null;
    }
    if (answers.taille === 'petite')      return 'petite';
    if (answers.taille === 'moyenne')     return 'moyenne';
    if (answers.taille === 'grande')      return 'grande';
    if (answers.taille === 'ne-sais-pas') return 'moyenne';
    return null; // taille jamais renseignée : aucune recommandation
  }

  /* Traduit une intention en OPTION RÉELLE, à partir de la liste des options
     de ce produit. Pure : elle ne touche pas au DOM, elle choisit dans un
     tableau qu'on lui donne.
     ⚠️ ELLE EST ICI, ET PAS DANS LA PRÉSÉLECTION, pour une raison de timing
     découverte en relecture : la modale MET EN PAUSE les notifications du
     projet pendant qu'elle est ouverte. L'étiquette posée sur le formulaire
     n'est donc pas rafraîchie tant que la modale n'est pas fermée — et l'écran
     de récap, qui s'affiche AVANT, lisait une étiquette absente ou périmée.
     Résultat : un encart sans taille, ou une taille d'avant sous un conseil qui
     la contredit. En partageant le calcul plutôt que le résultat, le récap n'a
     plus rien à attendre de personne.
     `max` = la dernière option, quel qu'en soit le nombre (Gaston : 110 cm).
     `grande` = la troisième si elle existe, sinon la dernière. */
  function resoudreTaille(options, intention) {
    if (!options || !options.length || !intention) return null;
    if (intention === 'max')     return options[options.length - 1];
    if (intention === 'petite')  return options[0];
    if (intention === 'moyenne') return options[Math.min(1, options.length - 1)];
    if (intention === 'grande')  return options[Math.min(2, options.length - 1)];
    return null;
  }

  // API publique
  window.sapiProject = {
    clesProjet: clesProjet,
    ecrireProjetDansUrl: ecrireProjetDansUrl,
    styleToEssence: styleToEssence,
    tailleIntention: tailleIntention,
    resoudreTaille: resoudreTaille,
    get: get,
    hasProject: hasProject,
    getAnswer: getAnswer,
    getLabel: getLabel,
    set: set,
    update: update,
    clear: clear,
    setAdviceText: setAdviceText,
    setContactState: setContactState,
    subscribe: subscribe,
    pauseNotifications: pauseNotifications,
    resumeNotifications: resumeNotifications,
    STORAGE_KEY: STORAGE_KEY,
    // Round 2 — 3.2 : helpers visibility centralisés. Les consommateurs JS
    // (modal, cards) appellent ces helpers au lieu de dupliquer la logique.
    computeVisibleStepIds: computeVisibleStepIds,
    cleanInvisibleAnswers: cleanInvisibleAnswersImpl,
  };
})();

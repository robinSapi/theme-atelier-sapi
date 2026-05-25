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
    // F2a-quater : nettoyer aussi ?piece= de l'URL pour éviter sa ré-ingestion
    // par ingestQueryParams() au prochain chargement de page (sinon le projet
    // "effacé" reviendrait dès le refresh).
    try {
      var url = new URL(window.location.href);
      if (url.searchParams.has('piece')) {
        url.searchParams.delete('piece');
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

  function notify() {
    var snapshot = get();
    for (var i = 0; i < listeners.length; i++) {
      try { listeners[i](snapshot); } catch (e) { /* swallow */ }
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
    entree:   'Entrée / Couloir',
    escalier: 'Cage d\'escalier',
  };

  function ingestQueryParams() {
    try {
      var params = new URLSearchParams(window.location.search);
      var piece = params.get('piece');
      if (!piece || !Object.prototype.hasOwnProperty.call(VALID_PIECES, piece)) return;

      var existingPiece = getAnswer('piece');

      // F2a-quater : nouvelle pièce arrivée par URL (depuis home, roompicker,
      // lien externe) → reset COMPLET du projet précédent. Les anciennes
      // réponses (taille/sortie/hauteur/table/style) n'ont plus de sens sur
      // une autre pièce — l'utilisateur démarre un nouveau parcours.
      if (existingPiece && existingPiece !== piece) {
        clearRaw(); // wipe localStorage silencieusement (notify unique via update)
        update({ piece: piece }, { piece: VALID_PIECES[piece] });
        return;
      }
      // Même pièce → rien à faire
      if (existingPiece === piece) return;
      // Pas de projet existant → ingestion classique
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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // API publique
  window.sapiProject = {
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
    STORAGE_KEY: STORAGE_KEY,
    // Round 2 — 3.2 : helpers visibility centralisés. Les consommateurs JS
    // (modal, cards) appellent ces helpers au lieu de dupliquer la logique.
    computeVisibleStepIds: computeVisibleStepIds,
    cleanInvisibleAnswers: cleanInvisibleAnswersImpl,
  };
})();

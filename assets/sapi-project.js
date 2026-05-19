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
      return { answers: {}, labels: {}, created_at: null, updated_at: null, session_id: null };
    }
    if (!p.labels || typeof p.labels !== 'object') p.labels = {};
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
   * @param {Object} [extra]  { session_id, ... }
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

    p.updated_at = now;
    var ok = writeRaw(p);
    if (ok) notify();
    return ok;
  }

  function clear() {
    var ok = clearRaw();
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
      // Si le projet n'a pas déjà cette pièce, on la sauvegarde silencieusement.
      // Si la pièce est différente, on respecte le projet en cours (le visiteur a
      // peut-être suivi un lien externe ; on ne veut pas écraser son projet).
      var existingPiece = getAnswer('piece');
      if (existingPiece) return;
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
    subscribe: subscribe,
    STORAGE_KEY: STORAGE_KEY,
  };
})();

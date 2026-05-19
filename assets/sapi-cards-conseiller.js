/**
 * Sapi Cards Conseiller — Cards "Conseil de Robin" / "Mon projet" sur /mes-creations/ (F2a Phase 2)
 *
 * Lit window.sapiProject (source de vérité), choisit la card à afficher,
 * fetche la phrase IA pour "Mon projet" et filtre la grille produit selon
 * les réponses du projet.
 *
 * Reprend la logique de filtrage de assets/mega-filtre.js (cardMatches,
 * getAcceptedCategories, etc.) — phase de transition avant la refonte
 * complète de mega-filtre.js en Phase 3.
 */
(function () {
  'use strict';

  var config = window.SAPI_CARDS_CONSEILLER || {};
  var STEPS = Array.isArray(config.steps) ? config.steps : [];
  var RULES = config.rules || {};
  var FALLBACK_PHRASE = config.fallbackPhrase || 'Voici ma sélection pour ton projet.';

  /* ─────────────────────────────────────────────
     Helpers visibilité (mirror inc/guide-data.php + mega-filtre.js)
     ───────────────────────────────────────────── */
  function getVisibleStepIds(answers) {
    var visible = [];
    for (var i = 0; i < STEPS.length; i++) {
      var step = STEPS[i];
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

  function cleanInvisibleAnswers(answers) {
    var visible = getVisibleStepIds(answers);
    var clean = {};
    for (var sid in answers) {
      if (answers.hasOwnProperty(sid) && visible.indexOf(sid) !== -1) {
        clean[sid] = answers[sid];
      }
    }
    return clean;
  }

  /* ─────────────────────────────────────────────
     Filtrage produit (mirror mega-filtre.js Phase 1)
     ───────────────────────────────────────────── */
  function hasAnyAnswer() {
    var a = (window.sapiProject && window.sapiProject.get().answers) || {};
    for (var k in a) {
      if (a.hasOwnProperty(k)) return true;
    }
    return false;
  }

  function getAnswers() {
    var raw = (window.sapiProject && window.sapiProject.get().answers) || {};
    return cleanInvisibleAnswers(raw);
  }

  function getAcceptedCategories(answers) {
    var sortie = answers.sortie || '';
    var piece = answers.piece || '';
    var eclairage = answers.eclairage || '';
    var pool;

    if (eclairage === 'secondaire') {
      var bySortie = RULES.cats_secondaire_by_sortie || {};
      pool = (bySortie[sortie] || bySortie[''] || ['lampadaires', 'lampesaposer']).slice();
      if (piece === 'cuisine') {
        pool = pool.filter(function (c) { return c !== 'lampesaposer'; });
      }
      return pool;
    }

    var cats = RULES.cats_by_sortie || {};
    pool = (cats[sortie] || cats[''] || ['suspensions', 'lampadaires', 'lampesaposer', 'appliques']).slice();
    if (piece === 'cuisine') {
      pool = pool.filter(function (c) { return c !== 'lampesaposer'; });
    }
    return pool;
  }

  function getAmpouleFilter(answers) {
    var piece = answers.piece || '';
    var taille = answers.taille || '';
    if (!piece) return null;
    if (taille === 'grande' && (RULES.ampoule_skip_when_grande || []).indexOf(piece) !== -1) return null;
    var map = RULES.ampoule_by_piece || {};
    if (Object.prototype.hasOwnProperty.call(map, piece)) return map[piece];
    return null;
  }

  function isVerticalAllowed(answers) {
    var piece = answers.piece || '';
    var taille = answers.taille || '';
    var hauteur = answers.hauteur || '';
    return (
      piece === 'escalier' ||
      (piece === 'entree' && (hauteur === 'haute' || hauteur === 'confortable')) ||
      (taille === 'petite' && (hauteur === 'haute' || hauteur === 'confortable'))
    );
  }

  function isHorizontalExcluded(answers) {
    return (
      answers.piece === 'escalier' ||
      (answers.taille === 'petite' && answers.hauteur === 'haute')
    );
  }

  function cardMatches(card) {
    if (!hasAnyAnswer()) return true;
    var answers = getAnswers();
    var catsAttr = card.getAttribute('data-categories') || '';
    var cardCats = catsAttr.split(/\s+/).filter(Boolean);

    var extras = RULES.extras_slugs || [];
    for (var i = 0; i < extras.length; i++) {
      if (cardCats.indexOf(extras[i]) !== -1) return false;
    }

    var accepted = getAcceptedCategories(answers);
    var hasMatchCat = cardCats.some(function (c) { return accepted.indexOf(c) !== -1; });
    if (!hasMatchCat) return false;

    var isSuspension = cardCats.indexOf('suspensions') !== -1;
    if (isSuspension) {
      var formatAttr = card.getAttribute('data-format-luminaire') || '';
      var cardFormats = formatAttr.split(/\s+/).filter(Boolean);
      if (cardFormats.indexOf('vertical') !== -1 && !isVerticalAllowed(answers)) return false;
      if (cardFormats.indexOf('horizontal') !== -1 && isHorizontalExcluded(answers)) return false;
    }

    var ampouleFilter = getAmpouleFilter(answers);
    if (ampouleFilter && ampouleFilter.length) {
      var ampAttr = card.getAttribute('data-type-ampoule') || '';
      var cardAmp = ampAttr.split(/\s+/).filter(Boolean);
      var ampOk = cardAmp.some(function (t) { return ampouleFilter.indexOf(t) !== -1; });
      if (!ampOk) return false;
    }

    return true;
  }

  function refilterGrid() {
    if (typeof window.sapiShopRefilter === 'function') {
      window.sapiShopRefilter();
      return;
    }
    // Fallback autonome (cas où shop.js ne serait pas chargé)
    var cards = document.querySelectorAll('.product-card-cinetique');
    cards.forEach(function (card) {
      var show = cardMatches(card);
      card.style.display = show ? '' : 'none';
      card.classList.toggle('is-filtered-out', !show);
    });
  }

  // Override l'API window.sapiMegaFilter pour que shop.js l'utilise.
  // mega-filtre.js Phase 1 expose des no-ops harmlets ; on prend le relais ici.
  window.sapiMegaFilter = {
    cardMatches: cardMatches,
    hasAnyAnswer: hasAnyAnswer,
  };

  /* ─────────────────────────────────────────────
     Rendu des cards
     ───────────────────────────────────────────── */
  var els = {};
  var advicePromise = null;
  var lastAdviceKey = null;

  function buildAnswersKey(answers) {
    var keys = Object.keys(answers).sort();
    var parts = [];
    for (var i = 0; i < keys.length; i++) {
      parts.push(keys[i] + '=' + answers[keys[i]]);
    }
    return parts.join('|');
  }

  function fetchAdvice() {
    var project = window.sapiProject && window.sapiProject.get();
    if (!project) return Promise.resolve(FALLBACK_PHRASE);

    var answers = cleanInvisibleAnswers(project.answers || {});
    var labels  = project.labels  || {};
    var key = buildAnswersKey(answers);

    // Réutilise un fetch en cours pour la même combinaison
    if (advicePromise && lastAdviceKey === key) return advicePromise;
    lastAdviceKey = key;

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_advice');
    fd.append('nonce', config.nonce || '');
    fd.append('answers', JSON.stringify(answers));
    fd.append('labels',  JSON.stringify(labels));

    advicePromise = fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        if (resp && resp.success && resp.data && typeof resp.data.message === 'string' && resp.data.message) {
          return resp.data.message;
        }
        return FALLBACK_PHRASE;
      })
      .catch(function () { return FALLBACK_PHRASE; });

    return advicePromise;
  }

  function renderMonProjet() {
    if (!els.cardMonProjet || !els.phrase || !els.phraseContent) return;
    els.cardConseil && (els.cardConseil.hidden = true);
    els.cardMonProjet.hidden = false;

    // État loading pour la phrase pendant le fetch IA
    els.phrase.setAttribute('data-state', 'loading');

    fetchAdvice().then(function (text) {
      // Garde-fou : si entre-temps le projet a été vidé, ne plus écrire
      if (!window.sapiProject || !window.sapiProject.hasProject()) return;
      els.phraseContent.textContent = text;
      els.phrase.removeAttribute('data-state');
    });
  }

  function renderConseil() {
    if (!els.cardConseil) return;
    els.cardMonProjet && (els.cardMonProjet.hidden = true);
    els.cardConseil.hidden = false;
  }

  function render() {
    if (window.sapiProject && window.sapiProject.hasProject()) {
      renderMonProjet();
    } else {
      renderConseil();
    }
    refilterGrid();
  }

  /* ─────────────────────────────────────────────
     CTA → dispatch événement (Phase 3 écoutera)
     ───────────────────────────────────────────── */
  function bindCTAs() {
    if (!els.zone) return;
    els.zone.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-action="open-modal"]');
      if (!btn) return;
      e.preventDefault();
      var modalState = btn.getAttribute('data-modal-state') || 's0';
      var event = new CustomEvent('sapi:open-modal', {
        bubbles: true,
        detail: { state: modalState },
      });
      btn.dispatchEvent(event);
      // Phase 2 : pas encore de modale — on log discrètement pour debug
      if (!window.__sapiModalReady) {
        // eslint-disable-next-line no-console
        console.info('[sapi] open-modal demandé (state=' + modalState + ') — modale Phase 3 à venir');
      }
    });
  }

  /* ─────────────────────────────────────────────
     Init
     ───────────────────────────────────────────── */
  function init() {
    els.zone = document.querySelector('[data-conseiller-zone]');
    if (!els.zone) return; // pas sur /mes-creations/

    els.cardConseil       = els.zone.querySelector('[data-conseiller-card="conseil"]');
    els.cardMonProjet     = els.zone.querySelector('[data-conseiller-card="mon-projet"]');
    els.phrase            = els.zone.querySelector('[data-mon-projet-phrase]');
    els.phraseContent     = els.zone.querySelector('[data-mon-projet-phrase-content]');

    bindCTAs();
    render();

    // Réagit aux changements du projet (autre onglet, modale, dev tools)
    if (window.sapiProject && typeof window.sapiProject.subscribe === 'function') {
      window.sapiProject.subscribe(function () {
        // Invalide le fetch IA en cours (les réponses ont changé)
        advicePromise = null;
        lastAdviceKey = null;
        render();
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

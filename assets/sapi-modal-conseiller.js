/**
 * Sapi Modal Conseiller — Modale tunnel S0/S1/S3 (F2a Phase 3)
 *
 * État S0 : écran 2 portes (Je choisis / Je décris)
 * État S1 : questions guidées (boutons-cards, avance auto, retour, progress)
 * État S3 : récap (chips + phrase IA Sonnet + CTA "Voir la sélection")
 *
 * Listener : event 'sapi:open-modal' (dispatché par sapi-cards-conseiller)
 *   detail.state = 's0' → tunnel complet depuis le début
 *   detail.state = 's3' → récap direct (projet existant, mode Modifier)
 *
 * Phase 4 ajoutera S2 (mode texte libre).
 */
(function () {
  'use strict';

  var config = window.SAPI_MODAL_CONSEILLER || {};
  var STEPS = Array.isArray(config.steps) ? config.steps : [];
  var ICONS = config.icons || {};
  var FALLBACK_RECAP = config.fallbackRecap || 'Voici une sélection adaptée à ton projet.';
  var KEY_LABELS = config.keyLabels || {
    piece: 'Pièce', taille: 'Taille', taille_escalier: 'Escalier',
    eclairage: 'Éclairage', sortie: 'Sortie', hauteur: 'Hauteur',
    table: 'Table', style: 'Style',
  };

  /* ─────────────────────────────────────────────
     State
     ───────────────────────────────────────────── */
  var state = {
    open: false,
    screen: null,         // 's0' | 's1' | 's3'
    answers: {},
    labels: {},
    currentQuestion: null,
    questionHistory: [],  // pile des questions traversées (pour Retour)
  };

  var els = {};
  var lastTrigger = null; // pour restaurer le focus à la fermeture

  /* ─────────────────────────────────────────────
     Helpers visibilité (mirror inc/guide-data.php)
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

  function cleanInvisibleAnswers() {
    var visible = getVisibleStepIds(state.answers);
    Object.keys(state.answers).forEach(function (sid) {
      if (visible.indexOf(sid) === -1) {
        delete state.answers[sid];
        delete state.labels[sid];
      }
    });
  }

  function getStep(stepId) {
    for (var i = 0; i < STEPS.length; i++) {
      if (STEPS[i].id === stepId) return STEPS[i];
    }
    return null;
  }

  function getDynamicQuestion(step) {
    if (step.dynamic_question && step.dynamic_question.piece) {
      var p = state.answers.piece;
      if (p && step.dynamic_question.piece[p]) return step.dynamic_question.piece[p];
    }
    return step.question;
  }

  /* ─────────────────────────────────────────────
     Rendu écrans
     ───────────────────────────────────────────── */
  function showScreen(name) {
    state.screen = name;
    if (!els.modal) return;
    var screens = els.modal.querySelectorAll('[data-screen]');
    screens.forEach(function (s) {
      s.hidden = (s.getAttribute('data-screen') !== name);
    });
    // Re-trigger fade-in animation on the visible screen
    var visible = els.modal.querySelector('[data-screen="' + name + '"]');
    if (visible) {
      visible.style.animation = 'none';
      // force reflow then restart
      void visible.offsetWidth;
      visible.style.animation = '';
    }
    // Scroll body to top quand on change d'écran
    if (els.body) els.body.scrollTop = 0;
  }

  function showQuestion(stepId) {
    state.currentQuestion = stepId;
    var step = getStep(stepId);
    if (!step) return;

    // Title (dynamique pour 'table' selon pièce)
    if (els.questionTitle) {
      els.questionTitle.textContent = getDynamicQuestion(step);
    }

    // Choices
    if (els.choices) {
      els.choices.innerHTML = '';
      var choices = step.choices || [];
      choices.forEach(function (choice) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'conseiller-choice';
        btn.setAttribute('data-choice', choice.slug);
        btn.setAttribute('data-label', choice.label);
        if (state.answers[stepId] === choice.slug) btn.classList.add('is-selected');

        var iconWrap = document.createElement('span');
        iconWrap.className = 'conseiller-choice__icon';
        iconWrap.innerHTML = ICONS[choice.icon] || '';
        btn.appendChild(iconWrap);

        var label = document.createElement('span');
        label.className = 'conseiller-choice__label';
        label.textContent = choice.label;
        btn.appendChild(label);

        if (choice.dim) {
          var dim = document.createElement('span');
          dim.className = 'conseiller-choice__dim';
          dim.textContent = choice.dim;
          btn.appendChild(dim);
        }

        els.choices.appendChild(btn);
      });
    }

    // Progress bar
    if (els.progressFill) {
      var visible = getVisibleStepIds(state.answers);
      var idx = visible.indexOf(stepId);
      var pct = visible.length > 0 ? Math.max(8, Math.round(((idx + 1) / visible.length) * 100)) : 8;
      els.progressFill.style.width = pct + '%';
    }
  }

  function answerCurrentQuestion(slug, label) {
    if (!state.currentQuestion) return;
    var step = state.currentQuestion;
    state.answers[step] = slug;
    state.labels[step] = label;
    cleanInvisibleAnswers();

    // Sauvegarde incrémentale dans sapiProject (partielle OK)
    if (window.sapiProject) {
      window.sapiProject.update(state.answers, state.labels);
    }

    // Empile la question dans l'historique pour permettre Retour
    if (state.questionHistory[state.questionHistory.length - 1] !== step) {
      state.questionHistory.push(step);
    }

    // Cherche la prochaine question visible après la courante
    var visible = getVisibleStepIds(state.answers);
    var idx = visible.indexOf(step);
    var nextStep = (idx !== -1 && idx + 1 < visible.length) ? visible[idx + 1] : null;

    if (nextStep) {
      showQuestion(nextStep);
    } else {
      showRecap();
    }
  }

  function backFromQuestion() {
    if (state.questionHistory.length === 0) {
      showScreen('s0');
      return;
    }
    var prev = state.questionHistory.pop();
    showQuestion(prev);
  }

  function backToQuestions() {
    // Depuis S3, on revient à la dernière question répondue
    var visible = getVisibleStepIds(state.answers);
    var lastAnswered = null;
    for (var i = visible.length - 1; i >= 0; i--) {
      if (state.answers[visible[i]]) { lastAnswered = visible[i]; break; }
    }
    if (!lastAnswered) lastAnswered = visible[0];

    // Reconstruit l'historique jusqu'à cette question (exclu)
    state.questionHistory = [];
    for (var j = 0; j < visible.length; j++) {
      if (visible[j] === lastAnswered) break;
      state.questionHistory.push(visible[j]);
    }
    showQuestion(lastAnswered);
    showScreen('s1');
  }

  /* ─────────────────────────────────────────────
     S3 — Récap + fetch phrase IA Sonnet
     ───────────────────────────────────────────── */
  var lastRecapKey = null;
  var recapPromise = null;

  function populateRecapChips() {
    if (!els.recapChips) return;
    els.recapChips.innerHTML = '';
    var visible = getVisibleStepIds(state.answers);
    visible.forEach(function (sid) {
      var slug = state.answers[sid];
      if (!slug) return;
      var label = state.labels[sid] || slug;
      var keyLabel = KEY_LABELS[sid] || sid;

      var chip = document.createElement('span');
      chip.className = 'conseiller-chip';
      var keyEl = document.createElement('span');
      keyEl.className = 'conseiller-chip__key';
      keyEl.textContent = keyLabel + ' :';
      chip.appendChild(keyEl);
      chip.appendChild(document.createTextNode(' ' + label));
      els.recapChips.appendChild(chip);
    });
  }

  function buildAnswersKey() {
    var visible = getVisibleStepIds(state.answers);
    var parts = [];
    visible.forEach(function (sid) {
      if (state.answers[sid]) parts.push(sid + '=' + state.answers[sid]);
    });
    return parts.join('|');
  }

  function fetchRecapPhrase() {
    var key = buildAnswersKey();
    if (recapPromise && lastRecapKey === key) return recapPromise;
    lastRecapKey = key;

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_recap');
    fd.append('nonce', config.nonce || '');
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('labels',  JSON.stringify(state.labels));

    recapPromise = fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        if (resp && resp.success && resp.data && typeof resp.data.message === 'string' && resp.data.message) {
          return resp.data.message;
        }
        return FALLBACK_RECAP;
      })
      .catch(function () { return FALLBACK_RECAP; });

    return recapPromise;
  }

  function showRecap() {
    populateRecapChips();

    if (els.recapText) {
      els.recapText.setAttribute('data-state', 'loading');
    }

    var startedAt = Date.now();
    fetchRecapPhrase().then(function (text) {
      if (!els.recapText) return;
      var elapsed = Date.now() - startedAt;
      var wait = Math.max(0, 700 - elapsed);
      setTimeout(function () {
        if (!state.open || state.screen !== 's3') return;
        els.recapText.textContent = text;
        els.recapText.removeAttribute('data-state');
      }, wait);
    });

    showScreen('s3');
  }

  /* ─────────────────────────────────────────────
     S0 — Choix de porte
     ───────────────────────────────────────────── */
  function chooseDoor(door) {
    if (door === 'choisis') {
      startQuestionsFlow();
    } else if (door === 'decris') {
      // Phase 4 : ouvrira S2. Pour l'instant, log + reste sur S0.
      // eslint-disable-next-line no-console
      console.info('[sapi] mode "Je décris" sera disponible Phase 4');
    }
  }

  function startQuestionsFlow() {
    state.questionHistory = [];
    var visible = getVisibleStepIds(state.answers);
    var first = visible[0] || 'piece';
    showQuestion(first);
    showScreen('s1');
  }

  /* ─────────────────────────────────────────────
     CTA Voir la sélection — applique le projet
     ───────────────────────────────────────────── */
  function applyAndClose() {
    if (window.sapiProject) {
      window.sapiProject.set(state.answers, state.labels);
    }
    closeModal();

    // Force le refilter (au cas où subscribe ne tire pas le pipeline complet)
    if (typeof window.sapiShopRefilter === 'function') {
      window.sapiShopRefilter();
    }

    // Scroll vers la grille pour montrer le résultat
    var grid = document.getElementById('sapi-product-grid');
    if (grid && grid.scrollIntoView) {
      grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  /* ─────────────────────────────────────────────
     Open / close
     ───────────────────────────────────────────── */
  function hydrateFromProject() {
    if (!window.sapiProject) return;
    var p = window.sapiProject.get();
    state.answers = {};
    state.labels = {};
    if (p && p.answers) {
      Object.keys(p.answers).forEach(function (k) { state.answers[k] = p.answers[k]; });
    }
    if (p && p.labels) {
      Object.keys(p.labels).forEach(function (k) { state.labels[k] = p.labels[k]; });
    }
    cleanInvisibleAnswers();
  }

  function openModal(initialScreen) {
    if (!els.modal) return;
    hydrateFromProject();
    state.questionHistory = [];
    recapPromise = null;
    lastRecapKey = null;

    state.open = true;
    els.modal.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    if (initialScreen === 's3' && window.sapiProject && window.sapiProject.hasProject()) {
      showRecap();
    } else if (initialScreen === 's1') {
      startQuestionsFlow();
    } else {
      showScreen('s0');
    }

    // Focus le bouton de fermeture pour annoncer l'ouverture aux screen readers
    setTimeout(function () {
      var close = els.modal.querySelector('[data-action="close"]');
      if (close) close.focus();
    }, 50);
  }

  function closeModal() {
    if (!els.modal) return;
    state.open = false;
    els.modal.hidden = true;
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    if (lastTrigger && lastTrigger.focus) {
      try { lastTrigger.focus(); } catch (e) { /* swallow */ }
    }
  }

  /* ─────────────────────────────────────────────
     Délégation événements
     ───────────────────────────────────────────── */
  function bindEvents() {
    // Listener global pour l'événement venant des cards Phase 2
    document.addEventListener('sapi:open-modal', function (e) {
      lastTrigger = e.target && e.target.closest ? e.target.closest('[data-action="open-modal"]') : null;
      var st = (e.detail && e.detail.state) || 's0';
      openModal(st);
    });

    // ESC pour fermer
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && state.open) {
        e.preventDefault();
        closeModal();
      }
    });

    if (!els.modal) return;

    // Délégation : clics dans la modale (close, door, choice, back, apply)
    els.modal.addEventListener('click', function (e) {
      var actionBtn = e.target.closest('[data-action]');
      if (!actionBtn) return;
      var action = actionBtn.getAttribute('data-action');

      switch (action) {
        case 'close':
          closeModal();
          break;
        case 'door':
          var door = actionBtn.getAttribute('data-door');
          chooseDoor(door);
          break;
        case 'back':
          backFromQuestion();
          break;
        case 'back-to-questions':
          backToQuestions();
          break;
        case 'apply':
          applyAndClose();
          break;
      }
    });

    // Click sur un choix de question (délégué)
    els.choices && els.choices.addEventListener('click', function (e) {
      var btn = e.target.closest('.conseiller-choice');
      if (!btn) return;
      var slug = btn.getAttribute('data-choice');
      var label = btn.getAttribute('data-label') || btn.textContent.trim();
      answerCurrentQuestion(slug, label);
    });
  }

  /* ─────────────────────────────────────────────
     Init
     ───────────────────────────────────────────── */
  function init() {
    els.modal = document.querySelector('[data-conseiller-modal]');
    if (!els.modal) return; // pas sur la page concernée
    els.body          = els.modal.querySelector('[data-modal-body]');
    els.questionTitle = els.modal.querySelector('[data-question-title]');
    els.choices       = els.modal.querySelector('[data-choices]');
    els.progressFill  = els.modal.querySelector('[data-progress-fill]');
    els.recapChips    = els.modal.querySelector('[data-recap-chips]');
    els.recapText     = els.modal.querySelector('[data-recap-text]');

    // Marqueur pour les cards Phase 2 (évite leur fallback console.info)
    window.__sapiModalReady = true;

    bindEvents();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Exposition pour debug et appels externes (Phase 4 fiche produit)
  window.sapiModalConseiller = {
    open: openModal,
    close: closeModal,
  };
})();

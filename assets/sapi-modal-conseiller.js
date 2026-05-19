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

  /* ─────────────────────────────────────────────
     State
     ───────────────────────────────────────────── */
  var state = {
    open: false,
    screen: null,         // 's0' | 's1' | 's2-start' | 's2-chat' | 's-transition'
    answers: {},
    labels: {},
    currentQuestion: null,
    questionHistory: [],  // pile des questions traversées (pour Retour)
    transition: false,    // F2a-bis : true pendant l'écran "Robin réfléchit"
    chat: {
      conversation: [],   // [{role:'user'|'assistant', content:'...'}]
      sessionId: null,
      status: 'idle',     // 'idle' | 'thinking'
      maxUserMessages: config.maxMessages || 15,
    },
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
    // Scroll la card au top quand on change d'écran (la card est scrollable)
    if (els.modalCard) els.modalCard.scrollTop = 0;
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
      // F2a-bis : dernière question répondue → écran transition + appel IA + close
      showTransitionAndExit({ source: 's1' });
    }
  }

  function backFromQuestion() {
    // Si on est sur S2-start (mode "Je décris"), Retour ramène à S0
    if (state.screen === 's2-start') {
      showScreen('s0');
      return;
    }
    if (state.questionHistory.length === 0) {
      showScreen('s0');
      return;
    }
    var prev = state.questionHistory.pop();
    showQuestion(prev);
  }

  /* ─────────────────────────────────────────────
     F2a-bis — Écran transition + 1 seul appel IA à la sortie de la modale
     S3 récap supprimé. À la dernière question répondue (S1) ou au CTA
     "Voir la sélection" (S2.chat), on affiche un écran "Robin réfléchit",
     on appelle sapi_megafilter_advice (Sonnet), on stocke le résultat
     dans sapiProject.advice_text, puis on ferme la modale.
     ───────────────────────────────────────────── */

  function showTransitionAndExit(opts) {
    opts = opts || {};
    if (state.transition) return; // évite double-trigger
    state.transition = true;
    showScreen('s-transition');

    var startedAt = Date.now();

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_advice');
    fd.append('nonce', config.nonce || '');
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('labels',  JSON.stringify(state.labels));
    if (opts.conversation && Array.isArray(opts.conversation) && opts.conversation.length) {
      fd.append('conversation', JSON.stringify(opts.conversation));
    }

    var fetchPromise = fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        if (resp && resp.success && resp.data && typeof resp.data.advice_text === 'string' && resp.data.advice_text) {
          return resp.data.advice_text;
        }
        return null; // fallback géré côté front (texte générique de la pièce)
      })
      .catch(function () { return null; });

    fetchPromise.then(function (advice) {
      var elapsed = Date.now() - startedAt;
      // Minimum 700ms d'écran transition pour la lisibilité du "Robin réfléchit"
      var wait = Math.max(0, 700 - elapsed);
      setTimeout(function () {
        // Sauvegarde du projet final avec advice_text (un seul write côté localStorage)
        if (window.sapiProject) {
          window.sapiProject.set(state.answers, state.labels, { advice_text: advice });
        }
        state.transition = false;
        closeModal();
        // Force le refilter au cas où le subscriber ne suffit pas
        if (typeof window.sapiShopRefilter === 'function') window.sapiShopRefilter();
        // Scroll vers la grille pour montrer le résultat
        var grid = document.getElementById('sapi-product-grid');
        if (grid && grid.scrollIntoView) {
          grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }, wait);
    });
  }

  /* ─────────────────────────────────────────────
     S2 — Mode texte libre + chat IA (F1b réutilisé)
     ───────────────────────────────────────────── */

  function getChoiceLabel(stepId, slug) {
    var step = getStep(stepId);
    if (!step) return slug;
    var choices = step.choices || [];
    for (var i = 0; i < choices.length; i++) {
      if (choices[i].slug === slug) return choices[i].label;
    }
    return slug;
  }

  // Merge un patch {key: slug | null} dans state.answers + state.labels
  function applyFiltersBatch(filters) {
    if (!filters || typeof filters !== 'object') return;
    Object.keys(filters).forEach(function (key) {
      var val = filters[key];
      if (val === null) {
        delete state.answers[key];
        delete state.labels[key];
      } else if (typeof val === 'string' && val) {
        state.answers[key] = val;
        state.labels[key]  = getChoiceLabel(key, val);
      }
    });
    cleanInvisibleAnswers();
    // Sauvegarde incrémentale
    if (window.sapiProject) {
      window.sapiProject.update(state.answers, state.labels);
    }
  }

  function enterChatMode() {
    if (els.modalCard) els.modalCard.classList.add('is-chat-mode');
    showScreen('s2-chat');
  }

  function exitChatMode() {
    if (els.modalCard) els.modalCard.classList.remove('is-chat-mode');
  }

  function startFreetextFlow() {
    // Réinitialise la session chat à chaque ouverture de S2
    state.chat.conversation = [];
    state.chat.sessionId = null;
    state.chat.status = 'idle';
    if (els.chatMessages) els.chatMessages.innerHTML = '';
    if (els.chatCta) els.chatCta.hidden = true;
    if (els.freetextInput) els.freetextInput.value = '';
    if (els.chatInput) {
      els.chatInput.value = '';
      els.chatInput.disabled = false;
      if (els.chatInputDefaultPlaceholder) {
        els.chatInput.placeholder = els.chatInputDefaultPlaceholder;
      }
    }
    if (els.chatSend) els.chatSend.disabled = false;
    showScreen('s2-start');
    // Focus l'input pour saisir directement
    setTimeout(function () {
      if (els.freetextInput) els.freetextInput.focus();
    }, 100);
  }

  function addUserBubble(text) {
    if (!els.chatMessages) return;
    var wrap = document.createElement('div');
    wrap.className = 'conseiller-chat-msg conseiller-chat-msg--user';
    var bubble = document.createElement('div');
    bubble.className = 'conseiller-chat-bubble';
    bubble.textContent = text;
    wrap.appendChild(bubble);
    els.chatMessages.appendChild(wrap);
    scrollChatToBottom();
  }

  function addRobinBubble(text, opts) {
    if (!els.chatMessages) return;
    opts = opts || {};
    var wrap = document.createElement('div');
    wrap.className = 'conseiller-chat-msg conseiller-chat-msg--robin';

    var bubble = document.createElement('div');
    bubble.className = 'conseiller-chat-bubble';
    bubble.textContent = text || '';
    wrap.appendChild(bubble);

    // Encart "Filtres appliqués"
    if (opts.filters && typeof opts.filters === 'object') {
      var parts = [];
      Object.keys(opts.filters).forEach(function (k) {
        var slug = opts.filters[k];
        if (slug === null) return;
        parts.push(getChoiceLabel(k, slug));
      });
      if (parts.length) {
        var fb = document.createElement('div');
        fb.className = 'conseiller-chat-filters';
        var label = document.createElement('span');
        label.className = 'conseiller-chat-filters__label';
        label.textContent = 'Filtres appliqués';
        fb.appendChild(label);
        var chips = document.createElement('span');
        chips.className = 'conseiller-chat-filters__chips';
        chips.textContent = parts.join(' · ');
        fb.appendChild(chips);
        wrap.appendChild(fb);
      }
    }

    els.chatMessages.appendChild(wrap);
    scrollChatToBottom();
  }

  function addThinkingBubble() {
    if (!els.chatMessages) return;
    if (document.getElementById('conseiller-chat-thinking')) return;
    var wrap = document.createElement('div');
    wrap.className = 'conseiller-chat-msg conseiller-chat-msg--robin';
    wrap.id = 'conseiller-chat-thinking';
    var bubble = document.createElement('div');
    bubble.className = 'conseiller-chat-bubble conseiller-chat-thinking';
    bubble.setAttribute('aria-label', 'Robin réfléchit');
    for (var i = 0; i < 3; i++) {
      var dot = document.createElement('span');
      dot.className = 'conseiller-chat-thinking__dot';
      bubble.appendChild(dot);
    }
    wrap.appendChild(bubble);
    els.chatMessages.appendChild(wrap);
    scrollChatToBottom();
  }

  function removeThinkingBubble() {
    var el = document.getElementById('conseiller-chat-thinking');
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }

  function scrollChatToBottom() {
    if (!els.chatMessages) return;
    // Le scrollable est .conseiller-chat (parent direct des messages)
    var scrollable = els.chatMessages.closest('.conseiller-chat') || els.chatMessages;
    scrollable.scrollTop = scrollable.scrollHeight;
  }

  function setChatFooterState(mode) {
    if (!els.chatInput || !els.chatSend) return;
    if (mode === 'loading') {
      els.chatInput.disabled = true;
      els.chatSend.disabled = true;
    } else if (mode === 'locked') {
      els.chatInput.disabled = true;
      els.chatSend.disabled = true;
      els.chatInput.value = '';
      els.chatInput.placeholder = 'Tu as atteint la limite. Clique sur Voir la sélection.';
    } else {
      els.chatInput.disabled = false;
      els.chatSend.disabled = false;
    }
  }

  function revealChatCta() {
    if (els.chatCta) els.chatCta.hidden = false;
  }

  // Appel IA : extraction freetext (Haiku) — endpoint F1b existant
  function submitFreetext(text) {
    if (state.chat.status !== 'idle') return;
    text = (text || '').trim();
    if (!text) return;

    // Transition S2-start → S2-chat (mode chat layout)
    enterChatMode();
    addUserBubble(text);
    addThinkingBubble();
    state.chat.status = 'thinking';
    setChatFooterState('loading');

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_freetext');
    fd.append('nonce', config.nonce || '');
    fd.append('message', text);
    if (state.chat.sessionId) fd.append('session_id', state.chat.sessionId);

    fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');

        if (!resp || !resp.success) {
          var fallback = (resp && resp.data && resp.data.fallback) ||
            'Je n\'arrive pas à analyser ton message. Tu peux essayer de répondre directement aux questions ou me contacter via le formulaire.';
          addRobinBubble(fallback);
          state.chat.conversation.push({ role: 'user', content: text });
          state.chat.conversation.push({ role: 'assistant', content: fallback });
          return;
        }

        var data = resp.data || {};
        state.chat.sessionId = data.session_id || state.chat.sessionId;

        var filters = data.filters || {};
        if (Object.keys(filters).length) {
          // Freetext = nouvelle description complète, on remplace les chips
          state.answers = {};
          state.labels = {};
          applyFiltersBatch(filters);
        }

        addRobinBubble(data.message || '', { filters: filters });
        state.chat.conversation.push({ role: 'user', content: text });
        state.chat.conversation.push({ role: 'assistant', content: data.message || '' });
        revealChatCta();
      })
      .catch(function () {
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Petit souci de connexion. Tu peux réessayer.');
      });
  }

  // Appel IA : chat conversationnel (Sonnet) — endpoint F1b existant
  function submitChat(text) {
    if (state.chat.status !== 'idle') return;
    text = (text || '').trim();
    if (!text) return;

    // Garde-fou client : 15 messages max
    var userMsgCount = 0;
    for (var i = 0; i < state.chat.conversation.length; i++) {
      if (state.chat.conversation[i].role === 'user') userMsgCount++;
    }
    if (userMsgCount >= state.chat.maxUserMessages) {
      addRobinBubble('On a bien discuté ! Clique sur Voir la sélection pour découvrir les modèles.');
      setChatFooterState('locked');
      revealChatCta();
      return;
    }

    addUserBubble(text);
    addThinkingBubble();
    state.chat.status = 'thinking';
    setChatFooterState('loading');

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_chat');
    fd.append('nonce', config.nonce || '');
    fd.append('user_message', text);
    fd.append('current_filters', JSON.stringify(state.answers));
    fd.append('conversation', JSON.stringify(state.chat.conversation));
    if (state.chat.sessionId) fd.append('session_id', state.chat.sessionId);

    fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');

        if (!resp || !resp.success) {
          var fallback = (resp && resp.data && resp.data.fallback) ||
            'Je n\'arrive pas à te répondre. Tu peux retenter ou cliquer sur Voir la sélection.';
          addRobinBubble(fallback);
          state.chat.conversation.push({ role: 'user', content: text });
          state.chat.conversation.push({ role: 'assistant', content: fallback });
          return;
        }

        var data = resp.data || {};
        state.chat.sessionId = data.session_id || state.chat.sessionId;

        if (data.filters_update) {
          applyFiltersBatch(data.filters_update);
        }
        addRobinBubble(data.message || '', { filters: data.filters_update });

        if (Array.isArray(data.conversation)) {
          state.chat.conversation = data.conversation;
        } else {
          state.chat.conversation.push({ role: 'user', content: text });
          state.chat.conversation.push({ role: 'assistant', content: data.message || '' });
        }

        if (data.action === 'contact') {
          // Pas de routing contact pour Phase 4 — on garde la CTA "Voir la sélection"
          setChatFooterState('locked');
        }

        revealChatCta();
      })
      .catch(function () {
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Petit souci de connexion. Tu peux réessayer.');
      });
  }

  /* ─────────────────────────────────────────────
     S0 — Choix de porte
     ───────────────────────────────────────────── */
  function chooseDoor(door) {
    if (door === 'choisis') {
      startQuestionsFlow();
    } else if (door === 'decris') {
      startFreetextFlow();
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
    state.transition = false;

    state.open = true;
    els.modal.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    // F2a-bis : plus d'écran S3, donc plus de "ouvrir directement au récap".
    // Si state.s3 demandé (anciens liens / data-modal-state), on retombe sur S0.
    if (initialScreen === 's1') {
      startQuestionsFlow();
    } else {
      showScreen('s0');
    }

    // Focus la card (rôle dialog) pour annoncer l'ouverture aux screen readers
    setTimeout(function () {
      if (els.modalCard && els.modalCard.focus) {
        els.modalCard.setAttribute('tabindex', '-1');
        els.modalCard.focus({ preventScroll: true });
      }
    }, 50);
  }

  function closeModal() {
    if (!els.modal) return;
    state.open = false;
    els.modal.hidden = true;
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    exitChatMode();
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
      // Click sur l'overlay (en dehors du dialog) → ferme
      if (e.target === els.modal) {
        closeModal();
        return;
      }

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
        case 'apply':
          // F2a-bis : CTA "Voir la sélection" en S2.chat → écran transition + appel IA
          // unique (avec la conversation), puis save + close. Plus de S3 récap.
          showTransitionAndExit({
            source: 's2',
            conversation: (state.chat && state.chat.conversation) || [],
          });
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

    // S2.start : submit freetext (Enter ou clic sur le bouton flèche)
    var freetextForm = els.modal.querySelector('[data-freetext-form]');
    if (freetextForm) {
      freetextForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!els.freetextInput) return;
        var val = els.freetextInput.value;
        els.freetextInput.value = '';
        submitFreetext(val);
      });
    }

    // S2.start : clic sur une suggestion → submitFreetext
    els.modal.addEventListener('click', function (e) {
      var sug = e.target.closest('[data-suggestion]');
      if (!sug) return;
      var text = sug.getAttribute('data-suggestion') || sug.textContent.trim();
      submitFreetext(text);
    });

    // S2.chat : submit message
    var chatForm = els.modal.querySelector('[data-chat-form]');
    if (chatForm) {
      chatForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!els.chatInput) return;
        var val = els.chatInput.value;
        els.chatInput.value = '';
        submitChat(val);
      });
    }
  }

  /* ─────────────────────────────────────────────
     Init
     ───────────────────────────────────────────── */
  function init() {
    els.modal = document.querySelector('[data-conseiller-modal]');
    if (!els.modal) return; // pas sur la page concernée
    els.modalCard     = els.modal.querySelector('[data-modal-card]');
    els.questionTitle = els.modal.querySelector('[data-question-title]');
    els.choices       = els.modal.querySelector('[data-choices]');
    els.progressFill  = els.modal.querySelector('[data-progress-fill]');
    els.freetextInput = els.modal.querySelector('[data-freetext-input]');
    els.chatMessages  = els.modal.querySelector('[data-chat-messages]');
    els.chatCta       = els.modal.querySelector('[data-chat-cta]');
    els.chatInput     = els.modal.querySelector('[data-chat-input]');
    els.chatSend      = els.modal.querySelector('.conseiller-chat-footer__send');
    els.chatInputDefaultPlaceholder = els.chatInput ? els.chatInput.getAttribute('placeholder') : '';

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

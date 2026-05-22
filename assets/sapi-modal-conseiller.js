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

  /* ─────────────────────────────────────────────
     sapiSafeFetch (audit #5) — fetch JSON avec :
       - timeout configurable (15s Haiku, 25s Sonnet)
       - check r.ok (sinon throw HTTP <status>)
       - support d'un AbortSignal externe (cancel quand modale ferme)
     Throw une Error 'timeout' / 'aborted' / 'HTTP xxx' au caller, qui
     décide du message UX et du reset state.transition.
     ───────────────────────────────────────────── */
  function sapiSafeFetch(url, options, opts) {
    options = options || {};
    opts = opts || {};
    var timeoutMs = typeof opts.timeout === 'number' ? opts.timeout : 15000;
    var externalSignal = opts.signal || null;
    var controller = new AbortController();
    var aborted = false;
    var timer = setTimeout(function () { aborted = 'timeout'; controller.abort(); }, timeoutMs);
    if (externalSignal) {
      if (externalSignal.aborted) { aborted = 'external'; controller.abort(); }
      externalSignal.addEventListener('abort', function () { aborted = aborted || 'external'; controller.abort(); }, { once: true });
    }
    var fetchOpts = Object.assign({}, options, { signal: controller.signal });
    return fetch(url, fetchOpts).then(function (r) {
      clearTimeout(timer);
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    }).catch(function (e) {
      clearTimeout(timer);
      if (e && e.name === 'AbortError') {
        var reason = aborted || 'aborted';
        throw new Error(reason === 'timeout' ? 'timeout' : 'aborted');
      }
      throw e;
    });
  }

  var config = window.SAPI_MODAL_CONSEILLER || {};
  var STEPS = Array.isArray(config.steps) ? config.steps : [];
  var ICONS = config.icons || {};
  // F2b Phase 2 — Mode court (fiche produit) : whitelist des steps autorisés
  var SHORT_STEPS = Array.isArray(config.shortSteps) ? config.shortSteps : ['piece', 'taille', 'taille_escalier', 'style'];
  var STYLE_CONSEILS = config.styleConseils || {};
  var SIZE_CONSEILS  = config.sizeConseils  || {};
  var PRODUCT_CTX = config.product || null;

  // Mapping projet → essence (legacy mon-projet.js pré-F1c)
  var ESSENCE_FROM_STYLE = { moderne: 'peuplier', ancien: 'okoume' };
  var ESSENCE_LABEL      = { peuplier: 'Peuplier', okoume: 'Okoumé' };
  // Mapping taille → index dans le select WC (legacy)
  var TAILLE_TO_INDEX    = { petite: 0, moyenne: 1, grande: 2 };

  // F2a-ter : labels humains des clés pour les chips récap S3 ("Pièce : Salon").
  var KEY_LABELS = {
    piece: 'Pièce',
    taille: 'Taille',
    taille_escalier: 'Escalier',
    eclairage: 'Éclairage',
    sortie: 'Sortie',
    hauteur: 'Hauteur',
    table: 'Au-dessus',
    style: 'Style',
  };

  /* ─────────────────────────────────────────────
     State
     ───────────────────────────────────────────── */
  var state = {
    open: false,
    screen: null,         // 's0' | 's1' | 's2-chat' | 's3' | 's-product-recap'
    answers: {},
    labels: {},
    currentQuestion: null,
    questionHistory: [],  // pile des questions traversées (pour Retour)
    transition: false,    // F2a-bis : true pendant l'écran "Robin réfléchit"
    aiController: null,   // Audit #7 : AbortController de la requête IA en cours, abort sur close/replace
    shortMode: false,     // F2b Phase 2 — true quand ouvert depuis fiche produit
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
  // Renvoie la liste brute des steps visibles (logique visibility uniquement).
  // Ne tient PAS compte du mode court — c'est cette base qui sert à
  // cleanInvisibleAnswers (qui ne doit pas effacer eclairage/sortie/hauteur/table
  // juste parce qu'on est sur une fiche produit en short mode).
  function computeRawVisibleSteps(answers) {
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

  // Visibilité effective pour le flow modale : applique le filtre mode court
  // si actif. C'est la liste utilisée pour les questions affichées, la barre
  // de progression, les chips récap et le routing fin-de-parcours.
  function getVisibleStepIds(answers) {
    var visible = computeRawVisibleSteps(answers);
    if (state.shortMode) {
      visible = visible.filter(function (id) { return SHORT_STEPS.indexOf(id) !== -1; });
    }
    return visible;
  }

  function cleanInvisibleAnswers() {
    // Utilise la visibilité BRUTE (sans short mode) pour ne pas effacer les
    // réponses des steps longs quand on navigue depuis une fiche produit.
    var visible = computeRawVisibleSteps(state.answers);
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
      // F2a-quater : 2 cols quand 2 ou 4 choix (sinon items isolés sur la
      // dernière ligne d'un grid 3 cols)
      els.choices.classList.toggle('conseiller-choices--2col', choices.length === 2 || choices.length === 4);
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
      // F2a-quater : bascule visuelle S0→S1 (ou no-op si déjà S1)
      if (state.screen !== 's1') showScreen('s1');
    } else if (state.shortMode) {
      // F2b Phase 2 — fin du parcours court : récap produit + IA dédiée (pas de
      // morphing modale→card, on reste dans la modale ouverte).
      if (window.sapiProject) {
        window.sapiProject.set(state.answers, state.labels);
      }
      showProductRecap();
    } else {
      // F2a-bis : dernière question répondue → écran transition + appel IA + close
      showTransitionAndExit({ source: 's1' });
    }
  }

  function backFromQuestion() {
    // F2a-quater : Retour depuis S1 → revient à la question précédente, ou à
    // l'écran S0 hybride si on est sur la 1re question (history vide).
    if (state.questionHistory.length === 0) {
      renderS0Hybrid(determineInitialState());
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

    // 1. Lancer le fetch IA en parallèle (résolu indépendamment de l'anim)
    var pendingAdvice = null;
    var adviceResolved = false;
    var fetchPromise = fetchAdviceFromIA(opts).then(function (advice) {
      pendingAdvice = advice;
      adviceResolved = true;
      return advice;
    });

    // 2. Save les réponses dans sapiProject SANS advice_text. Add la class
    //    .is-awaiting-advice sur la card AVANT le set, pour que le subscribe
    //    qui fire ne déclenche pas un typewriter sur le texte générique.
    var monProjetCard = document.querySelector('.conseiller-card--mon-projet');
    if (monProjetCard) monProjetCard.classList.add('is-awaiting-advice');
    if (window.sapiProject) {
      window.sapiProject.set(state.answers, state.labels);
    }

    // 3. Lancer la séquence d'animation (fade-out contenu → fade-out modale →
    //    scroll vers la card). La modale + sa logique sont nettoyées dedans.
    runExitSequence(monProjetCard).then(function () {
      state.open = false;
      exitChatMode();

      // 4. Refilter la grille
      if (typeof window.sapiShopRefilter === 'function') window.sapiShopRefilter();

      // 5. Le texte apparaît maintenant : retire .is-awaiting-advice +
      //    setAdviceText → trigger typewriter via le subscribe cards.
      //    Si l'IA n'est pas encore arrivée, on patiente.
      if (adviceResolved) {
        finishAdvice(monProjetCard, pendingAdvice);
      } else {
        fetchPromise.then(function (advice) {
          finishAdvice(monProjetCard, advice);
        });
      }

      state.transition = false;
    });
  }

  // Calcule la meta du filtre à la volée avec les answers donnés (élargissement
  // progressif + IDs matchant). Permet d'envoyer au backend l'image exacte
  // de ce que le visiteur va voir dans la grille.
  function buildFilterMeta(answers) {
    if (window.sapiMegaFilter && typeof window.sapiMegaFilter.computeFilterMeta === 'function') {
      try {
        return window.sapiMegaFilter.computeFilterMeta(answers || {});
      } catch (e) { /* fallback */ }
    }
    return { effectiveAnswers: answers || {}, ignoredAnswers: [], matchingIds: [] };
  }

  // Audit #7 : démarre une nouvelle requête IA — abort la précédente s'il y en
  // a une en cours. Retourne le signal à passer à sapiSafeFetch.
  function startAiRequest() {
    if (state.aiController) {
      try { state.aiController.abort(); } catch (e) { /* swallow */ }
    }
    state.aiController = new AbortController();
    return state.aiController.signal;
  }
  function clearAiRequest() {
    state.aiController = null;
  }

  // Helper : appel IA dédié, isolé pour pouvoir le tester séparément
  function fetchAdviceFromIA(opts) {
    var meta = buildFilterMeta(state.answers);
    var signal = startAiRequest();

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_advice');
    fd.append('nonce', config.nonce || '');
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('labels',  JSON.stringify(state.labels));
    fd.append('matching_product_ids', JSON.stringify(meta.matchingIds));
    fd.append('ignored_answers', JSON.stringify(meta.ignoredAnswers));
    if (opts.conversation && Array.isArray(opts.conversation) && opts.conversation.length) {
      fd.append('conversation', JSON.stringify(opts.conversation));
    }
    // Sonnet : 25s de timeout (plus lent que Haiku)
    return sapiSafeFetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }, { timeout: 25000, signal: signal })
      .then(function (resp) {
        clearAiRequest();
        if (resp && resp.success && resp.data && typeof resp.data.advice_text === 'string' && resp.data.advice_text) {
          return resp.data.advice_text;
        }
        return null;
      })
      .catch(function (err) {
        clearAiRequest();
        // Pour advice : on garde le fallback générique côté JS (la card "Mon
        // projet" affichera le texte générique de la pièce, pas d'erreur
        // visible). MAIS on reset state.transition pour ne pas bloquer la
        // modale si l'animation de sortie est en cours.
        state.transition = false;
        // eslint-disable-next-line no-console
        console.warn('[sapi] advice fetch fail:', err && err.message);
        return null;
      });
  }

  function finishAdvice(card, advice) {
    if (card) card.classList.remove('is-awaiting-advice');
    if (advice && window.sapiProject) {
      window.sapiProject.setAdviceText(advice);
    } else if (window.sapiProject) {
      // Force un re-render même sans advice pour sortir des dots et afficher
      // le texte générique de la pièce. Le typewriter va se déclencher.
      // Hack : notify manuel via un setAdviceText(null) — pas idéal mais OK.
      window.sapiProject.setAdviceText(null);
    }
  }

  // Séquence de sortie en 3 phases (~2s) :
  //   Phase 1 (0–600ms)     : fade-out du contenu interne (screens)
  //   Phase 2 (500–1100ms)  : fade-out de la modale entière (overlay + dialog)
  //   Phase 3 (1100–1900ms) : scroll smooth de la page pour centrer la card
  //                           "Mon projet" puis resolve (texte apparaît ensuite
  //                           via finishAdvice + typewriter)
  function runExitSequence(targetCard) {
    return new Promise(function (resolve) {
      var modalCard = els.modalCard;
      if (!els.modal || !modalCard) {
        if (els.modal) els.modal.hidden = true;
        resolve();
        return;
      }

      // Phase 1 — Fade-out du contenu interne (screen visible)
      var visibleScreen = els.modal.querySelector('[data-screen]:not([hidden])');
      if (visibleScreen) {
        visibleScreen.style.transition = 'opacity 0.6s ease';
        visibleScreen.style.opacity = '0';
      }

      // Phase 2 — Délai 500ms puis fade-out de la modale entière
      setTimeout(function () {
        els.modal.style.transition = 'background-color 0.6s ease';
        els.modal.style.backgroundColor = 'transparent';
        modalCard.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        modalCard.style.transform = 'scale(0.96)';
        modalCard.style.opacity = '0';
      }, 500);

      // Phase 3 — À 1100ms : hide modale + start scroll smooth
      setTimeout(function () {
        // Cleanup styles inline modale
        els.modal.hidden = true;
        els.modal.style.transition = '';
        els.modal.style.backgroundColor = '';
        modalCard.style.transition = '';
        modalCard.style.opacity = '';
        modalCard.style.transform = '';
        if (visibleScreen) {
          visibleScreen.style.transition = '';
          visibleScreen.style.opacity = '';
        }
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';

        // Scroll smooth pour centrer la card "Mon projet" dans la viewport
        if (targetCard) {
          try {
            var rect = targetCard.getBoundingClientRect();
            var targetY = window.scrollY + rect.top - Math.max(40, (window.innerHeight - rect.height) / 2);
            window.scrollTo({ top: Math.max(0, targetY), behavior: 'smooth' });
          } catch (e) {
            // Fallback navigateur sans behavior:smooth
            targetCard.scrollIntoView();
          }
        }

        // Attendre ~800ms pour laisser le scroll smooth se terminer
        // avant de résoudre (= avant que le texte apparaisse)
        setTimeout(resolve, 800);
      }, 1100);
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

  // F2a-quater : startFreetextFlow supprimé. Le champ texte est intégré dans
  // S0 hybride et submitFromS0Text() bascule directement vers S2.chat.

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

    var signal = startAiRequest();
    // Haiku : 15s de timeout
    sapiSafeFetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }, { timeout: 15000, signal: signal })
      .then(function (resp) {
        clearAiRequest();
        // Audit #7 : garde-fou DOM démonté (modale fermée pendant le fetch)
        if (!state.open) return;
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
      .catch(function (err) {
        clearAiRequest();
        // Aborted (modal close ou replaced) : silence — la modale est fermée ou un nouveau fetch a démarré
        if (err && (err.message === 'aborted' || err.message === 'timeout')) {
          if (err.message === 'timeout' && state.open) {
            removeThinkingBubble();
            state.chat.status = 'idle';
            setChatFooterState('idle');
            addRobinBubble('Le serveur ne répond pas. Tu peux réessayer ou me contacter via le formulaire.');
          }
          return;
        }
        if (!state.open) return;
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Je n\'arrive pas à te répondre pour l\'instant. Tu peux réessayer ou me contacter via le formulaire.');
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

    var meta = buildFilterMeta(state.answers);

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_chat');
    fd.append('nonce', config.nonce || '');
    fd.append('user_message', text);
    fd.append('matching_product_ids', JSON.stringify(meta.matchingIds));
    fd.append('ignored_answers', JSON.stringify(meta.ignoredAnswers));
    fd.append('current_filters', JSON.stringify(state.answers));
    fd.append('conversation', JSON.stringify(state.chat.conversation));
    if (state.chat.sessionId) fd.append('session_id', state.chat.sessionId);

    var signal = startAiRequest();
    // Sonnet : 25s de timeout (plus lent que Haiku)
    sapiSafeFetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' }, { timeout: 25000, signal: signal })
      .then(function (resp) {
        clearAiRequest();
        // Audit #7 : garde-fou DOM démonté (modale fermée pendant le fetch)
        if (!state.open) return;
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
      .catch(function (err) {
        clearAiRequest();
        if (err && (err.message === 'aborted' || err.message === 'timeout')) {
          if (err.message === 'timeout' && state.open) {
            removeThinkingBubble();
            state.chat.status = 'idle';
            setChatFooterState('idle');
            addRobinBubble('Le serveur ne répond pas. Tu peux réessayer ou me contacter via le formulaire.');
          }
          return;
        }
        if (!state.open) return;
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Je n\'arrive pas à te répondre pour l\'instant. Tu peux réessayer ou me contacter via le formulaire.');
      });
  }

  /* ─────────────────────────────────────────────
     F2a-quater — S0 hybride (question + choices + "ou" + texte libre)
     Remplace l'ancien S0 "Que préfères-tu ?" avec 2 portes.
     3 sous-états selon sapiProject : initial / partiel / complet (S3).
     ───────────────────────────────────────────── */

  // Décide quel écran afficher quand on ouvre la modale via state="s0".
  // Renvoie 's0-initial' | 's0-partiel' | 's3-carrefour'.
  function determineInitialState() {
    if (!window.sapiProject || !window.sapiProject.hasProject()) return 's0-initial';
    var visible = getVisibleStepIds(state.answers);
    if (visible.length === 0) return 's0-initial';
    var anyAnswered = false;
    var allAnswered = true;
    for (var i = 0; i < visible.length; i++) {
      if (state.answers[visible[i]]) anyAnswered = true;
      else allAnswered = false;
    }
    if (allAnswered) return 's3-carrefour';
    if (anyAnswered) return 's0-partiel';
    return 's0-initial';
  }

  // Trouve la prochaine question visible non répondue (1re question si initial).
  function getNextUnansweredVisibleStep() {
    var visible = getVisibleStepIds(state.answers);
    for (var i = 0; i < visible.length; i++) {
      if (!state.answers[visible[i]]) return visible[i];
    }
    return null;
  }

  // Peuple le S0 hybride selon le mode (initial ou partiel) et l'affiche.
  function renderS0Hybrid(mode) {
    var nextStepId, badgeText, placeholderText, resetVisible;

    if (mode === 's0-partiel') {
      nextStepId = getNextUnansweredVisibleStep() || 'piece';
      badgeText = 'Mon projet';
      placeholderText = 'Précise ton projet en quelques mots…';
      resetVisible = true;
    } else {
      // 's0-initial' (fallback)
      var visible = getVisibleStepIds(state.answers);
      nextStepId = visible[0] || 'piece';
      badgeText = 'Conseil de Robin';
      placeholderText = 'Décris ton projet en quelques mots…';
      resetVisible = false;
    }

    // Update badge text
    if (els.s0BadgeText) els.s0BadgeText.textContent = badgeText;

    // Update question + choices
    var step = getStep(nextStepId);
    if (step) {
      state.currentQuestion = nextStepId;
      if (els.s0Question) els.s0Question.textContent = getDynamicQuestion(step);
      if (els.s0Choices) {
        els.s0Choices.innerHTML = '';
        var choices = step.choices || [];
        // F2a-quater : 2 cols quand 2 ou 4 choix pour éviter les items isolés
        els.s0Choices.classList.toggle('conseiller-choices--2col', choices.length === 2 || choices.length === 4);
        choices.forEach(function (choice) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'conseiller-choice';
          btn.setAttribute('data-choice', choice.slug);
          btn.setAttribute('data-label', choice.label);
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
          els.s0Choices.appendChild(btn);
        });
      }
    }

    // Placeholder + value reset du champ texte
    if (els.s0Input) {
      els.s0Input.placeholder = placeholderText;
      els.s0Input.value = '';
    }

    // Toggle reset link (visible uniquement état partiel)
    if (els.s0ResetWrap) els.s0ResetWrap.hidden = !resetVisible;

    // Reset questionHistory : si state partiel, on pré-remplit avec les
    // questions déjà répondues avant la prochaine (pour permettre Retour).
    state.questionHistory = [];
    if (mode === 's0-partiel') {
      var visibleSteps = getVisibleStepIds(state.answers);
      for (var i = 0; i < visibleSteps.length; i++) {
        if (visibleSteps[i] === nextStepId) break;
        state.questionHistory.push(visibleSteps[i]);
      }
    }

    showScreen('s0');
  }

  // Soumission du champ texte S0 → bascule vers S2.chat avec bulle initiale.
  function submitFromS0Text(text) {
    text = (text || '').trim();
    if (!text) return;

    // Reset complet de l'état chat
    state.chat.conversation = [];
    state.chat.sessionId = null;
    state.chat.status = 'idle';
    if (els.chatMessages) els.chatMessages.innerHTML = '';
    if (els.chatCta) els.chatCta.hidden = true;
    if (els.chatInput) {
      els.chatInput.value = '';
      els.chatInput.disabled = false;
      if (els.chatInputDefaultPlaceholder) {
        els.chatInput.placeholder = els.chatInputDefaultPlaceholder;
      }
    }
    if (els.chatSend) els.chatSend.disabled = false;

    // Bulle initiale Robin (cosmétique, construite côté client — zéro IA)
    var greeting = getInitialChatGreeting();
    enterChatMode();
    addRobinBubble(greeting);
    state.chat.conversation.push({ role: 'assistant', content: greeting });

    // Soumet le texte saisi via le flow freetext existant (Haiku + transition)
    submitFreetext(text);
  }

  // Bulle d'accueil Robin selon l'état du projet (zéro appel IA).
  function getInitialChatGreeting() {
    if (!window.sapiProject || !window.sapiProject.hasProject()) {
      return 'Décris-moi ton projet, je vais t\'aider à trouver une sélection adaptée.';
    }
    return getInitialChatAdvice() + ' Qu\'est-ce que tu veux affiner ?';
  }

  // Action "Effacer et recommencer" depuis S0 (état partiel) : vide le projet
  // et bascule vers l'état initial dans la même modale. Pas de fermeture.
  function resetFromS0() {
    if (window.sapiProject) window.sapiProject.clear();
    state.answers = {};
    state.labels = {};
    state.questionHistory = [];
    renderS0Hybrid('s0-initial');
  }

  /* ─────────────────────────────────────────────
     S3 — Carrefour "Modifier mon projet" (F2a-ter)
     Chips récap lecture seule + 3 actions : Voir / Préciser / Effacer.
     Aucun appel IA — la bulle initiale en mode "Préciser" est construite
     côté client à partir de sapiProject.advice_text déjà stocké.
     ───────────────────────────────────────────── */

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

  function showS3Recap() {
    populateRecapChips();
    showScreen('s3');
  }

  // Action "Voir la sélection" depuis S3 : ferme la modale + scroll grille.
  // Projet inchangé, pas d'appel IA.
  function viewSelectionFromS3() {
    closeModal();
    var grid = document.getElementById('sapi-product-grid');
    if (grid && grid.scrollIntoView) {
      grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  // Action "Préciser avec Robin" depuis S3 : bascule vers S2.chat avec une
  // bulle initiale construite à partir de sapiProject.advice_text (zéro IA).
  function refineFromS3() {
    state.chat.conversation = [];
    state.chat.sessionId = null;
    state.chat.status = 'idle';
    if (els.chatMessages) els.chatMessages.innerHTML = '';
    if (els.chatCta) els.chatCta.hidden = false; // CTA visible direct (advice est déjà en mémoire)
    if (els.chatInput) {
      els.chatInput.value = '';
      els.chatInput.disabled = false;
      if (els.chatInputDefaultPlaceholder) {
        els.chatInput.placeholder = els.chatInputDefaultPlaceholder;
      }
    }
    if (els.chatSend) els.chatSend.disabled = false;

    var initialMsg = getInitialChatAdvice() + ' Qu\'est-ce que tu veux affiner ?';
    enterChatMode();
    addRobinBubble(initialMsg);
    state.chat.conversation.push({ role: 'assistant', content: initialMsg });

    setTimeout(function () {
      if (els.chatInput) els.chatInput.focus();
    }, 100);
  }

  // Récupère le texte conseil à utiliser dans la bulle initiale chat :
  // priorité advice_text → texte générique de la pièce (depuis SAPI_CARDS_CONSEILLER)
  // → fallback ultime.
  function getInitialChatAdvice() {
    var project = window.sapiProject ? window.sapiProject.get() : null;
    if (project && typeof project.advice_text === 'string' && project.advice_text) {
      return project.advice_text;
    }
    var piece = project && project.answers && project.answers.piece;
    var cardsConfig = window.SAPI_CARDS_CONSEILLER || {};
    var generics = cardsConfig.genericAdvice || {};
    if (piece && generics[piece]) return generics[piece];
    return cardsConfig.fallbackAdvice || 'Voici ma sélection pour ton projet.';
  }

  // Action "Effacer et recommencer" depuis S3 : vide sapiProject + revient
  // à S0 hybride en mode initial (peuplé via renderS0Hybrid pour avoir le
  // badge, la question, les choices et le placeholder corrects).
  function resetFromS3() {
    if (window.sapiProject) {
      window.sapiProject.clear();
    }
    state.answers = {};
    state.labels = {};
    state.questionHistory = [];
    renderS0Hybrid('s0-initial');
  }

  /* ─────────────────────────────────────────────
     F2b Phase 2 — s-product-recap : récap fiche produit, 100% statique
     Pattern repris du legacy renderProductGuideResult() pré-F1c :
       - intro construite côté client (pas d'IA)
       - récap Essence + Taille (label lu depuis le select WC du produit)
       - conseil de style fixe (mapping styleConseils localisé via PHP)
     ───────────────────────────────────────────── */

  // Lit le label de l'option taille effectivement disponible sur le produit
  // (correspondant à l'index dérivé du projet). Renvoie '' si pas de match.
  function readTailleLabelFromProductSelect(answers) {
    var sel = document.querySelector('select[name="attribute_pa_taille"]');
    if (!sel) return '';
    var options = [];
    for (var i = 0; i < sel.options.length; i++) {
      if (sel.options[i].value) options.push(sel.options[i]);
    }
    if (!options.length) return '';
    var taille = answers.taille || (answers.piece === 'escalier' && answers.taille_escalier === 'ouvert' ? 'moyenne' : '');
    if (!(taille in TAILLE_TO_INDEX)) return '';
    var idx = Math.min(TAILLE_TO_INDEX[taille], options.length - 1);
    return (options[idx].textContent || options[idx].text || '').trim();
  }

  // Construit l'intro "Pour votre <pièce>, Robin recommande :"
  function buildRecapIntro(answers, labels) {
    var pieceLbl = (labels.piece || '').toLowerCase();
    return 'Pour votre ' + pieceLbl + ', Robin recommande :';
  }

  // Affiche l'écran s-product-recap (immédiat, aucun fetch).
  function showProductRecap() {
    state.shortMode = true;

    var answers = state.answers;
    var labels = state.labels;
    var style = answers.style;
    var essence = ESSENCE_FROM_STYLE[style] || null;
    var essenceLabel = essence ? ESSENCE_LABEL[essence] : '';
    var tailleLabel = readTailleLabelFromProductSelect(answers);

    // Intro
    if (els.productRecapIntro) {
      els.productRecapIntro.textContent = buildRecapIntro(answers, labels);
    }

    // Récap card : Essence + Taille (chacune masquée si non disponible)
    var hasEssence = !!essence;
    var hasTaille = !!tailleLabel;
    if (els.productRecapCard) els.productRecapCard.hidden = !(hasEssence || hasTaille);
    if (els.productRecapEssence) {
      els.productRecapEssence.hidden = !hasEssence;
      if (hasEssence && els.productRecapEssenceValue) {
        els.productRecapEssenceValue.textContent = essenceLabel;
      }
    }
    if (els.productRecapTaille) {
      els.productRecapTaille.hidden = !hasTaille;
      if (hasTaille && els.productRecapTailleValue) {
        els.productRecapTailleValue.textContent = tailleLabel;
      }
    }

    // Conseil de style (texte fixe pré-généré)
    if (els.productRecapConseil) {
      var conseil = (style && STYLE_CONSEILS[style]) || '';
      els.productRecapConseil.textContent = conseil;
      els.productRecapConseil.hidden = !conseil;
    }

    // Conseil de taille (mirror conseil de style — texte fixe pré-généré).
    // Slug dérivé : pour escalier, taille_escalier=ouvert→grande, standard→petite.
    if (els.productRecapConseilTaille) {
      var tailleSlug = answers.taille || '';
      if (!tailleSlug && answers.piece === 'escalier' && answers.taille_escalier) {
        tailleSlug = answers.taille_escalier === 'ouvert' ? 'grande' : 'petite';
      }
      var conseilTaille = (tailleSlug && SIZE_CONSEILS[tailleSlug]) || '';
      els.productRecapConseilTaille.textContent = conseilTaille;
      els.productRecapConseilTaille.hidden = !conseilTaille;
    }

    showScreen('s-product-recap');
  }

  // CTA "Appliquer cette sélection" : ferme la modale, dispatch un event pour
  // que la fiche produit applique la pré-sélection variation.
  function applyProductSelection() {
    var detail = {
      productId: PRODUCT_CTX && PRODUCT_CTX.id ? PRODUCT_CTX.id : 0,
      answers: state.answers,
      labels: state.labels,
    };
    document.dispatchEvent(new CustomEvent('sapi:apply-product-selection', { detail: detail }));
    closeModal();
    // Scroll smooth vers les variations WC pour montrer le résultat
    setTimeout(function () {
      var form = document.querySelector('form.variations_form');
      if (form && form.scrollIntoView) {
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }, 150);
  }

  // Action "Modifier mes réponses" depuis s-product-recap : reset complet,
  // pattern éprouvé pré-F1c (renderProductGuideResult > redoBtn) — efface le
  // projet entièrement et redémarre le parcours court à la 1re question.
  function modifyProductAnswers() {
    if (window.sapiProject) window.sapiProject.clear();
    state.answers = {};
    state.labels = {};
    state.questionHistory = [];
    renderS0Hybrid('s0-initial');
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
    // F2b Phase 2 — Active le mode court UNIQUEMENT pour l'état "product".
    // Doit être positionné AVANT hydrateFromProject pour que cleanInvisibleAnswers
    // utilise la bonne liste de visibles (sinon des steps non-court restent en answers).
    state.shortMode = (initialScreen === 'product');

    hydrateFromProject();
    state.questionHistory = [];
    state.transition = false;

    state.open = true;
    els.modal.hidden = false;
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    // F2a-quater : state="s0" → détermine dynamiquement le sous-état
    //   (initial / partiel / s3-carrefour) selon le contenu du sapiProject.
    // F2a-ter : state="s3" force le carrefour (compat avec anciens liens).
    // F2b Phase 2 : state="product" → mode court fiche produit.
    //   - Si tous les steps courts sont répondus → directement s-product-recap
    //   - Sinon → S0 hybride avec mode court actif (la prochaine question est
    //     la 1re question du parcours court non répondue)
    if (initialScreen === 'product') {
      var visible = getVisibleStepIds(state.answers); // filtré short mode
      var allAnswered = visible.length > 0 && visible.every(function (id) { return !!state.answers[id]; });
      if (allAnswered) {
        showProductRecap();
      } else {
        var anyAnswered = visible.some(function (id) { return !!state.answers[id]; });
        renderS0Hybrid(anyAnswered ? 's0-partiel' : 's0-initial');
      }
    } else if (initialScreen === 's3' && window.sapiProject && window.sapiProject.hasProject()) {
      showS3Recap();
    } else if (initialScreen === 's0' || !initialScreen) {
      var detected = determineInitialState();
      if (detected === 's3-carrefour') {
        showS3Recap();
      } else {
        renderS0Hybrid(detected); // 's0-initial' ou 's0-partiel'
      }
    } else {
      // Fallback ultime (autres valeurs anciennes) → S0 hybride
      renderS0Hybrid(determineInitialState());
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
    // Audit #7 : abort tout fetch IA en cours (chat/freetext) — évite que la
    // réponse arrive après la fermeture et tente d'écrire dans le DOM démonté.
    if (state.aiController) {
      try { state.aiController.abort(); } catch (e) { /* swallow */ }
      state.aiController = null;
    }
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

    // ESC pour fermer — désactivé pendant l'animation morph (state.transition)
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && state.open && !state.transition) {
        e.preventDefault();
        closeModal();
      }
    });

    if (!els.modal) return;

    // Délégation : clics dans la modale (close, door, choice, back, apply)
    els.modal.addEventListener('click', function (e) {
      // Pendant l'animation morph, on ignore les clics pour ne pas casser
      if (state.transition) return;
      // Click sur l'overlay (en dehors du dialog) → ferme
      if (e.target === els.modal) {
        closeModal();
        return;
      }

      var actionBtn = e.target.closest('[data-action]');
      if (!actionBtn) return;
      var action = actionBtn.getAttribute('data-action');

      switch (action) {
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
        // F2a-ter : 3 actions du carrefour S3 "Modifier mon projet"
        case 's3-view':
          viewSelectionFromS3();
          break;
        case 's3-refine':
          refineFromS3();
          break;
        case 's3-reset':
          resetFromS3();
          break;
        // F2a-quater : lien "Effacer et recommencer" sur S0 hybride (état partiel)
        case 's0-reset':
          resetFromS0();
          break;
        // F2b Phase 2 : actions de l'écran s-product-recap
        case 'product-apply':
          applyProductSelection();
          break;
        case 'product-modify':
          modifyProductAnswers();
          break;
      }
    });

    // Click sur un choix (S0 hybride OU S1) — délégué sur toute la modale
    // pour couvrir les 2 contextes (refs DOM distinctes pour S0 et S1).
    els.modal.addEventListener('click', function (e) {
      var btn = e.target.closest('.conseiller-choice');
      if (!btn) return;
      var slug = btn.getAttribute('data-choice');
      var label = btn.getAttribute('data-label') || btn.textContent.trim();
      answerCurrentQuestion(slug, label);
    });

    // F2a-quater : submit du champ texte S0 hybride → bascule vers S2.chat
    var s0Form = els.modal.querySelector('[data-s0-form]');
    if (s0Form) {
      s0Form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!els.s0Input) return;
        var val = els.s0Input.value;
        els.s0Input.value = '';
        submitFromS0Text(val);
      });
    }

    // S2.chat : submit message dans le footer chat
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
    // S0 hybride (F2a-quater)
    els.s0BadgeText   = els.modal.querySelector('[data-s0-badge-text]');
    els.s0Question    = els.modal.querySelector('[data-s0-question]');
    els.s0Choices     = els.modal.querySelector('[data-s0-choices]');
    els.s0Input       = els.modal.querySelector('[data-s0-input]');
    els.s0ResetWrap   = els.modal.querySelector('[data-s0-reset-wrap]');
    // S1 (questions guidées)
    els.questionTitle = els.modal.querySelector('[data-question-title]');
    els.choices       = els.modal.querySelector('[data-choices]');
    els.progressFill  = els.modal.querySelector('[data-progress-fill]');
    // S2 chat
    els.chatMessages  = els.modal.querySelector('[data-chat-messages]');
    els.chatCta       = els.modal.querySelector('[data-chat-cta]');
    els.chatInput     = els.modal.querySelector('[data-chat-input]');
    els.chatSend      = els.modal.querySelector('.conseiller-chat-footer__send');
    els.chatInputDefaultPlaceholder = els.chatInput ? els.chatInput.getAttribute('placeholder') : '';
    // S3 carrefour
    els.recapChips    = els.modal.querySelector('[data-recap-chips]');
    // s-product-recap (F2b Phase 2 — récap statique sans IA)
    els.productRecapIntro        = els.modal.querySelector('[data-product-recap-intro]');
    els.productRecapCard         = els.modal.querySelector('[data-product-recap-card]');
    els.productRecapEssence      = els.modal.querySelector('[data-product-recap-essence]');
    els.productRecapEssenceValue = els.modal.querySelector('[data-product-recap-essence-value]');
    els.productRecapTaille       = els.modal.querySelector('[data-product-recap-taille]');
    els.productRecapTailleValue  = els.modal.querySelector('[data-product-recap-taille-value]');
    els.productRecapConseil      = els.modal.querySelector('[data-product-recap-conseil]');
    els.productRecapConseilTaille = els.modal.querySelector('[data-product-recap-conseil-taille]');

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

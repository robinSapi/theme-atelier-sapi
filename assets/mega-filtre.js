/**
 * Sapi Maison — Méga-filtre intelligent (F1a)
 *
 * Filtre client-side de /mes-creations/ piloté par 7 chips conditionnels.
 * Reproduit en JS la logique de inc/guide-data.php + sapi_guide_query_products.
 * Cohabite avec shop.js (pills catégorie + recherche) via window.sapiShopRefilter.
 */
(function () {
  'use strict';

  var config = window.SAPI_MEGAFILTER || {};
  var STEPS = Array.isArray(config.steps) ? config.steps : [];
  var RULES = config.rules || {};

  // ═══ State partagé ═══
  var state = {
    answers: {}, // ex. { piece: 'salon', taille: 'moyenne' }
    labels:  {}, // ex. { piece: 'Salon / Salle à manger' }
    modal: {
      session_id: null,
      conversation: [],   // [{role:'user'|'assistant', content:'...'}]
      ai_call_count: 0,
      status: 'idle',     // 'idle' | 'thinking'
      contact_shown: false,
    },
  };

  // ═══ DOM refs (peuplé dans init) ═══
  var els = {};

  // ═══════════════════════════════════════════════════════════
  //  Visibilité conditionnelle (mirror des règles inc/guide-data.php)
  // ═══════════════════════════════════════════════════════════
  function getVisibleSteps() {
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
            var ans = state.answers[k];
            if (!ans || group[k].indexOf(ans) === -1) { groupOk = false; break; }
          }
          if (groupOk) { orMatch = true; break; }
        }
        if (orMatch) visible.push(step.id);
      } else {
        var show = true;
        for (var key in vis) {
          if (!vis.hasOwnProperty(key)) continue;
          var a = state.answers[key];
          if (!a || vis[key].indexOf(a) === -1) { show = false; break; }
        }
        if (show) visible.push(step.id);
      }
    }
    return visible;
  }

  function cleanInvisibleAnswers() {
    var visible = getVisibleSteps();
    for (var sid in state.answers) {
      if (state.answers.hasOwnProperty(sid) && visible.indexOf(sid) === -1) {
        delete state.answers[sid];
        delete state.labels[sid];
      }
    }
  }

  function hasAnyAnswer() {
    for (var k in state.answers) {
      if (state.answers.hasOwnProperty(k)) return true;
    }
    return false;
  }

  // ═══════════════════════════════════════════════════════════
  //  Logique de filtrage produit (mirror PHP)
  // ═══════════════════════════════════════════════════════════

  // Catégories acceptées selon les réponses (mirror sapi_guide_get_categories)
  function getAcceptedCategories() {
    var a = state.answers;
    var sortie = a.sortie || '';
    var piece  = a.piece  || '';
    var eclairage = a.eclairage || '';

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

  // Filtre ampoule (mirror sapi_guide_get_ampoule_filter)
  function getAmpouleFilter() {
    var a = state.answers;
    var piece = a.piece || '';
    var taille = a.taille || '';
    if (!piece) return null;

    // Grande pièce cuisine/bureau : tous types OK
    if (taille === 'grande' && (RULES.ampoule_skip_when_grande || []).indexOf(piece) !== -1) {
      return null;
    }
    var map = RULES.ampoule_by_piece || {};
    if (Object.prototype.hasOwnProperty.call(map, piece)) {
      return map[piece]; // null = tous, ou array de slugs
    }
    return null;
  }

  // Exclusions de format vertical (mirror sapi_guide_query_products allow_vertical)
  function isVerticalAllowed() {
    var a = state.answers;
    var piece = a.piece || '';
    var taille = a.taille || '';
    var hauteur = a.hauteur || '';
    return (
      piece === 'escalier' ||
      (piece === 'entree' && (hauteur === 'haute' || hauteur === 'confortable')) ||
      (taille === 'petite' && (hauteur === 'haute' || hauteur === 'confortable'))
    );
  }
  function isHorizontalExcluded() {
    var a = state.answers;
    return (
      a.piece === 'escalier' ||
      (a.taille === 'petite' && a.hauteur === 'haute')
    );
  }

  // Une card matche-t-elle le méga-filtre ?
  // Si aucun chip n'a de réponse → tout passe (le méga-filtre est neutre).
  function cardMatches(card) {
    if (!hasAnyAnswer()) return true;

    var catsAttr = card.getAttribute('data-categories') || '';
    var cardCats = catsAttr.split(/\s+/).filter(Boolean);

    // Toujours exclure les extras (accessoires, carte-cadeau) dès qu'un chip répond
    var extras = RULES.extras_slugs || [];
    for (var i = 0; i < extras.length; i++) {
      if (cardCats.indexOf(extras[i]) !== -1) return false;
    }

    // Catégorie
    var accepted = getAcceptedCategories();
    var hasMatchCat = cardCats.some(function (c) { return accepted.indexOf(c) !== -1; });
    if (!hasMatchCat) return false;

    // Exclusions de format (uniquement sur suspensions, mirror PHP)
    var isSuspension = cardCats.indexOf('suspensions') !== -1;
    if (isSuspension) {
      var formatAttr = card.getAttribute('data-format-luminaire') || '';
      var cardFormats = formatAttr.split(/\s+/).filter(Boolean);
      if (cardFormats.indexOf('vertical') !== -1 && !isVerticalAllowed()) return false;
      if (cardFormats.indexOf('horizontal') !== -1 && isHorizontalExcluded()) return false;
    }

    // Filtre ampoule
    var ampouleFilter = getAmpouleFilter();
    if (ampouleFilter && ampouleFilter.length) {
      var ampAttr = card.getAttribute('data-type-ampoule') || '';
      var cardAmp = ampAttr.split(/\s+/).filter(Boolean);
      var ampOk = cardAmp.some(function (t) { return ampouleFilter.indexOf(t) !== -1; });
      if (!ampOk) return false;
    }

    return true;
  }

  // ═══════════════════════════════════════════════════════════
  //  Rendu des chips
  // ═══════════════════════════════════════════════════════════
  function renderChips() {
    if (!els.chipsContainer) return;
    var visible = getVisibleSteps();
    var chips = els.chipsContainer.querySelectorAll('.megafilter-chip');

    chips.forEach(function (chip) {
      var sid = chip.dataset.step;
      var isVisible = visible.indexOf(sid) !== -1;
      var hasValue = !!state.answers[sid];

      if (chip.classList.contains('is-conditional')) {
        chip.classList.toggle('is-visible', isVisible);
      }
      chip.classList.toggle('has-value', hasValue);

      var labelEl = chip.querySelector('.megafilter-chip-label');
      var valueEl = chip.querySelector('.megafilter-chip-value');
      var arrow = chip.querySelector('.megafilter-chip-arrow');

      if (hasValue) {
        // Chip répondu : on affiche uniquement la valeur (la question disparaît)
        // Le chevron reste : il signale qu'on peut toujours changer la réponse.
        var displayLabel = state.labels[sid] || state.answers[sid];
        if (valueEl) {
          valueEl.textContent = displayLabel;
          valueEl.hidden = false;
        }
        if (labelEl) labelEl.hidden = true;
      } else {
        // Chip vide : on affiche la question (label) + flèche
        if (valueEl) {
          valueEl.textContent = '';
          valueEl.hidden = true;
        }
        if (labelEl) {
          labelEl.textContent = getChipLabel(sid);
          labelEl.hidden = false;
        }
      }

      // Mettre à jour les options du menu (highlight sélection courante)
      var options = chip.querySelectorAll('.megafilter-chip-option');
      options.forEach(function (opt) {
        opt.classList.toggle('is-selected', opt.dataset.value === state.answers[sid]);
      });
    });
  }

  function getChipLabel(sid) {
    // Récupère le label depuis le DOM (déjà rendu côté PHP)
    var chip = els.chipsContainer && els.chipsContainer.querySelector('.megafilter-chip[data-step="' + sid + '"]');
    if (!chip) return sid;
    // Récupère le label original avant ajout de " :"
    if (!chip.dataset.originalLabel) {
      var initial = chip.querySelector('.megafilter-chip-label');
      chip.dataset.originalLabel = initial ? initial.textContent.replace(/\s*:\s*$/, '') : sid;
    }
    return chip.dataset.originalLabel;
  }

  function closeAllMenus(except) {
    if (!els.chipsContainer) return;
    var menus = els.chipsContainer.querySelectorAll('.megafilter-chip-menu');
    menus.forEach(function (m) {
      if (m === except) return;
      m.hidden = true;
      var parent = m.closest('.megafilter-chip');
      if (parent) {
        parent.classList.remove('is-open');
        var toggle = parent.querySelector('.megafilter-chip-toggle');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function openChipMenu(chip) {
    var menu = chip.querySelector('.megafilter-chip-menu');
    if (!menu) return;
    var isOpen = !menu.hidden;
    closeAllMenus(isOpen ? null : menu);
    menu.hidden = isOpen;
    chip.classList.toggle('is-open', !isOpen);
    var toggle = chip.querySelector('.megafilter-chip-toggle');
    if (toggle) toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
  }

  // ═══════════════════════════════════════════════════════════
  //  State mutations
  // ═══════════════════════════════════════════════════════════
  function setAnswer(stepId, value, label) {
    state.answers[stepId] = value;
    state.labels[stepId] = label || value;
    cleanInvisibleAnswers();
    onStateChange();
  }

  function clearAnswer(stepId) {
    delete state.answers[stepId];
    delete state.labels[stepId];
    cleanInvisibleAnswers();
    onStateChange();
  }

  function clearAllChips() {
    state.answers = {};
    state.labels = {};
    onStateChange();
  }

  function onStateChange() {
    renderChips();
    applyFiltersToGrid();
  }

  // Récupère le label humain d'un slug dans STEPS (utilisé quand l'IA renvoie un slug brut)
  function getChoiceLabel(stepId, slug) {
    for (var i = 0; i < STEPS.length; i++) {
      if (STEPS[i].id !== stepId) continue;
      var choices = STEPS[i].choices || [];
      for (var j = 0; j < choices.length; j++) {
        if (choices[j].slug === slug) return choices[j].label;
      }
    }
    return slug;
  }

  // Applique un batch de filtres (ajout / mise à jour / suppression via null) en un seul render
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
    onStateChange();
  }

  // ═══════════════════════════════════════════════════════════
  //  Application des filtres à la grille
  // ═══════════════════════════════════════════════════════════
  function applyFiltersToGrid() {
    // Affiche / masque le bouton "Tout effacer" selon qu'au moins un chip est répondu
    if (els.reset) els.reset.hidden = !hasAnyAnswer();

    // Délègue à shop.js pour appliquer le pipeline complet (catégorie + recherche + méga)
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

  // ═══════════════════════════════════════════════════════════
  //  Modale "Décrire mon projet" (F1b — IA Haiku + Sonnet)
  // ═══════════════════════════════════════════════════════════
  function openModal() {
    if (!els.modal) return;
    els.modal.hidden = false;
    document.body.style.overflow = 'hidden';
    var input = document.getElementById('megafilter-modal-input-initial');
    if (input) setTimeout(function () { input.focus(); }, 50);
  }

  function closeModal() {
    if (!els.modal) return;
    logSession();
    els.modal.hidden = true;
    document.body.style.overflow = '';
    resetModalState();
  }

  function resetModalState() {
    var start  = document.getElementById('megafilter-modal-start');
    var chat   = document.getElementById('megafilter-modal-chat');
    var ret    = document.getElementById('megafilter-modal-return');
    var footer = document.getElementById('megafilter-modal-footer');
    if (start)  start.hidden  = false;
    if (chat)   { chat.hidden = true; chat.innerHTML = ''; }
    if (ret)    ret.hidden    = true;
    if (footer) footer.hidden = true;

    var startInput  = document.getElementById('megafilter-modal-input-initial');
    var footerInput = document.getElementById('megafilter-modal-input-footer');
    if (startInput)  startInput.value = '';
    if (footerInput) { footerInput.value = ''; footerInput.disabled = false; }

    var sendBtn = document.getElementById('megafilter-modal-send');
    if (sendBtn) sendBtn.disabled = false;

    // Réinitialise le state modale (nouvelle session si réouverture)
    state.modal = {
      session_id: null,
      conversation: [],
      ai_call_count: 0,
      status: 'idle',
      contact_shown: false,
    };
  }

  // ─── Affichage des bulles ───
  function showChatPanel() {
    var start  = document.getElementById('megafilter-modal-start');
    var chat   = document.getElementById('megafilter-modal-chat');
    var footer = document.getElementById('megafilter-modal-footer');
    if (start)  start.hidden  = true;
    if (chat)   chat.hidden   = false;
    if (footer) footer.hidden = false;
  }

  function addUserBubble(text) {
    var chat = document.getElementById('megafilter-modal-chat');
    if (!chat) return;
    var wrap = document.createElement('div');
    wrap.className = 'megafilter-chat-msg megafilter-chat-msg--user';
    var bubble = document.createElement('div');
    bubble.className = 'megafilter-chat-bubble';
    bubble.textContent = text;
    wrap.appendChild(bubble);
    chat.appendChild(wrap);
    scrollChatToBottom();
  }

  function addRobinBubble(text, opts) {
    opts = opts || {};
    var chat = document.getElementById('megafilter-modal-chat');
    if (!chat) return;
    var wrap = document.createElement('div');
    wrap.className = 'megafilter-chat-msg megafilter-chat-msg--robin';

    var bubble = document.createElement('div');
    bubble.className = 'megafilter-chat-bubble';
    bubble.textContent = text || '';
    wrap.appendChild(bubble);

    // Encart "Filtres appliqués"
    if (opts.filters && Object.keys(opts.filters).length) {
      var fb = document.createElement('div');
      fb.className = 'megafilter-chat-filters';
      var label = document.createElement('strong');
      label.textContent = 'Filtres appliqués :';
      fb.appendChild(label);
      var list = document.createElement('span');
      var parts = [];
      Object.keys(opts.filters).forEach(function (k) {
        var slug = opts.filters[k];
        if (slug === null) return; // suppression : ne pas afficher
        parts.push(getChipLabel(k) + ' = ' + getChoiceLabel(k, slug));
      });
      if (parts.length) {
        list.textContent = ' ' + parts.join(' · ');
        fb.appendChild(list);
        wrap.appendChild(fb);
      }
    }

    chat.appendChild(wrap);
    scrollChatToBottom();
  }

  function addThinkingBubble() {
    var chat = document.getElementById('megafilter-modal-chat');
    if (!chat) return;
    if (document.getElementById('megafilter-chat-thinking')) return;
    var wrap = document.createElement('div');
    wrap.className = 'megafilter-chat-msg megafilter-chat-msg--robin megafilter-chat-msg--thinking';
    wrap.id = 'megafilter-chat-thinking';
    var bubble = document.createElement('div');
    bubble.className = 'megafilter-chat-bubble megafilter-thinking-bubble';
    bubble.setAttribute('aria-label', 'Robin réfléchit');
    for (var i = 0; i < 3; i++) {
      var dot = document.createElement('span');
      dot.className = 'megafilter-thinking-dot';
      bubble.appendChild(dot);
    }
    wrap.appendChild(bubble);
    chat.appendChild(wrap);
    scrollChatToBottom();
  }

  function removeThinkingBubble() {
    var el = document.getElementById('megafilter-chat-thinking');
    if (el) el.parentNode.removeChild(el);
  }

  function scrollChatToBottom() {
    // C'est .megafilter-modal-body qui scrolle (overflow-y: auto), pas le chat lui-même
    var body = document.getElementById('megafilter-modal-body');
    if (body) body.scrollTop = body.scrollHeight;
  }

  // ─── Compteur "Voir la sélection (X)" ───
  function countVisibleCards() {
    var cards = document.querySelectorAll('.product-card-cinetique');
    var count = 0;
    cards.forEach(function (card) {
      if (!card.classList.contains('is-filtered-out')) count++;
    });
    return count;
  }

  function showReturnButton() {
    var ret = document.getElementById('megafilter-modal-return');
    if (ret) ret.hidden = false;
    updateReturnCount();
  }

  function updateReturnCount() {
    var num = document.getElementById('megafilter-modal-return-num');
    if (num) num.textContent = countVisibleCards();
  }

  // ─── Footer chat enable/disable ───
  function setChatFooterState(stateName) {
    var input = document.getElementById('megafilter-modal-input-footer');
    var btn   = document.getElementById('megafilter-modal-send');
    if (!input || !btn) return;
    if (stateName === 'loading') {
      input.disabled = true;
      btn.disabled = true;
    } else if (stateName === 'locked') {
      input.disabled = true;
      btn.disabled = true;
      input.value = '';
      input.placeholder = 'Tu as atteint la limite. Contacte Robin directement.';
    } else { // 'idle'
      input.disabled = false;
      btn.disabled = false;
    }
  }

  function showContactCta() {
    if (state.modal.contact_shown) return;
    var footer = document.getElementById('megafilter-modal-footer');
    if (!footer) return;
    var cta = document.createElement('a');
    cta.className = 'megafilter-modal-send megafilter-modal-contact';
    cta.href = '/contact/';
    cta.textContent = 'Contacter Robin';
    footer.appendChild(cta);
    state.modal.contact_shown = true;
  }

  // ─── Appel IA : extraction freetext ───
  function submitFreetext(text) {
    if (state.modal.status !== 'idle') return;
    text = (text || '').trim();
    if (!text) return;

    showChatPanel();
    addUserBubble(text);
    addThinkingBubble();
    state.modal.status = 'thinking';
    state.modal.ai_call_count++;
    setChatFooterState('loading');

    var formData = new FormData();
    formData.append('action', 'sapi_megafilter_freetext');
    formData.append('nonce', config.nonce || '');
    formData.append('message', text);
    if (state.modal.session_id) formData.append('session_id', state.modal.session_id);

    fetch(config.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        removeThinkingBubble();
        state.modal.status = 'idle';
        setChatFooterState('idle');

        if (!resp || !resp.success) {
          var fallback = (resp && resp.data && resp.data.fallback) ||
            'Je n\'arrive pas à analyser ton message. Tu peux essayer de répondre directement aux questions ci-dessous, ou me contacter via le formulaire.';
          addRobinBubble(fallback);
          state.modal.conversation.push({ role: 'user', content: text });
          state.modal.conversation.push({ role: 'assistant', content: fallback });
          showContactCta();
          return;
        }

        var data = resp.data || {};
        state.modal.session_id = data.session_id || state.modal.session_id;

        var filters = data.filters || {};
        if (Object.keys(filters).length) {
          // Freetext = nouvelle description complète du projet : on remplace les chips
          // existants par l'interprétation de l'IA (sinon des chips fantômes survivent).
          state.answers = {};
          state.labels  = {};
          applyFiltersBatch(filters);
        }

        addRobinBubble(data.message || '', { filters: filters });
        state.modal.conversation.push({ role: 'user', content: text });
        state.modal.conversation.push({ role: 'assistant', content: data.message || '' });

        showReturnButton();
      })
      .catch(function () {
        removeThinkingBubble();
        state.modal.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Petit souci de connexion. Tu peux réessayer ou me contacter directement.');
        showContactCta();
      });
  }

  // ─── Appel IA : conversation chat ───
  function submitChat(text) {
    if (state.modal.status !== 'idle') return;
    text = (text || '').trim();
    if (!text) return;

    var userMsgCount = 0;
    for (var i = 0; i < state.modal.conversation.length; i++) {
      if (state.modal.conversation[i].role === 'user') userMsgCount++;
    }
    var maxMsg = config.maxMessages || 15;
    if (userMsgCount >= maxMsg) {
      addRobinBubble('On a bien discuté ! Pour aller plus loin, écris-moi directement via le formulaire de contact.');
      showContactCta();
      setChatFooterState('locked');
      return;
    }

    addUserBubble(text);
    addThinkingBubble();
    state.modal.status = 'thinking';
    state.modal.ai_call_count++;
    setChatFooterState('loading');

    var formData = new FormData();
    formData.append('action', 'sapi_megafilter_chat');
    formData.append('nonce', config.nonce || '');
    formData.append('user_message', text);
    formData.append('current_filters', JSON.stringify(state.answers));
    formData.append('conversation', JSON.stringify(state.modal.conversation));
    if (state.modal.session_id) formData.append('session_id', state.modal.session_id);

    fetch(config.ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        removeThinkingBubble();
        state.modal.status = 'idle';
        setChatFooterState('idle');

        if (!resp || !resp.success) {
          var fallback = (resp && resp.data && resp.data.fallback) ||
            'Je n\'arrive pas à te répondre pour l\'instant. Tu peux me contacter directement.';
          addRobinBubble(fallback);
          state.modal.conversation.push({ role: 'user', content: text });
          state.modal.conversation.push({ role: 'assistant', content: fallback });
          showContactCta();
          return;
        }

        var data = resp.data || {};
        state.modal.session_id = data.session_id || state.modal.session_id;

        if (data.filters_update) {
          applyFiltersBatch(data.filters_update);
          updateReturnCount();
        }

        addRobinBubble(data.message || '', { filters: data.filters_update });

        if (Array.isArray(data.conversation)) {
          state.modal.conversation = data.conversation;
        } else {
          state.modal.conversation.push({ role: 'user', content: text });
          state.modal.conversation.push({ role: 'assistant', content: data.message || '' });
        }

        if (data.action === 'contact') {
          showContactCta();
        }

        showReturnButton();
      })
      .catch(function () {
        removeThinkingBubble();
        state.modal.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Petit souci de connexion. Tu peux réessayer ou me contacter directement.');
      });
  }

  // ─── Logging session (sendBeacon à la fermeture) ───
  function logSession() {
    if (!state.modal.session_id || state.modal.ai_call_count === 0) return;

    var convStr = '';
    state.modal.conversation.forEach(function (m) {
      convStr += (m.role === 'user' ? 'U: ' : 'R: ') + m.content + '\n';
    });

    var payload = {
      action: 'sapi_robin_log_session',
      nonce: config.logNonce || '',
      session_id: state.modal.session_id,
      opening_context: 'megafilter',
      answers: JSON.stringify(state.answers),
      completion: hasAnyAnswer() ? 'complete' : 'partial',
      filter_activated: '1',
      ai_call_count: String(state.modal.ai_call_count),
      conversation: convStr,
      reco_shown: '1',
    };

    try {
      var fd = new FormData();
      Object.keys(payload).forEach(function (k) { fd.append(k, payload[k]); });
      if (navigator.sendBeacon) {
        navigator.sendBeacon(config.ajaxUrl, fd);
        return;
      }
    } catch (e) { /* fallthrough */ }

    fetch(config.ajaxUrl, {
      method: 'POST',
      body: new URLSearchParams(payload),
      credentials: 'same-origin',
      keepalive: true,
    }).catch(function () {});
  }

  // ═══════════════════════════════════════════════════════════
  //  Query params au load — ex. /mes-creations/?piece=salon
  // ═══════════════════════════════════════════════════════════
  function readQueryParams() {
    try {
      var params = new URLSearchParams(window.location.search);
      STEPS.forEach(function (step) {
        var val = params.get(step.id);
        if (!val) return;
        // Vérifie que la valeur est un choix valide
        var choice = (step.choices || []).find(function (c) { return c.slug === val; });
        if (!choice) return;
        state.answers[step.id] = val;
        state.labels[step.id]  = choice.label;
      });
      // Nettoie les réponses incohérentes avec les visibilités
      cleanInvisibleAnswers();
    } catch (e) {
      // URLSearchParams indisponible ou erreur silencieuse
    }
  }

  // ═══════════════════════════════════════════════════════════
  //  Event bindings
  // ═══════════════════════════════════════════════════════════
  function bindEvents() {
    // Délégation : ouverture menu chip
    if (els.chipsContainer) {
      els.chipsContainer.addEventListener('click', function (e) {
        var toggle = e.target.closest('.megafilter-chip-toggle');
        var option = e.target.closest('.megafilter-chip-option');

        if (option) {
          var chipFromOpt = option.closest('.megafilter-chip');
          if (!chipFromOpt) return;
          var sid = chipFromOpt.dataset.step;
          var newValue = option.dataset.value;
          // Toggle : re-cliquer sur l'option déjà sélectionnée la décoche
          if (state.answers[sid] === newValue) {
            clearAnswer(sid);
          } else {
            setAnswer(sid, newValue, option.dataset.label || option.textContent.trim());
          }
          closeAllMenus();
          return;
        }

        if (toggle) {
          var chip = toggle.closest('.megafilter-chip');
          if (chip) openChipMenu(chip);
        }
      });
    }

    // Fermer les menus au clic extérieur
    document.addEventListener('click', function (e) {
      if (els.chipsContainer && !els.chipsContainer.contains(e.target)) closeAllMenus();
    });

    // Échap ferme menus + modale
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      closeAllMenus();
      if (els.modal && !els.modal.hidden) closeModal();
    });

    // Bouton "Tout effacer"
    if (els.reset) {
      els.reset.addEventListener('click', function () { clearAllChips(); });
    }

    // Bouton "Décrire précisément mon projet"
    if (els.openAiBtn) {
      els.openAiBtn.addEventListener('click', openModal);
    }

    // Modale : close
    var closeBtn = document.getElementById('megafilter-modal-close');
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    // Modale : input central — Entrée soumet le texte libre
    var startInput = document.getElementById('megafilter-modal-input-initial');
    if (startInput) {
      startInput.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var val = startInput.value;
        startInput.value = '';
        submitFreetext(val);
      });
    }

    // Modale : clic sur suggestion = même chemin que le texte libre
    var suggestions = document.querySelectorAll('.megafilter-modal-sug');
    suggestions.forEach(function (sug) {
      sug.addEventListener('click', function () {
        submitFreetext(sug.textContent.trim());
      });
    });

    // Modale : footer chat — Entrée ou clic Envoyer
    var footerInput = document.getElementById('megafilter-modal-input-footer');
    var sendBtn = document.getElementById('megafilter-modal-send');
    if (footerInput) {
      footerInput.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var val = footerInput.value;
        footerInput.value = '';
        submitChat(val);
      });
    }
    if (sendBtn) {
      sendBtn.addEventListener('click', function () {
        if (!footerInput) return;
        var val = footerInput.value;
        footerInput.value = '';
        submitChat(val);
      });
    }

    // Modale : bouton "Voir la sélection" — ferme et laisse les chips appliqués
    var returnBtn = document.getElementById('megafilter-modal-return-btn');
    if (returnBtn) {
      returnBtn.addEventListener('click', function () {
        closeModal();
        // Scroll smooth vers la grille
        var grid = document.querySelector('.products, .product-list, [class*="product-grid"]');
        if (grid && grid.scrollIntoView) {
          grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    }
  }

  // ═══════════════════════════════════════════════════════════
  //  Init
  // ═══════════════════════════════════════════════════════════
  function init() {
    els.chipsContainer = document.getElementById('megafilter-chips');
    if (!els.chipsContainer) return; // pas sur la page concernée

    els.reset     = document.getElementById('megafilter-reset');
    els.openAiBtn = document.getElementById('megafilter-open-ai');
    els.modal     = document.getElementById('megafilter-modal');

    bindEvents();
    readQueryParams();
    renderChips();
    applyFiltersToGrid();
  }

  // API publique pour shop.js
  window.sapiMegaFilter = {
    cardMatches: cardMatches,
    hasAnyAnswer: hasAnyAnswer,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

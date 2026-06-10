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
  // Round 3 — Lot C2/C4 : URLs pour les CTAs de l'écran s-contact.
  var CONTACT_SURMESURE_URL = config.contactSurmesureUrl || '/sur-mesure/';
  var CONTACT_EMAIL         = config.contactEmail || 'robin@atelier-sapi.fr';

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
    editFromS3: false,    // Round 4 — true quand on édite une chip depuis S3 (retour direct au récap après modif)
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
     SessionTracker — log V3 des sessions Conseiller vers
     `sapi_megafilter_log_session` (UPSERT par session_id).
     4 moments clés : ouverture, transition d'écran, fermeture,
     submit contact. sendBeacon avec fallback fetch keepalive
     pour résilience au unload.
     ───────────────────────────────────────────── */
  var SessionTracker = (function () {
    var sessionId = null;
    var aiCallCount = 0;
    var hasStarted = false;

    function generateSessionId() {
      if (window.crypto && window.crypto.getRandomValues) {
        var bytes = new Uint8Array(8);
        window.crypto.getRandomValues(bytes);
        return 'mfs_' + Array.from(bytes).map(function (b) {
          var h = b.toString(16);
          return h.length === 1 ? '0' + h : h;
        }).join('');
      }
      // Fallback non-cryptographique (très anciens navigateurs)
      return 'mfs_' + Date.now().toString(16) + Math.random().toString(16).slice(2, 10);
    }

    function getSessionId() {
      if (sessionId) return sessionId;
      sessionId = generateSessionId();
      return sessionId;
    }

    function detectEntryPoint() {
      var body = document.body;
      if (body && body.classList.contains('home')) return 'home_picker';
      var path = window.location.pathname || '';
      if (path.indexOf('/mes-creations/') !== -1) {
        try {
          var url = new URL(window.location.href);
          if (url.searchParams.get('freetext')) return 'freetext';
        } catch (e) { /* swallow */ }
        return 'mes_creations';
      }
      if (body && (body.classList.contains('single-product') || path.indexOf('/produit/') !== -1)) {
        return 'product_pill';
      }
      return '';
    }

    function send(payload) {
      if (!config.ajaxUrl) return;
      payload.nonce = config.nonce || '';
      payload.session_id = getSessionId();
      var body;
      try {
        body = JSON.stringify(payload);
      } catch (e) { return; }
      // L'action doit être en query string : admin-ajax.php route via
      // $_REQUEST['action'] (lu depuis $_GET/$_POST). En envoyant du JSON
      // brut via php://input, l'action n'arriverait pas dans $_REQUEST.
      var sep = config.ajaxUrl.indexOf('?') > -1 ? '&' : '?';
      var url = config.ajaxUrl + sep + 'action=sapi_megafilter_log_session';
      // sendBeacon — résilient au unload (fermeture modale + navigation).
      if (navigator.sendBeacon) {
        try {
          var blob = new Blob([body], { type: 'application/json' });
          if (navigator.sendBeacon(url, blob)) return;
        } catch (e) { /* fallback ci-dessous */ }
      }
      // Fallback fetch keepalive (Chrome/Firefox modernes).
      try {
        fetch(url, {
          method: 'POST',
          body: body,
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          keepalive: true,
        }).catch(function () { /* swallow */ });
      } catch (e) { /* swallow */ }
    }

    function getMatchingProductIds() {
      // Scan DOM de la grille /mes-creations/ : cards WC ont une classe
      // `post-<id>` sur le <li.product>. Renvoie un CSV des IDs visibles
      // (filtrés is-filtered-out exclus si présent).
      var cards = document.querySelectorAll('ul.products li.product');
      if (!cards.length) return '';
      var ids = [];
      cards.forEach(function (card) {
        if (card.classList && card.classList.contains('is-filtered-out')) return;
        var m = card.className.match(/post-(\d+)/);
        if (m) ids.push(m[1]);
      });
      return ids.join(',');
    }

    function buildSnapshotPayload() {
      var payload = {};
      var project = window.sapiProject && window.sapiProject.get ? window.sapiProject.get() : null;
      if (project) {
        if (project.answers && Object.keys(project.answers).length) {
          payload.answers = project.answers;
        }
        if (project.advice_text) payload.advice_text = project.advice_text;
        if (project.contact_kind) payload.contact_kind = project.contact_kind;
        if (project.contact_subject) payload.contact_subject = project.contact_subject;
        if (project.contact_message) payload.contact_message = project.contact_message;
      }
      // answers_completed : toutes les questions visibles répondues
      if (project && project.answers) {
        try {
          var visible = getVisibleStepIds(project.answers);
          payload.answers_completed = (visible.length > 0 && visible.every(function (id) {
            return !!project.answers[id];
          })) ? 1 : 0;
        } catch (e) { /* swallow */ }
      }
      // Conversation chat
      if (state.chat && state.chat.conversation && state.chat.conversation.length) {
        payload.ai_chat_messages = state.chat.conversation;
      }
      if (aiCallCount > 0) payload.ai_call_count = aiCallCount;
      // Produits matchés (uniquement sur /mes-creations/)
      var ids = getMatchingProductIds();
      if (ids) payload.matching_product_ids = ids;
      return payload;
    }

    function start() {
      // Reset compteurs pour la session courante.
      aiCallCount = 0;
      hasStarted = true;
      send({
        entry_point: detectEntryPoint(),
        entry_url: window.location.pathname + window.location.search,
      });
    }

    function snapshot(extra) {
      if (!hasStarted) return;
      var payload = buildSnapshotPayload();
      if (extra && typeof extra === 'object') {
        Object.keys(extra).forEach(function (k) { payload[k] = extra[k]; });
      }
      send(payload);
    }

    function finalize() {
      if (!hasStarted) return;
      send(buildSnapshotPayload());
      hasStarted = false;
    }

    function incrementAiCallCount() {
      aiCallCount++;
    }

    return {
      start: start,
      snapshot: snapshot,
      finalize: finalize,
      incrementAiCallCount: incrementAiCallCount,
    };
  })();

  /* ─────────────────────────────────────────────
     Helpers visibilité (proxy vers sapiProject — Round 2 / 3.2)
     ───────────────────────────────────────────── */
  // Visibilité BRUTE (sans short mode) — utilisée par cleanInvisibleAnswers
  // pour ne pas effacer eclairage/sortie/hauteur/table juste parce qu'on est
  // sur une fiche produit en short mode.
  function computeRawVisibleSteps(answers) {
    if (window.sapiProject && typeof window.sapiProject.computeVisibleStepIds === 'function') {
      return window.sapiProject.computeVisibleStepIds(answers, STEPS);
    }
    return [];
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
    if (!window.sapiProject || typeof window.sapiProject.cleanInvisibleAnswers !== 'function') return;
    var clean = window.sapiProject.cleanInvisibleAnswers(state.answers, STEPS);
    Object.keys(state.answers).forEach(function (sid) {
      if (!Object.prototype.hasOwnProperty.call(clean, sid)) {
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
    // Tracking V3 — snapshot à chaque transition d'écran significative.
    SessionTracker.snapshot();
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
      // Round 4 — mockup-11 : 2 cols pour 2 choix, 4 cols pour 4 choix,
      // sinon 3 cols par défaut. Évite les items isolés sur la dernière ligne.
      els.choices.classList.toggle('choices--2col', choices.length === 2);
      els.choices.classList.toggle('choices--4col', choices.length === 4);
      choices.forEach(function (choice) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'choice';
        btn.setAttribute('data-choice', choice.slug);
        btn.setAttribute('data-label', choice.label);
        if (state.answers[stepId] === choice.slug) btn.classList.add('is-selected');

        var iconWrap = document.createElement('span');
        iconWrap.className = 'choice__icon';
        iconWrap.innerHTML = ICONS[choice.icon] || '';
        btn.appendChild(iconWrap);

        var label = document.createElement('span');
        label.className = 'choice__label';
        label.textContent = choice.label;
        btn.appendChild(label);

        if (choice.dim) {
          var dim = document.createElement('span');
          dim.className = 'choice__dim';
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

    // Round 4 — Cherche la prochaine question visible NON RÉPONDUE après
    // la courante. Skip celles déjà répondues : utile en mode édition S3
    // (clic sur chip) où on modifie une chip et les questions suivantes
    // ont déjà des réponses valides, donc on retourne direct au récap.
    var visible = getVisibleStepIds(state.answers);
    var idx = visible.indexOf(step);
    var nextStep = null;
    for (var i = idx + 1; i < visible.length; i++) {
      if (!state.answers[visible[i]]) {
        nextStep = visible[i];
        break;
      }
    }

    if (nextStep) {
      showQuestion(nextStep);
      // F2a-quater : bascule visuelle S0→S1 (ou no-op si déjà S1)
      if (state.screen !== 's1') showScreen('s1');
    } else if (state.editFromS3) {
      // Round 4 — édition d'une chip depuis S3 : retour direct au récap
      // (toutes les questions suivantes ont déjà des réponses valides).
      state.editFromS3 = false;
      if (window.sapiProject) {
        window.sapiProject.update(state.answers, state.labels);
      }
      showS3Recap();
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

      // Fix audit — Bug 2 : reprendre les notifications sapiProject (sinon
      // le notify bufferisé de sapiProject.set() ligne 496 n'est jamais
      // flushé puisque ce chemin ne passe pas par closeModal()) +
      // finaliser le tracking V3 (sinon la session n'est pas terminée
      // dans l'admin). Le resume déclenche un render() des cards qui va
      // basculer Conseil → Ton projet + repopulate le slot.
      SessionTracker.finalize();
      if (window.sapiProject && typeof window.sapiProject.resumeNotifications === 'function') {
        window.sapiProject.resumeNotifications();
      }

      // 4. Refilter la grille (idempotent — déjà déclenché par resume → render)
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
        SessionTracker.incrementAiCallCount();
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
  //   Phase 3 (1100–1900ms) : hide modale + cleanup styles puis resolve
  //                           (le texte apparaît ensuite via finishAdvice +
  //                           typewriter sur la card "Mon projet")
  // Round 6 — scroll auto retiré : trop perturbant avec la chorégraphie
  // 4 phases de la card "Ton projet" (apparition étagée typewriter →
  // chip-question → cards → nav). Le visiteur garde sa position de scroll.
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

        // Round 6 — scroll auto retiré. Le délai 800ms est conservé pour
        // laisser le fade-out modale finir avant que le texte typewriter
        // ne démarre sur la card "Ton projet".
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
    // Round 4 — markup mockup-11 : flat <div class="chat-bubble chat-bubble--visitor">
    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble chat-bubble--visitor';
    bubble.textContent = text;
    els.chatMessages.appendChild(bubble);
    scrollChatToBottom();
  }

  function addRobinBubble(text, opts) {
    if (!els.chatMessages) return;
    opts = opts || {};
    // Round 4 — markup mockup-11 : flat <div class="chat-bubble chat-bubble--robin">
    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble chat-bubble--robin';
    bubble.textContent = text || '';
    els.chatMessages.appendChild(bubble);

    // Encart "Filtres appliqués" — affiché en dessous de la bulle, classe
    // dédiée (déviation mockup justifiée : cas dynamique non couvert par le mockup).
    if (opts.filters && typeof opts.filters === 'object') {
      var parts = [];
      Object.keys(opts.filters).forEach(function (k) {
        var slug = opts.filters[k];
        if (slug === null) return;
        parts.push(getChoiceLabel(k, slug));
      });
      if (parts.length) {
        var fb = document.createElement('div');
        fb.className = 'chat-bubble-filters';
        var label = document.createElement('span');
        label.className = 'chat-bubble-filters__label';
        label.textContent = 'Filtres appliqués';
        fb.appendChild(label);
        var chips = document.createElement('span');
        chips.className = 'chat-bubble-filters__chips';
        chips.textContent = parts.join(' · ');
        fb.appendChild(chips);
        els.chatMessages.appendChild(fb);
      }
    }

    scrollChatToBottom();
  }

  function addThinkingBubble() {
    if (!els.chatMessages) return;
    if (document.getElementById('conseiller-chat-thinking')) return;
    // Round 4 — markup mockup-11 : flat chat-bubble + classe thinking
    var bubble = document.createElement('div');
    bubble.className = 'chat-bubble chat-bubble--robin chat-bubble-thinking';
    bubble.id = 'conseiller-chat-thinking';
    bubble.setAttribute('aria-label', 'Réponse en cours de préparation');
    for (var i = 0; i < 3; i++) {
      var dot = document.createElement('span');
      dot.className = 'chat-bubble-thinking__dot';
      bubble.appendChild(dot);
    }
    els.chatMessages.appendChild(bubble);
    scrollChatToBottom();
  }

  function removeThinkingBubble() {
    var el = document.getElementById('conseiller-chat-thinking');
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }

  function scrollChatToBottom() {
    if (!els.chatMessages) return;
    // Round 4 — Le scrollable est .modal__body (CSS Grid row 2, overflow-y: auto)
    var scrollable = els.chatMessages.closest('.modal__body') || els.chatMessages;
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
        SessionTracker.incrementAiCallCount();
        // Audit #7 : garde-fou DOM démonté (modale fermée pendant le fetch)
        if (!state.open) return;
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');

        if (!resp || !resp.success) {
          var fallback = (resp && resp.data && resp.data.fallback) ||
            'Je n\'arrive pas à analyser ton message. Tu peux réessayer ou m\'écrire directement.';
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

        // Round 3 — Lot C2 : action=contact → écran s-contact dédié (CTAs
        // formulaire/email selon contact_kind, pré-remplis subject/message).
        // Si filters non vide en plus (cas par cas, écart modéré), on passe
        // d'abord showContact mais sapiProject.action est stocké pour que
        // la grille montre la card sur-mesure en 1re position (Lot C3).
        if (data.action === 'contact') {
          showContact(data);
          return;
        }
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
            addRobinBubble('Le serveur ne répond pas. Tu peux réessayer ou contacter Robin via le formulaire.');
          }
          return;
        }
        if (!state.open) return;
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Je n\'arrive pas à te répondre pour l\'instant. Tu peux réessayer ou contacter Robin via le formulaire.');
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
        SessionTracker.incrementAiCallCount();
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

        // Round 3 — Lot C2 : action=contact → écran s-contact dédié.
        if (data.action === 'contact') {
          showContact(data);
          return;
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
            addRobinBubble('Le serveur ne répond pas. Tu peux réessayer ou contacter Robin via le formulaire.');
          }
          return;
        }
        if (!state.open) return;
        removeThinkingBubble();
        state.chat.status = 'idle';
        setChatFooterState('idle');
        addRobinBubble('Je n\'arrive pas à te répondre pour l\'instant. Tu peux réessayer ou contacter Robin via le formulaire.');
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
      badgeText = 'Ton projet';
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
        // Round 4 — mockup-11 : 2 cols pour 2 choix, 4 cols pour 4 choix, 3 par défaut.
        els.s0Choices.classList.toggle('choices--2col', choices.length === 2);
        els.s0Choices.classList.toggle('choices--4col', choices.length === 4);
        choices.forEach(function (choice) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'choice';
          btn.setAttribute('data-choice', choice.slug);
          btn.setAttribute('data-label', choice.label);
          var iconWrap = document.createElement('span');
          iconWrap.className = 'choice__icon';
          iconWrap.innerHTML = ICONS[choice.icon] || '';
          btn.appendChild(iconWrap);
          var label = document.createElement('span');
          label.className = 'choice__label';
          label.textContent = choice.label;
          btn.appendChild(label);
          if (choice.dim) {
            var dim = document.createElement('span');
            dim.className = 'choice__dim';
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
    // Round 4 — mode initial : réassurance "Robin t'aide à choisir" visible.
    // Mode partiel : remplacée par le bouton "Effacer et recommencer".
    if (els.s0Reassure) els.s0Reassure.hidden = resetVisible;

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

    // Bulle initiale de l'assistant (cosmétique, construite côté client — zéro IA)
    var greeting = getInitialChatGreeting();
    enterChatMode();
    addRobinBubble(greeting);
    state.chat.conversation.push({ role: 'assistant', content: greeting });

    // Soumet le texte saisi via le flow freetext existant (Haiku + transition)
    submitFreetext(text);
  }

  // Bulle d'accueil de l'assistant selon l'état du projet (zéro appel IA).
  function getInitialChatGreeting() {
    if (!window.sapiProject || !window.sapiProject.hasProject()) {
      return 'Décris-moi ton projet, je vais t\'aider à trouver une sélection adaptée dans le catalogue de Robin.';
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

  // Round 4 — mockup-11 : recap groupé par thème (Espace / Installation /
  // Esthétique) avec chips icône + label uppercase + valeur.
  var S3_GROUPS = [
    { title: 'Espace',       steps: ['piece', 'taille', 'taille_escalier'] },
    { title: 'Installation', steps: ['sortie', 'eclairage', 'hauteur', 'table'] },
    { title: 'Esthétique',   steps: ['style'] }
  ];
  var S3_KEY_LABELS = {
    piece: 'Pièce',
    taille: 'Taille',
    taille_escalier: 'Escalier',
    eclairage: 'Éclairage',
    sortie: 'Sortie électrique',
    hauteur: 'Hauteur sous plafond',
    table: 'Au-dessus',
    style: 'Style'
  };

  function populateRecapChips() {
    if (!els.recapChips) return;
    els.recapChips.innerHTML = '';

    S3_GROUPS.forEach(function (group) {
      var stepsWithValue = group.steps.filter(function (sid) {
        return state.answers[sid];
      });
      if (!stepsWithValue.length) return; // skip groupe si aucune réponse

      var groupEl = document.createElement('div');
      groupEl.className = 'recap-group';

      var titleEl = document.createElement('div');
      titleEl.className = 'recap-group__title';
      titleEl.textContent = group.title;
      groupEl.appendChild(titleEl);

      var chipsEl = document.createElement('div');
      chipsEl.className = 'recap-group__chips';

      stepsWithValue.forEach(function (sid) {
        var slug = state.answers[sid];
        var step = getStep(sid);
        var labelText = state.labels[sid] || slug;
        var keyLabel = S3_KEY_LABELS[sid] || sid;

        // Round 4 — chip cliquable pour éditer la réponse (mockup-11 hint
        // promettait cette fonctionnalité). Utilise un <button> au lieu
        // d'un <span> pour l'accessibilité + cursor pointer naturel.
        var chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'chip chip--project';
        chip.setAttribute('data-step-edit', sid);
        chip.setAttribute('aria-label', 'Modifier ' + keyLabel + ' : ' + labelText);

        // Icône : depuis l'icône du choix sélectionné dans le step
        var iconName = null;
        if (step && step.choices) {
          for (var i = 0; i < step.choices.length; i++) {
            if (step.choices[i].slug === slug) {
              iconName = step.choices[i].icon;
              break;
            }
          }
        }
        if (iconName && ICONS[iconName]) {
          var iconEl = document.createElement('span');
          iconEl.className = 'chip__icon';
          iconEl.innerHTML = ICONS[iconName];
          chip.appendChild(iconEl);
        }

        // Wrapper texte (label uppercase + valeur)
        var textWrap = document.createElement('span');
        var labelEl = document.createElement('span');
        labelEl.className = 'chip__label';
        labelEl.textContent = keyLabel;
        var valueEl = document.createElement('span');
        valueEl.className = 'chip__value';
        valueEl.textContent = labelText;
        textWrap.appendChild(labelEl);
        textWrap.appendChild(valueEl);
        chip.appendChild(textWrap);

        chipsEl.appendChild(chip);
      });

      groupEl.appendChild(chipsEl);
      els.recapChips.appendChild(groupEl);
    });
  }

  function showS3Recap() {
    populateRecapChips();
    showScreen('s3');
  }

  // Action "Voir la sélection" depuis S3 : ferme la modale + scroll grille.
  // Round 4 — Si le projet a été modifié depuis le dernier appel IA (chips
  // éditables, sapiProject.update invalide advice_text à null quand answers
  // change), on relance un nouveau fetch advice via showTransitionAndExit
  // pour avoir une phrase à jour sur la card "Mon projet". Sinon ferme direct.
  function viewSelectionFromS3() {
    var project = window.sapiProject ? window.sapiProject.get() : null;
    var needsNewAdvice = !project || !project.advice_text;
    if (needsNewAdvice) {
      showTransitionAndExit({ source: 's3' });
      return;
    }
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
    return cardsConfig.fallbackAdvice || 'Voici la sélection que je te propose dans le catalogue de Robin.';
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

  // Construit l'intro "Pour <ta/ton pièce>, Robin recommande :" — tutoiement,
  // possessif accordé au genre via une table piece-clé → forme tutoyée
  // (« votre » est neutre, pas « ton/ta » → table explicite pour éviter
  // « ton chambre »). Repli sur « ta pièce » si la clé est inconnue.
  var PIECE_TUTOIEMENT = {
    'cuisine': 'ta cuisine',
    'bureau': 'ton bureau',
    'salon': 'ton salon',
    'chambre': 'ta chambre',
    'chambre-enfant': 'ta chambre d\'enfant',
    'entree': 'ton entrée',
    'escalier': 'ta cage d\'escalier'
  };
  function buildRecapIntro(answers, labels) {
    var piece = PIECE_TUTOIEMENT[answers && answers.piece] || 'ta pièce';
    return 'Pour ' + piece + ', Robin recommande :';
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
     Round 3 — Lot C2 v2 : écran s-contact avec formulaire intégré
     Remplace les anciens CTAs externes (formulaire sur-mesure / mailto)
     par un form AJAX direct → endpoint sapi_megafilter_surmesure existant.
     ───────────────────────────────────────────── */
  // Construit le récap projet (chips ordonnées séparées par " · ").
  function buildContactRecap(answers, labels) {
    var orderedKeys = ['piece', 'taille', 'taille_escalier', 'eclairage', 'sortie', 'hauteur', 'table', 'style'];
    var lines = [];
    orderedKeys.forEach(function (k) {
      var slug = answers && answers[k];
      if (!slug) return;
      var lbl = (labels && labels[k]) || slug;
      lines.push(lbl);
    });
    return lines;
  }

  // Toggle l'état form/success de l'écran s-contact.
  function setContactScreenState(name) {
    if (!els.modal) return;
    var states = els.modal.querySelectorAll('[data-contact-state]');
    states.forEach(function (s) {
      s.hidden = (s.getAttribute('data-contact-state') !== name);
    });
  }

  function showContact(payload) {
    if (!els.contactMessage || !els.contactForm) return;

    // Reset état visuel : on entre toujours par le form (pas le success).
    setContactScreenState('form');
    // Reset erreur inline si présente
    var prevErr = els.contactForm.querySelector('.contact-form__error');
    if (prevErr) prevErr.remove();
    var submitBtn = els.contactForm.querySelector('[data-contact-submit]');
    if (submitBtn) submitBtn.disabled = false;

    // Message IA
    els.contactMessage.textContent = (payload && payload.message) || '';

    // Récap projet (mockup-11 : .chips-label + .chips > .chip simples)
    // Caché si vide — cas contact direct sans filters extraits.
    els.contactRecap.innerHTML = '';
    var recapLines = buildContactRecap(state.answers, state.labels);
    if (recapLines.length) {
      els.contactRecap.setAttribute('style', 'text-align: center;');
      var labelEl = document.createElement('div');
      labelEl.className = 'chips-label';
      labelEl.textContent = 'Ton projet';
      els.contactRecap.appendChild(labelEl);
      var chipsEl = document.createElement('div');
      chipsEl.className = 'chips';
      chipsEl.setAttribute('style', 'margin-top: 6px;');
      recapLines.forEach(function (line) {
        var chip = document.createElement('span');
        chip.className = 'chip';
        chip.textContent = line;
        chipsEl.appendChild(chip);
      });
      els.contactRecap.appendChild(chipsEl);
    } else {
      els.contactRecap.removeAttribute('style');
    }

    // Pré-remplit le textarea avec contact_subject + contact_message générés par l'IA
    if (els.contactMessageField) {
      var pre = '';
      if (payload && payload.contact_subject) pre += payload.contact_subject + '\n\n';
      if (payload && payload.contact_message) pre += payload.contact_message;
      els.contactMessageField.value = pre.trim();
    }

    // Email vide (le visiteur le remplit)
    var emailInput = els.contactForm.querySelector('input[name="email"]');
    if (emailInput) emailInput.value = '';

    // Persiste l'état contact dans sapiProject — utilisé par Lot C3 pour la
    // card sur-mesure en 1re position de la grille /mes-creations/.
    if (window.sapiProject && typeof window.sapiProject.setContactState === 'function') {
      window.sapiProject.setContactState({
        action: 'contact',
        contact_kind: payload.contact_kind || null,
        contact_subject: payload.contact_subject || '',
        contact_message: payload.contact_message || '',
      });
    }

    showScreen('s-contact');
  }

  // Submission du form de contact intégré → endpoint sapi_megafilter_surmesure.
  function submitContactForm(form) {
    if (!form || !config.ajaxUrl) return;
    var submitBtn = form.querySelector('[data-contact-submit]');
    var emailInput = form.querySelector('input[name="email"]');
    var msgInput = form.querySelector('[data-contact-message-field]');

    // Validation client minimale
    var emailVal = (emailInput && emailInput.value || '').trim();
    var msgVal = (msgInput && msgInput.value || '').trim();
    if (emailInput) emailInput.classList.remove('is-invalid');
    if (msgInput) msgInput.classList.remove('is-invalid');
    var hasErr = false;
    if (!emailVal || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) {
      if (emailInput) emailInput.classList.add('is-invalid');
      hasErr = true;
    }
    if (!msgVal) {
      if (msgInput) msgInput.classList.add('is-invalid');
      hasErr = true;
    }
    var prevErr = form.querySelector('.contact-form__error');
    if (prevErr) prevErr.remove();
    if (hasErr) {
      var err = document.createElement('p');
      err.className = 'contact-form__error';
      err.textContent = 'Email et message sont requis.';
      form.appendChild(err);
      return;
    }

    if (submitBtn) submitBtn.disabled = true;

    var project = window.sapiProject ? window.sapiProject.get() : null;
    var contactKind    = project && project.contact_kind || '';
    var contactSubject = project && project.contact_subject || '';

    // Tracking V3 — snapshot AVANT le submit pour qu'on trace même si le
    // visiteur ferme la modale avant la confirmation serveur.
    SessionTracker.snapshot({
      contact_triggered: 1,
      contact_submitted: 1,
      contact_email: emailVal,
      contact_message: msgVal,
      contact_kind: contactKind,
      contact_subject: contactSubject,
    });

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_surmesure');
    fd.append('nonce', config.nonce || '');
    fd.append('email', emailVal);
    fd.append('description', msgVal);
    fd.append('source', 'conseiller-modal');
    fd.append('source_url', window.location.href);
    if (contactKind)    fd.append('contact_kind', contactKind);
    if (contactSubject) fd.append('contact_subject', contactSubject);
    if (project) fd.append('project', JSON.stringify(project));

    fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        if (resp && resp.success) {
          // Clear l'état contact (la demande a été envoyée, plus besoin de
          // re-router sur cette session). La card sur-mesure de la grille
          // /mes-creations/ basculera sur son état "project" ou "empty"
          // au prochain refresh subscribe.
          if (window.sapiProject && typeof window.sapiProject.setContactState === 'function') {
            window.sapiProject.setContactState(null);
          }
          setContactScreenState('success');
          return;
        }
        if (submitBtn) submitBtn.disabled = false;
        var fallback = (resp && resp.data && resp.data.fallback) ||
          "L'envoi a échoué. Tu peux m'écrire directement à " + CONTACT_EMAIL + ".";
        var errEl = document.createElement('p');
        errEl.className = 'contact-form__error';
        errEl.textContent = fallback;
        form.appendChild(errEl);
      })
      .catch(function () {
        if (submitBtn) submitBtn.disabled = false;
        var errEl = document.createElement('p');
        errEl.className = 'contact-form__error';
        errEl.textContent = "Petit souci de connexion. Tu peux réessayer ou m'écrire à " + CONTACT_EMAIL + ".";
        form.appendChild(errEl);
      });
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

    // Geler les notifications sapiProject pendant la modale : évite que
    // les cards en arrière-plan (Conseil/Mon projet sur /mes-creations/)
    // refilter à chaque réponse cliquée (sinon flashs visibles à travers
    // l'overlay). À closeModal(), un flush unique met tout à jour d'un coup.
    if (window.sapiProject && typeof window.sapiProject.pauseNotifications === 'function') {
      window.sapiProject.pauseNotifications();
    }

    // Tracking V3 — INSERT row (entry_point + entry_url). Doit être appelé
    // AVANT showScreen() pour que le snapshot suivant soit un UPDATE.
    SessionTracker.start();

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
    // Round 2 — 3.1 : reset shortMode pour ne pas leak l'état "fiche produit"
    // dans une réouverture suivante (sinon modifyProductAnswers reste en mode
    // court, et la modale rouvre filtrée sur 4 steps au lieu du parcours
    // complet — confusion observée en navigation fiche→shop→fiche).
    state.shortMode = false;
    // Audit #7 : abort tout fetch IA en cours (chat/freetext) — évite que la
    // réponse arrive après la fermeture et tente d'écrire dans le DOM démonté.
    if (state.aiController) {
      try { state.aiController.abort(); } catch (e) { /* swallow */ }
      state.aiController = null;
    }
    // Annule l'éventuel auto-avance confirmStep en attente (sinon snapshot
    // tracking inutile + risque d'avancer dans une modale fermée).
    if (state.confirmAdvanceTimer) {
      clearTimeout(state.confirmAdvanceTimer);
      state.confirmAdvanceTimer = null;
    }
    els.modal.hidden = true;
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    exitChatMode();
    if (lastTrigger && lastTrigger.focus) {
      try { lastTrigger.focus(); } catch (e) { /* swallow */ }
    }
    // Tracking V3 — snapshot final via sendBeacon (résilient au unload).
    SessionTracker.finalize();
    // Reprendre les notifications sapiProject + flush l'éventuel update
    // accumulé pendant la modale (un seul refresh des cards à la fermeture).
    if (window.sapiProject && typeof window.sapiProject.resumeNotifications === 'function') {
      window.sapiProject.resumeNotifications();
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
      // Round 2 — 2.1 : garde-fou. state='product' sans config.product (pas
      // sur fiche produit) déclencherait applyProductSelection avec
      // productId:0 silencieux. Mieux vaut abort et logger.
      if (st === 'product' && !PRODUCT_CTX) {
        // eslint-disable-next-line no-console
        console.warn('[sapi-modal] open-modal state=product reçu sans config.product, abort.');
        return;
      }
      openModal(st);
      // Round 4 — Si detail.freetext fourni (depuis le room picker de la
      // card Conseil sur /mes-creations/), bascule en chat S2 avec le texte
      // initial — même mécanique que le param URL ?freetext= sur load.
      var freetext = (e.detail && typeof e.detail.freetext === 'string') ? e.detail.freetext.trim() : '';
      if (freetext) {
        setTimeout(function () { submitFromS0Text(freetext); }, 50);
      }
      // Confirm-step : la card chip-question a déjà enregistré la réponse,
      // on ouvre la modale sur la question répondue (pill selected via
      // state.answers hydraté), puis on auto-avance après 700ms vers la
      // suivante. Feedback visuel "ma réponse a bien été prise".
      var confirmStep  = e.detail && e.detail.confirmStep;
      var confirmSlug  = e.detail && e.detail.confirmSlug;
      var confirmLabel = e.detail && e.detail.confirmLabel;
      if (confirmStep && confirmSlug) {
        requestAnimationFrame(function () {
          state.currentQuestion = confirmStep;
          showQuestion(confirmStep);
          if (state.screen !== 's1') showScreen('s1');
          // Auto-avance : reuse answerCurrentQuestion qui gère history +
          // sapiProject sync + transition vers la prochaine question.
          // Tracker le timer pour pouvoir l'annuler si l'utilisateur ferme
          // la modale dans l'intervalle (sinon snapshot tracking inutile).
          if (state.confirmAdvanceTimer) clearTimeout(state.confirmAdvanceTimer);
          state.confirmAdvanceTimer = setTimeout(function () {
            state.confirmAdvanceTimer = null;
            if (!state.open) return;
            answerCurrentQuestion(confirmSlug, confirmLabel);
          }, 700);
        });
      }
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
        case 'close':
          // Round 2 — 3.4 : bouton close visible (croix top-right)
          closeModal();
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
        case 'back-to-chat':
          // Round 3 — Lot C2 : retour au chat depuis l'écran s-contact.
          // Le state.chat.conversation est préservé, on bascule juste la vue.
          if (window.sapiProject && typeof window.sapiProject.setContactState === 'function') {
            window.sapiProject.setContactState(null);
          }
          enterChatMode();
          break;
      }
    });

    // Click sur un choix (S0 hybride OU S1) — délégué sur toute la modale
    // pour couvrir les 2 contextes (refs DOM distinctes pour S0 et S1).
    els.modal.addEventListener('click', function (e) {
      var btn = e.target.closest('.choice');
      if (!btn) return;
      var slug = btn.getAttribute('data-choice');
      var label = btn.getAttribute('data-label') || btn.textContent.trim();
      answerCurrentQuestion(slug, label);
    });

    // Round 4 — Click sur une chip de récap S3 → édite ce step.
    // Bascule sur S1 avec la question correspondante, en mode editFromS3
    // pour qu'à la fin du flow (qui peut être immédiate si aucune réponse
    // n'est invalidée par le changement), on retourne directement au récap.
    els.modal.addEventListener('click', function (e) {
      var editChip = e.target.closest('[data-step-edit]');
      if (!editChip) return;
      var stepId = editChip.getAttribute('data-step-edit');
      if (!stepId) return;
      state.editFromS3 = true;
      // Reset questionHistory pour que le retour de S1 ne ramène pas à
      // d'anciennes questions du parcours initial — depuis S3 on est
      // "hors-flow", on permet juste l'édition ponctuelle.
      state.questionHistory = [];
      showQuestion(stepId);
      showScreen('s1');
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

    // Round 3 — Lot C2 v2 : submit du form contact intégré
    if (els.contactForm) {
      els.contactForm.addEventListener('submit', function (e) {
        e.preventDefault();
        submitContactForm(els.contactForm);
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
    els.s0Reassure    = els.modal.querySelector('[data-s0-reassure]');
    // S1 (questions guidées)
    els.questionTitle = els.modal.querySelector('[data-question-title]');
    els.choices       = els.modal.querySelector('[data-choices]');
    els.progressFill  = els.modal.querySelector('[data-progress-fill]');
    // S2 chat
    els.chatMessages  = els.modal.querySelector('[data-chat-messages]');
    els.chatCta       = els.modal.querySelector('[data-chat-cta]');
    els.chatInput     = els.modal.querySelector('[data-chat-input]');
    els.chatSend      = els.modal.querySelector('[data-chat-form] button[type="submit"]');
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
    // Round 3 — Lot C2 v2 : écran s-contact avec form intégré
    els.contactMessage      = els.modal.querySelector('[data-contact-message]');
    els.contactRecap        = els.modal.querySelector('[data-contact-recap]');
    els.contactForm         = els.modal.querySelector('[data-contact-form]');
    els.contactMessageField = els.modal.querySelector('[data-contact-message-field]');

    // Marqueur pour les cards Phase 2 (évite leur fallback console.info)
    window.__sapiModalReady = true;

    bindEvents();

    // Round 4 — Si un param URL ?freetext=… est présent (depuis le room
    // picker homepage), auto-ouvre la modale en S0 puis bascule en chat S2
    // avec le texte saisi. Nettoie ensuite l'URL pour éviter retrigger
    // au refresh.
    try {
      var params = new URLSearchParams(window.location.search);
      var initialFreetext = params.get('freetext');
      if (initialFreetext && initialFreetext.length) {
        // Nettoyer l'URL
        params.delete('freetext');
        var newSearch = params.toString();
        var newUrl = window.location.pathname + (newSearch ? '?' + newSearch : '') + (window.location.hash || '');
        window.history.replaceState({}, '', newUrl);
        // Ouvrir la modale en S0 puis basculer en chat S2 avec le texte
        setTimeout(function () {
          openModal('s0');
          submitFromS0Text(initialFreetext);
        }, 100);
      }
    } catch (e) { /* URLSearchParams indisponible — silencieux */ }
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

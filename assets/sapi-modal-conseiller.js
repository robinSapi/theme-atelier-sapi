/**
 * Sapi Modal Conseiller — Modale tunnel S0/S1/S3 (F2a Phase 3)
 *
 * État S0 : écran 2 portes (Je choisis / Je décris)
 * État S1 : questions guidées (boutons-cards, avance auto, retour, progress)
 * État S3 : récap (chips + phrase IA Sonnet + CTA "Voir la sélection")
 *
 * Listener : event 'sapi:open-modal' (dispatché par l'immersion /mes-creations/,
 * la pill fiche produit, ou le param ?freetext= au load)
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

  /* ⚠️ LES TABLES DE TRADUCTION DU PROJET VIVENT DANS `sapi-project.js`.
     `style → essence` existait ici, dans `sapi-product-preselect.js` et dans
     `sapi-photo-swap.js` ; `taille → index` existait ici et là-bas, **avec une
     divergence réelle sur l'escalier** qui faisait appliquer une taille et en
     annoncer une autre sur le même écran. Ne pas les réintroduire.
     Seuls restent ici les libellés d'affichage, qui ne sont lus que par la
     modale. */
  var ESSENCE_LABEL      = { peuplier: 'Peuplier', okoume: 'Okoumé' };

  // F2a-ter : labels humains des clés pour les chips récap S3 ("Pièce : Salon").
  var KEY_LABELS = {
    piece: 'Pièce',
    taille: 'Taille',
    taille_escalier: 'Escalier',
    eclairage: 'Éclairage',
    sortie: 'Sortie',
    hauteur: 'Hauteur',
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
      // Catégorie déduite par le serveur quand il n'en trouve qu'UNE. Sert de
      // destination au bouton « Voir… » lorsque le projet n'a pas de pièce.
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
  /* ⚠️ LU AU CHARGEMENT DU SCRIPT, AVANT TOUT LE RESTE — ne pas déplacer.
     `init()` retire `?freetext=` de l'URL puis ouvre la modale 100 ms plus
     tard. `detectEntryPoint()` relisait donc une URL DÉJÀ NETTOYÉE et
     concluait « mes_creations » : le compteur `freetext` du tableau de bord
     valait 0 depuis toujours, et `entry_url` — construit lui aussi après le
     nettoyage — n'en gardait aucune trace non plus.
     Résultat pour Robin : il ne pouvait pas savoir combien de visiteurs
     passent par le champ texte libre, c'est-à-dire par le parcours qu'on
     vient justement de réparer.
     La capture doit rester ICI, au tout premier instant du script. */
  var ENTREE_FREETEXT = '';
  try {
    ENTREE_FREETEXT = new URLSearchParams(window.location.search).get('freetext') || '';
  } catch (e) { /* URLSearchParams indisponible — silencieux */ }

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
        // Capture faite au chargement du script, avant le nettoyage d'URL.
        if (ENTREE_FREETEXT) return 'freetext';
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
      /* La phrase saisie sur la home. Le serveur sait la stocker depuis le
         début (`ai_freetext_input`) mais le client ne l'envoyait JAMAIS : la
         colonne était vide sur 100 % des lignes, `ai_freetext_used` restait à
         0, l'encart « saisie initiale » du détail ne s'affichait jamais, et
         surtout la recherche du tableau de bord — qui promet « texte libre » —
         interrogeait une colonne toujours NULL. */
      if (ENTREE_FREETEXT) payload.ai_freetext_input = ENTREE_FREETEXT;
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
      /* ⚠️ CUL-DE-SAC CORRIGÉ. `determineInitialState()` peut renvoyer
         `s3-carrefour` (toutes les réponses visibles sont données), mais
         `renderS0Hybrid` ne sait traiter que `s0-initial` et `s0-partiel` : il
         tombait dans la branche « initial » et réaffichait la première
         question comme si le projet était vide — alors qu'il était complet et
         toujours en mémoire, et sans aucun chemin de retour vers le récap.
         Chemin exact : récap → clic sur une chip (qui vide volontairement la
         pile d'historique) → « Étape précédente ». */
      var back = determineInitialState();
      if (back === 's3-carrefour') { showS3Recap(); return; }
      renderS0Hybrid(back);
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

  /* Où le visiteur doit-il atterrir en sortant de la modale ?
     ⚠️ CE TEST MANQUAIT, ET C'EST LE DÉFAUT LE PLUS COÛTEUX DU PARCOURS.
     La sortie de la modale a été conçue pour la page /mes-creations/ EN MODE
     IMMERSION : elle émet des événements que le hero écoute pour rafraîchir sa
     sélection. Mais le hero n'existe que si l'URL porte `?piece=`. Or le champ
     libre (home, /conseils-eclaires/, état A) envoie vers `?freetext=` SANS
     pièce : la page n'a donc ni hero, ni slider, personne pour écouter. Le
     bouton « Voir la sélection pour mon projet » fermait la modale et rien
     d'autre — alors que le message au-dessus venait d'annoncer des filtres.
     Quand le filtrage est passé côté serveur, seul le chemin de l'immersion a
     été rebranché ; la recette de l'époque ne vérifiait que l'OUVERTURE du
     champ libre, jamais sa sortie.
     → Sans immersion et avec une pièce connue, on RECHARGE vers
       /mes-creations/?piece=<pièce> : le visiteur atterrit exactement là où
       l'aurait mené un clic sur la carte de cette pièce, et `advice_text`,
       déjà stocké, y est repris et tapé par le hero. On réutilise un chemin
       éprouvé au lieu d'en inventer un.
     → Sans pièce du tout (l'extraction n'a rien trouvé : « salle de bain »
       n'existe pas dans les sept pièces du référentiel), il n'y a nulle part
       où aller. Décision de Robin : on l'emmène vers le CONTACT plutôt que de
       le laisser sur une page vide. C'est le seul moment du parcours où il a
       décidé quelque chose. */
  function immersionIsOnPage() {
    return !!document.querySelector('[data-immersion]');
  }
  function projectPiece() {
    try {
      var p = window.sapiProject && window.sapiProject.get ? window.sapiProject.get() : null;
      if (p && p.answers && p.answers.piece) return p.answers.piece;
    } catch (e) { /* swallow */ }
    return (state.answers && state.answers.piece) || '';
  }
  function goToSelectionPage(piece) {
    try {
      var url = new URL(window.location.href);
      url.pathname = '/mes-creations/';
      url.searchParams.delete('freetext');
      url.searchParams.set('piece', piece);
      window.location.assign(url.toString());
    } catch (err) {
      window.location.href = '/mes-creations/?piece=' + encodeURIComponent(piece);
    }
  }

  function showTransitionAndExit(opts) {
    opts = opts || {};
    if (state.transition) return; // évite double-trigger

    /* Aucune sélection à révéler sur cette page : on redirige (ou on bascule
       sur le contact) AVANT de lancer l'animation de sortie et l'appel IA.
       ⚠️ L'appel IA n'est pas perdu dans le cas « avec pièce » : il est lancé
       plus bas, son texte est stocké dans le projet, et la page d'arrivée le
       lit et le tape. C'est justement ce qui rend la redirection gratuite. */
    if (!immersionIsOnPage()) {
      var piece = projectPiece();
      if (!piece) {
        if (window.sapiProject) window.sapiProject.set(state.answers, state.labels);
        showContact({
          message: 'Je n’ai pas encore assez d’éléments pour te proposer une sélection. Laisse-moi ton mail et deux mots sur ton projet, je te réponds moi-même.'
        });
        return;
      }
      state.transition = true;
      if (window.sapiProject) window.sapiProject.set(state.answers, state.labels);
      fetchAdviceFromIA(opts).then(function (advice) {
        if (advice && window.sapiProject && typeof window.sapiProject.setAdviceText === 'function') {
          window.sapiProject.setAdviceText(advice);
        }
        goToSelectionPage(piece);
      });
      return;
    }

    state.transition = true;

    // 1. Lancer le fetch IA en parallèle (résolu indépendamment de l'anim)
    var pendingAdvice = null;
    var adviceResolved = false;
    var fetchPromise = fetchAdviceFromIA(opts).then(function (advice) {
      pendingAdvice = advice;
      adviceResolved = true;
      return advice;
    });

    // Immersion /mes-creations/ : dès le DÉBUT du calcul (modale encore ouverte),
    // on fait disparaître la phrase générique et on affiche le loader (3 points).
    // Le commentaire IA personnalisé la remplacera via 'sapi:advice-ready'.
    // On porte les réponses dans le detail : l'immersion précharge SA sélection
    // maintenant (~300ms), pendant que l'IA calcule et que l'animation de sortie
    // se joue (~1,9s). Les deux attentes se recouvrent au lieu de s'additionner.
    document.dispatchEvent(new CustomEvent('sapi:advice-loading', {
      detail: { answers: state.answers || {} }
    }));

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
      dispatchConseillerClosed();

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
    // Immersion : remplacer le loader par le commentaire IA (ou revenir au
    // texte générique si l'IA n'a rien renvoyé → advice vide).
    document.dispatchEvent(new CustomEvent('sapi:advice-ready', {
      detail: { advice: (typeof advice === 'string' ? advice : '') }
    }));
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

  /* Merge un patch {key: slug | null} dans state.answers + state.labels.
     `replaceAll` : la description libre REMPLACE le projet au lieu de le
     compléter.
     ⚠️ SANS CE PARAMÈTRE, L'INTENTION ÉCRITE N'ÉTAIT PAS RÉALISÉE. Le chemin
     du texte libre vide bien `state.answers` avec le commentaire « nouvelle
     description complète, on remplace les chips » — mais il appelait ensuite
     cette fonction, qui termine par `sapiProject.update()`, une FUSION. Vider
     l'état côté JS ne supprime rien dans la mémoire du navigateur.
     Cas réel constaté par Robin : mémoire « salon », il écrit « salle de bain ».
     Cette pièce n'existe pas dans les sept du référentiel, l'extraction ne peut
     donc rien renvoyer pour `piece` — et « salon » survivait. Le visiteur avait
     explicitement contredit son projet, l'écran affichait toujours « Salon »,
     l'IA était nourrie de « salon », et la sélection finale était celle d'un
     salon. Les deux coexistaient, l'ancien gagnait.
     `set()` remet aussi `advice_text` et l'état contact à zéro, ce qui est
     exactement ce qu'on veut quand le projet est redécrit. */
  function applyFiltersBatch(filters, replaceAll) {
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
    if (window.sapiProject) {
      if (replaceAll) {
        window.sapiProject.set(state.answers, state.labels);
      } else {
        window.sapiProject.update(state.answers, state.labels); // sauvegarde incrémentale
      }
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

  /* Fait défiler la conversation jusqu'à la dernière bulle.
     ⚠️ ON CHERCHE QUI DÉFILE, ON NE LE SUPPOSE PLUS. La version précédente
     visait `.modal__body` en dur, avec le commentaire « Round 4 — le scrollable
     est .modal__body ». C'était vrai à l'époque. Depuis, une passe CSS a posé
     `flex: 1` + `overflow-y: auto` sur `.chat-bubbles` : c'est ce cadre qui
     débordait, `.modal__body` avait exactement la hauteur de son contenu, et
     lui écrire un `scrollTop` ne faisait donc RIEN. La dernière réponse de
     Robin restait coupée en plein milieu — le défaut visible sur la capture.
     Une modification CSS avait silencieusement invalidé une hypothèse JS.
     En remontant jusqu'au premier ancêtre qui déborde réellement, le code
     survit aux deux mises en page : le cadre en desktop, le corps de la modale
     en mobile depuis qu'on a retiré le cadre. La recherche s'arrête à la carte
     de la modale — jamais question de faire défiler la page derrière. */
  function scrollChatToBottom() {
    if (!els.chatMessages) return;
    var el = els.chatMessages;
    var stop = els.modalCard || document.body;
    while (el) {
      if (el.scrollHeight > el.clientHeight + 2) {
        el.scrollTop = el.scrollHeight;
        return;
      }
      if (el === stop) return;
      el = el.parentElement;
    }
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

  /* Les deux sorties du chat.
     ⚠️ UN SEUL BOUTON DEVAIT DEVINER, ET SE TROMPAIT. Quand le projet n'a pas
     de pièce — cas fréquent : le visiteur nomme une pièce absente des sept du
     référentiel, « salle de bain » par exemple — le bouton unique basculait
     sur le contact, alors qu'une vraie sélection existait. Le moteur de
     filtrage n'a jamais eu besoin de la pièce : « au mur » suffit à déduire
     les appliques. C'est la PAGE de sélection qui est indexée sur la pièce.
     L'IA, elle, posait déjà la bonne question : « tu veux qu'on regarde les
     appliques, ou tu veux en parler directement avec Robin ? ». Les deux
     boutons reprennent exactement ces deux chemins, et on cesse de choisir à
     la place du visiteur.
     Quand la pièce EST connue, le second bouton reste masqué : un seul chemin
     a du sens, et on ne dilue pas l'action principale. */
  /* ── LA BARRE DE SORTIE DU CHAT ────────────────────────────────────────────
     Trois situations, et une seule question pour les départager : **connaît-on
     la pièce ?** Elle suffit, parce que la page de sélection est indexée
     dessus — sans pièce, il n'existe aucune URL où envoyer le visiteur.

     1. Pièce connue → un seul bouton, « Voir la sélection ». On ne dilue pas
        l'action principale avec une sortie contact dont personne n'a besoin.
     2. Pièce inconnue, conversation EN COURS → **aucune barre**. L'IA vient de
        poser une question ; lui montrer la sortie au même instant, c'est lui
        répondre « laisse tomber » pendant qu'on lui demande de préciser.
     3. Pièce inconnue, conversation TERMINÉE (`forceExit`) → le contact seul,
        en bouton plein. Là il faut bien proposer quelque chose.

     ⚠️ Le cas « pièce hors périmètre » (salle de bain, garage) ne passe PAS
     par ici : le serveur renvoie `action: "contact"` et les deux appelants
     partent sur `showContact()` avant d'arriver à cette fonction. Ne pas
     confondre « la pièce est hors périmètre » et « on ne connaît pas encore la
     pièce » — c'est la confusion qui a produit les deux derniers défauts, une
     fois dans chaque sens. */
  function revealChatCta(opts) {
    if (!els.chatCta) return;
    var forceExit = !!(opts && opts.forceExit);
    var canSelect = !!projectPiece();

    if (!canSelect && !forceExit) { els.chatCta.hidden = true; return; }
    els.chatCta.hidden = false;

    var contactBtn = els.chatCta.querySelector('[data-chat-cta-contact]');
    var primaryBtn = els.chatCta.querySelector('[data-chat-cta-primary]');

    if (primaryBtn) primaryBtn.hidden = !canSelect;
    if (contactBtn) {
      contactBtn.hidden = canSelect;
      /* Seul en piste, le bouton contact cesse d'être secondaire : le style
         fantôme signifierait « il y a mieux ailleurs », et il n'y a rien. */
      contactBtn.classList.toggle('action-btn--ghost', false);
      contactBtn.classList.toggle('action-btn--primary', true);
    }
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
        // Catégorie déduite côté serveur : destination du bouton « Voir… »
        // quand le projet n'a pas de pièce (cf. revealChatCta).

        /* Freetext = nouvelle description complète : on REMPLACE, et on le fait
           MÊME QUAND L'EXTRACTION NE RENVOIE RIEN.
           ⚠️ C'ÉTAIT LE TROU DU PREMIER CORRECTIF. Le remplacement était
           conditionné à `if (Object.keys(filters).length)` : quand le visiteur
           décrivait quelque chose que le référentiel ne sait pas nommer, le
           modèle ne renvoyait aucun filtre, la branche était sautée, et
           l'ANCIEN PROJET SURVIVAIT INTACT — le pire des cas, puisque c'est
           précisément là que le visiteur a décrit autre chose.
           Cas réel (Robin) : « une petite lampe pour une salle de bain ». La
           salle de bain n'existe pas dans les sept pièces, rien n'est extrait,
           et le site lui reservait la sélection de son projet précédent.
           Sur ce chemin, le message EST une description de projet par
           construction (le champ dit « Décris ton projet en quelques mots ») :
           il n'y a donc pas de cas où l'on voudrait conserver l'ancien.
           Placé dans la branche de SUCCÈS : une panne réseau ou une erreur IA
           ne détruit pas le projet du visiteur. */
        var filters = data.filters || {};
        state.answers = {};
        state.labels = {};
        applyFiltersBatch(filters, true);

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
      /* Plafond atteint : la saisie se verrouille, donc la barre DOIT proposer
         une sortie — d'où `forceExit`. Et le message doit nommer le bouton qui
         sera réellement là : sans pièce, « clique sur Voir la sélection »
         désignait un bouton masqué, sur un écran où l'on ne pouvait plus
         écrire. Cul-de-sac complet, rare mais total. */
      var hasPiece = !!projectPiece();
      addRobinBubble(hasPiece
        ? 'On a bien discuté ! Clique sur Voir la sélection pour découvrir les modèles.'
        : 'On a bien discuté ! Pour aller plus loin sur ce projet, le mieux est qu’on en parle directement.');
      setChatFooterState('locked');
      revealChatCta({ forceExit: true });
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
        // Catégorie déduite côté serveur : destination du bouton « Voir… »
        // quand le projet n'a pas de pièce (cf. revealChatCta).

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
      // ⚠️ « Précise » laissait croire à un complément ; le texte saisi
      // REMPLACE le projet (cf. applyFiltersBatch). Le libellé le dit.
      placeholderText = 'Décris ton projet en quelques mots…';
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

    /* ⚠️ PAS DE BULLE D'ACCUEIL SUR CE CHEMIN — retiré le 25/08.
       Elle a été conçue pour « Préciser avec Robin » depuis le récapitulatif
       (`refineFromS3`), où elle a du sens : le visiteur vient de lire le
       conseil et demande à l'affiner. Réutilisée telle quelle ici, sa prémisse
       est fausse : le visiteur ne précise rien, il décrit un projet NEUF.
       Ce que Robin a constaté : il tape « une grande suspension pour une salle
       de bain », et la conversation s'ouvre sur « Pour un salon, je te propose
       des luminaires à ampoule entourée… » — le conseil du projet mémorisé,
       affiché AVANT son propre message, et parlant d'autre chose.
       Aggravant, et c'était le vrai bug : la bulle était empilée dans
       `conversation`, donc renvoyée à l'IA aux tours suivants. Le modèle lisait
       une phrase que Robin n'a jamais dite, affirmant que le projet était un
       salon, et restait ancré sur la mauvaise pièce.
       Le fil commence désormais par le message du visiteur, comme il se doit. */
    enterChatMode();

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
    { title: 'Installation', steps: ['sortie', 'eclairage', 'hauteur'] },
    { title: 'Esthétique',   steps: ['style'] }
  ];
  var S3_KEY_LABELS = {
    piece: 'Pièce',
    taille: 'Taille',
    taille_escalier: 'Escalier',
    eclairage: 'Éclairage',
    sortie: 'Sortie électrique',
    hauteur: 'Hauteur sous plafond',
    style: 'Style'
  };

  /* ═══════════════════════════════════════════════════════════
     UNE PASTILLE DE PROJET — le markup n'existe QU'ICI
     ═══════════════════════════════════════════════════════════
     ⚠️ Le projet est affiché à plusieurs endroits de la modale, et les formes
     ont DÉJÀ divergé : l'écran contact et l'encart « Filtres appliqués »
     affichent la valeur SANS son mot-clé. Résultat, le récap contact dit
     littéralement « Salon / Salle à manger · Grand · Moderne » — et « Grand »
     tout seul ne veut rien dire.
     Cette forme-ci est la canonique : mot-clé + valeur + icône. C'est la seule
     qui reste juste quand on la sort de son contexte. **Ne pas en écrire une
     quatrième** ; appeler ce constructeur.

     @param sid       clé de la réponse (piece, taille, style…)
     @param opts      { cliquable: bool, motCle: string }
                      `cliquable` : un <button> qui édite la réponse. ⚠️ Sur la
                      fiche produit il DOIT rester faux — voir plus bas.
                      `motCle` : remplace le libellé par défaut (la fiche
                      produit dit « Taille de la pièce », pas « Taille »). */
  function buildProjectChip(sid, opts) {
    opts = opts || {};
    var slug = state.answers[sid];
    if (!slug) return null;
    var step = getStep(sid);
    /* ⚠️ `state.labels` D'ABORD, comme avant ce refactor. `getChoiceLabel`
       ne renvoie jamais de chaîne vide — à défaut de correspondance elle rend
       le slug brut. La mettre en premier rendait les deux termes suivants
       inatteignables : le jour où un slug disparaît du référentiel, le
       visiteur lirait « Pièce : chambre-enfant » alors que le libellé humain
       était disponible juste à côté. Repli silencieux évité. */
    var labelText = state.labels[sid] || getChoiceLabel(sid, slug) || slug;
    var keyLabel = opts.motCle || S3_KEY_LABELS[sid] || sid;

    var chip;
    if (opts.cliquable) {
      /* ⚠️ `data-step-edit` est écouté sur TOUTE la modale, pas sur un écran.
         Toute pastille qui le porte devient cliquable où qu'elle soit — et le
         retour après édition est codé en dur vers l'écran de la page de
         sélection, dont le bouton principal fait QUITTER la fiche produit.
         D'où le `<span>` par défaut. */
      chip = document.createElement('button');
      chip.type = 'button';
      chip.setAttribute('data-step-edit', sid);
      chip.setAttribute('aria-label', 'Modifier ' + keyLabel + ' : ' + labelText);
    } else {
      chip = document.createElement('span');
    }
    chip.className = 'chip chip--project' + (opts.empile ? ' chip--stacked' : '');

    // Icône du choix sélectionné
    var iconName = null;
    if (step && step.choices) {
      for (var i = 0; i < step.choices.length; i++) {
        if (step.choices[i].slug === slug) { iconName = step.choices[i].icon; break; }
      }
    }
    if (iconName && ICONS[iconName]) {
      var iconEl = document.createElement('span');
      iconEl.className = 'chip__icon';
      iconEl.innerHTML = ICONS[iconName];
      chip.appendChild(iconEl);
    }

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
    return chip;
  }

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
        var chip = buildProjectChip(sid, { cliquable: true });
        if (chip) chipsEl.appendChild(chip);
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
  /* ⚠️ LE LIBELLÉ NE DÉCRIVAIT PAS L'EFFET. « Voir la sélection pour mon
     projet » faisait défiler vers `#sapi-product-grid`, c'est-à-dire le
     CATALOGUE COMPLET, non filtré (`posts_per_page => -1`). Séquelle de la
     suppression du filtrage navigateur : la grille n'est plus filtrée par
     personne, donc y descendre ne montre pas « ma sélection » mais « tous les
     modèles ». On route désormais comme le CTA du chat : vers la page de
     sélection de la pièce, ou vers le contact si aucune pièce n'est connue. */
  function viewSelectionFromS3() {
    var project = window.sapiProject ? window.sapiProject.get() : null;
    var needsNewAdvice = !project || !project.advice_text;
    if (needsNewAdvice) {
      showTransitionAndExit({ source: 's3' });
      return;
    }
    // Le conseil est déjà en mémoire : pas d'appel IA, on emmène directement.
    if (!immersionIsOnPage()) {
      var piece = projectPiece();
      if (piece) { goToSelectionPage(piece); return; }
      showContact({
        message: 'Je n’ai pas encore assez d’éléments pour te proposer une sélection. Laisse-moi ton mail et deux mots sur ton projet, je te réponds moi-même.'
      });
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
    /* Le CTA est visible d'emblée (le conseil est déjà en mémoire), mais il
       DOIT passer par revealChatCta : c'était le seul chemin du fichier qui
       forçait `hidden = false` à la main, et il ressortait donc avec l'état
       visuel laissé par la conversation précédente. */
    revealChatCta();
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
  /* ⚠️ MÊME CALCUL QUE LA PRÉSÉLECTION, JAMAIS UN DEUXIÈME.
     Cette fonction refaisait tout de son côté : sa propre table `taille →
     index`, son propre `querySelector` sur le seul nom `attribute_pa_taille`,
     et sa propre règle pour l'escalier. Résultat, sur le MÊME écran : l'encart
     affichait la taille du milieu pour un escalier ouvert, et la phrase juste
     en dessous annonçait « cette grande taille ». Le site appliquait une chose
     et en écrivait une autre.

     ⚠️ POURQUOI ON PARTAGE LE CALCUL ET NON LE RÉSULTAT. Il serait tentant de
     lire l'étiquette `data-sapi-recommandation` que la présélection pose sur le
     formulaire. Ça ne marche pas ici : `openModal()` MET EN PAUSE les
     notifications du projet, donc l'étiquette n'est réécrite qu'à la FERMETURE
     de la modale — après cet écran. On lirait une étiquette absente (visiteur
     sans projet préalable, le parcours le plus fréquent) ou périmée, et le
     conseil de taille juste en dessous, lui calculé sur les réponses
     courantes, la contredirait. Le défaut d'origine, revenu par une porte de
     service. Trouvé en relecture, avant livraison. */
  function readTailleLabelFromProductSelect(answers) {
    var form = document.querySelector('form.variations_form');
    if (!form) return '';
    // Le menu de TAILLE : celui des variations qui n'est pas la matière.
    var sel = null;
    var sels = form.querySelectorAll('.variations select');
    for (var i = 0; i < sels.length; i++) {
      var n = sels[i].name || '';
      if (n.indexOf('materiau') === -1 && n.indexOf('essence') === -1) { sel = sels[i]; break; }
    }
    if (!sel) return '';
    var options = [];
    for (var j = 0; j < sel.options.length; j++) {
      if (sel.options[j].value) options.push(sel.options[j]);
    }
    if (!options.length || !window.sapiProject || !window.sapiProject.resoudreTaille) return '';
    var option = window.sapiProject.resoudreTaille(options, window.sapiProject.tailleIntention(answers));
    return option ? (option.textContent || option.text || '').trim() : '';
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
  /* `buildRecapIntro` produisait « Pour ton bureau, Robin recommande : ».
     Retirée avec la phrase : la pastille « Pièce : Bureau » dit la même chose
     en moins de place, et le bandeau « Ce que je te recommande sur ce modèle »
     dit le reste. La laisser en place aurait fait un vestige de plus.
     ⚠️ `PIECE_TUTOIEMENT` juste au-dessus n'a plus AUCUN lecteur dans ce
     fichier. Elle est conservée volontairement : c'est le miroir JS de
     `sapi_piece_possessive()` côté PHP, et le prochain écran qui tutoiera une
     pièce en aura besoin. Ne pas la recopier ailleurs. */

  // Affiche l'écran s-product-recap (immédiat, aucun fetch).
  function showProductRecap() {
    state.shortMode = true;

    var answers = state.answers;
    var labels = state.labels;
    var style = answers.style;
    var essence = (window.sapiProject && window.sapiProject.styleToEssence)
      ? (window.sapiProject.styleToEssence(answers) || null)
      : null;
    var essenceLabel = essence ? ESSENCE_LABEL[essence] : '';
    var tailleLabel = readTailleLabelFromProductSelect(answers);

    /* ── TON PROJET ────────────────────────────────────────────────────────
       On n'affiche QUE les réponses dont sort le conseil : la pièce donne le
       contexte, la taille de la pièce donne la taille du luminaire, le style
       donne l'essence. Sortie électrique, hauteur et éclairage servent à
       choisir QUELS modèles montrer, pas quelle version de celui-ci : les
       afficher ici promettrait un effet qu'elles n'ont pas sur cet écran.
       La phrase à se raconter : « on montre les réponses dont sort le conseil,
       et rien d'autre. »
       ⚠️ Pastilles NON cliquables ici : l'écouteur d'édition est global à la
       modale et son retour ramène vers l'écran de la page de sélection, dont
       le bouton principal fait quitter la fiche produit. */
    var chipsProjet = ['piece', 'taille', 'taille_escalier', 'style'];
    var MOTS_CLES_PRODUIT = { taille: 'Taille de la pièce' };
    if (els.productRecapProject) {
      els.productRecapProject.innerHTML = '';
      var nbChips = 0;
      chipsProjet.forEach(function (sid) {
        var chip = buildProjectChip(sid, { cliquable: false, empile: true, motCle: MOTS_CLES_PRODUIT[sid] });
        if (chip) { els.productRecapProject.appendChild(chip); nbChips++; }
      });
      els.productRecapProject.hidden = !nbChips;
      if (els.productRecapProjectLabel) els.productRecapProjectLabel.hidden = !nbChips;
    }

    // Récap card : Essence + Taille (chacune masquée si non disponible)
    var hasEssence = !!essence;
    var hasTaille = !!tailleLabel;
    var aReco = hasEssence || hasTaille;
    if (els.productRecapCard) els.productRecapCard.hidden = !aReco;
    if (els.productRecapRecoLabel) els.productRecapRecoLabel.hidden = !aReco;
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

    /* Conseil de style — il suit SA ligne, comme celui de la taille.
       La note vit maintenant DANS la carte, juste sous « Essence / Okoumé » :
       si cette ligne n'est pas affichée, sa phrase n'a rien à commenter. */
    if (els.productRecapConseil) {
      var conseil = (hasEssence && style && STYLE_CONSEILS[style]) || '';
      els.productRecapConseil.textContent = conseil;
      els.productRecapConseil.hidden = !conseil;
    }

    /* Conseil de taille — LE MÊME CALCUL QUE L'ENCART, pas un deuxième.
       ⚠️ Ce bloc dérivait son slug tout seul : `ouvert → grande`,
       `standard → petite`. L'encart, lui, affichait la taille du milieu pour
       un escalier ouvert et rien du tout pour un standard. Les deux étaient sur
       le même écran, l'un sous l'autre : le site appliquait une taille et en
       annonçait une autre, et il commentait une taille qu'il n'affichait pas.
       L'intention vient maintenant de la source unique (`sapi-project.js`), la
       même qui pilote la présélection.

       Et la phrase suit la LIGNE : si l'encart n'annonce aucune taille, il n'y
       a rien à commenter. Une phrase orpheline sous une ligne absente. */
    if (els.productRecapConseilTaille) {
      var intention = (window.sapiProject && window.sapiProject.tailleIntention)
        ? window.sapiProject.tailleIntention(answers)
        : null;
      // 'max' = la plus grande disponible : c'est bien du grand qu'on parle.
      var slugConseil = (intention === 'max') ? 'grande' : intention;
      var conseilTaille = (hasTaille && slugConseil && SIZE_CONSEILS[slugConseil]) || '';
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
    var orderedKeys = ['piece', 'taille', 'taille_escalier', 'eclairage', 'sortie', 'hauteur', 'style'];
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

    /* ⚠️ `contact_triggered` n'était envoyé QUE dans le payload du submit,
       à côté de `contact_submitted: 1` — les deux colonnes valaient donc
       toujours la même chose. Conséquences pour Robin : la pastille
       « Abandon » du tableau était du code mort, jamais affichée, et le
       visiteur qui ATTEINT le formulaire sans l'envoyer était indiscernable
       de celui qui a simplement fermé la modale.
       C'est exactement la population des pièces hors périmètre — celles qu'on
       vient d'y router. Sans cette ligne, on les envoie vers un formulaire
       dont on ne saura jamais combien l'ont vu sans écrire. */
    SessionTracker.snapshot({ contact_triggered: 1 });
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
    fd.append('sapi_ts', config.ts || '');
    fd.append('sapi_tsig', config.tsig || '');
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
    /* ⚠️ CES DEUX-LÀ FUYAIENT D'UNE OUVERTURE À L'AUTRE.
       `editFromS3` n'était remis à `false` que dans UNE branche de
       `answerCurrentQuestion`. Le visiteur qui ouvrait le récap, cliquait une
       chip pour corriger, puis se ravisait et fermait, laissait le drapeau
       levé : à la session suivante, arrivé au bout du questionnaire, il
       retombait sur le récap au lieu d'obtenir le conseil de Robin et sa
       sélection. Le conseil n'était jamais calculé.
       `chat.conversation` n'était vidé que sur deux chemins d'entrée : le
       tracking pouvait donc réémettre la conversation de la session
       précédente. */
    state.editFromS3 = false;
    state.chat.conversation = [];

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
    dispatchConseillerClosed();
  }

  // Événement déterministe émis à CHAQUE fermeture de la modale (fin ou
  // abandon), porteur des réponses finales. Plus fiable que d'écouter le
  // subscribe sapiProject côté immersion (qui dépend du flush pendingNotify
  // du resume → « ne se recharge pas tout le temps »).
  function dispatchConseillerClosed() {
    var answers = {};
    try {
      if (window.sapiProject && typeof window.sapiProject.get === 'function') {
        answers = window.sapiProject.get().answers || {};
      }
    } catch (e) { /* swallow */ }
    document.dispatchEvent(new CustomEvent('sapi:conseiller-closed', {
      detail: { answers: answers }
    }));
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
          /* CTA « Voir la sélection » du chat.

             Garde-fou : sans pièce ni critère, il n'y a rien à montrer. Le
             bouton est déjà masqué dans ce cas (revealChatCta) — ceci est la
             ceinture, pas la bretelle. Elle compte quand même : SUR LA PAGE
             D'IMMERSION, le chemin normal repartait en « moment 2 » et
             re-servait la sélection de l'ancienne pièce, la page derrière
             n'ayant jamais changé. C'est exactement ce que Robin a vu — les
             modèles du projet précédent sous une conversation sur une salle
             de bain. */
          if (!projectPiece()) {
            showContact({
              message: 'Pour cette pièce, je préfère te répondre moi-même. Laisse-moi ton mail et un mot sur ton projet.'
            });
            break;
          }
          // F2a-bis : écran transition + appel IA unique (avec la conversation),
          // puis save + close.
          showTransitionAndExit({
            source: 's2',
            conversation: (state.chat && state.chat.conversation) || [],
          });
          break;
        case 'chat-contact':
          // Seconde sortie du chat, celle que l'IA propose elle-même quand
          // elle sent que le projet mérite un échange direct.
          if (window.sapiProject) window.sapiProject.set(state.answers, state.labels);
          showContact({
            message: 'Dis-m’en un peu plus et laisse-moi ton mail : je te réponds moi-même.'
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
    els.productRecapProject      = els.modal.querySelector('[data-product-recap-project]');
    els.productRecapProjectLabel = els.modal.querySelector('[data-product-recap-project-label]');
    els.productRecapRecoLabel    = els.modal.querySelector('[data-product-recap-reco-label]');
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

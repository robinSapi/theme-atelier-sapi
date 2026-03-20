/**
 * Robin Conseiller V2 — Modale diaporama
 *
 * State machine + rendu fiches + AJAX lazy + localStorage sync.
 * Données des questions : sapiRobinConseiller.steps (via wp_localize_script).
 *
 * @package Theme_Sapi_Maison
 */
(function () {
  'use strict';

  /* ═══════════════════════════════════════════
     Config & refs (injectés par PHP)
  ═══════════════════════════════════════════ */
  var config = window.sapiRobinConseiller || {};
  var steps    = config.steps || [];
  var icons    = config.icons || {};
  var conseils = config.conseils || {};
  var AJAX_URL    = config.ajaxUrl || '/wp-admin/admin-ajax.php';
  var NONCE       = config.nonce || '';
  var STORAGE_KEY = 'sapiGuidePrefs';

  /* ═══════════════════════════════════════════
     State
  ═══════════════════════════════════════════ */
  var state = {
    answers: {},
    labels: {},
    currentStep: null,
    history: [],
    openingContext: '',
    contextData: {},
    conversation: [],
    aiCache: {},
    pendingXhr: null,
    isOpen: false
  };

  /* ═══════════════════════════════════════════
     localStorage helpers (même format que V1)
  ═══════════════════════════════════════════ */
  function safeLoad() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}'); }
    catch (e) { return {}; }
  }

  function safeSave(data) {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(data)); }
    catch (e) { /* quota exceeded */ }
  }

  function loadState() {
    var data = safeLoad();
    if (data && data.answers) {
      state.answers = data.answers;
      state.labels  = data.labels || {};
    } else {
      state.answers = {};
      state.labels  = {};
    }
  }

  function saveState() {
    var essenceMap = { moderne: 'peuplier', ancien: 'okoume' };
    var tailleMap  = { petite: 0, moyenne: 1, grande: 2 };

    var style  = state.answers.style || null;
    var taille = state.answers.taille || null;

    var existing = safeLoad();

    existing.answers    = state.answers;
    existing.labels     = state.labels;
    existing.essence    = essenceMap[style] || null;
    existing.tailleIndex = taille in tailleMap ? tailleMap[taille] : null;
    existing.pieceLabel  = state.labels.piece || null;
    existing.styleLabel  = state.labels.style || null;
    existing.tailleLabel = state.labels.taille || null;

    if (!existing.recommendedIds) {
      existing.recommendedIds = [];
    }

    safeSave(existing);
  }

  /* ═══════════════════════════════════════════
     Logique de visibilité (portée de guide-data.php)
  ═══════════════════════════════════════════ */
  function getVisibleSteps() {
    var visible = [];
    for (var i = 0; i < steps.length; i++) {
      var step = steps[i];
      var vis  = step.visibility;

      if (vis === 'always') {
        visible.push(step.id);
        continue;
      }

      if (typeof vis === 'object' && vis !== null) {
        if (vis._or) {
          var orMatch = false;
          for (var g = 0; g < vis._or.length; g++) {
            var group = vis._or[g];
            var groupOk = true;
            for (var key in group) {
              if (!group.hasOwnProperty(key)) continue;
              var answer = state.answers[key];
              if (!answer || group[key].indexOf(answer) === -1) {
                groupOk = false;
                break;
              }
            }
            if (groupOk) { orMatch = true; break; }
          }
          if (orMatch) visible.push(step.id);
        } else {
          var show = true;
          for (var key in vis) {
            if (!vis.hasOwnProperty(key)) continue;
            var answer = state.answers[key];
            if (!answer || vis[key].indexOf(answer) === -1) {
              show = false;
              break;
            }
          }
          if (show) visible.push(step.id);
        }
      }
    }
    return visible;
  }

  function cleanInvisibleAnswers() {
    var visible = getVisibleSteps();
    var changed = false;
    for (var stepId in state.answers) {
      if (state.answers.hasOwnProperty(stepId) && visible.indexOf(stepId) === -1) {
        delete state.answers[stepId];
        delete state.labels[stepId];
        changed = true;
      }
    }
    return changed;
  }

  function getStepById(id) {
    for (var i = 0; i < steps.length; i++) {
      if (steps[i].id === id) return steps[i];
    }
    return null;
  }

  function getNextStep(afterStepId) {
    var visible = getVisibleSteps();
    var idx = visible.indexOf(afterStepId);
    if (idx === -1 || idx >= visible.length - 1) return null;

    // Cherche le prochain step visible qui n'est pas encore répondu
    for (var i = idx + 1; i < visible.length; i++) {
      if (!state.answers[visible[i]]) return visible[i];
    }
    // Tous répondus → recommandation
    return 'recommendation';
  }

  function getFirstUnansweredStep() {
    var visible = getVisibleSteps();
    for (var i = 0; i < visible.length; i++) {
      if (!state.answers[visible[i]]) return visible[i];
    }
    return 'recommendation';
  }

  function hasAnyAnswer() {
    for (var k in state.answers) {
      if (state.answers.hasOwnProperty(k)) return true;
    }
    return false;
  }

  /* ═══════════════════════════════════════════
     Question dynamique (table/îlot selon pièce)
  ═══════════════════════════════════════════ */
  function getQuestionText(step) {
    if (step.dynamic_question && step.dynamic_question.piece) {
      var piece = state.answers.piece;
      if (piece && step.dynamic_question.piece[piece]) {
        return step.dynamic_question.piece[piece];
      }
    }
    return step.question;
  }

  /* ═══════════════════════════════════════════
     Résumé du projet (quand on rouvre la modale)
  ═══════════════════════════════════════════ */
  function buildProjectSummary() {
    var parts = [];
    var visible = getVisibleSteps();
    for (var i = 0; i < visible.length; i++) {
      var lbl = state.labels[visible[i]];
      if (lbl) parts.push(lbl.toLowerCase());
    }
    if (parts.length === 0) return 'Continuons à définir votre projet.';

    var piece = state.labels.piece || '';
    var summary = 'Vous cherchez un luminaire';
    if (piece) summary += ' pour votre ' + piece.toLowerCase();
    summary += '. ';

    if (parts.length > 1) {
      summary += 'Continuons à affiner votre projet.';
    } else {
      summary += 'Dites-m\'en plus pour que je vous conseille au mieux.';
    }
    return summary;
  }

  /* ═══════════════════════════════════════════
     Lien sortant dynamique vers /nos-creations/
  ═══════════════════════════════════════════ */
  function buildShopLink() {
    return {
      url: '/nos-creations/?robin_selection=1',
      label: 'Voir les mod\u00e8les filtr\u00e9s pour votre projet'
    };
  }

  /* ═══════════════════════════════════════════
     Lookup conseil pré-généré
  ═══════════════════════════════════════════ */
  function getConseil(stepId, slug) {
    // Construire la clé selon les règles de croisement par étape
    var piece = state.answers.piece || '';
    var taille = state.answers.taille || '';
    var taille_esc = state.answers.taille_escalier || '';
    var key = '';

    switch (stepId) {
      case 'piece':
        key = 'piece:' + slug;
        break;

      case 'taille':
        key = 'taille:' + slug + '|piece:' + piece;
        break;

      case 'taille_escalier':
        key = 'taille_escalier:' + slug;
        break;

      case 'eclairage':
        key = 'eclairage:' + slug + '|piece:' + piece;
        break;

      case 'sortie':
        var eclairage = state.answers.eclairage || '';
        if (piece === 'escalier') {
          key = 'sortie:' + slug + '|piece:escalier|taille_escalier:' + taille_esc;
        } else if (taille === 'grande' && eclairage) {
          key = 'sortie:' + slug + '|piece:' + piece + '|taille:grande|eclairage:' + eclairage;
        } else {
          key = 'sortie:' + slug + '|piece:' + piece + '|taille:' + taille;
        }
        break;

      case 'hauteur':
        var eclairageH = state.answers.eclairage || '';
        if (piece === 'escalier') {
          key = 'hauteur:' + slug + '|piece:escalier|taille_escalier:' + taille_esc;
        } else if (taille === 'grande' && eclairageH) {
          key = 'hauteur:' + slug + '|piece:' + piece + '|taille:grande|eclairage:' + eclairageH;
        } else {
          key = 'hauteur:' + slug + '|piece:' + piece + '|taille:' + taille;
        }
        break;

      case 'table':
        key = 'table:' + slug + '|piece:' + piece;
        break;

      case 'style':
        key = 'style:' + slug;
        break;

      default:
        key = stepId + ':' + slug;
    }

    return conseils[key] || null;
  }

  /* ═══════════════════════════════════════════
     DOM refs
  ═══════════════════════════════════════════ */
  var modal, overlay, body, backBtn, closeBtn, badgeEl;

  function initDomRefs() {
    modal      = document.getElementById('robin-modal');
    overlay    = document.getElementById('robin-modal-overlay');
    body       = document.getElementById('robin-modal-body');
    backBtn    = document.getElementById('robin-modal-back');
    closeBtn   = document.getElementById('robin-modal-close');
    badgeEl    = document.getElementById('robin-modal-badge');
  }

  /* ═══════════════════════════════════════════
     Modale : ouverture / fermeture
  ═══════════════════════════════════════════ */
  function openModal(context, contextData) {
    if (!modal) return;

    state.openingContext = context || 'bandeau';
    state.contextData   = contextData || {};
    state.history       = [];
    state.conversation  = [];

    var startStep;

    switch (state.openingContext) {
      case 'bandeau':
        if (hasAnyAnswer()) {
          var next = getFirstUnansweredStep();
          // Si tout est répondu, afficher la fiche merci (recommandation)
          startStep = next;
        } else {
          startStep = steps[0].id;
        }
        break;

      case 'category':
        // Fiche de confirmation hardcodée — traitée dans showFiche
        startStep = '_category_confirm';
        break;

      case 'product':
        // Appel IA comparaison — traité dans showFiche
        startStep = '_product_compare';
        break;

      case 'homepage':
        // La pièce est déjà sélectionnée via contextData.piece
        if (state.contextData.piece) {
          var pieceStep = getStepById('piece');
          if (pieceStep) {
            var choice = null;
            for (var c = 0; c < pieceStep.choices.length; c++) {
              if (pieceStep.choices[c].slug === state.contextData.piece) {
                choice = pieceStep.choices[c];
                break;
              }
            }
            if (choice) {
              state.answers.piece = choice.slug;
              state.labels.piece  = choice.label;
              cleanInvisibleAnswers();
              saveState();
            }
          }
          startStep = getNextStep('piece') || 'taille';
        } else {
          startStep = steps[0].id;
        }
        break;

      default:
        startStep = steps[0].id;
    }

    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    state.isOpen = true;

    updateModalProject();
    showFiche(startStep);
  }

  function closeModal() {
    if (!modal) return;

    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    state.isOpen = false;

    // Retirer le mode étendu + cinématique
    var container = document.querySelector('.robin-modal__container');
    if (container) container.classList.remove('robin-modal__container--expanded');
    body.classList.remove('robin-modal__body--reco');
    modal.classList.remove('robin-modal--reco');

    // Restaurer le header
    var projectEl = document.getElementById('robin-modal-project');
    if (projectEl) {
      projectEl.classList.remove('is-hidden');
      projectEl.querySelectorAll('.is-fading').forEach(function(el) { el.classList.remove('is-fading'); });
    }
    if (backBtn) { backBtn.classList.remove('is-hidden'); backBtn.style.opacity = ''; backBtn.style.transition = ''; }
    var badgeRestore = document.getElementById('robin-modal-badge');
    if (badgeRestore) { badgeRestore.style.opacity = ''; badgeRestore.style.transition = ''; }
    var closeRestore = document.getElementById('robin-modal-close');
    if (closeRestore) { closeRestore.style.opacity = ''; closeRestore.style.transition = ''; }
    var headerRestore = document.getElementById('robin-modal-header');
    if (headerRestore) headerRestore.classList.remove('is-collapsed');
    var projectRestore = document.getElementById('robin-modal-project');
    if (projectRestore) projectRestore.classList.remove('is-collapsed');

    // Restaurer le rideau
    var curtain = document.getElementById('robin-modal-curtain');
    var bulb = document.getElementById('robin-modal-curtain-bulb');
    if (curtain) { curtain.classList.remove('robin-modal__curtain--closing', 'robin-modal__curtain--opening'); }
    if (bulb) { bulb.classList.remove('robin-modal__curtain-bulb--visible', 'robin-modal__curtain-bulb--fading'); }

    // Annuler tout AJAX en cours
    if (state.pendingXhr) {
      state.pendingXhr.abort();
      state.pendingXhr = null;
    }

    saveState();
    refreshPageVisuals();
  }

  /* ═══════════════════════════════════════════
     Rendu d'une fiche
  ═══════════════════════════════════════════ */
  function showFiche(stepId) {
    state.currentStep = stepId;

    // Navigation : retour + titre
    updateHeader(stepId);

    // Fiches spéciales (contextes d'ouverture)
    if (stepId === '_category_confirm') {
      renderCategoryConfirm();
      return;
    }
    if (stepId === '_product_compare') {
      renderProductCompare();
      return;
    }
    if (stepId === 'recommendation') {
      renderRecommendation();
      return;
    }
    if (stepId === 'hors_parcours') {
      renderHorsParcours();
      return;
    }

    // Fiche questionnaire standard
    var step = getStepById(stepId);
    if (!step) return;

    // Première fiche (piece) sans réponse = texte d'accueil hardcodé, pas d'appel IA
    var isFirstFiche = (stepId === steps[0].id && state.history.length === 0);

    var html = '';
    var shouldAnimate = true;

    // ─── Zone haute : conseil centré ───
    html += '<div class="robin-fiche__top">';
    html += '<div class="robin-fiche__conseil" id="robin-fiche-conseil">';
    if (isFirstFiche) {
      html += renderConseil({ conseil_text: 'Chaque luminaire que je cr\u00e9e est une pi\u00e8ce unique, fa\u00e7onn\u00e9e \u00e0 la main dans mon atelier. Pour vous orienter au mieux, dites-moi dans quelle pi\u00e8ce vous imaginez votre futur luminaire.' }, true);
    } else {
      var lastStep = state.history.length > 0 ? state.history[state.history.length - 1] : null;
      var lastSlug = lastStep ? state.answers[lastStep] : null;
      var conseilData = lastStep && lastSlug ? getConseil(lastStep, lastSlug) : null;
      if (conseilData) {
        html += renderConseil(conseilData, true);
      } else if (hasAnyAnswer()) {
        // Résumé du projet quand on rouvre la modale avec des réponses existantes
        html += renderConseil({ conseil_text: buildProjectSummary() }, true);
      } else {
        shouldAnimate = false;
      }
    }
    html += '</div>';

    // 2 liens CTA — présents quand pièce + taille sont renseignées
    var hasMinAnswers = state.answers.piece && (state.answers.taille || state.answers.taille_escalier);
    if (!isFirstFiche && hasMinAnswers) {
      var shopLink = buildShopLink();

      // Lien 2 : Contacter Robin OU Sur mesure selon contexte
      var lastStepForLink = state.history.length > 0 ? state.history[state.history.length - 1] : null;
      var lastSlugForLink = lastStepForLink ? state.answers[lastStepForLink] : null;
      var conseilForLink = lastStepForLink && lastSlugForLink ? getConseil(lastStepForLink, lastSlugForLink) : null;
      var isConstrained = conseilForLink && conseilForLink.link_url === '/contact/';
      var showSurMesure = isConstrained || state.answers.taille === 'grande' || state.answers.hauteur === 'haute';

      html += '<div class="robin-fiche__cta-links" id="robin-fiche-link"' + (shouldAnimate ? ' style="opacity:0;"' : '') + '>';
      html += '<a class="robin-fiche__cta-link" href="' + escHtml(shopLink.url) + '">' + escHtml(shopLink.label) + ' &rarr;</a>';
      if (showSurMesure) {
        html += '<a class="robin-fiche__cta-link" href="/sur-mesure/">Imaginer un mod\u00e8le sur mesure &rarr;</a>';
      } else {
        html += '<a class="robin-fiche__cta-link" href="/contact/">Contacter Robin &rarr;</a>';
      }
      html += '</div>';
    }
    html += '</div>';

    // ─── Zone basse : question + boutons + input (cachée si animée) ───
    html += '<div class="robin-fiche__bottom" id="robin-fiche-bottom"' + (shouldAnimate ? ' style="opacity:0;"' : '') + '>';

    // Question
    html += '<div class="robin-fiche__question">' + escHtml(getQuestionText(step)) + '</div>';

    // Boutons choix
    html += '<div class="robin-fiche__choices">';
    for (var i = 0; i < step.choices.length; i++) {
      var c = step.choices[i];
      var selected = state.answers[stepId] === c.slug ? ' is-selected' : '';
      var iconHtml = icons[c.icon] ? '<span class="robin-fiche__choice-icon">' + icons[c.icon] + '</span>' : '';
      var dimHtml  = c.dim ? ' <span class="robin-fiche__choice-dim">' + escHtml(c.dim) + '</span>' : '';
      html += '<button class="robin-fiche__choice' + selected + '" data-step="' + escAttr(stepId) + '" data-slug="' + escAttr(c.slug) + '" data-label="' + escAttr(c.label) + '">';
      html += iconHtml + escHtml(c.label) + dimHtml;
      html += '</button>';
    }
    html += '</div>';

    // Champ texte libre
    html += renderTextInput();

    html += '</div>';

    body.innerHTML = html;

    if (shouldAnimate) {
      animateConseil();
    }
  }

  /* ═══════════════════════════════════════════
     Rendu partiel : conseil, loader, input
  ═══════════════════════════════════════════ */
  function renderConseil(data, animate) {
    if (!data || !data.conseil_text) return '';
    var html = '<div class="robin-fiche__citation">';
    if (animate) {
      // Mots avec opacity 0, animés ensuite par animateConseil()
      var words = data.conseil_text.split(' ');
      html += '<p class="robin-fiche__citation-text robin-fiche__citation-text--animated">';
      html += '<span class="robin-word robin-word--quote" style="opacity:0;">\u00AB\u00A0</span>';
      for (var i = 0; i < words.length; i++) {
        html += '<span class="robin-word" style="opacity:0;">' + escHtml(words[i]) + '</span> ';
      }
      html += '<span class="robin-word robin-word--quote" style="opacity:0;">\u00A0\u00BB</span>';
      html += '</p>';
      html += '<span class="robin-fiche__signature" style="opacity:0;">&mdash; Robin</span>';
    } else {
      html += '<p class="robin-fiche__citation-text">' + escHtml(data.conseil_text) + '</p>';
      html += '<span class="robin-fiche__signature">&mdash; Robin</span>';
    }
    html += '</div>';
    return html;
  }

  function animateConseil() {
    var words = body.querySelectorAll('.robin-word');
    var signature = body.querySelector('.robin-fiche__signature');
    var bottom = document.getElementById('robin-fiche-bottom');
    var startDelay = 500; // 0.5s avant de commencer
    var wordDelay = 50;    // 50ms entre chaque mot

    for (var i = 0; i < words.length; i++) {
      (function(el, d) {
        setTimeout(function() {
          el.style.transition = 'opacity 0.25s';
          el.style.opacity = '1';
        }, d);
      })(words[i], startDelay + i * wordDelay);
    }

    var endTime = startDelay + words.length * wordDelay;

    // Signature apparaît après le dernier mot
    if (signature) {
      setTimeout(function() {
        signature.style.transition = 'opacity 0.4s';
        signature.style.opacity = '1';
      }, endTime + 200);
    }

    // Lien sortant apparaît après la signature
    var link = document.getElementById('robin-fiche-link');
    if (link) {
      setTimeout(function() {
        link.style.transition = 'opacity 0.4s';
        link.style.opacity = '1';
      }, endTime + 500);
    }

    // Zone basse apparaît après le lien
    if (bottom) {
      setTimeout(function() {
        bottom.style.transition = 'opacity 0.4s';
        bottom.style.opacity = '1';
      }, endTime + 800);
    }
  }

  function renderConseilLoader() {
    return '<div class="robin-fiche__loader">' +
      '<div class="robin-fiche__loader-icon">' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>' +
      '</div>' +
      '<p class="robin-fiche__loader-text" id="robin-loader-text-1">Analyse du catalogue de Robin...</p>' +
      '<p class="robin-fiche__loader-text robin-fiche__loader-text--hidden" id="robin-loader-text-2">Et de ses conseils aussi !</p>' +
    '</div>';
  }

  function animateLoader() {
    var text2 = document.getElementById('robin-loader-text-2');
    if (text2) {
      setTimeout(function() {
        text2.classList.remove('robin-fiche__loader-text--hidden');
      }, 2000);
    }
  }

  function renderTextInput() {
    return '<div class="robin-fiche__input-wrap">' +
      '<div class="robin-fiche__input">' +
      '<input type="text" id="robin-text-input" placeholder="&Eacute;crire &agrave; Robin..." autocomplete="off">' +
      '<button type="button" class="robin-fiche__send" id="robin-text-send">&uarr;</button>' +
      '</div></div>';
  }

  /* ═══════════════════════════════════════════
     Fiches spéciales
  ═══════════════════════════════════════════ */
  function renderCategoryConfirm() {
    var slug = state.contextData.category_slug || '';
    var labelMap = {
      'suspensions': 'une suspension',
      'appliques': 'une applique murale',
      'lampadaires': 'un lampadaire',
      'lampesaposer': 'une lampe à poser'
    };
    var label = labelMap[slug] || 'ce type de luminaire';

    var html = '<div class="robin-fiche__conseil">';
    html += '<div class="robin-fiche__citation">';
    html += '<p class="robin-fiche__citation-text">C\'est bien ' + escHtml(label) + ' que vous cherchez ?</p>';
    html += '</div></div>';

    html += '<div class="robin-fiche__choices">';
    html += '<button class="robin-fiche__choice" id="robin-category-yes">Oui</button>';
    html += '<button class="robin-fiche__choice" id="robin-category-no">Non, autre chose</button>';
    html += '</div>';

    html += renderTextInput();
    body.innerHTML = html;
  }

  function renderProductCompare() {
    // TODO Phase D : appel IA comparaison produit/projet
    var html = '<div class="robin-fiche__conseil">';
    html += renderConseilLoader();
    html += '</div>';
    html += renderTextInput();
    body.innerHTML = html;
  }

  function renderRecommendation() {
    // Fiche "merci" — 2 boutons
    var html = '<div class="robin-fiche__top">';
    html += '<div class="robin-fiche__conseil">';
    html += renderConseil({ conseil_text: 'C\'est not\u00e9 ! Avec toutes ces infos, nous allons pouvoir vous proposer les meilleurs mod\u00e8les et vous aider \u00e0 choisir.' }, true);
    html += '</div>';
    html += '</div>';

    html += '<div class="robin-fiche__bottom" id="robin-fiche-bottom" style="opacity:0;">';
    html += '<div class="robin-fiche__choices robin-fiche__choices--reco">';
    html += '<button class="robin-fiche__choice robin-fiche__choice--primary" id="robin-reco-conseil">Voir le mod\u00e8le que Robin me conseille</button>';
    html += '<a class="robin-fiche__choice robin-fiche__choice--link" href="/nos-creations/?robin_selection=1">Voir tous les mod\u00e8les adapt\u00e9s \u00e0 mon projet</a>';
    html += '</div>';
    html += '</div>';

    body.innerHTML = html;
    animateConseil();
  }

  function onRecoConseilClick() {
    var curtain = document.getElementById('robin-modal-curtain');
    var bulb = document.getElementById('robin-modal-curtain-bulb');
    var container = document.querySelector('.robin-modal__container');
    var recoData = null;
    var recoReady = false;

    // 1. Overlay sombre
    modal.classList.add('robin-modal--reco');

    // 2. Chips disparaissent + rideau descend EN MÊME TEMPS
    animateHeaderSimplify();
    if (curtain) curtain.classList.add('robin-modal__curtain--closing');

    // 3. Après fermeture rideau (~2s), ampoule apparaît
    setTimeout(function() {
      if (bulb) bulb.classList.add('robin-modal__curtain-bulb--visible');
      // 4. Modale commence à s'agrandir APRÈS fermeture rideau
      if (container) container.classList.add('robin-modal__container--expanded');
    }, 2000);

    // 5. Appel AJAX en parallèle
    var fd = new FormData();
    fd.append('action', 'sapi_robin_conseil_step');
    fd.append('nonce', NONCE);
    fd.append('guide_website', '');
    fd.append('step_id', 'recommendation');
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('opening_context', state.openingContext);
    fd.append('context_data', JSON.stringify(state.contextData));
    fd.append('user_message', '');
    fd.append('conversation', JSON.stringify(state.conversation));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', AJAX_URL, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            recoData = resp.data;
          }
        } catch (e) {}
      }
      recoReady = true;
      // Si l'ampoule est déjà visible, ouvrir le rideau
      if (bulb && bulb.classList.contains('robin-modal__curtain-bulb--visible')) {
        openCurtain();
      }
    };
    xhr.send(fd);

    // Quand l'ampoule est visible ET la réponse est prête → ouvrir
    // Si la réponse arrive avant l'ampoule, on attend l'ampoule
    var ampouleCheckInterval = setInterval(function() {
      if (recoReady && bulb && bulb.classList.contains('robin-modal__curtain-bulb--visible')) {
        clearInterval(ampouleCheckInterval);
        // Laisser l'ampoule pulser au moins 1.5s
        setTimeout(openCurtain, 1500);
      }
    }, 200);

    function openCurtain() {
      // Masquer complètement le header
      var headerEl = document.getElementById('robin-modal-header');
      var projectEl2 = document.getElementById('robin-modal-project');
      if (headerEl) headerEl.classList.add('is-collapsed');
      if (projectEl2) projectEl2.classList.add('is-collapsed');

      // Préparer le contenu DERRIÈRE le rideau (déjà en place)
      body.classList.add('robin-modal__body--reco');
      if (recoData) {
        renderRecoResult(recoData, true);
      } else {
        body.innerHTML = '<div class="robin-reco">' +
          '<div class="robin-fiche__conseil">' +
          renderConseil({ conseil_text: 'D\u00e9sol\u00e9, je n\'ai pas pu analyser votre projet. Rendez-vous sur nos cr\u00e9ations pour explorer le catalogue.' }, false) +
          '</div>' +
          '<div class="robin-fiche__cta-links"><a class="robin-fiche__cta-link" href="/nos-creations/?robin_selection=1">Voir nos cr\u00e9ations &rarr;</a></div>' +
          '</div>';
      }

      // 6. Ampoule disparaît en fondu doux
      if (bulb) bulb.classList.add('robin-modal__curtain-bulb--fading');

      // 7. Attendre que l'ampoule soit partie, puis ouvrir le rideau
      setTimeout(function() {
        if (bulb) bulb.classList.remove('robin-modal__curtain-bulb--visible');

        if (curtain) {
          curtain.classList.remove('robin-modal__curtain--closing');
          curtain.classList.add('robin-modal__curtain--opening');
        }

        // 8. Après ouverture du rideau, animer les infos (pas la photo, elle est déjà visible)
        setTimeout(function() {
          animateRecoReveal();
          if (curtain) {
            curtain.classList.remove('robin-modal__curtain--opening');
            bulb.classList.remove('robin-modal__curtain-bulb--fading');
          }
        }, 1300);
      }, 800);
    }
  }

  function renderRecoResult(data, skipAnimate) {
    var products = data.products || [];
    var totalSlides = products.length;

    var html = '<div class="robin-reco">';

    // Texte A supprimé — la photo prend tout l'espace

    // Slider showcase — 1 produit par slide
    if (totalSlides > 0) {
      html += '<div class="robin-reco__showcase" id="robin-reco-showcase">';

      // Viewport
      html += '<div class="robin-reco__viewport">';
      html += '<div class="robin-reco__track" id="robin-reco-track">';

      for (var i = 0; i < totalSlides; i++) {
        var p = products[i];
        html += '<div class="robin-reco__slide" data-index="' + i + '">';

        // Photo plein bord cliquable — visible derrière le rideau (pas animée)
        html += '<a href="' + escAttr(p.permalink) + '" class="robin-reco__photo">';
        html += '<img src="' + escAttr(p.ambiance || p.image) + '" alt="' + escAttr(p.title) + '">';
        html += '</a>';

        // Nom + prix — visible derrière le rideau (pas animé)
        html += '<div class="robin-reco__info">';
        html += '<div class="robin-reco__info-top">';
        html += '<h3 class="product-name robin-reco__name">' + escHtml(p.title) + '</h3>';
        html += '<div class="robin-reco__price-wrap">';
        html += '<span class="robin-reco__price-from">À partir de</span>';
        html += '<span class="robin-reco__price">' + (p.price || '') + '</span>';
        html += '</div>';
        html += '</div>';
        // Label + Chips conseils (essence + taille)
        var hasChips = p.variation_label || p.size_label;
        if (hasChips) {
          html += '<div class="robin-reco__chips robin-reco__reveal" data-reveal="2">';
          html += '<span class="robin-reco__chip robin-reco__chip--label">Ma recommandation :</span>';
          if (p.size_label) {
            html += '<span class="robin-reco__chip">Taille : ' + escHtml(p.size_label) + '</span>';
          }
          if (p.variation_label) {
            html += '<span class="robin-reco__chip">Essence : ' + escHtml(p.variation_label) + '</span>';
          }
          html += '</div>';
        }
        if (p.reason) {
          // Texte B — mot par mot
          var reasonWords = p.reason.split(' ');
          html += '<p class="robin-reco__reason">';
          for (var w = 0; w < reasonWords.length; w++) {
            html += '<span class="robin-reco__reveal-word" style="opacity:0;">' + escHtml(reasonWords[w]) + '</span> ';
          }
          html += '</p>';
        }
        html += '<a href="' + escAttr(p.permalink) + '" class="robin-reco__cta robin-reco__reveal" data-reveal="4">Voir ce mod\u00e8le &rarr;</a>';
        html += '</div>';

        html += '</div>';
      }

      html += '</div>';
      html += '</div>';

      // Navigation
      if (totalSlides > 1) {
        html += '<div class="robin-reco__nav robin-reco__reveal" data-reveal="5">';
        html += '<button class="robin-reco__arrow robin-reco__arrow--prev" id="robin-reco-prev" disabled>&lsaquo;</button>';
        html += '<span class="robin-reco__counter"><span id="robin-reco-current">1</span> / ' + totalSlides + '</span>';
        html += '<button class="robin-reco__arrow robin-reco__arrow--next" id="robin-reco-next">&rsaquo;</button>';
        html += '</div>';
      }

      html += '</div>';
    }

    // Boutons finaux
    html += '<div class="robin-reco__actions robin-reco__reveal" data-reveal="6">';
    html += '<a class="robin-fiche__choice robin-fiche__choice--link" href="/nos-creations/?robin_selection=1">Voir les autres mod\u00e8les adapt\u00e9s</a>';
    html += '<a class="robin-fiche__choice robin-fiche__choice--link" href="/contact/">Contacter Robin</a>';
    html += '</div>';

    html += '</div>';

    body.innerHTML = html;

    // Déclencher le formatter de noms produit
    if (typeof window.sapiFormatProductNames === 'function') {
      window.sapiFormatProductNames();
    }

    // Init slider navigation
    if (totalSlides > 1) {
      initRecoSlider(totalSlides);
    }

    // Animer seulement si pas derrière le rideau
    if (!skipAnimate) {
      animateRecoReveal();
    }
  }

  function initRecoSlider(total) {
    var current = 0;
    var track = document.getElementById('robin-reco-track');
    var prevBtn = document.getElementById('robin-reco-prev');
    var nextBtn = document.getElementById('robin-reco-next');
    var counter = document.getElementById('robin-reco-current');

    function goTo(index) {
      if (index < 0 || index >= total) return;
      current = index;
      track.style.transform = 'translateX(-' + (current * 100) + '%)';
      counter.textContent = current + 1;
      prevBtn.disabled = current === 0;
      nextBtn.disabled = current === total - 1;
    }

    prevBtn.addEventListener('click', function() { goTo(current - 1); });
    nextBtn.addEventListener('click', function() { goTo(current + 1); });

    // Swipe mobile
    var startX = 0;
    var isDragging = false;
    var viewport = track.parentElement;

    viewport.addEventListener('touchstart', function(e) {
      startX = e.touches[0].clientX;
      isDragging = true;
    }, { passive: true });

    viewport.addEventListener('touchend', function(e) {
      if (!isDragging) return;
      isDragging = false;
      var diff = startX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) {
        goTo(diff > 0 ? current + 1 : current - 1);
      }
    });
  }

  function animateHeaderSimplify() {
    var projectEl = document.getElementById('robin-modal-project');
    var backBtnEl = document.getElementById('robin-modal-back');
    var badgeEl2 = document.getElementById('robin-modal-badge');
    var closeEl = document.getElementById('robin-modal-close');
    var delay = 0;

    // 1. Chips disparaissent une par une
    if (projectEl) {
      var chips = projectEl.querySelectorAll('.robin-modal__chip');
      var resetBtn = projectEl.querySelector('.robin-modal__reset');

      for (var i = 0; i < chips.length; i++) {
        (function(chip, d) {
          setTimeout(function() { chip.classList.add('is-fading'); }, d);
        })(chips[i], delay);
        delay += 150;
      }

      // 2. Recommencer disparaît
      if (resetBtn) {
        setTimeout(function() { resetBtn.classList.add('is-fading'); }, delay);
        delay += 200;
      }

      // 3. Barre projet se réduit
      setTimeout(function() { projectEl.classList.add('is-hidden'); }, delay);
      delay += 200;
    }

    // 4. Bouton retour disparaît
    if (backBtnEl) {
      setTimeout(function() { backBtnEl.style.transition = 'opacity 0.3s'; backBtnEl.style.opacity = '0'; }, delay);
    }

    // 5. Badge "Mon projet" disparaît
    if (badgeEl2) {
      setTimeout(function() { badgeEl2.style.transition = 'opacity 0.3s'; badgeEl2.style.opacity = '0'; }, delay + 150);
    }

    // 6. Croix disparaît
    if (closeEl) {
      setTimeout(function() { closeEl.style.transition = 'opacity 0.3s'; closeEl.style.opacity = '0'; }, delay + 300);
    }
  }

  function animateRecoReveal() {
    var revealTimings = { 2: 500, 3: 1000, 4: 2000, 5: 2500, 6: 3000 };

    // Tous les éléments reveal dans le DOM (premier slide + globaux)
    var allReveals = document.querySelectorAll('.robin-reco .robin-reco__reveal');
    allReveals.forEach(function(el) {
      var step = parseInt(el.dataset.reveal, 10);
      var delay = revealTimings[step] || 2000;
      setTimeout(function() {
        el.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
      }, delay);
    });

    // Texte B mot par mot
    var firstSlide = document.querySelector('.robin-reco__slide[data-index="0"]');
    if (firstSlide) {
      var words = firstSlide.querySelectorAll('.robin-reco__reveal-word');
      var wordStart = revealTimings[3] || 1800;
      for (var i = 0; i < words.length; i++) {
        (function(el, d) {
          setTimeout(function() {
            el.style.transition = 'opacity 0.3s';
            el.style.opacity = '1';
          }, d);
        })(words[i], wordStart + i * 60);
      }
    }

    // Activer le hover après toutes les animations
    var maxDelay = revealTimings[6] || 3000;
    setTimeout(function() {
      var reco = document.querySelector('.robin-reco');
      if (reco) reco.classList.add('robin-reco--ready');
    }, maxDelay + 500);
  }

  function renderHorsParcours() {
    // TODO Phase C : fiche hors parcours
    var html = '<div class="robin-fiche__conseil">';
    html += renderConseilLoader();
    html += '</div>';
    html += renderTextInput();
    body.innerHTML = html;
  }

  /* ═══════════════════════════════════════════
     Header modale (retour, titre, avatar)
  ═══════════════════════════════════════════ */
  function updateHeader(stepId) {
    var isFirst = state.history.length === 0;

    // Bouton retour (gauche) — invisible mais occupe l'espace sur la première fiche
    backBtn.style.visibility = isFirst ? 'hidden' : 'visible';
  }

  /* ═══════════════════════════════════════════
     Interaction : clic bouton choix
  ═══════════════════════════════════════════ */
  function onChoiceClick(stepId, slug, label) {
    // Valider que c'est une étape et un slug valides du questionnaire
    var step = getStepById(stepId);
    if (!step) {
      // Pas une étape du questionnaire — traiter comme un lien ou ignorer
      return;
    }
    var validSlug = false;
    for (var i = 0; i < step.choices.length; i++) {
      if (step.choices[i].slug === slug) {
        validSlug = true;
        label = step.choices[i].label; // Utiliser le label officiel, pas celui de l'IA
        break;
      }
    }
    if (!validSlug) return;

    // Mettre à jour state
    state.answers[stepId] = slug;
    state.labels[stepId]  = label;

    // Nettoyer les réponses des steps cachés
    cleanInvisibleAnswers();
    saveState();

    // Déterminer la fiche suivante
    var next = getNextStep(stepId);
    if (!next) next = 'recommendation';

    // Empiler l'historique
    state.history.push(stepId);

    // Afficher la fiche suivante (instantané)
    showFiche(next);

    // Mettre à jour le bandeau + résumé modale
    updateBandeauChips();
    updateModalProject();
  }

  /* ═══════════════════════════════════════════
     Interaction : texte libre
  ═══════════════════════════════════════════ */
  function onFreeText(message) {
    if (!message || !message.trim()) return;

    // Empiler le step courant dans l'historique
    if (state.currentStep) {
      state.history.push(state.currentStep);
    }

    // Afficher un loader plein écran dans la modale
    state.currentStep = '_free_text_loading';
    var topEl = body.querySelector('.robin-fiche__top');
    var bottomEl = document.getElementById('robin-fiche-bottom');
    if (topEl) {
      topEl.innerHTML = '<div class="robin-fiche__conseil">' + renderConseilLoader() + '</div>';
      animateLoader();
    }
    if (bottomEl) {
      bottomEl.style.opacity = '0';
      bottomEl.style.pointerEvents = 'none';
    }

    // Appel AJAX
    var fd = new FormData();
    fd.append('action', 'sapi_robin_conseil_step');
    fd.append('nonce', NONCE);
    fd.append('guide_website', '');
    fd.append('step_id', state.history[state.history.length - 1] || 'piece');
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('opening_context', state.openingContext);
    fd.append('context_data', JSON.stringify(state.contextData));
    fd.append('user_message', message);
    fd.append('conversation', JSON.stringify(state.conversation));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', AJAX_URL, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            handleFreeTextResponse(resp.data, message);
            return;
          }
        } catch (e) {}
      }

      // Erreur — revenir à la fiche précédente
      if (state.history.length > 0) {
        var prev = state.history.pop();
        showFiche(prev);
      }
    };
    xhr.send(fd);
  }

  function handleFreeTextResponse(data, originalMessage) {
    // 1. Auto-remplir les réponses déduites
    if (data.answered_steps && typeof data.answered_steps === 'object') {
      for (var stepId in data.answered_steps) {
        if (data.answered_steps.hasOwnProperty(stepId)) {
          state.answers[stepId] = data.answered_steps[stepId];
          // Trouver le label correspondant
          var step = getStepById(stepId);
          if (step) {
            for (var i = 0; i < step.choices.length; i++) {
              if (step.choices[i].slug === data.answered_steps[stepId]) {
                state.labels[stepId] = step.choices[i].label;
                break;
              }
            }
          }
        }
      }
      cleanInvisibleAnswers();
      saveState();
      updateBandeauChips();
      updateModalProject();
    }

    // 2. Sauvegarder dans la conversation
    state.conversation.push({ role: 'user', content: originalMessage });
    state.conversation.push({ role: 'assistant', content: data.conseil_text || '' });

    // 3. Afficher la réponse IA
    var nextStep = data.next_step_id || getFirstUnansweredStep();
    state.currentStep = nextStep;

    // Rendre la fiche avec le conseil IA et les boutons suggérés
    var html = '';

    // Zone haute : conseil IA
    html += '<div class="robin-fiche__top">';
    html += '<div class="robin-fiche__conseil">';
    html += renderConseil({ conseil_text: data.conseil_text }, true);
    html += '</div>';
    // 2 liens CTA standards
    var showSurMesureFT = state.answers.taille === 'grande' || state.answers.hauteur === 'haute';
    html += '<div class="robin-fiche__cta-links" id="robin-fiche-link" style="opacity:0;">';
    html += '<a class="robin-fiche__cta-link" href="/nos-creations/?robin_selection=1">Voir les mod\u00e8les filtr\u00e9s pour votre projet &rarr;</a>';
    if (showSurMesureFT) {
      html += '<a class="robin-fiche__cta-link" href="/sur-mesure/">Imaginer un mod\u00e8le sur mesure &rarr;</a>';
    } else {
      html += '<a class="robin-fiche__cta-link" href="/contact/">Contacter Robin &rarr;</a>';
    }
    html += '</div>';
    html += '</div>';

    // Zone basse : boutons suggérés par l'IA OU la prochaine question du questionnaire
    html += '<div class="robin-fiche__bottom" id="robin-fiche-bottom" style="opacity:0;">';

    if (data.suggested_buttons && data.suggested_buttons.length > 0) {
      // Séparer les 3 types de boutons
      var convBtns = [], stepBtns = [], linkBtns = [];
      for (var i = 0; i < data.suggested_buttons.length; i++) {
        var btn = data.suggested_buttons[i];
        if (btn.url) linkBtns.push(btn);
        else if (btn.step_id && btn.slug) stepBtns.push(btn);
        else convBtns.push(btn);
      }

      // Boutons conversation (suggestions IA) — en premier
      if (convBtns.length > 0) {
        html += '<div class="robin-fiche__conv-buttons">';
        for (var ci = 0; ci < convBtns.length; ci++) {
          html += '<button class="robin-fiche__choice robin-fiche__choice--conv" data-message="' + escAttr(convBtns[ci].label) + '">';
          html += escHtml(convBtns[ci].label);
          html += '</button>';
        }
        html += '</div>';
      }

      // Boutons questionnaire — au milieu
      if (stepBtns.length > 0) {
        html += '<div class="robin-fiche__choices">';
        for (var si = 0; si < stepBtns.length; si++) {
          html += '<button class="robin-fiche__choice" data-step="' + escAttr(stepBtns[si].step_id) + '" data-slug="' + escAttr(stepBtns[si].slug) + '" data-label="' + escAttr(stepBtns[si].label || '') + '">';
          html += escHtml(stepBtns[si].label);
          html += '</button>';
        }
        html += '</div>';
      }

      // Liens CTA — en dernier
      if (linkBtns.length > 0) {
        html += '<div class="robin-fiche__cta-links">';
        for (var li = 0; li < linkBtns.length; li++) {
          html += '<a class="robin-fiche__cta-link" href="' + escAttr(linkBtns[li].url) + '">';
          html += escHtml(linkBtns[li].label) + ' &rarr;';
          html += '</a>';
        }
        html += '</div>';
      }
    } else if (nextStep !== 'hors_parcours' && nextStep !== 'recommendation') {
      // Afficher la prochaine question du questionnaire
      var nextStepData = getStepById(nextStep);
      if (nextStepData) {
        html += '<div class="robin-fiche__question">' + escHtml(getQuestionText(nextStepData)) + '</div>';
        html += '<div class="robin-fiche__choices">';
        for (var j = 0; j < nextStepData.choices.length; j++) {
          var c = nextStepData.choices[j];
          var iconHtml = icons[c.icon] ? '<span class="robin-fiche__choice-icon">' + icons[c.icon] + '</span>' : '';
          var dimHtml = c.dim ? ' <span class="robin-fiche__choice-dim">' + escHtml(c.dim) + '</span>' : '';
          html += '<button class="robin-fiche__choice" data-step="' + escAttr(nextStep) + '" data-slug="' + escAttr(c.slug) + '" data-label="' + escAttr(c.label) + '">';
          html += iconHtml + escHtml(c.label) + dimHtml;
          html += '</button>';
        }
        html += '</div>';
      }
    }

    html += renderTextInput();
    html += '</div>';

    body.innerHTML = html;
    updateHeader(nextStep);
    animateConseil();
  }

  /* ═══════════════════════════════════════════
     Navigation retour
  ═══════════════════════════════════════════ */
  function goBack() {
    if (state.history.length === 0) return;
    var prevStep = state.history.pop();
    showFiche(prevStep);
  }

  /* ═══════════════════════════════════════════
     AJAX : fetch conseil IA (lazy)
  ═══════════════════════════════════════════ */
  function fetchConseil(stepId) {
    // Annuler le précédent
    if (state.pendingXhr) {
      state.pendingXhr.abort();
      state.pendingXhr = null;
    }

    var fd = new FormData();
    fd.append('action', 'sapi_robin_conseil_step');
    fd.append('nonce', NONCE);
    fd.append('guide_website', '');
    fd.append('step_id', stepId);
    fd.append('answers', JSON.stringify(state.answers));
    fd.append('opening_context', state.openingContext);
    fd.append('context_data', JSON.stringify(state.contextData));
    fd.append('user_message', '');
    fd.append('conversation', JSON.stringify(state.conversation));

    var xhr = new XMLHttpRequest();
    xhr.open('POST', AJAX_URL, true);
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      state.pendingXhr = null;

      if (xhr.status === 200) {
        try {
          var resp = JSON.parse(xhr.responseText);
          if (resp.success && resp.data) {
            state.aiCache[stepId] = resp.data;

            // Si on est toujours sur cette fiche, mettre à jour la zone conseil
            if (state.currentStep === stepId) {
              var conseilEl = document.getElementById('robin-fiche-conseil');
              if (conseilEl) {
                conseilEl.innerHTML = renderConseil(resp.data);
                // Ajouter le lien s'il y en a un
                if (resp.data.link_url) {
                  var linkHtml = '<div class="robin-fiche__link"><a href="' + escHtml(resp.data.link_url) + '">';
                  linkHtml += escHtml(resp.data.link_label || 'Voir') + ' &rarr;</a></div>';
                  conseilEl.insertAdjacentHTML('afterend', linkHtml);
                }
              }
            }
          }
        } catch (e) {
          // Silently fail — les boutons sont déjà affichés
        }
      }
    };
    xhr.send(fd);
    state.pendingXhr = xhr;
  }

  /* ═══════════════════════════════════════════
     Bandeau : mise à jour des chips
  ═══════════════════════════════════════════ */
  function updateBandeauChips() {
    var chipsEl = document.getElementById('robin-bandeau-chips');
    if (!chipsEl) return;

    var parts = [];
    var visible = getVisibleSteps();
    for (var i = 0; i < visible.length; i++) {
      var lbl = state.labels[visible[i]];
      if (lbl) parts.push(lbl);
    }

    chipsEl.textContent = parts.length > 0
      ? parts.join(' · ')
      : 'Robin peut vous conseiller';
  }

  /* ═══════════════════════════════════════════
     Résumé projet dans la modale (chips + recommencer)
  ═══════════════════════════════════════════ */
  function updateModalProject() {
    var projectEl = document.getElementById('robin-modal-project');
    if (!projectEl) return;

    var visible = getVisibleSteps();
    var answered = [];
    for (var i = 0; i < visible.length; i++) {
      var lbl = state.labels[visible[i]];
      if (lbl) answered.push({ stepId: visible[i], label: lbl });
    }

    if (answered.length === 0) {
      projectEl.style.display = 'none';
      return;
    }

    var html = '';
    for (var j = 0; j < answered.length; j++) {
      html += '<span class="robin-modal__chip" data-step="' + escAttr(answered[j].stepId) + '" style="cursor:pointer;">' + escHtml(answered[j].label) + '</span>';
    }
    html += '<button class="robin-modal__reset" id="robin-modal-reset" type="button">Recommencer</button>';

    projectEl.innerHTML = html;
    projectEl.style.display = '';
  }

  function resetProject() {
    state.answers = {};
    state.labels  = {};
    state.history = [];
    state.aiCache = {};
    state.conversation = [];
    // Vider complètement le localStorage
    localStorage.removeItem(STORAGE_KEY);
    // Réécrire un state vide propre
    safeSave({
      answers: {},
      labels: {},
      essence: null,
      tailleIndex: null,
      pieceLabel: null,
      styleLabel: null,
      tailleLabel: null,
      recommendedIds: [],
      productsData: [],
      conseilsText: null,
      selectionText: null,
      surMesureText: null,
      showSurMesure: false,
      productLinks: []
    });
    updateBandeauChips();
    updateModalProject();
    showFiche(steps[0].id);
  }

  /* ═══════════════════════════════════════════
     Refresh page visuals (consommateurs localStorage)
  ═══════════════════════════════════════════ */
  function refreshPageVisuals() {
    if (typeof window.sapiPersonalizeRefresh === 'function') {
      window.sapiPersonalizeRefresh();
    }
  }

  /* ═══════════════════════════════════════════
     Helpers
  ═══════════════════════════════════════════ */
  function escHtml(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function escAttr(str) {
    return escHtml(str).replace(/"/g, '&quot;');
  }

  function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
  }

  /* ═══════════════════════════════════════════
     Event listeners
  ═══════════════════════════════════════════ */
  function bindEvents() {
    // Fermer la modale
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay)  overlay.addEventListener('click', closeModal);

    // Retour
    if (backBtn) backBtn.addEventListener('click', goBack);

    // Reset projet (délégation car le bouton est dynamique)
    var projectEl = document.getElementById('robin-modal-project');
    if (projectEl) {
      projectEl.addEventListener('click', function (e) {
        if (e.target.id === 'robin-modal-reset' || e.target.closest('#robin-modal-reset')) {
          resetProject();
          return;
        }
        // Chip cliquable → revenir à la question
        var chip = e.target.closest('.robin-modal__chip');
        if (chip && chip.dataset.step) {
          state.history = [];
          showFiche(chip.dataset.step);
        }
      });
    }

    // Échap
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && state.isOpen) closeModal();
    });

    // Délégation : clic sur choix dans la modale
    if (body) {
      body.addEventListener('click', function (e) {
        var btn = e.target.closest('.robin-fiche__choice');
        if (btn) {
          // Bouton questionnaire
          if (btn.dataset.step && btn.dataset.slug) {
            onChoiceClick(btn.dataset.step, btn.dataset.slug, btn.dataset.label || '');
            return;
          }
          // Bouton conversation → renvoie comme texte libre
          if (btn.dataset.message) {
            onFreeText(btn.dataset.message);
            return;
          }
        }

        // Confirmation catégorie
        if (e.target.id === 'robin-category-yes') {
          onCategoryConfirm(true);
          return;
        }
        if (e.target.id === 'robin-category-no') {
          onCategoryConfirm(false);
          return;
        }

        // Voir le conseil de Robin (recommandation)
        if (e.target.id === 'robin-reco-conseil') {
          onRecoConseilClick();
          return;
        }

        // Bouton envoyer texte libre
        if (e.target.id === 'robin-text-send' || e.target.closest('#robin-text-send')) {
          var input = document.getElementById('robin-text-input');
          if (input && input.value.trim()) {
            onFreeText(input.value.trim());
          }
          return;
        }
      });

      // Entrée dans le champ texte
      body.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.id === 'robin-text-input') {
          e.preventDefault();
          if (e.target.value.trim()) {
            onFreeText(e.target.value.trim());
          }
        }
      });
    }

    // Ouverture : bandeau + pills
    document.addEventListener('click', function (e) {
      // Bandeau
      var bandeau = e.target.closest('#robin-bandeau');
      if (bandeau) {
        openModal('bandeau');
        return;
      }

      // Pills contextuelles
      var pill = e.target.closest('.robin-pill');
      if (pill) {
        var context = pill.dataset.robinContext || 'bandeau';
        var data = {};
        try { data = JSON.parse(pill.dataset.robinData || '{}'); } catch (err) {}
        openModal(context, data);
        return;
      }
    });

    // Bandeau accessible au clavier
    var bandeau = document.getElementById('robin-bandeau');
    if (bandeau) {
      bandeau.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openModal('bandeau');
        }
      });
    }
  }

  /* ═══════════════════════════════════════════
     Confirmation catégorie
  ═══════════════════════════════════════════ */
  function onCategoryConfirm(yes) {
    if (yes) {
      // Pré-remplir la sortie selon la catégorie
      var catToSortie = {
        'suspensions': 'plafond',
        'appliques': 'mur',
        'lampadaires': 'pas-de-sortie',
        'lampesaposer': 'pas-de-sortie'
      };
      var slug = state.contextData.category_slug;
      if (slug && catToSortie[slug]) {
        state.answers.sortie = catToSortie[slug];
        state.labels.sortie  = {
          'plafond': 'Au plafond',
          'mur': 'Au mur',
          'pas-de-sortie': 'Sur prise classique 230V'
        }[catToSortie[slug]] || '';
      }
      cleanInvisibleAnswers();
      saveState();
      // Aller à la prochaine question pertinente
      var next = getFirstUnansweredStep();
      state.history.push('_category_confirm');
      showFiche(next);
    } else {
      // Pas la bonne catégorie → fiche 1
      state.history.push('_category_confirm');
      showFiche(steps[0].id);
    }
  }

  /* ═══════════════════════════════════════════
     Init
  ═══════════════════════════════════════════ */
  function init() {
    initDomRefs();
    if (!modal) return;

    // Debug : ?robin=reset pour vider le localStorage
    if (window.location.search.indexOf('robin=reset') !== -1) {
      localStorage.removeItem(STORAGE_KEY);
      state.answers = {};
      state.labels  = {};
      // Nettoyer l'URL
      var url = window.location.href.replace(/[?&]robin=reset/, '').replace(/\?$/, '');
      window.history.replaceState({}, '', url);
    }

    loadState();
    bindEvents();
    updateBandeauChips();
  }

  // Lancement
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // API publique pour ouverture depuis d'autres scripts
  window.sapiRobinOpen = openModal;

})();

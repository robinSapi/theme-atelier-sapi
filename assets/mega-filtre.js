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
  var COMMENTARY_DELAY_MS = 2500;

  // ═══ State partagé ═══
  var state = {
    answers: {}, // ex. { piece: 'salon', taille: 'moyenne' }
    labels:  {}, // ex. { piece: 'Salon / Salle à manger' }
  };
  var commentaryTimer = null;

  // ═══ DOM refs (peuplé dans init) ═══
  var els = {};

  // ═══════════════════════════════════════════════════════════
  //  Visibilité conditionnelle (mirror robin-conseiller.js)
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
      var clearBtn = chip.querySelector('.megafilter-chip-clear');
      var arrow = chip.querySelector('.megafilter-chip-arrow');

      if (hasValue) {
        // Chip répondu : on affiche uniquement la valeur (la question disparaît)
        var displayLabel = state.labels[sid] || state.answers[sid];
        if (valueEl) {
          valueEl.textContent = displayLabel;
          valueEl.hidden = false;
        }
        if (labelEl) labelEl.hidden = true;
        if (clearBtn) clearBtn.hidden = false;
        if (arrow) arrow.style.display = 'none';
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
        if (clearBtn) clearBtn.hidden = true;
        if (arrow) arrow.style.display = '';
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
    scheduleCommentary();
  }

  // ═══════════════════════════════════════════════════════════
  //  Application des filtres à la grille
  // ═══════════════════════════════════════════════════════════
  function applyFiltersToGrid() {
    // Affiche / masque le footer "Tout effacer" selon qu'au moins un chip est répondu
    if (els.footer) els.footer.hidden = !hasAnyAnswer();

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
  //  Commentaire de Robin (débouncé 2,5s)
  // ═══════════════════════════════════════════════════════════
  var TAILLE_DIM = { petite: 'compact', moyenne: 'confortable', grande: 'spacieux' };

  function buildCommentary() {
    var a = state.answers;
    var l = state.labels;
    if (!hasAnyAnswer()) return '';

    var pieceLabel = l.piece ? l.piece.toLowerCase() : '';
    var styleLabel = l.style ? l.style.toLowerCase() : '';
    var taille = TAILLE_DIM[a.taille] || '';

    var sentence;
    if (pieceLabel && taille && styleLabel) {
      sentence = 'Pour ton <strong>' + pieceLabel + '</strong> ' + taille + ' et de style ' + styleLabel +
                 ', j\'ai sélectionné une lumière qui te correspond.';
    } else if (pieceLabel && taille) {
      sentence = 'Pour ton <strong>' + pieceLabel + '</strong> ' + taille +
                 ', voici une première sélection. Précise ton style pour affiner.';
    } else if (pieceLabel && styleLabel) {
      sentence = 'Pour ton <strong>' + pieceLabel + '</strong> de style ' + styleLabel +
                 ', voici ce qui peut convenir.';
    } else if (pieceLabel) {
      sentence = 'Pour ton <strong>' + pieceLabel + '</strong>, voici ce qui peut convenir. Réponds à quelques questions pour affiner.';
    } else if (styleLabel) {
      sentence = 'Pour un intérieur ' + styleLabel + ', voici ma première sélection.';
    } else {
      sentence = 'Réponds à quelques questions et je te montre les modèles qui te correspondent.';
    }

    return sentence + ' <span class="megafilter-commentary-sig">— Robin</span>';
  }

  function scheduleCommentary() {
    if (!els.commentary) return;
    // Masquer immédiatement à chaque changement
    els.commentary.classList.remove('is-visible');
    els.commentary.hidden = true;

    if (commentaryTimer) clearTimeout(commentaryTimer);
    if (!hasAnyAnswer()) return;

    commentaryTimer = setTimeout(function () {
      var html = buildCommentary();
      if (!html) return;
      els.commentary.innerHTML = html;
      els.commentary.hidden = false;
      // Reflow puis classe pour transition
      // eslint-disable-next-line no-unused-expressions
      els.commentary.offsetHeight;
      els.commentary.classList.add('is-visible');
    }, COMMENTARY_DELAY_MS);
  }

  // ═══════════════════════════════════════════════════════════
  //  Modale "Décrire mon projet" (UI shell uniquement)
  // ═══════════════════════════════════════════════════════════
  function openModal() {
    if (!els.modal) return;
    els.modal.hidden = false;
    document.body.style.overflow = 'hidden';
    // Focus l'input d'entrée
    var input = document.getElementById('megafilter-modal-input-initial');
    if (input) setTimeout(function () { input.focus(); }, 50);
  }

  function closeModal() {
    if (!els.modal) return;
    els.modal.hidden = true;
    document.body.style.overflow = '';
    // Reset à l'état initial pour la prochaine ouverture
    resetModalState();
  }

  function resetModalState() {
    var start  = document.getElementById('megafilter-modal-start');
    var chat   = document.getElementById('megafilter-modal-chat');
    var ret    = document.getElementById('megafilter-modal-return');
    var footer = document.getElementById('megafilter-modal-footer');
    if (start)  start.hidden  = false;
    if (chat)   chat.hidden   = true;
    if (ret)    ret.hidden    = true;
    if (footer) footer.hidden = true;
  }

  // Simulations cablées sur les 3 suggestions (F1a — pas d'IA réelle)
  var SIMULATIONS = {
    'suspension-salon-table': {
      userMsg: 'Une suspension moderne pour mon salon, au-dessus de la table',
      robinMsg: 'Très bien ! Pour un salon moderne avec une suspension au-dessus de la table, je te recommande des modèles à ampoule entourée (lumière diffuse, agréable pour les repas) et un format plutôt boule ou horizontal.',
      filters: {
        piece:    { value: 'salon',    label: 'Salon / Salle à manger' },
        style:    { value: 'moderne',  label: 'Moderne, neuf, tons clairs' },
        taille:   { value: 'moyenne',  label: 'Pièce standard' },
        sortie:   { value: 'plafond',  label: 'Au plafond' },
        hauteur:  { value: 'standard', label: 'Standard' },
        table:    { value: 'oui',      label: 'Oui' },
      },
    },
    'escalier': {
      userMsg: 'Quelque chose pour éclairer mon escalier',
      robinMsg: 'Pour un escalier, je sélectionne des suspensions verticales qui occupent bien la hauteur du vide.',
      filters: {
        piece:           { value: 'escalier', label: 'Cage d\'escalier' },
        taille_escalier: { value: 'standard', label: 'Escalier standard' },
        sortie:          { value: 'plafond',  label: 'Au plafond' },
      },
    },
    'lampe-chambre': {
      userMsg: 'Une lampe d\'appoint chambre bois clair',
      robinMsg: 'Pour une chambre, je te propose des lampes à poser ou des appliques avec une ampoule entourée pour une lumière douce.',
      filters: {
        piece:  { value: 'chambre',       label: 'Chambre' },
        taille: { value: 'moyenne',       label: 'Pièce standard' },
        sortie: { value: 'pas-de-sortie', label: 'Sur prise classique 230V' },
        style:  { value: 'moderne',       label: 'Moderne, neuf, tons clairs' },
      },
    },
  };

  // Filtres simulés en attente, appliqués au clic sur "Voir la sélection"
  var pendingSim = null;

  function simulateChat(simKey) {
    var sim = SIMULATIONS[simKey];
    if (!sim) return;

    document.getElementById('megafilter-modal-start').hidden = true;
    document.getElementById('megafilter-modal-chat').hidden = false;
    document.getElementById('megafilter-modal-return').hidden = false;
    document.getElementById('megafilter-modal-footer').hidden = false;

    var userBubble  = document.getElementById('megafilter-chat-user-bubble');
    var robinBubble = document.getElementById('megafilter-chat-robin-bubble');
    var filtersBox  = document.getElementById('megafilter-chat-filters');
    var filtersList = document.getElementById('megafilter-chat-filters-list');

    if (userBubble)  userBubble.textContent = sim.userMsg;
    if (robinBubble) robinBubble.textContent = sim.robinMsg;

    // Compose la liste des filtres affichés
    if (filtersBox && filtersList) {
      var parts = [];
      Object.keys(sim.filters).forEach(function (k) {
        parts.push(getChipLabel(k) + ' = ' + sim.filters[k].label);
      });
      filtersList.textContent = ' ' + parts.join(' · ');
      filtersBox.hidden = false;
    }

    // Pré-calcule combien de modèles matchent (sans appliquer encore)
    pendingSim = sim.filters;
    var previewCount = countMatchesForSimulation(sim.filters);
    var num = document.getElementById('megafilter-modal-return-num');
    if (num) num.textContent = previewCount;
  }

  function countMatchesForSimulation(filtersObj) {
    // Simule l'application sans toucher au state global
    var backup = { answers: state.answers, labels: state.labels };
    state.answers = {};
    state.labels = {};
    Object.keys(filtersObj).forEach(function (k) {
      state.answers[k] = filtersObj[k].value;
      state.labels[k]  = filtersObj[k].label;
    });
    cleanInvisibleAnswers();

    var cards = document.querySelectorAll('.product-card-cinetique');
    var count = 0;
    cards.forEach(function (card) {
      if (cardMatches(card)) count++;
    });

    state.answers = backup.answers;
    state.labels  = backup.labels;
    return count;
  }

  function applyPendingSimAndClose() {
    if (pendingSim) {
      state.answers = {};
      state.labels = {};
      Object.keys(pendingSim).forEach(function (k) {
        state.answers[k] = pendingSim[k].value;
        state.labels[k]  = pendingSim[k].label;
      });
      cleanInvisibleAnswers();
      pendingSim = null;
      onStateChange();
    }
    closeModal();
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
        var clearBtn = e.target.closest('.megafilter-chip-clear');

        if (clearBtn) {
          e.stopPropagation();
          var clearedChip = clearBtn.closest('.megafilter-chip');
          if (clearedChip) clearAnswer(clearedChip.dataset.step);
          return;
        }

        if (option) {
          var chipFromOpt = option.closest('.megafilter-chip');
          if (!chipFromOpt) return;
          setAnswer(chipFromOpt.dataset.step, option.dataset.value, option.dataset.label || option.textContent.trim());
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

    // Modale : clic sur suggestion
    var suggestions = document.querySelectorAll('.megafilter-modal-sug');
    suggestions.forEach(function (sug) {
      sug.addEventListener('click', function () {
        simulateChat(sug.dataset.sim);
      });
    });

    // Modale : bouton "Voir la sélection" — applique les filtres simulés et ferme
    var returnBtn = document.getElementById('megafilter-modal-return-btn');
    if (returnBtn) returnBtn.addEventListener('click', applyPendingSimAndClose);
  }

  // ═══════════════════════════════════════════════════════════
  //  Init
  // ═══════════════════════════════════════════════════════════
  function init() {
    els.chipsContainer = document.getElementById('megafilter-chips');
    if (!els.chipsContainer) return; // pas sur la page concernée

    els.commentary = document.getElementById('megafilter-commentary');
    els.footer     = document.getElementById('megafilter-footer');
    els.reset      = document.getElementById('megafilter-reset');
    els.openAiBtn  = document.getElementById('megafilter-open-ai');
    els.modal      = document.getElementById('megafilter-modal');

    bindEvents();
    readQueryParams();
    renderChips();

    // Premier passage : applique les filtres + déclenche commentaire si réponses depuis l'URL
    applyFiltersToGrid();
    if (hasAnyAnswer()) scheduleCommentary();
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

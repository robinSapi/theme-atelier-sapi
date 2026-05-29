/**
 * Galerie Inspiration — JS de page
 * - Submit AJAX du formulaire newsletter (card C4)
 * - Filtres multi-sélection pièce + essence (card filtres en 1ère position)
 */

(function () {
  'use strict';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    initNewsletter();
    initFilters();
  }

  function initNewsletter() {
    var forms = document.querySelectorAll('[data-inspiration-newsletter]');
    if (!forms.length) return;
    forms.forEach(bindForm);
  }

  function bindForm(form) {
    if (form.dataset.inspirationBound === '1') return;
    form.dataset.inspirationBound = '1';

    var input  = form.querySelector('input[type="email"]');
    var button = form.querySelector('button[type="submit"]');
    var status = form.querySelector('.inspiration-card__form-status');

    if (!input || !button || !status) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      setStatus(status, '', null);
      var email = (input.value || '').trim();

      if (!email || !isValidEmail(email)) {
        setStatus(status, 'Merci de saisir une adresse email valide.', 'error');
        input.focus();
        return;
      }

      if (!window.sapiInspiration || !window.sapiInspiration.ajaxUrl || !window.sapiInspiration.nonce) {
        setStatus(status, 'Configuration manquante. Merci de réessayer plus tard.', 'error');
        return;
      }

      button.disabled = true;
      var originalLabel = button.textContent;
      button.textContent = 'Envoi…';

      var body = new URLSearchParams();
      body.append('action', 'sapi_inspiration_brevo_subscribe');
      body.append('nonce', window.sapiInspiration.nonce);
      body.append('email', email);

      fetchWithTimeout(window.sapiInspiration.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'Accept': 'application/json'
        },
        body: body.toString()
      }, 8000)
        .then(function (response) { return response.json().catch(function () { return {}; }); })
        .then(function (json) {
          if (json && json.success) {
            setStatus(status, 'Merci, vous êtes inscrit·e !', 'success');
            input.value = '';
          } else {
            var msg = (json && json.data && json.data.message) || 'erreur';
            if (msg === 'invalid_email') {
              setStatus(status, 'Adresse email invalide.', 'error');
            } else {
              setStatus(status, 'Une erreur est survenue, merci de réessayer.', 'error');
            }
          }
        })
        .catch(function () {
          setStatus(status, 'Connexion impossible, merci de réessayer.', 'error');
        })
        .finally(function () {
          button.disabled = false;
          button.textContent = originalLabel;
        });
    });
  }

  function setStatus(el, message, kind) {
    el.textContent = message || '';
    el.classList.remove('inspiration-card__form-status--success', 'inspiration-card__form-status--error');
    if (kind === 'success') el.classList.add('inspiration-card__form-status--success');
    if (kind === 'error')   el.classList.add('inspiration-card__form-status--error');
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function fetchWithTimeout(url, options, timeout) {
    var controller = new AbortController();
    var id = setTimeout(function () { controller.abort(); }, timeout);
    options.signal = controller.signal;
    return fetch(url, options).finally(function () { clearTimeout(id); });
  }

  /* ============================================================
     Filtres galerie (pièce + essence, multi-select, client-side)
     ET inter-famille / OU intra-famille.
     ============================================================ */
  function initFilters() {
    var filtersCard = document.querySelector('[data-inspiration-filters]');
    if (!filtersCard) return;

    var resetBtn = filtersCard.querySelector('[data-inspiration-reset]');
    var emptyMsg = filtersCard.querySelector('[data-inspiration-empty]');
    var tiles    = Array.prototype.slice.call(document.querySelectorAll('.inspiration-tile'));
    if (!tiles.length) return;

    var state = { room: new Set(), essence: new Set() };

    filtersCard.addEventListener('click', function (e) {
      if (resetBtn && (e.target === resetBtn || resetBtn.contains(e.target))) {
        state.room.clear();
        state.essence.clear();
        var pressed = filtersCard.querySelectorAll('.inspiration-filter-btn[aria-pressed="true"]');
        Array.prototype.forEach.call(pressed, function (b) { b.setAttribute('aria-pressed', 'false'); });
        applyFilter();
        return;
      }

      var btn = e.target.closest && e.target.closest('.inspiration-filter-btn');
      if (!btn || !filtersCard.contains(btn)) return;

      var type  = btn.dataset.filterType;
      var value = btn.dataset.filterValue;
      if (!type || !value || !state[type]) return;

      if (state[type].has(value)) {
        state[type].delete(value);
        btn.setAttribute('aria-pressed', 'false');
      } else {
        state[type].add(value);
        btn.setAttribute('aria-pressed', 'true');
      }
      applyFilter();
    });

    function applyFilter() {
      var hasFilters = state.room.size > 0 || state.essence.size > 0;

      if (resetBtn) resetBtn.hidden = !hasFilters;

      if (!hasFilters) {
        tiles.forEach(function (tile) { tile.classList.remove('is-filtered-out'); });
        if (emptyMsg) emptyMsg.hidden = true;
        return;
      }

      var visibleCount = 0;
      tiles.forEach(function (tile) {
        var tileRooms    = (tile.dataset.rooms    || '').split(' ').filter(Boolean);
        var tileEssences = (tile.dataset.essences || '').split(' ').filter(Boolean);

        var roomMatch    = state.room.size === 0    || tileRooms.some(function (r)    { return state.room.has(r); });
        var essenceMatch = state.essence.size === 0 || tileEssences.some(function (e) { return state.essence.has(e); });

        if (roomMatch && essenceMatch) {
          tile.classList.remove('is-filtered-out');
          visibleCount++;
        } else {
          tile.classList.add('is-filtered-out');
        }
      });

      if (emptyMsg) emptyMsg.hidden = visibleCount > 0;
    }
  }
})();

/**
 * Galerie Inspiration — JS de page
 * Gère uniquement le submit AJAX du formulaire newsletter (card C4).
 * Les triggers Robin Conseiller (card C3) sont gérés par robin-conseiller.js
 * via le listener générique [data-robin-open].
 */

(function () {
  'use strict';

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
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
})();

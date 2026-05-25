/* ═══════════════════════════════════════════════════════════
   Admin Conseiller V3 — JS drill-down
   Gère l'ouverture/fermeture de la modale détail de session
   via AJAX (endpoint sapi_megafilter_get_session_detail).
   ═══════════════════════════════════════════════════════════ */

(function () {
  'use strict';

  var config = window.SAPI_ADMIN_CONSEILLER || {};
  if (!config.ajaxUrl) return;

  var overlay = document.getElementById('sapi-drill');
  if (!overlay) return;

  var body = overlay.querySelector('.drill__body');
  var headerH2 = overlay.querySelector('.drill__header h2');
  var actionsLeft = overlay.querySelector('.drill__actions .left');
  var deleteBtn = overlay.querySelector('.delete-button');
  var currentSessionId = null;

  function openDrill(rowEl) {
    var sid = rowEl.getAttribute('data-session-id');
    if (!sid) return;
    currentSessionId = sid;
    if (body) body.innerHTML = '<div class="drill-loading">Chargement…</div>';
    if (headerH2) headerH2.textContent = 'Session ' + sid;
    if (actionsLeft) actionsLeft.innerHTML = '';
    overlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';

    var fd = new FormData();
    fd.append('action', 'sapi_megafilter_get_session_detail');
    fd.append('nonce', config.nonce || '');
    fd.append('session_id', sid);

    fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        if (resp && resp.success && resp.data) {
          if (headerH2 && resp.data.title) headerH2.innerHTML = resp.data.title;
          if (body && resp.data.html) body.innerHTML = resp.data.html;
          if (actionsLeft && resp.data.actions_left) actionsLeft.innerHTML = resp.data.actions_left;
        } else {
          if (body) body.innerHTML = '<div class="drill-loading">Erreur de chargement.</div>';
        }
      })
      .catch(function () {
        if (body) body.innerHTML = '<div class="drill-loading">Erreur réseau.</div>';
      });
  }

  function closeDrill() {
    overlay.classList.remove('is-open');
    document.body.style.overflow = '';
    currentSessionId = null;
  }

  // Délégation : clic sur ligne du tableau
  document.addEventListener('click', function (e) {
    var row = e.target && e.target.closest ? e.target.closest('tr[data-session-id]') : null;
    if (row && !e.target.closest('a, button')) {
      openDrill(row);
      return;
    }
    // Bouton close
    if (e.target.classList && e.target.classList.contains('drill__close')) {
      closeDrill();
      return;
    }
    // Clic en dehors de la modale
    if (e.target === overlay) {
      closeDrill();
      return;
    }
  });

  // Echap
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
      closeDrill();
    }
  });

  // Bouton supprimer
  if (deleteBtn) {
    deleteBtn.addEventListener('click', function () {
      if (!currentSessionId) return;
      if (!confirm('Supprimer cette session ?')) return;
      var fd = new FormData();
      fd.append('action', 'sapi_megafilter_delete_session');
      fd.append('nonce', config.nonce || '');
      fd.append('session_id', currentSessionId);
      fetch(config.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
          if (resp && resp.success) {
            window.location.reload();
          } else {
            alert('Erreur lors de la suppression.');
          }
        });
    });
  }
})();

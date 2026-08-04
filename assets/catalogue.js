/* =========================================================================
 * Catalogue B2B (prescripteurs) — Temps 1
 * Galerie produit (card + modale), filtres catégories (client-side),
 * modale fiche technique (croix / clic extérieur / Échap, mobile).
 * Vanilla JS, aucune dépendance. Aucun prix, aucun lien vers le site.
 * ========================================================================= */
(function () {
  'use strict';

  /* ---- Galerie réutilisable ---- */
  function initGallery(gallery) {
    if (!gallery || gallery.dataset.galleryReady === '1') return;
    gallery.dataset.galleryReady = '1';

    var slides = Array.prototype.slice.call(gallery.querySelectorAll('.cat-gallery__slide'));
    var dots = Array.prototype.slice.call(gallery.querySelectorAll('.cat-gallery__dot'));
    if (slides.length <= 1) return;

    var index = 0;
    function go(i) {
      index = (i + slides.length) % slides.length;
      slides.forEach(function (s, k) { s.classList.toggle('is-active', k === index); });
      dots.forEach(function (d, k) { d.classList.toggle('is-active', k === index); });
    }

    var prev = gallery.querySelector('.cat-gallery__nav--prev');
    var next = gallery.querySelector('.cat-gallery__nav--next');
    if (prev) prev.addEventListener('click', function (e) { e.stopPropagation(); go(index - 1); });
    if (next) next.addEventListener('click', function (e) { e.stopPropagation(); go(index + 1); });
    dots.forEach(function (d) {
      d.addEventListener('click', function (e) { e.stopPropagation(); go(parseInt(d.getAttribute('data-go'), 10) || 0); });
    });

    // Swipe tactile
    var startX = null;
    gallery.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    gallery.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 40) go(index + (dx < 0 ? 1 : -1));
      startX = null;
    }, { passive: true });
  }

  function initGalleriesIn(root) {
    Array.prototype.slice.call(root.querySelectorAll('.cat-gallery')).forEach(initGallery);
  }

  /* ---- Filtres catégories ---- */
  function initFilters() {
    var buttons = Array.prototype.slice.call(document.querySelectorAll('.cat-filter'));
    var cards = Array.prototype.slice.call(document.querySelectorAll('.cat-card'));
    var empty = document.querySelector('.cat-grid__empty');
    if (!buttons.length) return;

    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var filter = btn.getAttribute('data-filter');
        buttons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
        var visible = 0;
        cards.forEach(function (card) {
          var show = filter === 'all' || card.getAttribute('data-cat') === filter;
          card.classList.toggle('is-filtered-out', !show);
          if (show) visible++;
        });
        if (empty) empty.hidden = visible !== 0;
      });
    });
  }

  /* ---- Modale fiche technique ---- */
  function initModal() {
    var modal = document.getElementById('cat-modal');
    if (!modal) return;
    var mediaSlot = document.getElementById('cat-modal-media');
    var titleSlot = document.getElementById('cat-modal-title');
    var bodySlot = document.getElementById('cat-modal-body');
    var dialog = modal.querySelector('.cat-modal__dialog');
    var closeBtn = modal.querySelector('.cat-modal__close');
    var lastFocused = null;

    function open(card) {
      var tpl = card.querySelector('.cat-card__fiche');
      var gallery = card.querySelector('.cat-gallery');
      var titleEl = card.querySelector('.cat-card__title');

      mediaSlot.innerHTML = '';
      bodySlot.innerHTML = '';
      // Recopie le titre DÉJÀ formaté par product-name-formatter.js (spans prénom/surnom).
      titleSlot.innerHTML = titleEl ? titleEl.innerHTML : '';

      if (gallery) {
        var g = gallery.cloneNode(true);
        g.removeAttribute('data-gallery-ready');
        // réinitialise l'état actif au premier visuel
        Array.prototype.slice.call(g.querySelectorAll('.cat-gallery__slide')).forEach(function (s, k) {
          s.classList.toggle('is-active', k === 0);
        });
        Array.prototype.slice.call(g.querySelectorAll('.cat-gallery__dot')).forEach(function (d, k) {
          d.classList.toggle('is-active', k === 0);
        });
        mediaSlot.appendChild(g);
        initGallery(g);
      }

      if (tpl && tpl.content) {
        bodySlot.appendChild(tpl.content.cloneNode(true));
      }

      lastFocused = document.activeElement;
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
      if (dialog) dialog.scrollTop = 0;
      if (closeBtn) closeBtn.focus();
      document.addEventListener('keydown', onKey);
    }

    function close() {
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      mediaSlot.innerHTML = '';
      bodySlot.innerHTML = '';
      document.removeEventListener('keydown', onKey);
      if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
    }

    function onKey(e) {
      if (e.key === 'Escape') { close(); return; }
      // Piège de focus minimal
      if (e.key === 'Tab') {
        var focusables = modal.querySelectorAll('button, a[href], [tabindex]:not([tabindex="-1"])');
        if (!focusables.length) return;
        var first = focusables[0];
        var last = focusables[focusables.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
      }
    }

    // Ouverture : clic n'importe où sur la carte (bouton « Fiche technique » inclus).
    // Les flèches/dots de galerie stoppent la propagation → ils naviguent sans ouvrir.
    document.addEventListener('click', function (e) {
      if (e.target.closest('.cat-gallery__nav') || e.target.closest('.cat-gallery__dot')) return;
      var card = e.target.closest('.cat-card');
      if (card) open(card);
    });

    // Fermeture : croix + clic extérieur (overlay / éléments [data-close])
    modal.addEventListener('click', function (e) {
      if (e.target.closest('[data-close]')) close();
    });
  }

  /* ---- Export PDF (indépendant des filtres d'affichage) ---- */
  function initPdfExport() {
    var block = document.querySelector('.cat-pdf');
    if (!block) return;
    var endpoint = block.getAttribute('data-endpoint');
    var btn = block.querySelector('.cat-pdf__btn');
    var status = block.querySelector('.cat-pdf__status');
    var boxes = Array.prototype.slice.call(block.querySelectorAll('.cat-pdf__choice input[type="checkbox"]'));
    if (!btn || !endpoint) return;

    function selected() {
      return boxes.filter(function (b) { return b.checked; }).map(function (b) { return b.value; });
    }
    function sync() { btn.disabled = selected().length === 0; }
    boxes.forEach(function (b) { b.addEventListener('change', sync); });
    sync();

    function showStatus(msg, isError) {
      if (!status) return;
      status.hidden = false;
      status.textContent = msg;
      status.classList.toggle('is-error', !!isError);
    }
    function endLoading() {
      btn.classList.remove('is-loading');
      sync();
    }

    btn.addEventListener('click', function () {
      var cats = selected();
      if (!cats.length) return;
      var url = endpoint + (endpoint.indexOf('?') > -1 ? '&' : '?') + 'cats=' + encodeURIComponent(cats.join(','));

      btn.classList.add('is-loading');
      btn.disabled = true;
      showStatus('Préparation de votre catalogue PDF…', false);

      fetch(url, { credentials: 'same-origin' })
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          var cd = r.headers.get('Content-Disposition') || '';
          var m = /filename="?([^";]+)"?/.exec(cd);
          var fname = m ? m[1] : 'catalogue-atelier-sapi.pdf';
          return r.blob().then(function (blob) { return { blob: blob, fname: fname }; });
        })
        .then(function (o) {
          var objUrl = URL.createObjectURL(o.blob);
          var a = document.createElement('a');
          a.href = objUrl;
          a.download = o.fname;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          setTimeout(function () { URL.revokeObjectURL(objUrl); }, 5000);
          endLoading();
          if (status) { status.hidden = true; status.textContent = ''; status.classList.remove('is-error'); }
        })
        .catch(function () {
          endLoading();
          showStatus('Le téléchargement a échoué. Merci de réessayer.', true);
        });
    });
  }

  function init() {
    initGalleriesIn(document);
    initFilters();
    initModal();
    initPdfExport();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * Centre d'assemblage — interactions de la page de montage et de l'accueil.
 * Sans ce script, les 3 étapes restent dépliées (le repli n'est posé qu'ici,
 * par la classe .js-phases) ; mode énigme et 1re version seulement.
 */
(function () {
  'use strict';

  /* ── Accueil : recherche ── */
  var q = document.getElementById('sapi-centre-q');
  if (q) {
    var items = Array.prototype.slice.call(document.querySelectorAll('.sapi-centre__grid > li'));
    var empty = document.querySelector('.sapi-centre__empty');
    var norm = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };
    q.addEventListener('input', function () {
      var v = norm(q.value.trim()), shown = 0;
      items.forEach(function (li) {
        var ok = !v || (li.getAttribute('data-search') || '').indexOf(v) !== -1;
        li.hidden = !ok; if (ok) shown++;
      });
      if (empty) empty.hidden = shown > 0;
    });
  }

  /* ── Fiche produit : la vignette ouvre la vidéo dans une fenêtre ── */
  function openVideoModal(id, sec) {
    var modal = document.createElement('div');
    modal.className = 'sapi-video-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Vidéo de montage');
    modal.innerHTML = '<div class="sapi-video-modal__box"><button type="button" class="sapi-video-modal__close" aria-label="Fermer">×</button>'
      + '<div class="sapi-video-modal__frame"><iframe src="https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0' + (sec ? '&start=' + sec : '')
      + '" title="Vidéo de montage" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe></div></div>';
    var opener = document.activeElement;
    var prevOverflow = document.documentElement.style.overflow;
    var openedAt = Date.now(), downOnBackdrop = false;
    function close() {
      document.removeEventListener('keydown', onKey);
      modal.remove();
      document.documentElement.style.overflow = prevOverflow;
      if (opener && opener.focus) opener.focus();
    }
    // Échap ne marche que tant que le focus n'est pas dans la vidéo : la croix reste visible.
    function onKey(e) {
      if (e.key === 'Escape') close();
      if (e.key === 'Tab') { e.preventDefault(); modal.querySelector('.sapi-video-modal__close').focus(); }
    }
    modal.addEventListener('mousedown', function (e) { downOnBackdrop = e.target === modal; });
    modal.addEventListener('click', function (e) {
      if (e.target.classList.contains('sapi-video-modal__close')) return close();
      if (e.target === modal && downOnBackdrop && Date.now() - openedAt > 350) close();
    });
    document.addEventListener('keydown', onKey);
    document.body.appendChild(modal);
    document.documentElement.style.overflow = 'hidden';
    modal.querySelector('.sapi-video-modal__close').focus();
  }
  document.querySelectorAll('.sapi-install-video[data-yt]').forEach(function (b) {
    b.addEventListener('click', function () { openVideoModal(b.getAttribute('data-yt'), 0); });
  });

  var root = document.querySelector('.sapi-notice');
  if (!root) return;

  /* ── Étapes : une ouverte, « Valider » coche et ouvre la suivante ── */
  var phases = Array.prototype.slice.call(root.querySelectorAll('.sapi-phase'));
  var done = root.querySelector('.sapi-notice__done');
  root.classList.add('js-phases');

  function setOpen(phase, open) {
    if (!open) phase.querySelectorAll('.sapi-video').forEach(resetVideo);
    phase.classList.toggle('is-open', open);
    phase.querySelector('.sapi-phase__head').setAttribute('aria-expanded', open ? 'true' : 'false');
    refreshAction(phase);
  }
  function refreshAction(phase) {
    var a = phase.querySelector('.sapi-phase__action');
    if (!a) return;
    a.textContent = phase.classList.contains('is-open') ? '' : (phase.classList.contains('is-done') ? 'Revoir' : 'Ouvrir');
  }
  phases.forEach(function (p, i) {
    refreshAction(p);
    p.querySelector('.sapi-phase__head').addEventListener('click', function () {
      setOpen(p, !p.classList.contains('is-open'));
    });
    var v = p.querySelector('[data-validate]');
    if (v) v.addEventListener('click', function () {
      p.classList.add('is-done');
      p.querySelector('.sapi-phase__badge').textContent = '✓';
      setOpen(p, false);
      var next = phases[i + 1];
      if (next) {
        setOpen(next, true);
        next.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      if (done && phases.every(function (x) { return x.classList.contains('is-done'); })) {
        done.hidden = false;
        done.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  });

  /* ── Version : change l'électricité, et l'énigme + la vidéo si la version a les siennes ── */
  var pills = Array.prototype.slice.call(root.querySelectorAll('.sapi-notice__pill'));
  var lieu = root.querySelector('[data-version-lieu]');
  function selectVersion(idx) {
    pills.forEach(function (x) {
      var on = x.getAttribute('data-version') === idx;
      x.classList.toggle('is-active', on);
      x.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
    root.querySelectorAll('[data-version-panel]').forEach(function (panel) {
      var on = panel.getAttribute('data-version-panel') === idx;
      panel.hidden = !on;
      if (on && lieu && panel.hasAttribute('data-lieu')) lieu.textContent = panel.getAttribute('data-lieu');
    });
    // Une vidéo lancée dans une version masquée continuerait de jouer : on remet l'affiche.
    root.querySelectorAll('.sapi-video').forEach(resetVideo);
    root.querySelectorAll('.sapi-enigme__tile[data-hint]').forEach(function (t) { t.setAttribute('aria-expanded', 'false'); t.classList.remove('is-active'); });
    root.querySelectorAll('[data-hint-text]').forEach(function (h) { h.hidden = true; });
  }
  pills.forEach(function (pill) {
    pill.addEventListener('click', function () { selectVersion(pill.getAttribute('data-version')); });
  });

  /* ── Onglets énigme / guidé ── */
  var tabs = Array.prototype.slice.call(root.querySelectorAll('.sapi-tabs [role="tab"]'));
  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      root.querySelectorAll('.sapi-video').forEach(resetVideo);
      tabs.forEach(function (t) {
        var on = t === tab;
        t.classList.toggle('is-active', on);
        t.setAttribute('aria-selected', on ? 'true' : 'false');
        var panel = document.getElementById(t.getAttribute('aria-controls'));
        if (panel) panel.hidden = !on;
      });
    });
  });

  /* ── Indices de l'énigme ── */
  var tiles = Array.prototype.slice.call(root.querySelectorAll('.sapi-enigme__tile[data-hint]'));
  tiles.forEach(function (tile) {
    tile.addEventListener('click', function () {
      var k = tile.getAttribute('data-hint');
      var wasOpen = tile.getAttribute('aria-expanded') === 'true';
      tiles.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); t.classList.remove('is-active'); });
      root.querySelectorAll('[data-hint-text]').forEach(function (h) { h.hidden = true; });
      if (!wasOpen) {
        tile.setAttribute('aria-expanded', 'true');
        tile.classList.add('is-active');
        var h = root.querySelector('[data-hint-text="' + k + '"]');
        if (h) h.hidden = false;
      }
    });
  });

  /* ── Vidéo YouTube chargée au clic (pas d'iframe ni de cookie avant) ── */
  root.querySelectorAll('.sapi-video').forEach(function (v) { v._poster = v.innerHTML; });
  function resetVideo(v) {
    if (v._poster && v.querySelector('iframe')) v.innerHTML = v._poster;
  }
  function playAt(video, sec) {
    if (!video) return;
    var id = video.getAttribute('data-yt');
    var src = 'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0' + (sec ? '&start=' + sec : '');
    video.innerHTML = '<iframe src="' + src + '" title="Vidéo de montage" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>';
    video.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  // Délégation : l'affiche est recréée quand on change de version.
  root.addEventListener('click', function (e) {
    var poster = e.target.closest('.sapi-video__poster');
    if (poster) return playAt(poster.closest('.sapi-video'), 0);
    var seek = e.target.closest('[data-seek]');
    if (seek) {
      var guide = seek.closest('.sapi-guide');
      playAt(guide && guide.querySelector('.sapi-video'), parseInt(seek.getAttribute('data-seek'), 10) || 0);
    }
  });

  /* ── Lien depuis une fiche produit : ?version=N ouvre la bonne version ── */
  var m = /[?&]version=(\d+)/.exec(window.location.search);
  if (m && root.querySelector('.sapi-notice__pill[data-version="' + m[1] + '"]')) selectVersion(m[1]);
})();

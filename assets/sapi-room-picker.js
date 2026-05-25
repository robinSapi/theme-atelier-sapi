/**
 * Sapi Room Picker — Bento card "Pour quelle pièce ?" sur la homepage.
 *
 * Question pièce (6 cases <a>) + champ texte libre.
 * Submit du champ texte libre → redirige vers /mes-creations/?freetext=…
 * La modale conseiller (présente sur shop/product) intercepte ce param au
 * load et s'ouvre en mode chat S2 avec le texte initial.
 */
(function () {
  'use strict';

  var CFG = window.SAPI_ROOM_PICKER || {};
  var CREATIONS_URL = CFG.creationsUrl || '/mes-creations/';

  function init() {
    var picker = document.querySelector('[data-room-picker]');
    if (!picker) return;

    var forms = picker.querySelectorAll('[data-room-picker-freetext]');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var input = form.querySelector('input[name="freetext"]');
        var text = (input && input.value || '').trim();
        if (!text) return;
        var url = CREATIONS_URL + '?freetext=' + encodeURIComponent(text);
        window.location.href = url;
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * Sapi Room Picker — le bloc « Pour quelle pièce ? ».
 *
 * Il existe en TROIS exemplaires, sur trois pages :
 *   - l'accueil (`front-page.php`),
 *   - Conseils éclairés (`page-conseils-eclaires.php`),
 *   - le hero de /mes-creations/ tant qu'aucune pièce n'est choisie
 *     (`woocommerce/archive-product.php`, état A).
 * Les deux premiers portent `[data-room-picker]`, le troisième
 * `[data-mes-creations-picker]` — ce fichier accepte les deux.
 *
 * Deux rôles :
 *   1. le champ texte libre redirige vers /mes-creations/?freetext=… — sur les
 *      deux premiers seulement, le troisième étant un formulaire GET natif ;
 *   2. si un projet est déjà en mémoire, on ajoute une ligne de rappel
 *      au-dessus des cases, sur les trois (variante A, décision Robin du 28/08).
 */
(function () {
  'use strict';

  var CFG = window.SAPI_ROOM_PICKER || {};
  var CREATIONS_URL = CFG.creationsUrl || '/mes-creations/';

  function pickers() {
    return document.querySelectorAll('[data-room-picker], [data-mes-creations-picker]');
  }

  /* ─────────────────────────────────────────────
     Le champ texte libre
     ───────────────────────────────────────────── */
  function brancherTexteLibre(picker) {
    var forms = picker.querySelectorAll('[data-room-picker-freetext]');
    forms.forEach(function (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var input = form.querySelector('input[name="freetext"]');
        var text = (input && input.value || '').trim();
        if (!text) return;
        /* On transporte l'origine, comme les cartes de pièce juste à côté :
           sans elle, ces visiteurs se confondent avec une arrivée directe.
           ⚠️ Ne concerne QUE l'accueil et Conseils éclairés. Le champ libre du
           troisième sélecteur, sur /mes-creations/, est un formulaire GET natif
           sans JavaScript : il ne passe pas par ici, ne porte pas `from`, et sa
           provenance est déduite du chemin côté modale. */
        var origine = picker.getAttribute('data-room-picker-from') || '';
        var url = CREATIONS_URL + '?freetext=' + encodeURIComponent(text)
                + (origine ? '&from=' + encodeURIComponent(origine) : '');
        window.location.href = url;
      });
    });
  }

  /* ─────────────────────────────────────────────
     Le rappel « tu as un projet en cours »
     ───────────────────────────────────────────── */

  /* L'adresse qui rouvre le projet ENTIER, pas seulement sa pièce.
     ⚠️ On passe par `ecrireProjetDansUrl` et non par une concaténation à la
     main : c'est elle qui connaît l'ordre canonique des clés et qui efface
     d'abord les anciennes. Une adresse écrite à la main ici divergerait au
     premier ajout de question, et le visiteur reprendrait un projet amputé. */
  function adresseDeReprise() {
    var p = window.sapiProject;
    var projet = p.get();
    var url;
    try {
      url = new URL(CREATIONS_URL, window.location.origin);
    } catch (e) {
      return null;
    }
    if (typeof p.ecrireProjetDansUrl === 'function') {
      p.ecrireProjetDansUrl(url, projet.answers || {});
    } else {
      /* Repli inatteignable en pratique (les deux fichiers sont chargés
         ensemble), mais une adresse minimale vaut mieux qu'une fausse. */
      url.searchParams.set('piece', projet.answers.piece);
    }
    /* ⚠️ PAS DE `from` ICI, ET C'EST DÉLIBÉRÉ. Les cartes de pièce en posent un
       pour qu'un clic ouvre une session : c'est une SÉLECTION de pièce, elle
       doit compter. Reprendre un projet n'en est pas une — la personne avait
       déjà été comptée quand elle a choisi. Poser `from` ici produirait une
       ligne strictement identique à un premier clic : le même visiteur
       compterait deux fois dans « Top pièces demandées », et son salon
       ressortirait gonflé par ses propres retours.
       Conséquence assumée : on ne mesure pas les reprises. Si Robin veut ce
       chiffre un jour, il faudra une provenance distincte, pas celle-ci. */
    return url.pathname + url.search;
  }

  function poserRappel(picker) {
    var p = window.sapiProject;
    if (!p || typeof p.get !== 'function') return;

    var piece = p.getAnswer ? p.getAnswer('piece') : '';
    if (!piece) return; // pas de projet, ou un projet sans pièce : rien à rappeler

    /* Garde d'idempotence, pour CE bloc seulement. La production sert les
       scripts via un cache qui peut les combiner : sans elle, une double
       exécution poserait deux rappels. (`brancherTexteLibre`, lui, n'est pas
       protégé — un double écouteur `submit` y serait sans conséquence, le
       premier redirigeant déjà la page.) */
    if (picker.querySelector('[data-picker-reprise]')) return;

    var cases = picker.querySelector('.room-picker-cards');
    if (!cases) return; // structure inattendue : on n'insère rien plutôt que n'importe où

    /* ⚠️ « ton salon » / « ta cuisine » vient de PHP (`SAPI_PROJECT.possessifs`),
       source unique partagée avec `sapi_piece_possessive()`. Ne pas écrire la
       table ici : le genre se trompe vite, et « ton chambre » sur l'accueil
       pendant que le hero dit « ta chambre » est le genre d'écart qu'on ne
       remarque qu'une fois en production. */
    var possessifs = (window.SAPI_PROJECT || {}).possessifs || {};
    var possessif = possessifs[piece];
    if (!possessif) return; // pièce inconnue de la table : on se tait plutôt que de dire « ta pièce »

    var href = adresseDeReprise(picker);
    if (!href) return;

    var bloc = document.createElement('div');
    bloc.className = 'room-picker-reprise';
    bloc.setAttribute('data-picker-reprise', '');

    var texte = document.createElement('span');
    texte.appendChild(document.createTextNode('Tu cherchais pour '));
    var fort = document.createElement('strong');
    fort.textContent = possessif;
    texte.appendChild(fort);

    /* Le point médian de la maquette validée. Un vrai élément et non un `gap` :
       en repli mobile, les deux moitiés passent à la ligne et il faut quelque
       chose entre elles. `aria-hidden` parce qu'un lecteur d'écran n'a pas à
       épeler une puce. */
    var sep = document.createElement('span');
    sep.className = 'room-picker-reprise__sep';
    sep.setAttribute('aria-hidden', 'true');
    sep.textContent = '·';

    var lien = document.createElement('a');
    lien.className = 'room-picker-reprise__lien';
    lien.href = href;
    lien.textContent = 'Reprendre ma sélection';

    bloc.appendChild(texte);
    bloc.appendChild(sep);
    bloc.appendChild(lien);
    cases.parentNode.insertBefore(bloc, cases);
  }

  /* ─────────────────────────────────────────────
     Accueil avec un projet entamé (24/11)
     Le script en ligne de front-page.php a déjà posé `.has-projet` avant
     l'affichage. Ici on remplit la proposition ; au moindre doute (pas de
     pièce connue, réseau, zéro modèle) on RETIRE la classe : le visiteur
     retrouve le sélecteur, jamais un bloc vide.
     ───────────────────────────────────────────── */
  function proposerProjet() {
    var section = document.querySelector('[data-accueil-section]');
    if (!section) return;
    var bloc = section.querySelector('[data-accueil-projet]');
    var p = window.sapiProject;
    function afficherSelecteur() { section.classList.remove('has-projet'); }

    var autre = bloc && bloc.querySelector('[data-accueil-autre]');
    if (autre) autre.addEventListener('click', function () {
      afficherSelecteur();
      // On vient de refuser ce projet : le rappel « Reprendre » serait redondant.
      var rappel = section.querySelector('[data-picker-reprise]');
      if (rappel) rappel.remove();
      var titre = section.querySelector('.room-picker-title');
      if (titre) { titre.setAttribute('tabindex', '-1'); titre.focus(); }
    });

    if (!bloc || !p || typeof p.get !== 'function') { afficherSelecteur(); return; }
    var projet = p.get();
    var answers = projet.answers || {};
    var possessifs = (window.SAPI_PROJECT || {}).possessifs || {};
    if (!answers.piece || !possessifs[answers.piece] || !CFG.ajaxUrl) { afficherSelecteur(); return; }
    section.classList.add('has-projet');

    // « Compléter mon projet » : la page de sélection, modale ouverte sur la prochaine question.
    var completer = bloc.querySelector('[data-accueil-completer]');
    var href = adresseDeReprise();
    if (completer && href) completer.href = href + (href.indexOf('?') === -1 ? '?' : '&') + 'completer=1';

    var fd = new FormData();
    fd.append('action', 'sapi_accueil_selection');
    fd.append('answers', JSON.stringify(answers));
    fetch(CFG.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (resp) {
        var d = resp && resp.success && resp.data;
        if (!d || !d.count || !d.html) { afficherSelecteur(); return; }
        var titre = bloc.querySelector('[data-accueil-titre]');
        if (titre && d.title) titre.textContent = d.title;
        /* Le conseil rédigé pour CE projet s'il existe (court), sinon la
           phrase de Robin pour la pièce. */
        var conseil = (typeof projet.advice_text === 'string' && projet.advice_text.length <= 400) ? projet.advice_text : '';
        var phrase = bloc.querySelector('[data-accueil-phrase]');
        if (phrase) phrase.textContent = conseil || d.phrase || '';
        var cartes = bloc.querySelector('[data-accueil-cartes]');
        if (cartes) cartes.innerHTML = d.html; // rendu serveur, mêmes cards que /mes-creations/
      })
      .catch(afficherSelecteur);
  }

  function init() {
    proposerProjet();
    /* Retour arrière servi par le cache du navigateur : le projet a pu changer
       sur /mes-creations/ entre-temps. */
    var lire = function () { try { return localStorage.getItem('sapiProject') || ''; } catch (err) { return ''; } };
    var auChargement = lire();
    window.addEventListener('pageshow', function (e) {
      if (e.persisted && document.querySelector('[data-accueil-section]') && lire() !== auChargement) window.location.reload();
    });
    var liste = pickers();
    if (!liste.length) return; // pas de sélecteur sur cette page
    liste.forEach(function (picker) {
      brancherTexteLibre(picker);
      poserRappel(picker);
    });
  }

  /* ⚠️ ON N'EXÉCUTE PAS TOUT DE SUITE. Ce fichier lit le PROJET au démarrage,
     il doit donc partir APRÈS `sapi-project.js` — dont l'ingestion de l'adresse
     a lieu à `DOMContentLoaded`.
     Le test naïf `readyState === 'loading'` ne le garantit pas : **en
     production, Autoptimize ajoute `defer` à tous les scripts**, et dans un
     script différé `readyState` vaut déjà « interactive ». On tomberait dans le
     `else` et on lirait un projet pas encore à jour.
     ⚠️ Le site de test n'a PAS Autoptimize : cette classe de bug ne peut pas
     être montrée en recette. Explication complète dans sapi-project.js. */
  if (document.readyState === 'complete') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
})();

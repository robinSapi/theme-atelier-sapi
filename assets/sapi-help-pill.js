/**
 * Sapi Help Pill — la pill de Robin sur la fiche produit.
 *
 * DEUX ÉTATS, et un seul discriminant : le site a-t-il choisi à la place du
 * visiteur ?
 *   • normal   — « Je t'aide à choisir la bonne version » (rendu par le PHP)
 *   • annoncé  — « J'ai choisi pour ton projet »
 *
 * ⚠️ POURQUOI CE FICHIER OBSERVE AU LIEU DE CALCULER — à lire avant de le
 * « simplifier ».
 *
 * `sapi-product-preselect.js` choisit la taille et l'essence à partir du projet
 * mémorisé. Ce fichier-ci pourrait refaire le même calcul pour savoir s'il doit
 * parler. Il ne le fait pas, pour deux raisons :
 *
 *   1. La table `style → essence` existe DÉJÀ en trois exemplaires dans le
 *      dépôt (preselect, photo-swap, modal-conseiller) et `taille → index` en
 *      deux. Une divergence est déjà en production. Une quatrième copie serait
 *      une divergence de plus, garantie à terme.
 *   2. Recalculer répondrait « une présélection AURAIT DÛ avoir lieu ». On veut
 *      savoir si elle a EU LIEU. Ce n'est pas la même question — un produit à
 *      taille unique, un attribut déjà bon, un menu absent : autant de cas où
 *      le calcul dit oui et où rien n'a bougé à l'écran.
 *
 * On observe donc l'EFFET : chaque menu d'attribut a-t-il reçu un changement
 * émis par du code ? Zéro table, aucun nom d'attribut en dur, et la règle de
 * Robin — « les deux attributs, pas l'un ou l'autre » — se généralise d'elle-même
 * à un produit qui en aurait trois.
 *
 * ⚠️ LE PIÈGE PRINCIPAL : distinguer le visiteur du script.
 * `preselect` écrit les valeurs avec `dispatchEvent(new Event('change'))`, et
 * pour l'essence il va jusqu'à appeler `swatch.click()`. Au niveau du TYPE
 * d'événement, présélection et geste humain sont indiscernables : un écouteur
 * naïf sur `change` éteindrait la pill dans la milliseconde où il l'allume.
 * Le discriminant est `event.isTrusted`, que seul un vrai geste met à `true`.
 *
 * ⚠️ ET SON COROLLAIRE, MOINS ÉVIDENT : pour l'essence il faut écouter le CLIC
 * sur la pastille, jamais le `change` qui en découle. Un clic humain sur une
 * pastille produit un `change` NON trusted — c'est `cinetique.js` qui le
 * fabrique. Écouter ce `change` classerait tout clic humain comme « du code ».
 *
 * Décisions Robin du 26/08 :
 *   • un seul attribut posé sur deux → la pill se TAIT (le cas existe vraiment :
 *     « Je ne sais pas » à la taille et « Pas de préférence » au style sont deux
 *     réponses légitimes du questionnaire) ;
 *   • retour au texte normal au premier geste, et SANS RETOUR EN ARRIÈRE, même
 *     si le visiteur remet le choix d'origine. Il a pris la main, il conduit ;
 *   • aucune explication du changement de texte : commenter le geste de
 *     quelqu'un qui vient de le faire, c'est un message sur un message.
 */
(function () {
  'use strict';

  /* ⚠️ CE FICHIER DOIT ÊTRE CHARGÉ AVANT `sapi-product-preselect.js`.
     Les deux s'abonnent à `DOMContentLoaded` et les abonnés partent dans
     l'ordre d'enregistrement, c'est-à-dire l'ordre d'enqueue (functions.php :
     help-pill d'abord, preselect ensuite). C'est ce qui garantit que nos
     écouteurs sont posés avant la première présélection.
     J'avais d'abord écrit ici qu'on ne dépendait PAS de cet ordre. C'est faux,
     et c'est le genre d'affirmation rassurante qui pourrit une relecture : le
     jour où quelqu'un intervertit les deux enqueue ou ajoute un `defer`, la
     pill se tairait sans que rien ne plante. */
  var TEXTE_ANNONCE = 'J\'ai choisi pour ton projet';

  /* Délai avant d'afficher le texte après un retour de modale. La modale ferme
     son overlay puis fait défiler jusqu'aux variations : basculer pendant la
     fermeture jouerait le changement derrière un voile, et c'est justement le
     meilleur instant du parcours pour qu'il soit vu.
     ⚠️ Doit rester AU-DESSUS des 400 ms que `preselect` attend avant de poser
     la taille — sinon ce rendu-ci arrive alors que seule l'essence est posée,
     sort sur la garde d'égalité, et ne gouverne plus rien : la bascule visible
     serait déclenchée par le `change` de la taille, hors de tout contrôle.
     C'était le cas avec 350 ms. */
  var DELAI_RETOUR_MODALE = 600;

  function init() {
    var pill = document.querySelector('[data-help-pill]');
    if (!pill) return;

    pill.addEventListener('click', function () {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', {
        detail: { state: 'product' }
      }));
    });

    var textEl = pill.querySelector('[data-help-pill-text]');
    var form = document.querySelector('form.variations_form');
    if (!textEl || !form) return;

    /* Le texte normal est LU dans le DOM, jamais réécrit ici. Le PHP est la
       source de vérité (single-product.php) : le dépôt a déjà payé une
       divergence PHP/JS sur ce texte précis. */
    var TEXTE_NORMAL = textEl.textContent;

    var mainPris = false; // verrou à sens unique : le visiteur a pris la main
    var posesParLeCode = {};

    /* TOUS les menus d'attribut comptent, sans exception.
       ⚠️ J'avais écarté du compte les menus à option unique, en croyant éviter
       d'annoncer un choix qui n'en est pas un. La relecture a montré que ce
       garde faisait l'INVERSE : sur un luminaire à taille unique dont le projet
       ne dit rien de la taille, il retirait ce menu du dénominateur, l'essence
       seule suffisait, et la pill annonçait alors que le menu taille affichait
       encore « Choisir une option ». C'est-à-dire exactement la règle de Robin
       — un seul attribut sur deux, la pill se tait — qui sautait.
       Sans garde, les deux cas sont justes. Ne pas le réintroduire. */
    function menusAChoix() {
      return [].slice.call(form.querySelectorAll('select[name^="attribute_"]'));
    }

    function projetExiste() {
      try {
        return !!(window.sapiProject && window.sapiProject.hasProject && window.sapiProject.hasProject());
      } catch (e) { return false; }
    }

    function toutEstPose() {
      var menus = menusAChoix();
      if (!menus.length) return false;
      return menus.every(function (s) { return posesParLeCode[s.name]; });
    }

    var texteActuel = TEXTE_NORMAL;
    function rendre() {
      var voulu = (!mainPris && projetExiste() && toutEstPose()) ? TEXTE_ANNONCE : TEXTE_NORMAL;
      if (voulu === texteActuel) return;
      texteActuel = voulu;
      /* Fondu court sur le seul texte : le changement se produit juste au-dessus
         de l'endroit où le doigt vient de cliquer, une bascule sèche y attirerait
         l'œil au mauvais moment. */
      textEl.style.transition = 'opacity .18s ease';
      textEl.style.opacity = '0';
      setTimeout(function () {
        textEl.textContent = voulu;
        textEl.style.opacity = '1';
      }, 180);
    }

    // ── Ce que le CODE pose : `change` non trusted sur un menu d'attribut.
    form.addEventListener('change', function (e) {
      var sel = e.target;
      if (!sel || !sel.name || sel.name.indexOf('attribute_') !== 0) return;
      if (e.isTrusted) { mainPris = true; rendre(); return; }
      posesParLeCode[sel.name] = true;
      rendre();
    }, true);

    /* ── Ce que le VISITEUR fait. Trois gestes, tous en capture pour passer
       avant les gestionnaires du thème. Le clic sur pastille est ici, et NON
       dans l'écouteur `change` ci-dessus : voir l'avertissement d'en-tête. */
    document.addEventListener('click', function (e) {
      if (!e.isTrusted || mainPris) return;
      var t = e.target;
      if (!t || !t.closest) return;
      if (t.closest('.material-option, .swatch-item, .reset_variations')) {
        mainPris = true;
        rendre();
      }
    }, true);

    /* Retour de modale : la présélection part sur cet événement. On laisse
       l'overlay finir de se fermer avant de montrer le nouveau texte. */
    document.addEventListener('sapi:apply-product-selection', function () {
      setTimeout(rendre, DELAI_RETOUR_MODALE);
    });

    /* Le projet peut disparaître pendant la visite (« Recommencer mon projet »
       vide le localStorage). La pill ne peut plus parler d'un projet qui
       n'existe plus. */
    try {
      if (window.sapiProject && window.sapiProject.subscribe) {
        window.sapiProject.subscribe(rendre);
      }
    } catch (e) { /* swallow */ }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

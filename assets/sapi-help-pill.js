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

    /* Garde d'idempotence. Si ce fichier venait à s'exécuter deux fois — cache
       qui duplique, scripts combinés, enqueue en double — la seconde instance
       poserait un second écouteur de clic (la modale s'ouvrirait deux fois) et,
       pire, lirait le texte « normal » APRÈS que la première a déjà annoncé.
       Son texte de repli deviendrait « J'ai choisi pour ton projet », et elle
       le réécrirait à chaque geste du visiteur : la pill ne reviendrait plus
       jamais en arrière. */
    if (pill.getAttribute('data-help-pill-ready') === '1') return;
    pill.setAttribute('data-help-pill-ready', '1');

    pill.addEventListener('click', function () {
      document.dispatchEvent(new CustomEvent('sapi:open-modal', {
        detail: { state: 'product' }
      }));
    });

    var textEl = pill.querySelector('[data-help-pill-text]');
    var form = document.querySelector('form.variations_form');
    if (!textEl || !form) return;

    /* Le texte normal vient de l'ATTRIBUT posé par le PHP, jamais du contenu
       affiché. Le PHP reste la source unique (single-product.php) — le dépôt a
       déjà payé une divergence PHP/JS sur ce texte précis — mais la lecture par
       attribut est en plus increvable : elle donne le même résultat qu'on la
       fasse avant ou après une annonce. */
    var TEXTE_NORMAL = textEl.getAttribute('data-help-pill-default') || textEl.textContent;

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

    /* ⚠️ DEUX NOMMAGES DIFFÉRENTS, ET C'EST TOUT LE PIÈGE.
       Le visiteur ne manipule PAS le menu que le code manipule. Un plugin de
       pastilles (WVS) remplace les menus WooCommerce par des boutons radio
       nommés `wvs_radio_attribute_pa_taille__7183` — `attribute_` y est au
       MILIEU, pas au début. Le plugin recopie ensuite la valeur dans le vrai
       menu caché, `attribute_pa_taille`, que la présélection vise elle aussi.

       D'où le défaut constaté par Robin : l'allumage marchait (le code écrit
       dans le vrai menu, nom préfixé) mais l'extinction ne partait jamais (le
       geste humain porte le nom du plugin, et mon test l'écartait).

       Les deux branches n'ont donc PAS le même test, et c'est volontaire :
         • geste humain  → `attribute_` n'importe où dans le nom, pour attraper
           le plugin d'aujourd'hui et celui qui le remplacera ;
         • pose par le code → `attribute_` en PRÉFIXE strict, parce que le
           compteur est indexé sur les vrais menus, ceux que `menusAChoix()`
           énumère. Élargir ce test-là ferait compter deux fois le même
           attribut sous deux noms. */
    form.addEventListener('change', function (e) {
      var sel = e.target;
      if (!sel || !sel.name || sel.name.indexOf('attribute_') === -1) return;
      if (e.isTrusted) { mainPris = true; rendre(); return; }
      if (sel.name.indexOf('attribute_') === 0) {
        posesParLeCode[sel.name] = true;
        rendre();
      }
    }, true);

    /* ── Ce que le VISITEUR fait. Trois gestes, tous en capture pour passer
       avant les gestionnaires du thème. Le clic sur pastille est ici, et NON
       dans l'écouteur `change` ci-dessus : voir l'avertissement d'en-tête. */
    document.addEventListener('click', function (e) {
      if (!e.isTrusted || mainPris) return;
      var t = e.target;
      if (!t || !t.closest) return;
      /* La pill est rendue À L'INTÉRIEUR du formulaire de variation (hook
         `woocommerce_before_single_variation`). Cliquer dessus pour ouvrir la
         modale ne doit évidemment pas compter comme « je reprends la main ». */
      if (t.closest('[data-help-pill]')) return;

      /* ⚠️ BORNÉ AU FORMULAIRE, ET C'EST INDISPENSABLE.
         J'avais écrit `[class*="wvs-"]` pour couvrir les pastilles du plugin.
         Or le plugin pose AUSSI ses classes sur le `<body>` lui-même
         (`wvs-behavior-blur`, `wvs-tooltip`…) : `closest()` remontait donc
         jusqu'en haut et n'importe quel clic — un lien du menu, le bandeau
         cookies, et surtout « Appliquer cette sélection » DANS la modale —
         armait le verrou. J'aurais cassé l'allumage en réparant l'extinction. */
      if (!t.closest('form.variations_form')) return;

      /* L'essence est rendue par le plugin en pastilles image : des `<li
         class="variable-item">` SANS aucun contrôle nommé. Le plugin recopie
         la valeur dans le menu caché via jQuery, ce qu'un écouteur natif ne
         voit pas — le clic est donc la SEULE voie d'extinction pour cet
         attribut. Ne pas retirer `.variable-item` de cette liste.
         Les trois autres sélecteurs couvrent les pastilles du thème et le lien
         « Effacer » de WooCommerce. */
      if (t.closest('.variable-item, .material-option, .swatch-item, .reset_variations')) {
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

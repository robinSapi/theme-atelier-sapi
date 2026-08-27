/**
 * Sapi Help Pill — la pill de Robin sur la fiche produit.
 *
 * UNE SEULE RÈGLE :
 *   La pill dit « C'est le mieux pour ton projet » quand CHAQUE menu de
 *   variation affiche exactement la valeur que le site a recommandée.
 *   Sinon elle dit « Je t'aide à choisir la bonne version ».
 *
 * C'est un message d'ÉTAT, pas d'action. Conséquence voulue par Robin le
 * 26/08 : la pill REVIENT si le visiteur s'éloigne puis retombe sur la version
 * recommandée. Il n'y a donc aucun verrou, aucun historique — juste une
 * comparaison, refaite à chaque changement.
 *
 * ⚠️ CE FICHIER A DÉJÀ ÉTÉ ÉCRIT DEUX FOIS AUTREMENT. Ce qui suit dit pourquoi,
 * pour que personne ne refasse le chemin.
 *
 * 1. Il a d'abord DEVINÉ la recommandation, en observant les événements émis
 *    par `sapi-product-preselect.js` et en séparant l'humain du code avec
 *    `event.isTrusted`. Deux raisons de l'abandonner, les deux mesurées sur la
 *    page réelle :
 *      • quand la valeur est déjà la bonne, la présélection n'écrit rien et
 *        n'émet rien : il n'y avait donc RIEN à observer, et la pill se taisait
 *        devant la recommandation affichée à l'écran ;
 *      • `isTrusted` ne sépare pas ce qu'on croit. Le bouton radio du plugin de
 *        pastilles émet un `change` natif ET trusted même déclenché par script,
 *        tandis que la pastille image n'émet aucun `change` du tout. Deux
 *        attributs de la même page, deux comportements opposés.
 *    Elle affirmait aussi le faux : ne connaissant qu'un historique et jamais
 *    l'écran, elle restait allumée sur une version qui n'était plus la sienne.
 *
 * 2. C'est maintenant `sapi-product-preselect.js` qui DÉCLARE sa
 *    recommandation, dans `data-sapi-recommandation` sur le formulaire. On la
 *    lit, on compare, c'est tout.
 *
 * ⚠️ LE DÉCLENCHEUR EST `found_variation` / `reset_data`, en jQuery, SUR LE
 * FORMULAIRE. WooCommerce émet toujours l'un des deux à chaque changement
 * d'attribut, 4 à 12 ms après le geste, les menus déjà à jour. Ça couvre
 * indifféremment le plugin de pastilles, les menus natifs, le lien « Effacer »
 * et les écritures de la présélection — une seule porte au lieu de trois.
 * Ce motif tourne DÉJÀ sur cette page (single-product.php) pour le prix, la
 * photo et la barre collante : la pill parle la même langue que le reste.
 * Écouter SUR LE FORMULAIRE, pas sur `document` : ce sont des événements
 * jQuery, invisibles d'un `addEventListener` natif, et c'est le formulaire qui
 * les émet. (Ils remontent bien jusqu'à `document` côté jQuery — le plugin de
 * pastilles s'en sert d'ailleurs — mais écouter à la source évite d'attraper un
 * autre formulaire de variation le jour où la page en portera deux.)
 */
(function () {
  'use strict';

  var TEXTE_ANNONCE = 'C\'est le mieux pour ton projet';

  /* Le même délai que WooCommerce s'impose avant de réafficher le bloc de
     variation. Trois effets : le texte de la pill et le prix juste en dessous
     bougent ensemble ; on absorbe le double `found_variation` que chaque
     écriture de la présélection provoque ; et on couvre les 400 ms entre la
     pose de l'essence et celle de la taille, donc pas de clignotement au
     chargement. Avec le fondu, le changement est perçu à ~480 ms : franc,
     jamais nerveux. */
  var ATTENTE = 300;

  function init() {
    var pill = document.querySelector('[data-help-pill]');
    if (!pill) return;

    /* Garde d'idempotence. La prod sert les scripts via un cache qui peut les
       combiner : si ce fichier s'exécutait deux fois, le clic ouvrirait la
       modale en double. */
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
       affiché — une lecture faite après une annonce prendrait « C'est le mieux
       pour ton projet » pour le texte de repli et ne reviendrait plus jamais en
       arrière. Le repli sur `textContent` couvre le cas d'un cache page qui
       servirait encore l'ancien HTML : à cet instant, il est la bonne valeur. */
    var TEXTE_NORMAL = textEl.getAttribute('data-help-pill-default') || textEl.textContent;

    /* Le MÊME périmètre que WooCommerce (`.variations select`), pas
       `select[name^="attribute_"]`. Le formulaire contient déjà un menu
       d'options payantes hors `.variations` ; le jour où un module en nomme un
       `attribute_…`, il entrerait dans le compte et la pill se tairait pour
       toujours, sans que rien ne casse. */
    function menusDeVariation() {
      return [].slice.call(form.querySelectorAll('.variations select'));
    }

    function recommandation() {
      try {
        return JSON.parse(form.getAttribute('data-sapi-recommandation') || '{}') || {};
      } catch (e) { return {}; }
    }

    function projetExiste() {
      try {
        return !!(window.sapiProject && window.sapiProject.hasProject && window.sapiProject.hasProject());
      } catch (e) { return false; }
    }

    function cestLeMieux() {
      var menus = menusDeVariation();
      /* ⚠️ LE REMPART. `every()` sur un tableau vide renvoie `true` : sans
         cette ligne, une page sans menu de variation allumerait la pill. */
      if (!menus.length) return false;
      var reco = recommandation();
      /* Chaque menu, sans exception. Un seul attribut recommandé sur deux ne
         suffit pas : « c'est le mieux » porte sur une VERSION, et une version
         c'est la combinaison entière. Tant qu'il en manque un, WooCommerce
         n'affiche d'ailleurs ni prix ni bouton d'achat — annoncer le meilleur
         au-dessus d'un formulaire qui réclame encore une réponse serait
         incohérent à l'œil autant que sur le fond.
         Cette règle se généralise seule : sur un produit à un seul attribut,
         une version entière fait un attribut. */
      return menus.every(function (s) {
        return s.name && reco[s.name] && s.value === reco[s.name];
      });
    }

    var texteActuel = TEXTE_NORMAL;
    var minuteur = null;

    function rendre() {
      var voulu = (projetExiste() && cestLeMieux()) ? TEXTE_ANNONCE : TEXTE_NORMAL;
      if (voulu === texteActuel) return; // indispensable : found_variation arrive en double
      texteActuel = voulu;
      textEl.style.transition = 'opacity .18s ease';
      textEl.style.opacity = '0';
      setTimeout(function () {
        textEl.textContent = voulu;
        textEl.style.opacity = '1';
      }, 180);
    }

    function rendrePlusTard() {
      clearTimeout(minuteur);
      minuteur = setTimeout(rendre, ATTENTE);
    }

    if (typeof jQuery !== 'undefined') {
      jQuery(form).on('found_variation reset_data', rendrePlusTard);
    } else {
      // Sans jQuery, WooCommerce ne fonctionne pas non plus — filet symbolique.
      form.addEventListener('change', rendrePlusTard, true);
    }

    // La présélection vient d'écrire sa carte : ne pas attendre le geste suivant.
    document.addEventListener('sapi:recommandation-declaree', rendrePlusTard);

    /* Le projet peut disparaître ou changer en cours de visite (« Recommencer
       mon projet »). La pill ne peut plus parler d'un projet qui n'existe plus.

       ⚠️ ORDRE DES ABONNÉS : ce fichier est chargé AVANT la présélection, donc
       il est notifié EN PREMIER — avant que l'étiquette n'ait été réécrite.
       C'est `rendrePlusTard` qui rend ça sûr : ses 300 ms laissent largement le
       temps à la présélection de mettre l'étiquette à jour, de façon synchrone,
       dans le même cycle de notification. **Ne jamais brancher `rendre`
       directement ici** — la pill lirait l'étiquette d'avant. */
    try {
      if (window.sapiProject && window.sapiProject.subscribe) {
        window.sapiProject.subscribe(rendrePlusTard);
      }
    } catch (e) { /* swallow */ }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

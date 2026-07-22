# Tasks — Coordination Cowork ↔ Claude Code

> Historique des tâches terminées archivé dans `claude_code_queue_archive.md` (nettoyé le 2026-06-03).

---

## [TÂCHE ✅ CODÉE — snippet prêt à coller, en attente validation Robin] Mode vacances — congés du 24 juillet au 24 août 2026
**Date :** 2026-07-22

### ✅ Résultat Claude Code (2026-07-22)
**Livrable :** `snippet-mode-vacances.php` à la racine du dépôt (même convention que les autres `snippet-*.php`). **C'est un snippet Code Snippets, PAS une modif du thème** (conforme à la demande). Rien poussé sur master.

**Ce que fait le snippet (tout piloté par 2 dates) :**
- **Bandeau haut de site** sur toutes les pages, injecté via `wp_body_open` (juste au-dessus du header, en flux normal → aucun conflit avec le header sticky, rien en `position:fixed`). Fond crème chaud charte (`#FBF6EA`→`#F4EAD4`), filet orange charte en haut (`#E35B24`), filet bois en bas (`#937D68`), texte foncé `#323232` (contraste ~11:1, largement lisible). Message = celui proposé, dates en gras.
- **Rappel court « expédition à partir du 24 août »** à 3 endroits : fiche produit (hook `woocommerce_before_add_to_cart_button`, juste au-dessus du bouton Ajouter au panier), page panier (`woocommerce_before_cart`), checkout (`woocommerce_before_checkout_form`, avant paiement). Petite pastille crème + liseré orange à gauche.
- La **date affichée est dérivée de la config** (`wp_date('j F')`) → changer la date de retour change le texte partout, automatiquement.

**Mécanisme dates :** en haut du fichier, fonction `sapi_vacances_reglages()` avec `'debut' => '2026-07-24'`, `'retour' => '2026-08-24'`. Actif du jour de début (00h00) au jour de retour **exclu** → **s'affiche tout seul le 24/07, disparaît tout seul le 24/08**. Prochaine fois : Robin change juste ces 2 dates. Toggle `'apercu' => false` : le passer à `true` force l'affichage pour tester hors période (bien le **remettre à false** avant la vraie mise en ligne).

**Charte respectée :** couleurs charte uniquement, **aucun tiret cadratin** (le « — » de l'exemple de la tâche a été remplacé par « : » dans les messages), accents en entités HTML pour zéro souci d'encodage dans l'éditeur Code Snippets. Sorties échappées (date via `esc_html`, reste = texte statique que je maîtrise).

**👉 Étape Robin pour valider sur test (le snippet n'est pas déployé par git — c'est un Code Snippet à coller) :**
1. Site **test** → réglages → **Code Snippets** → *Ajouter*.
2. Coller le contenu de `snippet-mode-vacances.php`, exécution **« Partout / Everywhere »**, Activer.
3. Comme on est avant le 24/07, pour prévisualiser : mettre `'apercu' => true`, vérifier le rendu (bandeau + fiche produit + panier + checkout), puis **remettre `'apercu' => false`**.
4. Quand c'est bon → « Go » : je peux te confirmer la manip, mais côté prod **c'est le même geste** (coller/activer le snippet sur le site prod). Les 2 dates étant déjà bonnes, il s'activera seul le 24/07 et se coupera seul le 24/08.

**⚠️ Hypothèse à confirmer :** panier/checkout en mode **classique** (shortcode), pas en blocs Gutenberg. Le site a des templates classiques custom (`woocommerce/cart/`, `woocommerce/checkout/`) → très probablement classique, donc les hooks panier/checkout marchent. **Filet de sécurité :** même si le panier/checkout étaient en blocs (hooks inline non déclenchés), le **bandeau site-wide s'affiche quand même sur ces pages** → le client est prévenu avant de payer dans tous les cas.

**Non vérifié ici :** rendu visuel réel (pas de navigateur / pas d'accès WP dans le bac à sable). Syntaxe PHP contrôlée (accolades/parenthèses équilibrées, tous les hooks pointent vers des fonctions définies) mais pas de `php -l` (binaire absent). À valider à l'œil sur test.

---

**Date (consigne d'origine) :** 2026-07-22
**Priorité :** HAUTE — ⏰ congés dans 2 jours (début vendredi 24/07). À livrer sur test vite pour validation Robin avant le départ.
**Branche :** test uniquement, jamais master. Robin valide avant prod.

**Contexte :** Robin part en congés du **vendredi 24 juillet au lundi 24 août 2026**. Il fabrique et expédie tout seul, donc aucune expédition pendant cette période. Décision prise : **on continue à vendre** (zéro perte de vente), on gère juste l'attente en prévenant clairement le client que sa commande sera **fabriquée et expédiée à partir du lundi 24 août**.

**À faire :**

1. **Bandeau haut de site** (toutes pages), aux couleurs de la charte, bien visible mais pas agressif. Message proposé (Robin peut ajuster) :
   « Atelier en congés jusqu'au 24 août. Vous pouvez commander dès maintenant : vos créations seront fabriquées et expédiées à partir du 24 août. Merci pour votre patience ! »

2. **Rappel du délai différé aux endroits sensibles** pour que personne ne découvre le délai après avoir payé :
   - **Fiche produit**, près du bouton « Ajouter au panier » : mention courte type « Atelier en congés — expédition à partir du 24 août ».
   - **Page panier + checkout** : le même rappel, visible avant paiement.

3. **Mécanisme d'activation/désactivation simple, piloté par dates.** Idéalement un **snippet via Code Snippets** (pas de modif directe du thème) qui active tout automatiquement entre deux dates paramétrables (début / fin de congés) et se désactive seul après. Robin ne doit pas avoir à toucher au code pour rentrer/sortir du mode vacances la prochaine fois — juste changer 2 dates.

**Critères de succès :**
- Le bandeau s'affiche sur tout le site pendant la période et disparaît seul après le 24 août.
- Le délai est rappelé sur fiche produit, panier et checkout.
- Le client peut commander et payer normalement.
- Robin peut réactiver le mode plus tard en changeant seulement les dates.
- Respect de la charte (couleurs, pas de tirets cadratins), rien poussé sur master.

**⚠️ Vu l'urgence :** si le mécanisme complet par dates prend trop de temps, livrer d'abord le **bandeau seul activable** (le plus important) et compléter le reste ensuite.


> **REFONTE FILTRAGE CONSEILLER — décisions d'architecture (11/06/2026).** Les tâches ci-dessous REMPLACENT les anciennes (qui supposaient un filtrage en double PHP/JS, désormais périmé).
> **Cap :** filtrage 100% côté serveur (PHP), un seul cerveau, **suppression du filtrage JavaScript** (le JS ne fait plus qu'afficher). Le filtre serveur est appelé à **2 moments** : (1) au chargement de `/mes-creations/` avec une pièce, (2) à la fermeture de la modale (questionnaire terminé OU abandonné en cours). L'IA (Sonnet) n'ajoute qu'un **commentaire** en fin de questionnaire.
> **Source de vérité du comportement voulu :** `assets/guide-filtrage-simulateur.html` (simulateur jouable + éditeur de règles, à ouvrir). Doc d'appui : `assets/guide-filtrage-impact.html`.
> **Toutes les tâches :** branche test uniquement, jamais master, Robin valide avant prod.

## [✅ FAIT — sur test] Immersion = via le room-picker (approche simple)
L'immersion s'active sur `?piece=` valide. En pratique ces URLs viennent du room-picker (cartes = liens `?piece=`). La **reprise auto** (qui ajoutait `?piece=` sans clic pour les revenants) a été **retirée** → un revenant arrive sur le room-picker. Pas de cookie (approche cookie abandonnée car sur-compliquée + souci cache prod). Seul compromis assumé : un lien `?piece=` partagé/favori affiche l'immersion (indistinguable d'un vrai clic). Aucun impact cache prod.

## [BUG ✅ CORRIGÉ — sur test] Le commentaire IA en fin de modale ne s'écrit jamais
Cause : le commentaire Sonnet (`sapi_megafilter_advice` → `advice_text`) était bien calculé, mais son lieu d'affichage (carte « Mon projet » + `sapi-cards-conseiller.js`) a été supprimé pendant la refonte (4b/7). **Fix** : il s'affiche désormais dans la **phrase de l'immersion** — la modale émet `sapi:advice-loading` (dès le début du calcul → loader 3 points qui remplace la phrase générique) puis `sapi:advice-ready` (texte → tapé à la machine, ou repli générique si vide). En attente de validation Robin.

## [TÂCHE 1] Architecture — filtrage serveur unique (fondation)
**Priorité : HAUTE — à faire en premier, les autres en dépendent.**
**⚠️ Demander un PLAN + un état des lieux AVANT de coder** : la branche test est en cours de refonte et instable. Plan validé par Robin, puis implémentation par sous-étapes.

Objectif : une seule fonction PHP qui, à partir des réponses (pièce, taille, sortie, hauteur, style…), renvoie la sélection filtrée + classée. Appelée à 2 moments :
- au **chargement** de `/mes-creations/?piece=X` → rendu serveur de la sélection au niveau pièce ;
- à la **fermeture de la modale** → AJAX avec les réponses données (partielles ou complètes ; endpoint `sapi_robin_filter_products` existe déjà sur master).

Retirer au JS (`sapi-cards-conseiller.js`) son rôle de moteur de filtrage : plus aucune règle métier dupliquée côté navigateur, il ne fait qu'afficher ce que le serveur renvoie.

Important pour la suite : faire en sorte que TOUTES les règles vivent dans **une config unique** (étendre `$sapi_filter_rules`) lue par ce filtre — c'est ce qui rendra possible la page admin (tâche 5).

**Critères de succès :** une seule logique de filtrage (PHP) ; le JS n'applique aucune règle ; les 2 points d'appel marchent ; fermer la modale en cours donne une sélection cohérente.

## 📐 RÉPONSE CLAUDE CODE — ÉTAT DES LIEUX + PLAN Tâche 1 (11/06, EN ATTENTE DU GO DE ROBIN)

### État des lieux du filtrage actuel (résumé)
Le filtrage est **hybride et les règles sont TRIPLIQUÉES** :
- **PHP** (functions.php) : `sapi_guide_get_categories` (cats par sortie + cuisine), `sapi_guide_get_ampoule_filter` (ampoule par pièce), `sapi_guide_query_products` (format vertical/horizontal + ampoule, WP_Query), `sapi_guide_collect_results` (variation essence/taille + images), `sapi_guide_pick_four` (les 4 retenus + `diversify_format`). **Règles EN DUR**, ne lisent PAS `$sapi_filter_rules`.
- **`$sapi_filter_rules`** (l.325) : config… **lue UNIQUEMENT par le JS** (localisée), pas par le PHP.
- **JS** (`sapi-cards-conseiller.js`) : `getAcceptedCategories`/`getAmpouleFilter`/`isVerticalAllowed`/`cardMatchesAnswers`/`computeEffectiveAnswers` (élargissement progressif) + `window.sapiMegaFilter` → `shop.js applyFilters` filtre la grille **dans le navigateur**. C'est ce qu'il faut supprimer.
- **2 endpoints AJAX** : `sapi_ajax_guide_results` (parcours complet → produits + texte IA Sonnet) et `sapi_ajax_robin_filter_products` (renvoie juste des IDs filtrés — **existe mais n'est appelé par aucun JS aujourd'hui** → c'est le candidat pour « moment 2, fermeture modale »). Aujourd'hui la modale, à la fermeture, **re-filtre en JS** (`sapiShopRefilter`), pas via cet endpoint.
- **Manque côté PHP vs simulateur** : la **couche PRIORITÉ/classement** (rang ampoule/catégorie/format + ordre d'importance + souple/strict) n'existe PAS en PHP — c'est la grande nouveauté. L'**élargissement progressif** n'existe qu'en JS.
- **Déjà aligné** : le **hero immersif état B que je viens de faire utilise DÉJÀ le filtrage serveur** (`sapi_guide_query_products`) au chargement = moment 1. Et j'ai déjà **neutralisé `sapiMegaFilter` en mode immersion** (catalogue laissé au serveur). Donc /mes-creations/ en état B est déjà à moitié sur le nouveau modèle.

### Plan Tâche 1 — moteur serveur unique (sous-étapes livrables sur test)
1. **Config unique** : étendre `$sapi_filter_rules` pour contenir TOUTE la config du simulateur (objet `C`) : cats par sortie (+ secondaire), ampoule par pièce **+ préférée**, format préféré par pièce, `cuisineRemove`, `grandeSkipAmpoule`, règles vertical/horizontal, **catégorie prioritaire par sortie**, **ordre d'importance** [catégorie>ampoule>format], prio on/off, mode souple/strict, style→essence, map escalier, grandeExclut2Tailles. En PHP d'abord, **structurée pour basculer en option WordPress (DB) en Tâche 5**. Devient la **seule source**, lue par PHP ET (le strict minimum) par le JS d'affichage.
2. **Moteur PHP unique** `sapi_conseiller_filter($answers)` qui reproduit EXACTEMENT le pipeline du simulateur : normalise (escalier→taille) → catégories (lit la config) → filtre dur (catégorie + format + ampoule) sur le catalogue → **classement priorité** (rang→score lexicographique selon l'ordre d'importance, souple/strict) → renvoie **la sélection classée** (slider immersion) **+ les 4 picks** (modale via `pick_four`). Refactorer `get_categories`/`get_ampoule_filter`/`query_products`/`collect_results` pour **lire la config** (plus de règles en dur) + ajouter la couche priorité.
3. **Brancher les 2 appels** : (a) **chargement** immersion → remplacer l'appel actuel par le moteur (sélection classée) ; (b) **fermeture modale** → faire que `sapi_ajax_robin_filter_products` appelle le moteur et renvoie la sélection ordonnée ; brancher `sapi-modal-conseiller.js` (fermeture terminée OU abandonnée) pour appeler cet endpoint et **mettre à jour la sélection** ; le JS ne fait QUE rendre.
4. **Couper le filtrage JS** : retirer `getAcceptedCategories`/`getAmpouleFilter`/`cardMatchesAnswers`/`computeEffectiveAnswers`/`sapiMegaFilter` de `sapi-cards-conseiller.js` et la dépendance de `shop.js` ; le JS masque/affiche selon les **IDs renvoyés par le serveur**.
5. **Vérifs** : sélection immersion == simulateur ; fermeture modale == simulateur ; zéro règle JS résiduelle ; catalogue intact ; console 0 erreur.

### Questions à trancher AVANT que je code
1. **Élargissement progressif vs mode souple** : le simulateur « souple » ne fait que CLASSER (n'exclut jamais), mais ne relâche pas les **filtres durs** (catégorie/ampoule/format) si 0 produit. On **garde** un repli qui relâche les filtres durs quand 0 résultat (comme l'actuel `computeEffectiveAnswers` JS + les fallbacks de `query_products`), ou on accepte « 0 résultat → carte sur-mesure » ? (Reco : garder un repli serveur léger.)
2. **Config en DB tout de suite ou en dur d'abord ?** Reco : array PHP en Tâche 1, basculé en option WordPress en Tâche 5 (sinon on code deux fois).
3. **Sortie du moteur** : pour l'immersion = liste classée complète ; pour la modale = les 4 picks. On renvoie les deux ? (le simulateur produit les deux). 
4. **⚠️ Branche test « instable »** : la note dit que la branche test est en cours de refonte. **Mon état B immersion EST-il cette refonte, ou une autre fenêtre Claude bosse en parallèle sur le filtrage ?** À confirmer pour ne pas se marcher dessus (cf. [[feedback_multi_claude_coordination]]).

### 👉 Action Robin
Valider/ajuster ce plan + répondre aux 4 questions. Sur « go » → je code la Tâche 1 par sous-étapes (1→5), push test, validation à chaque étape. Puis Tâches 2 (règles), 3 (priorités), 4 (room-picker), 5 (admin), 6-7 (IA + nettoyage).

### ✅ Avancement (validé Robin sur test)
- **T1 moteur FONCTIONNELLEMENT COMPLET** : config unique `$sapi_filter_rules` (toute la config du simulateur) ; `sapi_conseiller_rank_products()` (couche priorité, mécanique du simulateur) ; filtre dur PHP (`get_categories`/`get_ampoule_filter`/`query_products`/`collect_results`) **lit la config** (plus de règles en dur) ; **moment 1** (chargement immersion) + **moment 2** (fermeture modale → endpoint `sapi_ajax_immersion_selection` → re-filtre+classe serveur → remplace le slider) **OK terminé ET abandonné**. Markup card = source unique (`sapi_immersion_render_product_card`). Commits `bdeaae2`, `cd20893`, `8c799ac`.
- **⏳ Reste T1 « supprimer le filtrage JS »** : COUPLÉ à la Tâche 4 (le filtre JS `sapi-cards-conseiller.js` ne sert plus qu'à l'**état A** sans `?piece=`). À supprimer **avec** le room-picker (Tâche 4) qui remplace l'état A. Ne pas le retirer avant, sinon l'état A casse.
- **🔧 À LISSER PLUS TARD (demandé Robin)** : la mise à jour du slider au moment 2 **flashe** (remplacement sec des cards) → faire une **transition douce** (fade-out/fade-in) au lieu d'un swap brutal.
- **✅ MOMENT 2 FIABILISÉ + VALIDÉ** : la modale émet `sapi:conseiller-closed` (réponses finales) à **chaque** fermeture (fin + abandon) ; l'immersion l'écoute (on n'écoute plus le `subscribe` sapiProject, dont le notify dépendait du flush `pendingNotify` du resume → « ne se recharge pas tout le temps »). Baseline de dédup = sélection serveur (pièce seule) ; signature brûlée seulement si succès AJAX. **Changement de pièce** (projet recommencé) → **rechargement page** `?piece=<nouvelle>` (décor + sélection cohérents) ; même pièce + affinages → AJAX slider seul.
- **✅ T2 (règles) FAIT + VALIDÉ** : vertical autorisé dès plafond haut, cuisine retire lampe à poser ET lampadaire, **question « table » supprimée** du parcours (colonne analytics `table_reponse` conservée). Commits `04a02df`, `0f6b477`.
- **✅ T3 (priorités) de fait FAIT** : le classement par priorité (mécanique du simulateur) est en place et **s'applique réellement** depuis le fix config.
- **⚠️ PIÈGE MAJEUR corrigé (commit `e1704ab`)** : `$sapi_filter_rules` était une **variable LOCALE** de la fonction d'enqueue → `global $sapi_filter_rules` renvoyait vide → tout le filtre PHP utilisait les **valeurs de repli (anciennes règles)** : priorité no-op, `cuisine_remove`/`vertical_haute` jamais lus. **Symptôme trompeur : "rien de cassé" = en fait "rien ne s'applique".** Fix : config dans une **fonction** `sapi_conseiller_get_rules()` lue partout (enqueue + filtre + endpoint), zéro global. **RÈGLE : une config partagée PHP doit vivre dans une fonction, jamais en variable locale d'enqueue.**

## [TÂCHE 2] Règles de filtrage (dans le filtre serveur)
**Priorité : HAUTE — après tâche 1.** Comportement détaillé : voir le simulateur (`guide-filtrage-simulateur.html`).

Appliquer dans le filtre serveur :
- **Vertical** : autorisé dès `hauteur === 'haute'` (toutes pièces, toutes tailles). `confortable` garde la règle actuelle (entrée ou petite pièce). Escalier : vertical OK, horizontal exclu. Horizontal exclu en petite pièce + plafond haut. Boule toujours autorisée.
- **Supprimer la question « table »** du parcours (aucun effet sur la sélection, vérifié). Retirer l'étape de `inc/guide-data.php`, les références de libellés/`valid_keys` dans `functions.php`, et les références dans les JS de la modale. **GARDER** la colonne analytics `table_reponse` (historique) — juste arrêter de l'alimenter.
- **Cuisine : retirer lampes à poser ET lampadaires** des catégories (généralise l'actuel « pas de lampe à poser » en cuisine).

**Critères de succès :** comportement identique au simulateur (sections « Hauteur et format », « Taille », « Pièce »).

## [TÂCHE 3] Priorités — couche de préférence (dans le filtre serveur)
**Priorité : normale — après tâches 1-2.** Source de vérité : simulateur (sections Priorité, Pièce, Où installer).

La préférence ne fait que **CLASSER**, elle n'exclut jamais. Chaque produit reçoit un rang par critère, combinés en un score, **mode souple** (préférés en tête, on complète avec les autres si trop peu).
- **Ampoule** (par pièce) : chaleureux (salon/chambre/chambre-enfant/entrée) → préféré `ampoule_entouree` ; travail (cuisine/bureau) → `ampoule_degagee`. Map explicite (ne pas se reposer sur l'ordre d'un tableau).
- **Catégorie** (par sortie) : une catégorie prioritaire optionnelle par sortie (surtout utile pour « je ne sais pas » → les 4 catégories).
- **Format** (par pièce) : un format préféré optionnel par pièce (boule / horizontal / vertical).
- **Ordre d'importance** réglable des 3 critères ; défaut : **catégorie > ampoule > format**.

Mécanisme : un `priority_rank` (0/1) par critère → score lexicographique selon l'ordre d'importance → tri stable de la sélection ; en souple, compléter avec les rangs suivants.

**Critères de succès :** reproduire le simulateur (tester salon plafond haut, cuisine, sortie « je ne sais pas »).

## [TÂCHE 4] Room-picker sur /mes-creations/ pour l'arrivée sans projet
**Priorité : normale.** **Demander un plan + un mockup avant de coder.**

Mettre un room-picker sur `/mes-creations/` pour le visiteur qui arrive sans pièce. Choisir une pièce → charge la page avec cette pièce → déclenche l'appel filtre serveur (tâche 1, moment 1) → sélection au niveau pièce. Chaque arrivée passe ainsi par le même chemin serveur.

**Précision Robin (11/06) — gating de l'immersion :** l'expérience immersion (plein écran) ne doit s'afficher QUE si on arrive **depuis un room-picker** (donc avec une pièce). Si on arrive autrement sur `/mes-creations/` (sans pièce), afficher le **room-picker vierge**, PAS l'immersion (sinon c'est trop envahissant).

**Critères de succès :** arrivée sans pièce → room-picker vierge (pas d'immersion) ; arrivée avec pièce depuis un room-picker → immersion + sélection serveur au niveau pièce ; aucune régression depuis la home.

### 📐 PLAN Tâche 4 (11/06 — décisions Robin prises, EN ATTENTE DU GO)
**Décisions Robin :** (1) le room-picker **EST le hero** de l'état A ; (2) revenant avec projet sauvegardé → **reprise AUTO** : redirection directe vers `?piece=<pièce>` au chargement (pas de bande, pas de choix) ; (3) on **garde** le champ texte libre. Pour repartir de zéro : « Décrire mon projet » (change la pièce, recharge). Catalogue bare toujours atteignable car présent sous le hero immersif.
**Maquette :** `mockups/mes-creations-room-picker-etatA-v1.html` (toggle nouveau/revenant en haut).
**Sous-étape 4a — état A serveur (la feature visible) :**
- Dans `archive-product.php`, quand `$imm_piece === ''` : remplacer le hero artisan + les cartes conseiller cachées (`.conseiller-card--conseil/--mon-projet`, pilotées en JS) par un **room-picker serveur** identique à la home (signature Robin + titre + 7 cartes `<a href="?piece=<slug>">` + « ou » + freetext). **Factoriser** `$room_choices`/`$room_icons` de `front-page.php` dans des helpers partagés (`sapi_room_choices()`/`sapi_room_icon_svg()`), pas de copier-coller.
- Catalogue complet conservé dessous (inchangé).
- Reprise auto : **script inline dans `wp_head`** (uniquement état A `/mes-creations/` sans pièce) qui lit `localStorage['sapiProject']`, et si `answers.piece` est dans la whitelist → `location.replace('?piece='+piece)` **avant le paint** (zéro flash). Sinon le room-picker s'affiche. `STORAGE_KEY='sapiProject'`.
- Freetext : garder le comportement home (`?freetext=` → auto-ouvre la modale chat). **Vérifier** que l'auto-ouverture ne dépend pas de `sapi-cards-conseiller.js` (sinon la déplacer avant la coupe JS).
**✅ 4a + 4b CODÉS + SUR TEST (en attente validation Robin).**
- 4a : room-picker serveur en hero de l'état A + reprise auto.
- 4b : `assets/sapi-cards-conseiller.js` **SUPPRIMÉ** (tout le moteur de filtrage JS). Filtrage 100% serveur. Config `rules` plus exposée au JS. Conseils génériques par pièce déplacés sur `sapi-modal-conseiller` (global `SAPI_CARDS_CONSEILLER` conservé, clés genericAdvice+fallbackAdvice). Dépendance modale → `['sapi-project']`. **→ Termine la Tâche 1 step 4 (« supprimer le filtrage JS »).**
- Dead markup restant : `.conseiller-cards-zone` (branche état B, caché CSS, inerte) → à nettoyer en **Tâche 7**.
**Sous-étape 4b — couper le filtrage JS (= fin Tâche 1 step 4) :**
- Retirer de `sapi-cards-conseiller.js` : `getAcceptedCategories`/`getAmpouleFilter`/`cardMatchesAnswers`/`computeEffectiveAnswers`/`sapiMegaFilter` + la dépendance `shop.js applyFilters`. Le catalogue état A reste **complet** (plus de filtrage navigateur).
- ⚠️ `sapi-cards-conseiller.js` fait aussi : délégation `data-action="open-modal"`, ouverture modale depuis room-cards/forms, peuplement carte « Mon projet ». Vérifier ce qui reste utilisé (la modale écoute `sapi:open-modal` elle-même → découplée). Garder un handler minimal « clic → dispatch sapi:open-modal » si encore nécessaire ailleurs.
**Vérifs :** sans pièce → room-picker (carte=lien) ; clic → `?piece=` → immersion ; catalogue intact ; revenant → bande « Reprendre » ; freetext → modale ; **home inchangée** ; console 0 erreur ; zéro règle de filtrage JS résiduelle.

## [TÂCHE 5] Page admin WordPress — piloter les règles de filtrage (proposition 2)
**Priorité : normale — APRÈS tâches 1-3 (le filtre serveur et ses règles doivent exister et être centralisés).**
**Prérequis :** toutes les règles dans une config unique persistable (cf. tâche 1). Aujourd'hui certaines sont en dur → il faut d'abord les rassembler et les stocker (options WordPress / DB) pour qu'une page puisse les éditer.

Objectif : une page dans l'admin WordPress (comme le dashboard de stats du Conseiller) où Robin édite lui-même les règles, et ça s'applique au site en direct, sans repasser par du code :
- catégories par sortie ; ampoules acceptées + préférée par pièce ; règles de format ; priorités (ampoule/catégorie/format) + ordre d'importance ; exclusions par pièce (cuisine).
- Le filtre serveur (tâche 1) lit ces réglages depuis la DB au lieu de valeurs en dur.

**Le simulateur `assets/guide-filtrage-simulateur.html` EST la maquette de cette page** (mêmes réglages, même organisation) → s'en servir comme cahier des charges UI.

**Demander un plan avant de coder.** Soigner : ne pas laisser créer des combinaisons incohérentes (ex. retirer la catégorie qu'impose une sortie). Prévoir un « réinitialiser aux valeurs par défaut ».

**Critères de succès :** Robin modifie une règle dans l'admin → effet immédiat sur le filtrage du site, sans toucher au code.

### 📐 PLAN Tâche 5 (11/06 — décisions Robin : TOUT éditable + APERÇU LIVE intégré ; EN ATTENTE DU GO)
**Principe clé :** l'aperçu live n'embarque PAS de moteur JS (on vient de le supprimer du front, pas question de recréer 2 cerveaux). L'aperçu appelle le **vrai moteur PHP** avec les règles en cours d'édition, injectées via un hook `apply_filters('sapi_conseiller_rules', …)` le temps de la requête. Garantit zéro divergence aperçu/prod.
**Découpage (sous-étapes livrables sur test) :**
1. **5.1 — Socle config en DB (sans changement de comportement) :** renommer l'array actuel en `sapi_conseiller_default_rules()` ; `sapi_conseiller_get_rules()` = deep-merge de `get_option('sapi_conseiller_rules', [])` PAR-DESSUS les défauts, puis `apply_filters('sapi_conseiller_rules', $merged)`. Option vide → 100% défauts → comportement identique.
2. **5.2 — Page admin (menu + affichage) :** page sous le menu Conseiller (cap `manage_options`), formulaire pré-rempli reproduisant les sections du simulateur : ampoule_by_piece, ampoule_skip_when_grande, cats_by_sortie (+ secondaire), cuisine_remove/exclusions, prefs (ampoule/format/cat), prio+importance+mode, règles format booléennes, grande_exclut_2_tailles, style_essence, escalier_map. Listes de slugs valides = source unique (catégories WooCommerce, ampoules/formats/sorties/pièces depuis guide-data).
3. **5.3 — Sauvegarde + garde-fous :** POST nonce+cap, sanitization stricte (chaque slug validé contre sa whitelist), garde-fous anti-incohérence (ex. ne pas retirer la catégorie imposée par une sortie), bouton « Réinitialiser aux défauts » (supprime l'option).
4. **5.4 — Aperçu live (server-side) :** endpoint AJAX `sapi_admin_filter_preview` : reçoit l'état NON SAUVEGARDÉ du formulaire + des réponses (pièce + sortie/taille/etc.), pose le filtre `sapi_conseiller_rules` = règles draft, lance get_categories+query_products+rank, renvoie la sélection classée (vignettes+noms, + rang/score en debug). UI admin façon simulateur (pickers réponses → sélection).
**Vérifs :** option absente → défauts (diff nul) ; éditer une règle → effet immédiat sur immersion + moment 2 ; aperçu live == site ; reset OK ; sanitization rejette un slug inconnu ; 0 combinaison incohérente sauvegardable.

**✅ TÂCHE 5 FAITE + VALIDÉE SUR TEST (aperçu live OK, édition OK, pas de corruption).** Fichier `inc/conseiller-rules-admin.php` (require sous `is_admin()`).
- ⚠️ **PIÈGE serveur (O2switch/WAF)** : des cases à cocher multiples avec `name="...[]"` (même nom) sont **fusionnées → 1 seule valeur conservée**. Solution : **nom UNIQUE par case** `rules[map][ligne][option]=1`, sélection = clés cochées. À réutiliser pour tout futur formulaire admin avec cases multiples.
- ⚠️ Clé de ligne `''` (sortie par défaut) → `rules[map][][]` lue comme index numérique : encoder en sentinel `__empty`.
- Sous-menu enregistré en **priorité 11** (après le menu parent). Reset = `delete_option('sapi_conseiller_rules')`. 5.1 socle DB (sapi_conseiller_default_rules + get_rules merge option + apply_filters) ; 5.2/5.3 page sous-menu « Règles de filtrage » (priorité 11), formulaire schema-driven, sauvegarde admin-post (nonce+cap), sanitization whitelist, garde-fous, reset ; 5.4 aperçu live via endpoint `sapi_admin_filter_preview` (règles draft injectées par filtre → vrai moteur). Libellés clarifiés (éclairage principal/appoint). Pas de binaire PHP local → vérifié par équilibrage accolades ; blast radius = admin seul.

## [TÂCHE 6] Règle IA — suspension principale en grande pièce
**✅ FAIT (sur test)** — règle « RÈGLES SUSPENSION PRINCIPALE EN GRANDE PIÈCE » ajoutée dans `assets/guide-prompt-regles.txt`.
**Priorité : basse.** Éditer `assets/guide-prompt-regles.txt` : ajouter une règle pour que, quand une suspension est proposée comme éclairage **principal** dans une **grande pièce**, l'IA avertisse honnêtement qu'un seul luminaire peut ne pas suffire et suggère un complément (lampadaire, applique) ou un ensemble sur-mesure. (Le savoir + l'exemple existent déjà mais restent suggestifs ; une règle explicite rend l'avertissement fiable.)

## [TÂCHE 7] Nettoyage legacy (quand le nouveau flux est en prod)
**✅ FAIT (cœur) SUR TEST :**
- Quiz V1 mort retiré : `sapi_ajax_guide_results` (+ 2 add_action) + `sapi_guide_build_system_prompt` (−198 l). Gardés : nonce `sapi-guide-results` (partagé), `sapi_guide_check_rate_limit` (partagé), `sapi_guide_pick_four` + code `diversify_format` (grappe).
- Dead markup `.conseiller-cards-zone` retiré de l'état B d'archive-product.php (−123 l), hero artisan conservé.
- Filtrage JS déjà supprimé en 4b.
**⏸️ RESTE (optionnel, inoffensif — non fait par prudence) :**
- Dictionnaires `'table'` résiduels dans des fonctions LIVE (prompts IA `sapi_megafilter_build_freetext_prompt`, KEY_LABELS, export admin) — clés jamais lues depuis le retrait de la question, mais éditer des prompts IA sans pouvoir tester la sortie = risque non justifié. À faire si Robin veut, en testant l'IA.
- Helpers devenus orphelins par le retrait du quiz V1 : `sapi_guide_build_filter_context`, `sapi_guide_call_claude` (vs `_refine` utilisé). Morts mais inoffensifs.
- CSS mort : règles `.conseiller-cards-zone` / `.mes-creations-section-divider` (éléments retirés). Inoffensif.
**Priorité : basse, en dernier.** Une fois la refonte stable et validée : retirer le quiz V1 mort (`sapi_ajax_guide_results` + `sapi_guide_build_system_prompt`, plus appelé par aucun JS) et le filtrage JS s'il est entièrement remplacé. À faire prudemment, en vérifiant qu'aucun appel ne subsiste.
**⚠️ NE PAS supprimer le code « grappe »** (`diversify_format` dans `sapi_guide_pick_four`) : il est orphelin mais c'est une idée à conserver et à réactiver plus tard — voir l'idée ci-dessous.

## [IDÉE — à explorer plus tard] Grappe / multi-ampoules comme rampe vers le sur-mesure
Le mode « grappe » (montrer un produit de chaque format = de la diversité, + afficher la carte sur-mesure) est aujourd'hui orphelin (l'option a été retirée du questionnaire). Robin veut le **garder** : il doit servir de **support pour orienter vite le visiteur vers le sur-mesure**. L'objectif : que l'IA / le parcours puisse proposer rapidement une composition multi-ampoules ou un ensemble sur-mesure quand c'est pertinent, et le mécanisme « un de chaque format » est un bon véhicule pour donner à imaginer. À recâbler dans le nouveau système (porte d'entrée à définir) le moment venu. Le code `diversify_format` dans `sapi_guide_pick_four` est conservé exprès pour ça.

---

## [✅ FAIT — 2026-07-16] Charte — pousser la refonte du logo et des polices
**Date :** 2026-07-16
**Priorité :** normale

### ✅ Résultat Claude Code (2026-07-16)
- **Vérif avant commit** : `git status` propre — le `.ai` est bien dans `_local/` (gitignoré), rien d'interdit (`_local/`, `.DS_Store`, `.ai`, `charte-en-ligne.zip`) stagé. ✅
- **Commit** `dea72a2` « Logo régénéré depuis le vectoriel + zone de protection + Montserrat », `git push origin main` → OK.
- **Déploiement GHA** confirmé (fichier live re-daté ~30 s après le push). Critères testés :
  - `/` → 200, **identique** à `index.html` poussé ✅
  - `download/logo/logo-sapi-noir-zone.svg` → 200 (`image/svg+xml`) ✅
  - `download/logo-sapi.zip` → 200 (356 Ko) ✅
  - `download/polices-sapi.zip` → 200 (492 Ko) ✅
  - `download/square-peg.zip` → **404** (renommé, bien supprimé par la synchro FTP) ✅
  - `_local/` → **404** ✅
  - anciens PNG (`logo-sapi-noir-1000.png`) → **404** (supprimés), nouveaux PNG 1200 → 200 ✅
- **🔒 SSL** : l'AutoSSL O2switch est **désormais émis** → `https://charte.atelier-sapi.fr` répond en 200 avec **certificat valide** (le point en attente de la tâche précédente est résolu).

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`). Ne rien toucher dans `theme-atelier-sapi`.

**Contexte :** Robin a validé. Tout est écrit et vérifié côté fichiers, il ne reste qu'à
committer et pousser. Le workflow GHA déploie tout seul sur `charte.atelier-sapi.fr`.

**Ce qui change :**
- Logo entièrement régénéré depuis le master vectoriel (`.ai` converti en SVG).
  4 déclinaisons (noir, bois, gris, creme) x (SVG nu + SVG avec zone + PNG 1200/600/300).
- Zone de protection : carré = 2x le diamètre du disque, disque centré, marge D/2.
  Ratio vérifié à 0,500 sur les 5 images.
- Montserrat ajoutée au pack (4 TTF : Light, Regular, Bold, Black).
- Anciens PNG au lettrage évidé supprimés, filtres CSS retirés de la page.
- Section Logo : les cartes n'ont plus de padding, elles SONT le carré de protection.

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — vérifier qu'il n'y a NI `_local/`, NI `.DS_Store`, NI `.ai`, NI `charte-en-ligne.zip`.
   (contrôlé côté Cowork : c'est propre, mais revérifier avant de committer)
3. `git add -A`
4. Commit : « Logo régénéré depuis le vectoriel + zone de protection + Montserrat »
5. `git push origin main`
6. Suivre le run Actions. En cas d'échec, rapporter le log ici sans retenter en boucle.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` à jour : section Logo avec 6 cartes à l'échelle
- `download/logo/logo-sapi-noir-zone.svg` → 200
- `download/logo-sapi.zip` et `download/polices-sapi.zip` → 200
- `download/square-peg.zip` → **404 attendu** (renommé en polices-sapi.zip)
- `/_local/` → toujours 404

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

</details>

---

## [✅ FAIT — 2026-07-17] Charte — pousser en prod : photos, survol, carrousel, palette

**Date :** 2026-07-17
**Priorité :** normale

### ✅ Résultat Claude Code (2026-07-17)
**⚠️ Déployé en 2 commits — Cowork a enrichi les fichiers PENDANT mon travail** (ajout suppression Print + palette Affinity + README, après mon 1er commit). J'ai donc complété avec un 2e commit pour que le live corresponde à l'état final voulu. Le résultat en ligne est complet et correct.
- **Commit 1** `14bd66c` « Photos, doctrine de survol, carrousel de densité, dérivées en bande » (48 fichiers : photos, survol, carrousel, dérivées en bande, montserrat/square-peg séparés).
- **Commit 2** `88eb2be` « Retrait de la rubrique Print + palette Affinity (.clr) » (le delta arrivé après : `index.html` sans Print, `download/palette-sapi.clr`, `README.md`).
- **Vérif avant chaque commit** : `.DS_Store` (dans `download/fonts/`) et `.ai` (dans `_local/`) bien **ignorés**, absents du staging. Rien d'interdit poussé. ✅
- **Déploiement GHA** confirmé pour les 2 push (fichier live re-daté à chaque fois). Critères finaux testés (cert SSL valide) :
  - `/` → 200, **identique** à `index.html` final poussé ✅
  - `download/square-peg.zip` + `download/montserrat.zip` → **200** ✅
  - `download/polices-sapi.zip` → **404** (pack combiné retiré par la synchro FTP) ✅
  - `download/fonts/SquarePeg-Regular.woff2` → **404** ; `…SquarePeg-Regular.ttf` → 200 ✅
  - `download/palette-sapi.clr` → **200** (1670 o ; non matché par les exclusions `*.zip`, monte bien, pas besoin de toucher `deploy.yml`) ✅
  - `img/regle-zone.jpg` → 200 ; `img/regle-2-3.jpg` + `img/sapi-art.jpg` → **404** ✅
  - **Print retiré** : plus qu'**1** occurrence de « print » dans le HTML live = le garde-fou `localStorage` (attendu), aucun onglet Print ✅
  - `_local/` → **404** ✅
- **Contrôle assets** : tous les fichiers (img/ + download/) référencés dans le `index.html` live répondent **200** — aucun lien cassé.
- **⚠️ Rendu visuel non vérifié** (pas de navigateur dans le bac à sable, comme signalé par Cowork) : contrastes/largeurs/carrousel/survol + le garde-fou `sapi-charte-ctx=print` (page pas blanche) à contrôler à l'œil. Structure + tous les assets OK, mais le visuel reste à valider par Robin.

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`, branche `main`). Ne rien toucher dans `theme-atelier-sapi`.

**Contexte :** Robin a validé. Tout est écrit et vérifié côté fichiers, il ne reste qu'à
committer et pousser. Le workflow GHA déploie seul sur `charte.atelier-sapi.fr`.
48 fichiers en attente depuis le commit `dea72a2`.

**Ce qui change :**
- **Survol** : doctrine posée. Bouton = la couleur fonce + l'ombre s'ouvre, aucun mouvement.
  Carte = elle lévite, la couleur ne bouge pas. Inerte = rien. Boutons en aplat, plus de dégradé.
- **Couleurs** : une seule pastille par couleur, coupée 66/34. La dérivée de survol est la
  tranche droite (hex + « SURVOL », copiable au clic, token en `title`). Le bloc « 1 DÉRIVÉE »
  et les cartes `span 2` sont supprimés → **la grille des couleurs est devenue uniforme**.
- **Photos** : section refaite. 4 exemples segmentés, 3 overlays côte à côte, carrousel de
  densité pleine largeur (scroll-snap), lightbox qui conserve la bordure rouge des faux.
- **Règle de cadrage** : « le luminaire au centre, entier dans la moitié centrale ».
  Nouvelle image `img/regle-zone.jpg`. `img/regle-2-3.jpg` et `img/sapi-art.jpg` supprimées.
- **Section Formats refondue** : grille en 3 colonnes explicites (l'`auto-fit` écrasait les cartes),
  Pinterest réuni en une seule carte pleine largeur avec ses 3 ratios (2:3 référence, carré, long).
  Sous-titre supprimé : il énonçait encore l'ancienne règle des 2/3 et contredisait la section Photos.
  Nouvelle règle « Le centrage » + `img/regle-centrage.jpg`.
- **Les 3 exemples d'overlay repassés en 4:5** (ils étaient en 4:3), régénérés depuis `situation.jpg`.
- ⚠️ **Règle du nom de produit appliquée aux images** : prénom Montserrat 700 CAPITALES + surnom
  Square Peg, sur une seule ligne de base, rapport 2,12× (la charte affiche 2,13×). Les visuels
  violaient la règle que la charte énonce dans sa propre section Polices.
- **Polices** : woff2 remplacés par des TTF. `polices-sapi.zip` (pack combiné) abandonné au
  profit de **deux packs séparés** : `square-peg.zip` + `montserrat.zip`.
- Textes raccourcis à l'essentiel (2129 mots), tirets cadratins retirés partout.
- **Print supprimé** : Robin abandonne la rubrique. L'onglet, la section 09, l'entrée de menu,
  les 2 encarts « manque » disséminés dans Logo et Couleurs, le contexte `print` des 14 sections
  partagées et les 6 règles CSS orphelines (`.gap`, `.gap-t`, `.gap-list`) sont retirés.
  **Garde-fou ajouté** : le contexte est mémorisé en `localStorage`, et `'print'` dort dans le
  navigateur de quiconque a cliqué l'onglet. Sans lui la page s'ouvrait **vide**, sans rien en
  console pour l'expliquer. Il retombe sur `web` si le contexte mémorisé n'existe plus.
- **Nouvelle carte de téléchargement : la palette Affinity** (`download/palette-sapi.clr`,
  18 couleurs nommées, là où l'ancienne en avait 17 anonymes dont 2 fantômes).
  ⚠️ Elle est **générée depuis la constante `GAMMES` d'`index.html`** : ne jamais l'éditer à la
  main, sinon la charte et le nuancier divergent. Le `.clr` du Drive a été remplacé par le même
  fichier (ancien archivé dans `_local/`).
- README remis d'aplomb (il décrivait 12 PNG et des woff2 qui n'existent plus).

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — vérifier qu'il n'y a NI `_local/`, NI `.DS_Store`, NI `.ai`, NI `charte-en-ligne.zip`.
   (contrôlé côté Cowork : c'est propre, mais revérifier avant de committer)
3. `git add -A` — inclut 4 suppressions (`polices-sapi.zip`, les 2 woff2, `sapi-art.jpg`)
4. Commit : « Photos, survol, dérivées en bande, palette Affinity, print retiré »
5. `git push origin main`
6. Suivre le run Actions. En cas d'échec, rapporter le log ici sans retenter en boucle.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` à jour
- `download/square-peg.zip` et `download/montserrat.zip` → **200**
- `download/polices-sapi.zip` → **404 attendu** (le pack combiné est abandonné ; le workflow
  synchronise et doit le retirer du serveur — si il répond encore 200, le signaler)
- `download/fonts/SquarePeg-Regular.woff2` → **404 attendu**
- `img/regle-zone.jpg` et `img/regle-centrage.jpg` → 200 ; `img/regle-2-3.jpg` et `img/sapi-art.jpg` → 404
- `download/palette-sapi.clr` → **200**. Le workflow exclut `*.zip` et ré-inclut `download/*.zip` ;
  `.clr` n'est matché par aucune exclusion, donc il devrait monter. À vérifier quand même : si 404,
  ajouter une exception dans `deploy.yml`.
- **Plus aucun onglet « Print »** dans la barre de contexte : il ne reste que Site web et Réseaux
  sociaux. Et la page ne doit **pas** être blanche même avec `sapi-charte-ctx=print` en localStorage
  (c'est le cas de Robin) : tester en console avec
  `localStorage.setItem('sapi-charte-ctx','print')` puis recharger.
- `/_local/` → toujours 404

**⚠️ Point non vérifié côté Cowork :** le **rendu**. Pas de navigateur dans le bac à sable
(playwright ne peut pas installer Chromium sans sudo). Les contrastes, la largeur des textes,
l'équilibre des accolades et le parse JS sont vérifiés ; **le visuel ne l'est pas**.
Si quelque chose s'affiche de travers après déploiement, c'est là qu'il faut regarder en premier.

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

---

## [✅ FAIT — 2026-07-17] Charte — pousser la refonte des visuels et de la densité

**Date :** 2026-07-17
**Priorité :** normale

### ✅ Résultat Claude Code (2026-07-17)
- **Vérif avant commit** : `.ai` (dans `_local/`) et `.DS_Store` (dans `download/fonts/`) bien **ignorés** ; rien de `_local/` (ni `.bak.html`) stagé. 26 fichiers stagés (conforme), dont les 4 suppressions génériques. ✅
- **Commit** `5187b4a` « Photos de Robin, compositions réseaux, densité en 8 cartes », `git push origin main` → OK (un seul commit cette fois, pas de modif concurrente de Cowork).
- **Déploiement GHA** confirmé (fichier live re-daté ~20 s après le push). Critères testés (cert SSL valide) :
  - `/` → 200, **identique** à l'`index.html` poussé ✅
  - les **8 photos par modèle** (`packshot-leon`, `packshot-merveilleuse`, `detail-claudine`, `vue-dessous-olivia`, `vue-dessous-gaston`, `situation-sebastien`, `situation-alice`, `situation-charlie`) → **200** ✅
  - les **8 compositions réseaux** `img/compo-*.jpg` référencées et servies → **200** ✅
  - `img/regle-centrage.jpg` + `img/regle-zone.jpg` → 200 ✅
  - anciennes génériques (`packshot.jpg`, `detail.jpg`, `situation.jpg`, `vue-dessous.jpg`) → **404** (retirées par la synchro FTP) ✅
  - `_local/` → **404** ✅
- **Section Densité** : `.m-edito` (éditoriale) et `.m-split` (scindée) présentes dans le HTML live → 8 cartes en place ✅
- **Intégrité liens** : tous les assets référencés dans l'`index.html` live répondent **200** — aucun lien cassé.
- **Perf** : somme des photos référencées ~3,4 Mo (conforme à l'estimation Cowork ~3,6 Mo). Si la page traîne, l'option « redescendre 1600→1200px » reste en réserve.
- **⚠️ Rendu visuel non vérifié** (pas de navigateur dans le bac à sable) : hauteurs `.m-edito`/`.m-split` **estimées** → surveiller un éventuel débordement clippé en silence par `overflow:hidden` ; contraste des 3 critères photo sur aplat orange (3,63:1, sous le seuil 4,5 — réserve assumée par Robin). À valider à l'œil par Robin.

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`, branche `main`). Ne rien toucher dans `theme-atelier-sapi`.

**Contexte :** Robin a validé (« c'est parfait, on pousse en prod »). **26 fichiers** en attente
depuis `88eb2be`. Tout est écrit et contrôlé côté Cowork, il ne reste qu'à committer et pousser.

**Ce qui change :**
- **8 photos fournies par Robin remplacent les anciennes**, nommées **par modèle** :
  `packshot-leon`, `packshot-merveilleuse`, `detail-claudine`, `vue-dessous-olivia`,
  `vue-dessous-gaston`, `situation-sebastien`, `situation-alice`, `situation-charlie`.
  Les 4 génériques (`packshot.jpg`, `detail.jpg`, `situation.jpg`, `vue-dessous.jpg`) sont supprimées.
- **Les 4 catégories de visuels passent en carré.**
- **8 compositions réseaux publiées** ajoutées en galerie dans Formats (`img/compo-*.jpg`).
  35 Mo compressés à 601 Ko.
- **Les 5 images fabriquées régénérées** (`regle-zone`, `regle-centrage`, `overlay-noir/blanc/faux`)
  avec une recette unique : courbe `(1-(1-t)³)²`, rampe pure sans palier, dégradé qui s'arrête au
  luminaire, nom de produit en une seule couleur.
- **Carrousel de densité : 5 → 8 cartes.** Deux mises en page nouvelles (`.m-edito` éditorial à
  vignette, `.m-split` texte/photo 50-50) et deux cas nouveaux (la primaire en aplat sombre,
  l'orange répété quatre fois). Données produit tirées du **catalogue réel** (Olivia Ø700/750 g/120 €,
  Claudine Ø650/135 €) — aucun chiffre inventé.
- **Section Formats refondue** : grille 3 colonnes, Pinterest réuni en une carte pleine largeur avec
  ses 3 ratios, sous-titre supprimé, règle « Le centrage » ajoutée.
- **Les 3 critères photo en aplat orange** (choix de Robin, voir la réserve).
- **Infractions de la charte contre elle-même, corrigées** : 2 overlays pleins cadres qui mangeaient
  le luminaire dans les mockups, 6 noms de produit ayant perdu leurs deux polices, 1 prix en
  monospace (3e police interdite).
- **Le hero était illisible depuis le premier jour.** « ATELIER SÂPI » était en `--wood` sur le fond
  sombre : **1,36:1**. Le bois est un ton moyen (luminance 0,21), il ne peut porter aucun petit texte
  sur ce fond — même sans la photo il plafonnerait à 3,13. Passé en crème à 90% → **4,68:1**.
  La date en bas à droite était à **2,84:1** (opacité 50%) → passée à 85% → **4,75:1**.
  ⚠️ Conséquence assumée : le hero perd son accent bois.

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — vérifier qu'il n'y a NI `_local/`, NI `.DS_Store`, NI `.ai`.
   ⚠️ `_local/` contient des `.bak.html` et des PNG de travail : ils doivent rester gitignorés.
3. `git add -A` — inclut 4 suppressions (les photos génériques)
4. Commit : « Photos de Robin, compositions réseaux, densité en 8 cartes »
5. `git push origin main`
6. Suivre le run Actions. En cas d'échec, rapporter le log ici sans retenter en boucle.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` à jour
- `img/packshot-leon.jpg` et `img/compo-achetez-lampadaire.jpg` → **200**
- `img/packshot.jpg`, `img/detail.jpg`, `img/situation.jpg`, `img/vue-dessous.jpg` → **404 attendu**
  (le workflow synchronise et doit les retirer du serveur ; s'ils répondent encore 200, le signaler)
- Section Densité : **8 cartes**, dont une éditoriale et une scindée
- Section Formats : la galerie des 8 compositions s'affiche sous l'onglet **Réseaux sociaux**

**⚠️ Non vérifié côté Cowork — à regarder en premier si ça casse :**
- **Le rendu.** Pas de navigateur dans le bac à sable (playwright ne peut pas installer Chromium sans
  sudo). Contrastes, largeurs de texte, accolades, parse JS et intégrité des liens sont vérifiés ;
  **le visuel ne l'est pas**. Les hauteurs de `.m-edito` et `.m-split` sont **estimées** (marges de
  12 à 168px selon les cartes). Si une carte déborde, c'est là — un débordement de 42px est déjà
  passé inaperçu aujourd'hui parce que `overflow:hidden` clippe en silence.
- **`img/` pèse 3,6 Mo.** Si la page traîne, redescendre les photos de 1600 à 1200px.

**Réserve assumée par Robin :** les 3 critères photo sont en aplat `#E35B24`. Le corps de texte y est
à **3,63:1 pour un seuil de 4,5** — aucune couleur de texte ne passe sur cet orange. Le titre (19px
gras) et le chiffre passent, au seuil « grand texte » de 3,0. Robin a tranché en connaissance de
cause. Même situation sur tous les boutons blanc-sur-orange des maquettes.

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

</details>

---

## [✅ FAIT — 2026-07-17] Charte — corriger le hero, illisible depuis le premier jour

**Date :** 2026-07-17
**Priorité :** normale — **2 lignes de CSS, rien d'autre**

### ✅ Résultat Claude Code (2026-07-17)
- **Diff conforme** : un seul fichier (`index.html`), 2 lignes dans le `<style>`, rien d'autre.
  - `.hero .eyebrow` : `color:var(--wood)` → `color:var(--creme);opacity:.9` ✅
  - `.hero .date` : `opacity:.5` → `opacity:.85` ✅
- **Commit** `c907c5e` « Hero lisible : ourlet et date remontés au-dessus de 4,5:1 », `git push origin main` → OK.
- **Déploiement GHA** confirmé (fichier live re-daté ~10 s après le push). Les 2 règles corrigées sont **présentes telles quelles dans le HTML live** ; page live == `index.html` poussé ; arbre propre, HEAD=origin/main.
- **⚠️ Rendu non vérifié à l'œil** (pas de navigateur) : les valeurs de contraste (4,68:1 / 4,75:1) sont celles calculées par Cowork ; à confirmer visuellement par Robin que « ATELIER SÂPI » (crème, plus bois) et la date sont bien lisibles.
- 📝 Observation hors-scope (non touchée) : `.hero .date` reste en `font-family:ui-monospace…` (une police monospace) — la charte interdit une 3ᵉ police. Pas dans le périmètre de cette tâche ; à signaler si tu veux qu'on l'aligne plus tard.

<details><summary>Consigne d'origine (archivée)</summary>

**⚠️ Dépôt séparé, PAS le thème :** `~/Atelier Sapi Claude Cowork/business/docs/charte-graphique/`
(dépôt `robinSapi/charte-atelier-sapi`, branche `main`).

**Contexte :** Robin a signalé que « ATELIER SÂPI » était illisible dans l'en-tête. Mesuré : **1,36:1**.
Un second échec a été trouvé au passage, qu'il n'avait pas signalé : la date en bas à droite, à
**2,84:1**. Seul `index.html` a changé depuis `5187b4a`, et uniquement dans le `<style>`.

**Ce qui change :**
- `.hero .eyebrow` : `color:var(--wood)` → `color:var(--creme);opacity:.9` → **1,36 → 4,68:1**
- `.hero .date` : `opacity:.5` → `opacity:.85` → **2,84 → 4,75:1**

**Pourquoi le bois ne pouvait pas rester :** sa luminance (0,21) est trop proche de celle du hero
(0,15 — `#323232` recouvert de la photo à 28%). Même en retirant la photo, il plafonnerait à **3,13**
sur le noir. Ce n'était pas un réglage à ajuster : le bois est un ton moyen, il ne porte aucun petit
texte sur fond sombre. **Conséquence assumée par Robin : le hero perd son accent bois.**

**À faire :**
1. `cd business/docs/charte-graphique/`
2. `git status` — un seul fichier modifié, `index.html`
3. `git add index.html`
4. Commit : « Hero lisible : ourlet et date remontés au-dessus de 4,5:1 »
5. `git push origin main`
6. Suivre le run Actions.

**Critères de succès :**
- `https://charte.atelier-sapi.fr` : « ATELIER SÂPI » lisible dans l'en-tête, en crème et non en bois
- La date en bas à droite du hero est lisible

**Rappel :** `main` de ce dépôt est directement en ligne. Le push publie.

</details>

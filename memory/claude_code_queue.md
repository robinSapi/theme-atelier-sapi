# Tasks — Coordination Cowork ↔ Claude Code

> Historique des tâches terminées archivé dans `claude_code_queue_archive.md` (nettoyé le 2026-06-03).

> **REFONTE FILTRAGE CONSEILLER — décisions d'architecture (11/06/2026).** Les tâches ci-dessous REMPLACENT les anciennes (qui supposaient un filtrage en double PHP/JS, désormais périmé).
> **Cap :** filtrage 100% côté serveur (PHP), un seul cerveau, **suppression du filtrage JavaScript** (le JS ne fait plus qu'afficher). Le filtre serveur est appelé à **2 moments** : (1) au chargement de `/mes-creations/` avec une pièce, (2) à la fermeture de la modale (questionnaire terminé OU abandonné en cours). L'IA (Sonnet) n'ajoute qu'un **commentaire** en fin de questionnaire.
> **Source de vérité du comportement voulu :** `assets/guide-filtrage-simulateur.html` (simulateur jouable + éditeur de règles, à ouvrir). Doc d'appui : `assets/guide-filtrage-impact.html`.
> **Toutes les tâches :** branche test uniquement, jamais master, Robin valide avant prod.

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

**Critères de succès :** arrivée sans pièce → room-picker ; choisir une pièce → sélection serveur au niveau pièce ; aucune régression depuis la home.

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

**✅ TÂCHE 5 CODÉE + SUR TEST (en attente validation Robin).** Fichier `inc/conseiller-rules-admin.php` (require sous `is_admin()`). 5.1 socle DB (sapi_conseiller_default_rules + get_rules merge option + apply_filters) ; 5.2/5.3 page sous-menu « Règles de filtrage » (priorité 11), formulaire schema-driven, sauvegarde admin-post (nonce+cap), sanitization whitelist, garde-fous, reset ; 5.4 aperçu live via endpoint `sapi_admin_filter_preview` (règles draft injectées par filtre → vrai moteur). Libellés clarifiés (éclairage principal/appoint). Pas de binaire PHP local → vérifié par équilibrage accolades ; blast radius = admin seul.

## [TÂCHE 6] Règle IA — suspension principale en grande pièce
**Priorité : basse.** Éditer `assets/guide-prompt-regles.txt` : ajouter une règle pour que, quand une suspension est proposée comme éclairage **principal** dans une **grande pièce**, l'IA avertisse honnêtement qu'un seul luminaire peut ne pas suffire et suggère un complément (lampadaire, applique) ou un ensemble sur-mesure. (Le savoir + l'exemple existent déjà mais restent suggestifs ; une règle explicite rend l'avertissement fiable.)

## [TÂCHE 7] Nettoyage legacy (quand le nouveau flux est en prod)
**Priorité : basse, en dernier.** Une fois la refonte stable et validée : retirer le quiz V1 mort (`sapi_ajax_guide_results` + `sapi_guide_build_system_prompt`, plus appelé par aucun JS) et le filtrage JS s'il est entièrement remplacé. À faire prudemment, en vérifiant qu'aucun appel ne subsiste.
**⚠️ NE PAS supprimer le code « grappe »** (`diversify_format` dans `sapi_guide_pick_four`) : il est orphelin mais c'est une idée à conserver et à réactiver plus tard — voir l'idée ci-dessous.

## [IDÉE — à explorer plus tard] Grappe / multi-ampoules comme rampe vers le sur-mesure
Le mode « grappe » (montrer un produit de chaque format = de la diversité, + afficher la carte sur-mesure) est aujourd'hui orphelin (l'option a été retirée du questionnaire). Robin veut le **garder** : il doit servir de **support pour orienter vite le visiteur vers le sur-mesure**. L'objectif : que l'IA / le parcours puisse proposer rapidement une composition multi-ampoules ou un ensemble sur-mesure quand c'est pertinent, et le mécanisme « un de chaque format » est un bon véhicule pour donner à imaginer. À recâbler dans le nouveau système (porte d'entrée à définir) le moment venu. Le code `diversify_format` dans `sapi_guide_pick_four` est conservé exprès pour ça.

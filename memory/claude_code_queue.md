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

## [TÂCHE 5] Page admin WordPress — piloter les règles de filtrage (proposition 2)
**Priorité : normale — APRÈS tâches 1-3 (le filtre serveur et ses règles doivent exister et être centralisés).**
**Prérequis :** toutes les règles dans une config unique persistable (cf. tâche 1). Aujourd'hui certaines sont en dur → il faut d'abord les rassembler et les stocker (options WordPress / DB) pour qu'une page puisse les éditer.

Objectif : une page dans l'admin WordPress (comme le dashboard de stats du Conseiller) où Robin édite lui-même les règles, et ça s'applique au site en direct, sans repasser par du code :
- catégories par sortie ; ampoules acceptées + préférée par pièce ; règles de format ; priorités (ampoule/catégorie/format) + ordre d'importance ; exclusions par pièce (cuisine).
- Le filtre serveur (tâche 1) lit ces réglages depuis la DB au lieu de valeurs en dur.

**Le simulateur `assets/guide-filtrage-simulateur.html` EST la maquette de cette page** (mêmes réglages, même organisation) → s'en servir comme cahier des charges UI.

**Demander un plan avant de coder.** Soigner : ne pas laisser créer des combinaisons incohérentes (ex. retirer la catégorie qu'impose une sortie). Prévoir un « réinitialiser aux valeurs par défaut ».

**Critères de succès :** Robin modifie une règle dans l'admin → effet immédiat sur le filtrage du site, sans toucher au code.

## [TÂCHE 6] Règle IA — suspension principale en grande pièce
**Priorité : basse.** Éditer `assets/guide-prompt-regles.txt` : ajouter une règle pour que, quand une suspension est proposée comme éclairage **principal** dans une **grande pièce**, l'IA avertisse honnêtement qu'un seul luminaire peut ne pas suffire et suggère un complément (lampadaire, applique) ou un ensemble sur-mesure. (Le savoir + l'exemple existent déjà mais restent suggestifs ; une règle explicite rend l'avertissement fiable.)

## [TÂCHE 7] Nettoyage legacy (quand le nouveau flux est en prod)
**Priorité : basse, en dernier.** Une fois la refonte stable et validée : retirer le quiz V1 mort (`sapi_ajax_guide_results` + `sapi_guide_build_system_prompt`, plus appelé par aucun JS) et le filtrage JS s'il est entièrement remplacé. À faire prudemment, en vérifiant qu'aucun appel ne subsiste.
**⚠️ NE PAS supprimer le code « grappe »** (`diversify_format` dans `sapi_guide_pick_four`) : il est orphelin mais c'est une idée à conserver et à réactiver plus tard — voir l'idée ci-dessous.

## [IDÉE — à explorer plus tard] Grappe / multi-ampoules comme rampe vers le sur-mesure
Le mode « grappe » (montrer un produit de chaque format = de la diversité, + afficher la carte sur-mesure) est aujourd'hui orphelin (l'option a été retirée du questionnaire). Robin veut le **garder** : il doit servir de **support pour orienter vite le visiteur vers le sur-mesure**. L'objectif : que l'IA / le parcours puisse proposer rapidement une composition multi-ampoules ou un ensemble sur-mesure quand c'est pertinent, et le mécanisme « un de chaque format » est un bon véhicule pour donner à imaginer. À recâbler dans le nouveau système (porte d'entrée à définir) le moment venu. Le code `diversify_format` dans `sapi_guide_pick_four` est conservé exprès pour ça.

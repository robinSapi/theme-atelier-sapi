# Tasks — Coordination Cowork ↔ Claude Code

> Historique des tâches terminées archivé dans `claude_code_queue_archive.md` (nettoyé le 2026-06-03).

## ✅ EN PROD — GO-LIVE Harmonisation Conseiller (Phases 0→4) (2026-06-10)
**Déployé et vérifié par Robin sur atelier-sapi.fr.** `test-theme-sapi-maison` (31 commits) mergé dans `master` — merge commit `3f9fd11` « Harmonisation Conseiller (Phases 0→4) + refonte modale », puis workflow « Deploy to Production » lancé par Robin.
**Contenu livré :** pills Robin V1 partout (home, Conseils, modale 6 états, fiche produit) ; page Conseils alignée sur la home (Phase 1) ; modale refondue (grain bois sans pointillé, tutoiement gender-correct, hover orange, hauteur +20%, chat encadré + saisie en bas, récap s3 boutons en bas, option neutre pleine largeur) ; avatars en thumbnail ; **fix perf `:has()` qui bloquait /mes-creations/** (commit `0981348`).
**Vérifié OK par Robin en prod.** Reste **Phase 3** (card Robin Mes créations) en attente du brief refonte Mes créations.

## ✅ RETOUR COWORK — Pill « Signature Conseiller » home FAIT sur TEST (2026-06-10)
**Sur test, en attente de validation Robin. PAS en prod.** Branche `test-theme-sapi-maison`, déployé sur test.atelier-sapi.fr. 3 itérations : V1 trop grosse (`99c6903`) → mini B1 (`595874f`) → **format final `76a468f`**.
- **Format final :** la signature de la **home** (section Conseiller / room-picker) = mini capsule bois sombre avec **photo ronde sans contour + accroche Square Peg blanche 24px**. **« Le conseil de Robin » retiré** (l'identité passe par la photo). Texte d'accroche et photo inchangés. Rendu mobile géré (≤600px).
- **Scopé à la home uniquement** : la signature dans la **modale Conseiller** et la pill **fiche produit** ne bougent pas (réservées à l'étape suivante = généralisation, à lancer si Robin valide la V1).
- ⚠️ **Coordination 2 fenêtres :** ce chantier tournait **en parallèle** de la « suppression bento legacy » (autre fenêtre Claude Code) qui éditait le **même `style.css`** → ma 1re passe a été écrasée. Refaite proprement **après** que le bento soit passé en prod. Les deux travaux sont bien séparés, aucun mélange.

**👉 Action Robin :** regarder la pill sombre sur la home test (desktop + mobile). Si OK → me dire « go généralisation » (modale tous états + pill fiche produit + page conseils, même V1, lignes du bas contextuelles). Puis, quand tout est validé, go-live (merge master + prod manuel).

---

## ✅ RETOUR COWORK — Suppression bento legacy (CSS + JS) EN PROD (2026-06-10)
**Fait par l'autre fenêtre Claude Code, validé Robin, déjà en prod (atelier-sapi.fr).** Le vieux système « bento CINÉTIQUE » (CSS mort + code JS mort dans `cinetique.js`) a été retiré. `.hero-bento` et `.bento-bestseller-badge` (Star du moment) conservés. Pour mémoire Cowork : c'est la tâche « suppression du JS » dont Robin parlait.

---

## ✅ RETOUR COWORK — GO-LIVE refonte home FAIT + 10 retouches photos (2026-06-10, EN PROD)
**Refonte home mergée dans `master` et déployée en prod (atelier-sapi.fr).** Merge commit `1c3c642` « Refonte home juin 2026 » (129 commits de `feature/refonte-home`). Audit de branches fait avant merge : `master` était **== `test-theme-sapi-maison`** (aucune divergence test↔master, **pas de méga-filtre / prompts IA / backend Conseiller** embarqués). Après go-live, les deux branches test (`test-theme-sapi-maison` + `feature/refonte-home`) ont été **réalignées sur master**.

**Retouches photos/logos faites directement sur master (à la demande de Robin, EN PROD) :**
- Card **Sur mesure** : photo **ET** sous-titre désormais **pilotés par la catégorie produit `creations-sur-mesure`** — image via ACF `image_collection` → 3ᵉ photo ambiance produit → vignette ; sous-titre via la **description WooCommerce** de la catégorie ; repli en dur (`2026/04/Photo-Trio-de-34.jpg` / « Une pièce unique… ») si vide. **Plus rien en dur à changer dans le code → tout se pilote en admin** (Produits → Catégories → Créations sur mesure).
- Section **L'atelier** : fond par défaut → `2026/05/Claudine-La-turbine-Suspensions-Detail-1.jpg` (variable `$atelier_default_img`).
- Photo **Assemblage** (étape 04) : affichée en **miroir horizontal** (CSS `scaleX(-1)` sur `data-key="4"`).
- Bande **presse** : fix logo **Région Auvergne-Rhône-Alpes** (ancien fichier 404 + version blanche invisible → PNG couleur) + **tous les logos harmonisés à la même hauteur (46px, width auto, object-fit contain)**.

**⚠️ Actions restantes côté Robin → voir la section « À faire » plus bas** (vérifs prod, sitemap GSC, séquence Brevo, passe Yoast).

<details><summary>Ancien retour (2026-06-09, quand la refonte était encore sur test)</summary>

## ↩️ RETOUR COWORK — Refonte home : DA + passe mobile TERMINÉES (2026-06-09, sur test)
**Branche `feature/refonte-home`**, tout poussé/déployé sur **test.atelier-sapi.fr**, **jamais mergé master**. Dernier commit `17db187`.

**✅ Fait depuis le dernier point (tout validé par Robin sur test) :**
- **Série DA #1 → #8 complète** (desktop) : Atelier immersion lumière (#1), Hero naming card verre dépoli + dots sous le nom (#2), Conseiller signature de Robin + réchauffe (#3), Collections scrim allégé + carte « Sur mesure » + voile Star (#4), Avis papiers à ombre douce + grain bois (#5), Carte localisation mini-carte bois SVG (#6), Cadeau+Actus objet orange vs journal (#7), Newsletter bande bois chaud (#8).
- **Correctif réassurance** : wording humain single-line (Livraison rapide 48-72h / Façonné main à Lyon sous 5 jours / 30 jours pour changer d'avis / Paiement sécurisé). Mobile = 2 items (Livraison + Paiement).
- **Généralisation signature Robin** : modale Conseiller (états accueil + chat) + pill « Comment choisir ? » fiche produit (mini-avatar).
- **Vague de retouches Robin** (cosmétique) : numérotation sections 01→04 (plus de saut), espaces header→contenu harmonisés (2,5rem ; atelier 5rem), flèches Collections à gauche, hero carrousel = 8 produits, fix saut scroll-snap Collections (proximity→none desktop), hover Star, photo fond citation (`2026/06/Robin-Shooting.jpg`), pastille Conseiller (`2026/03/Robin-face-avec-Alice-lhelice.jpg`, zoom), card cadeau = photo `Carte-de-visite-3` + voile sombre, textes/paddings divers, 2e § atelier réécrit, CTA « Voir toutes les créations », etc.
- **PASSE MOBILE complète** : marge latérale 14px commune à toutes les sections-cards ; room-picker chips compactes (icône gauche + texte) ; titres catégories −40 % / sous-titres −30 % ; Collections cards −20 % ; **« Créations du moment » = star inchangée + 2 modèles en slider horizontal avec dots (scroll-dots.js), mini-cards photo 190px, prix masqué** ; avis carrousel ombres OK ; newsletter marge ; menu burger compacté (items 18px). Fix focal point : déjà géré par le plugin Media Focus Point sur toutes les images `wp_get_attachment_image` (décision « on ne touche à rien de plus »).

**⚠️ À SAVOIR :**
- Images `2026/03` + `2026/06` (atelier, expédition, Robin shooting, carte-de-visite) : **404 sur test (clone d'avril) / 200 en prod** → OK une fois en prod.
- Le point focal des images = **plugin Media Focus Point** (pas le thème) ; s'applique auto sur tout `wp_get_attachment_image()`.

**RESTE avant prod (dans l'ordre) :**
1. **Passe Yoast** — titre + meta description de la home (à faire, ou Claude Code prépare une proposition).
2. **Tester** une inscription newsletter + le form « Échanger avec Robin » fiche produit (Brevo) sur test.
3. **Go-live** : merge `feature/refonte-home` → `master` (sur « go » explicite Robin) → Robin lance le workflow GitHub Actions → prod → **re-soumission sitemap à Google Search Console**.
4. **Brevo (Robin)** : maj séquence d'accueil −10 % pour inclure `surmesure` + `ficheproduit`.
5. (optionnel) nettoyage CSS mort résiduel (`.bento-*`, `.process-*`).

</details>

## ✅ EN PROD (déploiement #124, commit `38184ae` — 2026-06-03)
Lot complet Brevo/disclaimers live sur atelier-sapi.fr : étiquetage `SOURCE` des portes #6, footer home réparé, `sapi_ajax_robin_contact` réparé, modale Conseiller + page /sur-mesure/ → #6 + disclaimers, fiche produit → disclaimer, suppression du form inline mort + JS.

## ✅ EN PROD (déploiement #125, commit `c9e07cd` — 2026-06-03)
- Source dédiée **`surmesure`** pour le form de la page /sur-mesure/ (`sapi_handle_surmesure_form`) — commit `27a89d4`.
- Source dédiée **`ficheproduit`** pour le form « Échanger avec Robin » des fiches produit (`sapi_ajax_robin_contact`) — commit `c9e07cd`.

⚠️ **Reste côté Brevo (Robin)** : les sources prospects sont maintenant `homepage`, `popupBienvenue`, `inspiration`, `conseiller`, `surmesure`, `ficheproduit` (+ `checkout` pour les acheteurs). Mettre à jour les conditions de la séquence d'accueil −10 % pour inclure les nouvelles valeurs `surmesure` et `ficheproduit` (avant elles étaient toutes en `conseiller`).

## ✅ [FAIT 2026-06-08 — sur test] Généralisation design « signature Robin » (chantier suivant DA #3) (commit `d659fe8`)
Le composant `.conseiller-sig` (pastille Robin + « Le conseil de Robin » + accroche Square Peg) est étendu :
- **Modale Conseiller** : signature injectée en en-tête de **S0 (accueil/choix pièce)** et **S2 (chat libre)** seulement (choix Robin) — markup statique PHP dans le shell `functions.php` (avatar `sapi_image 2026/03/Robin-face-avec-Alice-lhelice.jpg`). Badge contextuel **masqué en CSS** sur S0+S2 (élément gardé dans le DOM → hook JS `data-s0-badge-text` intact). États S1/S3/récap/contact inchangés. MQ mobile ≤600px : avatar 52px, accroche 24px.
- **Fiche produit** : pill « Comment choisir ? » (`.conseiller-pill-secondary`) — SVG crayon remplacé par un **mini-avatar rond Robin** (26px). `data-action`/`data-help-pill`/`data-help-pill-text` conservés (`sapi-help-pill.js` intact). Encart « Échanger avec Robin » NON touché (hors périmètre).
- **Aucun changement JS.** CSS 3825/3825, PHP 891/891 + 245/245.
**👉 Robin :** valider sur test (ouvrir le Conseiller → S0 + chat ; fiche produit variable → pill avec avatar). Reste pour la généralisation complète si voulu : room-picker page /conseils-éclairés/, encart contact fiche produit.

---

## 🔧 À faire

## [TÂCHE] Refonte /mes-creations/ — état B « arrivée depuis le room-picker » (expérience immersive)
**Date :** 2026-06-10 · **Priorité :** haute · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. Master/prod = SEULEMENT après validation Robin sur test.
**⚠️ Gros chantier qui touche au Conseiller** → **commence par un court PLAN d'implémentation (archi)**, écris-le dans ce queue, attends le feu vert de Robin via Cowork, PUIS code par étapes sur test. Ne pars pas bille en tête.

**Référence visuelle + interaction (source de vérité) :** `mockups/mockup-mes-creations-etat-B.html` (prototype validé par Robin : séquence, flou, slider, header, bandeau). **Audit de l'existant :** `mockups/AUDIT-MES-CREATIONS.md` (structure actuelle + hooks à préserver). Template concerné : `woocommerce/archive-product.php` + `assets/sapi-cards-conseiller.js` + `assets/sapi-project.js`.

### Le postulat (Robin)
Quasi personne n'arrive sur /mes-creations/ autrement que par le **room-picker** (sinon on atterrit sur une page catégorie). Donc on **personnalise** la page selon la pièce choisie. **Deux états :**

**État A — sans projet : NE RIEN CHANGER.** Hero croquis actuel + carte « Conseil de Robin » (room-picker) + catalogue. On laisse tel quel pour l'instant (l'harmonisation pill V1 de cette carte = un autre sujet, Phase 3, hors de cette tâche).

**État B — avec projet (= arrivée depuis le room-picker / projet connu) : la nouvelle expérience immersive** décrite ci-dessous, qui remplace le haut de page (hero + cartes Conseiller) ; le **catalogue « Toutes mes créations » reste en dessous**, inchangé.

### Détection A vs B (à arbitrer dans ton plan)
Le room-picker home pointe déjà vers `/mes-creations/?piece=<slug>`. Donc la pièce peut être connue **côté serveur** via le param `?piece=` → permet de rendre l'état B en PHP (photo + phrase + sélection filtrée). Un visiteur de retour avec un projet en `localStorage.sapiProject` (sans param) doit aussi avoir l'état B → bascule **côté JS**. **Propose l'archi** : rendu PHP quand `?piece=` est là + activation JS quand le projet vient du localStorage ; réconcilie avec `sapi-project.js` (structure `sapiProject`) et la logique actuelle des cartes `conseil` / `mon-projet` (qu'on remplace/réutilise).

### La séquence état B (cf. mockup)
1. **Plein écran sur la photo de la pièce choisie**, **sans header** au départ. Photo = champ ACF **`hero_<slug>`** de la page boutique (déjà exposé, cf. `archive-product.php` `$hero_photos_by_piece` / `data-piece-photos`).
2. **Pill Robin V1** + la **phrase de conseil propre à la pièce qui s'écrit** (machine à écrire). ⚠️ **La phrase ne se réécrit JAMAIS** (pas d'aller-retour IA → instantané). Source = le **conseil générique par pièce** déjà en PHP (`sapi_megafilter_get_generic_advices()` / `genericAdvice`), pas l'IA live.
3. Une fois la phrase écrite : la **question d'affinage** apparaît, puis le bouton primaire **« Voir ma sélection pour toi »**, puis le secondaire **« Voir toutes les créations »**, puis **le header se révèle en fondu** + le **bandeau réassurance** apparaît.
4. **Questions inline = `taille` puis `style` UNIQUEMENT** (la pièce est déjà connue). C'est le strict nécessaire pour cibler la **variation**. Le reste du questionnaire (sortie/hauteur/table…) reste réservé à **la modale**. Répondre à une chip = la valide (état sélectionné, cliquable pour changer) → **la question suivante prend la place** ; **la sélection ne s'affiche PAS** à ce moment, et la phrase ne se réécrit pas.
5. **La sélection se révèle** dans 2 cas seulement : clic sur **« Voir ma sélection pour toi »** (à tout moment), OU **après que les 3 questions soient répondues** (pièce + taille + style). Effet : **l'image plein écran se floute**, la **phrase remonte se caler sous le header** (devient le titre de la séquence), et un **slider horizontal des produits filtrés** apparaît dans l'espace libéré, par-dessus la pièce floutée. Chaque carte produit porte une **courte phrase de Robin** + nom formaté (prénom caps + surnom Square Peg) + prix ; **carte sur-mesure** en fin de slider.
6. **« Voir toutes les créations »** = **scrolle** vers le catalogue classique en dessous (inchangé : pills + grille).
7. Une fois les questions finies, un discret **« Préciser avec Robin »** ouvre **la modale** (questionnaire complet → sélection idéale, sans réécrire l'IA).

### Sélection des produits = en PHP
Robin veut le matching **calculé côté serveur** (propre, SEO), pas le clone JS actuel. **Réutilise les règles de filtre déjà en PHP** (`$sapi_filter_rules`, et la logique de `cardMatchesAnswers` de `sapi-cards-conseiller.js` à porter/мirror en PHP si pas déjà dispo serveur). À l'arrivée `?piece=salon`, la sélection est filtrée par pièce ; quand `taille`/`style` sont connus (localStorage), elle s'affine. **Propose dans ton plan** : tout PHP au load + re-filtrage JS au fil des réponses, ou rendu PHP via petit endpoint. Garder l'expérience instantanée (pas d'attente IA).

### Header + bandeau réassurance = comportement de la home
- **Header** : légèrement **transparent** (léger flou, texte clair) tant qu'il est **sur la photo**, puis **blanc opaque** (texte foncé) une fois scrollé. **Réutilise le comportement déjà en place sur la home** s'il existe (ne pas réinventer).
- **Bandeau réassurance** : **collé en bas de la photo plein écran** au départ (translucide), puis **sticky sous le header** une fois scrollé (blanc opaque). Même bandeau / wording que la home (Livraison 48-72h · Façonné main à Lyon sous 5 jours · 30 jours pour changer d'avis · Paiement sécurisé). Bascule au scroll (~mi-photo dans le proto, à régler).

### Garde-fous / à préserver
- **Tutoiement** partout, **pas de tiret cadratin**, accolades équilibrées.
- **Ne casse aucun `data-*` ni hook JS** listés dans `AUDIT-MES-CREATIONS.md §3` (zone conseiller, room-picker, mon-projet, sélection, catalogue, cartes produit `.product-card-cinetique` + tous leurs `data-*`, `data-piece-swap`, pills `data-cat`, modale `#robin-product-pill` etc.).
- **Catalogue bas inchangé** ; on ne touche pas à l'état A.
- Le **product-name-formatter** doit s'appliquer aux cartes du slider comme ailleurs.
- Mobile : prévoir le rendu (photo plein écran, slider scrollable, bandeau qui masque des items si besoin — cf. proto).

### Plan attendu (avant de coder)
Réponds dans ce queue avec : (a) détection A/B retenue (param `?piece=` + localStorage) et où vit le markup état B ; (b) source de la sélection en PHP (fonction réutilisée/créée) ; (c) réutilisation vs remplacement des cartes `conseil`/`mon-projet` actuelles ; (d) réutilisation du header/bandeau de la home ; (e) découpage en sous-étapes livrables sur test. **Puis attends le go de Robin.**

### 👉 Action Robin
Quand Claude Code aura posté son plan d'archi, le valider (ou ajuster) avant qu'il code. Référence à regarder en attendant : le prototype `mockups/mockup-mes-creations-etat-B.html`.

---

## 📐 RÉPONSE CLAUDE CODE — PLAN D'ARCHI état B (2026-06-10) · **EN ATTENTE DU GO DE ROBIN, RIEN DE CODÉ**

> Audit du code fait (archive-product.php + sapi-cards-conseiller.js + sapi-project.js + functions.php + header). **Bonne nouvelle :** la « sélection calculée côté serveur » que Robin veut **existe déjà** en PHP — la version JS actuelle (`cardMatchesAnswers`) n'en est qu'un miroir. Donc l'état B s'appuie sur de la mécanique éprouvée ; le gros du chantier = l'expérience (séquence hero + flou + slider), pas le matching.

### (a) Détection A vs B + où vit le markup état B
- **Source de vérité de l'état = `localStorage.sapiProject`** (déjà géré par `sapi-project.js`). `?piece=salon` n'est qu'une **graine** : au chargement, `sapi-project.js` (`ingestQueryParams()`) l'ingère déjà dans `sapiProject` et nettoie l'URL. Donc « arrivée room-picker » et « retour avec projet » convergent vers le **même signal** : `sapiProject.hasProject()`.
- **Rendu hybride, sans flash :**
  1. **PHP lit `?piece=` AU LOAD** (nouveau, ~10 lignes dans `archive-product.php`) → valide contre la whitelist (`sapi_megafilter_filters_whitelist()['piece']`) → si présent, **rend l'état B pré-peuplé côté serveur** (photo pièce, phrase générique de la pièce, slider de produits déjà filtrés) **et pose une classe `is-state-b` sur `<body>`/section** pour que le CSS cache l'état A immédiatement (zéro flash, bon pour le SEO : le contenu est dans le HTML).
  2. **Sans `?piece=` mais projet en localStorage** (visiteur de retour) : l'état B est **activé/hydraté par JS** au DOMContentLoaded (même markup, rempli/màj depuis `sapiProject`). Léger flash possible côté retour ; acceptable (cas minoritaire) ou masquable via une pré-classe lue très tôt.
  3. **Ni l'un ni l'autre** = **état A inchangé** (hero croquis + carte « Conseil de Robin » + room-picker), strictement comme aujourd'hui.
- **Où vit le markup état B :** un **nouveau bloc PHP dans `archive-product.php`**, en tête de page, **frère** des cartes actuelles (il ne remplace pas leur DOM — voir (c)). Il porte ses propres hooks (`data-immersion-*`). Le **catalogue bas reste la source des cartes** clonées dans le slider (comme aujourd'hui le fait `populateSelectionGrid`).

### (b) Source de la sélection = PHP **déjà existant** (réutilisé, pas recréé)
- `sapi_guide_get_categories($answers)` → catégories admissibles ; `sapi_guide_query_products($answers, $cats)` → `WP_Query` filtré ; `sapi_guide_collect_results($query, $answers)` → produits + **meilleure variation** (essence selon `style`, taille selon `taille`), prix, image, permalink. **C'est exactement le matching que Robin veut côté serveur.**
- À l'arrivée `?piece=salon` (taille/style encore inconnus) : on appelle ces fonctions avec `{piece}` seul → **sélection filtrée par pièce**, rendue en PHP dans le slider.
- Quand `taille`/`style` arrivent (réponses inline, côté JS) : **re-filtrage instantané sans IA**. Deux options à trancher (voir §Décisions) : **(b1)** re-render via un petit endpoint AJAX `wp_ajax_sapi_mescreations_selection` qui rappelle les mêmes fonctions PHP (100 % cohérent serveur, léger lag réseau), ou **(b2)** tout le catalogue est déjà dans le DOM (cas actuel) → on **clone + filtre côté JS** via `window.sapiMegaFilter.computeFilterMeta()` (déjà dispo, instantané, zéro réseau). **Ma reco : b2** (instantané, déjà éprouvé, le PHP au load couvre le SEO/premier rendu ; le JS n'affine que taille+style sur un volume déjà chargé).
- **Phrase de conseil :** `sapi_megafilter_generic_advice_for($piece)` (texte figé par pièce, **jamais l'IA live**) → conforme à « la phrase ne se réécrit jamais ».

### (c) Cartes `conseil` / `mon-projet` : réutilisation vs remplacement
- **`conseil` (état A) : conservée telle quelle**, on n'y touche pas (Phase 3 = autre sujet).
- **`mon-projet` (état B actuel, la carte englobante) : REMPLACÉE visuellement** par le hero immersif — **mais on garde et réutilise toute sa plomberie** : `window.sapiProject` (get/update/subscribe), `window.sapiMegaFilter`, `genericAdvice`, le `<template data-mes-creations-surmesure-template>` (carte sur-mesure), le clonage depuis `#sapi-product-grid`. **Nouveau contrôleur JS** `assets/sapi-mescreations-immersion.js` pour la **séquence** (machine à écrire, apparition Q → CTA → header/bandeau, flou + remontée + slider). ⚠️ **Différence de comportement clé vs l'existant :** aujourd'hui répondre à une chip inline **ouvre la modale** (`handleChipAnswer` → `sapi:open-modal`). En état B, répondre à une chip **valide inline et passe à la question suivante SANS ouvrir la modale** (cf. mockup). Donc je n'appelle **pas** `handleChipAnswer` ; j'écris la logique inline taille→style dédiée, qui fait juste `sapiProject.update()` + avance. La modale n'est ouverte que par le discret « Préciser avec Robin ».
- **Hooks préservés** (AUDIT-MES-CREATIONS §3) : je ne supprime aucun `data-*` existant ; l'état B ajoute les siens (`data-immersion`, `data-immersion-phrase`, `data-immersion-affine`, `data-immersion-slider`, `data-immersion-refine`…). Le catalogue bas + `.product-card-cinetique` + pills = **intacts**.

### (d) Header + bandeau réassurance de la home
- **Bandeau réassurance : déjà présent sur /mes-creations/** (sortie globale `sapi_robin_bandeau_v2()` via `header.php`, même wording). MAIS le proto veut un comportement spécifique état B (**translucide collé en bas de la photo** → **sticky blanc sous le header** au scroll), différent du bandeau sticky-haut global. **Reco :** réutiliser le **markup/wording** (même partial) mais piloter le **positionnement état B** via une classe (`is-immersion` : bottom translucide → `is-top` au scroll), scopée à cette page. À trancher : réutiliser l'instance globale en la repositionnant, ou rendre une instance dédiée dans le hero (plus propre, pas de risque de régression ailleurs). **Reco : instance dédiée dans le hero**, l'instance globale étant masquée tant qu'on est en état B.
- **Header transparent → opaque :** le mécanisme existe mais est **scopé `.home`** (CSS `.home .site-header` + JS inline dans `front-page.php` qui suit `.homepage-carousel-fullscreen`). **Reco :** généraliser proprement — sélecteur CSS `.site-header.header--over-hero` (au lieu de `.home`) + extraire le scroll-listener dans `assets/header-hero.js` réutilisable, déclenché par un `data-header-hero` sur la section hero. La home garde son rendu (on ajoute la classe là où `.home` l'avait). **Petit risque de régression home** → vérif côte à côte obligatoire. Si Robin préfère ne pas toucher la home, repli : JS dédié état B qui pose `is-scrolled` sans refactorer la home (duplication assumée mais zéro risque).

### (e) Découpage en sous-étapes livrables sur test (chaque étape = commit testable)
1. **Socle PHP état B** : lecture `?piece=` + whitelist + classe d'état ; rendu serveur du hero (photo pièce via `hero_<slug>`, pill V1, phrase générique de la pièce) — **statique, sans séquence** (juste la structure + le contenu correct). État A et catalogue inchangés.
2. **Sélection serveur dans le slider** : brancher `sapi_guide_query_products` + `collect_results` pour rendre les cartes produit filtrées par pièce en PHP + carte sur-mesure. Nom formaté (formatter), prix, « phrase de Robin » par carte.
3. **Séquence d'entrée (JS)** : machine à écrire (instantanée, pas d'IA), apparition question d'affinage → CTA → révélation header + bandeau. Header/bandeau over-photo (point d).
4. **Affinage inline taille→style** (JS) : chips validables, avance question suivante, `sapiProject.update()`, re-filtrage instantané du slider (b2). Pas d'ouverture modale.
5. **Révélation sélection** : « Voir ma sélection pour toi » **ou** fin des 3 réponses → flou photo + remontée phrase sous le header + slider par-dessus. « Voir toutes les créations » → scroll catalogue. « Préciser avec Robin » → modale (sans réécrire l'IA).
6. **Hydratation localStorage** (retour sans param) : activer l'état B côté JS depuis `sapiProject`. **Bascule A/B propre** (param vs localStorage vs rien).
7. **Passe mobile** (photo plein écran, slider scrollable, bandeau qui masque des items, chips) + **garde-fous** : tutoiement, pas de tiret cadratin, accolades, `console = 0 erreur`, tous les `data-*` AUDIT §3 préservés.

### ❓ Décisions à trancher par Robin avant que je code
1. **Re-filtrage taille/style : b2 (clone+filtre JS instantané, ma reco) ou b1 (endpoint AJAX 100 % serveur) ?**
2. **Header over-hero : généraliser le mécanisme home (refacto `.home`→classe, ma reco) ou JS dédié état B sans toucher la home (zéro risque) ?**
3. **Bandeau : instance dédiée dans le hero (ma reco) ou repositionner l'instance globale ?**
4. **Comportement « 0 produit » après affinage** (ex. combinaison rare) : on garde l'**élargissement progressif** existant (`computeEffectiveAnswers`, retire style puis taille…) pour ne jamais montrer un slider vide ? (Ma reco : oui, c'est déjà codé.)
5. **Photo pièce** : si la pièce choisie n'a **pas** de `hero_<slug>` renseigné en ACF → repli (photo générique ? on bascule en état A ?).

### 👉 Action Robin
Lis ce plan + trancher les 5 décisions ci-dessus (ou valider mes recos). Sur « go » → je code étape 1, push test, et on itère étape par étape. Rien n'est touché tant que tu n'as pas validé.

### ✅ Décisions Robin (2026-06-10) + démarrage code
1. **Re-filtrage taille/style → délégué à Claude.** Choix retenu : **serveur (petit AJAX `wp_ajax` qui rappelle `sapi_guide_query_products`)**. Raison : c'est exactement ton ask de départ (« matching calculé côté serveur, pas le clone JS »), et le re-filtrage client partagerait `sapiMegaFilter` avec le catalogue → risquerait de filtrer « Toutes mes créations » (qu'on veut intact). Le serveur gère aussi correctement les **variations** (essence/taille). Latence minime, écran déjà rempli pendant le fetch. → **arrive à l'étape 4**, pas étape 1.
2. **Header over-hero → JS dédié état B, on ne touche PAS la home** (zéro risque de régression).
3. **Bandeau → instance dédiée** dans le hero (wording réutilisé, positionnement état B propre).
4. **Élargissement progressif conservé** : `sapi_guide_query_products` a déjà des fallbacks intégrés (ampoule → format → taille) → jamais de slider vide.
5. **Repli photo générique** si la pièce n'a pas de `hero_<slug>`.

### ✅ [FAIT 2026-06-10 — sur test] ÉTAPE 1 — socle immersion serveur (commit `d8fa9ce`)
**Branche `test-theme-sapi-maison`, poussé sur test.** Tout est **gated sur un `?piece=` valide** → état A + visiteurs normaux **100 % inchangés** (aucun risque).
- **Détection** : `sapi_mescreations_immersion_piece()` valide `?piece=` contre la whitelist des pièces → `body.mescreations-immersion` + flag JS `SAPI_IMMERSION`.
- **Rendu serveur** du hero immersif : photo pièce (`hero_<slug>` + **repli générique** si absente, décision #5), **pill V1**, **phrase générique figée** (jamais l'IA), **sélection PIÈCE-LEVEL calculée côté serveur** (`sapi_guide_query_products`, avec ses fallbacks → jamais vide) dans le slider + **carte sur-mesure**, **bandeau réassurance dédié** (bas de la photo), CTAs, scroll hint.
- **JS séquence** (`sapi-mescreations-immersion.js`) : machine à écrire (instantanée, ne se réécrit jamais), **affinage inline taille→style** (stocke dans `sapiProject`, **sans rouvrir la modale**), **révélation sélection** (flou photo + slider) au clic « Voir ma sélection » OU après les 2 réponses, « Voir toutes les créations » → scroll catalogue, « Préciser avec Robin » → ouvre la modale (s3).
- **Catalogue bas intact** : `sapi-cards-conseiller.js` se met **en retrait** en mode immersion → « Toutes mes créations » garde toutes ses cartes + pills + cards réassurance.
- **product-name-formatter** appliqué aux cartes du slider (prénom caps + surnom Square Peg).

**👉 Action Robin — à regarder sur test :**
- `https://test.atelier-sapi.fr/mes-creations/?piece=salon` (essaie aussi `?piece=cuisine`, `?piece=chambre`, `?piece=bureau`, `?piece=entree`, `?piece=escalier`, `?piece=chambre-enfant`).
- Vérifie : la **photo plein écran** de la pièce, la **pill Robin**, la **phrase qui s'écrit**, la **question taille puis style** (cliquer valide + passe à la suivante), à la fin la **sélection qui se révèle** (photo se floute + slider de produits + carte sur-mesure), « **Voir ma sélection** » qui révèle à tout moment, « **Voir toutes les créations** » qui scrolle au catalogue, « **Préciser avec Robin** » qui ouvre la modale.
- Vérifie surtout que **`/mes-creations/` SANS `?piece=` est strictement comme avant** (état A + catalogue intacts), et **console F12 = 0 erreur**.

**⚠️ Volontairement PAS encore fait (étapes suivantes, je code après ton retour) :**
1. **Header transparent par-dessus la photo + bandeau qui devient sticky sous le header** au scroll (étape 1 garde le header normal opaque — le transparent-over-photo demande de gérer la lisibilité du logo/nav sur la photo, je le fais en passe dédiée propre).
2. **Re-filtrage de la sélection quand tu réponds taille/style** (= petit AJAX serveur, étape 4) — pour l'instant la sélection reste pièce-level, tes réponses sont bien mémorisées mais le slider ne se re-filtre pas encore.
3. **Hydratation localStorage** : un visiteur de retour avec un projet mais **sans `?piece=`** dans l'URL n'a pas encore l'état B (étape 6).
4. **Phrase courte de Robin par carte** du slider (pas de source de contenu pour l'instant — à voir avec toi : on l'écrit, ou on prend l'accroche produit ?).

**Dis-moi ce qui va / ce qui ne va pas, et je continue les étapes 2→6.**

---

## ✅ [FAIT 2026-06-10 — sur test] Harmonisation Conseiller — PHASE 4 : modale (6 états) (commit `f45f187`)
**Résultat (branche `test-theme-sapi-maison`, poussé sur test) :**
- **A. Cadre C** : la carte modale (`.conseiller-card--modal`) passe en **grain bois en filigrane** (le `::before` dashed est **repurposé en grain**, inset:0 desktop + mobile) → **plus de pointillé**, pas d'ourlet orange. Fond crème, radius 16, ombre inchangés.
- **B. Pill V1 sur les 6 écrans** : `.conseiller-sig conseiller-sig--v1` (composant Phase 1, non recréé) sur **s0** (accroche gardée) + **s2-chat** (« Discutons de ton projet ») + **ajoutée** en tête de **s1** (« On affine ton projet »), **s-product-recap** (« Mon conseil pour toi »), **s3** (« On récapitule ? »), **s-contact** form (« Échangeons ensemble ») et succès (« Merci, à très vite »). Anciens **badges texte masqués** (`.conseiller-card--modal .badge{display:none}`) mais **gardés dans le DOM** (ils portent `data-s0-badge-text`/`data-contact-badge-text`). Overrides modale obsolètes (signature 72px mobile) **nettoyés** → sizing 100 % géré par `--v1`.
- **C. Tutoiement** : corrigé dans `inc/guide-data.php` (titres taille/sortie/table/style — **possessifs accordés au genre** via table pièce→forme, pas de « ton chambre »), dans `buildRecapIntro` (JS, même table → « Pour ton salon, Robin recommande : ») et le **consentement s-contact** (« en envoyant ta demande, tu acceptes… »). ⚠️ `guide-data.php` est partagé → le bandeau « Mon projet » bénéficie aussi du tutoiement.
- **D. Hover des cartes de choix** (`.choice`) en **orange** (border + icône), scopé modale, comme la home.
- **Préservé** : tous les `data-*` (data-screen, data-s0-*, data-question-title, data-choices, data-progress-fill, data-chat-*, data-product-recap-*, data-recap-chips, data-contact-*), l'attribut `hidden`, le **honeypot `name="website"`**, la pill `#robin-product-pill`. **Aucune logique JS de filtre touchée.**
- **Vérifs** : accolades CSS 3738/3738 ; 7 pills `--v1` ; 0 vouvoiement résiduel (guide-data + modale) ; hooks présents.
**👉 Robin :** sur une **fiche produit variable** (test), ouvrir le Conseiller et parcourir les 6 écrans (desktop **+ mobile**) → carte au **grain bois sans pointillé**, **pill V1 photo + accroche Square Peg** en tête de chaque écran, **plus de vouvoiement** (titres s1, intro recap, consentement), **hover des choix en orange**. Vérifier que le filtre marche comme avant (choix → étapes → reco → appliquer → contact) et **console F12 = 0 erreur**. ⚠️ Lien « Contacter Robin » du récap produit → toujours `/contact/` (hors périmètre, à traiter à part si tu veux). Reste **Phase 3** (card Mes créations, après ton brief).

<details><summary>Énoncé original</summary>

## [TÂCHE] Harmonisation Conseiller — PHASE 4 : modale (tous les états)
**Date :** 2026-06-10 · **Priorité :** haute · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. Master/prod = SEULEMENT après validation Robin sur test.
**Réfs :** `mockups/AUDIT-MODALE-PHASE4.md` (audit complet des 6 états + liste des hooks à préserver) · `mockups/mockup-modale-conseiller-phase4.html` (rendu cible validé Robin).
**Décisions Robin (validées sur mockup) :** (1) **cadre C** = la carte modale passe en **fond crème + grain bois en filigrane seul**, on **retire le pointillé** (`::before` dashed) ; pas d'ourlet orange. (2) **pill V1 sur TOUS les écrans** (photo Robin sans contour + accroche Square Peg blanche), à la place des anciens badges texte. (3) **tutoiement** corrigé partout.

**Contexte / périmètre :** la modale (`.conseiller-modal` / `.conseiller-card--modal`) est injectée sur la **fiche produit variable** (markup statique PHP dans le shell `functions.php` ; scripts `sapi-modal-conseiller.js`, `sapi-cards-conseiller.js`, `sapi-help-pill.js`). **6 écrans** : `s0`, `s1`, `s2-chat`, `s-product-recap`, `s3`, `s-contact`. ⚠️ **Markup propre à la modale** (`.modal__*`, `.choices`, `.separator-or`, `.text-input*`, `.badge`) — ce n'est PAS la famille `.room-picker-*`. **Ne casser AUCUN hook JS / `data-*`** (liste exhaustive dans l'audit §4 : tous les `data-screen`, `data-s0-*`, `data-question-title`, `data-choices`, `data-progress-fill`, `data-chat-*`, `data-product-recap-*`, `data-recap-chips`, `data-contact-*`, l'attribut `hidden` de chaque `.modal__screen`, et le **honeypot `input[name="website"]`**). La pill déclencheur `#robin-product-pill` (Phase 2) reste inchangée.

**À faire :**

### A. Cadre C sur la carte modale (`.conseiller-card--modal`)
- Garder le fond crème `--color-warm`, **ajouter le grain bois** en filigrane : `background-image:repeating-linear-gradient(92deg,rgba(139,115,85,.05) 0,rgba(139,115,85,.05) 1px,transparent 1px,transparent 7px);`
- **Retirer le pointillé** : supprimer (ou neutraliser) le `::before` dashed `1.11px rgba(139,115,85,.35)` de la carte. Pas d'ourlet orange. Reste : `border-radius:16px`, ombre actuelle, overlay inchangé, bouton fermer inchangé.

### B. Pill V1 sur les 6 écrans (remplace les badges texte)
- **Réutiliser la classe existante `.conseiller-sig conseiller-sig--v1`** (déjà au CSS depuis Phase 1, commit `cb849af` — capsule wood-dark, avatar 34px sans contour, `__who` masqué, hook Square Peg blanc 24px, MQ mobile 21px). **Ne pas recréer de composant.**
- **s0 et s2-chat** : la signature `.conseiller-sig` existe déjà (commit `d659fe8`) → **ajouter la classe `conseiller-sig--v1`** + mettre l'accroche voulue. ⚠️ Vérifier qu'aucun override modale plus spécifique (`.conseiller-card--modal .conseiller-sig__avatar` à 72px, etc.) ne batte `--v1` ; si oui, neutraliser pour que le rendu compact V1 s'applique réellement dans la modale.
- **s1, s-product-recap, s3, s-contact** : **ajouter** le markup pill en tête de chaque écran :
```php
<div class="conseiller-sig conseiller-sig--v1">
  <span class="conseiller-sig__avatar"><?php echo sapi_image('2026/03/Robin-face-avec-Alice-lhelice.jpg','medium',['alt'=>'Robin, artisan de l\'Atelier Sâpi','class'=>'conseiller-sig__img','loading'=>'lazy']); ?></span>
  <span class="conseiller-sig__text"><span class="conseiller-sig__who">Le conseil de Robin</span><span class="conseiller-sig__hook">ACCROCHE</span></span>
</div>
```
- **Masquer les anciens badges texte** sans les retirer du DOM (ils portent des `data-*` lus par le JS) : `.conseiller-card--modal .badge{display:none}`. Vérifier que `.badge` n'a pas d'autre rôle visible dans la modale (l'audit ne lui en voit aucun) et qu'aucune LOGIQUE JS ne dépend de sa visibilité (texte/valeur OK, visibilité non).
- **Accroches Square Peg par écran** (tutoiement, pas de tiret cadratin) :
  - `s0` → « Mon regard d'artisan sur ton projet » (déjà en place, garder)
  - `s1` → « On affine ton projet »
  - `s2-chat` → « Discutons de ton projet » (remplace l'accroche actuelle)
  - `s-product-recap` → « Mon conseil pour toi »
  - `s3` → « On récapitule ? »
  - `s-contact` → « Échangeons ensemble »

### C. Tutoiement (corriger le vouvoiement résiduel)
Repérer et corriger dans le code de la modale / du mégafiltre (PHP `functions.php` et/ou config des questions) :
1. **Titres s1** type « QUELLE TAILLE FAIT VOTRE SALON ? » / « QUEL STYLE POUR VOTRE SALON ? ».
2. **Intro s-product-recap** : « Pour **votre** salon / salle à manger, Robin recommande : ».
3. **Consentement s-contact** : « En envoyant **votre** demande, **vous** acceptez… » → « En envoyant **ta** demande, **tu** acceptes… ».

⚠️ **Piège de genre** : ces titres **interpolent le nom de la pièce** ; « votre » est neutre, le tutoiement ne l'est pas. **Ne PAS remplacer mécaniquement « votre » par « ton »** (ça donnerait « ton chambre »). Introduire une **table piece-clé → possessif tutoyé exact**, et l'utiliser pour s1 + l'intro produit-recap :
| Pièce (label) | Forme tutoyée |
|---|---|
| Cuisine | ta cuisine |
| Bureau / Atelier | ton bureau |
| Salon / Salle à manger | ton salon |
| Chambre | ta chambre |
| Chambre enfant | ta chambre d'enfant |
| Entrée / Couloir | ton entrée |
| Cage d'escalier | ta cage d'escalier |
(Adapter aux clés réelles trouvées dans le code. Si une pièce manque, choisir la forme correcte ; en dernier recours, replier sur « **ta pièce** » plutôt qu'une forme fautive.)

### D. Hover des cartes de choix en orange (comme la home)
- `.conseiller-card--modal .choices > *:hover` (ou la classe réelle des cartes `.choices`) → aligner sur la home : `border-color:var(--color-orange);transform:translateY(-2px);box-shadow:var(--shadow-card-hover)` + carré d'icône `background:rgba(227,91,36,.12);color:var(--color-orange)` au hover. Scoper à la modale.

**NE PAS faire :** toucher au JS de filtre / aux `data-*` / au honeypot / à l'attribut `hidden` / à `#robin-product-pill`. Ne pas changer le comportement du lien « Contacter Robin » du récap produit (il part sur `/contact/` — incohérence notée dans l'audit §1 s-product-recap, mais **hors périmètre design**, à traiter à part si Robin le veut). Pas de tiret cadratin. Accolades équilibrées.

**Critères de succès :** sur une fiche produit variable (test), ouvrir la modale et parcourir les 6 écrans → carte au **grain bois sans pointillé**, **pill V1 photo + accroche Square Peg** en tête de **chaque** écran (accroches ci-dessus), **plus aucun vouvoiement** (titres s1, intro recap, consentement contact), **hover des choix en orange**. Le filtre fonctionne exactement comme avant (choix → étapes → reco → appliquer la sélection → contact). Console F12 = 0 erreur. Mobile ≤600px OK (pill 21px, plein écran).

### 👉 Action Robin
Sur test, fiche produit variable → ouvrir le Conseiller et faire défiler les 6 écrans (desktop + mobile). Vérifier grain/pill/tutoiement/hover + que rien n'est cassé. Si OK → go-live (avec les autres chantiers Conseiller en attente). Reste après ça : **Phase 3** (card Robin sur Mes créations, après ton brief refonte Mes créations).

</details>

---

## ✅ [FAIT 2026-06-10 — sur test] Harmonisation Conseiller — PHASE 2 : pill fiche produit mini V1 (V6) (commit `424828f`)
**Résultat (branche `test-theme-sapi-maison`, poussé sur test) :**
- **CSS** `.conseiller-pill-secondary` : capsule claire dashed → **mini capsule bois sombre** (`--color-wood-dark`, radius 60), **photo sans contour** (rond), **accroche Square Peg blanche**, **sans badge ni flèche**, hover ombre + lift. **Anciennes règles mortes retirées**. Retouche Robin (`068d366`) : **+20 % de taille** (avatar 31px, accroche 22px / mobile 19px, gap 11 / padding 5px 17px 5px 5px) + **`text-transform:none`** (le texte sortait en MAJUSCULES à cause de la règle `button` globale).
- **Texte (PHP, `single-product.php`)** : « Comment choisir ? » → **« Je t'aide à choisir la bonne version »** (wording final Robin, `5e0fb8c`). **Identique dans les 3 états** du projet.
- **JS (`sapi-help-pill.js`)** : comme le texte est désormais unique, la logique de texte contextuel (initial/partiel/complet + chips) a été **retirée proprement** (code mort) → le fichier ne garde que le câblage **clic → modale** (`data-help-pill` intact). Texte rendu côté PHP.
- **Câblage préservé** : `id="robin-product-pill"`, `data-action="open-modal"`, `data-modal-state="product"`, `data-help-pill`, `data-help-pill-text` → clic ouvre toujours la modale. Hook `woocommerce_before_single_variation` inchangé (**pill à sa position actuelle**). Accessoires/carte cadeau : toujours pas de pill.
- **Vérifs** : accolades 3737/3737 ; escaping JS/PHP OK ; pas de tiret cadratin.
**👉 Robin :** sur une **fiche produit variable** (test) : vérifier le look V6 (mini pill sombre + photo Robin + accroche Square Peg) + que le clic ouvre la modale + que le texte évolue selon le projet. **Position** : la pill reste à sa place actuelle — si tu la veux SOUS le sélecteur (mockup V6), c'est un changement de hook à part, dis-le. Restent **Phase 4** (modale, exploration mockup) et **Phase 3** (card Mes créations, après ton brief).

<details><summary>Énoncé original</summary>

## [TÂCHE] Harmonisation Conseiller — PHASE 2 : pill fiche produit en mini V1 (variante V6)
**Date :** 2026-06-10 · **Priorité :** haute · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. Master/prod après validation Robin.
**Mockup de référence :** `mockups/mockup-conseiller-pill-fiche-produit-10.html` → **variante V6** (mini pill V1 sombre DISCRÈTE, photo sans contour + accroche Square Peg, SANS badge ni flèche).
**Contexte :** la pill « Comment choisir ? » de la fiche produit (`.conseiller-pill-secondary`, `single-product.php` ~l.424) passe au design V1 mini/discret (capsule bois sombre, photo sans contour, accroche Square Peg, pas d'ornement). ⚠️ C'est un `<button>` CLIQUABLE qui ouvre la modale → **préserver tout le câblage** : `id="robin-product-pill"`, `data-action="open-modal"`, `data-modal-state="product"`, `data-help-pill`, et le span `data-help-pill-text` (texte piloté en live par `assets/sapi-help-pill.js`). **NE PAS toucher au JS.**

**À faire :**
1. **Markup** (`single-product.php`, `$render_help_pill`) : garder le `<button>` + tous ses attributs + le span avatar + le span `data-help-pill-text`. Juste **changer le texte par défaut** « Comment choisir ? » → « **Je t'aide à choisir la bonne variante** ». Vérifier dans `sapi-help-pill.js` si « Comment choisir ? » est codé en dur comme fallback → si oui, aligner le fallback sur le nouveau wording, **sans changer la logique** (les variantes contextuelles type « Adapter à mon projet » restent gérées par le JS).
2. **CSS** — remplacer le style actuel (capsule claire dashed) de `.conseiller-pill-secondary` par le mini V1 (V6, sans badge) :
```css
.conseiller-pill-secondary{display:inline-flex;align-items:center;gap:9px;background:var(--color-wood-dark);border:none;border-radius:60px;padding:4px 14px 4px 4px;cursor:pointer;transition:.2s}
.conseiller-pill-secondary:hover{box-shadow:var(--shadow-card-hover);transform:translateY(-1px)}
.conseiller-pill-secondary__avatar{width:26px;height:26px;flex-shrink:0}
.conseiller-pill-secondary__img{width:100%;height:100%;object-fit:cover;border-radius:50%;display:block}
.conseiller-pill-secondary [data-help-pill-text]{font-family:var(--font-display);color:#fff;font-size:18px;line-height:1}
```
(Pas de badge, pas de flèche. Mobile ≤600px : si l'accroche déborde, réduire à ~16px.)
3. **Cleanup** : retirer les anciennes règles `.conseiller-pill-secondary` devenues mortes (cadre dashed, ancien avatar, etc.).

**Pièges :** ne pas toucher au JS (`sapi-help-pill.js`), aux `data-*`, à l'`id`, ni au hook `woocommerce_before_single_variation` (la pill reste à sa position actuelle — si Robin veut la passer SOUS le sélecteur comme dans le mockup V6, c'est un changement de hook à part, à confirmer). Pas de tiret cadratin. Accolades équilibrées.
**Critères :** sur une fiche produit VARIABLE, la pill = mini capsule bois sombre + photo Robin sans contour (26px) + accroche Square Peg « Je t'aide à choisir la bonne variante », discrète ; clic ouvre toujours la modale ; le texte dynamique selon le projet fonctionne encore. Accessoires / carte cadeau : pas de pill (inchangé).

### 👉 Action Robin
Ouvrir une fiche produit variable sur test : vérifier le look V6 + que le clic ouvre bien la modale. Dis-moi aussi si tu veux la pill SOUS le sélecteur (mockup V6) ou si la position actuelle te va. Si OK → reste la **Phase 4** (modale, exploration mockup) et la **Phase 3** (card Mes créations, après ton brief).

</details>

## ✅ [FAIT 2026-06-10 — sur test] Harmonisation Conseiller — PHASE 1 : page Conseils alignée sur la home (commit `cb849af`)
**Résultat (branche `test-theme-sapi-maison`, poussé sur test) :**
- **A. Pill V1 factorisée** : `.home-projet .conseiller-sig*` → classe partagée **`.conseiller-sig--v1*`** (mêmes déclarations). Home : classe `conseiller-sig--v1` ajoutée sur `.conseiller-sig` → **rendu home strictement identique** (base + override, v1 gagne par ordre source comme avant). 0 référence `.home-projet .conseiller-sig` restante.
- **B. Panneau Conseils** : cadre **dashed crème supprimé** (`.advice-room-picker::before` dashed inset) → **fond warm + grain bois** (`::before` repeating-linear-gradient, inset:0, z-index:0 ; inner z-index:1). Panneau **contenu** conservé (`.advice-room-picker-section` max-width 1400). Padding 2.75rem 2rem.
- **C. Pill Robin V1** ajoutée en tête du `.room-picker-inner` de `page-conseils-eclaires.php` (même avatar que la home `2026/03/Robin-face-avec-Alice-lhelice.jpg`), accroche dédiée **« Mes conseils spécifiques pour ton projet »** (commit `97c1ef4`).
- **D. Titre** : `<h3>` conservé, texte passé au **tutoiement** « Pour quelle pièce cherches-tu un luminaire ? ». Style déjà aligné (classe partagée `.room-picker-title` = même typo que la home) → aucune CSS dupliquée ajoutée.
- **E. Hover chips orange** scopé Conseils : valeurs **exactement celles de la home** (border orange + bg #f4ead3 + shadow-card-hover + translateY-2 ; icône bg rgba(227,91,36,.12) + color orange).
- **Vérifs** : accolades 3735/3735 ; modale, fiche produit, Inspiration, tous les `data-*` non touchés ; pas de tiret cadratin.
**👉 Robin :** comparer home et page Conseils côte à côte sur test (fond warm+grain, pill V1 « Je t'éclaire avant de choisir », chips hover orange, titre tutoyé, panneau contenu). Home doit être inchangée. Si OK → **Phase 2** (mockup pill fiche produit d'abord, puis code).

<details><summary>Énoncé original</summary>

## [TÂCHE] Harmonisation Conseiller — PHASE 1 : aligner le room-picker page Conseils sur la home
**Date :** 2026-06-10 · **Priorité :** haute · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. Master/prod après validation Robin.
**Réf :** `mockups/AUDIT-CONSEILLER-PHASE0.md` + `mockups/PLAN-HARMONISATION-CONSEILLER.md`.
**Décisions Robin :** panneau contenu restylé (PAS pleine largeur) · garder `<h3>` mais style aligné + tutoiement · accroche dédiée Conseils « **Je t'éclaire avant de choisir** » · Inspiration HORS périmètre (ne pas toucher) · modale = Phase 4 (ne pas toucher) · fiche produit = Phase 2 (ne pas toucher).

**À faire :**

### A. Factoriser la pill V1 en classe partagée `.conseiller-sig--v1`
Dans `style.css`, RENOMMER les règles `.home-projet .conseiller-sig*` (commit `76a468f`) en `.conseiller-sig--v1*` (mêmes déclarations, juste le sélecteur change) :
```css
.conseiller-sig--v1{display:inline-flex;align-items:center;gap:12px;background:var(--color-wood-dark);border-radius:60px;padding:6px 24px 6px 6px;margin:0 0 16px}
.conseiller-sig--v1 .conseiller-sig__avatar{width:34px;height:34px;border:none;box-shadow:none}
.conseiller-sig--v1 .conseiller-sig__who{display:none}
.conseiller-sig--v1 .conseiller-sig__text{gap:0}
.conseiller-sig--v1 .conseiller-sig__hook{color:#fff;font-size:24px;line-height:1;margin:0}
@media (max-width:600px){.conseiller-sig--v1{max-width:100%}.conseiller-sig--v1 .conseiller-sig__hook{font-size:21px}}
```
Puis dans `front-page.php`, ajouter la classe `conseiller-sig--v1` à l'élément `.conseiller-sig` de la home. **Le rendu de la home doit rester STRICTEMENT identique** (vérifier).

### B. Restyler le panneau Conseils (panneau contenu, pas pleine largeur)
Supprimer le cadre dashed crème (`.advice-room-picker::before` dashed + fond carte actuel) et le remplacer par warm + grain :
```css
.advice-room-picker{position:relative;background:var(--color-warm);border-radius:16px;padding:2.75rem 2rem;text-align:center;overflow:hidden}
.advice-room-picker::before{content:"";position:absolute;inset:0;background-image:repeating-linear-gradient(92deg,rgba(139,115,85,.05) 0,rgba(139,115,85,.05) 1px,transparent 1px,transparent 7px);pointer-events:none;z-index:0}
.advice-room-picker .room-picker-inner{position:relative;z-index:1}
```
`.advice-room-picker-section` garde son `max-width:1400` + padding (→ contenu, pas edge-to-edge).

### C. Ajouter la pill Robin V1 en tête du `.room-picker-inner` (avant le `<h3>`), dans `page-conseils-eclaires.php`
```php
<div class="conseiller-sig conseiller-sig--v1">
  <span class="conseiller-sig__avatar"><?php echo sapi_image('<MÊME IMAGE AVATAR QUE LA HOME>', 'medium', ['alt' => 'Robin, artisan de l\'Atelier Sâpi', 'class' => 'conseiller-sig__img', 'loading' => 'lazy']); ?></span>
  <span class="conseiller-sig__text">
    <span class="conseiller-sig__who">Le conseil de Robin</span>
    <span class="conseiller-sig__hook">Je t'éclaire avant de choisir</span>
  </span>
</div>
```
(reprendre l'image d'avatar EXACTE utilisée sur la home pour la cohérence.)

### D. Titre : garder `<h3 class="room-picker-title">` mais aligner le style sur la home + passer au tutoiement
- Texte → « Pour quelle pièce cherches-tu un luminaire ? » (tutoiement).
- Style identique à la home : `.advice-room-picker .room-picker-title{font-family:var(--font-body);font-weight:700;font-size:clamp(...même valeur que la home...);color:var(--color-wood-dark)}`. Relever la valeur exacte du titre home et la reprendre.

### E. Hover des chips en ORANGE (comme la home), scopé Conseils
Reprendre les valeurs EXACTES de la home :
```css
@media (hover:hover){
  .advice-room-picker .room-card:hover{border-color:var(--color-orange);background:#f4ead3;box-shadow:var(--shadow-card-hover);transform:translateY(-2px)}
  .advice-room-picker .room-card:hover .room-card-icon{background:rgba(227,91,36,.12);color:var(--color-orange)}
}
```

**Pièges / NE PAS faire :** ne pas toucher la modale, la fiche produit, la page Inspiration, ni les `data-*` du room-picker. Pas de tiret cadratin. Accolades équilibrées. Vérifier que la home rend identique après la refacto de la pill (point A).

**Critères :** la page Conseils a le MÊME langage que la home (fond warm + grain, pill V1 avec accroche « Je t'éclaire avant de choisir », chips hover orange, titre aligné, tutoiement), en restant un panneau contenu (max-width 1400). Home inchangée. Mobile OK.

### 👉 Action Robin
Comparer home et page Conseils côte à côte sur test. Si identiques → on passe à la **Phase 2** (mockup pill fiche produit d'abord, puis code).

</details>

## ✅ [FAIT 2026-06-10 — lecture seule] Harmonisation Conseiller — PHASE 0 : spec + audit
**Livrable : `mockups/AUDIT-CONSEILLER-PHASE0.md`** (rapport complet, aucune modif de code). Synthèse :
- **(a) Spec de référence** figée (HOME) : bande `.home-projet-section` warm + grain bois `::before` (⚠️ pas de border-top aujourd'hui) ; pill V1 `.home-projet .conseiller-sig` (capsule wood-dark, photo 34px sans contour, hook Square Peg blanc 24px, label masqué) ; socle partagé `.room-picker-*` (valeurs exactes inner/title/cards/room-card/icon/label/or/freetext) ; câblage `data-room-picker`/`data-piece`/`data-room-picker-freetext`.
- **(b) Écarts par emplacement** : **Conseils** (`.advice-room-picker`) = cadre crème **dashed inset** + pas de pill + titre `<h3>` **vouvoiement** + hover **wood** (vs orange) + pas de grain ; **Modale S0** = ⚠️ **markup totalement à part** (`.modal__*`/`.choices`/`.separator-or`/`.text-input*`, PAS la famille `.room-picker-*`) + `.conseiller-sig` style ANCIEN → confirme un chantier Phase 4 (réécriture, pas override) ; **Pill fiche produit** = capsule claire dashed + avatar 26px → à passer V1 (préserver `id robin-product-pill`/`data-action`/`data-modal-state`/`data-help-pill`/`data-help-pill-text`) ; **Mes créations** = carte + badge crayon + titre vouvoiement (Phase 3, attend le brief).
- **(c) Reco Phase 1** : le socle `.room-picker-*` est **déjà bien factorisé** → ne pas le refactorer, juste réduire les overrides Conseils. Option : extraire les 3 traits identitaires home (grain + pill V1 + hover orange + tutoiement) dans un modificateur partagé `.room-picker--robin` **scopé aux room-pickers de PAGE** (home + conseils ; jamais modale/mes-créations).
- **(d) Verdict Inspiration** : **HORS périmètre** — c'est un **filtre de galerie** (`.inspiration-filter-btn`, pièce+essence), pas un room-picker. Il n'y a PAS de 4e room-picker.
- **Questions à trancher avant Phase 1** (dans le rapport §E) : Conseils en bande pleine largeur ou panneau contenu restylé (reco) ? garder `<h3>` SEO ou `<h2>` ? accroche pill propre à Conseils ? confirmer Inspiration exclu.
**👉 Robin :** lire `mockups/AUDIT-CONSEILLER-PHASE0.md` + trancher les questions §E → j'écris la Phase 1.

<details><summary>Énoncé original</summary>

## [TÂCHE] Harmonisation Conseiller — PHASE 0 : spec + audit (LECTURE SEULE, aucun changement)
**Date :** 2026-06-10 · **Priorité :** haute · **Lecture seule, AUCUNE modif de fichier.**
**Plan complet :** `mockups/PLAN-HARMONISATION-CONSEILLER.md` (à lire). Objectif : préparer l'harmonisation du composant « Robin Conseiller » sur tout le site, en partant de la HOME comme référence (pill V1 = commit `76a468f`).

**À faire :**
1. **Figer la spec** du composant de référence (room-picker home `.home-projet` + pill Robin V1) : relever les valeurs réelles dans `front-page.php` + `style.css` — fond/grain de la bande, titre (typo, tutoiement), pill V1 (capsule wood-dark, photo 34px sans contour, accroche Square Peg blanche 24px, pas de label), chips `.room-card` (repos + hover), séparateur « ou », champ libre + bouton rond orange, couleurs, attributs `data-*`. Restituer la spec noir sur blanc.
2. **Auditer chaque emplacement** et lister les ÉCARTS vs la référence :
   - Room-picker page **Conseils** (`page-conseils-eclaires.php` `.advice-room-picker-section`) : cadre crème dashed, titre vouvoiement « cherchez-vous », pas de pill Robin…
   - Room-picker **modale** (mégafiltre S0, `functions.php`) : documenter (traité en Phase 4, mais relever l'état).
   - **Pill fiche produit** (`single-product.php` `.conseiller-pill-secondary`).
   - **Card Robin Mes créations** (`woocommerce/archive-product.php`).
   - **`page-inspiration.php`** : confirmer si ses `room-card` = room-picker « pour quelle pièce » (4e à aligner) ou filtre distinct.
3. **Cartographier classes partagées vs spécifiques** (`.room-picker-*` base vs overrides `.home-projet` / `.advice-room-picker` / modale) → préparer une factorisation pour Phase 1.

**Livrable :** rapport = (a) spec de référence ; (b) tableau « emplacement → écarts » ; (c) reco de factorisation Phase 1 ; (d) verdict Inspiration. Aucune modif de code.
**👉 Robin :** lire le rapport → j'écris la Phase 1 (aligner Conseils sur la home) depuis les écarts.

</details>

## ✅ [FAIT 2026-06-10 — sur test] Pill Conseiller home — V1 accroche Square Peg dans la pill, sans label (commit `76a468f`)
**Résultat (branche `test-theme-sapi-maison`, poussé sur test) :** `.home-projet .conseiller-sig*` → la pill sombre ne contient plus que la **photo sans contour** (avatar 34px, `border:none`) + l'**accroche Square Peg blanche 24px**. **« Le conseil de Robin » masqué** (`__who{display:none}`, markup conservé). `gap:12`, `padding:6px 24px 6px 6px`, `margin:0 0 16px`, `__text gap:0`. Mobile ≤600px : hook **21px** + `max-width:100%`. Texte d'accroche et photo inchangés. Scopé home → modale + fiche produit intactes. Accolades 3732/3732, pas de tiret cadratin.
**👉 Robin :** valider sur test (accroche Square Peg lisible, plus de label, titre dominant). Si OK → généralisation (modale + fiche + page conseils) avec ce format, accroche **contextuelle par page** (home = actuel ; fiche = « Je t'aide à choisir la bonne variante » ; etc.).

<details><summary>Énoncé original</summary>

## [TÂCHE] Pill Conseiller home — V1 « accroche Square Peg DANS la pill, sans label »
**Date :** 2026-06-10 · **Priorité :** normale · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. Master/prod après validation Robin.
**Contexte :** itération finale du format. Le mini B1 (`595874f`) avec le Square Peg blanc 18px était illisible. Nouvelle direction validée (mockup `mockups/mockup-conseiller-pill-squarepeg-dedans.html` variante **V1**) : la pill sombre ne contient plus QUE la photo (**sans contour**) + l'**accroche Square Peg en plus grand** ; on **SUPPRIME « Le conseil de Robin »**. L'identité « Robin » passe par la photo. ⚠️ Ça remplace la règle précédente « ligne du haut fixe ».

**À faire (scopé home `.home-projet .conseiller-sig*`, ne pas toucher modale ni fiche) :**
1. **Retirer le label** : masquer la ligne « Le conseil de Robin » → `.home-projet .conseiller-sig__who{display:none}` (markup laissé en place, juste caché ; OK).
2. **Remplacer les tailles B1** par V1 :
```css
.home-projet .conseiller-sig{
  display:inline-flex;align-items:center;gap:12px;
  background:var(--color-wood-dark);
  border-radius:60px;
  padding:6px 24px 6px 6px;
  margin:0 0 16px;
}
.home-projet .conseiller-sig__avatar{width:34px;height:34px;border:none;box-shadow:none}
.home-projet .conseiller-sig__who{display:none}
.home-projet .conseiller-sig__text{gap:0}
.home-projet .conseiller-sig__hook{color:#fff;font-size:24px;line-height:1;margin:0}
```
Mobile ≤600px : vérifier que l'accroche ne déborde pas (réduire à ~21px si besoin), pas de contour avatar.
**Notes :** photo et texte d'accroche inchangés (la home garde son hook actuel). Pas de tiret cadratin, accolades équilibrées.
**Critères :** pill = photo sans contour + accroche Square Peg blanche 24px, lisible ; plus de « Le conseil de Robin » ; le titre reste dominant.
**👉 Robin :** valider sur test. Si OK → généralisation (modale + fiche + page conseils) avec ce format, accroche contextuelle par page (home = actuel ; fiche = « Je t'aide à choisir la bonne variante » ; etc.).

</details>

## ✅ [FAIT 2026-06-10 — sur test] Pill Conseiller home — mini format B1 (commit `595874f`)
**Résultat (branche `test-theme-sapi-maison`, poussé sur test) :** tailles de `.home-projet .conseiller-sig*` réduites au format **B1** — avatar **34px**, `gap:10px`, `padding:5px 18px 5px 5px`, `__text gap:1px`, eyebrow **9px** (letter-spacing .14em), accroche Square Peg **18px** (`line-height:1`). Mobile ≤600px : hook **16px** + `max-width:100%`. **Markup, textes, photo, centrage inchangés.** Toujours scopé home → modale + pill fiche produit intactes. Accolades 3732/3732, pas de tiret cadratin.
**👉 Robin :** valider sur test (le titre « Pour quelle pièce » doit reprendre la vedette). Si OK → généralisation (modale + fiche + page conseils) avec ce format B1 et lignes du bas contextuelles.

<details><summary>Énoncé original</summary>

## [TÂCHE] Pill Conseiller home — réduire en mini format B1 (trop grosse en V1)
**Date :** 2026-06-10 · **Priorité :** normale · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. Master/prod après validation Robin.
**Contexte :** la V1 appliquée (commit `99c6903`) est **trop grosse/visible** sur la home : elle écrase le titre « Pour quelle pièce… ». Robin valide le **format B1** (mini pill, mêmes style/couleurs, juste réduit) → mockup `mockups/mockup-conseiller-pill-B-squarepeg.html` variante **B1**. L'accroche reste en **Square Peg**.
**À faire :** ajuster UNIQUEMENT les valeurs de taille de la règle home `.home-projet .conseiller-sig*` (ajoutée en `99c6903`). Remplacer par :
```css
.home-projet .conseiller-sig{
  display:inline-flex;align-items:center;gap:10px;
  background:var(--color-wood-dark);
  border-radius:60px;
  padding:5px 18px 5px 5px;
  margin:0 0 18px;
}
.home-projet .conseiller-sig__avatar{width:34px;height:34px;border:2px solid rgba(255,255,255,.18);box-shadow:none}
.home-projet .conseiller-sig__text{gap:1px;text-align:left}
.home-projet .conseiller-sig__who{color:#e0a878;font-size:9px;letter-spacing:.14em}
.home-projet .conseiller-sig__hook{color:#fff;font-size:18px;line-height:1;margin-top:0}
```
Mobile ≤600px : la pill est déjà petite, vérifier juste qu'elle ne déborde pas (réduire le hook à ~16px si besoin). Rien d'autre ne change (markup, textes, photo, centrage). Pas de tiret cadratin, accolades équilibrées.
**Critères :** signature nettement plus discrète, le titre reprend la vedette, accroche en Square Peg lisible.
**👉 Robin :** valider sur test. Si OK → généralisation (modale + fiche + page conseils) avec ce format B1 et lignes du bas contextuelles.

</details>

## 🔧 À faire — actions Robin (post go-live)
1. **Vérifier la home en prod** (atelier-sapi.fr) : photos `2026/02`/`2026/04`/`2026/05`/`2026/06` affichées ; **card Sur mesure** (photo tirée de la catégorie `creations-sur-mesure` → si mauvaise/absente, mettre l'image voulue dans le champ ACF **Image collection** de la catégorie) ; **logo Région** affiché ; **logos presse alignés** ; **photo Assemblage en miroir** au survol de l'étape 04.
2. **Tester en prod** : inscription newsletter + form « Échanger avec Robin » fiche produit → réception Brevo.
3. **Re-soumettre le sitemap** dans Google Search Console.
4. **Brevo** : maj séquence d'accueil −10 % pour inclure les sources `surmesure` + `ficheproduit`.
5. **Passe Yoast** (pas encore faite) : titre + meta description de la home — Claude Code peut préparer une proposition au prochain run.
6. ~~(optionnel) nettoyage CSS mort résiduel~~ → ✅ **EN PROD (2026-06-10)** (commit `7534d2a`). Validé Robin, déployé.

## ✅ [FAIT 2026-06-10 — sur test] Suppression complète du système bento LEGACY (CSS + JS)
**Branche `test-theme-sapi-maison`, poussé sur test.** −869 lignes CSS / −68 lignes JS (cinetique.js) / −1 entrée scroll-dots.js. **Accolades CSS équilibrées 3724/3724**, JS balancé (parens/braces OK). Méthode : audit + grep boundary de CHAQUE classe (markup `.php` + JS `.js`, hors `/mockups/`) avant suppression.

**🗑️ Retiré — JS :**
| Fichier | Retiré |
|--------|--------|
| `cinetique.js` | bloc « Bento Cards Animation on Scroll » (`.bento-card` IntersectionObserver) ; bloc « Product Cards Parallax » (`.bento-product`/`.product-image` tilt souris) ; entrée cache `heroImage:'.bento-hero .bento-bg'` + son bloc parallax déjà commenté (DISABLED). Le reste du fichier (notifications, parallax shop/catégorie, particules canvas, smooth-scroll…) **intact**. |
| `scroll-dots.js` | entrée morte `{container:'.process-inner', child:'.process-step'}` (plus aucun élément → ne matchait rien). |

**🗑️ Retiré — CSS (0 usage markup ET JS confirmé) :** tout le système legacy « CINÉTIQUE Bento Grid » + ses media queries (1200px / 768px ×2 / 540px / 375px / reduced-motion / mobile slider) :
`.bento-container` · `.bento-card`(+hovers, +`:focus-visible` du groupe) · `.bento-bg` / `.bento-bg-img`(+`--bottom-right`) · `.bento-label` · `.bento-text` · `.bento-corner-info` · `.bento-statement` · `.bento-product`(+`.product-image`/`.product-overlay`/`.product-name`/`.product-cat` descendants) · `.bento-product-small`(+`.product-image-small`/`.product-overlay-small`/`.product-name-small` descendants) · `.bento-stats` · `.bento-process` · `.bento-hero` / `.bento-storytelling` / `.bento-atelier` (MQ seulement).
**+ companions orphelins** (0 usage, n'existaient QUE dans le markup bento supprimé, donc retirés pour finir la « suppression complète ») : `.hero-cta-row`, `.corner-label`, `.corner-price`, `.statement-inner/number/text/author`, `.product-info-reveal`, `.product-price-tag`, `.stat-block`(+`::after`/strong/span/hovers), `.stat-content`, `.stat-hover`(+img/text), `.process-header`, `.process-number`, `.process-title`, `.process-inner`(+`::before`), `.process-step`(+hovers), `.step-num`, `.step-text`, `.step-image-img`.

**✅ CONSERVÉ (vérifié présent + raison) :**
| Classe | Raison |
|--------|--------|
| `.hero-bento` | wrapper `.home-creations` (front-page.php:636) — 3 règles intactes (base + 2 MQ). |
| `.bento-bestseller-badge` | badge « Star » sur `.creation-star` (front-page.php:646). |
| `.product-badge` (base) | **live** : badges Promo/Nouveau des fiches/cartes produit (`content-product.php`). Le retirer décalait visuellement les badges → GARDÉ. |
| `.hero-cta` / `.hero-cta--wood` | **live** : CTA « Voir toutes les créations » + « Découvrir l'artisan » (front-page.php). |
| `.storytelling-text` | **live** : réutilisé par la section L'atelier (refonte #7). |
| `.process-*` de `page-sur-mesure.php` | ce sont `.surmesure-process-*` (namespace distinct) — **non touchés**. `.progress-step .step-number` (stepper modale Conseiller) = classe distincte, **non touchée**. |

**🔎 Pièges vérifiés :** page **« Star du moment »** utilise son propre namespace `star-storytelling__*` (PAS `process-*`/`step-*`/`bento-*`) → aucun risque. Commentaires de traçabilité `.bento-cta*`/`.bento-actu*`/`.bento-giftcard*`/`.bento-conseil*` (historique des refontes #9/DA#7) laissés en place (exacts, non trompeurs).
**Reste 0 référence** à un sélecteur retiré dans tout le markup/JS live (grep final = NONE).

**👉 Robin :** vérifier sur test — home (desktop **+ mobile**), page « Star du moment », une catégorie, une fiche produit (badges Promo/Nouveau OK) + **console F12 sur la home = 0 erreur**. Puis « go » → merge master + prod manuel. ⚠️ J'ai légèrement **étendu au-delà des seuls `.bento-*`** (companions orphelins `.stat-*`/`.process-*`/`.statement-*`/etc., tous 0 usage) pour vraiment finir le nettoyage — si tu préfères que je n'en retire qu'une partie, dis-le.

<details><summary>Énoncé original</summary>

## [TÂCHE] Suppression complète du système bento LEGACY (CSS + JS, hors home)
**Date :** 2026-06-10 · **Priorité :** basse (maintenance, aucune urgence) · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. **Master/prod = SEULEMENT après validation Robin sur test.**
**Contexte :** suite du nettoyage CSS (`7534d2a`). Le vieux système bento « CINÉTIQUE » (cartes `.bento-card`/`.bento-hero`/`.bento-bg`/`.bento-container`/`.bento-storytelling`/`.bento-process`/etc.) n'est **plus émis dans aucun markup** depuis la refonte, mais son CSS est resté « par prudence » car `cinetique.js` **poke encore ces sélecteurs** (`querySelector('.bento-hero .bento-bg')`, `querySelectorAll('.bento-card')`…) — code mort qui ne matche plus rien. On retire le tout d'un coup, CSS **et** JS.

**⚠️ À CONSERVER absolument (encore utilisés, NE PAS supprimer) :**
- `.hero-bento` → wrapper de la section `.home-creations` (markup actuel).
- `.bento-bestseller-badge` → badge « Star du moment » sur `.creation-star`.
- Tout le reste de `cinetique.js` qui ne concerne PAS le bento (autres animations/sections) : ne toucher QUE les blocs bento.

**À faire :**
1. **Audit `cinetique.js`** : lister tous les blocs/sélecteurs liés au bento legacy (`.bento-card`, `.bento-hero`, `.bento-bg`, `.bento-product`, `.bento-container`, etc.). Confirmer par grep markup que ces éléments n'existent plus dans le DOM rendu (front-page + autres templates). 
2. **Retirer le code JS mort** correspondant (handlers hover/parallax/tilt des bento cards, init bento…), en gardant le reste du fichier intact. Vérifier qu'aucune autre partie du JS n'en dépend.
3. **Retirer le CSS** désormais totalement orphelin : tous les `.bento-*` SAUF `.hero-bento` et `.bento-bestseller-badge` (grep chacun avant : 0 markup ET 0 JS après l'étape 2 → supprimer). Inclure leurs media queries + commentaires obsolètes.
4. **Vérifs :** accolades CSS équilibrées ; `cinetique.js` valide (pas d'erreur de syntaxe) ; **console navigateur sur la home test = 0 erreur JS** ; home (desktop + mobile), page « Star du moment », une catégorie, une fiche produit = rendu strictement inchangé.

**Livrable / critères :** tableau « retiré (CSS) / retiré (JS) / conservé » ; 0 erreur console ; rendu identique partout ; `.hero-bento` + `.bento-bestseller-badge` intacts. En cas de doute sur un bloc JS partagé → garder et le signaler.
**👉 Robin :** vérifier sur test (visuel + ouvrir la console F12 sur la home pour confirmer 0 erreur), puis go → merge master + prod manuel.

</details>

## ✅ [FAIT 2026-06-10 — sur test] Signature Conseiller → pill bois sombre (V1) — HOME (commit `99c6903`)
**Résultat (branche `test-theme-sapi-maison`, poussé sur test) :** `.conseiller-sig` restylée SCOPÉE à la home (`.home-projet .conseiller-sig*`) en capsule bois sombre — `inline-flex`, fond `--color-wood-dark`, radius 60px, avatar 60px bordé clair (`rgba(255,255,255,.18)`), eyebrow tan `#e0a878`, accroche blanche 28px. **Markup et textes inchangés** (ligne haut « Le conseil de Robin », ligne bas = le hook actuel de la home). Centrage OK sans rien ajouter : `.room-picker-inner` est déjà `flex`/`align-items:center` → la pill `inline-flex` se centre toute seule. **Réduction mobile ≤600px** (gap/padding réduits, avatar 48px, hook 21px, `max-width:100%` anti-débordement). Accolades 3732/3732. **Modale Conseiller (`.conseiller-card--modal`) + pill fiche produit NON touchés** (réservés à l'étape suivante).
⚠️ **Coordination :** 1re tentative écrasée par la fenêtre bento (édition concurrente du même `style.css`). Refait proprement une fois le bento mergé en prod.
**👉 Robin :** valider le rendu de la pill sombre sur la home test (desktop + mobile). Si OK → on lance la généralisation (modale tous états + pill fiche produit + page conseils) avec la même V1 et lignes du bas contextuelles.

<details><summary>Énoncé original</summary>

## [TÂCHE] Signature Conseiller → pill bois sombre (V1) — HOME d'abord
**Date :** 2026-06-10 · **Priorité :** normale · **Branche :** `test-theme-sapi-maison` (auto-deploy test). Push auto. Master/prod = après validation Robin.
**Mockup de référence :** `mockups/mockup-conseiller-signature-20-variantes.html` → **variante V1** (capsule bois sombre + photo ronde).
**Contexte :** Robin valide V1 pour la signature « Le conseil de Robin ». 1re étape : l'appliquer SUR LA HOME pour voir (section `.home-projet`). La généralisation (modale tous états + fiche produit) = tâche SUIVANTE, **ne PAS y toucher ici**. Règle de contenu : **ligne du haut TOUJOURS « Le conseil de Robin »** ; **ligne du bas contextuelle** (home = le hook actuel du room-picker ; ex. fiche produit = « Je t'aide à choisir la bonne variante »).

**À faire :** restyler `.conseiller-sig` SCOPÉ à la home (`.home-projet .conseiller-sig*`) — markup et textes INCHANGÉS, juste l'habillage capsule sombre. Ajouter dans `style.css` (après les règles `.conseiller-sig` existantes) :
```css
/* ===== Signature Conseiller — V1 pill bois sombre (home) ===== */
.home-projet .conseiller-sig{
  display:inline-flex;align-items:center;gap:16px;
  background:var(--color-wood-dark);
  border-radius:60px;
  padding:10px 26px 10px 10px;
  margin:0 0 18px;
}
.home-projet .conseiller-sig__avatar{
  width:60px;height:60px;border:2px solid rgba(255,255,255,.18);box-shadow:none;
}
.home-projet .conseiller-sig__who{color:#e0a878;} /* eyebrow tan chaud sur fond sombre */
.home-projet .conseiller-sig__hook{color:#fff;font-size:28px;}
```
**Centrage :** la pill doit rester centrée dans la bande crème. `.conseiller-sig` passe en `inline-flex` → vérifier que son conteneur (`.room-picker-inner` / `.home-projet`) est bien `text-align:center` (a priori oui). Sinon, ajouter `.home-projet .room-picker-inner{text-align:center}` ou envelopper. Vérifier aussi le rendu mobile (la pill ne doit pas déborder ; réduire police/padding ≤600px si besoin).

**Notes :** ne PAS toucher au markup ni aux textes (la ligne du bas reste celle déjà en place sur la home). Photo : laisser l'image actuelle de l'avatar. Pas de tiret cadratin. Accolades équilibrées. **NE PAS toucher** à la signature dans la modale ni à la pill fiche produit (étape suivante).
**👉 Robin :** valider le rendu de la pill sombre sur la home test, puis on lance la généralisation (modale + fiche produit + page conseils) avec la même V1 et les lignes du bas contextuelles.

</details>

## ✅ EN PROD (2026-06-10) — nettoyage CSS mort + suppression pill « Signature »
Déployés ensemble sur atelier-sapi.fr (master `0d114b1`) :
- **Nettoyage CSS mort** post-refonte (`7534d2a`) — détail dans la tâche ci-dessous.
- **Suppression du pill « Signature »** des cards produit (`0d114b1`) : badge `.badge-signature` + logique `$is_signature`/`is_featured()` + CSS retirés de `content-product.php` et `style.css`. Badges **Promo** et **Nouveau** conservés. (Le statut WooCommerce « mis en avant » reste utilisable ailleurs ; on a juste arrêté d'en faire un pill.)

## ✅ [EN PROD 2026-06-10] Nettoyage CSS mort post-refonte home (commit `7534d2a`)
**Résultat (branche `test-theme-sapi-maison`, poussé sur test — zéro régression).** Méthode : grep de chaque candidat dans tout le markup/JS (`*.php` + `*.js`, hors mockups, hors `style.css`).

**Constat clé :** la **refonte (#1/#6/#7) avait déjà retiré la quasi-totalité des blocs morts** (confirmé par les commentaires de traçabilité dans `style.css`). La plupart des candidats (`bento-room-picker`, `process-flip/tile/ribbon`, `home-atelier--band`, `atelier-duo/story/photo/media/maps-link`, `map-card`, `bento-giftcard`, `giftcard-*`, `bento-actu`, `bento-conseil`, `home-divers`, `cta-button`) → **déjà absents** de `style.css` (ne restent que des commentaires).

**🗑️ Retiré (0 occurrence markup + 0 JS) :**
| Classe | Ce qui a été retiré |
|--------|---------------------|
| `.bento-cta` | règle mobile dédiée (768px) + 2 entrées dans des sélecteurs groupés |
| `.bento-content` | 2 règles mobiles (768px, 375px) |
| `.bento-title` | 2 règles mobiles (768px, 375px) |
| `.bento-product-featured` | 1 règle mobile (768px) |
→ −34 lignes, accolades équilibrées **3843/3843**.

**✅ GARDÉ car référencé (règle « ≥1 usage → garder ») :**
- `bento-bg`, `bento-card`, `bento-hero`, `bento-product` → **référencés dans `cinetique.js`** (le système bento legacy « CINÉTIQUE » est encore poké par le JS ; le retirer entièrement = toucher au JS, **hors périmètre** de cette tâche CSS-only).
- `bento-bestseller-badge`, `hero-bento` → utilisés dans le markup PHP.
- `bento-container` / `bento-bg-img` / `bento-storytelling` / `bento-process` / `bento-statement` / `bento-stats` / `bento-atelier` / `bento-product-small` → **gardés par prudence** (colonne vertébrale structurelle du système bento référencé + warning explicite du task).

**🔎 Warning du task levé :** la page **« Star du moment »** (`page-la-star-du-moment.php`) utilise son **propre namespace `star-storytelling__*`** — elle ne réutilise **ni** `bento-storytelling`/`bento-process`, **ni** `storytelling-*`/`process-*`/`step-*`/`atelier-label`. Aucun risque de ce côté.

**💡 Reste possible (tâche SÉPARÉE, non faite ici) :** retirer **tout** le système bento legacy d'un coup (CSS **+** le code mort correspondant dans `cinetique.js` : `querySelector('.bento-hero .bento-bg')`, `querySelectorAll('.bento-card')`…). Hors périmètre « CSS-only » de cette tâche.

**👉 Robin :** vérifier sur test que rien n'a bougé (home desktop+mobile, page Star, une catégorie, une fiche produit) → puis « go » pour merge master + prod. Si tu veux que je retire aussi le bento legacy complet (CSS+JS), dis-le.

<details><summary>Énoncé original</summary>

**Date :** 2026-06-10 · **Priorité :** basse (maintenance) · **Branche :** `test-theme-sapi-maison` (auto-deploy test pour valider). Push auto. **Master = SEULEMENT après validation Robin sur test** (puis merge + prod manuel comme d'hab).
**Objectif :** supprimer de `style.css` les règles devenues ORPHELINES après la refonte home (anciennes sections remplacées), SANS toucher à rien d'utilisé. Tâche cosmétique/perf : **zéro changement visuel**, en cas de doute on GARDE.

**Méthode OBLIGATOIRE — vérif avant chaque suppression :**
Pour CHAQUE classe candidate, grep le token de classe dans TOUT le repo hors `style.css` : `grep -rn "nom-de-classe" --include=*.php --include=*.js --include=*.html .` (inclure `woocommerce/`, `inc/`, `template-parts/`, `assets/`). 
- 0 occurrence en markup/JS → règle CSS **morte → supprimer**.
- ≥1 occurrence → **garder**, même si ça paraît lié à la home.

**⚠️ Pièges de classes PARTAGÉES (à NE PAS supprimer, vérifier d'abord) :**
- `page-la-star-du-moment.php` réutilise des classes `storytelling-*` / `process-*` / `atelier-label` / éventuellement `bento-storytelling` / `bento-process` / `step-*` → **probablement à GARDER**. Grep impératif.
- `.bento-bestseller-badge` est RÉUTILISÉ (Star de « Créations du moment ») → GARDER.
- `.hero-bento` est RÉUTILISÉ (section `.home-creations`) → GARDER.
- `.section-header-kinetic` / `.section-title-kinetic` / `.section-num` / `.collection-card--surmesure` / `.creation-star*` / `.product-card-cinetique` / `.conseiller-sig*` / `.home-projet*` / `.home-atelier--lumiere` + ses enfants / `.loc-card*` / `.home-cadeau-actus` + enfants / `.newsletter--band` + enfants → tous UTILISÉS, GARDER.

**Candidats probables (à confirmer par grep, supprimer SI 0 usage) :**
`.bento-room-picker`, `.process-flip*`, `.process-tile*`, `.process-ribbon*`, `.home-atelier--band`, `.atelier-duo`, `.atelier-story*`, `.atelier-photo*`, `.atelier-media`, `.atelier-maps-link`, `.map-card*`, `.bento-giftcard`, `.giftcard-badge`, `.giftcard-info`, `.bento-actu*`, `.home-divers`, `.bento-cta`, `.cta-title`, `.cta-button`, `.bento-hero`, `.bento-content`, `.bento-title`, `.bento-category`, `.bento-product-featured*`, `.bento-conseil*`.
(⚠️ pour `.bento-bg` / `.bento-bg-img` : grep d'abord, encore utilisés par d'autres cartes éventuelles ? sinon supprimer. `.bento-container` / `.bento-card` : grep — si plus aucun markup ne les utilise après refonte, supprimer, sinon garder.)

**À faire :**
1. Lister chaque candidat, grep, classer gardé/supprimé.
2. Supprimer UNIQUEMENT les règles 100 % orphelines (bloc + media queries associées + commentaires obsolètes).
3. Ne PAS toucher au JS, au PHP, ni aux classes utilisées.
4. Vérifs : accolades CSS équilibrées ; **comparer visuellement sur test** la home (desktop + mobile), la page « Star du moment », une page catégorie et une fiche produit → strictement identiques.

**Livrable / critères :** un **tableau gardé vs supprimé** (avec le nb d'occurrences trouvées) dans le résultat ; accolades équilibrées ; rendu inchangé partout ; aucune classe utilisée supprimée par erreur.
**👉 Robin :** vérifier sur test que rien n'a bougé visuellement, puis « go » → merge master + déploiement prod manuel.

</details>

<details><summary>✅ [FAIT 2026-06-10 — EN PROD] GO-LIVE refonte home — énoncé original</summary>

## [TÂCHE] 🚀 GO-LIVE refonte home — vérifier les branches PUIS merge master + prod
**Date :** 2026-06-09 · **Priorité :** haute
**Contexte :** La refonte home (DA #1→#8 + mobile + Yoast) est validée sur test, branche `feature/refonte-home` (dernier commit `17db187`). Robin donne le GO pour la prod, MAIS demande de **vérifier les branches d'abord** : `feature/refonte-home` a été créée **depuis `test-theme-sapi-maison`** (pas master), donc le merge vers master peut embarquer la divergence test↔master, y compris le chantier audit filtre PHP qui bloquait historiquement le merge test→master.

**À faire — DANS CET ORDRE, avec PAUSE obligatoire :**

### Étape 1 — AUDIT des branches (lecture seule, AUCUN push)
- `git fetch` puis :
  - `git log --oneline master..feature/refonte-home` (tous les commits qui atterriraient dans master)
  - `git diff --stat master...feature/refonte-home` (fichiers touchés)
  - Isoler ce qui n'est PAS la refonte home : repérer tout changement sur le filtre méga-filtre / prompts IA / Conseiller backend / autres pages, et tout WIP non fini.
- Vérifier l'écart `feature/refonte-home` vs `test-theme-sapi-maison` (`git log --oneline test-theme-sapi-maison..feature/refonte-home`) pour confirmer que la branche = test + refonte, rien d'autre d'inattendu.
- **Produire un rapport clair** dans la réponse : « voici ce qui partirait en prod au-delà de la home », + un verdict « prêt / pas prêt » avec les points douteux listés.

### Étape 2 — ⛔ PAUSE : validation Robin
NE PAS merger master tant que Robin n'a pas confirmé le rapport de l'étape 1 (il revoit avec toi en session). Master = jamais de push sans accord explicite (règle projet).

### Étape 3 — Merge + prod (seulement après « go » explicite de Robin)
- Merger `feature/refonte-home` → `master` (merge commit, message clair « Refonte home juin 2026 »), `git push origin master`.
- **Déploiement prod** : le workflow `deploy-prod.yml` est en `workflow_dispatch` (manuel, pas d'auto sur push master). Donc après le push master, **Robin lance « Deploy to Production » depuis l'onglet GitHub Actions** (ou réactive le trigger si décidé). `gh` n'est pas installé en local → ne pas tenter de déclencher le workflow en CLI, l'indiquer à Robin.

### Étape 4 — Post-déploiement (actions Robin, à rappeler)
1. Vérifier la home en prod (atelier-sapi.fr), notamment que les images `2026/03` + `2026/06` (atelier, expédition, Robin shooting, carte-de-visite) s'affichent bien (elles existent en prod).
2. Tester en prod : une inscription newsletter + le form « Échanger avec Robin » d'une fiche produit → réception Brevo.
3. Re-soumettre le sitemap dans Google Search Console.
4. Brevo : maj séquence d'accueil −10 % pour inclure les sources `surmesure` + `ficheproduit`.

**Critères de succès :** rapport de branches livré et validé ; après go Robin, master à jour avec la refonte ; prod déployée ; checklist post-déploiement rappelée à Robin.
**👉 Robin :** revoir le rapport de branches avec Claude Code, puis donner le « go » pour le merge + lancer le workflow prod.

</details>

---

> **Série refonte DA (juin) — une tâche par section, validée sur test avant la suivante.** Mockups de référence dans `mockups/mockup-da-*.html`, décisions verrouillées dans `mockups/DECISIONS-MOCKUPS-DA.md` (section « ÉTAT VERROUILLÉ »). Focus DESKTOP (mobile = passe dédiée ensuite, dégrader proprement suffit). Ordre : **Atelier → Hero → Conseiller → Collections → Avis → Carte localisation → Cadeau+Actus → Newsletter** + 2 correctifs (voile Olivia, wording réassurance). Branche `feature/refonte-home`, push auto, jamais master.

## ✅ [FAIT 2026-06-08 — sur test] DA #2 — Hero : naming card verre dépoli + dots sous le nom (commit `51baabb`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :**
- **Markup** (front-page.php) : `.naming-card` restructurée en 2 rangées — `.naming-card__row` (flèche prev · `#carousel-naming-link` · flèche next) puis `.carousel-dots` **en dessous**. Wrapper `.card-controls` supprimé. JS carrousel inchangé (mêmes sélecteurs, juste déplacés dans le DOM).
- **CSS** : fond **verre dépoli sombre** (`rgba(40,33,27,.30)` + `blur(14px)` + bordure `rgba(255,255,255,.22)`, radius 22), `flex-direction:column`. Flèches + nom en **cream** (`--color-warm`). Dots **centrés sous le nom**, visibles sur sombre : inactif `rgba(255,255,255,.45)`, actif **orange allongé** (width 20, radius 10).
- **Cleanup** : règles `.card-controls` mortes supprimées (`display:contents`, ordering, `display:flex`), commentaires obsolètes corrigés. Grep `card-controls` → plus que des libellés à jour.
- **Vérifs** : grep 0 usage `.card-controls` en php/css (hors commentaires) ; CSS accolades 3786/3786 ; dots ≤520px toujours masqués (refonte #11 conservée).

### 👉 Action Robin
Valider sur test (desktop) : naming card en verre dépoli sombre, texte cream lisible, flèches+nom sur une rangée, dots centrés sous le nom (actif orange allongé). Puis → **DA #3 (Conseiller)**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #2 — Hero : naming card en verre dépoli + dots sous le nom
**Date :** 2026-06-08 · **Priorité :** haute · **⛔ Prérequis :** DA #1 validé par Robin.
**Mockup de référence :** `mockups/mockup-da-01-hero.html` (traitement verre dépoli de la variante B).
**Décision Robin :** garder le carrousel et la structure. Changer UNIQUEMENT la naming card : (1) remplacer son fond blanc translucide par un **verre dépoli** (fond sombre translucide + flou) ; (2) déplacer les **dots SOUS le nom** (plus dans la rangée entre les flèches).

**À faire :**
1. **Markup** (front-page.php ~l.474-495) — restructurer `.naming-card` en 2 rangées (retirer le wrapper `.card-controls`) :
```php
<div class="naming-card">
  <div class="naming-card__row">
    <button type="button" class="carousel-arrow carousel-arrow-prev" aria-label="Slide précédente">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
    </button>
    <a class="naming-link" href="#" id="carousel-naming-link" aria-label="Découvrir le modèle affiché"></a>
    <button type="button" class="carousel-arrow carousel-arrow-next" aria-label="Slide suivante">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
    </button>
  </div>
  <div class="carousel-dots">
    <?php for ($i = 0; $i < $total_slides; $i++) : ?>
      <button type="button" class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>" aria-label="Aller à la slide <?php echo ($i + 1); ?>"></button>
    <?php endfor; ?>
  </div>
</div>
```
(Le JS carrousel cible `.carousel-dot`, `#carousel-naming-link`, `.carousel-arrow-prev/next` : juste déplacés dans le DOM → rien à toucher côté JS.)

2. **CSS** — remplacer la règle `.naming-card` actuelle (fond `rgba(255,255,255,.8)`) par :
```css
.naming-card{
  pointer-events:auto;
  background:rgba(40,33,27,.30);
  -webkit-backdrop-filter:blur(14px);backdrop-filter:blur(14px);
  border:1px solid rgba(255,255,255,.22);
  border-radius:22px;
  padding:14px 22px;
  display:flex;flex-direction:column;align-items:center;gap:10px;
  width:520px;max-width:calc(100vw - 32px);
}
.naming-card__row{display:flex;align-items:center;gap:14px;width:100%}
```
Puis : garder `.naming-link` (déjà `color: var(--color-warm)`, flex:1, centré) ; ajouter `.naming-card .carousel-arrow{color:var(--color-warm)}` ; centrer les dots `.naming-card .carousel-dots{justify-content:center}`. Réconcilier les 2 définitions `.carousel-dot` existantes en UNE seule, visible sur fond sombre : inactif `background:rgba(255,255,255,.45);border:none;width:7px;height:7px;border-radius:50%`, actif `.carousel-dot.active{background:var(--color-orange);width:20px;border-radius:10px}`.

**Notes :** `.card-controls` n'existe plus → grep et supprimer sa règle CSS si morte. Le nom est déjà au format formatter (#12/P3), ne pas y retoucher. Dots masqués ≤520px mobile : déjà fait, garder. Pas de tiret cadratin, accolades équilibrées.

**Critères :** naming card en verre dépoli sombre (texte cream lisible), flèches + nom sur une rangée, dots centrés EN DESSOUS du nom, dot actif orange allongé.

### 👉 Action Robin
Valider sur test (desktop). Puis → DA #3 (Conseiller).

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA #3 — Conseiller : signature de Robin + réchauffe (commit `d43d6d8`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :**
- **Markup** (front-page.php, `.room-picker-inner`) : l'eyebrow « Ton projet » est remplacé par le bloc **`.conseiller-sig`** (pastille ronde Robin `2025/05/Robin-Sapi-A.jpg` + « Le conseil de Robin » + accroche Square Peg « Dis-moi pour quelle pièce, je te conseille »). Sous-titre **`.room-picker-sub`** tutoyé ajouté sous le `<h2>`. Chips (`.room-card`/`data-piece`), « ou », champ libre + **bouton flèche** intacts (pas de bouton « Conseille-moi »).
- **CSS** : composant **`.conseiller-sig*` nommé génériquement** (réutilisable au chantier suivant). Réchauffe : grain bois discret en fond (`.home-projet-section::before` z-index 0, contenu `.home-projet` z-index 1). Sous-titre **scopé `.home-projet .room-picker-sub`** (la version globale l.11162 sert la modale Conseiller → non touchée). Chips réchauffées au hover : crème chaud `#f4ead3` + ombre, en plus du border orange existant ; `background` ajouté à la transition.
- **Pièges traités** : token correct **`--shadow-card-hover`** (le `--shadow-hover` du mockup n'existe pas). `.room-picker-title` déjà `var(--color-wood-dark)` (l.11627). `.section-eyebrow` gardé défini (orphelin en php mais réservé).
- **Vérifs** : CSS accolades 3799/3799 ; tutoiement ; pas de tiret cadratin ; comportement `data-room-picker` / champ libre inchangé.

### 👉 Action Robin
Valider sur test (purger le cache page LSCache pour voir le nouveau markup). Si la signature plaît → chantier suivant (hors home) = la généraliser (modale tous états + page conseils + pill fiche produit). Puis home → **DA #4 (Collections)**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #3 — Conseiller « Pour quelle pièce ? » : signature de Robin + réchauffe
**Date :** 2026-06-08 · **Priorité :** haute · **⛔ Prérequis :** DA #2 validé.
**Mockup de référence :** `mockups/mockup-da-02-conseiller.html` (variante A).
**Décision Robin :** variante A (signature en en-tête). Structure conservée (chips + champ libre). Réchauffe via tokens globaux. Composant signature nommé GÉNÉRIQUEMENT (`conseiller-sig*`) car il sera généralisé ensuite (modale tous états + room picker page conseils + pill fiche produit = chantier SUIVANT, pas cette tâche).

**À faire :**
1. **Markup** — dans `.room-picker-inner` (front-page.php ~l.508), REMPLACER `<span class="section-eyebrow">Ton projet</span>` par le bloc signature, juste avant le `<h2 class="room-picker-title">` :
```php
<div class="conseiller-sig">
  <span class="conseiller-sig__avatar"><?php echo sapi_image('2025/05/Robin-Sapi-A.jpg', 'medium', ['alt' => 'Robin, artisan de l\'Atelier Sâpi', 'class' => 'conseiller-sig__img', 'loading' => 'lazy']); ?></span>
  <span class="conseiller-sig__text">
    <span class="conseiller-sig__who">Le conseil de Robin</span>
    <span class="conseiller-sig__hook">Dis-moi pour quelle pièce, je te conseille</span>
  </span>
</div>
```
Garder le `<h2 class="room-picker-title">`. Juste après le titre (avant `.room-picker-cards`), ajouter :
```php
<p class="room-picker-sub">Choisis une pièce, je te propose une sélection adaptée. Ou raconte-moi ton projet en quelques mots.</p>
```
NE PAS toucher aux chips (`.room-card`, `data-piece`), au « ou », ni au champ libre + son bouton flèche (`.room-picker-freetext__submit` : GARDER le bouton flèche actuel, PAS de bouton texte « Conseille-moi » — wording rejeté par Robin, l'accroche signature porte déjà le message).

2. **CSS** :
```css
/* ===== Conseiller — signature réutilisable (Robin) ===== */
.conseiller-sig{display:flex;align-items:center;gap:18px;justify-content:center;margin-bottom:18px}
.conseiller-sig__avatar{width:72px;height:72px;border-radius:50%;overflow:hidden;border:3px solid #fff;box-shadow:var(--shadow-card);flex-shrink:0}
.conseiller-sig__img{width:100%;height:100%;object-fit:cover;display:block}
.conseiller-sig__text{display:flex;flex-direction:column;text-align:left}
.conseiller-sig__who{font-size:12px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--color-wood)}
.conseiller-sig__hook{font-family:var(--font-display);font-size:32px;line-height:1;color:var(--color-wood-dark);margin-top:2px}
/* ===== Conseiller — réchauffe de la bande ===== */
.home-projet-section{position:relative}
.home-projet-section::before{content:"";position:absolute;inset:0;background-image:repeating-linear-gradient(92deg,rgba(139,115,85,.045) 0,rgba(139,115,85,.045) 1px,transparent 1px,transparent 7px);pointer-events:none;z-index:0}
.home-projet{position:relative;z-index:1}
.room-picker-sub{color:var(--color-wood-mid);font-size:15px;margin:0 auto 22px;max-width:560px}
```
Réchauffe des chips (hover crème chaud + ombre, en plus du border orange déjà là) :
```css
.home-projet .room-card{box-shadow:var(--shadow-card)}
@media (hover:hover){.home-projet .room-card:hover{background:#f4ead3;box-shadow:var(--shadow-hover)}}
```
Vérifier que `.room-picker-title` est bien `var(--color-wood-dark)` (pas noir pur).

**Notes :** photo Robin = provisoire (portrait carré propre à venir ; `object-fit:cover` rond gère le recadrage approximatif). Vérifier que le grain `::before` ne couvre pas le contenu (z-index). Tutoiement. Pas de tiret cadratin. Accolades équilibrées. `.section-eyebrow` reste défini (réutilisé ailleurs), on ne supprime que son usage ici.

**Critères :** signature de Robin (pastille ronde + « Le conseil de Robin » + accroche Square Peg) en tête de section, sous-titre tutoyé, chips réchauffées au hover, grain bois discret. On comprend tout de suite que c'est « le conseil de Robin ». Comportement Conseiller (data-room-picker, champ libre) intact.

### 👉 Action Robin
Valider sur test. Si la signature plaît → chantier suivant (hors home) = la généraliser (modale + page conseils + fiche produit). Puis home → DA #4 (Collections).

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA #4 — Collections : scrim allégé + carte « Sur mesure » + voile Star (commit `781141b`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :**
- **Scrim allégé** (`.collection-card .collection-details`) : opacités du dégradé de pied réduites d'~1/3 — base `0.9/0.7/0.35` → `0.6/0.45/0.22`, hover `0.92/0.75/0.4` → `0.62/0.5/0.27`. La photo respire, le titre reste lisible.
- **Carte « Sur mesure »** : ajoutée **en dernier** dans `.collections-grid` (front-page.php), `.collection-card collection-card--surmesure` → `/sur-mesure/`, photo `2025/09/Vincent-Ambiance3.jpg`, « Ton projet unique » + flèche. Réutilise `.collection-card` (hérite du peek 23.5% + hover), pas de CSS dédié nécessaire.
- **Voile Star** : un `.creation-star::after` existait déjà (dégradé `transparent 55% → .65`). Plutôt que dupliquer, j'ai **mis à jour son dégradé** vers la version progressive de la tâche (`to top, rgba(40,33,27,.55) 0% → .12 32% → transparent 55%`). `position:relative` et badge/label en `z-index:2` étaient déjà en place.
- **Vérifs** : CSS accolades 3799/3799 ; pas de tiret cadratin ; pas de changement site-wide (scopé collections home + Star).

### 👉 Action Robin
Valider sur test : scrim collections plus léger, carte « Sur mesure » en fin de carrousel, nom de la Star lisible. Puis → **DA #5 (Ils en parlent)**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #4 — Collections : scrim allégé + catégorie « Sur mesure » + voile Star
**Date :** 2026-06-08 · **Priorité :** haute · **⛔ Prérequis :** DA #3 validé.
**Mockup :** `mockups/mockup-da-03-collections.html` variante A. **Décisions Robin :** variante A (nom sur image, PAS de changement site-wide) + carte « Sur mesure » → `/sur-mesure/` + correctif voile local sur la Star (créations du moment).

**À faire :**
1. **Scrim allégé** : alléger d'environ un tiers le dégradé sombre de pied de carte (lire `.collection-card` / `.collection-details`, réduire l'opacité du gradient qui porte le titre). La photo respire plus.
2. **Carte Sur mesure** : après le `<?php endforeach; ?>` de `.collections-grid` (~l.565), ajouter EN DERNIER :
```php
<a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>" class="collection-card collection-card--surmesure">
  <div class="collection-visual">
    <?php echo sapi_image('2025/09/Vincent-Ambiance3.jpg', 'large', ['class' => 'collection-visual-img', 'loading' => 'lazy', 'alt' => 'Luminaire sur mesure, Atelier Sâpi']); ?>
  </div>
  <div class="collection-details">
    <h3>Sur mesure</h3>
    <div class="collection-meta"><span class="collection-count">Ton projet unique</span><span class="collection-btn">→</span></div>
  </div>
</a>
```
3. **Voile Star** : la carte `.creation-star` (~l.579) porte le nom blanc sur bois clair (contraste limite). Ajouter :
```css
.creation-star{position:relative}
.creation-star::after{content:"";position:absolute;inset:0;background:linear-gradient(to top,rgba(40,33,27,.55) 0%,rgba(40,33,27,.12) 32%,transparent 55%);z-index:1;pointer-events:none}
.creation-star .bento-bestseller-badge,.creation-star-label{z-index:2}
```
**Critères :** scrim plus léger, carte « Sur mesure » en fin de carrousel, nom de la Star lisible. Pas de tiret cadratin.
**👉 Robin :** valider → DA #5.

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA #5 — Ils en parlent : papiers à ombre douce + grain bois (commit `5f19fb4`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :**
- **Cartes sans bordure** : `.home-avis .testimonial-card` — `border` retirée (était `1px solid var(--color-gray-light)`), fond blanc + `box-shadow: var(--shadow-card)` conservés. Le **hover** (`translateY(-2px)` + `--shadow-card-hover`) est déjà fourni par la règle de base `.testimonial-card:hover` → effet « petits papiers posés ». Token correct `--shadow-card-hover` (le `--shadow-hover` de l'énoncé n'existe pas).
- **Réchauffe sans aplat crème** : `.home-avis` passe en `position:relative`, grain bois en filigrane via `::before` (`repeating-linear-gradient` 92deg rgba bois .04, `z-index:0`, `pointer-events:none`), contenu remonté avec `.home-avis > * { position:relative; z-index:1 }`. Fond reste transparent (pas de crème).
- **Inchangés** : badge Google, avis FR + avatars initiales (#12), zone presse `$press_refs` (markup non touché).
- **Vérifs** : tout scopé `.home-avis` → **base `.testimonial-card` (fiche produit) intacte** (vérifié : garde sa bordure + fond crème). CSS accolades 3802/3802 ; pas de tiret cadratin.

### 👉 Action Robin
Valider sur test : avis = papiers blancs à ombre douce (sans bordure, relief au survol) sur fond clair texturé bois, badge + logos presse intacts. Vérifier qu'une **fiche produit** n'a pas bougé. Puis → **DA #6 (Carte localisation)**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #5 — Ils en parlent : papiers à ombre douce (sans bordures), fond réchauffé
**Date :** 2026-06-08 · **Priorité :** haute · **⛔ Prérequis :** DA #4 validé.
**Mockup :** `mockups/mockup-da-05-avis.html` variante A. **Décision Robin :** variante A, garder les logos presse. PAS de fond crème.

**À faire :** (scopé `.home-avis`, NE PAS toucher aux styles partagés fiche produit)
1. **Cartes sans bordure** : lire `.home-avis .testimonial-card` ; retirer la `border`, la remplacer par `box-shadow: var(--shadow-card)` (hover `var(--shadow-hover)`), fond blanc. Effet « petits papiers posés ».
2. **Réchauffe sans aplat crème** : grain bois en filigrane sur `.home-avis` :
```css
.home-avis{position:relative}
.home-avis::before{content:"";position:absolute;inset:0;background-image:repeating-linear-gradient(92deg,rgba(139,115,85,.04) 0,rgba(139,115,85,.04) 1px,transparent 1px,transparent 7px);pointer-events:none;z-index:0}
.home-avis > *{position:relative;z-index:1}
```
3. **Conserver** badge Google, avis FR + avatars initiales (#12), zone presse `$press_refs`.
**Critères :** papiers blancs à ombre douce sur fond clair texturé bois, badge + logos presse intacts, fiche produit INCHANGÉE (vérifier). Pas de tiret cadratin.
**👉 Robin :** valider → DA #6.

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA #6 — Carte localisation : mini-carte bois (variante C) (commit `32d0a82`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :**
- **Markup** (`.quote-band`) : `.map-card` (fausse grille + pin pulse) remplacé par **`.loc-card`** — mini-carte SVG bois illustrée (fond, routes, bâtiments, pin orange avec ombre) + **invitation Square Peg** « À 15 min de Lyon, viens voir où ça se fabrique » en overlay bas, + pied (titre « Venir me voir à l'atelier » / adresse « 3 Rue Pierre Termier · Collonges-au-Mont-d'Or » / « Itinéraire → »). Lien Maps inchangé, bas-droite de la bande citation.
- **CSS** : bloc `.loc-card*` (token correct `--shadow-card-hover`, hovers wrappés `@media (hover:hover)`, flèche qui glisse au survol). Mobile ≤900px : card en flux centrée sous la citation.
- **Cleanup** : bloc `.map-card*` + keyframes `mapPinPulse` supprimés — **grep `map-card`/`mapPinPulse` = 0** (php + css).
- **Note** : la `.loc-card` est posée à `right:42px` (spec mockup variante C), pas alignée sur la gouttière 1600 comme l'ancienne — me dire si tu veux l'aligner sur le contenu. Citation/photo de Robin non touchées.
- **Vérifs** : CSS accolades 3798/3798 ; pas de tiret cadratin.

### 👉 Action Robin
Valider sur test : mini-carte bois soignée + invitation Square Peg, cliquable Maps, en bas-droite de la bande citation. Puis → **DA #7 (Cadeau + Actus)**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #6 — Carte localisation : mini-carte mixte (variante C)
**Date :** 2026-06-08 · **Priorité :** haute · **⛔ Prérequis :** DA #5 validé.
**Mockup :** `mockups/mockup-da-06-carte-localisation.html` variante C. On ne touche PAS à la citation/photo de Robin.

**À faire :** dans `.quote-band`, REMPLACER le bloc `<a class="map-card"> … </a>` (~l.796-808) par :
```php
<a class="loc-card" href="https://maps.app.goo.gl/a3MiaeoG3ySfyUQT9" target="_blank" rel="noopener noreferrer" aria-label="Venir me voir à l'atelier, voir l'itinéraire sur Google Maps">
  <div class="loc-media">
    <svg viewBox="0 0 520 280" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <rect width="520" height="280" fill="#E7DCC8"/>
      <path d="M -20 60 C 130 100, 190 30, 330 110 S 520 140, 560 120 L 560 190 C 420 170, 320 220, 200 190 S 40 220, -20 200 Z" fill="#D5C5A9"/>
      <g stroke="#C4B393" stroke-width="6" fill="none" stroke-linecap="round"><path d="M 60 0 L 130 100 L 270 140 L 360 280"/><path d="M 0 170 L 210 140 L 430 80 L 520 100"/></g>
      <g fill="#DCCDB3"><rect x="100" y="110" width="26" height="20" rx="3"/><rect x="310" y="92" width="28" height="22" rx="3"/><rect x="210" y="170" width="24" height="18" rx="3"/></g>
      <g transform="translate(265,128)"><ellipse cx="0" cy="34" rx="14" ry="4" fill="rgba(74,63,53,.18)"/><path d="M 0 30 C -16 6, -16 -10, 0 -10 C 16 -10, 16 6, 0 30 Z" fill="#E35B24"/><circle cx="0" cy="-2" r="6" fill="#FBF6EA"/></g>
    </svg>
    <div class="loc-invite">À 15 min de Lyon, viens voir où ça se fabrique</div>
  </div>
  <div class="loc-foot">
    <div><div class="loc-foot__ttl">Venir me voir à l'atelier</div><div class="loc-foot__adr">3 Rue Pierre Termier · Collonges-au-Mont-d'Or</div></div>
    <span class="loc-foot__go">Itinéraire <span class="loc-foot__arr">→</span></span>
  </div>
</a>
```
CSS (remplacer `.map-card*` par) :
```css
.loc-card{position:absolute;right:42px;bottom:36px;z-index:3;width:340px;border-radius:16px;overflow:hidden;box-shadow:var(--shadow-card);background:#fff;text-decoration:none;color:inherit;transition:.28s}
.loc-card:hover{box-shadow:var(--shadow-hover);transform:translateY(-2px)}
.loc-media{position:relative;height:150px;overflow:hidden}
.loc-media svg{width:100%;height:100%;object-fit:cover;display:block}
.loc-invite{position:absolute;left:0;right:0;bottom:0;background:linear-gradient(to top,rgba(74,63,53,.9),transparent);padding:26px 16px 12px;color:#fff;font-family:var(--font-display);font-size:20px;line-height:1}
.loc-foot{padding:14px 18px;display:flex;align-items:center;justify-content:space-between;gap:12px}
.loc-foot__ttl{font-weight:700;font-size:14.5px;color:var(--color-wood-dark)}
.loc-foot__adr{font-size:12px;color:var(--color-wood-mid);margin-top:2px}
.loc-foot__go{color:var(--color-orange);font-weight:600;font-size:13px;white-space:nowrap}
.loc-card:hover .loc-foot__arr{transform:translateX(4px);display:inline-block}
@media (max-width:900px){.loc-card{position:relative;right:auto;bottom:auto;width:auto;margin:30px auto 0}}
```
Grep `.map-card` → 0 après (markup + CSS).
**Critères :** fausse grille remplacée par mini-carte bois soignée + invitation Square Peg, cliquable Maps, en bas-droite de la bande citation. Pas de tiret cadratin.
**👉 Robin :** valider → DA #7.

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA #7 — Cadeau + Actus : objet iconique vs journal (variante C) (commit `c29b536`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :**
- **Markup** : section `.hero-bento.home-divers` / `.bento-container` remplacée par **`.home-cadeau-actus`** — `.gift-object` (bloc **orange iconique sans photo** : halo, badge « Offrir de la lumière », glyphe ✦, titre Square Peg, texte, CTA blanc) + `.news-journal` (**card journal claire** : photo, « Le journal de l'atelier · date », titre, chapô = `get_the_excerpt()` tronqué 26 mots, « Lire l'article → ») + **`.ca-allnews`** (« Voir toutes les actus → » **sorti de la card, centré dessous**). `$gift_card['url']` + WP_Query `flash-actu` conservés.
- **CSS** : nouveau bloc scopé `.home-cadeau-actus` (grille `.78fr/1.22fr`, mobile 1 col ≤880px). **Tokens corrigés** : `--shadow-card-hover` (pas `--shadow-hover`) et `--color-dark` (pas `--color-ink`) qui n'existent pas dans le thème.
- **Cleanup** : blocs morts supprimés — `.bento-giftcard*`, `.giftcard-badge/info/price`, `.bento-actu*`, règles grille `.home-divers`, + retrait des 3 classes (`.bento-giftcard/.bento-actu/.bento-conseil`) des media queries bento partagées. **Grep php/css = 0** (hors 1 commentaire). CSS accolades 3799/3799.
- **Note** : glyphe ✦ = lampe provisoire (OK selon énoncé). Pas de tiret cadratin.

### 👉 Action Robin
Valider sur test : cadeau orange compact iconique vs actus journal clair, CTA actus hors card centré dessous — les deux ne se ressemblent plus. Puis → **DA #8 (Newsletter)**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #7 — Cadeau + Actus : « objet iconique vs journal » (variante C)
**Date :** 2026-06-08 · **Priorité :** haute · **⛔ Prérequis :** DA #6 validé.
**Mockup :** `mockups/mockup-da-07b-cadeau-actus.html` variante C (validée « parfaite »). Cadeau = bloc orange iconique (sans photo) ; actus = card journal claire ; CTA « Voir toutes les actus » SORTI de la card, centré dessous.

**À faire :** remplacer le contenu de `<section class="hero-bento home-divers"><div class="bento-container"> … </div></section>` (~l.812-856) par :
```php
<section class="home-cadeau-actus">
  <div class="ca-grid">
    <?php if ($gift_card) : ?>
    <a href="<?php echo esc_url($gift_card['url']); ?>" class="gift-object">
      <span class="gift-object__halo" aria-hidden="true"></span>
      <span class="gift-object__badge">Offrir de la lumière</span>
      <span class="gift-object__body">
        <span class="gift-object__lamp" aria-hidden="true">✦</span>
        <span class="gift-object__title">La carte cadeau</span>
        <span class="gift-object__text">Tu hésites sur le modèle ? Offre une carte cadeau : la bonne personne choisira son luminaire, allumé à la main rien que pour elle.</span>
        <span class="gift-object__cta">Offrir une carte cadeau <span class="arr">→</span></span>
      </span>
    </a>
    <?php endif; ?>
    <?php
    $last_actu = new WP_Query(['posts_per_page'=>1,'post_status'=>'publish','category_name'=>'flash-actu','orderby'=>'date','order'=>'DESC']);
    if ($last_actu->have_posts()) : $last_actu->the_post();
    ?>
    <a href="<?php the_permalink(); ?>" class="news-journal">
      <span class="news-journal__photo"><?php if (has_post_thumbnail()) echo get_the_post_thumbnail(get_the_ID(), 'large', ['loading'=>'lazy','alt'=>get_the_title()]); ?></span>
      <span class="news-journal__body">
        <span class="news-journal__meta"><span class="news-journal__eyebrow">Le journal de l'atelier</span><span class="news-journal__date">· <?php echo esc_html(get_the_date('j F Y')); ?></span></span>
        <span class="news-journal__title"><?php echo esc_html(get_the_title()); ?></span>
        <span class="news-journal__chapo"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 26)); ?></span>
        <span class="news-journal__read">Lire l'article <span class="arr">→</span></span>
      </span>
    </a>
    <?php wp_reset_postdata(); endif; ?>
  </div>
  <div class="ca-allnews"><a href="<?php echo esc_url(home_url('/actus/')); ?>" class="ca-allnews__btn">Voir toutes les actus <span class="arr">→</span></a></div>
</section>
```
CSS (variante C, scopé) :
```css
.home-cadeau-actus{max-width:1400px;margin:0 auto;padding:5rem 3rem}
.ca-grid{display:grid;grid-template-columns:.78fr 1.22fr;gap:26px;align-items:stretch}
.gift-object{position:relative;border-radius:20px;overflow:hidden;background:linear-gradient(150deg,#E35B24 0%,#c4481b 100%);color:#fff;padding:40px 36px;display:flex;flex-direction:column;justify-content:space-between;box-shadow:var(--shadow-card);text-decoration:none;transition:.28s}
.gift-object:hover{box-shadow:var(--shadow-hover)}
.gift-object__halo{position:absolute;right:-40px;top:-40px;width:220px;height:220px;border-radius:50%;background:radial-gradient(circle,rgba(255,222,170,.55),transparent 68%);pointer-events:none}
.gift-object__badge{position:relative;z-index:2;align-self:flex-start;display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.3);border-radius:50px;padding:7px 15px;font-size:11.5px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
.gift-object__body{position:relative;z-index:2;display:block}
.gift-object__lamp{font-size:46px;line-height:1;display:block;margin:18px 0 6px}
.gift-object__title{font-family:var(--font-display);font-size:54px;line-height:.9;display:block;margin-bottom:12px}
.gift-object__text{font-size:14.5px;color:#ffeede;max-width:320px;display:block;margin-bottom:24px}
.gift-object__cta{display:inline-flex;align-items:center;gap:9px;background:#fff;color:var(--color-wood-dark);font-weight:600;font-size:14.5px;border-radius:50px;padding:13px 26px}
.news-journal{display:flex;flex-direction:column;background:#fff;border-radius:20px;overflow:hidden;box-shadow:var(--shadow-card);text-decoration:none;color:inherit;transition:.28s}
.news-journal:hover{box-shadow:var(--shadow-hover)}
.news-journal__photo{display:block;height:230px;overflow:hidden}
.news-journal__photo img{width:100%;height:100%;object-fit:cover;transition:transform .5s}
.news-journal:hover .news-journal__photo img{transform:scale(1.04)}
.news-journal__body{padding:28px 34px 30px;display:block}
.news-journal__meta{display:flex;align-items:center;gap:12px;color:var(--color-wood)}
.news-journal__eyebrow{font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
.news-journal__date{color:var(--color-wood-mid);font-size:12.5px;font-weight:500}
.news-journal__title{font-weight:700;font-size:25px;color:var(--color-wood-dark);line-height:1.2;display:block;margin:10px 0}
.news-journal__chapo{font-size:14.5px;color:var(--color-ink);display:block;margin-bottom:16px}
.news-journal__read{color:var(--color-orange);font-weight:600;font-size:14.5px;display:inline-flex;gap:7px;align-items:center}
.ca-allnews{display:flex;justify-content:center;margin-top:22px}
.ca-allnews__btn{display:inline-flex;align-items:center;gap:9px;background:transparent;border:1.5px solid var(--color-wood);color:var(--color-wood-dark);font-weight:600;font-size:14.5px;border-radius:50px;padding:13px 26px;text-decoration:none}
.ca-allnews__btn:hover{background:var(--color-wood);color:#fff}
.home-cadeau-actus .arr{display:inline-block;transition:transform .25s}
.gift-object:hover .arr,.news-journal:hover .arr,.ca-allnews__btn:hover .arr{transform:translateX(4px)}
@media (max-width:880px){.ca-grid{grid-template-columns:1fr}.home-cadeau-actus{padding:3rem 1.25rem}}
```
Cleanup : supprimer `.home-divers`, `.bento-giftcard`, `.giftcard-badge`, `.giftcard-info`, `.bento-actu*` si plus utilisés (grep .php).
**Notes :** glyphe `✦` = lampe provisoire OK ; chapô = `get_the_excerpt()` tronqué 26 mots ; CTA cadeau → `$gift_card['url']`. Pas de tiret cadratin.
**Critères :** cadeau orange compact iconique vs actus journal clair, CTA actus hors card centré dessous. Les deux ne se ressemblent plus.
**👉 Robin :** valider → DA #8.

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA #8 — Newsletter : bande bois chaud (variante B) (commit `3449982`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :**
- **Markup** : `.newsletter-kinetic` devient `.newsletter--band` — photo atelier `2025/04/IMG_5851.jpg` en fond (flou + opacity .16) + `.newsletter__veil` bois + `.newsletter__inner` (centré : eyebrow « La lettre de l'atelier », titre Square Peg « Reste dans la lumière », sous-titre tutoyé, **form + script AJAX `#newsletter-form` conservés à l'identique**, fineprint « Désinscription en un clic… »). Ancien header « 06 Restez informés » retiré.
- **CSS** : bloc `.newsletter--band` (dégradé bois chaud `#a98a64→#8B7355`, radius 18, min-height 380, centré). Titre `text-transform:none` (anti-uppercase global). **Bouton orange / input blanc** conservés. Feedback AJAX posé sur **pastille blanche** pour lisibilité sur le bois (le JS pose la couleur du texte).
- **Vérifs** : JS intact (`id="newsletter-form"` + `getElementById('newsletter-form')`) → inscription Brevo non touchée. CSS accolades 3811/3811 ; pas de tiret cadratin.
- **Note** : ambiance bois chaud/clair, distincte de l'atelier sombre (écart creusé comme voulu).

### 👉 Action Robin
Valider sur test : newsletter bois chaud lumineux, aérée, centrée, photo atelier en filigrane, seul point chaud = bouton orange. **Tester une inscription** (feedback Brevo lisible sur le bois). **Série DA #1→#8 terminée.** Restent : correctif réassurance (ci-dessus), **passe mobile**, **passe Yoast**, **go-live**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #8 — Newsletter : fond bois chaud + photo filigrane (variante B)
**Date :** 2026-06-08 · **Priorité :** haute · **⛔ Prérequis :** DA #7 validé.
**Mockup :** `mockups/mockup-da-08-newsletter.html` variante B. Bois chaud/clair (l'atelier est sombre → on creuse l'écart).

**À faire :** dans `<section class="newsletter-kinetic">` (~l.859), GARDER le `<form>` + son `<script>` AJAX, refondre l'habillage :
```php
<section class="newsletter-kinetic newsletter--band">
  <?php echo sapi_image('2025/04/IMG_5851.jpg', 'large', ['class' => 'newsletter__bg', 'loading' => 'lazy', 'alt' => '']); ?>
  <div class="newsletter__veil" aria-hidden="true"></div>
  <div class="newsletter__inner">
    <span class="newsletter__eyebrow">La lettre de l'atelier</span>
    <h2 class="newsletter__title">Reste dans la lumière</h2>
    <p class="newsletter-subtitle">Une fois par mois, je te raconte un nouveau modèle, un coin de l'atelier, une astuce déco. Pas de spam, juste l'essentiel.</p>
    [GARDER ICI le <form class="newsletter-form" id="newsletter-form"> existant à l'identique : honeypot + input email + bouton S'inscrire + .newsletter-feedback + le <script> AJAX]
    <p class="newsletter__fineprint">Désinscription en un clic. Je ne partage jamais ton adresse.</p>
  </div>
</section>
```
(Retirer l'ancien `.section-header-kinetic` « 06 Restez informés » au profit de cet en-tête centré.)
CSS :
```css
.newsletter--band{position:relative;overflow:hidden;background:linear-gradient(135deg,#a98a64,#8B7355);border-radius:18px;min-height:380px;display:flex;align-items:center;justify-content:center;text-align:center;padding:70px 24px}
.newsletter--band .newsletter__bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:blur(3px);transform:scale(1.06);opacity:.16}
.newsletter--band .newsletter__veil{position:absolute;inset:0;background:rgba(139,115,85,.55);z-index:2}
.newsletter--band .newsletter__inner{position:relative;z-index:3;max-width:560px}
.newsletter--band .newsletter__eyebrow{font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#ffe7cc;display:block;margin-bottom:12px}
.newsletter--band .newsletter__title{font-family:var(--font-display);font-size:clamp(2.6rem,5vw,3.2rem);font-weight:400;color:#fff;line-height:1;margin-bottom:12px}
.newsletter--band .newsletter-subtitle{color:#fbf2e4;font-size:15.5px;margin-bottom:30px}
.newsletter--band .newsletter__fineprint{font-size:12px;color:#f3e8d8;margin-top:16px;opacity:.85}
```
Bouton « S'inscrire » reste orange (#12), input fond blanc. Vérifier la lisibilité du feedback AJAX sur fond bois.
**Notes :** ne PAS casser le JS (id `newsletter-form`, AJAX Brevo). Pas de tiret cadratin, accolades équilibrées.
**Critères :** newsletter bois chaud lumineux, aérée, centrée, photo atelier en filigrane flou, seul point chaud = bouton orange. Ambiance distincte de l'atelier sombre. Inscription Brevo OK.
**👉 Robin :** valider. Restera : correctif réassurance (ci-dessous), passe mobile, passe Yoast, go-live.

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA — Correctif réassurance : wording humain (single-line)
**Résultat (branche `feature/refonte-home`, poussé sur test) :** retour Robin → **une seule accroche par item, sans sous-ligne** (la version accroche+sous-ligne testée jugée « trop lourde »). Wording final validé par Robin dans `inc/template-robin-bandeau-v2.php` :
- Livraison rapide en 48-72h (camion)
- Façonné main à Lyon sous 5 jours (engrenage, `.is-mobile-hidden`)
- 30 jours pour changer d'avis (retour, `.is-mobile-hidden`)
- Paiement sécurisé (cadenas)
CSS `.reassurance-text/.label/.sub` (scopé `.robin-bandeau`) supprimé. ⚠️ NE PAS confondre avec le `.reassurance-text` GLOBAL (functions.php + style.css ~15941) = autre composant (panier/fiche), intact. Ordre DOM + mobile (camion + cadenas) inchangés. CSS 3811/3811.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA — Correctif réassurance : wording artisan (desktop)
**Date :** 2026-06-08 · **Priorité :** normale · indépendant.
Dans `inc/template-robin-bandeau-v2.php` (4 items réassurance), wording artisan validé (accroche + sous-ligne) :
- « Façonné main à Lyon » / en moins de 5 jours
- « Chez toi en 48-72h » / expédié avec soin
- « Tu changes d'avis ? » / retours sous 30 jours
- « Paiement tranquille » / transaction sécurisée
⚠️ Mobile potentiellement trop long → wording desktop, ajustement mobile dans la passe mobile. Si le template n'a pas de sous-ligne, ajouter un `<span>` secondaire ou se limiter aux accroches. Tutoiement, pas de tiret cadratin.
**👉 Robin :** valider.

</details>

---

## ✅ [FAIT 2026-06-08 — sur test] DA #1 — L'atelier : immersion par la lumière (commit `043a0ec`)
**Résultat (branche `feature/refonte-home`, poussé sur test) :** la section `.home-atelier` passe de la bande crème (#15) à une **bande sombre immersive** (mockup-da-04c).
- **Markup** : `<section class="home-atelier home-atelier--lumiere" id="home-atelier">` — pile de 6 couches `.home-atelier__bg` (défaut + 1/étape, chacune une `sapi_image` `large`), double voile (`__veil` latéral + `__veil-bottom`), colonne texte sombre `__inner` (52%, header « 04 L'atelier » + eyebrow + titre Square Peg + 2 paragraphes verbatim **avec les 4 liens catégories inline** + CTA `hero-cta--wood`), et les **5 pills d'étape** (`<button class="atelier-step">`) en bas-droite avec la phrase manuscrite en `title`.
- **CSS** : nouveau bloc « immersion par la lumière » (voiles, crossfade `.is-on` opacity .6s, pills glassmorph orange au hover/focus). Liens SEO stylés clairs (souligné warm → orange au hover, anti-bleu). Mobile ≤900px : colonne empilée, pills statiques.
- **JS** : IIFE ajoutée dans le bloc de scripts final — `mouseenter`/`focus`/`click` d'une pill → crossfade vers sa photo ; `mouseleave`/`focusout` du ruban → retour au fond défaut.
- **Cleanup** : supprimé `.home-atelier--band`, ancien `.home-atelier__bg` (image unique), `.process-ribbon*`, `.process-tile*` (0 usage php confirmé par grep). `.atelier-band-title` redéfini scopé `--lumiere`, `.storytelling-text*` gardés.
- **Vérifs** : grep classes mortes → 0 dans les .php ; CSS accolades 3787/3787 ; PHP non lintable en local (php absent) mais blocs foreach/endforeach équilibrés, swap propre.
- ⚠️ **Photos `2026/03/` et `2026/06/` = 404 sur le TEST** (clone d'avril) → couches Assemblage/Expédition vides sur test, **OK en prod**. Le fond par défaut `2025/04/A7404411.jpg` existe.

### 👉 Action Robin
Valider sur `test.atelier-sapi.fr` (survole/clique les étapes → le fond se fond vers la photo de l'étape ; sortie du ruban → retour au défaut). Fournir une vraie photo de fond chaude si `A7404411` ne convient pas (variable `$atelier_default_img`). Une fois validé → **DA #2 (Hero)**.

<details><summary>Énoncé original</summary>

## [TÂCHE] DA #1 — L'atelier : immersion par la lumière (fond qui change au survol des étapes)
**Date :** 2026-06-08 · **Priorité :** haute
**Mockup de référence (à OUVRIR) :** `mockups/mockup-da-04c-atelier.html`.

**Contexte :** Refonte de la section `.home-atelier` (front-page.php ~l.627-661). On abandonne la bande à voile crème. Nouveau principe : grande image immersive en fond + **colonne texte sombre franche à gauche (toujours lisible)** ; au survol d'une des 5 pills d'étape (en bas à droite), le fond **crossfade** vers la photo de cette étape. Les 4 liens catégories restent des liens texte INLINE dans le paragraphe (déjà le cas). Plus de titre « Mon processus artisanal », plus de tuiles : les étapes deviennent les pills interactives.

**À faire :**

### A. Markup — remplacer le `<section class="home-atelier home-atelier--band"> … </section>` (l.627-661) par :
```php
<!-- L'atelier — immersion par la lumière (refonte DA, mockup-da-04c) -->
<?php
$atelier_default_img = '2025/04/A7404411.jpg'; // fond par défaut = luminaire allumé (modifiable ; sinon une photo d'atelier chaude)
// $process_steps : [num, label, photo, alt, phrase manuscrite] — repris à l'identique de l'existant
$process_steps = [
  ['01', 'Dessin',        '2025/05/IMG_1928-e1761747188966.png', "Dessin d'un luminaire en bois, Atelier Sâpi",          "Tout commence par un trait de crayon"],
  ['02', 'Découpe laser', '2025/05/IMG_7638.jpg',                'Découpe laser du bois pour luminaire',                 "Le laser suit mon dessin au dixième près"],
  ['03', 'Finitions',     '2025/09/Poncage.jpg',                 "Ponçage manuel d'un luminaire en bois, Atelier Sâpi",  "Le ponçage, c'est ma méditation"],
  ['04', 'Assemblage',    '2026/03/Robin-a-lassemblage.jpg',     'Robin assemble un luminaire dans son atelier à Lyon',  "Chaque pièce s'emboîte sans une vis"],
  ['05', 'Expédition',    '2026/06/Expedition.jpg',              "Luminaire emballé prêt pour l'expédition, Atelier Sâpi","Emballé comme si c'était pour ma mère"],
];
?>
<section class="home-atelier home-atelier--lumiere" id="home-atelier">
  <div class="home-atelier__bgstack" aria-hidden="true">
    <span class="home-atelier__bg is-on" data-key="default"><?php echo sapi_image($atelier_default_img, 'large', ['class' => 'home-atelier__bgimg', 'alt' => '', 'loading' => 'lazy']); ?></span>
    <?php foreach ($process_steps as $i => $step) : ?>
      <span class="home-atelier__bg" data-key="<?php echo (int)($i + 1); ?>"><?php echo sapi_image($step[2], 'large', ['class' => 'home-atelier__bgimg', 'alt' => '', 'loading' => 'lazy']); ?></span>
    <?php endforeach; ?>
  </div>
  <div class="home-atelier__veil" aria-hidden="true"></div>
  <div class="home-atelier__veil-bottom" aria-hidden="true"></div>

  <div class="home-atelier__inner">
    <div class="section-header-kinetic"><span class="section-num">04</span><h2 class="section-title-kinetic">L'atelier</h2></div>
    <span class="atelier-eyebrow">L'atelier · Lyon</span>
    <h3 class="atelier-band-title">Des sculptures lumineuses</h3>
    <p class="storytelling-text">Du croquis à l'assemblage final, chaque pièce est façonnée dans mon atelier lyonnais. Le bois prend forme sous mes mains, la lumière fait le reste.</p>
    <p class="storytelling-text storytelling-text--seo">Je dessine et fabrique à la commande des <a href="<?php echo esc_url($sapi_cat_url('suspensions')); ?>">suspensions</a>, <a href="<?php echo esc_url($sapi_cat_url('appliques')); ?>">appliques</a>, <a href="<?php echo esc_url($sapi_cat_url('lampesaposer')); ?>">lampes à poser</a> et <a href="<?php echo esc_url($sapi_cat_url('lampadaires')); ?>">lampadaires</a> en bois massif. Chaque luminaire est découpé au laser puis assemblé à la main : le peuplier clair ou l'okoumé chaleureux filtrent la lumière et dessinent des ombres uniques.</p>
    <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="hero-cta hero-cta--wood">Découvrir l'artisan</a>
  </div>

  <div class="atelier-steps" id="atelier-steps">
    <?php foreach ($process_steps as $i => $step) : ?>
    <button type="button" class="atelier-step" data-bg="<?php echo (int)($i + 1); ?>" title="« <?php echo esc_attr($step[4]); ?> »">
      <span class="atelier-step__n"><?php echo esc_html($step[0]); ?></span>
      <span class="atelier-step__t"><?php echo esc_html($step[1]); ?></span>
    </button>
    <?php endforeach; ?>
  </div>
</section>
```
⚠️ Le helper `$sapi_cat_url` est déjà défini juste au-dessus (l.621), le garder. Garder le texte SEO + le CTA `hero-cta--wood` à l'identique.

### B. CSS — remplacer le bloc `.home-atelier--band` / `.atelier-band-title` / `.process-ribbon*` / `.process-tile*` par :
```css
/* ===== Refonte DA — L'atelier immersion par la lumière (mockup-da-04c) ===== */
.home-atelier--lumiere{position:relative;overflow:hidden;min-height:620px;display:flex;align-items:stretch}
.home-atelier--lumiere .home-atelier__bgstack{position:absolute;inset:0;z-index:0}
.home-atelier--lumiere .home-atelier__bg{position:absolute;inset:0;opacity:0;transition:opacity .6s ease}
.home-atelier--lumiere .home-atelier__bg.is-on{opacity:1}
.home-atelier--lumiere .home-atelier__bgimg{width:100%;height:100%;object-fit:cover;object-position:center;display:block}
.home-atelier--lumiere .home-atelier__veil{position:absolute;inset:0;z-index:1;background:linear-gradient(95deg,rgba(40,33,27,.95) 0%,rgba(40,33,27,.92) 32%,rgba(40,33,27,.6) 52%,rgba(40,33,27,.14) 74%,rgba(40,33,27,0) 100%)}
.home-atelier--lumiere .home-atelier__veil-bottom{position:absolute;inset:0;z-index:1;background:linear-gradient(to top,rgba(40,33,27,.7) 0%,rgba(40,33,27,.18) 26%,transparent 46%)}
.home-atelier--lumiere .home-atelier__inner{position:relative;z-index:2;width:52%;padding:60px 56px 150px;display:flex;flex-direction:column;justify-content:center;color:#fff;max-width:none;margin:0}
.home-atelier--lumiere .section-header-kinetic{padding:0;max-width:none;margin:0 0 .5rem}
.home-atelier--lumiere .section-title-kinetic,.home-atelier--lumiere .section-num{color:#fff}
.home-atelier--lumiere .atelier-eyebrow{font-size:13px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#f0b07e}
.home-atelier--lumiere .atelier-band-title{font-family:var(--font-display);font-weight:400;font-size:clamp(2.6rem,5vw,4.1rem);line-height:.92;color:#fff;margin:8px 0 18px}
.home-atelier--lumiere .storytelling-text{color:#ece3d6;max-width:460px}
.home-atelier--lumiere .storytelling-text--seo a{color:#fff;text-decoration:none;border-bottom:1px solid rgba(240,176,126,.6);padding-bottom:1px}
.home-atelier--lumiere .storytelling-text--seo a:hover{color:#f0b07e;border-bottom-color:var(--color-orange)}
.home-atelier--lumiere .hero-cta{margin-top:6px}
.atelier-steps{position:absolute;z-index:3;right:30px;bottom:30px;max-width:64%;display:flex;flex-wrap:wrap;justify-content:flex-end;gap:10px}
.atelier-step{display:flex;align-items:center;gap:10px;background:rgba(40,33,27,.55);-webkit-backdrop-filter:blur(5px);backdrop-filter:blur(5px);border:1px solid rgba(255,255,255,.2);border-radius:50px;padding:8px 18px 8px 8px;cursor:pointer;transition:.25s;font-family:inherit}
.atelier-step:hover,.atelier-step:focus-visible{background:rgba(227,91,36,.92);border-color:var(--color-orange);transform:translateY(-2px)}
.atelier-step__n{width:28px;height:28px;border-radius:50%;background:var(--color-orange);color:#fff;font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;transition:.25s;flex-shrink:0}
.atelier-step:hover .atelier-step__n,.atelier-step:focus-visible .atelier-step__n{background:#fff;color:var(--color-orange)}
.atelier-step__t{color:#f3ead9;font-size:13px;font-weight:600;transition:.25s}
.atelier-step:hover .atelier-step__t,.atelier-step:focus-visible .atelier-step__t{color:#fff}
@media (max-width:900px){
  .home-atelier--lumiere{flex-direction:column;min-height:auto}
  .home-atelier--lumiere .home-atelier__inner{width:100%;padding:48px 26px 24px}
  .atelier-steps{position:static;max-width:none;justify-content:flex-start;padding:0 26px 36px}
}
```

### C. JS — ajouter dans le bloc de scripts inline en bas de front-page.php (pattern IIFE existant) :
```js
(function(){
  var sec = document.getElementById('home-atelier'); if(!sec) return;
  var layers = {}; sec.querySelectorAll('.home-atelier__bg').forEach(function(el){ layers[el.dataset.key] = el; });
  function show(k){ Object.keys(layers).forEach(function(x){ layers[x].classList.remove('is-on'); }); (layers[k]||layers['default']).classList.add('is-on'); }
  var steps = sec.querySelector('.atelier-steps');
  sec.querySelectorAll('.atelier-step').forEach(function(b){
    b.addEventListener('mouseenter', function(){ show(b.dataset.bg); });
    b.addEventListener('focus', function(){ show(b.dataset.bg); });
    b.addEventListener('click', function(){ show(b.dataset.bg); }); // tap mobile
  });
  if(steps){
    steps.addEventListener('mouseleave', function(){ show('default'); });
    steps.addEventListener('focusout', function(e){ if(!steps.contains(e.relatedTarget)) show('default'); });
  }
})();
```

### D. Cleanup
Supprimer de `style.css` les règles désormais mortes : `.home-atelier--band`, l'ancienne `.home-atelier__bg` (image unique pleine section), `.process-ribbon`, `.process-ribbon-title`, `.process-tile`, `.process-tile__img`, `.process-tile__photo`, `.process-tile__label` (grep d'abord : 0 usage dans les .php après refonte). Garder `.atelier-band-title` (réutilisé, redéfini ci-dessus) et `.storytelling-text*`.

**Notes / pièges :**
- Photos d'étapes `2026/03/` et `2026/06/` = 404 sur le TEST (clone d'avril) → sur test certaines couches seront vides, c'est OK sur la prod. Robin peut les uploader sur le test pour prévisualiser.
- Vérifier que `sapi_image` accepte `'class'` (oui) ; on met le `data-key` sur le `<span>` parent, pas sur l'`<img>`, donc pas de souci de passthrough d'attribut.
- Section désormais SOMBRE (plus crème) : l'ourlet P20 ne s'y applique pas. Pas de tiret cadratin. Accolades PHP/CSS équilibrées avant push.
- Mobile : non fignolé (passe dédiée plus tard), juste dégrader proprement (colonne empilée, pills statiques).

**Critères de succès :**
- Section atelier sombre immersive, colonne texte gauche 100 % lisible, fond = luminaire allumé par défaut.
- Survol/clic d'une pill d'étape (bas-droite) → le fond crossfade vers la photo de l'étape ; sortie du ruban → retour au fond par défaut.
- 4 liens catégories inline dans le paragraphe (maillage SEO) fonctionnels, stylés clairs (pas bleus).
- Plus aucune trace des tuiles/ribbon ni du titre « Mon processus artisanal » ; CSS mort purgé.

### 👉 Action Robin
Valider sur `test.atelier-sapi.fr` (survole les étapes). Penser à fournir une vraie photo de fond par défaut chaude si A7404411 ne te convient pas (variable `$atelier_default_img`). Une fois validé → DA #2 (Hero).

</details>

---

## ↩️ RETOUR COWORK — Refonte home : série + ajustements terminés (2026-06-05, sur test)
**Branche `feature/refonte-home`** (tout poussé, déployé sur test.atelier-sapi.fr ; **jamais mergé master**). Dernier commit `80306cd`.

**✅ Fait depuis le dernier point :**
- **Série DA #12 → #15 complète** (détails dans les entrées FAIT ci-dessous) : polish #12, room picker #13, bande citation Robin #14, bande atelier immersive #15.
- **Vague d'ajustements validés par Robin** (commits successifs) :
  - **Section L'atelier** : photo de fond `2025/05/Retouchee1.jpg`, textes alignés à gauche + titre sans majuscules, conteneur calé sur le standard (1600px), vignettes process +30% + titre « Mon processus artisanal », photos process Finitions/Assemblage/Expédition mises à jour.
  - **Créations du moment** : photos des 2 cards droite agrandies (min-height 400, dominantes) + textes centrés et agrandis.
  - **Avis « Ils en parlent »** : passé en **fond blanc** (cassait 3 bandes crème d'affilée) ; liens « Laisser un avis / Voir les N avis » déplacés en haut à droite, plus discrets.
  - **Zone presse « Ils parlent de nous »** créée dans la section avis — **5 références** : Maisons Actuelle, L'univers de la maison, Le Progrès, Région Auvergne-Rhône-Alpes, Collonges-au-Mont-d'Or (logos N&B → couleur au survol, cliquables). Pilotée par le tableau `$press_refs` (1 ligne/réf : name/url/logo) → **facile à compléter**.
  - **Room picker** : ajout « **Chambre enfant** » (icône nounours, slug `chambre-enfant`) ; chips un peu plus larges ; **effet photo-au-survol retiré proprement** (revenu à la bande crème simple).
  - **H2 home** plus épais (Montserrat 700).

**⚠️ À FAIRE / SAVOIR CÔTÉ ROBIN :**
1. **Images `2026/06/` cassées sur le TEST** (zone presse + photo Expédition) : le test est un **clone d'avril**, il n'a pas ces fichiers (vérifié curl : 200 prod / 404 test). **OK sur la prod**. Pour prévisualiser sur test → uploader ces images dans la médiathèque du site de test.
2. **Cache avis** : transient 6h sur `sapi_get_google_reviews()` — le tri « français d'abord » (P14) ne se voit qu'après vidage/expiration.
3. **Brevo (rappel ancien)** : maj séquence d'accueil −10 % pour inclure `surmesure` + `ficheproduit`.

**RESTE pour finir la refonte home (dans l'ordre) :** zone presse OK → **passe mobile complète** → **passe Yoast** (titre/meta home) → **go-live** (merge master + déploiement prod + re-soumission GSC). + nettoyage CSS mort résiduel (fragments `.bento-*`, `.process-*`, `.home-atelier-bg`).

---

> **Série refonte home #12 → #15 (06/06)** — issue de l'audit DA trié point par point avec Robin (`mockups/AUDIT-DA-HOME-JUIN-2026.md`, ses verdicts après chaque `>>`). Exécuter DANS L'ORDRE, une tâche à la fois, push après chacune. Focus DESKTOP (la passe mobile viendra après) : les refontes de sections doivent juste dégrader proprement en mobile, sans fignolage. Jamais master.

## ✅ [FAIT 2026-06-05 — sur test] Refonte home #12 — Polish DA validé (commits `bb68a2f` + `51a4f59`)
**Résultat :** P3+P9 formatter (carousel aligné ; article→Square Peg entier ; format prénom/surnom restreint aux 4 cat luminaires via `data-categories`/`data-product-cat`, **sans toucher au PHP mes-creations** — accessoires en Montserrat simple) ; P6 hover icône room cards orange (home) ; P7 zoom doux de l'IMG collections (1.04, fini le zoom du wrapper) ; P8 pieds des 3 cards créations alignés (flex) ; P13 avatars avis → **initiales sur disque** (scopé `.home-avis`, fiche produit garde ses photos) ; P14 avis **français d'abord** (`lang` exposé par `sapi_get_google_reviews`, tri stable + heuristique accents ; home : shuffle puis usort stable fr-d'abord) ; P18 **curseur custom supprimé** (markup+JS+CSS, 0 ref) ; P19 bouton newsletter orange (hover assombri) ; P20 ourlet `border-top` sur les bandes crème.
⚠️ **Cache avis** : `sapi_get_google_reviews()` a un transient 6h — le tri fr-d'abord (P14) ne prendra effet qu'après expiration/vidage du cache (P13 initiales OK immédiatement). À vider côté Robin si besoin de voir P14 tout de suite.
**Vérifs** : accolades front 73/73, functions 891/891, CSS 3738/3738. Commits par chemins explicites (WIP mes-creations autre fenêtre intact).

<details><summary>Énoncé original</summary>

**À faire :**
1. **P3 Naming card hero** : un seul format pour toutes les slides produit : prénom/surnom officiel (formatter), SANS flèche « → » dans le texte (doublon des chevrons). Slides promo : titre libre inchangé. (Voir la construction dans `homepage-carousel.js` + `SAPI_CAROUSEL_DATA`.)
2. **P9 Formatter** (`assets/product-name-formatter.js`) — 2 règles nouvelles : (a) si le premier mot est un article (La, Le, L'), tout le nom en Square Peg (fini « LA » criard sur La Merveilleuse) ; (b) le formatage prénom/surnom ne s'applique QU'AUX produits des 4 catégories luminaires (suspensions, appliques, lampesaposer, lampadaires) ; les autres (accessoires, ex. Ampoule Poire) restent en Montserrat simple. Pour (b), exposer la catégorie dans le markup là où c'est nécessaire (attribut `data-product-cat` sur les conteneurs de nom, côté PHP) plutôt que deviner côté JS. Vérifier ensuite hero + créations + /mes-creations/ + 1 page catégorie : aucun nom ne doit régresser.
3. **P6 Hover room cards** : `.home-projet .room-card:hover .room-card-icon { background: rgba(227, 91, 36, 0.12); color: var(--color-orange); }`. *(Sera repris tel quel dans la refonte #13 de la section : appliquer quand même, ça servira de base.)*
4. **P7 Collections** : au hover de `.collection-card`, zoom doux du visuel : `.collection-card:hover .collection-visual-img { transform: scale(1.04); }` + transition 0.6s, dans `@media (hover: hover)`.
5. **P8 Créations** : aligner les pieds des 3 cards. Scopé `.creations-grid` UNIQUEMENT : `.creations-grid .product-card-link { display: flex; flex-direction: column; height: 100%; }`, `.creations-grid .product-media { flex: 1; }`, infos/CTA calés en bas.
6. **P13 Avis** : scopé `.home-avis`, remplacer les `<img>` avatars Google par des initiales sur disque 36px : fond `var(--color-warm)`, texte `var(--color-wood)`, font-weight 700. Zéro bleu Google.
7. **P14 Avis** : avis en français d'abord (vérifier si `sapi_get_google_reviews()` expose un code langue, sinon heuristique sur le texte ; tri stable, l'anglais après).
8. **P18 Curseur custom** : SUPPRIMER markup `.cursor-custom` (front-page.php), CSS et JS associés (grep `cursor-custom|cursor-dot|cursor-outline` → 0 partout).
9. **P19 CTA** : état CIBLE à vérifier/imposer : « Toutes les créations » = orange ; « Découvrir l'artisan » = bois (`hero-cta--wood`) ; bouton newsletter « S'inscrire » = orange (`.newsletter-submit-kinetic`, hover assombri).
10. **P20 Bandes crème** : `border-top: 1px solid rgba(147, 125, 104, 0.1);` sur `.section-band--warm` et `.home-projet-section`.

**Pièges :** scopes stricts (fiche produit/catégories/page Star intactes, sauf l'effet voulu du formatter P9-b). Aucun tiret cadratin. Accolades équilibrées. Après push : vérifier 1 fiche produit + /mes-creations/ + 1 catégorie.
**👉 Robin :** revue desktop rapide, puis lancer #13.

</details>

---

## ✅ [FAIT 2026-06-05 — sur test] Refonte home #13 — Room picker immersif photographique (commit `bff5718`)
**Résultat :** « Ton projet » devient une bande photographique immersive.
- **Data** : 1 photo d'ambiance par pièce via taxonomie `media_room` (slugs alignés salon/cuisine/chambre/bureau/entree/escalier — le `?piece=` y mappe déjà), 6 `WP_Query` attachments (`fields=ids`, `no_found_rows`), fallback par pièce → **fond défaut (salon)**, fallback dur si rien tagué.
- **Markup** : `<section class="home-projet-section home-projet--immersif">` min-height 560, couches `.projet-bg` empilées (1/pièce + `--default`, `opacity 0`/`.on 1`, transition 0.7s) + `.projet-scrim` sombre. Room cards → **chips pills** (fond blanc translucide, radius 50, icône 18px, texte wood-dark). **Câblage Conseiller intact** (mêmes `<a>` href + `data-piece` + `data-room-picker`/`data-room-picker-freetext`).
- **JS** : crossfade au `mouseenter` d'une chip (**lazy** : `background-image` posé au 1er survol via `data-bg`), retour au défaut au `mouseleave`. **Desktop only** (`hover:hover`) ; mobile = fond défaut fixe.
- **Typo fond sombre** : h2 blanc + shadow, eyebrow warm, « ou »/filets blancs translucides, input freetext blanc translucide. P20 (ourlet) désactivé sur cette section.
- **Vérifs** : accolades PHP 89/89, CSS 3758/3758. ⚠️ Perf : 6 requêtes attachments `orderby rand` (OK car homepage page-cachée ; variété par chargement).

**MAJ (révision, commit `c980b18`)** : retour Robin — deux photos (hero + section) s'enchaînaient, trop lourd. Nouvelle approche **validée** : au **repos = bande crème classique** (texte sombre, cards blanches, ourlet P20) ; au **survol d'une chip**, la photo de la pièce apparaît **derrière** et le « fond » crème devient un **voile semi-transparent** (`.projet-veil` rgba warm .8) par-dessus. Plus de fond photo par défaut, plus de scrim sombre / chips pills / texte blanc. Crossfade lazy + câblage Conseiller inchangés.

### 👉 Action Robin
Valider sur test (desktop : survol Salon/Cuisine/etc. → la photo de la pièce transparaît sous le voile crème ; mobile : bande crème fixe). ⚠️ **Dépend des photos taguées `media_room`** : pièce sans photo → retombe sur le fond salon ; me dire lesquelles manquent. Voile à 0.8 — ajustable si tu veux la photo plus/moins présente. Puis **lancer #14**.

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #13 — Room picker photographique (idée B validée)
**Date :** 2026-06-06 · **Branche :** `feature/refonte-home` · **Priorité :** haute
**Cible visuelle :** `mockups/mockup-16-immersion-B-C-D.html`, section « IDÉE B ». ⚠️ C'est une cible, pas du code à copier : réutiliser les variables/classes du thème.

**Contexte :** La section « Ton projet » passe de chips blanches sur bande crème à une **bande photographique immersive** : photo d'ambiance en fond, scrim sombre, chips pills blanches, et au survol d'une chip le fond crossfade vers une ambiance de la pièce correspondante. Le câblage Conseiller (`data-room-picker`, `data-piece`, `data-room-picker-freetext`) ne doit PAS bouger.

**À faire :**
1. **Data** : pour chacun des 6 slugs du room picker (salon, cuisine, chambre, bureau, entree, escalier), récupérer 1 photo d'ambiance via la taxonomie `media_room` (attachments tagués, chantier S28). Lister d'abord les slugs réels des termes `media_room` (get_terms) et mapper. Fallback par pièce : une photo d'ambiance générique (la même que le fond par défaut). Fond par défaut : une ambiance chaude au choix parmi les taguées salon.
2. **Markup** : conserver la structure logique actuelle de `.home-projet-section` (eyebrow, h2, cards/chips, « ou », freetext) mais : section pleine largeur `min-height: 560px`, couches de fond empilées (1 div par pièce + 1 défaut, `background-size: cover`, `opacity 0`, `.on { opacity: 1 }`, transition 0.7s) + scrim `linear-gradient(180deg, rgba(31,24,18,.55), rgba(31,24,18,.3) 45%, rgba(31,24,18,.55))`. Les room cards deviennent des chips pills (fond `rgba(255,255,255,.92)`, radius 50px, texte wood-dark, icône conservée en 18px) — MÊMES balises `<a>` avec MÊMES attributs href/data-piece.
3. **JS** : au `mouseenter` d'une chip, activer la couche de fond correspondante ; au `mouseleave` de la section, retour au fond défaut. **Lazy** : ne définir le `background-image` d'une couche qu'au premier survol (data-src), sauf le fond défaut (chargé direct). Desktop only (`hover: hover`) ; en mobile/touch : fond défaut fixe, chips inchangées, aucun crossfade.
4. **Typo sur fond sombre** : h2 blanc + text-shadow léger ; eyebrow en `var(--color-warm)` ; « ou » et filets en blanc translucide ; input freetext fond `rgba(255,255,255,.94)`.
5. **Cleanup** : les styles `.home-projet`/`.room-card` de l'ancienne version qui ne servent plus → adapter, ne pas laisser de mort. L'ourlet P20 ne s'applique plus à cette section (elle n'est plus crème).

**Critères :** survol Salon/Cuisine/etc. = l'ambiance change derrière (vraies photos taguées), wording et comportements Conseiller intacts, lisibilité parfaite du h2 et des chips, mobile propre (fond fixe). Perf : une seule image chargée à l'arrivée.
**👉 Robin :** valider sur test, puis lancer #14.

</details>

---

## ✅ [FAIT 2026-06-05 — sur test] Refonte home #14 — Bande citation Robin pleine page + card carte (commit `7536ff7`)
**Résultat (dispo A) :**
- **Section** entre « Ils en parlent » et le bento divers : photo de Robin en fond via `sapi_image` (variable **`$quote_band_img = '2025/05/Robin-Sapi-A.jpg'` PROVISOIRE** — changer ce chemin suffit) + scrim 90deg `rgba(31,24,18,.72→.45→.2)`. min-height 72vh.
- **Contenu** (max-width 1180, padding généreux, aligné gauche) : guillemet « Square Peg 96px orange ; citation « sauce pour les pâtes » Square Peg blanc clamp(2.4–4rem) ; signature **Robin** / artisan · Lyon ; CTA orange pill « Les conseils de Robin » → `/conseils-eclaires/` + lien discret souligné « Faire connaissance → » → `/lumiere-dartisan/` (stylés explicitement, anti a:hover bleu).
- **Card carte** (absolute bas-droite, 280px, blanc translucide, radius 14, ombre) : mini-carte **SVG inline** (4 routes `#DECDAF`/`#E8DCC8` sur `#F5EFE4`) + **pin orange avec pulse CSS** (sur span wrapper pour que le `::after` rende) + « Venir me voir à l'atelier » + « Collonges-au-Mont-d'Or… » + « Itinéraire → » vers Maps (`target=_blank rel=noopener`). **Pas d'iframe.** Mobile (≤900px) : card en flux centrée sous la citation.
- **Bento divers** : carte **Conseil retirée** → reste **Carte cadeau (span 8) + Flash actu (span 4)** en desktop ; mobile = 1 colonne (giftcard `1/-1` base conservée, override 8/4 en `min-width:769px` pour éviter le bug mobile). _À ajuster sur rendu si le 8/4 ne plaît pas._
- **Cleanup** : bloc CSS `.bento-conseil*` supprimé (0 usage php). Fragments media-query `.bento-conseil` laissés (CSS mort inoffensif).
- **Vérifs** : `img` global = `height:auto` sans `!important` → `.quote-band__bg { height:100% }` (classe) gagne, image de fond OK. Accolades PHP 89/89, CSS 3775/3775.

### 👉 Action Robin
Valider sur test (desktop : citation lisible sur la photo, card carte cliquable bas-droite ; mobile : citation puis card empilées). ⚠️ **Photo PROVISOIRE** — prévoir la vraie photo de Robin (je changerai `$quote_band_img`). Tu peux aussi me dire si tu préfères l'agencement bento **8/4** ou autre. Puis **lancer #15**.

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #14 — Bande citation Robin pleine page + card carte (mockup-17, disposition A)
**Date :** 2026-06-06 · **Branche :** `feature/refonte-home` · **Priorité :** haute
**Cible visuelle :** `mockups/mockup-17-citation-robin-pleine-page.html`, **disposition A** (citation à gauche, photo lisible à droite). Robin ajustera scrim/cadrage avec la photo finale.

**Contexte :** La citation « sauce pour les pâtes » (actuelle card Conseil du bento) devient une **bande pleine largeur sur photo de Robin**, avec la card carte « Venir me voir à l'atelier » posée dessus (décision P12). Le bento divers ne garde que Carte cadeau + Flash actu.

**À faire :**
1. **Nouvelle section** entre « Ils en parlent » et le bento divers : `min-height: 72vh`, photo de fond = `2025/05/Robin-Sapi-A.jpg` (PROVISOIRE, sera remplacée — prévoir un point d'entrée simple pour changer l'image), scrim `linear-gradient(90deg, rgba(31,24,18,.72), rgba(31,24,18,.45) 50%, rgba(31,24,18,.2))`.
2. **Contenu** (z-index au-dessus, max-width 1180 centré, padding généreux, aligné gauche) : guillemet « en Square Peg ~96px `var(--color-orange)` ancré au-dessus de la 1re ligne ; citation en Square Peg blanc `clamp(2.4rem, 4.6vw, 4rem)`, max-width ~620px, text-shadow léger : « Éclairer une pièce, c'est un peu comme choisir la bonne sauce pour ses pâtes : tout est une question de préférence et de dosage ! » ; signature « **Robin** / artisan de l'Atelier Sâpi · Lyon » ; CTA orange pill « Les conseils de Robin » → `/conseils-eclaires/` + lien discret souligné blanc cassé « Faire connaissance → » → `/lumiere-dartisan/` (styler explicitement, piège a:hover bleu).
3. **Card carte** (absolute bas-droite, ~280px, fond `rgba(255,255,255,.96)`, radius 14, ombre) : mini-carte ILLUSTRÉE en SVG inline (3-4 routes en courbes `#DECDAF`/`#E8DCC8` sur fond `#F5EFE4`, reprendre les paths du mockup-17) + pin orange avec pulse CSS ; texte « Venir me voir à l'atelier » + « Collonges-au-Mont-d'Or, à 15 min de Lyon · sur rendez-vous » + lien « Itinéraire → » vers `https://maps.app.goo.gl/a3MiaeoG3ySfyUQT9` (`target="_blank" rel="noopener noreferrer"`). Pas d'iframe Google (perf + RGPD). Mobile : la card passe en flux sous la citation, centrée.
4. **Bento divers** : supprimer la card Conseil (`bento-conseil`). Réagencer : Carte cadeau `grid-column: 1 / -1` (bannière) + Flash actu… lire les spans restants et proposer l'agencement le plus propre (ex. actu en 12 dessous, ou giftcard 8 + actu 4). À ajuster sur rendu, le noter dans le résultat.
5. **Cleanup** : styles `.bento-conseil*` orphelins → grep puis supprimer si 0 usage. P16 (guillemets) est rendu obsolète par cette refonte : ne rien faire dessus.

**Critères :** l'enchaînement Ils en parlent → bande citation (voix + visage + lieu) → bento → newsletter se lit naturellement ; citation lisible sur la photo ; card carte cliquable ; bento à 2 cards propre ; mobile dégrade proprement (citation puis card empilées).
**👉 Robin :** valider sur test (et prévoir la vraie photo), puis lancer #15.

</details>

---

## ✅ [FAIT 2026-06-05 — sur test] Refonte home #15 — L'atelier en bande immersive voile crème (commit `594568a`)
**Résultat :** la section L'atelier abandonne le duo texte/photo → bande immersive calquée sur `.category-editorial`.
- **Image de fond** pleine section via `sapi_image` (variable **`$atelier_band_img = '2026/02/Bandeau-2.jpg'` PROVISOIRE** — changer ce chemin suffit, Robin fournira une photo d'atelier) z0 + **voile `::before` crème EXACTEMENT comme `.category-editorial`** (gradient 135deg .92/.88/.92) z1. **Variante sombre en commentaire CSS** (`.home-atelier--band::before`) pour basculer.
- **Contenu centré** (max-width 1000, z2) : header « 04 L'atelier », titre Square Peg « Des sculptures lumineuses » clamp(2.6–3.6rem), **2 paragraphes verbatim** (storytelling + SEO avec ses 4 liens intacts), CTA « Découvrir l'artisan » `hero-cta hero-cta--wood`.
- **Ruban process** : 5 cartes flip → **5 vignettes** (`flex:1`, blanc translucide .85, radius 12, `--shadow-card`) photo 110px + label « 01 Dessin ». **Phrases manuscrites conservées en attribut `title`** (tooltip natif). Flips supprimés (markup + CSS + JS tap).
- **Cleanup CSS mort** (0 usage php confirmé) : `.atelier-duo/-story/-photo/-media/-maps-link/-label/-story-title`, `.process-flip*`, `.home-atelier--photo-bg` (ancien test), `.home-atelier` retiré des groupes #8 padding.
- **Mobile** : vignettes 2 colonnes, padding réduit.
- **Vérifs** : `.home-atelier__bg { height:100% }` (classe) bat `img{height:auto}` → image OK. Accolades PHP 85/85, CSS 3758/3758.

### 👉 Action Robin
Valider sur test (image perceptible sous le voile crème, texte lisible, 4 liens SEO, ruban 5 vignettes + survol = tooltip phrase). ⚠️ **Image PROVISOIRE** `2026/02/Bandeau-2.jpg` — fournis une vraie photo d'atelier (je change `$atelier_band_img`). Variante sombre dispo en 1 ligne si tu préfères. **Série #12→#15 terminée.** Restent : **zone presse** (dès que tu fournis les références), **passe mobile complète**, **passe Yoast**, **go-live**.

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #15 — Bande atelier immersive, voile crème (idée C validée)
**Date :** 2026-06-06 · **Branche :** `feature/refonte-home` · **Priorité :** haute
**Cible visuelle :** `mockups/mockup-16-immersion-B-C-D.html`, section « IDÉE C », **voile crème** (choix Robin : même design que le storytelling des pages catégories). Garder la variante sombre EN COMMENTAIRE CSS pour pouvoir basculer.

**Contexte :** La section L'atelier abandonne le duo texte/photo : elle devient une bande immersive calquée sur `.category-editorial` (image de fond + voile crème quasi-opaque + contenu centré). La photo de Robin n'y figure plus (elle vit dans la bande citation #14). Les 5 cartes flip du process sont remplacées par un ruban de vignettes.

**À faire :**
1. **Section** : conserver le header kinetic « 04 L'atelier » et le helper `$sapi_cat_url`. Remplacer le contenu par : image de fond pleine section (PROVISOIRE : `2026/02/Bandeau-2.jpg`, remplaçable facilement — Robin fournira une photo d'atelier) en `position: absolute; inset: 0; object-fit: cover; z-index: 0` + voile `::before` EXACTEMENT comme `.category-editorial::before` (gradient 135deg `rgba(254,253,251,.92)` / `rgba(251,246,234,.88)` / `rgba(250,246,240,.92)`, z-index 1). Variante sombre en commentaire : `rgba(40,31,23,.82)/rgba(58,46,35,.74)/rgba(40,31,23,.85)`.
2. **Contenu** (z-index 2, max-width 1000 centré) : titre Square Peg « Des sculptures lumineuses » (`clamp(2.6rem, 5vw, 3.6rem)`, wood-dark) ; les 2 paragraphes EXISTANTS repris verbatim (storytelling + SEO avec ses 4 liens et leurs styles) ; CTA « Découvrir l'artisan » en `hero-cta hero-cta--wood`.
3. **Ruban process** : 5 vignettes (`flex: 1`, fond `rgba(255,255,255,.85)`, radius 12, ombre `--shadow-card`) : photo 110px (les 5 photos actuelles des flips) + label « 01 Dessin » etc. Les phrases manuscrites des versos sont conservées en attribut `title` de chaque vignette (tooltip natif), on ne les perd pas. SUPPRIMER les cards flip (`.process-flip*` markup + CSS + JS tap) après grep.
4. **Retraits** : `.atelier-duo`, `.atelier-media/.atelier-photo` (photo de Robin), `.atelier-maps-link` s'il reste, `.atelier-story*` devenus inutiles → adapter/supprimer le CSS mort (grep avant).
5. **Mobile** : vignettes en 2 colonnes wrap, padding réduit. Propre sans fignolage.

**Critères :** bande immersive fidèle au design category-editorial (image perceptible sous le voile crème), texte parfaitement lisible, 4 liens SEO intacts, ruban process avec les 5 photos + tooltips, plus aucune trace des flips ni de l'ancienne photo, CSS mort purgé.
**👉 Robin :** valider sur test. Ensuite : zone presse (dès que tu fournis les références), passe mobile complète, passe Yoast, go-live.

</details>

## ✅ [FAIT 2026-06-05 — sur test] Refonte home #11 — Corrections audit design (commits `b211e21` A-C + `f1399a2` D-E)
**A. 🚨 Fix bloquant mobile** : `.home-divers .bento-giftcard` `span 12` → **`1 / -1`** (survit au passage 1 colonne mobile → fini la citation Conseil écrasée à 175px).
**B. Hiérarchie CTA** : variante `.hero-cta--wood` (bois) appliquée à « Découvrir l'artisan » ; « Toutes les créations » reste orange → 1 seul CTA orange navigation par écran.
**C. Collections desktop** : cards `30%`→`23.5%` (peek de la 5e) + flèches prev/next dans le header (réutilise `.carousel-arrow`, scopées light, JS `scrollBy`), masquées en mobile.
**D. Hero carousel** : 1 produit/catégorie (2e interleave retiré → promos + 4 produits) ; **photos portrait écartées** (`wp_get_attachment_metadata`, `width>=height`, sinon photo/produit suivant) ; overlay renforcé ton chaud `rgba(31,24,18,.45→.72)` ; dots naming card masqués `≤520px` (nom complet).
**E. Quick wins** : footer `&mdash;`→`&middot;` ; bandeau réassurance mobile = **2 items FIXES** (Livraison 48-72h + Paiement sécurisé) via `.is-mobile-hidden` statique dans le template + randomisation JS désactivée (`bandeau-reassurance.js`) ; newsletter **centrée desktop** + padding vertical réduit (6/3→4/2.5rem) ; hover room-cards home en **accent orange** (global reste wood) ; alt texts nettoyés (0 tiret cadratin visible).
**Fichiers** : front-page.php, style.css, footer.php, inc/template-robin-bandeau-v2.php, assets/bandeau-reassurance.js. Accolades PHP 72/72, CSS 3739/3739. **NON créé** : zone presse (attend contenu Robin).
⚠️ Contexte multi-fenêtre : commits faits avec chemins explicites pour ne pas emporter le WIP mes-creations de l'autre fenêtre. Reste du CSS mort `.home-atelier--photo-bg` (test annulé) à nettoyer plus tard.

### 👉 Action Robin
Re-revue desktop + **mobile 390px** sur test (bento divers 1 colonne lisible, bandeau 2 items, nom modèle entier, collections peek+flèches, CTA artisan bois, newsletter centrée). Ensuite : zone presse (quand tu fournis les parutions), passe Yoast, go-live.

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #11 — Corrections audit design (bug mobile bloquant + CTA + collections + hero + quick wins)
**Date :** 2026-06-05
**Priorité :** haute (contient un bug mobile bloquant)
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**Contexte :** Audit design complet réalisé sur le rendu réel de test (desktop + mobile 390px). Robin valide la correction de tout en une tâche. La zone presse du mockup attend du contenu réel : NE PAS la créer ici.

**À faire :**

### A. 🚨 Fix bento divers cassé en mobile (bloquant)
Diagnostic confirmé : `.home-divers .bento-giftcard { grid-column: span 12; }` (style.css ~l.8177, HORS media query, spécificité 0,2,0) bat le reset mobile `.bento-giftcard { grid-column: span 1; }` (~l.11987, 0,1,0) → la grille garde 12 tracks en mobile, la citation Conseil s'écrase dans une colonne de 175px.
**Fix** : dans le bloc `@media (max-width: 768px)` existant, ajouter `.home-divers .bento-giftcard { grid-column: span 1; }` (ou passer la règle ~8177 en `grid-column: 1 / -1;` qui survit au passage 1 colonne — au choix, mais vérifier le résultat).
**Vérif** : à 390px, carte cadeau / citation Conseil / flash actu empilées en 1 colonne pleine largeur, citation lisible.

### B. Hiérarchie CTA — règle : 1 seul CTA orange « navigation » par écran
1. Créer la variante :
```css
.hero-cta--wood { background: var(--color-wood); }
@media (hover: hover) { .hero-cta--wood:hover { background: var(--color-wood-dark); } }
```
(si `.hero-cta` utilise `var(--gradient-cta)`, la variante doit l'écraser : `background: var(--color-wood);` suffit, vérifier au rendu.)
2. Appliquer `hero-cta hero-cta--wood` au CTA « Découvrir l'artisan » (`.atelier-story`). « Toutes les créations » (`.creations-cta`) RESTE orange.

### C. Collections desktop — affordance du carrousel
Deux volets :
1. **Peek** : passer les cards de `flex: 0 0 30%` à `flex: 0 0 23.5%` (desktop uniquement) → 4 cards entières + un bord de 5e visible = on comprend qu'il y a une suite. (Mobile inchangé.)
2. **Flèches** : ajouter des flèches prev/next en desktop sur `.collections-kinetic` (réutiliser les styles/SVG des `.carousel-arrow` de la naming card hero, posées à droite du header de section), avec un petit JS `scrollBy({ left: ±largeur_card, behavior: 'smooth' })` sur `.collections-grid`. Masquées en mobile (les dots existants suffisent).

### D. Hero carousel — 5 slides max, cadrage, mobile
1. **Réduire à 1 produit par catégorie** (au lieu de 2) : dans la prep data de `front-page.php`, ne garder que `$products_by_category[$cat_slug][0]` (supprimer la 2e boucle d'interleave) → promos + 4 slides produits.
2. **Écarter les photos portrait** : dans la boucle de sélection, vérifier les dimensions via `wp_get_attachment_metadata($image_id)` et ne retenir la photo que si `width >= height` (sinon essayer la photo ambiance suivante du produit ; si aucune paysage, passer au produit suivant de la catégorie).
3. **Overlay slides claires** : renforcer le bas du `.carousel-overlay` à `rgba(31, 24, 18, 0.45)` minimum (lisibilité du texte blanc sur photos claires type La Merveilleuse).
4. **Mobile ≤520px** : masquer les dots de la naming card (`.naming-card .carousel-dots { display: none; }` dans la MQ) pour que le nom du modèle s'affiche en entier ; garder les flèches.

### E. Quick wins
1. **Footer** : remplacer le tiret cadratin du copyright (« — Tous droits réservés ») par « · ». Puis grep `—` et `–` sur les fichiers du thème modifiés par la refonte : doit retourner 0 dans les contenus visibles.
2. **Bandeau réassurance mobile** : actuellement 2 items ALÉATOIRES parmi 4 (`inc/template-robin-bandeau-v2.php`). Remplacer par 2 items FIXES : « Livraison 48-72h » + « Paiement sécurisé » (les plus vendeurs). Desktop : 4 items inchangés.
3. **Newsletter** : centrer la section en desktop (header + sous-titre + form), elle flotte à gauche dans une fin de page très vide. Au passage, réduire légèrement son padding vertical pour compenser la hauteur prise par la section atelier (#10).
4. **Hover room-cards** : vérifier que `.room-card:hover` applique bien `border-color: var(--color-orange)` (ou wood) + `translateY(-2px)` + `--shadow-card-hover` sur la home ; si l'orange n'y est pas, l'ajouter scopé `.home-projet .room-card:hover`.

**Notes / pièges :**
- NE PAS créer la zone presse (attend le contenu de Robin). NE PAS toucher aux styles fiche produit / catégories / page Star.
- Micro-liens « Découvrir → » des cards en orange 13px : signalés sous AA par l'audit, mais on NE CHANGE PAS (cohérence avec /mes-creations/), juste le noter.
- Aucun tiret cadratin. Accolades équilibrées. Vérifier le rendu 390px ET desktop après chaque volet.

**Critères de succès :**
- Mobile 390px : bento divers en 1 colonne lisible ; nom du modèle entier dans la naming card ; bandeau = Livraison 48-72h + Paiement sécurisé.
- Desktop : « Découvrir l'artisan » en bois (1 seul CTA orange navigation par écran) ; collections avec peek + flèches fonctionnelles ; newsletter centrée.
- Hero : max 5-6 slides (promos + 4 produits), aucune photo portrait, texte lisible sur slides claires.
- Footer sans tiret cadratin.

### 👉 Action Robin
Re-revue desktop + mobile sur test. Ensuite il restera : la zone presse (quand tu fournis les parutions), la passe Yoast, et le go-live.

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #10 — Section L'atelier (photo/lien dissociés, process flip, plus haute)
**Résultat (branche `feature/refonte-home`, commit `eb59991`) :**
1. **Photo / lien Maps dissociés** : `.atelier-photo` passe de `<a>` à `<div>` (label « L'atelier · Lyon » overlay conservé) ; lien discret `.atelier-maps-link` (« Voir l'atelier sur Google Maps → ») sous la photo ; photo + lien dans un `<div class="atelier-media">` (1 seul enfant de grille). Lien stylé bois→orange au hover (anti `a:hover` bleu global).
2. **Eyebrow retiré** : `<span class="section-eyebrow">Mon atelier à Lyon</span>` supprimé du markup. **Classe `.section-eyebrow` conservée** dans style.css (réutilisable).
3. **Process → 5 cartes à retourner** (variante H) : `.process-flips`/`.process-flip` (rotateY 3D). **Flip au survol** (desktop), **au tap** (mobile) et **au clavier** (Enter/espace) via JS inline dans l'IIFE existant. Cleanup : `.process-strip`/`.process-tile*` (#7) supprimés (0 occurrence repo). **MAJ (commit `c3ab939`)** : faces inversées sur retour Robin → **recto = fond bois clair + numéro en grand + titre de l'étape**, **verso = photo** (au survol). Phrase manuscrite retirée.
4. **Section plus haute** : `.atelier-photo` min-height 300→**440** ; `.atelier-duo` gap 40→**56** ; `.hero-bento.home-atelier` **padding-bottom 5.5rem** (padding-top 6rem de #8 conservé) ; cartes process à 210px + margin-top 48px.
- **Vérifs** : accolades PHP 67/67, CSS 3716/3716 ; `.atelier-photo` = `<div>` ; markup eyebrow retiré, CSS section-eyebrow gardé.

### 👉 Action Robin
Revue de la section L'atelier sur test (desktop + **mobile : teste le flip au tap**) : photo non cliquable + lien Maps discret dessous, plus d'eyebrow, 5 cartes qui se retournent (phrase manuscrite au verso), section nettement plus haute. Si OK : passe Yoast, puis **go-live**.

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #10 — Section L'atelier : photo/lien dissociés, process en cartes à retourner, section plus haute
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).

**Contexte :** Retours Robin sur la section 04 L'atelier + choix validé pour le process : **variante H, cartes à retourner** (recto photo de l'étape, verso une phrase manuscrite de Robin sur fond bois sombre). Les 5 phrases ci-dessous sont VALIDÉES par Robin, les reprendre exactement.

**À faire :**

### 1. Dissocier la photo de l'atelier et le lien Google Maps
Actuellement `.atelier-photo` est un `<a>` vers Maps : photo de Robin + clic sortant = mélange confus.
- Transformer `.atelier-photo` en `<div>` (plus de lien sur la photo). Le label « L'atelier · Lyon » reste en overlay.
- Sous la photo, ajouter un petit lien discret :
```php
<a class="atelier-maps-link" href="https://maps.app.goo.gl/a3MiaeoG3ySfyUQT9" target="_blank" rel="noopener noreferrer">Voir l'atelier sur Google Maps →</a>
```
(Envelopper photo + lien dans un `<div class="atelier-media">` pour rester un seul enfant de grille.)
```css
.atelier-maps-link {
  display: inline-block;
  margin-top: 10px;
  font-size: 0.85rem;
  color: var(--color-wood);
  text-decoration: underline;
  text-underline-offset: 2px;
}
@media (hover: hover) {
  .atelier-maps-link:hover { color: var(--color-orange); }
}
```
(⚠️ styler le lien explicitement : piège connu du `a:hover` global bleu.)

### 2. Supprimer l'eyebrow « Mon atelier à Lyon »
Retirer le `<span class="section-eyebrow">Mon atelier à Lyon</span>` de `.atelier-story` (il encombre ; le signal géo vit déjà dans le texte, le label photo et le lien Maps). GARDER la classe `.section-eyebrow` dans style.css (réutilisable ailleurs).

### 3. Process → cartes à retourner (variante H validée)
REMPLACER le bloc `.process-strip` (tuiles + labels) par :
```php
<div class="process-flips">
  <?php
  $process_steps = [
    ['01', 'Dessin',        '2025/05/IMG_1928-e1761747188966.png', "Dessin d'un luminaire en bois, Atelier Sâpi",      "Tout commence par un trait de crayon"],
    ['02', 'Découpe laser', '2025/05/IMG_7638.jpg',                'Découpe laser du bois pour luminaire',             "Le laser suit mon dessin au dixième près"],
    ['03', 'Finitions',     '2025/03/P_SLM_XL_det5.jpg',           "Finitions manuelles d'un luminaire en bois",       "Le ponçage, c'est ma méditation"],
    ['04', 'Assemblage',    '2025/05/Robin-Sapi-A.jpg',            'Robin assemble un luminaire dans son atelier à Lyon', "Chaque pièce s'emboîte sans une vis"],
    ['05', 'Expédition',    '2025/07/Claudine-bandeau-1.jpg',      "Luminaire Claudine prêt pour l'expédition",        "Emballé comme si c'était pour ma mère"],
  ];
  foreach ($process_steps as $step) : ?>
  <div class="process-flip" tabindex="0" role="button" aria-label="Étape <?php echo esc_attr($step[0] . ' : ' . $step[1]); ?>">
    <div class="process-flip-inner">
      <div class="process-flip-front">
        <?php echo sapi_image($step[2], 'large', ['alt' => $step[3], 'class' => 'process-flip-photo', 'loading' => 'lazy']); ?>
        <span class="process-flip-label"><span class="process-flip-num"><?php echo esc_html($step[0]); ?></span> <?php echo esc_html($step[1]); ?></span>
      </div>
      <div class="process-flip-back">
        <p>« <?php echo esc_html($step[4]); ?> »</p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
```
```css
/* ===== Refonte home — process en cartes à retourner ===== */
.process-flips {
  display: flex;
  gap: 16px;
  margin-top: 48px;
}
.process-flip {
  flex: 1;
  height: 210px;
  perspective: 800px;
  cursor: pointer;
}
.process-flip-inner {
  position: relative;
  width: 100%;
  height: 100%;
  transition: transform 0.55s cubic-bezier(0.3, 0.7, 0.3, 1);
  transform-style: preserve-3d;
}
@media (hover: hover) {
  .process-flip:hover .process-flip-inner { transform: rotateY(180deg); }
}
.process-flip.is-flipped .process-flip-inner { transform: rotateY(180deg); }
.process-flip-front,
.process-flip-back {
  position: absolute;
  inset: 0;
  backface-visibility: hidden;
  -webkit-backface-visibility: hidden;
  border-radius: 12px;
  overflow: hidden;
}
.process-flip-photo { width: 100%; height: 100%; object-fit: cover; }
.process-flip-label {
  position: absolute;
  left: 0; right: 0; bottom: 0;
  padding: 10px;
  color: var(--color-white);
  background: linear-gradient(transparent, rgba(40, 30, 20, 0.55));
  font-size: 12px;
  font-weight: 600;
}
.process-flip-num { font-weight: 700; letter-spacing: 0.1em; opacity: 0.9; margin-right: 4px; }
.process-flip-back {
  background: var(--color-wood-dark);
  color: var(--color-warm);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 14px;
  text-align: center;
  transform: rotateY(180deg);
}
.process-flip-back p {
  font-family: var(--font-display);
  font-size: 1.5rem;
  line-height: 1.1;
  margin: 0;
}
@media (max-width: 880px) {
  .process-flips { flex-wrap: wrap; }
  .process-flip { flex: 1 1 30%; height: 180px; }
}
@media (max-width: 520px) {
  .process-flip { flex: 1 1 45%; }
}
```
JS (tap mobile + clavier) — inline en bas de front-page.php, dans le pattern IIFE existant :
```js
document.querySelectorAll('.process-flip').forEach(function (card) {
  card.addEventListener('click', function () { card.classList.toggle('is-flipped'); });
  card.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.classList.toggle('is-flipped'); }
  });
});
```
Cleanup : supprimer les règles `.process-strip` / `.process-tile*` de #7 (devenues orphelines, vérifier par grep).

### 4. Section plus haute
- `.atelier-photo { min-height: 440px; }` (au lieu de 300) ;
- `.atelier-duo { gap: 56px; }` ;
- `.home-atelier { padding-bottom: 5.5rem; }` (le padding-top 6rem de #8 reste) ;
- les cartes process à 210px + margin-top 48px participent. Résultat attendu : la section respire et s'impose comme un moment fort de la page.

**Notes / pièges :**
- Phrases du verso : guillemets français « » comme indiqué, AUCUN tiret cadratin.
- Les 5 photos sont les mêmes qu'avant (sapi_image), seuls les conteneurs changent.
- Mobile : vérifier le flip au tap, et que les versos Square Peg restent lisibles (taille à ajuster si débordement).
- Rien d'autre dans la section ne bouge (titre, textes, CTA, paragraphe SEO intacts).

**Critères de succès :**
- Photo atelier non cliquable, lien « Voir l'atelier sur Google Maps → » discret dessous, fonctionnel, jamais bleu.
- Plus d'eyebrow au-dessus du titre.
- 5 cartes process : photo + numéro/titre au recto, phrase manuscrite sur fond bois au verso ; flip au survol (desktop), au tap (mobile), au clavier (Enter/espace).
- Section nettement plus haute (photo 440px, gaps augmentés), équilibre desktop + mobile OK.

### 👉 Action Robin
Revue de la section sur test (desktop + mobile, teste le flip au tap). Si OK : passe Yoast, puis go-live.

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #9 — Créations = Star + best-sellers + 5 ajustements
**Résultat (branche `feature/refonte-home`, commit `409bafc`) :**
1. **Naming card hero** : `rgba(255,255,255,0.8)` + `backdrop-filter: blur(8px)` → légèrement translucide, lisible sur photo.
2. **Bandeau réassurance** : `@media(min-width:601px){ .home-repositioned-bar .reassurance-bar-inner { gap:48px } }` → items plus espacés sur la home **desktop only** (mobile reste compact, autres pages inchangées).
3. **Room-picker** : `.home-projet` élargi à **1100px**, titre `white-space:nowrap` en `@media(min-width:769px)` (1 ligne desktop, retour ligne mobile OK), `.home-projet .room-card` **230→300px** (~+30%).
4. **« Les créations du moment » refondue** :
   - **Data** (`front-page.php`) : `$star_id` initialisé à 0 (robustesse) ; query best-sellers `meta_key=total_sales`, `orderby=meta_value_num DESC`, `post__not_in=[$star_id]`. Collecte enrichie par produit (`ambiance_id` fallback thumbnail, `hover_id`=1re galerie WC, `category` singulier, `is_variable`+prix) — pattern `archive-product.php`.
   - **Markup** : `bento-container` remplacé par `.creations-grid` (2fr 1fr 1fr) = **Star en grande photo immersive** (`.creation-star`, overlay dégradé bas pour lisibilité, nom formaté + catégorie en blanc) + **2 best-sellers en `.product-card-cinetique`** identiques aux pages catégories (photo ambiance, hover allumé, nom/cat/prix, « Découvrir », badge « Best-seller ») + **CTA pill compact centré** (`.creations-cta > .hero-cta`). Plus aucune carte bento ni bandeau CTA pleine largeur.
   - **Formatter** : `.creation-star .product-name` ajouté aux 2 tableaux de `product-name-formatter.js` (et anciens `.bento-product-featured h3` / `.bento-hero .bento-title` morts retirés).
   - **Cleanup CSS** : blocs orphelins **supprimés** `.bento-hero(+::before)`, `.bento-content`, `.bento-title`, `.bento-category`, `.bento-product-featured*`, `.bento-cta*`, `.cta-title`. **Gardés** : `.bento-bg-img` (carte cadeau), `.hero-cta`, `.bento-bestseller-badge` (réutilisé Star+Best-seller), `.bento-label/-text` (hors scope). **Laissés** : fragments media-query `.bento-*` (CSS mort inoffensif, 0 match) → cleanup dédié.
5. **Section Créations aérée** : `.home-creations .section-header-kinetic { margin-bottom:2rem }` + grid gap 2rem + CTA margin-top 2.5rem.
- **Vérifs** : accolades PHP 63/63, CSS 3704/3704 ; classes partagées intactes ; aucune réf data obsolète.

**MAJ post-revue (commits `765d1c3`, `9666312`) :**
- Retiré les pills **« Best-seller »** des 2 cards (la Star garde « Star du moment »).
- Les 2 cards de droite ne sont plus des best-sellers mais les **2 produits les plus RÉCENTS** parmi **lampes à poser / lampadaires / appliques** (suspensions exclues), **forcément de 2 catégories différentes** (verrou `$fp_used_cats`, tri `date DESC`, Star toujours exclue).

### 👉 Action Robin
Re-revue des 5 points sur test (desktop + mobile) : surtout **« Créations du moment »** (Star grande photo + 2 produits récents de 2 catégories ≠, sans pill, + CTA compact ; jamais la Star en doublon) et le room-picker (titre 1 ligne ?). Coup d'œil fiche produit/catégorie (inchangées). Si OK : passe Yoast, puis **go-live**.

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #9 — Ajustements design (revue Robin) + refonte « Créations du moment » en Star + best-sellers
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).

**Contexte :** Nouvelle salve de retours Robin + décision sur le contenu de « Les créations du moment » : fini les produits au hasard, on affiche la **Star du moment + les 2 best-sellers** (tri `total_sales`, même pattern que les pages catégories, cf. `taxonomy-product_cat.php` l.59). Et le CTA bandeau orange pleine largeur est BEAUCOUP trop grand : remplacé par une pill compacte centrée.

**À faire :**

### 1. Naming card hero — légèrement transparente
`.naming-card` : `background: rgba(255,255,255,0.8);` et remettre un léger flou pour la lisibilité sur photo : `-webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px);`. Le reste (radius 50px, largeur fixe, ellipsis) ne bouge pas.

### 2. Bandeau réassurance — écarter les éléments
Scopé home via le hook existant : `.robin-bandeau.home-repositioned-bar .reassurance-bar-inner { gap: 48px; }` (lire la valeur actuelle du gap et viser ~×2 ; si l'espacement vient d'autre chose que `gap`, adapter avec le même scope). Mobile : ne pas écarter (garder le compact existant ≤600px).

### 3. Room-picker — H2 sur UNE ligne + cards +30%
1. Élargir le conteneur (principe charte : élargir avant de réduire la police) : `.home-projet { max-width: 1100px; }`. Puis desktop only : `@media (min-width: 769px) { .home-projet .room-picker-title { white-space: nowrap; } }`. Vérifier qu'à 1280px de viewport le titre tient sur une ligne ; sinon réduire le clamp à `clamp(1.5rem, 2.6vw, 2.1rem)` SUR CE TITRE uniquement. Mobile : retour à la ligne autorisé.
2. Cards réponses : `.home-projet .room-card { max-width: 300px; }` (230 → 300, ~+30 %). Vérifier l'équilibre de la rangée (3×2 ou 6×1 selon la largeur, pas de carte orpheline moche).

### 4. « Les créations du moment » — refonte complète : Star en grande photo + 2 best-sellers en cards classiques + CTA compact
**Disposition cible (validée Robin, cf. mockup-15)** : la Star = grande carte photo immersive à gauche (2fr), les 2 best-sellers = **cards produit CLASSIQUES** `.product-card-cinetique` (celles des pages catégories : photo ambiance + hover allumé, nom formaté, catégorie, prix, « Découvrir ») à droite (1fr + 1fr). Plus AUCUNE carte bento dans cette section.

**A. Data** (`front-page.php`, bloc `// Featured products for Bento grid`) : remplacer la query aléatoire par un tri best-sellers, en excluant la Star :
```php
$featured_query = new WP_Query([
  'post_type'      => 'product',
  'posts_per_page' => 8,
  'post_status'    => 'publish',
  'meta_key'       => 'total_sales',
  'orderby'        => 'meta_value_num',
  'order'          => 'DESC',
  'post__not_in'   => !empty($star_id) ? [(int) $star_id] : [],
  'tax_query'      => [
    [
      'taxonomy' => 'product_cat',
      'field'    => 'slug',
      'terms'    => ['suspensions', 'appliques', 'lampesaposer', 'lampadaires'],
      'operator' => 'IN',
    ],
  ],
]);
```
(⚠️ vérifier que `$star_id` défini dans le bloc Star plus haut est accessible ici ; sinon le stocker dans une variable dédiée.) Dans la boucle de collecte (break à 2), enrichir les données par produit pour alimenter les cards cinetique, en MIRRORANT la prep de `woocommerce/archive-product.php` (~l.440-477) : `ambiance_id` (photo ambiance 1, fallback thumbnail), `hover_id` (même logique que l'archive : image galerie WC « modèle allumé »), `category` (1re catégorie hors uncategorized, au singulier comme le bloc Star), `is_variable` + prix min.

**B. Markup** : dans `.home-creations`, REMPLACER le `<div class="bento-container">…</div>` par :
```php
<div class="creations-grid">

  <?php if ($star_product_data) : ?>
  <a href="<?php echo esc_url($star_product_data['url']); ?>" class="creation-star">
    <?php echo wp_get_attachment_image($star_product_data['image_id'], 'woocommerce_single', false, ['class' => 'creation-star-img', 'loading' => 'lazy', 'alt' => $star_product_data['name'] . ', star du moment']); ?>
    <span class="bento-bestseller-badge">Star du moment</span>
    <div class="creation-star-label">
      <h3 class="product-name"><?php echo esc_html($star_product_data['name']); ?></h3>
      <?php if ($star_product_data['category']) : ?><p><?php echo esc_html($star_product_data['category']); ?></p><?php endif; ?>
    </div>
  </a>
  <?php endif; ?>

  <?php foreach ($featured_products as $fp) : ?>
  <div class="product-card-cinetique" data-product-id="<?php echo esc_attr($fp['id']); ?>" data-piece-swap data-piece-swap-type="ambiance" data-piece-swap-size="large">
    <a href="<?php echo esc_url($fp['url']); ?>" class="product-card-link">
      <div class="product-media<?php echo !empty($fp['hover_id']) ? ' has-hover-image' : ''; ?>">
        <span class="bento-bestseller-badge">Best-seller</span>
        <span class="product-image-main"><?php echo wp_get_attachment_image($fp['ambiance_id'], 'large', false, ['alt' => $fp['name'], 'loading' => 'lazy']); ?></span>
        <?php if (!empty($fp['hover_id'])) : ?>
          <span class="product-image-hover"><?php echo wp_get_attachment_image($fp['hover_id'], 'woocommerce_thumbnail', false, ['alt' => $fp['name'] . ' - ambiance', 'loading' => 'lazy']); ?></span>
        <?php endif; ?>
      </div>
      <div class="product-info">
        <h3 class="product-name"><?php echo esc_html($fp['name']); ?></h3>
        <?php if (!empty($fp['category'])) : ?><p class="product-category"><?php echo esc_html($fp['category']); ?></p><?php endif; ?>
        <div class="product-price">
          <?php if (!empty($fp['is_variable'])) : ?><span class="price-from">À partir de</span><?php endif; ?>
          <span class="price-value"><?php echo wp_kses_post($fp['price']); ?></span>
        </div>
      </div>
      <div class="product-actions"><span class="btn-view">Découvrir ⇾</span></div>
    </a>
  </div>
  <?php endforeach; ?>

</div>
<div class="creations-cta">
  <a href="<?php echo home_url('/mes-creations/'); ?>" class="hero-cta">Toutes les créations</a>
</div>
```

**C. CSS** :
```css
/* ===== Refonte home — grille Créations du moment (star photo + cards classiques) ===== */
.creations-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr;
  gap: 2rem;
  align-items: stretch;
}
.creation-star {
  position: relative;
  display: block;
  border-radius: 16px;
  overflow: hidden;
  min-height: 420px;
}
.creation-star-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.creation-star .bento-bestseller-badge,
.creations-grid .product-media .bento-bestseller-badge {
  position: absolute;
  top: 14px;
  left: 14px;
  z-index: 2;
}
.creations-grid .product-media { position: relative; }
.creation-star-label {
  position: absolute;
  left: 18px;
  bottom: 16px;
  color: var(--color-white);
  z-index: 2;
}
.creation-star-label p { margin: 2px 0 0; font-size: 0.85rem; opacity: 0.92; }
.creations-cta { text-align: center; margin-top: 2.5rem; }
@media (max-width: 880px) {
  .creations-grid { grid-template-columns: 1fr 1fr; }
  .creation-star { grid-column: 1 / -1; min-height: 280px; }
}
@media (max-width: 520px) {
  .creations-grid { grid-template-columns: 1fr; }
}
```
(Vérifier que `.product-card-cinetique` rend bien hors `.product-grid` ; si une règle de layout manque, l'ajouter scopée `.creations-grid .product-card-cinetique`, ne pas toucher aux règles globales. Le nom sur la photo Star doit rester lisible : si la photo est claire, ajouter un léger dégradé bas via `.creation-star::after` comme le carousel le fait avec `.carousel-overlay`.)

**D. Formatter noms produits (règle design system)** : ajouter le sélecteur `.creation-star .product-name` dans les tableaux `selectors` de `assets/product-name-formatter.js` (les `.product-card-cinetique` y sont déjà). Le prénom/surnom de la Star doit s'afficher formaté comme partout.

**E. Cleanup** : `.bento-cta`, `.bento-hero`, `.bento-product-featured*`, `.bento-content`, `.bento-title`, `.bento-category` : grep .php sur chacun, supprimer les règles CSS de ceux à 0 usage (lister gardés/supprimés). ⚠️ `.bento-bestseller-badge` RESTE (réutilisé).

### 5. Aérer « Les créations du moment »
La nouvelle grille a déjà `gap: 2rem` + CTA à 2.5rem. Ajouter :
```css
.home-creations .section-header-kinetic { margin-bottom: 2rem; }
```
(L'ensemble doit respirer nettement plus qu'avant.)

**Notes / pièges :**
- Scopes stricts : rien ne bouge sur fiche produit / catégories / page Star / modale.
- Aucun tiret cadratin. Accolades équilibrées. Vérifier le rendu mobile de chaque point.

**Critères de succès :**
- Bulle hero légèrement translucide avec flou, lisible sur toutes les slides.
- Items du bandeau nettement plus espacés sur la home, inchangés ailleurs et en mobile.
- H2 room-picker sur une ligne en desktop ; cards réponses ~300px.
- Créations du moment : Star en GRANDE PHOTO immersive (2fr, badge + nom formaté en overlay) + 2 best-sellers réels en CARDS CLASSIQUES `.product-card-cinetique` identiques à celles des pages catégories (photo ambiance, hover allumé, nom formaté, catégorie, prix, « Découvrir »), badges « Best-seller », jamais la Star en doublon.
- CTA pill compact centré, plus aucun bandeau CTA pleine largeur, plus aucune carte bento dans la section, section aérée.
- Nom de la Star formaté prénom/surnom (sélecteur ajouté au formatter).

### 👉 Action Robin
Re-revue des 5 points sur test. Si OK : passe Yoast, puis go-live.

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #8 — 8 ajustements design (revue Robin)
**Résultat (branche `feature/refonte-home`, commit `97ea2b1`, 100% CSS) :**
1. **Naming card hero** : `min-width:440px` → **largeur fixe `width:520px`** + `max-width:calc(100vw-32px)`. Nom en `overflow:hidden; text-overflow:ellipsis` → la bulle ne resize plus jamais (noms longs tronqués).
2. **Bandeau réassurance** : `.robin-bandeau.home-repositioned-bar { padding:20px }` → plus haut **sur la home uniquement** (classe ajoutée par le JS front-page), compact ailleurs.
3. **Titre « Pour quelle pièce ? »** : `.home-projet .room-picker-title` aligné exactement sur `.section-title-kinetic` (Montserrat 600, clamp 1.6–2.4rem, wood-dark) + **neutralisé** `text-transform:none` / `letter-spacing:normal` (le global était uppercase/700).
4. **Room cards** : `.home-projet .room-card { max-width:230px }` (global reste 200px pour modale/Conseils).
5. **Respiration sections** : `padding-top` **6rem desktop / 3.5rem mobile** sur projet, collections, créations, atelier, avis, divers, newsletter. Bandes crème : padding **dans** le fond (crème englobant). Bento : double-classe `.hero-bento.home-*` pour battre `.hero-bento` sans souci d'ordre.
6. **Cards catégories** : `.collections-kinetic .collection-card { aspect-ratio:2/3; max-height:660px }` → ~1/3 plus hautes (scopé home, object-fit cover déjà en place).
7. **Titre L'atelier** : `.atelier-story-title { text-transform:none; margin:0.5rem 0 1.25rem; line-height:1.05 }` → minuscules + air. Eyebrow laissé uppercase.
8. **Numéros de section** : `1.1rem` → **`1.5rem`** via `.section-header-kinetic .section-num` + `.home-avis .testimonials-header .section-num`. **`.section-num` global intact (0.875rem)** → fiche produit/catégories non touchées.
- **Vérifs** : accolades CSS 3706/3706 ; `.section-num` global = 0.875rem confirmé ; aucune modif front-page.php (CSS only).

### 👉 Action Robin
Re-revue rapide des 8 points sur test (desktop + mobile) + coup d'œil fiche produit/catégorie (rien ne doit bouger). Si OK : passe Yoast (titre/meta home, avec Cowork), puis **go-live** (merge → master + déploiement prod + re-soumission GSC).

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #8 — Ajustements design (8 retours revue Robin sur #7)
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**Mockup de référence :** `mockups/mockup-15-home-refonte-juin-2026.html`.

**Contexte :** Revue de Robin après #7 : 8 micro-ajustements, tous scopés home. Pour chaque point, LIRE la règle actuelle avant de modifier. Aucun style partagé (fiche produit, catégories, page Star, modale Conseiller) ne doit bouger.

**À faire :**

### 1. Naming card hero — taille FIXE (et plus grande)
La bulle ne doit JAMAIS s'adapter à la longueur du nom du modèle. Remplacer le `min-width: 440px` par une largeur fixe plus généreuse : `width: 520px; max-width: calc(100vw - 32px);`. Ajouter sur le naming-link (le nom du modèle) : `white-space: nowrap; overflow: hidden; text-overflow: ellipsis;` pour absorber les noms longs sans déformer la bulle. Vérifier le rendu mobile (la bulle doit rester contenue, le max-width fait le travail).

### 2. Bandeau réassurance — plus haut, home uniquement
Hook facile : le JS de front-page.php ajoute la classe `home-repositioned-bar` au bandeau sur la home. Donc :
```css
.robin-bandeau.home-repositioned-bar { padding-top: 20px; padding-bottom: 20px; }
```
(au lieu des 12px globaux ; les autres pages gardent le bandeau compact.)

### 3. Titre « Pour quelle pièce cherches-tu un luminaire ? » — même police que les autres H2
Aligner `.home-projet .room-picker-title` EXACTEMENT sur les déclarations de `.section-title-kinetic` : `font-family: var(--font-body); font-size: clamp(1.6rem, 3.4vw, 2.4rem); font-weight: 600; color: var(--color-wood-dark);`. (Lire la règle actuelle de `.room-picker-title` pour neutraliser ce qui diverge : famille, graisse, transform éventuels.)

### 4. Room cards — légèrement plus larges
`.home-projet .room-card { max-width: 230px; }` (la règle globale `.room-card` reste à 200px pour la modale/page Conseils). Vérifier que les 6 tiennent toujours bien sur 1-2 rangées en desktop.

### 5. Respiration AVANT chaque titre de section — beaucoup plus d'air
Augmenter fortement le padding-top des sections de la home pour décoller chaque header de la section précédente. Cible : **6rem desktop / 3.5rem mobile** (≤768px). Sections concernées : `.home-projet-section`, `.collections-kinetic` (home only, vérifié), `.home-creations`, `.home-atelier`, `.home-avis` (le padding-top du header), `.home-divers`, `.newsletter-kinetic`. ⚠️ Sur les bandes crème, le padding doit rester DANS le fond (le crème englobe l'air, comme le mockup). Garder les paddings horizontaux/bas actuels.

### 6. Cards catégories (Collections) — plus hautes
`.collection-visual` est en height:100% → trouver la source réelle de hauteur (`.collection-card`, la grille `.collections-grid`, ou un aspect-ratio) et l'augmenter d'environ **un tiers** en desktop. Mobile : proportionnel, sans étirement disgracieux des images (object-fit cover déjà en place a priori).

### 7. Section 04 L'atelier — titre sans majuscules + air autour
Identifier la source du `text-transform: uppercase` qui s'applique au titre « Des sculptures lumineuses » (`.atelier-story-title`) et le neutraliser : `text-transform: none;`. Ajouter de l'espace autour du titre comme dans le mockup : `margin: 0.5rem 0 1.25rem; line-height: 1.05;`. Ne pas toucher l'eyebrow (lui reste uppercase, c'est voulu).

### 8. Numéros de section — plus grands (toutes les sections home)
`.section-header-kinetic .section-num { font-size: 1.5rem; }` (remplace le 1.1rem de #7) + même taille pour le numéro du header avis : `.home-avis .testimonials-header .section-num { font-size: 1.5rem; }`. ⚠️ Toujours via ces sélecteurs scopés, JAMAIS la règle globale `.section-num`.

**Notes / pièges :**
- Aucun tiret cadratin. Accolades équilibrées. Pas de nouvelle ombre.
- Après push : re-vérifier 30 secondes fiche produit + une catégorie (aucun changement attendu).

**Critères de succès :**
- Bulle hero : largeur identique sur toutes les slides, y compris noms longs (ellipsis).
- Bandeau réassurance visiblement plus haut sur la home, inchangé ailleurs.
- Titre room-picker visuellement identique aux autres H2 ; room cards plus larges.
- Respiration nette avant chaque section (desktop + mobile), crème englobant.
- Cards catégories ~1/3 plus hautes ; titre atelier en minuscules avec de l'air ; numéros de section nettement plus présents.

### 👉 Action Robin
Re-revue rapide des 8 points sur test (desktop + mobile). Si tout est bon : passe Yoast (avec Cowork), puis go-live.

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #7 — Passe design profonde
**Résultat (branche `feature/refonte-home`, commit `b027a92`) :**
- **A. Hero naming card** : bulle blanche pleine `rgba(255,255,255,.94)` radius 50px (exit verre dépoli/backdrop-filter). **Dots ré-affichés** (étaient `display:none`) : inactif bois discret 7px, **actif = pill orange 20px**. Chevrons + naming-link en `--color-wood-dark`. Tout scopé `.naming-card` → les autres carousels (cluster CSS 6106) non touchés.
- **B. Bandeau réassurance** (`.robin-bandeau`, global assumé) : fond **blanc**, icônes **orange**, texte **wood-mid**. ℹ️ Pas de « mode projet » dans le bandeau actuel (le JS `bandeau-reassurance.js` ne fait que randomiser 2/4 items sur mobile) → rien à corriger de ce côté.
- **C. Titres de section** au gabarit mockup (Montserrat 600, wood-dark, clamp réduit) : `.section-title-kinetic` (vérifié **home-only**), num discret scopé `.section-header-kinetic .section-num` (global `.section-num` **NON touché** → fiche produit/catégories intactes), titre avis scopé `.home-avis .testimonials-header h2`, titre « Pour quelle pièce ? » scopé `.home-projet`.
- **D. L'atelier recomposée** : `bento-container` remplacé par `.atelier-duo` (story texte + eyebrow + titre Square Peg + 2 paragraphes + **CTA pill orange `.hero-cta` à gauche**, photo cliquable arrondie `.atelier-photo` à droite) + `.process-strip` (5 tuiles image). **Paragraphe SEO + 4 liens catégories conservés verbatim.** Header « 04 » et helper `$sapi_cat_url` gardés.
- **E. Cleanup orphelins** (grep `.php` d'abord) :
  - **Supprimés** (home-only, 0 usage) : `.bento-storytelling`, `.storytelling-inner/-num/-label/-title/-link` (+hover) ; `.bento-atelier` (+`::before`) ; les 2 overrides #3 `.home-atelier .bento-storytelling/.bento-atelier`. Hover du label repointé `.bento-atelier:hover` → `.atelier-photo:hover`.
  - **Gardés** : `.storytelling-text` (réutilisé), `.atelier-label` (réutilisé par `.atelier-photo`).
  - **Laissés volontairement** (CSS mort inoffensif, 0 élément ne matche) : le bloc `.process-*` complet — car `.process-header` (page-sur-mesure) et `.step-num` (functions.php, sur-mesure, checkout/thankyou) y sont **partagés** ; disjoindre risquerait ces pages live. + les références groupées `.bento-storytelling/.bento-atelier/.bento-process` dans 3 media-queries grid. → **à nettoyer dans une tâche dédiée à faible risque**.
- **Vérifs** : accolades PHP 61/61, CSS 3698/3698 ; `.process-header`/`.step-num`/`.section-num` (global) toujours définis ; nouvelles classes atelier présentes.

### 👉 Action Robin
Revue **zone par zone** sur test (mockup-15 ouvert) : hero (bulle + dots orange), bandeau blanc, titres plus discrets, **L'atelier recomposée** (duo texte/photo + frise tuiles). **Re-vérifier 3 pages non touchées** : une **fiche produit**, une **page catégorie**, la **page Star** (`/la-star-du-moment/`). Liste ce qui détonne → #7bis. Ensuite : Yoast (titre/meta home), puis go-live.

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #7 — Passe design profonde : aligner le rendu sur le mockup-15 (retours Robin)
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**Mockup de référence :** `mockups/mockup-15-home-refonte-juin-2026.html` — OUVRIR et comparer zone par zone. Le HTML/CSS du mockup est une CIBLE VISUELLE, pas du code à copier.

**Contexte :** Retour Robin sur #6 : trop léger, seule l'alternance de fonds a été appliquée. 4 écarts majeurs restent vs mockup : (1) la naming card du hero doit être une bulle blanche avec dots orange, (2) le bandeau réassurance doit être blanc avec icônes orange, (3) les titres de section sont trop gros et pas dans le bon gabarit, (4) la section L'atelier doit être ENTIÈREMENT recomposée (texte + photo côte à côte, frise de tuiles, exit les cartes bento). ⚠️ Plusieurs classes touchées sont PARTAGÉES (`.section-num` → fiche produit + catégories ; `.storytelling-*`/`.process-*`/`.atelier-label` → page-la-star-du-moment.php ; `.testimonials-header h2` → fiche produit) : scoper exactement comme indiqué.

**À faire :**

### A. Hero — naming card en bulle blanche, dots orange
LIRE d'abord les règles `.naming-card`, `.carousel-dot` (2 définitions, prendre celle qui s'applique à la card du hero), `.carousel-arrow`.
1. `.naming-card` : remplacer l'effet verre dépoli par une bulle blanche pleine : `background: rgba(255,255,255,0.94);`, supprimer le backdrop-filter, `border: none;`, `border-radius: 50px;`. Garder le `min-width` (verrou anti-resize) et le layout flex.
2. Dots (dans la naming card) : inactif `background: rgba(147, 125, 104, 0.4); border: none; width: 7px; height: 7px;` ; `.carousel-dot.active` : `background: var(--color-orange); width: 20px; border-radius: 10px; transform: none;` (pillule étirée orange, comme le mockup).
3. `.carousel-arrow` (chevrons) : `color: var(--color-wood-dark);` pour contraster sur le blanc. Texte du naming-link : `var(--color-wood-dark)` aussi (vérifier).
4. Mobile : vérifier que la bulle reste propre ≤600px (min-width à adapter si elle déborde).

### B. Bandeau réassurance — blanc, icônes orange
⚠️ `.robin-bandeau` est GLOBAL (inclus via header.php sur tout le site, sticky). Le restyle s'applique partout, c'est assumé (barre neutre). LIRE les règles l.1386-1420 avant.
1. `.robin-bandeau` : `background: var(--color-white);` (garder border-bottom, sticky, padding).
2. `.robin-bandeau .reassurance-item svg` : couleur/stroke `var(--color-orange)`.
3. Texte des items : `var(--color-wood-mid)`.
4. Vérifier le mode « Mon projet » (badge bois + chips) : doit rester lisible sur blanc. Si un élément devient illisible, corriger sa couleur uniquement (pas le layout).

### C. Titres de section — gabarit mockup (plus petits, Montserrat 600, wood-dark)
1. `.section-title-kinetic` (utilisé UNIQUEMENT sur la home, vérifié) : remplacer par `font-family: var(--font-body); font-size: clamp(1.6rem, 3.4vw, 2.4rem); font-weight: 600; color: var(--color-wood-dark); margin: 0;`.
2. `.section-header-kinetic .section-num { font-size: 1.1rem; }` — ⚠️ SCOPÉ : ne PAS toucher la règle globale `.section-num` (fiche produit + catégories l'utilisent).
3. `.home-avis .testimonials-header h2` : même gabarit que `.section-title-kinetic` (déclarations identiques, sélecteur scopé `.home-avis`, la fiche produit garde son style).
4. `.home-projet .room-picker-title { font-size: clamp(1.5rem, 3vw, 2.1rem); font-weight: 600; color: var(--color-wood-dark); }` (scopé home).

### D. L'atelier — recomposition complète (layout mockup)
Dans `.home-atelier`, GARDER : le wrapper `<section class="hero-bento home-atelier">`, le header kinetic « 04 — L'atelier », le helper `$sapi_cat_url`. REMPLACER tout le `<div class="bento-container">…</div>` par :
```php
<div class="atelier-duo">
  <div class="atelier-story">
    <span class="section-eyebrow">Mon atelier à Lyon</span>
    <h3 class="atelier-story-title">Des sculptures lumineuses</h3>
    <p class="storytelling-text">Du croquis à l'assemblage final, chaque pièce est façonnée dans mon atelier lyonnais. Le bois prend forme sous mes mains, la lumière fait le reste.</p>
    [reprendre VERBATIM le <p class="storytelling-text storytelling-text--seo"> existant avec ses 4 liens catégories]
    <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="hero-cta">Découvrir l'artisan</a>
  </div>
  <a class="atelier-photo" href="https://maps.app.goo.gl/a3MiaeoG3ySfyUQT9" target="_blank" rel="noopener noreferrer">
    <?php echo sapi_image('2025/05/Robin-Sapi-A.jpg', 'large', ['alt' => 'Atelier Sâpi, atelier de fabrication de luminaires à Lyon', 'class' => 'atelier-photo-img', 'loading' => 'lazy']); ?>
    <div class="atelier-label"><span>L'atelier · Lyon</span></div>
  </a>
</div>
<div class="process-strip">
  <?php
  $process_steps = [
    ['01', 'Dessin',        '2025/05/IMG_1928-e1761747188966.png', "Dessin d'un luminaire en bois, Atelier Sâpi"],
    ['02', 'Découpe laser', '2025/05/IMG_7638.jpg',                'Découpe laser du bois pour luminaire'],
    ['03', 'Finitions',     '2025/03/P_SLM_XL_det5.jpg',           "Finitions manuelles d'un luminaire en bois"],
    ['04', 'Assemblage',    '2025/05/Robin-Sapi-A.jpg',            'Robin assemble un luminaire dans son atelier à Lyon'],
    ['05', 'Expédition',    '2025/07/Claudine-bandeau-1.jpg',      "Luminaire Claudine prêt pour l'expédition"],
  ];
  foreach ($process_steps as $step) : ?>
    <div class="process-tile">
      <div class="process-tile-img">
        <?php echo sapi_image($step[2], 'large', ['alt' => $step[3], 'class' => 'process-tile-photo', 'loading' => 'lazy']); ?>
      </div>
      <span class="process-tile-label"><?php echo esc_html($step[0] . ' · ' . $step[1]); ?></span>
    </div>
  <?php endforeach; ?>
</div>
```
CSS à ajouter (générique, réutilisable) :
```css
/* ===== Refonte home #7 — Section L'atelier (layout mockup) ===== */
.atelier-duo {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  align-items: center;
}
.atelier-story-title {
  font-family: var(--font-display);
  font-size: clamp(2.2rem, 4vw, 3rem);
  font-weight: 400;
  color: var(--color-wood-dark);
  line-height: 1;
  margin: 0 0 12px;
}
.atelier-story .hero-cta { margin-top: 1rem; }
.atelier-photo {
  position: relative;
  display: block;
  border-radius: 14px;
  overflow: hidden;
  min-height: 300px;
}
.atelier-photo-img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.process-strip {
  display: flex;
  gap: 14px;
  margin-top: 42px;
}
.process-tile { flex: 1; text-align: center; }
.process-tile-img {
  height: 96px;
  border-radius: 12px;
  overflow: hidden;
}
.process-tile-photo { width: 100%; height: 100%; object-fit: cover; }
.process-tile-label {
  display: block;
  font-size: 12px;
  margin-top: 8px;
  color: var(--color-wood-mid);
  font-weight: 500;
}
@media (max-width: 880px) {
  .atelier-duo { grid-template-columns: 1fr; gap: 24px; }
  .process-strip { flex-wrap: wrap; row-gap: 18px; }
  .process-tile { flex: 1 1 28%; }
}
```
Notes : le CTA réutilise `.hero-cta` (pill orange gradient existante, actuellement orpheline — vérifier qu'elle rend bien, sinon ajuster scopé `.atelier-story .hero-cta`). `.atelier-label` existant : vérifier son positionnement dans le nouveau contexte (il vivait dans une bento-card), corriger scopé `.atelier-photo .atelier-label` si besoin.

### E. Cleanup des styles devenus orphelins (avec vérification)
Supprimer les overrides #3 devenus inutiles : `.home-atelier .bento-storytelling` et `.home-atelier .bento-atelier`. Pour chaque ancienne classe candidate à suppression (`.bento-storytelling`, `.bento-atelier`, `.bento-process`, `.process-header`, `.process-number`, `.process-title`, `.process-inner`, `.process-step`, `.step-num`, `.step-text`, `.step-image-img`, `.storytelling-inner`, `.storytelling-label`, `.storytelling-num`, `.storytelling-title`, `.storytelling-link`) : `grep` dans TOUS les .php d'abord — ⚠️ `page-la-star-du-moment.php` en utilise certaines. Ne supprimer QUE celles à 0 usage. Lister gardées/supprimées dans le résultat.

**Notes / pièges :**
- `.storytelling-text` et `.storytelling-text--seo` restent UTILISÉS (nouveau markup) : ne pas y toucher.
- Fiche produit, pages catégories, page Star : visuellement INCHANGÉES (vérifier les 3 sur test après push).
- Pas de nouveau hex sauf les rgba() blancs/bois indiqués (patterns déjà présents dans le code). Aucun tiret cadratin. Accolades équilibrées.
- En cas de blocage layout : noter et passer au point suivant, ne pas s'acharner.

**Critères de succès :**
- Hero : bulle blanche radius 50px, dots bois discrets + actif pillule orange étirée, chevrons wood-dark.
- Bandeau réassurance blanc, icônes orange, texte wood-mid, mode projet lisible.
- Tous les titres de section au gabarit mockup (y compris avis et « Pour quelle pièce ? »), numéros discrets ; fiche produit/catégories inchangées.
- L'atelier : eyebrow + titre Square Peg + 2 paragraphes + CTA pill orange à gauche, photo cliquable arrondie à droite, frise de 5 tuiles image en dessous. Plus aucune carte bento dans cette section.
- Rapport de cleanup CSS (classes supprimées vs gardées avec justification).
- Mobile : duo empilé, tuiles process sur 2-3 colonnes, bulle hero contenue.

### 👉 Action Robin
Revue zone par zone avec le mockup ouvert à côté (hero, bandeau, titres, atelier) + re-vérifier fiche produit, une page catégorie et la page Star (non touchées). Lister ce qui détonne encore → #7bis si besoin. Ensuite : passe Yoast, puis go-live.

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #6 — Passe design (langage mockup-15)
**Résultat (branche `feature/refonte-home`, commit `698ef15`) :**
- **A. Utilitaires génériques** : `.section-band--warm` (fond crème) + `.section-eyebrow` (nommés génériquement, généralisables au site, pas `home-*`).
- **B. Alternance blanc/crème** : sections **Créations** et **Ils en parlent** enveloppées dans `<div class="section-band--warm">` (fond edge-to-edge, contenu calé). Rythme obtenu : réassurance(blanc) → Ton projet(crème) → Collections(blanc) → Créations(crème) → L'atelier(blanc) → Avis(crème) → bento divers(blanc) → Newsletter(blanc).
- **C. Entrée projet** : bloc CSS #1 remplacé → **bandeau crème pleine largeur** (exit `::before` dashed + border-radius + fond card). Eyebrow `home-projet__eyebrow` → `section-eyebrow` (markup). Chips blanches centrées sur crème (max-width 900px).
- **D. Ils en parlent** (tout scopé `.home-avis`, fiche produit **intacte**) : `.home-avis` passé en `background:transparent; max-width:none` pour laisser voir la bande ; header en flex aligné comme les autres sections (num+titre à gauche, badge Google `margin-left:auto`) au gabarit 1600px ; grid `align-items:start` + cards `background:white` bordure `--color-gray-light` → **hauteur au contenu** (fini le vide sous le texte) ; grid/cta recalés sur 1600px/padding 3rem. **Étoiles laissées jaune Google `#FBBC05`** (décision Robin).
- **E. Badges** : les 4 (`bento-bestseller`, `giftcard`, `bento-actu`, `bento-conseil`) harmonisés en **pill orange flat** (`--color-orange`, blanc, 12px/600, padding 5px 13px, radius 50px, ombres/gradient retirés). Position inchangée. Vérifié : ces classes ne servent **que** dans front-page.php → 0 impact ailleurs.
- **F. Bento divers** : classe `home-divers` ajoutée à la section ; **variante A** appliquée (carte cadeau en bannière `span 12`), **variante B** (3 cartes égales) en commentaire juste dessous.
- **G. Mobile** : companions `@media (max-width:768px)` pour `.home-avis` (paddings réduits, override de la spécificité des règles mobile existantes) et `.home-projet`.
- **Vérifs OK** : 2 bandes correctement ouvertes/fermées ; accolades PHP 61/61, CSS 3689/3689 ; aucune modif des styles partagés fiche produit / catégories.

### 👉 Action Robin
Revue complète sur test (desktop + **mobile**), **mockup-15 ouvert à côté** :
1. Valider l'alternance blanc/crème + l'entrée projet en bandeau.
2. **Trancher variante bento divers** : A (carte cadeau bannière, actuelle) ou B (3 cartes égales — à décommenter). Dis-moi.
3. Vérifier une **fiche produit** sur test → doit être **visuellement inchangée**.
4. Lister ce qui détonne encore → micro-tâche #6bis si besoin.
Ensuite : passe Yoast (titre/meta home, avec Cowork), puis go-live (merge → master + déploiement prod + re-soumission GSC).

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #6 — Passe design : appliquer le langage du mockup-15 (home pilote)
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**Mockup de référence :** `mockups/mockup-15-home-refonte-juin-2026.html` — à OUVRIR et regarder avant de coder. ⚠️ NE PAS recopier son HTML/CSS tel quel : c'est une cible visuelle, le code reste celui du thème (classes existantes + variables `--color-*`).
**Cette tâche ABSORBE le backlog 🎨 design** (qui est supprimé du queue).

**Contexte :** Robin valide le mockup-15 comme cible design. La home sert de pilote : les nouvelles classes sont nommées GÉNÉRIQUEMENT (pas `home-*`) car ce langage sera généralisé au site après validation. Principe directeur du mockup : **alternance de fonds blanc/crème en bandes pleine largeur** pour rythmer le scroll, cards blanches à bordure fine sur fond crème, badges pills orange harmonisés. Tout en variables existantes (`--color-warm`, `--color-wood`, `--color-orange`, `--color-white`, `--color-gray-light`), AUCUN nouveau hex.

**À faire :**

### A. Utilitaires génériques (nouveau bloc CSS)
```css
/* ===== Design system — bandes de section (langage mockup-15, généralisable) ===== */
.section-band--warm {
  background: var(--color-warm);
}
.section-eyebrow {
  display: block;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-size: 0.82rem;
  color: var(--color-wood);
  opacity: 0.85;
  margin-bottom: 0.5rem;
}
```

### B. Alternance de fonds — bandes pleine largeur
Rythme cible : réassurance (blanc) → **Ton projet (crème)** → Collections (blanc) → **Créations (crème)** → L'atelier (blanc) → **Ils en parlent (crème)** → bento divers (blanc) → Newsletter (blanc).
1. Envelopper la section `.home-creations` dans `<div class="section-band--warm"> … </div>` (le `.hero-bento` intérieur garde son max-width/padding : le fond file edge-to-edge, le contenu reste calé).
2. Idem pour la section `.home-avis` (`.product-testimonials.home-avis`).
3. L'atelier reste blanc (ses cartes warm font l'accent). Ne pas y toucher.

### C. Entrée projet — bandeau crème pleine largeur (exit le cadre dashed)
1. Markup : remplacer la classe `home-projet__eyebrow` par `section-eyebrow` (même élément). Rien d'autre ne bouge (room-picker + data-attributes intacts).
2. CSS : **remplacer intégralement** le bloc `/* ===== Refonte home #1 — Section entrée projet ===== */` par :
```css
/* ===== Refonte home — Entrée projet (bandeau pleine largeur) ===== */
.home-projet-section {
  background: var(--color-warm);
  padding: 3.5rem 2rem;
  margin: 0;
}
.home-projet {
  max-width: 900px;
  margin: 0 auto;
  text-align: center;
}
.home-projet .room-picker-cards {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 1.25rem;
  width: 100%;
}
@media (max-width: 768px) {
  .home-projet-section { padding: 2.5rem 1rem; }
  .home-projet .room-picker-cards { gap: 0.75rem; }
  .home-projet .room-card { flex: 0 0 calc(50% - 0.5rem); max-width: none; padding: 1rem 0.75rem; }
}
```
(Disparaissent : le `::before` dashed, le border-radius, le fond card, le z-index inner. Les room-cards blanches ressortent naturellement sur le crème.)

### D. Ils en parlent — au diapason (retours Robin du 04/06)
LIRE d'abord les règles existantes `.product-testimonials`, `.testimonials-header`, `.testimonial-card` (utilisées par la fiche produit : NE PAS les modifier). Toutes les corrections en sélecteurs scopés `.home-avis` :
1. **Header sur le rythme kinetic** : num + titre alignés à gauche comme les autres sections, badge Google rejeté à droite :
```css
.home-avis .testimonials-header {
  display: flex;
  align-items: baseline;
  gap: 2rem;
  max-width: 1600px;
  margin: 0 auto 1.5rem;
  padding: 3rem 3rem 0;
}
.home-avis .google-reviews-badge { margin-left: auto; }
```
(Ajuster si la structure interne du header s'y prête mal, mais l'intention est : même gabarit visuel que `.section-header-kinetic`.)
2. **Cards** : fond `var(--color-white)`, bordure `1px solid var(--color-gray-light)`, hauteur au contenu (plus de vide sous le texte) : `align-items: start` sur `.home-avis .testimonials-grid` + `height: auto` sur les cards si nécessaire.
3. **Gouttières** : grid + CTA calés sur le même max-width/padding que le header (cohérence avec les autres sections).
4. Étoiles : RESTENT jaune Google `#FBBC05` (décision Robin, crédibilité du badge). Ne pas les passer en orange.

### E. Badges pills orange harmonisés (classes home only, sans risque ailleurs)
LIRE les styles actuels de `.bento-bestseller-badge`, `.giftcard-badge`, `.bento-conseil-badge`, `.bento-actu-badge`, puis les harmoniser sur UN standard : `background: var(--color-orange)`, texte blanc, `border-radius: 50px`, `font-size: 12px`, `font-weight: 600`, `padding: 5px 13px`. Position inchangée (haut-gauche des images). Si l'un d'eux a un placement spécifique voulu, ne toucher que l'apparence.

### F. Bento divers — 2 variantes, Robin tranche sur rendu
Appliquer la variante A, laisser la B en commentaire juste en dessous :
```css
/* ===== Bento divers — variante A : carte cadeau en bannière ===== */
.home-divers .bento-giftcard { grid-column: span 12; grid-row: span 1; }
/* Variante B (3 cartes égales) — décommenter pour tester, et commenter A :
.home-divers .bento-giftcard { grid-column: span 4; grid-row: span 2; }
.home-divers .bento-conseil  { grid-column: span 4; }
.home-divers .bento-actu     { grid-column: span 4; }
*/
```
Ajouter la classe `home-divers` au `<section class="hero-bento">` du bento divers (celui avec Carte cadeau + Conseil + Flash actu).

### G. Passe mobile (≤768px)
Vérifier après tout le reste : bandes crème bien edge-to-edge, paddings verticaux réduits (~2.5rem), chips pièces sur 2 colonnes (déjà en place), cards avis lisibles et à hauteur du contenu, badges pas disproportionnés, aucun scroll horizontal.

**Notes / pièges :**
- AUCUNE modification des styles partagés avec la fiche produit (`.testimonial-card` & co non scopés) ni des pages catégories/sur-mesure.
- Footer : HORS SCOPE (sujet séparé).
- Pas de nouvelle ombre. Aucun tiret cadratin. Accolades équilibrées avant push.
- En cas de blocage CSS/layout sur un point : ne pas s'acharner, le noter dans le résultat et passer au suivant (on changera de concept ensemble).

**Critères de succès :**
- Le scroll de la home alterne blanc/crème comme le mockup-15 : projet, créations et avis sur bandes crème pleine largeur.
- Entrée projet sans cadre dashed, chips blanches centrées sur crème.
- Section avis : header aligné comme les autres, badge à droite, cartes blanches à hauteur du contenu.
- 4 badges harmonisés pill orange. Bento divers en variante A avec B commentée.
- Fiche produit visuellement INCHANGÉE (vérifier une fiche sur test après push).
- Mobile propre sur tous les points du G.

### 👉 Action Robin
Revue complète sur test (desktop + mobile), mockup-15 ouvert à côté. Tranche : variante A ou B pour le bento divers. Liste tout ce qui détonne encore → micro-tâche #6bis si besoin. Ensuite : passe Yoast (avec Cowork), puis go-live.

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #5 — Passe finale SEO/technique
**Résultat (branche `feature/refonte-home`, commit `347c32f`) :**
- **A. Hiérarchie Hn** : `room-picker-title` h3→**h2** (Ton projet), Star `bento-title` h2→**h3**, storytelling-title h2→**h3**. Vérifié : **1 seul h1** (carousel) + **6 h2** = Ton projet, Collections, Les créations du moment, L'atelier, Ils en parlent, Restez informés. Le reste en h3+. (Page Conseils + modale gardent leur h3 : non touchées.)
- **B. Cleanup CSS mort** : règles `.bento-room-picker` supprimées (bloc dédié + commentaire, + retrait du sélecteur dans 2 groupes en media query). **0 occurrence** de `bento-room-picker` dans tout le repo (css/php/js). Classes globales `.room-picker-inner/-title/-card` conservées (utilisées par `.home-projet`).
- **C. Chasse références mortes (audit, sans modif)** : aucune cassée par la refonte.
  - JS : `.bento-hero`, `.bento-product-featured`, `[data-room-picker]`, `[data-room-picker-freetext]`, `[data-piece-swap]` matchent toujours le DOM (cinetique.js, product-name-formatter.js, sapi-room-picker.js, sapi-photo-swap.js). Le h2→h3 de la Star ne casse pas le formatter (cible `.bento-title` par classe).
  - JS inline front-page.php : le bandeau `.robin-bandeau` est bien réinséré **entre le carousel et `.home-projet-section`** ; logique header transparent intacte.
  - `critical-css-homepage.css` (racine) = fichier **orphelin non suivi et non référencé** par le thème → pas câblé, aucun sélecteur mort actif. Non commité.
- **D. Maillage interne — rapport** (toutes pages testées **200** sur prod, aucun lien cassé) :
  - `/mes-creations/` (room cards `?piece=` + CTA Créations), 4 catégories `/categorie-produit/{suspensions,appliques,lampesaposer,lampadaires}/` (paragraphe SEO), `/lumiere-dartisan/` (storytelling), `/conseils-eclaires/` (carte Conseil), `/actus/` + dernier post (Flash actu), `/la-star-du-moment/` (Star). Liens externes : Google Maps (atelier), g.page (laisser un avis).
- **Vérifs** : accolades PHP 61/61, CSS 3676/3676.

### 👉 Action Robin
1. Vérifier la home sur test : **rien ne doit changer visuellement** (le CSS supprimé était mort).
2. **Yoast (admin)** : relire titre SEO + meta description de la page d'accueil pour coller au nouveau contenu (`luminaire bois artisanal` + Lyon s'appuient maintenant sur du vrai texte indexable).
3. Ensuite : passe **design** (backlog 🎨), puis go-live (merge → master + déploiement prod + re-soumission GSC de la home).

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #5 — Passe finale SEO + technique (Hn, maillage, cleanup)
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**⛔ Prérequis :** #4 fait. La passe DESIGN (backlog 🎨 ci-dessous) est un chantier SÉPARÉ : ne RIEN traiter du backlog design dans cette tâche.

**Contexte :** La structure cible est en place (carousel → réassurance → Ton projet → Collections 02 → Créations 03 → L'atelier 04 → Ils en parlent 05 → bento divers → Newsletter 06). #5 verrouille le volet SEO/technique : hiérarchie des titres propre, maillage interne vérifié, CSS mort nettoyé, et vérification que rien (JS, critical CSS, hooks `is_front_page()`) ne référence des éléments supprimés.

**À faire :**

### A. Hiérarchie Hn — 3 changements de BALISE uniquement (les classes, attributs et contenus ne bougent pas)
Cible : H1 unique (carousel) → H2 = titres de section → H3 = titres de cartes. Vérifié : aucun sélecteur CSS ne cible ces balises (seul `.testimonials-header h2` existe et n'est pas concerné).
1. Section Ton projet (`.home-projet`) : `<h3 class="room-picker-title">` → `<h2 class="room-picker-title">`. ⚠️ UNIQUEMENT dans `front-page.php` (la page Conseils et la modale gardent leur h3).
2. Carte Star (`.home-creations`) : `<h2 class="bento-title product-name">` → `<h3 class="bento-title product-name">`.
3. Carte storytelling (`.home-atelier`) : `<h2 class="storytelling-title">` → `<h3 class="storytelling-title">`.
Résultat attendu : 1 seul h1 ; h2 = Ton projet, Collections, Les créations du moment, L'atelier, Ils en parlent, Restez informés ; tout le reste en h3+.

### B. Cleanup CSS mort
Supprimer les règles `.bento-room-picker` orphelines de `style.css` (flag de #1 : ~l.10835+, ~11844, ~11920 — re-localiser par grep, les numéros ont bougé). Vérifier ensuite : `grep -c "bento-room-picker"` = 0 dans style.css ET dans tous les .php / .js.

### C. Chasse aux références mortes (JS, critical CSS, hooks front)
La home a perdu/déplacé des éléments (room-picker du bento, Star du 1er bento, featured/CTA/atelier/process du 2e, 1er bento supprimé). Vérifier qu'aucun code ne cible l'ancien état :
1. `assets/homepage-carousel.js` + JS chargés sur la home (cf. enqueues `is_front_page()` dans functions.php ~l.178, 213, 257, 263, 1652, 1690, 1702) : grepper les sélecteurs `.bento-room-picker`, `.bento-hero`, `data-room-picker`, `data-piece-swap` et confirmer que chaque sélecteur matche encore le DOM actuel (le room-picker et les data-piece-swap existent toujours, juste déplacés : à confirmer, pas forcément à corriger).
2. Critical CSS homepage (bloc inline `is_front_page()` dans functions.php, fichier source `critical-css-homepage.css` à la racine) : vérifier qu'il ne référence pas de sélecteurs supprimés et que le premier viewport (carousel + bandeau) reste couvert. Pas de refonte du critical CSS ici, juste retirer les sélecteurs morts s'il y en a.
3. Le JS inline de `front-page.php` (repositionnement `.robin-bandeau`, header transparent) : confirmer qu'il fonctionne toujours avec la nouvelle structure (le bandeau doit atterrir entre carousel et `.home-projet-section`).

### D. Vérifications maillage interne (rapport, pas de modif sauf lien cassé)
Confirmer que la home maille bien vers : les 4 catégories (`/categorie-produit/...` via le paragraphe SEO), `/mes-creations/` (CTA + room cards), `/lumiere-dartisan/` (storytelling), `/conseils-eclaires/` (carte Conseil), `/actus/` + dernier post (Flash actu), `/la-star-du-moment/` (Star), `/sur-mesure/` si présent. Lister le tout dans le résultat. Corriger uniquement si un lien est cassé (404/redirection).

**Notes / pièges :**
- NE PAS toucher au backlog design (cadre avis, dashed vs pleine largeur, spans des bentos).
- Aucun tiret cadratin. Accolades PHP/CSS équilibrées avant push.

**Critères de succès :**
- `grep` : 1 seul `<h1` dans front-page.php ; les 6 h2 listés en A ; 0 occurrence de `bento-room-picker` dans le repo.
- Aucune erreur JS console sur la home test (desktop + mobile), bandeau réassurance toujours positionné sous le carousel.
- Rapport de maillage complet dans le résultat de la tâche.

### 👉 Action Robin
1. Vérifier la home sur test (rien ne doit changer visuellement, sauf si du CSS mort masquait un défaut).
2. Côté Yoast (admin, pas Claude Code) : relire le titre SEO + meta description de la page d'accueil pour qu'ils collent au nouveau contenu (le mot-clé `luminaire bois artisanal` et Lyon peuvent maintenant s'appuyer sur du vrai texte indexable).
3. Ensuite : passe design (backlog 🎨), puis quand tout te va, on parlera go-live (merge vers master + déploiement prod + re-soumission GSC de la home).

</details>

## 🎨 Backlog passe design → ABSORBÉ dans la tâche #6 ci-dessus (04/06)
_(Entrée projet → bandeau pleine largeur tranché par Robin via mockup-15 ; avis `.home-avis` → section D de #6 ; rééquilibrage bento divers → section F de #6, variantes A/B.)_

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #4 — Section « Ils en parlent » (avis Google)
**Résultat (branche `feature/refonte-home`, commit `0dda3cf`) :**
- **Section** : `<section class="product-testimonials home-avis">` (header « 05 — Ils en parlent ») insérée **entre L'atelier et le bento divers**. `$home_reviews = sapi_get_google_reviews();` ; toute la section conditionnée à `if ($home_reviews && !empty(...['reviews']))` → home propre si l'API ne répond pas.
- **Contenu** : badge Google (logo + étoiles sur `rating` + « X/5 · N avis »), 3 avis réels (`shuffle` + `array_slice` 3), texte tronqué à 200 car., CTA « Laisser un avis » + « Voir les N avis ». Blocs badge + boucle étoiles repris **verbatim** de single-product.php.
- **Version simplifiée** (vs fiche produit) : pas de photo client, pas de modale, **pas de spans `testimonial-full-*`** cachés → cartes statiques.
- **CSS** : **aucun ajout**. `.testimonial-card` n'a pas de `cursor: pointer` (juste un léger lift au hover, inoffensif) → override `cursor:default` non nécessaire. Styles `.product-testimonials*` existants suffisent.
- **Renumérotation** : newsletter 05→**06**.
- **Vérifs OK** : `sapi_get_google_reviews()` définie (functions.php:7880) ; ordre = Collections 02 → Créations 03 → L'atelier 04 → Ils en parlent 05 → bento divers → Newsletter 06 ; accolades PHP 61/61.
- ⚠️ **Si la section n'apparaît pas sur test** : vérifier `SAPI_GOOGLE_API_KEY` / `SAPI_GOOGLE_PLACE_ID` dans le wp-config du site test (clone d'avril). Côté code, rien à corriger.

### 👉 Action Robin
Vérifier sur `test.atelier-sapi.fr` (desktop + mobile) que la section avis s'affiche (sinon → constantes API du wp-config test). Une fois validé → #5 (passe finale : hiérarchie Hn, maillage conseils/blog, cleanup CSS mort `.bento-room-picker`, vérif méta).

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #4 — Section « Ils en parlent » (avis Google)
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**⛔ Prérequis :** #3 fait (commit `1d3ecb8`) mais PAS ENCORE validé par Robin. Attendre son feu vert avant de lancer.
**Mockup de référence :** `mockups/mockup-15-home-refonte-juin-2026.html` (section « Ils en parlent » — la partie presse est REPORTÉE, pas de parutions réelles à afficher pour l'instant).

**Contexte :** Aucune preuve sociale sur la home aujourd'hui. La fiche produit a déjà tout ce qu'il faut : `sapi_get_google_reviews()` (Places API, cache transient 6h, note + total + pool d'avis) et les composants `.product-testimonials` / `.testimonials-grid` / `.testimonial-card` (single-product.php ~l.545-640). On réutilise ces styles à l'identique sur la home, en version SIMPLIFIÉE : pas de photo client, pas de modale (son JS est inline dans single-product.php, on ne le duplique pas), pas de spans `testimonial-full-*`.

**À faire :**

### A. Créer la section, entre « L'atelier » et le bento divers
Insérer après le `</section>` qui ferme `.home-atelier` et avant le bento divers (`<!-- Hero Bento Grid (continued) -->`) :
```php
<!-- Ils en parlent (refonte home #4) — avis Google, réutilise les composants de la fiche produit -->
<?php $home_reviews = sapi_get_google_reviews(); ?>
<?php if ($home_reviews && !empty($home_reviews['reviews'])) : ?>
<section class="product-testimonials home-avis">
  <div class="testimonials-header">
    <span class="section-num">05</span>
    <h2>Ils en parlent</h2>
    <div class="google-reviews-badge">
      [reprendre VERBATIM le bloc .google-reviews-badge de single-product.php l.552-568 : logo Google + étoiles sur $home_reviews['rating'] + texte "X/5 · N avis"]
    </div>
  </div>

  <div class="testimonials-grid">
    <?php
    $reviews_pool = $home_reviews['reviews'];
    shuffle($reviews_pool);
    $reviews_display = array_slice($reviews_pool, 0, 3);
    ?>
    <?php foreach ($reviews_display as $review) : ?>
    <div class="testimonial-card">
      <div class="testimonial-card-header">
        <?php if (!empty($review['photo'])) : ?>
        <img class="testimonial-avatar" src="<?php echo esc_url($review['photo']); ?>" alt="" width="36" height="36" loading="lazy">
        <?php endif; ?>
        <div class="testimonial-author-info">
          <span class="author-name"><?php echo esc_html($review['author']); ?></span>
          <span class="author-time"><?php echo esc_html($review['time']); ?></span>
        </div>
      </div>
      <div class="testimonial-rating">
        [reprendre VERBATIM la boucle 5 étoiles de single-product.php l.610-616]
      </div>
      <?php
        $text = $review['text'];
        $short = $text;
        if (mb_strlen($text) > 200) {
          $short = mb_substr($text, 0, 200);
          $short = mb_substr($short, 0, mb_strrpos($short, ' ')) . '…';
        }
      ?>
      <p class="testimonial-text"><?php echo esc_html($short); ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="testimonials-cta">
    <a href="https://g.page/r/CQ0YW1uBzOimEAE/review" target="_blank" rel="noopener noreferrer" class="testimonials-cta-review">Laisser un avis sur Google</a>
    <span class="testimonials-cta-sep">·</span>
    <a href="https://www.google.com/maps/place/?q=place_id:ChIJYyWUfZOV9EcRDRhbW4HM6KY" target="_blank" rel="noopener noreferrer">Voir les <?php echo esc_html($home_reviews['total']); ?> avis</a>
  </div>
</section>
<?php endif; ?>
```
⚠️ PAS de spans `testimonial-full-*` cachés ni de `<!-- Modale avis Google -->` : la version home est statique (les cartes ne s'ouvrent pas). Si un style donne un `cursor: pointer` aux cards, ajouter `.home-avis .testimonial-card { cursor: default; }`.

### B. Renuméroter la newsletter
`<span class="section-num">05</span>` → `06` dans `.newsletter-kinetic`.

### C. CSS — uniquement si nécessaire
Les styles `.product-testimonials` / `.testimonials-*` existent déjà. Vérifier le rendu hors contexte fiche produit ; si l'espacement/la largeur déraillent, corriger avec des règles scopées `.home-avis { … }` UNIQUEMENT (ne pas toucher aux règles existantes, la fiche produit les utilise).

**Notes / pièges :**
- Toute la section est dans `if ($home_reviews && …)` : si l'API Google ne répond pas, la home reste propre (rien ne s'affiche). Si la section n'apparaît pas sur test, vérifier que `SAPI_GOOGLE_API_KEY` / `SAPI_GOOGLE_PLACE_ID` sont définies dans le wp-config du site test (clone d'avril).
- La partie PRESSE du mockup est volontairement reportée (pas de parutions réelles fournies). Ne rien inventer.
- Aucun tiret cadratin. Vérifier accolades PHP/CSS avant push.

**Critères de succès :**
- Section « Ils en parlent » (05) entre L'atelier et le bento divers : badge Google (note + nb d'avis), 3 avis réels, CTA « Laisser un avis » + « Voir les N avis ».
- Cartes statiques (pas d'erreur JS au clic), rendu cohérent desktop + mobile.
- Newsletter numérotée 06. Ordre final : carousel → réassurance → Ton projet → Collections 02 → Créations 03 → L'atelier 04 → Ils en parlent 05 → bento divers → Newsletter 06.

### 👉 Action Robin
Vérifier sur `test.atelier-sapi.fr` que la section avis s'affiche (sinon → constantes API du wp-config test à contrôler) et que le rendu te va. Ensuite → #5 (passe finale : hiérarchie Hn, maillage conseils/blog, cleanup CSS mort, vérif méta).

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #3 — Section « L'atelier »
**Résultat (branche `feature/refonte-home`, commit `1d3ecb8`) :**
- **Section** : nouvelle `<section class="hero-bento home-atelier">` (header « 04 — L'atelier ») après « Les créations du moment ». Storytelling enrichi (label « Mon atelier à Lyon » sans le num, + paragraphe `storytelling-text--seo` verbatim avec 4 liens catégories via helper `$sapi_cat_url`) + photo atelier (verbatim) + frise process (verbatim **moins** `<span class="process-number">03</span>`).
- **Retraits / déplacements** : 1er bento **entièrement supprimé** ; Carte cadeau déplacée en **tête** du bento divers (carte cadeau + Conseil + Flash actu). Process + Atelier retirés du 2e bento (montés dans L'atelier).
- **Renumérotation** : newsletter 04→**05**. Plus aucun `process-number` résiduel (doublon « 03 » éliminé).
- **CSS** : règle header généralisée `.home-creations …` → `.hero-bento .section-header-kinetic` (sert #2 et #3) ; bloc `.home-atelier` (storytelling span 7 / atelier span 5) ; liens SEO stylés bois→orange au hover (contre le `a:hover` bleu global).
- **Vérifs OK** : ordre final = carousel → réassurance(JS) → Ton projet → Collections 02 → Créations 03 → L'atelier 04 → bento divers → Newsletter 05 ; accolades PHP 60/60, CSS 3679/3679 ; chaque carte déplacée 1× exactement.
- ℹ️ Si le paragraphe SEO clippe en desktop (`.bento-card` overflow:hidden + rows 200px), fallback prévu `.home-atelier .bento-storytelling { grid-row: span 3; }` — **pas appliqué d'office**, à voir sur rendu.

### 👉 Action Robin
Vérifier sur `test.atelier-sapi.fr` (desktop + mobile) : rendu section L'atelier, **pas de clipping du paragraphe SEO**, les 4 liens catégories (URLs `/categorie-produit/…/`, couleur bois/orange, pas de bleu), et l'allure du bento divers. Une fois validé → #4 (« Ils en parlent » : avis Etsy + presse).

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #3 — Section « L'atelier » : storytelling + photo + process + bloc texte SEO
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**⛔ Prérequis :** #2 fait (commit `55f3888`) mais PAS ENCORE validé par Robin. Attendre son feu vert avant de lancer cette tâche.
**Mockup de référence :** `mockups/mockup-15-home-refonte-juin-2026.html` (section « L'atelier »).

**Contexte :** Le storytelling (1er bento), la photo atelier et la frise process (2e bento) racontent la même chose : l'artisan. On les réunit dans UNE section « L'atelier » placée juste après « Les créations du moment », et on y ajoute le **bloc texte SEO** (contenu rédactionnel indexable avec maillage vers les 4 catégories). C'est l'enjeu n°1 de la refonte côté référencement. Même méthode que #2 : on déplace les cartes bento existantes dans un `.bento-container` neuf, styles internes globaux, seulement 2 overrides de span.

**À faire :**

### A. Créer la section, juste après « Les créations du moment »
Insérer entre le `</section>` qui ferme `.home-creations` et le `<!-- Hero Bento Grid (continued) -->` (2e bento) :
```php
<!-- L'atelier (refonte home #3) — storytelling + photo atelier + process + texte SEO -->
<?php
// URLs catégories pour le maillage interne (slugs canon : voir tax_query carousel)
$sapi_cat_url = function ($slug) {
  $t = get_term_by('slug', $slug, 'product_cat');
  $l = $t ? get_term_link($t) : '';
  return (!is_wp_error($l) && $l) ? $l : home_url('/mes-creations/');
};
?>
<section class="hero-bento home-atelier">
  <div class="section-header-kinetic">
    <span class="section-num">04</span>
    <h2 class="section-title-kinetic">L'atelier</h2>
  </div>
  <div class="bento-container">

    <div class="bento-card bento-storytelling">
      <div class="storytelling-inner">
        <span class="storytelling-label">Mon atelier à Lyon</span>
        <h2 class="storytelling-title">Des sculptures lumineuses</h2>
        <p class="storytelling-text">Du croquis à l'assemblage final, chaque pièce est façonnée dans mon atelier lyonnais. Le bois prend forme sous mes mains, la lumière fait le reste.</p>
        <p class="storytelling-text storytelling-text--seo">Je dessine et fabrique à la commande des <a href="<?php echo esc_url($sapi_cat_url('suspensions')); ?>">suspensions</a>, <a href="<?php echo esc_url($sapi_cat_url('appliques')); ?>">appliques</a>, <a href="<?php echo esc_url($sapi_cat_url('lampesaposer')); ?>">lampes à poser</a> et <a href="<?php echo esc_url($sapi_cat_url('lampadaires')); ?>">lampadaires</a> en bois massif. Chaque luminaire est découpé au laser puis assemblé à la main : le peuplier clair ou l'okoumé chaleureux filtrent la lumière et dessinent des ombres uniques.</p>
        <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="storytelling-link">
          <span>Découvrir l'artisan</span>
          <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
          </svg>
        </a>
      </div>
    </div>

    <!-- Carte photo atelier : déplacer ici le bloc <!-- Atelier Image --> existant du 2e bento, VERBATIM (lien Maps + label « L'atelier · Lyon ») -->

    <!-- Carte process : déplacer ici le bloc <!-- Process Card --> existant du 2e bento, en retirant UNIQUEMENT <span class="process-number">03</span> du process-header (numéro redondant avec le header de section) -->

  </div>
</section>
```
⚠️ Le storytelling ci-dessus reprend le markup existant à l'identique SAUF : (1) le label perd son `<span class="storytelling-num">01</span>` et devient « Mon atelier à Lyon » (signal géo, non redondant avec le header) ; (2) ajout du paragraphe `storytelling-text--seo`. Reprendre le texte SEO EXACTEMENT tel quel (wording validé, aucun tiret cadratin).

### B. Retirer les cartes déplacées + consolider les bentos restants
1. 1er bento : après retrait du storytelling, il ne reste QUE la Carte cadeau → **supprimer toute la section** du 1er bento, et déplacer le bloc `<!-- Carte Cadeau -->` (`bento-giftcard`, avec son `<?php if ($gift_card) : ?> … <?php endif; ?>`) en PREMIER dans le 2e bento.
2. 2e bento : retirer `<!-- Atelier Image -->` et `<!-- Process Card -->` (déplacés en A). Il reste : Carte cadeau + Conseil + Flash actu. Ce bento « divers » est un état transitoire assumé (la section Avis #4 viendra s'intercaler avant). Si la grille paraît déséquilibrée, ne pas bricoler les spans, on verra sur rendu.

### C. Renuméroter la newsletter
Dans `<section class="newsletter-kinetic">` : `<span class="section-num">04</span>` → `05`.

### D. CSS
1. **Remplacer** la règle de #2 `.home-creations .section-header-kinetic { … }` par une version générique qui sert #2 ET #3 (mêmes déclarations) :
```css
/* ===== Refonte home #2/#3 — headers de section dans les bentos ===== */
.hero-bento .section-header-kinetic {
  padding: 0;
  max-width: none;
  margin: 0 0 1.5rem;
}
```
2. **Ajouter** :
```css
/* ===== Refonte home #3 — Section L'atelier ===== */
.home-atelier .bento-storytelling { grid-column: span 7; }
.home-atelier .bento-atelier { grid-column: span 5; }
.storytelling-text--seo {
  margin-top: 0.75rem;
  font-size: 0.92em;
  opacity: 0.9;
}
.storytelling-text--seo a {
  color: var(--color-wood);
  text-decoration: underline;
  text-underline-offset: 2px;
}
@media (hover: hover) {
  .storytelling-text--seo a:hover { color: var(--color-orange); }
}
```
⚠️ Les liens du paragraphe SEO DOIVENT être stylés explicitement (règles ci-dessus) : le `a:hover` global passe en bleu sinon (piège connu).

**Notes / pièges :**
- `.bento-card` a `overflow: hidden` et la grille des rows fixes (200px). Si le paragraphe SEO fait clipper le contenu du storytelling en desktop, ajouter `.home-atelier .bento-storytelling { grid-row: span 3; }` (fallback autorisé). Vérifier aussi en mobile.
- Ne pas toucher au contenu de la carte Conseil ni Flash actu.
- Les anciens styles `.bento-room-picker` morts (flag de #1) restent hors scope.
- Vérifier l'équilibre des accolades PHP/CSS avant push.

**Critères de succès :**
- Section « L'atelier » (04) après « Les créations du moment » : storytelling enrichi à gauche (7 col), photo atelier à droite (5 col), frise process pleine largeur dessous, sans numéro « 03 » résiduel.
- Le paragraphe SEO s'affiche en entier (pas de clipping), avec 4 liens fonctionnels vers les pages catégories (URLs en `/categorie-produit/…/`), stylés bois/orange (pas de bleu au hover).
- 1er bento supprimé ; le bento « divers » (Carte cadeau + Conseil + Flash actu) s'affiche entre L'atelier et la newsletter.
- Newsletter numérotée 05. Ordre final de la page : carousel → réassurance → Ton projet → Collections 02 → Créations 03 → L'atelier 04 → bento divers → Newsletter 05.

### 👉 Action Robin
Vérifier sur `test.atelier-sapi.fr` : rendu de la section L'atelier (desktop + mobile), lisibilité du paragraphe SEO, les 4 liens catégories, et l'allure du bento divers. Une fois validé → #4 (section « Ils en parlent » : avis Etsy + presse).

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #2 — « Les créations du moment »
**Résultat (branche `feature/refonte-home`, commit `55f3888`) :**
- **Data** : `posts_per_page` 4→8 + `break` remplacé par `if (count($featured_products) >= 2) break;` → récupère désormais **2** produits featured (l.217 / l.255).
- **Section** : nouvelle `<section class="hero-bento home-creations">` (header « 03 — Les créations du moment ») insérée **juste après Collections**, avant le 2e bento. Star (grande) + boucle `$featured_products` (2 cartes `bento-product-featured`, attributs `data-piece-swap*` conservés) + CTA « Toutes les créations ». Spans bento réutilisés, **aucun CSS de carte réécrit**.
- **Retraits** : Star du 1er bento ; produit featured + CTA du 2e bento. Storytelling + Carte cadeau (1er) et Process + Atelier + Conseil + Flash actu (2e) **intacts**.
- **CSS** : 1 bloc `.home-creations .section-header-kinetic` (padding:0 / max-width:none / margin) pour aligner le header sur les cartes.
- **Vérifs OK** : 1 seule occurrence de `bento-hero`/`bento-cta`/`bento-product-featured` (dans la nouvelle section) ; accolades PHP 59/59, CSS 3673/3673.
- ⚠️ **État transitoire assumé** (cf. énoncé) : doublon temporaire du numéro « 03 » (section + carte Process), 1er bento à 2 cartes, 2e à 4 — sera re-séquencé / rééquilibré en #3.

### 👉 Action Robin
Regarder `test.atelier-sapi.fr` (desktop + mobile) : valider le regroupement et le layout (Star à gauche, 2 produits à droite, CTA en bandeau ; 2 produits distincts). Une fois validé → #3 (section « L'atelier » : storytelling + frise process + bloc texte SEO).

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #2 — Regrouper les portes produit en « Les créations du moment »
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**Mockup de référence :** `mockups/mockup-15-home-refonte-juin-2026.html` (section « Les créations du moment »).
**Prérequis :** #1 fait (commit `d939e22`).

**Contexte :** Les portes produit sont aujourd'hui éparpillées : la **Star du moment** est dans le 1er bento, le **produit featured** et le **CTA "Toutes les créations"** sont dans le 2e bento, séparés par la section Collections. On les regroupe dans UNE section claire « Les créations du moment », placée **juste après Collections**. On réutilise les cartes bento existantes (`bento-hero`, `bento-product-featured`, `bento-cta`) dans un `.bento-container` neuf : leurs spans (`8×3`, `4×2`, `12×1`) donnent exactement le layout voulu (Star à gauche, 2 produits empilés à droite, CTA en bandeau). **Zéro CSS de carte à réécrire.**

**À faire :**

### A. Data — récupérer 2 produits featured au lieu d'1
Dans `front-page.php`, bloc `// Featured products for Bento grid (random product)` (~l.213). Deux changements :
- `posts_per_page` : passer de `4` à `8` (pour trouver 2 produits avec photo `detail`).
- Remplacer la ligne `break; // Only need 1` par :
```php
        if (count($featured_products) >= 2) break;
```
(le `$featured_products[] = [...]` juste au-dessus reste inchangé.)

### B. Créer la section, juste après Collections
Insérer entre la fin de `<!-- Collections Carousel / Grid -->` (le `</section>` qui ferme `.collections-kinetic`) et le `<!-- Hero Bento Grid (continued) -->` (2e bento) :
```php
<!-- Les créations du moment (refonte home #2) — regroupe Star + produits featured + CTA -->
<section class="hero-bento home-creations">
  <div class="section-header-kinetic">
    <span class="section-num">03</span>
    <h2 class="section-title-kinetic">Les créations du moment</h2>
  </div>
  <div class="bento-container">

    <?php if ($star_product_data) : ?>
    <a href="<?php echo esc_url($star_product_data['url']); ?>" class="bento-card bento-hero">
      <?php echo wp_get_attachment_image($star_product_data['image_id'], 'woocommerce_single', false, ['class' => 'bento-bg-img', 'loading' => 'lazy', 'alt' => $star_product_data['name'] . ', star du moment']); ?>
      <span class="bento-bestseller-badge">Star du moment</span>
      <div class="bento-content">
        <h2 class="bento-title product-name"><?php echo esc_html($star_product_data['name']); ?></h2>
        <?php if ($star_product_data['category']) : ?>
          <p class="bento-category"><?php echo esc_html($star_product_data['category']); ?></p>
        <?php endif; ?>
      </div>
    </a>
    <?php endif; ?>

    <?php foreach ($featured_products as $fp) : ?>
    <a href="<?php echo esc_url($fp['url']); ?>" class="bento-card bento-product-featured" data-product-id="<?php echo esc_attr($fp['id']); ?>" data-piece-swap data-piece-swap-type="detail" data-piece-swap-size="large">
      <?php echo wp_get_attachment_image($fp['image_id'], 'large', false, ['class' => 'bento-bg-img', 'loading' => 'lazy', 'alt' => $fp['name'] . ', luminaire artisanal']); ?>
      <div class="bento-product-featured-info">
        <h3><?php echo esc_html($fp['name']); ?></h3>
        <span class="bento-product-featured-price"><?php echo wp_kses_post($fp['price']); ?></span>
      </div>
    </a>
    <?php endforeach; ?>

    <a href="<?php echo home_url('/mes-creations/'); ?>" class="bento-card bento-cta">
      <h3 class="cta-title">Toutes les créations</h3>
      <span class="cta-button">
        <span>Explorer</span>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/></svg>
      </span>
    </a>

  </div>
</section>
```

### C. Retirer les cartes déplacées de leurs anciens bentos
- 1er bento (`<section class="hero-bento">`, le premier) : supprimer le bloc `<!-- Star du moment -->` (`<?php if ($star_product_data) : ?> … <?php endif; ?>` avec `class="bento-card bento-hero"`).
- 2e bento (`<!-- Hero Bento Grid (continued) -->`) : supprimer le bloc `<!-- Product Card - Random Featured Product -->` (`bento-product-featured`, utilise `$featured_products[0]`) ET le bloc `<!-- CTA Card -->` (`bento-cta`, « Toutes les créations »).
- NE PAS toucher aux autres cartes : Storytelling + Carte cadeau (1er bento) ; Process + Atelier + Conseil + Flash actu (2e bento).

### D. CSS — un seul bloc (alignement du header avec les cartes)
Le `.hero-bento` apporte déjà `padding: 3rem` + `max-width: 1600px` + centrage. Mais `.section-header-kinetic` apporte son PROPRE `padding: 0 3rem` + max-width, ce qui le décalerait de 3rem par rapport aux cartes. Neutraliser, scopé à la nouvelle section. Ajouter dans `style.css` :
```css
/* ===== Refonte home #2 — Section Les créations du moment ===== */
.home-creations .section-header-kinetic {
  padding: 0;
  max-width: none;
  margin: 0 0 1.5rem;
}
```

**Notes / pièges :**
- État transitoire assumé : après #2, le 1er bento n'a plus que Storytelling + Carte cadeau, et le 2e que Process + Atelier + Conseil + Flash actu. Storytelling + Process partiront dans #3 (« L'atelier »). On ne rééquilibre pas les bentos maintenant.
- Numérotation : la section porte « 03 », et la carte Process affiche aussi « 03 » pour l'instant. Doublon temporaire, re-séquencé en #3. Ne pas y toucher ici.
- Garder les attributs `data-piece-swap*` sur les cartes featured (swap photo par pièce).
- Aucun tiret cadratin/demi-cadratin. Ne pas ajouter d'ombre aux cartes qui n'en ont pas.
- Vérifier l'équilibre des accolades PHP/CSS avant push.

**Critères de succès :**
- Nouvelle section « Les créations du moment » (header 03) juste après Collections, avec Star (grande, à gauche), 2 produits featured (à droite), et le CTA « Toutes les créations » en bandeau.
- 2 produits featured distincts s'affichent (data récupère bien 2).
- La Star a disparu du 1er bento ; le produit featured et le CTA ont disparu du 2e bento ; les autres cartes restent.
- Rendu desktop conforme au mockup, mobile correct (cartes empilées).

### 👉 Action Robin
Regarder `test.atelier-sapi.fr` (desktop + mobile) : valider le regroupement et le layout de la section. Une fois validé → #3 (section « L'atelier » : storytelling + frise process + bloc texte SEO).

</details>

## ✅ [FAIT 2026-06-04 — sur test] Refonte home #1 — Entrée projet en section dédiée
**Résultat (branche `feature/refonte-home`, commit `d939e22`) :**
- `front-page.php` : nouveau `<section class="home-projet-section">` inséré juste après le carousel (`endif` ~l.470) et avant le 1er bento. Markup room-picker repris, eyebrow « Ton projet », titre au **tutoiement** (« cherches-tu »), `isset()` sur `$room_icons`. Attributs `data-room-picker` + `data-room-picker-freetext` **préservés** (câblage JS Conseiller/Mon Projet intact).
- Bloc `bento-room-picker` retiré du 1er bento → il ne reste que 3 cartes (Star, Storytelling, Carte cadeau). Rééquilibrage du bento **volontairement pas fait** (prévu en tâche suivante, à voir sur rendu).
- `style.css` : bloc `/* Refonte home #1 */` ajouté après `.advice-room-picker*` (fond warm, bordure dashed, eyebrow, cards centrées, mobile 2 colonnes).
- **Vérifs OK** : 0 référence `bento-room-picker` dans le markup ; accolades front-page 59/59, CSS 3672/3672 ; `$room_choices`/`$room_icons` définis (l.194/203) avant usage.
- ⚠️ **Cleanup à prévoir** : les règles CSS `.bento-room-picker` (style.css l.10835+, 11844, 11920) sont maintenant **mortes** (plus aucun markup). Pas supprimées ici pour rester dans le scope ; à nettoyer dans une tâche dédiée.

### 👉 Action Robin
Regarder `test.atelier-sapi.fr` (desktop + mobile) : valider placement + rendu, et dire si tu préfères ce cadre warm contenu (dashed) ou un bandeau crème pleine largeur edge-to-edge. Une fois validé → on enchaîne #2 (regrouper les portes produit en « Créations du moment »).

<details><summary>Énoncé original</summary>

## [TÂCHE] Refonte home #1 — Remonter l'entrée projet en section dédiée
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** `feature/refonte-home` (push auto à la fin, jamais master).
**Mockup de référence :** `mockups/mockup-15-home-refonte-juin-2026.html` (section "Pour quelle pièce ?").

**Contexte :** Aujourd'hui le room-picker "Pour quelle pièce ?" est noyé comme 4e carte du 1er bento (`.bento-room-picker`), donc invisible. C'est l'entrée n°1 du tunnel. On la sort du bento et on la promeut en **section pleine largeur juste après le carousel** (donc après le bandeau réassurance injecté en JS, et avant le 1er bento). On RÉUTILISE le markup room-picker existant et ses styles globaux : ne rien réinventer.

**À faire :**

1. **Retirer** de `front-page.php` le bloc commenté `<!-- Pour quelle pièce ? … -->` correspondant à `<div class="bento-card bento-room-picker" data-room-picker>` (actuellement ~lignes 516-540), à l'intérieur du 1er `<section class="hero-bento"><div class="bento-container">`. NE PAS toucher aux 3 autres cartes du bento (Star, Storytelling, Carte cadeau).

2. **Insérer** juste après la fin de la section carousel (après le `<?php endif; ?>` qui ferme `if ($total_slides > 0)`, ~ligne 470) et AVANT le `<!-- Hero Bento Grid -->` (~ligne 472), cette nouvelle section. Le markup interne est repris à l'identique de la version page Conseils (`page-conseils-eclaires.php` ~ligne 218), donc les variables `$room_choices` / `$room_icons` déjà présentes dans `front-page.php` fonctionnent :

```php
<!-- Entrée projet — room picker promu en section pleine largeur (refonte home #1) -->
<section class="home-projet-section">
  <div class="home-projet" data-room-picker>
    <div class="room-picker-inner">
      <span class="home-projet__eyebrow">Ton projet</span>
      <h3 class="room-picker-title">Pour quelle pièce cherches-tu un luminaire ?</h3>
      <div class="room-picker-cards">
        <?php foreach ($room_choices as $room) :
          $icon_svg = isset($room_icons[$room['icon']]) ? $room_icons[$room['icon']] : '';
        ?>
          <a class="room-card" href="<?php echo esc_url(home_url('/mes-creations/?piece=' . $room['slug'])); ?>" data-piece="<?php echo esc_attr($room['slug']); ?>">
            <span class="room-card-icon"><?php echo $icon_svg; ?></span>
            <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="room-picker-or" aria-hidden="true">
        <span class="room-picker-or__text">ou</span>
      </div>
      <form class="room-picker-freetext" data-room-picker-freetext>
        <input type="text" class="room-picker-freetext__input" name="freetext"
               placeholder="Décris ton projet en quelques mots…" maxlength="500"
               aria-label="Décris ton projet en quelques mots">
        <button type="submit" class="room-picker-freetext__submit" aria-label="Envoyer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </button>
      </form>
    </div>
  </div>
</section>
```

⚠️ Conserver IMPÉRATIVEMENT les attributs `data-room-picker` et `data-room-picker-freetext` (ils câblent le JS Conseiller / Mon Projet). Wording du titre passé au **tutoiement** ("cherches-tu", pas "cherchez-vous").

3. **Ajouter** dans `style.css` ce bloc (à la suite des règles `.advice-room-picker*`, ~ligne 7910). Il est calqué sur `.advice-room-picker` mais nommé `home-projet*` pour découpler la home de la page Conseils. Les cartes sont déjà stylées par les règles globales `.room-card` / `.room-card-icon` / `.room-card-label`, ne pas les redéclarer :

```css
/* ===== Refonte home #1 — Section entrée projet ===== */
.home-projet-section {
  max-width: 1400px;
  margin: 2.5rem auto;
  padding: 0 2rem;
}
.home-projet {
  position: relative;
  background: var(--color-warm, #FBF6EA);
  border-radius: 16px;
  padding: 2.75rem 2rem;
  text-align: center;
  overflow: hidden;
}
.home-projet::before {
  content: "";
  position: absolute;
  inset: 12px;
  border: 1.5px dashed rgba(147, 125, 104, 0.35);
  border-radius: 12px;
  pointer-events: none;
}
.home-projet .room-picker-inner { position: relative; z-index: 1; }
.home-projet__eyebrow {
  display: block;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  font-size: 0.82rem;
  color: var(--color-wood);
  opacity: 0.85;
  margin-bottom: 0.5rem;
}
.home-projet .room-picker-cards {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 1.25rem;
  width: 100%;
}
@media (max-width: 768px) {
  .home-projet-section { padding: 0 1rem; }
  .home-projet { padding: 1.75rem 1.25rem; }
  .home-projet .room-picker-cards { gap: 0.75rem; }
  .home-projet .room-card { flex: 0 0 calc(50% - 0.5rem); max-width: none; padding: 1rem 0.75rem; }
}
```

**Notes / pièges :**
- Après retrait, le 1er bento n'a plus que 3 cartes. Le `.bento-container` est en grille 12 colonnes `grid-auto-flow: dense` : les 3 cartes restantes vont se réagencer. Si ça paraît déséquilibré, NE PAS bricoler les spans dans cette tâche, on rééquilibrera le bento dans une tâche suivante une fois vu sur le rendu.
- Ne pas ajouter d'ombre aux cartes qui n'en avaient pas. Aucun tiret cadratin ni demi-cadratin dans les textes.
- Vérifier l'équilibre des accolades CSS/PHP avant de pousser (pas de PHP local pour `php -l`).

**Critères de succès :**
- La section "Pour quelle pièce ?" apparaît en pleine largeur juste sous le bandeau réassurance, avant le 1er bento, fond warm + bordure dashed, 6 pièces + "ou" + champ libre.
- Cliquer une pièce ou soumettre le champ libre déclenche le même comportement Conseiller/Mon Projet qu'avant (attributs data préservés).
- Le room-picker a bien DISPARU du 1er bento ; les 3 autres cartes du bento s'affichent toujours.
- Rendu mobile correct (pièces sur 2 colonnes).

### 👉 Action Robin
Regarder `test.atelier-sapi.fr` (desktop + mobile) : valider le placement et le rendu, et dire si tu préfères ce cadre warm contenu (dashed) ou un bandeau crème pleine largeur edge-to-edge (ajustable en 1 tâche). Une fois validé, on enchaîne avec #2 (regrouper les portes produit en "Créations du moment").

</details>

## ✅ [VALIDÉ ROBIN 2026-06-04 — sur test] Setup branche refonte home + déploiement test
**Validation :** run `Deploy to Test Server` #2491 vert (18s) sur `feature/refonte-home` ; home rendue à l'identique sur test.atelier-sapi.fr, aucune régression. Prêt pour la 1re tâche de structure.

**Résultat (branche `feature/refonte-home`, commit `af1dabd`) :**
- Branche `feature/refonte-home` créée depuis `test-theme-sapi-maison` et poussée sur origin (tracking set).
- `.github/workflows/deploy-test.yml` : ajout de `feature/refonte-home` à `on.push.branches` (à côté de `test-theme-sapi-maison` et `feature/photos-par-piece`).
- Push effectué → le workflow `Deploy to Test Server` se déclenche sur cette branche. `front-page.php` **inchangé** : aucune modif de structure à ce stade.
- ⚠️ `gh` non installé en local : je n'ai pas pu lire le statut du run dans l'onglet Actions. À confirmer côté GitHub (doit être vert).

### 👉 Action Robin
Vérifier dans l'onglet **Actions** que `Deploy to Test Server` est passé au vert sur `feature/refonte-home`, puis vérifier le rendu de la home sur `test.atelier-sapi.fr` : doit être **identique** à l'actuelle (carousel + bento + reste OK, aucune régression). Une fois confirmé, on enchaîne avec la 1re tâche de structure (injection de la nouvelle ossature dans `front-page.php`, mockup de réf : `mockups/mockup-15-home-refonte-juin-2026.html`).

<details><summary>Énoncé original</summary>

## [TÂCHE] Setup branche refonte home + déploiement test
**Date :** 2026-06-04
**Priorité :** haute
**Branche :** créer `feature/refonte-home` **depuis `test-theme-sapi-maison`**. Push auto autorisé (branche feature/test). Jamais de push master.

**Contexte :** On démarre la refonte complète de la page d'accueil (`front-page.php`). On développe sur une branche dédiée, déployée sur `test.atelier-sapi.fr`, sans casser la home actuelle (qui reste intacte sur `test-theme-sapi-maison` et `master`). Rappel : le site de test n'a qu'une version du thème à la fois (déploiement FTP via GitHub Actions), il affichera donc la branche poussée en dernier. C'est assumé.

**À faire :**
1. Créer la branche `feature/refonte-home` à partir de `test-theme-sapi-maison`.
2. Dans `.github/workflows/deploy-test.yml`, ajouter `feature/refonte-home` à `on.push.branches` (à côté de `test-theme-sapi-maison` et `feature/photos-par-piece`).
3. Commit + push `feature/refonte-home` pour déclencher un premier déploiement test.
4. Vérifier dans l'onglet Actions que le workflow `Deploy to Test Server` s'exécute sans erreur.
5. NE RIEN modifier d'autre dans `front-page.php` à ce stade. La refonte arrivera dans des tâches suivantes, section par section (mockup de référence : `mockups/mockup-15-home-refonte-juin-2026.html`).

**Critères de succès :**
- `feature/refonte-home` existe et est poussée sur origin.
- Un push sur cette branche déclenche bien `deploy-test.yml` (run visible dans Actions, statut vert).
- `test.atelier-sapi.fr` rend la home actuelle **à l'identique** depuis cette branche : aucune régression visuelle, le carousel + le bento + tout le reste fonctionnent comme avant.

**Critères de succès :**
- `feature/refonte-home` existe et est poussée sur origin.
- Un push sur cette branche déclenche bien `deploy-test.yml` (run visible dans Actions, statut vert).
- `test.atelier-sapi.fr` rend la home actuelle **à l'identique** depuis cette branche : aucune régression visuelle, le carousel + le bento + tout le reste fonctionnent comme avant.

</details>

## ✅ [FAIT 2026-06-03 — sur test] /sur-mesure/ #6 + disclaimer + suppression form mort
**Résultat (branche `test`, commits `b99c9c8` + `572d69f`) :**
- **Partie A** — `sapi_handle_surmesure_form` (functions.php ~6525) : upsert Brevo liste #6 `attributes:['SOURCE'=>'conseiller']` **uniquement** (piège 400 évité), `updateEnabled:true`, après validation email, **non bloquant** (error_log `[sapi-brevo-conseiller] …/sur-mesure/…` ; email de notif + retour continuent). Disclaimer opt-in (vouvoiement, classe `.contact-disclaimer`) sous le bouton dans `page-sur-mesure.php`.
- **Partie B** — supprimés : le bloc inline mort `conseiller-surmesure-wrap` (archive-product.php), le bloc enqueue commenté dans functions.php, et le fichier `assets/sapi-surmesure-card.js`. La card-lien orange `mes-creations-surmesure-card` (→ /sur-mesure/) est **conservée**.
- **Vérifs OK** : 0 référence restante à `sapi-surmesure-card` / `conseiller-surmesure-wrap` ; accolades équilibrées (functions.php 889/889, archive 34/34) ; body Brevo = `listIds:[6]` + `SOURCE` seul. `php -l` impossible (pas de PHP local).

### 👉 Action Robin
Tester sur `test.atelier-sapi.fr` : soumission du formulaire `/sur-mesure/` → contact créé dans Brevo #6 (`SOURCE=conseiller`) + email de notif reçu + disclaimer visible ; vérifier que /mes-creations/ affiche toujours la card-lien orange. Puis « go prod » → merge test → master (avec tout le lot en attente).

<details><summary>Énoncé original</summary>

## [TÂCHE] /sur-mesure/ : inscrire à #6 (SOURCE=conseiller) + disclaimer, et supprimer le form inline mort
**Date :** 2026-06-03
**Branche :** à confirmer avec Robin avant de commiter. Pas de push master sans accord.
**Priorité :** normale

**Contexte :** Découverte de 3 formulaires "sur-mesure". La modale Conseiller (`sapi_megafilter_surmesure`) inscrit déjà à #6 `SOURCE=conseiller` + disclaimer (FAIT, validé). Restent deux choses, décidées avec Robin le 03/06 : (A) inscrire le VRAI entonnoir = la page /sur-mesure/ (qui n'inscrit personne aujourd'hui) ; (B) supprimer un formulaire inline mort.

### Partie A — Inscrire les demandes /sur-mesure/ à #6
Handler `sapi_handle_surmesure_form` (functions.php ~6498), POST classique, champs `fullname` / `email` / `message` / `robin_project`.
- Après la validation `if (!is_email($email))` (~6525), ajouter un upsert Brevo identique au pattern déjà utilisé (modale / robin_contact) :
  - `$api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';`
  - si clé : POST `https://api.brevo.com/v3/contacts`, body `['email'=>$email,'listIds'=>[6],'attributes'=>['SOURCE'=>'conseiller'],'updateEnabled'=>true]`.
  - IMPORTANT : n'envoyer QUE l'attribut `SOURCE` (pas de nom/message/téléphone → Brevo rejette en 400, contact jamais inscrit ; bug déjà vécu).
  - `error_log` sur échec, NON bloquant : l'email de notif à Robin et la confirmation/redirection doivent continuer même si Brevo échoue.
- Disclaimer opt-in sous le bouton d'envoi du formulaire dans `page-sur-mesure.php` (`#sur-mesure-form`), même style/CSS `.contact-disclaimer` que la modale :
  > En envoyant votre demande, vous acceptez de recevoir occasionnellement des nouvelles de l'Atelier Sâpi. Désinscription possible à tout moment.

### Partie B — Supprimer le formulaire inline mort
- Dans `woocommerce/archive-product.php` : supprimer UNIQUEMENT le bloc inline mort `conseiller-surmesure-wrap` / `conseiller-surmesure-card` / `conseiller-surmesure-form` (~ligne 535+, `data-surmesure-wrap hidden`).
- NE PAS toucher au template `mes-creations-surmesure-card` (~ligne 276) : c'est la card-lien orange vers /sur-mesure/, qui FONCTIONNE et qu'on garde.
- Supprimer le bloc d'enqueue commenté de `sapi-surmesure-card.js` dans functions.php (~488-497) et le fichier `assets/sapi-surmesure-card.js`.
- Vérifier qu'aucun autre fichier ne référence `sapi-surmesure-card` après suppression.

### Critères de succès
- Une soumission /sur-mesure/ crée un contact #6 `SOURCE=conseiller` (vérifiable côté Brevo) ; l'email de notif part toujours, même si Brevo échoue.
- Disclaimer visible sous le bouton du formulaire /sur-mesure/.
- Plus aucune trace du form inline mort ni de son JS ; la card-lien orange /mes-creations/ → /sur-mesure/ reste intacte.

</details>

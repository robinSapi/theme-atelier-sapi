# Audit — page « Mes créations » (/mes-creations/)
*10 juin 2026 — audit code (`woocommerce/archive-product.php`, `assets/sapi-cards-conseiller.js`) + rendu réel sur test (états avec projet / sans projet). Base de la passe de planification refonte. Lecture seule, aucune modif.*

Template : `woocommerce/archive-product.php` (v9.5.1, en-tête encore « SAPI CINÉTIQUE »). Carte produit : `content-product.php` n'est **pas** utilisé ici — la grille est rendue en dur dans `archive-product.php` (`.product-card-cinetique`). Cartes Robin pilotées par `sapi-cards-conseiller.js` (+ `sapi-project.js` pour la logique projet, `shop.js` pour le filtrage, `sapi-photo-swap.js`, `product-name-formatter.js`).

---

## 1. La page de haut en bas (5 blocs)

### Bloc 1 — Hero `.shop-hero-artisan`
Minimal : un `<h1>` manuscrit « Mes créations » posé sur une **photo de croquis** (carnet de Robin) en fond, fixée en CSS. Aucun sous-titre, aucune accroche, aucun CTA. **Gros potentiel inexploité** (c'est la 1re chose vue, et elle ne dit rien de l'offre ni n'oriente).

### Bloc 2 — « Ma sélection » `.conseiller-cards-zone.mes-creations-selection`
Zone à **deux cartes mutuellement exclusives** (toggle JS selon `localStorage.sapiProject`) :

**2a. Carte « Conseil de Robin »** (`.conseiller-card--conseil`, état SANS projet)
- Panneau **crème à cadre pointillé** (`dashed`) — **ANCIEN style**, identique à ce qu'était la page Conseils avant la Phase 1.
- Badge texte **« ✏ Conseil de Robin »** (`.conseiller-badge`, ancien) — **pas la pill V1**, **pas de grain bois**.
- Titre **« POUR QUELLE PIÈCE CHERCHEZ-VOUS UN LUMINAIRE ? »** → ⚠️ **VOUVOIEMENT** (devrait être « cherches-tu »).
- Room-picker : **7 pièces** (Salon, Cuisine, Chambre, Chambre enfant, Bureau, Entrée, Escalier) en cartes blanches (icône crème + label) + séparateur « ou » + champ libre « Décris ton projet… » + bouton rond orange.
- C'est **exactement la cible de la Phase 3** (aligner sur la home : pill V1, grain, hover orange, tutoiement). Aujourd'hui non fait.

**2b. Carte « Mon projet » englobante** (`.conseiller-card--mon-projet`, état AVEC projet)
- Bandeau **photo pleine largeur de la pièce** du projet (4 ambiances de la pièce, via `data-piece-photos` ACF `hero_<slug>` de la page boutique). Affiché seulement si la pièce a des photos.
- Badge **« ✏ Ton projet »** (déjà tutoiement).
- **Phrase IA** italique (effet machine à écrire) + guillemets : ex. « Pour un salon, je te propose des luminaires à ampoule entourée… ». Tutoiement OK.
- **Chip-question** inline (`data-inline-question`) : la prochaine question non répondue avec ses pills cliquables (ex. « Où vas-tu installer ton luminaire ? » → Au plafond / Au mur / Sur prise 230V / Je ne sais pas). Clic = enregistre + ouvre la modale à la question suivante.
- Lien **« Préciser ou modifier mon projet »** → ouvre la modale en édition (S3).
- **Grille sélection** (`data-mes-creations-selection-grid`) : remplie par JS avec les **clones** des cartes produit qui matchent le projet (source = la grille basse « Toutes mes créations ») + une **carte sur-mesure** « Créons ensemble / En parler à Robin » (orange) en dernière cellule. Slider avec flèches + dots si ça déborde.

### Bloc 3 — Séparateur `.mes-creations-section-divider`
Petit filet centré entre « Ma sélection » et le catalogue.

### Bloc 4 — « Toutes mes créations » `.mes-creations-catalogue`
- Header : titre **« TOUTES MES CRÉATIONS »** + sous-titre « Le catalogue complet, classé par type de luminaire ».
- **Pills catégorie** (`.mes-creations-pills`) : Tous · Suspensions · Lampadaires · Lampes à poser · Appliques et plafonniers · Accessoires. Filtrage **100 % client-side** (toutes les cartes sont déjà dans le DOM, on toggle des classes ; URL mise à jour via `pushState` ; `?product_cat=slug` réappliqué au reload).
- **Grille** `.product-grid #sapi-product-grid` : **TOUS les produits, sans pagination** (`posts_per_page => -1`). Chaque carte `.product-card-cinetique` = photo ambiance ACF (+ hover galerie), nom formaté (prénom bold caps + surnom Square Peg), catégorie au singulier, prix « À partir de » si variable, CTA « Découvrir ⇾ ». Riche jeu de `data-*` pour le filtrage (catégories, prix, essence, taille, format, ampoule…).
- **5 cartes « réassurance » texte** insérées **aléatoirement** dans la grille (`wp_rand`) : 100% artisanal / Pièces uniques / Bois PEFC / Service client / Fabriqué avec amour. → ⚠️ **VOUVOIEMENT** marqué (« Vous recevez… », « Votre intérieur… »). Lien « En savoir plus » → /lumiere-dartisan/.
- Bloc caché `.why-sapi-recap` (affiché seulement avec filtres actifs) — vouvoiement aussi.
- Empty-state « Aucun modèle… » + CTA sur-mesure.

### Bloc 5 — Outro `.shop-outro`
« **Vous** ne trouvez pas **votre** bonheur ? / **Dites** à Robin… » + CTA « Découvrir le sur mesure ». → ⚠️ **VOUVOIEMENT**.

---

## 2. Constats transverses (matière pour la refonte)

1. **Dette de vouvoiement** sur toute la moitié basse : titre room-picker, 5 cartes réassurance, recap caché, outro. Incohérent avec le tutoiement du reste du site. À reprendre dans la refonte.
2. **Carte « Conseil de Robin » = ancien langage** (badge texte + cadre pointillé, ni pill V1 ni grain). C'est le 1er point d'harmonisation (Phase 3).
3. **Hero pauvre** : H1 seul sur un croquis. Aucune orientation, aucune promesse, pas de pont vers le Conseiller. Le plus gros levier visuel de la page.
4. **Deux logiques superposées** : le « Conseiller » (sélection guidée par projet) **+** le catalogue à pills. Le visiteur peut soit se laisser guider, soit fouiller. La refonte doit clarifier la hiérarchie entre les deux (qui domine ? comment on passe de l'un à l'autre ?).
5. **Carte réassurance aléatoire** dans la grille : casse le rythme visuel et le ton (blocs très textuels au milieu de photos). À repenser (bande dédiée ? retirer ? réécrire en tutoiement ?).
6. **Pas de pagination** (toutes les fiches d'un coup) : OK tant que le catalogue est petit, à garder en tête si ça grossit.
7. **Naming legacy** : classes `.product-card-cinetique`, en-tête « SAPI CINÉTIQUE », version 9.5.1 — cosmétique, mais signe que ce template n'a pas été repris dans la refonte home.
8. **Mobile non capturé en image** (le viewport rendu ne reflowe pas via l'outil) → à revérifier en device avant de figer les mockups.

---

## 3. Câblage à NE PAS casser (hooks JS / data-*)

Scripts : `sapi-cards-conseiller.js`, `sapi-project.js`, `shop.js`, `sapi-photo-swap.js`, `product-name-formatter.js`. Conserver :
- **Zone & cartes Robin** : `data-conseiller-zone`, `data-mes-creations-selection`, `data-conseiller-card="conseil|mon-projet"`, l'attribut `hidden` (toggle d'état).
- **Room-picker** (carte conseil) : `data-room-picker`, `.room-card[data-piece][data-piece-label]`, `data-room-picker-freetext`.
- **Mon projet** : `data-piece-photos`, `data-mon-projet-photo`, `data-mon-projet-badge`/`-badge-text`, `data-mon-projet-phrase`/`-content`, `data-inline-question`, `data-mon-projet-edit` (+ `data-action="open-modal" data-modal-state="s3"`).
- **Sélection** : `data-mes-creations-selection-grid`, `data-mes-creations-selection-nav`, `data-mes-creations-surmesure-template`.
- **Catalogue** : `data-mes-creations-pills` + boutons `data-cat`, `#mes-creations-catalogue`, `#shop-products`, `#sapi-product-grid`.
- **Cartes produit** : `.product-card-cinetique` + tous les `data-id/categories/name/price/wood/size/format-luminaire/type-ampoule/size-variations`, `data-piece-swap*` (swap photo par pièce).

---

## 4. Questions ouvertes pour ton brief (pistes à trancher)

- **Hero** : on enrichit (sous-titre + accroche + pont vers le Conseiller) ou on garde le croquis minimal ?
- **Hiérarchie Conseiller vs catalogue** : la sélection guidée passe-t-elle devant (héros + room-picker en 1er) ou le catalogue reste-t-il roi ?
- **Carte « Conseil de Robin »** : on l'aligne en pill V1 + grain (Phase 3) — et est-ce qu'on revoit aussi sa place / son poids ?
- **Cartes réassurance** dans la grille : on garde / on déplace en bande dédiée / on supprime ? (et passage tutoiement).
- **Outro sur-mesure** : conservé tel quel, ou fondu dans la carte sur-mesure de la sélection ?
- **Mobile** : priorités d'affichage (la sélection guidée tient-elle en haut sur petit écran ?).

→ Quand tu m'envoies tes idées, je les croise avec cet audit et je pars sur une **exploration mockup** (comme pour la home et la modale).

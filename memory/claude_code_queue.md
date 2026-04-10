# Tasks — Coordination Cowork ↔ Claude Code

## 📋 À faire

## [TÂCHE] Pages catégories + mes-créations — Refonte grille produits avec photos d'ambiance
**Date :** 2026-04-10
**Priorité :** haute
**Branche :** test-theme-sapi-maison
**Fichiers :** `woocommerce/taxonomy-product_cat.php` · `inc/template-robin-bandeau-v2.php` · `style.css`
**Mockup HTML de référence :** `../Atelier Sapi Claude Cowork/mockup-categorie.html` ← **lire en premier**

---

### Contexte

Actuellement la grille produit affiche les photos studio (fond blanc) en 4 colonnes. Objectif : remplacer par une grille 2 colonnes avec les photos d'ambiance issues du repeater ACF, et redesigner le bandeau Robin Conseiller pour le rendre plus visible. Le mockup HTML contient le design exact attendu.

---

### 1. PHOTOS D'AMBIANCE — Logique de récupération (ACF)

Dans toutes les cards produit, remplacer `get_the_post_thumbnail()` par cette logique :

```php
// Chercher la 1ère photo de type "ambiance" dans le repeater ACF
$ambiance_url = null;
$galerie = get_field('galerie_produit', $product_id);
if ($galerie) {
    foreach ($galerie as $row) {
        if ($row['type_photo'] === 'ambiance' && !empty($row['image'])) {
            $ambiance_url = is_array($row['image'])
                ? $row['image']['sizes']['large']
                : wp_get_attachment_image_url($row['image'], 'large');
            break;
        }
    }
}
// Fallback : image featured WooCommerce
if (!$ambiance_url) {
    $ambiance_url = get_the_post_thumbnail_url($product_id, 'large');
}
```

Appliquer cette logique partout où une image produit est affichée dans la grille (coup de cœur inclus).

---

### 2. COUP DE CŒUR — Photo ambiance + overlay texte

Le bloc "coup de cœur" existant passe en pleine largeur avec photo d'ambiance et overlay texte (dégradé gauche→droite). Structure HTML cible :

```html
<div class="featured-wrap">
  <div class="featured-photo">
    <img src="[photo ambiance]" alt="[nom produit]"/>
    <div class="featured-overlay">
      <div class="featured-label">✦ Coup de cœur de l'atelier</div>
      <span class="featured-name-first">[PRÉNOM]</span>
      <span class="featured-name-sur">[Le Surnom]</span>
      <p class="featured-desc">[description courte WooCommerce]</p>
      <div class="featured-footer">
        <div class="featured-price">À partir de <strong>[prix] €</strong></div>
        <a href="[permalink]" class="btn-white">Découvrir →</a>
      </div>
    </div>
  </div>
</div>
```

CSS clé pour le `.featured-overlay` : `background: linear-gradient(to right, rgba(35,28,22,0.75) 0%, rgba(35,28,22,0.35) 50%, transparent 75%)`. Hauteur fixe `540px`, photo `object-fit: cover`. Voir le mockup pour le détail exact.

---

### 3. GRILLE 2 COLONNES — Remplace la grille 4 colonnes actuelle

Remplacer la boucle WooCommerce actuelle (4 colonnes) par une grille CSS 2 colonnes. Structure HTML cible par card :

```html
<div class="product-card">
  <div class="card-photo">
    <img src="[photo ambiance]" alt="[nom]" loading="lazy"/>
    <!-- Badge visible uniquement si ce produit est dans la sélection Robin -->
    <?php if ($is_robin_selection): ?>
      <div class="badge-selection">Ma sélection</div>
    <?php endif; ?>
    <div class="card-hover-cta">
      Découvrir
      <svg><!-- flèche droite --></svg>
    </div>
  </div>
  <div class="card-info">
    <span class="p-firstname">[PRÉNOM EN MAJUSCULES]</span>
    <span class="p-surname">[Le Surnom en Square Peg]</span>
    <div class="card-price">À partir de <strong>[prix] €</strong></div>
  </div>
</div>
```

CSS clé :
- `.product-grid` : `display: grid; grid-template-columns: 1fr 1fr; gap: 3px; padding: 3px 60px 0;`
- `.card-photo` : `aspect-ratio: 3 / 4; overflow: hidden; position: relative;`
- `.card-hover-cta` : `opacity: 0; transform: translateY(5px); transition: opacity 0.2s, transform 0.2s;` → `.product-card:hover .card-hover-cta { opacity: 1; transform: translateY(0); }`
- `.p-surname` : `font-family: 'Square Peg', cursive; font-size: 26px; display: block;`

**Badge "Ma sélection" :** visible si l'ID du produit est dans la liste retournée par le pipeline Robin Conseiller (même logique que le filtre `/nos-creations/?robin_selection=1` existant). Récupérer les IDs via la session/cookie Robin déjà en place.

---

### 4. BANDEAU ROBIN CONSEILLER — Redesign visuel

Modifier `inc/template-robin-bandeau-v2.php` (mode repos uniquement) :

**Changements visuels :**
- Fond : `background: var(--color-wood)` (#937D68) au lieu du fond crème actuel
- Texte : blanc
- Layout : 3 zones flex — gauche (picto + copy) · centre (3 étapes visuelles) · droite (CTA)
- CTA : bouton blanc `background: white; color: var(--color-wood-dark);` avec texte "Me guider →"

**Structure HTML cible (mode repos) :**
```html
<div class="robin-bandeau-redesign">
  <div class="robin-left">
    <div class="robin-picto"><!-- icône SVG suspension --></div>
    <div class="robin-copy">
      <h3>Pas sûr de votre choix ? Robin vous guide.</h3>
      <p>3 minutes pour trouver le luminaire idéal pour votre espace.</p>
    </div>
  </div>
  <div class="robin-steps">
    <div class="robin-step"><span class="robin-step-num">1</span><span>Votre pièce</span></div>
    <div class="robin-step-sep"></div>
    <div class="robin-step"><span class="robin-step-num">2</span><span>Votre style</span></div>
    <div class="robin-step-sep"></div>
    <div class="robin-step"><span class="robin-step-num">3</span><span>Votre reco</span></div>
  </div>
  <button class="robin-cta-btn js-open-robin">Me guider →</button>
</div>
```

Le clic sur `js-open-robin` doit déclencher le même comportement que le badge bois actuel (ouverture modale Robin Conseiller). Conserver la classe JS existante qui gère ça.

Sur mobile : flex-direction column, étapes masquées, CTA pleine largeur.

---

### 5. PAGE MES-CRÉATIONS — Même traitement

Appliquer exactement la même structure (bandeau Robin redesigné + coup de cœur + grille 2 colonnes) à la page `mes-créations` / `nos-créations`. Les fichiers concernés sont probablement `page-nos-creations.php` ou le template de la page. Si le coup de cœur n'existe pas sur cette page, l'omettre (commencer directement par la grille).

---

### Mobile

- Grille 2 colonnes → 1 colonne sous 768px (gap 3px, pas de padding latéral)
- `.featured-photo` → hauteur `380px` sur mobile
- Bandeau Robin → colonne, étapes masquées, CTA pleine largeur (déjà spécifié)
- `.card-hover-cta` → toujours visible sur mobile (pas de hover) : `opacity: 1; transform: none;`

---

### Critères de succès
- [ ] Les photos d'ambiance ACF s'affichent dans la grille (pas les photos studio)
- [ ] Fallback vers image WooCommerce si pas de photo ambiance dans le repeater
- [ ] Coup de cœur pleine largeur avec overlay texte et photo ambiance
- [ ] Grille 2 colonnes, ratio 3:4, hover CTA fonctionnel
- [ ] Badge "✦ Ma sélection" visible sur les produits recommandés par Robin Conseiller
- [ ] Bandeau Robin sur fond bois, CTA blanc, déclenche bien la modale
- [ ] Page mes-créations identique aux pages catégories
- [ ] Responsive mobile correct sur toutes les sections

---

## [TÂCHE] Page sur mesure V2 — Refonte complète avec onglets Particuliers / Professionnels
**Date :** 2026-04-09
**Priorité :** haute
**Fichier :** `page-sur-mesure.php` + `style.css` + `functions.php`
**Branche :** test-theme-sapi-maison

---

### Vision générale

Refonte complète de la page `/sur-mesure/`. Deux onglets **Particuliers** / **Professionnels** dès le hero switchent tout le contenu de la page via JS (pas de rechargement). Les onglets ne sont pas sticky.

---

### 1. HERO + ONGLETS

Remplacer le hero actuel par un hero avec deux onglets visibles. Deux fonds superposés en `position: absolute` avec transition `opacity: 0.5s ease` au switch.

**Photos de fond :**
- Particuliers : `https://atelier-sapi.fr/wp-content/uploads/2026/04/3-2.webp`
- Professionnels : `https://atelier-sapi.fr/wp-content/uploads/2025/07/Circle-salle-Vertical.jpg`

**Accroche (h1) :**
- Particuliers : *"Créons votre luminaire sur mesure"*
- Professionnels : *"Des luminaires à l'image de votre espace"*

**Sous-texte (1 phrase, `<p>`) :**
- Particuliers : *"Une idée, un espace, une envie — je conçois et fabrique votre pièce unique."*
- Professionnels : *"Restaurants, hôtels, boutiques : des créations artisanales adaptées à votre identité."*

**CTA :** bouton "Démarrer mon projet" → `#surmesure-form` (identique pour les deux onglets)

**Onglets :** deux boutons `<button data-tab="particulier">Particuliers</button>` et `<button data-tab="pro">Professionnels</button>`. L'onglet actif a une classe `is-active`.

---

### 2. SUPPRIMER

Supprimer entièrement la section `.surmesure-intro` ("Votre luminaire, votre histoire" — texte + photo Robin).

---

### 3. PROCESS (3 étapes)

Garder la section `.surmesure-process` mais avec deux versions du contenu selon l'onglet. Textes raccourcis à **1 phrase** par étape. Implémenter avec deux blocs `[data-tab-content]` (l'un masqué).

| Étape | Particuliers | Professionnels |
|-------|-------------|----------------|
| 01 | **Échangeons** — Décrivez votre projet, je vous conseille. | **Brief & devis** — Votre espace, vos contraintes, votre budget. |
| 02 | **Concevons** — Je dessine, on ajuste ensemble. | **Conception** — Un design dans votre identité, ajusté avec vous. |
| 03 | **Fabriquons** — Livré chez vous, prêt à poser. | **Fabrication** — Commandes multiples possibles, facturation pro. |

---

### 4. RÉALISATIONS

Garder le slider existant mais afficher **deux sliders** (un par onglet), filtrés via le champ ACF `type_projet` :
- Onglet Particuliers : `WP_Query` avec `meta_key = type_projet`, `meta_value = particulier`
- Onglet Professionnels : `meta_value = pro`

Si aucun projet dans un onglet : afficher `<p>Les premières réalisations arrivent bientôt !</p>` à la place du slider.

Les deux blocs sont dans le HTML, l'un masqué selon l'onglet actif.

---

### 5. FORMULAIRE

Un seul formulaire. Champs conditionnels visibles uniquement quand l'onglet Professionnels est actif (affichés/masqués par JS, classe `is-pro-field`) :
- `<input type="text" name="etablissement" placeholder="Type d'établissement (restaurant, hôtel, boutique…)">`
- `<input type="text" name="nb_luminaires" placeholder="Nombre de luminaires envisagés">`

Ces champs ne sont pas `required` (optionnels côté validation).

---

### 6. MÉCANIQUE JS

L'état actif est stocké dans une variable JS. Au clic sur un onglet :
1. Mettre à jour la classe `is-active` sur les boutons onglets
2. Switcher le fond du hero (opacity sur les deux divs superposées)
3. Afficher/masquer les blocs `[data-tab-content="particulier"]` et `[data-tab-content="pro"]`
4. Afficher/masquer les champs `.is-pro-field` dans le formulaire
5. Onglet par défaut au chargement : `particulier`

---

### Critères de succès
- [ ] Les deux onglets switchent tout le contenu sans rechargement
- [ ] Le fond du hero change avec un effet fondu (0.5s)
- [ ] La section "Votre luminaire, votre histoire" a disparu
- [ ] Les étapes process sont différentes selon l'onglet
- [ ] Les réalisations sont filtrées par type (Pro / Particulier)
- [ ] Les champs pro apparaissent uniquement dans l'onglet Professionnels
- [ ] Fonctionne correctement sur mobile

## ✅ Terminées

- Page sur mesure — refonte complète section réalisations (9 avril 2026, déployé en prod) :
  - Fix scroll modale (overflow: hidden + grid-template-rows contrainte)
  - Champ ACF "Sous-titre" + "Type de projet" (Pro/Particulier) avec button group
  - Cards redessinées : sous-titre sous le titre, CTA "Découvrir le projet →", suppression texte tronqué et témoignage
  - Slider horizontal reprenant le pattern steps-slider existant (nav < 01/06 > à côté du titre, dots, scroll-snap center)
  - Card active centrée à l'écran, cards 480px, photos carrées (1:1)
  - Notice admin "6 projets max affichés" + colonne "Type" dans la liste
  - Commits `5205981`→`678a81e`, branche `test-theme-sapi-maison`, mergé dans master (`0221ed8`)

*(purgé le 8 avril 2026)*

- Nettoyage fallbacks anciens champs ACF photos (`bandeau`, `ambiance_1/2/3`, `detail_1/2`, `tailles`) — +4/−45 lignes. Branche `test-theme-sapi-maison`.
- Bandeau dual-mode réassurance + Robin Conseiller — commits `45104da`→`c82a3c4`, branche `test-theme-sapi-maison`. Mode repos : 4 items statiques + badge wood "Démarrer mon projet". Mode projet : badge wood "Mon projet" + chips. Mobile : 2 items aléatoires + badge sur ligne dédiée.

- Robin Conseiller — 5 variations aléatoires pour le texte d'accueil de la première fiche. Commit `4495546`.
- Robin Conseiller — Inversion label/dim question Taille : « Petite pièce / Pièce standard / Grande pièce » + sous-titres « intime / confortable / spacieuse ». Commit `4e7b0b9`.
- Robin Conseiller — Labels Taille reformulés + 4e choix « Je ne sais pas » (slug `ne-sais-pas`, no filtre taille, Sortie déclenchée). Commit `43cf475`, branche `test-theme-sapi-maison`.

*(purgé le 3 avril 2026 — tâches du jour validées)*

- Optimisation SEO homepage — titres H1/H2/H3 homepage réécrits + ajustements Robin. 3 commits (`0e19aa7`, `5a3687b`, `b33e0be`), mergé dans master (`78d54ca`). **Déployé en prod** — Robin doit lancer le workflow GitHub Actions.
  - H1 : « Luminaires en bois · Atelier Sâpi »
  - H2 : « Fabriqués à la main, à la commande, dans mon atelier Lyonnais ! »
  - H2 storytelling : « Des créations imaginées et fabriquées avec passion »
  - H3 process : « Mon processus artisanal » (guillemets retirés)

- Réordonnancement page produit + intégration photo client — commit `e04e55d`
- Lightbox galerie produit — redesign complet (overlay flottant, swipe mobile) — commit `3e22522`
- Remplacement ACF "Phrase d'accroche" → description courte WooCommerce — commit `5a05513`, déployé en prod
- Refonte section "L'histoire de" → "Détails" + colonne droite ACF Descriptif — commit `b3924fe`
- Fiche produit accessoire — pill Robin masquée, fiche technique masquée, ratio Détails 2fr/1fr — commit `d2386a1`
- Mise à jour textes pages catégories (intro hero + éditorial) depuis fichier de référence — commit `b6c2581`, déployé en prod

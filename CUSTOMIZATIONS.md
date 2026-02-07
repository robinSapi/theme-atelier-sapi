# Documentation des Customisations - Thème Sâpi Maison

**Date de création:** 2025-02-04
**Contexte:** Migration du travail de Jérôme (Elementor) vers un thème custom

---

## 🎯 Principes d'Exigence

**Philosophie de développement :**
- **Qualité** : Code propre, maintenable, sans compromis. CSS-only quand possible, pas de hacks HTML inutiles.
- **Robustesse** : Solutions pérennes qui ne cassent pas aux mises à jour. Pas de workarounds fragiles.
- **Conversions** : Chaque élément UX doit servir la conversion. Design au service du business.
- **Différenciation** : Atelier Sâpi est une marque artisanale unique. Le site doit refléter cette singularité.

**Règles strictes :**
- Pas de suggestions automatiques de cache (il n'y en a pas sur Local by Flywheel)
- Pas de solutions "quick fix" qui complexifient le code
- Toujours comprendre les interdépendances CSS avant de modifier
- Tester visuellement sur testlumineux avant de valider

---

## 🚀 Workflow de Déploiement

```
Local (dossier projet) → GitHub → O2switch (hébergeur)
```

**Repository GitHub:** `https://github.com/robinSapi/testLumineux-atelier-sapi`

**Branche de travail:** `test-theme-sapi-maison` (NE PAS push sur main/master)

**Environnements:**
- **Production:** `https://atelier-sapi.fr` (site live)
- **Pré-production:** `https://testlumineux.atelier-sapi.fr` (tests)
- **Local:** `/Users/samuel/Local/atelier-sapi` (développement)

**Process:**
1. Modifications en local
2. `git push origin test-theme-sapi-maison`
3. Déploiement automatique sur testlumineux via O2switch
4. Test sur testlumineux
5. Si OK → déploiement en production

**Important:** Ne jamais tester en local avec une base de données - tout test se fait via push sur testlumineux.

---

## ⚠️ Éléments Non-Standards (mais qui fonctionnent)

### 1. Template WooCommerce `single-product.php`

**Statut actuel (2026-02-04):** Template maintenant conforme aux standards WooCommerce.

**Structure actuelle:**
```php
<?php the_post(); ?>
<?php global $product; ?>
<?php
if (!$product || !is_a($product, 'WC_Product')) {
  $product = wc_get_product(get_the_ID());
}
?>
<?php do_action('woocommerce_before_single_product'); ?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>
  <!-- contenu custom (sections hero, details, FAQ, etc.) -->
</div>
<?php do_action('woocommerce_after_single_product'); ?>
```

**Historique:**
- Version Jérôme (Elementor) : sans standards WooCommerce
- 2025-02-04 : Tentative de standardisation → a cassé le panier → revert
- 2026-02-04 : Nouvelle tentative avec `global $product` + `wc_product_class()` → **à tester**

**Action si problème:** Si l'ajout au panier cesse de fonctionner, comparer avec la version production.

---

### 2. URLs d'images - Environnement de test

**Statut:** ✅ CORRIGÉ (2026-02-04)

**Fichiers concernés (47 URLs corrigées):**
- `front-page.php` (9 URLs)
- `page-conseils-eclaires.php` (5 URLs)
- `page-lumiere-dartisan.php` (5 URLs)
- `page-contact.php` (1 URL)
- `woocommerce/archive-product.php` (5 URLs)
- `woocommerce/taxonomy-product_cat.php` (22 URLs)

**Problème initial:** Les images utilisaient des URLs absolues vers `https://atelier-sapi.fr/` (production) au lieu de `https://www.testlumineux.atelier-sapi.fr/` (test), ce qui empêchait l'affichage des images sur Chrome.

**Correction appliquée:**
```php
// Avant
'image' => 'https://atelier-sapi.fr/wp-content/uploads/2025/10/Bandeau.jpg',

// Après
'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/10/Bandeau.jpg',
```

**⚠️ ATTENTION pour le déploiement en production:**
Avant de déployer sur `atelier-sapi.fr`, il faudra :
1. Soit remettre les URLs vers `atelier-sapi.fr`
2. Soit utiliser des URLs relatives (`/wp-content/uploads/...`)
3. Soit utiliser `wp_get_attachment_url()` avec des IDs d'images WordPress

**Action future:** Idéalement, refactoriser pour utiliser des images depuis la médiathèque WordPress avec `wp_get_attachment_url()` au lieu d'URLs hardcodées.

---

### 3. Redirections des pages statiques vers catégories WooCommerce

**Fichier:** `functions.php` (lignes 224-250)

**Contexte:** Jérôme avait créé des pages WordPress statiques pour les catégories (avec Elementor) au lieu d'utiliser les taxonomies WooCommerce natives.

**Solution:** Redirections 301 automatiques
```php
'nos-lampadaires' => 'lampadaire'  // page statique → catégorie WooCommerce
```

**⚠️ ATTENTION:** Les slugs des catégories sont au **singulier** (lampadaire, suspension, etc.), pas pluriel. Ne pas modifier sans vérifier la base de données.

**Action future:** Ces pages statiques peuvent être supprimées si les redirections fonctionnent bien depuis plusieurs mois.

---

### 4. Meta SKU et catégories supprimées

**Fichier:** `functions.php` (ligne 253)

```php
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
```

**Raison:** Le client ne veut pas afficher "UGS: XXX" et les catégories sur les pages produit.

**Action future:** Si besoin de réactiver, commenter cette ligne.

---

## ✅ Modifications Standards (bonnes pratiques)

### 1. Support WooCommerce Gallery

**Fichier:** `functions.php` (lignes 18-20)

```php
add_theme_support('wc-product-gallery-zoom');
add_theme_support('wc-product-gallery-lightbox');
add_theme_support('wc-product-gallery-slider');
```

**Standard:** ✅ Recommandé par WooCommerce

---

### 2. Fragments de panier

**Fichier:** `functions.php` (lignes 256-263)

**Raison:** Met à jour le compteur du panier dans le header après AJAX.

**Standard:** ✅ Méthode officielle WooCommerce

---

### 3. Template hiérarchie

**Fichiers créés:**
- `woocommerce/single-product.php` (remplace le template WooCommerce)
- `woocommerce/taxonomy-product_cat.php` (remplace les archives catégories)
- `woocommerce/content-product.php` (remplace la boucle produits)

**Standard:** ✅ Méthode officielle WordPress/WooCommerce pour customiser les templates

---

## 🎨 CSS Design System - État Actuel

**Fichier:** `style.css` (~1940 lignes)

**Statut:** ✅ REFONTE TERMINÉE (2026-02-04)

### Système de variables unifié

```css
:root {
  /* Colors - Neutrals */
  --color-white: #FFFFFF;
  --color-cream: #FEFDFB;
  --color-warm: #FBF6EA;
  --color-gray-light: #F1F1F1;
  --color-gray-mid: #8A8A8A;
  --color-gray: #585858;
  --color-dark: #323232;
  --color-black: #000000;

  /* Colors - Brand */
  --color-wood: #937D68;
  --color-orange: #E67E22;
  --color-green: #018501;
  --color-green-hover: #026B02;

  /* Colors - Interactive */
  --color-link: #00589A;
  --color-link-hover: #00365F;
  --color-error: #C50000;
  --color-error-hover: #570000;

  /* Typography */
  --font-display: 'Square Peg', cursive;
  --font-body: 'Montserrat', sans-serif;

  /* Spacing & Layout */
  --radius: 5px;
  --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);

  /* Easing */
  --ease-expo: cubic-bezier(0.87, 0, 0.13, 1);
  --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
}
```

### Structure du fichier CSS

| Section | Lignes | Description |
|---------|--------|-------------|
| Design Tokens | 1-52 | Variables CSS unifiées |
| Reset & Base | 54-115 | Styles de base et typographie |
| Layout - Header | 117-230 | Header et navigation |
| Components - Buttons | 175-210 | Boutons standards et outline |
| Menu Mobile | 235-420 | Burger menu et overlay |
| Pages - Shop | 422-645 | Pages boutique et produits |
| Pages - Content | 690-900 | Artisan, Conseils, Contact, Blog |
| Components - Breadcrumbs | 902-930 | Fil d'Ariane |
| CINÉTIQUE - Cursor | 932-970 | Curseur custom |
| CINÉTIQUE - Header | 972-1085 | Header architectural |
| CINÉTIQUE - Menu | 1087-1200 | Menu overlay |
| CINÉTIQUE - Bento | 1204-1593 | Grille Bento homepage |
| CINÉTIQUE - Collections | 1595-1704 | Section collections |
| CINÉTIQUE - Newsletter | 1706-1774 | Newsletter |
| CINÉTIQUE - Footer | 1776-1853 | Footer |
| CINÉTIQUE - Responsive | 1855-1940 | Media queries |

### Refonte effectuée (2026-02-04)

1. ✅ **Variables unifiées** : suppression de tous les `--sapi-*` → utilisation exclusive de `--color-*`
2. ✅ **Code mort supprimé** : ~280 lignes de styles legacy home page
3. ✅ **Sélecteurs dédupliqués** : `.cart-link`, `.cart-count`, media queries
4. ✅ **Structure réorganisée** : commentaires de section clairs
5. ✅ **Code debug supprimé** : archive-product.php nettoyé

---

## 🔧 Points d'Attention pour le Futur

### Avant une mise à jour WooCommerce majeure:

1. **Tester l'ajout au panier** sur un environnement de test
2. Si ça casse, le problème est probablement dans `single-product.php`
3. Vérifier que les hooks WooCommerce n'ont pas changé
4. Vérifier la [doc officielle des templates](https://woocommerce.com/document/template-structure/)

### Si vous engagez un autre développeur:

1. Lui faire lire ce fichier AVANT de "corriger" le code
2. Les customisations non-standards sont **volontaires** et **fonctionnelles**
3. Ne pas "corriger" selon les standards sans tests approfondis

### Si l'ajout au panier cesse de fonctionner:

1. Vérifier `single-product.php` (voir section 1 ci-dessus)
2. Désactiver tous les plugins sauf WooCommerce pour isoler
3. Comparer avec la version en production qui fonctionne
4. Vérifier les sessions PHP et cookies navigateur

---

## ✅ Problèmes Résolus

### Bug panier "Olivia" sur testlumineux

**Statut:** ✅ RÉSOLU (2026-02-04)

**Problème:** Le panier affichait toujours "Olivia La gardiena" peu importe le produit ajouté.

**Cause:** La page "Mon Panier" (ID 3554) contenait du contenu statique hardcodé par Jérôme (tableau avec Olivia) au lieu du bloc WooCommerce Cart dynamique.

**Solution appliquée:**
1. Éditer la page "Mon Panier" dans l'admin WordPress
2. Supprimer tout le contenu statique (bloc Classique avec tableau Olivia)
3. Ajouter le bloc **WooCommerce Cart** (pas le shortcode en texte)
4. Enregistrer

**Leçon apprise:** Les pages WooCommerce (Panier, Commande, Mon compte) doivent utiliser les **blocs WooCommerce natifs** ou le shortcode dans un **bloc Shortcode dédié**, pas du texte brut dans un paragraphe.

**Pages WooCommerce à vérifier si problème similaire:**
- Panier : doit contenir bloc "Cart" ou `[woocommerce_cart]`
- Validation de commande : bloc "Checkout" ou `[woocommerce_checkout]`
- Mon compte : bloc "My Account" ou `[woocommerce_my_account]`

---

## 📝 Historique des Modifications

**2026-02-05 (Design CINÉTIQUE - Fiche produit + Panier/Checkout):**
- ✅ **FICHE PRODUIT - Hero style HP** :
  - Ajout numéro de section "01" + label catégorie
  - Classes `.product-hero-cinetique`, `.product-summary-header`
  - Titre plus grand (clamp), cohérent avec la HP

- ✅ **FICHE PRODUIT - Détails 2 colonnes** :
  - Grille `.product-details-grid` (1.5fr + 1fr)
  - Colonne gauche : descriptif ACF ou content
  - Colonne droite : highlights (découpe laser, bois certifié, montage, ampoule)
  - Section "02" avec numéro

- ✅ **FICHE PRODUIT - FAQ avec chevrons** :
  - Classes `.product-faq-cinetique`, `.faq-item`
  - Chevrons CSS-only (::before/::after rotations)
  - Section "03" avec numéro
  - Design condensé avec bordures arrondies

- ✅ **FICHE PRODUIT - Sticky bar premium** :
  - Background gradient cream → white
  - Bordure top wood 2px
  - Shadow améliorée
  - Typographie uppercase + letter-spacing

- ✅ **PANIER - Design CINÉTIQUE** :
  - Hook `woocommerce_before_cart` → hero avec section "01"
  - Hook `woocommerce_after_cart` → bloc rassurance (fabrication, livraison, retours)
  - CSS : `.cart-page-cinetique`, `.cart-hero`, `.cart-reassurance`
  - Table stylée avec thumbnails arrondies

- ✅ **CHECKOUT - Design CINÉTIQUE** :
  - Hook `woocommerce_before_checkout_form` → hero avec section "01"
  - Layout 2 colonnes (billing/shipping côte à côte)
  - Formulaires avec inputs arrondis, focus wood
  - Bouton "Commander" premium (uppercase, wood)

**2026-02-05 (Fix sticky add-to-cart produits variables):**
- ✅ **STICKY BAR VARIABLE PRODUCTS** : Gestion correcte des produits avec variations
  - Fichiers : `single-product.php`, `style.css`
  - Produits simples : bouton direct "Ajouter au panier" (AJAX)
  - Produits variables : bouton "Choisir les options" → scroll vers le formulaire
  - Une fois variation sélectionnée :
    - Le prix sticky se met à jour avec le prix de la variation
    - Le bouton devient "Ajouter au panier" et soumet le formulaire principal
  - Reset automatique si la variation est désélectionnée
  - CSS : états visuels `.sticky-scroll-to-form` et `.variation-selected`

**2026-02-05 (UX Améliorations page Nos Créations):**
- ✅ **HERO VISUEL** : Refonte hero avec grille texte + collage d'images
  - Fichiers : `archive-product.php`, `style.css`
  - Layout grid 2 colonnes : contenu à gauche, collage produits à droite
  - Collage dynamique : 3 produits "featured" (ou récents en fallback)
    - `.collage-main` : grande image (span 2 rows)
    - `.collage-accent-1` et `.collage-accent-2` : petites images
  - Hover zoom sur les images du collage
  - Responsive : colonne unique sur mobile, une seule image visible

- ✅ **COMPTEURS FILTRES DYNAMIQUES** : Affichage du nombre de produits par catégorie
  - Format : `Suspension (9)` - chiffre entre parenthèses
  - Utilise `$cat->count` pour les catégories
  - Utilise `$all_products->found_posts` pour "Tout"
  - Classe `.filter-count` avec opacité réduite

- ✅ **ÉTAT ACTIF FILTRES AMÉLIORÉ** : Design plus visible pour le filtre sélectionné
  - Fond `--color-wood` avec box-shadow
  - `transform: scale(1.02)` pour légère mise en avant
  - Bordure 2px (au lieu de 1px) pour plus de présence
  - Hover sur actif → fond `--color-dark`

- ✅ **CTAs STRATÉGIQUES** : Ajout de 2 boutons d'appel à l'action
  - **CTA Hero** : "Découvrir la collection" avec flèche ↓ (ancre vers #shop-products)
  - **CTA Outro** : "Contactez-nous" avec message personnalisation sur-mesure
  - Smooth scroll vers la section produits

- ✅ **TEXTE HERO CONDENSÉ** : Reformulation plus concise
  - Avant : "Chaque pièce est unique, découpée au laser et assemblée à la main dans notre atelier lyonnais."
  - Après : "Luminaires uniques, découpés au laser et assemblés à la main dans notre atelier lyonnais."

**2026-02-05 (Toggle variation selectors):**
- ✅ **VARIATION SELECTORS** : Design toggle-style pour Matériau et Taille sur pages produit
  - Fichiers : `style.css` (lignes ~4276+), `shop.js` (variationSwatches module)
  - Container card avec fond crème (`--color-cream`) et bordure subtile
  - Toggle buttons horizontaux avec :
    - Cercle preview (initiale ou image)
    - Label texte visible
    - État hover : bordure wood + shadow
    - État selected : fond warm + bordure wood
  - JavaScript synchronisé avec WooCommerce :
    - Click ajoute `.selected` et update le select caché
    - Reset button réinitialise les sélections
  - Design cohérent avec le thème Sapi Maison (couleurs, typographie, arrondis)

**2026-02-05 (Carrousel produits + filtres client-side):**
- ✅ **CARROUSEL PRODUITS** : Page `/nos-creations/` avec carrousel horizontal (plus de pagination)
  - Fichiers : `archive-product.php`, `content-product.php`, `shop.js`, `style.css`
  - Auto-scroll toutes les 4 secondes avec pause au hover
  - Navigation : flèches `<` `>` + dots/points indicateurs
  - Support swipe tactile
  - Responsive : 4 → 3 → 2 → 1 slides selon largeur écran

- ✅ **FILTRES CLIENT-SIDE** : Boutons filtres par catégorie (Tout, Suspensions, Lampadaires, etc.)
  - Filtrage JavaScript sans rechargement de page
  - Attribut `data-categories` sur chaque slide avec slugs des catégories
  - Classe `.is-filtered-out` pour masquer les produits

- ✅ **CONTEXTE GLOBAL PHP** : Variable `$sapi_carousel_context` pour passer des données entre templates
  ```php
  // Dans archive-product.php
  $sapi_carousel_context = [
    'is_carousel' => true,
    'categories' => implode(' ', $cat_slugs), // "suspension lampadaire"
  ];
  wc_get_template_part('content', 'product');
  $sapi_carousel_context = null;
  ```
  - `content-product.php` lit cette variable globale pour ajouter les classes/attributs appropriés

- ✅ **FIX SPÉCIFICITÉ CSS** : Règle `.is-filtered-out` devait battre `ul.products li.product { display: block !important }`
  - Problème : spécificité 0,0,2,2 vs 0,0,2,0
  - Solution : sélecteurs plus spécifiques `ul.products li.product.is-filtered-out` (0,0,3,2)
  - **Leçon importante** : En CSS, même avec `!important` des deux côtés, c'est la spécificité qui gagne !
  ```css
  /* style.css ligne ~1347 - DOIT battre ul.products li.product */
  ul.products li.product.is-filtered-out,
  .products-carousel-track > li.product.is-filtered-out {
    display: none !important;
    visibility: hidden !important;
    width: 0 !important;
    /* ... autres props de masquage agressif */
  }
  ```

- ✅ **HOVER IMAGE AMBIANCE** : Au survol d'une vignette produit, la première image de galerie (lifestyle) apparaît
  - PHP : `$hover_image_url = wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail')`
  - CSS : `.product-media.has-hover-image` avec transition opacity

**2026-02-05 (Process bar hover images):**
- ✅ **PROCESS BAR HOVER** : Au hover sur chaque étape, le texte disparaît et une photo apparaît
  - Ajout HTML : `<div class="step-image">` dans chaque `.process-step`
  - CSS : opacity transition avec `--ease-expo`
  - `.process-step` : `height: 120px` + `width: 140px` + `overflow: hidden`
  - `.step-image` : `inset: 0` (pas width/height 100% car ne fonctionne pas avec min-height)
  - Images utilisées :
    - 01 Dessin → `ordi_sapi2.jpg` (design sur ordinateur)
    - 02 Découpe laser → `detail_sapi.jpg` (détail précision)
    - 03 Assemblage → `Robin-Sapi-A.jpg` (Robin à l'atelier)
    - 04 Finitions → `P_SLM_XL_det5.jpg` (détail produit fini)
  - **Leçons** :
    - `height: 100%` sur enfant absolu ne fonctionne pas si parent a `min-height` (besoin d'un `height` explicite)
    - Utiliser `inset: 0` au lieu de `top/left + width/height: 100%` pour éviter ce problème

**2026-02-05 (chevron dropdown menu):**
- ✅ **CHEVRON CSS-ONLY** : Indicateur visuel pour "Nos créations" (menu avec sous-menu)
  - Solution : `::before` avec `position: absolute` + `right: 0`
  - `padding-right: 18px` sur le lien parent pour l'espace
  - Rotation 45deg → -135deg au hover
  - Pas de modification HTML, 100% CSS
  - **Leçon** : `::after` était déjà utilisé pour l'underline hover, donc `::before` pour le chevron

**2026-02-04 (optimisations UX homepage):**
- ✅ **HEADER** : Icône panier SVG (remplace emoji), navigation avec underline hover, badge couleur wood (#937D68)
- ✅ **HERO/BENTO** : Label "Pièce signature" avec fond orange + texte blanc, bouton CTA "Découvrir nos créations"
- ✅ **COLLECTIONS** : Labels toujours visibles (overlay permanent), format "X créations →"
- ✅ **FOOTER** : Restructuration complète - Logo "Atelier Sâpi" (Square Peg), 3 colonnes (Navigation, Contact, Social), mentions légales
- ✅ **NUMÉROTATION** : Sections 01/02/03 plus visibles (couleur wood, taille augmentée)

**2026-02-04 (refonte CSS):**
- ✅ **REFONTE CSS COMPLÈTE** : ~2220 lignes → ~1940 lignes
  - Suppression de ~280 lignes de code mort (ancien home page legacy)
  - Unification des variables : tous les `--sapi-*` → `--color-*`
  - Dédupliquer les sélecteurs (`.cart-link`, `.cart-count`, media queries)
  - Réorganisation avec commentaires de section clairs
  - Suppression du code debug dans archive-product.php

**2026-02-04 (correction images):**
- ✅ **CORRECTION IMAGES CHROME** : 47 URLs d'images corrigées de `atelier-sapi.fr` vers `testlumineux.atelier-sapi.fr`
  - Fichiers modifiés : front-page.php, page-conseils-eclaires.php, page-lumiere-dartisan.php, page-contact.php, archive-product.php, taxonomy-product_cat.php
  - Cause : les images du domaine de production ne s'affichaient pas sur Chrome en environnement de test

**2026-02-04:**
- ✅ Ajout section workflow déploiement (Local → GitHub → O2switch)
- ✅ Nouvelle tentative standardisation `single-product.php` : ajout `global $product` + wrapper `wc_product_class()`
- ✅ Ajout chargement `wc-cart-fragments` et `wc-add-to-cart-variation` dans functions.php
- ✅ Ajout filtre `woocommerce_add_to_cart_fragments` pour mise à jour compteur panier
- ✅ **BUG PANIER RÉSOLU** : page "Mon Panier" contenait du contenu statique hardcodé → remplacé par bloc WooCommerce Cart
- ✅ **NETTOYAGE ELEMENTOR COMPLÉTÉ** sur testlumineux :
  - Pages statiques supprimées : Les accessoires, Nos lampadaires, Nos suspensions, Nos appliques, Nos lampes à poser
  - Plugin Elementor supprimé (était déjà désactivé)
  - BDD nettoyée (0 métadonnées _elementor_* trouvées)
  - Note : les redirections 301 dans functions.php sont conservées pour le SEO (anciennes URLs → nouvelles catégories)

**2026-02-07 (Phase 4 - Proposal B Conversion Features):**
- ✅ **DOUBLE CTA STRATÉGIQUE** : Deux boutons d'achat pour capturer différents profils clients
  - Fichiers : `single-product.php`, `functions.php`, `style.css`
  - **CTA Principal** : "Ajouter au panier" (pour les comparateurs qui veulent continuer leurs recherches)
  - **CTA Secondaire** : "Acheter maintenant" (pour les décideurs rapides)
    - Express checkout : skip le panier, redirection directe vers /checkout/
    - Handler AJAX `sapi_ajax_buy_now()` qui vide le panier et ajoute le produit sélectionné
    - Style outline avec bordure wood, hover avec fond wood
    - Support produits simples ET produits variables (vérification variation_id)
  - Texte explicatif : "Paiement direct, sans passer par le panier"
  - JavaScript : validation de la sélection pour produits variables avant achat express

- ✅ **LIVRAISON ESTIMÉE PERSONNALISÉE** : Date de livraison dynamique au lieu d'un délai générique
  - Fichiers : `functions.php`, `single-product.php`, `style.css`
  - Fonction `sapi_get_estimated_delivery_date()` :
    - Calcul automatique : 8 jours ouvrés (5 fabrication + 3 livraison)
    - Exclusion des weekends (samedi/dimanche)
    - Format français : "12 février" (jour + mois en toutes lettres)
  - Affichage dans la réassurance : "Chez vous le **12 février**" (au lieu de "Livraison 48-72h")
  - Design : couleur verte (`--color-green`) pour feedback positif
  - **Impact conversion** : "Chez vous le 12 février" est 10× plus convaincant qu'un délai générique selon études UX

- ✅ **ACCORDÉON SPECS MOBILE** : Alternative mobile-friendly à la grille 4 colonnes des specs techniques
  - Fichiers : `single-product.php`, `style.css`
  - 4 sections repliables (balises `<details>` / `<summary>` natives) :
    - 01. Dimensions (dimensions, poids, longueur câble)
    - 02. Éclairage (culot E27, ampoule LED, variateur)
    - 03. Matériaux (peuplier PEFC, finitions, câble, pavillon)
    - 04. Installation (montage, difficulté, outils, entretien)
  - Animations fluides : chevron rotation 180°, slide-in content avec `@keyframes accordionSlide`
  - Responsive : accordéon visible uniquement sur mobile (< 768px), grille cachée
  - Desktop : grille 4 colonnes conservée, accordéon caché
  - Styles premium : fond crème, bordure wood active, box-shadow au hover

- ✅ **COMMIT** : `b91745e` - "feat(Phase 4): implement Proposal B conversion features"

**2026-02-06 (Phase 2 - Design System Premium):**
- ✅ **ENRICHISSEMENT DESIGN SYSTEM** : Ajout de couleurs et gradients premium dans variables CSS
  - Fichier : `style.css` (lignes ~59-95)
  - Nouvelles couleurs étendues :
    ```css
    --creme-papier: #FEFDFB;
    --creme-chaud: #FAF6F0;
    --ivoire-doux: #F5EDE4;
    --bois-dore: #937D68;
    --bois-profond: #6B5A4A;
    --bois-sombre: #4A3F35;
    --orange-sapi: #E35B24;
    --orange-hover: #C94D1E;
    ```
  - Gradients bois pour swatches (okoumé, peuplier, noyer, chêne, hêtre, bouleau, frêne, érable, merisier, pin)
  - Usage : variations de produits, ambiances, textures

- ✅ **CTA PRINCIPAL PREMIUM** : Refonte bouton "Ajouter au panier" selon Design System 4.1
  - Fichier : `style.css` (lignes ~7485-7590)
  - Gradient orange avec profondeur : `linear-gradient(180deg, #E35B24 0%, #D14F1C 100%)`
  - **Ombres chaudes** (pas grises !) : `rgba(227, 91, 36, 0.25)` pour cohérence brand
  - Hover : gradient plus foncé + `translateY(-2px)` + ombre plus marquée
  - Active : `translateY(0)` avec inset shadow pour effet "pressed"
  - Préfixes `-webkit-` pour compatibilité Safari

- ✅ **GALLERY OVERLAY DORÉ** : Lumière dorée subtile sur image produit principale (Design System 4.4)
  - Fichier : `style.css` (lignes ~6876-6910)
  - Pseudo-élément `::after` avec gradient diagonal :
    ```css
    background: linear-gradient(135deg, rgba(255, 248, 231, 0.15) 0%, transparent 50%);
    ```
  - `pointer-events: none` pour ne pas bloquer les interactions
  - Z-index géré : overlay `z-index: 1`, zoom icon `z-index: 2`
  - Effet premium subtil qui enrichit la perception produit sans surcharger

- ✅ **SECTION ARTISAN PREMIUM** : Refonte complète section Robin/Atelier (Design System 4.5)
  - Fichier : `style.css` (lignes ~1958-2070)
  - Card premium : fond `--creme-chaud`, border-radius 20px, bordure wood subtile
  - Grid 2 colonnes : photo circulaire 120px + contenu texte
  - Photo circulaire avec bordure wood 4px + box-shadow dorée
  - Citation italique 17px, line-height 1.7, couleur `--bois-profond`
  - Signature : font `Square Peg` (display), 28px, couleur `--bois-sombre`
  - Bouton outline "Découvrir notre histoire" avec hover wood

- ✅ **BADGES CERTIFICATIONS PREMIUM** : Amélioration visuelle des badges Garantie/CE/Lyon/PEFC
  - Fichier : `style.css` (lignes ~1897-1922)
  - Gradient crème : `linear-gradient(135deg, var(--creme-chaud) 0%, var(--ivoire-doux) 100%)`
  - Bordure wood très subtile : `rgba(147, 125, 104, 0.12)`
  - Border-radius 100px (pilule)
  - Hover : `translateY(-1px)` + box-shadow plus marquée
  - Transition fluide avec `--ease-smooth`

- ✅ **COMMIT** : `cb3e87b` - "feat(Phase 2): enrich Design System with premium styles"

**2026-02-07 (Harmonisation site - 3 Vagues Premium):**

Harmonisation complète du site pour que TOUTES les pages atteignent le niveau premium de la Homepage et de la Fiche Produit.

**VAGUE 1 - Storytelling & Trust (commit 2bd9f91):**
- ✅ **PAGE ARTISAN** (`page-lumiere-dartisan.php` + `style.css`)
  - Hero premium : gradient overlay warm (bois), clamp(56px, 10vw, 96px), min-height 60vh
  - Photo Robin circulaire : 280x280px, border 4px wood, box-shadow dorée (comme product page)
  - Hover effect : scale(1.05) avec shadow augmentée
  - Steps sections : translateY(-4px) + scale(1.02) au hover, layered shadows
  - Values cards : gradient cream background, hover lift translateY(-6px)
  - Quote finale : Square Peg italic clamp(32-48px), gradient background
  - CTA premium : orange gradient button 180deg (#E35B24→#D14F1C), warm shadows
  - Fichiers : 242 insertions, 87 deletions

**VAGUE 2 - Lead Generation (commit 9ff6945):**
- ✅ **PAGE CONSEILS ÉCLAIRÉS** (`page-conseils-eclaires.php` + `style.css`)
  - Hero : gradient overlay warm, clamp typography, min-height 60vh
  - Section numbers : 01-04 avec Design System styling (--bois-dore)
  - Typography : Square Peg pour h2 clamp(36-56px), orange h3 clamp(18-22px)
  - Images : hover translateY(-4px) + scale(1.02), shadows (0 16px 40px)
  - Text blocks : alternating gradient backgrounds (cream/white)
  - Outro : signature style italic, gradient 135deg
  - 4 section numbers ajoutés manuellement dans HTML

- ✅ **PAGE CONTACT** (`page-contact.php` + `style.css`)
  - Hero : same gradient overlay treatment
  - CTA buttons : orange gradient comme HP, padding 18px 36px
  - Hover states : translateY(-2px) avec warm shadows (rgba(227, 91, 36))
  - Button outline : 2px border, hover avec background fill
  - Form fields : focus states avec orange glow (box-shadow 0 0 0 3px)
  - Input/textarea : border 2px wood, border-radius 8px
  - Typography : labels uppercase 14px, letter-spacing 0.05em
  - Success/error messages : gradient backgrounds, 2px colored borders
  - Fichiers : 337 insertions, 41 deletions

**VAGUE 3 - Content (commit f1803e0):**
- ✅ **ARCHIVE BLOG** (`archive.php` + `style.css`)
  - Hero : gradient background clamp(56-96px), centered content
  - Blog grid : auto-fill minmax(320px, 1fr), gap 40px
  - Blog cards : 2px border wood, cream background, border-radius 16px
  - Hover : translateY(-6px), border color change, shadow increase
  - Card images : aspect-ratio 16/10, scale(1.05) hover avec 0.6s transition
  - Card meta : date + category display, wood gold color
  - Navigation : orange border buttons avec hover fill
  - No template-parts dependency (self-contained)

- ✅ **SINGLE POST** (`single.php` + `style.css`)
  - Header : gradient background, Square Peg clamp(42-72px)
  - Meta : date + category badge avec rounded styling
  - Featured image : elevated -40px overlap, box-shadow 0 16px 48px
  - Content : 800px max-width, 17px/1.8 line-height
  - Typography : clamp h2 (28-36px), orange h3 (22-28px)
  - Tags : hover background fill wood gold, border-radius 6px
  - Post navigation : gradient cards hover translateY(-4px)
  - No template-parts dependency (self-contained)
  - Fichiers : 515 insertions, 26 deletions

**RÉSULTATS HARMONISATION:**
- ✅ Toutes les pages utilisent les mêmes patterns premium
- ✅ Gradient backgrounds cohérents (135deg, warm cream tones)
- ✅ Square Peg pour display typography partout
- ✅ Clamp() pour responsive sizing uniforme
- ✅ Orange gradient buttons (#E35B24→#D14F1C) partout
- ✅ Hover effects (translateY + shadows) cohérents
- ✅ Design System colors (--bois-dore, --creme-papier, etc.)
- ✅ -webkit- prefixes pour Safari sur tous les transforms
- ✅ Total : 1094 insertions, 154 deletions sur 3 commits

**2025-02-04:**
- Création du thème custom depuis le travail Elementor de Jérôme
- Nettoyage du code debug
- Correction des slugs catégories (pluriel → singulier)
- Ajout support galerie WooCommerce
- ❌ Tentative de standardisation `single-product.php` → **échec, revert nécessaire**
- ✅ Ajout redirections pages statiques → catégories WooCommerce
- ✅ Création documentation CUSTOMIZATIONS.md
- ❌ Problème panier sur testlumineux identifié - **cause: environnement, pas code**

---

**Pour mettre à jour ce fichier:** Documentez toute modification qui s'écarte des standards WordPress/WooCommerce avec la raison et l'impact.

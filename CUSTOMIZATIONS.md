# CUSTOMIZATIONS.md — Thème Sapi Maison
## Historique, Erreurs, Solutions et Pièges à Éviter

> **LIRE CE FICHIER AVANT TOUTE MODIFICATION**
> Il contient l'historique des problèmes et ce qui NE FONCTIONNE PAS.

**Dernière mise à jour :** 2026-02-12
**Mainteneur :** Robin / Samuel

---

## 1. ÉTAT ACTUEL DU THÈME

| Fichier | Rôle | Lignes | Dernière modif | Attention |
|---------|------|--------|----------------|-----------|
| `functions.php` | Coeur du thème : assets, hooks WooCommerce, AJAX, meta boxes, SEO | ~1147 | 2026-02-11 | Complexe, beaucoup de hooks |
| `style.css` | Tous les styles (design system, composants, pages, responsive) | ~13086 | 2026-02-12 | Très lourd, variables CSS en haut |
| `front-page.php` | Homepage bento grid | ~283 | 2026-02-07 | URLs images hardcodées (testlumineux) |
| `header.php` | Header, nav, panier SVG | ~200 | 2026-02-06 | |
| `footer.php` | Footer + quick-view modal shell | ~72 | 2026-02-09 | |
| `woocommerce/single-product.php` | Fiche produit complète (hero, details, FAQ, sticky bar) | ~1061 | 2026-02-11 | Template custom, pas standard WC |
| `woocommerce/archive-product.php` | Page /nos-creations/ (hero magazine + carrousel + filtres) | ~366 | 2026-02-11 | Hero avec ACF image + focal point |
| `woocommerce/taxonomy-product_cat.php` | Pages catégories (mini-carousel + grille + editorial) | ~357 | 2026-02-10 | Custom queries (main query corrompue) |
| `woocommerce/content-product.php` | Card produit (vignette dans grilles/carrousels) | ~208 | 2026-02-11 | H3 pour SEO (pas H2) |
| `assets/cinetique.js` | Animations : parallax, particles canvas, filtres, scroll effects | ~1269 | 2026-02-11 | rAF throttle sur mousemove |
| `assets/quick-view.js` | Modal aperçu rapide produit (galerie, swatches, AJAX cart) | ~746 | 2026-02-11 | fetchWithTimeout 5s |
| `assets/menu.js` | Menu burger, search modal, focus trap WCAG | ~577 | 2026-02-11 | Focus trap sur modal recherche |
| `assets/shop.js` | Variation swatches, carrousel auto-scroll | ~781 | 2026-02-05 | |
| `assets/admin-focal-point.js` | Picker point focal hero (admin only) | ~201 | 2026-02-11 | AJAX save, pas save_post |
| `assets/admin-focal-point.css` | Styles admin picker focal | ~130 | 2026-02-11 | |
| `assets/fonts/SquarePeg-Regular.woff2` | Police display (latin) | 21.7KB | 2026-02-11 | Locale uniquement |
| `assets/fonts/SquarePeg-Regular-latin-ext.woff2` | Police display (latin-ext, accents FR) | 17.9KB | 2026-02-11 | Locale uniquement |

---

## 2. PIÈGES FRÉQUENTS — CE QUI NE MARCHE PAS

### "C'est un problème de cache"
**Réalité :** Sur ce projet, le cache est rarement le problème (pas de cache sur Local by Flywheel).

Avant de suggérer de vider le cache, vérifier :
1. Le fichier est-il VRAIMENT enregistré ?
2. Est-ce le BON fichier ? (attention : structure duale root + nested, voir section 4)
3. Git a-t-il bien synchronisé ? (`rsync` fait ?)
4. Y a-t-il une erreur PHP silencieuse ? (regarder les logs)
5. Le `filemtime()` est-il bien utilisé pour le cache busting des assets ?

### "Utilise Google Fonts pour Square Peg"
**Réalité :** NE FONCTIONNE PAS sur Safari. Safari charge Zapfino comme fallback `cursive` et le rendu est illisible.
**Faire :** Utiliser UNIQUEMENT la police locale dans `assets/fonts/`. Preload dans `functions.php`.
**Ce qui n'a pas marché :** Changer le fallback en `sans-serif`, forcer le rechargement Google Fonts, vider le cache.

### "Ajoute !important"
**Réalité :** Crée des guerres de spécificité impossibles à maintenir.
**Faire :** Augmenter la spécificité du sélecteur proprement.
**Exception documentée :** `.is-filtered-out { display: none !important }` — nécessaire car `ul.products li.product { display: block !important }` de WooCommerce.

### "Mets un setTimeout pour fixer le timing"
**Réalité :** Cache un problème de race condition.
**Faire :** Utiliser les événements appropriés (DOMContentLoaded, load, IntersectionObserver).

### "Utilise `save_post` pour la meta box focal point"
**Réalité :** Gutenberg re-soumet les formulaires meta box au save, ce qui écrase la valeur AJAX.
**Faire :** AJAX-only avec `wp_ajax_sapi_save_focal_point`. Pas de hook `save_post`.

### "Supprime `posts_per_page => -1`, c'est dangereux"
**Réalité :** Sur ce projet, c'est **intentionnel** pour le filtrage client-side JS (~20-40 produits max par catégorie). Le volume est contrôlé et ne posera pas de problème de performance.
**NE PAS changer** sans refactoriser tout le système de filtres JS.

### "L'audit dit qu'il y a une faille XSS dans cinetique.js"
**Réalité :** Un audit automatisé a cité cinetique.js:416 mais c'est du code canvas particles, pas du innerHTML. Le vrai innerHTML (inoffensif) est dans menu.js pour le SVG search icon. Vérifier les claims d'audit contre le code réel.

---

## 3. CE QUI CASSE RÉGULIÈREMENT

### Police Square Peg illisible
- **Symptôme :** Police moche ou Zapfino sur Safari
- **Cause :** Google Fonts + fallback `cursive`
- **Solution :** Police locale (`@font-face` dans style.css) + preload + fallback `'Brush Script MT', 'Segoe Script', Georgia, serif`
- **NE PAS faire :** Réactiver Google Fonts, utiliser `cursive` comme fallback

### Image hero ne remplit pas la section
- **Symptôme :** Image coupée en bas ou pas en plein écran
- **Cause :** `img { height: auto }` global écrase `height: 100%` des images positionnées absolues
- **Solution :** `height: 100% !important` + `width: 100% !important` sur l'image hero
- **NE PAS faire :** Retirer le `!important` — il est nécessaire ici

### Bouton aperçu rapide toujours visible
- **Symptôme :** Le bouton "Aperçu" est visible sans hover
- **Cause :** `opacity: 0.7` au lieu de `opacity: 0` en état par défaut
- **Solution :** `opacity: 0` + `pointer-events: none` par défaut, `opacity: 1` + `pointer-events: auto` au hover

### Sticky bar scroll ne fonctionne pas
- **Symptôme :** "Choisir les options" ne scroll pas vers les options
- **Cause :** L'attribut `id="product-summary-main"` manquant sur la cible
- **Solution :** Vérifier que `<div class="product-info-v2" id="product-summary-main">` existe dans single-product.php

### Quick-view cassé après cleanup console.log
- **Symptôme :** SyntaxError dans quick-view.js
- **Cause :** `perl -ni` supprime la ligne du `console.log(` mais laisse les propriétés de l'objet litéral sur les lignes suivantes
- **Solution :** Toujours vérifier les console.log multi-lignes avant suppression automatique

### Main query corrompue sur pages catégorie
- **Symptôme :** La grille produits affiche les mauvais produits ou est vide
- **Cause :** Le mini-carousel utilise une custom query qui corrompt la main query WooCommerce
- **Solution :** Utiliser `$grid_query` dédié avec `wp_reset_postdata()` après chaque query custom

### Styles pas déployés
- **Symptôme :** Les modifications ne s'affichent pas sur testlumineux
- **Cause :** Commit fait dans le nested repo au lieu du root
- **Solution :** Toujours `rsync` nested → root, puis commit au root (voir section 4)

---

## 4. RÈGLES DE CODE OBLIGATOIRES

### Structure duale du repo (CRITIQUE)
```
/Users/samuel/Local/atelier-sapi/              <- ROOT (déployé par GitHub Actions)
├── .git/
├── style.css, functions.php, etc.
└── wp-content/themes/theme-sapi-maison/       <- NESTED (WordPress local)
    ├── .git/
    └── style.css, functions.php, etc.
```
**Workflow :**
1. Travailler dans `wp-content/themes/theme-sapi-maison/` (pour tests WordPress local)
2. Syncer : `rsync -av --exclude='.git' --exclude='.github' wp-content/themes/theme-sapi-maison/ .`
3. Commit au ROOT, jamais dans le nested !

**JAMAIS changer `local-dir` dans le workflow GitHub Actions — cause page blanche !**

### Cache busting
```php
// OBLIGATOIRE — filemtime() pour versionner les assets
wp_enqueue_style('style', get_stylesheet_uri(), [], filemtime(get_stylesheet_directory() . '/style.css'));

// INTERDIT — version statique = cache stale
wp_enqueue_style('style', get_stylesheet_uri(), [], '0.1.1');
```

### Échappement PHP
```php
// OBLIGATOIRE à la sortie
echo esc_html($variable);
echo esc_url($url);
echo esc_attr($attribute);

// INTERDIT
echo $variable; // sans échappement
```

### JavaScript
```javascript
// OBLIGATOIRE — throttle les événements fréquents
let raf = null;
element.addEventListener('mousemove', (e) => {
  if (raf) return;
  raf = requestAnimationFrame(() => { /* ... */ raf = null; });
});

// OBLIGATOIRE — passive pour scroll/touch
window.addEventListener('scroll', handler, { passive: true });

// OBLIGATOIRE — timeout sur fetch
function fetchWithTimeout(url, timeout = 5000) { /* AbortController pattern */ }

// INTERDIT — pas de console.log en production
console.log('debug');
```

### Safari
- Toujours ajouter `-webkit-` pour transforms/transitions
- Utiliser `translateZ(0)` pour GPU acceleration
- Tester sur Safari mobile avant chaque push

---

## 5. DÉPENDANCES ET VERSIONS

| Composant | Version | Notes |
|-----------|---------|-------|
| WordPress | 6.7+ | |
| WooCommerce | 9.x | Templates custom dans `woocommerce/` |
| PHP | 8.0+ | `mb_substr` utilisé (meta descriptions) |
| ACF | Free (pas Pro) | Champ `shop_hero_image` sur page Shop |
| Square Peg font | woff2 locale | Depuis Google Fonts v7, auto-hébergée |
| Elementor | Supprimé | Nettoyé, redirections 301 conservées |
| jQuery | Via WP core | Utilisé uniquement par WooCommerce |

**Zéro dépendance JS externe** — tout est vanilla JS (cinetique.js, quick-view.js, menu.js, shop.js).

---

## 6. CHECKLIST AVANT COMMIT

- [ ] Pas de `console.log()` oubliés
- [ ] Tous les `echo` sont échappés (`esc_html`, `esc_url`, `esc_attr`)
- [ ] `filemtime()` utilisé pour les versions d'assets (pas de string statique)
- [ ] Préfixes `-webkit-` sur les transforms/transitions
- [ ] Testé sur Safari mobile
- [ ] Pas d'URL de staging hardcodée (`testlumineux.atelier-sapi.fr`)
- [ ] `rsync` nested → root fait avant commit
- [ ] Commit au ROOT (pas dans le nested)
- [ ] CUSTOMIZATIONS.md mis à jour si changement notable

---

## 7. HISTORIQUE DES MODIFICATIONS

### [2026-02-12] — Audit & Fix mobile homepage (commit 597c5bc)
**Fichiers :** `style.css`
**Problèmes identifiés :**
- `.collections-grid` `minmax(300px, 1fr)` → overflow sur iPhone SE (320px < 300px)
- `.process-inner` 5 flex items horizontaux sans wrap → overflow horizontal
- `.carousel-product-name` à `3rem` fixe sous 768px → trop gros sur petit écran
- Aucun `overflow-x: hidden` sur `.hero-bento`, `.collections-kinetic`, `.homepage-carousel-fullscreen`
- `.bento-container` `grid-auto-rows: 420px` fixe → pas flexible pour le contenu
- Newsletter form pas responsive (flex row sur mobile)

**Corrections :**
- `overflow-x: hidden` sur `.hero-bento`, `.collections-kinetic`, `.homepage-carousel-fullscreen`
- Collections grid : `minmax(min(280px, 100%), 1fr)` — empêche l'overflow sur petits écrans
- Process card : `overflow-x: auto` + `flex-shrink: 0` sur les steps (scroll horizontal natif)
- Carousel title (≤480px) : `clamp(1.75rem, 10vw, 2.5rem)`
- Bento grid (≤768px) : `grid-auto-rows: minmax(280px, auto)` au lieu de 420px fixe
- Newsletter (≤768px) : form en colonne, input 16px (empêche zoom iOS)
- Breakpoint ≤375px (iPhone SE) : padding réduit, fonts réduites
- Fix accolade CSS orpheline ligne 12583

**Leçon :** Toujours utiliser `minmax(min(X, 100%), 1fr)` pour les grilles CSS responsive — `minmax(X, 1fr)` force une largeur minimale qui overflow sur petits écrans.

### [2026-02-12] — Modifs PDF (breadcrumb, couleurs, grille, cards)
**Fichiers :** `style.css`, `front-page.php`, `functions.php`, `archive-product.php`, `cinetique.js`
**Changements :**
- Breadcrumb : SVG ampoule en séparateur, niveau intermédiaire "Nos créations", hover bois
- Couleur orange harmonisée : `#E67E22` → `#E35B24`
- Carousel buttons : border-radius 50px (pilule)
- Carousel text-shadow : supprimé l'offset 4px (effet doublé)
- Grille produits NC : pseudo-éléments WooCommerce supprimés, 4 colonnes, max-width 1400px
- Cards Suze/Timothée : nouvelle classe `.bento-product-featured` (statique, zoom au hover)
- `.site-content` padding-top : 40px → 0 (bande blanche supprimée)
- Stat blocks : padding réduit
- Collections/Process : spacing réduit

### [2026-02-11] — Self-host Square Peg font (commit 0d1b3d0)
**Fichiers :** `style.css`, `functions.php`, `assets/fonts/`
**Problème :** Police Square Peg illisible sur Safari (fallback `cursive` → Zapfino)
**Ce qui N'A PAS marché :** URL Google Fonts v5 (mauvaise version, fichier HTML au lieu de woff2)
**Solution :** Télécharger woff2 depuis Google Fonts v7 (latin + latin-ext), @font-face dans style.css, preload dans functions.php, remplacer 19 occurrences de `cursive` par `'Brush Script MT', 'Segoe Script', Georgia, serif`

### [2026-02-11] — Optimisations perf + a11y (commit dee62ad)
**Fichiers :** `cinetique.js`, `quick-view.js`, `menu.js`, `functions.php`, `single-product.php`
**Source :** Audit trié (7 fixes légitimes sur ~20 recommandations, le reste était incorrect ou déjà fait)
**Changements :**
- Throttle rAF sur mousemove des product cards (`cinetique.js`)
- Cache des sélecteurs DOM dans le scroll handler (`cinetique.js`)
- `{ passive: true }` sur le scroll listener (`cinetique.js`)
- `fetchWithTimeout` avec AbortController 5s (`quick-view.js`)
- Suppression de 39 console.log/error/warn (`quick-view.js`)
- Focus trap WCAG sur modal recherche (`menu.js`)
- Meta descriptions dynamiques pour produits/catégories/shop/home (`functions.php`)
- Hook `woocommerce_after_single_product` manquant (`single-product.php`)

### [2026-02-11] — Fix quick-view + aperçu hover + sticky scroll (commit 805328a)
**Fichiers :** `quick-view.js`, `style.css`, `single-product.php`
**Problème 1 :** quick-view cassé — SyntaxError après suppression console.log multi-ligne
**Solution 1 :** Retirer les propriétés orphelines de l'objet litéral, garder `this.updateProductInfo(productData)`
**Problème 2 :** Bouton aperçu toujours visible (opacity 0.7 par défaut)
**Solution 2 :** opacity 0 + pointer-events none par défaut, opacity 1 au hover
**Problème 3 :** Sticky bar "Choisir les options" ne scrollait pas
**Solution 3 :** Ajout `id="product-summary-main"` sur `.product-info-v2`

### [2026-02-11] — Hero magazine /nos-creations/ + Focal Point Picker
**Fichiers :** `archive-product.php`, `style.css`, `functions.php`, `admin-focal-point.js/css`
**Changement :** Remplacement grille 50/50 (texte + 3 images) par hero full-width magazine-style
**Image :** ACF `shop_hero_image` → fallback galerie featured → fallback produit récent
**Focal point :** Meta box admin avec crosshair draggable + preview rectangle + AJAX save (pas save_post)
**Ce qui N'A PAS marché :** `save_post` hook (Gutenberg écrase la valeur AJAX)

### [2026-02-10] — Refonte page catégorie
**Fichiers :** `taxonomy-product_cat.php`, `functions.php`, `style.css`
**Changements :** Mini-carousel "Coups de coeur" (4 best-sellers), grille complète, editorial en bas
**Leçons :** `wc_get_template_part()` ne permet pas de layout custom → HTML inline pour carousel. Main query corrompue après carousel → custom `$grid_query` nécessaire.

### [2026-02-09] — Quick View Modal
**Fichiers :** `footer.php`, `quick-view.js` (783 lignes), `style.css` (850+ lignes)
**Feature :** Modal aperçu sans quitter la page — galerie multi-images, swatches, AJAX cart, zoom fullscreen

### [2026-02-07] — Harmonisation premium (5 vagues, 3803 insertions)
**Commits :** 2bd9f91, 9ff6945, f1803e0, 58744ce, 04703db, 4cc440a
**Scope :** Toutes les pages au niveau premium de la HP — gradients, Square Peg, clamp(), hover effects, editorial content, parallax, canvas particles, fade-in animations, infinite scroll blog
**Tag :** `v1.0.0-premium-complete`

### [2026-02-07] — Phase 4 Conversion (commit b91745e)
**Features :** Double CTA (ajouter au panier + acheter maintenant), livraison estimée dynamique (8 jours ouvrés), accordéon specs mobile

### [2026-02-06] — Phase 2 Design System Premium (commit cb3e87b)
**Features :** Variables CSS enrichies (crèmes, bois, gradients), CTA orange gradient, gallery overlay doré, section artisan premium, badges certifications

### [2026-02-05] — Fiche produit + Panier/Checkout CINÉTIQUE
**Features :** Hero style HP sur produit, détails 2 colonnes, FAQ chevrons, sticky bar, variation toggles, carrousel produits, filtres client-side, process bar hover images, chevron dropdown menu

### [2026-02-04] — Setup initial
**Features :** Création thème custom depuis Elementor, refonte CSS (~280 lignes mortes supprimées), variables unifiées `--color-*`, correction 47 URLs images, redirections 301 pages statiques → catégories WC, fix panier "Olivia" (contenu statique hardcodé)

---

## 8. TAGS GIT

| Tag | Description |
|-----|-------------|
| `v1.0.0-premium-complete` | Fin harmonisation 5 vagues |
| `v2.1.0-hero-focal-font` | Avant optimisations audit (point de rollback) |

---

## 9. WORKFLOW DE DÉPLOIEMENT

```
Local (wp-content/themes/theme-sapi-maison/) → rsync → ROOT → GitHub → O2switch (auto)
```

**Branche :** `test-theme-sapi-maison` (NE PAS push sur main/master)
**Cible :** `https://testlumineux.atelier-sapi.fr`
**Production :** `https://atelier-sapi.fr`

**URLs d'images :** Actuellement hardcodées vers `testlumineux.atelier-sapi.fr`.
Avant mise en production : refactoriser avec `wp_get_attachment_url()` ou URLs relatives.

---

*Fichier maintenu par l'équipe Atelier Sapi*

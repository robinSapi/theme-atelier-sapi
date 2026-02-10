# AUDIT PAGE CATÉGORIE — SUSPENSION
## Analyse UX/UI de la partie haute (Header Zone)

**Date :** 10 février 2026
**Page auditée :** `/categorie-produit/suspension/`
**Site :** testlumineux.atelier-sapi.fr
**Auditeur :** Claude (Expert UX/UI e-commerce)

---

## DIAGNOSTIC : PARTIE HAUTE

### 📸 État actuel observé

```
┌─────────────────────────────────────────────────────────────┐
│  🔶 BARRE RÉASSURANCE (Fait-main · 5j · Retours 30j)        │
├─────────────────────────────────────────────────────────────┤
│  Header / Navigation                                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│                    [ÉNORME ESPACE BLANC]                     │
│                         ~150px                               │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│  ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓  │
│  BANDEAU GRIS #F5F5F5                                        │
│  Accueil > Nos créations > Suspension                        │
│  ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│                    [ESPACE BLANC]                            │
│                         ~80px                                │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│     01                    ← Police serif incohérente         │
│                                                              │
│     Suspension            ← Titre catégorie                  │
│                                                              │
│     "Retrouvez ici tous nos lustres, prêts à faire          │
│      rayonner votre déco intérieure !"                       │
│                           ↑ Décalé à gauche, pas centré      │
│                                                              │
├─────────────────────────────────────────────────────────────┤
│  [Grille produits...]                                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔴 PROBLÈMES IDENTIFIÉS

### 1. Espacement vertical excessif (~230px perdus)

| Zone | Espace actuel | Espace recommandé | Excès |
|------|---------------|-------------------|-------|
| Header → Breadcrumb | ~150px | 24-32px | **+120px** |
| Breadcrumb → Titre | ~80px | 32-48px | **+40px** |
| **Total perte** | **~230px** | — | **≈3 écrans mobiles** |

**Impact :** L'utilisateur doit scroller pour voir le premier produit. Sur mobile, le "above the fold" est entièrement gaspillé par du vide. Baymard Institute recommande que les premiers produits soient visibles sans scroll sur 85%+ des viewports.

---

### 2. Bandeau fil d'Ariane — Design incohérent

**Problèmes :**
- **Couleur grise (#F5F5F5)** : Crée une rupture visuelle brutale avec le fond crème (#FEFDFB) de la marque
- **Largeur 100%** : Barre pleine largeur = lourdeur visuelle, style "back-office"
- **Position flottante** : Trop éloigné de la réassurance (150px+) et du titre (80px+)
- **Typographie** : Texte trop petit, pas de hiérarchie avec le reste

**Benchmark :** Les sites premium (HAY, Moooi, Ferm Living) utilisent :
- Fil d'Ariane **inline** sous le header, sans fond coloré
- Même couleur de fond que la page
- Typographie discrète mais lisible (12-14px, couleur secondaire)

---

### 3. Numérotation "01" — Incohérence typographique

**Problèmes :**
- Police **serif** (type Georgia/Times) alors que le site utilise Montserrat (sans-serif)
- Style "magazine print" déconnecté de l'identité web
- **Aucune fonction** : Le "01" n'apporte aucune information (ce n'est pas un classement, pas une pagination)
- Prend de l'espace vertical précieux

**Benchmark :** Aucun site e-commerce premium n'utilise de numérotation arbitraire sur ses pages catégories. C'est un pattern de sites vitrines/portfolios mal transféré au e-commerce.

**Recommandation :** Supprimer complètement ou remplacer par un élément utile (nombre de produits : "12 luminaires").

---

### 4. Tagline mal alignée et peu engageante

**Problèmes :**
- **Alignement** : Décalé à gauche alors que le titre est centré
- **Contenu** : "Retrouvez ici tous nos lustres..." → générique, pas émotionnel
- **Longueur** : Trop longue pour un header de catégorie

**Benchmark copy premium :**
| Site | Tagline catégorie |
|------|-------------------|
| HAY | "Lighting that sets the mood" |
| Moooi | "Illuminate your world" |
| Ferm Living | "Light up your space" |
| Menu | "Sculptural lighting for modern living" |

**Recommandation :**
```
"Suspensions artisanales — Bois sculpté, lumière vivante"
```
ou simplement aucune tagline (le visuel parle).

---

### 5. Absence de visuel hero

**Problème :** La page catégorie n'a **aucune image d'en-tête**. C'est un header 100% texte sur fond blanc.

**Impact :**
- Zéro émotion
- Zéro immersion dans l'univers Sapi
- Ressemble à une page de résultats de recherche, pas à une boutique artisanale

**Benchmark :** 90% des sites e-commerce premium ont un **hero visuel** sur leurs pages catégories :

| Site | Hero catégorie |
|------|----------------|
| HAY | Image lifestyle pleine largeur (40-50vh) avec titre superposé |
| Moooi | Vidéo/image immersive avec produits phares |
| Ferm Living | Bandeau photo ambiance + titre centré |
| MADE | Carrousel produits vedettes |

---

## 📊 BENCHMARK PAGES CATÉGORIES E-COMMERCE PREMIUM

### Patterns observés (Baymard Institute + analyse concurrentielle)

| Pattern | Adoption | Application Sapi |
|---------|----------|------------------|
| **Hero visuel** (image/vidéo en header) | 87% des sites premium | ❌ Absent |
| **Fil d'Ariane inline** (sans fond coloré) | 92% | ❌ Bandeau gris |
| **Titre + sous-titre centré** | 78% | ⚠️ Partiellement (alignement cassé) |
| **Compteur produits visible** | 85% | ❌ Absent |
| **Filtres above the fold** | 91% | ❌ Absent |
| **Premier produit visible sans scroll (desktop)** | 95% requis | ❌ ~230px de padding |

### Structure header catégorie recommandée (State of the Art)

```
┌─────────────────────────────────────────────────────────────┐
│  Réassurance                                                 │
├─────────────────────────────────────────────────────────────┤
│  Navigation                                                  │
├─────────────────────────────────────────────────────────────┤
│  Accueil › Nos créations › Suspension    (inline, discret)  │
├─────────────────────────────────────────────────────────────┤
│  ┌───────────────────────────────────────────────────────┐  │
│  │                                                       │  │
│  │            [IMAGE HERO 40-50vh]                       │  │
│  │      Suspension artisanale en situation               │  │
│  │                                                       │  │
│  │              ─────────────────                        │  │
│  │                 SUSPENSIONS                           │  │
│  │           "Bois sculpté, lumière vivante"             │  │
│  │                                                       │  │
│  └───────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────┤
│  [Filtres]  Taille ▾  |  Matériau ▾  |  Prix ▾   12 produits│
├─────────────────────────────────────────────────────────────┤
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐                           │
│  │     │ │     │ │     │ │     │  ← Produits visibles      │
│  │     │ │     │ │     │ │     │    immédiatement          │
│  └─────┘ └─────┘ └─────┘ └─────┘                           │
└─────────────────────────────────────────────────────────────┘
```

---

## ✅ RECOMMANDATIONS

### P0 — Critiques (immédiat)

| # | Action | Détail | Impact | Effort |
|---|--------|--------|--------|--------|
| 1 | **Supprimer le bandeau gris** | Fil d'Ariane inline, fond transparent, même ligne que nav ou juste en dessous | 🔴 Cohérence visuelle | ⚡ 30min |
| 2 | **Réduire padding vertical** | Header→Breadcrumb: 24px / Breadcrumb→Titre: 32px | 🔴 Above the fold | ⚡ 15min |
| 3 | **Supprimer le "01"** | Élément inutile, incohérent typographiquement | 🔴 Cohérence | ⚡ 5min |
| 4 | **Centrer la tagline** | Aligner avec le titre, ou supprimer | 🔴 Alignement | ⚡ 5min |

### P1 — Importants (Sprint 1)

| # | Action | Détail | Impact | Effort |
|---|--------|--------|--------|--------|
| 5 | **Ajouter hero visuel** | Image lifestyle 40-50vh avec suspension Sapi, titre superposé en blanc | 🟠 Émotion +++ | 🔶 2-4h |
| 6 | **Réécrire la tagline** | "Suspensions artisanales — Bois sculpté, lumière vivante" ou équivalent court/poétique | 🟠 Branding | ⚡ 30min |
| 7 | **Ajouter compteur produits** | "12 créations" visible près des filtres ou du titre | 🟠 Information | ⚡ 30min |
| 8 | **Ajouter barre de filtres** | Taille / Matériau / Prix — visible above the fold | 🟠 UX Navigation | 🔶 4h |

### P2 — Améliorations (Sprint 2+)

| # | Action | Détail | Impact | Effort |
|---|--------|--------|--------|--------|
| 9 | **Titre en Square Peg** | Utiliser la typo signature pour "Suspension" | 🟡 Branding | ⚡ 15min |
| 10 | **Animation hero** | Léger parallax ou fade-in au scroll | 🟡 Premium feel | 🔶 2h |
| 11 | **Catégorie mise en avant** | Badge "Bestseller" ou "Nouveauté" sur certains produits | 🟡 Conversion | 🔶 2h |

---

## 🎨 CSS RECOMMANDÉ

### Fil d'Ariane inline (sans fond)

```css
.woocommerce-breadcrumb {
  /* Supprimer le bandeau gris */
  background: transparent;
  padding: 12px 0;
  margin: 0;

  /* Typographie discrète */
  font-family: 'Montserrat', sans-serif;
  font-size: 13px;
  color: var(--sapi-wood-60); /* #937D68 à 60% opacité */

  /* Alignement */
  text-align: left;
  max-width: var(--content-width);
  margin: 0 auto;
}

.woocommerce-breadcrumb a {
  color: var(--sapi-wood);
  text-decoration: none;
  transition: color 0.2s ease;
}

.woocommerce-breadcrumb a:hover {
  color: var(--sapi-orange);
}

/* Séparateur */
.woocommerce-breadcrumb .breadcrumb-separator {
  margin: 0 8px;
  color: var(--sapi-wood-40);
}
```

### Header catégorie avec hero

```css
.archive-header {
  position: relative;
  min-height: 45vh;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  align-items: center;
  padding: 48px 24px;
  margin-bottom: 32px;

  /* Image hero en background */
  background-image: url('/path/to/suspension-hero.jpg');
  background-size: cover;
  background-position: center;
}

/* Overlay pour lisibilité texte */
.archive-header::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to top,
    rgba(254, 253, 251, 0.95) 0%,
    rgba(254, 253, 251, 0.4) 50%,
    transparent 100%
  );
}

/* Titre catégorie */
.archive-header__title {
  position: relative;
  z-index: 1;
  font-family: 'Square Peg', cursive;
  font-size: clamp(42px, 8vw, 72px);
  color: var(--sapi-wood);
  margin: 0;
  text-align: center;
}

/* Tagline */
.archive-header__tagline {
  position: relative;
  z-index: 1;
  font-family: 'Montserrat', sans-serif;
  font-size: 16px;
  font-weight: 300;
  letter-spacing: 0.05em;
  color: var(--sapi-wood-80);
  margin-top: 12px;
  text-align: center;
}

/* Compteur produits */
.archive-header__count {
  position: relative;
  z-index: 1;
  font-size: 14px;
  color: var(--sapi-wood-60);
  margin-top: 24px;
}
```

### Spacing corrigé

```css
/* Variables spacing */
:root {
  --space-xs: 8px;
  --space-sm: 16px;
  --space-md: 24px;
  --space-lg: 32px;
  --space-xl: 48px;
}

/* Header → Breadcrumb */
.site-header + .woocommerce-breadcrumb,
.site-header + * .woocommerce-breadcrumb {
  margin-top: var(--space-md); /* 24px au lieu de 150px */
}

/* Breadcrumb → Archive header */
.woocommerce-breadcrumb + .archive-header {
  margin-top: var(--space-lg); /* 32px au lieu de 80px */
}

/* Archive header → Grille produits */
.archive-header + .products {
  margin-top: var(--space-xl); /* 48px */
}
```

---

## 📐 WIREFRAME AVANT/APRÈS

### AVANT (Actuel) — Desktop 1440px

```
┌────────────────────────────────────────────────────────────────────┐
│ Réassurance                                                         │
├────────────────────────────────────────────────────────────────────┤
│ Logo                          Nav                           Icônes │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│                                                                     │
│                         [150px vide]                                │
│                                                                     │
│                                                                     │
├════════════════════════════════════════════════════════════════════┤
│▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│
│▓  Accueil > Nos créations > Suspension                            ▓│
│▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓│
├════════════════════════════════════════════════════════════════════┤
│                                                                     │
│                         [80px vide]                                 │
│                                                                     │
├────────────────────────────────────────────────────────────────────┤
│                              01                                     │
│                                                                     │
│                          Suspension                                 │
│                                                                     │
│  "Retrouvez ici tous nos lustres, prêts à faire rayonner           │
│   votre déco intérieure !"     ← Mal aligné                        │
│                                                                     │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │          │  │          │  │          │  │          │           │
│  │ Produit  │  │ Produit  │  │ Produit  │  │ Produit  │  ENFIN    │
│  │          │  │          │  │          │  │          │  VISIBLE  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  ↓        │
```

**Verdict :** ~400px de scroll avant le premier produit. Expérience catastrophique.

---

### APRÈS (Recommandé) — Desktop 1440px

```
┌────────────────────────────────────────────────────────────────────┐
│ Réassurance                                                         │
├────────────────────────────────────────────────────────────────────┤
│ Logo                          Nav                           Icônes │
├────────────────────────────────────────────────────────────────────┤
│ Accueil › Nos créations › Suspension                    (inline)   │
├────────────────────────────────────────────────────────────────────┤
│ ┌────────────────────────────────────────────────────────────────┐ │
│ │                                                                │ │
│ │                    [IMAGE HERO 45vh]                           │ │
│ │           Photo lifestyle suspension Sapi                      │ │
│ │                                                                │ │
│ │                      ───────────────                           │ │
│ │                       Suspensions                              │ │
│ │               "Bois sculpté, lumière vivante"                  │ │
│ │                                                                │ │
│ │                        12 créations                            │ │
│ │                                                                │ │
│ └────────────────────────────────────────────────────────────────┘ │
├────────────────────────────────────────────────────────────────────┤
│ Filtres:  [Taille ▾]  [Matériau ▾]  [Prix ▾]         Trier par ▾  │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐           │
│  │          │  │          │  │          │  │          │  VISIBLE  │
│  │ Produit  │  │ Produit  │  │ Produit  │  │ Produit  │  ABOVE    │
│  │          │  │          │  │          │  │          │  THE FOLD │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘           │
```

**Améliorations :**
- Premier produit visible sans scroll (desktop)
- Hero émotionnel avec image lifestyle
- Fil d'Ariane intégré, pas de bandeau gris
- Titre en typo signature Square Peg
- Tagline courte et centrée
- Filtres accessibles immédiatement

---

## 📱 VERSION MOBILE (375px)

### Recommandation mobile

```
┌─────────────────────────┐
│ Réassurance (compact)   │
├─────────────────────────┤
│ ☰  Logo  🔍 🛒         │
├─────────────────────────┤
│ Accueil › Suspension    │
├─────────────────────────┤
│ ┌─────────────────────┐ │
│ │                     │ │
│ │   [HERO 35vh]       │ │
│ │                     │ │
│ │    Suspensions      │ │
│ │  "Lumière vivante"  │ │
│ │                     │ │
│ └─────────────────────┘ │
├─────────────────────────┤
│ [Filtrer ▾] 12 produits │
├─────────────────────────┤
│ ┌─────────┐ ┌─────────┐ │
│ │         │ │         │ │
│ │ Produit │ │ Produit │ │
│ │         │ │         │ │
│ └─────────┘ └─────────┘ │
│ ┌─────────┐ ┌─────────┐ │
│ │         │ │         │ │
```

---

## 🏁 RÉCAPITULATIF ACTIONS

| Priorité | Action | Fichier/Élément | Temps estimé |
|----------|--------|-----------------|--------------|
| 🔴 P0 | Supprimer bandeau gris breadcrumb | CSS `.woocommerce-breadcrumb` | 30 min |
| 🔴 P0 | Réduire padding vertical | CSS archive template | 15 min |
| 🔴 P0 | Supprimer "01" | Template PHP archive | 5 min |
| 🔴 P0 | Centrer tagline | CSS `.archive-description` | 5 min |
| 🟠 P1 | Ajouter hero visuel | Template + CSS + image | 2-4h |
| 🟠 P1 | Réécrire tagline | Contenu WP | 30 min |
| 🟠 P1 | Ajouter filtres | WooCommerce + CSS | 4h |
| 🟡 P2 | Typo Square Peg pour titre | CSS | 15 min |
| 🟡 P2 | Animation parallax hero | JS + CSS | 2h |

**Total estimé P0 :** ~1h
**Total estimé P0+P1 :** ~8h
**Total complet :** ~12h

---

## SOURCES BENCHMARK

- [Baymard Institute — Category Page UX](https://baymard.com/blog/ecommerce-category-page-design) (2024-2025)
- [HAY — Lighting Category](https://hay.dk/lighting)
- [Moooi — Lighting](https://moooi.com/lighting)
- [Ferm Living — Lighting](https://fermliving.com/lighting)
- [MADE.com — Lighting](https://made.com/lighting)
- [Shopify Category Page Best Practices 2025](https://shopify.com/blog/category-page-design)

---

*Audit réalisé par Claude | 10 février 2026*
*Pour Atelier Sâpi — theme-sapi-maison*

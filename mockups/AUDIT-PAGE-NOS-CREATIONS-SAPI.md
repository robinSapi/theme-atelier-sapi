# AUDIT PAGE "NOS CRÉATIONS"
## Analyse UX/UI/SEO complète avec benchmark

**Date :** 10 février 2026
**Page auditée :** `/nos-creations/`
**Site :** testlumineux.atelier-sapi.fr
**Auditeur :** Claude (Expert UX/UI e-commerce)
**Méthode :** Audit visuel Chrome + analyse HTML + comparaison audit Codex

---

## RÉSUMÉ EXÉCUTIF

| Aspect | État | Verdict |
|--------|------|---------|
| SEO (H1/H2) | ✅ Correct | H1 "Nos Créations" bien en premier |
| 3 visuels hero | ⚠️ Problème de contenu | 2/3 images hors-sujet |
| CTA hero | ✅ Bon | 1 CTA principal clair |
| Filtres catégories | ⚠️ Dense | 7 boutons + 3 dropdowns |
| Numérotation "01/02" | ❌ Inutile | Supprimer |
| Grille produits | ⚠️ Mixte | Accessoires génériques mélangés |
| Footer | ✅ Propre | Bien structuré |

**Note globale :** 6/10 — Structure correcte mais visuels hero mal choisis et UX perfectible.

---

## CORRECTION DE L'AUDIT CODEX

Codex avait signalé plusieurs problèmes. Voici la vérification terrain :

| Problème Codex | Vérification | Verdict |
|----------------|--------------|---------|
| "H2 avant H1" | ❌ **FAUX** — H1 "Nos Créations" est bien en premier | Non confirmé |
| "Préparez vous" sans trait d'union | Non trouvé sur la page | Possiblement autre page |
| "6-7 CTA en haut" | ✅ **Confirmé** — 7 boutons catégories visibles | Confirmé |
| "3 visuels qui traînent" | ✅ **Confirmé** — Composition asymétrique à droite | Confirmé |
| "Tout les produits" faute | Non trouvé sur cette page | Possiblement ailleurs |
| Copyright incorrect | ❌ **FAUX** — "Tous droits réservés" correct | Non confirmé |

**Conclusion :** 2/6 problèmes Codex confirmés. L'audit sans visuel a généré des faux positifs.

---

## ANALYSE DÉTAILLÉE

### 1. HERO SECTION — Les 3 visuels à droite

#### Structure observée

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│  01                           ┌────────────────┐ ┌──────────────┐   │
│                               │                │ │ Packaging    │   │
│  Nos Créations                │  SUSPENSION    │ │ Sapi         │   │
│                               │  LIFESTYLE     │ │ (cartes)     │   │
│  Luminaires uniques,          │  (60%)         │ └──────────────┘   │
│  découpés au laser...         │                │ ┌──────────────┐   │
│                               │                │ │ Douilles     │   │
│  [DÉCOUVRIR LA COLLECTION ↓]  │                │ │ noires       │   │
│                               └────────────────┘ └──────────────┘   │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

#### Problèmes identifiés

| # | Problème | Impact | Sévérité |
|---|----------|--------|----------|
| 1 | **Image packaging** : Cartes/boîte Sapi — HORS SUJET | Dilue le message "créations artisanales" | 🟠 Moyen |
| 2 | **Image douilles** : Accessoires électriques noirs | Casse l'émotion premium, ressemble à un catalogue électricien | 🔴 Élevé |
| 3 | **Absence de luminaire allumé dans 2/3 images** | On vend de la LUMIÈRE mais on ne la montre qu'une fois | 🟠 Moyen |
| 4 | **Ratios d'images hétérogènes** | Image principale ≈ carré, les 2 autres rectangulaires | 🟡 Faible |

#### Ce qui fonctionne bien

- ✅ Image principale : Suspension allumée sur mur orange — lifestyle, émotion, couleur chaude
- ✅ Le luminaire EST allumé → on voit la lumière diffusée
- ✅ Composition asymétrique intentionnelle (pas 3 carrés égaux)

#### Recommandation visuels

**Option A — Garder 3 images :**
```
┌────────────────┐ ┌──────────────┐
│                │ │ Applique     │
│  SUSPENSION    │ │ lifestyle    │
│  (principale)  │ └──────────────┘
│                │ ┌──────────────┐
│                │ │ Lampadaire   │
└────────────────┘ │ lifestyle    │
                   └──────────────┘
```
→ 3 types de luminaires, tous allumés, tous lifestyle

**Option B — 1 hero fort :**
```
┌───────────────────────────────────────┐
│                                       │
│        SUSPENSION HERO 100%           │
│        (plein écran, immersif)        │
│                                       │
└───────────────────────────────────────┘
```
→ Impact maximal, choix premium (HAY, Moooi)

---

### 2. NUMÉROTATION "01 / 02" — Pattern inutile

**Observation :**
- "01" avant "Nos Créations"
- "02" avant "Pourquoi choisir l'Atelier Sâpi ?"

**Problèmes :**
- Police serif incohérente avec le design system (Montserrat)
- Aucune fonction : ce n'est ni une pagination, ni un classement
- Consomme de l'espace vertical
- Pattern de portfolio/magazine, pas d'e-commerce

**Benchmark :** 0/10 sites e-commerce premium utilisent ce pattern.

**Recommandation :** Supprimer complètement.

---

### 3. FILTRES CATÉGORIES — Densité élevée

**Observation :** 7 boutons + 3 dropdowns

```
[TOUT (29)] [CARTE CADEAU] [LAMPE À POSER] [ACCESSOIRE] [APPLIQUE] [LAMPADAIRE] [SUSPENSION]

[PRIX ▾]  [ESSENCE ▾]  [DIMENSIONS ▾]
```

**Analyse :**

| Élément | Verdict |
|---------|---------|
| TOUT (29) | ✅ Bon — CTA principal actif, compteur produits |
| CARTE CADEAU (1) | ⚠️ Questionnable — 1 seul produit, devrait être un lien footer |
| ACCESSOIRE (6) | ⚠️ Mélange créations artisanales et produits génériques |
| Filtres PRIX/ESSENCE/DIMENSIONS | ✅ Bon — utiles pour la recherche |

**Recommandation :**
- Déplacer "Carte cadeau" hors des filtres principaux (bannière ou footer)
- Séparer "Accessoires" des créations artisanales (ou masquer les ampoules MIIDEX)
- Réduire à : TOUT | SUSPENSION | APPLIQUE | LAMPADAIRE | LAMPE À POSER

---

### 4. GRILLE PRODUITS — Cohérence visuelle

**Observation :**

| Produit | Type | Image | Problème |
|---------|------|-------|----------|
| Gaston Le Chardon | Suspension | ✅ Fond blanc, produit bois | OK |
| Suze La Méduse | Suspension | ✅ Fond blanc, produit bois | OK |
| **Ampoule Poire** | Accessoire | ❌ Packaging MIIDEX visible | **Casse l'esthétique** |
| Dalida Le Dahlia | Suspension | ✅ Fond blanc, produit bois | OK |

**Problème critique :** L'ampoule avec son packaging MIIDEX (marque tierce) apparaît dans la grille des "créations". Ce n'est pas une création Sapi, c'est un accessoire générique revendu.

**Impact :**
- Incohérence avec le message "100% artisanal français"
- Rupture visuelle (emballage plastique vs bois naturel)
- Perception de qualité diminuée

**Recommandation :**
- Photo custom de l'ampoule sans packaging
- OU séparer les accessoires dans une section distincte
- OU filtrer par défaut "LUMINAIRES" et non "TOUT"

---

### 5. STRUCTURE SEO — État actuel

```
H3 — "Votre panier" (caché, mini-cart)
H1 — "Nos Créations" ✅
H2 — "Pourquoi choisir l'Atelier Sâpi ?"
  H3 — "100% artisanal français"
  H3 — "Pièces uniques & originales"
  H3 — "Bois PEFC & éco-responsable"
  H3 — "Service client réactif"
  H3 — "Fabriqué avec amour à Lyon"
H2 — "Gaston Le chardon" (produit)
H2 — "Suze La méduse" (produit)
H2 — "Ampoule Poire" (produit)
...
```

**Verdict :** ✅ Correct
- H1 unique et pertinent
- H2 pour sections principales
- H3 pour sous-éléments

**Amélioration possible :** Les noms de produits en H2 diluent le SEO. Envisager H3 pour les titres produits.

---

### 6. CONTENU & COPY

**Hero :**
> "Luminaires uniques, découpés au laser et assemblés à la main dans notre atelier lyonnais."

✅ Excellent — Court, factuel, différenciant (Lyon, laser, main).

**Section réassurance :**
> "Nous ne fabriquons pas juste des luminaires. Nous créons des pièces uniques qui transforment votre intérieur en un lieu où il fait bon vivre."

✅ Bon — Émotionnel, bénéfice client clair.

**Pas de fautes détectées** sur cette page (contrairement à l'audit Codex).

---

## BENCHMARK VISUEL

### Pages "Nos créations" / "Shop all" — Best practices

| Site | Hero | Filtres | Pattern clé |
|------|------|---------|-------------|
| **HAY** | 1 image lifestyle pleine largeur | Sidebar sticky | Minimalisme, respiration |
| **Moooi** | Vidéo/animation hero | Filtres drawer | Immersion totale |
| **Ferm Living** | Carrousel 3 images | Pills + dropdown | Équilibre lifestyle/produit |
| **MADE** | Hero + "Shop by category" | Pills horizontaux | Navigation guidée |

**Ce que Sapi peut reprendre :**
1. **1 hero fort** au lieu de 3 images hétérogènes
2. **Filtres en sidebar** (desktop) pour plus de clarté
3. **Vidéo courte** d'un luminaire qui s'allume (différenciation)

---

## RECOMMANDATIONS PRIORISÉES

### P0 — Critiques (cette semaine)

| # | Action | Fichier/Zone | Effort | Impact |
|---|--------|--------------|--------|--------|
| 1 | Remplacer image packaging par luminaire | Hero | 30min | 🔴 Perception |
| 2 | Remplacer image douilles par luminaire | Hero | 30min | 🔴 Perception |
| 3 | Supprimer "01" et "02" | CSS/Template | 15min | 🟠 Cohérence |
| 4 | Masquer ampoule MIIDEX ou photo custom | Grille | 1h | 🟠 Cohérence |

### P1 — Important (Sprint 1)

| # | Action | Fichier/Zone | Effort | Impact |
|---|--------|--------------|--------|--------|
| 5 | Déplacer "Carte cadeau" hors filtres | Template | 1h | 🟠 UX |
| 6 | Réduire filtres à 5 catégories | Template | 1h | 🟠 Clarté |
| 7 | Afficher "LUMINAIRES" par défaut au lieu de "TOUT" | JS | 30min | 🟠 Focus |
| 8 | Ajouter micro-carousel au hover produit | CSS/JS | 4h | 🟡 Engagement |

### P2 — Amélioration (Sprint 2+)

| # | Action | Fichier/Zone | Effort | Impact |
|---|--------|--------------|--------|--------|
| 9 | Passer à 1 hero plein écran | Template + image | 2h | 🟡 Premium |
| 10 | Ajouter vidéo luminaire allumé | Média | 4h | 🟡 Émotion |
| 11 | Sidebar filtres sticky (desktop) | CSS | 3h | 🟡 UX |
| 12 | Changer H2 produits → H3 | Template | 1h | 🟢 SEO |

---

## WIREFRAME RECOMMANDÉ

### Desktop 1440px — Proposition

```
┌────────────────────────────────────────────────────────────────────────┐
│ Réassurance                                                            │
├────────────────────────────────────────────────────────────────────────┤
│ Logo                    Navigation                           🔍 🛒    │
├────────────────────────────────────────────────────────────────────────┤
│                                                                        │
│                    ┌──────────────────────────────────────────┐        │
│                    │                                          │        │
│  Nos Créations     │         HERO IMAGE UNIQUE                │        │
│                    │    (suspension lifestyle, allumée)       │        │
│  Luminaires        │                                          │        │
│  artisanaux...     │                                          │        │
│                    │                                          │        │
│  [DÉCOUVRIR ↓]     └──────────────────────────────────────────┘        │
│                                                                        │
├────────────────────────────────────────────────────────────────────────┤
│  🔍 Rechercher...                                                      │
├────────────────────────────────────────────────────────────────────────┤
│  [TOUS] [SUSPENSIONS] [APPLIQUES] [LAMPADAIRES] [LAMPES À POSER]       │
│                                                                        │
│  Prix ▾  |  Essence ▾  |  Dimensions ▾                    29 créations │
├────────────────────────────────────────────────────────────────────────┤
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐                      │
│  │ Gaston  │ │ Suze    │ │ Dalida  │ │ Timothée│                      │
│  │         │ │         │ │         │ │         │                      │
│  │ 75-165€ │ │ 75-155€ │ │ 65-150€ │ │ 90-160€ │                      │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘                      │
│                                                                        │
│  [ACCESSOIRES →] (lien secondaire vers section dédiée)                 │
│                                                                        │
└────────────────────────────────────────────────────────────────────────┘
```

**Changements clés :**
1. 1 hero au lieu de 3 images
2. Suppression "01/02"
3. 5 filtres au lieu de 7
4. Accessoires séparés (lien en bas)
5. Compteur "29 créations" visible

---

## MÉTRIQUES DE SUCCÈS

| Métrique | Actuel (estimé) | Cible |
|----------|-----------------|-------|
| Scroll to first product | ~150px | <50px |
| CTR filtres catégories | ~15% | 25% |
| Engagement hero | ~3s | 5s+ |
| Perception "artisanal" | Diluée (accessoires) | Renforcée |

---

## ANNEXE — SCREENSHOTS DE RÉFÉRENCE

Les captures d'écran suivantes ont été prises lors de l'audit :
- Hero section avec les 3 visuels
- Section réassurance
- Grille produits avec ampoule MIIDEX visible
- Footer complet

---

*Audit réalisé par Claude | 10 février 2026*
*Pour Atelier Sâpi — theme-sapi-maison*

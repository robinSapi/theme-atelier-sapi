# AUDIT PAGE CATÉGORIE SUSPENSION — V2
## État actuel après modifications

**Date :** 10 février 2026
**Page auditée :** `/categorie-produit/suspension/`
**Site :** testlumineux.atelier-sapi.fr
**Auditeur :** Claude (Expert UX/UI e-commerce)
**Contexte :** Page en cours de modification par l'utilisateur

---

## RÉSUMÉ EXÉCUTIF

| Aspect | État | Évolution vs audit précédent |
|--------|------|------------------------------|
| Bandeau gris breadcrumb | ✅ Supprimé | 🟢 Corrigé |
| Numérotation "01" | ✅ Supprimée | 🟢 Corrigé |
| Tagline | ✅ Bien centrée, bien écrite | 🟢 Corrigé |
| Titre onglet | ❌ "Archives des Suspension" | 🔴 Non corrigé |
| Faute orthographe | ❌ "Tous nos suspension" | 🔴 Nouveau bug |
| Carrousel navigation | ❌ Cercles vides | 🔴 Bug CSS |
| Contenu SEO | ✅ Riche et pertinent | 🟢 Ajouté |

**Note globale :** 7/10 — Améliorations significatives mais bugs à corriger.

---

## ✅ CE QUI A ÉTÉ CORRIGÉ (vs audit précédent)

### 1. Bandeau gris du fil d'Ariane → SUPPRIMÉ ✅
```
AVANT: ▓▓▓▓ Accueil > Suspension ▓▓▓▓ (fond gris #F5F5F5)
APRÈS: Accueil / Suspension (inline, fond transparent)
```
**Verdict :** Parfait, intégration propre.

### 2. Numérotation "01" → SUPPRIMÉE ✅
Plus de numérotation serif inutile. Le header est maintenant épuré.

### 3. Tagline → AMÉLIORÉE ✅
```
AVANT: "Retrouvez ici tous nos lustres, prêts à faire rayonner votre déco intérieure !"
       (décalée à gauche, générique)

APRÈS: "Des luminaires suspendus en bois qui transforment votre plafond en
        œuvre d'art. Du lustre design au modèle artisanal, trouvez la
        suspension qui raconte votre histoire."
       (centrée, émotionnelle, pertinente)
```
**Verdict :** Excellent copywriting.

### 4. Contenu SEO → ENRICHI ✅
Nouvelles sections ajoutées :
- **"La lumière qui vous ressemble"** (Square Peg, émotionnel)
- **"Pourquoi choisir une suspension en bois ?"** (argumentaire)
- **"Notre promesse"** (rassurance)
- **"Où installer votre suspension ?"** (guide d'usage avec checkmarks)

**Verdict :** Très bon pour le SEO et l'aide à la décision.

---

## ❌ BUGS À CORRIGER

### 🔴 BUG 1 — Titre onglet incorrect

**Observation :**
```
Actuel:  "Archives des Suspension - testLumineuxAtelierSapi"
Attendu: "Suspensions artisanales en bois | Atelier Sâpi"
```

**Problèmes :**
- "Archives des" → vocabulaire WordPress par défaut, pas premium
- "Suspension" au singulier → devrait être pluriel
- "testLumineuxAtelierSapi" → nom technique, pas commercial

**Impact :** SEO + perception marque dans les résultats Google.

**Correction :**
```php
// Dans functions.php ou via plugin SEO
add_filter('woocommerce_page_title', function($title) {
    if (is_product_category('suspension')) {
        return 'Nos suspensions';
    }
    return $title;
});

// Ou via Yoast/RankMath : définir un titre SEO custom
```

---

### 🔴 BUG 2 — Faute d'orthographe "Tous nos suspension"

**Observation :**
```
Actuel:  "Tous nos suspension"
Attendu: "Toutes nos suspensions"
```

**Localisation :** H2 de la section grille produits.

**Impact :** Crédibilité, perception "site pas fini".

**Correction :** Modifier le texte dans le template ou l'éditeur.

---

### 🔴 BUG 3 — Carrousel "Coups de cœur" cassé

**Observations visuelles :**

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Coups de cœur                                    │
│                 Nos créations les plus appréciées                   │
│                                                                     │
│  ○     [OLIVIA]  [GASTON]  [ALBAN]                            ○    │
│  ↑                                                             ↑    │
│  CERCLE VIDE                                           CERCLE VIDE  │
│  (pas de flèche)                                    (pas de flèche) │
│                                                                     │
│      [SUZE] ← Décalée en dessous, seule                            │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

**Problèmes identifiés :**

| # | Problème | Sévérité |
|---|----------|----------|
| 1 | Boutons navigation = cercles vides (pas d'icône flèche) | 🔴 |
| 2 | 4ème produit (Suze) décalé en dessous au lieu d'être caché | 🔴 |
| 3 | Pas de dots de pagination | 🟠 |
| 4 | Comportement carrousel non visible (pas de scroll horizontal) | 🟠 |

**Diagnostic probable :**
- CSS des flèches manquant (icon font non chargée ou classe incorrecte)
- Carrousel mal configuré (items visibles > items disponibles)
- Possible conflit JS/CSS avec le slider

**Correction CSS pour les flèches :**
```css
/* Si utilisation de Font Awesome ou icon font */
.carousel-nav-prev::before {
  content: '\f053'; /* fa-chevron-left */
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
}

.carousel-nav-next::before {
  content: '\f054'; /* fa-chevron-right */
  font-family: 'Font Awesome 6 Free';
  font-weight: 900;
}

/* Alternative avec SVG inline */
.carousel-nav-prev,
.carousel-nav-next {
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23E35B24'%3E%3Cpath d='M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: center;
  background-size: 24px;
}

.carousel-nav-next {
  transform: rotate(180deg);
}
```

---

### 🟠 BUG 4 — Incohérence image produit (Timothée)

**Observation :**
Dans la grille, tous les produits ont un fond blanc SAUF "Timothée l'Araignée" qui a une image lifestyle (mur orange).

**Impact :** Rupture visuelle dans la grille.

**Recommandation :**
- Option A : Mettre fond blanc pour Timothée (cohérence)
- Option B : Mettre lifestyle pour TOUS les produits (choix assumé)
- Option C : Garder lifestyle uniquement pour le produit "featured" (badge)

---

## 📊 ANALYSE STRUCTURE

### Hiérarchie HTML actuelle

```
H3 — "Votre panier" (mini-cart, caché)
H1 — "Suspension" ✅
H2 — "Coups de cœur"
  H3 — Noms produits carrousel
H2 — "Tous nos suspension" ❌ FAUTE
  H2 — Noms produits grille (devrait être H3)
```

**Problèmes SEO :**
1. Faute dans H2 principal
2. Noms de produits en H2 au lieu de H3 (dilution SEO)

---

## 📐 STRUCTURE DE LA PAGE

```
┌────────────────────────────────────────────────────────────────────┐
│ Header + Réassurance                                                │
├────────────────────────────────────────────────────────────────────┤
│ Accueil / Suspension (breadcrumb inline)                           │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│                         Suspension                                  │
│                                                                     │
│   Des luminaires suspendus en bois qui transforment votre          │
│   plafond en œuvre d'art...                                        │
│                                                                     │
├────────────────────────────────────────────────────────────────────┤
│                     Coups de cœur                        🔴 CASSÉ   │
│              ○ [Produit] [Produit] [Produit] ○                     │
│                     [Produit décalé]                               │
├────────────────────────────────────────────────────────────────────┤
│ Tous nos suspension ← 🔴 FAUTE              11 produits            │
├────────────────────────────────────────────────────────────────────┤
│ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐                                   │
│ │     │ │     │ │     │ │     │  Grille 4 colonnes                │
│ └─────┘ └─────┘ └─────┘ └─────┘                                   │
│ ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐                                   │
│ │     │ │     │ │     │ │ 🟠  │ ← Timothée lifestyle              │
│ └─────┘ └─────┘ └─────┘ └─────┘                                   │
├────────────────────────────────────────────────────────────────────┤
│           La lumière qui vous ressemble (Square Peg)               │
│                    Texte émotionnel...                             │
├────────────────────────────────────────────────────────────────────┤
│ POURQUOI CHOISIR UNE       │        NOTRE PROMESSE                 │
│ SUSPENSION EN BOIS ?       │        Chaque suspension...           │
├────────────────────────────────────────────────────────────────────┤
│              OÙ INSTALLER VOTRE SUSPENSION ?                       │
│ ✓ Au-dessus de la table à manger                                   │
│ ✓ Dans le salon                                                    │
│ ✓ Dans la chambre                                                  │
├────────────────────────────────────────────────────────────────────┤
│ 🔍 Rechercher un luminaire...                                      │
│ [PRIX ▾] [ESSENCE ▾] [DIMENSIONS ▾]                                │
├────────────────────────────────────────────────────────────────────┤
│ Footer                                                             │
└────────────────────────────────────────────────────────────────────┘
```

---

## ✅ RECOMMANDATIONS PRIORISÉES

### P0 — Critiques (immédiat)

| # | Action | Localisation | Effort |
|---|--------|--------------|--------|
| 1 | Corriger "Tous nos suspension" → "Toutes nos suspensions" | Template/Éditeur | ⚡ 2min |
| 2 | Ajouter icônes flèches au carrousel | CSS | ⚡ 15min |
| 3 | Fixer le 4ème produit carrousel (masquer ou aligner) | CSS/JS | 🔶 30min |
| 4 | Corriger titre onglet SEO | Yoast/functions.php | ⚡ 5min |

### P1 — Important (Sprint 1)

| # | Action | Localisation | Effort |
|---|--------|--------------|--------|
| 5 | Harmoniser images grille (fond blanc ou lifestyle) | Médias | 🔶 1h |
| 6 | Changer H2 produits → H3 | Template | ⚡ 15min |
| 7 | Ajouter dots pagination au carrousel | CSS/JS | 🔶 30min |
| 8 | Tester carrousel mobile | Test | 🔶 30min |

### P2 — Amélioration (Sprint 2+)

| # | Action | Localisation | Effort |
|---|--------|--------------|--------|
| 9 | Ajouter hero visuel en haut | Template + image | 🔶 2h |
| 10 | Animation hover sur carrousel | CSS | 🔶 1h |
| 11 | Filtres sticky au scroll | CSS/JS | 🔶 2h |

---

## 🔧 CORRECTIONS CSS RAPIDES

### Carrousel — Flèches navigation

```css
/* Style des boutons carrousel */
.swiper-button-prev,
.swiper-button-next,
.carousel-nav-prev,
.carousel-nav-next {
  width: 48px;
  height: 48px;
  border: 2px solid var(--sapi-orange, #E35B24);
  border-radius: 50%;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.swiper-button-prev:hover,
.swiper-button-next:hover {
  background: var(--sapi-orange);
}

/* Icône flèche avec pseudo-element */
.swiper-button-prev::after,
.swiper-button-next::after {
  font-size: 18px;
  color: var(--sapi-orange);
  font-weight: bold;
}

.swiper-button-prev:hover::after,
.swiper-button-next:hover::after {
  color: white;
}

/* Si pas de font icon, utiliser chevron CSS */
.swiper-button-prev::after {
  content: '‹';
}

.swiper-button-next::after {
  content: '›';
}
```

### Carrousel — Masquer overflow

```css
/* Masquer le 4ème produit qui dépasse */
.coups-de-coeur-wrapper {
  overflow: hidden;
}

.coups-de-coeur-slider {
  display: flex;
  gap: 24px;
}

.coups-de-coeur-slider .product-card:nth-child(n+4) {
  /* Masquer à partir du 4ème si pas de scroll */
  display: none;
}

/* OU si c'est un vrai slider Swiper */
.swiper-wrapper {
  overflow: hidden;
}
```

---

## 📱 À TESTER EN MOBILE

Points critiques à vérifier :
1. Carrousel "Coups de cœur" : swipe fonctionnel ?
2. Grille : passage à 2 puis 1 colonne ?
3. Filtres : accessibles sans scroll horizontal ?
4. Images : lazy loading correct ?

---

## RÉCAPITULATIF VISUEL

### Comparaison AVANT/APRÈS

| Élément | Avant (audit initial) | Maintenant |
|---------|----------------------|------------|
| Breadcrumb | Bandeau gris pleine largeur | ✅ Inline transparent |
| Numérotation | "01" en serif | ✅ Supprimée |
| Tagline | Décalée, générique | ✅ Centrée, émotionnelle |
| Titre onglet | "Archives des Suspension" | ❌ Pas changé |
| Carrousel | N/A | ❌ Cassé (cercles vides) |
| Orthographe | OK | ❌ "Tous nos suspension" |
| Contenu SEO | Minimal | ✅ Riche |

---

*Audit réalisé par Claude | 10 février 2026*
*Pour Atelier Sâpi — theme-sapi-maison*

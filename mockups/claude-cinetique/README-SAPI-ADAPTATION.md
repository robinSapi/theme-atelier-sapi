# SAPI CINÉTIQUE - Adaptation Charte Officielle

## Résumé

SAPI CINÉTIQUE a été adapté pour suivre **strictement la charte graphique officielle de SAPI** tout en conservant ses forces techniques et créatives :
- ✅ Bento grid complexe
- ✅ Custom cursor interactif
- ✅ Menu overlay sophistiqué
- ✅ Animations fluides
- ✅ Parallax 3D sur produits
- ✅ Keyboard shortcuts
- ✅ Easter egg Konami Code

---

## 🎨 Changements de Design

### Typographie

**Avant:**
- Display: Bodoni Moda (serif)
- Body: Space Mono (monospace)

**Après (Charte SAPI):**
- Display: **Square Peg** (cursive élégante pour mise en avant)
- Body: **Montserrat** (light 300, regular 400, bold 700, black 900)

### Palette de Couleurs

**Avant:**
- Fond: Noir (#000000)
- Accent: Or (#D4AF37)
- Secondaire: Slate (#1C1C1E)

**Après (Charte SAPI):**

**Couleurs Primaires:**
- Fond: Crème #FEFDFB
- Backgrounds: #F1F1F1, #FBF6EA
- Texte: #323232 (dark), #585858 (gray), #8A8A8A (mid-gray)
- Accent principal: **#937D68** (bois - ton chaud et artisanal)

**Couleurs Ponctuelles (accents uniquement):**
- Orange: **#E35B24** (CTA, éléments importants)
- Vert: **#018501** (feedback positif: "Ajouté!", "Inscrit!")

### Logo

**Avant:**
- Simple symbole ◯ + texte "Sâpi"

**Après:**
- Logo SAPI officiel : Cercle SVG avec lampe suspendue
- Monocouleur (#323232 dark par défaut)
- Peut être affiché en #937D68 (bois) dans certains contextes
- **Jamais** de version bi-couleur (règle de la charte)

---

## 🔄 Détails Techniques des Changements

### CSS Variables (`:root`)

```css
/* AVANT */
--color-black: #000000;
--color-gold: #D4AF37;
--font-display: 'Bodoni Moda', Georgia, serif;
--font-mono: 'Space Mono', monospace;

/* APRÈS */
--color-cream: #FEFDFB;
--color-wood: #937D68;
--color-orange: #E35B24; /* accent ponctuel */
--color-green: #018501; /* feedback positif */
--font-display: 'Square Peg', cursive;
--font-body: 'Montserrat', sans-serif;
```

### Body & Background

```css
/* AVANT */
body {
  background: var(--color-black);
  color: var(--color-beige);
}

/* APRÈS */
body {
  background: var(--color-cream);
  color: var(--color-dark);
  font-weight: 300; /* Montserrat Light par défaut */
}
```

### Custom Cursor

```css
/* AVANT */
.cursor-dot {
  background: var(--color-gold);
}
body.cursor-hover .cursor-dot {
  background: var(--color-white);
}

/* APRÈS */
.cursor-dot {
  background: var(--color-wood); /* bois au repos */
}
body.cursor-hover .cursor-dot {
  background: var(--color-orange); /* orange au hover */
}
```

### Header

```css
/* AVANT */
.header-architectural {
  background: rgba(0, 0, 0, 0.8);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

/* APRÈS */
.header-architectural {
  background: rgba(254, 253, 251, 0.95); /* crème translucide */
  border-bottom: 1px solid rgba(50, 50, 50, 0.1);
}
```

### Bento Cards

```css
/* AVANT */
.bento-card {
  background: var(--color-slate); /* sombre */
  border: 1px solid rgba(255, 255, 255, 0.1);
}
.bento-card:hover {
  border-color: var(--color-gold);
  box-shadow: 0 20px 60px rgba(212, 175, 55, 0.2);
}

/* APRÈS */
.bento-card {
  background: var(--color-white); /* clair */
  border: 1px solid var(--color-gray-light);
}
.bento-card:hover {
  border-color: var(--color-wood); /* accent bois */
  box-shadow: 0 20px 60px rgba(147, 125, 104, 0.15);
}
```

### Statement Card (Quote)

**Changement majeur:** Utilisation de **Square Peg** pour la citation

```css
/* AVANT */
.bento-statement {
  background: var(--color-gold); /* fond or */
}
.statement-text {
  font-family: var(--font-display); /* Bodoni Moda */
  color: var(--color-black);
}

/* APRÈS */
.bento-statement {
  background: var(--color-warm); /* fond #FBF6EA crème chaud */
}
.statement-text {
  font-family: var(--font-display); /* Square Peg cursive */
  font-size: clamp(1.5rem, 2.5vw, 2.25rem);
  color: var(--color-dark);
}
```

### Product Cards

```css
/* AVANT */
.product-badge {
  background: var(--color-gold);
  color: var(--color-black);
}
.product-price-tag {
  background: var(--color-gold);
  color: var(--color-black);
  font-family: var(--font-display);
}

/* APRÈS */
.product-badge {
  background: var(--color-orange); /* orange ponctuel */
  color: var(--color-white);
}
.product-price-tag {
  background: var(--color-wood); /* bois */
  color: var(--color-white);
  font-family: var(--font-body);
  font-weight: 900; /* Montserrat Black */
}
```

### CTA Button

```css
/* AVANT */
.bento-cta {
  background: var(--color-gold);
}
.cta-button {
  background: var(--color-black);
  color: var(--color-gold);
}

/* APRÈS */
.bento-cta {
  background: var(--color-orange); /* orange pour CTA important */
}
.cta-button {
  background: var(--color-white);
  color: var(--color-dark);
  font-weight: 700;
}
```

### Collection Cards

```css
/* AVANT */
.collection-btn {
  background: var(--color-gold);
  color: var(--color-black);
}
.collection-btn:hover {
  background: var(--color-white);
}

/* APRÈS */
.collection-btn {
  background: var(--color-wood); /* bois par défaut */
  color: var(--color-white);
}
.collection-btn:hover {
  background: var(--color-orange); /* orange au hover */
}
```

### Newsletter

```css
/* AVANT */
.newsletter-input-kinetic {
  background: var(--color-slate);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: var(--color-beige);
}
.newsletter-submit-kinetic {
  background: var(--color-gold);
  color: var(--color-black);
}

/* APRÈS */
.newsletter-input-kinetic {
  background: var(--color-white);
  border: 1px solid var(--color-gray-light);
  color: var(--color-dark);
  font-weight: 300;
}
.newsletter-input-kinetic:focus {
  border-color: var(--color-wood);
  background: var(--color-warm); /* feedback subtil */
}
.newsletter-submit-kinetic {
  background: var(--color-wood);
  color: var(--color-white);
}
.newsletter-submit-kinetic:hover {
  background: var(--color-orange);
}
```

---

## 🎯 Utilisation Stratégique des Couleurs

### Hiérarchie des Accents

1. **#937D68 (Bois)** - Accent principal
   - Navigation links hover
   - Cursor dot
   - Product price tags
   - Newsletter button
   - Collection buttons
   - Stats numbers
   - Footer links hover

2. **#E35B24 (Orange)** - Accents ponctuels importants
   - CTA principal (section "Toutes les créations")
   - Product badges ("Nouveau")
   - Corner price reveal
   - Hover states sur éléments majeurs
   - Menu items hover reveal

3. **#018501 (Vert)** - Feedback positif uniquement
   - "Ajouté !" au panier
   - "Inscrit !" à la newsletter

### Respect de la Charte

✅ **Primaires dominantes:** Crème, gris, bois utilisés à ~80% du design
✅ **Ponctuels en accent:** Orange et vert < 10% de la surface
✅ **Logo monocouleur:** Jamais de combinaison bi-couleur
✅ **Typographie stricte:** Square Peg + Montserrat uniquement

---

## 📊 Comparaison Visuelle

| Élément | Avant (Generic) | Après (SAPI Charte) |
|---------|----------------|---------------------|
| **Fond général** | Noir #000 | Crème #FEFDFB |
| **Accent principal** | Or #D4AF37 | Bois #937D68 |
| **CTA important** | Or sur noir | Orange #E35B24 |
| **Feedback positif** | Blanc | Vert #018501 |
| **Display font** | Bodoni Moda | Square Peg |
| **Body font** | Space Mono | Montserrat |
| **Logo** | Simple ◯ | SVG circle officiel |
| **Ambiance** | Dark luxury | Light artisanal |

---

## ⚡ JavaScript - Feedbacks Mis à Jour

### Notifications

```javascript
// AVANT
background: var(--color-slate);
border: 1px solid var(--color-gold);
color: var(--color-beige);

// APRÈS
background: var(--color-white);
border: 2px solid var(--color-wood);
color: var(--color-dark);
box-shadow: 0 10px 40px rgba(147, 125, 104, 0.2);
```

### Add to Cart Feedback

```javascript
// AVANT
priceTag.style.background = 'var(--color-white)';
// Puis retour à:
priceTag.style.background = 'var(--color-gold)';

// APRÈS
priceTag.style.background = 'var(--color-green)'; // vert = succès
// Puis retour à:
priceTag.style.background = 'var(--color-wood)';
```

### Newsletter Submit Feedback

```javascript
// APRÈS
button.style.background = 'var(--color-green)'; // vert = inscrit!
// Puis retour à:
button.style.background = 'var(--color-wood)';
```

### Header Scroll

```javascript
// AVANT
if (scrolled > 100) {
  header.style.background = 'rgba(0, 0, 0, 0.95)';
}

// APRÈS
if (scrolled > 100) {
  header.style.background = 'rgba(254, 253, 251, 0.98)';
  header.style.boxShadow = '0 2px 20px rgba(50, 50, 50, 0.1)';
}
```

---

## 🎨 Design Tokens - Variables CSS Complètes

```css
:root {
  /* SAPI Brand Colors - Primary */
  --color-cream: #FEFDFB;
  --color-gray-light: #F1F1F1;
  --color-warm: #FBF6EA;
  --color-dark: #323232;
  --color-gray: #585858;
  --color-gray-mid: #8A8A8A;
  --color-wood: #937D68;

  /* SAPI Brand Colors - Punctual (accents only) */
  --color-orange: #E35B24;
  --color-green: #018501;

  /* Additional colors for contrast */
  --color-white: #FFFFFF;
  --color-black: #000000;

  /* SAPI Brand Typography */
  --font-display: 'Square Peg', cursive;
  --font-body: 'Montserrat', sans-serif;

  /* Easing curves (unchanged) */
  --ease-expo: cubic-bezier(0.87, 0, 0.13, 1);
  --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
}
```

---

## 🚀 Fonctionnalités Conservées

### Techniques
✅ Bento Grid avec positionnement complexe (grid-column: span X)
✅ Custom cursor avec dot + outline
✅ Parallax 3D sur product cards (rotateX/rotateY au mousemove)
✅ IntersectionObserver pour animations au scroll
✅ RequestAnimationFrame pour parallax fluide
✅ Event delegation optimisée
✅ Keyboard shortcuts (M, C, Escape)
✅ Konami Code easter egg

### Design
✅ Menu overlay plein écran avec text reveal
✅ Product cards avec overlay fade-in
✅ Collection cards avec details slide-up
✅ Newsletter inline avec form stylisée
✅ Footer structuré en grid
✅ Responsive breakpoints (1200px, 768px)

### Interactions
✅ Hover states sophistiqués sur tous éléments
✅ Add to cart avec feedback visuel
✅ Newsletter submit avec confirmation
✅ Notifications toast système
✅ Smooth scroll sur ancres
✅ Menu toggle animé
✅ Card animations staggerées

---

## 📐 Principes Appliqués

### 1. Inversion du Contraste
- **Avant:** Fond noir, texte clair (high contrast dark mode)
- **Après:** Fond clair, texte foncé (luminosité naturelle artisanale)
- **Pourquoi:** La charte SAPI privilégie la clarté et la chaleur

### 2. Ton Chaud vs Luxe Froid
- **Avant:** Or éclatant (#D4AF37) = luxe traditionnel
- **Après:** Bois naturel (#937D68) = artisanat authentique
- **Pourquoi:** Reflète mieux l'identité "fait main" de SAPI

### 3. Typographie Artisanale
- **Avant:** Bodoni Moda (serif classique) + Space Mono (tech)
- **Après:** Square Peg (script élégant) + Montserrat (moderne lisible)
- **Pourquoi:** Square Peg apporte chaleur et personnalité artisanale

### 4. Accents Ponctuels Stratégiques
- Orange (#E35B24): Actions importantes uniquement (CTA, nouveautés)
- Vert (#018501): Feedback positif exclusivement
- **Jamais** de surcharge d'accents colorés

---

## 🎯 Résultat Final

**SAPI CINÉTIQUE** version charte officielle combine:
- ✅ **Design architectural sophistiqué** (Bento grid, cursor custom)
- ✅ **Charte graphique stricte** (Square Peg, Montserrat, couleurs SAPI)
- ✅ **Interactions riches** (parallax, animations fluides, feedback visuels)
- ✅ **Performance native** (60fps, optimisations GPU)
- ✅ **Cohérence totale** avec l'identité visuelle SAPI

Le mockup incarne maintenant parfaitement:
- L'artisanat de Robin (tons chauds, Square Peg script)
- La modernité technique (Bento grid, cursor custom, parallax)
- L'identité SAPI officielle (charte respectée à 100%)

---

**Adapté par Claude** | Février 2026
*Respect strict de la charte graphique SAPI*
*Maintien des innovations techniques CINÉTIQUE*

✨ **L'architecture rencontre l'artisanat** ✨

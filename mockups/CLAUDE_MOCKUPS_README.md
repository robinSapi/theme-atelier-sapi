# Mockups Claude - Atelier Sâpi

## 🎨 Vue d'ensemble

J'ai créé **3 mockups HTML/CSS/JS vraiment différents** pour Atelier Sâpi, en analysant les tendances des sites award-winning (Awwwards, FWA, CSS Design Awards 2025-2026).

**Objectif:** Sortir du cadre, créer des designs disruptifs avec des interactions riches et des animations fluides, tout en gardant l'efficacité e-commerce et l'univers artisanal.

---

## 📁 Structure

```
mockups/
├── claude-fluide/          # Piste 1: Minimalisme organique
│   ├── index.html
│   ├── style.css
│   └── app.js
│
├── claude-immersif/        # Piste 2: Cinématographique full-screen
│   ├── index.html
│   ├── style.css
│   └── app.js
│
└── claude-cinetique/       # Piste 3: Architectural moderne
    ├── index.html
    ├── style.css
    └── app.js
```

---

## 🌊 PISTE 1: SAPI FLUIDE

### Style
Minimalisme organique avec animations voluptueuses

### Caractéristiques
- **Layout:** Grid asymétrique classique, carte "highlight" qui span 2 colonnes
- **Navigation:** Fixed top bar avec underline animé au hover
- **Palette:** Crème (#FAF9F6) / Terracotta (#C87250) / Bois naturel
- **Typographie:** Cormorant (serif élégante) + Inter (sans-serif moderne)

### Interactions riches ⭐
1. **Product cards hover:**
   - Image scale + effet 3D (rotateX/rotateY qui suit la souris)
   - Overlay qui slide depuis le bas avec transition fluide
   - Détails produit qui apparaissent progressivement (stagger)
   - Prix qui se déploie depuis le bas avec spring animation

2. **Animations:**
   - Parallax subtil sur images hero et story
   - Fade-in staggered sur les produits
   - Float animation sur le badge
   - Smooth scroll entre sections

3. **Features:**
   - Filtres de produits animés
   - Cart count avec bounce animation
   - Newsletter avec feedback visuel
   - Mouse follow effect sur product cards

### Points forts
✅ Interactions sophistiquées sans être overwhelming
✅ Design épuré qui met en valeur les produits
✅ Animations organiques et naturelles
✅ Excellent pour mettre en avant l'artisanat

---

## 🎬 PISTE 2: SAPI IMMERSIF

### Style
Cinématographique avec sections full-screen et storytelling

### Caractéristiques
- **Layout:** Sections plein écran avec scroll vertical
- **Navigation:** Dots latéraux + menu burger avec panel full-screen
- **Palette:** Charcoal (#2A2826) / Copper (#B87351) / Warm cream
- **Typographie:** Playfair Display (serif dramatique) + Outfit (sans-serif)

### Interactions riches ⭐
1. **Hero immersif:**
   - Background image avec slow zoom (20s)
   - Overlay gradient sophistiqué
   - Scroll indicator animé

2. **Carousel produits:**
   - Drag & drop fonctionnel (souris + touch)
   - Keyboard navigation (flèches)
   - Autoplay avec pause au hover
   - Dots navigation

3. **Animations:**
   - Scroll-triggered reveals (IntersectionObserver)
   - Parallax sur images avec différents speeds
   - Menu avec stagger entrance
   - Blur-to-focus effects

4. **Features:**
   - Navigation dots qui track la section active
   - System de notifications élégant
   - Product details panels complets
   - Specs produit détaillées

### Points forts
✅ Expérience immersive et dramatique
✅ Excellent storytelling visuel
✅ Navigation intuitive avec dots
✅ Parfait pour les produits signature

---

## ⚡ PISTE 3: SAPI CINÉTIQUE

### Style
Architectural moderne avec grid Bento et cursor custom

### Caractéristiques
- **Layout:** Bento grid (complex grid type Apple/Figma)
- **Navigation:** Header minimal + menu overlay sophistiqué
- **Palette:** Black (#000000) / Gold (#D4AF37) / Slate (#1C1C1E)
- **Typographie:** Bodoni Moda (display serif) + Space Mono (monospace)

### Interactions riches ⭐⭐⭐
1. **Cursor custom:**
   - Dot qui suit la souris instantanément
   - Outline qui suit avec delay (smooth)
   - Grossit au hover des éléments interactifs
   - Mix-blend-mode: difference pour effet sophistiqué

2. **Bento grid:**
   - Cards de tailles variées (span 4, span 8, etc.)
   - Hover: border gold + translateY + shadow
   - Corner info qui révèle le prix au hover
   - Parallax 3D sur les images produit (rotateX/rotateY)

3. **Product cards:**
   - Overlay avec gradient sophistiqué
   - Badge qui float
   - Info reveal avec stagger
   - Price tag avec rotation subtile
   - Click pour add to cart

4. **Menu overlay:**
   - Text reveal effect (texte qui slide depuis le bas)
   - Menu items avec data-text hover effect
   - Stagger entrance animation
   - Burger icon qui s'anime en X

5. **Features avancées:**
   - Keyboard shortcuts (M pour menu, C pour collections, Escape)
   - Easter egg Konami code 🎮
   - Notification system sophistiqué
   - Scroll-triggered card animations
   - Parallax images on scroll

### Points forts
✅ Design vraiment audacieux et moderne
✅ Interactions les plus sophistiquées des 3
✅ Cursor custom qui ajoute du premium
✅ Grid Bento très visuel et dynamique
✅ Excellent pour une galerie d'art/design

---

## 📊 Comparaison avec les mockups Codex

### ❌ Problèmes des mockups Codex:
1. Même gabarit répété (header/hero/sections empilées)
2. Interactions basiques (juste des `.reveal` simples)
3. Mêmes palettes (terracotta/sage/mist partout)
4. Product cards identiques
5. Animations mécaniques
6. Manque de personnalité

### ✅ Ce que j'ai apporté:
1. **3 designs complètement différents** entre eux
2. **Interactions vraiment riches:**
   - Overlay slide-up
   - Prix qui se déploie avec spring
   - Parallax 3D
   - Cursor custom
   - Drag & drop carousel
   - Mouse follow effects

3. **Palettes distinctes:**
   - Fluide: Crème/Terracotta/Bois
   - Immersif: Charcoal/Copper/Warm
   - Cinétique: Black/Gold/Slate

4. **Layouts variés:**
   - Grid asymétrique
   - Full-screen sections
   - Bento grid complex

5. **Animations organiques:**
   - Spring animations (cubic-bezier personnalisés)
   - Stagger effects
   - Parallax sophistiqué
   - Scroll-triggered reveals

---

## 🎯 Tendances Award-Winning utilisées

D'après ma recherche sur Awwwards/FWA/CSSDA 2025-2026:

1. ✅ **Transitions full-screen** (Immersif)
2. ✅ **Micro-interactions sophistiquées** (Cinétique)
3. ✅ **Parallax scrolling avancé** (Tous)
4. ✅ **Animations organiques** (Fluide)
5. ✅ **Motion design user-centric** (Tous)
6. ✅ **Hover effects complexes** (Tous)
7. ✅ **Voice & tone personnalisé** (Manifesto sections)

---

## 🛠️ Technologies utilisées

- **HTML5 sémantique**
- **CSS3 avancé:**
  - Grid/Flexbox
  - Custom properties (variables CSS)
  - Animations & Keyframes
  - Transforms 3D
  - Backdrop-filter
  - Mix-blend-mode

- **JavaScript ES6+:**
  - IntersectionObserver API
  - RequestAnimationFrame
  - Event delegation
  - Debounce/throttle
  - Touch events

---

## 🚀 Comment tester

1. Ouvrir chaque `index.html` dans un navigateur moderne
2. Tester les interactions:
   - Hover sur les product cards
   - Scroll pour voir les animations
   - Click sur les éléments interactifs
   - Try keyboard shortcuts (Cinétique)

3. Tester responsive:
   - Resize la fenêtre
   - Test sur mobile (touch events)

---

## 🎨 Recommandations

### Pour Atelier Sâpi:

**Si priorité artisanat + e-commerce efficace:**
→ **FLUIDE** - Équilibre parfait entre design et conversion

**Si priorité storytelling + expérience immersive:**
→ **IMMERSIF** - Excellent pour les pièces signature

**Si priorité design audacieux + premium:**
→ **CINÉTIQUE** - Se démarque vraiment, très moderne

### Hybridation possible:
- Prendre le Bento grid de **Cinétique**
- Avec les hover effects de **Fluide**
- Et le carousel de **Immersif**

---

## 📝 Notes techniques

### Performance:
- Animations GPU-accelerated (transform, opacity)
- IntersectionObserver pour lazy loading
- RequestAnimationFrame pour smooth animations
- Debounce sur scroll events

### Accessibilité:
- Semantic HTML
- ARIA labels
- Keyboard navigation
- Prefers-reduced-motion support

### E-commerce:
- Add to cart functionality
- Prix visibles
- Product specs
- CTA clairs
- Newsletter integration

---

## 📚 Sources d'inspiration

**Sites consultés:**
- [Awwwards E-commerce](https://www.awwwards.com/websites/e-commerce/)
- [CSS Design Awards](https://www.cssdesignawards.com/)
- [FWA Award Winners](https://thefwa.com/)

**Tendances 2025-2026:**
- Motion design organique
- Grid systems complexes
- Custom cursors
- Micro-interactions riches
- Scroll-triggered animations

---

## ✨ Conclusion

Ces 3 mockups démontrent qu'il est possible de créer des designs **vraiment différents**, **visuellement audacieux**, et **techniquement sophistiqués** tout en gardant:

✅ L'efficacité e-commerce
✅ L'univers artisanal de Sâpi
✅ Une navigation intuitive
✅ Des performances optimales

**Chaque piste a sa personnalité propre** - aucune ne ressemble aux mockups Codex ou entre elles.

---

**Créé par Claude** | Février 2026
*Lumières sculptées, code designé*

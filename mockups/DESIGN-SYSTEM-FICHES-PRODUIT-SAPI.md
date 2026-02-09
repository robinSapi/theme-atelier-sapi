# DESIGN SYSTEM — FICHES PRODUIT ATELIER SAPI
## Un design émotionnel, tactile et convertissant

**Date :** 7 février 2026  
**Pour :** Atelier Sapi (Robin)  
**Objectif :** Créer des fiches produit qui donnent ENVIE de toucher le bois, de voir la lumière, d'ACHETER

---

## PHILOSOPHIE DESIGN

> **"On n'achète pas un luminaire. On achète la chaleur d'un soir d'hiver, 
> le reflet doré sur un mur de pierre, le geste de l'artisan."**

Le design doit évoquer :
- **Chaleur** → Tons boisés, lumière dorée, ombres douces
- **Artisanat** → Textures naturelles, imperfections assumées, authenticité
- **Poésie** → Espaces généreux, rythme lent, typographie expressive
- **Confiance** → Clarté, lisibilité, professionnalisme discret

---

## 1. PALETTE ENRICHIE

### Couleurs principales (évolution de la charte)

| Nom | Hex | Usage | Émotion |
|-----|-----|-------|---------|
| **Crème Papier** | `#FEFDFB` | Fond principal | Douceur, lumière naturelle |
| **Crème Chaud** | `#FAF6F0` | Fonds secondaires, cards | Chaleur enveloppante |
| **Ivoire Doux** | `#F5EDE4` | Hover states, zones actives | Tactile, papier artisanal |
| **Bois Doré** | `#937D68` | Titres, accents primaires | Noblesse du bois |
| **Bois Profond** | `#6B5A4A` | Texte principal | Ancrage, lisibilité |
| **Bois Sombre** | `#4A3F35` | Titres importants | Élégance, contraste |
| **Orange Sapi** | `#E35B24` | CTAs, badges, prix | Énergie, action |
| **Orange Hover** | `#C94D1E` | CTA hover | Profondeur, clic satisfaisant |
| **Vert Confiance** | `#018501` | Validations, rassurance | Nature, authenticité |
| **Vert Doux** | `#2D7D32` | Icônes, badges stock | Calme, positif |

### Couleurs d'ambiance (NOUVEAU)

| Nom | Hex | Usage |
|-----|-----|-------|
| **Lumière Dorée** | `#FFF8E7` | Overlay sur images lifestyle |
| **Ombre Chaude** | `rgba(74, 63, 53, 0.08)` | Ombres portées |
| **Voile Bois** | `rgba(147, 125, 104, 0.05)` | Fond texturé subtil |
| **Noir Doux** | `#2C2620` | Texte sur fond clair (pas de noir pur) |

### Gradients

```css
/* Fond premium pour sections hero */
--gradient-hero: linear-gradient(
  180deg, 
  #FEFDFB 0%, 
  #FAF6F0 50%, 
  #F5EDE4 100%
);

/* Overlay lumière dorée sur images */
--gradient-golden: linear-gradient(
  135deg,
  rgba(255, 248, 231, 0.3) 0%,
  rgba(254, 253, 251, 0) 60%
);

/* CTA avec profondeur */
--gradient-cta: linear-gradient(
  180deg,
  #E35B24 0%,
  #D14F1C 100%
);

/* Ombre intérieure pour inputs */
--gradient-inset: linear-gradient(
  180deg,
  rgba(74, 63, 53, 0.04) 0%,
  rgba(74, 63, 53, 0) 30%
);
```

### Textures (NOUVEAU)

```css
/* Fond papier artisanal subtil */
.texture-paper {
  background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%' height='100%' filter='url(%23noise)'/%3E%3C/svg%3E");
  background-blend-mode: soft-light;
  opacity: 0.03;
}

/* Grain photo vintage */
.texture-grain {
  background-image: url('/assets/textures/grain-soft.png');
  mix-blend-mode: multiply;
  opacity: 0.15;
}
```

---

## 2. TYPOGRAPHIE EXPRESSIVE

### Échelle typographique

| Niveau | Font | Taille Desktop | Taille Mobile | Weight | Line-height | Letter-spacing |
|--------|------|----------------|---------------|--------|-------------|----------------|
| **Display XL** | Square Peg | 72px | 48px | 400 | 0.9 | -0.02em |
| **Display** | Square Peg | 56px | 36px | 400 | 0.95 | -0.01em |
| **H1** | Montserrat | 40px | 28px | 600 | 1.1 | -0.02em |
| **H2** | Montserrat | 32px | 24px | 600 | 1.2 | -0.01em |
| **H3** | Montserrat | 24px | 20px | 600 | 1.3 | 0 |
| **H4** | Montserrat | 18px | 16px | 600 | 1.4 | 0.01em |
| **Body Large** | Montserrat | 18px | 16px | 400 | 1.6 | 0.01em |
| **Body** | Montserrat | 16px | 15px | 400 | 1.6 | 0.01em |
| **Body Small** | Montserrat | 14px | 13px | 400 | 1.5 | 0.02em |
| **Caption** | Montserrat | 12px | 11px | 500 | 1.4 | 0.05em |
| **Overline** | Montserrat | 11px | 10px | 600 | 1.2 | 0.15em |

### Styles typographiques CSS

```css
/* Titre poétique produit (Square Peg) */
.product-title-poetic {
  font-family: 'Square Peg', cursive;
  font-size: clamp(36px, 5vw, 56px);
  color: var(--bois-sombre);
  line-height: 0.95;
  letter-spacing: -0.01em;
  
  /* Ombre texte subtile pour profondeur */
  text-shadow: 0 2px 4px rgba(74, 63, 53, 0.08);
}

/* Titre produit classique */
.product-title {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(24px, 3vw, 40px);
  font-weight: 600;
  color: var(--bois-sombre);
  line-height: 1.1;
  letter-spacing: -0.02em;
}

/* Pitch émotionnel sous le titre */
.product-pitch {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(16px, 1.5vw, 20px);
  font-weight: 400;
  color: var(--bois-profond);
  line-height: 1.6;
  font-style: italic;
  opacity: 0.85;
}

/* Prix - gros et clair */
.product-price {
  font-family: 'Montserrat', sans-serif;
  font-size: clamp(28px, 3vw, 36px);
  font-weight: 700;
  color: var(--bois-sombre);
  letter-spacing: -0.02em;
  
  /* Légère ombre pour ancrage */
  text-shadow: 0 1px 2px rgba(74, 63, 53, 0.1);
}

/* Micro-copy rassurance */
.reassurance-text {
  font-family: 'Montserrat', sans-serif;
  font-size: 13px;
  font-weight: 500;
  color: var(--bois-dore);
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

/* Labels de formulaire */
.form-label {
  font-family: 'Montserrat', sans-serif;
  font-size: 11px;
  font-weight: 600;
  color: var(--bois-profond);
  letter-spacing: 0.12em;
  text-transform: uppercase;
  margin-bottom: 12px;
}
```

---

## 3. SYSTÈME D'ESPACEMENT

### Échelle (base 8px)

| Token | Valeur | Usage |
|-------|--------|-------|
| `--space-2xs` | 4px | Micro-espacements, icônes |
| `--space-xs` | 8px | Entre éléments liés |
| `--space-sm` | 12px | Padding interne compact |
| `--space-md` | 16px | Padding standard |
| `--space-lg` | 24px | Entre groupes |
| `--space-xl` | 32px | Entre sections |
| `--space-2xl` | 48px | Grandes séparations |
| `--space-3xl` | 64px | Sections majeures |
| `--space-4xl` | 96px | Hero, transitions |
| `--space-5xl` | 128px | Desktop grand écran |

### Grille

```css
/* Container principal */
.container {
  max-width: 1440px;
  margin: 0 auto;
  padding: 0 clamp(16px, 4vw, 64px);
}

/* Grille produit 50/50 */
.product-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-2xl);
  align-items: start;
}

@media (max-width: 968px) {
  .product-grid {
    grid-template-columns: 1fr;
    gap: var(--space-xl);
  }
}

/* Grille galerie vignettes */
.gallery-thumbnails {
  display: flex;
  gap: var(--space-sm);
  flex-wrap: wrap;
}
```

---

## 4. COMPOSANTS UI — DESIGN ENRICHI

### 4.1 BOUTON CTA PRINCIPAL

**Philosophie** : Chaleureux mais pas agressif. On sent l'artisanat, pas le "BUY NOW" e-commerce générique.

```css
.btn-primary {
  /* Fond avec gradient subtil */
  background: linear-gradient(180deg, #E35B24 0%, #D14F1C 100%);
  
  /* Forme généreuse */
  padding: 18px 40px;
  border-radius: 8px;
  border: none;
  
  /* Typo */
  font-family: 'Montserrat', sans-serif;
  font-size: 15px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #FEFDFB;
  
  /* Ombre chaude (pas grise !) */
  box-shadow: 
    0 4px 12px rgba(227, 91, 36, 0.25),
    0 2px 4px rgba(227, 91, 36, 0.15),
    inset 0 1px 0 rgba(255, 255, 255, 0.15);
  
  /* Transition smooth */
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  
  /* Curseur */
  cursor: pointer;
}

.btn-primary:hover {
  background: linear-gradient(180deg, #D14F1C 0%, #B8441A 100%);
  transform: translateY(-2px);
  box-shadow: 
    0 8px 20px rgba(227, 91, 36, 0.3),
    0 4px 8px rgba(227, 91, 36, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.btn-primary:active {
  transform: translateY(0);
  box-shadow: 
    0 2px 6px rgba(227, 91, 36, 0.2),
    inset 0 2px 4px rgba(0, 0, 0, 0.1);
}
```

**Variante "Acheter maintenant" (secondaire)** :

```css
.btn-secondary {
  background: transparent;
  border: 2px solid var(--bois-dore);
  color: var(--bois-profond);
  padding: 16px 38px;
  border-radius: 8px;
  
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  background: var(--bois-dore);
  color: #FEFDFB;
  border-color: var(--bois-dore);
  box-shadow: 0 4px 12px rgba(147, 125, 104, 0.2);
}
```

---

### 4.2 SWATCHES / SÉLECTEURS DE VARIATION

**Philosophie** : Chaque option doit être CLAIRE et TACTILE. On doit avoir envie de cliquer.

#### Swatch Taille (Cards avec prix)

```css
.size-option {
  /* Card généreuse */
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 20px 24px;
  min-width: 100px;
  
  /* Fond et bordure */
  background: var(--creme-chaud);
  border: 2px solid transparent;
  border-radius: 12px;
  
  /* Ombre subtile */
  box-shadow: 
    0 2px 8px rgba(74, 63, 53, 0.06),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  
  /* Transition */
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.size-option:hover {
  background: var(--ivoire-doux);
  border-color: var(--bois-dore);
  transform: translateY(-2px);
  box-shadow: 
    0 6px 16px rgba(74, 63, 53, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.9);
}

.size-option.selected {
  background: var(--bois-dore);
  border-color: var(--bois-dore);
  box-shadow: 
    0 4px 12px rgba(147, 125, 104, 0.25),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.size-option .size-value {
  font-family: 'Montserrat', sans-serif;
  font-size: 18px;
  font-weight: 600;
  color: var(--bois-sombre);
  margin-bottom: 4px;
}

.size-option.selected .size-value {
  color: #FEFDFB;
}

.size-option .size-price {
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  font-weight: 500;
  color: var(--bois-profond);
  opacity: 0.7;
}

.size-option.selected .size-price {
  color: rgba(254, 253, 251, 0.85);
}
```

#### Swatch Matériau (avec texture visuelle)

```css
.material-option {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px 20px;
  
  background: var(--creme-chaud);
  border: 2px solid transparent;
  border-radius: 12px;
  
  transition: all 0.25s ease;
  cursor: pointer;
}

.material-option:hover {
  background: var(--ivoire-doux);
  border-color: var(--bois-dore);
}

.material-option.selected {
  background: linear-gradient(135deg, var(--ivoire-doux) 0%, var(--creme-chaud) 100%);
  border-color: var(--bois-dore);
  box-shadow: 0 4px 12px rgba(147, 125, 104, 0.15);
}

/* Pastille texture bois */
.material-swatch {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  
  /* Bordure fine pour définition */
  border: 2px solid rgba(74, 63, 53, 0.15);
  
  /* Ombre intérieure pour effet "enfoncé" */
  box-shadow: 
    inset 0 2px 4px rgba(0, 0, 0, 0.1),
    0 1px 2px rgba(255, 255, 255, 0.8);
}

.material-swatch.okoume {
  background: linear-gradient(135deg, #D4B896 0%, #C4A882 50%, #B89B74 100%);
}

.material-swatch.peuplier {
  background: linear-gradient(135deg, #F5EAD6 0%, #E8DCC4 50%, #DDD0B8 100%);
}

.material-swatch.noyer {
  background: linear-gradient(135deg, #8B6F5C 0%, #7A5F4D 50%, #6B5242 100%);
}

.material-info {
  display: flex;
  flex-direction: column;
}

.material-name {
  font-family: 'Montserrat', sans-serif;
  font-size: 15px;
  font-weight: 600;
  color: var(--bois-sombre);
}

.material-description {
  font-family: 'Montserrat', sans-serif;
  font-size: 12px;
  color: var(--bois-profond);
  opacity: 0.7;
}
```

#### Swatch Couleur câble (pastilles simples)

```css
.color-options {
  display: flex;
  gap: 12px;
}

.color-option {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 3px solid transparent;
  cursor: pointer;
  
  /* Ombre pour profondeur */
  box-shadow: 
    0 2px 6px rgba(0, 0, 0, 0.1),
    inset 0 1px 2px rgba(255, 255, 255, 0.3);
  
  transition: all 0.2s ease;
}

.color-option:hover {
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.color-option.selected {
  border-color: var(--bois-dore);
  box-shadow: 
    0 0 0 2px var(--creme-papier),
    0 4px 12px rgba(147, 125, 104, 0.2);
}

.color-option.black { background: #2C2620; }
.color-option.white { background: #F5F5F5; border: 1px solid #E0E0E0; }
.color-option.red { background: #B8453A; }
.color-option.gold { background: linear-gradient(135deg, #D4A84B 0%, #C49A3D 100%); }
```

---

### 4.3 BLOC RASSURANCE

```css
.reassurance-block {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  padding: 20px 0;
  border-top: 1px solid rgba(147, 125, 104, 0.15);
  margin-top: 24px;
}

.reassurance-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.reassurance-icon {
  width: 20px;
  height: 20px;
  color: var(--bois-dore);
  opacity: 0.9;
}

.reassurance-text {
  font-family: 'Montserrat', sans-serif;
  font-size: 12px;
  font-weight: 500;
  color: var(--bois-profond);
  letter-spacing: 0.02em;
}

/* Version compacte mobile */
@media (max-width: 600px) {
  .reassurance-block {
    flex-direction: column;
    gap: 12px;
  }
}
```

---

### 4.4 GALERIE PRODUIT

```css
/* Container galerie */
.product-gallery {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

/* Image principale */
.gallery-main {
  position: relative;
  aspect-ratio: 1 / 1;
  border-radius: 16px;
  overflow: hidden;
  
  /* Fond pendant chargement */
  background: var(--creme-chaud);
  
  /* Ombre douce */
  box-shadow: 
    0 8px 32px rgba(74, 63, 53, 0.08),
    0 2px 8px rgba(74, 63, 53, 0.04);
}

.gallery-main img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  
  /* Transition pour changement d'image */
  transition: opacity 0.4s ease;
}

/* Overlay lumière dorée subtile */
.gallery-main::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    135deg,
    rgba(255, 248, 231, 0.15) 0%,
    transparent 50%
  );
  pointer-events: none;
}

/* Bouton zoom */
.gallery-zoom {
  position: absolute;
  bottom: 16px;
  right: 16px;
  width: 44px;
  height: 44px;
  
  background: rgba(254, 253, 251, 0.9);
  backdrop-filter: blur(8px);
  border: none;
  border-radius: 50%;
  
  display: flex;
  align-items: center;
  justify-content: center;
  
  color: var(--bois-profond);
  cursor: pointer;
  
  transition: all 0.2s ease;
}

.gallery-zoom:hover {
  background: #FEFDFB;
  transform: scale(1.1);
  box-shadow: 0 4px 12px rgba(74, 63, 53, 0.15);
}

/* Vignettes */
.gallery-thumbnails {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  padding: 4px;
  margin: -4px;
  
  /* Scrollbar custom */
  scrollbar-width: thin;
  scrollbar-color: var(--bois-dore) transparent;
}

.gallery-thumb {
  flex-shrink: 0;
  width: 80px;
  height: 80px;
  border-radius: 10px;
  overflow: hidden;
  
  border: 2px solid transparent;
  cursor: pointer;
  
  transition: all 0.2s ease;
}

.gallery-thumb:hover {
  border-color: var(--bois-dore);
  opacity: 0.9;
}

.gallery-thumb.active {
  border-color: var(--bois-dore);
  box-shadow: 0 2px 8px rgba(147, 125, 104, 0.2);
}

.gallery-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* Vignette vidéo */
.gallery-thumb.video::after {
  content: '▶';
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  
  background: rgba(44, 38, 32, 0.4);
  color: #FEFDFB;
  font-size: 18px;
}
```

---

### 4.5 SECTION "MOT DE ROBIN"

```css
.artisan-section {
  display: grid;
  grid-template-columns: 120px 1fr;
  gap: var(--space-xl);
  align-items: center;
  
  padding: var(--space-2xl);
  background: var(--creme-chaud);
  border-radius: 20px;
  
  /* Bordure subtile */
  border: 1px solid rgba(147, 125, 104, 0.1);
}

.artisan-photo {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  
  /* Bordure bois */
  border: 4px solid var(--bois-dore);
  box-shadow: 0 4px 16px rgba(147, 125, 104, 0.15);
}

.artisan-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.artisan-content {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.artisan-label {
  font-family: 'Montserrat', sans-serif;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--bois-dore);
}

.artisan-quote {
  font-family: 'Montserrat', sans-serif;
  font-size: 17px;
  font-style: italic;
  line-height: 1.7;
  color: var(--bois-profond);
}

.artisan-name {
  font-family: 'Square Peg', cursive;
  font-size: 28px;
  color: var(--bois-sombre);
  margin-top: 8px;
}

@media (max-width: 600px) {
  .artisan-section {
    grid-template-columns: 1fr;
    text-align: center;
  }
  
  .artisan-photo {
    margin: 0 auto;
  }
}
```

---

### 4.6 IMAGES LIFESTYLE

```css
.lifestyle-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-lg);
  margin: var(--space-3xl) 0;
}

.lifestyle-image {
  position: relative;
  aspect-ratio: 4 / 3;
  border-radius: 16px;
  overflow: hidden;
  
  /* Ombre élégante */
  box-shadow: 0 12px 40px rgba(74, 63, 53, 0.1);
}

.lifestyle-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  
  /* Légère désaturation pour cohérence */
  filter: saturate(0.95);
  
  transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.lifestyle-image:hover img {
  transform: scale(1.03);
}

/* Overlay lumière */
.lifestyle-image::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    transparent 60%,
    rgba(74, 63, 53, 0.15) 100%
  );
  pointer-events: none;
}

@media (max-width: 768px) {
  .lifestyle-section {
    grid-template-columns: 1fr;
  }
}
```

---

## 5. ANIMATIONS & MICRO-INTERACTIONS

### Easings personnalisés

```css
:root {
  /* Smooth et naturel */
  --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
  
  /* Entrée dynamique */
  --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
  
  /* Rebond subtil */
  --ease-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
  
  /* Lent et élégant */
  --ease-elegant: cubic-bezier(0.25, 0.1, 0.25, 1);
}
```

### Animation ajout panier

```css
@keyframes addToCartPulse {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
    box-shadow: 0 8px 24px rgba(227, 91, 36, 0.4);
  }
  100% {
    transform: scale(1);
  }
}

.btn-primary.adding {
  animation: addToCartPulse 0.4s var(--ease-bounce);
}

/* Feedback texte */
.btn-primary.added::after {
  content: '✓ Ajouté';
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--vert-confiance);
}
```

### Animation changement de prix

```css
@keyframes priceUpdate {
  0% {
    opacity: 0;
    transform: translateY(-8px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.product-price.updating {
  animation: priceUpdate 0.3s var(--ease-out-expo);
}
```

### Animation sélection swatch

```css
@keyframes swatchSelect {
  0% {
    transform: scale(1);
  }
  40% {
    transform: scale(0.95);
  }
  100% {
    transform: scale(1);
  }
}

.size-option.just-selected,
.material-option.just-selected {
  animation: swatchSelect 0.25s var(--ease-bounce);
}
```

### Hover image galerie

```css
.gallery-thumb {
  transition: 
    transform 0.3s var(--ease-smooth),
    border-color 0.2s ease,
    box-shadow 0.3s ease;
}

.gallery-thumb:hover {
  transform: translateY(-3px);
}
```

### Apparition progressive (scroll)

```css
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(24px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-on-scroll {
  opacity: 0;
}

.animate-on-scroll.visible {
  animation: fadeInUp 0.6s var(--ease-out-expo) forwards;
}

/* Délais échelonnés */
.animate-on-scroll:nth-child(1) { animation-delay: 0ms; }
.animate-on-scroll:nth-child(2) { animation-delay: 100ms; }
.animate-on-scroll:nth-child(3) { animation-delay: 200ms; }
.animate-on-scroll:nth-child(4) { animation-delay: 300ms; }
```

---

## 6. DESIGN SPÉCIFIQUE — PROPOSAL A (Immersion Narrative)

### Hero Video/GIF

```css
.hero-video-section {
  position: relative;
  height: 70vh;
  min-height: 500px;
  overflow: hidden;
  
  /* Fond de secours */
  background: var(--creme-chaud);
}

.hero-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
  
  /* Légère désaturation pour cohérence */
  filter: saturate(0.9) brightness(1.02);
}

/* Overlay gradient pour lisibilité texte */
.hero-video-section::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    rgba(254, 253, 251, 0) 0%,
    rgba(254, 253, 251, 0.3) 60%,
    rgba(254, 253, 251, 0.95) 100%
  );
}

/* Titre centré sur hero */
.hero-title {
  position: absolute;
  bottom: 15%;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
  
  font-family: 'Square Peg', cursive;
  font-size: clamp(48px, 8vw, 80px);
  color: var(--bois-sombre);
  text-align: center;
  
  text-shadow: 0 2px 20px rgba(254, 253, 251, 0.8);
}

/* Indicateur scroll */
.scroll-indicator {
  position: absolute;
  bottom: 32px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
  
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  
  color: var(--bois-dore);
  font-size: 12px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  
  animation: scrollBounce 2s infinite;
}

@keyframes scrollBounce {
  0%, 100% { transform: translateX(-50%) translateY(0); }
  50% { transform: translateX(-50%) translateY(8px); }
}
```

### Galerie verticale scrollable

```css
.gallery-vertical {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  max-height: 600px;
  overflow-y: auto;
  
  /* Scrollbar élégante */
  scrollbar-width: thin;
  scrollbar-color: var(--bois-dore) var(--creme-chaud);
  
  padding-right: var(--space-sm);
}

.gallery-vertical::-webkit-scrollbar {
  width: 6px;
}

.gallery-vertical::-webkit-scrollbar-track {
  background: var(--creme-chaud);
  border-radius: 3px;
}

.gallery-vertical::-webkit-scrollbar-thumb {
  background: var(--bois-dore);
  border-radius: 3px;
}

.gallery-vertical-image {
  aspect-ratio: 4 / 3;
  border-radius: 12px;
  overflow: hidden;
  cursor: pointer;
  
  transition: all 0.3s var(--ease-smooth);
}

.gallery-vertical-image:hover {
  box-shadow: 0 8px 24px rgba(74, 63, 53, 0.15);
}

.gallery-vertical-image.active {
  outline: 3px solid var(--bois-dore);
  outline-offset: 3px;
}
```

---

## 7. DESIGN SPÉCIFIQUE — PROPOSAL B (Configurateur Épuré)

### Image produit qui change

```css
.configurator-image {
  position: relative;
  aspect-ratio: 1 / 1;
  background: var(--creme-papier);
  border-radius: 20px;
  overflow: hidden;
  
  /* Ombre premium */
  box-shadow: 
    0 20px 60px rgba(74, 63, 53, 0.08),
    0 8px 24px rgba(74, 63, 53, 0.04);
}

.configurator-image img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  padding: 10%;
  
  /* Transition pour changement de config */
  transition: opacity 0.4s ease;
}

/* Fade entre images */
.configurator-image img.switching {
  opacity: 0;
}

/* Badge config en cours */
.config-badge {
  position: absolute;
  top: 20px;
  left: 20px;
  
  padding: 8px 16px;
  background: rgba(254, 253, 251, 0.95);
  backdrop-filter: blur(8px);
  border-radius: 100px;
  
  font-family: 'Montserrat', sans-serif;
  font-size: 12px;
  font-weight: 600;
  color: var(--bois-profond);
  letter-spacing: 0.05em;
}
```

### Cards taille avec prix intégré

```css
.size-cards {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-md);
}

.size-card {
  position: relative;
  padding: 24px 16px;
  
  background: var(--creme-chaud);
  border: 2px solid transparent;
  border-radius: 16px;
  
  text-align: center;
  cursor: pointer;
  
  /* Transition fluide */
  transition: all 0.3s var(--ease-smooth);
}

.size-card:hover {
  background: var(--ivoire-doux);
  border-color: rgba(147, 125, 104, 0.3);
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(74, 63, 53, 0.1);
}

.size-card.selected {
  background: var(--bois-dore);
  border-color: var(--bois-dore);
  box-shadow: 0 8px 24px rgba(147, 125, 104, 0.25);
}

.size-card .size-value {
  display: block;
  font-family: 'Montserrat', sans-serif;
  font-size: 22px;
  font-weight: 700;
  color: var(--bois-sombre);
  margin-bottom: 6px;
}

.size-card.selected .size-value {
  color: #FEFDFB;
}

.size-card .size-price {
  font-family: 'Montserrat', sans-serif;
  font-size: 15px;
  font-weight: 500;
  color: var(--bois-profond);
  opacity: 0.75;
}

.size-card.selected .size-price {
  color: rgba(254, 253, 251, 0.9);
  opacity: 1;
}

/* Indicateur "sélectionné" */
.size-card.selected::after {
  content: '✓';
  position: absolute;
  top: 12px;
  right: 12px;
  
  width: 22px;
  height: 22px;
  
  background: #FEFDFB;
  border-radius: 50%;
  
  display: flex;
  align-items: center;
  justify-content: center;
  
  font-size: 12px;
  font-weight: 700;
  color: var(--bois-dore);
}
```

### Livraison estimée

```css
.delivery-estimate {
  display: flex;
  align-items: center;
  gap: 10px;
  
  padding: 14px 18px;
  background: rgba(1, 133, 1, 0.08);
  border-radius: 10px;
  
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  color: var(--vert-confiance);
}

.delivery-estimate .icon {
  font-size: 18px;
}

.delivery-estimate .date {
  font-weight: 600;
}
```

### Accordéon specs

```css
.accordion {
  border: 1px solid rgba(147, 125, 104, 0.15);
  border-radius: 16px;
  overflow: hidden;
}

.accordion-item {
  border-bottom: 1px solid rgba(147, 125, 104, 0.1);
}

.accordion-item:last-child {
  border-bottom: none;
}

.accordion-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  padding: 20px 24px;
  background: transparent;
  border: none;
  width: 100%;
  
  font-family: 'Montserrat', sans-serif;
  font-size: 15px;
  font-weight: 600;
  color: var(--bois-sombre);
  
  cursor: pointer;
  transition: background 0.2s ease;
}

.accordion-header:hover {
  background: var(--creme-chaud);
}

.accordion-icon {
  width: 24px;
  height: 24px;
  color: var(--bois-dore);
  
  transition: transform 0.3s var(--ease-smooth);
}

.accordion-item.open .accordion-icon {
  transform: rotate(180deg);
}

.accordion-content {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.4s var(--ease-smooth);
}

.accordion-item.open .accordion-content {
  max-height: 500px;
}

.accordion-body {
  padding: 0 24px 24px;
  
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
  line-height: 1.7;
  color: var(--bois-profond);
}
```

---

## 8. CTA STICKY MOBILE

```css
.sticky-cta {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  
  display: none; /* Apparaît au scroll */
  
  padding: 16px 20px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom));
  
  background: rgba(254, 253, 251, 0.97);
  backdrop-filter: blur(12px);
  
  border-top: 1px solid rgba(147, 125, 104, 0.1);
  box-shadow: 0 -4px 20px rgba(74, 63, 53, 0.08);
}

.sticky-cta.visible {
  display: flex;
  align-items: center;
  gap: 16px;
  
  animation: slideUp 0.3s var(--ease-out-expo);
}

@keyframes slideUp {
  from {
    transform: translateY(100%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

.sticky-cta .price {
  font-family: 'Montserrat', sans-serif;
  font-size: 22px;
  font-weight: 700;
  color: var(--bois-sombre);
}

.sticky-cta .btn-primary {
  flex: 1;
  padding: 16px 24px;
}
```

---

## 9. RESPONSIVE BREAKPOINTS

```css
/* Mobile first */

/* Tablette portrait */
@media (min-width: 600px) {
  .product-grid {
    gap: var(--space-xl);
  }
  
  .size-cards {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Tablette paysage */
@media (min-width: 900px) {
  .product-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .sticky-cta {
    display: none !important; /* Desktop a le CTA visible */
  }
}

/* Desktop */
@media (min-width: 1200px) {
  .product-grid {
    gap: var(--space-2xl);
  }
  
  .gallery-main {
    aspect-ratio: 1 / 1;
  }
}

/* Grand écran */
@media (min-width: 1600px) {
  .container {
    max-width: 1440px;
  }
  
  .product-title-poetic {
    font-size: 64px;
  }
}
```

---

## 10. CHECKLIST IMPLÉMENTATION

### Design Tokens à créer (variables CSS)

- [ ] Couleurs (12 tokens)
- [ ] Gradients (4)
- [ ] Espacements (10)
- [ ] Typographie (10 classes)
- [ ] Ombres (4 niveaux)
- [ ] Border-radius (4 niveaux)
- [ ] Easings (4)
- [ ] Z-index (5 niveaux)

### Composants à créer

- [ ] `.btn-primary` + `.btn-secondary`
- [ ] `.size-option` / `.size-card`
- [ ] `.material-option`
- [ ] `.color-option`
- [ ] `.gallery-main` + `.gallery-thumb`
- [ ] `.reassurance-block`
- [ ] `.artisan-section`
- [ ] `.lifestyle-image`
- [ ] `.accordion`
- [ ] `.sticky-cta`

### Assets à produire

- [ ] Textures bois pour swatches (okoumé, peuplier, noyer)
- [ ] Icônes rassurance (SVG)
- [ ] Video/GIF produit allumé (Proposal A)
- [ ] Photos Robin atelier
- [ ] Photos lifestyle (2-3 par produit, AVEC produit visible)

---

*Design System créé par Claude | 7 février 2026*  
*Pour Atelier Sâpi — Fiches produit premium*

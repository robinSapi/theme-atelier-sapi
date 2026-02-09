# BRIEF TECHNIQUE — RECONSTRUCTION TEMPLATES SAPI
## Pour Claude Code — theme-sapi-maison

**Date:** 6 février 2026
**Objectif:** Reconstruire les gabarits WooCommerce avec design Cinétique/Lumière

---

## 🎨 CHARTE GRAPHIQUE SAPI

### Variables CSS obligatoires

\`\`\`css
:root {
  /* COULEURS PRIMAIRES */
  --color-cream: #FEFDFB;
  --color-gray-light: #F1F1F1;
  --color-warm: #FBF6EA;
  --color-dark: #323232;
  --color-gray: #585858;
  --color-wood: #937D68;      /* ACCENT PRINCIPAL */

  /* COULEURS PONCTUELLES */
  --color-orange: #E35B24;    /* CTAs, badges */
  --color-green: #018501;     /* Feedback positif */

  /* TYPOGRAPHIE */
  --font-display: 'Square Peg', cursive;
  --font-body: 'Montserrat', sans-serif;

  /* EASING */
  --ease-expo: cubic-bezier(0.87, 0, 0.13, 1);
  --ease-smooth: cubic-bezier(0.4, 0, 0.2, 1);
}
\`\`\`

---

## 📐 TEMPLATES À RECONSTRUIRE

### 1. Page Boutique (archive-product.php)
- Hero avec section-number "01" + titre
- Filtres horizontaux animés
- Grid 4 colonnes (responsive 3/2/1)

### 2. Carte Produit (content-product.php)
- Image ratio 1:1, scale au hover
- Badge optionnel (Nouveau/Signature)
- Quick view button au hover
- Prix + CTA "Voir"

### 3. Fiche Produit (single-product.php)
- Layout 2 colonnes (galerie | infos)
- Vignettes 100px minimum
- Prix DYNAMIQUE selon variation
- Swatches AVEC LABELS
- Bloc rassurance sous CTA
- Limiter lifestyle à 2-3 images (50vh max)

### 4. Mini Cart
- Slide panel depuis la droite
- Items avec images
- Total calculé
- CTA checkout

---

## 📋 CHECKLIST P0

- [ ] Corriger images 57x57px → width: 100%
- [ ] Prix dynamique selon variation
- [ ] Labels sur swatches
- [ ] Formulaire mobile visible
- [ ] Vignettes 100px min

---

## 📁 MOCKUPS DE RÉFÉRENCE

\`\`\`
/Users/samuel/Local/atelier-sapi/mockups/
├── claude-cinetique/     ← PRINCIPAL
└── claude-lumiere/       ← SECONDAIRE
\`\`\`

---

*Brief créé par Claude Cowork | 6 février 2026*

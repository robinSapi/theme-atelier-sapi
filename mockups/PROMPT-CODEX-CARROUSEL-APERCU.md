# PROMPT CODEX — Corrections Carrousel & Aperçu Rapide

## Contexte
Après audit du site testlumineux.atelier-sapi.fr, plusieurs écarts ont été identifiés entre les spécifications du carrousel éditorial / aperçu rapide et l'implémentation actuelle.

---

## 🔴 CORRECTIONS PRIORITAIRES (P0)

### 1. Afficher plusieurs cartes produits côte à côte dans le carrousel

**Problème actuel :** Le carrousel affiche 1 seule carte en pleine largeur.

**Correction demandée :**
- Afficher **3 cartes visibles** simultanément sur desktop
- Largeur de chaque carte : **28% du conteneur** (ou ~280-380px)
- Espacement entre cartes : **24px**
- Les cartes doivent se chevaucher légèrement (**-80px** de superposition organique)
- Sur mobile : afficher 1 carte avec navigation swipe

**CSS suggéré :**
```css
.carousel-item {
  flex: 0 0 28%;
  min-width: 280px;
  max-width: 380px;
  margin-right: -80px; /* superposition organique */
}
```

---

### 2. Ajouter le mini-carrousel sur /nos-creations/

**Problème actuel :** Le mini-carrousel de vignettes est présent sur les pages catégories (lampadaire, suspension) mais **absent sur /nos-creations/**.

**Correction demandée :**
- Ajouter le même composant mini-carrousel (grille de vignettes) sous le carrousel principal sur /nos-creations/
- Vignettes de 64×64px
- Bordure orange sur la vignette active
- Clic sur une vignette = aller directement au produit correspondant

---

### 3. Ajouter la description courte dans la modal Aperçu Rapide

**Problème actuel :** La modal affiche titre + prix + bouton, mais **pas de description produit**.

**Correction demandée :**
- Récupérer le `short_description` ou `excerpt` du produit WooCommerce
- L'afficher entre le titre et le prix
- Style : police body, interligne aéré, couleur bois (#6B5A4A)

**Structure attendue :**
```
[Titre produit]
[Description courte - 2-3 lignes max]
[Prix]
[Variantes]
[Bouton "Voir fiche complète"]
```

---

### 4. Ajouter les variantes dans la modal Aperçu Rapide

**Problème actuel :** Les variantes (tailles, essences de bois) ne sont **pas affichées** dans la modal.

**Correction demandée :**
- Récupérer les attributs de variation du produit WooCommerce (pa_taille, pa_materiau/pa_essence)
- Les afficher dans un encadré crème (#FAF7F2) avec bordure légère
- Format :
  ```
  TAILLES : S  M  L  XL
  ESSENCES : Okoumé  Peuplier  Noyer
  ```
- Labels en majuscules, valeurs en normal

**HTML suggéré :**
```html
<div class="quickview-variants">
  <div class="variant-group">
    <span class="variant-label">TAILLES</span>
    <span class="variant-values">S  M  L  XL</span>
  </div>
  <div class="variant-group">
    <span class="variant-label">ESSENCES</span>
    <span class="variant-values">Okoumé  Peuplier</span>
  </div>
</div>
```

---

## 🟠 CORRECTIONS IMPORTANTES (P1)

### 5. Passer la modal Aperçu Rapide en layout 2 colonnes (desktop)

**Problème actuel :** La modal utilise un layout vertical (image en haut, infos en bas).

**Correction demandée :**
- **Desktop (>768px)** : Layout 2 colonnes
  - Colonne gauche (60%) : Galerie photos + navigation + vignettes
  - Colonne droite (40%) : Titre, description, prix, variantes, bouton
- **Mobile (<768px)** : Garder le layout vertical actuel

**CSS suggéré :**
```css
@media (min-width: 768px) {
  .quickview-modal-content {
    display: grid;
    grid-template-columns: 60% 40%;
    gap: 32px;
  }
}
```

---

### 6. Rendre le bouton "Aperçu" visible sur mobile/tactile

**Problème actuel :** Le bouton "Aperçu rapide" n'apparaît qu'au hover CSS, invisible sur tactile.

**Correction demandée :**
- Sur mobile/tactile : afficher le bouton "Aperçu" de façon **permanente** sur les cartes produit
- Position : en bas à gauche de l'image, semi-transparent
- Icône œil + texte "Aperçu"

**CSS suggéré :**
```css
@media (hover: none) {
  .product-card .apercu-button {
    opacity: 1 !important;
    pointer-events: auto;
  }
}
```

---

## 🟡 AMÉLIORATIONS (P2)

### 7. Flèches de navigation avec forme "organique"

**Problème actuel :** Les flèches sont des boutons ronds/ovales simples.

**Correction demandée :**
- Appliquer un border-radius complexe type Patricia Urquiola
- Exemple : `border-radius: 60% 40% 55% 45% / 55% 45% 60% 40%;`
- Effet scale 1.1 au hover
- Couleur orange au hover (#F28949)

---

## 📋 CHECKLIST DE VALIDATION

Après corrections, vérifier :

### Carrousel
- [ ] 3 cartes visibles côte à côte sur desktop
- [ ] Espacement 24px entre les cartes
- [ ] Superposition légère (-80px)
- [ ] Mini-carrousel présent sur TOUTES les pages (y compris /nos-creations/)
- [ ] Navigation flèches fonctionnelle
- [ ] Compteur "X / Y" mis à jour

### Modal Aperçu Rapide
- [ ] Layout 2 colonnes sur desktop
- [ ] Titre produit affiché
- [ ] Description courte affichée
- [ ] Prix affiché
- [ ] Variantes (tailles + essences) affichées dans encadré crème
- [ ] Galerie photos avec navigation
- [ ] Barre de progression auto-advance (3s)
- [ ] Pause au hover/clic
- [ ] Bouton "Voir fiche complète" fonctionnel

### Mobile
- [ ] Bouton "Aperçu" visible sans hover
- [ ] Layout vertical pour la modal
- [ ] Carrousel swipeable (1 carte)

---

## Fichiers probablement concernés

- `theme-sapi-maison/assets/js/carousel.js` ou équivalent
- `theme-sapi-maison/assets/js/quickview.js` ou équivalent
- `theme-sapi-maison/assets/css/carousel.css`
- `theme-sapi-maison/assets/css/quickview.css`
- `theme-sapi-maison/template-parts/product-card.php`
- `theme-sapi-maison/woocommerce/content-product.php`

---

*Prompt généré par Claude | 7 février 2026*

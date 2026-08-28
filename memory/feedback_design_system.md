---
name: Design system — charte esthétique (source de vérité)
description: Couleurs, typo, heroes, ombres, cards — toutes les consignes visuelles validées par Robin
type: feedback
---

> **Lire ce fichier avant toute modification visuelle.** Ces règles sont actées — ne pas dévier sans demande explicite de Robin.

---

## 🎨 Couleurs — Variables CSS

Utiliser **toujours** les variables CSS. Ne jamais écrire de valeur hex directement dans le code.

```css
--color-wood:      #8B7355   /* couleur bois principale, .section-num, accents */
--color-wood-dark: #4A3F35   /* overlay heroes, textes sombres */
--color-wood-mid:  #6B5A4A   /* teintes intermédiaires */
```

Les anciennes variables `--bois-*` sont en cours de migration vers `--color-*` — ne pas les réintroduire.

---

## 🔤 Typographie

- **H1 heroes** : Square Peg, `clamp(72px, 14vw, 150px)`
- **Noms produits** : obligatoirement via `product-name-formatter.js`
  - `.product-firstname` : Montserrat gras, uppercase, 0.75em
  - `.product-restname` : Square Peg cursive, capitalize, 1.6em
  - Tout nouveau contexte affichant un nom produit = ajouter le sélecteur dans les deux tableaux `selectors` de `product-name-formatter.js`
- **H2 page Artisan** : Square Peg (tous)
- **`.section-num`** : `font-weight: 700; letter-spacing: 0.1em; color: var(--color-wood); opacity: 0.85`
  - ⚠️ Certaines pages utilisent encore `.section-number` (Artisan, Sur-mesure, thankyou, presse) — ne pas migrer sans instruction

---

## 🖼️ Heroes

### Standard sombre (Nos créations / Artisan / Conseils / Contact)
```css
/* Overlay */
background: rgba(74, 63, 53, 0.75);

/* Padding */
padding: 140px 20px 100px;   /* desktop */
padding: 120px 20px 80px;    /* mobile */
```
Texte blanc. Ne pas changer l'overlay pour du noir.

### Hero Sur-mesure
Fond clair (crème), texte sombre. Animation lettre par lettre JS sur H1. **Ne pas aligner sur les heroes sombres** — différenciation volontaire.

### Sections texte page Artisan (intro + citation)
Overlay quasi-opaque (0.95/0.88). **Ne pas remplacer par gradient ou semi-transparent** — Robin a reverté ce changement.

### Règle débordement H1
Si un H1 hero déborde : **élargir le conteneur** (max-width + `white-space: nowrap`) avant de réduire la taille de police.

---

## 🃏 Ombres — Cards

### Variables dans `:root` (actives depuis le 11 avril 2026)
```css
--shadow-card:       0 2px 8px rgba(147,125,104,0.12), 0 6px 28px rgba(147,125,104,0.15);
--shadow-card-hover: 0 4px 14px rgba(147,125,104,0.16), 0 12px 44px rgba(147,125,104,0.22);
```

### ⚠️ Règle absolue
**Ne jamais ajouter une ombre à une card qui n'en avait pas** — sauf demande explicite de Robin.

### Cards avec ombre repos + hover
`.product-card-cinetique`, `.carousel-editorial-slide .product-card-cinetique`, `.category-featured-card`, `.blog-card`, `.collection-card`, `.cross-link-card`, `.sapi-showcase-card`

### Cards avec ombre hover uniquement
`.product-card:hover`, `.why-sapi-card:hover`, `.testimonial-card:hover`, `.robin-category-card__inner:hover`, `.bento-card:hover`, `.sur-mesure-card__link:hover`

### Transform hover
`translateY(-2px)` uniformisé. **Exception : `.carousel-editorial-slide` → `transform: none`** au hover (pas de translateY).

---

## 📐 Grille produits (pages catégories) — Layout "Showcase split"

*En place depuis le 11 avril 2026 (commits `984a617`→`4ae967e`).*

- Pas de section "Coup de cœur", pas de H2 redondant au-dessus de la grille
- Sections renumérotées : grille = 01, puis 02, 03…
- Chaque produit = `.sapi-showcase-card` pleine largeur, 2 zones :
  - **Zone blanche 18%** : vignette 180px (coins 8px) + nom JS + prix + CTA bois
  - **Zone photo 82%** : 1re photo ambiance ACF plein cadre
  - Alternance gauche/droite
- Tri : best-sellers en premier (par nombre de ventes)
- Écart vertical entre cards : 2.5rem
- Hover : zoom photo ambiance + swap vignette → 1re photo galerie WC (modèle allumé) + CTA orange
- Mobile : empilé (photo haut, info bas), **vignette masquée**, CTA orange, marges 1.5rem, gap 2rem
- **Exception accessoires** : pas de photo ambiance ACF → fallback `get_the_post_thumbnail_url()` (image featured WC). Valable catégories + Mes créations. Ne pas utiliser une chaîne vide comme fallback — ça casse l'affichage mobile dans les showcase cards.

---

## 🎯 Robin Conseiller — Bandeau réassurance (template actuel)

Template revenu à `c82a3c4` le 11 avril 2026.

- Mode repos : 4 items réassurance + badge bois "Démarrer mon projet". Mobile : 2 items aléatoires.
- Mode projet : badge bois "Mon projet" + chips résumant les réponses. Classe `.has-project` sur `#robin-bandeau`.
- ⚠️ Les classes `.robin-left`, `.robin-steps`, `.robin-cta-btn` etc. **n'existent plus** — ne pas les utiliser ni les recréer sans instruction explicite.

---

## 🔘 Boutons & CTA — Règle forme = intention

*Audit 12 avril 2026 — référence : memory/design_system.md section Boutons*

**Pill 50px** = CTA de navigation/découverte :
- `.hero-cta`, `.btn-view-full`, `.sapi-showcase-card .showcase-cta`, `.product-card-cinetique .btn-view`, `.sur-mesure-card__cta`, `.mini-cart-empty .btn-continue`, `.robin-pill`, `.robin-conseil__contact-btn`, `.surmesure-card-cta`, `.bento-cta .cta-button`

**Rectangulaire 8px** = CTA de transaction :
- `.mini-cart-footer .btn-view-cart` (outline bois), `.mini-cart-footer .btn-checkout` (orange)
- `.artisan-cta .button` (orange gradient, 8px — surcharge le 5px de base)

**Base `.button` (5px)** = WooCommerce générique uniquement — ne pas l'utiliser pour de nouveaux CTAs visuels

**Asymétrique `0 0 16px 16px`** = `.carousel-editorial-slide .btn-view` — structurel, ne pas modifier

**Texte pur** = `.robin-category-card__cta`, `.surmesure-modal-cta` — card déjà cliquable, pas de forme propre

### Couleurs
- Orange / gradient-cta = action principale, achat
- Bois = invitation artisanale, CTA secondaire
- Outline bois = neutre, voir sans engagement
- Blanc = sur fond sombre uniquement (bento, carousel)

### À ne pas faire (boutons)
- Ne pas mettre `border-radius: 5px` sur un nouveau CTA visible
- Ne pas cumuler orange + bois sur la même ligne de CTA
- Ne pas ajouter de fond/bordure à un CTA texte pur (`.robin-category-card__cta`) — la card entière est cliquable

---

## 🚫 À ne jamais faire

- Écrire un mockup HTML avec de nouvelles classes CSS et demander à Claude Code de l'implémenter → il réécrit tout hors charte. **Écrire des diffs précis à la place.**
- Changer la couleur d'overlay des heroes pour du noir
- Toucher les overlays de la page Artisan
- Ajouter des ombres sur des cards qui n'en ont pas

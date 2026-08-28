---
name: Règles techniques et pièges
description: Formatage titres produits, $acf singleton, srcset variations, WC Add-Ons CSS
type: feedback
---

## Formatage des titres produits (RÈGLE ABSOLUE)
- **TOUJOURS** utiliser `product-name-formatter.js` pour les noms de produits
- `.product-firstname` = Montserrat gras, uppercase, 0.75em
- `.product-restname` = Square Peg cursive, capitalize, 1.6em
- **Nouveau contexte** : ajouter le sélecteur dans les DEUX tableaux `selectors` de `assets/product-name-formatter.js`

## Piège : $acf est le singleton global d'ACF
- `$acf = function_exists('get_field')` dans un template écrase le singleton → crash
- **Solution** : `$has_acf` à la place. Variables réservées : `$acf`, `$wpdb`, `$wp`, `$wp_query`, `$post`

## Piège : srcset et Variations WooCommerce
- Changer `img.src` sans `img.srcset = ''` → le navigateur garde l'ancienne image du srcset
- **TOUJOURS** : `mainImage.src = newSrc; mainImage.srcset = '';`

## WooCommerce Product Add-Ons — CSS Custom
- Plugin officiel WooCommerce/Automattic (~49€/an)
- Variations = changements fondamentaux ; Add-Ons = choix cosmétiques
- CSS masque les lignes détail et "Subtotal", affiche uniquement le montant total
- **Fichier** : `style.css` (fin du fichier, après ligne ~18508)

## Quick-view gallery-thumb fix
- `.quick-view-gallery-thumbs .gallery-thumb` nécessite `flex: 0 0 auto` + `padding: 0`

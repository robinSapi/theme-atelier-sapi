---
name: Branches Git — stratégie et état actuel
description: Branches actives, leur rôle, et règle de travail par branche
type: project
---

## ⚠️ RÈGLE ABSOLUE — À LIRE AVANT TOUTE MODIFICATION

**Toujours demander à Robin dans quelle branche travailler avant de toucher du code.**
Ne jamais supposer qu'on travaille sur `master`.

---

## Branches actives

### `master`
- Branche principale, déployée sur `test.atelier-sapi.fr` via GitHub Actions
- Reçoit les hotfixes, les petites modifs et les features terminées (via merge)
- **Ne jamais faire de grosse refonte directement sur master**

### `feature/refonte-fiche-produit`
- **Chantier :** refonte de `woocommerce/single-product.php` pour mieux mettre en avant les photos
- **Créée le :** 14 avril 2026 depuis `master`
- **Modifications attendues :**
  - Réordonner la galerie : `ambianceH` en premier, photos studio/WooCommerce en dernier
  - Supprimer l'intro screen (photo ambiance plein écran aléatoire au chargement)
  - Ajouter une section ambiance pleine largeur sous le bloc principal
  - Galerie mobile : slider horizontal swipeable (scroll-snap)
  - Simplifier la section "Fabriqué avec passion" → 1 phrase + lien page Artisan
- **Fichiers concernés :** `woocommerce/single-product.php` principalement
- **Merge dans master :** uniquement quand Robin valide sur le site test

---

## Workflow multi-branches

1. Chaque chantier = 1 branche dédiée
2. Les modifs urgentes passent par `master` directement (ou une branche `fix/...` mergée vite)
3. Après un hotfix sur `master`, récupérer dans les branches feature actives via `git merge master`
4. **Toujours préciser la branche cible dans les tâches de `claude_code_queue.md`**

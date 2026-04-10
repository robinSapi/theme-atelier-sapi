# CLAUDE.md – Thème Sapi Maison
## Fichier de Référence pour Claude Code

> **⚠️ LIRE CE FICHIER EN DÉBUT DE SESSION**
> Ce fichier est la référence principale. L'historique des modifications est dans git.

**Dernière synchro :** 3 avril 2026

---

## 🎯 IDENTITÉ PROJET

| Info | Valeur |
|------|--------|
| **Projet** | Atelier Sâpi – Luminaires artisanaux bois |
| **Site test** | `testlumineux.atelier-sapi.fr` |
| **Site prod** | `atelier-sapi.fr` |
| **Repo GitHub** | `github.com/robinSapi/theme-atelier-sapi` |
| **Branche test** | `test-theme-sapi-maison` |
| **Branche prod** | `master` (merge uniquement après validation Robin) |
| **Hébergeur** | O2switch |
| **Local** | `/Users/sapi/Atelier Sapi Claude Cowork/site-web/` |

---

## 🤝 MON RÔLE (Claude Code)

Je suis l'instance technique. Je m'occupe exclusivement du code du thème WordPress.

- Écriture et modification du code (PHP, JS, CSS)
- Git : commit, push, merge, hotfix, déploiement
- **Seule instance autorisée à modifier les fichiers du thème**
- Je lis les tâches dans `memory/claude_code_queue.md` au démarrage
- Je mets à jour les fichiers mémoire en fin de tâche
- Je retourne le résultat de chaque tâche dans `memory/claude_code_queue.md`

**Cowork (Claude Desktop) planifie et délègue — je n'agis que sur le code.**

---

## 🔀 WORKFLOW DÉPLOIEMENT

### Flux normal (test → prod)
```
Code → commit + push sur test-theme-sapi-maison → auto-deploy → testlumineux.atelier-sapi.fr
                                                                        ↓
                                                                  Robin teste
                                                                        ↓
                                                                  "Go prod"
                                                                        ↓
                                              Claude merge test-theme-sapi-maison → master + push
                                                                        ↓
                                                    Robin lance le workflow GitHub Actions
                                                                        ↓
                                                                  atelier-sapi.fr
```

### Hotfix urgent
```
Claude crée une branche hotfix/xxx depuis master
      → fait le fix → commit + push hotfix/xxx
      → Robin valide → Claude merge hotfix/xxx dans master + push
      → Robin lance le workflow → prod
      → Claude rebase test-theme-sapi-maison sur master
```

### Structure duale du repo (CRITIQUE)
```
/Users/sapi/Atelier Sapi Claude Cowork/site-web/     ← ROOT (déployé par GitHub Actions)
├── .git/
├── style.css, functions.php, etc.
└── wp-content/themes/theme-sapi-maison/              ← NESTED (WordPress local)
    ├── .git/
    └── style.css, functions.php, etc.
```
**Workflow :**
1. Travailler dans `wp-content/themes/theme-sapi-maison/`
2. Syncer : `rsync -av --exclude='.git' --exclude='.github' wp-content/themes/theme-sapi-maison/ .`
3. Commit au ROOT, jamais dans le nested !

**JAMAIS changer `local-dir` dans le workflow GitHub Actions — cause page blanche !**

**RÈGLES GIT :**
- Commit/push sur `test-theme-sapi-maison` : automatique, sans demander
- Merge dans `master` : **UNIQUEMENT** après validation explicite de Robin
- **JAMAIS** push directement sur master

---

## 🧠 MÉMOIRE

- `memory/MEMORY.md` — index des mémoires du thème (lire en premier)
- `memory/claude_code_queue.md` — tâches reçues de Cowork (lire au démarrage)
- `memory/*.md` — fichiers mémoire détaillés

---

## 🧭 PHILOSOPHIE DE TRAVAIL

- **Toujours implémenter ce qui se fait de mieux aujourd'hui**
- **Analyser le vrai problème** avant de coder — pas juste le symptôme
- **Proposer un refactoring** si le code existant est la source du problème
- **Pas de contournement** : si la bonne solution implique un changement côté Robin, le dire clairement
- **Un changement à la fois**, mesurer l'impact, puis passer au suivant

---

## 🚨 RÈGLES ABSOLUES – NE JAMAIS FAIRE

### 1. ❌ "C'est un problème de cache"
Avant de dire "vide ton cache", vérifier :
- Le fichier est-il bien enregistré ?
- Le bon fichier est-il modifié (pas un doublon) ?
- Git push effectué ? Déployé sur testlumineux ?
- Erreur PHP silencieuse ? (regarder les logs)
- `rsync` nested → root fait avant commit ?

### 2. ❌ posts_per_page => -1
```php
// INTERDIT – charge TOUS les produits en mémoire
// EXCEPTION : Filtrage client-side JS (volume contrôlé ~20-40 produits max)
```

### 3. ❌ Echo sans échappement
```php
echo esc_html($variable);   // ✅
echo esc_attr($variable);   // ✅
echo esc_url($url);         // ✅
```

### 4. ❌ Google Fonts pour Square Peg
Ne fonctionne pas sur Safari. Toujours utiliser la police hébergée localement.

### 5. ❌ Ajouter !important sans réflexion
Augmenter la spécificité du sélecteur proprement.
**Exception documentée :** `.is-filtered-out { display: none !important }`

### 6. ❌ setTimeout pour "fixer" un timing
Utiliser les bons événements (DOMContentLoaded, IntersectionObserver).

### 7. ❌ Gutenberg + save_post pour meta boxes
Utiliser AJAX-only (`wp_ajax_sapi_save_focal_point`).

### 8. ❌ height: 100% sur enfant avec parent en min-height
Utiliser `inset: 0`.

### 9. ❌ Changer img.src sans réinitialiser srcset
```javascript
mainImage.src = newImage;
mainImage.srcset = ''; // OBLIGATOIRE
```

### 10. ❌ `$acf` comme nom de variable
ACF stocke son singleton global dans `$acf`. Utiliser `$has_acf`.

### 11. ❌ Image statique sans `sapi_image()`
```php
<?php echo sapi_image('2025/05/photo.jpg', 'large', ['alt' => 'Description', 'loading' => 'lazy']); ?>
```

### 12. ❌ Afficher un titre produit sans le formatter
Tous les titres passent par `product-name-formatter.js`.

### 13. ❌ Version statique pour les assets
```php
wp_enqueue_style('style', get_stylesheet_uri(), [], filemtime(get_stylesheet_directory() . '/style.css'));
```

---

## ⚠️ CE QUI CASSE RÉGULIÈREMENT

| Problème | Cause | Solution |
|----------|-------|----------|
| Image hero ne remplit pas | `img { height: auto }` global | `height: 100% !important` sur l'image hero |
| Bouton aperçu rapide toujours visible | `opacity: 0.7` au lieu de `0` | `opacity: 0` + `pointer-events: none` par défaut |
| Sticky bar scroll ne fonctionne pas | `id="product-summary-main"` manquant | Vérifier dans single-product.php |
| Main query corrompue sur pages catégorie | Mini-carousel sans `wp_reset_postdata()` | `$grid_query` dédié + reset |
| Grille CSS overflow mobile | `minmax(X, 1fr)` | `minmax(min(X, 100%), 1fr)` |

---

## 📋 PATTERNS JS OBLIGATOIRES

```javascript
// Throttle événements fréquents
let raf = null;
element.addEventListener('mousemove', (e) => {
  if (raf) return;
  raf = requestAnimationFrame(() => { raf = null; });
});

// Passive pour scroll/touch
window.addEventListener('scroll', handler, { passive: true });

// Timeout sur fetch
function fetchWithTimeout(url, timeout = 5000) {
  const controller = new AbortController();
  const id = setTimeout(() => controller.abort(), timeout);
  return fetch(url, { signal: controller.signal }).finally(() => clearTimeout(id));
}
```

---

## 📂 STRUCTURE FICHIERS CLÉS

| Fichier | Rôle |
|---------|------|
| `functions.php` | Cœur du thème — `sapi_image()`, `sapi_get_product_photos()` |
| `style.css` | Styles (~13000+ lignes) — Design System unifié |
| `assets/cinetique.js` | Animations + interactions premium |
| `assets/quick-view.js` | Modal aperçu rapide (746 lignes) |
| `assets/shop.js` | Filtres + variations (781 lignes) |
| `assets/robin-conseiller.js` | Robin Conseiller V2 |
| `assets/guide-conseils.json` | 192 textes pré-générés |
| `woocommerce/single-product.php` | Fiche produit custom |
| `woocommerce/archive-product.php` | Page /nos-creations/ |
| `woocommerce/taxonomy-product_cat.php` | Pages catégorie |

---

## 🎨 DESIGN SYSTEM

```css
--color-cream: #FEFDFB;
--color-warm: #FBF6EA;
--color-wood: #937D68;
--color-orange: #E35B24;
--color-dark: #323232;
--font-display: 'Square Peg', cursive;
--font-body: 'Montserrat', sans-serif;
```

---

## ✅ CHECKLIST AVANT COMMIT

- [ ] Pas de console.log() oubliés
- [ ] Tous les echo sont échappés
- [ ] filemtime() utilisé pour les assets
- [ ] -webkit- sur les transforms/transitions
- [ ] Testé sur mobile (iPhone Safari minimum)
- [ ] Pas d'URL de staging hardcodée
- [ ] rsync nested → root fait avant commit
- [ ] Commit au ROOT (pas dans le nested)

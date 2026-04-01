# CLAUDE.md – Thème Sapi Maison
## Fichier de Référence Rapide pour IA

> **⚠️ LIRE CE FICHIER + `CUSTOMIZATIONS.md` AVANT TOUTE MODIFICATION**
> Ce fichier = référence rapide. CUSTOMIZATIONS.md = historique complet.

**Dernière synchro :** 31 mars 2026

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
| **Local** | `/Users/sapi/testLumineux-atelier-sapi` |

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

### Hotfix urgent (fix isolé sans embarquer le travail en cours)
```
Claude crée une branche hotfix/xxx depuis master
      → fait le fix → commit + push hotfix/xxx
      → Robin valide sur testlumineux (deploy manuel ou switch branche)
      → Claude merge hotfix/xxx dans master + push
      → Robin lance le workflow → prod
      → Claude rebase test-theme-sapi-maison sur master (récupère le fix)
```

**RÈGLES :**
- Commit/push sur `test-theme-sapi-maison` : automatique, sans demander
- Merge dans `master` : **UNIQUEMENT** après validation explicite de Robin ("go prod", "go en production", etc.)
- **JAMAIS** push directement sur master sans passer par le merge
- **Hotfix** : quand Robin veut passer un fix en prod sans embarquer le travail en cours, Claude crée une branche `hotfix/xxx` depuis master, fait le fix, et merge uniquement cette branche dans master

---

## 🧭 PHILOSOPHIE DE TRAVAIL

- **Toujours implémenter ce qui se fait de mieux aujourd'hui** — les standards actuels du secteur, pas juste "ça marche"
- **Analyser le vrai problème** avant de coder — pas juste le symptôme
- **Proposer un refactoring** si le code existant est la source du problème
- **Pas de contournement** : si la bonne solution implique un changement côté Robin (ACF, réglage WordPress, upload), le dire clairement
- **Découper les tâches complexes** et mettre à jour la mémoire entre chaque étape

---

## 🚨 RÈGLES ABSOLUES – NE JAMAIS FAIRE

### 1. ❌ "C'est un problème de cache"
**Le cache n'est PAS le problème dans 90% des cas sur ce projet.**
Il n'y a pas de cache actif en développement !

**Avant de dire "vide ton cache", vérifier :**
- [ ] Le fichier est-il bien enregistré ?
- [ ] Le bon fichier est-il modifié (pas un doublon) ?
- [ ] Git push effectué ? Déployé sur testlumineux ?
- [ ] Erreur PHP silencieuse ? (regarder les logs)

### 2. ❌ posts_per_page => -1
```php
// INTERDIT – charge TOUS les produits en mémoire
$query = new WP_Query(['posts_per_page' => -1]);

// EXCEPTION : Recherche produits et filtres (volume contrôlé ~20-40 produits)
// Voir CUSTOMIZATIONS.md section "Pièges fréquents"
```

### 3. ❌ Echo sans échappement
```php
// INTERDIT
echo $variable;

// OBLIGATOIRE
echo esc_html($variable);
echo esc_attr($variable);
echo esc_url($url);
```

### 4. ❌ Google Fonts pour Square Peg
**NE FONCTIONNE PAS SUR SAFARI.** Utiliser uniquement la police locale.
```css
/* INTERDIT */
@import url('fonts.googleapis.com/...Square+Peg');

/* CORRECT – police hébergée localement */
src: url('./assets/fonts/SquarePeg-Regular.woff2');
```

### 5. ❌ Ajouter !important sans réflexion
**Crée plus de problèmes qu'il n'en résout.**
✅ Augmenter la spécificité du sélecteur proprement.

### 6. ❌ setTimeout pour "fixer" un timing
**Cache un problème de race condition.**
✅ Utiliser les bons événements (DOMContentLoaded, load, etc.)

### 7. ❌ Gutenberg + save_post pour meta boxes
**Gutenberg re-soumet les formulaires et écrase les valeurs AJAX.**
✅ Utiliser AJAX-only pour sauvegarder les meta boxes custom.

### 8. ❌ height: 100% sur enfant avec parent en min-height
**Ne fonctionne pas.**
✅ Utiliser `inset: 0` au lieu de `top/left + width/height: 100%`

### 9. ❌ Changer img.src sans réinitialiser srcset
**Le navigateur peut ignorer le nouveau src et afficher l'ancienne image du srcset.**
```javascript
// INCOMPLET
mainImage.src = newImage;

// CORRECT
mainImage.src = newImage;
mainImage.srcset = ''; // OBLIGATOIRE
```

### 10. ❌ Utiliser `$acf` comme nom de variable dans un template WordPress
**ACF stocke son singleton global dans `$acf`. Les templates WordPress s'exécutent dans le scope global, donc écrire `$acf = true` écrase le singleton !**
```php
// INTERDIT – écrase le singleton global d'ACF → "Call to a member function init() on true"
$acf = function_exists('get_field');

// CORRECT
$has_acf = function_exists('get_field');
```
**Variables globales réservées à éviter** : `$acf`, `$wpdb`, `$wp`, `$wp_query`, `$post`

### 11. ❌ Image statique sans `sapi_image()`
**Les images hardcodées en PHP ne bénéficient pas du srcset → le mobile charge l'image en pleine résolution.**
```php
// INTERDIT – pas de srcset, pèse des Mo sur mobile
<img src="<?php echo get_template_directory_uri(); ?>/assets/img/photo.jpg">

// CORRECT – srcset automatique, lazy loading
<?php echo sapi_image('2025/05/photo.jpg', 'large', ['alt' => 'Description', 'loading' => 'lazy']); ?>
```
- Utiliser `'large'` pour les images standard, `'full'` pour les hero plein écran
- Les images ACF/WooCommerce ont déjà leur srcset — pas besoin du helper
- Si on remplace une photo : plugin "Enable Media Replace" pour garder le même nom de fichier

### 12. ❌ Afficher un titre produit sans le formatter
**Tous les titres de produits DOIVENT passer par `product-name-formatter.js`.**
Le formatter sépare automatiquement le prénom (1er mot) du reste du nom :
- **Prénom** → `<span class="product-firstname">` = Montserrat gras, uppercase, 0.75em
- **Article + nom** → `<span class="product-restname">` = Square Peg cursive, capitalize, 1.6em

```html
<!-- INTERDIT — titre brut sans formatage -->
<h3><?php the_title(); ?></h3>

<!-- CORRECT — utiliser un sélecteur ciblé par le formatter -->
<!-- Le JS formate automatiquement si le sélecteur est dans la liste -->
```
**Pour ajouter un nouveau contexte :** ajouter le sélecteur CSS dans les deux tableaux `selectors` de `assets/product-name-formatter.js` (init + MutationObserver).

---

## 📂 STRUCTURE FICHIERS CLÉS

| Fichier | Rôle | ⚠️ Attention |
|---------|------|--------------|
| `functions.php` | Cœur du thème | Contient `sapi_image()`, `sapi_get_product_photos()`, `sapi_get_video_thumbnail()` |
| `style.css` | Styles (~13000+ lignes) | Design System unifié |
| `assets/cinetique.js` | Animations + interactions | Premium v2.0 |
| `assets/quick-view.js` | Modal aperçu rapide | 746 lignes |
| `assets/menu.js` | Menu burger + recherche | Focus trap WCAG |
| `assets/shop.js` | Filtres + variations | 781 lignes |
| `assets/robin-conseiller.js` | Robin Conseiller V2 | Contrôleur modale + questionnaire |
| `assets/guide-conseils.json` | Robin Conseiller V2 | 192 textes pré-générés |
| `inc/guide-data.php` | Robin Conseiller V2 | Questions/choix/visibilité (7 étapes) |
| `woocommerce/single-product.php` | Fiche produit | Template custom complet |
| `woocommerce/archive-product.php` | Page /nos-creations/ | Hero magazine + carrousel |
| `woocommerce/taxonomy-product_cat.php` | Pages catégorie | Mini-carousel + grille |

---

## 🎨 DESIGN SYSTEM (Variables CSS)

```css
/* Couleurs principales */
--color-cream: #FEFDFB;
--color-warm: #FBF6EA;
--color-wood: #937D68;       /* Accent principal */
--color-orange: #E35B24;     /* CTA (harmonisé 12/02/2026) */
--color-dark: #323232;

/* Typographie */
--font-display: 'Square Peg', cursive;  /* Titres */
--font-body: 'Montserrat', sans-serif;  /* Corps */

/* Boutons CTA */
background: linear-gradient(180deg, #E35B24 0%, #D14F1C 100%);
/* Ombres CHAUDES (pas grises !) */
box-shadow: 0 4px 15px rgba(227, 91, 36, 0.25);
```

---

## ✨ FONCTIONNALITÉS PREMIUM IMPLÉMENTÉES

Tout le site a été harmonisé en **5 vagues de design premium** :

1. **Vague 1** – Storytelling & Trust (page Artisan)
2. **Vague 2** – Lead Generation (Conseils, Contact)
3. **Vague 3** – Content (Blog archive, Single post)
4. **Vague 4** – Collections Enrichment (Editorial content, 500+ mots/catégorie)
5. **Vague 5** – Premium Interactions v2.0 (Parallax, Canvas particles, Filters, Infinite scroll)

**Quick View Modal** : Aperçu produit sans quitter la page (galerie, variations, AJAX cart)

**Focal Point Picker** : Sélecteur visuel du point focal pour hero /nos-creations/

**Galerie Variations** : Quand une variation est sélectionnée, son image s'intègre dans la galerie produit
- Image de variation devient l'image principale
- Première vignette remplacée par l'image de variation
- Navigation complète préservée (flèches, clics)
- Restauration automatique à la désélection

**ACF Pro — Galerie produit flexible** (mars 2026)
- Repeater `galerie_produit` : types ambiance / détail / taille / client / fabrication (max 15 photos)
- Vidéo oEmbed `video_produit` : URL YouTube/Vimeo → miniature avec picto play en 1ère vignette
- Helper `sapi_get_product_photos($post_id, $type, $limit)` — fallback automatique vers anciens champs
- Helper `sapi_get_video_thumbnail($url)` — miniatures YouTube/Vimeo avec cache Vimeo
- Photos "client" exclues de la galerie principale (section dédiée uniquement)

**Robin Conseiller V2** (en prod, mars 2026)
- Questionnaire 7 étapes → 192 textes pré-générés → recommandation avec rideau cinématique
- Feature flag : `SAPI_ROBIN_V2` dans functions.php
- Filtre "Ma sélection" sur /nos-creations/ via ?robin_selection=1
- ⚠️ À affiner : présentation du sur-mesure dans la proposition finale

➡️ **Voir CUSTOMIZATIONS.md pour le détail complet de chaque vague**

---

## 🐛 PROBLÈMES CONNUS ET RÉSOLUS

### ✅ Police Safari (RÉSOLU)
- **Problème :** Square Peg = Zapfino (illisible)
- **Solution :** Police hébergée localement + preload

### ✅ Bug panier "produit statique" (RÉSOLU)
- **Problème :** Panier affichait toujours le même produit
- **Solution :** Page Panier avait du contenu statique → remplacé par bloc WooCommerce Cart

### ✅ Images ne s'affichent pas (RÉSOLU)
- **Problème :** URLs hardcodées vers production
- **Solution :** URLs corrigées vers testlumineux

### ✅ CSS spécificité `.is-filtered-out` (RÉSOLU)
- **Problème :** `!important` ne suffisait pas
- **Solution :** Triple approche (overflow + flex-wrap + nth-child)

### ✅ Images non centrées sur mobile (RÉSOLU — mars 2026)
- **Problème :** WooCommerce force `height: auto` sur toutes les images (`.woocommerce img`), cassant `object-fit: cover`
- **Solution :** `height: 100% !important` sur `.client-photo-image` et `.product-mini-card-img`

### ✅ Tracking conversions Google Ads inactif (RÉSOLU — mars 2026)
- **Problème :** Déclencheur GTM pointait vers l'ancienne URL Elementor `/check-out-confirmation`
- **Solution :** Déclencheur → événement `purchase`, variables → data layer `ecommerce.value` / `ecommerce.transaction_id`

### ⚠️ Google for WooCommerce — connexion Google Ads cassée (NON RÉSOLU)
- Erreur API au moment du setup, bouton Continue grisé
- Impact limité : les conversions passent par GTM. Manque Enhanced Conversions et remarketing dynamique.
- À retenter lors d'une prochaine mise à jour du plugin

➡️ **Voir CUSTOMIZATIONS.md section "Historique des Modifications" pour tous les détails**

---

## ✅ CHECKLIST AVANT COMMIT

```markdown
- [ ] Pas de console.log() oubliés
- [ ] Tous les echo sont échappés
- [ ] Testé sur mobile (iPhone Safari minimum)
- [ ] Pas d'URL de staging hardcodée
- [ ] CUSTOMIZATIONS.md mis à jour si changement notable
```

---

## 📚 DOCUMENTATION COMPLÈTE

**Pour l'historique détaillé, les leçons apprises, et le contexte complet :**

➡️ **Lire `CUSTOMIZATIONS.md`** (~350 lignes de documentation)

Ce fichier contient :
- Workflow détaillé Local → GitHub → O2switch
- Historique complet de chaque modification avec dates
- Leçons apprises (ce qui n'a PAS marché)
- CSS Design System complet avec toutes les variables
- Détail des 5 vagues d'harmonisation premium
- Documentation technique de chaque fonctionnalité

---

## 🧠 GESTION MÉMOIRE

La mémoire est stockée dans `~/.claude/projects/.../memory/`. Elle persiste entre les conversations.

### Quand ÉCRIRE dans la mémoire
1. **Décision d'architecture** — ex: "on utilise un repeater plutôt que des champs fixes"
2. **Piège technique découvert** — ex: "WooCommerce force height:auto sur les images"
3. **Préférence de Robin** — ex: "je préfère créer les champs ACF moi-même"
4. **Chantier terminé** — noter ce qui a été fait et ce qui reste
5. **Planification future** — quelque chose à faire plus tard

### Quand NE PAS écrire
- Détails d'implémentation (c'est dans le code/git)
- Discussions en cours qui n'ont pas abouti à une décision
- Infos dérivables du code ou de git log

### Degré de précision
- Toujours noter le **pourquoi** d'une décision, pas juste le quoi
- Pour un bug résolu, noter la **cause racine** (pas les tentatives ratées)

### En DÉBUT de conversation
- Si Robin parle d'un sujet qui a un fichier mémoire, le lire avant de répondre

### En FIN de session
- Checkpoint : "Est-ce qu'il y a des décisions/découvertes à sauvegarder ?"
- Nettoyer le fichier de session temporaire s'il existe
- Les projets terminés → une ligne dans "Historique notable" de MEMORY.md, fichier supprimé

### Organisation des fichiers
- **feedback_*.md** — préférences de travail (permanent, rarement obsolète)
- **project_*.md** — chantiers en cours avec date de dernière MAJ
- **reference_*.md** — outils et ressources externes
- Chaque fichier a une `date_updated` dans le frontmatter. Si > 2 mois, signaler à Robin.

---

**Fin du fichier CLAUDE.md**

*Ce fichier = référence rapide. CUSTOMIZATIONS.md = documentation complète.*

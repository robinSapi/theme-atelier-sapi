# Documentation des Customisations - Thème Sâpi Maison

**Date de création:** 2025-02-04
**Contexte:** Migration du travail de Jérôme (Elementor) vers un thème custom

---

## 🚀 Workflow de Déploiement

```
Local (dossier projet) → GitHub → O2switch (hébergeur)
```

**Repository GitHub:** `https://github.com/robinSapi/testLumineux-atelier-sapi`

**Branche de travail:** `test-theme-sapi-maison` (NE PAS push sur main/master)

**Environnements:**
- **Production:** `https://atelier-sapi.fr` (site live)
- **Pré-production:** `https://testlumineux.atelier-sapi.fr` (tests)
- **Local:** `/Users/samuel/Local/atelier-sapi` (développement)

**Process:**
1. Modifications en local
2. `git push origin test-theme-sapi-maison`
3. Déploiement automatique sur testlumineux via O2switch
4. Test sur testlumineux
5. Si OK → déploiement en production

**Important:** Ne jamais tester en local avec une base de données - tout test se fait via push sur testlumineux.

---

## ⚠️ Éléments Non-Standards (mais qui fonctionnent)

### 1. Template WooCommerce `single-product.php`

**Statut actuel (2026-02-04):** Template maintenant conforme aux standards WooCommerce.

**Structure actuelle:**
```php
<?php the_post(); ?>
<?php global $product; ?>
<?php
if (!$product || !is_a($product, 'WC_Product')) {
  $product = wc_get_product(get_the_ID());
}
?>
<?php do_action('woocommerce_before_single_product'); ?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>
  <!-- contenu custom (sections hero, details, FAQ, etc.) -->
</div>
<?php do_action('woocommerce_after_single_product'); ?>
```

**Historique:**
- Version Jérôme (Elementor) : sans standards WooCommerce
- 2025-02-04 : Tentative de standardisation → a cassé le panier → revert
- 2026-02-04 : Nouvelle tentative avec `global $product` + `wc_product_class()` → **à tester**

**Action si problème:** Si l'ajout au panier cesse de fonctionner, comparer avec la version production.

---

### 2. URLs d'images hardcodées vers production

**Fichiers concernés:**
- `woocommerce/taxonomy-product_cat.php` (lignes 45-192)
- `woocommerce/archive-product.php`
- `front-page.php`
- `page-*.php`

**Problème:** Les images utilisent des URLs absolues vers `https://www.atelier-sapi.fr/` au lieu de chemins relatifs ou `wp_get_attachment_url()`.

**Exemple:**
```php
'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/10/Bandeau.jpg',
```

**Impact:**
- ✅ Aucun impact fonctionnel (les images s'affichent)
- ⚠️ Si les images sont modifiées sur la prod, elles seront automatiquement mises à jour partout
- ⚠️ Dépendance au domaine de production

**Action future:** Si vous voulez des images différentes entre testlumineux et prod, il faudra remplacer ces URLs par des uploads WordPress gérés via la médiathèque.

---

### 3. Redirections des pages statiques vers catégories WooCommerce

**Fichier:** `functions.php` (lignes 224-250)

**Contexte:** Jérôme avait créé des pages WordPress statiques pour les catégories (avec Elementor) au lieu d'utiliser les taxonomies WooCommerce natives.

**Solution:** Redirections 301 automatiques
```php
'nos-lampadaires' => 'lampadaire'  // page statique → catégorie WooCommerce
```

**⚠️ ATTENTION:** Les slugs des catégories sont au **singulier** (lampadaire, suspension, etc.), pas pluriel. Ne pas modifier sans vérifier la base de données.

**Action future:** Ces pages statiques peuvent être supprimées si les redirections fonctionnent bien depuis plusieurs mois.

---

### 4. Meta SKU et catégories supprimées

**Fichier:** `functions.php` (ligne 253)

```php
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
```

**Raison:** Le client ne veut pas afficher "UGS: XXX" et les catégories sur les pages produit.

**Action future:** Si besoin de réactiver, commenter cette ligne.

---

## ✅ Modifications Standards (bonnes pratiques)

### 1. Support WooCommerce Gallery

**Fichier:** `functions.php` (lignes 18-20)

```php
add_theme_support('wc-product-gallery-zoom');
add_theme_support('wc-product-gallery-lightbox');
add_theme_support('wc-product-gallery-slider');
```

**Standard:** ✅ Recommandé par WooCommerce

---

### 2. Fragments de panier

**Fichier:** `functions.php` (lignes 256-263)

**Raison:** Met à jour le compteur du panier dans le header après AJAX.

**Standard:** ✅ Méthode officielle WooCommerce

---

### 3. Template hiérarchie

**Fichiers créés:**
- `woocommerce/single-product.php` (remplace le template WooCommerce)
- `woocommerce/taxonomy-product_cat.php` (remplace les archives catégories)
- `woocommerce/content-product.php` (remplace la boucle produits)

**Standard:** ✅ Méthode officielle WordPress/WooCommerce pour customiser les templates

---

## 🔧 Points d'Attention pour le Futur

### Avant une mise à jour WooCommerce majeure:

1. **Tester l'ajout au panier** sur un environnement de test
2. Si ça casse, le problème est probablement dans `single-product.php`
3. Vérifier que les hooks WooCommerce n'ont pas changé
4. Vérifier la [doc officielle des templates](https://woocommerce.com/document/template-structure/)

### Si vous engagez un autre développeur:

1. Lui faire lire ce fichier AVANT de "corriger" le code
2. Les customisations non-standards sont **volontaires** et **fonctionnelles**
3. Ne pas "corriger" selon les standards sans tests approfondis

### Si l'ajout au panier cesse de fonctionner:

1. Vérifier `single-product.php` (voir section 1 ci-dessus)
2. Désactiver tous les plugins sauf WooCommerce pour isoler
3. Comparer avec la version en production qui fonctionne
4. Vérifier les sessions PHP et cookies navigateur

---

## ✅ Problèmes Résolus

### Bug panier "Olivia" sur testlumineux

**Statut:** ✅ RÉSOLU (2026-02-04)

**Problème:** Le panier affichait toujours "Olivia La gardiena" peu importe le produit ajouté.

**Cause:** La page "Mon Panier" (ID 3554) contenait du contenu statique hardcodé par Jérôme (tableau avec Olivia) au lieu du bloc WooCommerce Cart dynamique.

**Solution appliquée:**
1. Éditer la page "Mon Panier" dans l'admin WordPress
2. Supprimer tout le contenu statique (bloc Classique avec tableau Olivia)
3. Ajouter le bloc **WooCommerce Cart** (pas le shortcode en texte)
4. Enregistrer

**Leçon apprise:** Les pages WooCommerce (Panier, Commande, Mon compte) doivent utiliser les **blocs WooCommerce natifs** ou le shortcode dans un **bloc Shortcode dédié**, pas du texte brut dans un paragraphe.

**Pages WooCommerce à vérifier si problème similaire:**
- Panier : doit contenir bloc "Cart" ou `[woocommerce_cart]`
- Validation de commande : bloc "Checkout" ou `[woocommerce_checkout]`
- Mon compte : bloc "My Account" ou `[woocommerce_my_account]`

---

## 📝 Historique des Modifications

**2026-02-04:**
- ✅ Ajout section workflow déploiement (Local → GitHub → O2switch)
- ✅ Nouvelle tentative standardisation `single-product.php` : ajout `global $product` + wrapper `wc_product_class()`
- ✅ Ajout chargement `wc-cart-fragments` et `wc-add-to-cart-variation` dans functions.php
- ✅ Ajout filtre `woocommerce_add_to_cart_fragments` pour mise à jour compteur panier
- ✅ **BUG PANIER RÉSOLU** : page "Mon Panier" contenait du contenu statique hardcodé → remplacé par bloc WooCommerce Cart

**2025-02-04:**
- Création du thème custom depuis le travail Elementor de Jérôme
- Nettoyage du code debug
- Correction des slugs catégories (pluriel → singulier)
- Ajout support galerie WooCommerce
- ❌ Tentative de standardisation `single-product.php` → **échec, revert nécessaire**
- ✅ Ajout redirections pages statiques → catégories WooCommerce
- ✅ Création documentation CUSTOMIZATIONS.md
- ❌ Problème panier sur testlumineux identifié - **cause: environnement, pas code**

---

**Pour mettre à jour ce fichier:** Documentez toute modification qui s'écarte des standards WordPress/WooCommerce avec la raison et l'impact.

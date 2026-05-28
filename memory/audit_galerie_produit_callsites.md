# Audit call-sites repeater `galerie_produit` — Phase 0 S28
**Date audit :** 2026-05-28
**Branche :** feature/photos-par-piece
**Commit base :** 4d606df (Merge test-theme-sapi-maison → master : card sur-mesure variante D + queue Cowork)

## Résumé

- **Nombre total de call-sites** : 25 (5 lectures directes du repeater + 17 appels indirects via le helper `sapi_get_product_photo_ids` / `sapi_get_product_photos` + 3 commentaires / docs hors code actif)
- **Fichiers PHP impactés** : 8
  - `functions.php` (helper + 2 appels indirects + 1 doc-block)
  - `woocommerce/single-product.php` (3 lectures directes + 1 appel indirect)
  - `woocommerce/archive-product.php` (1 appel indirect)
  - `woocommerce/taxonomy-product_cat.php` (4 appels indirects)
  - `woocommerce/content-product.php` (1 appel indirect)
  - `page-inspiration.php` (1 lecture directe)
  - `page-la-star-du-moment.php` (2 appels indirects)
  - `front-page.php` (3 appels indirects + 1 commentaire)
- **Fichiers JS impactés** : 0 — aucun JS du thème ne référence `galerie_produit` ni `type_photo`. Pas de `wp_localize_script` qui pousse le repeater. Pas de `data-galerie*`. La consommation est 100 % côté PHP côté serveur (HTML rendu).
- **Snippets Code Snippets impactés** : non auditable depuis le repo. La mémoire Cowork [reference_snippets_actifs] n'est pas accessible depuis cette session ; Robin devra confirmer côté admin si un snippet manipule `galerie_produit` (probable : non, vu que le repeater est lu uniquement pour le rendu).
- **API ACF repeater (`have_rows` / `the_row` / `get_sub_field`)** : **aucune occurrence** dans tout le repo. Tous les accès passent par `get_field('galerie_produit', $post_id)` qui renvoie un tableau, parcouru via `foreach` brut. Bonne nouvelle pour la migration : un seul motif d'accès à remplacer.
- **API meta WP brut (`get_post_meta` / `update_post_meta`)** : **aucune** occurrence ciblant `galerie_produit`. Pas de meta_query non plus. Le repeater n'est jamais utilisé comme filtre WP_Query.

## Par catégorie d'usage

### Lecture meta directe (`get_post_meta`, `get_field`)

**5 call-sites** lisent directement le repeater via `get_field('galerie_produit', …)` :

- `functions.php:1046` — dans le helper `sapi_get_product_photo_ids()` (centralisateur)
- `page-inspiration.php:30` — lecture brute dans la page inspiration (n'utilise pas le helper, fait sa propre boucle)
- `woocommerce/single-product.php:104` — galerie principale (acf_photos[])
- `woocommerce/single-product.php:135` — galerie thumbnails (gallery_acf_photos[])
- `woocommerce/single-product.php:175` — slideshow ambiance (slideshow_photos[])

### Boucles repeater (`have_rows` / `the_row` / `get_sub_field`)

**0 call-site.** Aucun usage de l'API itérative ACF. Tous les call-sites du point précédent font un `foreach` sur le tableau renvoyé par `get_field()`. Donc l'API consommée par le thème est : *tableau plat de rows*, chaque row contenant `image` (ID / array / URL ACF) + `type_photo` (string ou `['value' => …]`).

### Helpers existants

**3 helpers PHP** dans `functions.php` qui encapsulent la logique :

- `sapi_get_product_photo_ids($post_id, $type, $limit)` — `functions.php:1042` — **le seul helper qui lit `galerie_produit`** ; renvoie un array d'IDs d'attachment filtré par `type_photo`. C'est le wrapper canonique.
- `sapi_get_product_photos($post_id, $type, $limit, $size)` — `functions.php:1073` — wrapper du précédent qui renvoie des URLs au lieu d'IDs.
- `sapi_get_acf_image_id($field_value)` — `functions.php:1127` — utilitaire générique pour extraire l'ID d'un champ image ACF (toutes formes : array, ID numérique, URL). Utilisé par `sapi_get_product_photo_ids` ET par les 4 lectures directes (page-inspiration + single-product ×3). N'a aucune dépendance au nom du repeater — restera intact en Phase 2.

**17 appels indirects** au helper :
- `functions.php:184` — preload LCP carousel home
- `functions.php:5261` — bandeau ambiance card variante D (sur-mesure)
- `front-page.php:41` — image principale carousel home (par catégorie)
- `front-page.php:132` — image star du moment (detail)
- `front-page.php:236` — image card "featured" home
- `front-page.php:309` — image collection (3e ambiance) sur home
- `page-la-star-du-moment.php:50` — pool ambiance de la page star
- `page-la-star-du-moment.php:51` — pool detail de la page star
- `woocommerce/single-product.php:119` — fallback dans la galerie principale (déjà appelé depuis `$acf_photos` quand le repeater est vide)
- `woocommerce/single-product.php:558` — photo client (bandeau preuve sociale testimonials)
- `woocommerce/content-product.php:127` — pages catégories : ambiance 1 en image principale + ambiance 2 en hover
- `woocommerce/archive-product.php:364` — page /nos-creations/ : ambiance card produit (sauf accessoires)
- `woocommerce/taxonomy-product_cat.php:79` — mini-carousel hero catégorie (ambiance ×6)
- `woocommerce/taxonomy-product_cat.php:80` — mini-carousel hero catégorie (detail ×6)
- `woocommerce/taxonomy-product_cat.php:178` — fond ambiance hero catégorie (fabrication pool)
- `woocommerce/taxonomy-product_cat.php:371` — vignette pour catégorie liée (croisements)

### Rendu front (templates WC, single-product, archive, etc.)

Tous les call-sites listés ci-dessus sont du rendu front (HTML produit). Décomposition par template :

- **`single-product.php`** : 3 lectures directes (galerie principale, thumbnails, slideshow) + 1 fallback indirect + 1 photo client → **template le plus impacté**.
- **`taxonomy-product_cat.php`** : 4 appels indirects (mini-carousel, fabrication, vignette croisée). Photos consommées : `ambiance`, `detail`, `fabrication`.
- **`archive-product.php`** : 1 appel indirect (ambiance card grille /nos-creations/).
- **`content-product.php`** : 1 appel indirect (ambiance hover card en pages catégories).
- **`page-inspiration.php`** : 1 lecture directe (boucle sur 200 produits max, filtre `ambiance` + `detail`). N'utilise PAS le helper — duplique la logique de parcours.
- **`page-la-star-du-moment.php`** : 2 appels indirects (`ambiance` + `detail`).
- **`front-page.php`** : 3 appels indirects (carousel home, star home, featured home, collection home).

### JS / AJAX

**0 call-site.** Aucun fichier dans `assets/*.js` ne référence `galerie_produit`, `type_photo`, `data-galerie`. Aucun `wp_localize_script` ne pousse les données du repeater au JS. Les seules occurrences "galerie" dans le JS / CSS / HTML sont des classes ou commentaires UI sans rapport (`star-galerie`, `Dots pagination galerie mobile`, etc.).

### Admin / WP-Admin

**0 call-site code actif.** Aucune métabox custom, aucune colonne admin produit, aucun handler AJAX qui manipule `galerie_produit`. Le seul touchpoint admin est :

- `functions.php:6308-6319` — **doc-block** qui décrit la structure ACF attendue (champ Repeater créé manuellement via ACF Pro UI, pas registré en PHP). Pas de code exécuté.

Conséquence : la migration Phase 2 ne touchera rien côté admin du thème. La création des 8 Gallery ACF + taxonomie `media_room` (Phase 1, hors scope) restera 100 % gérée par l'UI ACF/WordPress.

## Détail par call-site

### [#1] `functions.php:1042-1062` — Helper canonique (lecture repeater)
**Contexte** : helper réutilisé partout dans le thème.
**Pattern matché** : `get_field('galerie_produit', $post_id)` + foreach + lecture `type_photo` + `image`.
**Extrait** :
```php
function sapi_get_product_photo_ids($post_id, $type = '', $limit = 0) {
  if (!function_exists('get_field')) return [];

  $ids = [];
  $galerie = get_field('galerie_produit', $post_id);

  if (!empty($galerie) && is_array($galerie)) {
    foreach ($galerie as $row) {
      $row_type = isset($row['type_photo']) ? $row['type_photo'] : '';
      if (is_array($row_type)) $row_type = isset($row_type['value']) ? $row_type['value'] : '';
      if ($type && $row_type !== $type) continue;
      $id = sapi_get_acf_image_id(isset($row['image']) ? $row['image'] : null);
      if ($id) {
        $ids[] = $id;
        if ($limit > 0 && count($ids) >= $limit) break;
      }
    }
  }

  return $ids;
}
```
**Note** : seul point d'entrée canonique pour récupérer les IDs photos par type. Statut **actif**.
**Impact migration** : **fort** — c'est LE call-site à refactor pour le pattern dual-read (Phase 3). Une fois ce helper migré vers les 8 Gallery ACF, les 17 appels indirects (#9–#25) suivent gratuitement.

### [#2] `functions.php:1034` — Commentaire helper
**Contexte** : doc-block du helper.
**Pattern matché** : `galerie_produit` dans un commentaire (`* Helper: get photo URLs from galerie_produit repeater by type.`).
**Extrait** :
```php
/**
 * Helper: get photo URLs from galerie_produit repeater by type.
 * Returns array of URLs matching the given type, or all photos if no type specified.
 * ...
 */
```
**Note** : juste un commentaire — pas de code exécuté. Statut **doc**.
**Impact migration** : **faible** — à mettre à jour quand le helper sera refactor.

### [#3] `functions.php:6308-6319` — Doc-block structure ACF
**Contexte** : commentaire descriptif dans `functions.php`, juste après un handler `manage_projet_sur_mesure_posts_custom_column`.
**Pattern matché** : `galerie_produit` dans un commentaire (`*   - galerie_produit   (Repeater)     → Photos supplémentaires`).
**Extrait** :
```php
/*
 * ACF fields for Product media (video + photo gallery repeater)
 * Created MANUALLY via ACF Pro UI — not registered in PHP.
 *
 * Field names expected by the template:
 *   - video_produit     (oEmbed)       → URL YouTube/Vimeo
 *   - galerie_produit   (Repeater)     → Photos supplémentaires
 *     ├─ type_photo     (Select)       → ambiance / detail / taille / client / fabrication
 *     └─ image          (Image, array) → Photo
 *
 * Location: Post Type = product
 */
```
**Note** : doc-block qui sert de référence pour Robin sur la structure ACF attendue. Aucune fonction associée — c'est un commentaire orphelin. Statut **doc**.
**Impact migration** : **faible** — à réécrire en Phase 1 pour documenter les 8 nouveaux Gallery ACF + la taxonomie `media_room`.

### [#4] `page-inspiration.php:28-44` — Lecture brute (boucle sur 200 produits)
**Contexte** : template galerie inspiration (`Template Name: Galerie Inspiration`). Parcourt 200 produits max et collecte toutes les photos `ambiance` + `detail`.
**Pattern matché** : `get_field('galerie_produit', $product_id)` — **n'utilise PAS le helper**, réimplémente la boucle.
**Extrait** :
```php
if ($products_query->have_posts() && function_exists('get_field')) {
  foreach ($products_query->posts as $product_id) {
    $galerie = get_field('galerie_produit', $product_id);
    if (empty($galerie) || !is_array($galerie)) continue;
    foreach ($galerie as $row) {
      $type = isset($row['type_photo']) ? $row['type_photo'] : '';
      if (is_array($type)) $type = isset($type['value']) ? $type['value'] : '';
      if ($type !== 'ambiance' && $type !== 'detail') continue;
      $img_id = sapi_get_acf_image_id(isset($row['image']) ? $row['image'] : null);
      if (!$img_id) continue;
      $photos[] = [
        'attachment_id' => $img_id,
        'product_id'    => $product_id,
      ];
    }
  }
}
```
**Note** : duplique la logique de `sapi_get_product_photo_ids` parce qu'on a besoin du `product_id` à côté de l'`attachment_id`. Statut **actif**.
**Impact migration** : **moyen** — un call-site à part qui ne bénéficiera pas du refactor du helper. Soit on étend le helper pour exposer aussi le `product_id`, soit on duplique l'effort. À noter pour Phase 1 (recommandation : factoriser un helper bas niveau `sapi_iterate_product_photos($post_id, $types)` qui yield `[$type, $img_id]`).

### [#5] `woocommerce/single-product.php:103-125` — Galerie principale (acf_photos[])
**Contexte** : section galerie du template single-product, construit le tableau `$acf_photos[]` pour le rendu de la galerie principale (avec label par type).
**Pattern matché** : `get_field('galerie_produit')` + foreach + lecture `type_photo` + exclusion `client`.
**Extrait** :
```php
if (function_exists('get_field')) {
  $galerie_repeater = get_field('galerie_produit');
  if (!empty($galerie_repeater) && is_array($galerie_repeater)) {
    foreach ($galerie_repeater as $row) {
      $img_field = isset($row['image']) ? $row['image'] : null;
      $img_id = sapi_get_acf_image_id($img_field);
      $url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : '';
      if ($url) {
        $type = isset($row['type_photo']) ? $row['type_photo'] : 'ambiance';
        if (is_array($type)) $type = isset($type['value']) ? $type['value'] : 'ambiance';
        if ($type === 'client') continue; // Photos client exclues de la galerie
        $acf_photos[] = ['url' => $url, 'label' => isset($type_labels[$type]) ? $type_labels[$type] : ucfirst($type), 'id' => $img_id];
      }
    }
  } else {
    // Fallback: use helper which reads old fixed fields
    $all_photo_ids = sapi_get_product_photo_ids(get_the_ID());
    foreach ($all_photo_ids as $photo_id) {
      $photo_url = wp_get_attachment_image_url($photo_id, 'full');
      if ($photo_url) $acf_photos[] = ['url' => $photo_url, 'label' => 'Photo', 'id' => $photo_id];
    }
  }
}
```
**Note** : à la différence du helper, garde le **label humain** (ambiance / détail / etc.) par photo pour l'affichage. Exclut les photos `client` (réservées au bandeau testimonials, voir #19). Statut **actif**.
**Impact migration** : **fort** — un des 3 plus gros call-sites du single-product. Le mapping type→label devra être conservé après migration (chaque Gallery ACF aura déjà son type implicite via le nom de champ — le label sera dérivé du field key).

### [#6] `woocommerce/single-product.php:134-164` — Thumbnails (gallery_acf_photos[])
**Contexte** : sélection ambiance 1 + types `taille` + `accessoires` pour les slides supplémentaires de la galerie principale.
**Pattern matché** : `get_field('galerie_produit')` + **deux passes** sur le tableau (ambiance puis taille/accessoires).
**Extrait** :
```php
$galerie_repeater_gal = get_field('galerie_produit');
if (!empty($galerie_repeater_gal) && is_array($galerie_repeater_gal)) {
  // D'abord : première photo ambiance
  foreach ($galerie_repeater_gal as $row) {
    $type = isset($row['type_photo']) ? $row['type_photo'] : '';
    if (is_array($type)) $type = isset($type['value']) ? $type['value'] : '';
    if ($type !== 'ambiance') continue;
    $img_field = isset($row['image']) ? $row['image'] : null;
    $img_id = sapi_get_acf_image_id($img_field);
    $url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : '';
    if ($url) {
      $gallery_acf_photos[] = ['url' => $url, 'label' => 'Ambiance', 'id' => $img_id];
      $ambiance_added = true;
      break; // Seulement la première
    }
  }
  // Ensuite : taille + accessoires
  foreach ($galerie_repeater_gal as $row) {
    ...
    if ($type !== 'taille' && $type !== 'accessoires') continue;
    ...
  }
}
```
**Note** : `accessoires` est lu ici alors que le doc-block (#3) ne le liste pas dans `type_photo` (ambiance / detail / taille / client / fabrication). Cohérence à vérifier — soit le doc-block est obsolète, soit le code lit un type qui ne sera jamais présent. Statut **actif**.
**Impact migration** : **fort** — à refactor en Phase 3. Pas de boucle nécessaire après migration : on lira directement le Gallery ACF "ambiance" (limit 1) + le Gallery "taille" + (le Gallery "accessoires" s'il existe — sinon à clarifier avec Robin avant Phase 1).

### [#7] `woocommerce/single-product.php:172-191` — Slideshow ambiance (slideshow_photos[])
**Contexte** : slideshow "Stories" mobile en intro produit. Tri par ordre `['ambiance', 'vue de dessous', 'detail', 'fabrication']`.
**Pattern matché** : `get_field('galerie_produit')` + double foreach (slideshow_types × rows).
**Extrait** :
```php
$slideshow_types = ['ambiance', 'vue de dessous', 'detail', 'fabrication'];
$slideshow_photos = [];
if (function_exists('get_field')) {
  $galerie_repeater_ss = get_field('galerie_produit');
  if (!empty($galerie_repeater_ss) && is_array($galerie_repeater_ss)) {
    foreach ($slideshow_types as $ss_type) {
      foreach ($galerie_repeater_ss as $row) {
        $type = isset($row['type_photo']) ? $row['type_photo'] : '';
        if (is_array($type)) $type = isset($type['value']) ? $type['value'] : '';
        if ($type !== $ss_type) continue;
        $img_field = isset($row['image']) ? $row['image'] : null;
        $img_id = sapi_get_acf_image_id($img_field);
        if ($img_id) {
          $slideshow_photos[] = $img_id;
        }
      }
    }
  }
}
```
**Note** : `'vue de dessous'` (avec espaces et accent) est un type qui n'est PAS dans le doc-block (#3) ni dans `$type_labels` de #5. À nouveau, soit le doc-block est incomplet, soit ce type n'a jamais été utilisé. Statut **actif**.
**Impact migration** : **fort** — call-site qui dépend de l'**ordre** des types. Après migration vers 8 Gallery ACF distincts, l'ordre sera contrôlé en PHP au lieu d'être contenu dans le repeater. À refactor proprement en Phase 3.

### [#8] `front-page.php:304` — Commentaire image collection
**Contexte** : commentaire devant l'appel à `sapi_get_product_photo_ids` (#15).
**Pattern matché** : `galerie_produit` dans un commentaire (`// Image collection : 3ème photo ambiance du repeater galerie_produit`).
**Extrait** :
```php
// Image collection : 3ème photo ambiance du repeater galerie_produit
// (produit "preferred" si défini, sinon 1er produit de la catégorie),
// fallback sur la dernière ambiance disponible, puis vignette WC en dernier recours.
$target_id = $preferred_id ?: $fallback_id;
if ($target_id) {
  $amb_photo_ids = sapi_get_product_photo_ids($target_id, 'ambiance');
```
**Note** : juste un commentaire. Statut **doc**.
**Impact migration** : **faible** — à reformuler en Phase 3 (le concept de "3e photo ambiance" peut rester, mais le mot "repeater" devra être remplacé par "Gallery ACF ambiance").

### [#9] `functions.php:184` — Preload LCP home carousel
**Contexte** : `<head>` du thème (`wp_head` hook), genère un `<link rel="preload">` pour la première image du carousel home.
**Pattern matché** : `sapi_get_product_photo_ids($q->posts[0], 'ambiance', 1)`
**Extrait** :
```php
if ($q->have_posts()) {
  $lcp_ids = sapi_get_product_photo_ids($q->posts[0], 'ambiance', 1);
  if (!empty($lcp_ids)) {
    $lcp_id = $lcp_ids[0];
    $lcp_src = wp_get_attachment_image_url($lcp_id, 'full');
    $lcp_srcset = wp_get_attachment_image_srcset($lcp_id, 'full');
    echo '<link rel="preload" href="' . esc_url($lcp_src) . '" as="image" fetchpriority="high"';
```
**Note** : appel critique perf (LCP carousel home). Statut **actif**.
**Impact migration** : **faible** — passera par le helper refactoré gratuitement.

### [#10] `functions.php:5261` — Bandeau ambiance card variante D
**Contexte** : rendu de la card sur-mesure variante D, récupère la 2e photo ambiance pour un bandeau pleine largeur.
**Pattern matché** : `sapi_get_product_photos($pid, 'ambiance', 2)`
**Extrait** :
```php
// Ambiance photo for full-width banner
$pid = $product->get_id();
$ambiance_photos = sapi_get_product_photos($pid, 'ambiance', 2);
// Prefer second ambiance photo, fallback to first
$ambiance_url = isset($ambiance_photos[1]) ? $ambiance_photos[1] : (isset($ambiance_photos[0]) ? $ambiance_photos[0] : '');
```
**Note** : utilise `sapi_get_product_photos` (URLs) au lieu de `_ids`. Statut **actif**.
**Impact migration** : **faible** — gratuit via refactor du helper.

### [#11] `front-page.php:41` — Carousel home image principale par catégorie
**Contexte** : boucle par catégorie sur la home, première photo ambiance pour la slide carousel.
**Pattern matché** : `sapi_get_product_photo_ids(get_the_ID(), 'ambiance', 1)`
**Extrait** :
```php
if ($product) {
  $photo_ids = sapi_get_product_photo_ids(get_the_ID(), 'ambiance', 1);
  $image_id = !empty($photo_ids) ? $photo_ids[0] : 0;

  if ($image_id) {
```
**Note** : statut **actif**.
**Impact migration** : **faible** — via helper.

### [#12] `front-page.php:132` — Star du moment (detail)
**Contexte** : 1re photo detail du produit "star du moment" sur la home.
**Pattern matché** : `sapi_get_product_photo_ids($star_id, 'detail', 1)`
**Extrait** :
```php
$detail_photo_ids = sapi_get_product_photo_ids($star_id, 'detail', 1);
$star_image_id = !empty($detail_photo_ids) ? $detail_photo_ids[0] : 0;
if (!$star_image_id) {
  $star_image_id = get_post_thumbnail_id($star_id);
}
```
**Note** : fallback sur `post_thumbnail_id` si pas de detail. Statut **actif**.
**Impact migration** : **faible** — via helper.

### [#13] `front-page.php:236` — Card featured home
**Contexte** : section "featured products" sur la home, photo detail.
**Pattern matché** : `sapi_get_product_photo_ids(get_the_ID(), 'detail', 1)`
**Extrait** :
```php
if ($product) {
  $detail_photo_ids = sapi_get_product_photo_ids(get_the_ID(), 'detail', 1);
  $featured_image_id = !empty($detail_photo_ids) ? $detail_photo_ids[0] : 0;

  if ($featured_image_id) {
```
**Note** : statut **actif**.
**Impact migration** : **faible** — via helper.

### [#14] `front-page.php:309` — Collection home (3e ambiance)
**Contexte** : image de la section "Collections" sur la home (3e photo ambiance comme préférée, fallback sur la dernière).
**Pattern matché** : `sapi_get_product_photo_ids($target_id, 'ambiance')` (sans limit, on lit toutes les ambiances)
**Extrait** :
```php
if ($target_id) {
  $amb_photo_ids = sapi_get_product_photo_ids($target_id, 'ambiance');
  if (!empty($amb_photo_ids)) {
    $col_image_id = isset($amb_photo_ids[2]) ? $amb_photo_ids[2] : end($amb_photo_ids);
  } else {
    $col_image_id = get_post_thumbnail_id($target_id);
  }
}
```
**Note** : dépend du **nombre** de photos ambiance (indexe la 3e). Après migration, ce sera la 3e image du Gallery ACF "ambiance". Statut **actif**.
**Impact migration** : **faible** — via helper, sémantique préservée.

### [#15] `page-la-star-du-moment.php:50-51` — Page Star du moment (pools)
**Contexte** : template `page-la-star-du-moment.php`, récupère **tous** les IDs ambiance et detail du produit star pour les répartir ensuite dans la page.
**Pattern matché** : 2 appels `sapi_get_product_photo_ids($star_id, 'ambiance'|'detail')` sans limit.
**Extrait** :
```php
// Photos ACF (repeater) — IDs pour wp_get_attachment_image()
$ambiance_photo_ids = sapi_get_product_photo_ids($star_id, 'ambiance');
$detail_photo_ids   = sapi_get_product_photo_ids($star_id, 'detail');

// Photo principale produit
$main_image_id = $star_product->get_image_id();

// Galerie produit
$gallery_ids = $star_product->get_gallery_image_ids();

// Hero = première ambiance ou image principale
$hero_id = !empty($ambiance_photo_ids[0]) ? $ambiance_photo_ids[0] : $main_image_id;
```
**Note** : commentaire littéral `(repeater)` à mettre à jour. Statut **actif**.
**Impact migration** : **faible** — via helper.

### [#16] `woocommerce/single-product.php:119` — Fallback galerie principale
**Contexte** : utilisé quand `$galerie_repeater` est vide (cf #5). Appelle le helper sans type pour récupérer toutes les photos.
**Pattern matché** : `sapi_get_product_photo_ids(get_the_ID())`
**Extrait** :
```php
} else {
  // Fallback: use helper which reads old fixed fields
  $all_photo_ids = sapi_get_product_photo_ids(get_the_ID());
  foreach ($all_photo_ids as $photo_id) {
    $photo_url = wp_get_attachment_image_url($photo_id, 'full');
    if ($photo_url) $acf_photos[] = ['url' => $photo_url, 'label' => 'Photo', 'id' => $photo_id];
  }
}
```
**Note** : ironique — le commentaire dit "reads old fixed fields" mais le helper lit le repeater. Code mort fonctionnellement (le repeater est rempli pour tous les produits actuels) mais reste **actif** au sens où il s'exécute si le repeater est vide. Statut **actif (chemin froid)**.
**Impact migration** : **moyen** — le commentaire trompeur sera à supprimer ; le fallback peut être conservé pendant la phase dual-read (Phase 3) et retiré en Phase 4 (cleanup).

### [#17] `woocommerce/single-product.php:558` — Photo client (bandeau testimonials)
**Contexte** : bandeau preuve sociale dans la section testimonials, photo aléatoire parmi les `client`.
**Pattern matché** : `sapi_get_product_photo_ids($product_id, 'client')` + `array_rand`.
**Extrait** :
```php
$client_photo_ids = sapi_get_product_photo_ids($product_id, 'client');
$bandeau_id = !empty($client_photo_ids) ? $client_photo_ids[array_rand($client_photo_ids)] : 0;
if ($bandeau_id) :
  $captions = [
    'Photo envoyée par une cliente',
    'Photo envoyée récemment par un client'
  ];
  $random_caption = $captions[array_rand($captions)];
```
**Note** : seul consommateur du type `client`. Cohérent avec l'exclusion `if ($type === 'client') continue;` dans #5. Statut **actif**.
**Impact migration** : **faible** — via helper.

### [#18] `woocommerce/content-product.php:127` — Pages catégories : ambiance 1 + 2
**Contexte** : card produit en pages catégorie WooCommerce (utilise `is_product_category()` pour basculer en mode "image ambiance" au lieu de l'image WC native).
**Pattern matché** : `sapi_get_product_photo_ids($product_id, 'ambiance', 2)`
**Extrait** :
```php
// Pages catégories : photo ambiance 1 en image principale, photo ambiance 2 en hover
$sapi_category_ambiance_id = 0;
if (is_product_category()) {
  $amb_ids = sapi_get_product_photo_ids($product_id, 'ambiance', 2);
  if (!empty($amb_ids)) {
    $sapi_category_ambiance_id = $amb_ids[0];
    $hover_image_id = isset($amb_ids[1]) ? $amb_ids[1] : 0;
  }
}
```
**Note** : statut **actif**.
**Impact migration** : **faible** — via helper.

### [#19] `woocommerce/archive-product.php:364` — /nos-creations/ card ambiance
**Contexte** : page /nos-creations/, photo ambiance pour la card (sauf accessoires qui gardent l'image WC native).
**Pattern matché** : `sapi_get_product_photo_ids($product_id, 'ambiance', 1)`
**Extrait** :
```php
// Photo ambiance ACF (sauf accessoires → photo produit WooCommerce)
$is_accessoire = in_array('accessoires', $cat_slugs);
$amb_photo_ids = !$is_accessoire ? sapi_get_product_photo_ids($product_id, 'ambiance', 1) : [];
$ambiance_id = !empty($amb_photo_ids) ? $amb_photo_ids[0] : get_post_thumbnail_id($product_id);
```
**Note** : statut **actif**.
**Impact migration** : **faible** — via helper.

### [#20] `woocommerce/taxonomy-product_cat.php:79` — Hero catégorie ambiance ×6
**Contexte** : mini-carousel hero des pages taxonomie, jusqu'à 6 photos ambiance.
**Pattern matché** : `sapi_get_product_photo_ids($pid, 'ambiance', 6)`
**Extrait** :
```php
$amb_ids    = sapi_get_product_photo_ids($pid, 'ambiance', 6);
$detail_ids = sapi_get_product_photo_ids($pid, 'detail',   6);
// Alternance ambiance / détail
$slide_ids = [];
$max = max(count($amb_ids), count($detail_ids));
for ($j = 0; $j < $max; $j++) {
    if (isset($amb_ids[$j]))    $slide_ids[] = $amb_ids[$j];
    if (isset($detail_ids[$j])) $slide_ids[] = $detail_ids[$j];
}
```
**Note** : statut **actif**.
**Impact migration** : **faible** — via helper.

### [#21] `woocommerce/taxonomy-product_cat.php:80` — Hero catégorie detail ×6
**Contexte** : voir #20, deuxième appel jumelé pour alternance ambiance/detail.
**Pattern matché** : `sapi_get_product_photo_ids($pid, 'detail', 6)`
**Extrait** : voir #20.
**Note** : statut **actif**.
**Impact migration** : **faible** — via helper.

### [#22] `woocommerce/taxonomy-product_cat.php:178` — Fabrication pool
**Contexte** : background ambiance hero catégorie, pool aléatoire parmi toutes les photos `fabrication` des produits de la catégorie.
**Pattern matché** : `sapi_get_product_photo_ids(get_the_ID(), 'fabrication')` dans une boucle WP_Query.
**Extrait** :
```php
$ambiance_bg_id = 0;
if ($bg_query->have_posts()) {
  $fabrication_pool = [];
  while ($bg_query->have_posts()) {
    $bg_query->the_post();
    $fab_photo_ids = sapi_get_product_photo_ids(get_the_ID(), 'fabrication');
    if (!empty($fab_photo_ids)) {
      $fabrication_pool = array_merge($fabrication_pool, $fab_photo_ids);
    }
  }
  if (!empty($fabrication_pool)) {
    $ambiance_bg_id = $fabrication_pool[array_rand($fabrication_pool)];
```
**Note** : seul consommateur en masse du type `fabrication` (avec #7 slideshow). Statut **actif**.
**Impact migration** : **faible** — via helper.

### [#23] `woocommerce/taxonomy-product_cat.php:371` — Vignette catégorie liée
**Contexte** : module "croisements catégorie", récupère la vignette d'une catégorie liée à partir du 1er produit (ambiance) sinon fallback `post_thumbnail`.
**Pattern matché** : `sapi_get_product_photo_ids( $pid, 'ambiance', 1 )`
**Extrait** :
```php
// Photo ambiance du repeater ACF (comme le hero)
$amb_ids = sapi_get_product_photo_ids( $pid, 'ambiance', 1 );
if ( ! empty( $amb_ids ) ) {
  $thumb_id = $amb_ids[0];
} else {
  // Fallback : image produit WooCommerce
  $thumb_id = get_post_thumbnail_id( $pid );
}
```
**Note** : commentaire à mettre à jour ("du repeater ACF" → "du Gallery ACF"). Statut **actif**.
**Impact migration** : **faible** — via helper.

### [#24 + #25] Helpers et utilitaires
**Contexte** : `sapi_get_product_photos` (`functions.php:1073`) et `sapi_get_acf_image_id` (`functions.php:1127`) — non comptés comme call-sites distincts car ils sont dans la mécanique du helper #1.
**Note** : `sapi_get_acf_image_id` est **agnostique du nom du champ** — il n'a pas besoin d'être migré. Il restera intact en Phase 3. `sapi_get_product_photos` est un simple wrapper d'IDs → URLs, idem.
**Impact migration** : **faible** — pas de modif directe.

## Synthèse risques migration

- **Surface concentrée** : 1 helper + 4 templates produit + 4 pages templates couvrent **tous** les call-sites. Pas de fan-out admin, pas de JS, pas de meta_query — la migration est circonscrite au rendu front PHP.
- **API uniforme** : 100 % des accès passent par `get_field()` + `foreach` array (aucun `have_rows`). Le pattern dual-read peut être uniformément remplacé.
- **Types consommés** : `ambiance` (8 call-sites), `detail` (4), `client` (1), `fabrication` (2), `taille` (1), `accessoires` (1, possiblement mort), `'vue de dessous'` (1, possiblement mort). Sur les 8 Gallery cibles, les types `accessoires` et `'vue de dessous'` sont à clarifier avec Robin avant Phase 1 (présents dans le code, absents du doc-block #3).
- **Call-site hors helper** : seulement `page-inspiration.php` (#4) duplique la logique de parcours pour préserver le `product_id`. À factoriser en Phase 1 (helper `sapi_iterate_product_photos`).
- **Photos "client" et "fabrication"** : usages isolés (testimonial + background catégorie). À ne pas oublier dans les 8 Gallery (Phase 1).

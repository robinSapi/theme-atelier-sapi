<?php
/**
 * Catalogue B2B (prescripteurs) — Temps 1.
 *
 * Source de données produits + mapping des caractéristiques + champs ACF
 * éditoriaux (Histoire / Bois). Cette couche est la SOURCE DE VÉRITÉ UNIQUE :
 * le rendu HTML de `page-catalogue.php` ET l'export PDF (Temps 2) rejoueront
 * exactement la même requête et le même mapping.
 *
 * Contrainte cardinale : ÉTANCHÉITÉ. Aucune donnée de prix n'est jamais
 * produite par cette couche (ni champ, ni méthode `get_price`), afin qu'aucun
 * prix ne puisse fuiter dans le HTML ou le JSON transmis au navigateur.
 */

if (!defined('ABSPATH')) exit;

/* =========================================================================
 * 1. Catégories du catalogue (slugs product_cat RÉELS + libellés affichés)
 *    Ordre = ordre d'affichage des filtres et de la grille.
 * ========================================================================= */

/**
 * @return array<string,string> slug product_cat => libellé affiché
 */
function sapi_catalogue_categories() {
  return [
    'suspensions'  => 'Suspensions',
    'appliques'    => 'Appliques',
    'lampadaires'  => 'Lampadaires',
    'lampesaposer' => 'Posables',
  ];
}

/**
 * Normalise un slug product_cat vers l'un des 4 slugs canoniques du catalogue.
 * Gère les variantes historiques (appliques-murales, lampes-a-poser).
 *
 * @param string $slug
 * @return string slug canonique, ou '' si hors catalogue
 */
function sapi_catalogue_canonical_cat($slug) {
  $aliases = [
    'appliques-murales' => 'appliques',
    'lampes-a-poser'    => 'lampesaposer',
  ];
  if (isset($aliases[$slug])) $slug = $aliases[$slug];
  return array_key_exists($slug, sapi_catalogue_categories()) ? $slug : '';
}

/* =========================================================================
 * 2. Mapping des caractéristiques (clés ACF/Woo réelles → libellés)
 *    SOURCE DE VÉRITÉ du tableau « Fiche technique ». Réutilisé au Temps 2.
 *    Aligné sur woocommerce/single-product.php (l.766-887) et sur
 *    acf-champs-fiche-technique.csv. Aucune clé inventée.
 *
 *    Chaque ligne :
 *      - key      : clé ACF (ou clé logique pour dimensions/poids)
 *      - label    : libellé affiché
 *      - cats     : 'all' ou tableau de slugs canoniques où la ligne s'applique
 *      - fallback : valeur par défaut si le champ est vide ('' = pas de défaut)
 *      - source   : 'acf' (défaut) | 'dimensions' | 'weight'
 * ========================================================================= */

/**
 * @return array<string,array<int,array>> section => lignes
 */
function sapi_catalogue_specs_schema() {
  return [
    'Dimensions' => [
      ['key' => 'dimensions',      'label' => 'Dimensions',            'cats' => 'all',            'fallback' => 'Voir variations', 'source' => 'dimensions'],
      ['key' => 'poids',           'label' => 'Poids',                 'cats' => 'all',            'fallback' => '',                'source' => 'weight'],
      ['key' => 'hauteur_totale',  'label' => 'Hauteur totale',        'cats' => ['lampadaires'],  'fallback' => ''],
      ['key' => 'hauteur_ampoule', 'label' => 'Hauteur ampoule',       'cats' => ['lampadaires'],  'fallback' => ''],
      ['key' => 'longueur_cable',  'label' => 'Longueur câble',        'cats' => 'all',            'fallback' => ''],
      ['key' => 'rosace',          'label' => 'Rosace',                'cats' => ['suspensions'],  'fallback' => ''],
      ['key' => 'fixation_murale', 'label' => 'Fixation murale',       'cats' => ['appliques'],    'fallback' => ''],
      ['key' => 'type_connexion',  'label' => 'Connexion électrique',  'cats' => ['appliques'],    'fallback' => ''],
    ],
    'Éclairage' => [
      ['key' => 'culot',                'label' => 'Culot',                'cats' => 'all', 'fallback' => 'E27'],
      ['key' => 'ampoule_recommandee',  'label' => 'Ampoule recommandée',  'cats' => 'all', 'fallback' => 'LED filament 4-6W (2700K)'],
      ['key' => 'ampoule_incluse',      'label' => 'Ampoule incluse',      'cats' => 'all', 'fallback' => 'Non (disponible en option)'],
      ['key' => 'compatible_variateur', 'label' => 'Compatible variateur', 'cats' => 'all', 'fallback' => ''],
      ['key' => 'compatible_dcl',       'label' => 'Compatible DCL',       'cats' => 'all', 'fallback' => ''],
    ],
    'Matériaux' => [
      ['key' => 'materiau_structure', 'label' => 'Structure', 'cats' => 'all', 'fallback' => '100% bois'],
      ['key' => 'bois',               'label' => 'Bois',      'cats' => 'all', 'fallback' => 'Peuplier ou Okoumé - Au choix'],
      ['key' => 'finition',           'label' => 'Finition',  'cats' => 'all', 'fallback' => 'Contreplaqué poncé'],
      ['key' => 'materiau_cable',     'label' => 'Câble',     'cats' => 'all', 'fallback' => ''],
    ],
    'Installation' => [
      ['key' => 'assemblage',              'label' => 'Assemblage',    'cats' => 'all',                          'fallback' => 'Notice et tuto vidéo'],
      ['key' => 'installation_difficulte', 'label' => 'Difficulté',    'cats' => 'all',                          'fallback' => 'Facile (15-30 min)'],
      ['key' => 'assemblage_outils',       'label' => 'Outils requis', 'cats' => 'all',                          'fallback' => 'Aucun'],
      ['key' => 'entretien',               'label' => 'Entretien',     'cats' => 'all',                          'fallback' => 'Chiffon sec ou plumeau'],
      ['key' => 'interrupteur',            'label' => 'Interrupteur',  'cats' => ['lampadaires', 'lampesaposer'], 'fallback' => ''],
    ],
  ];
}

/**
 * Une ligne du schéma s'applique-t-elle au produit (selon ses catégories) ?
 *
 * @param array        $row           ligne du schéma
 * @param array<string> $canonical_cats slugs canoniques du produit
 * @return bool
 */
function sapi_catalogue_row_applies($row, $canonical_cats) {
  if ($row['cats'] === 'all') return true;
  return (bool) array_intersect((array) $row['cats'], $canonical_cats);
}

/**
 * Construit le tableau des caractéristiques d'un produit à partir du schéma.
 * Règle brief : les lignes applicables à la catégorie sont TOUJOURS affichées ;
 * un champ vide sans défaut affiche « — » (jamais masqué). Les lignes hors
 * catégorie ne sont pas ajoutées.
 *
 * @param WC_Product $product
 * @return array<int,array{title:string,items:array<int,array{label:string,value:string}>}>
 */
function sapi_catalogue_get_product_specs($product) {
  if (!$product) return [];
  $product_id = $product->get_id();

  // Catégories canoniques du produit
  $slugs = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'slugs']);
  if (is_wp_error($slugs)) $slugs = [];
  $canonical = [];
  foreach ($slugs as $s) {
    $c = sapi_catalogue_canonical_cat($s);
    if ($c && !in_array($c, $canonical, true)) $canonical[] = $c;
  }

  $has_acf = function_exists('get_field');
  $sections = [];

  foreach (sapi_catalogue_specs_schema() as $section_title => $rows) {
    $items = [];
    foreach ($rows as $row) {
      if (!sapi_catalogue_row_applies($row, $canonical)) continue;

      $source = isset($row['source']) ? $row['source'] : 'acf';
      $value  = '';

      if ($source === 'dimensions') {
        // Priorité aux tailles réelles des variations ; repli sur ACF/WooCommerce.
        $sizes = sapi_catalogue_product_sizes($product);
        $value = $sizes ? implode(' · ', $sizes) : sapi_catalogue_product_dimensions($product, $has_acf);
      } elseif ($source === 'weight') {
        $value = sapi_catalogue_product_weight($product, $has_acf);
      } else {
        $value = $has_acf ? (string) get_field($row['key'], $product_id) : '';
      }

      // « Bois » : privilégier les essences réelles des variations (repli ACF).
      if ($row['key'] === 'bois') {
        $ess = sapi_catalogue_product_essences($product);
        if ($ess) $value = implode(' · ', $ess);
      }

      if ($value === '' || $value === null) {
        $value = $row['fallback'] !== '' ? $row['fallback'] : '—';
      }

      $items[] = ['label' => $row['label'], 'value' => (string) $value];
    }
    if ($items) {
      $sections[] = ['title' => $section_title, 'items' => $items];
    }
  }

  return $sections;
}

/**
 * Chaîne dimensions : champ ACF `dimensions`, sinon composition H/L/P,
 * sinon dimensions WooCommerce natives.
 */
function sapi_catalogue_product_dimensions($product, $has_acf) {
  $product_id = $product->get_id();
  $dimensions_str = '';

  if ($has_acf) {
    $dimensions = (string) get_field('dimensions', $product_id);
    if ($dimensions !== '') {
      $dimensions_str = $dimensions;
    } else {
      $hauteur    = (string) get_field('hauteur', $product_id);
      $largeur    = (string) get_field('largeur', $product_id);
      $profondeur = (string) get_field('profondeur', $product_id);
      if ($hauteur || $largeur || $profondeur) {
        $dim_parts = [];
        if ($largeur)    $dim_parts[] = 'L ' . $largeur;
        if ($profondeur) $dim_parts[] = 'P ' . $profondeur;
        if ($hauteur)    $dim_parts[] = 'H ' . $hauteur;
        $dimensions_str = implode(' × ', $dim_parts);
      }
    }
  }

  if (!$dimensions_str && function_exists('wc_format_dimensions')) {
    $wc_dims = wc_format_dimensions($product->get_dimensions(false));
    if ($wc_dims && $wc_dims !== 'N/A') $dimensions_str = $wc_dims;
  }

  return $dimensions_str;
}

/**
 * Poids : champ ACF `poids`, sinon poids WooCommerce natif (+ « kg »).
 */
function sapi_catalogue_product_weight($product, $has_acf) {
  $poids = $has_acf ? (string) get_field('poids', $product->get_id()) : '';
  if ($poids !== '') return $poids;
  $weight = $product->get_weight();
  return $weight ? $weight . ' kg' : '';
}

/**
 * Essences de bois disponibles pour un produit (Peuplier / Okoumé), lues sur
 * l'attribut de variation. Utilisé pour afficher des puces informatives, jamais
 * pour un prix ou une variation achetable.
 *
 * @param WC_Product $product
 * @return array<int,string> libellés (ex. ['Peuplier', 'Okoumé'])
 */
function sapi_catalogue_product_essences($product) {
  $labels = ['peuplier' => 'Peuplier', 'okoume' => 'Okoumé'];
  $found  = [];
  if (!function_exists('wc_get_product_terms')) return $found;
  foreach (['pa_materiau', 'pa_bois', 'pa_essence'] as $tax) {
    $terms = wc_get_product_terms($product->get_id(), $tax, ['fields' => 'slugs']);
    if (empty($terms) || is_wp_error($terms)) continue;
    foreach ($terms as $slug) {
      if (isset($labels[$slug]) && !in_array($labels[$slug], $found, true)) {
        $found[] = $labels[$slug];
      }
    }
  }
  return $found;
}

/**
 * Tailles disponibles d'un produit, lues sur l'attribut de variation `pa_taille`
 * (même principe que les essences). Triées par ordre numérique croissant.
 *
 * @param WC_Product $product
 * @return array<int,string> libellés (ex. ['50 cm', '100 cm', '130 cm'])
 */
function sapi_catalogue_product_sizes($product) {
  if (!function_exists('wc_get_product_terms')) return [];
  $names = wc_get_product_terms($product->get_id(), 'pa_taille', ['fields' => 'names']);
  if (empty($names) || is_wp_error($names)) return [];
  $names = array_values(array_unique(array_map('trim', $names)));
  usort($names, function ($a, $b) {
    $na = (float) preg_replace('/[^0-9.]/', '', $a);
    $nb = (float) preg_replace('/[^0-9.]/', '', $b);
    if ($na == $nb) return strcmp($a, $b);
    return $na < $nb ? -1 : 1;
  });
  return $names;
}

/**
 * Nettoie un fragment HTML éditorial pour l'étanchéité : retire les shortcodes
 * (qui pourraient générer des liens/galeries internes) et TOUS les liens `<a>`
 * (tout en conservant leur texte). Ne laisse passer qu'un balisage de mise en
 * forme sûr. Garantit qu'aucune description ne réintroduit un chemin vers le site.
 *
 * @param string $html
 * @return string HTML nettoyé (sans <a>, sans shortcode)
 */
function sapi_catalogue_safe_html($html) {
  if ($html === '' || $html === null) return '';
  $html = strip_shortcodes((string) $html);
  $allowed = [
    'p'      => [],
    'br'     => [],
    'strong' => [],
    'b'      => [],
    'em'     => [],
    'i'      => [],
    'u'      => [],
    'ul'     => [],
    'ol'     => [],
    'li'     => [],
    'h3'     => [],
    'h4'     => [],
    'span'   => [],
    'blockquote' => [],
  ];
  return trim(wp_kses($html, $allowed));
}

/* =========================================================================
 * 3. Source de données produits — SOURCE DE VÉRITÉ UNIQUE (Temps 1 + Temps 2)
 * ========================================================================= */

/**
 * Récupère les produits du catalogue, groupés par catégorie, sous forme de
 * tableaux normalisés SANS AUCUN PRIX. Requête appelable indépendamment du
 * rendu (le PDF du Temps 2 rejouera la même).
 *
 * @param array<string>|null $categories  slugs canoniques à inclure (null = tous)
 * @param array<int>|null    $include_ids IDs produit à inclure (null = tous).
 *                                        Modification ADDITIVE : sans cet
 *                                        argument, le comportement est
 *                                        strictement identique (/catalogue et le
 *                                        PDF public ne bougent pas d'un octet).
 * @return array<string,array{label:string,products:array}> slug => bloc catégorie
 */
function sapi_catalogue_get_products($categories = null, $include_ids = null) {
  if (!function_exists('wc_get_product')) return [];

  $cats  = sapi_catalogue_categories();
  $slugs = $categories ? array_values(array_intersect(array_keys($cats), $categories)) : array_keys($cats);

  $out = [];
  foreach ($slugs as $s) {
    $out[$s] = ['label' => $cats[$s], 'products' => []];
  }
  if (!$slugs) return $out;

  // Restriction optionnelle à une liste d'IDs (sélection produit du tarif pro).
  $ids = null;
  if ($include_ids !== null) {
    $ids = array_values(array_unique(array_filter(array_map('intval', (array) $include_ids))));
    // ⚠️ `post__in => []` est IGNORÉ par WP_Query : elle renverrait TOUT le
    // catalogue au lieu de rien. Une sélection vide doit sortir ici.
    if (!$ids) return $out;
  }

  // EXCEPTION posts_per_page => -1 documentée (CLAUDE.md règle 2) :
  // catalogue B2B à volume contrôlé (~40 produits), chargé en une fois pour un
  // filtrage 100% client-side. Pas de pagination, pas d'AJAX.
  $args = [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
    'no_found_rows'  => true,
    'tax_query'      => [[
      'taxonomy' => 'product_cat',
      'field'    => 'slug',
      'terms'    => $slugs,
    ]],
  ];
  // `post__in` restreint sans toucher au tri : l'ordre reste celui du site
  // (catégorie puis menu_order), jamais l'ordre de la sélection.
  if ($ids) $args['post__in'] = $ids;

  $query = new WP_Query($args);

  foreach ($query->posts as $post) {
    $product = wc_get_product($post->ID);
    if (!$product || $product->get_status() !== 'publish') continue;

    // Catégorie primaire = première catégorie canonique du produit dans l'ordre d'affichage
    $product_slugs = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'slugs']);
    if (is_wp_error($product_slugs)) continue;
    $product_canonical = [];
    foreach ($product_slugs as $ps) {
      $c = sapi_catalogue_canonical_cat($ps);
      if ($c) $product_canonical[] = $c;
    }
    $primary = '';
    foreach ($slugs as $s) {
      if (in_array($s, $product_canonical, true)) { $primary = $s; break; }
    }
    if ($primary === '') continue;

    $out[$primary]['products'][] = sapi_catalogue_normalize_product($product);
  }

  wp_reset_postdata();
  return $out;
}

/**
 * Normalise un WC_Product en tableau prêt pour le rendu / le JSON.
 * ZÉRO PRIX. Les images sont fournies en IDs d'attachment (le rendu décide de
 * la taille via wp_get_attachment_image ; le PDF du Temps 2 relit les fichiers).
 *
 * @param WC_Product $product
 * @return array
 */
function sapi_catalogue_normalize_product($product) {
  $id = $product->get_id();

  $gallery_ids = function_exists('sapi_get_product_photo_ids_with_fallback')
    ? sapi_get_product_photo_ids_with_fallback($id, '', 0)
    : [];
  // Repli ultime : image mise en avant si aucune galerie ACF.
  if (empty($gallery_ids)) {
    $thumb = get_post_thumbnail_id($id);
    if ($thumb) $gallery_ids = [(int) $thumb];
  }

  return [
    'id'          => $id,
    'title'       => get_the_title($id),
    'sku'         => (string) $product->get_sku(), // référence (pas un prix) — utile au gabarit PDF
    'short_desc'  => sapi_catalogue_safe_html($product->get_short_description()),
    'description' => sapi_catalogue_safe_html($product->get_description()),
    'gallery_ids' => array_map('intval', $gallery_ids),
    'essences'    => sapi_catalogue_product_essences($product),
    'sizes'       => sapi_catalogue_product_sizes($product),
    'specs'       => sapi_catalogue_get_product_specs($product),
    // Volontairement : aucun 'price', aucun 'permalink', aucun lien.
  ];
}

/* =========================================================================
 * 4. Champs ACF éditoriaux (Histoire de l'atelier / Deux bois au choix)
 *    Attachés au template page-catalogue.php → Robin édite depuis la page.
 * ========================================================================= */

function sapi_register_acf_catalogue() {
  if (!function_exists('acf_add_local_field_group')) return;

  acf_add_local_field_group([
    'key'    => 'group_catalogue_b2b',
    'title'  => 'Catalogue B2B — contenus',
    'fields' => [
      // En-tête
      [
        'key'          => 'field_cat_accroche',
        'label'        => 'Accroche (en-tête)',
        'name'         => 'catalogue_accroche',
        'type'         => 'textarea',
        'rows'         => 2,
        'instructions' => 'Courte phrase sous le titre du catalogue (optionnel).',
      ],

      // Section « Histoire de l'atelier »
      [
        'key'           => 'field_cat_histoire_titre',
        'label'         => 'Histoire — titre',
        'name'          => 'catalogue_histoire_titre',
        'type'          => 'text',
        'default_value' => 'Histoire de l’atelier',
      ],
      [
        'key'   => 'field_cat_histoire_texte',
        'label' => 'Histoire — texte',
        'name'  => 'catalogue_histoire_texte',
        'type'  => 'wysiwyg',
        'tabs'  => 'visual',
        'media_upload' => 0,
      ],
      [
        'key'           => 'field_cat_histoire_image',
        'label'         => 'Histoire — image',
        'name'          => 'catalogue_histoire_image',
        'type'          => 'image',
        'return_format' => 'id',
        'preview_size'  => 'medium',
      ],

      // Section « Deux bois au choix »
      [
        'key'           => 'field_cat_bois_titre',
        'label'         => 'Bois — titre',
        'name'          => 'catalogue_bois_titre',
        'type'          => 'text',
        'default_value' => 'Deux bois au choix',
      ],
      [
        'key'   => 'field_cat_bois_intro',
        'label' => 'Bois — introduction',
        'name'  => 'catalogue_bois_intro',
        'type'  => 'textarea',
        'rows'  => 3,
      ],
      [
        'key'           => 'field_cat_bois_peuplier_texte',
        'label'         => 'Peuplier — texte',
        'name'          => 'catalogue_bois_peuplier_texte',
        'type'          => 'textarea',
        'rows'          => 3,
        'default_value' => 'Finition claire.',
      ],
      [
        'key'           => 'field_cat_bois_peuplier_image',
        'label'         => 'Peuplier — image',
        'name'          => 'catalogue_bois_peuplier_image',
        'type'          => 'image',
        'return_format' => 'id',
        'preview_size'  => 'medium',
      ],
      [
        'key'           => 'field_cat_bois_okoume_texte',
        'label'         => 'Okoumé — texte',
        'name'          => 'catalogue_bois_okoume_texte',
        'type'          => 'textarea',
        'rows'          => 3,
        'default_value' => 'Teinte plus rosée et plus sombre.',
      ],
      [
        'key'           => 'field_cat_bois_okoume_image',
        'label'         => 'Okoumé — image',
        'name'          => 'catalogue_bois_okoume_image',
        'type'          => 'image',
        'return_format' => 'id',
        'preview_size'  => 'medium',
      ],
    ],
    'location' => [
      [
        [
          'param'    => 'page_template',
          'operator' => '==',
          'value'    => 'page-catalogue.php',
        ],
      ],
    ],
    'position' => 'normal',
    'style'    => 'default',
  ]);
}
add_action('acf/init', 'sapi_register_acf_catalogue');

<?php
/**
 * Catalogue B2B — couche PRIX (extension de inc/catalogue-data.php).
 *
 * ⚠️ inc/catalogue-data.php porte une garantie documentée : « aucune donnée de
 * prix n'est jamais produite par cette couche ». C'est elle qui rend la page
 * /catalogue auditable d'un coup d'œil (un grep suffit). ELLE N'EST PAS TOUCHÉE :
 * les prix vivent ICI, dans un fichier séparé, appelé EXPLICITEMENT par les seuls
 * rendus qui en ont besoin (/catalogue-prix, et le PDF tarif pro du Lot 3).
 *
 * Règle d'or : pas de paramètre `$with_prices = false` sur les fonctions publiques
 * existantes. Une fonction séparée, appelée sciemment. Un défaut finit toujours
 * par se retourner contre soi.
 *
 * Convention confirmée par Robin : les prix WooCommerce du site sont des PVP TTC,
 * TVA 20%. Aucun prix HT n'est calculé ici — la conversion se fait au rendu du
 * PDF pro, avec le taux de remise du moment (qui n'existe jamais sur le web).
 */

if (!defined('ABSPATH')) exit;

/** Coefficient TVA appliqué au passage TTC → HT (20%). */
if (!defined('SAPI_CATALOGUE_TVA_COEF')) define('SAPI_CATALOGUE_TVA_COEF', 1.20);

/** Libellé d'une ligne de prix quand le produit n'a qu'une seule déclinaison. */
if (!defined('SAPI_CATALOGUE_ROW_UNIQUE')) define('SAPI_CATALOGUE_ROW_UNIQUE', 'Modèle unique');

/* =========================================================================
 * 1. Arrondis et conversion TTC → HT remisé
 * ========================================================================= */

/**
 * Arrondi d'un prix HT à l'euro INFÉRIEUR (74,17 → 74). Un seul endroit.
 *
 * ⚠️ PIÈGE FLOTTANT — round(…, 6) AVANT floor(), jamais floor() nu.
 * En double précision, `ttc / 1.20 * (1 - taux)` retombe régulièrement à un
 * epsilon SOUS l'entier attendu, et floor() fait alors perdre un euro entier :
 *
 *   PVP 108 € à 30%  →  62.99999999999999   → floor nu = 62, attendu 63
 *   PVP 204 € à 30%  →  118.99999999999999  → floor nu = 118, attendu 119
 *   PVP 216 € à 30%  →  125.99999999999999  → floor nu = 125, attendu 126
 *   PVP 396 € à 30%  →  230.99999999999997  → floor nu = 230, attendu 231
 *   PVP 408 € à 30%  →  237.99999999999997  → floor nu = 237, attendu 238
 *   PVP 420 € à 30%  →  244.99999999999997  → floor nu = 244, attendu 245
 *   PVP 432 € à 30%  →  251.99999999999997  → floor nu = 251, attendu 252
 *
 * Balayage Robin (PVP 20→600 € par 0,10 × taux 0→50% par 0,1 point) : 82 cas de
 * divergence, tous corrigés par le round() préalable. Jeu de test du critère n°9.
 *
 * @param float $price prix HT brut (non arrondi)
 * @return float euro inférieur
 */
function sapi_catalogue_round_ht($price) {
  return (float) floor(round((float) $price, 6));
}

/**
 * Prix professionnel HT à partir d'un PVP TTC et d'un taux de remise.
 *
 * Le taux est TOUJOURS un argument, jamais une constante : c'est exactement ce
 * qui rend l'export à taux variable possible (Muse à -12%, un prospect à -30%),
 * sans que le site ait jamais à savoir qui est qui.
 *
 * @param float $ttc  PVP TTC
 * @param float $rate taux de remise en FRACTION (0.30 = 30%), pas en pourcentage
 * @return float prix HT remisé, arrondi à l'euro inférieur
 */
function sapi_catalogue_ht_from_ttc($ttc, $rate) {
  $ttc  = (float) $ttc;
  $rate = (float) $rate;
  if ($ttc <= 0) return 0.0;
  // Bornes de sécurité : un taux hors [0, 0.95] est une saisie aberrante.
  if ($rate < 0)    $rate = 0.0;
  if ($rate > 0.95) $rate = 0.95;

  return sapi_catalogue_round_ht($ttc / SAPI_CATALOGUE_TVA_COEF * (1 - $rate));
}

/**
 * Formate un montant pour l'affichage (page et PDF).
 *
 * N'utilise volontairement PAS wc_price() : celui-ci émet un balisage
 * `<span class="woocommerce-Price-amount">`, précisément ce que les audits
 * d'étanchéité grepent. Ici on maîtrise la sortie de bout en bout.
 * Les décimales ne sont affichées que si elles existent (108 € et non 108,00 €).
 *
 * @param float $amount
 * @return string ex. « 108 € » ou « 108,50 € »
 */
function sapi_catalogue_format_price($amount) {
  $amount   = (float) $amount;
  $decimals = (abs($amount - round($amount)) < 0.005) ? 0 : 2;
  return number_format_i18n($amount, $decimals) . ' €';
}

/* =========================================================================
 * 2. Prix par variation
 * ========================================================================= */

/**
 * Taxonomies d'attribut portant l'essence de bois, par ordre de préférence.
 * Même liste que sapi_catalogue_product_essences() (inc/catalogue-data.php).
 * @return array<int,string>
 */
function sapi_catalogue_pricing_essence_taxonomies() {
  return ['pa_materiau', 'pa_bois', 'pa_essence'];
}

/**
 * Libellé lisible d'un terme d'attribut à partir de son slug.
 * Repli sur le slug « humanisé » si le terme a disparu.
 *
 * @param string $slug
 * @param string $taxonomy
 * @return string
 */
function sapi_catalogue_term_label($slug, $taxonomy) {
  $slug = (string) $slug;
  if ($slug === '' || $taxonomy === '') return '';
  $term = get_term_by('slug', $slug, $taxonomy);
  if ($term && !is_wp_error($term)) return $term->name;
  return ucfirst(str_replace('-', ' ', $slug));
}

/**
 * Prix de référence TTC d'un produit ou d'une variation = le PVP.
 *
 * On prend le prix RÉGULIER en priorité : une promotion temporaire ne doit pas
 * devenir la base d'un tarif (le PDF pro en dérivera le prix revendeur, et une
 * remise de saison n'a pas à se répercuter sur un tarif annuel). Repli sur le
 * prix actif si aucun prix régulier n'est renseigné.
 *
 * @param WC_Product $product produit simple ou variation
 * @return float|null null si le produit n'a aucun prix exploitable
 */
function sapi_catalogue_reference_price($product) {
  if (!$product) return null;
  $regular = $product->get_regular_price();
  $price   = ($regular !== '' && (float) $regular > 0) ? (float) $regular : (float) $product->get_price();
  return $price > 0 ? $price : null;
}

/**
 * Valeur numérique d'un libellé de taille, pour le tri (« 100 cm » → 100).
 * Même extraction que sapi_catalogue_product_sizes() — tri cohérent partout.
 *
 * @param string $label
 * @return float
 */
function sapi_catalogue_size_sort_value($label) {
  return (float) preg_replace('/[^0-9.]/', '', (string) $label);
}

/**
 * Tableau des prix d'un produit, une ligne par combinaison achetable.
 *
 * Croise `pa_taille` × essence (pa_materiau / pa_bois / pa_essence) en lisant
 * les variations réelles — pas le produit de toutes les tailles par toutes les
 * essences : une combinaison non créée en base n'apparaît pas.
 * Produit simple = une seule ligne.
 *
 * Les variations portant une essence hors catalogue sont ÉCARTÉES (cf.
 * sapi_catalogue_essence_labels()). Un produit dont toutes les variations
 * seraient écartées ne sort aucune ligne : ni prix sur la carte, ni tableau.
 *
 * ⚠️ NE CALCULE AUCUN PRIX HT. La conversion se fait au rendu du PDF pro, avec
 * le taux du moment (cf. sapi_catalogue_ht_from_ttc()).
 *
 * @param WC_Product $product
 * @return array{
 *   rows: array<int,array{label:string,size:string,essence:string,ttc:float,sku:string}>,
 *   min_ttc: float|null,
 *   max_ttc: float|null,
 *   essence_varies: bool
 * }
 */
function sapi_catalogue_product_pricing($product) {
  $empty = ['rows' => [], 'min_ttc' => null, 'max_ttc' => null, 'essence_varies' => false];
  if (!$product || !function_exists('wc_get_product')) return $empty;

  $rows = [];

  if (method_exists($product, 'is_type') && $product->is_type('variable')) {
    $ess_taxes = sapi_catalogue_pricing_essence_taxonomies();

    foreach ($product->get_children() as $child_id) {
      $variation = wc_get_product($child_id);
      if (!$variation || $variation->get_status() !== 'publish') continue;

      $ttc = sapi_catalogue_reference_price($variation);
      if ($ttc === null) continue;

      // Slugs bruts de la variation : ['attribute_pa_taille' => 'slug', …].
      // Une valeur vide = « n'importe lequel » côté WooCommerce.
      $attrs = $variation->get_variation_attributes();

      $size_slug = !empty($attrs['attribute_pa_taille']) ? (string) $attrs['attribute_pa_taille'] : '';
      $ess_slug  = '';
      $ess_tax   = '';
      foreach ($ess_taxes as $tax) {
        if (!empty($attrs['attribute_' . $tax])) {
          $ess_slug = (string) $attrs['attribute_' . $tax];
          $ess_tax  = $tax;
          break;
        }
      }

      // ⚠️ Essences NON exposées par le catalogue (ex. « Peuplier teinté noir »)
      // → variation écartée. Sans ce filtre, /catalogue-prix afficherait un prix
      // sur un bois que la fiche technique juste en dessous ne mentionne pas :
      // le prescripteur verrait une option qu'on ne lui présente pas.
      // Table de référence : sapi_catalogue_essence_labels() (catalogue-data.php).
      $allowed = sapi_catalogue_essence_labels();
      if ($ess_slug !== '' && !isset($allowed[$ess_slug])) continue;

      $size    = $size_slug !== '' ? sapi_catalogue_term_label($size_slug, 'pa_taille') : '';
      // Libellé pris dans la table du catalogue (et non sur le terme WooCommerce)
      // pour que le tableau de prix et la ligne « Bois » disent exactement pareil.
      $essence = $ess_slug !== ''  ? $allowed[$ess_slug] : '';

      $parts = array_filter([$size, $essence]);
      $rows[] = [
        'label'   => $parts ? implode(' · ', $parts) : SAPI_CATALOGUE_ROW_UNIQUE,
        'size'    => $size,
        'essence' => $essence,
        'ttc'     => (float) $ttc,
        'sku'     => (string) $variation->get_sku(),
      ];
    }

    // Tri : taille croissante (numérique), puis essence alphabétique.
    usort($rows, function ($a, $b) {
      $na = sapi_catalogue_size_sort_value($a['size']);
      $nb = sapi_catalogue_size_sort_value($b['size']);
      if ($na != $nb) return $na < $nb ? -1 : 1;
      return strcmp($a['essence'], $b['essence']);
    });
  } else {
    $ttc = sapi_catalogue_reference_price($product);
    if ($ttc !== null) {
      $rows[] = [
        'label'   => SAPI_CATALOGUE_ROW_UNIQUE,
        'size'    => '',
        'essence' => '',
        'ttc'     => (float) $ttc,
        'sku'     => (string) $product->get_sku(),
      ];
    }
  }

  if (!$rows) return $empty;

  $prices = wp_list_pluck($rows, 'ttc');

  return [
    'rows'           => $rows,
    'min_ttc'        => (float) min($prices),
    'max_ttc'        => (float) max($prices),
    'essence_varies' => sapi_catalogue_pricing_essence_varies($rows),
  ];
}

/**
 * Le prix dépend-il de l'essence, à taille égale ?
 *
 * Diagnostic : si la réponse est non sur tout le catalogue, le tableau des
 * variations peut être réduit à la seule taille (décision de Robin, pas
 * automatisée ici — on se contente de constater).
 *
 * @param array $rows lignes issues de sapi_catalogue_product_pricing()
 * @return bool
 */
function sapi_catalogue_pricing_essence_varies($rows) {
  $by_size = [];
  foreach ($rows as $row) {
    if ($row['essence'] === '') continue;
    $by_size[$row['size']][] = (float) $row['ttc'];
  }
  foreach ($by_size as $prices) {
    if (count(array_unique($prices)) > 1) return true;
  }
  return false;
}

/* =========================================================================
 * 3. Normalisation enrichie (aucune duplication de la couche Temps 1)
 * ========================================================================= */

/**
 * Normalise un produit AVEC ses prix : délègue tout à la fonction publique
 * existante, puis AJOUTE la clé `pricing`. La logique specs / galerie /
 * descriptions n'est jamais dupliquée.
 *
 * @param WC_Product $product
 * @return array
 */
function sapi_catalogue_normalize_product_priced($product) {
  $data = sapi_catalogue_normalize_product($product);
  $data['pricing'] = sapi_catalogue_product_pricing($product);
  return $data;
}

/**
 * Équivalent de sapi_catalogue_get_products() avec les prix.
 *
 * Rejoue la MÊME requête (même groupement, même ordre, mêmes exclusions) en
 * déléguant à la fonction publique, puis enrichit chaque produit. Aucune copie
 * de la requête : elle reste définie à un seul endroit, dans catalogue-data.php.
 *
 * @param array<string>|null $categories  slugs canoniques (null = toutes)
 * @param array<int>|null    $include_ids IDs produit à inclure (null = tous)
 * @return array<string,array{label:string,products:array}>
 */
function sapi_catalogue_get_products_priced($categories = null, $include_ids = null) {
  $catalogue = sapi_catalogue_get_products($categories, $include_ids);

  foreach ($catalogue as $slug => $block) {
    foreach ($block['products'] as $i => $p) {
      $product = function_exists('wc_get_product') ? wc_get_product($p['id']) : null;
      $catalogue[$slug]['products'][$i]['pricing'] = $product
        ? sapi_catalogue_product_pricing($product)
        : ['rows' => [], 'min_ttc' => null, 'max_ttc' => null, 'essence_varies' => false];
    }
  }

  return $catalogue;
}

/* =========================================================================
 * 4. Champs ACF de la page prix
 *    Groupe SÉPARÉ de `group_catalogue_b2b` : la page /catalogue historique
 *    n'est pas modifiée, et le flag est visible d'un coup d'œil dans l'éditeur.
 * ========================================================================= */

function sapi_register_acf_catalogue_prix() {
  if (!function_exists('acf_add_local_field_group')) return;

  acf_add_local_field_group([
    'key'    => 'group_catalogue_prix',
    'title'  => 'Catalogue — prix publics',
    'fields' => [
      [
        'key'           => 'field_cat_affiche_prix',
        'label'         => 'Afficher les prix publics',
        'name'          => 'catalogue_affiche_prix',
        'type'          => 'true_false',
        'ui'            => 1,
        'default_value' => 0,
        'instructions'  => 'À activer UNIQUEMENT sur la page /catalogue-prix. La page /catalogue remise aux prescripteurs doit rester strictement sans prix.',
      ],
      [
        'key'           => 'field_cat_mentions_legales',
        'label'         => 'Mentions légales (page prix)',
        'name'          => 'catalogue_mentions_legales',
        'type'          => 'textarea',
        'rows'          => 8,
        'default_value' => '',
        // Volontairement VIDE : les mentions du tarif professionnel (HT, minimum
        // de commande, confidentialité) sont un AUTRE texte, propre au PDF pro.
        // Les recycler ici mettrait du vocabulaire revendeur sur une page TTC
        // grand public.
        'instructions'  => 'Affichées en haut de la page, sous l’accroche. Texte grand public, en TTC. Ne pas y recopier les mentions du tarif professionnel (prix HT, minimum de commande, confidentialité) : c’est un autre document. Laissé vide, le bloc n’apparaît pas.',
        'conditional_logic' => [
          [
            ['field' => 'field_cat_affiche_prix', 'operator' => '==', 'value' => '1'],
          ],
        ],
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
    'position'   => 'normal',
    'style'      => 'default',
    'menu_order' => -1, // au-dessus du groupe « Catalogue B2B — contenus »
  ]);
}
add_action('acf/init', 'sapi_register_acf_catalogue_prix');

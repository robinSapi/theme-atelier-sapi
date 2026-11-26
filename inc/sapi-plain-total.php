<?php
/**
 * Prix sur les fiches ACCESSOIRES sans options (26/11/2026).
 *
 * PROBLÈME
 * Le thème masque le prix général de la fiche (.product-price-v2, style.css).
 * Sur un luminaire, le prix affiché est la ligne « Sous-total » de Product
 * Add-Ons, juste au-dessus de la quantité, qui suit taille, options et quantité.
 * Une fiche accessoire n'a pas d'add-on : pas de sous-total, donc aucun prix
 * (ampoules, pied de lampadaire), ou seulement celui de la variation quand les
 * prix diffèrent, qui ne suit pas la quantité.
 *
 * CE QUE FAIT CE FICHIER
 * Sur ces fiches seulement, une ligne de sous-total au même endroit et au même
 * style (p.price .amount en vert) : prix unitaire × quantité, celui de la
 * variation choisie pour un produit variable (assets/sapi-plain-total.js). Le
 * prix de variation de WooCommerce y est masqué pour ne pas l'afficher deux fois.
 *
 * PÉRIMÈTRE, volontairement étroit : catégorie « accessoires » ET aucun add-on
 * (même global, par catégorie). Une fiche luminaire ne remplit jamais les deux
 * conditions : elle ne voit ni la ligne, ni la classe, ni le script.
 */

if (!defined('ABSPATH')) exit;

/** La fiche reçoit-elle la ligne de sous-total ? */
function sapi_plain_total_applies($product) {
  if (!$product || !is_object($product) || !function_exists('is_product') || !is_product()) return false;
  $id = $product->get_id();
  if (!has_term('accessoires', 'product_cat', $id)) return false;
  // Jamais un luminaire, même rangé aussi dans « accessoires »
  if (function_exists('sapi_ampoule_is_luminaire') && sapi_ampoule_is_luminaire($id)) return false;
  if (!$product->is_type('simple') && !$product->is_type('variable')) return false;
  if (class_exists('WC_Product_Addons_Helper')) {
    $addons = WC_Product_Addons_Helper::get_product_addons($id);
    if (!empty($addons)) return false;
  }
  return true;
}

/** Produit de la fiche affichée (le global $product n'existe pas encore à body_class). */
function sapi_plain_total_page_product() {
  if (!function_exists('is_product') || !is_product()) return null;
  $p = wc_get_product(get_queried_object_id());
  return $p ?: null;
}

add_filter('body_class', function ($classes) {
  if (sapi_plain_total_applies(sapi_plain_total_page_product())) $classes[] = 'sapi-plain-total-page';
  return $classes;
});

// Juste avant la quantité, comme le sous-total des luminaires
add_action('woocommerce_before_add_to_cart_quantity', function () {
  global $product;
  if (!sapi_plain_total_applies($product)) return;
  $unit = $product->is_type('simple') ? (float) wc_get_price_to_display($product) : null;
  ?>
  <div class="sapi-plain-total" aria-live="polite"<?php echo $unit !== null ? ' data-unit="' . esc_attr($unit) . '"' : ' hidden'; ?>>
    <p class="price"><span class="amount"><?php echo $unit !== null ? wp_kses_post(wc_price($unit)) : ''; ?></span></p>
  </div>
  <?php
});

add_action('wp_enqueue_scripts', function () {
  if (!sapi_plain_total_applies(sapi_plain_total_page_product())) return;
  $path = get_template_directory() . '/assets/sapi-plain-total.js';
  if (!file_exists($path)) return;
  wp_enqueue_script('sapi-plain-total', get_template_directory_uri() . '/assets/sapi-plain-total.js', ['jquery'], filemtime($path), true);
  wp_localize_script('sapi-plain-total', 'SAPI_PLAIN_TOTAL', [
    'decimals' => wc_get_price_decimals(),
    'dec'      => wc_get_price_decimal_separator(),
    'thousand' => wc_get_price_thousand_separator(),
    'format'   => html_entity_decode(get_woocommerce_price_format(), ENT_QUOTES, 'UTF-8'),
    'symbol'   => html_entity_decode(get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8'),
  ]);
});

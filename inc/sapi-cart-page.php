<?php
/**
 * Page panier (WooCommerce Blocks) : ampoule et suggestions.
 * Variante B de la maquette, validée par Robin le 15/10/2026.
 *
 * 1. LANGUETTE AMPOULE : sous chaque luminaire du panier, une languette beige
 *    accrochée à sa carte propose l'ampoule recommandée (sapi_ampoule_for).
 *    Elle n'a ni cadre blanc ni contrôles, pour qu'on voie qu'elle n'est pas
 *    au panier. Ajout en un clic via le store WooCommerce Blocks (le panier se
 *    met à jour sans recharger). Les languettes disparaissent dès qu'une
 *    ampoule est au panier. Rendu par assets/sapi-cart-page.js.
 * 2. VITRINE « ÇA IRAIT BIEN AVEC… » (desktop) : sous le panier, avant la
 *    réassurance, trois luminaires choisis comme dans le mini-panier mais
 *    sans l'ampoule. Masquée en mobile.
 * Le bloc natif des ventes croisées (« Avez-vous déjà la bonne ampoule ? »)
 * est masqué en CSS : la languette le remplace.
 *
 * Aucun prix ni remise n'est calculé ici : tout vient de WooCommerce.
 *
 * @package theme-sapi-maison
 */

if (!defined('ABSPATH')) exit;

/** Ampoule recommandée par ligne de panier luminaire : [cart_item_key => données]. */
function sapi_cart_page_amp_data() {
  $out = [];
  if (!function_exists('WC') || !WC()->cart || !function_exists('sapi_ampoule_for') || !function_exists('sapi_suggest_type')) return $out;
  foreach (WC()->cart->get_cart() as $key => $item) {
    $pid = (int) $item['product_id'];
    if (!sapi_suggest_type($pid)) continue;
    $amp = sapi_ampoule_for($pid);
    if (!$amp) continue;
    $lum = wc_get_product($pid);
    $parts = preg_split('/\s+/', trim($lum ? $lum->get_name() : ''));
    $out[$key] = [
      'id'    => $amp->get_id(),
      'first' => $parts[0],
      'name'  => sapi_ampoule_short_name($amp),
      'price' => html_entity_decode(wp_strip_all_tags(wc_price(wc_get_price_to_display($amp))), ENT_QUOTES, 'UTF-8'),
      'img'   => $amp->get_image_id() ? (string) wp_get_attachment_image_url($amp->get_image_id(), 'thumbnail') : '',
    ];
  }
  return $out;
}

add_action('wp_enqueue_scripts', function () {
  if (!function_exists('is_cart') || !is_cart()) return;
  $path = get_template_directory() . '/assets/sapi-cart-page.js';
  if (!file_exists($path)) return;
  wp_enqueue_script('sapi-cart-page', get_template_directory_uri() . '/assets/sapi-cart-page.js', [], filemtime($path), true);
  wp_localize_script('sapi-cart-page', 'sapiCartPage', [
    'amp'     => (object) sapi_cart_page_amp_data(),
    'ajaxUrl' => class_exists('WC_AJAX') ? WC_AJAX::get_endpoint('add_to_cart') : '',
  ]);
});

/** Vitrine sous le panier, juste avant la réassurance. */
add_filter('render_block', function ($content, $block) {
  if (($block['blockName'] ?? '') !== 'woocommerce/cart') return $content;
  if (!function_exists('sapi_suggest_for_cart') || !function_exists('WC') || !WC()->cart || WC()->cart->is_empty()) return $content;
  $data = sapi_suggest_for_cart(false);
  if (empty($data['items'])) return $content;

  $source = $data['source'];
  $first = $source ? preg_split('/\s+/', trim($source->get_name()))[0] : '';
  $source_key = '';
  if ($source) {
    foreach (WC()->cart->get_cart() as $k => $item) {
      if ((int) $item['product_id'] === $source->get_id()) $source_key = $k;
    }
  }
  ob_start();
  ?>
  <section class="sapi-cart-page-suggest" data-source-key="<?php echo esc_attr($source_key); ?>" aria-label="<?php esc_attr_e('Suggestions', 'theme-sapi-maison'); ?>">
    <p class="sapi-suggest__eyebrow"><?php esc_html_e('Pour compléter votre commande', 'theme-sapi-maison'); ?></p>
    <p class="sapi-suggest__title"><?php echo $first
      ? esc_html(sprintf(__('Ça irait bien avec %s', 'theme-sapi-maison'), $first))
      : esc_html__('Nos meilleures ventes', 'theme-sapi-maison'); ?></p>
    <ul class="sapi-suggest__cards">
      <?php foreach ($data['items'] as $i => $s) echo sapi_suggest_card_html($s, $i, $first); ?>
    </ul>
  </section>
  <?php
  $html = ob_get_clean();

  $marker = '<div class="cart-reassurance">';
  $pos = strpos($content, $marker);
  return $pos === false ? $content . $html : substr_replace($content, $html . $marker, $pos, strlen($marker));
}, 20, 2);

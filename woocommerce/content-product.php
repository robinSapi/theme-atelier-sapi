<?php
/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * @package Sapi-Maison
 * @version 9.4.0
 */

defined('ABSPATH') || exit;

global $product;

// Ensure product data is properly set up
if (!$product || !is_a($product, 'WC_Product')) {
  $product = wc_get_product(get_the_ID());
}

// Check if the product is valid and visible
if (!$product || !$product->is_visible()) {
  return;
}

// DEBUG - Remove this after testing
$debug_info = [
  'ID' => $product->get_id(),
  'Name' => $product->get_name(),
  'Has Image' => $product->get_image_id() ? 'YES' : 'NO',
  'Price HTML' => $product->get_price_html() ? 'YES' : 'NO'
];
?>
<li <?php wc_product_class('product-card', $product); ?>>
  <!-- DEBUG INFO -->
  <div style="background: yellow; padding: 5px; font-size: 11px; border: 1px solid red;">
    DEBUG: <?php echo implode(' | ', array_map(fn($k, $v) => "$k: $v", array_keys($debug_info), $debug_info)); ?>
  </div>

  <a href="<?php the_permalink(); ?>" class="product-card-link woocommerce-LoopProduct-link">
    <div class="product-card-image">
      <!-- DEBUG: Image section -->
      <div style="background: lightblue; padding: 3px;">IMAGE HERE:</div>
      <?php echo woocommerce_get_product_thumbnail(); ?>
    </div>
    <h2 class="woocommerce-loop-product__title"><?php the_title(); ?></h2>
    <div style="background: lightgreen; padding: 3px;">PRICE HERE:</div>
    <span class="price"><?php echo $product->get_price_html(); ?></span>
  </a>
  <div style="background: lightcoral; padding: 3px;">BUTTON HERE:</div>
  <?php woocommerce_template_loop_add_to_cart(); ?>
</li>

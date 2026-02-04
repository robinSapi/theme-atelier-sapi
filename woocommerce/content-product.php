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
?>
<li <?php wc_product_class('product-card', $product); ?>>
  <a href="<?php the_permalink(); ?>" class="product-card-link woocommerce-LoopProduct-link">
    <div class="product-card-image">
      <?php echo woocommerce_get_product_thumbnail(); ?>
    </div>
    <h2 class="woocommerce-loop-product__title"><?php the_title(); ?></h2>
    <span class="price"><?php echo $product->get_price_html(); ?></span>
  </a>
  <?php woocommerce_template_loop_add_to_cart(); ?>
</li>

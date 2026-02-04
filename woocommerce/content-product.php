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

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if (!is_a($product, WC_Product::class) || !$product->is_visible()) {
  return;
}
?>
<li <?php wc_product_class('product-card', $product); ?>>
  <a href="<?php echo esc_url($product->get_permalink()); ?>" class="product-card-link woocommerce-LoopProduct-link">
    <?php if ($product->get_image_id()) : ?>
      <div class="product-card-image">
        <?php echo $product->get_image('woocommerce_thumbnail'); ?>
      </div>
    <?php endif; ?>
    <h2 class="woocommerce-loop-product__title"><?php echo esc_html($product->get_name()); ?></h2>
    <?php if ($price_html = $product->get_price_html()) : ?>
      <span class="price"><?php echo $price_html; ?></span>
    <?php endif; ?>
  </a>
  <?php
  // Add to cart button
  woocommerce_template_loop_add_to_cart();
  ?>
</li>

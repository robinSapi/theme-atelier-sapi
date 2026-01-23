<?php
defined('ABSPATH') || exit;

global $product;

if (empty($product) || !$product->is_visible()) {
  return;
}
?>
<li <?php wc_product_class('product-card', $product); ?>>
  <a href="<?php the_permalink(); ?>" class="product-card-link">
    <div class="product-card-image">
      <?php woocommerce_template_loop_product_thumbnail(); ?>
    </div>
    <h2 class="woocommerce-loop-product__title"><?php the_title(); ?></h2>
    <?php woocommerce_template_loop_price(); ?>
  </a>
  <?php woocommerce_template_loop_add_to_cart(); ?>
</li>

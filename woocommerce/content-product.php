<?php
/**
 * The template for displaying product content within loops
 *
 * SAPI CINÉTIQUE - Enhanced product card with badges, hover effects, and quick view
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

// Check if the product is valid
// Note: Don't use is_visible() here as it fails with custom WP_Query
if (!$product || $product->get_status() !== 'publish') {
  return;
}

// Get product data
$product_id = $product->get_id();
$product_type = $product->get_type();
$is_new = false;
$is_signature = false;

// Check if product is "new" (created within last 30 days)
$created_date = get_the_date('U', $product_id);
$thirty_days_ago = strtotime('-30 days');
if ($created_date > $thirty_days_ago) {
  $is_new = true;
}

// Check for "signature" tag or featured status
if ($product->is_featured()) {
  $is_signature = true;
}

// Get category for display
$categories = get_the_terms($product_id, 'product_cat');
$category_name = '';
if ($categories && !is_wp_error($categories)) {
  // Get the first non-uncategorized category
  foreach ($categories as $cat) {
    if ($cat->slug !== 'uncategorized') {
      $category_name = $cat->name;
      break;
    }
  }
}

// Check if on sale
$is_on_sale = $product->is_on_sale();

// Get price display
$price_html = $product->get_price_html();
$is_variable = $product->is_type('variable');

// Get gallery image for hover effect (lifestyle/ambiance photo)
$gallery_ids = $product->get_gallery_image_ids();
$hover_image_url = '';
if (!empty($gallery_ids)) {
  $hover_image_url = wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail');
}
?>

<li <?php wc_product_class('product-card-cinetique', $product); ?> data-category="<?php echo esc_attr(sanitize_title($category_name)); ?>">
  <a href="<?php the_permalink(); ?>" class="product-card-link">
    <div class="product-media<?php echo $hover_image_url ? ' has-hover-image' : ''; ?>">
      <?php
      // Product image (main)
      if (has_post_thumbnail()) {
        echo '<span class="product-image-main">' . woocommerce_get_product_thumbnail('woocommerce_thumbnail') . '</span>';
      } else {
        echo '<span class="product-image-main">' . wc_placeholder_img('woocommerce_thumbnail') . '</span>';
      }

      // Hover image (lifestyle/ambiance)
      if ($hover_image_url) {
        echo '<span class="product-image-hover"><img src="' . esc_url($hover_image_url) . '" alt="' . esc_attr(get_the_title()) . ' - ambiance" loading="lazy"></span>';
      }
      ?>

      <?php if ($is_on_sale) : ?>
        <span class="product-badge badge-sale"><?php esc_html_e('Promo', 'theme-sapi-maison'); ?></span>
      <?php elseif ($is_new) : ?>
        <span class="product-badge badge-new"><?php esc_html_e('Nouveau', 'theme-sapi-maison'); ?></span>
      <?php elseif ($is_signature) : ?>
        <span class="product-badge badge-signature"><?php esc_html_e('Signature', 'theme-sapi-maison'); ?></span>
      <?php endif; ?>

      <span class="product-quick-view">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        <?php esc_html_e('Aperçu', 'theme-sapi-maison'); ?>
      </span>
    </div>

    <div class="product-info">
      <h2 class="product-name"><?php the_title(); ?></h2>

      <?php if ($category_name) : ?>
        <p class="product-category"><?php echo esc_html($category_name); ?></p>
      <?php endif; ?>

      <div class="product-price">
        <?php if ($is_variable) : ?>
          <span class="price-from"><?php esc_html_e('À partir de', 'theme-sapi-maison'); ?></span>
        <?php endif; ?>
        <span class="price-value"><?php echo $price_html; ?></span>
      </div>
    </div>
  </a>

  <div class="product-actions">
    <a href="<?php the_permalink(); ?>" class="btn-view">
      <?php esc_html_e('Découvrir', 'theme-sapi-maison'); ?> →
    </a>
  </div>
</li>

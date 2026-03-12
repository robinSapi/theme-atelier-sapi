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

global $product, $sapi_carousel_context;

// Ensure product data is properly set up
if (!$product || !is_a($product, 'WC_Product')) {
  $product = wc_get_product(get_the_ID());
}

// Check if we're in a carousel context (passed from archive-product.php or taxonomy-product_cat.php)
$is_carousel = !empty($sapi_carousel_context['is_carousel']);
$carousel_categories = !empty($sapi_carousel_context['categories']) ? $sapi_carousel_context['categories'] : '';

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
$is_variable = $product->is_type('variable');
$price_html = '';

if ($is_variable) {
  // Pour les produits variables, afficher uniquement le prix minimum
  $min_price = $product->get_variation_price('min');
  $price_html = wc_price($min_price);
  $filter_price = $min_price ? $min_price : $product->get_price();
} else {
  // Pour les produits simples, utiliser le HTML par défaut
  $price_html = $product->get_price_html();
  $filter_price = $product->get_price();
}

// Get wood essence from ACF or product attributes
$wood_essence = '';
if (function_exists('get_field')) {
  $wood_essence = get_field('essence_de_bois', $product_id);
}
if (!$wood_essence) {
  // Try product attributes (pa_bois then pa_materiau)
  $wood_attr = $product->get_attribute('pa_bois');
  if (!$wood_attr) {
    $wood_attr = $product->get_attribute('pa_materiau');
  }
  if ($wood_attr) {
    // For variable products, may return "Peuplier, Okoumé" — keep all, sanitized
    $wood_essence = sanitize_title($wood_attr);
  }
}

// Get size/dimensions in cm (numeric value for filtering)
$size_dimension = 0;
if (function_exists('get_field')) {
  // Try ACF field first (should be numeric in cm)
  $acf_size = get_field('hauteur_cm', $product_id);
  if ($acf_size) {
    $size_dimension = (float) $acf_size;
  }
}
if (!$size_dimension) {
  // Calculate from WooCommerce dimensions
  $height = (float) $product->get_height();
  $width = (float) $product->get_width();
  $length = (float) $product->get_length();
  $max_dim = max($height, $width, $length);
  if ($max_dim > 0) {
    $size_dimension = $max_dim;
  }
}

// Get gallery image for hover effect (lifestyle/ambiance photo)
$gallery_ids = $product->get_gallery_image_ids();
$hover_image_url = '';
if (!empty($gallery_ids)) {
  $hover_image_url = wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail');
}
?>

<?php
// Build classes - add carousel slide class if in carousel context
$card_classes = 'product-card-cinetique';
$is_editorial_carousel = !empty($sapi_carousel_context['is_editorial']);
$slide_index = 0;
$thumbnail_url = '';

if ($is_carousel) {
  if ($is_editorial_carousel) {
    $card_classes .= ' carousel-editorial-slide';
    $slide_index = isset($sapi_carousel_context['slide_index']) ? $sapi_carousel_context['slide_index'] : 0;
    $thumbnail_url = isset($sapi_carousel_context['thumbnail_url']) ? $sapi_carousel_context['thumbnail_url'] : '';
  } else {
    $card_classes .= ' products-carousel-slide';
  }
}

// Build data attributes - always include data-categories for filtering
$data_attrs = 'data-id="' . esc_attr($product_id) . '"';
$data_attrs .= ' data-category="' . esc_attr(sanitize_title($category_name)) . '"';
$filter_categories = $carousel_categories;
if (!$filter_categories && $categories && !is_wp_error($categories)) {
  $cat_slug_list = [];
  foreach ($categories as $c) {
    $cat_slug_list[] = $c->slug;
  }
  $filter_categories = implode(' ', $cat_slug_list);
}
$data_attrs .= $filter_categories ? ' data-categories="' . esc_attr($filter_categories) . '"' : '';
$data_attrs .= ' data-name="' . esc_attr(strtolower(get_the_title())) . '"';
$data_attrs .= ' data-price="' . esc_attr($filter_price) . '"';
$data_attrs .= $wood_essence ? ' data-wood="' . esc_attr(sanitize_title($wood_essence)) . '"' : '';
$data_attrs .= $size_dimension > 0 ? ' data-size="' . esc_attr($size_dimension) . '"' : '';

// Variation images for guide personalization (one image per essence)
if ($is_variable) {
  $variation_imgs = [];
  $main_img_id = $product->get_image_id();
  $children = $product->get_children();
  foreach ($children as $var_id) {
    $var_obj = wc_get_product($var_id);
    if (!$var_obj || !$var_obj->is_purchasable()) continue;
    $ess = $var_obj->get_attribute('pa_materiau');
    if (!$ess) continue;
    $ess_slug = sanitize_title($ess);
    if (isset($variation_imgs[$ess_slug])) continue;
    $img_id = $var_obj->get_image_id();
    if ($img_id && (int) $img_id !== (int) $main_img_id) {
      $img_url = wp_get_attachment_image_url($img_id, 'woocommerce_thumbnail');
      if ($img_url) {
        $variation_imgs[$ess_slug] = $img_url;
      }
    }
  }
  if (!empty($variation_imgs)) {
    $data_attrs .= ' data-variation-imgs="' . esc_attr(wp_json_encode($variation_imgs)) . '"';
  }
}

// Add editorial carousel specific attributes
if ($is_editorial_carousel) {
  $data_attrs .= ' data-slide-index="' . esc_attr($slide_index) . '"';
  if ($thumbnail_url) {
    $data_attrs .= ' data-thumbnail="' . esc_url($thumbnail_url) . '"';
  }
}
?>
<li <?php wc_product_class($card_classes, $product); ?> <?php echo $data_attrs; ?>>
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

      <button
        type="button"
        class="product-quick-view"
        data-product-id="<?php echo esc_attr($product_id); ?>"
        data-product-url="<?php echo esc_url(get_permalink($product_id)); ?>"
        aria-label="<?php echo esc_attr(sprintf(__('Aperçu rapide de %s', 'theme-sapi-maison'), get_the_title())); ?>">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        <?php esc_html_e('Aperçu', 'theme-sapi-maison'); ?>
      </button>
    </div>

    <div class="product-info">
      <h3 class="product-name"><?php the_title(); ?></h3>

      <?php if ($category_name && !is_product_category()) : ?>
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
      <?php esc_html_e('Découvrir', 'theme-sapi-maison'); ?> ⇾
    </a>
  </div>
</li>

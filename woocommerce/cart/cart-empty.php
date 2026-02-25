<?php
/**
 * Empty cart page — Atelier Sâpi
 * Overrides WooCommerce templates/cart/cart-empty.php
 */
defined('ABSPATH') || exit;

// Récupérer les produits récemment vus (cookie WooCommerce)
$viewed_products = !empty($_COOKIE['woocommerce_recently_viewed'])
  ? array_filter(array_map('absint', explode('|', wp_unslash($_COOKIE['woocommerce_recently_viewed']))))
  : [];

// Limiter à 3
$viewed_products = array_slice($viewed_products, 0, 3);
?>

<section class="empty-cart-page">
  <div class="empty-cart-content">
    <div class="empty-cart-icon">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="9" cy="21" r="1"/>
        <circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
    </div>
    <h1 class="empty-cart-title">Votre panier est vide... pour l'instant&nbsp;!</h1>
    <p class="empty-cart-text">Nos luminaires n'attendent que vous. Laissez-vous inspirer par nos créations artisanales.</p>
  </div>

  <?php if (!empty($viewed_products)) : ?>
    <div class="empty-cart-viewed">
      <h2 class="empty-cart-viewed-title">Vos dernières découvertes</h2>
      <div class="empty-cart-viewed-grid">
        <?php foreach ($viewed_products as $product_id) :
          $viewed_product = wc_get_product($product_id);
          if (!$viewed_product || !$viewed_product->is_visible()) continue;
        ?>
          <a href="<?php echo esc_url($viewed_product->get_permalink()); ?>" class="empty-cart-viewed-card">
            <div class="empty-cart-viewed-image">
              <?php echo $viewed_product->get_image('medium'); ?>
            </div>
            <div class="empty-cart-viewed-info">
              <?php
              $name_parts = explode(' ', $viewed_product->get_name(), 2);
              ?>
              <h3><?php echo esc_html($name_parts[0]); ?></h3>
              <?php if (isset($name_parts[1])) : ?>
                <span class="empty-cart-viewed-desc"><?php echo esc_html($name_parts[1]); ?></span>
              <?php endif; ?>
              <span class="empty-cart-viewed-price"><?php echo $viewed_product->get_price_html(); ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="empty-cart-cta">
    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="empty-cart-btn">Découvrir nos créations</a>
  </div>
</section>

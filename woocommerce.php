<?php
/**
 * WooCommerce Template
 * Wrapper pour toutes les pages WooCommerce (panier, checkout, compte, etc.)
 * Assure une largeur cohérente avec le reste du site
 *
 * @package Sapi-Maison
 */

get_header();

// Classe et style spécifiques pour la page panier
$wrapper_class = 'woocommerce-wrapper';
$wrapper_style = '';
$wrapper_attrs = '';

if (is_cart()) {
  $wrapper_class .= ' woocommerce-wrapper--cart';
  // Multiple fallback approaches for cart width
  $wrapper_style = ' style="max-width: 1200px !important; margin-left: auto !important; margin-right: auto !important; width: 100% !important; box-sizing: border-box !important;"';
  $wrapper_attrs = ' data-cart-page="true"';
}
?>

<div class="<?php echo esc_attr($wrapper_class); ?>"<?php echo $wrapper_style; ?><?php echo $wrapper_attrs; ?>>
  <?php woocommerce_content(); ?>
</div>

<?php if (is_cart()) : ?>
<!-- Fallback JavaScript pour forcer la largeur du panier -->
<script>
(function() {
  'use strict';

  // Force cart width with multiple selectors
  function forceCartWidth() {
    const cartWrapper = document.querySelector('[data-cart-page="true"]');
    const wooWrapper = document.querySelector('.woocommerce-wrapper--cart');
    const bodyCart = document.querySelector('body.woocommerce-cart');

    if (cartWrapper) {
      cartWrapper.style.cssText = 'max-width: 1200px !important; margin-left: auto !important; margin-right: auto !important; width: 100% !important; box-sizing: border-box !important;';
    }

    if (wooWrapper) {
      wooWrapper.style.cssText = 'max-width: 1200px !important; margin-left: auto !important; margin-right: auto !important; width: 100% !important; box-sizing: border-box !important;';
    }

    // Also target WooCommerce inner containers
    const cartForm = document.querySelector('.woocommerce-cart-form');
    const cartCollaterals = document.querySelector('.cart-collaterals');

    if (cartForm && cartForm.parentElement) {
      cartForm.parentElement.style.cssText = 'max-width: 1200px !important; margin: 0 auto !important;';
    }
  }

  // Apply on load
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', forceCartWidth);
  } else {
    forceCartWidth();
  }

  // Re-apply after any AJAX updates
  if (typeof jQuery !== 'undefined') {
    jQuery(document.body).on('updated_wc_div updated_cart_totals', forceCartWidth);
  }
})();
</script>
<?php endif; ?>

<?php
get_footer();

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

if (is_cart()) {
  $wrapper_class .= ' woocommerce-wrapper--cart';
  $wrapper_style = ' style="max-width: 1200px; margin-left: auto; margin-right: auto;"';
}
?>

<div class="<?php echo esc_attr($wrapper_class); ?>"<?php echo $wrapper_style; ?>>
  <?php woocommerce_content(); ?>
</div>

<?php
get_footer();

<?php
/**
 * The Template for displaying product archives
 *
 * SAPI CINÉTIQUE - Shop page with animated filters and enhanced grid
 *
 * @package Sapi-Maison
 * @version 9.4.0
 */

defined('ABSPATH') || exit;

get_header();

// Get all product categories for filters
$product_categories = get_terms([
  'taxonomy' => 'product_cat',
  'hide_empty' => true,
  'exclude' => [get_option('default_product_cat')], // Exclude "Uncategorized"
  'orderby' => 'menu_order',
  'order' => 'ASC',
]);
?>

<!-- Hero Section -->
<section class="shop-hero-cinetique">
  <span class="section-number">01</span>
  <h1><?php esc_html_e('Nos Créations', 'theme-sapi-maison'); ?></h1>
  <p class="shop-subtitle">
    <?php esc_html_e('Chaque pièce est unique, découpée au laser et assemblée à la main dans notre atelier lyonnais.', 'theme-sapi-maison'); ?>
  </p>
</section>

<!-- Product Filters -->
<nav class="product-filters" role="navigation" aria-label="<?php esc_attr_e('Filtres produits', 'theme-sapi-maison'); ?>">
  <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="filter-btn active" data-filter="all">
    <?php esc_html_e('Tout', 'theme-sapi-maison'); ?>
  </a>
  <?php if ($product_categories && !is_wp_error($product_categories)) : ?>
    <?php foreach ($product_categories as $cat) : ?>
      <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="filter-btn" data-filter="<?php echo esc_attr($cat->slug); ?>">
        <?php echo esc_html($cat->name); ?>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</nav>

<!-- Products Grid -->
<section class="shop-products">
  <?php if (woocommerce_product_loop()) : ?>

    <ul class="products-grid-cinetique products columns-4">
      <?php
      if (wc_get_loop_prop('total')) :
        while (have_posts()) :
          the_post();
          wc_get_template_part('content', 'product');
        endwhile;
      endif;
      ?>
    </ul>

    <?php woocommerce_pagination(); ?>

  <?php else : ?>

    <div class="woocommerce-no-products-found">
      <p><?php esc_html_e('Aucun produit ne correspond à votre recherche.', 'theme-sapi-maison'); ?></p>
      <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="button">
        <?php esc_html_e('Voir toutes les créations', 'theme-sapi-maison'); ?>
      </a>
    </div>

  <?php endif; ?>
</section>

<!-- Outro Section -->
<section class="shop-outro">
  <p class="shop-outro-text">
    <?php esc_html_e('Laissez-vous guider par la lumière...', 'theme-sapi-maison'); ?>
  </p>
</section>

<?php
get_footer();

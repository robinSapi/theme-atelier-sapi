<?php
/**
 * The Template for displaying product archives
 *
 * SAPI CINÉTIQUE - Shop page with carousel, client-side filters, and hover effects
 *
 * @package Sapi-Maison
 * @version 9.5.0
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

// Get ALL products (no pagination)
$all_products = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => -1,
  'post_status' => 'publish',
  'orderby' => 'menu_order date',
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

<!-- Product Filters (client-side filtering) -->
<nav class="product-filters product-filters-js" role="navigation" aria-label="<?php esc_attr_e('Filtres produits', 'theme-sapi-maison'); ?>">
  <button type="button" class="filter-btn active" data-filter="all">
    <?php esc_html_e('Tout', 'theme-sapi-maison'); ?>
  </button>
  <?php if ($product_categories && !is_wp_error($product_categories)) : ?>
    <?php foreach ($product_categories as $cat) : ?>
      <button type="button" class="filter-btn" data-filter="<?php echo esc_attr($cat->slug); ?>">
        <?php echo esc_html($cat->name); ?>
      </button>
    <?php endforeach; ?>
  <?php endif; ?>
</nav>

<!-- Products Carousel -->
<section class="shop-products">
  <?php if ($all_products->have_posts()) : ?>

    <div class="products-carousel-wrapper">
      <div class="products-carousel" data-products-carousel data-filterable>
        <ul class="products-carousel-track products">
          <?php while ($all_products->have_posts()) : $all_products->the_post(); ?>
            <?php
            global $product, $sapi_carousel_context;
            $product = wc_get_product(get_the_ID());

            // Pass carousel context to content-product.php
            $product_cats = get_the_terms(get_the_ID(), 'product_cat');
            $cat_slugs = [];
            if ($product_cats && !is_wp_error($product_cats)) {
              foreach ($product_cats as $cat) {
                $cat_slugs[] = $cat->slug;
              }
            }
            $sapi_carousel_context = [
              'is_carousel' => true,
              'categories' => implode(' ', $cat_slugs),
            ];

            wc_get_template_part('content', 'product');

            $sapi_carousel_context = null;
            ?>
          <?php endwhile; ?>
        </ul>
      </div>
      <div class="products-carousel-controls">
        <button class="carousel-btn products-carousel-prev" aria-label="<?php esc_attr_e('Précédent', 'theme-sapi-maison'); ?>">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        <div class="products-carousel-dots"></div>
        <button class="carousel-btn products-carousel-next" aria-label="<?php esc_attr_e('Suivant', 'theme-sapi-maison'); ?>">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
      </div>
    </div>

  <?php else : ?>

    <div class="woocommerce-no-products-found">
      <p><?php esc_html_e('Aucun produit ne correspond à votre recherche.', 'theme-sapi-maison'); ?></p>
    </div>

  <?php endif; ?>
  <?php wp_reset_postdata(); ?>
</section>

<!-- Outro Section -->
<section class="shop-outro">
  <p class="shop-outro-text">
    <?php esc_html_e('Laissez-vous guider par la lumière...', 'theme-sapi-maison'); ?>
  </p>
</section>

<?php
get_footer();

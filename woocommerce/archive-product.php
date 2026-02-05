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

<!-- Hero Section with Visual -->
<section class="shop-hero-cinetique shop-hero-visual">
  <div class="shop-hero-grid">
    <div class="shop-hero-content">
      <span class="section-number">01</span>
      <h1><?php esc_html_e('Nos Créations', 'theme-sapi-maison'); ?></h1>
      <p class="shop-subtitle">
        <?php esc_html_e('Luminaires uniques, découpés au laser et assemblés à la main dans notre atelier lyonnais.', 'theme-sapi-maison'); ?>
      </p>
      <a href="#shop-products" class="shop-hero-cta button">
        <?php esc_html_e('Découvrir la collection', 'theme-sapi-maison'); ?>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <polyline points="19 12 12 19 5 12"></polyline>
        </svg>
      </a>
    </div>
    <div class="shop-hero-visual-collage">
      <?php
      // Get 3 featured products for the collage
      $featured_products = wc_get_products([
        'limit' => 3,
        'status' => 'publish',
        'featured' => true,
        'return' => 'objects',
      ]);

      // Fallback to recent products if no featured
      if (empty($featured_products)) {
        $featured_products = wc_get_products([
          'limit' => 3,
          'status' => 'publish',
          'orderby' => 'date',
          'order' => 'DESC',
          'return' => 'objects',
        ]);
      }

      $collage_classes = ['collage-main', 'collage-accent-1', 'collage-accent-2'];
      $i = 0;
      foreach ($featured_products as $fp) :
        $img_id = $fp->get_image_id();
        $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'medium_large') : wc_placeholder_img_src('medium_large');
        $class = isset($collage_classes[$i]) ? $collage_classes[$i] : '';
      ?>
        <a href="<?php echo esc_url($fp->get_permalink()); ?>" class="collage-item <?php echo esc_attr($class); ?>">
          <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($fp->get_name()); ?>" loading="lazy">
        </a>
      <?php
        $i++;
      endforeach;
      ?>
    </div>
  </div>
</section>

<!-- Product Filters with dynamic counts -->
<nav class="product-filters product-filters-js" role="navigation" aria-label="<?php esc_attr_e('Filtres produits', 'theme-sapi-maison'); ?>">
  <button type="button" class="filter-btn active" data-filter="all">
    <?php esc_html_e('Tout', 'theme-sapi-maison'); ?>
    <span class="filter-count">(<?php echo esc_html($all_products->found_posts); ?>)</span>
  </button>
  <?php if ($product_categories && !is_wp_error($product_categories)) : ?>
    <?php foreach ($product_categories as $cat) : ?>
      <button type="button" class="filter-btn" data-filter="<?php echo esc_attr($cat->slug); ?>">
        <?php echo esc_html($cat->name); ?>
        <span class="filter-count">(<?php echo esc_html($cat->count); ?>)</span>
      </button>
    <?php endforeach; ?>
  <?php endif; ?>
</nav>

<!-- Products Carousel -->
<section class="shop-products" id="shop-products">
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

<!-- Outro Section with CTA -->
<section class="shop-outro">
  <div class="shop-outro-content">
    <p class="shop-outro-text">
      <?php esc_html_e('Vous ne trouvez pas votre bonheur ?', 'theme-sapi-maison'); ?>
    </p>
    <p class="shop-outro-subtitle">
      <?php esc_html_e('Dites-nous ce que vous imaginez et nous créerons ensemble votre luminaire sur-mesure.', 'theme-sapi-maison'); ?>
    </p>
    <a href="mailto:contact@atelier-sapi.fr" class="button button-outline shop-outro-cta">
      <?php esc_html_e('Contactez-nous', 'theme-sapi-maison'); ?>
    </a>
  </div>
</section>

<?php
get_footer();

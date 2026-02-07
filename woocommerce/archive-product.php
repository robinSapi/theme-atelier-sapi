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

<!-- Why Atelier Sapi - Brand Promise Section -->
<section class="why-sapi">
  <div class="why-sapi-inner">
    <div class="why-sapi-header">
      <span class="section-number">02</span>
      <h2>Pourquoi choisir l'Atelier Sâpi ?</h2>
      <p class="why-sapi-intro">Nous ne fabriquons pas juste des luminaires. Nous créons des pièces uniques qui transforment votre intérieur en un lieu où il fait bon vivre.</p>
    </div>

    <div class="why-sapi-grid">
      <div class="why-sapi-card">
        <div class="why-sapi-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
          </svg>
        </div>
        <h3>100% artisanal français</h3>
        <p>Chaque luminaire est conçu, découpé et assemblé à la main dans notre atelier lyonnais. Pas de production de masse, juste du savoir-faire et de la passion.</p>
      </div>

      <div class="why-sapi-card">
        <div class="why-sapi-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>
          </svg>
        </div>
        <h3>Pièces uniques & originales</h3>
        <p>Chaque modèle est une création originale signée Robin. Vous ne trouverez jamais nos luminaires ailleurs. Votre intérieur sera unique, comme vous.</p>
      </div>

      <div class="why-sapi-card">
        <div class="why-sapi-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
          </svg>
        </div>
        <h3>Bois PEFC & éco-responsable</h3>
        <p>Nos bois proviennent de forêts gérées durablement. Production locale, emballages recyclables, zéro gaspillage. Beauté et responsabilité vont de pair.</p>
      </div>

      <div class="why-sapi-card">
        <div class="why-sapi-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <h3>Service client réactif</h3>
        <p>Une question ? Un doute ? Besoin de conseils ? Robin est là pour vous accompagner personnellement, du choix à l'installation. Réponse rapide garantie.</p>
      </div>
    </div>

    <div class="why-sapi-highlight">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
      </svg>
      <div class="why-sapi-highlight-content">
        <h3>Fabriqué avec amour à Lyon</h3>
        <p>Robin conçoit, découpe, assemble et expédie personnellement chaque luminaire. Vous recevez bien plus qu'un objet : vous recevez une histoire, un bout de son atelier, une pièce qui porte son attention aux détails.</p>
      </div>
    </div>
  </div>
</section>

<!-- Product Filters with dynamic counts -->
<div class="product-filters-wrapper">
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

  <!-- Advanced Filters -->
  <div class="product-filters-advanced">
    <div class="filter-dropdown" data-filter-type="price">
      <button type="button" class="filter-dropdown-toggle">
        <span class="filter-label"><?php esc_html_e('Prix', 'theme-sapi-maison'); ?></span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </button>
      <div class="filter-dropdown-menu">
        <button type="button" class="filter-option active" data-price="all"><?php esc_html_e('Tous les prix', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-price="0-100"><?php esc_html_e('Moins de 100€', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-price="100-200"><?php esc_html_e('100€ - 200€', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-price="200-300"><?php esc_html_e('200€ - 300€', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-price="300+"><?php esc_html_e('Plus de 300€', 'theme-sapi-maison'); ?></button>
      </div>
    </div>

    <div class="filter-dropdown" data-filter-type="wood">
      <button type="button" class="filter-dropdown-toggle">
        <span class="filter-label"><?php esc_html_e('Essence', 'theme-sapi-maison'); ?></span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </button>
      <div class="filter-dropdown-menu">
        <button type="button" class="filter-option active" data-wood="all"><?php esc_html_e('Toutes les essences', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-wood="chene"><?php esc_html_e('Chêne', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-wood="hetre"><?php esc_html_e('Hêtre', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-wood="noyer"><?php esc_html_e('Noyer', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-wood="bouleau"><?php esc_html_e('Bouleau', 'theme-sapi-maison'); ?></button>
      </div>
    </div>

    <div class="filter-dropdown" data-filter-type="size">
      <button type="button" class="filter-dropdown-toggle">
        <span class="filter-label"><?php esc_html_e('Taille', 'theme-sapi-maison'); ?></span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
      </button>
      <div class="filter-dropdown-menu">
        <button type="button" class="filter-option active" data-size="all"><?php esc_html_e('Toutes les tailles', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-size="petit"><?php esc_html_e('Petit (&lt;30cm)', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-size="moyen"><?php esc_html_e('Moyen (30-60cm)', 'theme-sapi-maison'); ?></button>
        <button type="button" class="filter-option" data-size="grand"><?php esc_html_e('Grand (&gt;60cm)', 'theme-sapi-maison'); ?></button>
      </div>
    </div>

    <button type="button" class="filter-reset" style="display: none;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
      <?php esc_html_e('Réinitialiser', 'theme-sapi-maison'); ?>
    </button>
  </div>
</div>

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

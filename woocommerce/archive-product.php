<?php
/**
 * The Template for displaying product archives
 *
 * SAPI CINÉTIQUE - Shop page with carousel, client-side filters, and hover effects
 *
 * @package Sapi-Maison
 * @version 9.5.1
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

<!-- Hero Section - Magazine Style -->
<?php
// Priority 1: ACF custom hero image (attached to WooCommerce Shop page)
$hero_img_url = '';
$hero_alt = 'Nos Créations - Atelier Sâpi';
$shop_page_id = wc_get_page_id('shop');

if (function_exists('get_field')) {
  $acf_hero = get_field('shop_hero_image', $shop_page_id);
  if ($acf_hero) {
    $hero_img_url = sapi_get_acf_image_url($acf_hero);
    $hero_alt = is_array($acf_hero) && !empty($acf_hero['alt']) ? $acf_hero['alt'] : $hero_alt;
  }
}

// Priority 2: Fallback to featured product gallery image (ambiance photo)
if (!$hero_img_url) {
  $hero_products = wc_get_products([
    'limit'    => 1,
    'status'   => 'publish',
    'featured' => true,
    'return'   => 'objects',
  ]);

  if (empty($hero_products)) {
    $hero_products = wc_get_products([
      'limit'   => 1,
      'status'  => 'publish',
      'orderby' => 'date',
      'order'   => 'DESC',
      'return'  => 'objects',
    ]);
  }

  if (!empty($hero_products)) {
    $hero_product = $hero_products[0];
    // Try gallery first (ambiance photo), then main image
    $gallery_ids = $hero_product->get_gallery_image_ids();
    if (!empty($gallery_ids)) {
      $hero_img_url = wp_get_attachment_image_url($gallery_ids[0], 'full');
      $hero_alt = $hero_product->get_name() . ' - ambiance';
    } else {
      $hero_img_id = $hero_product->get_image_id();
      $hero_img_url = $hero_img_id
        ? wp_get_attachment_image_url($hero_img_id, 'full')
        : wc_placeholder_img_src('full');
      $hero_alt = $hero_product->get_name();
    }
  }
}
?>
<section class="shop-hero-cinetique shop-hero-magazine">
  <?php if ($hero_img_url) : ?>
    <img
      class="shop-hero-magazine-bg"
      src="<?php echo esc_url($hero_img_url); ?>"
      alt="<?php echo esc_attr($hero_alt); ?>"
      style="object-position: center;"
      fetchpriority="high"
    />
  <?php endif; ?>
  <div class="shop-hero-magazine-overlay"></div>
  <div class="shop-hero-magazine-content">
    <h1><?php esc_html_e('Nos Créations', 'theme-sapi-maison'); ?></h1>
    <p class="shop-subtitle">
      <?php esc_html_e('Luminaires uniques, découpés au laser et assemblés à la main dans notre atelier lyonnais.', 'theme-sapi-maison'); ?>
    </p>
  </div>
</section>

<!-- Product Filters with dynamic counts -->
<div class="product-filters-wrapper">
  <!-- Search Bar -->
  <div class="product-search-bar">
    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"></circle>
      <path d="m21 21-4.35-4.35"></path>
    </svg>
    <input
      type="text"
      id="product-search-input"
      class="product-search-input"
      placeholder="<?php esc_attr_e('Rechercher un luminaire...', 'theme-sapi-maison'); ?>"
      aria-label="<?php esc_attr_e('Rechercher un produit', 'theme-sapi-maison'); ?>"
    />
    <button type="button" class="search-clear" style="display: none;" aria-label="<?php esc_attr_e('Effacer la recherche', 'theme-sapi-maison'); ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </div>

  <nav class="product-filters product-filters-js" role="navigation" aria-label="<?php esc_attr_e('Filtres produits', 'theme-sapi-maison'); ?>">
    <button type="button" class="filter-btn active" data-filter="all">
      <?php esc_html_e('Tout', 'theme-sapi-maison'); ?>
      <span class="filter-count">(<?php echo esc_html($all_products->found_posts); ?>)</span>
    </button>
    <?php
    // Ordre personnalisé des catégories
    $cat_order = ['suspensions', 'appliques', 'lampeaposer', 'lampadaires', 'accessoires', 'carte-cadeau'];
    $cats_by_slug = [];
    if ($product_categories && !is_wp_error($product_categories)) {
      foreach ($product_categories as $cat) {
        $cats_by_slug[$cat->slug] = $cat;
      }
    }
    foreach ($cat_order as $slug) :
      if (!isset($cats_by_slug[$slug])) continue;
      $cat = $cats_by_slug[$slug];
      $btn_class = 'filter-btn';
      if ($cat->slug === 'carte-cadeau') {
        $btn_class .= ' filter-btn--gift';
      }
    ?>
      <button type="button" class="<?php echo esc_attr($btn_class); ?>" data-filter="<?php echo esc_attr($cat->slug); ?>">
        <?php echo esc_html($cat->name); ?>
        <span class="filter-count">(<?php echo esc_html($cat->count); ?>)</span>
      </button>
    <?php endforeach; ?>
  </nav>

</div>

<!-- Products Grid -->
<section class="shop-products" id="shop-products">
  <?php if ($all_products->have_posts()) : ?>
    <?php
    // "Pourquoi choisir l'Atelier Sâpi" — cards inserted in the product grid
    // Positions aléatoires dans des zones pour varier la colonne à chaque chargement
    $sapi_card_contents = [
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>',
        'title' => '100% artisanal français',
        'text' => 'Chaque luminaire est conçu, découpé et assemblé à la main dans notre atelier lyonnais. Pas de production de masse, juste du savoir-faire et de la passion.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
        'title' => 'Pièces uniques & originales',
        'text' => 'Chaque modèle est une création originale signée Robin. Vous ne trouverez jamais nos luminaires ailleurs. Votre intérieur sera unique, comme vous.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'title' => 'Bois PEFC & éco-responsable',
        'text' => 'Nos bois proviennent de forêts gérées durablement. Production locale, emballages recyclables, zéro gaspillage. Beauté et responsabilité vont de pair.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'title' => 'Service client réactif',
        'text' => 'Une question ? Un doute ? Besoin de conseils ? Robin est là pour vous accompagner personnellement, du choix à l\'installation. Réponse rapide garantie.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'title' => 'Fabriqué avec amour à Lyon',
        'text' => 'Robin conçoit, découpe, assemble et expédie personnellement chaque luminaire. Vous recevez bien plus qu\'un objet : vous recevez une histoire, un bout de son atelier, une pièce qui porte son attention aux détails.',
        'highlight' => true,
      ],
    ];
    // Zones d'insertion : chaque card apparaît dans une plage aléatoire
    $sapi_card_zones = [[3, 6], [8, 11], [13, 16], [18, 21], [23, 26]];
    $sapi_text_cards = [];
    foreach ($sapi_card_zones as $i => $zone) {
      if (isset($sapi_card_contents[$i])) {
        $pos = wp_rand($zone[0], $zone[1]);
        $sapi_text_cards[$pos] = $sapi_card_contents[$i];
      }
    }
    $sapi_product_counter = 0;
    ?>

    <ul class="products columns-3">
      <?php
      while ($all_products->have_posts()) :
        $all_products->the_post();
        global $product, $sapi_carousel_context;
        $product = wc_get_product(get_the_ID());

        // Pass category context for filters
        $product_id = $product->get_id();
        $product_cats = get_the_terms($product_id, 'product_cat');
        $cat_slugs = [];
        if ($product_cats && !is_wp_error($product_cats)) {
          foreach ($product_cats as $cat) {
            $cat_slugs[] = $cat->slug;
          }
        }
        $sapi_carousel_context = [
          'is_carousel' => false,
          'categories' => implode(' ', $cat_slugs),
        ];

        wc_get_template_part('content', 'product');

        $sapi_carousel_context = null;
        $sapi_product_counter++;

        if (isset($sapi_text_cards[$sapi_product_counter])) :
          $card = $sapi_text_cards[$sapi_product_counter];
          $card_class = 'product-text-card';
          if (!empty($card['highlight'])) $card_class .= ' product-text-card--highlight';
        ?>
        <li class="<?php echo esc_attr($card_class); ?>">
          <div class="product-text-card-inner">
            <div class="product-text-card-icon"><?php echo $card['icon']; ?></div>
            <h3><?php echo esc_html($card['title']); ?></h3>
            <p><?php echo esc_html($card['text']); ?></p>
          </div>
        </li>
        <?php endif;

      endwhile;
      ?>
    </ul>

    <!-- Message "aucun résultat" pour le filtrage JS côté client -->
    <div class="woocommerce-no-products-found" style="display: none;">
      <p><?php esc_html_e('Aucun produit ne correspond à votre recherche.', 'theme-sapi-maison'); ?></p>
    </div>

  <?php else : ?>

    <div class="woocommerce-no-products-found">
      <p><?php esc_html_e('Aucun produit ne correspond à votre recherche.', 'theme-sapi-maison'); ?></p>
    </div>

  <?php endif; ?>
  <?php wp_reset_postdata(); ?>

  <!-- Grosse card récap "Pourquoi choisir Sâpi" — visible uniquement avec filtres actifs -->
  <div class="why-sapi-recap" style="display: none;">
    <div class="why-sapi-recap-inner">
      <h2>Pourquoi choisir l'Atelier Sâpi ?</h2>
      <div class="why-sapi-recap-grid">
        <div class="why-sapi-recap-item">
          <div class="product-text-card-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
          </div>
          <h3>100% artisanal français</h3>
          <p>Chaque luminaire est conçu, découpé et assemblé à la main dans notre atelier lyonnais.</p>
        </div>
        <div class="why-sapi-recap-item">
          <div class="product-text-card-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
          </div>
          <h3>Pièces uniques & originales</h3>
          <p>Chaque modèle est une création originale signée Robin. Votre intérieur sera unique, comme vous.</p>
        </div>
        <div class="why-sapi-recap-item">
          <div class="product-text-card-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <h3>Bois PEFC & éco-responsable</h3>
          <p>Nos bois proviennent de forêts gérées durablement. Beauté et responsabilité vont de pair.</p>
        </div>
        <div class="why-sapi-recap-item">
          <div class="product-text-card-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <h3>Service client réactif</h3>
          <p>Robin est là pour vous accompagner personnellement, du choix à l'installation.</p>
        </div>
      </div>
      <div class="why-sapi-recap-highlight">
        <div class="product-text-card-icon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </div>
        <div>
          <h3>Fabriqué avec amour à Lyon</h3>
          <p>Vous recevez bien plus qu'un objet : vous recevez une histoire, un bout de notre atelier, une pièce qui porte notre attention aux détails.</p>
        </div>
      </div>
    </div>
  </div>
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
    <a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>" class="button button-outline shop-outro-cta">
      <?php esc_html_e('Découvrir le sur mesure', 'theme-sapi-maison'); ?>
    </a>
  </div>
</section>

<?php
get_footer();

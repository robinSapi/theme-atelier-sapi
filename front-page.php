<?php
/**
 * Front Page Template - CINÉTIQUE Design
 *
 * @package Theme_Sapi_Maison
 */

get_header();

// Query products for full-page carousel - two from each category with ambiance_1 field
// Order: suspension, applique, lampe à poser, lampadaire (x2)
$carousel_products = [];
$categories_order = ['suspensions', 'appliques', 'lampeaposer', 'lampadaires'];

// Get 2 products from each category
$products_by_category = [];
foreach ($categories_order as $cat_slug) {
  $args = [
    'post_type' => 'product',
    'posts_per_page' => 2, // Get 2 products instead of 1
    'post_status' => 'publish',
    'tax_query' => [
      [
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => $cat_slug,
      ],
    ],
    'meta_query' => [
      [
        'key' => 'ambiance_1',
        'compare' => 'EXISTS',
      ],
    ],
    'orderby' => 'rand',
  ];

  $query = new WP_Query($args);
  $products_by_category[$cat_slug] = [];

  if ($query->have_posts()) {
    while ($query->have_posts()) {
      $query->the_post();
      $product = wc_get_product(get_the_ID());
      $ambiance_1 = get_field('ambiance_1');

      if ($ambiance_1 && $product) {
        // Handle different ACF return formats for image
        $image_url = '';
        if (is_array($ambiance_1) && isset($ambiance_1['url'])) {
          $image_url = $ambiance_1['url'];
        } elseif (is_array($ambiance_1) && isset($ambiance_1['ID'])) {
          $image_url = wp_get_attachment_image_url($ambiance_1['ID'], 'full');
        } elseif (is_numeric($ambiance_1)) {
          $image_url = wp_get_attachment_image_url($ambiance_1, 'full');
        } elseif (is_string($ambiance_1) && strpos($ambiance_1, 'http') === 0) {
          $image_url = $ambiance_1;
        }

        // Get minimum price
        if ($product->is_type('variable')) {
          $min_price = $product->get_variation_price('min');
          $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
        } else {
          $price_display = wc_price($product->get_price());
        }

        if ($image_url) {
          $products_by_category[$cat_slug][] = [
            'id' => get_the_ID(),
            'name' => get_the_title(),
            'price' => $price_display,
            'url' => get_permalink(),
            'image' => $image_url,
          ];
        }
      }
    }
  }
  wp_reset_postdata();
}

// Interleave products: suspension, applique, lampe, lampadaire, suspension, applique, lampe, lampadaire
foreach ($categories_order as $cat_slug) {
  if (isset($products_by_category[$cat_slug][0])) {
    $carousel_products[] = $products_by_category[$cat_slug][0];
  }
}
foreach ($categories_order as $cat_slug) {
  if (isset($products_by_category[$cat_slug][1])) {
    $carousel_products[] = $products_by_category[$cat_slug][1];
  }
}

// Query 2 random products for small bento cards (only from main categories)
$random_products = [];
$random_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => 2,
  'post_status' => 'publish',
  'orderby' => 'rand',
  'tax_query' => [
    [
      'taxonomy' => 'product_cat',
      'field' => 'slug',
      'terms' => ['suspensions', 'appliques', 'lampeaposer', 'lampadaires'],
      'operator' => 'IN',
    ],
  ],
]);

if ($random_query->have_posts()) {
  while ($random_query->have_posts()) {
    $random_query->the_post();
    $product = wc_get_product(get_the_ID());

    if ($product && has_post_thumbnail()) {
      $random_products[] = [
        'name' => get_the_title(),
        'url' => get_permalink(),
        'image' => get_the_post_thumbnail_url(get_the_ID(), 'woocommerce_thumbnail'),
      ];
    }
  }
  wp_reset_postdata();
}

// Query latest product for "Nouveauté" block
$latest_product = null;
$latest_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => 1,
  'post_status' => 'publish',
  'orderby' => 'date',
  'order' => 'DESC',
  'tax_query' => [
    [
      'taxonomy' => 'product_cat',
      'field' => 'slug',
      'terms' => ['suspensions', 'appliques', 'lampeaposer', 'lampadaires'],
      'operator' => 'IN',
    ],
  ],
]);

if ($latest_query->have_posts()) {
  while ($latest_query->have_posts()) {
    $latest_query->the_post();
    $product = wc_get_product(get_the_ID());

    // Get product category
    $categories = get_the_terms(get_the_ID(), 'product_cat');
    $category_name = '';
    if ($categories && !is_wp_error($categories)) {
      foreach ($categories as $cat) {
        if ($cat->slug !== 'uncategorized') {
          $category_name = $cat->name;
          break;
        }
      }
    }

    // Get wood essence from ACF or product attributes
    $wood_essence = '';
    if (function_exists('get_field')) {
      $wood_essence = get_field('essence_de_bois', get_the_ID());
    }
    if (!$wood_essence) {
      $wood_attr = $product->get_attribute('pa_bois');
      if ($wood_attr) {
        $wood_essence = $wood_attr;
      }
    }

    // Build category display
    $category_display = $category_name;
    if ($wood_essence) {
      $category_display .= ' · ' . $wood_essence;
    }

    // Get price
    if ($product->is_type('variable')) {
      $min_price = $product->get_variation_price('min');
      $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
    } else {
      $price_display = wc_price($product->get_price());
    }

    $latest_product = [
      'name' => get_the_title(),
      'category' => $category_display,
      'price' => $price_display,
      'image' => get_the_post_thumbnail_url(get_the_ID(), 'woocommerce_single'),
      'url' => get_permalink(),
      'badge' => 'Nouveau',
    ];
  }
  wp_reset_postdata();
}

// Featured products for Bento grid (random product)
$featured_products = [];
$featured_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => 1,
  'post_status' => 'publish',
  'orderby' => 'rand',
  'tax_query' => [
    [
      'taxonomy' => 'product_cat',
      'field' => 'slug',
      'terms' => ['suspensions', 'appliques', 'lampeaposer', 'lampadaires'],
      'operator' => 'IN',
    ],
  ],
]);

if ($featured_query->have_posts()) {
  while ($featured_query->have_posts()) {
    $featured_query->the_post();
    $product = wc_get_product(get_the_ID());

    if ($product) {
      // Get detail_1 ACF field
      $detail_1 = get_field('detail_1', get_the_ID());
      $image_url = '';

      if ($detail_1) {
        // Handle different ACF return formats
        if (is_array($detail_1) && isset($detail_1['url'])) {
          $image_url = $detail_1['url'];
        } elseif (is_array($detail_1) && isset($detail_1['ID'])) {
          $image_url = wp_get_attachment_image_url($detail_1['ID'], 'full');
        } elseif (is_numeric($detail_1)) {
          $image_url = wp_get_attachment_image_url($detail_1, 'full');
        } elseif (is_string($detail_1) && strpos($detail_1, 'http') === 0) {
          $image_url = $detail_1;
        }
      }

      if ($image_url) {
        // Get price
        if ($product->is_type('variable')) {
          $min_price = $product->get_variation_price('min');
          $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
        } else {
          $price_display = wc_price($product->get_price());
        }

        $featured_products[] = [
          'name' => get_the_title(),
          'price' => $price_display,
          'image' => $image_url,
          'url' => get_permalink(),
        ];
      }
    }
  }
  wp_reset_postdata();
}

// Collections
$collections = [
  [
    'name' => 'Suspensions',
    'count' => '12 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg',
    'url' => '/categorie-produit/suspension/',
  ],
  [
    'name' => 'Lampadaires',
    'count' => '8 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Claudine-bandeau-1.jpg',
    'url' => '/categorie-produit/lampadaire/',
  ],
  [
    'name' => 'Appliques',
    'count' => '6 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg',
    'url' => '/categorie-produit/applique/',
  ],
  [
    'name' => 'À poser',
    'count' => '5 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Charlie-Bandeau-2.jpg',
    'url' => '/categorie-produit/lampe-a-poser/',
  ],
];
?>

<!-- Custom Cursor (desktop only, hidden on touch devices) -->
<div class="cursor-custom">
  <div class="cursor-dot"></div>
  <div class="cursor-outline"></div>
</div>

<!-- Full Page Carousel -->
<?php if (!empty($carousel_products)) : ?>
<section class="homepage-carousel-fullscreen">
  <div class="carousel-container">
    <div class="carousel-slides">
    <?php foreach ($carousel_products as $index => $product) : ?>
      <div class="carousel-slide<?php echo $index === 0 ? ' active' : ''; ?>"
           style="background-image: url('<?php echo esc_url($product['image']); ?>');">
        <div class="carousel-overlay"></div>
        <div class="carousel-content">
          <p class="carousel-product-name"><?php echo esc_html($product['name']); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

    <!-- Hero Text -->
    <div class="carousel-hero-text">
      <h1 class="carousel-hero-title">LUMINAIRE EN BOIS — ATELIER SÂPI</h1>
      <h2 class="carousel-hero-subtitle">Découvrez les luminaires en bois de Robin,<br>fabriqués avec passion à la commande</h2>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Hero Bento Grid -->
<section class="hero-bento">
  <div class="bento-container">
    <!-- Large Hero Card -->
    <div class="bento-card bento-hero">
      <div class="bento-bg" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg');"></div>
      <div class="bento-content">
        <h1 class="bento-title">Sculpter<br>la lumière</h1>
        <p class="bento-text">Des créations artisanales qui transforment l'espace</p>
        <div class="hero-cta-row">
          <a href="<?php echo home_url('/boutique/'); ?>" class="hero-cta">
            <span>Découvrir nos créations</span>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
            </svg>
          </a>
        </div>
      </div>
    </div>

    <!-- Statement Card -->
    <div class="bento-card bento-statement">
      <div class="statement-inner">
        <span class="statement-number">01</span>
        <h2 class="statement-text">"Je ne fabrique pas<br>des lampes.<br>Je crée des présences."</h2>
        <p class="statement-author">— Robin, artisan</p>
      </div>
    </div>

    <!-- Product Card - Nouveauté (static) -->
    <?php if ($latest_product) : ?>
    <a href="<?php echo esc_url($latest_product['url']); ?>" class="bento-card bento-product-featured">
      <div class="bento-bg" style="background-image: url('<?php echo esc_url($latest_product['image']); ?>');"></div>
      <?php if ($latest_product['badge']) : ?>
        <span class="bento-product-featured-badge"><?php echo esc_html($latest_product['badge']); ?></span>
      <?php endif; ?>
      <div class="bento-product-featured-info">
        <h3><?php echo esc_html($latest_product['name']); ?></h3>
        <span class="bento-product-featured-price"><?php echo wp_kses_post($latest_product['price']); ?></span>
      </div>
    </a>
    <?php endif; ?>

    <!-- Stats Card -->
    <div class="bento-card bento-stats">
      <a href="<?php echo home_url('/lumiere-dartisan/'); ?>" class="stat-block">
        <div class="stat-content">
          <strong>100%</strong>
          <span>Fait main</span>
        </div>
        <div class="stat-hover">
          <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/Robin-Sapi-A.jpg" alt="Robin à l'atelier">
          <span class="stat-hover-text">Découvrir l'artisan →</span>
        </div>
      </a>
      <a href="<?php echo home_url('/lumiere-dartisan/#savoir-faire'); ?>" class="stat-block">
        <div class="stat-content">
          <strong>&lt;5j</strong>
          <span>Fabrication</span>
        </div>
        <div class="stat-hover">
          <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Charlie-Bandeau-2.jpg" alt="Lampe dans un intérieur">
          <span class="stat-hover-text">Le processus →</span>
        </div>
      </a>
      <a href="<?php echo home_url('/contact/'); ?>" class="stat-block">
        <div class="stat-content">
          <strong>Lyon</strong>
          <span>Atelier</span>
        </div>
        <div class="stat-hover">
          <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg" alt="Création dans un appartement">
          <span class="stat-hover-text">Nous contacter →</span>
        </div>
      </a>
    </div>

    <!-- Small Product Cards (Random) -->
    <?php if (!empty($random_products)) : ?>
      <?php foreach ($random_products as $random_product) : ?>
        <a href="<?php echo esc_url($random_product['url']); ?>" class="bento-card bento-product-small">
          <div class="product-image-small" style="background-image: url('<?php echo esc_url($random_product['image']); ?>');"></div>
          <div class="product-overlay-small">
            <h4 class="product-name-small"><?php echo esc_html($random_product['name']); ?></h4>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- Collections Grid -->
<section class="collections-kinetic">
  <div class="section-header-kinetic">
    <span class="section-num">02</span>
    <h2 class="section-title-kinetic">Collections</h2>
  </div>

  <div class="collections-grid">
    <?php foreach ($collections as $collection) : ?>
      <a href="<?php echo esc_url($collection['url']); ?>" class="collection-card">
        <div class="collection-visual" style="background-image: url('<?php echo esc_url($collection['image']); ?>');"></div>
        <div class="collection-details">
          <h3><?php echo esc_html($collection['name']); ?></h3>
          <div class="collection-meta">
            <span class="collection-count"><?php echo esc_html($collection['count']); ?></span>
            <span class="collection-btn">→</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Hero Bento Grid (continued) -->
<section class="hero-bento">
  <div class="bento-container">
    <!-- Process Card -->
    <div class="bento-card bento-process">
      <div class="process-header">
        <span class="process-number">03</span>
        <h3 class="process-title">"Mon processus artisanal"</h3>
      </div>
      <div class="process-inner">
        <div class="process-step">
          <span class="step-num">01</span>
          <span class="step-text">Dessin</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/IMG_1928.png');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">02</span>
          <span class="step-text">Découpe laser</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/IMG_7638.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">03</span>
          <span class="step-text">Finitions</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/03/P_SLM_XL_det5.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">04</span>
          <span class="step-text">Assemblage</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/Robin-Sapi-A.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">05</span>
          <span class="step-text">Expédition</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Claudine-bandeau-1.jpg');"></div>
        </div>
      </div>
    </div>

    <!-- Product Card - Random Featured Product -->
    <?php if (!empty($featured_products)) : ?>
    <a href="<?php echo esc_url($featured_products[0]['url']); ?>" class="bento-card bento-product-featured">
      <div class="bento-bg" style="background-image: url('<?php echo esc_url($featured_products[0]['image']); ?>');"></div>
      <div class="bento-product-featured-info">
        <h3><?php echo esc_html($featured_products[0]['name']); ?></h3>
        <span class="bento-product-featured-price"><?php echo wp_kses_post($featured_products[0]['price']); ?></span>
      </div>
    </a>
    <?php endif; ?>

    <!-- Atelier Image -->
    <div class="bento-card bento-atelier">
      <div class="bento-bg" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/Robin-Sapi-A.jpg');"></div>
      <div class="atelier-label">
        <span>L'atelier · Lyon</span>
      </div>
    </div>

    <!-- CTA Card -->
    <a href="<?php echo home_url('/boutique/'); ?>" class="bento-card bento-cta">
      <h3 class="cta-title">Toutes les créations</h3>
      <span class="cta-button">
        <span>Explorer</span>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/>
        </svg>
      </span>
    </a>
  </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-kinetic">
  <div class="section-header-kinetic">
    <span class="section-num">04</span>
    <h2 class="section-title-kinetic">Restez informés</h2>
  </div>
  <p class="newsletter-subtitle">Nouveautés, éditions limitées, coulisses d'atelier.</p>
  <form class="newsletter-form" action="#" method="post">
    <input type="email" placeholder="votre@email.fr" class="newsletter-input-kinetic" required />
    <button type="submit" class="newsletter-submit-kinetic">
      <span>S'inscrire</span>
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
      </svg>
    </button>
  </form>
</section>

<script>
(function() {
  const carousel = document.querySelector('.homepage-carousel-fullscreen');

  // 1. Déplacer le bandeau de réassurance juste sous le carousel
  const reassuranceBar = document.querySelector('.reassurance-bar-sticky');
  if (reassuranceBar && carousel) {
    carousel.parentNode.insertBefore(reassuranceBar, carousel.nextSibling);
    reassuranceBar.classList.add('home-repositioned-bar');
  }

  // 2. Menu : transparent sur le carousel, opaque après
  const header = document.querySelector('.site-header');
  if (header && carousel) {
    function updateHeaderState() {
      const carouselBottom = carousel.offsetTop + carousel.offsetHeight;
      if (window.scrollY + header.offsetHeight >= carouselBottom) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    }
    window.addEventListener('scroll', updateHeaderState, { passive: true });
    updateHeaderState();
  }
})();
</script>

<?php
get_footer();

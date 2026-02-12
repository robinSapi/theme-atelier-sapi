<?php
/**
 * Front Page Template - CINÉTIQUE Design
 *
 * @package Theme_Sapi_Maison
 */

get_header();

// Query products for full-page carousel - one from each category with ambiance_1 field
$carousel_products = [];
$categories_order = ['suspensions', 'appliques', 'lampeaposer', 'lampadaires'];

foreach ($categories_order as $cat_slug) {
  $args = [
    'post_type' => 'product',
    'posts_per_page' => 1,
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
    'orderby' => 'rand', // Random product from category
  ];

  $query = new WP_Query($args);

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
          $carousel_products[] = [
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

// Featured products for Bento grid (other products)
$featured_products = [
  [
    'name' => 'Suze la Méduse',
    'category' => 'Applique · Formes organiques',
    'price' => '129€',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg',
    'url' => '/produit/suze-la-meduse/',
    'badge' => null,
  ],
];

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
          <h2 class="carousel-product-name"><?php echo esc_html($product['name']); ?></h2>
          <div class="carousel-product-price">
            <span class="price-label">À partir de</span>
            <?php echo $product['price']; ?>
          </div>
          <a href="<?php echo esc_url($product['url']); ?>" class="carousel-btn-discover">
            Découvrir
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
            </svg>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

    <!-- Navigation Dots -->
    <div class="carousel-dots">
      <?php foreach ($carousel_products as $index => $product) : ?>
        <button class="carousel-dot<?php echo $index === 0 ? ' active' : ''; ?>"
                data-slide="<?php echo $index; ?>"
                aria-label="Aller au produit <?php echo $index + 1; ?>"></button>
      <?php endforeach; ?>
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

    <!-- Product Card 1 (Latest Product - Nouveauté) -->
    <?php if ($latest_product) : ?>
    <a href="<?php echo esc_url($latest_product['url']); ?>" class="bento-card bento-product bento-product-latest">
      <div class="product-image" style="background-image: url('<?php echo esc_url($latest_product['image']); ?>');"></div>
      <div class="product-overlay">
        <?php if ($latest_product['badge']) : ?>
          <div class="product-badge"><?php echo esc_html($latest_product['badge']); ?></div>
        <?php endif; ?>
        <div class="product-info-reveal">
          <h3 class="product-name"><?php echo esc_html($latest_product['name']); ?></h3>
          <div class="product-price-tag">
            <span><?php echo wp_kses_post($latest_product['price']); ?></span>
          </div>
        </div>
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

    <!-- Process Card -->
    <div class="bento-card bento-process">
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
          <span class="step-text">Assemblage</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/Robin-Sapi-A.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">04</span>
          <span class="step-text">Finitions</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/03/P_SLM_XL_det5.jpg');"></div>
        </div>
      </div>
    </div>

    <!-- Product Card 3 -->
    <a href="<?php echo esc_url($featured_products[0]['url']); ?>" class="bento-card bento-product">
      <div class="product-image" style="background-image: url('<?php echo esc_url($featured_products[0]['image']); ?>');"></div>
      <div class="product-overlay">
        <?php if ($featured_products[0]['badge']) : ?>
          <div class="product-badge"><?php echo esc_html($featured_products[0]['badge']); ?></div>
        <?php endif; ?>
        <div class="product-info-reveal">
          <h3 class="product-name"><?php echo esc_html($featured_products[0]['name']); ?></h3>
          <p class="product-cat"><?php echo esc_html($featured_products[0]['category']); ?></p>
          <div class="product-price-tag">
            <span><?php echo esc_html($featured_products[0]['price']); ?></span>
          </div>
        </div>
      </div>
    </a>

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

<!-- Newsletter Section -->
<section class="newsletter-kinetic">
  <div class="newsletter-content">
    <div class="newsletter-text">
      <span class="section-num">03</span>
      <h2 class="section-title-kinetic">Restez<br>informés</h2>
      <p>Nouveautés, éditions limitées, coulisses d'atelier.</p>
    </div>
    <form class="newsletter-form" action="#" method="post">
      <input type="email" placeholder="votre@email.fr" class="newsletter-input-kinetic" required />
      <button type="submit" class="newsletter-submit-kinetic">
        <span>S'inscrire</span>
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
        </svg>
      </button>
    </form>
  </div>
</section>

<?php
get_footer();

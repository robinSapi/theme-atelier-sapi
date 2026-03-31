<?php
/**
 * Front Page Template - CINÉTIQUE Design
 *
 * @package Theme_Sapi_Maison
 */

get_header();

// Query products for full-page carousel - two from each category with ambiance photos
// Order: suspension, applique, lampe à poser, lampadaire (x2)
$carousel_products = [];
$categories_order = ['suspensions', 'appliques', 'lampesaposer', 'lampadaires'];

// Get 2 products from each category
$products_by_category = [];
foreach ($categories_order as $cat_slug) {
  $args = [
    'post_type' => 'product',
    'posts_per_page' => 4,
    'post_status' => 'publish',
    'tax_query' => [
      [
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => $cat_slug,
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

      if ($product) {
        $ambiance_photos = sapi_get_product_photos(get_the_ID(), 'ambiance', 1);
        $image_url = !empty($ambiance_photos) ? $ambiance_photos[0] : '';

        if ($image_url) {
          // Get minimum price
          if ($product->is_type('variable')) {
            $min_price = $product->get_variation_price('min');
            $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
          } else {
            $price_display = wc_price($product->get_price());
          }

          $products_by_category[$cat_slug][] = [
            'id' => get_the_ID(),
            'name' => get_the_title(),
            'price' => $price_display,
            'url' => get_permalink(),
            'image' => $image_url,
          ];

          if (count($products_by_category[$cat_slug]) >= 2) break;
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

// Star du moment — lit le champ ACF produit_star de la page "La star du moment"
$star_product_data = null;
$star_page = get_page_by_path('la-star-du-moment');
if ($star_page && function_exists('get_field')) {
  $star_post = get_field('produit_star', $star_page->ID);
  $star_id = $star_post ? (is_object($star_post) ? $star_post->ID : (int) $star_post) : 0;
  $product = $star_id ? wc_get_product($star_id) : null;

  if ($product) {
    if ($product->is_type('variable')) {
      $min_price = $product->get_variation_price('min');
      $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
    } else {
      $price_display = wc_price($product->get_price());
    }

    $categories = get_the_terms($star_id, 'product_cat');
    $category_name = '';
    if ($categories && !is_wp_error($categories)) {
      foreach ($categories as $cat) {
        if ($cat->slug !== 'uncategorized') {
          $category_name = str_replace(
            ['Suspensions', 'Appliques', 'Lampadaires', 'Lampes à poser'],
            ['Suspension',  'Applique',  'Lampadaire',  'Lampe à poser'],
            $cat->name
          );
          break;
        }
      }
    }

    $detail_photos = sapi_get_product_photos($star_id, 'detail', 1);
    $image_url = !empty($detail_photos) ? $detail_photos[0] : '';
    if (!$image_url) {
      $image_url = get_the_post_thumbnail_url($star_id, 'woocommerce_single');
    }

    $star_product_data = [
      'name'     => $product->get_name(),
      'category' => $category_name,
      'price'    => $price_display,
      'image'    => $image_url,
      'url'      => home_url('/la-star-du-moment/'),
    ];
  }
}

// Query Carte Cadeau
$gift_card = null;
$gc_query = new WP_Query([
  'post_type'      => 'product',
  'posts_per_page' => 1,
  'post_status'    => 'publish',
  'tax_query'      => [['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => 'carte-cadeau']],
]);

if ($gc_query->have_posts()) {
  $gc_query->the_post();
  $product = wc_get_product(get_the_ID());

  if ($product) {
    if ($product->is_type('variable')) {
      $min_price = $product->get_variation_price('min');
      $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
    } else {
      $price_display = wc_price($product->get_price());
    }

    // Image custom : Bandeau-Noel.jpg depuis la médiathèque
    $bandeau_noel = get_posts([
      'post_type'      => 'attachment',
      'posts_per_page' => 1,
      'post_status'    => 'inherit',
      'meta_query'     => [['key' => '_wp_attached_file', 'value' => 'Bandeau-Noel', 'compare' => 'LIKE']],
    ]);
    $gc_image = $bandeau_noel
      ? wp_get_attachment_image_url($bandeau_noel[0]->ID, 'large')
      : get_the_post_thumbnail_url(get_the_ID(), 'woocommerce_single');

    $gift_card = [
      'name'  => get_the_title(),
      'price' => $price_display,
      'image' => $gc_image,
      'url'   => get_permalink(),
    ];
  }
  wp_reset_postdata();
}

// Room picker now opens Mon Projet banner instead of guide-luminaire page
$creations_url = home_url('/mes-creations/');

// Room choices for mini-questionnaire "Pour quelle pièce ?"
$room_choices = [
  ['label' => 'Salon',   'slug' => 'salon',    'icon' => 'sofa'],
  ['label' => 'Cuisine', 'slug' => 'cuisine',  'icon' => 'dining'],
  ['label' => 'Chambre', 'slug' => 'chambre',  'icon' => 'bed'],
  ['label' => 'Bureau',  'slug' => 'bureau',   'icon' => 'monitor'],
  ['label' => 'Entrée',  'slug' => 'entree',   'icon' => 'door'],
  ['label' => 'Escalier','slug' => 'escalier', 'icon' => 'stairs'],
];

$room_icons = [
  'sofa'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><rect x="2" y="11" width="20" height="7" rx="2"/><path d="M5 18v2m14-2v2"/></svg>',
  'dining'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V20H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>',
  'bed'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>',
  'monitor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>',
  'door'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="15" cy="12" r="1"/></svg>',
  'stairs'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4v-4h4v-4h4V8h4"/><path d="M4 20V8"/><path d="M20 20V8"/></svg>',
  'autre'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
];

// Featured products for Bento grid (random product)
$featured_products = [];
$featured_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => 4,
  'post_status' => 'publish',
  'orderby' => 'rand',
  'tax_query' => [
    [
      'taxonomy' => 'product_cat',
      'field' => 'slug',
      'terms' => ['suspensions', 'appliques', 'lampesaposer', 'lampadaires'],
      'operator' => 'IN',
    ],
  ],
]);

if ($featured_query->have_posts()) {
  while ($featured_query->have_posts()) {
    $featured_query->the_post();
    $product = wc_get_product(get_the_ID());

    if ($product) {
      $detail_photos = sapi_get_product_photos(get_the_ID(), 'detail', 1);
      $image_url = !empty($detail_photos) ? $detail_photos[0] : '';

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
        break; // Only need 1
      }
    }
  }
  wp_reset_postdata();
}

// Collections
// Collections dynamiques — URLs et images récupérées depuis WooCommerce/ACF
$collection_slugs = [
  ['slug' => 'suspensions', 'name' => 'Suspensions'],
  ['slug' => 'lampadaires', 'name' => 'Lampadaires'],
  ['slug' => 'appliques',   'name' => 'Appliques'],
  ['slug' => 'lampesaposer', 'name' => 'À poser'],
  ['slug' => 'accessoires', 'name' => 'Accessoires', 'prefer' => 'ampoule'],
];

$collections = [];
foreach ($collection_slugs as $col) {
  $cat_term = get_term_by('slug', $col['slug'], 'product_cat');
  if (!$cat_term) continue;

  $cat_url = get_term_link($cat_term);
  $cat_count = $cat_term->count;

  // Récupérer l'image d'un produit de la catégorie (bandeau ACF → image à la une)
  $col_image = '';
  $col_query = new WP_Query([
    'post_type' => 'product',
    'posts_per_page' => 12,
    'post_status' => 'publish',
    'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $col['slug']]],
    'orderby' => 'menu_order date',
    'order' => 'ASC',
  ]);
  if ($col_query->have_posts()) {
    $fallback_id = null;
    while ($col_query->have_posts()) {
      $col_query->the_post();
      $pid = get_the_ID();
      if (!$fallback_id) $fallback_id = $pid;

      // Priorité à un produit spécifique si défini (ex: "vincent" pour suspensions, "ampoule" pour accessoires)
      if (!empty($col['prefer']) && stripos(get_the_title(), $col['prefer']) !== false) {
        $fallback_id = $pid;
      }
      if ($col['slug'] === 'suspensions' && stripos(get_the_title(), 'vincent') !== false) {
        $fallback_id = $pid;
      }

      // Essayer le bandeau ACF
      if (function_exists('get_field')) {
        $bandeau = get_field('bandeau', $pid);
        if ($bandeau) {
          $col_image = sapi_get_acf_image_url($bandeau, 'large');
          if ($col_image) break;
        }
      }
    }

    // Fallback : image à la une du produit prioritaire
    if (empty($col_image) && $fallback_id) {
      $col_image = get_the_post_thumbnail_url($fallback_id, 'large');
    }
    wp_reset_postdata();
  }

  $collections[] = [
    'name' => $col['name'],
    'count' => $cat_count . ' ' . ($cat_count > 1 ? 'créations' : 'création'),
    'image' => $col_image,
    'url' => $cat_url,
  ];
}
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
      <div class="carousel-slide<?php echo $index === 0 ? ' active' : ''; ?>">
        <img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?> — Luminaire artisanal en bois" class="carousel-slide-img" <?php echo $index === 0 ? '' : 'loading="lazy"'; ?>>
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

    <!-- Star du moment -->
    <?php if ($star_product_data) : ?>
    <a href="<?php echo esc_url($star_product_data['url']); ?>" class="bento-card bento-hero">
      <img src="<?php echo esc_url($star_product_data['image']); ?>" alt="<?php echo esc_attr($star_product_data['name']); ?> — Star du moment" class="bento-bg-img" loading="lazy">
      <span class="bento-bestseller-badge">Star du moment</span>
      <div class="bento-content">
        <h2 class="bento-title product-name"><?php echo esc_html($star_product_data['name']); ?></h2>
        <?php if ($star_product_data['category']) : ?>
          <p class="bento-category"><?php echo esc_html($star_product_data['category']); ?></p>
        <?php endif; ?>
      </div>
    </a>
    <?php endif; ?>

    <!-- Storytelling Artisanat -->
    <div class="bento-card bento-storytelling">
      <div class="storytelling-inner">
        <span class="storytelling-label"><span class="storytelling-num">01</span> L'atelier</span>
        <h2 class="storytelling-title">Sculptées à la main</h2>
        <p class="storytelling-text">Du croquis à l'assemblage final, chaque pièce est façonnée dans mon atelier lyonnais. Le bois prend forme sous mes mains, la lumière fait le reste.</p>
        <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="storytelling-link">
          <span>Découvrir l'artisan</span>
          <svg width="16" height="16" viewBox="0 0 20 20" fill="none">
            <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
          </svg>
        </a>
      </div>
    </div>

    <!-- Carte Cadeau -->
    <?php if ($gift_card) : ?>
    <a href="<?php echo esc_url($gift_card['url']); ?>" class="bento-card bento-giftcard">
      <img src="<?php echo esc_url($gift_card['image']); ?>" alt="Carte cadeau Atelier Sâpi" class="bento-bg-img bento-bg-img--bottom-right" loading="lazy">
      <span class="giftcard-badge">Idée cadeau</span>
      <div class="giftcard-info">
        <h3>Offrez la lumière avec une carte cadeau</h3>
      </div>
    </a>
    <?php endif; ?>

    <!-- Pour quelle pièce ? -->
    <div class="bento-card bento-room-picker">
      <div class="room-picker-inner">
        <?php if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) : ?>
          <span class="robin-modal__badge" style="margin-bottom: 0.5rem;">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
            Conseil de Robin
          </span>
        <?php endif; ?>
        <h3 class="room-picker-title">Pour quelle pièce cherchez-vous un luminaire ?</h3>
        <p class="room-picker-sub">
          Quelques questions et Robin vous guide vers le luminaire idéal
        </p>
        <div class="room-picker-cards">
          <?php foreach ($room_choices as $room) : ?>
            <button type="button" class="room-card" data-piece="<?php echo esc_attr($room['slug']); ?>" onclick="<?php if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) : ?>if(window.sapiRobinOpen)window.sapiRobinOpen('homepage',{piece:this.dataset.piece});<?php else : ?>var bar=document.getElementById('mon-projet-bar');if(bar){bar.scrollIntoView({behavior:'smooth',block:'start'});var t=document.getElementById('mon-projet-toggle');if(t&&t.getAttribute('aria-expanded')==='false')t.click();}<?php endif; ?>">
              <span class="room-card-icon"><?php echo $room_icons[$room['icon']]; ?></span>
              <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Collections Carousel / Grid -->
<section class="collections-kinetic">
  <div class="section-header-kinetic">
    <span class="section-num">02</span>
    <h2 class="section-title-kinetic">Collections</h2>
  </div>

  <div class="collections-grid">
    <?php foreach ($collections as $collection) : ?>
      <a href="<?php echo esc_url($collection['url']); ?>" class="collection-card">
        <div class="collection-visual">
          <img src="<?php echo esc_url($collection['image']); ?>" alt="Collection <?php echo esc_attr($collection['name']); ?> — Luminaires en bois" class="collection-visual-img" loading="lazy">
        </div>
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
          <img src="<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/IMG_1928.png" alt="Dessin d'un luminaire en bois — Atelier Sâpi" class="step-image-img" loading="lazy">
        </div>
        <div class="process-step">
          <span class="step-num">02</span>
          <span class="step-text">Découpe laser</span>
          <img src="<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/IMG_7638.jpg" alt="Découpe laser du bois pour luminaire" class="step-image-img" loading="lazy">
        </div>
        <div class="process-step">
          <span class="step-num">03</span>
          <span class="step-text">Finitions</span>
          <img src="<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/03/P_SLM_XL_det5.jpg" alt="Finitions manuelles d'un luminaire en bois" class="step-image-img" loading="lazy">
        </div>
        <div class="process-step">
          <span class="step-num">04</span>
          <span class="step-text">Assemblage</span>
          <img src="<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/Robin-Sapi-A.jpg" alt="Robin assemble un luminaire dans son atelier à Lyon" class="step-image-img" loading="lazy">
        </div>
        <div class="process-step">
          <span class="step-num">05</span>
          <span class="step-text">Expédition</span>
          <img src="<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/07/Claudine-bandeau-1.jpg" alt="Luminaire Claudine prêt pour l'expédition" class="step-image-img" loading="lazy">
        </div>
      </div>
    </div>

    <!-- Product Card - Random Featured Product -->
    <?php if (!empty($featured_products)) : ?>
    <a href="<?php echo esc_url($featured_products[0]['url']); ?>" class="bento-card bento-product-featured">
      <img src="<?php echo esc_url($featured_products[0]['image']); ?>" alt="<?php echo esc_attr($featured_products[0]['name']); ?> — Luminaire artisanal" class="bento-bg-img" loading="lazy">
      <div class="bento-product-featured-info">
        <h3><?php echo esc_html($featured_products[0]['name']); ?></h3>
        <span class="bento-product-featured-price"><?php echo wp_kses_post($featured_products[0]['price']); ?></span>
      </div>
    </a>
    <?php endif; ?>

    <!-- Atelier Image -->
    <a href="https://maps.app.goo.gl/a3MiaeoG3ySfyUQT9" target="_blank" rel="noopener noreferrer" class="bento-card bento-atelier">
      <img src="<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/Robin-Sapi-A.jpg" alt="Atelier Sâpi — Atelier de fabrication de luminaires à Lyon" class="bento-bg-img" loading="lazy">
      <div class="atelier-label">
        <span>L'atelier · Lyon</span>
      </div>
    </a>

    <!-- Conseil -->
    <a href="<?php echo esc_url(home_url('/conseils-eclaires/')); ?>" class="bento-card bento-conseil">
      <span class="bento-conseil-badge">Conseil</span>
      <div class="bento-conseil-content">
        <h3>Éclairer une pièce, c'est un peu comme choisir la bonne sauce pour ses pâtes : tout est une question de préférence et de dosage !</h3>
        <span class="bento-conseil-cta">Découvrir les conseils de Robin →</span>
      </div>
    </a>

    <!-- Dernier Flash Actu -->
    <?php
    $last_actu = new WP_Query([
      'posts_per_page' => 1,
      'post_status'    => 'publish',
      'category_name'  => 'flash-actu',
      'orderby'        => 'date',
      'order'          => 'DESC'
    ]);
    if ($last_actu->have_posts()) : $last_actu->the_post();
    ?>
    <a href="<?php the_permalink(); ?>" class="bento-card bento-actu">
      <?php if (has_post_thumbnail()) : ?>
        <img src="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="bento-bg-img" loading="lazy">
      <?php endif; ?>
      <span class="bento-actu-badge">Flash actu</span>
      <div class="bento-actu-content">
        <h3><?php echo esc_html(get_the_title()); ?></h3>
        <span class="bento-actu-date"><?php echo esc_html(get_the_date('d/m/Y')); ?></span>
      </div>
    </a>
    <?php
    wp_reset_postdata();
    endif;
    ?>

    <!-- CTA Card -->
    <a href="<?php echo home_url('/mes-creations/'); ?>" class="bento-card bento-cta">
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
  <form class="newsletter-form" action="#" method="post" id="newsletter-form">
    <!-- Honeypot anti-spam -->
    <div style="display:none;" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
    <input type="email" placeholder="votre@email.fr" class="newsletter-input-kinetic" required />
    <button type="submit" class="newsletter-submit-kinetic">
      <span>S'inscrire</span>
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
        <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
      </svg>
    </button>
    <p class="newsletter-feedback" style="display:none; margin-top:0.8rem; font-size:0.95rem;"></p>
  </form>
  <script>
  (function(){
    var form = document.getElementById('newsletter-form');
    if (!form) return;
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var input = form.querySelector('input[type="email"]');
      var btn = form.querySelector('button');
      var feedback = form.querySelector('.newsletter-feedback');
      var email = input.value.trim();
      if (!email) return;

      btn.disabled = true;
      btn.querySelector('span').textContent = 'Envoi…';
      feedback.style.display = 'none';

      var data = new FormData(form);
      data.append('action', 'sapi_newsletter_subscribe');
      data.append('nonce', '<?php echo esc_js(wp_create_nonce("sapi_newsletter_nonce")); ?>');

      fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', {
        method: 'POST',
        body: data
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        feedback.style.display = 'block';
        if (res.success) {
          feedback.style.color = '#018501';
          feedback.textContent = res.data.message;
          input.value = '';
        } else {
          feedback.style.color = '#E35B24';
          feedback.textContent = res.data.message;
        }
        btn.disabled = false;
        btn.querySelector('span').textContent = "S'inscrire";
      })
      .catch(function() {
        feedback.style.display = 'block';
        feedback.style.color = '#E35B24';
        feedback.textContent = 'Erreur réseau. Réessayez.';
        btn.disabled = false;
        btn.querySelector('span').textContent = "S'inscrire";
      });
    });
  })();
  </script>
</section>

<script>
(function() {
  const carousel = document.querySelector('.homepage-carousel-fullscreen');

  // 1. Déplacer le bandeau juste sous le carousel (V1 ou V2)
  const monProjetBar = document.querySelector('.mon-projet-bar') || document.querySelector('.robin-bandeau');
  if (monProjetBar && carousel) {
    carousel.parentNode.insertBefore(monProjetBar, carousel.nextSibling);
    monProjetBar.classList.add('home-repositioned-bar');
  }

  // 2. Menu : transparent sur le carousel, opaque après
  const header = document.querySelector('.site-header');
  if (header && carousel) {
    function updateHeaderState() {
      // getBoundingClientRect() donne la position actuelle par rapport au viewport
      const carouselRect = carousel.getBoundingClientRect();
      const carouselBottom = carouselRect.bottom;
      const scrollThreshold = 50; // Marge de 50px pour garder la transparence

      // Si le bas du carousel est au-dessus du seuil, menu opaque
      // Sinon (carousel encore visible dans le viewport), menu transparent
      if (carouselBottom < scrollThreshold) {
        header.classList.add('is-scrolled');
      } else {
        header.classList.remove('is-scrolled');
      }
    }
    window.addEventListener('scroll', updateHeaderState, { passive: true });
    updateHeaderState(); // Run once on load
  }
})();
</script>

<?php
get_footer();

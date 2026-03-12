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
$categories_order = ['suspensions', 'appliques', 'lampesaposer', 'lampadaires'];

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
        $image_url = sapi_get_acf_image_url($ambiance_1);

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

// Query Olivia La Gardiéna — Bestseller
$olivia_product = null;
$olivia_query = new WP_Query([
  'post_type'      => 'product',
  'posts_per_page' => 1,
  'post_status'    => 'publish',
  'name'           => 'olivia-la-gardiena',
]);

if ($olivia_query->have_posts()) {
  $olivia_query->the_post();
  $product = wc_get_product(get_the_ID());

  if ($product) {
    if ($product->is_type('variable')) {
      $min_price = $product->get_variation_price('min');
      $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
    } else {
      $price_display = wc_price($product->get_price());
    }

    $categories = get_the_terms(get_the_ID(), 'product_cat');
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

    $image_url = '';
    if (function_exists('get_field')) {
      $detail_2 = get_field('detail_2', get_the_ID());
      if ($detail_2) {
        $image_url = sapi_get_acf_image_url($detail_2);
      }
    }
    if (!$image_url) {
      $image_url = get_the_post_thumbnail_url(get_the_ID(), 'woocommerce_single');
    }

    $olivia_product = [
      'name'     => get_the_title(),
      'category' => $category_name,
      'price'    => $price_display,
      'image'    => $image_url,
      'url'      => get_permalink(),
    ];
  }
  wp_reset_postdata();
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

// URL du Guide Luminaire (recherche dynamique de la page par template)
$guide_url = home_url('/guide-luminaire/'); // fallback
$guide_pages = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => 'page-guide-luminaire.php', 'number' => 1]);
if (!empty($guide_pages)) {
  $guide_url = get_permalink($guide_pages[0]->ID);
}

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
  'posts_per_page' => 1,
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
      // Get detail_1 ACF field
      $detail_1 = get_field('detail_1', get_the_ID());
      $image_url = '';

      if ($detail_1) {
        $image_url = sapi_get_acf_image_url($detail_1);
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

    <!-- Olivia Bestseller -->
    <?php if ($olivia_product) : ?>
    <a href="<?php echo esc_url($olivia_product['url']); ?>" class="bento-card bento-hero">
      <div class="bento-bg" style="background-image: url('<?php echo esc_url($olivia_product['image']); ?>');"></div>
      <span class="bento-bestseller-badge">Coup de cœur</span>
      <div class="bento-content">
        <h2 class="bento-title"><?php echo esc_html($olivia_product['name']); ?></h2>
        <?php if ($olivia_product['category']) : ?>
          <p class="bento-category"><?php echo esc_html($olivia_product['category']); ?></p>
        <?php endif; ?>
      </div>
    </a>
    <?php endif; ?>

    <!-- Storytelling Artisanat -->
    <div class="bento-card bento-storytelling">
      <div class="storytelling-inner">
        <span class="storytelling-label">L'atelier</span>
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
      <div class="bento-bg" style="background-image: url('<?php echo esc_url($gift_card['image']); ?>'); background-position: bottom right;"></div>
      <span class="giftcard-badge">Idée cadeau</span>
      <div class="giftcard-info">
        <h3>Offrez la lumière avec une carte cadeau</h3>
      </div>
    </a>
    <?php endif; ?>

    <!-- Pour quelle pièce ? -->
    <div class="bento-card bento-room-picker">
      <div class="room-picker-inner">
        <h3 class="room-picker-title">Pour quelle pièce cherchez-vous un luminaire ?</h3>
        <p class="room-picker-sub">
          <a href="<?php echo esc_url($guide_url); ?>">Quelques questions et Robin vous guide vers le luminaire idéal →</a>
        </p>
        <div class="room-picker-cards">
          <?php foreach ($room_choices as $room) : ?>
            <a href="<?php echo esc_url(add_query_arg('piece', $room['slug'], $guide_url)); ?>" class="room-card">
              <span class="room-card-icon"><?php echo $room_icons[$room['icon']]; ?></span>
              <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
            </a>
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
          <div class="step-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/IMG_1928.png');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">02</span>
          <span class="step-text">Découpe laser</span>
          <div class="step-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/IMG_7638.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">03</span>
          <span class="step-text">Finitions</span>
          <div class="step-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/03/P_SLM_XL_det5.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">04</span>
          <span class="step-text">Assemblage</span>
          <div class="step-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/Robin-Sapi-A.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">05</span>
          <span class="step-text">Expédition</span>
          <div class="step-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/07/Claudine-bandeau-1.jpg');"></div>
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
    <a href="https://maps.app.goo.gl/a3MiaeoG3ySfyUQT9" target="_blank" rel="noopener noreferrer" class="bento-card bento-atelier">
      <div class="bento-bg" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/Robin-Sapi-A.jpg');"></div>
      <div class="atelier-label">
        <span>L'atelier · Lyon</span>
      </div>
    </a>

    <!-- CTA Card -->
    <a href="<?php echo home_url('/nos-creations/'); ?>" class="bento-card bento-cta">
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

      var data = new FormData();
      data.append('action', 'sapi_newsletter_subscribe');
      data.append('email', email);
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

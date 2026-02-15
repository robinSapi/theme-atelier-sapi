<?php

if (!defined('ABSPATH')) {
  exit;
}

function sapi_maison_setup() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
  add_theme_support('custom-logo', [
    'height' => 120,
    'width' => 300,
    'flex-height' => true,
    'flex-width' => true,
  ]);
  add_theme_support('woocommerce');
  add_theme_support('wc-product-gallery-zoom');
  add_theme_support('wc-product-gallery-lightbox');
  add_theme_support('wc-product-gallery-slider');

  register_nav_menus([
    'primary' => __('Menu principal', 'theme-sapi-maison'),
    'footer' => __('Menu pied de page', 'theme-sapi-maison'),
  ]);
}
add_action('after_setup_theme', 'sapi_maison_setup');

function sapi_maison_enqueue_assets() {
  // Montserrat only from Google Fonts — Square Peg is self-hosted (Safari fix)
  $fonts = [
    'family' => 'Montserrat:wght@300;400;500;600;700;900',
    'display' => 'swap',
  ];
  wp_enqueue_style('sapi-maison-fonts', add_query_arg($fonts, 'https://fonts.googleapis.com/css2'));

  // CRITICAL: Use filemtime for automatic cache busting
  wp_enqueue_style('sapi-maison-style', get_stylesheet_uri(), ['sapi-maison-fonts'], filemtime(get_stylesheet_directory() . '/style.css'));

  // Menu burger JavaScript - chargé sur toutes les pages
  $menu_js_path = get_template_directory() . '/assets/menu.js';
  wp_enqueue_script('sapi-maison-menu', get_template_directory_uri() . '/assets/menu.js', [], file_exists($menu_js_path) ? filemtime($menu_js_path) : '1.0.0', true);

  // CINÉTIQUE interactions (bento animations, custom cursor, parallax, quantity buttons)
  // Chargé sur homepage ET pages produit
  if (is_front_page() || (class_exists('WooCommerce') && is_product())) {
    $cinetique_js_path = get_template_directory() . '/assets/cinetique.js';
    wp_enqueue_script('sapi-maison-cinetique', get_template_directory_uri() . '/assets/cinetique.js', [], file_exists($cinetique_js_path) ? filemtime($cinetique_js_path) : '1.0.0', true);
  }

  // Homepage fullscreen carousel
  if (is_front_page()) {
    $carousel_js_path = get_template_directory() . '/assets/homepage-carousel.js';
    wp_enqueue_script('sapi-maison-homepage-carousel', get_template_directory_uri() . '/assets/homepage-carousel.js', [], file_exists($carousel_js_path) ? filemtime($carousel_js_path) : '1.0.0', true);
  }

  // WooCommerce shop interactions (filters, animations)
  if (class_exists('WooCommerce') && (is_shop() || is_product_category() || is_product())) {
    $shop_js_path = get_template_directory() . '/assets/shop.js';
    if (file_exists($shop_js_path)) {
      wp_enqueue_script('sapi-maison-shop', get_template_directory_uri() . '/assets/shop.js', ['jquery'], filemtime($shop_js_path), true);

      // Pass WooCommerce data to JS
      wp_localize_script('sapi-maison-shop', 'sapiShop', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'cartUrl' => wc_get_cart_url(),
        'checkoutUrl' => wc_get_checkout_url(),
        'currency' => get_woocommerce_currency_symbol(),
      ]);
    }

    // Editorial Carousel removed — products now displayed in grid

    // Quick View modal for product previews
    $quick_view_js_path = get_template_directory() . '/assets/quick-view.js';
    if (file_exists($quick_view_js_path)) {
      wp_enqueue_script('sapi-maison-quick-view', get_template_directory_uri() . '/assets/quick-view.js', [], filemtime($quick_view_js_path), true);
    }
  }

  // Blog Timeline & Carousel - only on blog home page
  if (is_home()) {
    $blog_timeline_js_path = get_template_directory() . '/assets/blog-timeline.js';
    if (file_exists($blog_timeline_js_path)) {
      wp_enqueue_script('sapi-maison-blog-timeline', get_template_directory_uri() . '/assets/blog-timeline.js', [], filemtime($blog_timeline_js_path), true);
    }
  }
}
add_action('wp_enqueue_scripts', 'sapi_maison_enqueue_assets');

// Preload self-hosted Square Peg font (Safari fix — Google Fonts fails on some Safari versions)
function sapi_preload_square_peg() {
  $font_dir = get_template_directory_uri() . '/assets/fonts/';
  echo '<link rel="preload" href="' . esc_url($font_dir . 'SquarePeg-Regular.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
  echo '<link rel="preload" href="' . esc_url($font_dir . 'SquarePeg-Regular-latin-ext.woff2') . '" as="font" type="font/woff2" crossorigin>' . "\n";
}
add_action('wp_head', 'sapi_preload_square_peg', 1);

function sapi_maison_content_width() {
  $GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'sapi_maison_content_width', 0);

// ACF hero image is attached to the WooCommerce Shop page
// Field: shop_hero_image (Image, return format: Array)
// Location rule in ACF: Page = Boutique (Shop page)

// Focal Point Picker - Meta box + admin assets for Shop page
function sapi_focal_point_meta_box() {
  $shop_page_id = wc_get_page_id('shop');
  if (!$shop_page_id) return;

  add_meta_box(
    'sapi_hero_focal_point',
    'Point focal du hero',
    'sapi_focal_point_render',
    'page',
    'normal',
    'high'
  );
}
add_action('add_meta_boxes', 'sapi_focal_point_meta_box');

function sapi_focal_point_render($post) {
  $shop_page_id = wc_get_page_id('shop');
  if ($post->ID !== $shop_page_id) {
    echo '<p>Ce champ est uniquement pour la page Boutique.</p>';
    return;
  }

  wp_nonce_field('sapi_focal_point_save', 'sapi_focal_nonce');
  $focal = get_post_meta($post->ID, '_sapi_hero_focal_point', true);
  if (!$focal) $focal = '50% 50%';

  // Get hero image from ACF
  $hero_img_url = '';
  if (function_exists('get_field')) {
    $acf_hero = get_field('shop_hero_image', $post->ID);
    if ($acf_hero) {
      $hero_img_url = is_array($acf_hero) ? $acf_hero['url'] : $acf_hero;
    }
  }
  ?>
  <div id="sapi-focal-picker">
    <?php if ($hero_img_url) : ?>
      <div class="focal-picker-wrap">
        <label>Cliquez sur l'image pour d&eacute;finir le point focal :</label>
        <div class="focal-picker-area">
          <img class="focal-picker-image" src="<?php echo esc_url($hero_img_url); ?>" alt="Hero preview">
          <div class="focal-picker-crosshair"><div class="focal-picker-dot"></div></div>
          <div class="focal-picker-preview"></div>
        </div>
        <div class="focal-picker-info">
          <span>Point focal :</span>
          <span class="focal-picker-coords"><?php echo esc_html($focal); ?></span>
          <span class="focal-picker-status"></span>
          <span class="focal-picker-hint">Cliquez pour d&eacute;placer &bull; Sauvegarde automatique</span>
        </div>
      </div>
    <?php else : ?>
      <div class="focal-picker-no-image">
        Uploadez d'abord une image hero via le champ ACF "shop_hero_image" ci-dessus, puis enregistrez la page.
      </div>
    <?php endif; ?>
    <input type="hidden" id="sapi_hero_focal_point" name="sapi_hero_focal_point" value="<?php echo esc_attr($focal); ?>">
  </div>
  <?php
}

// Focal point is saved exclusively via AJAX (below).
// No save_post hook — Gutenberg re-submits meta box forms on "Update",
// which overwrites the AJAX-saved value with the stale initial value.

// Save focal point via AJAX (instant save, Gutenberg compatible)
function sapi_focal_point_ajax_save() {
  check_ajax_referer('sapi_focal_point_save', 'nonce');
  if (!current_user_can('edit_posts')) wp_send_json_error('Permission denied');

  $post_id = intval($_POST['post_id']);
  $focal = sanitize_text_field($_POST['focal_point']);
  if ($post_id && $focal) {
    update_post_meta($post_id, '_sapi_hero_focal_point', $focal);
    wp_send_json_success(['saved' => $focal]);
  }
  wp_send_json_error('Missing data');
}
add_action('wp_ajax_sapi_save_focal_point', 'sapi_focal_point_ajax_save');

function sapi_focal_point_admin_assets($hook) {
  if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

  global $post;
  $shop_page_id = wc_get_page_id('shop');
  if (!$post || $post->ID !== $shop_page_id) return;

  wp_enqueue_style('sapi-focal-point', get_template_directory_uri() . '/assets/admin-focal-point.css', [], filemtime(get_template_directory() . '/assets/admin-focal-point.css'));
  wp_enqueue_script('sapi-focal-point', get_template_directory_uri() . '/assets/admin-focal-point.js', [], filemtime(get_template_directory() . '/assets/admin-focal-point.js'), true);
  wp_localize_script('sapi-focal-point', 'sapiFocal', [
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce'   => wp_create_nonce('sapi_focal_point_save'),
    'postId'  => $post->ID,
  ]);
}
add_action('admin_enqueue_scripts', 'sapi_focal_point_admin_assets');

function sapi_maison_cart_count() {
  if (!function_exists('WC')) {
    return 0;
  }
  return WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}

function sapi_maison_structured_data() {
  if (is_product()) {
    global $product;

    if (!$product) {
      return;
    }

    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'Product',
      'name' => get_the_title(),
      'description' => wp_strip_all_tags(get_the_excerpt()),
      'sku' => $product->get_sku(),
      'offers' => [
        '@type' => 'Offer',
        'url' => get_permalink(),
        'priceCurrency' => 'EUR',
        'price' => $product->get_price(),
        'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'seller' => [
          '@type' => 'Organization',
          'name' => 'Atelier Sâpi'
        ]
      ]
    ];

    if (has_post_thumbnail()) {
      $schema['image'] = get_the_post_thumbnail_url(null, 'full');
    }

    if ($product->get_rating_count() > 0) {
      $schema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $product->get_average_rating(),
        'reviewCount' => $product->get_rating_count()
      ];
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
  }

  if (is_front_page()) {
    $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'Organization',
      'name' => 'Atelier Sâpi',
      'url' => home_url(),
      'logo' => get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '',
      'sameAs' => [
        'https://www.instagram.com/atelier.sapi/',
        'https://www.facebook.com/ateliersapi'
      ]
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
  }
}
add_action('wp_head', 'sapi_maison_structured_data');

function sapi_maison_open_graph() {
  if (is_product()) {
    global $product;

    if (!$product) {
      return;
    }

    echo '<meta property="og:type" content="product">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr(wp_strip_all_tags(get_the_excerpt())) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
    echo '<meta property="og:site_name" content="Atelier Sâpi">' . "\n";

    if (has_post_thumbnail()) {
      echo '<meta property="og:image" content="' . esc_url(get_the_post_thumbnail_url(null, 'large')) . '">' . "\n";
    }

    echo '<meta property="product:price:amount" content="' . esc_attr($product->get_price()) . '">' . "\n";
    echo '<meta property="product:price:currency" content="EUR">' . "\n";
  } elseif (is_front_page()) {
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr(get_bloginfo('description')) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url(home_url()) . '">' . "\n";
    echo '<meta property="og:site_name" content="Atelier Sâpi">' . "\n";
  }

  echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
add_action('wp_head', 'sapi_maison_open_graph');

function sapi_maison_canonical() {
  if (is_singular()) {
    echo '<link rel="canonical" href="' . esc_url(get_permalink()) . '">' . "\n";
  } elseif (is_archive()) {
    $url = get_term_link(get_queried_object());
    if (!is_wp_error($url)) {
      echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    }
  } elseif (is_front_page()) {
    echo '<link rel="canonical" href="' . esc_url(home_url('/')) . '">' . "\n";
  }
}
add_action('wp_head', 'sapi_maison_canonical');

// Meta descriptions (SEO)
function sapi_maison_meta_description() {
  $description = '';

  if (is_singular('product')) {
    global $product;
    if ($product) {
      $description = wp_strip_all_tags($product->get_short_description());
      if (empty($description)) {
        $description = wp_strip_all_tags($product->get_description());
      }
    }
  } elseif (is_product_category()) {
    $term = get_queried_object();
    if ($term) {
      $descs = [
        'suspension' => 'Découvrez nos suspensions artisanales en bois. Luminaires suspendus design, découpés au laser et assemblés à la main dans notre atelier lyonnais.',
        'lampadaire' => 'Nos lampadaires en bois sculptés transforment vos espaces. Éclairage d\'ambiance unique, fabriqués en France à Lyon.',
        'applique' => 'Appliques murales artisanales en bois. Créez des jeux de lumière poétiques sur vos murs. Chaque pièce est unique.',
        'lampe-a-poser' => 'Lampes à poser portables en bois. Déplacez-les où vous voulez pour créer une bulle de lumière intime.',
        'accessoire' => 'Accessoires pour luminaires artisanaux. Ampoules, câbles textile et pièces détachées pour vos créations Atelier Sâpi.',
      ];
      $description = isset($descs[$term->slug]) ? $descs[$term->slug] : wp_strip_all_tags(term_description($term->term_id, 'product_cat'));
    }
  } elseif (is_shop()) {
    $description = 'Luminaires artisanaux en bois, découpés au laser et assemblés à la main à Lyon. Suspensions, lampadaires, appliques et lampes design.';
  } elseif (is_front_page()) {
    $description = get_bloginfo('description') ?: 'Luminaires artisanaux en bois sculptés à la main à Lyon. Suspensions, lampadaires, appliques et lampes design. 100% français.';
  }

  if (!empty($description)) {
    $description = mb_substr(trim($description), 0, 160);
    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
  }
}
add_action('wp_head', 'sapi_maison_meta_description', 6);

function sapi_maison_breadcrumbs() {
  if (is_front_page()) {
    return;
  }

  $breadcrumbs = [];
  $breadcrumbs[] = [
    'name' => 'Accueil',
    'url' => home_url('/')
  ];

  if (is_product()) {
    $breadcrumbs[] = [
      'name' => 'Nos créations',
      'url' => home_url('/nos-creations/')
    ];
    $terms = get_the_terms(get_the_ID(), 'product_cat');
    if ($terms && !is_wp_error($terms)) {
      $main_term = $terms[0];
      $breadcrumbs[] = [
        'name' => $main_term->name,
        'url' => get_term_link($main_term)
      ];
    }
    $breadcrumbs[] = [
      'name' => get_the_title(),
      'url' => ''
    ];
  } elseif (is_product_category()) {
    $breadcrumbs[] = [
      'name' => 'Nos créations',
      'url' => home_url('/nos-creations/')
    ];
    $term = get_queried_object();
    $breadcrumbs[] = [
      'name' => $term->name,
      'url' => ''
    ];
  }

  // SVG flèche comme séparateur
  $bulb = '<span class="breadcrumb-separator"><svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.5 1L6.5 6L1.5 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';

  $schema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => []
  ];

  echo '<nav class="breadcrumbs" aria-label="Fil d\'Ariane"><div class="breadcrumbs-inner">';
  foreach ($breadcrumbs as $index => $crumb) {
    $position = $index + 1;

    $schema['itemListElement'][] = [
      '@type' => 'ListItem',
      'position' => $position,
      'name' => $crumb['name'],
      'item' => $crumb['url'] ?: null
    ];

    if ($crumb['url']) {
      echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['name']) . '</a>';
      if ($position < count($breadcrumbs)) {
        echo ' ' . $bulb . ' ';
      }
    } else {
      echo '<span class="breadcrumb-current">' . esc_html($crumb['name']) . '</span>';
    }
  }
  echo '</div></nav>';

  echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

// Redirect static category pages to WooCommerce native categories
add_action('template_redirect', function() {
  if (!is_page()) {
    return;
  }

  $page_slug = get_post_field('post_name', get_queried_object_id());

  // Map static page slugs to WooCommerce category slugs
  $category_redirects = [
    'nos-lampadaires' => 'lampadaire',
    'nos-suspensions' => 'suspension',
    'nos-appliques' => 'applique',
    'nos-lampes-a-poser' => 'lampe-a-poser',
    'les-accessoires' => 'accessoire',
  ];

  if (isset($category_redirects[$page_slug])) {
    $category_slug = $category_redirects[$page_slug];
    $category_link = get_term_link($category_slug, 'product_cat');

    if (!is_wp_error($category_link)) {
      wp_redirect($category_link, 301);
      exit;
    }
  }
});

// Remove default product meta display (SKU, Categories, Tags)
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);

// Ensure WooCommerce scripts are loaded - CRITICAL for cart functionality
add_action('wp_enqueue_scripts', function() {
  // Use class_exists instead of function_exists for reliability
  if (class_exists('WooCommerce')) {
    // Cart fragments for AJAX cart updates (CRITICAL for add-to-cart to work)
    wp_enqueue_script('wc-cart-fragments');
  }
}, 25);

// Variation scripts for variable products
add_action('wp_enqueue_scripts', function() {
  if (class_exists('WooCommerce') && is_product()) {
    wp_enqueue_script('wc-add-to-cart-variation');
  }
}, 30);

// Update cart count fragment after AJAX add-to-cart
add_filter('woocommerce_add_to_cart_fragments', function($fragments) {
  $count = WC()->cart->get_cart_contents_count();
  ob_start();
  ?>
  <span class="cart-count<?php echo $count === 0 ? ' cart-count--empty' : ''; ?>"><?php echo $count > 0 ? esc_html($count) : ''; ?></span>
  <?php
  $fragments['.cart-count'] = ob_get_clean();

  // Mini cart fragment for sliding panel
  ob_start();
  sapi_render_mini_cart_contents();
  $fragments['.mini-cart-body'] = ob_get_clean();

  // Mini cart total
  ob_start();
  ?>
  <div class="mini-cart-total">
    <span><?php esc_html_e('Total', 'theme-sapi-maison'); ?></span>
    <strong class="total-amount"><?php echo WC()->cart->get_cart_total(); ?></strong>
  </div>
  <?php
  $fragments['.mini-cart-total'] = ob_get_clean();

  return $fragments;
});

/**
 * Render mini cart contents for AJAX refresh
 */
function sapi_render_mini_cart_contents() {
  if (!WC()->cart || WC()->cart->is_empty()) {
    ?>
    <div class="mini-cart-body">
      <div class="mini-cart-empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="9" cy="21" r="1"/>
          <circle cx="20" cy="21" r="1"/>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
        </svg>
        <p><?php esc_html_e('Votre panier est vide', 'theme-sapi-maison'); ?></p>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn-continue">
          <?php esc_html_e('Voir les créations', 'theme-sapi-maison'); ?>
        </a>
      </div>
    </div>
    <?php
    return;
  }
  ?>
  <div class="mini-cart-body">
    <div class="mini-cart-items">
      <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
        $product = $cart_item['data'];
        $product_id = $cart_item['product_id'];
        $quantity = $cart_item['quantity'];
        $product_permalink = $product->is_visible() ? $product->get_permalink($cart_item) : '';
      ?>
        <div class="mini-cart-item" data-key="<?php echo esc_attr($cart_item_key); ?>">
          <div class="mini-cart-item-image">
            <?php echo $product->get_image('thumbnail'); ?>
          </div>
          <div class="mini-cart-item-details">
            <span class="mini-cart-item-name">
              <?php echo $product_permalink ? '<a href="' . esc_url($product_permalink) . '">' . esc_html($product->get_name()) . '</a>' : esc_html($product->get_name()); ?>
            </span>
            <span class="mini-cart-item-meta">
              <?php echo wc_get_formatted_cart_item_data($cart_item); ?>
              <?php echo sprintf(__('Qté: %d', 'theme-sapi-maison'), $quantity); ?>
            </span>
            <span class="mini-cart-item-price">
              <?php echo WC()->cart->get_product_price($product); ?>
            </span>
          </div>
          <button class="mini-cart-item-remove" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>" aria-label="<?php esc_attr_e('Retirer du panier', 'theme-sapi-maison'); ?>">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php
}

/**
 * Custom variation attribute display with visible labels
 * Add classes and data attributes for JS-powered swatches
 */
add_filter('woocommerce_dropdown_variation_attribute_options_html', function($html, $args) {
  $args = wp_parse_args(apply_filters('woocommerce_dropdown_variation_attribute_options_args', $args), [
    'options'          => false,
    'attribute'        => false,
    'product'          => false,
    'selected'         => false,
    'name'             => '',
    'id'               => '',
    'class'            => '',
    'show_option_none' => __('Choose an option', 'woocommerce'),
  ]);

  // Only modify for specific attributes
  $attribute = $args['attribute'];
  $product = $args['product'];
  $options = $args['options'];

  // Check if this is a material/finish attribute (common swatch candidates)
  $swatch_attributes = ['pa_essence', 'pa_materiau', 'pa_finition', 'pa_couleur', 'essence', 'materiau', 'finition', 'couleur'];
  $is_swatch_attribute = false;

  foreach ($swatch_attributes as $swatch_attr) {
    if (stripos($attribute, $swatch_attr) !== false) {
      $is_swatch_attribute = true;
      break;
    }
  }

  if (!$is_swatch_attribute || empty($options)) {
    return $html;
  }

  // Get attribute taxonomy for swatch images
  $attribute_taxonomy = wc_attribute_taxonomy_name(str_replace('pa_', '', $attribute));

  // Premium wood gradients from Design System
  $wood_gradients = [
    'okoume'   => 'linear-gradient(135deg, #D4B896 0%, #C4A882 50%, #B89B74 100%)',
    'peuplier' => 'linear-gradient(135deg, #F5EAD6 0%, #E8DCC4 50%, #DDD0B8 100%)',
    'noyer'    => 'linear-gradient(135deg, #8B6F5C 0%, #7A5F4D 50%, #6B5242 100%)',
    // Fallbacks pour autres bois
    'chene'    => 'linear-gradient(135deg, #C4A77D 0%, #B8996D 50%, #A08660 100%)',
    'hetre'    => 'linear-gradient(135deg, #E8D4B8 0%, #DEC8A7 50%, #D4BC96 100%)',
    'bouleau'  => 'linear-gradient(135deg, #F5E6D3 0%, #EDDEC7 50%, #E8D4BC 100%)',
    'frene'    => 'linear-gradient(135deg, #D9C8A5 0%, #CFBE9B 50%, #C9B58F 100%)',
    'erable'   => 'linear-gradient(135deg, #E8D8C8 0%, #DECCBA 50%, #D4C4B0 100%)',
    'merisier' => 'linear-gradient(135deg, #B87333 0%, #A46428 50%, #8B4513 100%)',
    'pin'      => 'linear-gradient(135deg, #DEB887 0%, #D1A876 50%, #C4A46C 100%)',
  ];

  // Build custom swatch HTML
  $swatch_html = '<div class="attribute-swatch" data-attribute="' . esc_attr($attribute) . '">';

  foreach ($options as $option) {
    $term = get_term_by('slug', $option, $attribute_taxonomy);
    $term_name = $term ? $term->name : $option;
    $is_selected = $args['selected'] === $option ? ' selected' : '';

    // Get wood slug for class mapping
    $wood_slug = sanitize_title($option);

    // Normalize wood names (handle accents)
    $wood_class = $wood_slug;
    $wood_class = str_replace(['okoumé', 'okoume'], 'okoume', $wood_class);

    // Get wood gradient from Design System
    $wood_gradient = isset($wood_gradients[$wood_slug]) ? $wood_gradients[$wood_slug] : '';

    // Try to get a term image (if using ACF or similar)
    $term_image = '';
    if ($term && function_exists('get_field')) {
      $term_image = get_field('swatch_image', $term);
    }

    // Check for default wood image in theme assets
    $default_image_path = get_template_directory() . '/assets/images/wood/' . $wood_slug . '.jpg';
    $default_image_url = '';
    if (file_exists($default_image_path)) {
      $default_image_url = get_template_directory_uri() . '/assets/images/wood/' . $wood_slug . '.jpg';
    }

    // Use premium .material-option structure for wood essences
    $swatch_html .= '<label class="swatch-item material-option' . $is_selected . '" data-value="' . esc_attr($option) . '" data-wood="' . esc_attr($wood_class) . '">';

    // Premium circular swatch with gradient
    $swatch_html .= '<div class="material-swatch ' . esc_attr($wood_class) . '"';
    if ($wood_gradient && !$term_image && !$default_image_url) {
      $swatch_html .= ' style="background: ' . esc_attr($wood_gradient) . ';"';
    }
    $swatch_html .= '>';

    if ($term_image) {
      // ACF image - render inside the circular swatch
      $swatch_html .= '<img src="' . esc_url($term_image['sizes']['thumbnail']) . '" alt="' . esc_attr($term_name) . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
    } elseif ($default_image_url) {
      // Default theme image - render inside the circular swatch
      $swatch_html .= '<img src="' . esc_url($default_image_url) . '" alt="' . esc_attr($term_name) . '" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">';
    }
    // If no image, the CSS gradient will show via inline style or CSS class

    $swatch_html .= '</div>';

    // Material info with name
    $swatch_html .= '<div class="material-info">';
    $swatch_html .= '<span class="material-name">' . esc_html($term_name) . '</span>';
    $swatch_html .= '</div>';

    $swatch_html .= '</label>';
  }

  $swatch_html .= '</div>';

  // Keep the original select but add a wrapper class
  $html = str_replace('<select', '<select class="swatch-select-hidden"', $html);

  return $swatch_html . $html;
}, 10, 2);

/**
 * WooCommerce gallery thumbnail size
 * Ensure thumbnails are at least 100px
 */
add_filter('woocommerce_gallery_thumbnail_size', function() {
  return [100, 100];
});

/**
 * Increase gallery image size
 */
add_filter('woocommerce_gallery_image_size', function() {
  return 'woocommerce_single';
});

/**
 * Custom SEO titles for product category pages
 */
add_filter('document_title_parts', function($title) {
  if (is_product_category()) {
    $term = get_queried_object();
    $category_titles = [
      'suspension' => 'Suspensions artisanales en bois',
      'lampadaire' => 'Lampadaires en bois design',
      'applique' => 'Appliques murales artisanales',
      'lampe-a-poser' => 'Lampes à poser en bois',
      'accessoire' => 'Accessoires pour luminaires',
    ];

    if (isset($category_titles[$term->slug])) {
      $title['title'] = $category_titles[$term->slug];
    }
  }
  return $title;
});

/**
 * Register custom image sizes for better product display
 */
add_action('after_setup_theme', function() {
  add_image_size('product-card', 400, 400, true);
  add_image_size('product-gallery-thumb', 120, 120, true);
}, 11);

/**
 * CINÉTIQUE - Custom Cart Page Header with Progress Bar
 */
add_action('woocommerce_before_cart', function() {
  ?>
  <div class="cart-page-cinetique">
    <!-- Progress Bar -->
    <div class="checkout-progress">
      <div class="progress-step active">
        <span class="step-number">1</span>
        <span class="step-label"><?php esc_html_e('Panier', 'theme-sapi-maison'); ?></span>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step">
        <span class="step-number">2</span>
        <span class="step-label"><?php esc_html_e('Commande', 'theme-sapi-maison'); ?></span>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step">
        <span class="step-number">3</span>
        <span class="step-label"><?php esc_html_e('Confirmation', 'theme-sapi-maison'); ?></span>
      </div>
    </div>

    <div class="cart-hero">
      <span class="section-number">01</span>
      <h1><?php esc_html_e('Votre Panier', 'theme-sapi-maison'); ?></h1>
      <p class="cart-subtitle"><?php esc_html_e('Plus que quelques clics avant de recevoir votre luminaire !', 'theme-sapi-maison'); ?></p>
    </div>
  <?php
});

add_action('woocommerce_after_cart', function() {
  ?>
    <div class="cart-reassurance">
      <div class="reassurance-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/>
          <polyline points="12 6 12 12 16 14"/>
        </svg>
        <div class="reassurance-text">
          <strong><?php esc_html_e('Fabrication < 5 jours', 'theme-sapi-maison'); ?></strong>
          <span><?php esc_html_e('Fait main dans notre atelier lyonnais', 'theme-sapi-maison'); ?></span>
        </div>
      </div>
      <div class="reassurance-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="1" y="3" width="15" height="13"/>
          <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
          <circle cx="5.5" cy="18.5" r="2.5"/>
          <circle cx="18.5" cy="18.5" r="2.5"/>
        </svg>
        <div class="reassurance-text">
          <strong><?php esc_html_e('Livraison 48-72h', 'theme-sapi-maison'); ?></strong>
          <span><?php esc_html_e('Soigneusement emballé', 'theme-sapi-maison'); ?></span>
        </div>
      </div>
      <div class="reassurance-item">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="1 4 1 10 7 10"/>
          <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
        </svg>
        <div class="reassurance-text">
          <strong><?php esc_html_e('Retours 30 jours', 'theme-sapi-maison'); ?></strong>
          <span><?php esc_html_e('Satisfait ou remboursé', 'theme-sapi-maison'); ?></span>
        </div>
      </div>
    </div>
  </div>
  <?php
});

/**
 * CINÉTIQUE - Custom Checkout Page Header with Progress Bar
 */
add_action('woocommerce_before_checkout_form', function() {
  ?>
  <div class="checkout-page-cinetique">
    <!-- Progress Bar -->
    <div class="checkout-progress">
      <div class="progress-step completed">
        <span class="step-number">1</span>
        <span class="step-label"><?php esc_html_e('Panier', 'theme-sapi-maison'); ?></span>
      </div>
      <div class="progress-line completed"></div>
      <div class="progress-step active">
        <span class="step-number">2</span>
        <span class="step-label"><?php esc_html_e('Commande', 'theme-sapi-maison'); ?></span>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step">
        <span class="step-number">3</span>
        <span class="step-label"><?php esc_html_e('Confirmation', 'theme-sapi-maison'); ?></span>
      </div>
    </div>

    <div class="checkout-hero">
      <span class="section-number">01</span>
      <h1><?php esc_html_e('Finaliser ma commande', 'theme-sapi-maison'); ?></h1>
      <p class="checkout-subtitle"><?php esc_html_e('Votre luminaire sera bientôt chez vous !', 'theme-sapi-maison'); ?></p>
    </div>
  <?php
}, 5);

add_action('woocommerce_after_checkout_form', function() {
  ?>
  </div><!-- /.checkout-page-cinetique -->
  <?php
});

/**
 * AJAX Add to Cart - Safari Compatible
 * Handles AJAX add to cart from sticky bar (simple products)
 */
add_action('wp_ajax_sapi_add_to_cart', 'sapi_ajax_add_to_cart');
add_action('wp_ajax_nopriv_sapi_add_to_cart', 'sapi_ajax_add_to_cart');

function sapi_ajax_add_to_cart() {
  // Verify nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sapi-add-to-cart')) {
    wp_send_json_error(['message' => 'Invalid nonce']);
    return;
  }

  $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
  $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;

  if ($product_id <= 0) {
    wp_send_json_error(['message' => 'Invalid product']);
    return;
  }

  // Add to cart
  $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);

  if ($cart_item_key) {
    // Get refreshed fragments
    ob_start();
    woocommerce_mini_cart();
    $mini_cart = ob_get_clean();

    $data = [
      'fragments' => apply_filters('woocommerce_add_to_cart_fragments', [
        'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
      ]),
      'cart_hash' => WC()->cart->get_cart_hash(),
      'cart_quantity' => WC()->cart->get_cart_contents_count(),
    ];

    wp_send_json_success($data);
  } else {
    wp_send_json_error(['message' => 'Could not add to cart']);
  }
}

/**
 * Calculate estimated delivery date (Phase 4 - Proposal B)
 * Fabrication (5 jours ouvrés) + Livraison (3 jours ouvrés)
 *
 * @return string Formatted date "12 février"
 */
function sapi_get_estimated_delivery_date() {
  $business_days_to_add = 8; // 5 jours fabrication + 3 jours livraison
  $current_date = new DateTime();
  $days_added = 0;

  while ($days_added < $business_days_to_add) {
    $current_date->modify('+1 day');
    $day_of_week = $current_date->format('N'); // 1 (lundi) à 7 (dimanche)

    // Skip weekends (6 = samedi, 7 = dimanche)
    if ($day_of_week < 6) {
      $days_added++;
    }
  }

  // Format: "12 février"
  $months_fr = [
    1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
    5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
    9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
  ];

  $day = $current_date->format('j');
  $month = $months_fr[(int)$current_date->format('n')];

  return $day . ' ' . $month;
}

/**
 * AJAX Buy Now - Express Checkout (Phase 4 - Proposal B)
 * Adds product to cart and redirects directly to checkout
 */
add_action('wp_ajax_sapi_buy_now', 'sapi_ajax_buy_now');
add_action('wp_ajax_nopriv_sapi_buy_now', 'sapi_ajax_buy_now');

function sapi_ajax_buy_now() {
  // Verify nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sapi-buy-now')) {
    wp_send_json_error(['message' => 'Session expirée, veuillez recharger la page']);
    return;
  }

  $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
  $quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
  $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

  if ($product_id <= 0) {
    wp_send_json_error(['message' => 'Produit invalide']);
    return;
  }

  // Get product to check if it's variable
  $product = wc_get_product($product_id);
  if (!$product) {
    wp_send_json_error(['message' => 'Produit introuvable']);
    return;
  }

  // For variable products, ensure variation is selected
  if ($product->is_type('variable') && !$variation_id) {
    wp_send_json_error(['message' => 'Veuillez sélectionner toutes les options']);
    return;
  }

  // Clear cart for true express checkout experience
  WC()->cart->empty_cart();

  // Add to cart
  $cart_item_key = false;
  if ($variation_id > 0) {
    // Variable product - get variation attributes
    $variation_data = [];
    foreach ($_POST as $key => $value) {
      if (strpos($key, 'attribute_') === 0) {
        $variation_data[$key] = sanitize_text_field($value);
      }
    }
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation_data);
  } else {
    // Simple product
    $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);
  }

  if ($cart_item_key) {
    wp_send_json_success([
      'message' => 'Redirection vers la commande...',
      'checkout_url' => wc_get_checkout_url(),
    ]);
  } else {
    wp_send_json_error(['message' => 'Impossible d\'ajouter au panier']);
  }
}

/**
 * Custom product search endpoint with metadata support
 * Searches in: title, content, dimensions, wood type, price
 */
function sapi_register_product_search_endpoint() {
  register_rest_route('sapi/v1', '/products/search', [
    'methods' => 'GET',
    'callback' => 'sapi_product_search',
    'permission_callback' => '__return_true',
    'args' => [
      'query' => [
        'required' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
      ],
    ],
  ]);
}
add_action('rest_api_init', 'sapi_register_product_search_endpoint');

function sapi_product_search($request) {
  $query_string = $request->get_param('query');

  if (empty($query_string) || strlen($query_string) < 2) {
    return new WP_REST_Response([], 200);
  }

  $query_lower = strtolower($query_string);
  $results = [];
  $found_ids = [];

  // Get ALL published products to filter client-side
  $all_products_args = [
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1, // Get all products
    'tax_query' => [
      [
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => ['accessoire'], // Exclude accessories category
        'operator' => 'NOT IN',
      ],
    ],
  ];

  $all_products = new WP_Query($all_products_args);
  $is_numeric_search = is_numeric($query_string);
  $dimension_search = $is_numeric_search ? floatval($query_string) : 0;

  if ($all_products->have_posts()) {
    while ($all_products->have_posts()) {
      $all_products->the_post();
      $product_id = get_the_ID();
      $product = wc_get_product($product_id);

      if (!$product) continue;

      $title = strtolower($product->get_name());
      $score = 0;

      // 1. Search in product title (highest priority)
      if (stripos($title, $query_lower) !== false) {
        $score += 100;
      }

      // 2. Search in wood type (materiau)
      $wood_essence = get_field('essence_de_bois', $product_id);
      if (!$wood_essence) {
        // Try pa_materiau attribute (used in variations)
        $wood_attr = $product->get_attribute('pa_materiau');
        if ($wood_attr) {
          $wood_essence = strtolower($wood_attr);
        }
      }
      if ($wood_essence && stripos(strtolower($wood_essence), $query_lower) !== false) {
        $score += 50;
      }

      // 3. Search in dimensions if numeric query
      if ($is_numeric_search && $dimension_search > 0) {
        // Try ACF field first
        $acf_dimension = get_field('hauteur_cm', $product_id);
        if ($acf_dimension) {
          $dim = floatval($acf_dimension);
          // ±20cm tolerance
          if ($dim >= ($dimension_search - 20) && $dim <= ($dimension_search + 20)) {
            $score += 75;
          }
        } else {
          // Try pa_taille attribute (e.g., "50-cm", "100-cm", "130-cm")
          $taille_attr = $product->get_attribute('pa_taille');
          if ($taille_attr) {
            // Extract numeric value from "XXX-cm" format
            preg_match('/(\d+)/', $taille_attr, $matches);
            if (!empty($matches[1])) {
              $dim = floatval($matches[1]);
              // ±20cm tolerance
              if ($dim >= ($dimension_search - 20) && $dim <= ($dimension_search + 20)) {
                $score += 75;
              }
            }
          } else {
            // Fallback to WooCommerce dimensions
            $height = floatval($product->get_height());
            $width = floatval($product->get_width());
            $length = floatval($product->get_length());
            $max_dim = max($height, $width, $length);

            if ($max_dim > 0 && $max_dim >= ($dimension_search - 20) && $max_dim <= ($dimension_search + 20)) {
              $score += 75;
            }
          }
        }
      }

      // 4. Search in categories
      $categories = get_the_terms($product_id, 'product_cat');
      if ($categories && !is_wp_error($categories)) {
        foreach ($categories as $cat) {
          if (stripos(strtolower($cat->name), $query_lower) !== false) {
            $score += 25;
          }
        }
      }

      // Only include products with a score > 0
      if ($score > 0) {
        $results[] = [
          'score' => $score,
          'data' => sapi_format_product_for_search($product),
        ];
      }
    }
    wp_reset_postdata();
  }

  // Sort by score (highest first)
  usort($results, function($a, $b) {
    return $b['score'] - $a['score'];
  });

  // Extract just the product data and limit to 8 results
  $final_results = array_slice(array_map(function($item) {
    return $item['data'];
  }, $results), 0, 8);

  return new WP_REST_Response($final_results, 200);
}

function sapi_format_product_for_search($product) {
  $product_id = $product->get_id();

  // Get categories
  $categories = get_the_terms($product_id, 'product_cat');
  $category_names = [];
  if ($categories && !is_wp_error($categories)) {
    foreach ($categories as $cat) {
      if ($cat->slug !== 'uncategorized') {
        $category_names[] = $cat->name;
      }
    }
  }

  // Get image
  $image_id = $product->get_image_id();
  $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';

  // Get metadata
  $wood_essence = get_field('essence_de_bois', $product_id);
  $dimensions = get_field('hauteur_cm', $product_id);

  return [
    'id' => $product_id,
    'title' => $product->get_name(),
    'link' => get_permalink($product_id),
    'image' => $image_url,
    'price' => $product->get_price_html(),
    'categories' => $category_names,
    'wood' => $wood_essence,
    'dimensions' => $dimensions,
  ];
}


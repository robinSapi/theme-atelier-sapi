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
  $fonts = [
    'family' => 'Montserrat:wght@400;500;600;700|Square+Peg:wght@400;500',
    'display' => 'swap',
  ];
  wp_enqueue_style('sapi-maison-fonts', add_query_arg($fonts, 'https://fonts.googleapis.com/css'));
  wp_enqueue_style('sapi-maison-style', get_stylesheet_uri(), ['sapi-maison-fonts'], '0.1.1');

  // Menu burger JavaScript - chargé sur toutes les pages
  wp_enqueue_script('sapi-maison-menu', get_template_directory_uri() . '/assets/menu.js', [], '0.1.0', true);

  if (is_front_page()) {
    wp_enqueue_script('sapi-maison-home', get_template_directory_uri() . '/assets/home.js', [], '0.1.0', true);
  }
}
add_action('wp_enqueue_scripts', 'sapi_maison_enqueue_assets');

function sapi_maison_content_width() {
  $GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'sapi_maison_content_width', 0);

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
    $term = get_queried_object();
    $breadcrumbs[] = [
      'name' => $term->name,
      'url' => ''
    ];
  }

  $schema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => []
  ];

  echo '<nav class="breadcrumbs" aria-label="Fil d\'Ariane">';
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
        echo ' <span class="breadcrumb-separator">/</span> ';
      }
    } else {
      echo '<span class="breadcrumb-current">' . esc_html($crumb['name']) . '</span>';
    }
  }
  echo '</nav>';

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

// Ensure WooCommerce scripts are loaded
add_action('wp_enqueue_scripts', function() {
  if (function_exists('is_woocommerce')) {
    // Cart fragments for AJAX cart updates (CRITICAL for add-to-cart)
    wp_enqueue_script('wc-cart-fragments');

    // Variation scripts for variable products
    if (is_product()) {
      wp_enqueue_script('wc-add-to-cart-variation');
    }
  }
}, 20);

// Update cart count fragment after AJAX add-to-cart
add_filter('woocommerce_add_to_cart_fragments', function($fragments) {
  ob_start();
  ?>
  <span class="cart-count"><?php echo esc_html(WC()->cart->get_cart_contents_count()); ?></span>
  <?php
  $fragments['.cart-count'] = ob_get_clean();
  return $fragments;
});

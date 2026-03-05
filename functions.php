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
    'primary'       => __('Menu principal', 'theme-sapi-maison'),
    'footer'        => __('Menu pied de page', 'theme-sapi-maison'),
    'footer_social' => __('Footer – Réseaux sociaux', 'theme-sapi-maison'),
    'footer_legal'  => __('Footer – Mentions légales', 'theme-sapi-maison'),
  ]);
}
add_action('after_setup_theme', 'sapi_maison_setup');

/**
 * Walker pour les menus footer : rend des <a> simples (pas de <ul><li>).
 */
class Sapi_Footer_Walker extends Walker_Nav_Menu {
  public function start_lvl(&$output, $depth = 0, $args = null) {}
  public function end_lvl(&$output, $depth = 0, $args = null) {}

  public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
    $atts = [];
    $atts['href']  = !empty($item->url) ? $item->url : '';
    $atts['title'] = !empty($item->attr_title) ? $item->attr_title : '';

    if ('_blank' === $item->target) {
      $atts['target'] = '_blank';
      $atts['rel']    = 'noopener';
    }

    $attributes = '';
    foreach ($atts as $attr => $value) {
      if (!empty($value)) {
        $attributes .= ' ' . $attr . '="' . esc_attr($value) . '"';
      }
    }

    $output .= '<a' . $attributes . '>' . esc_html($item->title) . '</a>';
  }

  public function end_el(&$output, $item, $depth = 0, $args = null) {}
}

/**
 * Ajouter "Accueil" en premier élément du menu mobile uniquement.
 */
add_filter('wp_nav_menu_items', function ($items, $args) {
  if (isset($args->menu_id) && 'mobile-nav-menu' === $args->menu_id) {
    $home = '<li class="menu-item menu-item-home"><a href="' . esc_url(home_url('/')) . '">' . esc_html__('Accueil', 'theme-sapi-maison') . '</a></li>';
    $items = $home . $items;
  }
  return $items;
}, 10, 2);

/**
 * Ajouter une classe CSS "menu-separator-before" sur l'item "Accessoires"
 * pour afficher un séparateur visuel dans le sous-menu "Nos créations".
 */
add_filter('wp_nav_menu_objects', function ($items) {
  foreach ($items as $item) {
    if (strtolower(trim($item->title)) === 'accessoires' && $item->menu_item_parent != 0) {
      $item->classes[] = 'menu-separator-before';
    }
  }
  return $items;
});

/**
 * Fallbacks : affichent les liens hardcodés si les menus WordPress ne sont pas encore créés.
 */
function sapi_fallback_primary_menu() {
  ?>
  <nav class="primary-nav" aria-label="<?php esc_attr_e('Menu principal', 'theme-sapi-maison'); ?>">
    <ul class="nav-menu">
      <li class="menu-item menu-item-has-children">
        <a href="<?php echo esc_url(home_url('/nos-creations/')); ?>">Nos créations</a>
        <ul class="sub-menu">
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/suspensions/')); ?>">Suspensions</a></li>
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/lampadaires/')); ?>">Lampadaires</a></li>
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/appliques/')); ?>">Appliques</a></li>
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/lampeaposer/')); ?>">À poser</a></li>
        </ul>
      </li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>">Sur mesure</a></li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>">L'artisan</a></li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/conseils-eclaires/')); ?>">Conseils</a></li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></li>
    </ul>
  </nav>
  <?php
}

function sapi_fallback_mobile_menu() {
  ?>
  <nav class="mobile-menu-nav" aria-label="<?php esc_attr_e('Menu mobile', 'theme-sapi-maison'); ?>">
    <ul class="mobile-nav-menu" id="mobile-nav-menu">
      <li class="menu-item menu-item-home"><a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a></li>
      <li class="menu-item menu-item-has-children">
        <a href="<?php echo esc_url(home_url('/nos-creations/')); ?>">Nos créations</a>
        <ul class="sub-menu">
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/suspensions/')); ?>">Suspensions</a></li>
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/lampadaires/')); ?>">Lampadaires</a></li>
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/appliques/')); ?>">Appliques</a></li>
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/lampeaposer/')); ?>">À poser</a></li>
        </ul>
      </li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>">Sur mesure</a></li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>">L'artisan</a></li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/conseils-eclaires/')); ?>">Conseils</a></li>
      <li class="menu-item"><a href="<?php echo esc_url(home_url('/contact/')); ?>">Contact</a></li>
    </ul>
  </nav>
  <?php
}

function sapi_fallback_footer_nav() {
  echo '<a href="' . esc_url(home_url('/nos-creations/')) . '">Nos créations</a>';
  echo '<a href="' . esc_url(home_url('/lumiere-dartisan/')) . '">L\'artisan</a>';
  echo '<a href="' . esc_url(home_url('/conseils-eclaires/')) . '">Conseils</a>';
  echo '<a href="' . esc_url(home_url('/contact/')) . '">Contact</a>';
}

function sapi_fallback_social_menu() {
  echo '<a href="https://www.instagram.com/atelier_sapi/" target="_blank" rel="noopener">Instagram</a>';
  echo '<a href="https://www.facebook.com/ateliersapi" target="_blank" rel="noopener">Facebook</a>';
  echo '<a href="https://www.pinterest.fr/ateliersapi/" target="_blank" rel="noopener">Pinterest</a>';
  echo '<a href="' . esc_url(home_url('/actus/')) . '">Actus</a>';
}

function sapi_fallback_legal_menu() {
  echo '<a href="' . esc_url(home_url('/mentions-legales/')) . '">Mentions légales</a>';
  echo '<a href="' . esc_url(home_url('/cgv/')) . '">CGV</a>';
  echo '<a href="' . esc_url(home_url('/politique-de-confidentialite/')) . '">Confidentialité</a>';
}

// Précharger Square Peg avant le premier paint (élimine le FOUT / chargement en deux temps)
add_action('wp_head', function() {
  $uri = get_template_directory_uri();
  echo '<link rel="preload" href="' . esc_url($uri) . '/assets/fonts/SquarePeg-Regular.woff2" as="font" type="font/woff2" crossorigin>' . "\n";
  echo '<link rel="preload" href="' . esc_url($uri) . '/assets/fonts/SquarePeg-Regular-latin-ext.woff2" as="font" type="font/woff2" crossorigin>' . "\n";
}, 1);

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
  wp_localize_script('sapi-maison-menu', 'sapiMenu', [
    'miniCartNonce' => wp_create_nonce('sapi-update-mini-cart-qty'),
  ]);

  // Product name formatter - chargé sur toutes les pages (prénom en Montserrat, reste en Square Peg)
  $formatter_js_path = get_template_directory() . '/assets/product-name-formatter.js';
  wp_enqueue_script('sapi-maison-product-formatter', get_template_directory_uri() . '/assets/product-name-formatter.js', [], file_exists($formatter_js_path) ? filemtime($formatter_js_path) : '1.0.0', true);

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

  // Cart page JS — enqueued when is_cart() returns true
  if (class_exists('WooCommerce') && is_cart()) {
    $cart_js_path = get_template_directory() . '/assets/cart-page.js';
    if (file_exists($cart_js_path)) {
      wp_enqueue_script('sapi-maison-cart-page', get_template_directory_uri() . '/assets/cart-page.js', [], filemtime($cart_js_path), true);
    }
  }

  // Guide Luminaire questionnaire
  if (is_page_template('page-guide-luminaire.php')) {
    $guide_js_path = get_template_directory() . '/assets/guide-luminaire.js';
    if (file_exists($guide_js_path)) {
      wp_enqueue_script('sapi-guide-luminaire', get_template_directory_uri() . '/assets/guide-luminaire.js', [], filemtime($guide_js_path), true);
    }
    // Quick View for product results
    $quick_view_js_path = get_template_directory() . '/assets/quick-view.js';
    if (file_exists($quick_view_js_path)) {
      wp_enqueue_script('sapi-maison-quick-view', get_template_directory_uri() . '/assets/quick-view.js', [], filemtime($quick_view_js_path), true);
    }
  }
}
add_action('wp_enqueue_scripts', 'sapi_maison_enqueue_assets');

/**
 * Tracking des produits récemment consultés (cookie woocommerce_recently_viewed)
 * WooCommerce ne remplit ce cookie que si le widget "Recently Viewed" est actif.
 * On le gère manuellement ici pour toujours avoir les données.
 */
add_action('template_redirect', function () {
  if (!is_singular('product')) return;

  $product_id = get_queried_object_id();
  if (!$product_id) return;

  $viewed = !empty($_COOKIE['woocommerce_recently_viewed'])
    ? array_filter(array_map('absint', explode('|', wp_unslash($_COOKIE['woocommerce_recently_viewed']))))
    : [];

  // Retirer l'ID s'il existe déjà pour le mettre en premier
  $viewed = array_diff($viewed, [$product_id]);
  array_unshift($viewed, $product_id);

  // Limiter à 15 produits
  $viewed = array_slice($viewed, 0, 15);

  wc_setcookie('woocommerce_recently_viewed', implode('|', $viewed));
});

/**
 * render_block — Enveloppe le bloc panier WooCommerce dans .sapi-cart-outer
 * Ce wrapper est injecté côté PHP AVANT React et ne sera jamais touché par React.
 * Il permet de scoper notre CSS avec une spécificité garantie.
 */
// Remplace "Supprimer l'élément" par "Supprimer" dans le panier (rendu React)
add_action('wp_footer', function () {
  if (!function_exists('is_cart') || !is_cart()) return;
  ?>
  <script>
  (function () {
    function replaceCartTexts() {
      // "Supprimer l'élément" → "Supprimer"
      document.querySelectorAll('.wc-block-cart-item__remove-link').forEach(function (el) {
        if (el.textContent.trim() === "Supprimer l\u2019\u00e9l\u00e9ment") {
          el.textContent = 'Supprimer';
        }
      });
      // Titre cross-sell → texte personnalisé
      document.querySelectorAll('.wp-block-heading').forEach(function (el) {
        if (el.textContent.trim().indexOf('peut vous int') !== -1) {
          el.textContent = 'Avez-vous d\u00e9j\u00e0 la bonne ampoule\u00a0?';
        }
      });
    }
    replaceCartTexts();
    new MutationObserver(replaceCartTexts).observe(document.body, { childList: true, subtree: true });
  })();
  </script>
  <?php
});

// Panier vide : 3 derniers produits vus (fallback produits populaires) + remplacement JS
add_action('wp_footer', function () {
  if (!function_exists('is_cart') || !is_cart()) return;
  if (!WC()->cart || !WC()->cart->is_empty()) return;

  $products_data = [];
  $has_viewed = false;

  // 1. Essayer les produits récemment vus (cookie WooCommerce)
  if (!empty($_COOKIE['woocommerce_recently_viewed'])) {
    $viewed_ids = array_filter(array_map('absint', explode('|', wp_unslash($_COOKIE['woocommerce_recently_viewed']))));
    $viewed_ids = array_slice($viewed_ids, 0, 3);
    foreach ($viewed_ids as $vid) {
      $vp = wc_get_product($vid);
      if (!$vp || !$vp->is_visible()) continue;
      $name_parts = explode(' ', $vp->get_name(), 2);
      $vp_cats = get_the_terms($vid, 'product_cat');
      $vp_cat_name = '';
      if ($vp_cats && !is_wp_error($vp_cats)) {
        foreach ($vp_cats as $vc) {
          if ($vc->slug !== 'non-classe' && $vc->slug !== 'uncategorized') { $vp_cat_name = $vc->name; break; }
        }
      }
      $products_data[] = [
        'url'   => $vp->get_permalink(),
        'name'  => $name_parts[0],
        'desc'  => isset($name_parts[1]) ? $name_parts[1] : '',
        'cat'   => $vp_cat_name,
        'img'   => get_the_post_thumbnail_url($vid, 'medium') ?: '',
      ];
    }
    if (count($products_data) > 0) {
      $has_viewed = true;
    }
  }

  // 2. Fallback : 3 produits les plus vendus
  if (count($products_data) < 3) {
    // Récupérer les IDs déjà présents pour ne pas les dupliquer
    $existing_urls = array_column($products_data, 'url');
    $popular_query = new WP_Query([
      'post_type'      => 'product',
      'posts_per_page' => 3 - count($products_data),
      'post_status'    => 'publish',
      'meta_key'       => 'total_sales',
      'orderby'        => 'meta_value_num',
      'order'          => 'DESC',
      'tax_query'      => [[
        'taxonomy' => 'product_visibility',
        'field'    => 'name',
        'terms'    => 'exclude-from-catalog',
        'operator' => 'NOT IN',
      ]],
    ]);
    if ($popular_query->have_posts()) {
      while ($popular_query->have_posts()) {
        $popular_query->the_post();
        $p = wc_get_product(get_the_ID());
        if (!$p) continue;
        if (in_array($p->get_permalink(), $existing_urls)) continue;
        $name_parts = explode(' ', $p->get_name(), 2);
        $p_cats = get_the_terms(get_the_ID(), 'product_cat');
        $p_cat_name = '';
        if ($p_cats && !is_wp_error($p_cats)) {
          foreach ($p_cats as $pc) {
            if ($pc->slug !== 'non-classe' && $pc->slug !== 'uncategorized') { $p_cat_name = $pc->name; break; }
          }
        }
        $products_data[] = [
          'url'   => get_permalink(),
          'name'  => $name_parts[0],
          'desc'  => isset($name_parts[1]) ? $name_parts[1] : '',
          'cat'   => $p_cat_name,
          'img'   => get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: '',
        ];
        if (count($products_data) >= 3) break;
      }
      wp_reset_postdata();
    }
  }

  $shop_url = esc_url(wc_get_page_permalink('shop'));
  ?>
  <script>
  (function() {
    var popularProducts = <?php echo wp_json_encode(array_values($products_data)); ?>;
    var shopUrl = <?php echo wp_json_encode($shop_url); ?>;
    var sectionTitle = <?php echo wp_json_encode($has_viewed ? 'Consultés récemment' : 'La sélection de l\'artisan'); ?>;
    var done = false;

    function replaceEmptyCart() {
      if (done) return;

      // Chercher le texte "vide" partout dans la page
      var allEls = document.querySelectorAll('h1, h2, h3, p');
      var emptyMsg = null;
      for (var i = 0; i < allEls.length; i++) {
        if (allEls[i].textContent.indexOf('vide') !== -1) {
          emptyMsg = allEls[i];
          break;
        }
      }
      if (!emptyMsg) return;

      done = true;

      // Remonter au conteneur le plus logique
      var container = emptyMsg.closest('.wp-block-woocommerce-empty-cart-block')
        || emptyMsg.closest('.wp-block-woocommerce-cart')
        || emptyMsg.closest('.wc-block-cart')
        || emptyMsg.closest('.sapi-cart-outer')
        || emptyMsg.closest('.page-content')
        || emptyMsg.closest('main')
        || emptyMsg.parentElement;

      var productsHTML = '';
      if (popularProducts.length > 0) {
        productsHTML = '<div class="empty-cart-viewed"><h2 class="empty-cart-viewed-title">' + sectionTitle + '</h2><div class="empty-cart-viewed-grid">';
        for (var j = 0; j < popularProducts.length; j++) {
          var pr = popularProducts[j];
          productsHTML += '<a href="' + pr.url + '" class="empty-cart-viewed-card">';
          if (pr.img) {
            productsHTML += '<div class="empty-cart-viewed-image"><img src="' + pr.img + '" alt="' + pr.name + '" loading="lazy" /></div>';
          }
          productsHTML += '<div class="empty-cart-viewed-info">';
          productsHTML += '<h3>' + pr.name + '</h3>';
          if (pr.desc) productsHTML += '<span class="empty-cart-viewed-desc">' + pr.desc + '</span>';
          if (pr.cat) productsHTML += '<span class="empty-cart-viewed-cat">' + pr.cat + '</span>';
          productsHTML += '</div></a>';
        }
        productsHTML += '</div></div>';
      }

      container.innerHTML =
        '<section class="empty-cart-page">' +
          '<div class="empty-cart-content">' +
            '<div class="empty-cart-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg></div>' +
            '<h1 class="empty-cart-title">Votre panier est vide... pour l\u2019instant\u00a0!</h1>' +
            '<p class="empty-cart-text">Nos luminaires n\u2019attendent que vous. Laissez-vous inspirer par nos cr\u00e9ations artisanales.</p>' +
          '</div>' +
          '<div class="empty-cart-cta"><a href="' + shopUrl + '" class="empty-cart-btn">D\u00e9couvrir nos cr\u00e9ations</a></div>' +
          productsHTML +
        '</section>';
    }

    // Tenter plusieurs fois après le rendu React
    setTimeout(replaceEmptyCart, 300);
    setTimeout(replaceEmptyCart, 800);
    setTimeout(replaceEmptyCart, 1500);
    var obs = new MutationObserver(function() { if (!done) replaceEmptyCart(); });
    obs.observe(document.body, { childList: true, subtree: true });
  })();
  </script>
  <?php
});

// Supprime la limite de caractères sur la description courte dans le panier WooCommerce Blocks
add_filter('wc_blocks_product_short_description_character_limit', '__return_false');


add_filter('render_block', function ($content, $block) {
  if ($block['blockName'] === 'woocommerce/cart') {
    ob_start();
    ?>
    <div class="sapi-cart-outer">
      <div class="cart-page-cinetique">
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
      <?php echo $content; ?>
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
    </div>
    <?php
    return ob_get_clean();
  }
  if ($block['blockName'] === 'woocommerce/checkout') {
    ob_start();
    ?>
    <div class="sapi-checkout-outer">
      <div class="checkout-page-cinetique">
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
      <?php echo $content; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }
  return $content;
}, 10, 2);

/**
 * Helper: extract URL from ACF image field (handles all return formats)
 * Centralized here to avoid duplication across templates.
 */
function sapi_get_acf_image_url($field_value, $size = 'full') {
  if (!$field_value) return '';
  if (is_array($field_value) && isset($field_value['url'])) {
    return $field_value['url'];
  } elseif (is_array($field_value) && isset($field_value['ID'])) {
    return wp_get_attachment_image_url($field_value['ID'], $size);
  } elseif (is_numeric($field_value)) {
    return wp_get_attachment_image_url($field_value, $size);
  } elseif (is_string($field_value) && strpos($field_value, 'http') === 0) {
    return $field_value;
  }
  return '';
}

/**
 * Page confirmation (thankyou) : on rend manuellement les détails commande
 * et les adresses client dans notre template custom (2 colonnes).
 * Empêcher WooCommerce de les re-afficher via do_action('woocommerce_thankyou').
 */
remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);

// Masquer "Informations complémentaires" (champs additionnels checkout) sur la confirmation
add_action('woocommerce_order_details_after_customer_details', function() {
  // Vide : empêche les hooks suivants de s'exécuter en retirant tout
  remove_all_actions('woocommerce_order_details_after_customer_details', 20);
  remove_all_actions('woocommerce_order_details_after_customer_details', 30);
  remove_all_actions('woocommerce_order_details_after_customer_details', 40);
}, 1);

/**
 * Migration unique — supprime Elementor de la page checkout (ID 13).
 * S'exécute une fois au premier chargement admin, puis ne fait plus rien.
 */
add_action('admin_init', function () {
  $page_id = wc_get_page_id('checkout');
  if (!$page_id || $page_id < 1) return;
  if (get_post_meta($page_id, '_wp_page_template', true) !== 'elementor_header_footer') return;

  // 1. Template → défaut du thème
  update_post_meta($page_id, '_wp_page_template', 'default');

  // 2. Supprime toutes les metas Elementor
  foreach (['_elementor_data', '_elementor_template_type', '_elementor_edit_mode',
            '_elementor_version', '_elementor_css', '_elementor_controls_usage'] as $meta) {
    delete_post_meta($page_id, $meta);
  }

  // 3. Injecte le bloc WooCommerce Checkout dans post_content
  wp_update_post([
    'ID'           => $page_id,
    'post_content' => '<!-- wp:woocommerce/checkout --><div class="wp-block-woocommerce-checkout alignwide wc-block-checkout is-loading"></div><!-- /wp:woocommerce/checkout -->',
  ]);
});


function sapi_maison_content_width() {
  $GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'sapi_maison_content_width', 0);

// ACF hero image is attached to the WooCommerce Shop page
// Field: shop_hero_image (Image, return format: Array)
// Location rule in ACF: Page = Boutique (Shop page)


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
        'accessoires' => 'Accessoires pour luminaires artisanaux. Ampoules, câbles textile et pièces détachées pour vos créations Atelier Sâpi.',
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
    'les-accessoires' => 'accessoires',
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
        $display_name = $product->is_type('variation') ? wc_get_product($product_id)->get_name() : $product->get_name();
      ?>
        <div class="mini-cart-item" data-key="<?php echo esc_attr($cart_item_key); ?>">
          <div class="mini-cart-item-image">
            <?php echo $product->get_image('thumbnail'); ?>
          </div>
          <div class="mini-cart-item-details">
            <span class="mini-cart-item-name">
              <?php echo $product_permalink ? '<a href="' . esc_url($product_permalink) . '">' . esc_html($display_name) . '</a>' : esc_html($display_name); ?>
            </span>
            <div class="mini-cart-item-meta">
              <?php if (!empty($cart_item['variation'])) : ?>
                <?php foreach ($cart_item['variation'] as $attr_key => $attr_value) :
                  if (empty($attr_value)) continue;
                  $attr_name = wc_attribute_label(str_replace('attribute_', '', $attr_key), $product);
                ?>
                  <div class="mini-cart-var-line">
                    <span class="mini-cart-var-label"><?php echo esc_html($attr_name); ?> :</span>
                    <span class="mini-cart-var-value"><?php echo esc_html($attr_value); ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="mini-cart-item-bottom">
              <span class="mini-cart-item-price">
                <?php echo WC()->cart->get_product_price($product); ?>
              </span>
              <div class="mini-cart-qty-selector" data-cart-item-key="<?php echo esc_attr($cart_item_key); ?>">
                <button class="mini-cart-qty-btn mini-cart-qty-minus" aria-label="<?php esc_attr_e('Diminuer la quantité', 'theme-sapi-maison'); ?>">−</button>
                <span class="mini-cart-qty-value"><?php echo esc_html($quantity); ?></span>
                <button class="mini-cart-qty-btn mini-cart-qty-plus" aria-label="<?php esc_attr_e('Augmenter la quantité', 'theme-sapi-maison'); ?>">+</button>
              </div>
            </div>
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
 * AJAX handler: update mini-cart item quantity
 */
function sapi_update_mini_cart_qty() {
  // Verify nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sapi-update-mini-cart-qty')) {
    wp_send_json_error(['message' => 'Invalid nonce']);
    return;
  }

  $cart_item_key = sanitize_text_field($_POST['cart_item_key'] ?? '');
  $quantity = absint($_POST['quantity'] ?? 0);

  if (!$cart_item_key || !WC()->cart) {
    wp_send_json_error();
  }

  if ($quantity === 0) {
    WC()->cart->remove_cart_item($cart_item_key);
  } else {
    WC()->cart->set_quantity($cart_item_key, $quantity);
  }

  WC()->cart->calculate_totals();

  // Return updated fragments
  $fragments = apply_filters('woocommerce_add_to_cart_fragments', array());
  wp_send_json_success(array('fragments' => $fragments));
}
add_action('wp_ajax_sapi_update_mini_cart_qty', 'sapi_update_mini_cart_qty');
add_action('wp_ajax_nopriv_sapi_update_mini_cart_qty', 'sapi_update_mini_cart_qty');

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
      'accessoires' => 'Accessoires pour luminaires',
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

// Progress bar panier/checkout migrée dans le filtre render_block (compatible WooCommerce Blocks)

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

  $product_id   = isset($_POST['product_id'])   ? absint($_POST['product_id'])   : 0;
  $quantity     = isset($_POST['quantity'])      ? absint($_POST['quantity'])      : 1;
  $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : 0;

  // Collect variation attributes (attribute_pa_*)
  $variation = [];
  foreach ($_POST as $key => $value) {
    if (strpos($key, 'attribute_') === 0) {
      $variation[sanitize_title(wp_unslash($key))] = sanitize_text_field(wp_unslash($value));
    }
  }

  if ($product_id <= 0) {
    wp_send_json_error(['message' => 'Invalid product']);
    return;
  }

  // Add to cart (works for simple and variable products)
  $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation);

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
 * Fabrication (3 jours ouvrés) + Livraison (2 jours ouvrés)
 *
 * @return string Formatted date "12 février"
 */
function sapi_get_estimated_delivery_date() {
  $business_days_to_add = 5; // 3 jours fabrication + 2 jours livraison
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
 * ═══════════════════════════════════════════════════════════════════
 * GUIDE LUMINAIRE V2 — AJAX + Claude AI Integration
 * Filters products by category/format/ampoule, then generates
 * a personalised AI recommendation via the Claude API.
 * ═══════════════════════════════════════════════════════════════════
 */
add_action('wp_ajax_sapi_guide_results', 'sapi_ajax_guide_results');
add_action('wp_ajax_nopriv_sapi_guide_results', 'sapi_ajax_guide_results');

function sapi_ajax_guide_results() {
  // 1. Nonce check
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  // 2. Parse & sanitize answers
  $raw_answers = isset($_POST['answers']) ? sanitize_text_field(wp_unslash($_POST['answers'])) : '{}';
  $answers = json_decode($raw_answers, true);

  if (!is_array($answers) || empty($answers)) {
    wp_send_json_error(['message' => 'Données invalides']);
    return;
  }

  $clean = [];
  foreach ($answers as $key => $val) {
    $clean[sanitize_key($key)] = sanitize_text_field($val);
  }

  // 3. Determine product categories from step 1
  $categories = sapi_guide_get_categories($clean);

  // 4. Query main products
  $query_result = sapi_guide_query_products($clean, $categories);
  $products_data  = $query_result['products'];
  $fallback_notes = $query_result['fallback_notes'];

  // 5. Limit to 4 products for display AND for AI prompt
  $display_products = array_slice($products_data, 0, 4);

  // 6. Call Claude API for AI recommendation (only sees the 4 displayed products)
  $ai_response = null;
  if (!empty($display_products)) {
    $system_prompt = sapi_guide_build_system_prompt($display_products, $clean, $fallback_notes);
    $ai_response = sapi_guide_call_claude($system_prompt);
  }

  // 6. Build response
  $followup_buttons = [];
  $ai_text = null;

  if ($ai_response && isset($ai_response['recommendation'])) {
    $ai_text = $ai_response['recommendation'];
    $followup_buttons = isset($ai_response['followup_buttons']) ? $ai_response['followup_buttons'] : [];
  }

  if (empty($display_products)) {
    wp_send_json_error(['message' => 'Aucun produit trouvé']);
    return;
  }

  // 8. Send email notification to Robin
  $labels = [
    'piece'   => 'Pièce',
    'taille'  => 'Taille de la pièce',
    'sortie'  => 'Sortie électrique',
    'hauteur' => 'Hauteur sous-plafond',
    'table'   => 'Au-dessus d\'une table',
    'style'   => 'Style intérieur',
  ];
  $email_body = "Nouvelle recommandation Guide Luminaire\n";
  $email_body .= "========================================\n\n";
  $email_body .= "RÉPONSES DU CLIENT :\n";
  foreach ($labels as $key => $label) {
    if (isset($clean[$key])) {
      $email_body .= "- " . $label . " : " . $clean[$key] . "\n";
    }
  }
  $email_body .= "\nPRODUITS PROPOSÉS :\n";
  foreach ($display_products as $dp) {
    $email_body .= "- " . $dp['title'] . " (" . wp_strip_all_tags($dp['price']) . ")\n";
  }
  $email_body .= "\nRECOMMANDATION IA :\n";
  $email_body .= $ai_text ? $ai_text : "(pas de texte IA)";
  $email_body .= "\n\n---\nDate : " . wp_date('d/m/Y H:i');

  wp_mail(
    get_option('admin_email'),
    'Guide Luminaire — Nouvelle recommandation',
    $email_body
  );

  wp_send_json_success([
    'ai_text'          => $ai_text,
    'products'         => $display_products,
    'followup_buttons' => $followup_buttons,
  ]);
}

/**
 * Step 1 → WooCommerce product categories
 */
function sapi_guide_get_categories(array $answers) {
  $sortie = isset($answers['sortie']) ? $answers['sortie'] : '';
  $piece  = isset($answers['piece'])  ? $answers['piece']  : '';

  switch ($sortie) {
    case 'plafond':
      $cats = ['suspensions'];
      break;
    case 'mur':
      $cats = ['appliques'];
      break;
    case 'pas-de-sortie':
      $cats = ['lampadaires', 'lampeaposer', 'appliques'];
      break;
    default:
      $cats = ['suspensions', 'appliques', 'lampadaires', 'lampeaposer'];
  }

  // Règle A : jamais de lampe à poser en cuisine
  if ($piece === 'cuisine') {
    $cats = array_values(array_diff($cats, ['lampeaposer']));
  }

  return $cats;
}

/**
 * Get ampoule type filter based on room
 */
function sapi_guide_get_ampoule_filter($piece) {
  switch ($piece) {
    case 'cuisine':
    case 'bureau':
      return ['ampoule_degagee', 'semi_degagee'];
    case 'salon':
    case 'chambre':
      return ['ampoule_entouree', 'semi_degagee'];
    default:
      return null; // entrée/couloir: all types OK
  }
}

/**
 * Query main products based on guide answers
 */
function sapi_guide_query_products(array $answers, array $categories) {
  $tax_query = ['relation' => 'AND'];

  // Category filter
  $tax_query[] = [
    'taxonomy' => 'product_cat',
    'field'    => 'slug',
    'terms'    => $categories,
    'operator' => 'IN',
  ];

  // Format exclusion: plafond + standard + not above table → exclude vertical
  $exclude_vertical = (
    (isset($answers['sortie']) && $answers['sortie'] === 'plafond') &&
    (isset($answers['hauteur']) && $answers['hauteur'] === 'standard') &&
    (isset($answers['table']) && $answers['table'] === 'non')
  );

  if ($exclude_vertical) {
    $tax_query[] = [
      'taxonomy' => 'pa_format',
      'field'    => 'slug',
      'terms'    => ['vertical'],
      'operator' => 'NOT IN',
    ];
  }

  // Règle B : escalier → exclure format plat (préférer boule ou vertical)
  $piece = isset($answers['piece']) ? $answers['piece'] : '';
  if ($piece === 'escalier' && in_array('suspensions', $categories)) {
    $tax_query[] = [
      'taxonomy' => 'pa_format',
      'field'    => 'slug',
      'terms'    => ['plat'],
      'operator' => 'NOT IN',
    ];
  }

  // Ampoule filter based on room (reuses $piece from above)
  $ampoule_filter = sapi_guide_get_ampoule_filter($piece);
  $has_ampoule_filter = false;

  if ($ampoule_filter) {
    $tax_query[] = [
      'taxonomy' => 'pa_type-ampoule',
      'field'    => 'slug',
      'terms'    => $ampoule_filter,
      'operator' => 'IN',
    ];
    $has_ampoule_filter = true;
  }

  $args = [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 20,
    'tax_query'      => $tax_query,
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
  ];

  $query = new WP_Query($args);
  $fallback_notes = [];

  // Fallback: if no results with ampoule filter, retry without it
  if (!$query->have_posts() && $has_ampoule_filter) {
    wp_reset_postdata();
    array_pop($tax_query); // Remove ampoule filter
    $args['tax_query'] = $tax_query;
    $query = new WP_Query($args);
    $ideal_types = implode(' ou ', $ampoule_filter);
    $fallback_notes[] = "ATTENTION : aucun produit avec ampoule $ideal_types n'était disponible dans cette catégorie. Les produits ci-dessous peuvent avoir un type d'ampoule différent de l'idéal pour cette pièce. Signale-le honnêtement au client comme un compromis, ne dis pas que c'est le choix idéal.";
  }

  // Second fallback: if still no results, drop format exclusion too
  if (!$query->have_posts() && $exclude_vertical) {
    wp_reset_postdata();
    $args['tax_query'] = [
      ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $categories, 'operator' => 'IN'],
    ];
    $query = new WP_Query($args);
    $fallback_notes[] = "ATTENTION : le filtre de format a aussi été relâché. Des formats normalement exclus peuvent apparaître.";
  }

  $results = sapi_guide_collect_results($query, $answers);
  wp_reset_postdata();
  return ['products' => $results, 'fallback_notes' => $fallback_notes];
}

/**
 * Query complementary products for grande pièce
 */
function sapi_guide_query_complements(array $answers, array $main_categories) {
  // Complements: jamais de suspension (nécessite sortie plafond dédiée)
  $complement_pool = ['lampadaires', 'lampeaposer', 'appliques'];
  $complement_cats = array_values(array_diff($complement_pool, $main_categories));

  if (empty($complement_cats)) {
    $complement_cats = ['lampadaires', 'lampeaposer'];
  }

  $args = [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'tax_query'      => [
      ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $complement_cats, 'operator' => 'IN'],
    ],
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
  ];

  $query = new WP_Query($args);
  $results = sapi_guide_collect_results($query, $answers);
  wp_reset_postdata();
  return $results;
}

/**
 * Process query results into product data arrays
 */
function sapi_guide_collect_results($query, array $answers) {
  // Determine preferred essence from style answer
  $style = isset($answers['style']) ? $answers['style'] : '';
  $preferred_essence = '';
  if ($style === 'moderne') {
    $preferred_essence = 'peuplier';
  } elseif ($style === 'ancien') {
    $preferred_essence = 'okoume';
  }

  $products = [];
  while ($query->have_posts()) {
    $query->the_post();
    $product = wc_get_product(get_the_ID());
    if (!$product || $product->get_status() !== 'publish') {
      continue;
    }

    $image_id  = $product->get_image_id();
    $price     = $product->get_price_html();
    $variation_label = '';

    // Get category slugs
    $cats = get_the_terms($product->get_id(), 'product_cat');
    $cat_slugs = [];
    if ($cats && !is_wp_error($cats)) {
      foreach ($cats as $c) {
        $cat_slugs[] = $c->slug;
      }
    }

    // Get format attribute
    $format_terms = get_the_terms($product->get_id(), 'pa_format');
    $format = ($format_terms && !is_wp_error($format_terms)) ? $format_terms[0]->name : '';

    // Get ampoule attribute
    $ampoule_terms = get_the_terms($product->get_id(), 'pa_type-ampoule');
    $ampoule = ($ampoule_terms && !is_wp_error($ampoule_terms)) ? $ampoule_terms[0]->name : '';

    // Match preferred essence variation
    if ($preferred_essence && $product->is_type('variable')) {
      $variations = $product->get_available_variations();
      foreach ($variations as $var) {
        $materiau_value = isset($var['attributes']['attribute_pa_materiau'])
          ? $var['attributes']['attribute_pa_materiau']
          : '';
        if ($materiau_value === $preferred_essence) {
          if (!empty($var['image_id'])) {
            $image_id = $var['image_id'];
          }
          $var_product = wc_get_product($var['variation_id']);
          if ($var_product) {
            $price = $var_product->get_price_html();
          }
          $term = get_term_by('slug', $preferred_essence, 'pa_materiau');
          $variation_label = $term ? $term->name : ucfirst($preferred_essence);
          break;
        }
      }
    }

    $products[] = [
      'id'              => $product->get_id(),
      'title'           => $product->get_name(),
      'price'           => $price,
      'image'           => $image_id ? wp_get_attachment_url($image_id) : '',
      'image_alt'       => $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '',
      'permalink'       => get_permalink($product->get_id()),
      'variation_label' => $variation_label,
      'categories'      => $cat_slugs,
      'format'          => $format,
      'type_ampoule'    => $ampoule,
      'total_sales'     => (int) $product->get_total_sales(),
    ];
  }

  return $products;
}

/**
 * Find a product in the data array by ID
 */
function sapi_guide_find_product_by_id(array $products, $id) {
  foreach ($products as $p) {
    if ((int) $p['id'] === (int) $id) {
      return $p;
    }
  }
  return null;
}

/**
 * Build the system prompt for Claude with filtered products and client answers
 */
function sapi_guide_build_system_prompt(array $products_data, array $answers, array $fallback_notes = []) {
  $theme_dir = get_stylesheet_directory();

  // Load rules and tone from text files
  $regles = file_get_contents($theme_dir . '/assets/guide-prompt-regles.txt');
  $ton    = file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt');

  $prompt = $regles . "\n\n" . $ton . "\n\n";

  // Inject fallback warnings if filters were relaxed
  if (!empty($fallback_notes)) {
    $prompt .= implode("\n", $fallback_notes) . "\n\n";
  }

  // Catalogue filtré
  $prompt .= "CATALOGUE FILTRÉ (correspond aux besoins du client) :\n";
  foreach ($products_data as $p) {
    $prompt .= "- " . $p['title'] . " | Prix : " . wp_strip_all_tags($p['price']) . " | Catégorie : " . implode(', ', $p['categories']) . " | Format : " . $p['format'] . " | Ampoule : " . $p['type_ampoule'];
    if ($p['variation_label']) {
      $prompt .= " | Essence recommandée : " . $p['variation_label'];
    }
    $prompt .= " | Ventes : " . $p['total_sales'] . " | ID : " . $p['id'] . "\n";
  }

  // Réponses du client
  $prompt .= "\nRÉPONSES DU CLIENT :\n";
  $labels = [
    'piece'   => 'Pièce',
    'taille'  => 'Taille de la pièce',
    'sortie'  => 'Sortie électrique',
    'hauteur' => 'Hauteur sous-plafond',
    'table'   => 'Au-dessus d\'une table',
    'style'   => 'Style intérieur',
  ];
  foreach ($labels as $key => $label) {
    $val = isset($answers[$key]) ? $answers[$key] : 'Non demandé';
    $prompt .= "- " . $label . " : " . $val . "\n";
  }

  // Format de réponse JSON
  $prompt .= "\nFORMAT DE RÉPONSE (JSON strict, sans commentaires) :\n";
  $prompt .= "{\n";
  $prompt .= "  \"recommendation\": \"Texte expliquant pourquoi cette sélection correspond aux critères du client...\",\n";
  $prompt .= "  \"followup_buttons\": [\n";
  $prompt .= "    {\"label\": \"Texte du bouton\", \"type\": \"question\"},\n";
  $prompt .= "    {\"label\": \"Texte du bouton\", \"type\": \"question\"},\n";
  $prompt .= "    {\"label\": \"Texte du bouton\", \"type\": \"question\"},\n";
  $prompt .= "    {\"label\": \"Texte du bouton\", \"type\": \"contact\"}\n";
  $prompt .= "  ]\n";
  $prompt .= "}\n";

  return $prompt;
}

/**
 * Call Claude API and return parsed response
 */
function sapi_guide_call_claude($system_prompt) {
  $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
  if (empty($api_key)) {
    return null;
  }

  $body = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 1024,
    'system'     => $system_prompt,
    'messages'   => [
      ['role' => 'user', 'content' => 'Voici mes réponses au questionnaire. Explique-moi pourquoi cette sélection de luminaires correspond à mes critères.'],
    ],
  ];

  $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
    'timeout' => 30,
    'headers' => [
      'Content-Type'     => 'application/json',
      'x-api-key'        => $api_key,
      'anthropic-version' => '2023-06-01',
    ],
    'body' => wp_json_encode($body),
  ]);

  if (is_wp_error($response)) {
    error_log('Sapi Guide Claude API error: ' . $response->get_error_message());
    return null;
  }

  $status = wp_remote_retrieve_response_code($response);
  $raw_body = wp_remote_retrieve_body($response);

  if ($status !== 200) {
    error_log('Sapi Guide Claude API HTTP ' . $status . ': ' . $raw_body);
    return null;
  }

  $data = json_decode($raw_body, true);
  if (!isset($data['content'][0]['text'])) {
    return null;
  }

  $text = $data['content'][0]['text'];

  // Clean markdown code fences if present
  $text = preg_replace('/^```json\s*/i', '', trim($text));
  $text = preg_replace('/\s*```$/i', '', $text);

  $parsed = json_decode(trim($text), true);
  if (!$parsed || !isset($parsed['recommendation'])) {
    // If Claude didn't return valid JSON, use the raw text as recommendation
    return [
      'recommendation'   => $text,
      'followup_buttons' => [],
    ];
  }

  return $parsed;
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

  // Get ALL published products to filter client-side
  $all_products_args = [
    'post_type' => 'product',
    'post_status' => 'publish',
    'posts_per_page' => -1, // Get all products
    'tax_query' => [
      [
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => ['accessoires'], // Exclude accessories category
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

/**
 * Newsletter opt-out checkbox on checkout
 * Adds a checkbox below "Ajouter une note de commande" (location: order)
 */
add_action('woocommerce_init', function () {
  if (!function_exists('woocommerce_register_additional_checkout_field')) return;

  woocommerce_register_additional_checkout_field([
    'id'       => 'sapi-maison/newsletter-optout',
    'label'    => 'Je ne souhaite pas recevoir les actualités de l\'Atelier Sâpi',
    'location' => 'order',
    'type'     => 'checkbox',
    'default'  => false,
  ]);
});


// Save the opt-out choice as order meta
add_action('woocommerce_set_additional_field_value', function ($key, $value, $group, $wc_object) {
  if ($key !== 'sapi-maison/newsletter-optout') return;
  if (!($wc_object instanceof WC_Order)) return;
  $wc_object->update_meta_data('_sapi_newsletter_optout', wc_bool_to_string($value));
}, 10, 4);

/**
 * ============================================================
 * CUSTOM POST TYPE : Projets Sur Mesure
 * ============================================================
 */
function sapi_register_cpt_projet_sur_mesure() {
  register_post_type('projet_sur_mesure', [
    'labels' => [
      'name'               => 'Projets Sur Mesure',
      'singular_name'      => 'Projet Sur Mesure',
      'add_new'            => 'Ajouter un projet',
      'add_new_item'       => 'Ajouter un projet sur mesure',
      'edit_item'          => 'Modifier le projet',
      'new_item'           => 'Nouveau projet',
      'view_item'          => 'Voir le projet',
      'search_items'       => 'Rechercher un projet',
      'not_found'          => 'Aucun projet trouvé',
      'not_found_in_trash' => 'Aucun projet dans la corbeille',
    ],
    'public'        => false,
    'show_ui'       => true,
    'show_in_menu'  => true,
    'menu_position' => 25,
    'menu_icon'     => 'dashicons-lightbulb',
    'supports'      => ['title', 'editor', 'thumbnail', 'revisions'],
    'has_archive'   => false,
    'rewrite'       => false,
  ]);
}
add_action('init', 'sapi_register_cpt_projet_sur_mesure');

/**
 * ACF fields for Projets Sur Mesure
 */
function sapi_register_acf_projet_sur_mesure() {
  if (!function_exists('acf_add_local_field_group')) return;

  acf_add_local_field_group([
    'key'      => 'group_projet_sur_mesure',
    'title'    => 'Détails du projet',
    'fields'   => [
      [
        'key'   => 'field_psm_essence',
        'label' => 'Essence de bois',
        'name'  => 'essence_bois',
        'type'  => 'text',
        'placeholder' => 'Ex: Peuplier, Okoumé, Chêne...',
      ],
      [
        'key'   => 'field_psm_dimensions',
        'label' => 'Dimensions',
        'name'  => 'dimensions_projet',
        'type'  => 'text',
        'placeholder' => 'Ex: 60 x 40 cm',
      ],
      [
        'key'   => 'field_psm_piece',
        'label' => 'Pièce de destination',
        'name'  => 'piece_destination',
        'type'  => 'text',
        'placeholder' => 'Ex: Salon, Chambre, Restaurant...',
      ],
      [
        'key'   => 'field_psm_temoignage',
        'label' => 'Témoignage client',
        'name'  => 'temoignage_client',
        'type'  => 'textarea',
        'rows'  => 3,
        'instructions' => 'Citation du client (optionnel)',
      ],
      [
        'key'   => 'field_psm_nom_client',
        'label' => 'Nom du client',
        'name'  => 'nom_client',
        'type'  => 'text',
        'placeholder' => 'Ex: Marie, Restaurant Le Comptoir...',
        'instructions' => 'Prénom ou nom affiché sous le témoignage (optionnel)',
      ],
    ],
    'location' => [
      [
        [
          'param'    => 'post_type',
          'operator' => '==',
          'value'    => 'projet_sur_mesure',
        ],
      ],
    ],
    'position' => 'normal',
    'style'    => 'default',
  ]);
}
add_action('acf/init', 'sapi_register_acf_projet_sur_mesure');

/**
 * Handle "Sur Mesure" contact form submission
 */
function sapi_handle_surmesure_form() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['sapi_surmesure_nonce'])) {
    return ['submitted' => false, 'success' => false, 'error' => ''];
  }

  if (!wp_verify_nonce($_POST['sapi_surmesure_nonce'], 'sapi_surmesure_form')) {
    return ['submitted' => true, 'success' => false, 'error' => 'Erreur de sécurité. Veuillez réessayer.'];
  }

  // Honeypot
  if (!empty($_POST['website'])) {
    return ['submitted' => true, 'success' => false, 'error' => 'Spam détecté.'];
  }

  $name    = sanitize_text_field($_POST['name'] ?? '');
  $email   = sanitize_email($_POST['email'] ?? '');
  $message = sanitize_textarea_field($_POST['message'] ?? '');

  if (empty($name) || empty($email) || empty($message)) {
    return ['submitted' => true, 'success' => false, 'error' => 'Veuillez remplir tous les champs.'];
  }

  if (!is_email($email)) {
    return ['submitted' => true, 'success' => false, 'error' => 'Adresse email invalide.'];
  }

  $to      = 'contact@atelier-sapi.fr';
  $subject = '[Sur Mesure] Nouveau projet de ' . $name;
  $body    = "Nom: $name\n";
  $body   .= "Email: $email\n\n";
  $body   .= "Projet sur mesure:\n$message";

  $headers = [
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . get_bloginfo('name') . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
  ];

  if (wp_mail($to, $subject, $body, $headers)) {
    return ['submitted' => true, 'success' => true, 'error' => ''];
  }

  return ['submitted' => true, 'success' => false, 'error' => "Erreur lors de l'envoi. Veuillez réessayer ou nous contacter directement par email."];
}


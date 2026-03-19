<?php

if (!defined('ABSPATH')) {
  exit;
}

/* ─── Feature flag Robin Conseiller V2 ─── */
if (!defined('SAPI_ROBIN_V2')) {
  define('SAPI_ROBIN_V2', true);
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
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/lampesaposer/')); ?>">À poser</a></li>
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
          <li class="menu-item"><a href="<?php echo esc_url(home_url('/categorie-produit/lampesaposer/')); ?>">À poser</a></li>
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
    'wcAjaxUrl'     => home_url('/?wc-ajax='),
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

  // Guide personalization — swap product card images based on guide preferences
  if (is_front_page() || (class_exists('WooCommerce') && (is_shop() || is_product_category()))) {
    $gp_path = get_template_directory() . '/assets/guide-personalize.js';
    if (file_exists($gp_path)) {
      wp_enqueue_script('sapi-guide-personalize', get_template_directory_uri() . '/assets/guide-personalize.js', [], filemtime($gp_path), true);
    }
  }

  // Cart page JS — enqueued when is_cart() returns true
  if (class_exists('WooCommerce') && is_cart()) {
    $cart_js_path = get_template_directory() . '/assets/cart-page.js';
    if (file_exists($cart_js_path)) {
      wp_enqueue_script('sapi-maison-cart-page', get_template_directory_uri() . '/assets/cart-page.js', [], filemtime($cart_js_path), true);
    }
  }


  // Scroll Dots — mobile slide indicators (grilles verticales → sliders horizontaux)
  $scroll_dots_path = get_template_directory() . '/assets/scroll-dots.js';
  if (file_exists($scroll_dots_path)) {
    wp_enqueue_script('sapi-maison-scroll-dots', get_template_directory_uri() . '/assets/scroll-dots.js', [], filemtime($scroll_dots_path), true);
  }

  // Guide luminaire — bandeau + questionnaire (toutes les pages)
  require_once get_template_directory() . '/inc/guide-data.php';

  if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) {
    // V2 — Robin Conseiller : modale diaporama
    $robin_js_path = get_template_directory() . '/assets/robin-conseiller.js';
    if (file_exists($robin_js_path)) {
      wp_enqueue_script('sapi-robin-conseiller', get_template_directory_uri() . '/assets/robin-conseiller.js', [], filemtime($robin_js_path), true);
      $conseils_path = get_template_directory() . '/assets/guide-conseils.json';
      $conseils_data = file_exists($conseils_path) ? json_decode(file_get_contents($conseils_path), true) : [];
      wp_localize_script('sapi-robin-conseiller', 'sapiRobinConseiller', [
        'steps'    => sapi_guide_get_steps(),
        'icons'    => sapi_guide_get_icons(),
        'conseils' => $conseils_data,
        'ajaxUrl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('sapi-guide-results'),
      ]);
    }
  } else {
    // V1 — Mon Projet : bandeau dépliable (legacy)
    $mon_projet_path = get_template_directory() . '/assets/mon-projet.js';
    if (file_exists($mon_projet_path)) {
      wp_enqueue_script('sapi-mon-projet', get_template_directory_uri() . '/assets/mon-projet.js', [], filemtime($mon_projet_path), true);
      wp_localize_script('sapi-mon-projet', 'sapiMonProjet', [
        'steps'   => sapi_guide_get_steps(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('sapi-guide-results'),
      ]);
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
 * Shortcode classique [woocommerce_cart] — Enveloppe dans .sapi-cart-outer
 * Injecte la progress bar + réassurances (même rendu que le bloc WC Blocks)
 */
add_action('woocommerce_before_cart', function () {
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
          <span class="step-label"><?php esc_html_e('Paiement', 'theme-sapi-maison'); ?></span>
        </div>
      </div>
      <p class="cart-subtitle"><?php esc_html_e('Plus que quelques clics avant de recevoir votre luminaire !', 'theme-sapi-maison'); ?></p>
  <?php
});

add_action('woocommerce_after_cart', function () {
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
  </div>
  <?php
});

/**
 * render_block — Enveloppe le bloc panier WooCommerce dans .sapi-cart-outer
 * Ce wrapper est injecté côté PHP AVANT React et ne sera jamais touché par React.
 * Il permet de scoper notre CSS avec une spécificité garantie.
 */
// Remplace "Supprimer l'élément" par "Supprimer" dans le panier (rendu React)
add_action('wp_footer', function () {
  if (!function_exists('is_cart') || !is_cart()) return;

  // Mapping slug → product_id pour les cross-sells (utilisé par le JS ci-dessous)
  $cross_sell_ids = WC()->cart->get_cross_sells();
  $slug_map = [];
  foreach ($cross_sell_ids as $pid) {
    $p = wc_get_product($pid);
    if ($p) {
      $slug_map[$p->get_slug()] = $pid;
    }
  }
  ?>
  <script>
  var sapiCrossSellMap = <?php echo wp_json_encode($slug_map); ?>;
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
    // Cross-sell: ajout direct au panier en AJAX (un clic = produit ajouté)
    function fixCrossSellButtons() {
      document.querySelectorAll('.wc-block-cart__main .wc-block-components-product-button__button').forEach(function (btn) {
        if (btn.dataset.fixedAjax) return;
        btn.dataset.fixedAjax = 'true';

        var li = btn.closest('li') || btn.closest('.wc-block-grid__product');
        if (!li) return;
        var productLink = li.querySelector('a[href]');
        if (!productLink) return;
        var url = productLink.getAttribute('href');

        // Extraire le slug depuis l'URL du produit
        var slug = url.replace(/\/$/, '').split('/').pop();
        var productId = (window.sapiCrossSellMap || {})[slug];
        if (!productId) return;

        var originalText = btn.textContent;

        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          e.stopImmediatePropagation();

          if (btn.disabled) return;
          btn.disabled = true;
          btn.textContent = 'Ajout\u2026';

          var formData = new FormData();
          formData.append('product_id', productId);
          formData.append('quantity', 1);

          fetch('/?wc-ajax=add_to_cart', {
            method: 'POST',
            body: formData
          })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.error) {
              btn.textContent = 'Erreur';
              setTimeout(function () {
                btn.textContent = originalText;
                btn.disabled = false;
              }, 2000);
            } else {
              btn.textContent = 'Ajout\u00e9 \u2713';
              setTimeout(function () { location.reload(); }, 800);
            }
          })
          .catch(function () {
            btn.textContent = 'Erreur';
            setTimeout(function () {
              btn.textContent = originalText;
              btn.disabled = false;
            }, 2000);
          });
        }, true);
      });
    }

    replaceCartTexts();
    fixCrossSellButtons();
    new MutationObserver(function () {
      replaceCartTexts();
      fixCrossSellButtons();
    }).observe(document.body, { childList: true, subtree: true });
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

      // Chercher le texte "vide" partout dans la page (tous types d'éléments)
      var allEls = document.querySelectorAll('h1, h2, h3, h4, p, strong, span, div');
      var emptyMsg = null;
      for (var i = 0; i < allEls.length; i++) {
        if (allEls[i].textContent.indexOf('vide') !== -1 && allEls[i].children.length < 3) {
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

    // Tenter plusieurs fois après le rendu React (mobile peut être plus lent)
    setTimeout(replaceEmptyCart, 300);
    setTimeout(replaceEmptyCart, 800);
    setTimeout(replaceEmptyCart, 1500);
    setTimeout(replaceEmptyCart, 3000);
    setTimeout(replaceEmptyCart, 5000);
    var obs = new MutationObserver(function() { if (!done) replaceEmptyCart(); });
    obs.observe(document.body, { childList: true, subtree: true, characterData: true });
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
            <span class="step-label"><?php esc_html_e('Paiement', 'theme-sapi-maison'); ?></span>
          </div>
        </div>
        <p class="cart-subtitle"><?php esc_html_e('Plus que quelques clics avant de recevoir votre luminaire !', 'theme-sapi-maison'); ?></p>
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
    <script>
    (function() {
      function addShippingNote() {
        var shipping = document.querySelector('.sapi-cart-outer .wc-block-components-totals-shipping');
        if (shipping && !shipping.querySelector('.cart-shipping-note')) {
          var note = document.createElement('p');
          note.className = 'cart-shipping-note';
          note.textContent = 'Choisissez votre mode de livraison \u00e0 la prochaine \u00e9tape';
          shipping.appendChild(note);
        }
      }
      // WC Blocks renders via React — observe DOM changes
      var observer = new MutationObserver(addShippingNote);
      var target = document.querySelector('.sapi-cart-outer');
      if (target) observer.observe(target, { childList: true, subtree: true });
      addShippingNote();
    })();
    </script>
    <?php
    return ob_get_clean();
  }
  if ($block['blockName'] === 'woocommerce/checkout') {
    // Sur la page thank you, le template thankyou.php gère déjà sa propre progress bar
    if (function_exists('is_wc_endpoint_url') && is_wc_endpoint_url('order-received')) {
      return $content;
    }
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
            <span class="step-label"><?php esc_html_e('Paiement', 'theme-sapi-maison'); ?></span>
          </div>
        </div>
        <h1 class="checkout-title"><?php esc_html_e('Finaliser ma commande', 'theme-sapi-maison'); ?></h1>
        <p class="checkout-subtitle"><?php esc_html_e('Votre luminaire sera bientôt chez vous !', 'theme-sapi-maison'); ?></p>
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
              <?php
              // Afficher les add-ons — double approche pour fiabilité
              $addon_rendered = false;

              // Méthode 1: données directes du plugin Product Add-Ons
              if (!empty($cart_item['addons']) && is_array($cart_item['addons'])) :
                foreach ($cart_item['addons'] as $addon) :
                  $name = isset($addon['name']) ? $addon['name'] : '';
                  $value = isset($addon['value']) ? $addon['value'] : '';
                  if (empty($name) || empty($value)) continue;
                  $addon_rendered = true;
              ?>
                  <div class="mini-cart-var-line">
                    <span class="mini-cart-var-label"><?php echo esc_html(rtrim($name, ': ')); ?> :</span>
                    <span class="mini-cart-var-value"><?php echo esc_html($value); ?></span>
                  </div>
              <?php endforeach; endif;

              // Méthode 2 (fallback): parsing du texte WooCommerce
              if (!$addon_rendered) :
                $flat_data = wc_get_formatted_cart_item_data($cart_item, true);
                if (!empty($flat_data)) :
                  $lines = array_filter(explode("\n", trim($flat_data)));
                  foreach ($lines as $line) :
                    $parts = explode(': ', $line, 2);
                    if (count($parts) === 2) :
              ?>
                  <div class="mini-cart-var-line">
                    <span class="mini-cart-var-label"><?php echo esc_html(rtrim($parts[0], ': ')); ?> :</span>
                    <span class="mini-cart-var-value"><?php echo esc_html($parts[1]); ?></span>
                  </div>
              <?php endif; endforeach; endif; endif; ?>
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
add_action('wc_ajax_sapi_update_mini_cart_qty', 'sapi_update_mini_cart_qty');

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
 * Utilise wc-ajax pour que les plugins (Product Add-Ons) chargent leurs hooks frontend
 */
add_action('wp_ajax_sapi_add_to_cart', 'sapi_ajax_add_to_cart');
add_action('wp_ajax_nopriv_sapi_add_to_cart', 'sapi_ajax_add_to_cart');
add_action('wc_ajax_sapi_add_to_cart', 'sapi_ajax_add_to_cart');

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
add_action('wc_ajax_sapi_buy_now', 'sapi_ajax_buy_now');

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

function sapi_guide_check_rate_limit() {
  $ip  = md5(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown');
  $key = 'sapi_guide_rl_' . $ip;
  $hits = (int) get_transient($key);
  if ($hits >= 30) {
    return false;
  }
  set_transient($key, $hits + 1, HOUR_IN_SECONDS);
  return true;
}

function sapi_ajax_guide_results() {
  // 1. Nonce check
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  // 1b. Rate limiting (10 appels IA/heure par IP) — checked later, products still returned
  $ai_allowed = sapi_guide_check_rate_limit();

  // 1c. Honeypot check
  if (!empty($_POST['guide_website'])) {
    $bot_ip  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'inconnue';
    $bot_val = sanitize_text_field(wp_unslash($_POST['guide_website']));
    wp_mail(
      'contact@atelier-sapi.fr',
      '[Sécurité] Bot détecté sur le quiz luminaire',
      "Un robot a rempli le champ honeypot du questionnaire guide luminaire.\n\n" .
      "IP : " . $bot_ip . "\n" .
      "Valeur du champ : " . $bot_val . "\n" .
      "Date : " . current_time('d/m/Y H:i:s') . "\n\n" .
      "Le bot a été bloqué automatiquement."
    );
    wp_send_json_error(['message' => 'Erreur de validation']);
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

  // Normalise taille_escalier → taille pour le filtrage produits
  // standard → petite (suspensions compactes), ouvert → grande (grandes suspensions verticales)
  if (!empty($clean['taille_escalier']) && empty($clean['taille'])) {
    $clean['taille'] = ($clean['taille_escalier'] === 'ouvert') ? 'grande' : 'petite';
  }

  // 3. Determine product categories
  $categories = sapi_guide_get_categories($clean);

  // 4. Query main products
  $query_result = sapi_guide_query_products($clean, $categories);
  $products_data  = $query_result['products'];
  $fallback_notes = $query_result['fallback_notes'];

  // 4b. Build filter context for refinement calls
  $filter_context = sapi_guide_build_filter_context($clean, $categories, $fallback_notes);

  // 5. Show sur mesure card? (grappe, grande pièce, haute hauteur)
  $show_sur_mesure = false;
  $eclairage_answer = isset($clean['eclairage']) ? $clean['eclairage'] : '';
  $taille_answer    = isset($clean['taille'])    ? $clean['taille']    : '';
  $hauteur_answer   = isset($clean['hauteur'])   ? $clean['hauteur']   : '';

  $sur_mesure_reason = '';
  if ($eclairage_answer === 'grappe') {
    $show_sur_mesure = true;
    $sur_mesure_reason = 'grappe';
  } elseif ($taille_answer === 'grande') {
    $show_sur_mesure = true;
    $sur_mesure_reason = 'grande';
  } elseif (in_array($hauteur_answer, ['haute', 'confortable'], true)) {
    $show_sur_mesure = true;
    $sur_mesure_reason = 'hauteur';
  }

  // 6. Pick products: 3 if sur mesure card shown (4th slot = carte sur mesure), else 4
  // Grappe: diversify by format (one of each)
  $diversify_format = ($eclairage_answer === 'grappe');
  $display_products = sapi_guide_pick_four($products_data, $show_sur_mesure ? 3 : 4, $diversify_format);

  // 6. Call Claude API for AI recommendation (skip if rate limited)
  $ai_response = null;
  if (!empty($display_products) && $ai_allowed) {
    $system_prompt = sapi_guide_build_system_prompt($display_products, $clean, $fallback_notes, $show_sur_mesure);
    $ai_response = sapi_guide_call_claude($system_prompt);
  }

  // 6. Build response
  $conseils_text = null;
  $selection_text = null;
  $sur_mesure_text = null;

  if ($ai_response) {
    if (isset($ai_response['conseils_text'])) {
      $conseils_text = $ai_response['conseils_text'];
    }
    if (isset($ai_response['selection_text'])) {
      $selection_text = $ai_response['selection_text'];
    }
    if (isset($ai_response['sur_mesure_text'])) {
      $sur_mesure_text = $ai_response['sur_mesure_text'];
    }
  }

  if (empty($display_products)) {
    wp_send_json_error(['message' => 'Aucun produit trouvé']);
    return;
  }

  // Log session
  $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : wp_generate_uuid4();
  $product_ids_for_log = array_map(function($p) { return $p['id']; }, $display_products);
  sapi_guide_log_initial($session_id, $clean, $product_ids_for_log, $conseils_text ?: '');

  wp_send_json_success([
    'conseils_text'     => $conseils_text,
    'selection_text'    => $selection_text,
    'products'          => $display_products,
    'show_sur_mesure'   => $show_sur_mesure,
    'sur_mesure_reason' => $sur_mesure_reason,
    'sur_mesure_text'   => $sur_mesure_text,
    'filter_context'    => $filter_context,
    'session_id'        => $session_id,
  ]);
}

/**
 * ── Phase B : Contact form from guide results ──
 * Receives the client's message + contact info + full guide context.
 * Sends a lead email to Robin.
 */
add_action('wp_ajax_sapi_guide_contact', 'sapi_ajax_guide_contact');
add_action('wp_ajax_nopriv_sapi_guide_contact', 'sapi_ajax_guide_contact');

function sapi_ajax_guide_contact() {
  // 1. Nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-contact')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  // 2. Honeypot
  if (!empty($_POST['website'])) {
    wp_send_json_error(['message' => 'Spam']);
    return;
  }

  // 3. Sanitize
  $name    = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
  $email   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
  $phone   = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
  $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
  $ai_text = sanitize_textarea_field(wp_unslash($_POST['ai_text'] ?? ''));

  // 4. Validate
  if (empty($name)) {
    wp_send_json_error(['message' => 'Prénom requis']);
    return;
  }
  if (empty($email) && empty($phone)) {
    wp_send_json_error(['message' => 'Email ou téléphone requis']);
    return;
  }
  if (!empty($email) && !is_email($email)) {
    wp_send_json_error(['message' => 'Email invalide']);
    return;
  }

  // 5. Parse guide answers + conversation history
  $labels_raw = json_decode(wp_unslash($_POST['labels'] ?? '{}'), true);
  if (!is_array($labels_raw)) {
    $labels_raw = [];
  }
  $conversation_raw = json_decode(wp_unslash($_POST['conversation'] ?? '[]'), true);
  if (!is_array($conversation_raw)) {
    $conversation_raw = [];
  }

  // 6. Build email body
  $body  = "Nouveau message depuis le Guide Luminaire\n";
  $body .= "==========================================\n\n";
  $body .= "CLIENT :\n";
  $body .= "- Prénom : " . esc_html($name) . "\n";
  if ($email) {
    $body .= "- Email : " . esc_html($email) . "\n";
  }
  if ($phone) {
    $body .= "- Téléphone : " . esc_html($phone) . "\n";
  }
  $body .= "\nMESSAGE :\n" . esc_html($message) . "\n";
  $body .= "\nRÉPONSES AU QUESTIONNAIRE :\n";
  foreach ($labels_raw as $step => $label) {
    $body .= "- " . ucfirst(sanitize_text_field($step)) . " : " . sanitize_text_field($label) . "\n";
  }
  // Include conversation history if the client had a back-and-forth with the AI
  if (!empty($conversation_raw)) {
    $body .= "\nHISTORIQUE DE CONVERSATION :\n";
    foreach ($conversation_raw as $msg) {
      if (!isset($msg['role']) || !isset($msg['content'])) continue;
      $role_label = ($msg['role'] === 'user') ? 'Client' : 'IA (Robin)';
      $body .= $role_label . " : " . sanitize_textarea_field($msg['content']) . "\n\n";
    }
  } else {
    $body .= "\nRECOMMANDATION IA :\n" . ($ai_text ? esc_html($ai_text) : '(pas de texte IA)') . "\n";
  }
  $body .= "\n---\nDate : " . wp_date('d/m/Y H:i') . "\n";

  // 7. Headers
  $headers = ['Content-Type: text/plain; charset=UTF-8'];
  if ($email) {
    $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
  }

  // 8. Send
  $sent = wp_mail(
    'contact@atelier-sapi.fr',
    '[Guide Luminaire] Message de ' . $name,
    $body,
    $headers
  );

  // Log contact
  $contact_session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
  if ($contact_session_id) {
    sapi_guide_log_contact($contact_session_id, $name, $email);
  }

  if ($sent) {
    wp_send_json_success(['message' => 'Envoyé']);
  } else {
    wp_send_json_error(['message' => 'Erreur envoi']);
  }
}

/**
 * ── Contact inline depuis la card Robin-Conseil ──
 * Envoie vers Brevo (liste dédiée) + email de notification à Robin.
 */
add_action('wp_ajax_sapi_robin_contact', 'sapi_ajax_robin_contact');
add_action('wp_ajax_nopriv_sapi_robin_contact', 'sapi_ajax_robin_contact');

function sapi_ajax_robin_contact() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  $email   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
  $phone   = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
  $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
  $project = sanitize_text_field(wp_unslash($_POST['project'] ?? ''));
  $page    = sanitize_text_field(wp_unslash($_POST['page'] ?? ''));

  if (empty($email) || !is_email($email)) {
    wp_send_json_error(['message' => 'Email requis']);
    return;
  }

  // 1. Envoyer vers Brevo (liste 7 = demandes de contact Mon Projet)
  $api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
  if ($api_key) {
    $attributes = [];
    if ($phone)   $attributes['SMS']     = $phone;
    if ($message) $attributes['MESSAGE']  = $message;
    if ($project) $attributes['PROJET']   = $project;
    if ($page)    $attributes['PAGE']     = $page;

    wp_remote_post('https://api.brevo.com/v3/contacts', [
      'headers' => [
        'api-key'      => $api_key,
        'Content-Type' => 'application/json',
        'Accept'       => 'application/json',
      ],
      'body' => wp_json_encode([
        'email'         => $email,
        'listIds'       => [6],
        'attributes'    => $attributes,
        'updateEnabled' => true,
      ]),
      'timeout' => 15,
    ]);
  }

  // 2. Email de notification à Robin
  $body  = "Demande de contact depuis « Contacter Robin »\n";
  $body .= "=============================================\n\n";
  $body .= "EMAIL : " . esc_html($email) . "\n";
  if ($phone) {
    $body .= "TÉLÉPHONE : " . esc_html($phone) . "\n";
  }
  if ($message) {
    $body .= "\nMESSAGE : " . esc_html($message) . "\n";
  }
  if ($project) {
    $body .= "\nPROJET : " . esc_html($project) . "\n";
  }
  $body .= "\nPAGE : " . esc_html($page) . "\n";
  $body .= "DATE : " . wp_date('d/m/Y H:i') . "\n";

  wp_mail(
    'contact@atelier-sapi.fr',
    '[Mon Projet] Demande de contact',
    $body,
    [
      'Content-Type: text/plain; charset=UTF-8',
      'Reply-To: ' . $email,
    ]
  );

  wp_send_json_success(['message' => 'Envoyé']);
}

/**
 * ── Phase C : Smart refinement — AI routes client message ──
 * Client sends a follow-up message after seeing initial results.
 * Claude decides: refine products, show contact form, or both.
 */
add_action('wp_ajax_sapi_guide_refine', 'sapi_ajax_guide_refine');
add_action('wp_ajax_nopriv_sapi_guide_refine', 'sapi_ajax_guide_refine');

function sapi_ajax_guide_refine() {
  // 1. Nonce check (reuses the guide results nonce)
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  // 1b. Rate limiting — if exceeded, fallback to contact form
  if (!sapi_guide_check_rate_limit()) {
    wp_send_json_success([
      'action'         => 'contact',
      'ai_text'        => 'Je ne peux plus affiner ma recherche pour le moment. Laissez vos coordonnées et Robin vous répondra personnellement.',
      'conversation'   => [],
    ]);
    return;
  }

  // 2. Parse inputs
  $user_message      = sanitize_textarea_field(wp_unslash($_POST['user_message'] ?? ''));
  $raw_answers       = isset($_POST['answers']) ? sanitize_text_field(wp_unslash($_POST['answers'])) : '{}';
  $answers           = json_decode($raw_answers, true);
  $conversation_raw  = isset($_POST['conversation']) ? wp_unslash($_POST['conversation']) : '[]';
  $conversation      = json_decode($conversation_raw, true);
  $current_ids_raw   = isset($_POST['current_products']) ? wp_unslash($_POST['current_products']) : '[]';
  $current_product_ids = json_decode($current_ids_raw, true);
  $filter_context    = sanitize_textarea_field(wp_unslash($_POST['filter_context'] ?? ''));

  if (empty($user_message)) {
    wp_send_json_error(['message' => 'Message vide']);
    return;
  }

  if (!is_array($answers)) $answers = [];
  if (!is_array($conversation)) $conversation = [];
  if (!is_array($current_product_ids)) $current_product_ids = [];

  // Limiter l'historique de conversation pour éviter de dépasser les limites de tokens
  $conversation = array_slice($conversation, -20);

  // Sanitize answers
  $clean = [];
  foreach ($answers as $key => $val) {
    $clean[sanitize_key($key)] = sanitize_text_field($val);
  }

  // Sanitize current product IDs
  $current_product_ids = array_map('intval', $current_product_ids);

  // 3. Get FULL product catalog
  $all_products = sapi_guide_query_all_products($clean);

  // 4. Build refinement system prompt
  $system_prompt = sapi_guide_build_refine_prompt(
    $all_products,
    $clean,
    $filter_context,
    $current_product_ids
  );

  // 5. Build messages array with conversation history
  $messages = [];
  if (is_array($conversation)) {
    foreach ($conversation as $msg) {
      if (!isset($msg['role']) || !isset($msg['content'])) continue;
      $role = ($msg['role'] === 'assistant') ? 'assistant' : 'user';
      $messages[] = ['role' => $role, 'content' => sanitize_textarea_field($msg['content'])];
    }
  }
  $messages[] = ['role' => 'user', 'content' => $user_message];

  // 6. Call Claude API
  $ai_response = sapi_guide_call_claude_refine($system_prompt, $messages);

  if (!$ai_response) {
    // Fallback: route to contact form
    wp_send_json_success([
      'action'       => 'contact',
      'ai_text'      => '',
      'products'     => [],
      'conversation' => array_merge(
        $conversation,
        [['role' => 'user', 'content' => $user_message]]
      ),
    ]);
    return;
  }

  $action         = isset($ai_response['action']) ? $ai_response['action'] : 'contact';
  $recommendation = isset($ai_response['recommendation']) ? $ai_response['recommendation'] : '';
  $raw_product_ids = isset($ai_response['product_ids']) ? $ai_response['product_ids'] : [];

  // Validate action
  if (!in_array($action, ['refine', 'contact', 'both'], true)) {
    $action = 'contact';
  }

  // Normalize product_ids: support both old [123, 456] and new [{"product_id":123,"variation_id":456}] formats
  $product_requests = [];
  foreach ($raw_product_ids as $item) {
    if (is_array($item) && isset($item['product_id'])) {
      $product_requests[] = [
        'product_id'   => (int) $item['product_id'],
        'variation_id' => isset($item['variation_id']) ? (int) $item['variation_id'] : 0,
      ];
    } elseif (is_numeric($item)) {
      $product_requests[] = [
        'product_id'   => (int) $item,
        'variation_id' => 0,
      ];
    }
  }

  // 7. If refine or both, resolve product IDs to full product data
  $new_products = [];
  if (in_array($action, ['refine', 'both'], true) && !empty($product_requests)) {
    foreach ($product_requests as $req) {
      $found = sapi_guide_find_product_by_id($all_products, $req['product_id']);
      if ($found) {
        // If Claude specified a variation_id, override image/price/labels with that variation
        if ($req['variation_id'] && !empty($found['variations'])) {
          foreach ($found['variations'] as $v) {
            if ($v['variation_id'] === $req['variation_id']) {
              if ($v['image_url']) {
                $found['image'] = $v['image_url'];
                $found['image_alt'] = $found['title'] . ' - ' . trim($v['essence'] . ' ' . $v['taille']);
              }
              if ($v['price']) {
                $found['price'] = $v['price'];
              }
              $found['variation_label'] = $v['essence'];
              $found['size_label'] = $v['taille'];
              break;
            }
          }
        }
        // Strip variations data before sending to frontend
        unset($found['variations'], $found['best_variation_id']);
        $new_products[] = $found;
      }
    }
    // If Claude returned IDs but none resolved, fallback to contact
    if (empty($new_products)) {
      $action = 'contact';
    }
  }

  // 8. Build updated conversation
  $updated_conversation = array_merge(
    $conversation,
    [
      ['role' => 'user', 'content' => $user_message],
      ['role' => 'assistant', 'content' => $recommendation],
    ]
  );

  // 9. Log refine
  $refine_session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
  if ($refine_session_id) {
    $refine_pids = array_map(function($p) { return $p['id']; }, $new_products);
    sapi_guide_log_refine($refine_session_id, $user_message, $recommendation, $refine_pids);
  }

  // 10. Send response
  wp_send_json_success([
    'action'       => $action,
    'ai_text'      => $recommendation,
    'products'     => $new_products,
    'conversation' => $updated_conversation,
  ]);
}

/* ═══════════════════════════════════════════════════════════
   ROBIN CONSEILLER V2 — Endpoint per-step AI conseil
═══════════════════════════════════════════════════════════ */
add_action('wp_ajax_sapi_robin_conseil_step', 'sapi_ajax_robin_conseil_step');
add_action('wp_ajax_nopriv_sapi_robin_conseil_step', 'sapi_ajax_robin_conseil_step');

function sapi_ajax_robin_conseil_step() {
  // 1. Nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
  }

  // 2. Honeypot
  if (!empty($_POST['guide_website'])) {
    wp_send_json_error(['message' => 'Erreur']);
  }

  // 3. Rate limit
  $ai_allowed = sapi_guide_check_rate_limit();

  // 4. Parse inputs
  $step_id = isset($_POST['step_id']) ? sanitize_key($_POST['step_id']) : '';
  $answers = [];
  if (!empty($_POST['answers'])) {
    $raw = json_decode(sanitize_text_field(wp_unslash($_POST['answers'])), true);
    if (is_array($raw)) {
      foreach ($raw as $k => $v) {
        $answers[sanitize_key($k)] = sanitize_text_field($v);
      }
    }
  }

  $opening_context = isset($_POST['opening_context']) ? sanitize_key($_POST['opening_context']) : 'bandeau';
  $context_data = [];
  if (!empty($_POST['context_data'])) {
    $cd = json_decode(sanitize_text_field(wp_unslash($_POST['context_data'])), true);
    if (is_array($cd)) {
      foreach ($cd as $k => $v) {
        $context_data[sanitize_key($k)] = sanitize_text_field($v);
      }
    }
  }

  $user_message = isset($_POST['user_message']) ? sanitize_textarea_field(wp_unslash($_POST['user_message'])) : '';

  if (empty($step_id)) {
    wp_send_json_error(['message' => 'step_id manquant']);
  }

  // 5. Si pas d'IA (rate limit), renvoyer une réponse vide
  if (!$ai_allowed) {
    wp_send_json_success([
      'conseil_text' => null,
      'link_url'     => null,
      'link_label'   => null,
    ]);
  }

  // 6. Construire le prompt et appeler Claude
  $system_prompt = sapi_robin_build_step_prompt($step_id, $answers, $opening_context, $context_data, $user_message);

  // Message user contextuel
  $user_prompt = '';
  if (!empty($user_message)) {
    $user_prompt = $user_message;
  } elseif ($step_id === 'recommendation') {
    $user_prompt = 'Voici mes réponses complètes. Recommande-moi des luminaires précis.';
  } elseif ($step_id === 'product_page') {
    $product_name = isset($context_data['product_name']) ? $context_data['product_name'] : 'ce luminaire';
    $user_prompt = 'Je regarde ' . $product_name . '. Qu\'en penses-tu par rapport à mon projet ?';
  } else {
    // Step normal — le dernier choix fait
    $last_answer = isset($answers[$step_id]) ? $answers[$step_id] : '';
    $user_prompt = $last_answer
      ? 'J\'ai répondu "' . $last_answer . '" à la question sur ' . $step_id . '. Donne-moi ton conseil.'
      : 'Donne-moi ton conseil pour cette étape.';
  }

  $result = sapi_robin_call_claude_step($system_prompt, $user_prompt);

  if (!$result) {
    wp_send_json_success([
      'conseil_text' => null,
      'link_url'     => null,
      'link_label'   => null,
    ]);
  }

  wp_send_json_success($result);
}

/**
 * Robin V2 — Build system prompt for a single step.
 */
function sapi_robin_build_step_prompt($step_id, $answers, $opening_context, $context_data, $user_message) {
  $theme_dir = get_template_directory();

  // Load prompt files
  $ton      = @file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt') ?: '';
  $savoir   = @file_get_contents($theme_dir . '/assets/guide-prompt-savoir.txt') ?: '';
  $regles   = @file_get_contents($theme_dir . '/assets/guide-prompt-regles.txt') ?: '';
  $exemples = @file_get_contents($theme_dir . '/assets/guide-prompt-exemples.txt') ?: '';

  // Load full catalog
  require_once $theme_dir . '/inc/guide-data.php';
  $all_products = sapi_guide_query_all_products($answers);

  $catalog_lines = [];
  foreach ($all_products as $p) {
    $line = '- ' . $p['title'];
    $line .= ' | Catégorie: ' . ($p['category_label'] ?? '');
    $line .= ' | Format: ' . ($p['format'] ?? '');
    $line .= ' | Ampoule: ' . ($p['type_ampoule'] ?? '');
    if (!empty($p['variation_label'])) {
      $line .= ' | Essence recommandée: ' . $p['variation_label'];
    }
    $line .= ' | Ventes: ' . ($p['total_sales'] ?? 0);
    $line .= ' | ID: ' . $p['id'];
    $line .= ' | URL: ' . ($p['permalink'] ?? '');

    if (!empty($p['variations'])) {
      $vars = [];
      foreach ($p['variations'] as $v) {
        $vars[] = $v['essence'] . ' ' . ($v['taille'] ?? '') . ' (var:' . $v['variation_id'] . ')';
      }
      $line .= ' | Variations: ' . implode(', ', $vars);
    }

    $catalog_lines[] = $line;
  }

  // Build answers summary
  $answers_text = '';
  $label_map = [
    'piece' => 'Pièce', 'taille' => 'Taille', 'taille_escalier' => 'Type escalier',
    'eclairage' => 'Éclairage', 'sortie' => 'Installation', 'hauteur' => 'Hauteur plafond',
    'table' => 'Au-dessus table/îlot', 'style' => 'Style',
  ];
  foreach ($answers as $k => $v) {
    $label = isset($label_map[$k]) ? $label_map[$k] : $k;
    $answers_text .= '- ' . $label . ' : ' . $v . "\n";
  }

  // Compose system prompt
  $prompt = $ton . "\n\n" . $savoir . "\n\n" . $regles . "\n\n";

  $prompt .= "EXEMPLES DE CONSEILS PAR ÉTAPE (pour le ton et la direction) :\n";
  $prompt .= $exemples . "\n\n";

  $prompt .= "CATALOGUE COMPLET DES LUMINAIRES :\n";
  $prompt .= implode("\n", $catalog_lines) . "\n\n";

  if (!empty($answers_text)) {
    $prompt .= "RÉPONSES DU CLIENT JUSQU'À PRÉSENT :\n" . $answers_text . "\n";
  }

  $prompt .= "CONTEXTE D'OUVERTURE : " . $opening_context . "\n";
  if (!empty($context_data)) {
    $prompt .= "DONNÉES CONTEXTUELLES : " . wp_json_encode($context_data) . "\n";
  }
  $prompt .= "ÉTAPE ACTUELLE : " . $step_id . "\n\n";

  // Output format — différent selon texte libre ou pas
  $is_free_text = !empty($user_message);

  $prompt .= "FORMAT DE RÉPONSE (JSON strict, pas de markdown, pas de code fences) :\n";

  if ($is_free_text) {
    $prompt .= "Le client a écrit un message libre. Analyse ce qu'il dit et réponds.\n";
    $prompt .= "{\n";
    $prompt .= '  "conseil_text": "Ta réponse personnalisée (2-5 phrases, style Robin)",' . "\n";
    $prompt .= '  "link_url": "/nos-creations/suspensions/" ou null,' . "\n";
    $prompt .= '  "link_label": "Voir les suspensions" ou null,' . "\n";
    $prompt .= '  "answered_steps": { "piece": "cuisine", "taille": "petite" } ou {} si rien déduit,' . "\n";
    $prompt .= '  "suggested_buttons": [' . "\n";
    $prompt .= '    { "label": "Sortie plafond", "slug": "plafond", "step_id": "sortie" }' . "\n";
    $prompt .= '  ] ou [] si pas pertinent,' . "\n";
    $prompt .= '  "next_step_id": "sortie" ou "hors_parcours" ou null' . "\n";
    $prompt .= "}\n\n";

    $prompt .= "RÈGLES TEXTE LIBRE :\n";
    $prompt .= "- Analyse le message du client et déduis les réponses aux questions du questionnaire.\n";
    $prompt .= "- answered_steps : les step_id → slug que tu as pu déduire du message. Utilise UNIQUEMENT les slugs valides des étapes du questionnaire.\n";

    // Liste des slugs valides par étape
    $prompt .= "- Slugs valides :\n";
    require_once get_template_directory() . '/inc/guide-data.php';
    $all_steps = sapi_guide_get_steps();
    foreach ($all_steps as $s) {
      $slugs = array_map(function($c) { return $c['slug']; }, $s['choices']);
      $prompt .= '  - ' . $s['id'] . ' : ' . implode(', ', $slugs) . "\n";
    }

    $prompt .= "- next_step_id : la prochaine étape logique du questionnaire, ou 'hors_parcours' si le message sort du cadre.\n";
    $prompt .= "- suggested_buttons : boutons à proposer au client pour continuer. Chaque bouton a un label, un slug et un step_id.\n";
    $prompt .= "- Si le message est une question hors questionnaire (livraison, prix, sur mesure...), réponds et mets next_step_id à 'hors_parcours'.\n";
  } else {
    $prompt .= "{\n";
    $prompt .= '  "conseil_text": "Ton conseil personnalisé pour cette étape (2-4 phrases, style citation Robin)",' . "\n";
    $prompt .= '  "link_url": "/nos-creations/suspensions/" ou null si pas pertinent,' . "\n";
    $prompt .= '  "link_label": "Voir les suspensions" ou null' . "\n";
    $prompt .= "}\n\n";
  }

  $prompt .= "RÈGLES IMPORTANTES :\n";
  $prompt .= "- Le conseil_text doit être personnel et adapté.\n";
  $prompt .= "- Ne répète pas la question, donne directement le conseil.\n";
  $prompt .= "- Le link_url doit pointer vers une page existante du site (catégorie, page nos-créations, etc.) ou null.\n";
  $prompt .= "- Pas de markdown. Texte brut uniquement.\n";
  $prompt .= "- Pas de guillemets « » dans le texte (ils sont ajoutés côté front).\n";

  return $prompt;
}

/**
 * Robin V2 — Call Claude API with custom user message.
 */
function sapi_robin_call_claude_step($system_prompt, $user_message) {
  $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
  if (empty($api_key)) {
    return null;
  }

  $body = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 512,
    'system'     => $system_prompt,
    'messages'   => [
      ['role' => 'user', 'content' => $user_message],
    ],
  ];

  $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
    'timeout' => 30,
    'headers' => [
      'Content-Type'      => 'application/json',
      'x-api-key'         => $api_key,
      'anthropic-version'  => '2023-06-01',
    ],
    'body' => wp_json_encode($body),
  ]);

  if (is_wp_error($response)) {
    error_log('Robin V2 Claude API error: ' . $response->get_error_message());
    return null;
  }

  $status   = wp_remote_retrieve_response_code($response);
  $raw_body = wp_remote_retrieve_body($response);

  if ($status !== 200) {
    error_log('Robin V2 Claude API HTTP ' . $status . ': ' . $raw_body);
    return null;
  }

  $data = json_decode($raw_body, true);
  if (!isset($data['content'][0]['text'])) {
    return null;
  }

  $text = $data['content'][0]['text'];
  $text = preg_replace('/^```json\s*/i', '', trim($text));
  $text = preg_replace('/\s*```$/i', '', $text);

  $parsed = json_decode(trim($text), true);
  if (!$parsed || !isset($parsed['conseil_text'])) {
    return ['conseil_text' => $text, 'link_url' => null, 'link_label' => null];
  }

  return $parsed;
}

/**
 * Step 1 → WooCommerce product categories
 */
function sapi_guide_get_categories(array $answers) {
  $sortie    = isset($answers['sortie'])    ? $answers['sortie']    : '';
  $piece     = isset($answers['piece'])     ? $answers['piece']     : '';
  $eclairage = isset($answers['eclairage']) ? $answers['eclairage'] : '';

  // Éclairage secondaire → pool limité, affiné par sortie
  if ($eclairage === 'secondaire') {
    $pool = ['lampadaires', 'lampesaposer']; // default (NSP) : pas d'appliques
    if ($sortie === 'plafond') {
      $pool = ['suspensions'];
    } elseif ($sortie === 'mur') {
      $pool = ['appliques'];
    } elseif ($sortie === 'pas-de-sortie') {
      $pool = ['lampadaires', 'lampesaposer', 'appliques'];
    }
    if ($piece === 'cuisine') {
      $pool = array_values(array_diff($pool, ['lampesaposer']));
    }
    return $pool;
  }

  switch ($sortie) {
    case 'plafond':
      $cats = ['suspensions'];
      break;
    case 'mur':
      $cats = ['appliques'];
      break;
    case 'pas-de-sortie':
      $cats = ['lampadaires', 'lampesaposer', 'appliques'];
      break;
    default: // "ne-sais-pas" → pas d'appliques (nécessite sortie mur)
      $cats = ['suspensions', 'lampadaires', 'lampesaposer'];
  }

  // Règle A : jamais de lampe à poser en cuisine
  if ($piece === 'cuisine') {
    $cats = array_values(array_diff($cats, ['lampesaposer']));
  }

  return $cats;
}

/**
 * Get ampoule type filter based on room
 */
function sapi_guide_get_ampoule_filter($piece, $taille = '') {
  switch ($piece) {
    case 'cuisine':
    case 'bureau':
      if ($taille === 'grande') {
        return null; // grande pièce : tous les types OK
      }
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

  // Format vertical : exclu par défaut pour suspensions, SAUF escalier ou (petite + haute)
  $piece   = isset($answers['piece'])   ? $answers['piece']   : '';
  $taille  = isset($answers['taille'])  ? $answers['taille']  : '';
  $hauteur = isset($answers['hauteur']) ? $answers['hauteur'] : '';

  $eclairage = isset($answers['eclairage']) ? $answers['eclairage'] : '';

  $allow_vertical = (
    $eclairage === 'grappe' ||
    $piece === 'escalier' ||
    ($piece === 'entree' && in_array($hauteur, ['grande', 'confortable'], true)) ||
    ($taille === 'petite' && in_array($hauteur, ['grande', 'confortable'], true))
  );

  if (in_array('suspensions', $categories) && !$allow_vertical) {
    $tax_query[] = [
      'taxonomy' => 'pa_format',
      'field'    => 'slug',
      'terms'    => ['vertical'],
      'operator' => 'NOT IN',
    ];
  }

  // Règle B : escalier → exclure format horizontal (préférer boule ou vertical)
  if ($piece === 'escalier' && in_array('suspensions', $categories)) {
    $tax_query[] = [
      'taxonomy' => 'pa_format',
      'field'    => 'slug',
      'terms'    => ['horizontal'],
      'operator' => 'NOT IN',
    ];
  }

  // Ampoule filter based on room (reuses $piece from above)
  $ampoule_filter = sapi_guide_get_ampoule_filter($piece, $taille);
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
  if (!$query->have_posts() && !$allow_vertical) {
    wp_reset_postdata();
    $args['tax_query'] = [
      ['taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $categories, 'operator' => 'IN'],
    ];
    $query = new WP_Query($args);
    $fallback_notes[] = "ATTENTION : le filtre de format a aussi été relâché. Des formats normalement exclus peuvent apparaître.";
  }

  $results = sapi_guide_collect_results($query, $answers);

  // Fallback: if grande pièce excluded all products via ≤2 tailles, retry without size filter
  if (empty($results) && $taille === 'grande' && $query->have_posts()) {
    $query->rewind_posts();
    $answers_no_taille = $answers;
    unset($answers_no_taille['taille']);
    $results = sapi_guide_collect_results($query, $answers_no_taille);
    $fallback_notes[] = "ATTENTION : les produits proposés n'ont pas tous 3 tailles ou plus. La recommandation de taille peut ne pas correspondre parfaitement à une grande pièce.";
  }

  wp_reset_postdata();
  return ['products' => $results, 'fallback_notes' => $fallback_notes];
}

/**
 * Query complementary products for grande pièce
 */
function sapi_guide_query_complements(array $answers, array $main_categories) {
  // Complements: jamais de suspension (nécessite sortie plafond dédiée)
  $complement_pool = ['lampadaires', 'lampesaposer', 'appliques'];
  $complement_cats = array_values(array_diff($complement_pool, $main_categories));

  if (empty($complement_cats)) {
    $complement_cats = ['lampadaires', 'lampesaposer'];
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
 * Query ALL published products (full catalog, no filters).
 * Used for refinement calls so Claude can pick from any product.
 */
function sapi_guide_query_all_products($answers = []) {
  $args = [
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 50,
    'orderby'        => 'menu_order date',
    'order'          => 'ASC',
  ];
  $query = new WP_Query($args);
  $results = sapi_guide_collect_results($query, $answers, true);
  wp_reset_postdata();
  return $results;
}

/**
 * Build a human-readable description of the filters applied during the first recommendation.
 * Sent to Claude during refinement so it understands what was filtered and why.
 */
function sapi_guide_build_filter_context(array $answers, array $categories, array $fallback_notes = []) {
  $parts = [];
  $sortie    = isset($answers['sortie'])    ? $answers['sortie']    : '';
  $piece     = isset($answers['piece'])     ? $answers['piece']     : '';
  $taille    = isset($answers['taille'])    ? $answers['taille']    : '';
  $hauteur   = isset($answers['hauteur'])   ? $answers['hauteur']   : '';
  $style     = isset($answers['style'])     ? $answers['style']     : '';
  $eclairage = isset($answers['eclairage']) ? $answers['eclairage'] : '';
  $table     = isset($answers['table'])     ? $answers['table']     : '';

  $parts[] = 'Catégories filtrées : ' . implode(', ', $categories);

  if ($eclairage === 'secondaire') {
    $parts[] = 'Éclairage secondaire demandé (complémentaire).';
  }

  if ($sortie === 'plafond') {
    $parts[] = 'Filtré sur suspensions car sortie plafond.';
  } elseif ($sortie === 'mur') {
    $parts[] = 'Filtré sur appliques car sortie mur.';
  } elseif ($sortie === 'pas-de-sortie') {
    $parts[] = 'Filtré sur lampadaires, lampes à poser et appliques car prise 230V (pas de sortie électrique dédiée).';
  } elseif ($sortie === 'ne-sais-pas') {
    $parts[] = 'Sortie inconnue : suspensions, lampadaires et lampes à poser proposés.';
  }

  $ampoule_filter = sapi_guide_get_ampoule_filter($piece, $taille);
  if ($ampoule_filter) {
    $parts[] = 'Filtre ampoule : ' . implode(' ou ', $ampoule_filter) . ' (pièce : ' . $piece . ').';
  }

  if ($sortie === 'plafond' && $hauteur === 'standard' && $table === 'non') {
    $parts[] = 'Format vertical exclu (plafond standard sans table en dessous).';
  }

  if ($style === 'moderne') {
    $parts[] = 'Essence conseillée : Peuplier (style moderne).';
  } elseif ($style === 'ancien') {
    $parts[] = 'Essence conseillée : Okoumé (style ancien/chaleureux).';
  }

  if ($piece === 'cuisine') {
    $parts[] = 'Lampe à poser exclue (cuisine).';
  }

  if ($taille === 'grande') {
    $parts[] = 'Grande pièce : produits avec peu de tailles disponibles exclus.';
  }

  if (!empty($fallback_notes)) {
    $parts[] = 'Notes de fallback (filtres relâchés) : ' . implode(' ', $fallback_notes);
  }

  return implode("\n", $parts);
}

/**
 * Process query results into product data arrays
 */
function sapi_guide_collect_results($query, array $answers, $skip_exclusions = false) {
  // Determine preferred essence from style answer
  $style = isset($answers['style']) ? $answers['style'] : '';
  $preferred_essence = '';
  if ($style === 'moderne') {
    $preferred_essence = 'peuplier';
  } elseif ($style === 'ancien') {
    $preferred_essence = 'okoume';
  }

  // Determine preferred size index from room size
  $taille_answer = isset($answers['taille']) ? $answers['taille'] : '';
  $size_index = 0; // petite = smallest
  if ($taille_answer === 'moyenne') $size_index = 1;
  if ($taille_answer === 'grande') $size_index = 2;

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
    $size_label = '';

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

    // Match preferred essence + size variation
    if ($product->is_type('variable')) {
      $variations = $product->get_available_variations();

      // Determine preferred taille slug for this product
      $preferred_taille_slug = '';
      if ($taille_answer) {
        $taille_terms = wc_get_product_terms($product->get_id(), 'pa_taille', ['orderby' => 'menu_order']);

        // Grande pièce + suspension : exclure les produits avec 2 tailles ou moins (sauf en refine)
        if (!$skip_exclusions && $taille_answer === 'grande' && !empty($taille_terms) && count($taille_terms) <= 2 && array_intersect($cat_slugs, ['suspensions'])) {
          continue;
        }

        if (!empty($taille_terms)) {
          $idx = min($size_index, count($taille_terms) - 1);
          $preferred_taille_slug = $taille_terms[$idx]->slug;
          $size_label = $taille_terms[$idx]->name;
        }
      }

      // Find best variation matching both essence + taille
      $best_var = null;
      $fallback_essence = null;
      $fallback_taille = null;

      foreach ($variations as $var) {
        $mat = isset($var['attributes']['attribute_pa_materiau']) ? $var['attributes']['attribute_pa_materiau'] : '';
        $tai = isset($var['attributes']['attribute_pa_taille']) ? $var['attributes']['attribute_pa_taille'] : '';

        // Empty attribute = "any" in WooCommerce
        $essence_ok = (!$preferred_essence || $mat === $preferred_essence || $mat === '');
        $taille_ok  = (!$preferred_taille_slug || $tai === $preferred_taille_slug || $tai === '');

        if ($essence_ok && $taille_ok) {
          $best_var = $var;
          break;
        }
        if ($essence_ok && !$fallback_essence) $fallback_essence = $var;
        if ($taille_ok && !$fallback_taille) $fallback_taille = $var;
      }

      if (!$best_var) {
        $best_var = $fallback_essence ?: $fallback_taille;
      }

      if ($best_var) {
        if (!empty($best_var['image_id'])) {
          $image_id = $best_var['image_id'];
        }
        $var_product = wc_get_product($best_var['variation_id']);
        if ($var_product) {
          $price = $var_product->get_price_html();
        }
        if ($preferred_essence) {
          $term = get_term_by('slug', $preferred_essence, 'pa_materiau');
          $variation_label = $term ? $term->name : ucfirst($preferred_essence);
        }
      }

      // Collect ALL variations for refine context
      $all_vars = [];
      foreach ($variations as $var) {
        $mat_slug = isset($var['attributes']['attribute_pa_materiau']) ? $var['attributes']['attribute_pa_materiau'] : '';
        $tai_slug = isset($var['attributes']['attribute_pa_taille']) ? $var['attributes']['attribute_pa_taille'] : '';
        $mat_name = '';
        if ($mat_slug) {
          $mat_term = get_term_by('slug', $mat_slug, 'pa_materiau');
          $mat_name = $mat_term ? $mat_term->name : ucfirst($mat_slug);
        }
        $tai_name = '';
        if ($tai_slug) {
          $tai_term = get_term_by('slug', $tai_slug, 'pa_taille');
          $tai_name = $tai_term ? $tai_term->name : ucfirst($tai_slug);
        }
        $var_img_id = !empty($var['image_id']) ? (int) $var['image_id'] : 0;
        $var_product_obj = wc_get_product($var['variation_id']);
        $all_vars[] = [
          'variation_id' => (int) $var['variation_id'],
          'essence'      => $mat_name,
          'taille'       => $tai_name,
          'image_url'    => $var_img_id ? wp_get_attachment_url($var_img_id) : '',
          'price'        => $var_product_obj ? $var_product_obj->get_price_html() : '',
        ];
      }
    }

    // Ambiance photo for full-width banner (fallback: ambiance_2 → ambiance_1)
    $ambiance_url = '';
    $pid = $product->get_id();
    $ambiance_raw = get_field('ambiance_2', $pid);
    if (!$ambiance_raw) {
      $ambiance_raw = get_field('ambiance_1', $pid);
    }
    if ($ambiance_raw) {
      $ambiance_url = sapi_get_acf_image_url($ambiance_raw, 'full');
    }

    // Hover image (first gallery image for card hover effect)
    $hover_image_url = '';
    $gallery_ids = $product->get_gallery_image_ids();
    if (!empty($gallery_ids)) {
      $hover_image_url = wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail');
    }

    // Category label for card
    $cat_names = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
    $cat_label = !empty($cat_names) ? $cat_names[0] : '';

    $products[] = [
      'id'              => $product->get_id(),
      'title'           => $product->get_name(),
      'price'           => $price,
      'image'           => $image_id ? wp_get_attachment_url($image_id) : '',
      'image_alt'       => $image_id ? get_post_meta($image_id, '_wp_attachment_image_alt', true) : '',
      'hover_image'     => $hover_image_url,
      'permalink'       => get_permalink($product->get_id()),
      'variation_label' => $variation_label,
      'size_label'      => $size_label,
      'categories'      => $cat_slugs,
      'category_label'  => $cat_label,
      'format'          => $format,
      'type_ampoule'    => $ampoule,
      'total_sales'     => (int) $product->get_total_sales(),
      'ambiance'        => $ambiance_url,
      'variations'        => isset($all_vars) ? $all_vars : [],
      'best_variation_id' => isset($best_var) ? (int) $best_var['variation_id'] : 0,
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
 * Pick up to $count products from the filtered list.
 * Normal mode: best seller, newest, 2nd best seller, random.
 * Diversify mode (grappe): one product per format (horizontal, boule, vertical…),
 * prioritized by total_sales within each format.
 */
function sapi_guide_pick_four(array $products, $count = 4, $diversify_format = false) {
  if (count($products) <= $count) {
    return $products;
  }

  // Grappe: pick one product per format, best seller within each
  if ($diversify_format) {
    $by_format = [];
    foreach ($products as $p) {
      $fmt = !empty($p['format']) ? strtolower($p['format']) : 'autre';
      if (!isset($by_format[$fmt])) {
        $by_format[$fmt] = [];
      }
      $by_format[$fmt][] = $p;
    }
    // Sort each format group by sales desc
    foreach ($by_format as &$group) {
      usort($group, function ($a, $b) {
        return $b['total_sales'] - $a['total_sales'];
      });
    }
    unset($group);

    // Pick best seller from each format
    $picked = [];
    foreach ($by_format as $group) {
      $picked[] = $group[0];
      if (count($picked) >= $count) {
        break;
      }
    }

    // If not enough formats, fill from remaining best sellers
    if (count($picked) < $count) {
      $picked_ids = array_map(function ($p) { return $p['id']; }, $picked);
      $remaining = array_filter($products, function ($p) use ($picked_ids) {
        return !in_array($p['id'], $picked_ids);
      });
      usort($remaining, function ($a, $b) {
        return $b['total_sales'] - $a['total_sales'];
      });
      foreach ($remaining as $p) {
        $picked[] = $p;
        if (count($picked) >= $count) {
          break;
        }
      }
    }

    return $picked;
  }

  // Normal mode
  $picked = [];
  $remaining = $products;

  // 1) Best seller
  usort($remaining, function ($a, $b) {
    return $b['total_sales'] - $a['total_sales'];
  });
  $picked[] = array_shift($remaining);

  // 2) Newest (highest ID = most recently created)
  usort($remaining, function ($a, $b) {
    return $b['id'] - $a['id'];
  });
  $picked[] = array_shift($remaining);

  // 3) 2nd best seller (best seller among remaining)
  usort($remaining, function ($a, $b) {
    return $b['total_sales'] - $a['total_sales'];
  });
  $picked[] = array_shift($remaining);

  // 4) Random among remaining (only when picking 4)
  if ($count >= 4 && !empty($remaining)) {
    $picked[] = $remaining[array_rand($remaining)];
  }

  return $picked;
}

/**
 * Build the system prompt for Claude with filtered products and client answers
 */
function sapi_guide_build_system_prompt(array $products_data, array $answers, array $fallback_notes = [], $show_sur_mesure = false) {
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
    $prompt .= "- " . $p['title'] . " | Catégorie : " . implode(', ', $p['categories']) . " | Format : " . $p['format'] . " | Ampoule : " . $p['type_ampoule'];
    if ($p['variation_label']) {
      $prompt .= " | Essence recommandée : " . $p['variation_label'];
    }
    if (!empty($p['size_label'])) {
      $prompt .= " | Taille recommandée : " . $p['size_label'];
    }
    $prompt .= " | Ventes : " . $p['total_sales'] . " | ID : " . $p['id'] . "\n";
  }

  // Réponses du client
  $prompt .= "\nRÉPONSES DU CLIENT :\n";
  $labels = [
    'piece'     => 'Pièce',
    'taille'    => 'Taille de la pièce',
    'eclairage' => 'Type d\'éclairage',
    'sortie'    => 'Sortie électrique',
    'hauteur'   => 'Hauteur sous-plafond',
    'table'     => 'Au-dessus d\'une table',
    'style'     => 'Style intérieur',
  ];
  foreach ($labels as $key => $label) {
    $val = isset($answers[$key]) ? $answers[$key] : 'Non demandé';
    $prompt .= "- " . $label . " : " . $val . "\n";
  }

  if ($show_sur_mesure) {
    $prompt .= "\nINFO CONTEXTE : Une carte \"Création sur mesure\" est affichée à côté des produits. NE mentionne PAS le sur mesure dans le champ \"recommendation\" — utilise le champ \"sur_mesure_text\" à la place.\n";
    $prompt .= "Dans \"sur_mesure_text\", écris un texte court (30 mots max) qui DOIT commencer par \"Par exemple\" ou \"Et pourquoi pas\". Tu proposes une IDÉE ouverte, pas une solution. Tu NE décides PAS à la place du client. Exemple de ton : \"Par exemple, Robin pourrait imaginer…\" ou \"Et pourquoi pas quelque chose de…\". Reste rêveur et suggestif. L'objectif : ouvrir une porte, donner envie d'en discuter avec Robin.\n";
  }

  // Format de réponse JSON
  $prompt .= "\nTEXTES À GÉNÉRER :\n";
  $prompt .= "1. \"conseils_text\" (~150-200 mots) : Conseils CONCRETS, TECHNIQUES et FACTUELS adaptés au projet du client. Type d'éclairage selon la pièce, hauteur de suspension idéale, nombre de points lumineux, puissance recommandée, température de couleur, type d'ampoule, etc. NE mentionne AUCUN nom de modèle — le client verra sa sélection personnalisée sur une autre page. Reste purement sur le conseil technique et l'expertise artisanale.\n";
  $prompt .= "2. \"selection_text\" (~60-80 mots) : Texte pour la page Nos Créations. Justifie le choix de ces modèles précis pour le projet du client. Explique pourquoi chaque type de luminaire recommandé correspond à sa situation (pièce, hauteur, style…). Plus technique et factuel que le texte conseils.\n";
  if ($show_sur_mesure) {
    $prompt .= "3. \"sur_mesure_text\" (30 mots max) : DOIT commencer par \"Par exemple\" ou \"Et pourquoi pas\". Propose une IDÉE ouverte de création sur mesure, pas une solution. Reste rêveur et suggestif.\n";
  }
  $prompt .= "\nFORMAT DE RÉPONSE (JSON strict, sans commentaires, sans markdown) :\n";
  $prompt .= "{\n";
  $prompt .= "  \"conseils_text\": \"...\",\n";
  $prompt .= "  \"selection_text\": \"...\"";
  if ($show_sur_mesure) {
    $prompt .= ",\n  \"sur_mesure_text\": \"...\"";
  }
  $prompt .= "\n}\n";

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
  if (!$parsed || (!isset($parsed['conseils_text']) && !isset($parsed['recommendation']))) {
    // If Claude didn't return valid JSON, use the raw text as conseils_text
    return [
      'conseils_text' => $text,
    ];
  }

  return $parsed;
}

/**
 * ── Refinement: Build system prompt with full catalog ──
 * Used when the client sends a follow-up message after seeing initial results.
 */
function sapi_guide_build_refine_prompt(array $all_products, array $answers, $filter_context, array $current_product_ids) {
  $theme_dir = get_stylesheet_directory();
  $refine_rules = file_get_contents($theme_dir . '/assets/guide-prompt-refine.txt');
  $ton          = file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt');

  $prompt = $refine_rules . "\n\n" . $ton . "\n\n";

  // Full catalog
  $prompt .= "CATALOGUE COMPLET (tous les luminaires disponibles) :\n";
  foreach ($all_products as $p) {
    $is_current = in_array((int) $p['id'], $current_product_ids, true) ? ' [ACTUELLEMENT AFFICHÉ]' : '';
    $prompt .= '- ' . $p['title']
      . ' | ID : ' . $p['id']
      . ' | Catégorie : ' . implode(', ', $p['categories'])
      . ' | Format : ' . $p['format']
      . ' | Ampoule : ' . $p['type_ampoule']
      . ' | Ventes : ' . $p['total_sales']
      . $is_current . "\n";

    // List all variations
    if (!empty($p['variations'])) {
      $var_parts = [];
      foreach ($p['variations'] as $v) {
        $label = trim($v['essence'] . ' ' . $v['taille']);
        $var_parts[] = $label . ' (var:' . $v['variation_id'] . ')';
      }
      $prompt .= '  Variations : ' . implode(', ', $var_parts) . "\n";
      if (!empty($p['best_variation_id'])) {
        $best_label = '';
        foreach ($p['variations'] as $v) {
          if ($v['variation_id'] === $p['best_variation_id']) {
            $best_label = trim($v['essence'] . ' ' . $v['taille']);
            break;
          }
        }
        $prompt .= '  [RECOMMANDÉ : var:' . $p['best_variation_id'] . ($best_label ? ' ' . $best_label : '') . "]\n";
      }
    }
  }

  // Quiz answers
  $prompt .= "\nRÉPONSES INITIALES DU CLIENT AU QUESTIONNAIRE :\n";
  $labels = [
    'piece'     => 'Pièce',
    'taille'    => 'Taille de la pièce',
    'eclairage' => 'Type d\'éclairage',
    'sortie'    => 'Sortie électrique',
    'hauteur'   => 'Hauteur sous-plafond',
    'table'     => 'Au-dessus d\'une table',
    'style'     => 'Style intérieur',
  ];
  foreach ($labels as $key => $label) {
    $val = isset($answers[$key]) ? $answers[$key] : 'Non demandé';
    $prompt .= '- ' . $label . ' : ' . $val . "\n";
  }

  // Filter context from first round
  $prompt .= "\nFILTRES APPLIQUÉS LORS DE LA PREMIÈRE RECOMMANDATION :\n";
  $prompt .= $filter_context . "\n";

  // Currently displayed products
  $prompt .= "\nPRODUITS ACTUELLEMENT AFFICHÉS AU CLIENT (IDs) : " . implode(', ', $current_product_ids) . "\n";

  // Response format
  $prompt .= "\nFORMAT DE RÉPONSE (JSON strict, sans commentaires) :\n";
  $prompt .= "{\n";
  $prompt .= '  "action": "refine" | "contact" | "both",' . "\n";
  $prompt .= '  "recommendation": "Texte de réponse pour le client (max 80 mots)...",' . "\n";
  $prompt .= '  "product_ids": [{"product_id": 123, "variation_id": 456}, {"product_id": 789}]' . "\n";
  $prompt .= "}\n";
  $prompt .= "Note : product_ids est obligatoire si action = \"refine\" ou \"both\". Vide [] si action = \"contact\".\n";
  $prompt .= "Chaque entrée de product_ids DOIT contenir un product_id. Le variation_id est optionnel : utilise-le pour recommander une essence/taille spécifique. Si omis, la variation recommandée initialement sera utilisée.\n";

  return $prompt;
}

/**
 * ── Refinement: Call Claude API with conversation history ──
 * Similar to sapi_guide_call_claude() but supports multi-turn messages.
 */
function sapi_guide_call_claude_refine($system_prompt, array $messages) {
  $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
  if (empty($api_key)) {
    return null;
  }

  $body = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 1024,
    'system'     => $system_prompt,
    'messages'   => $messages,
  ];

  $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
    'timeout' => 30,
    'headers' => [
      'Content-Type'      => 'application/json',
      'x-api-key'         => $api_key,
      'anthropic-version' => '2023-06-01',
    ],
    'body' => wp_json_encode($body),
  ]);

  if (is_wp_error($response)) {
    error_log('Sapi Guide Refine API error: ' . $response->get_error_message());
    return null;
  }

  $status   = wp_remote_retrieve_response_code($response);
  $raw_body = wp_remote_retrieve_body($response);

  if ($status !== 200) {
    error_log('Sapi Guide Refine API HTTP ' . $status . ': ' . $raw_body);
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
  if (!$parsed || !isset($parsed['action'])) {
    // Fallback: treat as contact intent with raw text
    return [
      'action'         => 'contact',
      'recommendation' => $text,
      'product_ids'    => [],
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
 * NEWSLETTER BREVO — AJAX subscription
 * ============================================================
 */
add_action('wp_ajax_sapi_newsletter_subscribe', 'sapi_newsletter_subscribe');
add_action('wp_ajax_nopriv_sapi_newsletter_subscribe', 'sapi_newsletter_subscribe');

function sapi_newsletter_subscribe() {
    check_ajax_referer('sapi_newsletter_nonce', 'nonce');

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Adresse email invalide.']);
    }

    $api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
    if (!$api_key) {
        wp_send_json_error(['message' => 'Configuration newsletter manquante.']);
    }

    $response = wp_remote_post('https://api.brevo.com/v3/contacts', [
        'headers' => [
            'api-key'      => $api_key,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ],
        'body' => wp_json_encode([
            'email'            => $email,
            'listIds'          => [6],
            'updateEnabled'    => true,
        ]),
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Erreur de connexion. Réessayez plus tard.']);
    }

    $code = wp_remote_retrieve_response_code($response);

    if ($code === 201 || $code === 204) {
        wp_send_json_success(['message' => 'Bienvenue dans la famille Sâpi !']);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if ($code === 400 && isset($body['message']) && strpos($body['message'], 'already exist') !== false) {
        wp_send_json_success(['message' => 'Vous êtes déjà inscrit(e) !']);
    }

    wp_send_json_error(['message' => 'Une erreur est survenue. Réessayez plus tard.']);
}

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
      [
        'key'          => 'field_psm_photo_2',
        'label'        => 'Photo 2',
        'name'         => 'photo_2',
        'type'         => 'image',
        'return_format' => 'array',
        'preview_size'  => 'medium',
        'instructions'  => 'Photo supplémentaire (optionnel)',
      ],
      [
        'key'          => 'field_psm_photo_3',
        'label'        => 'Photo 3',
        'name'         => 'photo_3',
        'type'         => 'image',
        'return_format' => 'array',
        'preview_size'  => 'medium',
        'instructions'  => 'Photo supplémentaire (optionnel)',
      ],
      [
        'key'          => 'field_psm_photo_4',
        'label'        => 'Photo 4',
        'name'         => 'photo_4',
        'type'         => 'image',
        'return_format' => 'array',
        'preview_size'  => 'medium',
        'instructions'  => 'Photo supplémentaire (optionnel)',
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

/*
 * ═══════════════════════════════════════════════════════════════════
 * GUIDE LUMINAIRE — LOGGING & ADMIN
 * Enregistre chaque session du guide luminaire en base de données.
 * ═══════════════════════════════════════════════════════════════════
 */

/**
 * Create the guide logs table on theme switch (activation)
 */
function sapi_guide_create_logs_table() {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';
  $charset = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS $table (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    session_id varchar(36) NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    piece varchar(50) DEFAULT '',
    taille varchar(50) DEFAULT '',
    eclairage varchar(50) DEFAULT '',
    sortie varchar(50) DEFAULT '',
    hauteur varchar(50) DEFAULT '',
    table_reponse varchar(50) DEFAULT '',
    style varchar(50) DEFAULT '',
    products_shown text DEFAULT '',
    ai_text text DEFAULT '',
    refine_count int(11) DEFAULT 0,
    refine_messages text DEFAULT '',
    contact_sent tinyint(1) DEFAULT 0,
    contact_name varchar(100) DEFAULT '',
    contact_email varchar(100) DEFAULT '',
    user_agent varchar(500) DEFAULT '',
    ip_address varchar(45) DEFAULT '',
    device_type varchar(20) DEFAULT '',
    referrer varchar(500) DEFAULT '',
    location varchar(200) DEFAULT '',
    PRIMARY KEY (id),
    KEY session_id (session_id)
  ) $charset;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
add_action('after_switch_theme', 'sapi_guide_create_logs_table');

// Also create on init if table doesn't exist yet (for existing installs)
// And add missing columns for existing tables
function sapi_guide_maybe_create_table() {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';
  if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
    sapi_guide_create_logs_table();
  } else {
    // Add columns if they don't exist yet (migration)
    $columns = $wpdb->get_col("DESCRIBE $table", 0);
    if (!in_array('ip_address', $columns, true)) {
      $wpdb->query("ALTER TABLE $table ADD COLUMN ip_address varchar(45) DEFAULT '' AFTER user_agent");
    }
    if (!in_array('device_type', $columns, true)) {
      $wpdb->query("ALTER TABLE $table ADD COLUMN device_type varchar(20) DEFAULT '' AFTER ip_address");
    }
    if (!in_array('referrer', $columns, true)) {
      $wpdb->query("ALTER TABLE $table ADD COLUMN referrer varchar(500) DEFAULT '' AFTER device_type");
    }
    if (!in_array('location', $columns, true)) {
      $wpdb->query("ALTER TABLE $table ADD COLUMN location varchar(200) DEFAULT '' AFTER referrer");
    }
  }
}
add_action('admin_init', 'sapi_guide_maybe_create_table');

/**
 * Log the initial quiz results
 */
function sapi_guide_log_initial($session_id, $answers, $product_ids, $ai_text) {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';

  $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
  $ip_address = isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    ? sanitize_text_field(wp_unslash(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]))
    : (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '');
  $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';

  // Detect device type + browser from user-agent
  $device_type = 'Desktop';
  $ua_lower = strtolower($user_agent);
  if (preg_match('/mobile|android.*mobile|iphone|ipod|windows phone/i', $ua_lower)) {
    $device_type = 'Mobile';
  } elseif (preg_match('/tablet|ipad|android(?!.*mobile)/i', $ua_lower)) {
    $device_type = 'Tablette';
  }

  // Detect browser
  $browser = '';
  if (preg_match('/Edg\//i', $user_agent)) {
    $browser = 'Edge';
  } elseif (preg_match('/OPR\//i', $user_agent)) {
    $browser = 'Opera';
  } elseif (preg_match('/Chrome\//i', $user_agent) && !preg_match('/Edg\//i', $user_agent)) {
    $browser = 'Chrome';
  } elseif (preg_match('/Safari\//i', $user_agent) && !preg_match('/Chrome\//i', $user_agent)) {
    $browser = 'Safari';
  } elseif (preg_match('/Firefox\//i', $user_agent)) {
    $browser = 'Firefox';
  }
  if ($browser) {
    $device_type .= ' · ' . $browser;
  }

  $wpdb->insert($table, [
    'session_id'     => $session_id,
    'created_at'     => current_time('mysql'),
    'piece'          => isset($answers['piece'])     ? $answers['piece']     : '',
    'taille'         => !empty($answers['taille']) ? $answers['taille'] : (!empty($answers['taille_escalier']) ? $answers['taille_escalier'] : ''),
    'eclairage'      => isset($answers['eclairage']) ? $answers['eclairage'] : '',
    'sortie'         => isset($answers['sortie'])    ? $answers['sortie']    : '',
    'hauteur'        => isset($answers['hauteur'])   ? $answers['hauteur']   : '',
    'table_reponse'  => isset($answers['table'])     ? $answers['table']     : '',
    'style'          => isset($answers['style'])     ? $answers['style']     : '',
    'products_shown' => implode(', ', $product_ids),
    'ai_text'        => $ai_text ?: '',
    'refine_count'   => 0,
    'refine_messages'=> '',
    'contact_sent'   => 0,
    'user_agent'     => mb_substr($user_agent, 0, 500),
    'ip_address'     => $ip_address,
    'device_type'    => $device_type,
    'referrer'       => mb_substr($referrer, 0, 500),
    'location'       => '',
  ]);

  // Resolve geolocation AFTER response is sent (shutdown hook)
  $insert_id = $wpdb->insert_id;
  if ($insert_id && $ip_address && !in_array($ip_address, ['127.0.0.1', '::1'], true)) {
    add_action('shutdown', function() use ($insert_id, $ip_address) {
      sapi_guide_resolve_geolocation($insert_id, $ip_address);
    });
  }
}

/**
 * Resolve IP geolocation via ip-api.com and update the log row.
 * Called on shutdown hook so it never blocks the response to the visitor.
 */
function sapi_guide_resolve_geolocation($row_id, $ip_address) {
  $geo_response = wp_remote_get('http://ip-api.com/json/' . rawurlencode($ip_address) . '?fields=city,regionName,country&lang=fr', [
    'timeout' => 5,
  ]);
  if (is_wp_error($geo_response)) return;

  $geo_data = json_decode(wp_remote_retrieve_body($geo_response), true);
  if (!$geo_data) return;

  $parts = array_filter([
    $geo_data['city'] ?? '',
    $geo_data['regionName'] ?? '',
    $geo_data['country'] ?? '',
  ]);
  $location = implode(', ', $parts);
  if (!$location) return;

  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';
  $wpdb->update($table, ['location' => mb_substr($location, 0, 200)], ['id' => $row_id], ['%s'], ['%d']);
}

/**
 * Log a refine interaction (update existing session row)
 */
function sapi_guide_log_refine($session_id, $user_message, $ai_text, $product_ids) {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';

  $row = $wpdb->get_row($wpdb->prepare("SELECT id, refine_count, refine_messages FROM $table WHERE session_id = %s", $session_id));
  if (!$row) return;

  $existing = $row->refine_messages ? $row->refine_messages : '';
  $new_entry = 'Client: ' . $user_message . "\nRobin: " . $ai_text;
  if (!empty($product_ids)) {
    $new_entry .= ' [produits: ' . implode(', ', $product_ids) . ']';
  }
  $updated = $existing ? $existing . "\n---\n" . $new_entry : $new_entry;

  $wpdb->update($table, [
    'refine_count'    => (int) $row->refine_count + 1,
    'refine_messages' => $updated,
  ], ['id' => $row->id]);
}

/**
 * Log contact form submission (update existing session row)
 */
function sapi_guide_log_contact($session_id, $name, $email) {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';

  $wpdb->update($table, [
    'contact_sent'  => 1,
    'contact_name'  => $name,
    'contact_email' => $email,
  ], ['session_id' => $session_id]);
}

/**
 * ── Admin page : Guide Luminaire Logs ──
 */
function sapi_guide_admin_menu() {
  add_menu_page(
    'Guide Luminaire — Sessions',
    'Guide Luminaire',
    'manage_woocommerce',
    'sapi-guide-logs',
    'sapi_guide_admin_page',
    'dashicons-welcome-learn-more',
    26
  );
}
add_action('admin_menu', 'sapi_guide_admin_menu');

/**
 * CSV Export handler
 */
function sapi_guide_export_csv() {
  if (!isset($_GET['sapi_guide_export']) || $_GET['sapi_guide_export'] !== '1') return;
  if (!current_user_can('manage_woocommerce')) return;
  if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sapi_guide_export')) return;

  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';
  $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=guide-luminaire-sessions-' . wp_date('Y-m-d') . '.csv');

  $out = fopen('php://output', 'w');
  fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel

  fputcsv($out, ['Date', 'IP', 'Localisation', 'Appareil', 'Provenance', 'Pièce', 'Taille', 'Éclairage', 'Sortie', 'Hauteur', 'Table', 'Style', 'Produits affichés', 'Texte IA', 'Nb refines', 'Messages refine', 'Contact envoyé', 'Nom contact', 'Email contact'], ';');

  foreach ($rows as $r) {
    fputcsv($out, [
      $r['created_at'],
      $r['ip_address'] ?? '',
      $r['location'] ?? '',
      $r['device_type'] ?? '',
      $r['referrer'] ?? '',
      $r['piece'],
      $r['taille'],
      $r['eclairage'],
      $r['sortie'],
      $r['hauteur'],
      $r['table_reponse'],
      $r['style'],
      $r['products_shown'],
      $r['ai_text'],
      $r['refine_count'],
      $r['refine_messages'],
      $r['contact_sent'] ? 'Oui' : 'Non',
      $r['contact_name'],
      $r['contact_email'],
    ], ';');
  }
  fclose($out);
  exit;
}
add_action('admin_init', 'sapi_guide_export_csv');

/**
 * Admin page renderer
 */
function sapi_guide_admin_page() {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_guide_logs';

  // Handle delete action
  if (isset($_GET['sapi_guide_delete']) && isset($_GET['_wpnonce'])) {
    $delete_id = (int) $_GET['sapi_guide_delete'];
    if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sapi_guide_delete_' . $delete_id)) {
      $wpdb->delete($table, ['id' => $delete_id], ['%d']);
      echo '<div class="notice notice-success is-dismissible"><p>Session supprimée.</p></div>';
    }
  }

  $per_page = 30;
  $paged = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
  $offset = ($paged - 1) * $per_page;

  $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
  $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset));
  $total_pages = ceil($total / $per_page);

  $export_url = wp_nonce_url(admin_url('admin.php?page=sapi-guide-logs&sapi_guide_export=1'), 'sapi_guide_export');

  ?>
  <div class="wrap">
    <h1>Guide Luminaire — Sessions <span style="font-size:0.6em; color:#999;">(<?php echo esc_html($total); ?> sessions)</span></h1>
    <p>
      <a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">Exporter CSV</a>
    </p>
    <table class="widefat striped" style="margin-top:10px;">
      <thead>
        <tr>
          <th>Date</th>
          <th>Appareil</th>
          <th>Localisation</th>
          <th>Provenance</th>
          <th>Pièce</th>
          <th>Taille</th>
          <th>Sortie</th>
          <th>Style</th>
          <th>Produits</th>
          <th>Refines</th>
          <th>Contact</th>
          <th style="width:50px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)) : ?>
          <tr><td colspan="12" style="text-align:center; color:#999;">Aucune session enregistrée pour le moment.</td></tr>
        <?php else : ?>
          <?php foreach ($rows as $r) : ?>
            <tr>
              <td style="white-space:nowrap;"><?php echo esc_html(wp_date('d/m/Y H:i', strtotime($r->created_at))); ?></td>
              <td style="white-space:nowrap;"><?php echo esc_html($r->device_type ?: '—'); ?></td>
              <td><?php echo esc_html($r->location ?: '—'); ?></td>
              <td>
                <?php if ($r->referrer) :
                  $ref_path = trim(wp_parse_url($r->referrer, PHP_URL_PATH) ?: '', '/');
                  $ref_page_id = url_to_postid($r->referrer);
                  if ($ref_page_id) {
                    $ref_label = get_the_title($ref_page_id);
                  } elseif ($ref_path === '' || $ref_path === 'accueil') {
                    $ref_label = 'Accueil';
                  } elseif (strpos($ref_path, 'nos-creations') !== false) {
                    $ref_label = 'Nos créations';
                  } else {
                    $ref_label = ucfirst(str_replace(['-', '/'], [' ', ' › '], $ref_path));
                  }
                ?>
                  <span title="<?php echo esc_attr($r->referrer); ?>" style="cursor:help;">
                    <?php echo esc_html($ref_label); ?>
                  </span>
                <?php else : ?>
                  —
                <?php endif; ?>
              </td>
              <td><?php echo esc_html($r->piece); ?></td>
              <td><?php echo esc_html($r->taille); ?></td>
              <td><?php echo esc_html($r->sortie); ?></td>
              <td><?php echo esc_html($r->style); ?></td>
              <td><?php echo esc_html($r->products_shown); ?></td>
              <td style="text-align:center;">
                <?php if ($r->refine_count > 0) : ?>
                  <span title="<?php echo esc_attr($r->refine_messages); ?>" style="cursor:help; text-decoration:underline dotted;">
                    <?php echo esc_html($r->refine_count); ?>
                  </span>
                <?php else : ?>
                  —
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php if ($r->contact_sent) : ?>
                  <span title="<?php echo esc_attr($r->contact_name . ' — ' . $r->contact_email); ?>" style="cursor:help; color:#2E7D32;">&#10003;</span>
                <?php else : ?>
                  —
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php
                $delete_url = wp_nonce_url(
                  admin_url('admin.php?page=sapi-guide-logs&sapi_guide_delete=' . (int) $r->id),
                  'sapi_guide_delete_' . (int) $r->id
                );
                ?>
                <a href="<?php echo esc_url($delete_url); ?>"
                   onclick="return confirm('Supprimer cette session ?');"
                   style="color:#a00; text-decoration:none;" title="Supprimer">&#10005;</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
    <?php if ($total_pages > 1) : ?>
      <div class="tablenav">
        <div class="tablenav-pages">
          <?php
          echo wp_kses_post(paginate_links([
            'base'    => add_query_arg('paged', '%#%'),
            'format'  => '',
            'current' => $paged,
            'total'   => $total_pages,
          ]));
          ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <?php
}

// ─── AJAX: Render product cards for Conseils page ───
add_action('wp_ajax_sapi_conseils_products', 'sapi_ajax_conseils_products');
add_action('wp_ajax_nopriv_sapi_conseils_products', 'sapi_ajax_conseils_products');

function sapi_ajax_conseils_products() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  $ids_raw = isset($_POST['ids']) ? sanitize_text_field(wp_unslash($_POST['ids'])) : '[]';
  $ids = json_decode($ids_raw, true);

  if (!is_array($ids) || empty($ids)) {
    wp_send_json_error('No product IDs');
    return;
  }

  // Limit to 12 products max
  $ids = array_slice(array_map('absint', $ids), 0, 12);

  $query = new WP_Query([
    'post_type'      => 'product',
    'post__in'       => $ids,
    'orderby'        => 'post__in',
    'posts_per_page' => 12,
  ]);

  ob_start();
  if ($query->have_posts()) {
    echo '<ul class="products columns-4">';
    while ($query->have_posts()) {
      $query->the_post();
      wc_get_template_part('content', 'product');
    }
    echo '</ul>';
    wp_reset_postdata();
  }
  $html = ob_get_clean();

  wp_send_json_success($html);
}


<?php

if (!defined('ABSPATH')) {
  exit;
}

/* ─── Anti-spam : rate limiting formulaires de contact ─── */
function sapi_check_form_rate_limit($form_id = 'contact', $max_hits = 5) {
  $ip  = md5(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown');
  $key = 'sapi_form_rl_' . $form_id . '_' . $ip;
  $hits = (int) get_transient($key);
  if ($hits >= $max_hits) {
    return false;
  }
  set_transient($key, $hits + 1, HOUR_IN_SECONDS);
  return true;
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
        <a href="<?php echo esc_url(home_url('/mes-creations/')); ?>">Mes créations</a>
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
        <a href="<?php echo esc_url(home_url('/mes-creations/')); ?>">Mes créations</a>
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
  echo '<a href="' . esc_url(home_url('/mes-creations/')) . '">Mes créations</a>';
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

  // Preload LCP : première image du carousel avec srcset (accueil uniquement)
  if (is_front_page()) {
    $categories_order = ['suspensions', 'appliques-murales', 'lampes-a-poser', 'lampadaires'];
    foreach ($categories_order as $cat_slug) {
      $term = get_term_by('slug', $cat_slug, 'product_cat');
      if (!$term) continue;
      $q = new WP_Query([
        'post_type' => 'product',
        'posts_per_page' => 1,
        'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id]],
        'fields' => 'ids',
      ]);
      if ($q->have_posts()) {
        $lcp_ids = sapi_get_product_photo_ids($q->posts[0], 'ambiance', 1);
        if (!empty($lcp_ids)) {
          $lcp_id = $lcp_ids[0];
          $lcp_src = wp_get_attachment_image_url($lcp_id, 'full');
          $lcp_srcset = wp_get_attachment_image_srcset($lcp_id, 'full');
          echo '<link rel="preload" href="' . esc_url($lcp_src) . '" as="image" fetchpriority="high"';
          if ($lcp_srcset) {
            echo ' imagesrcset="' . esc_attr($lcp_srcset) . '" imagesizes="100vw"';
          }
          echo '>' . "\n";
        }
        wp_reset_postdata();
        break;
      }
      wp_reset_postdata();
    }
  }
}, 1);

/**
 * Dequeue scripts/styles not needed on non-WooCommerce pages
 */
function sapi_dequeue_unnecessary_assets() {
  if (is_front_page() || is_page()) {
    // jQuery UI datepicker — only needed on admin/checkout
    wp_dequeue_script('jquery-ui-datepicker');
    wp_dequeue_style('jquery-ui-datepicker');
  }
}
add_action('wp_enqueue_scripts', 'sapi_dequeue_unnecessary_assets', 100);

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

  // Sapi Project (F2a) — module localStorage "Mon projet", chargé toutes pages (léger, ~3KB)
  // Source de vérité pour les cards Conseiller, la modale tunnel, la fiche produit (F2b).
  $sapi_project_js_path = get_template_directory() . '/assets/sapi-project.js';
  if (file_exists($sapi_project_js_path)) {
    wp_enqueue_script('sapi-project', get_template_directory_uri() . '/assets/sapi-project.js', [], filemtime($sapi_project_js_path), true);
    wp_localize_script('sapi-project', 'SAPI_PROJECT', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('sapi-megafilter'),
    ]);
  }

  // CINÉTIQUE interactions (bento animations, custom cursor, parallax, quantity buttons, showcase slideshow)
  // Chargé sur homepage, pages produit ET pages catégorie
  if (is_front_page() || (class_exists('WooCommerce') && (is_product() || is_product_category()))) {
    $cinetique_js_path = get_template_directory() . '/assets/cinetique.js';
    wp_enqueue_script('sapi-maison-cinetique', get_template_directory_uri() . '/assets/cinetique.js', [], file_exists($cinetique_js_path) ? filemtime($cinetique_js_path) : '1.0.0', true);
  }

  // Homepage fullscreen carousel
  if (is_front_page()) {
    $carousel_js_path = get_template_directory() . '/assets/homepage-carousel.js';
    wp_enqueue_script('sapi-maison-homepage-carousel', get_template_directory_uri() . '/assets/homepage-carousel.js', [], file_exists($carousel_js_path) ? filemtime($carousel_js_path) : '1.0.0', true);
  }

  // FAQ accordion — articles de blog (Yoast FAQ block)
  if (is_single() && 'post' === get_post_type()) {
    $faq_js_path = get_template_directory() . '/assets/faq-accordion.js';
    if (file_exists($faq_js_path)) {
      wp_enqueue_script('sapi-maison-faq-accordion', get_template_directory_uri() . '/assets/faq-accordion.js', [], filemtime($faq_js_path), true);
    }
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

  }

  // F2a-quinquies — Hero live update (H1 qui s'adapte au changement de
  // sapiProject.answers.piece). Enqueue sur is_shop() uniquement.
  if (class_exists('WooCommerce') && is_shop()) {
    $hero_live_js_path = get_template_directory() . '/assets/sapi-hero-live.js';
    if (file_exists($hero_live_js_path)) {
      wp_enqueue_script(
        'sapi-hero-live',
        get_template_directory_uri() . '/assets/sapi-hero-live.js',
        ['sapi-project'],
        filemtime($hero_live_js_path),
        true
      );
      wp_localize_script('sapi-hero-live', 'SAPI_HERO_TITLES', sapi_get_hero_piece_titles());
    }
  }

  // Méga-filtre intelligent + modale Conseiller V3
  // - is_shop() : tous les scripts (méga-filtre, cards Conseil/Mon projet, modale)
  // - is_product() (F2b) : on étend à la fiche produit pour la modale partagée
  //   (la pill "Comment choisir ?" déclenche un sapi:open-modal).
  if (class_exists('WooCommerce') && (is_shop() || is_product())) {
    require_once get_template_directory() . '/inc/guide-data.php';

    // Règles de filtrage utilisées par sapi-cards-conseiller.js pour décider
    // quels produits matchent le projet du visiteur (pièce/sortie/taille).
    $sapi_filter_rules = [
      // Pièces avec filtre ampoule (mirror sapi_guide_get_ampoule_filter)
      'ampoule_by_piece' => [
        'cuisine'  => ['ampoule_degagee', 'semi_degagee'],
        'bureau'   => ['ampoule_degagee', 'semi_degagee'],
        'salon'    => ['ampoule_entouree', 'semi_degagee'],
        'chambre'  => ['ampoule_entouree', 'semi_degagee'],
        'entree'   => null,
        'escalier' => null,
      ],
      'ampoule_skip_when_grande' => ['cuisine', 'bureau'],
      'cats_by_sortie' => [
        'plafond'       => ['suspensions'],
        'mur'           => ['appliques'],
        'pas-de-sortie' => ['lampadaires', 'lampesaposer', 'appliques'],
        // Round 2 — 6.1 (N8) : appliques ajoutées par symétrie avec
        // cats_secondaire_by_sortie['ne-sais-pas']. Cohérent avec le kit
        // prise électrique (regles.txt:37, savoir.txt:48) qui permet
        // d'installer une applique sans sortie murale.
        'ne-sais-pas'   => ['suspensions', 'lampadaires', 'lampesaposer', 'appliques'],
        ''              => ['suspensions', 'lampadaires', 'lampesaposer', 'appliques'],
      ],
      'cats_secondaire_by_sortie' => [
        'plafond'       => ['suspensions'],
        'mur'           => ['appliques'],
        'pas-de-sortie' => ['lampadaires', 'lampesaposer', 'appliques'],
        'ne-sais-pas'   => ['lampadaires', 'lampesaposer', 'appliques'],
        ''              => ['lampadaires', 'lampesaposer'],
      ],
      'extras_slugs' => ['accessoires', 'carte-cadeau'],
    ];

    // F2a Phase 2 — cards "Conseil de Robin" / "Mon projet" sur /mes-creations/
    $cards_conseiller_js_path = get_template_directory() . '/assets/sapi-cards-conseiller.js';
    if (file_exists($cards_conseiller_js_path)) {
      wp_enqueue_script(
        'sapi-cards-conseiller',
        get_template_directory_uri() . '/assets/sapi-cards-conseiller.js',
        ['sapi-project', 'sapi-maison-shop'],
        filemtime($cards_conseiller_js_path),
        true
      );
      wp_localize_script('sapi-cards-conseiller', 'SAPI_CARDS_CONSEILLER', [
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'nonce'          => wp_create_nonce('sapi-megafilter'),
        'steps'          => sapi_guide_get_steps(),
        'rules'          => $sapi_filter_rules,
        // F2a-bis : textes génériques par pièce + fallback ultime — lus
        // synchronement par sapi-cards-conseiller.js (zéro AJAX au load).
        'genericAdvice'  => sapi_megafilter_get_generic_advices(),
        'fallbackAdvice' => __('Voici ma sélection pour ton projet.', 'theme-sapi-maison'),
      ]);
    }

    // F2a Phase 3 — modale tunnel 2 portes (S0/S1/S3) + Phase 4 (S2)
    $modal_conseiller_js_path = get_template_directory() . '/assets/sapi-modal-conseiller.js';
    if (file_exists($modal_conseiller_js_path)) {
      wp_enqueue_script(
        'sapi-modal-conseiller',
        get_template_directory_uri() . '/assets/sapi-modal-conseiller.js',
        ['sapi-project', 'sapi-cards-conseiller'],
        filemtime($modal_conseiller_js_path),
        true
      );
      wp_localize_script('sapi-modal-conseiller', 'SAPI_MODAL_CONSEILLER', [
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'nonce'          => wp_create_nonce('sapi-megafilter'),
        'steps'          => sapi_guide_get_steps(),
        'icons'          => sapi_guide_get_icons(),
        'maxMessages'    => 15,
        // F2b Phase 2 — Mode court : whitelist des steps utilisés sur fiche produit
        // (le reste est skip même si la visibility le permettrait).
        'shortSteps'     => ['piece', 'taille', 'taille_escalier', 'style'],
        // F2b Phase 2 — Conseils de style fixes (pattern legacy pg_style:* —
        // textes pré-générés affichés immédiatement, zéro IA).
        'styleConseils'  => sapi_megafilter_get_style_conseils(),
        'sizeConseils'   => sapi_megafilter_get_size_conseils(),
        // F2b Phase 2 — Contexte produit (null hors is_product, sinon ID + nom).
        'product'        => is_product() ? [
          'id'   => get_queried_object_id(),
          'name' => get_the_title(get_queried_object_id()),
        ] : null,
        // Round 3 — Lot C2/C4 : URL formulaire + email contact pour les CTAs
        // de l'écran s-contact et de la card sur-mesure routée contact.
        'contactSurmesureUrl' => home_url('/sur-mesure/'),
        'contactEmail'        => 'robin@atelier-sapi.fr',
      ]);
    }

    // F2b — Pill "Comment choisir ?" / "Adapter à mon projet" sur fiche produit
    if (is_product()) {
      $help_pill_js_path = get_template_directory() . '/assets/sapi-help-pill.js';
      if (file_exists($help_pill_js_path)) {
        wp_enqueue_script(
          'sapi-help-pill',
          get_template_directory_uri() . '/assets/sapi-help-pill.js',
          ['sapi-project', 'sapi-modal-conseiller'],
          filemtime($help_pill_js_path),
          true
        );
      }

      // F2b Phase 3 — Pré-sélection variation au load + listener apply event
      $preselect_js_path = get_template_directory() . '/assets/sapi-product-preselect.js';
      if (file_exists($preselect_js_path)) {
        wp_enqueue_script(
          'sapi-product-preselect',
          get_template_directory_uri() . '/assets/sapi-product-preselect.js',
          ['sapi-project', 'jquery'],
          filemtime($preselect_js_path),
          true
        );
      }
    }

    // F2a Phase 4 — card Sur-mesure intercalée dans la grille
    // MASQUÉE TEMPORAIREMENT — Robin a demandé de désactiver l'affichage pour
    // l'instant. Le markup PHP reste en place (rendu avec attribut `hidden`)
    // mais sans le JS pour le révéler, la card ne s'affiche jamais.
    // Pour réactiver : décommenter le bloc ci-dessous.
    //
    // $surmesure_js_path = get_template_directory() . '/assets/sapi-surmesure-card.js';
    // if (file_exists($surmesure_js_path)) {
    //   wp_enqueue_script(
    //     'sapi-surmesure-card',
    //     get_template_directory_uri() . '/assets/sapi-surmesure-card.js',
    //     ['sapi-project'],
    //     filemtime($surmesure_js_path),
    //     true
    //   );
    // }
  }

  // Cart page JS — enqueued when is_cart() returns true
  if (class_exists('WooCommerce') && is_cart()) {
    $cart_js_path = get_template_directory() . '/assets/cart-page.js';
    if (file_exists($cart_js_path)) {
      wp_enqueue_script('sapi-maison-cart-page', get_template_directory_uri() . '/assets/cart-page.js', [], filemtime($cart_js_path), true);
    }
  }

  // Galerie Inspiration — page autonome (CSS dédié, masonry CSS columns).
  // Slug 'inspiration' → WordPress charge page-inspiration.php via la hiérarchie
  // de templates, sans qu'il faille assigner le template depuis l'admin.
  if (is_page('inspiration') || is_page_template('page-inspiration.php')) {
    $inspiration_css_path = get_template_directory() . '/assets/inspiration.css';
    if (file_exists($inspiration_css_path)) {
      wp_enqueue_style('sapi-maison-inspiration', get_template_directory_uri() . '/assets/inspiration.css', ['sapi-maison-style'], filemtime($inspiration_css_path));
    }
    $inspiration_js_path = get_template_directory() . '/assets/inspiration.js';
    if (file_exists($inspiration_js_path)) {
      wp_enqueue_script('sapi-maison-inspiration', get_template_directory_uri() . '/assets/inspiration.js', [], filemtime($inspiration_js_path), true);
      wp_localize_script('sapi-maison-inspiration', 'sapiInspiration', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('sapi_inspiration_brevo_nonce'),
      ]);
    }
  }


  // Scroll Dots — mobile slide indicators (grilles verticales → sliders horizontaux)
  $scroll_dots_path = get_template_directory() . '/assets/scroll-dots.js';
  if (file_exists($scroll_dots_path)) {
    wp_enqueue_script('sapi-maison-scroll-dots', get_template_directory_uri() . '/assets/scroll-dots.js', [], filemtime($scroll_dots_path), true);
  }

  // Guide luminaire — helpers PHP utilisés par le méga-filtre (et F1b à venir)
  require_once get_template_directory() . '/inc/guide-data.php';

  // Bandeau réassurance : randomisation mobile + cleanup localStorage legacy
  $bandeau_path = get_template_directory() . '/assets/bandeau-reassurance.js';
  if (file_exists($bandeau_path)) {
    wp_enqueue_script('sapi-bandeau-reassurance', get_template_directory_uri() . '/assets/bandeau-reassurance.js', [], filemtime($bandeau_path), true);
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
            <span><?php esc_html_e('Fait main dans l\'atelier lyonnais de Robin', 'theme-sapi-maison'); ?></span>
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
            '<p class="empty-cart-text">Les luminaires de Robin n\u2019attendent que vous. Laissez-vous inspirer par ses cr\u00e9ations artisanales.</p>' +
          '</div>' +
          '<div class="empty-cart-cta"><a href="' + shopUrl + '" class="empty-cart-btn">D\u00e9couvrir les cr\u00e9ations</a></div>' +
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
              <span><?php esc_html_e('Fait main dans l\'atelier lyonnais de Robin', 'theme-sapi-maison'); ?></span>
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
 * Helper: extract video thumbnail URL from YouTube or Vimeo URL
 */
function sapi_get_video_thumbnail($url) {
  if (!$url) return '';

  // YouTube (watch, embed, shorts, youtu.be)
  if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
    return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
  }

  // Vimeo – use oembed API (cached by WordPress transients)
  if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
    $transient_key = 'sapi_vimeo_thumb_' . $m[1];
    $cached = get_transient($transient_key);
    if ($cached) return $cached;

    $response = wp_remote_get('https://vimeo.com/api/v2/video/' . $m[1] . '.json');
    if (!is_wp_error($response)) {
      $data = json_decode(wp_remote_retrieve_body($response), true);
      if (!empty($data[0]['thumbnail_large'])) {
        set_transient($transient_key, $data[0]['thumbnail_large'], DAY_IN_SECONDS * 30);
        return $data[0]['thumbnail_large'];
      }
    }
  }

  return '';
}

/**
 * Helper: get photo URLs from galerie_produit repeater by type.
 * Returns array of URLs matching the given type, or all photos if no type specified.
 *
 * @param int    $post_id  Product ID
 * @param string $type     Photo type: 'ambiance', 'detail', 'taille', 'client', 'fabrication', or '' for all
 * @param int    $limit    Max number of photos to return (0 = all)
 * @return array           Array of attachment IDs (0 excluded — only valid IDs)
 */
function sapi_get_product_photo_ids($post_id, $type = '', $limit = 0) {
  if (!function_exists('get_field')) return [];

  $ids = [];
  $galerie = get_field('galerie_produit', $post_id);

  if (!empty($galerie) && is_array($galerie)) {
    foreach ($galerie as $row) {
      $row_type = isset($row['type_photo']) ? $row['type_photo'] : '';
      if (is_array($row_type)) $row_type = isset($row_type['value']) ? $row_type['value'] : '';
      if ($type && $row_type !== $type) continue;
      $id = sapi_get_acf_image_id(isset($row['image']) ? $row['image'] : null);
      if ($id) {
        $ids[] = $id;
        if ($limit > 0 && count($ids) >= $limit) break;
      }
    }
  }

  return $ids;
}

/**
 * Get product photos as URLs (backward-compatible wrapper).
 *
 * @param int    $post_id  Product post ID
 * @param string $type     Photo type filter
 * @param int    $limit    Max number of photos (0 = all)
 * @param string $size     WordPress image size (default 'full')
 * @return array           Array of image URLs
 */
function sapi_get_product_photos($post_id, $type = '', $limit = 0, $size = 'full') {
  $ids = sapi_get_product_photo_ids($post_id, $type, $limit);
  $urls = [];
  foreach ($ids as $id) {
    $url = wp_get_attachment_image_url($id, $size);
    if ($url) $urls[] = $url;
  }
  return $urls;
}

/**
 * Helper: output an <img> tag with proper srcset from a media library filename.
 * Looks up the attachment by filename, caches the ID, and uses wp_get_attachment_image().
 *
 * @param string $filename  Filename relative to uploads (e.g. '2025/05/IMG_1928.jpg')
 * @param string $size      WordPress image size (default 'large')
 * @param array  $attr      Extra attributes for the img tag (alt, class, loading, etc.)
 * @return string            HTML <img> tag with srcset, or fallback <img> if not found
 */
function sapi_image($filename, $size = 'large', $attr = []) {
  // Cache attachment IDs to avoid repeated DB queries
  static $cache = [];

  if (!isset($cache[$filename])) {
    global $wpdb;
    $cache[$filename] = $wpdb->get_var($wpdb->prepare(
      "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
      $filename
    ));
  }

  $id = $cache[$filename];

  if ($id) {
    return wp_get_attachment_image((int) $id, $size, false, $attr);
  }

  // Fallback: hardcoded img without srcset
  $alt = isset($attr['alt']) ? $attr['alt'] : '';
  $class = isset($attr['class']) ? $attr['class'] : '';
  $loading = isset($attr['loading']) ? $attr['loading'] : 'lazy';
  return sprintf(
    '<img src="%s" alt="%s" class="%s" loading="%s">',
    esc_url(home_url('/wp-content/uploads/' . $filename)),
    esc_attr($alt),
    esc_attr($class),
    esc_attr($loading)
  );
}

/**
 * Helper: extract attachment ID from ACF image field (handles all return formats)
 * Returns 0 if the field contains a raw URL string (no attachment ID available).
 */
function sapi_get_acf_image_id($field_value) {
  if (!$field_value) return 0;
  if (is_array($field_value) && isset($field_value['ID'])) {
    return (int) $field_value['ID'];
  } elseif (is_numeric($field_value)) {
    return (int) $field_value;
  }
  return 0;
}

/**
 * Helper: extract URL from ACF image field (handles all return formats)
 * Centralized here to avoid duplication across templates.
 */
function sapi_get_acf_image_url($field_value, $size = 'full') {
  if (!$field_value) return '';
  // Try to get ID and use WordPress for proper size
  $id = sapi_get_acf_image_id($field_value);
  if ($id) {
    return wp_get_attachment_image_url($id, $size);
  }
  // Fallback: raw URL from array or string
  if (is_array($field_value) && isset($field_value['url'])) {
    return $field_value['url'];
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




function sapi_maison_cart_count() {
  if (!function_exists('WC')) {
    return 0;
  }
  return WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}

/**
 * Génère le shippingDetails Schema.org à partir des zones WooCommerce.
 */
function sapi_get_shipping_schema($product) {
  $shipping_details = [];
  $zones = WC_Shipping_Zones::get_zones();

  foreach ($zones as $zone_data) {
    $zone = new WC_Shipping_Zone($zone_data['zone_id']);
    $locations = $zone->get_zone_locations();
    $methods = $zone->get_shipping_methods(true);

    // Collecter les pays de cette zone
    $countries = [];
    foreach ($locations as $location) {
      if ($location->type === 'country') {
        $countries[] = $location->code;
      } elseif ($location->type === 'continent') {
        $countries[] = $location->code;
      }
    }

    if (empty($countries) || empty($methods)) {
      continue;
    }

    foreach ($methods as $method) {
      if ($method->id === 'local_pickup') {
        continue;
      }

      foreach ($countries as $country_code) {
        $detail = [
          '@type' => 'OfferShippingDetails',
          'shippingDestination' => [
            '@type' => 'DefinedRegion',
            'addressCountry' => $country_code
          ],
          'deliveryTime' => [
            '@type' => 'ShippingDeliveryTime',
            'handlingTime' => [
              '@type' => 'QuantitativeValue',
              'minValue' => 3,
              'maxValue' => 5,
              'unitCode' => 'd'
            ],
            'transitTime' => [
              '@type' => 'QuantitativeValue',
              'minValue' => 1,
              'maxValue' => 2,
              'unitCode' => 'd'
            ]
          ]
        ];

        // Tarif : flat_rate ou free_shipping
        if ($method->id === 'flat_rate') {
          $cost = $method->get_option('cost');
          if ($cost !== '' && $cost !== false) {
            $detail['shippingRate'] = [
              '@type' => 'MonetaryAmount',
              'value' => $cost,
              'currency' => 'EUR'
            ];
          }
        } elseif ($method->id === 'free_shipping') {
          $detail['shippingRate'] = [
            '@type' => 'MonetaryAmount',
            'value' => '0',
            'currency' => 'EUR'
          ];
        }

        $shipping_details[] = $detail;
      }
    }
  }

  return !empty($shipping_details) ? $shipping_details : [];
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
      'url' => get_permalink(),
      'sku' => $product->get_sku(),
      'brand' => [
        '@type' => 'Brand',
        'name' => 'Atelier Sâpi'
      ],
      'offers' => [
        '@type' => 'Offer',
        'url' => get_permalink(),
        'priceCurrency' => 'EUR',
        'price' => $product->get_price(),
        'priceValidUntil' => gmdate('Y-m-d', strtotime('+1 year')),
        'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'seller' => [
          '@type' => 'Organization',
          'name' => 'Atelier Sâpi'
        ],
        'shippingDetails' => sapi_get_shipping_schema($product),
        'hasMerchantReturnPolicy' => [
          '@type' => 'MerchantReturnPolicy',
          'applicableCountry' => 'FR',
          'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
          'merchantReturnDays' => 30,
          'returnMethod' => 'https://schema.org/ReturnByMail',
          'returnFees' => 'https://schema.org/ReturnFeesCustomerResponsibility',
          'refundType' => 'https://schema.org/FullRefund',
          'returnPolicySeasonalOverride' => []
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

  // Organization schema supprimé — Yoast le génère déjà dans son @graph
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
        'suspension' => 'Découvrez les suspensions artisanales en bois de l\'Atelier Sâpi. Luminaires suspendus design, découpés au laser et assemblés à la main à Lyon.',
        'lampadaire' => 'Les lampadaires en bois sculptés de Robin transforment vos espaces. Éclairage d\'ambiance unique, fabriqués en France à Lyon.',
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
      'name' => 'Mes créations',
      'url' => home_url('/mes-creations/')
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
      'name' => 'Mes créations',
      'url' => home_url('/mes-creations/')
    ];
    $term = get_queried_object();
    $breadcrumbs[] = [
      'name' => $term->name,
      'url' => ''
    ];
  }

  // SVG flèche comme séparateur
  $bulb = '<span class="breadcrumb-separator"><svg width="8" height="12" viewBox="0 0 8 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1.5 1L6.5 6L1.5 11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';

  // BreadcrumbList JSON-LD supprimé — Yoast le génère déjà dans son @graph
  echo '<nav class="breadcrumbs" aria-label="Fil d\'Ariane"><div class="breadcrumbs-inner">';
  foreach ($breadcrumbs as $index => $crumb) {
    $position = $index + 1;

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

/**
 * Réordonner les données panier : variations avant add-ons.
 *
 * WooCommerce Blocks (Store API) appelle ce filtre avec un array vide,
 * puis le plugin WC Product Add-Ons y ajoute ses données.
 * Les variations sont affichées séparément par Blocks via $cart_item['variation'].
 *
 * Stratégie : on injecte les variations dans item_data à priorité 5 (avant les
 * add-ons à priorité 10+). Le filtre rest_request_after_callbacks vide ensuite
 * le champ variation[] de la réponse Store API pour éviter le doublon.
 * Le CSS cache aussi le 2ème .product-details en sécurité.
 */
add_filter('woocommerce_get_item_data', function($item_data, $cart_item) {
  if (empty($cart_item['variation'])) {
    return $item_data;
  }

  // Construire les entrées de variation
  $variation_entries = [];
  foreach ($cart_item['variation'] as $attr_key => $attr_val) {
    if (empty($attr_val)) {
      continue;
    }
    $taxonomy = str_replace('attribute_', '', $attr_key);
    $label    = wc_attribute_label($taxonomy);
    if (taxonomy_exists($taxonomy)) {
      $term = get_term_by('slug', $attr_val, $taxonomy);
      $value = $term ? $term->name : $attr_val;
    } else {
      $value = $attr_val;
    }
    $variation_entries[] = [
      'key'     => $label,
      'value'   => $value,
      'display' => esc_html($value),
    ];
  }

  // Variations d'abord, puis add-ons
  return array_merge($variation_entries, $item_data);
}, 5, 2);

// Supprimer les variations de la réponse Store API pour éviter le doublon
// (les variations sont déjà dans item_data grâce au filtre ci-dessus)
add_filter('rest_request_after_callbacks', function($response, $handler, $request) {
  if (! ($response instanceof WP_REST_Response)) {
    return $response;
  }
  $route = $request->get_route();
  if (strpos($route, 'wc/store/v1/cart') === false && strpos($route, 'wc/store/v1/batch') === false) {
    return $response;
  }
  $data = $response->get_data();
  // Panier complet (/wc/store/v1/cart)
  if (isset($data['items']) && is_array($data['items'])) {
    foreach ($data['items'] as &$item) {
      if (isset($item['variation'])) {
        $item['variation'] = [];
      }
    }
    unset($item);
    $response->set_data($data);
  }
  // Endpoints batch ou item individuel
  if (isset($data['variation'])) {
    $data['variation'] = [];
    $response->set_data($data);
  }
  return $response;
}, 10, 3);

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

  // "Je ne sais pas" → pas de filtre taille (montrer tous les produits quelle que soit leur taille)
  if (isset($clean['taille']) && $clean['taille'] === 'ne-sais-pas') {
    unset($clean['taille']);
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

  $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : wp_generate_uuid4();

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
  $body  = "Nouveau message depuis le Robin Conseiller\n";
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
    '[Robin Conseiller] Message de ' . $name,
    $body,
    $headers
  );

  // Log contact
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

  // Honeypot
  if (!empty($_POST['website'])) {
    wp_send_json_error(['message' => 'Spam détecté.']);
    return;
  }

  // Rate limiting (5 soumissions/heure par IP)
  if (!sapi_check_form_rate_limit('robin_contact')) {
    wp_send_json_error(['message' => 'Trop de messages envoyés. Réessayez plus tard.']);
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
      'ai_text'        => 'Je ne peux pas affiner davantage pour le moment. Laissez vos coordonnées et Robin vous répondra personnellement.',
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

  // 9. Send response
  wp_send_json_success([
    'action'       => $action,
    'ai_text'      => $recommendation,
    'products'     => $new_products,
    'conversation' => $updated_conversation,
  ]);
}

/* ═══════════════════════════════════════════════════════════
   MÉGA-FILTRE INTELLIGENT (F1b) — IA dans la modale "Décrire mon projet"

   Deux endpoints AJAX dédiés :
   - sapi_megafilter_freetext : extraction de filtres structurés depuis texte
     libre (Haiku — rapide, déterministe)
   - sapi_megafilter_chat     : conversation libre dans la modale, peut
     ajuster les chips et router vers le formulaire de contact (Sonnet)

   Réutilise sapi_guide_check_rate_limit() et sapi_guide_query_all_products()
   du Conseiller. Endpoints distincts de sapi_ajax_guide_* pour éviter de
   mélanger les contextes (anciens orphelins, à supprimer en F1d).
═══════════════════════════════════════════════════════════ */

/**
 * Whitelist des slugs valides par clé de filtre, dérivée de
 * sapi_guide_get_steps() (source de vérité unique). Sert à la fois pour
 * lister les valeurs autorisées dans le system prompt et pour filtrer
 * les hallucinations Claude.
 */
function sapi_megafilter_filters_whitelist() {
  require_once get_template_directory() . '/inc/guide-data.php';
  $whitelist = [];
  foreach (sapi_guide_get_steps() as $step) {
    $slugs = [];
    foreach ($step['choices'] as $choice) {
      $slugs[] = $choice['slug'];
    }
    $whitelist[$step['id']] = $slugs;
  }
  return $whitelist;
}

/**
 * Wrapper Claude API local au méga-filtre. Retourne le texte brut de la
 * réponse (ou null en cas d'erreur). Le parsing JSON est délégué à
 * sapi_megafilter_parse_json() pour rester tolérant aux fences markdown.
 *
 * Note : on duplique légèrement sapi_guide_call_claude{,_refine} pour
 * isoler le nouveau contexte. Un refactor global est prévu en F1d.
 */
function sapi_megafilter_call_claude($model, $system, array $messages, $max_tokens = 1024) {
  $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
  if (empty($api_key)) {
    return null;
  }

  $body = [
    'model'      => $model,
    'max_tokens' => $max_tokens,
    'system'     => $system,
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
    error_log('Sapi MegaFilter Claude API error: ' . $response->get_error_message());
    return null;
  }

  $status   = wp_remote_retrieve_response_code($response);
  $raw_body = wp_remote_retrieve_body($response);

  if ($status !== 200) {
    error_log('Sapi MegaFilter Claude API HTTP ' . $status . ': ' . $raw_body);
    return null;
  }

  $data = json_decode($raw_body, true);
  if (!isset($data['content'][0]['text'])) {
    return null;
  }

  return $data['content'][0]['text'];
}

/**
 * Parse JSON tolérant — gère plusieurs cas pathologiques :
 *  1. JSON pur
 *  2. JSON entouré de fences markdown ```json ... ```
 *  3. Prose avant + bloc ```json ... ``` après (cas rencontré quand les
 *     exemples conversationnels priment dans le prompt — Claude écrit
 *     la prose ET le JSON)
 *  4. Prose mélangée avec une accolade {...} valide quelque part
 * Retourne null si rien n'a pu être extrait.
 */
function sapi_megafilter_parse_json($text) {
  if (!is_string($text)) return null;
  $clean = trim($text);

  // Stratégie 1 : essai direct (cas idéal)
  $direct = json_decode($clean, true);
  if (is_array($direct)) return $direct;

  // Stratégie 2 : fences markdown au début/fin
  $stripped = preg_replace('/^```(?:json)?\s*/i', '', $clean);
  $stripped = preg_replace('/\s*```$/', '', $stripped);
  $stripped = trim($stripped);
  $direct = json_decode($stripped, true);
  if (is_array($direct)) return $direct;

  // Stratégie 3 : trouve un bloc ```json ... ``` (ou ``` ... ```) n'importe où
  if (preg_match('/```(?:json)?\s*(\{[\s\S]*?\})\s*```/i', $clean, $m)) {
    $direct = json_decode(trim($m[1]), true);
    if (is_array($direct)) return $direct;
  }

  // Stratégie 4 : extrait la 1re accolade ouvrante à la dernière fermante
  $first = strpos($clean, '{');
  $last  = strrpos($clean, '}');
  if ($first !== false && $last !== false && $last > $first) {
    $candidate = substr($clean, $first, $last - $first + 1);
    $direct = json_decode($candidate, true);
    if (is_array($direct)) return $direct;
  }

  return null;
}

/**
 * Charge le contenu des 4 fichiers prompt V2 (assets/guide-prompt-*.txt) et
 * retourne la concaténation prête à coller en tête d'un system prompt
 * méga-filtre V3.
 *
 * Pattern repris de sapi_robin_build_step_prompt (V2) pour rapatrier la
 * voix Robin (ton chaleureux, tutoiement) et les règles métier dures
 * (cuisine sans lampe à poser, multi-ampoules, escalier, applique kit
 * prise, etc.) dans les prompts V3.
 *
 * @param bool $with_exemples Inclure guide-prompt-exemples.txt (verbeux,
 *                            à réserver aux prompts conversationnels —
 *                            risque de pollution de sortie JSON sinon).
 * @return string
 */
function sapi_megafilter_load_v2_prompts($with_exemples = false) {
  $theme_dir = get_template_directory();
  $ton    = @file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt')    ?: '';
  $savoir = @file_get_contents($theme_dir . '/assets/guide-prompt-savoir.txt') ?: '';
  $regles = @file_get_contents($theme_dir . '/assets/guide-prompt-regles.txt') ?: '';

  $out  = $ton . "\n\n" . $savoir . "\n\n" . $regles . "\n\n";

  if ($with_exemples) {
    $exemples = @file_get_contents($theme_dir . '/assets/guide-prompt-exemples.txt') ?: '';
    if ($exemples) {
      $out .= "EXEMPLES DE CONSEILS PAR ÉTAPE (pour le ton et la direction) :\n" . $exemples . "\n\n";
    }
  }

  return $out;
}

/**
 * System prompt — extraction freetext (Haiku).
 */
function sapi_megafilter_build_freetext_prompt(array $whitelist) {
  $labels = [
    'piece'           => 'pièce',
    'taille'          => 'taille de pièce',
    'taille_escalier' => 'type d\'escalier (uniquement si piece=escalier)',
    'eclairage'       => 'source principale ou secondaire',
    'sortie'          => 'où installer (plafond, mur, prise)',
    'hauteur'         => 'hauteur sous plafond',
    'table'           => 'au-dessus d\'une table/lit',
    'style'           => 'style d\'intérieur',
  ];

  // Injecte ton + savoir + regles V2 en tête (PAS exemples : risque de
  // polluer la sortie JSON stricte attendue par cet endpoint Haiku).
  $prompt  = sapi_megafilter_load_v2_prompts(false);

  $prompt .= "Tu es Robin, artisan menuisier lyonnais qui fabrique des luminaires en bois à la découpe laser.\n";
  $prompt .= "Un visiteur décrit son projet en quelques mots. Ton rôle : extraire les filtres structurés qu'il indique et lui répondre en 1-2 phrases.\n\n";

  $prompt .= "FILTRES DISPONIBLES (utilise UNIQUEMENT ces slugs exacts) :\n";
  foreach ($labels as $key => $label) {
    if (!isset($whitelist[$key])) continue;
    $prompt .= '- ' . $key . ' (' . $label . ') : ' . implode(' | ', $whitelist[$key]) . "\n";
  }

  $prompt .= "\nFORMAT DE RÉPONSE (JSON strict, sans markdown, sans prose autour) :\n";
  $prompt .= "{\n";
  $prompt .= '  "filters": { "piece": "chambre", "sortie": "mur" },' . "\n";
  $prompt .= '  "message": "Très bien, ...",' . "\n";
  $prompt .= '  "action": "contact",' . "\n";
  $prompt .= '  "contact_kind": "sur-mesure",' . "\n";
  $prompt .= '  "contact_subject": "Projet ...",' . "\n";
  $prompt .= '  "contact_message": "Bonjour Robin, ..."' . "\n";
  $prompt .= "}\n\n";

  $prompt .= "3 VOIES DE SORTIE — arbitre selon la nature du projet du visiteur :\n";
  $prompt .= "1) PROJET STANDARD : tu extrais les filtres possibles depuis sa phrase. Renvoie {filters: {...}, message: \"...\", action: null}\n";
  $prompt .= "   Exemples de déductions à faire (extrait ce que tu peux INFÉRER, pas seulement ce qui est explicite) :\n";
  $prompt .= "   - \"applique pour ma chambre\" → piece=chambre, sortie=mur (applique = mur)\n";
  $prompt .= "   - \"suspension salon\" → piece=salon, sortie=plafond (suspension = plafond)\n";
  $prompt .= "   - \"lampadaire chambre\" → piece=chambre, sortie=pas-de-sortie (lampadaire = prise 230V)\n";
  $prompt .= "   - \"lampe à poser bureau\" → piece=bureau, sortie=pas-de-sortie\n";
  $prompt .= "2) PROJET INCOMPLET : il manque une info essentielle pour proposer une sélection. Tu poses une question de précision dans message : {filters: {}, message: \"...\", action: null}\n";
  $prompt .= "3) PROJET CONTACT : la demande sort du périmètre catalogue, ou nécessite un échange direct. Renvoie {filters: {...} OU {}, message: \"...\", action: \"contact\", contact_kind: \"pro\"|\"sur-mesure\"|\"simple\"}\n\n";

  $prompt .= "CRITÈRES POUR `action: \"contact\"` :\n";
  $prompt .= "- Multi-luminaires : visiteur cherche plusieurs lampes pour un même projet (≥2 explicitement)\n";
  $prompt .= "- Pro / B2B : hôtel, restaurant, bureaux d'entreprise, salle d'événement, cadeau d'entreprise, retail, espace public\n";
  $prompt .= "- Dimensions custom : hauteur précise hors catalogue, format inhabituel demandé\n";
  $prompt .= "- Essence custom : bois non catalogue (chêne, noyer, etc.)\n";
  $prompt .= "- Combinaison qui sort manifestement du catalogue (style/format/usage spécial)\n\n";

  $prompt .= "CHOIX DE `contact_kind` :\n";
  $prompt .= "- \"pro\" : projet professionnel/B2B. CTA principal côté UI = \"Ouvrir le formulaire sur-mesure\".\n";
  $prompt .= "- \"sur-mesure\" : résidentiel avec demande très spécifique (custom dimensions, essence, design). CTAs côté UI = \"Formulaire sur-mesure\" + \"Email\" côte à côte.\n";
  $prompt .= "- \"simple\" : résidentiel léger qui veut juste un échange rapide. CTA principal = \"M'envoyer un email\".\n\n";

  $prompt .= "RÈGLE DU CAS PAR CAS (très important) :\n";
  $prompt .= "- Si malgré la complexité tu peux quand même proposer 1-2 modèles approchants du catalogue, fais-le : remplis `filters` AVEC `action: \"contact\"`. Le visiteur voit la sélection ET la porte sur-mesure côte à côte.\n";
  $prompt .= "- Si l'écart est trop grand (ex: hôtelier 30 chambres) : bascule directement en `action: \"contact\"` avec `filters: {}` — ne simule pas une recherche catalogue qui n'a aucun sens.\n\n";

  $prompt .= "CHAMPS BONUS pour l'UI contact (à remplir SI action = \"contact\") :\n";
  $prompt .= "- `contact_subject` : résumé court du projet (1 ligne max, ex: \"Projet hôtel — 30 chambres équipées\")\n";
  $prompt .= "- `contact_message` : message d'amorce pour le formulaire/email (3-4 lignes), comme si le visiteur l'écrivait lui-même à Robin. Pas de bullet points, ton humain.\n\n";

  $prompt .= "RÈGLES :\n";
  $prompt .= "- N'invente PAS de slug : utilise exactement ceux listés dans FILTRES DISPONIBLES.\n";
  $prompt .= "- `message` : 1-2 phrases chaleureuses, tutoiement, ton artisan. Mentionne ce que tu as compris du projet.\n";
  $prompt .= "- Pas d'emoji, pas de markdown dans `message`.\n\n";

  $prompt .= "⚠️ FORMAT DE SORTIE — IMPÉRATIF :\n";
  $prompt .= "Ta réponse DOIT être UNIQUEMENT le JSON décrit ci-dessus. RIEN d'autre :\n";
  $prompt .= "- PAS de prose avant le JSON\n";
  $prompt .= "- PAS de prose après le JSON\n";
  $prompt .= "- PAS de bloc ```markdown autour\n";
  $prompt .= "Premier caractère = `{`, dernier caractère = `}`. Point.\n";

  return $prompt;
}

/**
 * System prompt — conversation libre (Sonnet).
 */
function sapi_megafilter_build_chat_prompt(array $current_filters, array $all_products, array $whitelist, array $matching_ids = [], array $ignored_keys = []) {
  // Round 2 — 1.3 : contexte d'interaction EN PREMIER (avant ton/savoir/regles/exemples)
  // pour que l'IA arrête de prétendre que le visiteur voit la grille pendant le chat.
  $prompt  = "CONTEXTE D'INTERACTION :\n";
  $prompt .= "Tu es Robin dans une modale flottante ouverte par-dessus la grille des modèles.\n";
  $prompt .= "TANT QUE le visiteur n'a pas cliqué sur \"Voir la sélection\" pour fermer la modale,\n";
  $prompt .= "IL NE VOIT PAS la grille en dessous (elle est masquée par la modale).\n";
  $prompt .= "Ne dis donc JAMAIS \"tu vois les modèles à côté\", \"regarde la sélection\", ou équivalent.\n";
  $prompt .= "Présente-lui la sélection en mots, comme si vous étiez au téléphone ensemble.\n\n";

  // Injecte ton + savoir + regles + exemples V2 (équivalent V2
  // sapi_robin_build_step_prompt — les exemples guident le ton conversationnel).
  $prompt .= sapi_megafilter_load_v2_prompts(true);

  $prompt .= "Tu es Robin, artisan menuisier lyonnais qui fabrique des luminaires en bois à la découpe laser dans son atelier à Lyon.\n";
  $prompt .= "Tu accompagnes un visiteur qui explore ta collection sur le site atelier-sapi.fr.\n\n";

  $prompt .= "TON :\n";
  $prompt .= "- Chaleureux, simple, tutoiement systématique\n";
  $prompt .= "- Artisan passionné, pas vendeur\n";
  $prompt .= "- 2-4 phrases max par réponse\n";
  $prompt .= "- Tu peux mentionner la fabrication (laser, atelier à Lyon, bois français) si pertinent\n";
  $prompt .= "- Pas d'emoji, pas de markdown\n\n";

  $prompt .= "PROJET DU VISITEUR :\n";
  if (empty($current_filters)) {
    $prompt .= "(aucun filtre indiqué pour l'instant)\n";
  } else {
    $human_labels = sapi_megafilter_labels_from_slugs($current_filters);
    $prompt .= sapi_megafilter_format_project_text($current_filters, $human_labels) . "\n";
  }

  // Contrat enrichi : catalogue split (présentés/écartés) + réponses élargies.
  // Remplace l'ancienne section "CATALOGUE COMPLET" : maintenant l'IA sait
  // précisément ce que le visiteur voit dans la grille.
  $prompt .= sapi_megafilter_format_ignored_answers($ignored_keys);
  $prompt .= sapi_megafilter_format_catalog_split($all_products, $matching_ids);

  $prompt .= sapi_megafilter_adaptive_consigne_block();

  $prompt .= "\nSLUGS VALIDES (pour `filters_update`) :\n";
  foreach ($whitelist as $key => $slugs) {
    $prompt .= '- ' . $key . ' : ' . implode(' | ', $slugs) . "\n";
  }

  $prompt .= "\nFORMAT DE RÉPONSE (JSON strict, sans markdown, sans prose autour) :\n";
  $prompt .= "{\n";
  $prompt .= '  "message": "Réponse de Robin en 2-4 phrases...",' . "\n";
  $prompt .= '  "filters_update": { "piece": "cuisine", "style": null },' . "\n";
  $prompt .= '  "action": "contact",' . "\n";
  $prompt .= '  "contact_kind": "pro",' . "\n";
  $prompt .= '  "contact_subject": "Projet hôtel — 30 chambres équipées",' . "\n";
  $prompt .= '  "contact_message": "Bonjour Robin, je suis hôtelier..."' . "\n";
  $prompt .= "}\n\n";

  // Round 3 — Lot C1 : aligne les 3 voies de sortie sur le freetext (Haiku).
  $prompt .= "3 VOIES DE SORTIE — arbitre au fil de la conversation :\n";
  $prompt .= "1) Conversation NORMALE : `message` + éventuellement `filters_update`, pas d'`action`.\n";
  $prompt .= "2) Demande INCOMPLÈTE : tu poses une question de précision dans `message`, pas d'`action`.\n";
  $prompt .= "3) PROJET CONTACT : la demande sort du périmètre catalogue ou nécessite un échange direct → `action: \"contact\"` + `contact_kind: \"pro\"|\"sur-mesure\"|\"simple\"` + `contact_subject` + `contact_message`.\n\n";

  $prompt .= "CRITÈRES POUR `action: \"contact\"` :\n";
  $prompt .= "- Multi-luminaires (≥2 lampes pour un même projet), pro/B2B (hôtel, restaurant, retail, espace public), dimensions custom, essence custom (chêne, noyer, etc.), combinaison hors catalogue.\n\n";

  $prompt .= "CHOIX DE `contact_kind` :\n";
  $prompt .= "- \"pro\" : projet professionnel/B2B → CTA UI principal = formulaire sur-mesure.\n";
  $prompt .= "- \"sur-mesure\" : résidentiel avec demande très spécifique → CTAs UI = formulaire + email côte à côte.\n";
  $prompt .= "- \"simple\" : résidentiel léger qui veut juste un échange rapide → CTA UI principal = email direct.\n\n";

  $prompt .= "RÈGLE DU CAS PAR CAS :\n";
  $prompt .= "- Si malgré la complexité tu peux quand même proposer 1-2 modèles approchants, fais-le : remplis `filters_update` AVEC `action: \"contact\"`. Le visiteur voit la sélection ET la porte sur-mesure côte à côte.\n";
  $prompt .= "- Si l'écart est trop grand (ex: hôtelier 30 chambres) : bascule directement en `action: \"contact\"` sans `filters_update`.\n\n";

  $prompt .= "CHAMPS BONUS pour l'UI contact (à remplir SI action = \"contact\") :\n";
  $prompt .= "- `contact_subject` : résumé court du projet (1 ligne, ex: \"Projet hôtel — 30 chambres équipées\").\n";
  $prompt .= "- `contact_message` : message d'amorce pour le formulaire/email (3-4 lignes), comme si le visiteur l'écrivait à Robin. Ton humain, pas de bullet points.\n\n";

  $prompt .= "RÈGLES :\n";
  $prompt .= "- `message` : obligatoire, 2-4 phrases.\n";
  $prompt .= "- `filters_update` : optionnel. À inclure UNIQUEMENT si tu veux changer les chips suite au message du visiteur (ex. il précise, change d'avis). Utilise les slugs exacts. `null` pour supprimer un filtre. Ne touche pas aux chips non concernés.\n";
  $prompt .= "- Ne nomme JAMAIS de modèle précis dans `message` (le visiteur les voit dans la grille à côté). Présente plutôt l'ambiance, la matière, le format.\n\n";

  $prompt .= "⚠️ FORMAT DE SORTIE — IMPÉRATIF :\n";
  $prompt .= "Les EXEMPLES de ton plus haut sont là pour calibrer TA VOIX dans le champ `message`. Ta réponse, elle, DOIT être UNIQUEMENT le JSON décrit ci-dessus. RIEN d'autre :\n";
  $prompt .= "- PAS de prose avant le JSON\n";
  $prompt .= "- PAS de prose après le JSON\n";
  $prompt .= "- PAS de bloc ```markdown autour\n";
  $prompt .= "- Tout ton texte conversationnel va DANS le champ `message`, pas dehors.\n";
  $prompt .= "Premier caractère de ta réponse = `{`, dernier caractère = `}`. Point.\n";

  return $prompt;
}

/* ── Endpoint A1 : extraction freetext (Haiku) ───────────────────────── */
add_action('wp_ajax_sapi_megafilter_freetext', 'sapi_ajax_megafilter_freetext');
add_action('wp_ajax_nopriv_sapi_megafilter_freetext', 'sapi_ajax_megafilter_freetext');

function sapi_ajax_megafilter_freetext() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-megafilter')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  if (!sapi_guide_check_rate_limit()) {
    wp_send_json_error([
      'message'  => 'rate_limit',
      'fallback' => 'Trop de demandes pour le moment, réessaie dans une heure ou contacte-moi directement via le formulaire.',
    ]);
    return;
  }

  $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));
  if (empty($message) || mb_strlen($message) > 500) {
    wp_send_json_error(['message' => 'Message invalide']);
    return;
  }

  $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
  if (empty($session_id)) {
    $session_id = 'mfs_' . bin2hex(random_bytes(8));
  }

  $whitelist = sapi_megafilter_filters_whitelist();
  $system_prompt = sapi_megafilter_build_freetext_prompt($whitelist);

  $ai_text = sapi_megafilter_call_claude(
    'claude-haiku-4-5',
    $system_prompt,
    [['role' => 'user', 'content' => $message]],
    512
  );

  if (!$ai_text) {
    wp_send_json_error([
      'message'  => 'api_error',
      'fallback' => 'Je n\'arrive pas à analyser ton message pour l\'instant. Tu peux réessayer ou m\'écrire directement.',
    ]);
    return;
  }

  // Round 3 fix — Le prompt Lot C1 permet 3 voies de sortie : standard
  // (filters peuplés), incomplet (filters vide + question dans message),
  // contact (action=contact sans filters). On accepte donc tant qu'on a
  // un JSON parsé valide avec au moins un message ou une action — sinon
  // seulement fallback parse_error.
  $parsed = sapi_megafilter_parse_json($ai_text);
  $has_message = ($parsed && isset($parsed['message']) && is_string($parsed['message']) && $parsed['message'] !== '');
  $has_action  = ($parsed && isset($parsed['action']) && is_string($parsed['action']));
  if (!$parsed || (!$has_message && !$has_action)) {
    wp_send_json_error([
      'message'  => 'parse_error',
      'fallback' => 'Je n\'ai pas bien compris ton message. Tu peux reformuler ou m\'écrire directement.',
    ]);
    return;
  }

  // filters peut être absent (cas action=contact direct ou question de
  // précision). On normalise à array vide pour la suite du traitement.
  $raw_filters = (isset($parsed['filters']) && is_array($parsed['filters'])) ? $parsed['filters'] : [];
  $clean_filters = [];
  foreach ($raw_filters as $key => $val) {
    if (!isset($whitelist[$key])) continue;
    if (!is_string($val)) continue;
    if (!in_array($val, $whitelist[$key], true)) continue;
    $clean_filters[$key] = $val;
  }

  $robin_message = (isset($parsed['message']) && is_string($parsed['message']))
    ? sanitize_textarea_field($parsed['message'])
    : '';

  // Round 2 — 4.1.c : on propage `action: contact` quand l'IA route vers le
  // formulaire (projet hors-norme : pro, sur-mesure explicite, demande
  // spéciale). Le JS affichera un CTA Contact au lieu de "Voir la sélection".
  // Round 3 — Lot C1 : enrichissement avec contact_kind/subject/message pour
  // que le front puisse construire l'UI dédiée + pré-remplir le formulaire.
  $action = null;
  $contact_kind = null;
  $contact_subject = '';
  $contact_message = '';
  if (isset($parsed['action']) && $parsed['action'] === 'contact') {
    $action = 'contact';
    $allowed_kinds = ['pro', 'sur-mesure', 'simple'];
    if (isset($parsed['contact_kind']) && is_string($parsed['contact_kind']) && in_array($parsed['contact_kind'], $allowed_kinds, true)) {
      $contact_kind = $parsed['contact_kind'];
    }
    if (isset($parsed['contact_subject']) && is_string($parsed['contact_subject'])) {
      $contact_subject = sanitize_text_field($parsed['contact_subject']);
    }
    if (isset($parsed['contact_message']) && is_string($parsed['contact_message'])) {
      $contact_message = sanitize_textarea_field($parsed['contact_message']);
    }
  }

  wp_send_json_success([
    'filters'         => $clean_filters,
    'message'         => $robin_message,
    'action'          => $action,
    'contact_kind'    => $contact_kind,
    'contact_subject' => $contact_subject,
    'contact_message' => $contact_message,
    'session_id'      => $session_id,
  ]);
}

/* ── Endpoint A2 : conversation libre (Sonnet) ───────────────────────── */
add_action('wp_ajax_sapi_megafilter_chat', 'sapi_ajax_megafilter_chat');
add_action('wp_ajax_nopriv_sapi_megafilter_chat', 'sapi_ajax_megafilter_chat');

function sapi_ajax_megafilter_chat() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-megafilter')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  if (!sapi_guide_check_rate_limit()) {
    wp_send_json_success([
      'message'      => 'Je ne peux pas répondre davantage pour le moment. Si tu veux, écris-moi directement via le formulaire et je te répondrai personnellement.',
      'action'       => 'contact',
      'conversation' => [],
    ]);
    return;
  }

  $user_message = sanitize_textarea_field(wp_unslash($_POST['user_message'] ?? ''));
  if (empty($user_message) || mb_strlen($user_message) > 1000) {
    wp_send_json_error(['message' => 'Message invalide']);
    return;
  }

  $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : '';
  if (empty($session_id)) {
    $session_id = 'mfs_' . bin2hex(random_bytes(8));
  }

  $filters_raw = isset($_POST['current_filters']) ? wp_unslash($_POST['current_filters']) : '{}';
  $current_filters = json_decode($filters_raw, true);
  if (!is_array($current_filters)) $current_filters = [];

  $conversation_raw = isset($_POST['conversation']) ? wp_unslash($_POST['conversation']) : '[]';
  $conversation = json_decode($conversation_raw, true);
  if (!is_array($conversation)) $conversation = [];

  // Garde-fou serveur : 15 échanges utilisateur max (= 30 messages user+assistant)
  $user_msg_count = 0;
  foreach ($conversation as $m) {
    if (isset($m['role']) && $m['role'] === 'user') $user_msg_count++;
  }
  if ($user_msg_count >= 15) {
    wp_send_json_success([
      'message'      => 'On a bien discuté ! Pour aller plus loin, écris-moi directement via le formulaire de contact et on continuera ensemble.',
      'action'       => 'contact',
      'conversation' => $conversation,
      'session_id'   => $session_id,
    ]);
    return;
  }

  $whitelist = sapi_megafilter_filters_whitelist();
  $clean_current = [];
  foreach ($current_filters as $k => $v) {
    if (!isset($whitelist[$k])) continue;
    if (!is_string($v)) continue;
    if (!in_array($v, $whitelist[$k], true)) continue;
    $clean_current[$k] = $v;
  }

  $all_products = sapi_guide_query_all_products([]);

  // Contrat enrichi : matching IDs + ignored answers (envoyés par le JS).
  $matching_ids = sapi_megafilter_parse_matching_ids(isset($_POST['matching_product_ids']) ? wp_unslash($_POST['matching_product_ids']) : '');
  $ignored_keys = sapi_megafilter_parse_ignored_answers(isset($_POST['ignored_answers']) ? wp_unslash($_POST['ignored_answers']) : '');

  $system_prompt = sapi_megafilter_build_chat_prompt($clean_current, $all_products, $whitelist, $matching_ids, $ignored_keys);

  $messages = [];
  foreach ($conversation as $msg) {
    if (!isset($msg['role']) || !isset($msg['content'])) continue;
    $role = ($msg['role'] === 'assistant') ? 'assistant' : 'user';
    $messages[] = ['role' => $role, 'content' => sanitize_textarea_field($msg['content'])];
  }
  $messages[] = ['role' => 'user', 'content' => $user_message];

  $ai_text = sapi_megafilter_call_claude(
    'claude-sonnet-4-6',
    $system_prompt,
    $messages,
    1024
  );

  if (!$ai_text) {
    wp_send_json_error([
      'message'  => 'api_error',
      'fallback' => 'Je n\'arrive pas à te répondre pour l\'instant. Tu peux me contacter directement via le formulaire.',
    ]);
    return;
  }

  $parsed = sapi_megafilter_parse_json($ai_text);

  // Si le JSON est foireux, on tombe sur le texte brut comme message neutre
  $robin_message = '';
  $filters_update = null;
  $action = null;

  if ($parsed && isset($parsed['message']) && is_string($parsed['message'])) {
    $robin_message = sanitize_textarea_field($parsed['message']);
  } else {
    $robin_message = sanitize_textarea_field($ai_text);
  }

  if ($parsed && isset($parsed['filters_update']) && is_array($parsed['filters_update'])) {
    $filters_update = [];
    foreach ($parsed['filters_update'] as $k => $v) {
      if (!isset($whitelist[$k])) continue;
      if ($v === null) {
        $filters_update[$k] = null;
      } elseif (is_string($v) && in_array($v, $whitelist[$k], true)) {
        $filters_update[$k] = $v;
      }
    }
    if (empty($filters_update)) $filters_update = null;
  }

  // Round 3 — Lot C1 : enrichissement avec contact_kind/subject/message,
  // miroir du endpoint freetext. Permet à l'UI de construire CTAs adaptés
  // + pré-remplir le formulaire /sur-mesure/.
  $contact_kind = null;
  $contact_subject = '';
  $contact_message = '';
  if ($parsed && isset($parsed['action']) && $parsed['action'] === 'contact') {
    $action = 'contact';
    $allowed_kinds = ['pro', 'sur-mesure', 'simple'];
    if (isset($parsed['contact_kind']) && is_string($parsed['contact_kind']) && in_array($parsed['contact_kind'], $allowed_kinds, true)) {
      $contact_kind = $parsed['contact_kind'];
    }
    if (isset($parsed['contact_subject']) && is_string($parsed['contact_subject'])) {
      $contact_subject = sanitize_text_field($parsed['contact_subject']);
    }
    if (isset($parsed['contact_message']) && is_string($parsed['contact_message'])) {
      $contact_message = sanitize_textarea_field($parsed['contact_message']);
    }
  }

  $new_conversation = array_merge($conversation, [
    ['role' => 'user',      'content' => $user_message],
    ['role' => 'assistant', 'content' => $robin_message],
  ]);

  wp_send_json_success([
    'message'         => $robin_message,
    'filters_update' => $filters_update,
    'action'          => $action,
    'contact_kind'    => $contact_kind,
    'contact_subject' => $contact_subject,
    'contact_message' => $contact_message,
    'conversation'    => $new_conversation,
    'session_id'      => $session_id,
  ]);
}

/* ═══════════════════════════════════════════════════════════
   MÉGA-FILTRE F2a / F2a-bis — Endpoints additionnels
   - sapi_megafilter_advice    : phrase IA conseillère unique, appelée à la sortie
     de la modale (Sonnet, sans cache). Stockée dans sapiProject.advice_text côté
     front et réutilisée sans nouvel appel.
   - sapi_megafilter_surmesure : soumission form sur-mesure (email Robin)
═══════════════════════════════════════════════════════════ */

/**
 * Textes génériques pré-rédigés par pièce — utilisés en fallback si l'IA plante
 * et en affichage par défaut sur la card "Mon projet" tant qu'aucun parcours
 * n'a abouti dans la modale. Source de vérité unique partagée PHP / JS.
 */
/**
 * Titres du hero /mes-creations/ par pièce (F2a-quinquies).
 * Source unique partagée entre le rendu PHP initial (archive-product.php)
 * et la localize JS (sapi-hero-live.js qui met à jour le H1 en live au
 * changement de sapiProject.answers.piece).
 */
function sapi_get_hero_piece_titles() {
  return [
    'default' => __('Mes Créations', 'theme-sapi-maison'),
    'pieces'  => [
      'salon'    => __('Pour un salon', 'theme-sapi-maison'),
      'chambre'  => __('Pour une chambre', 'theme-sapi-maison'),
      'cuisine'  => __('Pour une cuisine', 'theme-sapi-maison'),
      'bureau'   => __('Pour un bureau', 'theme-sapi-maison'),
      'entree'   => __('Pour une entrée', 'theme-sapi-maison'),
      'escalier' => __('Pour un escalier', 'theme-sapi-maison'),
    ],
  ];
}

function sapi_megafilter_get_generic_advices() {
  return [
    'cuisine'  => __("Pour une cuisine, je privilégie les modèles où l'ampoule reste à découvert. La lumière descend franchement sur le plan de travail, sans zone d'ombre.", 'theme-sapi-maison'),
    'bureau'   => __("Pour un bureau, je retiens les modèles où l'ampoule reste à découvert. La lumière est directe et tranchée, idéale pour la concentration sans fatiguer les yeux.", 'theme-sapi-maison'),
    'salon'    => __("Pour un salon, je privilégie l'ampoule entourée. La lumière passe à travers le bois et dessine ses motifs au mur, l'ambiance s'installe.", 'theme-sapi-maison'),
    'chambre'  => __("Pour une chambre, je sélectionne des modèles à ampoule entourée. Une lumière douce et diffuse, qui invite au calme et révèle les jeux du bois.", 'theme-sapi-maison'),
    'entree'   => __("Pour une entrée, ma sélection mise sur l'ampoule entourée. La lumière joue avec les découpes du bois, donne le ton dès le pas de porte.", 'theme-sapi-maison'),
    'escalier' => __("Pour un escalier, je retiens les modèles hauts qui occupent le volume. La cage se révèle par étages, l'œil suit la lumière en montant.", 'theme-sapi-maison'),
  ];
}

function sapi_megafilter_generic_advice_for($piece) {
  $advices = sapi_megafilter_get_generic_advices();
  if (is_string($piece) && isset($advices[$piece])) return $advices[$piece];
  return __('Voici ma sélection pour ton projet.', 'theme-sapi-maison');
}

/**
 * Sanitise un payload {answers, labels} en ne gardant que les paires reconnues
 * dans la whitelist du méga-filtre. Retourne [$clean_answers, $clean_labels].
 */
function sapi_megafilter_sanitize_project($answers_raw, $labels_raw = []) {
  $whitelist = sapi_megafilter_filters_whitelist();
  $clean_answers = [];
  $clean_labels  = [];
  if (!is_array($answers_raw)) return [$clean_answers, $clean_labels];

  foreach ($answers_raw as $k => $v) {
    $key = sanitize_key($k);
    if (!isset($whitelist[$key])) continue;
    if (!is_string($v)) continue;
    if (!in_array($v, $whitelist[$key], true)) continue;
    $clean_answers[$key] = $v;
  }

  if (is_array($labels_raw)) {
    foreach ($labels_raw as $k => $v) {
      $key = sanitize_key($k);
      if (!isset($clean_answers[$key])) continue; // pas de label sans answer
      if (!is_string($v)) continue;
      $clean_labels[$key] = sanitize_text_field($v);
    }
  }

  return [$clean_answers, $clean_labels];
}

/**
 * Construit un résumé textuel multi-ligne du projet visiteur, avec des CLÉS
 * EXPLICITES qui lèvent l'ambiguïté pour l'IA (ex. "Emplacement de la sortie
 * électrique : Au mur" plutôt que "Sortie : Au mur" qui pouvait être lu
 * comme "la pièce est au mur"). Utilisé dans les prompts IA et l'email
 * sur-mesure à Robin.
 */
function sapi_megafilter_format_project_text(array $answers, array $labels) {
  $key_labels = [
    'piece'           => 'Pièce où installer le luminaire',
    'taille'          => 'Taille de la pièce',
    'taille_escalier' => "Type d'escalier",
    'eclairage'       => "Rôle d'éclairage attendu",
    'sortie'          => "Emplacement de la sortie électrique",
    'hauteur'         => 'Hauteur sous plafond',
    'table'           => "Sera-t-il au-dessus d'un meuble (table/lit/bureau)",
    'style'           => 'Style décoratif souhaité',
  ];
  $parts = [];
  foreach ($key_labels as $k => $label) {
    if (!isset($answers[$k])) continue;
    $value = isset($labels[$k]) ? $labels[$k] : $answers[$k];
    $parts[] = '- ' . $label . ' : ' . $value;
  }
  return implode("\n", $parts);
}

/**
 * Mappe un tableau de slugs {key => slug} vers le tableau de labels affichables
 * correspondants {key => label} en lookup sur sapi_guide_get_steps()[].choices[].
 * Permet à sapi_megafilter_build_chat_prompt de réutiliser
 * sapi_megafilter_format_project_text quand le POST ne fournit que les slugs.
 */
function sapi_megafilter_labels_from_slugs(array $filters) {
  if (empty($filters)) return [];
  $steps = function_exists('sapi_guide_get_steps') ? sapi_guide_get_steps() : [];
  $by_step = [];
  foreach ($steps as $step) {
    if (!isset($step['id']) || !isset($step['choices'])) continue;
    $by_step[$step['id']] = $step['choices'];
  }
  $out = [];
  foreach ($filters as $key => $slug) {
    if (!is_string($slug) || $slug === '') continue;
    if (!isset($by_step[$key])) continue;
    foreach ($by_step[$key] as $choice) {
      if (isset($choice['slug'], $choice['label']) && $choice['slug'] === $slug) {
        $out[$key] = $choice['label'];
        break;
      }
    }
  }
  return $out;
}

/**
 * F2b — Modale Conseiller V3 partagée entre /mes-creations/ et fiche produit.
 *
 * Le markup ne change pas selon la page : c'est sapi-modal-conseiller.js qui
 * adapte l'écran à l'ouverture (S0/S1/S2-chat/S3, et bientôt s-product-recap).
 *
 * Hookée sur wp_footer pour éviter de dupliquer le markup dans deux templates.
 * Condition d'activation : is_shop() || is_product() — les scripts associés
 * (sapi-modal-conseiller, sapi-project, sapi-cards-conseiller) sont enqueués
 * en miroir dans la même condition.
 */
function sapi_render_conseiller_modal() {
  if (!class_exists('WooCommerce')) return;
  if (!(is_shop() || is_product())) return;

  $pencil_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>';
  $arrow_svg  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
  ?>
  <div class="conseiller-modal" data-conseiller-modal hidden>
    <div class="conseiller-card conseiller-card--modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Conseil de Robin', 'theme-sapi-maison'); ?>" data-modal-card>

      <!-- Round 2 — 3.4 : bouton close visible toutes tailles (critique mobile,
           sinon modale plein écran sans croix = visiteur bloqué). Câblé via
           data-action="close" sur le handler de délégation existant. -->
      <button type="button" class="conseiller-modal__close" data-action="close" aria-label="<?php esc_attr_e('Fermer', 'theme-sapi-maison'); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>


      <!-- S0 — Écran hybride (F2a-quater) : question dynamique + choices + séparateur "ou" + champ texte.
           Bascule dynamique selon sapiProject : "Conseil de Robin" (initial) ou "Mon projet" (partiel).
           Si projet complet → la logique d'ouverture montre S3 directement à la place. -->
      <div class="conseiller-modal__screen" data-screen="s0" hidden>
        <div class="conseiller-card__inner">
          <span class="conseiller-badge conseiller-badge--default" data-s0-badge>
            <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <span data-s0-badge-text><?php esc_html_e('Conseil de Robin', 'theme-sapi-maison'); ?></span>
          </span>

          <h2 class="conseiller-h2" data-s0-question aria-live="polite">…</h2>

          <div class="conseiller-choices" data-s0-choices></div>

          <div class="conseiller-separator-or" aria-hidden="true">
            <span class="conseiller-separator-or__text"><?php esc_html_e('ou', 'theme-sapi-maison'); ?></span>
          </div>

          <form class="conseiller-freetext" data-s0-form>
            <div class="conseiller-freetext__wrap">
              <input type="text" class="conseiller-freetext__input" data-s0-input
                     placeholder="<?php esc_attr_e('Décris ton projet en quelques mots…', 'theme-sapi-maison'); ?>"
                     maxlength="500"
                     aria-label="<?php esc_attr_e('Décris ton projet en quelques mots', 'theme-sapi-maison'); ?>">
              <button type="submit" class="conseiller-freetext__submit" aria-label="<?php esc_attr_e('Envoyer', 'theme-sapi-maison'); ?>">
                <?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
              </button>
            </div>
          </form>

          <div class="conseiller-modal__nav" data-s0-reset-wrap hidden>
            <button type="button" class="conseiller-s3-reset" data-action="s0-reset">
              <?php esc_html_e('Effacer et recommencer', 'theme-sapi-maison'); ?>
            </button>
          </div>
        </div>
      </div>

      <!-- S1 — Mode questions guidées -->
      <div class="conseiller-modal__screen" data-screen="s1" hidden>
        <div class="conseiller-card__inner">
          <div class="conseiller-progress" aria-hidden="true">
            <div class="conseiller-progress__fill" data-progress-fill style="width: 0%"></div>
          </div>

          <span class="conseiller-badge conseiller-badge--default">
            <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php esc_html_e('Conseil de Robin', 'theme-sapi-maison'); ?>
          </span>

          <h2 class="conseiller-h2" data-question-title aria-live="polite">…</h2>

          <div class="conseiller-choices" data-choices></div>

          <div class="conseiller-modal__nav">
            <button type="button" class="conseiller-back-link" data-action="back">← <?php esc_html_e('Retour', 'theme-sapi-maison'); ?></button>
          </div>
        </div>
      </div>

      <!-- S2.chat — Mode texte libre, conversation -->
      <div class="conseiller-modal__screen" data-screen="s2-chat" hidden>
        <div class="conseiller-card__inner conseiller-card__inner--chat">
          <div class="conseiller-chat-header">
            <span class="conseiller-badge conseiller-badge--default">
              <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
              <?php esc_html_e('Conseil de Robin', 'theme-sapi-maison'); ?>
            </span>
          </div>

          <div class="conseiller-chat" data-chat-messages></div>

          <div class="conseiller-chat-cta" data-chat-cta hidden>
            <button type="button" class="conseiller-cta" data-action="apply">
              <span><?php esc_html_e('Voir la sélection', 'theme-sapi-maison'); ?></span>
              <?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </button>
          </div>

          <form class="conseiller-chat-footer" data-chat-form>
            <input type="text" class="conseiller-chat-footer__input" data-chat-input
                   placeholder="<?php esc_attr_e('Continuer à discuter avec Robin…', 'theme-sapi-maison'); ?>"
                   maxlength="1000"
                   aria-label="<?php esc_attr_e('Message', 'theme-sapi-maison'); ?>">
            <button type="submit" class="conseiller-chat-footer__send"><?php esc_html_e('Envoyer', 'theme-sapi-maison'); ?></button>
          </form>
        </div>
      </div>

      <!-- s-product-recap (F2b Phase 2) — Mode court fin de parcours sur fiche
           produit. Récap 100% statique (textes pré-générés, zéro IA), pattern
           repris du legacy renderProductGuideResult() :
             - intro dynamique "Pour votre <pièce> de taille <taille>, Robin recommande :"
             - récap card 2 lignes : Essence / Taille recommandée (label lu du select WC)
             - conseil de style fixe (pg_style:moderne/ancien/neutre)
             - 3 actions : Appliquer / Modifier / Contacter -->
      <div class="conseiller-modal__screen" data-screen="s-product-recap" hidden>
        <div class="conseiller-card__inner">
          <span class="conseiller-badge conseiller-badge--default">
            <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php esc_html_e('Conseil de Robin', 'theme-sapi-maison'); ?>
          </span>

          <p class="conseiller-product-recap__intro" data-product-recap-intro></p>

          <div class="conseiller-product-recap__card" data-product-recap-card hidden>
            <div class="conseiller-product-recap__row" data-product-recap-essence hidden>
              <span class="conseiller-product-recap__label"><?php esc_html_e('Essence', 'theme-sapi-maison'); ?></span>
              <span class="conseiller-product-recap__value" data-product-recap-essence-value></span>
            </div>
            <div class="conseiller-product-recap__row" data-product-recap-taille hidden>
              <span class="conseiller-product-recap__label"><?php esc_html_e('Taille recommandée', 'theme-sapi-maison'); ?></span>
              <span class="conseiller-product-recap__value" data-product-recap-taille-value></span>
            </div>
          </div>

          <p class="conseiller-product-recap__conseil" data-product-recap-conseil></p>
          <p class="conseiller-product-recap__conseil conseiller-product-recap__conseil--taille" data-product-recap-conseil-taille></p>

          <div class="conseiller-modal__cta">
            <button type="button" class="conseiller-cta" data-action="product-apply">
              <span><?php esc_html_e('Appliquer cette sélection', 'theme-sapi-maison'); ?></span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
            </button>
          </div>

          <div class="conseiller-modal__nav conseiller-modal__nav--product-recap">
            <button type="button" class="conseiller-back-link" data-action="product-modify">← <?php esc_html_e('Modifier mes réponses', 'theme-sapi-maison'); ?></button>
            <a class="conseiller-back-link conseiller-back-link--contact" href="<?php echo esc_url(home_url('/contact/')); ?>">
              <?php esc_html_e('Contacter Robin', 'theme-sapi-maison'); ?>
            </a>
          </div>
        </div>
      </div>

      <!-- Round 3 — Lot C2 : écran s-contact pour les projets routés vers
           Robin (pro / sur-mesure / simple). Reçoit message + recap + CTAs
           dynamiques selon contact_kind. Le visiteur peut revenir au chat. -->
      <div class="conseiller-modal__screen" data-screen="s-contact" hidden>
        <div class="conseiller-card__inner">
          <span class="conseiller-badge conseiller-badge--default">
            <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <span data-contact-badge-text><?php esc_html_e('Échangeons ensemble', 'theme-sapi-maison'); ?></span>
          </span>

          <p class="conseiller-contact__message" data-contact-message></p>
          <div class="conseiller-contact__recap" data-contact-recap></div>
          <div class="conseiller-contact__ctas" data-contact-ctas></div>

          <div class="conseiller-modal__nav">
            <button type="button" class="conseiller-back-link" data-action="back-to-chat">
              ← <?php esc_html_e('Continuer la discussion avec Robin', 'theme-sapi-maison'); ?>
            </button>
          </div>
        </div>
      </div>

      <!-- S3 — Carrefour "Modifier mon projet" -->
      <div class="conseiller-modal__screen" data-screen="s3" hidden>
        <div class="conseiller-card__inner">
          <span class="conseiller-badge conseiller-badge--default">
            <?php echo $pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            <?php esc_html_e('Mon projet', 'theme-sapi-maison'); ?>
          </span>
          <h2 class="conseiller-h2"><?php esc_html_e('Voici ton projet', 'theme-sapi-maison'); ?></h2>

          <div class="conseiller-chips" data-recap-chips></div>

          <div class="conseiller-s3-secondary-actions">
            <button type="button" class="conseiller-cta conseiller-cta--secondary" data-action="s3-reset">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>
              <span><?php esc_html_e('Recommencer', 'theme-sapi-maison'); ?></span>
            </button>
            <button type="button" class="conseiller-cta conseiller-cta--secondary" data-action="s3-refine">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <span><?php esc_html_e('Préciser avec Robin', 'theme-sapi-maison'); ?></span>
            </button>
          </div>

          <div class="conseiller-modal__cta">
            <button type="button" class="conseiller-cta" data-action="s3-view">
              <span><?php esc_html_e('Voir la sélection', 'theme-sapi-maison'); ?></span>
              <?php echo $arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </button>
          </div>
        </div>
      </div>

    </div><!-- /.conseiller-card--modal -->
  </div>
  <?php
}
add_action('wp_footer', 'sapi_render_conseiller_modal');

/* ── Helpers contrat IA enrichi : passes catalogue split + ignored_answers
   ─────────────────────────────────────────────────────────────────────────────
   Utilisés par sapi_ajax_megafilter_advice ET sapi_ajax_megafilter_chat pour
   construire les sections "PRODUITS PRÉSENTÉS" / "PRODUITS ÉCARTÉS" /
   "RÉPONSES ÉLARGIES" dans les prompts IA.
   ───────────────────────────────────────────────────────────────────────────── */

// Parse le POST 'matching_product_ids' (JSON array d'IDs côté JS) en array d'ints.
function sapi_megafilter_parse_matching_ids($raw) {
  if (!is_string($raw) || $raw === '') return [];
  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) return [];
  $ids = [];
  foreach ($decoded as $v) {
    $id = absint($v);
    if ($id > 0) $ids[] = $id;
  }
  return array_values(array_unique($ids));
}

// Parse le POST 'ignored_answers' (JSON array de slugs step côté JS) en array
// filtré aux step IDs reconnus par le guide.
function sapi_megafilter_parse_ignored_answers($raw) {
  if (!is_string($raw) || $raw === '') return [];
  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) return [];
  $valid_keys = ['piece','taille','taille_escalier','eclairage','sortie','hauteur','table','style'];
  $out = [];
  foreach ($decoded as $v) {
    if (is_string($v) && in_array($v, $valid_keys, true)) $out[] = $v;
  }
  return array_values(array_unique($out));
}

// Construit les 2 sections "PRODUITS PRÉSENTÉS" + "PRODUITS ÉCARTÉS" depuis un
// $all_products (sapi_guide_query_all_products) + les matching_ids.
// Round 2 — 4.2 : enrichi avec essences disponibles + prix dès, lus depuis
// $p['variations'] (essence) et $p['price_min_raw'] (déjà calculés dans
// sapi_guide_collect_results) — l'IA peut désormais parler de matière et de
// prix sans inventer.
function sapi_megafilter_format_catalog_split(array $all_products, array $matching_ids) {
  $matching_set = array_flip(array_map('intval', $matching_ids));
  $presented = [];
  $ecarted = [];
  foreach ($all_products as $p) {
    $id = isset($p['id']) ? intval($p['id']) : 0;
    if ($id <= 0) continue;
    $cats = isset($p['categories']) && is_array($p['categories']) ? implode(', ', $p['categories']) : '';
    $format = isset($p['format']) ? $p['format'] : '';
    $ampoule = isset($p['type_ampoule']) ? $p['type_ampoule'] : '';

    // Essences uniques depuis les variations (typiquement Peuplier, Okoumé)
    $essences_uniques = [];
    if (!empty($p['variations']) && is_array($p['variations'])) {
      foreach ($p['variations'] as $v) {
        if (!empty($v['essence']) && !in_array($v['essence'], $essences_uniques, true)) {
          $essences_uniques[] = $v['essence'];
        }
      }
    }
    $essences = empty($essences_uniques) ? '?' : implode(', ', $essences_uniques);

    // Prix dès — utilise price_min_raw (float) pour formater proprement
    $prix = '?';
    if (isset($p['price_min_raw']) && $p['price_min_raw'] > 0) {
      $prix = number_format((float) $p['price_min_raw'], 0, ',', ' ') . '€';
    }

    $line = '- ' . (isset($p['title']) ? $p['title'] : '?')
          . ' | Catégorie : ' . $cats
          . ' | Format : ' . $format
          . ' | Ampoule : ' . $ampoule
          . ' | Essences : ' . $essences
          . ' | Prix dès : ' . $prix;
    if (isset($matching_set[$id])) $presented[] = $line;
    else                            $ecarted[] = $line;
  }

  $out = "\nPRODUITS PRÉSENTÉS AU VISITEUR APRÈS FILTRAGE (" . count($presented) . ") :\n";
  $out .= empty($presented) ? "(aucun)\n" : implode("\n", $presented) . "\n";

  if (!empty($ecarted)) {
    $out .= "\nPRODUITS ÉCARTÉS PAR LE FILTRE (" . count($ecarted) . ") — non visibles par le visiteur :\n";
    $out .= implode("\n", $ecarted) . "\n";
  }
  return $out;
}

// Construit la section "RÉPONSES ÉLARGIES" (ligne unique, "" si aucune).
function sapi_megafilter_format_ignored_answers(array $ignored_keys) {
  if (empty($ignored_keys)) return '';
  $labels = [
    'piece'           => 'la pièce',
    'taille'          => 'la taille de pièce',
    'taille_escalier' => "le type d'escalier",
    'eclairage'       => "le rôle d'éclairage",
    'sortie'          => "le type de sortie",
    'hauteur'         => 'la hauteur sous plafond',
    'table'           => "l'emplacement au-dessus d'un meuble",
    'style'           => 'le style',
  ];
  $parts = [];
  foreach ($ignored_keys as $k) {
    if (isset($labels[$k])) $parts[] = $labels[$k];
  }
  if (empty($parts)) return '';
  return "\nRÉPONSES ÉLARGIES POUR TROUVER DES MODÈLES : " . implode(', ', $parts)
       . "\n(le visiteur avait répondu, mais le filtre direct ne ramenait rien → on a relâché ces contraintes pour pouvoir lui montrer des modèles)\n";
}

// Bloc consigne adaptative à ajouter au system prompt advice + chat.
// Round 3 — Lot A : réécriture complète. L'IA ne révèle JAMAIS le mécanisme
// interne de filtrage au visiteur. Présentation comme proposition d'artisan,
// sur-mesure comme porte de sortie naturelle (jamais aveu d'échec).
function sapi_megafilter_adaptive_consigne_block() {
  $out  = "\nPRÉSENTATION DE LA SÉLECTION AU VISITEUR :\n";
  $out .= "- Si AUCUN produit présenté au visiteur (liste vide) : propose chaleureusement le sur-mesure (Robin peut créer un modèle qui n'existe pas dans le catalogue), sans baratin, sans promesse de modèles imaginaires.\n";
  $out .= "- Si la sélection présentée correspond EXACTEMENT à la demande de départ : présente la sélection naturellement.\n";
  $out .= "- Si la sélection s'écarte de la demande de départ (sans dire pourquoi !) : présente la sélection comme une proposition d'artisan. Tu peux reconnaître la demande initiale en intro (\"tu cherches plutôt du moderne pour ta cuisine\") puis présenter ta sélection, et invite le visiteur au sur-mesure comme alternative naturelle si la sélection ne lui plaît pas.\n";

  $out .= "\nVOCABULAIRE STRICTEMENT INTERDIT — ne le mentionne JAMAIS au visiteur :\n";
  $out .= "- \"j'ai élargi\", \"j'ai relâché\", \"j'ai mis de côté\", \"j'ai assoupli\", \"j'ai été plus large sur…\", \"j'ai un peu débordé sur d'autres pièces\"\n";
  $out .= "- \"comme je n'avais pas grand-chose à te montrer\", \"sinon je n'avais que 2-3 modèles\"\n";
  $out .= "- \"contrainte\", \"paramètre\", \"préférence\", \"filtre\", \"critère\", \"sélection élargie\", \"élargissement\"\n";
  $out .= "Le visiteur ne sait pas comment fonctionne le filtre en interne, et n'a pas à le savoir. Tu présentes simplement ta sélection.\n";

  $out .= "\nEXEMPLES CANONIQUES (le ton, pas le texte exact à recopier) :\n";
  $out .= "- \"Tu cherches plutôt du moderne pour ta cuisine. Voilà ma sélection — si tu ne trouves pas exactement ce que tu imaginais, on peut aussi imaginer quelque chose de sur-mesure ensemble.\"\n";
  $out .= "- \"Voilà ma proposition pour ton salon. Pense à vérifier les dimensions sur chaque fiche pour être sûr du rendu — et n'hésite pas à me dire si tu veux qu'on en parle ensemble.\"\n";
  $out .= "- \"Voilà ce que je te propose. Si tu cherches quelque chose de très précis qui ne figure pas dans ces modèles, on peut imaginer du sur-mesure ensemble.\"\n";
  $out .= "- \"Voilà ma sélection. Si tu as besoin de quelque chose de très spécifique pour ton projet, je peux te faire du sur-mesure — il suffit qu'on échange ensemble.\"\n";

  $out .= "\nRÈGLES MÉTIER vs RÉPONSES ÉLARGIES :\n";
  $out .= "- Si la clé `piece` figure parmi les RÉPONSES ÉLARGIES, les règles métier par pièce ont été assouplies volontairement pour pouvoir te montrer une sélection. N'oppose donc PAS au visiteur les règles \"pas de lampe à poser en cuisine\" ou autres règles ampoule par pièce. Présente la sélection telle qu'elle, sans contredire la grille.\n";

  $out .= "\nCONTENU DE LA PHRASE :\n";
  $out .= "- N'ÉNUMÈRE PAS chaque réponse du projet. Va à l'essentiel.\n";
  $out .= "- Si le style est \"Pas de préférence\" (ou \"neutre\"), NE LE MENTIONNE PAS du tout — ce n'est pas une info.\n";
  $out .= "- Évite les tournures qui confondent une caractéristique de la PIÈCE avec une RÉPONSE du visiteur. Exemple à NE PAS faire : \"ta cuisine est au mur\" (la cuisine n'est PAS au mur — c'est l'arrivée électrique qui est au mur, ce qui détermine le type de produit côté filtre).\n";
  $out .= "- Dans TOUS les cas : NE NOMME PAS de modèle précis du catalogue — le visiteur les voit dans la grille juste après.\n";
  $out .= "- Le sur-mesure est ta porte de sortie naturelle quand la sélection s'écarte de la demande initiale. JAMAIS comme un aveu d'échec, toujours comme une alternative que tu peux proposer.\n";

  return $out;
}

/* ── Endpoint F2a-bis : phrase IA conseillère unique, appelée à la sortie modale
   ─────────────────────────────────────────────────────────────────────────────
   - Modèle Sonnet (qualité du ton, sortie unique → on peut se permettre le coût)
   - Input :  answers + labels + matching_product_ids + ignored_answers
              (+ conversation optionnel en sortie de S2)
   - Output : { advice_text: "..." }
   - Pas de cache serveur — chaque parcours est unique
   - Fallback : texte générique correspondant à la pièce
   ───────────────────────────────────────────────────────────────────────────── */
add_action('wp_ajax_sapi_megafilter_advice', 'sapi_ajax_megafilter_advice');
add_action('wp_ajax_nopriv_sapi_megafilter_advice', 'sapi_ajax_megafilter_advice');

function sapi_ajax_megafilter_advice() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-megafilter')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  $answers_raw = isset($_POST['answers']) ? json_decode(wp_unslash($_POST['answers']), true) : [];
  $labels_raw  = isset($_POST['labels'])  ? json_decode(wp_unslash($_POST['labels']),  true) : [];
  list($answers, $labels) = sapi_megafilter_sanitize_project($answers_raw, $labels_raw);

  $piece = isset($answers['piece']) ? $answers['piece'] : '';

  // Fallback si projet sans pièce
  if (empty($answers) || empty($piece)) {
    wp_send_json_success([
      'advice_text' => sapi_megafilter_generic_advice_for($piece),
    ]);
    return;
  }

  // Rate-limit : on tombe sur le texte générique de la pièce (pas d'erreur visible)
  if (!sapi_guide_check_rate_limit()) {
    wp_send_json_success([
      'advice_text' => sapi_megafilter_generic_advice_for($piece),
    ]);
    return;
  }

  // Conversation optionnelle (présente en sortie de S2 mode texte libre)
  $conversation_raw = isset($_POST['conversation']) ? json_decode(wp_unslash($_POST['conversation']), true) : [];
  $conversation_block = '';
  if (is_array($conversation_raw) && !empty($conversation_raw)) {
    $lines = [];
    foreach ($conversation_raw as $msg) {
      if (!isset($msg['role'], $msg['content'])) continue;
      $role = ($msg['role'] === 'assistant') ? 'Robin' : 'Visiteur';
      $lines[] = $role . ' : ' . sanitize_textarea_field($msg['content']);
    }
    if (!empty($lines)) {
      $conversation_block = "\n\nÉCHANGES avec le visiteur :\n" . implode("\n", $lines);
    }
  }

  $project_text = sapi_megafilter_format_project_text($answers, $labels);

  // Contexte filtre : matching IDs + ignored answers (envoyés par le JS, qui
  // est la source de vérité du filtrage côté client). On enrichit le prompt
  // pour que l'IA sache combien de produits sont présentés au visiteur et
  // si des contraintes ont été élargies — pour adapter sa phrase en conséquence.
  $matching_ids = sapi_megafilter_parse_matching_ids(isset($_POST['matching_product_ids']) ? wp_unslash($_POST['matching_product_ids']) : '');
  $ignored_keys = sapi_megafilter_parse_ignored_answers(isset($_POST['ignored_answers']) ? wp_unslash($_POST['ignored_answers']) : '');
  $all_products = sapi_guide_query_all_products([]);
  $catalog_split_block  = sapi_megafilter_format_catalog_split($all_products, $matching_ids);
  $ignored_answers_block = sapi_megafilter_format_ignored_answers($ignored_keys);

  // Injecte ton + savoir + regles V2 en tête (PAS exemples : équivalent V2
  // sapi_robin_call_recommendation qui n'inclut pas les exemples — sortie
  // JSON courte à 1-2 phrases, pas besoin d'amorces conversationnelles).
  $system_prompt  = sapi_megafilter_load_v2_prompts(false);

  $system_prompt .= "Tu es Robin, artisan menuisier lyonnais qui fabrique des luminaires en bois à la découpe laser dans son atelier de Lyon.\n";
  $system_prompt .= "Un visiteur vient de te décrire son projet (via questionnaire ou conversation libre). Tu lui présentes ta sélection en 1-2 phrases personnalisées.\n\n";
  $system_prompt .= "TON :\n";
  $system_prompt .= "- Tutoiement, chaleureux, artisan passionné, jamais vendeur\n";
  $system_prompt .= "- Évoque concrètement ce que tu as compris du projet et pourquoi ta sélection lui correspond\n";
  $system_prompt .= "- Tu peux mentionner une essence de bois, un format, une ambiance — mais PAS de modèle précis (le visiteur va les voir juste après)\n";
  $system_prompt .= "- Pas d'emoji, pas de markdown, pas de signature (elle est ajoutée séparément côté front)\n";
  $system_prompt .= "- Format : 1 à 2 phrases, max 300 caractères\n\n";

  $system_prompt .= sapi_megafilter_adaptive_consigne_block();

  $system_prompt .= "\nFORMAT DE RÉPONSE (JSON strict, sans markdown) :\n";
  $system_prompt .= "{ \"advice_text\": \"...\" }\n\n";

  $system_prompt .= "⚠️ FORMAT DE SORTIE — IMPÉRATIF :\n";
  $system_prompt .= "Ta réponse DOIT être UNIQUEMENT le JSON ci-dessus. RIEN d'autre :\n";
  $system_prompt .= "- PAS de prose avant le JSON\n";
  $system_prompt .= "- PAS de prose après le JSON\n";
  $system_prompt .= "- PAS de bloc ```markdown autour\n";
  $system_prompt .= "Premier caractère = `{`, dernier caractère = `}`. Point.\n";

  $user_msg  = "PROJET DU VISITEUR :\n" . $project_text;
  $user_msg .= $ignored_answers_block;
  $user_msg .= $catalog_split_block;
  $user_msg .= $conversation_block;

  $ai_text = sapi_megafilter_call_claude(
    'claude-sonnet-4-6',
    $system_prompt,
    [['role' => 'user', 'content' => $user_msg]],
    512
  );

  $advice = '';
  if ($ai_text) {
    $parsed = sapi_megafilter_parse_json($ai_text);
    if ($parsed && isset($parsed['advice_text']) && is_string($parsed['advice_text'])) {
      $advice = sanitize_textarea_field($parsed['advice_text']);
    }
  }

  // Fallback gracieux si IA plante ou format inattendu
  if (empty($advice)) {
    $advice = sapi_megafilter_generic_advice_for($piece);
  }

  wp_send_json_success(['advice_text' => $advice]);
}

/* ── F2b Phase 2 : conseils de style pré-générés pour le récap fiche produit
   ─────────────────────────────────────────────────────────────────────────────
   Pattern d'origine pré-F1c (assets/guide-conseils.json → pg_style:*) : textes
   fixes affichés immédiatement, ZÉRO appel IA. La modale produit reproduit
   à l'identique ce comportement statique.
   ───────────────────────────────────────────────────────────────────────────── */

function sapi_megafilter_get_style_conseils() {
  return [
    'moderne' => __("Le Peuplier, clair et lumineux, s'accordera parfaitement avec votre intérieur moderne.", 'theme-sapi-maison'),
    'ancien'  => __("L'Okoumé, chaud et ambré, s'intègrera naturellement dans votre intérieur aux tons chauds.", 'theme-sapi-maison'),
    'neutre'  => __("Les deux essences sont belles — vous pourrez voir les photos de chaque finition sur la fiche.", 'theme-sapi-maison'),
  ];
}

function sapi_megafilter_get_size_conseils() {
  return [
    'petite'  => __("Cette taille s'adapte bien à un petit espace sans être trop imposante.", 'theme-sapi-maison'),
    'moyenne' => __("Cette taille standard convient à la plupart des pièces.", 'theme-sapi-maison'),
    'grande'  => __("Cette grande taille créera un point focal fort dans ton espace.", 'theme-sapi-maison'),
  ];
}

/* ── Endpoint F2a-3 : soumission form sur-mesure (email Robin) ────────────────── */
add_action('wp_ajax_sapi_megafilter_surmesure', 'sapi_ajax_megafilter_surmesure');
add_action('wp_ajax_nopriv_sapi_megafilter_surmesure', 'sapi_ajax_megafilter_surmesure');

function sapi_ajax_megafilter_surmesure() {
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-megafilter')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
    return;
  }

  // Rate limit anti-spam : même limite que l'IA (1h glissante côté IP)
  if (!sapi_guide_check_rate_limit()) {
    wp_send_json_error([
      'message'  => 'rate_limit',
      'fallback' => 'Trop de demandes pour le moment. Réessaie dans une heure ou écris-moi directement à robin@atelier-sapi.fr.',
    ]);
    return;
  }

  $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
  $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

  if (!is_email($email)) {
    wp_send_json_error(['message' => 'Email invalide']);
    return;
  }

  $description_len = mb_strlen($description);
  if ($description_len > 0 && $description_len > 1500) {
    wp_send_json_error(['message' => 'Description trop longue']);
    return;
  }

  // Honeypot anti-bot : si le champ "website" est rempli, on retourne success silencieusement
  if (!empty($_POST['website'])) {
    wp_send_json_success(['message' => 'Merci.']);
    return;
  }

  // Snapshot projet (optionnel)
  $project_text_snapshot = '';
  if (isset($_POST['project'])) {
    $project_raw = json_decode(wp_unslash($_POST['project']), true);
    if (is_array($project_raw)) {
      $answers_raw = isset($project_raw['answers']) ? $project_raw['answers'] : [];
      $labels_raw  = isset($project_raw['labels'])  ? $project_raw['labels']  : [];
      list($answers, $labels) = sapi_megafilter_sanitize_project($answers_raw, $labels_raw);
      if (!empty($answers)) {
        $project_text_snapshot = sapi_megafilter_format_project_text($answers, $labels);
      }
    }
  }

  // Construction de l'email à Robin
  $to      = 'robin@atelier-sapi.fr';
  $subject = '[Sur-mesure] Nouvelle demande de ' . $email;
  $body    = "Nouvelle demande sur-mesure reçue depuis /mes-creations/\n\n";
  $body   .= "── Email visiteur ──\n" . $email . "\n\n";
  if ($project_text_snapshot !== '') {
    $body .= "── Projet en cours ──\n" . $project_text_snapshot . "\n\n";
  }
  $body   .= "── Description ──\n" . ($description !== '' ? $description : '(pas de description)') . "\n\n";
  $body   .= "── Source ──\n";
  $body   .= 'Page : ' . (isset($_POST['source_url']) ? esc_url_raw(wp_unslash($_POST['source_url'])) : '(non transmise)') . "\n";
  $body   .= 'IP : ' . (isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '?') . "\n";
  $body   .= 'Date : ' . current_time('mysql') . "\n";

  $headers = [
    'Content-Type: text/plain; charset=UTF-8',
    'Reply-To: ' . $email,
  ];

  $sent = wp_mail($to, $subject, $body, $headers);

  if (!$sent) {
    error_log('Sapi MegaFilter sur-mesure : wp_mail failed pour ' . $email);
    wp_send_json_error([
      'message'  => 'send_failed',
      'fallback' => 'L\'envoi a échoué. Tu peux m\'écrire directement à robin@atelier-sapi.fr.',
    ]);
    return;
  }

  wp_send_json_success([
    'message' => 'Reçu, Robin t\'écrit sous 48h.',
  ]);
}

/* ═══════════════════════════════════════════════════════════
   ROBIN CONSEILLER V2 — Filtrage produits pour "Ma sélection"
═══════════════════════════════════════════════════════════ */
add_action('wp_ajax_sapi_robin_filter_products', 'sapi_ajax_robin_filter_products');
add_action('wp_ajax_nopriv_sapi_robin_filter_products', 'sapi_ajax_robin_filter_products');

function sapi_ajax_robin_filter_products() {
  // 1. Nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
  }

  // 2. Parse answers
  $answers = [];
  if (!empty($_POST['answers'])) {
    $raw = json_decode(sanitize_text_field(wp_unslash($_POST['answers'])), true);
    if (is_array($raw)) {
      foreach ($raw as $k => $v) {
        $answers[sanitize_key($k)] = sanitize_text_field($v);
      }
    }
  }

  if (empty($answers)) {
    wp_send_json_error(['message' => 'Pas de réponses']);
  }

  // 3. Normaliser taille_escalier → taille
  if (!empty($answers['taille_escalier'])) {
    $answers['taille'] = $answers['taille_escalier'] === 'ouvert' ? 'grande' : 'petite';
  }

  // "Je ne sais pas" → pas de filtre taille
  if (isset($answers['taille']) && $answers['taille'] === 'ne-sais-pas') {
    unset($answers['taille']);
  }

  // 4. Filtrage via le pipeline existant
  require_once get_template_directory() . '/inc/guide-data.php';
  $categories = sapi_guide_get_categories($answers);
  $result = sapi_guide_query_products($answers, $categories);
  $products = isset($result['products']) ? $result['products'] : [];

  // 5. Extraire les IDs
  $ids = array_map(function($p) { return $p['id']; }, $products);

  wp_send_json_success([
    'product_ids' => $ids,
    'count'       => count($ids),
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
  $ai_call_count = isset($_POST['ai_call_count']) ? (int) $_POST['ai_call_count'] : 0;

  if (empty($step_id)) {
    wp_send_json_error(['message' => 'step_id manquant']);
  }

  // 5. Si pas d'IA (rate limit), renvoyer un fallback
  if (!$ai_allowed) {
    wp_send_json_success([
      'conseil_text' => 'Le service est temporairement indisponible. Pour une réponse rapide, contactez Robin directement.',
      'link_url'     => null,
      'link_label'   => null,
      'suggested_buttons' => [
        ['label' => 'Contacter Robin', 'url' => '/contact/'],
      ],
      'next_step_id' => 'hors_parcours',
      'answered_steps' => new \stdClass(),
    ]);
  }

  // 6. Recommendation finale — pipeline complet
  if ($step_id === 'recommendation') {
    sapi_robin_handle_recommendation($answers, $ai_allowed);
    return; // sapi_robin_handle_recommendation fait son propre wp_send_json
  }

  // 7. Étapes normales — construire le prompt et appeler Claude
  $system_prompt = sapi_robin_build_step_prompt($step_id, $answers, $opening_context, $context_data, $user_message, $ai_call_count);

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

  if (!$result || empty($result['conseil_text'])) {
    wp_send_json_success([
      'conseil_text' => 'Je ne peux pas répondre à cette question. Le mieux est d\'en parler directement avec Robin.',
      'link_url'     => null,
      'link_label'   => null,
      'suggested_buttons' => [
        ['label' => 'Contacter Robin', 'url' => '/contact/'],
        ['label' => 'Reprendre le questionnaire', 'slug' => 'restart', 'step_id' => 'piece'],
      ],
      'next_step_id' => 'hors_parcours',
      'answered_steps' => new \stdClass(),
    ]);
  }

  // Filtrer les boutons et réponses invalides avant d'envoyer
  $result = sapi_robin_validate_response($result);

  wp_send_json_success($result);
}

/**
 * Robin V2 — Valide et nettoie la réponse IA.
 * Supprime les boutons avec des slugs invalides et les answered_steps incorrects.
 */
function sapi_robin_validate_response($result) {
  require_once get_template_directory() . '/inc/guide-data.php';
  $all_steps = sapi_guide_get_steps();

  // Construire la map des slugs valides par step_id
  $valid_slugs = [];
  foreach ($all_steps as $s) {
    $valid_slugs[$s['id']] = array_map(function($c) { return $c['slug']; }, $s['choices']);
  }

  // URLs valides pour les boutons liens
  $valid_urls = ['/contact/', '/mes-creations/', '/mes-creations/?robin_selection=1', '/sur-mesure/',
    '/categorie-produit/suspensions/', '/categorie-produit/appliques/',
    '/categorie-produit/lampadaires/', '/categorie-produit/lampes-a-poser/'];

  // Filtrer suggested_buttons
  if (!empty($result['suggested_buttons']) && is_array($result['suggested_buttons'])) {
    $clean_buttons = [];
    foreach ($result['suggested_buttons'] as $btn) {
      if (!empty($btn['url'])) {
        // Bouton lien — vérifier que l'URL est dans la liste
        if (in_array($btn['url'], $valid_urls, true)) {
          $clean_buttons[] = $btn;
        }
      } elseif (!empty($btn['step_id']) && !empty($btn['slug'])) {
        // Bouton questionnaire — vérifier que le slug est valide pour cette étape
        if (isset($valid_slugs[$btn['step_id']]) && in_array($btn['slug'], $valid_slugs[$btn['step_id']], true)) {
          $clean_buttons[] = $btn;
        }
      } elseif (!empty($btn['label'])) {
        // Bouton conversation — juste un label, renvoie comme texte libre
        $clean_buttons[] = ['label' => sanitize_text_field($btn['label'])];
      }
    }
    $result['suggested_buttons'] = $clean_buttons;
  }

  // Filtrer answered_steps
  if (!empty($result['answered_steps']) && is_array($result['answered_steps'])) {
    $clean_answers = [];
    foreach ($result['answered_steps'] as $step_id => $slug) {
      if (isset($valid_slugs[$step_id]) && in_array($slug, $valid_slugs[$step_id], true)) {
        $clean_answers[$step_id] = $slug;
      }
    }
    $result['answered_steps'] = $clean_answers;
  }

  return $result;
}

/**
 * Robin V2 — Build system prompt for a single step.
 */
/**
 * Robin V2 — Recommandation finale : filtrage + Claude + produits.
 */
function sapi_robin_handle_recommendation($answers, $ai_allowed) {
  require_once get_template_directory() . '/inc/guide-data.php';

  // Normaliser taille_escalier → taille
  if (!empty($answers['taille_escalier'])) {
    $answers['taille'] = $answers['taille_escalier'] === 'ouvert' ? 'grande' : 'petite';
  }

  // Détecter si le projet relève du sur mesure
  $piece   = isset($answers['piece']) ? $answers['piece'] : '';
  $taille  = isset($answers['taille']) ? $answers['taille'] : '';
  $hauteur = isset($answers['hauteur']) ? $answers['hauteur'] : '';
  $is_sur_mesure = ($piece === 'escalier')
    || ($taille === 'grande' && in_array($hauteur, ['haute', 'confortable'], true))
    || ($taille === 'grande' && $piece === 'escalier');

  if ($is_sur_mesure) {
    sapi_robin_handle_sur_mesure($answers, $ai_allowed);
    return;
  }

  // Pipeline de filtrage (parcours standard)
  $categories = sapi_guide_get_categories($answers);
  $result = sapi_guide_query_products($answers, $categories);
  $products = isset($result['products']) ? $result['products'] : [];

  $picked = sapi_guide_pick_four($products, 4);

  if (empty($picked)) {
    wp_send_json_success([
      'conseil_text' => 'Je n\'ai pas trouvé de luminaire qui corresponde exactement à vos critères. Explorez le catalogue ou contactez Robin directement.',
      'products' => [],
    ]);
    return;
  }

  // Préparer les données produit pour le front
  $front_products = [];
  foreach ($picked as $p) {
    $front_products[] = [
      'id'              => $p['id'],
      'title'           => $p['title'],
      'price'           => $p['price'],
      'image'           => $p['image'],
      'hover_image'     => isset($p['hover_image']) ? $p['hover_image'] : '',
      'ambiance'        => isset($p['ambiance']) ? $p['ambiance'] : '',
      'permalink'       => $p['permalink'],
      'variation_label' => isset($p['variation_label']) ? $p['variation_label'] : '',
      'size_label'      => isset($p['size_label']) ? $p['size_label'] : '',
      'category_label'  => isset($p['category_label']) ? $p['category_label'] : '',
      'reason'          => '', // Sera rempli par Claude
    ];
  }

  // Appeler Claude pour le texte A + texte B par produit
  if ($ai_allowed) {
    $ai_result = sapi_robin_call_recommendation($picked, $answers);
    if ($ai_result) {
      // Texte A
      $conseil_text = isset($ai_result['conseil_text']) ? $ai_result['conseil_text'] : '';

      // Textes B par produit
      if (!empty($ai_result['products'])) {
        foreach ($ai_result['products'] as $ai_prod) {
          $pid = isset($ai_prod['id']) ? (int) $ai_prod['id'] : 0;
          $reason = isset($ai_prod['reason']) ? $ai_prod['reason'] : '';
          for ($i = 0; $i < count($front_products); $i++) {
            if ($front_products[$i]['id'] === $pid) {
              $front_products[$i]['reason'] = $reason;
              break;
            }
          }
        }
      }

      wp_send_json_success([
        'conseil_text' => $conseil_text,
        'products'     => $front_products,
      ]);
      return;
    }
  }

  // Fallback sans IA
  wp_send_json_success([
    'conseil_text' => 'Voici les luminaires qui correspondent le mieux à votre projet. Contactez Robin si vous souhaitez en discuter.',
    'products'     => $front_products,
  ]);
}

/**
 * Robin V2 — Gestion du parcours "sur mesure" (escalier, grande pièce + plafond haut).
 */
function sapi_robin_handle_sur_mesure($answers, $ai_allowed) {
  $label_map = [
    'piece' => 'Pièce', 'taille' => 'Taille', 'taille_escalier' => 'Type escalier',
    'eclairage' => 'Éclairage', 'sortie' => 'Installation', 'hauteur' => 'Hauteur plafond',
    'table' => 'Au-dessus table/îlot', 'style' => 'Style',
  ];
  $answers_text = '';
  foreach ($answers as $k => $v) {
    $label = isset($label_map[$k]) ? $label_map[$k] : $k;
    $answers_text .= '- ' . $label . ' : ' . $v . "\n";
  }

  $conseil_text = '';
  if ($ai_allowed) {
    $conseil_text = sapi_robin_call_sur_mesure($answers_text);
  }
  if (empty($conseil_text)) {
    // Fallback sans IA
    $conseil_text = 'Ce type de projet mérite une attention particulière. Robin a déjà réalisé des luminaires pour des situations similaires. Le mieux est d\'en discuter directement avec lui.';
  }

  wp_send_json_success([
    'recommend_type' => 'sur_mesure',
    'conseil_text'   => $conseil_text,
    'products'       => [],
  ]);
}

/**
 * Robin V2 — Appel Claude pour le texte sur mesure.
 */
function sapi_robin_call_sur_mesure($answers_text) {
  $theme_dir = get_template_directory();
  $ton = @file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt') ?: '';

  $prompt  = $ton . "\n\n";
  $prompt .= "CONTEXTE : Le client a un projet qui relève du sur mesure. Robin crée des luminaires sur mesure pour ce genre de situations.\n\n";
  $prompt .= "RÉPONSES DU CLIENT :\n" . $answers_text . "\n";
  $prompt .= "MISSION : Rédige un court texte (3-4 phrases MAX) qui :\n";
  $prompt .= "1. Valide que le sur mesure est la bonne option pour ce projet\n";
  $prompt .= "2. Rassure le client en mentionnant que Robin a déjà créé des luminaires pour des situations similaires\n";
  $prompt .= "3. Donne envie de contacter Robin pour en discuter\n\n";
  $prompt .= "RÈGLES ABSOLUES :\n";
  $prompt .= "- Tu ne proposes PAS de luminaire, tu ne décris PAS ce que Robin pourrait créer. C'est la mission de Robin, pas la tienne.\n";
  $prompt .= "- Tu parles du projet du client (sa pièce, ses contraintes) et tu expliques pourquoi le sur mesure est adapté.\n";
  $prompt .= "- Pas de guillemets « ». Pas de markdown. Texte brut uniquement.\n";
  $prompt .= "- Réponds UNIQUEMENT avec le texte, rien d'autre (pas de JSON, pas de commentaire).\n";

  $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
  if (empty($api_key)) return '';

  $body = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 256,
    'system'     => $prompt,
    'messages'   => [
      ['role' => 'user', 'content' => 'Voici mon projet. Est-ce que le sur mesure est adapté ?'],
    ],
  ];

  $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
    'timeout' => 20,
    'headers' => [
      'Content-Type'      => 'application/json',
      'x-api-key'         => $api_key,
      'anthropic-version'  => '2023-06-01',
    ],
    'body' => wp_json_encode($body),
  ]);

  if (is_wp_error($response)) return '';

  $resp_body = json_decode(wp_remote_retrieve_body($response), true);
  if (!$resp_body || empty($resp_body['content'][0]['text'])) return '';

  return trim($resp_body['content'][0]['text']);
}

/**
 * Robin V2 — Appel Claude pour la recommandation finale.
 */
function sapi_robin_call_recommendation($products, $answers) {
  $theme_dir = get_template_directory();
  $ton    = @file_get_contents($theme_dir . '/assets/guide-prompt-ton.txt') ?: '';
  $savoir = @file_get_contents($theme_dir . '/assets/guide-prompt-savoir.txt') ?: '';
  $regles = @file_get_contents($theme_dir . '/assets/guide-prompt-regles.txt') ?: '';

  // Construire la liste des produits pour le prompt
  $product_lines = [];
  foreach ($products as $p) {
    $line = '- ' . $p['title'] . ' (ID: ' . $p['id'] . ')';
    $line .= ' | Catégorie: ' . ($p['category_label'] ?? '');
    $line .= ' | Format: ' . ($p['format'] ?? '');
    $line .= ' | Ampoule: ' . ($p['type_ampoule'] ?? '');
    if (!empty($p['variation_label'])) $line .= ' | Essence: ' . $p['variation_label'];
    $line .= ' | Ventes: ' . ($p['total_sales'] ?? 0);
    $product_lines[] = $line;
  }

  // Résumé des réponses
  $label_map = [
    'piece' => 'Pièce', 'taille' => 'Taille', 'taille_escalier' => 'Type escalier',
    'eclairage' => 'Éclairage', 'sortie' => 'Installation', 'hauteur' => 'Hauteur plafond',
    'table' => 'Au-dessus table/îlot', 'style' => 'Style',
  ];
  $answers_text = '';
  foreach ($answers as $k => $v) {
    $label = isset($label_map[$k]) ? $label_map[$k] : $k;
    $answers_text .= '- ' . $label . ' : ' . $v . "\n";
  }

  $prompt = $ton . "\n\n" . $savoir . "\n\n" . $regles . "\n\n";
  $prompt .= "PRODUITS SÉLECTIONNÉS POUR CE CLIENT :\n" . implode("\n", $product_lines) . "\n\n";
  $prompt .= "RÉPONSES DU CLIENT :\n" . $answers_text . "\n";
  $prompt .= "MISSION : Rédige une recommandation finale personnalisée.\n\n";
  $prompt .= "FORMAT DE RÉPONSE (JSON strict, pas de markdown) :\n";
  $prompt .= "{\n";
  $prompt .= '  "conseil_text": "Texte A : 2 phrases MAXIMUM. Synthèse technique courte. Ne cite pas les noms des produits.",' . "\n";
  $prompt .= '  "products": [' . "\n";
  $prompt .= '    { "id": 123, "reason": "Texte B : 1 seule phrase. Pourquoi ce modèle précis convient." }' . "\n";
  $prompt .= '  ]' . "\n";
  $prompt .= "}\n\n";
  $prompt .= "RÈGLES :\n";
  $prompt .= "- Le conseil_text ne mentionne PAS les noms de produits (ils sont affichés à côté).\n";
  $prompt .= "- Chaque reason est personnalisée (pas générique).\n";
  $prompt .= "- Pas de guillemets « » (ajoutés côté front).\n";
  $prompt .= "- Pas de markdown.\n";
  $prompt .= "- Les IDs dans products doivent correspondre exactement aux IDs des produits fournis.\n";

  // Appel avec plus de tokens (la recommandation est plus longue)
  $api_key = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
  if (empty($api_key)) return null;

  $body = [
    'model'      => 'claude-sonnet-4-6',
    'max_tokens' => 2048,
    'system'     => $prompt,
    'messages'   => [
      ['role' => 'user', 'content' => 'Voici mon projet complet. Recommande-moi des luminaires.'],
    ],
  ];

  $response = wp_remote_post('https://api.anthropic.com/v1/messages', [
    'timeout' => 45,
    'headers' => [
      'Content-Type'      => 'application/json',
      'x-api-key'         => $api_key,
      'anthropic-version'  => '2023-06-01',
    ],
    'body' => wp_json_encode($body),
  ]);

  if (is_wp_error($response)) {
    error_log('Robin V2 Reco API error: ' . $response->get_error_message());
    return null;
  }

  $status   = wp_remote_retrieve_response_code($response);
  $raw_body = wp_remote_retrieve_body($response);

  if ($status !== 200) {
    error_log('Robin V2 Reco API HTTP ' . $status . ': ' . $raw_body);
    return null;
  }

  $data = json_decode($raw_body, true);
  if (!isset($data['content'][0]['text'])) return null;

  $text = $data['content'][0]['text'];
  $text = preg_replace('/^```json\s*/i', '', trim($text));
  $text = preg_replace('/\s*```$/i', '', $text);

  $parsed = json_decode(trim($text), true);
  if (!$parsed || !isset($parsed['conseil_text'])) {
    // Fallback : essayer d'extraire conseil_text par regex
    if (preg_match('/"conseil_text"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $text, $m)) {
      return ['conseil_text' => stripslashes($m[1]), 'products' => []];
    }
    return ['conseil_text' => 'Explorez les luminaires sélectionnés ci-dessous, ou contactez Robin pour un conseil personnalisé.', 'products' => []];
  }

  return $parsed;
}

function sapi_robin_build_step_prompt($step_id, $answers, $opening_context, $context_data, $user_message, $ai_call_count = 0) {
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

    // Compteur d'échanges IA — limite à 3
    $exchange_num = $ai_call_count + 1;
    $prompt .= "\nÉCHANGE IA N°" . $exchange_num . " sur 3 maximum.\n";
    if ($exchange_num >= 3) {
      $prompt .= "C'est ton DERNIER échange. Tu DOIS conclure maintenant :\n";
      $prompt .= "- Plus de boutons conversation. Uniquement des boutons liens (catalogue, contact, sur-mesure) ou questionnaire.\n";
      $prompt .= "- Oriente le client vers une action concrète : voir les modèles, contacter Robin, ou imaginer un sur-mesure.\n";
    } elseif ($exchange_num === 2) {
      $prompt .= "C'est ton avant-dernier échange. Commence à orienter le client vers une action.\n";
    }
    $prompt .= "\n";
    $prompt .= "{\n";
    $prompt .= '  "conseil_text": "Ta réponse personnalisée (2-5 phrases, style Robin)",' . "\n";
    $prompt .= '  "link_url": "/mes-creations/suspensions/" ou null,' . "\n";
    $prompt .= '  "link_label": "Voir les suspensions" ou null,' . "\n";
    $prompt .= '  "answered_steps": { "piece": "cuisine", "taille": "petite" } ou {} si rien déduit,' . "\n";
    $prompt .= '  "suggested_buttons": [' . "\n";
    $prompt .= '    { "label": "Voir les suspensions", "url": "/categorie-produit/suspensions/" },' . "\n";
    $prompt .= '    { "label": "Contacter Robin", "url": "/contact/" },' . "\n";
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
    $prompt .= "- suggested_buttons : trois types possibles :\n";
    $prompt .= "  - Bouton lien (ouvre une page) : { \"label\": \"...\", \"url\": \"/chemin/\" }\n";
    $prompt .= "  - Bouton questionnaire (valide une étape) : { \"label\": \"...\", \"slug\": \"...\", \"step_id\": \"...\" }\n";
    $prompt .= "  - Bouton conversation (continue la discussion) : { \"label\": \"...\" } — le label est renvoyé comme message\n";
    $prompt .= "  URLs valides : /contact/, /mes-creations/?robin_selection=1, /sur-mesure/\n";
    $prompt .= "- BOUTONS PAR DÉFAUT : si tu n'as pas de meilleure idée, utilise ces boutons liens :\n";

    // Injecter les boutons par défaut selon le contexte
    $taille = isset($answers['taille']) ? $answers['taille'] : '';
    $hauteur = isset($answers['hauteur']) ? $answers['hauteur'] : '';
    $show_sur_mesure_prompt = ($taille === 'grande' || $hauteur === 'haute');

    $prompt .= '  { "label": "Voir les modèles filtrés pour votre projet", "url": "/mes-creations/?robin_selection=1" }' . "\n";
    if ($show_sur_mesure_prompt) {
      $prompt .= '  { "label": "Imaginer un modèle sur mesure", "url": "/sur-mesure/" }' . "\n";
    } else {
      $prompt .= '  { "label": "Contacter Robin", "url": "/contact/" }' . "\n";
    }
    $prompt .= "  Tu peux garder ces boutons, les remplacer, ou en ajouter selon ta réponse. Retourne la liste complète dans suggested_buttons.\n";
    $prompt .= "- Si le message est une question hors questionnaire (livraison, prix, sur mesure...), réponds et mets next_step_id à 'hors_parcours'.\n";
  } else {
    $prompt .= "{\n";
    $prompt .= '  "conseil_text": "Ton conseil personnalisé pour cette étape (2-4 phrases, style citation Robin)",' . "\n";
    $prompt .= '  "link_url": "/mes-creations/suspensions/" ou null si pas pertinent,' . "\n";
    $prompt .= '  "link_label": "Voir les suspensions" ou null' . "\n";
    $prompt .= "}\n\n";
  }

  $prompt .= "RÈGLES IMPORTANTES :\n";
  $prompt .= "- Le conseil_text doit être personnel et adapté.\n";
  $prompt .= "- Ne répète pas la question, donne directement le conseil.\n";
  $prompt .= "- Le link_url doit pointer vers une page existante du site (catégorie, page nos-créations, etc.) ou null.\n";
  $prompt .= "- Pas de markdown. Texte brut uniquement.\n";
  $prompt .= "- Pas de guillemets « » dans le texte (ils sont ajoutés côté front).\n";
  $prompt .= "- Les labels des boutons : première lettre majuscule, reste en minuscule. Exemple : \"Voir les suspensions\", \"Contacter Robin\".\n";
  $prompt .= "- CRITIQUE : ta réponse doit être UNIQUEMENT du JSON valide. Pas de texte avant, pas de texte après, pas de commentaire, pas d'analyse. Juste le JSON.\n";
  $prompt .= "- Tu es l'assistant de Robin, l'artisan de l'Atelier Sâpi. Tu parles directement au visiteur, naturellement, sans prétendre être Robin. Tu connais bien ses créations et tu guides le client. Ne dis jamais 'le client', 'mon analyse', 'voici ma réflexion'. Quand tu parles de Robin ou de son travail, utilise la 3e personne (\"Robin\", \"son atelier\"). Pour tes recommandations, utilise le \"je\" naturellement (\"je vous recommande\").\n";

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

  // Nettoyer les code fences
  $text = preg_replace('/^```json\s*/i', '', trim($text));
  $text = preg_replace('/\s*```$/i', '', $text);

  // Essai 1 : parser directement
  $parsed = json_decode(trim($text), true);
  if ($parsed && isset($parsed['conseil_text'])) {
    return $parsed;
  }

  // Essai 2 : extraire le JSON du texte (si Claude a mis du texte autour)
  if (preg_match('/\{[\s\S]*"conseil_text"[\s\S]*\}/s', $text, $match)) {
    $parsed = json_decode($match[0], true);
    if ($parsed && isset($parsed['conseil_text'])) {
      return $parsed;
    }
  }

  // Essai 3 : extraire juste le conseil_text par regex
  if (preg_match('/"conseil_text"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/s', $text, $m)) {
    return ['conseil_text' => stripslashes($m[1]), 'link_url' => null, 'link_label' => null];
  }

  // Fallback : texte brut nettoyé
  $clean = preg_replace('/\{[\s\S]*\}/', '', $text);
  $clean = trim($clean);
  if (!empty($clean)) {
    return ['conseil_text' => $clean, 'link_url' => null, 'link_label' => null];
  }

  return ['conseil_text' => $text, 'link_url' => null, 'link_label' => null];
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
    default:
      // Round 3 — Lot B : "ne-sais-pas" inclut désormais appliques, par cohérence
      // avec $sapi_filter_rules['cats_by_sortie']['ne-sais-pas'] (Round 2 — N8,
      // commit d8be0ff). Le kit prise électrique (savoir.txt:48, regles.txt:37)
      // permet l'installation d'une applique sans sortie murale dédiée.
      $cats = ['suspensions', 'lampadaires', 'lampesaposer', 'appliques'];
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
  // "Je ne sais pas" → pas de filtre taille (sécurité si appelé directement)
  if (isset($answers['taille']) && $answers['taille'] === 'ne-sais-pas') {
    unset($answers['taille']);
  }

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
    $piece === 'escalier' ||
    ($piece === 'entree' && in_array($hauteur, ['haute', 'confortable'], true)) ||
    ($taille === 'petite' && in_array($hauteur, ['haute', 'confortable'], true))
  );

  if (in_array('suspensions', $categories) && !$allow_vertical) {
    $tax_query[] = [
      'taxonomy' => 'pa_format',
      'field'    => 'slug',
      'terms'    => ['vertical'],
      'operator' => 'NOT IN',
    ];
  }

  // Règle B : exclure format horizontal dans les espaces étroits + hauts
  $exclude_horizontal = (
    ($piece === 'escalier') ||
    ($taille === 'petite' && $hauteur === 'haute')
  );
  if ($exclude_horizontal && in_array('suspensions', $categories)) {
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

    // Ambiance photo for full-width banner
    $pid = $product->get_id();
    $ambiance_photos = sapi_get_product_photos($pid, 'ambiance', 2);
    // Prefer second ambiance photo, fallback to first
    $ambiance_url = isset($ambiance_photos[1]) ? $ambiance_photos[1] : (isset($ambiance_photos[0]) ? $ambiance_photos[0] : '');

    // Hover image (first gallery image for card hover effect)
    $hover_image_url = '';
    $gallery_ids = $product->get_gallery_image_ids();
    if (!empty($gallery_ids)) {
      $hover_image_url = wp_get_attachment_image_url($gallery_ids[0], 'woocommerce_thumbnail');
    }

    // Category label for card
    $cat_names = wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']);
    $cat_label = !empty($cat_names) ? $cat_names[0] : '';

    // Round 2 — 4.2 : prix min raw (float) pour le catalogue passé à l'IA.
    // Pour les produits variables : min des prix de variation (sans taxes,
    // version raw — pas le HTML formaté).
    $price_min_raw = 0.0;
    if ($product->is_type('variable')) {
      $var_prices = $product->get_variation_prices(false);
      if (!empty($var_prices['price'])) {
        $price_min_raw = (float) min($var_prices['price']);
      }
    } else {
      $raw = $product->get_price();
      $price_min_raw = is_numeric($raw) ? (float) $raw : 0.0;
    }

    $products[] = [
      'id'              => $product->get_id(),
      'title'           => $product->get_name(),
      'price'           => $price,
      'price_min_raw'   => $price_min_raw,
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
 * Newsletter opt-in checkbox on checkout (RGPD — opt-in explicite)
 * Case à cocher activement ; la meta _sapi_newsletter_optin est exploitée
 * par sapi_brevo_newsletter_sync_optin() pour pousser le contact dans la
 * liste Brevo #6 "Newsletter" dès la création de la commande.
 */
add_action('woocommerce_init', function () {
  if (!function_exists('woocommerce_register_additional_checkout_field')) return;

  woocommerce_register_additional_checkout_field([
    'id'       => 'sapi-maison/newsletter-optin',
    'label'    => 'Je souhaite recevoir des nouvelles de l\'atelier et de jolies idées pour m\'inspirer',
    'location' => 'order',
    'type'     => 'checkbox',
    'default'  => false,
  ]);
});


// Save the opt-in choice as order meta
add_action('woocommerce_set_additional_field_value', function ($key, $value, $group, $wc_object) {
  if ($key !== 'sapi-maison/newsletter-optin') return;
  if (!($wc_object instanceof WC_Order)) return;
  $wc_object->update_meta_data('_sapi_newsletter_optin', wc_bool_to_string($value));
}, 10, 4);

// Sauvegarder la note et l'opt-in newsletter depuis la page Order Pay,
// puis déclencher la sync Brevo si la case vient d'être cochée (cas retry
// paiement où la case a été oubliée au checkout initial).
add_action('woocommerce_before_pay_action', function ($order) {
  // Note de commande
  if (! empty($_POST['sapi_order_note'])) {
    $note = sanitize_textarea_field(wp_unslash($_POST['sapi_order_note']));
    if ($note) {
      $order->add_order_note(esc_html($note), 1); // 1 = note client
      $order->set_customer_note($note);
    }
  }
  // Newsletter opt-in
  if (! empty($_POST['sapi_newsletter_optin'])) {
    $order->update_meta_data('_sapi_newsletter_optin', 'yes');
  }
  $order->save();

  sapi_brevo_newsletter_sync_optin($order->get_id());
});

/**
 * Push vers la liste Brevo #6 (Newsletter) si le client a coché l'opt-in.
 *
 * Déclenché à la création de la commande (tous moyens de paiement, avant
 * validation du paiement — le consentement est donné au submit). Également
 * rappelé depuis la page order-pay si l'opt-in est coché au retry. Le flag
 * _sapi_newsletter_brevo_synced garantit l'idempotence.
 *
 * updateEnabled: true → dédoublonne si déjà inscrit via la popup cookie.
 * Un échec Brevo n'interrompt pas le workflow commande (log uniquement).
 */
// Checkout Blocks (Store API) — hook principal sur ce site
add_action('woocommerce_store_api_checkout_order_processed', function ($order) {
  if ($order instanceof WC_Order) {
    sapi_brevo_newsletter_sync_optin($order->get_id());
  }
}, 20, 1);
// Checkout classique (fallback / compat)
add_action('woocommerce_checkout_order_processed', 'sapi_brevo_newsletter_sync_optin', 20, 1);

function sapi_brevo_newsletter_sync_optin($order_id) {
  $order = wc_get_order($order_id);
  if (!$order) return;

  if ($order->get_meta('_sapi_newsletter_optin') !== 'yes') return;
  if ($order->get_meta('_sapi_newsletter_brevo_synced') === 'yes') return;

  $email = $order->get_billing_email();
  if (!$email || !is_email($email)) return;

  $api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
  if (!$api_key) {
    error_log('[sapi-brevo-newsletter] BREVO_API_KEY manquante, opt-in non synchronisé (commande #' . $order_id . ')');
    return;
  }

  $attributes = [];
  $firstname = $order->get_billing_first_name();
  $lastname  = $order->get_billing_last_name();
  if ($firstname) $attributes['PRENOM'] = $firstname;
  if ($lastname)  $attributes['NOM']    = $lastname;

  $payload = [
    'email'         => $email,
    'listIds'       => [6],
    'updateEnabled' => true,
  ];
  if (!empty($attributes)) {
    $payload['attributes'] = $attributes;
  }

  $response = wp_remote_post('https://api.brevo.com/v3/contacts', [
    'timeout' => 10,
    'headers' => [
      'accept'       => 'application/json',
      'content-type' => 'application/json',
      'api-key'      => $api_key,
    ],
    'body'    => wp_json_encode($payload),
  ]);

  if (is_wp_error($response)) {
    error_log('[sapi-brevo-newsletter] Erreur HTTP commande #' . $order_id . ' : ' . $response->get_error_message());
    return;
  }

  $code = wp_remote_retrieve_response_code($response);
  if ($code >= 200 && $code < 300) {
    $order->update_meta_data('_sapi_newsletter_brevo_synced', 'yes');
    $order->save();
    return;
  }

  error_log('[sapi-brevo-newsletter] Brevo a répondu ' . $code . ' pour commande #' . $order_id . ' : ' . wp_remote_retrieve_body($response));
}

/**
 * Push systématique vers la liste Brevo #12 "Commande récente" à chaque commande.
 *
 * Sert de file d'attente pour l'automation post-achat : +14 jours → email
 * demande d'avis Google → ajout liste #7 "Clients" → retrait #12 (ce qui
 * permet la ré-entrée à la commande suivante). Aucune condition opt-in :
 * tous les clients passent par ce tunnel. Aucun flag d'idempotence : on
 * veut que chaque nouvelle commande (re)pousse dans la file. Brevo gère
 * le dédoublonnage via updateEnabled=true.
 */
add_action('woocommerce_store_api_checkout_order_processed', function ($order) {
  if ($order instanceof WC_Order) {
    sapi_brevo_commande_recente_sync($order->get_id());
  }
}, 20, 1);
add_action('woocommerce_checkout_order_processed', 'sapi_brevo_commande_recente_sync', 20, 1);

function sapi_brevo_commande_recente_sync($order_id) {
  $order = wc_get_order($order_id);
  if (!$order) return;

  $email = $order->get_billing_email();
  if (!$email || !is_email($email)) return;

  $api_key = defined('BREVO_API_KEY') ? BREVO_API_KEY : '';
  if (!$api_key) {
    error_log('[sapi-brevo-commande-recente] BREVO_API_KEY manquante (commande #' . $order_id . ')');
    return;
  }

  $attributes = [];
  $firstname = $order->get_billing_first_name();
  $lastname  = $order->get_billing_last_name();
  if ($firstname) $attributes['PRENOM'] = $firstname;
  if ($lastname)  $attributes['NOM']    = $lastname;

  $payload = [
    'email'         => $email,
    'listIds'       => [12],
    'updateEnabled' => true,
  ];
  if (!empty($attributes)) {
    $payload['attributes'] = $attributes;
  }

  $response = wp_remote_post('https://api.brevo.com/v3/contacts', [
    'timeout' => 10,
    'headers' => [
      'accept'       => 'application/json',
      'content-type' => 'application/json',
      'api-key'      => $api_key,
    ],
    'body'    => wp_json_encode($payload),
  ]);

  if (is_wp_error($response)) {
    error_log('[sapi-brevo-commande-recente] Erreur HTTP commande #' . $order_id . ' : ' . $response->get_error_message());
    return;
  }

  $code = wp_remote_retrieve_response_code($response);
  if ($code >= 200 && $code < 300) return;

  error_log('[sapi-brevo-commande-recente] Brevo a répondu ' . $code . ' pour commande #' . $order_id . ' : ' . wp_remote_retrieve_body($response));
}

/**
 * ============================================================
 * NEWSLETTER BREVO — AJAX subscription
 * ============================================================
 */
add_action('wp_ajax_sapi_newsletter_subscribe', 'sapi_newsletter_subscribe');
add_action('wp_ajax_nopriv_sapi_newsletter_subscribe', 'sapi_newsletter_subscribe');

function sapi_newsletter_subscribe() {
    check_ajax_referer('sapi_newsletter_nonce', 'nonce');

    // Honeypot
    if (!empty($_POST['website'])) {
        wp_send_json_error(['message' => 'Spam détecté.']);
    }

    // Rate limiting (5 inscriptions/heure par IP)
    if (!sapi_check_form_rate_limit('newsletter')) {
        wp_send_json_error(['message' => 'Trop de tentatives. Réessayez plus tard.']);
    }

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
        'key'           => 'field_psm_type_client',
        'label'         => 'Type de projet',
        'name'          => 'type_client',
        'type'          => 'button_group',
        'choices'       => [
          'particulier'   => 'Particulier',
          'professionnel' => 'Professionnel',
        ],
        'default_value' => 'particulier',
        'layout'        => 'horizontal',
        'instructions'  => 'Permettra de filtrer l\'affichage Pro / Particulier sur le site.',
      ],
      [
        'key'          => 'field_psm_sous_titre',
        'label'        => 'Sous-titre',
        'name'         => 'sous_titre',
        'type'         => 'text',
        'placeholder'  => 'Ex : Restaurant gastronomique à Lyon',
        'instructions' => 'Courte accroche affichée en gras au début de la description dans la modale (optionnel)',
      ],
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

// Notice admin : limite de 6 projets affichés sur le site
function sapi_projet_sur_mesure_admin_notice() {
  $screen = get_current_screen();
  if (!$screen || $screen->post_type !== 'projet_sur_mesure') return;

  if ($screen->base === 'edit') {
    $count = wp_count_posts('projet_sur_mesure')->publish;
    $msg = 'Seuls les <strong>6 projets les plus récents</strong> sont affichés sur la page Sur Mesure du site.';
    if ($count > 6) {
      $hidden = $count - 6;
      $msg .= ' ' . $hidden . ' projet' . ($hidden > 1 ? 's ne sont pas visibles' : ' n\'est pas visible') . ' actuellement.';
    }
    echo '<div class="notice notice-info"><p>' . $msg . '</p></div>';
  }
}
add_action('admin_notices', 'sapi_projet_sur_mesure_admin_notice');

// Colonne admin "Type" dans la liste des projets sur mesure
function sapi_psm_admin_columns($columns) {
  $new = [];
  foreach ($columns as $key => $label) {
    $new[$key] = $label;
    if ($key === 'title') {
      $new['type_client'] = 'Type';
    }
  }
  return $new;
}
add_filter('manage_projet_sur_mesure_posts_columns', 'sapi_psm_admin_columns');

function sapi_psm_admin_column_content($column, $post_id) {
  if ($column !== 'type_client') return;
  $type = get_field('type_client', $post_id);
  $labels = ['particulier' => 'Particulier', 'professionnel' => 'Pro'];
  echo esc_html($labels[$type] ?? 'Particulier');
}
add_action('manage_projet_sur_mesure_posts_custom_column', 'sapi_psm_admin_column_content', 10, 2);

/*
 * ACF fields for Product media (video + photo gallery repeater)
 * Created MANUALLY via ACF Pro UI — not registered in PHP.
 *
 * Field names expected by the template:
 *   - video_produit     (oEmbed)       → URL YouTube/Vimeo
 *   - galerie_produit   (Repeater)     → Photos supplémentaires
 *     ├─ type_photo     (Select)       → ambiance / detail / taille / client / fabrication
 *     └─ image          (Image, array) → Photo
 *
 * Location: Post Type = product
 */

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

  // Rate limiting (5 soumissions/heure par IP)
  if (!sapi_check_form_rate_limit('surmesure')) {
    return ['submitted' => true, 'success' => false, 'error' => 'Trop de messages envoyés. Réessayez plus tard.'];
  }

  $name    = sanitize_text_field($_POST['fullname'] ?? '');
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
  // Projet Robin (si existant)
  $robin_project = sanitize_textarea_field($_POST['robin_project'] ?? '');
  if (!empty($robin_project)) {
    $body .= "Projet du client (questionnaire):\n$robin_project\n\n";
  }

  $body   .= "Projet sur mesure:\n$message";

  $headers = [
    'Content-Type: text/plain; charset=UTF-8',
    'From: ' . get_bloginfo('name') . ' <noreply@' . wp_parse_url(home_url(), PHP_URL_HOST) . '>',
    'Reply-To: ' . $name . ' <' . $email . '>',
  ];

  if (wp_mail($to, $subject, $body, $headers)) {
    return ['submitted' => true, 'success' => true, 'error' => ''];
  }

  return ['submitted' => true, 'success' => false, 'error' => "Erreur lors de l'envoi. Veuillez réessayer ou contacter Robin directement par email."];
}

/* ═══════════════════════════════════════════════════════════
   ROBIN CONSEILLER V2 — Sessions tracking (table + endpoint + admin)
═══════════════════════════════════════════════════════════ */

/**
 * Create the Robin V2 sessions table
 */
function sapi_robin_create_sessions_table() {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_robin_sessions';
  $charset = $wpdb->get_charset_collate();

  $sql = "CREATE TABLE IF NOT EXISTS $table (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    session_id varchar(36) NOT NULL,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opening_context varchar(20) DEFAULT '',
    context_data text DEFAULT '',
    piece varchar(50) DEFAULT '',
    taille varchar(50) DEFAULT '',
    eclairage varchar(50) DEFAULT '',
    sortie varchar(50) DEFAULT '',
    hauteur varchar(50) DEFAULT '',
    table_reponse varchar(50) DEFAULT '',
    style varchar(50) DEFAULT '',
    completion varchar(20) DEFAULT 'partial',
    reco_shown tinyint(1) DEFAULT 0,
    reco_product_ids text DEFAULT '',
    filter_activated tinyint(1) DEFAULT 0,
    ai_call_count int(11) DEFAULT 0,
    conversation text DEFAULT '',
    contact_sent tinyint(1) DEFAULT 0,
    contact_name varchar(100) DEFAULT '',
    contact_email varchar(100) DEFAULT '',
    contact_phone varchar(50) DEFAULT '',
    device_type varchar(20) DEFAULT '',
    ip_address varchar(45) DEFAULT '',
    referrer varchar(500) DEFAULT '',
    location varchar(200) DEFAULT '',
    PRIMARY KEY (id),
    KEY session_id (session_id),
    KEY created_at (created_at)
  ) $charset;";

  require_once ABSPATH . 'wp-admin/includes/upgrade.php';
  dbDelta($sql);
}
add_action('after_switch_theme', 'sapi_robin_create_sessions_table');

function sapi_robin_maybe_create_table() {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_robin_sessions';
  if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) !== $table) {
    sapi_robin_create_sessions_table();
  }
}
add_action('admin_init', 'sapi_robin_maybe_create_table');

/**
 * AJAX endpoint — log Robin V2 session (called via sendBeacon on modal close)
 */
add_action('wp_ajax_sapi_robin_log_session', 'sapi_ajax_robin_log_session');
add_action('wp_ajax_nopriv_sapi_robin_log_session', 'sapi_ajax_robin_log_session');

function sapi_ajax_robin_log_session() {
  // sendBeacon sends as application/x-www-form-urlencoded or text/plain
  // Parse from raw input if needed
  $raw = file_get_contents('php://input');
  $data = [];
  if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
      $data = $decoded;
    } else {
      parse_str($raw, $data);
    }
  }
  if (empty($data)) {
    $data = $_POST;
  }

  // Nonce check
  $nonce = isset($data['nonce']) ? sanitize_text_field($data['nonce']) : '';
  if (!wp_verify_nonce($nonce, 'sapi-guide-results')) {
    wp_send_json_error(['message' => 'Nonce invalide']);
  }

  // Parse session data
  $session_id      = isset($data['session_id']) ? sanitize_text_field($data['session_id']) : '';
  $opening_context = isset($data['opening_context']) ? sanitize_key($data['opening_context']) : '';
  $context_data    = isset($data['context_data']) ? sanitize_text_field($data['context_data']) : '';

  $answers = [];
  if (!empty($data['answers'])) {
    $raw_answers = is_string($data['answers']) ? json_decode($data['answers'], true) : $data['answers'];
    if (is_array($raw_answers)) {
      foreach ($raw_answers as $k => $v) {
        $answers[sanitize_key($k)] = sanitize_text_field($v);
      }
    }
  }

  $completion       = isset($data['completion']) ? sanitize_key($data['completion']) : 'partial';
  $reco_shown       = !empty($data['reco_shown']) ? 1 : 0;
  $reco_product_ids = isset($data['reco_product_ids']) ? sanitize_text_field($data['reco_product_ids']) : '';
  $filter_activated = !empty($data['filter_activated']) ? 1 : 0;
  $ai_call_count    = isset($data['ai_call_count']) ? (int) $data['ai_call_count'] : 0;
  $conversation     = isset($data['conversation']) ? sanitize_textarea_field($data['conversation']) : '';
  $contact_sent     = !empty($data['contact_sent']) ? 1 : 0;
  $contact_name     = isset($data['contact_name']) ? sanitize_text_field($data['contact_name']) : '';
  $contact_email    = isset($data['contact_email']) ? sanitize_email($data['contact_email']) : '';
  $contact_phone    = isset($data['contact_phone']) ? sanitize_text_field($data['contact_phone']) : '';

  if (empty($session_id)) {
    wp_send_json_error(['message' => 'session_id manquant']);
  }

  // Device detection
  $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '';
  $device = 'Desktop';
  if (preg_match('/Mobile|Android|iPhone/i', $user_agent)) {
    $device = 'Mobile';
  } elseif (preg_match('/Tablet|iPad/i', $user_agent)) {
    $device = 'Tablette';
  }
  $browser = 'Autre';
  if (strpos($user_agent, 'Chrome') !== false && strpos($user_agent, 'Edg') === false) {
    $browser = 'Chrome';
  } elseif (strpos($user_agent, 'Safari') !== false && strpos($user_agent, 'Chrome') === false) {
    $browser = 'Safari';
  } elseif (strpos($user_agent, 'Firefox') !== false) {
    $browser = 'Firefox';
  } elseif (strpos($user_agent, 'Edg') !== false) {
    $browser = 'Edge';
  }
  $device_type = $device . ' · ' . $browser;

  // IP
  $ip = '';
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])))[0];
  } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
    $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
  }

  // Referrer
  $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER'])) : '';

  // Insert or update
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_robin_sessions';

  $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE session_id = %s", $session_id));

  $row_data = [
    'opening_context'  => $opening_context,
    'context_data'     => $context_data,
    'piece'            => $answers['piece'] ?? '',
    'taille'           => $answers['taille'] ?? $answers['taille_escalier'] ?? '',
    'eclairage'        => $answers['eclairage'] ?? '',
    'sortie'           => $answers['sortie'] ?? '',
    'hauteur'          => $answers['hauteur'] ?? '',
    'table_reponse'    => $answers['table'] ?? '',
    'style'            => $answers['style'] ?? '',
    'completion'       => $completion,
    'reco_shown'       => $reco_shown,
    'reco_product_ids' => $reco_product_ids,
    'filter_activated' => $filter_activated,
    'ai_call_count'    => $ai_call_count,
    'conversation'     => $conversation,
    'contact_sent'     => $contact_sent,
    'contact_name'     => $contact_name,
    'contact_email'    => $contact_email,
    'contact_phone'    => $contact_phone,
    'device_type'      => $device_type,
    'ip_address'       => $ip,
    'referrer'         => $referrer,
  ];

  if ($existing) {
    $wpdb->update($table, $row_data, ['session_id' => $session_id]);
  } else {
    $row_data['session_id'] = $session_id;
    $row_data['created_at'] = current_time('mysql');
    $wpdb->insert($table, $row_data);
    $existing = $wpdb->insert_id;
  }

  // Geolocation (async)
  if ($ip && !empty($existing)) {
    $row_id = (int) $existing;
    add_action('shutdown', function () use ($ip, $row_id) {
      $resp = wp_remote_get("http://ip-api.com/json/{$ip}?fields=city,regionName,country&lang=fr", ['timeout' => 5]);
      if (is_wp_error($resp)) return;
      $body = json_decode(wp_remote_retrieve_body($resp), true);
      if (empty($body['city'])) return;
      $location = implode(', ', array_filter([$body['city'], $body['regionName'], $body['country']]));
      global $wpdb;
      $table = $wpdb->prefix . 'sapi_robin_sessions';
      $wpdb->update($table, ['location' => mb_substr($location, 0, 200)], ['id' => $row_id], ['%s'], ['%d']);
    });
  }

  wp_send_json_success(['logged' => true]);
}

/**
 * Admin menu — Robin Conseiller
 */
function sapi_robin_admin_menu() {
  add_menu_page(
    'Robin Conseiller — Sessions',
    'Robin Conseiller',
    'manage_woocommerce',
    'sapi-robin-sessions',
    'sapi_robin_admin_page',
    'dashicons-lightbulb',
    26
  );
}
add_action('admin_menu', 'sapi_robin_admin_menu');

/**
 * CSV Export — Robin V2
 */
function sapi_robin_export_csv() {
  if (!isset($_GET['sapi_robin_export']) || $_GET['sapi_robin_export'] !== '1') return;
  if (!current_user_can('manage_woocommerce')) return;
  if (!isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sapi_robin_export')) return;

  global $wpdb;
  $table = $wpdb->prefix . 'sapi_robin_sessions';
  $rows = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC", ARRAY_A);

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=robin-conseiller-sessions-' . wp_date('Y-m-d') . '.csv');

  $out = fopen('php://output', 'w');
  fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

  fputcsv($out, ['Date', 'Contexte', 'Appareil', 'Localisation', 'Provenance', 'Pièce', 'Taille', 'Éclairage', 'Sortie', 'Hauteur', 'Table', 'Style', 'Avancement', 'Reco vue', 'Produits reco', 'Filtre activé', 'Appels IA', 'Conversation', 'Contact', 'Nom', 'Email', 'Téléphone'], ';');

  foreach ($rows as $r) {
    fputcsv($out, [
      $r['created_at'],
      $r['opening_context'],
      $r['device_type'],
      $r['location'],
      $r['referrer'],
      $r['piece'],
      $r['taille'],
      $r['eclairage'],
      $r['sortie'],
      $r['hauteur'],
      $r['table_reponse'],
      $r['style'],
      $r['completion'],
      $r['reco_shown'] ? 'Oui' : 'Non',
      $r['reco_product_ids'],
      $r['filter_activated'] ? 'Oui' : 'Non',
      $r['ai_call_count'],
      $r['conversation'],
      $r['contact_sent'] ? 'Oui' : 'Non',
      $r['contact_name'],
      $r['contact_email'],
      $r['contact_phone'],
    ], ';');
  }
  fclose($out);
  exit;
}
add_action('admin_init', 'sapi_robin_export_csv');

/**
 * Admin page renderer — Robin Conseiller V2
 */
function sapi_robin_admin_page() {
  global $wpdb;
  $table = $wpdb->prefix . 'sapi_robin_sessions';

  // Créer la table si elle n'existe pas
  sapi_robin_maybe_create_table();

  // Handle delete action
  if (isset($_GET['sapi_robin_delete']) && isset($_GET['_wpnonce'])) {
    $delete_id = (int) $_GET['sapi_robin_delete'];
    if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sapi_robin_delete_' . $delete_id)) {
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

  // Stats rapides
  $stats_complete = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE completion = 'complete'");
  $stats_reco     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE reco_shown = 1");
  $stats_filter   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE filter_activated = 1");
  $stats_contact  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE contact_sent = 1");

  $export_url = wp_nonce_url(admin_url('admin.php?page=sapi-robin-sessions&sapi_robin_export=1'), 'sapi_robin_export');

  $context_labels = [
    'bandeau'       => 'Bandeau',
    'category'      => 'Catégorie',
    'product'       => 'Produit',
    'product_guide' => 'Fiche produit',
    'homepage'      => 'Accueil',
  ];
  ?>
  <div class="wrap">
    <h1>Robin Conseiller — Sessions <span style="font-size:0.6em; color:#999;">(<?php echo esc_html($total); ?>)</span></h1>

    <!-- Stats rapides -->
    <div style="display:flex; gap:1.5rem; margin:1rem 0 1.5rem; flex-wrap:wrap;">
      <div style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:0.75rem 1.25rem; min-width:120px;">
        <div style="font-size:1.5em; font-weight:700; color:#2E7D32;"><?php echo esc_html($stats_complete); ?></div>
        <div style="font-size:0.8em; color:#666;">Quiz terminés</div>
      </div>
      <div style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:0.75rem 1.25rem; min-width:120px;">
        <div style="font-size:1.5em; font-weight:700; color:#E35B24;"><?php echo esc_html($stats_reco); ?></div>
        <div style="font-size:0.8em; color:#666;">Reco vues</div>
      </div>
      <div style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:0.75rem 1.25rem; min-width:120px;">
        <div style="font-size:1.5em; font-weight:700; color:#937D68;"><?php echo esc_html($stats_filter); ?></div>
        <div style="font-size:0.8em; color:#666;">Filtre activé</div>
      </div>
      <div style="background:#fff; border:1px solid #ddd; border-radius:8px; padding:0.75rem 1.25rem; min-width:120px;">
        <div style="font-size:1.5em; font-weight:700; color:#1565C0;"><?php echo esc_html($stats_contact); ?></div>
        <div style="font-size:0.8em; color:#666;">Contacts</div>
      </div>
    </div>

    <p><a href="<?php echo esc_url($export_url); ?>" class="button button-secondary">Exporter CSV</a></p>

    <table class="widefat striped" style="margin-top:10px; font-size:13px;">
      <thead>
        <tr>
          <th>Date</th>
          <th>Contexte</th>
          <th>Appareil</th>
          <th>Lieu</th>
          <th>Pièce</th>
          <th>Taille</th>
          <th>Sortie</th>
          <th>Style</th>
          <th>Avancement</th>
          <th style="text-align:center;">Reco</th>
          <th style="text-align:center;">Filtre</th>
          <th style="text-align:center;">IA</th>
          <th style="text-align:center;">Contact</th>
          <th style="width:40px;"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)) : ?>
          <tr><td colspan="14" style="text-align:center; color:#999;">Aucune session enregistrée.</td></tr>
        <?php else : ?>
          <?php foreach ($rows as $r) : ?>
            <tr>
              <td style="white-space:nowrap;"><?php echo esc_html(date('d/m H:i', strtotime($r->created_at))); ?></td>
              <td>
                <?php
                $ctx = $r->opening_context;
                $ctx_label = isset($context_labels[$ctx]) ? $context_labels[$ctx] : esc_html($ctx);
                $ctx_colors = ['bandeau' => '#937D68', 'category' => '#E35B24', 'product' => '#1565C0', 'product_guide' => '#7B1FA2', 'homepage' => '#2E7D32'];
                $ctx_color = isset($ctx_colors[$ctx]) ? $ctx_colors[$ctx] : '#666';
                ?>
                <span style="background:<?php echo esc_attr($ctx_color); ?>; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.75em; font-weight:600;">
                  <?php echo esc_html($ctx_label); ?>
                </span>
              </td>
              <td style="white-space:nowrap; font-size:0.85em;"><?php echo esc_html($r->device_type ?: '—'); ?></td>
              <td style="font-size:0.85em;"><?php echo esc_html($r->location ?: '—'); ?></td>
              <td><?php echo esc_html($r->piece ?: '—'); ?></td>
              <td><?php echo esc_html($r->taille ?: '—'); ?></td>
              <td><?php echo esc_html($r->sortie ?: '—'); ?></td>
              <td><?php echo esc_html($r->style ?: '—'); ?></td>
              <td>
                <?php if ($r->completion === 'complete') : ?>
                  <span style="color:#2E7D32; font-weight:600;">Complet</span>
                <?php else : ?>
                  <span style="color:#999;">Partiel</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php if ($r->reco_shown) : ?>
                  <span title="Produits : <?php echo esc_attr($r->reco_product_ids); ?>" style="cursor:help; color:#E35B24;">&#9733;</span>
                <?php else : ?>
                  —
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php echo $r->filter_activated ? '<span style="color:#937D68;">&#10003;</span>' : '—'; ?>
              </td>
              <td style="text-align:center;">
                <?php if ($r->ai_call_count > 0) : ?>
                  <span title="<?php echo esc_attr(mb_substr($r->conversation, 0, 500)); ?>" style="cursor:help; text-decoration:underline dotted;">
                    <?php echo esc_html($r->ai_call_count); ?>
                  </span>
                <?php else : ?>
                  —
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php if ($r->contact_sent) : ?>
                  <span title="<?php echo esc_attr($r->contact_name . ' · ' . $r->contact_email . ' · ' . $r->contact_phone); ?>" style="cursor:help; color:#2E7D32;">&#10003;</span>
                <?php else : ?>
                  —
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php
                $delete_url = wp_nonce_url(
                  admin_url('admin.php?page=sapi-robin-sessions&sapi_robin_delete=' . (int) $r->id),
                  'sapi_robin_delete_' . (int) $r->id
                );
                ?>
                <a href="<?php echo esc_url($delete_url); ?>"
                   onclick="return confirm('Supprimer cette session ?');"
                   style="color:#a00; text-decoration:none; font-size:0.9em;" title="Supprimer">&#10005;</a>
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

/* ─── Google Reviews (Places API New) ─── */
function sapi_get_google_reviews() {
  $api_key  = defined('SAPI_GOOGLE_API_KEY') ? SAPI_GOOGLE_API_KEY : '';
  $place_id = defined('SAPI_GOOGLE_PLACE_ID') ? SAPI_GOOGLE_PLACE_ID : '';

  if (empty($api_key) || empty($place_id)) {
    return null;
  }

  // Cache 6h via transient
  $cache_key = 'sapi_google_reviews_' . md5($place_id);
  $cached = get_transient($cache_key);
  if ($cached !== false && is_array($cached) && isset($cached['rating'])) {
    return $cached;
  }
  // Nettoyer un éventuel transient invalide
  if ($cached !== false) {
    delete_transient($cache_key);
  }

  $url = 'https://places.googleapis.com/v1/places/' . $place_id;

  $response = wp_remote_get($url, [
    'timeout' => 10,
    'headers' => [
      'X-Goog-Api-Key'    => $api_key,
      'X-Goog-FieldMask'  => 'reviews,rating,userRatingCount',
      'Referer'            => home_url('/'),
    ],
  ]);

  if (is_wp_error($response)) {
    return null;
  }

  $body = json_decode(wp_remote_retrieve_body($response), true);
  if (empty($body) || !isset($body['rating'])) {
    return null;
  }

  $result = [
    'rating'      => floatval($body['rating']),
    'total'       => intval($body['userRatingCount'] ?? 0),
    'reviews'     => [],
  ];

  if (!empty($body['reviews'])) {
    foreach ($body['reviews'] as $review) {
      // Prefer originalText (French) over translated text
      $text = '';
      if (!empty($review['originalText']['text'])) {
        $text = $review['originalText']['text'];
      } elseif (!empty($review['text']['text'])) {
        $text = $review['text']['text'];
      }

      $result['reviews'][] = [
        'author'  => $review['authorAttribution']['displayName'] ?? '',
        'rating'  => intval($review['rating'] ?? 5),
        'text'    => $text,
        'time'    => $review['relativePublishTimeDescription'] ?? '',
        'photo'   => $review['authorAttribution']['photoUri'] ?? '',
      ];
    }
  }

  set_transient($cache_key, $result, 6 * HOUR_IN_SECONDS);

  return $result;
}



// ─── Robots.txt — règles supplémentaires ─────────────────────────────────────
// Bloque les URLs parasites crawlées par Google (audit GSC 2 avril 2026)
add_filter( 'robots_txt', function ( $output ) {
  $output .= "\n# Atelier Sapi — règles personnalisées\n";
  $output .= "Disallow: /*?wc-ajax=*\n";
  $output .= "Disallow: /wp-json/complianz/\n";
  $output .= "Disallow: /*?PageSpeed=*\n";
  return $output;
}, 99 );

/**
 * ============================================================
 * COUPON BIENVENUE10 — Auto-application via cookie
 * ============================================================
 * Le snippet popup cookies (sapi-cookie-popup) pose le cookie
 * `sapi_pending_coupon` quand un visiteur laisse son email. On lit ce cookie
 * ici pour appliquer automatiquement le coupon BIENVENUE10 dès qu'un produit
 * est ajouté au panier, puis on détruit le cookie pour ne plus rejouer.
 *
 * Sécurité : la valeur du cookie est ignorée et le code est forcé en dur à
 * 'BIENVENUE10' — ça empêche de faire appliquer un autre coupon en manipulant
 * le cookie côté client.
 */
add_action('woocommerce_add_to_cart', 'sapi_auto_apply_welcome_coupon');
add_action('woocommerce_cart_loaded_from_session', 'sapi_auto_apply_welcome_coupon');

function sapi_auto_apply_welcome_coupon() {
  if (empty($_COOKIE['sapi_pending_coupon'])) return;
  if (!function_exists('WC') || !WC()->cart) return;

  $cart = WC()->cart;
  if ($cart->is_empty()) return;

  $coupon_code = 'bienvenue10'; // WC normalise en lowercase
  if ($cart->has_discount($coupon_code)) {
    sapi_clear_pending_coupon_cookie();
    return;
  }

  // apply_coupon() affiche lui-même les notices WC (succès ou refus).
  // Qu'il soit accepté ou refusé, on détruit le cookie pour ne pas rejouer
  // à chaque ajout panier suivant (sinon spam de notices).
  $cart->apply_coupon($coupon_code);
  sapi_clear_pending_coupon_cookie();
}

function sapi_clear_pending_coupon_cookie() {
  if (!headers_sent()) {
    $path   = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
    setcookie('sapi_pending_coupon', '', time() - 3600, $path, $domain);
  }
  unset($_COOKIE['sapi_pending_coupon']);
}

/**
 * Notice WC personnalisée lors de l'application du coupon BIENVENUE10.
 * Remplace le "Code promo appliqué avec succès" générique par un message
 * de bienvenue aligné sur le ton de l'Atelier.
 */
add_filter('woocommerce_coupon_success_message', 'sapi_welcome_coupon_success_message', 10, 3);

function sapi_welcome_coupon_success_message($msg, $msg_code, $coupon) {
  if (!$coupon || !is_object($coupon) || !method_exists($coupon, 'get_code')) return $msg;
  if (strtolower($coupon->get_code()) !== 'bienvenue10') return $msg;
  if ((int) $msg_code !== WC_Coupon::WC_COUPON_SUCCESS) return $msg;

  return 'Bienvenue à l\'Atelier Sâpi ! Votre réduction de 10% a été appliquée. 🌿';
}

/**
 * Galerie Inspiration — handler AJAX inscription newsletter Brevo (liste #6).
 * Mirroir du handler `sapi_brevo_subscribe` du snippet popup cookies, mais
 * avec SOURCE = "Galerie Inspiration" pour distinguer l'origine côté Brevo.
 *
 * Pourquoi un handler dédié plutôt que de réutiliser celui du snippet :
 * - Le snippet hardcode SOURCE = 'popup', non paramétrable.
 * - Garde le snippet (gestion par Robin via Code Snippets) totalement indépendant
 *   du déploiement du thème.
 */
if (!function_exists('sapi_get_brevo_api_key')) {
  function sapi_get_brevo_api_key() {
    foreach (['BREVO_API_KEY', 'SAPI_BREVO_API_KEY', 'SIB_API_KEY', 'SENDINBLUE_API_KEY'] as $const) {
      if (defined($const) && constant($const)) {
        return constant($const);
      }
    }
    $key = get_option('sib_api_key_v3');
    if (!empty($key)) return $key;
    $options = get_option('mailin_options');
    if (is_array($options)) {
      foreach (['api_key_v3', 'api_key', 'access_key', 'apikey'] as $k) {
        if (!empty($options[$k])) return $options[$k];
      }
    }
    return null;
  }
}

add_action('wp_ajax_nopriv_sapi_inspiration_brevo_subscribe', 'sapi_inspiration_brevo_subscribe');
add_action('wp_ajax_sapi_inspiration_brevo_subscribe', 'sapi_inspiration_brevo_subscribe');
function sapi_inspiration_brevo_subscribe() {
  if (!check_ajax_referer('sapi_inspiration_brevo_nonce', 'nonce', false)) {
    wp_send_json_error(['message' => 'invalid_nonce'], 403);
  }

  $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
  if (empty($email) || !is_email($email)) {
    wp_send_json_error(['message' => 'invalid_email'], 400);
  }

  $api_key = sapi_get_brevo_api_key();
  if (empty($api_key)) {
    wp_send_json_error(['message' => 'no_api_key'], 500);
  }

  $response = wp_remote_post('https://api.brevo.com/v3/contacts', [
    'timeout' => 10,
    'headers' => [
      'accept'       => 'application/json',
      'content-type' => 'application/json',
      'api-key'      => $api_key,
    ],
    'body' => wp_json_encode([
      'email'         => $email,
      'listIds'       => [6],
      'updateEnabled' => true,
      'attributes'    => [
        'SOURCE' => 'Galerie Inspiration',
      ],
    ]),
  ]);

  if (is_wp_error($response)) {
    wp_send_json_error(['message' => 'http_error', 'details' => $response->get_error_message()], 500);
  }

  $code = wp_remote_retrieve_response_code($response);
  if ($code >= 200 && $code < 300) {
    wp_send_json_success();
  }

  $body = wp_remote_retrieve_body($response);
  wp_send_json_error(['message' => 'brevo_error', 'code' => $code, 'body' => $body], $code);
}

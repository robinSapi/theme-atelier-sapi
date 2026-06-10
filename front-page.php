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
    'posts_per_page' => 6,
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
        // Cherche une photo ambiance PAYSAGE (width >= height) — écarte les portraits
        $photo_ids = sapi_get_product_photo_ids(get_the_ID(), 'ambiance', 0);
        $image_id = 0;
        foreach ((array) $photo_ids as $pid) {
          $meta = wp_get_attachment_metadata($pid);
          if (!empty($meta['width']) && !empty($meta['height']) && $meta['width'] >= $meta['height']) {
            $image_id = (int) $pid;
            break;
          }
        }

        if ($image_id) {
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
            'image_id' => $image_id,
          ];

          if (count($products_by_category[$cat_slug]) >= 2) break; // jusqu'à 2 produits (paysage) par catégorie
        }
      }
    }
  }
  wp_reset_postdata();
}

// 2 produits par catégorie, interleavés (8 slides produits max) : susp, applique, lampe, lampadaire ×2
for ($i = 0; $i < 2; $i++) {
  foreach ($categories_order as $cat_slug) {
    if (isset($products_by_category[$cat_slug][$i])) {
      $carousel_products[] = $products_by_category[$cat_slug][$i];
    }
  }
}

// Slides "en avant" — ACF Repeater sur la front page
// Filtrage : actives ET dans la fenêtre temporelle ET avec image valide.
$promo_slides = [];
$front_page_id = (int) get_option('page_on_front');
if ($front_page_id && function_exists('get_field')) {
  $raw_slides = get_field('slides_en_avant', $front_page_id) ?: [];
  $today = current_time('Y-m-d');
  foreach ($raw_slides as $slide) {
    if (empty($slide['active'])) continue;
    if (empty($slide['image']))  continue;
    if (!empty($slide['date_debut']) && $today < $slide['date_debut']) continue;
    if (!empty($slide['date_fin'])   && $today > $slide['date_fin'])   continue;
    $promo_slides[] = [
      'image_id' => (int) $slide['image'],
      'url'      => trim((string) ($slide['url'] ?? '')),
      'titre'    => trim((string) ($slide['titre'] ?? '')),
    ];
  }
}

// Star du moment — lit le champ ACF produit_star de la page "La star du moment"
$star_product_data = null;
$star_id = 0; // initialisé ici pour rester accessible au bloc best-sellers (exclusion Star)
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

    $detail_photo_ids = sapi_get_product_photo_ids($star_id, 'detail', 1);
    $star_image_id = !empty($detail_photo_ids) ? $detail_photo_ids[0] : 0;
    if (!$star_image_id) {
      $star_image_id = get_post_thumbnail_id($star_id);
    }

    $star_product_data = [
      'name'     => $product->get_name(),
      'category' => $category_name,
      'price'    => $price_display,
      'image_id' => $star_image_id,
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
    $gc_image_id = $bandeau_noel
      ? $bandeau_noel[0]->ID
      : get_post_thumbnail_id(get_the_ID());

    $gift_card = [
      'name'     => get_the_title(),
      'price'    => $price_display,
      'image_id' => $gc_image_id,
      'url'      => get_permalink(),
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
  ['label' => 'Chambre enfant', 'slug' => 'chambre-enfant', 'icon' => 'teddy'],
  ['label' => 'Bureau',  'slug' => 'bureau',   'icon' => 'monitor'],
  ['label' => 'Entrée',  'slug' => 'entree',   'icon' => 'door'],
  ['label' => 'Escalier','slug' => 'escalier', 'icon' => 'stairs'],
];

$room_icons = [
  'sofa'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><rect x="2" y="11" width="20" height="7" rx="2"/><path d="M5 18v2m14-2v2"/></svg>',
  'dining'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V20H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>',
  'bed'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>',
  'teddy'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="7" r="2.5"/><circle cx="12" cy="13" r="7"/><circle cx="9.5" cy="12" r=".7" fill="currentColor" stroke="none"/><circle cx="14.5" cy="12" r=".7" fill="currentColor" stroke="none"/><circle cx="12" cy="15" r="1.3"/><path d="M10.6 16.6a2.1 2.1 0 0 0 2.8 0"/></svg>',
  'monitor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>',
  'door'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="15" cy="12" r="1"/></svg>',
  'stairs'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4v-4h4v-4h4V8h4"/><path d="M4 20V8"/><path d="M20 20V8"/></svg>',
  'autre'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
];

// 2 produits les plus RÉCENTS parmi lampes à poser / lampadaires / appliques,
// forcément de 2 catégories différentes (Star exclue).
$featured_products = [];
$fp_target_cats = ['lampesaposer', 'lampadaires', 'appliques'];
$featured_query = new WP_Query([
  'post_type'      => 'product',
  'posts_per_page' => 12, // marge pour trouver 2 catégories différentes
  'post_status'    => 'publish',
  'orderby'        => 'date',
  'order'          => 'DESC',
  'post__not_in'   => !empty($star_id) ? [(int) $star_id] : [],
  'tax_query'      => [
    [
      'taxonomy' => 'product_cat',
      'field'    => 'slug',
      'terms'    => $fp_target_cats,
      'operator' => 'IN',
    ],
  ],
]);

$fp_used_cats = []; // verrou : 2 catégories différentes
if ($featured_query->have_posts()) {
  while ($featured_query->have_posts()) {
    $featured_query->the_post();
    $fp_id = get_the_ID();
    $product = wc_get_product($fp_id);
    if (!$product) continue;

    // Catégorie cible de ce produit (1re parmi les 3 visées) — sert au verrou + à l'affichage
    $fp_cats = get_the_terms($fp_id, 'product_cat');
    $fp_cat_slug = '';
    $fp_category = '';
    if ($fp_cats && !is_wp_error($fp_cats)) {
      foreach ($fp_cats as $cat) {
        if (in_array($cat->slug, $fp_target_cats, true)) {
          $fp_cat_slug = $cat->slug;
          $fp_category = str_replace(
            ['Appliques', 'Lampadaires', 'Lampes à poser'],
            ['Applique',  'Lampadaire',  'Lampe à poser'],
            $cat->name
          );
          break;
        }
      }
    }
    // Sauter si pas de catégorie cible, ou catégorie déjà prise (2 catégories distinctes)
    if ($fp_cat_slug === '' || in_array($fp_cat_slug, $fp_used_cats, true)) {
      continue;
    }

    // Photo ambiance (fallback thumbnail) + hover (1re galerie WC) — pattern archive-product.php
    $amb_ids = sapi_get_product_photo_ids($fp_id, 'ambiance', 1);
    $ambiance_id = !empty($amb_ids) ? $amb_ids[0] : get_post_thumbnail_id($fp_id);
    $gallery_ids = $product->get_gallery_image_ids();
    $hover_id = !empty($gallery_ids) ? $gallery_ids[0] : 0;

    // Prix
    $fp_is_variable = $product->is_type('variable');
    if ($fp_is_variable) {
      $min_price = $product->get_variation_price('min');
      $price_display = $min_price ? wc_price($min_price) : $product->get_price_html();
    } else {
      $price_display = wc_price($product->get_price());
    }

    $fp_used_cats[] = $fp_cat_slug;
    $featured_products[] = [
      'id'          => $fp_id,
      'name'        => get_the_title(),
      'category'    => $fp_category,
      'price'       => $price_display,
      'is_variable' => $fp_is_variable,
      'ambiance_id' => $ambiance_id,
      'hover_id'    => $hover_id,
      'url'         => get_permalink(),
    ];
    if (count($featured_products) >= 2) break;
  }
  wp_reset_postdata();
}

// Collections
// Collections dynamiques — URLs et images récupérées depuis WooCommerce/ACF
$collection_slugs = [
  ['slug' => 'suspensions', 'name' => 'Suspensions', 'desc' => 'Une lampe à suspendre au plafond. L\'idéal entre éclairage et déco !'],
  ['slug' => 'lampadaires', 'name' => 'Lampadaires', 'desc' => 'Sur pied, posés au sol. Pratique à déplacer et à installer.'],
  ['slug' => 'appliques',   'name' => 'Appliques',   'desc' => 'Fixées au mur, en lumière d\'appoint. Un éclairage qui habille la pièce par les côtés.'],
  ['slug' => 'lampesaposer', 'name' => 'Lampe à poser', 'desc' => 'À poser sur une table ou un meuble. Rapide, pratique et mobiles.'],
  ['slug' => 'accessoires', 'name' => 'Accessoires', 'prefer' => 'ampoule', 'desc' => 'Ampoules, systèmes électriques et compléments'],
];

$collections = [];
foreach ($collection_slugs as $col) {
  $cat_term = get_term_by('slug', $col['slug'], 'product_cat');
  if (!$cat_term) continue;

  $cat_url = get_term_link($cat_term);
  $cat_count = $cat_term->count;

  // 1. Vérifier si une image custom ACF existe pour cette catégorie
  $col_image_id = null;
  if (function_exists('get_field') && $cat_term) {
    $custom_image_id = get_field('image_collection', 'product_cat_' . $cat_term->term_id);
    if ($custom_image_id) {
      $col_image_id = $custom_image_id;
    }
  }

  // 2. Fallback : image depuis un produit de la catégorie (si pas d'ACF)
  if (!$col_image_id) {
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
      $preferred_id = null;
      while ($col_query->have_posts()) {
        $col_query->the_post();
        $pid = get_the_ID();
        if (!$fallback_id) $fallback_id = $pid;

        // Priorité à un produit spécifique si défini (ex: "ampoule" pour accessoires)
        if (!empty($col['prefer']) && stripos(get_the_title(), $col['prefer']) !== false) {
          $preferred_id = $pid;
        }
      }
      wp_reset_postdata();

      // Image collection : 3ème photo ambiance du repeater galerie_produit
      // (produit "preferred" si défini, sinon 1er produit de la catégorie),
      // fallback sur la dernière ambiance disponible, puis vignette WC en dernier recours.
      $target_id = $preferred_id ?: $fallback_id;
      if ($target_id) {
        $amb_photo_ids = sapi_get_product_photo_ids($target_id, 'ambiance');
        if (!empty($amb_photo_ids)) {
          $col_image_id = isset($amb_photo_ids[2]) ? $amb_photo_ids[2] : end($amb_photo_ids);
        } else {
          $col_image_id = get_post_thumbnail_id($target_id);
        }
      }
    }
  }

  // Description : champ WooCommerce de la catégorie en priorité (éditable en admin),
  // sinon repli sur la ligne factuelle par défaut définie dans $collection_slugs.
  $wc_desc = $cat_term->description ? trim(wp_strip_all_tags($cat_term->description)) : '';
  $col_desc = $wc_desc !== '' ? $wc_desc : (isset($col['desc']) ? $col['desc'] : '');

  $collections[] = [
    'name' => $col['name'],
    'desc' => $col_desc,
    'count' => $cat_count . ' ' . ($cat_count > 1 ? 'créations' : 'création'),
    'image_id' => $col_image_id,
    'url' => $cat_url,
  ];
}
?>

<!-- Full Page Carousel -->
<?php
$total_slides = count($promo_slides) + count($carousel_products);
$slide_index = 0; // compteur global pour déterminer la première slide active

// Data slides à exposer au JS pour mettre à jour la naming card au changement de slide.
// Ordre : slides promo en premier, puis slides produits — DOIT correspondre à l'ordre des slides rendues ci-dessous.
// html_entity_decode : certains titres WP contiennent l'entité HTML littérale (ex. « L&rsquo;Incandescent »
// au lieu de « L'Incandescent »). Sans décodage, ça remonte tel quel dans le DOM, et la règle globale
// .product-restname { text-transform: capitalize } capitalise le « r » après le « & » (word-boundary CSS),
// donnant « L&Rsquo;Incandescent » à l'écran.
$carousel_slides_data = [];
foreach ($promo_slides as $promo) {
  $carousel_slides_data[] = [
    'name'    => html_entity_decode((string) ($promo['titre'] ?? ''), ENT_QUOTES, 'UTF-8'),
    'url'     => (string) ($promo['url'] ?? ''),
    'isPromo' => true,
  ];
}
foreach ($carousel_products as $product) {
  $carousel_slides_data[] = [
    'name'    => html_entity_decode((string) $product['name'], ENT_QUOTES, 'UTF-8'),
    'url'     => (string) $product['url'],
    'isPromo' => false,
  ];
}
?>
<?php if ($total_slides > 0) : ?>
<section class="homepage-carousel-fullscreen">
  <div class="carousel-container">

    <!-- Couche 1 : slides image (cliquabilité M22 préservée sur les slides produits) -->
    <div class="carousel-slides">

      <?php foreach ($promo_slides as $promo) :
        $is_first = $slide_index === 0;
        $has_url  = $promo['url'] !== '';
        $classes  = 'carousel-slide carousel-slide-promo';
        if ($is_first) $classes .= ' active';

        // Pas de surcharge du 'alt' : on utilise le texte alternatif natif de la médiathèque WP.
        $img_attr = [
          'class'   => 'carousel-slide-img',
          'loading' => $is_first ? 'eager' : 'lazy',
          'sizes'   => '100vw',
        ];
        if ($is_first) $img_attr['fetchpriority'] = 'high';
      ?>
        <?php if ($has_url) : ?>
          <a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($promo['url']); ?>">
        <?php else : ?>
          <div class="<?php echo esc_attr($classes); ?>">
        <?php endif; ?>
            <?php echo wp_get_attachment_image($promo['image_id'], 'full', false, $img_attr); ?>
            <div class="carousel-overlay"></div>
        <?php if ($has_url) : ?>
          </a>
        <?php else : ?>
          </div>
        <?php endif; ?>
        <?php $slide_index++; ?>
      <?php endforeach; ?>

      <?php foreach ($carousel_products as $product) :
        $is_first = $slide_index === 0;
        $classes  = 'carousel-slide carousel-slide-product';
        if ($is_first) $classes .= ' active';
      ?>
        <a class="<?php echo esc_attr($classes); ?>" href="<?php echo esc_url($product['url']); ?>" aria-label="Découvrir <?php echo esc_attr($product['name']); ?>">
          <?php
            $img_attr = [
              'class'   => 'carousel-slide-img',
              'alt'     => esc_attr($product['name']) . ', luminaire artisanal en bois',
              'loading' => $is_first ? 'eager' : 'lazy',
              'sizes'   => '100vw',
            ];
            if ($is_first) $img_attr['fetchpriority'] = 'high';
            echo wp_get_attachment_image($product['image_id'], 'full', false, $img_attr);
          ?>
          <div class="carousel-overlay"></div>
        </a>
        <?php $slide_index++; ?>
      <?php endforeach; ?>

    </div>

    <!-- Couche 2 : foreground en flex column — zone texte (H1+H2) + zone card naming/nav.
         pointer-events: none sur le wrapper laisse passer les clics sur les slides.
         La naming card et son contenu remettent pointer-events: auto. -->
    <div class="carousel-foreground">

      <div class="hero-text-area">
        <h1 class="carousel-hero-title">Luminaires en bois · Atelier Sâpi</h1>
        <p class="carousel-hero-subtitle">Fabriqués à la main, à la commande, dans mon atelier à Lyon</p>
      </div>

      <div class="card-area">
        <div class="naming-card">
          <div class="naming-card__row">
            <button type="button" class="carousel-arrow carousel-arrow-prev" aria-label="Slide précédente">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"/>
              </svg>
            </button>
            <a class="naming-link" href="#" id="carousel-naming-link" aria-label="Découvrir le modèle affiché"></a>
            <button type="button" class="carousel-arrow carousel-arrow-next" aria-label="Slide suivante">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="9 18 15 12 9 6"/>
              </svg>
            </button>
          </div>
          <div class="carousel-dots">
            <?php for ($i = 0; $i < $total_slides; $i++) : ?>
              <button type="button"
                      class="carousel-dot<?php echo $i === 0 ? ' active' : ''; ?>"
                      aria-label="Aller à la slide <?php echo ($i + 1); ?>"></button>
            <?php endfor; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>window.SAPI_CAROUSEL_DATA = <?php echo wp_json_encode($carousel_slides_data); ?>;</script>
<?php endif; ?>

<!-- Entrée projet — room picker (bande crème) -->
<section class="home-projet-section">
  <div class="home-projet" data-room-picker>
    <div class="room-picker-inner">
      <div class="conseiller-sig">
        <span class="conseiller-sig__avatar"><?php echo sapi_image('2026/03/Robin-face-avec-Alice-lhelice.jpg', 'medium', ['alt' => 'Robin, artisan de l\'Atelier Sâpi', 'class' => 'conseiller-sig__img', 'loading' => 'lazy']); ?></span>
        <span class="conseiller-sig__text">
          <span class="conseiller-sig__who">Le conseil de Robin</span>
          <span class="conseiller-sig__hook">Mon regard d'artisan sur ton projet</span>
        </span>
      </div>
      <h2 class="room-picker-title">Pour quelle pièce cherches-tu un luminaire ?</h2>
      <div class="room-picker-cards">
        <?php foreach ($room_choices as $room) :
          $icon_svg = isset($room_icons[$room['icon']]) ? $room_icons[$room['icon']] : '';
        ?>
          <a class="room-card" href="<?php echo esc_url(home_url('/mes-creations/?piece=' . $room['slug'])); ?>" data-piece="<?php echo esc_attr($room['slug']); ?>">
            <span class="room-card-icon"><?php echo $icon_svg; ?></span>
            <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="room-picker-or" aria-hidden="true">
        <span class="room-picker-or__text">ou</span>
      </div>
      <form class="room-picker-freetext" data-room-picker-freetext>
        <input type="text" class="room-picker-freetext__input" name="freetext"
               placeholder="Décris ton projet en quelques mots…" maxlength="500"
               aria-label="Décris ton projet en quelques mots">
        <button type="submit" class="room-picker-freetext__submit" aria-label="Envoyer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </button>
      </form>
    </div>
  </div>
</section>

<!-- Collections Carousel / Grid -->
<section class="collections-kinetic">
  <div class="section-header-kinetic">
    <span class="section-num">01</span>
    <h2 class="section-title-kinetic">Collections</h2>
    <div class="collections-nav">
      <button type="button" class="carousel-arrow collections-nav-prev" aria-label="Collections précédentes">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <button type="button" class="carousel-arrow collections-nav-next" aria-label="Collections suivantes">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>

  <div class="collections-grid">
    <?php foreach ($collections as $collection) : ?>
      <a href="<?php echo esc_url($collection['url']); ?>" class="collection-card">
        <div class="collection-visual">
          <?php echo wp_get_attachment_image($collection['image_id'], 'large', false, ['class' => 'collection-visual-img', 'loading' => 'lazy', 'alt' => 'Collection ' . $collection['name'] . ', luminaires en bois']); ?>
        </div>
        <div class="collection-details">
          <h3><?php echo esc_html($collection['name']); ?></h3>
          <?php if (!empty($collection['desc'])) : ?><p class="collection-desc"><?php echo esc_html($collection['desc']); ?></p><?php endif; ?>
          <div class="collection-meta">
            <span class="collection-count"><?php echo esc_html($collection['count']); ?></span>
            <span class="collection-btn">→</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
      <a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>" class="collection-card collection-card--surmesure">
        <div class="collection-visual">
          <?php echo sapi_image('2026/04/Photo-Trio-de-34.jpg', 'large', ['class' => 'collection-visual-img', 'loading' => 'lazy', 'alt' => 'Luminaire sur mesure, Atelier Sâpi']); ?>
        </div>
        <div class="collection-details">
          <h3>Sur mesure</h3>
          <p class="collection-desc">Une pièce unique, pensée avec toi.</p>
          <div class="collection-meta"><span class="collection-count">Ton projet unique</span><span class="collection-btn">→</span></div>
        </div>
      </a>
  </div>
</section>

<!-- Les créations du moment (refonte home #2) — regroupe Star + produits featured + CTA -->
<div class="section-band--warm">
<section class="hero-bento home-creations">
  <div class="section-header-kinetic">
    <span class="section-num">02</span>
    <h2 class="section-title-kinetic">Les créations du moment</h2>
  </div>
  <div class="creations-grid">

    <?php if ($star_product_data) : ?>
    <a href="<?php echo esc_url($star_product_data['url']); ?>" class="creation-star">
      <?php echo wp_get_attachment_image($star_product_data['image_id'], 'woocommerce_single', false, ['class' => 'creation-star-img', 'loading' => 'lazy', 'alt' => $star_product_data['name'] . ', star du moment']); ?>
      <span class="bento-bestseller-badge">Star du moment</span>
      <div class="creation-star-label">
        <h3 class="product-name"><?php echo esc_html($star_product_data['name']); ?></h3>
        <?php if ($star_product_data['category']) : ?><p><?php echo esc_html($star_product_data['category']); ?></p><?php endif; ?>
      </div>
    </a>
    <?php endif; ?>

    <div class="creations-products"><?php foreach ($featured_products as $fp) : ?>
    <div class="product-card-cinetique" data-product-id="<?php echo esc_attr($fp['id']); ?>" data-piece-swap data-piece-swap-type="ambiance" data-piece-swap-size="large">
      <a href="<?php echo esc_url($fp['url']); ?>" class="product-card-link">
        <div class="product-media<?php echo !empty($fp['hover_id']) ? ' has-hover-image' : ''; ?>">
          <span class="product-image-main"><?php echo wp_get_attachment_image($fp['ambiance_id'], 'large', false, ['alt' => $fp['name'], 'loading' => 'lazy']); ?></span>
          <?php if (!empty($fp['hover_id'])) : ?>
            <span class="product-image-hover"><?php echo wp_get_attachment_image($fp['hover_id'], 'woocommerce_thumbnail', false, ['alt' => $fp['name'] . ' - ambiance', 'loading' => 'lazy']); ?></span>
          <?php endif; ?>
        </div>
        <div class="product-info">
          <h3 class="product-name"><?php echo esc_html($fp['name']); ?></h3>
          <?php if (!empty($fp['category'])) : ?><p class="product-category"><?php echo esc_html($fp['category']); ?></p><?php endif; ?>
          <div class="product-price">
            <?php if (!empty($fp['is_variable'])) : ?><span class="price-from">À partir de</span><?php endif; ?>
            <span class="price-value"><?php echo wp_kses_post($fp['price']); ?></span>
          </div>
        </div>
        <div class="product-actions"><span class="btn-view">Découvrir ⇾</span></div>
      </a>
    </div>
    <?php endforeach; ?></div>

  </div>
  <div class="creations-cta">
    <a href="<?php echo home_url('/mes-creations/'); ?>" class="hero-cta">Voir toutes les créations</a>
  </div>
</section>
</div>

<!-- L'atelier (refonte home #3) — storytelling + photo atelier + process + texte SEO -->
<?php
// URLs catégories pour le maillage interne (slugs canon : voir tax_query carousel)
$sapi_cat_url = function ($slug) {
  $t = get_term_by('slug', $slug, 'product_cat');
  $l = $t ? get_term_link($t) : '';
  return (!is_wp_error($l) && $l) ? $l : home_url('/mes-creations/');
};
?>
<!-- L'atelier — immersion par la lumière (refonte DA, mockup-da-04c) -->
<?php
$atelier_default_img = '2025/04/A7404411.jpg'; // fond par défaut = luminaire allumé (modifiable ; sinon une photo d'atelier chaude)
// $process_steps : [num, label, photo, alt, phrase manuscrite] — repris à l'identique de l'existant
$process_steps = [
  ['01', 'Dessin',        '2025/05/IMG_1928-e1761747188966.png', "Dessin d'un luminaire en bois, Atelier Sâpi",          "Tout commence par un trait de crayon"],
  ['02', 'Découpe laser', '2025/05/IMG_7638.jpg',                'Découpe laser du bois pour luminaire',                 "Le laser suit mon dessin au dixième près"],
  ['03', 'Finitions',     '2025/09/Poncage.jpg',                 "Ponçage manuel d'un luminaire en bois, Atelier Sâpi",  "Le ponçage, c'est ma méditation"],
  ['04', 'Assemblage',    '2026/03/Robin-a-lassemblage.jpg',     'Robin assemble un luminaire dans son atelier à Lyon',  "Chaque pièce s'emboîte sans une vis"],
  ['05', 'Expédition',    '2026/06/Expedition.jpg',              "Luminaire emballé prêt pour l'expédition, Atelier Sâpi","Emballé comme si c'était pour ma mère"],
];
?>
<section class="home-atelier home-atelier--lumiere" id="home-atelier">
  <div class="home-atelier__bgstack" aria-hidden="true">
    <span class="home-atelier__bg is-on" data-key="default"><?php echo sapi_image($atelier_default_img, 'large', ['class' => 'home-atelier__bgimg', 'alt' => '', 'loading' => 'lazy']); ?></span>
    <?php foreach ($process_steps as $i => $step) : ?>
      <span class="home-atelier__bg" data-key="<?php echo (int)($i + 1); ?>"><?php echo sapi_image($step[2], 'large', ['class' => 'home-atelier__bgimg', 'alt' => '', 'loading' => 'lazy']); ?></span>
    <?php endforeach; ?>
  </div>
  <div class="home-atelier__veil" aria-hidden="true"></div>
  <div class="home-atelier__veil-bottom" aria-hidden="true"></div>

  <div class="home-atelier__inner">
    <div class="section-header-kinetic"><span class="section-num">03</span><h2 class="section-title-kinetic">L'atelier</h2></div>
    <span class="atelier-eyebrow">L'atelier · Lyon</span>
    <h3 class="atelier-band-title">Des sculptures lumineuses</h3>
    <p class="storytelling-text">Du croquis à l'assemblage final, chaque pièce est façonnée dans mon atelier lyonnais. Le bois prend forme sous mes mains, la lumière fait le reste.</p>
    <p class="storytelling-text storytelling-text--seo">Je dessine et fabrique à la commande des <a href="<?php echo esc_url($sapi_cat_url('suspensions')); ?>">suspensions</a>, <a href="<?php echo esc_url($sapi_cat_url('appliques')); ?>">appliques</a>, <a href="<?php echo esc_url($sapi_cat_url('lampesaposer')); ?>">lampes à poser</a> et <a href="<?php echo esc_url($sapi_cat_url('lampadaires')); ?>">lampadaires</a> en bois. Chaque luminaire est dessiné sur une feuille de papier, puis modélisé à l'ordinateur, découpé au laser et enfin assemblé à la main. Chez vous, le peuplier clair ou l'okoumé chaleureux filtreront la lumière et dessineront des ombres uniques.</p>
    <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="hero-cta hero-cta--wood">Découvrir l'artisan</a>
  </div>

  <div class="atelier-steps" id="atelier-steps">
    <span class="atelier-steps__title">Mon processus artisanal</span>
    <?php foreach ($process_steps as $i => $step) : ?>
    <button type="button" class="atelier-step" data-bg="<?php echo (int)($i + 1); ?>" title="« <?php echo esc_attr($step[4]); ?> »">
      <span class="atelier-step__n"><?php echo esc_html($step[0]); ?></span>
      <span class="atelier-step__t"><?php echo esc_html($step[1]); ?></span>
    </button>
    <?php endforeach; ?>
  </div>
</section>

<!-- Ils en parlent (refonte home #4) — avis Google, réutilise les composants de la fiche produit -->
<?php $home_reviews = sapi_get_google_reviews(); ?>
<?php if ($home_reviews && !empty($home_reviews['reviews'])) : ?>
<section class="product-testimonials home-avis">
  <div class="testimonials-header">
    <span class="section-num">04</span>
    <h2>Ils en parlent</h2>
    <div class="home-avis__links">
      <a href="https://g.page/r/CQ0YW1uBzOimEAE/review" target="_blank" rel="noopener noreferrer">Laisser un avis</a>
      <span class="home-avis__links-sep" aria-hidden="true">·</span>
      <a href="https://www.google.com/maps/place/?q=place_id:ChIJYyWUfZOV9EcRDRhbW4HM6KY" target="_blank" rel="noopener noreferrer">Voir les <?php echo esc_html($home_reviews['total']); ?> avis</a>
    </div>
    <div class="google-reviews-badge">
      <svg class="google-logo" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
      <div class="google-reviews-summary">
        <div class="google-stars">
          <?php
          $home_rating = $home_reviews['rating'];
          for ($i = 1; $i <= 5; $i++) :
            if ($i <= floor($home_rating)) : ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="#FBBC05"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?php else : ?>
              <svg width="14" height="14" viewBox="0 0 24 24" fill="#ddd"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <?php endif;
          endfor; ?>
        </div>
        <span class="google-rating-text"><?php echo esc_html($home_rating); ?>/5 · <?php echo esc_html($home_reviews['total']); ?> avis</span>
      </div>
    </div>
  </div>

  <div class="testimonials-grid">
    <?php
    $reviews_pool = $home_reviews['reviews'];
    shuffle($reviews_pool); // variété à chaque chargement
    // P14 : français d'abord (tri stable — préserve l'ordre mélangé au sein de chaque langue)
    usort($reviews_pool, function ($a, $b) {
      $fa = (stripos($a['lang'] ?? '', 'fr') === 0) ? 0 : 1;
      $fb = (stripos($b['lang'] ?? '', 'fr') === 0) ? 0 : 1;
      return $fa <=> $fb;
    });
    $reviews_display = array_slice($reviews_pool, 0, 3);
    ?>
    <?php foreach ($reviews_display as $review) : ?>
    <div class="testimonial-card">
      <div class="testimonial-card-header">
        <?php
          // P13 : avatar = initiales sur disque (zéro image/bleu Google)
          $av_parts = preg_split('/\s+/', trim($review['author']));
          $av_initials = '';
          if (!empty($av_parts[0])) $av_initials .= mb_substr($av_parts[0], 0, 1);
          if (count($av_parts) > 1) $av_initials .= mb_substr(end($av_parts), 0, 1);
          $av_initials = mb_strtoupper($av_initials);
        ?>
        <span class="testimonial-avatar testimonial-avatar--initials" aria-hidden="true"><?php echo esc_html($av_initials); ?></span>
        <div class="testimonial-author-info">
          <span class="author-name"><?php echo esc_html($review['author']); ?></span>
          <span class="author-time"><?php echo esc_html($review['time']); ?></span>
        </div>
      </div>
      <div class="testimonial-rating">
        <?php for ($i = 1; $i <= 5; $i++) : ?>
          <?php if ($i <= $review['rating']) : ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="#FBBC05"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <?php else : ?>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="#ddd"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php
        $text = $review['text'];
        $short = $text;
        if (mb_strlen($text) > 200) {
          $short = mb_substr($text, 0, 200);
          $short = mb_substr($short, 0, mb_strrpos($short, ' ')) . '…';
        }
      ?>
      <p class="testimonial-text"><?php echo esc_html($short); ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <?php
  // Ils parlent de nous — références presse. Ajouter une ligne par référence :
  // 'logo' = chemin média (ex. '2026/06/logo-xxx.png') ; vide => le nom s'affiche en texte.
  $press_refs = [
    ['name' => 'Maisons Actuelle', 'url' => 'https://maisonsactuelle.com/2026/01/13/atelier-sapi-la-ou-la-lumiere-retrouve-dans-le-bois-le-souvenir-secret-de-la-nature/', 'logo' => '2026/06/cropped-cropped-cropped-LogoMAISONactuelle.png'],
    ['name' => "L'univers de la maison", 'url' => 'https://luniversdelamaison-lemag.com/decoration/luminaire/2873-quand-le-bois-devient-lumiere', 'logo' => '2026/06/images.jpeg'],
    ['name' => 'Le Progrès', 'url' => 'https://www.leprogres.fr/economie/2025/11/13/un-artisan-createur-de-luminaire-ouvre-son-atelier', 'logo' => '2026/06/le-nouveau-logo-de-votre-journal-1665505929.jpg'],
    ['name' => 'Région Auvergne-Rhône-Alpes', 'url' => 'https://www.auvergnerhonealpes-orientation.fr/laureats-auverboost-lyon-decembre-2025/', 'logo' => '2026/06/logo-white.png.webp'],
    ['name' => "Collonges-au-Mont-d'Or", 'url' => 'https://www.collongesaumontdor.fr/mon-quotidien/vie-economique/commercants-et-artisans/?filter_sectors%5B0%5D=commercants-artisans-entreprises', 'logo' => '2026/06/filemanager-2827393906.jpg'],
  ];
  if (!empty($press_refs)) : ?>
  <div class="home-press">
    <span class="home-press__label">Ils parlent de nous</span>
    <div class="home-press__logos">
      <?php foreach ($press_refs as $ref) : ?>
      <a class="home-press__item" href="<?php echo esc_url($ref['url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($ref['name']); ?>">
        <?php if (!empty($ref['logo'])) : ?>
          <?php echo sapi_image($ref['logo'], 'medium', ['class' => 'home-press__logo', 'alt' => $ref['name'], 'loading' => 'lazy']); ?>
        <?php else : ?>
          <span class="home-press__name"><?php echo esc_html($ref['name']); ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
<?php endif; ?>

<!-- Bande citation Robin (refonte #14, dispo A) — entre « Ils en parlent » et le bento divers -->
<?php $quote_band_img = '2026/06/Robin-Shooting.jpg'; // photo de Robin (shooting) ?>
<section class="quote-band">
  <?php echo sapi_image($quote_band_img, 'large', ['class' => 'quote-band__bg', 'loading' => 'lazy', 'alt' => 'Robin, artisan de l\'Atelier Sâpi, dans son atelier à Lyon']); ?>
  <div class="quote-band__scrim" aria-hidden="true"></div>
  <div class="quote-inner">
    <span class="q-mark" aria-hidden="true">«</span>
    <p class="q-text">Éclairer une pièce, c'est un peu comme choisir la bonne sauce pour ses pâtes : tout est une question de préférence et de dosage !</p>
    <div class="q-sig">
      <span class="who"><b>Robin</b><span>artisan de l'Atelier Sâpi · Lyon</span></span>
    </div>
    <div class="q-links">
      <a class="q-link-cta" href="<?php echo esc_url(home_url('/conseils-eclaires/')); ?>">Les conseils (sérieux) de Robin</a>
      <a class="q-link-discreet" href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>">Faire connaissance →</a>
    </div>
  </div>

  <a class="loc-card" href="https://maps.app.goo.gl/a3MiaeoG3ySfyUQT9" target="_blank" rel="noopener noreferrer" aria-label="Venir me voir à l'atelier, voir l'itinéraire sur Google Maps">
    <div class="loc-media">
      <svg viewBox="0 0 520 280" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <rect width="520" height="280" fill="#E7DCC8"/>
        <path d="M -20 60 C 130 100, 190 30, 330 110 S 520 140, 560 120 L 560 190 C 420 170, 320 220, 200 190 S 40 220, -20 200 Z" fill="#D5C5A9"/>
        <g stroke="#C4B393" stroke-width="6" fill="none" stroke-linecap="round"><path d="M 60 0 L 130 100 L 270 140 L 360 280"/><path d="M 0 170 L 210 140 L 430 80 L 520 100"/></g>
        <g fill="#DCCDB3"><rect x="100" y="110" width="26" height="20" rx="3"/><rect x="310" y="92" width="28" height="22" rx="3"/><rect x="210" y="170" width="24" height="18" rx="3"/></g>
        <g transform="translate(265,128)"><ellipse cx="0" cy="34" rx="14" ry="4" fill="rgba(74,63,53,.18)"/><path d="M 0 30 C -16 6, -16 -10, 0 -10 C 16 -10, 16 6, 0 30 Z" fill="#E35B24"/><circle cx="0" cy="-2" r="6" fill="#FBF6EA"/></g>
      </svg>
      <div class="loc-invite">À 15 min de Lyon, viens voir où ça se fabrique</div>
    </div>
    <div class="loc-foot">
      <div><div class="loc-foot__ttl">Venir me voir à l'atelier</div><div class="loc-foot__adr">3 Rue Pierre Termier · Collonges-au-Mont-d'Or</div></div>
      <span class="loc-foot__go">Itinéraire <span class="loc-foot__arr">→</span></span>
    </div>
  </a>
</section>

<!-- Cadeau + Actus (refonte DA #7, variante C) — objet iconique vs journal -->
<section class="home-cadeau-actus">
  <div class="ca-grid">
    <?php if ($gift_card) : ?>
    <a href="<?php echo esc_url($gift_card['url']); ?>" class="gift-object">
      <span class="gift-object__photo" aria-hidden="true"><?php echo sapi_image('2025/09/Carte-de-visite-3.jpg', 'large', ['class' => 'gift-object__photoimg', 'alt' => '', 'loading' => 'lazy']); ?></span>
      <span class="gift-object__halo" aria-hidden="true"></span>
      <span class="gift-object__badge">Offrir de la lumière</span>
      <span class="gift-object__body">
        <span class="gift-object__lamp" aria-hidden="true">✦</span>
        <span class="gift-object__title">La carte cadeau</span>
        <span class="gift-object__text">Tu hésites sur le modèle ? Offre une carte cadeau : la bonne personne choisira son luminaire, allumé à la main rien que pour elle.</span>
        <span class="gift-object__cta">Offrir une carte cadeau <span class="arr">→</span></span>
      </span>
    </a>
    <?php endif; ?>
    <?php
    $last_actu = new WP_Query(['posts_per_page'=>1,'post_status'=>'publish','category_name'=>'flash-actu','orderby'=>'date','order'=>'DESC']);
    if ($last_actu->have_posts()) : $last_actu->the_post();
    ?>
    <a href="<?php the_permalink(); ?>" class="news-journal">
      <span class="news-journal__photo"><?php if (has_post_thumbnail()) echo get_the_post_thumbnail(get_the_ID(), 'large', ['loading'=>'lazy','alt'=>get_the_title()]); ?></span>
      <span class="news-journal__body">
        <span class="news-journal__meta"><span class="news-journal__eyebrow">Le journal de l'atelier</span><span class="news-journal__date">· <?php echo esc_html(get_the_date('j F Y')); ?></span></span>
        <span class="news-journal__title"><?php echo esc_html(get_the_title()); ?></span>
        <span class="news-journal__chapo"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 26)); ?></span>
        <span class="news-journal__read">Lire l'article <span class="arr">→</span></span>
      </span>
    </a>
    <?php wp_reset_postdata(); endif; ?>
  </div>
  <div class="ca-allnews"><a href="<?php echo esc_url(home_url('/actus/')); ?>" class="ca-allnews__btn">Voir toutes les actus <span class="arr">→</span></a></div>
</section>

<!-- Newsletter Section (refonte DA #8, variante B — bois chaud) -->
<section class="newsletter-kinetic newsletter--band">
  <?php echo sapi_image('2025/04/IMG_5851.jpg', 'large', ['class' => 'newsletter__bg', 'loading' => 'lazy', 'alt' => '']); ?>
  <div class="newsletter__veil" aria-hidden="true"></div>
  <div class="newsletter__inner">
    <span class="newsletter__eyebrow">La lettre de l'atelier</span>
    <h2 class="newsletter__title">Reste dans la lumière</h2>
    <p class="newsletter-subtitle">Une fois par mois, je te raconte un nouveau modèle, un coin de l'atelier, une astuce déco. Pas de spam, juste l'essentiel.</p>
  <form class="newsletter-form" action="#" method="post" id="newsletter-form">
    <!-- Honeypot anti-spam -->
    <div style="display:none;" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
    <input type="email" name="email" placeholder="votre@email.fr" class="newsletter-input-kinetic" required />
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
    <p class="newsletter__fineprint">Désinscription en un clic. Je ne partage jamais ton adresse.</p>
  </div>
</section>

<script>
(function() {
  const carousel = document.querySelector('.homepage-carousel-fullscreen');

  // 1. Déplacer le bandeau réassurance juste sous le carousel
  const reassuranceBar = document.querySelector('.robin-bandeau');
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

  // 4. Collections — flèches desktop (scroll horizontal)
  var collectionsGrid = document.querySelector('.collections-grid');
  if (collectionsGrid) {
    var firstCard = collectionsGrid.querySelector('.collection-card');
    var collStep = firstCard ? firstCard.getBoundingClientRect().width + 24 : 320;
    var collPrev = document.querySelector('.collections-nav-prev');
    var collNext = document.querySelector('.collections-nav-next');
    if (collPrev) collPrev.addEventListener('click', function () { collectionsGrid.scrollBy({ left: -collStep, behavior: 'smooth' }); });
    if (collNext) collNext.addEventListener('click', function () { collectionsGrid.scrollBy({ left: collStep, behavior: 'smooth' }); });
  }
})();

// L'atelier — crossfade du fond au survol des pills d'étape (refonte DA #1)
(function(){
  var sec = document.getElementById('home-atelier'); if(!sec) return;
  var layers = {}; sec.querySelectorAll('.home-atelier__bg').forEach(function(el){ layers[el.dataset.key] = el; });
  function show(k){ Object.keys(layers).forEach(function(x){ layers[x].classList.remove('is-on'); }); (layers[k]||layers['default']).classList.add('is-on'); }
  var steps = sec.querySelector('.atelier-steps');
  sec.querySelectorAll('.atelier-step').forEach(function(b){
    b.addEventListener('mouseenter', function(){ show(b.dataset.bg); });
    b.addEventListener('focus', function(){ show(b.dataset.bg); });
    b.addEventListener('click', function(){ show(b.dataset.bg); }); // tap mobile
  });
  if(steps){
    steps.addEventListener('mouseleave', function(){ show('default'); });
    steps.addEventListener('focusout', function(e){ if(!steps.contains(e.relatedTarget)) show('default'); });
  }
})();
</script>

<?php
get_footer();

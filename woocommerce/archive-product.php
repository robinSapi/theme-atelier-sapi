<?php
/**
 * The Template for displaying product archives
 *
 * SAPI CINÉTIQUE - Shop page with carousel, client-side filters, and hover effects
 *
 * @package Sapi-Maison
 * @version 9.5.1
 */

defined('ABSPATH') || exit;

get_header();

// Get all product categories for filters
$product_categories = get_terms([
  'taxonomy' => 'product_cat',
  'hide_empty' => true,
  'exclude' => [get_option('default_product_cat')], // Exclude "Uncategorized"
  'orderby' => 'menu_order',
  'order' => 'ASC',
]);

// Get ALL products (no pagination)
$all_products = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => -1,
  'post_status' => 'publish',
  'orderby' => 'menu_order date',
  'order' => 'ASC',
]);
?>

<!-- Hero Section - Magazine Style -->
<?php
// Hero image: featured product gallery image (ambiance photo)
$hero_img_url = '';
$hero_alt = 'Mes Créations - Atelier Sâpi';
if (!$hero_img_url) {
  $hero_products = wc_get_products([
    'limit'    => 1,
    'status'   => 'publish',
    'featured' => true,
    'return'   => 'objects',
  ]);

  if (empty($hero_products)) {
    $hero_products = wc_get_products([
      'limit'   => 1,
      'status'  => 'publish',
      'orderby' => 'date',
      'order'   => 'DESC',
      'return'  => 'objects',
    ]);
  }

  if (!empty($hero_products)) {
    $hero_product = $hero_products[0];
    // Try gallery first (ambiance photo), then main image
    $gallery_ids = $hero_product->get_gallery_image_ids();
    if (!empty($gallery_ids)) {
      $hero_img_url = wp_get_attachment_image_url($gallery_ids[0], 'full');
      $hero_alt = $hero_product->get_name() . ' - ambiance';
    } else {
      $hero_img_id = $hero_product->get_image_id();
      $hero_img_url = $hero_img_id
        ? wp_get_attachment_image_url($hero_img_id, 'full')
        : wc_placeholder_img_src('full');
      $hero_alt = $hero_product->get_name();
    }
  }
}
?>
<section class="shop-hero-artisan">
  <div class="shop-hero-artisan-inner">
    <h1><?php esc_html_e('Mes Créations', 'theme-sapi-maison'); ?></h1>
    <p class="shop-hero-artisan-subtitle">
      <?php esc_html_e('Luminaires uniques, découpés au laser et assemblés à la main dans l\'atelier lyonnais de Robin.', 'theme-sapi-maison'); ?>
    </p>
    <!-- CTA maillage interne → Conseils éclairés -->
    <p class="seo-cta-maillage-inline">Vous ne savez pas par où commencer ? <a href="<?php echo esc_url(home_url('/conseils-eclaires/')); ?>">Lisez les conseils de Robin →</a></p>
  </div>
</section>

<!-- Conseil personnalisé de Robin pour Mes Créations (shown by mon-projet.js if available) -->
<?php
require_once get_template_directory() . '/inc/template-robin-conseil.php';
sapi_robin_conseil_card( 'selection' );
?>

<!-- Product Filters with dynamic counts -->
<div class="product-filters-wrapper">
  <!-- Search Bar -->
  <div class="product-search-bar">
    <svg class="search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <circle cx="11" cy="11" r="8"></circle>
      <path d="m21 21-4.35-4.35"></path>
    </svg>
    <input
      type="text"
      id="product-search-input"
      class="product-search-input"
      placeholder="<?php esc_attr_e('Rechercher un luminaire...', 'theme-sapi-maison'); ?>"
      aria-label="<?php esc_attr_e('Rechercher un produit', 'theme-sapi-maison'); ?>"
    />
    <button type="button" class="search-clear" style="display: none;" aria-label="<?php esc_attr_e('Effacer la recherche', 'theme-sapi-maison'); ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
  </div>

  <nav class="product-filters product-filters-js" role="navigation" aria-label="<?php esc_attr_e('Filtres produits', 'theme-sapi-maison'); ?>">
    <?php
    // Build category lookup
    $cats_by_slug = [];
    if ($product_categories && !is_wp_error($product_categories)) {
      foreach ($product_categories as $cat) {
        $cats_by_slug[$cat->slug] = $cat;
      }
    }

    // Count for "Toutes nos créations" = total minus extras
    $extras_slugs = ['accessoires', 'carte-cadeau'];
    $creations_count = $all_products->found_posts;
    foreach ($extras_slugs as $es) {
      if (isset($cats_by_slug[$es])) {
        $creations_count -= $cats_by_slug[$es]->count;
      }
    }
    ?>

    <!-- Dropdown mobile (remplace les pills sur mobile) -->
    <?php
    $creations_order = ['suspensions', 'appliques', 'lampadaires', 'lampesaposer'];
    $all_filter_label = esc_html__('Toutes mes créations', 'theme-sapi-maison') . ' (' . esc_html($creations_count) . ')';
    ?>
    <div class="filter-dropdown filter-dropdown--mobile" id="mobile-category-dropdown">
      <button type="button" class="filter-dropdown-toggle">
        <span class="filter-label"><?php echo $all_filter_label; ?></span>
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <div class="filter-dropdown-menu">
        <button type="button" class="filter-option active" data-filter="all"><?php echo $all_filter_label; ?></button>
        <?php foreach ($creations_order as $slug) :
          if (!isset($cats_by_slug[$slug])) continue;
          $cat = $cats_by_slug[$slug]; ?>
          <button type="button" class="filter-option" data-filter="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?>)</button>
        <?php endforeach; ?>
        <?php foreach ($extras_slugs as $slug) :
          if (!isset($cats_by_slug[$slug])) continue;
          $cat = $cats_by_slug[$slug]; ?>
          <button type="button" class="filter-option" data-filter="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?>)</button>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Ligne 1 : Filtres catégorie (luminaires) — desktop only -->
    <div class="filter-row filter-row--categories">
      <button type="button" class="filter-btn active" data-filter="all">
        <?php esc_html_e('Toutes mes créations', 'theme-sapi-maison'); ?>
        <span class="filter-count">(<?php echo esc_html($creations_count); ?>)</span>
      </button>
      <?php foreach ($creations_order as $slug) :
        if (!isset($cats_by_slug[$slug])) continue;
        $cat = $cats_by_slug[$slug]; ?>
        <button type="button" class="filter-btn" data-filter="<?php echo esc_attr($cat->slug); ?>">
          <?php echo esc_html($cat->name); ?>
          <span class="filter-count">(<?php echo esc_html($cat->count); ?>)</span>
        </button>
      <?php endforeach; ?>
    </div>

    <!-- Ligne 2 : Extras (accessoires, carte cadeau) — desktop only -->
    <div class="filter-row filter-row--extras">
      <?php
      foreach ($extras_slugs as $slug) :
        if (!isset($cats_by_slug[$slug])) continue;
        $cat = $cats_by_slug[$slug];
        $btn_class = 'filter-btn filter-btn--extra';
        if ($slug === 'carte-cadeau') {
          $btn_class .= ' filter-btn--gift';
        }
      ?>
        <button type="button" class="<?php echo esc_attr($btn_class); ?>" data-filter="<?php echo esc_attr($cat->slug); ?>">
          <?php echo esc_html($cat->name); ?>
          <span class="filter-count">(<?php echo esc_html($cat->count); ?>)</span>
        </button>
      <?php endforeach; ?>
    </div>

  </nav>

</div>

<!-- ── Méga-filtre intelligent (F1a) ── -->
<?php
$megafilter_steps = sapi_guide_get_steps();
$megafilter_chip_labels = [
  'piece'           => __('Pour quelle pièce ?', 'theme-sapi-maison'),
  'taille'          => __('Quelle taille ?', 'theme-sapi-maison'),
  'taille_escalier' => __('Quel escalier ?', 'theme-sapi-maison'),
  'eclairage'       => __('Éclairage principal ?', 'theme-sapi-maison'),
  'sortie'          => __('Quelle sortie ?', 'theme-sapi-maison'),
  'hauteur'         => __('Quelle hauteur ?', 'theme-sapi-maison'),
  'table'           => __('Au-dessus d\'une table ?', 'theme-sapi-maison'),
  'style'           => __('Quel style ?', 'theme-sapi-maison'),
];
?>
<section class="megafilter-bar" id="megafilter-bar" aria-label="<?php esc_attr_e('Affiner les créations', 'theme-sapi-maison'); ?>">
  <div class="megafilter-bar-inner">
    <div class="megafilter-header">
      <h2 class="megafilter-title"><?php esc_html_e('Affiner avec Robin', 'theme-sapi-maison'); ?></h2>
      <p class="megafilter-intro"><?php esc_html_e('Réponds aux questions ci-dessous pour voir les modèles qui te correspondent.', 'theme-sapi-maison'); ?></p>
    </div>

    <div class="megafilter-chips" id="megafilter-chips" role="group" aria-label="<?php esc_attr_e('Filtres du projet', 'theme-sapi-maison'); ?>">
      <?php foreach ($megafilter_steps as $step) :
        $sid = $step['id'];
        $label = isset($megafilter_chip_labels[$sid]) ? $megafilter_chip_labels[$sid] : ucfirst($sid);
        $is_always = ($step['visibility'] === 'always');
      ?>
        <div class="megafilter-chip<?php echo $is_always ? '' : ' is-conditional'; ?>" data-step="<?php echo esc_attr($sid); ?>">
          <button type="button" class="megafilter-chip-toggle" aria-haspopup="listbox" aria-expanded="false">
            <span class="megafilter-chip-label"><?php echo esc_html($label); ?></span>
            <span class="megafilter-chip-value" hidden></span>
            <svg class="megafilter-chip-arrow" width="10" height="10" viewBox="0 0 12 12" fill="none" aria-hidden="true">
              <path d="M3 4.5l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
          <div class="megafilter-chip-menu" role="listbox" hidden>
            <?php foreach ($step['choices'] as $choice) : ?>
              <button type="button" class="megafilter-chip-option" role="option" data-value="<?php echo esc_attr($choice['slug']); ?>" data-label="<?php echo esc_attr($choice['label']); ?>">
                <?php echo esc_html($choice['label']); ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="megafilter-actions">
      <button type="button" class="megafilter-ai-btn" id="megafilter-open-ai">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/>
          <path d="m15 5 4 4"/>
        </svg>
        <span><?php esc_html_e('Décrire précisément mon projet', 'theme-sapi-maison'); ?></span>
      </button>
      <button type="button" class="megafilter-reset" id="megafilter-reset" hidden>
        <?php esc_html_e('Tout effacer', 'theme-sapi-maison'); ?>
      </button>
    </div>
  </div>
</section>

<!-- Products Grid — grille 2 colonnes photos ambiance -->
<section class="shop-products" id="shop-products">
  <?php if ($all_products->have_posts()) : ?>

    <?php
    /**
     * Sépare un nom de produit en prénom + surnom.
     */
    if (!function_exists('sapi_split_product_name')) {
      function sapi_split_product_name($title) {
        $words = explode(' ', $title, 2);
        return [
          'firstname' => $words[0],
          'surname'   => isset($words[1]) ? $words[1] : '',
        ];
      }
    }
    ?>

    <?php
    // Cards réassurance insérées dans la grille (visibles filtre "Tout" seulement — shop.js gère le masquage)
    $sapi_card_contents = [
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>',
        'title' => '100% artisanal français',
        'text' => 'Chaque luminaire est conçu, découpé et assemblé à la main dans l\'atelier lyonnais de Robin. Pas de production de masse, juste du savoir-faire et de la passion.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
        'title' => 'Pièces uniques & originales',
        'text' => 'Chaque modèle est une création originale signée Robin. Vous ne trouverez ces luminaires nulle part ailleurs. Votre intérieur sera unique, comme vous.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'title' => 'Bois PEFC & éco-responsable',
        'text' => 'Les bois proviennent de forêts gérées durablement (PEFC). Production locale, emballages recyclables, zéro gaspillage. Beauté et responsabilité vont de pair.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'title' => 'Service client réactif',
        'text' => 'Une question ? Robin est là pour vous accompagner personnellement, du choix à l\'installation. Réponse rapide garantie.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'title' => 'Fabriqué avec amour à Lyon',
        'text' => 'Vous recevez bien plus qu\'un objet : vous recevez une histoire, un bout de son atelier, une pièce qui porte son attention aux détails.',
        'highlight' => true,
      ],
    ];
    $sapi_card_zones = [[3, 6], [8, 11], [13, 16], [18, 21], [23, 26]];
    $sapi_text_cards = [];
    foreach ($sapi_card_zones as $i => $zone) {
      if (isset($sapi_card_contents[$i])) {
        $pos = wp_rand($zone[0], $zone[1]);
        $sapi_text_cards[$pos] = $sapi_card_contents[$i];
      }
    }
    $sapi_product_counter = 0;
    ?>

    <div class="product-grid" id="sapi-product-grid">
      <?php
      while ($all_products->have_posts()) :
        $all_products->the_post();
        global $product;
        $product = wc_get_product(get_the_ID());
        $product_id = $product->get_id();

        // Catégories pour le filtrage JS
        $product_cats = get_the_terms($product_id, 'product_cat');
        $cat_slugs = [];
        if ($product_cats && !is_wp_error($product_cats)) {
          foreach ($product_cats as $cat) {
            $cat_slugs[] = $cat->slug;
          }
        }
        $filter_categories = implode(' ', $cat_slugs);

        // Prix pour le filtrage
        $filter_price = $product->get_price();

        // Essence de bois
        $wood_terms = wc_get_product_terms($product_id, 'pa_essence-de-bois');
        $wood_essence = !empty($wood_terms) ? $wood_terms[0]->name : '';

        // Taille
        $size_dimension = 0;
        $taille_terms = wc_get_product_terms($product_id, 'pa_taille');
        if (!empty($taille_terms)) {
          preg_match('/\d+/', $taille_terms[0]->name, $m);
          if (!empty($m)) $size_dimension = (int) $m[0];
        }

        // Nombre de variations de taille (utile au filtrage taille=spacieuse)
        $taille_variations_count = count($taille_terms);

        // Méga-filtre — format luminaire (boule/horizontal/vertical) et type d'ampoule
        $format_terms = wc_get_product_terms($product_id, 'pa_format');
        $format_slugs = [];
        foreach ($format_terms as $t) { $format_slugs[] = $t->slug; }
        $format_attr = implode(' ', $format_slugs);

        $ampoule_terms = wc_get_product_terms($product_id, 'pa_type-ampoule');
        $ampoule_slugs = [];
        foreach ($ampoule_terms as $t) { $ampoule_slugs[] = $t->slug; }
        $ampoule_attr = implode(' ', $ampoule_slugs);

        // Photo ambiance ACF (sauf accessoires → photo produit WooCommerce)
        $is_accessoire = in_array('accessoires', $cat_slugs);
        $amb_photo_ids = !$is_accessoire ? sapi_get_product_photo_ids($product_id, 'ambiance', 1) : [];
        $ambiance_id = !empty($amb_photo_ids) ? $amb_photo_ids[0] : get_post_thumbnail_id($product_id);

        // Nom splitté
        $name_parts = sapi_split_product_name(get_the_title());

        // Prix min
        $is_variable = $product->is_type('variable');
        $price_min = $is_variable ? $product->get_variation_price('min') : $product->get_price();

        // Data attributes pour le filtrage shop.js
        $data_attrs  = 'data-id="' . esc_attr($product_id) . '"';
        $data_attrs .= ' data-categories="' . esc_attr($filter_categories) . '"';
        $data_attrs .= ' data-name="' . esc_attr(strtolower(get_the_title())) . '"';
        $data_attrs .= ' data-price="' . esc_attr($filter_price) . '"';
        $data_attrs .= $wood_essence ? ' data-wood="' . esc_attr(sanitize_title($wood_essence)) . '"' : '';
        $data_attrs .= $size_dimension > 0 ? ' data-size="' . esc_attr($size_dimension) . '"' : '';
        $data_attrs .= $format_attr ? ' data-format-luminaire="' . esc_attr($format_attr) . '"' : '';
        $data_attrs .= $ampoule_attr ? ' data-type-ampoule="' . esc_attr($ampoule_attr) . '"' : '';
        $data_attrs .= ' data-size-variations="' . esc_attr($taille_variations_count) . '"';
      ?>
        <?php
          // Catégorie affichée (singulier)
          $display_cat = '';
          if ($product_cats && !is_wp_error($product_cats)) {
            foreach ($product_cats as $cat) {
              if ($cat->slug !== 'uncategorized') {
                $display_cat = str_replace(
                  ['Suspensions', 'Appliques', 'Lampadaires', 'Lampes à poser'],
                  ['Suspension',  'Applique',  'Lampadaire',  'Lampe à poser'],
                  $cat->name
                );
                break;
              }
            }
          }

          // Hover image (1re photo galerie WooCommerce)
          $gallery_ids = $product->get_gallery_image_ids();
          $hover_id = !empty($gallery_ids) ? $gallery_ids[0] : 0;

          // Prix HTML
          $price_html = $is_variable ? wc_price($price_min) : $product->get_price_html();
        ?>
        <div class="product-card-cinetique" data-product-id="<?php echo esc_attr($product_id); ?>" <?php echo $data_attrs; ?>>
          <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="product-card-link">
            <div class="product-media<?php echo $hover_id ? ' has-hover-image' : ''; ?>">
              <?php if ($ambiance_id) : ?>
                <span class="product-image-main"><?php echo wp_get_attachment_image($ambiance_id, 'large', false, ['alt' => get_the_title(), 'loading' => 'lazy']); ?></span>
              <?php else : ?>
                <span class="product-image-main"><?php echo woocommerce_get_product_thumbnail('woocommerce_thumbnail'); ?></span>
              <?php endif; ?>
              <?php if ($hover_id) : ?>
                <span class="product-image-hover"><?php echo wp_get_attachment_image($hover_id, 'woocommerce_thumbnail', false, ['alt' => get_the_title() . ' - ambiance', 'loading' => 'lazy']); ?></span>
              <?php endif; ?>
              <div class="badge-selection" style="display:none;">Ma sélection</div>
            </div>

            <div class="product-info">
              <h3 class="product-name"><?php echo esc_html(get_the_title()); ?></h3>
              <?php if ($display_cat) : ?>
                <p class="product-category"><?php echo esc_html($display_cat); ?></p>
              <?php endif; ?>
              <div class="product-price">
                <?php if ($is_variable) : ?>
                  <span class="price-from"><?php esc_html_e('À partir de', 'theme-sapi-maison'); ?></span>
                <?php endif; ?>
                <span class="price-value"><?php echo $price_html; ?></span>
              </div>
            </div>

            <div class="product-actions">
              <span class="btn-view">
                <?php esc_html_e('Découvrir', 'theme-sapi-maison'); ?> ⇾
              </span>
            </div>
          </a>
        </div>
      <?php
        $sapi_product_counter++;
        if (isset($sapi_text_cards[$sapi_product_counter])) :
          $card = $sapi_text_cards[$sapi_product_counter];
          $card_class = 'product-text-card';
          if (!empty($card['highlight'])) $card_class .= ' product-text-card--highlight';
        ?>
        <div class="<?php echo esc_attr($card_class); ?>">
          <div class="product-text-card-inner">
            <div class="product-text-card-icon"><?php echo $card['icon']; ?></div>
            <h3><?php echo esc_html($card['title']); ?></h3>
            <p><?php echo esc_html($card['text']); ?></p>
            <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="product-text-card-discover">En savoir plus</a>
          </div>
        </div>
        <?php endif; ?>
      <?php endwhile; ?>
    </div>

    <!-- Grosse card récap "Pourquoi choisir Sâpi" — visible uniquement avec filtres actifs (shop.js) -->
    <div class="why-sapi-recap" style="display: none;">
      <div class="why-sapi-recap-inner">
        <h2>Pourquoi choisir l'Atelier Sâpi ?</h2>
        <div class="why-sapi-recap-grid">
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
            <h3>100% artisanal français</h3>
            <p>Chaque luminaire est conçu, découpé et assemblé à la main dans l'atelier lyonnais de Robin.</p>
          </div>
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></div>
            <h3>Pièces uniques & originales</h3>
            <p>Chaque modèle est une création originale signée Robin. Votre intérieur sera unique, comme vous.</p>
          </div>
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <h3>Bois PEFC & éco-responsable</h3>
            <p>Les bois proviennent de forêts gérées durablement (PEFC). Beauté et responsabilité vont de pair.</p>
          </div>
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <h3>Service client réactif</h3>
            <p>Robin est là pour vous accompagner personnellement, du choix à l'installation.</p>
          </div>
        </div>
        <div class="why-sapi-recap-highlight">
          <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
          <div>
            <h3>Fabriqué avec amour à Lyon</h3>
            <p>Vous recevez bien plus qu'un objet : vous recevez une histoire, un bout de l'atelier de Robin, une pièce qui porte son attention aux détails.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Badge "Ma sélection" — activé via localStorage Robin Conseiller -->
    <script>
    (function() {
      try {
        var prefs = JSON.parse(localStorage.getItem('sapiGuidePrefs'));
        if (!prefs || !prefs.recommendedIds || !prefs.recommendedIds.length) return;
        var ids = prefs.recommendedIds.map(function(id) { return String(id); });
        document.querySelectorAll('.product-card[data-product-id]').forEach(function(card) {
          if (ids.indexOf(card.getAttribute('data-product-id')) !== -1) {
            var badge = card.querySelector('.badge-selection');
            if (badge) badge.style.display = '';
          }
        });
      } catch(e) {}
    })();
    </script>

    <!-- Message "aucun résultat" pour le filtrage JS côté client -->
    <div class="woocommerce-no-products-found" style="display: none;">
      <p><?php esc_html_e('Aucun produit ne correspond à votre recherche.', 'theme-sapi-maison'); ?></p>
    </div>

  <?php else : ?>

    <div class="woocommerce-no-products-found">
      <p><?php esc_html_e('Aucun produit ne correspond à votre recherche.', 'theme-sapi-maison'); ?></p>
    </div>

  <?php endif; ?>
  <?php wp_reset_postdata(); ?>
</section>

<!-- Outro Section with CTA -->
<section class="shop-outro">
  <div class="shop-outro-content">
    <p class="shop-outro-text">
      <?php esc_html_e('Vous ne trouvez pas votre bonheur ?', 'theme-sapi-maison'); ?>
    </p>
    <p class="shop-outro-subtitle">
      <?php esc_html_e('Dites à Robin ce que vous imaginez et créez ensemble votre luminaire sur-mesure.', 'theme-sapi-maison'); ?>
    </p>
    <a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>" class="shop-outro-cta">
      <?php esc_html_e('Découvrir le sur mesure', 'theme-sapi-maison'); ?>
    </a>
  </div>
</section>

<!-- ── Modale "Décrire mon projet" (F1a — UI shell uniquement, IA en F1b) ── -->
<div class="megafilter-modal" id="megafilter-modal" role="dialog" aria-modal="true" aria-labelledby="megafilter-modal-title" hidden>
  <div class="megafilter-modal-header">
    <span class="megafilter-modal-title" id="megafilter-modal-title"><?php esc_html_e('Décrire mon projet', 'theme-sapi-maison'); ?></span>
    <button type="button" class="megafilter-modal-close" id="megafilter-modal-close" aria-label="<?php esc_attr_e('Fermer', 'theme-sapi-maison'); ?>">×</button>
  </div>

  <div class="megafilter-modal-body" id="megafilter-modal-body">
    <div class="megafilter-modal-start" id="megafilter-modal-start">
      <h3 class="megafilter-modal-headline"><?php esc_html_e('Décris ton projet', 'theme-sapi-maison'); ?></h3>
      <p class="megafilter-modal-intro"><?php esc_html_e('Je transforme tes mots en sélection de luminaires.', 'theme-sapi-maison'); ?></p>
      <div class="megafilter-modal-input-wrap">
        <input type="text" class="megafilter-modal-input" id="megafilter-modal-input-initial"
               placeholder="<?php esc_attr_e('Ex. : Un luminaire pour mon salon, au-dessus de la table…', 'theme-sapi-maison'); ?>"
               aria-label="<?php esc_attr_e('Décris ton projet en quelques mots', 'theme-sapi-maison'); ?>">
      </div>
      <div class="megafilter-modal-suggestions">
        <button type="button" class="megafilter-modal-sug" data-sim="suspension-salon-table">
          <?php esc_html_e('Une suspension moderne pour mon salon', 'theme-sapi-maison'); ?>
        </button>
        <button type="button" class="megafilter-modal-sug" data-sim="escalier">
          <?php esc_html_e('Quelque chose pour éclairer mon escalier', 'theme-sapi-maison'); ?>
        </button>
        <button type="button" class="megafilter-modal-sug" data-sim="lampe-chambre">
          <?php esc_html_e('Une lampe d\'appoint chambre bois clair', 'theme-sapi-maison'); ?>
        </button>
      </div>
    </div>

    <div class="megafilter-modal-chat" id="megafilter-modal-chat" hidden>
      <div class="megafilter-chat-msg megafilter-chat-msg--user">
        <div class="megafilter-chat-bubble" id="megafilter-chat-user-bubble"></div>
      </div>
      <div class="megafilter-chat-msg megafilter-chat-msg--robin">
        <div class="megafilter-chat-bubble" id="megafilter-chat-robin-bubble"></div>
        <div class="megafilter-chat-filters" id="megafilter-chat-filters" hidden>
          <strong><?php esc_html_e('Filtres appliqués :', 'theme-sapi-maison'); ?></strong>
          <span id="megafilter-chat-filters-list"></span>
        </div>
      </div>
    </div>
  </div>

  <div class="megafilter-modal-return" id="megafilter-modal-return" hidden>
    <button type="button" class="megafilter-modal-return-btn" id="megafilter-modal-return-btn">
      <?php esc_html_e('Voir la sélection', 'theme-sapi-maison'); ?>
      (<span id="megafilter-modal-return-num">0</span> <?php esc_html_e('modèles', 'theme-sapi-maison'); ?>) →
    </button>
  </div>

  <div class="megafilter-modal-footer" id="megafilter-modal-footer" hidden>
    <input type="text" class="megafilter-modal-input megafilter-modal-input--footer"
           placeholder="<?php esc_attr_e('Continuer à discuter avec Robin…', 'theme-sapi-maison'); ?>"
           aria-label="<?php esc_attr_e('Message', 'theme-sapi-maison'); ?>" disabled>
    <button type="button" class="megafilter-modal-send" disabled><?php esc_html_e('Envoyer', 'theme-sapi-maison'); ?></button>
  </div>
</div>

<?php
get_footer();

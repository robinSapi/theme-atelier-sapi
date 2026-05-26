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

// Get ALL products (no pagination)
$all_products = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => -1,
  'post_status' => 'publish',
  'orderby' => 'menu_order date',
  'order' => 'ASC',
]);

// Pills catégorie (Chantier 3) — récupère les catégories WC avec leurs counts.
// Ordre fixe : suspensions / lampadaires / lampesaposer / appliques / accessoires.
// Le total "Tous" exclut uniquement carte-cadeau (gift card, hors créations).
$mes_creations_cat_order = ['suspensions', 'lampadaires', 'lampesaposer', 'appliques', 'accessoires'];
$mes_creations_cats_raw  = get_terms([
  'taxonomy'   => 'product_cat',
  'hide_empty' => true,
  'exclude'    => [get_option('default_product_cat')],
]);
$mes_creations_cats_by_slug = [];
if ($mes_creations_cats_raw && !is_wp_error($mes_creations_cats_raw)) {
  foreach ($mes_creations_cats_raw as $cat) {
    $mes_creations_cats_by_slug[$cat->slug] = $cat;
  }
}
$mes_creations_total = $all_products->found_posts;
if (isset($mes_creations_cats_by_slug['carte-cadeau'])) {
  $mes_creations_total -= (int) $mes_creations_cats_by_slug['carte-cadeau']->count;
}
// Lecture du param GET ?product_cat= pour le filtre actif initial.
$mes_creations_active_cat = isset($_GET['product_cat']) ? sanitize_key(wp_unslash($_GET['product_cat'])) : '';
if ($mes_creations_active_cat && !isset($mes_creations_cats_by_slug[$mes_creations_active_cat])) {
  $mes_creations_active_cat = '';
}
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
<?php
// F2a-quinquies : hero qui s'adapte au changement de sapiProject.answers.piece
// en live (crossfade via sapi-hero-live.js). Le mapping est centralisé dans
// la fonction helper sapi_get_hero_piece_titles() (functions.php) — source
// unique partagée entre le rendu PHP initial et la localize JS.
// Sous-titre et CTA "Conseils de Robin" supprimés : le hero ne contient
// que H1 + photo de fond. La photo reste statique (refonte par pièce =
// chantier S28 séparé, en pause).
$hero_titles = function_exists('sapi_get_hero_piece_titles')
  ? sapi_get_hero_piece_titles()
  : ['default' => __('Mes Créations', 'theme-sapi-maison'), 'pieces' => []];
$piece_param = isset($_GET['piece']) ? sanitize_key(wp_unslash($_GET['piece'])) : '';
$hero_title  = isset($hero_titles['pieces'][$piece_param])
  ? $hero_titles['pieces'][$piece_param]
  : $hero_titles['default'];
?>
<section class="shop-hero-artisan">
  <div class="shop-hero-artisan-inner">
    <h1 data-hero-title><?php echo esc_html($hero_title); ?></h1>
  </div>
</section>

<!-- ── F2a Phase 2 — Cards Conseiller V3 (sans projet / avec projet) ── -->
<?php
// Icône SVG crayon (badge "Conseil de Robin" / "Mon projet") + CTA.
$conseiller_pencil_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>';

// Round 4 — Room picker dans la card Conseil (même contenu que la homepage).
require_once get_template_directory() . '/inc/guide-data.php';
$conseil_room_choices = [
  ['label' => 'Salon',   'slug' => 'salon',    'icon' => 'sofa'],
  ['label' => 'Cuisine', 'slug' => 'cuisine',  'icon' => 'dining'],
  ['label' => 'Chambre', 'slug' => 'chambre',  'icon' => 'bed'],
  ['label' => 'Bureau',  'slug' => 'bureau',   'icon' => 'monitor'],
  ['label' => 'Entrée',  'slug' => 'entree',   'icon' => 'door'],
  ['label' => 'Escalier','slug' => 'escalier', 'icon' => 'stairs'],
];
$conseil_room_icons = sapi_guide_get_icons();
?>
<!-- Refonte /mes-creations/ — Section "Ma sélection" : card englobante qui
     contient badge + phrase IA + slot grille (peuplé par sapi-cards-conseiller.js
     via clone des produits matchés depuis la grille basse "Toutes mes créations").
     Sans projet : card "Conseil de Robin" simple avec CTA → modale V3. -->
<section class="conseiller-cards-zone mes-creations-selection" data-conseiller-zone data-mes-creations-selection aria-label="<?php esc_attr_e('Ma sélection', 'theme-sapi-maison'); ?>">

  <!-- Card "Conseil de Robin" — visible sans projet. CTA orange → modale V3
       en création. Le room picker n'est plus ici (reste sur la home). -->
  <div class="conseiller-card conseiller-card--conseil" data-conseiller-card="conseil" hidden>
    <div class="conseiller-card__inner">
      <span class="conseiller-badge conseiller-badge--default">
        <?php echo $conseiller_pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <?php esc_html_e('Conseil de Robin', 'theme-sapi-maison'); ?>
      </span>
      <p class="conseiller-mon-projet__text">
        <span class="conseiller-mon-projet__text-content">
          <?php esc_html_e('Avant de tout regarder, dis-moi pour quelle pièce tu cherches — je te ferai une sélection adaptée à ton projet.', 'theme-sapi-maison'); ?>
        </span>
      </p>
      <button type="button" class="conseiller-cta" data-action="open-modal" data-modal-state="s0" data-conseil-cta>
        <span><?php esc_html_e('Démarrer mon projet', 'theme-sapi-maison'); ?></span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </div>
  </div>

  <!-- Card "Mon projet" englobante — visible avec projet. Contient :
       - badge "Mon projet · N luminaires" (N dynamique via JS)
       - phrase IA italique + signature Square Peg
       - lien "Préciser ou modifier mon projet" → modale V3 en édition (S3)
       - slot grille rempli par JS (clones des cards matching + card sur-mesure) -->
  <section class="conseiller-card conseiller-card--mon-projet mes-creations-selection__card" data-conseiller-card="mon-projet" hidden>
    <div class="conseiller-card__inner">
      <span class="conseiller-badge conseiller-badge--default" data-mon-projet-badge>
        <?php echo $conseiller_pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
        <span data-mon-projet-badge-text><?php esc_html_e('Mon projet', 'theme-sapi-maison'); ?></span>
      </span>
      <p class="conseiller-mon-projet__text" data-mon-projet-phrase>
        <span class="conseiller-mon-projet__text-content" data-mon-projet-phrase-content></span>
      </p>
      <!-- Chip-question : prochaine question non répondue avec ses pills
           cliquables (héritage F2a-sexies). Visible quand le projet est
           incomplet, le clic sur une pill enregistre la réponse + ouvre
           la modale sur la question suivante. -->
      <div class="conseiller-mon-projet__inline-question" data-inline-question hidden></div>
      <a class="conseiller-link mes-creations-selection__edit" href="#" data-action="open-modal" data-modal-state="s3" data-mon-projet-edit>
        <span><?php esc_html_e('Préciser ou modifier mon projet', 'theme-sapi-maison'); ?></span>
        <?php echo $conseiller_pencil_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
      </a>
      <!-- Slot grille : rempli par sapi-cards-conseiller.js avec les clones
           des cards .product-card-cinetique qui matchent sapiProject + la
           card sur-mesure (clonée depuis le <template> ci-dessous) en
           dernière cellule. -->
      <div class="mes-creations-selection__grid" data-mes-creations-selection-grid aria-live="polite"></div>

      <!-- Navigation slider : flèches + dots. Peuplé par JS selon le nombre
           de pages (= total cards / cards visibles par viewport). Masqué
           si tout tient sur une page. -->
      <div class="mes-creations-selection__nav" data-mes-creations-selection-nav hidden></div>

      <!-- Template card sur-mesure (Chantier 2) — markup mockup-15 ligne 419.
           Cloné par populateSelectionGrid() comme dernière cellule du slot.
           Pas rendu dans le DOM tant que le JS ne le clone pas. -->
      <template data-mes-creations-surmesure-template>
        <a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>" class="mes-creations-surmesure-card" data-mes-creations-surmesure-cta>
          <div class="mes-creations-surmesure-card__photo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
              <path d="M21 3v5h-5"/>
              <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
              <path d="M8 16H3v5"/>
            </svg>
            <span class="mes-creations-surmesure-card__badge"><?php esc_html_e('Sur-mesure', 'theme-sapi-maison'); ?></span>
          </div>
          <div class="mes-creations-surmesure-card__body">
            <div class="mes-creations-surmesure-card__title"><?php esc_html_e('Et si on créait le tien ?', 'theme-sapi-maison'); ?></div>
            <div class="mes-creations-surmesure-card__sub"><?php esc_html_e('Décris ton projet à Robin', 'theme-sapi-maison'); ?></div>
          </div>
        </a>
      </template>
    </div>
  </section>
</section>

<!-- Séparateur visuel entre "Ma sélection" et "Toutes mes créations" -->
<div class="mes-creations-section-divider" aria-hidden="true">
  <span class="mes-creations-section-divider__dot"></span>
  <span class="mes-creations-section-divider__dot"></span>
  <span class="mes-creations-section-divider__dot"></span>
</div>

<!-- Section "Toutes mes créations" — catalogue complet (1 seule grille DOM,
     source of truth pour les matches qui sont clonés dans la card "Ma sélection"
     via JS). Pills catégorie : Chantier 4 (placeholder visuel pour l'instant). -->
<section class="mes-creations-catalogue" id="mes-creations-catalogue">
  <header class="mes-creations-catalogue__header">
    <h2 class="mes-creations-catalogue__title"><?php esc_html_e('Toutes mes créations', 'theme-sapi-maison'); ?></h2>
    <p class="mes-creations-catalogue__sub"><?php esc_html_e('Le catalogue complet, classé par type de luminaire', 'theme-sapi-maison'); ?></p>
  </header>

  <!-- Pills catégorie (Chantier 3) — filtrage AJAX-less (toutes les cards
       sont déjà dans le DOM, on toggle .is-cat-filtered via JS). URL mise
       à jour via history.pushState. Au reload avec ?product_cat=<slug>,
       la classe is-active est appliquée côté PHP. -->
  <nav class="mes-creations-pills" data-mes-creations-pills aria-label="<?php esc_attr_e('Filtrer par catégorie', 'theme-sapi-maison'); ?>">
    <button type="button" class="mes-creations-pill<?php echo empty($mes_creations_active_cat) ? ' is-active' : ''; ?>" data-cat="all">
      <?php esc_html_e('Tous', 'theme-sapi-maison'); ?>
      <span class="mes-creations-pill__count"><?php echo esc_html($mes_creations_total); ?></span>
    </button>
    <?php foreach ($mes_creations_cat_order as $slug) :
      if (!isset($mes_creations_cats_by_slug[$slug])) continue;
      $cat = $mes_creations_cats_by_slug[$slug];
      $is_active = ($mes_creations_active_cat === $slug);
    ?>
      <button type="button" class="mes-creations-pill<?php echo $is_active ? ' is-active' : ''; ?>" data-cat="<?php echo esc_attr($cat->slug); ?>">
        <?php echo esc_html($cat->name); ?>
        <span class="mes-creations-pill__count"><?php echo esc_html($cat->count); ?></span>
      </button>
    <?php endforeach; ?>
  </nav>

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

        <?php
        // F2a Phase 4 — Card Sur-mesure insérée une fois, après le 7e produit.
        // Le wrap span sur toute la largeur de la grille (grid-column: 1 / -1).
        // JS bascule entre l'état empty/project/success selon sapiProject.
        if ($sapi_product_counter === 7) :
          $surmesure_badge_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/></svg>';
        ?>
        <div class="conseiller-surmesure-wrap" data-surmesure-wrap hidden>

          <!-- État A — Sans projet (form complet) -->
          <div class="conseiller-card conseiller-surmesure-card" data-surmesure-state="empty" hidden>
            <div class="conseiller-card__inner">
              <span class="conseiller-badge conseiller-badge--surmesure">
                <?php echo $surmesure_badge_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                <?php esc_html_e('Sur-mesure', 'theme-sapi-maison'); ?>
              </span>
              <h2 class="conseiller-h2"><?php esc_html_e('Et si on créait ton luminaire sur-mesure ?', 'theme-sapi-maison'); ?></h2>
              <p class="conseiller-subtitle"><?php esc_html_e('Laisse ton email et décris ton projet en quelques mots. Robin te revient avec une proposition personnalisée.', 'theme-sapi-maison'); ?></p>

              <form class="conseiller-surmesure-form" data-surmesure-form data-surmesure-mode="full">
                <input type="email" class="conseiller-surmesure-form__input" name="email" required
                       placeholder="<?php esc_attr_e('Ton adresse email', 'theme-sapi-maison'); ?>"
                       aria-label="<?php esc_attr_e('Email', 'theme-sapi-maison'); ?>">
                <textarea class="conseiller-surmesure-form__textarea" name="description" required rows="3"
                          placeholder="<?php esc_attr_e('Décris ton projet (taille, pièce, contraintes, inspirations…)', 'theme-sapi-maison'); ?>"
                          aria-label="<?php esc_attr_e('Description du projet', 'theme-sapi-maison'); ?>"></textarea>
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="conseiller-surmesure-form__honeypot" aria-hidden="true">
                <button type="submit" class="conseiller-cta">
                  <span><?php esc_html_e('Recevoir une proposition', 'theme-sapi-maison'); ?></span>
                  <?php echo $conseiller_arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </button>
              </form>
              <p class="conseiller-surmesure-reassurance"><?php esc_html_e('Réponse de Robin sous 48h · Aucun engagement', 'theme-sapi-maison'); ?></p>
            </div>
          </div>

          <!-- État B — Avec projet (compact, chips discrets + précisions optionnelles) -->
          <div class="conseiller-card conseiller-surmesure-card conseiller-surmesure-card--compact" data-surmesure-state="project" hidden>
            <div class="conseiller-card__inner">
              <span class="conseiller-badge conseiller-badge--surmesure">
                <?php echo $surmesure_badge_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                <?php esc_html_e('Sur-mesure', 'theme-sapi-maison'); ?>
              </span>
              <h2 class="conseiller-h2"><?php esc_html_e('Un sur-mesure pour ce projet ?', 'theme-sapi-maison'); ?></h2>
              <div class="conseiller-chips conseiller-chips--compact" data-surmesure-chips></div>

              <form class="conseiller-surmesure-form" data-surmesure-form data-surmesure-mode="project">
                <input type="email" class="conseiller-surmesure-form__input" name="email" required
                       placeholder="<?php esc_attr_e('Ton adresse email', 'theme-sapi-maison'); ?>"
                       aria-label="<?php esc_attr_e('Email', 'theme-sapi-maison'); ?>">
                <input type="text" class="conseiller-surmesure-form__input conseiller-surmesure-form__input--small" name="description"
                       placeholder="<?php esc_attr_e('Précisions ou inspirations (optionnel)', 'theme-sapi-maison'); ?>"
                       aria-label="<?php esc_attr_e('Précisions', 'theme-sapi-maison'); ?>">
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="conseiller-surmesure-form__honeypot" aria-hidden="true">
                <button type="submit" class="conseiller-cta">
                  <span><?php esc_html_e('Recevoir une proposition', 'theme-sapi-maison'); ?></span>
                  <?php echo $conseiller_arrow_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                </button>
              </form>
              <p class="conseiller-surmesure-reassurance"><?php esc_html_e('Réponse de Robin sous 48h · Aucun engagement', 'theme-sapi-maison'); ?></p>
            </div>
          </div>

          <!-- Round 3 — Lot C3 : état "contact" routé par l'IA (action=contact).
               Affiche un wording adapté au contact_kind (pro / sur-mesure / simple)
               + CTA(s) vers le formulaire /sur-mesure/ ou email — pré-remplis via
               sapiProject.contact_subject + contact_message (Lot C4). -->
          <div class="conseiller-card conseiller-surmesure-card conseiller-surmesure-card--contact" data-surmesure-state="contact" hidden>
            <div class="conseiller-card__inner">
              <span class="conseiller-badge conseiller-badge--surmesure">
                <?php echo $surmesure_badge_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                <?php esc_html_e('Sur-mesure', 'theme-sapi-maison'); ?>
              </span>
              <h2 class="conseiller-h2" data-surmesure-contact-title><?php esc_html_e('On en parle ensemble ?', 'theme-sapi-maison'); ?></h2>
              <p class="conseiller-subtitle" data-surmesure-contact-subtitle><?php esc_html_e('Décris-moi ton projet, je te recontacte directement.', 'theme-sapi-maison'); ?></p>
              <div class="conseiller-surmesure-contact-ctas" data-surmesure-contact-ctas></div>
              <p class="conseiller-surmesure-reassurance"><?php esc_html_e('Réponse de Robin sous 48h · Aucun engagement', 'theme-sapi-maison'); ?></p>
            </div>
          </div>

          <!-- État succès (commun aux deux modes) -->
          <div class="conseiller-card conseiller-surmesure-card conseiller-surmesure-card--success" data-surmesure-state="success" hidden>
            <div class="conseiller-card__inner">
              <span class="conseiller-badge conseiller-badge--surmesure">
                <?php echo $surmesure_badge_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                <?php esc_html_e('Sur-mesure', 'theme-sapi-maison'); ?>
              </span>
              <h2 class="conseiller-h2"><?php esc_html_e('Reçu — Robin t\'écrit sous 48h', 'theme-sapi-maison'); ?></h2>
              <p class="conseiller-subtitle"><?php esc_html_e('Merci pour ta demande. Tu vas recevoir un email de confirmation et Robin te répondra personnellement.', 'theme-sapi-maison'); ?></p>
            </div>
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

    <!-- Empty-state "aucun produit" pour le filtrage JS côté client.
         Cas extrême après l'élargissement progressif : aucun produit catalogue
         ne match même avec toutes les contraintes relâchées sauf sortie.
         CTA sur-mesure pour donner une issue concrète au visiteur. -->
    <div class="woocommerce-no-products-found shop-empty-state" style="display: none;">
      <p class="shop-empty-state__text">
        <?php esc_html_e('Aucun modèle de notre catalogue ne correspond à ce projet.', 'theme-sapi-maison'); ?>
      </p>
      <p class="shop-empty-state__subtext">
        <?php esc_html_e('Robin peut imaginer un luminaire sur-mesure pour ton projet.', 'theme-sapi-maison'); ?>
      </p>
      <a class="shop-empty-state__cta" href="<?php echo esc_url(home_url('/sur-mesure/')); ?>">
        <?php esc_html_e('Découvrir le sur-mesure', 'theme-sapi-maison'); ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>

  <?php else : ?>

    <div class="woocommerce-no-products-found shop-empty-state">
      <p class="shop-empty-state__text">
        <?php esc_html_e('Aucun modèle de notre catalogue ne correspond à ce projet.', 'theme-sapi-maison'); ?>
      </p>
      <p class="shop-empty-state__subtext">
        <?php esc_html_e('Robin peut imaginer un luminaire sur-mesure pour ton projet.', 'theme-sapi-maison'); ?>
      </p>
      <a class="shop-empty-state__cta" href="<?php echo esc_url(home_url('/sur-mesure/')); ?>">
        <?php esc_html_e('Découvrir le sur-mesure', 'theme-sapi-maison'); ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>

  <?php endif; ?>
  <?php wp_reset_postdata(); ?>
</section>

</section><!-- /.mes-creations-catalogue (wrap "Toutes mes créations") -->

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

<?php
// F2b : la modale Conseiller V3 a été extraite vers sapi_render_conseiller_modal()
// (functions.php) et hookée sur wp_footer pour être partagée avec la fiche produit.

get_footer();

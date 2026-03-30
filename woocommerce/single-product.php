<?php
/**
 * Single Product Template
 *
 * SAPI CINÉTIQUE - Premium product page with hero layout
 * Based on competitive analysis: bōlum, Loupiote, Apple
 *
 * @package Sapi-Maison
 */

defined('ABSPATH') || exit;

get_header();
?>

<?php while (have_posts()) : ?>
  <?php the_post(); ?>

  <?php
  global $product;
  $product = wc_get_product(get_the_ID());

  if (!$product) {
    continue;
  }

  // Product data
  $product_id = $product->get_id();
  $is_variable = $product->is_type('variable');

  // Category
  $product_cats = get_the_terms($product_id, 'product_cat');
  $cat_name = $product_cats && !is_wp_error($product_cats) ? $product_cats[0]->name : 'Création';
  $is_accessoire = false;
  $is_carte_cadeau = false;
  if ($product_cats && !is_wp_error($product_cats)) {
    foreach ($product_cats as $pcat) {
      if ($pcat->slug === 'accessoires') { $is_accessoire = true; }
      if ($pcat->slug === 'carte-cadeau') { $is_carte_cadeau = true; }
    }
  }

  // ACF fields
  $phrase = function_exists('get_field') ? get_field('phrase_daccroche') : '';
  $mini_description = function_exists('get_field') ? get_field('mini_description') : '';

  // Price display
  $price_html = $product->get_price_html();
  ?>

  <?php do_action('woocommerce_before_single_product'); ?>

  <div id="product-<?php the_ID(); ?>" <?php wc_product_class('product-page-cinetique product-page-v2', $product); ?>>

  <?php
  // sapi_get_acf_image_url() is defined in functions.php

  // Get first ambiance image for intro screen
  $ambiance_intro_photos = sapi_get_product_photos(get_the_ID(), 'ambiance', 1);
  $ambiance_intro = !empty($ambiance_intro_photos) ? $ambiance_intro_photos[0] : '';

  // Collect ALL photos for lightbox: product images + ACF ambiance/detail
  $acf_photos = [];

  // 1. Product main image + gallery
  $lb_main_id = wc_get_product(get_the_ID()) ? wc_get_product(get_the_ID())->get_image_id() : 0;
  $lb_gallery_ids = wc_get_product(get_the_ID()) ? wc_get_product(get_the_ID())->get_gallery_image_ids() : [];
  if ($lb_main_id) {
    $lb_url = wp_get_attachment_image_url($lb_main_id, 'full');
    if ($lb_url) $acf_photos[] = ['url' => $lb_url, 'label' => 'Produit'];
  }
  foreach ($lb_gallery_ids as $lb_gid) {
    $lb_url = wp_get_attachment_image_url($lb_gid, 'full');
    if ($lb_url) $acf_photos[] = ['url' => $lb_url, 'label' => 'Galerie'];
  }

  $first_acf_index = count($acf_photos); // index where ACF photos start

  // 2. Video oEmbed
  $video_oembed = '';
  $video_url_raw = '';
  if (function_exists('get_field')) {
    $video_raw = get_field('video_produit', false, false); // Raw URL
    $video_rendered = get_field('video_produit'); // oEmbed HTML or URL

    if ($video_raw) {
      $video_url_raw = $video_raw;
      // If ACF returns HTML (iframe), use it directly; otherwise convert URL to embed
      if ($video_rendered && $video_rendered !== $video_raw && strpos($video_rendered, '<') !== false) {
        $video_oembed = $video_rendered;
      } else {
        // Fallback: use wp_oembed_get to convert URL to iframe
        $video_oembed = wp_oembed_get($video_raw);
      }
    }
  }

  // 3. ACF photos from repeater (with fallback to old fields in helper)
  $type_labels = [
    'ambiance'    => 'Ambiance',
    'detail'      => 'Détail',
    'taille'      => 'Tailles',
    'client'      => 'Client',
    'fabrication' => 'Fabrication',
  ];

  if (function_exists('get_field')) {
    $galerie_repeater = get_field('galerie_produit');
    if (!empty($galerie_repeater) && is_array($galerie_repeater)) {
      foreach ($galerie_repeater as $row) {
        $url = sapi_get_acf_image_url(isset($row['image']) ? $row['image'] : null);
        if ($url) {
          $type = isset($row['type_photo']) ? $row['type_photo'] : 'ambiance';
          $acf_photos[] = ['url' => $url, 'label' => isset($type_labels[$type]) ? $type_labels[$type] : ucfirst($type)];
        }
      }
    } else {
      // Fallback: use helper which reads old fixed fields
      $all_product_photos = sapi_get_product_photos(get_the_ID());
      foreach ($all_product_photos as $url) {
        $acf_photos[] = ['url' => $url, 'label' => 'Photo'];
      }
    }
  }

  $acf_only_count = count($acf_photos) - $first_acf_index;

  if ($ambiance_intro) :
  ?>
  <!-- Product Intro Screen with Ambiance Image -->
  <div class="product-intro-screen" id="product-intro-screen" style="--intro-bg-image: url('<?php echo esc_url($ambiance_intro); ?>');">
    <div class="product-intro-content">
      <h1 class="product-intro-title"><?php the_title(); ?></h1>
      <span class="product-intro-skip">Scrollez ou cliquez pour découvrir</span>
    </div>
  </div>
  <?php endif; ?>

  <?php sapi_maison_breadcrumbs(); ?>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 01 — HERO PRODUIT (Premium Layout)
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-hero product-hero-v2">
    <div class="product-hero-container">

      <!-- COLONNE GAUCHE: Galerie (60%) -->
      <div class="product-gallery-v2">

        <!-- Mobile-only: Titre et phrase d'accroche au-dessus de la photo -->
        <div class="product-gallery-mobile-header">
          <h1 class="product-title-mobile"><?php the_title(); ?></h1>
          <?php if ($phrase || $mini_description) : ?>
            <p class="product-tagline-mobile">
              <?php echo esc_html($phrase ? $phrase : $mini_description); ?>
            </p>
          <?php elseif ($product->get_short_description()) : ?>
            <p class="product-tagline-mobile">
              <?php echo wp_strip_all_tags($product->get_short_description()); ?>
            </p>
          <?php endif; ?>
        </div>

        <?php
        // Main product image
        $main_image_id = $product->get_image_id();
        $gallery_ids = $product->get_gallery_image_ids();

        if ($main_image_id) {
          $main_image_url = wp_get_attachment_image_url($main_image_id, 'woocommerce_single');
          $main_image_full = wp_get_attachment_image_url($main_image_id, 'full');
          ?>
          <div class="gallery-main" style="cursor: pointer;">
              <img src="<?php echo esc_url($main_image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="gallery-main-image">
              <?php if ($video_oembed) : ?>
              <div class="gallery-main-video" style="display: none;">
                <?php echo $video_oembed; ?>
              </div>
              <?php endif; ?>
            <!-- Mobile navigation arrows (minimal style) -->
            <button type="button" class="gallery-nav gallery-nav-prev" aria-label="Image précédente">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>
            <button type="button" class="gallery-nav gallery-nav-next" aria-label="Image suivante">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
          <?php
        }

        // Thumbnails (horizontal)
        if (!empty($gallery_ids) || $main_image_id) {
          $all_images = $main_image_id ? array_merge([$main_image_id], $gallery_ids) : $gallery_ids;

          $has_video = !empty($video_oembed);
          if (count($all_images) + $acf_only_count + ($has_video ? 1 : 0) > 1) {
            $video_thumb = $has_video ? sapi_get_video_thumbnail($video_url_raw) : '';
            ?>
            <div class="gallery-thumbnails">
              <?php if ($has_video) : ?>
              <button class="gallery-thumb gallery-thumb-video" data-video="true" aria-label="Voir la vidéo">
                <?php if ($video_thumb) : ?>
                  <img src="<?php echo esc_url($video_thumb); ?>" alt="<?php echo esc_attr(get_the_title() . ' - Vidéo'); ?>">
                <?php else : ?>
                  <span class="gallery-thumb-video-placeholder"></span>
                <?php endif; ?>
                <span class="gallery-thumb-play">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><polygon points="5,3 19,12 5,21"></polygon></svg>
                </span>
              </button>
              <?php endif; ?>
              <?php foreach ($all_images as $index => $image_id) :
                $thumb_url = wp_get_attachment_image_url($image_id, 'woocommerce_gallery_thumbnail');
                // Use 'full' size for main display to ensure ACF images display properly
                $full_url = wp_get_attachment_image_url($image_id, 'full');
                // Fallback to woocommerce_single if full is not available
                if (!$full_url) {
                  $full_url = wp_get_attachment_image_url($image_id, 'woocommerce_single');
                }
                ?>
                <button class="gallery-thumb<?php echo $index === 0 ? ' active' : ''; ?>" data-image="<?php echo esc_url($full_url); ?>">
                  <?php
                    $cat_names = wp_list_pluck(wc_get_product_terms($product->get_id(), 'product_cat'), 'name');
                    $cat_label = !empty($cat_names) ? $cat_names[0] : 'luminaire';
                  ?>
                  <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr(get_the_title() . ' - ' . $cat_label . ' photo ' . ($index + 1)); ?>">
                </button>
              <?php endforeach; ?>
              <?php
              // ACF ambiance/detail photos as additional gallery thumbnails
              for ($i = $first_acf_index; $i < count($acf_photos); $i++) :
                $acf_photo = $acf_photos[$i];
              ?>
                <button class="gallery-thumb" data-image="<?php echo esc_url($acf_photo['url']); ?>">
                  <img src="<?php echo esc_url($acf_photo['url']); ?>" alt="<?php echo esc_attr(get_the_title() . ' - ' . $acf_photo['label']); ?>">
                </button>
              <?php endfor; ?>
            </div>
            <?php
          }
        }
        ?>
      </div>

      <!-- COLONNE DROITE: Informations (40%) -->
      <div class="product-info-v2" id="product-summary-main">

        <!-- Titre H1 (nom du modèle) -->
        <h1 class="product-title-v2"><?php the_title(); ?></h1>

        <!-- Mini description / Phrase d'accroche -->
        <?php if ($phrase || $mini_description) : ?>
          <p class="product-tagline">
            <?php echo esc_html($phrase ? $phrase : $mini_description); ?>
          </p>
        <?php elseif ($product->get_short_description()) : ?>
          <p class="product-tagline">
            <?php echo wp_strip_all_tags($product->get_short_description()); ?>
          </p>
        <?php endif; ?>

        <!-- Prix -->
        <div class="product-price-v2">
          <?php
          // Toujours afficher "À partir de" avec le prix minimum
          if ($is_variable) {
            $min_price = $product->get_variation_price('min');
            echo '<span class="price-from-label">À partir de </span>';
            echo '<span class="price-amount">' . wc_price($min_price) . '</span>';
          } else {
            echo '<span class="price-from-label">À partir de </span>';
            echo '<span class="price-amount">' . wc_price($product->get_price()) . '</span>';
          }
          ?>
        </div>

        <?php if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) : ?>
          <button type="button" class="robin-pill" id="robin-product-pill"
            data-robin-context="product_guide"
            data-robin-data='<?php echo esc_attr(wp_json_encode(['product_id' => $product_id, 'product_name' => get_the_title()])); ?>'>
            Comment choisir le bon mod&egrave;le ?
          </button>
        <?php endif; ?>

        <!-- Séparateur visuel -->
        <hr class="product-divider">

        <!-- Formulaire d'achat (variations + quantité + CTA) -->
        <div class="product-form-v2">
          <!-- Introduction aux variations -->
          <?php if ($product->is_type('variable')) : ?>
          <p class="variations-intro">Composez votre luminaire :</p>
          <?php endif; ?>

          <?php
          // Remove default actions to control order
          remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
          remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);
          remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
          remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20);

          // Only render add to cart form
          woocommerce_template_single_add_to_cart();
          ?>
        </div>

        <!-- Paiement rapide (Apple Pay, etc.) - discret -->
        <div class="product-quick-pay">
          <?php
          // Ce hook est utilisé par WooCommerce Stripe et autres passerelles
          // pour afficher les boutons Apple Pay / Google Pay
          do_action('woocommerce_after_add_to_cart_button');
          ?>
        </div>

        <!-- Réassurance inline (au-dessus du fold) -->
        <?php if (!$is_carte_cadeau) : ?>
        <div class="product-reassurance-v2">
          <?php if (!$is_accessoire) : ?>
          <div class="reassurance-item-v2">
            <svg width="18" height="18" viewBox="0 0 512 512" fill="none" stroke="var(--color-wood)" stroke-width="35">
              <path d="M283.4 19.83c-3.2 0-31.2 5.09-31.2 5.09-1.3 41.61-30.4 78.48-90.3 84.88l-12.8-23.07-25.1 2.48 11.3 60.09-113.79-4.9 12.2 41.5C156.3 225.4 150.7 338.4 124 439.4c47 53 141.8 47.8 186 43.1 3.1-62.2 52.4-64.5 135.9-32.2 11.3-17.6 18.8-36 44.6-50.7l-46.6-139.5-27.5 6.2c11-21.1 32.2-49.9 50.4-63.4l15.6-86.9c-88.6-6.3-146.4-46.36-199-96.17z"/>
            </svg>
            <span>Fabriqué à <strong>Lyon</strong></span>
          </div>
          <?php endif; ?>
          <div class="reassurance-item-v2 reassurance-delivery">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <span>Chez vous le <strong><?php echo sapi_get_estimated_delivery_date(); ?></strong></span>
          </div>
          <div class="reassurance-item-v2">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
            </svg>
            <span>Retours <strong>30 jours</strong></span>
          </div>
        </div>


        <!-- CTA Échanger avec Robin -->
        <div class="robin-contact-bandeau" id="ctaRobinContact">
          <div class="robin-contact-closed">
            <span class="robin-contact-question">Envie d'en discuter ?</span>
            <button type="button" class="robin-contact-toggle">Échanger avec Robin →</button>
          </div>
          <div class="robin-contact-open" hidden>
            <span class="robin-contact-label">Robin vous recontacte rapidement.</span>
            <form class="robin-contact-form" data-product="<?php echo esc_attr($product->get_name()); ?>">
              <?php wp_nonce_field('sapi-guide-results', 'robin_contact_nonce', false); ?>
              <input type="email" name="email" class="robin-contact-email" placeholder="votre@email.com" required>
              <textarea name="message" class="robin-contact-message" placeholder="Votre message (optionnel)" rows="2"></textarea>
              <button type="submit" class="robin-contact-submit">Envoyer</button>
            </form>
          </div>
          <div class="robin-contact-success" hidden>
            <span class="robin-contact-done">Message envoyé ! Robin revient vers vous très vite.</span>
          </div>
        </div>
        <?php endif; // fin !$is_carte_cadeau ?>

        <?php if (!$is_accessoire) : ?>
        <!-- Micro-copy artisan -->
        <p class="product-artisan-note">
          <em>Chaque pièce est découpée au laser puis assemblée à la main à l'Atelier Sâpi, à Lyon.</em>
        </p>
        <?php endif; ?>

      </div>
    </div>
  </section>

  <?php if (!$is_carte_cadeau) : // Masquer tout le contenu détaillé pour la carte cadeau ?>
  <!-- ═══════════════════════════════════════════════════════════════
       SECTION PHOTO CLIENT — BANDEAU
       ═══════════════════════════════════════════════════════════════ -->
  <?php
  if (function_exists('get_field')) {
    $bandeau = get_field('bandeau');
    if ($bandeau) {
      $bandeau_url = sapi_get_acf_image_url($bandeau);

      $section_num = 0; // Compteur dynamique pour numérotation des sections
      if ($bandeau_url) :
        // Random caption
        $captions = [
          'Photo envoyée par une cliente',
          'Photo envoyée récemment par un client'
        ];
        $random_caption = $captions[array_rand($captions)];
  ?>
  <section class="product-client-photo">
    <div class="client-photo-header">
      <span class="section-number"><?php echo esc_html(sprintf('%02d', ++$section_num)); ?></span>
      <h2><?php echo esc_html($random_caption); ?></h2>
    </div>
    <div class="client-photo-wrapper">
      <img src="<?php echo esc_url($bandeau_url); ?>" alt="Photo client - <?php echo esc_attr(get_the_title()); ?>" class="client-photo-image">
    </div>
  </section>
  <?php
      endif;
    }
  }
  ?>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 02 — POURQUOI CETTE PIÈCE
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-why product-why-cinetique">
    <div class="product-why-grid">
      <div class="product-why-left">
        <div class="product-why-header">
          <span class="section-number"><?php echo esc_html(sprintf('%02d', ++$section_num)); ?></span>
          <?php
          $name_parts = explode(' ', $product->get_name(), 2);
          $model_name = $name_parts[0];
          ?>
          <h2>L'histoire de <?php echo esc_html($model_name); ?></h2>
        </div>
        <div class="product-why-content">
        <?php
        $why_content = '';
        if (function_exists('get_field')) {
          $pourquoi = get_field('pourquoi_cette_piece');
          $descriptif = get_field('descriptif');
          if ($pourquoi) {
            $why_content = $pourquoi;
          } elseif ($descriptif) {
            $why_content = $descriptif;
          }
        }
        if ($why_content) {
          echo wp_kses_post($why_content);
        } else {
          the_content();
        }
        ?>
        </div><!-- .product-why-content -->
      </div><!-- .product-why-left -->
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 03 — FICHE TECHNIQUE (Dynamique via ACF)
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-specs product-specs-v2">
    <div class="product-specs-header">
      <span class="section-number"><?php echo esc_html(sprintf('%02d', ++$section_num)); ?></span>
      <h2>Fiche technique</h2>
    </div>

    <?php
    // SVG icons (définis avant le try pour être disponibles dans le catch aussi)
    $icon_dimensions   = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>';
    $icon_eclairage    = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>';
    $icon_materiaux    = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>';
    $icon_installation = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>';

    // Valeurs par défaut (fallback statique si le try échoue)
    $spec_sections = [
      ['title' => 'Dimensions',   'icon' => $icon_dimensions,   'items' => [
        ['label' => 'Dimensions', 'value' => 'Voir variations'],
      ]],
      ['title' => 'Éclairage',    'icon' => $icon_eclairage,    'items' => [
        ['label' => 'Culot',              'value' => 'E27'],
        ['label' => 'Ampoule recommandée','value' => 'LED filament 4-6W (2700K)'],
        ['label' => 'Ampoule incluse',    'value' => 'Non (disponible en option)'],
      ]],
      ['title' => 'Matériaux',    'icon' => $icon_materiaux,    'items' => [
        ['label' => 'Structure', 'value' => '100% bois'],
        ['label' => 'Finition',  'value' => 'Contreplaqué poncé'],
      ]],
      ['title' => 'Installation', 'icon' => $icon_installation, 'items' => [
        ['label' => 'Assemblage',   'value' => 'Notice et tuto vidéo'],
        ['label' => 'Difficulté',   'value' => 'Facile (15-30 min)'],
        ['label' => 'Entretien',    'value' => 'Chiffon sec ou plumeau'],
      ]],
    ];

    try {
      // ── Déterminer la catégorie produit ──
      $product_cat_slugs = [];
      if ($product_cats && !is_wp_error($product_cats)) {
        foreach ($product_cats as $cat) {
          $product_cat_slugs[] = $cat->slug;
        }
      }
      $is_suspension  = in_array('suspensions', $product_cat_slugs);
      $is_lampadaire  = in_array('lampadaires', $product_cat_slugs);
      $is_lampe_poser = in_array('lampesaposer', $product_cat_slugs) || in_array('lampes-a-poser', $product_cat_slugs);
      $is_applique    = in_array('appliques', $product_cat_slugs) || in_array('appliques-murales', $product_cat_slugs);

      // IMPORTANT : ne pas nommer cette variable $acf — c'est le nom du singleton global ACF !
      $has_acf = function_exists('get_field');

      // ── Dimensions (champs ACF + WooCommerce en fallback) ──
      $dimensions_str = '';
      $poids = '';
      if ($has_acf) {
        $dimensions = get_field('dimensions');
        $hauteur    = get_field('hauteur');
        $largeur    = get_field('largeur');
        $profondeur = get_field('profondeur');
        $poids      = (string) get_field('poids');

        if ($dimensions) {
          $dimensions_str = (string) $dimensions;
        } elseif ($hauteur || $largeur || $profondeur) {
          $dim_parts = [];
          if ($largeur)    $dim_parts[] = 'L ' . $largeur;
          if ($profondeur) $dim_parts[] = 'P ' . $profondeur;
          if ($hauteur)    $dim_parts[] = 'H ' . $hauteur;
          $dimensions_str = implode(' × ', $dim_parts);
        }
      }
      if (!$dimensions_str && $product && function_exists('wc_format_dimensions')) {
        $wc_dims = wc_format_dimensions($product->get_dimensions(false));
        if ($wc_dims && $wc_dims !== 'N/A') {
          $dimensions_str = $wc_dims;
        }
      }
      if (!$poids) {
        $weight = $product ? $product->get_weight() : '';
        $poids  = $weight ? $weight . ' kg' : '';
      }

      // ── Champs ACF communs (avec fallbacks) ──
      $culot              = ($has_acf ? (string) get_field('culot') : '')                    ?: 'E27';
      $ampoule_reco       = ($has_acf ? (string) get_field('ampoule_recommandee') : '')      ?: 'LED filament 4-6W (2700K)';
      $ampoule_incluse    = ($has_acf ? (string) get_field('ampoule_incluse') : '')          ?: 'Non (disponible en option)';
      $materiau_structure = ($has_acf ? (string) get_field('materiau_structure') : '')       ?: '100% bois';
      $bois               = ($has_acf ? (string) get_field('bois') : '')                     ?: 'Peuplier ou Okoumé - Au choix';
      $finition           = ($has_acf ? (string) get_field('finition') : '')                 ?: 'Contreplaqué poncé';
      $assemblage         = ($has_acf ? (string) get_field('assemblage') : '')               ?: 'Notice et tuto vidéo';
      $difficulte         = ($has_acf ? (string) get_field('installation_difficulte') : '')  ?: 'Facile (15-30 min)';
      $outils_requis      = ($has_acf ? (string) get_field('assemblage_outils') : '')        ?: 'Aucun';
      $entretien          = ($has_acf ? (string) get_field('entretien') : '')                ?: 'Chiffon sec ou plumeau';

      // ── Champs ACF par catégorie (pas de fallback) ──
      $longueur_cable       = $has_acf ? (string) get_field('longueur_cable')       : '';
      $materiau_cable       = $has_acf ? (string) get_field('materiau_cable')       : '';
      $compatible_dcl       = $has_acf ? (string) get_field('compatible_dcl')       : '';
      $compatible_variateur = $has_acf ? (string) get_field('compatible_variateur') : '';
      $rosace               = $has_acf ? (string) get_field('rosace')               : '';
      $hauteur_totale       = $has_acf ? (string) get_field('hauteur_totale')       : '';
      $hauteur_ampoule_ft   = $has_acf ? (string) get_field('hauteur_ampoule')      : '';
      $interrupteur         = $has_acf ? (string) get_field('interrupteur')         : '';
      $fixation_murale      = $has_acf ? (string) get_field('fixation_murale')      : '';
      $type_connexion       = $has_acf ? (string) get_field('type_connexion')       : '';

      // ── Construire les 4 sections de specs ──

      // Section 1 : Dimensions & Produit
      $specs_dimensions   = [];
      $specs_dimensions[] = ['label' => 'Dimensions', 'value' => $dimensions_str ?: 'Voir variations'];
      if ($poids)                             $specs_dimensions[] = ['label' => 'Poids',              'value' => $poids];
      if ($is_lampadaire && $hauteur_totale)  $specs_dimensions[] = ['label' => 'Hauteur totale',     'value' => $hauteur_totale];
      if ($is_lampadaire && $hauteur_ampoule_ft) $specs_dimensions[] = ['label' => 'Hauteur ampoule', 'value' => $hauteur_ampoule_ft];
      if ($longueur_cable)                    $specs_dimensions[] = ['label' => 'Longueur câble',     'value' => $longueur_cable];
      if ($is_suspension && $rosace)          $specs_dimensions[] = ['label' => 'Rosace',             'value' => $rosace];
      if ($is_applique && $fixation_murale)   $specs_dimensions[] = ['label' => 'Fixation murale',    'value' => $fixation_murale];
      if ($is_applique && $type_connexion)    $specs_dimensions[] = ['label' => 'Connexion électrique','value' => $type_connexion];

      // Section 2 : Éclairage
      $specs_eclairage   = [];
      $specs_eclairage[] = ['label' => 'Culot',               'value' => $culot];
      $specs_eclairage[] = ['label' => 'Ampoule recommandée', 'value' => $ampoule_reco];
      $specs_eclairage[] = ['label' => 'Ampoule incluse',     'value' => $ampoule_incluse];
      if ($compatible_variateur) $specs_eclairage[] = ['label' => 'Compatible variateur', 'value' => $compatible_variateur];
      if ($compatible_dcl)       $specs_eclairage[] = ['label' => 'Compatible DCL',       'value' => $compatible_dcl];

      // Ampoule associée via cross-sells (hors accessoires)
      $ampoule_product = null;
      if (!$is_accessoire) {
        $cross_sell_ids = $product->get_cross_sell_ids();
        foreach ($cross_sell_ids as $cs_id) {
          $cs_cats = get_the_terms($cs_id, 'product_cat');
          if ($cs_cats && !is_wp_error($cs_cats)) {
            foreach ($cs_cats as $cs_cat) {
              if ($cs_cat->slug === 'accessoires') {
                $ampoule_product = wc_get_product($cs_id);
                break 2;
              }
            }
          }
        }
        if ($ampoule_product && $ampoule_product->is_purchasable() && $ampoule_product->is_in_stock()) {
          $specs_eclairage[] = [
            'type'       => 'ampoule_button',
            'product_id' => $ampoule_product->get_id(),
            'name'       => $ampoule_product->get_name(),
            'price'      => $ampoule_product->get_price(),
          ];
        }
      }

      // Section 3 : Matériaux
      $specs_materiaux   = [];
      $specs_materiaux[] = ['label' => 'Structure', 'value' => $materiau_structure];
      $specs_materiaux[] = ['label' => 'Bois',      'value' => $bois];
      $specs_materiaux[] = ['label' => 'Finition',  'value' => $finition];
      if ($materiau_cable) $specs_materiaux[] = ['label' => 'Câble', 'value' => $materiau_cable];

      // Section 4 : Installation
      $specs_installation   = [];
      $specs_installation[] = ['label' => 'Assemblage',  'value' => $assemblage];
      $specs_installation[] = ['label' => 'Difficulté',  'value' => $difficulte];
      $specs_installation[] = ['label' => 'Outils requis','value' => $outils_requis];
      $specs_installation[] = ['label' => 'Entretien',   'value' => $entretien];
      if ($interrupteur) $specs_installation[] = ['label' => 'Interrupteur', 'value' => $interrupteur];

      $spec_sections = [
        ['title' => 'Dimensions',   'icon' => $icon_dimensions,   'items' => $specs_dimensions],
        ['title' => 'Éclairage',    'icon' => $icon_eclairage,    'items' => $specs_eclairage],
        ['title' => 'Matériaux',    'icon' => $icon_materiaux,    'items' => $specs_materiaux],
        ['title' => 'Installation', 'icon' => $icon_installation, 'items' => $specs_installation],
      ];

    } catch (\Throwable $e) {
      // Log pour débogage serveur
      error_log('[Sapi] Fiche technique error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
      // Commentaire HTML visible dans le source pour identifier l'erreur exacte
      echo "\n<!-- [SAPI-DEBUG] " . esc_html($e->getMessage()) . ' (' . esc_html(basename($e->getFile())) . ':' . (int)$e->getLine() . ") -->\n";
      // $spec_sections garde les valeurs par défaut définies avant le try
    }
    ?>

    <!-- Accordion Mobile -->
    <div class="product-specs-accordion">
      <?php foreach ($spec_sections as $section) : ?>
        <?php if (!empty($section['items'])) : ?>
        <details class="specs-accordion-item">
          <summary class="specs-accordion-title">
            <?php echo $section['icon']; ?>
            <span><?php echo esc_html($section['title']); ?></span>
            <svg class="accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </summary>
          <div class="specs-accordion-content">
            <?php foreach ($section['items'] as $item) : ?>
              <?php if (!empty($item['type']) && $item['type'] === 'ampoule_button') : ?>
              <div class="spec-item spec-item-ampoule">
                <button type="button" class="add-ampoule-btn" data-product-id="<?php echo esc_attr($item['product_id']); ?>">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                  Ajouter une ampoule — <?php echo wp_kses_post(wc_price($item['price'])); ?>
                </button>
              </div>
              <?php else : ?>
              <div class="spec-item">
                <span class="spec-label"><?php echo esc_html($item['label']); ?></span>
                <span class="spec-value"><?php echo esc_html($item['value']); ?></span>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </details>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- Grid Desktop -->
    <div class="product-specs-grid">
      <?php foreach ($spec_sections as $section) : ?>
        <?php if (!empty($section['items'])) : ?>
        <div class="specs-column">
          <h3 class="specs-column-title">
            <?php echo $section['icon']; ?>
            <?php echo esc_html($section['title']); ?>
          </h3>
          <div class="specs-list">
            <?php foreach ($section['items'] as $item) : ?>
              <?php if (!empty($item['type']) && $item['type'] === 'ampoule_button') : ?>
              <div class="spec-item spec-item-ampoule">
                <button type="button" class="add-ampoule-btn" data-product-id="<?php echo esc_attr($item['product_id']); ?>">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                  Ajouter une ampoule — <?php echo wp_kses_post(wc_price($item['price'])); ?>
                </button>
              </div>
              <?php else : ?>
              <div class="spec-item">
                <span class="spec-label"><?php echo esc_html($item['label']); ?></span>
                <span class="spec-value"><?php echo esc_html($item['value']); ?></span>
              </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <!-- Badges certifications -->
    <div class="specs-badges">
      <div class="spec-badge">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        <span>Garantie 2 ans</span>
      </div>
      <div class="spec-badge">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>
        </svg>
        <span>Conforme CE</span>
      </div>
      <div class="spec-badge">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
        </svg>
        <span>Fabriqué à Lyon</span>
      </div>
      <div class="spec-badge">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 2a10 10 0 1 0 10 10H12V2z"/><path d="M12 2a7 7 0 0 1 7 7h-7V2z"/>
        </svg>
        <span>Bois PEFC</span>
      </div>
    </div>
  </section>


  <?php if (!$is_accessoire) : ?>
  <!-- ═══════════════════════════════════════════════════════════════
       SECTION — TÉMOIGNAGES (Preuve Sociale)
       ═══════════════════════════════════════════════════════════════ -->
  <?php
  $google_reviews = sapi_get_google_reviews();
  ?>
  <section class="product-testimonials">
    <div class="testimonials-header">
      <span class="section-number"><?php echo esc_html(sprintf('%02d', ++$section_num)); ?></span>
      <h2>Ce qu'en pensent les clients</h2>
      <?php if ($google_reviews) : ?>
      <div class="google-reviews-badge">
        <svg class="google-logo" width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
        <div class="google-reviews-summary">
          <div class="google-stars">
            <?php
            $rating = $google_reviews['rating'];
            for ($i = 1; $i <= 5; $i++) :
              if ($i <= floor($rating)) : ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="#FBBC05"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <?php else : ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="#ddd"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <?php endif;
            endfor; ?>
          </div>
          <span class="google-rating-text"><?php echo esc_html($rating); ?>/5 · <?php echo esc_html($google_reviews['total']); ?> avis</span>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($google_reviews && !empty($google_reviews['reviews'])) : ?>
    <div class="testimonials-grid">
      <?php
      $reviews_pool = $google_reviews['reviews'];
      shuffle($reviews_pool);
      $reviews_display = array_slice($reviews_pool, 0, 3);
      ?>
      <?php foreach ($reviews_display as $review) : ?>
      <div class="testimonial-card">
        <div class="testimonial-card-header">
          <?php if (!empty($review['photo'])) : ?>
          <img class="testimonial-avatar" src="<?php echo esc_url($review['photo']); ?>" alt="" width="36" height="36" loading="lazy">
          <?php endif; ?>
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
        <span class="testimonial-full-text" hidden><?php echo esc_attr($text); ?></span>
        <span class="testimonial-full-author" hidden><?php echo esc_attr($review['author']); ?></span>
        <span class="testimonial-full-photo" hidden><?php echo esc_attr($review['photo'] ?? ''); ?></span>
        <span class="testimonial-full-time" hidden><?php echo esc_attr($review['time'] ?? ''); ?></span>
        <span class="testimonial-full-rating" hidden><?php echo esc_attr($review['rating']); ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="testimonials-cta">
      <a href="https://g.page/r/CQ0YW1uBzOimEAE/review" target="_blank" rel="noopener noreferrer" class="testimonials-cta-review">Laisser un avis sur Google</a>
      <span class="testimonials-cta-sep">·</span>
      <a href="https://www.google.com/maps/place/?q=place_id:ChIJYyWUfZOV9EcRDRhbW4HM6KY" target="_blank" rel="noopener noreferrer">Voir les <?php echo esc_html($google_reviews['total']); ?> avis</a>
    </div>
    <!-- Modale avis Google -->
    <div class="review-modal-overlay" id="reviewModal" hidden>
      <div class="review-modal">
        <button type="button" class="review-modal-close" aria-label="Fermer">&times;</button>
        <div class="review-modal-header">
          <img class="review-modal-avatar" src="" alt="" width="48" height="48">
          <div class="review-modal-author-info">
            <span class="review-modal-name"></span>
            <span class="review-modal-time"></span>
          </div>
        </div>
        <div class="review-modal-rating"></div>
        <p class="review-modal-text"></p>
      </div>
    </div>
    <script>
    (function() {
      var modal = document.getElementById('reviewModal');
      var overlay = modal;
      var closeBtn = modal.querySelector('.review-modal-close');

      document.querySelectorAll('.testimonial-card').forEach(function(card) {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function() {
          var fullText = card.querySelector('.testimonial-full-text').textContent;
          var author = card.querySelector('.testimonial-full-author').textContent;
          var photo = card.querySelector('.testimonial-full-photo').textContent;
          var time = card.querySelector('.testimonial-full-time').textContent;
          var rating = parseInt(card.querySelector('.testimonial-full-rating').textContent);

          modal.querySelector('.review-modal-name').textContent = author;
          modal.querySelector('.review-modal-time').textContent = time;
          modal.querySelector('.review-modal-text').textContent = fullText;

          var avatar = modal.querySelector('.review-modal-avatar');
          if (photo) { avatar.src = photo; avatar.style.display = ''; }
          else { avatar.style.display = 'none'; }

          var stars = '';
          for (var i = 1; i <= 5; i++) {
            stars += '<svg width="16" height="16" viewBox="0 0 24 24" fill="' + (i <= rating ? '#FBBC05' : '#ddd') + '"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
          }
          modal.querySelector('.review-modal-rating').innerHTML = stars;

          modal.hidden = false;
          document.body.style.overflow = 'hidden';
        });
      });

      closeBtn.addEventListener('click', closeModal);
      overlay.addEventListener('click', function(e) {
        if (e.target === overlay) closeModal();
      });
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
      });

      function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
      }
    })();
    </script>
    <?php else : ?>
    <div class="testimonials-cta">
      <p>Vous avez ce produit ? <a href="https://g.page/r/CQ0YW1uBzOimEAE/review" target="_blank" rel="noopener noreferrer">Partagez votre avis</a></p>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; // fin exclusion avis accessoires ?>

  <?php if (!$is_accessoire) : ?>
  <!-- ═══════════════════════════════════════════════════════════════
       SECTION — L'ATELIER (Fabriqué avec passion)
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-atelier product-atelier-cinetique">
    <div class="product-atelier-grid">
      <div class="product-atelier-image">
          <img src="https://atelier-sapi.fr/wp-content/uploads/2026/03/Robin-au-poncage.jpg" alt="Robin au ponçage dans l'atelier Sapi" loading="lazy">
      </div>
      <div class="product-atelier-content">
        <span class="section-number"><?php echo esc_html(sprintf('%02d', ++$section_num)); ?></span>
        <h2>Fabriqué avec passion</h2>
        <p class="atelier-intro">Chaque luminaire est conçu et fabriqué à la main par Robin dans son atelier lyonnais.</p>
        <div class="atelier-quote-body">
          <div class="robin-conseil__quote">&ldquo;</div>
          <p class="atelier-quote-text">Je crée chaque pièce comme si elle allait éclairer ma propre maison. La précision de la découpe laser combinée au savoir-faire artisanal, c'est ce qui rend chaque luminaire unique.</p>
        </div>
        <p class="atelier-signature">Robin, créateur à l'Atelier Sâpi</p>
        <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="robin-conseil__product-link">Découvrir l'histoire de Robin &rarr;</a>
        <p class="atelier-rdv">Atelier ouvert sur rendez-vous — <a href="<?php echo esc_url(home_url('/contact/')); ?>">prendre rendez-vous</a></p>
      </div>
    </div>
  </section>
  <?php endif; // fin exclusion atelier accessoires ?>
  <?php endif; // fin !$is_carte_cadeau ?>

  <!-- ═══════════════════════════════════════════════════════════════
       PRODUITS SIMILAIRES / CARTE CADEAU
       ═══════════════════════════════════════════════════════════════ -->
  <?php if ($is_carte_cadeau) : ?>
  <!-- Grille produits pour carte cadeau -->
  <?php
  $gift_query = new WP_Query([
    'post_type'      => 'product',
    'posts_per_page' => 4,
    'post_status'    => 'publish',
    'tax_query'      => [[
      'taxonomy' => 'product_cat',
      'field'    => 'slug',
      'terms'    => ['carte-cadeau', 'accessoires'],
      'operator' => 'NOT IN',
    ]],
    'orderby' => 'rand',
  ]);
  if ($gift_query->have_posts()) :
  ?>
  <section class="product-related product-related-giftcard">
    <div class="product-related-header">
      <h2>Vos proches pourront par exemple s'offrir</h2>
    </div>
    <div class="products-grid products-grid-cinetique" id="related-carousel">
      <?php
      while ($gift_query->have_posts()) {
        $gift_query->the_post();
        wc_get_template_part('content', 'product');
      }
      wp_reset_postdata();
      ?>
    </div>
    <div class="related-cta">
      <a href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>" class="related-cta-btn">
        Voir toutes les cr&eacute;ations
      </a>
    </div>
  </section>
  <?php endif; ?>

  <?php else : ?>
  <!-- Produits similaires (standard) -->
  <?php
  $related_products = wc_get_related_products($product_id, 4);
  if (!empty($related_products)) :
  ?>
  <section class="product-related">
    <div class="product-related-header">
      <span class="section-number"><?php echo esc_html(sprintf('%02d', ++$section_num)); ?></span>
      <h2>Vous aimerez aussi</h2>
    </div>
    <div class="products-grid products-grid-cinetique" id="related-carousel">
      <?php
      foreach ($related_products as $related_id) {
        $post_object = get_post($related_id);
        setup_postdata($GLOBALS['post'] = $post_object);
        wc_get_template_part('content', 'product');
      }
      wp_reset_postdata();
      ?>
    </div>
    <?php
    // CTA vers la page catégorie du produit
    $product_cats = get_the_terms($product_id, 'product_cat');
    if ($product_cats && !is_wp_error($product_cats)) {
      $main_cat = null;
      foreach ($product_cats as $cat) {
        if ($cat->slug !== 'non-classe' && $cat->slug !== 'uncategorized') {
          $main_cat = $cat;
          break;
        }
      }
      if ($main_cat) {
        $masculin = in_array($main_cat->slug, ['accessoires', 'lampadaires']);
        $cta_text = $masculin ? 'Voir tous les ' : 'Voir toutes les ';
        $cta_text .= strtolower($main_cat->name);
        ?>
        <div class="related-cta">
          <a href="<?php echo esc_url(get_term_link($main_cat)); ?>" class="related-cta-btn">
            <?php echo esc_html($cta_text); ?>
          </a>
        </div>
      <?php } ?>
    <?php } ?>
  </section>
  <?php endif; ?>
  <?php endif; // fin carte cadeau vs standard ?>

  <?php do_action('woocommerce_after_single_product'); ?>
  </div><!-- /.product-page-cinetique -->

<?php endwhile; ?>

<!-- Sticky Add to Cart Bar -->
<?php if ($product) : ?>
<div class="sticky-add-to-cart" id="sticky-add-to-cart" data-product-type="<?php echo esc_attr($product->get_type()); ?>">
  <div class="sticky-inner">
    <div class="sticky-product-info">
      <?php echo $product->get_image('thumbnail'); ?>
      <div class="sticky-product-details">
        <span class="sticky-product-name"><?php the_title(); ?></span>
        <span class="sticky-product-price"><?php echo $product->get_price_html(); ?></span>
      </div>
    </div>
    <div class="sticky-actions">
      <?php if ($is_variable) : ?>
        <a href="#product-summary-main" class="sticky-btn sticky-scroll-to-form">
          <?php esc_html_e('Choisir les options', 'theme-sapi-maison'); ?>
        </a>
      <?php else : ?>
        <button type="button" class="sticky-btn sticky-add-btn" data-product-id="<?php echo esc_attr($product_id); ?>">
          <?php esc_html_e('Ajouter au panier', 'theme-sapi-maison'); ?>
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function() {
  // Product Intro Screen Animation with Scroll-to-Reveal
  const introScreen = document.getElementById('product-intro-screen');

  if (introScreen) {
    // N'afficher l'intro qu'une seule fois par session par produit.
    // Après un ajout au panier (rechargement), l'intro ne réapparaît pas.
    const introKey = 'sapi_intro_<?php echo $product->get_id(); ?>';
    const fromStar = new URLSearchParams(window.location.search).get('from') === 'star';
    if (sessionStorage.getItem(introKey) || fromStar) {
      introScreen.remove();
      if (fromStar) window.history.replaceState({}, '', window.location.pathname);
    } else {
      sessionStorage.setItem(introKey, '1');

    let introRemoved = false;
    let scrollProgress = 0;
    const fadeDistance = 200;
    const snapThreshold = 0.3; // 30% → au-delà on ferme, en-deçà on ramène

    // Bloquer le scroll de la page derrière l'intro
    document.documentElement.classList.add('sapi-intro-active');

    // Fade in the image after initial black fade
    setTimeout(function() {
      introScreen.classList.add('loaded');
    }, 300);

    // Snap : fermer ou ramener selon la progression
    function snapIntro() {
      var progress = Math.min(1, scrollProgress / fadeDistance);
      if (progress > 0 && progress < 1) {
        introScreen.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        if (progress >= snapThreshold) {
          removeIntro();
        } else {
          // Ramener à la position initiale
          scrollProgress = 0;
          introScreen.style.transform = 'translateY(0)';
          // Retirer la transition après l'animation
          setTimeout(function() { introScreen.style.transition = ''; }, 400);
        }
      }
    }

    // Glissement vers le haut via wheel
    var wheelTimer = null;
    function handleWheel(e) {
      if (introRemoved) return;
      e.preventDefault();

      scrollProgress += Math.abs(e.deltaY);
      var progress = Math.min(1, scrollProgress / fadeDistance);
      introScreen.style.transform = 'translateY(-' + (progress * 100) + 'vh)';

      if (progress >= 1) {
        removeIntro();
      } else {
        // Snap après un court délai sans scroll (l'utilisateur a arrêté)
        clearTimeout(wheelTimer);
        wheelTimer = setTimeout(snapIntro, 150);
      }
    }

    // Glissement via touch (swipe up)
    let touchStartY = 0;
    function handleTouchStart(e) {
      touchStartY = e.touches[0].clientY;
    }
    function handleTouchMove(e) {
      if (introRemoved) return;
      e.preventDefault();

      const deltaY = touchStartY - e.touches[0].clientY;
      if (deltaY > 0) {
        scrollProgress += deltaY;
        touchStartY = e.touches[0].clientY;
        var progress = Math.min(1, scrollProgress / fadeDistance);
        introScreen.style.transform = 'translateY(-' + (progress * 100) + 'vh)';

        if (progress >= 1) {
          removeIntro();
        }
      }
    }
    function handleTouchEnd() {
      if (!introRemoved) snapIntro();
    }

    function removeIntro() {
      if (introRemoved) return;
      introRemoved = true;
      clearTimeout(wheelTimer);
      introScreen.style.transform = 'translateY(-100vh)';
      document.documentElement.classList.remove('sapi-intro-active');
      window.removeEventListener('wheel', handleWheel, { passive: false });
      introScreen.removeEventListener('touchstart', handleTouchStart);
      introScreen.removeEventListener('touchmove', handleTouchMove);
      introScreen.removeEventListener('touchend', handleTouchEnd);
      setTimeout(function() {
        introScreen.remove();
      }, 400);
    }

    // Click to skip — glisse vers le haut
    introScreen.addEventListener('click', function() {
      if (!introRemoved) {
        introScreen.style.transition = 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        removeIntro();
      }
    });

    // Écouter après le chargement de l'image
    setTimeout(function() {
      window.addEventListener('wheel', handleWheel, { passive: false });
      introScreen.addEventListener('touchstart', handleTouchStart, { passive: true });
      introScreen.addEventListener('touchmove', handleTouchMove, { passive: false });
      introScreen.addEventListener('touchend', handleTouchEnd, { passive: true });
    }, 800);
    } // end else (intro non encore vue)
  }

  const stickyBar = document.getElementById('sticky-add-to-cart');
  const heroSection = document.querySelector('.product-hero-v2');

  // Show/hide sticky bar based on scroll position
  if (stickyBar && heroSection) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        stickyBar.classList.toggle('is-visible', !entry.isIntersecting);
      });
    }, { threshold: 0, rootMargin: '-100px 0px 0px 0px' });

    observer.observe(heroSection);
  }

  // Handle simple product add to cart (Safari compatible)
  const addBtn = stickyBar ? stickyBar.querySelector('.sticky-add-btn') : null;
  if (addBtn) {
    addBtn.addEventListener('click', function() {
      const btn = this;
      const productId = btn.dataset.productId;

      btn.classList.add('loading');
      btn.textContent = 'Ajout...';

      // Use jQuery AJAX for Safari compatibility (WooCommerce standard method)
      if (typeof jQuery !== 'undefined') {
        jQuery.ajax({
          type: 'POST',
          url: '<?php echo esc_url(home_url('/?wc-ajax=sapi_add_to_cart')); ?>',
          data: {
            action: 'sapi_add_to_cart',
            product_id: productId,
            quantity: 1,
            nonce: '<?php echo wp_create_nonce('sapi-add-to-cart'); ?>'
          },
          success: function(response) {
            btn.classList.remove('loading');
            if (response.success) {
              btn.textContent = 'Ajouté !';
              // Trigger WooCommerce cart update
              jQuery(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash]);
              // Update mini cart if exists
              if (response.data.fragments) {
                jQuery.each(response.data.fragments, function(key, value) {
                  jQuery(key).replaceWith(value);
                });
              }
            } else {
              btn.textContent = 'Erreur';
            }
            setTimeout(function() {
              btn.textContent = '<?php esc_html_e('Ajouter au panier', 'theme-sapi-maison'); ?>';
            }, 2000);
          },
          error: function() {
            btn.classList.remove('loading');
            btn.textContent = 'Erreur';
            setTimeout(function() {
              btn.textContent = '<?php esc_html_e('Ajouter au panier', 'theme-sapi-maison'); ?>';
            }, 2000);
          }
        });
      }
    });
  }

  // Handle variable product scroll to form
  const scrollBtn = stickyBar ? stickyBar.querySelector('.sticky-scroll-to-form') : null;
  if (scrollBtn) {
    scrollBtn.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  // Update sticky bar, gallery and specs when variation is selected
  const variationForm = document.querySelector('.variations_form');
  const mainImage = document.querySelector('.gallery-main-image');
  const galleryZoomLink = null; // Removed: lightbox replaces zoom link

  // Store original first thumbnail data
  const firstThumb = document.querySelector('.gallery-thumb');
  let originalFirstThumbSrc = null;
  let originalFirstThumbData = null;

  if (firstThumb) {
    const firstThumbImg = firstThumb.querySelector('img');
    if (firstThumbImg) {
      originalFirstThumbSrc = firstThumbImg.src;
    }
    originalFirstThumbData = firstThumb.dataset.image;
  }

  // Lecture de la valeur originale d'un champ de la fiche technique
  function getOrigSpecValue(label) {
    for (const item of document.querySelectorAll('.spec-item')) {
      const lbl = item.querySelector('.spec-label');
      if (lbl && lbl.textContent.trim() === label) {
        const val = item.querySelector('.spec-value');
        return val ? val.textContent : '';
      }
    }
    return '';
  }

  // Mise à jour d'un champ de la fiche technique par son libellé
  function updateSpecLabel(label, value) {
    document.querySelectorAll('.spec-item').forEach(item => {
      const lbl = item.querySelector('.spec-label');
      if (lbl && lbl.textContent.trim() === label) {
        const val = item.querySelector('.spec-value');
        if (val) val.textContent = value;
      }
    });
  }

  // Lecture du libellé d'un attribut de variation depuis les données WooCommerce
  function getVariationAttributeLabel(variation, attrName) {
    if (!variationForm || !variation || !variation.attributes) return '';
    // Chercher la clé d'attribut correspondante dans variation.attributes
    var attrKey = '';
    var attrSlug = '';
    for (var key in variation.attributes) {
      if (key.toLowerCase().indexOf(attrName.toLowerCase()) !== -1) {
        attrKey = key;
        attrSlug = variation.attributes[key];
        break;
      }
    }
    if (!attrKey || !attrSlug) return '';
    // Chercher le label dans le select natif WooCommerce
    var select = variationForm.querySelector('select[name="' + attrKey + '"]');
    if (select) {
      var option = select.querySelector('option[value="' + attrSlug + '"]');
      if (option) return option.textContent.trim();
    }
    // Chercher dans les swatches (data-value correspond au slug)
    var wrapper = variationForm.querySelector('.variable-items-wrapper[data-attribute_name="' + attrKey + '"]');
    if (wrapper) {
      var swatch = wrapper.querySelector('.variable-item[data-value="' + attrSlug + '"]');
      if (swatch) return swatch.dataset.title || swatch.getAttribute('title') || '';
    }
    // Fallback : capitaliser le slug
    return attrSlug.charAt(0).toUpperCase() + attrSlug.slice(1).replace(/-/g, ' ');
  }

  const origBoisValue       = getOrigSpecValue('Bois');
  const origDimensionsValue = getOrigSpecValue('Dimensions');
  const origPoidsValue      = getOrigSpecValue('Poids');

  // État initial : Dimensions et Poids dépendent du choix de variation
  if (variationForm) {
    updateSpecLabel('Dimensions', 'Faites votre choix');
    updateSpecLabel('Poids',      'Faites votre choix');
  }

  if (variationForm && typeof jQuery !== 'undefined') {
    jQuery(variationForm).on('found_variation', function(event, variation) {
      // Update sticky bar price
      if (stickyBar) {
        const priceEl = stickyBar.querySelector('.sticky-product-price');
        if (priceEl && variation.price_html) {
          priceEl.innerHTML = variation.price_html;
        }
      }
      if (scrollBtn) {
        scrollBtn.textContent = '<?php esc_html_e('Ajouter au panier', 'theme-sapi-maison'); ?>';
        scrollBtn.classList.add('variation-selected');
        scrollBtn.addEventListener('click', function submitForm(e) {
          e.preventDefault();
          const mainForm = document.querySelector('.variations_form');
          if (mainForm) {
            mainForm.querySelector('.single_add_to_cart_button').click();
          }
        }, { once: true });
      }

      // Update gallery with variation image
      if (variation.image && variation.image.src && mainImage) {
        const variationImageSrc = variation.image.src;
        const variationImageFull = variation.image.full_src || variationImageSrc;
        const variationThumbSrc = variation.image.gallery_thumbnail_src || variationImageSrc;

        // Update main image
        mainImage.src = variationImageSrc;
        mainImage.srcset = variation.image.srcset || '';
        if (galleryZoomLink) {
          galleryZoomLink.href = variationImageFull;
        }

        // Replace first thumbnail with variation image
        if (firstThumb) {
          const firstThumbImg = firstThumb.querySelector('img');
          if (firstThumbImg) {
            firstThumbImg.src = variationThumbSrc;
          }
          firstThumb.dataset.image = variationImageSrc;
          firstThumb.classList.add('variation-active');

          // Make sure it's active
          document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
          firstThumb.classList.add('active');
        }
      }

      // Mettre à jour "Bois" avec le libellé sélectionné dans l'attribut Matériau
      var materiauLabel = getVariationAttributeLabel(variation, 'materiau');
      if (materiauLabel) updateSpecLabel('Bois', materiauLabel);

      // Mettre à jour "Dimensions" — priorité aux données WooCommerce, sinon label attribut
      if (variation.dimensions_html && variation.dimensions_html.trim()) {
        updateSpecLabel('Dimensions', variation.dimensions_html.replace(/<[^>]*>/g, '').trim());
      } else {
        var tailleLabel = getVariationAttributeLabel(variation, 'taille');
        if (tailleLabel) updateSpecLabel('Dimensions', tailleLabel);
      }

      // Mettre à jour "Poids" — priorité aux données WooCommerce formatées
      if (variation.weight_html && variation.weight_html.trim()) {
        updateSpecLabel('Poids', variation.weight_html.replace(/<[^>]*>/g, '').trim());
      } else if (variation.weight) {
        updateSpecLabel('Poids', variation.weight + ' kg');
      }
    });

    jQuery(variationForm).on('reset_data', function() {
      if (scrollBtn) {
        scrollBtn.textContent = '<?php esc_html_e('Choisir les options', 'theme-sapi-maison'); ?>';
        scrollBtn.classList.remove('variation-selected');
      }

      // Restore original first thumbnail
      if (firstThumb && originalFirstThumbSrc && originalFirstThumbData) {
        const firstThumbImg = firstThumb.querySelector('img');
        if (firstThumbImg) {
          firstThumbImg.src = originalFirstThumbSrc;
        }
        firstThumb.dataset.image = originalFirstThumbData;
        firstThumb.classList.remove('variation-active');

        // Update main image to first thumbnail
        if (mainImage) {
          mainImage.src = originalFirstThumbData;
          mainImage.srcset = '';
        }
        if (galleryZoomLink) {
          galleryZoomLink.href = originalFirstThumbData;
        }
      }

      // Restaurer les valeurs originales
      if (origBoisValue) updateSpecLabel('Bois', origBoisValue);
      updateSpecLabel('Dimensions', 'Faites votre choix');
      updateSpecLabel('Poids',      'Faites votre choix');
    });
  }

  // Gallery thumbnail switching
  const thumbnails = document.querySelectorAll('.gallery-thumb');
  const videoContainer = document.querySelector('.gallery-main-video');

  function showVideo() {
    if (videoContainer && mainImage) {
      mainImage.style.display = 'none';
      videoContainer.style.display = 'block';
    }
  }

  function hideVideo() {
    if (videoContainer && mainImage) {
      videoContainer.style.display = 'none';
      mainImage.style.display = '';
    }
  }

  thumbnails.forEach(thumb => {
    thumb.addEventListener('click', function() {
      thumbnails.forEach(t => t.classList.remove('active'));
      this.classList.add('active');

      if (this.dataset.video === 'true') {
        showVideo();
      } else {
        hideVideo();
        if (mainImage) {
          mainImage.src = this.dataset.image;
          mainImage.srcset = '';
        }
      }
      if (galleryZoomLink) {
        galleryZoomLink.href = this.dataset.image || '';
      }
    });
  });

  // Click on main gallery image opens the lightbox (mobile + desktop)
  var galleryMainEl = document.querySelector('.gallery-main');
  if (galleryMainEl) {
    galleryMainEl.addEventListener('click', function(e) {
      if (e.target.closest('.gallery-nav')) return; // Ignore arrow clicks
      var lb = document.getElementById('ambiance-lightbox');
      if (lb && lb.openLightbox) {
        // Open lightbox at the currently displayed image
        var activeThumb = document.querySelector('.gallery-thumb.active');
        var allThumbs = document.querySelectorAll('.gallery-thumb');
        var idx = 0;
        allThumbs.forEach(function(t, i) { if (t === activeThumb) idx = i; });
        lb.openLightbox(idx);
      }
    });
  }

  // Mobile gallery navigation with arrows and swipe
  const galleryMain = document.querySelector('.gallery-main');
  const navPrev = document.querySelector('.gallery-nav-prev');
  const navNext = document.querySelector('.gallery-nav-next');

  if (galleryMain && thumbnails.length > 1) {
    let currentIndex = 0;

    // Function to navigate to a specific image
    function navigateToImage(index) {
      if (index < 0 || index >= thumbnails.length) return;

      currentIndex = index;
      const targetThumb = thumbnails[index];

      // Update active thumbnail
      thumbnails.forEach(t => t.classList.remove('active'));
      targetThumb.classList.add('active');

      if (targetThumb.dataset.video === 'true') {
        showVideo();
      } else {
        hideVideo();
        // Update main image
        if (mainImage) {
          mainImage.src = targetThumb.dataset.image;
          mainImage.srcset = '';
        }
      }

      // Update zoom link if available
      if (galleryZoomLink) {
        galleryZoomLink.href = targetThumb.dataset.image || '';
      }
    }

    // Arrow navigation
    if (navPrev) {
      navPrev.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const newIndex = currentIndex > 0 ? currentIndex - 1 : thumbnails.length - 1;
        navigateToImage(newIndex);
      });
    }

    if (navNext) {
      navNext.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const newIndex = currentIndex < thumbnails.length - 1 ? currentIndex + 1 : 0;
        navigateToImage(newIndex);
      });
    }

    // Touch swipe detection
    let touchStartX = 0;
    let touchEndX = 0;
    const minSwipeDistance = 50; // minimum distance for a swipe

    galleryMain.addEventListener('touchstart', function(e) {
      touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    galleryMain.addEventListener('touchend', function(e) {
      touchEndX = e.changedTouches[0].screenX;
      handleSwipe();
    }, { passive: true });

    function handleSwipe() {
      const swipeDistance = touchEndX - touchStartX;

      if (Math.abs(swipeDistance) < minSwipeDistance) return; // Not a swipe

      if (swipeDistance > 0) {
        // Swipe right - go to previous image
        const newIndex = currentIndex > 0 ? currentIndex - 1 : thumbnails.length - 1;
        navigateToImage(newIndex);
      } else {
        // Swipe left - go to next image
        const newIndex = currentIndex < thumbnails.length - 1 ? currentIndex + 1 : 0;
        navigateToImage(newIndex);
      }
    }
  }

  // ========================================
  // AJAX Add to Cart — listener click (évite les conflits avec WC variation JS)
  // Fonctionne pour produits simples ET variables
  // ========================================
  const mainAddBtn = document.querySelector('.single_add_to_cart_button');
  if (mainAddBtn && typeof jQuery !== 'undefined') {
    mainAddBtn.addEventListener('click', function(e) {
      // Laisser passer si le bouton est désactivé (variation non sélectionnée)
      if (this.classList.contains('disabled') || this.disabled) return;
      e.preventDefault();

      const btn = this;
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Ajout en cours…';

      // Sérialiser TOUT le formulaire (variations + add-ons + futurs plugins)
      // On retire add-to-cart pour éviter que WC_Form_Handler l'intercepte (double ajout)
      const cartForm = document.querySelector('form.cart');
      const formSerialized = cartForm
        ? jQuery(cartForm).serialize().replace(/(?:^|&)add-to-cart=[^&]*/g, '')
        : '';
      const ajaxData = formSerialized
        + '&action=sapi_add_to_cart'
        + '&nonce=<?php echo wp_create_nonce('sapi-add-to-cart'); ?>'
        + '&product_id=<?php echo $product->get_id(); ?>';

      jQuery.ajax({
        type: 'POST',
        url: '<?php echo esc_url(home_url('/?wc-ajax=sapi_add_to_cart')); ?>',
        data: ajaxData,
        success: function(response) {
          btn.disabled = false;
          if (response.success) {
            btn.textContent = 'Ajouté !';
            setTimeout(function() { btn.textContent = originalText; }, 2500);
            // Ouvre le mini-cart et met à jour les fragments
            jQuery(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash]);
            if (response.data.fragments) {
              jQuery.each(response.data.fragments, function(key, value) {
                jQuery(key).replaceWith(value);
              });
            }
          } else {
            btn.textContent = originalText;
          }
        },
        error: function() {
          btn.disabled = false;
          btn.textContent = originalText;
        }
      });
    });
  }

  // Buy Now (Express Checkout) - Phase 4 Proposal B
  const buyNowBtn = document.querySelector('.btn-buy-now');
  if (buyNowBtn && typeof jQuery !== 'undefined') {
    buyNowBtn.addEventListener('click', function() {
      const btn = this;
      const productId = btn.dataset.productId;
      const variationForm = document.querySelector('.variations_form');

      // Check if product is variable and variation is selected
      if (variationForm) {
        const variationId = variationForm.querySelector('input[name="variation_id"]');
        if (!variationId || !variationId.value) {
          alert('Veuillez sélectionner toutes les options avant d\'acheter');
          return;
        }
      }

      btn.classList.add('loading');
      btn.disabled = true;
      const originalText = btn.innerHTML;
      btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Préparation...';

      // Sérialiser TOUT le formulaire (variations + add-ons + futurs plugins)
      // On retire add-to-cart pour éviter que WC_Form_Handler l'intercepte (double ajout)
      const cartForm = document.querySelector('form.cart');
      const formSerialized = cartForm
        ? jQuery(cartForm).serialize().replace(/(?:^|&)add-to-cart=[^&]*/g, '')
        : '';
      const ajaxData = formSerialized
        + '&action=sapi_buy_now'
        + '&product_id=' + productId
        + '&quantity=1'
        + '&nonce=<?php echo wp_create_nonce('sapi-buy-now'); ?>';

      jQuery.ajax({
        type: 'POST',
        url: '<?php echo esc_url(home_url('/?wc-ajax=sapi_buy_now')); ?>',
        data: ajaxData,
        success: function(response) {
          if (response.success) {
            // Redirect to checkout
            window.location.href = response.data.checkout_url;
          } else {
            btn.classList.remove('loading');
            btn.disabled = false;
            btn.innerHTML = originalText;
            alert(response.data.message || 'Une erreur est survenue');
          }
        },
        error: function() {
          btn.classList.remove('loading');
          btn.disabled = false;
          btn.innerHTML = originalText;
          alert('Une erreur est survenue, veuillez réessayer');
        }
      });
    });
  }

  // ── Bouton "Ajouter l'ampoule au panier" ──
  document.querySelectorAll('.add-ampoule-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      var button = this;
      var productId = button.dataset.productId;
      if (!productId || button.classList.contains('loading')) return;

      button.classList.add('loading');
      var originalText = button.innerHTML;
      button.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg> Ajout en cours…';

      jQuery.ajax({
        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        type: 'POST',
        data: {
          action: 'sapi_add_to_cart',
          product_id: productId,
          quantity: 1,
          nonce: '<?php echo wp_create_nonce('sapi-add-to-cart'); ?>'
        },
        success: function(response) {
          if (response.success) {
            button.classList.remove('loading');
            button.classList.add('added');
            button.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Ajouté !';
            jQuery(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash]);
            setTimeout(function() {
              button.classList.remove('added');
              button.innerHTML = originalText;
            }, 3000);
          } else {
            button.classList.remove('loading');
            button.innerHTML = originalText;
          }
        },
        error: function() {
          button.classList.remove('loading');
          button.innerHTML = originalText;
        }
      });
    });
  });
})();
</script>
<?php endif; ?>


<?php if (!empty($acf_photos)) : ?>
<!-- Lightbox Ambiance/Détail -->
<div class="ambiance-lightbox" id="ambiance-lightbox" aria-hidden="true" role="dialog" aria-modal="true" data-photos='<?php echo wp_json_encode($acf_photos, JSON_HEX_APOS | JSON_HEX_QUOT); ?>' data-first-acf="<?php echo esc_attr($first_acf_index); ?>">
  <div class="ambiance-lightbox-overlay"></div>
  <div class="ambiance-lightbox-content">
    <button class="ambiance-lightbox-close" aria-label="<?php esc_attr_e('Fermer', 'theme-sapi-maison'); ?>" type="button">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
    <div class="ambiance-lightbox-main">
      <img src="" alt="" class="ambiance-lightbox-image">
    </div>
    <div class="ambiance-lightbox-footer">
      <button class="ambiance-lightbox-prev" aria-label="<?php esc_attr_e('Image précédente', 'theme-sapi-maison'); ?>" type="button">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
      </button>
      <div class="ambiance-lightbox-thumbs-wrapper">
        <div class="ambiance-lightbox-thumbs"></div>
      </div>
      <button class="ambiance-lightbox-next" aria-label="<?php esc_attr_e('Image suivante', 'theme-sapi-maison'); ?>" type="button">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  var lightbox = document.getElementById('ambiance-lightbox');
  if (!lightbox) return;

  var photos = JSON.parse(lightbox.dataset.photos || '[]');
  if (!photos.length) return;

  var current = 0;
  var img = lightbox.querySelector('.ambiance-lightbox-image');
  var content = lightbox.querySelector('.ambiance-lightbox-content');
  var thumbsContainer = lightbox.querySelector('.ambiance-lightbox-thumbs');
  var productName = <?php echo wp_json_encode(get_the_title()); ?>;

  // Adjust card width to fit current image
  function adjustCardWidth() {
    if (!img.naturalWidth || !img.naturalHeight) return;
    var ratio = img.naturalWidth / img.naturalHeight;
    var maxH = window.innerHeight * 0.75;
    var maxW = Math.min(window.innerWidth * 0.94, 1200);
    var w = Math.min(ratio * maxH, maxW);
    // Minimum width for thumbnails row
    w = Math.max(w, 360);
    content.style.maxWidth = Math.ceil(w + 12) + 'px';
  }

  img.addEventListener('load', adjustCardWidth);

  // Build thumbnails
  photos.forEach(function(photo, i) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ambiance-thumb' + (i === 0 ? ' active' : '');
    btn.innerHTML = '<img src="' + photo.url + '" alt="' + productName + ' - ' + photo.label + '">';
    btn.addEventListener('click', function() { goTo(i); });
    thumbsContainer.appendChild(btn);
  });

  function goTo(index) {
    current = index;
    img.src = photos[current].url;
    img.srcset = '';
    img.alt = productName + ' - ' + photos[current].label;
    var thumbs = thumbsContainer.querySelectorAll('.ambiance-thumb');
    thumbs.forEach(function(t, i) { t.classList.toggle('active', i === current); });
  }

  var firstAcf = parseInt(lightbox.dataset.firstAcf, 10) || 0;

  function open(startIndex) {
    goTo(typeof startIndex === 'number' ? startIndex : 0);
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    lightbox.querySelector('.ambiance-lightbox-close').focus();
  }

  function close() {
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    // Restore gallery: reactivate first thumbnail and its image
    var allThumbs = document.querySelectorAll('.gallery-thumb');
    var firstThumb = document.querySelector('.gallery-thumb');
    if (firstThumb) {
      allThumbs.forEach(function(t) { t.classList.remove('active'); });
      firstThumb.classList.add('active');
      var mainImg = document.querySelector('.gallery-main-image');
      if (mainImg && firstThumb.dataset.image) {
        mainImg.src = firstThumb.dataset.image;
        mainImg.srcset = '';
      }
      var zoomLink = document.querySelector('.gallery-zoom');
      if (zoomLink && firstThumb.dataset.image) {
        zoomLink.href = firstThumb.dataset.image;
      }
    }
    var galleryMainFocus = document.querySelector('.gallery-main');
    if (galleryMainFocus) galleryMainFocus.focus();
  }

  // Expose open function so gallery click handler can call it
  lightbox.openLightbox = open;

  // Close
  lightbox.querySelector('.ambiance-lightbox-close').addEventListener('click', close);
  lightbox.querySelector('.ambiance-lightbox-overlay').addEventListener('click', close);

  // Navigation
  lightbox.querySelector('.ambiance-lightbox-prev').addEventListener('click', function() {
    goTo(current > 0 ? current - 1 : photos.length - 1);
  });
  lightbox.querySelector('.ambiance-lightbox-next').addEventListener('click', function() {
    goTo(current < photos.length - 1 ? current + 1 : 0);
  });

  // Keyboard
  lightbox.addEventListener('keydown', function(e) {
    if (lightbox.getAttribute('aria-hidden') === 'true') return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') goTo(current > 0 ? current - 1 : photos.length - 1);
    if (e.key === 'ArrowRight') goTo(current < photos.length - 1 ? current + 1 : 0);
  });
})();
</script>
<?php endif; ?>

<script>
(function() {
  var cta = document.getElementById('ctaRobinContact');
  if (!cta) return;

  var toggle = cta.querySelector('.robin-contact-toggle');
  var closed = cta.querySelector('.robin-contact-closed');
  var open = cta.querySelector('.robin-contact-open');
  var success = cta.querySelector('.robin-contact-success');
  var form = cta.querySelector('.robin-contact-form');

  toggle.addEventListener('click', function() {
    closed.hidden = true;
    open.hidden = false;
    cta.querySelector('.robin-contact-email').focus();
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var email = form.querySelector('.robin-contact-email').value;
    var btn = form.querySelector('.robin-contact-submit');
    btn.disabled = true;
    btn.textContent = 'Envoi…';

    // Récupérer le projet Robin Conseiller si disponible
    var project = '';
    try {
      var stored = localStorage.getItem('sapi_robin_project');
      if (stored) {
        var p = JSON.parse(stored);
        var parts = [];
        if (p.piece) parts.push(p.piece);
        if (p.taille) parts.push(p.taille);
        if (p.style) parts.push(p.style);
        if (parts.length) project = parts.join(' · ');
      }
    } catch(err) {}

    var fd = new FormData();
    fd.append('action', 'sapi_robin_contact');
    fd.append('nonce', form.querySelector('[name="robin_contact_nonce"]').value);
    var message = form.querySelector('.robin-contact-message').value;
    fd.append('email', email);
    fd.append('page', form.dataset.product || window.location.pathname);
    if (message) fd.append('message', message);
    if (project) fd.append('project', project);

    fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', {
      method: 'POST',
      body: fd
    }).then(function() {
      open.hidden = true;
      success.hidden = false;
    }).catch(function() {
      btn.disabled = false;
      btn.textContent = 'Envoyer';
    });
  });
})();
</script>

<?php
get_footer();

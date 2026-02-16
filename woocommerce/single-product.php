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

  // ACF fields
  $phrase = function_exists('get_field') ? get_field('phrase_daccroche') : '';
  $mini_description = function_exists('get_field') ? get_field('mini_description') : '';

  // Price display
  $price_html = $product->get_price_html();

  // Stock status
  $stock_status = $product->get_stock_status();
  ?>

  <?php do_action('woocommerce_before_single_product'); ?>

  <div id="product-<?php the_ID(); ?>" <?php wc_product_class('product-page-cinetique product-page-v2', $product); ?>>

  <?php
  // Get Ambiance 1 image for intro screen
  $ambiance_intro = '';
  if (function_exists('get_field')) {
    $ambiance_1 = get_field('ambiance_1');
    if ($ambiance_1) {
      // Handle different ACF return formats
      if (is_array($ambiance_1) && isset($ambiance_1['url'])) {
        $ambiance_intro = $ambiance_1['url'];
      } elseif (is_array($ambiance_1) && isset($ambiance_1['ID'])) {
        $ambiance_intro = wp_get_attachment_image_url($ambiance_1['ID'], 'full');
      } elseif (is_numeric($ambiance_1)) {
        $ambiance_intro = wp_get_attachment_image_url($ambiance_1, 'full');
      } elseif (is_string($ambiance_1) && strpos($ambiance_1, 'http') === 0) {
        $ambiance_intro = $ambiance_1;
      }
    }
  }

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
          <div class="gallery-main">
            <a href="<?php echo esc_url($main_image_full); ?>" class="gallery-zoom" data-fancybox="product-gallery">
              <img src="<?php echo esc_url($main_image_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" class="gallery-main-image">
              <span class="gallery-zoom-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                  <line x1="11" y1="8" x2="11" y2="14"></line>
                  <line x1="8" y1="11" x2="14" y2="11"></line>
                </svg>
              </span>
            </a>
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
          if (count($all_images) > 1) {
            ?>
            <div class="gallery-thumbnails">
              <?php foreach ($all_images as $index => $image_id) :
                $thumb_url = wp_get_attachment_image_url($image_id, 'woocommerce_gallery_thumbnail');
                $full_url = wp_get_attachment_image_url($image_id, 'woocommerce_single');
                ?>
                <button class="gallery-thumb<?php echo $index === 0 ? ' active' : ''; ?>" data-image="<?php echo esc_url($full_url); ?>">
                  <img src="<?php echo esc_url($thumb_url); ?>" alt="">
                </button>
              <?php endforeach; ?>
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

        <!-- Séparateur visuel -->
        <hr class="product-divider">

        <!-- Formulaire d'achat (variations + quantité + CTA) -->
        <div class="product-form-v2">
          <!-- Introduction aux variations -->
          <p class="variations-intro">Composez votre luminaire :</p>

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
        <div class="product-reassurance-v2">
          <div class="reassurance-item-v2">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M4 20l7-7M11 13l7-7M18 6l2-2"/>
              <circle cx="20" cy="4" r="2"/>
              <line x1="4" y1="20" x2="2" y2="22"/>
              <line x1="7" y1="17" x2="5" y2="19"/>
              <line x1="10" y1="14" x2="8" y2="16"/>
              <line x1="13" y1="11" x2="11" y2="13"/>
              <line x1="16" y1="8" x2="14" y2="10"/>
            </svg>
            <span>Fabrication <strong>&lt;5 jours</strong></span>
          </div>
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

        <!-- Stock indicator -->
        <?php
        $stock_class = 'stock-badge-v2';
        if ($stock_status === 'instock') {
          $stock_class .= ' in-stock';
          $stock_icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>';
          $stock_text = __('En stock', 'theme-sapi-maison');
        } elseif ($stock_status === 'onbackorder') {
          $stock_class .= ' on-order';
          $stock_icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
          $stock_text = __('Fabriqué à la commande', 'theme-sapi-maison');
        } else {
          $stock_class .= ' out-of-stock';
          $stock_icon = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/></svg>';
          $stock_text = __('Indisponible', 'theme-sapi-maison');
        }
        ?>
        <div class="<?php echo esc_attr($stock_class); ?>">
          <?php echo $stock_icon; ?>
          <span><?php echo esc_html($stock_text); ?></span>
        </div>

        <!-- CTA Sur-mesure -->
        <div class="product-custom-cta">
          <p class="custom-cta-text">Besoin d'une version sur-mesure ?</p>
          <a href="mailto:contact@atelier-sapi.fr" class="custom-cta-link">
            Parlons de votre projet →
          </a>
        </div>

        <!-- Micro-copy artisan -->
        <p class="product-artisan-note">
          <em>Chaque pièce est découpée au laser puis assemblée à la main dans notre atelier lyonnais.</em>
        </p>

      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 02 — POURQUOI CETTE PIÈCE
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-why product-why-cinetique">
    <div class="product-why-header">
      <span class="section-number">02</span>
      <h2>Pourquoi cette pièce ?</h2>
    </div>
    <div class="product-why-grid">
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
      </div>
      <div class="product-why-usage">
        <h3>Idéal pour</h3>
        <ul class="usage-list">
          <?php
          $usages = function_exists('get_field') ? get_field('usages') : null;
          if ($usages && is_array($usages)) {
            foreach ($usages as $usage) {
              echo '<li>' . esc_html($usage['usage']) . '</li>';
            }
          } else {
            $default_usages = ['Salon & séjour', 'Chambre à coucher', 'Bureau & espace de travail', 'Entrée & couloir'];
            foreach ($default_usages as $usage) {
              echo '<li>' . esc_html($usage) . '</li>';
            }
          }
          ?>
        </ul>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 03 — FICHE TECHNIQUE (Style bōlum)
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-specs product-specs-v2">
    <div class="product-specs-header">
      <span class="section-number">03</span>
      <h2>Fiche technique</h2>
      <p class="specs-intro">Toutes les informations pour bien choisir votre luminaire</p>
    </div>

    <!-- Accordion Mobile - Phase 4 Proposal B -->
    <div class="product-specs-accordion">
      <?php
      // Get dimensions and weight (same logic as grid)
      $dimensions_str = '';
      $poids = '';
      if (function_exists('get_field')) {
        $dimensions = get_field('dimensions');
        $hauteur = get_field('hauteur');
        $largeur = get_field('largeur');
        $profondeur = get_field('profondeur');
        $poids = get_field('poids');

        if ($dimensions) {
          $dimensions_str = $dimensions;
        } elseif ($hauteur || $largeur || $profondeur) {
          $dim_parts = [];
          if ($largeur) $dim_parts[] = 'L ' . $largeur;
          if ($profondeur) $dim_parts[] = 'P ' . $profondeur;
          if ($hauteur) $dim_parts[] = 'H ' . $hauteur;
          $dimensions_str = implode(' × ', $dim_parts);
        }
      }

      if (!$dimensions_str && $product) {
        $wc_dims = wc_format_dimensions($product->get_dimensions(false));
        if ($wc_dims && $wc_dims !== 'N/A') {
          $dimensions_str = $wc_dims;
        }
      }

      if (!isset($poids) || !$poids) {
        $poids = $product && $product->get_weight() ? $product->get_weight() . ' kg' : null;
      }
      ?>

      <details class="specs-accordion-item">
        <summary class="specs-accordion-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          </svg>
          <span>Dimensions</span>
          <svg class="accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </summary>
        <div class="specs-accordion-content">
          <div class="spec-item">
            <span class="spec-label">Dimensions</span>
            <span class="spec-value"><?php echo esc_html($dimensions_str ?: 'Voir variations'); ?></span>
          </div>
          <?php if ($poids) : ?>
          <div class="spec-item">
            <span class="spec-label">Poids</span>
            <span class="spec-value"><?php echo esc_html($poids); ?></span>
          </div>
          <?php endif; ?>
          <div class="spec-item">
            <span class="spec-label">Longueur câble</span>
            <span class="spec-value">90 cm (ajustable)</span>
          </div>
        </div>
      </details>

      <details class="specs-accordion-item">
        <summary class="specs-accordion-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 18h6"/><path d="M10 22h4"/>
            <path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>
          </svg>
          <span>Éclairage</span>
          <svg class="accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </summary>
        <div class="specs-accordion-content">
          <div class="spec-item">
            <span class="spec-label">Culot</span>
            <span class="spec-value">E27</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Ampoule recommandée</span>
            <span class="spec-value">LED filament 4-6W (2700K)</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Compatible variateur</span>
            <span class="spec-value">Oui, avec ampoule dimmable</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Ampoule incluse</span>
            <span class="spec-value">Non (disponible en option)</span>
          </div>
        </div>
      </details>

      <details class="specs-accordion-item">
        <summary class="specs-accordion-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
          </svg>
          <span>Matériaux</span>
          <svg class="accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </summary>
        <div class="specs-accordion-content">
          <div class="spec-item">
            <span class="spec-label">Structure</span>
            <span class="spec-value">Peuplier français PEFC</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Finition</span>
            <span class="spec-value">Bois naturel non traité</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Câble</span>
            <span class="spec-value">Textile noir tressé</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Pavillon</span>
            <span class="spec-value">Métal noir mat</span>
          </div>
        </div>
      </details>

      <details class="specs-accordion-item">
        <summary class="specs-accordion-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
          </svg>
          <span>Installation</span>
          <svg class="accordion-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="6 9 12 15 18 9"/>
          </svg>
        </summary>
        <div class="specs-accordion-content">
          <div class="spec-item">
            <span class="spec-label">Montage</span>
            <span class="spec-value">Notice illustrée incluse</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Difficulté</span>
            <span class="spec-value">Facile (15-30 min)</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Outils requis</span>
            <span class="spec-value">Tournevis (fourni)</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Entretien</span>
            <span class="spec-value">Chiffon sec</span>
          </div>
        </div>
      </details>
    </div>

    <div class="product-specs-grid">
      <!-- Colonne 1: Dimensions & Poids -->
      <div class="specs-column">
        <h3 class="specs-column-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          </svg>
          Dimensions
        </h3>
        <div class="specs-list">
          <?php
          $dimensions_str = '';
          if (function_exists('get_field')) {
            $dimensions = get_field('dimensions');
            $hauteur = get_field('hauteur');
            $largeur = get_field('largeur');
            $profondeur = get_field('profondeur');
            $poids = get_field('poids');

            if ($dimensions) {
              $dimensions_str = $dimensions;
            } elseif ($hauteur || $largeur || $profondeur) {
              $dim_parts = [];
              if ($largeur) $dim_parts[] = 'L ' . $largeur;
              if ($profondeur) $dim_parts[] = 'P ' . $profondeur;
              if ($hauteur) $dim_parts[] = 'H ' . $hauteur;
              $dimensions_str = implode(' × ', $dim_parts);
            }
          }

          if (!$dimensions_str && $product) {
            $wc_dims = wc_format_dimensions($product->get_dimensions(false));
            if ($wc_dims && $wc_dims !== 'N/A') {
              $dimensions_str = $wc_dims;
            }
          }

          if (!isset($poids) || !$poids) {
            $poids = $product && $product->get_weight() ? $product->get_weight() . ' kg' : null;
          }
          ?>
          <div class="spec-item">
            <span class="spec-label">Dimensions</span>
            <span class="spec-value"><?php echo esc_html($dimensions_str ?: 'Voir variations'); ?></span>
          </div>
          <?php if ($poids) : ?>
          <div class="spec-item">
            <span class="spec-label">Poids</span>
            <span class="spec-value"><?php echo esc_html($poids); ?></span>
          </div>
          <?php endif; ?>
          <div class="spec-item">
            <span class="spec-label">Longueur câble</span>
            <span class="spec-value">90 cm (ajustable)</span>
          </div>
        </div>
      </div>

      <!-- Colonne 2: Éclairage -->
      <div class="specs-column">
        <h3 class="specs-column-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 18h6"/><path d="M10 22h4"/>
            <path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>
          </svg>
          Éclairage
        </h3>
        <div class="specs-list">
          <div class="spec-item">
            <span class="spec-label">Culot</span>
            <span class="spec-value">E27</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Ampoule recommandée</span>
            <span class="spec-value">LED filament 4-6W (2700K)</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Compatible variateur</span>
            <span class="spec-value">Oui, avec ampoule dimmable</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Ampoule incluse</span>
            <span class="spec-value">Non (disponible en option)</span>
          </div>
        </div>
      </div>

      <!-- Colonne 3: Matériaux -->
      <div class="specs-column">
        <h3 class="specs-column-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>
          </svg>
          Matériaux
        </h3>
        <div class="specs-list">
          <div class="spec-item">
            <span class="spec-label">Structure</span>
            <span class="spec-value">Peuplier français PEFC</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Finition</span>
            <span class="spec-value">Bois naturel non traité</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Câble</span>
            <span class="spec-value">Textile noir tressé</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Pavillon</span>
            <span class="spec-value">Métal noir mat</span>
          </div>
        </div>
      </div>

      <!-- Colonne 4: Installation -->
      <div class="specs-column">
        <h3 class="specs-column-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
          </svg>
          Installation
        </h3>
        <div class="specs-list">
          <div class="spec-item">
            <span class="spec-label">Montage</span>
            <span class="spec-value">Notice illustrée incluse</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Difficulté</span>
            <span class="spec-value">Facile (15-30 min)</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Outils requis</span>
            <span class="spec-value">Tournevis (fourni)</span>
          </div>
          <div class="spec-item">
            <span class="spec-label">Entretien</span>
            <span class="spec-value">Chiffon sec</span>
          </div>
        </div>
      </div>
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

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 04 — L'ATELIER
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-atelier product-atelier-cinetique">
    <div class="product-atelier-grid">
      <div class="product-atelier-image">
        <?php
        $atelier_img_url = null;
        $atelier_img = function_exists('get_field') ? get_field('atelier_photo', 'option') : null;

        if ($atelier_img && isset($atelier_img['url'])) {
          $atelier_img_url = $atelier_img['url'];
        } else {
          $theme_image = get_template_directory() . '/assets/images/atelier-robin.jpg';
          if (file_exists($theme_image)) {
            $atelier_img_url = get_template_directory_uri() . '/assets/images/atelier-robin.jpg';
          }
        }

        if ($atelier_img_url) :
        ?>
          <img src="<?php echo esc_url($atelier_img_url); ?>" alt="Robin dans l'atelier Sapi" loading="lazy">
        <?php else : ?>
          <div class="atelier-placeholder">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
            </svg>
            <span>Photo atelier</span>
          </div>
        <?php endif; ?>
      </div>
      <div class="product-atelier-content">
        <span class="section-number">04</span>
        <h2>Fabriqué avec passion</h2>
        <p class="atelier-intro">Chaque luminaire est conçu et fabriqué à la main par Robin dans notre atelier lyonnais.</p>
        <blockquote class="atelier-quote">
          « Je crée chaque pièce comme si elle allait éclairer ma propre maison. La précision de la découpe laser combinée au savoir-faire artisanal, c'est ce qui rend nos luminaires uniques. »
        </blockquote>
        <p class="atelier-signature">— Robin, fondateur d'Atelier Sapi</p>
        <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="button button-outline">Découvrir notre histoire</a>
      </div>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 05 — TÉMOIGNAGES (Preuve Sociale)
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-testimonials">
    <div class="testimonials-header">
      <span class="section-number">05</span>
      <h2>Ce qu'en pensent nos clients</h2>
    </div>

    <?php
    // Hook pour intégrer Judge.me, Trustpilot ou autre système d'avis
    do_action('sapi_product_reviews', $product_id);

    // Afficher les avis WooCommerce natifs si disponibles
    $reviews_count = $product->get_review_count();

    if ($reviews_count > 0) :
      // WooCommerce reviews template
      comments_template();
    else :
      // Afficher des témoignages génériques si pas d'avis spécifiques
    ?>
    <div class="testimonials-grid">
      <div class="testimonial-card">
        <div class="testimonial-rating">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <p class="testimonial-text">« Le luminaire est magnifique, le montage était simple et le résultat dépasse mes attentes. Un vrai coup de cœur ! »</p>
        <div class="testimonial-author">
          <span class="author-name">Sophie M.</span>
          <span class="author-location">Lyon</span>
        </div>
      </div>

      <div class="testimonial-card">
        <div class="testimonial-rating">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <p class="testimonial-text">« La qualité du bois et des finitions est exceptionnelle. On sent le travail artisanal. Livraison rapide et soignée. »</p>
        <div class="testimonial-author">
          <span class="author-name">Thomas R.</span>
          <span class="author-location">Paris</span>
        </div>
      </div>

      <div class="testimonial-card">
        <div class="testimonial-rating">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <p class="testimonial-text">« J'adore les jeux d'ombres que projette ce luminaire. Robin a été très réactif pour répondre à mes questions. »</p>
        <div class="testimonial-author">
          <span class="author-name">Marie-Claire D.</span>
          <span class="author-location">Bordeaux</span>
        </div>
      </div>
    </div>

    <div class="testimonials-cta">
      <p>Vous avez ce produit ? <a href="#review_form_wrapper">Partagez votre avis</a></p>
    </div>
    <?php endif; ?>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 06 — GALERIE AMBIANCE
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-gallery-large">
    <?php
    $has_acf_images = false;

    if (function_exists('get_field')) {
      $acf_images = [
        get_field('ambiance_1'),
        get_field('ambiance_2'),
      ];

      $acf_images = array_filter($acf_images);

      if (!empty($acf_images)) {
        $has_acf_images = true;
        foreach ($acf_images as $img) {
          if (isset($img['url'])) {
            echo '<div class="gallery-large-item"><img src="' . esc_url($img['url']) . '" alt="' . esc_attr(get_the_title()) . ' - ambiance" loading="lazy"></div>';
          }
        }
      }
    }

    if (!$has_acf_images && !empty($gallery_ids)) {
      $lifestyle_images = array_slice($gallery_ids, 0, 2);
      foreach ($lifestyle_images as $img_id) {
        $img_url = wp_get_attachment_image_url($img_id, 'large');
        if ($img_url) {
          echo '<div class="gallery-large-item"><img src="' . esc_url($img_url) . '" alt="' . esc_attr(get_the_title()) . ' - ambiance" loading="lazy"></div>';
        }
      }
    }
    ?>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════
       SECTION 07 — FAQ
       ═══════════════════════════════════════════════════════════════ -->
  <section class="product-faq product-faq-cinetique">
    <div class="product-faq-header">
      <span class="section-number">06</span>
      <h2>Des Questions ?</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary><span class="faq-question">Quelle ampoule choisir ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Avec chaque modèle, nous vous recommandons une ampoule adaptée. Nous l'avons soigneusement choisie, et elle est disponible à l'achat comme accessoire.</p>
          <ul>
            <li>Puissance : 4-6W (équivalent 40-60W)</li>
            <li>Température : blanc chaud (2700K)</li>
            <li>Type : LED filament E27</li>
          </ul>
        </div>
      </details>
      <details class="faq-item">
        <summary><span class="faq-question">Comment se passe le montage ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Nos luminaires sont livrés pré-assemblés ou avec un guide d'assemblage clair et illustré. Le montage se fait en quelques minutes, sans outil spécial.</p>
        </div>
      </details>
      <details class="faq-item">
        <summary><span class="faq-question">Puis-je personnaliser mon luminaire ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Absolument ! Contactez-nous pour discuter de vos envies : dimensions sur-mesure, gravures personnalisées, finitions spéciales... Nous étudions chaque demande avec plaisir.</p>
        </div>
      </details>
      <details class="faq-item">
        <summary><span class="faq-question">Quels sont les délais de livraison ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Fabrication en moins de 5 jours ouvrés, puis expédition sous 48-72h. Vous recevez un email de suivi dès l'envoi de votre colis.</p>
        </div>
      </details>
    </div>
  </section>

  <!-- ═══════════════════════════════════════════════════════════════
       PRODUITS SIMILAIRES
       ═══════════════════════════════════════════════════════════════ -->
  <?php
  $related_products = wc_get_related_products($product_id, 4);
  if (!empty($related_products)) :
  ?>
  <section class="product-related">
    <div class="product-related-header">
      <h2>Vous aimerez aussi</h2>
    </div>
    <div class="products-grid products-grid-cinetique">
      <?php
      foreach ($related_products as $related_id) {
        $post_object = get_post($related_id);
        setup_postdata($GLOBALS['post'] = $post_object);
        wc_get_template_part('content', 'product');
      }
      wp_reset_postdata();
      ?>
    </div>
  </section>
  <?php endif; ?>

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
    let introRemoved = false;

    // Fade in the image after initial black fade
    setTimeout(function() {
      introScreen.classList.add('loaded');
    }, 300);

    // Scroll-based fade out
    function handleScroll() {
      if (introRemoved) return;

      const scrollY = window.scrollY || window.pageYOffset;
      const fadeDistance = 200; // Distance to scroll for complete fade

      // Calculate opacity based on scroll (1 at top, 0 at fadeDistance)
      const opacity = Math.max(0, 1 - (scrollY / fadeDistance));

      if (opacity <= 0) {
        // Completely scrolled, remove intro
        introScreen.style.opacity = '0';
        setTimeout(function() {
          if (!introRemoved) {
            introScreen.remove();
            introRemoved = true;
            window.removeEventListener('scroll', handleScroll);
          }
        }, 300);
      } else {
        introScreen.style.opacity = opacity;
      }
    }

    // Click to skip
    introScreen.addEventListener('click', function() {
      if (!introRemoved) {
        this.classList.add('fade-out');
        introRemoved = true;
        window.removeEventListener('scroll', handleScroll);
        setTimeout(function() {
          introScreen.remove();
        }, 600);
      }
    });

    // Listen to scroll after image loads
    setTimeout(function() {
      window.addEventListener('scroll', handleScroll);
    }, 800);
  }

  const stickyBar = document.getElementById('sticky-add-to-cart');
  const heroSection = document.querySelector('.product-hero-v2');

  if (!stickyBar || !heroSection) return;

  // Show/hide sticky bar based on scroll position
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      stickyBar.classList.toggle('is-visible', !entry.isIntersecting);
    });
  }, { threshold: 0, rootMargin: '-100px 0px 0px 0px' });

  observer.observe(heroSection);

  // Handle simple product add to cart (Safari compatible)
  const addBtn = stickyBar.querySelector('.sticky-add-btn');
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
          url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
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
  const scrollBtn = stickyBar.querySelector('.sticky-scroll-to-form');
  if (scrollBtn) {
    scrollBtn.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });

    // Update sticky bar and main gallery image when variation is selected
    const variationForm = document.querySelector('.variations_form');
    const mainImage = document.querySelector('.gallery-main-image');
    const galleryZoomLink = document.querySelector('.gallery-zoom');
    let originalImageSrc = mainImage ? mainImage.src : '';
    let originalImageFull = galleryZoomLink ? galleryZoomLink.href : '';

    if (variationForm && typeof jQuery !== 'undefined') {
      jQuery(variationForm).on('found_variation', function(event, variation) {
        // Update sticky bar price
        const priceEl = stickyBar.querySelector('.sticky-product-price');
        if (priceEl && variation.price_html) {
          priceEl.innerHTML = variation.price_html;
        }
        scrollBtn.textContent = '<?php esc_html_e('Ajouter au panier', 'theme-sapi-maison'); ?>';
        scrollBtn.classList.add('variation-selected');
        scrollBtn.addEventListener('click', function submitForm(e) {
          e.preventDefault();
          const mainForm = document.querySelector('.variations_form');
          if (mainForm) {
            mainForm.querySelector('.single_add_to_cart_button').click();
          }
        }, { once: true });

        // Update main gallery image with variation image
        if (variation.image && variation.image.src && mainImage) {
          mainImage.src = variation.image.src;
          mainImage.srcset = variation.image.srcset || '';
          mainImage.sizes = variation.image.sizes || '';
          mainImage.alt = variation.image.alt || '';
          mainImage.title = variation.image.title || '';

          // Update zoom link if exists
          if (galleryZoomLink && variation.image.full_src) {
            galleryZoomLink.href = variation.image.full_src;
          }
        }
      });

      jQuery(variationForm).on('reset_data', function() {
        scrollBtn.textContent = '<?php esc_html_e('Choisir les options', 'theme-sapi-maison'); ?>';
        scrollBtn.classList.remove('variation-selected');

        // Reset to original image
        if (mainImage && originalImageSrc) {
          mainImage.src = originalImageSrc;
          mainImage.srcset = '';
          if (galleryZoomLink && originalImageFull) {
            galleryZoomLink.href = originalImageFull;
          }
        }
      });
    }
  }

  // Gallery thumbnail switching
  const thumbnails = document.querySelectorAll('.gallery-thumb');
  const mainImage = document.querySelector('.gallery-main-image');

  thumbnails.forEach(thumb => {
    thumb.addEventListener('click', function() {
      thumbnails.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      if (mainImage) {
        mainImage.src = this.dataset.image;
      }
    });
  });

  // Mobile gallery navigation with arrows and swipe
  const galleryMain = document.querySelector('.gallery-main');
  const navPrev = document.querySelector('.gallery-nav-prev');
  const navNext = document.querySelector('.gallery-nav-next');
  const galleryZoomLink = document.querySelector('.gallery-zoom');

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

      // Update main image
      if (mainImage) {
        mainImage.src = targetThumb.dataset.image;
      }

      // Update zoom link if available
      if (galleryZoomLink) {
        galleryZoomLink.href = targetThumb.dataset.image;
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

      // Get form data for variable products
      const formData = variationForm ? jQuery(variationForm).serializeArray() : [];
      const ajaxData = {
        action: 'sapi_buy_now',
        product_id: productId,
        quantity: 1,
        nonce: '<?php echo wp_create_nonce('sapi-buy-now'); ?>'
      };

      // Add variation data if exists
      formData.forEach(item => {
        ajaxData[item.name] = item.value;
      });

      jQuery.ajax({
        type: 'POST',
        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
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
})();
</script>
<?php endif; ?>

<?php
get_footer();

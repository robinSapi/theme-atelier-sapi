<?php

defined('ABSPATH') || exit;

get_header();
?>

<?php while (have_posts()) : ?>
  <?php the_post(); ?>

  <?php
  // Force WooCommerce to setup product data for current post
  global $product;
  $product = wc_get_product(get_the_ID());

  // Safety check
  if (!$product) {
    continue;
  }
  ?>

  <?php do_action('woocommerce_before_single_product'); ?>

  <div id="product-<?php the_ID(); ?>" <?php wc_product_class('product-page-cinetique', $product); ?>>

  <?php sapi_maison_breadcrumbs(); ?>

  <section class="product-hero product-hero-cinetique">
    <div class="product-hero-grid">
      <div class="product-gallery">
        <?php do_action('woocommerce_before_single_product_summary'); ?>
      </div>
      <div class="product-summary">
        <?php
        $phrase = function_exists('get_field') ? get_field('phrase_daccroche') : '';
        // Get product category for section context
        $product_cats = get_the_terms(get_the_ID(), 'product_cat');
        $cat_name = $product_cats && !is_wp_error($product_cats) ? $product_cats[0]->name : 'Création';
        ?>
        <div class="product-summary-inner" id="product-summary-main">
          <div class="product-summary-header">
            <span class="section-number">01</span>
            <span class="product-category-label"><?php echo esc_html($cat_name); ?></span>
          </div>
          <?php do_action('woocommerce_single_product_summary'); ?>
          <?php if ($phrase) : ?>
            <p class="product-hookline"><?php echo esc_html($phrase); ?></p>
          <?php endif; ?>

          <!-- Delivery/Returns Info Block -->
          <div class="product-delivery-info">
            <div class="delivery-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              <span><strong>&lt;5j</strong> Fabrication</span>
            </div>
            <div class="delivery-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="3" width="15" height="13"/>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                <circle cx="5.5" cy="18.5" r="2.5"/>
                <circle cx="18.5" cy="18.5" r="2.5"/>
              </svg>
              <span><strong>48-72h</strong> Livraison</span>
            </div>
            <div class="delivery-item">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"/>
                <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
              </svg>
              <span><strong>30j</strong> Retours</span>
            </div>
          </div>
        </div>
        <div class="product-personalisation">
          <p><strong>Vous souhaitez une version colorée ? Une taille différente ? Une gravure personnalisée ?</strong></p>
          <p>Dites-nous vite ce que vous imaginez et nous vous ferons rapidement la meilleure des propositions !</p>
          <a class="button button-outline" href="mailto:contact@atelier-sapi.fr">Contactez-nous</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Reassurance Bar - Above the fold -->
  <section class="product-assurances product-assurances-cinetique product-assurances-above-fold">
    <div class="assurance-item">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/picto-assembly.svg'); ?>" alt="Assemblage guidé">
      <p>Assemblage guidé<br>et ludique</p>
    </div>
    <div class="assurance-item">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/picto-french.svg'); ?>" alt="Fabrication artisanale">
      <p>Fabrication artisanale<br>dans le Rhône</p>
    </div>
    <div class="assurance-item">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/picto-guarantee.svg'); ?>" alt="Design unique">
      <p>Design unique et<br>produits garantis</p>
    </div>
    <div class="assurance-item">
      <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/picto-pantone.svg'); ?>" alt="Adaptable">
      <p>Adaptable selon<br>vos envies</p>
    </div>
  </section>

  <!-- Section 02: Pourquoi cette pièce -->
  <section class="product-why product-why-cinetique">
    <div class="product-why-header">
      <span class="section-number">02</span>
      <h2>Pourquoi cette pièce ?</h2>
    </div>
    <div class="product-why-grid">
      <div class="product-why-content">
        <?php
        // Try ACF "pourquoi" field first, fallback to descriptif, then the_content()
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
          // Try ACF "usages" field, fallback to generic usages
          $usages = function_exists('get_field') ? get_field('usages') : null;
          if ($usages && is_array($usages)) {
            foreach ($usages as $usage) {
              echo '<li>' . esc_html($usage['usage']) . '</li>';
            }
          } else {
            // Default usages based on category
            $default_usages = [
              'Salon & séjour',
              'Chambre à coucher',
              'Bureau & espace de travail',
              'Entrée & couloir'
            ];
            foreach ($default_usages as $usage) {
              echo '<li>' . esc_html($usage) . '</li>';
            }
          }
          ?>
        </ul>
      </div>
    </div>
  </section>

  <!-- Section 03: Fiche technique -->
  <section class="product-specs product-specs-cinetique">
    <div class="product-specs-header">
      <span class="section-number">03</span>
      <h2>Fiche technique</h2>
    </div>
    <div class="product-specs-table">
      <?php
      // Get product attributes and ACF fields for specs
      $specs = [];

      // Dimensions (from ACF or attributes)
      if (function_exists('get_field')) {
        $dimensions = get_field('dimensions');
        $hauteur = get_field('hauteur');
        $largeur = get_field('largeur');
        $profondeur = get_field('profondeur');

        if ($dimensions) {
          $specs['Dimensions'] = $dimensions;
        } elseif ($hauteur || $largeur || $profondeur) {
          $dim_parts = [];
          if ($largeur) $dim_parts[] = 'L ' . $largeur;
          if ($profondeur) $dim_parts[] = 'P ' . $profondeur;
          if ($hauteur) $dim_parts[] = 'H ' . $hauteur;
          $specs['Dimensions'] = implode(' × ', $dim_parts);
        }

        // Other ACF specs
        $poids = get_field('poids');
        if ($poids) $specs['Poids'] = $poids;
      }

      // WooCommerce dimensions fallback
      if (empty($specs['Dimensions']) && $product) {
        $wc_dims = wc_format_dimensions($product->get_dimensions(false));
        if ($wc_dims && $wc_dims !== 'N/A') {
          $specs['Dimensions'] = $wc_dims;
        }
      }

      // Weight fallback
      if (empty($specs['Poids']) && $product && $product->get_weight()) {
        $specs['Poids'] = $product->get_weight() . ' kg';
      }

      // Fixed specs for luminaires
      $specs['Ampoule'] = 'E27 – LED filament recommandée';
      $specs['Câble'] = 'Textile noir, 90 cm max, pavillon métal';
      $specs['Montage'] = 'Instructions incluses, outils fournis';
      $specs['Matériau'] = 'Peuplier français certifié PEFC';
      $specs['Finition'] = 'Bois naturel, découpe laser précision';

      foreach ($specs as $label => $value) :
      ?>
        <div class="spec-row">
          <span class="spec-label"><?php echo esc_html($label); ?></span>
          <span class="spec-value"><?php echo esc_html($value); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Section 04: L'Atelier -->
  <section class="product-atelier product-atelier-cinetique">
    <div class="product-atelier-grid">
      <div class="product-atelier-image">
        <?php
        // Try ACF option field, then theme image, then product featured image
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
        <a href="<?php echo esc_url(home_url('/notre-histoire/')); ?>" class="button button-outline">Découvrir notre histoire</a>
      </div>
    </div>
  </section>

  <!-- Product gallery -->
  <section class="product-gallery-large">
    <?php
    $has_acf_images = false;

    // Try ACF fields first
    if (function_exists('get_field')) {
      $acf_images = [
        get_field('ambiance_1'),
        get_field('detail_1'),
        get_field('detail_2'),
        get_field('tailles'),
        get_field('ambiance_2_opt'),
        get_field('ambiance_3_opt'),
      ];
      foreach ($acf_images as $image) {
        if ($image && isset($image['url'])) {
          $has_acf_images = true;
          $alt = isset($image['alt']) ? $image['alt'] : get_the_title();
          echo '<img src="' . esc_url($image['url']) . '" alt="' . esc_attr($alt) . '" loading="lazy" />';
        }
      }
    }

    // Fallback to WooCommerce gallery if no ACF images
    if (!$has_acf_images) {
      global $product;
      $gallery_ids = $product ? $product->get_gallery_image_ids() : [];

      if (!empty($gallery_ids)) {
        foreach ($gallery_ids as $gallery_id) {
          $img_url = wp_get_attachment_image_url($gallery_id, 'large');
          $img_alt = get_post_meta($gallery_id, '_wp_attachment_image_alt', true) ?: get_the_title();
          if ($img_url) {
            echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" loading="lazy" />';
          }
        }
      }
    }
    ?>
  </section>

  <section class="product-faq product-faq-cinetique">
    <div class="product-faq-header">
      <span class="section-number">05</span>
      <h2>Des Questions ?</h2>
    </div>
    <div class="faq-list">
      <details class="faq-item">
        <summary><span class="faq-question">Quelle ampoule choisir ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Avec chaque modèle, nous vous recommandons une ampoule adaptée. Nous l'avons soigneusement choisie, et elle est disponible à l'achat comme accessoire.</p>
          <ul>
            <li>Des ampoules E27 : c'est le standard le plus adapté et le plus populaire.</li>
            <li>Filament LED : idéal pour créer les ombres uniques de nos luminaires.</li>
            <li>Taille et forme : elle dépend du modèle. Attention aux ampoules trop grandes.</li>
            <li>Couleur : blanc chaud (2700K), parfait pour mettre en valeur le bois.</li>
            <li>Puissance : entre 1000 et 1400 lm selon le modèle.</li>
          </ul>
        </div>
      </details>
      <details class="faq-item">
        <summary><span class="faq-question">Le montage du luminaire est-il compliqué ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Pas d'inquiétude, toutes les instructions sont dans le colis ! Montage simple et rapide.</p>
          <p>Besoin d'aide ? Écrivez-nous à contact@atelier-sapi.fr.</p>
        </div>
      </details>
      <details class="faq-item">
        <summary><span class="faq-question">Dans combien de temps vais-je recevoir mon luminaire ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Fabrication : moins de 5 jours.</p>
          <p>Expédition : 48 à 72h pour rejoindre votre nid douillet.</p>
        </div>
      </details>
      <details class="faq-item">
        <summary><span class="faq-question">Puis-je retourner mon luminaire ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Vous avez 30 jours après réception pour le retourner.</p>
          <p>Contactez-nous si besoin, puis renvoyez le produit dans son emballage d'origine.</p>
        </div>
      </details>
      <details class="faq-item">
        <summary><span class="faq-question">Le câble avec la douille est-il fourni ?</span><span class="faq-chevron"></span></summary>
        <div class="faq-answer">
          <p>Oui ! Câble textile noir, pavillon métal noir, douille E27. Longueur max 90 cm.</p>
          <p>Besoin d'un câble particulier ? Contactez-nous avant la commande.</p>
        </div>
      </details>
    </div>
  </section>

  <section class="product-related">
    <h2>Vous aimerez peut-être aussi</h2>
    <?php woocommerce_output_related_products(); ?>
  </section>

  </div><!-- /.product wrapper -->

  <!-- Sticky Add to Cart Bar -->
  <div class="sticky-add-to-cart" id="sticky-add-to-cart" aria-hidden="true" data-product-type="<?php echo esc_attr($product->get_type()); ?>">
    <div class="sticky-add-to-cart-inner">
      <div class="sticky-product-info">
        <span class="sticky-product-name"><?php the_title(); ?></span>
        <span class="sticky-product-price" id="sticky-price"><?php echo $product->get_price_html(); ?></span>
      </div>
      <div class="sticky-product-actions">
        <?php if ($product->is_in_stock() && $product->is_purchasable()) : ?>
          <?php if ($product->is_type('simple')) : ?>
            <!-- Simple product: direct add to cart -->
            <a href="<?php echo esc_url($product->add_to_cart_url()); ?>"
               class="button sticky-add-to-cart-btn ajax_add_to_cart"
               data-product_id="<?php echo esc_attr($product->get_id()); ?>"
               data-product_sku="<?php echo esc_attr($product->get_sku()); ?>"
               data-quantity="1">
              Ajouter au panier
            </a>
          <?php else : ?>
            <!-- Variable product: scroll to form for selection -->
            <button type="button" class="button sticky-add-to-cart-btn sticky-scroll-to-form" id="sticky-variable-btn">
              <span class="sticky-btn-text">Choisir les options</span>
            </button>
          <?php endif; ?>
        <?php else : ?>
          <span class="button button-disabled">Indisponible</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php do_action('woocommerce_after_single_product'); ?>

<?php endwhile; ?>

<script>
// Sticky add-to-cart: visibility, price sync, and variable product handling
(function() {
  const stickyBar = document.getElementById('sticky-add-to-cart');
  const productSummary = document.getElementById('product-summary-main');
  const stickyPrice = document.getElementById('sticky-price');
  const stickyVariableBtn = document.getElementById('sticky-variable-btn');
  const productType = stickyBar ? stickyBar.dataset.productType : 'simple';

  if (!stickyBar || !productSummary) return;

  // Visibility observer
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        stickyBar.classList.remove('is-visible');
        stickyBar.setAttribute('aria-hidden', 'true');
      } else {
        stickyBar.classList.add('is-visible');
        stickyBar.setAttribute('aria-hidden', 'false');
      }
    });
  }, { threshold: 0, rootMargin: '-100px 0px 0px 0px' });

  observer.observe(productSummary);

  // Variable products: sync price and button state
  if (productType === 'variable' && typeof jQuery !== 'undefined') {
    const $form = jQuery('.variations_form');
    const btnTextEl = stickyVariableBtn ? stickyVariableBtn.querySelector('.sticky-btn-text') : null;

    // Scroll to form when clicking sticky button
    if (stickyVariableBtn) {
      stickyVariableBtn.addEventListener('click', function() {
        const formSection = document.querySelector('.variations_form') || productSummary;
        if (formSection) {
          formSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
          // Focus first swatch/select after scroll
          setTimeout(() => {
            const firstInput = formSection.querySelector('.swatch-item, select');
            if (firstInput) firstInput.focus();
          }, 500);
        }
      });
    }

    // Listen for variation changes
    $form.on('found_variation', function(event, variation) {
      // Update sticky price with variation price
      if (stickyPrice && variation.price_html) {
        stickyPrice.innerHTML = variation.price_html;
      }

      // Update button text to "Ajouter au panier" when valid variation selected
      if (btnTextEl) {
        btnTextEl.textContent = 'Ajouter au panier';
        stickyVariableBtn.classList.add('variation-selected');
      }

      // Change button behavior to submit the main form
      if (stickyVariableBtn) {
        stickyVariableBtn.onclick = function(e) {
          e.preventDefault();
          const addBtn = document.querySelector('.single_add_to_cart_button');
          if (addBtn && !addBtn.disabled) {
            addBtn.click();
          }
        };
      }
    });

    // Reset when variation is cleared
    $form.on('reset_data', function() {
      // Restore original price
      const originalPrice = document.querySelector('.summary .price');
      if (stickyPrice && originalPrice) {
        stickyPrice.innerHTML = originalPrice.innerHTML;
      }

      // Reset button
      if (btnTextEl) {
        btnTextEl.textContent = 'Choisir les options';
        stickyVariableBtn.classList.remove('variation-selected');
      }

      // Reset button behavior
      if (stickyVariableBtn) {
        stickyVariableBtn.onclick = function() {
          const formSection = document.querySelector('.variations_form') || productSummary;
          if (formSection) {
            formSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        };
      }
    });
  }
})();
</script>

<?php
get_footer();

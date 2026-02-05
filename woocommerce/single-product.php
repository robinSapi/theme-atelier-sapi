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

  <div id="product-<?php the_ID(); ?>" <?php wc_product_class('', $product); ?>>

  <?php sapi_maison_breadcrumbs(); ?>

  <section class="product-hero">
    <div class="product-hero-grid">
      <div class="product-gallery">
        <?php do_action('woocommerce_before_single_product_summary'); ?>
      </div>
      <div class="product-summary">
        <?php
        $phrase = function_exists('get_field') ? get_field('phrase_daccroche') : '';
        ?>
        <div class="product-summary-inner" id="product-summary-main">
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

  <section class="product-details">
    <h2>Détails et conseils</h2>
    <div class="product-details-content">
      <?php
      if (function_exists('get_field')) {
        $descriptif = get_field('descriptif');
        if ($descriptif) {
          echo wp_kses_post($descriptif);
        } else {
          the_content();
        }
      } else {
        the_content();
      }
      ?>
    </div>
  </section>

  <section class="product-assurances">
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

  <section class="product-faq">
    <h2>Des Questions ?</h2>
    <div class="faq-list">
      <details>
        <summary>Quelle ampoule choisir ?</summary>
        <div>
          <p>Avec chaque modèle, nous vous recommandons une ampoule adaptée. Nous l’avons soigneusement choisie, et elle est disponible à l’achat comme accessoire.</p>
          <ul>
            <li>Des ampoules E27 : c’est le standard le plus adapté et le plus populaire.</li>
            <li>Filament LED : idéal pour créer les ombres uniques de nos luminaires.</li>
            <li>Taille et forme : elle dépend du modèle. Attention aux ampoules trop grandes.</li>
            <li>Couleur : blanc chaud (2700K), parfait pour mettre en valeur le bois.</li>
            <li>Puissance : entre 1000 et 1400 lm selon le modèle.</li>
          </ul>
        </div>
      </details>
      <details>
        <summary>Le montage du luminaire est-il compliqué ?</summary>
        <div>
          <p>Pas d’inquiétude, toutes les instructions sont dans le colis ! Montage simple et rapide.</p>
          <p>Besoin d’aide ? Écrivez-nous à contact@atelier-sapi.fr.</p>
        </div>
      </details>
      <details>
        <summary>Dans combien de temps vais-je recevoir mon luminaire ?</summary>
        <div>
          <p>Fabrication : moins de 5 jours.</p>
          <p>Expédition : 48 à 72h pour rejoindre votre nid douillet.</p>
        </div>
      </details>
      <details>
        <summary>Puis-je retourner mon luminaire ?</summary>
        <div>
          <p>Vous avez 30 jours après réception pour le retourner.</p>
          <p>Contactez-nous si besoin, puis renvoyez le produit dans son emballage d'origine.</p>
        </div>
      </details>
      <details>
        <summary>Le câble avec la douille est-il fourni ?</summary>
        <div>
          <p>Oui ! Câble textile noir, pavillon métal noir, douille E27. Longueur max 90 cm.</p>
          <p>Besoin d’un câble particulier ? Contactez-nous avant la commande.</p>
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
  <div class="sticky-add-to-cart" id="sticky-add-to-cart" aria-hidden="true">
    <div class="sticky-add-to-cart-inner">
      <div class="sticky-product-info">
        <span class="sticky-product-name"><?php the_title(); ?></span>
        <span class="sticky-product-price"><?php echo $product->get_price_html(); ?></span>
      </div>
      <div class="sticky-product-actions">
        <?php if ($product->is_in_stock() && $product->is_purchasable()) : ?>
          <a href="<?php echo esc_url($product->add_to_cart_url()); ?>"
             class="button sticky-add-to-cart-btn"
             data-product_id="<?php echo esc_attr($product->get_id()); ?>"
             data-quantity="1"
             <?php echo $product->is_type('simple') ? 'data-product_sku="' . esc_attr($product->get_sku()) . '"' : ''; ?>>
            Ajouter au panier
          </a>
        <?php else : ?>
          <span class="button button-disabled">Indisponible</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php do_action('woocommerce_after_single_product'); ?>

<?php endwhile; ?>

<script>
// Sticky add-to-cart visibility
(function() {
  const stickyBar = document.getElementById('sticky-add-to-cart');
  const productSummary = document.getElementById('product-summary-main');

  if (!stickyBar || !productSummary) return;

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
})();
</script>

<?php
get_footer();

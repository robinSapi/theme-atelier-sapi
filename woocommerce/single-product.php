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
        <div class="product-summary-inner">
          <?php do_action('woocommerce_single_product_summary'); ?>
          <?php if ($phrase) : ?>
            <p class="product-hookline"><?php echo esc_html($phrase); ?></p>
          <?php endif; ?>
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

  <?php do_action('woocommerce_after_single_product'); ?>

<?php endwhile; ?>

<?php
get_footer();

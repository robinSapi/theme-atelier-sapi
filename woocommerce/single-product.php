<?php

defined('ABSPATH') || exit;

get_header();
?>

<?php while (have_posts()) : ?>
  <?php the_post(); ?>

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
      <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-assembly.svg" alt="Assemblage guidé">
      <p>Assemblage guidé<br>et ludique</p>
    </div>
    <div class="assurance-item">
      <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-french.svg" alt="Fabrication artisanale">
      <p>Fabrication artisanale<br>dans le Rhône</p>
    </div>
    <div class="assurance-item">
      <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-guarantee.svg" alt="Design unique">
      <p>Design unique et<br>produits garantis</p>
    </div>
    <div class="assurance-item">
      <img src="https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/03/picto-pantone.svg" alt="Adaptable">
      <p>Adaptable selon<br>vos envies</p>
    </div>
  </section>

  <section class="product-gallery-large">
    <?php
    if (function_exists('get_field')) {
      $images = [
        get_field('ambiance_1'),
        get_field('detail_1'),
        get_field('detail_2'),
        get_field('tailles'),
        get_field('ambiance_2_opt'),
        get_field('ambiance_3_opt'),
      ];
      foreach ($images as $image) {
        if ($image && isset($image['url'])) {
          echo '<img src="' . esc_url($image['url']) . '" alt="" />';
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

<?php endwhile; ?>

<?php
get_footer();

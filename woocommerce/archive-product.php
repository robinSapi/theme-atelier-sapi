<?php
defined('ABSPATH') || exit;

get_header();
?>

<section class="shop-hero" style="background-image: url('https://atelier-sapi.fr/wp-content/uploads/2025/01/sapi_illus_creations.jpg');">
  <div class="shop-hero-inner">
    <div class="shop-hero-title">
      <span class="divider"></span>
      <h1>Nos luminaires</h1>
      <span class="divider"></span>
    </div>
    <p class="shop-hero-subtitle">Préparez vous à découvrir nos luminaires en bois uniques</p>
  </div>
</section>

<section class="shop-intro">
  <p><strong>Bienvenue au cœur de l'Atelier Sâpi</strong><br>
  Chaque luminaire naît d’une idée lumineuse, d’un croquis sur papier, puis prend vie grâce à la précision du laser et la chaleur du bois. Robin, le créateur, sélectionne lui-même les essences comme le peuplier ou l’okoumé, avant de les transformer avec soin dans son atelier lyonnais.</p>
  <p>Nos suspensions en bois, à la fois légères et expressives, sont conçues pour sublimer vos intérieurs, du salon à la chambre, en passant par l’entrée ou la cuisine. Inspirées par la nature et les formes organiques, nos créations mêlent savoir-faire artisanal, design poétique et fabrication raisonnée.</p>
</section>

<section class="shop-categories">
  <div class="shop-category-grid">
    <a class="shop-category" href="/nos-suspensions/" style="background-image: url('https://www.atelier-sapi.fr/wp-content/uploads/2025/05/IMG_8801-682x1024.jpg');">
      <span>Les suspensions</span>
    </a>
    <a class="shop-category" href="/nos-lampadaires/" style="background-image: url('https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Face-Allumee.jpg');">
      <span>Les lampadaires</span>
    </a>
    <a class="shop-category" href="/nos-appliques/" style="background-image: url('https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg');">
      <span>Les appliques</span>
    </a>
    <a class="shop-category" href="/nos-lampes-a-poser/" style="background-image: url('https://www.atelier-sapi.fr/wp-content/uploads/2025/09/IMG_9802.jpg');">
      <span>Les lampes à poser</span>
    </a>
    <a class="shop-category" href="/les-accessoires/">
      <span>Les accessoires</span>
    </a>
  </div>
  <div class="shop-cta-row">
    <a class="button" href="/nos-creations/carte-cadeau/">Carte cadeau 🎁</a>
    <a class="button button-outline" href="/contact/">Modèle personnalisé</a>
  </div>
</section>

<section class="shop-products">
  <?php
  // DEBUG - Check loop conditions
  $has_loop = woocommerce_product_loop();
  $total = wc_get_loop_prop('total');
  $has_posts = have_posts();
  ?>
  <div style="background: yellow; padding: 10px; border: 2px solid red; margin: 20px;">
    <strong>DEBUG ARCHIVE PAGE (archive-product.php):</strong><br>
    - woocommerce_product_loop(): <?php echo $has_loop ? 'TRUE' : 'FALSE'; ?><br>
    - wc_get_loop_prop('total'): <?php echo $total; ?><br>
    - have_posts(): <?php echo $has_posts ? 'TRUE' : 'FALSE'; ?>
  </div>

  <?php if (woocommerce_product_loop()) : ?>
    <div style="background: lightgreen; padding: 5px;">ENTERING PRODUCT LOOP</div>
    <?php woocommerce_product_loop_start(); ?>
    <?php if (wc_get_loop_prop('total')) : ?>
      <div style="background: lightblue; padding: 5px;">FOUND <?php echo wc_get_loop_prop('total'); ?> PRODUCTS</div>
      <?php while (have_posts()) : ?>
        <?php the_post(); ?>
        <div style="background: orange; padding: 5px;">LOADING PRODUCT TEMPLATE FOR: <?php the_title(); ?></div>
        <?php wc_get_template_part('content', 'product'); ?>
      <?php endwhile; ?>
    <?php else : ?>
      <div style="background: red; color: white; padding: 5px;">TOTAL IS ZERO OR FALSE</div>
    <?php endif; ?>
    <?php woocommerce_product_loop_end(); ?>
    <?php woocommerce_pagination(); ?>
  <?php else : ?>
    <div style="background: red; color: white; padding: 10px;">NO PRODUCT LOOP - woocommerce_product_loop() returned FALSE</div>
    <?php wc_no_products_found(); ?>
  <?php endif; ?>
</section>

<section class="shop-outro">
  <p class="shop-outro-text">Laissez-vous guider par la lumière, explorez nos collections pensées pour illuminer chaque pièce… autrement.</p>
</section>

<?php
get_footer();

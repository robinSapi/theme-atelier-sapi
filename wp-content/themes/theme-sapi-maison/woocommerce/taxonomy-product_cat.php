<?php

defined('ABSPATH') || exit;

get_header();

$term = get_queried_object();
$term_name = $term && isset($term->name) ? $term->name : '';
$term_slug = $term && isset($term->slug) ? $term->slug : '';
?>

<section class="shop-hero" style="background-image: url('https://test.atelier-sapi.fr/wordpress/wp-content/uploads/2025/01/sapi_illus_creations.jpg');">
  <div class="shop-hero-inner">
    <div class="shop-hero-title">
      <span class="divider"></span>
      <h1><?php echo esc_html($term_name ? $term_name : 'Nos créations'); ?></h1>
      <span class="divider"></span>
    </div>
  </div>
</section>

<?php
if ($term_slug === 'suspensions') :
  $featured = [
    [
      'id' => 'arthus',
      'title' => 'Arthus Le lotus',
      'subtitle' => 'Prêt à laisser Arthus être le centre de l’attention ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Arthus-Bandeau-2.png',
    ],
    [
      'id' => 'olivia',
      'title' => 'Olivia La gardiena',
      'subtitle' => 'Votre intérieur tombera sous le charme d’Olivia',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Olivia-Bandeau-2.png',
    ],
    [
      'id' => 'suze',
      'title' => 'Suze La méduse',
      'subtitle' => 'Sans bouger, plongez dans les abysses de Suze la Méduse',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Suze-Bandeau-1.png',
    ],
    [
      'id' => 'vincent',
      'title' => "Vincent L'incandescent",
      'subtitle' => 'Prêt à laisser Vincent illuminer vos journées (et vos nuits) ? ',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Vincent-Bandeau-2.png',
    ],
    [
      'id' => 'alban',
      'title' => 'Alban Le virevoltant',
      'subtitle' => 'Alban, notre virevoltant s’invite chez vous pour mettre l’ambiance !',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Alban-Bandeau-2.png',
    ],
    [
      'id' => 'irene',
      'title' => 'Irène La reine',
      'subtitle' => 'Prêt à couronner Irène, reine de votre intérieur ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Irene-Bandeau-3-scaled.png',
    ],
    [
      'id' => 'dalida',
      'title' => 'Dalida Le dahlia',
      'subtitle' => 'Et si Dalida enflammait votre déco ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Dalida-Bandeau-1.png',
    ],
    [
      'id' => 'gaston',
      'title' => 'Gaston Le chardon',
      'subtitle' => 'Et si Gaston était le luminaire de votre vie ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Gaston-Bandeau-2.png',
    ],
  ];
?>
  <section class="category-featured">
    <?php foreach ($featured as $item) : ?>
      <article id="<?php echo esc_attr($item['id']); ?>" class="category-featured-card">
        <h2><?php echo esc_html($item['title']); ?></h2>
        <p class="category-featured-subtitle"><?php echo esc_html($item['subtitle']); ?></p>
        <div class="category-featured-media">
          <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
        </div>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<section class="shop-products">
  <?php if (woocommerce_product_loop()) : ?>
    <?php woocommerce_product_loop_start(); ?>
    <?php if (wc_get_loop_prop('total')) : ?>
      <?php while (have_posts()) : ?>
        <?php the_post(); ?>
        <?php wc_get_template_part('content', 'product'); ?>
      <?php endwhile; ?>
    <?php endif; ?>
    <?php woocommerce_product_loop_end(); ?>
    <?php woocommerce_pagination(); ?>
  <?php else : ?>
    <?php wc_no_products_found(); ?>
  <?php endif; ?>
</section>

<?php
get_footer();

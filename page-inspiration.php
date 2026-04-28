<?php
/*
Template Name: Galerie Inspiration
*/
get_header();

// Collecte des photos ambiance + detail sur tous les produits publiés.
// Volume contrôlé (~24 produits, marge à 200) — la règle anti -1 vise les
// requêtes catalogues massives, pas les pages galerie au volume borné.
$products_query = new WP_Query([
  'post_type'      => 'product',
  'post_status'    => 'publish',
  'posts_per_page' => 200,
  'fields'         => 'ids',
  'no_found_rows'  => true,
  'tax_query'      => [
    [
      'taxonomy' => 'product_cat',
      'field'    => 'slug',
      'terms'    => ['suspensions', 'appliques', 'lampadaires', 'lampesaposer'],
    ],
  ],
]);

$photos = [];

if ($products_query->have_posts() && function_exists('get_field')) {
  foreach ($products_query->posts as $product_id) {
    $galerie = get_field('galerie_produit', $product_id);
    if (empty($galerie) || !is_array($galerie)) continue;
    foreach ($galerie as $row) {
      $type = isset($row['type_photo']) ? $row['type_photo'] : '';
      if (is_array($type)) $type = isset($type['value']) ? $type['value'] : '';
      if ($type !== 'ambiance' && $type !== 'detail') continue;
      $img_id = sapi_get_acf_image_id(isset($row['image']) ? $row['image'] : null);
      if (!$img_id) continue;
      $photos[] = [
        'attachment_id' => $img_id,
        'product_id'    => $product_id,
      ];
    }
  }
}

shuffle($photos);

// Équivalent PHP de product-name-formatter.js (split premier mot / reste).
// Le rendu serveur évite le FOUC ; le formatter JS détecte .product-firstname
// et n'agit pas si déjà présent.
if (!function_exists('inspiration_format_product_name')) {
  function inspiration_format_product_name($name) {
    $name = trim($name);
    if ($name === '') return '';
    $words = preg_split('/\s+/', $name, 2);
    if (count($words) < 2) {
      return '<span class="product-firstname">' . esc_html($name) . '</span>';
    }
    return '<span class="product-firstname">' . esc_html($words[0]) . '</span> <span class="product-restname">' . esc_html($words[1]) . '</span>';
  }
}
?>

<section class="inspiration-intro">
  <h1 class="inspiration-title"><?php the_title(); ?></h1>
  <?php
  if (have_posts()) :
    while (have_posts()) : the_post();
      $page_content = trim(get_the_content());
      if ($page_content !== '') : ?>
        <div class="inspiration-content"><?php the_content(); ?></div>
      <?php endif;
    endwhile;
  endif;
  ?>
</section>

<section class="inspiration-gallery" aria-label="<?php echo esc_attr__('Galerie inspiration', 'sapi-maison'); ?>">
  <?php if (empty($photos)) : ?>
    <p class="inspiration-empty">Aucune image à afficher pour le moment.</p>
  <?php else : ?>
    <?php foreach ($photos as $i => $photo) :
      $product_id   = $photo['product_id'];
      $img_id       = $photo['attachment_id'];
      $product_name = get_the_title($product_id);
      $product_url  = get_permalink($product_id);
      $alt          = get_post_meta($img_id, '_wp_attachment_image_alt', true);
      if ($alt === '') $alt = $product_name;

      $img_attrs = [
        'alt'      => $alt,
        'class'    => 'inspiration-tile-img',
        'loading'  => $i < 6 ? 'eager' : 'lazy',
        'decoding' => 'async',
      ];
      if ($i === 0) {
        $img_attrs['fetchpriority'] = 'high';
      }
    ?>
      <a href="<?php echo esc_url($product_url); ?>" class="inspiration-tile" aria-label="<?php echo esc_attr($product_name); ?>">
        <?php echo wp_get_attachment_image($img_id, 'large', false, $img_attrs); ?>
        <span class="inspiration-tile-overlay" aria-hidden="true">
          <span class="inspiration-tile-name"><?php echo inspiration_format_product_name($product_name); ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</section>

<?php
wp_reset_postdata();
get_footer();

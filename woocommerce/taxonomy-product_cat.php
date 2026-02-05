<?php

defined('ABSPATH') || exit;

get_header();

$term = get_queried_object();
$term_name = $term && isset($term->name) ? $term->name : '';
$term_slug = $term && isset($term->slug) ? $term->slug : '';

$category_intro = [
  'suspension' => "Retrouvez ici tous nos lustres, prêts à faire rayonner votre déco intérieure !",
  'lampadaire' => "Posés au sol et bien branchés, des lampadaires prêts à illuminer chez vous !",
  'applique' => "Nos appliques, un mix idéal entre éclairages et déco pour vos murs !",
  'lampe-a-poser' => "Lampes de chevet, de bureau ou de salon, c'est ici que ça se passe !",
  'accessoire' => "Ampoules, douilles et pied de lampadaire, retrouvez ici de quoi parfaire votre éclairage !",
];

sapi_maison_breadcrumbs();
?>

<section class="shop-hero-cinetique">
  <span class="section-number">01</span>
  <h1><?php echo esc_html($term_name ? $term_name : 'Nos créations'); ?></h1>
  <?php if (isset($category_intro[$term_slug])) : ?>
    <p class="shop-subtitle"><?php echo esc_html($category_intro[$term_slug]); ?></p>
  <?php endif; ?>
</section>

<?php
if ($term_slug === 'suspension') :
  $featured = [
    [
      'id' => 'timothee',
      'title' => "Timothée L'araignée",
      'subtitle' => "La seule qu'on veut bien voir descendre du plafond 🕷️",
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/10/Bandeau.jpg',
      'link' => '/produit/timothee-laraignee/',
    ],
    [
      'id' => 'arthus',
      'title' => 'Arthus Le lotus',
      'subtitle' => 'Prêt à laisser Arthus être le centre de l’attention ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Arthus-Bandeau-2.png',
      'link' => '/produit/arthus-le-lotus/',
    ],
    [
      'id' => 'olivia',
      'title' => 'Olivia La gardiena',
      'subtitle' => 'Votre intérieur tombera sous le charme d’Olivia',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Olivia-Bandeau-2.png',
      'link' => '/produit/olivia-la-gardiena/',
    ],
    [
      'id' => 'suze',
      'title' => 'Suze La méduse',
      'subtitle' => 'Sans bouger, plongez dans les abysses de Suze la Méduse',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Suze-Bandeau-1.png',
      'link' => '/produit/suze-la-meduse/',
    ],
    [
      'id' => 'vincent',
      'title' => "Vincent L'incandescent",
      'subtitle' => 'Prêt à laisser Vincent illuminer vos journées (et vos nuits) ? ',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Vincent-Bandeau-2.png',
      'link' => '/produit/vincent-lincandescent/',
    ],
    [
      'id' => 'alban',
      'title' => 'Alban Le virevoltant',
      'subtitle' => 'Alban, notre virevoltant s’invite chez vous pour mettre l’ambiance !',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Alban-Bandeau-2.png',
      'link' => '/produit/alban-le-virevoltant/',
    ],
    [
      'id' => 'irene',
      'title' => 'Irène La reine',
      'subtitle' => 'Prêt à couronner Irène, reine de votre intérieur ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Irene-Bandeau-3-scaled.png',
      'link' => '/produit/irene-la-reine/',
    ],
    [
      'id' => 'dalida',
      'title' => 'Dalida Le dahlia',
      'subtitle' => 'Et si Dalida enflammait votre déco ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Dalida-Bandeau-1-1.jpg',
      'link' => '/produit/dalida-le-dahlia/',
    ],
    [
      'id' => 'gaston',
      'title' => 'Gaston Le chardon',
      'subtitle' => 'Et si Gaston était le luminaire de votre vie ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Gaston-Bandeau-2.png',
      'link' => '/produit/gaston-le-chardon/',
    ],
  ];

elseif ($term_slug === 'lampadaire') :
  $featured = [
    [
      'id' => 'claudine',
      'title' => 'Claudine La turbine',
      'subtitle' => 'Etes vous prêts pour un vent de fraicheur ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Claudine-bandeau-1.jpg',
      'link' => '/produit/claudine-la-turbine/',
    ],
    [
      'id' => 'charlie',
      'title' => 'Charlie Le pissenlit',
      'subtitle' => "Nous l'aimons un peu, beaucoup, passionnément !",
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Charlie-Bandeau-2.jpg',
      'link' => '/produit/charlie-le-pissenlit/',
    ],
    [
      'id' => 'vincent',
      'title' => "Vincent L'incandescent",
      'subtitle' => 'Prêt à laisser Vincent illuminer vos journées (et vos nuits) ? ',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/09/Bandeau-2.jpg',
      'link' => '/produit/vincent-lincandescent-3/',
    ],
    [
      'id' => 'dalida',
      'title' => 'Dalida La dahlia',
      'subtitle' => 'Et si Dalida enflammait votre déco ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Dalida-Bandeau-1.jpg',
      'link' => '/produit/dalida-le-dahlia-lamp/',
    ],
    [
      'id' => 'olivia',
      'title' => 'Olivia La gardiena',
      'subtitle' => 'Votre intérieur tombera sous le charme d’Olivia',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Olivia-Bandeau-1.jpg',
      'link' => '/produit/olivia-la-gardiena-lamp/',
    ],
  ];

elseif ($term_slug === 'applique') :
  $featured = [
    [
      'id' => 'charlie',
      'title' => 'Charlie Le pissenlit',
      'subtitle' => 'Où est Charlie ? Bientôt chez vous ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/09/3quarts-2.jpg',
      'link' => '/produit/charlie-le-pissenlit-2/',
    ],
    [
      'id' => 'claudine',
      'title' => 'Claudine La turbine',
      'subtitle' => 'Êtes-vous prêts pour un vent de fraîcheur ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/09/IMG_6216.jpg',
      'link' => '/produit/claudine-la-turbine-2/',
    ],
    [
      'id' => 'olivia',
      'title' => 'Olivia La gardiena',
      'subtitle' => "Votre intérieur tombera sous le charme d'Olivia",
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/09/3quarts-3.jpg',
      'link' => '/produit/olivia-la-gardiena-2/',
    ],
    [
      'id' => 'dalida',
      'title' => 'Dalida La dahlia',
      'subtitle' => 'Et si Dalida enflammait votre déco ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/09/Detail-2.jpg',
      'link' => '/produit/dalida-le-dahlia-2/',
    ],
  ];

elseif ($term_slug === 'lampe-a-poser') :
  $featured = [
    [
      'id' => 'bertrand',
      'title' => 'Bertrand Le volcan',
      'subtitle' => 'Une explosion de douceur',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/09/Bandeau.jpg',
      'link' => '/produit/bertrand-le-volcan/',
    ],
    [
      'id' => 'vincent',
      'title' => "Vincent L'incandescent",
      'subtitle' => 'Prêt à laisser Vincent illuminer vos soirées ?',
      'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/09/Bandeau-1.jpg',
      'link' => '/produit/vincent-lincandescent-2/',
    ],
  ];
endif;

if (!empty($featured)) :
?>
  <section class="category-featured">
    <?php foreach ($featured as $item) :
      // Get product image dynamically from WooCommerce
      $product_slug = basename(untrailingslashit($item['link']));
      $product_obj = get_page_by_path($product_slug, OBJECT, 'product');
      $product_image = '';

      if ($product_obj) {
        $product_image = get_the_post_thumbnail_url($product_obj->ID, 'large');
      }

      // Fallback to hardcoded image if product not found
      if (!$product_image && !empty($item['image'])) {
        $product_image = $item['image'];
      }

      // Skip if no image at all
      if (!$product_image) continue;
    ?>
      <article id="<?php echo esc_attr($item['id']); ?>" class="category-featured-card">
        <a class="category-featured-link" href="<?php echo esc_url($item['link']); ?>">
          <div class="category-featured-media">
            <img src="<?php echo esc_url($product_image); ?>" alt="<?php echo esc_attr($item['title']); ?>" loading="lazy">
          </div>
          <div class="category-featured-content">
            <h2><?php echo esc_html($item['title']); ?></h2>
            <p class="category-featured-subtitle"><?php echo esc_html($item['subtitle']); ?></p>
            <span class="category-featured-cta"><?php esc_html_e('Découvrir', 'theme-sapi-maison'); ?> →</span>
          </div>
        </a>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<?php
// Get ALL products in this category for the carousel (no pagination)
$term = get_queried_object();
$products_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => -1,
  'tax_query' => [
    [
      'taxonomy' => 'product_cat',
      'field' => 'term_id',
      'terms' => $term->term_id,
    ],
  ],
  'orderby' => 'menu_order date',
  'order' => 'ASC',
]);

if ($products_query->have_posts()) :
?>
<section class="shop-products">
  <div class="products-carousel-wrapper">
    <div class="products-carousel" data-products-carousel>
      <ul class="products-carousel-track products">
        <?php while ($products_query->have_posts()) : $products_query->the_post(); ?>
          <?php
          global $product;
          $product = wc_get_product(get_the_ID());
          ?>
          <li class="products-carousel-slide">
            <?php wc_get_template_part('content', 'product'); ?>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>
    <div class="products-carousel-controls">
      <button class="carousel-btn products-carousel-prev" aria-label="<?php esc_attr_e('Précédent', 'theme-sapi-maison'); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>
      <div class="products-carousel-dots"></div>
      <button class="carousel-btn products-carousel-next" aria-label="<?php esc_attr_e('Suivant', 'theme-sapi-maison'); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>
  </div>
</section>
<?php
wp_reset_postdata();
else :
  wc_no_products_found();
endif;
?>

<?php
get_footer();

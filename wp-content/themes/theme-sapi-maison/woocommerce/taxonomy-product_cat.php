<?php

defined('ABSPATH') || exit;

get_header();

$term = get_queried_object();
$term_name = $term && isset($term->name) ? $term->name : '';
$term_slug = $term && isset($term->slug) ? $term->slug : '';

$category_intro = [
  'suspensions' => "Retrouvez ici tout nos lustres, prêts à faire rayonner votre déco intérieure !",
  'lampadaires' => "Posés au sol et bien branchés, des lampadaires prêts à illuminer chez vous !",
  'appliques' => "Nos appliques, un mix idéal entre éclairages et déco pour vos murs !",
  'lampes-a-poser' => "Lampes de chevet, de bureau ou de salon, c'est ici que ça se passe !",
  'accessoires' => "Ampoules, douilles et pied de lampadaire, retrouvez ici de quoi parfaire votre éclairage !",
];
?>

<section class="shop-hero" style="background-image: url('<?php echo esc_url(get_site_url()); ?>/wp-content/uploads/2025/01/sapi_illus_creations.jpg');">
  <div class="shop-hero-inner">
    <div class="shop-hero-title">
      <span class="divider"></span>
      <h1><?php echo esc_html($term_name ? $term_name : 'Nos créations'); ?></h1>
      <span class="divider"></span>
    </div>
  </div>
</section>

<?php if (isset($category_intro[$term_slug])) : ?>
  <section class="category-intro">
    <p><?php echo esc_html($category_intro[$term_slug]); ?></p>
  </section>
<?php endif; ?>

<?php
if ($term_slug === 'suspensions') :
  $featured = [
    [
      'id' => 'timothee',
      'title' => "Timothée L'araignée",
      'subtitle' => "La seule qu'on veut bien voir descendre du plafond 🕷️",
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/10/Bandeau.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/timothee-laraignee/',
    ],
    [
      'id' => 'arthus',
      'title' => 'Arthus Le lotus',
      'subtitle' => 'Prêt à laisser Arthus être le centre de l’attention ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Arthus-Bandeau-2.png',
      'link' => 'https://atelier-sapi.fr/nos-creations/arthus-le-lotus/',
    ],
    [
      'id' => 'olivia',
      'title' => 'Olivia La gardiena',
      'subtitle' => 'Votre intérieur tombera sous le charme d’Olivia',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Olivia-Bandeau-2.png',
      'link' => 'https://atelier-sapi.fr/nos-creations/olivia-la-gardiena/',
    ],
    [
      'id' => 'suze',
      'title' => 'Suze La méduse',
      'subtitle' => 'Sans bouger, plongez dans les abysses de Suze la Méduse',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Suze-Bandeau-1.png',
      'link' => 'https://atelier-sapi.fr/nos-creations/suze-la-meduse/',
    ],
    [
      'id' => 'vincent',
      'title' => "Vincent L'incandescent",
      'subtitle' => 'Prêt à laisser Vincent illuminer vos journées (et vos nuits) ? ',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Vincent-Bandeau-2.png',
      'link' => 'https://atelier-sapi.fr/nos-creations/vincent-lincandescent/',
    ],
    [
      'id' => 'alban',
      'title' => 'Alban Le virevoltant',
      'subtitle' => 'Alban, notre virevoltant s’invite chez vous pour mettre l’ambiance !',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Alban-Bandeau-2.png',
      'link' => 'https://atelier-sapi.fr/nos-creations/alban-le-virevoltant/',
    ],
    [
      'id' => 'irene',
      'title' => 'Irène La reine',
      'subtitle' => 'Prêt à couronner Irène, reine de votre intérieur ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Irene-Bandeau-3-scaled.png',
      'link' => 'https://atelier-sapi.fr/nos-creations/irene-la-reine/',
    ],
    [
      'id' => 'dalida',
      'title' => 'Dalida Le dahlia',
      'subtitle' => 'Et si Dalida enflammait votre déco ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Dalida-Bandeau-1-1.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/dalida-le-dahlia/',
    ],
    [
      'id' => 'gaston',
      'title' => 'Gaston Le chardon',
      'subtitle' => 'Et si Gaston était le luminaire de votre vie ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Gaston-Bandeau-2.png',
      'link' => 'https://atelier-sapi.fr/nos-creations/gaston-le-chardon/',
    ],
  ];

elseif ($term_slug === 'lampadaires') :
  $featured = [
    [
      'id' => 'claudine',
      'title' => 'Claudine La turbine',
      'subtitle' => 'Etes vous prêts pour un vent de fraicheur ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Claudine-bandeau-1.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/claudine-la-turbine/',
    ],
    [
      'id' => 'charlie',
      'title' => 'Charlie Le pissenlit',
      'subtitle' => "Nous l'aimons un peu, beaucoup, passionnément !",
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Charlie-Bandeau-2.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/charlie-le-pissenlit/',
    ],
    [
      'id' => 'vincent',
      'title' => "Vincent L'incandescent",
      'subtitle' => 'Prêt à laisser Vincent illuminer vos journées (et vos nuits) ? ',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/Bandeau-2.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/vincent-lincandescent-3/',
    ],
    [
      'id' => 'dalida',
      'title' => 'Dalida La dahlia',
      'subtitle' => 'Et si Dalida enflammait votre déco ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Dalida-Bandeau-1.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/dalida-le-dahlia-lamp/',
    ],
    [
      'id' => 'olivia',
      'title' => 'Olivia La gardiena',
      'subtitle' => 'Votre intérieur tombera sous le charme d’Olivia',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/07/Olivia-Bandeau-1.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/olivia-la-gardiena-lamp/',
    ],
  ];

elseif ($term_slug === 'appliques') :
  $featured = [
    [
      'id' => 'charlie',
      'title' => 'Charlie Le pissenlit',
      'subtitle' => 'Où est Charlie ? Bientôt chez vous ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/3quarts-2.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/charlie-le-pissenlit-2/',
    ],
    [
      'id' => 'claudine',
      'title' => 'Claudine La turbine',
      'subtitle' => 'Êtes-vous prêts pour un vent de fraîcheur ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/IMG_6216.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/claudine-la-turbine-2/',
    ],
    [
      'id' => 'olivia',
      'title' => 'Olivia La gardiena',
      'subtitle' => "Votre intérieur tombera sous le charme d'Olivia",
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/3quarts-3.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/olivia-la-gardiena-2/',
    ],
    [
      'id' => 'dalida',
      'title' => 'Dalida La dahlia',
      'subtitle' => 'Et si Dalida enflammait votre déco ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/Detail-2.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/dalida-le-dahlia-2/',
    ],
  ];

elseif ($term_slug === 'lampes-a-poser') :
  $featured = [
    [
      'id' => 'bertrand',
      'title' => 'Bertrand Le volcan',
      'subtitle' => 'Une explosion de douceur',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/Bandeau.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/bertrand-le-volcan/',
    ],
    [
      'id' => 'vincent',
      'title' => "Vincent L'incandescent",
      'subtitle' => 'Prêt à laisser Vincent illuminer vos soirées ?',
      'image' => 'https://www.atelier-sapi.fr/wp-content/uploads/2025/09/Bandeau-1.jpg',
      'link' => 'https://atelier-sapi.fr/nos-creations/vincent-lincandescent-2/',
    ],
  ];
endif;

if (!empty($featured)) :
?>
  <section class="category-featured">
    <?php foreach ($featured as $item) : ?>
      <article id="<?php echo esc_attr($item['id']); ?>" class="category-featured-card">
        <h2><?php echo esc_html($item['title']); ?></h2>
        <p class="category-featured-subtitle"><?php echo esc_html($item['subtitle']); ?></p>
        <a class="category-featured-link" href="<?php echo esc_url($item['link']); ?>">
          <div class="category-featured-media">
            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title']); ?>">
          </div>
        </a>
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

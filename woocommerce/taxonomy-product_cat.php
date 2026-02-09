<?php
/**
 * Product Category Archive Template
 *
 * @package Sapi-Maison
 * @version 9.5.1
 */

defined('ABSPATH') || exit;

get_header();

$term = get_queried_object();

// Ensure $term is valid
if (!$term || !is_a($term, 'WP_Term')) {
  get_footer();
  return;
}

$term_name = $term->name;
$term_slug = $term->slug;
$term_id = $term->term_id;

$category_intro = [
  'suspension' => "Retrouvez ici tous nos lustres, prêts à faire rayonner votre déco intérieure !",
  'lampadaire' => "Posés au sol et bien branchés, des lampadaires prêts à illuminer chez vous !",
  'applique' => "Nos appliques, un mix idéal entre éclairages et déco pour vos murs !",
  'lampe-a-poser' => "Lampes de chevet, de bureau ou de salon, c'est ici que ça se passe !",
  'accessoire' => "Ampoules, douilles et pied de lampadaire, retrouvez ici de quoi parfaire votre éclairage !",
];

if (function_exists('sapi_maison_breadcrumbs')) {
  sapi_maison_breadcrumbs();
}
?>

<section class="shop-hero-cinetique">
  <span class="section-number">01</span>
  <h1><?php echo esc_html($term_name ? $term_name : 'Nos créations'); ?></h1>
  <?php if (isset($category_intro[$term_slug])) : ?>
    <p class="shop-subtitle"><?php echo esc_html($category_intro[$term_slug]); ?></p>
  <?php endif; ?>
</section>

<!-- Rich Editorial Content Section -->
<section class="category-editorial" data-particles="wood">
  <div class="category-editorial-inner">
    <?php
    // Rich editorial content per category
    $editorial_content = [
      'suspension' => [
        'tagline' => 'La lumière qui vous ressemble',
        'intro' => 'Une suspension, ce n\'est pas juste un luminaire accroché au plafond. C\'est le point focal de votre pièce, celle qui attire le regard dès qu\'on franchit la porte. C\'est l\'élément qui transforme un espace ordinaire en un lieu unique, chaleureux, où on a envie de vivre.',
        'why' => 'Pourquoi choisir une suspension en bois ?',
        'why_content' => 'Le bois apporte une chaleur incomparable. Il filtre la lumière avec douceur, créant des jeux d\'ombres poétiques sur vos murs. Nos suspensions ne sont pas de simples objets décoratifs : elles racontent une histoire, celle de l\'artisanat français, du savoir-faire et de la passion.',
        'promise' => 'Notre promesse',
        'promise_content' => 'Chaque suspension est unique. Découpée au laser dans notre atelier lyonnais, assemblée à la main, testée avec soin. Vous recevez bien plus qu\'un luminaire : vous recevez une pièce d\'art fonctionnelle, pensée pour durer, conçue pour sublimer votre quotidien.',
        'use_cases' => 'Où installer votre suspension ?',
        'use_cases_items' => [
          'Au-dessus de la table à manger → Pour créer une ambiance conviviale lors des repas',
          'Dans le salon → Comme pièce maîtresse qui structure l\'espace',
          'Dans la chambre → Pour une atmosphère apaisante et enveloppante',
          'Au-dessus d\'un îlot central → Design et fonctionnel pour cuisiner avec style'
        ]
      ],
      'lampadaire' => [
        'tagline' => 'L\'élégance posée',
        'intro' => 'Un lampadaire, c\'est la liberté d\'éclairer sans contrainte. Pas de trou à percer, pas de plafond trop bas. Juste poser, brancher, et profiter. C\'est l\'allié parfait pour structurer un coin lecture, réchauffer un angle froid, ou simplement ajouter une touche de caractère.',
        'why' => 'Pourquoi un lampadaire fait la différence ?',
        'why_content' => 'Contrairement à un plafonnier, le lampadaire crée une lumière d\'ambiance, plus douce, plus humaine. Il se fond dans votre décoration tout en affirmant son caractère. Nos modèles en bois sculptent l\'espace avec poésie, entre ombre et lumière.',
        'promise' => 'Notre engagement',
        'promise_content' => 'Stabilité, robustesse, élégance. Nos lampadaires sont conçus pour être beaux ET pratiques. Base lestée pour éviter tout basculement, bois PEFC pour une démarche responsable, assemblage précis pour une finition irréprochable.',
        'use_cases' => 'Dans quelles pièces l\'utiliser ?',
        'use_cases_items' => [
          'Salon → À côté du canapé pour lire confortablement',
          'Bureau → Éclairage indirect qui évite la fatigue visuelle',
          'Chambre → Ambiance tamisée pour les soirées cocooning',
          'Entrée → Premier coup d\'œil chaleureux en rentrant chez soi'
        ]
      ],
      'applique' => [
        'tagline' => 'La lumière qui habille vos murs',
        'intro' => 'Une applique murale, c\'est l\'art de libérer de l\'espace au sol tout en créant une atmosphère unique. Elle sculpte vos murs avec sa lumière, dessine des ombres douces, et transforme un pan de mur banal en élément décoratif à part entière.',
        'why' => 'Pourquoi choisir une applique ?',
        'why_content' => 'Pratiques et élégantes, les appliques sont parfaites pour les petits espaces ou pour créer un éclairage d\'appoint. Nos modèles en bois diffusent une lumière chaleureuse et tamisée, idéale pour les moments de détente.',
        'promise' => 'Ce qui fait notre différence',
        'promise_content' => 'Installation simplifiée, câblage textile de qualité, fixations fournies. Chaque applique est testée et emballée avec soin. Vous recevez un luminaire prêt à poser, avec notice illustrée claire et assistance disponible si besoin.',
        'use_cases' => 'Où installer vos appliques ?',
        'use_cases_items' => [
          'De chaque côté du lit → Pour lire sans déranger l\'autre',
          'Dans le couloir → Lumière douce qui guide sans éblouir',
          'Salle de bain → Éclairage d\'ambiance autour du miroir',
          'Escalier → Sécurité + décoration en un seul geste'
        ]
      ],
      'lampe-a-poser' => [
        'tagline' => 'La touche finale qui change tout',
        'intro' => 'Une lampe à poser, c\'est la liberté totale. Sur une table de chevet, un bureau, une étagère... Elle s\'installe partout, se déplace au gré de vos envies, et crée instantanément une bulle de lumière intime et chaleureuse.',
        'why' => 'Pourquoi craquer pour une lampe à poser ?',
        'why_content' => 'Compacte, mobile, polyvalente. La lampe à poser est le luminaire qui s\'adapte à VOUS, et non l\'inverse. Nos créations en bois apportent du cachet même éteintes, et transforment chaque coin en petit havre de douceur une fois allumées.',
        'promise' => 'Notre savoir-faire',
        'promise_content' => 'Base stable, interrupteur intégré, câble renforcé. Chaque lampe à poser est pensée pour le quotidien : pratique, belle, durable. Pas de fioritures inutiles, juste l\'essentiel fait avec passion.',
        'use_cases' => 'Comment l\'utiliser ?',
        'use_cases_items' => [
          'Chevet → Lumière douce pour lire avant de dormir',
          'Bureau → Éclairage ciblé pour travailler sans fatigue',
          'Table basse → Créer une ambiance cosy dans le salon',
          'Commode → Petit point lumineux pour guider la nuit'
        ]
      ],
      'accessoire' => [
        'tagline' => 'Les détails qui font toute la différence',
        'intro' => 'Un beau luminaire mérite les bons accessoires. Ampoules à filament pour les jeux d\'ombres, douilles de qualité pour la sécurité, pieds adaptés pour les lampadaires... C\'est ici que vous trouvez tout pour parfaire votre installation.',
        'why' => 'Pourquoi ces accessoires comptent ?',
        'why_content' => 'Une mauvaise ampoule, c\'est 50% du charme perdu. Une douille fragile, c\'est un risque inutile. Nos accessoires sont sélectionnés avec le même soin que nos luminaires : qualité, sécurité, esthétique. Rien au hasard.',
        'promise' => 'Notre sélection',
        'promise_content' => 'Ampoules LED longue durée, douilles certifiées CE, câbles textile premium. Tout est testé, tout est compatible avec nos créations. Vous achetez en toute confiance, avec la garantie que ça marchera parfaitement ensemble.',
        'use_cases' => 'Que choisir ?',
        'use_cases_items' => [
          'Ampoules filament → Pour les suspensions ouvertes (effet visuel maximal)',
          'Ampoules opaques → Pour les luminaires fermés (lumière uniforme)',
          'Pieds de lampadaire → Pour transformer une suspension en lampe sur pied',
          'Douilles E27 → Pour personnaliser ou réparer vos luminaires'
        ]
      ]
    ];

    $content = isset($editorial_content[$term_slug]) ? $editorial_content[$term_slug] : null;

    if ($content) :
    ?>
      <div class="editorial-hero">
        <span class="section-number">02</span>
        <h2 class="editorial-tagline"><?php echo esc_html($content['tagline']); ?></h2>
        <p class="editorial-intro"><?php echo esc_html($content['intro']); ?></p>
      </div>

      <div class="editorial-grid">
        <div class="editorial-block">
          <h3><?php echo esc_html($content['why']); ?></h3>
          <p><?php echo esc_html($content['why_content']); ?></p>
        </div>

        <div class="editorial-block editorial-block-highlight">
          <svg class="editorial-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
            <circle cx="12" cy="7" r="4"/>
          </svg>
          <h3><?php echo esc_html($content['promise']); ?></h3>
          <p><?php echo esc_html($content['promise_content']); ?></p>
        </div>
      </div>

      <div class="editorial-use-cases">
        <h3><?php echo esc_html($content['use_cases']); ?></h3>
        <ul class="use-cases-list">
          <?php foreach ($content['use_cases_items'] as $use_case) : ?>
            <li>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
              <?php echo esc_html($use_case); ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
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
$products_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => -1,
  'tax_query' => [
    [
      'taxonomy' => 'product_cat',
      'field' => 'term_id',
      'terms' => $term_id,
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
          global $product, $sapi_carousel_context;
          $product = wc_get_product(get_the_ID());

          // Pass carousel context to content-product.php
          $sapi_carousel_context = [
            'is_carousel' => true,
            'categories' => '', // Not needed for category pages (already filtered)
          ];

          wc_get_template_part('content', 'product');

          $sapi_carousel_context = null;
          ?>
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

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
  'suspensions' => "Des luminaires suspendus en bois qui transforment votre plafond en œuvre d'art. Du lustre design au modèle artisanal, trouvez la suspension qui raconte votre histoire.",
  'lampadaires' => "L'éclairage d'ambiance parfait pour structurer votre espace sans percer le plafond. Nos lampadaires en bois allient design sculptural et lumière chaleureuse.",
  'appliques' => "Libérez vos sols, habillez vos murs. Nos appliques murales en bois créent une atmosphère unique tout en dessinant des jeux d'ombres poétiques.",
  'lampeaposer' => "La touche finale qui change tout. Posez-la où vous voulez, déplacez-la au gré de vos envies : nos lampes nomades créent une bulle de lumière intime partout chez vous.",
  'accessoire' => "Les bons accessoires font toute la différence. Ampoules filament, douilles certifiées, câbles premium : tout pour sublimer vos luminaires en toute sécurité.",
];

if (function_exists('sapi_maison_breadcrumbs')) {
  sapi_maison_breadcrumbs();
}
?>

<section class="shop-hero-cinetique">
  <h1><?php echo esc_html($term_name ? $term_name : 'Nos créations'); ?></h1>
  <?php if (isset($category_intro[$term_slug])) : ?>
    <p class="shop-subtitle"><?php echo esc_html($category_intro[$term_slug]); ?></p>
  <?php endif; ?>
</section>

<?php
// PHASE 2: Mini-carousel "Coups de cœur" (best-sellers, 20vh height)
$featured_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => 1,
  'tax_query' => [
    [
      'taxonomy' => 'product_cat',
      'field' => 'term_id',
      'terms' => $term_id,
    ],
  ],
  'meta_key' => 'total_sales',
  'orderby' => 'meta_value_num',
  'order' => 'DESC',
]);

if ($featured_query->have_posts()) :
?>
<section class="featured-products-mini">
  <div class="featured-products-header">
    <h2><span class="section-num">01</span> Le coup de cœur de l'Atelier</h2>
  </div>

  <div class="products-carousel-mini-wrapper" data-carousel-mini>
    <div class="products-carousel-mini">
      <ul class="products-carousel-mini-track products">
        <?php
        while ($featured_query->have_posts()) :
          $featured_query->the_post();
          global $product;
          $product = wc_get_product(get_the_ID());
          $product_id = $product->get_id();
          $product_url = get_permalink($product_id);

          // Récupérer l'URL de l'image bandeau ACF pour le background
          $bandeau_url = '';
          if (function_exists('get_field')) {
            $bandeau_image = get_field('bandeau', $product_id);

            if ($bandeau_image) {
              // Gérer différents formats de retour ACF pour obtenir l'URL
              if (is_array($bandeau_image) && isset($bandeau_image['url'])) {
                $bandeau_url = $bandeau_image['url'];
              } elseif (is_array($bandeau_image) && isset($bandeau_image['ID'])) {
                $bandeau_url = wp_get_attachment_image_url($bandeau_image['ID'], 'full');
              } elseif (is_numeric($bandeau_image)) {
                $bandeau_url = wp_get_attachment_image_url($bandeau_image, 'full');
              } elseif (is_string($bandeau_image) && strpos($bandeau_image, 'http') === 0) {
                $bandeau_url = $bandeau_image;
              }
            }
          }

          $product_name = get_the_title();

          // Prix simplifié : "À partir de XX€"
          $price = $product->get_price();
          $price_formatted = wc_price($price);

          // Style inline pour le background
          $card_style = $bandeau_url ? 'style="background-image: url(' . esc_url($bandeau_url) . ');"' : '';
        ?>
          <li class="product-mini-card" <?php echo $card_style; ?>>
            <div class="product-hero-content">
              <h3 class="product-hero-name"><?php echo esc_html($product_name); ?></h3>
              <div class="product-hero-price">
                À partir de <?php echo $price_formatted; ?>
              </div>
              <a href="<?php echo esc_url($product_url); ?>" class="product-hero-cta">
                Découvrir
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                  <path d="M3 8H13M13 8L8 3M13 8L8 13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
              </a>
            </div>
          </li>
        <?php endwhile; ?>
      </ul>
    </div>

    <div class="carousel-mini-nav">
      <button class="carousel-mini-btn carousel-mini-prev" aria-label="<?php esc_attr_e('Produit précédent', 'theme-sapi-maison'); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>
      <button class="carousel-mini-btn carousel-mini-next" aria-label="<?php esc_attr_e('Produit suivant', 'theme-sapi-maison'); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>
  </div>
</section>
<?php
wp_reset_postdata();
endif;
?>

<!-- PHASE 2: Full product grid (all products) -->
<section class="category-products-grid">
  <div class="products-grid-header">
    <h2><span class="section-num">02</span> Toutes nos <?php echo esc_html(strtolower($term_name)); ?></h2>
  </div>

  <?php
  // Query all products in this category for the grid
  $grid_query = new WP_Query([
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

  if ($grid_query->have_posts()) :
  ?>
    <ul class="products columns-4">
      <?php
      while ($grid_query->have_posts()) :
        $grid_query->the_post();
        wc_get_template_part('content', 'product');
      endwhile;
      ?>
    </ul>
  <?php
    wp_reset_postdata();
  else :
    wc_no_products_found();
  endif;
  ?>
</section>

<!-- Rich Editorial Content Section (MOVED TO BOTTOM) -->
<?php
// Query one product to get Ambiance 1 background
$bg_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => 1,
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

$ambiance_bg_url = '';
if ($bg_query->have_posts()) {
  $bg_query->the_post();
  $bg_product_id = get_the_ID();

  if (function_exists('get_field')) {
    $ambiance_image = get_field('ambiance_1', $bg_product_id);

    if ($ambiance_image) {
      // Handle different ACF return formats
      if (is_array($ambiance_image) && isset($ambiance_image['url'])) {
        $ambiance_bg_url = $ambiance_image['url'];
      } elseif (is_array($ambiance_image) && isset($ambiance_image['ID'])) {
        $ambiance_bg_url = wp_get_attachment_image_url($ambiance_image['ID'], 'full');
      } elseif (is_numeric($ambiance_image)) {
        $ambiance_bg_url = wp_get_attachment_image_url($ambiance_image, 'full');
      } elseif (is_string($ambiance_image) && strpos($ambiance_image, 'http') === 0) {
        $ambiance_bg_url = $ambiance_image;
      }
    }
  }

  wp_reset_postdata();
}

$editorial_style = $ambiance_bg_url ? 'style="background-image: url(' . esc_url($ambiance_bg_url) . ');"' : '';
?>
<section class="category-editorial" data-particles="wood" <?php echo $editorial_style; ?>>
  <div class="category-editorial-inner">
    <?php
    // Rich editorial content per category
    $editorial_content = [
      'suspensions' => [
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
      'lampadaires' => [
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
      'appliques' => [
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
      'lampeaposer' => [
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
        <h2 class="editorial-tagline"><span class="section-num">03</span> <?php echo esc_html($content['tagline']); ?></h2>
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
get_footer();

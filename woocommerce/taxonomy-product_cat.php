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
  'suspensions' => "Des luminaires suspendus en bois qui transforment votre plafond en œuvre d'art. Du lustre design et moderne au modèle plus traditionnel, trouvez la suspension qui raconte votre histoire.",
  'lampadaires' => "L'éclairage d'ambiance parfait pour structurer votre espace sans percer le plafond. Les lampadaires en bois Atelier Sâpi allient design sculptural et lumière chaleureuse.",
  'appliques' => "Libérez vos sols, habillez vos murs. Les appliques murales en bois créent une atmosphère unique tout en dessinant des jeux d'ombres poétiques.",
  'lampesaposer' => "La touche finale qui change tout. Posez-la où vous voulez, déplacez-la au gré de vos envies : les lampes nomades créent une bulle de lumière intime partout chez vous.",
  'accessoires' => "Les bons accessoires font toute la différence. Ampoules filament, douilles certifiées, câbles textiles, pied de lampe : tout pour sublimer vos luminaires en toute sécurité.",
];

if (function_exists('sapi_maison_breadcrumbs')) {
  sapi_maison_breadcrumbs();
}
?>

<section class="shop-hero-cinetique">
  <h1><?php echo esc_html($term_name ? $term_name : 'Mes créations'); ?></h1>
  <?php if (isset($category_intro[$term_slug])) : ?>
    <p class="shop-subtitle"><?php echo esc_html($category_intro[$term_slug]); ?></p>
  <?php endif; ?>
</section>

<!-- Full product grid (all products) -->
<section class="category-products-grid">
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
    $product_count = 0;
  ?>
    <div class="sapi-showcase-grid">
      <?php
      while ($grid_query->have_posts()) :
        $grid_query->the_post();
        $pid = get_the_ID();
        $product = wc_get_product($pid);
        if (!$product) continue;
        $product_count++;

        $permalink = get_permalink($pid);
        $title = get_the_title();
        $amb = sapi_get_product_photos($pid, 'ambiance', 1, 'large');
        $ambiance_url = !empty($amb) ? $amb[0] : '';
        $studio_url = get_the_post_thumbnail_url($pid, 'medium');
        $gallery_ids = $product->get_gallery_image_ids();
        $gallery_hover_url = !empty($gallery_ids) ? wp_get_attachment_image_url($gallery_ids[0], 'medium') : '';
        $is_variable = $product->is_type('variable');
        $price_html = $is_variable
          ? 'À partir de <strong>' . esc_html(number_format((float)$product->get_variation_price('min'), 2, ',', '')) . '&nbsp;€</strong>'
          : '<strong>' . esc_html(number_format((float)$product->get_price(), 2, ',', '')) . '&nbsp;€</strong>';

        // Alternance gauche/droite
        $side_class = ($product_count % 2 === 1) ? 'showcase-left' : 'showcase-right';
      ?>
        <a href="<?php echo esc_url($permalink); ?>" class="sapi-showcase-card <?php echo esc_attr($side_class); ?>">
          <div class="showcase-info">
            <?php if ($studio_url) : ?>
              <div class="showcase-product-img-wrap">
                <img src="<?php echo esc_url($studio_url); ?>" alt="<?php echo esc_attr($title); ?>" class="showcase-product-img showcase-product-img-main" loading="lazy" />
                <?php if ($gallery_hover_url) : ?>
                  <img src="<?php echo esc_url($gallery_hover_url); ?>" alt="<?php echo esc_attr($title); ?> — allumé" class="showcase-product-img showcase-product-img-hover" loading="lazy" />
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <h3 class="showcase-name product-name"><?php echo esc_html($title); ?></h3>
            <div class="showcase-price"><?php echo $price_html; ?></div>
            <span class="showcase-cta">Découvrir ⇾</span>
          </div>
          <div class="showcase-photo">
            <?php if ($ambiance_url) : ?>
              <img src="<?php echo esc_url($ambiance_url); ?>" alt="<?php echo esc_attr($title); ?> — ambiance" class="showcase-bg" loading="lazy" />
            <?php endif; ?>
          </div>
        </a>
      <?php

        // Card Robin après le 4ème produit
        if ($product_count === 4 && defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) :
        ?>
          <div class="robin-category-card" id="robin-category-card">
            <div class="robin-category-card__inner" data-robin-context="category" data-robin-data='<?php echo esc_attr(wp_json_encode(['category_slug' => $term_slug])); ?>'>
              <span class="robin-modal__badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
                Conseil de Robin
              </span>
              <p class="robin-category-card__text">Quel mod&egrave;le est fait pour vous ? R&eacute;pondez &agrave; quelques questions, Robin vous guide.</p>
              <span class="robin-category-card__cta">D&eacute;couvrir &rarr;</span>
            </div>
          </div>
        <?php
        endif;

      endwhile;
      ?>
    </div>
  <?php
    wp_reset_postdata();
  else :
    wc_no_products_found();
  endif;
  ?>
</section>

<!-- Rich Editorial Content Section (MOVED TO BOTTOM) -->
<?php
// Background éditorial : photo "fabrication" aléatoire prise dans tous les produits de la catégorie
$bg_query = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => 20,
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
  $fabrication_pool = [];
  while ($bg_query->have_posts()) {
    $bg_query->the_post();
    $fab_photos = sapi_get_product_photos(get_the_ID(), 'fabrication');
    if (!empty($fab_photos)) {
      $fabrication_pool = array_merge($fabrication_pool, $fab_photos);
    }
  }
  if (!empty($fabrication_pool)) {
    $ambiance_bg_url = $fabrication_pool[array_rand($fabrication_pool)];
  }
  wp_reset_postdata();
}

?>
<section class="category-editorial" data-particles="wood">
  <?php if ($ambiance_bg_url) : ?>
    <img src="<?php echo esc_url($ambiance_bg_url); ?>" alt="<?php echo esc_attr(single_term_title('', false)); ?> — Collection luminaires en bois" class="category-editorial-img" loading="lazy">
  <?php endif; ?>
  <div class="category-editorial-inner">
    <?php
    // Rich editorial content per category
    $editorial_content = [
      'suspensions' => [
        'tagline' => 'La lumière qui vous ressemble',
        'intro' => 'Une suspension, ce n\'est pas juste un luminaire accroché au plafond. C\'est le point focal de votre pièce, celle qui attire le regard dès qu\'on franchit la porte. C\'est l\'élément qui transforme un espace ordinaire en un lieu unique, chaleureux, où on a envie de vivre.',
        'why' => 'Pourquoi choisir une suspension en bois ?',
        'why_content' => 'Le bois apporte une chaleur incomparable. Il filtre la lumière avec douceur, créant des jeux d\'ombres poétiques sur vos murs. Ces suspensions ne sont pas de simples objets décoratifs : elles racontent une histoire, celle de l\'artisanat français, du savoir-faire et de la passion.',
        'promise' => 'La promesse de Robin',
        'promise_content' => 'Chaque suspension est unique. Découpée au laser dans l\'atelier de Robin près de Lyon, poncée à la main, vérifiée avec soin. Vous recevez bien plus qu\'un luminaire : vous recevez une pièce d\'art fonctionnelle, pensée pour durer, conçue pour sublimer votre quotidien.',
        'use_cases' => 'Où installer votre suspension ?',
        'use_cases_items' => [
          'Au-dessus de la table à manger → Pour créer une ambiance conviviale lors des repas et impressionner vos convives',
          'Dans le salon → Comme pièce maîtresse qui structure l\'espace',
          'Dans la chambre → Pour une atmosphère apaisante et enveloppante',
          'Au-dessus d\'un îlot central → Design et fonctionnel pour cuisiner avec style'
        ]
      ],
      'lampadaires' => [
        'tagline' => 'L\'élégance posée',
        'intro' => 'Un lampadaire, c\'est la liberté d\'éclairer sans contrainte. Pas de trou à percer, pas de plafond trop bas. Juste poser, brancher, et profiter. C\'est l\'allié parfait pour structurer un coin lecture, réchauffer un angle froid, ou simplement ajouter une touche de caractère.',
        'why' => 'Pourquoi un lampadaire fait la différence ?',
        'why_content' => 'Contrairement à un plafonnier, le lampadaire crée une lumière d\'ambiance, plus douce, plus humaine. Il se fond dans votre décoration tout en affirmant son caractère. Les modèles en bois de Robin sculptent l\'espace avec poésie, entre ombre et lumière.',
        'promise' => 'L\'engagement de Robin',
        'promise_content' => 'Stabilité, robustesse, élégance. Les lampadaires sont conçus pour être beaux ET pratiques. Base lestée pour éviter tout basculement, bois PEFC pour une démarche responsable, découpe précise pour une finition irréprochable.',
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
        'why_content' => 'Pratiques et élégantes, les appliques sont parfaites pour les petits espaces ou pour créer un éclairage d\'appoint. Les modèles en bois diffusent une lumière chaleureuse et tamisée, idéale pour les moments de détente.',
        'promise' => 'Ce qui fait la différence',
        'promise_content' => 'Installation simplifiée, câblage textile de qualité, fixations fournies. Chaque applique est testée et emballée avec soin. Vous recevez un luminaire prêt à poser, avec notice illustrée claire et assistance disponible si besoin.',
        'use_cases' => 'Où installer vos appliques ?',
        'use_cases_items' => [
          'De chaque côté du lit → Pour lire doucement avant de s\'endormir',
          'Dans le couloir → Lumière douce qui guide sans éblouir',
          'Salon → Éclairage d\'appoint idéal pour les grands espaces, ou pour les soirées tranquilles',
          'Escalier → Sécurité + décoration en un seul geste'
        ]
      ],
      'lampesaposer' => [
        'tagline' => 'La touche finale qui change tout',
        'intro' => 'Une lampe à poser, c\'est la liberté totale. Sur une table de chevet, un bureau, une étagère... Elle s\'installe partout, se déplace au gré de vos envies, et crée instantanément une bulle de lumière intime et chaleureuse.',
        'why' => 'Pourquoi craquer pour une lampe à poser ?',
        'why_content' => 'Compacte, mobile, polyvalente. La lampe à poser est le luminaire qui s\'adapte à VOUS, et non l\'inverse. Les créations en bois de Robin apportent du cachet même éteintes, et transforment chaque coin en petit havre de douceur une fois allumées.',
        'promise' => 'Le savoir-faire de Robin',
        'promise_content' => 'Base stable, interrupteur intégré, câble textile. Chaque lampe à poser est pensée pour le quotidien : pratique, belle, durable. Pas de fioritures inutiles, juste l\'essentiel fait avec passion.',
        'use_cases' => 'Comment l\'utiliser ?',
        'use_cases_items' => [
          'Chevet → Lumière douce pour lire avant de dormir',
          'Bureau → Éclairage ciblé pour travailler sans fatigue',
          'Table basse → Créer une ambiance cosy dans le salon',
          'Commode → Petit point lumineux pour guider la nuit'
        ]
      ],
      'accessoires' => [
        'tagline' => 'Les détails qui font toute la différence',
        'intro' => 'Un beau luminaire mérite les bons accessoires. Ampoules à filament pour les jeux d\'ombres, douilles de qualité pour la sécurité, pieds adaptés pour les lampadaires... C\'est ici que vous trouvez tout pour parfaire votre installation.',
        'why' => 'Pourquoi ces accessoires comptent ?',
        'why_content' => 'Une mauvaise ampoule, c\'est 50% du charme perdu. Une douille fragile, c\'est un risque inutile. Les accessoires sont sélectionnés avec le même soin que les luminaires : qualité, sécurité, esthétique. Rien au hasard.',
        'promise' => 'La sélection de Robin',
        'promise_content' => 'Ampoules LED longue durée, douilles certifiées CE, câbles textile premium. Tout est testé, tout est compatible avec les créations de l\'Atelier Sâpi. Vous achetez en toute confiance, avec la garantie que ça marchera parfaitement ensemble.',
        'use_cases' => 'Que choisir ?',
        'use_cases_items' => [
          'Ampoules filament → Pour tous nos luminaires. Puissance maximale, consommation maitrisée, et jeux de lumière qui font rêver',
          'Quelle couleur ? → A vous de choisir ! Noir ou blanc disponible en ligne.',
          'Pieds de lampadaire → Pour transformer une suspension en lampe sur pied',
          'Douilles E27 → Pour personnaliser ou réparer vos luminaires'
        ]
      ]
    ];

    $content = isset($editorial_content[$term_slug]) ? $editorial_content[$term_slug] : null;

    if ($content) :
    ?>
      <div class="editorial-hero">
        <h2 class="editorial-tagline"><span class="section-num">02</span> <?php echo esc_html($content['tagline']); ?></h2>
        <p class="editorial-intro"><?php echo esc_html($content['intro']); ?></p>
      </div>

      <button class="editorial-read-more" aria-expanded="false">
        <span>En savoir plus</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="6 9 12 15 18 9"></polyline>
        </svg>
      </button>

      <div class="editorial-collapsible">
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
      </div>

      <script>
      (function() {
        var btn = document.querySelector('.editorial-read-more');
        if (!btn) return;
        btn.addEventListener('click', function() {
          var section = btn.closest('.category-editorial');
          var expanded = section.classList.toggle('is-expanded');
          btn.setAttribute('aria-expanded', expanded);
          btn.querySelector('span').textContent = expanded ? 'Réduire' : 'En savoir plus';
        });
      })();
      </script>
    <?php endif; ?>
  </div>
</section>

<?php
// ── Bloc « Découvrez aussi » — maillage interne entre catégories ──
$cross_links = [
  'suspensions'  => ['appliques', 'lampadaires', 'lampesaposer'],
  'appliques'    => ['suspensions', 'lampesaposer', 'lampadaires'],
  'lampadaires'  => ['lampesaposer', 'suspensions', 'appliques'],
  'lampesaposer' => ['lampadaires', 'appliques', 'suspensions'],
];

if ( isset( $cross_links[ $term_slug ] ) ) :
  $linked_slugs = $cross_links[ $term_slug ];
  $linked_cards = [];

  foreach ( $linked_slugs as $slug ) {
    $linked_term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $linked_term ) continue;

    // Récupérer la photo du 1er produit de cette catégorie
    $cat_query = new WP_Query([
      'post_type'      => 'product',
      'posts_per_page' => 1,
      'tax_query'      => [[ 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $linked_term->term_id ]],
      'orderby'        => 'menu_order date',
      'order'          => 'ASC',
    ]);

    $thumb_url = '';
    if ( $cat_query->have_posts() ) {
      $cat_query->the_post();
      $pid = get_the_ID();

      // Photo ambiance du repeater ACF (comme le hero)
      $amb = sapi_get_product_photos( $pid, 'ambiance', 1 );
      if ( ! empty( $amb ) ) {
        $thumb_url = $amb[0];
      } else {
        // Fallback : image produit WooCommerce
        $product = wc_get_product( $pid );
        if ( $product ) {
          $img_id = $product->get_image_id();
          if ( $img_id ) {
            $src = wp_get_attachment_image_src( $img_id, 'medium' );
            $thumb_url = $src ? $src[0] : '';
          }
        }
      }
      wp_reset_postdata();
    }

    $linked_cards[] = [
      'term'  => $linked_term,
      'thumb' => $thumb_url,
    ];
  }

  $carte_cadeau = get_term_by( 'slug', 'carte-cadeau', 'product_cat' );
?>
<section class="category-cross-links">
  <h2><span class="section-num">03</span> Découvrez aussi</h2>
  <div class="cross-links-cards">
    <?php foreach ( $linked_cards as $card ) : ?>
      <a href="<?php echo esc_url( get_term_link( $card['term'] ) ); ?>" class="cross-link-card">
        <?php if ( $card['thumb'] ) : ?>
          <div class="cross-link-card-img">
            <img src="<?php echo esc_url( $card['thumb'] ); ?>" alt="<?php echo esc_attr( $card['term']->name ); ?>" loading="lazy">
          </div>
        <?php endif; ?>
        <span class="cross-link-card-name"><?php echo esc_html( $card['term']->name ); ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php if ( $carte_cadeau && ! is_wp_error( get_term_link( $carte_cadeau ) ) ) : ?>
    <a href="<?php echo esc_url( get_term_link( $carte_cadeau ) ); ?>" class="cross-link-gift">
      Vous hésitez ? Offrez une carte cadeau
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php
get_footer();

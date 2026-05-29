<?php
/*
Template Name: Galerie Inspiration
*/
get_header();

// Collecte des photos ambiance + detail sur les produits publiés
// (4 catégories luminaires uniquement). Volume contrôlé (~24 produits,
// marge à 200) — la règle anti -1 vise les requêtes catalogues massives,
// pas les pages galerie au volume borné.
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

// S28 Phase 6a-bis — lecture via helper Phase 3 (Gallery uniquement, plus
// de fallback repeater depuis 6b-1). On garde le couple [attachment_id,
// product_id] pour pouvoir lier chaque photo à son produit. L'ordre interne
// par produit (ambiance puis detail, au lieu de l'ordre repeater mélangé) est
// SANS IMPACT VISIBLE : shuffle($photos) randomise tout ci-dessous.
$photos = [];

if ($products_query->have_posts() && function_exists('get_field')) {
  foreach ($products_query->posts as $product_id) {
    foreach (['ambiance', 'detail'] as $type) {
      $ids = sapi_get_product_photo_ids($product_id, $type);
      foreach ($ids as $img_id) {
        $photos[] = [
          'attachment_id' => $img_id,
          'product_id'    => $product_id,
        ];
      }
    }
  }
}

shuffle($photos);

// Lecture des taxonomies media_room / media_essence pour chaque photo
// (snippet maison "Photos par pièce + matière"). Précharge le cache de termes
// pour éviter ~2N requêtes lors des wp_get_object_terms par photo.
$used_rooms    = []; // slug => label (collectés pour la card filtres)
$used_essences = [];

if (!empty($photos)) {
  $attachment_ids = wp_list_pluck($photos, 'attachment_id');
  update_object_term_cache(array_unique($attachment_ids), 'attachment');

  foreach ($photos as $i => $photo) {
    $img_id = $photo['attachment_id'];

    $room_slugs = [];
    $room_terms = wp_get_object_terms($img_id, 'media_room');
    if (!is_wp_error($room_terms)) {
      foreach ($room_terms as $t) {
        $room_slugs[] = $t->slug;
        $used_rooms[$t->slug] = $t->name;
      }
    }
    $photos[$i]['rooms'] = $room_slugs;

    $essence_slugs = [];
    $essence_terms = wp_get_object_terms($img_id, 'media_essence');
    if (!is_wp_error($essence_terms)) {
      foreach ($essence_terms as $t) {
        $essence_slugs[] = $t->slug;
        $used_essences[$t->slug] = $t->name;
      }
    }
    $photos[$i]['essences'] = $essence_slugs;
  }

  // Labels d'affichage courts pour certains slugs (override du nom WP).
  $room_label_overrides = [
    'autre-piece-maison' => 'Autre',
  ];
  foreach ($room_label_overrides as $slug => $short) {
    if (isset($used_rooms[$slug])) {
      $used_rooms[$slug] = $short;
    }
  }

  // Tri pièces par popularité supposée (Robin) ; "autre-*" en fin de liste ;
  // pièces hors classement → après les connues mais avant les "autre-*".
  $room_priority = [
    'salon'             => 1,
    'cuisine'           => 2,
    'salle-a-manger'    => 3,
    'chambre'           => 4,
    'chambre-enfant'    => 5,
    'bureau'            => 6,
    'entree'            => 7,
    'escalier'          => 8,
    'couloir'           => 9,
    'hotel'             => 20,
    'restaurant'        => 21,
    'boutique'          => 22,
    'espace-bien-etre'  => 23,
    'salle-de-reunion'  => 24,
  ];
  uksort($used_rooms, function ($a, $b) use ($room_priority, $used_rooms) {
    $aIsAutre = strpos($a, 'autre') === 0;
    $bIsAutre = strpos($b, 'autre') === 0;
    if ($aIsAutre && !$bIsAutre) return 1;
    if (!$aIsAutre && $bIsAutre) return -1;
    $rankA = isset($room_priority[$a]) ? $room_priority[$a] : 100;
    $rankB = isset($room_priority[$b]) ? $room_priority[$b] : 100;
    if ($rankA !== $rankB) return $rankA - $rankB;
    return strcmp($used_rooms[$a], $used_rooms[$b]);
  });

  asort($used_essences);
}

$show_filters = !empty($used_rooms) || !empty($used_essences);

// Icônes pièces (reprises du room-picker homepage). Slug → SVG.
// Slugs absents du mapping → label seul (validé Robin).
$inspiration_room_icons = [
  'salon'           => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><rect x="2" y="11" width="20" height="7" rx="2"/><path d="M5 18v2m14-2v2"/></svg>',
  'cuisine'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V20H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>',
  // Couteau + fourchette (Lucide utensils, simplifié).
  'salle-a-manger'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 2v7c0 1.1.9 2 2 2h2c1.1 0 2-.9 2-2V2"/><path d="M6 11v11"/><path d="M19 2c-1.66 0-3 2.69-3 6s1.34 6 3 6"/><path d="M19 14v8"/></svg>',
  'chambre'         => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>',
  'chambre-enfant'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>',
  'bureau'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>',
  'entree'          => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="15" cy="12" r="1"/></svg>',
  'escalier'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4v-4h4v-4h4V8h4"/><path d="M4 20V8"/><path d="M20 20V8"/></svg>',
];

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

// ---- Cards intercalées ----
// Distribution proportionnelle au total d'items. Avec CSS columns, le
// navigateur remplit la colonne 1 d'abord, puis 2, puis 3 — donc des
// positions fixes type [4, 9, 14, …] avec espacement 5 et 70-90 photos
// font tomber les 6 cards toutes dans le 1er tiers du DOM (= colonne 1).
// La formule floor(total * i / (nb_cards + 1)) répartit les cards sur
// toute la longueur de la grille, donc dans toutes les colonnes.
$num_photos  = count($photos);
$nb_cards    = 6;
$card_keys   = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6'];
$total_tiles = $num_photos + $nb_cards;
$cards_at    = [];

if ($num_photos > 0) {
  for ($i = 1; $i <= $nb_cards; $i++) {
    $pos = (int) floor($total_tiles * $i / ($nb_cards + 1));
    if ($pos < 1) $pos = 1;
    if ($pos > $total_tiles) $pos = $total_tiles;
    // Garde-fou : si deux cards tombent sur la même position
    // (cas extrême avec très peu de photos), on décale d'un cran.
    while (isset($cards_at[$pos]) && $pos < $total_tiles) {
      $pos++;
    }
    $cards_at[$pos] = $card_keys[$i - 1];
  }
}

// Si on a moins de cards effectives qu'attendu (collision/garde-fou),
// $total_tiles est ajusté en conséquence.
$total_tiles = $num_photos + count($cards_at);
$photo_pile  = $photos;

$render_card = function ($card_id) {
  switch ($card_id) {
    case 'c1':
      ?>
      <article class="inspiration-card inspiration-card--reassurance inspiration-card--c1">
        <div class="inspiration-card__inner">
          <span class="inspiration-card__icon" aria-hidden="true">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/icons/picto-french.svg'); ?>" width="32" height="32" alt="" loading="lazy" decoding="async">
          </span>
          <h3 class="inspiration-card__title">Fait main en France</h3>
          <p class="inspiration-card__text">Chaque luminaire est conçu, découpé et assemblé dans mon atelier.</p>
        </div>
      </article>
      <?php
      break;

    case 'c2':
      ?>
      <article class="inspiration-card inspiration-card--story inspiration-card--c2">
        <div class="inspiration-card__inner">
          <p class="inspiration-card__display">Assemblez, Éclairez, Admirez&nbsp;!</p>
          <p class="inspiration-card__text">Le slogan de l'Atelier Sâpi&nbsp;: trois étapes, un luminaire qui vous ressemble.</p>
        </div>
      </article>
      <?php
      break;

    case 'c3':
      ?>
      <article class="inspiration-card inspiration-card--cta inspiration-card--c3">
        <div class="inspiration-card__inner">
          <h3 class="inspiration-card__title">Besoin d'aide pour choisir&nbsp;?</h3>
          <p class="inspiration-card__text">Réponds à quelques questions et je te montre les modèles qui correspondent.</p>
          <a class="inspiration-card__button" href="<?php echo esc_url(home_url('/mes-creations/')); ?>" aria-label="Affiner ma sélection avec Robin">Affiner ma sélection</a>
        </div>
      </article>
      <?php
      break;

    case 'c4':
      $form_id = 'inspiration-newsletter-' . wp_generate_uuid4();
      ?>
      <article class="inspiration-card inspiration-card--newsletter inspiration-card--c4">
        <div class="inspiration-card__inner">
          <h3 class="inspiration-card__title">Recevez les coulisses de mon atelier</h3>
          <p class="inspiration-card__text">Nouveautés, mes projets en cours, mes inspirations directement dans votre boîte mail.</p>
          <form class="inspiration-card__form" data-inspiration-newsletter novalidate>
            <label for="<?php echo esc_attr($form_id); ?>" class="inspiration-card__form-label">Adresse email</label>
            <div class="inspiration-card__form-row">
              <input
                type="email"
                id="<?php echo esc_attr($form_id); ?>"
                name="email"
                placeholder="votre@email.fr"
                autocomplete="email"
                required
                class="inspiration-card__form-input">
              <button type="submit" class="inspiration-card__form-button">Je m'abonne</button>
            </div>
            <p class="inspiration-card__form-status" role="status" aria-live="polite"></p>
          </form>
        </div>
      </article>
      <?php
      break;

    case 'c5':
      ?>
      <article class="inspiration-card inspiration-card--reassurance inspiration-card--c5">
        <div class="inspiration-card__inner">
          <h3 class="inspiration-card__title">Et une création sur mesure&nbsp;?</h3>
          <p class="inspiration-card__text">Dimension spécifique, forme nouvelle, couleur favorite&nbsp;?</p>
          <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="inspiration-card__link">Me contacter →</a>
        </div>
      </article>
      <?php
      break;

    case 'c6':
      ?>
      <article class="inspiration-card inspiration-card--story inspiration-card--story-dark inspiration-card--c6">
        <div class="inspiration-card__inner">
          <p class="inspiration-card__display inspiration-card__display--lg">Du bois, de la lumière, et beaucoup de patience</p>
          <p class="inspiration-card__text">Découpe laser de précision, ponçage à la main, finitions soignées. Chaque pièce est unique.</p>
        </div>
      </article>
      <?php
      break;
  }
};

$render_photo = function ($photo, $position_in_grid) {
  $product_id   = $photo['product_id'];
  $img_id       = $photo['attachment_id'];
  $product_name = get_the_title($product_id);
  $product_url  = get_permalink($product_id);
  $alt          = get_post_meta($img_id, '_wp_attachment_image_alt', true);
  if ($alt === '') $alt = $product_name;

  // Les 6 premières tuiles (LCP) en eager, le reste en lazy.
  $img_attrs = [
    'alt'      => $alt,
    'class'    => 'inspiration-tile-img',
    'loading'  => $position_in_grid <= 6 ? 'eager' : 'lazy',
    'decoding' => 'async',
  ];
  if ($position_in_grid === 1) {
    $img_attrs['fetchpriority'] = 'high';
  }

  $rooms_attr    = !empty($photo['rooms'])    ? implode(' ', $photo['rooms'])    : '';
  $essences_attr = !empty($photo['essences']) ? implode(' ', $photo['essences']) : '';
  ?>
  <a href="<?php echo esc_url($product_url); ?>" class="inspiration-tile"
     data-rooms="<?php echo esc_attr($rooms_attr); ?>"
     data-essences="<?php echo esc_attr($essences_attr); ?>"
     aria-label="<?php echo esc_attr($product_name); ?>">
    <?php echo wp_get_attachment_image($img_id, 'large', false, $img_attrs); ?>
    <span class="inspiration-tile-overlay" aria-hidden="true">
      <span class="inspiration-tile-name"><?php echo inspiration_format_product_name($product_name); ?></span>
    </span>
  </a>
  <?php
};

$render_filter_card = function () use ($used_rooms, $used_essences, $inspiration_room_icons) {
  ?>
  <article class="inspiration-card inspiration-card--filters" data-inspiration-filters>
    <div class="inspiration-card__inner inspiration-filters__inner">

      <?php if (!empty($used_rooms)) : ?>
        <div class="inspiration-filters__section">
          <p class="inspiration-filters__legend">Pièce</p>
          <div class="inspiration-filters__grid">
            <?php foreach ($used_rooms as $slug => $label) :
              $has_icon = isset($inspiration_room_icons[$slug]);
              $btn_class = 'inspiration-filter-btn' . ($has_icon ? '' : ' inspiration-filter-btn--no-icon');
              ?>
              <button type="button" class="<?php echo esc_attr($btn_class); ?>"
                      data-filter-type="room"
                      data-filter-value="<?php echo esc_attr($slug); ?>"
                      aria-pressed="false">
                <?php if ($has_icon) : ?>
                  <span class="inspiration-filter-btn__icon" aria-hidden="true"><?php echo $inspiration_room_icons[$slug]; ?></span>
                <?php endif; ?>
                <span class="inspiration-filter-btn__label"><?php echo esc_html($label); ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($used_essences)) : ?>
        <div class="inspiration-filters__section">
          <p class="inspiration-filters__legend">Essence de bois</p>
          <div class="inspiration-filters__grid">
            <?php foreach ($used_essences as $slug => $label) : ?>
              <button type="button" class="inspiration-filter-btn inspiration-filter-btn--no-icon"
                      data-filter-type="essence"
                      data-filter-value="<?php echo esc_attr($slug); ?>"
                      aria-pressed="false">
                <span class="inspiration-filter-btn__label"><?php echo esc_html($label); ?></span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <button type="button" class="inspiration-filters__reset" data-inspiration-reset hidden>Réinitialiser les filtres</button>
      <p class="inspiration-filters__empty" data-inspiration-empty hidden>Aucune photo ne correspond à ces filtres.</p>
    </div>
  </article>
  <?php
};
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
  <?php else :
    if ($show_filters) {
      $render_filter_card();
    }
    for ($i = 1; $i <= $total_tiles; $i++) {
      if (isset($cards_at[$i])) {
        $render_card($cards_at[$i]);
      } elseif (!empty($photo_pile)) {
        $photo = array_shift($photo_pile);
        $render_photo($photo, $i);
      }
    }
    ?>

    <article class="inspiration-card inspiration-card--final" aria-labelledby="inspiration-final-title">
      <div class="inspiration-card__inner">
        <h2 id="inspiration-final-title" class="inspiration-card__title inspiration-card__title--lg">Découvrir tous les modèles</h2>
        <p class="inspiration-card__text">L'ensemble du catalogue Atelier Sâpi</p>
        <a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/boutique/')); ?>" class="inspiration-card__button inspiration-card__button--lg">Voir la boutique</a>
      </div>
    </article>
  <?php endif; ?>
</section>

<?php
wp_reset_postdata();
get_footer();

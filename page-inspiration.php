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
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M15 12l-8.5 8.5a2.12 2.12 0 0 1-3-3L12 9"/>
              <path d="M17.64 15L22 10.64"/>
              <path d="M20.91 11.7l-1.25-1.25a2.12 2.12 0 0 1 0-3l1.13-1.13a2.12 2.12 0 0 0 0-3L19 1l-3.4 3.4a2.12 2.12 0 0 0 0 3L17 8.85"/>
            </svg>
          </span>
          <h3 class="inspiration-card__title">Fait main en France</h3>
          <p class="inspiration-card__text">Chaque luminaire est conçu, découpé et assemblé dans l'atelier.</p>
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
          <h3 class="inspiration-card__title">Pas sûr du modèle pour votre pièce&nbsp;?</h3>
          <p class="inspiration-card__text">Robin Conseiller vous oriente en 3 questions.</p>
          <button type="button" class="inspiration-card__button" data-robin-open="bandeau" aria-label="Démarrer le configurateur Robin Conseiller">Démarrer le configurateur</button>
        </div>
      </article>
      <?php
      break;

    case 'c4':
      $form_id = 'inspiration-newsletter-' . wp_generate_uuid4();
      ?>
      <article class="inspiration-card inspiration-card--newsletter inspiration-card--c4">
        <div class="inspiration-card__inner">
          <h3 class="inspiration-card__title">Recevez les coulisses de l'atelier</h3>
          <p class="inspiration-card__text">Nouveautés, projets en cours, et inspirations directement dans votre boîte mail.</p>
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
          <h3 class="inspiration-card__title">Sur-mesure possible</h3>
          <p class="inspiration-card__text">Une dimension, une teinte ou une essence spécifique&nbsp;? Parlons-en.</p>
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
          <p class="inspiration-card__text">Découpe laser de précision, assemblage à la main, finitions soignées. Chaque pièce est unique.</p>
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
  ?>
  <a href="<?php echo esc_url($product_url); ?>" class="inspiration-tile" aria-label="<?php echo esc_attr($product_name); ?>">
    <?php echo wp_get_attachment_image($img_id, 'large', false, $img_attrs); ?>
    <span class="inspiration-tile-overlay" aria-hidden="true">
      <span class="inspiration-tile-name"><?php echo inspiration_format_product_name($product_name); ?></span>
    </span>
  </a>
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

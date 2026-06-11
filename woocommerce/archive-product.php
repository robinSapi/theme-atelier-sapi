<?php
/**
 * The Template for displaying product archives
 *
 * SAPI CINÉTIQUE - Shop page with carousel, client-side filters, and hover effects
 *
 * @package Sapi-Maison
 * @version 9.5.1
 */

defined('ABSPATH') || exit;

get_header();

// Get ALL products (no pagination)
$all_products = new WP_Query([
  'post_type' => 'product',
  'posts_per_page' => -1,
  'post_status' => 'publish',
  'orderby' => 'menu_order date',
  'order' => 'ASC',
]);

// Pills catégorie (Chantier 3) — récupère les catégories WC avec leurs counts.
// Ordre fixe : suspensions / lampadaires / lampesaposer / appliques / accessoires.
// Le total "Tous" exclut uniquement carte-cadeau (gift card, hors créations).
$mes_creations_cat_order = ['suspensions', 'lampadaires', 'lampesaposer', 'appliques', 'accessoires'];
$mes_creations_cats_raw  = get_terms([
  'taxonomy'   => 'product_cat',
  'hide_empty' => true,
  'exclude'    => [get_option('default_product_cat')],
]);
$mes_creations_cats_by_slug = [];
if ($mes_creations_cats_raw && !is_wp_error($mes_creations_cats_raw)) {
  foreach ($mes_creations_cats_raw as $cat) {
    $mes_creations_cats_by_slug[$cat->slug] = $cat;
  }
}
// Lecture du param GET ?product_cat= pour le filtre actif initial.
$mes_creations_active_cat = isset($_GET['product_cat']) ? sanitize_key(wp_unslash($_GET['product_cat'])) : '';
if ($mes_creations_active_cat && !isset($mes_creations_cats_by_slug[$mes_creations_active_cat])) {
  $mes_creations_active_cat = '';
}
?>

<!-- Hero Section -->
<?php
// Photos par pièce (ACF hero_<slug> sur la page boutique) exposées au JS via
// data-piece-photos sur la card "Mon projet" : sapi-cards-conseiller.js
// affiche un bandeau de la pièce du projet en haut de la card. Le hero, lui,
// est statique (photo Croquis + titre "Mes créations" fixes, cf. CSS
// .shop-hero-artisan). Format : { slug: [url1, url2, ...], … } — array même
// pour un seul ID, future-proof si Robin passe les champs en Galerie. Pas de
// clé 'default' : pas de photo dédiée à une pièce → pas de bandeau.
$piece_titles = function_exists('sapi_get_hero_piece_titles')
  ? sapi_get_hero_piece_titles()
  : ['pieces' => []];
$piece_slugs = !empty($piece_titles['pieces']) ? array_keys($piece_titles['pieces']) : [];

$hero_photos_by_piece = [];
if ($piece_slugs && function_exists('get_field') && function_exists('wc_get_page_id')) {
  $shop_page_id = wc_get_page_id('shop');
  if ($shop_page_id > 0) {
    // Helper local : normalise une valeur ACF (ID seul, array d'IDs,
    // ou array d'objets attachment) en liste d'URLs full size.
    $sapi_acf_to_urls = function ($val) {
      $ids = [];
      if (is_array($val)) {
        foreach ($val as $item) {
          if (is_numeric($item)) {
            $ids[] = (int) $item;
          } elseif (is_array($item) && !empty($item['ID'])) {
            $ids[] = (int) $item['ID'];
          }
        }
      } elseif (is_numeric($val)) {
        $ids[] = (int) $val;
      }
      $urls = [];
      foreach ($ids as $id) {
        $url = wp_get_attachment_image_url($id, 'full');
        if ($url) $urls[] = $url;
      }
      return $urls;
    };

    foreach ($piece_slugs as $piece_slug) {
      $urls = $sapi_acf_to_urls(get_field('hero_' . $piece_slug, $shop_page_id));
      // Filet de sécurité : ACF convertit parfois les tirets du nom de champ
      // en underscores (ex. champ nommé hero_chambre_enfant pour le slug
      // chambre-enfant). On retente la variante underscore si rien trouvé.
      if (empty($urls) && strpos($piece_slug, '-') !== false) {
        $urls = $sapi_acf_to_urls(get_field('hero_' . str_replace('-', '_', $piece_slug), $shop_page_id));
      }
      if (!empty($urls)) {
        $hero_photos_by_piece[$piece_slug] = $urls;
      }
    }
  }
}

$hero_photos_attr = !empty($hero_photos_by_piece)
  ? wp_json_encode($hero_photos_by_piece, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
  : '';

// ── Refonte /mes-creations/ — état B « immersion » (arrivée depuis le
// room-picker via ?piece=). Tout est gated sur une pièce valide : sans
// ?piece= valide, $imm_piece reste vide et rien ci-dessous n'est rendu
// (état A 100 % inchangé). Le CSS (body.mescreations-immersion) masque le
// hero croquis + la zone cards conseiller et révèle ce hero immersif.
$imm_piece = function_exists('sapi_mescreations_immersion_piece') ? sapi_mescreations_immersion_piece() : '';
if ($imm_piece) {
  $imm_possessive = function_exists('sapi_piece_possessive') ? sapi_piece_possessive($imm_piece) : 'ta pièce';
  // Photo plein écran : 1re photo hero_<slug> si dispo, sinon repli générique
  // (décision Robin #5). Repli = une ambiance neutre connue en prod.
  $imm_photo_url = !empty($hero_photos_by_piece[$imm_piece][0]) ? $hero_photos_by_piece[$imm_piece][0] : '';
  $imm_generic_fallback = '2025/04/Alban-Le-virevoltant-Salon-Paysage.jpg';
  // Phrase de conseil FIGÉE par pièce (jamais l'IA live — décision Robin / brief).
  $imm_phrase = function_exists('sapi_megafilter_generic_advice_for') ? sapi_megafilter_generic_advice_for($imm_piece) : '';

  // Sélection PIÈCE-LEVEL calculée CÔTÉ SERVEUR (réutilise le matching du
  // méga-filtre : catégories admissibles + filtre ampoule par pièce + fallbacks
  // progressifs intégrés → jamais de slider vide). taille/style viendront
  // affiner via AJAX serveur à l'étape suivante.
  $imm_answers   = ['piece' => $imm_piece];
  $imm_cats      = function_exists('sapi_guide_get_categories') ? sapi_guide_get_categories($imm_answers) : [];
  $imm_query_res = (function_exists('sapi_guide_query_products') && $imm_cats)
    ? sapi_guide_query_products($imm_answers, $imm_cats)
    : ['products' => []];
  $imm_products  = isset($imm_query_res['products']) ? $imm_query_res['products'] : [];
  // Refonte filtrage (Tâche 1) : classement par priorité (couche serveur unique).
  if (function_exists('sapi_conseiller_rank_products')) {
    $imm_products = sapi_conseiller_rank_products($imm_products, $imm_answers);
  }
?>
<!-- Track de scroll-pinning : le hero (sticky) reste épinglé pendant que le
     scroll pilote la révélation (flou photo + remontée texte + apparition des
     cards), réversible. La hauteur du track = durée de l'animation. -->
<div class="mescreations-immersion-track" data-immersion-track>
<section class="mescreations-immersion" data-immersion data-immersion-piece="<?php echo esc_attr($imm_piece); ?>" aria-label="<?php echo esc_attr(sprintf(__('Ma sélection pour %s', 'theme-sapi-maison'), $imm_possessive)); ?>">
  <div class="mescreations-immersion__bg">
    <?php if ($imm_photo_url) : ?>
      <img class="mescreations-immersion__bg-img" src="<?php echo esc_url($imm_photo_url); ?>" alt="" loading="eager" fetchpriority="high">
    <?php else : ?>
      <?php echo sapi_image($imm_generic_fallback, 'full', ['alt' => '', 'class' => 'mescreations-immersion__bg-img', 'loading' => 'eager']); ?>
    <?php endif; ?>
  </div>
  <div class="mescreations-immersion__scrim" aria-hidden="true"></div>

  <!-- Bandeau réassurance : on réutilise le MÊME mécanisme que la home — le
       bandeau global .robin-bandeau est déplacé en JS juste après ce hero et
       reçoit .home-repositioned-bar (sticky sous le header au scroll). Rien
       n'est rendu ici. -->

  <!-- Bloc texte (pill + phrase IA + question) : centré, remonte au scroll. -->
  <div class="mescreations-immersion__inner">
    <!-- Pill Robin V1 (composant partagé, déjà stylé) -->
    <div class="conseiller-sig conseiller-sig--v1 mescreations-immersion__sig" data-immersion-sig>
      <span class="conseiller-sig__avatar"><?php echo sapi_image('2026/03/Robin-face-avec-Alice-lhelice.jpg', 'thumbnail', ['alt' => 'Robin, artisan de l\'Atelier Sâpi', 'class' => 'conseiller-sig__img', 'loading' => 'lazy']); ?></span>
      <span class="conseiller-sig__text">
        <span class="conseiller-sig__who"><?php esc_html_e('Le conseil de Robin', 'theme-sapi-maison'); ?></span>
        <span class="conseiller-sig__hook"><?php echo esc_html(sprintf(__('Mon conseil pour %s', 'theme-sapi-maison'), $imm_possessive)); ?></span>
      </span>
    </div>

    <!-- Phrase de conseil (machine à écrire au load, ne se réécrit jamais).
         Le texte complet est dans data-immersion-phrase-text ; le JS l'écrit. -->
    <p class="mescreations-immersion__phrase" data-immersion-phrase data-immersion-phrase-text="<?php echo esc_attr($imm_phrase); ?>">
      <span class="mescreations-immersion__phrase-content"></span>
    </p>

    <!-- Bouton sous la citation : ouvre la modale Conseiller pour décrire le
         projet en détail (questionnaire complet → produit plus adapté). -->
    <button type="button" class="mescreations-immersion__describe" data-immersion-describe data-action="open-modal" data-modal-state="s0" hidden>
      <?php esc_html_e('Décrire mon projet en détail pour un luminaire plus adapté', 'theme-sapi-maison'); ?>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
    </button>
  </div>

  <!-- Sélection : apparaît au scroll (flou photo), pièce-level rendue CÔTÉ
       SERVEUR + carte sur-mesure. Positionnée dans la partie basse du hero. -->
  <div class="mescreations-immersion__selection" data-immersion-selection>
    <div class="mescreations-immersion__selection-head">
      <span class="mescreations-immersion__selection-title"><?php echo esc_html(sprintf(__('Ma sélection pour %s', 'theme-sapi-maison'), $imm_possessive)); ?></span>
    </div>
    <div class="mescreations-immersion__slider-wrap">
      <button type="button" class="mes-creations-selection__nav-arrow mescreations-immersion__arrow" data-immersion-prev aria-label="<?php esc_attr_e('Précédent', 'theme-sapi-maison'); ?>" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
      </button>
      <div class="mescreations-immersion__slider" data-immersion-slider>
      <?php
      // Card = sapi_immersion_render_product_card() (même rendu que l'endpoint
      // AJAX du « moment 2 » → une seule source pour le markup des cards).
      foreach ($imm_products as $imm_prod) {
        sapi_immersion_render_product_card($imm_prod, $imm_piece);
      }
      ?>

      <!-- Carte sur-mesure en fin de slider -->
      <a class="mescreations-immersion__pcard mescreations-immersion__pcard--sur" href="<?php echo esc_url(home_url('/sur-mesure/')); ?>">
        <span class="mescreations-immersion__sur-eyebrow"><?php esc_html_e('Sur-mesure', 'theme-sapi-maison'); ?></span>
        <span class="mescreations-immersion__sur-title"><?php esc_html_e('Créons ensemble', 'theme-sapi-maison'); ?></span>
        <span class="mescreations-immersion__sur-sub"><?php esc_html_e('Rien ne colle parfaitement ? Robin dessine ton luminaire.', 'theme-sapi-maison'); ?></span>
        <span class="mescreations-immersion__sur-cta">
          <?php esc_html_e('En parler à Robin', 'theme-sapi-maison'); ?>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="14" height="14"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </span>
      </a>
      </div><!-- /.mescreations-immersion__slider -->
      <button type="button" class="mes-creations-selection__nav-arrow mescreations-immersion__arrow" data-immersion-next aria-label="<?php esc_attr_e('Suivant', 'theme-sapi-maison'); ?>" hidden>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
      </button>
    </div><!-- /.mescreations-immersion__slider-wrap -->
  </div>

  <div class="mescreations-immersion__scrollhint" data-immersion-scrollhint aria-hidden="true">
    <!-- Indice 1 : incite à scroller pour révéler — monte et disparaît au scroll. -->
    <span class="mescreations-immersion__hint mescreations-immersion__hint--reveal">
      <span class="mescreations-immersion__hint-label"><?php esc_html_e('Découvre ta sélection', 'theme-sapi-maison'); ?></span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </span>
    <!-- Indice 2 : apparaît une fois la sélection révélée (pause) → catalogue. -->
    <span class="mescreations-immersion__hint mescreations-immersion__hint--catalogue">
      <span class="mescreations-immersion__hint-label"><?php esc_html_e('Voir le catalogue complet', 'theme-sapi-maison'); ?></span>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </span>
  </div>
</section>
</div><!-- /.mescreations-immersion-track -->
<?php } // end état B immersion ?>

<?php if ($imm_piece) : // ── ÉTAT B : hero artisan (h1, caché en CSS sous l'immersion). Zone cards conseiller retirée (Tâche 7, plus de sapi-cards-conseiller.js). ── ?>

<section class="shop-hero-artisan">
  <div class="shop-hero-artisan-inner">
    <h1><?php esc_html_e('Mes créations', 'theme-sapi-maison'); ?></h1>
  </div>
</section>


<?php else : // ── ÉTAT A (sans ?piece=) : le room-picker EST le hero (Tâche 4). ── ?>

<?php
require_once get_template_directory() . '/inc/guide-data.php';
$picker_icons = sapi_guide_get_icons();
?>
<section class="mescreations-picker-hero" data-mes-creations-picker aria-label="<?php esc_attr_e('Choisir une pièce', 'theme-sapi-maison'); ?>">
  <div class="mescreations-picker-hero__inner room-picker-inner">
    <div class="conseiller-sig conseiller-sig--v1">
      <span class="conseiller-sig__avatar"><?php echo sapi_image('2026/03/Robin-face-avec-Alice-lhelice.jpg', 'thumbnail', ['alt' => 'Robin, artisan de l\'Atelier Sâpi', 'class' => 'conseiller-sig__img', 'loading' => 'lazy']); ?></span>
      <span class="conseiller-sig__text">
        <span class="conseiller-sig__who"><?php esc_html_e('Le conseil de Robin', 'theme-sapi-maison'); ?></span>
        <span class="conseiller-sig__hook"><?php esc_html_e('Mon regard d\'artisan sur ton projet', 'theme-sapi-maison'); ?></span>
      </span>
    </div>
    <h1 class="room-picker-title"><?php esc_html_e('Pour quelle pièce cherches-tu un luminaire ?', 'theme-sapi-maison'); ?></h1>
    <p class="room-picker-sub"><?php esc_html_e('Choisis une pièce : je te montre la sélection que je conseille, pensée pour cet espace.', 'theme-sapi-maison'); ?></p>
    <div class="room-picker-cards">
      <?php foreach (sapi_room_choices() as $room) :
        $icon_svg = isset($picker_icons[$room['icon']]) ? $picker_icons[$room['icon']] : '';
      ?>
        <a class="room-card" href="<?php echo esc_url(home_url('/mes-creations/?piece=' . $room['slug'])); ?>" data-piece="<?php echo esc_attr($room['slug']); ?>">
          <span class="room-card-icon"><?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
          <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="room-picker-or" aria-hidden="true">
      <span class="room-picker-or__text"><?php esc_html_e('ou', 'theme-sapi-maison'); ?></span>
    </div>
    <!-- Texte libre : formulaire GET natif → /mes-creations/?freetext=… ; la
         modale s'auto-ouvre en chat sur ce param (sapi-modal-conseiller.js init).
         Aucun JS requis ici. -->
    <form class="room-picker-freetext" method="get" role="search" action="<?php echo esc_url(home_url('/mes-creations/')); ?>">
      <input type="text" class="room-picker-freetext__input" name="freetext"
             placeholder="<?php esc_attr_e('Décris ton projet en quelques mots…', 'theme-sapi-maison'); ?>"
             maxlength="500"
             aria-label="<?php esc_attr_e('Décris ton projet en quelques mots', 'theme-sapi-maison'); ?>">
      <button type="submit" class="room-picker-freetext__submit" aria-label="<?php esc_attr_e('Envoyer', 'theme-sapi-maison'); ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
      </button>
    </form>
  </div>
</section>

<?php endif; // fin état A / état B ?>

<!-- Section "Toutes mes créations" — catalogue complet (1 seule grille DOM,
     source of truth pour les matches qui sont clonés dans la card "Ma sélection"
     via JS). Pills catégorie : Chantier 4 (placeholder visuel pour l'instant). -->
<section class="mes-creations-catalogue" id="mes-creations-catalogue">
  <header class="mes-creations-catalogue__header">
    <h2 class="mes-creations-catalogue__title"><?php esc_html_e('Toutes mes créations', 'theme-sapi-maison'); ?></h2>
    <p class="mes-creations-catalogue__sub"><?php esc_html_e('Le catalogue complet, classé par type de luminaire', 'theme-sapi-maison'); ?></p>
  </header>

  <!-- Pills catégorie (Chantier 3) — filtrage AJAX-less (toutes les cards
       sont déjà dans le DOM, on toggle .is-cat-filtered via JS). URL mise
       à jour via history.pushState. Au reload avec ?product_cat=<slug>,
       la classe is-active est appliquée côté PHP. -->
  <nav class="mes-creations-pills" data-mes-creations-pills aria-label="<?php esc_attr_e('Filtrer par catégorie', 'theme-sapi-maison'); ?>">
    <button type="button" class="mes-creations-pill<?php echo empty($mes_creations_active_cat) ? ' is-active' : ''; ?>" data-cat="all">
      <?php esc_html_e('Tous', 'theme-sapi-maison'); ?>
    </button>
    <?php foreach ($mes_creations_cat_order as $slug) :
      if (!isset($mes_creations_cats_by_slug[$slug])) continue;
      $cat = $mes_creations_cats_by_slug[$slug];
      $is_active = ($mes_creations_active_cat === $slug);
    ?>
      <button type="button" class="mes-creations-pill<?php echo $is_active ? ' is-active' : ''; ?>" data-cat="<?php echo esc_attr($cat->slug); ?>">
        <?php echo esc_html($cat->name); ?>
      </button>
    <?php endforeach; ?>
  </nav>

<!-- Products Grid — grille 2 colonnes photos ambiance -->
<section class="shop-products" id="shop-products">
  <?php if ($all_products->have_posts()) : ?>

    <?php
    /**
     * Sépare un nom de produit en prénom + surnom.
     */
    if (!function_exists('sapi_split_product_name')) {
      function sapi_split_product_name($title) {
        $words = explode(' ', $title, 2);
        return [
          'firstname' => $words[0],
          'surname'   => isset($words[1]) ? $words[1] : '',
        ];
      }
    }
    ?>

    <?php
    // Cards réassurance insérées dans la grille (visibles filtre "Tout" seulement — shop.js gère le masquage)
    $sapi_card_contents = [
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>',
        'title' => '100% artisanal français',
        'text' => 'Chaque luminaire est conçu, découpé et assemblé à la main dans l\'atelier lyonnais de Robin. Pas de production de masse, juste du savoir-faire et de la passion.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>',
        'title' => 'Pièces uniques & originales',
        'text' => 'Chaque modèle est une création originale signée Robin. Vous ne trouverez ces luminaires nulle part ailleurs. Votre intérieur sera unique, comme vous.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
        'title' => 'Bois PEFC & éco-responsable',
        'text' => 'Les bois proviennent de forêts gérées durablement (PEFC). Production locale, emballages recyclables, zéro gaspillage. Beauté et responsabilité vont de pair.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'title' => 'Service client réactif',
        'text' => 'Une question ? Robin est là pour vous accompagner personnellement, du choix à l\'installation. Réponse rapide garantie.',
      ],
      [
        'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'title' => 'Fabriqué avec amour à Lyon',
        'text' => 'Vous recevez bien plus qu\'un objet : vous recevez une histoire, un bout de son atelier, une pièce qui porte son attention aux détails.',
        'highlight' => true,
      ],
    ];
    $sapi_card_zones = [[3, 6], [8, 11], [13, 16], [18, 21], [23, 26]];
    $sapi_text_cards = [];
    foreach ($sapi_card_zones as $i => $zone) {
      if (isset($sapi_card_contents[$i])) {
        $pos = wp_rand($zone[0], $zone[1]);
        $sapi_text_cards[$pos] = $sapi_card_contents[$i];
      }
    }
    $sapi_product_counter = 0;
    ?>

    <div class="product-grid" id="sapi-product-grid">
      <?php
      while ($all_products->have_posts()) :
        $all_products->the_post();
        global $product;
        $product = wc_get_product(get_the_ID());
        $product_id = $product->get_id();

        // Catégories pour le filtrage JS
        $product_cats = get_the_terms($product_id, 'product_cat');
        $cat_slugs = [];
        if ($product_cats && !is_wp_error($product_cats)) {
          foreach ($product_cats as $cat) {
            $cat_slugs[] = $cat->slug;
          }
        }
        $filter_categories = implode(' ', $cat_slugs);

        // Prix pour le filtrage
        $filter_price = $product->get_price();

        // Essence de bois
        $wood_terms = wc_get_product_terms($product_id, 'pa_essence-de-bois');
        $wood_essence = !empty($wood_terms) ? $wood_terms[0]->name : '';

        // Taille
        $size_dimension = 0;
        $taille_terms = wc_get_product_terms($product_id, 'pa_taille');
        if (!empty($taille_terms)) {
          preg_match('/\d+/', $taille_terms[0]->name, $m);
          if (!empty($m)) $size_dimension = (int) $m[0];
        }

        // Nombre de variations de taille (utile au filtrage taille=spacieuse)
        $taille_variations_count = count($taille_terms);

        // Méga-filtre — format luminaire (boule/horizontal/vertical) et type d'ampoule
        $format_terms = wc_get_product_terms($product_id, 'pa_format');
        $format_slugs = [];
        foreach ($format_terms as $t) { $format_slugs[] = $t->slug; }
        $format_attr = implode(' ', $format_slugs);

        $ampoule_terms = wc_get_product_terms($product_id, 'pa_type-ampoule');
        $ampoule_slugs = [];
        foreach ($ampoule_terms as $t) { $ampoule_slugs[] = $t->slug; }
        $ampoule_attr = implode(' ', $ampoule_slugs);

        // Photo ambiance ACF (sauf accessoires → photo produit WooCommerce)
        $is_accessoire = in_array('accessoires', $cat_slugs);
        $amb_photo_ids = !$is_accessoire ? sapi_get_product_photo_ids($product_id, 'ambiance', 1) : [];
        $ambiance_id = !empty($amb_photo_ids) ? $amb_photo_ids[0] : get_post_thumbnail_id($product_id);

        // Nom splitté
        $name_parts = sapi_split_product_name(get_the_title());

        // Prix min
        $is_variable = $product->is_type('variable');
        $price_min = $is_variable ? $product->get_variation_price('min') : $product->get_price();

        // Data attributes pour le filtrage shop.js
        $data_attrs  = 'data-id="' . esc_attr($product_id) . '"';
        $data_attrs .= ' data-categories="' . esc_attr($filter_categories) . '"';
        $data_attrs .= ' data-name="' . esc_attr(strtolower(get_the_title())) . '"';
        $data_attrs .= ' data-price="' . esc_attr($filter_price) . '"';
        $data_attrs .= $wood_essence ? ' data-wood="' . esc_attr(sanitize_title($wood_essence)) . '"' : '';
        $data_attrs .= $size_dimension > 0 ? ' data-size="' . esc_attr($size_dimension) . '"' : '';
        $data_attrs .= $format_attr ? ' data-format-luminaire="' . esc_attr($format_attr) . '"' : '';
        $data_attrs .= $ampoule_attr ? ' data-type-ampoule="' . esc_attr($ampoule_attr) . '"' : '';
        $data_attrs .= ' data-size-variations="' . esc_attr($taille_variations_count) . '"';
      ?>
        <?php
          // Catégorie affichée (singulier)
          $display_cat = '';
          if ($product_cats && !is_wp_error($product_cats)) {
            foreach ($product_cats as $cat) {
              if ($cat->slug !== 'uncategorized') {
                $display_cat = str_replace(
                  ['Suspensions', 'Appliques', 'Lampadaires', 'Lampes à poser'],
                  ['Suspension',  'Applique',  'Lampadaire',  'Lampe à poser'],
                  $cat->name
                );
                break;
              }
            }
          }

          // Hover image (1re photo galerie WooCommerce)
          $gallery_ids = $product->get_gallery_image_ids();
          $hover_id = !empty($gallery_ids) ? $gallery_ids[0] : 0;

          // Prix HTML
          $price_html = $is_variable ? wc_price($price_min) : $product->get_price_html();
        ?>
        <div class="product-card-cinetique" data-product-id="<?php echo esc_attr($product_id); ?>" <?php echo $data_attrs; ?><?php echo !$is_accessoire ? ' data-piece-swap data-piece-swap-type="ambiance" data-piece-swap-size="large"' : ''; ?>>
          <a href="<?php echo esc_url(get_permalink($product_id)); ?>" class="product-card-link">
            <div class="product-media<?php echo $hover_id ? ' has-hover-image' : ''; ?>">
              <?php if ($ambiance_id) : ?>
                <span class="product-image-main"><?php echo wp_get_attachment_image($ambiance_id, 'large', false, ['alt' => get_the_title(), 'loading' => 'lazy']); ?></span>
              <?php else : ?>
                <span class="product-image-main"><?php echo woocommerce_get_product_thumbnail('woocommerce_thumbnail'); ?></span>
              <?php endif; ?>
              <?php if ($hover_id) : ?>
                <span class="product-image-hover"><?php echo wp_get_attachment_image($hover_id, 'woocommerce_thumbnail', false, ['alt' => get_the_title() . ' - ambiance', 'loading' => 'lazy']); ?></span>
              <?php endif; ?>
            </div>

            <div class="product-info">
              <h3 class="product-name"><?php echo esc_html(get_the_title()); ?></h3>
              <?php if ($display_cat) : ?>
                <p class="product-category"><?php echo esc_html($display_cat); ?></p>
              <?php endif; ?>
              <div class="product-price">
                <?php if ($is_variable) : ?>
                  <span class="price-from"><?php esc_html_e('À partir de', 'theme-sapi-maison'); ?></span>
                <?php endif; ?>
                <span class="price-value"><?php echo $price_html; ?></span>
              </div>
            </div>

            <div class="product-actions">
              <span class="btn-view">
                <?php esc_html_e('Découvrir', 'theme-sapi-maison'); ?> ⇾
              </span>
            </div>
          </a>
        </div>
      <?php
        $sapi_product_counter++;
        if (isset($sapi_text_cards[$sapi_product_counter])) :
          $card = $sapi_text_cards[$sapi_product_counter];
          $card_class = 'product-text-card';
          if (!empty($card['highlight'])) $card_class .= ' product-text-card--highlight';
        ?>
        <div class="<?php echo esc_attr($card_class); ?>">
          <div class="product-text-card-inner">
            <div class="product-text-card-icon"><?php echo $card['icon']; ?></div>
            <h3><?php echo esc_html($card['title']); ?></h3>
            <p><?php echo esc_html($card['text']); ?></p>
            <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="product-text-card-discover">En savoir plus</a>
          </div>
        </div>
        <?php endif; ?>

      <?php endwhile; ?>
    </div>

    <!-- Grosse card récap "Pourquoi choisir Sâpi" — visible uniquement avec filtres actifs (shop.js) -->
    <div class="why-sapi-recap" style="display: none;">
      <div class="why-sapi-recap-inner">
        <h2>Pourquoi choisir l'Atelier Sâpi ?</h2>
        <div class="why-sapi-recap-grid">
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
            <h3>100% artisanal français</h3>
            <p>Chaque luminaire est conçu, découpé et assemblé à la main dans l'atelier lyonnais de Robin.</p>
          </div>
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></div>
            <h3>Pièces uniques & originales</h3>
            <p>Chaque modèle est une création originale signée Robin. Votre intérieur sera unique, comme vous.</p>
          </div>
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <h3>Bois PEFC & éco-responsable</h3>
            <p>Les bois proviennent de forêts gérées durablement (PEFC). Beauté et responsabilité vont de pair.</p>
          </div>
          <div class="why-sapi-recap-item">
            <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></div>
            <h3>Service client réactif</h3>
            <p>Robin est là pour vous accompagner personnellement, du choix à l'installation.</p>
          </div>
        </div>
        <div class="why-sapi-recap-highlight">
          <div class="product-text-card-icon"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg></div>
          <div>
            <h3>Fabriqué avec amour à Lyon</h3>
            <p>Vous recevez bien plus qu'un objet : vous recevez une histoire, un bout de l'atelier de Robin, une pièce qui porte son attention aux détails.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty-state "aucun produit" pour le filtrage JS côté client.
         Cas extrême après l'élargissement progressif : aucun produit catalogue
         ne match même avec toutes les contraintes relâchées sauf sortie.
         CTA sur-mesure pour donner une issue concrète au visiteur. -->
    <div class="woocommerce-no-products-found shop-empty-state" style="display: none;">
      <p class="shop-empty-state__text">
        <?php esc_html_e('Aucun modèle de notre catalogue ne correspond à ce projet.', 'theme-sapi-maison'); ?>
      </p>
      <p class="shop-empty-state__subtext">
        <?php esc_html_e('Robin peut imaginer un luminaire sur-mesure pour ton projet.', 'theme-sapi-maison'); ?>
      </p>
      <a class="shop-empty-state__cta" href="<?php echo esc_url(home_url('/sur-mesure/')); ?>">
        <?php esc_html_e('Découvrir le sur-mesure', 'theme-sapi-maison'); ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>

  <?php else : ?>

    <div class="woocommerce-no-products-found shop-empty-state">
      <p class="shop-empty-state__text">
        <?php esc_html_e('Aucun modèle de notre catalogue ne correspond à ce projet.', 'theme-sapi-maison'); ?>
      </p>
      <p class="shop-empty-state__subtext">
        <?php esc_html_e('Robin peut imaginer un luminaire sur-mesure pour ton projet.', 'theme-sapi-maison'); ?>
      </p>
      <a class="shop-empty-state__cta" href="<?php echo esc_url(home_url('/sur-mesure/')); ?>">
        <?php esc_html_e('Découvrir le sur-mesure', 'theme-sapi-maison'); ?>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" width="16" height="16"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </a>
    </div>

  <?php endif; ?>
  <?php wp_reset_postdata(); ?>
</section>

</section><!-- /.mes-creations-catalogue (wrap "Toutes mes créations") -->

<!-- Outro Section with CTA -->
<section class="shop-outro">
  <div class="shop-outro-content">
    <p class="shop-outro-text">
      <?php esc_html_e('Vous ne trouvez pas votre bonheur ?', 'theme-sapi-maison'); ?>
    </p>
    <p class="shop-outro-subtitle">
      <?php esc_html_e('Dites à Robin ce que vous imaginez et créez ensemble votre luminaire sur-mesure.', 'theme-sapi-maison'); ?>
    </p>
    <a href="<?php echo esc_url(home_url('/sur-mesure/')); ?>" class="shop-outro-cta">
      <?php esc_html_e('Découvrir le sur mesure', 'theme-sapi-maison'); ?>
    </a>
  </div>
</section>

<?php
// F2b : la modale Conseiller V3 a été extraite vers sapi_render_conseiller_modal()
// (functions.php) et hookée sur wp_footer pour être partagée avec la fiche produit.

get_footer();

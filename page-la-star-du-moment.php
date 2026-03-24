<?php
/**
 * Template Name: La Star du moment
 *
 * Page showcase dédiée à un produit vedette.
 * Le produit est sélectionné via un champ ACF "produit_star" (Post Object).
 *
 * @package Sapi-Maison
 */

defined('ABSPATH') || exit;
get_header();

// Récupérer le produit star via ACF
$star_product = null;
$star_id = 0;
if (function_exists('get_field')) {
  $star_post = get_field('produit_star');
  if ($star_post) {
    $star_id = is_object($star_post) ? $star_post->ID : (int) $star_post;
    $star_product = wc_get_product($star_id);
  }
}

if (!$star_product) :
?>
  <div class="star-empty" style="padding:4rem 2rem; text-align:center;">
    <p>Aucun produit star sélectionné. Ajoutez un champ ACF <code>produit_star</code> (Post Object) sur cette page.</p>
  </div>
<?php
  get_footer();
  return;
endif;

// Données du produit
$name       = $star_product->get_name();
$price      = $star_product->get_price_html();
$permalink  = get_permalink($star_id);
$short_desc = $star_product->get_short_description();

// ACF fields
$has_acf          = function_exists('get_field');
$phrase_accroche  = $has_acf ? get_field('phrase_daccroche', $star_id) : '';
$mini_desc        = $has_acf ? get_field('mini_description', $star_id) : '';
$pourquoi         = $has_acf ? get_field('pourquoi_cette_piece', $star_id) : '';
$descriptif       = $has_acf ? get_field('descriptif', $star_id) : '';

// Photos ACF
$ambiance_1 = $has_acf ? sapi_get_acf_image_url(get_field('ambiance_1', $star_id)) : '';
$ambiance_2 = $has_acf ? sapi_get_acf_image_url(get_field('ambiance_2', $star_id)) : '';
$ambiance_3 = $has_acf ? sapi_get_acf_image_url(get_field('ambiance_3', $star_id)) : '';
$bandeau    = $has_acf ? sapi_get_acf_image_url(get_field('bandeau', $star_id)) : '';
$detail_1   = $has_acf ? sapi_get_acf_image_url(get_field('detail_1', $star_id)) : '';
$detail_2   = $has_acf ? sapi_get_acf_image_url(get_field('detail_2', $star_id)) : '';

// Photo principale produit
$main_image_id  = $star_product->get_image_id();
$main_image_url = $main_image_id ? wp_get_attachment_image_url($main_image_id, 'full') : wc_placeholder_img_src('full');

// Galerie produit
$gallery_ids  = $star_product->get_gallery_image_ids();
$gallery_urls = [];
foreach ($gallery_ids as $gid) {
  $url = wp_get_attachment_image_url($gid, 'full');
  if ($url) $gallery_urls[] = $url;
}

// Toutes les photos pour la galerie (ambiance + principale + galerie)
$all_photos = array_filter(array_merge(
  [$ambiance_1, $ambiance_2, $ambiance_3],
  [$main_image_url],
  $gallery_urls
));

// Hero = ambiance_1 ou image principale
$hero_url = $ambiance_1 ?: $main_image_url;

// Données techniques
$dimensions = $has_acf ? get_field('dimensions', $star_id) : '';
$bois       = $has_acf ? get_field('bois', $star_id) : '';
$finition   = $has_acf ? get_field('finition', $star_id) : '';
$culot      = $has_acf ? get_field('culot', $star_id) : '';
$poids      = $has_acf ? get_field('poids', $star_id) : '';

?>

<!-- ========== HERO PLEIN ÉCRAN ========== -->
<section class="star-hero">
  <img
    class="star-hero__bg"
    src="<?php echo esc_url($hero_url); ?>"
    alt="<?php echo esc_attr($name); ?> - Ambiance"
    fetchpriority="high"
  />
  <div class="star-hero__overlay"></div>
  <div class="star-hero__content">
    <span class="star-hero__badge">La star du moment</span>
    <h1 class="star-hero__title product-name"><?php echo esc_html($name); ?></h1>
    <?php if ($phrase_accroche) : ?>
      <p class="star-hero__accroche"><?php echo esc_html($phrase_accroche); ?></p>
    <?php endif; ?>
    <a href="#star-galerie" class="star-hero__scroll">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <polyline points="19 12 12 19 5 12"></polyline>
      </svg>
    </a>
  </div>
</section>

<!-- ========== GALERIE IMMERSIVE ========== -->
<section class="star-galerie" id="star-galerie">

  <?php if ($mini_desc || $descriptif) : ?>
  <div class="star-intro">
    <?php if ($mini_desc) : ?>
      <p class="star-intro__mini"><?php echo esc_html($mini_desc); ?></p>
    <?php endif; ?>
    <?php if ($descriptif) : ?>
      <div class="star-intro__text"><?php echo wp_kses_post($descriptif); ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Mosaïque de photos -->
  <div class="star-mosaic">
    <?php
    // Toutes les photos : ACF + produit principale + galerie WooCommerce
    // On évite les doublons via les URLs
    $seen_urls = [];
    $all_mosaic = [];

    // 1. Photos ACF (ordre éditorial)
    $acf_candidates = [
      ['url' => $bandeau,    'alt' => 'Bandeau',  'large' => true],
      ['url' => $ambiance_1, 'alt' => 'Ambiance', 'large' => false],
      ['url' => $detail_1,   'alt' => 'Détail',   'large' => false],
      ['url' => $detail_2,   'alt' => 'Détail',   'large' => false],
      ['url' => $ambiance_2, 'alt' => 'Ambiance', 'large' => true],
      ['url' => $ambiance_3, 'alt' => 'Ambiance', 'large' => false],
    ];
    foreach ($acf_candidates as $p) {
      if (!empty($p['url']) && !isset($seen_urls[$p['url']])) {
        $all_mosaic[] = $p;
        $seen_urls[$p['url']] = true;
      }
    }

    // 2. Photo principale produit
    if ($main_image_url && !isset($seen_urls[$main_image_url])) {
      $all_mosaic[] = ['url' => $main_image_url, 'alt' => 'Produit', 'large' => false];
      $seen_urls[$main_image_url] = true;
    }

    // 3. Galerie WooCommerce
    foreach ($gallery_urls as $gurl) {
      if (!isset($seen_urls[$gurl])) {
        $all_mosaic[] = ['url' => $gurl, 'alt' => 'Galerie', 'large' => false];
        $seen_urls[$gurl] = true;
      }
    }

    foreach ($all_mosaic as $photo) :
      $large_class = $photo['large'] ? ' star-mosaic__item--large' : '';
    ?>
    <div class="star-mosaic__item<?php echo $large_class; ?>">
      <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($name . ' - ' . $photo['alt']); ?>" loading="lazy" />
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- ========== POURQUOI CETTE PIÈCE ========== -->
<?php if ($pourquoi) : ?>
<section class="star-pourquoi">
  <div class="star-pourquoi__inner">
    <h2>Pourquoi cette pièce ?</h2>
    <div class="star-pourquoi__text"><?php echo wp_kses_post($pourquoi); ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ========== FICHE TECHNIQUE ========== -->
<?php if ($dimensions || $bois || $finition || $culot) : ?>
<section class="star-specs">
  <div class="star-specs__inner">
    <h2>Fiche technique</h2>
    <div class="star-specs__grid">
      <?php if ($dimensions) : ?>
      <div class="star-specs__item">
        <span class="star-specs__label">Dimensions</span>
        <span class="star-specs__value"><?php echo esc_html($dimensions); ?></span>
      </div>
      <?php endif; ?>
      <?php if ($bois) : ?>
      <div class="star-specs__item">
        <span class="star-specs__label">Bois</span>
        <span class="star-specs__value"><?php echo esc_html($bois); ?></span>
      </div>
      <?php endif; ?>
      <?php if ($finition) : ?>
      <div class="star-specs__item">
        <span class="star-specs__label">Finition</span>
        <span class="star-specs__value"><?php echo esc_html($finition); ?></span>
      </div>
      <?php endif; ?>
      <?php if ($culot) : ?>
      <div class="star-specs__item">
        <span class="star-specs__label">Culot</span>
        <span class="star-specs__value"><?php echo esc_html($culot); ?></span>
      </div>
      <?php endif; ?>
      <?php if ($poids) : ?>
      <div class="star-specs__item">
        <span class="star-specs__label">Poids</span>
        <span class="star-specs__value"><?php echo esc_html($poids); ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ========== CTA FINAL ========== -->
<section class="star-cta">
  <div class="star-cta__inner">
    <h2 class="star-cta__title product-name"><?php echo esc_html($name); ?></h2>
    <div class="star-cta__price"><?php echo $price; ?></div>
    <a href="<?php echo esc_url($permalink); ?>" class="star-cta__btn">
      Découvrir ce luminaire
    </a>
    <a href="<?php echo esc_url(home_url('/mes-creations/')); ?>" class="star-cta__link">
      Voir toutes mes créations
    </a>
  </div>
</section>

<?php get_footer(); ?>

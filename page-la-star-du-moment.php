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
$description = $star_product->get_description();
$short_desc  = $star_product->get_short_description();

// ACF fields
$has_acf          = function_exists('get_field');
$phrase_accroche  = $has_acf ? get_field('phrase_daccroche', $star_id) : '';
$mini_desc        = $has_acf ? get_field('mini_description', $star_id) : '';
$pourquoi         = $has_acf ? get_field('pourquoi_cette_piece', $star_id) : '';
$descriptif       = $has_acf ? (get_field('Descriptif', $star_id) ?: get_field('descriptif', $star_id)) : '';

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

<!-- ========== PRÉSENTATION ========== -->
<?php
// Accroche : phrase_daccroche ou mini_description
$accroche = $phrase_accroche ?: $mini_desc;
// Texte principal : description longue WooCommerce
$texte_principal = $description ?: $short_desc;
if ($accroche || $texte_principal || $descriptif) :
?>
<section class="star-presentation">
  <div class="star-presentation__inner">
    <?php if ($accroche) : ?>
      <p class="star-presentation__accroche"><?php echo esc_html($accroche); ?></p>
    <?php endif; ?>
    <?php if ($texte_principal) : ?>
      <div class="star-presentation__desc"><?php echo wp_kses_post($texte_principal); ?></div>
    <?php endif; ?>
    <a href="<?php echo esc_url($permalink); ?>" class="star-presentation__link">Voir la fiche complète &rarr;</a>
  </div>
</section>
<?php endif; ?>

<!-- ========== GALERIE — CARROUSEL HORIZONTAL ========== -->
<section class="star-galerie" id="star-galerie">
  <?php
  // Collecter toutes les photos (dédoublonnées)
  $seen_urls = [];
  $all_photos = [];

  $acf_candidates = [
    ['url' => $bandeau,    'alt' => 'Bandeau'],
    ['url' => $ambiance_1, 'alt' => 'Ambiance'],
    ['url' => $detail_1,   'alt' => 'Détail'],
    ['url' => $detail_2,   'alt' => 'Détail'],
    ['url' => $ambiance_2, 'alt' => 'Ambiance'],
    ['url' => $ambiance_3, 'alt' => 'Ambiance'],
  ];
  foreach ($acf_candidates as $p) {
    if (!empty($p['url']) && !isset($seen_urls[$p['url']])) {
      $all_photos[] = $p;
      $seen_urls[$p['url']] = true;
    }
  }
  if ($main_image_url && !isset($seen_urls[$main_image_url])) {
    $all_photos[] = ['url' => $main_image_url, 'alt' => 'Produit'];
    $seen_urls[$main_image_url] = true;
  }
  foreach ($gallery_urls as $gurl) {
    if (!isset($seen_urls[$gurl])) {
      $all_photos[] = ['url' => $gurl, 'alt' => 'Galerie'];
      $seen_urls[$gurl] = true;
    }
  }

  // Séparer en 2 groupes pour intercaler les interludes
  $mid = (int) ceil(count($all_photos) / 2);
  $group1 = array_slice($all_photos, 0, $mid);
  $group2 = array_slice($all_photos, $mid);
  ?>

  <!-- Carrousel 1 -->
  <div class="star-carousel" id="star-carousel-1">
    <div class="star-carousel__track">
      <?php foreach ($group1 as $photo) : ?>
      <div class="star-carousel__slide">
        <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($name . ' - ' . $photo['alt']); ?>" loading="lazy" />
      </div>
      <?php endforeach; ?>
    </div>
    <button class="star-carousel__arrow star-carousel__arrow--prev" aria-label="Précédent">&#8249;</button>
    <button class="star-carousel__arrow star-carousel__arrow--next" aria-label="Suivant">&#8250;</button>
  </div>

  <!-- Interlude : descriptif -->
  <?php if ($descriptif) : ?>
  <div class="star-interlude">
    <div class="star-interlude__inner">
      <h2 class="star-interlude__title">En détail</h2>
      <div class="star-descriptif__card"><?php echo wp_kses_post($descriptif); ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Interlude : storytelling artisanat -->
  <div class="star-interlude">
    <div class="star-interlude__inner star-storytelling__card">
      <div class="star-storytelling__icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
      </div>
      <h3>100% artisanal</h3>
      <p>Conçu, découpé au laser et assemblé à la main par Robin dans son atelier lyonnais. Bois issu de forêts gérées durablement (PEFC).</p>
      <a href="<?php echo esc_url(home_url('/lumiere-dartisan/')); ?>" class="star-storytelling__link">Découvrir l'atelier</a>
    </div>
  </div>

  <!-- Carrousel 2 -->
  <?php if (!empty($group2)) : ?>
  <div class="star-carousel star-carousel--small" id="star-carousel-2">
    <div class="star-carousel__track">
      <?php foreach ($group2 as $photo) : ?>
      <div class="star-carousel__slide">
        <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($name . ' - ' . $photo['alt']); ?>" loading="lazy" />
      </div>
      <?php endforeach; ?>
    </div>
    <button class="star-carousel__arrow star-carousel__arrow--prev" aria-label="Précédent">&#8249;</button>
    <button class="star-carousel__arrow star-carousel__arrow--next" aria-label="Suivant">&#8250;</button>
  </div>
  <?php endif; ?>

  <!-- Interlude : accompagnement -->
  <div class="star-interlude">
    <div class="star-interlude__inner star-storytelling__card">
      <div class="star-storytelling__icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
      </div>
      <h3>Un accompagnement personnel</h3>
      <p>Une question sur ce modèle ? Robin vous accompagne personnellement, du choix de l'essence à l'installation.</p>
      <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="star-storytelling__link">Contacter Robin</a>
    </div>
  </div>
</section>

<!-- Lightbox plein écran -->
<div class="star-lightbox" id="star-lightbox" aria-hidden="true" role="dialog">
  <div class="star-lightbox__overlay"></div>
  <button class="star-lightbox__close" aria-label="Fermer">&times;</button>
  <button class="star-lightbox__arrow star-lightbox__arrow--prev" aria-label="Précédente">&#8249;</button>
  <img class="star-lightbox__img" src="" alt="" />
  <button class="star-lightbox__arrow star-lightbox__arrow--next" aria-label="Suivante">&#8250;</button>
  <div class="star-lightbox__counter"></div>
</div>

<!-- Carrousel + Lightbox JS -->
<script>
(function() {
  // Carrousels : flèches — centrer la slide suivante/précédente
  document.querySelectorAll('.star-carousel').forEach(function(carousel) {
    var track = carousel.querySelector('.star-carousel__track');
    var prev = carousel.querySelector('.star-carousel__arrow--prev');
    var next = carousel.querySelector('.star-carousel__arrow--next');
    if (!track || !prev || !next) return;

    var slides = Array.from(track.querySelectorAll('.star-carousel__slide'));
    var currentIdx = 0;

    function getCenterIndex() {
      // Trouver la slide la plus proche du centre du track
      var trackCenter = track.scrollLeft + track.offsetWidth / 2;
      var closest = 0;
      var closestDist = Infinity;
      slides.forEach(function(slide, i) {
        var slideCenter = slide.offsetLeft - track.offsetLeft + slide.offsetWidth / 2;
        var dist = Math.abs(trackCenter - slideCenter);
        if (dist < closestDist) {
          closestDist = dist;
          closest = i;
        }
      });
      return closest;
    }

    function scrollToSlide(idx) {
      if (idx < 0) idx = 0;
      if (idx >= slides.length) idx = slides.length - 1;
      currentIdx = idx;
      var slide = slides[idx];
      var slideCenter = slide.offsetLeft - track.offsetLeft + slide.offsetWidth / 2;
      var trackCenter = track.offsetWidth / 2;
      track.scrollTo({ left: slideCenter - trackCenter, behavior: 'smooth' });
    }

    prev.addEventListener('click', function() {
      scrollToSlide(getCenterIndex() - 1);
    });
    next.addEventListener('click', function() {
      scrollToSlide(getCenterIndex() + 1);
    });
  });

  // Lightbox
  var lightbox = document.getElementById('star-lightbox');
  if (!lightbox) return;

  var lbImg = lightbox.querySelector('.star-lightbox__img');
  var lbCounter = lightbox.querySelector('.star-lightbox__counter');
  var allSlides = Array.from(document.querySelectorAll('.star-carousel__slide img'));
  var currentIdx = 0;

  function openLightbox(idx) {
    currentIdx = idx;
    updateLightbox();
    lightbox.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function updateLightbox() {
    if (!allSlides[currentIdx]) return;
    lbImg.src = allSlides[currentIdx].src;
    lbImg.alt = allSlides[currentIdx].alt;
    lbCounter.textContent = (currentIdx + 1) + ' / ' + allSlides.length;
  }

  // Clic sur les slides
  allSlides.forEach(function(img, i) {
    img.style.cursor = 'pointer';
    img.addEventListener('click', function() { openLightbox(i); });
  });

  // Contrôles lightbox
  lightbox.querySelector('.star-lightbox__close').addEventListener('click', closeLightbox);
  lightbox.querySelector('.star-lightbox__overlay').addEventListener('click', closeLightbox);
  lightbox.querySelector('.star-lightbox__arrow--prev').addEventListener('click', function() {
    currentIdx = (currentIdx - 1 + allSlides.length) % allSlides.length;
    updateLightbox();
  });
  lightbox.querySelector('.star-lightbox__arrow--next').addEventListener('click', function() {
    currentIdx = (currentIdx + 1) % allSlides.length;
    updateLightbox();
  });

  // Clavier
  document.addEventListener('keydown', function(e) {
    if (lightbox.getAttribute('aria-hidden') !== 'false') return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') { currentIdx = (currentIdx - 1 + allSlides.length) % allSlides.length; updateLightbox(); }
    if (e.key === 'ArrowRight') { currentIdx = (currentIdx + 1) % allSlides.length; updateLightbox(); }
  });
})();
</script>

<!-- ========== POURQUOI CETTE PIÈCE ========== -->
<?php if ($pourquoi) : ?>
<section class="star-pourquoi">
  <div class="star-pourquoi__inner">
    <h2>Pourquoi cette pièce ?</h2>
    <div class="star-pourquoi__text"><?php echo wp_kses_post($pourquoi); ?></div>
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

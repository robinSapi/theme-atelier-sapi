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

<!-- ========== GALERIE IMMERSIVE ========== -->
<section class="star-galerie" id="star-galerie">
  <?php
  // Séparer photos ACF (plein écran) et galerie (carrées, à pairer avec texte)
  $seen_urls = [];
  $acf_photos = [];
  $gallery_photos = [];

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
      $acf_photos[] = $p;
      $seen_urls[$p['url']] = true;
    }
  }
  if ($main_image_url && !isset($seen_urls[$main_image_url])) {
    $gallery_photos[] = ['url' => $main_image_url, 'alt' => 'Produit'];
    $seen_urls[$main_image_url] = true;
  }
  foreach ($gallery_urls as $gurl) {
    if (!isset($seen_urls[$gurl])) {
      $gallery_photos[] = ['url' => $gurl, 'alt' => 'Galerie'];
      $seen_urls[$gurl] = true;
    }
  }

  // Sections texte à pairer avec les photos galerie
  $text_sections = [];
  if ($descriptif) {
    $text_sections[] = ['type' => 'descriptif', 'content' => $descriptif];
  }
  $text_sections[] = [
    'type' => 'storytelling',
    'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>',
    'title' => '100% artisanal',
    'text' => 'Conçu, découpé au laser et assemblé à la main par Robin dans son atelier lyonnais. Bois issu de forêts gérées durablement (PEFC).',
    'link' => home_url('/lumiere-dartisan/'),
    'link_label' => 'Découvrir l\'atelier',
  ];
  $text_sections[] = [
    'type' => 'storytelling',
    'icon' => '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
    'title' => 'Un accompagnement personnel',
    'text' => 'Une question sur ce modèle ? Robin vous accompagne personnellement, du choix de l\'essence à l\'installation.',
    'link' => home_url('/contact/'),
    'link_label' => 'Contacter Robin',
  ];

  // Rendu : alterner photos ACF plein écran et duos galerie+texte
  $acf_i = 0;
  $gal_i = 0;
  $txt_i = 0;
  $total_acf = count($acf_photos);

  // Afficher les 2 premières photos ACF
  while ($acf_i < min(2, $total_acf)) :
    $photo = $acf_photos[$acf_i]; $acf_i++;
  ?>
  <div class="star-frame">
    <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($name . ' - ' . $photo['alt']); ?>" loading="lazy" />
  </div>
  <?php endwhile; ?>

  <?php
  // Alterner : duo (galerie + texte), puis photo ACF, puis duo, etc.
  while ($acf_i < $total_acf || $gal_i < count($gallery_photos) || $txt_i < count($text_sections)) :

    // Duo : photo galerie + section texte côte à côte
    if ($gal_i < count($gallery_photos) && $txt_i < count($text_sections)) :
      $gphoto = $gallery_photos[$gal_i]; $gal_i++;
      $text = $text_sections[$txt_i]; $txt_i++;
      $reverse = ($txt_i % 2 === 0) ? ' star-duo--reverse' : '';
  ?>
  <div class="star-duo<?php echo $reverse; ?>">
    <div class="star-duo__photo">
      <img src="<?php echo esc_url($gphoto['url']); ?>" alt="<?php echo esc_attr($name . ' - ' . $gphoto['alt']); ?>" loading="lazy" />
    </div>
    <div class="star-duo__text">
      <?php if ($text['type'] === 'descriptif') : ?>
        <h2 class="star-duo__title">En détail</h2>
        <div class="star-descriptif__card"><?php echo wp_kses_post($text['content']); ?></div>
      <?php else : ?>
        <div class="star-storytelling__card">
          <div class="star-storytelling__icon"><?php echo $text['icon']; ?></div>
          <h3><?php echo esc_html($text['title']); ?></h3>
          <p><?php echo esc_html($text['text']); ?></p>
          <a href="<?php echo esc_url($text['link']); ?>" class="star-storytelling__link"><?php echo esc_html($text['link_label']); ?></a>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <?php
    endif;

    // Photo ACF plein écran entre les duos
    if ($acf_i < $total_acf) :
      $photo = $acf_photos[$acf_i]; $acf_i++;
  ?>
  <div class="star-frame">
    <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($name . ' - ' . $photo['alt']); ?>" loading="lazy" />
  </div>
  <?php
    endif;

    // Photos galerie restantes sans texte = en duo simple
    if ($txt_i >= count($text_sections) && $gal_i < count($gallery_photos)) :
      $gphoto = $gallery_photos[$gal_i]; $gal_i++;
  ?>
  <div class="star-frame star-frame--small">
    <img src="<?php echo esc_url($gphoto['url']); ?>" alt="<?php echo esc_attr($name . ' - ' . $gphoto['alt']); ?>" loading="lazy" />
  </div>
  <?php
    endif;

  endwhile;
  ?>
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

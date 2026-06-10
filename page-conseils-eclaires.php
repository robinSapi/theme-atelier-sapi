<?php
/*
Template Name: Conseils eclaires
*/
get_header();

$has_acf = function_exists('get_field');

// Room picker data (même que homepage)
require_once get_template_directory() . '/inc/guide-data.php';
$room_choices = [
  ['label' => 'Salon',   'slug' => 'salon',    'icon' => 'sofa'],
  ['label' => 'Cuisine', 'slug' => 'cuisine',  'icon' => 'dining'],
  ['label' => 'Chambre', 'slug' => 'chambre',  'icon' => 'bed'],
  ['label' => 'Chambre enfant', 'slug' => 'chambre-enfant', 'icon' => 'teddy'],
  ['label' => 'Bureau',  'slug' => 'bureau',   'icon' => 'monitor'],
  ['label' => 'Entrée',  'slug' => 'entree',   'icon' => 'door'],
  ['label' => 'Escalier','slug' => 'escalier', 'icon' => 'stairs'],
];
$room_icons = sapi_guide_get_icons();

$tips = [];
for ($i = 1; $i <= 4; $i++) {
    $image_field = $has_acf ? get_field("tip_{$i}_picture") : false;
    $image_url = sapi_get_acf_image_url($image_field);

    $tips[] = [
        'number'  => str_pad($i, 2, '0', STR_PAD_LEFT),
        'title'   => $has_acf ? get_field("tip_{$i}_title") : '',
        'image'   => $image_url,
        'summary' => $has_acf ? get_field("tip_{$i}_summary") : '',
        'content' => $has_acf ? get_field("tip_{$i}_content") : '',
    ];
}
?>

<!-- 1. Hero -->
<section class="advice-hero-artisan">
  <?php echo sapi_image('2025/03/Sapi-header_idees.jpg', 'full', ['alt' => 'Conseils éclairage — Atelier Sâpi', 'class' => 'advice-hero-artisan-img']); ?>
  <div class="advice-hero-artisan-inner">
    <h1>Conseils éclairés</h1>
    <p>Suspensions ou lampadaire ? Quelle ampoule choisir ? Retrouvez ici les infos idéales pour une décoration réussie !</p>
  </div>
</section>

<!-- 3. Cartes conseils -->
<section class="advice-tips-section">
  <div class="advice-tips-grid">
    <?php foreach ($tips as $i => $tip) : ?>
    <div class="advice-tip" data-tip="<?php echo esc_attr($i); ?>">
      <div class="advice-tip-flipper">
        <!-- Face avant -->
        <div class="advice-tip-front">
          <div class="advice-tip-image">
            <img src="<?php echo esc_url($tip['image']); ?>" alt="<?php echo esc_attr($tip['title']); ?>" class="advice-tip-image-img" loading="lazy">
            <div class="advice-tip-overlay"></div>
            <div class="advice-tip-content">
              <span class="advice-tip-number"><?php echo esc_html($tip['number']); ?></span>
              <h2><?php echo esc_html($tip['title']); ?></h2>
              <button class="advice-tip-btn">Voir le conseil</button>
            </div>
          </div>
        </div>
        <!-- Face arrière (mobile flip) -->
        <div class="advice-tip-back">
          <div class="advice-tip-back-inner">
            <p class="advice-tip-back-text"><?php echo esc_html($tip['summary']); ?></p>
            <div class="advice-tip-back-buttons">
              <button class="advice-tip-back-close" aria-label="Fermer">&times;</button>
              <button class="advice-tip-back-more" data-tip="<?php echo esc_attr($i); ?>">En savoir plus</button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Overlay plein écran (utilisé par les cartes conseils) -->
<div class="advice-overlay" aria-hidden="true">
  <div class="advice-overlay-inner">
    <button class="advice-overlay-close" aria-label="Fermer">&times;</button>
    <div class="advice-overlay-body"></div>
  </div>
</div>

<!-- 4. Citation Robin -->
<section class="advice-outro">
  <p>Éclairer une pièce, c'est un peu comme choisir la bonne sauce pour ses pâtes : tout est une question de préférence et de dosage !</p>
  <span class="advice-outro-signature">Robin, créateur à l'Atelier Sâpi</span>
</section>

<!-- Script cartes conseils -->
<script>
(function() {
  'use strict';

  var contents = <?php echo json_encode(array_map(function($t) { return $t['content']; }, $tips)); ?>;

  var overlay = document.querySelector('.advice-overlay');
  var overlayBody = document.querySelector('.advice-overlay-body');
  var overlayClose = document.querySelector('.advice-overlay-close');

  function closeFlip(tip) {
    tip.classList.remove('is-flipped');
  }

  function openOverlay(tipIndex) {
    overlayBody.innerHTML = contents[tipIndex];
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeOverlay() {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  /* Clic n'importe où sur la face avant → flip la card */
  document.querySelectorAll('.advice-tip-front').forEach(function(front) {
    front.addEventListener('click', function() {
      var tip = this.closest('.advice-tip');
      document.querySelectorAll('.advice-tip').forEach(function(t) {
        if (t !== tip) closeFlip(t);
      });
      var backFace = tip.querySelector('.advice-tip-back');
      backFace.classList.add('no-touch');
      tip.classList.add('is-flipped');
      setTimeout(function() {
        backFace.classList.remove('no-touch');
      }, 700);
    });
  });

  /* Bouton croix (face arrière) → retourne la card */
  document.querySelectorAll('.advice-tip-back-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
      closeFlip(this.closest('.advice-tip'));
    });
  });

  /* Bouton "En savoir plus" → ouvre overlay plein écran */
  document.querySelectorAll('.advice-tip-back-more').forEach(function(btn) {
    btn.addEventListener('click', function() {
      openOverlay(this.getAttribute('data-tip'));
    });
  });

  /* Overlay : fermer */
  if (overlayClose) {
    overlayClose.addEventListener('click', closeOverlay);
  }
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeOverlay();
    });
  }

})();
</script>

<!-- 5. Articles blog -->
<?php
$conseils_query = new WP_Query([
  'posts_per_page' => 3,
  'post_status'    => 'publish',
  'category_name'  => 'conseils',
  'orderby'        => 'date',
  'order'          => 'DESC',
]);

if ($conseils_query->have_posts()) :
?>
<section class="blog-grid-section advice-section--warm">
  <div class="blog-grid-container">
    <h2 class="advice-articles-title">Nos derniers conseils</h2>
    <div class="blog-grid">
      <?php while ($conseils_query->have_posts()) : $conseils_query->the_post(); ?>
        <article <?php post_class('blog-grid-card'); ?>>
          <?php if (has_post_thumbnail()) : ?>
            <div class="blog-grid-media">
              <a href="<?php the_permalink(); ?>">
                <?php echo wp_get_attachment_image(get_post_thumbnail_id(), 'large'); ?>
              </a>
            </div>
          <?php endif; ?>
          <div class="blog-grid-content">
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <p><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
            <div class="blog-grid-meta">
              <span class="blog-grid-date"><?php echo esc_html(get_the_date('d/m/Y')); ?></span>
              <a href="<?php the_permalink(); ?>" class="blog-grid-link">Lire →</a>
            </div>
          </div>
        </article>
      <?php endwhile; ?>
    </div>

    <?php
    $conseils_cat = get_category_by_slug('conseils');
    if ($conseils_cat && $conseils_query->found_posts > 3) : ?>
      <div class="advice-articles-more">
        <a href="<?php echo esc_url(get_category_link($conseils_cat)); ?>">Lire tous les articles du blog →</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php
wp_reset_postdata();
endif;
?>

<!-- 6. Room picker — identique à celui de la homepage (titre + 6 pièces +
     séparateur "ou" + champ texte libre). Le wrapper externe garde son
     style propre (max-width + padding adaptés à la page conseils). -->
<section class="advice-room-picker-section">
  <div class="advice-room-picker" data-room-picker>
    <div class="room-picker-inner">
      <div class="conseiller-sig conseiller-sig--v1">
        <span class="conseiller-sig__avatar"><?php echo sapi_image('2026/03/Robin-face-avec-Alice-lhelice.jpg', 'medium', ['alt' => 'Robin, artisan de l\'Atelier Sâpi', 'class' => 'conseiller-sig__img', 'loading' => 'lazy']); ?></span>
        <span class="conseiller-sig__text">
          <span class="conseiller-sig__who">Le conseil de Robin</span>
          <span class="conseiller-sig__hook">Mes conseils spécifiques pour ton projet</span>
        </span>
      </div>
      <h3 class="room-picker-title">Pour quelle pièce cherches-tu un luminaire ?</h3>
      <div class="room-picker-cards">
        <?php foreach ($room_choices as $room) :
          $icon_svg = isset($room_icons[$room['icon']]) ? $room_icons[$room['icon']] : '';
        ?>
          <a class="room-card" href="<?php echo esc_url(home_url('/mes-creations/?piece=' . $room['slug'])); ?>" data-piece="<?php echo esc_attr($room['slug']); ?>">
            <span class="room-card-icon"><?php echo $icon_svg; ?></span>
            <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
          </a>
        <?php endforeach; ?>
      </div>
      <div class="room-picker-or" aria-hidden="true">
        <span class="room-picker-or__text">ou</span>
      </div>
      <form class="room-picker-freetext" data-room-picker-freetext>
        <input type="text" class="room-picker-freetext__input" name="freetext"
               placeholder="Décris ton projet en quelques mots…" maxlength="500"
               aria-label="Décris ton projet en quelques mots">
        <button type="submit" class="room-picker-freetext__submit" aria-label="Envoyer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
        </button>
      </form>
    </div>
  </div>
</section>

<!-- 7. CTA maillage interne → Mes Créations -->
<section class="seo-cta-maillage seo-cta-maillage--button">
  <p>Prêt à passer à l'action ?</p>
  <a href="<?php echo esc_url(home_url('/mes-creations/')); ?>" class="button">Voir toutes les créations</a>
</section>

<?php
get_footer();

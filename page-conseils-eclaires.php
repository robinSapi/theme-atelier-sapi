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

<section class="advice-hero" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/03/Sapi-header_idees.jpg');">
  <div class="advice-hero-overlay"></div>
  <div class="advice-hero-content">
    <h1>Conseils éclairés</h1>
    <p>Suspensions ou lampadaire ? Quelle ampoule choisir ? Retrouvez ici les infos idéales pour une décoration réussie !</p>
  </div>
</section>

<!-- Conseil personnalisé de Robin (shown by mon-projet.js if available) -->
<?php
require_once get_template_directory() . '/inc/template-robin-conseil.php';
sapi_robin_conseil_card( 'conseils' );
?>

<!-- Bouton refresh après modification des réponses (caché par défaut) -->
<div class="conseils-refresh" id="conseils-refresh-btn" style="display:none">
  <button type="button" class="conseils-refresh-btn">Obtenir les conseils de Robin</button>
</div>

<section class="advice-tips-section">
  <div class="advice-tips-grid">
    <?php foreach ($tips as $i => $tip) : ?>
    <div class="advice-tip" data-tip="<?php echo esc_attr($i); ?>">
      <div class="advice-tip-flipper">
        <!-- Face avant -->
        <div class="advice-tip-front">
          <div class="advice-tip-image" style="background-image: url('<?php echo esc_url($tip['image']); ?>');">
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

<!-- Card "Pour quelle pièce" — ouvre la modale Robin -->
<section class="advice-room-picker-section">
  <div class="advice-room-picker">
    <div class="room-picker-inner">
      <span class="robin-modal__badge" style="margin-bottom: 0.5rem;">
        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/></svg>
        Conseil de Robin
      </span>
      <h3 class="room-picker-title">Pour quelle pièce cherchez-vous un luminaire ?</h3>
      <p class="room-picker-sub">Quelques questions et Robin vous guide vers le luminaire idéal</p>
      <div class="room-picker-cards">
        <?php foreach ($room_choices as $room) :
          $icon_svg = isset($room_icons[$room['icon']]) ? $room_icons[$room['icon']] : '';
        ?>
          <button type="button" class="room-card" data-piece="<?php echo esc_attr($room['slug']); ?>" onclick="if(window.sapiRobinOpen)window.sapiRobinOpen('homepage',{piece:this.dataset.piece});">
            <span class="room-card-icon"><?php echo $icon_svg; ?></span>
            <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
          </button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- Overlay plein écran -->
<div class="advice-overlay" aria-hidden="true">
  <div class="advice-overlay-inner">
    <button class="advice-overlay-close" aria-label="Fermer">&times;</button>
    <div class="advice-overlay-body"></div>
  </div>
</div>

<section class="advice-outro">
  <p>Éclairer une pièce, c'est un peu comme choisir la bonne sauce pour ses pâtes : tout est une question de préférence et de dosage !</p>
  <span class="advice-outro-signature">Robin, créateur à l'Atelier Sâpi</span>
</section>

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

<!-- Grille : articles de la catégorie Conseils -->
<?php
$conseils_paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$conseils_query = new WP_Query([
  'posts_per_page' => 6,
  'post_status'    => 'publish',
  'category_name'  => 'conseils',
  'orderby'        => 'date',
  'order'          => 'DESC',
  'paged'          => $conseils_paged
]);

if ($conseils_query->have_posts()) :
?>
<section class="blog-grid-section">
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

    <?php if ($conseils_query->max_num_pages > 1) : ?>
      <nav class="blog-pagination" role="navigation">
        <?php
        echo paginate_links([
          'total'     => $conseils_query->max_num_pages,
          'current'   => $conseils_paged,
          'prev_text' => '← Précédent',
          'next_text' => 'Suivant →',
          'mid_size'  => 2
        ]);
        ?>
      </nav>
    <?php endif; ?>
  </div>
</section>
<?php
wp_reset_postdata();
endif;
?>

<?php
get_footer();

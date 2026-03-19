<?php
/*
Template Name: Conseils eclaires
*/
get_header();

$has_acf = function_exists('get_field');

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

<?php if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) : ?>
  <div style="text-align:center; margin: 1.5rem 0;">
    <button type="button" class="robin-pill" data-robin-context="bandeau">
      &#x1F4A1; Vous avez un projet ? Robin vous conseille
    </button>
  </div>
<?php endif; ?>

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

    <!-- Card CTA – Ouvre le bandeau Mon Projet (visible uniquement sans projet) -->
    <div class="advice-guide-cta">
      <div class="advice-guide-cta-inner">
        <h2 class="advice-guide-cta-title">Définissez votre projet d'éclairage</h2>
        <p class="advice-guide-cta-text">Répondez à quelques questions et Robin vous recommande les luminaires idéaux pour votre pièce.</p>
        <button type="button" class="advice-guide-cta-btn" onclick="<?php if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) : ?>if(window.sapiRobinOpen)window.sapiRobinOpen('bandeau');<?php else : ?>var bar=document.getElementById('mon-projet-bar');if(bar){bar.scrollIntoView({behavior:'smooth',block:'start'});var t=document.getElementById('mon-projet-toggle');if(t&&t.getAttribute('aria-expanded')==='false')t.click();}<?php endif; ?>">
          Commencer mon projet
        </button>
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

<?php
get_footer();

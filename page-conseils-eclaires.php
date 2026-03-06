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

    <!-- Panneau citation / détail (3 colonnes, hidden par défaut) -->
    <div class="advice-quote-panel" aria-hidden="true">
      <div class="advice-quote-panel-inner">
        <!-- Vue citation -->
        <div class="advice-panel-quote">
          <p class="advice-quote-text"></p>
          <div class="advice-quote-buttons">
            <button class="advice-quote-close" aria-label="Fermer">&times;</button>
            <button class="advice-quote-more">En savoir plus</button>
          </div>
        </div>
        <!-- Vue détail (remplace la citation) -->
        <div class="advice-panel-detail" aria-hidden="true">
          <div class="advice-panel-detail-body"></div>
          <div class="advice-panel-detail-buttons">
            <button class="advice-quote-close" aria-label="Fermer">&times;</button>
          </div>
        </div>
      </div>
    </div>

    <?php
    // URL du Guide Luminaire (recherche dynamique)
    $guide_url = home_url('/guide-luminaire/');
    $guide_pages = get_pages(['meta_key' => '_wp_page_template', 'meta_value' => 'page-guide-luminaire.php', 'number' => 1]);
    if (!empty($guide_pages)) {
      $guide_url = get_permalink($guide_pages[0]->ID);
    }
    ?>

    <!-- Card CTA – Guide Luminaire avec choix de pièce -->
    <?php
    $room_choices = [
      ['label' => 'Salon',   'slug' => 'salon',    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><rect x="2" y="11" width="20" height="7" rx="2"/><path d="M5 18v2m14-2v2"/></svg>'],
      ['label' => 'Cuisine', 'slug' => 'cuisine',  'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 13.87A4 4 0 0 1 7.41 6a5.11 5.11 0 0 1 1.05-1.54 5 5 0 0 1 7.08 0A5.11 5.11 0 0 1 16.59 6 4 4 0 0 1 18 13.87V20H6Z"/><line x1="6" y1="17" x2="18" y2="17"/></svg>'],
      ['label' => 'Chambre', 'slug' => 'chambre',  'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20v-8a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v8"/><path d="M2 14h20"/><path d="M2 10V7a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v3"/></svg>'],
      ['label' => 'Bureau',  'slug' => 'bureau',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8m-4-4v4"/></svg>'],
      ['label' => 'Entrée',  'slug' => 'entree',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="15" cy="12" r="1"/></svg>'],
      ['label' => 'Escalier','slug' => 'escalier', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h4v-4h4v-4h4V8h4"/><path d="M4 20V8"/><path d="M20 20V8"/></svg>'],
    ];
    ?>
    <div class="advice-guide-cta">
      <div class="advice-guide-cta-inner">
        <h2 class="advice-guide-cta-title">Pour quelle pièce cherchez-vous un luminaire ?</h2>
        <p class="advice-guide-cta-text">Sélectionnez votre pièce et Robin vous guide vers le luminaire idéal.</p>
        <div class="advice-room-picker">
          <?php foreach ($room_choices as $room) : ?>
            <a href="<?php echo esc_url(add_query_arg('piece', $room['slug'], $guide_url)); ?>" class="room-card">
              <span class="room-card-icon"><?php echo $room['icon']; ?></span>
              <span class="room-card-label"><?php echo esc_html($room['label']); ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Overlay plein écran mobile -->
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

  var grid = document.querySelector('.advice-tips-grid');
  var panel = document.querySelector('.advice-quote-panel');
  var quoteText = panel.querySelector('.advice-quote-text');
  var panelQuote = panel.querySelector('.advice-panel-quote');
  var panelDetail = panel.querySelector('.advice-panel-detail');
  var panelDetailBody = panel.querySelector('.advice-panel-detail-body');
  var activeTipIndex = null;

  /* Données des citations et contenu */
  var summaries = <?php echo json_encode(array_map(function($t) { return $t['summary']; }, $tips)); ?>;
  var contents = <?php echo json_encode(array_map(function($t) { return $t['content']; }, $tips)); ?>;

  function isMobile() {
    return window.innerWidth <= 768;
  }

  /* ========== DESKTOP : slide + panneau ========== */
  function showQuoteView() {
    panelQuote.style.display = 'flex';
    panelQuote.setAttribute('aria-hidden', 'false');
    panelDetail.style.display = 'none';
    panelDetail.setAttribute('aria-hidden', 'true');
  }

  function showDetailView() {
    panelQuote.style.display = 'none';
    panelQuote.setAttribute('aria-hidden', 'true');
    panelDetail.style.display = 'flex';
    panelDetail.setAttribute('aria-hidden', 'false');
  }

  function closeAllDesktop() {
    grid.classList.remove('is-expanded');
    panel.setAttribute('aria-hidden', 'true');
    document.querySelectorAll('.advice-tip').forEach(function(t) {
      t.classList.remove('is-active');
    });
    showQuoteView();
    activeTipIndex = null;
  }

  /* ========== MOBILE : flip card + overlay ========== */
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

  /* ========== EVENT LISTENERS ========== */

  /* Clic "Voir le conseil" */
  document.querySelectorAll('.advice-tip-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tip = this.closest('.advice-tip');
      var tipIndex = tip.getAttribute('data-tip');

      if (isMobile()) {
        /* Mobile : flip la card */
        document.querySelectorAll('.advice-tip').forEach(function(t) {
          if (t !== tip) closeFlip(t);
        });
        var backFace = tip.querySelector('.advice-tip-back');
        backFace.classList.add('no-touch');
        tip.classList.add('is-flipped');
        setTimeout(function() {
          backFace.classList.remove('no-touch');
        }, 700);
      } else {
        /* Desktop : slide + panneau */
        document.querySelectorAll('.advice-tip').forEach(function(t) {
          t.classList.remove('is-active');
        });
        tip.classList.add('is-active');
        activeTipIndex = tipIndex;
        quoteText.textContent = summaries[tipIndex];
        showQuoteView();
        grid.classList.add('is-expanded');
        panel.setAttribute('aria-hidden', 'false');
      }
    });
  });

  /* Boutons croix desktop (panneau) */
  panel.querySelectorAll('.advice-quote-close').forEach(function(btn) {
    btn.addEventListener('click', closeAllDesktop);
  });

  /* Bouton "En savoir plus" desktop (panneau) */
  panel.querySelector('.advice-quote-more').addEventListener('click', function() {
    if (activeTipIndex === null) return;
    panelDetailBody.innerHTML = contents[activeTipIndex];
    showDetailView();
  });

  /* Mobile : boutons croix (face arrière) */
  document.querySelectorAll('.advice-tip-back-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tip = this.closest('.advice-tip');
      closeFlip(tip);
    });
  });

  /* Mobile : bouton "En savoir plus" → ouvre overlay plein écran */
  document.querySelectorAll('.advice-tip-back-more').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var tipIndex = this.getAttribute('data-tip');
      openOverlay(tipIndex);
    });
  });

  /* Overlay : bouton fermer */
  if (overlayClose) {
    overlayClose.addEventListener('click', closeOverlay);
  }

  /* Overlay : fermer en cliquant sur le fond */
  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) closeOverlay();
    });
  }

})();
</script>

<?php
get_footer();

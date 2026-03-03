<?php
/*
Template Name: Conseils eclaires
*/
get_header();

$has_acf = function_exists('get_field');

$tips = [];
for ($i = 1; $i <= 4; $i++) {
    $image_field = $has_acf ? get_field("tip_{$i}_picture") : false;
    $image_url   = '';
    if (is_array($image_field) && !empty($image_field['url'])) {
        $image_url = $image_field['url'];
    } elseif (is_string($image_field) && !empty($image_field)) {
        $image_url = $image_field;
    }

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
  </div>
</section>

<!-- Overlay plein écran mobile -->
<div class="advice-overlay" aria-hidden="true">
  <div class="advice-overlay-inner">
    <button class="advice-overlay-close" aria-label="Fermer">&times;</button>
    <div class="advice-overlay-body"></div>
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

<section class="advice-guide-cta">
  <div class="advice-guide-cta-inner">
    <div class="advice-guide-cta-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2C8.13 2 5 5.13 5 9c0 2.38 1.19 4.47 3 5.74V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.87-3.13-7-7-7z"/><path d="M9 21h6"/><path d="M10 17v-2.5"/><path d="M14 17v-2.5"/></svg>
    </div>
    <h2 class="advice-guide-cta-title">Trouvez le luminaire fait pour vous</h2>
    <p class="advice-guide-cta-text">Répondez à 6 questions simples et découvrez nos créations adaptées à votre intérieur, votre style et vos envies.</p>
    <a href="<?php echo esc_url($guide_url); ?>" class="advice-guide-cta-btn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><path d="M11 8v6m-3-3h6"/></svg>
      Démarrer le questionnaire
    </a>
  </div>
</section>

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

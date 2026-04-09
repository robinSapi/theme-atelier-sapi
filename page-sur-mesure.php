<?php
/*
Template Name: Sur Mesure
*/

// Handle form submission
$form = sapi_handle_surmesure_form();

get_header();
?>

<!-- HERO -->
<section class="surmesure-hero">
  <div class="surmesure-hero-content">
    <h1 class="surmesure-title-animated" data-text="Créons ensemble votre luminaire !"></h1>
    <p class="surmesure-hero-subtitle">Vous avez une idée, un espace, une envie ? Je conçois et fabrique votre luminaire sur mesure, pièce unique pensée pour votre intérieur.</p>
    <a href="#surmesure-form" class="surmesure-scroll-cta surmesure-hero-fade">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <polyline points="19 12 12 19 5 12"></polyline>
      </svg>
      Parlons de votre projet
    </a>
  </div>
  <script>
  (function() {
    var el = document.querySelector('.surmesure-title-animated');
    if (!el) return;
    var text = el.getAttribute('data-text');
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (prefersReduced) { el.textContent = text; el.classList.add('is-visible'); return; }
    var delay = 80;
    text.split('').forEach(function(char, i) {
      var span = document.createElement('span');
      span.textContent = char;
      span.className = 'surmesure-letter';
      span.style.animationDelay = (0.5 + i * delay / 1000) + 's';
      span.style.WebkitAnimationDelay = (0.5 + i * delay / 1000) + 's';
      el.appendChild(span);
    });
    var totalDuration = 0.5 + text.length * delay / 1000 + 0.3;
    el.closest('.surmesure-hero-content').style.setProperty('--title-end', totalDuration + 's');
  })();
  </script>
</section>

<!-- INTRODUCTION -->
<section class="surmesure-intro">
  <div class="surmesure-intro-grid">
    <div class="surmesure-intro-content">
      <span class="section-number">01</span>
      <h2>Votre luminaire, votre histoire</h2>
      <p>Chaque intérieur est unique. Parfois, aucun luminaire existant ne correspond exactement à ce que vous imaginez : une dimension particulière, une essence de bois précise, une forme qui épouse votre espace.</p>
      <p>C'est pour cela que je propose la création sur mesure. On part de votre vision pour aboutir à un luminaire artisanal qui vous ressemble, fabriqué à la main dans mon atelier lyonnais.</p>
    </div>
    <div class="surmesure-intro-image">
      <?php echo sapi_image('2025/05/Robin-Sapi-A.jpg', 'large', ['alt' => "Robin dans l'atelier Sâpi", 'loading' => 'lazy']); ?>
    </div>
  </div>
</section>

<!-- PROCESSUS -->
<section class="surmesure-process">
  <div class="surmesure-process-header">
    <h2>Comment ça se passe ?</h2>
    <p>Un projet sur mesure en 3 étapes simples</p>
  </div>

  <div class="surmesure-steps">
    <div class="surmesure-step">
      <span class="surmesure-step-number">01</span>
      <div class="surmesure-step-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
      </div>
      <h3>Échangeons</h3>
      <p>Décrivez-moi votre projet : la pièce, l'ambiance souhaitée, vos contraintes de dimensions. Je vous écoute et vous conseille.</p>
    </div>

    <div class="surmesure-step">
      <span class="surmesure-step-number">02</span>
      <div class="surmesure-step-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
          <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
          <path d="M2 2l7.586 7.586"></path>
          <circle cx="11" cy="11" r="2"></circle>
        </svg>
      </div>
      <h3>Concevons</h3>
      <p>Je dessine votre luminaire et vous propose un design adapté. On ajuste ensemble jusqu'à ce que le résultat vous plaise parfaitement.</p>
    </div>

    <div class="surmesure-step">
      <span class="surmesure-step-number">03</span>
      <div class="surmesure-step-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
        </svg>
      </div>
      <h3>Fabriquons</h3>
      <p>Votre luminaire prend vie à l'atelier. Découpe laser, assemblage à la main, finition soignée. Je vous tiens informé à chaque étape.</p>
    </div>
  </div>
</section>

<!-- RÉALISATIONS -->
<section class="surmesure-realisations">
  <?php
  $has_acf = function_exists('get_field');
  $projets = new WP_Query([
    'post_type'      => 'projet_sur_mesure',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
  ]);
  $total = $projets->found_posts ?: 0;
  ?>
  <div class="surmesure-realisations-header">
    <div class="surmesure-realisations-title">
      <span class="section-number">02</span>
      <h2>Réalisations sur mesure</h2>
    </div>
    <?php if ($total > 0) : ?>
      <div class="steps-slider-nav surmesure-slider-nav">
        <button type="button" class="steps-slider-btn surmesure-slider-prev" aria-label="Projet précédent">&lt;</button>
        <span class="steps-slider-counter surmesure-slider-counter">01 / <?php echo str_pad($total, 2, '0', STR_PAD_LEFT); ?></span>
        <button type="button" class="steps-slider-btn surmesure-slider-next" aria-label="Projet suivant">&gt;</button>
      </div>
    <?php endif; ?>
    <p>Chaque projet est unique, voici quelques exemples de créations personnalisées.</p>
  </div>

  <?php if ($projets->have_posts()) : ?>
    <div class="surmesure-grid" id="surmesure-slider-track">
      <?php while ($projets->have_posts()) : $projets->the_post();
        $essence    = $has_acf ? get_field('essence_bois') : '';
        $piece      = $has_acf ? get_field('piece_destination') : '';
        $dims       = $has_acf ? get_field('dimensions_projet') : '';
        $temoignage = $has_acf ? get_field('temoignage_client') : '';
        $nom_client = $has_acf ? get_field('nom_client') : '';
        $sous_titre = $has_acf ? get_field('sous_titre') : '';
        $full_desc  = get_the_content();
        $thumb_url  = get_the_post_thumbnail_url(get_the_ID(), 'large');

        // Photos supplémentaires
        $gallery_urls = [];
        if ($thumb_url) $gallery_urls[] = $thumb_url;
        for ($i = 2; $i <= 4; $i++) {
          $photo = $has_acf ? get_field('photo_' . $i) : '';
          $url = sapi_get_acf_image_url($photo, 'large');
          if ($url) $gallery_urls[] = $url;
        }
      ?>
        <article class="surmesure-card" role="button" tabindex="0"
          data-modal-title="<?php echo esc_attr(get_the_title()); ?>"
          data-modal-image="<?php echo esc_attr($thumb_url ?: ''); ?>"
          data-modal-gallery="<?php echo esc_attr(wp_json_encode($gallery_urls)); ?>"
          data-modal-desc="<?php echo esc_attr($full_desc); ?>"
          data-modal-essence="<?php echo esc_attr($essence); ?>"
          data-modal-piece="<?php echo esc_attr($piece); ?>"
          data-modal-dims="<?php echo esc_attr($dims); ?>"
          data-modal-temoignage="<?php echo esc_attr($temoignage); ?>"
          data-modal-client="<?php echo esc_attr($nom_client); ?>"
          data-modal-soustitre="<?php echo esc_attr($sous_titre); ?>"
        >
          <?php if (has_post_thumbnail()) : ?>
            <div class="surmesure-card-image">
              <?php the_post_thumbnail('large', ['loading' => 'lazy']); ?>
            </div>
          <?php endif; ?>
          <div class="surmesure-card-content">
            <h3><?php the_title(); ?></h3>
            <?php if ($sous_titre) : ?>
              <p class="surmesure-card-desc"><?php echo esc_html($sous_titre); ?></p>
            <?php endif; ?>
            <?php if ($essence || $piece || $dims) : ?>
              <div class="surmesure-card-details">
                <?php if ($essence) : ?>
                  <span class="surmesure-detail">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>
                    <?php echo esc_html($essence); ?>
                  </span>
                <?php endif; ?>
                <?php if ($piece) : ?>
                  <span class="surmesure-detail">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <?php echo esc_html($piece); ?>
                  </span>
                <?php endif; ?>
                <?php if ($dims) : ?>
                  <span class="surmesure-detail">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 8 21"></polyline><line x1="16" y1="3" x2="3" y2="16"></line></svg>
                    <?php echo esc_html($dims); ?>
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <span class="surmesure-card-cta">Découvrir le projet →</span>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
    <div class="steps-slider-dots surmesure-slider-dots">
      <?php for ($d = 0; $d < $total; $d++) : ?>
        <button class="steps-slider-dot<?php echo $d === 0 ? ' is-active' : ''; ?>" data-idx="<?php echo $d; ?>"></button>
      <?php endfor; ?>
    </div>
    <?php wp_reset_postdata(); ?>

    <!-- Modale projet -->
    <div class="surmesure-modal" id="surmesure-modal" aria-hidden="true">
      <div class="surmesure-modal-overlay"></div>
      <div class="surmesure-modal-container" role="dialog" aria-modal="true" aria-label="Détail du projet">
        <button class="surmesure-modal-close" aria-label="Fermer">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
        <div class="surmesure-modal-body">
          <div class="surmesure-modal-gallery" style="position:relative;">
            <div class="surmesure-modal-image">
              <img src="" alt="">
            </div>
            <button class="surmesure-modal-nav surmesure-modal-nav-prev" aria-label="Photo précédente" style="display:none;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button class="surmesure-modal-nav surmesure-modal-nav-next" aria-label="Photo suivante" style="display:none;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"></polyline></svg>
            </button>
            <div class="surmesure-modal-thumbs"></div>
          </div>
          <div class="surmesure-modal-content">
            <h3 class="surmesure-modal-title"></h3>
            <p class="surmesure-modal-subtitle" style="display:none;"></p>
            <div class="surmesure-modal-details surmesure-card-details"></div>
            <div class="surmesure-modal-desc"></div>
            <blockquote class="surmesure-modal-quote surmesure-card-quote" style="display:none;">
              <p></p>
              <cite></cite>
            </blockquote>
            <a href="#surmesure-form" class="surmesure-modal-cta">
              Vous aussi, lancez votre projet sur mesure →
            </a>
          </div>
        </div>
      </div>
    </div>

    <script>
    (function() {
      'use strict';

      // --- Slider navigation (même pattern que page artisan) ---
      var track = document.getElementById('surmesure-slider-track');
      if (track) {
        var cards = track.querySelectorAll('.surmesure-card');
        var counter = document.querySelector('.surmesure-slider-counter');
        var dots = document.querySelectorAll('.surmesure-slider-dots .steps-slider-dot');
        var cur = 0;
        var tot = cards.length;

        function updateSliderUI() {
          counter.textContent = String(cur + 1).padStart(2, '0') + ' / ' + String(tot).padStart(2, '0');
          dots.forEach(function(d, i) {
            d.classList.toggle('is-active', i === cur);
          });
        }

        function sliderGoTo(idx) {
          if (idx < 0 || idx >= tot) return;
          cur = idx;
          cards[idx].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
          updateSliderUI();
        }

        var scrollTimer;
        track.addEventListener('scroll', function() {
          clearTimeout(scrollTimer);
          scrollTimer = setTimeout(function() {
            var trackCenter = track.scrollLeft + track.clientWidth / 2;
            var idx = 0;
            var minDist = Infinity;
            for (var i = 0; i < tot; i++) {
              var cardCenter = cards[i].offsetLeft + cards[i].offsetWidth / 2;
              var dist = Math.abs(cardCenter - trackCenter);
              if (dist < minDist) { minDist = dist; idx = i; }
            }
            if (idx !== cur && idx >= 0 && idx < tot) {
              cur = idx;
              updateSliderUI();
            }
          }, 80);
        }, { passive: true });

        document.querySelector('.surmesure-slider-prev').addEventListener('click', function() { sliderGoTo(cur - 1); });
        document.querySelector('.surmesure-slider-next').addEventListener('click', function() { sliderGoTo(cur + 1); });

        dots.forEach(function(dot) {
          dot.addEventListener('click', function() { sliderGoTo(parseInt(this.dataset.idx)); });
        });
      }

      // --- Modale ---
      var modal = document.getElementById('surmesure-modal');
      if (!modal) return;

      var overlay   = modal.querySelector('.surmesure-modal-overlay');
      var closeBtn  = modal.querySelector('.surmesure-modal-close');
      var imgEl     = modal.querySelector('.surmesure-modal-image img');
      var thumbsEl  = modal.querySelector('.surmesure-modal-thumbs');
      var prevBtn   = modal.querySelector('.surmesure-modal-nav-prev');
      var nextBtn   = modal.querySelector('.surmesure-modal-nav-next');
      var titleEl   = modal.querySelector('.surmesure-modal-title');
      var subtitleEl = modal.querySelector('.surmesure-modal-subtitle');
      var detailsEl = modal.querySelector('.surmesure-modal-details');
      var descEl    = modal.querySelector('.surmesure-modal-desc');
      var quoteEl   = modal.querySelector('.surmesure-modal-quote');
      var quotePEl  = quoteEl.querySelector('p');
      var citeEl    = quoteEl.querySelector('cite');
      var ctaLink   = modal.querySelector('.surmesure-modal-cta');
      var previousFocus = null;
      var currentGallery = [];
      var currentIndex = 0;

      function buildDetail(svg, text) {
        return '<span class="surmesure-detail">' + svg + ' ' + text + '</span>';
      }

      function setActiveThumb(index) {
        var thumbs = thumbsEl.querySelectorAll('.surmesure-modal-thumb');
        thumbs.forEach(function(t, i) {
          t.classList.toggle('is-active', i === index);
        });
      }

      function goToPhoto(index) {
        if (index < 0 || index >= currentGallery.length) return;
        currentIndex = index;
        imgEl.src = currentGallery[index];
        imgEl.srcset = '';
        setActiveThumb(index);
      }

      function openModal(card) {
        var data = card.dataset;
        previousFocus = document.activeElement;

        titleEl.textContent = data.modalTitle || '';
        var st = data.modalSoustitre || '';
        subtitleEl.textContent = st;
        subtitleEl.style.display = st ? '' : 'none';
        imgEl.src = data.modalImage || '';
        imgEl.srcset = '';
        imgEl.alt = data.modalTitle || '';

        // Gallery
        currentGallery = [];
        currentIndex = 0;
        try { currentGallery = JSON.parse(data.modalGallery || '[]'); } catch(e) {}

        // Thumbnails
        thumbsEl.innerHTML = '';
        var hasGallery = currentGallery.length > 1;
        if (hasGallery) {
          currentGallery.forEach(function(url, i) {
            var thumb = document.createElement('button');
            thumb.className = 'surmesure-modal-thumb' + (i === 0 ? ' is-active' : '');
            thumb.setAttribute('aria-label', 'Photo ' + (i + 1));
            thumb.innerHTML = '<img src="' + url + '" alt="">';
            thumb.addEventListener('click', function() { goToPhoto(i); });
            thumbsEl.appendChild(thumb);
          });
          thumbsEl.style.display = '';
        } else {
          thumbsEl.style.display = 'none';
        }

        // Arrows
        prevBtn.style.display = hasGallery ? '' : 'none';
        nextBtn.style.display = hasGallery ? '' : 'none';

        // Details
        var html = '';
        if (data.modalEssence) html += buildDetail('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>', data.modalEssence);
        if (data.modalPiece) html += buildDetail('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>', data.modalPiece);
        if (data.modalDims) html += buildDetail('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 8 21"></polyline><line x1="16" y1="3" x2="3" y2="16"></line></svg>', data.modalDims);
        detailsEl.innerHTML = html;
        detailsEl.style.display = html ? '' : 'none';

        // Description
        descEl.textContent = data.modalDesc || '';
        descEl.style.display = data.modalDesc ? '' : 'none';

        // Testimonial
        if (data.modalTemoignage) {
          quotePEl.textContent = data.modalTemoignage;
          citeEl.textContent = data.modalClient ? '— ' + data.modalClient : '';
          citeEl.style.display = data.modalClient ? '' : 'none';
          quoteEl.style.display = '';
        } else {
          quoteEl.style.display = 'none';
        }

        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        closeBtn.focus();
      }

      function closeModal() {
        modal.setAttribute('aria-hidden', 'true');
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
        if (previousFocus) previousFocus.focus();
      }

      // Open on card click
      document.querySelectorAll('.surmesure-card').forEach(function(card) {
        card.addEventListener('click', function(e) {
          // Don't intercept CTA link in modal
          if (e.target.closest('.surmesure-modal')) return;
          openModal(card);
        });
        card.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openModal(card);
          }
        });
      });

      // Arrow navigation
      prevBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        var idx = currentIndex > 0 ? currentIndex - 1 : currentGallery.length - 1;
        goToPhoto(idx);
      });
      nextBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        var idx = currentIndex < currentGallery.length - 1 ? currentIndex + 1 : 0;
        goToPhoto(idx);
      });

      // Close
      closeBtn.addEventListener('click', closeModal);
      overlay.addEventListener('click', closeModal);
      document.addEventListener('keydown', function(e) {
        if (!modal.classList.contains('is-open')) return;
        if (e.key === 'Escape') closeModal();
        if (e.key === 'ArrowLeft' && currentGallery.length > 1) {
          var idx = currentIndex > 0 ? currentIndex - 1 : currentGallery.length - 1;
          goToPhoto(idx);
        }
        if (e.key === 'ArrowRight' && currentGallery.length > 1) {
          var idx = currentIndex < currentGallery.length - 1 ? currentIndex + 1 : 0;
          goToPhoto(idx);
        }
      });

      // Close modal when CTA link is clicked
      ctaLink.addEventListener('click', function() {
        closeModal();
      });
    })();
    </script>

  <?php else : ?>
    <div class="surmesure-empty">
      <p>Les premières réalisations sur mesure arrivent bientôt !</p>
      <p>En attendant, n'hésitez pas à nous décrire votre projet ci-dessous.</p>
    </div>
  <?php endif; ?>
</section>

<!-- FORMULAIRE -->
<section id="surmesure-form" class="surmesure-form-section">
  <img src="https://atelier-sapi.fr/wp-content/uploads/2025/03/IMG_2202-scaled.jpg" alt="" class="surmesure-form-bg" loading="lazy">
  <div class="surmesure-form-wrapper">
    <h2>Votre projet commence ici</h2>
    <p class="surmesure-form-intro">Décrivez-moi votre idée, même vaguement. Je vous recontacte sous 48h pour en discuter ensemble.</p>

    <?php if ($form['success']) : ?>
      <div class="form-message form-message--success">
        <p><strong>Message envoyé !</strong></p>
        <p>Merci pour votre message. Je reviens vers vous très vite pour discuter de votre projet.</p>
      </div>
    <?php else : ?>

      <?php if ($form['error']) : ?>
        <div class="form-message form-message--error">
          <p><?php echo esc_html($form['error']); ?></p>
        </div>
      <?php endif; ?>

      <form id="sur-mesure-form" action="#surmesure-form" method="post">
        <?php wp_nonce_field('sapi_surmesure_form', 'sapi_surmesure_nonce'); ?>

        <!-- Honeypot -->
        <div style="display: none;" aria-hidden="true">
          <label for="website">Ne pas remplir</label>
          <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
        </div>

        <label for="surmesure-name">Nom</label>
        <input id="surmesure-name" type="text" name="fullname" required value="<?php echo esc_attr($_POST['fullname'] ?? ''); ?>" placeholder="Votre nom">

        <label for="surmesure-email">Email</label>
        <input id="surmesure-email" type="email" name="email" required value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" placeholder="votre@email.fr">

        <label for="surmesure-message">Votre projet</label>

        <!-- Bandeau projet Robin (rempli par JS si projet existant) -->
        <div id="robin-contact-project" style="display:none;"></div>
        <input type="hidden" name="robin_project" id="robin-contact-project-data" value="">

        <textarea id="surmesure-message" name="message" rows="6" required placeholder="Décrivez votre idée : le type de luminaire, la pièce, les dimensions souhaitées, le style, le bois..."><?php echo esc_textarea($_POST['message'] ?? ''); ?></textarea>

        <button type="submit">Envoyer ma demande</button>
      </form>
    <?php endif; ?>

    <p class="surmesure-form-alt">
      Vous préférez échanger directement ?
      <a href="mailto:contact@atelier-sapi.fr">contact@atelier-sapi.fr</a>
      ou <a href="tel:0680435585">06 80 43 55 85</a>
    </p>
  </div>
</section>

<?php
get_footer();

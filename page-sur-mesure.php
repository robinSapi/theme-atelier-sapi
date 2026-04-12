<?php
/*
Template Name: Sur Mesure
*/

// Handle form submission
$form = sapi_handle_surmesure_form();

get_header();
?>

<!-- HERO + ONGLETS -->
<section class="surmesure-hero">
  <div class="surmesure-hero-bg surmesure-hero-bg--particulier is-active">
    <?php echo sapi_image('2026/04/3-2.webp', 'full', ['alt' => '', 'loading' => 'eager', 'class' => 'surmesure-hero-bg-img']); ?>
  </div>
  <div class="surmesure-hero-bg surmesure-hero-bg--pro">
    <?php echo sapi_image('2025/07/Circle-salle-Vertical.jpg', 'full', ['alt' => '', 'loading' => 'lazy', 'class' => 'surmesure-hero-bg-img']); ?>
  </div>
  <div class="surmesure-hero-overlay"></div>
  <div class="surmesure-hero-content">
    <h1 class="surmesure-hero-title" data-tab-content="particulier" data-text="Créons votre luminaire sur mesure"></h1>
    <h1 class="surmesure-hero-title" data-tab-content="pro" style="display:none;" data-text="Des luminaires à l'image de votre espace"></h1>
    <p class="surmesure-hero-subtitle" data-tab-content="particulier">Une idée, un espace, une envie — je conçois et fabrique votre pièce unique.</p>
    <p class="surmesure-hero-subtitle" data-tab-content="pro" style="display:none;">Restaurants, hôtels, boutiques : des créations artisanales adaptées à votre identité.</p>
    <div class="surmesure-tabs">
      <button class="surmesure-tab is-active" data-tab="particulier" type="button">Particuliers</button>
      <button class="surmesure-tab" data-tab="pro" type="button">Professionnels</button>
    </div>
  </div>
</section>

<!-- PROCESSUS -->
<section class="surmesure-process">
  <div class="surmesure-process-header">
    <h2>Comment &ccedil;a se passe ?</h2>
    <p>Un projet sur mesure en 3 &eacute;tapes simples</p>
  </div>

  <!-- Particuliers -->
  <div class="surmesure-steps" data-tab-content="particulier">
    <div class="surmesure-step">
      <span class="surmesure-step-number">01</span>
      <div class="surmesure-step-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
      </div>
      <h3>&Eacute;changeons</h3>
      <p>D&eacute;crivez votre projet, je vous conseille.</p>
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
      <p>Je dessine, on ajuste ensemble.</p>
    </div>
    <div class="surmesure-step">
      <span class="surmesure-step-number">03</span>
      <div class="surmesure-step-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
        </svg>
      </div>
      <h3>Fabriquons</h3>
      <p>Livr&eacute; chez vous, pr&ecirc;t &agrave; poser.</p>
    </div>
  </div>

  <!-- Professionnels -->
  <div class="surmesure-steps" data-tab-content="pro" style="display:none;">
    <div class="surmesure-step">
      <span class="surmesure-step-number">01</span>
      <div class="surmesure-step-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
      </div>
      <h3>Brief &amp; devis</h3>
      <p>Votre espace, vos contraintes, votre budget.</p>
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
      <h3>Conception</h3>
      <p>Un design dans votre identit&eacute;, ajust&eacute; avec vous.</p>
    </div>
    <div class="surmesure-step">
      <span class="surmesure-step-number">03</span>
      <div class="surmesure-step-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
        </svg>
      </div>
      <h3>Fabrication</h3>
      <p>Commandes multiples possibles, facturation pro.</p>
    </div>
  </div>
</section>

<!-- RÉALISATIONS -->
<section class="surmesure-realisations">
  <?php
  $has_acf = function_exists('get_field');

  // Projets Particuliers
  $projets_particulier = new WP_Query([
    'post_type'      => 'projet_sur_mesure',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
      [
        'key'   => 'type_client',
        'value' => 'particulier',
      ],
    ],
  ]);

  // Projets Professionnels
  $projets_pro = new WP_Query([
    'post_type'      => 'projet_sur_mesure',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
      [
        'key'   => 'type_client',
        'value' => 'professionnel',
      ],
    ],
  ]);
  ?>

  <!-- Slider Particuliers -->
  <div data-tab-content="particulier">
    <div class="surmesure-realisations-header">
      <div class="surmesure-realisations-title">
        <span class="section-number">02</span>
        <h2>R&eacute;alisations sur mesure</h2>
      </div>
      <p>Chaque projet est unique, voici quelques exemples de cr&eacute;ations personnalis&eacute;es.</p>
    </div>

    <?php if ($projets_particulier->have_posts()) : ?>
      <div class="surmesure-grid">
        <?php while ($projets_particulier->have_posts()) : $projets_particulier->the_post();
          $essence    = $has_acf ? get_field('essence_bois') : '';
          $piece      = $has_acf ? get_field('piece_destination') : '';
          $dims       = $has_acf ? get_field('dimensions_projet') : '';
          $temoignage = $has_acf ? get_field('temoignage_client') : '';
          $nom_client = $has_acf ? get_field('nom_client') : '';
          $sous_titre = $has_acf ? get_field('sous_titre') : '';
          $full_desc  = get_the_content();
          $thumb_url  = get_the_post_thumbnail_url(get_the_ID(), 'large');

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
              <span class="surmesure-card-cta">D&eacute;couvrir le projet &rarr;</span>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <?php wp_reset_postdata(); ?>
    <?php else : ?>
      <div class="surmesure-empty">
        <p>Les premi&egrave;res r&eacute;alisations arrivent bient&ocirc;t !</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Slider Professionnels -->
  <div data-tab-content="pro" style="display:none;">
    <div class="surmesure-realisations-header">
      <div class="surmesure-realisations-title">
        <span class="section-number">02</span>
        <h2>R&eacute;alisations professionnelles</h2>
      </div>
      <p>Des cr&eacute;ations sur mesure pour les professionnels de l'h&ocirc;tellerie, la restauration et le commerce.</p>
    </div>

    <?php if ($projets_pro->have_posts()) : ?>
      <div class="surmesure-grid">
        <?php while ($projets_pro->have_posts()) : $projets_pro->the_post();
          $essence    = $has_acf ? get_field('essence_bois') : '';
          $piece      = $has_acf ? get_field('piece_destination') : '';
          $dims       = $has_acf ? get_field('dimensions_projet') : '';
          $temoignage = $has_acf ? get_field('temoignage_client') : '';
          $nom_client = $has_acf ? get_field('nom_client') : '';
          $sous_titre = $has_acf ? get_field('sous_titre') : '';
          $full_desc  = get_the_content();
          $thumb_url  = get_the_post_thumbnail_url(get_the_ID(), 'large');

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
              <span class="surmesure-card-cta">D&eacute;couvrir le projet &rarr;</span>
            </div>
          </article>
        <?php endwhile; ?>
      </div>
      <?php wp_reset_postdata(); ?>
    <?php else : ?>
      <div class="surmesure-empty">
        <p>Les premi&egrave;res r&eacute;alisations arrivent bient&ocirc;t !</p>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modale projet (unique, partagée) -->
  <div class="surmesure-modal" id="surmesure-modal" aria-hidden="true">
    <div class="surmesure-modal-overlay"></div>
    <div class="surmesure-modal-container" role="dialog" aria-modal="true" aria-label="D&eacute;tail du projet">
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
          <button class="surmesure-modal-nav surmesure-modal-nav-prev" aria-label="Photo pr&eacute;c&eacute;dente" style="display:none;">
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
            Vous aussi, lancez votre projet sur mesure &rarr;
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FORMULAIRE -->
<section id="surmesure-form" class="surmesure-form-section">
  <img src="https://atelier-sapi.fr/wp-content/uploads/2025/03/IMG_2202-scaled.jpg" alt="" class="surmesure-form-bg" loading="lazy">
  <div class="surmesure-form-wrapper">
    <h2>Votre projet commence ici</h2>
    <p class="surmesure-form-intro">D&eacute;crivez-moi votre id&eacute;e, m&ecirc;me vaguement. Je vous recontacte sous 48h pour en discuter ensemble.</p>

    <?php if ($form['success']) : ?>
      <div class="form-message form-message--success">
        <p><strong>Message envoy&eacute; !</strong></p>
        <p>Merci pour votre message. Je reviens vers vous tr&egrave;s vite pour discuter de votre projet.</p>
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

        <!-- Champs pro (masqués par défaut) -->
        <div class="is-pro-field" style="display:none;">
          <label for="surmesure-etablissement">&Eacute;tablissement</label>
          <input id="surmesure-etablissement" type="text" name="etablissement" value="<?php echo esc_attr($_POST['etablissement'] ?? ''); ?>" placeholder="Type d'&eacute;tablissement (restaurant, h&ocirc;tel, boutique&hellip;)">
        </div>
        <div class="is-pro-field" style="display:none;">
          <label for="surmesure-nb-luminaires">Nombre de luminaires</label>
          <input id="surmesure-nb-luminaires" type="text" name="nb_luminaires" value="<?php echo esc_attr($_POST['nb_luminaires'] ?? ''); ?>" placeholder="Nombre de luminaires envisag&eacute;s">
        </div>

        <label for="surmesure-message">Votre projet</label>

        <!-- Bandeau projet Robin (rempli par JS si projet existant) -->
        <div id="robin-contact-project" style="display:none;"></div>
        <input type="hidden" name="robin_project" id="robin-contact-project-data" value="">

        <textarea id="surmesure-message" name="message" rows="6" required placeholder="D&eacute;crivez votre id&eacute;e : le type de luminaire, la pi&egrave;ce, les dimensions souhait&eacute;es, le style, le bois..."><?php echo esc_textarea($_POST['message'] ?? ''); ?></textarea>

        <button type="submit">Envoyer ma demande</button>
      </form>
    <?php endif; ?>

    <p class="surmesure-form-alt">
      Vous pr&eacute;f&eacute;rez &eacute;changer directement ?
      <a href="mailto:contact@atelier-sapi.fr">contact@atelier-sapi.fr</a>
      ou <a href="tel:0680435585">06 80 43 55 85</a>
    </p>
  </div>
</section>

<script>
(function() {
  'use strict';

  var activeTab = 'particulier';
  var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var LETTER_DELAY = 40;

  // --- Onglets ---
  var tabs = document.querySelectorAll('.surmesure-tab');
  var heroBgParticulier = document.querySelector('.surmesure-hero-bg--particulier');
  var heroBgPro = document.querySelector('.surmesure-hero-bg--pro');
  var proFields = document.querySelectorAll('.is-pro-field');
  var heroSubtitles = document.querySelectorAll('.surmesure-hero-subtitle');

  // --- Animation écriture H1 ---
  function animateTitle(tab) {
    var title = document.querySelector('.surmesure-hero-title[data-tab-content="' + tab + '"]');
    var subtitle = document.querySelector('.surmesure-hero-subtitle[data-tab-content="' + tab + '"]');
    if (!title) return;

    var text = title.getAttribute('data-text') || '';
    subtitle.classList.remove('is-visible');

    if (prefersReduced) {
      title.textContent = text;
      subtitle.classList.add('is-visible');
      return;
    }

    title.innerHTML = '';
    text.split('').forEach(function(char, i) {
      var span = document.createElement('span');
      span.textContent = char;
      span.className = 'surmesure-letter';
      span.style.animationDelay = (i * LETTER_DELAY / 1000) + 's';
      span.style.webkitAnimationDelay = (i * LETTER_DELAY / 1000) + 's';
      title.appendChild(span);
    });

    // Afficher le sous-titre quand la dernière lettre finit son animation
    var lastSpan = title.lastElementChild;
    if (lastSpan) {
      lastSpan.addEventListener('animationend', function onEnd() {
        lastSpan.removeEventListener('animationend', onEnd);
        subtitle.classList.add('is-visible');
      });
    }
  }

  function switchTab(tab) {
    if (tab === activeTab) return;
    activeTab = tab;

    // Boutons onglets
    tabs.forEach(function(t) {
      t.classList.toggle('is-active', t.dataset.tab === tab);
    });

    // Fonds hero
    heroBgParticulier.classList.toggle('is-active', tab === 'particulier');
    heroBgPro.classList.toggle('is-active', tab === 'pro');

    // Masquer sous-titres immédiatement
    heroSubtitles.forEach(function(el) {
      el.classList.remove('is-visible');
    });

    // Contenu onglets (re-query pour inclure les dots créés dynamiquement)
    document.querySelectorAll('[data-tab-content]').forEach(function(el) {
      el.style.display = el.dataset.tabContent === tab ? '' : 'none';
    });

    // Champs pro formulaire
    proFields.forEach(function(el) {
      el.style.display = tab === 'pro' ? '' : 'none';
    });

    // Animation titre
    animateTitle(tab);
  }

  // Animation initiale au chargement
  animateTitle('particulier');

  tabs.forEach(function(btn) {
    btn.addEventListener('click', function() {
      switchTab(this.dataset.tab);
    });
  });

  // --- Modale ---
  var modal = document.getElementById('surmesure-modal');
  if (!modal) return;

  var overlay   = modal.querySelector('.surmesure-modal-overlay');
  var closeBtn  = modal.querySelector('.surmesure-modal-close');
  var imageContainer = modal.querySelector('.surmesure-modal-image');
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
  var galleryEl = modal.querySelector('.surmesure-modal-gallery');
  var previousFocus = null;
  var currentGallery = [];
  var currentIndex = 0;
  var isMobile = window.matchMedia('(max-width: 768px)');
  var photoDots = null;

  function buildDetail(svg, text) {
    return '<span class="surmesure-detail">' + svg + ' ' + text + '</span>';
  }

  function setActiveThumb(index) {
    var thumbs = thumbsEl.querySelectorAll('.surmesure-modal-thumb');
    thumbs.forEach(function(t, i) {
      t.classList.toggle('is-active', i === index);
    });
  }

  function setActiveDot(index) {
    if (!photoDots) return;
    var dots = photoDots.querySelectorAll('.surmesure-modal-photo-dot');
    dots.forEach(function(d, i) {
      d.classList.toggle('active', i === index);
    });
  }

  function goToPhoto(index) {
    if (index < 0 || index >= currentGallery.length) return;
    currentIndex = index;

    if (isMobile.matches) {
      var imgs = imageContainer.querySelectorAll('img');
      if (imgs[index]) {
        imgs[index].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
      }
      setActiveDot(index);
    } else {
      var mainImg = imageContainer.querySelector('img');
      if (mainImg) {
        mainImg.src = currentGallery[index];
        mainImg.srcset = '';
      }
      setActiveThumb(index);
    }
  }

  function openModal(card) {
    var data = card.dataset;
    previousFocus = document.activeElement;

    titleEl.textContent = data.modalTitle || '';
    var st = data.modalSoustitre || '';
    subtitleEl.textContent = st;
    subtitleEl.style.display = st ? '' : 'none';

    currentGallery = [];
    currentIndex = 0;
    try { currentGallery = JSON.parse(data.modalGallery || '[]'); } catch(e) {}
    if (!currentGallery.length && data.modalImage) currentGallery = [data.modalImage];

    var hasGallery = currentGallery.length > 1;

    // Construire les images
    imageContainer.innerHTML = '';
    if (isMobile.matches && hasGallery) {
      // Mobile : toutes les images côte à côte pour scroll horizontal
      currentGallery.forEach(function(url) {
        var img = document.createElement('img');
        img.src = url;
        img.srcset = '';
        img.alt = data.modalTitle || '';
        imageContainer.appendChild(img);
      });

      // Dots
      if (photoDots) photoDots.remove();
      photoDots = document.createElement('div');
      photoDots.className = 'surmesure-modal-photo-dots';
      currentGallery.forEach(function(url, i) {
        var dot = document.createElement('button');
        dot.className = 'surmesure-modal-photo-dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', 'Photo ' + (i + 1));
        dot.addEventListener('click', function() { goToPhoto(i); });
        photoDots.appendChild(dot);
      });
      imageContainer.parentNode.insertBefore(photoDots, imageContainer.nextSibling);

      // Scroll listener pour mettre à jour les dots
      imageContainer.addEventListener('scroll', function onImgScroll() {
        if (!modal.classList.contains('is-open')) {
          imageContainer.removeEventListener('scroll', onImgScroll);
          return;
        }
        var center = imageContainer.scrollLeft + imageContainer.clientWidth / 2;
        var imgs = imageContainer.querySelectorAll('img');
        var closest = 0;
        var minDist = Infinity;
        imgs.forEach(function(img, j) {
          var imgCenter = img.offsetLeft + img.offsetWidth / 2;
          var dist = Math.abs(imgCenter - center);
          if (dist < minDist) { minDist = dist; closest = j; }
        });
        if (closest !== currentIndex) {
          currentIndex = closest;
          setActiveDot(closest);
        }
      }, { passive: true });
    } else {
      // Desktop : une seule image
      var img = document.createElement('img');
      img.src = currentGallery[0] || data.modalImage || '';
      img.srcset = '';
      img.alt = data.modalTitle || '';
      imageContainer.appendChild(img);
    }

    // Thumbnails (desktop uniquement)
    thumbsEl.innerHTML = '';
    if (hasGallery && !isMobile.matches) {
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

    prevBtn.style.display = hasGallery && !isMobile.matches ? '' : 'none';
    nextBtn.style.display = hasGallery && !isMobile.matches ? '' : 'none';

    var html = '';
    if (data.modalEssence) html += buildDetail('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle></svg>', data.modalEssence);
    if (data.modalPiece) html += buildDetail('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>', data.modalPiece);
    if (data.modalDims) html += buildDetail('<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 8 21"></polyline><line x1="16" y1="3" x2="3" y2="16"></line></svg>', data.modalDims);
    detailsEl.innerHTML = html;
    detailsEl.style.display = html ? '' : 'none';

    descEl.textContent = data.modalDesc || '';
    descEl.style.display = data.modalDesc ? '' : 'none';

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
    if (photoDots) { photoDots.remove(); photoDots = null; }
    if (previousFocus) previousFocus.focus();
  }

  document.querySelectorAll('.surmesure-card').forEach(function(card) {
    card.addEventListener('click', function(e) {
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

  ctaLink.addEventListener('click', function() {
    closeModal();
  });
})();
</script>

<?php
get_footer();

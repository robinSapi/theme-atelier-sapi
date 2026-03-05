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
  <div class="surmesure-hero-overlay"></div>
  <div class="surmesure-hero-content">
    <h1>Créons ensemble votre luminaire</h1>
    <p>Vous avez une idée, un espace, une envie ? Je conçois et fabrique votre luminaire sur mesure, pièce unique pensée pour votre intérieur.</p>
    <a href="#surmesure-form" class="surmesure-scroll-cta">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <polyline points="19 12 12 19 5 12"></polyline>
      </svg>
      Parlons de votre projet
    </a>
  </div>
</section>

<!-- INTRODUCTION -->
<section class="surmesure-intro">
  <div class="surmesure-intro-grid">
    <div class="surmesure-intro-content">
      <span class="section-number">01</span>
      <h2>Votre luminaire, votre histoire</h2>
      <p>Chaque intérieur est unique. Parfois, aucun luminaire existant ne correspond exactement à ce que vous imaginez : une dimension particulière, une essence de bois précise, une forme qui épouse votre espace.</p>
      <p>C'est pour cela que je propose la création sur mesure. Ensemble, nous partons de votre vision pour aboutir à un luminaire artisanal qui vous ressemble, fabriqué à la main dans mon atelier lyonnais.</p>
    </div>
    <div class="surmesure-intro-image">
      <img src="<?php echo esc_url(home_url('/wp-content/uploads/')); ?>2025/05/Robin-Sapi-A.jpg" alt="Robin dans l'atelier Sâpi" loading="lazy">
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
  <div class="surmesure-realisations-header">
    <span class="section-number">02</span>
    <h2>Nos réalisations sur mesure</h2>
    <p>Chaque projet est unique, voici quelques exemples de créations personnalisées.</p>
  </div>

  <?php
  $has_acf = function_exists('get_field');
  $projets = new WP_Query([
    'post_type'      => 'projet_sur_mesure',
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
  ]);

  if ($projets->have_posts()) : ?>
    <div class="surmesure-grid">
      <?php while ($projets->have_posts()) : $projets->the_post(); ?>
        <article class="surmesure-card">
          <?php if (has_post_thumbnail()) : ?>
            <div class="surmesure-card-image">
              <?php the_post_thumbnail('large', ['loading' => 'lazy']); ?>
            </div>
          <?php endif; ?>
          <div class="surmesure-card-content">
            <h3><?php the_title(); ?></h3>
            <?php
            $essence = $has_acf ? get_field('essence_bois') : '';
            $piece   = $has_acf ? get_field('piece_destination') : '';
            $dims    = $has_acf ? get_field('dimensions_projet') : '';
            ?>
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

            <?php if (get_the_content()) : ?>
              <p class="surmesure-card-desc"><?php echo esc_html(wp_trim_words(get_the_content(), 25)); ?></p>
            <?php endif; ?>

            <?php
            $temoignage  = $has_acf ? get_field('temoignage_client') : '';
            $nom_client  = $has_acf ? get_field('nom_client') : '';
            ?>
            <?php if ($temoignage) : ?>
              <blockquote class="surmesure-card-quote">
                <p><?php echo esc_html($temoignage); ?></p>
                <?php if ($nom_client) : ?>
                  <cite>— <?php echo esc_html($nom_client); ?></cite>
                <?php endif; ?>
              </blockquote>
            <?php endif; ?>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
    <?php wp_reset_postdata(); ?>

  <?php else : ?>
    <div class="surmesure-empty">
      <p>Nos premières réalisations sur mesure arrivent bientôt !</p>
      <p>En attendant, n'hésitez pas à nous décrire votre projet ci-dessous.</p>
    </div>
  <?php endif; ?>
</section>

<!-- FORMULAIRE -->
<section id="surmesure-form" class="surmesure-form-section">
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

      <form action="<?php echo esc_url(get_permalink()); ?>#surmesure-form" method="post">
        <?php wp_nonce_field('sapi_surmesure_form', 'sapi_surmesure_nonce'); ?>

        <!-- Honeypot -->
        <div style="display: none;" aria-hidden="true">
          <label for="website">Ne pas remplir</label>
          <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
        </div>

        <label for="surmesure-name">Nom</label>
        <input id="surmesure-name" type="text" name="name" required value="<?php echo esc_attr($_POST['name'] ?? ''); ?>" placeholder="Votre nom">

        <label for="surmesure-email">Email</label>
        <input id="surmesure-email" type="email" name="email" required value="<?php echo esc_attr($_POST['email'] ?? ''); ?>" placeholder="votre@email.fr">

        <label for="surmesure-message">Votre projet</label>
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

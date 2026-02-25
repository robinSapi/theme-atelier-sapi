<?php
/*
Template Name: Lumiere d'artisan
*/
get_header();
?>

<section class="artisan-hero artisan-hero-cinetique">
  <div class="artisan-hero-inner">
    <h1>Lumières d'artisan</h1>
    <p class="artisan-hero-subtitle">L'Atelier Sâpi, c'est surtout le travail de Robin, artisan à tout faire !</p>
    <a href="#artisan-video" class="artisan-scroll-cta">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <polyline points="19 12 12 19 5 12"></polyline>
      </svg>
      Découvrir l'atelier
    </a>
  </div>
</section>

<!-- Video Section -->
<section class="artisan-video" id="artisan-video">
  <div class="artisan-video-wrapper">
    <div class="artisan-video-container">
      <?php
      // Try to get video from ACF, otherwise use placeholder
      $video_url = function_exists('get_field') ? get_field('video_atelier') : '';
      $video_poster = function_exists('get_field') ? get_field('video_poster') : '';
      $poster_url = $video_poster ? $video_poster['url'] : home_url('/wp-content/uploads/2025/05/Robin-Sapi-A.jpg');

      if ($video_url) :
        // Check if it's a YouTube/Vimeo URL or a local video
        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) :
          // Extract YouTube ID
          preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches);
          $youtube_id = isset($matches[1]) ? $matches[1] : '';
          if ($youtube_id) :
      ?>
        <div class="video-embed-wrapper">
          <iframe
            src="https://www.youtube.com/embed/<?php echo esc_attr($youtube_id); ?>?rel=0&modestbranding=1"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
            loading="lazy"
          ></iframe>
        </div>
      <?php
          endif;
        else :
      ?>
        <video controls poster="<?php echo esc_url($poster_url); ?>">
          <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
          Votre navigateur ne supporte pas la lecture vidéo.
        </video>
      <?php endif; ?>
      <?php else : ?>
        <!-- Placeholder when no video is configured -->
        <div class="video-placeholder" style="background-image: url('<?php echo esc_url($poster_url); ?>');">
          <div class="video-placeholder-content">
            <div class="video-play-btn">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor">
                <polygon points="5 3 19 12 5 21 5 3"></polygon>
              </svg>
            </div>
            <p>Vidéo de l'atelier bientôt disponible</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <div class="artisan-video-caption">
      <h2>Bienvenue dans mon univers</h2>
      <p>Découvrez les coulisses de la création de vos luminaires, de l'idée à l'expédition.</p>
    </div>
  </div>
</section>

<section class="artisan-intro artisan-intro-cinetique">
  <div class="artisan-intro-grid">
    <div class="artisan-intro-content">
      <span class="section-number">01</span>
      <h2>Créer, c'est tout ce que j'adore !</h2>
      <p>Alors l'Atelier Sâpi, c'est mon coin de paradis. Ici, j'imagine, je conçois, je fabrique et j'expédie moi-même tous les luminaires que j'ai le plaisir de vous faire découvrir. Je me déplace aussi régulièrement pour tester les prototypes, réaliser des photos et des vidéos, afin de vous montrer mes créations dans des lieux réels, vivants.</p>
    </div>
    <div class="artisan-intro-image-wrapper">
      <img src="<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/Robin-Sapi-A.jpg" alt="Robin dans l'atelier Sapi" class="artisan-intro-robin-photo" loading="lazy">
    </div>
  </div>
</section>

<section class="artisan-step artisan-step-cinetique">
  <div class="artisan-step-grid">
    <div class="artisan-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/IMG_1928.png');"></div>
    <div class="artisan-step-content">
      <span class="section-number">02</span>
      <h2>Tout commence par une idée</h2>
      <p>Mon inspiration ? Elle vient de ce que je vois, de ce que j'entends, de vos idées, de vos envies, de vos retours. Je puise dans la nature, l'architecture, les mouvements du quotidien. Je tiens compte des contraintes d'éclairage que vous partagez avec moi, et je cherche à proposer des luminaires pour toutes les situations.</p>
      <p>Je dessine des croquis dans mes carnets, ou parfois sur ce qui me tombe sous la main ! Certaines idées prennent des mois à mûrir, d'autres surgissent en un éclair ... Mais chaque luminaire est né ici, dans ma tête et à l'atelier ! Chaque idée est originale par essence.</p>
    </div>
  </div>
</section>

<section class="artisan-step artisan-step-cinetique">
  <div class="artisan-step-grid reverse">
    <div class="artisan-step-content">
      <span class="section-number">03</span>
      <h2>Comment je la concrétise ?</h2>
      <p>Quand une idée tient la route et que je veux lui donner vie, je passe à l'étape suivante, sur ordinateur. Je dessine un modèle en 3D et j'affine chaque pièce, chaque détail, chaque assemblage. C'est aussi à cette étape que j'imagine les variations possibles : différentes tailles, formes, finitions …</p>
      <p>L'essentiel est d'imaginer le réel et de garder en vue que le luminaire sera suspendu dans un vrai intérieur. Comment va-t-il épouser l'espace, quelles ombres va-t-il projeter, à quoi ressemblera l'ambiance qu'il créera ? Lorsque ma conception est prête, j'exporte chaque pièce en format 2D pour la production. Et comme je suis un peu maniaque, je classe bien tous les documents : bien archiver le passé, c'est bien construire le futur ! Ensuite, place à la découpe ...</p>
    </div>
    <div class="artisan-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/Retouchee1.jpg');"></div>
  </div>
</section>

<section class="artisan-step artisan-step-cinetique">
  <div class="artisan-step-grid">
    <div class="artisan-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/05/IMG_7638.jpg');"></div>
    <div class="artisan-step-content">
      <span class="section-number">04</span>
      <h2>J'adore l'étape de fabrication</h2>
      <p>C'est le moment où j'utilise ma machine de découpe laser, que j'ai installée dans l'atelier. Je prépare les panneaux de bois (peuplier ou okoumé, soigneusement sélectionnés), je les positionne dans la machine, je paramètre la découpe, puis je lance les programmes.</p>
      <p>Pendant 20 à 40 minutes, la machine découpe et grave avec précision les pièces qui composeront le luminaire. J'avoue, j'ai été obnubilé par le mouvement chorégraphié… mais, ça va mieux maintenant.</p>
    </div>
  </div>
</section>

<section class="artisan-step artisan-step-cinetique">
  <div class="artisan-step-grid reverse">
    <div class="artisan-step-content">
      <span class="section-number">05</span>
      <h2>Et maintenant, elle vous appartient !</h2>
      <p>Chaque pièce est ensuite contrôlée à la main : je ponce, j'affine, je vérifie la qualité avant de passer à l'assemblage puis à l'emballage. En effet, certains modèles vous arrivent pré-assemblés, et il ne vous reste que quelques étapes faciles et guidées !</p>
      <p>Je prépare chaque colis avec soin : pièces, accessoires commandés, notice d'installation… et bien sûr, un bon emballage pour garantir une livraison impeccable !</p>
    </div>
    <div class="artisan-image" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/")); ?>2025/07/P1130073-scaled.jpg');"></div>
  </div>
</section>

<!-- Values Section -->
<section class="artisan-values artisan-values-cinetique">
  <div class="artisan-values-header">
    <span class="section-number">06</span>
    <h2>Mes valeurs</h2>
  </div>
  <div class="artisan-values-grid">
    <div class="artisan-value-item">
      <div class="value-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M12 2L2 7l10 5 10-5-10-5z"/>
          <path d="M2 17l10 5 10-5"/>
          <path d="M2 12l10 5 10-5"/>
        </svg>
      </div>
      <h3>Artisanat local</h3>
      <p>Tout est fabriqué à la main dans mon atelier à Lyon, du dessin à l'expédition.</p>
    </div>
    <div class="artisan-value-item">
      <div class="value-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/>
          <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
          <line x1="9" y1="9" x2="9.01" y2="9"/>
          <line x1="15" y1="9" x2="15.01" y2="9"/>
        </svg>
      </div>
      <h3>Passion & authenticité</h3>
      <p>Chaque création naît d'une envie sincère de vous apporter lumière et bien-être.</p>
    </div>
    <div class="artisan-value-item">
      <div class="value-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
          <polyline points="7.5 4.21 12 6.81 16.5 4.21"/>
          <polyline points="7.5 19.79 7.5 14.6 3 12"/>
          <polyline points="21 12 16.5 14.6 16.5 19.79"/>
          <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
          <line x1="12" y1="22.08" x2="12" y2="12"/>
        </svg>
      </div>
      <h3>Éco-responsabilité</h3>
      <p>Bois issus de forêts gérées, production locale, emballages recyclables.</p>
    </div>
  </div>
</section>

<section class="artisan-quote artisan-quote-cinetique" style="background-image: url('<?php echo esc_url(home_url("/wp-content/uploads/2025/04/Vue-detail-1.jpg")); ?>');">
  <blockquote>
    « Je vous souhaite autant de plaisir à monter et à profiter de votre luminaire Sâpi que j'en ai eu lors de sa conception et de sa fabrication ! »
    <cite>Robin, créateur à l'Atelier Sâpi</cite>
  </blockquote>
</section>

<!-- CTA Section -->
<section class="artisan-cta">
  <div class="artisan-cta-content">
    <h2>Envie de découvrir mes créations ?</h2>
    <p>Chaque luminaire est une pièce unique, pensée pour sublimer votre intérieur.</p>
    <a href="<?php echo home_url('/nos-creations/'); ?>" class="button">Voir la collection</a>
  </div>
</section>

<?php
get_footer();

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
  <div class="artisan-video-grid">
    <div class="artisan-video-container artisan-video-portrait">
      <iframe
        src="https://www.youtube.com/embed/FTTmlrgIJPY?rel=0&modestbranding=1"
        frameborder="0"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        allowfullscreen
        loading="lazy"
      ></iframe>
    </div>
    <div class="artisan-video-caption">
      <h2>Bienvenue dans mon univers</h2>
      <p>Découvrez les coulisses de la création de vos luminaires, de l'idée à l'expédition.</p>
    </div>
  </div>
</section>

<section class="artisan-intro artisan-intro-cinetique">
  <div class="artisan-intro-content artisan-intro-content--right">
    <h2>Créer, c'est tout ce que j'adore !</h2>
    <p>Alors l'Atelier Sâpi, c'est mon coin de paradis. Ici, j'imagine, je conçois, je fabrique et j'expédie moi-même tous les luminaires que j'ai le plaisir de vous faire découvrir. Je me déplace aussi régulièrement pour tester les prototypes, réaliser des photos et des vidéos, afin de vous montrer mes créations dans des lieux réels, vivants.</p>
  </div>
</section>

<!-- Steps Slider -->
<section class="artisan-steps-slider">
  <div class="steps-slider-header">
    <h2>Mon processus</h2>
    <div class="steps-slider-nav">
      <button type="button" class="steps-slider-btn steps-slider-prev" aria-label="Étape précédente">&lt;</button>
      <span class="steps-slider-counter">01 / 04</span>
      <button type="button" class="steps-slider-btn steps-slider-next" aria-label="Étape suivante">&gt;</button>
    </div>
  </div>
  <div class="steps-slider-viewport">
  <div class="steps-slider-track" id="stepsSliderTrack">
    <div class="steps-slide">
      <div class="steps-slide-inner">
        <div class="steps-slide-image">
          <?php echo sapi_image('2025/05/IMG_1928-e1761747188966.png', 'large', ['alt' => "Dessin d'un luminaire — Étape création Atelier Sâpi", 'class' => 'steps-slide-image-img', 'loading' => 'lazy']); ?>
        </div>
        <div class="steps-slide-content">
          <span class="section-number">01</span>
          <h3>Tout commence par une idée</h3>
          <p>Mon inspiration ? Elle vient de ce que je vois, de ce que j'entends, de vos idées, de vos envies, de vos retours. Je puise dans la nature, l'architecture, les mouvements du quotidien. Je tiens compte des contraintes d'éclairage que vous partagez avec moi, et je cherche à proposer des luminaires pour toutes les situations.</p>
          <p>Je dessine des croquis dans mes carnets, ou parfois sur ce qui me tombe sous la main ! Certaines idées prennent des mois à mûrir, d'autres surgissent en un éclair ... Mais chaque luminaire est né ici, dans ma tête et à l'atelier ! Chaque idée est originale par essence.</p>
        </div>
      </div>
    </div>
    <div class="steps-slide">
      <div class="steps-slide-inner">
        <div class="steps-slide-image">
          <?php echo sapi_image('2025/05/Retouchee1.jpg', 'large', ['alt' => "Conception 3D d'un luminaire en bois", 'class' => 'steps-slide-image-img', 'loading' => 'lazy']); ?>
        </div>
        <div class="steps-slide-content">
          <span class="section-number">02</span>
          <h3>Comment je la concrétise ?</h3>
          <p>Quand une idée tient la route et que je veux lui donner vie, je passe à l'étape suivante, sur ordinateur. Je dessine un modèle en 3D et j'affine chaque pièce, chaque détail, chaque assemblage. C'est aussi à cette étape que j'imagine les variations possibles : différentes tailles, formes, finitions …</p>
          <p>L'essentiel est d'imaginer le réel et de garder en vue que le luminaire sera suspendu dans un vrai intérieur. Comment va-t-il épouser l'espace, quelles ombres va-t-il projeter, à quoi ressemblera l'ambiance qu'il créera ? Lorsque ma conception est prête, j'exporte chaque pièce en format 2D pour la production. Et comme je suis un peu maniaque, je classe bien tous les documents : bien archiver le passé, c'est bien construire le futur ! Ensuite, place à la découpe ...</p>
        </div>
      </div>
    </div>
    <div class="steps-slide">
      <div class="steps-slide-inner">
        <div class="steps-slide-image">
          <?php echo sapi_image('2025/05/IMG_7638.jpg', 'large', ['alt' => "Découpe laser du bois pour luminaire artisanal", 'class' => 'steps-slide-image-img', 'loading' => 'lazy']); ?>
        </div>
        <div class="steps-slide-content">
          <span class="section-number">03</span>
          <h3>J'adore l'étape de fabrication</h3>
          <p>C'est le moment où j'utilise ma machine de découpe laser, que j'ai installée dans l'atelier. Je prépare les panneaux de bois (peuplier ou okoumé, soigneusement sélectionnés), je les positionne dans la machine, je paramètre la découpe, puis je lance les programmes.</p>
          <p>Pendant 20 à 40 minutes, la machine découpe et grave avec précision les pièces qui composeront le luminaire. J'avoue, j'ai été obnubilé par le mouvement chorégraphié… mais, ça va mieux maintenant.</p>
        </div>
      </div>
    </div>
    <div class="steps-slide">
      <div class="steps-slide-inner">
        <div class="steps-slide-image">
          <?php echo sapi_image('2025/07/P1130073-scaled.jpg', 'large', ['alt' => "Finitions et assemblage d'un luminaire en bois", 'class' => 'steps-slide-image-img', 'loading' => 'lazy']); ?>
        </div>
        <div class="steps-slide-content">
          <span class="section-number">04</span>
          <h3>Et maintenant, elle vous appartient !</h3>
          <p>Chaque pièce est ensuite contrôlée à la main : je ponce, j'affine, je vérifie la qualité avant de passer à l'assemblage puis à l'emballage. En effet, certains modèles vous arrivent pré-assemblés, et il ne vous reste que quelques étapes faciles et guidées !</p>
          <p>Je prépare chaque colis avec soin : pièces, accessoires commandés, notice d'installation… et bien sûr, un bon emballage pour garantir une livraison impeccable !</p>
        </div>
      </div>
    </div>
  </div>
  <div class="steps-slider-dots" id="stepsSliderDots">
    <button class="steps-slider-dot is-active" data-idx="0"></button>
    <button class="steps-slider-dot" data-idx="1"></button>
    <button class="steps-slider-dot" data-idx="2"></button>
    <button class="steps-slider-dot" data-idx="3"></button>
  </div>
</section>

<script>
(function() {
  var track = document.getElementById('stepsSliderTrack');
  if (!track) return;
  var slides = track.querySelectorAll('.steps-slide');
  var counter = document.querySelector('.steps-slider-counter');
  var dots = document.querySelectorAll('.steps-slider-dot');
  var current = 0;
  var total = slides.length;

  function updateUI() {
    counter.textContent = String(current + 1).padStart(2, '0') + ' / ' + String(total).padStart(2, '0');
    dots.forEach(function(d, i) {
      d.classList.toggle('is-active', i === current);
    });
  }

  function goTo(idx) {
    if (idx < 0 || idx >= total) return;
    current = idx;
    slides[idx].scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
    updateUI();
  }

  // Détecter le slide visible au scroll
  var scrollTimer;
  track.addEventListener('scroll', function() {
    clearTimeout(scrollTimer);
    scrollTimer = setTimeout(function() {
      var scrollLeft = track.scrollLeft;
      var slideWidth = slides[0].offsetWidth + 24; // gap 1.5rem ≈ 24px
      var idx = Math.round(scrollLeft / slideWidth);
      if (idx !== current && idx >= 0 && idx < total) {
        current = idx;
        updateUI();
      }
    }, 80);
  }, { passive: true });

  document.querySelector('.steps-slider-prev').addEventListener('click', function() { goTo(current - 1); });
  document.querySelector('.steps-slider-next').addEventListener('click', function() { goTo(current + 1); });

  dots.forEach(function(dot) {
    dot.addEventListener('click', function() { goTo(parseInt(this.dataset.idx)); });
  });
})();
</script>

<!-- Values Section -->
<section class="artisan-values artisan-values-cinetique">
  <div class="artisan-values-header">
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
      <p>Tout est fabriqué à la main dans mon atelier à Lyon, du dessin à l'expédition. Envie de voir les luminaires en vrai ? <a href="<?php echo esc_url(home_url('/contact/')); ?>">Contactez-moi</a></p>
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
      <p>Bois issus de forêts gérées, production locale et à la commande, emballages recyclables ou récupérés.</p>
    </div>
  </div>
</section>

<section class="artisan-quote artisan-quote-cinetique">
  <?php echo sapi_image('2025/04/Vue-detail-1.jpg', 'large', ['alt' => "Détail d'un luminaire en bois Atelier Sâpi", 'class' => 'artisan-quote-img', 'loading' => 'lazy']); ?>
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
    <a href="<?php echo home_url('/mes-creations/'); ?>" class="button">Voir la collection</a>
    <p class="seo-cta-maillage-inline">Besoin de conseils pour choisir ? <a href="<?php echo esc_url(home_url('/conseils-eclaires/')); ?>">Voir les conseils de Robin →</a></p>
  </div>
</section>

<?php
// Schema LocalBusiness — données structurées pour la page artisan
$local_business = [
  '@context' => 'https://schema.org',
  '@type' => 'LocalBusiness',
  'name' => 'Atelier Sâpi',
  'description' => 'Fabrication artisanale de luminaires en bois',
  'url' => home_url('/'),
  'image' => get_theme_mod('custom_logo') ? wp_get_attachment_image_url(get_theme_mod('custom_logo'), 'full') : '',
  'telephone' => '+33680435585',
  'email' => 'contact@atelier-sapi.fr',
  'address' => [
    '@type' => 'PostalAddress',
    'streetAddress' => '3 rue Pierre Termier',
    'addressLocality' => 'Collonges-au-Mont-d\'Or',
    'postalCode' => '69660',
    'addressCountry' => 'FR'
  ],
  'geo' => [
    '@type' => 'GeoCoordinates',
    'latitude' => 45.8165,
    'longitude' => 4.8403
  ],
  'openingHoursSpecification' => [
    [
      '@type' => 'OpeningHoursSpecification',
      'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
      'opens' => '09:00',
      'closes' => '19:00'
    ]
  ],
  'priceRange' => '€€',
  'sameAs' => [
    'https://www.instagram.com/atelier.sapi/',
    'https://www.facebook.com/ateliersapi'
  ]
];
echo '<script type="application/ld+json">' . wp_json_encode($local_business, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

get_footer();

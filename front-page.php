<?php
/**
 * Front Page Template - CINÉTIQUE Design
 *
 * @package Theme_Sapi_Maison
 */

get_header();

// Featured products for Bento grid
$featured_products = [
  [
    'name' => 'Timothée l\'Araignée',
    'category' => 'Suspension · Chêne',
    'price' => '389€',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/10/Bandeau.jpg',
    'url' => '/nos-creations/timothee-laraignee/',
    'badge' => 'Nouveau',
  ],
  [
    'name' => 'Claudine la Turbine',
    'category' => 'Lampadaire · Design cinétique',
    'price' => '259€',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Claudine.jpg',
    'url' => '/nos-creations/claudine-la-turbine/',
    'badge' => null,
  ],
  [
    'name' => 'Suze la Méduse',
    'category' => 'Applique · Formes organiques',
    'price' => '129€',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg',
    'url' => '/nos-creations/suze-la-meduse/',
    'badge' => null,
  ],
];

// Collections
$collections = [
  [
    'name' => 'Suspensions',
    'count' => '12 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg',
    'url' => '/categorie-produit/suspension/',
  ],
  [
    'name' => 'Lampadaires',
    'count' => '8 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Claudine.jpg',
    'url' => '/categorie-produit/lampadaire/',
  ],
  [
    'name' => 'Appliques',
    'count' => '6 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Face-allumee-1.jpg',
    'url' => '/categorie-produit/applique/',
  ],
  [
    'name' => 'À poser',
    'count' => '5 créations',
    'image' => 'https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Charlie-Bandeau-2.jpg',
    'url' => '/categorie-produit/lampe-a-poser/',
  ],
];
?>

<!-- Custom Cursor (desktop only, hidden on touch devices) -->
<div class="cursor-custom">
  <div class="cursor-dot"></div>
  <div class="cursor-outline"></div>
</div>

<!-- Hero Bento Grid -->
<section class="hero-bento">
  <div class="bento-container">
    <!-- Large Hero Card -->
    <div class="bento-card bento-hero">
      <div class="bento-bg" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg');"></div>
      <div class="bento-content">
        <div class="bento-label">Pièce signature</div>
        <h1 class="bento-title">Sculpter<br>la lumière</h1>
        <p class="bento-text">Des créations artisanales qui transforment l'espace</p>
        <div class="hero-cta-row">
          <a href="<?php echo home_url('/nos-creations/'); ?>" class="hero-cta">
            <span>Découvrir nos créations</span>
            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
              <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
            </svg>
          </a>
          <div class="bento-corner-info">
            <span class="corner-label">À partir de</span>
            <strong class="corner-price">219€</strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Statement Card -->
    <div class="bento-card bento-statement">
      <div class="statement-inner">
        <span class="statement-number">01</span>
        <h2 class="statement-text">"Je ne fabrique pas<br>des lampes.<br>Je crée des présences."</h2>
        <p class="statement-author">— Robin, artisan</p>
      </div>
    </div>

    <!-- Product Card 1 -->
    <a href="<?php echo esc_url($featured_products[0]['url']); ?>" class="bento-card bento-product">
      <div class="product-image" style="background-image: url('<?php echo esc_url($featured_products[0]['image']); ?>');"></div>
      <div class="product-overlay">
        <?php if ($featured_products[0]['badge']) : ?>
          <div class="product-badge"><?php echo esc_html($featured_products[0]['badge']); ?></div>
        <?php endif; ?>
        <div class="product-info-reveal">
          <h3 class="product-name"><?php echo esc_html($featured_products[0]['name']); ?></h3>
          <p class="product-cat"><?php echo esc_html($featured_products[0]['category']); ?></p>
          <div class="product-price-tag">
            <span><?php echo esc_html($featured_products[0]['price']); ?></span>
          </div>
        </div>
      </div>
    </a>

    <!-- Stats Card -->
    <div class="bento-card bento-stats">
      <a href="<?php echo home_url('/lumiere-dartisan/'); ?>" class="stat-block">
        <div class="stat-content">
          <strong>100%</strong>
          <span>Fait main</span>
        </div>
        <div class="stat-hover">
          <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/Robin-Sapi-A.jpg" alt="Robin à l'atelier">
          <span class="stat-hover-text">Découvrir l'artisan →</span>
        </div>
      </a>
      <a href="<?php echo home_url('/lumiere-dartisan/#savoir-faire'); ?>" class="stat-block">
        <div class="stat-content">
          <strong>&lt;5j</strong>
          <span>Fabrication</span>
        </div>
        <div class="stat-hover">
          <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/07/Charlie-Bandeau-2.jpg" alt="Lampe dans un intérieur">
          <span class="stat-hover-text">Le processus →</span>
        </div>
      </a>
      <a href="<?php echo home_url('/contact/'); ?>" class="stat-block">
        <div class="stat-content">
          <strong>Lyon</strong>
          <span>Atelier</span>
        </div>
        <div class="stat-hover">
          <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/12/Olivia-La-gardiena.jpg" alt="Création dans un appartement">
          <span class="stat-hover-text">Nous contacter →</span>
        </div>
      </a>
    </div>

    <!-- Product Card 2 -->
    <a href="<?php echo esc_url($featured_products[1]['url']); ?>" class="bento-card bento-product">
      <div class="product-image" style="background-image: url('<?php echo esc_url($featured_products[1]['image']); ?>');"></div>
      <div class="product-overlay">
        <?php if ($featured_products[1]['badge']) : ?>
          <div class="product-badge"><?php echo esc_html($featured_products[1]['badge']); ?></div>
        <?php endif; ?>
        <div class="product-info-reveal">
          <h3 class="product-name"><?php echo esc_html($featured_products[1]['name']); ?></h3>
          <p class="product-cat"><?php echo esc_html($featured_products[1]['category']); ?></p>
          <div class="product-price-tag">
            <span><?php echo esc_html($featured_products[1]['price']); ?></span>
          </div>
        </div>
      </div>
    </a>

    <!-- Process Card -->
    <div class="bento-card bento-process">
      <div class="process-inner">
        <div class="process-step">
          <span class="step-num">01</span>
          <span class="step-text">Dessin</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/03/ordi_sapi2.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">02</span>
          <span class="step-text">Découpe laser</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/03/detail_sapi.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">03</span>
          <span class="step-text">Assemblage</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/Robin-Sapi-A.jpg');"></div>
        </div>
        <div class="process-step">
          <span class="step-num">04</span>
          <span class="step-text">Finitions</span>
          <div class="step-image" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/03/P_SLM_XL_det5.jpg');"></div>
        </div>
      </div>
    </div>

    <!-- Product Card 3 -->
    <a href="<?php echo esc_url($featured_products[2]['url']); ?>" class="bento-card bento-product">
      <div class="product-image" style="background-image: url('<?php echo esc_url($featured_products[2]['image']); ?>');"></div>
      <div class="product-overlay">
        <?php if ($featured_products[2]['badge']) : ?>
          <div class="product-badge"><?php echo esc_html($featured_products[2]['badge']); ?></div>
        <?php endif; ?>
        <div class="product-info-reveal">
          <h3 class="product-name"><?php echo esc_html($featured_products[2]['name']); ?></h3>
          <p class="product-cat"><?php echo esc_html($featured_products[2]['category']); ?></p>
          <div class="product-price-tag">
            <span><?php echo esc_html($featured_products[2]['price']); ?></span>
          </div>
        </div>
      </div>
    </a>

    <!-- Atelier Image -->
    <div class="bento-card bento-atelier">
      <div class="bento-bg" style="background-image: url('https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2025/05/Robin-Sapi-A.jpg');"></div>
      <div class="atelier-label">
        <span>L'atelier · Lyon</span>
      </div>
    </div>

    <!-- CTA Card -->
    <a href="<?php echo home_url('/nos-creations/'); ?>" class="bento-card bento-cta">
      <h3 class="cta-title">Toutes les créations</h3>
      <span class="cta-button">
        <span>Explorer</span>
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
          <path d="M5 12H19M19 12L12 5M19 12L12 19" stroke="currentColor" stroke-width="2"/>
        </svg>
      </span>
    </a>
  </div>
</section>

<!-- Collections Grid -->
<section class="collections-kinetic">
  <div class="section-header-kinetic">
    <span class="section-num">02</span>
    <h2 class="section-title-kinetic">Collections</h2>
  </div>

  <div class="collections-grid">
    <?php foreach ($collections as $collection) : ?>
      <a href="<?php echo esc_url($collection['url']); ?>" class="collection-card">
        <div class="collection-visual" style="background-image: url('<?php echo esc_url($collection['image']); ?>');"></div>
        <div class="collection-details">
          <h3><?php echo esc_html($collection['name']); ?></h3>
          <div class="collection-meta">
            <span class="collection-count"><?php echo esc_html($collection['count']); ?></span>
            <span class="collection-btn">→</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-kinetic">
  <div class="newsletter-content">
    <div class="newsletter-text">
      <span class="section-num">03</span>
      <h2 class="section-title-kinetic">Restez<br>informés</h2>
      <p>Nouveautés, éditions limitées, coulisses d'atelier.</p>
    </div>
    <form class="newsletter-form" action="#" method="post">
      <input type="email" placeholder="votre@email.fr" class="newsletter-input-kinetic" required />
      <button type="submit" class="newsletter-submit-kinetic">
        <span>S'inscrire</span>
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
          <path d="M4 10H16M16 10L10 4M16 10L10 16" stroke="currentColor" stroke-width="2"/>
        </svg>
      </button>
    </form>
  </div>
</section>

<?php
get_footer();

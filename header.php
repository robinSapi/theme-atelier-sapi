<?php
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
  <div class="header-inner">
    <div class="site-logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <img src="https://www.testlumineux.atelier-sapi.fr/wp-content/uploads/2024/12/logo_sapi.svg" alt="Atelier Sâpi" class="custom-logo">
      </a>
    </div>

    <!-- Menu Desktop -->
    <nav class="primary-nav" aria-label="Menu principal">
      <ul class="nav-menu">
        <li><a href="<?php echo home_url('/'); ?>">Accueil</a></li>
        <li class="menu-item-has-children">
          <a href="<?php echo home_url('/boutique/'); ?>">Nos créations</a>
          <ul class="sub-menu">
            <li><a href="<?php echo home_url('/categorie-produit/suspension/'); ?>">Suspensions</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampadaire/'); ?>">Lampadaires</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/applique/'); ?>">Appliques</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampe-a-poser/'); ?>">À poser</a></li>
          </ul>
        </li>
        <li><a href="<?php echo home_url('/lumiere-dartisan/'); ?>">L'artisan</a></li>
        <li><a href="<?php echo home_url('/conseils-eclaires/'); ?>">Conseils</a></li>
        <li><a href="<?php echo home_url('/contact/'); ?>">Contact</a></li>
      </ul>
    </nav>

    <div class="header-actions">
      <button class="cart-link mini-cart-toggle" aria-label="<?php esc_attr_e('Ouvrir le panier', 'theme-sapi-maison'); ?>" aria-expanded="false" aria-controls="mini-cart">
        <span class="cart-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
            <line x1="3" y1="6" x2="21" y2="6"/>
            <path d="M16 10a4 4 0 0 1-8 0"/>
          </svg>
        </span>
        <?php $cart_count = sapi_maison_cart_count(); ?>
        <span class="cart-count<?php echo $cart_count === 0 ? ' cart-count--empty' : ''; ?>"><?php echo $cart_count > 0 ? esc_html($cart_count) : ''; ?></span>
      </button>

      <!-- Menu Burger Toggle -->
      <button class="menu-toggle" aria-label="Menu" aria-expanded="false" aria-controls="mobile-menu">
        <span class="menu-burger">
          <span class="burger-line"></span>
          <span class="burger-line"></span>
          <span class="burger-line"></span>
        </span>
        <span class="menu-toggle-text">Menu</span>
      </button>
    </div>
  </div>

  <!-- Mobile Menu Overlay -->
  <div class="mobile-menu-overlay" id="mobile-menu">
    <nav class="mobile-menu-nav" aria-label="Menu mobile">
      <ul class="mobile-nav-menu">
        <li><a href="<?php echo home_url('/'); ?>">Accueil</a></li>
        <li class="menu-item-has-children">
          <a href="<?php echo home_url('/boutique/'); ?>">Nos créations</a>
          <ul class="sub-menu">
            <li><a href="<?php echo home_url('/categorie-produit/suspension/'); ?>">Suspensions</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampadaire/'); ?>">Lampadaires</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/applique/'); ?>">Appliques</a></li>
            <li><a href="<?php echo home_url('/categorie-produit/lampe-a-poser/'); ?>">À poser</a></li>
          </ul>
        </li>
        <li><a href="<?php echo home_url('/lumiere-dartisan/'); ?>">L'artisan</a></li>
        <li><a href="<?php echo home_url('/conseils-eclaires/'); ?>">Conseils</a></li>
        <li><a href="<?php echo home_url('/contact/'); ?>"><?php esc_html_e('Contact', 'theme-sapi-maison'); ?></a></li>
      </ul>
    </nav>
  </div>

  <!-- Mini Cart Sliding Panel -->
  <div class="mini-cart" id="mini-cart" aria-hidden="true">
    <div class="mini-cart-header">
      <h3><?php esc_html_e('Votre panier', 'theme-sapi-maison'); ?></h3>
      <button class="mini-cart-close" aria-label="<?php esc_attr_e('Fermer le panier', 'theme-sapi-maison'); ?>">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <div class="mini-cart-content">
      <?php if (function_exists('sapi_render_mini_cart_contents')) : ?>
        <?php sapi_render_mini_cart_contents(); ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="mini-cart-overlay" id="mini-cart-overlay"></div>
</header>
<main class="site-content">

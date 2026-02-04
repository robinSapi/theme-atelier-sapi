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
      <?php
      if (has_custom_logo()) {
        the_custom_logo();
      } else {
        echo '<a class="site-title" href="' . esc_url(home_url('/')) . '">' . esc_html(get_bloginfo('name')) . '</a>';
      }
      ?>
    </div>

    <!-- Menu Desktop -->
    <nav class="primary-nav" aria-label="Menu principal">
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'container' => false,
        'menu_class' => 'nav-menu',
        'fallback_cb' => function() {
          echo '<ul class="nav-menu">';
          echo '<li><a href="' . home_url('/') . '">Accueil</a></li>';
          echo '<li><a href="' . home_url('/nos-creations/') . '">Nos créations</a></li>';
          echo '<li><a href="' . home_url('/lumiere-dartisan/') . '">L\'artisan</a></li>';
          echo '<li><a href="' . home_url('/conseils-eclaires/') . '">Conseils</a></li>';
          echo '<li><a href="' . home_url('/contact/') . '">Contact</a></li>';
          echo '</ul>';
        },
      ]);
      ?>
    </nav>

    <div class="header-actions">
      <a class="cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>">
        <span class="cart-icon">🛒</span>
        <span class="cart-text">Panier</span>
        <span class="cart-count"><?php echo esc_html(sapi_maison_cart_count()); ?></span>
      </a>

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
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'container' => false,
        'menu_class' => 'mobile-nav-menu',
        'fallback_cb' => function() {
          echo '<ul class="mobile-nav-menu">';
          echo '<li><a href="' . home_url('/') . '">Accueil</a></li>';
          echo '<li><a href="' . home_url('/nos-creations/') . '">Nos créations</a></li>';
          echo '<li><a href="' . home_url('/lumiere-dartisan/') . '">L\'artisan</a></li>';
          echo '<li><a href="' . home_url('/conseils-eclaires/') . '">Conseils</a></li>';
          echo '<li><a href="' . home_url('/contact/') . '">Contact</a></li>';
          echo '</ul>';
        },
      ]);
      ?>
    </nav>
  </div>
</header>
<main class="site-content">

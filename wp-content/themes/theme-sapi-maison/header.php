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
    <nav class="primary-nav" aria-label="Menu principal">
      <?php
      wp_nav_menu([
        'theme_location' => 'primary',
        'container' => false,
        'fallback_cb' => false,
      ]);
      ?>
    </nav>
    <div class="header-actions">
      <a class="cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>">
        Panier
        <span class="cart-count"><?php echo esc_html(sapi_maison_cart_count()); ?></span>
      </a>
    </div>
  </div>
</header>
<main class="site-content">

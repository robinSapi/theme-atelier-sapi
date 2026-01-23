<?php

if (!defined('ABSPATH')) {
  exit;
}

function sapi_maison_setup() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
  add_theme_support('custom-logo', [
    'height' => 120,
    'width' => 300,
    'flex-height' => true,
    'flex-width' => true,
  ]);
  add_theme_support('woocommerce');

  register_nav_menus([
    'primary' => __('Menu principal', 'theme-sapi-maison'),
    'footer' => __('Menu pied de page', 'theme-sapi-maison'),
  ]);
}
add_action('after_setup_theme', 'sapi_maison_setup');

function sapi_maison_enqueue_assets() {
  $fonts = [
    'family' => 'Montserrat:wght@400;500;600;700|Square+Peg:wght@400;500',
    'display' => 'swap',
  ];
  wp_enqueue_style('sapi-maison-fonts', add_query_arg($fonts, 'https://fonts.googleapis.com/css'));
  wp_enqueue_style('sapi-maison-style', get_stylesheet_uri(), ['sapi-maison-fonts'], '0.1.0');
}
add_action('wp_enqueue_scripts', 'sapi_maison_enqueue_assets');

function sapi_maison_content_width() {
  $GLOBALS['content_width'] = 1200;
}
add_action('after_setup_theme', 'sapi_maison_content_width', 0);

function sapi_maison_cart_count() {
  if (!function_exists('WC')) {
    return 0;
  }
  return WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
}


<?php
/**
 * Présentation des options de la fiche produit : une seule carte, une ligne par
 * option (matériau, taille, add-ons, ampoule). Tout le travail est côté
 * navigateur, voir assets/sapi-product-options.js et le bloc « OPTIONS FICHE
 * PRODUIT » de style.css. Maquette validée par Robin le 15/10/2026.
 *
 * Chargé après sapi-ampoule-optin.js : la ligne ampoule doit déjà être en place
 * quand la carte se construit.
 *
 * @package theme-sapi-maison
 */

if (!defined('ABSPATH')) exit;

add_action('wp_enqueue_scripts', function () {
  if (!function_exists('is_product') || !is_product()) return;
  $path = get_template_directory() . '/assets/sapi-product-options.js';
  if (!file_exists($path)) return;
  $deps = ['jquery'];
  if (wp_script_is('sapi-ampoule-optin', 'registered')) $deps[] = 'sapi-ampoule-optin';
  wp_enqueue_script('sapi-product-options', get_template_directory_uri() . '/assets/sapi-product-options.js', $deps, filemtime($path), true);
}, 20);

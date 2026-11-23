<?php
/**
 * Template Name: Conseil de Robin (réseaux)
 *
 * Page cachée pour les publications Instagram / Facebook / Pinterest. Pas de
 * menu ni de pied de page : la modale du Conseiller s'ouvre seule, en plein
 * écran, et c'est tout ce que le visiteur voit. La partie serveur vit dans
 * inc/sapi-conseil-reseaux.php.
 *
 * Mise en place : créer une page WordPress (slug libre, ex. /conseil/), lui
 * donner ce modèle, ne la mettre dans aucun menu. Elle est en noindex.
 */

/* ⚠️ JAMAIS EN CACHE. Le nonce et le time-trap du formulaire sont écrits dans
   le HTML : servie depuis le cache LiteSpeed au-delà de 12-24 h, la page
   refuserait chaque envoi (« la page a expiré »), et recharger resservirait
   la même page. Pour une page qui reçoit tout le trafic des pubs, bloquant. */
if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
do_action('litespeed_control_set_nocache', 'landing Conseil de Robin : nonce dans le formulaire');
nocache_headers();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <?php wp_head(); ?>
  <?php /* ⚠️ LE POPUP « -10 % » NE DOIT PAS SE SUPERPOSER AU CONSEILLER.
           Le snippet cookies (Code Snippets) enchaîne consentement puis écran
           promo email. Ici le visiteur va déjà laisser son email à Robin : un
           second formulaire par-dessus serait absurde. Le snippet saute l'écran
           promo quand ce cookie existe. Cookie de SESSION (sans date) : il
           disparaît à la fermeture du navigateur, on ne retire pas le popup
           pour toujours à quelqu'un qui repartirait sans rien laisser.
           Le bandeau de consentement, lui, s'affiche normalement. */ ?>
  <script>try{if(!/(?:^|;\s*)sapi_promo_dismissed=1/.test(document.cookie)){document.cookie='sapi_promo_dismissed=1; path=/; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');}}catch(e){}</script>
</head>
<body <?php body_class('sapi-landing-conseil'); ?>>
<?php wp_body_open(); ?>
<main class="landing-conseil">
  <noscript>
    <p class="landing-conseil__noscript">
      <?php esc_html_e('Le conseil de Robin a besoin de JavaScript.', 'theme-sapi-maison'); ?>
      <a href="<?php echo esc_url(home_url('/mes-creations/')); ?>"><?php esc_html_e('Voir toutes les créations', 'theme-sapi-maison'); ?></a>
    </p>
  </noscript>
</main>
<?php wp_footer(); ?>
</body>
</html>

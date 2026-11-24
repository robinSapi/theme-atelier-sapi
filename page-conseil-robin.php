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

<?php /* ⚠️ BANDEAU COOKIES DISCRET, À LA PLACE DE LA GRANDE FENÊTRE.
         Décision Robin du 24/11 : sur cette page, la fenêtre plein écran du
         snippet cookies passerait PAR-DESSUS le Conseiller, au moment même où
         le visiteur arrive de sa pub. On la masque (CSS ci-dessous) et on
         affiche une barre fine en bas qui ne bloque rien.
         Même mécanique de consentement que le snippet (API Complianz) : tant
         que personne n'a cliqué « Accepter », aucun traceur ne part. Ignorer
         la barre vaut refus. « Refuser » aussi visible que « Accepter »
         (exigence CNIL).
         Le snippet n'est pas modifié : il reste maître partout ailleurs. */ ?>
<style>
  body.sapi-landing-conseil #sapi-cookie-overlay { display: none !important; }
  .landing-cookies {
    position: fixed; left: 0; right: 0; bottom: 0; z-index: 10060;
    display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 8px 14px;
    padding: 10px 16px calc(10px + env(safe-area-inset-bottom));
    background: #FEFDFB; border-top: 1px solid #E8DCC4;
    font: 12.5px/1.4 'Montserrat', Arial, sans-serif; color: #5C4033;
  }
  .landing-cookies[hidden] { display: none; }
  .landing-cookies p { margin: 0; flex: 1 1 260px; max-width: 560px; }
  .landing-cookies__btns { display: flex; gap: 8px; }
  .landing-cookies button {
    font: 600 12.5px 'Montserrat', Arial, sans-serif; padding: 8px 18px; border-radius: 999px; cursor: pointer;
    border: 1.5px solid #937D68; background: #fff; color: #5C4033;
  }
  /* La barre ne doit pas cacher le pied de la modale (« Non merci… »). */
  body.has-landing-cookies .conseiller-modal.is-landing { padding-bottom: var(--landing-cookies-h, 64px); }
</style>
<div class="landing-cookies" data-landing-cookies hidden role="region" aria-label="<?php esc_attr_e('Cookies', 'theme-sapi-maison'); ?>">
  <p><?php esc_html_e('Avec ton accord, cette page mesure sa fréquentation (Google, Meta, Pinterest). Sans ton accord, rien n\'est mesuré.', 'theme-sapi-maison'); ?></p>
  <span class="landing-cookies__btns">
    <button type="button" data-cookies="deny"><?php esc_html_e('Refuser', 'theme-sapi-maison'); ?></button>
    <button type="button" data-cookies="accept"><?php esc_html_e('Accepter', 'theme-sapi-maison'); ?></button>
  </span>
</div>
<script>
(function () {
  var bar = document.querySelector('[data-landing-cookies]');
  if (!bar) return;
  var c = document.cookie || '';
  /* Choix déjà fait (sur cette page ou ailleurs sur le site) : rien à montrer. */
  if (/(?:^|;\s*)cmplz_banner-status[^=]*=dismissed/.test(c) || /(?:^|;\s*)cmplz_consent_status[^=]*=(allow|deny)/.test(c)) return;

  bar.hidden = false;
  document.body.classList.add('has-landing-cookies');
  document.body.style.setProperty('--landing-cookies-h', bar.offsetHeight + 'px');

  function dismissed() {
    try {
      if (typeof window.cmplz_set_banner_status === 'function') window.cmplz_set_banner_status('dismissed');
      if (typeof window.cmplz_set_cookie === 'function') window.cmplz_set_cookie('banner-status', 'dismissed');
    } catch (e) {}
    var d = new Date(); d.setTime(d.getTime() + 365 * 864e5);
    document.cookie = 'cmplz_banner-status=dismissed; expires=' + d.toUTCString() + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
  }
  /* Même ordre d'appels que le snippet cookies (callComplianz). */
  function choisir(action) {
    try {
      if (action === 'accept' && typeof window.cmplz_accept_all === 'function') window.cmplz_accept_all();
      else if (action === 'deny' && typeof window.cmplz_deny_all === 'function') window.cmplz_deny_all();
      else if (typeof window.cmplz_set_consent === 'function') {
        var v = action === 'accept' ? 'allow' : 'deny';
        ['marketing', 'statistics', 'preferences'].forEach(function (k) { window.cmplz_set_consent(k, v); });
        window.cmplz_set_consent('functional', 'allow');
      }
    } catch (e) {}
    dismissed();
    try { (window.dataLayer = window.dataLayer || []).push({ event: 'cookie_consent', cookie_action: action, cookie_ui: 'landing_bar' }); } catch (e) {}
    bar.hidden = true;
    document.body.classList.remove('has-landing-cookies');
  }
  bar.addEventListener('click', function (e) {
    var b = e.target.closest('[data-cookies]');
    if (b) choisir(b.getAttribute('data-cookies'));
  });
})();
</script>
</body>
</html>

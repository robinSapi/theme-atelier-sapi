<?php
$is_simplified = function_exists('is_cart') && (is_cart() || is_checkout());
?>
</main>

<?php if ($is_simplified) : ?>

<footer class="site-footer site-footer--simplified">
  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Atelier Sâpi &mdash; Tous droits réservés</p>
    <div class="footer-legal">
      <?php
      wp_nav_menu([
        'theme_location' => 'footer_legal',
        'container'      => false,
        'items_wrap'     => '%3$s',
        'walker'         => new Sapi_Footer_Walker(),
        'fallback_cb'    => 'sapi_fallback_legal_menu',
        'depth'          => 1,
      ]);
      ?>
    </div>
  </div>
</footer>

<?php else : ?>

<footer class="site-footer footer-kinetic">
  <div class="footer-grid">
    <!-- Brand Column -->
    <div class="footer-brand">
      <a href="<?php echo esc_url(home_url('/')); ?>" class="footer-logo">Atelier Sâpi</a>
      <p>Luminaires artisanaux en bois, conçus et fabriqués à Lyon. Chaque création est unique, sculptée par la lumière et l'amour du bois.</p>
    </div>

    <!-- Navigation Column -->
    <div class="footer-col">
      <h4>Navigation</h4>
      <nav class="footer-nav" aria-label="Menu pied de page">
        <?php
        wp_nav_menu([
          'theme_location' => 'footer',
          'container'      => false,
          'items_wrap'     => '%3$s',
          'walker'         => new Sapi_Footer_Walker(),
          'fallback_cb'    => 'sapi_fallback_footer_nav',
          'depth'          => 1,
        ]);
        ?>
      </nav>
    </div>

    <!-- Contact Column -->
    <div class="footer-col">
      <h4>Contact</h4>
      <a href="mailto:contact@atelier-sapi.fr">contact@atelier-sapi.fr</a>
      <a href="tel:+33680435585">06 80 43 55 85</a>
      <span class="footer-location">Lyon, France</span>
    </div>

    <!-- Social Column -->
    <div class="footer-col">
      <h4>Suivez-nous</h4>
      <?php
      wp_nav_menu([
        'theme_location' => 'footer_social',
        'container'      => false,
        'items_wrap'     => '%3$s',
        'walker'         => new Sapi_Footer_Walker(),
        'fallback_cb'    => 'sapi_fallback_social_menu',
        'depth'          => 1,
      ]);
      ?>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Atelier Sâpi &mdash; Tous droits réservés</p>
    <div class="footer-legal">
      <?php
      wp_nav_menu([
        'theme_location' => 'footer_legal',
        'container'      => false,
        'items_wrap'     => '%3$s',
        'walker'         => new Sapi_Footer_Walker(),
        'fallback_cb'    => 'sapi_fallback_legal_menu',
        'depth'          => 1,
      ]);
      ?>
    </div>
  </div>
</footer>

<?php endif; ?>

<!-- Quick View Modal -->
<div class="quick-view-modal" id="quick-view-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="quick-view-title">
  <div class="quick-view-overlay"></div>
  <div class="quick-view-content">
    <button class="quick-view-close" aria-label="<?php esc_attr_e('Fermer l\'aperçu', 'theme-sapi-maison'); ?>">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>
    <div class="quick-view-loading">
      <svg class="spinner" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
      </svg>
      <p><?php esc_html_e('Chargement...', 'theme-sapi-maison'); ?></p>
    </div>
    <div class="quick-view-body"></div>
  </div>
</div>

<?php
if (defined('SAPI_ROBIN_V2') && SAPI_ROBIN_V2) {
  require_once get_template_directory() . '/inc/template-robin-modal.php';
  sapi_robin_modal();
}
?>

<?php wp_footer(); ?>
</body>
</html>

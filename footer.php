<?php
?>
</main>
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
        <a href="<?php echo home_url('/nos-creations/'); ?>">Nos créations</a>
        <a href="<?php echo home_url('/lumiere-dartisan/'); ?>">L'artisan</a>
        <a href="<?php echo home_url('/conseils-eclaires/'); ?>">Conseils</a>
        <a href="<?php echo home_url('/contact/'); ?>">Contact</a>
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
      <a href="https://www.instagram.com/atelier_sapi/" target="_blank" rel="noopener">Instagram</a>
      <a href="https://www.facebook.com/ateliersapi" target="_blank" rel="noopener">Facebook</a>
      <a href="https://www.pinterest.fr/ateliersapi/" target="_blank" rel="noopener">Pinterest</a>
      <a href="<?php echo esc_url(home_url('/actus/')); ?>">Actus</a>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> Atelier Sâpi &mdash; Tous droits réservés</p>
    <div class="footer-legal">
      <a href="<?php echo home_url('/mentions-legales/'); ?>">Mentions légales</a>
      <a href="<?php echo home_url('/cgv/'); ?>">CGV</a>
      <a href="<?php echo home_url('/politique-de-confidentialite/'); ?>">Confidentialité</a>
    </div>
  </div>
</footer>

<!-- Quick View Modal -->
<div class="quick-view-modal" id="quick-view-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="quick-view-title">
  <div class="quick-view-overlay"></div>
  <div class="quick-view-content">
    <button type="button" class="quick-view-close" aria-label="<?php esc_attr_e('Fermer l\'aperçu', 'theme-sapi-maison'); ?>">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
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

<?php wp_footer(); ?>
</body>
</html>

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
      <a href="tel:+33600000000">06 00 00 00 00</a>
      <span class="footer-location">Lyon, France</span>
    </div>

    <!-- Social Column -->
    <div class="footer-col">
      <h4>Suivez-nous</h4>
      <a href="https://www.instagram.com/atelier_sapi/" target="_blank" rel="noopener">Instagram</a>
      <a href="https://www.facebook.com/ateliersapi" target="_blank" rel="noopener">Facebook</a>
      <a href="https://www.pinterest.fr/ateliersapi/" target="_blank" rel="noopener">Pinterest</a>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?> &mdash; Tous droits réservés</p>
    <div class="footer-legal">
      <a href="<?php echo home_url('/mentions-legales/'); ?>">Mentions légales</a>
      <a href="<?php echo home_url('/cgv/'); ?>">CGV</a>
      <a href="<?php echo home_url('/politique-de-confidentialite/'); ?>">Confidentialité</a>
    </div>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

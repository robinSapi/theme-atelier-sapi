<?php
?>
</main>
<footer class="site-footer">
  <div class="footer-inner">
    <div>
      <p><?php echo esc_html(get_bloginfo('name')); ?> &mdash; Tous droits réservés.</p>
    </div>
    <nav class="footer-nav" aria-label="Menu pied de page">
      <?php
      wp_nav_menu([
        'theme_location' => 'footer',
        'container' => false,
        'fallback_cb' => false,
      ]);
      ?>
    </nav>
  </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>

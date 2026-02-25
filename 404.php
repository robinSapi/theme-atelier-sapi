<?php
get_header();
?>

<section class="error-404-page">
  <div class="error-404-content">
    <span class="error-404-code">404</span>
    <h1 class="error-404-title">Oups, cette lumière s'est éteinte...</h1>
    <p class="error-404-text">La page que vous cherchez semble avoir disparu. Mais pas de panique, on peut retrouver la lumière ensemble&nbsp;!</p>

    <form role="search" method="get" class="error-404-search" action="<?php echo esc_url(home_url('/')); ?>">
      <label for="error-search" class="screen-reader-text">Rechercher</label>
      <input type="search" id="error-search" name="s" placeholder="Rechercher pour retrouver la lumière..." />
      <button type="submit" aria-label="Rechercher">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>
    </form>

    <a href="<?php echo esc_url(home_url('/')); ?>" class="error-404-btn">Retour à l'accueil</a>
  </div>
</section>

<?php
get_footer();

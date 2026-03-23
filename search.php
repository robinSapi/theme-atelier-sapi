<?php
get_header();
$search_query = get_search_query();
?>

<section class="search-results-page">
  <div class="search-results-header">
    <h1>Résultats pour <em>&laquo;&nbsp;<?php echo esc_html($search_query); ?>&nbsp;&raquo;</em></h1>

    <form role="search" method="get" class="search-results-form" action="<?php echo esc_url(home_url('/')); ?>">
      <label for="search-input" class="screen-reader-text">Rechercher</label>
      <input type="search" id="search-input" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="Affiner votre recherche..." />
      <button type="submit" aria-label="Rechercher">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>
    </form>
  </div>

  <?php if (have_posts()) : ?>
    <div class="search-results-grid">
      <?php while (have_posts()) : the_post(); ?>
        <a href="<?php the_permalink(); ?>" class="search-result-card">
          <?php if (has_post_thumbnail()) : ?>
            <div class="search-result-image">
              <?php the_post_thumbnail('medium'); ?>
            </div>
          <?php endif; ?>
          <div class="search-result-info">
            <h2><?php the_title(); ?></h2>
            <?php if (get_post_type() === 'product') :
              $product_obj = wc_get_product(get_the_ID());
              if ($product_obj) : ?>
                <span class="search-result-price"><?php echo $product_obj->get_price_html(); ?></span>
              <?php endif; ?>
            <?php else : ?>
              <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
            <?php endif; ?>
            <span class="search-result-type"><?php echo get_post_type() === 'product' ? 'Luminaire' : 'Article'; ?></span>
          </div>
        </a>
      <?php endwhile; ?>
    </div>

    <?php the_posts_navigation([
      'prev_text' => '&larr; Résultats précédents',
      'next_text' => 'Résultats suivants &rarr;',
    ]); ?>

  <?php else : ?>
    <div class="search-no-results">
      <p>Aucun résultat pour cette recherche. Essayez avec d'autres termes&nbsp;!</p>
      <a href="<?php echo esc_url(home_url('/mes-creations/')); ?>" class="search-browse-btn">Parcourir les créations</a>
    </div>
  <?php endif; ?>
</section>

<?php
get_footer();

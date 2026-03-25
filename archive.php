<?php
get_header();

// Supprime le préfixe et les balises HTML du titre
$archive_title = wp_strip_all_tags(get_the_archive_title());
$archive_title = preg_replace('/^.+:\s*/', '', $archive_title);
?>

<!-- Archive Hero -->
<section class="blog-hero">
  <div class="blog-hero-inner">
    <h1><?php echo esc_html($archive_title); ?></h1>
    <?php if (get_the_archive_description()) : ?>
      <p><?php echo wp_kses_post(get_the_archive_description()); ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- Grille articles -->
<section class="blog-grid-section">
  <div class="blog-grid-container">
    <?php if (have_posts()) : ?>
      <div class="blog-grid">
        <?php while (have_posts()) : the_post(); ?>
          <article <?php post_class('blog-grid-card'); ?>>
            <?php if (has_post_thumbnail()) : ?>
              <div class="blog-grid-media">
                <a href="<?php the_permalink(); ?>">
                  <?php echo wp_get_attachment_image(get_post_thumbnail_id(), 'large'); ?>
                </a>
              </div>
            <?php endif; ?>
            <div class="blog-grid-content">
              <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              <p><?php echo wp_trim_words(get_the_excerpt(), 20, '...'); ?></p>
              <div class="blog-grid-meta">
                <span class="blog-grid-date"><?php echo esc_html(get_the_date('d/m/Y')); ?></span>
                <a href="<?php the_permalink(); ?>" class="blog-grid-link">Lire →</a>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>

      <?php
      global $wp_query;
      if ($wp_query->max_num_pages > 1) : ?>
        <nav class="blog-pagination" role="navigation">
          <?php
          echo paginate_links([
            'total'     => $wp_query->max_num_pages,
            'current'   => max(1, get_query_var('paged')),
            'prev_text' => '← Précédent',
            'next_text' => 'Suivant →',
            'mid_size'  => 2
          ]);
          ?>
        </nav>
      <?php endif; ?>

    <?php else : ?>
      <div class="blog-no-posts">
        <p>Aucun article trouvé.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php
get_footer();

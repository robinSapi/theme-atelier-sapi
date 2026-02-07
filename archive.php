<?php
get_header();
?>

<!-- Blog Archive Hero Premium -->
<section class="blog-hero">
  <div class="blog-hero-content">
    <h1><?php echo esc_html(get_the_archive_title()); ?></h1>
    <?php if (get_the_archive_description()) : ?>
      <p><?php echo wp_kses_post(get_the_archive_description()); ?></p>
    <?php endif; ?>
  </div>
</section>

<!-- Blog Grid Premium -->
<section class="blog-list">
  <?php if (have_posts()) : ?>
    <div class="blog-grid">
      <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('blog-card'); ?>>
          <?php if (has_post_thumbnail()) : ?>
            <div class="blog-card-media">
              <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('medium_large'); ?>
              </a>
            </div>
          <?php endif; ?>

          <div class="blog-card-content">
            <h2>
              <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
            </h2>

            <div class="blog-card-meta">
              <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
                <?php echo esc_html(get_the_date()); ?>
              </time>
            </div>

            <?php if (has_excerpt()) : ?>
              <p><?php echo esc_html(get_the_excerpt()); ?></p>
            <?php endif; ?>

            <a href="<?php the_permalink(); ?>" class="blog-card-link">Lire la suite →</a>
          </div>
        </article>
      <?php endwhile; ?>
    </div>

    <div class="blog-navigation">
      <?php the_posts_navigation(array(
        'prev_text' => '← Articles précédents',
        'next_text' => 'Articles suivants →',
      )); ?>
    </div>

  <?php else : ?>
    <div class="blog-no-posts">
      <p>Aucun article trouvé.</p>
    </div>
  <?php endif; ?>
</section>

<?php
get_footer();

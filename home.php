<?php
get_header();
?>

<section class="blog-hero">
  <div class="blog-hero-inner">
    <h1>Les actus</h1>
    <p>Les nouveautés lumineuses, conseils et inspirations de l'atelier.</p>
  </div>
</section>

<section class="blog-list">
  <?php if (have_posts()) : ?>
    <div class="blog-grid">
      <?php while (have_posts()) : ?>
        <?php the_post(); ?>
        <article <?php post_class('blog-card'); ?>>
          <?php if (has_post_thumbnail()) : ?>
            <div class="blog-card-media">
              <a href="<?php the_permalink(); ?>">
                <?php the_post_thumbnail('large'); ?>
              </a>
            </div>
          <?php endif; ?>
          <div class="blog-card-content">
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <p><?php echo wp_kses_post(get_the_excerpt()); ?></p>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
    <?php the_posts_navigation(); ?>
  <?php else : ?>
    <p>Aucun article pour le moment.</p>
  <?php endif; ?>
</section>

<?php
get_footer();

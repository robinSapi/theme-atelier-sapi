<?php
get_header();
?>

<section class="blog-hero">
  <div class="blog-hero-inner">
    <h1>Les actus</h1>
    <p>Les nouveautés lumineuses, conseils et inspirations de l'atelier.</p>
  </div>
</section>

<!-- Article vedette : dernier article publié -->
<?php
$featured_id = 0;
$featured_query = new WP_Query([
  'posts_per_page' => 1,
  'post_status' => 'publish',
  'category_name' => 'flash-actu',
  'orderby' => 'date',
  'order' => 'DESC'
]);

if ($featured_query->have_posts()) :
  $featured_query->the_post();
  $featured_id = get_the_ID();
?>
<section class="blog-featured-hero">
  <a href="<?php the_permalink(); ?>" class="blog-featured-link">
    <div class="blog-featured-media">
      <?php if (has_post_thumbnail()) : ?>
        <?php echo wp_get_attachment_image(get_post_thumbnail_id(), 'large', false, [
          'class' => 'blog-featured-bg'
        ]); ?>
      <?php endif; ?>
    </div>
    <div class="blog-featured-content">
      <span class="blog-featured-date"><?php echo esc_html(get_the_date('d/m/Y')); ?></span>
      <h2><?php echo esc_html(get_the_title()); ?></h2>
      <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 30, '...')); ?></p>
      <span class="blog-featured-cta">Lire l'article →</span>
    </div>
  </a>
</section>
<?php
  wp_reset_postdata();
endif;
?>

<!-- Grille: Tous les autres articles -->
<section class="blog-grid-section">
  <div class="blog-grid-container">
    <?php
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    $grid_query = new WP_Query([
      'posts_per_page' => 9,
      'post_status' => 'publish',
      'category_name' => 'flash-actu',
      'orderby' => 'date',
      'order' => 'DESC',
      'post__not_in' => $featured_id ? [$featured_id] : [],
      'paged' => $paged
    ]);

    if ($grid_query->have_posts()) :
    ?>
      <div class="blog-grid">
        <?php while ($grid_query->have_posts()) : $grid_query->the_post(); ?>
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
                <span class="blog-grid-date"><?php echo get_the_date('d/m/Y'); ?></span>
                <a href="<?php the_permalink(); ?>" class="blog-grid-link">Lire →</a>
              </div>
            </div>
          </article>
        <?php endwhile; ?>
      </div>

      <!-- Pagination -->
      <?php if ($grid_query->max_num_pages > 1) : ?>
        <nav class="blog-pagination" role="navigation">
          <?php
          echo paginate_links([
            'total' => $grid_query->max_num_pages,
            'current' => $paged,
            'prev_text' => '← Précédent',
            'next_text' => 'Suivant →',
            'mid_size' => 2
          ]);
          ?>
        </nav>
      <?php endif; ?>

    <?php
    wp_reset_postdata();
    endif;
    ?>
  </div>
</section>

<?php
get_footer();

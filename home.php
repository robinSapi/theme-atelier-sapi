<?php
get_header();
?>

<section class="blog-hero">
  <div class="blog-hero-inner">
    <h1>Les actus</h1>
    <p>Les nouveautés lumineuses, conseils et inspirations de l'atelier.</p>
  </div>
</section>

<!-- Carousel: 5 derniers articles -->
<section class="blog-carousel-section">
  <?php
  // Query pour les 5 derniers articles (carousel)
  $carousel_query = new WP_Query([
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
  ]);

  $carousel_posts = []; // Initialiser AVANT le if pour éviter les erreurs

  if ($carousel_query->have_posts()) :
    while ($carousel_query->have_posts()) {
      $carousel_query->the_post();
      $carousel_posts[] = [
        'id' => get_the_ID(),
        'title' => get_the_title(),
        'permalink' => get_permalink(),
        'excerpt' => wp_trim_words(get_the_excerpt(), 25, '...'),
        'thumbnail_id' => get_post_thumbnail_id(),
        'has_thumbnail' => has_post_thumbnail(),
        'date' => get_the_date('d/m/Y'),
        'classes' => get_post_class('blog-card')
      ];
    }
    $total_carousel = count($carousel_posts);
    wp_reset_postdata();
  ?>

    <div class="blog-carousel-container">
      <button class="carousel-nav carousel-prev" aria-label="Article précédent">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>

      <div class="blog-carousel" data-total="<?php echo esc_attr($total_carousel); ?>">
        <div class="blog-carousel-track">
          <?php foreach ($carousel_posts as $index => $post) : ?>
            <article class="<?php echo esc_attr(implode(' ', $post['classes'])); ?>" data-index="<?php echo esc_attr($index); ?>">
              <?php if ($post['has_thumbnail'] && $post['thumbnail_id']) : ?>
                <div class="blog-card-media">
                  <a href="<?php echo esc_url($post['permalink']); ?>">
                    <?php echo wp_get_attachment_image($post['thumbnail_id'], 'large'); ?>
                  </a>
                </div>
              <?php endif; ?>
              <div class="blog-card-content">
                <h2><a href="<?php echo esc_url($post['permalink']); ?>"><?php echo esc_html($post['title']); ?></a></h2>
                <p><?php echo wp_kses_post($post['excerpt']); ?></p>
                <div class="blog-card-date"><?php echo esc_html($post['date']); ?></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <button class="carousel-nav carousel-next" aria-label="Article suivant">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
          <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
      </button>
    </div>

  <?php endif; ?>
</section>

<!-- Grille: Tous les autres articles -->
<section class="blog-grid-section">
  <div class="blog-grid-container">
    <?php
    // Récupérer les IDs des articles du carousel pour les exclure
    $carousel_ids = array_column($carousel_posts, 'id');

    // Pagination
    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

    // Query pour tous les autres articles (grille)
    $grid_query = new WP_Query([
      'posts_per_page' => 9,
      'post_status' => 'publish',
      'orderby' => 'date',
      'order' => 'DESC',
      'post__not_in' => $carousel_ids,
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
                  <?php the_post_thumbnail('medium_large'); ?>
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

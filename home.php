<?php
get_header();
?>

<section class="blog-hero">
  <div class="blog-hero-inner">
    <h1>Les actus</h1>
    <p>Les nouveautés lumineuses, conseils et inspirations de l'atelier.</p>
  </div>
</section>

<section class="blog-timeline-section">
  <?php if (have_posts()) : ?>
    <?php
    // Store all posts for timeline and carousel
    $all_posts = [];
    while (have_posts()) {
      the_post();
      $all_posts[] = [
        'id' => get_the_ID(),
        'title' => get_the_title(),
        'permalink' => get_permalink(),
        'excerpt' => wp_trim_words(get_the_excerpt(), 25, '...'),
        'thumbnail_id' => get_post_thumbnail_id(),
        'has_thumbnail' => has_post_thumbnail(),
        'date' => get_the_date('d/m/Y'),
        'timestamp' => get_the_time('U'),
        'classes' => get_post_class('blog-card')
      ];
    }
    $total_posts = count($all_posts);
    ?>

    <!-- Timeline -->
    <div class="blog-timeline">
      <div class="timeline-track">
        <?php foreach ($all_posts as $index => $post) : ?>
          <button
            class="timeline-dot <?php echo $index === 0 ? 'active' : ''; ?>"
            data-article-index="<?php echo esc_attr($index); ?>"
            aria-label="<?php echo esc_attr('Article : ' . $post['title']); ?>"
            title="<?php echo esc_attr($post['date'] . ' - ' . $post['title']); ?>">
            <span class="timeline-date"><?php echo esc_html($post['date']); ?></span>
          </button>
        <?php endforeach; ?>
        <div class="timeline-line"></div>
      </div>
    </div>

    <!-- Carousel -->
    <div class="blog-carousel-container">
      <button class="carousel-nav carousel-prev" aria-label="Article précédent">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
          <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
      </button>

      <div class="blog-carousel" data-total="<?php echo esc_attr($total_posts); ?>">
        <div class="blog-carousel-track">
          <?php foreach ($all_posts as $index => $post) : ?>
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

  <?php else : ?>
    <p class="no-posts">Aucun article pour le moment.</p>
  <?php endif; ?>
</section>

<?php
get_footer();

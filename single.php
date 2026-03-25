<?php
get_header();

while (have_posts()) : the_post();
?>

<!-- Single Post Hero Premium -->
<article id="post-<?php the_ID(); ?>" <?php post_class('single-post'); ?>>
  <header class="single-post-header">
    <div class="single-post-header-content">
      <h1><?php the_title(); ?></h1>

      <div class="single-post-meta">
        <time datetime="<?php echo esc_attr(get_the_date('c')); ?>">
          <?php echo esc_html(get_the_date('j F Y')); ?>
        </time>
        <?php
        $categories = get_the_category();
        if (!empty($categories)) :
          $back_url = '';
          if (has_category('flash-actu')) {
            $back_url = get_permalink(get_option('page_for_posts'));
          } elseif (has_category('conseils')) {
            $back_url = home_url('/conseils-eclaires/');
          }
          if ($back_url) {
            echo '<a href="' . esc_url($back_url) . '" class="single-post-category">' . esc_html($categories[0]->name) . '</a>';
          } else {
            echo '<span class="single-post-category">' . esc_html($categories[0]->name) . '</span>';
          }
        endif;
        ?>
      </div>
    </div>
  </header>

  <?php if (has_post_thumbnail()) : ?>
    <div class="single-post-featured" data-parallax="0.3">
      <?php the_post_thumbnail('full'); ?>
    </div>
  <?php endif; ?>

  <div class="single-post-content">
    <?php the_content(); ?>
  </div>

  <?php if (has_tag()) : ?>
    <footer class="single-post-footer">
      <div class="single-post-tags">
        <?php the_tags('<span class="tags-label">Tags : </span>', ', ', ''); ?>
      </div>
    </footer>
  <?php endif; ?>
</article>

<!-- Post Navigation Premium -->
<?php
$prev_post = get_previous_post();
$next_post = get_next_post();

if ($prev_post || $next_post) :
?>
  <nav class="post-navigation">
    <?php if ($prev_post) : ?>
      <div class="post-nav-item post-nav-prev">
        <span class="post-nav-label">← Article précédent</span>
        <a href="<?php echo esc_url(get_permalink($prev_post)); ?>">
          <?php echo esc_html(get_the_title($prev_post)); ?>
        </a>
      </div>
    <?php endif; ?>

    <?php if ($next_post) : ?>
      <div class="post-nav-item post-nav-next">
        <span class="post-nav-label">Article suivant →</span>
        <a href="<?php echo esc_url(get_permalink($next_post)); ?>">
          <?php echo esc_html(get_the_title($next_post)); ?>
        </a>
      </div>
    <?php endif; ?>
  </nav>
<?php endif; ?>

<?php if (has_category('flash-actu')) : ?>
  <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>" class="single-post-back">← Retour aux actus</a>
<?php elseif (has_category('conseils')) : ?>
  <a href="<?php echo esc_url(home_url('/conseils-eclaires/')); ?>" class="single-post-back">← Retour aux conseils</a>
<?php endif; ?>

<?php
endwhile;

get_footer();

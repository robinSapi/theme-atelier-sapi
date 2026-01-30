<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 */

get_header();
?>

<div class="site-main">
  <div class="container">
    <?php
    if (have_posts()) :
      while (have_posts()) : the_post();
        get_template_part('template-parts/content', get_post_type());
      endwhile;

      // Pagination
      the_posts_pagination([
        'mid_size' => 2,
        'prev_text' => __('« Précédent', 'theme-sapi-maison'),
        'next_text' => __('Suivant »', 'theme-sapi-maison'),
      ]);
    else :
      get_template_part('template-parts/content', 'none');
    endif;
    ?>
  </div>
</div>

<?php
get_footer();

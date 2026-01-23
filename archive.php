<?php
get_header();

if (have_posts()) :
  echo '<h1>' . esc_html(get_the_archive_title()) . '</h1>';
  while (have_posts()) :
    the_post();
    get_template_part('template-parts/content', get_post_type());
  endwhile;
  the_posts_navigation();
else :
  get_template_part('template-parts/content', 'none');
endif;

get_footer();

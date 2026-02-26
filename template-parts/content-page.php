<?php
?>
<article id="page-<?php the_ID(); ?>" <?php post_class('page-default'); ?>>
  <header class="page-default-header">
    <h1><?php the_title(); ?></h1>
  </header>
  <div class="entry-content">
    <?php the_content(); ?>
  </div>
</article>

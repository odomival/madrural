<?php
/**
 * Displaying archive page (category, tag, archives post, author's post)
 * 
 * @package bootstrap-basic
 */

get_header( 'back' );

/**
 * determine main column size from actived sidebar
 */
$main_column_size = bootstrapBasicGetMainColumnSize();
?>
<div id="primary" class="col-md-<?php echo $main_column_size; ?> content-area" id="main-column">

<main id="main" class="site-main" role="main">
  <?php if (have_posts()) { ?>
  <header class="page-header">
    <div class="header_title">
      <?php single_cat_title( '<h2 class="entry-title">', '</h2>' ); ?>
    </div>
  </header>
  <!-- .page-header -->
  <div class="content_post box_category">
    <?php
    /* Start the Loop */
    while ( have_posts() ) {
      the_post();

      /* 
       * Include the Post-Format-specific template for the content.
       * If you want to override this in a child theme, then include a file
       * called content-___.php (where ___ is the Post Format name) and that will be used instead.
       */
      get_template_part( 'template-parts/content', 'experiencias' );
    } //endwhile; 
    ?>
    <?php bootstrapBasicPagination(); ?>
    <?php } else { ?>
    <?php get_template_part('no-results', 'archive'); ?>
    <?php } //endif; ?>
  </div>
</main>
</div>
<?php get_sidebar('right'); ?>
<?php get_footer(); ?>

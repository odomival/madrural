<?php
/**
 * The template for displaying search results.
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
      <h2 class="entry-title">
        <?php
        /* translators: %s Search value. */
        printf( __( '<span>Resultados de la búsqueda: %s </span>', 'bootstrap-basic' ), '<span>' . get_search_query() . '</span>' );
        ?>
      </h2>
    </div>
  </header>
  <!-- .page-header -->
  <div class="content_post box_category">
    <?php
    // start the loop
    while ( have_posts() ) {
      the_post();

      /* 
       * Include the Post-Format-specific template for the content.
       * If you want to override this in a child theme, then include a file
       * called content-___.php (where ___ is the Post Format name) and that will be used instead.
       */
      get_template_part( 'template-parts/content', 'search' );
    } // end while

    bootstrapBasicPagination();
    ?>
    <?php } else { ?>
    <?php get_template_part('no-results', 'search'); ?>
    <?php } // endif; ?>
  </div>
</main>
</div>
<?php get_sidebar('right'); ?>
<?php get_footer(); ?>

<?php
/*
 * 
 *    Template Name: MADRURAL "BLOG"
 * 
 */
get_header('madrural');

/**
 * determine main column size from actived sidebar
 */
$main_column_size = bootstrapBasicGetMainColumnSize();?> 

<section id="primary" class="content-area col-sm-12 col-md-12 col-lg-12 search-experiences">
  <div id="main" class="site-main" role="main">
    <?php the_content(); ?>
    <div class="header_title box">
      <?php the_title( '<h1 class="entry-title ">', '</h1>' ); ?>
    </div>
	  
    <div class="box_category">
      <?php
      if ( get_query_var( 'paged' ) ) {
        $paged = get_query_var( 'paged' );
      } elseif ( get_query_var( 'page' ) ) { // 'page' is used instead of 'paged' on Static Front Page
        $paged = get_query_var( 'page' );
      } else {
        $paged = 1;
      }

      $custom_query_args = array(
        'post_type' => 'post',
        'posts_per_page' => 4,
        'paged' => $paged,
        'post_status' => 'publish',
        'ignore_sticky_posts' => true,
        'category_name' => 'blog',
        'order' => 'DESC', // 'ASC'
        'orderby' => 'date' // modified | title | name | ID | rand
      );
      $custom_query = new WP_Query( $custom_query_args );

      if ( $custom_query->have_posts() ):
        while ( $custom_query->have_posts() ): $custom_query->the_post();
      ?>
      <?php get_template_part( 'template-parts/content', 'blog' ); ?>
      <?php
      endwhile;
      ?>
      <?php if ($custom_query->max_num_pages > 1) : // custom pagination  ?>
      <?php
      $orig_query = $wp_query; // fix for pagination to work
      $wp_query = $custom_query;
      ?>
      <nav id="post-nav" class="row">

		<?php $args = array(
	'format'    => 'page/%#%/', 
	'prev_text' => '← Anterior', 
	'next_text' => 'Siguiente →'
);
the_posts_pagination($args); ?>
		  
      </nav>

      <?php
      $wp_query = $orig_query; // fix for pagination to work
      ?>
      <?php endif; ?>
      <?php
      wp_reset_postdata(); // reset the query 
      else :
        echo '<p>' . __( 'Lo sentimos, ninguna entrada coincide con tus criterios' ) . '</p>';
      endif;
      ?>
    </div>
  </div>
  <!-- #main --> 
</section>
<!-- #primary -->

<?php
get_footer();

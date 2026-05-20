<?php
/**
 * Template for displaying single post (read full post page).
 * 
 * @package bootstrap-basic
 */

get_header(); ?>

	<section id="primary" class="content-area col-sm-12 col-md-12 col-lg-12">
		<div id="main" class="site-main" role="main">
			<div class="content_post">
			<?php
			while ( have_posts() ) : the_post();

				get_template_part( 'template-parts/content', 'page' );

				//the_post_navigation();


			endwhile; // End of the loop.
			?></div>
		</div><!-- #main -->
	</section><!-- #primary2 -->

<?php
get_footer();


<?php
/*
 * 
 *    Template Name: MADRURAL "JORNADA"
 * 
 */
get_header('naranja');

?> 
<?php get_sidebar('left'); ?> 
				<div class="content-area" id="main-column">
					<div class="" style="background-image: url('<?php echo wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) ); ?>'); background-position: center; background-size:cover">

					<main id="main" class="site-main" role="main">
						<?php 
						while (have_posts()) {
							the_post();

							get_template_part('content', 'page');

							echo "\n\n";
							
							// If comments are open or we have at least one comment, load up the comment template
							if (comments_open() || '0' != get_comments_number()) {
								comments_template();
							}

							echo "\n\n";

						} //endwhile;
						?> 
					</main>
					</div>
				</div>
<?php get_footer(); ?> 
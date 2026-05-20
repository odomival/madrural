<?php
/**
 * Template part for displaying page content in organizaciones.php
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WP_Bootstrap_Starter
 */

?>
        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="post-thumbnail ">
          <?php the_post_thumbnail(); ?>

        </div>
			<div class="title_category">

				
	<h2 class="entry-title">
		<a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark">
		<?php 
		if (strlen($post->post_title) > 35) { echo substr(the_title($before = '', $after = '', FALSE), 0, 35) . '...'; } 
		else { the_title(); } 
		?>
		</a>
	</h2>
				
		<div class="seeMore">
            <p><a href="<?php echo esc_url( get_permalink() ); ?>" rel="bookmark"> Más información</a></p>
          </div>
			</div>
        <!-- .entry-content --> 
      </article>
      <!-- #post-## -->


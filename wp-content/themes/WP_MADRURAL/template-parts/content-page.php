<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WP_Bootstrap_Starter
 */

?>
<section class="page_singlular">
  <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <?php
    $enable_vc = get_post_meta( get_the_ID(), '_wpb_vc_js_status', true );
    if ( !$enable_vc ) {
      ?>
    <!-- .entry-header -->
    <?php } ?>
    <div class="entry-content experience_section">
      <div class="thumbnail_header">
        <?php the_post_thumbnail(); ?>    
		<?php the_title( '<div class="entry-title"><h1>', '</h1></div>' );?>
      </div>
 
      <div class="block_hentry">
        <?php
        the_content();
        wp_link_pages( array(
          'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'wp-bootstrap-starter' ),
          'after' => '</div>',
        ) );
        ?>
        <?php if ( get_edit_post_link() && !$enable_vc ) : ?>
        <footer class="entry-footer">
          <?php
          edit_post_link(
            sprintf(
              /* translators: %s: Name of current post */
              esc_html__( 'Edit %s', 'wp-bootstrap-starter' ),
              the_title( '<span class="screen-reader-text">"', '"</span>', false )
            ),
            '<span class="edit-link">',
            '</span>'
          );
          ?>
			
        </footer>
        <!-- .entry-footer -->
        <?php endif; ?>
      </div>

    </div>
    <!-- .entry-content --> 
    
  </article>

</section>
<!-- #post-## --> 

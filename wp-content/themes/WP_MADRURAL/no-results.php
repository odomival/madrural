<?php
/**
 * The template part for displaying message that posts cannot be found.
 * 
 * @package bootstrap-basic
 */
?>
<section class="no-results not-found box_category">
	  <header class="page-header">
    <div class="header_title">
      <h2 class="entry-title">
		<?php _e('No se ha encontrado nada', 'bootstrap-basic'); ?>
		</h2>
	
	</header><!-- .page-header -->

	<div class="page-content row-with-vspace">
		<?php if (is_home() && current_user_can('publish_posts')) { ?> 
			<p><?php 
				/* translators: %1$s: Link to add new post. */
				printf(__('¿Listo para publicar tu primer post? <a href="%1$s">Empezar aquí</a>.', 'bootstrap-basic'), esc_url(admin_url('post-new.php'))); 
			?></p>
		<?php } elseif (is_search()) { ?> 
			<p><?php _e('Lo sentimos, pero no hay nada que coincida con sus términos de búsqueda. Por favor, inténtelo de nuevo con otras palabras clave.', 'bootstrap-basic'); ?></p>
			<div class="buscador_madrural"><?php echo bootstrapBasicFullPageSearchForm(); ?></div> 
		<?php } else { ?> 
			<p><?php _e('Parece que no podemos encontrar lo que busca. Tal vez la búsqueda pueda ayudar.', 'bootstrap-basic'); ?></p>
			<div class="buscador_madrural"><?php echo bootstrapBasicFullPageSearchForm(); ?> </div>
		<?php } //endif; ?> 
	</div><!-- .page-content -->
</section><!-- .no-results -->
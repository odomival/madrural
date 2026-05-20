<?php
/**
 * The theme header
 * 
 * @package bootstrap-basic
 */
?>
<!DOCTYPE html>
<!--[if lt IE 7]>  <html class="no-js lt-ie9 lt-ie8 lt-ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7]>     <html class="no-js lt-ie9 lt-ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8]>     <html class="no-js lt-ie9" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js" <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>">

<!--wordpress head-->
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
if ( function_exists( 'wp_body_open' ) ) {
  wp_body_open();
} else {
  do_action( 'wp_body_open' );
}
?>
<!--[if lt IE 8]>
			<p class="ancient-browser-alert">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/" target="_blank">upgrade your browser</a>.</p>
		<![endif]-->

<div class="mad_container">
<?php do_action('before'); ?>
<header role="banner">
	<a href="<?php echo get_option('home'); ?>/" >
	<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
	<lottie-player src="/wp-content/themes/WP_MADRURAL/inc/files/logo_ocre.json"   background="transparent"  speed="1" class="logo"  autoplay></lottie-player>
	</a>
	
  <?php
  wp_nav_menu( array(
    'theme_location' => 'page',
    'container' => 'div',
    'container_id' => 'main-nav',
    'container_class' => 'header--buttons',
    'menu_id' => false,
    'menu_class' => 'madbar-nav',
    'depth' => 3,
    'fallback_cb' => 'wp_bootstrap_navwalker::fallback',
    'add_li_class' => 'menu_btn'
  ) );
  ?>
</header>
<div id="content" class="row row-with-vspace site-content">

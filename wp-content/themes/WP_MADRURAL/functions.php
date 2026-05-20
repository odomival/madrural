<?php
/**
 * Bootstrap Basic theme
 * 
 * @package bootstrap-basic
 */


/**
 * Required WordPress variable.
 */
if (!isset($content_width)) {
    $content_width = 1170;
}


/**
 * The Bootstrap Basic main class.
 */
require_once get_template_directory() . '/inc/BootstrapBasic.php';


/**
 * Register commonly use scripts and styles.
 */
$BootstrapBasic = new \BootstrapBasic();
unset($BootstrapBasic);


if (!function_exists('bootstrapBasicSetup')) {
    /**
     * Setup theme and register support wp features.
     */
    function bootstrapBasicSetup() 
    {
        /**
         * Make theme available for translation
         * Translations can be filed in the /languages/ directory
         * 
         * copy from underscores theme
         */
        load_theme_textdomain('bootstrap-basic', get_template_directory() . '/languages');

        // add theme support title-tag
        add_theme_support('title-tag');

        // add theme support post and comment automatic feed links
        add_theme_support('automatic-feed-links');

        // enable support for post thumbnail or feature image on posts and pages
        add_theme_support('post-thumbnails');

        // allow the use of html5 markup
        // @link https://codex.wordpress.org/Theme_Markup
        add_theme_support('html5', array('caption', 'comment-form', 'comment-list', 'gallery', 'search-form'));

        // add support menu
        register_nav_menus(array(
            'primary' => __('Primary Menu', 'bootstrap-basic'),
        ));

        // add post formats support
        add_theme_support('post-formats', array('aside', 'image', 'video', 'quote', 'link'));

        // add support custom background
        add_theme_support(
            'custom-background', 
            apply_filters(
                'bootstrap_basic_custom_background_args', 
                array(
                    'default-color' => 'ffffff', 
                    'default-image' => ''
                )
            )
        );

        // @since 1.1 or WordPress 5.0+
        // make gutenberg support. --------------------------------------------------------------------------------------
        // @link https://wordpress.org/gutenberg/handbook/extensibility/theme-support/ reference.
        // add wide alignment ( https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#wide-alignment )
        add_theme_support('align-wide');
        // support default block styles for front-end ( https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#default-block-styles )
        add_theme_support('wp-block-styles');
        // support editor styles ( https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#editor-styles )
        // this one make appearance in editor more close to Bootstrap 3.
        add_theme_support('editor-styles');
        // support responsive embeds for front-end ( https://wordpress.org/gutenberg/handbook/designers-developers/developers/themes/theme-support/#responsive-embedded-content )
        add_theme_support('responsive-embeds');
        // end make gutenberg support. ---------------------------------------------------------------------------------
    }// bootstrapBasicSetup
}
add_action('after_setup_theme', 'bootstrapBasicSetup');


if (!function_exists('bootstrapBasicWidgetsInit')) {
    /**
     * Register widget areas
     */
    function bootstrapBasicWidgetsInit() 
    {
        register_sidebar(array(
            'name' => __('Sidebar right', 'bootstrap-basic'),
            'id' => 'sidebar-right',
            'before_widget' => '<aside id="%1$s" class="widget %2$s">',
            'after_widget' => '</aside>',
            'before_title' => '<h1 class="widget-title">',
            'after_title' => '</h1>',
        ));

        register_sidebar(array(
            'name' => __('Sidebar left', 'bootstrap-basic'),
            'id' => 'sidebar-left',
            'before_widget' => '<aside id="%1$s" class="widget %2$s">',
            'after_widget' => '</aside>',
            'before_title' => '<h1 class="widget-title">',
            'after_title' => '</h1>',
        ));

        register_sidebar(array(
            'name' => __('Header right', 'bootstrap-basic'),
            'id' => 'header-right',
            'description' => __('Header widget area on the right side next to site title.', 'bootstrap-basic'),
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h1 class="widget-title">',
            'after_title' => '</h1>',
        ));

        register_sidebar(array(
            'name' => __('Navigation bar right', 'bootstrap-basic'),
            'id' => 'navbar-right',
            'before_widget' => '',
            'after_widget' => '',
            'before_title' => '',
            'after_title' => '',
        ));

        register_sidebar(array(
            'name' => __('Footer left', 'bootstrap-basic'),
            'id' => 'footer-left',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h1 class="widget-title">',
            'after_title' => '</h1>',
        ));

        register_sidebar(array(
            'name' => __('Footer right', 'bootstrap-basic'),
            'id' => 'footer-right',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h1 class="widget-title">',
            'after_title' => '</h1>',
        ));
        register_sidebar(array(
            'name' => __('Footer center', 'bootstrap-basic'),
            'id' => 'footer-center',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget' => '</div>',
            'before_title' => '<h1 class="widget-title">',
            'after_title' => '</h1>',
        ));
    }// bootstrapBasicWidgetsInit
}
add_action('widgets_init', 'bootstrapBasicWidgetsInit');


if (!function_exists('bootstrapBasicEnqueueScripts')) {
    /**
     * Enqueue scripts & styles
     * 
     * @global \WP_Scripts $wp_scripts
     */
    function bootstrapBasicEnqueueScripts() 
    {
        global $wp_scripts;
        $Theme = wp_get_theme();
        $themeVersion = $Theme->get('Version');
        unset($Theme);

        wp_enqueue_style('bootstrap-style');
        wp_enqueue_style('bootstrap-theme-style', get_template_directory_uri() . '/css/bootstrap-theme.min.css', array(), '3.4.1');
        wp_enqueue_style('fontawesome-style', get_template_directory_uri() . '/css/font-awesome.min.css', array(), '4.7.0');
        wp_enqueue_style('main-style', get_template_directory_uri() . '/css/main.css', array(), $themeVersion);

        // check if there are any calendar widget block.
        if (bootstrapBasicHasWidgetBlock('calendar') === true) {
            // if theme using widget blocks.
            // enqueue css to fix calendar widget block to render as non widget block.
            // if you would like it to be render as new widget block, please dequeue this handle.
            wp_enqueue_style('bootstrapbasic-widgetblocks-calendar', get_template_directory_uri() . '/css/widget-blocks/calendar.css', array(), $themeVersion);
        }

        // js that is useful for development.
        wp_enqueue_script('modernizr-script', get_template_directory_uri() . '/js/vendor/modernizr.min.js', array(), '3.6.0-20190314', true);
        // js that is useful for old browsers.
        wp_register_script('respond-script', get_template_directory_uri() . '/js/vendor/respond.min.js', array(), '1.4.2', true);
        $wp_scripts->add_data('respond-script', 'conditional', 'lt IE 9');
        wp_enqueue_script('respond-script');
        wp_register_script('html5-shiv-script', get_template_directory_uri() . '/js/vendor/html5shiv.min.js', array(), '3.7.3', true);
        $wp_scripts->add_data('html5-shiv-script', 'conditional', 'lte IE 9');
        wp_enqueue_script('html5-shiv-script');
        
        if (is_singular() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }

        wp_enqueue_script('bootstrap-script');
        wp_enqueue_script('main-script', get_template_directory_uri() . '/js/main.js', array('jquery'), $themeVersion, true);
        wp_enqueue_script('miscript', get_stylesheet_directory_uri(). '/js/lottie.js', array('jquery'), $themeVersion, true);

		
        wp_enqueue_style('bootstrap-basic-style', get_stylesheet_uri(), array(), $themeVersion);

        // move jquery to bottom ( https://wordpress.stackexchange.com/a/225936/41315 )
        $wp_scripts->add_data('jquery', 'group', 1);
        $wp_scripts->add_data('jquery-core', 'group', 1);
        $wp_scripts->add_data('jquery-migrate', 'group', 1);
    }// bootstrapBasicEnqueueScripts
}
add_action('wp_enqueue_scripts', 'bootstrapBasicEnqueueScripts');


/**
 * admin page displaying help.
 */
if (is_admin()) {
    require get_template_directory() . '/inc/BootstrapBasicAdminHelp.php';
    $bbsc_adminhelp = new BootstrapBasicAdminHelp();
    add_action('admin_menu', array($bbsc_adminhelp, 'themeHelpMenu'));
    unset($bbsc_adminhelp);
}


/**
 * Make WordPress 5 (Gutenberg) editor support Bootstrap CSS.
 */
require_once get_template_directory() . '/inc/BootstrapBasicWp5.php';
$BbWp5 = new BootstrapBasicWp5();
unset($BbWp5);


/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';


/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';


/**
 * Custom dropdown menu and navbar in walker class
 */
require get_template_directory() . '/inc/BootstrapBasicMyWalkerNavMenu.php';


/**
 * Template functions
 */
require get_template_directory() . '/inc/template-functions.php';


/**
 * --------------------------------------------------------------
 * Theme widget & widget hooks
 * --------------------------------------------------------------
 */
require get_template_directory() . '/inc/widgets/BootstrapBasicAutoRegisterWidgets.php';
$BootstrapBasicAutoRegisterWidgets = new BootstrapBasicAutoRegisterWidgets();
$BootstrapBasicAutoRegisterWidgets->registerAll();
unset($BootstrapBasicAutoRegisterWidgets);
require get_template_directory() . '/inc/template-widgets-hook.php';


/*****************************
MADRURAL ADDS
******************************/
function add_additional_class_on_li($classes, $item, $args) {
    if(isset($args->add_li_class)) {
        $classes[] = $args->add_li_class;
    }
    return $classes;
}
add_filter('nav_menu_css_class', 'add_additional_class_on_li', 1, 3);

/**STYLE fonts**/
function addFontStyle() {
	
	  wp_enqueue_style( 'slider', 'https://use.typekit.net/wue6hil.css', array(), '1.1', 'all');
}
add_action( 'wp_enqueue_scripts', 'addFontStyle' );

//***shortcode**//

function short_geometria_casa() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93.27 107.86" class="house-header" >
            <polygon points="0 37.59 0 107.86 93.27 107.86 93.27 37.59 46.63 0 0 37.59" fill="#ef6c4d" ></polygon>		</svg>
';
}
add_shortcode('geometria_casa','short_geometria_casa');

function short_brujula_geometria() {
	return '<div class="svg-container" data-v-d09c60c2=""><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 333.32 333.32" data-v-d09c60c2=""><path d="M47.14,192.92H93.77a46.63,46.63,0,0,1,46.63,46.63v0a46.63,46.63,0,0,1-46.63,46.63H47.14a0,0,0,0,1,0,0V192.92a0,0,0,0,1,0,0Z" transform="translate(-141.92 136.47) rotate(-45)" fill="#6b4848" data-v-d09c60c2=""></path> <circle cx="166.72" cy="166.38" r="47.51" fill="#ef6c4d" data-v-d09c60c2=""></circle> <polygon points="223.87 43.51 174.18 93.2 240.12 159.15 289.82 109.46 283.42 49.91 223.87 43.51" fill="#6b4848" data-v-d09c60c2=""></polygon></svg></div>';
}
add_shortcode('brujula_geometria','short_brujula_geometria');

function short_geometria_circulo() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 95.01 95.01" class="circle-video" data-v-2c506852=""><circle cx="47.51" cy="47.51" r="47.51" fill="#ef6c4d" data-v-2c506852=""></circle></svg>';
}
add_shortcode('geometria_circulo','short_geometria_circulo');

function short_geometria_cilindro() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 93.27 93.27" class="path-footer" data-v-2c506852=""><path d="M0,0H46.63A46.63,46.63,0,0,1,93.27,46.63v0A46.63,46.63,0,0,1,46.63,93.27H0a0,0,0,0,1,0,0V0A0,0,0,0,1,0,0Z" transform="translate(0 93.27) rotate(-90)" fill="#ef6c4d" data-v-2c506852=""></path></svg>';
}
add_shortcode('geometria_cilindro','short_geometria_cilindro');

function short_mad_sello() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 182.97 182.97" fill="1a1a1a" aria-labelledby="logocircularTitle" role="img" data-v-14f6ca09="">
  <title id="logocircularTitle" data-v-14f6ca09="">Sello circular Madrid Destino Rural</title>  <path class="sello_custom_color" d="M91.49,183A91.49,91.49,0,1,1,183,91.49,91.59,91.59,0,0,1,91.49,183Zm0-181.17a89.69,89.69,0,1,0,89.68,89.69A89.79,89.79,0,0,0,91.49,1.8Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M39.73,68.34l-7.68-2.76,0,.08,5,6-.11.31L29.2,73.49l0,.09,7.69,2.76-.67,1.86L24.88,74.13l.72-2,8.08-1.53v0l-5.25-6.33.69-1.9,11.3,4.07Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M34.53,53.33l1.07-1.54L48.67,54l-1.29,1.86-3.49-.64L40.63,60l1.85,3-1.22,1.77Zm5.16,5.08,2.42-3.5-5-.95-.06.09Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M50,52.74l-8-9,4.31-3.81a5.72,5.72,0,0,1,4.36-1.73A6.71,6.71,0,0,1,56.54,45a5.67,5.67,0,0,1-2.23,3.94Zm3-5.14a3.51,3.51,0,0,0,1.44-2.53,4.54,4.54,0,0,0-1.22-3.24,4.44,4.44,0,0,0-3-1.58,3.57,3.57,0,0,0-2.79,1.12l-2.7,2.39L50.32,50Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M60.62,43.87,55,33.24l5.86-3.09a4.48,4.48,0,0,1,3.66-.5,3.49,3.49,0,0,1,2,1.69,3.68,3.68,0,0,1-.74,4.28l4.83,3-2.1,1.11-4.43-2.89-3.78,2,2.15,4.09Zm-2.92-10,1.73,3.28,4.36-2.29a2,2,0,0,0,.9-2.6,1.64,1.64,0,0,0-1-.82,2.35,2.35,0,0,0-2,.3Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M73.2,38,69.53,26.56l2-.63,3.66,11.43Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M78.56,36.2,77,24.29l5.71-.76a5.76,5.76,0,0,1,4.59,1,6.26,6.26,0,0,1,2.2,4.15,6.25,6.25,0,0,1-1.15,4.73,5.69,5.69,0,0,1-4.06,2Zm5.38-2.57a3.57,3.57,0,0,0,2.61-1.3,4.64,4.64,0,0,0,.8-3.37,4.49,4.49,0,0,0-1.57-3,3.6,3.6,0,0,0-2.94-.62l-3.57.47,1.09,8.26Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M100.62,35.45l3-11.64,5.58,1.46a5.77,5.77,0,0,1,3.87,2.67,6.24,6.24,0,0,1,.47,4.67,6.26,6.26,0,0,1-2.86,4,5.66,5.66,0,0,1-4.52.34Zm6-.35a3.58,3.58,0,0,0,2.91-.21,4.64,4.64,0,0,0,2-2.83,4.43,4.43,0,0,0-.33-3.33,3.58,3.58,0,0,0-2.49-1.7l-3.49-.91-2.1,8.07Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M122.34,37.81l-4.08-2.17-1.65,3.11,7.2,3.81-.87,1.63-9-4.78,5.63-10.62,8.62,4.56L127.32,35l-6.8-3.6L119.12,34l4.09,2.16Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M137.58,44.51a6.53,6.53,0,0,0-2.36-3.92c-1.08-1-2.34-1.44-3.17-.48s.44,2.2,1.73,3.49c1.52,1.53,3.93,3.93,1.72,6.45-1.91,2.19-4.48,1.43-6.64-.46a9.46,9.46,0,0,1-3.29-6.22l1.89-.26a7.74,7.74,0,0,0,2.6,5.16c1.35,1.18,2.81,1.65,3.71.63s.16-2.28-1.31-3.79c-1.87-1.88-4-4-2.08-6.22s4.41-1,6.07.4a9.39,9.39,0,0,1,3,5Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M142.61,45.5l6.94,9.16-1.46,1.11L145.24,52l-8.1,6.15-1.25-1.65L144,50.38l-2.86-3.77Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M140.85,63.94l10.6-5.67,1,1.81-10.6,5.68Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M158.37,75.56l-11.43,3.69-.56-1.73,5.79-9.16v0l-7.93,2.56-.63-2L155,65.25,155.6,67l-5.8,9.16v0l7.92-2.56Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M160.23,87.1a6.38,6.38,0,1,1-6.75-6.34A6.55,6.55,0,0,1,160.23,87.1Zm-10.88.68a4.54,4.54,0,1,0,9.05-.57,4.53,4.53,0,1,0-9.05.57Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M146.65,104.84,158,108.71,155.9,115a4.48,4.48,0,0,1-2.33,2.89A3.51,3.51,0,0,1,151,118a3.7,3.7,0,0,1-2.4-3.62L143,115.52l.76-2.25,5.21-1,1.37-4L146,106.79Zm9,5.23-3.52-1.2-1.59,4.67a2,2,0,0,0,1.14,2.51,1.75,1.75,0,0,0,1.29-.09,2.34,2.34,0,0,0,1.21-1.57Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M146.35,132.92l-6.53-4.18c-2.49-1.6-2.68-4.43-1-7s4.28-3.58,6.78-2l6.52,4.18L151,125.68l-6.52-4.18c-1.52-1-3.14-.3-4.12,1.23s-.93,3.3.59,4.27l6.53,4.18Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M134.46,128.54l8.37,8.64-4.77,4.61a4.47,4.47,0,0,1-3.38,1.51,3.5,3.5,0,0,1-2.37-1.07,3.7,3.7,0,0,1-.49-4.32l-5.47-1.52,1.71-1.65,5.07,1.52,3.07-3L133,130Zm5.61,8.75-2.59-2.67-3.55,3.43a2,2,0,0,0-.14,2.74,1.69,1.69,0,0,0,1.19.52,2.38,2.38,0,0,0,1.8-.84Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M126.51,150.39l-1.62.94-10.83-7.61,2-1.13,2.87,2.07,5-2.86-.38-3.52,1.86-1.07ZM124,143.61l-3.68,2.12,4.11,3,.09-.06Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M113.89,156.28l-3.06-9.7-7,2.22-.55-1.75,9-2.84,3.61,11.45Z" data-v-14f6ca09=""></path>  <path class="sello_custom_color" d="M91.49,155.23A63.82,63.82,0,0,1,27.74,91.49h1.87a61.94,61.94,0,0,0,61.88,61.87Z" data-v-14f6ca09=""></path>
</svg>';
}
add_shortcode('mad_sello','short_mad_sello');

//ICONOS EXPERIENCIAS SVG//
//**experiencial
function short_experiencial_icon1() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve"><style type="text/css">.color_cicle{fill:#55C5E5;}</style><path class="color_cicle" d="M47.5,2.2L47.5,2.2c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0	C2.7,22.2,22.8,2.2,47.5,2.2z"/><path d="M6.4,5.4h7.2v2.1H7.5v9.7H5.4V6.4L6.4,5.4z M90.6,16.1H58.3v2.1h32.2V16.1z M91.6,44v19.2c0.1,4.9-3.2,9.2-8,10.4l2.6,17.2 L84.1,91l-2.5-17.2h-9.9l2,12.8l-0.3,0.9c-5,5.3-13.3,5.6-18.7,0.7c-5.1,4.6-12.8,4.6-17.9,0c-5.4,4.9-13.7,4.5-18.7-0.7L18,86.6 l2-12.8h-9.9L7.5,91l-2.1-0.3L8,73.5c-4.8-1.1-8.1-5.5-8-10.4V44h2.2v19.2c0,4.7,3.5,8.4,7.8,8.4h10.3l0.5-3.2H10c0,0,0,0,0,0 c-2.5,0-4.6-2-4.6-4.6V51.1c0-4.5,3.6-8.1,8.1-8.1c2.6,0,4.7,2.1,4.7,4.7v11.4h4l1-6.3l1.1-0.9h36.5v-4.3H63v4.3h4.4l1,0.9l1,6.3h4 V47.7c0-2.6,2.1-4.7,4.7-4.7c4.5,0,8.1,3.6,8.1,8.1v12.7c0,2.5-2.1,4.6-4.6,4.6H70.8l0.5,3.2h10.3c4.3,0,7.8-3.8,7.8-8.4V44H91.6z  M69.7,61.2l0.8,5h11.2c1.4,0,2.4-1.1,2.4-2.5V51.1c0-3.3-2.7-6-6-6c-1.4,0-2.6,1.2-2.6,2.6v12.4l-1.1,1.1L69.7,61.2z M21.2,66.2 l0.8-5h-4.8l-1.1-1.1V47.7c0-1.4-1.2-2.6-2.6-2.6c-3.3,0-6,2.7-6,6v12.7c0,0,0,0,0,0.1c0,1.4,1.1,2.4,2.5,2.4L21.2,66.2z M71.5,86.3 L66.4,54H25.2l-5.1,32.4c4.4,4.2,11.4,4.2,15.7-0.1V75.3h2.1v10.9c4.4,4.3,11.4,4.3,15.8,0V70h2.1v16.3c2.1,2.1,4.9,3.2,7.9,3.2 C66.6,89.5,69.4,88.4,71.5,86.3z M38.3,19C38.3,19,38.3,19,38.3,19c0-3.6,2.9-6.5,6.4-6.5s6.4,2.9,6.4,6.4c0,3.6-2.9,6.4-6.4,6.4 C41.2,25.4,38.4,22.5,38.3,19z M40.5,19c0,2.4,1.9,4.3,4.3,4.3c2.4,0,4.3-1.9,4.3-4.3c0-2.4-1.9-4.3-4.3-4.3c0,0,0,0,0,0 C42.4,14.7,40.5,16.6,40.5,19z M17.2,28.6c3.6,0,6.4,2.9,6.4,6.5c0,3.6-2.9,6.4-6.5,6.4c-3.6,0-6.4-2.9-6.4-6.4c0,0,0,0,0,0 C10.7,31.5,13.6,28.6,17.2,28.6z M17.2,30.8c-2.4,0-4.3,1.9-4.3,4.3c0,0,0,0,0,0c0,2.4,1.9,4.3,4.3,4.3c2.4,0,4.3-1.9,4.3-4.3 C21.5,32.7,19.6,30.8,17.2,30.8z M74.4,28.6c3.6,0,6.4,2.9,6.5,6.4c0,3.6-2.9,6.4-6.4,6.5c0,0,0,0,0,0c-3.6,0-6.5-2.9-6.5-6.4 C68,31.5,70.9,28.6,74.4,28.6z M74.5,30.8c-2.4,0-4.3,1.9-4.3,4.3c0,0,0,0,0,0c0,2.4,1.9,4.3,4.3,4.3c2.4,0,4.3-1.9,4.3-4.3 C78.8,32.7,76.8,30.8,74.5,30.8z M37.9,36c0-3.9,3.1-7,7-7h2.8c2.9,0,5.5,1.5,6.9,4l2.7,4.7c0.7,1.3,0.3,3-1,3.7l-7.5,4.3 c-0.8,0.4-1.8,0.2-2.2-0.6c-0.2-0.3-0.2-0.6-0.2-0.9c0-0.4,0.2-0.9,0.5-1.2l6.6-5.3L52.3,36l-6.6,5.3c-1.6,1.3-1.9,3.7-0.6,5.3 c1.2,1.4,3.2,1.8,4.8,0.9l7.5-4.3c2.3-1.3,3.1-4.3,1.8-6.6l-2.7-4.7c-1.8-3.1-5.1-5.1-8.8-5.1h-2.8c-5,0-9.1,4.1-9.1,9.1 c0,0,0,0,0,0v13.8h2.1V36z M64.8,7.2h-2.1v7.2h2.1V7.2z M70.2,3.8c0-0.9,0.7-1.6,1.6-1.6c0.9,0,1.6,0.7,1.6,1.6v10.7h2.1l0-10.7 c0-2.1-1.7-3.8-3.8-3.8S68,1.7,68,3.8v10.7h2.2V3.8z M80.9,7.3C80.9,7.3,80.9,7.3,80.9,7.3c0-0.9,0.7-1.6,1.6-1.6 c0.9,0,1.6,0.7,1.6,1.6v7.1h2.1V7.3c0-2.1-1.7-3.8-3.8-3.8s-3.8,1.7-3.8,3.8v7.1h2.1V7.3z M27.9,59.4h9v-2.1h-9V59.4z M54.8,59.4h9 v-2.1h-9V59.4z M1.1,23.6L0,22.5V1.1L1.1,0H19l1,1.1v21.5l-1,1.1H1.1z M2.1,21.5h15.8V2.1H2.1V21.5z"/></svg>';
}
add_shortcode('experiencial_icon1','short_experiencial_icon1');

function short_experiencial_icon2() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"  viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#55C5E5;}</style>
<path class="color_circle" d="M47,3.3L47,3.3c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0 C2.2,23.3,22.3,3.3,47,3.3z"/>
<path d="M72,48.8h19.7v2.1H72V48.8z M76.3,39.9h2.1v7.2h-2.1V39.9z M89.2,47.1V40c0-2.1-1.7-3.8-3.8-3.8s-3.8,1.7-3.8,3.8v7.1h2.1 V40c0-0.9,0.7-1.6,1.6-1.6c0.9,0,1.6,0.7,1.6,1.6c0,0,0,0,0,0v7.1L89.2,47.1z M80.2,9.7h6.1V7.6h-7.2l-1.1,1.1v10.7h2.1V9.7z  M91.7,2.2H73.8l-1.1,1.1v21.5l1.1,1.1h17.9l1.1-1.1V3.3L91.7,2.2z M90.7,23.7H74.9V4.3h15.8V23.7z M10.4,57.1V24.8l-1.1-1.1H2.2 v2.1h6.1V56H2.2v2.1h7.2L10.4,57.1z M10.4,10.4c0-4.5-3.7-8.2-8.2-8.2v2.1c3.4,0.1,6,3,5.8,6.3c-0.1,3.2-2.7,5.7-5.8,5.8v2.1 C6.8,18.7,10.4,15,10.4,10.4z M11.2,82.8h10.7v2.1H11.2V82.8z M38.7,15.8h2.1v3.6h-2.1V15.8z M38.7,23h2.1v3.6h-2.1V23z M41.6,20.1 h3.6v2.1h-3.6V20.1z M34.4,20.1H38v2.1h-3.6V20.1z M28,3.3h2.1v3.6H28V3.3z M28,10.4h2.1V14H28V10.4z M30.9,7.6h3.6v2.1h-3.6V7.6z  M23.7,7.6h3.6v2.1h-3.6V7.6z M22.6,24.8h2.1v3.6h-2.1V24.8z M22.6,31.9h2.1v3.6h-2.1V31.9z M25.5,29.1h3.6v2.1h-3.6V29.1z  M18.3,29.1h3.6v2.1h-3.6V29.1z M35.5,61.6c2.3-0.2,4.5-1.4,6-3.1c1.8-2.1,2.5-4.9,2-7.6l-1.5-8.4l-1.1-0.9H28.1l-1,0.9l-1.5,8.4 c-0.6,2.7,0.2,5.5,2,7.6c1.5,1.7,3.6,2.9,5.9,3.1v12.6l-3.8,1.5l0.8,2l4-1.7l4.1,1.6l0.8-2l-3.8-1.5V61.6z M29,43.7h10.9l1.2,6.3 l-0.5-0.2c-2.3-0.8-4.7-0.5-6.8,0.8c-1.4,1-3.3,1.2-4.9,0.6l-1.3-0.5L29,43.7z M29,57c-1-1.1-1.5-2.5-1.6-4l0.8,0.3 c0.8,0.3,1.6,0.4,2.5,0.4c1.5,0,3-0.4,4.3-1.2c1.5-0.9,3.3-1.2,4.9-0.6l1.5,0.5c0.1,1.7-0.5,3.4-1.6,4.7c-2.6,3-7.2,3.3-10.1,0.7 C29.4,57.5,29.2,57.3,29,57L29,57z M60.6,25.7V14l-1.1-1h-5.4l-1.1,1v11.7c0,2.7-0.9,5.3-2.5,7.4c-1.9,2.5-2.9,5.6-2.9,8.7v34.9 l1.1,1.1h16.1l1.1-1.1V41.8c0-3.1-1-6.2-2.9-8.7C61.4,30.9,60.6,28.3,60.6,25.7z M58.4,15.1v8.6h-3.2v-8.6H58.4z M63.8,75.6h-14 V41.8c0-2.7,0.9-5.3,2.5-7.4c1.8-2.5,2.8-5.4,2.9-8.5h3.2c0,3.1,1.1,6.1,2.9,8.5c1.6,2.1,2.5,4.7,2.5,7.4L63.8,75.6z M68.4,68.4 h23.3v2.1H68.4V68.4z M2.2,68.4h28.6v2.1H2.2V68.4z M37.4,68.4h8.2v2.1h-8.2V68.4z M40,88.1h7v2.1h-7V88.1z M70.2,82.8h8.9v2.1h-8.9 V82.8z"/>
</svg>';
}
add_shortcode('experiencial_icon2','short_experiencial_icon2');

function short_experiencial_icon3() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"  viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#55C5E5;}</style>
<path class="color_circle" d="M47.5,2.3L47.5,2.3c24.7,0,44.8,20,44.8,44.7l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0 C2.8,22.4,22.8,2.3,47.5,2.3z"/>
<path d="M11,70.3V50.7l-1.1-1.1H2.8v2.1h6.1v17.5H2.8v2.1h7.2L11,70.3z M11,84.7c0-4.5-3.7-8.2-8.2-8.2v2.1c3.4,0.1,6,3,5.8,6.3 c-0.1,3.2-2.7,5.7-5.8,5.8v2.1C7.3,92.9,11,89.2,11,84.7z M39.8,2.6l2.1-0.5l1.8,7.2l-2.1,0.5L39.8,2.6z M52.3,9.2l1.8-7.2l2.1,0.5 l-1.8,7.2L52.3,9.2z M65,8.9l3.6-5.4l1.8,1.2l-3.6,5.4L65,8.9z M25.6,4.7l1.8-1.2L31,8.9l-1.8,1.2L25.6,4.7z M12.4,32.8h2.1v3.6 h-2.1V32.8z M12.4,39.9h2.1v3.6h-2.1V39.9z M8.1,37.1h3.6v2.1H8.1V37.1z M15.3,37.1h3.6v2.1h-3.6V37.1z M78.7,43.5h2.1v3.6h-2.1 V43.5z M78.7,50.7h2.1v3.6h-2.1V50.7z M74.3,47.8h3.6v2.1h-3.6V47.8z M81.5,47.8h3.6v2.1h-3.6V47.8z M84,27.4h2.1V31H84V27.4z  M84,34.6h2.1v3.6H84V34.6z M79.7,31.7h3.6v2.1h-3.6V31.7z M86.9,31.7h3.6v2.1h-3.6V31.7z M27.9,78.6h6v6.1l1.7,0.8l3.8-3l3.8,3 l1.8-0.8v-6.1h25v-2.1c-1.4,0.2-2.6-0.8-2.8-2.2c-0.2-1.4,0.8-2.6,2.2-2.8c0.2,0,0.4,0,0.6,0v-1.1l0,0V16.7l-1-1.1H29.6 c-3.5,0-6.4,2.9-6.4,6.4v52C23.2,76.6,25.3,78.6,27.9,78.6z M42.9,82.5l-2.7-2.2h-1.4L36,82.5v-11h6.8L42.9,82.5z M66.1,76.5H45v-5 h21C65,73,65,74.9,66.1,76.5L66.1,76.5z M68,17.7v51.6H30.7V17.7L68,17.7z M28.6,17.9v51.4h-0.7c-0.9,0-1.8,0.3-2.6,0.8V22 C25.3,20.1,26.6,18.4,28.6,17.9L28.6,17.9z M27.9,71.4h6v5h-6c-1.4,0.2-2.6-0.8-2.8-2.2c-0.2-1.4,0.8-2.6,2.2-2.8 C27.5,71.4,27.7,71.4,27.9,71.4z M44.6,42.8v18.6l1.1,1.1h7.2l1.1-1.1V42.8H60l1.1-1.1v-7.2L60,33.5h-6.1v-7.9l-1.1-1.1h-7.2 l-1.1,1.1v7.9h-6.1l-1.1,1.1v7.2l1.1,1.1H44.6z M39.6,35.6h6.1l1.1-1.1v-7.9h5v7.9l1.1,1.1H59v5h-6.1l-1.1,1.1v18.6h-5V41.7 l-1.1-1.1h-6.1L39.6,35.6z M92.2,71.1H81.5l-1.1,1.1v19.7h2.1V73.2h9.7V71.1z M85.1,76.5h7.2v2.1h-7.2V76.5z M85.1,81.8h7.2V84h-7.2 V81.8z M85.1,87.2h7.2v2.1h-7.2V87.2z"/>
</svg>';
}
add_shortcode('experiencial_icon3','short_experiencial_icon3');

function short_experiencial_icon4() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"  viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#55C5E5;}</style>
<circle class="color_circle" cx="47.5" cy="47" r="44.8"/>
<path d="M47.5,51.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1c-1.4,0-2.5-1.1-2.5-2.5h-2.1c0,1.4-1.1,2.5-2.5,2.5v2.1 C45.4,53.4,46.7,52.8,47.5,51.7z M17.8,27.3h2.1v3.6h-2.1V27.3z M17.8,34.4h2.1V38h-2.1V34.4z M20.6,31.6h3.6v2.1h-3.6V31.6z  M13.5,31.6h3.6v2.1h-3.6V31.6z M23.2,4h2.1v3.6h-2.1V4z M23.2,11.2h2.1v3.6h-2.1V11.2z M26,8.3h3.6v2.1H26V8.3z M18.9,8.3h3.6v2.1 h-3.6V8.3z M7.1,14.7h2.1v3.6H7.1V14.7z M7.1,21.9h2.1v3.6H7.1V21.9z M9.9,19h3.6v2.1H9.9V19z M2.8,19h3.6v2.1H2.8V19z M30.3,91.6 l1.8-17.9l2.1,0.2l-1.8,17.9L30.3,91.6z M62.9,73.7l-2.1,0.2l1.7,16.7H45.9v2.1h23v-2.1h-4.4L62.9,73.7z M47.1,62.3h0.8l3.6-1.5 l-0.8-2l-3.2,1.3l-3.2-1.3l-0.8,2L47.1,62.3z M71.2,57.4L70,57.9v-10c0-3.2-1.7-6.1-4.5-7.7L58.3,36l-1.1,1.8l1.2,0.7l-9.2,3.8 l-6.7-3.6l-1,1.9L65,53.1l-4.3,1.8l0.8,2l6.4-2.7v4.6l-20.4,8.5l-20.4-8.5v-4.6l6.4,2.7l0.8-2l-7.2-3v-4c0-2.4,1.3-4.7,3.4-5.9 l7.2-4.1L36.7,36l-7.2,4.1c-2.8,1.6-4.5,4.6-4.4,7.7v10l-1.1-0.5l-0.8,2l2,0.8V72h2.1v-5.5l5,2.1V72h2.1v-2.6l2.1,0.9l0.8-2 l-2.9-1.2v-3l5,2.1v13h2.1V67.1l5,2.1v3.1l-2.1-0.9l-0.8,2l2.9,1.2V81h2.1v-6.5l5-2.1v6.8h2.1v-7.7l2.9-1.2l-0.8-2l-2.1,0.9v-3 l5-2.1v8h2.1v-8.9l5-2.1v3.1L65,65.4l0.8,2l2.1-0.9v7.3h2.1V60.2l2-0.8L71.2,57.4z M67.5,52.1l-15.7-8.3l9-3.7l3.7,2.1 c2.1,1.2,3.4,3.4,3.4,5.9v4L67.5,52.1z M32.1,66.2l-5-2.1v-3.1l5,2.1V66.2z M53.6,70.1l-5,2.1v-3.1l5-2.1V70.1z M37.7,51.3 c1.4-1.5,1.3-3.9-0.2-5.3c-1.4-1.3-3.7-1.3-5.1,0l1.5,1.5c0.6-0.7,1.6-0.7,2.3-0.1c0.7,0.6,0.7,1.6,0.1,2.3c0,0,0,0-0.1,0.1 L37.7,51.3z M14.4,67.4c-1.9,0-3.8,0.6-5.4,1.8c-1.6-1.1-3.4-1.8-5.4-1.8H2.8l-1.1,1.1v8c0,3.7,2.7,6.8,6.3,7.3v8h2.1v-4.3h5.2v-2.1 h-5.2v-1.5c3.6-0.5,6.3-3.6,6.3-7.2v-8l-1.1-1.1L14.4,67.4z M14.2,76.5c0.1,2.9-2.1,5.3-5,5.4c-2.9,0.1-5.3-2.1-5.4-5 c0-0.2,0-0.3,0-0.5v-7c1.3,0,2.5,0.4,3.6,1.1c-0.7,0.8-1.3,1.8-1.6,2.8l2,0.7c1-2.7,3.5-4.6,6.4-4.7L14.2,76.5z M92.2,4.7H77.9 l-1.1,1.1V18c-0.7-0.5-1.6-0.7-2.5-0.8c-2.6,0-4.6,2.1-4.6,4.7s2.1,4.6,4.7,4.6c2.6,0,4.6-2.1,4.6-4.6v-9.7h12.2V18 c-0.7-0.5-1.6-0.7-2.5-0.8c-2.6,0-4.7,2.1-4.7,4.7s2.1,4.6,4.7,4.6c2.6,0,4.7-2.1,4.7-4.7V5.8L92.2,4.7z M74.3,24.4 c-1.4,0-2.5-1.1-2.5-2.5c0-1.4,1.1-2.5,2.5-2.5s2.5,1.1,2.5,2.5c0,0,0,0,0,0C76.9,23.3,75.7,24.4,74.3,24.4z M79,6.9h12.2v3.2H79 V6.9z M88.7,24.4c-1.4,0-2.5-1.1-2.5-2.5c0-1.4,1.1-2.5,2.5-2.5s2.5,1.1,2.5,2.5c0,0,0,0,0,0C91.2,23.3,90.1,24.4,88.7,24.4z  M56.7,21.6c-0.5-1.1-1.2-2.1-2-3c0.1-0.4,0.1-0.8,0.1-1.1c0-4-3.2-7.4-7.3-7.4c-4,0-7.4,3.2-7.4,7.3c0,0.4,0,0.9,0.1,1.3 c-2.8,2.9-3.6,7.1-2,10.8c0.2,0.4,0.4,0.8,0.6,1.2c-1.6,1.3-1.9,3.6-0.6,5.3c1.3,1.6,3.6,1.9,5.3,0.6c0.5-0.4,0.9-0.9,1.1-1.4 c1.9,0.6,3.8,0.6,5.7,0c0.8,1.9,3.1,2.7,5,1.9c1.9-0.8,2.7-3.1,1.9-5c-0.2-0.6-0.6-1-1.1-1.4C57.7,27.9,58,24.6,56.7,21.6L56.7,21.6 z M40,27.8c-0.7-2.2-0.4-4.7,0.9-6.6c2.6,3.3,6.5,5.3,10.7,5.3c1.2,0,2.5-0.1,3.7-0.5c-0.3,4.3-4.1,7.6-8.4,7.3 C43.7,33,41,30.9,40,27.8z M55.2,23.8c-4.8,1.6-10-0.2-12.9-4.2c3.3-2.8,8.3-2.5,11.1,0.9c0.7,0.8,1.2,1.7,1.5,2.7 C55.1,23.3,55.1,23.6,55.2,23.8L55.2,23.8z M47.5,12.2c2.7,0,4.9,2,5.2,4.7c-3.2-1.9-7.1-1.9-10.3,0C42.6,14.3,44.8,12.2,47.5,12.2z  M41.2,35.2c-0.9,0-1.6-0.8-1.6-1.6c0-0.5,0.2-0.9,0.6-1.2c0.7,0.8,1.6,1.5,2.5,2C42.4,34.9,41.8,35.2,41.2,35.2z M55.4,33.5 c0,0.9-0.7,1.6-1.6,1.6c-0.6,0-1.2-0.3-1.4-0.9c0.9-0.5,1.8-1.2,2.5-2C55.2,32.6,55.4,33.1,55.4,33.5z M84.3,78.2l1.5-1.5l-3.6-3.6 h-1.5l-3.6,3.6l1.5,1.5l2.8-2.8L84.3,78.2z M87.9,80.2l-3.6,3.6l1.5,1.5l2.8-2.8l2.8,2.8l1.5-1.5l-3.6-3.6H87.9z M80.8,87.4L77.2,91 l1.5,1.5l2.8-2.8l2.8,2.8l1.5-1.5l-3.6-3.6H80.8z"/>
</svg>';
}
add_shortcode('experiencial_icon4','short_experiencial_icon4');

function short_experiencial_icon5() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"  viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#55C5E5;}</style>
<path class="color_circle" d="M45.9,1.1L45.9,1.1c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0 C1.1,21.1,21.1,1.1,45.9,1.1z"/>
<path d="M76.1,23.6l1-0.9c0.1-0.7,0.2-1.3,0.2-2c0-5.5-4.5-10-10-10c-0.6,0-1.2,0.1-1.8,0.2C63,6,57,4,52.1,6.5 c-2.8,1.4-4.8,4-5.4,7.1c-1.4-0.7-2.9-1.1-4.5-1.1c-5.5,0-10,4.5-10,10l1.1,1.1L76.1,23.6z M42.2,14.7c1.7,0,3.4,0.5,4.8,1.6 l1.7-0.8l0,0c0-4.4,3.6-7.8,8-7.8c3.2,0,6,2,7.2,4.9l1.3,0.6c0.7-0.2,1.4-0.3,2.1-0.3c4.4,0,7.9,3.5,7.9,7.9c0,0.2,0,0.5,0,0.7H34.4 C35,17.6,38.3,14.7,42.2,14.7z M12.7,25.4c7,0,12.7-5.7,12.7-12.7S19.7,0,12.7,0S0,5.7,0,12.7C0,19.7,5.7,25.4,12.7,25.4z M12.7,2.1 c5.8,0,10.6,4.7,10.6,10.6s-4.7,10.6-10.6,10.6S2.1,18.5,2.1,12.7C2.1,6.9,6.9,2.1,12.7,2.1z M80.6,5.4l3.6-3.6l-1.5-1.5l-2.8,2.8 L77,0.3l-1.5,1.5l3.6,3.6H80.6z M82.7,9l3.6,3.6h1.5L91.3,9l-1.5-1.5L87,10.3l-2.8-2.8L82.7,9z M61.2,56.3L60,54.5 c-4.8,3.2-8.5,8-10.3,13.5h-0.3c-3.6,0-6.5,2.9-6.5,6.4c0,3.6,2.9,6.5,6.4,6.5s6.5-2.9,6.5-6.4c0-2.6-1.6-5-4-6 C53.5,63.5,56.8,59.2,61.2,56.3z M53.7,74.5c0,2.4-1.9,4.3-4.3,4.3s-4.3-1.9-4.3-4.3c0-2.4,1.9-4.3,4.3-4.3c0,0,0,0,0,0 C51.8,70.2,53.7,72.1,53.7,74.5z M59,76.2h2.1c0-2.3,0.3-4.6,1-6.9l-2-0.6C59.4,71.2,59,73.7,59,76.2z M90.6,51.6v-1.5h-15v-7.6 l4,1.5l-1.9,0.7l0.8,2L91,42l-0.8-2l-7.7,3l-7-2.7v-2.4H78v-2.1H53v2.1h2.5v3.2L51,42.8l-20.9-8.7h-0.8l-22,8.5l-5.9-2.3l-0.8,2 l12.5,4.8l0.8-2l-3.7-1.4l12.4-4.8l6.5,2.7H30l6.8-2.4l11.3,4.7l-3,1.2l0.8,2l9.6-3.7v8.1c-1.5,0.5-2.9,1.1-4.3,1.9 c-3.8-2.1-8.1-3.2-12.5-3.2H1.1v1.6l-0.2,1.1c9.1,2,16.3,9.1,18.3,18.3l2.1-0.4C19.4,62.4,13.7,55.6,6,52.2h11.2 c9.6,0,18.4,5.8,22.1,14.7c-1.1,3-1.7,6.1-1.7,9.3v14.3h2.1V76.2c0-13.3,10.7-24,24-24h11.2C69,54.8,64.3,59.3,61.6,65l1.9,0.9 c4-8.3,12.4-13.7,21.7-13.7h0.5c-9.6,4.2-15.8,13.6-15.8,24v14.3h2.1V76.2c0-11.3,7.9-21,18.9-23.5L90.6,51.6z M29.7,39.5l-4-1.7 l4.1-1.6l4.2,1.8L29.7,39.5z M45.8,57.2c-1.1-1.1-2.4-2.1-3.7-2.9L41,56.1c1.2,0.8,2.3,1.7,3.4,2.6c-1.5,1.7-2.8,3.6-3.9,5.6 c-2.8-5.4-7.3-9.7-12.9-12.1h11.1c3.6,0,7.1,0.8,10.3,2.4C47.9,55.4,46.8,56.3,45.8,57.2z M57.6,50.8V37.9h15.8v12.2h-6.8v-4.3h-2.1 v4.3h-0.8C61.6,50.1,59.6,50.4,57.6,50.8z M80.6,76.2h2.1c0-7.1,3.1-13.9,8.6-18.4l-1.4-1.6C84,61.2,80.5,68.5,80.6,76.2z  M20.8,73.4c-3.6,0-6.4,2.9-6.4,6.4s2.9,6.4,6.4,6.4s6.4-2.9,6.4-6.4c0,0,0,0,0,0C27.2,76.3,24.3,73.4,20.8,73.4z M20.8,84.1 c-2.4,0-4.3-1.9-4.3-4.3c0-2.4,1.9-4.3,4.3-4.3s4.3,1.9,4.3,4.3c0,0,0,0,0,0C25.1,82.2,23.1,84.1,20.8,84.1z M85.2,78.8 c-3.6,0-6.4,2.9-6.4,6.4s2.9,6.4,6.4,6.4s6.4-2.9,6.4-6.4c0,0,0,0,0,0C91.6,81.6,88.8,78.8,85.2,78.8z M85.2,89.5 c-2.4,0-4.3-1.9-4.3-4.3c0-2.4,1.9-4.3,4.3-4.3c2.4,0,4.3,1.9,4.3,4.3c0,0,0,0,0,0C89.5,87.6,87.6,89.5,85.2,89.5z M59.1,81.6h2.1 v8.9h-2.1V81.6z M20,53.9l-1.1,1.8c6.1,3.7,10.3,10,11.3,17.1l2.1-0.3C31.2,64.8,26.7,58,20,53.9z M1.8,56.2l-1.4,1.6 C5.8,62.4,9,69.1,9,76.2v3.6h2.1v-3.6C11.1,68.5,7.7,61.2,1.8,56.2z M48.3,83.4h2.1v7.4h-2.1V83.4z M30.4,79.8h2.1v10.7h-2.1V79.8z  M65.2,28.7l-12.5,3.6l0.6,2.1l12.2-3.5l12,3.5l0.6-2l-12.3-3.6H65.2z"/>
</svg>';
}
add_shortcode('experiencial_icon5','short_experiencial_icon5');

function short_experiencial_icon6() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"  viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#55C5E5;}</style>
<path class="color_circle" d="M47.5,2.7L47.5,2.7c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0 C2.8,22.8,22.8,2.7,47.5,2.7z"/>
<path d="M9.9,82.6c2.6,0,4.7-2.1,4.7-4.6c0-2.6-2.1-4.7-4.6-4.7s-4.7,2.1-4.7,4.6c0,0,0,0,0,0C5.3,80.5,7.3,82.6,9.9,82.6z  M9.9,75.4c1.4,0,2.5,1.1,2.5,2.5c0,1.4-1.1,2.5-2.5,2.5s-2.5-1.1-2.5-2.5c0,0,0,0,0,0C7.4,76.5,8.5,75.4,9.9,75.4z M18.1,91.3V67.2 l-1-1H2.8l-1,1v24.2c0,0.3-0.1,0.6,0,0.9h0l1,1h14.3l1-1h0.1C18.1,91.9,18.1,91.6,18.1,91.3z M3.8,68.2h12.3v18.6 c-3-3.4-8.2-3.7-11.6-0.7c-0.3,0.2-0.5,0.5-0.7,0.7L3.8,68.2z M15.9,91.2h-12c0.5-3.3,3.7-5.6,7-5C13.5,86.6,15.5,88.6,15.9,91.2z  M8.1,69.7h3.6v2.1H8.1V69.7z M85.1,82.6c2.6,0,4.7-2.1,4.7-4.7s-2.1-4.7-4.7-4.7s-4.7,2.1-4.7,4.7S82.5,82.6,85.1,82.6L85.1,82.6z  M85.1,75.4c1.4,0,2.5,1.1,2.5,2.5s-1.1,2.5-2.5,2.5c-1.4,0-2.5-1.1-2.5-2.5v0C82.6,76.5,83.7,75.4,85.1,75.4 C85.1,75.4,85.1,75.4,85.1,75.4z M93.3,92.2c0-0.3,0-0.6-0.1-0.9V67.2l-1-1H77.9l-1,1v24.2c0,0.3,0,0.6,0,0.9l0,0l1,1h14.3 L93.3,92.2L93.3,92.2L93.3,92.2z M78.9,68.2h12.3v18.6c-3-3.4-8.2-3.7-11.5-0.7c-0.3,0.2-0.5,0.5-0.7,0.7V68.2z M91.1,91.2h-12 c0.5-3.3,3.7-5.6,7-5C88.6,86.7,90.7,88.7,91.1,91.2z M83.3,69.7h3.6v2.1h-3.6V69.7z M47.5,28.6c-3.6,0-6.4,2.9-6.4,6.4 s2.9,6.4,6.4,6.4s6.4-2.9,6.4-6.4c0,0,0,0,0,0C53.9,31.4,51.1,28.6,47.5,28.6z M47.5,39.3c-2.4,0-4.3-1.9-4.3-4.3s1.9-4.3,4.3-4.3 c2.4,0,4.3,1.9,4.3,4.3c0,0,0,0,0,0C51.8,37.4,49.9,39.3,47.5,39.3z M80.8,53c-0.2-0.9-0.9-1.5-1.7-1.7l-4.7-0.9 c-0.7-0.1-1.4,0-2,0.5c-0.3,0.3-0.6,0.6-0.7,1l-9.3,3c-0.6-1.3-1-2.6-1.3-4l-0.2-1.3c-0.7-3.9-4-6.7-8-6.7h-1.8V45h1.8 c2.9,0,5.4,2.1,5.9,5l0.2,1.3c0.2,1.5,0.7,2.9,1.3,4.3l-3,1c-0.7-1.5-1.3-3.1-1.6-4.7l-2.1,0.4c0.3,1.7,0.9,3.4,1.6,5L51,58.6 c0.1-0.8,0.3-1.5,0.7-2.2c0.6-0.9,0.7-2,0.4-3c-0.3-0.7-0.8-1.2-1.5-1.4c-1.3-0.3-2.6-0.1-3.8,0.6c-0.9,0.5-1.8,1.2-2.5,2 c-0.3,0.4-0.7,0.7-1.1,0.9c-0.6-1.1-1.1-2.4-1.4-3.7l-2.1,0.4c0.9,4.8,4.5,8.4,5.5,9.3c0.6,0.6,0.7,1.6,0.2,2.2 c-0.3,0.3-0.7,0.5-1.1,0.6c-0.4,0-0.9-0.1-1.2-0.4c-3.5-3.1-7-7-7-12.9c0-3.4,2.7-6.1,6.1-6.1h1.8v-2.2h-1.8c-4.5,0-8.2,3.7-8.2,8.2 c0,1.5,0.2,3,0.7,4.5c-0.4,0-0.7,0.1-1.1,0.2c-3.6,1.2-5.8,5.6-5.4,10.9s3.7,9.5,7.6,10l-3.4,14.5h-9.9v2.1h50.1v-2.1h-9.9l-6.3-27 l-2.1,0.5l6.2,26.5h-3.3l-7.7-20c0.3,0,0.7,0,1-0.1c1-0.2,1.8-0.9,2.1-1.9c0.3-1-0.1-2.1-1-2.8c-0.7-0.5-1.1-1.3-1.1-2.1l7.3-2.3 c0.5,0.8,1,1.6,1.5,2.4l0.3,0.4c0.5,0.9,1.4,1.5,2.4,1.7c1,0.2,2,0,2.9-0.6c1.7-1.1,2.2-3.3,1.1-5.1c-0.2-0.4-0.5-0.8-0.8-1.2 l8.4-2.7l2.3,0.6l0.8-0.1l3.1-2.1C80.6,54.7,80.9,53.9,80.8,53z M38,76.6l0.5-0.1c2.1-0.7,3.6-2.6,4.7-4c0.6-0.8,1.4-1.3,2.4-1.5 l-7.8,20.2h-3.3L38,76.6z M40.1,91.2L47.5,72l7.4,19.2L40.1,91.2z M63.1,60.4c0.4,0.5,0.7,1.1,1,1.5c0.5,0.8,0.2,1.8-0.6,2.2 c-0.4,0.2-0.8,0.3-1.2,0.2c-0.4-0.1-0.8-0.4-1-0.7L61,63.2c-0.4-0.6-0.8-1.3-1.2-1.9l3.2-1L63.1,60.4z M78.7,53.5l-2.7,1.8l-2.2-0.5 h-0.6l-23.9,7.7l-0.7,0.7c-0.6,1.9,0.1,3.9,1.7,5c0.2,0.1,0.3,0.4,0.2,0.6c0,0.2-0.2,0.3-0.4,0.3c-1,0.1-1.9,0.1-2.9-0.1 c-2.2-0.3-4.4,0.5-5.8,2.3c-0.9,1.2-2.2,2.8-3.7,3.3c-0.3,0.1-0.7,0.2-1,0.2c-3.3,0.2-6.2-3.4-6.6-8.1c-0.3-4.2,1.3-7.9,3.9-8.7 c0.4-0.1,0.8-0.2,1.2-0.2c1.5,3,3.7,5.7,6.3,7.9c0.7,0.6,1.6,0.9,2.5,0.9h0.3c1-0.1,2-0.6,2.6-1.4c1.3-1.5,1.1-3.8-0.3-5.1 c-0.9-0.8-1.7-1.7-2.4-2.7c0.6-0.3,1.1-0.8,1.5-1.3c0.5-0.6,1.2-1.1,1.9-1.5c0.6-0.4,1.3-0.6,2-0.5c0.1,0,0.2,0.1,0.2,0.2 c0.1,0.2-0.1,0.6-0.4,1.3c-0.8,1.5-1,3.1-0.6,4.7l1.4,0.8l22.6-7.2l0.7-1c0-0.1,0-0.2,0.1-0.2c0.1-0.1,0.2-0.1,0.2-0.1l4.7,0.9 l0.2-1L78.7,53.5z M17.8,49.3h2.1v3.6h-2.1V49.3z M17.8,56.4h2.1V60h-2.1V56.4z M20.6,53.6h3.6v2.1h-3.6V53.6z M13.5,53.6h3.6v2.1 h-3.6V53.6z M23.2,29.6h2.1v3.6h-2.1V29.6z M23.2,36.8h2.1v3.6h-2.1V36.8z M26,33.9h3.6V36H26V33.9z M18.9,33.9h3.6V36h-3.6V33.9z  M7.1,36.8h2.1v3.6H7.1V36.8z M7.1,43.9h2.1v3.6H7.1V43.9z M9.9,41.1h3.6v2.1H9.9V41.1z M2.8,41.1h3.6v2.1H2.8V41.1z M11.6,12.6 l0.3,0.8l1.3,1.3c-1.1,1.8-0.9,4.2,0.7,5.7l2.5,2.5h1.5l5.1-5.1v-1.5l-2.5-2.5c-1.5-1.5-3.9-1.8-5.7-0.6l-1-1v-3h32.8v3.4 c-2.1,0.5-3.6,2.4-3.6,4.5v3.6l1.1,1.1h7.2l1.1-1.1v-3.5c0-2.2-1.5-4-3.6-4.5V9.2h32.8v3l-1,1c-1.8-1.1-4.2-0.9-5.7,0.6l-2.5,2.5 v1.5l5.1,5.1h1.5l2.5-2.5c1.5-1.5,1.8-3.9,0.7-5.7l1.3-1.3l0.3-0.8V9.2h8.8V7.1H2.8v2.1h8.8V12.6z M17.1,14.6c0.7,0,1.3,0.3,1.8,0.7 l1.8,1.8l-3.6,3.6l-1.8-1.8c-1-1-1-2.6,0-3.5C15.8,14.8,16.4,14.6,17.1,14.6L17.1,14.6z M50,17.1v2.5h-5v-2.5 c0.2-1.4,1.4-2.4,2.8-2.2C49,15,49.9,15.9,50,17.1z M79.7,18.8l-1.8,1.8l-3.6-3.6l1.8-1.8c1-1,2.6-1,3.6,0S80.7,17.9,79.7,18.8 C79.7,18.8,79.7,18.8,79.7,18.8z M86.9,32.4c1.7,0,3.4,0.3,5,0.8l0.6-2c-1.8-0.6-3.7-0.8-5.6-0.9c-7.4,0-14.1,4.3-17.2,11 c-3,1.3-5.8,3.1-8.2,5.4l1.5,1.5c6.9-6.7,17.1-8.7,26-5l0.8-2c-5.4-2.3-11.5-2.7-17.2-1.1C75.7,35.4,81.1,32.4,86.9,32.4z M6.3,1.7 h10.8v2.1H6.3V1.7z M36.8,1.7h7.2v2.1h-7.2V1.7z M69,1.7h10.7v2.1H69V1.7z"/>
</svg>';
}
add_shortcode('experiencial_icon6','short_experiencial_icon6');


//**CULTURAL
function short_cultural_icon1() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#E86848;}</style>
<path class="color_circle" d="M47.2,3.1L47.2,3.1c24.7,0,44.8,20,44.8,44.8v0c0,24.7-20,44.8-44.8,44.8h0c-24.7,0-44.8-20-44.8-44.8v0C2.4,23.2,22.4,3.1,47.2,3.1z"/>
<path d="M87.6,7.5L84,3.9l1.5-1.5l2.8,2.8l2.8-2.8l1.5,1.5l-3.6,3.6H87.6z M80.4,14.6L76.8,11l1.5-1.5l2.8,2.8L84,9.5l1.5,1.5l-3.6,3.6H80.4z M74.8,7.5h-1.5l-3.6-3.6l1.5-1.5L74,5.2l2.8-2.8l1.5,1.5L74.8,7.5z M82.2,92.6h-2.1V89c-0.2-1.4-1.4-2.4-2.8-2.2c-1.1,0.1-2,1-2.2,2.2v3.6h-2.2V89c0.1-2.6,2.3-4.5,4.9-4.4c2.4,0.1,4.3,2,4.4,4.4V92.6z M22.9,41.5L21.3,40l3.6-3.6h1.5L30,40l-1.5,1.5l-2.8-2.8L22.9,41.5z M41.8,54H9.6v2.1h2.5v12.1h2.1V56.1h22.9v12.1h2.1V56.1h2.5V54z M43.8,92.6h-2.1v-6.1h-6.8v6.1h-2.1v-7.2l1.1-1.1h9l1.1,1.1V92.6z M18.7,92.6h-2.1v-6.1H9.7v6.1H7.6v-7.2l1.1-1.1h9l1.1,1.1V92.6z M73.8,34.4c-1.3-0.5-2.7-0.8-4.2-0.9c-0.5-7-6.6-12.2-13.6-11.7s-12.2,6.6-11.7,13.6c0.2,2,0.8,4,1.8,5.7l-8.3,3.2L35.6,42l-0.9-3.8h1.8v-2.2h-2.3l-2-8V17.4l-1.1-1.1h-4.3V7.8h2.5V5.6h-2.5V3.1h-2.1v2.5h-2.5v2.1h2.5v8.6h-4.3l-1.1,1.1v10.6l-2,8h-2.3v2.1h1.8l-0.8,3l-13.2-5l-0.8,2L14.7,43l-5.9,5.9l0.8,1.8h32.2l0.8-1.8l-3-3l24.7-9.5c2.9-1.1,6-1.1,8.9,0l18.5,7.1l0.8-2L73.8,34.4z M21.4,18.5H30v8.6h-8.6V18.5z M12.1,48.6l5.3-5.3l0.3-0.5l3.4-13.5h9.1l3.4,13.5l0.3,0.5l5.3,5.3H12.1z M63.4,34.4l-15.2,5.9c-1.2-1.7-1.8-3.8-1.8-5.8c0-5.8,4.8-10.6,10.6-10.5c5.4,0,10,4.1,10.5,9.5C66.1,33.6,64.7,33.9,63.4,34.4z M92.7,79.4L78.6,61.8v-7.5h2.5v-2.1h-2.5v-2.5h-2.1v2.5H74v2.1h2.5v7.5L63.2,78.5l-8.1-8.1l-0.8-0.3h-24V64c0-2.6-2.1-4.7-4.7-4.7S21,61.4,21,64v6.1H2.4v2.1h51.5l6.8,6.8H2.4v2.1h22.2v11.5h2.1V81.2h26.5v11.5h2.1V81.2h6.8v11.5h2.1V81.2h26.5v11.5H93V80.1L92.7,79.4z M23.1,64c0.2-1.4,1.4-2.4,2.8-2.2c1.1,0.1,2,1,2.2,2.2v6.1h-5V64z M89.7,79H65.5l12.1-15.1L89.7,79z M69.7,56.8h-2.1c0,3.9-2.9,7.3-6.8,7.8V54.2c3.1-0.5,5.4-3.2,5.4-6.3H64c0,2-1.4,3.6-3.2,4.1v-7.7h-2.1v15c-2.9-0.5-5-3.1-5-6h-2.2c0,4.1,3.1,7.6,7.2,8.1v9.8h2.1v-4.4C65.8,66.3,69.7,62,69.7,56.8z M24.6,21h2.1v7.2h-2.1V21z"/>
</svg>';
}
add_shortcode('cultural_icon1','short_cultural_icon1');

function short_cultural_icon2() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#E86848;}</style>
<path class="color_circle" d="M47.3,92.2L47.3,92.2c-24.7,0-44.8-20-44.8-44.8l0,0c0-24.7,20-44.8,44.8-44.8l0,0C72,2.7,92,22.8,92,47.5l0,0C92,72.2,72,92.2,47.3,92.2z"/>
<path d="M45.5,60.7h3.6v2.1h-3.6V60.7z M77,86.9l-1.1-1.1h-2.5v-7.9l-1.1-1.1h-1.8l-1.6-8.1l-1-0.9h-3.6L63.2,69v7.9H51.7l-1.6-8.1l-1-0.9h-3.6l-1,0.9l-1.6,8.1H31.4V69l-1.1-1.1h-3.6l-1,0.9L24,76.9h-1.8l-1.1,1.1v7.9h-2.5l-1.1,1.1v4.3h-6.1v2.1h19.7v-2.1H19.7v-3.2h55.2v3.2H63.4v2.1h19.7v-2.1H77V86.9z M65.4,70H67l1.4,6.8h-3V70z M46.4,70h1.8l1.4,6.8H45L46.4,70z M27.6,70h1.6v6.8h-3L27.6,70z M23.3,85.8V79h48v6.8L23.3,85.8z M46.2,24.2h2.1v32.2h-2.1V24.2z M53.4,65.4h2.1V69h-2.1V65.4z M39.1,65.4h2.1V69h-2.1V65.4z M80.4,27.1c7,0,12.7-5.7,12.7-12.7S87.4,1.7,80.4,1.7S67.7,7.4,67.7,14.4c0,0,0,0,0,0C67.7,21.4,73.4,27.1,80.4,27.1z M80.4,3.8C86.2,3.8,91,8.6,91,14.4S86.2,25,80.4,25s-10.6-4.7-10.6-10.6C69.8,8.6,74.6,3.8,80.4,3.8z M58.6,36.9l-3.3,1.5l-1.5-17.8l-0.3-0.7L48,14.5h-1.5l-5.4,5.4l-0.3,0.7l-2.2,25.3L29,50.2l-11.5-8.9h-1.3L1.9,52l1.3,1.7l13.7-10.2l14.6,11.4l1.3-1.7l-1.9-1.5l7.5-3.5l-1.1,12.5h-0.8l-0.8,0.3L34,62.9l-0.3,0.8V75h2.1V64.1l1.2-1.2h4.9v-2.1h-2.4l3.4-39.6l4.3-4.3l4.3,4.3l3.4,39.6h-2.4v2.1h4.9l1.2,1.2V75h2.1V63.6l-0.3-0.8l-1.8-1.8L58,60.7h-0.8l-1.7-20.1l4-1.8c3.6-1.7,7.7-1.6,11.2,0.3l20.9,11.1l1-1.9L71.7,37.2C67.6,35.1,62.8,34.9,58.6,36.9z M24.8,17.8l3.6-3.6l-1.5-1.5L24,15.6l-2.8-2.8l-1.5,1.5l3.6,3.6H24.8z M15.8,12.4l3.6-3.6l-1.5-1.5l-2.8,2.8l-2.8-2.8l-1.5,1.5l3.6,3.6H15.8z M5.3,34.2h19.1l0.8-0.3c1.1-1.2,1.7-2.7,1.7-4.3c0-3.6-2.9-6.4-6.4-6.4c-1.1,0-2.1,0.3-3,0.8c-3.1-3.3-8.4-3.4-11.6-0.2C3,26.3,2.5,30.4,4.3,33.7L5.3,34.2z M11.5,23.5c1.6,0,3.1,0.6,4.2,1.7c-0.3,0.3-0.5,0.6-0.7,0.9l1.8,1.1c1.2-2,3.9-2.7,5.9-1.4c2,1.2,2.7,3.9,1.4,5.9c-0.1,0.1-0.2,0.2-0.2,0.4h-18c-1.4-3.1,0-6.7,3-8.1C9.7,23.7,10.6,23.5,11.5,23.5L11.5,23.5z M82.4,56.4h-2.1c0,1.4-1.1,2.5-2.5,2.5c0,0,0,0,0,0v2.1c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7V59C83.5,59,82.4,57.9,82.4,56.4C82.4,56.5,82.4,56.5,82.4,56.4z M13.3,71.8v-2.1c-1.4,0-2.5-1.1-2.5-2.5c0,0,0,0,0,0H8.6c0,1.4-1.1,2.5-2.5,2.5c0,0,0,0,0,0v2.1c1.4,0,2.7-0.6,3.6-1.7C10.6,71.2,11.9,71.8,13.3,71.8z"/>
</svg>';
}
add_shortcode('cultural_icon2','short_cultural_icon2');

function short_cultural_icon3() {
	return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#E86848;}</style>
<path class="color_circle" d="M47,92.8L47,92.8c-24.7,0-44.8-20-44.8-44.8l0,0c0-24.7,20-44.8,44.8-44.8l0,0c24.7,0,44.8,20,44.8,44.8l0,0C91.8,72.7,71.7,92.8,47,92.8z"/>
<path d="M87.5,53.4l-0.6-0.9L67.2,41.7h-1L46.5,52.5L46,53.4v17.3c-3.5-0.4-6.9-0.6-10.4-0.4V53.4l-0.9-1l-8.8-1.5c2.7-1.9,4.3-4.9,4.3-8.2h-2.1c0,3.6-2.4,6.7-5.9,7.6l-1-0.2V40c3.1-0.5,5.4-3.2,5.4-6.3h-2.1c0,2-1.3,3.7-3.2,4.2v-7.7h-2.1v15c-2.9-0.5-5-3.1-5-6H12c0,4.1,3.1,7.6,7.2,8.2v2.5L2.5,47l-0.3,2.1l31.3,5.2v16.1c-5.2,0.4-10.4,1.4-15.4,3l-5.4-2.6c-3.2-1.6-6.8-2.4-10.4-2.4v2.1h0.2c3.2,0,6.3,0.7,9.2,2.1l3.4,1.7c-0.5,0.2-1.1,0.4-1.6,0.6l0.8,2c16.6-6.6,35.2-6,51.3,1.7l0.9-1.9c-0.6-0.3-1.3-0.6-1.9-0.9c8.8-2.1,18-2.2,26.9-0.5l0.5-2.1c-1.5-0.3-3-0.5-4.5-0.7L87.5,53.4z M58.8,73.6v-7.7c0.2-4.4,3.8-7.8,8.2-7.6c4.1,0.1,7.5,3.5,7.6,7.6v6.3c-4.5,0.3-8.9,1.1-13.2,2.3C60.5,74.2,59.7,73.9,58.8,73.6z M76.7,72.1v-6.2c0-5.5-4.5-10-10-10s-10,4.5-10,10v7c-1.1-0.3-2.2-0.6-3.2-0.9v-4.3h-2.2v3.9c-1.1-0.2-2.2-0.4-3.2-0.6V54l18.6-10.1L85.3,54v18.3c-1.1-0.1-2.2-0.2-3.2-0.2v-6.2c0-2-0.4-3.9-1.1-5.8L79,61c0.7,1.6,1,3.3,1,5v6C78.9,72,77.8,72,76.7,72.1z M15.7,55.9h8.1V58h-8.1V55.9z M22.9,66.7h5.4v2.1h-5.4V66.7z M2.3,61.3h7.2v2.1H2.3V61.3z M7.8,20.5h42.8l1.1-1.1c0-5.5-4.5-10-10-10c-1.5,0-3.1,0.3-4.5,1c-1-5.4-6.2-9.1-11.6-8.1c-3.1,0.6-5.8,2.6-7.2,5.5c-5.4-1-10.6,2.6-11.6,8c-0.1,0.6-0.2,1.2-0.2,1.8c0,0.7,0.1,1.3,0.2,2L7.8,20.5z M16.6,9.7c0.7,0,1.4,0.1,2.1,0.3L20,9.4c1.6-4.1,6.1-6.1,10.2-4.5c3,1.2,5,4.1,5,7.4l0,0l1.7,0.9c1.4-1,3-1.6,4.7-1.6c3.9,0,7.3,2.9,7.8,6.8H8.8c0-0.2,0-0.5,0-0.7C8.8,13.2,12.3,9.7,16.6,9.7z M80.2,2.2c-7,0-12.7,5.7-12.7,12.7s5.7,12.7,12.7,12.7s12.7-5.7,12.7-12.7c0,0,0,0,0,0C92.9,7.9,87.2,2.2,80.2,2.2z M80.2,25.5c-5.8,0-10.6-4.7-10.6-10.6S74.4,4.3,80.2,4.3s10.6,4.7,10.6,10.6C90.8,20.8,86,25.5,80.2,25.5z M47,39.4l-2.8-2.8L42.7,38l3.6,3.6h1.5l3.6-3.6l-1.5-1.5L47,39.4z M49.9,29.1l3.6,3.6H55l3.6-3.6L57,27.6l-2.8,2.8l-2.8-2.8L49.9,29.1z M87.2,36.3l-1.5-1.5l-2.8,2.8L80,34.7l-1.5,1.5l3.6,3.6h1.5L87.2,36.3z M12.3,85.6h-2.1c0,1.4-1.1,2.5-2.5,2.5v2.1c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1C13.4,88.1,12.3,87,12.3,85.6z M66.2,87.4h-2.1c0,1.4-1.1,2.5-2.5,2.5v2.1c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1C67.3,89.9,66.2,88.8,66.2,87.4z M40.9,80.2h-2.1c0,1.4-1.1,2.5-2.5,2.5v2.1c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1C42.1,82.8,40.9,81.6,40.9,80.2z M51.3,65.9h2.1c0-5.4,3.3-10.3,8.3-12.3l-0.8-2C55.1,54,51.3,59.6,51.3,65.9z M66.7,50.5c-0.6,0-1.3,0-1.9,0.1l0.3,2.1c4.6-0.6,9.1,1.3,12,4.9l1.7-1.3C75.8,52.7,71.4,50.5,66.7,50.5z"/>
</svg>';
}
add_shortcode('cultural_icon3','short_cultural_icon3');

//**PATRIMONIAL
function short_patrimonial_icon1() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#FFA661;}</style>
<path class="color_circle" d="M47.5,3.3L47.5,3.3c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0C2.8,23.4,22.8,3.3,47.5,3.3z"/>
<path d="M46.8,38.9l-3.6-3.6l1.5-1.5l2.8,2.8l2.8-2.8l1.5,1.5l-3.6,3.6H46.8z M23.5,28.4h-2.1v-8.9l1.1-1.1h5.4v2.1h-4.3V28.4z M39.9,60.1c-1.5-1.5-1.5-3.8,0-5.3s3.8-1.5,5.3,0l0,0l-1.5,1.5c-0.6-0.6-1.7-0.6-2.3,0c-0.6,0.6-0.6,1.6,0,2.3c0,0,0,0,0,0L39.9,60.1z M50.6,58.8c-1.5-1.5-1.5-3.8,0-5.3s3.8-1.5,5.3,0L54.4,55c-0.6-0.6-1.6-0.6-2.3,0c0,0,0,0,0,0c-0.6,0.6-0.6,1.7,0,2.3L50.6,58.8z M44.6,65.7l1.8-7.2l2.1,0.5l-1.8,7.2L44.6,65.7z M43.8,74.9h2.1v4h-2.1V74.9z M49.1,74.9h2.1v4h-2.1V74.9z M39.3,3.6l2.1-0.5l1.8,7.2l-2.1,0.5L39.3,3.6z M51.8,10.2l1.8-7.2l2.1,0.5l-1.8,7.2L51.8,10.2z M64.5,9.9l3.6-5.4l1.8,1.2l-3.6,5.4L64.5,9.9z M25.1,5.7l1.8-1.2l3.6,5.4l-1.8,1.2L25.1,5.7z M9.9,54.5H2.8v-2.1h6.1V4.3H2.8V2.2h7.2L11,3.2v50.2L9.9,54.5z M92.2,58.2h-7.2L84,57.1V24.9l1.1-1.1h7.2V26h-6.1V56h6.1V58.2z M92.2,18.7c-4.5,0.2-8.4-3.4-8.5-7.9c-0.2-4.5,3.4-8.4,7.9-8.5c0.2,0,0.4,0,0.6,0v2.1c-3.4,0.1-6,3-5.8,6.3c0.1,3.2,2.7,5.7,5.8,5.8V18.7z M81.5,81H59.3v-2.6l-0.6-1c-1.8-0.9-3-2.8-3-4.8v-2.6c4.5-2.8,7.2-7.7,7.2-13c0-1.6-0.3-3.2-0.8-4.8c-0.1-0.3-0.2-0.6-0.3-0.9l4.4-4.3c1.3-1.5,1.2-3.7-0.2-5.1c-1.4-1.2-3.5-1.2-4.8,0l-3,3c-1.5-1-2.3-2.7-2.3-4.5v-3.2c0-1.4,1.1-2.5,2.5-2.5c0,0,0,0,0,0l1.1-1.1v-3.6L58.2,29H36.8l-1.1,1.1v3.6l1.1,1.1c1.4,0,2.5,1.1,2.5,2.5c0,0,0,0,0,0v3.2c0,1.8-0.9,3.4-2.3,4.5l-3-3c-1.4-1.4-3.7-1.4-5.1,0c-1.4,1.4-1.4,3.7,0,5.1l4.4,4.3c-0.1,0.3-0.2,0.6-0.3,0.9c-2.2,6.7,0.5,14,6.4,17.8v2.7c0,2.1-1.2,3.9-3,4.8l-0.6,1V81H13.5l-1.1,1.1v10.8h2.1v-9.7h6.8v9.7h2.1v-9.7h48v9.7h2.1v-9.7h6.8v9.7h2.1V82.1L81.5,81z M41.4,40.4v-3.2c0-2.2-1.5-4-3.6-4.5v-1.6h19.3v1.6c-2.1,0.5-3.6,2.4-3.6,4.5v3.2c0,2.4,1.1,4.6,3,6l-1.2,1.2c-1.1-1-2.6-1.6-4.2-1.6l0,0c-1.3,0-2.6,0.4-3.7,1.2c-1.1-0.8-2.4-1.2-3.7-1.2c-1.5,0-3,0.6-4.2,1.6l-1.2-1.2C40.3,45,41.4,42.8,41.4,40.4z M37.8,79c2.2-1.4,3.6-3.8,3.6-6.4v-1.5c0.7,0.3,1.5,0.6,2.2,0.8l0.5-2.1c-1-0.2-1.9-0.6-2.8-1.1l0,0h-0.1c-4.4-2.3-7.1-6.8-7.1-11.7c0-1.4,0.2-2.7,0.6-4l1.1,1.2l1.5-1.5l-7.2-7.2c-0.5-0.6-0.5-1.5,0.1-2.1c0.6-0.5,1.4-0.5,1.9,0l7.2,7.2l0.3-0.3l0,0l0.9-0.9c1.6-1.6,4.1-1.7,5.8-0.2c0.1,0,0.1,0.1,0.2,0.2l0.8,0.8l0.8-0.8c1.6-1.6,4.1-1.7,5.8-0.1c0.1,0,0.1,0.1,0.1,0.1l1,1l0,0l0.2,0.2l7.2-7.2c0.6-0.6,1.5-0.6,2.1,0c0.3,0.3,0.4,0.6,0.4,1c0,0.4-0.2,0.7-0.4,1l-7.1,7.2l1.5,1.5l1.1-1.1c0.4,1.3,0.6,2.6,0.6,4c0,4.9-2.7,9.5-7.1,11.7h-0.1l0,0c-0.9,0.5-1.8,0.8-2.8,1.1l0.5,2.1c0.8-0.2,1.5-0.5,2.2-0.8v1.6c0,2.6,1.4,5,3.6,6.3v2H37.8V79z M92.2,70.3H79V14.1L77.9,13H17.1L16,14.1v56.2H2.8v2.1H16v6.5h2.1V15.1h58.7v64H79v-6.7h13.2V70.3z"/>
</svg>';
}
add_shortcode('patrimonial_icon1','short_patrimonial_icon1');

function short_patrimonial_icon2() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#FFA661;}</style>
<circle class="color_circle" cx="47.9" cy="47.5" r="44.8"/>
<path d="M14.9,71.8l-3.6,3.6l1.5,1.5l2.8-2.8l2.8,2.8l1.5-1.5l-3.6-3.6H14.9z M5.9,64.7l-3.6,3.6l1.5,1.5l2.8-2.8l2.8,2.8l1.5-1.5l-3.6-3.6H5.9z M5.9,79l-3.6,3.6l1.5,1.5l2.8-2.8l2.8,2.8l1.5-1.5L7.5,79H5.9z M77.2,13.5h2.1v3.6h-2.1V13.5z M77.2,20.6h2.1v3.6h-2.1V20.6z M80.1,17.8h3.6v2.1h-3.6V17.8z M72.9,17.8h3.6v2.1h-3.6V17.8z M86.2,2.7h2.1v3.6h-2.1V2.7z M86.2,9.9h2.1v3.6h-2.1V9.9z M89.1,7h3.6v2.1h-3.6V7z M81.9,7h3.6v2.1h-3.6V7z M55,34.2c2.6,0,4.6-2.1,4.6-4.7c0-2.6-2.1-4.6-4.7-4.6c-2.6,0-4.6,2.1-4.6,4.7c0,0,0,0,0,0C50.3,32.2,52.4,34.2,55,34.2C55,34.2,55,34.2,55,34.2z M55,27.1c1.4,0,2.5,1.1,2.5,2.5s-1.1,2.5-2.5,2.5s-2.5-1.1-2.5-2.5c0,0,0,0,0,0C52.5,28.2,53.6,27.1,55,27.1C55,27.1,55,27.1,55,27.1z M30,48.2l-1.1,1.1v9l1.1,1h14.3l1.1-1.1v-9l-1.1-1H30z M43.2,57.2H31v-6.9h12.2L43.2,57.2z M54.2,48.2c-3.1,0-5.5,2.5-5.5,5.5s2.5,5.5,5.5,5.5s5.5-2.5,5.5-5.5C59.7,50.7,57.2,48.2,54.2,48.2z M54.2,57.2c-1.9,0-3.4-1.5-3.4-3.4c0-1.9,1.5-3.4,3.4-3.4c1.9,0,3.4,1.5,3.4,3.4l0,0C57.5,55.7,56,57.2,54.2,57.2L54.2,57.2z M70.1,45.4l-0.8-0.8h-4.3v-4.3L64,39.3h-2.2c5.4-3.7,6.7-11.1,3-16.4c-2.2-3.2-5.9-5.1-9.8-5.1H33.6c-6.5,0-11.8,5.3-11.8,11.8c0,3.9,1.9,7.5,5.1,9.7h-2.2l-1.1,1.1v23.3l1.1,1.1h15V69l1.1,1.1h2.5v3.4l-0.9,0.9v5.3l1.1,1.1h0.7v11.4h2.1V80.8h3.3v11.5h2.1V80.8h0.7l1.1-1.1v-5.3l-0.9-0.9V70H55l1.1-1.1v-4.3H64l1.1-1.1v-4.3h4.3l0.8-0.8c2.3,3.8,6.4,6.1,10.8,6.1l1.1-1.1V40.3L81,39.3C76.6,39.3,72.4,41.6,70.1,45.4z M51.3,78.6h-6.8v-3.2h6.8V78.6z M50.4,73.3h-5V70h5V73.3z M54,67.9H41.8v-3.2H54V67.9z M64.7,29.6c0,5.3-4.3,9.7-9.7,9.7c-5.3,0-9.7-4.3-9.7-9.7c0-5.3,4.3-9.6,9.6-9.7C60.3,19.9,64.7,24.2,64.7,29.6C64.7,29.6,64.7,29.6,64.7,29.6z M48.3,19.9c-1.7,1.2-3.1,2.9-4,4.8c-0.9-1.9-2.3-3.6-4-4.8H48.3z M23.9,29.6c0-5.3,4.3-9.7,9.7-9.7c5.3,0,9.7,4.3,9.7,9.7c0,2.5-0.9,4.8-2.6,6.6l-2.5-7.4c-0.1-0.5-0.2-0.9-0.5-1.3l0,0l0,0c-1.2-2.3-4-3.2-6.2-2c-1.6,0.8-2.5,2.4-2.5,4.1c0,0.4,0.1,0.7,0.1,1.1l0,0l0.1,0.3c0,0.1,0.1,0.3,0.1,0.4l2.6,7.7C27.3,38.3,23.9,34.3,23.9,29.6z M35.7,28.3l0.3,1c0,0.1,0,0.2,0,0.3c0,1.4-1.2,2.5-2.6,2.5c-1,0-1.8-0.6-2.2-1.5L31.1,30c-0.3-1.4,0.6-2.7,2-3C34.1,26.8,35.1,27.3,35.7,28.3L35.7,28.3z M33.6,34.3c1.4,0,2.7-0.6,3.6-1.7l1.1,3.3c-0.4-0.1-0.7-0.2-1.1-0.2c-1.4,0-2.7,0.6-3.6,1.7l-1.1-3.3C32.8,34.2,33.2,34.3,33.6,34.3L33.6,34.3z M34.9,41.5l-0.2-0.7c0-0.1,0-0.3,0-0.4c0-1.4,1.1-2.5,2.5-2.5s2.5,1.1,2.5,2.5s-1.1,2.5-2.5,2.5c0,0,0,0,0,0C36.2,42.9,35.3,42.3,34.9,41.5L34.9,41.5z M62.9,62.6H25.7V41.4h7c0.1,0.3,0.2,0.6,0.3,0.9l0,0l0,0c1.1,2.3,3.8,3.3,6.2,2.3c1.6-0.8,2.7-2.4,2.7-4.2c0-0.5-0.1-0.9-0.3-1.3l0,0l-0.2-0.5c1.2-1.1,2.2-2.4,2.9-3.9c0.9,1.9,2.3,3.6,4,4.8h-4.8v2.1h19.4L62.9,62.6L62.9,62.6z M68.3,57.2h-3.2V46.8h3.2L68.3,57.2z M79.9,62.5c-5.8-0.7-10-5.9-9.3-11.7c0.6-4.9,4.4-8.7,9.3-9.3V62.5z M21.5,44.9l0.6-2L16,41.1l-1.4,1v9l1.4,1l6.2-1.8l-0.6-2l-4.8,1.4v-6.1L21.5,44.9z M84.8,44.8l4.5-3.2l1.3,1.7l-4.5,3.2L84.8,44.8z M84.8,57.3l1.3-1.7l4.5,3.2l-1.3,1.7L84.8,57.3z M85.5,50h5.4v2.1h-5.4V50z M37.1,12.4h9v2.1h-9V12.4z M49.7,12.4h3.6v2.1h-3.6V12.4z"/>
</svg>';
}
add_shortcode('patrimonial_icon2','short_patrimonial_icon2');

function short_patrimonial_icon3() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#FFA661;}</style>
<circle class="color_circle" cx="47.5" cy="47" r="44.8"/>
<path d="M78.7,39.8h2.1v3.6h-2.1V39.8z M78.7,47h2.1v3.6h-2.1V47z M74.3,44.1h3.6v2.1h-3.6V44.1z M81.5,44.1h3.6v2.1h-3.6V44.1z M17.8,54.1h2.1v3.6h-2.1V54.1z M17.8,61.3h2.1v3.6h-2.1V61.3z M20.6,58.4h3.6v2.1h-3.6V58.4z M13.5,58.4h3.6v2.1h-3.6V58.4z M12.4,38h2.1v3.6h-2.1V38z M12.4,45.2h2.1v3.6h-2.1V45.2z M15.3,42.3h3.6v2.1h-3.6V42.3z M8.1,42.3h3.6v2.1H8.1V42.3z M36.8,28.3c1.1,0,2.1-0.3,2.9-1c0.4,0.1,0.9,0.3,1.4,0.4V34h2.1v-5.9c1,0.1,2.1,0.2,3.2,0.3V30h2.1v-1.7c1.1,0,2.2-0.1,3.2-0.3v5.9H54v-6.2c0.5-0.1,1-0.2,1.4-0.4c2,1.6,4.9,1.3,6.5-0.7c1.6-2,1.3-4.9-0.7-6.5c-0.8-0.7-1.9-1-2.9-1c-1.1,0-2.1,0.4-2.9,1c-1-0.3-2.1-0.6-3.2-0.7v-7.9c1.9-1.3,2.4-3.9,1.1-5.8c-0.8-1.1-2.1-1.8-3.4-1.8h-4.6c-2.3,0-4.2,1.9-4.2,4.2c0,1.4,0.7,2.6,1.8,3.4v7.9c-1.1,0.2-2.1,0.4-3.2,0.7c-2-1.6-4.9-1.3-6.5,0.7c-1.6,2-1.3,4.9,0.7,6.5C34.6,28,35.7,28.3,36.8,28.3z M54,25.5c-2.1,0.5-4.3,0.8-6.5,0.7c-2.2,0-4.3-0.2-6.5-0.7c0.5-1.1,0.5-2.4,0-3.6c4.2-1,8.7-1,12.9,0C53.5,23.1,53.5,24.3,54,25.5z M58.3,21.2c1.4,0,2.5,1.1,2.5,2.5s-1.1,2.5-2.5,2.5s-2.5-1.1-2.5-2.5C55.8,22.3,56.9,21.2,58.3,21.2L58.3,21.2z M45.2,6h4.5c1.1,0,2,0.9,2,2.1s-0.9,2.1-2,2.1h-4.5c-1.1,0-2-0.9-2-2.1S44.1,6,45.2,6z M45,12.2h0.2h4.5H50v6.9c-0.8-0.1-1.7-0.1-2.5-0.1s-1.7,0-2.5,0.1L45,12.2z M36.8,21.2c1.4,0,2.5,1.1,2.5,2.5s-1.1,2.5-2.5,2.5s-2.5-1.1-2.5-2.5C34.2,22.3,35.4,21.2,36.8,21.2C36.8,21.2,36.8,21.2,36.8,21.2L36.8,21.2z M41.1,75.4l0,5.5l0.1,0.5l5.4,9h1.8l5.4-9L54,81l0-5.5h-2.1l0,5.2l-3.2,5.4v-7.3h-2.1v7.3l-3.2-5.4l0-5.2H41.1z M47.1,75.4h0.8c10.2-4.1,18.6-8.3,18.6-18.9V38.9l-1.3-1c-1.2,0.3-2.5,0.4-3.7,0.4c-5.1,0-9.9-2.3-13.1-6.3h-1.7c-4,5-10.6,7.3-16.8,5.8l-1.3,1v17.7C28.5,67.1,37,71.3,47.1,75.4z M33.8,65.2L46.4,61v3.7l-9.6,3.2C35.7,67.1,34.7,66.2,33.8,65.2z M30.7,48.3l15.8-5.2v3.7L30.7,52V48.3z M46.4,72.8c-2.5-1-4.9-2.1-7.3-3.4l7.3-2.4L46.4,72.8z M30.7,56.5v-2.2L46.4,49v3.7L30.8,58C30.7,57.5,30.7,57,30.7,56.5z M46.4,55v3.7l-14,4.7c-0.6-1-1.1-2.1-1.4-3.3L46.4,55z M61.4,40.4c1,0,1.9-0.1,2.9-0.2v16.4c0,8.8-6.7,12.6-15.8,16.3V35.3C52.1,38.6,56.7,40.4,61.4,40.4L61.4,40.4z M33.6,40.4c4.8,0,9.4-1.8,12.8-5v5.5l-15.8,5.2v-6C31.6,40.3,32.6,40.3,33.6,40.4L33.6,40.4z M24.2,92.8l1.1-1.1v-5.4l-1.1-1.1h-0.7v-9.7l-1.7-0.8l-3.9,2.9l-2.6-4.4h-1.8l-2.8,4.4L7,74.8l-1.7,0.8v9.7H4.6l-1.1,1.1v5.4l1.1,1.1H24.2z M23.2,90.6H5.6v-3.2h17.6V90.6z M7.4,77.8l2.9,2.2l1.6-0.3l2.5-3.9l2.3,3.9l1.5,0.3l3.1-2.3v7.6h-6v-2.5h-2.1v2.5H7.4V77.8z M71.5,20.1l1.7,0.9l7.5-5l7.5,5l1.7-0.9V6.9h2.5V4.7h-2.5V4l-1.1-1.1H72.6L71.5,4v0.7H69v2.1h2.5V20.1z M73.6,5.1h14v13l-6.4-4.3H80l-6.4,4.3L73.6,5.1z M91.1,80.3c0-3.2-2.6-5.8-5.8-5.8c-1.9,0-3.6,0.9-4.7,2.5c-1.8-2.7-5.3-3.4-8-1.7s-3.4,5.3-1.7,8c0.3,0.5,0.7,0.9,1.1,1.2l7.9,7.9h1.5l7.9-7.9C90.5,83.5,91.1,81.9,91.1,80.3z M87.8,83l-7.2,7.2L73.4,83c-0.8-0.7-1.2-1.7-1.2-2.7c0-2,1.6-3.7,3.7-3.7c2,0,3.7,1.6,3.7,3.7h2.1c0-2,1.6-3.7,3.6-3.7c2,0,3.7,1.6,3.7,3.6C89,81.3,88.6,82.3,87.8,83L87.8,83z M13.6,20.8h1.6L25,10.1l0.1-1.3l-3.6-5.4l-0.9-0.5H8.1L7.2,3.4L3.7,8.8l0.1,1.3L13.6,20.8z M22.2,8.3h-2.8l-2.2-3.2h2.8L22.2,8.3z M8.7,5.1h6l2.2,3.2h-5.5v2.1h10.5l-7.4,8.1L7,10.4h1.7V8.3H6.5L8.7,5.1z"/>
</svg>';
}
add_shortcode('patrimonial_icon3','short_patrimonial_icon3');

function short_patrimonial_icon4() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#FFA661;}</style>
<circle class="color_circle" cx="47.2" cy="48.1" r="44.8"/>
<path d="M16,68.4c-2.6,0-4.7,2-4.7,4.6c0,0,0,0,0,0v9l1.1,1.1h7.2l1.1-1.1v-9C20.6,70.5,18.5,68.4,16,68.4z M18.5,80.9h-5V73c0.2-1.4,1.4-2.4,2.8-2.2c1.1,0.1,2,1,2.2,2.2V80.9z M17.6,31.9v10.7l1.1,1.1h7.2l1.1-1.1V31.9c-0.1-2.6-2.3-4.5-4.9-4.4C19.6,27.5,17.7,29.5,17.6,31.9z M24.7,31.9v9.7h-5v-9.7c0.2-1.4,1.4-2.4,2.8-2.2C23.7,29.8,24.6,30.7,24.7,31.9z M37.2,10.4c0,1.6,0.6,3.1,1.7,4.3l0.8,0.4h19.1l0.9-0.5c2.3-3.9,0.9-9-3-11.3c-1.2-0.7-2.7-1.1-4.1-1.1c-2.2,0-4.4,0.9-5.9,2.5c-3.1-1.7-7-0.5-8.7,2.7C37.5,8.3,37.3,9.3,37.2,10.4z M43.7,6.1c1.5,0,2.9,0.7,3.6,2l1.8-1.1c-0.2-0.3-0.4-0.6-0.7-0.9c2.4-2.3,6.3-2.3,8.6,0.2c1.1,1.1,1.7,2.6,1.7,4.2c0,0.9-0.2,1.7-0.5,2.5h-18c-1.4-1.9-1-4.6,0.9-6C41.9,6.4,42.8,6.1,43.7,6.1L43.7,6.1z M72.3,72.9c-5.5,0-10,4.5-10,10v9.8h2.1v-9.8c-0.2-4.4,3.2-8,7.6-8.2c4.4-0.2,8,3.2,8.2,7.6c0,0.2,0,0.4,0,0.6v9.8h2.1v-9.8C82.4,77.4,77.9,72.9,72.3,72.9C72.4,72.9,72.4,72.9,72.3,72.9z M85.6,82.9v9.8h2.1v-9.8c0-3.1,1.9-6,4.7-7.2l-0.8-2C87.9,75.2,85.6,78.9,85.6,82.9z M49.1,72.9c-5.5,0-10,4.5-10,10v9.8h2.1v-9.8c-0.2-4.4,3.2-8,7.6-8.2c4.4-0.2,8,3.2,8.2,7.6c0,0.2,0,0.4,0,0.6v9.8h2.1v-9.8C59.1,77.4,54.6,72.9,49.1,72.9C49.1,72.9,49.1,72.9,49.1,72.9z M87.4,53.3c0,1.4-1.1,2.5-2.5,2.5c0,0,0,0,0,0V58c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.5,1.7v-2.2c-1.4,0-2.5-1.1-2.5-2.5l0,0H87.4z M92,68.7v-2.1H73.4V58h2.5v-2.1H53.7V49h2.5v-2.1h-34V49h2.5v6.8H2.5V58h2.5v34.7h2.1V58h17.6v3.2h-4.3v2.1h4.3v29.3h2.1V54.4h4.3v-2.1h-4.3V49h24.7v3.2h-4.3v2.1h4.3v12.2H31.2v2.1h2.5v24h2.1v-24L92,68.7z M53.7,63.4h6.1v-2.1h-6.1V58h17.6v8.6H53.7V63.4z M10.4,53.9h2.1V24h19.3v21H34v-5.3l11.1-4.3c2.6-1,5.4-1.1,8.1-0.3l25.7,7.9l-5.2,2.1l0.8,2L92.4,40l-0.8-2l-9.6,3.8L75,39.7c3.8-5.9,2.1-13.8-3.8-17.6c-5.9-3.8-13.8-2.1-17.6,3.8c-1.3,2-2,4.4-2,6.8c-2.4-0.3-4.9-0.1-7.2,0.8l-10.4,4V24h2.5v-2.1H7.9V24h2.5v7.4L2.8,29l-0.6,2l8.2,2.5V53.9z M53.7,32.8c0-5.8,4.7-10.6,10.5-10.6c5.8,0,10.6,4.7,10.6,10.5c0,2.3-0.7,4.5-2.1,6.3l-19-5.8h-0.1C53.7,33,53.7,32.9,53.7,32.8z M25.8,7.9V5.7h-2.5V3.2h-2.1v2.5h-2.5v2.1h2.5v8.8L7.8,18.3L8,20.4l14.2-1.8l14.2,1.8l0.3-2.1l-13.4-1.7V7.9H25.8z M34.8,52.3h9v2.1h-9V52.3z M9.7,61.2h7.2v2.1H9.7V61.2z M63.4,61.2h5.4v2.1h-5.4V61.2z M88.4,5.3l-2.8-2.8L84.1,4l3.6,3.6h1.5L92.8,4l-1.5-1.5L88.4,5.3z M82.1,14.7l3.6-3.6l-1.5-1.5l-2.8,2.8l-2.8-2.8L77,11.1l3.6,3.6H82.1z M77,2.5l-2.8,2.8l-2.8-2.8L69.8,4l3.6,3.6h1.5L78.5,4L77,2.5z"/>
</svg>';
}
add_shortcode('patrimonial_icon4','short_patrimonial_icon4');

//**NATURALEZA
function short_naturaleza_icon1() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#5BB65F;}</style>
<path class="color_circle" d="M47.9,2.7L47.9,2.7c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0C3.2,22.8,23.2,2.7,47.9,2.7z"/>
<path d="M14.4,27.1c-7,0-12.7-5.7-12.7-12.7S7.4,1.7,14.4,1.7s12.7,5.7,12.7,12.7c0,0,0,0,0,0C27.1,21.4,21.4,27.1,14.4,27.1z M14.4,3.8C8.6,3.8,3.8,8.6,3.9,14.4S8.6,25,14.5,25c5.8,0,10.5-4.7,10.5-10.6C25,8.5,20.3,3.8,14.4,3.8C14.4,3.8,14.4,3.8,14.4,3.8z M92.2,19.9H49.5l-1-0.9c-0.1-0.7-0.2-1.3-0.2-2c0-5.5,4.5-10,10-10c0.6,0,1.2,0,1.7,0.2c2.4-5,8.4-7,13.4-4.6c2.9,1.4,4.9,4.1,5.4,7.3c4.9-2.5,10.9-0.5,13.4,4.4c0.7,1.4,1.1,3,1.1,4.6L92.2,19.9z M50.4,17.8h40.7c-0.6-4.3-4.6-7.3-8.9-6.7c-1.3,0.2-2.6,0.7-3.7,1.5l-1.7-0.9l0,0c0-4.4-3.5-7.9-7.9-7.9c-3.2,0-6.2,2-7.3,5l-1.3,0.6c-0.7-0.2-1.4-0.3-2.1-0.3c-4.4,0-7.9,3.5-7.9,7.9C50.3,17.3,50.4,17.5,50.4,17.8z M85.8,35.7l-1.5-1.5l3.6-3.6h1.5l3.6,3.6l-1.5,1.5l-2.8-2.8L85.8,35.7z M36,7.1l-3.6-3.6L33.9,2l2.8,2.8L39.6,2l1.5,1.5l-3.6,3.6H36z M23.2,79.7h2.1v7.2h-2.1V79.7z M28.5,85.1h2.1v7.2h-2.1V85.1z M93,80.8l-7.2-7.2h-1.5l-7.2,7.2l-0.3,0.8v10.7l1.1,1.1h14.3l1.1-1.1V81.5L93,80.8z M85.1,75.9l4.6,4.6h-9.2L85.1,75.9z M86.2,91.2v-4.3H84v4.3h-5v-8.6h12.2v8.6H86.2z M22.2,91.2c-1.3,0-2.3-1-2.3-2.3c0,0,0,0,0,0v-9.2c0-2.3-1-4.5-2.7-6.1h5.2c3.4,0,6.1,2.7,6.1,6.1v1.8h2.1v-1.8c0-4.5-3.7-8.2-8.2-8.2H2.8v2.1h9c3.4,0,6.1,2.7,6.1,6.1v9.2c0,2.4,2,4.4,4.4,4.4h50.3v-2.1H22.2z M91.5,50.3L76.1,65.7l-6.4-6.4h-1.5l-9.1,9.1l-1.6-6.7v-10l14.4-8.6c2.1-1.2,2.9-3.8,1.7-6s-3.8-2.9-6-1.7L52.4,43l1,1.9l15.3-7.6c1.1-0.6,2.5-0.2,3.1,0.9s0.2,2.5-0.9,3.1l-14.9,8.9l-0.5,0.9v9.6h-5v-15h-2.2v15h-5v-9.6l-0.5-0.9l-14.9-8.9c-0.5-0.3-0.9-0.8-1-1.5c-0.3-1.2,0.5-2.4,1.7-2.7c0.5-0.1,1,0,1.5,0.2l15.3,7.6l1-1.9l-15.3-7.6c-2-1-4.4-0.4-5.6,1.5c-1.3,2-0.8,4.8,1.2,6.1c0.1,0,0.1,0.1,0.2,0.1l14.4,8.6v10l-1.6,6.7L16,45h-1.5L2,57.5L3.5,59l6.4-6.4l4.6,4.6H16l4.6-4.6l18.3,18.2l-5,21.2l2.1,0.5l7-29.6h12.6l7,29.6l2-0.5l-5-21.2l9.3-9.3l5.7,5.7l-6.4,6.4l1.5,1.5L93,51.8L91.5,50.3z M15.3,54.9l-3.9-3.9l3.9-3.9l3.9,3.9L15.3,54.9z M48.3,68.6l-9,23.3l2,0.8l8-20.7l8,20.7l2-0.8l-9-23.3H48.3z M49.3,41.4c3.6,0,6.4-2.9,6.4-6.4s-2.9-6.4-6.4-6.4c-3.6,0-6.4,2.9-6.4,6.4c0,0,0,0,0,0C42.9,38.6,45.7,41.4,49.3,41.4z M49.3,30.7c2.4,0,4.3,1.9,4.3,4.3s-1.9,4.3-4.3,4.3c-2.4,0-4.3-1.9-4.3-4.3c0,0,0,0,0,0C45,32.6,46.9,30.7,49.3,30.7z"/>
</svg>';
}
add_shortcode('naturaleza_icon1','short_naturaleza_icon1');

function short_naturaleza_icon2() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#5BB65F;}</style>
<path class="color_circle" d="M47.7,3.3L47.7,3.3c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8h0c-24.7,0-44.8-20-44.8-44.8l0,0C2.9,23.3,22.9,3.3,47.7,3.3z"/>
<path d="M88.8,16.1L86,13.3l-1.5,1.5l3.6,3.6h1.5l3.6-3.6l-1.5-1.5L88.8,16.1z M89.6,7.6L93.2,4l-1.5-1.5l-2.8,2.8L86,2.5L84.5,4l3.6,3.6H89.6z M80.6,13l3.6-3.6l-1.5-1.5l-2.8,2.8l-2.8-2.8l-1.5,1.5l3.6,3.6H80.6z M50.4,27.6h19.1l0.8-0.4c2.4-2.6,2.2-6.7-0.5-9.1c-1.2-1.1-2.7-1.7-4.3-1.7c-1,0-2.1,0.3-3,0.8c-1.5-1.6-3.7-2.5-5.9-2.5c-4.6,0-8.2,3.7-8.2,8.3c0,1.4,0.4,2.9,1.1,4.1L50.4,27.6z M56.6,16.9c1.6,0,3.1,0.6,4.2,1.7c-0.3,0.3-0.5,0.6-0.7,0.9l1.8,1.1c1.1-2.1,3.8-2.9,5.8-1.7c2.1,1.1,2.9,3.8,1.7,5.8c-0.1,0.3-0.3,0.5-0.5,0.7H51c-1.4-3.1,0-6.7,3.1-8.1C54.8,17.1,55.7,16.9,56.6,16.9L56.6,16.9z M53,47.4c3.6,0,6.4-2.9,6.4-6.4c0-3.6-2.9-6.4-6.4-6.4c-3.6,0-6.4,2.9-6.4,6.4c0,0,0,0,0,0C46.6,44.5,49.5,47.4,53,47.4z M53,36.6c2.4,0,4.3,1.9,4.3,4.3s-1.9,4.3-4.3,4.3s-4.3-1.9-4.3-4.3c0,0,0,0,0,0C48.7,38.6,50.6,36.6,53,36.6z M80,56.1l4-2.3c2.5-1.4,5.4-1.7,8.1-0.8l0.7-2C89.6,50,86,50.3,82.9,52L82,52.5l-3.2-2.5c-5.2-4-12.5-4-17.8,0l-2.8,2.1c-3.1-0.3-7-1.2-9.7-4.8c-1-1.3-2.6-2.1-4.3-2.2c-1.7,0-3.3,0.7-4.4,2.1c-1.2,1.5-2.2,3-3,4.7l-7.2-5.6c-5.2-4.1-12.5-4.1-17.8,0L2.2,54l1.3,1.7l9.7-7.5c4.5-3.4,10.7-3.4,15.1,0l7.6,5.9c-0.9,2.1-1.5,4.2-1.9,6.4l0.4,0.9h-2v2.1H35l-2.5,4.1c-6-2.5-12.9,0.4-15.4,6.4c-1.2,2.8-1.2,5.9-0.1,8.8H2.9v2.1h15.2c3.6,5.5,10.9,7,16.3,3.4c1.4-0.9,2.5-2.1,3.4-3.4h11.6v-2.1H39c0.4-1,0.7-2.1,0.8-3.3h2.4c0.1,0.2,0.2,0.4,0.4,0.5c1.4,1.6,3.8,1.7,5.4,0.2c0.5-0.4,0.9-1,1.1-1.7l13-13.2l1.3,2.8c-5.1,2.9-7.3,9.1-5.1,14.6H53v2.1h6.2c3.6,5.5,10.9,7,16.3,3.4c1.4-0.9,2.6-2.1,3.4-3.4h13.4v-2.1H80.1c2.4-6.1-0.6-12.9-6.6-15.3c-2.6-1-5.6-1.1-8.2-0.2l-3.8-8.3c0.1-0.1,0.3-0.2,0.4-0.3c1.5-1.5,1.5-3.8,0.1-5.3c-0.3-0.3-0.6-0.5-0.9-0.7l1.3-1c4.5-3.5,10.7-3.5,15.1,0l2.5,1.9l-1,0.6L80,56.1z M69.1,68.8c5.4,0,9.7,4.4,9.7,9.7c0,5.4-4.4,9.7-9.7,9.7c-5.4,0-9.7-4.4-9.7-9.7c0-3.4,1.8-6.6,4.7-8.3l4,8.8l2-0.9l-4-8.8C67.1,68.9,68.1,68.8,69.1,68.8z M42.4,71l-3.2-6.3l3.7,2.5L42.4,71z M36.8,64.6l5.1,10.1l-0.3,2.4c0,0.1,0,0.2,0,0.3h-1.8c-0.3-3.6-2.3-6.9-5.3-8.8L36.8,64.6z M37.6,77.4H29l4.3-7C35.7,72,37.2,74.6,37.6,77.4L37.6,77.4z M28,88.1c-5.3,0-9.7-4.3-9.7-9.7s4.3-9.7,9.7-9.7c0,0,0,0,0,0c1.2,0,2.3,0.2,3.4,0.6l-5.2,8.5l0.9,1.6h10.5C37,84.4,32.9,88.1,28,88.1z M51.1,63.4H61L49.9,74.6l1.7-7.4C51.9,66,51.7,64.6,51.1,63.4z M60.2,61.3h-11L42,56.1l-1.3,1.7l7.5,5.4c1.1,0.8,1.6,2.2,1.3,3.5l-2.5,11c-0.2,0.9-1,1.5-1.9,1.4c-0.4-0.1-0.7-0.3-1-0.5c-0.3-0.3-0.5-0.8-0.4-1.3l1.5-10.5l-0.5-1l-6.5-4.5l0,0l0,0L36.3,60c0.4-2.2,1.1-4.4,2.1-6.4l0.2-0.3l-0.1,0c0.8-1.7,1.8-3.2,3-4.7c1.1-1.5,3.2-1.7,4.7-0.6c0.2,0.2,0.4,0.4,0.6,0.6c2.7,3.6,6.8,5.5,12.6,5.7c0.9,0.1,1.5,0.8,1.5,1.7c0,0.4-0.2,0.9-0.5,1.2c-0.3,0.3-0.8,0.5-1.2,0.5c-5.7-0.2-10.1-1.1-14.4-5l-1.4,1.6c4.8,4.4,9.8,5.4,15.8,5.6c0.1,0,0.2,0,0.3,0L60.2,61.3z M7.2,23.9c0,0.1,0,0.1,0,0.2c0,1,0.2,2,0.6,2.9l1,0.7h27.4l1-0.6l0,0l0.1-0.2c0.1-0.2,0.2-0.5,0.3-0.7s0.1-0.3,0.1-0.4s0.1-0.4,0.2-0.6s0-0.3,0.1-0.4s0.1-0.4,0.1-0.5s0-0.3,0-0.5s0-0.2,0-0.4l0,0c0-0.1,0-0.2,0-0.4c0-5.5-4.4-10-9.9-10c-0.9,0-1.7,0.1-2.5,0.3C25.1,6.8,19.5,1.9,13,2.2S1.5,8.2,1.9,14.7C2.1,18.4,4.1,21.9,7.2,23.9z M35.8,23c0,0.1,0,0.3,0,0.4c0,0.3,0,0.6-0.1,0.9v0.2c-0.1,0.3-0.2,0.7-0.3,1l0,0h-26c-0.1-0.5-0.2-0.9-0.2-1.4c0-3,2.5-5.4,5.4-5.4c1.3,0,2.6,0.5,3.6,1.4c-0.1,0.3-0.2,0.6-0.2,0.9l2.1,0.4c0.9-4.3,5-7,9.3-6.1C33.2,16,35.9,19.2,35.8,23z M13.6,4.3c5.3,0,9.7,4.3,9.7,9.7v0.1c-1.7,0.9-3.1,2.2-4,3.9c-3.3-2.5-8.1-1.8-10.5,1.5c-0.5,0.6-0.8,1.3-1.1,2.1C3.5,18.3,2.7,12.2,6.1,8C7.9,5.7,10.7,4.4,13.6,4.3L13.6,4.3z M19,63.4v-2.1c-1.4,0-2.5-1.1-2.5-2.5l0,0h-2.1c0,1.4-1.1,2.5-2.5,2.5v2.1c1.4,0,2.7-0.6,3.6-1.7C16.3,62.8,17.6,63.4,19,63.4z"/></svg>';
}
add_shortcode('naturaleza_icon2','short_naturaleza_icon2');

function short_naturaleza_icon3() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#5BB65F;}</style>
<path class="color_circle" d="M47.5,2.7L47.5,2.7c24.7,0,44.8,20,44.8,44.8v0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8v0C2.8,22.8,22.8,2.7,47.5,2.7z"/>
<path d="M50,19.6l3.6-3.6l-1.5-1.5l-2.8,2.8l-2.8-2.8L45,16l3.6,3.6H50z M41.1,14.2l3.6-3.6l-1.5-1.5L40.3,12l-2.8-2.8L36,10.7l3.6,3.6H41.1z M62.5,13.8c0,0.1,0,0.2,0,0.4s0,0.3,0,0.4c0,0.2,0.1,0.4,0.1,0.6c0,0.1,0,0.3,0.1,0.4s0.1,0.4,0.2,0.6s0.1,0.2,0.1,0.4c0.1,0.3,0.2,0.5,0.3,0.8c0,0,0,0.1,0,0.1l0,0l1,0.6h27.4l1-0.7c0.4-0.9,0.6-1.9,0.6-2.9c0-4.2-3.4-7.5-7.6-7.5c-1.6,0-3.1,0.5-4.4,1.5C78.5,3.7,72.4,2,67.6,4.7c-3.2,1.8-5.1,5.1-5.1,8.7C62.6,13.6,62.6,13.7,62.5,13.8z M72.6,5.6c3.7,0,7,2.6,7.7,6.3l2.1-0.4c-0.1-0.3-0.1-0.6-0.2-0.9c2.2-2,5.7-1.8,7.6,0.5c0.9,1,1.3,2.2,1.4,3.5c0,0.5,0,1-0.1,1.4H65.1c0,0,0,0,0-0.1c-0.1-0.3-0.2-0.7-0.3-1v-0.2c0-0.3-0.1-0.6-0.1-0.9s0-0.3,0-0.4C64.8,9.1,68.2,5.6,72.6,5.6L72.6,5.6z M69.7,70.8c0,1.4-1.1,2.5-2.5,2.5v2.1c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1c-1.4,0-2.5-1.1-2.5-2.5H69.7z M11,83.4H8.9c0,1.4-1.1,2.5-2.5,2.5V88c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1C12.1,85.9,11,84.8,11,83.4z M82.2,88.7c0,1.4-1.1,2.5-2.5,2.5v2.1c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1c-1.4,0-2.5-1.1-2.5-2.5H82.2z M12.7,23.6c6.1,0,11-4.9,11-11s-4.9-11-11-11s-11,4.9-11,11S6.6,23.6,12.7,23.6z M12.7,3.8c4.9,0,8.8,4,8.8,8.9c0,4.9-4,8.8-8.9,8.8c-4.9,0-8.8-4-8.8-8.9c0,0,0,0,0,0C3.8,7.8,7.8,3.8,12.7,3.8z M24.1,43.9c-0.8-0.1-1.6,0.2-2.2,0.8l-2.4,4.1c-1.3,2.2-0.6,5,1.6,6.3c0.1,0.1,0.3,0.2,0.4,0.2l3.4,1.6c1.2,0.6,2.5,0.6,3.7,0.1c1.2-0.5,2.1-1.5,2.5-2.7l4.4-12.6c0.5-1.3-0.2-2.8-1.6-3.2c0,0,0,0-0.1,0l-2.4-0.8c-1.7-0.5-3.5,0.2-4.4,1.6l-2.8,4.6L24.1,43.9z M26.1,45.1l2.9-4.7c0.4-0.6,1.2-0.9,1.9-0.7l2.3,0.7c0.1,0,0.2,0.1,0.2,0.2c0,0.1,0,0.2,0,0.3l-4.3,12.6c-0.5,1.3-1.9,2-3.2,1.5c-0.1,0-0.1-0.1-0.2-0.1l-3.4-1.6c-0.6-0.3-1.1-0.8-1.3-1.5c-0.2-0.7-0.1-1.4,0.2-2l2.3-3.8c0.1,0,0.3,0,0.4,0C24.8,46.1,25.6,45.8,26.1,45.1L26.1,45.1z M48.5,26.8c-3.6,0-6.4,2.9-6.4,6.5s2.9,6.4,6.5,6.4c3.6,0,6.4-2.9,6.4-6.4C55,29.7,52.1,26.8,48.5,26.8C48.6,26.8,48.6,26.8,48.5,26.8z M48.5,37.6c-2.4,0-4.3-1.9-4.3-4.3s1.9-4.3,4.3-4.3c2.4,0,4.3,1.9,4.3,4.3C52.9,35.6,50.9,37.6,48.5,37.6C48.6,37.6,48.6,37.6,48.5,37.6z M46.7,41.5c-1-1.3-2.6-2.1-4.3-2.2h-0.1c-1.7,0-3.3,0.8-4.3,2.1c-2,2.5-5.9,16.4-6.4,18.6l-0.8,6.4l-0.8,1.4l-7.6,1.5c0.1-0.4,0.1-0.8,0.1-1.2c0-4.9-3.3-9.3-8.1-10.5c0-0.4,0.1-0.7,0.1-1.1c0-4.5-3.7-8.2-8.2-8.2c-1.4,0-2.9,0.4-4.1,1.1l1.1,1.8c2.9-1.7,6.6-0.7,8.3,2.2c0.5,0.9,0.8,2,0.8,3c0,0.3,0,0.5,0,0.8c-0.2,0-0.4,0-0.7,0c-0.7,0-1.3,0.1-2,0.2l0.4,2.1c0.5-0.1,1.1-0.1,1.6-0.1c4.8,0,8.8,3.9,8.8,8.8c0,0.6-0.1,1.1-0.2,1.7L2.5,73.3L3,75.4l25.7-5.1l-5,8.5c-1,1.8-0.3,4.1,1.5,5.1c1.7,0.9,3.8,0.4,4.9-1.2l8.6-12l0.2-0.3l1.1-4.2l2.1,2.9v11.6c-0.1,2.1,1.6,3.8,3.6,3.8c0.8,0,1.7-0.2,2.4-0.7c1-0.7,1.5-1.8,1.5-3l0.8-13.5c0-0.4,0-0.8-0.1-1.2l2.8-0.6l-0.4-2.1L49.5,64c-0.1-0.1-0.2-0.3-0.3-0.4L43.5,56l1.6-5c3.2,1.8,6.8,2.8,10.4,2.9l-2.7,30.5l2.1,0.2l2.7-30.6c2.1-0.1,3.7-1.8,3.6-3.9c-0.1-1.7-1.3-3.2-2.9-3.5l0.2-2.4l-2.1-0.2l-0.2,2.4C53.2,46,49.4,45,46.7,41.5z M59.2,50.2c0,0.9-0.7,1.6-1.6,1.6c0,0-0.1,0-0.1,0c-5.6-0.2-9.1-1.2-12.3-3.3L43.5,49l-2.2,6.9l0.1,0.8l6.2,8c0.5,0.7,0.7,1.5,0.7,2.3l-0.8,13.6c0,0.9-0.7,1.6-1.6,1.6c-0.2,0-0.4,0-0.5-0.1c-0.7-0.2-1.1-0.9-1.1-1.6v-12v-0.2l0,0l-0.1-0.2l0,0l-0.1-0.3L40.5,63l-1.9,0.3l-1.7,6.1l-8.5,11.9l0,0.1c-0.5,0.8-1.5,1-2.2,0.6c-0.8-0.5-1-1.4-0.6-2.2c0,0,0,0,0,0c7.4-12.6,7.4-12.6,7.5-12.7l0.9-6.8c0.5-2.6,4.4-15.7,5.9-17.5c0.6-0.8,1.6-1.3,2.7-1.3l0,0c1,0,2,0.5,2.6,1.3c2.8,3.6,6.9,5.5,12.6,5.8C58.5,48.6,59.2,49.3,59.2,50.2z M92.1,55.5l-13,2.6v-7.8c4.1-0.5,7.2-4,7.2-8.1H84c0,2.9-2.1,5.5-5,6v-15h-2.1v7.7c-1.9-0.5-3.2-2.2-3.2-4.2h-2.1c0,3.1,2.3,5.8,5.4,6.3v10.4c-3.9-0.5-6.8-3.9-6.8-7.8h-2.1c0,5.2,3.9,9.5,9,10v2.8l-18.2,3.6l0.4,2.1l33.3-6.6L92.1,55.5z M75.4,26.8c1.6-0.9,3.5-0.9,5.2-0.1l11.2,5.4l0.9-1.9l-11.2-5.4c-2.3-1.1-4.9-1-7.2,0.2l-17.7,9.9l1,1.9L75.4,26.8z M10.4,33.4l-8.1,3.9l0.9,1.9l8.1-3.9c1.4-0.7,3-0.7,4.5-0.2l9.7,3.8l0.8-2l-9.7-3.8C14.6,32.4,12.4,32.5,10.4,33.4z"/></svg>';
}
add_shortcode('naturaleza_icon3','short_naturaleza_icon3');

function short_naturaleza_icon4() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">	.color_circle{fill:#5BB65F;}</style>
<path class="color_circle" d="M47.5,2.7L47.5,2.7c24.7,0,44.7,20,44.7,44.8l0,0c0,24.7-20,44.8-44.8,44.8h0c-24.7,0-44.8-20-44.8-44.8l0,0
	C2.7,22.8,22.8,2.7,47.5,2.7z"/>
<path d="M79,88.7h-2.1c0,1.4-1.1,2.5-2.5,2.5c0,0,0,0,0,0v2.1c1.4,0,2.7-0.6,3.6-1.7c0.9,1.1,2.2,1.7,3.6,1.7v-2.1
	C80.1,91.2,79,90.1,79,88.7z M80.6,1.7c-7,0-12.7,5.7-12.7,12.7s5.7,12.7,12.7,12.7c7,0,12.7-5.7,12.7-12.7c0,0,0,0,0,0
	C93.3,7.4,87.6,1.7,80.6,1.7z M80.6,24.9c-5.8,0-10.6-4.7-10.6-10.6c0-5.8,4.7-10.6,10.6-10.6c5.8,0,10.6,4.7,10.6,10.6c0,0,0,0,0,0
	C91.2,20.2,86.5,25,80.6,24.9C80.6,25,80.6,25,80.6,24.9L80.6,24.9z M21.4,7.1L25,3.5L23.5,2l-2.8,2.8L17.8,2l-1.5,1.5l3.6,3.6H21.4
	z M10.7,9.2l-1.5,1.5l3.6,3.6h1.5l3.6-3.6l-1.5-1.5L13.5,12L10.7,9.2z M3.5,2L2,3.5l3.6,3.6h1.5l3.6-3.6L9.2,2L6.3,4.8L3.5,2z
	 M26.1,43.5c3.3-0.1,6.5,1.2,8.9,3.5l2.5,2.4l1.5-1.6l-2.5-2.4c-2.8-2.7-6.5-4.2-10.4-4.1c-0.4,0-0.7,0-1.1,0.1L12.4,28.8h-1.5
	L2,37.8l1.5,1.5l2.8-2.8l4.6,4.6h1.5l4.6-4.6l5.4,5.4c-2.5,0.7-4.8,2.1-6.6,3.9l-3.8,3.9L8.6,48c-1.8-0.9-3.8-1.4-5.8-1.5l-0.1,2.1
	c1.7,0.1,3.4,0.5,4.9,1.3l9,4.5l1-1.9l-3.4-1.7l3.3-3.5C19.7,44.9,22.9,43.5,26.1,43.5z M11.7,38.8L7.8,35l3.9-3.9l3.9,3.9
	L11.7,38.8z M91.6,47.5l1.3-1.7l-10.7-8.1h-1.3l-11,8.7h-13v2.1h12.7c4.4,0,7.9,3.5,7.9,7.9c0,0.4,0,0.9-0.1,1.3L75.1,65
	c-1,3.3-0.2,6.8,2.2,9.3l-3,2.6c-0.6,0-1.1-0.1-1.7-0.1c-0.3,0-0.7,0-1,0l1.8-1.8L73,73.4c-4.4-1.9-6.9-6.7-6-11.4l0.5-2.6l-2.1-0.4
	l-0.5,2.6c-1,5.2,1.4,10.4,5.9,13.1L68,77.4c-3.3,0.7-6.4,2.3-8.9,4.5c-1.9-0.9-3.9-1.4-5.9-1.4l1-8.6h10.5v-2.1H54.4l0.7-6l0,0
	c0.5-1.8,0.7-3.7,0.5-5.6l-1.4-11l0,0c0.9-1.5,1.4-3.1,1.5-4.8c3.4-0.4,6.2-2.8,7.1-6.1L62.3,35c-3-1.5-6.7-1.1-9.3,1.1
	c-0.2-0.2-0.5-0.4-0.8-0.6c0.4-1.6,1.5-1.9,3-2.4l0.2-0.1l0,0c0.9-0.3,1.8-0.5,2.7-0.5c0.3,0,0.7,0,1.2,0c1.9-0.2,3.7-1,5-2.3
	l-1.5-1.5c-0.9,0.9-2,1.4-3.2,1.6c1.5-1.7,2.6-3.8,3.2-6l-2-0.6c-1.3,4.4-3.3,6.1-5,6.9c0.2-0.5,0.4-1,0.5-1.5c0.3-1.4,0-2.8-0.9-4
	l-1.7,1.3c0.5,0.6,0.7,1.4,0.5,2.2c-0.3,1.5-1.2,2.9-2.4,3.8l0,0c-0.6,0.5-1.1,1.2-1.4,2c-0.9-0.3-1.9-0.5-2.9-0.5
	c-1,0-1.9,0.2-2.9,0.5c-0.3-0.8-0.7-1.5-1.4-2l0,0c-1.3-0.9-2.1-2.3-2.5-3.8c-0.2-0.8,0-1.6,0.5-2.2l-1.7-1.3
	c-0.9,1.1-1.2,2.6-0.9,4c0.1,0.5,0.3,1,0.5,1.5c-1.7-0.8-3.7-2.5-5-6.9l-2,0.6c0.6,2.2,1.6,4.3,3.2,6c-1.2-0.2-2.3-0.8-3.2-1.6
	l-1.5,1.5c1.3,1.3,3.1,2.2,5,2.3c0.5,0,0.9,0,1.2,0c0.9,0,1.8,0.1,2.7,0.5l0,0l0.2,0.1c1.5,0.5,2.5,0.8,3,2.4
	c-0.3,0.2-0.5,0.4-0.8,0.6c-2.6-2.2-6.3-2.6-9.3-1l-0.5,1.2c1,3.3,3.8,5.7,7.1,6.1c0.1,1.7,0.6,3.4,1.5,4.8l0,0l-1.4,11
	c-0.2,1.9-0.1,3.8,0.5,5.6l0,0l2.6,21.2c-0.4,0.4-0.8,0.9-1.2,1.4c-4.3-1.5-8.9,0.7-10.4,5c-0.1,0.2-0.2,0.5-0.2,0.7l2.1,0.5
	c0.9-3.3,4.2-5.2,7.5-4.3c0.9,0.2,1.8,0.7,2.5,1.3l1.4-1.7c-0.3-0.2-0.5-0.4-0.8-0.6c4.2-5.4,12-6.3,17.4-2c0,0,0,0,0.1,0l1.3-1.7
	c-0.3-0.2-0.6-0.4-0.9-0.7c5.6-4.4,13.3-5.2,19.7-2c-0.2,0.8-0.3,1.6-0.3,2.4h2.1c0-4.3,3.5-7.9,7.9-7.9c0.5,0,1.1,0,1.6,0.2
	l0.4-2.1c-0.7-0.1-1.3-0.2-2-0.2c-3.8,0-7.3,2.2-9,5.6c-1.4-0.7-2.9-1.2-4.4-1.5l2.5-2.2v-1.6c-2.3-1.9-3.3-5-2.4-7.9l2.3-7.3v-0.1
	c0.9-4.9-2-9.7-6.8-11.2l8.8-7L91.6,47.5z M41.5,58.4l1-7.7c1.3,2.8,4.6,4,7.4,2.7c1.2-0.6,2.2-1.5,2.7-2.7l1,7.7
	c0.7,6.4-3.2,10.3-6,11.3C44.7,68.6,40.8,64.8,41.5,58.4z M60.5,36.5c-0.9,2-2.8,3.4-5,3.7c-0.2-0.9-0.6-1.7-1.1-2.5
	C56.1,36.3,58.4,35.8,60.5,36.5L60.5,36.5z M53.6,42.1c-0.1,1.4-0.5,2.8-1.2,4c-0.2,0.3-0.3,0.6-0.5,0.9s-0.2,0.5-0.3,0.8
	c-0.9,2.2-1.8,4-4.1,4s-3.2-1.8-4.1-4c-0.1-0.3-0.2-0.6-0.3-0.8s-0.3-0.6-0.5-0.9c-0.7-1.2-1.2-2.6-1.2-4c0.1-3.4,3-6,6.3-5.8
	C50.9,36.4,53.5,39,53.6,42.1z M34.5,36.5c2.1-0.6,4.4-0.2,6.1,1.2c-0.5,0.8-0.9,1.6-1.1,2.5C37.3,39.9,35.5,38.5,34.5,36.5z
	 M47.2,71.8h0.6c1.8-0.6,3.4-1.7,4.6-3.1L51,80.6c-0.8,0.1-1.6,0.3-2.4,0.5v-7.4h-2.2V82c-0.7,0.4-1.4,0.8-2.1,1.3l-1.8-14.6
	C43.8,70.1,45.4,71.2,47.2,71.8z M11,85.7V75.3c3.1-0.5,5.3-3.2,5.3-6.3h-2.1c0,1.9-1.3,3.6-3.2,4.1v-7.7H8.8v14.9
	c-2.9-0.5-5-3.1-5-6H1.7c0,4.1,3.1,7.6,7.2,8.2v9.8H11v-4.4c5.1-0.5,9-4.8,9-10h-2.2C17.8,81.8,14.9,85.2,11,85.7z M33.2,64.7v-2.1
	c-1.4,0-2.5-1.1-2.5-2.5c0,0,0,0,0,0h-2.1c0,1.4-1.1,2.5-2.5,2.5c0,0,0,0-0.1,0v2.1c1.4,0,2.7-0.6,3.6-1.7
	C30.5,64.1,31.8,64.7,33.2,64.7z M49.3,7c-1,0-2.1,0.3-3,0.8c-1.5-1.6-3.7-2.5-5.9-2.5c-4.5,0-8.3,3.6-8.3,8.2
	c0,1.5,0.4,2.9,1.1,4.2l0.9,0.5h19.1l0.8-0.4c2.4-2.6,2.2-6.7-0.4-9.1C52.5,7.6,50.9,7,49.3,7z M52.8,16h-18
	c-0.4-0.8-0.5-1.6-0.5-2.5c0-3.4,2.7-6.1,6.1-6.1c1.6,0,3.1,0.6,4.2,1.7C44.3,9.4,44,9.7,43.8,10l1.8,1.1c1.3-2,4-2.5,6-1.1
	S54.1,14,52.8,16C52.8,16,52.8,16,52.8,16z"/>
</svg>';
}
add_shortcode('naturaleza_icon4','short_naturaleza_icon4');

function short_naturaleza_icon5() {
return '<svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"  viewBox="0 0 95 95" style="enable-background:new 0 0 95 95;" xml:space="preserve">
<style type="text/css">.color_circle{fill:#5BB65F;}</style>
<path class="color_circle" d="M47.5,2.7L47.5,2.7c24.7,0,44.8,20,44.8,44.8l0,0c0,24.7-20,44.8-44.8,44.8l0,0c-24.7,0-44.8-20-44.8-44.8l0,0 C2.8,22.8,22.8,2.7,47.5,2.7z"/>
<path d="M16,23.2h-1.5l-3.6-3.6l1.5-1.5l2.8,2.8l2.8-2.8l1.5,1.5L16,23.2z M47.8,10l-2.5-2.2L42.8,10l-1.4-1.6l3.2-2.8H46l3.2,2.8 L47.8,10z M87.9,51.8l-3.6-3.6l1.5-1.5l2.8,2.8l2.8-2.8l1.5,1.5l-3.6,3.6H87.9z M59.2,91.4l-1.5-1.5l3.6-3.6h1.5l3.6,3.6l-1.5,1.5 L62,88.5L59.2,91.4z M85.8,39.3h-1.5l-3.6-3.6l1.5-1.5l2.8,2.8l2.8-2.8l1.5,1.5L85.8,39.3z M16,7.1h-1.5l-3.6-3.6L12.4,2l2.8,2.8 L18.1,2l1.5,1.5L16,7.1z M7.1,14.2H5.6L2,10.7l1.5-1.5L6.3,12l2.8-2.8l1.5,1.5L7.1,14.2z M67.2,72.6l1.5-1.5l4.9,4.9l-1.5,1.5 L67.2,72.6z M70.9,68.5l1.5-1.5l5.5,5.5L76.4,74L70.9,68.5z M32.5,92.2h-2.1c0-6.3-5.1-11.5-11.5-11.4c-3.1,0-6,1.2-8.2,3.4 l-1.5-1.5c5.3-5.4,13.9-5.4,19.2-0.2C31,85.1,32.5,88.6,32.5,92.2L32.5,92.2z M92.2,19.9H67.2l-1.1-1.1c0-7.7,6.3-14,14-14 c2.4,0,4.7,0.6,6.8,1.8c0.2-1.5,0.7-3,1.3-4.4l1.9,1c-0.8,1.6-1.2,3.4-1.2,5.3l0,0l-1.7,0.9c-2-1.6-4.5-2.4-7.1-2.4 C74,7,68.9,11.7,68.3,17.8h24L92.2,19.9z M11.5,81.6c-0.3-2.9-2.5-5.3-5.4-6l0.5-2.1c3.7,0.9,6.5,4,7,7.8L11.5,81.6z M10.5,76.3 l-2.1-0.6c0.3-1,0.5-2.1,0.4-3.2c0-4.4-2.6-8.5-6.6-10.4l0.9-1.9c4.7,2.3,7.8,7,7.8,12.3C11,73.8,10.8,75.1,10.5,76.3z M7.3,32.1 C7,29.6,5.5,27.3,3.3,26l-1.1,1.8c1.6,0.9,2.7,2.6,3,4.4C4,32.6,3,33.2,2,34l1.4,1.6c2.2-2,5.7-1.8,7.6,0.5c0.9,1,1.4,2.3,1.4,3.6 c0,0.5-0.1,0.9-0.2,1.4H2.8v2.1H13l1-0.7c0.4-0.9,0.6-1.9,0.6-2.9C14.6,35.6,11.4,32.3,7.3,32.1z M47.5,23.2 c-9.3,0-17.9,5-22.6,13.1l1.9,1.1c6.6-11.5,21.3-15.4,32.8-8.8s15.4,21.3,8.8,32.8C61.7,72.7,47,76.7,35.5,70.1 c-3.3-1.9-6.1-4.5-8.1-7.7c6,1.7,12.5-0.5,16.2-5.5c0.6,0.2,1.2,0.4,1.8,0.6l0.6-2c-8.4-2.7-11.6-8.6-10.2-19 c6.1,2.7,11.5,6.6,16,11.5c0,0.5,0.1,1.1,0.2,1.6l2.1-0.5c-0.8-3.3,1.1-6.6,4.4-7.4c3.3-0.8,6.6,1.1,7.4,4.4 c0.8,3.3-1.1,6.6-4.4,7.4c-1,0.3-2.1,0.3-3.1,0c-0.5-0.1-1-0.3-1.5-0.6l-1.1,1.8c0.4,0.2,0.8,0.4,1.2,0.6 c-0.3,7.1-6.1,12.7-13.2,12.7c-1.1,0-2.2-0.1-3.3-0.4l-0.5,2.1c1.3,0.3,2.6,0.5,3.8,0.5c8.1,0,14.8-6.3,15.3-14.4c0.3,0,0.5,0,0.8,0 c2.5,0,4.9-1.2,6.5-3.2l1.7,1.1l1.2-1.8l-1.8-1.1c1.7-4.2-0.3-9-4.5-10.7c-4.2-1.7-9,0.3-10.7,4.5c-0.1,0.2-0.2,0.5-0.3,0.7 c-2.4-2.5-5.1-4.7-8-6.6c0.2-3.3,1.4-6.4,3.4-9c2.3,3.1,4.1,6.5,5.6,10l2-0.8c-1.7-4.2-4-8.1-6.8-11.6h-1.7 c-2.5,2.9-4.1,6.4-4.6,10.2c-2.2-1.3-4.4-2.4-6.8-3.3l-1.4,0.8c-1.8,10.6,0.7,17.4,7.7,21.1c-2.5,3.1-6.3,4.9-10.2,4.9l0,0 c-1.9,0-3.8-0.4-5.6-1.3c-0.2-0.5-0.5-1-0.7-1.6l3.5-1.6c0.1,1.1,0.3,2.1,0.9,3.1l1.9-1.1c-0.5-1.1-0.7-2.4-0.6-3.7l-1.5-1l-4.7,2.2 c-0.5-1.6-0.7-3.2-0.9-4.9l-2.1,0.2c1,14.4,13.5,25.2,27.9,24.2c14.4-1,25.2-13.5,24.2-27.9C72.6,33.7,61.2,23.1,47.5,23.2 L47.5,23.2z M23.6,47.6l-2.1-0.2c0.2-2.7,0.8-5.3,1.8-7.8l2,0.8C24.3,42.7,23.7,45.1,23.6,47.6z M16,7.1h-1.5l-3.6-3.6L12.4,2 l2.8,2.8L18.1,2l1.5,1.5L16,7.1z M7.1,14.2H5.6L2,10.7l1.5-1.5L6.3,12l2.8-2.8l1.5,1.5L7.1,14.2z M67.2,72.6l1.5-1.5l4.9,4.9 l-1.5,1.5L67.2,72.6z M70.9,68.5l1.5-1.5l5.5,5.5L76.4,74L70.9,68.5z M16,7.1h-1.5l-3.6-3.6L12.4,2l2.8,2.8L18.1,2l1.5,1.5L16,7.1z  M7.1,14.2H5.6L2,10.7l1.5-1.5L6.3,12l2.8-2.8l1.5,1.5L7.1,14.2z M67.2,72.6l1.5-1.5l4.9,4.9l-1.5,1.5L67.2,72.6z M70.9,68.5 l1.5-1.5l5.5,5.5L76.4,74L70.9,68.5z M16,7.1h-1.5l-3.6-3.6L12.4,2l2.8,2.8L18.1,2l1.5,1.5L16,7.1z M7.1,14.2H5.6L2,10.7l1.5-1.5 L6.3,12l2.8-2.8l1.5,1.5L7.1,14.2z M91.5,86.7l1.5-1.5L78.8,70.9h-1.5l-0.4,0.4l-3.7-3.7c10.2-14.2,6.9-33.9-7.2-44.1 S32,16.6,21.8,30.8S14.9,64.7,29,74.8c11.8,8.5,27.8,7.8,38.9-1.6l3.5,3.5l-0.5,0.5v1.5L85.2,93l1.5-1.5L73.2,78l4.8-4.8L91.5,86.7z  M18.1,49.3c0-16.2,13.1-29.4,29.4-29.4s29.4,13.1,29.4,29.4S63.7,78.6,47.5,78.6l0,0C31.3,78.6,18.2,65.5,18.1,49.3z M69.5,71.8 c0.8-0.8,1.6-1.6,2.3-2.5l3.5,3.5l-2.4,2.4L69.5,71.8z M16,7.1h-1.5l-3.6-3.6L12.4,2l2.8,2.8L18.1,2l1.5,1.5L16,7.1z"/>
</svg>';
}
add_shortcode('naturaleza_icon5','short_naturaleza_icon5');



//*personalizar menu*//

register_nav_menus( array(
	'page' => __( 'single page', 'wp_madrural' )
) );

// Función para crear una taxonomía EXPERIENCIAS
function crear_taxonomia_experiencias() {

    // Definimos un array para las traducciones de la taxonomía
    $etiquetas = array(
        'name' => __( 'Experiencia' ),
        'singular_name' => __( 'Experiencia' ),
        'edit_item' => __( 'Editar experiencia' ),
        'all_items' => __( 'Ver por experiencia' ),
        'add_new_item' => __( 'Agregar un nueva experiencia' ),
        'menu_name' => __( 'Experiencias' ),
    ); 	

    // Función WordPress para registrar la taxonomía
    register_taxonomy(
        'Experiencia_Mad',
        array('post'), // Tipos de Post a los que asociaremos la taxonomía
        array(
            'hierarchical' => true, // True para taxonomías del tipo "Categoría" y false para el tipo "Etiquetas"
            'labels' => $etiquetas, // La variable con las traducciones de las etiquetas
            'show_ui' => true,
            'show_admin_column' => false,
            'query_var' => true,
			'show_in_rest' => true,
			'order' => 'DSC',
            'rewrite' => array( 'slug' => 'Experiencia_Mad' ),
        )
    );

}
add_action( 'init', 'crear_taxonomia_experiencias', 0 );

//Estilos login css
add_action( 'login_enqueue_scripts', 'my_login_logo' );
function my_login_logo() {
wp_enqueue_style( 'login-custom-style', get_bloginfo('stylesheet_directory'). '/login.css', array('login') );
}



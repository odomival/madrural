<?php
/**
 * Plugin Name:       MADRURAL - Autenticación Personalizada de Gestores
 * Description:       Sistema de login independiente del wp-admin, control de acceso por territorio para la agenda de eventos, gestión de perfiles y menú flotante de usuario.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            MADRURAL
 * Text Domain:       madrural-auth
 * Domain Path:       /languages
 *
 * @package MADRURAL_Auth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MADRURAL_Auth_Plugin' ) ) {
	class MADRURAL_Auth_Plugin {
		const OPTION_PAGES = 'madrural_auth_pages';
		const SESSION_KEY  = 'madrural_auth_profile';
		const COOKIE_KEY   = 'madrural_auth_profile_token';
		const FIXED_TERRITORIO_LABELS = array(
			'Sierra Norte',
			'Sierra de Guadarrama',
			'Sierra Oeste',
			'Las Vegas & La Alcarria',
		);
		protected static $runtime_login_notice      = '';
		protected static $runtime_login_redirect_to = '';

		public static function init() {
			add_action( 'init', array( __CLASS__, 'start_session' ), 1 );
			add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
			add_action( 'init', array( __CLASS__, 'ensure_fixed_territorios_terms' ), 15 );
			add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
			add_action( 'init', array( __CLASS__, 'handle_frontend_actions' ), 20 );
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_default_pages' ) );
			add_action( 'template_redirect', array( __CLASS__, 'render_virtual_auth_pages' ), 0 );
			add_action( 'template_redirect', array( __CLASS__, 'protect_event_pages' ), 5 );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
			add_action( 'wp_footer', array( __CLASS__, 'render_floating_user_menu' ) );
			add_filter( 'body_class', array( __CLASS__, 'add_auth_body_class' ) );

			add_filter( 'madrural_eventos_user_can_manage_events', array( __CLASS__, 'filter_eventos_manage_permission' ) );
			add_filter( 'madrural_eventos_user_can_access_event', array( __CLASS__, 'filter_eventos_access_permission' ), 10, 3 );
			add_filter( 'madrural_eventos_mis_eventos_query_args', array( __CLASS__, 'filter_mis_eventos_query_args' ), 10, 2 );
			add_filter( 'madrural_eventos_frontend_save_data', array( __CLASS__, 'filter_frontend_event_save_data' ), 10, 3 );
			add_filter( 'madrural_eventos_frontend_form_territorios', array( __CLASS__, 'filter_frontend_form_territorios' ), 10, 3 );
			add_filter( 'madrural_eventos_frontend_can_add_territorios', array( __CLASS__, 'filter_frontend_can_add_territorios' ), 10, 2 );
			add_filter( 'madrural_eventos_frontend_new_territorios_raw', array( __CLASS__, 'filter_frontend_new_territorios_raw' ), 10, 2 );
			add_filter( 'madrural_eventos_login_url', array( __CLASS__, 'filter_eventos_login_url' ) );
		}

		public static function add_auth_body_class( $classes ) {
			if ( self::is_auth_front_request() ) {
				$classes[] = 'madrural-auth-page';
			}

			return $classes;
		}

		public static function is_auth_front_request() {
			if ( is_admin() ) {
				return false;
			}

			if ( self::request_matches_slug( 'acceso-gestores' ) || self::request_matches_slug( 'panel-perfiles-madrural' ) ) {
				return true;
			}

			if ( is_page() ) {
				$post = get_post( get_queried_object_id() );
				if ( $post instanceof WP_Post ) {
					$content = (string) $post->post_content;

					return has_shortcode( $content, 'madrural_auth_login' ) || has_shortcode( $content, 'madrural_auth_panel' );
				}
			}

			return false;
		}

		/**
		 * Returns true when current request belongs to madrural-eventos views.
		 *
		 * @return bool
		 */
		public static function is_eventos_front_request() {
			if ( is_admin() ) {
				return false;
			}

			if ( is_singular( 'evento' ) ) {
				return true;
			}

			$event_pages = get_option( 'madrural_eventos_front_pages', array() );
			if ( is_array( $event_pages ) && is_page() ) {
				$current_id = (int) get_queried_object_id();
				foreach ( array( 'agenda', 'mis', 'form' ) as $key ) {
					if ( isset( $event_pages[ $key ] ) && (int) $event_pages[ $key ] === $current_id ) {
						return true;
					}
				}
			}

			if ( self::request_matches_slug( 'agenda-eventos' ) || self::request_matches_slug( 'mis-eventos' ) || self::request_matches_slug( 'gestionar-evento' ) ) {
				return true;
			}

			if ( is_page() ) {
				$post = get_post( get_queried_object_id() );
				if ( $post instanceof WP_Post ) {
					$content = (string) $post->post_content;

					return has_shortcode( $content, 'madrural_eventos_list' )
						|| has_shortcode( $content, 'madrural_eventos_lista' )
						|| has_shortcode( $content, 'madrural_mis_eventos' )
						|| has_shortcode( $content, 'madrural_evento_form' )
						|| has_shortcode( $content, 'madrural_eventos_formulario' );
				}
			}

			return false;
		}

		public static function render_virtual_auth_pages() {
			if ( is_admin() || ! is_404() ) {
				return;
			}

			if ( self::request_matches_slug( 'acceso-gestores' ) ) {
				self::render_virtual_page( self::shortcode_login() );
			}

			if ( self::request_matches_slug( 'panel-perfiles-madrural' ) ) {
				self::render_virtual_page( self::shortcode_superadmin_panel() );
			}
		}

		public static function request_matches_slug( $slug ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
			$slug        = trim( (string) $slug, '/' );

			if ( '' === $path || '' === $slug ) {
				return false;
			}

			return $path === $slug || (bool) preg_match( '#/' . preg_quote( $slug, '#' ) . '$#', $path );
		}

		public static function render_virtual_page( $content ) {
			global $wp_query;

			status_header( 200 );
			nocache_headers();

			if ( $wp_query instanceof WP_Query ) {
				$wp_query->is_404 = false;
			}

			self::enqueue_assets();

			get_header();
			echo $content;
			get_footer();
			exit;
		}

		public static function activate() {
			self::install_table();
			self::ensure_fixed_territorios_terms();
			self::seed_superadmin_profile();
			self::maybe_create_default_pages( true );
		}

		public static function deactivate() {
			if ( session_status() === PHP_SESSION_ACTIVE ) {
				unset( $_SESSION[ self::SESSION_KEY ] );
			}

			self::clear_profile_cookie();
		}

		public static function load_textdomain() {
			load_plugin_textdomain( 'madrural-auth', false, dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages' );
		}

		public static function start_session() {
			if ( is_admin() ) {
				return;
			}

			if ( session_status() !== PHP_SESSION_ACTIVE && ! headers_sent() ) {
				session_start();
			}
		}

		public static function get_table_name() {
			global $wpdb;

			return $wpdb->prefix . 'madrural_perfiles';
		}

		public static function install_table() {
			global $wpdb;

			$table_name      = self::get_table_name();
			$charset_collate = $wpdb->get_charset_collate();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql = "CREATE TABLE {$table_name} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				name varchar(191) NOT NULL,
				password varchar(255) NOT NULL,
				role varchar(20) NOT NULL,
				territorio varchar(191) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				UNIQUE KEY name (name)
			) {$charset_collate};";

			dbDelta( $sql );
		}

		public static function seed_superadmin_profile() {
			global $wpdb;

			$table_name = self::get_table_name();
			$exists     = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE name = %s LIMIT 1", 'superadmin' ) );

			if ( $exists ) {
				return;
			}

			$wpdb->insert(
				$table_name,
				array(
					'name'       => 'superadmin',
					'password'   => wp_hash_password( 'k0Zqxbudolu@' ),
					'role'       => 'superadmin',
					'territorio' => '',
				),
				array( '%s', '%s', '%s', '%s' )
			);
		}

		public static function maybe_create_default_pages( $force = false ) {
			if ( ! $force && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$pages = get_option( self::OPTION_PAGES, array() );

			$definitions = array(
				'login' => array(
					'title'   => 'Acceso Gestores',
					'slug'    => 'acceso-gestores',
					'content' => '[madrural_auth_login]',
				),
				'panel' => array(
					'title'   => 'Panel de Perfiles MADRURAL',
					'slug'    => 'panel-perfiles-madrural',
					'content' => '[madrural_auth_panel]',
				),
			);

			$updated = false;

			foreach ( $definitions as $key => $definition ) {
				$existing_id = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;
				if ( $existing_id > 0 && get_post( $existing_id ) instanceof WP_Post ) {
					continue;
				}

				$existing_page = get_page_by_path( $definition['slug'] );
				if ( $existing_page instanceof WP_Post ) {
					$pages[ $key ] = (int) $existing_page->ID;
					$updated       = true;
					continue;
				}

				$page_id = wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_title'   => $definition['title'],
						'post_name'    => $definition['slug'],
						'post_content' => $definition['content'],
						'post_status'  => 'publish',
					),
					true
				);

				if ( ! is_wp_error( $page_id ) && $page_id > 0 ) {
					$pages[ $key ] = (int) $page_id;
					$updated       = true;
				}
			}

			if ( $updated || $force ) {
				update_option( self::OPTION_PAGES, $pages );
			}
		}

		public static function register_shortcodes() {
			add_shortcode( 'madrural_auth_login', array( __CLASS__, 'shortcode_login' ) );
			add_shortcode( 'madrural_auth_panel', array( __CLASS__, 'shortcode_superadmin_panel' ) );
		}

		public static function get_current_profile() {
			if ( session_status() === PHP_SESSION_ACTIVE && ! empty( $_SESSION[ self::SESSION_KEY ] ) && is_array( $_SESSION[ self::SESSION_KEY ] ) ) {
				return self::normalize_profile( $_SESSION[ self::SESSION_KEY ] );
			}

			return null;
		}

		public static function normalize_profile( $profile ) {
			if ( ! is_array( $profile ) ) {
				return null;
			}

			$territorio_raw = isset( $profile['territorio'] ) ? (string) $profile['territorio'] : '';
			$territorios    = self::parse_territorios( $territorio_raw );

			return array(
				'id'         => isset( $profile['id'] ) ? (int) $profile['id'] : 0,
				'name'       => isset( $profile['name'] ) ? sanitize_text_field( (string) $profile['name'] ) : '',
				'role'       => isset( $profile['role'] ) ? sanitize_text_field( (string) $profile['role'] ) : '',
				'territorio' => ! empty( $territorios ) ? (string) $territorios[0] : '',
				'territorios' => $territorios,
			);
		}

		public static function parse_territorios( $value ) {
			if ( is_array( $value ) ) {
				$items = $value;
			} else {
				$value = sanitize_text_field( (string) $value );
				$items = '' === $value ? array() : explode( ',', $value );
			}

			$allowed_slugs = self::get_fixed_territorio_slugs();
			$result        = array();
			foreach ( $items as $item ) {
				$item_raw = sanitize_text_field( (string) $item );
				if ( '' === $item_raw ) {
					continue;
				}

				$term_slug = '';
				if ( ctype_digit( $item_raw ) ) {
					$term = get_term( (int) $item_raw, 'territorio_evento' );
					if ( $term instanceof WP_Term ) {
						$term_slug = sanitize_title( $term->slug );
					}
				} else {
					$term_slug = sanitize_title( $item_raw );
				}

				if ( '' === $term_slug || ! in_array( $term_slug, $allowed_slugs, true ) ) {
					continue;
				}

				$result[] = $term_slug;
			}

			return array_values( array_unique( $result ) );
		}

		/**
		 * Returns fixed territorio slugs.
		 *
		 * @return array<string>
		 */
		public static function get_fixed_territorio_slugs() {
			return array_map( 'sanitize_title', self::FIXED_TERRITORIO_LABELS );
		}

		/**
		 * Ensures fixed territorio terms exist.
		 *
		 * @return void
		 */
		public static function ensure_fixed_territorios_terms() {
			if ( ! taxonomy_exists( 'territorio_evento' ) ) {
				return;
			}

			foreach ( self::FIXED_TERRITORIO_LABELS as $label ) {
				if ( ! term_exists( $label, 'territorio_evento' ) ) {
					wp_insert_term( $label, 'territorio_evento' );
				}
			}
		}

		/**
		 * Returns fixed territorio terms in nomenclator order.
		 *
		 * @return array<int,WP_Term>
		 */
		public static function get_fixed_territorio_terms() {
			$terms = array();
			foreach ( self::FIXED_TERRITORIO_LABELS as $label ) {
				$slug = sanitize_title( $label );
				$term = get_term_by( 'slug', $slug, 'territorio_evento' );
				if ( $term instanceof WP_Term ) {
					$terms[] = $term;
				}
			}

			return $terms;
		}

		/**
		 * Returns human-readable territorio names for table rendering.
		 *
		 * @param string $territorio_raw Stored territorio value.
		 * @return string
		 */
		public static function get_territorio_display_label( $territorio_raw ) {
			if ( 'global' === $territorio_raw ) {
				return 'global';
			}

			$slugs = self::parse_territorios( $territorio_raw );
			if ( empty( $slugs ) ) {
				return '';
			}

			$labels = array();
			foreach ( $slugs as $slug ) {
				$term = get_term_by( 'slug', $slug, 'territorio_evento' );
				if ( $term instanceof WP_Term && '' !== (string) $term->name ) {
					$labels[] = (string) $term->name;
				} else {
					$labels[] = ucwords( str_replace( '-', ' ', (string) $slug ) );
				}
			}

			return implode( ', ', array_unique( $labels ) );
		}

		public static function merge_with_new_territorios( $selected, $new_raw ) {
			unset( $new_raw );

			return self::parse_territorios( $selected );
		}

		public static function get_cookie_secret() {
			return wp_salt( 'auth' );
		}

		public static function persist_profile_cookie( $profile ) {
			if ( headers_sent() || ! is_array( $profile ) ) {
				return;
			}

			$payload = wp_json_encode(
				array(
					'id'         => (int) $profile['id'],
					'name'       => (string) $profile['name'],
					'role'       => (string) $profile['role'],
					'territorio' => (string) $profile['territorio'],
				)
			);
			$pagination_links = is_array( $pagination_links ) ? $pagination_links : array();
			$base_pagination_url = remove_query_arg( 'ma_paged' );
			if ( ! is_string( $base_pagination_url ) || '' === $base_pagination_url ) {
				$base_pagination_url = self::get_page_url( 'panel' );
			}
			$prev_url            = ( $current_page > 1 )
				? add_query_arg( 'ma_paged', $current_page - 1, $base_pagination_url )
				: '';
			$next_url            = ( $current_page < $total_pages )
				? add_query_arg( 'ma_paged', $current_page + 1, $base_pagination_url )
				: '';
			$pagination_links = is_array( $pagination_links ) ? $pagination_links : array();

			if ( ! is_array( $pagination_links ) || empty( $pagination_links ) ) {
				$pagination_links = array(
					'<span class="page-numbers prev disabled" aria-disabled="true">' . esc_html__( '« Anterior', 'madrural-auth' ) . '</span>',
					'<span aria-current="page" class="page-numbers current">1</span>',
					'<span class="page-numbers next disabled" aria-disabled="true">' . esc_html__( 'Siguiente »', 'madrural-auth' ) . '</span>',
				);
			}

			if ( ! is_string( $payload ) || '' === $payload ) {
				return;
			}

			$encoded = base64_encode( $payload );
			$hash    = hash_hmac( 'sha256', $encoded, self::get_cookie_secret() );
			$value   = $encoded . '.' . $hash;

			foreach ( self::get_cookie_paths() as $path ) {
				setcookie( self::COOKIE_KEY, $value, time() + DAY_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true );
			}

			$_COOKIE[ self::COOKIE_KEY ] = $value;
		}

		public static function get_cookie_paths() {
			$paths = array( COOKIEPATH, SITECOOKIEPATH, '/' );
			$home  = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );

			if ( '' !== $home ) {
				$paths[] = '/' . trim( $home, '/' ) . '/';
			}

			$paths = array_map(
				function( $path ) {
					$path = (string) $path;
					if ( '' === $path ) {
						return '/';
					}

					return '/' . trim( $path, '/' ) . '/';
				},
				$paths
			);

			return array_values( array_unique( $paths ) );
		}

		public static function read_profile_from_cookie() {
			$raw = isset( $_COOKIE[ self::COOKIE_KEY ] ) ? (string) wp_unslash( $_COOKIE[ self::COOKIE_KEY ] ) : '';
			if ( '' === $raw || false === strpos( $raw, '.' ) ) {
				return null;
			}

			$parts = explode( '.', $raw, 2 );
			if ( 2 !== count( $parts ) ) {
				return null;
			}

			$encoded = $parts[0];
			$hash    = $parts[1];
			$valid   = hash_hmac( 'sha256', $encoded, self::get_cookie_secret() );
			if ( ! hash_equals( $valid, $hash ) ) {
				return null;
			}

			$payload = base64_decode( $encoded, true );
			if ( false === $payload ) {
				return null;
			}

			$data = json_decode( $payload, true );
			if ( ! is_array( $data ) ) {
				return null;
			}

			return self::normalize_profile( $data );
		}

		public static function clear_profile_cookie() {
			if ( headers_sent() ) {
				return;
			}

			foreach ( self::get_cookie_paths() as $path ) {
				setcookie( self::COOKIE_KEY, '', time() - HOUR_IN_SECONDS, $path, COOKIE_DOMAIN, is_ssl(), true );
			}

			unset( $_COOKIE[ self::COOKIE_KEY ] );
		}

		public static function is_authenticated() {
			$profile = self::get_current_profile();

			return is_array( $profile ) && ! empty( $profile['id'] ) && ! empty( $profile['name'] );
		}

		public static function is_superadmin_profile() {
			$profile = self::get_current_profile();

			return is_array( $profile ) && 'superadmin' === $profile['role'];
		}

		public static function get_page_url( $key, $fallback = '' ) {
			$pages   = get_option( self::OPTION_PAGES, array() );
			$page_id = isset( $pages[ $key ] ) ? (int) $pages[ $key ] : 0;

			if ( $page_id > 0 ) {
				$url = get_permalink( $page_id );
				if ( is_string( $url ) && '' !== $url ) {
					return $url;
				}
			}

			if ( '' !== $fallback ) {
				return $fallback;
			}

			$slugs = array(
				'login' => 'acceso-gestores',
				'panel' => 'panel-perfiles-madrural',
			);

			if ( isset( $slugs[ $key ] ) ) {
				$page = get_page_by_path( $slugs[ $key ] );
				if ( $page instanceof WP_Post ) {
					$url = get_permalink( $page->ID );
					if ( is_string( $url ) && '' !== $url ) {
						return $url;
					}
				}

				return home_url( '/' . $slugs[ $key ] . '/' );
			}

			return home_url( '/' );
		}

		/**
		 * Renders shared header equivalent to madrural-eventos views.
		 *
		 * @param string $active Active navigation item.
		 * @return string
		 */
		public static function render_shared_header( $active = 'perfiles' ) {
			if ( class_exists( 'MADRURAL_Eventos_Plugin' ) && is_callable( array( 'MADRURAL_Eventos_Plugin', 'render_views_header' ) ) ) {
				return (string) MADRURAL_Eventos_Plugin::render_views_header( $active );
			}

			$agenda_url = home_url( '/agenda-eventos/' );
			if ( class_exists( 'MADRURAL_Eventos_Plugin' ) && is_callable( array( 'MADRURAL_Eventos_Plugin', 'get_frontend_page_url' ) ) ) {
				$agenda_url = (string) MADRURAL_Eventos_Plugin::get_frontend_page_url( 'agenda', $agenda_url );
			}

			$items = array(
				'agenda' => array(
					'label' => esc_html__( 'Agenda', 'madrural-eventos' ),
					'url'   => $agenda_url,
				),
			);

			if ( self::is_authenticated() ) {
				$items['mis'] = array(
					'label' => esc_html__( 'Gestión de Eventos', 'madrural-eventos' ),
					'url'   => self::get_mis_eventos_url(),
				);
			} else {
				$items['gestionar'] = array(
					'label' => esc_html__( 'Gestionar Eventos', 'madrural-eventos' ),
					'url'   => self::get_page_url( 'login', home_url( '/acceso-gestores/' ) ),
				);
			}

			if ( self::is_superadmin_profile() ) {
				$items['perfiles'] = array(
					'label' => esc_html__( 'Perfiles', 'madrural-eventos' ),
					'url'   => self::get_page_url( 'panel', home_url( '/panel-perfiles-madrural/' ) ),
				);
			}

			$lottie_src = trailingslashit( get_stylesheet_directory_uri() ) . 'inc/files/logo_verde.json';

			$html  = '<div class="madrural-plugin-view">';
			$html .= '<div class="madrural-plugin-header">';
			$html .= '<div class="madrural-plugin-header-inner">';
			$html .= '<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js" defer></script>';
			$html .= '<a class="madrural-plugin-brand-link" href="' . esc_url( home_url( '/' ) ) . '">';
			$html .= '<span class="wpr-lottie-animations-wrapper madrural-brand-lottie" aria-hidden="true"><lottie-player src="' . esc_url( $lottie_src ) . '" background="transparent" speed="1" autoplay></lottie-player></span>';
			$html .= '<span class="madrural-plugin-brand-copy">';
			$html .= '<strong class="madrural-plugin-brand-title">' . esc_html__( 'MADRURAL', 'madrural-eventos' ) . '</strong>';
			$html .= '<small class="madrural-plugin-brand-subtitle">' . esc_html__( 'El Madrid que no te esperas', 'madrural-eventos' ) . '</small>';
			$html .= '</span>';
			$html .= '</a>';
			$html .= '<nav class="madrural-plugin-nav" aria-label="' . esc_attr__( 'Navegación de eventos', 'madrural-eventos' ) . '">';

			if ( class_exists( 'MADRURAL_Eventos_Plugin' ) && is_callable( array( 'MADRURAL_Eventos_Plugin', 'render_language_switcher_dropdown' ) ) ) {
				$html .= (string) MADRURAL_Eventos_Plugin::render_language_switcher_dropdown();
			}

			foreach ( $items as $key => $item ) {
				$active_class = ( $active === $key ) ? ' is-active' : '';
				$html        .= '<a class="madrural-plugin-nav-link' . esc_attr( $active_class ) . '" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			}

			$html .= '</nav>';
			$html .= '</div>';
			$html .= '</div>';

			return $html;
		}

		public static function handle_frontend_actions() {
			if ( isset( $_POST['madrural_auth_action'] ) && 'login' === sanitize_text_field( wp_unslash( $_POST['madrural_auth_action'] ) ) ) {
				self::handle_login_submission();
			}

			if ( isset( $_POST['madrural_auth_action'] ) && 'save_profile' === sanitize_text_field( wp_unslash( $_POST['madrural_auth_action'] ) ) ) {
				self::handle_profile_submission();
			}

			if ( isset( $_POST['madrural_auth_action'] ) && 'delete_profile' === sanitize_text_field( wp_unslash( $_POST['madrural_auth_action'] ) ) ) {
				self::handle_profile_deletion();
			}

			if ( isset( $_GET['madrural_auth_logout'] ) ) {
				self::handle_logout_request();
			}
		}

		public static function handle_login_submission() {
			self::start_session();
			self::$runtime_login_redirect_to = '';

			if ( ! isset( $_POST['madrural_auth_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['madrural_auth_nonce'] ) ), 'madrural_auth_login' ) ) {
				self::$runtime_login_notice = 'nonce';
				return;
			}

			$name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';

			if ( '' === $name || '' === $password ) {
				self::$runtime_login_notice = 'missing';
				return;
			}

			global $wpdb;
			$table_name = self::get_table_name();
			$profile    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s LIMIT 1", $name ), ARRAY_A );

			if ( ! is_array( $profile ) || empty( $profile['password'] ) || ! wp_check_password( $password, $profile['password'] ) ) {
				self::$runtime_login_notice = 'invalid';
				return;
			}

			$_SESSION[ self::SESSION_KEY ] = array(
				'id'         => (int) $profile['id'],
				'name'       => sanitize_text_field( (string) $profile['name'] ),
				'role'       => sanitize_text_field( (string) $profile['role'] ),
				'territorio' => sanitize_text_field( (string) $profile['territorio'] ),
			);

			$redirect_to = class_exists( 'MADRURAL_Eventos_Plugin' ) && is_callable( array( 'MADRURAL_Eventos_Plugin', 'get_frontend_page_url' ) )
				? MADRURAL_Eventos_Plugin::get_frontend_page_url( 'agenda', home_url( '/agenda-eventos/' ) )
				: home_url( '/agenda-eventos/' );

			wp_safe_redirect( $redirect_to );
			exit;
		}

		public static function handle_profile_submission() {
			if ( ! self::is_superadmin_profile() ) {
				wp_die( esc_html__( 'Acceso denegado.', 'madrural-auth' ) );
			}

			if ( ! isset( $_POST['madrural_auth_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['madrural_auth_profile_nonce'] ) ), 'madrural_auth_save_profile' ) ) {
				self::redirect_to_panel( 'nonce' );
			}

			$profile_id = isset( $_POST['profile_id'] ) ? (int) $_POST['profile_id'] : 0;
			$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
			$password   = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
			$role        = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : 'admin';
			$territorios = isset( $_POST['territorios'] ) ? self::parse_territorios( wp_unslash( $_POST['territorios'] ) ) : array();
			$territorio  = '';

			if ( '' === $name ) {
				self::redirect_to_panel( 'missing_name', $profile_id );
			}

			if ( ! in_array( $role, array( 'superadmin', 'admin' ), true ) ) {
				$role = 'admin';
			}

			if ( 'superadmin' === $role ) {
				$territorio = 'global';
			} elseif ( empty( $territorios ) ) {
				self::redirect_to_panel( 'missing_territorio', $profile_id );
			} else {
				$territorio = implode( ',', $territorios );
			}

			global $wpdb;
			$table_name = self::get_table_name();
			$existing   = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE name = %s LIMIT 1", $name ) );

			if ( $existing && (int) $existing !== $profile_id ) {
				self::redirect_to_panel( 'duplicate_name', $profile_id );
			}

			$data = array(
				'name'       => $name,
				'role'       => $role,
				'territorio' => $territorio,
			);

			$formats = array( '%s', '%s', '%s' );

			if ( '' !== $password ) {
				$data['password'] = wp_hash_password( $password );
				$formats[]        = '%s';
			}

			if ( $profile_id > 0 ) {
				$wpdb->update( $table_name, $data, array( 'id' => $profile_id ), $formats, array( '%d' ) );
			} else {
				if ( '' === $password ) {
					self::redirect_to_panel( 'missing_password' );
				}

				if ( empty( $data['password'] ) ) {
					$data['password'] = wp_hash_password( $password );
					$formats[]        = '%s';
				}

				$wpdb->insert( $table_name, $data, $formats );
			}

			self::redirect_to_panel( 'saved' );
		}

		public static function handle_logout_request() {
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
			if ( '' !== $nonce && ! wp_verify_nonce( $nonce, 'madrural_auth_logout' ) ) {
				// Continue logout to avoid stale nonce blocking session close.
			}

			if ( session_status() === PHP_SESSION_ACTIVE ) {
				unset( $_SESSION[ self::SESSION_KEY ] );
				session_unset();
				session_destroy();
			}

			self::clear_profile_cookie();

			$redirect_url = home_url( '/' );
			if ( class_exists( 'MADRURAL_Eventos_Plugin' ) && is_callable( array( 'MADRURAL_Eventos_Plugin', 'get_frontend_page_url' ) ) ) {
				$redirect_url = MADRURAL_Eventos_Plugin::get_frontend_page_url( 'agenda', home_url( '/agenda-eventos/' ) );
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}

		public static function handle_profile_deletion() {
			if ( ! self::is_superadmin_profile() ) {
				wp_die( esc_html__( 'Acceso denegado.', 'madrural-auth' ) );
			}

			if ( ! isset( $_POST['madrural_auth_delete_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['madrural_auth_delete_nonce'] ) ), 'madrural_auth_delete_profile' ) ) {
				self::redirect_to_panel( 'nonce' );
			}

			$profile_id = isset( $_POST['profile_id'] ) ? (int) $_POST['profile_id'] : 0;
			if ( $profile_id <= 0 ) {
				self::redirect_to_panel( 'nonce' );
			}

			$current_profile = self::get_current_profile();
			if ( is_array( $current_profile ) && isset( $current_profile['id'] ) && (int) $current_profile['id'] === $profile_id ) {
				self::redirect_to_panel( 'cannot_delete_current' );
			}

			global $wpdb;
			$table_name = self::get_table_name();
			$wpdb->delete( $table_name, array( 'id' => $profile_id ), array( '%d' ) );

			self::redirect_to_panel( 'deleted' );
		}

		public static function protect_event_pages() {
			if ( ! is_page() ) {
				return;
			}

			$event_pages = get_option( 'madrural_eventos_front_pages', array() );
			if ( ! is_array( $event_pages ) ) {
				return;
			}

			$current_id      = (int) get_queried_object_id();
			$current_post    = get_post( $current_id );
			$restricted_keys = array( 'mis', 'form' );
			$restricted_ids  = array();

			foreach ( $restricted_keys as $key ) {
				if ( isset( $event_pages[ $key ] ) && (int) $event_pages[ $key ] > 0 ) {
					$restricted_ids[] = (int) $event_pages[ $key ];
				}
			}

			$is_restricted = in_array( $current_id, $restricted_ids, true );

			if ( ! $is_restricted && $current_post instanceof WP_Post ) {
				$content       = (string) $current_post->post_content;
				$current_slug  = (string) $current_post->post_name;
				$is_restricted = has_shortcode( $content, 'madrural_mis_eventos' ) || has_shortcode( $content, 'madrural_evento_form' ) || in_array( $current_slug, array( 'mis-eventos', 'gestionar-evento' ), true );
			}

			if ( ! $is_restricted ) {
				return;
			}

			if ( ! self::is_authenticated() ) {
				$login_url = add_query_arg( 'redirect_to', self::get_current_url(), self::get_page_url( 'login' ) );
				wp_safe_redirect( $login_url );
				exit;
			}

			if ( isset( $_GET['me_event_id'] ) ) {
				$event_id = (int) $_GET['me_event_id'];
				if ( $event_id > 0 && ! self::can_profile_access_event( $event_id ) ) {
					wp_die( esc_html__( 'Acceso Denegado', 'madrural-auth' ) );
				}
			}
		}

		public static function filter_eventos_manage_permission( $can ) {
			if ( self::is_authenticated() ) {
				return true;
			}

			return $can;
		}

		public static function filter_eventos_access_permission( $allowed, $event_id, $action ) {
			if ( ! self::is_authenticated() ) {
				return $allowed;
			}

			$profile = self::get_current_profile();
			if ( ! is_array( $profile ) ) {
				return false;
			}

			if ( 'superadmin' === $profile['role'] ) {
				return true;
			}

			return self::can_profile_access_event( $event_id );
		}

		public static function filter_mis_eventos_query_args( $args, $user_id ) {
			if ( ! self::is_authenticated() ) {
				return $args;
			}

			$profile = self::get_current_profile();
			if ( ! is_array( $profile ) ) {
				return $args;
			}

			unset( $args['author'] );

			$territorios = isset( $profile['territorios'] ) && is_array( $profile['territorios'] ) ? self::parse_territorios( $profile['territorios'] ) : array();

			if ( 'admin' === $profile['role'] && ! empty( $territorios ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => 'territorio_evento',
						'field'    => 'slug',
						'terms'    => $territorios,
					),
				);
			}

			return $args;
		}

		public static function filter_frontend_event_save_data( $data, $post_id, $event_id ) {
			if ( ! self::is_authenticated() ) {
				return $data;
			}

			$profile = self::get_current_profile();
			if ( ! is_array( $profile ) || 'admin' !== $profile['role'] ) {
				return $data;
			}

			$territorios = isset( $profile['territorios'] ) && is_array( $profile['territorios'] ) ? self::parse_territorios( $profile['territorios'] ) : array();
			if ( empty( $territorios ) ) {
				return $data;
			}

			$submitted = array();
			if ( isset( $data['territorios'] ) && is_array( $data['territorios'] ) ) {
				foreach ( $data['territorios'] as $raw_term ) {
					$term = get_term_by( is_numeric( $raw_term ) ? 'id' : 'slug', is_numeric( $raw_term ) ? (int) $raw_term : sanitize_title( (string) $raw_term ), 'territorio_evento' );
					if ( $term instanceof WP_Term ) {
						$submitted[] = $term->slug;
					}
				}
			}

			$submitted = array_values( array_unique( $submitted ) );
			$allowed_selected = array_values( array_intersect( $submitted, $territorios ) );

			$locked_terms = array();

			if ( $event_id > 0 ) {
				$existing_terms = wp_get_post_terms( (int) $event_id, 'territorio_evento', array( 'fields' => 'slugs' ) );
				$existing_terms = is_wp_error( $existing_terms ) ? array() : array_map( 'sanitize_title', (array) $existing_terms );
				$locked_terms   = array_values( array_diff( $existing_terms, $territorios ) );

				$locked_ids_raw = isset( $_POST['madrural_locked_territorios'] ) ? sanitize_text_field( wp_unslash( $_POST['madrural_locked_territorios'] ) ) : '';
				if ( '' !== $locked_ids_raw ) {
					$locked_ids = array_filter( array_map( 'absint', explode( ',', $locked_ids_raw ) ) );
					foreach ( $locked_ids as $locked_id ) {
						$term = get_term( $locked_id, 'territorio_evento' );
						if ( $term instanceof WP_Term ) {
							$locked_terms[] = $term->slug;
						}
					}
					$locked_terms = array_values( array_unique( array_map( 'sanitize_title', $locked_terms ) ) );
				}

				$allowed_selected = array_values( array_unique( array_merge( $locked_terms, $allowed_selected ) ) );
			}

			if ( empty( $allowed_selected ) && $event_id <= 0 ) {
				$allowed_selected = array( $territorios[0] );
			}

			$data['territorios'] = $allowed_selected;

			return $data;
		}

		public static function filter_frontend_form_territorios( $terms, $event_id, $post ) {
			$profile = self::get_current_profile();
			if ( ! is_array( $profile ) || 'admin' !== $profile['role'] || is_wp_error( $terms ) || ! is_array( $terms ) ) {
				return $terms;
			}

			$allowed = isset( $profile['territorios'] ) && is_array( $profile['territorios'] ) ? self::parse_territorios( $profile['territorios'] ) : array();
			if ( empty( $allowed ) ) {
				return array();
			}

			return array_values(
				array_filter(
					$terms,
					function( $term ) use ( $allowed ) {
						return $term instanceof WP_Term && in_array( $term->slug, $allowed, true );
					}
				)
			);
		}

		public static function filter_frontend_can_add_territorios( $can_add, $event_id ) {
			$profile = self::get_current_profile();
			if ( is_array( $profile ) && 'admin' === $profile['role'] ) {
				return false;
			}

			return $can_add;
		}

		public static function filter_frontend_new_territorios_raw( $new_territorios_raw, $event_id ) {
			$profile = self::get_current_profile();
			if ( ! is_array( $profile ) ) {
				return $new_territorios_raw;
			}

			if ( 'admin' === $profile['role'] ) {
				return '';
			}

			return $new_territorios_raw;
		}

		public static function filter_eventos_login_url( $url ) {
			$login_url = self::get_page_url( 'login' );

			return '' !== $login_url ? $login_url : $url;
		}

		public static function can_profile_access_event( $event_id ) {
			$profile = self::get_current_profile();
			if ( ! is_array( $profile ) ) {
				return false;
			}

			if ( 'superadmin' === $profile['role'] ) {
				return true;
			}

			$territorios = isset( $profile['territorios'] ) && is_array( $profile['territorios'] ) ? self::parse_territorios( $profile['territorios'] ) : array();
			if ( empty( $territorios ) ) {
				return false;
			}

			$terms = wp_get_post_terms( (int) $event_id, 'territorio_evento' );
			if ( is_wp_error( $terms ) || empty( $terms ) ) {
				return false;
			}

			foreach ( $terms as $term ) {
				if ( in_array( $term->slug, $territorios, true ) ) {
					return true;
				}
			}

			return false;
		}

		public static function shortcode_login() {
			if ( self::is_authenticated() ) {
				$target = self::is_superadmin_profile() ? self::get_page_url( 'panel' ) : self::get_mis_eventos_url();

				if ( ! headers_sent() ) {
					wp_safe_redirect( $target );
					exit;
				}

				return '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $target ) ) . ');</script><noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $target ) . '"></noscript>';
			}

			$notice_map = array(
				'invalid' => esc_html__( 'Credenciales inválidas. Revisa tus datos.', 'madrural-auth' ),
				'missing' => esc_html__( 'Debes indicar nombre y contraseña.', 'madrural-auth' ),
				'nonce'   => esc_html__( 'No se pudo validar la solicitud.', 'madrural-auth' ),
			);

			$notice = isset( $_GET['ma_notice'] ) ? sanitize_key( wp_unslash( $_GET['ma_notice'] ) ) : '';
			if ( '' === $notice && '' !== self::$runtime_login_notice ) {
				$notice = sanitize_key( self::$runtime_login_notice );
			}
			$msg    = isset( $notice_map[ $notice ] ) ? $notice_map[ $notice ] : '';

			$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
			if ( '' === $redirect_to && '' !== self::$runtime_login_redirect_to ) {
				$redirect_to = self::$runtime_login_redirect_to;
			}

			$lottie_src = trailingslashit( get_stylesheet_directory_uri() ) . 'inc/files/logo_verde.json';

			ob_start();
			?>
			<div class="madrural-auth-wrap">
				<div class="madrural-auth-brand">
					<a class="madrural-auth-brand-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js" defer></script>
						<span class="wpr-lottie-animations-wrapper madrural-auth-lottie" aria-hidden="true">
							<lottie-player src="<?php echo esc_url( $lottie_src ); ?>" background="transparent" speed="1" autoplay></lottie-player>
						</span>
						<span class="madrural-auth-brand-copy">
							<strong><?php echo esc_html__( 'MADRURAL', 'madrural-auth' ); ?></strong>
							<small><?php echo esc_html__( 'El Madrid que no te esperas', 'madrural-auth' ); ?></small>
						</span>
					</a>
				</div>
				<form method="post" class="madrural-auth-card madrural-auth-login-form">
					<h2><?php echo esc_html__( 'Acceso de Gestores', 'madrural-auth' ); ?></h2>
					<?php if ( '' !== $msg ) : ?>
						<div class="madrural-auth-notice is-error"><?php echo esc_html( $msg ); ?></div>
					<?php endif; ?>
					<?php wp_nonce_field( 'madrural_auth_login', 'madrural_auth_nonce' ); ?>
					<input type="hidden" name="madrural_auth_action" value="login">
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
					<p>
						<label for="madrural-auth-name"><?php echo esc_html__( 'Nombre', 'madrural-auth' ); ?></label>
						<input id="madrural-auth-name" name="name" type="text" required>
					</p>
					<p>
						<label for="madrural-auth-password"><?php echo esc_html__( 'Contraseña', 'madrural-auth' ); ?></label>
						<span class="madrural-auth-password-wrap">
							<input id="madrural-auth-password" name="password" type="password" required>
							<button type="button" class="madrural-auth-password-toggle" data-target="madrural-auth-password" aria-label="<?php echo esc_attr__( 'Mostrar contraseña', 'madrural-auth' ); ?>" aria-pressed="false">👁</button>
						</span>
					</p>
					<p><button type="submit" class="madrural-auth-btn"><?php echo esc_html__( 'Entrar', 'madrural-auth' ); ?></button></p>
				</form>
			</div>
			<?php

			return (string) ob_get_clean();
		}

		public static function shortcode_superadmin_panel() {
			if ( ! self::is_authenticated() || ! self::is_superadmin_profile() ) {
				return '<div class="madrural-auth-card"><p>' . esc_html__( 'Acceso denegado.', 'madrural-auth' ) . '</p></div>';
			}

			global $wpdb;
			$table_name = self::get_table_name();
			$per_page     = 10;
			$current_page = isset( $_GET['ma_paged'] ) ? max( 1, (int) wp_unslash( $_GET['ma_paged'] ) ) : 1;
			$offset       = ( $current_page - 1 ) * $per_page;

			$total_profiles = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
			$total_pages    = max( 1, (int) ceil( $total_profiles / $per_page ) );
			if ( $current_page > $total_pages ) {
				$current_page = $total_pages;
				$offset       = ( $current_page - 1 ) * $per_page;
			}

			$profiles = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id, name, role, territorio FROM {$table_name} ORDER BY name ASC LIMIT %d OFFSET %d",
					$per_page,
					$offset
				),
				ARRAY_A
			);

			$edit_id      = isset( $_GET['ma_edit'] ) ? (int) $_GET['ma_edit'] : 0;
			$edit_profile = null;
			if ( $edit_id > 0 ) {
				$edit_profile = $wpdb->get_row( $wpdb->prepare( "SELECT id, name, role, territorio FROM {$table_name} WHERE id = %d LIMIT 1", $edit_id ), ARRAY_A );
			}

			$notice_map = array(
				'saved'              => esc_html__( 'Perfil guardado correctamente.', 'madrural-auth' ),
				'deleted'            => esc_html__( 'Perfil eliminado correctamente.', 'madrural-auth' ),
				'nonce'              => esc_html__( 'No se pudo validar la solicitud.', 'madrural-auth' ),
				'duplicate_name'     => esc_html__( 'El nombre ya existe, usa otro.', 'madrural-auth' ),
				'missing_name'       => esc_html__( 'El nombre es obligatorio.', 'madrural-auth' ),
				'missing_password'   => esc_html__( 'La contraseña es obligatoria para nuevos perfiles.', 'madrural-auth' ),
				'missing_territorio' => esc_html__( 'Debes asignar un territorio al rol admin.', 'madrural-auth' ),
				'cannot_delete_current' => esc_html__( 'No puedes eliminar el perfil con sesión activa.', 'madrural-auth' ),
			);
			$notice     = isset( $_GET['ma_notice'] ) ? sanitize_key( wp_unslash( $_GET['ma_notice'] ) ) : '';
			$msg        = isset( $notice_map[ $notice ] ) ? $notice_map[ $notice ] : '';
			$is_error   = in_array( $notice, array( 'nonce', 'duplicate_name', 'missing_name', 'missing_password', 'missing_territorio', 'cannot_delete_current' ), true );

			$terms = self::get_fixed_territorio_terms();

			$current_name        = is_array( $edit_profile ) ? $edit_profile['name'] : '';
			$current_role        = is_array( $edit_profile ) ? $edit_profile['role'] : 'admin';
			$current_territorios = is_array( $edit_profile ) ? self::parse_territorios( $edit_profile['territorio'] ) : array();
			$panel_url           = self::get_page_url( 'panel' );
			$base_pagination_url = remove_query_arg( 'ma_paged' );
			if ( ! is_string( $base_pagination_url ) || '' === $base_pagination_url ) {
				$base_pagination_url = self::get_page_url( 'panel' );
			}

			if ( $total_pages <= 1 ) {
				$pagination_links = array(
					'<span class="page-numbers prev disabled" aria-disabled="true">' . esc_html__( '« Anterior', 'madrural-auth' ) . '</span>',
					'<span aria-current="page" class="page-numbers current">1</span>',
					'<span class="page-numbers next disabled" aria-disabled="true">' . esc_html__( 'Siguiente »', 'madrural-auth' ) . '</span>',
				);
			} else {
				$pagination_links = paginate_links(
					array(
						'base'      => esc_url_raw( add_query_arg( 'ma_paged', '%#%', $base_pagination_url ) ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => $total_pages,
						'type'      => 'array',
						'prev_next' => true,
						'prev_text' => esc_html__( '« Anterior', 'madrural-auth' ),
						'next_text' => esc_html__( 'Siguiente »', 'madrural-auth' ),
					)
				);
				$pagination_links = is_array( $pagination_links ) ? $pagination_links : array();
			}

			ob_start();
			?>
			<?php echo self::render_shared_header( 'perfiles' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="madrural-plugin-content">
			<div class="madrural-auth-admin-grid">
				<div class="madrural-auth-card">
					<h2><?php echo esc_html__( 'Perfiles registrados', 'madrural-auth' ); ?></h2>
					<p><a class="madrural-auth-btn madrural-auth-create-profile-link" href="<?php echo esc_url( $panel_url ); ?>"><?php echo esc_html__( 'Crear nuevo perfil', 'madrural-auth' ); ?></a></p>
					<?php if ( '' !== $msg ) : ?>
						<div class="madrural-auth-notice <?php echo $is_error ? 'is-error' : 'is-success'; ?>"><?php echo esc_html( $msg ); ?></div>
					<?php endif; ?>
					<table class="madrural-auth-table">
						<thead>
							<tr>
								<th><?php echo esc_html__( 'Nombre', 'madrural-auth' ); ?></th>
								<th><?php echo esc_html__( 'Rol', 'madrural-auth' ); ?></th>
								<th><?php echo esc_html__( 'Territorio', 'madrural-auth' ); ?></th>
								<th><?php echo esc_html__( 'Acción', 'madrural-auth' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php if ( ! empty( $profiles ) ) : ?>
							<?php foreach ( $profiles as $profile ) : ?>
								<tr>
									<td><?php echo esc_html( $profile['name'] ); ?></td>
									<td><?php echo esc_html( $profile['role'] ); ?></td>
									<td><?php echo esc_html( self::get_territorio_display_label( (string) $profile['territorio'] ) ); ?></td>
									<td class="madrural-auth-actions-cell">
									<a class="madrural-auth-edit-profile-link" href="<?php echo esc_url( add_query_arg( 'ma_edit', (int) $profile['id'], self::get_page_url( 'panel' ) ) ); ?>"><?php echo esc_html__( 'Editar', 'madrural-auth' ); ?></a>
										|
										<form method="post" class="madrural-auth-inline-form madrural-auth-confirm-form" data-confirm-message="<?php echo esc_attr__( '¿Seguro que deseas eliminar este perfil?', 'madrural-auth' ); ?>">
											<?php wp_nonce_field( 'madrural_auth_delete_profile', 'madrural_auth_delete_nonce' ); ?>
											<input type="hidden" name="madrural_auth_action" value="delete_profile">
											<input type="hidden" name="profile_id" value="<?php echo esc_attr( (string) $profile['id'] ); ?>">
											<button type="submit" class="madrural-auth-link-button"><?php echo esc_html__( 'Eliminar', 'madrural-auth' ); ?></button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr><td colspan="4"><?php echo esc_html__( 'No hay perfiles todavía.', 'madrural-auth' ); ?></td></tr>
						<?php endif; ?>
						</tbody>
					</table>
					<nav class="madrural-auth-pagination" aria-label="<?php echo esc_attr__( 'Paginación', 'madrural-auth' ); ?>">
						<?php foreach ( $pagination_links as $pagination_link ) : ?>
							<?php echo wp_kses_post( $pagination_link ); ?>
						<?php endforeach; ?>
					</nav>
				</div>

				<div class="madrural-auth-card">
					<h2><?php echo esc_html( $edit_id > 0 ? __( 'Editar perfil', 'madrural-auth' ) : __( 'Nuevo perfil', 'madrural-auth' ) ); ?></h2>
					<form method="post" class="madrural-auth-profile-form" autocomplete="off">
						<?php wp_nonce_field( 'madrural_auth_save_profile', 'madrural_auth_profile_nonce' ); ?>
						<input type="hidden" name="madrural_auth_action" value="save_profile">
						<input type="hidden" name="profile_id" value="<?php echo esc_attr( (string) $edit_id ); ?>">
						<input type="text" name="madrural_fake_user" autocomplete="username" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;height:0;width:0;pointer-events:none;">
						<input type="password" name="madrural_fake_password" autocomplete="current-password" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;opacity:0;height:0;width:0;pointer-events:none;">
						<p>
							<label for="ma_name"><?php echo esc_html__( 'Nombre', 'madrural-auth' ); ?></label>
							<input type="text" id="ma_name" name="name" required value="<?php echo esc_attr( $current_name ); ?>" autocomplete="off" autocapitalize="none" autocorrect="off" spellcheck="false">
						</p>
						<p>
							<label for="ma_password"><?php echo esc_html__( 'Contraseña', 'madrural-auth' ); ?></label>
							<span class="madrural-auth-password-wrap">
								<input type="password" id="ma_password" name="password" autocomplete="new-password" <?php echo 0 === $edit_id ? 'required' : ''; ?>>
								<button type="button" class="madrural-auth-password-toggle" data-target="ma_password" aria-label="<?php echo esc_attr__( 'Mostrar contraseña', 'madrural-auth' ); ?>" aria-pressed="false">👁</button>
							</span>
						</p>
						<p>
							<label for="ma_role"><?php echo esc_html__( 'Rol', 'madrural-auth' ); ?></label>
							<select id="ma_role" name="role">
								<option value="admin" <?php selected( $current_role, 'admin' ); ?>><?php echo esc_html__( 'admin', 'madrural-auth' ); ?></option>
								<option value="superadmin" <?php selected( $current_role, 'superadmin' ); ?>><?php echo esc_html__( 'superadmin', 'madrural-auth' ); ?></option>
							</select>
						</p>
						<p>
							<label for="ma_territorio"><?php echo esc_html__( 'Territorios', 'madrural-auth' ); ?></label>
							<select id="ma_territorio" name="territorios[]" multiple size="6">
								<?php if ( ! is_wp_error( $terms ) ) : ?>
									<?php foreach ( $terms as $term ) : ?>
										<option value="<?php echo esc_attr( $term->slug ); ?>" <?php echo in_array( $term->slug, $current_territorios, true ) ? 'selected' : ''; ?>><?php echo esc_html( $term->name ); ?></option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</p>
						<p><button type="submit" class="madrural-auth-btn"><?php echo esc_html__( 'Guardar perfil', 'madrural-auth' ); ?></button></p>
					</form>

				</div>
			</div>
			</div>
			</div>
			<?php

			return (string) ob_get_clean();
		}

		public static function enqueue_assets() {
			if ( is_admin() ) {
				return;
			}

			wp_enqueue_style(
				'madrural-auth-frontend',
				plugins_url( 'assets/css/auth-frontend.css', dirname( __FILE__ ) ),
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'madrural-auth-frontend',
				plugins_url( 'assets/js/auth-frontend.js', dirname( __FILE__ ) ),
				array(),
				'1.0.0',
				true
			);
		}

		public static function render_floating_user_menu() {
			if ( ! self::is_authenticated() ) {
				return;
			}

			if ( ! self::is_auth_front_request() && ! self::is_eventos_front_request() ) {
				return;
			}

			$profile    = self::get_current_profile();
			$initial    = strtoupper( substr( (string) $profile['name'], 0, 1 ) );
			$role_label = isset( $profile['role'] ) ? sanitize_text_field( (string) $profile['role'] ) : '';
			$territorio_value = isset( $profile['territorios'] ) ? $profile['territorios'] : ( isset( $profile['territorio'] ) ? (string) $profile['territorio'] : '' );
			$territorio_label = self::get_territorio_display_label( $territorio_value );
			if ( '' === $territorio_label && 'superadmin' === $role_label ) {
				$territorio_label = 'global';
			}
			$mis_url    = self::get_mis_eventos_url();
			$logout_url = add_query_arg(
				array(
					'madrural_auth_logout' => 1,
					'_wpnonce'             => wp_create_nonce( 'madrural_auth_logout' ),
				),
				home_url( '/' )
			);
			?>
			<div class="madrural-auth-floating" id="madrural-auth-floating">
				<button type="button" class="madrural-auth-avatar" id="madrural-auth-avatar"><?php echo esc_html( $initial ); ?></button>
				<div class="madrural-auth-dropdown" id="madrural-auth-dropdown">
					<div class="madrural-auth-user"><?php echo esc_html( $profile['name'] ); ?></div>
					<?php if ( '' !== $role_label ) : ?>
						<div class="madrural-auth-user-role"><?php echo esc_html( sprintf( 'Rol: %s', $role_label ) ); ?></div>
					<?php endif; ?>
					<?php if ( '' !== $territorio_label ) : ?>
						<div class="madrural-auth-user-territorio"><?php echo esc_html( sprintf( 'Territorio: %s', $territorio_label ) ); ?></div>
					<?php endif; ?>
					<a href="<?php echo esc_url( $mis_url ); ?>"><?php echo esc_html__( 'Gestión de Eventos', 'madrural-auth' ); ?></a>
					<?php if ( 'superadmin' === $profile['role'] ) : ?>
						<a href="<?php echo esc_url( self::get_page_url( 'panel' ) ); ?>"><?php echo esc_html__( 'Perfiles', 'madrural-auth' ); ?></a>
					<?php endif; ?>
					<a href="<?php echo esc_url( $logout_url ); ?>"><?php echo esc_html__( 'Cerrar Sesión', 'madrural-auth' ); ?></a>
				</div>
			</div>
			<?php
		}

		public static function redirect_to_login( $notice ) {
			$url = add_query_arg( 'ma_notice', sanitize_key( $notice ), self::get_page_url( 'login' ) );

			if ( ! headers_sent() ) {
				wp_safe_redirect( $url );
				exit;
			}

			echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $url ) ) . ');</script>';
			exit;
		}

		public static function redirect_to_panel( $notice, $edit_id = 0 ) {
			$args = array( 'ma_notice' => sanitize_key( $notice ) );
			if ( $edit_id > 0 ) {
				$args['ma_edit'] = $edit_id;
			}

			$url = add_query_arg( $args, self::get_page_url( 'panel' ) );

			if ( ! headers_sent() ) {
				wp_safe_redirect( $url );
				exit;
			}

			echo '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $url ) ) . ');</script>';
			exit;
		}

		public static function get_current_url() {
			$scheme = is_ssl() ? 'https://' : 'http://';
			$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
			$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

			return esc_url_raw( $scheme . $host . $uri );
		}

		public static function get_mis_eventos_url() {
			$pages   = get_option( 'madrural_eventos_front_pages', array() );
			$page_id = isset( $pages['mis'] ) ? (int) $pages['mis'] : 0;

			if ( $page_id > 0 ) {
				$url = get_permalink( $page_id );
				if ( is_string( $url ) && '' !== $url ) {
					return $url;
				}
			}

			$page = get_page_by_path( 'mis-eventos' );
			if ( $page instanceof WP_Post ) {
				$url = get_permalink( $page->ID );
				if ( is_string( $url ) && '' !== $url ) {
					return $url;
				}
			}

			return home_url( '/' );
		}
	}
}

register_activation_hook( __FILE__, array( 'MADRURAL_Auth_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MADRURAL_Auth_Plugin', 'deactivate' ) );

MADRURAL_Auth_Plugin::init();

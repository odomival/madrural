<?php
/**
 * Plugin Name:       MADRURAL - Agenda de Eventos Multidestino
 * Description:       Plugin para la gestión descentralizada, bilingüe (Español/Inglés) e interoperable mediante API REST de la agenda de eventos de MADRURAL.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            MADRURAL
 * Text Domain:       madrural-eventos
 * Domain Path:       /languages
 *
 * @package MADRURAL_Eventos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MADRURAL_Eventos_Plugin' ) ) {
	/**
	 * Main plugin class.
	 */
	class MADRURAL_Eventos_Plugin {
		/**
		 * CPT slug.
		 *
		 * @var string
		 */
		const CPT = 'evento';

		/**
		 * Category taxonomy slug.
		 *
		 * @var string
		 */
		const TAX_CATEGORIA = 'categoria_evento';

		/**
		 * Territory taxonomy slug.
		 *
		 * @var string
		 */
		const TAX_TERRITORIO = 'territorio_evento';

		/**
		 * Fixed territory labels.
		 *
		 * @var array<string>
		 */
		const FIXED_TERRITORIO_LABELS = array(
			'Sierra Norte',
			'Sierra de Guadarrama',
			'Sierra Oeste',
			'Las Vegas & La Alcarria',
		);

		/**
		 * Gestor role slug.
		 *
		 * @var string
		 */
		const ROLE_GESTOR = 'gestor_madrural';

		/**
		 * Option name for frontend pages.
		 *
		 * @var string
		 */
		const OPTION_FRONT_PAGES = 'madrural_eventos_front_pages';

		/**
		 * Custom storage table for eventos.
		 *
		 * @var string
		 */
		const EVENTS_TABLE = 'mod145_madrural_eventos';

		/**
		 * Migration flag option.
		 *
		 * @var string
		 */
		const OPTION_EVENTS_TABLE_MIGRATED = 'madrural_eventos_table_migrated';

		/**
		 * Init hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'ensure_events_table' ), 5 );
			add_action( 'init', array( __CLASS__, 'maybe_backfill_events_table' ), 6 );
			add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
			add_action( 'init', array( __CLASS__, 'register_post_type_and_taxonomies' ) );
			add_action( 'init', array( __CLASS__, 'ensure_fixed_territorios_terms' ), 15 );
			add_action( 'init', array( __CLASS__, 'register_meta_fields' ) );
			add_action( 'init', array( __CLASS__, 'sync_roles_and_caps' ), 20 );
			add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 30 );
			add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_box' ) );
			add_action( 'save_post_' . self::CPT, array( __CLASS__, 'save_event_meta' ) );
			add_action( 'save_post_' . self::CPT, array( __CLASS__, 'translate_event_on_save' ), 20, 3 );
			add_action( 'before_delete_post', array( __CLASS__, 'handle_event_post_delete' ) );
			add_action( 'trashed_post', array( __CLASS__, 'handle_event_post_delete' ) );
			add_action( 'pre_get_posts', array( __CLASS__, 'restrict_gestor_admin_list' ) );
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
			add_action( 'template_redirect', array( __CLASS__, 'render_virtual_auth_login_fallback' ), 0 );
			add_action( 'template_redirect', array( __CLASS__, 'handle_frontend_requests' ) );
			add_action( 'template_redirect', array( __CLASS__, 'redirect_restricted_views_to_auth_login' ), 0 );
			add_action( 'template_redirect', array( __CLASS__, 'disable_cache_on_plugin_views' ), 1 );
			add_action( 'admin_init', array( __CLASS__, 'maybe_create_default_pages' ) );
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
			add_filter( 'body_class', array( __CLASS__, 'add_plugin_body_class' ) );
			add_filter( 'the_content', array( __CLASS__, 'render_frontend_event_details' ) );
			add_action( 'wp_head', array( __CLASS__, 'render_event_schema_json_ld' ) );
		}

		/**
		 * Renders virtual login page for madrural-auth on /acceso-gestores/ when it resolves to 404.
		 *
		 * @return void
		 */
		public static function render_virtual_auth_login_fallback() {
			if ( ! is_404() ) {
				return;
			}

			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
			if ( '' === $path || ! preg_match( '#(^|/)acceso-gestores$#', $path ) ) {
				return;
			}

			if ( ! shortcode_exists( 'madrural_auth_login' ) ) {
				return;
			}

			global $wp_query;
			if ( $wp_query instanceof WP_Query ) {
				$wp_query->is_404 = false;
			}

			status_header( 200 );
			nocache_headers();
			wp_enqueue_style(
				'madrural-eventos-frontend',
				plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css',
				array(),
				'1.0.0'
			);

			$content = do_shortcode( '[madrural_auth_login]' );

			?>
			<!doctype html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php wp_head(); ?>
			</head>
			<body <?php body_class( 'madrural-auth-login-virtual' ); ?>>
				<?php echo wp_kses_post( $content ); ?>
				<?php wp_footer(); ?>
			</body>
			</html>
			<?php
			exit;
		}

		/**
		 * Redirects restricted frontend views to external auth login.
		 *
		 * @return void
		 */
		public static function redirect_restricted_views_to_auth_login() {
			if ( is_admin() || self::user_can_manage_events() ) {
				return;
			}

			$login_url = self::get_external_auth_login_url();
			if ( '' === $login_url ) {
				return;
			}

			$current_post = get_queried_object();
			if ( $current_post instanceof WP_Post ) {
				if ( 'acceso-gestores' === $current_post->post_name ) {
					return;
				}

				$is_restricted = in_array( $current_post->post_name, array( 'mis-eventos', 'gestionar-evento' ), true ) || has_shortcode( (string) $current_post->post_content, 'madrural_mis_eventos' ) || has_shortcode( (string) $current_post->post_content, 'madrural_evento_form' );

				if ( $is_restricted ) {
					wp_safe_redirect( $login_url );
					exit;
				}
			}
		}

		/**
		 * Adds body class on plugin managed pages.
		 *
		 * @param array $classes Existing body classes.
		 * @return array
		 */
		public static function add_plugin_body_class( $classes ) {
			if ( self::is_plugin_front_page() ) {
				$classes[] = 'madrural-eventos-page';
			}

			return $classes;
		}

		/**
		 * Disables full-page cache for plugin frontend pages.
		 *
		 * @return void
		 */
		public static function disable_cache_on_plugin_views() {
			if ( ! self::is_plugin_front_page() ) {
				return;
			}

			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}

			nocache_headers();
		}

		/**
		 * Checks if current request is a plugin frontend page.
		 *
		 * @return bool
		 */
		public static function is_plugin_front_page() {
			if ( ! is_page() ) {
				return false;
			}

			$pages = get_option( self::OPTION_FRONT_PAGES, array() );
			if ( empty( $pages ) || ! is_array( $pages ) ) {
				return false;
			}

			$current_id = get_queried_object_id();

			return in_array( (int) $current_id, array_map( 'intval', $pages ), true );
		}

		/**
		 * Adds event links to site menus for global access.
		 *
		 * @param string   $items Menu items HTML.
		 * @param stdClass $args Menu arguments.
		 * @return string
		 */
		public static function inject_event_links_into_menus( $items, $args ) {
			if ( is_admin() ) {
				return $items;
			}

			$menu_class     = isset( $args->menu_class ) ? (string) $args->menu_class : '';
			if ( false === strpos( $menu_class, 'madbar-nav' ) ) {
				return $items;
			}

			$agenda_url = self::get_frontend_page_url( 'agenda' );
			if ( '' === $agenda_url ) {
				return $items;
			}

			if ( false === strpos( $items, 'madrural-menu-eventos' ) ) {
				$items .= '<li class="menu-item madrural-menu-eventos"><a href="' . esc_url( $agenda_url ) . '">' . esc_html__( 'Eventos', 'madrural-eventos' ) . '</a></li>';
			}

			if ( self::user_can_manage_events() && false === strpos( $items, 'madrural-menu-mis-eventos' ) ) {
				$items .= '<li class="menu-item madrural-menu-mis-eventos"><a href="' . esc_url( self::get_frontend_page_url( 'mis' ) ) . '">' . esc_html__( 'Gestión de Eventos', 'madrural-eventos' ) . '</a></li>';
			}

			return $items;
		}

		/**
		 * Enqueues frontend assets.
		 *
		 * @return void
		 */
		public static function enqueue_frontend_assets() {
			wp_enqueue_style(
				'madrural-eventos-frontend',
				plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css',
				array(),
				'1.0.0'
			);

			wp_enqueue_script(
				'madrural-eventos-frontend',
				plugin_dir_url( __FILE__ ) . 'assets/js/frontend.js',
				array(),
				'1.0.0',
				true
			);
		}

		/**
		 * Loads plugin textdomain.
		 *
		 * @return void
		 */
		public static function load_textdomain() {
			load_plugin_textdomain( 'madrural-eventos', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Returns custom capabilities for eventos.
		 *
		 * @return array<string>
		 */
		public static function get_event_caps() {
			return array(
				'edit_evento',
				'read_evento',
				'delete_evento',
				'edit_eventos',
				'edit_others_eventos',
				'publish_eventos',
				'read_private_eventos',
				'delete_eventos',
				'delete_private_eventos',
				'delete_published_eventos',
				'delete_others_eventos',
				'edit_private_eventos',
				'edit_published_eventos',
				'create_eventos',
			);
		}

		/**
		 * Activation callback.
		 *
		 * @return void
		 */
		public static function activate() {
			self::ensure_events_table();
			self::register_post_type_and_taxonomies();
			self::sync_roles_and_caps();
			self::maybe_create_default_pages( true );
			self::backfill_events_table();
			flush_rewrite_rules();
		}

		/**
		 * Returns custom eventos table name.
		 *
		 * @return string
		 */
		public static function get_events_table_name() {
			return self::EVENTS_TABLE;
		}

		/**
		 * Ensures custom eventos table exists.
		 *
		 * @return void
		 */
		public static function ensure_events_table() {
			static $ensured = false;
			if ( $ensured ) {
				return;
			}

			self::install_events_table();
			$ensured = true;
		}

		/**
		 * Installs/updates custom eventos table schema.
		 *
		 * @return void
		 */
		public static function install_events_table() {
			global $wpdb;

			$table_name      = self::get_events_table_name();
			$charset_collate = $wpdb->get_charset_collate();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$sql = "CREATE TABLE {$table_name} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				post_id bigint(20) unsigned NOT NULL,
				titulo text NOT NULL,
				descripcion longtext NOT NULL,
				categoria_term_id bigint(20) unsigned NOT NULL DEFAULT 0,
				categoria varchar(191) NOT NULL DEFAULT '',
				territorio_term_id bigint(20) unsigned NOT NULL DEFAULT 0,
				territorio varchar(191) NOT NULL DEFAULT '',
				fecha_inicio varchar(20) NOT NULL DEFAULT '',
				fecha_fin varchar(20) NOT NULL DEFAULT '',
				hora_evento varchar(10) NOT NULL DEFAULT '',
				ubicacion varchar(255) NOT NULL DEFAULT '',
				estado_moderacion varchar(20) NOT NULL DEFAULT 'borrador',
				galeria_ids longtext NOT NULL,
				titulo_en text NOT NULL,
				descripcion_en longtext NOT NULL,
				categoria_en varchar(191) NOT NULL DEFAULT '',
				updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY  (id),
				UNIQUE KEY post_id (post_id)
			) {$charset_collate};";

			dbDelta( $sql );
		}

		/**
		 * Gets a custom table row by post ID.
		 *
		 * @param int $post_id Post ID.
		 * @return array|null
		 */
		public static function get_event_row( $post_id ) {
			global $wpdb;

			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				return null;
			}

			$table_name = self::get_events_table_name();
			$row        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE post_id = %d LIMIT 1", $post_id ), ARRAY_A );

			return is_array( $row ) ? $row : null;
		}

		/**
		 * Returns event field from custom table with WP fallback.
		 *
		 * @param int    $post_id Post ID.
		 * @param string $field Field key.
		 * @param mixed  $default Default value.
		 * @return mixed
		 */
		public static function get_event_storage_value( $post_id, $field, $default = '' ) {
			$row = self::get_event_row( $post_id );
			if ( is_array( $row ) && array_key_exists( $field, $row ) ) {
				if ( 'galeria_ids' === $field ) {
					return self::decode_gallery_ids_from_db( $row[ $field ] );
				}

				return $row[ $field ];
			}

			return $default;
		}

		/**
		 * Returns normalized DB value for galeria IDs storage.
		 *
		 * @param mixed $value Raw gallery value.
		 * @return string
		 */
		public static function encode_gallery_ids_for_db( $value ) {
			$ids  = self::sanitize_image_ids( $value );
			$json = wp_json_encode( $ids );

			return is_string( $json ) ? $json : '[]';
		}

		/**
		 * Decodes stored gallery IDs from DB.
		 *
		 * @param mixed $value Raw DB value.
		 * @return array<int>
		 */
		public static function decode_gallery_ids_from_db( $value ) {
			if ( is_string( $value ) && '' !== $value ) {
				$data = json_decode( $value, true );
				if ( is_array( $data ) ) {
					return self::sanitize_image_ids( $data );
				}
			}

			return self::sanitize_image_ids( $value );
		}

		/**
		 * Upserts event data into custom table.
		 *
		 * @param int   $post_id Post ID.
		 * @param array $data Normalized event data.
		 * @return void
		 */
		public static function upsert_event_row( $post_id, $data ) {
			global $wpdb;

			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				return;
			}

			$table_name = self::get_events_table_name();

			$payload = array(
				'post_id'           => $post_id,
				'titulo'            => isset( $data['titulo'] ) ? sanitize_text_field( (string) $data['titulo'] ) : '',
				'descripcion'       => isset( $data['descripcion'] ) ? wp_kses_post( (string) $data['descripcion'] ) : '',
				'categoria_term_id' => isset( $data['categoria_term_id'] ) ? (int) $data['categoria_term_id'] : 0,
				'categoria'         => isset( $data['categoria'] ) ? sanitize_text_field( (string) $data['categoria'] ) : '',
				'territorio_term_id'=> isset( $data['territorio_term_id'] ) ? (int) $data['territorio_term_id'] : 0,
				'territorio'        => isset( $data['territorio'] ) ? sanitize_text_field( (string) $data['territorio'] ) : '',
				'fecha_inicio'      => isset( $data['fecha_inicio'] ) ? self::sanitize_date( (string) $data['fecha_inicio'] ) : '',
				'fecha_fin'         => isset( $data['fecha_fin'] ) ? self::sanitize_date( (string) $data['fecha_fin'] ) : '',
				'hora_evento'       => isset( $data['hora_evento'] ) ? self::sanitize_time( (string) $data['hora_evento'] ) : '',
				'ubicacion'         => isset( $data['ubicacion'] ) ? sanitize_text_field( (string) $data['ubicacion'] ) : '',
				'estado_moderacion' => isset( $data['estado_moderacion'] ) ? self::sanitize_moderation_state( (string) $data['estado_moderacion'] ) : 'borrador',
				'galeria_ids'       => self::encode_gallery_ids_for_db( isset( $data['galeria_ids'] ) ? $data['galeria_ids'] : array() ),
				'titulo_en'         => isset( $data['titulo_en'] ) ? sanitize_text_field( (string) $data['titulo_en'] ) : '',
				'descripcion_en'    => isset( $data['descripcion_en'] ) ? wp_kses_post( (string) $data['descripcion_en'] ) : '',
				'categoria_en'      => isset( $data['categoria_en'] ) ? sanitize_text_field( (string) $data['categoria_en'] ) : '',
				'updated_at'        => current_time( 'mysql' ),
			);

			$formats = array(
				'%d', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
			);

			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE post_id = %d LIMIT 1", $post_id ) );
			if ( $exists ) {
				$wpdb->update( $table_name, $payload, array( 'post_id' => $post_id ), $formats, array( '%d' ) );
				return;
			}

			$wpdb->insert( $table_name, $payload, $formats );
		}

		/**
		 * Deletes custom event row by post ID.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public static function delete_event_row( $post_id ) {
			global $wpdb;

			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				return;
			}

			$table_name = self::get_events_table_name();
			$wpdb->delete( $table_name, array( 'post_id' => $post_id ), array( '%d' ) );
		}

		/**
		 * Handles deletion/trash of evento posts to keep custom table in sync.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public static function handle_event_post_delete( $post_id ) {
			$post_id = (int) $post_id;
			if ( $post_id <= 0 ) {
				return;
			}

			$post = get_post( $post_id );
			if ( $post instanceof WP_Post && self::CPT !== $post->post_type ) {
				return;
			}

			self::delete_event_row( $post_id );
		}

		/**
		 * Runs one-time backfill when migration flag is missing.
		 *
		 * @return void
		 */
		public static function maybe_backfill_events_table() {
			if ( 'yes' === get_option( self::OPTION_EVENTS_TABLE_MIGRATED, 'no' ) ) {
				return;
			}

			self::backfill_events_table();
		}

		/**
		 * Backfills custom table from existing evento posts.
		 *
		 * @return void
		 */
		public static function backfill_events_table() {
			self::ensure_events_table();

			$posts = get_posts(
				array(
					'post_type'      => self::CPT,
					'post_status'    => array( 'publish', 'pending', 'draft', 'private' ),
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			if ( empty( $posts ) ) {
				update_option( self::OPTION_EVENTS_TABLE_MIGRATED, 'yes' );
				return;
			}

			foreach ( $posts as $post_id ) {
				self::sync_event_row_from_post( (int) $post_id );
			}

			update_option( self::OPTION_EVENTS_TABLE_MIGRATED, 'yes' );
		}

		/**
		 * Synchronizes one evento post into custom table row.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public static function sync_event_row_from_post( $post_id ) {
			$post_id = (int) $post_id;
			$post    = get_post( $post_id );
			if ( ! $post instanceof WP_Post || self::CPT !== $post->post_type ) {
				return;
			}

			$gallery_ids = self::sanitize_image_ids( get_post_meta( $post_id, 'madrural_galeria_ids', true ) );
			if ( empty( $gallery_ids ) && has_post_thumbnail( $post_id ) ) {
				$gallery_ids = array( (int) get_post_thumbnail_id( $post_id ) );
			}

			$categorias       = wp_get_post_terms( $post_id, self::TAX_CATEGORIA );
			$categoria_term   = ( ! is_wp_error( $categorias ) && ! empty( $categorias ) && isset( $categorias[0] ) && $categorias[0] instanceof WP_Term ) ? $categorias[0] : null;
			$territorios      = wp_get_post_terms( $post_id, self::TAX_TERRITORIO );
			$territorio_term  = ( ! is_wp_error( $territorios ) && ! empty( $territorios ) && isset( $territorios[0] ) && $territorios[0] instanceof WP_Term ) ? $territorios[0] : null;

			self::upsert_event_row(
				$post_id,
				array(
					'titulo'            => (string) $post->post_title,
					'descripcion'       => (string) $post->post_content,
					'categoria_term_id' => $categoria_term instanceof WP_Term ? (int) $categoria_term->term_id : 0,
					'categoria'         => $categoria_term instanceof WP_Term ? (string) $categoria_term->name : '',
					'territorio_term_id'=> $territorio_term instanceof WP_Term ? (int) $territorio_term->term_id : 0,
					'territorio'        => $territorio_term instanceof WP_Term ? (string) $territorio_term->name : '',
					'fecha_inicio'      => (string) get_post_meta( $post_id, 'madrural_fecha_inicio', true ),
					'fecha_fin'         => (string) get_post_meta( $post_id, 'madrural_fecha_fin', true ),
					'hora_evento'       => (string) get_post_meta( $post_id, 'madrural_hora_evento', true ),
					'ubicacion'         => (string) get_post_meta( $post_id, 'madrural_ubicacion', true ),
					'estado_moderacion' => (string) get_post_meta( $post_id, 'madrural_estado_moderacion', true ),
					'galeria_ids'       => $gallery_ids,
					'titulo_en'         => (string) get_post_meta( $post_id, '_madrural_titulo_en', true ),
					'descripcion_en'    => (string) get_post_meta( $post_id, '_madrural_descripcion_en', true ),
					'categoria_en'      => (string) get_post_meta( $post_id, '_madrural_categoria_en', true ),
				)
			);
		}

		/**
		 * Creates default frontend pages with shortcodes.
		 *
		 * @param bool $force Force recreation checks.
		 * @return void
		 */
		public static function maybe_create_default_pages( $force = false ) {
			if ( ! $force && ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$pages = get_option( self::OPTION_FRONT_PAGES, array() );

			$definitions = array(
				'agenda' => array(
					'title'   => 'Agenda de Eventos',
					'slug'    => 'agenda-eventos',
					'content' => '[madrural_eventos_list]',
				),
				'mis'    => array(
					'title'   => 'Gestión de Eventos',
					'slug'    => 'mis-eventos',
					'content' => '[madrural_mis_eventos]',
				),
				'form'   => array(
					'title'   => 'Crear Evento',
					'slug'    => 'gestionar-evento',
					'content' => '[madrural_evento_form]',
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
				update_option( self::OPTION_FRONT_PAGES, $pages );
			}
		}

		/**
		 * Ensures role/capability consistency.
		 *
		 * @return void
		 */
		public static function sync_roles_and_caps() {
			self::add_or_update_gestor_role();
			self::grant_caps_to_admin_roles();
		}

		/**
		 * Deactivation callback.
		 *
		 * @return void
		 */
		public static function deactivate() {
			flush_rewrite_rules();
		}

		/**
		 * Adds or updates gestor role.
		 *
		 * @return void
		 */
		public static function add_or_update_gestor_role() {
			$author_caps = array();
			$author      = get_role( 'author' );

			if ( $author instanceof WP_Role ) {
				$author_caps = $author->capabilities;
			}

			$caps_to_remove = array(
				'edit_posts',
				'delete_posts',
				'delete_published_posts',
				'publish_posts',
				'read_private_posts',
				'edit_published_posts',
			);

			foreach ( $caps_to_remove as $cap ) {
				unset( $author_caps[ $cap ] );
			}

			$author_caps['read'] = true;

			foreach ( self::get_event_caps() as $event_cap ) {
				$author_caps[ $event_cap ] = true;
			}

			$existing_role = get_role( self::ROLE_GESTOR );
			if ( ! $existing_role instanceof WP_Role ) {
				add_role(
					self::ROLE_GESTOR,
					esc_html__( 'Gestor Territorial MADRURAL', 'madrural-eventos' ),
					$author_caps
				);
				return;
			}

			foreach ( $author_caps as $cap => $is_allowed ) {
				if ( $is_allowed ) {
					$existing_role->add_cap( $cap );
				}
			}

			foreach ( $caps_to_remove as $cap ) {
				$existing_role->remove_cap( $cap );
			}
		}

		/**
		 * Grants custom evento caps to privileged roles.
		 *
		 * @return void
		 */
		public static function grant_caps_to_admin_roles() {
			$roles = array( 'administrator', 'editor' );

			foreach ( $roles as $role_name ) {
				$role = get_role( $role_name );
				if ( ! $role instanceof WP_Role ) {
					continue;
				}

				foreach ( self::get_event_caps() as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}

		/**
		 * Registers CPT and taxonomies.
		 *
		 * @return void
		 */
		public static function register_post_type_and_taxonomies() {
			register_post_type(
				self::CPT,
				array(
					'labels'       => array(
						'name'               => esc_html__( 'Eventos', 'madrural-eventos' ),
						'singular_name'      => esc_html__( 'Evento', 'madrural-eventos' ),
						'menu_name'          => esc_html__( 'Eventos', 'madrural-eventos' ),
						'add_new'            => esc_html__( 'Añadir nuevo', 'madrural-eventos' ),
						'add_new_item'       => esc_html__( 'Añadir nuevo evento', 'madrural-eventos' ),
						'edit_item'          => esc_html__( 'Editar evento', 'madrural-eventos' ),
						'new_item'           => esc_html__( 'Nuevo evento', 'madrural-eventos' ),
						'view_item'          => esc_html__( 'Ver evento', 'madrural-eventos' ),
						'search_items'       => esc_html__( 'Buscar eventos', 'madrural-eventos' ),
						'not_found'          => esc_html__( 'No se encontraron eventos', 'madrural-eventos' ),
						'not_found_in_trash' => esc_html__( 'No hay eventos en la papelera', 'madrural-eventos' ),
					),
					'public'       => true,
					'has_archive'  => true,
					'show_in_rest' => true,
					'menu_icon'    => 'dashicons-calendar-alt',
					'supports'     => array( 'title', 'editor', 'thumbnail' ),
					'rewrite'      => array( 'slug' => 'eventos' ),
					'capability_type' => array( 'evento', 'eventos' ),
					'map_meta_cap'    => true,
				),
			);

			register_taxonomy(
				self::TAX_CATEGORIA,
				self::CPT,
				array(
					'labels'            => array(
						'name'          => esc_html__( 'Categorías de evento', 'madrural-eventos' ),
						'singular_name' => esc_html__( 'Categoría de evento', 'madrural-eventos' ),
					),
					'public'            => true,
					'hierarchical'      => true,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'manage_categories',
						'delete_terms' => 'manage_categories',
						'assign_terms' => 'edit_eventos',
					),
				),
			);

			register_taxonomy(
				self::TAX_TERRITORIO,
				self::CPT,
				array(
					'labels'            => array(
						'name'          => esc_html__( 'Territorios', 'madrural-eventos' ),
						'singular_name' => esc_html__( 'Territorio', 'madrural-eventos' ),
					),
					'public'            => true,
					'hierarchical'      => true,
					'show_in_rest'      => true,
					'show_admin_column' => true,
					'capabilities'      => array(
						'manage_terms' => 'manage_categories',
						'edit_terms'   => 'manage_categories',
						'delete_terms' => 'manage_categories',
						'assign_terms' => 'edit_eventos',
					),
				),
			);
		}

		/**
		 * Registers post meta fields.
		 *
		 * @return void
		 */
		public static function register_meta_fields() {
			$meta_fields = array(
				'madrural_fecha_inicio'    => array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_date' ),
				),
				'madrural_fecha_fin'       => array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_date' ),
				),
				'madrural_hora_evento'     => array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_time' ),
				),
				'madrural_ubicacion'       => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'madrural_estado_moderacion' => array(
					'type'              => 'string',
					'sanitize_callback' => array( __CLASS__, 'sanitize_moderation_state' ),
					'default'           => 'borrador',
				),
				'madrural_galeria_ids'       => array(
					'type'              => 'array',
					'sanitize_callback' => array( __CLASS__, 'sanitize_image_ids' ),
					'default'           => array(),
				),
				'_madrural_titulo_en'       => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				),
				'_madrural_descripcion_en'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'wp_kses_post',
					'default'           => '',
				),
				'_madrural_categoria_en'    => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'default'           => '',
				),
			);

			foreach ( $meta_fields as $meta_key => $args ) {
				register_post_meta(
					self::CPT,
					$meta_key,
					array(
						'single'            => true,
						'show_in_rest'      => true,
						'type'              => $args['type'],
						'default'           => isset( $args['default'] ) ? $args['default'] : '',
						'sanitize_callback' => $args['sanitize_callback'],
						'auth_callback'     => function( $allowed, $meta_key, $post_id ) {
							return current_user_can( 'edit_post', $post_id ) || current_user_can( 'edit_eventos' );
						},
					)
				);
			}
		}

		/**
		 * Translates evento fields and stores English copies in post meta on native save.
		 *
		 * @param int      $post_id Post ID.
		 * @param WP_Post  $post Post object.
		 * @param bool $update Whether this is an existing post being updated.
		 * @return void
		 */
		public static function translate_event_on_save( $post_id, $post, $update ) {
			unset( $update );

			if ( ! $post instanceof WP_Post || self::CPT !== $post->post_type ) {
				return;
			}

			if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( isset( $_POST['madrural_evento_meta_nonce'] ) ) {
				if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['madrural_evento_meta_nonce'] ) ), 'madrural_evento_meta_nonce_action' ) ) {
					return;
				}
			} elseif ( ! ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			self::translate_and_store_event_english_meta( $post_id );
		}

		/**
		 * Registers event details metabox.
		 *
		 * @return void
		 */
		public static function register_meta_box() {
			add_meta_box(
				'madrural_evento_detalles',
				esc_html__( 'Detalles del evento', 'madrural-eventos' ),
				array( __CLASS__, 'render_meta_box' ),
				self::CPT,
				'normal',
				'default'
			);
		}

		/**
		 * Renders metabox fields.
		 *
		 * @param WP_Post $post Post object.
		 * @return void
		 */
		public static function render_meta_box( $post ) {
			wp_nonce_field( 'madrural_evento_meta_nonce_action', 'madrural_evento_meta_nonce' );

			$fecha_inicio = get_post_meta( $post->ID, 'madrural_fecha_inicio', true );
			$fecha_fin    = get_post_meta( $post->ID, 'madrural_fecha_fin', true );
			$hora_evento  = get_post_meta( $post->ID, 'madrural_hora_evento', true );
			$ubicacion    = get_post_meta( $post->ID, 'madrural_ubicacion', true );
			$estado       = get_post_meta( $post->ID, 'madrural_estado_moderacion', true );
			$galeria_ids  = self::get_event_gallery_ids( $post->ID );
			?>
			<p>
				<label for="madrural_fecha_inicio"><strong><?php echo esc_html__( 'Fecha de inicio', 'madrural-eventos' ); ?></strong></label><br>
				<input type="date" id="madrural_fecha_inicio" name="madrural_fecha_inicio" value="<?php echo esc_attr( self::sanitize_date( (string) $fecha_inicio ) ); ?>" title="<?php echo esc_attr__( 'Selecciona la fecha', 'madrural-eventos' ); ?>">
			</p>
			<p>
				<label for="madrural_fecha_fin"><strong><?php echo esc_html__( 'Fecha de fin', 'madrural-eventos' ); ?></strong></label><br>
				<input type="date" id="madrural_fecha_fin" name="madrural_fecha_fin" value="<?php echo esc_attr( self::sanitize_date( (string) $fecha_fin ) ); ?>" title="<?php echo esc_attr__( 'Selecciona la fecha', 'madrural-eventos' ); ?>">
			</p>
			<p>
				<label for="madrural_hora_evento"><strong><?php echo esc_html__( 'Hora del evento', 'madrural-eventos' ); ?></strong></label><br>
				<input type="time" id="madrural_hora_evento" name="madrural_hora_evento" value="<?php echo esc_attr( $hora_evento ); ?>">
			</p>
			<p>
				<label for="madrural_ubicacion"><strong><?php echo esc_html__( 'Ubicación', 'madrural-eventos' ); ?></strong></label><br>
				<input type="text" class="widefat" id="madrural_ubicacion" name="madrural_ubicacion" value="<?php echo esc_attr( $ubicacion ); ?>" maxlength="255">
			</p>
			<p>
				<label for="madrural_estado_moderacion"><strong><?php echo esc_html__( 'Estado de moderación', 'madrural-eventos' ); ?></strong></label><br>
				<select id="madrural_estado_moderacion" name="madrural_estado_moderacion">
					<?php
					$states = array(
						'borrador'  => esc_html__( 'Borrador', 'madrural-eventos' ),
						'pendiente' => esc_html__( 'Pendiente', 'madrural-eventos' ),
						'publicado' => esc_html__( 'Publicado', 'madrural-eventos' ),
					);

					foreach ( $states as $key => $label ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $estado, $key, false ) . '>' . esc_html( $label ) . '</option>';
					}
					?>
				</select>
			</p>
			<p>
				<label for="madrural_galeria_ids"><strong><?php echo esc_html__( 'Imágenes (IDs separados por coma)', 'madrural-eventos' ); ?></strong></label><br>
				<input type="text" class="widefat" id="madrural_galeria_ids" name="madrural_galeria_ids" value="<?php echo esc_attr( implode( ',', $galeria_ids ) ); ?>">
			</p>
			<?php
		}

		/**
		 * Saves metabox fields.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public static function save_event_meta( $post_id ) {
			if ( ! isset( $_POST['madrural_evento_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['madrural_evento_meta_nonce'] ) ), 'madrural_evento_meta_nonce_action' ) ) {
				return;
			}

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$fields = array(
				'madrural_fecha_inicio'      => array( __CLASS__, 'sanitize_date' ),
				'madrural_fecha_fin'         => array( __CLASS__, 'sanitize_date' ),
				'madrural_hora_evento'       => array( __CLASS__, 'sanitize_time' ),
				'madrural_ubicacion'         => 'sanitize_text_field',
				'madrural_estado_moderacion' => array( __CLASS__, 'sanitize_moderation_state' ),
				'madrural_galeria_ids'       => array( __CLASS__, 'sanitize_image_ids' ),
			);

			foreach ( $fields as $key => $sanitizer ) {
				if ( ! isset( $_POST[ $key ] ) ) {
					continue;
				}

				$value = wp_unslash( $_POST[ $key ] );
				$value = call_user_func( $sanitizer, $value );
				update_post_meta( $post_id, $key, $value );
			}

			self::sync_event_row_from_post( $post_id );
		}

		/**
		 * Restricts admin event listing for gestor role.
		 *
		 * @param WP_Query $query Query object.
		 * @return void
		 */
		public static function restrict_gestor_admin_list( $query ) {
			if ( ! is_admin() || ! $query->is_main_query() ) {
				return;
			}

			$post_type = $query->get( 'post_type' );
			if ( self::CPT !== $post_type ) {
				return;
			}

			$current_user = wp_get_current_user();
			if ( ! ( $current_user instanceof WP_User ) ) {
				return;
			}

			if ( in_array( self::ROLE_GESTOR, (array) $current_user->roles, true ) ) {
				$query->set( 'author', (int) $current_user->ID );
			}
		}

		/**
		 * Registers custom REST routes.
		 *
		 * @return void
		 */
		public static function register_rest_routes() {
			register_rest_route(
				'madrural/v1',
				'/eventos',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( __CLASS__, 'rest_get_eventos' ),
						'permission_callback' => '__return_true',
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( __CLASS__, 'rest_create_evento' ),
						'permission_callback' => array( __CLASS__, 'rest_permission_edit_eventos' ),
					),
				)
			);

			register_rest_route(
				'madrural/v1',
				'/eventos/(?P<id>\d+)',
				array(
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( __CLASS__, 'rest_update_evento' ),
						'permission_callback' => array( __CLASS__, 'rest_permission_edit_single_evento' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( __CLASS__, 'rest_delete_evento' ),
						'permission_callback' => array( __CLASS__, 'rest_permission_edit_single_evento' ),
					),
				)
			);
		}

		/**
		 * Permission callback for listing/editing events collection.
		 *
		 * @return bool
		 */
		public static function rest_permission_edit_eventos() {
			return self::user_can_manage_events();
		}

		/**
		 * Permission callback for single event actions.
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return bool
		 */
		public static function rest_permission_edit_single_evento( WP_REST_Request $request ) {
			$event_id = (int) $request->get_param( 'id' );
			if ( $event_id <= 0 ) {
				return false;
			}

			return self::user_can_access_event( $event_id, 'edit' );
		}

		/**
		 * GET /madrural/v1/eventos
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function rest_get_eventos( WP_REST_Request $request ) {
			$per_page     = max( 1, min( 100, (int) $request->get_param( 'per_page' ) ) );
			$page         = max( 1, (int) $request->get_param( 'page' ) );
			$territorio   = sanitize_text_field( (string) $request->get_param( 'territorio' ) );
			$fecha_inicio = self::sanitize_date( (string) $request->get_param( 'fecha_inicio' ) );
			$fecha_fin    = self::sanitize_date( (string) $request->get_param( 'fecha_fin' ) );

			$args = array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => $per_page,
				'paged'          => $page,
			);

			if ( '' !== $territorio ) {
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					array(
						'taxonomy' => self::TAX_TERRITORIO,
						'field'    => is_numeric( $territorio ) ? 'term_id' : 'slug',
						'terms'    => is_numeric( $territorio ) ? (int) $territorio : $territorio,
					),
				);
			}

			if ( '' !== $fecha_inicio || '' !== $fecha_fin ) {
				$meta_query = array( 'relation' => 'AND' );

				if ( '' !== $fecha_fin ) {
					$meta_query[] = array(
						'key'     => 'madrural_fecha_inicio',
						'value'   => $fecha_fin,
						'compare' => '<=',
						'type'    => 'DATE',
					);
				}

				if ( '' !== $fecha_inicio ) {
					$meta_query[] = array(
						'key'     => 'madrural_fecha_fin',
						'value'   => $fecha_inicio,
						'compare' => '>=',
						'type'    => 'DATE',
					);
				}

				$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			$query  = new WP_Query( $args );
			$events = array();

			foreach ( $query->posts as $post ) {
				$events[] = self::prepare_event_payload( $post );
			}

			return rest_ensure_response(
				array(
					'items'       => $events,
					'total'       => (int) $query->found_posts,
					'total_pages' => (int) $query->max_num_pages,
					'page'        => $page,
				)
			);
		}

		/**
		 * POST /madrural/v1/eventos
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function rest_create_evento( WP_REST_Request $request ) {
			$data = $request->get_json_params();
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$postarr = self::map_request_to_postarr( $data, 0, null );

			if ( '' === $postarr['post_title'] ) {
				return new WP_Error( 'madrural_missing_title', esc_html__( 'El campo título es obligatorio.', 'madrural-eventos' ), array( 'status' => 400 ) );
			}

			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			self::save_rest_event_meta_and_terms( $post_id, $data );
			self::translate_and_store_event_english_meta( $post_id );

			return rest_ensure_response(
				array(
					'message' => esc_html__( 'Evento creado correctamente.', 'madrural-eventos' ),
					'event'   => self::prepare_event_payload( get_post( $post_id ) ),
				)
			);
		}

		/**
		 * PUT/PATCH /madrural/v1/eventos/{id}
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function rest_update_evento( WP_REST_Request $request ) {
			$post_id = (int) $request->get_param( 'id' );
			$post    = get_post( $post_id );
			if ( ! $post || self::CPT !== $post->post_type ) {
				return new WP_Error( 'madrural_event_not_found', esc_html__( 'Evento no encontrado.', 'madrural-eventos' ), array( 'status' => 404 ) );
			}

			$data = $request->get_json_params();
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$postarr = self::map_request_to_postarr( $data, $post_id, $post );
			$result  = wp_update_post( $postarr, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			self::save_rest_event_meta_and_terms( $post_id, $data );
			self::translate_and_store_event_english_meta( $post_id );

			return rest_ensure_response(
				array(
					'message' => esc_html__( 'Evento actualizado correctamente.', 'madrural-eventos' ),
					'event'   => self::prepare_event_payload( get_post( $post_id ) ),
				)
			);
		}

		/**
		 * DELETE /madrural/v1/eventos/{id}
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function rest_delete_evento( WP_REST_Request $request ) {
			$post_id = (int) $request->get_param( 'id' );
			$post    = get_post( $post_id );
			if ( ! $post || self::CPT !== $post->post_type ) {
				return new WP_Error( 'madrural_event_not_found', esc_html__( 'Evento no encontrado.', 'madrural-eventos' ), array( 'status' => 404 ) );
			}

			$result = wp_trash_post( $post_id );
			if ( ! $result ) {
				return new WP_Error( 'madrural_event_delete_failed', esc_html__( 'No se pudo eliminar el evento.', 'madrural-eventos' ), array( 'status' => 500 ) );
			}

			return rest_ensure_response(
				array(
					'message' => esc_html__( 'Evento enviado a la papelera.', 'madrural-eventos' ),
					'id'      => $post_id,
				)
			);
		}

		/**
		 * Registers plugin shortcodes.
		 *
		 * @return void
		 */
		public static function register_shortcodes() {
			add_shortcode( 'madrural_eventos_list', array( __CLASS__, 'shortcode_eventos_list' ) );
			add_shortcode( 'madrural_eventos_lista', array( __CLASS__, 'shortcode_eventos_list' ) );
			add_shortcode( 'madrural_mis_eventos', array( __CLASS__, 'shortcode_mis_eventos' ) );
			add_shortcode( 'madrural_evento_form', array( __CLASS__, 'shortcode_evento_form' ) );
			add_shortcode( 'madrural_eventos_formulario', array( __CLASS__, 'shortcode_evento_form' ) );
		}

		/**
		 * Handles frontend create/update/delete actions.
		 *
		 * @return void
		 */
		public static function handle_frontend_requests() {
			if ( isset( $_POST['madrural_front_action'] ) && 'save_event' === sanitize_text_field( wp_unslash( $_POST['madrural_front_action'] ) ) ) {
				self::process_frontend_event_save();
			}

			if ( isset( $_GET['me_action'] ) && 'delete' === sanitize_text_field( wp_unslash( $_GET['me_action'] ) ) ) {
				self::process_frontend_event_delete();
			}
		}

		/**
		 * Handles frontend save event action.
		 *
		 * @return void
		 */
		public static function process_frontend_event_save() {
			if ( ! isset( $_POST['madrural_front_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['madrural_front_nonce'] ) ), 'madrural_front_save_event' ) ) {
				self::redirect_with_notice( 'error_nonce' );
			}

			if ( ! self::user_can_manage_events() ) {
				self::redirect_with_notice( 'error_permissions' );
			}

			$event_id = isset( $_POST['madrural_event_id'] ) ? (int) $_POST['madrural_event_id'] : 0;
			$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$content  = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
			$ubicacion_raw = isset( $_POST['ubicacion'] ) ? (string) wp_unslash( $_POST['ubicacion'] ) : '';
			$ubicacion     = self::sanitize_location_url( $ubicacion_raw );

			if ( '' === $title ) {
				self::redirect_with_notice( 'error_title', $event_id );
			}

			if ( '' !== trim( $ubicacion_raw ) && '' === $ubicacion ) {
				self::redirect_with_notice( 'error_ubicacion_url', $event_id );
			}

			$existing_post = null;
			if ( $event_id > 0 ) {
				$existing_post = get_post( $event_id );
				if ( ! $existing_post instanceof WP_Post || self::CPT !== $existing_post->post_type || ! self::user_can_access_event( $event_id, 'edit' ) ) {
					self::redirect_with_notice( 'error_permissions' );
				}
			}

			$data = array(
				'title'            => $title,
				'description'      => $content,
				'estado_moderacion' => isset( $_POST['estado_moderacion'] ) ? sanitize_text_field( wp_unslash( $_POST['estado_moderacion'] ) ) : 'pendiente',
				'fecha_inicio'     => isset( $_POST['fecha_inicio'] ) ? sanitize_text_field( wp_unslash( $_POST['fecha_inicio'] ) ) : '',
				'fecha_fin'        => isset( $_POST['fecha_fin'] ) ? sanitize_text_field( wp_unslash( $_POST['fecha_fin'] ) ) : '',
				'hora_evento'      => isset( $_POST['hora_evento'] ) ? sanitize_text_field( wp_unslash( $_POST['hora_evento'] ) ) : '',
				'ubicacion'        => $ubicacion,
				'galeria_ids'      => isset( $_POST['madrural_galeria_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['madrural_galeria_ids'] ) ) : '',
			);

			$postarr = self::map_request_to_postarr( $data, $event_id, $existing_post );
			$post_id = wp_insert_post( $postarr, true );

			if ( is_wp_error( $post_id ) ) {
				self::redirect_with_notice( 'error_save', $event_id );
			}

			$data['categorias'] = isset( $_POST['categorias'] ) ? self::sanitize_term_input_list( wp_unslash( $_POST['categorias'] ) ) : array();
			$data['categorias'] = self::merge_with_new_terms( self::TAX_CATEGORIA, $data['categorias'], isset( $_POST['nuevas_categorias'] ) ? wp_unslash( $_POST['nuevas_categorias'] ) : '' );

			$territorio_term_id = self::normalize_single_territorio_term_id( isset( $_POST['territorio'] ) ? wp_unslash( $_POST['territorio'] ) : '' );
			if ( $territorio_term_id <= 0 ) {
				self::redirect_with_notice( 'error_territorio', $event_id );
			}
			$data['territorios'] = array( $territorio_term_id );

			$remove_gallery_ids = isset( $_POST['madrural_galeria_remove_ids'] ) ? self::sanitize_image_ids( wp_unslash( $_POST['madrural_galeria_remove_ids'] ) ) : array();

			if ( ! empty( $remove_gallery_ids ) ) {
				$data['galeria_ids'] = array_values( array_diff( self::sanitize_image_ids( $data['galeria_ids'] ), $remove_gallery_ids ) );
			}

			if ( isset( $_FILES['madrural_event_images'] ) && ! empty( $_FILES['madrural_event_images']['name'][0] ) ) {
				$data['galeria_ids'] = array_merge( self::sanitize_image_ids( $data['galeria_ids'] ), self::handle_frontend_gallery_uploads( $_FILES['madrural_event_images'], $post_id ) );
			}

			$data = apply_filters( 'madrural_eventos_frontend_save_data', $data, $post_id, $event_id );

			self::save_rest_event_meta_and_terms( $post_id, $data );
			self::translate_and_store_event_english_meta( $post_id );

			self::redirect_with_notice( 'saved', (int) $post_id );
		}

		/**
		 * Handles frontend delete event action.
		 *
		 * @return void
		 */
		public static function process_frontend_event_delete() {
			$event_id = isset( $_GET['event_id'] ) ? (int) $_GET['event_id'] : 0;

			if ( $event_id <= 0 || ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'madrural_delete_event_' . $event_id ) ) {
				self::redirect_with_notice( 'error_nonce' );
			}

			if ( ! self::user_can_manage_events() || ! self::user_can_access_event( $event_id, 'delete' ) ) {
				self::redirect_with_notice( 'error_permissions' );
			}

			$post = get_post( $event_id );
			if ( ! $post instanceof WP_Post || self::CPT !== $post->post_type ) {
				self::redirect_with_notice( 'error_not_found' );
			}

			wp_trash_post( $event_id );
			self::delete_event_row( $event_id );
			self::redirect_with_notice( 'deleted' );
		}

		/**
		 * Renders frontend list shortcode.
		 *
		 * @param array $atts Shortcode attributes.
		 * @return string
		 */
		public static function shortcode_eventos_list( $atts ) {
			$atts = shortcode_atts(
				array(
					'per_page' => 12,
				),
				$atts,
				'madrural_eventos_list'
			);

			$territorio_input   = isset( $_GET['me_territorio'] ) ? wp_unslash( $_GET['me_territorio'] ) : '';
			$territorio_term_id = self::normalize_single_territorio_term_id( $territorio_input );
			$categoria          = isset( $_GET['me_categoria'] ) ? sanitize_text_field( wp_unslash( $_GET['me_categoria'] ) ) : '';
			$desde              = isset( $_GET['me_desde'] ) ? self::sanitize_date( wp_unslash( $_GET['me_desde'] ) ) : '';
			$hasta              = isset( $_GET['me_hasta'] ) ? self::sanitize_date( wp_unslash( $_GET['me_hasta'] ) ) : '';

			$current_page = isset( $_GET['me_paged'] ) ? max( 1, (int) wp_unslash( $_GET['me_paged'] ) ) : 1;

			$args = array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => max( 1, (int) $atts['per_page'] ),
				'paged'          => $current_page,
			);

			$tax_query = array();
			if ( $territorio_term_id > 0 ) {
				$tax_query[] = array(
					'taxonomy' => self::TAX_TERRITORIO,
					'field'    => 'term_id',
					'terms'    => $territorio_term_id,
				);
			}

			if ( '' !== $categoria ) {
				$tax_query[] = array(
					'taxonomy' => self::TAX_CATEGORIA,
					'field'    => is_numeric( $categoria ) ? 'term_id' : 'slug',
					'terms'    => is_numeric( $categoria ) ? (int) $categoria : $categoria,
				);
			}

			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			}

			if ( '' !== $desde || '' !== $hasta ) {
				$meta_query = array( 'relation' => 'AND' );

				if ( '' !== $hasta ) {
					$meta_query[] = array(
						'key'     => 'madrural_fecha_inicio',
						'value'   => $hasta,
						'compare' => '<=',
						'type'    => 'DATE',
					);
				}

				if ( '' !== $desde ) {
					$meta_query[] = array(
						'key'     => 'madrural_fecha_fin',
						'value'   => $desde,
						'compare' => '>=',
						'type'    => 'DATE',
					);
				}

				$args['meta_query'] = $meta_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			$query       = new WP_Query( $args );
			$territorios = self::get_fixed_territorio_terms();
			$categorias  = get_terms( array( 'taxonomy' => self::TAX_CATEGORIA, 'hide_empty' => false ) );

			ob_start();
			echo self::render_views_header( 'agenda' );
			?>
			<div class="madrural-plugin-content">
			<h2 class="madrural-view-heading"><?php echo esc_html__( 'Agenda', 'madrural-eventos' ); ?></h2>
			<form method="get" class="madrural-eventos-filtros">
				<p>
					<label for="me_territorio"><?php echo esc_html__( 'Territorio', 'madrural-eventos' ); ?></label>
					<select id="me_territorio" name="me_territorio">
						<option value=""><?php echo esc_html__( 'Todos', 'madrural-eventos' ); ?></option>
						<?php
						if ( ! is_wp_error( $territorios ) ) {
							foreach ( $territorios as $term ) {
								echo '<option value="' . esc_attr( (string) $term->term_id ) . '" ' . selected( $territorio_term_id, (int) $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
							}
						}
						?>
					</select>
				</p>
				<p>
					<label for="me_categoria"><?php echo esc_html__( 'Categoría', 'madrural-eventos' ); ?></label>
					<select id="me_categoria" name="me_categoria">
						<option value=""><?php echo esc_html__( 'Todas', 'madrural-eventos' ); ?></option>
						<?php
						if ( ! is_wp_error( $categorias ) ) {
							foreach ( $categorias as $term ) {
								echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $categoria, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
							}
						}
						?>
					</select>
				</p>
				<p>
					<label for="me_desde"><?php echo esc_html__( 'Desde', 'madrural-eventos' ); ?></label>
					<input type="date" id="me_desde" name="me_desde" value="<?php echo esc_attr( self::sanitize_date( (string) $desde ) ); ?>" title="<?php echo esc_attr__( 'Selecciona la fecha', 'madrural-eventos' ); ?>">
				</p>
				<p>
					<label for="me_hasta"><?php echo esc_html__( 'Hasta', 'madrural-eventos' ); ?></label>
					<input type="date" id="me_hasta" name="me_hasta" value="<?php echo esc_attr( self::sanitize_date( (string) $hasta ) ); ?>" title="<?php echo esc_attr__( 'Selecciona la fecha', 'madrural-eventos' ); ?>">
				</p>
				<p>
					<button type="submit"><?php echo esc_html__( 'Filtrar', 'madrural-eventos' ); ?></button>
				</p>
			</form>
			<div class="madrural-eventos-listado">
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); ?>
						<?php
						$territorio_terms  = wp_get_post_terms( get_the_ID(), self::TAX_TERRITORIO, array( 'fields' => 'names' ) );
						$territorio_label  = ( ! is_wp_error( $territorio_terms ) && ! empty( $territorio_terms ) ) ? (string) $territorio_terms[0] : '';
						$event_description = wp_strip_all_tags( (string) get_the_content() );
						$event_description = wp_trim_words( $event_description, 24, '…' );
						?>
						<article class="madrural-evento-item">
							<a class="madrural-evento-card-link" href="<?php echo esc_url( get_permalink() ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Ver detalles de %s', 'madrural-eventos' ), get_the_title() ) ); ?>"></a>
							<?php echo self::render_event_gallery_carousel( get_the_ID(), 'medium_large' ); ?>
							<?php if ( '' !== $territorio_label ) : ?>
								<p class="madrural-evento-territorio"><?php echo esc_html( $territorio_label ); ?></p>
							<?php endif; ?>
							<p class="madrural-evento-title-card"><?php echo esc_html( get_the_title() ); ?></p>
							<?php if ( '' !== $event_description ) : ?>
								<p class="madrural-evento-descripcion"><?php echo esc_html( $event_description ); ?></p>
							<?php endif; ?>
						</article>
					<?php endwhile; ?>
				<?php else : ?>
					<p><?php echo esc_html__( 'No hay eventos para los filtros seleccionados.', 'madrural-eventos' ); ?></p>
				<?php endif; ?>
			</div>
			<?php echo self::render_frontend_pagination( $current_page, (int) $query->max_num_pages, 'me_paged' ); ?>
			</div>
			</div>
			<?php
			wp_reset_postdata();
			return (string) ob_get_clean();
		}

		/**
		 * Renders "gestión de eventos" shortcode.
		 *
		 * @return string
		 */
		public static function shortcode_mis_eventos() {
			if ( ! self::user_can_manage_events() ) {
				$login_url = self::get_external_auth_login_url();
				if ( '' === $login_url ) {
					$login_url = home_url( '/acceso-gestores/' );
				}

				if ( '' !== $login_url && ! headers_sent() ) {
					wp_safe_redirect( $login_url );
					exit;
				}

				return '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $login_url ) ) . ');</script><noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $login_url ) . '"></noscript>';
			}

			$user_id     = get_current_user_id();
			$form_url    = self::get_frontend_page_url( 'form', get_permalink() );
			$dashboard   = self::get_frontend_page_url( 'mis', get_permalink() );
			$current_page = isset( $_GET['me_paged'] ) ? max( 1, (int) wp_unslash( $_GET['me_paged'] ) ) : 1;
			$per_page     = 10;
			$query_args  = array(
				'post_type'      => self::CPT,
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'author'         => $user_id,
				'posts_per_page' => $per_page,
				'paged'          => $current_page,
			);
			$query_args  = apply_filters( 'madrural_eventos_mis_eventos_query_args', $query_args, $user_id );
			if ( ! isset( $query_args['posts_per_page'] ) || (int) $query_args['posts_per_page'] <= 0 ) {
				$query_args['posts_per_page'] = $per_page;
			}
			$query_args['paged'] = $current_page;
			$events_query = new WP_Query( $query_args );
			$events       = $events_query->posts;

			ob_start();
			echo self::render_views_header( 'mis' );
			?>
			<div class="madrural-plugin-content">
			<h2 class="madrural-view-heading"><?php echo esc_html__( 'Gestión de Eventos', 'madrural-eventos' ); ?></h2>
			<?php echo self::render_notice_message(); ?>
			<p><a href="<?php echo esc_url( add_query_arg( 'me_action', 'new', $form_url ) ); ?>"><?php echo esc_html__( 'Crear nuevo evento', 'madrural-eventos' ); ?></a></p>
			<table class="madrural-mis-eventos" style="width:100%;border-collapse:collapse;">
				<thead>
				<tr>
					<th><?php echo esc_html__( 'Título', 'madrural-eventos' ); ?></th>
					<th><?php echo esc_html__( 'Estado', 'madrural-eventos' ); ?></th>
					<th><?php echo esc_html__( 'Territorio', 'madrural-eventos' ); ?></th>
					<th><?php echo esc_html__( 'Fecha', 'madrural-eventos' ); ?></th>
					<th><?php echo esc_html__( 'Acciones', 'madrural-eventos' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( ! empty( $events ) ) : ?>
					<?php foreach ( $events as $event ) : ?>
						<?php
						$edit_url   = add_query_arg( array( 'me_action' => 'edit', 'me_event_id' => $event->ID ), $form_url );
						$delete_url = add_query_arg(
							array(
								'me_action' => 'delete',
								'event_id'  => $event->ID,
								'_wpnonce'  => wp_create_nonce( 'madrural_delete_event_' . $event->ID ),
							),
							$dashboard
						);
						$territorio_terms = wp_get_post_terms( $event->ID, self::TAX_TERRITORIO, array( 'fields' => 'names' ) );
						$territorio_label = ( is_wp_error( $territorio_terms ) || empty( $territorio_terms ) ) ? '-' : (string) $territorio_terms[0];
						?>
						<tr>
							<td><?php echo esc_html( get_the_title( $event ) ); ?></td>
							<td><?php echo esc_html( (string) get_post_meta( $event->ID, 'madrural_estado_moderacion', true ) ); ?></td>
							<td><?php echo esc_html( $territorio_label ); ?></td>
							<td><?php echo esc_html( self::format_date_for_display( (string) get_post_meta( $event->ID, 'madrural_fecha_inicio', true ) ) ); ?></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html__( 'Editar', 'madrural-eventos' ); ?></a>
								 |
								<a href="<?php echo esc_url( $delete_url ); ?>" class="madrural-eventos-confirm-link" data-confirm-message="<?php echo esc_attr__( '¿Seguro que deseas eliminar este evento?', 'madrural-eventos' ); ?>"><?php echo esc_html__( 'Eliminar', 'madrural-eventos' ); ?></a>
								 |
								<a href="<?php echo esc_url( get_permalink( $event ) ); ?>"><?php echo esc_html__( 'Ver', 'madrural-eventos' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr><td colspan="5"><?php echo esc_html__( 'No has creado eventos todavía.', 'madrural-eventos' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
			<?php echo self::render_frontend_pagination( $current_page, (int) $events_query->max_num_pages, 'me_paged' ); ?>
			</div>
			</div>
			<?php
			wp_reset_postdata();
			return (string) ob_get_clean();
		}

		/**
		 * Renders frontend event form shortcode.
		 *
		 * @return string
		 */
		public static function shortcode_evento_form() {
			if ( ! self::user_can_manage_events() ) {
				$login_url = self::get_external_auth_login_url();
				if ( '' === $login_url ) {
					$login_url = home_url( '/acceso-gestores/' );
				}

				if ( '' !== $login_url && ! headers_sent() ) {
					wp_safe_redirect( $login_url );
					exit;
				}

				return '<script>window.location.replace(' . wp_json_encode( esc_url_raw( $login_url ) ) . ');</script><noscript><meta http-equiv="refresh" content="0;url=' . esc_url( $login_url ) . '"></noscript>';
			}

			$event_id = isset( $_GET['me_event_id'] ) ? (int) $_GET['me_event_id'] : 0;
			$post     = null;

			if ( $event_id > 0 ) {
				$post = get_post( $event_id );
				if ( ! $post instanceof WP_Post || self::CPT !== $post->post_type || ! self::user_can_access_event( $event_id, 'edit' ) ) {
					return '<p>' . esc_html__( 'No tienes permisos para editar este evento.', 'madrural-eventos' ) . '</p>';
				}
			}

			$title       = $post instanceof WP_Post ? $post->post_title : '';
			$description = $post instanceof WP_Post ? $post->post_content : '';
			$fecha_inicio = $post instanceof WP_Post ? (string) get_post_meta( $post->ID, 'madrural_fecha_inicio', true ) : '';
			$fecha_fin    = $post instanceof WP_Post ? (string) get_post_meta( $post->ID, 'madrural_fecha_fin', true ) : '';
			$hora_evento  = $post instanceof WP_Post ? (string) get_post_meta( $post->ID, 'madrural_hora_evento', true ) : '';
			$ubicacion    = $post instanceof WP_Post ? (string) get_post_meta( $post->ID, 'madrural_ubicacion', true ) : '';
			$estado       = $post instanceof WP_Post ? (string) get_post_meta( $post->ID, 'madrural_estado_moderacion', true ) : 'pendiente';
			$galeria_ids  = $post instanceof WP_Post ? self::get_event_gallery_ids( $post->ID ) : array();
			$sel_cats     = $post instanceof WP_Post ? wp_get_post_terms( $post->ID, self::TAX_CATEGORIA, array( 'fields' => 'ids' ) ) : array();
			$sel_ters     = $post instanceof WP_Post ? wp_get_post_terms( $post->ID, self::TAX_TERRITORIO, array( 'fields' => 'ids' ) ) : array();

			$cats = get_terms( array( 'taxonomy' => self::TAX_CATEGORIA, 'hide_empty' => false ) );
			$ters = self::get_fixed_territorio_terms();
			$ters = apply_filters( 'madrural_eventos_frontend_form_territorios', $ters, $event_id, $post );

			$selected_territorio = ! empty( $sel_ters ) ? (int) $sel_ters[0] : 0;
			if ( $selected_territorio > 0 && is_array( $ters ) ) {
				$allowed_ids = array();
				foreach ( $ters as $term ) {
					if ( $term instanceof WP_Term ) {
						$allowed_ids[] = (int) $term->term_id;
					}
				}
				if ( ! in_array( $selected_territorio, $allowed_ids, true ) ) {
					$selected_territorio = 0;
				}
			}

			ob_start();
			echo self::render_views_header( 'form' );
			?>
			<div class="madrural-plugin-content">
			<h2 class="madrural-view-heading"><?php echo esc_html__( 'Evento', 'madrural-eventos' ); ?></h2>
			<?php echo self::render_notice_message(); ?>
			<form method="post" class="madrural-evento-formulario" enctype="multipart/form-data">
				<?php wp_nonce_field( 'madrural_front_save_event', 'madrural_front_nonce' ); ?>
				<input type="hidden" name="madrural_front_action" value="save_event">
				<input type="hidden" name="madrural_event_id" value="<?php echo esc_attr( (string) $event_id ); ?>">
				<input type="hidden" name="madrural_galeria_ids" value="<?php echo esc_attr( implode( ',', $galeria_ids ) ); ?>">
				<p>
					<label for="madrural_title"><strong><?php echo esc_html__( 'Título', 'madrural-eventos' ); ?></strong></label><br>
					<input type="text" id="madrural_title" name="title" required value="<?php echo esc_attr( $title ); ?>" style="width:100%;">
				</p>
				<p>
					<label for="madrural_description"><strong><?php echo esc_html__( 'Descripción', 'madrural-eventos' ); ?></strong></label><br>
					<textarea id="madrural_description" name="description" rows="6" style="width:100%;"><?php echo esc_textarea( $description ); ?></textarea>
				</p>
				<p>
					<label for="madrural_fecha_inicio_front"><strong><?php echo esc_html__( 'Fecha inicio', 'madrural-eventos' ); ?></strong></label><br>
					<input type="date" id="madrural_fecha_inicio_front" name="fecha_inicio" value="<?php echo esc_attr( self::sanitize_date( (string) $fecha_inicio ) ); ?>" title="<?php echo esc_attr__( 'Selecciona la fecha', 'madrural-eventos' ); ?>">
				</p>
				<p>
					<label for="madrural_fecha_fin_front"><strong><?php echo esc_html__( 'Fecha fin', 'madrural-eventos' ); ?></strong></label><br>
					<input type="date" id="madrural_fecha_fin_front" name="fecha_fin" value="<?php echo esc_attr( self::sanitize_date( (string) $fecha_fin ) ); ?>" title="<?php echo esc_attr__( 'Selecciona la fecha', 'madrural-eventos' ); ?>">
				</p>
				<p>
					<label for="madrural_hora_evento_front"><strong><?php echo esc_html__( 'Hora', 'madrural-eventos' ); ?></strong></label><br>
					<input type="time" id="madrural_hora_evento_front" name="hora_evento" value="<?php echo esc_attr( $hora_evento ); ?>">
				</p>
				<p>
					<label for="madrural_ubicacion_front"><strong><?php echo esc_html__( 'Ubicación', 'madrural-eventos' ); ?></strong></label><br>
					<input type="url" id="madrural_ubicacion_front" name="ubicacion" value="<?php echo esc_attr( $ubicacion ); ?>" placeholder="<?php echo esc_attr__( 'https://maps.google.com/...', 'madrural-eventos' ); ?>" style="width:100%;">
				</p>
				<p>
					<label for="madrural_estado_front"><strong><?php echo esc_html__( 'Estado de moderación', 'madrural-eventos' ); ?></strong></label><br>
					<select id="madrural_estado_front" name="estado_moderacion">
						<option value="borrador" <?php selected( $estado, 'borrador' ); ?>><?php echo esc_html__( 'Borrador', 'madrural-eventos' ); ?></option>
						<option value="pendiente" <?php selected( $estado, 'pendiente' ); ?>><?php echo esc_html__( 'Pendiente', 'madrural-eventos' ); ?></option>
						<option value="publicado" <?php selected( $estado, 'publicado' ); ?>><?php echo esc_html__( 'Publicado', 'madrural-eventos' ); ?></option>
					</select>
				</p>

				<fieldset>
					<legend><?php echo esc_html__( 'Imágenes del evento', 'madrural-eventos' ); ?></legend>
					<input type="hidden" name="madrural_galeria_remove_ids" id="madrural_galeria_remove_ids" value="">
					<input type="file" id="madrural_event_images" name="madrural_event_images[]" multiple accept="image/*" style="display:none;">
					<button type="button" class="madrural-add-image-input" id="madrural-add-images-trigger"><?php echo esc_html__( 'Añadir imágenes', 'madrural-eventos' ); ?></button>

					<div class="madrural-galeria-preview" id="madrural-existing-gallery">
						<?php foreach ( $galeria_ids as $img_id ) : ?>
							<?php $thumb = wp_get_attachment_image_url( $img_id, 'thumbnail' ); ?>
							<?php if ( $thumb ) : ?>
								<div class="madrural-upload-item" data-existing-id="<?php echo esc_attr( (string) $img_id ); ?>">
									<img src="<?php echo esc_url( $thumb ); ?>" alt="">
									<span class="madrural-upload-name"><?php echo esc_html( basename( (string) get_attached_file( $img_id ) ) ); ?></span>
									<button type="button" class="madrural-remove-upload"><?php echo esc_html__( 'Remover', 'madrural-eventos' ); ?></button>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>

					<div class="madrural-galeria-preview" id="madrural-new-gallery"></div>
				</fieldset>

				<fieldset>
					<legend><?php echo esc_html__( 'Categorías', 'madrural-eventos' ); ?></legend>
					<select id="madrural_categoria_select" name="categorias[]">
						<option value=""><?php echo esc_html__( 'Seleccionar categoría', 'madrural-eventos' ); ?></option>
						<?php
						if ( ! is_wp_error( $cats ) ) {
							$selected_cat = ! empty( $sel_cats ) ? (int) $sel_cats[0] : 0;
							foreach ( $cats as $term ) {
								echo '<option value="' . esc_attr( (string) $term->term_id ) . '" ' . selected( $selected_cat, (int) $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
							}
						}
						?>
						<option value="__add_new__"><?php echo esc_html__( 'Agregar', 'madrural-eventos' ); ?></option>
					</select>
					<div id="madrural_categoria_add_wrap" style="display:none; margin-top:10px;">
						<input type="text" id="madrural_nueva_categoria_input" placeholder="<?php echo esc_attr__( 'Nueva categoría', 'madrural-eventos' ); ?>">
						<button type="button" id="madrural_add_categoria_btn"><?php echo esc_html__( 'Agregar categoría', 'madrural-eventos' ); ?></button>
					</div>
					<input type="hidden" id="madrural_nuevas_categorias" name="nuevas_categorias" value="">
				</fieldset>

				<p>
					<label for="madrural_territorio_select"><strong><?php echo esc_html__( 'Territorio', 'madrural-eventos' ); ?></strong></label><br>
					<select id="madrural_territorio_select" name="territorio" required>
						<option value=""><?php echo esc_html__( 'Selecciona un territorio', 'madrural-eventos' ); ?></option>
						<?php
						if ( ! is_wp_error( $ters ) ) {
							foreach ( $ters as $term ) {
								echo '<option value="' . esc_attr( (string) $term->term_id ) . '" ' . selected( $selected_territorio, (int) $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
							}
						}
						?>
					</select>
				</p>

				<p>
					<button type="submit"><?php echo esc_html__( 'Guardar evento', 'madrural-eventos' ); ?></button>
				</p>
			</form>
			<script>
			(function(){
				var input = document.getElementById('madrural_event_images');
				var trigger = document.getElementById('madrural-add-images-trigger');
				var newGallery = document.getElementById('madrural-new-gallery');
				var existingGallery = document.getElementById('madrural-existing-gallery');
				var removeIdsInput = document.getElementById('madrural_galeria_remove_ids');
				if (!input || !trigger || !newGallery) {
					return;
				}

				var dt = new DataTransfer();

				function renderNewUploads() {
					newGallery.innerHTML = '';
					Array.prototype.forEach.call(dt.files, function(file, index){
						var card = document.createElement('div');
						card.className = 'madrural-upload-item';

						var img = document.createElement('img');
						img.src = URL.createObjectURL(file);
						img.alt = file.name;

						var name = document.createElement('span');
						name.className = 'madrural-upload-name';
						name.textContent = file.name;

						var remove = document.createElement('button');
						remove.type = 'button';
						remove.className = 'madrural-remove-upload';
						remove.textContent = 'Remover';
						remove.addEventListener('click', function(){
							var fresh = new DataTransfer();
							Array.prototype.forEach.call(dt.files, function(item, itemIndex){
								if (itemIndex !== index) {
									fresh.items.add(item);
								}
							});
							dt = fresh;
							input.files = dt.files;
							renderNewUploads();
						});

						card.appendChild(img);
						card.appendChild(name);
						card.appendChild(remove);
						newGallery.appendChild(card);
					});
				}

				trigger.addEventListener('click', function(){
					input.click();
				});

				input.addEventListener('change', function(){
					Array.prototype.forEach.call(input.files, function(file){
						dt.items.add(file);
					});
					input.files = dt.files;
					renderNewUploads();
				});

				if (existingGallery && removeIdsInput) {
					existingGallery.addEventListener('click', function(e){
						if (!e.target.classList.contains('madrural-remove-upload')) {
							return;
						}
						var card = e.target.closest('.madrural-upload-item');
						if (!card) {
							return;
						}
						var id = card.getAttribute('data-existing-id');
						if (id) {
							var current = removeIdsInput.value ? removeIdsInput.value.split(',') : [];
							if (current.indexOf(id) === -1) {
								current.push(id);
								removeIdsInput.value = current.join(',');
							}
						}
						card.remove();
					});
				}

				function bindAddOption(selectId, wrapId, inputId, buttonId, hiddenId) {
					var select = document.getElementById(selectId);
					var wrap = document.getElementById(wrapId);
					var inputNew = document.getElementById(inputId);
					var buttonAdd = document.getElementById(buttonId);
					var hidden = document.getElementById(hiddenId);
					if (!select || !wrap || !inputNew || !buttonAdd || !hidden) {
						return;
					}

					select.addEventListener('change', function(){
						var show = false;
						if (select.multiple) {
							show = Array.prototype.some.call(select.selectedOptions || [], function(option){
								return option.value === '__add_new__';
							});
						} else {
							show = select.value === '__add_new__';
						}
						wrap.style.display = show ? 'block' : 'none';
					});

					buttonAdd.addEventListener('click', function(){
						var label = inputNew.value.trim();
						if (!label) {
							return;
						}
						var currentValues = hidden.value ? hidden.value.split(',') : [];
						if (currentValues.indexOf(label) === -1) {
							currentValues.push(label);
						}
						hidden.value = currentValues.join(',');
						var option = document.createElement('option');
						option.value = label;
						option.textContent = label;
						option.selected = true;
						var addOption = select.querySelector('option[value="__add_new__"]');
						select.insertBefore(option, addOption);
						if (!select.multiple) {
							select.value = label;
						}
						inputNew.value = '';
						wrap.style.display = 'none';
					});
				}

				bindAddOption('madrural_categoria_select', 'madrural_categoria_add_wrap', 'madrural_nueva_categoria_input', 'madrural_add_categoria_btn', 'madrural_nuevas_categorias');
			})();
			</script>
			</div>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		/**
		 * Checks if current user can manage events.
		 *
		 * @return bool
		 */
		public static function user_can_manage_events() {
			$can = false;

			return (bool) apply_filters( 'madrural_eventos_user_can_manage_events', $can );
		}

		/**
		 * Checks if current user/session can access a specific event.
		 *
		 * @param int    $event_id Event ID.
		 * @param string $action Action key (edit/delete).
		 * @return bool
		 */
		public static function user_can_access_event( $event_id, $action = 'edit' ) {
			$event_id = (int) $event_id;
			if ( $event_id <= 0 ) {
				return false;
			}

			$allowed = false;

			return (bool) apply_filters( 'madrural_eventos_user_can_access_event', $allowed, $event_id, $action );
		}

		/**
		 * Returns external auth login URL.
		 *
		 * @return string
		 */
		public static function get_external_auth_login_url() {
			$url = (string) apply_filters( 'madrural_eventos_login_url', '' );
			if ( '' !== $url ) {
				return $url;
			}

			$page = get_page_by_path( 'acceso-gestores' );
			if ( $page instanceof WP_Post ) {
				$permalink = get_permalink( $page->ID );
				if ( is_string( $permalink ) && '' !== $permalink ) {
					return $permalink;
				}
			}

			$login_pages = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => 'publish',
					's'              => '[madrural_auth_login]',
					'posts_per_page' => 1,
				)
			);

			if ( ! empty( $login_pages ) && $login_pages[0] instanceof WP_Post ) {
				$permalink = get_permalink( $login_pages[0]->ID );
				if ( is_string( $permalink ) && '' !== $permalink ) {
					return $permalink;
				}
			}

			return home_url( '/acceso-gestores/' );
		}

		/**
		 * Returns frontend page URL for plugin pages.
		 *
		 * @param string $key Page key.
		 * @param string $fallback Fallback URL.
		 * @return string
		 */
		public static function get_frontend_page_url( $key, $fallback = '' ) {
			$pages   = get_option( self::OPTION_FRONT_PAGES, array() );
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

			return home_url( '/' );
		}

		/**
		 * Returns fixed territorio labels.
		 *
		 * @return array<string>
		 */
		public static function get_fixed_territorio_labels() {
			return self::FIXED_TERRITORIO_LABELS;
		}

		/**
		 * Ensures fixed territorio terms exist in taxonomy.
		 *
		 * @return void
		 */
		public static function ensure_fixed_territorios_terms() {
			if ( ! taxonomy_exists( self::TAX_TERRITORIO ) ) {
				return;
			}

			foreach ( self::get_fixed_territorio_labels() as $label ) {
				if ( ! term_exists( $label, self::TAX_TERRITORIO ) ) {
					wp_insert_term( $label, self::TAX_TERRITORIO );
				}
			}
		}

		/**
		 * Returns fixed territorio terms in nomenclator order.
		 *
		 * @return array<int,WP_Term>
		 */
		public static function get_fixed_territorio_terms() {
			$terms_by_slug = array();

			foreach ( self::get_fixed_territorio_labels() as $label ) {
				$slug = sanitize_title( $label );
				$term = get_term_by( 'slug', $slug, self::TAX_TERRITORIO );
				if ( $term instanceof WP_Term ) {
					$terms_by_slug[ $slug ] = $term;
				}
			}

			return array_values( $terms_by_slug );
		}

		/**
		 * Redirects to dashboard page with notice.
		 *
		 * @param string $notice Notice key.
		 * @param int    $event_id Event ID.
		 * @return void
		 */
		public static function redirect_with_notice( $notice, $event_id = 0 ) {
			$dashboard_url = self::get_frontend_page_url( 'mis' );

			$args = array( 'me_notice' => sanitize_key( $notice ) );
			if ( $event_id > 0 ) {
				$args['me_event_id'] = (int) $event_id;
			}

			wp_safe_redirect( add_query_arg( $args, $dashboard_url ) );
			exit;
		}

		/**
		 * Renders feedback notice based on query arg.
		 *
		 * @return string
		 */
		public static function render_notice_message() {
			if ( ! isset( $_GET['me_notice'] ) ) {
				return '';
			}

			$notice = sanitize_key( wp_unslash( $_GET['me_notice'] ) );
			$map    = array(
				'saved'             => esc_html__( 'Evento guardado correctamente.', 'madrural-eventos' ),
				'deleted'           => esc_html__( 'Evento eliminado correctamente.', 'madrural-eventos' ),
				'error_nonce'       => esc_html__( 'No se pudo validar la solicitud. Inténtalo de nuevo.', 'madrural-eventos' ),
				'error_permissions' => esc_html__( 'No tienes permisos para realizar esta acción.', 'madrural-eventos' ),
				'error_title'       => esc_html__( 'El título del evento es obligatorio.', 'madrural-eventos' ),
				'error_territorio'  => esc_html__( 'Debes seleccionar un territorio válido.', 'madrural-eventos' ),
				'error_not_found'   => esc_html__( 'El evento no existe.', 'madrural-eventos' ),
				'error_save'        => esc_html__( 'No se pudo guardar el evento.', 'madrural-eventos' ),
				'error_ubicacion_url' => esc_html__( 'Ubicación debe ser una URL válida o quedar vacía.', 'madrural-eventos' ),
			);

			if ( ! isset( $map[ $notice ] ) ) {
				return '';
			}

			return '<div class="madrural-eventos-notice"><p>' . esc_html( $map[ $notice ] ) . '</p></div>';
		}

		/**
		 * Renders shared plugin views header navigation.
		 *
		 * @param string $active Active view key.
		 * @return string
		 */
		public static function render_views_header( $active = 'agenda' ) {
			$can_manage    = self::user_can_manage_events();
			$manage_url    = self::get_frontend_page_url( 'mis' );
			$login_url     = self::get_external_auth_login_url();
			$is_superadmin = class_exists( 'MADRURAL_Auth_Plugin' ) && is_callable( array( 'MADRURAL_Auth_Plugin', 'is_superadmin_profile' ) ) && MADRURAL_Auth_Plugin::is_superadmin_profile();

			$items = array(
				'agenda' => array(
					'label' => esc_html__( 'Agenda', 'madrural-eventos' ),
					'url'   => self::get_frontend_page_url( 'agenda' ),
				),
			);

			if ( $can_manage ) {
				$items['mis'] = array(
					'label' => esc_html__( 'Gestión de Eventos', 'madrural-eventos' ),
					'url'   => '' !== $manage_url ? $manage_url : self::get_frontend_page_url( 'mis' ),
				);
			} else {
				$items['gestionar'] = array(
					'label' => esc_html__( 'Gestionar Eventos', 'madrural-eventos' ),
					'url'   => '' !== $login_url ? $login_url : home_url( '/acceso-gestores/' ),
				);
			}

			if ( $is_superadmin ) {
				$panel_url = class_exists( 'MADRURAL_Auth_Plugin' ) && is_callable( array( 'MADRURAL_Auth_Plugin', 'get_page_url' ) ) ? MADRURAL_Auth_Plugin::get_page_url( 'panel', home_url( '/panel-perfiles-madrural/' ) ) : home_url( '/panel-perfiles-madrural/' );
				$items['perfiles'] = array(
					'label' => esc_html__( 'Perfiles', 'madrural-eventos' ),
					'url'   => $panel_url,
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

			foreach ( $items as $key => $item ) {
				$active_class = ( $active === $key ) ? ' is-active' : '';
				$html        .= '<a class="madrural-plugin-nav-link' . esc_attr( $active_class ) . '" href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			}

			$html .= '</nav>';
			$html .= '</div>';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Renders frontend pagination links.
		 *
		 * @param int    $current_page Current page.
		 * @param int    $total_pages Total pages.
		 * @param string $query_var Query arg name for page.
		 * @return string
		 */
		public static function render_frontend_pagination( $current_page, $total_pages, $query_var = 'me_paged' ) {
			$current_page = max( 1, (int) $current_page );
			$total_pages  = max( 1, (int) $total_pages );
			$is_single_page = ( $total_pages <= 1 );

			$base_url = remove_query_arg( $query_var );

			if ( $is_single_page ) {
				$links = array(
					'<span class="page-numbers prev disabled" aria-disabled="true">' . esc_html__( '« Anterior', 'madrural-eventos' ) . '</span>',
					'<span aria-current="page" class="page-numbers current">1</span>',
					'<span class="page-numbers next disabled" aria-disabled="true">' . esc_html__( 'Siguiente »', 'madrural-eventos' ) . '</span>',
				);
			} else {
				$links = paginate_links(
					array(
						'base'      => esc_url_raw( add_query_arg( $query_var, '%#%', $base_url ) ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => $total_pages,
						'type'      => 'array',
						'prev_next' => true,
						'prev_text' => esc_html__( '« Anterior', 'madrural-eventos' ),
						'next_text' => esc_html__( 'Siguiente »', 'madrural-eventos' ),
					)
				);

				if ( empty( $links ) || ! is_array( $links ) ) {
					return '';
				}
			}

			$html = '<nav class="madrural-pagination" aria-label="' . esc_attr__( 'Paginación', 'madrural-eventos' ) . '">';
			foreach ( $links as $link ) {
				$html .= $link;
			}
			$html .= '</nav>';

			return $html;
		}

		/**
		 * Maps REST payload to post fields.
		 *
		 * @param array $data REST body.
		 * @param int          $post_id Post ID (0 for create).
		 * @param WP_Post|null $existing_post Existing post for partial updates.
		 * @return array
		 */
		public static function map_request_to_postarr( $data, $post_id, $existing_post = null ) {
			$title = '';
			if ( isset( $data['title'] ) ) {
				$title = sanitize_text_field( (string) $data['title'] );
			} elseif ( $existing_post instanceof WP_Post ) {
				$title = $existing_post->post_title;
			}

			$content = '';
			if ( isset( $data['description'] ) ) {
				$content = wp_kses_post( (string) $data['description'] );
			} elseif ( $existing_post instanceof WP_Post ) {
				$content = $existing_post->post_content;
			}

			$status = isset( $data['estado_moderacion'] ) ? self::sanitize_moderation_state( (string) $data['estado_moderacion'] ) : '';

			$post_status_map = array(
				'borrador'  => 'draft',
				'pendiente' => 'pending',
				'publicado' => 'publish',
			);

			$postarr = array(
				'post_type'    => self::CPT,
				'post_title'   => $title,
				'post_content' => $content,
			);

			if ( '' !== $status && isset( $post_status_map[ $status ] ) ) {
				$postarr['post_status'] = $post_status_map[ $status ];
			} elseif ( ! $existing_post instanceof WP_Post ) {
				$postarr['post_status'] = 'draft';
			}

			if ( $post_id > 0 ) {
				$postarr['ID'] = $post_id;
			}

			if ( 0 === $post_id ) {
				$postarr['post_author'] = get_current_user_id();
			}

			return $postarr;
		}

		/**
		 * Saves meta and taxonomy terms from REST payload.
		 *
		 * @param int   $post_id Post ID.
		 * @param array $data REST body.
		 * @return void
		 */
		public static function save_rest_event_meta_and_terms( $post_id, $data ) {
			$meta_map = array(
				'madrural_fecha_inicio'      => isset( $data['fecha_inicio'] ) ? self::sanitize_date( (string) $data['fecha_inicio'] ) : null,
				'madrural_fecha_fin'         => isset( $data['fecha_fin'] ) ? self::sanitize_date( (string) $data['fecha_fin'] ) : null,
				'madrural_hora_evento'       => isset( $data['hora_evento'] ) ? self::sanitize_time( (string) $data['hora_evento'] ) : null,
				'madrural_ubicacion'         => isset( $data['ubicacion'] ) ? self::sanitize_location_url( (string) $data['ubicacion'] ) : null,
				'madrural_estado_moderacion' => isset( $data['estado_moderacion'] ) ? self::sanitize_moderation_state( (string) $data['estado_moderacion'] ) : null,
				'madrural_galeria_ids'       => isset( $data['galeria_ids'] ) ? self::sanitize_image_ids( $data['galeria_ids'] ) : null,
			);

			foreach ( $meta_map as $key => $value ) {
				if ( null !== $value ) {
					update_post_meta( $post_id, $key, $value );
				}
			}

			if ( isset( $meta_map['madrural_galeria_ids'] ) && is_array( $meta_map['madrural_galeria_ids'] ) && ! empty( $meta_map['madrural_galeria_ids'] ) && ! has_post_thumbnail( $post_id ) ) {
				set_post_thumbnail( $post_id, (int) $meta_map['madrural_galeria_ids'][0] );
			}

			if ( isset( $data['categorias'] ) && is_array( $data['categorias'] ) ) {
				$terms = self::sanitize_term_input_list( $data['categorias'] );
				wp_set_post_terms( $post_id, $terms, self::TAX_CATEGORIA, false );
			}

			$territorio_input = null;
			if ( array_key_exists( 'territorio', $data ) ) {
				$territorio_input = $data['territorio'];
			} elseif ( array_key_exists( 'territorios', $data ) ) {
				$territorio_input = $data['territorios'];
			}

			if ( null !== $territorio_input ) {
				$territorio_term_id = self::normalize_single_territorio_term_id( $territorio_input );
				$territorio_terms   = $territorio_term_id > 0 ? array( $territorio_term_id ) : array();
				wp_set_post_terms( $post_id, $territorio_terms, self::TAX_TERRITORIO, false );
			}

			self::sync_event_row_from_post( $post_id );
		}

		/**
		 * Prepares API payload from event post.
		 *
		 * @param WP_Post|null $post Post object.
		 * @return array
		 */
		public static function prepare_event_payload( $post ) {
			if ( ! $post instanceof WP_Post ) {
				return array();
			}

			$post_id = (int) $post->ID;

			$categoria_terms = wp_get_post_terms( $post_id, self::TAX_CATEGORIA, array( 'fields' => 'ids' ) );
			$territorio_terms = wp_get_post_terms( $post_id, self::TAX_TERRITORIO, array( 'fields' => 'ids' ) );
			$gallery_ids      = self::get_event_gallery_ids( $post_id );
			$gallery_urls     = array();

			foreach ( $gallery_ids as $gallery_id ) {
				$url = wp_get_attachment_image_url( $gallery_id, 'full' );
				if ( $url ) {
					$gallery_urls[] = $url;
				}
			}

			$fecha_inicio = (string) self::get_event_storage_value( $post_id, 'fecha_inicio', (string) get_post_meta( $post_id, 'madrural_fecha_inicio', true ) );
			$fecha_fin    = (string) self::get_event_storage_value( $post_id, 'fecha_fin', (string) get_post_meta( $post_id, 'madrural_fecha_fin', true ) );
			$hora_evento  = (string) self::get_event_storage_value( $post_id, 'hora_evento', (string) get_post_meta( $post_id, 'madrural_hora_evento', true ) );
			$ubicacion    = (string) self::get_event_storage_value( $post_id, 'ubicacion', (string) get_post_meta( $post_id, 'madrural_ubicacion', true ) );
			$estado       = (string) self::get_event_storage_value( $post_id, 'estado_moderacion', (string) get_post_meta( $post_id, 'madrural_estado_moderacion', true ) );

			return array(
				'id'               => $post_id,
				'title'            => get_the_title( $post ),
				'description'      => apply_filters( 'the_content', $post->post_content ),
				'link'             => get_permalink( $post ),
				'fecha_inicio'     => $fecha_inicio,
				'fecha_inicio_display' => self::format_date_for_display( $fecha_inicio ),
				'fecha_fin'        => $fecha_fin,
				'fecha_fin_display' => self::format_date_for_display( $fecha_fin ),
				'hora_evento'      => $hora_evento,
				'ubicacion'        => $ubicacion,
				'estado_moderacion' => $estado,
				'galeria_ids'      => $gallery_ids,
				'imagenes'         => $gallery_urls,
				'categorias'       => is_wp_error( $categoria_terms ) ? array() : array_map( 'intval', $categoria_terms ),
				'territorios'      => is_wp_error( $territorio_terms ) ? array() : array_map( 'intval', $territorio_terms ),
			);
		}

		/**
		 * Adds semantic event block to event content.
		 *
		 * @param string $content Content.
		 * @return string
		 */
		public static function render_frontend_event_details( $content ) {
			if ( ! is_singular( self::CPT ) || ! in_the_loop() || ! is_main_query() ) {
				return $content;
			}

			$post_id      = get_the_ID();
			$fecha_inicio = self::get_event_storage_value( $post_id, 'fecha_inicio', get_post_meta( $post_id, 'madrural_fecha_inicio', true ) );
			$fecha_fin    = self::get_event_storage_value( $post_id, 'fecha_fin', get_post_meta( $post_id, 'madrural_fecha_fin', true ) );
			$hora_evento  = self::get_event_storage_value( $post_id, 'hora_evento', get_post_meta( $post_id, 'madrural_hora_evento', true ) );
			$ubicacion    = self::get_event_storage_value( $post_id, 'ubicacion', get_post_meta( $post_id, 'madrural_ubicacion', true ) );
			$estado       = self::get_event_storage_value( $post_id, 'estado_moderacion', get_post_meta( $post_id, 'madrural_estado_moderacion', true ) );
			$categorias   = wp_get_post_terms( $post_id, self::TAX_CATEGORIA, array( 'fields' => 'names' ) );
			$territorios  = wp_get_post_terms( $post_id, self::TAX_TERRITORIO, array( 'fields' => 'names' ) );
			$agenda_url   = self::get_frontend_page_url( 'agenda', get_post_type_archive_link( self::CPT ) );
			$form_url     = self::get_frontend_page_url( 'form' );
			$edit_url     = '';

			if ( self::user_can_access_event( $post_id, 'edit' ) && '' !== $form_url ) {
				$edit_url = add_query_arg(
					array(
						'me_action'   => 'edit',
						'me_event_id' => $post_id,
					),
					$form_url
				);
			}

			$title = get_the_title( $post_id );
			$primary_categoria  = ( ! is_wp_error( $categorias ) && ! empty( $categorias ) ) ? (string) $categorias[0] : '';
			$primary_territorio = ( ! is_wp_error( $territorios ) && ! empty( $territorios ) ) ? (string) $territorios[0] : '';
			$gallery_ids = self::get_event_gallery_ids( $post_id );
			if ( empty( $gallery_ids ) && has_post_thumbnail( $post_id ) ) {
				$gallery_ids = array( (int) get_post_thumbnail_id( $post_id ) );
			}

			$gallery_items = array();
			foreach ( $gallery_ids as $gallery_id ) {
				$thumb_url = wp_get_attachment_image_url( (int) $gallery_id, 'medium_large' );
				$full_url  = wp_get_attachment_image_url( (int) $gallery_id, 'full' );
				if ( ! $thumb_url || ! $full_url ) {
					continue;
				}

				$gallery_items[] = array(
					'thumb' => $thumb_url,
					'full'  => $full_url,
				);
			}

			$can_manage    = self::user_can_manage_events();
			$manage_url    = self::get_frontend_page_url( 'mis' );
			$login_url     = self::get_external_auth_login_url();
			$is_superadmin = class_exists( 'MADRURAL_Auth_Plugin' ) && is_callable( array( 'MADRURAL_Auth_Plugin', 'is_superadmin_profile' ) ) && MADRURAL_Auth_Plugin::is_superadmin_profile();

			$header_items = array(
				'agenda' => array(
					'label' => esc_html__( 'Agenda', 'madrural-eventos' ),
					'url'   => self::get_frontend_page_url( 'agenda' ),
				),
			);

			if ( $can_manage ) {
				$header_items['mis'] = array(
					'label' => esc_html__( 'Gestión de Eventos', 'madrural-eventos' ),
					'url'   => '' !== $manage_url ? $manage_url : self::get_frontend_page_url( 'mis' ),
				);
			} else {
				$header_items['gestionar'] = array(
					'label' => esc_html__( 'Gestionar Eventos', 'madrural-eventos' ),
					'url'   => '' !== $login_url ? $login_url : home_url( '/acceso-gestores/' ),
				);
			}

			if ( $is_superadmin ) {
				$panel_url = class_exists( 'MADRURAL_Auth_Plugin' ) && is_callable( array( 'MADRURAL_Auth_Plugin', 'get_page_url' ) ) ? MADRURAL_Auth_Plugin::get_page_url( 'panel', home_url( '/panel-perfiles-madrural/' ) ) : home_url( '/panel-perfiles-madrural/' );
				$header_items['perfiles'] = array(
					'label' => esc_html__( 'Perfiles', 'madrural-eventos' ),
					'url'   => $panel_url,
				);
			}
			$lottie_src   = trailingslashit( get_stylesheet_directory_uri() ) . 'inc/files/logo_verde.json';

			$details  = '<div class="madrural-plugin-header">';
			$details .= '<div class="madrural-plugin-header-inner">';
			$details .= '<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js" defer></script>';
			$details .= '<a class="madrural-plugin-brand-link" href="' . esc_url( home_url( '/' ) ) . '">';
			$details .= '<span class="wpr-lottie-animations-wrapper madrural-brand-lottie" aria-hidden="true"><lottie-player src="' . esc_url( $lottie_src ) . '" background="transparent" speed="1" autoplay></lottie-player></span>';
			$details .= '<span class="madrural-plugin-brand-copy">';
			$details .= '<strong class="madrural-plugin-brand-title">' . esc_html__( 'MADRURAL', 'madrural-eventos' ) . '</strong>';
			$details .= '<small class="madrural-plugin-brand-subtitle">' . esc_html__( 'El Madrid que no te esperas', 'madrural-eventos' ) . '</small>';
			$details .= '</span>';
			$details .= '</a>';
			$details .= '<nav class="madrural-plugin-nav" aria-label="' . esc_attr__( 'Navegación de eventos', 'madrural-eventos' ) . '">';

			foreach ( $header_items as $header_key => $header_item ) {
				$active_class = ( 'agenda' === $header_key ) ? ' is-active' : '';
				$details     .= '<a class="madrural-plugin-nav-link' . esc_attr( $active_class ) . '" href="' . esc_url( $header_item['url'] ) . '">' . esc_html( $header_item['label'] ) . '</a>';
			}

			$details .= '</nav>';
			$details .= '</div>';
			$details .= '</div>';
			$details .= '<div class="madrural-plugin-content madrural-evento-detail-content">';
			$details .= '<section class="madrural-evento-detail" itemscope itemtype="https://schema.org/Event">';
			$details .= '<div class="madrural-evento-detail-media">';
			$details .= self::render_event_gallery_carousel( $post_id, 'large' );
			$details .= '<div class="madrural-evento-hero-overlay">';
			$details .= '<div class="madrural-evento-hero-badges">';
			if ( '' !== $primary_categoria ) {
				$details .= '<span class="madrural-evento-hero-badge">🏷️ ' . esc_html( $primary_categoria ) . '</span>';
			}
			$details .= '</div>';
			$details .= '<h1 class="madrural-evento-detail-title">' . esc_html( $title ) . '</h1>';
			$details .= '<p class="madrural-evento-hero-meta">';
			if ( '' !== $fecha_inicio ) {
				$details .= '<span>📅 ' . esc_html( self::format_date_for_display( $fecha_inicio ) ) . '</span>';
			}
			if ( '' !== $hora_evento ) {
				$details .= '<span>🕒 ' . esc_html( $hora_evento ) . ' h</span>';
			}
			if ( '' !== $primary_territorio ) {
				$details .= '<span>📍 ' . esc_html( $primary_territorio ) . '</span>';
			}
			$details .= '</p>';
			$details .= '</div>';
			$details .= '</div>';
			$details .= '<div class="madrural-evento-detail-body">';
			$details .= '<div class="madrural-evento-detail-main">';
			$details .= '<h3 class="madrural-evento-detail-section-title">' . esc_html__( 'Sobre el evento', 'madrural-eventos' ) . '</h3>';
			$details .= '<div class="madrural-evento-detail-description">' . $content . '</div>';
			if ( '' !== $ubicacion ) {
				$details .= '<p class="madrural-evento-location-cta-wrap"><a class="madrural-evento-location-btn" href="' . esc_url( $ubicacion ) . '" target="_blank" rel="noopener noreferrer">📍 ' . esc_html__( 'Ver ubicación', 'madrural-eventos' ) . '</a></p>';
			}
			$details .= '</div>';
			$details .= '<aside class="madrural-evento-detail-info">';
			$details .= '<h3 class="madrural-evento-detail-info-title">' . esc_html__( 'Información del evento', 'madrural-eventos' ) . '</h3>';

			if ( '' !== $fecha_inicio ) {
				$details .= '<div class="madrural-evento-info-item"><span class="madrural-evento-info-icon">📅</span><p><strong>' . esc_html__( 'Fecha de inicio', 'madrural-eventos' ) . '</strong><time datetime="' . esc_attr( $fecha_inicio ) . '" itemprop="startDate">' . esc_html( self::format_date_for_display( $fecha_inicio ) ) . '</time></p></div>';
			}

			if ( '' !== $fecha_fin ) {
				$details .= '<div class="madrural-evento-info-item"><span class="madrural-evento-info-icon">📅</span><p><strong>' . esc_html__( 'Fecha de fin', 'madrural-eventos' ) . '</strong><time datetime="' . esc_attr( $fecha_fin ) . '" itemprop="endDate">' . esc_html( self::format_date_for_display( $fecha_fin ) ) . '</time></p></div>';
			}

			if ( '' !== $hora_evento ) {
				$details .= '<div class="madrural-evento-info-item"><span class="madrural-evento-info-icon">🕒</span><p><strong>' . esc_html__( 'Hora', 'madrural-eventos' ) . '</strong><span itemprop="doorTime">' . esc_html( $hora_evento ) . ' h</span></p></div>';
			}

			if ( ! is_wp_error( $categorias ) && ! empty( $categorias ) ) {
				$details .= '<div class="madrural-evento-info-item"><span class="madrural-evento-info-icon">🏷️</span><p><strong>' . esc_html__( 'Categoría', 'madrural-eventos' ) . '</strong><span>' . esc_html( implode( ', ', $categorias ) ) . '</span></p></div>';
			}

			if ( ! is_wp_error( $territorios ) && ! empty( $territorios ) ) {
				$details .= '<div class="madrural-evento-info-item"><span class="madrural-evento-info-icon">🌍</span><p><strong>' . esc_html__( 'Territorio / Entidad gestora', 'madrural-eventos' ) . '</strong><span>' . esc_html( implode( ', ', $territorios ) ) . '</span></p></div>';
			}

			$details .= '</aside>';
			$details .= '</div>';

			if ( ! empty( $gallery_items ) ) {
				$details .= '<section class="madrural-evento-gallery-card">';
				$details .= '<h3 class="madrural-evento-gallery-title">' . esc_html__( 'Galería del evento', 'madrural-eventos' ) . '</h3>';
				$details .= '<div class="madrural-evento-gallery-grid">';
				foreach ( $gallery_items as $gallery_item ) {
					$details .= '<button type="button" class="madrural-evento-gallery-trigger" data-full-src="' . esc_url( $gallery_item['full'] ) . '"><img src="' . esc_url( $gallery_item['thumb'] ) . '" alt="' . esc_attr__( 'Imagen del evento', 'madrural-eventos' ) . '"></button>';
				}
				$details .= '</div>';
				$details .= '</section>';

				$details .= '<div class="madrural-evento-gallery-modal" id="madrural-evento-gallery-modal" aria-hidden="true">';
				$details .= '<button type="button" class="madrural-evento-gallery-modal-close" aria-label="' . esc_attr__( 'Cerrar', 'madrural-eventos' ) . '">×</button>';
				$details .= '<div class="madrural-evento-gallery-modal-body"><img src="" alt="' . esc_attr__( 'Imagen ampliada del evento', 'madrural-eventos' ) . '"></div>';
				$details .= '</div>';
			}

			$details .= '<div class="madrural-evento-actions">';
			if ( '' !== $agenda_url ) {
				$details .= '<a class="madrural-evento-action-link" href="' . esc_url( $agenda_url ) . '">' . esc_html__( 'Ver lista de eventos', 'madrural-eventos' ) . '</a>';
			}
			if ( '' !== $edit_url ) {
				$details .= '<a class="madrural-evento-action-link is-primary" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Editar evento', 'madrural-eventos' ) . '</a>';
			}
			$details .= '</div>';

			$details .= '</section>';
			$details .= '</div>';

			return $details;
		}

		/**
		 * Outputs JSON-LD schema for event pages.
		 *
		 * @return void
		 */
		public static function render_event_schema_json_ld() {
			if ( ! is_singular( self::CPT ) ) {
				return;
			}

			$post_id      = get_the_ID();
			$fecha_inicio = self::get_event_storage_value( $post_id, 'fecha_inicio', get_post_meta( $post_id, 'madrural_fecha_inicio', true ) );
			$fecha_fin    = self::get_event_storage_value( $post_id, 'fecha_fin', get_post_meta( $post_id, 'madrural_fecha_fin', true ) );
			$hora_evento  = self::get_event_storage_value( $post_id, 'hora_evento', get_post_meta( $post_id, 'madrural_hora_evento', true ) );
			$ubicacion    = self::get_event_storage_value( $post_id, 'ubicacion', get_post_meta( $post_id, 'madrural_ubicacion', true ) );

			$start_date = '';
			$end_date   = '';

			if ( '' !== $fecha_inicio ) {
				$start_date = $fecha_inicio;
				if ( '' !== $hora_evento ) {
					$start_date .= 'T' . $hora_evento;
				}
			}

			if ( '' !== $fecha_fin ) {
				$end_date = $fecha_fin;
			}

			$schema = array(
				'@context'    => 'https://schema.org',
				'@type'       => 'Event',
				'name'        => get_the_title( $post_id ),
				'description' => wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ),
				'url'         => get_permalink( $post_id ),
			);

			if ( '' !== $start_date ) {
				$schema['startDate'] = $start_date;
			}

			if ( '' !== $end_date ) {
				$schema['endDate'] = $end_date;
			}

			if ( '' !== $ubicacion ) {
				$schema['location'] = array(
					'@type' => 'Place',
					'name'  => $ubicacion,
				);
			}

			$gallery_ids = self::get_event_gallery_ids( $post_id );
			if ( ! empty( $gallery_ids ) ) {
				$images = array();
				foreach ( $gallery_ids as $gallery_id ) {
					$url = wp_get_attachment_image_url( $gallery_id, 'full' );
					if ( $url ) {
						$images[] = $url;
					}
				}

				if ( ! empty( $images ) ) {
					$schema['image'] = $images;
				}
			} elseif ( has_post_thumbnail( $post_id ) ) {
				$schema['image'] = get_the_post_thumbnail_url( $post_id, 'full' );
			}

			echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>';
		}

		/**
		 * Returns gallery IDs for an event.
		 *
		 * @param int $post_id Post ID.
		 * @return array<int>
		 */
		public static function get_event_gallery_ids( $post_id ) {
			$from_table = self::get_event_storage_value( $post_id, 'galeria_ids', null );
			if ( is_array( $from_table ) ) {
				return self::sanitize_image_ids( $from_table );
			}

			$stored = get_post_meta( $post_id, 'madrural_galeria_ids', true );

			return self::sanitize_image_ids( $stored );
		}

		/**
		 * Returns plugin managed default event image URL.
		 *
		 * @return string
		 */
		public static function get_default_event_image_url() {
			return plugin_dir_url( __FILE__ ) . 'assets/images/evento-default.jpg';
		}

		/**
		 * Renders carousel HTML for event gallery.
		 *
		 * @param int    $post_id Post ID.
		 * @param string $size Image size.
		 * @return string
		 */
		public static function render_event_gallery_carousel( $post_id, $size = 'large' ) {
			$image_ids = self::get_event_gallery_ids( $post_id );
			if ( empty( $image_ids ) && has_post_thumbnail( $post_id ) ) {
				$image_ids = array( (int) get_post_thumbnail_id( $post_id ) );
			}

			$image_urls = array();
			foreach ( $image_ids as $image_id ) {
				$url = wp_get_attachment_image_url( $image_id, $size );
				if ( $url ) {
					$image_urls[] = $url;
				}
			}

			if ( empty( $image_urls ) ) {
				$image_urls[] = self::get_default_event_image_url();
			}

			$carousel_id = 'madrural-carousel-' . (int) $post_id . '-' . wp_rand( 100, 999 );
			$html        = '<div id="' . esc_attr( $carousel_id ) . '" class="madrural-evento-carousel js-madrural-carousel" data-autoplay="true">';
			$html       .= '<div class="madrural-evento-carousel-track">';
			foreach ( $image_urls as $url ) {
				$html .= '<figure class="madrural-evento-slide"><img src="' . esc_url( $url ) . '" alt="' . esc_attr__( 'Imagen del evento', 'madrural-eventos' ) . '"></figure>';
			}
			$html .= '</div>';

			if ( count( $image_urls ) > 1 ) {
				$html .= '<button type="button" class="madrural-carousel-nav madrural-carousel-prev" aria-label="' . esc_attr__( 'Imagen anterior', 'madrural-eventos' ) . '">‹</button>';
				$html .= '<button type="button" class="madrural-carousel-nav madrural-carousel-next" aria-label="' . esc_attr__( 'Imagen siguiente', 'madrural-eventos' ) . '">›</button>';
				$html .= '<div class="madrural-carousel-dots" aria-hidden="true"></div>';
			}
			$html .= '</div>';

			return $html;
		}

		/**
		 * Handles frontend gallery uploads.
		 *
		 * @param array $files Uploaded files array.
		 * @param int   $post_id Event ID.
		 * @return array<int>
		 */
		public static function handle_frontend_gallery_uploads( $files, $post_id ) {
			if ( ! function_exists( 'media_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/media.php';
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

			$uploaded_ids = array();
			$file_count   = isset( $files['name'] ) && is_array( $files['name'] ) ? count( $files['name'] ) : 0;

			for ( $i = 0; $i < $file_count; $i++ ) {
				if ( empty( $files['name'][ $i ] ) ) {
					continue;
				}

				$_FILES['madrural_single_image'] = array(
					'name'     => $files['name'][ $i ],
					'type'     => $files['type'][ $i ],
					'tmp_name' => $files['tmp_name'][ $i ],
					'error'    => $files['error'][ $i ],
					'size'     => $files['size'][ $i ],
				);

				$attachment_id = media_handle_upload( 'madrural_single_image', $post_id );
				if ( ! is_wp_error( $attachment_id ) ) {
					$uploaded_ids[] = (int) $attachment_id;
				}
			}

			if ( ! empty( $uploaded_ids ) && ! has_post_thumbnail( $post_id ) ) {
				set_post_thumbnail( $post_id, $uploaded_ids[0] );
			}

			unset( $_FILES['madrural_single_image'] );

			return $uploaded_ids;
		}

		/**
		 * Formats stored date for dd/mm/yyyy display.
		 *
		 * @param string $value Stored date.
		 * @return string
		 */
		public static function format_date_for_display( $value ) {
			$value = self::sanitize_date( $value );
			if ( '' === $value ) {
				return '';
			}

			$parts = explode( '-', $value );

			return $parts[2] . '/' . $parts[1] . '/' . $parts[0];
		}

		/**
		 * Sanitizes date value in YYYY-MM-DD or dd/mm/yyyy.
		 *
		 * @param string $value Raw date.
		 * @return string
		 */
		public static function sanitize_date( $value ) {
			$value = sanitize_text_field( $value );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
				return $value;
			}

			if ( preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $value ) ) {
				$parts = explode( '/', $value );
				if ( 3 === count( $parts ) ) {
					return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
				}
			}

			return '';
		}

		/**
		 * Sanitizes gallery image IDs.
		 *
		 * @param mixed $value Raw value.
		 * @return array<int>
		 */
		public static function sanitize_image_ids( $value ) {
			if ( is_string( $value ) ) {
				$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
			}

			if ( ! is_array( $value ) ) {
				return array();
			}

			$ids = array_map( 'intval', $value );
			$ids = array_filter(
				$ids,
				function( $id ) {
					return $id > 0;
				}
			);

			return array_values( array_unique( $ids ) );
		}

		/**
		 * Sanitizes taxonomy term input list (IDs or slugs).
		 *
		 * @param mixed $value Raw term list.
		 * @return array
		 */
		public static function sanitize_term_input_list( $value ) {
			if ( ! is_array( $value ) ) {
				$value = array( $value );
			}

			$terms = array();
			foreach ( $value as $item ) {
				$item = sanitize_text_field( (string) $item );
				if ( '' === $item || '__add_new__' === $item ) {
					continue;
				}

				$terms[] = ctype_digit( $item ) ? (int) $item : sanitize_title( $item );
			}

			return array_values( array_unique( $terms ) );
		}

		/**
		 * Normalizes incoming territorio input to a single allowed term ID.
		 *
		 * @param mixed $value Territory term input (id/slug/array).
		 * @return int
		 */
		public static function normalize_single_territorio_term_id( $value ) {
			$allowed_slugs = array_map(
				'sanitize_title',
				self::get_fixed_territorio_labels()
			);

			$items = self::sanitize_term_input_list( $value );
			foreach ( $items as $item ) {
				$term = null;
				if ( is_int( $item ) ) {
					$term = get_term( $item, self::TAX_TERRITORIO );
				} else {
					$term = get_term_by( 'slug', sanitize_title( (string) $item ), self::TAX_TERRITORIO );
				}

				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				if ( in_array( $term->slug, $allowed_slugs, true ) ) {
					return (int) $term->term_id;
				}
			}

			return 0;
		}

		/**
		 * Merges selected terms with new typed values, creating terms when needed.
		 *
		 * @param string $taxonomy Taxonomy slug.
		 * @param array  $selected_terms Selected term IDs/slugs.
		 * @param string $new_terms_raw Comma-separated term names.
		 * @return array
		 */
		public static function merge_with_new_terms( $taxonomy, $selected_terms, $new_terms_raw ) {
			$selected = self::sanitize_term_input_list( $selected_terms );
			$new_raw  = sanitize_text_field( (string) $new_terms_raw );

			if ( '' === $new_raw ) {
				return $selected;
			}

			$labels = array_filter( array_map( 'trim', explode( ',', $new_raw ) ) );
			foreach ( $labels as $label ) {
				$existing = term_exists( $label, $taxonomy );
				if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
					$selected[] = (int) $existing['term_id'];
					continue;
				}

				if ( is_int( $existing ) && $existing > 0 ) {
					$selected[] = (int) $existing;
					continue;
				}

				$created = wp_insert_term( $label, $taxonomy );
				if ( ! is_wp_error( $created ) && isset( $created['term_id'] ) ) {
					$selected[] = (int) $created['term_id'];
				}
			}

			$normalized_ids = array();
			foreach ( self::sanitize_term_input_list( $selected ) as $term_value ) {
				if ( is_int( $term_value ) ) {
					$normalized_ids[] = $term_value;
					continue;
				}

				$found = term_exists( $term_value, $taxonomy );
				if ( is_array( $found ) && isset( $found['term_id'] ) ) {
					$normalized_ids[] = (int) $found['term_id'];
				} elseif ( is_int( $found ) && $found > 0 ) {
					$normalized_ids[] = (int) $found;
				}
			}

			return array_values( array_unique( array_filter( $normalized_ids ) ) );
		}

		/**
		 * Sanitizes time value in HH:MM.
		 *
		 * @param string $value Raw time.
		 * @return string
		 */
		public static function sanitize_time( $value ) {
			$value = sanitize_text_field( $value );
			if ( preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ) {
				return $value;
			}

			return '';
		}

		/**
		 * Sanitizes location URL allowing empty value.
		 *
		 * @param string $value Raw location value.
		 * @return string
		 */
		public static function sanitize_location_url( $value ) {
			$value = trim( (string) $value );
			if ( '' === $value ) {
				return '';
			}

			$sanitized = esc_url_raw( $value, array( 'http', 'https' ) );
			if ( '' === $sanitized || ! filter_var( $sanitized, FILTER_VALIDATE_URL ) ) {
				return '';
			}

			return $sanitized;
		}

		/**
		 * Sanitizes moderation state.
		 *
		 * @param string $value Raw state.
		 * @return string
		 */
		public static function sanitize_moderation_state( $value ) {
			$value   = sanitize_text_field( $value );
			$allowed = array( 'borrador', 'pendiente', 'publicado' );

			if ( in_array( $value, $allowed, true ) ) {
				return $value;
			}

			return 'borrador';
		}

		/**
		 * Returns currently selected category name for an event.
		 *
		 * @param int $post_id Post ID.
		 * @return string
		 */
		public static function get_event_primary_category_name( $post_id ) {
			$terms = wp_get_post_terms( $post_id, self::TAX_CATEGORIA );
			if ( is_wp_error( $terms ) || empty( $terms ) || ! isset( $terms[0] ) || ! $terms[0] instanceof WP_Term ) {
				return '';
			}

			return sanitize_text_field( (string) $terms[0]->name );
		}

		/**
		 * Translates and stores English mirror fields for an evento.
		 *
		 * @param int $post_id Post ID.
		 * @return void
		 */
		public static function translate_and_store_event_english_meta( $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post instanceof WP_Post || self::CPT !== $post->post_type ) {
				return;
			}

			$category_es = self::get_event_primary_category_name( $post_id );

			$translations = array(
				'_madrural_titulo_en'      => array(
					'text'     => (string) $post->post_title,
					'sanitize' => 'sanitize_text_field',
				),
				'_madrural_descripcion_en' => array(
					'text'     => (string) $post->post_content,
					'sanitize' => 'wp_kses_post',
				),
				'_madrural_categoria_en'   => array(
					'text'     => $category_es,
					'sanitize' => 'sanitize_text_field',
				),
			);

			try {
				foreach ( $translations as $meta_key => $config ) {
					$source_text = isset( $config['text'] ) ? (string) $config['text'] : '';
					if ( '' === trim( wp_strip_all_tags( $source_text ) ) ) {
						update_post_meta( $post_id, $meta_key, '' );
						continue;
					}

					$translated = self::translate_text_external( $source_text, 'es', 'en' );
					if ( is_wp_error( $translated ) ) {
						error_log( 'MADRURAL translation error [' . $meta_key . '] for post ' . $post_id . ': ' . $translated->get_error_message() );
						continue;
					}

					$sanitize_callback = isset( $config['sanitize'] ) ? $config['sanitize'] : 'sanitize_text_field';
					update_post_meta( $post_id, $meta_key, call_user_func( $sanitize_callback, (string) $translated ) );
				}
			} catch ( Throwable $exception ) {
				error_log( 'MADRURAL translation exception for post ' . $post_id . ': ' . $exception->getMessage() );
			}

			self::sync_event_row_from_post( $post_id );
		}

		/**
		 * Translates text using external translation API.
		 *
		 * @param string $text Source text.
		 * @param string $source_lang Source language code.
		 * @param string $target_lang Target language code.
		 * @return string|WP_Error
		 */
		public static function translate_text_external( $text, $source_lang = 'es', $target_lang = 'en' ) {
			$text = (string) $text;
			if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
				return '';
			}

			$source_lang = strtoupper( sanitize_text_field( (string) $source_lang ) );
			$target_lang = strtoupper( sanitize_text_field( (string) $target_lang ) );

			if ( defined( 'MADRURAL_TRANSLATE_KEY' ) && '' !== trim( (string) MADRURAL_TRANSLATE_KEY ) ) {
				$endpoint = (string) apply_filters( 'madrural_eventos_translate_endpoint', 'https://api-free.deepl.com/v2/translate' );
				$args     = array(
					'timeout' => 15,
					'body'    => array(
						'auth_key'       => (string) MADRURAL_TRANSLATE_KEY,
						'text'           => array( $text ),
						'source_lang'    => $source_lang,
						'target_lang'    => 'EN' === $target_lang ? 'EN-US' : $target_lang,
						'tag_handling'   => 'html',
						'preserve_formatting' => '1',
					),
				);

				$response = wp_remote_post( esc_url_raw( $endpoint ), $args );
				if ( is_wp_error( $response ) ) {
					return $response;
				}

				$status_code = (int) wp_remote_retrieve_response_code( $response );
				$body        = (string) wp_remote_retrieve_body( $response );
				$data        = json_decode( $body, true );

				if ( $status_code < 200 || $status_code >= 300 ) {
					return new WP_Error( 'madrural_translate_http_error', 'Translation API returned HTTP ' . $status_code );
				}

				if ( ! is_array( $data ) || empty( $data['translations'][0]['text'] ) ) {
					return new WP_Error( 'madrural_translate_invalid_response', 'Translation API response did not include translated text.' );
				}

				return (string) $data['translations'][0]['text'];
			}

			$endpoint = (string) apply_filters( 'madrural_eventos_translate_fallback_endpoint', 'https://api.mymemory.translated.net/get' );
			$args     = array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
			);
			$url      = add_query_arg(
				array(
					'q'        => rawurlencode( $text ),
					'langpair' => strtolower( $source_lang ) . '|' . strtolower( $target_lang ),
				),
				esc_url_raw( $endpoint )
			);

			$response = wp_remote_get( $url, $args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$status_code = (int) wp_remote_retrieve_response_code( $response );
			$body        = (string) wp_remote_retrieve_body( $response );
			$data        = json_decode( $body, true );

			if ( $status_code < 200 || $status_code >= 300 ) {
				return new WP_Error( 'madrural_translate_http_error', 'Translation API returned HTTP ' . $status_code );
			}

			if ( ! is_array( $data ) || empty( $data['responseData']['translatedText'] ) ) {
				return new WP_Error( 'madrural_translate_invalid_response', 'Translation API response did not include translated text.' );
			}

			return (string) $data['responseData']['translatedText'];
		}
	}
}

register_activation_hook( __FILE__, array( 'MADRURAL_Eventos_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'MADRURAL_Eventos_Plugin', 'deactivate' ) );

MADRURAL_Eventos_Plugin::init();

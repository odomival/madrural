<?php
/**
 * Public Event API endpoints.
 *
 * @package MADRURAL_Eventos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MADRURAL_Eventos_API' ) ) {
	/**
	 * Public API class for eventos.
	 */
	class MADRURAL_Eventos_API {
		/**
		 * Token transient prefix.
		 *
		 * @var string
		 */
		const TOKEN_TRANSIENT_PREFIX = 'madrural_api_token_';

		/**
		 * Token ttl in seconds.
		 *
		 * @var int
		 */
		const TOKEN_TTL = 43200;

		/**
		 * Authenticated profile cached for the current REST request.
		 *
		 * @var array|null
		 */
		protected static $current_request_profile = null;

		/**
		 * Initializes hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
			add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
			add_action( 'template_redirect', array( __CLASS__, 'render_swagger_ui_virtual_page' ), 0 );
		}

		/**
		 * Registers public routes.
		 *
		 * @return void
		 */
		public static function register_routes() {
			register_rest_route(
				'madrural/v1',
				'/api-docs',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( __CLASS__, 'get_openapi_spec' ),
						'permission_callback' => '__return_true',
					),
				)
			);

			register_rest_route(
				'madrural/v1',
				'/api-eventos/login',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( __CLASS__, 'login' ),
						'permission_callback' => '__return_true',
					),
				)
			);

			register_rest_route(
				'madrural/v1',
				'/api-eventos',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( __CLASS__, 'get_eventos' ),
						'permission_callback' => '__return_true',
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( __CLASS__, 'create_evento' ),
						'permission_callback' => array( __CLASS__, 'permission_edit_eventos' ),
					),
				)
			);

			register_rest_route(
				'madrural/v1',
				'/api-eventos/(?P<id>\d+)',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( __CLASS__, 'get_evento_by_id' ),
						'permission_callback' => '__return_true',
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( __CLASS__, 'update_evento' ),
						'permission_callback' => array( __CLASS__, 'permission_edit_single_evento' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( __CLASS__, 'delete_evento' ),
						'permission_callback' => array( __CLASS__, 'permission_edit_single_evento' ),
					),
				)
			);
		}

		/**
		 * GET /madrural/v1/api-eventos
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response
		 */
		public static function get_eventos( WP_REST_Request $request ) {
			$per_page = (int) $request->get_param( 'per_page' );
			if ( $per_page <= 0 ) {
				$per_page = 12;
			}
			$per_page = max( 1, min( 100, $per_page ) );

			$page = (int) $request->get_param( 'paged' );
			if ( $page <= 0 ) {
				$page = (int) $request->get_param( 'me_paged' );
			}
			if ( $page <= 0 ) {
				$page = 1;
			}

			$territorio_input = $request->get_param( 'territorio' );
			if ( null === $territorio_input ) {
				$territorio_input = $request->get_param( 'me_territorio' );
			}
			$territorio_term_id = MADRURAL_Eventos_Plugin::normalize_single_territorio_term_id( $territorio_input );

			$categoria = (string) $request->get_param( 'categoria' );
			if ( '' === $categoria ) {
				$categoria = (string) $request->get_param( 'me_categoria' );
			}
			$categoria = sanitize_text_field( $categoria );

			$desde = (string) $request->get_param( 'desde' );
			if ( '' === $desde ) {
				$desde = (string) $request->get_param( 'me_desde' );
			}
			$desde = MADRURAL_Eventos_Plugin::sanitize_date( $desde );

			$hasta = (string) $request->get_param( 'hasta' );
			if ( '' === $hasta ) {
				$hasta = (string) $request->get_param( 'me_hasta' );
			}
			$hasta = MADRURAL_Eventos_Plugin::sanitize_date( $hasta );

			$allowed_territorio_ids = array();
			$fixed_territorios      = MADRURAL_Eventos_Plugin::get_fixed_territorio_terms();
			foreach ( $fixed_territorios as $fixed_territorio ) {
				if ( $fixed_territorio instanceof WP_Term ) {
					$allowed_territorio_ids[] = (int) $fixed_territorio->term_id;
				}
			}

			$args = array(
				'post_type'         => MADRURAL_Eventos_Plugin::CPT,
				'post_status'       => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page'    => $per_page,
				'paged'             => $page,
				'suppress_filters'  => true,
			);

			$tax_query = array();
			if ( $territorio_term_id > 0 ) {
				$tax_query[] = array(
					'taxonomy' => MADRURAL_Eventos_Plugin::TAX_TERRITORIO,
					'field'    => 'term_id',
					'terms'    => $territorio_term_id,
				);
			} elseif ( ! empty( $allowed_territorio_ids ) ) {
				$tax_query[] = array(
					'taxonomy' => MADRURAL_Eventos_Plugin::TAX_TERRITORIO,
					'field'    => 'term_id',
					'terms'    => $allowed_territorio_ids,
					'operator' => 'IN',
				);
			}

			if ( '' !== $categoria ) {
				$tax_query[] = array(
					'taxonomy' => MADRURAL_Eventos_Plugin::TAX_CATEGORIA,
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

			$query = new WP_Query( $args );
			$items = array();

			foreach ( (array) $query->posts as $post ) {
				$items[] = self::map_payload_to_english_fields( MADRURAL_Eventos_Plugin::prepare_event_payload( $post ) );
			}

			return rest_ensure_response(
				array(
					'items'       => $items,
					'total'       => (int) $query->found_posts,
					'total_pages' => (int) $query->max_num_pages,
					'page'        => $page,
					'per_page'    => $per_page,
				)
			);
		}

		/**
		 * GET /madrural/v1/api-eventos/{id}
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function get_evento_by_id( WP_REST_Request $request ) {
			$event_id = (int) $request->get_param( 'id' );
			$post     = get_post( $event_id );
			$allowed_statuses = array( 'publish', 'draft', 'pending', 'future', 'private' );

			if ( ! $post instanceof WP_Post || MADRURAL_Eventos_Plugin::CPT !== $post->post_type ) {
				return new WP_Error( 'madrural_api_evento_not_found', esc_html__( 'Evento no encontrado.', 'madrural-eventos' ), array( 'status' => 404 ) );
			}

			if ( ! in_array( (string) $post->post_status, $allowed_statuses, true ) ) {
				return new WP_Error( 'madrural_api_evento_not_found', esc_html__( 'Evento no encontrado.', 'madrural-eventos' ), array( 'status' => 404 ) );
			}

			return rest_ensure_response( self::map_payload_to_english_fields( MADRURAL_Eventos_Plugin::prepare_event_payload( $post ) ) );
		}

		/**
		 * POST /madrural/v1/api-eventos/login
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function login( WP_REST_Request $request ) {
			if ( ! class_exists( 'MADRURAL_Auth_Plugin' ) || ! is_callable( array( 'MADRURAL_Auth_Plugin', 'get_table_name' ) ) ) {
				return new WP_Error( 'madrural_api_auth_unavailable', esc_html__( 'Autenticación no disponible.', 'madrural-eventos' ), array( 'status' => 500 ) );
			}

			$data = $request->get_json_params();
			if ( ! is_array( $data ) ) {
				$data = $request->get_body_params();
			}
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$name     = isset( $data['name'] ) ? sanitize_text_field( (string) $data['name'] ) : '';
			$password = isset( $data['password'] ) ? (string) $data['password'] : '';

			if ( '' === $name || '' === $password ) {
				return new WP_Error( 'madrural_api_login_missing', esc_html__( 'Los campos name y password son obligatorios.', 'madrural-eventos' ), array( 'status' => 400 ) );
			}

			global $wpdb;
			$table_name = MADRURAL_Auth_Plugin::get_table_name();
			$profile    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE name = %s LIMIT 1", $name ), ARRAY_A );

			if ( ! is_array( $profile ) || empty( $profile['password'] ) || ! wp_check_password( $password, $profile['password'] ) ) {
				return new WP_Error( 'madrural_api_login_invalid', esc_html__( 'Credenciales inválidas.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			$normalized = MADRURAL_Auth_Plugin::normalize_profile( $profile );
			if ( ! is_array( $normalized ) || empty( $normalized['role'] ) ) {
				return new WP_Error( 'madrural_api_login_invalid_profile', esc_html__( 'Perfil inválido.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			$token = self::generate_token();
			if ( '' === $token ) {
				return new WP_Error( 'madrural_api_login_token_error', esc_html__( 'No se pudo generar el token.', 'madrural-eventos' ), array( 'status' => 500 ) );
			}

			$token_key = self::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $token );
			$stored    = array(
				'profile'    => $normalized,
				'issued_at'  => time(),
				'expires_at' => time() + self::TOKEN_TTL,
			);

			set_transient( $token_key, $stored, self::TOKEN_TTL );

			return rest_ensure_response(
				array(
					'token'      => $token,
					'token_type' => 'Bearer',
					'expires_in' => self::TOKEN_TTL,
					'profile'    => array(
						'id'         => isset( $normalized['id'] ) ? (int) $normalized['id'] : 0,
						'name'       => isset( $normalized['name'] ) ? (string) $normalized['name'] : '',
						'role'       => isset( $normalized['role'] ) ? (string) $normalized['role'] : '',
						'territorio' => isset( $normalized['territorio'] ) ? (string) $normalized['territorio'] : '',
						'territorios' => isset( $normalized['territorios'] ) && is_array( $normalized['territorios'] ) ? array_values( $normalized['territorios'] ) : array(),
					),
				)
			);
		}

		/**
		 * Permission callback for create endpoint.
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return bool|WP_Error
		 */
		public static function permission_edit_eventos( WP_REST_Request $request ) {
			$profile = self::authenticate_request_profile( $request );
			if ( is_wp_error( $profile ) ) {
				return $profile;
			}

			$role = isset( $profile['role'] ) ? (string) $profile['role'] : '';
			if ( ! in_array( $role, array( 'superadmin', 'admin' ), true ) ) {
				return new WP_Error( 'madrural_api_forbidden', esc_html__( 'No autorizado para crear eventos.', 'madrural-eventos' ), array( 'status' => 403 ) );
			}

			return true;
		}

		/**
		 * Permission callback for update/delete by ID.
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return bool|WP_Error
		 */
		public static function permission_edit_single_evento( WP_REST_Request $request ) {
			$profile = self::authenticate_request_profile( $request );
			if ( is_wp_error( $profile ) ) {
				return $profile;
			}

			$role = isset( $profile['role'] ) ? (string) $profile['role'] : '';
			if ( ! in_array( $role, array( 'superadmin', 'admin' ), true ) ) {
				return new WP_Error( 'madrural_api_forbidden', esc_html__( 'No autorizado.', 'madrural-eventos' ), array( 'status' => 403 ) );
			}

			$event_id = (int) $request->get_param( 'id' );
			if ( $event_id <= 0 ) {
				return new WP_Error( 'madrural_api_invalid_id', esc_html__( 'ID de evento inválido.', 'madrural-eventos' ), array( 'status' => 400 ) );
			}

			$post = get_post( $event_id );
			if ( ! $post instanceof WP_Post || MADRURAL_Eventos_Plugin::CPT !== $post->post_type ) {
				return new WP_Error( 'madrural_api_evento_not_found', esc_html__( 'Evento no encontrado.', 'madrural-eventos' ), array( 'status' => 404 ) );
			}

			if ( 'superadmin' === $role ) {
				return true;
			}

			if ( self::profile_can_access_event( $profile, $event_id ) ) {
				return true;
			}

			return new WP_Error( 'madrural_api_forbidden_event', esc_html__( 'No tienes permisos para este evento.', 'madrural-eventos' ), array( 'status' => 403 ) );
		}

		/**
		 * POST /madrural/v1/api-eventos
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function create_evento( WP_REST_Request $request ) {
			$profile = self::get_authenticated_profile();
			if ( ! is_array( $profile ) ) {
				$profile = self::authenticate_request_profile( $request );
			}
			if ( is_wp_error( $profile ) || ! is_array( $profile ) ) {
				return is_wp_error( $profile ) ? $profile : new WP_Error( 'madrural_api_unauthorized', esc_html__( 'No autorizado.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			$data = $request->get_json_params();
			if ( ! is_array( $data ) ) {
				$data = $request->get_body_params();
			}
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			if ( ! self::profile_can_use_payload_territorio( $profile, $data ) ) {
				return new WP_Error( 'madrural_api_forbidden_territorio', esc_html__( 'No tienes permisos para el territorio indicado.', 'madrural-eventos' ), array( 'status' => 403 ) );
			}

			$postarr = MADRURAL_Eventos_Plugin::map_request_to_postarr( $data, 0, null );
			if ( '' === (string) $postarr['post_title'] ) {
				return new WP_Error( 'madrural_missing_title', esc_html__( 'El campo título es obligatorio.', 'madrural-eventos' ), array( 'status' => 400 ) );
			}

			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			MADRURAL_Eventos_Plugin::save_rest_event_meta_and_terms( $post_id, $data );
			MADRURAL_Eventos_Plugin::translate_and_store_event_english_meta( $post_id );

			return rest_ensure_response(
				array(
					'message' => esc_html__( 'Evento creado correctamente.', 'madrural-eventos' ),
					'event'   => self::map_payload_to_english_fields( MADRURAL_Eventos_Plugin::prepare_event_payload( get_post( $post_id ) ) ),
				)
			);
		}

		/**
		 * PUT/PATCH /madrural/v1/api-eventos/{id}
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function update_evento( WP_REST_Request $request ) {
			$profile = self::get_authenticated_profile();
			if ( ! is_array( $profile ) ) {
				$profile = self::authenticate_request_profile( $request );
			}
			if ( is_wp_error( $profile ) || ! is_array( $profile ) ) {
				return is_wp_error( $profile ) ? $profile : new WP_Error( 'madrural_api_unauthorized', esc_html__( 'No autorizado.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			$post_id = (int) $request->get_param( 'id' );
			$post    = get_post( $post_id );
			if ( ! $post instanceof WP_Post || MADRURAL_Eventos_Plugin::CPT !== $post->post_type ) {
				return new WP_Error( 'madrural_event_not_found', esc_html__( 'Evento no encontrado.', 'madrural-eventos' ), array( 'status' => 404 ) );
			}

			$data = $request->get_json_params();
			if ( ! is_array( $data ) ) {
				$data = $request->get_body_params();
			}
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			if ( ! self::profile_can_use_payload_territorio( $profile, $data ) ) {
				return new WP_Error( 'madrural_api_forbidden_territorio', esc_html__( 'No tienes permisos para asignar ese territorio.', 'madrural-eventos' ), array( 'status' => 403 ) );
			}

			$postarr = MADRURAL_Eventos_Plugin::map_request_to_postarr( $data, $post_id, $post );
			$result  = wp_update_post( $postarr, true );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			MADRURAL_Eventos_Plugin::save_rest_event_meta_and_terms( $post_id, $data );
			MADRURAL_Eventos_Plugin::translate_and_store_event_english_meta( $post_id );

			return rest_ensure_response(
				array(
					'message' => esc_html__( 'Evento actualizado correctamente.', 'madrural-eventos' ),
					'event'   => self::map_payload_to_english_fields( MADRURAL_Eventos_Plugin::prepare_event_payload( get_post( $post_id ) ) ),
				)
			);
		}

		/**
		 * DELETE /madrural/v1/api-eventos/{id}
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response|WP_Error
		 */
		public static function delete_evento( WP_REST_Request $request ) {
			$post_id = (int) $request->get_param( 'id' );
			$post    = get_post( $post_id );

			if ( ! $post instanceof WP_Post || MADRURAL_Eventos_Plugin::CPT !== $post->post_type ) {
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
		 * Authenticates API request profile from bearer token.
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return array|WP_Error
		 */
		private static function authenticate_request_profile( WP_REST_Request $request ) {
			$token = self::get_bearer_token_from_request( $request );
			if ( '' === $token ) {
				return new WP_Error( 'madrural_api_missing_token', esc_html__( 'Token no proporcionado.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			$token_key = self::TOKEN_TRANSIENT_PREFIX . hash( 'sha256', $token );
			$stored    = get_transient( $token_key );

			if ( ! is_array( $stored ) || empty( $stored['profile'] ) || ! is_array( $stored['profile'] ) ) {
				return new WP_Error( 'madrural_api_invalid_token', esc_html__( 'Token inválido o expirado.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			$expires_at = isset( $stored['expires_at'] ) ? (int) $stored['expires_at'] : 0;
			if ( $expires_at > 0 && time() > $expires_at ) {
				delete_transient( $token_key );
				return new WP_Error( 'madrural_api_expired_token', esc_html__( 'Token expirado.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			$profile = MADRURAL_Auth_Plugin::normalize_profile( $stored['profile'] );
			if ( ! is_array( $profile ) ) {
				return new WP_Error( 'madrural_api_invalid_profile', esc_html__( 'Perfil inválido.', 'madrural-eventos' ), array( 'status' => 401 ) );
			}

			self::$current_request_profile = $profile;

			return $profile;
		}

		/**
		 * Returns currently authenticated profile for this request.
		 *
		 * @return array|null
		 */
		private static function get_authenticated_profile() {
			return is_array( self::$current_request_profile ) ? self::$current_request_profile : null;
		}

		/**
		 * Extracts bearer token from request.
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return string
		 */
		private static function get_bearer_token_from_request( WP_REST_Request $request ) {
			$authorization = (string) $request->get_header( 'authorization' );

			if ( '' === $authorization && isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
				$authorization = (string) wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
			}

			if ( '' === $authorization && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
				$authorization = (string) wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
			}

			if ( preg_match( '/^Bearer\s+(.+)$/i', trim( $authorization ), $matches ) ) {
				return trim( (string) $matches[1] );
			}

			return '';
		}

		/**
		 * Generates token string.
		 *
		 * @return string
		 */
		private static function generate_token() {
			try {
				return bin2hex( random_bytes( 32 ) );
			} catch ( Exception $exception ) {
				unset( $exception );
				return wp_generate_password( 64, false, false );
			}
		}

		/**
		 * Checks profile can access event by territory.
		 *
		 * @param array $profile Profile data.
		 * @param int   $event_id Event id.
		 * @return bool
		 */
		private static function profile_can_access_event( $profile, $event_id ) {
			if ( ! is_array( $profile ) ) {
				return false;
			}

			$role = isset( $profile['role'] ) ? (string) $profile['role'] : '';
			if ( 'superadmin' === $role ) {
				return true;
			}

			if ( 'admin' !== $role ) {
				return false;
			}

			$allowed_slugs = isset( $profile['territorios'] ) && is_array( $profile['territorios'] ) ? MADRURAL_Auth_Plugin::parse_territorios( $profile['territorios'] ) : array();
			if ( empty( $allowed_slugs ) ) {
				return false;
			}

			$terms = wp_get_post_terms( (int) $event_id, MADRURAL_Eventos_Plugin::TAX_TERRITORIO, array( 'fields' => 'slugs' ) );
			if ( is_wp_error( $terms ) || ! is_array( $terms ) || empty( $terms ) ) {
				return false;
			}

			foreach ( $terms as $slug ) {
				if ( in_array( (string) $slug, $allowed_slugs, true ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks if profile can use incoming territory payload.
		 *
		 * @param array $profile Profile data.
		 * @param array $data Request payload.
		 * @return bool
		 */
		private static function profile_can_use_payload_territorio( $profile, $data ) {
			if ( ! is_array( $profile ) ) {
				return false;
			}

			$role = isset( $profile['role'] ) ? (string) $profile['role'] : '';
			if ( 'superadmin' === $role ) {
				return true;
			}

			if ( 'admin' !== $role ) {
				return false;
			}

			$allowed_slugs = isset( $profile['territorios'] ) && is_array( $profile['territorios'] ) ? MADRURAL_Auth_Plugin::parse_territorios( $profile['territorios'] ) : array();
			if ( empty( $allowed_slugs ) ) {
				return false;
			}

			$territorio_input = null;
			if ( is_array( $data ) && array_key_exists( 'territorio', $data ) ) {
				$territorio_input = $data['territorio'];
			} elseif ( is_array( $data ) && array_key_exists( 'territorios', $data ) ) {
				$territorio_input = $data['territorios'];
			}

			if ( null === $territorio_input ) {
				return true;
			}

			$term_id = MADRURAL_Eventos_Plugin::normalize_single_territorio_term_id( $territorio_input );
			if ( $term_id <= 0 ) {
				return false;
			}

			$term = get_term( $term_id, MADRURAL_Eventos_Plugin::TAX_TERRITORIO );
			if ( ! $term instanceof WP_Term ) {
				return false;
			}

			return in_array( (string) $term->slug, $allowed_slugs, true );
		}

		/**
		 * Returns OpenAPI specification for the Eventos API.
		 *
		 * @param WP_REST_Request $request REST request.
		 * @return WP_REST_Response
		 */
		public static function get_openapi_spec( WP_REST_Request $request ) {
			unset( $request );

			$event_body_schema = array(
				'type'       => 'object',
				'properties' => array(
					'title'            => array( 'type' => 'string' ),
					'description'      => array( 'type' => 'string' ),
					'estado_moderacion' => array( 'type' => 'string', 'enum' => array( 'borrador', 'pendiente', 'publicado' ) ),
					'territorio'       => array( 'type' => 'string' ),
					'territorios'      => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'categorias'       => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
					'fecha_inicio'     => array( 'type' => 'string', 'format' => 'date' ),
					'fecha_fin'        => array( 'type' => 'string', 'format' => 'date' ),
					'hora_evento'      => array( 'type' => 'string', 'example' => '10:00' ),
					'ubicacion'        => array( 'type' => 'string' ),
				),
			);

			$spec = array(
				'openapi' => '3.0.3',
				'info'    => array(
					'title'       => 'MADRURAL Eventos API',
					'version'     => '1.0.0',
					'description' => 'API de eventos con GET público y CRUD protegido por token.',
				),
				'servers' => array(
					array( 'url' => home_url( '/wp-json/madrural/v1' ) ),
					array( 'url' => 'https://www.madrural.com/wp-json/madrural/v1' ),
				),
				'components' => array(
					'securitySchemes' => array(
						'BearerAuth' => array(
							'type'         => 'http',
							'scheme'       => 'bearer',
							'bearerFormat' => 'Token',
						),
					),
				),
				'paths' => array(
					'/api-eventos/login' => array(
						'post' => array(
							'summary' => 'Login y generación de token',
							'requestBody' => array(
								'required' => true,
								'content'  => array(
									'application/json' => array(
										'schema' => array(
											'type'       => 'object',
											'required'   => array( 'name', 'password' ),
											'properties' => array(
												'name' => array( 'type' => 'string' ),
												'password' => array( 'type' => 'string' ),
											),
										),
									),
								),
							),
							'responses' => array(
								'200' => array( 'description' => 'Token generado correctamente.' ),
								'400' => array( 'description' => 'Datos faltantes.' ),
								'401' => array( 'description' => 'Credenciales inválidas.' ),
							),
						),
					),
					'/api-eventos' => array(
						'get'  => array(
							'summary' => 'Listar eventos',
							'parameters' => array(
								array( 'name' => 'territorio', 'in' => 'query', 'schema' => array( 'type' => 'string' ) ),
								array( 'name' => 'categoria', 'in' => 'query', 'schema' => array( 'type' => 'string' ) ),
								array( 'name' => 'desde', 'in' => 'query', 'schema' => array( 'type' => 'string', 'format' => 'date' ) ),
								array( 'name' => 'hasta', 'in' => 'query', 'schema' => array( 'type' => 'string', 'format' => 'date' ) ),
								array( 'name' => 'paged', 'in' => 'query', 'schema' => array( 'type' => 'integer' ) ),
								array( 'name' => 'per_page', 'in' => 'query', 'schema' => array( 'type' => 'integer' ) ),
							),
							'responses' => array(
								'200' => array( 'description' => 'Listado de eventos.' ),
							),
						),
						'post' => array(
							'summary'  => 'Crear evento',
							'security' => array( array( 'BearerAuth' => array() ) ),
							'requestBody' => array(
								'required' => true,
								'content'  => array(
									'application/json' => array(
										'schema' => $event_body_schema,
									),
								),
							),
							'responses' => array(
								'200' => array( 'description' => 'Evento creado.' ),
								'401' => array( 'description' => 'Token faltante o inválido.' ),
								'403' => array( 'description' => 'Sin permisos.' ),
							),
						),
					),
					'/api-eventos/{id}' => array(
						'get' => array(
							'summary' => 'Obtener evento por ID',
							'parameters' => array(
								array( 'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
							),
						),
						'put' => array(
							'summary'  => 'Actualizar evento (PUT)',
							'security' => array( array( 'BearerAuth' => array() ) ),
							'parameters' => array(
								array( 'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
							),
							'requestBody' => array(
								'required' => true,
								'content'  => array(
									'application/json' => array(
										'schema' => $event_body_schema,
									),
								),
							),
						),
						'patch' => array(
							'summary'  => 'Actualizar evento (PATCH)',
							'security' => array( array( 'BearerAuth' => array() ) ),
							'parameters' => array(
								array( 'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
							),
							'requestBody' => array(
								'required' => true,
								'content'  => array(
									'application/json' => array(
										'schema' => $event_body_schema,
									),
								),
							),
						),
						'delete' => array(
							'summary'  => 'Eliminar evento',
							'security' => array( array( 'BearerAuth' => array() ) ),
							'parameters' => array(
								array( 'name' => 'id', 'in' => 'path', 'required' => true, 'schema' => array( 'type' => 'integer' ) ),
							),
						),
					),
				),
			);

			return rest_ensure_response( $spec );
		}

		/**
		 * Registers API docs shortcodes.
		 *
		 * @return void
		 */
		public static function register_shortcodes() {
			add_shortcode( 'madrural_api_docs', array( __CLASS__, 'shortcode_swagger_ui' ) );
		}

		/**
		 * Renders Swagger UI container.
		 *
		 * @return string
		 */
		public static function shortcode_swagger_ui() {
			$spec_url = rest_url( 'madrural/v1/api-docs' );

			ob_start();
			?>
			<div id="madrural-swagger-ui" style="min-height: 600px;"></div>
			<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
			<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
			<script>
				document.addEventListener('DOMContentLoaded', function () {
					if (typeof window.SwaggerUIBundle !== 'function') {
						return;
					}

					window.SwaggerUIBundle({
						url: <?php echo wp_json_encode( esc_url_raw( $spec_url ) ); ?>,
						dom_id: '#madrural-swagger-ui',
						deepLinking: true,
						persistAuthorization: true,
						docExpansion: 'list'
					});
				});
			</script>
			<?php

			return (string) ob_get_clean();
		}

		/**
		 * Renders virtual Swagger UI page at /api-eventos-docs.
		 *
		 * @return void
		 */
		public static function render_swagger_ui_virtual_page() {
			if ( is_admin() ) {
				return;
			}

			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$path        = trim( (string) wp_parse_url( $request_uri, PHP_URL_PATH ), '/' );
			if ( '' === $path || ! preg_match( '#(^|/)api-eventos-docs$#', $path ) ) {
				return;
			}

			global $wp_query;
			if ( $wp_query instanceof WP_Query ) {
				$wp_query->is_404 = false;
			}

			status_header( 200 );
			nocache_headers();

			$spec_url = rest_url( 'madrural/v1/api-docs' );
			?>
			<!doctype html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title><?php echo esc_html__( 'MADRURAL API Docs', 'madrural-eventos' ); ?></title>
				<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
				<?php wp_head(); ?>
			</head>
			<body>
				<div id="madrural-swagger-ui" style="min-height: 100vh;"></div>
				<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
				<script>
					document.addEventListener('DOMContentLoaded', function () {
						if (typeof window.SwaggerUIBundle !== 'function') {
							return;
						}

						window.SwaggerUIBundle({
							url: <?php echo wp_json_encode( esc_url_raw( $spec_url ) ); ?>,
							dom_id: '#madrural-swagger-ui',
							deepLinking: true,
							persistAuthorization: true,
							docExpansion: 'list'
						});
					});
				</script>
				<?php wp_footer(); ?>
			</body>
			</html>
			<?php
			exit;
		}

		/**
		 * Ensures language-consistent API payload keys for requested fields.
		 *
		 * @param array $payload Event payload.
		 * @return array
		 */
		private static function map_payload_to_english_fields( $payload ) {
			if ( ! is_array( $payload ) ) {
				return array();
			}

			$post_id = isset( $payload['id'] ) ? (int) $payload['id'] : 0;
			$titulo = '';
			$descripcion = '';
			$categoria = '';
			$title_en = '';
			$description_en = '';
			$category_en = '';

			if ( $post_id > 0 ) {
				$titulo         = (string) MADRURAL_Eventos_Plugin::get_event_storage_value( $post_id, 'titulo', '' );
				$descripcion    = (string) MADRURAL_Eventos_Plugin::get_event_storage_value( $post_id, 'descripcion', '' );
				$categoria      = (string) MADRURAL_Eventos_Plugin::get_event_storage_value( $post_id, 'categoria', '' );
				$title_en       = (string) MADRURAL_Eventos_Plugin::get_event_storage_value( $post_id, 'titulo_en', '' );
				$description_en = (string) MADRURAL_Eventos_Plugin::get_event_storage_value( $post_id, 'descripcion_en', '' );
				$category_en    = (string) MADRURAL_Eventos_Plugin::get_event_storage_value( $post_id, 'categoria_en', '' );
			}

			$payload['titulo']         = $titulo;
			$payload['descripcion']    = $descripcion;
			$payload['categoria']      = $categoria;
			$payload['titulo_en']      = $title_en;
			$payload['descripcion_en'] = $description_en;
			$payload['categoria_en']   = $category_en;

			return $payload;
		}
	}
}

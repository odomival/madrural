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
		 * Initializes hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		}

		/**
		 * Registers public routes.
		 *
		 * @return void
		 */
		public static function register_routes() {
			register_rest_route(
				'madrural/v1',
				'/api-eventos',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( __CLASS__, 'get_eventos' ),
						'permission_callback' => '__return_true',
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
				)
			);
		}

		/**
		 * GET /madrural/v1/api-eventos
		 *
		 * Filters: territorio, categoria, desde, hasta, paged|me_paged, per_page.
		 * Empty or null filters are ignored.
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
				'post_type'      => MADRURAL_Eventos_Plugin::CPT,
				'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'suppress_filters' => true,
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

			$query  = new WP_Query( $args );
			$items  = array();
			$posts  = is_array( $query->posts ) ? $query->posts : array();

			foreach ( $posts as $post ) {
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
				return new WP_Error(
					'madrural_api_evento_not_found',
					esc_html__( 'Evento no encontrado.', 'madrural-eventos' ),
					array( 'status' => 404 )
				);
			}

			if ( ! in_array( (string) $post->post_status, $allowed_statuses, true ) ) {
				return new WP_Error(
					'madrural_api_evento_not_found',
					esc_html__( 'Evento no encontrado.', 'madrural-eventos' ),
					array( 'status' => 404 )
				);
			}

			return rest_ensure_response( self::map_payload_to_english_fields( MADRURAL_Eventos_Plugin::prepare_event_payload( $post ) ) );
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

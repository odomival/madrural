<?php

/**
 * Class ExactMetrics_Welcome
 */
class ExactMetrics_Welcome {

	/**
	 * ExactMetrics_Welcome constructor.
	 */
	public function __construct() {

		// If we are not in admin or admin ajax, return
		if ( ! is_admin() ) {
			return;
		}

		// If user is in admin ajax or doing cron, return
		if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		// If user is not logged in, return
		if ( ! is_user_logged_in() ) {
			return;
		}

		// If user cannot manage_options, return
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		add_action( 'admin_init', array( $this, 'maybe_redirect' ), 9999 );

		add_action( 'admin_menu', array( $this, 'register' ) );
		// Add the welcome screen to the network dashboard.
		add_action( 'network_admin_menu', array( $this, 'register' ) );

		add_action( 'admin_head', array( $this, 'hide_menu' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'welcome_scripts' ) );
	}

	/**
	 * Register the pages to be used for the Welcome screen.
	 *
	 * These pages will be removed from the Dashboard menu, so they will
	 * not actually show. Sneaky, sneaky.
	 *
	 * @since 1.0.0
	 */
	public function register() {

		// Getting started - shows after installation.
		add_dashboard_page(
			esc_html__( 'Welcome to ExactMetrics', 'google-analytics-dashboard-for-wp' ),
			esc_html__( 'Welcome to ExactMetrics', 'google-analytics-dashboard-for-wp' ),
			apply_filters( 'exactmetrics_welcome_cap', 'manage_options' ),
			'exactmetrics-getting-started',
			array( $this, 'welcome_screen' )
		);
	}

	/**
	 * Removed the dashboard pages from the admin menu.
	 *
	 * This means the pages are still available to us, but hidden.
	 *
	 * @since 1.0.0
	 */
	public function hide_menu() {
		remove_submenu_page( 'index.php', 'exactmetrics-getting-started' );
	}


	/**
	 * Check if we should do any redirect.
	 */
	public function maybe_redirect() {

		// Bail if no activation redirect.
		if ( ! get_transient( '_exactmetrics_activation_redirect' ) || isset( $_GET['exactmetrics-redirect'] ) ) {
			return;
		}

		// Delete the redirect transient.
		delete_transient( '_exactmetrics_activation_redirect' );

		// Bail if activating from network, or bulk.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		$upgrade = get_option( 'exactmetrics_version_upgraded_from', false );
		if ( apply_filters( 'exactmetrics_enable_onboarding_wizard', false === $upgrade ) ) {
			$path     = 'index.php?page=exactmetrics-getting-started&exactmetrics-redirect=1';
			$redirect = is_network_admin() ? network_admin_url( $path ) : admin_url( $path );
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Scripts for loading the welcome screen Vue instance.
	 */
	public function welcome_scripts() {

		$current_screen = get_current_screen();
		$screens        = array(
			'dashboard_page_exactmetrics-getting-started',
			'index_page_exactmetrics-getting-started-network',
		);

		if ( empty( $current_screen->id ) || ! in_array( $current_screen->id, $screens, true ) ) {
			return;
		}

		$version_path = exactmetrics_is_pro_version() ? 'pro' : 'lite';
		if ( ! defined( 'EXACTMETRICS_LOCAL_JS_URL' ) ) {
			ExactMetrics_Admin_Assets::enqueue_script_specific_css( 'src/modules/wizard-onboarding/wizard.js' );
		}

		$app_js_url = ExactMetrics_Admin_Assets::get_js_url( 'src/modules/wizard-onboarding/wizard.js' );
		wp_register_script( 'exactmetrics-vue-script', $app_js_url, array(), exactmetrics_get_asset_version(), true );
		wp_enqueue_script( 'exactmetrics-vue-script' );

		$user_data = wp_get_current_user();

		wp_localize_script(
			'exactmetrics-vue-script',
			'exactmetrics',
			array(
				'ajax'                 => add_query_arg( 'page', 'exactmetrics-onboarding', admin_url( 'admin-ajax.php' ) ),
				'nonce'                => wp_create_nonce( 'mi-admin-nonce' ),
				'network'              => is_network_admin(),
				'translations'         => wp_get_jed_locale_data( 'mi-vue-app' ),
				'assets'               => plugins_url( $version_path . '/assets/vue', EXACTMETRICS_PLUGIN_FILE ),
				'roles'                => exactmetrics_get_roles(),
				'roles_manage_options' => exactmetrics_get_manage_options_roles(),
				'wizard_url'           => is_network_admin() ? network_admin_url( 'index.php?page=exactmetrics-onboarding' ) : admin_url( 'index.php?page=exactmetrics-onboarding' ),
				'shareasale_id'        => exactmetrics_get_shareasale_id(),
				'shareasale_url'       => exactmetrics_get_shareasale_url( exactmetrics_get_shareasale_id(), '' ),
				// Used to add notices for future deprecations.
				'versions'             => exactmetrics_get_php_wp_version_warning_data(),
				'plugin_version'       => EXACTMETRICS_VERSION,
				'first_name'           => ! empty( $user_data->first_name ) ? $user_data->first_name : '',
				'exit_url'             => add_query_arg( 'page', 'exactmetrics_settings', admin_url( 'admin.php' ) ),
				'had_ecommerce'        => exactmetrics_get_option( 'gadwp_ecommerce', false ),
			)
		);
	}

	/**
	 * Load the welcome screen content.
	 */
	public function welcome_screen() {
		do_action( 'exactmetrics_head' );

		exactmetrics_settings_error_page( $this->get_screen_id() );
		exactmetrics_settings_inline_js();
	}

	/**
	 * Get the screen id to control which Vue component is loaded.
	 *
	 * @return string
	 */
	public function get_screen_id() {
		$screen_id = 'exactmetrics-welcome';

		if ( defined( 'EXACTMETRICS_VERSION' ) && function_exists( 'ExactMetrics' ) ) {
			$migrated = exactmetrics_get_option( 'gadwp_migrated', 0 );
			if ( time() - $migrated < HOUR_IN_SECONDS || isset( $_GET['exactmetrics-migration'] ) ) {
				$screen_id = 'exactmetrics-migration-wizard';
			}
		}

		return $screen_id;
	}
}

new ExactMetrics_Welcome();

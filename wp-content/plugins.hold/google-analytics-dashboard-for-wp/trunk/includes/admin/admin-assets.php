<?php
/**
 * WP ADMIN assets will be enqueued here.
 *
 * @package exactmetrics
 */

/**
 * Class ExactMetrics_Admin_Assets
 * This class is responsible for load CSS and JS in admin panel.
 */
class ExactMetrics_Admin_Assets {
	/**
	 * ExactMetrics handles.
	 */
	private $own_handles = array(
		'exactmetrics-vue-script',
		'exactmetrics-vue-frontend',
		'exactmetrics-vue-reports',
		'exactmetrics-vue-widget',
	);

	/**
	 * Store manifest.json file content.
	 *
	 * @var array
	 */
	private static $manifest_data;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_filter( 'script_loader_tag', array( $this, 'script_loader_tag' ), 99999, 3 );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

		$this->get_manifest_data();
	}

	/**
	 * Update script tag.
	 * The vue code needs type=module.
	 */
	public function script_loader_tag( $tag, $handle, $src ) {

		if ( ! in_array( $handle, $this->own_handles ) ) {
			return $tag;
		}

		// Change the script tag by adding type="module" and return it.
		return sprintf(
			'<script type="module" src="%1$s" id="%2$s-js"></script>%3$s',
			$src,
			$handle,
			PHP_EOL
		);
	}

	/**
	 * Loads styles for all ExactMetrics-based Administration Screens.
	 *
	 * @return null Return early if not on the proper screen.
	 */
	public function admin_styles() {

		$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

		// Load Common admin styles.
		wp_register_style('exactmetrics-admin-common-style', plugins_url('assets/css/admin-common' . $suffix . '.css', EXACTMETRICS_PLUGIN_FILE), array(), exactmetrics_get_asset_version());
		wp_enqueue_style('exactmetrics-admin-common-style');

		// Get current screen.
		$screen = get_current_screen();

		// Bail if we're not on a ExactMetrics screen.
		if (empty($screen->id) || strpos($screen->id, 'exactmetrics') === false) {
			return;
		}

		// For the settings page, load the Vue app styles.
		if (exactmetrics_is_settings_page()) {
			if (!defined('EXACTMETRICS_LOCAL_JS_URL')) {
				$this->enqueue_script_specific_css( 'src/modules/settings/settings.js' );
			}

			// Don't load other styles on the settings page.
			return;
		}

		if (exactmetrics_is_reports_page()) {
			if (!defined('EXACTMETRICS_LOCAL_JS_URL')) {
				$this->enqueue_script_specific_css( 'src/modules/reports/reports.js' );
			}

			return;
		}

		// Tooltips
		wp_enqueue_script('jquery-ui-tooltip');
	}

	/**
	 * Loads scripts for all ExactMetrics-based Administration Screens.
	 *
	 * @return null Return early if not on the proper screen.
	 */
	public function admin_scripts() {

		// Our Common Admin JS.
		$suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

		wp_enqueue_script('exactmetrics-admin-common-script', plugins_url('assets/js/admin-common' . $suffix . '.js', EXACTMETRICS_PLUGIN_FILE), array('jquery'), exactmetrics_get_asset_version(), true);

		wp_localize_script(
			'exactmetrics-admin-common-script',
			'exactmetrics_admin_common',
			array(
				'ajax'                 => admin_url('admin-ajax.php'),
				'dismiss_notice_nonce' => wp_create_nonce('exactmetrics-dismiss-notice'),
			)
		);

		// Get current screen.
		$screen = get_current_screen();

		// Bail if we're not on a ExactMetrics screen.
		if (empty($screen->id) || strpos($screen->id, 'exactmetrics') === false) {
			return;
		}

		$version_path = exactmetrics_is_pro_version() ? 'pro' : 'lite';

		// For the settings page, load the Vue app.
		if (exactmetrics_is_settings_page()) {
			$app_js_url = self::get_js_url( 'src/modules/settings/settings.js' );
			wp_register_script('exactmetrics-vue-script', $app_js_url, array(), exactmetrics_get_asset_version(), true);
			wp_enqueue_script('exactmetrics-vue-script');

			$plugins         = get_plugins();
			$install_amp_url = false;
			if (exactmetrics_can_install_plugins()) {
				$amp_key = 'amp/amp.php';
				if (array_key_exists($amp_key, $plugins)) {
					$install_amp_url = wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . $amp_key), 'activate-plugin_' . $amp_key);
				} else {
					$install_amp_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=amp'), 'install-plugin_amp');
				}
			}

			$install_woocommerce_url = false;
			if (exactmetrics_can_install_plugins()) {
				$woo_key = 'woocommerce/woocommerce.php';
				if (array_key_exists($woo_key, $plugins)) {
					$install_woocommerce_url = wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=' . $woo_key), 'activate-plugin_' . $woo_key);
				} else {
					$install_woocommerce_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce');
				}
			}

			$prepared_dimensions = array();
			if (class_exists('ExactMetrics_Admin_Custom_Dimensions')) {
				$dimensions          = new ExactMetrics_Admin_Custom_Dimensions();
				$dimensions          = $dimensions->custom_dimensions();
				$prepared_dimensions = array();
				foreach ($dimensions as $dimension_type => $dimension) {
					$dimension['type']     = $dimension_type;
					$prepared_dimensions[] = $dimension;
				}
			}

			$is_authed = (ExactMetrics()->auth->is_authed() || ExactMetrics()->auth->is_network_authed());

			wp_localize_script(
				'exactmetrics-vue-script',
				'exactmetrics',
				array(
					'ajax'                            => admin_url('admin-ajax.php'),
					'nonce'                           => wp_create_nonce('mi-admin-nonce'),
					'network'                         => is_network_admin(),
					'translations'                    => wp_get_jed_locale_data(exactmetrics_is_pro_version() ? 'exactmetrics-premium' : 'google-analytics-dashboard-for-wp'),
					'assets'                          => plugins_url($version_path . '/assets/vue', EXACTMETRICS_PLUGIN_FILE),
					'roles'                           => exactmetrics_get_roles(),
					'roles_manage_options'            => exactmetrics_get_manage_options_roles(),
					'shareasale_id'                   => exactmetrics_get_shareasale_id(),
					'shareasale_url'                  => exactmetrics_get_shareasale_url(exactmetrics_get_shareasale_id(), ''),
					'addons_url'                      => is_multisite() ? network_admin_url('admin.php?page=exactmetrics_network#/addons') : admin_url('admin.php?page=exactmetrics_settings#/addons'),
					'seo_settings_page_url'           => is_multisite() ? network_admin_url('admin.php?page=exactmetrics_network#/seo') : admin_url('admin.php?page=exactmetrics_settings#/seo'),
					'aioseo_dashboard_url'            => is_multisite() ? network_admin_url('admin.php?page=aioseo') : admin_url('admin.php?page=aioseo'),
					'wp_plugins_page_url'             => is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php'),
					'email_summary_url'               => admin_url('admin.php?exactmetrics_email_preview&exactmetrics_email_template=summary'),
					'install_amp_url'                 => $install_amp_url,
					'install_woo_url'                 => $install_woocommerce_url,
					'dimensions'                      => $prepared_dimensions,
					'wizard_url'                      => is_network_admin() ? network_admin_url('index.php?page=exactmetrics-onboarding') : admin_url('index.php?page=exactmetrics-onboarding'),
					'install_plugins'                 => exactmetrics_can_install_plugins(),
					'unfiltered_html'                 => current_user_can('unfiltered_html'),
					'activate_nonce'                  => wp_create_nonce('exactmetrics-activate'),
					'deactivate_nonce'                => wp_create_nonce('exactmetrics-deactivate'),
					'install_nonce'                   => wp_create_nonce('exactmetrics-install'),
					// Used to add notices for future deprecations.
					'versions'                        => exactmetrics_get_php_wp_version_warning_data(),
					'plugin_version'                  => EXACTMETRICS_VERSION,
					'is_admin'                        => true,
					'admin_email'                     => get_option('admin_email'),
					'site_url'                        => get_site_url(),
					'reports_url'                     => add_query_arg('page', 'exactmetrics_reports', admin_url('admin.php')),
					'ecommerce_report_url'            => add_query_arg('page', 'exactmetrics_reports#/ecommerce', admin_url('admin.php')),
					'ecommerce_settings_tab_url'      => add_query_arg('page', 'exactmetrics_settings#/ecommerce', admin_url('admin.php')),
					'first_run_notice'                => apply_filters('exactmetrics_settings_first_time_notice_hide', exactmetrics_get_option('exactmetrics_first_run_notice')),
					'getting_started_url'             => is_network_admin() ? network_admin_url('admin.php?page=exactmetrics_network#/about') : admin_url('admin.php?page=exactmetrics_settings#/about/getting-started'),
					'authed'                          => $is_authed,
					'new_pretty_link_url'             => admin_url('post-new.php?post_type=pretty-link'),
					'wpmailsmtp_admin_url'            => admin_url('admin.php?page=wp-mail-smtp'),
					'load_headline_analyzer_settings' => exactmetrics_load_gutenberg_app() ? 'true' : 'false',
					'exit_url'                        => add_query_arg('page', 'exactmetrics_settings', admin_url('admin.php')),
					'timezone'                        => date('e'),
					'funnelkit_stripe_woo_page_url'   => admin_url( 'admin.php?page=wc-settings&tab=fkwcs_api_settings' ),
					'funnelkit_stripe_woo_nonce'      => wp_create_nonce('exactmetrics-funnelkit-stripe-woo-nonce'),
				)
			);

			// Don't load other scripts on the settings page.
			return;
		}

		if (exactmetrics_is_reports_page()) {

			$app_js_url = self::get_js_url( 'src/modules/reports/reports.js' );
			wp_register_script('exactmetrics-vue-reports', $app_js_url, array(), exactmetrics_get_asset_version(), true);
			wp_enqueue_script('exactmetrics-vue-reports');

			// We do not have a current auth.
			$auth      = ExactMetrics()->auth;
			$site_auth = $auth->get_viewname();
			$ms_auth   = is_multisite() && $auth->get_network_viewname();

			wp_localize_script(
				'exactmetrics-vue-reports',
				'exactmetrics',
				array(
					'ajax'                => admin_url('admin-ajax.php'),
					'nonce'               => wp_create_nonce('mi-admin-nonce'),
					'network'             => is_network_admin(),
					'translations'        => wp_get_jed_locale_data(exactmetrics_is_pro_version() ? 'exactmetrics-premium' : 'google-analytics-dashboard-for-wp'),
					'assets'              => plugins_url($version_path . '/assets/vue', EXACTMETRICS_PLUGIN_FILE),
					'pro_assets'          => plugins_url($version_path . '/assets', EXACTMETRICS_PLUGIN_FILE),
					'shareasale_id'       => exactmetrics_get_shareasale_id(),
					'shareasale_url'      => exactmetrics_get_shareasale_url(exactmetrics_get_shareasale_id(), ''),
					'addons_url'          => is_multisite() ? network_admin_url('admin.php?page=exactmetrics_network#/addons') : admin_url('admin.php?page=exactmetrics_settings#/addons'),
					'timezone'            => date('e'), // phpcs:ignore
					'authed'              => $site_auth || $ms_auth,
					'settings_url'        => add_query_arg('page', 'exactmetrics_settings', admin_url('admin.php')),
					// Used to add notices for future deprecations.
					'versions'            => exactmetrics_get_php_wp_version_warning_data(),
					'plugin_version'      => EXACTMETRICS_VERSION,
					'is_admin'            => true,
					'admin_email'         => get_option('admin_email'),
					'site_url'            => get_site_url(),
					'wizard_url'          => is_network_admin() ? network_admin_url('index.php?page=exactmetrics-onboarding') : admin_url('index.php?page=exactmetrics-onboarding'),
					'install_nonce'       => wp_create_nonce('exactmetrics-install'),
					'activate_nonce'      => wp_create_nonce('exactmetrics-activate'),
					'deactivate_nonce'    => wp_create_nonce('exactmetrics-deactivate'),
					'update_settings'     => current_user_can('exactmetrics_save_settings'),
					'migrated'            => exactmetrics_get_option('gadwp_migrated', 0),
					'yearinreview'        => exactmetrics_yearinreview_dates(),
					'reports_url'         => add_query_arg('page', 'exactmetrics_reports', admin_url('admin.php')),
				)
			);

			return;
		}

		// ublock notice
		add_action('admin_print_footer_scripts', array( $this, 'exactmetrics_settings_ublock_error_js' ), 9999999);
	}

	/**
	 * Need to identify why this function is using.
	 */
	public function exactmetrics_settings_ublock_error_js() {
		echo "<script type='text/javascript'>\n";
		echo "jQuery( document ).ready( function( $ ) {
				if ( window.uorigindetected == null){
				   $('#exactmetrics-ublock-origin-error').show();
				   $('.exactmetrics-nav-tabs').hide();
				   $('.exactmetrics-nav-container').hide();
				   $('#exactmetrics-addon-heading').hide();
				   $('#exactmetrics-addons').hide();
				   $('#exactmetrics-reports').hide();
				}
			});";
		echo "\n</script>";
	}

	/**
	 * Load CSS from manifest.json
	 */
	public static function enqueue_script_specific_css( $js_file_path ) {
		if ( defined( 'EXACTMETRICS_LOCAL_JS_URL' ) ) {
			return;
		}

		$version_path = exactmetrics_is_pro_version() ? 'pro' : 'lite';
		$plugin_path  = plugin_dir_path( EXACTMETRICS_PLUGIN_FILE );

		if ( ! isset( self::$manifest_data[ $js_file_path ] ) ) {
			return;
		}

		$js_imports    = self::$manifest_data[ $js_file_path ]['imports'];
		$css_file_path = $plugin_path . $version_path . '/assets/vue/';

		// Add JS own CSS file.
		if ( isset( self::$manifest_data[ $js_file_path ]['css'] ) ) {
			self::add_js_own_css_files( self::$manifest_data[ $js_file_path ]['css'], $version_path );
		}

		// Loop through all imported js file of entry file.
		foreach( $js_imports as $js_filename ) {
			// Check imported file available in manifest.json
			if ( ! isset( self::$manifest_data[ $js_filename ] ) ) {
				continue;
			}

			// Check imported js file has it's own css.
			if ( ! isset( self::$manifest_data[ $js_filename ]['css'] ) ) {
				continue;
			}

			$js_file_css = self::$manifest_data[ $js_filename ]['css'];

			// css must be array.
			if ( ! is_array( $js_file_css ) ) {
				continue;
			}

			// Loop to css files of a imported js file.
			foreach ( $js_file_css as $css_hash_name ) {
				if ( file_exists( $css_file_path . $css_hash_name ) ) {
					wp_enqueue_style(
						'exactmetrics-style-' . basename( $css_hash_name ),
						plugins_url( $version_path . '/assets/vue/' . $css_hash_name, EXACTMETRICS_PLUGIN_FILE ),
						array(),
						exactmetrics_get_asset_version()
					);
				}
			}
		}
	}

	/**
	 * Add JS it's own CSS build file.
	 */
	private static function add_js_own_css_files( $css_files, $version_path ) {
		foreach ( $css_files as $css_filename ) {
			wp_enqueue_style(
				'exactmetrics-style-' . basename( $css_filename ),
				plugins_url( $version_path . '/assets/vue/' . $css_filename, EXACTMETRICS_PLUGIN_FILE ),
				array(),
				exactmetrics_get_asset_version()
			);
		}
	}

	/**
	 * Get JS build file URL of a entry file.
	 *
	 * @return string
	 */
	public static function get_js_url( $path ) {
		if ( ! $path ) {
			return;
		}

		if ( defined( 'EXACTMETRICS_LOCAL_JS_URL' ) && EXACTMETRICS_LOCAL_JS_URL ) {
			return EXACTMETRICS_LOCAL_JS_URL . $path;
		}

		// If the file is not available on manifest.
		if ( ! isset( self::$manifest_data[ $path ] ) ) {
			return;
		}

		$js_file      = self::$manifest_data[ $path ]['file'];
		$version_path = exactmetrics_is_pro_version() ? 'pro' : 'lite';

		return plugins_url( $version_path . '/assets/vue/' . $js_file, EXACTMETRICS_PLUGIN_FILE );
	}

	/**
	 * Fetch manifest.json data and store it to array for future use.
	 *
	 * @return void
	 */
	private function get_manifest_data() {
		$version_path  = exactmetrics_is_pro_version() ? 'pro' : 'lite';
		$plugin_path   = plugin_dir_path( EXACTMETRICS_PLUGIN_FILE );
		$manifest_path = $plugin_path . $version_path . '/assets/vue/manifest.json';

		// Return if manifest.json not exists.
		if ( ! file_exists( $manifest_path ) ) {
			return;
		}

		self::$manifest_data = json_decode( file_get_contents( $manifest_path ), true );
	}
}

new ExactMetrics_Admin_Assets();

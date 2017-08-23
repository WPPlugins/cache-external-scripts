<?php
/**
 * Plugin Name: Cache External Scripts
 * Plugin URI: http://www.forcemedia.nl/wordpress-plugins/cache-external-scripts/
 * Description: This plugin allows you to cache the Google Analytics JavaScript file to be cached for more than 2 hours, for a better PageSpeed score
 * Version: 0.4
 * Author: Diego Voors
 * Author URI: http://www.forcemedia.nl
 * License: GPL2
 */

class CacheExternalScripts {
	/**
	 * Absolute path to the WordPRess uploads directory
	 *
	 * @var string
	 */
	private $upload_base_dir;

	/**
	 * URL path to the local uploads directory
	 *
	 * @var string
	 */
	private $upload_base_url;

	/**
	 * Initialise the plugin
	 */
	public function __construct() {
		$this->init_vars();
		$this->setup_cron();
		$this->rewrite_html_output();
		add_action( 'wp', [ $this, 'setup_cron' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'settings_init' ] );
		add_action( 'referesh_external_script_cache', [ $this, 'referesh_external_script_cache' ] );
		register_deactivation_hook( __FILE__, [ __CLASS__, 'deactivate_plugin' ] );
	}

	/**
	 * Setup initial variables which we'll need later
	 */
	public function init_vars() {
		$wp_upload_dir = wp_upload_dir();
		$this->upload_base_dir = $wp_upload_dir['basedir'];
		$this->upload_base_url = $wp_upload_dir['baseurl'];
	}

	/**
	 * Setup the CRON job to refresh the cached files
	 */
	public function setup_cron() {
		if ( ! wp_next_scheduled( 'referesh_external_script_cache' ) ) {
			wp_schedule_event( time(), 'daily', 'referesh_external_script_cache' );
		}
	}

	/**
	 * Rewrite the output HTML to use our local files instead of the remote ones.
	 */
	public function rewrite_html_output() {
		add_action( 'get_header', [ $this, 'internal_ob_start' ] );
		add_action( 'wp_footer', [ $this, 'internal_ob_end_flush' ], 99999 );
	}

	/**
	 * Start our own Output Buffering
	 */
	public function internal_ob_start() {
		return ob_start( [ $this, 'filter_wp_head_output' ] );
	}

	/**
	 * Stop the Output Buffering we started
	 */
	public function internal_ob_end_flush() {
		ob_end_flush();
	}

	/**
	 * The callback for Output Buffering - rewrites the external references with
	 * local ones
	 *
	 * @param string $output The original output.
	 *
	 * @return string The modified output
	 */
	public function filter_wp_head_output( $output ) {
		if ( file_exists( $this->upload_base_dir . '/cached-scripts/analytics.js' ) ) {
			$output = preg_replace( '#(http:|https:|)//www.google-analytics.com/analytics.js#', $this->upload_base_url . '/cached-scripts/analytics.js', $output );
		}
		if ( file_exists( $this->upload_base_dir . '/cached-scripts/ga.js' ) ) {
			$output = str_replace( "ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';", "ga.src = '" . $this->upload_base_url . "/cached-scripts/ga.js'", $output );
		}
		return $output;
	}

	/**
	 * Update the locally cached files
	 */
	public function referesh_external_script_cache() {
		$dir = $this->upload_base_dir . '/cached-scripts';
		if ( ! file_exists( $dir ) && ! is_dir( $dir ) ) {
			mkdir( $dir );
		}

		$analytics_data = $this->get_data( 'http://www.google-analytics.com/analytics.js' );
		if ( $analytics_data and ( ! file_exists( $this->upload_base_dir . '/cached-scripts/analytics.js' ) or $analytics_data !== file_get_contents( $this->upload_base_dir . '/cached-scripts/analytics.js' ) ) ) {
			$fp = fopen( $this->upload_base_dir . '/cached-scripts/analytics.js', 'wb' );
			fwrite( $fp, $analytics_data );
			fclose( $fp );
		}

		$ga_data = $this->get_data( 'http://www.google-analytics.com/ga.js' );
		if ( $ga_data and ( ! file_exists( $this->upload_base_dir . '/cached-scripts/ga.js' ) or $ga_data !== file_get_contents( $this->upload_base_dir . '/cached-scripts/ga.js' ) ) ) {
			$fp = fopen( $this->upload_base_dir . '/cached-scripts/ga.js', 'wb');
			fwrite( $fp, $ga_data );
			fclose( $fp );
		}
	}

	/**
	 * Add our Admin Menu
	 */
	public function add_admin_menu() {
		add_options_page( 'Cache External Scripts', 'Cache External Scripts', 'manage_options', 'cache-external-scripts', [ $this, 'options_page' ] );
	}

	/**
	 * Register our settings
	 */
	public function settings_init() {
		register_setting( 'pluginPage', 'ces_settings', 'validate_input' );
	}

	/**
	 * Output our Options page
	 */
	function options_page() {
		?>
			<h1>Cache External Sources</h1>
		<?php
		if( 'cache-scripts' === $_GET['action'] ){
			echo 'Fetching scripts...</p>';
			$this->referesh_external_script_cache();
		}
		if ( file_exists( $this->upload_base_dir . '/cached-scripts/analytics.js' ) and file_exists( $this->upload_base_dir . '/cached-scripts/ga.js' ) ) {
			echo '<p>Google Analytics file (analytics.js) succesfully cached on local server!</p><p>In case you want to force the cache to be renewed, click <a href="'.get_site_url().'/wp-admin/options-general.php?page=cache-external-scripts&action=cache-scripts">this link</a>

			<span style="margin-top:70px;background-color:#fff;padding:10px;border:1px solid #C42429;display:inline-block;">Did this plugin help you to leverage browser caching and increase your PageSpeed Score? <a href="https://wordpress.org/support/view/plugin-reviews/cache-external-scripts" target="_blank">Please rate the plugin</a>!<br />Did not work for your site? <a href="https://wordpress.org/support/plugin/cache-external-scripts" target="_blank">Please let us know</a>!</span>';
		} else {
			echo '<p>Google Analytics file (analytics.js) is not cached yet on the local server. Please refresh <a href="'.get_site_url().'" target="_blank">your frontpage</a> to start the cron or start it manually by pressing <a href="' . get_site_url() . '/wp-admin/options-general.php?page=cache-external-scripts&action=cache-scripts">this link</a>.</p>';
		}
	}



	/**
	 * Get the data from a URL
	 *
	 * @param string $url Absolute URL to get the data from.
	 *
	 * @return text The contents of the file
	 */
	static function get_data( $url ) {
		$ch = curl_init();
		$timeout = 5;
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
		$data = curl_exec( $ch );
		curl_close( $ch );
		return $data;
	}

	/**
	 * When the plugin is deactivated, remove the cron job and tidy up.
	 */
	public static function deactivate_plugin() {
		// find out when the last event was scheduled.
		$timestamp = wp_next_scheduled( 'referesh_external_script_cache' );
		// unschedule previous event if any.
		wp_unschedule_event( $timestamp, 'referesh_external_script_cache' );
	}
}
new CacheExternalScripts;

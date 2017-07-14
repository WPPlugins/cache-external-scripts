<?php
/**
 * Plugin Name: Cache External Scripts
 * Plugin URI: http://www.forcemedia.nl/wordpress-plugins/cache-external-scripts/
 * Description: This plugin allows you to cache the Google Analytics JavaScript file to be cached for more than 2 hours, for a better PageSpeed score
 * Version: 0.3
 * Author: Diego Voors
 * Author URI: http://www.forcemedia.nl
 * License: GPL2
 */
 
//Define uploads directory to store cached scripts
$wp_upload_dir = wp_upload_dir();
define('UPLOAD_BASE_DIR',$wp_upload_dir['basedir']);
define('UPLOAD_BASE_URL',$wp_upload_dir['baseurl']);

// create a scheduled event (if it does not exist already)
function ces_cronstarter_activation() {
	if( !wp_next_scheduled( 'cache-external-scripts-cron' ) ) {  
	   wp_schedule_event( time(), 'daily', 'cache-external-scripts-cron' );  
	}
}
// and make sure it's called whenever WordPress loads
add_action('wp', 'ces_cronstarter_activation');


// unschedule event upon plugin deactivation
function ces_cronstarter_deactivate() {	
	// find out when the last event was scheduled
	$timestamp = wp_next_scheduled ('cache-external-scripts-cron');
	// unschedule previous event if any
	wp_unschedule_event ($timestamp, 'cache-external-scripts-cron');
} 
register_deactivation_hook (__FILE__, 'ces_cronstarter_deactivate');

function get_data($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

// here's the function we'd like to call with our cron job
function function_cache_external_scripts() {
	
	$dir = UPLOAD_BASE_DIR.'/cached-scripts';
	if (!file_exists($dir) && !is_dir($dir)) {
		mkdir($dir);         
	}
	
	$analytics_data = get_data('http://www.google-analytics.com/analytics.js');
	if($analytics_data AND (!file_exists(UPLOAD_BASE_DIR.'/cached-scripts/analytics.js') OR $analytics_data!=file_get_contents(UPLOAD_BASE_DIR.'/cached-scripts/analytics.js'))){
		$fp = fopen(UPLOAD_BASE_DIR.'/cached-scripts/analytics.js',"wb");
		fwrite($fp,$analytics_data);
		fclose($fp);
	}
	
	$ga_data = get_data('http://www.google-analytics.com/ga.js');
	if($ga_data AND (!file_exists(UPLOAD_BASE_DIR.'/cached-scripts/ga.js') OR $ga_data!=file_get_contents(UPLOAD_BASE_DIR.'/cached-scripts/ga.js'))){
		$fp = fopen(UPLOAD_BASE_DIR.'/cached-scripts/ga.js',"wb");
		fwrite($fp,$ga_data);
		fclose($fp);
	}
	
}
// hook that function onto our scheduled event:
add_action ('cache-external-scripts-cron', 'function_cache_external_scripts'); 


add_action('get_header', 'ces_ob_start');
add_action('wp_footer', 'ces_ob_end_flush', 99999);
function ces_ob_start() {
    ob_start('ces_filter_wp_head_output');
}
function ces_ob_end_flush() {
    ob_end_flush();
}
function ces_filter_wp_head_output($output) {
	if(file_exists(UPLOAD_BASE_DIR.'/cached-scripts/analytics.js')){
		$output = preg_replace('#(http:|https:|)//www.google-analytics.com/analytics.js#',UPLOAD_BASE_URL.'/cached-scripts/analytics.js',$output);
	}
	if(file_exists(UPLOAD_BASE_DIR.'/cached-scripts/ga.js')){
		$output = str_replace("ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';","ga.src = '".UPLOAD_BASE_URL."/cached-scripts/ga.js'",$output);
	}
    return $output;
}

add_action( 'admin_menu', 'ces_add_admin_menu' );
add_action( 'admin_init', 'ces_settings_init' );

function ces_add_admin_menu(  ) { 
	add_options_page( 'Cache External Scripts', 'Cache External Scripts', 'manage_options', 'cache-external-scripts', 'ces_options_page' );
}


function ces_settings_init(  ) { 
	register_setting( 'pluginPage', 'ces_settings', 'validate_input' );
}

function ces_options_page(  ) { 
	?>
		<h1>Cache External Sources</h1>
	<?php
	if($_GET['action']=='cache-scripts'){
		echo 'Fetching scripts...</p>';
		function_cache_external_scripts();
	}
	if(file_exists(UPLOAD_BASE_DIR.'/cached-scripts/analytics.js') AND file_exists(UPLOAD_BASE_DIR.'/cached-scripts/ga.js')){
		echo '<p>Google Analytics file (analytics.js) succesfully cached on local server!</p><p>In case you want to force the cache to be renewed, click <a href="'.get_site_url().'/wp-admin/options-general.php?page=cache-external-scripts&action=cache-scripts">this link</a>
		
		<span style="margin-top:70px;background-color:#fff;padding:10px;border:1px solid #C42429;display:inline-block;">Did this plugin help you to leverage browser caching and increase your PageSpeed Score? <a href="https://wordpress.org/support/view/plugin-reviews/cache-external-scripts" target="_blank">Please rate the plugin</a>!<br />Did not work for your site? <a href="https://wordpress.org/support/plugin/cache-external-scripts" target="_blank">Please let us know</a>!</span>';
	}else{
		echo '<p>Google Analytics file (analytics.js) is not cached yet on the local server. Please refresh <a href="'.get_site_url().'" target="_blank">your frontpage</a> to start the cron or start it manually by pressing <a href="'.get_site_url().'/wp-admin/options-general.php?page=cache-external-scripts&action=cache-scripts">this link</a>.</p>';
	}
}
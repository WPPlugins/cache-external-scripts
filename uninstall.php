<?
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

//Define uploads directory to store cached scripts
$wp_upload_dir = wp_upload_dir();
define('UPLOAD_BASE_DIR',$wp_upload_dir['basedir']);
define('UPLOAD_BASE_URL',$wp_upload_dir['baseurl']);

//Delete all cached files
array_map('unlink', glob(UPLOAD_BASE_DIR.'/cached-scripts/'."*.js"));
//Finally remove the (empty) dir
rmdir(UPLOAD_BASE_DIR.'/cached-scripts');
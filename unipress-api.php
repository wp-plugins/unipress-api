<?php
/**
 * Main PHP file used to for initial calls to UniPress API classes and functions.
 *
 * @package UniPress API
 * @since 1.0.0
 */
 
/*
Plugin Name: UniPress API
Plugin URI: http://getunipress.com/
Description: A premium WordPress plugin by UniPress.
Author: UniPress Development Team
Version: 1.2.1
Author URI: http://getunipress.com/
Tags:
*/

//Define global variables...
if ( !defined( 'UNIPRESS_STORE_URL' ) )
	define( 'UNIPRESS_STORE_URL',	'http://getunipress.com' );
	
define( 'UNIPRESS_API_NAME', 		'UniPress API' );
define( 'UNIPRESS_API_SLUG', 		'unipress-api' );
define( 'UNIPRESS_API_VERSION', 	'1.2.1' );
define( 'UNIPRESS_API_DB_VERSION', 	'1.0.0' );
define( 'UNIPRESS_API_URL', 		plugin_dir_url( __FILE__ ) );
define( 'UNIPRESS_API_PATH', 		plugin_dir_path( __FILE__ ) );
define( 'UNIPRESS_API_BASENAME', 	plugin_basename( __FILE__ ) );
define( 'UNIPRESS_API_REL_DIR', 	dirname( UNIPRESS_API_BASENAME ) );

define( 'UNIPRESS_API_IOS_MAX_CHAR', 218 ); //characters
define( 'UNIPRESS_API_ANDROID_MAX_CHAR', 4000 ); //characters


/**
 * Instantiate UniPress API class, require helper files
 *
 * @since 1.0.0
 */
function unipress_api_plugins_loaded() {
	
	require_once( 'class.php' );

	// Instantiate the Pigeon Pack class
	if ( class_exists( 'UniPress_API' ) ) {
		
		global $unipress_api;
		
		$unipress_api = new UniPress_API();
		
		require_once( 'functions.php' );
		require_once( 'post-types.php' );
		require_once( 'shortcodes.php' );
				
		if ( !empty( $_REQUEST['unipress-api'] ) ) {
			add_filter( 'jetpack_check_mobile', '__return_false' ); //JetPack messes with the mobile menu, so return false on UniPress API calls
		}

		//Internationalization
		load_plugin_textdomain( 'unipress-api', false, UNIPRESS_API_REL_DIR . '/i18n/' );
			
	}

}
add_action( 'plugins_loaded', 'unipress_api_plugins_loaded', 4815162342 ); //wait for the plugins to be loaded before init

function unipress_api_register_image_sizes() {
	add_image_size( 'unipress-phone', 640, 100, true ); //deprecated
	add_image_size( 'unipress-tablet', 1536, 240, true ); //deprecated
	add_image_size( 'unipress-tablet-portrait', 1536, 180, true );
	add_image_size( 'unipress-tablet-landscape', 2048, 180, true );
	add_image_size( 'unipress-smartphone', 1080, 168, true );
	add_image_size( 'unipress-wide-screen', 2560, 180, true );
}
add_action( 'init', 'unipress_api_register_image_sizes' );

/**
 * Activation hook
 *
 * @since 1.2.0
 *
 * @return void
*/
function unipress_api_activation() {
	if ( ! wp_next_scheduled( 'unipress_api_token_cleanup_schedule' ) ) {
		wp_schedule_event( strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', strtotime( 'Tomorrow 4AM' ) ) ) ), 'daily', 'unipress_api_token_cleanup_schedule' );
	}
}
register_activation_hook( __FILE__, 'unipress_api_activation' );

/**
 * Deactivation hook
 *
 * @since 1.2.0
 */
function unipress_api_deactivation() {
	wp_clear_scheduled_hook( 'unipress_api_token_cleanup_schedule' );
}
register_deactivation_hook( __FILE__, 'unipress_api_deactivation' );

<?php
/**
 * Registers UniPress API shortcodes
 *
 * @package UniPress API
 * @since 1.0.0
 */
 
if ( !function_exists( 'do_unipress_hide_content' ) ) { 

	/**
	 * Shortcode for UniPress to hide content that you don't want to show on the app
	 *
	 * @since 1.0.0
	 */
	function do_unipress_hide_content( $atts, $content=null ) {
	
		if ( !empty( $_GET['unipress-api'] ) ) {
			return '';
		}
		
		return do_shortcode( $content );
		
	}
	add_shortcode( 'unipress_hide_content', 'do_unipress_hide_content' );
	add_shortcode( 'unipress-hide-content', 'do_unipress_hide_content' );
	
}
 
if ( !function_exists( 'do_unipress_image' ) ) { 

	/**
	 * Shortcode for UniPress to help the mobile app in determining the header image
	 *
	 * @since 1.0.0
	 */
	function do_unipress_image( $atts ) {
	
		return '<div id="unipress-image"></div>';
		
	}
	add_shortcode( 'unipress_image', 'do_unipress_image' );
	add_shortcode( 'unipress-image', 'do_unipress_image' );
	
}
 
if ( !function_exists( 'do_unipress_video' ) ) { 

	/**
	 * Shortcode for UniPress to help the mobile app in determining the header video
	 *
	 * @since 1.0.0
	 */
	function do_unipress_video( $atts ) {
	
		return '<div id="unipress-video"></div>';
		
	}
	add_shortcode( 'unipress_video', 'do_unipress_video' );
	add_shortcode( 'unipress-video', 'do_unipress_video' );
	
}

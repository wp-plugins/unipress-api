<?php

function unipress_api_post_types() {
	$labels = array(
		'name' 					=> _x( 'Ads', 'post type general name', 'unipress-api' ),
		'singular_name' 		=> _x( 'Ad', 'post type singular name', 'unipress-api' ),
		'menu_name' 			=> _x( 'Ads', 'admin menu', 'unipress-api' ),
		'name_admin_bar' 		=> _x( 'Ad', 'add new on admin bar', 'unipress-api' ),
		'add_new' 				=> _x( 'Add New', 'ad', 'unipress-api' ),
		'add_new_item' 			=> __( 'Add New Ad', 'unipress-api' ),
		'new_item' 				=> __( 'New Ad', 'unipress-api' ),
		'edit_item' 			=> __( 'Edit Ad', 'unipress-api' ),
		'view_item' 			=> __( 'View Ad', 'unipress-api' ),
		'all_items' 			=> __( 'All Ads', 'unipress-api' ),
		'search_items' 			=> __( 'Search Ads', 'unipress-api' ),
		'parent_item_colon' 	=> __( 'Parent Ads:', 'unipress-api' ),
		'not_found' 			=> __( 'No ads found.', 'unipress-api' ),
		'not_found_in_trash' 	=> __( 'No ads found in Trash.', 'unipress-api' )
	);
	
	$args = array(
		'labels' 				=> $labels,
		'public' 				=> false,
		'publicly_queryable' 	=> false,
		'show_ui' 				=> true,
		'show_in_menu' 			=> false,
		'query_var' 			=> false,
		'rewrite' 				=> array( 'slug' => 'unipress-ad' ),
		'capability_type' 		=> 'post',
		'register_meta_box_cb' 	=> 'unipress_api_ad_metaboxes',
		'has_archive' 			=> false,
		'hierarchical' 			=> false,
		'menu_position' 		=> null,
		'supports' 				=> array( 'title', 'thumbnail' )
	);
	
	register_post_type( 'unipress-ad', $args );
	
	$labels = array(
		'name' 					=> _x( 'Push Notifications', 'post type general name', 'unipress-api' ),
		'singular_name' 		=> _x( 'Push Notification', 'post type singular name', 'unipress-api' ),
		'menu_name' 			=> _x( 'Push Notifications', 'admin menu', 'unipress-api' ),
		'name_admin_bar' 		=> _x( 'Push', 'add new on admin bar', 'unipress-api' ),
		'add_new' 				=> _x( 'Add New', 'Push', 'unipress-api' ),
		'add_new_item' 			=> __( 'Add New Push Notification', 'unipress-api' ),
		'new_item' 				=> __( 'New Push', 'unipress-api' ),
		'edit_item' 			=> __( 'Edit Push', 'unipress-api' ),
		'view_item' 			=> __( 'View Push', 'unipress-api' ),
		'all_items' 			=> __( 'All Push Notifications', 'unipress-api' ),
		'search_items' 			=> __( 'Search Push Notifications', 'unipress-api' ),
		'parent_item_colon' 	=> __( 'Parent Push Notifications:', 'unipress-api' ),
		'not_found' 			=> __( 'No Push Notifications found.', 'unipress-api' ),
		'not_found_in_trash' 	=> __( 'No Push Notifications found in Trash.', 'unipress-api' )
	);
	
	$args = array(
		'labels' 				=> $labels,
		'public' 				=> false,
		'publicly_queryable' 	=> false,
		'show_ui' 				=> true,
		'show_in_menu' 			=> false,
		'query_var' 			=> false,
		'rewrite' 				=> array( 'slug' => 'unipress-ad' ),
		'capability_type' 		=> 'post',
		'register_meta_box_cb' 	=> 'unipress_api_push_metaboxes',
		'has_archive' 			=> false,
		'hierarchical' 			=> false,
		'menu_position' 		=> null,
		'supports' 				=> array( 'title' )
	);
	
	register_post_type( 'unipress-push', $args );
}
add_action( 'init', 'unipress_api_post_types' );

if ( !function_exists( 'unipress_api_ad_metaboxes' ) ) {
		
	/**
	 * Registers metaboxes for IssueM Articles
	 *
	 * @since 1.0.0
	 */
	function unipress_api_ad_metaboxes() {
		
		remove_meta_box( 'leaky_paywall_content_visibility', 'unipress-ad', 'side' );
		add_meta_box( 'unipress_ad_meta_box', __( 'UniPress Ad Options', 'unipress-api' ), 'unipress_ad_meta_box', 'unipress-ad', 'normal', 'high' );
		
		do_action( 'unipress_api_ad_metaboxes' );
		
	}

}

if ( !function_exists( 'unipress_ad_meta_box' ) ) {
	
	function unipress_ad_meta_box( $post ) {
		$ad_type = get_post_meta( $post->ID, '_ad_type', true );
		$ad_link = get_post_meta( $post->ID, '_ad_link', true );
		?>
		
		<div id="unipress-ad-metabox">
		
			<p>
			<label for="unipress-ad-type"><?php _e( 'Ad Type:', 'issuem' ); ?></label>&nbsp;
			<select id="unipress-ad-type" name="ad-type">
				<option value="tablet-portrait" <?php selected( 'tablet-portrait', $ad_type ) ?>><?php _e( 'Tablet Portrait', 'unipress-api' ); ?></option>
				<option value="tablet-landscape" <?php selected( 'tablet-landscape', $ad_type ) ?>><?php _e( 'Tablet Landscape', 'unipress-api' ); ?></option>
				<option value="smartphone" <?php selected( 'smartphone', $ad_type ) ?>><?php _e( 'Smartphone', 'unipress-api' ); ?></option>
				<option value="wide-screen" <?php selected( 'wide-screen', $ad_type ) ?>><?php _e( 'Wide Screen', 'unipress-api' ); ?></option>
				<option value="phone" <?php selected( 'phone', $ad_type ) ?>><?php _e( 'Phone (deprecated)', 'unipress-api' ); ?></option>
				<option value="tablet" <?php selected( 'tablet', $ad_type ) ?>><?php _e( 'Tablet (deprecated)', 'unipress-api' ); ?></option>
			</select>
			</p>
			
			<p>
			<label for="unipress-ad-link"><?php _e( 'Ad Link:', 'issuem' ); ?></label>&nbsp;
			<input id="unipress-ad-link" type="text" name="ad-link" value="<?php echo $ad_link; ?>" />
			</p>
			
			<p class="descripton">
				<strong><?php _e( 'Tablet Portrait:', 'unipress-api' ); ?></strong> 1536px x 180px<br />
				<strong><?php _e( 'Tablet Landscape:', 'unipress-api' ); ?></strong> 2048px x 180px <br />
				<strong><?php _e( 'Smartphone:', 'unipress-api' ); ?></strong> 1080px x 168px<br />
				<strong><?php _e( 'Wide Screen:', 'unipress-api' ); ?></strong> 2560px x 180px<br />
			</p>

		</div>
		
		<?php	
	}
	
}

if ( !function_exists( 'save_unipress_ad_meta_box' ) ) {
	
	/**
	 * Saves Article meta
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id WordPress post ID
	 */
	function save_unipress_ad_meta_box( $post_id ) {
	
		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
			
		if ( isset( $_REQUEST['_inline_edit'] ) || isset( $_REQUEST['doing_wp_cron'] ) )
			return;
			
		if ( !empty( $_POST['ad-type'] ) )
			update_post_meta( $post_id, '_ad_type', $_POST['ad-type'] );
		else
			delete_post_meta( $post_id, '_ad_type' );
			
		if ( !empty( $_POST['ad-link'] ) )
			update_post_meta( $post_id, '_ad_link', $_POST['ad-link'] );
		else
			delete_post_meta( $post_id, '_ad_link' );
			
		do_action( 'save_unipress_ad_meta_box', $post_id );
				
	}
	add_action( 'save_post_unipress-ad', 'save_unipress_ad_meta_box' );

}

if ( !function_exists( 'unipress_api_push_metaboxes' ) ) {

	/**
	 * Registers metaboxes for IssueM Push Notifications
	 *
	 * @since CHANGEME
	 */
	function unipress_api_push_metaboxes() {
		
		remove_meta_box( 'leaky_paywall_content_visibility', 'unipress-push', 'side' );
		add_meta_box( 'unipress_push_meta_box', __( 'Push Notification', 'unipress-api' ), 'unipress_push_meta_box', 'unipress-push', 'normal', 'high' );
		
		do_action( 'unipress_api_push_metaboxes' );
		
	}
	
}

if ( !function_exists( 'unipress_push_meta_box' ) ) {
	
	function unipress_push_meta_box( $post ) {
		$push_type = get_post_meta( $post->ID, '_push_type', true );
		$push_content = get_post_meta( $post->ID, '_push_content', true );
		$max_length = ( 'android' === $push_type ) ? UNIPRESS_API_ANDROID_MAX_CHAR : UNIPRESS_API_IOS_MAX_CHAR;
		?>
		
		<div id="unipress-push-metabox">
		
			<p>
			<label for="unipress-push-type"><?php _e( 'Push Type:', 'issuem' ); ?></label>&nbsp;
			<select id="unipress-push-type" name="push-type">
				<option value="all" <?php selected( 'all', $push_type ) ?>><?php _e( 'Both iOS and Android', 'unipress-api' ); ?></option>
				<option value="iOS" <?php selected( 'ios', $push_type ) ?>><?php _e( 'iOS', 'unipress-api' ); ?></option>
				<option value="Android" <?php selected( 'android', $push_type ) ?>><?php _e( 'Android', 'unipress-api' ); ?></option>
			</select>
			</p>
			
			<p>
			<?php
			$remaining = $max_length - strlen( $push_content );
			if ( 10 > $remaining ) {
				$length_class = 'unipress-push-count-superwarn';
			} else if ( 20 > $remaining ) {
				$length_class = 'unipress-push-count-warn';
			} else {
				$length_class = 'unipress-push-count';
			}
			?>
			<label id="unipress-content-label" for="unipress-push-content"><?php printf( __( 'Push Content (%s bytes):', 'issuem' ), '<span id="push-current-length" class="' . $length_class . '">' . strlen( $push_content ) . '</span> / <span id="push-max-length">' . $max_length . '</span>' ); ?></label><br/>
			<textarea id="unipress-push-content" name="push-content"><?php echo $push_content; ?></textarea><br/>
			</p>
			
	        <script type="text/javascript" charset="utf-8">
	            var UNIPRESS_API_IOS_MAX_CHAR = <?php echo UNIPRESS_API_IOS_MAX_CHAR; ?>;
	            var UNIPRESS_API_ANDROID_MAX_CHAR = <?php echo UNIPRESS_API_ANDROID_MAX_CHAR; ?>;
	        </script>

		</div>
		
		<?php	
	}
	
}

if ( !function_exists( 'save_unipress_push_meta_box' ) ) {
	
	/**
	 * Saves Article meta
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id WordPress post ID
	 */
	function save_unipress_push_meta_box( $post_id ) {
	
		// verify if this is an auto save routine. 
		// If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return;
			
		if ( isset( $_REQUEST['_inline_edit'] ) || isset( $_REQUEST['doing_wp_cron'] ) )
			return;
			
		if ( !empty( $_POST['push-type'] ) )
			update_post_meta( $post_id, '_push_type', $_POST['push-type'] );
		else
			delete_post_meta( $post_id, '_push_type' );
			
		if ( !empty( $_POST['push-content'] ) )
			update_post_meta( $post_id, '_push_content', $_POST['push-content'] );
		else
			delete_post_meta( $post_id, '_push_content' );
			
		do_action( 'save_unipress_push_meta_box', $post_id );
				
	}
	add_action( 'save_post_unipress-push', 'save_unipress_push_meta_box' );

}
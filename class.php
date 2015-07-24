<?php
/**
 * Registers UniPress API class
 *
 * @package UniPress API
 * @since 1.0.0
 */

/**
 * This class registers the main issuem functionality
 *
 * @since 1.0.0
 */
if ( ! class_exists( 'UniPress_API' ) ) {
	
	class UniPress_API {
		
		private $leaky_paywall_enabled;
		
		/**
		 * Class constructor, puts things in motion
		 *
		 * @since 1.0.0
		 */
		function __construct() {
			
			register_nav_menu( 'unipress-app-menu', 'UniPress Device Menu' );
			
			add_action( 'admin_init', array( $this, 'upgrade' ) );
			
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_wp_enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( $this, 'admin_wp_print_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			
			add_action( 'wp', array( $this, 'process_requests' ), 15 );
	        // Whenever you publish new content, notify UniPress Servers
	        add_action( 'transition_post_status', array( $this, 'push_notification' ), 100, 3 );
	        
	        add_action( 'wp_head', array( $this, 'deeplinks' ) );
	        
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( is_plugin_active( 'issuem-leaky-paywall/issuem-leaky-paywall.php' ) ) {
				$this->leaky_paywall_enabled = true;
				add_filter( 'leaky_paywall_subscriber_info_paid_subscriber_end', array( $this, 'leaky_paywall_subscriber_info_paid_subscriber_end' ) );
			} else {
				$this->leaky_paywall_enabled = false;
			}

		}
		
		function upgrade() {
			
			$old_version = get_option( 'unipress_api_version', '0.0.0' );
						
			if ( version_compare( $old_version, '1.2.0', '<' ) ) {
				$this->upgrade_to_1_2_0();
			}
						
			update_option( 'unipress_api_version', UNIPRESS_API_VERSION );
			
		}
		
		function upgrade_to_1_2_0() {
			if ( ! wp_next_scheduled( 'unipress_api_token_cleanup_schedule' ) ) {
				wp_schedule_event( strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', strtotime( 'Tomorrow 4AM' ) ) ) ), 'daily', 'unipress_api_token_cleanup_schedule' );
			}
		}
		
		function admin_wp_enqueue_scripts( $hook_suffix ) {
			
			//wp_print_r( $hook_suffix );
			$post_type = false;

            if ( isset( $_REQUEST['post_type'] ) ) {

                    $post_type = $_REQUEST['post_type'];

            } else {

                    if ( isset( $_REQUEST['post'] ) )
                            $post_id = (int) $_REQUEST['post'];
                    elseif ( isset( $_REQUEST['post_ID'] ) )
                            $post_id = (int) $_REQUEST['post_ID'];
                    else
                            $post_id = 0;

                    if ( $post_id )
                            $post = get_post( $post_id );

                    if ( isset( $post ) && !empty( $post ) )
                            $post_type = $post->post_type;

            }
            
			if ( 'toplevel_page_unipress-settings' === $hook_suffix )
				wp_enqueue_script( 'unipress_admin_js', UNIPRESS_API_URL . 'js/admin.js', array( 'jquery' ), UNIPRESS_API_VERSION );

			if ( 'unipress-push' === $post_type && ( 'post-new.php' === $hook_suffix || 'post.php' === $hook_suffix ) ) {
				wp_enqueue_script( 'unipress_admin_push_js', UNIPRESS_API_URL . 'js/admin-push.js', array( 'jquery' ), UNIPRESS_API_VERSION );
				wp_enqueue_script( 'unipress_utf8_js', UNIPRESS_API_URL . 'js/utf8.js', array( 'unipress_admin_push_js' ), UNIPRESS_API_VERSION );
			}
		}
		
		function admin_wp_print_styles() {
			global $hook_suffix;
			$post_type = false;
			
            $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

            if ( isset( $_REQUEST['post_type'] ) ) {

                    $post_type = $_REQUEST['post_type'];

            } else {

                    if ( isset( $_REQUEST['post'] ) )
                            $post_id = (int) $_REQUEST['post'];
                    elseif ( isset( $_REQUEST['post_ID'] ) )
                            $post_id = (int) $_REQUEST['post_ID'];
                    else
                            $post_id = 0;

                    if ( $post_id )
                            $post = get_post( $post_id );

                    if ( isset( $post ) && !empty( $post ) )
                            $post_type = $post->post_type;

            }
            
			if ( 'toplevel_page_unipress-settings' === $hook_suffix )
				wp_enqueue_style( 'unipress_admin_css', UNIPRESS_API_URL . 'css/admin.css', '', UNIPRESS_API_VERSION );
			if ( 'unipress-push' === $post_type && ( 'post-new.php' === $hook_suffix || 'post.php' === $hook_suffix ) )
				wp_enqueue_style( 'unipress_admin_push_css', UNIPRESS_API_URL . 'css/admin-push.css', '', UNIPRESS_API_VERSION );
		}
		
		function wp_enqueue_scripts() {
			wp_enqueue_script( 'unipress-api', UNIPRESS_API_URL . '/js/unipress.js', array( 'jquery' ), UNIPRESS_API_VERSION );
			wp_localize_script( 'unipress-api', 'ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
			wp_enqueue_style( 'unipress-api', UNIPRESS_API_URL . '/css/unipress.css', '', UNIPRESS_API_VERSION );
		}
		
		function admin_menu() {
			
			add_menu_page( __( 'UniPress', 'unipress-api' ), __( 'UniPress', 'unipress-api' ), apply_filters( 'manage_unipress_api_settings', 'manage_options' ), 'unipress-settings', array( $this, 'settings_page' ), UNIPRESS_API_URL . '/images/issuem-16x16.png' );
						
			add_submenu_page( 'unipress-settings', __( 'Settings', 'unipress-api' ), __( 'Settings', 'unipress-api' ), apply_filters( 'manage_unipress_api_settings', 'manage_options' ), 'unipress-settings', array( $this, 'settings_page' ) );
			
			add_submenu_page( 'unipress-settings', __( 'Ads', 'unipress-api' ), __( 'Ads', 'unipress-api' ), apply_filters( 'manage_unipress_api_settings', 'manage_options' ), 'edit.php?post_type=unipress-ad' );
			add_submenu_page( 'unipress-settings', __( 'New Ad', 'unipress-api' ), __( 'New Ad', 'unipress-api' ), apply_filters( 'manage_unipress_api_settings', 'manage_options' ), 'post-new.php?post_type=unipress-ad' );
			
			add_submenu_page( 'unipress-settings', __( 'Push Notifications', 'unipress-api' ), __( 'Push Notifications', 'unipress-api' ), apply_filters( 'manage_unipress_api_settings', 'manage_options' ), 'edit.php?post_type=unipress-push' );
			add_submenu_page( 'unipress-settings', __( 'New Push', 'unipress-api' ), __( 'New Push', 'unipress-api' ), apply_filters( 'manage_unipress_api_settings', 'manage_options' ), 'post-new.php?post_type=unipress-push' );

		}
		
		/**
		 * Get UniPress API options
		 *
		 * @since 1.0.0
		 */
		function get_settings() {
			
			$defaults = array( 
				'device-limit' 				=> 5,
				'subscription-ids' 			=> array(),
				'push-device-type' 			=> 'all',
				'dev-mode' 					=> false,
				'app-id' 					=> '',
				'secret-key' 				=> '',
				'silent-push' 				=> true,
				'attachment-baseurl' 		=> '', //For CDNs
				'excerpt-type' 				=> 'default',
				'excerpt-size' 				=> 55,
				'excluded-cats' 			=> array(),
				//Deeplinks
				'dl-enabled' 					=> false,
				'dl-custom-schema' 				=> '',
				'dl-app-description' 			=> '',
				'dl-app-logo-url' 				=> '',
				'dl-ios-enabled' 				=> false,
				'dl-ios-app-name' 				=> '',
				'dl-ios-app-id' 				=> '',
				'dl-ios-twitter-enabled' 		=> false,
				'dl-ios-facebook-enabled' 		=> false,
				'dl-android-enabled' 			=> false,
				'dl-android-app-name' 			=> '',
				'dl-android-app-id' 			=> '',
				'dl-android-twitter-enabled' 	=> false,
				'dl-android-facebook-enabled' 	=> false,
				'dl-facebook-fallback' 			=> false,
				'css' 						=> '', //Custom CSS
				'js' 						=> '', //Custom JS
			);
		
			$defaults = apply_filters( 'unipress_api_settings_defaults', $defaults );
			
			$settings = get_option( 'unipress-api' );
												
			return wp_parse_args( $settings, $defaults );
			
		}
		
		/**
		 * Update UniPress API options
		 *
		 * @since 1.0.0
		 */
		function update_settings( $settings ) {
			
			update_option( 'unipress-api', $settings );
			
		}
		
		/**
		 * Create and Display IssueM settings page
		 *
		 * @since 1.0.0
		 */
		function settings_page() {
			
			// Get the user options
			$settings = $this->get_settings();

			if ( isset( $_REQUEST['unipress_api_settings_nonce'] ) 
				&& wp_verify_nonce( $_REQUEST['unipress_api_settings_nonce'], 'save_unipress_api_settings' ) ) {
									
				if ( isset( $_REQUEST['device-limit'] ) && is_numeric( $_REQUEST['device-limit'] ) ) {
					$settings['device-limit'] = $_REQUEST['device-limit'];
				} else {
					$settings['device-limit'] = 5;
				}
				
				if ( !empty( $_REQUEST['subscription-ids'] ) ) {
					$subscription_ids = array();
					foreach( $_REQUEST['subscription-ids'] as $subscription_id ) {
						if ( !empty( $subscription_id['app-id'] ) && isset( $subscription_id['level-id'] ) && is_numeric( $subscription_id['level-id'] ) ) {
							$app_id = trim( $subscription_id['app-id'] );
							$subscription_ids[$app_id] = $subscription_id['level-id'];
						}
					}
					$settings['subscription-ids'] = $subscription_ids;
				} else {
					$settings['subscription-ids'] = array();
				}
					
				if ( !empty( $_REQUEST['push-device-type'] ) && in_array( $_REQUEST['push-device-type'], array( 'all', 'iOS', 'Android' ) ) ) {
					$settings['push-device-type'] = $_REQUEST['push-device-type'];
				} else {
					unset( $settings['push-device-type'] );
				}
					
				if ( !empty( $_REQUEST['dev-mode'] ) ) {
					$settings['dev-mode'] = true;
				} else {
					$settings['dev-mode'] = false;
				}
				
				if ( !empty( $_REQUEST['app-id'] ) ) {
					$settings['app-id'] = trim( $_REQUEST['app-id'] );
				} else {
					$settings['app-id'] = '';
				}
					
				if ( !empty( $_REQUEST['secret-key'] ) ) {
					$settings['secret-key'] = trim( $_REQUEST['secret-key'] );
				} else {
					$settings['secret-key'] = '';
				}
					
				if ( !empty( $_REQUEST['silent-push'] ) ) {
					$settings['silent-push'] = true;
				} else {
					$settings['silent-push'] = false;
				}
					
				if ( !empty( $_REQUEST['attachment-baseurl'] ) ) {
					$baseurl = trim( $_REQUEST['attachment-baseurl'] );
					$settings['attachment-baseurl'] = $baseurl;
					if ( false === filter_var( $baseurl, FILTER_VALIDATE_URL ) ) {
						$errors[] = '<div class="error"><p><strong>' . __( 'Invalid URL entered for Attachment Baseurl.', 'unipress-api' ) . '</strong></p></div>';
					}
				} else {
					$settings['attachment-baseurl'] = '';
				}
					
				if ( !empty( $_REQUEST['excerpt-type'] ) ) {
					$settings['excerpt-type'] = $_REQUEST['excerpt-type'];
				} else {
					unset( $settings['excerpt-type'] );
				}
					
				if ( !empty( $_REQUEST['excerpt-size'] ) ) {
					$settings['excerpt-size'] = intval( $_REQUEST['excerpt-size'] );
				} else {
					unset( $settings['excerpt-size'] );
				}
					
				if ( !empty( $_REQUEST['excluded-cats'] ) ) {
					$settings['excluded-cats'] = $_REQUEST['excluded-cats'];
				} else {
					unset( $settings['excluded-cats'] );
				}
				
				//Deeplinks
				if ( !empty( $_REQUEST['dl-enabled'] ) ) { 
					$settings['dl-enabled'] = true;
				} else {
					$settings['dl-enabled'] = false;
				}
				if ( !empty( $_REQUEST['dl-custom-schema'] ) ) { 
					$settings['dl-custom-schema'] = trim( $_REQUEST['dl-custom-schema'] );
				} else {
					$settings['dl-custom-schema'] = '';
				}
				if ( !empty( $_REQUEST['dl-app-description'] ) ) {
					$settings['dl-app-description'] = trim( $_REQUEST['dl-app-description'] );
				} else {
					$settings['dl-app-description'] = '';
				}
				if ( !empty( $_REQUEST['dl-app-logo-url'] ) ) {
					$settings['dl-app-logo-url'] = trim( $_REQUEST['dl-app-logo-url'] );
				} else {
					$settings['dl-app-logo-url'] = '';
				}
				if ( !empty( $_REQUEST['dl-ios-enabled'] ) ) {
					$settings['dl-ios-enabled'] = true;
				} else {
					$settings['dl-ios-enabled'] = false;
				}
				if ( !empty( $_REQUEST['dl-ios-app-name'] ) ) {
					$settings['dl-ios-app-name'] = trim( $_REQUEST['dl-ios-app-name'] );
				} else {
					$settings['dl-ios-app-name'] = '';
				}
				if ( !empty( $_REQUEST['dl-ios-app-id'] ) ) {
					$settings['dl-ios-app-id'] = trim( $_REQUEST['dl-ios-app-id'] );
				} else {
					$settings['dl-ios-app-id'] = '';
				}
				if ( !empty( $_REQUEST['dl-ios-twitter-enabled'] ) ) {
					$settings['dl-ios-twitter-enabled'] = true;
				} else {
					$settings['dl-ios-twitter-enabled'] = false;
				}
				if ( !empty( $_REQUEST['dl-ios-facebook-enabled'] ) ) {
					$settings['dl-ios-facebook-enabled'] = true;
				} else {
					$settings['dl-ios-facebook-enabled'] = false;
				}
				if ( !empty( $_REQUEST['dl-android-enabled'] ) ) {
					$settings['dl-android-enabled'] = true;
				} else {
					$settings['dl-android-enabled'] = false;
				}
				if ( !empty( $_REQUEST['dl-android-app-name'] ) ) {
					$settings['dl-android-app-name'] = trim( $_REQUEST['dl-android-app-name'] );
				} else {
					$settings['dl-android-app-name'] = '';
				}
				if ( !empty( $_REQUEST['dl-android-app-id'] ) ) {
					$settings['dl-android-app-id'] = trim( $_REQUEST['dl-android-app-id'] );
				} else {
					$settings['dl-android-app-id'] = '';
				}
				if ( !empty( $_REQUEST['dl-android-twitter-enabled'] ) ) {
					$settings['dl-android-twitter-enabled'] = true;
				} else {
					$settings['dl-android-twitter-enabled'] = false;
				}
				if ( !empty( $_REQUEST['dl-android-facebook-enabled'] ) ) {
					$settings['dl-android-facebook-enabled'] = true;
				} else {
					$settings['dl-android-facebook-enabled'] = false;
				}
				if ( !empty( $_REQUEST['dl-facebook-fallback'] ) ) {
					$settings['dl-facebook-fallback'] = true;
				} else {
					$settings['dl-facebook-fallback'] = false;
				}

				if ( !empty( $_REQUEST['css'] ) ) {
					$settings['css'] = stripslashes( $_REQUEST['css'] );
				} else {
					$settings['css'] = '';
				}

				if ( !empty( $_REQUEST['js'] ) ) {
					$settings['js'] = stripslashes( $_REQUEST['js'] );
				} else {
					$settings['js'] = '';
				}
				
				if ( !empty( $settings['app-id'] ) && !empty( $settings['secret-key'] ) ) {
					if ( !$this->verify_secret_key( $settings['app-id'], $settings['secret-key'], $settings['dev-mode'] ) ) {
						$errors[] = '<div class="error"><p><strong>' . __( 'Error validating Secret Key. Please try again.', 'unipress-api' ) . '</strong></p></div>';
					}
				}
				
				if ( !empty( $errors ) ) {
					foreach( $errors as $error ) {
						echo $error;
					}
				} else {
					$this->update_settings( $settings );
					// update settings notification ?>
					<div class="updated"><p><strong><?php _e( 'UniPress Settings Updated.', 'unipress-api' );?></strong></p></div>
					<?php
				}
				
				do_action( 'update_unipress_api_settings', $settings );
				
			}
						
			// Display HTML form for the options below
			?>
			<div class=wrap>
            <div style="width:70%;" class="postbox-container">
            <div class="metabox-holder">	
            <div class="meta-box-sortables ui-sortable">
            
                <form id="issuem" method="post" action="">

                    <h2 style='margin-bottom: 10px;' ><?php _e( 'UniPress Settings', 'unipress-api' ); ?></h2>
                
                    <?php do_action( 'unipress_api_settings_form_start', $settings ); ?>
                    
                    <div id="modules" class="postbox">
                    
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        
                        <h3 class="hndle"><span><?php _e( 'UniPress Options', 'unipress-api' ); ?></span></h3>
                        
                        <div class="inside">
                        
                        <table id="unipress_administrator_options" class="unipress-table">
                                               
                        	<tr>
                                <th><?php _e( 'Device Limit', 'unipress-api' ); ?></th>
                                <td>
                                	<input type="number" id="device-limit" class="small-text" name="device-limit" value="<?php echo htmlspecialchars( stripcslashes( $settings['device-limit'] ) ); ?>" />
                                	<p class="description"><?php _e( 'The number of mobile devices a user can register', 'unipress-api' ); ?></p>
                                </td>
                            </tr>
                            <?php if ( $this->leaky_paywall_enabled ) { ?>
                        	<tr>
                                <th>
	                                <?php _e( 'Subscription ID Matching', 'unipress-api' ); ?>
                                	<p class="description"><?php _e( 'Enter the In-App Product ID and select the Leaky Paywall Subscription that a user should get when purchasing from their mobile device.', 'unipress-api' ); ?></p>

	                            </th>
                                <td>
									<?php
									$count = 0;
									$lp_settings = get_leaky_paywall_settings();
									if ( !empty( $lp_settings['levels'] ) ) {
										echo '<div id="subscription-ids-matching">';
										if ( !empty( $settings['subscription-ids'] ) ) {

											foreach( $settings['subscription-ids'] as $app_id => $subscription_id ) {
												echo '<p class="subscription-id-match">';
												echo '<input type="text" class="in_app_purchase_id" name="subscription-ids[' . $count . '][app-id]" value="' . $app_id .'" />';
												echo '&nbsp;';
												echo '<select name="subscription-ids[' . $count . '][level-id]">';
												foreach( $lp_settings['levels'] as $level_id => $level ) {
													echo '<option value="' . $level_id .'" ' . selected( $level_id, $subscription_id, true ) . '>' . $level['label'] . '</option>';
												}
												echo '</select>';
												echo '&nbsp;';
									            echo '<a class="subscription-id-delete" href="#">&times;</a>'; 
												echo '</p>';
												$count++;
											}
										}
										echo '</div>';
										
									    echo '<script type="text/javascript" charset="utf-8">';
									    echo '    var unipress_subscription_ids_iteration = ' . $count . ';';
									    echo '</script>';
									    
										submit_button( __( 'Add New Subscription Match', 'unipress-api' ), 'secondary', 'add-new-susbcription-match', true );
									} else {
										echo '<p>' . __( 'No Subscriptions Found. Please add some to the Leaky Paywall settings.', 'unipress-api' ) . '</p>';
									}
									?>
                                </td>
                            </tr>
                            <?php } ?>
                        	<tr>
                                <th><?php _e( 'Push Notification Device Type(s)', 'unipress-api' ); ?></th>
                                <td>
									<?php
									echo '<p>';
									echo '<select name="push-device-type">';
									echo '<option value="all" ' . selected( 'all', $settings['push-device-type'], true ) . '>' . __( 'iOS and Android', 'unipress-api' ) . '</option>';
									echo '<option value="iOS" ' . selected( 'iOS', $settings['push-device-type'], true ) . '>' . __( 'iOS', 'unipress-api' ) . '</option>';
									echo '<option value="Android" ' . selected( 'Android', $settings['push-device-type'], true ) . '>' . __( 'Android', 'unipress-api' ) . '</option>';
									echo '</select>';
									echo '</p>';
									?>
                                </td>
                            </tr>
                            
                        	<tr>
                                <th><?php _e( 'UniPress App Dev Mode', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="checkbox" id="dev-mode" name="dev-mode" <?php checked( $settings['dev-mode'] ); ?> /></p>
                                </td>
                            </tr> 
                        	<tr>
                                <th><?php _e( 'UniPress In-App Product ID', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="app-id" class="" name="app-id" value="<?php echo htmlspecialchars( stripcslashes( $settings['app-id'] ) ); ?>" /></p>
                                </td>
                            </tr>            
                        	<tr>
                                <th><?php _e( 'UniPress App Secret Key', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="secret-key" class="" name="secret-key" value="<?php echo htmlspecialchars( stripcslashes( $settings['secret-key'] ) ); ?>" /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'Silent Push Notification', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="checkbox" id="silent-push" name="silent-push" <?php checked( $settings['silent-push'] ); ?> /></p>
                                	<p class="description"><?php _e( 'Silent Push tells the mobile device when new content is available and caches the latest content', 'unipress' ); ?></p>
                                </td>
                            </tr> 
                        	<tr>
                                <th><?php _e( 'Attachment Baseurl', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="attachment-baseurl" class="" name="attachment-baseurl" value="<?php echo htmlspecialchars( stripcslashes( $settings['attachment-baseurl'] ) ); ?>" /></p>
                                	<p class="description"><?php _e( 'Change this if you are using a CDN to deliver your files.', 'unipress' ); ?></p>
                                </td>
                            </tr> 
                        	<tr>
                                <th><?php _e( 'Excerpt Options', 'unipress-api' ); ?></th>
                                <td>
	                                <p>
		                                <?php 
			                            $excerpt_options = array(
				                            'default' => __( 'WordPress Default', 'unipress-api' ),
				                            'content' => __( 'Post Content', 'unipress-api' ),
			                            );
			                            $select = '<select id="unipress-excerpt-type" name="excerpt-type">';
			                            foreach ( $excerpt_options as $type => $label ) {
				                            $select .= '<option value="' . $type . '" ' . selected( $type, $settings['excerpt-type'], false ) . '>' . $label . '</option>';
			                            }
			                            $select .= '</select>';
			                            
			                            if ( 'content' === $settings['excerpt-type'] ) {
				                            $hidden = '';
			                            } else {
				                            $hidden = 'display: none;';
			                            }
			                            $limit_input = '<input type="number" class="small-text" name="excerpt-size" value="' . $settings['excerpt-size'] . '" />';
			                            $limit = '<span id="unipress-excerpt-size" style="' . $hidden . '">' . sprintf( __( '(limited to %s words)', 'unipress-api' ), $limit_input ) . '</span>';
			                            
			                            printf( __( 'Use %s %s for the excerpt in UniPress.', 'unipress-api' ), $select, $limit ); 
			                            ?>
	                                </p>
                                </td>
                            </tr> 
                        	<tr>
                                <th><?php _e( 'Exclude Categories', 'unipress-api' ); ?></th>
                                <td>
		                            <p>
		                            <select id="excluded-cats" name="excluded-cats[]" multiple="multiple" size="5">
			                            <?php 
			                            $categories = get_categories( array( 'hide_empty' => 0, 'orderby' => 'name' ) );
			                            foreach ( $categories as $category ) {
			                                ?>
			                                <option value="<?php echo $category->term_id; ?>" <?php selected( in_array( $category->term_id, $settings['excluded-cats'] ) ); ?>><?php echo $category->name; ?></option>
			                                <?php
			                            }
			                            ?>
		                            </select>
		                            </p>
                                	<p class="description"><?php _e( 'Select any categories you do not want to display in UniPress.', 'unipress' ); ?></p>
                                </td>
                            </tr> 
                            
                        </table>
                                                                          
                        <p class="submit">
                            <input class="button-primary" type="submit" name="update_unipress_api_settings" value="<?php _e( 'Save Settings', 'unipress-api' ) ?>" />
                        </p>

                        </div>
                        
                    </div>
                    
                    <?php wp_nonce_field( 'save_unipress_api_settings', 'unipress_api_settings_nonce' ); ?>
                    
                    <div id="modules" class="postbox">
                    
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        
                        <h3 class="hndle"><span><?php _e( 'Application Deeplink Options', 'unipress-api' ); ?></span></h3>
                        
                        <div class="inside">
                        
                        <table id="unipress_deeplink_options" class="unipress-table deeplink-table">
                        	<tr>
                                <th><?php _e( 'Enable Deeplinks', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="checkbox" id="dl-enabled" name="dl-enabled" <?php checked( $settings['dl-enabled'] ); ?> /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'Custom Schema', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="dl-custom-schema" name="dl-custom-schema" value="<?php echo $settings['dl-custom-schema']; ?>" /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'App Logo URL', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="dl-app-logo-url" name="dl-app-logo-url" value="<?php echo $settings['dl-app-logo-url']; ?>" /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'App Description', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="dl-app-description" name="dl-app-description" value="<?php echo $settings['dl-app-description']; ?>" /></p>
                                </td>
                            </tr>
                        </table>
                        
                        <table id="unipress_deeplink_ios_options" class="unipress-table deeplink-table">
                        	<tr>
                                <th><?php _e( 'Enable iOS App Links', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="checkbox" id="dl-ios-enabled" name="dl-ios-enabled" <?php checked( $settings['dl-ios-enabled'] ); ?> /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'App Name', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="dl-ios-app-name" name="dl-ios-app-name" value="<?php echo $settings['dl-ios-app-name']; ?>" /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'App ID', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="dl-ios-app-id" name="dl-ios-app-id" value="<?php echo $settings['dl-ios-app-id']; ?>" /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'Social Networks', 'unipress-api' ); ?></th>
                                <td>
	                                <p>
                                	<input type="checkbox" id="dl-ios-twitter-enabled" name="dl-ios-twitter-enabled" <?php checked( $settings['dl-ios-twitter-enabled'] ); ?> />
                                	<label for="dl-ios-twitter-enabled"><?php _e( 'Enable Twitter', 'unipress-api' ); ?></label>
	                                </p>
	                                <p>
                                	<input type="checkbox" id="dl-ios-facebook-enabled" name="dl-ios-facebook-enabled" <?php checked( $settings['dl-ios-facebook-enabled'] ); ?> />
                                	<label for="dl-ios-facebook-enabled"><?php _e( 'Enable Facebook', 'unipress-api' ); ?></label>
	                                </p>
                                </td>
                            </tr>
                        </table>
                        
                        <table id="unipress_deeplink_android_options" class="unipress-table deeplink-table">
                        	<tr>
                                <th><?php _e( 'Enable Android App Links', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="checkbox" id="dl-android-enabled" name="dl-android-enabled" <?php checked( $settings['dl-android-enabled'] ); ?> /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'App Name', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="dl-android-app-name" name="dl-android-app-name" value="<?php echo $settings['dl-android-app-name']; ?>" /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'App ID', 'unipress-api' ); ?></th>
                                <td>
                                	<p><input type="text" id="dl-android-app-id" name="dl-android-app-id" value="<?php echo $settings['dl-android-app-id']; ?>" /></p>
                                </td>
                            </tr>
                        	<tr>
                                <th><?php _e( 'Social Networks', 'unipress-api' ); ?></th>
                                <td>
	                                <p>
                                	<input type="checkbox" id="dl-android-twitter-enabled" name="dl-android-twitter-enabled" <?php checked( $settings['dl-android-twitter-enabled'] ); ?> />
                                	<label for="dl-android-twitter-enabled"><?php _e( 'Enable Twitter', 'unipress-api' ); ?></label>
	                                </p>
	                                <p>
                                	<input type="checkbox" id="dl-android-facebook-enabled" name="dl-android-facebook-enabled" <?php checked( $settings['dl-android-facebook-enabled'] ); ?> />
                                	<label for="dl-android-facebook-enabled"><?php _e( 'Enable Facebook', 'unipress-api' ); ?></label>
	                                </p>
                                </td>
                            </tr>
                        </table>

                        <table id="unipress_deeplink_additional_options" class="unipress-table deeplink-table">
                        	<tr>
                                <th><?php _e( 'Enable Facebook Fallback', 'unipress-api' ); ?></th>
                                <td>
                                	<p>
	                                <input type="checkbox" id="dl-facebook-fallback" name="dl-facebook-fallback" <?php checked( $settings['dl-facebook-fallback'] ); ?> />
	                                <label for="dl-facebook-fallback"><?php _e( 'Fallback to Website URL (for Facebook App links).', 'unipress-api' ); ?></label>
	                                </p>
                                </td>
                            </tr>
                        </table>
                                                                                                  
                        <p class="submit">
                            <input class="button-primary" type="submit" name="update_unipress_api_settings" value="<?php _e( 'Save Settings', 'unipress-api' ) ?>" />
                        </p>

                        </div>
                        
                    </div>
                        
                    <div id="modules" class="postbox">
                    
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        
                        <h3 class="hndle"><span><?php _e( 'Custom Styling (CSS)', 'unipress-api' ); ?></span></h3>
                        
                        <div class="inside">

						<textarea id="unipress-custom-css" name="css"><?php echo esc_textarea( $settings['css'] ); ?></textarea> 
						<p class="description"><?php _e( 'Use this to add custom style sheets to your UniPress mobile app!', 'unipress-api' ); ?></p>                       
                                                                          
                        <p class="submit">
                            <input class="button-primary" type="submit" name="update_unipress_api_settings" value="<?php _e( 'Save Settings', 'unipress-api' ) ?>" />
                        </p>

                        </div>
                        
                    </div>
                        
                    <div id="modules" class="postbox">
                    
                        <div class="handlediv" title="Click to toggle"><br /></div>
                        
                        <h3 class="hndle"><span><?php _e( 'Custom JavaScript (JS)', 'unipress-api' ); ?></span></h3>
                        
                        <div class="inside">

						<textarea id="unipress-custom-js" name="js"><?php echo esc_textarea( $settings['js'] ); ?></textarea> 
						<p class="description"><?php _e( 'Use this to add custom JavaScript to your UniPress mobile app!', 'unipress-api' ); ?></p>                       
                                                                          
                        <p class="submit">
                            <input class="button-primary" type="submit" name="update_unipress_api_settings" value="<?php _e( 'Save Settings', 'unipress-api' ) ?>" />
                        </p>

                        </div>
                        
                    </div>
                           
                    <?php do_action( 'unipress_api_settings_form_end', $settings ); ?>
                    
                </form>
                
            </div>
            </div>
            </div>
			</div>
			<?php
			
		}
		
		function deeplinks() {
			$settings = $this->get_settings();

			if ( $settings['dl-enabled'] ) {
				
				global $post;
				
				if ( is_single() ) {
					$title = html_entity_decode( get_the_title(), ENT_COMPAT, get_bloginfo('charset') );
				} else {
					$title = html_entity_decode( get_bloginfo( 'name' ), ENT_COMPAT, get_bloginfo('charset') );
				}
				$permalink = get_permalink( $post->ID );
				
				/* Twitter all (for all apps) */
				if ( $settings['dl-ios-twitter-enabled'] || $settings['dl-android-twitter-enabled'] ) {
					echo '<meta name="twitter:card" content="app">' . "\n";
					echo '<meta name="twitter:image" content="' . $settings['dl-app-logo-url'] . '">' . "\n";
					echo '<meta name="twitter:title" content="' . $title . '">' . "\n";
				}
				
				/* Facebook all (for all apps) */
				if ( $settings['dl-ios-facebook-enabled'] || $settings['dl-android-facebook-enabled'] ) {
					echo '<meta property="al:web:url" content="' . $permalink . '" />' . "\n";
					echo '<meta property="al:web:should_fallback" content="' . ( $settings['dl-facebook-fallback'] ? 'true' : 'false' ) . '">' . "\n";
					echo '<meta property="og:type" content="website">' . "\n";
					echo '<meta property="og:title" content="' . $title . '">' . "\n";
				}
					
				/* iOS */
				if ( $settings['dl-ios-enabled'] ) {
					
					/* Twitter iOS */
					if ( $settings['dl-ios-twitter-enabled'] ) {
						echo '<meta name="twitter:app:name:iphone" content="' . $settings['dl-ios-app-name'] . '">' . "\n";
						echo '<meta name="twitter:app:id:iphone" content="' . $settings['dl-ios-app-id'] . '">' . "\n";
						echo '<meta name="twitter:app:url:iphone" content="' . $settings['dl-custom-schema'] . '://post/' . $post->ID .'">' . "\n";
						echo '<meta name="twitter:app:name:ipad" content="' . $settings['dl-ios-app-name'] . '">' . "\n";
						echo '<meta name="twitter:app:id:ipad" content="' . $settings['dl-ios-app-id'] . '">' . "\n";
						echo '<meta name="twitter:app:url:ipad" content="' . $settings['dl-custom-schema'] . '://post/' . $post->ID .'">' . "\n";
					}
					
					/* Facebook iOS */
					if ( $settings['dl-ios-facebook-enabled'] ) {
						echo '<meta property="al:ios:app_name" content="' . $settings['dl-ios-app-name'] . '" />' . "\n";
						echo '<meta property="al:ios:app_store_id" content="' . $settings['dl-ios-app-id'] . '" />' . "\n";
						echo '<meta property="al:ios:url" content="' . $settings['dl-custom-schema'] . '://post/' . $post->ID .'" />' . "\n";
					}
				}
				
				/* Android */
				if ( $settings['dl-android-enabled'] ) {
					
					/* Twitter Android */
					if ( $settings['dl-android-twitter-enabled'] ) {
						echo '<meta name="twitter:app:name:googleplay" content="' . $settings['dl-android-app-name'] . '" />' . "\n";
						echo '<meta name="twitter:app:id:googleplay" content="' . $settings['dl-android-app-id'] . '" />' . "\n";
						echo '<meta name="twitter:app:url:googleplay" content="' . $settings['dl-custom-schema'] . '://post/' . $post->ID .'" />' . "\n";
						echo '<meta name="twitter:description" content="' . $settings['dl-app-description'] . '">' . "\n";
					}
					
					/* Facebook Android */
					if ( $settings['dl-android-facebook-enabled'] ) {
						echo '<meta property="al:android:app_name" content="' . $settings['dl-android-app-name'] . '">' . "\n";
						echo '<meta property="al:android:package" content="' . $settings['dl-android-app-id'] . '">' . "\n";
						echo '<meta property="al:android:url" content="' . $settings['dl-custom-schema'] . '://post/' . $post->ID .'">' . "\n";
					}
				}
				
			}
		}
		
		function verify_secret_key( $app_id, $secret_key, $dev_mode=true ) {
			
			if ( !$dev_mode ) {
				$push_url = 'https://app.getunipress.com/paywall/1.1/%s/options?secretkey=%s'; //production
			} else {
				$push_url = 'http://toronto.briskmobile.com:8091/paywall/1.1/%s/options?secretkey=%s'; //development
			}
			$push_url = sprintf( $push_url, $app_id, $secret_key );

			$response = wp_remote_get( $push_url );
			
			if ( !empty( $response ) ) {
				if ( is_wp_error( $response ) ) {
					error_log( sprintf( __( 'UniPress Push Notification Error: %s', 'unipress-api' ), $response->get_error_message() ) );
				} else {
					$body = json_decode( wp_remote_retrieve_body( $response ) );
					if ( $body->success ) {
						return true;
					}
				}
			}
			
			return false;
				
		}
		
		function leaky_paywall_subscriber_info_paid_subscriber_end( $content ) {
			
			$settings = $this->get_settings();
			$current_user = wp_get_current_user();
			
			if ( 0 !== $current_user->ID ) {
				$devices = get_user_meta( $current_user->ID, 'unipress-devices' );
				$content .= '<div id="unipress-devices">';
				
				$content .= '<div id="unipress-device-list">';
				if ( !empty( $devices ) ) {
					foreach( $devices as $device ) {
						$content .= unipress_api_device_row( $device );
					}
				}
				$content .= '</div>';
				
				$content .= '<div id="unipress-device-options">';
				if ( count( $devices ) < $settings['device-limit'] ) {
					$content .= '<a class="button unipress-add-new-device" href="#">Add New Mobile Device</a>';
				} else {
					$content .= '<p>' . __( 'You have reached your device limit, you must remove a device before adding new ones', 'unipress-api' ) . '</p>';
				}
				$content .= '</div>';
				
				$content .= '</div>';
			}
			
			return $content;
			
		}
		
		function push_notification( $new_status, $old_status, $post ) {
			
			if ( 'publish' === $new_status && 'publish' !== $old_status ) {

				$settings = $this->get_settings();
				
				if ( empty( $settings['dev-mode'] ) ) {
					$push_url = 'https://app.getunipress.com/paywall/1.1/%s/push?secretkey=%s'; //production
				} else {
					$push_url = 'http://toronto.briskmobile.com:8091/paywall/1.1/%s/push?secretkey=%s'; //development
				}
				$push_url = sprintf( $push_url, $settings['app-id'], $settings['secret-key'] );

				if ( 'unipress-push' === $post->post_type ) {
					if ( !empty( $_POST['push-type'] ) && !empty( $_POST['push-content'] ) ) { //this happens before the save_post_unipress-push, so we need to pull from _POST
						$args = array(
							'headers'	=> array( 'content-type' => 'application/json' ),
							'body'		=> json_encode( array( 'device-type' => $_POST['push-type'], 'message' => $_POST['push-content'] ) ),
						);
						$response = wp_remote_post( $push_url, $args );
					}
				} else if ( !empty( $settings['silent-push'] ) && 'post' === $post->post_type ) { //assume it's the only type of content that we want to send a silent notification for...
					$args = array(
						'headers'	=> array( 'content-type' => 'application/json' ),
						'body'		=> json_encode( array( 'device-type' => $settings['push-device-type'], 'post_date' => $post->post_date_gmt ) ),
					);
					$response = wp_remote_post( $push_url, $args );
				}
				
				if ( !empty( $response ) && is_wp_error( $response ) ) {
					error_log( sprintf( __( 'UniPress Push Notification Error: %s', 'unipress-api' ), $response->get_error_message() ) );
				}

			}
			
		}
		
		function process_requests() {
			
			if ( !empty( $_REQUEST['unipress-api'] ) ) {

				switch ( $_REQUEST['unipress-api'] ) {
					
					case 'get-menu':
						$this->api_response( $this->get_menu() );
						break;
						
					case 'get-content-list':
						$this->api_response( $this->get_content_list() );
						break;
						
					case 'get-article':
						$this->api_response( $this->get_article() );
						break;
						
					case 'authorize-device':
						$this->api_response( $this->authorize_device() );
						break;
						
					case 'get-ad-data':
						$this->api_response( $this->get_ad_data() );
						break;
						
					case 'verify-device-id':
						$this->api_response( $this->verify_device_id() );
						break;
						
					case 'set-subscription':
						$this->api_response( $this->set_subscription() );
						break;
						
					case 'update-subscriber':
						$this->api_response( $this->update_subscriber() );
						break;
						
					case 'get-post-types':
						$this->api_response( $this->get_post_types() );
						break;
						
					case 'get-comments':
						$this->api_response( $this->get_comments() );
						break;
						
					case 'add-comment':
						$this->api_response( $this->add_comment() );
						break;
						
					case 'get-css':
						$this->api_response( $this->get_css() );
						break;
						
					case 'get-js':
						$this->api_response( $this->get_js() );
						break;
						
					default:
						$response = apply_filters( 'process-unipress-api-request-' . $_REQUEST['unipress-api'], false );
						if ( empty( $response ) ) {
							$response = array(
								'http_code' => 502,
								'body' 		=> __( 'Unrecognized Request Sent', 'unipress-api' ),
							);
						}
						$this->api_response( $response );
						break;
					
				}
				
			}
			
		}
		
		function api_response( $response ) {
				
	        header( 'HTTP/1.1 ' . $response['http_code'] . ' ' . $this->http_code_string( $response['http_code'] ) );
	        header( 'Content-type: application/json' );
	
	        // this should be templatized in a real-world solution
	        echo json_encode( $response['body'] );
			exit;
			
		}
		
		function http_code_string( $code ) {
			switch( $code ) {
				case '200':
					return __( 'Success', 'unipress-api' );
				case '201':
					return __( 'Created', 'unipress-api' );
				case '204':
					return __( 'No Content', 'unipress-api' );
				case '400':
					return __( 'Bad Request', 'unipress-api' );
				case '401':
					return __( 'Device not authorized', 'unipress-api' );
				case '402':
					return __( 'Subscription required', 'unipress-api' );
				case '409':
					return __( 'Conflict', 'unipress-api' );
				case '502':
					return __( 'Bad Gateway', 'unipress-api' );
				default:
					return __( 'Unknown', 'unipress-api' );
			}
		}
		
		function get_menu() {
			if ( ( $locations = get_nav_menu_locations() ) && isset( $locations['unipress-app-menu'] ) ) {
				$menu = wp_get_nav_menu_object( $locations['unipress-app-menu'] );
		        if ( $menu && ! is_wp_error( $menu ) ) {
					$menu_items = wp_get_nav_menu_items( $menu->term_id, array( 'update_post_term_cache' => false ) );
				} else {
					$menu_items = array();
				}
			} else {
				$menu_items = array();
			}
			
			$response = array(
				'http_code' => 200,
				'body' 		=> $menu_items,
			);
			return $response;
		}
		
		//parse data
		function get_content_list() {
			global $post;
			$settings = $this->get_settings();
			$check_categories = false;

			$args['posts_per_page'] = !empty( $_REQUEST['posts_per_page'] ) ? $_REQUEST['posts_per_page'] 						: 10;
			$args['offset'] 		= !empty( $_REQUEST['page'] ) 			? $_REQUEST['posts_per_page'] * $_REQUEST['page'] 	: 0;
			$args['orderby'] 		= !empty( $_REQUEST['orderby'] ) 		? $_REQUEST['orderby'] 								: 'post_date';
			$args['order'] 			= !empty( $_REQUEST['order'] ) 			? $_REQUEST['order'] 								: 'DESC';
			$args['post_type'] 		= !empty( $_REQUEST['post_type'] ) 		? $_REQUEST['post_type'] 							: array( 'post' );
			$args['s'] 		        = !empty( $_REQUEST['s'] ) 		        ? $_REQUEST['s'] 							        : false;
			$args['author']         = !empty( $_REQUEST['author'] )         ? $_REQUEST['author'] 						        : '';

			if ( !empty( $_REQUEST['taxonomy'] ) && !empty( $_REQUEST['term'] ) ) {
				if ( is_numeric( $_REQUEST['term'] ) ) {
					$field = 'term_id';
				} else {
					$field = 'slug';
				}
				$args['tax_query'] = array(
					array(
						'taxonomy' => $_REQUEST['taxonomy'],
						'field'    => $field,
						'terms'    => $_REQUEST['term'],
					),
				);
			} else if ( !empty( $_REQUEST['taxonomies'] ) && !empty( $_REQUEST['terms'] ) ) {
				if ( is_array( $_REQUEST['taxonomies'] ) && is_array( $_REQUEST['terms'] ) ) {
					if ( count( $_REQUEST['taxonomies'] ) === count( $_REQUEST['terms'] ) ) { //Make sure they are the same size
						$taxonomies = $_REQUEST['taxonomies'];
						$terms = $_REQUEST['terms'];
						$args['tax_query'] = array( 'relation' => 'AND' );
						foreach( $taxonomies as $key => $taxonomy ) {
							
							if ( is_numeric( $terms[$key] ) ) {
								$field = 'term_id';
							} else {
								$field = 'slug';
							}
							
							$args['tax_query'][] = array(
								'taxonomy' => $taxonomy,
								'field'    => $field,
								'terms'    => $terms[$key],
							);
						}
					}
				} 
			}
			
			$args = apply_filters( 'unipress_get_content_list_get_posts_args', $args );
			$posts = get_posts( $args );
			
			$upload_dir = wp_upload_dir();
			$baseurl = $upload_dir['baseurl'];
			if ( !empty( $settings['attachment-baseurl'] ) ) { //setting override WordPress default
				$baseurl = $settings['attachment-baseurl'];
			}
			
			if ( !empty( $settings['excluded-cats'] ) ) {
				$check_categories = true;
			}
			
			foreach( $posts as $key => &$post ) {
				setup_postdata( $post );
				
				if ( $check_categories ) {
					$match = false;
					$categories = wp_get_post_categories( $post->ID );
					
					foreach ( $categories as $cat ) {
						if ( in_array( $cat, $settings['excluded-cats'] ) ) {
							$match = true;
							break;
						}
					}
					
					if ( $match ) {
						unset( $posts[$key] );
						continue; //Skip this post
					}
				}
								
				$args = array(
					'post_type' 		=> 'attachment',
					'posts_per_page' 	=> -1,
					'post_parent' 		=> $post->ID,
					'exclude' 			=> get_post_thumbnail_id( $post->ID )
				);
				$attachment_posts = get_posts( $args );
				$attachments = array();
		
				if ( !empty( $attachment_posts ) ) {
					foreach ( $attachment_posts as $attachment ) {
						$metadata = wp_get_attachment_metadata( $attachment->ID );
						if ( !empty( $metadata ) ) {
							$attachments[] = $metadata;
						}
					}
				}
				
				$post->attachment_baseurl = apply_filters( 'unipress_attachment_baseurl', $baseurl );
				$post->attachments = $attachments;
				$post->featured_image = apply_filters( 'unipress_api_get_content_list_featured_image', wp_get_attachment_metadata( get_post_thumbnail_id( $post->ID ) ), $post->ID );

				$post->author_meta = new stdClass();
				$post->author_meta->user_login 		= get_the_author_meta( 'user_login', 		$post->post_author );
				$post->author_meta->user_nicename 	= get_the_author_meta( 'user_nicename', 	$post->post_author );
				$post->author_meta->display_name 	= get_the_author_meta( 'display_name', 		$post->post_author );
				$post->author_meta->nickname 		= get_the_author_meta( 'nickname', 			$post->post_author );
				$post->author_meta->first_name 		= get_the_author_meta( 'first_name', 		$post->post_author );
				$post->author_meta->last_name 		= get_the_author_meta( 'last_name', 		$post->post_author );
				$post->author_meta->nickname 		= get_the_author_meta( 'nickname', 			$post->post_author );
				$post->author_meta->user_firstname 	= get_the_author_meta( 'user_firstname', 	$post->post_author );
				$post->author_meta->user_lastname 	= get_the_author_meta( 'user_lastname', 	$post->post_author );
				$post->author_meta->description 	= get_the_author_meta( 'description', 		$post->post_author );
				
				$post->formatted_post_content = apply_filters( 'the_content', $post->post_content );
				
				if ( 'default' === $settings['excerpt-type'] ) {
					$excerpt = get_the_excerpt();
				} else {
					$excerpt = str_replace( ']]>', ']]&gt;', $post->formatted_post_content ); //From wp-includes/formatting.php
					$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
					$excerpt = wp_trim_words( $excerpt, $settings['excerpt-size'], $excerpt_more );
				}
				
				$post->post_excerpt = apply_filters( 'unipress_api_excerpt', $excerpt );
				
				//We don't need these
				unset( $post->post_content );
				unset( $post->post_content_filtered );
				
				$taxonomies = get_taxonomies();
				
				if ( !empty( $taxonomies ) ) {
					$post->taxonomies = new stdClass();
					
					foreach( $taxonomies as $taxonomy ) {
						
						$terms = wp_get_post_terms( $post->ID, $taxonomy );
						
						if ( !empty( $terms ) ) {
						
							foreach( $terms as &$term ) {
								$term->link = get_term_link( $term, $taxonomy );
							}
							
							$post->taxonomies->$taxonomy = $terms;
							
						}
					
					}
					
				}
				
				$post = apply_filters( 'unipress_api_post', $post );
				$post = apply_filters( 'unipress_api_get_content_list_post', $post );
					
			}
			
			$response = array(
				'http_code' => 200,
				'body' 		=> $posts,
			);
			return $response;
		}
		
		function get_article() {
			try {
				if ( empty( $_REQUEST['article-id'] ) ) {
					throw new Exception( __( 'Missing Article ID.', 'unipress-api' ), 400 );
				} else if ( !is_numeric( $_REQUEST['article-id'] ) ) {
					throw new Exception( __( 'Invalid Article Format.', 'unipress-api' ), 400 );
				}
				
				if ( empty( $_REQUEST['device-id'] ) ) {
					throw new Exception( __( 'Missing Device ID.', 'unipress-api' ), 400 );
				} else {
					$device_id = $_REQUEST['device-id'];
					if ( $this->leaky_paywall_enabled ) {
						$restrictions = unipress_api_get_user_restrictions_by_device_id( $device_id );
					} else {
						$restrictions = array();
					}
				}
				
				global $post;
				$settings = $this->get_settings();
				$is_restricted = false;
				$upload_dir = wp_upload_dir();
				$baseurl = $upload_dir['baseurl'];
				if ( !empty( $settings['attachment-baseurl'] ) ) { //setting override WordPress default
					$baseurl = $settings['attachment-baseurl'];
				}
				$post = get_post( $_REQUEST['article-id'] );
				setup_postdata( $post ); 

				$response['http_code'] = 200;
				
				$post->unipress_restrictions = $restrictions;
				$post->unipress_article_restriction = false;
				$post->unipress_article_count = 0;
				$post->unipress_article_limit = false;
				$post->unipress_article_remaining = false;
				
				if ( !empty( $restrictions['post_types'] ) ) {
					
					foreach( $restrictions['post_types'] as $key => $restriction ) {
						
						if ( $post->post_type == $restriction['post_type'] ) {
						
							if ( 0 <= $restriction['allowed_value'] ) {
						
								$post_type_id = $key;
								$restricted_post_type = $restriction['post_type'];
								$is_restricted = true;
								$post->unipress_article_restriction = $restriction;
								$post->unipress_article_count = 0;
								$post->unipress_article_limit = $restriction['allowed_value'];
								
								if ( !empty( $available_content[$restricted_post_type] ) ) {
									$post->unipress_article_count = count( $available_content[$restricted_post_type] );
									$post->unipress_article_remaining = $restriction['allowed_value'] - count( $available_content[$restricted_post_type] );
								} else {
									$post->unipress_article_remaining = $restriction['allowed_value'];
								}
							
								break;
								
							}
							
						}
						
					}

				}

				if ( !empty( $post ) ) {
					$args = array(
						'post_type' 		=> 'attachment',
						'posts_per_page' 	=> -1,
						'post_parent' 		=> $post->ID,
						'exclude' 			=> get_post_thumbnail_id( $post->ID )
					);
					$attachment_posts = get_posts( $args );
					$attachments = array();
			
					if ( !empty( $attachment_posts ) ) {
						foreach ( $attachment_posts as $attachment ) {
							$metadata = wp_get_attachment_metadata( $attachment->ID );
							if ( !empty( $metadata ) ) {
								$attachments[] = $metadata;
							}
						}
					}
					
					$post->guid = html_entity_decode( $post->guid );
					
					$post->attachment_baseurl = apply_filters( 'unipress_api_attachment_baseurl', $baseurl );
					$post->attachments = $attachments;
					$post->featured_image = apply_filters( 'unipress_api_get_article_featured_image', wp_get_attachment_metadata( get_post_thumbnail_id( $post->ID ) ), $post->ID );
					
					$post->author_meta = new stdClass();
					$post->author_meta->user_login 		= get_the_author_meta( 'user_login', 		$post->post_author );
					$post->author_meta->user_nicename 	= get_the_author_meta( 'user_nicename', 	$post->post_author );
					$post->author_meta->display_name 	= get_the_author_meta( 'display_name', 		$post->post_author );
					$post->author_meta->nickname 		= get_the_author_meta( 'nickname', 			$post->post_author );
					$post->author_meta->first_name 		= get_the_author_meta( 'first_name', 		$post->post_author );
					$post->author_meta->last_name 		= get_the_author_meta( 'last_name', 		$post->post_author );
					$post->author_meta->nickname 		= get_the_author_meta( 'nickname', 			$post->post_author );
					$post->author_meta->user_firstname 	= get_the_author_meta( 'user_firstname', 	$post->post_author );
					$post->author_meta->user_lastname 	= get_the_author_meta( 'user_lastname', 	$post->post_author );
					$post->author_meta->description 	= get_the_author_meta( 'description', 		$post->post_author );
					
					$post->formatted_post_content = apply_filters( 'the_content', $post->post_content );
					
					if ( 'default' === $settings['excerpt-type'] ) {
						$excerpt = get_the_excerpt();
					} else {
						$excerpt = str_replace( ']]>', ']]&gt;', $post->formatted_post_content ); //From wp-includes/formatting.php
						$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
						$excerpt = wp_trim_words( $excerpt, $settings['excerpt-size'], $excerpt_more );
					}
					
					$post->post_excerpt = apply_filters( 'unipress_api_excerpt', $excerpt );
						
					//We don't need these
					unset( $post->post_content );
					unset( $post->post_content_filtered );
					
					$taxonomies = get_taxonomies();
					
					if ( !empty( $taxonomies ) ) {
						$post->taxonomies = new stdClass();
						
						foreach( $taxonomies as $taxonomy ) {
							
							$terms = wp_get_post_terms( $post->ID, $taxonomy );
							
							if ( !empty( $terms ) ) {
							
								foreach( $terms as &$term ) {
									$term->link = get_term_link( $term, $taxonomy );
								}
								
								$post->taxonomies->$taxonomy = $terms;
								
							}
						
						}
						
					}
					
					$post = apply_filters( 'unipress_api_post', $post );
					$post = apply_filters( 'unipress_api_get_article_post', $post );
					
				}
				
				if ( $this->leaky_paywall_enabled ) {
					
					$visibility = get_post_meta( $post->ID, '_issuem_leaky_paywall_visibility', true );
									
					if ( false !== $visibility && !empty( $visibility['visibility_type'] ) && 'default' !== $visibility['visibility_type'] ) {
						
					    $level_id = unipress_api_get_user_level_id_by_device_id( $device_id );
												
						switch( $visibility['visibility_type'] ) {
							
							case 'only':
								if ( !in_array( $level_id, $visibility['only_visible'], true ) ) {
									$is_restricted = true;
									$post->visible = false;
								}
								break;
								
							case 'always':
								if ( in_array( -1, $visibility['always_visible'] ) || in_array( $level_id, $visibility['always_visible'] ) ) { //-1 = Everyone
									$is_restricted = false;
									$post->visible = true;
								}
								break;
							
							case 'onlyalways':
								if ( !in_array( $level_id, $visibility['only_always_visible'] ) ) {
									$is_restricted = true;
									$post->visible = false;
								} else {
									$is_restricted = false;
									$post->visible = true;
								}
								break;
							
						}
						
						if ( $is_restricted ) {
							$response = array(
								'http_code' => 403,
								'body' 		=> $post,
							);
							return $response;
						}
						
					}
					
					$is_restricted = apply_filters( 'unipress_filter_is_restricted', $is_restricted, $restrictions, $post );
	
					if ( $is_restricted ) {
						
						$lp_settings = get_leaky_paywall_settings();
						switch ( $lp_settings['cookie_expiration_interval'] ) {
							case 'hour':
								$multiplier = 60 * 60; //seconds in an hour
								break;
							case 'day':
								$multiplier = 60 * 60 * 24; //seconds in a day
								break;
							case 'week':
								$multiplier = 60 * 60 * 24 * 7; //seconds in a week
								break;
							case 'month':
								$multiplier = 60 * 60 * 24 * 7 * 4; //seconds in a month (4 weeks)
								break;
							case 'year':
								$multiplier = 60 * 60 * 24 * 7 * 52; //seconds in a year (52 weeks)
								break;
						}
						$expiration = time() + ( $lp_settings['cookie_expiration'] * $multiplier );
						
						$cookie = get_option( 'unipress_cookie_' . $device_id, array() );
						if ( !empty( $cookie ) )
							$available_content = maybe_unserialize( $cookie );
						
						if ( empty( $available_content[$restricted_post_type] ) )
							$available_content[$restricted_post_type] = array();							
					
						foreach ( $available_content[$restricted_post_type] as $key => $restriction ) {
							
							if ( time() > $restriction || 7200 > $restriction ) { 
								//this post view has expired
								//Or it is very old and based on the post ID rather than the expiration time
								unset( $available_content[$restricted_post_type][$key] );
								
							}
							
						}
													
						if( -1 != $restrictions['post_types'][$post_type_id]['allowed_value'] ) { //-1 means unlimited
							
							$post->unipress_article_limit = $restrictions['post_types'][$post_type_id]['allowed_value'];
	
							if ( $restrictions['post_types'][$post_type_id]['allowed_value'] > count( $available_content[$restricted_post_type] ) ) { 
							
								if ( !array_key_exists( $post->ID, $available_content[$restricted_post_type] ) ) {
									
									$available_content[$restricted_post_type][$post->ID] = $expiration;
								
								}
								
							} else {
							
								if ( !array_key_exists( $post->ID, $available_content[$restricted_post_type] ) ) {
	
									$response['http_code'] = 401;
														
								}
								
							}
							
							$post->unipress_article_count = count( $available_content[$restricted_post_type] );
						
						}
						
						$serialized_available_content = maybe_serialize( $available_content );
						update_option( 'unipress_cookie_' . $device_id, $serialized_available_content );
						
					}
					
				} else {
					$visibility = false;
					$post->visible = true;
				}
				
				$response['body'] = $post;
				
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}
		
		function authorize_device() {
			try {
				$settings = $this->get_settings();

				$input = file_get_contents('php://input');
				$post = json_decode( $input, TRUE ); 
			
				if ( empty( $post['device-id'] ) ) {
					throw new Exception( __( 'Missing Device ID.', 'unipress-api' ), 400 );
				}
				
				if ( empty( $post['user-token'] ) ) {
					throw new Exception( __( 'Missing User Token.', 'unipress-api' ), 400 );
				} else {
					$token = strtolower( trim( $post['user-token'] ) );
				}
				
				if ( false === ( $user_id = get_option( 'unipress_api_token_' . $token, false ) ) 
					|| false === ( $expires = get_option( 'unipress_api_token_expires_' . $token, false ) ) ) {
					throw new Exception( __( 'Unable to verify token. Please generate a new one and try again.', 'unipress-api' ), 400 );
				}
				
				$now = current_time( 'timestamp' );
				if ( $now > intval( $expires ) ) {
					delete_option( 'unipress_api_token_expires_' . $token );
					delete_option( 'unipress_api_token_' . $token );
					throw new Exception( __( 'User Token has Expired. Please generate a new one.', 'unipress-api' ), 400 );
				}
				
				$devices = get_user_meta( $user_id, 'unipress-devices' );
				
				if ( !empty( $devices ) && in_array( $post['device-id'], $devices ) ) {
					$response = array(
						'http_code' => 201,
						'body' 		=> __( "Device already exists on user's device list", 'unipress-api' ),
					);
				} else if ( count( $devices ) >= $settings['device-limit'] ) {
					$response = array(
						'http_code' => 401,
						'body' 		=> __( "User has reached device limit. Please remove devices before attempting to add new devices.", 'unipress-api' ),
					);
				} else {
					add_user_meta( $user_id, 'unipress-devices', $post['device-id'] );
					$response = array(
						'http_code' => 200,
						'body' 		=> __( "Device added to user's device list", 'unipress-api' ),
					);
				}
				
				delete_option( 'unipress_api_token_expires_' . $token );
				delete_option( 'unipress_api_token_' . $token );
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}
		
		function get_ad_data() {
			try {
				if ( empty( $_REQUEST['device-type'] ) ) {
					throw new Exception( __( 'Missing Device Type.', 'unipress-api' ), 400 );
				} else {
					$device_type = strtolower( $_REQUEST['device-type'] );
					if ( !in_array( $device_type, array( 'phone', 'tablet', 'tablet-portrait', 'tablet-landscape', 'smartphone', 'wide-screen' ) ) ) {
						throw new Exception( __( 'Invalid Device Type. Must be tablet-portrait, tablet-landscape, smartphone, wide-screen.', 'unipress-api' ), 400 );						
					}
				}
				
				$args = array(
					'post_type' 	=> 'unipress-ad',
					'meta_query' 	=> array(
						array(
							'key' 	=> '_ad_type',
							'value' => $_REQUEST['device-type'],
						),
					),
				);
				
				$ads = get_posts( $args );
				$body = array();
				
				if ( !empty( $ads ) ) {
					foreach ( $ads as $key => $ad ) {
						$ad_link = get_post_meta( $ad->ID, '_ad_link', true );
						if ( !empty( $ad_link ) ) {
							$body[$key]['ad_link' ] = $ad_link;							
						}
						
						$img_id = get_post_thumbnail_id( $ad->ID );
						$ad_img = wp_get_attachment_image_src( $img_id, 'unipress-' . $_REQUEST['device-type'] ); ;
						if ( !empty( $ad_img ) ) {
							$body[$key]['ad_img'] = $ad_img[0];	
						}
					}
				}
				
				$response = array(
					'http_code' => 200,
					'body' 		=> $body,
				);
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}
		
		function verify_device_id() {
			try {
				if ( empty( $_REQUEST['device-id'] ) ) {
					throw new Exception( __( 'Missing Device ID.', 'unipress-api' ), 400 );
				} else {
					$user = unipress_api_get_user_by_device_id( $_REQUEST['device-id'] );
					if ( empty( $user ) ) {
						$response = array(
							'http_code' => 200,
							'body' 		=> __( 'No user found.','unipress-api' ),
						);
						return $response;
					} else {
						throw new Exception( __( 'User already exists.', 'unipress-api' ), 409 );
					}
				}
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}
		
		function set_subscription() {
			try {
				$input = file_get_contents('php://input');
				$post = json_decode( $input, TRUE );
				
				if ( empty( $post['device-id'] ) ) {
					throw new Exception( __( 'Missing Device ID.', 'unipress-api' ), 400 );
				}
				
				if ( empty( $post['device-type'] ) ) {
					throw new Exception( __( 'Missing Device Type.', 'unipress-api' ), 400 );
				} else {
					if ( !( 'ios' === strtolower( $post['device-type'] ) || 'android' === strtolower( $post['device-type'] ) ) ) {
						throw new Exception( __( 'Invalid Device Type. Must be iOS or Android.', 'unipress-api' ), 400 );						
					}
				}
				
				if ( empty( $post['receipt'] ) ) {
					throw new Exception( __( 'Missing Receipt Data.', 'unipress-api' ), 400 );
				} else {
					//verify receipt data with Brisk server
					if ( 'fail' === $post['receipt'] ) {
						throw new Exception( __( 'Invalid Receipt Data.', 'unipress-api' ), 400 );						
					} else {
						$receipt = $post['receipt'];
					}
				}
				
				if ( empty( $receipt['package'] ) ) {
					throw new Exception( __( 'Missing Package Data.', 'unipress-api' ), 400 );
				} else {
					$level_id = unipress_get_leaky_paywall_subscription_level_level_id( $receipt['package'] );
					if ( false === $level = get_leaky_paywall_subscription_level( $level_id ) ) {
						throw new Exception( __( 'Unable to find valid package.', 'unipress-api' ), 400 );
					}
				}
								
				if ( $user = unipress_api_get_user_by_device_id( $post['device-id'] ) ) {
					$existing_customer = true;
					$login = $user->user_login;
					$email = $user->user_email;
				} else {
					$existing_customer = false;
					//Create Guest User
					do {
						$username = 'unipress_' . uniqid();
						$user = get_user_by( 'login', $username ); 
					} while( false !== $user );
					
					$url = parse_url( get_bloginfo( 'wpurl' ) );
					
					//We need to generate a fake user for iOS subscribers
					$login = $username;
					$email = $username . '@' . $url['host'];
				}
				
				$customer_id = uniqid(); //need to get transaction ID
				
				$meta = array(
					'level_id'			=> $level_id,
					'subscriber_id' 	=> $customer_id, //need to get transaction ID
					'subscriber_email' 	=> $email,
					'price' 			=> '0.00', //need to get receipt price
					'description' 		=> sprintf( __( '%s Subscription', 'unipress-api' ), $post['device-type'] ),
					'payment_gateway' 	=> $post['device-type'],
					'payment_status' 	=> 'active',
					'expires' 			=> date_i18n( 'Y-m-d H:i:s', strtotime( '+1 month' ) ), //need to get receipt expiry
					'interval' 			=> $level['interval'],
					'interval_count' 	=> $level['interval_count'],
					'plan' 				=> $level['interval_count'] . ' ' . strtoupper( substr( $level['interval'], 0, 1 ) ),
				);
				
				$unique_hash = leaky_paywall_hash( $email );
										
				if ( $existing_customer ) {
					$user_id = leaky_paywall_update_subscriber( $unique_hash, $email, $customer_id, $meta );
					if ( !empty( $user_id ) ) {
						$response = array(
							'http_code' => 201,
							'body' 		=> __( 'Subscription Update', 'unipress-api' ),
						);
					}
				} else {
					$meta['created'] = date_i18n( 'Y-m-d H:i:s' );
					$user_id = leaky_paywall_new_subscriber( $unique_hash, $email, $customer_id, $meta );
					if ( !empty( $user_id ) ) {
						$response = array(
							'http_code' => 200,
							'body' 		=> __( 'Subscription Created', 'unipress-api' ),
						);
					}
				}
				
				$devices = get_user_meta( $user_id, 'unipress-devices' );
				
				if ( !empty( $user_id ) ) {
					if ( empty( $devices ) || !in_array( $post['device-id'], $devices ) ) {
						add_user_meta( $user_id, 'unipress-devices', $post['device-id'] );
					}
				} else {
					$response = array(
						'http_code' => 417,
						'body' 		=> __( 'Unable to create subscriber.', 'unipress-api' ),
					);
				}
				
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}	
		
		function update_subscriber() {
			try {
				$input = file_get_contents('php://input');
				$post = json_decode( $input, TRUE ); 
				
				if ( empty( $post['device-id'] ) ) {
					throw new Exception( __( 'Missing Device ID.', 'unipress-api' ), 400 );
				}
				
				if ( empty( $post['device-type'] ) ) {
					throw new Exception( __( 'Missing Device Type.', 'unipress-api' ), 400 );
				} else {
					if ( !( 'ios' === strtolower( $post['device-type'] ) || 'android' === strtolower( $post['device-type'] ) ) ) {
						throw new Exception( __( 'Invalid Device Type. Must be iOS or Android.', 'unipress-api' ), 400 );						
					}
				}
				
				if ( empty( $post['email'] ) ) {
					throw new Exception( __( 'Missing Email Address.', 'unipress-api' ), 400 );
				} else {
					$email = trim( $post['email'] );
				}
				if ( !is_email( $email ) ) {
					throw new Exception( __( 'Invalid Email Address.', 'unipress-api' ), 400 );
				}
				
				if ( empty( $post['username'] ) ) {
					throw new Exception( __( 'Missing Username.', 'unipress-api' ), 400 );
				} else {
					$username = trim( $post['username'] );
				}
				
				if ( !empty( $post['displayname'] ) ) {
					$displayname = trim( $post['displayname'] );
				} else {
					$displayname = false;
				}
				
				if ( empty( $post['password'] ) ) {
					throw new Exception( __( 'Missing Password.', 'unipress-api' ), 400 );
				} else {
					$password = $post['password']; //don't trim, incase they add a space at the end of their password on purpose...
				}
				
				$user = unipress_api_get_user_by_device_id( $post['device-id'] );
				
				if ( !empty( $user ) ) {
					$response = array(
						'http_code' => '201',
						'body' 		=> '', //unknown, yet
					);
				
					//This device ID already has a user
					if ( $user->user_email !== $email ) {
						$existing_user = get_user_by( 'email', $email );
						if ( empty( $existing_user ) ) {
							$userdata = array(
								'ID' 			=> $user->ID,
								'user_email' 	=> $email,
							);
							$user_id = wp_update_user( $userdata );
							if ( !empty( $user_id ) && !is_wp_error( $user_id ) ) {
								$response['body'][] = 'Subscriber Email Updated.';
							} else if ( is_wp_error( $user_id ) ) {
								throw new Exception( sprintf( __( 'Unable to update the subscriber email address: %s', 'unipress-api' ), $user_id->get_error_message() ), 400 );
							} else {
								throw new Exception( __( 'Unable to update the subscriber email address: Reason Unknown.', 'unipress-api' ), 400 );
							}
						} else {
							throw new Exception( __( 'Email address already exists.', 'unipress-api' ), 409 );
						}
					}
					
					if ( $user->user_login !== $username ) { //don't need to update if we're the same username :)
						$existing_user = get_user_by( 'login', $username );
						if ( empty( $existing_user ) ) {
							global $wpdb;							
							$sql = "UPDATE {$wpdb->users} SET user_login = %s WHERE ID = %d";
							$sql = $wpdb->prepare( $sql, $username, $user->ID );
							$result = $wpdb->query( $sql );
							if ( false === $result ) {
								throw new Exception( __( 'Unable to update the subscriber username: Reason Unknown.', 'unipress-api' ), 400 );
							} else {
								$response['body'][] = 'Subscriber Username Updated.';
							}
						} else {
							throw new Exception( __( 'Username already exists.', 'unipress-api' ), 409 );
						}
					}
					
					if ( !empty( $displayname ) ) {
						$userdata = array(
							'ID' 			=> $user->ID,
							'display_name' 	=> $displayname,
						);
						$user_id = wp_update_user( $userdata );
						if ( !empty( $user_id ) && !is_wp_error( $user_id ) ) {
							$response['body'][] = 'Subscriber Display Name Updated.';
						} else if ( is_wp_error( $user_id ) ) {
							throw new Exception( sprintf( __( 'Unable to update the subscriber display name: %s', 'unipress-api' ), $user_id->get_error_message() ), 400 );
						} else {
							throw new Exception( __( 'Unable to update the subscriber display name: Reason Unknown.', 'unipress-api' ), 400 );
						}
					}
					
					wp_set_password( $password, $user->ID );
					$response['body'][] = 'Subscriber Password Updated.';
				} else {
					throw new Exception( __( 'Unable to find valid subscription for this user.', 'unipress-api' ), 400 );
				}
								
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}
		
		function get_post_types() {
			$post_types = get_post_types();
			unset( $post_types['attachment'] );
			unset( $post_types['revision'] );
			unset( $post_types['nav_menu_item'] );
			$response = array(
				'http_code' => 200,
				'body' 		=> array_values( $post_types ),
			);
			return $response;
		}
		
		function get_comments() {
			try {
				if ( empty( $_REQUEST['article-id'] ) ) {
					throw new Exception( __( 'Missing Article ID.', 'unipress-api' ), 400 );
				} else if ( !is_numeric( $_REQUEST['article-id'] ) ) {
					throw new Exception( __( 'Invalid Article Format.', 'unipress-api' ), 400 );
				} else {
					$args['post_id'] = $_REQUEST['article-id'];
				}
				
				$args['number'] 	= !empty( $_REQUEST['number'] ) 	? $_REQUEST['number'] 	: 10;
				$args['offset'] 	= !empty( $_REQUEST['offset'] ) 	? $_REQUEST['offset'] 	: 0;
				$args['orderby'] 	= !empty( $_REQUEST['orderby'] ) 	? $_REQUEST['orderby'] 	: 'comment_date_gmt';
				$args['order'] 		= !empty( $_REQUEST['order'] ) 		? $_REQUEST['order'] 	: 'DESC';
				$args['status']		= !empty( $_REQUEST['status'] ) 	? $_REQUEST['status'] 	: 'approve';
				
				$comments = get_comments( $args );
				
				if ( !empty( $comments ) ) {
					
					foreach( $comments as &$comment ) {
						
						$hash = md5( strtolower( trim( $comment->comment_author_email ) ) );
						$comment->gravatar_url = 'http://www.gravatar.com/avatar/' . $hash;
						
					}
					
				}
					
				$response = array(
					'http_code' => 200,
					'body' 		=> $comments,
				);
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}	
		
		function add_comment() {
			try {
				$input = file_get_contents('php://input');
				$post = json_decode( $input, TRUE ); 

				if ( empty( $post['article-id'] ) ) {
					throw new Exception( __( 'Missing Article ID.', 'unipress-api' ), 400 );
				} else if ( !is_numeric( $post['article-id'] ) ) {
					throw new Exception( __( 'Invalid Article Format.', 'unipress-api' ), 400 );
				}

				if ( empty( $post['device-id'] ) ) {
					throw new Exception( __( 'Missing Device ID.', 'unipress-api' ), 400 );
				}
				
				if ( empty( $post['device-type'] ) ) {
					throw new Exception( __( 'Missing Device Type.', 'unipress-api' ), 400 );
				} else {
					if ( !( 'ios' === strtolower( $post['device-type'] ) || 'android' === strtolower( $post['device-type'] ) ) ) {
						throw new Exception( __( 'Invalid Device Type. Must be iOS or Android.', 'unipress-api' ), 400 );						
					}
				}
								
				$user = unipress_api_get_user_by_device_id( $post['device-id'] );
				
				if ( empty( $user ) ) {
					throw new Exception( __( 'Unable to locate user for this device.', 'unipress-api' ), 400 );
				}
				
				if ( empty( $post['comment'] ) ) {
					throw new Exception( __( 'Empty Comment.', 'unipress-api' ), 400 );
				}
												
				$args = array(
					'comment_post_ID' 		=> $post['article-id'],
					'comment_author' 		=> $user->user_login,
					'comment_author_email' 	=> $user->user_email,
					'comment_content' 		=> $post['comment'],
					'comment_type' 			=> '',
					'comment_parent' 		=> !empty( $post['parent-comment-id'] ) ? $post['parent-comment-id'] : 0,
					'user_id' 				=> $user->ID,
					'comment_author_IP' 	=> unipress_api_get_ip_address(),
					'comment_agent' 		=> $post['device-type'],
					'comment_date' 			=> current_time('mysql'),
					'comment_approved' 		=> 1,
				);
				
				$comment_id = wp_insert_comment( $args );
										
				if ( !empty( $comment_id ) ) {
					$response = array(
						'http_code' => 201,
						'body' 		=> __( 'Comment added.', 'unipress-api' ),
					);
				} else {
					$response = array(
						'http_code' => 417,
						'body' 		=> __( 'Unable to add comment.', 'unipress-api' ),
					);
				}
				
				return $response;
			}
			catch ( Exception $e ) {
				$response = array(
					'http_code' => $e->getCode(),
					'body' 		=> $e->getMessage(),
				);
				return $response;
			}
		}
		
		function get_css() {
			$settings = $this->get_settings();
			$response = array(
				'http_code' => 200,
				'body' 		=> $settings['css'],
			);
			return $response;
		}
		
		function get_js() {
			$settings = $this->get_settings();
			$response = array(
				'http_code' => 200,
				'body' 		=> $settings['js'],
			);
			return $response;
		}		

	}

}

<?php

if(!function_exists('llog')) {

    function llog($str){
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }
}

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed


// the name of your product. This should match the download name in EDD exactly
// define( 'WPSPPRO_ITEM_NAME', 'wpsppro' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

// the name of the settings page for the license input to be displayed
define( 'WPRTSP_LICENSE_PAGE', 'wprtsp-settings' );

add_action( 'admin_init', 'wprtsp_activate_license' );
add_action( 'admin_init', 'wprtsp_deactivate_license' );
add_action( 'admin_notices', 'wprtsp_admin_notices' );
add_action( 'admin_init', 'wprtsp_plugin_updater', 0 );

//add_filter('site_transient_update_plugins', 'test_wprtsp_update' );

function test_wprtsp_update($str){
    llog($str);
    die();
    return $str;
}


function wprtsp_plugin_updater() {

	// retrieve our license key from the DB
    $license_key =  get_option( 'wprtsp' ) ;
    if($license_key && !empty($license_key['license_key'])) {
        $license_key = trim( $license_key['license_key'] );
    }
    else {
        $license_key = false;
    }

    // setup the updater
    
    $wprtsp = WPRTSP::get_instance();
    
    if(!is_admin()) return;
    
    $plugindata = get_plugin_data( $wprtsp->dir . 'wprtsp.php', 0, 0);
    
	$updater = new WPRTSP_Plugin_Updater( WPRTSP_EDD_SL_URL, WPRTSPFILE, array(
			'version' 	=> $plugindata['Version'], 			// current version number
			'license' 	=> $license_key, 			// license key (used get_option above to retrieve from DB)
			'item_name' => $plugindata['Name'],	// name of this plugin
			'author' 	=> $plugindata['Author'],  	// author of this plugin
			'beta'		=> false
		)
    );
    
}


function wprtsp_sanitize_license( $new ) {
	$old = get_option( 'wprtsp_license_key' );
	if( $old && $old != $new ) {
		delete_option( 'wprtsp_license_status' ); // new license has been entered, so must reactivate
	}
	return $new;
}

/************************************
* this illustrates how to activate
* a license key
*************************************/

function wprtsp_activate_license() {
    
    // listen for our activate button to be clicked

	if( isset( $_POST['wprtsp'] ) && isset($_POST['wprtsp']['license_key']) && isset($_POST['wprtsp']['license_activate'])  ) {

        //$license = get_option('wpsppro_license_key');
		// run a quick security check
	 	//if( ! check_admin_referer( 'wpsppro_license_activate_nonce', 'wpsppro_license_activate_nonce' ) ) {
        //    return; // get out if we didn't click the Activate button
        //}

        // retrieve the license from the database
        $option = get_option('wprtsp');
        if(! isset($option['license_key']) ){
            return;
        }
        //$license = trim( $option['license_key'] );
        $license = $_POST['wprtsp']['license_key'];

        if( ! $license ) {
            return;
        }
        $wprtsp = WPRTSP::get_instance();

		//$license = trim( $_POST['wprtsp']['wpsppro_license_key'] );

        $plugindata = get_plugin_data( $wprtsp->dir . 'wprtsp.php', 0, 0);

		// data to send in our API request
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
            //'item_name'  => urlencode( WPSPPRO_ITEM_NAME ), // the name of our product in EDD
            'item_name' => $plugindata['Name'],	// name of this plugin
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPRTSP_EDD_SL_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'wprtsp' );
			}

		} else {

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( false === $license_data->success ) {

				switch( $license_data->error ) {

					case 'expired' :

						$message = sprintf(
							__( 'Your license key expired on %s.', 'wprtsp' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
						);
						break;

					case 'revoked' :

						$message = __( 'Your license key has been disabled.', 'wprtsp' );
						break;

					case 'missing' :

						$message = __( 'Invalid license.', 'wprtsp' );
						break;

					case 'invalid' :
					case 'site_inactive' :

						$message = __( 'Your license is not active for this URL.', 'wprtsp' );
						break;

					case 'item_name_mismatch' :

						$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'wprtsp' ), get_plugin_data( WPRTSPFILE, 0, 0)['Name'] );
						break;

					case 'no_activations_left':

						$message = __( 'Your license key has reached its activation limit.', 'wprtsp' );
						break;

					default :

						$message = __( 'An error occurred, please try again.', 'wprtsp' );
						break;
                }
               
                if(!empty($_REQUEST['wprtsp']) && !empty($_REQUEST['wprtsp']['license_key'])) {
                    $message = 'Tried to activate key: <code>' . $_REQUEST['wprtsp']['license_key'] .'</code> <strong>Please correct the license key again and retry</strong>.';
                }
                else {
                    $message .= ' <strong>Please correct the license key again and retry</strong>.';
                }

            }
            else {
                update_option('wprtsp', array('license_key' => $_REQUEST['wprtsp']['license_key']));
                $message = __( 'Activation Successful. Thankyou for activating!', 'wprtsp' );
            }

		}

		// $license_data->license will be either "valid" or "invalid"
        //update_option('wpsppro');
		update_option( 'wprtsp_license_status', $license_data->license );
		wp_redirect( add_query_arg( array( 'wprtsp_activation' => $license_data->success ? $license_data->success : 'false', 'wprtsp_activation_message' => urlencode( $message ) ), get_admin_url( null, 'edit.php?post_type=socialproof&page=wprtsp') ) );
		exit();
	}
}


/***********************************************
* Illustrates how to deactivate a license key.
* This will decrease the site count
***********************************************/

function wprtsp_deactivate_license() {

    // listen for our activate button to be clicked
    
	if( isset( $_POST['wprtsp'] ) && isset($_POST['wprtsp']['license_key']) && isset($_POST['wprtsp']['license_deactivate']) ) {

		// run a quick security check

        // retrieve the license from the database
        $option = get_option('wprtsp');
        
        if(! isset($option['license_key'])) {
            return;
        }
        
        $license = trim( $option['license_key'] );
        $wprtsp = WPRTSP::get_instance();
        $plugindata = get_plugin_data( WPRTSPFILE, 0, 0);
		// data to send in our API request
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
            //'item_name'  => urlencode( WPSPPRO_ITEM_NAME ), // the name of our product in EDD
            'item_name' => $plugindata['Name'],	// name of this plugin
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( WPRTSP_EDD_SL_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'wprtsp' );
			}

            $redirect = add_query_arg( array( 'wprtsp_deactivation' => 'false', 'wprtsp_activation_message' => urlencode( $message ) ), get_admin_url(null, 'edit.php?post_type=socialproof&page=wprtsp') );

			wp_redirect( $redirect );
			exit();
        }
        
        $license_data = json_decode( wp_remote_retrieve_body( $response ) ); // decode the license data

		// $license_data->license will be either "deactivated" or "failed"
		if( $license_data->license == 'deactivated' ) {
			delete_option( 'wprtsp' );
			delete_option( 'wprtsp_license_status' );
		}

		wp_redirect( add_query_arg( array( 'wprtsp_deactivation' => $license_data->success ? "true" : "false" , 'wprtsp_deactivation_message' => urlencode( $license_data->license ) ), get_admin_url( null, 'edit.php?post_type=socialproof&page=wprtsp') ) );
		exit();

	}
}


/************************************
* this illustrates how to check if
* a license key is still valid
* the updater does this for you,
* so this is only needed if you
* want to do something custom
*************************************/

function wprtsp_check_license() {

	global $wp_version;

	$license = trim( get_option( 'wprtsp_license_key' ) );

	$api_params = array(
		'edd_action' => 'check_license',
		'license' => $license,
		'item_name' => urlencode( WPRTSP_ITEM_NAME ),
		'url'       => home_url()
	);

	// Call the custom API.
	$response = wp_remote_post( WPRTSP_EDD_SL_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

	if ( is_wp_error( $response ) )
		return false;

	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if( $license_data->license == 'valid' ) {
		//echo 'valid'; exit;
		return 'valid';
		// this license is still valid
	} else {
		//echo 'invalid'; exit;
		return 'invalid';
		// this license is no longer valid
	}
}

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer
 */
function wprtsp_admin_notices() {
    
	if ( isset( $_REQUEST['wprtsp_activation'] ) && ! empty( $_REQUEST['wprtsp_activation_message'] ) ) {
        $message = urldecode( $_REQUEST['wprtsp_activation_message'] );
		switch( $_REQUEST['wprtsp_activation'] ) {

			case 'false':
				?>
				<div class="error" style="background: #fbfbfb; border-top: 1px solid #eee; border-right: 1px solid #eee;">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				break;

			case 'true':
            default:
                ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo $message; ?></p>
				</div>
				<?php
				break;

		}
    }
    
    if(isset( $_REQUEST['wprtsp_deactivation'] ) && ! empty( $_REQUEST['wprtsp_deactivation_message'] ) ) {
        $message = urldecode( $_REQUEST['wprtsp_deactivation_message'] );
        switch( $_REQUEST['wprtsp_deactivation'] ) {
            case 'false':
				?>
				<div class="error" style="background: #fbfbfb; border-top: 1px solid #eee; border-right: 1px solid #eee;">
					<p><?php echo ucfirst($message) . '. Are you using the correct license key?'; ?></p>
				</div>
				<?php
				break;

			case 'true':
            default:
                ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo ucfirst($message); ?></p>
				</div>
				<?php
				break;
        }
    }
}

// ====


/**
 * Allows plugins to use their own update API.
 *
 * @author Easy Digital Downloads
 * @version 1.6.17
 */
if( ! class_exists( 'WPRTSP_Plugin_Updater' ) ) {
    class WPRTSP_Plugin_Updater {

        private $api_url     = '';
        private $api_data    = array();
        private $name        = '';
        private $slug        = '';
        private $version     = '';
        private $wp_override = false;
        private $cache_key   = '';

        private $health_check_timeout = 5;

        /**
         * Class constructor.
         *
         * @uses plugin_basename()
         * @uses hook()
         *
         * @param string  $_api_url     The URL pointing to the custom API endpoint.
         * @param string  $_plugin_file Path to the plugin file.
         * @param array   $_api_data    Optional data to send with API calls.
         */
        public function __construct( $_api_url, $_plugin_file, $_api_data = null ) {

            global $edd_plugin_data;

            $this->api_url     = trailingslashit( $_api_url );
            $this->api_data    = $_api_data;
            $this->name        = plugin_basename( $_plugin_file );
            $this->slug        = basename( $_plugin_file, '.php' );
            $this->version     = $_api_data['version'];
            $this->wp_override = isset( $_api_data['wp_override'] ) ? (bool) $_api_data['wp_override'] : false;
            $this->beta        = ! empty( $this->api_data['beta'] ) ? true : false;
            $this->cache_key   = 'edd_sl_' . md5( serialize( $this->slug . $this->api_data['license'] . $this->beta ) );

            $edd_plugin_data[ $this->slug ] = $this->api_data;

            /**
             * Fires after the $edd_plugin_data is setup.
             *
             * @since x.x.x
             *
             * @param array $edd_plugin_data Array of EDD SL plugin data.
             */
            do_action( 'post_wprtsp_plugin_updater_setup', $edd_plugin_data );

            // Set up hooks.
            $this->init();

        }

        /**
         * Set up WordPress filters to hook into WP's update process.
         *
         * @uses add_filter()
         *
         * @return void
         */
        public function init() {

            add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
            add_filter( 'plugins_api', array( $this, 'plugins_api_filter' ), 10, 3 );
            remove_action( 'after_plugin_row_' . $this->name, 'wp_plugin_update_row', 10 );
            add_action( 'after_plugin_row_' . $this->name, array( $this, 'show_update_notification' ), 10, 2 );
            add_action( 'admin_init', array( $this, 'show_changelog' ) );

        }

        /**
         * Check for Updates at the defined API endpoint and modify the update array.
         *
         * This function dives into the update API just when WordPress creates its update array,
         * then adds a custom API call and injects the custom plugin data retrieved from the API.
         * It is reassembled from parts of the native WordPress plugin update code.
         * See wp-includes/update.php line 121 for the original wp_update_plugins() function.
         *
         * @uses api_request()
         *
         * @param array   $_transient_data Update array build by WordPress.
         * @return array Modified update array with custom plugin data.
         */
        public function check_update( $_transient_data ) {

            global $pagenow;

            if ( ! is_object( $_transient_data ) ) {
                $_transient_data = new stdClass;
            }

            if ( 'plugins.php' == $pagenow && is_multisite() ) {
                return $_transient_data;
            }

            if ( ! empty( $_transient_data->response ) && ! empty( $_transient_data->response[ $this->name ] ) && false === $this->wp_override ) {
                return $_transient_data;
            }

            $version_info = $this->get_cached_version_info();

            if ( false === $version_info ) {
                $version_info = $this->api_request( 'plugin_latest_version', array( 'slug' => $this->slug, 'beta' => $this->beta ) );

                $this->set_version_info_cache( $version_info );

            }

            if ( false !== $version_info && is_object( $version_info ) && isset( $version_info->new_version ) ) {

                if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {

                    $_transient_data->response[ $this->name ] = $version_info;

                }

                $_transient_data->last_checked           = time();
                $_transient_data->checked[ $this->name ] = $this->version;

            }

            return $_transient_data;
        }

        /**
         * show update nofication row -- needed for multisite subsites, because WP won't tell you otherwise!
         *
         * @param string  $file
         * @param array   $plugin
         */
        public function show_update_notification( $file, $plugin ) {

            if ( is_network_admin() ) {
                return;
            }

            if( ! current_user_can( 'update_plugins' ) ) {
                return;
            }

            if( ! is_multisite() ) {
                return;
            }

            if ( $this->name != $file ) {
                return;
            }

            // Remove our filter on the site transient
            remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 10 );

            $update_cache = get_site_transient( 'update_plugins' );

            $update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

            if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->name ] ) ) {

                $version_info = $this->get_cached_version_info();

                if ( false === $version_info ) {
                    $version_info = $this->api_request( 'plugin_latest_version', array( 'slug' => $this->slug, 'beta' => $this->beta ) );

                    // Since we disabled our filter for the transient, we aren't running our object conversion on banners, sections, or icons. Do this now:
                    if ( isset( $version_info->banners ) && ! is_array( $version_info->banners ) ) {
                        $version_info->banners = $this->convert_object_to_array( $version_info->banners );
                    }

                    if ( isset( $version_info->sections ) && ! is_array( $version_info->sections ) ) {
                        $version_info->sections = $this->convert_object_to_array( $version_info->sections );
                    }

                    if ( isset( $version_info->icons ) && ! is_array( $version_info->icons ) ) {
                        $version_info->icons = $this->convert_object_to_array( $version_info->icons );
                    }

                    $this->set_version_info_cache( $version_info );
                }

                if ( ! is_object( $version_info ) ) {
                    return;
                }

                if ( version_compare( $this->version, $version_info->new_version, '<' ) ) {

                    $update_cache->response[ $this->name ] = $version_info;

                }

                $update_cache->last_checked = time();
                $update_cache->checked[ $this->name ] = $this->version;

                set_site_transient( 'update_plugins', $update_cache );

            } else {

                $version_info = $update_cache->response[ $this->name ];

            }

            // Restore our filter
            add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

            if ( ! empty( $update_cache->response[ $this->name ] ) && version_compare( $this->version, $version_info->new_version, '<' ) ) {

                // build a plugin list row, with update notification
                $wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
                # <tr class="plugin-update-tr"><td colspan="' . $wp_list_table->get_column_count() . '" class="plugin-update colspanchange">
                echo '<tr class="plugin-update-tr" id="' . $this->slug . '-update" data-slug="' . $this->slug . '" data-plugin="' . $this->slug . '/' . $file . '">';
                echo '<td colspan="3" class="plugin-update colspanchange">';
                echo '<div class="update-message notice inline notice-warning notice-alt">';

                $changelog_link = self_admin_url( 'index.php?edd_sl_action=view_plugin_changelog&plugin=' . $this->name . '&slug=' . $this->slug . '&TB_iframe=true&width=772&height=911' );

                if ( empty( $version_info->download_link ) ) {
                    printf(
                        __( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s.', 'easy-digital-downloads' ),
                        esc_html( $version_info->name ),
                        '<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
                        esc_html( $version_info->new_version ),
                        '</a>'
                    );
                } else {
                    printf(
                        __( 'There is a new version of %1$s available. %2$sView version %3$s details%4$s or %5$supdate now%6$s.', 'easy-digital-downloads' ),
                        esc_html( $version_info->name ),
                        '<a target="_blank" class="thickbox" href="' . esc_url( $changelog_link ) . '">',
                        esc_html( $version_info->new_version ),
                        '</a>',
                        '<a href="' . esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $this->name, 'upgrade-plugin_' . $this->name ) ) .'">',
                        '</a>'
                    );
                }

                do_action( "in_plugin_update_message-{$file}", $plugin, $version_info );

                echo '</div></td></tr>';
            }
        }

        /**
         * Updates information on the "View version x.x details" page with custom data.
         *
         * @uses api_request()
         *
         * @param mixed   $_data
         * @param string  $_action
         * @param object  $_args
         * @return object $_data
         */
        public function plugins_api_filter( $_data, $_action = '', $_args = null ) {

            if ( $_action != 'plugin_information' ) {

                return $_data;

            }

            if ( ! isset( $_args->slug ) || ( $_args->slug != $this->slug ) ) {

                return $_data;

            }

            $to_send = array(
                'slug'   => $this->slug,
                'is_ssl' => is_ssl(),
                'fields' => array(
                    'banners' => array(),
                    'reviews' => false,
                    'icons'   => array(),
                )
            );

            $cache_key = 'edd_api_request_' . md5( serialize( $this->slug . $this->api_data['license'] . $this->beta ) );

            // Get the transient where we store the api request for this plugin for 24 hours
            $edd_api_request_transient = $this->get_cached_version_info( $cache_key );

            //If we have no transient-saved value, run the API, set a fresh transient with the API value, and return that value too right now.
            if ( empty( $edd_api_request_transient ) ) {

                $api_response = $this->api_request( 'plugin_information', $to_send );

                // Expires in 3 hours
                $this->set_version_info_cache( $api_response, $cache_key );

                if ( false !== $api_response ) {
                    $_data = $api_response;
                }

            } else {
                $_data = $edd_api_request_transient;
            }

            // Convert sections into an associative array, since we're getting an object, but Core expects an array.
            if ( isset( $_data->sections ) && ! is_array( $_data->sections ) ) {
                $_data->sections = $this->convert_object_to_array( $_data->sections );
            }

            // Convert banners into an associative array, since we're getting an object, but Core expects an array.
            if ( isset( $_data->banners ) && ! is_array( $_data->banners ) ) {
                $_data->banners = $this->convert_object_to_array( $_data->banners );
            }

            // Convert icons into an associative array, since we're getting an object, but Core expects an array.
            if ( isset( $_data->icons ) && ! is_array( $_data->icons ) ) {
                $_data->icons = $this->convert_object_to_array( $_data->icons );
            }

            return $_data;
        }

        /**
         * Convert some objects to arrays when injecting data into the update API
         *
         * Some data like sections, banners, and icons are expected to be an associative array, however due to the JSON
         * decoding, they are objects. This method allows us to pass in the object and return an associative array.
         *
         * @since 3.6.5
         *
         * @param stdClass $data
         *
         * @return array
         */
        private function convert_object_to_array( $data ) {
            $new_data = array();
            foreach ( $data as $key => $value ) {
                $new_data[ $key ] = $value;
            }

            return $new_data;
        }

        /**
         * Disable SSL verification in order to prevent download update failures
         *
         * @param array   $args
         * @param string  $url
         * @return object $array
         */
        public function http_request_args( $args, $url ) {

            $verify_ssl = $this->verify_ssl();
            if ( strpos( $url, 'https://' ) !== false && strpos( $url, 'edd_action=package_download' ) ) {
                $args['sslverify'] = $verify_ssl;
            }
            return $args;

        }

        /**
         * Calls the API and, if successfull, returns the object delivered by the API.
         *
         * @uses get_bloginfo()
         * @uses wp_remote_post()
         * @uses is_wp_error()
         *
         * @param string  $_action The requested action.
         * @param array   $_data   Parameters for the API action.
         * @return false|object
         */
        private function api_request( $_action, $_data ) {

            global $wp_version, $edd_plugin_url_available;

            // Do a quick status check on this domain if we haven't already checked it.
            $store_hash = md5( $this->api_url );
            if ( ! is_array( $edd_plugin_url_available ) || ! isset( $edd_plugin_url_available[ $store_hash ] ) ) {
                $test_url_parts = parse_url( $this->api_url );

                $scheme = ! empty( $test_url_parts['scheme'] ) ? $test_url_parts['scheme']     : 'http';
                $host   = ! empty( $test_url_parts['host'] )   ? $test_url_parts['host']       : '';
                $port   = ! empty( $test_url_parts['port'] )   ? ':' . $test_url_parts['port'] : '';

                if ( empty( $host ) ) {
                    $edd_plugin_url_available[ $store_hash ] = false;
                } else {
                    $test_url = $scheme . '://' . $host . $port;
                    $response = wp_remote_get( $test_url, array( 'timeout' => $this->health_check_timeout, 'sslverify' => true ) );
                    $edd_plugin_url_available[ $store_hash ] = is_wp_error( $response ) ? false : true;
                }
            }

            if ( false === $edd_plugin_url_available[ $store_hash ] ) {
                return;
            }

            $data = array_merge( $this->api_data, $_data );

            if ( $data['slug'] != $this->slug ) {
                return;
            }

            if( $this->api_url == trailingslashit ( home_url() ) ) {
                return false; // Don't allow a plugin to ping itself
            }

            $api_params = array(
                'edd_action' => 'get_version',
                'license'    => ! empty( $data['license'] ) ? $data['license'] : '',
                'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
                'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
                'version'    => isset( $data['version'] ) ? $data['version'] : false,
                'slug'       => $data['slug'],
                'author'     => $data['author'],
                'url'        => home_url(),
                'beta'       => ! empty( $data['beta'] ),
            );

            $verify_ssl = $this->verify_ssl();
            $request    = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => $verify_ssl, 'body' => $api_params ) );

            if ( ! is_wp_error( $request ) ) {
                $request = json_decode( wp_remote_retrieve_body( $request ) );
            }

            if ( $request && isset( $request->sections ) ) {
                $request->sections = maybe_unserialize( $request->sections );
            } else {
                $request = false;
            }

            if ( $request && isset( $request->banners ) ) {
                $request->banners = maybe_unserialize( $request->banners );
            }

            if ( $request && isset( $request->icons ) ) {
                $request->icons = maybe_unserialize( $request->icons );
            }

            if( ! empty( $request->sections ) ) {
                foreach( $request->sections as $key => $section ) {
                    $request->$key = (array) $section;
                }
            }

            return $request;
        }

        public function show_changelog() {

            global $edd_plugin_data;

            if( empty( $_REQUEST['edd_sl_action'] ) || 'view_plugin_changelog' != $_REQUEST['edd_sl_action'] ) {
                return;
            }

            if( empty( $_REQUEST['plugin'] ) ) {
                return;
            }

            if( empty( $_REQUEST['slug'] ) ) {
                return;
            }

            if( ! current_user_can( 'update_plugins' ) ) {
                wp_die( __( 'You do not have permission to install plugin updates', 'easy-digital-downloads' ), __( 'Error', 'easy-digital-downloads' ), array( 'response' => 403 ) );
            }

            $data         = $edd_plugin_data[ $_REQUEST['slug'] ];
            $beta         = ! empty( $data['beta'] ) ? true : false;
            $cache_key    = md5( 'edd_plugin_' . sanitize_key( $_REQUEST['plugin'] ) . '_' . $beta . '_version_info' );
            $version_info = $this->get_cached_version_info( $cache_key );

            if( false === $version_info ) {

                $api_params = array(
                    'edd_action' => 'get_version',
                    'item_name'  => isset( $data['item_name'] ) ? $data['item_name'] : false,
                    'item_id'    => isset( $data['item_id'] ) ? $data['item_id'] : false,
                    'slug'       => $_REQUEST['slug'],
                    'author'     => $data['author'],
                    'url'        => home_url(),
                    'beta'       => ! empty( $data['beta'] )
                );

                $verify_ssl = $this->verify_ssl();
                $request    = wp_remote_post( $this->api_url, array( 'timeout' => 15, 'sslverify' => $verify_ssl, 'body' => $api_params ) );

                if ( ! is_wp_error( $request ) ) {
                    $version_info = json_decode( wp_remote_retrieve_body( $request ) );
                }


                if ( ! empty( $version_info ) && isset( $version_info->sections ) ) {
                    $version_info->sections = maybe_unserialize( $version_info->sections );
                } else {
                    $version_info = false;
                }

                if( ! empty( $version_info ) ) {
                    foreach( $version_info->sections as $key => $section ) {
                        $version_info->$key = (array) $section;
                    }
                }

                $this->set_version_info_cache( $version_info, $cache_key );

            }

            if( ! empty( $version_info ) && isset( $version_info->sections['changelog'] ) ) {
                echo '<div style="background:#fff;padding:10px;">' . $version_info->sections['changelog'] . '</div>';
            }

            exit;
        }

        public function get_cached_version_info( $cache_key = '' ) {

            if( empty( $cache_key ) ) {
                $cache_key = $this->cache_key;
            }

            $cache = get_option( $cache_key );

            if( empty( $cache['timeout'] ) || time() > $cache['timeout'] ) {
                return false; // Cache is expired
            }

            // We need to turn the icons into an array, thanks to WP Core forcing these into an object at some point.
            $cache['value'] = json_decode( $cache['value'] );
            if ( ! empty( $cache['value']->icons ) ) {
                $cache['value']->icons = (array) $cache['value']->icons;
            }

            return $cache['value'];

        }

        public function set_version_info_cache( $value = '', $cache_key = '' ) {

            if( empty( $cache_key ) ) {
                $cache_key = $this->cache_key;
            }

            $data = array(
                'timeout' => strtotime( '+3 hours', time() ),
                'value'   => json_encode( $value )
            );

            update_option( $cache_key, $data, 'no' );

        }

        /**
         * Returns if the SSL of the store should be verified.
         *
         * @since  1.6.13
         * @return bool
         */
        private function verify_ssl() {
            return (bool) apply_filters( 'edd_sl_api_request_verify_ssl', true, $this );
        }

    }
}

if(! class_exists('WPRTSP_EDD_Settings')) {
    class WPRTSP_EDD_Settings{
        function __construct(){
            add_action( 'admin_init', array($this, 'register_settings') );
            add_action( 'admin_menu', array( $this, 'settings_menu' ) );

        }

        function register_settings(){
            register_setting('wprtsp', 'wprtsp', array( $this, 'sanitize' ));
            add_settings_section('wprtsp_main', '', array($this,'main_section_text'), 'wprtsp');
            add_settings_field('wprtsp_license', 'License Key', array($this, 'wprtsp_license_key'), 'wprtsp', 'wprtsp_main');
            add_settings_field('wprtsp_activation', 'Status', array($this, 'wprtsp_license_activation'), 'wprtsp', 'wprtsp_main');
        }
            
        function main_section_text(){
        }

        function wprtsp_license_activation() {
            $settings = get_option('wprtsp');
            $wprtsp_license_status = get_option('wprtsp_license_status');
            if(! $this->get_setting('license_key') ) {
                ?>
                You must <strong>1. Enter your license key</strong> and <strong>2. Save your license key</strong> before you can activate it.
                <?php
                return;
            }
            else {
                if( $wprtsp_license_status !== false && $wprtsp_license_status == 'valid' ) {
                    ?>
                    <span style="color: hsl(120, 100%, 27%);font-weight: bold;border-radius: 5px;display: inline-block;padding: .5em 1em;"><?php _e('ACTIVE'); ?></span>
                <?php
                    submit_button('Deactivate', 'secondary', 'wprtsp[license_deactivate]', false, false );
                }
                else{
                    submit_button('Activate', 'secondary', 'wprtsp[license_activate]', false, false );
                }
            }
            
        }

        function wprtsp_license_key(){
            $settings = get_option('wprtsp');
            $wprtsp_license_status = get_option('wprtsp_license_status');
            $readonly = '';
            $protected = 'type="text"';
            if( $wprtsp_license_status !== false && $wprtsp_license_status == 'valid' ) {
                $readonly = 'readonly';
                $protected = 'type="password"';
            }
            ?>
            <input autocomplete="on" <?php echo $readonly; ?> <?php echo $protected; ?> placeholder="Enter your license key" id="license_key" name="wprtsp[license_key]" value="<?php echo esc_attr( $this->get_setting('license_key')); ?>" />
            <?php
        }
            
        function settings_menu(){
            //add_options_page('Social Proof', 'Social Proof', 'manage_options', 'wpsppro', array( $this, 'settings_page' ));
            add_submenu_page('edit.php?post_type=socialproof','Social Proof', 'License', 'manage_options', 'wprtsp', array( $this, 'settings_page' ));
            //add_menu_page('Social Proof', 'Social Proof', 'manage_options', 'wprtsp', array( $this, 'settings_page' ));
        }

        function settings_page(){ ?>
            <div class="wrap">
            <h1>WP Real-Time Social-Proof License Settings</h1>
            <div class="container">
                <form method="post" action="options.php" autocomplete="on" id="wprtsp-license-form">
                <?php settings_fields( 'wprtsp' ); ?>
                <?php do_settings_sections( 'wprtsp' ); ?>
                <?php submit_button(); ?>
                </form>
            </div>
        <?php
        }
            
        function get_setting( $setting ) {
            $defaults = $this->defaults();
            $settings = wp_parse_args( get_option( 'wprtsp', $defaults ), $defaults );
            return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
        }
            
        function defaults() {
            $defaults = array(
                'license_key' => '',
            );
            return $defaults;
        }

        function sanitize( $settings ){
            $settings['license_key'] = sanitize_text_field($settings['license_key']);    
            return $settings;
        }
    }
}

$wprtsp_edd_settings = new WPRTSP_EDD_Settings();
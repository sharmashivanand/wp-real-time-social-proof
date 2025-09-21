<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WPRTSP_LICENSE_PAGE' ) ) {
	define( 'WPRTSP_LICENSE_PAGE', 'wprtsp-settings' );
}

if ( ! class_exists( 'WPRTSP_EDD_Settings' ) ) {
	class WPRTSP_EDD_Settings {
		function __construct() {
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_menu', array( $this, 'settings_menu' ) );

			add_action( 'admin_notices', array( $this, 'wprtsp_check_dependencies' ) );
			// the name of the settings page for the license input to be displayed

			add_action( 'admin_notices', array( $this, 'wprtsp_admin_notices' ) );
			// add_action( 'admin_init', 'wprtsp_activate_license' );
			// add_action( 'admin_init',array( $this, 'wprtsp_deactivate_license' ) );
		}

		/**
		 * This is a means of catching errors from the activation method above and displaying it to the customer
		 */
		function wprtsp_admin_notices() {

			if ( isset( $_REQUEST['wprtsp_activation'] ) && ! empty( $_REQUEST['wprtsp_activation_message'] ) ) {
				$message = urldecode( $_REQUEST['wprtsp_activation_message'] );
				switch ( $_REQUEST['wprtsp_activation'] ) {

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

			if ( isset( $_REQUEST['wprtsp_deactivation'] ) && ! empty( $_REQUEST['wprtsp_deactivation_message'] ) ) {
				$message = urldecode( $_REQUEST['wprtsp_deactivation_message'] );
				switch ( $_REQUEST['wprtsp_deactivation'] ) {
					case 'false':
						?>
				<div class="error" style="background: #fbfbfb; border-top: 1px solid #eee; border-right: 1px solid #eee;">
					<p><?php echo ucfirst( $message ) . '. Are you using the correct license key?'; ?></p>
				</div>
						<?php
						break;

					case 'true':
					default:
						?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo ucfirst( $message ); ?></p>
				</div>
						<?php
						break;
				}
			}
		}

		/************************************
		 * this illustrates how to check if
		 * a license key is still valid
		 * the updater does this for you,
		 * so this is only needed if you
		 * want to do something custom
		 * UNUSED / Can be used on-demand for troubleshooting
		 *************************************/

		function wprtsp_check_license() {

			// Security check - ensure user has proper capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			global $wp_version;
			$wprtsp = WPRTSP::get_instance();

			$license = sanitize_text_field( $wprtsp->get_setting( 'license_key' ) );

			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license,
				// 'item_name'  => urlencode( WPRTSP_ITEM_NAME ),
				'item_id'    => 262,
				'url'        => site_url(),
			);

			// Call the custom API.
			$response = wp_remote_post(
				WPRTSP_EDD_SL_URL,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $license_data->license == 'valid' ) {
				// echo 'valid'; exit;
				return 'valid';
				// this license is still valid
			} else {
				// echo 'invalid'; exit;
				return 'invalid';
				// this license is no longer valid
			}
		}


		function wprtsp_check_dependencies() {

			if ( class_exists( 'WPRTSP_Records_Manager' ) ) {
				$wpsprm  = wprtsp_records_manager();
				$file    = new ReflectionClass( 'WPRTSP_Records_Manager' );
				$file    = $file->getFileName();
				$data    = get_plugin_data( $file, false );
				$version = $data['Version'];
				if ( version_compare( $version, '0.2', '<=' ) ) {
					echo '<div class="notice notice-error"><p>WP Social Proof requires Records Manager Version 1.0 or later. You are using version ' . $data['Version'] . '. Please <a href="https://wp-social-proof.com/?p=339" target="_blank">visit your Social Proof account</a> to download the updated version.</p></div>';
				}
			}
		}

		function settings_menu() {
			add_submenu_page( 'edit.php?post_type=socialproof', 'Social Proof', 'License', 'manage_options', 'wprtsp', array( $this, 'settings_page' ) );
		}

		function register_settings() {
			register_setting( 'wprtsp', 'wprtsp', array( $this, 'sanitize' ) );
			add_settings_section( 'wprtsp_main', '', array( $this, 'main_section_text' ), 'wprtsp' );
			add_settings_field( 'wprtsp_license', 'License Key', array( $this, 'wprtsp_license_key_markup' ), 'wprtsp', 'wprtsp_main' );
			add_settings_field( 'wprtsp_activation', 'Status', array( $this, 'wprtsp_license_actions_markup' ), 'wprtsp', 'wprtsp_main' );
		}

		function main_section_text() {
		}

		function wprtsp_license_key_markup() {
			$readonly  = '';
			$protected = 'type="text"';
			if ( $this->get_activation_status() == 'valid' ) {
				// $readonly  = 'readonly';
				// $protected = 'type="password"';
			}
			?>
			<input autocomplete="on" <?php echo $readonly; ?> <?php echo $protected; ?> placeholder="Enter your license key" id="license_key" name="wprtsp[license_key]" value="<?php echo esc_attr( $this->get_setting( 'license_key' ) ); ?>" />
			<?php
		}

		function wprtsp_license_actions_markup() {
			$wprtsp = WPRTSP::get_instance();
			?>
			<script type="text/javascript">
			console.dir('BEFORE');
			console.dir('WPRTST license status locally:' + '<?php echo get_transient( 'wprtsp_license_status' ); ?>')
			console.dir('WPRTST license status on server:' + '<?php echo $wprtsp->get_pro_status( false ); ?>')
			</script>
			<?php
			$status = $wprtsp->get_pro_status( false ) == false ? 'no' : 'yes';
			?>
			<input id="wprtsp_activation" name="wprtsp[wprtsp_activation]" type="hidden" value="<?php echo $status; ?>" />
			<?php
			if ( ! $this->get_setting( 'license_key' ) ) {
				?>
				You must <strong>1. Enter your license key</strong> and <strong>2. Save your license key</strong> before you can activate it.
				<?php
			} elseif ( $this->get_activation_status() == 'valid' ) {
				?>
					<span style="color: hsl(120, 100%, 27%);font-weight: bold;border-radius: 5px;display: inline-block;padding: .5em 0em;"><?php _e( 'ACTIVE' ); ?></span>
					<?php
			} else {
				?>
					<span style="color: hsl(350, 100%, 50%);font-weight: bold;border-radius: 5px;display: inline-block;padding: .5em 0em;"><?php _e( ucwords( $this->get_activation_status() ) ); ?></span>
					<?php
					// submit_button( 'Activate', 'secondary', 'wprtsp[license_activate]', false, false );

			}
			?>
			
			<script type="text/javascript">
			console.dir('AFTER');
			console.dir('WPRTST license status locally:' + '<?php echo get_transient( 'wprtsp_license_status' ); ?>')
			console.dir('WPRTST license status on server:' + '<?php echo $wprtsp->get_pro_status( false ); ?>')
			</script>
			<?php
		}

		function settings_page() {
			// Security check - ensure user has proper capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'wprtsp' ) );
			}
			?>
			<div class="wrap">
			<h1>WP Real-Time Social-Proof License Settings</h1>
			<?php settings_errors( 'wprtsp' ); ?>
			<div class="container">
				<form method="post" action="options.php" autocomplete="on" id="wprtsp-license-form">
				<?php settings_fields( 'wprtsp' ); ?>
				<?php wp_nonce_field( 'wprtsp_license_action', 'wprtsp_license_nonce' ); ?>
				<?php
				do_settings_sections( 'wprtsp' );
				$wprtsp = WPRTSP::get_instance();
				if ( $this->get_activation_status() !== 'valid' ) {
					$text = 'Save & Activate';
				} else {
					$text = 'De-Activate';
				}
				submit_button( $text, 'primary', $text );
				?>
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

		function sanitize( $settings ) {
			$settings['license_key'] = sanitize_text_field( $settings['license_key'] );
			// $this->wprtsp_purge_old_license( $settings['license_key'] );
			// var_dump( $settings );
			// die();
			$status = sanitize_text_field( $settings['wprtsp_activation'] );

			// Security checks before processing license operations
			if ( ! current_user_can( 'manage_options' ) ) {
				add_settings_error( 'wprtsp', 'unauthorized', __( 'Unauthorized operation. You do not have sufficient permissions.', 'wprtsp' ), 'error' );
				return $settings; // Return without processing
			}

			// Verify nonce for license operations (only when form is submitted)
			if ( isset( $_POST['wprtsp_license_nonce'] ) ) {
				if ( ! wp_verify_nonce( $_POST['wprtsp_license_nonce'], 'wprtsp_license_action' ) ) {
					add_settings_error( 'wprtsp', 'nonce_failed', __( 'Security check failed. Please refresh the page and try again.', 'wprtsp' ), 'error' );
					return $settings; // Return without processing
				}
			}

			$wprtsp = WPRTSP::get_instance();
			if ( $status == 'no' ) { // Not Activated
				// $old = sanitize_text_field( $wprtsp->get_setting( 'license_key' ) );
				if ( $settings['license_key'] ) {
					$this->wprtsp_activate_license( $settings['license_key'] );
				}
			}
			if ( $status == 'yes' ) { // Already Activated / Deactivate?
				$this->wprtsp_deactivate_license( $settings['license_key'] );
				$settings['license_key'] = '';
			}
			return $settings;
		}

		function get_activation_status() {
			return get_transient( 'wprtsp_license_status' );
		}

		/**
		 * Purge activation status in case a new license key is entered.
		 * UNUSED
		 *
		 * @param [type] $new
		 * @return void
		 */
		function wprtsp_purge_old_license( $new ) {
			$wprtsp = WPRTSP::get_instance();

			$old = sanitize_text_field( $wprtsp->get_setting( 'license_key' ) );

			if ( $old && $old != $new ) {
				$this->wprtsp_deactivate_license( $old );
				$this->wprtsp_activate_license( $new ); // new license has been entered, so must reactivate
			}
			// return $new;
		}

		/************************************
		 * this illustrates how to activate
		 * a license key
		 *************************************/

		function wprtsp_activate_license( $license ) {
			// listen for our activate button to be clicked

			// Security check - ensure user has proper capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			// Validate license key
			$license = trim( sanitize_text_field( $license ) );
			if ( empty( $license ) ) {
				return false;
			}

			$wprtsp = WPRTSP::get_instance();

			// $license = trim( $_POST['wprtsp']['wpsppro_license_key'] );

			$plugindata = get_plugin_data( $wprtsp->dir . 'wprtsp.php', 0, 0 );

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				// 'item_name'  => urlencode( WPSPPRO_ITEM_NAME ), // the name of our product in EDD
				'item_name'  => $plugindata['Name'], // name of this plugin
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post(
				WPRTSP_EDD_SL_URL,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				// llog( debug_backtrace()[1]['function'] );

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.', 'wprtsp' );
				}
			} else {

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( false === $license_data->success ) {

					switch ( $license_data->error ) {

						case 'expired':
							$message = sprintf(
								__( 'Your license key expired on %s.', 'wprtsp' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
							);
							break;

						case 'revoked':
							$message = __( 'Your license key has been disabled.', 'wprtsp' );
							break;

						case 'missing':
							$message = __( 'Invalid license.', 'wprtsp' );
							break;

						case 'invalid':
						case 'site_inactive':
							$message = __( 'Your license is not active for this URL.', 'wprtsp' );
							break;

						case 'item_name_mismatch':
							$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'wprtsp' ), get_plugin_data( WPRTSPFILE, 0, 0 )['Name'] );
							break;

						case 'no_activations_left':
							$message = __( 'Your license key has reached its activation limit.', 'wprtsp' );
							break;

						default:
							$message = __( 'An error occurred, please try again.', 'wprtsp' );
							break;
					}

					if ( ! empty( $_REQUEST['wprtsp'] ) && ! empty( $_REQUEST['wprtsp']['license_key'] ) ) {
						$message = 'Tried to activate key: <code>' . $_REQUEST['wprtsp']['license_key'] . '</code> <strong>Please correct the license key again and retry</strong>.';
					} else {
						$message .= ' <strong>Please correct the license key again and retry</strong>.';
					}
					$wprtsp = WPRTSP::get_instance();
					$wprtsp->set_validation( $license_data->error );

				} else {
					// update_option( 'wprtsp', array( 'license_key' => $_REQUEST['wprtsp']['license_key'] ) );
					$message = __( 'Activation Successful. Thankyou for activating!', 'wprtsp' );
					$wprtsp->set_validation( $license_data->license );
				}
			}

			// $license_data->license will be either "valid" or "invalid"
			// update_option('wpsppro');

			return;
			wp_redirect(
				add_query_arg(
					array(
						'wprtsp_activation'         => $license_data->success ? $license_data->success : 'false',
						'wprtsp_activation_message' => urlencode( $message ),
					),
					get_admin_url( null, 'edit.php?post_type=socialproof&page=wprtsp' )
				)
			);
			exit();
		}

		/***********************************************
		 * Illustrates how to deactivate a license key.
		 * This will decrease the site count
		 ***********************************************/

		function wprtsp_deactivate_license( $license ) {

			// Security check - ensure user has proper capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			// Validate license key
			$license = trim( sanitize_text_field( $license ) );
			if ( empty( $license ) ) {
				return false;
			}

			$wprtsp     = WPRTSP::get_instance();
			$plugindata = get_plugin_data( WPRTSPFILE, 0, 0 );
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				// 'item_name'  => urlencode( WPSPPRO_ITEM_NAME ), // the name of our product in EDD
				'item_name'  => $plugindata['Name'], // name of this plugin
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post(
				WPRTSP_EDD_SL_URL,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.', 'wprtsp' );
				}

				$redirect = add_query_arg(
					array(
						'wprtsp_deactivation'       => 'false',
						'wprtsp_activation_message' => urlencode( $message ),
					),
					get_admin_url( null, 'edit.php?post_type=socialproof&page=wprtsp' )
				);

				// wp_redirect( $redirect );
				exit();
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) ); // decode the license data

			if ( $license_data->license == 'deactivated' ) {
				delete_transient( 'wprtsp_license_status' );
				delete_option( 'wprtsp' );
			}

			return;
			wp_redirect(
				add_query_arg(
					array(
						'wprtsp_deactivation'         => $license_data->success ? 'true' : 'false',
						'wprtsp_deactivation_message' => urlencode( $license_data->license ),
					),
					get_admin_url( null, 'edit.php?post_type=socialproof&page=wprtsp' )
				)
			);
			exit();
		}

		function flog( $str ) {
			if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
				return;
			}
			$date = date( 'Ymd-G:i:s' ); // 20171231-23:59:59
			$date = $date . '-' . microtime( true );
			$file = trailingslashit( __DIR__ ) . 'log.log';
			file_put_contents( $file, PHP_EOL . $date, FILE_APPEND | LOCK_EX );
			usleep( 1000 );
			$str = print_r( $str, true );
			file_put_contents( $file, PHP_EOL . $str, FILE_APPEND | LOCK_EX );
			usleep( 1000 );
		}
	}
}

$wprtsp_edd_settings = new WPRTSP_EDD_Settings();

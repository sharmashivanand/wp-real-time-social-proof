<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * UNIX-style license trait - contains all license functionality
 * Provides complete license management to any class that uses it
 * PHP 5.6+ compatible
 *
 * @since 2.4
 */

trait WPRTSP_License_Trait {

	// License state
	private $license_error = '';

	/**
	 * Initialize license hooks - call from parent class constructor
	 */
	public function init_license() {
		add_action( 'admin_menu', array( $this, 'license_menu' ) );
		add_action( 'admin_init', array( $this, 'license_handle' ) );
		add_action( 'admin_notices', array( $this, 'license_notices' ) );
	}

	/**
	 * Check if license is valid
	 *
	 * @param bool $force_check Force fresh check bypassing cache
	 * @return bool True if license is valid
	 */
	public function is_valid_pro( $force_check = false ) {
		$status = get_transient( WPRTSP_LICENSE_CACHE_KEY );
		if ( false === $status || $force_check ) {
			$status = $this->license_check();
			if ( $status ) {
				set_transient( WPRTSP_LICENSE_CACHE_KEY, $status, WPRTSP_LICENSE_TTL );
			}
		}
		return 'valid' === $status;
	}

	/**
	 * Get license status - for backwards compatibility
	 *
	 * @param bool $cached Use cached result (true) or force fresh (false)
	 * @return bool License validity
	 */
	public function get_pro_status( $cached = true ) {
		return $this->is_valid_pro( ! $cached );
	}

	/**
	 * Set license validation status - for backwards compatibility
	 *
	 * @param string $status Status to set
	 * @return bool Always returns true
	 */
	public function set_validation( $status ) {
		set_transient( WPRTSP_LICENSE_CACHE_KEY, $status, WPRTSP_LICENSE_TTL );
		return true;
	}

	/**
	 * Activate license key
	 *
	 * @param string $key License key
	 * @return bool Success
	 */
	public function license_activate( $key ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->license_error( 'Unauthorized' );
		}

		$key = sanitize_text_field( trim( $key ) );
		if ( empty( $key ) ) {
			return $this->license_error( 'Empty key' );
		}

		$response = $this->license_api( 'activate_license', $key );
		if ( ! $response || empty( $response['success'] ) ) {
			return $this->license_error( isset( $response['error'] ) ? $response['error'] : 'Failed' );
		}

		$this->update_setting( 'license_key', $key );
		set_transient( WPRTSP_LICENSE_CACHE_KEY, 'valid', WPRTSP_LICENSE_TTL );
		return true;
	}

	/**
	 * Deactivate license
	 *
	 * @return bool Success
	 */
	public function license_deactivate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->license_error( 'Unauthorized' );
		}

		$key = $this->get_setting( 'license_key' );
		if ( $key ) {
			$this->license_api( 'deactivate_license', $key );
		}

		$this->delete_setting( 'license_key' );
		delete_transient( WPRTSP_LICENSE_CACHE_KEY );
		return true;
	}

	/**
	 * Refresh license status
	 *
	 * @return bool Current validity
	 */
	public function license_refresh() {
		delete_transient( WPRTSP_LICENSE_CACHE_KEY );
		return $this->is_valid_pro();
	}

	/**
	 * Get/set license error
	 *
	 * @param string $msg Error message (optional)
	 * @return mixed Error message or false
	 */
	public function license_error( $msg = null ) {
		if ( $msg ) {
			$this->license_error = $msg;
			return false;
		}
		return $this->license_error;
	}

	/**
	 * Check license with API
	 *
	 * @return string|bool License status
	 */
	private function license_check() {
		$key = $this->get_setting( 'license_key' );
		if ( empty( $key ) ) {
			return false;
		}

		$response = $this->license_api( 'check_license', $key );
		return isset( $response['license'] ) ? $response['license'] : false;
	}

	/**
	 * License API communication
	 *
	 * @param string $action API action
	 * @param string $key License key
	 * @return array|bool API response
	 */
	private function license_api( $action, $key ) {
		$response = wp_remote_post(
			WPRTSP_EDD_SL_URL,
			array(
				'timeout' => 15,
				'body'    => array(
					'edd_action' => $action,
					'license'    => $key,
					'item_id'    => WPRTSP_LICENSE_ITEM_ID,
					'url'        => home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
	}

	/**
	 * License admin menu
	 */
	public function license_menu() {
		add_submenu_page(
			'edit.php?post_type=socialproof',
			'License',
			'License',
			'manage_options',
			'wprtsp-license',
			array( $this, 'license_page' )
		);
	}

	/**
	 * Handle license form submissions
	 */
	public function license_handle() {
		if ( ! isset( $_POST['wprtsp_license_nonce'] ) || ! wp_verify_nonce( $_POST['wprtsp_license_nonce'], 'license' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = '';
		if ( isset( $_POST['activate'] ) ) {
			$action = 'activate';
		} elseif ( isset( $_POST['deactivate'] ) ) {
			$action = 'deactivate';
		} elseif ( isset( $_POST['refresh'] ) ) {
			$action = 'refresh';
		}

		$redirect = admin_url( 'edit.php?post_type=socialproof&page=wprtsp-license' );

		switch ( $action ) {
			case 'activate':
				$key     = sanitize_text_field( $_POST['license_key'] );
				$result  = $this->license_activate( $key );
				$message = $result ? 'License activated' : $this->license_error();
				break;
			case 'deactivate':
				$result  = $this->license_deactivate();
				$message = 'License deactivated';
				break;
			case 'refresh':
				$this->license_refresh();
				$message = 'Status refreshed';
				$result  = true;
				break;
			default:
				return;
		}

		wp_redirect(
			add_query_arg(
				array(
					'status'  => $result ? 'success' : 'error',
					'message' => urlencode( $message ),
				),
				$redirect
			)
		);
		exit;
	}

	/**
	 * License admin notices
	 */
	public function license_notices() {
		if ( ! isset( $_GET['page'] ) || 'wprtsp-license' !== $_GET['page'] ) {
			return;
		}

		if ( isset( $_GET['status'] ) && isset( $_GET['message'] ) ) {
			$class = 'success' === $_GET['status'] ? 'notice-success' : 'notice-error';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible">';
			echo '<p>' . esc_html( urldecode( $_GET['message'] ) ) . '</p>';
			echo '</div>';
		}
	}

	/**
	 * License admin page
	 */
	public function license_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$valid = $this->is_valid_pro();
		$key   = $this->get_setting( 'license_key' );
		?>
			<div class="wrap">
				<h1>License Management</h1>
				
				<div class="card" style="max-width: 600px; margin: 20px 0; padding: 20px;">
					<h2>Status: <?php echo $valid ? '<span style="color: green;">Active</span>' : '<span style="color: red;">Inactive</span>'; ?></h2>
					
					<form method="post">
					<?php wp_nonce_field( 'license', 'wprtsp_license_nonce' ); ?>
						
						<table class="form-table">
							<tr>
								<th><label for="license_key">License Key</label></th>
								<td>
								<?php if ( $valid ) : ?>
										<input type="password" id="license_key" value="<?php echo esc_attr( $key ); ?>" readonly class="regular-text" />
										<p class="description">License is active. Deactivate to change.</p>
									<?php else : ?>
										<input type="text" name="license_key" id="license_key" value="<?php echo esc_attr( $key ); ?>" class="regular-text" placeholder="Enter license key" />
										<p class="description">Enter your license key and click Activate.</p>
									<?php endif; ?>
								</td>
							</tr>
						</table>
						
						<p class="submit">
						<?php if ( $valid ) : ?>
								<input type="submit" name="deactivate" class="button button-secondary" value="Deactivate" />
								<input type="submit" name="refresh" class="button button-secondary" value="Refresh Status" />
							<?php else : ?>
								<input type="submit" name="activate" class="button button-primary" value="Activate License" />
								<?php if ( $key ) : ?>
									<input type="submit" name="refresh" class="button button-secondary" value="Check Status" />
								<?php endif; ?>
							<?php endif; ?>
						</p>
					</form>
				</div>
			</div>
			<?php
	}

	/**
	 * Singleton pattern for backwards compatibility with existing manager calls
	 * This allows existing WPRTSP_License_Manager::instance() calls to work
	 */
	private static $license_manager_instance = null;

	public static function get_license_manager() {
		if ( null === self::$license_manager_instance ) {
			// Return the WPRTSP instance that uses this trait
			self::$license_manager_instance = WPRTSP::get_instance();
		}
		return self::$license_manager_instance;
	}

	// Backwards compatibility aliases for manager methods
	public function is_valid() {
		return $this->is_valid_pro();
	}

	public function activate( $key ) {
		return $this->license_activate( $key );
	}

	public function deactivate() {
		return $this->license_deactivate();
	}

	public function refresh() {
		return $this->license_refresh();
	}

	public function error( $msg = null ) {
		return $this->license_error( $msg );
	}

	public function check() {
		return $this->license_check();
	}

	public function api( $action, $key ) {
		return $this->license_api( $action, $key );
	}

	public function menu() {
		return $this->license_menu();
	}

	public function handle() {
		return $this->license_handle();
	}

	public function notices() {
		return $this->license_notices();
	}

	public function page() {
		return $this->license_page();
	}

	/*
	 * REQUIRED METHODS - Parent class must implement these:
	 * 
	 * public function get_setting( $key ) {
	 *     // Return setting value for $key
	 * }
	 * 
	 * public function update_setting( $key, $value ) {
	 *     // Update setting $key with $value
	 * }
	 * 
	 * public function delete_setting( $key ) {
	 *     // Delete setting $key
	 * }
	 */
}


<?php

class WPRTSPGENERAL {

	static function get_instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->setup();
			$instance->setup_actions();
		}
		return $instance;
	}

	function setup() {
	}

	function llog( $str ) {
		echo '<pre>';
		print_r( $str );
		echo '</pre>';
	}

	function setup_actions() {
		add_action( 'wprtsp_add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_filter( 'wprtsp_sanitize', array( $this, 'sanitize' ) );
		add_filter( 'wprtsp_vars', array( $this, 'wprtsp_get_styles' ) ); // hook js style vars to the proof
		add_action( 'wprtsp_popup_style', array( $this, 'popup_box_style' ), 10, 2 ); // hook js style vars to the proof may be refactor?
		add_action( 'admin_init', array( $this, 'save_ga_profile' ) );
	}

	function add_meta_boxes() {
		add_meta_box( 'social-proof-general', __( 'General', 'wprtsp' ), array( $this, 'general_meta_box' ), 'socialproof', 'normal' );
	}

	function defaults( $defaults = array() ) {

		$wprtsp = wprtsp();

		$defaults['general_ga_utm_tracking']             = 1;
		$defaults['general_badge_enable']                = 1; // bool
		$defaults['general_title_string']                = 'Rachel says…'; // bool
		$defaults['general_show_on']                     = '1'; // select
		$defaults['general_post_ids']                    = get_option( 'page_on_front' ); // string
		$defaults['general_position']                    = 'bl'; // select
		$defaults['general_initial_popup_time']          = '5'; // select
		$defaults['general_duration']                    = '7'; // select
		$defaults['general_subsequent_popup_time']       = '30'; // select
		$defaults['general_box_style']                   = 'rounded';
		$defaults['general_notification_theme']          = 'light';
		$defaults['general_allowed_positions']           = array(
			'bl' => 'Bottom Left',
			'br' => 'Bottom Right',
		);
		$defaults['general_allowed_box_styles']          = array( 'square', 'rounded' );
		$defaults['general_allowed_notification_themes'] = array( 'light', 'dark' );
		$defaults['general_roles_exclude']               = '';
		$defaults['general_notification_order']          = $wprtsp->registered_proofs();
		return $defaults;
	}

	function general_meta_box() {
		global $post;
		$settings = get_post_meta( $post->ID, '_socialproof', true );
		$defaults = $this->defaults();

		if ( ! $settings ) {
			$settings = $defaults;
		}

		$settings                     = $this->sanitize( $settings );
		$show_on                      = $settings['general_show_on'];
		$wprtsp_general_roles_exclude = $settings['general_roles_exclude'];
		$post_ids                     = $settings['general_post_ids'];
		$duration                     = $settings['general_duration'];
		$initial_popup_time           = $settings['general_initial_popup_time'];
		$subsequent_popup_time        = $settings['general_subsequent_popup_time'];
		$general_badge_enable         = $settings['general_badge_enable'];
		$general_position             = $settings['general_position'];
		$positions_html               = '';
		$positions                    = $defaults['general_allowed_positions'];
		$general_box_style            = $settings['general_box_style'];
		$box_styles_html              = '';
		$box_styles                   = $defaults['general_allowed_box_styles'];
		$general_notification_theme   = $settings['general_notification_theme'];
		$notification_theme_html      = '';
		$notification_themes          = $defaults['general_allowed_notification_themes'];

		$statevars = array(
			'origin_site_url'        => get_site_url(),
			'origin_edit_url'        => get_edit_post_link( $post->ID ),
			'origin_nonce'           => wp_create_nonce( 'wprtsp_gaapi' ),
			'origin_notification_id' => $post->ID,
			'origin_ajaxurl'         => admin_url( 'admin-ajax.php' ),
		);
		$statevars = json_encode( $statevars );
		$statevars = strtr( base64_encode( $statevars ), '+/=', '-_,' );
		foreach ( $positions as $key => $value ) {
			$positions_html .= '<option value="' . $key . '" ' . selected( $general_position, $key, false ) . '>' . preg_replace( '/[^\da-z]/i', ' ', $value ) . '</option>';
		}
		foreach ( $box_styles as $value ) {
			$box_styles_html .= '<option value="' . $value . '" ' . selected( $general_box_style, $value, false ) . '>' . ucwords( preg_replace( '/[^\da-z]/i', ' ', $value ) ) . '</option>';
		}
		foreach ( $notification_themes as $value ) {
			$notification_theme_html .= '<option value="' . $value . '" ' . selected( $general_notification_theme, $value, false ) . '>' . ucwords( preg_replace( '/[^\da-z]/i', ' ', $value ) ) . '</option>';
		}
		$ga_profile = get_option( 'wpsppro_ga_profile' );
		if ( $ga_profile ) {
			$ga_profile = $ga_profile['ga_webpropertyua'];
		}
		wp_nonce_field( 'socialproof_meta_box_nonce', 'socialproof_meta_box_nonce' );
		$pdata = WPRTSP::get_instance();
		$pdata = $pdata->version;
		?>
		<input type="hidden" value="<?php echo $pdata; ?>" name="wprtsp[general_cpt_version]" />
		<table id="tbl_display" class="wprtsp_tbl wprtsp_tbl_display">
			<tr>
				<td colspan="2">
					<h3>Configuration</h3>
				</td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Connect with Google Analytics to get Live Visitor count. Make sure to select the correct analytics profile.</p></div></div><label>Google Analytics</label></td>
				<td>
				<?php
				if ( ! $ga_profile ) {
					?>
					 <a class="button-primary btn-gauth" href="<?php echo WPRTSPAPIEP . '?wppro_gaapi_authenticate=' . $statevars; ?>">Sign in with Google</a> 
					 <!-- <a class="button-primary" href="<?php echo WPRTSPAPIEP . '?wppro_gaapi_authenticate=' . $statevars; ?>">Authenticate with Google Analytics</a> -->
					<?php
				} else {
					?>
					Profile Active: <?php echo $ga_profile; ?><br /><a href="<?php echo WPRTSPAPIEP . '/?wppro_gaapi_revoke=' . $statevars; ?>" class="button-primary">Disconnect</a><?php } ?>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Check this box to see traffic and conversions in your Google Analytics profile.</p></div></div><label for="wprtsp[general_ga_utm_tracking]">Enable Google Analytics UTM Tracking for Conversions?</label></td>
				<td><input id="wprtsp[general_ga_utm_tracking]" name="wprtsp[general_ga_utm_tracking]" type="checkbox" value="1" <?php checked( 1, $settings['general_ga_utm_tracking'], true ); ?>/></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Indepentent, third-party verification builds credibility and authenticity for your website. People will be able to click on this link to see verification badge on WP-Social-Proof.Com.</p></div></div><label for="wprtsp[general_badge_enable]">Enable verified badge?</label></td>
				<td><input id="wprtsp[general_badge_enable]" name="wprtsp[general_badge_enable]" type="checkbox" value="1" <?php checked( 1, $general_badge_enable, true ); ?>/></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Customize the title notification messagee.</p></div></div><label for="wprtsp[general_title_string]">Title Notification Text</label></td>
				<td><input id="wprtsp[general_title_string]" name="wprtsp[general_title_string]" type="text" value="<?php echo $settings['general_title_string']; ?>" /></td>
			</tr>
			<tr>
				<td colspan="2">
					<h3>Display</h3>
				</td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>User with these roles will not see notifications. Comes in handy when you are logged into your own site and don't want to be disturbed.</p></div></div><label for="wprtsp_general_roles_exclude">Exclude User Roles</label></td>
				<td>
					<?php
					global $wp_roles;
					$role_options = array();
					foreach ( $wp_roles->roles as $role => $capabilities ) {
						$role_options[] = $role;
					}
					$role_options[] = '';
					?>
					<select id="wprtsp_general_roles_exclude" name="wprtsp[general_roles_exclude]">
					<?php
					foreach ( $role_options as $option ) {
						if ( ! empty( $option ) ) {
							$option_label = ucwords( $option ) . ' &amp; Above';
						} else {
							$option_label = 'None';
						}
						?>
						<option value="<?php echo $option; ?>"<?php selected( $wprtsp_general_roles_exclude, $option ); ?>><?php echo $option_label; ?></option>
						<?php
					}
					?>
					</select>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Where all to show Social Proof notifications.</p></div></div><label for="wprtsp_general_show_on">Show Notifications On</label></td>
				<td><select id="wprtsp_general_show_on" name="wprtsp[general_show_on]">
						<option value="1" <?php selected( $show_on, 1 ); ?> >Entire Site</option>
						<option value="2" <?php selected( $show_on, 2 ); ?> >On selected posts / pages</option>
						<option value="3" <?php selected( $show_on, 3 ); ?> >Everywhere except the following</option>
					</select>
			</tr>
			<tr id="post_ids_selector">
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Enable, disable Social Proof notifications on specific pages.</p></div></div><label for="wprtsp_general_post_ids">Enter Post Ids (comma separated)</label></td>
				<td><input type="text" class="widefat" <?php if ( $show_on == 1 ) {	echo 'readonly'; } ?> id="wprtsp_general_post_ids" name="wprtsp[general_post_ids]" value="<?php echo $post_ids; ?>"></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Position of Social Proof notifications. We've seen best results from the bottom left position.</p></div></div><label for="wprtsp[general_position]">Notification Position</label></td>
				<td><select id="wprtsp[general_position]" name="wprtsp[general_position]"><?php echo $positions_html; ?></select></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Depending on your site design one of these styles will look the best.</p></div></div><label for="wprtsp[general_box_style]">Notification Style</label></td>
				<td><select id="wprtsp[general_box_style]" name="wprtsp[general_box_style]"><?php echo $box_styles_html; ?></select></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Depending on your site design one of these themes will look the best.</p></div></div><label for="wprtsp[general_notification_theme]">Notification Theme</label></td>
				<td><select id="wprtsp[general_notification_theme]" name="wprtsp[general_notification_theme]"><?php echo $notification_theme_html; ?></select></td>
			</tr>
			<tr>
				<td colspan="2">
					<h3>Order of Proofs</h3>
				</td>
			</tr>
			<tr>
				<td>
					<div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>The notifications will popup in this order.  You can drag and drop to set the sequence of the notifications.</p></div></div><label for="wprtsp_general_notification_order_ui">The order of proofs</label>
				</td>
				<td>
					<ol id="wprtsp_general_notification_order_ui" multiple>
						<?php
						$proofs = $this->sanitize_order( $settings['general_notification_order'], $defaults['general_notification_order'] );// array_values( array_unique( array_merge( , array_diff( , $settings['general_notification_order'] ) ) ) );
						// $proofs = explode( ',', $proofs );
						// $this->llog( $proofs );
						foreach ( $proofs as $proof ) {
							echo '<li id="' . $proof . '">' . $proof . '</li>';
						}
						?>
					</ol>
					<input type="hidden" value="<?php echo implode( ',', $settings['general_notification_order'] ); ?>" id="wprtsp_general_notification_order" name="wprtsp[general_notification_order]" />
					<script type="text/javascript">
					jQuery( document ).ready(function($) {
						$( "#wprtsp_general_notification_order_ui" ).sortable({
							update: function( event, ui ){
								$('#wprtsp_general_notification_order').val($("#wprtsp_general_notification_order_ui").sortable('toArray'));
							}
						});
						$( "#wprtsp_general_notification_order_ui" ).disableSelection();
					});
					</script>
				</td>
			</tr>
		</table>
		<table id="tbl_timings" class="wprtsp_tbl wprtsp_tbl_timings">
			<tr>
				<td colspan="2">
					<h3>Timing</h3>
				</td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>How long should each notification show.</p></div></div><label for="wprtsp_general_duration">Duration of each notification</label></td>
				<td><input type="number" class="widefat" id="wprtsp_general_duration" name="wprtsp[general_duration]" value="<?php echo $duration; ?>"/></td>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>How long before visitors see the first notification.</p></div></div><label for="wprtsp_general_initial_popup_time">Delay before first notification</label></td>
				<td><select id="wprtsp_general_initial_popup_time" name="wprtsp[general_initial_popup_time]">
						<option value="5" <?php selected( $initial_popup_time, 5 ); ?> >5</option>
						<option value="15" <?php selected( $initial_popup_time, 15 ); ?> >15</option>
						<option value="30" <?php selected( $initial_popup_time, 30 ); ?> >30</option>
						<option value="60" <?php selected( $initial_popup_time, 60 ); ?> >60</option>
						<option value="120" <?php selected( $initial_popup_time, 120 ); ?> >120</option>
					</select>
				</td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>How much delay should be there between each notification. Too frequent notifications could ruin the user-experience. Moderation is key. Use the element of surprise.</p></div></div><label for="wprtsp_general_subsequent_popup_time">Delay between notifications</label></td>
				<td><select id="wprtsp_general_subsequent_popup_time" name="wprtsp[general_subsequent_popup_time]">
						<option value="5" <?php selected( $subsequent_popup_time, 5 ); ?> >5</option>
						<option value="15" <?php selected( $subsequent_popup_time, 15 ); ?> >15</option>
						<option value="30" <?php selected( $subsequent_popup_time, 30 ); ?> >30</option>
						<option value="60" <?php selected( $subsequent_popup_time, 60 ); ?> >60</option>
						<option value="120" <?php selected( $subsequent_popup_time, 120 ); ?> >120</option>
					</select></td>
			</tr>
		</table>
		<script type="text/javascript">
		jQuery( document ).ready(function() {
			jQuery('#wprtsp_general_show_on').on('change',  function() {
				if(jQuery('#wprtsp_general_show_on').val() == 1 ) {
					jQuery('#wprtsp_general_post_ids').attr('readonly', 'true');
				}
				else {
					jQuery('#wprtsp_general_post_ids').removeAttr('readonly');
				}
			});
		});
		</script>
		<?php
	}

	function sanitize( $request ) {
		$defaults = $this->defaults();

		if ( ! $request || ! is_array( $request ) ) { // not sure why but on a freshpost, if you customize settings, this throws errors when DEBUG is true			
			//return $request; // how do we verify this call is a valid request from our app?
			//return;
		}

		$request['general_ga_utm_tracking']       = array_key_exists( 'general_ga_utm_tracking', $request ) && $request['general_ga_utm_tracking'] ? 1 : 0;
		$request['general_badge_enable']          = array_key_exists( 'general_badge_enable', $request ) && $request['general_badge_enable'] ? 1 : 0;
		$request['general_title_string']          = array_key_exists( 'general_title_string', $request ) && $request['general_title_string'] ? sanitize_text_field( $request['general_title_string'] ) : $defaults['general_title_string'];
		$request['general_roles_exclude']         = array_key_exists( 'general_roles_exclude', $request ) ? sanitize_text_field( $request['general_roles_exclude'] ) : $defaults['general_roles_exclude'];
		$request['general_show_on']               = array_key_exists( 'general_show_on', $request ) ? sanitize_text_field( $request['general_show_on'] ) : $defaults['general_show_on'];
		$request['general_post_ids']              = array_key_exists( 'general_post_ids', $request ) ? sanitize_text_field( $request['general_post_ids'] ) : $defaults['general_post_ids'];
		$request['general_position']              = array_key_exists( 'general_position', $request ) ? sanitize_text_field( $request['general_position'] ) : $defaults['general_position'];
		$request['general_box_style']             = array_key_exists( 'general_box_style', $request ) ? sanitize_text_field( $request['general_box_style'] ) : $defaults['general_box_style'];
		$request['general_notification_theme']    = array_key_exists( 'general_notification_theme', $request ) ? sanitize_text_field( $request['general_notification_theme'] ) : $defaults['general_notification_theme'];
		$request['general_duration']              = array_key_exists( 'general_duration', $request ) ? sanitize_text_field( $request['general_duration'] ) : $defaults['general_duration'];
		$request['general_initial_popup_time']    = array_key_exists( 'general_initial_popup_time', $request ) ? sanitize_text_field( $request['general_initial_popup_time'] ) : $defaults['general_initial_popup_time'];
		$request['general_subsequent_popup_time'] = array_key_exists( 'general_subsequent_popup_time', $request ) ? sanitize_text_field( $request['general_subsequent_popup_time'] ) : $defaults['general_subsequent_popup_time'];
		$request['general_notification_order']    = array_key_exists( 'general_notification_order', $request ) ? ( is_array( $request['general_notification_order'] ) ? $this->sanitize_order( $request['general_notification_order'], $defaults['general_notification_order'] ) : $this->sanitize_order( explode( ',', $request['general_notification_order'] ), $defaults['general_notification_order'] ) ) : $defaults['general_notification_order'];

		return $request;
	}

	/**
	 * Really magical function. Takes two arrays and sorts the second array by the order of the first
	 *
	 * @param [type] $setting: current setting
	 * @param [type] $default: default proofs
	 * @return void
	 */
	function sanitize_order( $setting, $default ) {
		// array_values ensures that non-associative arrays don't get messed up because of keys
		// array_intersect ensures that only the available proofs are used in the sequence
		return array_values( array_intersect( array_map( 'sanitize_text_field', array_values( array_unique( array_merge( $setting, array_diff( $default, $setting ) ) ) ) ), $default ) );
	}

	function wprtsp_get_styles( $vars ) {
		$styles = array(
			'popup_style' => $this->get_popup_style( $vars ),
			// 'popup_cta_style' => $this->get_popup_cta_style($vars),
		);
		$vars['styles'] = $styles;
		return $vars;
	}

	function get_popup_style( $settings ) {
		switch ( $settings['general_position'] ) {
			case 'bl':
				$position_css = 'bottom: -9999px; left:10px';
				break;
			case 'br':
				$position_css = 'bottom: -9999px; right:10px';
				break;
		}
		return apply_filters( 'wprtsp_popup_style', 'position:fixed; opacity:0;z-index:9999; margin: 0 0 0 0; box-shadow: 20px 20px 60px 0 rgba(36,35,40,.1); ' . $position_css . '; ', $settings );
	}

	function popup_box_style( $style, $settings ) {
		if ( isset( $settings['general_box_style'] ) && $settings['general_box_style'] == 'rounded' ) {
			$style .= 'border-radius: 5000px;';
		}
		return $style;
	}

	function save_ga_profile() {
			// https://dev.converticacommerce.com/woocommerce-sandbox/wp-admin/post.php?post=204&action=edit&origin_nonce=4c990a9bb6&gaapi_accountid=107074057&gaapi_webpropertyid=159840733&gaapi_webpropertyua=UA-107074057-1&gaapi_profileid=161102233&wpsppro-action=oauth&success=1
		// llog('hello');

		if ( isset( $_REQUEST['wpsppro-action'] ) && $_REQUEST['wpsppro-action'] == 'oauth' ) {
			wp_verify_nonce( $_REQUEST['origin_nonce'], 'wprtsp_gaapi' );
			if ( current_user_can( 'activate_plugins' )
			&& isset( $_REQUEST['success'] ) && $_REQUEST['success']
			&& isset( $_REQUEST['gaapi_accountid'] ) && $_REQUEST['gaapi_accountid']
			&& isset( $_REQUEST['gaapi_webpropertyid'] ) && $_REQUEST['gaapi_webpropertyid']
			&& isset( $_REQUEST['gaapi_webpropertyua'] ) && $_REQUEST['gaapi_webpropertyua']
			&& isset( $_REQUEST['gaapi_profileid'] ) && $_REQUEST['gaapi_profileid']
			) {
				//
				// wp_send_json(get_option('wpsppro_ga_profile'));
				$settings                     = array();
				$settings['ga_accountid']     = $_REQUEST['gaapi_accountid'];
				$settings['ga_webpropertyid'] = $_REQUEST['gaapi_webpropertyid'];
				$settings['ga_webpropertyua'] = $_REQUEST['gaapi_webpropertyua'];
				$settings['ga_profileid']     = $_REQUEST['gaapi_profileid'];
				update_option( 'wpsppro_ga_profile', $settings );
			} else {

			}
			wp_redirect( html_entity_decode( get_edit_post_link( $_REQUEST['post'] ) ), 302 );
			exit;
		}

		if ( isset( $_REQUEST['wpsppro-action'] ) && $_REQUEST['wpsppro-action'] == 'revoke' && isset( $_REQUEST['success'] ) && $_REQUEST['success'] == '1' ) {
			delete_option( 'wpsppro_ga_profile' );
		}
	}

}

class LiveSales {

	function get_names( $notification_id ) {
		return apply_filters( 'wprtsp_names', array( 'Kai Nakken', 'Cathy Gluck', 'Tiana Heier', 'Reiko Doucette', 'Shanel Nichols', 'Karan Sigler', 'Javier Roots', 'Camila Nowak', 'Refugia Blanc', 'Farrah Beehler', 'Kelly Lonergan', 'Jene Lechler', 'Awilda Hesler', 'Robbi Jauregui', 'Jaimie Wilkinson', 'Nanette Perras', 'Cinda Alley', 'Monet Player', 'Linn Bayless', 'Yukiko Cottman', 'Almeta Walkes', 'Janina Benesh', 'Shaun Camp', 'Mitch Ohern', 'Sam Carlon', 'Man Millard', 'Dania Coil', 'Eartha Hayhurst', 'Devin Fuston', 'Darcie Covin', 'Traci Mcsweeney', 'Lenore Bourassa', 'Nita Kaya', 'Tamra Biron', 'Melissa Garett', 'Myrta Magallanes', 'Magen Matinez', 'Gabriella Falls', 'Wayne Mcshane', 'Kristal Murnane', 'Allegra Plotner', 'Floyd Busbee', 'Danuta Lookabaugh', 'Nisha Correira', 'Lincoln Ewert', 'Shaunta Antrim', 'Augustine Rominger', 'Brady Sharpton', 'Jenice Tiedeman', 'Emanuel Hysmith', 'Sade Tefft', 'Kathe Macdowell', 'Tom Fordham', 'Elaina Moad', 'Denise Trudel', 'Rusty Mechem', 'Rosaura Tarin', 'Glayds Anger', 'Roma Hendrickson', 'Marsha Mathena', 'Shiloh Broadfoot', 'Casandra Pia', 'Cortez Bronstein', 'Bernadette Schwartz', 'Corinne Goudeau', 'Cornelia Kelsey', 'Joe Amore', 'Ahmad Blanca', 'Liana Chastain', 'Ester Shoop', 'Shayna Stoneman', 'Adrienne Faz', 'Carissa Cagle', 'Carita Meshell', 'Ria Reidy', 'Ka Hixson', 'Micki Hazen', 'Jeri Chaires', 'Gil Ledger', 'Kirk Square', 'Ericka Cedeno', 'Forest Mcquaid', 'Lauretta Keenan', 'Cleopatra Teeters', 'Gertha Rivas', 'Madie Iadarola', 'Elke Springfield', 'Marisol Patrick', 'Yoshie Studley', 'Cristopher Roddy', 'Buster Nyland', 'Vannesa Grable', 'Katharina Bustle', 'Monique Villescas', 'Maximo Lamb', 'Voncile Donahoe', 'Aiko Atkin', 'Tobie Mehta', 'Sixta Domina', 'Daniele Chacon' ), $notification_id );
	}

	function get_locations( $notification_id ) {
		return apply_filters( 'wprtsp_locations', array( 'San Bernardino, California', 'Birmingham, Alabama', 'San Diego, California', 'Fresno, California', 'Modesto, California', 'Toledo, Ohio', 'Modesto, California', 'Santa Ana, California', 'Mesa, Arizona', 'Dallas, Texas', 'New York, New York', 'Norfolk, Virginia', 'Phoenix, Arizona', 'Charlotte, North Carolina', 'Jersey City, New Jersey', 'Indianapolis, Indiana', 'Arlington, Texas', 'Raleigh, North Carolina', 'Bakersfield, California', 'Scottsdale, Arizona', 'Philadelphia, Pennsylvania', 'Tucson, Arizona', 'Garland, Texas', 'Fresno, California', 'Los Angeles, California', 'Lincoln, Nebraska', 'Detroit, Michigan', 'San Bernardino, California', 'Fort Worth, Texas', 'Chula Vista, California', 'Glendale, Arizona', 'Pittsburgh, Pennsylvania', 'Las Vegas, Nevada', 'Lexington-Fayette, Kentucky', 'Akron, Ohio', 'Orlando, Florida', 'Baton Rouge, Louisiana', 'Lincoln, Nebraska', 'Buffalo, New York', 'St. Paul, Minnesota', 'Norfolk, Virginia', 'San Antonio, Texas', 'St. Petersburg, Florida', 'Detroit, Michigan', 'Houston, Texas', 'St. Petersburg, Florida', 'Madison, Wisconsin', 'Lincoln, Nebraska', 'Montgomery, Alabama', 'Milwaukee, Wisconsin', 'Jersey City, New Jersey', 'New York, New York', 'Denver, Colorado', 'Birmingham, Alabama', 'Sacramento, California', 'Hialeah, Florida', 'Albuquerque, New Mexico', 'San Bernardino, California', 'Baton Rouge, Louisiana', 'Chula Vista, California', 'Cleveland, Ohio', 'Aurora, Colorado', 'New Orleans, Louisiana', 'Modesto, California', 'Washington, District of Columbia', 'Arlington, Texas', 'Pittsburgh, Pennsylvania', 'Montgomery, Alabama', 'San Antonio, Texas', 'Virginia Beach, Virginia', 'Laredo, Texas', 'Laredo, Texas', 'Phoenix, Arizona', 'Newark, New Jersey', 'Virginia Beach, Virginia', 'Lincoln, Nebraska', 'Baltimore, Maryland', 'Chandler, Arizona', 'Houston, Texas', 'Corpus Christi, Texas', 'Tampa, Florida', 'San Bernardino, California', 'Austin, Texas', 'Fort Wayne, Indiana', 'Oakland, California', 'Fresno, California', 'Miami, Florida', 'Huntington, New York', 'Milwaukee, Wisconsin', 'Jacksonville, Florida', 'Washington, District of Columbia', 'Laredo, Texas', 'Lubbock, Texas', 'Tucson, Arizona', 'Stockton, California', 'Albuquerque, New Mexico', 'Phoenix, Arizona', 'Durham, North Carolina', 'Arlington, Texas', 'Boise, Idaho' ), $notification_id );
	}

	function get_times( $notification_id ) {
		return apply_filters( 'wprtsp_times', array( 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97, 101, 103, 107, 109, 113, 127, 131, 137, 139, 149, 151, 157, 163, 167, 173, 179, 181, 191, 193, 197, 199, 211, 223, 227, 229, 233, 239, 241, 251, 257, 263, 269, 271, 277, 281, 283, 293, 307, 311, 313, 317, 331, 337, 347, 349, 353, 359, 367, 373, 379, 383, 389, 397, 401, 409, 419, 421, 431, 433, 439, 443, 449, 457, 461, 463, 467, 479, 487, 491, 499, 503, 509, 521, 523, 541 ), $notification_id );
	}

	static function get_instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->setup_actions();
		}
		return $instance;
	}

	function llog( $str ) {
		echo '<pre>';
		print_r( $str );
		echo '</pre>';
	}

	function setup_actions() {
		add_filter( 'wprtsp_register_proof', array( $this, 'register_proof' ) );
		add_action( 'wprtsp_add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_filter( 'wprtsp_sanitize', array( $this, 'sanitize' ) );
		
		add_filter( 'wprtsp_get_proof_data_conversions_WooCommerce', array( $this, 'get_wooc_conversions' ), 10, 2 ); // Get wooc comversions
		add_filter( 'wprtsp_tag_WooCommerce_name', array( $this, 'get_tag_WooCommerce_name' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_WooCommerce_firstname', array( $this, 'get_tag_WooCommerce_firstname' ) ); // replace woocommerce {firstname} tag
		add_filter( 'wprtsp_tag_WooCommerce_lastname', array( $this, 'get_tag_WooCommerce_lastname' ) ); // replace woocommerce {lastname} tag
		add_filter( 'wprtsp_tag_WooCommerce_location', array( $this, 'get_tag_WooCommerce_location' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_WooCommerce_action', array( $this, 'get_tag_WooCommerce_action' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_WooCommerce_product', array( $this, 'get_tag_WooCommerce_product' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_WooCommerce_time', array( $this, 'get_tag_WooCommerce_time' ) ); // replace woocommerce {name} tag

		add_filter( 'wprtsp_get_proof_data_conversions_Easy_Digital_Downloads', array( $this, 'get_edd_conversions' ), 10, 2 ); // Get edd comversions
		add_filter( 'wprtsp_tag_Easy_Digital_Downloads_name', array( $this, 'get_tag_Easy_Digital_Downloads_name' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Easy_Digital_Downloads_location', array( $this, 'get_tag_Easy_Digital_Downloads_location' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Easy_Digital_Downloads_action', array( $this, 'get_tag_Easy_Digital_Downloads_action' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Easy_Digital_Downloads_product', array( $this, 'get_tag_Easy_Digital_Downloads_product' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Easy_Digital_Downloads_time', array( $this, 'get_tag_Easy_Digital_Downloads_time' ) ); // replace woocommerce {name} tag

		add_filter( 'wprtsp_get_proof_data_conversions_Generated', array( $this, 'get_generated_conversions' ), 10, 2 ); // Get generated comversions
		add_filter( 'wprtsp_tag_Generated_name', array( $this, 'get_tag_Generated_name' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Generated_location', array( $this, 'get_tag_Generated_location' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Generated_action', array( $this, 'get_tag_Generated_action' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Generated_product', array( $this, 'get_tag_Generated_product' ) ); // replace woocommerce {name} tag
		add_filter( 'wprtsp_tag_Generated_time', array( $this, 'get_tag_Generated_time' ) ); // replace woocommerce {name} tag
	}

	function register_proof( $proofs ) {
		$proofs[] = get_class( $this );
		return $proofs;
	}

	function add_meta_boxes() {
		add_meta_box( 'social-proof-conversions', __( 'Live Sales', 'wprtsp' ), array( $this, 'conversions_meta_box' ), 'socialproof', 'normal' );
	}

	function defaults( $defaults = array() ) {

		$defaults['conversions_enable']                  = 1; // bool
		$defaults['conversions_enable_mob']              = 1; // bool
		$defaults['conversions_shop_type']               = class_exists( 'Easy_Digital_Downloads' ) ? 'Easy_Digital_Downloads' : ( class_exists( 'WooCommerce' ) ? 'WooCommerce' : 'Generated' );
		$defaults['conversion_template_line1']           = '{name} {location}';
		$defaults['conversion_template_line2']           = '{action} {product} {time}';
		$defaults['conversion_generated_action']         = 'subscribed to the';
		$defaults['conversion_generated_product']        = 'newsletter';
		$defaults['conversions_sound_notification']      = 0; // bool
		$defaults['conversions_sound_notification_file'] = 'salient.mp3'; // string
		$defaults['conversions_title_notification']      = 0; // bool
		$defaults['conversions_timeframe']               = 7;
		$defaults['conversions_allowed_timeframes']      = array( 1, 2, 3, 7, -1 );

		/*
		 Additional routines */
		// $defaults['conversions_generated_records'] = $this->generate_cpt_records(array('conversions_transaction' => 'subscribed to the newsletter', 'conversions_transaction_alt' => 'registered for the webinar'));

		return $defaults;
	}

	function wooc_get_orders() {
		$args            = array(
			'post_type'   => 'shop_order',
			'post_status' => 'wc-completed',
			'numberposts' => -1,
		);
		$customer_orders = get_posts( $args );

		if ( ! empty( $customer_orders ) && is_array( $customer_orders ) ) {
			return count( $customer_orders );
		}
		return 0;
	}

	function conversions_meta_box() {
		global $post;
		$wprtsp   = WPRTSP::get_instance();
		$defaults = $this->defaults();
		$settings = get_post_meta( $post->ID, '_socialproof', true );
		if ( ! $settings ) {
			$settings = $defaults;
		}

		$settings = $this->sanitize( $settings );

		$conversions_enable                  = $settings['conversions_enable'];
		$conversions_enable_mob              = $settings['conversions_enable_mob'];
		$conversions_title_notification      = $settings['conversions_title_notification'];
		$conversions_shop_type               = $settings['conversions_shop_type'];
		$conversion_generated_action         = $settings['conversion_generated_action'];
		$conversion_generated_product        = $settings['conversion_generated_product'];
		$conversion_template_line1           = $settings['conversion_template_line1'];
		$conversion_template_line2           = $settings['conversion_template_line2'];
		$conversions_sound_notification      = $settings['conversions_sound_notification'];
		$conversions_sound_notification_file = $settings['conversions_sound_notification_file'];

		$files = array_diff( scandir( $wprtsp->dir . 'assets/sounds' ), array( '.', '..' ) );

		$available_audio = '<select id="wprtsp_conversions_sound_notification_file" name="wprtsp[conversions_sound_notification_file]">';
		foreach ( $files as $file ) {

			$available_audio .= '<option ' . disabled( $conversions_sound_notification, false, false ) . ' value="' . $file . '" ' . selected( $conversions_sound_notification_file, $file, false ) . '>' . ucwords( str_replace( '-', ' ', explode( '.', $file )[0] ) ) . '</option>';
		}
		$available_audio .= '</select>';

		$sources = array();
		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			$sources[] = 'Easy_Digital_Downloads';
		}
		if ( class_exists( 'WooCommerce' ) ) {
			$sources[] = 'WooCommerce';
		}
		$sources[] = 'Generated';
		$sources   = apply_filters( 'wprtsp_shop_type', $sources );
		sort( $sources );
		$sources_html = '';
		foreach ( $sources as $key => $value ) {
			$sources_html .= '<option value="' . $value . '" ' . selected( $conversions_shop_type, $value, false ) . '>' . preg_replace( '/[^\da-z]/i', ' ', $value ) . '</option>';
		}
		$conversions_timeframe = $settings['conversions_timeframe'];
		$timeframes            = $defaults['conversions_allowed_timeframes'];
		$timeframes_html       = '';
		foreach ( $timeframes as $key ) {
			if ( $key == -1 ) {
				$timeframes_html .= '<option value="' . $key . '" ' . selected( $conversions_timeframe, $key, false ) . '>Lifetime</option>';
			} else {
				$timeframes_html .= '<option value="' . $key . '" ' . selected( $conversions_timeframe, $key, false ) . '>' . $key . ' days</option>';
			}
		}

		$timeframes_html = '<select id="wprtsp_conversions_timeframe" name="wprtsp[conversions_timeframe]">' . $timeframes_html . '</select>';
		?>
		<table id="tbl_conversions" class="wprtsp_tbl wprtsp_tbl_conversions">
			<tr>
				<td colspan="2">
					<h3>Show live sales to visitors</h3>
				</td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp[conversions_enable]">Enable on Desktop</label></td>
				<td>
					<input id="wprtsp[conversions_enable]" name="wprtsp[conversions_enable]" type="checkbox" value="1" <?php checked( 1, $conversions_enable, true ); ?>/>
				</td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp[conversions_enable_mob]">Enable on Mobile</label></td>
				<td>
					<input id="wprtsp[conversions_enable_mob]" name="wprtsp[conversions_enable_mob]" type="checkbox" value="1" <?php checked( 1, $conversions_enable_mob, true ); ?>/>
				</td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Depending on your operating system this will change the browser's tab title to highlight the browser-tab.</p></div></div><label for="wprtsp_conversions_title_notification">Enable Title Notification</label></td>
				<td><input id="wprtsp_conversions_title_notification" name="wprtsp[conversions_title_notification]" type="checkbox" value="1" <?php checked( 1, $conversions_title_notification, true ); ?>/></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Desperate method of grabbing visitor's attention, we agree. Use with extreme caution.</p></div></div><label for="wprtsp_conversions_sound_notification">Enable Sound Notification</label></td>
				<td><input id="wprtsp_conversions_sound_notification" name="wprtsp[conversions_sound_notification]" type="checkbox" value="1" <?php checked( 1, $conversions_sound_notification, true ); ?>/></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Subtle and some exaggerated sounds. Pick wisely. Make sure you preview it.</p></div></div><label for="wprtsp_conversions_sound_notification_file">Choose Audio</label></td>
				<td><?php echo $available_audio; ?><span id="conversions_audition_sound" class="dashicons-arrow-right dashicons"></span></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Select the source of conversions.</p></div></div><label for="wprtsp_conversions_shop_type">Source</label></td>
				<td><select id="wprtsp_conversions_shop_type" name="wprtsp[conversions_shop_type]">
						<?php echo $sources_html; ?>
					</select></td>
			</tr><?php
			do_action('wprtsp_shop_type_configuration'); // Hook your additional settings, defaults and sanitisation must be performed by you.
			?>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Select how recent the sales should be.</p></div></div><label for="wprtsp_conversions_timeframe">Show number of sales since</label></td>
				<td><?php echo $timeframes_html; ?></td>
			</tr>
			<tr>
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Construct the message. You can use <strong>name</strong> of the customer, <strong>location</strong> of the customer if available, <strong>action</strong> they took like <em>bought</em> or <em>subscribed</em> depending on the shop, <strong>product</strong> name and <strong>time</strong> of purchase.</p></div></div>Template</td>
				<td>
					<label>Line 1: <input type="text" value="<?php echo $conversion_template_line1; ?>" name="wprtsp[conversion_template_line1]" class="widefat" /></label><br />
					<label>Line 2: <input type="text" value="<?php echo $conversion_template_line2; ?>" name="wprtsp[conversion_template_line2]" class="widefat"/></label>
				</td>
			</tr>
			<tr class="generated_transactions">
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Action visitors took for auto generated records.</p></div></div><label for="wprtsp_conversion_generated_action">Action for Generated records</label></td>
				<td><input id="wprtsp_conversion_generated_action" <?php if ( $conversions_shop_type != 'Generated' ) { echo 'readonly'; } ?> name="wprtsp[conversion_generated_action]" type="text" class="widefat" value="<?php echo $conversion_generated_action; ?>" /></td>
			</tr>
			<tr class="generated_transactions">
				<td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Product visitors bought for auto generated records.</p></div></div><label for="wprtsp_conversion_generated_product">Product for Generated records</label></td>
				<td><input id="wprtsp_conversion_generated_product" <?php if ( $conversions_shop_type != 'Generated' ) { echo 'readonly';} ?> name="wprtsp[conversion_generated_product]" type="text" class="widefat" value="<?php echo $conversion_generated_product; ?>" /></td>
			</tr>
		</table>
		<script type="text/javascript">
		jQuery( document ).ready(function() {
			jQuery('#wprtsp_conversions_shop_type').on('change',  function() {
				if(jQuery('#wprtsp_conversions_shop_type').val() == 'Generated' ) {
					//jQuery('#wprtsp_conversions_transaction').removeAttr('readonly');
					console.log('Generated');
					jQuery('#wprtsp_conversion_generated_action').removeAttr('readonly');
					jQuery('#wprtsp_conversion_generated_product').removeAttr('readonly');
				}
				else {
					console.log(jQuery('#wprtsp_conversions_shop_type').val());
					//jQuery('#wprtsp_conversions_transaction').attr('readonly', 'true');
					jQuery('#wprtsp_conversion_generated_action').attr('readonly', 'true');
					jQuery('#wprtsp_conversion_generated_product').attr('readonly', 'true');
				}
			});
			jQuery('#wprtsp_conversions_sound_notification').change(function() {
				if(jQuery('#wprtsp_conversions_sound_notification').prop('checked')) {
					jQuery('#wprtsp_conversions_sound_notification_file option').each(function(){
						if(jQuery(this).attr('disabled')) {
							jQuery(this).removeAttr('disabled');
						}
					});
				}
				else {
					jQuery('#wprtsp_conversions_sound_notification_file option').each(function(){
						if(! jQuery(this).attr('selected')) {
							jQuery(this).attr('disabled','true');
						}
					});
				}
			});
			jQuery('#conversions_audition_sound').click(function(){
				wprtsp_conversions_sound_preview = jQuery('#wprtsp_conversions_sound_preview').length ? jQuery('#wprtsp_conversions_sound_preview') : jQuery('<audio/>', {
					id: 'wprtsp_conversions_sound_preview'
				}).appendTo('body');
				if( ! jQuery('#wprtsp_conversions_sound_notification').prop('checked')) {
					alert('Cannot play sound if Sound Notification is unchecked.');
					return;
				}
				
				jQuery('#wprtsp_conversions_sound_preview').attr('src','<?php echo $wprtsp->uri . 'assets/sounds/'; ?>' + jQuery('#wprtsp_conversions_sound_notification_file').val());
				document.getElementById("wprtsp_conversions_sound_preview").play(); 
			});
		});
		</script>
		<?php
	}

	function sanitize( $request ) {
		$defaults = $this->defaults();

		$request['conversions_enable']                  = array_key_exists( 'conversions_enable', $request ) && $request['conversions_enable'] ? 1 : 0;
		$request['conversions_enable_mob']              = array_key_exists( 'conversions_enable_mob', $request ) && $request['conversions_enable_mob'] ? 1 : 0;
		$request['conversions_title_notification']      = array_key_exists( 'conversions_title_notification', $request ) && $request['conversions_title_notification'] ? 1 : 0;
		$request['conversions_shop_type']               = array_key_exists( 'conversions_shop_type', $request ) ? sanitize_text_field( $request['conversions_shop_type'] ) : $defaults['general_post_ids'];
		$request['conversion_generated_action']         = array_key_exists( 'conversion_generated_action', $request ) ? sanitize_text_field( $request['conversion_generated_action'] ) : $defaults['conversion_generated_action'];
		$request['conversion_generated_product']        = array_key_exists( 'conversion_generated_product', $request ) ? sanitize_text_field( $request['conversion_generated_product'] ) : $defaults['conversion_generated_product'];
		$request['conversion_template_line1']           = array_key_exists( 'conversion_template_line1', $request ) ? sanitize_text_field( $request['conversion_template_line1'] ) : $defaults['conversion_template_line1'];
		$request['conversion_template_line2']           = array_key_exists( 'conversion_template_line2', $request ) ? sanitize_text_field( $request['conversion_template_line2'] ) : $defaults['conversion_template_line2'];
		$request['conversions_sound_notification']      = array_key_exists( 'conversions_sound_notification', $request ) && $request['conversions_sound_notification'] ? 1 : 0;
		$request['conversions_sound_notification_file'] = array_key_exists( 'conversions_sound_notification_file', $request ) ? sanitize_text_field( $request['conversions_sound_notification_file'] ) : $defaults['conversions_sound_notification_file'];
		$request['conversions_timeframe']               = array_key_exists( 'conversions_timeframe', $request ) ? sanitize_text_field( $request['conversions_timeframe'] ) : $defaults['conversions_timeframe'];

		return $request;
	}

	/************************************** */

	function get_wooc_conversions( $settings ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}
		$value  = $settings['conversions_timeframe'];
		$period = ( $value >= 0 ) ? '>' . ( time() - ( $value * DAY_IN_SECONDS ) ) : false;

		$args = array(
			'limit'   => 100,
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'ids',
			'status'  => 'completed',
		);

		if ( $period ) {
			$args['date_created'] = $period;
		}

		$query  = new WC_Order_Query( $args );
		$orders = $query->get_orders();

		$customers = array();
		$messages  = array();
		foreach ( $orders as $purchase ) {
			$order      = new WC_Order( $purchase );
			$order_data = $order->get_data();

			$user      = $order->get_user();
			$name      = '';
			$firstname = '';
			$lastname  = '';
			if ( ! empty( $user ) ) {

				if ( $user->user_firstname && strtolower( $user->user_firstname ) != 'guest' ) {
					$name     .= $user->user_firstname;
					$firstname = $user->user_firstname;
				}
				if ( $user->user_lastname && strtolower( $user->user_lastname ) != 'guest' ) {
					$name    .= ' ' . $user->user_lastname;
					$lastname = $user->user_lastname;
				}
				if ( empty( trim( $name ) ) ) {
					$name = __( 'A visitor', 'wprtsp' );
				}
			} else {
				$customers[ $purchase ]['name'] = __( 'A visitor', 'wprtsp' );
			}
			$item = $order->get_items();
			if ( empty( $item ) ) {
				continue;
			}
			$item = array_shift( $item );
			if ( is_null( $item ) ) {
				continue;
			}
			$customers[ $purchase ]['product']      = $item->get_name();
			$customers[ $purchase ]['product_link'] = get_permalink( $item->get_product_id() );
			$time                                   = new WC_DateTime( $order->get_date_completed() );
			$customers[ $purchase ]['time']         = human_time_diff( $time->getTimestamp() );
			$messages[]                             = array(
				'link'      => $customers[ $purchase ]['product_link'],
				'name'      => $name ? $name : __( 'A guest', 'wprtsp' ),
				'firstname' => $firstname ? $firstname : __( 'A guest', 'wprtsp' ),
				'lastname'  => $lastname ? $lastname : '',
				'location'  => implode( ', ', array_filter( array( $order_data['billing']['city'], $order_data['billing']['country'] ) ) ),
				'action'    => __( 'purchased', 'wprtsp' ),
				'product'   => $customers[ $purchase ]['product'],
				'time'      => __( $customers[ $purchase ]['time'] . ' ago', 'wprtsp' ),
			);
		}
		$messages = $this->translate_wooc_placeholders( $messages, $settings );
		return $messages;
	}

	function translate_wooc_placeholders( $records, $settings ) {
		$template1 = $settings['conversion_template_line1'];
		$template2 = $settings['conversion_template_line2'];
		$messages  = array();

		$i = 0;
		foreach ( $records as $key => $value ) {
			$messages[ $i ]['line1'] = '<span class="wprtsp_line1">' . preg_replace_callback(
				'/{.+?}/',
				function( $matches ) use ( $value, $settings ) {
					$key = preg_replace( '/[^\da-z]/i', '', $matches[0] );
					return apply_filters( 'wprtsp_tag_' . $settings['conversions_shop_type'] . '_' . $key, $value[ $key ] );
				},
				$template1
			) . '</span>';
			$messages[ $i ]['line2'] = '<span class="wprtsp_line2">' . preg_replace_callback(
				'/{.+?}/',
				function( $matches ) use ( $value, $settings ) {
					$key = preg_replace( '/[^\da-z]/i', '', $matches[0] );
					return apply_filters( 'wprtsp_tag_' . $settings['conversions_shop_type'] . '_' . $key, $value[ $key ] );
				},
				$template2
			) . '</span>';
			$messages[ $i ]['link']  = $value['link'];
			$i++;
		}
		return $messages;
	}

	function get_tag_WooCommerce_name( $name ) {
		return "<span class=\"wprtsp_name\">$name</span>";
	}

	function get_tag_WooCommerce_firstname( $name ) {
		return "<span class=\"wprtsp_name\">$name</span>";
	}

	function get_tag_WooCommerce_lastname( $name ) {
		return "<span class=\"wprtsp_name\">$name</span>";
	}

	function get_tag_WooCommerce_action( $action ) {
		return "<span class=\"wprtsp_action\">$action</span>";
	}

	function get_tag_WooCommerce_product( $product ) {
		return "<span class=\"wprtsp_product\">$product</span>";
	}

	function get_tag_WooCommerce_time( $time ) {
		return "<span class=\"wprtsp_time\">$time</span>";
	}

	function get_tag_WooCommerce_location( $location ) {
		return empty( $location ) ? '' : " <span class=\"wprtsp_location\">from $location </span>";
	}

	/********************************************** */

	function get_edd_conversions( $settings ) {
		if ( ! class_exists( 'Easy_Digital_Downloads' ) ) {
			return array();
		}
		$args = array(
			'numberposts'      => 100,
			'post_status'      => 'publish',
			'post_type'        => 'edd_payment',
			'suppress_filters' => true,
		);

		$value = $settings['conversions_timeframe'];

		$period = ( $value >= 0 ) ? ( time() - ( $value * DAY_IN_SECONDS ) ) : false;

		if ( $period ) {
			$args['date_query'] = array(
				'after' => date( 'c', $period ),
			);
		}

		$payments = get_posts( $args );
		$records  = array();
		$messages = array();
		if ( $payments ) {
			foreach ( $payments as $payment_post ) {
				setup_postdata( $payment_post );
				$payment = new EDD_Payment( $payment_post->ID );

				if ( empty( $payment->ID ) ) {
					continue;
				}

				$payment_time = human_time_diff( strtotime( $payment->date ), current_time( 'timestamp' ) );
				$customer     = new EDD_Customer( $payment->customer_id );
				$downloads    = $payment->cart_details;
				$downloads    = array_slice( $downloads, 0, 1, true );
				$name         = '';
				$firstname    = '';
				$lastname     = '';
				$address      = array_key_exists( 'address', $payment->user_info ) ? $payment->user_info['address'] : false;
				if ( $address ) {
					$address = $payment->user_info['address']['city'] . ' ' . $payment->user_info['address']['country'];
					if ( empty( trim( $address ) ) ) {
						$address = false;
					}
				}
				if ( array_key_exists( 'first_name', $payment->user_info ) && ! empty( $payment->user_info['first_name'] ) ) {
					$name      = $payment->user_info['first_name'];
					$firstname = $name;
				}
				if ( array_key_exists( 'last_name', $payment->user_info ) && ! empty( $payment->user_info['last_name'] ) ) {
					$name    .= ' ' . $payment->user_info['last_name'];
					$lastname = $payment->user_info['last_name'];
				}
				if ( empty( trim( $name ) ) ) {
					$name = 'Someone';
				}
				$records[ $payment_post->ID ] = array(
					'product_link' => get_permalink( $downloads[0]['id'] ),
					'first_name'   => $payment->user_info['first_name'],
					'last_name'    => $payment->user_info['last_name'],
					'transaction'  => __( 'purchased', 'wprtsp' ),
					'product'      => $downloads[0]['name'],
					'time'         => $payment_time,
				);
				$messages[]                   = array(
					'link'      => get_permalink( $downloads[0]['id'] ),
					'name'      => $name,
					'firstname' => $firstname ? $firstname : __( 'A visitor', 'wprtsp' ),
					'lastname'  => $lastname ? $lastname : '',
					'location'  => $address,
					'action'    => __( 'purchased', 'wprtsp' ),
					'product'   => $downloads[0]['name'],
					'time'      => __( $payment_time . ' ago', 'wprtsp' ),
				);
			}
			wp_reset_postdata();
		}
		$messages = $this->translate_edd_placeholders( $messages, $settings );
		return $messages;
	}

	function translate_edd_placeholders( $records, $settings ) {
		$template1 = $settings['conversion_template_line1'];
		$template2 = $settings['conversion_template_line2'];
		$messages  = array();

		$i = 0;
		foreach ( $records as $key => $value ) {
			$messages[ $i ]['line1'] = '<span class="wprtsp_line1">' . preg_replace_callback(
				'/{.+?}/',
				function( $matches ) use ( $value, $settings ) {
					$key = preg_replace( '/[^\da-z]/i', '', $matches[0] );
					return apply_filters( 'wprtsp_tag_' . $settings['conversions_shop_type'] . '_' . $key, $value[ $key ] );
				},
				$template1
			) . '</span>';
			$messages[ $i ]['line2'] = '<span class="wprtsp_line2">' . preg_replace_callback(
				'/{.+?}/',
				function( $matches ) use ( $value, $settings ) {
					$key = preg_replace( '/[^\da-z]/i', '', $matches[0] );
					return apply_filters( 'wprtsp_tag_' . $settings['conversions_shop_type'] . '_' . $key, $value[ $key ] );
				},
				$template2
			) . '</span>';
			$messages[ $i ]['link']  = $value['link'];
			$i++;
		}
		return $messages;
	}

	function get_tag_Easy_Digital_Downloads_name( $name ) {
		return "<span class=\"wprtsp_name\">$name</span>";
	}

	function get_tag_Easy_Digital_Downloads_action( $action ) {
		return "<span class=\"wprtsp_action\">$action</span>";
	}

	function get_tag_Easy_Digital_Downloads_product( $product ) {
		return "<span class=\"wprtsp_product\">$product</span>";
	}

	function get_tag_Easy_Digital_Downloads_time( $time ) {
		return "<span class=\"wprtsp_time\">$time</span>";
	}

	function get_tag_Easy_Digital_Downloads_location( $location ) {
		return empty( $location ) ? '' : " <span class=\"wprtsp_location\">from $location </span>";
	}

	/**************************************************** */

	function shuffle_assoc( $list ) {
		if ( ! is_array( $list ) ) {
			return $list;
		}

		$keys = array_keys( $list );
		shuffle( $keys );
		$random = array();
		foreach ( $keys as $key ) {
			$random[ $key ] = $list[ $key ];
		}
		return $random;
	}

	function get_generated_conversions( $settings ) {
		// $transaction = $settings['conversions_transaction'];
		// $transaction_alt = $settings['conversions_transaction_alt'];

		$link      = get_site_url();
		$indexes   = array();
		$names     = $this->get_names( $settings['notification_id'] );
		$locations = $this->get_locations( $settings['notification_id'] );
		$times     = $this->get_times( $settings['notification_id'] );
		reset( $names );
		reset( $locations );
		reset( $times );

		for ( $i = 0; $i < count( $names ); $i++ ) {

			$indexes[ $i ] = array(
				'link'     => $link,
				'name'     => current( $names ),
				'location' => current( $locations ),
				'action'   => $settings['conversion_generated_action'],
				'product'  => $settings['conversion_generated_product'],

			);
			next( $locations );
			next( $names );
		}

		shuffle( $indexes );

		reset( $times );
		$date = date( 'Y-m-d H:i:s' );
		for ( $i = 0; $i < count( $indexes ); $i++ ) {
			$time                  = strtotime( $date );
			$time                  = $time - ( current( $times ) * 60 );
			$indexes[ $i ]['time'] = __( human_time_diff( $time ) . ' ago', 'wprtsp' );
			next( $times );
		}

		return $this->translate_generated_placeholders( $indexes, $settings );
	}

	function translate_generated_placeholders( $records, $settings ) {
		$template1 = $settings['conversion_template_line1'];
		$template2 = $settings['conversion_template_line2'];
		$messages  = array();

		$i = 0;
		foreach ( $records as $key => $value ) {
			$messages[ $i ]['line1'] = '<span class="wprtsp_line1">' . preg_replace_callback(
				'/{.+?}/',
				function( $matches ) use ( $value, $settings ) {
					$key = preg_replace( '/[^\da-z]/i', '', $matches[0] );
					return apply_filters( 'wprtsp_tag_' . $settings['conversions_shop_type'] . '_' . $key, $value[ $key ] );
				},
				$template1
			) . '</span>';
			$messages[ $i ]['line2'] = '<span class="wprtsp_line2">' . preg_replace_callback(
				'/{.+?}/',
				function( $matches ) use ( $value, $settings ) {
					$key = preg_replace( '/[^\da-z]/i', '', $matches[0] );
					return apply_filters( 'wprtsp_tag_' . $settings['conversions_shop_type'] . '_' . $key, $value[ $key ] );
				},
				$template2
			) . '</span>';
			$messages[ $i ]['link']  = $value['link'];
			$i++;
		}
		return $messages;
	}

	function get_tag_Generated_name( $name ) {
		return "<span class=\"wprtsp_name\">$name</span>";
	}

	function get_tag_Generated_action( $action ) {
		return "<span class=\"wprtsp_action\">$action</span>";
	}

	function get_tag_Generated_product( $product ) {
		return "<span class=\"wprtsp_product\">$product</span>";
	}

	function get_tag_Generated_time( $time ) {
		return "<span class=\"wprtsp_time\">$time</span>";
	}

	function get_tag_Generated_location( $location ) {
		return empty( $location ) ? '' : " <span class=\"wprtsp_location\">from $location </span>";
	}

}

class WPRTSPUPGRADES {
	static function get_instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->setup_actions();
		}
		return $instance;
	}

	function setup_actions() {
		add_action( 'wprtsp_add_meta_boxes', array( $this, 'add_meta_boxes' ) );
	}

	function add_meta_boxes() {
		$wprtsp = WPRTSP::get_instance();
		if ( ! $wprtsp->is_valid_pro() ) {
			add_meta_box( 'social-proof-upgrades', __( 'WP Real-Time Social-Proof Pro', 'wprtsp' ), array( $this, 'meta_box' ), 'socialproof', 'normal' );
		}
	}

	function meta_box() {
		?>
	   <h3>Upgrade to WP Real-Time Social-Proof Pro today</h3>
	   <div>
	   <ul>
	   <li>Boost Conversions</li>
	   <li>Reduce Cost of Customer-Acquisition</li>
	   <li>Build Trust and Brand-Value</li>
	   <li>Connect with Google Analytics to get live, real-time users visiting your site</li>
	   <li>Show custom discounts and calls-to-action</li>
	   </ul>

	   <a class="button-primary" href="https://wp-social-proof.com/?p=262" target="_blank">Upgrade to Pro Version!</a>
	   <iframe style="display: block;margin: 1.618em 0" width="560" height="315" src="https://www.youtube.com/embed/crxvwE3YWQo" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>       </div>
		<?php
	}
}

function wprtspupgrades() {
	return WPRTSPUPGRADES::get_instance();
}

function wprtspgeneral() {
	return WPRTSPGENERAL::get_instance();
}

function wprtspconversion() {
	return LiveSales::get_instance();
}

wprtspgeneral();

wprtspconversion();

wprtspupgrades();

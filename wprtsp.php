<?php
/**
 * Plugin Name: WP Real-Time Social-Proof
 * Description: Animated, live, real-time social-proof Pop-ups for your WordPress website to boost sales and signups.
 * Version:     2.1.8
 * Plugin URI:  https://wordpress.org/plugins/wp-real-time-social-proof/
 * Author:      Shivanand Sharma
 * Author URI:  https://www.wp-social-proof.com
 * Text Domain: wprtsp
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Tags: social proof, conversion, ctr, ecommerce, marketing, popup, woocommerce, easy digital downloads, newsletter, optin, signup, sales triggers
 */

/*
Copyright 2019 Shivanand Sharma

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

define( 'WPRTSP_EDD_SL_URL', 'https://wp-social-proof.com' );
define( 'WPRTSPAPIEP', 'https://wp-social-proof.com/gaapi/' );
define( 'WPRTSPFILE', __FILE__ );

class WPRTSP {

	public $style_box, $wprtsp_notification_style, $wprtsp_text_style, $wprtsp_action_style, $sound_notification, $sound_notification_markup;
	public $dir = '';
	public $uri = '';
	public $settings;

	static function get_instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
			$instance->setup();
			$instance->setup_actions();
			$instance->includes();
		}
		return $instance;
	}

	function setup() {
		$this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );
		$this->uri = trailingslashit( plugin_dir_url( __FILE__ ) );
		$this->set_version();

	}

	function includes() {
		require_once $this->dir . 'inc/meta.php';

		if ( file_exists( $this->dir . 'wprtsppro/pro/pro.php' ) ) {
			include_once $this->dir . 'wprtsppro/pro/pro.php';
		}
	}

	function setup_actions() {
		add_action( 'plugins_loaded', array( $this, 'set_version' ) ); // setup plugin information so that it's easier to get
		add_action( 'admin_init', array( $this, 'plugin_data' ) ); // setup plugin information so that it's easier to get
		add_action( 'init', array( $this, 'register_post_types' ) ); // register our CPT
		add_action( 'admin_notices', array( $this, 'admin_notice' ), 99999 );
		add_action( 'admin_init', array( $this, 'needs_upgrade' ) ); // upgrade cpt data routine
		add_action( 'admin_init', array( $this, 'do_upgrade' ) ); // upgrade cpt data routine
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) ); // Add important links to plugins list (left-side)
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 2 ); // Add important links to plugins list (right-side)
		add_action( 'admin_head', array( $this, 'admin_head' ) ); // some quick fixes to admin styles especially CPT menu icon
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_styles' ) ); // Style our CPT
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) ); // Add the front end script
		add_action( 'add_meta_boxes_socialproof', array( $this, 'remove_metaboxes' ), 5 ); // hook early and remove all metaboxes
		add_action( 'add_meta_boxes_socialproof', array( $this, 'add_meta_boxes' ) ); // add metaboxes
		add_action( 'save_post_socialproof', array( $this, 'save_meta_box_data' ), 10, 3 ); // save metabox data
		

		add_action( 'wprtsp_add_meta_boxes', array( $this, 'add_extra_meta_boxes' ) ); // add extra metaboxes
		
		add_filter( 'wprtsp_enabled', array( $this, 'wprtsp_enabled' ), 10, 2 ); // check if proof is enabled

		add_filter( 'wprtsp_vars', array( $this, 'wprtsp_detect_mobile' ) ); // check if mobile vars need to be enqueued in js
		add_action( 'admin_menu', array( $this, 'settings_menu' ) );
		add_action( 'admin_menu', array( $this, 'firstrun_menu' ) );

		add_filter( 'wprtsp_vars', array( $this, 'wprtsp_add_vars' ) ); // enqueue any additional js data

		add_action( 'in_plugin_update_message-' . basename( __DIR__ ) . '/' . basename( __FILE__ ), array( $this, 'plugin_update_message' ), 10, 2 );
	}

	function add_extra_meta_boxes(){
		add_meta_box( 'social-proof-rss', __( 'Tips &amp; Tricks', 'wprtsp' ), array( $this, 'meta_box_tips' ), 'socialproof', 'side' );
	}

	function meta_box_tips(){
		echo 22;
	}

	function is_pro() {
		if ( file_exists( $this->dir . 'wprtsppro/pro/pro.php' ) ) {
			return true;
		}
	}

	function is_valid_pro() {
		if ( $this->is_pro() ) {
			$settings = get_option( 'wprtsp' );
			if ( ! $settings ) {
				return;
			}
			if ( empty( $settings['license_key'] ) ) {
				return;
			}
			return $this->get_pro_status();
		}
	}

	function get_pro_status() {
		$status = get_transient( 'wprtsp_license_status' );
		if ( ! $status ) {
			$settings = get_option( 'wprtsp' );
			if ( ! $settings ) {
				return;
			}
			if ( empty( $settings['license_key'] ) ) {
				return;
			}

			$key = $settings['license_key'];
			if ( empty( $key ) ) {
				return;
			}
			$url         = trailingslashit( WPRTSP_EDD_SL_URL ) . '?edd_action=check_license&item_id=262&license=' . $key . '&url=' . site_url();
			$response    = wp_safe_remote_request( $url );
			$headers     = wp_remote_retrieve_headers( $response );
			$status_code = wp_remote_retrieve_response_code( $response );
			if ( 200 != $status_code ) {
				return;
			}
			if ( is_wp_error( $response ) ) {
				return;
			}
			$body   = wp_remote_retrieve_body( $response );
			$status = json_decode( $body, true );
			if ( is_null( $status ) ) {
				return;
			}
			if ( $status['success'] != true ) {
				return;
			}
			if ( ! empty( $status['success'] ) && $status['success'] == true ) {
				$this->set_validation();
				return true;
			}
			return;
		} else {
			return $status == 'valid';
		}
	}

	function set_validation() {
		set_transient( 'wprtsp_license_status', 'valid', 24 * HOUR_IN_SECONDS );
		return true;
	}

	function set_version() {
		$version = get_file_data( WPRTSPFILE, array( 'wprtspversion' => 'Version' ) );
		if ( isset( $version['wprtspversion'] ) ) {
			$this->version = $version['wprtspversion'];
		} else {
			$this->version = $version['wprtspversion'];
		}
		// $this->llog($this->version);
	}

	function plugin_data() {
		$plugin_data = get_plugin_data( WPRTSPFILE );
		return $plugin_data;
	}

	function register_post_types() {

		$labels   = array(
			'name'                  => __( 'Social Proof', 'wprtsp' ),
			'singular_name'         => __( 'singular_name', 'wprtsp' ),
			'menu_name'             => __( 'Social Proof', 'wprtsp' ),
			'name_admin_bar'        => __( 'name_admin_bar', 'wprtsp' ),
			'add_new'               => __( 'Add New Social Proof', 'wprtsp' ),
			'add_new_item'          => __( 'Add New Social Proof', 'wprtsp' ),
			'edit_item'             => __( 'Edit Social Proof', 'wprtsp' ),
			'new_item'              => __( 'new_item', 'wprtsp' ),
			'view_item'             => __( 'view_item', 'wprtsp' ),
			'view_items'            => __( 'view_items', 'wprtsp' ),
			'search_items'          => __( 'search_items', 'wprtsp' ),
			'not_found'             => __( 'No Social Proofs found', 'wprtsp' ),
			'not_found_in_trash'    => __( 'No Social Proofs in Trash', 'wprtsp' ),
			'all_items'             => __( 'All Social Proofs', 'wprtsp' ),
			'featured_image'        => __( 'featured_image', 'wprtsp' ),
			'set_featured_image'    => __( 'set_featured_image', 'wprtsp' ),
			'remove_featured_image' => __( 'remove_featured_image', 'wprtsp' ),
			'use_featured_image'    => __( 'use_featured_image', 'wprtsp' ),
			'insert_into_item'      => __( 'insert_into_item', 'wprtsp' ),
			'uploaded_to_this_item' => __( 'uploaded_to_this_item', 'wprtsp' ),
			'filter_items_list'     => __( 'filter_items_list', 'wprtsp' ),
			'items_list_navigation' => __( 'items_list_navigation', 'wprtsp' ),
			'items_list'            => __( 'items_list', 'wprtsp' ),
		);
		$cpt_args = array(
			'description'         => 'Social Proof',
			'public'              => false,
			'show_ui'             => true,
			'rewrite'             => false,
			'publicly_queryable'  => false,
			'query_var'           => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'show_in_admin_bar'   => true,
			'show_in_rest'        => false,
			'menu_position'       => null,
			// 'menu_icon'           => 'dashicons-format-chat',
			'menu_icon'           => $this->uri . 'assets/menu-icon.svg',
			'can_export'          => true,
			'delete_with_user'    => false,
			'hierarchical'        => false,
			'has_archive'         => false,
			'labels'              => $labels,
			'template_lock'       => true,

			// What features the post type supports.
			'supports'            => array(
				'title',
				// 'editor',
				// 'thumbnail',
				// Theme/Plugin feature support.
				// 'custom-background', // Custom Background Extended
				// 'custom-header',     // Custom Header Extended
				// 'wpcom-markdown',    // Jetpack Markdown
			),
		);

		register_post_type( 'socialproof', apply_filters( 'socialproof_post_type_args', $cpt_args ) );
	}

	function admin_notice() {

		$upgrade_required = get_option( 'wprtsp_upgrade_required' );
		if ( $upgrade_required ) {
			?>
			<div class="notice notice-error"><p><strong>Please <a href="<?php echo get_admin_url( null, 'edit.php?post_type=socialproof' ); ?>">visit all your social proofs</a> and save them again else they may not work with this version.</strong></p></div>
			<?php
		}
	}

	function needs_upgrade() {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		$old_version = get_option( 'wprtsp_firstrun' );

		if ( ! $old_version || $old_version != $this->version ) { // show the first run dashboard before attempting to upgrade
			if ( empty( $old_version ) ) {
				$this->firstrun_redirect();
			} else {
				$this->firstrun_redirect( $old_version );
			}
		}

		$socialproofs = array();
		$query        = new WP_Query(
			array(
				'post_type'   => 'socialproof',
				'post_status' => 'any',
			)
		);

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id   = get_the_ID();
			$post_meta = get_post_meta( $post_id, '_socialproof', true );
			if ( ! isset( $post_meta['general_cpt_version'] ) || ( isset( $post_meta['general_cpt_version'] ) && version_compare( $post_meta['general_cpt_version'], $this->version, '<' ) ) ) {
				$socialproofs[] = $post_id;
			}
		}

		wp_reset_query();

		if ( ! empty( $socialproofs ) ) {
			update_option( 'wprtsp_upgrade_required', true );
		}
	}

	function firstrun_redirect( $upgrade = false ) {

		if ( $upgrade ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => 'wprtsp-firstrun',
						'upgrade' => $upgrade,
					),
					admin_url( 'index.php' )
				)
			);
			update_option( 'wprtsp_firstrun', $this->version );
			exit;
		} else {
			wp_safe_redirect( add_query_arg( array( 'page' => 'wprtsp-firstrun' ), admin_url( 'index.php' ) ) );
			update_option( 'wprtsp_firstrun', $this->version );
			exit;
		}
	}

	function wprtsp_dashboard() {

		$display_version = $this->version;
		$plugin_data     = $this->plugin_data();
		// $this->llog($_REQUEST);
		if ( isset( $_REQUEST['upgrade'] ) ) {
			$process = 'updating';
		} else {
			$process = 'installing';
		}
		?>
		<div class="wrap about-wrap">
		<h1><?php printf( esc_html__( 'Welcome to %1$s %2$s', 'wprtsp' ), $plugin_data['Name'], $display_version ); ?></h1>
		<div class="about-text"><?php printf( esc_html__( 'Thank you for %1$s %2$s! Version %3$s is ready to rock!', 'wprtsp' ), $process, $plugin_data['Name'], $display_version ); ?></div>
		<div class="wp-badge wprtsp-badge"><?php printf( esc_html__( 'Version %s', 'wprtsp' ), $display_version ); ?></div>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wprtsp-about' ), 'index.php' ) ) ); ?>">
				<?php esc_html_e( 'What&#8217;s New', 'bbpress' ); ?>
			</a><!--<a class="nav-tab" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wprtsp-credits' ), 'index.php' ) ) ); ?>">
				<?php esc_html_e( 'Credits', 'wprtsp' ); ?>
			</a>-->
		</h2>

		<div class="changelog">
			<h3><?php _e( 'First things first →', 'wprtsp' ); ?></h3>

			<div class="feature-section col one-col has-1-columns">
				<div class="last-feature feature column is-vertically-aligned-top">
					<p><?php _e( 'This is a new generation of social-proof. Many kinds of proof, many ways to grab visitor attention, settings on steriods, control over desktop and mobile&hellip; aaand so much more&hellip;!', 'wprtsp' ); ?></p>
				</div>
			</div>

			<div class="feature-section col two-col has-2-columns">
				<div class="last-feature feature column is-vertically-aligned-top">
					<h4><?php _e( 'Now you have deep Integration with Google Analytics', 'wprtsp' ); ?></h4>
					<p><?php _e( 'Track goals, conversions and even fetch live, real-time visitor count&hellip; like a pro! Ain\'t that cool?', 'wprtsp' ); ?></p>
				</div>

				<div class="feature column is-vertically-aligned-top">
					<h4><?php _e( '&hellip;and Powerful yet Friendly Options. Solid!', 'wprtsp' ); ?></h4>
					<p><?php _e( 'Impressively effective in turning fense-sitting visitors into paying customers. — because <strong>those are the easiest to convert</strong> and <strong><em>that\'s where your money is best spent</em></strong>.  Powerful, robust settings to tweak it to the tee without any compromise. Inline tooltips to make things easy and a breeze to setup.', 'wprtsp' ); ?></p>
				</div>
			</div>
		</div>

		<div class="changelog">
			<h3><?php esc_html_e( 'Upgrader… Proper!', 'wprtsp' ); ?></h3>

			<div class="feature-section col one-col has-1-columns">
				<div class="last-feature feature column is-vertically-aligned-top">
					<p><?php esc_html_e( 'We agree it\'s been a challenge to upgrade ' . $plugin_data['Name'] . ' seamlessly. But with the new experience ' . $plugin_data['Name'] . ' upgrades all your social-proofs seamlessly. Took a little while to get things right but we did it!', 'wprtsp' ); ?></p>
				</div>
			</div>

			<div class="feature-section col three-col has-3-columns">
				<div class="feature column is-vertically-aligned-top">
					<h4><?php esc_html_e( 'Power of Addons', 'wprtsp' ); ?></h4>
					<p><?php _e( $plugin_data['Name'] . ' is just an engine to engage visitors with social-proof. However, with a gunning engine you should be able to serve any kind of statistical data. Now with the power of addons, you have access to a variety of stats that you can engage visitors with — <strong>recent sales</strong>, live <strong>real-time visitor count</strong>, <strong>sales milestones</strong>, <strong>custom calls-to-action</strong> and so much more.', 'wprtsp' ); ?></p>
				</div>

				<div class="feature column is-vertically-aligned-top">
					<h4><?php esc_html_e( 'A Call to Developers', 'bbpress' ); ?></h4>
					<p><?php esc_html_e( 'Developers can create their own addons and sell / distribute as they deem fit. This extends the power of ' . $plugin_data['Name'] . ' and is a win-win for everyone.', 'wprtsp' ); ?></p>
				</div>

				<div class="last-feature feature column is-vertically-aligned-top">
					<h4><?php _e( 'Over to you', 'wprtsp' ); ?></h4>
					<p><?php _e( 'We\'ll stop bragging now and let you tap into the power of ' . $plugin_data['Name'] . ' Let\'s <a href="' . get_admin_url( null, 'edit.php?post_type=socialproof' ) . '">get started</a>. Shall we?', 'wprtsp' ); ?></p>
				</div>
			</div>
		</div>
		<div class="return-to-dashboard"><?php _e( 'Let\'s', 'wprtsp' ); ?><a href="<?php echo esc_url( get_admin_url( null, 'edit.php?post_type=socialproof' ) ); ?>"><?php _e( 'Get Started' ); ?></a><?php _e( 'Shall we?', 'wprtsp' ); ?></div>
	</div>
		<?php
	}

	function do_upgrade() {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}
		$socialproofs = array();
		$query        = new WP_Query(
			array(
				'post_type'   => 'socialproof',
				'post_status' => 'any',
			)
		);

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id   = get_the_ID();
			$post_meta = get_post_meta( $post_id, '_socialproof', true );
			// Even though the upgrade flag is set, we must still verify each CPT for prudence
			if ( ! isset( $post_meta['general_cpt_version'] ) || ( isset( $post_meta['general_cpt_version'] ) && version_compare( $post_meta['general_cpt_version'], $this->version, '<' ) ) ) {
				$socialproofs[] = $post_id;
			}
		}

		wp_reset_query();

		if ( ! empty( $socialproofs ) ) {
			do_action( 'before_wprtsp_upgrade', $this->version );
			foreach ( $socialproofs as $socialproof ) {
				$settings = get_post_meta( $socialproof, '_socialproof', true );
				$settings = $this->wprtsp_sanitize( $settings );
				update_post_meta( $socialproof, '_socialproof', $settings );
			}
			do_action( 'after_wprtsp_upgrade', $this->version );
			delete_option( 'wprtsp_upgrade_required' );
		}

	}

	/* Add links below the plugin name on the plugins page */
	function plugin_action_links( $links ) {
		$links[] = '<a target="_blank" href="https://www.converticacommerce.com?item_name=Donation%20for%20WP%20Social%20Proof&cmd=_donations&currency_code=USD&lc=US&business=shivanand@converticacommerce.com"><strong style="display:inline">Donate</strong></a>';
		$links[] = '<a href="' . get_admin_url( null, 'edit.php?post_type=socialproof' ) . '"><strong style="display:inline">Settings</strong></a>';
		return $links;
	}

	function plugin_meta_links( $links, $file ) {

		if ( $file !== plugin_basename( __FILE__ ) ) {
			return $links;
		}

		$links[] = '<strong><a target="_blank" href="https://wp-social-proof.com/contact/" title="Direct Free Support">Free Direct Support</a></strong>';
		$links[] = '<strong><a target="_blank" href="https://wp-social-proof.com/" title="Website of this plugin">Plugin Homepage</a></strong>';
		$links[] = '<strong><a target="_blank" href="https://wordpress.org/plugins/wp-real-time-social-proof/" title="Rate WP Real-Time Social-Proof">Rate the plugin ★★★★★</a></strong>';
		$links[] = '<strong><a target="_blank" href="https://www.converticacommerce.com?item_name=Donation%20for%20WP%20Social%20Proof&cmd=_donations&currency_code=USD&lc=US&business=shivanand@converticacommerce.com"><strong style="display:inline">Donate</strong></a></strong>';

		return $links;
	}

	function admin_head() {
		?>
		<style type="text/css">#menu-posts-socialproof .wp-menu-image img { width: 20px; height: auto; opacity: 1; }</style>
		<?php
		remove_submenu_page( 'index.php', 'wprtsp-firstrun' );
	}

	/* Enqueue the styles for admin page */
	function plugin_styles() {
		$screen = get_current_screen();

		if ( $screen->post_type == 'socialproof' ) {
			wp_enqueue_style( 'wprtsp', $this->uri . 'assets/admin-styles.css', array(), filemtime( $this->dir . 'assets/admin-styles.css' ) );
		}

	}

	function enqueue_scripts() {
		$notifications        = get_posts(
			array(
				'post_type'      => 'socialproof',
				'posts_per_page' => -1,
			)
		);
		$active_notifications = array();

		foreach ( $notifications as $notification ) {
			$meta                    = get_post_meta( $notification->ID, '_socialproof', true );
			$meta                    = $this->wprtsp_sanitize( $meta );
			$meta['notification_id'] = $notification->ID;
			$this->settings          = $meta;
			$enabled                 = apply_filters( 'wprtsp_enabled', false, $meta );

			if ( ! $enabled ) {
				return;
			}
		}
	}

	function remove_metaboxes() {
		global $wp_meta_boxes;
		global $post;
		$current_post_type = get_post_type( $post );
		if ( $current_post_type == 'socialproof' ) {
			$publishbox    = $wp_meta_boxes['socialproof']['side']['core']['submitdiv'];
			$wp_meta_boxes = array();

			$wp_meta_boxes['socialproof'] = array(
				'side' => array(
					'core' => array( 'submitdiv' => $publishbox ),
				),
			);
		}
	}

	function add_meta_boxes() {
		do_action( 'wprtsp_add_meta_boxes' );
		remove_all_actions( 'edit_form_advanced' );
		remove_all_actions( 'edit_page_form' );
	}

	function save_meta_box_data( $post_id, $post, $update ) {

		if ( ! isset( $_POST['socialproof_meta_box_nonce'] ) ||
			! wp_verify_nonce( $_POST['socialproof_meta_box_nonce'], 'socialproof_meta_box_nonce' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$request = $_POST['wprtsp'];

		$settings = $this->wprtsp_sanitize( $request );
		$settings = apply_filters( 'wprtsp_cpt_update_settings', $settings );

		update_post_meta( $post_id, '_socialproof', $settings );
	}

	function wprtsp_enabled( $enabled, $settings ) {
		// echo '$enabled';
		// var_dump($enabled);
		// echo '$settings';
		// var_dump($settings);
		$post_ids = $settings['general_post_ids'];
		$show_on  = $settings['general_show_on'];
		switch ( $show_on ) {
			case '1':
				$records = $this->wprtsp_get_proofs();
				if ( $records ) {
					$this->settings['proofs'] = $records;
					$enabled                  = true;
				}
				break;
			case '2':
				if ( ! is_array( $post_ids ) ) {
					if ( strpos( $post_ids, ',' ) !== false ) {
						$post_ids = explode( ',', $post_ids );
					} else {
						$post_ids = array( $post_ids );
					}
				}
				if ( is_singular() && in_array( get_the_ID(), $post_ids ) ) {
					$records = $this->wprtsp_get_proofs();
					if ( $records ) {
						$this->settings['proofs'] = $records;
						$enabled                  = true;
					}
				}
				break;
			case '3':
				$post_ids = $settings['general_post_ids'];
				if ( ! is_array( $post_ids ) ) {
					if ( strpos( $post_ids, ',' ) !== false ) {
						$post_ids = explode( ',', $post_ids );
					} else {
						$post_ids = array( $post_ids );
					}
				}
				if ( ! is_singular() || ( is_singular() && ! in_array( get_the_ID(), $post_ids ) ) ) {
					$records = $this->wprtsp_get_proofs();
					if ( $records ) {
						$this->settings['proofs'] = $records;
						$enabled                  = true;
					}
				}
				break;
		}

		$exclude_role = $settings['general_roles_exclude'];
		$roles        = get_role( $exclude_role )->capabilities;
		foreach ( $roles as $cap ) {
			if ( current_user_can( $cap ) ) {
				$enabled = false;
			}
		}
		if ( $enabled ) {
			wp_enqueue_script( 'wprtsp-main', $this->uri . 'assets/wprtspcpt.js', array( 'jquery' ), filemtime( $this->dir . 'assets/wprtspcpt.js' ), true );
			wp_localize_script( 'wprtsp-main', 'wprtsp_vars', json_encode( apply_filters( 'wprtsp_vars', $this->settings ) ) );
		}
		return $enabled;
	}

	function wprtsp_detect_mobile( $settings ) {
		$settings['is_mobile'] = wp_is_mobile() ? true : false;
		return $settings;
	}

	function wprtsp_add_vars( $vars ) {
		$vars['url']      = $this->uri;
		$vars['siteurl']  = get_bloginfo( 'url' );
		$vars['sitename'] = get_bloginfo( 'name' );
		return $vars;
	}

	function wprtsp_get_proofs() {
		$settings = $this->settings;
		// $this->llog($settings);
		$conversions = apply_filters( 'wprtsp_get_proof_data_conversions_' . $settings['conversions_shop_type'], $settings );
		$hotstats    = apply_filters( 'wprtsp_get_proof_data_hotstats_' . $settings['conversions_shop_type'], array(), $settings );
		// $this->llog($settings);
		$livestats = apply_filters( 'wprtsp_get_proof_data_livestats', array(), $settings );
		$ctas      = apply_filters( 'wprtsp_get_proof_data_ctas', array_key_exists( 'ctas', $settings ) ? $settings['ctas'] : array(), $settings );

		if ( $conversions ) {
			$settings['proofs']['conversions'] = $conversions;
		}
		if ( $hotstats ) {
			$settings['proofs']['hotstats'] = $hotstats;
		}
		if ( $livestats ) {
			$settings['proofs']['livestats'] = $livestats;
		}
		if ( $ctas ) {
			$settings['proofs']['ctas'] = $ctas;
		}

		return $settings['proofs'];
	}

	function settings_menu() {
		add_options_page( 'Social Proof', 'Social Proof', 'manage_options', 'wprtsp', array( $this, 'settings_link' ) );
		add_submenu_page( 'edit.php?post_type=socialproof', 'Social Proof Support', 'Hero Support', 'manage_options', 'wprtsp-support', array( $this, 'support_page' ) );
	}

	function firstrun_menu() {
		$dashboard = add_dashboard_page( 'WP Real-Time Social-Proof', 'WP Real-Time Social-Proof', 'manage_options', 'wprtsp-firstrun', array( $this, 'wprtsp_dashboard' ) );
		add_action( 'admin_print_styles-' . $dashboard, array( $this, 'admin_dashboard_css' ) );
	}

	function admin_dashboard_css() {
		wp_enqueue_style( 'wprtsp-dashboard', $this->uri . 'assets/dashboard.css' );
	}

	function settings_link() {
		?>
		<div class="wrap">
			<h1>Get Started with WP Real-Time Social-Proof</h1>
			<div class="container-wrap">
			<p><strong><a href="<?php echo get_admin_url( null, 'edit.php?post_type=socialproof' ); ?>">Click here to get started with Wp Real-Time Social-Proof</a></strong></p>
			</div>
		</div>
		<script>
		top.location.href='<?php echo get_admin_url( null, 'edit.php?post_type=socialproof' ); ?>'; // redirect via JS because WP throws headers already sent in WP Debug mode.
		</script>
		<?php
		// wp_redirect( get_admin_url( null, 'edit.php?post_type=socialproof'), 301 );
		// exit;
	}

	function support_page() {
		?>
		<div class="wrap">
		<h1>Support for WP Real-Time Social-Proof</h1>
		<div class="container-wrap">
			<h2>We are eager to help you! And here's why</h2>
			<div style="max-width: 600px;">
				<p>In order to improve WP Real-Time Social-Proof, we are eager to learn what kind of issues you face and help resolve them. That's the reason why <strong>we offer prompt, free, heroic support</strong>.</p>
				<p>If you want prompt support, the fastest way to get a response is to <a target="_blank" href="https://wp-social-proof.com/contact/" style="font-weight:bold">send us a direct message here</a>.</p>
				<p>Did we tell you that support is free? :)</p>
			</div>
		</div>
	</div>
		<?php
	}

	function plugin_update_message( $data, $response ) {
		$upgrade_notice = '';
		if ( $this->is_valid_pro() ) {
			$changelog = 'https://wp-social-proof.com/buy/wp-real-time-social-proof/?changelog=1';
			$changelog = 'https://raw.githubusercontent.com/sharmashivanand/wp-real-time-social-proof/master/readme.txt';
			$res       = wp_safe_remote_get( $changelog );
			if ( is_wp_error( $res ) ) {
				return;
			}
			$res    = wp_remote_retrieve_body( $res );
			$regexp = '~==\s*Changelog\s*==\s*=\s*[0-9.]+\s*=(.*)(=\s*' . preg_quote( $this->version ) . '\s*=|$)~Uis';// $res  = stristr( $res, '== Changelog ==', true );
			if ( ! preg_match( $regexp, $res, $matches ) ) {
				return;
			}
			$changelog = (array) preg_split( '~[\r\n]+~', trim( $matches[1] ) );
			// $ul = false;

			foreach ( $changelog as $index => $line ) {
				if ( preg_match( '~^\s*\*\s*~', $line ) ) {
					$line            = preg_replace( '~^\s*\*\s*~', '', htmlspecialchars( $line ) );
					$upgrade_notice .= '<span style="font-weight:bold;">&#x2605;</span> ' . $line . '<br />';
				} else {

				}
			}
			$upgrade_notice = $upgrade_notice;
		} else {
			$changelog = 'https://plugins.trac.wordpress.org/browser/' . basename( $this->dir ) . '/trunk/readme.txt?format=txt'; // should translate into https://plugins.trac.wordpress.org/browser/wp-real-time-social-proof/trunk/readme.txt?format=txt since repo doesn't allow changing slugs
			$res       = wp_safe_remote_get( $changelog );
			if ( is_wp_error( $res ) ) {
				return;
			}
			$res    = wp_remote_retrieve_body( $res );
			$regexp = '~==\s*Changelog\s*==\s*=\s*[0-9.]+\s*=(.*)(=\s*' . preg_quote( $this->version ) . '\s*=|$)~Uis';// $res  = stristr( $res, '== Changelog ==', true );
			if ( ! preg_match( $regexp, $res, $matches ) ) {
				return;
			}
			$changelog = (array) preg_split( '~[\r\n]+~', trim( $matches[1] ) );
			// $ul = false;

			foreach ( $changelog as $index => $line ) {
				if ( preg_match( '~^\s*\*\s*~', $line ) ) {
					$line            = preg_replace( '~^\s*\*\s*~', '', htmlspecialchars( $line ) );
					$upgrade_notice .= '<span style="font-weight:bold;">&#x2605;</span> ' . $line . '<br />';
				} else {

				}
			}
			$upgrade_notice = $upgrade_notice;
		}

		$new_version = $data['new_version'];
		if ( version_compare( $this->version, '2', '<' ) && version_compare( $new_version, '2', '>=' ) ) { // if current version is less than 2 and new version is 2 or greater then bold the message and show as critical.
			$major_notice = '<strong>Alert! This is a major update. Get rid of your old <a href="' . get_admin_url( null, 'edit.php?post_type=socialproof' ) . '">Social Proofs and create new ones</a> after upgrade for more awesomeness!</strong><br />';
		} else {
			$major_notice = '';
		}
		echo '<br /><br /><span style="display:block; border: 1px solid hsl(200, 100%, 80%); padding: 1em; background: hsl(200, 100%, 90%); line-height:2">' . $major_notice . $upgrade_notice . '</span>';
	}

	function wprtsp_sanitize( $request ) {
		return apply_filters( 'wprtsp_sanitize', $request );
	}

	private function __construct() {}

	function respond_to_browser( $response, $data, $screen_id ) {
		if ( isset( $data['wprtsp'] ) ) {
			$notification_id = $data['wprtsp_notification_id'];
			$shop_type       = get_post_meta( $notification_id, '_socialproof', true );
			$shop_type       = $shop_type['conversions_shop_type'];
			$request_type    = $shop_type['proof_type']; // which kind of proof is requested
			switch ( $shop_type ) {
				case 'Easy_Digital_Downloads':
					$response = $this->send_edd_records( $response, $data, $screen_id, $notification_id );
					break;
				case 'WooCommerce':
					return $this->send_wooc_records( $response, $data, $screen_id, $notification_id );
				break;
				default:
					$response = $this->send_generated_records( $response, $data, $screen_id, $notification_id );
			}
		}
		return $response;
	}

	/* Outputs any variable / php objects / arrays in a clear visible frmat */
	function llog( $str ) {
		if ( ! is_user_logged_in() ) {
			return;
		}
		echo '<pre>';
		print_r( $str );
		echo '</pre>';
	}

}

function wprtsp() {
	return WPRTSP::get_instance();
}

// Let's roll!
wprtsp();

<?php
/**
 * Plugin Name: WP Live Social-Proof
 * Description: Animated, live, real-time social-proof Pop-ups for your WordPress website to boost sales and signups.
 * Version:     2.3
 * Plugin URI:  https://wordpress.org/plugins/wp-real-time-social-proof/
 * Author:      Shivanand Sharma
 * Author URI:  https://www.wp-social-proof.com
 * Text Domain: wprtsp
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Tags: social proof, conversion, ctr, ecommerce, marketing, popup, woocommerce, easy digital downloads, newsletter, optin, signup, sales triggers
 */

/*
Copyright 2025 Shivanand Sharma

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPRTSP_EDD_SL_URL', 'https://wp-social-proof.com' );
define( 'WPRTSPAPIEP', 'https://wp-social-proof.com/gaapi/' );
define( 'WPRTSPFILE', __FILE__ );

// License constants for PHP 5.6+ compatibility (traits can't have constants before PHP 8.2)
define( 'WPRTSP_LICENSE_CACHE_KEY', 'wprtsp_license_status' );
define( 'WPRTSP_LICENSE_ITEM_ID', 262 );
define( 'WPRTSP_LICENSE_TTL', 86400 ); // 24 hours

// Include license trait before class definition
require_once plugin_dir_path( __FILE__ ) . 'inc/license_manager.php';

class WPRTSP {
	use WPRTSP_License_Trait;

	public $style_box, $wprtsp_notification_style, $wprtsp_text_style, $wprtsp_action_style, $sound_notification, $sound_notification_markup;
	public $dir = '';
	public $uri = '';
	public $settings;
	public $version = false;

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

		// License manager already included before class definition
		if ( file_exists( $this->dir . 'premium/pro.php' ) && $this->is_valid_pro() ) {
			include_once $this->dir . 'premium/pro.php';
		}
	}

	function setup_actions() {
		$this->init_license(); // Initialize license functionality
		add_action( 'plugins_loaded', array( $this, 'set_version' ) ); // setup plugin information so that it's easier to get
		add_action( 'admin_init', array( $this, 'plugin_data' ) ); // setup plugin information so that it's easier to get
		add_action( 'init', array( $this, 'register_post_types' ) ); // register our CPT
		add_action( 'admin_notices', array( $this, 'admin_notice' ), 99999 );
		add_action( 'admin_init', array( $this, 'needs_upgrade' ) ); // upgrade cpt data routine
		add_action( 'admin_init', array( $this, 'do_upgrade' ) ); // upgrade cpt data routine
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) ); // Add important links to plugins list (left-side)
		add_filter( 'plugin_row_meta', array( $this, 'plugin_meta_links' ), 10, 2 ); // Add important links to plugins list (right-side)
		add_action( 'admin_head', array( $this, 'admin_head' ) ); // some quick fixes to admin styles especially CPT menu icon
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_styles' ), 99 ); // Style our CPT
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
		add_action( 'check_ajax_referer', array( $this, 'postbox_actions' ), 1 );
		add_filter( 'get_user_option_closedpostboxes_socialproof', array( $this, 'metaboxclosed' ), 99, 3 );
		add_filter( 'get_user_option_metaboxhidden_socialproof', array( $this, 'metaboxhidden' ), 99, 3 );
	}

	function metaboxclosed( $result, $option, $user ) {
		// disable hiding socialproof metaboxes.
		return array( 'pseudo_wpsp_box' );
	}

	function metaboxhidden( $result, $option, $user ) {
		// disable hiding socialproof metaboxes.
		return array( 'pseudo_wpsp_box' );
	}

	function postbox_actions( $action ) {
		// print_r($_REQUEST);
		if ( ! empty( $_REQUEST['page'] ) && $_REQUEST['page'] == 'socialproof' ) {
			if ( $action != 'meta-box-order' ) {
				die( 'wpsp doesn\'t support: ' . $action );
			}
			// return;
		}
	}

	function add_extra_meta_boxes() {
		add_meta_box( 'social-proof-rss', __( 'Tips &amp; Tricks', 'wprtsp' ), array( $this, 'meta_box_tips' ), 'socialproof', 'side' );
	}

	function meta_box_tips() {
		?>
		<div class="wprtsp-tips">
			<h4><?php _e( 'Quick Tips', 'wprtsp' ); ?></h4>
			<ul>
				<li><?php _e( 'Test different positions and styles to find what converts best', 'wprtsp' ); ?></li>
				<li><?php _e( 'Use real customer data for authentic social proof', 'wprtsp' ); ?></li>
				<li><?php _e( 'Monitor conversion rates to optimize performance', 'wprtsp' ); ?></li>
			</ul>
			
			<h4><?php _e( 'Need Help?', 'wprtsp' ); ?></h4>
			<p>
				<a href="https://wp-social-proof.com/contact/" target="_blank" class="button button-secondary">
					<?php _e( 'Get Support', 'wprtsp' ); ?>
				</a>
			</p>
			
			<?php if ( ! $this->is_valid_pro() ) : ?>
			<h4><?php _e( 'Go Pro', 'wprtsp' ); ?></h4>
			<p><?php _e( 'Unlock advanced features with WP Social Proof Pro!', 'wprtsp' ); ?></p>
			<p>
				<a href="https://wp-social-proof.com/" target="_blank" class="button button-primary">
					<?php _e( 'Upgrade Now', 'wprtsp' ); ?>
				</a>
			</p>
			<?php endif; ?>
		</div>
		<style>
		.wprtsp-tips h4 { margin-top: 15px; margin-bottom: 8px; }
		.wprtsp-tips ul { margin-left: 20px; }
		.wprtsp-tips li { margin-bottom: 5px; }
		.wprtsp-tips .button { margin-top: 5px; }
		</style>
		<?php
	}

	function is_pro() {
		if ( file_exists( $this->dir . 'wprtsppro/pro/pro.php' ) ) {
			return true;
		}
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

	function set_version() {
		$version = get_file_data( WPRTSPFILE, array( 'wprtspversion' => 'Version' ) );
		if ( isset( $version['wprtspversion'] ) ) {
			$this->version = $version['wprtspversion'];
		} else {
			$this->version = $version['wprtspversion'];
		}
	}

	function plugin_data() {
		$plugin_data = get_plugin_data( WPRTSPFILE );
		return $plugin_data;
	}
	
	/**
	 * Helper function to get plugin data from anywhere
	 * 
	 * @return array Plugin data
	 */
	function get_plugin_data_static() {
		return $this->plugin_data();
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

		if ( $this->is_pro() && ! $this->is_valid_pro() ) { // Allow opportunity to input license
			echo '<div class="notice notice-error"><p><strong>WP Social Proof Pro: <a class="button-primary" target="_blank" href="' . menu_page_url( 'wprtsp', false ) . '">Click&nbsp;here&nbsp;to&nbsp;enter&nbsp;your&nbsp;license&nbsp;&rarr;</a></strong></p></div>';
		}

		$args   = array(
			'post_type'   => 'socialproof',
			'post_status' => 'publish',
		);
		$proofs = new WP_Query( $args );
		if ( ! $proofs->have_posts() ) {
			$screen = get_current_screen();
			if ( $screen && ! empty( $screen->id ) && $screen->id !== 'edit-socialproof' ) {
				echo '<div class="notice notice-success"><p><strong>Let\'s get started with Social Proof.</strong> <a class="button-primary" target="_blank" href="' . esc_url( get_admin_url( null, 'edit.php?post_type=socialproof' ) ) . '">Create&nbsp;your&nbsp;first&nbsp;Social-Proof&nbsp;&rarr;</a></p></div>';
			}
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
				$this->firstrun_redirect( $this->version );
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
		if ( isset( $_REQUEST['upgrade'] ) ) {
			$process = 'updating';
		} else {
			$process = 'installing';
		}
		?>
		<div class="wrap about-wrap">
		<h1><?php printf( esc_html__( 'Welcome to %1$s %2$s', 'wprtsp' ), $plugin_data['Name'], $display_version ); ?></h1>
		<div class="about-text"><?php printf( esc_html__( 'Thank you for %1$s %2$s! Version %3$s is ready to rock!', 'wprtsp' ), $process, $plugin_data['Name'], $display_version ); ?></div>
		<?php
		if ( $this->is_pro() && ! $this->is_valid_pro() ) { // Allow opportunity to input license
			echo '<a class="button-primary" target="_blank" style="font-size:1.618em;padding: 1em; display:table; height: auto; margin: 1em;" href="' . menu_page_url( 'wprtsp', false ) . '">Click&nbsp;here&nbsp;to&nbsp;enter&nbsp;your&nbsp;license&nbsp;&rarr;</a>';
		}
		if ( ! $this->is_pro() ) { // Allow to upgrade
			echo '<a class="button-primary" target="_blank" style="font-size:1.618em;padding: 1em; display:table; height: auto; margin: 1em;" href="https://wp-social-proof.com/?utm_source=firstrun&utm_medium=web&utm_campaign=wprtsp">Unleash more sales with WP Social Proof Pro!&nbsp;&rarr;</a>';
		}

		$args   = array(
			'post_type'   => 'socialproof',
			'post_status' => 'publish',
		);
		$proofs = new WP_Query( $args );
		if ( ! $proofs->have_posts() ) {
			echo '<a class="button-primary" target="_blank" style="font-size:1.618em;padding: 1em; display:table; height: auto; margin: 1em;" href="' . esc_url( get_admin_url( null, 'edit.php?post_type=socialproof' ) ) . '">Create&nbsp;your&nbsp;first&nbsp;Social-Proof&nbsp;&rarr;</a>';
		}

		?>
		<div class="wp-badge wprtsp-badge"><?php printf( esc_html__( 'Version %s', 'wprtsp' ), $display_version ); ?></div>

		<h2 class="nav-tab-wrapper">
			<a class="nav-tab nav-tab-active" href="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'wprtsp-about' ), 'index.php' ) ) ); ?>">
				<?php esc_html_e( 'What&#8217;s New', 'wprtsp' ); ?>
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
					<h4><?php esc_html_e( 'A Call to Developers', 'wprtsp' ); ?></h4>
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

	function get_setting( $setting ) {
		$settings = get_option( 'wprtsp' );
		return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
	}

	function update_setting( $setting, $value ) {
		$settings = get_option( 'wprtsp' );
		if ( ! $settings ) {
			$settings = array();
		}
		$settings[ $setting ] = $value;

		wp_cache_delete( 'wprtsp', 'options' );
		return update_option( 'wprtsp', $settings );
	}

	function delete_setting( $setting ) {
		$settings = get_option( 'wprtsp' );
		if ( ! $settings ) {
			$settings = array();
		}
		unset( $settings[ $setting ] );
		update_option( 'wprtsp', $settings );
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
		<style type="text/css">#menu-posts-socialproof .wp-menu-image img { width: 20px; height: auto; opacity: 1; }
		.post-type-socialproof .postbox.hide-if-js {
			_display: block;
		}
		</style>
		<?php
		remove_submenu_page( 'index.php', 'wprtsp-firstrun' );
	}

	/* Enqueue the styles for admin page */
	function plugin_styles() {
		$screen = get_current_screen();
		if ( $screen->post_type == 'socialproof' ) {
			// wp_dequeue_script( 'jquery-ui-sortable' );
			wp_dequeue_script( 'jquery-ui-draggable' );
			wp_deregister_script( 'postbox' );
			wp_enqueue_style( 'wprtsp', $this->uri . 'assets/admin-styles.css', array(), filemtime( $this->dir . 'assets/admin-styles.css' ) );
		}
	}

	function enqueue_scripts() {
		$notifications = get_posts(
			array(
				'post_type'      => 'socialproof',
				'posts_per_page' => -1,
			)
		);

		// Fist collect all proofs that are supposed to be shown on singular
		// Second collect all proofs that are supposed to be shown except on this post
		// Finally collect all proofs that are supposed to be shown globally
		$sp_sinular = array();
		$sp_global  = array();
		foreach ( $notifications as $notification ) {
			$meta     = get_post_meta( $notification->ID, '_socialproof', true );
			$meta     = $this->wprtsp_sanitize( $meta );
			$post_ids = $meta['general_post_ids'];
			$show_on  = $meta['general_show_on'];
			if ( $meta['general_show_on'] == '2' ) {
				$sp_sinular[] = $notification;
			} else {
				$sp_global[] = $notification;
			}
		}
		$notifications = array_merge( $sp_sinular, $sp_global );

		foreach ( $notifications as $notification ) {
			$meta                    = get_post_meta( $notification->ID, '_socialproof', true );
			$meta                    = $this->wprtsp_sanitize( $meta );
			$meta['notification_id'] = $notification->ID;
			$meta['post_title']      = sanitize_title( $notification->post_title );
			$this->settings          = $meta;
			// echo 'enabled:' . $notification->ID . PHP_EOL;
			$enabled = apply_filters( 'wprtsp_enabled', false, $meta );

			if ( $enabled ) {
				return $enabled; // Required so that once an apt social-proof to be displayed is found, it is not overridden with global or other older social-proofs.
			}
			if ( ! $enabled ) {
				// return false;
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

		$post_ids = $settings['general_post_ids'];
		$show_on  = $settings['general_show_on'];

		switch ( $show_on ) {
			case '1':   // Entire Site
				$records = $this->wprtsp_get_proofs();
				if ( $records ) {
					$this->settings['proofs'] = $records;
					$enabled                  = true;
				}
				break;
			case '2': // Specific post / page
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
			case '3':   // Everywhere except the following
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
		if ( ! empty( $exclude_role ) ) {
			$roles = get_role( $exclude_role )->capabilities;
			foreach ( $roles as $cap ) {
				if ( current_user_can( $cap ) ) {
					$enabled = false;
				}
			}
		} else {
			// $enabled = false;
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
		$vars['url']               = $this->uri;
		$vars['siteurl']           = get_bloginfo( 'url' );
		$vars['sitename']          = get_bloginfo( 'name' );
		$vars['translate_ago']     = __( 'ago', 'wprtsp' );
		$vars['translate_minutes'] = __( 'minutes', 'wprtsp' );
		return $vars;
	}

	function registered_proofs() {
		return apply_filters( 'wprtsp_register_proof', array() );
	}

	function wprtsp_get_proofs() {
		$settings    = $this->settings;
		$conversions = apply_filters( 'wprtsp_get_proof_data_conversions_' . $settings['conversions_shop_type'], $settings );
		$hotstats    = apply_filters( 'wprtsp_get_proof_data_hotstats_' . $settings['conversions_shop_type'], array(), $settings );
		$livestats   = apply_filters( 'wprtsp_get_proof_data_livestats', array(), $settings );

		$ctas = apply_filters( 'wprtsp_get_proof_data_ctas', array_key_exists( 'ctas', $settings ) ? $settings['ctas'] : array(), $settings );

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
		if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
			return;
		}
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

/**
 * Global helper function to get plugin data
 * Can be called from anywhere including addons
 * 
 * @return array Plugin data
 */
function wprtsp_get_plugin_data() {
	if ( ! function_exists( 'get_plugin_data' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	return get_plugin_data( WPRTSPFILE );
}

// Let's roll!
wprtsp();

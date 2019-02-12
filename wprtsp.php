<?php
/**
 * Plugin Name: WP Real-Time Social-Proof
 * Description: Animated, live, real-time social-proof Pop-ups for your WordPress website to boost sales and signups.
 * Version:     1.9.2
 * Plugin URI:  https://wordpress.org/plugins/wp-real-time-social-proof/
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: wprtsp
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Tags: social proof, conversion, ctr, ecommerce, marketing, popup, woocommerce, easy digital downloads, newsletter, optin, signup, sales triggers
 */

/*
Copyright 2018 Shivanand Sharma

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

//namespace wprtsp;
if( ! class_exists('WPRTSP') ) {
    class WPRTSP {

        public $names = array('Kai Nakken', 'Cathy Gluck', 'Tiana Heier', 'Reiko Doucette', 'Shanel Nichols', 'Karan Sigler', 'Javier Roots', 'Camila Nowak', 'Refugia Blanc', 'Farrah Beehler', 'Kelly Lonergan', 'Jene Lechler', 'Awilda Hesler', 'Robbi Jauregui', 'Jaimie Wilkinson', 'Nanette Perras', 'Cinda Alley', 'Monet Player', 'Linn Bayless', 'Yukiko Cottman', 'Almeta Walkes', 'Janina Benesh', 'Shaun Camp', 'Mitch Ohern', 'Sam Carlon', 'Man Millard', 'Dania Coil', 'Eartha Hayhurst', 'Devin Fuston', 'Darcie Covin', 'Traci Mcsweeney', 'Lenore Bourassa', 'Nita Kaya', 'Tamra Biron', 'Melissa Garett', 'Myrta Magallanes', 'Magen Matinez', 'Gabriella Falls', 'Wayne Mcshane', 'Kristal Murnane', 'Allegra Plotner', 'Floyd Busbee', 'Danuta Lookabaugh', 'Nisha Correira', 'Lincoln Ewert', 'Shaunta Antrim', 'Augustine Rominger', 'Brady Sharpton', 'Jenice Tiedeman', 'Emanuel Hysmith', 'Sade Tefft', 'Kathe Macdowell', 'Tom Fordham', 'Elaina Moad', 'Denise Trudel', 'Rusty Mechem', 'Rosaura Tarin', 'Glayds Anger', 'Roma Hendrickson', 'Marsha Mathena', 'Shiloh Broadfoot', 'Casandra Pia', 'Cortez Bronstein', 'Bernadette Schwartz', 'Corinne Goudeau', 'Cornelia Kelsey', 'Joe Amore', 'Ahmad Blanca', 'Liana Chastain', 'Ester Shoop', 'Shayna Stoneman', 'Adrienne Faz', 'Carissa Cagle', 'Carita Meshell', 'Ria Reidy', 'Ka Hixson', 'Micki Hazen', 'Jeri Chaires', 'Gil Ledger', 'Kirk Square', 'Ericka Cedeno', 'Forest Mcquaid', 'Lauretta Keenan', 'Cleopatra Teeters', 'Gertha Rivas', 'Madie Iadarola', 'Elke Springfield', 'Marisol Patrick', 'Yoshie Studley', 'Cristopher Roddy', 'Buster Nyland', 'Vannesa Grable', 'Katharina Bustle', 'Monique Villescas', 'Maximo Lamb', 'Voncile Donahoe', 'Aiko Atkin', 'Tobie Mehta', 'Sixta Domina', 'Daniele Chacon') ;

        public $locations = array(array('city' => 'San Bernardino','state' => 'California'),array('city' => 'Birmingham','state' => 'Alabama'),array('city' => 'San Diego','state' => 'California'),array('city' => 'Fresno','state' => 'California'),array('city' => 'Modesto','state' => 'California'),array('city' => 'Toledo','state' => 'Ohio'),array('city' => 'Modesto','state' => 'California'),array('city' => 'Santa Ana','state' => 'California'),array('city' => 'Mesa','state' => 'Arizona'),array('city' => 'Dallas','state' => 'Texas'),array('city' => 'New York','state' => 'New York'),array('city' => 'Norfolk','state' => 'Virginia'),array('city' => 'Phoenix','state' => 'Arizona'),array('city' => 'Charlotte','state' => 'North Carolina'),array('city' => 'Jersey City','state' => 'New Jersey'),array('city' => 'Indianapolis','state' => 'Indiana'),array('city' => 'Arlington','state' => 'Texas'),array('city' => 'Raleigh','state' => 'North Carolina'),array('city' => 'Bakersfield','state' => 'California'),array('city' => 'Scottsdale','state' => 'Arizona'),array('city' => 'Philadelphia','state' => 'Pennsylvania'),array('city' => 'Tucson','state' => 'Arizona'),array('city' => 'Garland','state' => 'Texas'),array('city' => 'Fresno','state' => 'California'),array('city' => 'Los Angeles','state' => 'California'),array('city' => 'Lincoln','state' => 'Nebraska'),array('city' => 'Detroit','state' => 'Michigan'),array('city' => 'San Bernardino','state' => 'California'),array('city' => 'Fort Worth','state' => 'Texas'),array('city' => 'Chula Vista','state' => 'California'),array('city' => 'Glendale','state' => 'Arizona'),array('city' => 'Pittsburgh','state' => 'Pennsylvania'),array('city' => 'Las Vegas','state' => 'Nevada'),array('city' => 'Lexington-Fayette','state' => 'Kentucky'),array('city' => 'Akron','state' => 'Ohio'),array('city' => 'Orlando','state' => 'Florida'),array('city' => 'Baton Rouge','state' => 'Louisiana'),array('city' => 'Lincoln','state' => 'Nebraska'),array('city' => 'Buffalo','state' => 'New York'),array('city' => 'St. Paul','state' => 'Minnesota'),array('city' => 'Norfolk','state' => 'Virginia'),array('city' => 'San Antonio','state' => 'Texas'),array('city' => 'St. Petersburg','state' => 'Florida'),array('city' => 'Detroit','state' => 'Michigan'),array('city' => 'Houston','state' => 'Texas'),array('city' => 'St. Petersburg','state' => 'Florida'),array('city' => 'Madison','state' => 'Wisconsin'),array('city' => 'Lincoln','state' => 'Nebraska'),array('city' => 'Montgomery','state' => 'Alabama'),array('city' => 'Milwaukee','state' => 'Wisconsin'),array('city' => 'Jersey City','state' => 'New Jersey'),array('city' => 'New York','state' => 'New York'),array('city' => 'Denver','state' => 'Colorado'),array('city' => 'Birmingham','state' => 'Alabama'),array('city' => 'Sacramento','state' => 'California'),array('city' => 'Hialeah','state' => 'Florida'),array('city' => 'Albuquerque','state' => 'New Mexico'),array('city' => 'San Bernardino','state' => 'California'),array('city' => 'Baton Rouge','state' => 'Louisiana'),array('city' => 'Chula Vista','state' => 'California'),array('city' => 'Cleveland','state' => 'Ohio'),array('city' => 'Aurora','state' => 'Colorado'),array('city' => 'New Orleans','state' => 'Louisiana'),array('city' => 'Modesto','state' => 'California'),array('city' => 'Washington','state' => 'District of Columbia'),array('city' => 'Arlington','state' => 'Texas'),array('city' => 'Pittsburgh','state' => 'Pennsylvania'),array('city' => 'Montgomery','state' => 'Alabama'),array('city' => 'San Antonio','state' => 'Texas'),array('city' => 'Virginia Beach','state' => 'Virginia'),array('city' => 'Laredo','state' => 'Texas'),array('city' => 'Laredo','state' => 'Texas'),array('city' => 'Phoenix','state' => 'Arizona'),array('city' => 'Newark','state' => 'New Jersey'),array('city' => 'Virginia Beach','state' => 'Virginia'),array('city' => 'Lincoln','state' => 'Nebraska'),array('city' => 'Baltimore','state' => 'Maryland'),array('city' => 'Chandler','state' => 'Arizona'),array('city' => 'Houston','state' => 'Texas'),array('city' => 'Corpus Christi','state' => 'Texas'),array('city' => 'Tampa','state' => 'Florida'),array('city' => 'San Bernardino','state' => 'California'),array('city' => 'Austin','state' => 'Texas'),array('city' => 'Fort Wayne','state' => 'Indiana'),array('city' => 'Oakland','state' => 'California'),array('city' => 'Fresno','state' => 'California'),array('city' => 'Miami','state' => 'Florida'),array('city' => 'Huntington','state' => 'New York'),array('city' => 'Milwaukee','state' => 'Wisconsin'),array('city' => 'Jacksonville','state' => 'Florida'),array('city' => 'Washington','state' => 'District of Columbia'),array('city' => 'Laredo','state' => 'Texas'),array('city' => 'Lubbock','state' => 'Texas'),array('city' => 'Tucson','state' => 'Arizona'),array('city' => 'Stockton','state' => 'California'),array('city' => 'Albuquerque','state' => 'New Mexico'),array('city' => 'Phoenix','state' => 'Arizona'),array('city' => 'Durham','state' => 'North Carolina'),array('city' => 'Arlington','state' => 'Texas'),array('city' => 'Boise','state' => 'Idaho'));

        public $style_box, $wprtsp_notification_style, $wprtsp_text_style, $wprtsp_action_style, $sound_notification, $sound_notification_markup;
        public $dir = '';
        public $uri = '';

        static function get_instance() {

            static $instance = null;
            if ( is_null( $instance ) ) {
                $instance = new self;
                $instance->setup();
                $instance->includes();
                $instance->setup_actions();
            }
            return $instance;
        }

        function setup(){
            $this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );
            $this->uri  = trailingslashit( plugin_dir_url(  __FILE__ ) );
        }

        function includes(){
            if( file_exists( $this->dir . 'pro/pro.php') ) {
                include_once( $this->dir . 'pro/pro.php' );
            }
        }

        function setup_actions(){
            
            add_action( 'admin_init', array( $this, 'maybe_deactivate' ));
            add_action( 'init', array( $this, 'register_post_types' ));
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'plugin_styles' ) );
            add_filter( 'heartbeat_received', array( $this,'respond_to_browser'), 10, 3 ); // Logged in users:
            add_filter( 'heartbeat_nopriv_received', array( $this,'respond_to_browser'), 10, 3 ); // Logged out users
            add_action( 'wp_enqueue_scripts',  array( $this, 'enqueue_scripts'));
            add_action( 'add_meta_boxes', array( $this,'add_meta_boxes' ));
            add_action( 'save_post', array($this, 'save_meta_box_data' ));
            add_action( 'wprtsp_general_meta_settings',array( __NAMESPACE__, 'wprtsppro_general_meta' ));
            
            add_action( 'in_plugin_update_message'. basename(__DIR__).'/'.basename(__FILE__), array($this, 'plugin_update_message'), 10, 2 );
        }

        function maybe_deactivate(){
            if(class_exists('WPRTSPPRO')) {
                deactivate_plugins( plugin_basename(__FILE__));
            }
        }
        function plugin_update_message( $data, $response ) {
            if( isset( $data['upgrade_notice'] ) ) {
                printf(
                    '<div class="update-message">%s</div>',
                    wpautop( '<strong>Alert!</strong> This is a major update. Please setup the plugin again to use the new powerful features!' )
                );
            }
        }

        function add_meta_boxes(){
            add_meta_box( 'social-proof-general', __( 'General', 'erm' ), array($this, 'general_meta_box'), 'socialproof', 'normal');
            add_meta_box( 'social-proof-conversions', __( 'Conversions', 'erm' ), array($this, 'conversions_meta_box'), 'socialproof', 'normal');
        }

        function cpt_defaults() {

            $defaults = array(

                    'general_show_on' => '1',
                    'general_duration' => '4',
                    'general_initial_popup_time' => '5',
                    'general_subsequent_popup_time' => '30',
                    'general_post_ids' => get_option( 'page_on_front'),

                    'conversions_enable' => 1,
                    'conversions_show_on_mobile' => 1,
                    'conversions_shop_type' => class_exists('Easy_Digital_Downloads') ?  'Easy_Digital_Downloads' : ( class_exists( 'WooCommerce' ) ?  'WooCommerce' :  'Generated' ),
                    'conversions_transaction' => 'subscribed to the newsletter',
                    'conversions_transaction_alt' => 'registered for the webinar',
                    
                    'conversions_sound_notification' => 0,
                    'conversions_position' => 'bl',
                    
                    'positions' => array('bl' => 'Bottom Left', 'br' => 'Bottom Right'),

                    /* Additional routines */
                    'conversions_records' => $this->generate_cpt_records(array('conversions_transaction' => 'subscribed to the newsletter', 'conversions_transaction_alt' => 'registered for the webinar')),

            );

            return apply_filters('wprtsp_cpt_defaults', $defaults);
        }

        function general_meta_box(){
            global $post;
            wp_nonce_field( 'socialproof_meta_box_nonce', 'socialproof_meta_box_nonce' );
            if( apply_filters( 'wprtsp_general_meta', true ) ) {
            $settings = get_post_meta( $post->ID, '_socialproof', true );
            //$this->llog($settings);
            $settings = wp_parse_args ( $settings, $this->cpt_defaults());
            $show_on = $settings['general_show_on'];
            $post_ids = $settings['general_post_ids'];
            $duration = $settings['general_duration'];
            $initial_popup_time = $settings['general_initial_popup_time'];
            $subsequent_popup_time = $settings['general_subsequent_popup_time'];
            ?>
            <table id="tbl_display" class="wprtsp_tbl wprtsp_tbl_display">
                <tr>
                    <td colspan="2">
                        <h3>Display</h3>
                    </td>
                </tr>
                <tr>
                    <td><label for="wprtsp_general_show_on">Show On</label></td>
                    <td><select id="wprtsp_general_show_on" name="wprtsp[general_show_on]">
                            <option value="1" <?php selected( $show_on, 1 ); ?> >Entire Site</option>
                            <option value="2" <?php selected( $show_on, 2 ); ?> >On selected posts / pages</option>
                            <option value="3" <?php selected( $show_on, 3 ); ?> >Everywhere except the following</option>
                        </select>
                </tr>
                <tr id="post_ids_selector">
                    <td><label for="wprtsp_general_post_ids">Enter Post Ids (comma separated)</label></td>
                    <td><input type="text" class="widefat" <?php if($show_on == 1) {echo 'readonly="true"';} ?> id="wprtsp_general_post_ids" name="wprtsp[general_post_ids]" value="<?php echo $post_ids; ?>"></td>
                </tr>
            </table>
            <table id="tbl_timings" class="wprtsp_tbl wprtsp_tbl_timings">
                <tr>
                    <td colspan="2">
                        <h3>Timing</h3>
                    </td>
                </tr>
                <tr>
                    <td><label for="wprtsp_general_duration">Duration of each notification</label></td>
                    <td><input type="text" class="widefat" id="wprtsp_general_duration" name="wprtsp[general_duration]" value="<?php echo $duration; ?>"/>
                <tr>
                    <td><label for="wprtsp_general_initial_popup_time">Delay before first notification</label></td>
                    <td><select id="wprtsp_general_initial_popup_time" name="wprtsp[general_initial_popup_time]">
                            <option value="5" <?php selected( $initial_popup_time, 5 ); ?> >5</option>
                            <option value="15" <?php selected( $initial_popup_time, 15 ); ?> >15</option>
                            <option value="30" <?php selected( $initial_popup_time, 30 ); ?> >30</option>
                            <option value="60" <?php selected( $initial_popup_time, 60 ); ?> >60</option>
                            <option value="120" <?php selected( $initial_popup_time, 120 ); ?> >120</option>
                        </select></td>
                </tr>
                <tr>
                    <td><label for="wprtsp_general_subsequent_popup_time">Delay between notifications</label></td>
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
            else {
                do_action('wprtsp_general_meta_settings');
            }
        }

        function conversions_meta_box(){
            global $post;
            if( apply_filters('wprtsp_conversions_meta', true) ) {
            $settings = get_post_meta($post->ID, '_socialproof', true);
            $defaults = $this->cpt_defaults();
            $settings = wp_parse_args($settings, $defaults);
            $conversions_enable = $settings['conversions_enable'];
            $conversions_shop_type = $settings['conversions_shop_type'];
            $conversions_transaction = $settings['conversions_transaction'];
            $conversions_transaction_alt = $settings['conversions_transaction_alt'];
            $conversions_sound_notification = $settings['conversions_sound_notification'];
            $conversions_position = array_key_exists('conversions_position', $settings) ? $settings['conversions_position'] : 'bl';

            $sources = array();
            if(class_exists('Easy_Digital_Downloads')) {
                $sources[] = 'Easy_Digital_Downloads';
            }
            if( class_exists( 'WooCommerce' ) ) {
                $sources[] = 'WooCommerce';
            }
            $sources[] = 'Generated';

            $sources_html = '';
            foreach($sources as $key=>$value) {
                $sources_html .= '<option value="' . $value . '" ' . selected( $conversions_shop_type, $value, false ) .'>'. preg_replace('/[^\da-z]/i',' ', $value) .'</option>';
            }
            
            $positions_html = '';
            $positions = $defaults['positions'];
            foreach($positions as $key=>$value) {
                $positions_html .= '<option value="' . $key . '" ' . selected( $conversions_position, $key, false ) .'>'. preg_replace('/[^\da-z]/i',' ', $value) .'</option>';
            }
            ?>
            <table id="tbl_conversions" class="wprtsp_tbl wprtsp_tbl_conversions">
                <tr>
                    <td colspan="2">
                        <h3>Conversions</h3>
                    </td>
                </tr>
                <tr>
                    <td><label for="wprtsp[conversions_enable]">Enable</label></td>
                    <td>
                        <input id="wprtsp[conversions_enable]" name="wprtsp[conversions_enable]" type="checkbox" value="1" <?php checked( $conversions_enable, '1' , true); ?>/>
                    </td>
                </tr>
                <tr>
                    <td><label for="wprtsp_conversions_shop_type">Source</label></td>
                    <td><select id="wprtsp_conversions_shop_type" name="wprtsp[conversions_shop_type]">
                            <?php echo $sources_html; ?>
                        </select></td>
                </tr>
                <tr>
                    <td><label for="wprtsp_conversions_transaction">Transaction 1 for Generated Records</label></td>
                    <td><input id="wprtsp_conversions_transaction" <?php if($conversions_shop_type != 'Generated') {echo 'readonly="true"';} ?> name="wprtsp[conversions_transaction]" type="text" class="widefat" value="<?php echo $conversions_transaction ?>" /></td>
                </tr>
                <tr>
                    <td><label for="wprtsp_conversions_transaction_alt">Transaction 2 for Generated Records</label></td>
                    <td><input id="wprtsp_conversions_transaction_alt" <?php if($conversions_shop_type != 'Generated') {echo 'readonly="true"';} ?> name="wprtsp[conversions_transaction_alt]" type="text" class="widefat" value="<?php echo $conversions_transaction_alt ?>" /></td>
                </tr>
                <tr>
                    <td><label for="wprtsp[conversions_position]">Position</label></td>
                    <td><select id="wprtsp[conversions_position]" name="wprtsp[conversions_position]">
                            <?php echo $positions_html; ?>
                        </select></td>
                </tr>
                <tr>
                    <td><label for="wprtsp[conversions_sound_notification]">Enable Sound Notification</label></td>
                    <td><input id="wprtsp[conversions_sound_notification]" name="wprtsp[conversions_sound_notification]" type="checkbox" value="1" <?php checked( $conversions_sound_notification, '1' , true); ?>/></td>
                </tr>
            </table>
            <script type="text/javascript">
            jQuery( document ).ready(function() {
                jQuery('#wprtsp_conversions_shop_type').on('change',  function() {
                    if(jQuery('#wprtsp_conversions_shop_type').val() == 'Generated' ) {
                        jQuery('#wprtsp_conversions_transaction').removeAttr('readonly');
                        jQuery('#wprtsp_conversions_transaction_alt').removeAttr('readonly');
                    }
                    else {
                        jQuery('#wprtsp_conversions_transaction').attr('readonly', 'true');
                        jQuery('#wprtsp_conversions_transaction_alt').attr('readonly', 'true');
                    }
                });
            });
            
            </script>
            <?php
            }
            else {
                do_action('wprtsp_conversions_meta_settings');
            }
        }

        function save_meta_box_data($post_id){

            if ( ! isset( $_POST['socialproof_meta_box_nonce'] ) ||
                ! wp_verify_nonce( $_POST['socialproof_meta_box_nonce'], 'socialproof_meta_box_nonce' ) ) {
                return;
            }

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            
            $general_show_on = sanitize_text_field($_POST['wprtsp']['general_show_on']);
            $general_duration = sanitize_text_field($_POST['wprtsp']['general_duration']);
            $general_initial_popup_time = sanitize_text_field($_POST['wprtsp']['general_initial_popup_time']);
            $general_subsequent_popup_time = sanitize_text_field($_POST['wprtsp']['general_subsequent_popup_time']);
            $general_post_ids = sanitize_text_field($_POST['wprtsp']['general_post_ids']);

            $conversions_enable = sanitize_text_field($_POST['wprtsp']['conversions_enable']);
            $conversions_shop_type = sanitize_text_field($_POST['wprtsp']['conversions_shop_type']);
            $conversions_transaction = sanitize_text_field($_POST['wprtsp']['conversions_transaction']);
            $conversions_transaction_alt = sanitize_text_field($_POST['wprtsp']['conversions_transaction_alt']);
            $conversions_sound_notification = sanitize_text_field($_POST['wprtsp']['conversions_sound_notification']);
            $conversions_position = sanitize_text_field($_POST['wprtsp']['conversions_position']);

            $settings = array(

                'general_show_on' => $general_show_on,
                'general_duration' => $general_duration,
                'general_initial_popup_time' => $general_initial_popup_time,
                'general_subsequent_popup_time' => $general_subsequent_popup_time,
                'general_post_ids' => $general_post_ids,

                'conversions_enable' => $conversions_enable,
                'conversions_shop_type' => $conversions_shop_type,
                'conversions_transaction' => $conversions_transaction,
                'conversions_transaction_alt' => $conversions_transaction_alt,
                'conversions_sound_notification' => $conversions_sound_notification,
                'conversions_position' => $conversions_position,

            );
            
            $settings['records'] = $this->generate_cpt_records($settings);
            $this->generate_edd_records();
            $this->generate_wooc_records();
            //$settings = wp_parse_args( $settings, $this->cpt_defaults() );
            update_post_meta( $post_id, '_socialproof', $settings );
        }

        function register_post_types(){
            
            $labels = array(
                'name'                  => __( 'Notifications',                   'erm' ),
                'singular_name'         => __( 'singular_name',                    'erm' ),
                'menu_name'             => __( 'Social Proof',                   'erm' ),
                'name_admin_bar'        => __( 'name_admin_bar',                    'erm' ),
                'add_new'               => __( 'Add New Notification',                        'erm' ),
                'add_new_item'          => __( 'Add New Notification',            'erm' ),
                'edit_item'             => __( 'Edit Notification',               'erm' ),
                'new_item'              => __( 'new_item',                'erm' ),
                'view_item'             => __( 'view_item',               'erm' ),
                'view_items'            => __( 'view_items',              'erm' ),
                'search_items'          => __( 'search_items',            'erm' ),
                'not_found'             => __( 'No notificatins found',          'erm' ),
                'not_found_in_trash'    => __( 'not_found_in_trash', 'erm' ),
                'all_items'             => __( 'All Notifications',                   'erm' ),
                'featured_image'        => __( 'featured_image',                   'erm' ),
                'set_featured_image'    => __( 'set_featured_image',               'erm' ),
                'remove_featured_image' => __( 'remove_featured_image',            'erm' ),
                'use_featured_image'    => __( 'use_featured_image',            'erm' ),
                'insert_into_item'      => __( 'insert_into_item',        'erm' ),
                'uploaded_to_this_item' => __( 'uploaded_to_this_item',   'erm' ),
                'filter_items_list'     => __( 'filter_items_list',       'erm' ),
                'items_list_navigation' => __( 'items_list_navigation',   'erm' ),
                'items_list'            => __( 'items_list',              'erm' ),
            );
            $cpt_args = array(
                'description'         => 'Social Proof',
                'public'              => false,
                'show_ui'               => true,
                'show_in_admin_bar'   => true,
                'show_in_rest'        => true,
                'menu_position'       => null,
                'menu_icon'           => 'dashicons-format-chat',
                'can_export'          => true,
                'delete_with_user'    => false,
                'hierarchical'        => false,
                'has_archive'         => false,
                'labels'              => $labels,
                'template_lock' => true,
        
                // What features the post type supports.
                'supports' => array(
                    'title',
                    //'editor',
                    //'thumbnail',
                    // Theme/Plugin feature support.
                    //'custom-background', // Custom Background Extended
                    //'custom-header',     // Custom Header Extended
                    //'wpcom-markdown',    // Jetpack Markdown
                )
            );

            register_post_type( 'socialproof', apply_filters( 'socialproof_post_type_args', $cpt_args ) );
        }

        /* Get the engine going */
        private function __construct() {}

        function respond_to_browser($response, $data, $screen_id) {
            if ( isset( $data['wprtsp'] ) ) {
                $notification_id =  $data['wprtsp_notification_id'];
                $shop_type = get_post_meta($notification_id, '_socialproof', true);
                $shop_type = $shop_type['conversions_shop_type'];
                
                switch($shop_type) {
                    case 'Easy_Digital_Downloads': $response = $this->send_edd_records($response, $data, $screen_id, $notification_id);
                    break;
                    case 'WooCommerce': return $this->send_wooc_records($response, $data, $screen_id, $notification_id);
                    break;
                    default: $response = $this->send_generated_records($response, $data, $screen_id, $notification_id);
                }
            }
            return $response;
        }

        function send_edd_records($response, $data, $screen_id, $notification_id) {
            $records = get_transient('wprtsp_edd_' . $data['wprtsp']);
            $settings = get_transient('wprtsp_edd');
            if(false === $settings) {
                $settings = $this->generate_edd_records();
            }
            if($records) {
                $record = array_shift(array_diff_key( array_keys($settings), $records ));
                if(empty($record)) {
                    return;
                }
                $records[] = $record;
                set_transient('wprtsp_edd_' . $data['wprtsp'], $records, 1 * HOUR_IN_SECONDS);
                $response['wprtsp'] = json_encode( $this->get_edd_message($settings[$record], $notification_id) );
            }
            else {
                $settings_clone = $settings; // we'll use array_shift but need to reuse the array; so clone it
                $record = array_shift(array_keys( $settings_clone ));
                if(empty($record)) {
                    return;
                }
                $records[] = $record;
                set_transient('wprtsp_edd_' . $data['wprtsp'], array( $record ), 1 * HOUR_IN_SECONDS);
                $response['wprtsp'] = json_encode( $this->get_edd_message($settings_clone[$record], $notification_id) );
                }
            return $response;
        }

        function get_edd_message($record, $notification_id) {
            $settings = $this->get_cpt_settings($notification_id);
            $message = $settings['conversions_sound_notification_markup'].'<span class="geo wprtsp_notification" style="'.$settings['conversions_notification_style'].'">Map</span><span class="wprtsp_text" style="'.$settings['conversions_text_style'].'"><span class="wprtsp_name">' . $record['first_name'] . '</span> purchased <a href="'.$record['product_link'].'">' . $record['product'] . '</a><span class="action" style="'.$settings['conversions_action_style'].'"> ' . $record['time'] . ' ago</span></span>';    
            
            return $message;
        }
        
        function send_wooc_records($response, $data, $screen_id) {
            $records = get_transient('wprtsp_wooc_' . $data['wprtsp']);
            //$response['wprtsp'] = json_encode($records);
            $settings = get_transient('wprtsp_wooc');
            if(false === $settings) {
                $settings = $this->generate_wooc_records();
            }
            if($records) {
                $record = array_shift( $records );
                if(empty($record)) {
                    return;
                }
                $response['wprtsp'] = json_encode( $this->get_wooc_message($record) );
                set_transient('wprtsp_wooc_' . $data['wprtsp'], $records, 1 * HOUR_IN_SECONDS);
            }
            else {
                $record = array_shift( $settings );
                if(empty($record)) {
                    return;
                }
                $records[] = $record;
                set_transient('wprtsp_wooc_' . $data['wprtsp'], $settings, 1 * HOUR_IN_SECONDS);
                $response['wprtsp'] = json_encode( $this->get_wooc_message($record) );
                }
            return $response;
        }

        function get_wooc_message($record) {
            $settings = $this->get_cpt_settings($notification_id);
            $message = $settings['conversions_sound_notification_markup'].'<span class="geo wprtsp_notification" style="'.$settings['conversions_notification_style'].'">Map</span><span class="wprtsp_text" style="'.$settings['conversions_text_style'].'"><span class="wprtsp_name">' . $record['first_name'] . '</span> purchased <a href="'.$record['product_link'].'">' . $record['product'] . '</a><span class="action" style="'.$settings['conversions_action_style'].'"> ' . $record['time'] . ' ago</span></span>';
            
            return $message;
        }

        function send_generated_records( $response, $data, $screen_id, $notification_id = 0 ) {
            $response['wprtsp'] = 'this is a generated record';
            $records = get_transient( 'wprtsp_' . $data['wprtsp'] );
            $settings = \get_post_meta( $notification_id, '_socialproof', true );
            $settings = $settings['records'];
            if( $records ) {
                $record = array_rand( array_diff_key( $settings, $records ));
                if(empty($record)) {
                    return '';
                }
                $records[] = $record;
                set_transient('wprtsp_' . $data['wprtsp'], $records, 1 * HOUR_IN_SECONDS);
                $response['wprtsp'] = json_encode( $settings[$record] );
            }
            else {
                $record = array_rand( $settings );
                if(empty($record)) {
                    return '';
                }
                set_transient( 'wprtsp_' . $data['wprtsp'], array($record), 1 * HOUR_IN_SECONDS );
                $response['wprtsp'] = json_encode( $settings[$record] );
                }
            return $response;
        }

        function make_seed() {
            list($usec, $sec) = explode(' ', microtime());
            return $sec + $usec * 1000000;
        }

        function enqueue_scripts(){
            $notifications  = \get_posts( array( 'post_type' => 'socialproof', 'posts_per_page' => -1 ) );
            $active_notifications = array();
            foreach($notifications as $notification) {
                $meta = \get_post_meta( $notification->ID, '_socialproof', true );
                $show_on = $meta['general_show_on'];
                switch($show_on) {
                    case '1':
                        if(apply_filters('wprtsp_enabled', $meta['conversions_enable'], $meta)) {
                            $social_proof_settings = $this->get_cpt_settings( $notification->ID );
                            wp_enqueue_script( 'wprtsp-fp', $this->uri .'assets/fingerprint2.min.js', array(), null, true);
                            wp_enqueue_script( 'wprtsp-main', $this->uri .'assets/wprtspcpt.js', array('heartbeat','jquery'), null, true);
                            wp_localize_script( 'wprtsp-main', 'wprtsp_vars', json_encode( $social_proof_settings ) );
                        }
                        break;
                    case '2': 
                        if(! is_array($post_ids)) {
                            if(strpos($post_ids, ',') !== false ){
                                $post_ids = explode(',', $post_ids);
                            }
                            else {
                                $post_ids = array($post_ids);
                            }
                        }
                        if( is_singular()  && in_array(get_the_ID(), $post_ids) ) {
                            if(apply_filters('wprtsp_enabled', $meta['conversions_enable'], $meta)) {
                                $social_proof_settings = $this->get_cpt_settings( $notification->ID );
                                wp_enqueue_script( 'wprtsp-fp', $this->uri .'assets/fingerprint2.min.js', array(), null, true);
                                wp_enqueue_script( 'wprtsp-main', $this->uri .'assets/wprtspcpt.js', array('heartbeat','jquery'), null, true);
                                wp_localize_script( 'wprtsp-main', 'wprtsp_vars', json_encode( $social_proof_settings ));
                            }
                        }
                        break;
                    case '3':
                        $post_ids = $meta['general_post_ids'];
                            if(! is_array($post_ids)) {
                                if(strpos($post_ids, ',') !== false ){
                                    $post_ids = explode(',', $post_ids);
                                }
                                else {
                                    $post_ids = array($post_ids);
                                }
                            }
                            if(  ! is_singular() || (is_singular()  && ! in_array(get_the_ID(), $post_ids) ) ) {
                                if(apply_filters('wprtsp_enabled', $meta['conversions_enable'], $meta)) {
                                    $social_proof_settings = $this->get_cpt_settings( $notification->ID );
                                    wp_enqueue_script( 'wprtsp-fp', $this->uri .'assets/fingerprint2.min.js', array(), null, true);
                                    wp_enqueue_script( 'wprtsp-main', $this->uri .'assets/wprtspcpt.js', array('heartbeat','jquery'), null, true);
                                    wp_localize_script( 'wprtsp-main', 'wprtsp_vars', json_encode( $social_proof_settings ) );
                                }
                            }
                        break;
                }
            }
        }

        function get_cpt_settings( $notification_id ){
            $meta = get_post_meta($notification_id, '_socialproof', true);
            $position = $meta['conversions_position'];
            $position_css = '';
            switch($position) {
                case 'bl': $position_css = 'bottom: 10px; left:10px';
                break;
                case 'br': $position_css = 'bottom: 10px; right:10px';
                break;
            }

            $vars = array(
                'id' => (int) $notification_id,
                'url' => $this->uri,
                'conversions_container_style' => apply_filters( 'wprtsp_conversions_container_style', 'display:none; border-radius: 500px; position:fixed; bottom:10px; bottom: 10px; z-index:9999; background: white; margin: 0 0 0 0; box-shadow: 20px 20px 60px 0 rgba(36,35,40,.1); '.$position_css.'; ' ),
                'conversions_notification_style' => apply_filters('wprtsp_conversions_notification_style', 'text-align: center; display: block; height: 48px; width: 48px; float: left; margin-right: .5em; border-radius: 1000px; text-indent:-9999px; background:url(' . $this->uri . 'assets/map.svg ) no-repeat center;' ),
                'conversions_action_style' => apply_filters( 'wprtsp_conversions_action_style', 'margin-top: .5em; display: block; font-weight: 300; color: #aaa; font-size: 12px; line-height: 1em;' ),
                'conversions_text_style' => apply_filters( 'wprtsp_conversions_text_style', 'display:block; font-weight:bold; font-size: 14px; line-height: 1em;white-space: nowrap;' ),
                'conversions_sound_notification' => apply_filters('wprtsp_conversions_sound_notification', ( $meta['conversions_sound_notification'] == '1' ) ? true : false),
                'conversions_sound_notification_markup' => apply_filters( 'wprtsp_conversions_sound_notification_markup','<audio preload="auto" autoplay="true" src="' . $this->uri .'assets/sounds/unsure.mp3">Your browser does not support the <code>audio</code>element.</audio>'),
                'conversions_shop_type' => apply_filters( 'wprtsp_conversions_shop_type', $meta['conversions_shop_type'] ),
                'general_duration' => apply_filters( 'wprtsp_general_duration', (int) $meta['general_duration'] ),
                'general_initial_popup_time' => apply_filters( 'wprtsp_general_initial_popup_time', (int) $meta['general_initial_popup_time'] ),
                'general_subsequent_popup_time' => apply_filters('wprtsp_general_subsequent_popup_time', (int) $meta['general_subsequent_popup_time'] )
            );
            if($vars['conversions_sound_notification'] == false) {
                $vars['conversions_sound_notification_markup'] = '';
            }
            return $vars;
        }

        /* Add links below the plugin name on the plugins page */
        function plugin_action_links($links){
            $links[] = '<a href="https://www.converticacommerce.com?item_name=Donation%20for%20WP%20Social%20Proof&cmd=_donations&currency_code=USD&lc=US&business=shivanand@converticacommerce.com"><strong style="display:inline">Donate</strong></a>';
            return $links;
        }

        /* Enqueue the styles for admin page */
        function plugin_styles(){
            $screen = get_current_screen();
            
            if( $screen->id == 'toplevel_page_' . basename( __FILE__ , '.php') ) {
                wp_enqueue_style( 'wprtsp', $this->uri . 'assets/admin-styles.css' );
            }
            if($screen->post_type == 'socialproof') {
                wp_enqueue_style( 'wprtsp-cpt', $this->uri . 'assets/cpt-styles.css' );
            }
            
        }

        /* Outputs any variable / php objects / arrays in a clear visible frmat */
        function llog($str) {
            echo '<pre>';
            print_r($str);
            echo '</pre>';
        }

        function get_setting( $setting ) {
            $defaults = $this->defaults();
            $settings = wp_parse_args( get_option( 'wprtsp', $defaults ), $defaults );
            return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
        }

        function sanitize( $settings ) {
            $settings['records'] = $this->generate_records($settings);
            $this->generate_edd_records();
            $this->generate_wooc_records();
            $settings['transaction'] = sanitize_text_field($settings['transaction']);
            $settings['transaction_alt'] = sanitize_text_field($settings['transaction_alt']);
            $settings['post_ids'] = sanitize_text_field(  preg_replace("/[^0-9,]/", "", $settings['post_ids']) ) ;
            return $settings;
        }

        function generate_cpt_records($settings){
            
            $transaction = $settings['conversions_transaction'];
            $transaction_alt = $settings['conversions_transaction_alt'];
            $indexes = array();
            reset( $this->names );
            reset( $this->locations );
            for ( $i=0; $i < 100; $i++ ) {
                mt_srand( $this->make_seed() );
                $randval = mt_rand();
                $name = explode(' ', current($this->names));
                $indexes[$i] = array('first_name' => $name[0], 'last_name' => $name[1], 'location' =>  current($this->locations), 'transaction' => ($randval % 2) ? $transaction : $transaction_alt );
                next($this->locations);
                next($this->names);
            }
            return $indexes;
        }

        function generate_edd_records() {
            if(! class_exists('Easy_Digital_Downloads')){
                return false;
            }
            $args = array(
                'numberposts'      => 100,
                'post_status'      => 'publish',			
                'post_type'        => 'edd_payment',
                'suppress_filters' => true, 
                );						
            $payments = get_posts( $args );			
            $records = array();
            if ( $payments ) { 
                foreach ( $payments as $payment_post ) { 
                    setup_postdata($payment_post);
                    $payment      = new \EDD_Payment( $payment_post->ID );
                    if(empty($payment->ID)) {
                        continue;
                    }
                    
                    $payment_time   = human_time_diff(strtotime( $payment->date ), current_time('timestamp'));
                    $customer       = new \EDD_Customer( $payment->customer_id );
                    $downloads = $payment->cart_details;
                    $downloads = array_slice($downloads, 0, 1, true);
                    
                    $records[$payment_post->ID] = array('product_link'=>get_permalink( $downloads[0]['id'] ),'first_name' => $payment->user_info['first_name'], 'last_name' => $payment->user_info['last_name'], 'transaction' => 'purchased', 'product' => $downloads[0]['name'] , 'time' => $payment_time);
                }
                wp_reset_postdata();
            }
            
            return set_transient( 'wprtsp_edd', $records, 30 * MINUTE_IN_SECONDS );
        }

        function generate_wooc_records() {
            if( ! class_exists('WooCommerce') ) {
                return false;
            }
            $query = new \WC_Order_Query( array(
                'limit' => 100,
                'orderby' => 'date',
                'order' => 'DESC',
                'return' => 'ids',
                'status' => 'completed'
            ) );
            $orders = $query->get_orders();
            $customers = array();
            foreach($orders as $purchase) {
                $order = \wc_get_order($purchase);
                
                $user = $order->get_user();
                if(!empty($user)) {
                    $customers[$purchase]['first_name'] = empty($user->user_firstname) ? 'Guest' : $user->user_firstname ;
                    $customers[$purchase]['last_name'] = empty($user->user_lastname) ? 'Guest' : $user->user_lastname ;
                }
                else {
                    $customers[$purchase]['first_name'] = 'Guest';
                    $customers[$purchase]['last_name'] = 'Guest';
                }
                $item = $order->get_items();
                $item = array_shift($item);
                $customers[$purchase]['transaction'] = 'purchased';
                $customers[$purchase]['product'] = $item->get_name();
                $customers[$purchase]['product_link'] = get_permalink($item->get_product_id());
                $time = new \WC_DateTime( $order->get_date_completed() );
                $customers[$purchase]['time'] = human_time_diff($time->getTimestamp());
            }
            return set_transient( 'wprtsp_wooc', $customers, 30 * MINUTE_IN_SECONDS);
        }
    }
}

function wprtsp() {

        return WPRTSP::get_instance();

}

// Let's roll!
wprtsp();
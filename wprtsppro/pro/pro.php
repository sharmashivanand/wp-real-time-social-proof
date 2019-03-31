<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPRTSPLIVE{
    
    static function get_instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup();
			$instance->setup_actions();
		}
		return $instance;
    }

    function setup(){
    }

    function llog($str){
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }

    function setup_actions(){
        add_action( 'wprtsp_add_meta_boxes', array($this, 'add_meta_boxes') );
        add_filter( 'wprtsp_sanitize', array($this, 'sanitize') );
        add_filter( 'wprtsp_get_proof_data_livestats', array($this, 'send_data'), 10, 2);
    }
    
    function add_meta_boxes(){
        add_meta_box( 'social-proof-live', __( 'Live Visitors', 'wprtsp' ), array($this, 'meta_box'), 'socialproof', 'normal');
    }

    function defaults($defaults = array()) {
        $defaults['livestats_enable'] = 1; // bool
        $defaults['livestats_enable_mob'] = 1; // bool
        $defaults['livestats_sound_notification'] = 0; // bool
        $defaults['livestats_sound_notification_file'] = 'salient.mp3'; // string
        $defaults['livestats_title_notification'] = 0; // bool
        $defaults['livestats_threshold'] = 0;
        return $defaults;
    }

    function meta_box(){
        global $post;
        $wprtsp = WPRTSP::get_instance();
        $settings = get_post_meta($post->ID, '_socialproof', true);
        if(! $settings) {
            $settings = $this->defaults();
        }
        
        $settings = $this->sanitize($settings);
        
        $livestats_enable = $settings['livestats_enable'];
        $livestats_enable_mob = $settings['livestats_enable_mob'];
        $livestats_title_notification = $settings['livestats_title_notification'];
        $livestats_sound_notification = $settings['livestats_sound_notification'];
        $livestats_sound_notification_file = $settings['livestats_sound_notification_file'];
        $available_audio = '<select id="wprtsp_livestats_sound_notification_file" name="wprtsp[livestats_sound_notification_file]">';
        $files = array_diff(scandir($wprtsp->dir . 'assets/sounds'), array('.', '..'));
        foreach ($files as $file ) {
            
            $available_audio .= '<option '. disabled( $livestats_sound_notification, false, false) .' value="'.$file.'" '. selected( $livestats_sound_notification_file, $file, false ) .'>'.ucwords(str_replace('-', ' ',explode('.', $file)[0])).'</option>';
        }
        $available_audio .= '</select>';
        ?>
        <table id="tbl_livestats" class="wprtsp_tbl wprtsp_tbl_livestats">
            <thead>
                <tr>
                    <td colspan="2"><h3>Show number of live visitors</h3></td>
                </tr>
                <tr>
                    <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp_livestats_enable">Enable on Desktop</label>
                    </td>
                    <td>
                        <input id="wprtsp_livestats_enable" name="wprtsp[livestats_enable]" type="checkbox" value="1" <?php checked( $livestats_enable, '1' , true); ?>/>
                    </td>
                </tr>
                <tr>
                    <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp_livestats_enable_mob">Enable on Mobile</label>
                    </td>
                    <td>
                        <input id="wprtsp_livestats_enable_mob" name="wprtsp[livestats_enable_mob]" type="checkbox" value="1" <?php checked( $livestats_enable_mob, '1' , true); ?>/>
                    </td>
                </tr>
                <tr>
                    <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Depending on your operating system this will change the browser's tab title to highlight the browser-tab.</p></div></div><label for="wprtsp_livestats_title_notification">Enable Title Notification</label></td>
                    <td><input id="wprtsp_livestats_title_notification" name="wprtsp[livestats_title_notification]" type="checkbox"
                            value="1" <?php checked( 1, $livestats_title_notification, true); ?>/></td>
                </tr>
                <tr>
                    <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Desperate method of grabbing visitor's attention, we agree. Use with extreme caution.</p></div></div><label for="wprtsp_livestats_sound_notification">Enable Sound Notification</label></td>
                    <td><input id="wprtsp_livestats_sound_notification" name="wprtsp[livestats_sound_notification]" type="checkbox"
                            value="1" <?php checked( 1, $livestats_sound_notification, true); ?>/></td>
                </tr>
                <tr>
                    <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Subtle and some exaggerated sounds. Pick wisely. Make sure you preview it.</p></div></div><label for="wprtsp_livestats_sound_notification_file">Choose Audio</label></td>
                    <td>
                        <?php echo $available_audio; ?><span id="livestats_audition_sound" class="dashicons-arrow-right dashicons"></span></td>
                </tr>
                <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Set a minimum threshold so that visitors don't see the notification if too few people are visiting the site.</p></div></div><label for="wprtsp_livestats_threshold">Show only when the number of visitors is greater than</label></td>
                <td><input type="number" class="widefat" id="wprtsp_livestats_threshold" name="wprtsp[livestats_threshold]" value="<?php echo $settings['livestats_threshold'] ?>"/></td>
            </tr>
            </thead>
        </table>
        <script type="text/javascript">
        jQuery('#wprtsp_livestats_sound_notification').change(function() {
                if(jQuery('#wprtsp_livestats_sound_notification').prop('checked')) {
                    jQuery('#wprtsp_livestats_sound_notification_file option').each(function(){
                        if(jQuery(this).attr('disabled')) {
                            jQuery(this).removeAttr('disabled');
                        }
                    });
                }
                else {
                    jQuery('#wprtsp_livestats_sound_notification_file option').each(function(){
                        if(! jQuery(this).attr('selected')) {
                            jQuery(this).attr('disabled','true');
                        }
                    });
                }
            });
            jQuery('#livestats_audition_sound').click(function(){
                wprtsp_livestats_sound_preview = jQuery('#wprtsp_livestats_sound_preview').length ? jQuery('#wprtsp_livestats_sound_preview') : jQuery('<audio/>', {
                    id: 'wprtsp_livestats_sound_preview'
                }).appendTo('body');
                if( ! jQuery('#wprtsp_livestats_sound_notification').prop('checked')) {
                    alert('Cannot play sound if Sound Notification is unchecked.');
                    return;
                }
                
                jQuery('#wprtsp_livestats_sound_preview').attr('src','<?php echo $wprtsp->uri.'assets/sounds/' ?>' + jQuery('#wprtsp_livestats_sound_notification_file').val());
                document.getElementById("wprtsp_livestats_sound_preview").play(); 
            });
            </script>
        <?php
    }

    function sanitize($request = array()){
        $defaults = $this->defaults();
        $request['livestats_enable'] = array_key_exists('livestats_enable', $request) && $request['livestats_enable'] ? 1 : 0;
        $request['livestats_enable_mob'] = array_key_exists('livestats_enable_mob', $request) && $request['livestats_enable_mob'] ? 1 : 0;
        $request['livestats_title_notification'] = array_key_exists('livestats_title_notification', $request) && $request['livestats_title_notification'] ? 1 : 0;
        $request['livestats_sound_notification'] = array_key_exists('livestats_sound_notification', $request) && $request['livestats_sound_notification'] ? 1 : 0;
        $request['livestats_sound_notification_file'] = array_key_exists('livestats_sound_notification_file', $request) ? sanitize_text_field($request['livestats_sound_notification_file'] ) : $defaults['livestats_sound_notification_file'];
        $request['livestats_threshold'] = array_key_exists('livestats_threshold', $request) ? sanitize_text_field($request['livestats_threshold']) : $defaults['livestats_threshold'];
        
        return $request;
    }
    
    function send_data($arr = array(),$settings){
        $livecount = get_transient( 'wpsppro_ga_rtusers' );
        if( false === $livecount ) {
            $ga = get_option('wpsppro_ga_profile');
            $ga = strtr( base64_encode( json_encode($ga )), '+/=', '-_,' );
            $args = array(
                'httpversion' => '1.1',
                'blocking' => true,
                //'headers' => array(),
                'body' => array( 'ga' => $ga, 'auth' => get_site_url(), 'req' => 'ga_livestats' ),
            );
            
            $response  = wp_remote_get( WPRTSPAPIEP, $args);
            
            if ( is_wp_error( $response ) ) {
                $error_message = $response->get_error_message();
                //$this->llog( "Something went wrong: $error_message" );
            }
            else {
                $livecount = wp_remote_retrieve_body($response) ;
                
                $livecount = json_decode($livecount, true);
                $livecount = $livecount['rt:activeUsers'];
                $livecount = empty($livecount) ? '0' : $livecount ;
                //$livestats['ga_rtusers'] = $livecount;
                //$livecount = array('ga_rtusers' => $livecount);
                set_transient( 'wpsppro_ga_rtusers', array('ga_rtusers' => $livecount), 5 * MINUTE_IN_SECONDS );
            }    
        }
        else {
            $livecount = $livecount['ga_rtusers'];
        }
        $threshold = $settings['livestats_threshold'];
        if( $livecount && $livecount > $threshold) {
            $livestats[] = array(
                'line1' => $livecount == 1 ?  $livecount . ' visitor is currently': $livecount . ' people are currently',
                'line2' => 'viewing this site.',
            );
        }
        return $livestats;
    }

}

function wprtsplive() {
	return WPRTSPLIVE::get_instance();
}

wprtsplive();

class WPRTSPHOT{
    
    static function get_instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup();
			$instance->setup_actions();
		}
		return $instance;
    }

    function setup(){
    }

    function llog($str){
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }

    function setup_actions(){
        add_action( 'wprtsp_add_meta_boxes', array($this, 'add_meta_boxes') );
        add_filter( 'wprtsp_sanitize', array($this, 'sanitize') );
        add_filter( 'wprtsp_get_proof_data_hotstats_WooCommerce', array($this, 'send_data_wooc'), 10, 2);
        add_filter( 'wprtsp_get_proof_data_hotstats_Easy_Digital_Downloads', array($this, 'send_data_edd'), 10, 2);
        
    }
    
    function add_meta_boxes(){
        add_meta_box( 'social-proof-hot-stats', __( 'Hot Stats', 'wprtsp' ), array($this, 'meta_box'), 'socialproof', 'normal');
    }

    function defaults($defaults = array()) {
        $defaults['hotstats_enable'] = 1;
        $defaults['hotstats_enable_mob'] = 1;
        $defaults['hotstats_sound_notification'] = 0; // bool
        $defaults['hotstats_sound_notification_file'] = 'salient.mp3'; // string
        $defaults['hotstats_title_notification'] = 0; // bool
        $defaults['hotstats_timeframe'] = 1;
        $defaults['hotstats_timeframes'] = array(1, 2, 3, 7, -1);
        $defaults['hotstats_threshold'] = 0;
        return $defaults;
    }

    function meta_box(){
        global $post;
        $wprtsp = WPRTSP::get_instance();
        $defaults = $this->defaults();
        $settings = get_post_meta( $post->ID, '_socialproof', true );
        if(! $settings) {
            $settings = $defaults;
        }
        $settings = $this->sanitize($settings);
        $hotstats_enable = $settings['hotstats_enable'];
        $hotstats_enable_mob = $settings['hotstats_enable_mob'];
        $hotstats_timeframe = $settings['hotstats_timeframe'];
        
        $hotstats_title_notification = $settings['hotstats_title_notification'];
        $hotstats_sound_notification = $settings['hotstats_sound_notification'];
        $hotstats_sound_notification_file = $settings['hotstats_sound_notification_file'];
        $timeframes = $defaults['hotstats_timeframes'];
        $timeframes_html = '';
        
        foreach($timeframes as $key) {
            if($key == -1) {
                $timeframes_html .= '<option value="' . $key . '" ' . selected( $hotstats_timeframe, $key, false ) .'>Lifetime</option>';
            }
            else {
                $timeframes_html .= '<option value="' . $key . '" ' . selected( $hotstats_timeframe, $key, false ) .'>' . $key . ' days</option>';
            }
        }
        $timeframes_html = '<select id="wprtsp_hotstats_timeframe" name="wprtsp[hotstats_timeframe]">'.$timeframes_html.'</select>';
        $files = array_diff(scandir($wprtsp->dir . 'assets/sounds'), array('.', '..'));
        $available_audio = '<select id="wprtsp_hotstats_sound_notification_file" name="wprtsp[hotstats_sound_notification_file]">';
        foreach ($files as $file ) {
            
            $available_audio .= '<option '. disabled( $hotstats_sound_notification, false, false) .' value="'.$file.'" '. selected( $hotstats_sound_notification_file, $file, false ) .'>'.ucwords(str_replace('-', ' ',explode('.', $file)[0])).'</option>';
        }
        $available_audio .= '</select>';
        ?>
        <table id="tbl_hotstats" class="wprtsp_tbl wprtsp_tbl_hotstats">
            <thead>
                <tr>
                    <td colspan="2">
                        <h3>Show Conversion Milestones over a period of time.</h3>
                    </td>
    
                </tr>
            </thead>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp_hotstats_enable">Enable Hot Stats on Desktop</label></td>
                <td><input id="wprtsp_hotstats_enable" name="wprtsp[hotstats_enable]" type="checkbox" value="1" <?php checked( $hotstats_enable, '1' , true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp_hotstats_enable_mob">Enable Hot Stats on Mobile</label></td>
                <td><input id="wprtsp_hotstats_enable_mob" name="wprtsp[hotstats_enable_mob]" type="checkbox" value="1" <?php checked( $hotstats_enable_mob, '1' , true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Depending on your operating system this will change the browser's tab title to highlight the browser-tab.</p></div></div><label for="wprtsp_hotstats_title_notification">Enable Title Notification</label></td>
                <td><input id="wprtsp_hotstats_title_notification" name="wprtsp[hotstats_title_notification]" type="checkbox"
                        value="1" <?php checked( 1, $hotstats_title_notification, true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Desperate method of grabbing visitor's attention, we agree. Use with extreme caution.</p></div></div><label for="wprtsp_hotstats_sound_notification">Enable Sound Notification</label></td>
                <td><input id="wprtsp_hotstats_sound_notification" name="wprtsp[hotstats_sound_notification]" type="checkbox"
                        value="1" <?php checked( 1, $hotstats_sound_notification, true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Subtle and some exaggerated sounds. Pick wisely. Make sure you preview it.</p></div></div><label for="wprtsp_hotstats_sound_notification_file">Choose Audio</label></td>
                <td>
                    <?php echo $available_audio; ?><span id="hotstats_audition_sound" class="dashicons-arrow-right dashicons"></span></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Select how recent the sales should be.</p></div></div><label for="wprtsp_hotstats_timeframe">Show number of sales since</label></td>
                <td><?php echo $timeframes_html; ?></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Set a minimum threshold so that visitors don't see the notification if too few purchases were made.</p></div></div><label for="wprtsp_hotstats_threshold">Show only when the number of conversions is greater than</label></td>
                <td><input type="number" class="widefat" id="wprtsp_hotstats_threshold" name="wprtsp[hotstats_threshold]" value="<?php echo $settings['hotstats_threshold'] ?>"/></td>
            </tr>
            
        </table>
        <script type="text/javascript">
        jQuery('#wprtsp_hotstats_sound_notification').change(function() {
                if(jQuery('#wprtsp_hotstats_sound_notification').prop('checked')) {
                    jQuery('#wprtsp_hotstats_sound_notification_file option').each(function(){
                        if(jQuery(this).attr('disabled')) {
                            jQuery(this).removeAttr('disabled');
                        }
                    });
                }
                else {
                    jQuery('#wprtsp_hotstats_sound_notification_file option').each(function(){
                        if(! jQuery(this).attr('selected')) {
                            jQuery(this).attr('disabled','true');
                        }
                    });
                }
            });
            jQuery('#hotstats_audition_sound').click(function(){
                wprtsp_hotstats_sound_preview = jQuery('#wprtsp_hotstats_sound_preview').length ? jQuery('#wprtsp_hotstats_sound_preview') : jQuery('<audio/>', {
                    id: 'wprtsp_hotstats_sound_preview'
                }).appendTo('body');
                if( ! jQuery('#wprtsp_hotstats_sound_notification').prop('checked')) {
                    alert('Cannot play sound if Sound Notification is unchecked.');
                    return;
                }
                
                jQuery('#wprtsp_hotstats_sound_preview').attr('src','<?php echo $wprtsp->uri.'assets/sounds/' ?>' + jQuery('#wprtsp_hotstats_sound_notification_file').val());
                document.getElementById("wprtsp_hotstats_sound_preview").play(); 
            });
            </script>
        <?php
    }

    function sanitize($request = array()){
        $defaults = $this->defaults();
        
        $request['hotstats_enable'] = array_key_exists('hotstats_enable', $request) && $request['hotstats_enable'] ? 1 : 0;
        $request['hotstats_enable_mob'] = array_key_exists('hotstats_enable_mob', $request) && $request['hotstats_enable_mob'] ? 1 : 0;
        $request['hotstats_title_notification'] = array_key_exists('hotstats_title_notification', $request) && $request['hotstats_title_notification'] ? 1 : 0;
        $request['hotstats_sound_notification'] = array_key_exists('hotstats_sound_notification', $request) && $request['hotstats_sound_notification'] ? 1 : 0;
        $request['hotstats_sound_notification_file'] = array_key_exists('hotstats_sound_notification_file', $request) ? sanitize_text_field($request['hotstats_sound_notification_file'] ) : $defaults['hotstats_sound_notification_file'];
        $request['hotstats_timeframe'] = array_key_exists('hotstats_timeframe', $request) ? sanitize_text_field($request['hotstats_timeframe']) : $defaults['hotstats_timeframe'];
        $request['hotstats_threshold'] = array_key_exists('hotstats_threshold', $request) ? sanitize_text_field($request['hotstats_threshold']) : $defaults['hotstats_threshold'];
        
        return $request;
    }
    
    function send_data_wooc( $hotstats = array(), $settings ) {
    
        if( ! class_exists('WooCommerce') ) {
            return false;
        }
        
        $value = $settings['hotstats_timeframe'];
        $period = ($value >= 0 ) ?  '>'.( time() - ( $value * DAY_IN_SECONDS ))  : false;
        $args = array(
            'limit' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'ids',
            'status' => 'completed',
            
        );
        if($period) {
            $args['date_created'] = $period;
        }
       
        $orders = wc_get_orders( $args );
        
        $count = count($orders);
        $threshold = $settings['hotstats_threshold'];
        
        if( $count && $count > $threshold) {
            $strtime = $period ? 'in the last ' . $value . ' days.' : 'till date.' ;
            $hotstats[] = array(
                'line1' => $count . ' products sold',
                'line2' => $strtime,
            );
        }
        return $hotstats;
    }

    function send_data_edd( $hotstats , $settings ) {
        if(! class_exists('Easy_Digital_Downloads')){
            return array();
        }
        
        $value = $settings['hotstats_timeframe'];
        $period = ($value >= 0 ) ?  ( time() - ( $value * DAY_IN_SECONDS ))  : false;
        $strtime = $period ? 'in the last ' . $value . ' days.' : 'till date.' ;
        $args = array(
            'numberposts'      => 100,
            'post_status'      => 'publish',			
            'post_type'        => 'edd_payment',
            'suppress_filters' => true, 
            );
        if( $period )	 {
            $args['date_query'] = array(
                    'after' => date('c', $period)
            );
        }
        
        $payments = new WP_Query( $args );			
        $threshold = $settings['hotstats_threshold'];
        
        if ( $payments->post_count > 0 && $payments->post_count > $threshold) {
            
            $hotstats[] = array(
                'line1' => $payments->post_count .' products sold',
                'line2' => $strtime,
            );
            wp_reset_postdata();
        }
        return $hotstats;
    }

}

function wprtsphot() {
	return WPRTSPHOT::get_instance();
}

wprtsphot();

class WPRTSPCTA{
    
    static function get_instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup();
			$instance->setup_actions();
		}
		return $instance;
    }

    function setup(){
    }

    function llog($str){
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }

    function setup_actions(){
        add_action( 'wprtsp_add_meta_boxes', array($this, 'add_meta_boxes') );
        add_filter( 'wprtsp_sanitize', array($this, 'sanitize') );
        add_filter( 'wprtsp_get_proof_data_ctas', array($this, 'send_proof_data'), 10, 2 );
        
    }
    
    function add_meta_boxes(){
        add_meta_box( 'social-proof-custom-message', __( 'Custom Calls To Actions', 'wprtsp' ), array($this, 'meta_box'), 'socialproof', 'normal');
    }

    function defaults($defaults = array()) {
        $defaults['ctas_enable'] = 1;
        $defaults['ctas_enable_mob'] = 1;
        $defaults['ctas_sound_notification'] = 1; // bool
        $defaults['ctas_sound_notification_file'] = 'demonstrative.mp3'; // string
        $defaults['ctas_title_notification'] = 1; // bool
        $defaults['ctas'] = array( array('message' => 'Get 15% discount with coupon INSERTCOUPONHERE', 'button_text' => 'Buy Now', 'link' => ''));
        return $defaults;
    }

    function meta_box(){
        global $post;
        $wprtsp = WPRTSP::get_instance();
        $settings = get_post_meta($post->ID, '_socialproof', true);
        
        if(! $settings) {
            $settings = $this->defaults();
        }
        $defaults = $this->defaults();
        $settings = $this->sanitize($settings);
        
        $ctas = $settings['ctas'];
        $ctas_enable =  $settings['ctas_enable'];
        $ctas_enable_mob =  $settings['ctas_enable_mob'];
        $ctas_title_notification = $settings['ctas_title_notification'];
        $ctas_sound_notification = $settings['ctas_sound_notification'];
        $ctas_sound_notification_file = $settings['ctas_sound_notification_file'];
        $available_audio = '<select id="wprtsp_ctas_sound_notification_file" name="wprtsp[ctas_sound_notification_file]">';
        $files = array_diff(scandir($wprtsp->dir . 'assets/sounds'), array('.', '..'));
        foreach ($files as $file ) {
            
            $available_audio .= '<option '. disabled( $ctas_sound_notification, false, false) .' value="'.$file.'" '. selected( $ctas_sound_notification_file, $file, false ) .'>'.ucwords(str_replace('-', ' ',explode('.', $file)[0])).'</option>';
        }
        $available_audio .= '</select>';
        ?>
        <table id="tbl_ctas" class="wprtsp_tbl wprtsp_tbl_ctas">
        <thead>
                <tr>
                    <td colspan="2">
                        <h3>Add custom calls to action such as offers, discount coupons etc.</h3>
                    </td>

                </tr>
            </thead>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp_ctas_enable">Enable CTAs on Desktop</label></td>
                <td><input id="wprtsp_ctas_enable" name="wprtsp[ctas_enable]" type="checkbox" value="1" <?php checked( $ctas_enable, '1' , true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>You can independently enable and disable this notification on desktop and mobile.</p></div></div><label for="wprtsp_ctas_enable_mob">Enable CTAs on Mobile</label></td>
                <td><input id="wprtsp_ctas_enable_mob" name="wprtsp[ctas_enable_mob]" type="checkbox" value="1" <?php checked( $ctas_enable_mob, '1' , true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Depending on your operating system this will change the browser's tab title to highlight the browser-tab.</p></div></div><label for="wprtsp_ctas_title_notification">Enable Title Notification</label></td>
                <td><input id="wprtsp_ctas_title_notification" name="wprtsp[ctas_title_notification]" type="checkbox"
                        value="1" <?php checked( 1, $ctas_title_notification, true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Desperate method of grabbing visitor's attention, we agree. Use with extreme caution. Recommended only whe you are presenting an impeccable offer.</p></div></div><label for="wprtsp_ctas_sound_notification">Enable Sound Notification</label></td>
                <td><input id="wprtsp_ctas_sound_notification" name="wprtsp[ctas_sound_notification]" type="checkbox"
                        value="1" <?php checked( 1, $ctas_sound_notification, true); ?>/></td>
            </tr>
            <tr>
                <td><div class="wprtsp-help-tip"><div class="wprtsp-help-content"><p>Subtle and some exaggerated sounds. Pick wisely. Make sure you preview it.</p></div></div><label for="wprtsp_ctas_sound_notification_file">Choose Audio</label></td>
                <td>
                    <?php echo $available_audio; ?><span id="ctas_audition_sound" class="dashicons-arrow-right dashicons"></span></td>
            </tr>
        </table>
        <table id="ctas-fieldset-one" width="100%" class="wprtsp_tbl">
            <thead>
                <tr>
                    <th width="40%">Message</th>
                    <th width="15%">Button Text</th>
                    <th width="35%">Click-Through Link</th>
                    <th width="10%"></th>
                </tr>
            </thead>
            <tbody>
                    <?php
                
                    $count = count($ctas);
                
                    for($i = 0 ; $i < $count; $i++) {
                        $elem = array_shift($ctas);
                        
                    ?>
                        <tr>
                            <td><input type="text" class="widefat" name="wprtsp[ctas][<?php echo $i ?>][message]" value="<?php echo $elem['message']; ?>" /></td>
                            <td><input type="text" class="widefat" name="wprtsp[ctas][<?php echo $i ?>][button_text]" value="<?php echo $elem['button_text']; ?>" /></td>
                            <td><input type="url" class="widefat" name="wprtsp[ctas][<?php echo $i ?>][link]" value="<?php echo $elem['link']; ?>" /></td>
                            <td><a class="button remove-row" href="#">Remove</a></td>
                        </tr>
                    <?php
                    
                    }
                    ?>
                    <tr class="empty-row screen-reader-text">
                        <td><input type="text" id="ctas_message_empty" class="widefat" name="wprtsp[ctas][<?php echo $i ?>][message]" /></td>
                        <td><input type="text" id="ctas_button_text_empty" class="widefat" name="wprtsp[ctas][<?php echo $i ?>][button_text]" /></td>
                        <td><input type="text" id="ctas_link_empty" class="widefat" name="wprtsp[ctas][<?php echo $i ?>][link]" /></td>
                    <td><a class="button remove-row" href="#">Remove</a></td>
                </tr>
            </tbody>
        </table>
        <p><a id="add-row" class="button" href="#">Add another</a></p>
        <script type="text/javascript">
        jQuery( '#add-row' ).on('click', function() {
            $time = new Date().getTime();
                var row = jQuery( '.empty-row.screen-reader-text' ).clone(true);
                row.removeClass( 'empty-row screen-reader-text' );
                row.insertBefore( '#ctas-fieldset-one tbody>tr:last' );
                jQuery('#ctas_message_empty').attr('name', function(){jQuery(this).removeAttr('id'); return 'wprtsp[ctas]['+$time+'][message]'});
                jQuery('#ctas_button_text_empty').attr('name', function(){jQuery(this).removeAttr('id');return 'wprtsp[ctas]['+$time+'][button_text]'});
                jQuery('#ctas_link_empty').attr('name', function(){jQuery(this).removeAttr('id');return 'wprtsp[ctas]['+$time+'][link]'});
                return false;
            });
            
            jQuery( '.remove-row' ).on('click', function() {
                jQuery(this).parents('tr').remove();
                return false;
            });
            jQuery('#wprtsp_ctas_sound_notification').change(function() {
                if(jQuery('#wprtsp_ctas_sound_notification').prop('checked')) {
                    jQuery('#wprtsp_ctas_sound_notification_file option').each(function(){
                        if(jQuery(this).attr('disabled')) {
                            jQuery(this).removeAttr('disabled');
                        }
                    });
                }
                else {
                    jQuery('#wprtsp_ctas_sound_notification_file option').each(function(){
                        if(! jQuery(this).attr('selected')) {
                            jQuery(this).attr('disabled','true');
                        }
                    });
                }
            });
            jQuery('#ctas_audition_sound').click(function(){
                wprtsp_ctas_sound_preview = jQuery('#wprtsp_ctas_sound_preview').length ? jQuery('#wprtsp_ctas_sound_preview') : jQuery('<audio/>', {
                    id: 'wprtsp_ctas_sound_preview'
                }).appendTo('body');
                if( ! jQuery('#wprtsp_ctas_sound_notification').prop('checked')) {
                    alert('Cannot play sound if Sound Notification is unchecked.');
                    return;
                }
                
                jQuery('#wprtsp_ctas_sound_preview').attr('src','<?php echo $wprtsp->uri.'assets/sounds/' ?>' + jQuery('#wprtsp_ctas_sound_notification_file').val());
                document.getElementById("wprtsp_ctas_sound_preview").play(); 
            });
        </script>
    <?php
    }

    function sanitize($request = array()){
        $defaults = $this->defaults();
        
        $request['ctas_enable'] = array_key_exists('ctas_enable', $request) && $request['ctas_enable'] ? 1 : 0;
        $request['ctas_enable_mob'] = array_key_exists('ctas_enable_mob', $request) && $request['ctas_enable_mob'] ? 1 : 0;
        $request['ctas_title_notification'] = array_key_exists('ctas_title_notification', $request) && $request['ctas_title_notification'] ? 1 : 0;
        $request['ctas_sound_notification'] = array_key_exists('ctas_sound_notification', $request) && $request['ctas_sound_notification'] ? 1 : 0;
        $request['ctas_sound_notification_file'] = array_key_exists('ctas_sound_notification_file', $request) ? sanitize_text_field($request['ctas_sound_notification_file'] ) : $defaults['ctas_sound_notification_file'];
    
        if(array_key_exists('ctas', $request)) {
            $ctas = $request['ctas'];
            foreach($ctas as $cta => $value) {
            
                if( empty($value['message'] ) && empty( $value['button_text']) && empty($value['link']) ) {
                    unset($ctas[$cta]);
                }
            }
        }
    
        $request['ctas'] = isset($ctas)? array_values($ctas) : $defaults['ctas'];

        return $request;
    }
    
    function send_proof_data($ctas, $settings){

        $ctas = $settings['ctas'];
        return array_values($ctas);

    }

}

function wprtspcta() {
	return WPRTSPCTA::get_instance();
}

wprtspcta();

if(file_exists(trailingslashit(__DIR__) . 'updater.php' ) ) {
    include_once(trailingslashit(__DIR__) . 'updater.php');
}
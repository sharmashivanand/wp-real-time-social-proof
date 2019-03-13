<?php

class WPRTSPGENERAL{
    
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

    function setup_actions(){
        add_action('wprtsp_add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('wprtsp_cpt_defaults', array($this, 'defaults'));
        add_filter('wprtsp_sanitize', array($this, 'sanitize'));
    }
    
    function add_meta_boxes(){
        add_meta_box( 'social-proof-general', __( 'General', 'erm' ), array($this, 'general_meta_box'), 'socialproof', 'normal');
        add_meta_box( 'social-proof-conversions', __( 'Conversions', 'erm' ), array($this, 'conversions_meta_box'), 'socialproof', 'normal');
    }

    function defaults($defaults = array()){
        $defaults['general_show_on'] = '1';
        $defaults['general_duration'] = '4';
        $defaults['general_initial_popup_time'] = '5';
        $defaults['general_subsequent_popup_time'] = '30';
        $defaults['general_post_ids'] = get_option( 'page_on_front');
        $defaults['general_position'] = 'bl';
        
        $defaults['positions'] = array('bl' => 'Bottom Left', 'br' => 'Bottom Right');
        
        /* Additional routines */
        //$defaults['conversions_records'] = $this->generate_cpt_records(array('conversions_transaction' => 'subscribed to the newsletter', 'conversions_transaction_alt' => 'registered for the webinar'));

        return $defaults;
    }

    function sanitize($request = array()){
        $defaults = $this->defaults();
        $request['general_show_on'] = array_key_exists('general_show_on' ,$request) ? sanitize_text_field($request['general_show_on'] ) : $defaults['general_show_on'];
        $request['general_post_ids'] = array_key_exists('general_post_ids' ,$request) ? sanitize_text_field($request['general_post_ids'] ) : $defaults['general_post_ids'];
        $request['general_position'] = array_key_exists('general_position' ,$request) ? sanitize_text_field($request['general_position'] ) : $defaults['general_position'];
        $request['general_initial_popup_time'] = array_key_exists('general_initial_popup_time' ,$request) ? sanitize_text_field($request['general_initial_popup_time'] ) : $defaults['general_initial_popup_time'];
        $request['general_duration'] = array_key_exists('general_duration' ,$request) ? sanitize_text_field($request['general_duration'] ) : $defaults['general_duration'];
        $request['general_subsequent_popup_time'] = array_key_exists('general_subsequent_popup_time' ,$request) ? sanitize_text_field($request['general_subsequent_popup_time'] ) : $defaults['general_subsequent_popup_time'];

        return $request;
    }

    function general_meta_box(){
        global $post;
        wp_nonce_field( 'socialproof_meta_box_nonce', 'socialproof_meta_box_nonce' );
        $settings = get_post_meta( $post->ID, '_socialproof', true );

        $defaults = $this->defaults();
        $settings = wp_parse_args( $settings, $defaults );
        $show_on = $settings['general_show_on'];
        $post_ids = $settings['general_post_ids'];
        $duration = $settings['general_duration'];
        $initial_popup_time = $settings['general_initial_popup_time'];
        $subsequent_popup_time = $settings['general_subsequent_popup_time'];
        $general_position = array_key_exists('general_position', $settings) ? $settings['general_position'] : 'bl';
        $positions_html = '';
        $positions = $defaults['positions'];
        foreach($positions as $key=>$value) {
            $positions_html .= '<option value="' . $key . '" ' . selected( $general_position, $key, false ) .'>'. preg_replace('/[^\da-z]/i',' ', $value) .'</option>';
        }
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
            <tr>
                <td><label for="wprtsp[general_position]">Position</label></td>
                <td><select id="wprtsp[general_position]" name="wprtsp[general_position]">
                        <?php echo $positions_html; ?>
                    </select></td>
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
        $( document ).ready(function() {
            $('#wprtsp_general_show_on').on('change',  function() {
                if($('#wprtsp_general_show_on').val() == 1 ) {
                    $('#wprtsp_general_post_ids').attr('readonly', 'true');
                }
                else {
                    $('#wprtsp_general_post_ids').removeAttr('readonly');
                }
            });
        });
        </script>
        <?php

    }

}

class WPRTSPCONVERSION{
    
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

    function setup_actions(){
        add_action('wprtsp_add_meta_boxes', array($this, 'add_meta_boxes'));
        add_filter('wprtsp_cpt_defaults', array($this, 'defaults'));
        add_filter('wprtsp_sanitize', array($this, 'sanitize'));
    }
    
    function add_meta_boxes(){
        add_meta_box( 'social-proof-conversions', __( 'Conversions', 'erm' ), array($this, 'conversions_meta_box'), 'socialproof', 'normal');
    }
    
    function conversions_meta_box(){
        global $post;
        $defaults = $this->defaults();
        $settings = get_post_meta($post->ID, '_socialproof', true);
        if(! $settings) {
            $settings = $defaults;
        }
        $settings = $this->sanitize($settings);

        //$conversions_enable = $settings['conversions_enable'];
        $conversions_shop_type = $settings['conversions_shop_type'];
        //$conversions_transaction = $settings['conversions_transaction'];
        //$conversions_transaction_alt = $settings['conversions_transaction_alt'];
        $conversion_generated_action = $settings['conversion_generated_action'];
        $conversion_generated_product = $settings['conversion_generated_product'];
        $conversion_template_line1 = $settings['conversion_template_line1'];
        $conversion_template_line2 = $settings['conversion_template_line2'];
        $conversions_sound_notification = $settings['conversions_sound_notification'];
        
        $sources = array();
        if(class_exists('Easy_Digital_Downloads')) {
            $sources[] = 'Easy_Digital_Downloads';
        }
        if( class_exists( 'WooCommerce' ) ) {
            $sources[] = 'WooCommerce';
        }
        $sources[] = 'Generated';
        
        $sources = apply_filters('wprtsp_shop_type', $sources);

        $sources_html = '';
        foreach($sources as $key=>$value) {
            $sources_html .= '<option value="' . $value . '" ' . selected( $conversions_shop_type, $value, false ) .'>'. preg_replace('/[^\da-z]/i',' ', $value) .'</option>';
        }
       
        ?>
        <table id="tbl_conversions" class="wprtsp_tbl wprtsp_tbl_conversions">
            <tr>
                <td colspan="2">
                    <h3>Conversions</h3>
                </td>
            </tr>
            <tr>
                <td><label for="wprtsp_conversions_shop_type">Source</label></td>
                <td><select id="wprtsp_conversions_shop_type" name="wprtsp[conversions_shop_type]">
                        <?php echo $sources_html; ?>
                    </select></td>
            </tr>
            <tr>
                <td>Template</td>
                <td>
                <label>Line 1: <input type="text" value="<?php echo $conversion_template_line1; ?>" name="wprtsp[conversion_template_line1]" class="widefat" /></label><br />
                <label>Line 2: <input type="text" value="<?php echo $conversion_template_line2; ?>" name="wprtsp[conversion_template_line2]" class="widefat"/></label>
                </td>
            </tr>
            <tr class="generated_transactions">
                <td><label for="wprtsp_conversion_generated_action">Action for Generated records</label></td>
                <td><input id="wprtsp_conversion_generated_action" <?php if($conversions_shop_type != 'Generated') {echo 'readonly="true"';} ?> name="wprtsp[conversion_generated_action]" type="text" class="widefat" value="<?php echo $conversion_generated_action ?>" /></td>
            </tr>
            <tr class="generated_transactions">
                <td><label for="wprtsp_conversion_generated_product">Product for Generated records</label></td>
                <td><input id="wprtsp_conversion_generated_product" <?php if($conversions_shop_type != 'Generated') {echo 'readonly="true"';} ?> name="wprtsp[conversion_generated_product]" type="text" class="widefat" value="<?php echo $conversion_generated_product ?>" /></td>
            </tr>
            <tr>
                <td><label for="wprtsp[conversions_sound_notification]">Enable Sound Notification</label></td>
                <td><input id="wprtsp[conversions_sound_notification]" name="wprtsp[conversions_sound_notification]" type="checkbox" value="1" <?php checked( $conversions_sound_notification, '1' , true); ?>/></td>
            </tr>
        </table>
        <script type="text/javascript">
        $( document ).ready(function() {
            $('#wprtsp_conversions_shop_type').on('change',  function() {
                if($('#wprtsp_conversions_shop_type').val() == 'Generated' ) {
                    //$('#wprtsp_conversions_transaction').removeAttr('readonly');
                    $('#wprtsp_conversion_generated_action').removeAttr('readonly');
                    $('#wprtsp_conversion_generated_product').removeAttr('readonly');
                }
                else {
                    //$('#wprtsp_conversions_transaction').attr('readonly', 'true');
                    $('#wprtsp_conversion_generated_action').attr('readonly', 'true');
                    $('#wprtsp_conversion_generated_product').attr('readonly', 'true');
                }
            });
        });
        
        </script>
        <?php
        $wprtsp = WPRTSP::get_instance();
        $wprtsp->llog( $settings );
    }

    function defaults($defaults = array()){

        //$defaults['conversions_enable'] = true;
        //$defaults['conversions_show_on_mobile'] = true;
        $defaults['conversions_shop_type'] = class_exists('Easy_Digital_Downloads') ?  'Easy_Digital_Downloads' : ( class_exists( 'WooCommerce' ) ?  'WooCommerce' :  'Generated');
        $defaults['conversion_template_line1'] = '{name} {location}';
        $defaults['conversion_template_line2'] = 'just {action} {product} {time}.';
        $defaults['conversion_generated_action'] = 'subscribed to the';
        $defaults['conversion_generated_product'] = 'newsletter';
        //$defaults['conversions_transaction'] = 'subscribed to the newsletter';
        //$defaults['conversions_transaction_alt'] = 'registered for the webinar';
        $defaults['conversions_sound_notification'] = 0;

        return $defaults;
    }

    function sanitize($request = array()){
        //$request['conversions_enable'] = array_key_exists('conversions_enable' ,$request) && $request['conversions_enable'] ? true : false;
        $request['conversions_shop_type'] = array_key_exists('conversions_shop_type' ,$request) ? sanitize_text_field($request['conversions_shop_type'] ) : $defaults['conversions_shop_type'];
        $request['conversion_template_line1'] = array_key_exists('conversion_template_line1', $request) ? sanitize_text_field($request['conversion_template_line1']) :  $defaults['conversion_template_line1'];
        $request['conversion_template_line2'] = array_key_exists('conversion_template_line2', $request) ? sanitize_text_field($request['conversion_template_line2']) :  $defaults['conversion_template_line2'];
        $request['conversion_generated_action'] = array_key_exists('conversion_generated_action' ,$request) ? sanitize_text_field($request['conversion_generated_action'] ) : $defaults['conversion_generated_action'];
        $request['conversion_generated_product'] = array_key_exists('conversion_generated_product' ,$request) ? sanitize_text_field($request['conversion_generated_product'] ) : $defaults['conversion_generated_product'];
        //$settings['conversions_transaction'] = array_key_exists('conversions_transaction' ,$request) ? sanitize_text_field($request['conversions_transaction'] ) : $defaults['conversions_transaction'];
        //$settings['conversions_transaction_alt'] = array_key_exists('conversions_transaction_alt' ,$request) ? sanitize_text_field($request['conversions_transaction_alt'] ) : $defaults['conversions_transaction_alt'];
        $request['conversions_sound_notification'] = array_key_exists('conversions_sound_notification' , $request) && $request['conversions_sound_notification'] ? 1 : 0;
        return $request;
    }
}

function wprtspgeneral() {
	return WPRTSPGENERAL::get_instance();
}

function wprtspconversion() {
	return WPRTSPCONVERSION::get_instance();
}

wprtspgeneral();

wprtspconversion();
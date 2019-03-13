wprtsp_pop = false;
var clock = false;
var data = {};
var debug = 1;
function llog(message) {
    if (debug) {
        console.dir(message);
    }
}

if (jQuery) {
    jQuery.fn.updateProof = function (message) {

        if (settings.conversions_sound_notification) {
            try {
                var wprtsp_audio = jQuery('#wprtsp_audio').length ? jQuery('#wprtsp_audio') : jQuery('<audio/>', {
                    id: 'wprtsp_audio',
                    class: 'wprtsp_audio',
                    preload: 'auto',
                    autoplay: false,
                    src: `${settings.url}assets/sounds/unsure.mp3`
                }).appendTo('body');
                jQuery('#wprtsp_audio').attr('src', `${settings.url}assets/sounds/unsure.mp3`);
                var playPromise = jQuery('#wprtsp_audio')[0].play();
                if (playPromise !== undefined) {
                    playPromise.then(_ => {
                        llog('success');
                    })
                        .catch(error => {
                            llog(playPromise);
                            llog(error);
                        });
                }
            }
            catch (e) {
                llog('couldn\'t play sound' + e);
            }
        }
        llog('updating proof');
        jQuery("#wprtsp_pop").css('border-radius', '1000px');
        jQuery("#wprtsp_pop").contents().find("#wprtsp").html(message);
        jQuery("#wprtsp_pop").contents().find("#wprtsp_wrap").attr('class', 'conversions');
        jQuery('#wprtsp_pop').css('height', jQuery("#wprtsp_pop").contents().find("html").height());
        jQuery('#wprtsp_pop').css('width', jQuery("#wprtsp_pop").contents().find("body").width());
        height = jQuery("#wprtsp_pop").contents().find("html").height();
        return this;
    }

    jQuery.fn.updatePosition = function () {
        jQuery('#wprtsp_pop').css('bottom', '-' + (height + 10) + 'px');
        return this;
    }

    function clearProof() {
        //jQuery("#cta-grow").removeClass('cta-grow');
        clock = setTimeout(wprtsp_show_message, settings.general_subsequent_popup_time * 1000);
        return this;
    }

    function wprtsp_show_message() {

        prime1 = [3, 7, 13, 19, 29];
        prime2 = [5, 11, 17, 23, 31];

        wprtsp_init = {
            _ajax_nonce: settings.ajax_nonce,
            action: "wprtsp_get_message",
            timestamp: new Date().getTime(),
            data: data
        };

        jQuery.ajax({
            type: "post",
            dataType: "html",
            url: settings.ajaxurl,
            data: wprtsp_init,
            success: function (response) {

                if (settings.conversions_shop_type == 'Generated') {
                    llog('response:' + response);
                    try {
                        response = JSON.parse(response);
                    }
                    catch(e){
                        llog(e);
                        clearTimeout(clock);
                        llog('stopping clock');
                        return;
                    }
                    if (!prime1.length) {
                        prime1 = [5, 11, 17, 23, 31];
                    }
                    if (!prime2.length) {
                        prime2 = [3, 7, 13, 19, 29];
                    }
                    when = Date.now();
                    if (when % 2) {
                        when = prime1.pop();
                    } else {
                        when = prime2.shift();
                    }
                    message = settings.conversions_sound_notification_markup + `<div id="wprtsp_wrap" class="wprtsp-conversion">
                    <span class="wprtsp_left"></span>
                    <div class="wprtsp_right">
                        <div class="wprtsp_line1"><span class="wprtsp_name">${response.first_name}</span> from <span class="wprtsp_location">${response.location.city}, ${response.location.state}</span></div>
                        <div class="wprtsp_line2"><span class="wprtsp_action" style="${settings.conversions_action_style}">${response.transaction} ${when} minutes ago</span></div>
                    </div>
                </div>`
                }
                if (settings.conversions_shop_type == 'Easy_Digital_Downloads' || settings.conversions_shop_type == 'WooCommerce') {
                    response = JSON.parse(response);
                    message = settings.conversions_sound_notification_markup + response;
                }
                jQuery('#wprtsp_pop').updateProof(message).updatePosition().animate({ "bottom": "10px", 'opacity': '1' }, { duration: 300, complete: function () { /*jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'grow ' + settings.general_duration + 's ease-in-out'); */ } }).delay(settings.general_duration * 1000).animate({ "bottom": '-' + height + 'px', 'opacity': '0' }, { duration: 300, complete: function () { clearProof(); jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'none'); } });
                jQuery('#wprtsp_pop').mouseover(function () {
                    clearTimeout(clock);
                    llog('stopping clock');
                    wprtsp_pop.stop(true, true);//.show(200);
                }).mouseout(function () {
                    wprtsp_pop.stop(true, true).delay(200).animate({ "bottom": '-' + height + 'px', 'opacity': '0' }, { duration: 300, complete: function () { clearProof(); jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'none'); clock = setTimeout(wprtsp_show_message, settings.general_subsequent_popup_time * 1000); llog('restarting clock');} })
                    
                });
            },
            error: function (xhr, status, err) {
                clearTimeout(clock);
            },
            complete: function (xhr, status) {
                //clearTimeout(clock);
            }
        });
    }

    function wprtsp_get_message() {

        return;
        if (data.hasOwnProperty('wprtsp')) {
            if (settings.conversions_shop_type == 'Generated') {
                if (!prime1.length) {
                    prime1 = [5, 11, 17, 23, 31];
                }
                if (!prime2.length) {
                    prime2 = [3, 7, 13, 19, 29];
                }
                when = Date.now();
                if (when % 2) {
                    when = prime1.pop();
                } else {
                    when = prime2.shift();
                }
                message = `<div id="wprtsp_wrap">${settings.conversions_sound_notification_markup}<span class="geo wpsrtp_notification" style="${settings.conversions_notification_style}">Map</span><span class="wprtsp_text" style="${settings.conversions_text_style}"><span class="wprtsp_name">${result.first_name}</span> from <span class="wprtsp_location">${result.location.city}, ${result.location.state}</span> <span class="wprtsp_action" style="${settings.conversions_action_style}">${result.transaction} ${when} minutes ago</span></span></div>`;

                message = `<div id="wprtsp_wrap" class="wprtsp-conversion">
                <span class="wprtsp_left"></span>
                <div class="wprtsp_right">
                    <div class="wprtsp_line1"><span class="wprtsp_name">${result.first_name}</span> from <span class="wprtsp_location">${result.location.city}, ${result.location.state}</span></div>
                    <div class="wprtsp_line2"><span class="wprtsp_action" style="${settings.conversions_action_style}">${result.transaction} ${when} minutes ago</span></div>
                </div>
            </div>`;
            }
            if (settings.conversions_shop_type == 'Easy_Digital_Downloads' || settings.conversions_shop_type == 'WooCommerce') {
                message = settings.conversions_sound_notification_markup + result;
                /*
                jQuery("#wprtsp_pop").slideDown(200, function () {
                    jQuery("#wprtsp_pop").contents().find("#wprtsp").html('<div id="wprtsp_wrap">' + settings.conversions_sound_notification_markup + result + '</div>');
                    jQuery('#wprtsp_pop').css('height', jQuery("#wprtsp_pop").contents().find("html").height());
                    jQuery('#wprtsp_pop').css('width', jQuery("#wprtsp_pop").contents().find("body").width());
                }).delay(settings.general_duration * 1000).fadeOut(2000);
                */
                //.contents().find("#wprtsp").html(result).slideDown(200).delay(settings.general_duration * 1000).fadeOut(2000);
            }

        }
    }

    jQuery(document).ready(function ($) {
        time = Date.now();
        settings = JSON.parse(wprtsp_vars);
        console.dir(settings);
        wprtsp_pop = jQuery('#wprtsp_pop').length ? jQuery('#wprtsp_pop') : jQuery('<iframe/>', {
            id: 'wprtsp_pop',
            frameborder: '0',
            scrolling: 'no',
            class: 'wprtsp_pop',
            style: settings.conversions_container_style,
            srcdoc: '<html><head><base target="_parent"><link rel="stylesheet" type="text/css" href="' + settings.url + 'assets/proof-styles.css"></head><body id="wprtsp"><img src="' + settings.url + 'assets/map.svg" /></body></html>',
        }).appendTo('body');

        //wp.heartbeat.interval(settings.general_initial_popup_time);

        new Fingerprint2().get(function (result, components) {
            //jQuery(document).on('heartbeat-send', function (e, data) {

            //data = {};
            data['wprtsp'] = result + '_' + time;
            //console.log(data['wprtsp']);
            data['wprtsp_notification_id'] = settings.id;
            llog(data);
            //});
        })
        clock = setTimeout(wprtsp_show_message, settings.general_initial_popup_time * 1000);

        jQuery(document).on('heartbeat-tick', function (event, data) {
            if (data.hasOwnProperty('wprtsp')) {
                wp.heartbeat.interval(settings.general_subsequent_popup_time); // don't pop-up too much

                //wprtsp_pop.attr('style', settings.conversions_container_style);
                result = JSON.parse(data['wprtsp']);
                //console.log(result);
                var message;
                if (settings.conversions_shop_type == 'Generated') {
                    if (!prime1.length) {
                        prime1 = [5, 11, 17, 23, 31];
                    }
                    if (!prime2.length) {
                        prime2 = [3, 7, 13, 19, 29];
                    }
                    when = Date.now();
                    if (when % 2) {
                        when = prime1.pop();
                    } else {
                        when = prime2.shift();
                    }
                    message = `<div id="wprtsp_wrap">${settings.conversions_sound_notification_markup}<span class="geo wpsrtp_notification" style="${settings.conversions_notification_style}">Map</span><span class="wprtsp_text" style="${settings.conversions_text_style}"><span class="wprtsp_name">${result.first_name}</span> from <span class="wprtsp_location">${result.location.city}, ${result.location.state}</span> <span class="wprtsp_action" style="${settings.conversions_action_style}">${result.transaction} ${when} minutes ago</span></span></div>`;

                    message = `<div id="wprtsp_wrap" class="wprtsp-conversion">
                        <span class="wprtsp_left"></span>
                        <div class="wprtsp_right">
                            <div class="wprtsp_line1"><span class="wprtsp_name">${result.first_name}</span> from <span class="wprtsp_location">${result.location.city}, ${result.location.state}</span></div>
                            <div class="wprtsp_line2"><span class="wprtsp_action" style="${settings.conversions_action_style}">${result.transaction} ${when} minutes ago</span></div>
                        </div>
                    </div>`;

                    /*
                    jQuery("#wprtsp_pop").slideDown(200, function () {
                        jQuery("#wprtsp_pop").contents().find("#wprtsp").html(html);
                        jQuery('#wprtsp_pop').css('height', jQuery("#wprtsp_pop").contents().find("html").height());
                        jQuery('#wprtsp_pop').css('width', jQuery("#wprtsp_pop").contents().find("body").width());
                    }).delay(settings.general_duration * 1000).fadeOut(2000);
                    */
                }
                if (settings.conversions_shop_type == 'Easy_Digital_Downloads' || settings.conversions_shop_type == 'WooCommerce') {
                    message = settings.conversions_sound_notification_markup + result;
                    /*
                    jQuery("#wprtsp_pop").slideDown(200, function () {
                        jQuery("#wprtsp_pop").contents().find("#wprtsp").html('<div id="wprtsp_wrap">' + settings.conversions_sound_notification_markup + result + '</div>');
                        jQuery('#wprtsp_pop').css('height', jQuery("#wprtsp_pop").contents().find("html").height());
                        jQuery('#wprtsp_pop').css('width', jQuery("#wprtsp_pop").contents().find("body").width());
                    }).delay(settings.general_duration * 1000).fadeOut(2000);
                    */
                    //.contents().find("#wprtsp").html(result).slideDown(200).delay(settings.general_duration * 1000).fadeOut(2000);
                }

                jQuery('#wprtsp_pop').updateProof(message).updatePosition().animate({ "bottom": "10px", 'opacity': '1' }, { duration: 300, complete: function () { jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'grow ' + settings.general_duration + 's ease-in-out'); } }).delay(settings.general_duration * 1000).animate({ "bottom": '-' + height + 'px', 'opacity': '0' }, { duration: 300, complete: function () { clearProof(); /*jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'none'); */ } });

            }
            jQuery('#wprtsp_pop').mouseover(function () {
                wprtsp_pop.stop(true, true).show(200);
            }).mouseout(function () {
                wprtsp_pop.stop(true, true).delay(200).fadeOut(2000);
            });
        });


    });
}
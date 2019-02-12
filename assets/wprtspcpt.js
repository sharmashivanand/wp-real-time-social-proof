wprtsp_pop = false;
if (jQuery) {
    jQuery(document).ready(function ($) {
        time = Date.now();
        wprtsp_settings = JSON.parse(wprtsp_vars);
        console.dir(wprtsp_settings);
        wprtsp_pop = jQuery('#wprtsp_pop').length ? jQuery('#wprtsp_pop') : jQuery('<iframe/>', {
            id: 'wprtsp_pop',
            frameborder: '0',
            scrolling: 'no',
            class: 'wprtsp_pop',
            style: wprtsp_settings.conversions_container_style,
            srcdoc: '<html><head><base target="_parent"><link rel="stylesheet" type="text/css" href="' + wprtsp_settings.url + 'assets/proof-styles.css"></head><body id="wprtsp"><img src="' + wprtsp_settings.url + 'assets/map.svg" /></body></html>',
        }).appendTo('body');
        if (wprtsp_settings.conversions_sound_notification) {
            var wprtsp_audio = jQuery('#wprtsp_audio').length ? jQuery('#wprtsp_audio') : jQuery('<audio/>', {
                id: 'wprtsp_audio',
                class: 'wprtsp_audio',
                preload: 'auto',
                src: `${wprtsp_settings.url}assets/sounds/unsure.mp3`
            }).appendTo('body');
        }
        wp.heartbeat.interval(wprtsp_settings.general_initial_popup_time);
        new Fingerprint2().get(function (result, components) {
            jQuery(document).on('heartbeat-send', function (e, data) {
                data['wprtsp'] = result + '_' + time;
                //console.log(data['wprtsp']);
                data['wprtsp_notification_id'] = wprtsp_settings.id;
            });
        })
        prime1 = [3, 7, 13, 19, 29];
        prime2 = [5, 11, 17, 23, 31];

        jQuery(document).on('heartbeat-tick', function (event, data) {
            if (data.hasOwnProperty('wprtsp')) {
                wp.heartbeat.interval(wprtsp_settings.general_subsequent_popup_time); // don't pop-up too much

                //wprtsp_pop.attr('style', wprtsp_settings.conversions_container_style);
                result = JSON.parse(data['wprtsp']);
                //console.log(result);
                if (wprtsp_settings.conversions_shop_type == 'Generated') {
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
                    var html = `<div id="wprtsp_wrap">${wprtsp_settings.conversions_sound_notification_markup}<span class="geo wpsrtp_notification" style="${wprtsp_settings.conversions_notification_style}">Map</span><span class="wprtsp_text" style="${wprtsp_settings.conversions_text_style}"><span class="wprtsp_name">${result.first_name}</span> from <span class="wprtsp_location">${result.location.city}, ${result.location.state}</span> <span class="wprtsp_action" style="${wprtsp_settings.conversions_action_style}">${result.transaction} ${when} minutes ago</span></span></div>`;

                    jQuery("#wprtsp_pop").slideDown(200, function () {
                        jQuery("#wprtsp_pop").contents().find("#wprtsp").html(html);
                        jQuery('#wprtsp_pop').css('height', jQuery("#wprtsp_pop").contents().find("html").height());
                        jQuery('#wprtsp_pop').css('width', jQuery("#wprtsp_pop").contents().find("body").width());
                    }).delay(wprtsp_settings.general_duration * 1000).fadeOut(2000);
                }
                if (wprtsp_settings.conversions_shop_type == 'Easy_Digital_Downloads' || wprtsp_settings.conversions_shop_type == 'WooCommerce') {
                    jQuery("#wprtsp_pop").slideDown(200, function () {
                        jQuery("#wprtsp_pop").contents().find("#wprtsp").html('<div id="wprtsp_wrap">' + wprtsp_settings.conversions_sound_notification_markup + result + '</div>');
                        jQuery('#wprtsp_pop').css('height', jQuery("#wprtsp_pop").contents().find("html").height());
                        jQuery('#wprtsp_pop').css('width', jQuery("#wprtsp_pop").contents().find("body").width());
                    }).delay(wprtsp_settings.general_duration * 1000).fadeOut(2000);
                    //.contents().find("#wprtsp").html(result).slideDown(200).delay(wprtsp_settings.general_duration * 1000).fadeOut(2000);
                }

            }
            jQuery('#wprtsp_pop').mouseover(function () {
                wprtsp_pop.stop(true, true).show(200);
            }).mouseout(function () {
                wprtsp_pop.stop(true, true).delay(200).fadeOut(2000);
            });
        });
    });
}
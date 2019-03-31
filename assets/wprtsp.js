if (jQuery) {
    jQuery(document).ready(function ($) {
        time = Date.now();
        wprtsp_settings = JSON.parse(wprtsp_vars);
        console.dir(wprtsp_settings);
        if(wprtsp_settings.wprtsp_sound_notification){
            var wprtsp_audio = jQuery('#wprtsp_audio').length ? jQuery('#wprtsp_audio') : jQuery('<audio/>', {
            id: 'wprtsp_audio',
            class: 'wprtsp_audio',
            preload: 'auto',
            src: `${wprtsp_settings.url}assets/sounds/unsure.mp3`
        }).appendTo('body');
        }
        wp.heartbeat.interval(wprtsp_settings.initial_popup_time); // first proof should jump in in 5 seconds
        new Fingerprint2().get(function (result, components) {
            jQuery(document).on('heartbeat-send', function (e, data) {
                data['wprtsp'] = result + '_' + time;
            });
        })
        prime1 = [3, 7, 13, 19, 29];
        prime2 = [5, 11, 17, 23, 31];
        jQuery(document).on('heartbeat-tick', function (event, data) {
            if (data.hasOwnProperty('wprtsp')) {
                wp.heartbeat.interval(wprtsp_settings.subsequent_popup_time); // don't pop-up too much
                var wprtsp_pop = jQuery('#wprtsp_pop').length ? jQuery('#wprtsp_pop') : jQuery('<span/>', {
                    id: 'wprtsp_pop',
                    class: 'wprtsp_pop',
                }).appendTo('body');
                
                wprtsp_pop.attr('style', wprtsp_settings.style_box);
                result = JSON.parse(data['wprtsp']);
                if (wprtsp_settings.shop_type == 'generated') {
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
                    wprtsp_pop.html(`${wprtsp_settings.wprtsp_sound_notification_markup}<span class="geo wpsrtp_notification" style="${wprtsp_settings.wprtsp_notification_style}">Map</span><span class="wprtsp_text" style="${wprtsp_settings.wprtsp_text_style}"><span class="wprtsp_name">${result.first_name}</span> from <span class="wprtsp_location">${result.location.city}, ${result.location.state}</span> <span class="wprtsp_action" style="${wprtsp_settings.wprtsp_action_style}">${result.transaction} ${when} minutes ago</span></span>`).slideDown(200).delay(4000).fadeOut(2000);
                }
                if (wprtsp_settings.shop_type == 'edd' || wprtsp_settings.shop_type == 'wooc') {
                    wprtsp_pop.html(result).slideDown(200).delay(4000).fadeOut(2000);
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
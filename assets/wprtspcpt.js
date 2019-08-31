"use strict";
var settings = JSON.parse(wprtsp_vars);
var clock = false;
var flag = false;
var current_proof_type = false;
var wprtsp_pop = false;
var wprtsp_conversions_messages = [];
var wprtsp_hotstats_messages = [];
var wprtsp_livestats_messages = [];
var wprtsp_ctas_messages = [];
var debug = 1;
var title = false;
var titletimer = false;
var height = 0;
var wprtsp_startshow = 0;
var wprtsp_pauseshow = 0;
llog(settings);

function llog($str) {
    if (debug) {
        console.dir($str);
    }
}

if (jQuery) {
    jQuery(document).ready(function ($) {
        if (settings.proofs) {
            if (settings.hasOwnProperty('conversions_enable_mob') && settings.conversions_enable_mob && settings.is_mobile && settings.proofs.conversions && settings.proofs.conversions.length) {
                build_conversions();
            }
            if (settings.hasOwnProperty('conversions_enable') && settings.conversions_enable && !settings.is_mobile && settings.proofs.conversions && settings.proofs.conversions.length) {
                build_conversions();
            }
            if (!settings.hasOwnProperty('conversions_enable') && settings.proofs.conversions && settings.proofs.conversions.length) {
                build_conversions();
            }
            if (settings.hasOwnProperty('hotstats_enable') && settings.hotstats_enable && !settings.is_mobile && settings.proofs.hotstats && settings.proofs.hotstats.length ||
                settings.hasOwnProperty('hotstats_enable_mob') && settings.hotstats_enable_mob && settings.is_mobile && settings.proofs.hotstats && settings.proofs.hotstats.length
            ) {
                build_hotstats();
            }
            if (settings.hasOwnProperty('livestats_enable') && settings.livestats_enable && !settings.is_mobile && settings.proofs.livestats && settings.proofs.livestats.length ||
                settings.hasOwnProperty('livestats_enable_mob') && settings.livestats_enable_mob && settings.is_mobile && settings.proofs.livestats && settings.proofs.livestats.length
            ) {
                build_livestats();
            }
            if (settings.hasOwnProperty('ctas_enable') && settings.ctas_enable && !settings.is_mobile && settings.proofs.ctas && settings.proofs.ctas.length ||
                settings.hasOwnProperty('ctas_enable_mob') && settings.ctas_enable_mob && settings.is_mobile && settings.proofs.ctas && settings.proofs.ctas.length
            ) {
                build_ctas();
            }
            init_flag();
            wprtsp_pop = jQuery('#wprtsp_pop').length ? jQuery('#wprtsp_pop') : jQuery('<iframe/>', {
                id: 'wprtsp_pop',
                class: 'wprtsp_pop',
                frameborder: '0',
                scrolling: 'no',
                style: settings.styles.popup_style,
                srcdoc: '<html><head><base target="_parent"><link rel="stylesheet" type="text/css" href="' + settings.url + 'assets/proof-styles.css"></head><body id="wprtsp"><img src="' + settings.url + 'assets/verified.svg" /></body></html>',
            }).appendTo('body');
            clock = setTimeout(wprtsp_show_message, settings.general_initial_popup_time * 1000);
            llog('initiated');
        }
    });
} else {
    llog('no jq');
}

jQuery.fn.updateProof = function (message) {
    if (current_proof_type == 'ctas') {
        jQuery("#wprtsp_pop").css('border-radius', '5px');
    } else {
        if (settings.general_box_style == 'rounded') {
            jQuery("#wprtsp_pop").css('border-radius', '1000px');
        } else {
            jQuery("#wprtsp_pop").css('border-radius', '0px');
        }
    }
    if (eval(`settings.${current_proof_type}_sound_notification`)) {
        //llog( eval(  `settings.${current_proof_type}_sound_notification_file` ) ) ;
        var src = `${settings.url}assets/sounds/` + eval(`settings.${current_proof_type}_sound_notification_file`);
        //llog(src);
        var wprtsp_audio = jQuery('#wprtsp_audio').length ? jQuery('#wprtsp_audio') : jQuery('<audio/>', {
            id: 'wprtsp_audio',
            class: 'wprtsp_audio',
            preload: 'auto',
            autoplay: false,
            src: src
        }).appendTo('body');
        jQuery('#wprtsp_audio').attr('src', src);
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
    if (eval(`settings.${current_proof_type}_title_notification`)) {
        title = jQuery(document).attr("title");
        if (title) {
            titletimer = setInterval(titlenotification, 2000);
        }
    }
    jQuery("#wprtsp_pop").contents().find("#wprtsp").html(message);
    //if (current_proof_type == 'ctas') {
    try {
        //jQuery("#cta-grow").css('animation-duration', (settings.general_duration * 1000) + 's');
    } catch (e) {
        llog(e);
    }
    //}
    jQuery("#wprtsp_pop").contents().find("#wprtsp").attr('class', settings.general_notification_theme);
    jQuery("#wprtsp_pop").contents().find("#wprtsp_wrap").attr('class', current_proof_type);
    jQuery('#wprtsp_pop').css('height', jQuery("#wprtsp_pop").contents().find("html").height());
    jQuery('#wprtsp_pop').css('width', jQuery("#wprtsp_pop").contents().find("body").width());
    jQuery('#wprtsp_pop').css('max-width', 'calc( 100% - 20px )');
    height = jQuery("#wprtsp_pop").contents().find("html").height();
    //llog(height);
    return this;
}

function clearProof() {
    if (title) {
        jQuery(document).attr('title', title);
        clearInterval(titletimer);
        title = false;
    }
    //jQuery("#cta-grow").removeClass('cta-grow');
    clock = setTimeout(wprtsp_show_message, settings.general_subsequent_popup_time * 1000);
    return this;
}

jQuery.fn.updatePosition = function () {
    jQuery('#wprtsp_pop').css('bottom', '-' + (height + 10) + 'px');
    return this;
}

function wprtsp_show_message() {
    llog('wprtsp_show_message');
    var message = wprtsp_get_message();
    if (!message) {
        try {
            clearTimeout(clock);
        } catch (e) {
            llog(e);
        }
        return;
    }
    jQuery('#wprtsp_pop').updateProof(message).updatePosition().animate({
        "bottom": "10px",
        'opacity': '1'
    }, {
        duration: 300,
        complete: function () {
            wprtsp_startshow = Date.now();
            jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'grow ' + settings.general_duration + 's ease-in-out');
        }
    }).delay(settings.general_duration * 1000).animate({
        "bottom": '-' + height + 'px',
        'opacity': '0'
    }, {
        duration: 300,
        complete: function () {
            clearProof();
            jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'none');
        }
    });

    jQuery('#wprtsp_pop').mouseover(function () {
        wprtsp_pauseshow = Date.now();
        console.log('Show Started @: '+ wprtsp_startshow);
        console.log('Show Paused @: '+ wprtsp_pauseshow);
        clearTimeout(clock);
        wprtsp_pop.stop(true, true).show(200);
    }).mouseout(function () {
        console.log('Show stopped for ' + (wprtsp_pauseshow - wprtsp_startshow) + 'ms');
        console.log('Show remaining for ' + ((settings.general_duration * 1000) - (wprtsp_pauseshow - wprtsp_startshow)) + 'ms');
        wprtsp_pop.stop(true, true).delay((settings.general_duration * 1000) - (wprtsp_pauseshow - wprtsp_startshow)).animate({
            "bottom": '-' + height + 'px',
            'opacity': '0'
        }, {
            duration: 300,
            complete: function () {
                clearProof();
                jQuery("#wprtsp_pop").contents().find("#cta-grow").css('animation', 'none');
            }
        });
    });
}

function titlenotification() {
    if (document.title != settings.general_title_string) {
        jQuery(document).attr('title', settings.general_title_string);
    } else {
        jQuery(document).attr('title', title);
    }
}

function wprtsp_get_message() {
    llog('wprtsp_get_message');
    llog('flag:' + flag);
    if (flag == 's') {
        llog(flag);
        set_next_flag();
        current_proof_type = 'conversions';
        return wprtsp_conversions_messages.shift();
    }
    if (flag == 'h') {
        llog(flag);
        set_next_flag();
        current_proof_type = 'hotstats';
        return wprtsp_hotstats_messages.shift();
    }
    if (flag == 'l') {
        llog(flag);
        set_next_flag();
        current_proof_type = 'livestats';
        return wprtsp_livestats_messages.shift();
    }
    if (flag == 'c') {
        llog(flag);
        // once CTA is shown, no need to show newer popups
        set_next_flag();
        current_proof_type = 'ctas';
        //clearTimeout(clock);
        return wprtsp_ctas_messages.shift();
    }
}

function init_flag() {
    llog('init_flag');
    llog(wprtsp_conversions_messages);
    if (wprtsp_conversions_messages.length) {
        flag = 's';
        llog('initiated flag to ' + flag);
        return;
    }
    if (wprtsp_hotstats_messages.length) {
        flag = 'h';
        llog('initiated flag to ' + flag);
        return;
    }
    if (wprtsp_livestats_messages.length) {
        flag = 'l';
        llog('initiated flag to ' + flag);
        return;
    }
    if (wprtsp_ctas_messages.length) {
        flag = 'c';
        llog('initiated flag to ' + flag);
        return;
    }
}

function set_next_flag() {

    if (flag == 's') {
        if (wprtsp_hotstats_messages.length) {
            flag = 'h';
            return;
        }
        if (wprtsp_livestats_messages.length) {
            flag = 'l';
            return;
        }
        if (wprtsp_ctas_messages.length) {
            flag = 'c';
            return;
        }
        if (wprtsp_conversions_messages.length) {
            flag = 's';
            return;
        }
    }
    if (flag == 'h') {
        if (wprtsp_livestats_messages.length) {
            flag = 'l';
            return;
        }
        if (wprtsp_ctas_messages.length) {
            flag = 'c';
            return;
        }
        if (wprtsp_conversions_messages.length) {
            flag = 's';
            return;
        }
        if (wprtsp_hotstats_messages.length) {
            flag = 'h';
            return;
        }
    }
    if (flag == 'l') {
        if (wprtsp_ctas_messages.length) {
            flag = 'c';
            return;
        }
        if (wprtsp_conversions_messages.length) {
            flag = 's';
            return;
        }
        if (wprtsp_hotstats_messages.length) {
            flag = 'h';
            return;
        }
        if (wprtsp_livestats_messages.length) {
            flag = 'l';
            return;
        }
    }
    if (flag == 'c') {
        //clearTimeout(clock);
        //return;
        if (wprtsp_conversions_messages.length) {
            flag = 's';
            return;
        }
        if (wprtsp_hotstats_messages.length) {
            flag = 'h';
            return;
        }
        if (wprtsp_livestats_messages.length) {
            flag = 'l';
            return;
        }
        if (wprtsp_ctas_messages.length) {
            flag = 'c';
            return;
        }
    }
}

function build_conversions() {
    for (var i = 0; i < settings.proofs.conversions.length; i++) {
        wprtsp_conversions_messages.push(conversions_html(settings.proofs.conversions[i]));
    }
    llog(wprtsp_conversions_messages);
}

function conversions_html(conversion) {
    var link = get_ga_utm_link(conversion['link'], 'conversion');
    var verified_link = get_verified_link('conversion');
    var verified_markup = '';
    if (verified_link) {
        verified_markup = `<div class="wprtsp_verified"><a target="_blank" href="${verified_link}"><img class="verified-conversion-icon" src="${settings.url}assets/verified.svg" /><em class="text-verified">Verified</em> <em class="text-verified-by">by</em> <strong class="text-verified-brand">WP Social Proof</strong></a></div>`;
    }
    return `<div id="wprtsp_wrap" class="wprtsp-conversion">
    <a class="wprtsp_left" href="${link}"></a>
    <div class="wprtsp_right">
        <div class="wprtsp_line1"><a href="${link}">${conversion['line1']}</a></div>
        <div class="wprtsp_line2"><a href="${link}">${conversion['line2']}</a></div>
        ${verified_markup}
    </div></div>`;
}

function build_hotstats() {
    for (var i = 0; i < settings.proofs.hotstats.length; i++) {
        wprtsp_hotstats_messages.push(hotstats_html(settings.proofs.hotstats[i]));
    }
}

function hotstats_html(hotstat) {
    var verified_link = get_verified_link('hotstat');
    var verified_markup = '';
    if (verified_link) {
        verified_markup = `<div class="wprtsp_verified"><a target="_blank" href="${verified_link}"><img class="verified-conversion-icon" src="${settings.url}assets/verified.svg"/><em class="text-verified">Verified</em> <em class="text-verified-by">by</em> <strong class="text-verified-brand">WP Social Proof</strong></a></div>`;
    }
    return `<div id="wprtsp_wrap" class="wprtsp-hotstat">
    <span class="wprtsp_left"></span>
    <div class="wprtsp_right">
        <div class="wprtsp_line1">${hotstat['line1']}</div>
        <div class="wprtsp_line2">${hotstat['line2']}</div>
        ${verified_markup}
    </div></div>`;
}

function build_livestats() {
    for (var i = 0; i < settings.proofs.livestats.length; i++) {
        wprtsp_livestats_messages.push(livestats_html(settings.proofs.livestats[i]));
    }
    //llog(wprtsp_livestats_messages);
}

function livestats_html(livestat) {
    var verified_link = get_verified_link('livestat');
    var verified_markup = '';
    if (verified_link) {
        verified_markup = `<div class="wprtsp_verified"><a target="_blank" href="${verified_link}"><img class="verified-livestat-icon" src="${settings.url}assets/verified.svg"/><em class="text-verified">Verified</em> <em class="text-verified-by">by</em> <strong class="text-verified-brand">WP Social Proof</strong></a></div>`;
    }
    return `<div id="wprtsp_wrap" class="wprtsp-livestat">
    <span class="wprtsp_left"></span>
    <div class="wprtsp_right">
        <div class="wprtsp_line1">${livestat['line1']}</div>
        <div class="wprtsp_line2">${livestat['line2']}</div>
        ${verified_markup}
    </div></div>`;
}

function build_ctas() {
    for (var i = 0; i < settings.proofs.ctas.length; i++) {
        wprtsp_ctas_messages.push(ctas_html(settings.proofs.ctas[i]));
    }
    //llog(wprtsp_ctas_messages);
}

function ctas_html(cta) {
    if (cta['link']) {
        var link = get_ga_utm_link(cta['link'], 'cta');
        return `<span id="cta-grow"></span><div id="wprtsp_wrap" class="wprtsp-cta">
    <a class="wprtsp_cta_icon" href="${link}"></a>
    <div class="wprtsp_cta_body">
        <div class="wprtsp_cta_title"><a href="${link}">${cta['message']}</a></div>
        <div class="wprtsp_cta_message"><a class="cta_btn" href="${link}">${cta['button_text']}</a></div>
    </div></div>`;
    } else {
        return `<span id="cta-grow"></span><div id="wprtsp_wrap" class="wprtsp-cta">
        <span class="wprtsp_cta_icon"></span>
        <div class="wprtsp_cta_body">
            <div class="wprtsp_cta_title"><span>${cta['message']}</span></div>
            <div class="wprtsp_cta_message"><span class="cta_btn">${cta['button_text']}</span></div>
        </div></div>`;
    }
}

function get_ga_utm_link(link, type) {
    //var link = cta['link'];
    if (settings.general_ga_utm_tracking) {
        link = new URL(link);
        link.searchParams.set('utm_campaign', 'WP-Social-Proof-Pro');
        link.searchParams.set('utm_source', 'wp-social-proof-pro-' + type);
        link.searchParams.set('utm_content', 'wp-social-proof-pro-notification-id-' + settings.notification_id);
        return link.toString();
    }
    return link;
}

function get_verified_link(type) {
    if (settings.general_badge_enable) {
        if (settings.conversions_shop_type == 'Generated') {
            return;
        }
        var site_url = new URL(settings.url);
        var verified_url = new URL('https://wp-social-proof.com/verify');
        settings.t = type;
        var en = btoa(encodeURIComponent(JSON.stringify(settings)));
        verified_url.searchParams.set('wsps', btoa(encodeURIComponent(settings.conversions_shop_type)));

        var setvars = JSON.parse(JSON.stringify(settings));
        setvars.proofs.conversions = setvars.proofs.conversions.shift();

        verified_url.searchParams.set('wspv', btoa(encodeURIComponent(JSON.stringify(setvars))));
        verified_url.searchParams.set('wspc', +new Date());
        verified_url.searchParams.set('wspt', btoa(encodeURIComponent(type)));
        return verified_url.toString();
    }
}
console.log(1);

jQuery(document).ready(function ($) {
    $('.meta-box-sortables').sortable({
        disabled: true
    });

    $('.postbox .hndle').css('cursor', 'pointer');
});

jQuery(document).on('postbox-toggled', function (event, p) {
    console.log(p.id);
    elid = p.id;
    jQuery('.postbox').each(function (index, el) {
        if(el.id.includes('social-proof')) {
            if(el.id != elid) {
                console.log(el.id + '!=' + elid);
                //$(this).find('.inside').slideUp(800);
                if(! $(this).hasClass('closed')){
                    //$(this).addClass('closed');
                    $(this).find('.inside').hide(800, function(){
                        $(el).addClass('closed');
                        $(el).find('.inside').removeAttr('style');
                    });
                }
            }
        }
    });
    //jQuery(elid).slideIn(800);
})

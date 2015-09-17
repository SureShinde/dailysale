enquire.register('(max-width: ' + bp.medium + 'px)', {
    match: function () {
        jQuery('.aw-box').css('width','100%');
        jQuery('.aw-box').css('padding-left','0px');
    },
    unmatch: function () {
        jQuery('.aw-box').css('width','50%');
        jQuery('.aw-box').css('padding-left','15px');
    }
});

Event.observe(window, "load", function(){
    if (jQuery(document).width() <= bp.medium) {
        jQuery('.aw-box').css('width','100%');
        jQuery('.aw-box').css('padding-left','0px');
    }
});
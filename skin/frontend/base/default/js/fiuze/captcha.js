jQuery(document).ready(function(){
    jQuery(document).ajaxSuccess(function (event, xhr, settings) {
        var captcha_refresh = xhr.responseJSON['captcha_refresh'];
        if (typeof(captcha_refresh) !="undefined"){
            switch(captcha_refresh){
                //update captcha
                case 10:
                    var form_id = xhr.responseJSON['form_id'];
                    $(form_id).captcha.refresh($('catpcha-reload'));
                    break;
            }
        }
    });
});
Ajax.Responders.register({
    onComplete: function(event, xhr){
        var response = xhr.responseText.evalJSON();
        if (typeof(response['captcha_refresh']) !="undefined"){
            switch(response['captcha_refresh']){
                //update captcha
                case 10:
                    var form_id = response['form_id'];
                    $(form_id).captcha.refresh($('catpcha-reload'));
                    break;
            }
        }
    }
});
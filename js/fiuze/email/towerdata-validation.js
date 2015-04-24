Validation.add("validate-email", "Please enter a valid email address. For example johndoe@domain.com.", function(v) {
    var url = "/fiuze_towerdata/index/ajax/?email=" + encodeURIComponent(v);
    var ok = false;
    new Ajax.Request(url, {
        method: "get",
    asynchronous: false,
        onSuccess: function(transport) {
        var obj = response = eval("(" + transport.responseText + ")");
        validateTrueEmailMsg = obj.status_desc;
        if (obj.success === false) {
            Validation.get("validate-email").error = validateTrueEmailMsg;
            ok = false;
        } else {
            ok = true; /* return true or false */
        }
    },
    onComplete: function() {
        if ($("advice-validate-email-email")) {
            $("advice-validate-email-email").remove();
        }
        if ($("advice-validate-email-email_address")) {
            $("advice-validate-email-email_address").remove();
        }
        if ($("advice-validate-email-billing:email")) {
            $("advice-validate-email-billing:email").remove();
        }
        if ($("advice-validate-email-shipping:email")) {
            $("advice-validate-email-shipping:email").remove();
        }
        if ($("advice-validate-email-_accountemail")) {
            $("advice-validate-email-_accountemail").remove();
        }

    }
});
    return ok;
});
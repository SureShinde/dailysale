/**
* Braintree Payments Extension
*
* This source file is subject to the Braintree Payment System Agreement (https://www.braintreepayments.com/legal)
*
* DISCLAIMER
* This file will not be supported if it is modified.
*
* @copyright   Copyright (c) 2015 Braintree. (https://www.braintreepayments.com/)
*/
document.observe("dom:loaded", function() {
    if ($('payment_braintree_basic-state') != null 
        && $('payment_braintree_basic-state').up('div .section-config') != null) {

        $('payment_braintree_basic-state').up('div .section-config').hide();
    }
});

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
var BraintreeUtils = Class.create();
BraintreeUtils.prototype = {
    initialize: function(){
        this.client = false;
        this.nonceInputId = 'braintree_nonce';
        this.paypalSuccessInProgress = false;
        this.paypalSuccessInProgressContainerId = false;
    },
    setup: function(token, formId, onPaymentMethodReceived){
        var options = {id: formId};
        if (typeof onPaymentMethodReceived === 'function') {
            options['onPaymentMethodReceived'] = function(obj) {onPaymentMethodReceived(obj); };
        }
        braintree.setup(
            token,
            'custom',
            options
        );
        this.client = new braintree.api.Client({clientToken: token});
    },
    createHiddenInput: function(attributes, formId){
        var element, attr, value;
        element = document.createElement('input');
        for (attr in attributes) {
            if (attributes.hasOwnProperty(attr)) {
                value = attributes[attr];
                element.setAttribute(attr, value);
            }
        }
        element.setAttribute('type', 'hidden');
        element.setAttribute('value', '');
        if ($(element.id)) {
            $(element.id).remove();
        }
        $(formId).appendChild(element);
    },
    setupDataJS: function(environment, kountId, merchantId, formId){
        var env = '';
        if (environment == 'production') {
            env = BraintreeData.environments.production;
        } else {
            env = BraintreeData.environments.sandbox;    
        }
        if (kountId) {
            env = env.withId(kountId);
        }
        BraintreeData.setup(merchantId, formId, env);
    },
    setupDataJSOnEvent: function(environment, kountId, merchantId, formId){
        var self = this;
        window.onBraintreeDataLoad = function() {
            self.setupDataJS(environment, kountId, merchantId, formId);
        };
    },
    getPaymentNonce: function(prefix, cardholder, onSuccess, token){
        var nonceInputId = this.nonceInputId;
        var number = $(prefix + '_cc_number').value;
        var cvv = '';
        if ($(prefix + '_cc_cid')) {
            cvv = $(prefix + '_cc_cid').value;
        }
        var expMonth = $(prefix + '_expiration').value;
        var expYear = $(prefix + '_expiration_yr').value;
        if ($(prefix + '_cardholder_name') && $(prefix + '_cardholder_name').value) {
            cardholder = $(prefix + '_cardholder_name').value;
            cardholder = cardholder.stripTags();
        } 
        var params = {number: number, expirationMonth: expMonth, expirationYear: expYear, cardholderName : cardholder};
        if (cvv) {
            params['cvv'] = cvv;
        }
        var client = this.client;
        if (!client) {
            client = this.client = new braintree.api.Client({clientToken: token});
        }
        client.tokenizeCard(params, function (err, nonce) {
            $(nonceInputId).value = nonce;
            if (typeof onSuccess === 'function') {
                onSuccess();
            }
        });
    },
    setupPayPal: function(token, containerId, amount, currency, title,
        nonceInputId, enableShipping, enableBilling, locale, onSuccess, onCancel, unsupported){
        var container = containerId;
        var self = this;
        var params = {
            container: containerId,
            singleUse: true,
            amount: amount,
            currency: currency,
            paymentMethodNonceInputField: nonceInputId,
            locale: locale,
            onPaymentMethodReceived: function (obj) {
                if ($(container)) {
                    $(container).hide();
                }
                if (typeof onSuccess === 'function' && container === self.paypalSuccessInProgressContainerId && !self.isPayPalSuccessInProgress()) {
                    self.setPayPalSuccessInProgress();
                    onSuccess(obj);
                }
            },
            onUnsupported: function (obj) {
                alert(unsupported);
                if (typeof onCancel === 'function') {
                    onCancel();
                }
            },
            onCancelled: function (obj) {
                if (typeof onCancel === 'function') {
                    onCancel();
                }
            }
        };
        if (title) {
            params['displayName'] = title;
        }
        if (enableShipping) {
            params['enableShippingAddress'] = true;
        } else {
            params['enableShippingAddress'] = false;
        }
        if (enableBilling) {
            params['enableBillingAddress'] = true;
        } else {
            params['enableBillingAddress'] = false;
        }
        braintree.setup(token, "paypal", params);
        Event.observe($(containerId), 'click', function(){
            self.paypalSuccessInProgressContainerId = containerId;
        });
    },
    isPayPalSuccessInProgress: function(){
        return this.paypalSuccessInProgress;
    },
    setPayPalSuccessInProgress: function(){
        this.paypalSuccessInProgress = true;
    },
    place3DSecureOrder: function(card, amount, canContinueOnFail, onError, onSuccess, defaultErrorMessage, formId, tokenFieldId, token, onUserClose, beforeStart){
        if (typeof beforeStart == 'function') {
            beforeStart();
        }
        var client = this.client;
        if (!client) {
            client = this.client = new braintree.api.Client({clientToken: token});
        }
        var nonceInputId = this.nonceInputId;
        var liabilityShifted = 'liability_shifted';
        var liabilityShiftPossible = 'liability_shift_possible';
        this.createHiddenInput({name: "payment[" + liabilityShifted +"]", id: liabilityShifted}, formId);
        this.createHiddenInput({name: "payment[" + liabilityShiftPossible +"]", id: liabilityShiftPossible}, formId);
        var parameters = {amount: amount, creditCard: card};
        if (typeof onUserClose == 'function') {
            parameters['onUserClose'] = onUserClose;
        }
        client.verify3DS(parameters, function (error, response) {
            var errorMessage = '';
            if (error) {
                errorMessage = error.message;
            } else {
                var liabilityPossibleResult = response.verificationDetails.liabilityShiftPossible;
                var liabilityShiftedResult = response.verificationDetails.liabilityShifted;
                if (liabilityShiftedResult || canContinueOnFail || !liabilityPossibleResult) {
                    if ($(tokenFieldId)) {
                        $(tokenFieldId).disabled = true;
                    }
                    $(nonceInputId).value = response.nonce;
                    $(nonceInputId).disabled = false;
                    $(liabilityShifted).value = ~~liabilityShiftedResult;
                    $(liabilityShifted).disabled = false;
                    $(liabilityShiftPossible).value = ~~liabilityPossibleResult;
                    $(liabilityShiftPossible).disabled = false;
                    onSuccess();
                    if ($(tokenFieldId)) {
                        $(tokenFieldId).disabled = false;
                    }
                } else {
                    errorMessage = defaultErrorMessage;
                }
            }
            if (errorMessage) {
                $(liabilityShifted).remove();
                $(liabilityShiftPossible).remove();
                onError(errorMessage);
            }
        });
    }
};

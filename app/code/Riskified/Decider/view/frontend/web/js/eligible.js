define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/action/redirect-on-success',
    'Magento_Checkout/js/checkout-data'
], function ($, ko, quote, storage, redirectOnSuccessAction, checkoutData) {
    'use strict';

    function optInCall() {
        storage.post(
            'decider/deco/optIn',
            JSON.stringify({
                quote_id: quote.getQuoteId(),
                payment_method: checkoutData.getSelectedPaymentMethod()
            }),
            true
        ).done(function (result) {
            if (result.status == 'opt_in') {
                redirectOnSuccessAction.execute();
            }
        });
    }

    return {
        paymentFail: function(buttonColor, buttonTextColor, logoUrl) {
            storage.post(
                'decider/deco/isEligible',
                JSON.stringify({
                    quote_id: quote.getQuoteId()
                }),
                true
            ).done(function (result) {
                if (result.status == 'eligible') {
                    $('.payment-method._active #deco-container').html("<div id='deco-widget'></div>");
                    window.drawDecoWidget(() => {
                        return optInCall();
                    }, {
                        buttonColor: buttonColor,
                        buttonText: buttonTextColor,
                        logoUrl: logoUrl
                    });
                }
            });
        }
    }
});

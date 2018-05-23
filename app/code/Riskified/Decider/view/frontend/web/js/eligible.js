define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/storage'
], function ($, ko, quote, storage) {
    'use strict';

    function optInCall() {
        storage.post(
            'decider/deco/optIn',
            JSON.stringify({
                quote_id: quote.getQuoteId()
            }),
            true
        ).done(function (result) {

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
                    $('#deco-container').html("<div id='deco-widget'></div>");
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

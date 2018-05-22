define([
    'jquery',
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/storage'
], function ($, ko, quote, storage) {
    'use strict';

    return {
        /**
         *
         * @return {*}
         */
        getIsEligible: function () {
            return this.eligible = ko.observable();
        },

        paymentFail: function() {
            var quoteId = quote.getQuoteId();
            storage.post(
                'decider/deco/isEligible',
                JSON.stringify({
                    quote_id: quoteId
                }),
                true
            ).done(function (result) {
                if (result.status == 'eligible') {
                    $('#deco-container').html("<div id='deco-widget'></div>");
                    window.drawDecoWidget();
                }
            });
        }
    };
});

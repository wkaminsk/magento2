/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define([
    'jquery',
    'underscore',
    'Magento_Checkout/js/view/payment/default',
    'Magento_Braintree/js/view/payment/adapter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/payment/additional-validators',
    'Magento_Vault/js/view/payment/vault-enabler',
    'Magento_Checkout/js/action/create-billing-address',
    'mage/translate',
    'Riskified_Decider/js/model/advice'
], function (
    $,
    _,
    Component,
    Braintree,
    quote,
    fullScreenLoader,
    additionalValidators,
    VaultEnabler,
    createBillingAddress,
    $t,
    advice
) {
    'use strict';

    var mixin = {

        /**
         * Prepare data to place order
         * @param {Object} data
         */
        beforePlaceOrder: function (data) {
            //check Riskified-Api-Advise-Call response
            let params = { quote_id: quote.getQuoteId(), gateway: "braintree_paypal", email:  data.details.email};

            try {
                advice.validate(params);
            } catch (e) {
                return false;
            }

            this.setPaymentMethodNonce(data.nonce);

            if ((this.isRequiredBillingAddress() || quote.billingAddress() === null) &&
                typeof data.details.billingAddress !== 'undefined'
            ) {
                this.setBillingAddress(data.details, data.details.billingAddress);
            }

            if (this.isSkipOrderReview()) {
                this.placeOrder();
            } else {
                this.customerEmail(data.details.email);
                this.isReviewRequired(true);
            }
        },
    };

    return function (target) {
        return target.extend(mixin);
    };
});

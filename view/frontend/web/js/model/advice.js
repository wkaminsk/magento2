/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @api
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'mage/url'
], function ($, quote, urlBuilder) {
    'use strict';

    return {
        payload : {},
        additionalPayload : {},
        setGateway: function(gateway) {
          this.gateway = gateway;
        },
        setMode: function(mode) {
          this.mode = mode;
        },
        preparePayload : function() {
            let payload = {
                mode: this.mode,
                quote_id: quote.getQuoteId(),
                email: quote.guestEmail,
                gateway: this.gateway
            };

            this.payload = {
                ...payload,
                ...this.additionalPayload
            };
        },
        setAdditionalPayload : function(payload) {
            this.additionalPayload = payload;
        },
        registerSuccessCallback : function(callback) {
            this.successCallback = callback;
        },
        registerDenyCallback : function(callback) {
            this.denyCallback = callback;
        },
        registerDisabledCallback : function(callback) {
            this.disableCallback = callback;
        },
        validate : function() {
            this.preparePayload();

            return this.doCall("/decider/advice/call", this.payload).success((response) => {
                let apiResponseStatus = response.status;
                if (apiResponseStatus === 0){
                    this.successCallback();
                } else {
                    if (apiResponseStatus === 3){
                        this.denyCallback();
                    } else if (apiResponseStatus === 9999){
                        this.disableCallback();
                    } else {
                        this.successCallback();
                    }
                }
            });
        },
        deny : function() {
            return this.doCall("/decider/order/deny", this.payload);
        },
        doCall : function(url, payload) {
            return $.ajax({
                url: urlBuilder.build(url),
                type: 'POST',
                params: payload,
                async: false,
                contentType: "application/json"
            });
        }
    };
});

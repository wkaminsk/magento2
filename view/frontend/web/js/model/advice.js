/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/**
 * @api
 */
define(['jquery', 'mage/url'], function ($, urlBuilder) {
    'use strict';

    return {
        validate : function(payload, successfullCallback, denyCallback, disabledCallback) {
            return this.doCall("/decider/advice/call", payload).success((response) => {
                let apiResponseStatus = response.status;
                if (apiResponseStatus === 0){
                    successfullCallback();
                } else {
                    if (apiResponseStatus === 3){
                        denyCallback();
                    } else if (apiResponseStatus === 9999){
                        disabledCallback();
                    } else {
                        successfullCallback();
                    }
                }
            });
        },
        deny : function(payload) {
            return this.doCall("/decider/order/deny", payload);
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

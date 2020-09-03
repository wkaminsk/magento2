var config = {
    map: {
        '*': {
            "Magento_Checkout/js/model/quote" : "Riskified_Decider/js/model/quote"
        }
    },
    config: {
        mixins: {
            'Adyen_Payment/js/view/payment/method-renderer/adyen-cc-method': {
                'Riskified_Decider/js/view/payment/method-renderer/adyen-cc-method-mixin': true
            },
            'Magento_Braintree/js/view/payment/validator-handler': {
                'Riskified_Decider/js/view/payment/braintree/validator-handler-mixin': true
            },
            'Magento_Braintree/js/view/payment/3d-secure': {
                'Riskified_Decider/js/view/payment/braintree/3d-secure-mixin': true
            },
        },
    }
};
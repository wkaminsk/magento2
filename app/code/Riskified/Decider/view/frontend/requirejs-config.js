var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/payment/default': {
                'Riskified_Decider/js/payment': true
            },
            'Adyen_Payment/js/view/payment/method-renderer/adyen-cc-method': {
                'Riskified_Decider/js/payment/adyen-cc-method': true
            }
        }
    },
    map: {
        '*': {
            deco: 'Riskified_Decider/js/deco',
            eligible: 'Riskified_Decider/js/eligible'
        }
    }
};

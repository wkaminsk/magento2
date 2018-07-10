var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/payment/default': {
                'Riskified_Decider/js/payment': true
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

/**
 *                       ######
 *                       ######
 * ############    ####( ######  #####. ######  ############   ############
 * #############  #####( ######  #####. ######  #############  #############
 *        ######  #####( ######  #####. ######  #####  ######  #####  ######
 * ###### ######  #####( ######  #####. ######  #####  #####   #####  ######
 * ###### ######  #####( ######  #####. ######  #####          #####  ######
 * #############  #############  #############  #############  #####  ######
 *  ############   ############  #############   ############  #####  ######
 *                                      ######
 *                               #############
 *                               ############
 *
 * Adyen Payment module (https://www.adyen.com/)
 *
 * Copyright (c) 2015 Adyen BV (https://www.adyen.com/)
 * See LICENSE.txt for license details.
 *
 * Author: Adyen <magento@adyen.com>
 */
/*browser:true*/
/*global define*/
define(
    [
        'underscore',
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Adyen_Payment/js/action/place-order',
        'mage/translate',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Customer/js/model/customer',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Magento_Checkout/js/model/quote',
        'ko',
        'Adyen_Payment/js/model/installments',
    ],
    function (_, $, Component, placeOrderAction, $t, additionalValidators, customer, creditCardData, quote, ko, installments) {
        'use strict';

        var mixin = {
            /**
             * @override
             */
            placeOrder: function (data, event) {
                var self = this,
                    placeOrder;

                if (event) {
                    event.preventDefault();
                }


                var options = {};
                var cseInstance = adyen.createEncryption(options);
                var generationtime = self.getGenerationTime();

                var cardData = {
                    number: self.creditCardNumber(),
                    cvc: self.creditCardVerificationNumber(),
                    holderName: self.creditCardOwner(),
                    expiryMonth: self.creditCardExpMonth(),
                    expiryYear: self.creditCardExpYear(),
                    generationtime: generationtime
                };

                var data = cseInstance.encrypt(cardData);
                self.encryptedData(data);

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);

                    $.when(placeOrder).fail(function (response) {
                        $(document).trigger('paymentFail');
                        self.isPlaceOrderActionAllowed(true);
                    });
                    return true;
                }
                return false;
            }
        };

        return function (target) { // target == Result that Magento_Ui/.../default returns.
            return target.extend(mixin); // new result that all other modules receive
        };
    });



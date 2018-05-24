<?php

namespace Riskified\Decider\Model\Payment;

class Deco extends \Magento\Payment\Model\Method\AbstractMethod
{
    const PAYMENT_METHOD_DECO_CODE = 'deco';

    /**
     * Payment code name
     *
     * @var string
     */
    protected $_code = self::PAYMENT_METHOD_DECO_CODE;
}

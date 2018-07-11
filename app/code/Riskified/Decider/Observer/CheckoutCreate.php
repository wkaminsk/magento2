<?php

namespace Riskified\Decider\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riskified\Decider\Api\Api;
use Riskified\Decider\Model\Payment\Deco;

class CheckoutCreate implements ObserverInterface
{
    private $logger;
    private $orderApi;
    private $checkoutResource;

    public function __construct(
        \Riskified\Decider\Logger\Order $logger,
        \Riskified\Decider\Api\Order $orderApi,
        \Riskified\Decider\Model\Resource\Checkout $checkoutResource
    ) {
        $this->logger = $logger;
        $this->orderApi = $orderApi;
        $this->checkoutResource = $checkoutResource;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();
        if ($order->getPayment()->getMethod() === Deco::PAYMENT_METHOD_DECO_CODE) {
            return $this;
        }

        try {
            $this->checkoutResource->generateCheckoutId($order->getQuoteId());
            $this->orderApi->post($order, Api::ACTION_CHECKOUT_CREATE);
        } catch (\Exception $e) {
            $this->logger->logException($e);
        }

        return $this;
    }
}

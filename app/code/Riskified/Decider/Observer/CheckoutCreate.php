<?php

namespace Riskified\Decider\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riskified\Decider\Api\Api;

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
        $order = $observer->getOrder();
        try {
            $this->checkoutResource->generateCheckoutId($order->getQuoteId());
            $this->orderApi->post($order, Api::ACTION_CHECKOUT_CREATE);
        } catch (\Exception $e) {
            $this->logger->logException($e);
        }

        return $this;
    }
}

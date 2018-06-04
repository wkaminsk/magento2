<?php

namespace Riskified\Decider\Plugin;

use Riskified\Decider\Api\Api;

class OrderServicePlugin
{
    /**
     * @var \Riskified\Decider\Api\Log
     */
    private $logger;

    /**
     * @var \Riskified\Decider\Api\Order
     */
    private $apiOrderLayer;

    /**
     * CheckoutCreate constructor.
     *
     * @param \Riskified\Decider\Logger\Order $logger
     * @param \Riskified\Decider\Api\Order $orderApi
     */
    public function __construct(
        \Riskified\Decider\Logger\Order $logger,
        \Riskified\Decider\Api\Order $orderApi
    ) {
        $this->logger = $logger;
        $this->apiOrderLayer = $orderApi;
    }

    public function aroundPlace(
        \Magento\Sales\Model\Service\OrderService $subject,
        \Closure $proceed,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $return = $proceed($order);

        try {
            $this->apiOrderLayer->post($order, Api::ACTION_CHECKOUT_CREATE);
        } catch (\Exception $e) {
            $this->logger->logException($e);
        }

        return $return;
    }
}

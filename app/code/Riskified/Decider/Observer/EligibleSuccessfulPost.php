<?php
namespace Riskified\Decider\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riskified\Decider\Api\Deco;

class EligibleSuccessfulPost implements ObserverInterface
{
    private $logger;
    private $orderApi;

    public function __construct(
        \Riskified\Decider\Logger\Order $logger,
        \Riskified\Decider\Api\Order $orderApi
    ) {
        $this->logger = $logger;
        $this->orderApi = $orderApi;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $response = $observer->getResponse();

        if (!isset($response->order)) {
            return $this;
        }

        $orderId = $response->order->id;
        $status = $response->order->status;
        $description = $response->order->description;

        if ($status == Deco::STATUS_ELIGIBLE) {
        }
    }
}

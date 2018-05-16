<?php

namespace Riskified\Decider\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riskified\Decider\Api\Api;
use Riskified\Decider\Api\Deco;

class OrderPaymentFailed implements ObserverInterface
{
    private $logger;
    private $apiOrderLayer;
    private $apiConfig;
    private $apiOrderConfig;

    /**
     * @var Deco
     */
    private $deco;

    /**
     * UpdateOrderState constructor.
     *
     * @param \Riskified\Decider\Api\Log $logger
     * @param \Riskified\Decider\Api\Config $config
     * @param \Riskified\Decider\Api\Order\Config $apiOrderConfig
     * @param \Riskified\Decider\Api\Order $orderApi
     * @param Deco $deco
     */
    public function __construct(
        \Riskified\Decider\Api\Log $logger,
        \Riskified\Decider\Api\Config $config,
        \Riskified\Decider\Api\Order\Config $apiOrderConfig,
        \Riskified\Decider\Api\Order $orderApi,
        Deco $deco
    ) {
        $this->logger = $logger;
        $this->apiOrderConfig = $apiOrderConfig;
        $this->apiOrderLayer = $orderApi;
        $this->apiConfig = $config;
        $this->deco = $deco;
    }

    /**
     * Observer handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return OrderPaymentFailed
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        try {
//            $this->apiOrderLayer->post(
//                $order,
//                Api::ACTION_CHECKOUT_DENIED
//            );

            $this->deco->post(
                $order,
                Deco::ACTION_ELIGIBLE
            );
        } catch (\Exception $e) {
            $this->logger->logException($e);
        }

        return $this;
    }
}

<?php
namespace Riskified\Decider\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riskified\Decider\Api\Api;

class OrderPlacedAfter implements ObserverInterface
{
    private $_logger;
    private $_orderApi;

    public function __construct(
        \Riskified\Decider\Logger\Order $logger,
        \Riskified\Decider\Api\Order $orderApi
    ) {
        $this->_logger = $logger;
        $this->_orderApi = $orderApi;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();

        if (!$order) {
            return;
        }

        if ($order->dataHasChangedFor('state')) {

            $payment = $order->getPayment();
            $paymentMethodCode = $payment->getMethod();

            if ($paymentMethodCode == 'adyen_oneclick' || $paymentMethodCode == 'adyen_cc') {
                if ($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING) {
                    $this->triggerSave($order);
                }
            } else {
                $this->triggerSave($order);
            }
        } else {
            $this->_logger->debug(
                __("Order place event observer: The state was not changed for this order. Aborting.")
            );
        }
    }

    /**
     * Trigger post method for allowed order.
     *
     * @param \Magento\Sales\Model\Order $order
     */
    private function triggerSave($order)
    {
        try {
            $this->_orderApi->post($order, Api::ACTION_UPDATE);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }
}

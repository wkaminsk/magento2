<?php

namespace Riskified\Decider\Observer;

use \Magento\Sales\Model\Order;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\CatalogInventory\Api\StockManagementInterface;
use \Magento\CatalogInventory\Model\Indexer\Stock\Processor as StockProcessor;

class UnCancelOrder implements ObserverInterface
{
    /**
     * @var  \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var StockProcessor
     */
    protected $stockIndexerProcessor;

    /**
     * UpdateOrderState constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        StockManagementInterface $stockManagement,
        StockProcessor $stockIndexerProcessor
    ) {
        $this->eventManager = $eventManager;
        $this->stockManagement = $stockManagement;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
    }

    /**
     * Observer handler
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getOrder();
        $comment = $observer->getComment();

        if ($order->isCanceled()) {
            $state = Order::STATE_PROCESSING;
            $productStockQty = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $productStockQty[$item->getProductId()] = $item->getQtyCanceled();
                foreach ($item->getChildrenItems() as $child) {
                    $productStockQty[$child->getProductId()] = $item->getQtyCanceled();
                    $child->setQtyCanceled(0);
                    $child->setTaxCanceled(0);
                    $child->setDiscountTaxCompensationCanceled(0);
                }
                $item->setQtyCanceled(0);
                $item->setTaxCanceled(0);
                $item->setDiscountTaxCompensationCanceled(0);
                $this->eventManager->dispatch('sales_order_item_uncancel', ['item' => $item]);
            }

            $this->subtractInventory($order, $productStockQty);

            $order->setSubtotalCanceled(0);
            $order->setBaseSubtotalCanceled(0);
            $order->setTaxCanceled(0);
            $order->setBaseTaxCanceled(0);
            $order->setShippingCanceled(0);
            $order->setBaseShippingCanceled(0);
            $order->setDiscountCanceled(0);
            $order->setBaseDiscountCanceled(0);
            $order->setTotalCanceled(0);
            $order->setBaseTotalCanceled(0);
            $order->setState($state)
                ->setStatus($order->getConfig()->getStateDefaultStatus($state));
            if (!empty($comment)) {
                $order->addStatusHistoryComment($comment, false);
            }
        }

        return $this;
    }

    /**
     * Subtract items qtys from stock related with uncancel products.
     *
     * @param $order
     * @param $productQty
     *
     * @return $this
     */
    public function subtractInventory($order, $productQty)
    {
        if ($order->getInventoryProcessed()) {
            return $this;
        }

        /**
         * Reindex items
         */
        $itemsForReindex = $this->stockManagement->registerProductsSale(
            $productQty,
            $order->getStore()->getWebsiteId()
        );
        $productIds = [];
        foreach ($itemsForReindex as $item) {
            $item->save();
            $productIds[] = $item->getProductId();
        }
        if (!empty($productIds)) {
            $this->stockIndexerProcessor->reindexList($productIds);
        }
        $order->setInventoryProcessed(true);

        return $this;
    }
}

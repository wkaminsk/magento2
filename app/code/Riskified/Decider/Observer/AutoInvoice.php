<?php

namespace Riskified\Decider\Observer;

use Magento\Framework\App\ObjectManagerFactory;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Model\Context;
use Magento\Sales\Model\Order as OrderEntity;
use Magento\Sales\Model\Service\InvoiceService;
use Riskified\Common\Riskified;
use Riskified\Decider\Api\Config;
use Riskified\Decider\Api\Order as OrderApi;
use Riskified\Decider\Api\Order\Log;
use Riskified\Decider\Logger\Order;
use Magento\Framework\App\Config\ScopeConfigInterface as ScopeInterface;

/**
 * Observer Auto Invoice Class.
 * Creates invoice when order was approved in Riskified.
 *
 * @category Riskified
 * @package  Riskified_Decider
 */
class AutoInvoice implements ObserverInterface
{
    /**
     * Module main logger class.
     *
     * @var Order
     */
    protected $logger;

    /**
     * Module api class.
     *
     * @var OrderApi
     */
    protected $apiOrder;

    /**
     * Api logger.
     *
     * @var Log
     */
    protected $apiOrderLogger;

    /**
     * Module config.
     *
     * @var Config
     */
    protected $apiConfig;


    /**
     * Magento's invoice service.
     *
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * Context class.
     *
     * @var Context
     */
    protected $context;

    /**
     * Object Manager class.
     *
     * @var ObjectManagerFactory
     */
    protected $objectManager;

    /**
     * State class used to emulate admin scope during invoice creation.
     *
     * @var State
     */
    protected $state;

    /**
     * Scope config class
     *
     * @var ScopeInterface
     */
    protected $scopeConfig;

    /**
     * AutoInvoice constructor.
     *
     * @param Log                  $apiOrderLogger
     * @param Order                $logger
     * @param Config               $apiConfig
     * @param OrderApi             $orderApi
     * @param InvoiceService       $invoiceService
     * @param Context              $context
     * @param ScopeInterface $scopeConfig
     * @param ObjectManagerFactory $objectManagerFactory
     */
    public function __construct(
        Log $apiOrderLogger,
        Order $logger,
        Config $apiConfig,
        OrderApi $orderApi,
        InvoiceService $invoiceService,
        Context $context,
        ScopeInterface $scopeConfig,
        ObjectManagerFactory $objectManagerFactory
    ) {
        $this->logger = $logger;
        $this->context = $context;
        $this->apiOrder = $orderApi;
        $this->apiConfig = $apiConfig;
        $this->apiOrderLogger = $apiOrderLogger;
        $this->invoiceService = $invoiceService;
        $this->objectManager = $objectManagerFactory;
        $this->scopeConfig = $scopeConfig;
        $this->state = $context->getAppState();
    }

    /**
     * Main method ran during event raise.
     *
     * @param Observer $observer
     *
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if (!$this->canRun()) {

            $this->logger->addInfo(
                sprintf('Auto-invoicing disabled.')
            );
            return $this;
        }

        $this->logger->addInfo(
            sprintf('Auto-invoicing enabled. Processing observer.')
        );

        $order = $observer->getOrder();

        if (!$order || !$order->getId()) {
            $this->logger->addInfo(
                sprintf('Order object is invalid. Aborting auto-invoicing process.')
            );

            return $this;
        }

        $this->logger->addInfo(
            sprintf('Auto-invoicing order #%s', $order->getIncrementId())
        );


        if (!$order->canInvoice()
            || $order->getState() != OrderEntity::STATE_PROCESSING
        ) {
            $this->logger->addInfo(
                sprintf('Order #%s cannot be invoiced.', $order->getIncrementId())
            );

            if ($this->apiConfig->isLoggingEnabled()) {
                $this->apiOrderLogger->logInvoice($order);
            }

            return $this;
        }
        try {
            $this->updateStripeApiConnection($order);
        } catch (\Exception $e) {
            $this->logger->addCritical(
                sprintf('Error during processing Stripe integration: %s', $e->getMessage())
            );
            return $this;
        }
        $invoice = $this->state->emulateAreaCode(
            'adminhtml',
            [$this->invoiceService, 'prepareInvoice'],
            [$order]
        );

        if (!$invoice->getTotalQty()) {

            $this->logger->addInfo(
                sprintf('Invoice cannot be created. No items to invoice.')
            );

            return $this;
        }
        try {
            $invoice
                ->setRequestedCaptureCase($this->apiConfig->getCaptureCase())
                ->addComment(
                    __(
                        'Invoice automatically created by Riskified when order was approved'
                    ),
                    false,
                    false
                );
            
            $this->state->emulateAreaCode(
                'adminhtml',
                [$invoice, 'register']
            );
        } catch (\Exception $e) {
            $this->logger->addInfo("Error creating invoice: " . $e->getMessage());
            return $this;
        }
        try {
            $this->state->emulateAreaCode(
                'adminhtml',
                [$invoice, 'save']
            );

            $this->state->emulateAreaCode(
                'adminhtml',
                [$invoice->getOrder(), 'save']
            );
        } catch (\Exception $e) {
            $this->logger->addCritical(
                'Error creating transaction: ' . $e->getMessage()
            );

            return $this;
        }

        $this->logger->addInfo("Auto-invoicing process was successful.");
    }

    /**
     * Method checks if observer can be run
     *
     * @return bool
     */
    protected function canRun()
    {
        if (!$this->apiConfig->isAutoInvoiceEnabled()) {
            return false;
        }
        if (!$this->apiConfig->isEnabled()) {
            return false;
        }

        return true;
    }

    /**
     * Methods prepares stripe api to handle stripe capture for multi store installations
     *
     * @param \Magento\Sales\Model\Order $order
     */
    private function updateStripeApiConnection($order)
    {
        if (class_exists("\Stripe\Stripe")) {
            $this->logger->addInfo(
                sprintf('Stripe API found, updating secret key for order #%s.', $order->getIncrementId())
            );

            $storeId = $order->getStoreId();

            $stripeMode = $this->scopeConfig->getValue(
                "payment/cryozonic_stripe/stripe_mode",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $secretKey = $this->scopeConfig->getValue(
                "payment/cryozonic_stripe/stripe_{$stripeMode}_sk",
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );

            $this->logger->addInfo(
                sprintf(
                    'New Stripe Secret has been fetched from config for Store ID %s where Order #%s was created.',
                    $storeId,
                    $order->getIncrementId()
                )
            );
            \Stripe\Stripe::setApiKey(trim($secretKey));

            $this->logger->addInfo(
                sprintf('Stripe secret key was found, processing invoice.')
            );
        }
    }
}

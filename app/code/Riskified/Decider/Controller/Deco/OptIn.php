<?php

namespace Riskified\Decider\Controller\Deco;

use Magento\Framework\App\Action\Action;
use Riskified\Decider\Api\Deco;
use Riskified\Decider\Api\Api;
use \Magento\Sales\Model\Order;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;

class OptIn extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Riskified\Decider\Api\Log
     */
    private $logger;

    /**
     * @var Deco
     */
    private $deco;

    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    private $sessionManager;

    /**
     * @var \Riskified\Decider\Api\Order
     */
    private $api;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    /**
     * @var \Riskified\Decider\Api\Order
     */
    private $orderApi;

    /**
     * @var \Riskified\Decider\Api\Config
     */
    private $apiConfig;

    /**
     * @var \Riskified\Decider\Api\Order\Config
     */
    private $apiOrderConfig;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * IsEligible constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param Deco $deco
     * @param \Riskified\Decider\Api\Log $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param CartManagementInterface $quoteManagement
     * @param EventManager $eventManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        Deco $deco,
        \Riskified\Decider\Api\Log $logger,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Riskified\Decider\Api\Order $api,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riskified\Decider\Api\Order $orderApi,
        \Riskified\Decider\Api\Config $apiConfig,
        \Riskified\Decider\Api\Order\Config $apiOrderConfig,
        CartManagementInterface $quoteManagement,
        EventManager $eventManager
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->deco = $deco;
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
        $this->api = $api;
        $this->checkoutSession = $checkoutSession;
        $this->orderApi = $orderApi;
        $this->apiConfig = $apiConfig;
        $this->apiOrderConfig = $apiOrderConfig;
        $this->quoteManagement = $quoteManagement;
        $this->eventManager = $eventManager;
    }

    /**
     * OptIn Api call.
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $this->logger->log('Deco OptIn request, quote_id: ' . $this->checkoutSession->getQuoteId());

            $response = $this->deco->post(
                $this->checkoutSession->getQuote(),
                Deco::ACTION_OPT_IN
            );

            if ($response->order->status == 'opt_in') {
                if (!$this->checkoutSession->getLastRealOrder()->getQuoteId() !== $this->checkoutSession->getQuote()->getId()) {
                    $this->prepareOrder();
                }
                $this->processOrder($this->checkoutSession->getQuote()->getPayment()->getMethod());

                $this->orderApi->post(
                    $this->checkoutSession->getLastRealOrder(),
                    Api::ACTION_CREATE
                );
            }

            return $resultJson->setData([
                'success' => true,
                'status' => $response->order->status,
                'message' => $response->order->description
            ]);
        } catch (\Exception $e) {
            $this->logger->logException($e);

            return $resultJson->setData(
                [
                    'success' => false,
                    'status' => 'not_eligible',
                    'message' => $e->getMessage()
                ]
            );
        }
    }

    /**
     * Return customer quote
     *
     * @param string $paymentMethod
     *
     * @return void
     */
    protected function processOrder($paymentMethod)
    {
        switch ($paymentMethod) {
            case 'authorizenet_directpost':
                $directPostSession = $this->_objectManager->get(\Magento\Authorizenet\Model\Directpost\Session::class);
                $incrementId = $directPostSession->getLastOrderIncrementId();
                if ($incrementId) {
                    /* @var $order \Magento\Sales\Model\Order */
                    $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class)->loadByIncrementId($incrementId);
                    if ($order->getId()) {
                        try {
                            /** @var \Magento\Quote\Api\CartRepositoryInterface $quoteRepository */
                            $quoteRepository = $this->_objectManager->create(\Magento\Quote\Api\CartRepositoryInterface::class);
                            /** @var \Magento\Quote\Model\Quote $quote */
                            $quote = $quoteRepository->get($order->getQuoteId());
                            $quote->setIsActive(0);
                            $quoteRepository->save($quote);
                            $this->api->unCancelOrder(
                                $order,
                                __('Payment by %1 has been declined. Order processed by Deco Payments', $order->getPayment()->getMethod())
                            );
                            $order->getPayment()->setMethod('deco')->save();
                            $order->save();

                            if ($this->apiConfig->getConfigStatusControlActive()) {
                                $state = Order::STATE_PROCESSING;
                                $status = $this->apiOrderConfig->getOnHoldStatusCode();
                                $order->setState($state)->setStatus($status);
                                $order->addStatusHistoryComment('Order submitted to Riskified', false);
                                $order->save();
                            }
                        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                            $this->logger->logException($e);
                        }
                    }
                }
        }
    }

    public function prepareOrder()
    {
        $previousMethod = $this->checkoutSession->getQuote()->getPayment()->getMethod();
        $this->checkoutSession->getQuote()->getPayment()->setMethod('deco');
        $order = $this->quoteManagement->submit($this->checkoutSession->getQuote());
        if (null == $order) {
            throw new LocalizedException(
                __('An error occurred on the server. Please try to place the order again.')
            );
        }

        $order->addStatusHistoryComment(__('Payment by %1 has been declined. Order processed by Deco Payments', $previousMethod), false);
        if ($this->apiConfig->getConfigStatusControlActive()) {
            $state = Order::STATE_PROCESSING;
            $status = $this->apiOrderConfig->getOnHoldStatusCode();
            $order->setState($state)->setStatus($status);
            $order->addStatusHistoryComment('Order submitted to Riskified', false);
        }
        $order->save();

        $this->checkoutSession->setLastQuoteId($this->checkoutSession->getQuote()->getId());
        $this->checkoutSession->setLastSuccessQuoteId($this->checkoutSession->getQuote()->getId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());

        $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $this->checkoutSession->getQuote()]);

        return $this;
    }
}

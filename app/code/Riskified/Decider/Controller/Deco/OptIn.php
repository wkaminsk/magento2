<?php

namespace Riskified\Decider\Controller\Deco;

use Magento\Framework\App\Action\Action;
use Riskified\Decider\Api\Deco;
use Riskified\Decider\Api\Api;

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

    private $orderApi;

    /**
     * IsEligible constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param Deco $deco
     * @param \Riskified\Decider\Api\Log $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        Deco $deco,
        \Riskified\Decider\Api\Log $logger,
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Riskified\Decider\Api\Order $api,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riskified\Decider\Api\Order $orderApi
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->deco = $deco;
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
        $this->api = $api;
        $this->checkoutSession = $checkoutSession;
        $this->orderApi = $orderApi;
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
                $this->checkoutSession->getLastRealOrder(),
                Deco::ACTION_OPT_IN
            );

            if ($response->order->status == 'opt_in') {
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
                            $this->api->unCancelOrder($order, __('Order processed by Deco Payments'));
                            $order->getPayment()->setMethod('deco')->save();
                            $order->save();
                        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                            $this->logger->logException($e);
                        }
                    }
                }
        }
    }
}

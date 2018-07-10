<?php

namespace Riskified\Decider\Controller\Deco;

use Magento\Framework\App\Action\Action;
use Riskified\Decider\Api\Api;

class CheckoutDenied extends Action
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
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

    private $orderApi;

    /**
     * IsEligible constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riskified\Decider\Api\Log $logger
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riskified\Decider\Api\Log $logger,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riskified\Decider\Api\Order $orderApi
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderApi = $orderApi;
        $this->logger = $logger;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Is Eligible Api call.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->logger->log('Checkout Denied request, quote_id: ' . $this->checkoutSession->getQuoteId());
            $this->checkoutSession->getQuote()->setQuoteId($this->checkoutSession->getQuote()->getId());
            $this->orderApi->post(
                $this->checkoutSession->getQuote(),
                Api::ACTION_CHECKOUT_DENIED
            );
            return $resultJson->setData([
                'success' => true
            ]);
        } catch (\Exception $e) {
            $this->logger->logException($e);
            return $resultJson->setData(
                [
                    'success' => false,
                    'message' => $e->getMessage()
                ]
            );
        }
    }
}

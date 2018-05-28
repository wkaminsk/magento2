<?php

namespace Riskified\Decider\Controller\Deco;

use Magento\Framework\App\Action\Action;
use Riskified\Decider\Api\Deco;

class IsEligible extends Action
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
     * @var \Magento\Checkout\Model\Session
     */
    private $checkoutSession;

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
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->deco = $deco;
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
            $this->logger->log('Deco isEligible request, quote_id: ' . $this->checkoutSession->getQuoteId());
            $response = $this->deco->post(
                $this->checkoutSession->getQuoteId(),
                Deco::ACTION_ELIGIBLE
            );

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
}

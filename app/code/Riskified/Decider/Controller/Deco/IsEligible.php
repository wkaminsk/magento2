<?php

namespace Riskified\Decider\Controller\Deco;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;
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
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $helper;

    /**
     * IsEligible constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param Deco $deco
     * @param \Riskified\Decider\Api\Log $logger
     * @param \Magento\Framework\Json\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        Deco $deco,
        \Riskified\Decider\Api\Log $logger,
        \Magento\Framework\Json\Helper\Data $helper
    ) {
        parent::__construct($context);

        $this->resultJsonFactory = $resultJsonFactory;
        $this->deco = $deco;
        $this->logger = $logger;
        $this->helper = $helper;
    }

    /**
     * Is Eligible Api call.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $params = $this->helper->jsonDecode($this->getRequest()->getContent());
        $resultJson = $this->resultJsonFactory->create();
        try {
            $this->logger->log('Deco isEligible request, quote_id: ' . $params['quote_id']);
            $response = $this->deco->post(
                $params['quote_id'],
                Deco::ACTION_ELIGIBLE
            );
            $resultJson->setData([
                'success' => true,
                'status' => $response->order->status,
                'message' => $response->order->description
            ]);

            $this->logger->log($resultJson);

            return $resultJson;
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

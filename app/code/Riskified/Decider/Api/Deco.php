<?php

namespace Riskified\Decider\Api;

use Riskified\OrderWebhook\Transport\CurlTransport;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Magento\Framework\App\Helper\Context;

class Deco
{
    const ACTION_ELIGIBLE = 'eligible';
    const ACTION_OPT_IN = 'opt_in';
    const STATUS_ELIGIBLE = 'eligible';
    const STATUS_NOT_ELIGIBLE = 'not_eligible';

    /**
     * @var Config
     */
    protected $apiConfig;

    /**
     * @var Api
     */
    protected $api;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $_eventManager;

    /**
     * @var \Riskified\Decider\Api\Order\Helper
     */
    private $_orderHelper;

    /**
     * Deco constructor.
     *
     * @param Config $apiConfig
     * @param Api $api
     * @param Context $context
     */
    public function __construct(
        Config $apiConfig,
        Api $api,
        Context $context,
        \Riskified\Decider\Api\Order\Helper $orderHelper
    ) {
        $this->apiConfig = $apiConfig;
        $this->api = $api;
        $this->_eventManager = $context->getEventManager();
        $this->_orderHelper = $orderHelper;

        $this->api->initSdk();
    }

    /**
     * @param $order
     * @param $action
     *
     * @return $this|mixed
     *
     * @throws \Exception
     * @throws \Riskified\OrderWebhook\Exception\CurlException
     */
    public function post($order, $action)
    {
        if (!$this->apiConfig->isEnabled()) {
            return $this;
        }

        if (!$this->apiConfig->isDecoEnabled()) {
            return $this;
        }

        $transport = $this->getTransport();

        if (!$order) {
            throw new \Exception("Order doesn't not exists");
        }

        $this->_orderHelper->setOrder($order);

        $eventData = array(
            'order' => $order,
            'action' => $action
        );

        try {
            switch ($action) {
                case self::ACTION_ELIGIBLE:
                    $orderForTransport = $this->load($order);
                    $response = $transport->isEligible($orderForTransport);
                    break;

                case self::ACTION_OPT_IN:
                    $orderForTransport = $this->load($order);
                    $response = $transport->optIn($orderForTransport);
                    break;
            }

            $eventData['response'] = $response;

            $this->_eventManager->dispatch(
                'riskified_decider_post_eligible_success',
                $eventData
            );
        } catch (\Riskified\OrderWebhook\Exception\CurlException $curlException) {
            throw $curlException;
        } catch (\Exception $e) {
            throw $e;
        }

        return $response;
    }

    /**
     * @param $order
     * @return Model\Order
     */
    private function load($order)
    {
        $order_array = array(
            'id' => $this->_orderHelper->getOrderOrigId(),
        );

        $order = new Model\Checkout(array_filter($order_array, 'strlen'));

        return $order;
    }

    /**
     * @return CurlTransport
     */
    public function getTransport()
    {
        $transport = new CurlTransport(new Signature\HttpDataSignature(), $this->apiConfig->getConfigDecoEnv());
        $transport->timeout = 15;
        $transport->use_https = true;

        return $transport;
    }
}

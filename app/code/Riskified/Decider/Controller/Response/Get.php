<?php
namespace Riskified\Decider\Controller\Response;

use \Riskified\DecisionNotification;

class Get extends \Magento\Framework\App\Action\Action
{
    private $apiOrderLayer;
    private $api;
    private $apiLogger;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Riskified\Decider\Api\Api $api,
        \Riskified\Decider\Api\Order $apiOrder,
        \Riskified\Decider\Api\Log $apiLogger
    )
    {
        parent::__construct($context);
        $this->api = $api;
        $this->apiLogger = $apiLogger;
        $this->apiOrderLayer = $apiOrder;
    }


    public function execute()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();

        $this->apiLogger->log("Start execute");
        $statusCode = 200;
        $id = null;
        $msg = null;
        try {
            $notification = $this->api->parseRequest($request);
            $id = $notification->id;
            if ($notification->status == 'test' && $id == 0) {
                $statusCode = 200;
                $msg = 'Test notification received successfully';
                $this->apiLogger->log("Test Notification received: ", serialize($notification));
            } else {
                $this->apiLogger->log("Notification received: ", serialize($notification));
                $order = $this->apiOrderLayer->loadOrderByOrigId($id);
                if (!$order || !$order->getId()) {
                    $this->apiLogger->log("ERROR: Unable to load order (" . $id . ")");
                    $statusCode = 400;
                    $msg = 'Could not find order to update.';
                } else {
                    $this->apiOrderLayer->update($order, $notification->status, $notification->oldStatus, $notification->description);
                    $statusCode = 200;
                    $msg = 'Order-Update event triggered.';
                }
            }
        } catch (\Riskified\DecisionNotification\Exception\AuthorizationException $e) {
            $this->apiLogger->logException($e);
            $statusCode = 401;
            $msg = 'Authentication Failed.';
        } catch (\Riskified\DecisionNotification\Exception\BadPostJsonException $e) {
            $this->apiLogger->logException($e);
            $statusCode = 400;
            $msg = "JSON Parsing Error.";
        } catch (\Exception $e) {
            $this->apiLogger->log("ERROR: while processing notification for order $id");
            $this->apiLogger->logException($e);
            $statusCode = 500;
            $msg = "Internal Error";
        }

        $this->apiLogger->log($msg);
        $response->setHttpResponseCode($statusCode);
        $response->setHeader('Content-Type', 'application/json');
        $response->setBody('{ "order" : { "id" : "' . $id . '", "description" : "' . $msg . '" } }');
        $response->sendResponse();

        exit;
    }
}

<?php

namespace Riskified\Decider\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Riskified\Decider\Api\Order;
use Riskified\Decider\Api\Api;
use Riskified\Decider\Api\Order\Helper;
use Riskified\Decider\Api\Order\Config;
use Riskified\Decider\Api\Order\Log;
use Riskified\Common\Signature;
use Riskified\OrderWebhook\Model;
use Riskified\OrderWebhook\Transport;

class TestDeclineEmailStoreLanguage extends Command
{
    private $_api;
    private $_orderHelper;
    private $_context;
    private $_eventManager;
    private $_messageManager;
    private $_backendAuthSession;
    private $_orderFactory;
    private $logger;
    private $session;
    private $date;
    private $queueFactory;

    public function __construct(
        Api $api,
        Helper $orderHelper,
        Config $apiConfig,
        Log $logger,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Order $orderFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Riskified\Decider\Model\QueueFactory $queueFactory,
        \Magento\Framework\Session\SessionManagerInterface $session
    ) {
        $this->_api = $api;
        $this->_orderHelper = $orderHelper;
        $this->_apiConfig = $apiConfig;
        $this->_context = $context;
        $this->_eventManager = $context->getEventManager();
        $this->_backendAuthSession = $backendAuthSession;
        $this->_messageManager = $messageManager;
        $this->_orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->session = $session;
        $this->date = $date;
        $this->queueFactory = $queueFactory;
        $this->_api->initSdk();

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('riskified:testDeclineEmail:storeLanguage');
        $this->setDescription('Checking Riskified declined email store language');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        return 'test';
    }
}
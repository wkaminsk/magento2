<?php

namespace Riskified\Decider\Model\Resource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\AbstractModel;

class Checkout extends AbstractDb
{
    private $random;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     *
     * @param string $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Math\Random $random,
        $connectionName = null
    ) {
        $this->random = $random;

        parent::__construct($context, $connectionName);
    }


    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riskified_checkout', 'quote_id');
    }

    public function generateCheckoutId($quoteId)
    {
        $connection = $this->getConnection();
        $data = [[
            'quote_id' => $quoteId,
            'checkout_id' => $quoteId . '_' . $this->random->getUniqueHash()
        ]];

        $connection->insertOnDuplicate($this->getTable('riskified_checkout'), $data);
    }

    public function getCheckoutId($quoteId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('riskified_checkout'),
            ['checkout_id']
        )->where('quote_id = ?', (int) $quoteId);

        return $this->getConnection()->fetchOne($select);
    }
}

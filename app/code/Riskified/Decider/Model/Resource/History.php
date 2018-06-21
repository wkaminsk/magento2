<?php

namespace Riskified\Decider\Model\Resource;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class History extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('riskified_history', 'history_id');
    }
}
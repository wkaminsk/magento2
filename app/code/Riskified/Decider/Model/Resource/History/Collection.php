<?php

namespace Riskified\Decider\Model\Resource\History;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Riskified\Decider\Model\History', 'Riskified\Decider\Model\Resource\History');
    }
}

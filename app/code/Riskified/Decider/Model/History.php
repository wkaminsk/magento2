<?php

namespace Riskified\Decider\Model;

class History extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Riskified\Decider\Model\Resource\History');
    }
}

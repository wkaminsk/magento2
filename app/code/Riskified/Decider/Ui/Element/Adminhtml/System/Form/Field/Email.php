<?php

namespace Riskified\Decider\Ui\Element\Adminhtml\System\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Backend\Block\Template\Context;

class Email extends AbstractFieldArray
{
    /**
     * @param Context $context
     *
     * @param array $data
     */
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _prepareToRender()
    {
        $this->addColumn(
            'email',
            [
                'label' => __('Address')
            ]
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Email');
    }
}

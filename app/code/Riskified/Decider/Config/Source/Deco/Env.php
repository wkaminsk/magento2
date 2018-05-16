<?php

namespace Riskified\Decider\Config\Source\Deco;

class Env implements \Magento\Framework\Option\ArrayInterface
{
    const SANDBOX = 'sandboxw.decopayments.com';
    const STAGING = 'stagingw.decopayments.com';
    const PRODUCTION = 'w.decopayments.com';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SANDBOX,
                'label' => __('Sandbox')
            ],
            [
                'value' => self::STAGING,
                'label' => __('Staging')
            ],
            [
                'value' => self::PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }
}
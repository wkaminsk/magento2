<?php

namespace Riskified\Decider\Config\Source\Deco;

class Env implements \Magento\Framework\Option\ArrayInterface
{
    const SANDBOX = 'https://sandboxw.decopayments.com';
    const STAGING = 'https://stagingw.decopayments.com';
    const PRODUCTION = 'https://w.decopayments.com';

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
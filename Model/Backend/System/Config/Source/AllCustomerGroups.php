<?php

namespace TNW\Subscriptions\Model\Backend\System\Config\Source;

class AllCustomerGroups implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => 0, 'label' => __('All Allowed Groups')],
            ['value' => 1, 'label' => __('Specific Groups')]
        ];
    }
}

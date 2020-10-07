<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Data\Form;

/**
 * Plugin for \Magento\Framework\Data\Form\AbstractForm
 */
class AbstractForm
{
    /**
     * Add extra form element: addon_text
     *
     * @param \Magento\Framework\Data\Form\AbstractForm $subject
     * @param \Magento\Framework\Data\Form\Element\Fieldset $result
     * @return \Magento\Framework\Data\Form\Element\Fieldset
     */
    public function afterAddFieldset(
        \Magento\Framework\Data\Form\AbstractForm $subject,
        \Magento\Framework\Data\Form\Element\Fieldset $result
    ) {
        if ($result) {
            $result->addType('addon_text', \TNW\Subscriptions\Model\Data\Form\Element\AddonText::class);
        }

        return $result;
    }
}

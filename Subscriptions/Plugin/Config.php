<?php
/**
 *  Copyright © 2017 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Plugin;

/**
 * Plugin for \Magento\Config\Model\Config
 * Need to validate and convert currency fields.
 */
class Config
{
    /**
     * Allowed sections to check
     * @var array
     */
    private $sections = [
        'tnw_subscriptions_product',
    ];

    /**
     * Allowed fields to check
     * @var array
     */
    private $fields = [
        'trial_price'
    ];

    /**
     * Validate and convert currency fields.
     *
     * @param \Magento\Config\Model\Config $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
        if (in_array($subject->getSection(), $this->sections)) {
            //$subject['groups']['trial']['fields']['trial_price']['value']
            $groups = $subject->getGroups();
            foreach($groups as $groupName => $group) {
                foreach($group['fields'] as $fieldName => $field) {
                    if (in_array($fieldName, $this->fields)) {
                        $value = $field['value'];
                        $price = sprintf("%F", $value);
                        $price = round($price, 2);
                        $groups[$groupName]['fields'][$fieldName]['value'] = $price;
                    }
                }
            }
            $subject->setGroups($groups);
        }

        return $proceed();
    }
}

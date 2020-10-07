<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Plugin\Config\Model\Structure\Element;

/**
 * Class Field - plugin to modify result on showInDefaultMethod
 */
class Field
{
    /**
     * Adds possibility to save config fields in single store mode
     * Exclusively for dynamic tnw_subscriptions_payment_methods/active_methods config
     *
     * @param $subject
     * @param $result
     * @return bool
     */
    public function afterShowInDefault($subject, $result)
    {
        $fieldData = $subject->getData();
        if (isset($fieldData['path']) && $fieldData['path'] == "tnw_subscriptions_payment_methods/active_methods") {
            $result = true;
        }
        return $result;
    }
}

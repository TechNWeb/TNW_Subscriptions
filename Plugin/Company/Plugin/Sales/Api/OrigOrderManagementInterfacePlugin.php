<?php
/**
 *  Copyright © 2018 TechNWeb, Inc. All rights reserved.
 *  See TNW_LICENSE.txt for license details.
 *
 */
namespace TNW\Subscriptions\Plugin\Company\Plugin\Sales\Api;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;

/**
 * Class OrigOrderManagementInterfacePlugin - plugin
 */
class OrigOrderManagementInterfacePlugin
{
    /**
     * @param $origsubject
     * @param callable $proceed
     * @param OrderManagementInterface $subject
     * @param OrderInterface $result
     * @return OrderInterface
     */
    public function aroundAfterPlace(
        $origsubject,
        callable $proceed,
        OrderManagementInterface $subject,
        OrderInterface $result
    ) {
        return $result;
    }
}

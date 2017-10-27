<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Shipment;

/**
 * Edit wrapper
 */
class DetailsEditForm extends \Magento\Framework\View\Element\Template
{
    /**
     * Retrieve edit form content.
     *
     * @return string
     */
    public function getFormContentHtml()
    {
        return $this->getChildHtml();
    }
}

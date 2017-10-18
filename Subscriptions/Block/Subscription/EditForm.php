<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription;

/**
 * Customer address edit block
 */
class EditForm extends \Magento\Framework\View\Element\Template
{
    public function getFormContentHtml()
    {
        return $this->getChildHtml('customer_address_edit');
    }

    public function getFormClass()
    {
        return 'shipping-form';
    }

    public function getFormId()
    {
        return 'shipping-address-form';
    }
}

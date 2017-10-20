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
    /** HTML element form class */
    const FORM_CLASS = 'shipping-form';

    /** HTML element form id */
    const FORM_ID = 'shipping-address-form';

    /**
     * Retrieve edit form content.
     *
     * @return string
     */
    public function getFormContentHtml()
    {
        return $this->getChildHtml('customer_address_edit');
    }

    /**
     * Return form class.
     *
     * @return string
     */
    public function getFormClass()
    {
        return self::FORM_CLASS;
    }

    /**
     * Return form id.
     *
     * @return string
     */
    public function getFormId()
    {
        return self::FORM_ID;
    }
}

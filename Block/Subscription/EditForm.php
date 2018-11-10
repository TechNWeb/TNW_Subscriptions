<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription;

/**
 * Customer address edit block
 */
class EditForm extends \Magento\Framework\View\Element\Template
{
    /**
     * HTML element form class.
     *
     * @var string
     */
    protected $formClass = 'shipping-form';

    /**
     * HTML element form id.
     *
     * @var string
     */
    protected $formId = 'shipping-address-form';

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
        return $this->formClass;
    }

    /**
     * Return form id.
     *
     * @return string
     */
    public function getFormId()
    {
        return $this->formId;
    }

    /**
     * Return Cancel button id.
     *
     * @return string
     */
    public function getCancelButtonId()
    {
        return 'cancel-save';
    }

    /**
     * Return save button id.
     *
     * @return string
     */
    public function getSaveButtonId()
    {
        return 'save_address';
    }
}

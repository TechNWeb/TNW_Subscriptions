<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Shipment;

use TNW\Subscriptions\Block\Subscription\EditForm;

/**
 * Shipping details edit wrapper
 */
class DetailsEditForm extends EditForm
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
    protected $formId = 'shipping-details-form';

    /**
     * @inheritdoc
     */
    public function getFormContentHtml()
    {
        return $this->getChildHtml();
    }

    /**
     * @inheritdoc
     */
    public function getCancelButtonId()
    {
        return $this->getFormId() . '-cancel';
    }

    /**
     * @inheritdoc
     */
    public function getSaveButtonId()
    {
        return $this->getFormId() . '-save';
    }
}

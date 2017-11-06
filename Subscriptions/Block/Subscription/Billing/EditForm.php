<?php
/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Block\Subscription\Billing;

/**
 * Customer billing address edit block.
 */
class EditForm extends \TNW\Subscriptions\Block\Subscription\EditForm
{
    /**
     * @inheritdoc
     */
    protected $formClass = 'billing-form';

    /**
     * @inheritdoc
     */
    protected $formId = 'billing-address-form';
}

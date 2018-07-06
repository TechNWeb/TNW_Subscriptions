<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Model\SubscriptionProfile\DataProvider\Modal;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\Api\Filter;

/**
 * Data provider for customer exists from in popup.
 */
class CustomerExistsForm extends AbstractDataProvider
{
    /**
     * Form data scope.
     */
    const DATA_SCOPE_CUSTOMER_ALREADY_EXISTS_MODAL_FORM = 'tnw_subscriptionprofile_customer_exists_modal_form';

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
    }
}
